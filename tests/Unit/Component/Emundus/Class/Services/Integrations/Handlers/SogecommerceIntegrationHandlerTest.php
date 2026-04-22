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
use Tchooz\Services\Integrations\Configurations\SogecommerceIntegrationConfiguration;
use Tchooz\Services\Integrations\Handlers\SogecommerceIntegrationHandler;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\SogecommerceIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class SogecommerceIntegrationHandlerTest extends UnitTestCase
{
	private SogecommerceIntegrationHandler $handler;
	private SynchronizerEntity $synchronizer;

	protected function setUp(): void
	{
		parent::setUp();

		$config = [
			'authentication' => [
				'client_id'     => '',
				'client_secret' => '',
			],
			'endpoint'       => 'https://sogecommerce.societegenerale.eu/vads-payment/',
			'mode'           => 'TEST',
			'return_url'     => '',
		];
		$this->synchronizer = new SynchronizerEntity(1, 'sogecommerce', 'Sogecommerce', 'Sogecommerce synchronizer', [], $config);
		$this->handler      = $this->getMockBuilder(SogecommerceIntegrationHandler::class)
			->setConstructorArgs([$this->synchronizer, new SogecommerceIntegrationConfiguration()])
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
		$this->assertInstanceOf(SogecommerceIntegrationHandler::class, $this->handler);
		$this->assertSame('sogecommerce', $this->handler->getSynchronizer()->getType());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::getConfiguration
	 * @covers \Tchooz\Services\Integrations\Configurations\SogecommerceIntegrationConfiguration::getParameters
	 */
	public function testGetConfiguration(): void
	{
		$configuration = $this->handler->getConfiguration();
		$this->assertInstanceOf(SogecommerceIntegrationConfiguration::class, $configuration);

		$parameters = $configuration->getParameters();
		$this->assertIsArray($parameters);
		$this->assertNotEmpty($parameters);

		$paramNames = array_map(fn($p) => $p->getName(), $parameters);
		$this->assertContains('client_id', $paramNames);
		$this->assertContains('client_secret', $paramNames);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\SogecommerceIntegrationHandler::getRequiredAddons
	 */
	public function testGetRequiredAddons(): void
	{
		$requiredAddons = $this->handler->getRequiredAddons();
		$this->assertIsArray($requiredAddons);
		$this->assertCount(1, $requiredAddons);
		$this->assertContainsOnlyInstancesOf(AddonEnum::class, $requiredAddons);
		$this->assertSame(AddonEnum::PAYMENT, $requiredAddons[0]);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\SogecommerceIntegrationHandler::onActivate
	 */
	public function testOnActivateReturnsTrue(): void
	{
		$result = $this->handler->onActivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\SogecommerceIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivateReturnsTrue(): void
	{
		$result = $this->handler->onDeactivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\SogecommerceIntegrationHandler::onSetup
	 */
	public function testOnSetupEncryptsClientSecret(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$plainSecret = 'my_sogecommerce_secret';
		$setup       = (object) [
			'authentication' => (object) [
				'client_id'     => 'test-client-id',
				'client_secret' => $plainSecret,
			],
		];

		$result = $this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($result);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertNotEmpty($config['authentication']['client_secret']);
		$this->assertNotSame($plainSecret, $config['authentication']['client_secret']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\SogecommerceIntegrationHandler::onSetup
	 */
	public function testOnSetupUpdatesConfigurationGroup(): void
	{
		$repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repositoryMock->method('flush')->willReturn(true);

		$setup = (object) [
			'authentication' => (object) [
				'client_id'     => 'new-client-id',
				'client_secret' => 'some_secret',
			],
			'configuration'  => (object) [
				'mode'       => 'PRODUCTION',
				'return_url' => 'https://example.com/return',
			],
		];

		$this->handler->onSetup($setup, $repositoryMock);

		$config = $this->handler->getSynchronizer()->getConfig();
		$this->assertSame('PRODUCTION', $config['mode']);
		$this->assertSame('https://example.com/return', $config['return_url']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\SogecommerceIntegrationHandler::onSetup
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
			],
		];

		$this->assertFalse($this->handler->getSynchronizer()->isEnabled());

		$this->handler->onSetup($setup, $repositoryMock);

		$this->assertTrue($this->handler->getSynchronizer()->isEnabled());
	}
}
