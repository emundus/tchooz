<?php
/**
 * @version     2.0.0
 * @package     com_emundus
 * @copyright   Copyright (C) 2024. Tous droits réservés.
 * @license     GNU General Public License version 2 ou version ultérieure ; Voir LICENSE.txt
 * @author      emundus <jeremy.legendre@emundus.fr> - http://www.emundus.fr
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Factory;

class EmundusModelAdministratorWorkflow extends JModelList
{
	private $db = null;

	public function __construct($config = [], \Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null)
	{

		$this->db = Factory::getContainer()->get('DatabaseDriver');

		parent::__construct($config, $factory);
	}

	public function install() {
		$installed = false;

		require_once (JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php');

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		/**
		 * Tables that must exists
		 * jos_emundus_setup_workflows
		 * jos_emundus_setup_workflows_programs
		 * jos_emundus_setup_workflows_steps
		 * jos_emundus_setup_workflows_steps_entry_status
		 * jos_emundus_setup_workflows_steps_roles
		 */

		$columns = [
			['name' => 'label', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
			['name' => 'published', 'type' => 'TINYINT', 'null' => 1, 'default' => 1]
		];
		$response = EmundusHelperUpdate::createTable('jos_emundus_setup_workflows', $columns);

		$tasks = [];
		if ($response['status']) {
			$columns = [
				['name' => 'workflow_id', 'type' => 'INT', 'null' => 0],
				['name' => 'program_id', 'type' => 'INT', 'null' => 0],
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_worflows_programs_workflows_id_fk',
					'from_column'    => 'workflow_id',
					'ref_table'      => 'jos_emundus_setup_workflows',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_worflows_programs_prg_id_fk',
					'from_column'    => 'program_id',
					'ref_table'      => 'jos_emundus_setup_programmes',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_setup_workflows_programs', $columns, $foreign_keys);
			$tasks[] = $created['status'];

			$columns = [
				['name' => 'workflow_id', 'type' => 'INT', 'null' => 0],
				['name' => 'label', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
				['name' => 'type', 'type' => 'INT', 'length' => 11, 'null' => 0], // 1 is 'applicant' 2 is 'evaluator'
				['name' => 'sub_type', 'type' => 'INT', 'length' => 11, 'null' => 1, 'default' => 0],
				['name' => 'profile_id', 'type' => 'INT', 'null' => 1],
				['name' => 'form_id', 'type' => 'INT', 'null' => 1],
				['name' => 'multiple', 'type' => 'TINYINT', 'null' => 1],
				['name' => 'output_status', 'type' => 'INT', 'null' => 0],
				['name' => 'state', 'type' => 'INT', 'null' => 0, 'default' => 1], // 1: published, 0: archived, -1: deleted
			];
			$foreign_keys = [
				[
					'name'           => 'jos_emundus_setup_workflows_steps_workflows_id_fk',
					'from_column'    => 'workflow_id',
					'ref_table'      => 'jos_emundus_setup_workflows',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_workflows_steps_profiles_id_fk',
					'from_column'    => 'profile_id',
					'ref_table'      => 'jos_emundus_setup_profiles',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_workflows_steps_jff_id_fk',
					'from_column'    => 'form_id',
					'ref_table'      => 'jos_fabrik_forms',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'jos_emundus_setup_workflows_steps_output_status_step_fk',
					'from_column'    => 'output_status',
					'ref_table'      => 'jos_emundus_setup_status',
					'ref_column'     => 'step',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_setup_workflows_steps', $columns, $foreign_keys);
			$tasks[] = $created['status'];

			$columns = [
				['name' => 'step_id', 'type' => 'INT', 'null' => 0],
				['name' => 'status', 'type' => 'INT', 'null' => 0],
			];
			$foreign_keys = [
				[
					'name' => 'jos_emundus_setup_workflows_step_id_fk',
					'from_column' => 'step_id',
					'ref_table' => 'jos_emundus_setup_workflows_steps',
					'ref_column' => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name' => 'jos_emundus_setup_workflow_steps_status_fk',
					'from_column' => 'status',
					'ref_table' => 'jos_emundus_setup_status',
					'ref_column' => 'step',
					'update_cascade' => true,
					'delete_cascade' => true
				],
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_setup_workflows_steps_entry_status', $columns, $foreign_keys);
			$tasks[] = $created['status'];

			$columns = [
				['name' => 'step_id', 'type' => 'INT', 'null' => 0],
				['name' => 'group_id', 'type' => 'INT', 'null' => 0],
			];
			$foreign_keys = [
				[
					'name' => 'jos_emundus_setup_workflows_steps_groups_steps_id_fk',
					'from_column' => 'step_id',
					'ref_table' => 'jos_emundus_setup_workflows_steps',
					'ref_column' => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name' => 'jos_emundus_setup_workflows_steps_groups_group_id_fk',
					'from_column' => 'group_id',
					'ref_table' => 'jos_emundus_setup_groups',
					'ref_column' => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_setup_workflows_steps_groups', $columns, $foreign_keys);
			$tasks[] = $created['status'];

			$columns = [
				['name' => 'parent_id', 'type' => 'INT', 'null' => 0, 'default' => 0],
				['name' => 'label', 'type' => 'VARCHAR(255)', 'null' => 0],
				['name' => 'action_id', 'type' => 'INT', 'null' => 1],
				['name' => 'published', 'type' => 'TINYINT', 'null' => 1],
				['name' => 'system', 'type' => 'TINYINT', 'null' => 1, 'default' => 0],
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_setup_step_types', $columns);
			if ($created) {
				// add first rows to the table
				$rows = [
					[1, 0, 'COM_EMUNDUS_WORKFLOW_STEP_TYPE_APPLICANT', 0, 1, 1],
					[2, 0, 'COM_EMUNDUS_WORKFLOW_STEP_TYPE_EVALUATOR', 0, 1, 1],
				];

				foreach ($rows as $row) {
					$query->clear()
						->insert('#__emundus_setup_step_types');
					$query->columns(['id', 'parent_id', 'label', 'action_id', 'published', $db->quoteName('system')]);
					$query->values(implode(',', $db->quote($row)));

					$db->setQuery($query);
					$db->execute();
				}
			}
			$tasks[] = $created['status'];

			$columns = [
				['name' => 'campaign_id', 'type' => 'INT', 'null' => 0],
				['name' => 'step_id', 'type' => 'INT', 'null' => 0],
				['name' => 'start_date', 'type' => 'DATETIME', 'null' => 1],
				['name' => 'end_date', 'type' => 'DATETIME', 'null' => 1],
				['name' => 'infinite', 'type' => 'TINYINT', 'null' => 0, 'default' => 0],
			];
			$foreign_keys = [
				[
					'name' => 'jos_emundus_setup_campaigns_step_dates_jesc__fk',
					'from_column' => 'campaign_id',
					'ref_table' => 'jos_emundus_setup_campaigns',
					'ref_column' => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name' => 'jos_emundus_setup_campaigns_step_dates_step_id_fk',
					'from_column' => 'step_id',
					'ref_table' => 'jos_emundus_setup_workflows_steps',
					'ref_column' => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_setup_campaigns_step_dates', $columns, $foreign_keys);
			$tasks[] = $created['status'];


			$query->clear()
				->select('extension_id')
				->from('#__extensions')
				->where('type = ' . $db->quote('component'))
				->where('element = ' . $db->quote('com_emundus'))
				->where('name = ' . $db->quote('com_emundus'));

			$db->setQuery($query);
			$component_id = $db->loadResult();

			$result = EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'onboardingmenu',
				'title' => 'Workflows',
				'link' => 'index.php?option=com_emundus&view=workflows',
				'path' => 'workflows',
				'alias' => 'workflows',
				'type' => 'component',
				'component_id' => $component_id,
				'access' => 7
			]);

			if ($result['id']) {
				EmundusHelperUpdate::addJoomlaMenu([
					'menutype' => 'onboardingmenu',
					'title' => 'Modifier un workflow',
					'link' => 'index.php?option=com_emundus&view=workflows&layout=edit',
					'alias' => 'edit',
					'path' => 'workflows/edit',
					'type' => 'component',
					'component_id' => $component_id,
					'access' => 7
				], $result['id']);

				EmundusHelperUpdate::addJoomlaMenu([
					'menutype' => 'onboardingmenu',
					'title' => 'Ajouter workflow',
					'link' => 'index.php?option=com_emundus&view=workflows&layout=add',
					'alias' => 'add',
					'path' => 'workflows/add',
					'type' => 'component',
					'component_id' => $component_id,
					'access' => 7
				], $result['id']);
			}

			$query = $this->db->getQuery(true);
			$query->clear()
				->select('id')
				->from('#__menu')
				->where('alias = ' . $this->db->quote('campaigns'))
				->andWhere('menutype = ' . $this->db->quote('onboardingmenu'));
			$this->db->setQuery($query);
			$parent_id = $this->db->loadResult();
			EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'onboardingmenu',
				'title' => 'Éditer un programme',
				'link' => 'index.php?option=com_emundus&view=programme&layout=edit',
				'alias' => 'edit-program',
				'path' => 'campaigns/edit-program',
				'type' => 'component',
				'component_id' => $component_id,
				'access' => 7
			], $parent_id);

			$manifest = '{"name":"Fabrik Form - eMundus Phase \u00e9valuation","type":"plugin","creationDate":"September 2024","author":"J\u00e9r\u00e9my L","copyright":"Copyright (C) 2024 eMundus.fr - All rights reserved.","authorEmail":"jeremy.legendre@emundus.fr","authorUrl":"www.emundus.fr","version":"2.0.0","description":"Gestion d\'acc\u00e8s et des donn\u00e9es soumises pour les phases d\'\u00e9valuation","group":"","filename":"emundusstepevaluation"}';
			EmundusHelperUpdate::installExtension('Fabrik Form - eMundus Phase évaluation', 'emundusstepevaluation', $manifest, 'plugin', 1, 'fabrik_form');
			EmundusHelperUpdate::enableEmundusPlugins('emundusstepevaluation');

			if (!in_array(false, $tasks)) {
				$installed = true;
			}
		}

		return $installed;
	}
}