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
use Joomla\CMS\Log\Log;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;

class SylviaIntegrationHandler extends AbstractIntegrationHandler
{
	private const TASK_TYPE = 'plg_task_sylvia';

	public function onActivate(): bool
	{
		return $this->switchSchedulerTask(1);
	}

	public function onDeactivate(): bool
	{
		return $this->switchSchedulerTask(0);
	}

	/**
	 * Publish (1) or unpublish (0) the daily Sylvia scheduled task, so the plugin only
	 * runs while the integration is enabled.
	 *
	 * @param   int  $state
	 *
	 * @return bool
	 */
	private function switchSchedulerTask(int $state): bool
	{
		try
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->update($db->quoteName('#__scheduler_tasks'))
				->set($db->quoteName('state') . ' = ' . $db->quote($state))
				->where($db->quoteName('type') . ' = ' . $db->quote(self::TASK_TYPE));
			$db->setQuery($query);

			return (bool) $db->execute();
		}
		catch (\Throwable $e)
		{
			Log::add('Error switching Sylvia scheduled task state: ' . $e->getMessage(), Log::ERROR, 'com_emundus.sylvia');

			return false;
		}
	}
}
