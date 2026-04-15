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
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Integrations\Configurations\HubspotIntegrationConfiguration;
use Tchooz\Services\Integrations\Handlers\HubspotIntegrationHandler;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\HubspotIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class HubspotIntegrationHandlerTest extends UnitTestCase
{
	private HubspotIntegrationHandler $handler;

	protected function setUp(): void
	{
		parent::setUp();

		$synchronizer  = new SynchronizerEntity(1, 'hubspot', 'HubSpot', 'HubSpot synchronizer');
		$this->handler = new HubspotIntegrationHandler(
			$synchronizer,
			new HubspotIntegrationConfiguration()
		);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::__construct
	 */
	public function testInvoke(): void
	{
		$this->assertInstanceOf(AbstractIntegrationHandler::class, $this->handler);
		$this->assertInstanceOf(HubspotIntegrationHandler::class, $this->handler);
		$this->assertSame('hubspot', $this->handler->getSynchronizer()->getType());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::getConfiguration
	 * @covers \Tchooz\Services\Integrations\Configurations\HubspotIntegrationConfiguration::getParameters
	 */
	public function testGetConfiguration(): void
	{
		$configuration = $this->handler->getConfiguration();
		$this->assertInstanceOf(HubspotIntegrationConfiguration::class, $configuration);

		$parameters = $configuration->getParameters();
		$this->assertIsArray($parameters);
		$this->assertCount(2, $parameters);

		$this->assertSame('token', $parameters[0]->getName());
		$this->assertSame('base_url', $parameters[1]->getName());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\HubspotIntegrationHandler::getRequiredAddons
	 */
	public function testGetRequiredAddonsReturnsEmptyArray(): void
	{
		$requiredAddons = $this->handler->getRequiredAddons();
		$this->assertIsArray($requiredAddons);
		$this->assertCount(0, $requiredAddons);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\HubspotIntegrationHandler::onActivate
	 */
	public function testOnActivateReturnsTrue(): void
	{
		$result = $this->handler->onActivate();
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\HubspotIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivateReturnsTrue(): void
	{
		$result = $this->handler->onDeactivate();
		$this->assertTrue($result);
	}
}
