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
use Tchooz\Enums\Workflow\WorkflowStepDateRelativeToEnum;

class Release2_9_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];
		$tasks = [];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_date', 'TINYINT(1) NOT NULL DEFAULT 0');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_to', 'VARCHAR(55) NOT NULL DEFAULT "' . WorkflowStepDateRelativeToEnum::STATUS->value . '"');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_start_date_value', 'INT(11) NOT NULL DEFAULT 0');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_start_date_unit', 'VARCHAR(55) NOT NULL DEFAULT \'day\'');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_end_date_value', 'INT(11) NOT NULL DEFAULT 0');
			$tasks[] = $result['status'];

			$result = EmundusHelperUpdate::addColumn('#__emundus_setup_campaigns_step_dates', 'relative_end_date_unit', 'VARCHAR(55) NOT NULL DEFAULT \'day\'');
			$tasks[] = $result['status'];

			$columns = [
				[
					'name' => 'date_time',
					'type' => 'DATETIME',
					'null' => 0,
				],
				[
					'name' => 'status',
					'type' => 'INT(11) NOT NULL',
					'null' => 0,
				],
				[
					'name' => 'fnum',
					'type' => 'VARCHAR(28) NOT NULL',
					'null' => 0,
				]
			];
			$result = EmundusHelperUpdate::createTable('jos_emundus_fnums_status_date', $columns);
			$tasks[] = $result['status'];

			$manifest = '{"name":"plg_fabrik_list_emailhistory","type":"plugin","creationDate":"2025-08-20","author":"eMundus","copyright":"Copyright (C) 2005-2025 Fabrikar - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"https:\/\/www.emundus.fr","version":"2.9.0","description":"PLG_LIST_EMAIL_HISTORY_DATA_DESCRIPTION","group":"","changelogurl":"","filename":"emailhistory"}';
			$tasks[] = EmundusHelperUpdate::installExtension('plg_fabrik_list_emailhistory', 'emailhistory', $manifest, 'plugin', 1, 'fabrik_list');

			$manifest = '{"name":"plg_fabrik_list_hideanonymdata","type":"plugin","creationDate":"2025-08-20","author":"eMundus","copyright":"Copyright (C) 2005-2025 Fabrikar - All rights reserved.","authorEmail":"dev@emundus.fr","authorUrl":"https:\/\/www.emundus.fr","version":"2.9.0","description":"PLG_LIST_HIDE_ANONYM_DATA_DESCRIPTION","group":"","changelogurl":"","filename":"hideanonymdata"}';
			$tasks[] = EmundusHelperUpdate::installExtension('plg_fabrik_list_hideanonymdata', 'hideanonymdata', $manifest, 'plugin', 1, 'fabrik_list');

			// associate plugins to fabrik list on #__messages list
			$query->clear()
				->select('id, params')
				->from($this->db->quoteName('#__fabrik_lists'))
				->where($this->db->quoteName('db_table_name') . ' = ' . $this->db->quote('jos_messages'));
			$this->db->setQuery($query);
			$lists = $this->db->loadAssocList();

			foreach ($lists as $list)
			{
				// add emailhistory and hideanonymdata plugin to the list
				$params = json_decode($list['params'], true);

				if (!isset($params['plugins'])) {
					$params['plugins'] = [];
				}
				if (!isset($params['plugin_description'])) {
					$params['plugin_description'] = [];
				}
				if (!isset($params['plugin_state'])) {
					$params['plugin_state'] = [];
				}

				if (!in_array('hideanonymdata', $params['plugins'])) {
					$params['plugins'][] = 'hideanonymdata';
					$params['plugin_description'][] = 'Masquer les informations des comptes anonymes dans le tableau des messages';
					$params['plugin_state'][] = 1;

					// set default parameters for hideanonymdata plugin
					if (!isset($params['user_id_field'])) {
						$query->clear()
							->select('jfe.id')
							->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
							->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
							->leftJoin($this->db->quoteName('#__fabrik_lists', 'jfl') . ' ON jfl.form_id = jffg.form_id')
							->where('jfl.id = ' . (int) $list['id'])
							->andWhere($this->db->quoteName('jfe.name') . ' = ' . $this->db->quote('user_id_to'));

						$this->db->setQuery($query);
						$user_id_field = $this->db->loadResult();

						if ($user_id_field) {
							$params['user_id_field'] = $user_id_field;
						}
					}

					if (!isset($params['fields_to_hide'])) {
						$params['fields_to_hide'] = 'user_id_to,message';
					}
				}

				if (!in_array('emailhistory', $params['plugins']))
				{
					$params['plugins'][] = 'emailhistory';
					$params['plugin_description'][] = 'Masquer les messages qui ne sont pas des emails';
					$params['plugin_state'][] = 1;
				}

				$list['params'] = json_encode($params);
				$query->clear()
					->update($this->db->quoteName('#__fabrik_lists'))
					->set('params = ' . $this->db->quote($list['params']))
					->where('id = ' . (int) $list['id']);


				$this->db->setQuery($query);
				$tasks[] = $this->db->execute();
			}

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}