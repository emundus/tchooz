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

class Release2_2_0Installer extends ReleaseInstaller
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
			/* PREPARE BOOKING */

			// Create data_events_location
			$columns = [
				[
					'name' => 'date_time',
					'type' => 'DATETIME',
					'null' => 0,
				],
				[
					'name'   => 'name',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name' => 'address',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name' => 'description',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name' => 'map_location',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name'    => 'published',
					'type'    => 'TINYINT',
					'null'    => 0,
					'default' => 1
				],
				[
					'name' => 'created_by',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'updated',
					'type' => 'DATETIME',
					'null' => 1
				],
				[
					'name' => 'updated_by',
					'type' => 'INT',
					'null' => 1
				]
			];
			EmundusHelperUpdate::createTable('data_events_location', $columns);
			//

			// Create table data_location_rooms
			$columns      = [
				[
					'name' => 'location',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name'   => 'name',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name' => 'capacity',
					'type' => 'INT',
					'null' => 1
				]
			];
			$foreign_keys = [
				[
					'name'           => 'data_location_rooms_location_fk',
					'from_column'    => 'location',
					'ref_table'      => 'data_events_location',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			EmundusHelperUpdate::createTable('data_location_rooms', $columns, $foreign_keys);
			//

			// Create table data_specifications
			$columns = [
				[
					'name' => 'date_time',
					'type' => 'DATETIME',
					'null' => 0,
				],
				[
					'name'   => 'name',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 0
				],
				[
					'name'    => 'published',
					'type'    => 'TINYINT',
					'null'    => 0,
					'default' => 1
				],
				[
					'name' => 'created_by',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'updated',
					'type' => 'DATETIME',
					'null' => 1
				],
				[
					'name' => 'updated_by',
					'type' => 'INT',
					'null' => 1
				]
			];
			EmundusHelperUpdate::createTable('data_specifications', $columns);

			// Add some default datas
			$query->clear()
				->select('count(*)')
				->from('data_specifications');
			$this->db->setQuery($query);
			$count = $this->db->loadResult();

			if (empty($count))
			{
				$default_specs = [
					[
						'date_time'  => date('Y-m-d H:i:s'),
						'name'       => 'Audio',
						'created_by' => 1
					],
					[
						'date_time'  => date('Y-m-d H:i:s'),
						'name'       => 'Vidéo',
						'created_by' => 1
					]
				];

				foreach ($default_specs as $default_spec)
				{
					$default_spec = (object) $default_spec;
					$this->db->insertObject('data_specifications', $default_spec);
				}
			}
			//
			//

			// Create table data_location_rooms_specs
			$columns      = [
				[
					'name' => 'room',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'specification',
					'type' => 'INT',
					'null' => 0
				]
			];
			$foreign_keys = [
				[
					'name'           => 'data_location_rooms_specs_room_fk',
					'from_column'    => 'room',
					'ref_table'      => 'data_location_rooms',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'data_location_rooms_specs_specification_fk',
					'from_column'    => 'specification',
					'ref_table'      => 'data_specifications',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				]
			];
			EmundusHelperUpdate::createTable('data_location_rooms_specs', $columns, $foreign_keys);
			//

			// Create table jos_emundus_setup_events
			$columns      = [
				[
					'name' => 'date_time',
					'type' => 'DATETIME',
					'null' => 0,
				],
				[
					'name'   => 'name',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1,
				],
				[
					'name'   => 'color',
					'type'   => 'VARCHAR',
					'length' => 10,
					'null'   => 1,
				],
				[
					'name' => 'location',
					'type' => 'INT',
					'null' => 1,
				],
				[
					'name'    => 'is_conference_link',
					'type'    => 'TINYINT',
					'null'    => 0,
					'default' => 0,
				],
				[
					'name'    => 'conference_engine',
					'type'    => 'VARCHAR',
					'length'  => 50,
					'null'    => 1,
					'comment' => 'teams, zoom, other'
				],
				[
					'name'    => 'generate_link_by',
					'type'    => 'INT',
					'null'    => 1,
					'comment' => '1: booking, 2: slot'
				],
				[
					'name'   => 'link',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name' => 'manager',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name'    => 'available_for',
					'type'    => 'INT',
					'null'    => 1,
					'comment' => '1: campaigns, 2: programs'
				],
				[
					'name' => 'slot_duration',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name'   => 'slot_duration_type',
					'type'   => 'VARCHAR',
					'length' => 50,
					'null'   => 1
				],
				[
					'name' => 'slot_break_every',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name' => 'slot_break_time',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name'   => 'slot_break_time_type',
					'type'   => 'VARCHAR',
					'length' => 50,
					'null'   => 1
				],
				[
					'name' => 'slots_availables_to_show',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name' => 'slot_can_book_until_days',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name' => 'slot_can_book_until_date',
					'type' => 'DATETIME',
					'null' => 1
				],
				[
					'name' => 'slot_can_cancel',
					'type' => 'TINYINT',
					'null' => 1
				],
				[
					'name' => 'slot_can_cancel_until_days',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name' => 'slot_can_cancel_until_date',
					'type' => 'DATETIME',
					'null' => 1
				],
				[
					'name' => 'created_by',
					'type' => 'INT',
					'null' => 0,
				],
				[
					'name' => 'updated',
					'type' => 'DATETIME',
					'null' => 1,
				],
				[
					'name' => 'updated_by',
					'type' => 'INT',
					'null' => 1,
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_events_location_fk',
					'from_column'    => 'location',
					'ref_table'      => 'data_events_location',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				],
				[
					'name'           => 'jos_emundus_setup_events_manager_fk',
					'from_column'    => 'manager',
					'ref_table'      => 'jos_users',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_events', $columns, $foreign_keys);
			//

			// Create jos_emundus_setup_events_repeat_campaign
			$columns      = [
				[
					'name' => 'event',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'campaign',
					'type' => 'INT',
					'null' => 0
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_events_repeat_campaign_event_fk',
					'from_column'    => 'event',
					'ref_table'      => 'jos_emundus_setup_events',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_events_repeat_campaign_campaign_fk',
					'from_column'    => 'campaign',
					'ref_table'      => 'jos_emundus_setup_campaigns',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_events_repeat_campaign', $columns, $foreign_keys);
			//

			// Create jos_emundus_setup_events_repeat_programme
			$columns      = [
				[
					'name' => 'event',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'programme',
					'type' => 'INT',
					'null' => 0
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_events_repeat_programme_event_fk',
					'from_column'    => 'event',
					'ref_table'      => 'jos_emundus_setup_events',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_events_repeat_programme_programme_fk',
					'from_column'    => 'programme',
					'ref_table'      => 'jos_emundus_setup_programmes',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_events_repeat_program', $columns, $foreign_keys);
			//

			// Create table jos_emundus_setup_event_slots
			$columns      = [
				[
					'name' => 'event',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name'    => 'parent_slot_id',
					'type'    => 'INT',
					'null'    => 0,
					'default' => 0
				],
				[
					'name' => 'start_date',
					'type' => 'DATETIME',
					'null' => 0
				],
				[
					'name' => 'end_date',
					'type' => 'DATETIME',
					'null' => 0
				],
				[
					'name' => 'room',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name' => 'slot_capacity',
					'type' => 'INT',
					'null' => 1
				],
				[
					'name' => 'more_infos',
					'type' => 'TEXT',
					'null' => 1
				],
				[
					'name'   => 'link',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name'   => 'teams_id',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_event_slots_event_fk',
					'from_column'    => 'event',
					'ref_table'      => 'jos_emundus_setup_events',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_event_slots_room_fk',
					'from_column'    => 'room',
					'ref_table'      => 'data_location_rooms',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_event_slots', $columns, $foreign_keys);
			//

			// Create table jos_emundus_setup_slot_users
			$columns      = [
				[
					'name' => 'slot',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'user',
					'type' => 'INT',
					'null' => 0
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_slot_users_slot_fk',
					'from_column'    => 'slot',
					'ref_table'      => 'jos_emundus_setup_event_slots',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_slot_users_user_fk',
					'from_column'    => 'user',
					'ref_table'      => 'jos_users',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				]
			];
			EmundusHelperUpdate::createTable('jos_emundus_setup_slot_users', $columns, $foreign_keys);
			//

			// Create table jos_emundus_setup_availabilities
			$columns      = [
				[
					'name' => 'slot',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'event',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'start_date',
					'type' => 'DATETIME',
					'null' => 0
				],
				[
					'name' => 'end_date',
					'type' => 'DATETIME',
					'null' => 0
				],
				[
					'name'    => 'capacity',
					'type'    => 'INT',
					'null'    => 0,
					'default' => 1
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_availabilities_slot_fk',
					'from_column'    => 'slot',
					'ref_table'      => 'jos_emundus_setup_event_slots',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_availabilities_event_fk',
					'from_column'    => 'event',
					'ref_table'      => 'jos_emundus_setup_events',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				]
			];
			$comment      = 'This table is used to store all availabilities for a given slot';
			EmundusHelperUpdate::createTable('jos_emundus_setup_availabilities', $columns, $foreign_keys, $comment);

			$columns      = [
				[
					'name' => 'availability',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'event',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'slot',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'user',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name' => 'ccid',
					'type' => 'INT',
					'null' => 0
				],
				[
					'name'   => 'fnum',
					'type'   => 'VARCHAR',
					'length' => 28,
					'null'   => 0
				],
				[
					'name'   => 'link',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				],
				[
					'name'   => 'teams_id',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null'   => 1
				]
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_registrants_availability_fk',
					'from_column'    => 'availability',
					'ref_table'      => 'jos_emundus_setup_availabilities',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				],
				[
					'name'           => 'jos_emundus_registrants_slot_fk',
					'from_column'    => 'slot',
					'ref_table'      => 'jos_emundus_setup_event_slots',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				],
				[
					'name'           => 'jos_emundus_registrants_event_fk',
					'from_column'    => 'event',
					'ref_table'      => 'jos_emundus_setup_events',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				],
				[
					'name'           => 'jos_emundus_registrants_user_fk',
					'from_column'    => 'user',
					'ref_table'      => 'jos_users',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_registrants_ccid_fk',
					'from_column'    => 'ccid',
					'ref_table'      => 'jos_emundus_campaign_candidature',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_registrants_fnum_fk',
					'from_column'    => 'fnum',
					'ref_table'      => 'jos_emundus_campaign_candidature',
					'ref_column'     => 'fnum',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$comment      = 'This table is used to store all registrants for a given slot';
			EmundusHelperUpdate::createTable('jos_emundus_registrants', $columns, $foreign_keys, $comment);
			//

			// Create table jos_emundus_setup_events_notifications
			$columns      = [
				[
					'name' => 'event',
					'type' => 'INT',
				],
				[
					'name'    => 'applicant_notify',
					'type'    => 'TINYINT',
					'default' => 1
				],
				[
					'name' => 'applicant_notify_email',
					'null' => 1,
					'type' => 'INT'
				],
				[
					'name'    => 'applicant_recall',
					'type'    => 'TINYINT',
					'default' => 1
				],
				[
					'name'    => 'applicant_recall_frequency',
					'type'    => 'INT',
					'null'    => 1,
					'default' => 7
				],
				[
					'name' => 'applicant_recall_email',
					'null' => 1,
					'type' => 'INT'
				],
				[
					'name'    => 'manager_recall',
					'type'    => 'TINYINT',
					'default' => 0
				],
				[
					'name' => 'manager_recall_frequency',
					'null' => 1,
					'type' => 'INT'
				],
				[
					'name' => 'manager_recall_email',
					'null' => 1,
					'type' => 'INT'
				],
				[
					'name'    => 'users_recall',
					'type'    => 'TINYINT',
					'default' => 0
				],
				[
					'name' => 'users_recall_frequency',
					'null' => 1,
					'type' => 'INT'
				],
				[
					'name' => 'users_recall_email',
					'null' => 1,
					'type' => 'INT'
				],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_events_notifications_applicant_notify_email_fk',
					'from_column'    => 'applicant_notify_email',
					'ref_table'      => 'jos_emundus_setup_emails',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				],
				[
					'name'           => 'jos_emundus_setup_events_notifications_applicant_recall_email_fk',
					'from_column'    => 'applicant_recall_email',
					'ref_table'      => 'jos_emundus_setup_emails',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				],
				[
					'name'           => 'jos_emundus_setup_events_notifications_manager_recall_email_fk',
					'from_column'    => 'manager_recall_email',
					'ref_table'      => 'jos_emundus_setup_emails',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				],
				[
					'name'           => 'jos_emundus_setup_events_notifications_users_recall_email_fk',
					'from_column'    => 'users_recall_email',
					'ref_table'      => 'jos_emundus_setup_emails',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => false
				]
			];
			$comment      = 'This table is used to setup all notifications for a given event';
			EmundusHelperUpdate::createTable('jos_emundus_setup_events_notifications', $columns, $foreign_keys, $comment);
			//

			$query->clear()
				->select('*')
				->from('#__emundus_setup_config')
				->where('namekey = ' . $this->db->quote('onboarding_lists'));
			$this->db->setQuery($query);
			$onboarding_lists = $this->db->loadObject();

			if (!empty($onboarding_lists))
			{
				$onboarding_value = json_decode($onboarding_lists->value, true);

				if (empty($onboarding_value['events']))
				{
					$onboarding_value['events'] = [
						'title' => 'COM_EMUNDUS_ONBOARD_EVENTS',
						'tabs'  => [
							[
								'controller' => 'events',
								'getter'     => 'getevents',
								'title'      => 'COM_EMUNDUS_ONBOARD_EVENTS',
								'key'        => 'events',
								'actions'    => [
									[
										'action'     => 'index.php?option=com_emundus&view=events&layout=add',
										'controller' => 'events',
										'label'      => 'COM_EMUNDUS_ONBOARD_ADD_EVENT',
										'name'       => 'add',
										'type'       => 'redirect',
									],
									[
										'action'     => 'index.php?option=com_emundus&view=events&layout=add&event=%id%',
										'controller' => 'events',
										'label'      => 'COM_EMUNDUS_ONBOARD_MODIFY',
										'name'       => 'edit',
										'type'       => 'redirect',
									],
									[
										'action'     => 'duplicateevent',
										'controller' => 'events',
										'label'      => 'COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE',
										'name'       => 'duplicate'
									],
									[
										'action'     => 'deleteevent',
										'controller' => 'events',
										'label'      => 'COM_EMUNDUS_ACTIONS_DELETE',
										'name'       => 'delete',
										'confirm'    => 'COM_EMUNDUS_ONBOARD_EVENT_DELETE_CONFIRM',
									],
								],
								'filters'    => [
									[
										'label'      => 'COM_EMUNDUS_ONBOARD_EVENT_USERS',
										'getter'     => 'getslotsusers',
										'controller' => 'events',
										'key'        => 'users',
										'values'     => null
									],
									[
										'label'      => 'COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS',
										'getter'     => 'getlocations',
										'controller' => 'events',
										'key'        => 'location',
										'values'     => null
									]
								]
							],
							[
								'controller' => 'events',
								'getter'     => 'getalllocations',
								'title'      => 'COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS',
								'key'        => 'locations',
								'actions'    => [
									[
										'action'     => 'index.php?option=com_emundus&view=events&layout=addlocation',
										'controller' => 'events',
										'label'      => 'COM_EMUNDUS_ONBOARD_ADD_LOCATION',
										'name'       => 'add',
										'type'       => 'redirect',
									],
									[
										'action'     => 'index.php?option=com_emundus&view=events&layout=addlocation&location=%id%',
										'controller' => 'events',
										'label'      => 'COM_EMUNDUS_ONBOARD_MODIFY',
										'name'       => 'edit',
										'type'       => 'redirect',
									],
									[
										'action'     => 'deletelocation',
										'controller' => 'events',
										'label'      => 'COM_EMUNDUS_ACTIONS_DELETE',
										'name'       => 'delete',
										'confirm'    => 'COM_EMUNDUS_ONBOARD_LOCATION_DELETE_CONFIRM',
										'showon'     => [
											'key'      => 'nb_events',
											'operator' => '<',
											'value'    => 1
										]
									],
								],
								'filters'    => []
							]
						]
					];

					$onboarding_lists->value = json_encode($onboarding_value);
					$this->db->updateObject('#__emundus_setup_config', $onboarding_lists, 'namekey');

					// Create events menu
					$datas       = [
						'menutype'     => 'onboardingmenu',
						'title'        => 'Évènements',
						'alias'        => 'events',
						'link'         => 'index.php?option=com_emundus&view=events',
						'type'         => 'component',
						'component_id' => ComponentHelper::getComponent('com_emundus')->id,
						'params'       => [
							'menu_image_css' => 'event'
						]
					];
					$events_menu = EmundusHelperUpdate::addJoomlaMenu($datas, 1, 0);

					if ($events_menu['status'])
					{
						EmundusHelperUpdate::insertFalangTranslation(1, $events_menu['id'], 'menu', 'title', 'Events');

						// Create add event menu
						$datas          = [
							'menutype'     => 'onboardingmenu',
							'title'        => 'Ajouter un évènement',
							'alias'        => 'add',
							'path'         => 'events/add',
							'link'         => 'index.php?option=com_emundus&view=events&layout=add',
							'type'         => 'component',
							'component_id' => ComponentHelper::getComponent('com_emundus')->id,
							'params'       => [
								'menu_show' => 0
							]
						];
						$add_event_menu = EmundusHelperUpdate::addJoomlaMenu($datas, $events_menu['id'], 0);

						if ($add_event_menu['status'])
						{
							EmundusHelperUpdate::insertFalangTranslation(1, $add_event_menu['id'], 'menu', 'title', 'Add an event');
						}
						else
						{
							EmundusHelperUpdate::displayMessage('Error creating add event menu', 'error');
						}

						// Create add location menu
						$datas             = [
							'menutype'     => 'onboardingmenu',
							'title'        => 'Ajouter un lieu',
							'alias'        => 'add-location',
							'path'         => 'events/add-location',
							'link'         => 'index.php?option=com_emundus&view=events&layout=addlocation',
							'type'         => 'component',
							'component_id' => ComponentHelper::getComponent('com_emundus')->id,
							'params'       => [
								'menu_show' => 0
							]
						];
						$add_location_menu = EmundusHelperUpdate::addJoomlaMenu($datas, $events_menu['id'], 0);

						if ($add_location_menu['status'])
						{
							EmundusHelperUpdate::insertFalangTranslation(1, $add_location_menu['id'], 'menu', 'title', 'Add a location');
						}
						else
						{
							EmundusHelperUpdate::displayMessage('Error creating add location menu', 'error');
						}
					}
					else
					{
						EmundusHelperUpdate::displayMessage('Error creating events menu', 'error');
					}
					//
				}

				// Teams integration
				EmundusHelperUpdate::addColumn('jos_emundus_setup_sync', 'name', 'VARCHAR', 255, 0);
				EmundusHelperUpdate::addColumn('jos_emundus_setup_sync', 'description', 'TEXT');
				EmundusHelperUpdate::addColumn('jos_emundus_setup_sync', 'enabled', 'TINYINT', 3, 0, 0);
				EmundusHelperUpdate::addColumn('jos_emundus_setup_sync', 'icon', 'VARCHAR', 255);

				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__emundus_setup_sync'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('teams'));
				$this->db->setQuery($query);
				$teams = $this->db->loadResult();

				if (empty($teams))
				{
					$teams = [
						'type'        => 'teams',
						'name'        => 'Microsoft Teams',
						'description' => 'Génerez des liens de réunion Teams pour vos évènements.',
						'params'      => '{}',
						'config'      => '{}',
						'icon'        => 'teams.svg',
						'enabled'     => 0,
						'published'   => 1,
					];
					$teams = (object) $teams;
					$this->db->insertObject('jos_emundus_setup_sync', $teams);
				}
				//

				// Install booking element
				EmundusHelperUpdate::installExtension('plg_fabrik_element_booking', 'booking', null, 'plugin', 1, 'fabrik_element');
				//

				// Emails
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__emundus_setup_emails'))
					->where($this->db->quoteName('lbl') . ' LIKE ' . $this->db->quote('booking_confirmation'));
				$this->db->setQuery($query);
				$booking_confirmation_email = $this->db->loadResult();

				if (empty($booking_confirmation_email))
				{
					$booking_confirmation_email = [
						'lbl'        => 'booking_confirmation',
						'subject'    => 'Confirmation de votre réservation / Confirmation of your booking',
						'emailfrom'  => '',
						'message'    => file_get_contents(JPATH_ROOT . '/administrator/components/com_emundus/scripts/html/booking/booking_confirmation.html'),
						'type'       => 1,
						'published'  => 1,
						'email_tmpl' => 1,
						'category'   => 'Système',
						'button'     => ''
					];
					$booking_confirmation_email = (object) $booking_confirmation_email;
					$this->db->insertObject('#__emundus_setup_emails', $booking_confirmation_email);
				}
				//

				// Install emails emundus plugin
				EmundusHelperUpdate::installExtension('plg_emundus_emails', 'emails', null, 'plugin', 1, 'emundus');
				//

				// Microsoft Dynamics integration
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__emundus_setup_sync'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('microsoft_dynamics'));
				$this->db->setQuery($query);
				$microsoft_dynamics = $this->db->loadResult();

				if (empty($microsoft_dynamics))
				{
					$microsoft_dynamics = [
						'type'        => 'microsoft_dynamics',
						'name'        => 'Microsoft Dynamics 365',
						'description' => 'Transformez vos dossiers de candidatures en opportunités dans votre CRM.',
						'params'      => '{}',
						'config'      => '{}',
						'icon'        => 'dynamics_365.svg',
						'enabled'     => 0,
						'published'   => 1,
					];
					$microsoft_dynamics = (object) $microsoft_dynamics;
					$this->db->insertObject('jos_emundus_setup_sync', $microsoft_dynamics);
				}
				//
			}


			$tags_to_create = [
				'BOOKING_START_DATE' => [
					1 => 'Booking start date',
					2 => 'Date de début de réservation'
				],
				'BOOKING_END_DATE'   => [
					1 => 'Booking end date',
					2 => 'Date de fin de réservation'
				],
				'BOOKING_END_HOUR'   => [
					1 => 'Booking end hour',
					2 => 'Heure de fin de réservation'
				],
				'BOOKING_LOCATION'   => [
					1 => 'Booking location',
					2 => 'Lieu de réservation'
				],
				'BOOKING_LOCATION_LINK'   => [
					1 => 'Booking location link',
					2 => 'Lien du lieu de réservation'
				],
				'BOOKING_LOCATION_DESCRIPTION'   => [
					1 => 'Booking location description',
					2 => 'Description du lieu de réservation'
				]
			];

			foreach ($tags_to_create as $key => $tag)
			{
				$query->clear()
					->select('id')
					->from('#__emundus_setup_tags')
					->where('tag = ' . $this->db->quote($key));
				$this->db->setQuery($query);
				$exist = $this->db->loadResult();

				if (empty($exist))
				{
					$insert_object = [
						'date_time'   => date('Y-m-d H:i:s'),
						'tag'         => $key,
						'request'     => '[' . $key . ']',
						'description' => $tag[1],
						'published'   => 0
					];
					$insert_object = (object) $insert_object;
					$this->db->insertObject('#__emundus_setup_tags', $insert_object);
					$tag_id = $this->db->insertid();

					EmundusHelperUpdate::insertFalangTranslation(1, $tag_id, 'emundus_setup_tags', 'description', $tag[1]);
					EmundusHelperUpdate::insertFalangTranslation(2, $tag_id, 'emundus_setup_tags', 'description', $tag[2]);
				}
			}

			$query->clear()
				->select('id, published')
				->from('#__emundus_setup_tags')
				->where($this->db->quoteName('tag') . ' = ' . $this->db->quote('TRAINING_PROGRAMME'));
			$this->db->setQuery($query);
			$training_programme_tag = $this->db->loadObject();

			if (!empty($training_programme_tag) && $training_programme_tag->published == 0)
			{
				$query->clear()
					->update($this->db->quoteName('#__emundus_setup_tags'))
					->set($this->db->quoteName('published') . ' = 1')
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($training_programme_tag->id));
				$this->db->setQuery($query);
				$this->db->execute();

				EmundusHelperUpdate::insertFalangTranslation(1, $training_programme_tag->id, 'emundus_setup_tags', 'description', 'Program\'s name');
				EmundusHelperUpdate::insertFalangTranslation(2, $training_programme_tag->id, 'emundus_setup_tags', 'description', 'Nom du programme');
			}

			EmundusHelperUpdate::addColumn('jos_emundus_setup_events_notifications', 'ics_event_name', 'VARCHAR', 255, 1);
			/* END PREPARE BOOKING */

			$query->clear()
				->select('link')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('coordinatormenu'))
				->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('email-history'));
			$this->db->setQuery($query);
			$email_history_link = $this->db->loadResult();

			if(!empty($email_history_link)) {
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
				->update($this->db->quoteName('#__emundus_setup_emails'))
				->set($this->db->quoteName('name') . ' = ' . $this->db->quote(''))
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote('null'))
				->orWhere($this->db->quoteName('name') . ' IS NULL');
			$this->db->setQuery($query);
			$this->db->execute();

			// Update template variables
			$styleFile = JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml';
			if(file_exists($styleFile))
			{
				$variables   = Yaml::parse(file_get_contents($styleFile));
				foreach ($variables as $key1 => $variable) {
					if(is_array($variable)) {
						foreach ($variable as $key2 => $subVariable) {
							if(str_contains($subVariable, 'px')) {
								// Convert to REM
								$rem = (int) $subVariable / 16;

								EmundusHelperUpdate::updateYamlVariable($key1, $rem . 'rem', $styleFile, $key2);
							}
						}
					}
				}
			}

			$query->clear()
				->update($this->db->quoteName('jos_emundus_setup_actions'))
				->set($this->db->quoteName('status') . ' = 0')
				->where($this->db->quoteName('name') . ' IN (' . implode(',',$this->db->quote(['interview','decision','admission'])) . ')');
			$this->db->setQuery($query);
			$this->db->execute();

			EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_FABRIK_SESSION_EXPIRED', 'Your session on the page has expired', 'override', 0, '', '', 'en-GB');

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