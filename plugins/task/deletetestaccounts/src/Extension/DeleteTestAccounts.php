<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Task.deletetestaccounts
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\DeleteTestAccounts\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * A task plugin. For Delete Test accounts after x days
 * {@see ExecuteTaskEvent}.
 *
 * @since 5.0.0
 */
final class DeleteTestAccounts extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use TaskPluginTrait;

    /**
     * @var string[]
     * @since 5.0.0
     */
    private const TASKS_MAP = [
        'delete.testaccounts' => [
            'langConstPrefix' => 'PLG_TASK_DELETETESTACCOUNTS_DELETE',
            'method'          => 'deleteTestAccounts',
            'form'            => 'deleteForm',
        ],
    ];

    /**
     * @var boolean
     * @since 5.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since 5.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    /**
     * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
     *
     * @return integer  The routine exit code.
     *
     * @since  5.0.0
     * @throws \Exception
     */
    private function deleteTestAccounts(ExecuteTaskEvent $event): int
    {
        $daysToReminderAfter = (int) $event->getArgument('params')->testAccountReminderPeriod ?? 0;
        $daysToDeleteAfter = (int) $event->getArgument('params')->testAccountDeletePeriod ?? 0;
        $this->logTask(sprintf('Delete test accounts after %d days', $daysToDeleteAfter));

        $now               = Factory::getDate()->toSql();
        $db                = $this->getDatabase();
        $query             = $db->getQuery(true);

	    try
	    {
		    // First get all testing_account that lastvisitdate is older than $daysToDeleteAfter
		    if ($daysToDeleteAfter > 0)
		    {
			    $days = -1 * $daysToDeleteAfter;

			    $query->select('id')
				    ->from($db->quoteName('#__users'))
				    ->where($db->quoteName('lastvisitdate') . ' < ' . $db->quote(Factory::getDate()->modify("-$days days")->toSql()))
				    ->where('JSON_VALID(params)')
				    ->where('JSON_EXTRACT(params, "$.testing_account")');
			    $db->setQuery($query);
			    $testAccountsToDelete = $db->loadColumn();

			    if (!empty($testAccountsToDelete))
			    {
					//TODO: Delete test accounts
			    }
		    }

		    // Then get all testing_account that lastvisitdate is older than $daysToReminderAfter
		    if ($daysToReminderAfter > 0)
		    {
			    $days = -1 * $daysToReminderAfter;

			    $query->select('id')
				    ->from($db->quoteName('#__users'))
				    ->where($db->quoteName('lastvisitdate') . ' < ' . $db->quote(Factory::getDate()->modify("-$days days")->toSql()))
				    ->where('JSON_VALID(params)')
				    ->where('JSON_EXTRACT(params, "$.testing_account")');
			    $db->setQuery($query);
			    $testAccountsToReminder = $db->loadColumn();

			    if (!empty($testAccountsToReminder))
			    {
					//TODO: Send reminder email
			    }
		    }
	    }
	    catch (\RuntimeException $e)
	    {
		    // Ignore it
		    return Status::KNOCKOUT;
	    }

        $this->logTask('Delete test accounts end');

        return Status::OK;
    }
}
