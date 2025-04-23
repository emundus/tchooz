<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.BookingRecall
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Emundus\Plugin\Task\Emailrecall\Extension;

use DateTime;
use EmundusModelEmails;
use EmundusModelEvents;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status as TaskStatus;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Task plugin with routines that check whether a reminder mail should be sent for an event availability reservation
 *
 * @since  4.1.0
 */
final class Emailrecall extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 *
	 * @since 4.1.0
	 */
	protected const TASKS_MAP = [
		'email.recall' => [
			'langConstPrefix' => 'PLG_TASK_EMAIL_RECALL',
			'form'            => 'emailrecall_param',
			'method'          => 'sendEmailRecall',
		],
	];

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 4.1.0
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
	 * @var boolean
	 * @since 4.1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * The root directory path
	 *
	 * @var    string
	 * @since  4.2.0
	 */
	private $rootDirectory;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher     The dispatcher
	 * @param   array                $config         An optional associative array of configuration settings
	 * @param   string               $rootDirectory  The root directory to look for images
	 *
	 * @since   4.2.0
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config, string $rootDirectory)
	{
		parent::__construct($dispatcher, $config);

		Log::addLogger(['text_file' => 'com_emundus.task_email_recall.php'], Log::ALL, ['com_emundus.task_email_recall.php']);

		$this->rootDirectory = $rootDirectory;
	}

	/**
	 * @param   ExecuteTaskEvent  $event  The onExecuteTask event
	 *
	 * @return integer  The exit code
	 *
	 * @throws \RuntimeException
	 * @throws \LogicException
	 * @since 4.1.0
	 */
	protected function sendEmailRecall(ExecuteTaskEvent $event): int
	{
		$params = $event->getArgument('params');

		if (!empty($params->email_id) && !empty($params->files_to_check) && !empty($params->element_to_observe) && !empty($params->frequency))
		{
			try
			{
				if (!class_exists('EmundusHelperDate'))
				{
					require_once JPATH_SITE . '/components/com_emundus/helpers/date.php';
				}
				if (!class_exists('EmundusModelEmails'))
				{
					require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
				}
				$m_emails       = new EmundusModelEmails();
				$automated_user = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 1);

				$frequencies = explode(',', $params->frequency);

				//1. Get files to check
				$files = $this->getFiles($params->files_to_check);

				foreach ($files as $file)
				{
					//2. Check if we have a value for the element to observe
					$date_values = [];

					foreach ($params->element_to_observe as $element)
					{
						$date_values = array_merge($date_values, $this->getValuesByElement($element, $file->fnum));
					}

					// Remove null values from the array
					$date_values = array_filter($date_values, function ($value) {
						return $value !== null;
					});

					if (!empty($date_values))
					{
						//3. Check if one of the date need to be recalled
						$now = new DateTime();

						foreach ($date_values as $repeat_key => $date_value)
						{
							$date     = new DateTime($date_value);
							$interval = $now->diff($date);
							$days     = $interval->days;

							$post = [
								'DATE' => \EmundusHelperDate::displayDate($date_value, 'DATE_FORMAT_LC1')
							];
							$elements_tags = (array)$params->element_available_in_tags;
							foreach ($elements_tags as $element)
							{
								$value = $this->getValuesByElement($element->element, $file->fnum);
								if(is_array($value)) {
									$value = $value[$repeat_key];
								}

								if(!empty($value)) {
									$post[$element->tag] = $value;
								}
							}

							if ($days > 0 && in_array($days, $frequencies))
							{
								// 5. Get users to notify
								$users = $this->getUsersToNotify($params, $file->fnum);

								//6. Send email
								foreach ($users as $user)
								{
									$email = $this->getEmail($user);

									if (!empty($email))
									{
										$m_emails->sendEmailNoFnum($email, $params->email_id, $post, $automated_user, [], $file->fnum);
									}
								}
							}
						}
					}
				}
			}
			catch (\Exception $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus.task_email_recall.php');

				return TaskStatus::INVALID_EXIT;
			}
		}

		return TaskStatus::OK;
	}

	private function getFiles(array $status): array
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->select('id,fnum')
			->from($db->quoteName('#__emundus_campaign_candidature'))
			->where($db->quoteName('status') . ' IN (' . implode(',', $status) . ')');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	private function getValuesByElement(int $element, string $fnum): array
	{
		$values = [];

		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->select(['fe.name', 'fl.db_table_name', 'fe.group_id'])
			->from($db->quoteName('#__fabrik_elements', 'fe'))
			->leftJoin($db->quoteName('#__fabrik_formgroup', 'ff') . ' ON ' . $db->quoteName('ff.group_id') . ' = ' . $db->quoteName('fe.group_id'))
			->leftJoin($db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $db->quoteName('fl.form_id') . ' = ' . $db->quoteName('ff.form_id'))
			->where($db->quoteName('fe.id') . ' = ' . $db->quote($element));
		$db->setQuery($query);
		$element = $db->loadObject();

		if (!empty($element->db_table_name))
		{
			// Check if group is repeat
			$query->clear()
				->select('table_join')
				->from($db->quoteName('#__fabrik_joins'))
				->where($db->quoteName('group_id') . ' = ' . $db->quote($element->group_id))
				->where($db->quoteName('table_join_key') . ' = ' . $db->quote('parent_id'))
				->where($db->quoteName('join_from_table') . ' = ' . $db->quote($element->db_table_name));
			$db->setQuery($query);
			$join = $db->loadResult();

			if (empty($join))
			{
				$query->clear()
					->select($db->quoteName($element->name))
					->from($db->quoteName($element->db_table_name))
					->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
				$db->setQuery($query);
				$values = [$db->loadResult()];
			}
			else
			{
				$query->clear()
					->select($db->quoteName('r.' . $element->name))
					->from($db->quoteName($join, 'r'))
					->leftJoin($db->quoteName($element->db_table_name, 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('r.parent_id'))
					->where($db->quoteName('t.fnum') . ' = ' . $db->quote($fnum));
				$db->setQuery($query);
				$values = $db->loadColumn();
			}
		}

		return $values;
	}

	private function getUsersToNotify(object $params, string $fnum): array
	{
		$users = [];

		$db    = $this->getDatabase();
		$query = $db->createQuery();

		switch ($params->notify_associated)
		{
			case 1:
				// Get groups associated directly to fnum and get all users of those groups
				$query->clear()
					->select('DISTINCT group_id')
					->from($db->quoteName('#__emundus_group_assoc'))
					->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum))
					->where($db->quoteName('action_id') . ' = 1')
					->where($db->quoteName('r') . ' = 1');
				$db->setQuery($query);
				$groups = $db->loadColumn();

				$query->clear()
					->select('user_id')
					->from($db->quoteName('#__emundus_groups'))
					->where($db->quoteName('group_id') . ' IN (' . implode(',', $groups) . ')');
				$db->setQuery($query);
				$users = $db->loadColumn();
				break;
			case 2:
				// Get users directly associated to fnum
				$query->clear()
					->select('user_id')
					->from($db->quoteName('#__emundus_users_assoc'))
					->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum))
					->where($db->quoteName('action_id') . ' = 1')
					->where($db->quoteName('r') . ' = 1');
				$db->setQuery($query);
				$users = $db->loadColumn();
				break;
		}

		if (!empty($params->group_id))
		{
			$query->clear()
				->select('user_id')
				->from($db->quoteName('#__emundus_groups'))
				->where($db->quoteName('group_id') . ' = ' . $params->group_id);
			if (!empty($users))
			{
				$query->where($db->quoteName('user_id') . ' IN (' . implode(',', $users) . ')');
			}
			$db->setQuery($query);
			$users = $db->loadColumn();
		}

		if (!empty($params->profile_id))
		{
			$query->clear()
				->select('eu.user_id')
				->from($db->quoteName('#__emundus_users', 'eu'))
				->leftJoin($db->quoteName('#__emundus_users_profiles', 'eup') . ' ON ' . $db->quoteName('eup.user_id') . ' = ' . $db->quoteName('eu.user_id'))
				->where($db->quoteName('eu.profile') . ' = ' . $params->profile_id)
				->orWhere($db->quoteName('eup.profile_id') . ' = ' . $params->profile_id);
			if (!empty($users))
			{
				$query->extendWhere(
					'AND',
					$db->quoteName('eu.user_id') . ' IN (' . implode(',', $users) . ')'
				);
			}
			$db->setQuery($query);
			$users = $db->loadColumn();
		}

		return array_unique($users);
	}

	private function getEmail(int $user_id): ?string
	{
		$db    = $this->getDatabase();
		$query = $db->createQuery();

		$query->select($db->quoteName('email'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('id') . ' = ' . $user_id);
		$db->setQuery($query);

		return $db->loadResult();
	}
}
