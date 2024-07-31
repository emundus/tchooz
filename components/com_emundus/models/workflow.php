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

			if (!empty($steps)) {
				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_workflows_steps'))
					->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id']);

				try {
					$this->db->setQuery($query);
					$this->db->execute();
				} catch (Exception $e) {
					Log::add('Error while deleting workflow steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}

				foreach ($steps as $step) {
					$step['workflow_id'] = $workflow['id'];
					$step_object = (object)$step;

					try {
						$inserted = $this->db->insertObject('#__emundus_setup_workflows_steps', $step_object);
					} catch (Exception $e) {
						var_dump($e->getMessage());exit;
						Log::add('Error while adding workflow step: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
					}
				}

			} else {
				$query->clear()
					->delete($this->db->quoteName('#__emundus_setup_workflows_steps'))
					->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id']);

				try {
					$this->db->setQuery($query);
					$this->db->execute();
				} catch (Exception $e) {
					Log::add('Error while deleting workflow steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}

			$query->clear()
				->delete($this->db->quoteName('#__emundus_setup_workflows_programs'))
				->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id']);

			try {
				$this->db->setQuery($query);
				$this->db->execute();
			} catch (Exception $e) {
				Log::add('Error while deleting workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}

			foreach ($programs as $program) {
				$programData = new stdClass();
				$programData->workflow_id = $workflow['id'];
				$programData->program_id = $program['id'];

				try {
					$this->db->insertObject('#__emundus_setup_workflows_programs', $programData);
				} catch (Exception $e) {
					Log::add('Error while adding workflow program: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}
		}

		return $updated;
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
					->select('*')
					->from($this->db->quoteName('#__emundus_setup_workflows_steps'))
					->where($this->db->quoteName('workflow_id') . ' = ' . $id);

				try {
					$this->db->setQuery($query);
					$workflowData['steps'] = $this->db->loadObjectList();
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