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

class EmundusModelWorkflow extends JModelList
{
	private $app;

	private $db;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->app = Factory::getApplication();
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		Log::addLogger(['text_file' => 'com_emundus.formbuilder.php'], Log::ALL, array('com_emundus.workflow'));
	}


	public function add(): int
	{
		$new_workflow_id = 0;

		$workflow = new stdClass();
		$workflow->label = Text::_('COM_EMUNDUS_WORKFLOW_NEW');
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
	public function delete($wid, $user_id): bool
	{
		$deleted = false;

		if (!empty($wid)) {
			$query = $this->db->createQuery();

			$query->delete('#__emundus_setup_workflows')
				->where('id = ' . $wid);

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (Exception $e) {
				Log::add('Error while deleting workflow [' . $wid . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}

			if ($deleted) {
				// TODO: log the action, and who did it
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
				$this->db->execute();
				$updated = true;
			} catch (Exception $e) {
				Log::add('Error while updating workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				$error_occurred = true;
			}

			$query->clear()
				->delete($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id']);

			try {
				$this->db->setQuery($query);
				$this->db->execute();
			} catch (Exception $e) {
				Log::add('Error while deleting workflow steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				$error_occurred = true;
			}

			if (!empty($steps)) {
				foreach ($steps as $step) {
					$step['workflow_id'] = $workflow['id'];

					// if the step is new, we need to unset the id
					// if it is new, the id will be under 1
					if ($step['id'] < 1) {
						unset($step['id']);
					}
					if ($step['type'] == 2) {
						$step['profile_id'] = null;
					} else {
						$step['form_id'] = null;
					}
					$step_object = (object)$step;

					try {
						$inserted = $this->db->insertObject('#__emundus_setup_workflows_steps', $step_object);

						if ($inserted) {
							$step['id'] = $this->db->insertid();
							$step['entry_status'] = array_filter($step['entry_status']);

							if (!empty($step['entry_status'])) {
								foreach ($step['entry_status'] as $status) {
									$entry_status = new stdClass();
									$entry_status->step_id = $step['id'];
									$entry_status->status = $status['id'];

									$this->db->insertObject('#__emundus_setup_workflows_steps_entry_status', $entry_status);
								}
							}

							if (!empty($step['roles'])) {
								foreach($step['roles'] as $role) {
									$row = new stdClass();
									$row->step_id = $step['id'];
									$row->profile_id = $role['id'];

									$this->db->insertObject('#__emundus_setup_workflows_steps_roles', $row);
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

	public function getWorkflows($ids = [], $limit = 0, $page = 0, $programs = []): array
	{
		$workflows = [];

		$query = $this->db->getQuery(true);

		$query->select('esw.*, GROUP_CONCAT(eswp.program_id) as programme_ids')
			->from($this->db->quoteName('#__emundus_setup_workflows', 'esw'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp') . ' ON eswp.workflow_id = esw.id')
			->where($this->db->quoteName('esw.published') . ' = 1');

		if (!empty($ids)) {
			$query->where($this->db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
		}

		if (!empty($programs)) {
			$query->where($this->db->quoteName('eswp.program_id') . ' IN (' . implode(',', $programs) . ')');
		}

		$query->group('esw.id');

		if ($limit > 0) {
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

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
					->select('esws.*, GROUP_CONCAT(eswses.status) AS entry_status, GROUP_CONCAT(eswsr.profile_id) AS roles')
					->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
					->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'eswses') . ' ON ' . $this->db->quoteName('eswses.step_id') . ' = ' . $this->db->quoteName('esws.id'))
					->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_roles', 'eswsr') . ' ON ' . $this->db->quoteName('eswsr.step_id') . ' = ' . $this->db->quoteName('esws.id'))
					->where($this->db->quoteName('esws.workflow_id') . ' = ' . $id)
					->group($this->db->quoteName('esws.id'))
					->order($this->db->quoteName('esws.start_date') . ' ASC');

				try {
					$this->db->setQuery($query);
					$workflowData['steps'] = $this->db->loadObjectList();
					$workflowData['steps'] = array_values($workflowData['steps']);

					foreach ($workflowData['steps'] as $key => $step) {
						$workflowData['steps'][$key]->entry_status = array_unique(explode(',', $step->entry_status));
						$workflowData['steps'][$key]->roles = !empty($step->roles) ? array_unique(explode(',', $step->roles)) : [];
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
	 * @param $id
	 *
	 * @return object with step data
	 */
	public function getStepData($id): object
	{
		$data = new stdClass();

		if (!empty($id)) {
			$query = $this->db->createQuery();
			$query->clear()
				->select('esws.*, GROUP_CONCAT(eswses.status) AS entry_status, GROUP_CONCAT(eswsr.profile_id) AS roles')
				->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'eswses') . ' ON ' . $this->db->quoteName('eswses.step_id') . ' = ' . $this->db->quoteName('esws.id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_roles', 'eswsr') . ' ON ' . $this->db->quoteName('eswsr.step_id') . ' = ' . $this->db->quoteName('esws.id'))
				->where('esws.id = ' . $id)
				->group($this->db->quoteName('esws.id'));

			try {
				$this->db->setQuery($query);
				$data = $this->db->loadObject();

				if (!empty($data->id)) {
					$data->entry_status = array_unique(explode(',', $data->entry_status));
					$data->roles = !empty($data->roles) ? array_unique(explode(',', $data->roles)) : [];

					$query->clear()
						->select('program_id')
						->from($this->db->quoteName('#__emundus_setup_workflows_programs'))
						->where($this->db->quoteName('workflow_id') . ' = ' . $data->workflow_id);

					$this->db->setQuery($query);
					$data->programs = $this->db->loadColumn();
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
					if ($step->type == 2)
					{
						$steps[] = $step;
					}
				}
			}
		}

		return $steps;
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

			$query->select('ecc.status, esp.id as program_id, ecc.published')
				->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
				->where('ecc.' . $column . ' LIKE ' . $this->db->quote($file_identifier));

			$this->db->setQuery($query);
			$file_infos = $this->db->loadAssoc();

			if (!empty($file_infos['program_id']) && $file_infos['published']) {
				// get workflows associated to this program
				$query->clear()
					->select('workflow_id')
					->from($this->db->quoteName('#__emundus_setup_workflows_programs'))
					->where('program_id = ' . $this->db->quote($file_infos['program_id']));

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
						->andWhere('esws.type = ' . $this->db->quote($type));

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
				->from('#__emundus_setup_workflow_step_types');

			$this->db->setQuery($query);
			$types = $this->db->loadObjectList();
		} catch (Exception $e) {
			Log::add('Error while fetching step types: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $types;
	}
}