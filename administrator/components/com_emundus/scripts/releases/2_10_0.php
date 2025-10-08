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

class Release2_10_0Installer extends ReleaseInstaller
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
			$this->initUserTypeFeature($query);

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
	
	private function initUserTypeFeature($query)
	{
		$columns      = [
			[
				'name' => 'created_at',
				'type' => 'datetime',
			],
			[
				'name'   => 'label',
				'type'   => 'varchar',
				'length' => 255,
				'null'   => 0,
			],
			[
				'name'    => 'published',
				'type'    => 'tinyint',
				'length'  => 3,
				'null'    => 0,
				'default' => 1,
			],
			[
				'name'   => 'created_by',
				'type'   => 'int',
				'length' => 11,
				'null'   => 1,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'data_user_category_created_by_user_fk',
				'from_column'    => 'created_by',
				'ref_table'      => 'jos_users',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$this->tasks[]      = EmundusHelperUpdate::createTable('data_user_category', $columns, $foreign_keys, 'List of user categories')['status'];

		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_users', 'user_category', 'int', 11)['status'];

		$query->clear()
			->select('form_id')
			->from($this->db->quoteName('#__emundus_setup_formlist'))
			->where($this->db->quoteName('type') . ' LIKE ' . $this->db->quote('profile'));
		$this->db->setQuery($query);
		$profile_form = $this->db->loadResult();

		if (!empty($profile_form))
		{
			$query->clear()
				->select('group_id')
				->from($this->db->quoteName('#__fabrik_formgroup'))
				->where($this->db->quoteName('form_id') . ' = ' . (int) $profile_form)
				->order('ordering');
			$this->db->setQuery($query);
			$groups = $this->db->loadColumn();

			if (!empty($groups))
			{
				$last_group_id = end($groups);

				$user_category_element = [
					'name'     => 'user_category',
					'group_id' => $last_group_id,
					'plugin'   => 'databasejoin',
					'label'    => 'COM_EMUNDUS_USERS_USER_CATEGORY'
				];
				$params            = [
					'alias'                   => 'user_category',
					'join_db_name'            => 'data_user_category',
					'join_key_column'         => 'id',
					'join_val_column'         => 'label',
					'join_val_column_concat'  => '',
					'database_join_where_sql' => 'WHERE {thistable}.published = 1',
				];
				$this->tasks[]           = EmundusHelperUpdate::addFabrikElement($user_category_element, $params, false);

				$this->tasks[] = EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_USER_CATEGORY', 'User Category', 'override', 0, null, null, 'en-GB');
				$this->tasks[] = EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_USERS_USER_CATEGORY', 'Catégorie d\'utilisateur');
			}
		}

		$columns      = [
			[
				'name' => 'campaign_id',
				'type' => 'int',
				'length' => 11,
				'null'   => 0,
			],
			[
				'name'   => 'user_category_id',
				'type'   => 'int',
				'length' => 11,
				'null'   => 0,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'jos_emundus_setup_campaigns_user_category_user_fk',
				'from_column'    => 'user_category_id',
				'ref_table'      => 'data_user_category',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'jos_emundus_setup_campaigns_user_category_campaign_fk',
				'from_column'    => 'campaign_id',
				'ref_table'      => 'jos_emundus_setup_campaigns',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$this->tasks[]      = EmundusHelperUpdate::createTable('jos_emundus_setup_campaigns_user_category', $columns, $foreign_keys, 'Associate user category with campaign')['status'];

		$this->tasks[] = EmundusHelperUpdate::installExtension('plg_system_emundus_user_category', 'emundus_user_category', null, 'plugin', 0, 'system');

		// Add type column to jos_emundus_setup_form_rules_js_conditions
		$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_setup_form_rules_js_conditions', 'type', 'varchar', 50, 0, 'form')['status'];

		// Add data_user_category to jos_emundus_datas_library
		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__emundus_datas_library'))
			->where($this->db->quoteName('database_name') . ' = ' . $this->db->quote('data_user_category'));
		$this->db->setQuery($query);
		$data_user_category_id = $this->db->loadResult();

		if(empty($data_user_category_id))
		{
			$insert = (object) [
				'database_name' => 'data_user_category',
				'join_column_id' => 'id',
				'join_column_val' => 'label',
				'label' => 'Catégorie d\'utilisateur',
				'description' => 'Catégories d\'utilisateurs pour la gestion des utilisateurs',
				'created' => (new \DateTime())->format('Y-m-d H:i:s'),
				'translation' => 0
			];

			$this->tasks[] = $this->db->insertObject('#__emundus_datas_library', $insert);
		}

		// Create menu to associate multiple users to a category
		$query->clear()
			->select('id')
			->from($this->db->quoteName('#__menu'))
			->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=users&format=raw&layout=affectusercategory&Itemid={Itemid}'));
		$this->db->setQuery($query);
		$associate_user_category_menu = $this->db->loadResult();

		if(empty($associate_user_category_menu))
		{
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'))
				->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('action-users'));
			$this->db->setQuery($query);
			$parent_menu_id = $this->db->loadResult();

			$datas       = [
				'menutype'     => 'actions-users',
				'title'        => 'Associer une catégorie utilisateur',
				'alias'        => 'associate-user-category',
				'link'         => 'index.php?option=com_emundus&view=users&format=raw&layout=affectusercategory&Itemid={Itemid}',
				'type'         => 'url',
				'component_id' => 0,
				'note'         => '12|u|1|affectusercategory'
			];
			$associate_user_category_menu = EmundusHelperUpdate::addJoomlaMenu($datas, $parent_menu_id, 0);

			if ($this->tasks[] = $associate_user_category_menu['status'])
			{
				EmundusHelperUpdate::insertFalangTranslation(1, $associate_user_category_menu['id'], 'menu', 'title', 'Associate User Category');
			}
		}
	}
}