<?php
/**
 * @package     Tchooz\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Services\Integrations\EmundusIntegrationConfiguration;
use Tchooz\Synchronizers\SMS\OvhSMS;

class OvhIntegrationHandler extends AbstractIntegrationHandler
{
	const SCHEDULER_TASK_TYPE = 'plg_task_sms_task_get';

	private OvhSMS $ovhSynchronizer;

	public function __construct(
		SynchronizerEntity               $synchronizer,
		?EmundusIntegrationConfiguration $configuration
	)
	{
		parent::__construct($synchronizer, $configuration);
	}

	public function getRequiredAddons(): array
	{
		return [AddonEnum::SMS];
	}

	public function onActivate(): bool
	{
		return $this->toggleSchedulerTask(true);
	}

	public function onDeactivate(): bool
	{
		return $this->toggleSchedulerTask(false);
	}

	public function onAfterSetup(object $setup): bool
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		// 1. Test authentication
		$sms_services = $this->getOvhSynchronizer()->getSmsServices();

		// 2. Enable sendsms plugin
		if (!class_exists('EmundusHelperUpdate'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		}
		\EmundusHelperUpdate::enableEmundusPlugins('sendsms', 'task');

		// 3. Enable scheduler task
		$query = $db->getQuery(true);
		$this->toggleSchedulerTask(true);

		// 4. Publish application SMS menu link
		$query->clear()
			->update($db->quoteName('#__menu'))
			->set($db->quoteName('published') . ' = 1')
			->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=com_emundus&view=application&layout=sms&format=raw'));
		$db->setQuery($query);
		$db->execute();

		// 5. Publish send sms action menu link
		$query->clear()
			->update($db->quoteName('#__menu'))
			->set($db->quoteName('published') . ' = 1')
			->where($db->quoteName('link') . ' LIKE ' . $db->quote('%index.php?option=com_emundus&view=sms&layout=send&format=raw%'));
		$db->setQuery($query);
		$db->execute();

		return !empty($sms_services);
	}

	private function toggleSchedulerTask(bool $state)
	{
		$intState = intval($state);

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->clear()
			->update($db->quoteName('#__scheduler_tasks'))
			->set($db->quoteName('state') . ' = ' . $intState)
			->where($db->quoteName('type') . ' = ' . $db->quote(self::SCHEDULER_TASK_TYPE));
		$db->setQuery($query);

		return $db->execute();
	}

	public function getOvhSynchronizer(): OvhSMS
	{
		if (empty($this->ovhSynchronizer))
		{
			$this->ovhSynchronizer = new OvhSMS();
		}

		return $this->ovhSynchronizer;
	}

	public function setOvhSynchronizer(OvhSMS $ovhSynchronizer): OvhIntegrationHandler
	{
		$this->ovhSynchronizer = $ovhSynchronizer;

		return $this;
	}
}