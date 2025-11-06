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

		try
		{
			$this->initShareFilters();

			$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_sign_requests', 'ordered', 'TINYINT', 3, 1, 0);

			$query = $this->db->createQuery();
			$this->initCrcFeature($query);

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
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
			[
				'name'            => 'jos_emundus_addresses_country_fk',
				'from_column'     => 'country',
				'ref_table'       => 'data_country',
				'ref_column'      => 'id',
				'update_cascade'  => true,
				'delete_set_null' => true
			]
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
			[
				'name'            => 'jos_emundus_organizations_address_fk',
				'from_column'     => 'address',
				'ref_table'       => 'jos_emundus_addresses',
				'ref_column'      => 'id',
				'update_cascade'  => true,
				'delete_set_null' => true
			],
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
}