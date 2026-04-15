<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Integrations
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Integrations;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;
use Tchooz\Services\Integrations\IntegrationHandlerResolver;
use Tchooz\Services\Integrations\IntegrationService;

/**
 * @covers \Tchooz\Services\Integrations\IntegrationService
 */
class IntegrationServiceTest extends TestCase
{
	/** @var SynchronizerRepository&\PHPUnit\Framework\MockObject\MockObject */
	private $repositoryMock;

	/** @var IntegrationHandlerResolver&\PHPUnit\Framework\MockObject\MockObject */
	private $resolverMock;

	/** @var AbstractIntegrationHandler&\PHPUnit\Framework\MockObject\MockObject */
	private $handlerMock;

	private IntegrationService $service;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		// Provide a no-op EmundusHelperCache so executeHandler does not fail outside Docker
		if (!class_exists('EmundusHelperCache'))
		{
			eval('class EmundusHelperCache { public function clean(bool $debug = false, array $groups = []): void {} }');
		}
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->repositoryMock = $this->getMockBuilder(SynchronizerRepository::class)
			->disableOriginalConstructor()
			->getMock();

		$this->resolverMock = $this->getMockBuilder(IntegrationHandlerResolver::class)
			->disableOriginalConstructor()
			->getMock();

		$this->handlerMock = $this->getMockBuilder(AbstractIntegrationHandler::class)
			->disableOriginalConstructor()
			->getMock();

		$this->service = new IntegrationService($this->repositoryMock, $this->resolverMock);
	}

	// -------------------------------------------------------------------------
	// activate()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateReturnsFalseWhenSynchronizerNotFound(): void
	{
		$this->repositoryMock->method('getById')->with(1)->willReturn(null);

		$this->assertFalse($this->service->activate(1));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateReturnsTrueImmediatelyWhenAlreadyEnabled(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], [], true, true);
		$this->repositoryMock->method('getById')->with(1)->willReturn($synchronizer);
		$this->resolverMock->expects($this->never())->method('resolve');

		$this->assertTrue($this->service->activate(1));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateThrowsRuntimeExceptionWhenAddonDependenciesAreNotSatisfied(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'stripe', 'Stripe', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);

		$this->handlerMock->method('checkAddonDependencies')->willReturn([
			'satisfied' => false,
			'missing'   => [AddonEnum::PAYMENT],
		]);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->expectException(\RuntimeException::class);
		$this->service->activate(1);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateThrowsRuntimeExceptionWhenMultipleAddonDependenciesMissing(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'stripe', 'Stripe', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);

		$this->handlerMock->method('checkAddonDependencies')->willReturn([
			'satisfied' => false,
			'missing'   => [AddonEnum::PAYMENT, AddonEnum::SMS],
		]);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->expectException(\RuntimeException::class);
		$this->service->activate(1);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateReturnsFalseWhenHandlerThrowsGenericException(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->resolverMock->method('resolve')->willThrowException(new \Exception('Generic error'));

		$this->assertFalse($this->service->activate(1));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateFlushesAndReturnsTrueOnSuccessWithoutConfiguration(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->repositoryMock->method('flush')->with($synchronizer)->willReturn(true);

		$this->handlerMock->method('checkAddonDependencies')->willReturn(['satisfied' => true, 'missing' => []]);
		$this->handlerMock->method('onActivate')->willReturn(true);
		$this->handlerMock->method('getConfiguration')->willReturn(null);

		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->assertTrue($this->service->activate(1));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateSetsEnabledTrueBeforeFlush(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->repositoryMock->method('flush')->willReturn(true);

		$this->handlerMock->method('checkAddonDependencies')->willReturn(['satisfied' => true, 'missing' => []]);
		$this->handlerMock->method('onActivate')->willReturn(true);
		$this->handlerMock->method('getConfiguration')->willReturn(null);

		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->service->activate(1);

		$this->assertTrue($synchronizer->isEnabled());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateInitialisesConfigurationWhenParametersArePresent(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], []);
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->repositoryMock->method('flush')->willReturn(true);

		$field = new StringField('api_key', 'API Key');

		$configMock = $this->getMockBuilder(EmundusIntegrationConfiguration::class)->getMock();
		$configMock->method('getParameters')->willReturn([$field]);
		$configMock->method('getDefaultParameters')->willReturn([]);

		$this->handlerMock->method('checkAddonDependencies')->willReturn(['satisfied' => true, 'missing' => []]);
		$this->handlerMock->method('onActivate')->willReturn(true);
		$this->handlerMock->method('getConfiguration')->willReturn($configMock);

		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->assertTrue($this->service->activate(1));

		// The synchronizer config should now contain the field key initialised to empty string
		$config = $synchronizer->getConfig();
		$this->assertArrayHasKey('api_key', $config);
		$this->assertSame('', $config['api_key']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateInitialisesConfigurationWithGroupedParameters(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], []);
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->repositoryMock->method('flush')->willReturn(true);

		$group = new FieldGroup('authentication', 'Authentication');
		$field = new StringField('api_key', 'API Key', false, $group);

		$configMock = $this->getMockBuilder(EmundusIntegrationConfiguration::class)->getMock();
		$configMock->method('getParameters')->willReturn([$field]);
		$configMock->method('getDefaultParameters')->willReturn([]);

		$this->handlerMock->method('checkAddonDependencies')->willReturn(['satisfied' => true, 'missing' => []]);
		$this->handlerMock->method('onActivate')->willReturn(true);
		$this->handlerMock->method('getConfiguration')->willReturn($configMock);

		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->service->activate(1);

		$config = $synchronizer->getConfig();
		$this->assertArrayHasKey('authentication', $config);
		$this->assertArrayHasKey('api_key', $config['authentication']);
		$this->assertSame('', $config['authentication']['api_key']);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::activate
	 */
	public function testActivateDoesNotOverwriteExistingConfigValues(): void
	{
		$existingConfig = ['api_key' => 'existing_value'];
		$synchronizer   = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], $existingConfig);
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->repositoryMock->method('flush')->willReturn(true);

		$field = new StringField('api_key', 'API Key');

		$configMock = $this->getMockBuilder(EmundusIntegrationConfiguration::class)->getMock();
		$configMock->method('getParameters')->willReturn([$field]);
		$configMock->method('getDefaultParameters')->willReturn([]);

		$this->handlerMock->method('checkAddonDependencies')->willReturn(['satisfied' => true, 'missing' => []]);
		$this->handlerMock->method('onActivate')->willReturn(true);
		$this->handlerMock->method('getConfiguration')->willReturn($configMock);

		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->service->activate(1);

		// Existing value must not be overwritten
		$config = $synchronizer->getConfig();
		$this->assertSame('existing_value', $config['api_key']);
	}

	// -------------------------------------------------------------------------
	// deactivate()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::deactivate
	 */
	public function testDeactivateReturnsFalseWhenSynchronizerNotFound(): void
	{
		$this->repositoryMock->method('getById')->with(99)->willReturn(null);

		$this->assertFalse($this->service->deactivate(99));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::deactivate
	 */
	public function testDeactivateReturnsTrueImmediatelyWhenAlreadyDisabled(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], [], true, false);
		$this->repositoryMock->method('getById')->with(1)->willReturn($synchronizer);
		$this->resolverMock->expects($this->never())->method('resolve');

		$this->assertTrue($this->service->deactivate(1));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::deactivate
	 */
	public function testDeactivateReturnsFalseWhenHandlerThrowsGenericException(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], [], true, true);
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->resolverMock->method('resolve')->willThrowException(new \Exception('Generic error'));

		$this->assertFalse($this->service->deactivate(1));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::deactivate
	 */
	public function testDeactivateFlushesAndReturnsTrueOnSuccess(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], [], true, true);
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->repositoryMock->method('flush')->with($synchronizer)->willReturn(true);

		$this->handlerMock->method('onDeactivate')->willReturn(true);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->assertTrue($this->service->deactivate(1));
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::deactivate
	 */
	public function testDeactivateSetsEnabledFalseBeforeFlush(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], [], true, true);
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->repositoryMock->method('flush')->willReturn(true);

		$this->handlerMock->method('onDeactivate')->willReturn(true);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->service->deactivate(1);

		$this->assertFalse($synchronizer->isEnabled());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::deactivate
	 */
	public function testDeactivateReturnsFlushResult(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc', [], [], true, true);
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->repositoryMock->method('flush')->willReturn(false);

		$this->handlerMock->method('onDeactivate')->willReturn(true);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->assertFalse($this->service->deactivate(1));
	}

	// -------------------------------------------------------------------------
	// getParameters()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::getParameters
	 */
	public function testGetParametersReturnsEmptyArrayWhenHandlerHasNoConfiguration(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc');
		$this->handlerMock->method('getConfiguration')->willReturn(null);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$result = $this->service->getParameters($synchronizer);

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::getParameters
	 */
	public function testGetParametersReturnsConfigurationParameters(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc');

		$field1 = new StringField('token', 'Token');
		$field2 = new StringField('base_url', 'Base URL');

		$configMock = $this->getMockBuilder(EmundusIntegrationConfiguration::class)->getMock();
		$configMock->method('getParameters')->willReturn([$field1, $field2]);

		$this->handlerMock->method('getConfiguration')->willReturn($configMock);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$result = $this->service->getParameters($synchronizer);

		$this->assertCount(2, $result);
		$this->assertSame($field1, $result[0]);
		$this->assertSame($field2, $result[1]);
	}

	// -------------------------------------------------------------------------
	// setup()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::setup
	 */
	public function testSetupThrowsRuntimeExceptionWhenSynchronizerNotFound(): void
	{
		$this->repositoryMock->method('getById')->willReturn(null);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Synchronizer not found');
		$this->service->setup(1, (object) []);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::setup
	 */
	public function testSetupThrowsRuntimeExceptionWhenOnSetupFails(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);

		$this->handlerMock->method('onSetup')->willReturn(false);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->expectException(\RuntimeException::class);
		$this->service->setup(1, (object) []);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::setup
	 */
	public function testSetupThrowsRuntimeExceptionWhenOnAfterSetupFails(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);

		$this->handlerMock->method('onSetup')->willReturn(true);
		$this->handlerMock->method('onAfterSetup')->willReturn(false);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->expectException(\RuntimeException::class);
		$this->service->setup(1, (object) []);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::setup
	 */
	public function testSetupReturnsTrueWhenBothHooksSucceed(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);

		$setup = (object) ['authentication' => (object) ['token' => 'my_token']];

		$this->handlerMock->method('onSetup')->with($setup, $this->repositoryMock)->willReturn(true);
		$this->handlerMock->method('onAfterSetup')->with($setup)->willReturn(true);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->assertTrue($this->service->setup(1, $setup));
	}

	// -------------------------------------------------------------------------
	// getMissingAddons()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::getMissingAddons
	 */
	public function testGetMissingAddonsReturnsEmptyArrayWhenSynchronizerNotFound(): void
	{
		$this->repositoryMock->method('getById')->willReturn(null);

		$result = $this->service->getMissingAddons(1);

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::getMissingAddons
	 */
	public function testGetMissingAddonsReturnsEmptyArrayWhenHandlerThrowsException(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'unknown_type', 'Unknown', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);
		$this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('Handler not found'));

		$result = $this->service->getMissingAddons(1);

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::getMissingAddons
	 */
	public function testGetMissingAddonsReturnsEmptyArrayWhenAllDependenciesSatisfied(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'stripe', 'Stripe', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);

		$this->handlerMock->method('checkAddonDependencies')->willReturn([
			'satisfied' => true,
			'missing'   => [],
		]);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$result = $this->service->getMissingAddons(1);

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::getMissingAddons
	 */
	public function testGetMissingAddonsReturnsMissingAddonsList(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'stripe', 'Stripe', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);

		$this->handlerMock->method('checkAddonDependencies')->willReturn([
			'satisfied' => false,
			'missing'   => [AddonEnum::PAYMENT],
		]);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$result = $this->service->getMissingAddons(1);

		$this->assertCount(1, $result);
		$this->assertContainsOnlyInstancesOf(AddonEnum::class, $result);
		$this->assertSame(AddonEnum::PAYMENT, $result[0]);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\IntegrationService::getMissingAddons
	 */
	public function testGetMissingAddonsReturnsAllMissingAddons(): void
	{
		$synchronizer = new SynchronizerEntity(1, 'stripe', 'Stripe', 'desc');
		$this->repositoryMock->method('getById')->willReturn($synchronizer);

		$this->handlerMock->method('checkAddonDependencies')->willReturn([
			'satisfied' => false,
			'missing'   => [AddonEnum::PAYMENT, AddonEnum::SMS],
		]);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$result = $this->service->getMissingAddons(1);

		$this->assertCount(2, $result);
		$this->assertSame(AddonEnum::PAYMENT, $result[0]);
		$this->assertSame(AddonEnum::SMS, $result[1]);
	}
}
