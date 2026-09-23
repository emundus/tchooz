<?php

namespace Tchooz\Services\Sofis;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\api\Api;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;

/**
 * Two-step authentication for the Sofis (Microsoft Dynamics 365) integration:
 *
 *  1. Obtain an IDAMA access token from the authorization server using client_credentials with
 *     HTTP Basic authentication (base64(client_id:secret)).
 *  2. Exchange that IDAMA token for an Azure AD access token on the EntraId token endpoint, sending
 *     it as the client_assertion (jwt-bearer) together with the Entra client id, scope and the
 *     target Dynamics resource.
 *
 * The resulting Azure AD token is cached until it expires so repeated calls within one lifecycle
 * reuse it. The authenticator receives an already-decrypted configuration (the synchronizer
 * decrypts secrets before handing it over) and is agnostic of where the configuration comes from,
 * which keeps it testable.
 */
class SofisAuthenticator
{
	private const ASSERTION_TYPE = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

	private const GRANT_TYPE = 'client_credentials';

	private string $idpTokenUrl;

	private string $idpClientId;

	private string $idpSecret;

	private string $tokenEndpoint;

	private string $entraClientId;

	private string $scope;

	private string $resource;

	private Api $transport;

	private ?string $accessToken = null;

	private int $expiresAt = 0;

	public function __construct(array $config, ?Api $transport = null)
	{
		$this->idpTokenUrl       = $config['idp_token_url'] ?? '';
		$this->idpClientId       = $config['idp_client_id'] ?? '';
		$this->idpSecret         = $config['idp_secret'] ?? '';
		$this->tokenEndpoint     = $config['token_endpoint'] ?? '';
		$this->entraClientId     = $config['entra_client_id'] ?? '';
		$this->scope             = $config['scope'] ?? 'default';
		$this->resource          = $config['resource'] ?? '';
		$this->transport         = $transport ?? new Api();

		Log::addLogger(['text_file' => 'com_emundus.sofis.php'], Log::ALL, ['com_emundus.sofis']);
	}

	/**
	 * Build an authenticator from a Sofis synchronizer, reading its authentication configuration and
	 * decrypting the stored secrets. Single source of truth for turning stored config into runnable
	 * credentials — used both by the transport and by the setup authentication test.
	 */
	public static function fromSynchronizer(SynchronizerEntity $synchronizer, ?Api $transport = null): self
	{
		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}

		$authConfig = $synchronizer->getConfig()['authentication'] ?? [];

		$config = [
			'idp_token_url'       => $authConfig['idp_token_url'] ?? '',
			'idp_client_id'       => $authConfig['idp_client_id'] ?? '',
			'idp_secret'          => !empty($authConfig['idp_secret']) ? \EmundusHelperFabrik::decryptDatas($authConfig['idp_secret']) : '',
			'token_endpoint'      => $authConfig['token_endpoint'] ?? '',
			'entra_client_id'     => $authConfig['entra_client_id'] ?? '',
			'scope'               => $authConfig['scope'] ?? 'default',
			'resource'            => $authConfig['resource'] ?? '',
		];

		return new self($config, $transport);
	}

	/**
	 * Return a valid Azure AD access token, running the two-step exchange only when the cached token
	 * is missing or about to expire.
	 *
	 * @throws \Exception
	 */
	public function getAccessToken(): string
	{
		if ($this->accessToken !== null && $this->expiresAt > (time() + 30))
		{
			return $this->accessToken;
		}

		$idamaToken = $this->requestIdamaToken();

		$body = [
			'client_id'             => $this->entraClientId,
			'client_assertion_type' => self::ASSERTION_TYPE,
			'client_assertion'      => $idamaToken,
			'grant_type'            => self::GRANT_TYPE,
			'scope'                 => $this->scope,
			'resource'              => $this->resource,
		];

		$response = $this->transport->post($this->tokenEndpoint, $body, ['Accept' => 'application/json']);

		if (empty($response) || $response['status'] !== 200 || empty($response['data']->access_token))
		{
			Log::add('Sofis token exchange failed : ' . json_encode($response), Log::ERROR, 'com_emundus.sofis');

			throw new \RuntimeException(Text::_('COM_EMUNDUS_SOFIS_TOKEN_EXCHANGE_FAILED'));
		}

		$this->accessToken = $response['data']->access_token;
		$expiresIn         = !empty($response['data']->expires_in) ? (int) $response['data']->expires_in : 3599;
		$this->expiresAt   = time() + $expiresIn;

		return $this->accessToken;
	}

	/**
	 * Step 1: obtain an IDAMA access token from the authorization server using client_credentials
	 * with HTTP Basic authentication.
	 *
	 * @throws \Exception
	 */
	private function requestIdamaToken(): string
	{
		if (empty($this->idpTokenUrl) || empty($this->idpClientId) || empty($this->idpSecret))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SOFIS_MISSING_CONFIGURATION'));
		}

		$this->transport->setBaseUrl($this->idpTokenUrl);
		$this->transport->setClient();

		$response = $this->transport->post(
			$this->idpTokenUrl,
			['grant_type' => self::GRANT_TYPE, 'scope' => $this->scope],
			[
				'Authorization' => 'Basic ' . base64_encode($this->idpClientId . ':' . $this->idpSecret),
				'Accept'        => 'application/json',
			]
		);

		if (empty($response) || $response['status'] !== 200 || empty($response['data']->access_token))
		{
			Log::add('Sofis IDAMA token request failed : ' . json_encode($response), Log::ERROR, 'com_emundus.sofis');

			throw new \RuntimeException(Text::_('COM_EMUNDUS_SOFIS_IDAMA_TOKEN_FAILED'));
		}

		return $response['data']->access_token;
	}
}
