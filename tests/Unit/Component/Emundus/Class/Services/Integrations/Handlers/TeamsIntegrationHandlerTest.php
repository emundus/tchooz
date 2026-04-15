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
use Tchooz\Services\Integrations\Configurations\TeamsIntegrationConfiguration;
use Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class TeamsIntegrationHandlerTest extends UnitTestCase
{
	private TeamsIntegrationHandler $handler;
	private SynchronizerEntity $synchronizer;

	protected function setUp(): void
	{
		parent::setUp();

		$config = [
			'authentication' => [
				'route'         => '',
				'client_id'     => '',
				'client_secret' => '',
				'tenant_id'     => '',
				'email'         => '',
			],
		];
		$this->synchronizer = new SynchronizerEntity(1, 'teams', 'Teams', 'Microsoft Teams synchronizer', [], $config);
		$this->handler      = $this->getMockBuilder(TeamsIntegrationHandler::class)
			->setConstructorArgs([$this->synchronizer, new TeamsIntegrationConfiguration()])
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
		$this->assertInstanceOf(TeamsIntegrationHandler::class, $this->handler);
		$this->assertSame('teams', $this->handler->getSynchronizer()->getType());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::getConfiguration
	 * @covers \Tchooz\Services\Integrations\Configurations\TeamsIntegrationConfiguration::getParameters
	 */
	public function testGetConfiguration(): void
	{
		$configuration = $this->handler->getConfiguration();
		$this->assertInstanceOf(TeamsIntegrationConfiguration::class, $configuration);

		$parameters = $configuration->getParameters();
		$this->assertIsArray($parameters);
		$this->assertCount(4, $parameters);

		$paramNames = array_map(fn($p) => $p->getName(), $parameters);
		$this->assertContains('client_id', $paramNames);
		$this->assertContains('client_secret', $paramNames);
		$this->assertContains('tenant_id', $paramNames);
		$this->assertContains('email', $paramNames);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler::getRequiredAddons
	 */
	public function testGetRequiredAddonsReturnsEmptyArray(): void
	{
		$requiredAddons = $this->handler->getRequiredAddons();
		$this->assertIsArray($requiredAddons);
		$this->assertCount(0, $requiredAddons);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler::onActivate
	 */
	public function testOnActivateReturnsTrue(): void
	{
		$result = $this->handler->onActivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivateReturnsTrue(): void
	{
		$result = $this->handler->onDeactivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler::onSetup
	 */
	public function testOnSetupSetsAuthConfig(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$tenantId = 'test-tenant-id';
		$clientId = 'test-client-id';
		$email    = 'admin@example.com';
		$setup    = (object) [
			'authentication' => (object) [
				'client_id'     => $clientId,
				'client_secret' => 'my_new_secret',
				'tenant_id'     => $tenantId,
				'email'         => $email,
			],
		];

		$result = $this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($result);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertSame($clientId, $config['authentication']['client_id']);
		$this->assertSame($tenantId, $config['authentication']['tenant_id']);
		$this->assertSame($email, $config['authentication']['email']);
		$this->assertSame(
			'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token',
			$config['authentication']['route']
		);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler::onSetup
	 */
	public function testOnSetupEncryptsClientSecret(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$plainSecret = 'my_plain_teams_secret';
		$setup       = (object) [
			'authentication' => (object) [
				'client_id'     => 'test-client-id',
				'client_secret' => $plainSecret,
				'tenant_id'     => 'test-tenant-id',
				'email'         => 'admin@example.com',
			],
		];

		$this->handler->onSetup($setup, $repositoryMock);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertNotEmpty($config['authentication']['client_secret']);
		$this->assertNotSame($plainSecret, $config['authentication']['client_secret']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler::onSetup
	 */
	public function testOnSetupPreservesClientSecretWhenMasked(): void
	{
		$oldEncrypted = 'old_encrypted_teams_secret';
		$config       = [
			'authentication' => [
				'route'         => '',
				'client_id'     => '',
				'client_secret' => $oldEncrypted,
				'tenant_id'     => '',
				'email'         => '',
			],
		];
		$synchronizer = new SynchronizerEntity(1, 'teams', 'Teams', 'Teams sync', [], $config);
		$handler      = new TeamsIntegrationHandler($synchronizer, new TeamsIntegrationConfiguration());

		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication' => (object) [
				'client_id'     => 'test-client-id',
				'client_secret' => '***',
				'tenant_id'     => 'test-tenant-id',
				'email'         => 'admin@example.com',
			],
		];

		$handler->onSetup($setup, $repositoryMock);

		$updatedConfig = $handler->getSynchronizer()->getConfig();
		$this->assertSame($oldEncrypted, $updatedConfig['authentication']['client_secret']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\TeamsIntegrationHandler::onSetup
	 */
	public function testOnSetupEnablesSync(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication' => (object) [
				'client_id'     => 'test-client-id',
				'client_secret' => 'some_secret',
				'tenant_id'     => 'test-tenant-id',
				'email'         => 'admin@example.com',
			],
		];

		$this->assertFalse($this->handler->getSynchronizer()->isEnabled());

		$this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($this->handler->getSynchronizer()->isEnabled());
	}
}
