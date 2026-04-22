<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Integrations\Handlers;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Services\Integrations\Configurations\OvhIntegrationConfiguration;
use Tchooz\Services\Integrations\Handlers\OvhIntegrationHandler;
use Tchooz\Synchronizers\SMS\OvhSMS;

/**
 * @covers \Tchooz\Services\Integrations\Handlers\OvhIntegrationHandler
 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler
 */
class OvhIntegrationHandlerTest extends UnitTestCase
{
	private OvhIntegrationHandler $handler;

	protected function setUp(): void
	{
		parent::setUp();

		$configuration   = [
			'authentication' => [
				'client_id'     => '',
				'client_secret' => '',
				'consumer_key'  => '',
			]
		];
		$ovhSynchronizer = new SynchronizerEntity(1, 'ovh', 'OVH', 'Synchronizer ovh', [], $configuration);
		$this->handler   = new OvhIntegrationHandler(
			$ovhSynchronizer,
			new OvhIntegrationConfiguration()
		);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::__construct
	 */
	public function testInvoke(): void
	{
		$this->assertInstanceOf(OvhIntegrationHandler::class, $this->handler);
		$this->assertSame($this->handler->getSynchronizer()->getType(), 'ovh');
	}

	/**
	 * @covers \Tchooz\Services\Integrations\AbstractIntegrationHandler::getConfiguration
	 * @covers \Tchooz\Services\Integrations\Configurations\OvhIntegrationConfiguration::getParameters
	 */
	public function testGetConfiguration(): void
	{
		$configuration = $this->handler->getConfiguration();
		$this->assertInstanceOf(OvhIntegrationConfiguration::class, $configuration);

		$parameters = $configuration->getParameters();
		$this->assertIsArray($parameters);
		$this->assertCount(3, $parameters);

		$this->assertSame('authentication', $parameters[0]->getGroup()->getName());
		$this->assertSame('client_id', $parameters[0]->getName());
		$this->assertSame('client_secret', $parameters[1]->getName());
		$this->assertSame('consumer_key', $parameters[2]->getName());
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\OvhIntegrationHandler::getRequiredAddons
	 */
	public function testGetRequiredAddons(): void
	{
		$requiredAddons = $this->handler->getRequiredAddons();
		$this->assertIsArray($requiredAddons);
		$this->assertContainsOnlyInstancesOf(AddonEnum::class, $requiredAddons);
		$this->assertCount(1, $requiredAddons);
		$this->assertSame(AddonEnum::SMS, $requiredAddons[0]);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\OvhIntegrationHandler::onActivate
	 */
	public function testOnActivate(): void
	{
		$this->handler->onActivate();

		$query = $this->db->getQuery(true);
		$query->select('state')
			->from($this->db->quoteName('#__scheduler_tasks'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote(OvhIntegrationHandler::SCHEDULER_TASK_TYPE));
		$this->db->setQuery($query);
		$task = $this->db->loadResult();

		$this->assertEquals(1, $task, 'Scheduler task should be enabled after activation');
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\OvhIntegrationHandler::onDeactivate
	 */
	public function testOnDeactivate(): void
	{
		$this->handler->onDeactivate();

		$query = $this->db->getQuery(true);
		$query->select('state')
			->from($this->db->quoteName('#__scheduler_tasks'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote(OvhIntegrationHandler::SCHEDULER_TASK_TYPE));
		$this->db->setQuery($query);
		$task = $this->db->loadResult();

		$this->assertEquals(0, $task, 'Scheduler task should be enabled after activation');
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\OvhIntegrationHandler::onAfterSetup
	 */
	public function testOnAfterSetupFailed(): void
	{
		$setup = (object) [];

		$this->expectException(Exception::class);
		$this->expectExceptionMessage(Text::_('COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_NO_SERVICES_AVAILABLE'));

		$this->handler->onAfterSetup($setup);
	}

	/**
	 * @covers \Tchooz\Services\Integrations\Handlers\OvhIntegrationHandler::onAfterSetup
	 */
	public function testOnAfterSetupSuccess(): void
	{
		$ovhSynchronizerMock = $this->getMockBuilder(OvhSMS::class)
			->disableOriginalConstructor()
			->getMock();
		$ovhSynchronizerMock->method('getSmsServices')->willReturn(['service1', 'service2']);
		$this->handler->setOvhSynchronizer($ovhSynchronizerMock);

		$setup = (object) [];

		$result = $this->handler->onAfterSetup($setup);

		$this->assertSame(true, $result, 'onAfterSetup should return true when services are available');

		$query = $this->db->getQuery(true);
		$query->select('enabled')
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('sendsms'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('task'));
		$this->db->setQuery($query);
		$plugin = $this->db->loadResult();
		$this->assertEquals(1, $plugin, 'sendsms plugin should be enabled after setup');

		$query->clear()
			->select('published')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=application&layout=sms&format=raw'));
		$this->db->setQuery($query);
		$menuLink1 = $this->db->loadResult();
		$this->assertEquals(1, $menuLink1, 'Application SMS menu link should be published after setup');

		$query->clear()
			->select('published')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('%index.php?option=com_emundus&view=sms&layout=send&format=raw%'));
		$this->db->setQuery($query);
		$menuLink2 = $this->db->loadResult();
		$this->assertEquals(1, $menuLink2, 'Send SMS action menu link should be published after setup');
	}
}