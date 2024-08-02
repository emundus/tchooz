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


	public function add()
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

	public function updateWorkflow($workflow, $steps, $programs)
	{
		$updated = false;

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
			}

			$query->clear()
				->delete($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id']);

			try {
				$this->db->setQuery($query);
				$this->db->execute();
			} catch (Exception $e) {
				Log::add('Error while deleting workflow steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}

			if (!empty($steps)) {
				foreach ($steps as $step) {
					$step['workflow_id'] = $workflow['id'];

					// if the step is new, we need to unset the id
					// if it is new, the id will be under 1
					if ($step['id'] < 1) {
						unset($step['id']);
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
						}
					} catch (Exception $e) {
						var_dump($e->getMessage());exit;
						Log::add('Error while adding workflow step: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
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
					}
				}
			}
		}

		return $updated;
	}

	public function deleteWorkflowStep($stepId) {
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

	public function getWorkflows($ids = [], $limit = 0, $page = 0) {
		$workflows = [];

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->db->quoteName('#__emundus_setup_workflows'))
			->where($this->db->quoteName('published') . ' = 1');

		if (!empty($ids)) {
			$query->where($this->db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
		}

		if ($limit > 0) {
			$query->setLimit($limit, $page * $limit);
		}

		try {
			$this->db->setQuery($query);
			$workflows = $this->db->loadObjectList();
		} catch (Exception $e) {
			Log::add('Error while fetching workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $workflows;
	}

	public function getWorkflow($id)
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
					->select('esws.*, GROUP_CONCAT(eswses.status) AS entry_status')
					->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
					->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'eswses') . ' ON ' . $this->db->quoteName('eswses.step_id') . ' = ' . $this->db->quoteName('esws.id'))
					->where($this->db->quoteName('esws.workflow_id') . ' = ' . $id)
					->group($this->db->quoteName('esws.id'))
					->order($this->db->quoteName('esws.start_date') . ' ASC');

				try {
					$this->db->setQuery($query);
					$workflowData['steps'] = $this->db->loadObjectList();
					$workflowData['steps'] = array_values($workflowData['steps']);

					foreach ($workflowData['steps'] as $key => $step) {
						$workflowData['steps'][$key]->entry_status = explode(',', $step->entry_status);
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
}