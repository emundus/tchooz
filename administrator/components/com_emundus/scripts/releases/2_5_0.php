<?php

namespace scripts;

use EmundusHelperUpdate;

class Release2_5_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			// Create table jos_emundus_contacts
			$columns      = [
				[
					'name'   => 'lastname',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null' => 0
				],
				[
					'name'   => 'firstname',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null' => 0
				],
				[
					'name'   => 'email',
					'type'   => 'VARCHAR',
					'length' => 255,
					'null' => 0
				],
				[
					'name'   => 'phone_1',
					'type'   => 'VARCHAR',
					'length' => 100,
					'null' => 1
				],
				[
					'name'   => 'user_id',
					'type'   => 'INT',
					'length' => 11,
					'null' => 1
				],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_contacts_user_id_fk',
					'from_column'    => 'user_id',
					'ref_table'      => 'jos_emundus_users',
					'ref_column'     => 'user_id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$tasks[] = EmundusHelperUpdate::createTable('jos_emundus_contacts', $columns, $foreign_keys)['status'];

			// Move parameters from module to component for filters
			$query->clear()
				->select('id, params, published')
				->from($this->db->quoteName('#__modules'))
				->where($this->db->quoteName('module').' = '.$this->db->quote('mod_emundus_filters'))
				->where($this->db->quoteName('published').' = 1');
			$this->db->setQuery($query);
			$modules = $this->db->loadObjectList();

			foreach ($modules as $module) {
				// Get menus associated with the module
				$query->clear()
					->select('m.id, m.params')
					->from($this->db->quoteName('#__modules_menu','mm'))
					->leftJoin($this->db->quoteName('#__menu','m').' ON '.$this->db->quoteName('m.id').' = '.$this->db->quoteName('mm.menuid'))
					->where($this->db->quoteName('mm.moduleid').' = '.$this->db->quote($module->id));
				$this->db->setQuery($query);
				$menus = $this->db->loadObjectList();

				$params = json_decode($module->params, true);

				foreach ($menus as $menu) {
					// Transfer the parameters to the component
					$menu_params = json_decode($menu->params, true);
					unset($menu_params['em_use_module_for_filters']);
					$menu_params['filter_on_fnums'] = $params['filter_on_fnums'];
					$menu_params['element_id'] = $params['element_id'];
					$menu_params['default_filter_element_ids'] = $params['default_filter_element_ids'];
					$menu_params['filter_status'] = $params['filter_status'];
					$menu_params['filter_status_order'] = $params['filter_status_order'];
					$menu_params['filter_campaign'] = $params['filter_campaign'];
					$menu_params['filter_campaigns_order'] = $params['filter_campaigns_order'];
					$menu_params['filter_programs'] = $params['filter_programs'];
					$menu_params['filter_programs_order'] = $params['filter_programs_order'];
					$menu_params['filter_years'] = $params['filter_years'];
					$menu_params['filter_years_order'] = $params['filter_years_order'];
					$menu_params['filter_tags'] = $params['filter_tags'];
					$menu_params['filter_tags_order'] = $params['filter_tags_order'];
					$menu_params['filter_published'] = $params['filter_published'];
					$menu_params['filter_published_order'] = $params['filter_published_order'];
					$menu_params['filter_groups'] = $params['filter_groups'];
					$menu_params['filter_groups_order'] = $params['filter_groups_order'];
					$menu_params['filter_users'] = $params['filter_users'];
					$menu_params['filter_users_order'] = $params['filter_users_order'];
					$menu_params['filter_attachments'] = $params['filter_attachments'];
					$menu_params['filter_attachments_order'] = $params['filter_attachments_order'];
					$menu_params['filter_steps'] = $params['filter_steps'];
					$menu_params['filter_steps_order'] = $params['filter_steps_order'];
					$menu_params['filter_evaluated'] = $params['filter_evaluated'];
					$menu_params['filter_evaluated_order'] = $params['filter_evaluated_order'];
					$menu_params['more_filter_elements'] = $params['more_filter_elements'];

					$menu->params = json_encode($menu_params);
					$tasks[] = $this->db->updateObject('#__menu', $menu, 'id');
				}

				// Unpublish the module
				$module->published = 0;
				$tasks[] = $this->db->updateObject('#__modules', $module, 'id');
			}
			//

			$result['status'] = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();

			return $result;
		}

		return $result;
	}
}