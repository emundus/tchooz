<?php

namespace Tchooz\Services\Addons\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;

class BookingAddonHandler extends AbstractAddonHandler
{
	public function onActivate(): bool
	{
		return $this->applyState(true);
	}

	public function onDeactivate(): bool
	{
		return $this->applyState(false);
	}

	private function applyState(bool $state): bool
	{
		$tasks = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$intState = $state ? 1 : 0;

		try {
			// ACL
			$actionRepository = new ActionRepository();
			$referenceAction = $actionRepository->getByName('booking');
			$referenceAction->setStatus($state);
			$tasks[] = $actionRepository->flush($referenceAction);

			// toggle menu items
			$menuLinks = [
				'index.php?option=com_emundus&view=events',
				'index.php?option=com_emundus&view=events&layout=add',
				'index.php?option=com_emundus&view=events&layout=addlocation',
			];
			$query->clear()
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = ' . $db->quote($intState))
				->where($db->quoteName('link') . ' IN (' . implode(',', $db->quote($menuLinks)) . ')');
			$db->setQuery($query);
			$tasks[] = $db->execute();

			$emails = [
				'booking_confirmation'
			];
			$query->clear()
				->update($db->quoteName('#__emundus_setup_emails'))
				->set($db->quoteName('published') . ' = ' . $db->quote($intState))
				->where($db->quoteName('lbl') . ' IN (' . implode(',', $db->quote($emails)) . ')');
			$db->setQuery($query);
			$tasks[] = $db->execute();

			$query->clear()
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('enabled') . ' = ' . $db->quote($intState))
				->where($db->quoteName('name') . ' LIKE ' . $db->quote('plg_task_booking_recall'));
			$db->setQuery($query);
			$tasks[] = $db->execute();

			$this->ensureSchedulerTask($state);
		} catch (\Exception $e) {
			$tasks[] = false;
		}

		return !in_array(false, $tasks);
	}

	private function ensureSchedulerTask(bool $state): void
	{
		$intState = $state ? 1 : 0;

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('id')
			->from($db->quoteName('#__scheduler_tasks'))
			->where($db->quoteName('type') . ' = ' . $db->quote('booking.recall'));
		$db->setQuery($query);
		$task_id = $db->loadResult();

		if (empty($task_id) && $state)
		{
			if (!class_exists('EmundusHelperUpdate'))
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
			}

			$execution_rules = [
				'rule-type'        => 'interval-hours',
				'interval-hours' => 1,
				'exec-day'         => 1,
				'exec-time'        => '12:00'
			];
			$cron_rules = [
				'type' => 'interval',
				'exp'  => 'PT1H'
			];

			\EmundusHelperUpdate::createSchedulerTask('Booking recall', 'booking.recall', $execution_rules, $cron_rules);
		}
		else
		{
			$query->clear()
				->update($db->quoteName('#__scheduler_tasks'))
				->set($db->quoteName('state') . ' = ' . $intState)
				->where($db->quoteName('id') . ' = ' . $db->quote($task_id));
			$db->setQuery($query);
			$db->execute();
		}
	}
}