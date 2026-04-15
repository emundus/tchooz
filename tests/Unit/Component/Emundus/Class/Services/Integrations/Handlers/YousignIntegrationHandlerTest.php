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
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Integrations\Configurations\YousignIntegrationConfiguration;
use Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class YousignIntegrationHandlerTest extends UnitTestCase
{
	private YousignIntegrationHandler $handler;
	private SynchronizerEntity $synchronizer;

	protected function setUp(): void
	{
		parent::setUp();

		$config = [
			'base_url'        => 'https://api-sandbox.yousign.app/v3',
			'authentication'  => [
				'token'          => '',
				'mode'           => 0,
				'create_webhook' => 0,
			],
			'configuration'   => [
				'signature_level'                => 'electronic_signature',
				'signature_authentication_mode'  => 'otp_email',
				'signature_display_mode'         => 'minimal',
				'request_name'                   => '',
				'expiration_date'                => null,
			],
		];
		$this->synchronizer = new SynchronizerEntity(1, 'yousign', 'Yousign', 'Yousign synchronizer', [], $config);
		$this->handler      = $this->getMockBuilder(YousignIntegrationHandler::class)
			->setConstructorArgs([$this->synchronizer, new YousignIntegrationConfiguration()])
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
		$this->assertInstanceOf(YousignIntegrationHandler::class, $this->handler);
		$this->assertSame('yousign', $this->handler->getSynchronizer()->getType());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::getConfiguration
	 * @covers \Tchooz\Services\Integrations\Configurations\YousignIntegrationConfiguration::getParameters
	 */
	public function testGetConfiguration(): void
	{
		$configuration = $this->handler->getConfiguration();
		$this->assertInstanceOf(YousignIntegrationConfiguration::class, $configuration);

		$parameters = $configuration->getParameters();
		$this->assertIsArray($parameters);
		$this->assertNotEmpty($parameters);

		$paramNames = array_map(fn($p) => $p->getName(), $parameters);
		$this->assertContains('token', $paramNames);
		$this->assertContains('mode', $paramNames);
		$this->assertContains('signature_level', $paramNames);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::getRequiredAddons
	 */
	public function testGetRequiredAddons(): void
	{
		$requiredAddons = $this->handler->getRequiredAddons();
		$this->assertIsArray($requiredAddons);
		$this->assertCount(1, $requiredAddons);
		$this->assertContainsOnlyInstancesOf(AddonEnum::class, $requiredAddons);
		$this->assertSame(AddonEnum::NUMERIC_SIGN, $requiredAddons[0]);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onActivate
	 */
	public function testOnActivateReturnsTrue(): void
	{
		$result = $this->handler->onActivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivateReturnsTrue(): void
	{
		$result = $this->handler->onDeactivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onSetup
	 */
	public function testOnSetupEncryptsToken(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$plainToken = 'my_yousign_api_token';
		$setup      = (object) [
			'authentication'  => (object) [
				'token'          => $plainToken,
				'mode'           => 0,
				'create_webhook' => 0,
			],
			'configuration'   => (object) [
				'signature_level'               => 'electronic_signature',
				'signature_authentication_mode' => 'otp_email',
				'signature_display_mode'        => 'minimal',
				'request_name'                  => '',
			],
		];

		$result = $this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($result);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertNotEmpty($config['authentication']['token']);
		$this->assertNotSame($plainToken, $config['authentication']['token']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onSetup
	 */
	public function testOnSetupPreservesTokenWhenMasked(): void
	{
		$existingToken = 'already_encrypted_token_value';
		$config        = [
			'base_url'        => 'https://api-sandbox.yousign.app/v3',
			'authentication'  => [
				'token' => $existingToken,
				'mode'  => 0,
			],
			'configuration'   => [],
		];
		$synchronizer  = new SynchronizerEntity(1, 'yousign', 'Yousign', 'Yousign sync', [], $config);
		$handler       = new YousignIntegrationHandler($synchronizer, new YousignIntegrationConfiguration());

		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication'  => (object) [
				'token'          => '***',
				'mode'           => 0,
				'create_webhook' => 0,
			],
			'configuration'   => (object) [
				'signature_level'               => 'electronic_signature',
				'signature_authentication_mode' => 'otp_email',
				'signature_display_mode'        => 'minimal',
				'request_name'                  => '',
			],
		];

		$handler->onSetup($setup, $repositoryMock);

		$updatedConfig = $handler->getSynchronizer()->getConfig();
		$this->assertSame($existingToken, $updatedConfig['authentication']['token']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onSetup
	 */
	public function testOnSetupSetsSandboxUrlForMode0(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication'  => (object) [
				'token'          => 'some_token',
				'mode'           => 0,
				'create_webhook' => 0,
			],
			'configuration'   => (object) [
				'signature_level'               => 'electronic_signature',
				'signature_authentication_mode' => 'otp_email',
				'signature_display_mode'        => 'minimal',
				'request_name'                  => '',
			],
		];

		$this->handler->onSetup($setup, $repositoryMock);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertSame('https://api-sandbox.yousign.app/v3', $config['base_url']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onSetup
	 */
	public function testOnSetupSetsProductionUrlForMode1(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication'  => (object) [
				'token'          => 'some_token',
				'mode'           => 1,
				'create_webhook' => 0,
			],
			'configuration'   => (object) [
				'signature_level'               => 'electronic_signature',
				'signature_authentication_mode' => 'otp_email',
				'signature_display_mode'        => 'minimal',
				'request_name'                  => '',
			],
		];

		$this->handler->onSetup($setup, $repositoryMock);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertSame('https://api.yousign.app/v3', $config['base_url']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onSetup
	 */
	public function testOnSetupSetsConfigurationFields(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication'  => (object) [
				'token'          => 'some_token',
				'mode'           => 1,
				'create_webhook' => 1,
			],
			'configuration'   => (object) [
				'signature_level'               => 'advanced_electronic_signature',
				'signature_authentication_mode' => 'otp_sms',
				'signature_display_mode'        => 'detailed',
				'request_name'                  => 'My Request',
				'expiration_date'               => '2026-12-31',
			],
		];

		$this->handler->onSetup($setup, $repositoryMock);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertSame('advanced_electronic_signature', $config['configuration']['signature_level']);
		$this->assertSame('otp_sms', $config['configuration']['signature_authentication_mode']);
		$this->assertSame('detailed', $config['configuration']['signature_display_mode']);
		$this->assertSame('My Request', $config['configuration']['request_name']);
		$this->assertSame('2026-12-31', $config['configuration']['expiration_date']);
		$this->assertSame(1, $config['authentication']['mode']);
		$this->assertSame(1, $config['authentication']['create_webhook']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onSetup
	 */
	public function testOnSetupSetsNullExpirationDateWhenInvalid(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication'  => (object) [
				'token'          => 'some_token',
				'mode'           => 0,
				'create_webhook' => 0,
			],
			'configuration'   => (object) [
				'signature_level'               => 'electronic_signature',
				'signature_authentication_mode' => 'otp_email',
				'signature_display_mode'        => 'minimal',
				'request_name'                  => '',
				'expiration_date'               => 'Invalid Date',
			],
		];

		$this->handler->onSetup($setup, $repositoryMock);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertNull($config['configuration']['expiration_date']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\YousignIntegrationHandler::onSetup
	 */
	public function testOnSetupEnablesSync(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication'  => (object) [
				'token'          => 'some_token',
				'mode'           => 0,
				'create_webhook' => 0,
			],
			'configuration'   => (object) [
				'signature_level'               => 'electronic_signature',
				'signature_authentication_mode' => 'otp_email',
				'signature_display_mode'        => 'minimal',
				'request_name'                  => '',
			],
		];

		$this->assertFalse($this->handler->getSynchronizer()->isEnabled());

		$this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($this->handler->getSynchronizer()->isEnabled());
	}
}
