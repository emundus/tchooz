<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Actionlog.joomla
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Emundus\Messenger\Extension;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
final class Messenger extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher
	 * @param   array                $config      An optional associative array of configuration settings
	 *
	 * @since   3.9.0
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterMessageSent' => 'prepareNotifications'
		];
	}

	public function prepareNotifications(GenericEvent $event): void
	{
		$name = $event->getName();
		$data = $event->getArguments();

		// Store users to be notified in CRON
		$emConfig           = ComponentHelper::getParams('com_emundus');
		$send_notifications = $emConfig->get('messenger_notifications_on_send', 1);

		if ($send_notifications)
		{
			$db    = $this->getDatabase();
			$query = $db->getQuery(true);

			include_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
			include_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
			$m_files   = new \EmundusModelFiles();
			$fnumInfos = $m_files->getFnumInfos($data['fnum']);

			$users_to_send = [];

			// If message sent by an other user than the file owner we need to only notify the file owner
			if ($data['message']->user_id_from != $fnumInfos['applicant_id'])
			{
				$users_to_send[] = $fnumInfos['applicant_id'];

				// Remove notifications from all users that are not the file owner
				$query->clear()
					->delete($db->quoteName('#__emundus_chatroom_notifications'))
					->where($db->quoteName('chatroom_id') . ' = ' . $db->quote($data['message']->page))
					->where($db->quoteName('user_id') . ' <> ' . $db->quote($fnumInfos['applicant_id']));
				$db->setQuery($query);
				$db->execute();
			}
			else
			{
				// Remove notifications from file owner
				$query->clear()
					->delete($db->quoteName('#__emundus_chatroom_notifications'))
					->where($db->quoteName('chatroom_id') . ' = ' . $db->quote($data['message']->page))
					->where($db->quoteName('user_id') . ' = ' . $db->quote($fnumInfos['applicant_id']));
				$db->setQuery($query);
				$db->execute();

				$messenger_notify_users_programs = $emConfig->get('messenger_notify_users_programs', 0);
				$messenger_notify_groups         = $emConfig->get('messenger_notify_groups', '');
				$messenger_notify_users          = $emConfig->get('messenger_notify_users', '');
				
				if ($messenger_notify_users_programs == 1)
				{
					$query->clear()
						->select('DISTINCT ' . $db->quoteName('u.id'))
						->from($db->quoteName('#__emundus_groups', 'g'))
						->leftJoin($db->quoteName('#__emundus_setup_groups_repeat_course', 'grc') . ' ON ' . $db->quoteName('grc.parent_id') . ' = ' . $db->quoteName('g.group_id'))
						->innerJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('g.user_id'))
						->where($db->quoteName('grc.course') . ' LIKE ' . $db->quote($fnumInfos['training']));
					$db->setQuery($query);
					$users_associated_programs = $db->loadColumn();
					if (!empty($users_associated_programs))
					{
						$users_associated_programs = array_filter($users_associated_programs);
					}

					// Get users associated to the file by their group directly
					$query->clear()
						->select('DISTINCT ' . $db->quoteName('u.id'))
						->from($db->quoteName('#__emundus_groups', 'g'))
						->leftJoin($db->quoteName('#__emundus_group_assoc', 'ga') . ' ON ' . $db->quoteName('ga.group_id') . ' = ' . $db->quoteName('g.group_id'))
						->innerJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('g.user_id'))
						->where($db->quoteName('ga.fnum') . ' = ' . $db->quote($data['fnum']));
					$db->setQuery($query);
					$users_groups_associated = $db->loadColumn();
					if (!empty($users_groups_associated))
					{
						$users_groups_associated = array_filter($users_groups_associated);
					}

					// Get users associated to the file directly
					$query->clear()
						->select('DISTINCT ' . $db->quoteName('u.id'))
						->from($db->quoteName('#__emundus_campaign_candidature', 'cc'))
						->leftJoin($db->quoteName('#__emundus_users_assoc', 'eua') . ' ON ' . $db->quoteName('eua.fnum') . ' LIKE ' . $db->quoteName('cc.fnum'))
						->innerJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('eua.user_id'))
						->where($db->quoteName('cc.fnum') . ' LIKE ' . $db->quote($data['fnum']))
						->group($db->quoteName('cc.fnum'));
					$db->setQuery($query);
					$users_associated = $db->loadColumn();
					if (!empty($users_associated))
					{
						$users_associated = array_filter($users_associated);
					}

					$users_to_send = array_filter(array_unique(array_merge($users_to_send, $users_associated_programs, $users_groups_associated, $users_associated)));
				}

				if (!empty($messenger_notify_groups))
				{
					$query->clear()
						->select('DISTINCT user_id')
						->from('#__emundus_groups')
						->where('group_id IN (' . $messenger_notify_groups . ')');
					$db->setQuery($query);
					$users_notify_groups = $db->loadColumn();
					
					$users_to_send = array_filter(array_unique(array_merge($users_to_send, $users_notify_groups)));
				}

				if (!empty($users_to_send))
				{
					// Just to be sure, check for every user in the list if they can access the file
					foreach ($users_to_send as $key => $user_to_check)
					{
						$can_access = \EmundusHelperAccess::isUserAllowedToAccessFnum($user_to_check, $data['fnum']);

						if (!$can_access)
						{
							unset($users_to_send[$key]);
						}
					}
				}

				// Add additional users to notify, these do not need to be able to access the file
				if (!empty($messenger_notify_users))
				{
					$messenger_notify_users = explode(',', $messenger_notify_users);
					$users_to_send          = array_filter(array_unique(array_merge($users_to_send, $messenger_notify_users)));
				}
			}

			if (!empty($users_to_send))
			{
				$query->clear()
					->insert($db->quoteName('#__emundus_chatroom_notifications'))
					->columns([
						$db->quoteName('chatroom_id'),
						$db->quoteName('user_id'),
						$db->quoteName('message_id')
					]);
				foreach ($users_to_send as $user_id)
				{
					$query->values($db->quote($data['message']->page) . ', ' . $db->quote($user_id) . ', ' . $db->quote($data['message_id']));
				}
				$db->setQuery($query);
				$db->execute();
			}

		}
	}
}