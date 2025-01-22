<?php
/**
 * Messages model used for the new message dialog.
 *
 * @package    Joomla
 * @subpackage eMundus
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Component\ComponentHelper;

class EmundusModelWorkflow extends JModelList
{
	private $app;

	private $db;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->app = Factory::getApplication();
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		Log::addLogger(['text_file' => 'com_emundus.workflow.php'], Log::ALL, array('com_emundus.workflow'));
	}

	public function add($label = ''): int
	{
		$new_workflow_id = 0;

		$workflow = new stdClass();
		$workflow->label = !empty($label) ? $label : Text::_('COM_EMUNDUS_WORKFLOW_NEW');
		$workflow->published = 1;

		try {
			$this->db->insertObject('#__emundus_setup_workflows', $workflow);
			$new_workflow_id = $this->db->insertid();
		} catch (Exception $e) {
			Log::add('Error while adding workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $new_workflow_id;
	}

	/**
	 * Delete workflow based on id.
	 * @param $wid
	 * @return bool true if deleted, false otherwise
	 */
	public function delete($wid, $user_id, $force = false): bool
	{
		$deleted = false;

		if (!empty($wid)) {
			$query = $this->db->createQuery();

			if ($force) {
				$query->delete('#__emundus_setup_workflows')
					->where('id = ' . $wid);

				try {
					$this->db->setQuery($query);
					$deleted = $this->db->execute();
				} catch (Exception $e) {
					Log::add('Error while deleting workflow [' . $wid . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			} else {
				$query->update('#__emundus_setup_workflows')
					->set('published = 0')
					->where('id = ' . $wid);

				try {
					$this->db->setQuery($query);
					$deleted = $this->db->execute();
				} catch (Exception $e) {
					Log::add('Error while unpublishing workflow [' . $wid . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}

			if ($deleted) {
				$action = $force ? 'deleted' : 'unpublished';
				Log::add('Workflow [' . $wid . '] ' . $action . ' by user [' . $user_id . ']', Log::INFO, 'com_emundus.workflow');
			}
		}

		return $deleted;
	}

	public function updateWorkflow($workflow, $steps, $programs): bool
	{
		$updated = false;
		$error_occurred = false;

		if (!empty($workflow['id'])) {
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_setup_workflows'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($workflow['label']))
				->set($this->db->quoteName('published') . ' = ' . $this->db->quote($workflow['published']))
				->where($this->db->quoteName('id') . ' = ' . $workflow['id']);

			try {
				$this->db->setQuery($query);
				$updated = $this->db->execute();
			} catch (Exception $e) {
				Log::add('Error while updating workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				$error_occurred = true;
			}

			if (!empty($steps)) {
				// verify step entry status are not the same on multiple steps
				$already_used_entry_status = [];
				foreach ($steps as $step) {
					foreach($step['entry_status'] as $status) {
						if (!$this->isEvaluationStep($step['type'])) {
							if (in_array($status['id'], $already_used_entry_status)) {
								$status_label = $status['label'] ?? '';

								throw new Exception(sprintf(Text::_('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS_ALREADY_USED'), $status_label));
							}
							$already_used_entry_status[] = $status['id'];
						}
					}
				}

				foreach ($steps as $step) {
					$step['workflow_id'] = $workflow['id'];

					// if the step is new, we need to unset the id
					// if it is new, the id will be under 1
					if ($step['id'] < 1) {
						unset($step['id']);
					}
					if ($this->isEvaluationStep($step['type'])) {
						$step['profile_id'] = null;
					} else {
						$step['form_id'] = null;
					}
					$step_object = (object)$step;

					try {
						if (empty($step['id'])) {
							$inserted = $this->db->insertObject('#__emundus_setup_workflows_steps', $step_object);
							if ($inserted) {
								$step['id'] = $this->db->insertid();
							}
						} else {
							$fields = ['label', 'type', 'state', 'multiple', 'output_status', 'ordering'];

							$fields_set = [];
							foreach($fields as $field) {
								if (!isset($step[$field])) {
									continue;
								}

								if ($step[$field] == '') {
									$fields_set[] = $this->db->quoteName($field) . ' = NULL';
								} else {
									$fields_set[] = $this->db->quoteName($field) . ' = ' . $this->db->quote($step[$field]);
								}
							}
							// update existing step
							$query->clear()
								->update($this->db->quoteName('#__emundus_setup_workflows_steps'))
								->set($fields_set);

							if (!$this->isEvaluationStep($step['type']))
							{
								$query->set($this->db->quoteName('profile_id') . ' = ' . $this->db->quote($step['profile_id']));
								$query->set($this->db->quoteName('form_id') . ' = NULL');
							}
							else
							{
								$query->set($this->db->quoteName('form_id') . ' = ' . $this->db->quote($step['form_id']));
								$query->set($this->db->quoteName('profile_id') . ' = NULL');
							}

							$query->where($this->db->quoteName('id') . ' = ' . $step['id']);

							$this->db->setQuery($query);
							$this->db->execute();
						}

						if (!empty($step['id'])) {
							$step['entry_status'] = array_filter($step['entry_status']);

							$query->clear()
								->delete($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status'))
								->where($this->db->quoteName('step_id') . ' = ' . $step['id']);

							$this->db->setQuery($query);
							$this->db->execute();

							if (!empty($step['entry_status'])) {
								foreach ($step['entry_status'] as $status) {
									$entry_status = new stdClass();
									$entry_status->step_id = $step['id'];
									$entry_status->status = $status['id'];

									$this->db->insertObject('#__emundus_setup_workflows_steps_entry_status', $entry_status);
								}
							}
						}
					} catch (Exception $e) {
						Log::add('Error while adding workflow step: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
						$error_occurred = true;
					}
				}
			}

			// select programs from the workflow
			$query->clear()
				->select('id, program_id')
				->from($this->db->quoteName('#__emundus_setup_workflows_programs'))
				->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id']);

			try {
				$this->db->setQuery($query);
				$workflow_programs = $this->db->loadAssocList('program_id');
			} catch (Exception $e) {
				Log::add('Error while fetching workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				$error_occurred = true;
			}

			// delete the programs that are not in the workflow
			$new_program_ids = array_map(function($program) {
				return $program['id'];
			}, $programs);
			foreach($workflow_programs as $program_id => $workflow_program) {
				if (!in_array($program_id, $new_program_ids)) {
					$query->clear()
						->delete($this->db->quoteName('#__emundus_setup_workflows_programs'))
						->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id'])
						->where($this->db->quoteName('program_id') . ' = ' . $program_id);

					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (Exception $e) {
						Log::add('Error while deleting workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
						$error_occurred = true;
					}
				}
			}

			if (!empty($new_program_ids)) {
				// insert the programs that are not in the workflow
				foreach ($new_program_ids as $new_prog_id) {
					if (!array_key_exists($new_prog_id, $workflow_programs)) {
						$programData = new stdClass();
						$programData->workflow_id = $workflow['id'];
						$programData->program_id = $new_prog_id;

						try {
							$this->db->insertObject('#__emundus_setup_workflows_programs', $programData);
						} catch (Exception $e) {
							Log::add('Error while adding workflow program: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
							$error_occurred = true;
						}
					}
				}

				// remove the programs that are linked to another workflow, if any
				// there can be only one workflow per program
				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_workflows_programs'))
					->where($this->db->quoteName('program_id') . ' IN (' . implode(',', $new_program_ids) . ')')
					->where($this->db->quoteName('workflow_id') . ' != ' . $workflow['id']);

				try {
					$this->db->setQuery($query);
					$this->db->execute();
				} catch (Exception $e) {
					Log::add('Error while deleting workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
					$error_occurred = true;
				}
			}
		}

		if ($error_occurred) {
			$updated = false;
		}

		return $updated;
	}

	public function deleteWorkflowStep($stepId): bool
	{
		$deleted = false;

		if (!empty($stepId)) {
			$query = $this->db->getQuery(true);

			$query->delete($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->where($this->db->quoteName('id') . ' = ' . $stepId);

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (Exception $e) {
				Log::add('Error while deleting workflow step: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}
		}

		return $deleted;
	}

	public function countWorkflows($ids = []): int
	{
		$nb_workflows = 0;

		$query = $this->db->createQuery();

		$query->select('COUNT(esw.id)')
			->from($this->db->quoteName('#__emundus_setup_workflows', 'esw'))
			->where($this->db->quoteName('esw.published') . ' = 1');

		if (!empty($ids)) {
			$query->where($this->db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
		}

		try {
			$this->db->setQuery($query);
			$nb_workflows = $this->db->loadResult();
		} catch (Exception $e) {
			Log::add('Error counting published workflows : ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $nb_workflows;
	}

	/**
	 * @param $ids
	 * @param $limit default is 0
	 * @param $page default is 0
	 * @param $programs default is [], allowed values are an array of program ids
	 * @param $order_by default is 'esw.id', allowed values are 'esw.id' and 'esw.label'
	 * @param $order default is 'DESC', allowed values are 'ASC' and 'DESC'
	 *
	 * @return array
	 */
	public function getWorkflows($ids = [], $limit = 0, $page = 0, $programs = [], $order_by =  'esw.id', $order = 'DESC'): array
	{
		$workflows = [];

		$query = $this->db->getQuery(true);

		$query->select('esw.*, GROUP_CONCAT(eswp.program_id) as programme_ids')
			->from($this->db->quoteName('#__emundus_setup_workflows', 'esw'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp') . ' ON eswp.workflow_id = esw.id')
			->where($this->db->quoteName('esw.published') . ' = 1');

		if (!empty($ids)) {
			$query->where($this->db->quoteName('esw.id') . ' IN (' . implode(',', $ids) . ')');
		}

		if (!empty($programs) && !in_array('all', $programs)) {
			$query->where($this->db->quoteName('eswp.program_id') . ' IN (' . implode(',', $programs) . ')');
		}

		$query->group('esw.id');

		if ($limit > 0) {
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

		$allowed_order_by = ['esw.id', 'esw.label'];
		$allowed_order = ['ASC', 'DESC'];

		if (!in_array($order_by, $allowed_order_by)) {
			$order_by = 'esw.id';
		}

		if (!in_array($order, $allowed_order)) {
			$order = 'DESC';
		}

		$query->order($this->db->quoteName($order_by) . ' ' . $order);

		try {
			$this->db->setQuery($query);
			$workflows = $this->db->loadObjectList();

			foreach ($workflows as $key => $workflow) {
				$workflows[$key]->programme_ids = !empty($workflow->programme_ids) ? explode(',', $workflow->programme_ids) : [];
			}
		} catch (Exception $e) {
			Log::add('Error while fetching workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $workflows;
	}

	/**
	 * @param int $id
	 *
	 * @return array with workflow, steps and programs
	 */
	public function getWorkflow($id): array
	{
		$workflowData = [];

		if (!empty($id)) {
			$query = $this->db->getQuery(true);

			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_workflows'))
				->where($this->db->quoteName('id') . ' = ' . $id);

			try {
				$this->db->setQuery($query);
				$workflow = $this->db->loadObject();
			} catch (Exception $e) {
				Log::add('Error while fetching workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}

			if (!empty($workflow->id)) {
				$workflowData = [
					'workflow' => $workflow,
					'steps' => [],
					'programs' => []
				];

				$query->clear()
					->select('esws.id')
					->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
					->where($this->db->quoteName('esws.workflow_id') . ' = ' . $id)
					->andWhere($this->db->quoteName('esws.state') . ' = 1')
					->order('esws.ordering ASC');

				try {
					$this->db->setQuery($query);
					$step_ids = $this->db->loadColumn();

					foreach ($step_ids as $step_id)
					{
						$workflowData['steps'][] = $this->getStepData($step_id);
					}
				} catch (Exception $e) {
					Log::add('Error while fetching workflow steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}

				$query->clear()
					->select('program_id')
					->from($this->db->quoteName('#__emundus_setup_workflows_programs'))
					->where($this->db->quoteName('workflow_id') . ' = ' . $id);

				try {
					$this->db->setQuery($query);
					$workflowData['programs'] = $this->db->loadColumn();
				} catch (Exception $e) {
					Log::add('Error while fetching workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}
		}

		return $workflowData;
	}

	/**
	 * @param int $id
	 * @param int $cid campaign id, if set, it will return the dates for the campaign and this step
	 * @return object with step data
	 */
	public function getStepData($id, $cid = null): object
	{
		$data = new stdClass();

		if (!empty($id)) {
			$query = $this->db->createQuery();
			$query->clear()
				->select('esws.*, GROUP_CONCAT(DISTINCT eswses.status) AS entry_status')
				->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'eswses') . ' ON ' . $this->db->quoteName('eswses.step_id') . ' = ' . $this->db->quoteName('esws.id'))
				->where('esws.id = ' . $id)
				->group($this->db->quoteName('esws.id'));

			try {
				$this->db->setQuery($query);
				$data = $this->db->loadObject();

				if (!empty($data->id)) {
					$data->entry_status = array_unique(explode(',', $data->entry_status));
					$data->action_id = 1;
					$data->table = '';

					if ($this->isEvaluationStep($data->type)) {
						$query->clear()
							->select('db_table_name, id')
							->from('#__fabrik_lists')
							->where('form_id = ' . $data->form_id);

						try {
							$this->db->setQuery($query);
							$table_data = $this->db->loadAssoc();

							$data->table = $table_data['db_table_name'];
							$data->table_id = $table_data['id'];
						} catch (Exception $e) {
							Log::add('Error while fetching form table name: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
						}
					}

					if (!empty($data->type)) {
						$query->clear()
							->select('action_id')
							->from($this->db->quoteName('#__emundus_setup_step_types'))
							->where('id = ' . $data->type);

						$this->db->setQuery($query);
						$data->action_id = $this->db->loadResult();
					}

					$query->clear()
						->select('program_id')
						->from($this->db->quoteName('#__emundus_setup_workflows_programs'))
						->where($this->db->quoteName('workflow_id') . ' = ' . $data->workflow_id);

					$this->db->setQuery($query);
					$data->programs = $this->db->loadColumn();

					if (!empty($cid)) {
						$query->clear()
							->select('start_date, end_date, infinite')
							->from('#__emundus_setup_campaigns_step_dates')
							->where('campaign_id = ' . $cid)
							->where('step_id = ' . $id);

						$this->db->setQuery($query);
						$dates = $this->db->loadAssoc();

						$data->start_date = $dates['start_date'];
						$data->end_date = $dates['end_date'];
						$data->infinite = $dates['infinite'];
					}
				} else {
					$data = new stdClass();
				}
			} catch (Exception $e) {
				Log::add('Error while fetching workflow steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}
		}

		return $data;
	}

	public function getEvaluatorStepsByProgram($program_id): array
	{
		$steps = [];

		if (!empty($program_id)) {
			$workflows = $this->getWorkflows([], 0, 0, [$program_id]);

			foreach ($workflows as $workflow)
			{
				$workflow_data = $this->getWorkflow($workflow->id);

				foreach ($workflow_data['steps'] as $step)
				{
					if ($this->isEvaluationStep($step->type))
					{
						$steps[] = $this->getStepData($step->id);
					}
				}
			}
		}

		return $steps;
	}

	/**
	 * @param $user_id
	 *
	 * @return array
	 */
	public function getEvaluatorSteps($user_id): array
	{
		$steps = [];

		if (!empty($user_id)) {
			$workflows = $this->getWorkflows();

			if (!class_exists('EmundusHelperAccess')) {
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
			}

			foreach ($workflows as $workflow)
			{
				$workflow_data = $this->getWorkflow($workflow->id);

				foreach ($workflow_data['steps'] as $step)
				{
					if ($this->isEvaluationStep($step->type) && (EmundusHelperAccess::asAccessAction($step->action_id, 'c', $user_id) || EmundusHelperAccess::asAccessAction($step->action_id, 'r', $user_id)))
					{
						$steps[] = $step;
					}
				}
			}
		}

		return $steps;
	}

	/*
	 * @param $step object use getStepData to get the step object
	 * @param $user_id
	 *
	 * What is a file evaluated :
	 * for each steps, if file has been evaluated by me (presence of a row with current user as evaluator in the step table), then it is evaluated
	 * if file has been evaluated by someone else and not me, and evaluation step is "multiple", then it is not evaluated
	 * if file has been evaluated by someone else, and evaluation step is not "multiple", then it is evaluated
	 *
	 * TODO: If I use only have read access on the specific step, what is the rule for the user on the file ?
	 * It is not up to him to evaluate the file, so is it always considered as evaluated ? only if another evaluator has evaluated it ?
	 *
	 * But, we will allow through an event to define if a file is evaluated or not with different rules
	 * Event Name : onGetEvaluatedRowIdsByUser
	 *
	 * @return array of file ids that are evaluated by the user for this step
	 */
	public function getEvaluatedFilesByUser($step, $user_id)
	{
		$ids = [];

		if (!empty($step->id) && !empty($user_id)) {
			if (!empty($step->table)) {
				$has_edition_access = EmundusHelperAccess::asAccessAction($step->action_id, 'c', $user_id);

				$query = $this->db->createQuery();

				if ($step->multiple && $has_edition_access) {
					$query->select('ccid')
						->from($this->db->quoteName($step->table))
						->where('evaluator = ' . $user_id)
						->andWhere('step_id = ' . $step->id);
				} else {
					$query->select('ccid')
						->from($this->db->quoteName($step->table))
						->where('step_id = ' . $step->id);
				}

				try {
					$this->db->setQuery($query);
					$ids = $this->db->loadColumn();
				} catch (Exception $e) {
					Log::add('Error while fetching files evaluated by user: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}

			PluginHelper::importPlugin('emundus');
			$dispatcher = Factory::getApplication()->getDispatcher();
			$onGetEvaluatedFilesByUser = new GenericEvent('onCallEventHandler', ['onGetEvaluatedFilesByUser', ['step' => $step, 'user_id' => (int)$user_id, 'evaluation_row_ids' => &$ids]]);
			$dispatcher->dispatch('onCallEventHandler', $onGetEvaluatedFilesByUser);
		}

		return $ids;
	}

	public function getStepEvaluationsForFile($step_id, $ccid)
	{
		$evaluations = [];

		if (!empty($step_id) && !empty($ccid)) {
			$step = $this->getStepData($step_id);

			$query = $this->db->createQuery();
			$query->select('evaluation_table.*, CONCAT(jeu.firstname, " ", jeu.lastname) as evaluator_name')
				->from($this->db->quoteName($step->table, 'evaluation_table'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'jeu') . ' ON jeu.user_id = evaluation_table.evaluator')
				->where('evaluation_table.ccid = ' . $ccid)
				->andWhere('evaluation_table.step_id = ' . $step_id);

			$this->db->setQuery($query);
			$evaluations = $this->db->loadAssocList();

			foreach($evaluations as $key => $evaluation) {
				$evaluations[$key]['url'] = '/evaluation-step-form?view=details&rowid=' . $evaluation['id'] . '&formid=' . $step->form_id . '&' . $step->table . '___ccid=' . $ccid . '&' . $step->table . '___step_id=' . $step->id . '&tmpl=component&iframe=1';
			}
		}

		return $evaluations;
	}

	/**
	 * @param $fnum
	 *
	 * @return null|object if a step is found, it returns a workflow step object, otherwise null
	 */
	public function getCurrentWorkflowStepFromFile($file_identifier, $type = 1, $column = 'fnum'): ?object
	{
		$step = null;

		if (!empty($file_identifier) && in_array($column, ['fnum', 'id'])) {
			$query = $this->db->createQuery();

			$query->select('ecc.status, esp.id as program_id, ecc.published, ecc.campaign_id')
				->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
				->where('ecc.' . $column . ' LIKE ' . $this->db->quote($file_identifier));

			$this->db->setQuery($query);
			$file_infos = $this->db->loadAssoc();

			if (!empty($file_infos['program_id']) && $file_infos['published']) {
				$query->clear()
					->select('eswp.workflow_id')
					->from($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp'))
					->leftJoin($this->db->quoteName('#__emundus_setup_workflows', 'esw') . ' ON ' . $this->db->quoteName('esw.id') . ' = ' . $this->db->quoteName('eswp.workflow_id'))
					->where('eswp.program_id = ' . $this->db->quote($file_infos['program_id']))
					->andWhere('esw.published = 1');

				try {
					$this->db->setQuery($query);
					$workflow_ids = $this->db->loadColumn();
				} catch (Exception $e) {
				}

				if (!empty($workflow_ids)) {
					$query->clear()
						->select('esws.*')
						->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
						->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'eswses') . ' ON ' . $this->db->quoteName('eswses.step_id') . ' = ' . $this->db->quoteName('esws.id'))
						->where('esws.workflow_id IN (' . implode(',', $workflow_ids) . ')')
						->andWhere('eswses.status = ' . $file_infos['status'])
						->andWhere('esws.type = ' . $this->db->quote($type))
						->andWhere('esws.state = 1');

					$this->db->setQuery($query);
					$step = $this->db->loadObject();

					if (!empty($step->id)) {
						$step->profile = $step->profile_id;
						$step->display_preliminary_documents = false;
						$step->specific_documents = [];

						$query->clear()
							->select('status')
							->from('#__emundus_setup_workflows_steps_entry_status')
							->where('step_id = ' . $step->id);

						$this->db->setQuery($query);
						$step->entry_status = $this->db->loadColumn();

						$query->clear()
							->select('start_date, end_date, infinite')
							->from('#__emundus_setup_campaigns_step_dates')
							->where('campaign_id = ' . $file_infos['campaign_id'])
							->where('step_id = ' . $step->id);

						$this->db->setQuery($query);
						$dates = $this->db->loadAssoc();

						if (!empty($dates)) {
							$step->start_date = $dates['start_date'];
							$step->end_date = $dates['end_date'];
							$step->infinite = $dates['infinite'];
						}
					} else {
						$step = null;
					}
				}
			}
		}

		return $step;
	}

	/**
	 * @return array of step types
	 */
	public function getStepTypes(): array
	{
		$types = [];

		try {
			$query = $this->db->getQuery(true);
			$query->select('*')
				->from('#__emundus_setup_step_types');

			$this->db->setQuery($query);
			$types = $this->db->loadObjectList();

			foreach ($types as $key => $type) {
				$query->clear()
					->select('DISTINCT(group_id)')
					->from('#__emundus_acl')
					->where('action_id = ' . $type->action_id)
					->andWhere('c = 1 OR r = 1');

				$this->db->setQuery($query);
				$types[$key]->group_ids = $this->db->loadColumn();
			}
		} catch (Exception $e) {
			Log::add('Error while fetching step types: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $types;
	}

	/**
	 * @param $types
	 *
	 * @return bool true if saved, false otherwise
	 */
	public function saveStepTypes($types): bool
	{
		$saved = false;

		if (!empty($types))
		{
			$existing_types    = $this->getStepTypes();
			$existing_type_ids = array_map(function ($type) { return $type->id;}, $existing_types);

			$types_ids = array_map(function ($type) {
				return $type['id'];
			}, $types);

			$new_types              = array_filter($types, function ($type) use ($existing_type_ids) {
				return !in_array($type['id'], $existing_type_ids);
			});
			$already_existing_types = array_filter($types, function ($type) use ($existing_type_ids) {
				return in_array($type['id'], $existing_type_ids);
			});
			$removed_types          = array_filter($existing_types, function ($type) use ($types_ids) {
				return !in_array($type->id, $types_ids);
			});

			$query = $this->db->getQuery(true);

			$updates = [];
			foreach ($already_existing_types as $type)
			{
				$query->clear()
					->select('action_id')
					->from('#__emundus_setup_step_types')
					->where('id = ' . $type['id']);
				try
				{
					$this->db->setQuery($query);
					$action_id = $this->db->loadResult();

					if (!empty($action_id)) {
						$query->clear()
							->update('#__emundus_setup_actions')
							->set('label = ' . $this->db->quote($type['label']))
							->where('id = ' . $action_id);
						$this->db->setQuery($query);
						$updates[] = $this->db->execute();
					}

					$query->clear()
						->update('#__emundus_setup_step_types')
						->set('label = ' . $this->db->quote($type['label']))
						->where('id = ' . $type['id']);
					$this->db->setQuery($query);
					$updates[] = $this->db->execute();
				}
				catch (Exception $e)
				{
					Log::add('Error while updating step type: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}

			$inserts = [];
			foreach ($new_types as $type)
			{
				$action         = new stdClass();
				$action->name   = strtolower(str_replace(' ', '_', $type['label']));
				$action->name   = preg_replace('/[^A-Za-z0-9_]/', '', $action->name);
				$action->name   .= uniqid();
				$action->label  = $type['label'];
				$action->multi  = 0;
				$action->c      = 1;
				$action->r      = 1;
				$action->u      = 1;
				$action->d      = 1;
				$action->status = 1;
				$action->ordering = 999;

				$query->clear()
					->insert('#__emundus_setup_actions')
					->columns('name, label, multi, c, r, u, d, status,  ordering')
					->values($this->db->quote($action->name) . ', ' . $this->db->quote($action->label) . ', ' . $action->multi . ', ' . $action->c . ', ' . $action->r . ', ' . $action->u . ', ' . $action->d . ', ' . $action->status . ', ' . $action->ordering);

				try
				{
					$this->db->setQuery($query);
					$this->db->execute();
					$action_id = $this->db->insertid();
				}
				catch (Exception $e)
				{
					$inserts[] = false;
					Log::add('Error while adding action: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}

				if (!empty($action_id)) {
					$query->clear()
						->insert('#__emundus_setup_step_types')
						->columns('label, action_id, parent_id')
						->values($this->db->quote($type['label']) . ', ' . $action_id . ', ' . $type['parent_id']);

					try
					{
						$this->db->setQuery($query);
						$inserts[] = $this->db->execute();
					}
					catch (Exception $e)
					{
						$inserts[] = false;
						Log::add('Error while adding step type: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
					}

					$eMundus_config = ComponentHelper::getParams('com_emundus');
					$all_rights_grp = $eMundus_config->get('all_rights_group', 1);

					if (!empty($all_rights_grp)) {
						EmundusHelperAccess::addAccessToGroup($action_id, $all_rights_grp, ['c' => 1, 'r' => 1, 'u' => 1, 'd' => 1]);
					}
				}
			}

			$statuses = array_merge($updates, $inserts);

			if (!empty($removed_types))
			{
				$removed_types_ids = array_map(function ($type) {
					return $type->id;
				}, $removed_types);

				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_step_types'))
					->where($this->db->quoteName('id') .' IN (' . implode(',', $removed_types_ids) . ')')
					->andWhere($this->db->quoteName('system') . ' is null or ' . $this->db->quoteName('system') . ' = 0');

				try
				{
					$this->db->setQuery($query);
					$statuses[] = $this->db->execute();
				}
				catch (Exception $e)
				{
					$statuses[] = false;
					Log::add('Error while deleting step types: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}

			$saved = !empty($statuses) && !in_array(false, $statuses);
		}

		return $saved;
	}

	public function getStepAssocActionId($step_id)
	{
		$action_id = 0;

		if (!empty($step_id)) {
			$query = $this->db->createQuery();
			$query->select('type')
				->from($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->where('id = ' . $step_id);

			$this->db->setQuery($query);
			$step_types = $this->db->loadAssoc();

			if (!empty($step_types)) {
				$query->clear()
					->select('action_id')
					->from($this->db->quoteName('#__emundus_setup_step_types'))
					->where('id = ' . $step_types['type']);

				$this->db->setQuery($query);
				$action_id = $this->db->loadResult();
			}
		}

		return $action_id;
	}

	/**
	 * @param int $campaign_id
	 *
	 * @return array
	 */
	public function getCampaignSteps($campaign_id): array
	{
		$steps = [];

		if (!empty($campaign_id)) {
			$query = $this->db->createQuery();
			$query->select('esp.id')
				->from($this->db->quoteName('#__emundus_setup_programmes', 'esp'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esp.code = esc.training')
				->where('esc.id = ' . $campaign_id);

			$this->db->setQuery($query);
			$program_id = $this->db->loadResult();

			if (!empty($program_id)) {
				$workflows = $this->getWorkflows([], 0, 0, [$program_id]);

				foreach ($workflows as $workflow)
				{
					$workflow_data = $this->getWorkflow($workflow->id);

					foreach ($workflow_data['steps'] as $step)
					{
						$query->clear()
							->select('id, start_date, end_date, infinite')
							->from('#__emundus_setup_campaigns_step_dates')
							->where('campaign_id = ' . $campaign_id)
							->where('step_id = ' . $step->id);

						$this->db->setQuery($query);
						$dates = $this->db->loadAssoc();

						if (empty($dates['id'])) {
							// create the dates
							$row = new stdClass();
							$row->campaign_id = $campaign_id;
							$row->step_id = $step->id;
							$row->start_date = null;
							$row->end_date = null;
							$row->infinite = 0;
							$this->db->insertObject('#__emundus_setup_campaigns_step_dates', $row);

							$this->db->setQuery($query);
							$dates = $this->db->loadAssoc();
						}

						$step->start_date = $dates['start_date'];
						$step->end_date = $dates['end_date'];
						$step->infinite = $dates['infinite'];

						$steps[] = $step;
					}
				}
			}
		}

		return $steps;
	}

	public function saveCampaignStepsDates($campaign_id, $steps): bool
	{
		$saved = false;

		if (!empty($campaign_id) && !empty($steps)) {
			$query = $this->db->createQuery();

			$saves = [];
			foreach ($steps as $step) {
				if (!empty($step['id'])) {
					$query->clear()
						->update('#__emundus_setup_campaigns_step_dates')
						->set('start_date = ' . $this->db->quote($step['start_date']))
						->set('end_date = ' . $this->db->quote($step['end_date']))
						->set('infinite = ' . $this->db->quote($step['infinite']))
						->where('step_id = ' . $step['id'])
						->andwhere('campaign_id = ' . $campaign_id);

					try {
						$this->db->setQuery($query);
						$saves[] = $this->db->execute();
					} catch (Exception $e) {
						Log::add('Error while updating campaign step dates: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
					}
				}
			}

			$saved = !empty($saves) && !in_array(false, $saves);
		}

		return $saved;
	}

	public function updateProgramWorkflows($program_id, $workflows): bool
	{
		$updated = false;

		if (!empty($program_id)) {
			$deleted = false;
			$query = $this->db->createQuery();

			$query->delete('#__emundus_setup_workflows_programs')
				->where('program_id = ' . $program_id);

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (Exception $e) {
				Log::add('Error while deleting program workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}


			if (!empty($workflows)) {
				$updates = [];
				foreach ($workflows as $workflow_id) {
					$workflowData = new stdClass();
					$workflowData->workflow_id = $workflow_id;
					$workflowData->program_id = $program_id;

					try {
						$updates[] = $this->db->insertObject('#__emundus_setup_workflows_programs', $workflowData);
					} catch (Exception $e) {
						Log::add('Error while adding program workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
					}
				}

				$updated = !empty($updates) && !in_array(false, $updates);
			} else {
				$updated = $deleted;
			}
		}

		return $updated;
	}

	public function updateStepState($step_id, $state): bool
	{
		$archived = false;

		if (!empty($step_id)) {
			// state must be 0 or 1
			if (!in_array($state, [0, 1])) {
				throw new Exception('Invalid state');
			}

			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->set($this->db->quoteName('state') . ' = ' . $state)
				->where($this->db->quoteName('id') . ' = ' . $step_id);

			try {
				$this->db->setQuery($query);
				$archived = $this->db->execute();
			} catch (Exception $e) {
				Log::add('Error while archiving workflow step: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}
		}

		return $archived;
	}

	/*
	 * @param $type int
	 */
	public function isEvaluationStep($type): bool
	{
		$is_evaluation_step = false;

		if (!empty($type)) {
			if ($type == 2) {
				$is_evaluation_step = true;
			} else {
				$query = $this->db->createQuery();
				$query->select('parent_id')
					->from($this->db->quoteName('#__emundus_setup_step_types'))
					->where('id = ' . $type);

				$this->db->setQuery($query);
				$parent_id = $this->db->loadResult();

				$is_evaluation_step = $parent_id == 2;
			}
		}

		return $is_evaluation_step;
	}

	public function getParentStepType($type) {
		$parent_id = 0;

		if (!empty($type)) {
			$query = $this->db->createQuery();
			$query->select('parent_id')
				->from($this->db->quoteName('#__emundus_setup_step_types'))
				->where('id = ' . $type);

			$this->db->setQuery($query);
			$parent_id = $this->db->loadResult();
		}

		return $parent_id;
	}

	public function getProgramsWorkflows()
	{
		$programs_workflows = [];

		$query = $this->db->getQuery(true);
		$query->select('GROUP_CONCAT(eswp.workflow_id) as workflow_ids, eswp.program_id')
			->from($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows', 'esw') . ' ON ' . $this->db->quoteName('esw.id') . ' = ' . $this->db->quoteName('eswp.workflow_id'))
			->where('esw.published = 1')
			->group('eswp.program_id');

		try {
			$this->db->setQuery($query);
			$programs_workflows = $this->db->loadAssocList('program_id');

			foreach ($programs_workflows as $key => $program_workflow) {
				$programs_workflows[$key] = explode(',', $program_workflow['workflow_ids']);
			}

		} catch (Exception $e) {
			Log::add('Error while fetching programs workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $programs_workflows;
	}

	/**
	 * Duplicate a workflow and all its steps (not programs)
	 *
	 * @param $workflow_id
	 *
	 * @return int
	 */
	public function duplicateWorkflow($workflow_id): int
	{
		$new_workflow_id = 0;

		if (!empty($workflow_id)) {
			$workflow_data = $this->getWorkflow($workflow_id);

			try {
				if (!empty($workflow_data)) {
					$new_workflow_id = $this->add();

					$query = $this->db->getQuery(true);

					$query->update('#__emundus_setup_workflows')
						->set('label = ' . $this->db->quote($workflow_data['workflow']->label . ' - Copie'))
						->where('id = ' . $new_workflow_id);

					$this->db->setQuery($query);
					$this->db->execute();

					$steps = $workflow_data['steps'];

					foreach($steps as $step) {
						$step->multiple = empty($step->multiple) ? 0 : $step->multiple;
						$columns = ['workflow_id', 'label', 'type', 'multiple', 'state', 'output_status'];
						$values = [$new_workflow_id, $this->db->quote($step->label), $step->type, $step->multiple, $step->state, $step->output_status];

						if ($this->isEvaluationStep($step->type)) {
							$columns[] = 'form_id';
							$values[] = $step->form_id;
						} else {
							$columns[] = 'profile_id';
							$values[] = $step->profile_id;
						}

						$query->clear()
							->insert('#__emundus_setup_workflows_steps')
							->columns($columns)
							->values(implode(',', $values));

						$this->db->setQuery($query);
						$this->db->execute();
						$new_step_id = $this->db->insertid();

						if (!empty($new_step_id)) {
							foreach($step->entry_status as $status) {
								$query->clear()
									->insert('#__emundus_setup_workflows_steps_entry_status')
									->columns('step_id, status')
									->values($new_step_id . ', ' . $status);

								$this->db->setQuery($query);
								$this->db->execute();
							}
						}
					}
				}
			} catch (Exception $e) {
				var_dump($e->getMessage() . '  ' .  $query->__toString());exit;
			}
		}

		return $new_workflow_id;
	}

	/**
	 * old Workflows were kind of equals to current Step Object, not Workflow Object
	 * before a program could be linked to muliple workflows, now it can only be linked to one
	 * before campaigns could be linked to multiple workflows, now they can not be linked to any, it must be througth campaign's program
	 * if two campaigns have the same program, but don't have the same steps, then they must have different programs
	 * @return bool migrated
	 */
	public function migrateDeprecatedCampaignWorkflows(): bool
	{
		$migrated = false;

		$query = $this->db->getQuery(true);
		$query->select('id')
			->from($this->db->quoteName('#__emundus_campaign_workflow'));

		try {
			$this->db->setQuery($query);
			$workflow_ids = $this->db->loadColumn();
		} catch (Exception $e) {
			Log::add('Error while fetching deprecated campaign workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		if (!empty($workflow_ids)) {
			$deprecated_workflows = [];

			foreach($workflow_ids as $workflow_id) {
				$query = $this->db->getQuery(true);
				$query->clear()
					->select('ecw.id, ecw.profile, ecw.start_date, ecw.end_date, ecw.output_status, GROUP_CONCAT(ecwrc.campaign) as campaign_ids, GROUP_CONCAT(ecwrp.programs) as program_codes, ecwres.entry_status as entry_status')
					->from($this->db->quoteName('#__emundus_campaign_workflow', 'ecw'))
					->leftJoin($this->db->quoteName('jos_emundus_campaign_workflow_repeat_campaign', 'ecwrc') . ' ON ' . $this->db->quoteName('ecwrc.parent_id') . ' = ' . $this->db->quoteName('ecw.id'))
					->leftJoin($this->db->quoteName('jos_emundus_campaign_workflow_repeat_programs', 'ecwrp') . ' ON ' . $this->db->quoteName('ecwrp.parent_id') . ' = ' . $this->db->quoteName('ecw.id'))
					->leftJoin($this->db->quoteName('joomla5.jos_emundus_campaign_workflow_repeat_entry_status', 'ecwres') . ' ON ' . $this->db->quoteName('ecwres.parent_id') . ' = ' . $this->db->quoteName('ecw.id'))
					->where('ecw.id = ' . $workflow_id);

				try {
					$this->db->setQuery($query);
					$deprecated_workflow_data = $this->db->loadAssoc();
				} catch (Exception $e) {
					Log::add('Error while fetching deprecated campaign workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}

				if (!empty($deprecated_workflow_data)) {
					$deprecated_workflow_data['entry_status'] = array_unique(explode(',', $deprecated_workflow_data['entry_status']));
					$deprecated_workflow_data['campaign_ids'] = array_unique(explode(',', $deprecated_workflow_data['campaign_ids']));
					$deprecated_workflow_data['program_codes'] = array_unique(explode(',', $deprecated_workflow_data['program_codes']));
				}
				$deprecated_workflows[] = $deprecated_workflow_data;
			}

			$new_workflows_data = [];

			foreach ($deprecated_workflows as $deprecated_workflow)
			{



			}
		} else {
			// no deprecated workflows, perfect
			$migrated = true;
		}

		return $migrated;
	}
}