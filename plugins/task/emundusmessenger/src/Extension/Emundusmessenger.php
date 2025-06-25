<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      Task.Checkgantrymode
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Task\Emundusmessenger\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
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
class Emundusmessenger extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use TaskPluginTrait;

	/**
	 * @var string[]
	 * @since 5.0.0
	 */
	protected const TASKS_MAP = [
		'plg_task_emundusmessenger_task_get' => [
			'langConstPrefix' => 'PLG_TASK_EMUNDUSMESSENGER',
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
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		$failed = false;

		// First check if we have to send notifications
		$emConfig                    = ComponentHelper::getParams('com_emundus');
		$send_notifications          = $emConfig->get('messenger_notifications_on_send', 1);
		$messenger_add_message_notif = $emConfig->get('messenger_add_message_notif', 1);
		$automated_task_user         = (int) $emConfig->get('automated_task_user', 1);

		if ($send_notifications == 1)
		{
			// Check if notifications have to been sent
			$query->select('ecn.user_id,u.name,u.email,group_concat(ec.fnum) as fnums,group_concat(ec.ccid) as ccids')
				->from($db->quoteName('#__emundus_chatroom_notifications', 'ecn'))
				->leftJoin($db->quoteName('#__emundus_chatroom', 'ec') . ' ON ' . $db->quoteName('ecn.chatroom_id') . ' = ' . $db->quoteName('ec.id'))
				->leftJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('ecn.user_id') . ' = ' . $db->quoteName('u.id'))
				->where($db->quoteName('ecn.last_notification') . ' IS NULL')
				->group($db->quoteName('ecn.user_id'))
				->order('ecn.user_id, ecn.chatroom_id');
			$db->setQuery($query);
			$notifications = $db->loadObjectList();

			if (!empty($notifications))
			{
				require_once JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'emails.php';
				$m_emails = new \EmundusModelEmails();

				$email_tmpl = 'messenger_reminder_group';

				foreach ($notifications as $notification)
				{
					$query->clear()
						->select('id')
						->from('#__emundus_campaign_candidature')
						->where('applicant_id = ' . $notification->user_id);
					$db->setQuery($query);
					$my_files = $db->loadColumn();
					if (array_intersect(explode(',', $notification->ccids), $my_files))
					{
						$email_tmpl = 'messenger_reminder';
					}

					$fnums = [];
					if (!empty($notification->fnums))
					{
						$fnums = explode(',', $notification->fnums);
					}

					$post = array(
						'FNUMS'     => $this->getFnumsList($notification->user_id, $fnums),
						'NAME'      => $notification->name,
						'USER_NAME' => $notification->name,
						'SITE_URL'  => Uri::base(),
					);

					$sent = $m_emails->sendEmailNoFnum($notification->email, $email_tmpl, $post, $notification->user_id, [], null, true, [], $automated_task_user);

					if ($sent)
					{
						// Mark notification as sent
						$query->clear()
							->update($db->quoteName('#__emundus_chatroom_notifications'))
							->set($db->quoteName('last_notification') . ' = ' . $db->quote(date('Y-m-d H:i:s')))
							->where($db->quoteName('user_id') . ' = ' . $notification->user_id);
						$db->setQuery($query);
						$db->execute();
					}
					// to avoid been considered as a spam process or DDoS
					sleep(0.1);
				}
			}
		}

		return $failed ? TaskStatus::INVALID_EXIT : TaskStatus::OK;
	}

	private function getFnumsList($user_id, $fnums)
	{
		$fnumList = '';

		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		try
		{
			if (!class_exists('EmundusModelProfile'))
			{
				require_once JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php';
			}
			$m_profile = new \EmundusModelProfile();

			$menutype = $m_profile->getProfileByApplicant($user_id)['menutype'];

			// Get the first link from the partner's menu that corresponds to the file list.
			$query->clear()
				->select($db->quoteName(array('id', 'path')))
				->from($db->quoteName('#__menu'))
				->where($db->quoteName('menutype') . ' LIKE ' . $db->quote($menutype))
				->andWhere($db->quoteName('published') . ' = 1')
				->andWhere($db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_emundus&view=files%') . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_emundus&view=evaluation%') . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_emundus&view=decision%'))
				->order($db->quoteName('lft'));
			$db->setQuery($query);
			$userLink = $db->loadObject();

			// Check published languages on the platform
			$query->clear()
				->select($db->quoteName('lang_code'))
				->from($db->quoteName('#__languages'))
				->where($db->quoteName('published') . ' = 1');
			$db->setQuery($query);
			$languages = $db->loadColumn();

			// Check if a translation exists for this link
			// In english
			if (in_array('en-GB', $languages))
			{
				$query->clear()
					->select($db->quoteName('value'))
					->from($db->quoteName('#__falang_content'))
					->where($db->quoteName('language_id') . ' = 1')
					->andWhere($db->quoteName('reference_table') . ' = ' . $db->quote('menu'))
					->andWhere($db->quoteName('reference_field') . ' = ' . $db->quote('path'))
					->andWhere($db->quoteName('reference_id') . ' = ' . $db->quote($userLink->id))
					->andWhere($db->quoteName('published') . ' = 1');
				$db->setQuery($query);
				$path_en = $db->loadResult();
			}
			else
			{
				$path_en = '';
			}

			// In french
			if (in_array('fr-FR', $languages))
			{
				$query->clear()
					->select($db->quoteName('value'))
					->from($db->quoteName('#__falang_content'))
					->where($db->quoteName('language_id') . ' = 2')
					->andWhere($db->quoteName('reference_table') . ' = ' . $db->quote('menu'))
					->andWhere($db->quoteName('reference_field') . ' = ' . $db->quote('path'))
					->andWhere($db->quoteName('reference_id') . ' = ' . $db->quote($userLink->id))
					->andWhere($db->quoteName('published') . ' = 1');
				$db->setQuery($query);
				$path_fr = $db->loadResult();
			}
			else
			{
				$path_fr = '';
			}

			// If there are both en and fr translations, use no link in the mail
			if ((!empty($path_fr) && !empty($path_en)) && $path_fr !== $path_en)
			{
				$userLink = '';
			}
			else
			{
				if (!empty($path_fr))
				{
					// If there is only a french one, use the french translation of the link
					$userLink->path = $path_fr;
				}
				else
				{
					if (!empty($path_en))
					{
						// If there is only an english one, use the english translation of the link
						$userLink->path = $path_en;
					}
				}
			}

			$fnumList = '<ul>';
			foreach ($fnums as $key => $applicant_fnum)
			{
				if (!empty($userLink))
				{
					$fnumList .= '<li><a href="' . Uri::root() . $userLink->path . '#' . $applicant_fnum . '|open">' . $applicant_fnum . '</a></li>';
				}
				else
				{
					$fnumList .= '<li>' . $applicant_fnum . '</li>';
				}
			}
			$fnumList .= '</ul>';
		}
		catch (ExecutionFailureException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}

		return $fnumList;
	}
}
