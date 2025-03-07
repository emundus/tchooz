<?php


/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;
use Symfony\Component\Yaml\Yaml;

class Release2_3_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		try
		{
			/* MESSENGER */
			// Update chatroom with ccid
			EmundusHelperUpdate::addColumn('jos_emundus_chatroom', 'ccid', 'INT');

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('applicantmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('mes-reservations') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=events&layout=mybooking'));
			$this->db->setQuery($query);
			$list_reservations = $this->db->loadResult();

			if(empty($list_reservations))
			{
				$data              = [
					'menutype'          => 'applicantmenu',
					'title'             => 'Mes réservations',
					'alias'             => 'mes-reservations',
					'path'              => 'mes-reservations',
					'link'              => 'index.php?option=com_emundus&view=events&layout=mybooking',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
				];
				$reservations_menu = EmundusHelperUpdate::addJoomlaMenu($data, 1, 0);
				EmundusHelperUpdate::insertFalangTranslation(1, $reservations_menu['id'], 'menu', 'title', 'My reservations');
			}

			EmundusHelperUpdate::addCustomEvents([
				['label' => 'onAfterUnsubscribeRegistrant', 'category' => 'Booking'],
				['label' => 'onAfterBookingRegistrant', 'category' => 'Booking']
			]);

			$query->clear()
				->select('extension_id,params')
				->from('#__extensions')
				->where('type = ' . $this->db->quote('plugin'))
				->where('name = ' . $this->db->quote('plg_user_joomla'))
				->where('element = ' . $this->db->quote('joomla'))
				->where('folder = ' . $this->db->quote('user'));
			$this->db->setQuery($query);
			$extension = $this->db->loadObject();

			if(!empty($extension))
			{
				$params = json_decode($extension->params, true);
				$params['mail_to_user'] = '0';
				$extension->params = json_encode($params);
				$this->db->updateObject('#__extensions', $extension, 'extension_id');
			}
			//

			$query->clear()
				->select('link')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('coordinatormenu'))
				->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('email-history'));
			$this->db->setQuery($query);
			$email_history_link = $this->db->loadResult();

			if (!empty($email_history_link))
			{
				$query->clear()
					->update($this->db->quoteName('#__menu'))
					->set($this->db->quoteName('link') . ' = ' . $this->db->quote($email_history_link))
					->set($this->db->quoteName('published') . ' = 1')
					->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('adminmenu'))
					->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('email-history'));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			$query->clear()
				->update($this->db->quoteName('#__emundus_chatroom', 'ec'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.fnum') . ' = ' . $this->db->quoteName('ec.fnum'))
				->set($this->db->quoteName('ec.ccid') . ' = ' . $this->db->quoteName('ecc.id'))
				->where($this->db->quoteName('ec.ccid') . ' IS NULL');
			$this->db->setQuery($query);
			$this->db->execute();
			//

			// Install messenger event handler
			EmundusHelperUpdate::installExtension('Messenger recall', 'messenger', null, 'plugin', 1, 'emundus');
			EmundusHelperUpdate::addCustomEvents([['label' => 'onAfterMessageSent', 'category' => 'Messenger']]);
			//

			// Create jos_emundus_chatroom_notifications
			$columns      = [
				[
					'name' => 'chatroom_id',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'user_id',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'message_id',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name' => 'last_notification',
					'type' => 'DATETIME',
					'null' => 1
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_chatroom_notifications_chatroom_fk',
					'from_column'    => 'chatroom_id',
					'ref_table'      => 'jos_emundus_chatroom',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_chatroom_notifications_user_fk',
					'from_column'    => 'user_id',
					'ref_table'      => 'jos_users',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_chatroom_notifications', $columns, $foreign_keys);

			EmundusHelperUpdate::installExtension('plg_task_emundusmessenger', 'emundusmessenger', null, 'plugin', 1, 'task');

			$query->clear()
				->select('id')
				->from('#__scheduler_tasks')
				->where('type = ' . $this->db->quote('plg_task_emundusmessenger_task_get'));
			$this->db->setQuery($query);
			$task = $this->db->loadResult();

			if (empty($task))
			{
				$execution_rules = [
					'rule-type'     => 'interval-days',
					'interval-days' => 1,
					'exec-day'      => 20,
					'exec-time'     => '12:00'
				];
				$cron_rules      = [
					'type' => 'interval',
					'exp'  => 'P1D'
				];

				EmundusHelperUpdate::createSchedulerTask('[MESSENGER] Recall unread messages', 'plg_task_emundusmessenger_task_get', $execution_rules, $cron_rules);
			}

			// Delete MESSENGER_NOTIFY tag
			$query->clear()
				->delete('#__emundus_setup_tags')
				->where('tag = ' . $this->db->quote('MESSENGER_NOTIFY'));
			$this->db->setQuery($query);
			$this->db->execute();

			$result['status'] = true;
		}
		catch (\Exception $e)
		{
			$result['message'] = $e->getMessage();

			return $result;
		}


		return $result;
	}
}