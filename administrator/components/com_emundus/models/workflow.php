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
use Joomla\CMS\Component\ComponentHelper;

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
		$tasks = [];

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
					[1, 0, 'COM_EMUNDUS_WORKFLOW_STEP_TYPE_APPLICANT', 1, 1, 1],
					[2, 0, 'COM_EMUNDUS_WORKFLOW_STEP_TYPE_EVALUATOR', 5, 1, 1],
				];

				foreach ($rows as $row) {
					$query->clear()
						->select('id')
						->from('#__emundus_setup_step_types')
						->where('id = ' . $db->quote($row[0]));
					$db->setQuery($query);
					$exists = $db->loadResult();

					if(!$exists)
					{
						$query->clear()
							->insert('#__emundus_setup_step_types');
						$query->columns(['id', 'parent_id', 'label', 'action_id', 'published', $db->quoteName('system')]);
						$query->values(implode(',', $db->quote($row)));

						$db->setQuery($query);
						$db->execute();
					}
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


			$component_id = ComponentHelper::getComponent('com_emundus')->id;

			$result = EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'onboardingmenu',
				'title' => 'Workflows',
				'link' => 'index.php?option=com_emundus&view=workflows',
				'path' => 'workflows',
				'alias' => 'workflows',
				'type' => 'component',
				'component_id' => $component_id,
				'access' => 7,
				'params'       => [
					'menu_image_css' => 'schema'
					]
				]
			);

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

			EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'application',
				'title' => 'Phase de gestion de dossier',
				'link' => 'index.php?option=com_emundus&view=workflows&layout=evaluatorstep',
				'alias' => 'evaluator-step',
				'path' => 'evaluator-step',
				'type' => 'component',
				'component_id' => $component_id,
				'access' => 6,
				'menu_show' => 0
			], 1);

			$fabrik_component_id = ComponentHelper::getComponent('com_fabrik')->id;

			EmundusHelperUpdate::addJoomlaMenu([
				'menutype' => 'application',
				'title' => 'Evaluation Step Form Menu',
				'link' => 'index.php?option=com_fabrik&view=form',
				'alias' => 'evaluation-step-form',
				'path' => 'evaluation-step-form',
				'type' => 'component',
				'component_id' => $fabrik_component_id,
				'access' => 6,
				'menu_show' => 0
			], 1);

			$manifest = '{"name":"Fabrik Form - eMundus Phase \u00e9valuation","type":"plugin","creationDate":"September 2024","author":"J\u00e9r\u00e9my L","copyright":"Copyright (C) 2024 eMundus.fr - All rights reserved.","authorEmail":"jeremy.legendre@emundus.fr","authorUrl":"www.emundus.fr","version":"2.0.0","description":"Gestion d\'acc\u00e8s et des donn\u00e9es soumises pour les phases d\'\u00e9valuation","group":"","filename":"emundusstepevaluation"}';
			EmundusHelperUpdate::installExtension('Fabrik Form - eMundus Phase évaluation', 'emundusstepevaluation', $manifest, 'plugin', 1, 'fabrik_form');
			EmundusHelperUpdate::enableEmundusPlugins('emundusstepevaluation');

			// modify edit redirect url for the program edition
			$query->clear()
				->select('value')
				->from('#__emundus_setup_config')
				->where($db->quoteName('namekey') . ' = ' . $db->quote('onboarding_lists'));

			$db->setQuery($query);
			$onboarding_lists = $db->loadResult();
			$onboarding_lists = json_decode($onboarding_lists, true);
			$list_to_update = false;
			if (!empty($onboarding_lists['campaigns'])) {
				foreach ($onboarding_lists['campaigns']['tabs'] as $tab_key => $tab) {
					if ($tab['key'] !== 'programs') {
						continue;
					}

					foreach($tab['actions'] as $action_key => $action) {
						if ($action['name'] == 'edit' && $action['action'] !== '/campaigns/edit-program?id=%id%') {
							$action['action'] = '/campaigns/edit-program?id=%id%';

							$onboarding_lists['campaigns']['tabs'][$tab_key]['actions'][$action_key] = $action;
							$list_to_update = true;
						}
					}
				}
			}

			if ($list_to_update) {
				$query->clear()
					->update('#__emundus_setup_config')
					->set('value = ' . $db->quote(json_encode($onboarding_lists)))
					->where($db->quoteName('namekey') . ' = ' . $db->quote('onboarding_lists'));

				$db->setQuery($query);
				$tasks[] = $db->execute();
			}
		}

		// check that there is column ordering in emundus_setup_workflows_steps
		$add_ordering = EmundusHelperUpdate::addColumn('jos_emundus_setup_workflows_steps', 'ordering', 'INT', null, 1, 0);
		$tasks[] = $add_ordering['status'];

		// add a redirect plugin to setup_programs form, to redirect correctly on save
		$query->clear()
			->select('id, params')
			->from($db->quoteName('#__fabrik_forms', 'ff'))
			->where('id = 108');

		$db->setQuery($query);
		$form = $db->loadObject();

		if (!empty($form->params)) {
			$params = json_decode($form->params, true);
			$redirect_plugin_index = array_search('redirect', $params['plugins']);

			if (empty($redirect_plugin_index)) {
				// add a redirect plugin
				$params['plugins'][] = 'redirect';

				// get the redirect plugin index
				$redirect_plugin_index = array_search('redirect', $params['plugins']);
				$params['plugin_locations'][] = 'both';
				$params['plugin_events'][] = 'both';
				$params['plugin_state'][] = '1';
				$params['plugin_description'][] = 'Redirect to current program edition';
				$params['jump_page'][$redirect_plugin_index] = '/campaigns/modifier-un-programme?rowid={jos_emundus_setup_programmes___id}&tmpl=component&iframe=1';
				$params['save_and_next'][$redirect_plugin_index] = 0;
				$params['append_jump_url'][$redirect_plugin_index] = 1;
				$params['thanks_message'][$redirect_plugin_index] = '';
				$params['save_insession'][$redirect_plugin_index] = 0;
				$params['redirect_conditon'][$redirect_plugin_index] = 1;
				$params['redirect_content_reset_form'][$redirect_plugin_index] = 1;
				$params['redirect_content_how'][$redirect_plugin_index] = 'popup';
				$params['redirect_content_popup_height'][$redirect_plugin_index] = '';
				$params['redirect_content_popup_x_offset'][$redirect_plugin_index] = '';
				$params['redirect_content_popup_y_offset'][$redirect_plugin_index] = '';
				$params['redirect_content_popup_title'][$redirect_plugin_index] = '';

				$query->clear()
					->update($db->quoteName('#__fabrik_forms'))
					->set('params = ' . $db->quote(json_encode($params)))
					->where('id = 108');

				$db->setQuery($query);
				$tasks[] = $db->execute();
			}
		}

		if (!in_array(false, $tasks)) {
			$installed = true;
		}

		return $installed;
	}


	public function migrateOldWorkflows()
	{
		// TODO
	}

	public function migrateEvaluations()
	{
		// TODO
	}
}