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

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('coordinatormenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('reservations') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=events&layout=registrants'));
			$this->db->setQuery($query);
			$list_reservations_coord = $this->db->loadResult();

			if(empty($list_reservations_coord)) {
				$data              = [
					'menutype'          => 'coordinatormenu',
					'title'             => 'Réservations',
					'alias'             => 'reservations',
					'path'              => 'reservations',
					'link'              => 'index.php?option=com_emundus&view=events&layout=registrants',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
				];
				$reservations_menu_coord = EmundusHelperUpdate::addJoomlaMenu($data, 1, 0);
				EmundusHelperUpdate::insertFalangTranslation(1, $reservations_menu_coord['id'], 'menu', 'title', 'Reservations');
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('adminmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('reservations-admin') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=events&layout=registrants'));
			$this->db->setQuery($query);
			$list_reservations_sysadmin = $this->db->loadResult();

			if(empty($list_reservations_sysadmin)) {
				$data              = [
					'menutype'          => 'adminmenu',
					'title'             => 'Réservations',
					'alias'             => 'reservations-admin',
					'path'              => 'reservations-admin',
					'link'              => 'index.php?option=com_emundus&view=events&layout=registrants',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'params'       => [
						'menu_image_css' => 'calendar_clock'
					],
					'template_style_id' => 0
				];
				$reservations_menu_sysadmin = EmundusHelperUpdate::addJoomlaMenu($data, 1, 0);
				EmundusHelperUpdate::insertFalangTranslation(1, $reservations_menu_sysadmin['id'], 'menu', 'title', 'Reservations');
			}

			EmundusHelperUpdate::addCustomEvents([
				['label' => 'onAfterUnsubscribeRegistrant', 'category' => 'Booking'],
				['label' => 'onAfterBookingRegistrant', 'category' => 'Booking']
			]);


			$query->clear()
				->select('id')
				->from('#__emundus_setup_actions')
				->where('name = ' . $this->db->quote('booking'));
			$this->db->setQuery($query);
			$booking_acl = $this->db->loadResult();

			if(empty($booking_acl))
			{
				$query->clear()
					->select('MAX(ordering)')
					->from('#__emundus_setup_actions')
					->where('ordering <> 999');
				$this->db->setQuery($query);
				$ordering = $this->db->loadResult();

				$booking_acl = [
					'name'        => 'booking',
					'label'       => 'COM_EMUNDUS_ACL_BOOKING',
					'multi'       => 0,
					'c'           => 1,
					'r'           => 1,
					'u'           => 1,
					'd'           => 1,
					'ordering'    => $ordering + 1,
					'status'      => 1,
					'description' => 'COM_EMUNDUS_ACL_BOOKING_DESC'
				];
				$booking_acl = (object) $booking_acl;
				$this->db->insertObject('#__emundus_setup_actions', $booking_acl);
				$booking_acl->id = $this->db->insertid();

				// Give all rights to all rights group
				$all_rights_group = ComponentHelper::getParams('com_emundus')->get('all_rights_group', 1);
				$booking_acl_rights = [
					'group_id' => $all_rights_group,
					'action_id' => $booking_acl->id,
					'c' => 1,
					'r' => 1,
					'u' => 1,
					'd' => 1,
					'time_date' => date('Y-m-d H:i:s')
				];
				$booking_acl_rights = (object) $booking_acl_rights;
				$this->db->insertObject('#__emundus_acl', $booking_acl_rights);
			}

			EmundusHelperUpdate::addColumn('jos_emundus_setup_events','teams_subject','VARCHAR', 255);

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

			$tasks = [];

			$columns = [
				['name' => 'created_by', 'type' => 'INT', 'null' => 0],
				['name' => 'created_date', 'type' => 'DATETIME', 'null' => 0],
				['name' => 'modified_by', 'type' => 'INT', 'null' => 1, 'default' => 0],
				['name' => 'modified_date', 'type' => 'DATETIME', 'null' => 1, 'default' => ''],
				['name' => 'label', 'type' => 'VARCHAR(255)', 'null' => 0, 'default' => ''],
				['name' => 'message', 'type' => 'VARCHAR(255)', 'null' => 0, 'default' => ''],
				['name' => 'category_id', 'type' => 'INT', 'null' => 1],
				['name' => 'success_tag', 'type' => 'INT', 'null' => 1, 'default' => 0],
				['name' => 'failure_tag', 'type' => 'INT', 'null' => 1, 'default' => 0],
				['name' => 'published', 'type' => 'BOOL', 'null' => 0, 'default' => true]
			];
			$sms_table_result = EmundusHelperUpdate::createTable('jos_emundus_setup_sms', $columns);
			$tasks[] = $sms_table_result['status'];

			$columns = [
				['name' => 'created_by', 'type' => 'INT', 'null' => 0],
				['name' => 'created_date', 'type' => 'DATETIME', 'null' => 0],
				['name' => 'updated_date', 'type' => 'DATETIME', 'null' => 1],
				['name' => 'message', 'type' => 'VARCHAR(255)', 'null' => 0, 'default' => ''],
				['name' => 'phone_number', 'type' => 'VARCHAR(255)', 'null' => 0, 'default' => ''],
				['name' => 'fnum', 'type' => 'VARCHAR(28)', 'null' => 1, 'default' => ''],
				['name' => 'template_id', 'type' => 'INT', 'null' => 1, 'default' => '0'],
				['name' => 'user_id', 'type' => 'INT', 'null' => 1, 'default' => ''],
				['name' => 'attempts', 'type' => 'INT', 'null' => 1, 'default' => '0'],
				['name' => 'status', 'type' => 'VARCHAR(255)', 'null' => 0, 'default' => 'pending'],
			];
			$sms_queue_result = EmundusHelperUpdate::createTable('jos_emundus_sms_queue', $columns);
			$tasks[] = $sms_queue_result['status'];

			$columns = [
				['name' => 'label', 'type' => 'VARCHAR(255)', 'null' => 0, 'default' => ''],
				['name' => 'type', 'type' => 'VARCHAR(255)', 'null' => 0, 'default' => ''],
				['name' => 'published', 'type' => 'TINYINT(1)', 'null' => 0, 'default' => '1'],
			];
			$setup_category_result = EmundusHelperUpdate::createTable('jos_emundus_setup_category', $columns);
			$tasks[] = $setup_category_result['status'];

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_actions'))
				->where('name = ' . $this->db->quote('sms'));
			$this->db->setQuery($query);
			$sms_acl = $this->db->loadResult();

			if (empty($sms_acl)) {
				$query->clear()
					->select('MAX(ordering)')
					->from('#__emundus_setup_actions')
					->where('ordering <> 999');
				$this->db->setQuery($query);
				$ordering = $this->db->loadResult();

				$sms_acl = [
					'name'        => 'sms',
					'label'       => 'COM_EMUNDUS_ACCESS_SMS',
					'multi'       => 1,
					'c'           => 1,
					'r'           => 1,
					'u'           => 1,
					'd'           => 0,
					'ordering'    => $ordering + 1,
					'status'      => 1,
					'description' => 'COM_EMUNDUS_ACCESS_SMS_DESC'
				];
				$sms_acl = (object) $sms_acl;
				$this->db->insertObject('#__emundus_setup_actions', $sms_acl);
				$sms_acl = $this->db->insertid();

				// Give all rights to all rights group
				$all_rights_group = ComponentHelper::getParams('com_emundus')->get('all_rights_group', 1);
				$sms_acl_rights = [
					'group_id' => $all_rights_group,
					'action_id' =>$sms_acl,
					'c' => 1,
					'r' => 1,
					'u' => 1,
					'd' => 1,
					'time_date' => date('Y-m-d H:i:s')
				];
				$sms_acl_rights = (object) $sms_acl_rights;
				$inserted = $this->db->insertObject('#__emundus_acl', $sms_acl_rights);

				$tasks[] = $inserted;
			}

			if (!empty($sms_acl)) {
				$result = EmundusHelperUpdate::addJoomlaMenu([
					'menutype' => 'application',
					'title' => 'SMS',
					'link' => 'index.php?option=com_emundus&view=application&layout=sms&format=raw',
					'alias' => 'sms',
					'path' => 'sms',
					'type' => 'component',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'access' => 6,
					'menu_show' => 0,
					'note' => $sms_acl . '|r'
				], 1, 0);
				$tasks[] = $result['status'];

				// found menu heading for SMS
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__menu'))
					->where('menutype = ' . $this->db->quote('actions'))
					->where('title = ' . $this->db->quote('Envoyer'))
					->where('type = ' . $this->db->quote('heading'));

				$this->db->setQuery($query);
				$parent_id = $this->db->loadResult();

				$result  = EmundusHelperUpdate::addJoomlaMenu([
					'menutype'     => 'actions',
					'title'        => 'SMS au(x) déposant(s)',
					'link'         => '/index.php?option=com_emundus&view=sms&layout=send&format=raw&fnums={fnums}',
					'alias'        => 'send-sms-action',
					'path'         => 'send-sms-action',
					'type'         => 'url',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'access'       => 6,
					'menu_show'    => 0,
					'note'         => 'sms|c|1'
				], $parent_id, 0);
				$tasks[] = $result['status'];
			}

			$manifest = '{"name":"plg_task_sendsms","type":"plugin","creationDate":"2025-02-24","author":"eMundus","copyright":"(C) 2024 Open Source Matters, Inc.","authorEmail":"dev@emundus.io","authorUrl":"www.emundus.fr","version":"2.3.0","description":"PLG_TASK_SMS_XML_DESCRIPTION","group":"","changelogurl":"","namespace":"Joomla\\Plugin\\Task\\SendSMS","filename":"sendsms"}';
			$tasks[] = EmundusHelperUpdate::installExtension('plg_task_sendsms', 'sendsms', $manifest, 'plugin', 1, 'task');

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_sync'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('ovh'));
			$this->db->setQuery($query);
			$ovh = $this->db->loadResult();

			if (empty($ovh))
			{
				$ovh = [
					'type'        => 'ovh',
					'name'        => 'SMS OVH',
					'description' => 'Envoi de SMS via OVH',
					'params'      => '{}',
					'config'      => '{}',
					'icon'        => 'ovh.svg',
					'enabled'     => 0,
					'published'   => 0,
				];
				$ovh = (object) $ovh;
				$this->db->insertObject('jos_emundus_setup_sync', $ovh);
			}

			// Create add sms menu
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
				->andWhere($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=emails'));
			$this->db->setQuery($query);
			$emails_menu_id = $this->db->loadResult();

			if(!empty($emails_menu_id))
			{
				$datas         = [
					'menutype'     => 'onboardingmenu',
					'title'        => 'Créer un SMS',
					'alias'        => 'edit',
					'path'         => 'sms/edit',
					'link'         => 'index.php?option=com_emundus&view=sms&layout=edit',
					'type'         => 'component',
					'component_id' => ComponentHelper::getComponent('com_emundus')->id,
					'params'       => [
						'menu_show' => 0
					]
				];
				$edit_sms_menu = EmundusHelperUpdate::addJoomlaMenu($datas, $emails_menu_id, 1);


				if ($edit_sms_menu['status'])
				{
					EmundusHelperUpdate::insertFalangTranslation(1, $edit_sms_menu['id'], 'menu', 'title', 'Create an SMS');
				}
				else
				{
					EmundusHelperUpdate::displayMessage('Error creating add sms menu', 'error');
				}

				// Create scheduler task for sending sms
				$execution_rules = [
					'rule-type'     => 'interval-minutes',
					'interval-minutes' => '5',
					'exec-day'      => date('d'),
					'exec-time'     => '23:00',
				];
				$cron_rules      = [
					'type' => 'interval',
					'exp'  => 'PT5M',
				];
				EmundusHelperUpdate::createSchedulerTask('Sending SMS', 'plg_task_sms_task_get', $execution_rules, $cron_rules, [], 0);
				//
			}

			$columns      = [
				['name' => 'registrant', 'type' => 'INT', 'null' => 0],
				['name' => 'user', 'type' => 'INT', 'null' => 0],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_registrants_registrant_fk',
					'from_column'    => 'registrant',
					'ref_table'      => 'jos_emundus_registrants',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_registrants_users_user_fk',
					'from_column'    => 'user',
					'ref_table'      => 'jos_users',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				],
			];
			EmundusHelperUpdate::createTable('jos_emundus_registrants_users', $columns, $foreign_keys, 'Users associated to registrants');

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__viewlevels'))
				->where($this->db->quoteName('title') . ' LIKE ' . $this->db->quote('Coordinator'));
			$this->db->setQuery($query);
			$coordinator_viewlevel = $this->db->loadResult();

			if(!empty($coordinator_viewlevel)) {
				$query->clear()
					->update($this->db->quoteName('#__content'))
					->set($this->db->quoteName('access') . ' = ' . $this->db->quote($coordinator_viewlevel))
					->where($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('ressources'));
				$this->db->setQuery($query);
				$this->db->execute();
			}

			$result['status'] = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status'] = false;
			$result['message'] = $e->getMessage();

			return $result;
		}


		return $result;
	}
}