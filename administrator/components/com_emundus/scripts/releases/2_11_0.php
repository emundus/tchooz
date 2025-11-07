<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusColumnTypeEnum;
use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;
use EmundusTableColumn;
use EmundusTableForeignKey;
use EmundusTableForeignKeyOnEnum;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Table\Table;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionsAndorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\Task\TaskStatusEnum;


class Release2_11_0Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$this->initShareFilters();
			$this->initAutomationBuilder();

			$this->initStats();

			$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_sign_requests', 'ordered', 'TINYINT', 3, 1, 0);

			$this->initCrcFeature($query);

			$this->initChoicesFeature($query);

			$this->fixSetupActionsKeys();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function initAutomationBuilder(): void
	{
		$query = $this->db->createQuery();

		// Add automation module to setup_config
		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_setup_config'))
			->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('automation'));
		$this->db->setQuery($query);
		$config = $this->db->loadObject();

		if (empty($config->namekey))
		{
			$insert        = (object) [
				'namekey' => 'automation',
				'value'   => json_encode([
					'enabled'   => 0,
					'displayed' => 0,
					'params'    => new \stdClass()
				])
			];
			$this->tasks[] = $this->db->insertObject('#__emundus_setup_config', $insert);
		}

		$automationResult = EmundusHelperUpdate::createTable('jos_emundus_automation',
			[
				new EmundusTableColumn('name', EmundusColumnTypeEnum::VARCHAR, 255, false),
				new EmundusTableColumn('description', EmundusColumnTypeEnum::TEXT, null, true),
				new EmundusTableColumn('event_id', EmundusColumnTypeEnum::INT, 11, false),
				new EmundusTableColumn('published', EmundusColumnTypeEnum::TINYINT, 1, false, 1),
			],
			[
				new EmundusTableForeignKey('fk_automation_event', 'event_id', 'jos_emundus_plugin_events', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $automationResult['status'];

		$actionResult = EmundusHelperUpdate::createTable('jos_emundus_action',
			[
				new EmundusTableColumn('name', EmundusColumnTypeEnum::VARCHAR, 100, false),
				new EmundusTableColumn('automation_id', EmundusColumnTypeEnum::INT, 11, false),
				new EmundusTableColumn('params', EmundusColumnTypeEnum::JSON, null, true),
			],
			[
				new EmundusTableForeignKey('fk_action_automation', 'automation_id', 'jos_emundus_automation', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $actionResult['status'];

		$groupConditionTable = EmundusHelperUpdate::createTable('jos_emundus_group_condition',
			[
				new EmundusTableColumn('parent_id', EmundusColumnTypeEnum::INT, 11, true, 'NULL'),
				new EmundusTableColumn('operator', EmundusColumnTypeEnum::VARCHAR, 10, false, ConditionsAndorEnum::AND->value),
			],
			[
				new EmundusTableForeignKey('fk_group_condition_parent', 'parent_id', 'jos_emundus_group_condition', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $groupConditionTable['status'];

		$conditionTable = EmundusHelperUpdate::createTable('jos_emundus_condition',
			[
				new EmundusTableColumn('group_id', EmundusColumnTypeEnum::INT, 11, true),
				new EmundusTableColumn('target', EmundusColumnTypeEnum::VARCHAR, 255, false),
				new EmundusTableColumn('type', EmundusColumnTypeEnum::VARCHAR, 255, false, ConditionTargetTypeEnum::FORMDATA->value),
				new EmundusTableColumn('operator', EmundusColumnTypeEnum::VARCHAR, 50, false, ConditionOperatorEnum::EQUALS->value),
				new EmundusTableColumn('value', EmundusColumnTypeEnum::VARCHAR, 255, true, 'NULL'),
			],
			[
				new EmundusTableForeignKey('fk_condition_group_condition', 'group_id', 'jos_emundus_group_condition', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $conditionTable['status'];

		$automationConditionsTable = EmundusHelperUpdate::createTable('jos_emundus_automation_condition',
			[
				new EmundusTableColumn('automation_id', EmundusColumnTypeEnum::INT, 11, false),
				new EmundusTableColumn('condition_id', EmundusColumnTypeEnum::INT, 11, false),
			],
			[
				new EmundusTableForeignKey('fk_automation_conditions_automation', 'automation_id', 'jos_emundus_automation', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
				new EmundusTableForeignKey('fk_automation_conditions_condition', 'condition_id', 'jos_emundus_condition', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $automationConditionsTable['status'];

		$actionConditionsTable = EmundusHelperUpdate::createTable('jos_emundus_action_condition',
			[
				new EmundusTableColumn('action_id', EmundusColumnTypeEnum::INT, 11, false),
				new EmundusTableColumn('condition_id', EmundusColumnTypeEnum::INT, 11, false),
			],
			[
				new EmundusTableForeignKey('fk_action_conditions_action', 'action_id', 'jos_emundus_action', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
				new EmundusTableForeignKey('fk_action_conditions_condition', 'condition_id', 'jos_emundus_condition', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $actionConditionsTable['status'];


		$targetsTable = EmundusHelperUpdate::createTable('jos_emundus_automation_target',
			[
				new EmundusTableColumn('action_id', EmundusColumnTypeEnum::INT, 11, false),
				new EmundusTableColumn('type', EmundusColumnTypeEnum::VARCHAR, 50, false, TargetTypeEnum::FILE->value),
				new EmundusTableColumn('predefinition', EmundusColumnTypeEnum::VARCHAR, 255, true),
			],
			[
				new EmundusTableForeignKey('fk_automation_target_action', 'action_id', 'jos_emundus_action', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $targetsTable['status'];

		$targetsConditionsTable = EmundusHelperUpdate::createTable('jos_emundus_automation_target_condition',
			[
				new EmundusTableColumn('target_id', EmundusColumnTypeEnum::INT, 11, false),
				new EmundusTableColumn('condition_id', EmundusColumnTypeEnum::INT, 11, false),
			],
			[
				new EmundusTableForeignKey('fk_target_conditions_target', 'target_id', 'jos_emundus_automation_target', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
				new EmundusTableForeignKey('fk_target_conditions_condition', 'condition_id', 'jos_emundus_condition', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $targetsConditionsTable['status'];

		$menuResult = EmundusHelperUpdate::addJoomlaMenu([
			'menutype' => 'onboardingmenu',
			'title' => 'Automatisations',
			'alias' => 'emundus-automations',
			'link' => 'index.php?option=com_emundus&view=automation',
			'published' => 1,
			'type' => 'component',
			'component_id' => ComponentHelper::getComponent('com_emundus')->id,
			'access' => 8,
			'params' => [
				'menu_show' => 1,
				'menu_image_css' => 'automation'
			],
		]);
		$this->tasks[] = $menuResult['status'];

		if (!empty($menuResult['id'])) {
			// add sub menu edit automation
			$editmenu = EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'onboardingmenu',
				'title' => 'Automatisations',
				'alias' => 'automation-edit',
				'access' => 8,
				'link' => 'index.php?option=com_emundus&view=automation&layout=edit',
				'published' => 1,
				'type' => 'component',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params' => [
					'menu_show' => 1,
					'menu_image_css' => 'automation'
				],
			], $menuResult['id']);
			$this->tasks[] = $editmenu['status'];
		}

		$actionId = EmundusHelperUpdate::createNewAction('automation', ['multi' => 0, 'c' => 1, 'r' => 1, 'u' => 1, 'd' => 1], 'COM_EMUNDUS_AUTOMATION', 'COM_EMUNDUS_AUTOMATION_DESC');
		$this->tasks[] = !empty($actionId);

		EmundusHelperUpdate::addColumn('jos_emundus_plugin_events', 'available', EmundusColumnTypeEnum::TINYINT->value, 1, false, 0);
		$eventNames = [
			'onAfterStatusChange',
			'onAfterDeleteFile',
			'onAfterAddUserToGroup',
			'onAfterAddUserProfile',
			'onAfterCampaignCandidature',
			'onAfterCampaignCreate',
			'onAfterCampaignUnpublish',
			'onAfterProgramCreate',
			'onBeforeLoad',
			'onAfterProcess',
			'onAfterTagAdd',
			'onAfterCampaignPublish',
			'onAfterCopyApplication',
			'onAfterSubmitEvaluation',
			'onAfterRender',
			'onAfterSubmitFile',
			'onBeforeApplicantEnterApplication',
			'onUserLogin'
		];

		$query->clear()
			->update($this->db->quoteName('jos_emundus_plugin_events'))
			->set($this->db->quoteName('available') . ' = 1')
			->set($this->db->quoteName('description') . ' = ' . $this->db->quote(''))
			->where($this->db->quoteName('label') . ' IN (' . implode(',', $this->db->quote($eventNames)) . ')');

		$this->db->setQuery($query);
		$this->tasks[] = $this->db->execute();

		$query->clear()
			->update($this->db->quoteName('jos_emundus_plugin_events'))
			->set($this->db->quoteName('category') . ' = ' . $this->db->quote('Campaign'))
			->where($this->db->quoteName('category') . ' = ' . $this->db->quote('Camapign'));

		$this->db->setQuery($query);
		$this->tasks[] = $this->db->execute();

		$createTaskTable = EmundusHelperUpdate::createTable('jos_emundus_task',
			[
				new EmundusTableColumn('status', EmundusColumnTypeEnum::VARCHAR, 50, false, TaskStatusEnum::PENDING->value),
				new EmundusTableColumn('action_id', EmundusColumnTypeEnum::INT, 11, false),
				new EmundusTableColumn('user_id', EmundusColumnTypeEnum::INT, 11, true, 'NULL'),
				new EmundusTableColumn('metadata', EmundusColumnTypeEnum::JSON, null, true),
				new EmundusTableColumn('created_at', EmundusColumnTypeEnum::DATETIME, null, false),
				new EmundusTableColumn('updated_at', EmundusColumnTypeEnum::DATETIME, null, false),
				new EmundusTableColumn('started_at', EmundusColumnTypeEnum::DATETIME, null, true, 'NULL'),
				new EmundusTableColumn('finished_at', EmundusColumnTypeEnum::DATETIME, null, true, 'NULL'),
				new EmundusTableColumn('attempts', EmundusColumnTypeEnum::INT, 11, false, 0),
			],
			[
				new EmundusTableForeignKey('fk_task_user', 'user_id', 'jos_users', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::SET_NULL),
				new EmundusTableForeignKey('fk_task_action', 'action_id', 'jos_emundus_action', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::CASCADE),
			]
		);
		$this->tasks[] = $createTaskTable['status'];

		$this->tasks[] =  EmundusHelperUpdate::installExtension('plg_task_executeemundusactions', 'executeemundusactions', null, 'plugin', 1, 'task');
		$this->tasks[] =  EmundusHelperUpdate::addSchedulerTask('plg_task_executeemundusactions_task_get', 'Exécuter les actions Emundus', ["rule-type" => "interval-minutes", "interval-minutes" => "1", 'exec-day' => Date::getInstance()->day, "exec-time" => "12:00"], ["type" => "interval", "exp"=>"PT1M"], 1, 1);

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('rapport-d-activite-2'));
		$this->db->setQuery($query);
		$reportMenuId = $this->db->loadResult();

		if (!empty($reportMenuId))
		{
			$menuResult = EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'adminmenu',
				'title' => 'Historique d\'exécution des actions asynchrones',
				'alias' => 'emundus-tasks-history',
				'link' => 'index.php?option=com_emundus&view=task&layout=history',
				'published' => 1,
				'type' => 'component',
				'access' => 8,
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params' => [
					'menu_show' => 1,
					'menu_image_css' => 'schedule'
				],
			], $reportMenuId);
			$this->tasks[] = $menuResult['status'];
		} else {
			$this->tasks[] = false;
		}

		$params = ComponentHelper::getParams('com_scheduler');
		$params->set('lazy_scheduler.enabled', false);

		$table = Table::getInstance('extension');
		$table->load(['element' => 'com_scheduler', 'type' => 'component']);
		$table->params = $params->toString();
		$table->store();
	}

	private function initShareFilters(): void
	{
		$columns      = [
			[
				'name'   => 'filter_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			],
			[
				'name'   => 'user_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 1,
			],
			[
				'name'   => 'group_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 1,
			],
			[
				'name'   => 'shared_by',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			],
			[
				'name' => 'shared_date',
				'type' => 'DATETIME',
				'null' => 0,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'emundus_filters_assoc_filter_fk',
				'from_column'    => 'filter_id',
				'ref_table'      => '#__emundus_filters',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'emundus_filters_assoc_user_fk',
				'from_column'    => 'user_id',
				'ref_table'      => '#__users',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'emundus_filters_assoc_group_fk',
				'from_column'    => 'group_id',
				'ref_table'      => '#__emundus_setup_groups',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true

			],
			[
				'name'           => 'emundus_filters_assoc_shared_by_fk',
				'from_column'    => 'shared_by',
				'ref_table'      => '#__users',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => false
			]
		];
		$created      = EmundusHelperUpdate::createTable('jos_emundus_filters_assoc', $columns, $foreign_keys);
		$this->tasks[]      = $created['status'];

		$columns      = [
			[
				'name'   => 'filter_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			],
			[
				'name'   => 'user_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'emundus_filters_favorites_filter_fk',
				'from_column'    => 'filter_id',
				'ref_table'      => '#__emundus_filters',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'emundus_filters_favorites_user_fk',
				'from_column'    => 'user_id',
				'ref_table'      => '#__users',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$created      = EmundusHelperUpdate::createTable('jos_emundus_filters_favorites', $columns, $foreign_keys);
		$this->tasks[]      = $created['status'];

		$created = EmundusHelperUpdate::createTable('jos_emundus_filters_user_default_filter',
			[
				[
					'name'   => 'filter_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0,
				],
				[
					'name'   => 'user_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0,
				]
			],
			[
				[
					'name'           => 'emundus_filters_user_default_filter_filter_fk',
					'from_column'    => 'filter_id',
					'ref_table'      => '#__emundus_filters',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'emundus_filters_user_default_filter_user_fk',
					'from_column'    => 'user_id',
					'ref_table'      => '#__users',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			]);

		$this->tasks[] = $created['status'];

		$this->tasks[] = !empty(EmundusHelperUpdate::createNewAction('share_filters', ['multi' => 0, 'c' => 1, 'r' => 1, 'u' => 1, 'd' => 0], 'COM_EMUNDUS_SHARE_FILTERS'));
	}

	private function initCrcFeature($query): void
	{
		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_contacts', 'birthdate', 'DATE')['status'];
		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_contacts', 'gender', 'varchar', 10)['status'];
		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_contacts', 'status', 'varchar', 255, 0)['status'];
		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_contacts', 'published', 'tinyint', 3, 0, 1)['status'];
		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_contacts', 'fonction', 'varchar', 255)['status'];
		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_contacts', 'service', 'varchar', 255)['status'];
		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_contacts', 'profile_picture', 'varchar', 255)['status'];

		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_contacts_address', 'address_id', 'int', 11)['status'];
		// Set null all old columns to avoid issues with foreign keys
		$this->db->setQuery("ALTER TABLE `#__emundus_contacts_address` MODIFY `address1` VARCHAR(255) NULL;")->execute();
		$this->db->setQuery("ALTER TABLE `#__emundus_contacts_address` MODIFY `zip` VARCHAR(20) NULL;")->execute();
		$this->db->setQuery("ALTER TABLE `#__emundus_contacts_address` MODIFY `city` VARCHAR(255) NULL;")->execute();
		$this->db->setQuery("ALTER TABLE `#__emundus_contacts_address` MODIFY `country` INT(11) NULL;")->execute();

		$columns       = [
			['name' => 'locality', 'type' => 'VARCHAR', 'length' => 255, 'null' => 1],
			['name' => 'region', 'type' => 'VARCHAR', 'length' => 255, 'null' => 1],
			['name' => 'street_address', 'type' => 'VARCHAR', 'length' => 255, 'null' => 1],
			['name' => 'extended_address', 'type' => 'VARCHAR', 'length' => 255, 'null' => 1],
			['name' => 'postal_code', 'type' => 'VARCHAR', 'length' => 10, 'null' => 1],
			['name' => 'description', 'type' => 'TEXT', 'null' => 1],
			['name' => 'country', 'type' => 'INT', 'length' => 11, 'null' => 1],
		];
		$foreign_keys  = [
			new EmundusTableForeignKey('jos_emundus_addresses_country_fk', 'country', 'data_country', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::SET_NULL),
		];
		$this->tasks[] = EmundusHelperUpdate::createTable('jos_emundus_addresses', $columns, $foreign_keys, 'List of addresses')['status'];
		$this->migrateOldAddressesToNewStructure($query);

		$constraintName = 'jos_emundus_contacts_address_address_id_fk';
		$exists = $this->db->setQuery("
		    SELECT CONSTRAINT_NAME
		    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
		    WHERE TABLE_SCHEMA = DATABASE()
		      AND TABLE_NAME = '{$this->db->replacePrefix('#__emundus_contacts_address')}'
		      AND CONSTRAINT_NAME = '{$constraintName}'
		")->loadResult();

		// Add foreign key only if it doesn't already exist
		if (!$exists)
		{
			$this->tasks[] = $this->db->setQuery("
		        ALTER TABLE `#__emundus_contacts_address`
		        ADD CONSTRAINT `{$constraintName}`
		            FOREIGN KEY (`address_id`)
		            REFERENCES `#__emundus_addresses` (`id`)
		            ON DELETE SET NULL
		            ON UPDATE CASCADE;
		    ")->execute();
		}


		$columns       = [
			['name' => 'name', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
			['name' => 'description', 'type' => 'TEXT', 'null' => 1],
			['name' => 'url_website', 'type' => 'TEXT', 'null' => 1],
			['name' => 'published', 'type' => 'TINYINT', 'length' => 3, 'default' => 1],
			['name' => 'status', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
			['name' => 'address', 'type' => 'INT', 'length' => 11, 'null' => 1],
			['name' => 'identifier_code', 'type' => 'VARCHAR', 'length' => 255, 'null' => 1],
			['name' => 'logo', 'type' => 'VARCHAR', 'length' => 255, 'null' => 1],
		];
		$foreign_keys  = [
			new EmundusTableForeignKey('jos_emundus_organizations_address_fk', 'address', 'jos_emundus_addresses', 'id', EmundusTableForeignKeyOnEnum::CASCADE, EmundusTableForeignKeyOnEnum::SET_NULL),
		];
		$this->tasks[] = EmundusHelperUpdate::createTable('jos_emundus_organizations', $columns, $foreign_keys, 'List of organizations')['status'];

		$columns       = [
			['name' => 'contact_id', 'type' => 'INT', 'length' => 11, 'null' => 0],
			['name' => 'organization_id', 'type' => 'INT', 'length' => 11, 'null' => 0],
			['name' => 'is_referent_contact', 'type' => 'TINYINT', 'length' => 1, 'default' => 0],
		];
		$foreign_keys  = [
			[
				'name'           => 'jos_emundus_contacts_organizations_contact_fk',
				'from_column'    => 'contact_id',
				'ref_table'      => 'jos_emundus_contacts',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_contacts_organizations_org_fk',
				'from_column'    => 'organization_id',
				'ref_table'      => 'jos_emundus_organizations',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$this->tasks[] = EmundusHelperUpdate::createTable('jos_emundus_contacts_organizations', $columns, $foreign_keys, 'Associations between contacts and organizations')['status'];

		$columns       = [
			['name' => 'contact_id', 'type' => 'INT', 'length' => 11, 'null' => 0],
			['name' => 'country_id', 'type' => 'INT', 'length' => 11, 'null' => 0]
		];
		$foreign_keys  = [
			[
				'name'           => 'jos_emundus_contacts_countries_contact_fk',
				'from_column'    => 'contact_id',
				'ref_table'      => 'jos_emundus_contacts',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_contacts_countries_country_fk',
				'from_column'    => 'country_id',
				'ref_table'      => 'data_country',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$this->tasks[] = EmundusHelperUpdate::createTable('jos_emundus_contacts_countries', $columns, $foreign_keys, 'Associations between contacts and countries')['status'];

		// Add CRC module to setup_config
		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_setup_config'))
			->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('crc'));
		$this->db->setQuery($query);
		$config = $this->db->loadObject();

		if (empty($config->namekey))
		{
			$insert        = (object) [
				'namekey' => 'crc',
				'value'   => json_encode([
					'enabled'   => 0,
					'displayed' => 0,
					'params'    => new \stdClass()
				])
			];
			$this->tasks[] = $this->db->insertObject('#__emundus_setup_config', $insert);
		}

		// Add CRC acl(s) (contacts and organizations management)
		$this->tasks[] = EmundusHelperUpdate::createNewAction('contact');
		$this->tasks[] = EmundusHelperUpdate::createNewAction('organization');

		// Create CRC menu item
		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
			->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('relation-client') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=crc'));
		$this->db->setQuery($query);
		$crc_menu_id = $this->db->loadResult();

		if (empty($crc_menu_id))
		{
			$data     = [
				'menutype'          => 'onboardingmenu',
				'title'             => 'Relation client',
				'alias'             => 'relation-client',
				'path'              => 'relation-client',
				'link'              => 'index.php?option=com_emundus&view=crc',
				'type'              => 'component',
				'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
				'template_style_id' => 0,
				'params'            => [
					'menu_image_css' => 'sensor_occupied',
				],
			];
			$crc_menu = EmundusHelperUpdate::addJoomlaMenu($data, 1, 0);
			EmundusHelperUpdate::insertFalangTranslation(1, $crc_menu['id'], 'menu', 'title', 'Customer Relations');

			$crc_menu_id = $crc_menu['id'];
		}

		if (!empty($crc_menu_id))
		{
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('contact-form') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=crc&layout=contactform'));
			$this->db->setQuery($query);
			$edit_contact_menu_id = $this->db->loadResult();

			if (empty($edit_contact_menu_id))
			{
				$data              = [
					'menutype'          => 'onboardingmenu',
					'title'             => 'Ajouter/Modifier un contact',
					'alias'             => 'contact-form',
					'path'              => 'contact-form',
					'link'              => 'index.php?option=com_emundus&view=crc&layout=contactform',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
				];
				$edit_contact_menu = EmundusHelperUpdate::addJoomlaMenu($data, $crc_menu_id, 0);
				EmundusHelperUpdate::insertFalangTranslation(1, $edit_contact_menu['id'], 'menu', 'title', 'Add/Edit a contact');
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' LIKE ' . $this->db->quote('onboardingmenu'))
				->andWhere($this->db->quoteName('alias') . ' LIKE ' . $this->db->quote('organization-form') . ' OR ' . $this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_emundus&view=crc&layout=organizationform'));
			$this->db->setQuery($query);
			$edit_org_menu_id = $this->db->loadResult();

			if (empty($edit_org_menu_id))
			{
				$data          = [
					'menutype'          => 'onboardingmenu',
					'title'             => 'Ajouter/Modifier une organisation',
					'alias'             => 'organization-form',
					'path'              => 'organization-form',
					'link'              => 'index.php?option=com_emundus&view=crc&layout=organizationform',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
				];
				$edit_org_menu = EmundusHelperUpdate::addJoomlaMenu($data, $crc_menu_id, 0);
				EmundusHelperUpdate::insertFalangTranslation(1, $edit_org_menu['id'], 'menu', 'title', 'Add/Edit an organization');
			}
		}
	}

	private function migrateOldAddressesToNewStructure($query): void
	{
		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_contacts_address'));
		$this->db->setQuery($query);
		$oldAddresses = $this->db->loadObjectList();

		foreach ($oldAddresses as $address)
		{
			$newAddress = (object) [
				'locality'         => $address->city,
				'region'           => $address->state,
				'street_address'   => $address->address1,
				'extended_address' => $address->address2,
				'postal_code'      => $address->zip,
				'description'      => null,
				'country'          => $address->country
			];

			if ($this->tasks[] = $this->db->insertObject('jos_emundus_addresses', $newAddress))
			{
				$newAddressId = $this->db->insertid();

				// Update the old address record with the new address ID
				$address->address_id = $newAddressId;
				$this->tasks[]       = $this->db->updateObject('#__emundus_contacts_address', $address, 'id');
			}
		}
	}

	private function initChoicesFeature($query): void
	{
		// Check if choices config exists
		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_setup_config'))
			->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('choices'));

		$this->db->setQuery($query);
		$config = $this->db->loadObject();

		if (empty($config->namekey))
		{
			$params = [
				'enabled'   => 0,
				'displayed' => 0
			];

			$insert        = [
				'namekey' => 'choices',
				'value'   => json_encode($params)
			];
			$insert        = (object) $insert;
			$this->tasks[] = $this->db->insertObject('#__emundus_setup_config', $insert);
		}
		//

		$columns       = [
			[
				'name'   => 'campaign_id',
				'type'   => 'int',
				'length' => 11,
				'null'   => 0
			],
			[
				'name'   => 'fnum',
				'type'   => 'varchar',
				'length' => 28,
				'null'   => 0
			],
			[
				'name'   => 'user_id',
				'type'   => 'int',
				'length' => 11,
				'null'   => 0
			],
			[
				'name'    => 'state',
				'type'    => 'int',
				'length'  => 11,
				'null'    => 0,
				'default' => 0
			],
			[
				'name'    => 'order',
				'type'    => 'int',
				'length'  => 11,
				'null'    => 0,
				'default' => 0
			]
		];
		$foreign_keys  = [
			[
				'name'           => 'jos_emundus_campaign_candidature_choices_campaign_id_c_fk',
				'from_column'    => 'campaign_id',
				'ref_table'      => 'jos_emundus_setup_campaigns',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_campaign_candidature_choices_fnum_cc_fk',
				'from_column'    => 'fnum',
				'ref_table'      => 'jos_emundus_campaign_candidature',
				'ref_column'     => 'fnum',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_campaign_candidature_choices_applicant_id_user_fk',
				'from_column'    => 'user_id',
				'ref_table'      => 'jos_emundus_users',
				'ref_column'     => 'user_id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$this->tasks[] = EmundusHelperUpdate::createTable('jos_emundus_campaign_candidature_choices', $columns, $foreign_keys, 'List of choices by application file')['status'];

		// Create new step type
		// Add code column to step types
		$this->tasks[] = EmundusHelperUpdate::addColumn('#__emundus_setup_step_types', 'code', 'varchar', 50)['status'];

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_setup_step_types'))
			->where($this->db->quoteName('label') . ' = ' . $this->db->quote('COM_EMUNDUS_WORKFLOW_STEP_TYPE_CHOICES'));
		$this->db->setQuery($query);
		$step = $this->db->loadResult();

		if (empty($step))
		{
			$insert        = [
				'parent_id' => 0,
				'code'      => 'choices',
				'class'     => 'purple',
				'label'     => 'COM_EMUNDUS_WORKFLOW_STEP_TYPE_CHOICES',
				'action_id' => 1,
				'published' => 0,
				'system'    => 1,
			];
			$insert        = (object) $insert;
			$this->tasks[] = $this->db->insertObject('#__emundus_setup_step_types', $insert);
		}

		// Update code if payment step type
		$query->clear()
			->update($this->db->quoteName('#__emundus_setup_step_types'))
			->set($this->db->quoteName('code') . ' = ' . $this->db->quote('payment'))
			->where($this->db->quoteName('label') . ' = ' . $this->db->quote('COM_EMUNDUS_WORKFLOW_STEP_TYPE_PAYMENT'));
		$this->db->setQuery($query);
		$this->tasks[] = $this->db->execute();
		//

		$columns       = [
			[
				'name'   => 'step_id',
				'type'   => 'int',
				'length' => 11,
				'null'   => 0
			],
			[
				'name'   => 'max',
				'type'   => 'int',
				'length' => 11,
				'null'   => 0
			],
			[
				'name'    => 'can_be_ordering',
				'type'    => 'tinyint',
				'length'  => 3,
				'null'    => 0,
				'default' => 0
			],
			[
				'name'    => 'can_be_confirmed',
				'type'    => 'tinyint',
				'length'  => 3,
				'null'    => 0,
				'default' => 0
			],
			[
				'name'    => 'form_id',
				'type'    => 'int',
				'length'  => 11,
				'null'    => 1,
				'default' => 0
			]
		];
		$foreign_keys  = [
			[
				'name'           => 'step_choices_rules_step_id_fk',
				'from_column'    => 'step_id',
				'ref_table'      => 'jos_emundus_setup_workflows_steps',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$this->tasks[] = EmundusHelperUpdate::createTable('jos_emundus_setup_workflow_step_choices_rules', $columns, $foreign_keys, 'Rules of choices steps')['status'];

		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'parent_id', 'int', 11)['status'];

		$this->tasks[] = EmundusHelperUpdate::installExtension('plg_emundus_application_choices', 'application_choices', null, 'plugin', 1, 'emundus');

		$action_id     = EmundusHelperUpdate::createNewAction('application_choices');
		$this->tasks[] = !empty($action_id);

		$this->tasks[] = EmundusHelperUpdate::installExtension('plg_fabrik_element_application_choices', 'applicationchoices', null, 'plugin', 1, 'fabrik_element');

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('application'))
			->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=application&layout=applicationchoices&format=raw'));
		$this->db->setQuery($query);
		$menu_id = $this->db->loadResult();

		if (empty($menu_id))
		{
			$datas   = [
				'title'        => 'Voeux',
				'menutype'     => 'application',
				'type'         => 'component',
				'link'         => 'index.php?option=com_emundus&view=application&layout=applicationchoices&format=raw',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'note'         => $action_id . '|r'
			];
			$menu_id = EmundusHelperUpdate::addJoomlaMenu($datas, 1, 0)['id'];
		}
		if (empty($menu_id))
		{
			$this->tasks[] = false;
		}

		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('applicantmenu'))
			->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=application_choices'));
		$this->db->setQuery($query);
		$menu_id = $this->db->loadResult();

		if (empty($menu_id))
		{
			$datas   = [
				'title'        => 'Sélection de voeux',
				'menutype'     => 'applicantmenu',
				'type'         => 'component',
				'link'         => 'index.php?option=com_emundus&view=application_choices',
				'component_id' => ComponentHelper::getComponent('com_emundus')->id,
				'params'       => [
					'menu_show' => 0
				]
			];
			$menu_id = EmundusHelperUpdate::addJoomlaMenu($datas, 1, 0)['id'];
		}
		if (empty($menu_id))
		{
			$this->tasks[] = false;
		}

		$columns       = [
			[
				'name'   => 'parent_id',
				'type'   => 'int',
				'length' => 11,
				'null'   => 0
			],
		];
		$foreign_keys  = [
			[
				'name'           => 'jos_emundus_campaign_candidature_choices_parent_id_fk',
				'from_column'    => 'parent_id',
				'ref_table'      => 'jos_emundus_campaign_candidature_choices',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$this->tasks[] = EmundusHelperUpdate::createTable('jos_emundus_campaign_candidature_choices_more', $columns, $foreign_keys, 'Complete more informations about choices')['status'];

		EmundusHelperUpdate::installExtension('plg_fabrik_form_applicationchoicesmore', 'applicationchoicesmore', null, 'plugin', 1, 'fabrik_form');

		// Create a Fabrik form
		$datas = [
			'label' => 'SETUP_CAMPAIGNS_CANDIDATURE_CHOICES_MORE',
			'intro' => 'SETUP_CAMPAIGNS_CANDIDATURE_CHOICES_MORE_INTRO',
			'submit_button_label' => 'SAVE_CONTINUE',
		];
		$params = [
			'process-jplugins'   => '2',
			'plugins'            => array("applicationchoicesmore"),
			'plugin_state'       => array("1"),
			'plugin_locations'   => array("both"),
			'plugin_events'      => array("both"),
			'plugin_description' => array(""),
		];
		$form = EmundusHelperUpdate::addFabrikForm($datas, $params);
		$this->tasks[] = $form['status'];

		EmundusHelperUpdate::insertTranslationsTag('SETUP_CAMPAIGNS_CANDIDATURE_CHOICES_MORE', 'Informations complémentaires', 'override', $form['id'], 'fabrik_forms', 'label');
		EmundusHelperUpdate::insertTranslationsTag('SETUP_CAMPAIGNS_CANDIDATURE_CHOICES_MORE', 'More informations', 'override', $form['id'], 'fabrik_forms', 'label', 'en-GB');
		EmundusHelperUpdate::insertTranslationsTag('SETUP_CAMPAIGNS_CANDIDATURE_CHOICES_MORE_INTRO', '', 'override', $form['id'], 'fabrik_forms', 'intro');
		EmundusHelperUpdate::insertTranslationsTag('SETUP_CAMPAIGNS_CANDIDATURE_CHOICES_MORE_INTRO', '', 'override', $form['id'], 'fabrik_forms', 'intro', 'en-GB');

		// Create a Fabrik list
		$list = EmundusHelperUpdate::addFabrikList([
			'label' => 'SETUP_CAMPAIGNS_CANDIDATURE_CHOICES_MORE',
			'db_table_name' => 'jos_emundus_campaign_candidature_choices_more',
			'form_id' => $form['id']
		], [
			"menu_access_only" => "1",
			// 10 = nobody, 1 = public
			"allow_view_details" => "1",
			"allow_edit_details" => "1",
			"allow_add" => "1",
			"allow_delete" => "10",
			"allow_drop" => "10"
		]);
		$this->tasks[] = $list['status'];

		// Create a fabrik group
		if ($form['status'])
		{
			$group = EmundusHelperUpdate::addFabrikGroup(['name' => 'SETUP_CAMPAIGNS_CANDIDATURE_CHOICES_MORE'], ['repeat_group_show_first' => 1], 1, true);

			$this->tasks[] = $group['status'];
			if ($group['status'])
			{
				EmundusHelperUpdate::joinFormGroup($form['id'], [$group['id']]);

				// Create a parent_id element (corresponding to parent choice)
				$elements = [
					[
						'name'                 => 'id',
						'group_id'             => $group['id'],
						'plugin'               => 'internalid',
						'label'                => 'id',
						'show_in_list_summary' => 0,
						'hidden'               => 1
					],
					[
						'name'                 => 'parent_id',
						'group_id'             => $group['id'],
						'plugin'               => 'field',
						'label'                => 'parent_id',
						'show_in_list_summary' => 0,
						'hidden'               => 1
					]
				];

				foreach ($elements as $element) {
					$result = EmundusHelperUpdate::addFabrikElement($element);
					$this->tasks[] = $result['status'];
				}
			}
		}
	}

	private function initStats(): void
	{
		$columns      = [
			[
				'name'   => 'date',
				'type'   => 'DATE',
				'null'   => 0,
			],
			[
				'name'   => 'count',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
				'default'=> 0,
			],
			[
				'name'   => 'link',
				'type'   => 'VARCHAR',
				'length' => 255,
				'null'   => 1,
			]
		];
		$created      = EmundusHelperUpdate::createTable('jos_emundus_page_analytics', $columns);
		$this->tasks[]      = $created['status'];

		// Install the plugin but do not enable it by default
		$this->tasks[] = EmundusHelperUpdate::installExtension('plg_emundus_analytics_system', 'emundus_analytics', null, 'plugin', 0, 'system');
	}

	private function fixSetupActionsKeys(): void
	{
		$queryString = 'show indexes from jos_emundus_setup_actions;';
		$this->db->setQuery($queryString);
		$indexes = $this->db->loadObjectList();
		$hasNameIndex = false;
		$hasLabelIndex = false;
		foreach ($indexes as $index)
		{
			if ($index->Key_name === 'name')
			{
				$hasNameIndex = true;
			}

			if ($index->Key_name === 'label')
			{
				$hasLabelIndex = true;
			}
		}
		if (!$hasNameIndex)
		{
			$queryString = 'create index name on jos_emundus_setup_actions (name)';
			$this->db->setQuery($queryString);
			$this->tasks[] = $this->db->execute();
		}

		if($hasLabelIndex)
		{
			// Get exiting keys on jos_emundus_setup_actions
			$queryString = 'alter table jos_emundus_setup_actions drop key label;';
			$this->db->setQuery($queryString);
			$this->tasks[] = $this->db->execute();
		}
	}
}