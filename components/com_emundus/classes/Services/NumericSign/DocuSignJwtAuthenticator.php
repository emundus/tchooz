<?php

namespace Tchooz\Services\NumericSign;

use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Client\ApiException;
use DocuSign\eSign\Client\Auth\OAuth;
use DocuSign\eSign\Configuration;
use Joomla\CMS\Log\Log;

class DocuSignJwtAuthenticator
{
	private string $integrationKey;
	private string $userId;
	private string $privateKey;

	private string $authUrl;
	private string $baseUrl;
	private ApiClient $client;

	public function __construct(array $config)
	{
		$this->integrationKey = $config['integration_key'];
		$this->userId         = $config['user_id'];
		$this->privateKey     = $config['private_key']; // contenu du fichier .pem
		$this->authUrl        = $config['mode'] === 'PRODUCTION' ? OAuth::$PRODUCTION_OAUTH_BASE_PATH : OAuth::$DEMO_OAUTH_BASE_PATH;
		$this->baseUrl        = $config['mode'] === 'PRODUCTION' ? 'https://www.docusign.net/restapi' : 'https://demo.docusign.net/restapi';

		$dsConfig = new Configuration();
		$dsConfig->setHost($this->baseUrl);
		$oauth = new OAuth();
		$oauth->setOAuthBasePath($this->authUrl);
		$this->client = new ApiClient($dsConfig, $oauth);
	}

	/**
	 * Retourne un access_token DocuSign valide via JWT.
	 */
	public function getAccessToken(): ?string
	{
		try {
			$scopes = "signature impersonation";

			$response = $this->client->requestJWTUserToken(
				$this->integrationKey,
				$this->userId,
				$this->privateKey,
				$scopes,
				3600 // 1 heure
			);

			return $response[0]['access_token'];
		}
		catch (ApiException $e) {
			Log::add(
				'DocuSign JWT Auth error: ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docusign'
			);
			return null;
		}
	}

	/**
	 * Construit un ApiClient complet avec Authorization: Bearer <token>.
	 */
	public function getAuthenticatedClient(): ApiClient
	{
		$token = $this->getAccessToken();

		if (!$token) {
			throw new \RuntimeException("Unable to authenticate with DocuSign via JWT.");
		}

		$config = new Configuration();
		$config->setHost($this->baseUrl);
		$config->addDefaultHeader('Authorization', 'Bearer ' . $token);

		return new ApiClient($config);
	}
}
