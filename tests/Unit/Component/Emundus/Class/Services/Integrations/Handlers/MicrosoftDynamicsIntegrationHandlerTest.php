<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Integrations\Handlers;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Integrations\Configurations\MicrosoftDynamicsIntegrationConfiguration;
use Tchooz\Services\Integrations\Handlers\MicrosoftDynamicsIntegrationHandler;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\MicrosoftDynamicsIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class MicrosoftDynamicsIntegrationHandlerTest extends UnitTestCase
{
	private MicrosoftDynamicsIntegrationHandler $handler;
	private SynchronizerEntity $synchronizer;

	protected function setUp(): void
	{
		parent::setUp();

		$config = [
			'base_url'       => '',
			'authentication' => [
				'client_id'     => '',
				'client_secret' => '',
				'tenant_id'     => '',
			],
		];
		$this->synchronizer = new SynchronizerEntity(1, 'microsoft_dynamics', 'Microsoft Dynamics', 'MS Dynamics synchronizer', [], $config);
		$this->handler      = $this->getMockBuilder(MicrosoftDynamicsIntegrationHandler::class)
			->setConstructorArgs([$this->synchronizer, new MicrosoftDynamicsIntegrationConfiguration()])
			->onlyMethods(['encrypt'])
			->getMock();
		$this->handler->method('encrypt')->willReturnCallback(static fn(string $v): string => 'encrypted_' . $v);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::__construct
	 */
	public function testInvoke(): void
	{
		$this->assertInstanceOf(AbstractIntegrationHandler::class, $this->handler);
		$this->assertInstanceOf(MicrosoftDynamicsIntegrationHandler::class, $this->handler);
		$this->assertSame('microsoft_dynamics', $this->handler->getSynchronizer()->getType());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::getConfiguration
	 * @covers \Tchooz\Services\Integrations\Configurations\MicrosoftDynamicsIntegrationConfiguration::getParameters
	 */
	public function testGetConfiguration(): void
	{
		$configuration = $this->handler->getConfiguration();
		$this->assertInstanceOf(MicrosoftDynamicsIntegrationConfiguration::class, $configuration);

		$parameters = $configuration->getParameters();
		$this->assertIsArray($parameters);
		$this->assertCount(4, $parameters);

		$paramNames = array_map(fn($p) => $p->getName(), $parameters);
		$this->assertContains('domain', $paramNames);
		$this->assertContains('client_id', $paramNames);
		$this->assertContains('client_secret', $paramNames);
		$this->assertContains('tenant_id', $paramNames);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\MicrosoftDynamicsIntegrationHandler::getRequiredAddons
	 */
	public function testGetRequiredAddonsReturnsEmptyArray(): void
	{
		$requiredAddons = $this->handler->getRequiredAddons();
		$this->assertIsArray($requiredAddons);
		$this->assertCount(0, $requiredAddons);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\MicrosoftDynamicsIntegrationHandler::onActivate
	 */
	public function testOnActivateReturnsTrue(): void
	{
		$result = $this->handler->onActivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\MicrosoftDynamicsIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivateReturnsTrue(): void
	{
		$result = $this->handler->onDeactivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\MicrosoftDynamicsIntegrationHandler::onSetup
	 */
	public function testOnSetupSetsAuthConfig(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$domain   = 'https://org.crm.dynamics.com';
		$tenantId = 'test-tenant-id';
		$clientId = 'test-client-id';
		$setup    = (object) [
			'authentication' => (object) [
				'domain'        => $domain,
				'client_id'     => $clientId,
				'client_secret' => 'my_new_secret',
				'tenant_id'     => $tenantId,
			],
		];

		$result = $this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($result);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertSame($domain, $config['base_url']);
		$this->assertSame($clientId, $config['authentication']['client_id']);
		$this->assertSame($tenantId, $config['authentication']['tenant_id']);
		$this->assertSame('client_credentials', $config['authentication']['grant_type']);
		$this->assertSame($domain . '/.default', $config['authentication']['scope']);
		$this->assertSame($domain, $config['authentication']['domain']);
		$this->assertSame(
			'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token',
			$config['authentication']['route']
		);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\MicrosoftDynamicsIntegrationHandler::onSetup
	 */
	public function testOnSetupEncryptsClientSecret(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$plainSecret = 'my_plain_secret';
		$setup       = (object) [
			'authentication' => (object) [
				'domain'        => 'https://org.crm.dynamics.com',
				'client_id'     => 'test-client-id',
				'client_secret' => $plainSecret,
				'tenant_id'     => 'test-tenant-id',
			],
		];

		$this->handler->onSetup($setup, $repositoryMock);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertNotEmpty($config['authentication']['client_secret']);
		$this->assertNotSame($plainSecret, $config['authentication']['client_secret']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\MicrosoftDynamicsIntegrationHandler::onSetup
	 */
	public function testOnSetupPreservesClientSecretWhenMasked(): void
	{
		$oldEncrypted = 'old_encrypted_secret_value';
		$config       = [
			'base_url'       => '',
			'authentication' => [
				'client_id'     => '',
				'client_secret' => $oldEncrypted,
				'tenant_id'     => '',
			],
		];
		$synchronizer = new SynchronizerEntity(1, 'microsoft_dynamics', 'MS Dynamics', 'MS Dynamics sync', [], $config);
		$handler      = new MicrosoftDynamicsIntegrationHandler($synchronizer, new MicrosoftDynamicsIntegrationConfiguration());

		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication' => (object) [
				'domain'        => 'https://org.crm.dynamics.com',
				'client_id'     => 'test-client-id',
				'client_secret' => '***',
				'tenant_id'     => 'test-tenant-id',
			],
		];

		$handler->onSetup($setup, $repositoryMock);

		$updatedConfig = $handler->getSynchronizer()->getConfig();
		$this->assertSame($oldEncrypted, $updatedConfig['authentication']['client_secret']);
	}
}
