<?php

/**
 * @package     Joomla.Plugins
 * @subpackage  Task.Checkgantrymode
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Checkgantrymode\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\Exception\ExecutionFailureException;
use Joomla\Event\SubscriberInterface;

/**
 * Task plugin with routines to check in a checked out item.
 *
 * @since  5.0.0
 */
class Checkgantrymode extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use TaskPluginTrait;

    /**
     * @var string[]
     * @since 5.0.0
     */
    protected const TASKS_MAP = [
        'plg_task_checkgantrymode_task_get' => [
            'langConstPrefix' => 'PLG_TASK_CHECKGANTRYMODE',
            'method'          => 'makeCheckin',
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
     * Standard method for the checkin routine.
     *
     * @param   ExecuteTaskEvent  $event  The onExecuteTask event
     *
     * @return  integer  The exit code
     *
     * @since   5.0.0
     */
    protected function makeCheckin(ExecuteTaskEvent $event): int
    {
        $db     = $this->getDatabase();
        $failed = false;

		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('name') . ' = ' . $db->quote('plg_system_gantry5'))
			->where($db->quoteName('element') . ' = ' . $db->quote('gantry5'));
		$db->setQuery($query);
		$params = json_decode($db->loadResult(), true);
		if($params['production'] == 0) {
			$params['production'] = 1;

			$query->clear()
				->update($db->quoteName('#__extensions'))
				->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
				->where($db->quoteName('name') . ' = ' . $db->quote('plg_system_gantry5'))
				->where($db->quoteName('element') . ' = ' . $db->quote('gantry5'));
			$db->setQuery($query);
			try {
				$db->execute();
			} catch (ExecutionFailureException $e) {
				$failed = true;
			}
		}

        return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
    }
}
