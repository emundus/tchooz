<?php
/**
 * Messages model used for the new message dialog.
 *
 * @package    Joomla
 * @subpackage eMundus
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Plugin\PluginHelper;
use Component\Emundus\Helpers\HtmlSanitizerSingleton;

JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_emundus/models');

class EmundusModelMessenger extends ListModel
{
	private $db;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->db = Factory::getContainer()->get('DatabaseDriver');
		if (!class_exists('HtmlSanitizerSingleton')) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/html.php');
		}
	}

	public function checkMessengerState(): bool
	{
		$enabled = false;

		try
		{
			$query = $this->db->createQuery();

			$query->select('enabled')
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('mod_emundus_messenger_notifications'));
			$this->db->setQuery($query);
			$enabled = $this->db->loadResult() == 1;
		}
		catch (Exception $e)
		{
			Log::add('Error when try to check if messenger is enabled : ' . $e->getMessage(), 'error', 'emundus');
		}

		return $enabled;
	}

	public function createChatroom($fnum = null, $id = null)
	{
		$query    = $this->db->createQuery();
		$chatroom = null;

		try
		{
			require_once JPATH_BASE . '/components/com_emundus/models/files.php';
			$m_files = new EmundusModelFiles;

			$fnumInfos = $m_files->getFnumInfos($fnum);

			if (!empty($fnumInfos))
			{
				$insert = [
					'fnum'   => $fnum,
					'status' => 1,
					'ccid'   => $fnumInfos['ccid']
				];
				$insert = (object) $insert;

				if ($this->db->insertObject('#__emundus_chatroom', $insert))
				{
					$chatroom_id = $this->db->insertid();
					$chatroom    = $this->getChatroom($chatroom_id);
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error creating chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');
		}

		return $chatroom;
	}

	public function closeChatroom($fnum)
	{
		$closed = false;
		$query  = $this->db->createQuery();

		try
		{
			$query->clear()
				->update($this->db->quoteName('#__emundus_chatroom'))
				->set($this->db->quoteName('status') . ' = 0')
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$closed = $this->db->execute();

			// Remove notifications
			$query->clear()
				->delete($this->db->quoteName('#__emundus_chatroom_notifications','ecn'))
				->leftJoin($this->db->quoteName('#__emundus_chatroom', 'ec') . ' ON ' . $this->db->quoteName('ec.id') . ' = ' . $this->db->quoteName('ecn.chatroom_id'))
				->where($this->db->quoteName('ec.fnum') . ' LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Error closing chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');
		}

		return $closed;
	}

	public function openChatroom($fnum)
	{
		$opened = false;
		$query  = $this->db->createQuery();

		try
		{
			$query->clear()
				->update($this->db->quoteName('#__emundus_chatroom'))
				->set($this->db->quoteName('status') . ' = 1')
				->where($this->db->quoteName('fnum') . ' LIKE ' . $this->db->quote($fnum));
			$this->db->setQuery($query);
			$opened = $this->db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Error opening chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');
		}

		return $opened;
	}

	public function getChatroom($id)
	{
		$query = $this->db->createQuery();

		$query->select('ec.*,esc.label as campaign,esc.year, esp.id as program_id, esp.label as program')
			->from($this->db->quoteName('#__emundus_chatroom', 'ec'))
			->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.id') . ' = ' . $this->db->quoteName('ec.ccid'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
			->where($this->db->quoteName('ec.id') . ' = ' . $id);
		$this->db->setQuery($query);

		try
		{
			return $this->db->loadObject();
		}
		catch (Exception $e)
		{
			Log::add('Error getting chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');

			return false;
		}
	}

	public function getChatroomsByUser($user_id = 0)
	{
		$chatrooms = [];
		if (empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		try
		{
			$query = $this->db->createQuery();

			$query->clear()
				->select('ec.*, esc.label as campaign, esc.year, esp.id as program_id, esp.label as program')
				->from($this->db->quoteName('#__emundus_chatroom', 'ec'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ec.ccid') . ' = ' . $this->db->quoteName('ecc.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
				->where($this->db->quoteName('ecc.applicant_id') . ' = ' . $user_id);
			$this->db->setQuery($query);
			$chatrooms = $this->db->loadObjectList();

			foreach ($chatrooms as $chatroom)
			{
				$chatroom->messages = $this->getMessagesByChatroom($chatroom->id);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error getting chatrooms by user : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');
		}

		return $chatrooms;
	}

	public function getChatroomsByFnum($fnum)
	{
		$chatrooms = null;
		$query     = $this->db->createQuery();

		$query->select('ec.*,esc.label as campaign,esc.year')
			->from($this->db->quoteName('#__emundus_chatroom', 'ec'))
			->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.id') . ' = ' . $this->db->quoteName('ec.ccid'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
			->where($this->db->quoteName('ec.fnum') . ' LIKE ' . $this->db->quote($fnum));
		$this->db->setQuery($query);

		try
		{
			$chatrooms = $this->db->loadObjectList();

			foreach ($chatrooms as $chatroom)
			{
				$chatroom->messages = $this->getMessagesByChatroom($chatroom->id);
			}
		}
		catch (Exception $e)
		{
			Log::add('Error getting chatroom by fnum : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');
		}

		return $chatrooms;
	}

	public function getMessagesByChatroom($chatroom_id)
	{
		$messages = [];

		try
		{
			$query = $this->db->createQuery();

			$query->select('m.message')
				->from($this->db->quoteName('#__messages', 'm'))
				->where($this->db->quoteName('m.page') . ' = ' . $chatroom_id);
			$this->db->setQuery($query);
			$messages = $this->db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error getting messages by chatroom : ' . $e->getMessage(), Log::ERROR, 'com_emundus.chatroom');
		}

		return $messages;
	}

	function getFilesByUser($user_id = null, $only_published = true)
	{
		$files = [];
		$query = $this->db->createQuery();

		if (empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		if (!empty($user_id))
		{
			$query->select('sc.*,cc.fnum,cc.published as file_publish, sp.label as program, sp.id as program_id')
				->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'sc') . ' ON ' . $this->db->quoteName('sc.id') . ' = ' . $this->db->quoteName('cc.campaign_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'sp') . ' ON ' . $this->db->quoteName('sp.code') . ' = ' . $this->db->quoteName('sc.training'))
				->where($this->db->quoteName('cc.applicant_id') . ' = ' . $user_id);
			//->group('sc.id');

			if ($only_published)
			{
				$query->where($this->db->quoteName('sc.published') . ' = 1');
			}

			try
			{
				$this->db->setQuery($query);
				$files = $this->db->loadObjectList();
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus_messages/models/messages | Error when try to get files associated to user : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $files;
	}

	function getMessagesByFnum($fnum, $offset = 0, $user_id = null): object
	{
		$messages = [];

		if(!class_exists('EmundusHelperDate'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/date.php';
		}

		if (!empty($fnum))
		{
			if (empty($user_id))
			{
				$user_id = Factory::getApplication()->getIdentity()->id;
			}

			$eMConfig              = ComponentHelper::getParams('com_emundus');
			$anonymous_coordinator = $eMConfig->get('messenger_anonymous_coordinator', '0');

			try
			{
				$query = $this->db->createQuery();
				$query->select('DISTINCT CAST(m.date_time AS DATE) as dates, GROUP_CONCAT(DISTINCT m.message_id ORDER BY m.date_time) as messages')
					->from($this->db->quoteName('#__messages', 'm'))
					->leftJoin($this->db->quoteName('#__emundus_chatroom', 'c') . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('m.page'))
					->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('m.user_id_from'))
					->where($this->db->quoteName('c.fnum') . ' LIKE ' . $this->db->quote($fnum))
					->group('CAST(m.date_time AS DATE)')
					->order('m.date_time');
				$this->db->setQuery($query);
				$dates = $this->db->loadObjectList();

				foreach ($dates as $date)
				{
					$date->messages = explode(',', $date->messages);
				}

				$query->clear()
					->select('m.*, CASE WHEN eu.is_anonym != 1 THEN u.name ELSE ' . $this->db->quote(Text::_('COM_EMUNDUS_ANONYM_ACCOUNT')) . ' END as name')
					->from($this->db->quoteName('#__messages', 'm'))
					->leftJoin($this->db->quoteName('#__emundus_chatroom', 'c') . ' ON ' . $this->db->quoteName('c.id') . ' = ' . $this->db->quoteName('m.page'))
					->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('m.user_id_from'))
					->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('u.id'))
					->where($this->db->quoteName('c.fnum') . ' LIKE ' . $this->db->quote($fnum))
					->order('m.date_time DESC');
				$this->db->setQuery($query, $offset);
				$messages = $this->db->loadObjectList();

				foreach ($messages as $message)
				{
					$hour               = EmundusHelperDate::displayDate($message->date_time, 'H:i', 0);
					$message->date_hour = $hour;
					$message->me        = $message->user_id_from == $user_id;
				}

				$datas            = new stdClass;
				$datas->dates     = $dates;
				$datas->messages  = array_reverse($messages);
				$datas->anonymous = $anonymous_coordinator;
				$messages         = $datas;
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus_messages/models/messages | Error when try to get messages associated to user : ' . $user_id . ' with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
				$messages = [];
			}
		}

		return $messages;
	}

	/**
	 * Send a message on chatroom of an application file
	 *
	 * @param $message
	 * @param $fnum
	 *
	 * @return stdClass
	 *
	 * @throws Exception
	 * @since version 1.0.0
	 */
	function sendMessage($message, $fnum, $user_id = 0)
	{
		$result = new stdClass();

		if (empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
		require_once(JPATH_SITE . '/components/com_emundus/models/messages.php');
		$m_files    = new EmundusModelFiles;
		$m_messages = new EmundusModelMessages;

		if(!class_exists('EmundusHelperCache'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
		}
		$h_cache = new EmundusHelperCache();

		$fnum_detail = $m_files->getFnumInfos($fnum);

		if (!empty($fnum_detail))
		{
			$htmlSanitizer = HtmlSanitizerSingleton::getInstance();
			$message = $htmlSanitizer->sanitizeFor('title', $message);

			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			try
			{
				$query->select('id')
					->from($db->quoteName('#__emundus_chatroom'))
					->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum));
				$db->setQuery($query);
				$chatroom = $db->loadResult();

				if (empty($chatroom))
				{
					$chatroom = $m_messages->createChatroom($fnum);
				}

				if (!empty($chatroom))
				{
					$query->clear()
						->insert($db->quoteName('#__messages'))
						->set($db->quoteName('user_id_from') . ' = ' . $db->quote($user_id))
						->set($db->quoteName('folder_id') . ' = 2')
						->set($db->quoteName('date_time') . ' = ' . $db->quote(date('Y-m-d H:i:s')))
						->set($db->quoteName('state') . ' = 0')
						->set($db->quoteName('message') . ' = ' . $db->quote($message))
						->set($db->quoteName('page') . ' = ' . $db->quote($chatroom));
					$db->setQuery($query);

					if($db->execute())
					{
						$new_message = $db->insertid();

						if($h_cache->isEnabled())
						{
							// Add notifications to cache
							if ($fnum_detail['applicant_id'] === $user_id)
							{
								$cache_key_to_add = 'notifications_no_applicant';
								$cache_key_to_remove = 'notifications_' . $fnum_detail['applicant_id'];
							}
							else
							{
								$cache_key_to_add = 'notifications_' . $fnum_detail['applicant_id'];
								$cache_key_to_remove = 'notifications_no_applicant';
							}

							// Add to cache
							$notifications = $h_cache->get($cache_key_to_add);
							$notification_updated = false;
							if (!empty($notifications))
							{
								foreach ($notifications as $key => $notification)
								{
									if ($notification['fnum'] === $fnum)
									{
										$notification['notifications'] += 1;
										$notification['messages']      .= ',' . $new_message;
										$notifications[$key]           = $notification;
										$notification_updated          = true;
									}
								}
							}
							if (!$notification_updated)
							{
								$notifications[] = [
									'fnum'          => $fnum,
									'page'          => $chatroom,
									'notifications' => 1,
									'fullname'      => $fnum_detail['name'],
									'messages'      => $new_message
								];
							}
							$h_cache->set($cache_key_to_add, $notifications);
							//

							// Remove from cache
							$notifications_to_split = $h_cache->get($cache_key_to_remove);
							if (!empty($notifications_to_split))
							{
								foreach ($notifications_to_split as $key => $notification)
								{
									if ($notification['fnum'] === $fnum)
									{
										unset($notifications_to_split[$key]);
									}
								}
								$notifications_to_split = array_values($notifications_to_split);
								$h_cache->set($cache_key_to_remove, $notifications_to_split);
							}
							//
						}

						$statusMessage = $m_messages->getStatusChatroom($fnum);
						if ($statusMessage == 0)
						{
							$m_messages->openChatroom($fnum);
						}

						$result = $this->getMessageById($new_message);

						// Declare the event
						require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
						$m_files   = new EmundusModelFiles();
						$fnumInfos = $m_files->getFnumInfos($fnum);

						PluginHelper::importPlugin('emundus');
						$dispatcher = Factory::getApplication()->getDispatcher();

						$onAfterMessageSentEventHandler = new GenericEvent(
							'onCallEventHandler',
							['onAfterMessageSent',
								// Datas to pass to the event
								['fnum' => $fnum, 'ccid' => (int) $fnumInfos['ccid'], 'message' => $result, 'message_id' => $new_message]
							]
						);
						$onAfterMessageSent             = new GenericEvent(
							'onAfterMessageSent',
							// Datas to pass to the event
							['fnum' => $fnum, 'ccid' => (int) $fnumInfos['ccid'], 'message' => $result, 'message_id' => $new_message]
						);

						// Dispatch the event
						$dispatcher->dispatch('onCallEventHandler', $onAfterMessageSentEventHandler);
						$dispatcher->dispatch('onAfterMessageSent', $onAfterMessageSent);
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus_messages/models/messages | Error when try to get messages associated to user : ' . $user_id . ' with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $result;
	}

	function getMessageById($id, $user_id = 0)
	{
		$message = new stdClass();
		$query   = $this->db->createQuery();

		try
		{
			if (empty($user_id))
			{
				$user_id = Factory::getApplication()->getIdentity()->id;
			}

			$query->select('m.*,u.name')
				->from($this->db->quoteName('#__messages', 'm'))
				->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('m.user_id_from'))
				->where($this->db->quoteName('m.message_id') . ' = ' . $this->db->quote($id));
			$this->db->setQuery($query);

			$message            = $this->db->loadObject();
			$hour               = EmundusHelperDate::displayDate($message->date_time, 'H:i', 0);
			$message->date_hour = $hour;
			$message->me        = $message->user_id_from == $user_id;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus_messages/models/messages | Error when try to get messages with ID ' . $id . ' with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $message;
	}

	function getNotifications($user_id, $applicant = true, $messages_content = false)
	{
		$notifications = [];
		$query         = $this->db->createQuery();

		if(!class_exists('EmundusHelperCache'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/cache.php';
		}
		$h_cache = new EmundusHelperCache();

			try
			{
				if ($applicant)
				{
					if($h_cache->isEnabled())
					{
						$notifications = $h_cache->get('notifications_' . $user_id);
					}
					else {
						$notifications = false;
					}

					if($notifications === false)
					{
						$query->select('ec.fnum, ecn.chatroom_id as page, COUNT(ecn.message_id) as notifications, concat(eu.lastname, " ", eu.firstname) as fullname, group_concat(ecn.message_id) as messages')
							->from($this->db->quoteName('#__emundus_chatroom_notifications', 'ecn'))
							->leftJoin($this->db->quoteName('#__emundus_chatroom', 'ec') . ' ON ' . $this->db->quoteName('ec.id') . ' = ' . $this->db->quoteName('ecn.chatroom_id'))
							->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.id') . ' = ' . $this->db->quoteName('ec.ccid'))
							->leftJoin($this->_db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->_db->quoteName('eu.user_id') . ' LIKE ' . $this->_db->quoteName('ecn.user_id'))
							->where($this->db->quoteName('ecn.user_id') . ' = ' . $this->db->quote($user_id))
							->where($this->db->quoteName('ecc.applicant_id') . ' = ' . $this->db->quote($user_id))
							->andWhere($this->db->quoteName('ec.status') . ' = 1')
							->group('ecn.chatroom_id');
						$this->db->setQuery($query);
						$notifications = $this->db->loadAssocList();

						if($h_cache->isEnabled())
						{
							$h_cache->set('notifications_' . $user_id, $notifications);
						}
					}
				}
				else
				{
					if (!class_exists('EmundusHelperDate'))
					{
						require_once JPATH_SITE . '/components/com_emundus/helpers/date.php';
					}

					if($h_cache->isEnabled())
					{
						$notifications = $h_cache->get('notifications_no_applicant');
					}
					else {
						$notifications = false;
					}

					if($notifications === false)
					{
						// Get count of messages since last reply from an other user that applicant
						$query->select('ecc.fnum, m.page, COUNT(m.message_id) as notifications, CASE WHEN eu.is_anonym != 1 THEN concat(eu.lastname, " ", eu.firstname) ELSE '.$this->db->quote(Text::_('COM_EMUNDUS_ANONYM_ACCOUNT')).' END as fullname, group_concat(m.message_id) as messages')
							->from($this->_db->quoteName('#__emundus_campaign_candidature', 'ecc'))
							->leftJoin($this->_db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->_db->quoteName('eu.user_id') . ' LIKE ' . $this->_db->quoteName('ecc.applicant_id'))
							->leftJoin($this->_db->quoteName('#__emundus_chatroom', 'ec') . ' ON ' . $this->_db->quoteName('ec.fnum') . ' LIKE ' . $this->_db->quoteName('ecc.fnum'))
							->leftJoin($this->_db->quoteName('#__messages', 'm') . ' ON ' . $this->_db->quoteName('m.page') . ' = ' . $this->_db->quoteName('ec.id'))
							->where($this->_db->quoteName('m.user_id_from') . ' = ' . $this->_db->quoteName('ecc.applicant_id'))
							->andWhere($this->_db->quoteName('m.date_time') . ' > COALESCE((SELECT MAX(date_time) FROM jos_messages WHERE page = ec.id AND user_id_from <> ecc.applicant_id),"1970-01-01 00:00:00")')
							->andWhere($this->_db->quoteName('m.date_time') . ' <= NOW()')
							->andWhere($this->db->quoteName('ec.status') . ' = 1')
							->group('ecc.fnum');
						$this->_db->setQuery($query);
						$notifications = $this->_db->loadAssocList();

						if($h_cache->isEnabled())
						{
							$h_cache->set('notifications_no_applicant', $notifications);
						}
					}

					if (!empty($notifications) && $messages_content)
					{
						foreach ($notifications as $key => $notification)
						{
							if (EmundusHelperAccess::isUserAllowedToAccessFnum($user_id,$notification['fnum']) === false || EmundusHelperAccess::asAccessAction(36, 'c', $user_id, $notification['fnum']) === false)
							{
								unset($notifications[$key]);
								continue;
							}

							$messages_ids = explode(',', $notification['messages']);
							$query->clear()
								->select('m.message_id,m.message,m.date_time')
								->from($this->_db->quoteName('#__messages', 'm'))
								->where($this->_db->quoteName('m.message_id') . ' IN (' . implode(',',$messages_ids) . ')');
							$this->_db->setQuery($query);
							$notifications[$key]['messages'] = $this->_db->loadAssocList();
							foreach ($notifications[$key]['messages'] as $k => $message)
							{
								$notifications[$key]['messages'][$k]['date_time'] = EmundusHelperDate::displayDate($message['date_time']);
							}
						}
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus_messages/models/messages | Error when try to get messages associated to user : ' . $user_id . ' with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}

		return $notifications;
	}

	function getNotificationsByFnum($fnum, $user_id)
	{
		$notifications = 0;
		$query         = $this->db->createQuery();

		try
		{
			$query->select('COUNT(m.message_id)')
				->from($this->_db->quoteName('#__emundus_campaign_candidature','ecc'))
				->leftJoin($this->_db->quoteName('#__emundus_chatroom','ec').' ON '.$this->_db->quoteName('ec.fnum').' LIKE '.$this->_db->quoteName('ecc.fnum'))
				->leftJoin($this->_db->quoteName('#__messages','m').' ON '.$this->_db->quoteName('m.page').' = '.$this->_db->quoteName('ec.id'))
				->where($this->_db->quoteName('ec.status') . ' <> 0')
				->andWhere($this->_db->quoteName('m.user_id_from').' = '.$this->_db->quoteName('ecc.applicant_id'))
				->andWhere($this->_db->quoteName('m.date_time') . ' > COALESCE((SELECT MAX(date_time) FROM jos_messages WHERE page = ec.id AND user_id_from <> ecc.applicant_id),"1970-01-01 00:00:00")')
				->andWhere($this->_db->quoteName('m.date_time') . ' <= NOW()')
				->andWhere($this->_db->quoteName('ecc.fnum').' LIKE '.$this->_db->quote($fnum));
			$this->_db->setQuery($query);
			$notifications = $this->_db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus_messages/models/messages | Error when try to get messages associated to user : ' . $user_id . ' with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
		}

		return $notifications;
	}

	function markAsRead($chatroom_id, $user_id = 0)
	{
		$query = $this->db->createQuery();

		if (empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		try
		{
			$query->select('m.message_id')
				->from($this->db->quoteName('#__messages', 'm'))
				->where($this->db->quoteName('m.page') . ' = ' . $chatroom_id)
				->andWhere($this->db->quoteName('m.state') . ' = 0')
				->andWhere($this->db->quoteName('m.user_id_from') . ' <> ' . $user_id);
			$this->db->setQuery($query);
			$messages_readed = $this->db->loadColumn();

			if (!empty($messages_readed))
			{
				$query->clear()
					->update($this->db->quoteName('#__messages'))
					->set($this->db->quoteName('state') . ' = 1')
					->where($this->db->quoteName('message_id') . ' IN (' . implode(',', $messages_readed) . ')');
				$this->db->setQuery($query);
				$this->db->execute();

				// Remove this chatroom from notifications
				$query->clear()
					->delete($this->db->quoteName('#__emundus_chatroom_notifications'))
					->where($this->db->quoteName('chatroom_id') . ' = ' . $chatroom_id)
					->andWhere($this->db->quoteName('user_id') . ' = ' . $user_id);
				$this->db->setQuery($query);
				$this->db->execute();
			}

			return sizeof($messages_readed);
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus_messages/models/messages | Error when try to mark messages as read with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function getDocumentsByCampaign($fnum, $applicant)
	{
		$documents_by_campaign = [];

		if (!empty($fnum))
		{
			$db    = JFactory::getDbo();
			$query = $db->createQuery();

			try
			{
				if ($applicant == 'true')
				{
					$query->select('attachments')
						->from($db->quoteName('#__emundus_chatroom'))
						->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum));
					$db->setQuery($query);
					$attachment_allowed = $db->loadResult();

					if (!empty($attachment_allowed))
					{
						$query->clear()
							->select('id,value')
							->from($db->quoteName('#__emundus_setup_attachments'))
							->where($db->quoteName('id') . ' IN (' . $attachment_allowed . ')');
						$db->setQuery($query);

						$documents_by_campaign = $db->loadObjectList();
					}
				}
				else
				{
					$query->select('sc.profile_id')
						->from($db->quoteName('#__emundus_setup_campaigns', 'sc'))
						->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $db->quoteName('cc.campaign_id') . ' = ' . $db->quoteName('sc.id'))
						->where($db->quoteName('cc.fnum') . ' LIKE ' . $db->quote($fnum));
					$db->setQuery($query);
					$profile_id = $db->loadResult();

					if (!empty($profile_id))
					{
						$query->clear()
							->select('sa.id,sa.value')
							->from($db->quoteName('#__emundus_setup_attachments', 'sa'))
							->leftJoin($db->quoteName('#__emundus_setup_attachment_profiles', 'sap') . ' ON ' . $db->quoteName('sap.attachment_id') . ' = ' . $db->quoteName('sa.id'))
							->leftJoin($db->quoteName('#__emundus_setup_profiles', 'sp') . ' ON ' . $db->quoteName('sp.id') . ' = ' . $db->quoteName('sap.profile_id'))
							->where($db->quoteName('sp.id') . ' = ' . $db->quote($profile_id));
						$db->setQuery($query);

						$documents_by_campaign = $db->loadObjectList();
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus_messages/models/messages | Error when try to get documents with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
			}
		}

		return $documents_by_campaign;
	}

	function askAttachment($fnum, $attachment, $message)
	{
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'messages.php');
		$db    = JFactory::getDbo();
		$query = $db->createQuery();

		$m_messages = new EmundusModelMessages;

		try
		{
			$new_message = $this->sendMessage($message, $fnum);

			$query->select('id')
				->from($db->quoteName('#__emundus_chatroom'))
				->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum));
			$db->setQuery($query);
			$chatroom = $db->loadResult();

			if (empty($chatroom))
			{
				$chatroom = $m_messages->createChatroom($fnum);
			}

			$query->clear()
				->select('attachments')
				->from($db->quoteName('#__emundus_chatroom'))
				->where($db->quoteName('id') . ' LIKE ' . $db->quote($chatroom));
			$db->setQuery($query);
			$attachment_exist = $db->loadResult();

			if (!empty($attachment_exist))
			{
				$attachment .= ',' . $attachment_exist;
			}

			$query->clear()
				->update($db->quoteName('#__emundus_chatroom'))
				->set($db->quoteName('attachments') . ' = ' . $db->quote($attachment))
				->where($db->quoteName('id') . ' = ' . $db->quote($chatroom));
			$db->setQuery($query);
			$db->execute();

			return $new_message;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus_messages/models/messages | Error when try to ask attachment with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	function moveToUploadedFile($fnumInfos, $attachment, $filesrc, $target_file)
	{
		$moved = false;

		if (!empty($fnumInfos['fnum']))
		{
			$db    = JFactory::getDbo();
			$query = $db->createQuery();
			$user  = JFactory::getUser();

			try
			{
				if (empty($attachment))
				{
					$query->select('id')
						->from($db->quoteName('#__emundus_setup_attachments'))
						->where($db->quoteName('lbl') . ' LIKE ' . $db->quote('_messenger_attachments'));
					$db->setQuery($query);
					$attachment = $db->loadResult();
				}

				if (!empty($attachment))
				{
					$query->clear()
						->insert($db->quoteName('#__emundus_uploads'))
						->set($db->quoteName('user_id') . ' = ' . $db->quote($user->id))
						->set($db->quoteName('fnum') . ' = ' . $db->quote($fnumInfos['fnum']))
						->set($db->quoteName('campaign_id') . ' = ' . $db->quote($fnumInfos['id']))
						->set($db->quoteName('attachment_id') . ' = ' . $db->quote($attachment))
						->set($db->quoteName('filename') . ' = ' . $db->quote($filesrc))
						->set($db->quoteName('description') . ' = ' . $db->quote(''));
					$db->setQuery($query);
					$inserted = $db->execute();

					if ($inserted)
					{
						$query->clear()
							->select('id, attachments')
							->from($db->quoteName('#__emundus_chatroom'))
							->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnumInfos['fnum']));
						$db->setQuery($query);
						$chatroom = $db->loadObject();

						if (!empty($chatroom) && !empty($chatroom->id))
						{
							$chatroom_attachments = explode(',', $chatroom->attachments);
							foreach ($chatroom_attachments as $key => $attach)
							{
								if ($attach == $attachment)
								{
									unset($chatroom_attachments[$key]);
								}
							}

							if (!empty($chatroom_attachments))
							{
								$attachs = implode(',', $chatroom_attachments);
							}
							else
							{
								$attachs = $db->quote(null);
							}

							$query->clear()
								->update($db->quoteName('#__emundus_chatroom'))
								->set($db->quoteName('attachments') . ' = ' . $db->quote($attachs))
								->where($db->quoteName('fnum') . ' = ' . $db->quote($fnumInfos['fnum']));
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('component/com_emundus_messages/models/messages | Error when try to move file to emundus upload with query : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus');
				$moved = false;
			}
		}

		return $moved;
	}

	/**
	 * @param $applicant_fnum
	 * @param $notify_applicant
	 *
	 *
	 * @depecated since version 2.2.0
	 */
	function notifyByMail($applicant_fnum, $notify_applicant = 0)
	{
		$db    = JFactory::getDbo();
		$query = $db->createQuery();

		include_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
		include_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		include_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'profile.php');
		include_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'controllers' . DS . 'messages.php');

		$m_files    = new EmundusModelFiles;
		$m_profile  = new EmundusModelProfile;
		$c_messages = new EmundusControllerMessages;

		$eMConfig                 = JComponentHelper::getParams('com_emundus');
		$notify_groups            = $eMConfig->get('messenger_notify_groups', '');
		$notify_users             = explode(',', $eMConfig->get('messenger_notify_users', ''));
		$notify_to_users_programs = $eMConfig->get('messenger_notify_users_programs', 0);
		$add_message_notif        = $eMConfig->get('messenger_add_message_notif', 0);
		$fnumTagsInfos            = $m_files->getFnumTagsInfos($applicant_fnum);

		if ($notify_applicant)
		{
			$query->select($db->quoteName('id'))
				->from($db->quoteName('#__emundus_setup_emails'))
				->where($db->quoteName('lbl') . ' LIKE ' . $db->quote('messenger_reminder'));
			$db->setQuery($query);
			$email_tmpl = $db->loadResult();

			$c_messages->sendEmail($applicant_fnum, $email_tmpl);
		}
		else
		{
			// Send notifications to users/groups associated to file
			$query->select($db->quoteName('id'))
				->from($db->quoteName('#__emundus_setup_emails'))
				->where($db->quoteName('lbl') . ' LIKE ' . $db->quote('messenger_reminder_group'));
			$db->setQuery($query);
			$email_tmpl = $db->loadResult();

			$users_associated_programs = array();

			// Get users associated to the file by their group and the campaign_program
			if ($notify_to_users_programs == '1')
			{
				$query->clear()
					->select('DISTINCT ' . $db->quoteName('u.id'))
					->from($db->quoteName('#__emundus_groups', 'g'))
					->leftJoin($db->quoteName('#__emundus_setup_groups_repeat_course', 'grc') . ' ON ' . $db->quoteName('grc.parent_id') . ' = ' . $db->quoteName('g.group_id'))
					->innerJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('g.user_id'))
					->where($db->quoteName('grc.course') . ' LIKE ' . $db->quote($fnumTagsInfos['campaign_code']));
				$db->setQuery($query);
				$users_associated_programs = $db->loadColumn();

				if (!empty($users_associated_programs))
				{
					$users_associated_programs = array_filter($users_associated_programs);
				}
			}

			// Get users associated to the file by their group directly
			$query->clear()
				->select('DISTINCT ' . $db->quoteName('u.id'))
				->from($db->quoteName('#__emundus_groups', 'g'))
				->leftJoin($db->quoteName('#__emundus_group_assoc', 'ga') . ' ON ' . $db->quoteName('ga.group_id') . ' = ' . $db->quoteName('g.group_id'))
				->innerJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('g.user_id'))
				->where($db->quoteName('ga.fnum') . ' = ' . $db->quote($applicant_fnum));
			$db->setQuery($query);
			$groups_associated = $db->loadColumn();

			if (!empty($groups_associated))
			{
				$groups_associated = array_filter($groups_associated);
			}

			// Get users associated to the file directly
			$query->clear()
				->select('DISTINCT ' . $db->quoteName('u.id'))
				->from($db->quoteName('#__emundus_campaign_candidature', 'cc'))
				->leftJoin($db->quoteName('#__emundus_users_assoc', 'eua') . ' ON ' . $db->quoteName('eua.fnum') . ' LIKE ' . $db->quoteName('cc.fnum'))
				->innerJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('eua.user_id'))
				->where($db->quoteName('cc.fnum') . ' LIKE ' . $db->quote($applicant_fnum))
				->group($db->quoteName('cc.fnum'));
			$db->setQuery($query);
			$users_associated = $db->loadColumn();

			if (!empty($users_associated))
			{
				$users_associated = array_filter($users_associated);
			}

			$users_to_send = array_filter(array_unique(array_merge($users_associated_programs, $groups_associated, $users_associated)));

			$users_notify_groups = array();

			// Pre-filter users to send the notification to by their group
			if (!empty($notify_groups))
			{
				$query->clear()
					->select('DISTINCT user_id')
					->from('#__emundus_groups')
					->where('gr.group_id IN (' . $notify_groups . ')');
				$db->setQuery($query);
				$users_notify_groups = $db->loadColumn();

			}

			if (!empty($users_notify_groups))
			{
				$users_to_send = array_intersect($users_to_send, $users_notify_groups);
			}

			// Just to be sure, check for every user in the list if they can access the file
			foreach ($users_to_send as $key => $user_to_check)
			{
				$can_access = EmundusHelperAccess::isUserAllowedToAccessFnum($user_to_check, $applicant_fnum);

				if (!$can_access)
				{
					unset($users_to_send[$key]);
				}
			}

			// Add additional users to notify, these do not need to be able to access the file
			if (!empty($notify_users))
			{
				$users_to_send = array_intersect($users_to_send, $notify_users);
			}

			// If no users found to notify send to coordinators instead
			if (empty($users_to_send))
			{
				$query->clear()
					->select('DISTINCT ' . $db->quoteName('eu.user_id'))
					->from($db->quoteName('#__emundus_users_profiles', 'eup'))
					->leftJoin($db->quoteName('#__emundus_users', 'eu') . ' ON ' . $db->quoteName('eu.user_id') . ' = ' . $db->quoteName('eup.user_id'))
					->where($db->quoteName('profile_id') . ' = 2');
				$db->setQuery($query);
				$users_to_send = $db->loadColumn();

				// We still need to check if the coordinator has access to the fnum
				foreach ($users_to_send as $key => $user_to_check)
				{
					$can_access = EmundusHelperAccess::isUserAllowedToAccessFnum($user_to_check, $applicant_fnum);

					if (!$can_access)
					{
						unset($users_to_send[$key]);
					}
				}
			}

			if (!empty($users_to_send))
			{
				$message = '';
				if ($add_message_notif)
				{
					$query->clear()
						->select($db->quoteName('m.message'))
						->from($db->quoteName('#__messages', 'm'))
						->leftJoin($db->quoteName('#__emundus_chatroom', 'ec') . ' ON ' . $db->quoteName('ec.id') . ' = ' . $db->quoteName('m.page'))
						->where($db->quoteName('ec.fnum') . ' LIKE ' . $db->quote($applicant_fnum))
						->order($db->quoteName('m.message_id') . ' DESC');
					$db->setQuery($query);
					$message = $db->loadResult();
				}

				foreach ($users_to_send as $user_to_send)
				{
					$query->clear()
						->select($db->quoteName(array('id', 'email', 'name')))
						->from($db->quoteName('#__users'))
						->where($db->quoteName('id') . ' = ' . $user_to_send);
					$db->setQuery($query);
					$user_info = $db->loadObject();

					$to = $user_info->email;

					$menutype = $m_profile->getProfileByApplicant($user_info->id)['menutype'];

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

					$fnumList          = '<ul>';
					$fnumListCampaign  = '<ul>';
					$fnumListProgramme = '<ul>';
					if (!empty($userLink))
					{
						$fnumList          .= '<li><a href="' . JURI::root() . $userLink->path . '#' . $applicant_fnum . '|open">' . $applicant_fnum . '</a></li>';
						$fnumListCampaign  .= '<li><a href="' . JURI::root() . $userLink->path . '#' . $applicant_fnum . '|open">' . $applicant_fnum . '</a>' . ' (' . $fnumTagsInfos['campaign_label'] . ')' . '</li>';
						$fnumListProgramme .= '<li><a href="' . JURI::root() . $userLink->path . '#' . $applicant_fnum . '|open">' . $applicant_fnum . '</a>' . ' (' . $fnumTagsInfos['training_programme'] . ')' . '</li>';
					}
					else
					{
						$fnumList          .= '<li>' . $applicant_fnum . '</li>';
						$fnumListCampaign  .= '<li>' . $applicant_fnum . ' (' . $fnumTagsInfos['campaign_label'] . ')</li>';
						$fnumListProgramme .= '<li>' . $applicant_fnum . ' (' . $fnumTagsInfos['training_programme'] . ')</li>';
					}
					if (!empty($message))
					{
						$fnumList          .= '<br />"' . $message . '"';
						$fnumListCampaign  .= '<br />"' . $message . '"';
						$fnumListProgramme .= '<br />"' . $message . '"';
					}
					$fnumList          .= '</ul>';
					$fnumListCampaign  .= '</ul>';
					$fnumListProgramme .= '</ul>';

					$post = array(
						'FNUMS'           => $fnumList,
						'FNUMS_CAMPAIGNS' => $fnumListCampaign,
						'FNUMS_TRAININGS' => $fnumListProgramme,
						'APPLICANT_NAME'  => $fnumTagsInfos['applicant_name'],
						'NAME'            => $user_info->name,
						'USER_NAME'       => $user_info->name,
						'SITE_URL'        => JURI::root(),
					);

					$c_messages->sendEmailNoFnum($to, $email_tmpl, $post, $user_info->id);
					// to avoid been considered as a spam process or DDoS
					sleep(0.1);
				}
			}
		}
	}
}
