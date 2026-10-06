<?php

namespace Unit\Component\Emundus\Class\Services\Sofis;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\api\Api;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Services\Sofis\SofisAuthenticator;

/**
 * @package     Unit\Component\Emundus\Class\Services\Sofis
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Sofis\SofisAuthenticator
 */
class SofisAuthenticatorTest extends UnitTestCase
{
	private string $idpUrl = 'https://auth.example.com/am/oauth2/realms/root/realms/services/access_token';

	private string $exchangeUrl = 'https://exchange.example.com/entraid/oauth2/v1/token';

	private string $resource = 'https://sofis-integration.sandbox.operations.dynamics.com';

	protected function setUp(): void
	{
		// No dataset needed — the authenticator takes a plain config array and a transport.
	}

	protected function tearDown(): void
	{
		// No dataset created — nothing to clean up.
	}

	private function config(): array
	{
		return [
			'idp_token_url'   => $this->idpUrl,
			'idp_client_id'   => 'idp-client',
			'idp_secret'      => 'idp-secret',
			'token_endpoint'  => $this->exchangeUrl,
			'entra_client_id' => 'entra-client',
			'scope'           => 'default',
			'resource'        => $this->resource,
		];
	}

	/**
	 * Return a transport whose post() returns the IDAMA token for the authorization server and the
	 * Azure token for the exchange endpoint, recording every call into $calls.
	 */
	private function transportReturningTokens(array &$calls, ?array $idamaResponse = null, ?array $azureResponse = null): Api
	{
		$idamaResponse = $idamaResponse ?? ['status' => 200, 'data' => (object) ['access_token' => 'IDAMA_TOKEN', 'expires_in' => 3600]];
		$azureResponse = $azureResponse ?? ['status' => 200, 'data' => (object) ['access_token' => 'AZURE_TOKEN', 'expires_in' => 3599]];

		$transport = $this->createMock(Api::class);
		$transport->method('post')->willReturnCallback(function ($url, $body, $headers = []) use (&$calls, $idamaResponse, $azureResponse) {
			$calls[] = ['url' => $url, 'body' => $body, 'headers' => $headers];

			return $url === $this->idpUrl ? $idamaResponse : $azureResponse;
		});

		return $transport;
	}

	// -------------------------------------------------------------------------
	// Two-step exchange
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Sofis\SofisAuthenticator::getAccessToken
	 * @return void
	 */
	public function testGetAccessTokenReturnsAzureToken(): void
	{
		$calls         = [];
		$authenticator = new SofisAuthenticator($this->config(), $this->transportReturningTokens($calls));

		$this->assertSame('AZURE_TOKEN', $authenticator->getAccessToken(), 'The Azure AD token from the exchange step must be returned.');
	}

	/**
	 * @covers \Tchooz\Services\Sofis\SofisAuthenticator::getAccessToken
	 * @return void
	 */
	public function testGetAccessTokenPerformsBasicAuthThenAssertionExchange(): void
	{
		$calls = [];
		(new SofisAuthenticator($this->config(), $this->transportReturningTokens($calls)))->getAccessToken();

		$this->assertCount(2, $calls, 'Authentication must perform two requests: token then exchange.');

		// Step 1 — authorization server with HTTP Basic auth.
		$this->assertSame($this->idpUrl, $calls[0]['url'], 'The first request must target the authorization server.');
		$this->assertStringStartsWith('Basic ', $calls[0]['headers']['Authorization'], 'The token request must use HTTP Basic authentication.');
		$this->assertSame('idp-client:idp-secret', base64_decode(substr($calls[0]['headers']['Authorization'], 6)), 'Basic auth must encode idp client id and secret.');

		// Step 2 — exchange the IDAMA token as the client_assertion.
		$this->assertSame($this->exchangeUrl, $calls[1]['url'], 'The second request must target the exchange endpoint.');
		$this->assertSame('IDAMA_TOKEN', $calls[1]['body']['client_assertion'], 'The IDAMA token must be sent as the client_assertion.');
		$this->assertSame('entra-client', $calls[1]['body']['client_id'], 'The Entra client id must be sent as client_id.');
		$this->assertSame('client_credentials', $calls[1]['body']['grant_type'], 'The grant type must be client_credentials.');
		$this->assertSame($this->resource, $calls[1]['body']['resource'], 'The Dynamics resource must be forwarded.');
	}

	/**
	 * @covers \Tchooz\Services\Sofis\SofisAuthenticator::getAccessToken
	 * @return void
	 */
	public function testGetAccessTokenCachesTokenAndExchangesOnlyOnce(): void
	{
		$calls     = [];
		$transport = $this->transportReturningTokens($calls);
		$transport->expects($this->exactly(2))->method('post'); // 1 token + 1 exchange, then cached

		$authenticator = new SofisAuthenticator($this->config(), $transport);

		$first  = $authenticator->getAccessToken();
		$second = $authenticator->getAccessToken();

		$this->assertSame($first, $second, 'A cached, still-valid token must be reused.');
	}

	/**
	 * @covers \Tchooz\Services\Sofis\SofisAuthenticator::fromSynchronizer
	 * @return void
	 */
	public function testFromSynchronizerReadsConfigDecryptsSecretsAndAuthenticates(): void
	{
		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}

		$config = [
			'authentication' => [
				'idp_token_url'   => $this->idpUrl,
				'idp_client_id'   => 'idp-client',
				'idp_secret'      => \EmundusHelperFabrik::encryptDatas('idp-secret'),
				'token_endpoint'  => $this->exchangeUrl,
				'entra_client_id' => 'entra-client',
				'scope'           => 'default',
				'resource'        => $this->resource,
			],
		];
		$synchronizer = new SynchronizerEntity(1, 'sofis', 'Sofis', '', [], $config, true, true);

		$calls         = [];
		$authenticator = SofisAuthenticator::fromSynchronizer($synchronizer, $this->transportReturningTokens($calls));

		$this->assertSame('AZURE_TOKEN', $authenticator->getAccessToken(), 'fromSynchronizer must produce a working authenticator.');
		$this->assertSame('idp-client:idp-secret', base64_decode(substr($calls[0]['headers']['Authorization'], 6)), 'The stored IDP secret must be decrypted before use.');
	}

	// -------------------------------------------------------------------------
	// Failures
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Sofis\SofisAuthenticator::getAccessToken
	 * @return void
	 */
	public function testGetAccessTokenWhenIdamaTokenFailsThrows(): void
	{
		$calls     = [];
		$transport = $this->transportReturningTokens($calls, ['status' => 401, 'data' => null]);

		$this->expectException(\RuntimeException::class);

		(new SofisAuthenticator($this->config(), $transport))->getAccessToken();
	}

	/**
	 * @covers \Tchooz\Services\Sofis\SofisAuthenticator::getAccessToken
	 * @return void
	 */
	public function testGetAccessTokenWhenExchangeFailsThrows(): void
	{
		$calls     = [];
		$transport = $this->transportReturningTokens($calls, null, ['status' => 400, 'data' => null]);

		$this->expectException(\RuntimeException::class);

		(new SofisAuthenticator($this->config(), $transport))->getAccessToken();
	}

	/**
	 * @covers \Tchooz\Services\Sofis\SofisAuthenticator::getAccessToken
	 * @return void
	 */
	public function testGetAccessTokenWhenConfigIncompleteThrows(): void
	{
		$config                  = $this->config();
		$config['idp_client_id'] = '';

		$transport = $this->createMock(Api::class);
		$transport->expects($this->never())->method('post');

		$this->expectException(\RuntimeException::class);

		(new SofisAuthenticator($config, $transport))->getAccessToken();
	}
}
