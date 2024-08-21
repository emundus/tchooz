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
				]
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_setup_workflows_programs', $columns, $foreign_keys);
			$tasks[] = $created['status'];

			$columns = [
				['name' => 'workflow_id', 'type' => 'INT', 'null' => 0],
				['name' => 'label', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
				['name' => 'type', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0], // 'applicant' or 'evaluator'
				['name' => 'profile_id', 'type' => 'INT', 'null' => 1],
				['name' => 'form_id', 'type' => 'INT', 'null' => 1],
				['name' => 'multiple', 'type' => 'TINYINT', 'null' => 1],
				['name' => 'start_date', 'type' => 'DATETIME', 'null' => 0],
				['name' => 'end_date', 'type' => 'DATETIME', 'null' => 0],
				['name' => 'output_status', 'type' => 'INT', 'null' => 0],
				['name' => 'output_status', 'state' => 'INT', 'null' => 0], // 1: published, 0: archived, -1: deleted
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
				['name' => 'profile_id', 'type' => 'INT', 'null' => 0],
			];
			$foreign_keys = [
				[
					'name' => 'jos_emundus_setup_workflows_steps_roles_steps_id_fk',
					'from_column' => 'step_id',
					'ref_table' => 'jos_emundus_setup_workflows_steps',
					'ref_column' => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name' => 'jos_emundus_setup_workflows_steps_roles_profiles_id_fk',
					'from_column' => 'profile_id',
					'ref_table' => 'jos_emundus_setup_profiles',
					'ref_column' => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
			];
			$created = EmundusHelperUpdate::createTable('jos_emundus_setup_workflows_steps_roles', $columns, $foreign_keys);
			$tasks[] = $created['status'];

			if (!in_array(false, $tasks)) {
				$installed = true;
			}
		}

		return $installed;
	}
}