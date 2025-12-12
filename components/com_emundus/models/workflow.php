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
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Enums\Workflow\WorkflowStepDatesRelativeUnitsEnum;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\TransactionRepository;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Enums\Workflow\WorkflowStepDateRelativeToEnum;
use Tchooz\Repositories\Workflow\StepRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');

class EmundusModelWorkflow extends JModelList
{
	private $app;
	private $db;

	private int $payment_step_type = 0;

	private EmundusHelperCache $h_cache;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->app     = Factory::getApplication();
		$this->db      = Factory::getContainer()->get('DatabaseDriver');
		$this->h_cache = new EmundusHelperCache();

		$payment_repository      = new PaymentRepository();
		$this->payment_step_type = $payment_repository->getPaymentStepTypeId();

		Log::addLogger(['text_file' => 'com_emundus.workflow.php'], Log::ALL, array('com_emundus.workflow'));
	}

	public function getPaymentStepType(): int
	{
		return $this->payment_step_type;
	}

	public function add($label = ''): int
	{
		$new_workflow_id = 0;

		$workflow            = new stdClass();
		$workflow->label     = !empty($label) ? $label : Text::_('COM_EMUNDUS_WORKFLOW_NEW');
		$workflow->published = 1;

		try
		{
			$this->db->insertObject('#__emundus_setup_workflows', $workflow);
			$new_workflow_id = $this->db->insertid();
		}
		catch (Exception $e)
		{
			Log::add('Error while adding workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $new_workflow_id;
	}

	/**
	 * Delete workflow based on id.
	 *
	 * @param $wid
	 *
	 * @return bool true if deleted, false otherwise
	 */
	public function delete($wid, $user_id, $force = false): bool
	{
		$deleted = false;

		if (!empty($wid))
		{
			if (!is_array($wid))
			{
				$wid = [$wid];
			}

			$query = $this->db->createQuery();

			if ($force)
			{
				$query->delete('#__emundus_setup_workflows')
					->where('id IN (' . implode(',', $this->db->quote($wid)) . ')');

				try
				{
					$this->db->setQuery($query);
					$deleted = $this->db->execute();
				}
				catch (Exception $e)
				{
					Log::add('Error while deleting workflow(s) [' . implode(',', $wid) . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}
			else
			{
				$query->update('#__emundus_setup_workflows')
					->set('published = 0')
					->where('id IN (' . implode(',', $this->db->quote($wid)) . ')');

				try
				{
					$this->db->setQuery($query);
					$deleted = $this->db->execute();
				}
				catch (Exception $e)
				{
					Log::add('Error while unpublishing workflow [' . implode(',', $wid) . '] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}

			if ($deleted)
			{
				$action = $force ? 'deleted' : 'unpublished';
				Log::add('Workflow [' . implode(',', $wid) . '] ' . $action . ' by user [' . $user_id . ']', Log::INFO, 'com_emundus.workflow');
			}
		}

		return $deleted;
	}

	public function updateWorkflow($workflow, $steps, $programs): bool
	{
		$updated        = false;
		$error_occurred = false;

		if (!empty($workflow['id']))
		{
			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_setup_workflows'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($workflow['label']))
				->set($this->db->quoteName('published') . ' = ' . $this->db->quote($workflow['published']))
				->where($this->db->quoteName('id') . ' = ' . $workflow['id']);

			try
			{
				$this->db->setQuery($query);
				$updated = $this->db->execute();
			}
			catch (Exception $e)
			{
				Log::add('Error while updating workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				$error_occurred = true;
			}

			if (!empty($steps))
			{
				// verify step entry status are not the same on multiple steps
				$already_used_entry_status = [];
				foreach ($steps as $step)
				{
					foreach ($step['entry_status'] as $status)
					{
						if ($this->isApplicantStep($step['type']))
						{
							if (in_array($status['id'], $already_used_entry_status))
							{
								$status_label = $status['label'] ?? '';

								throw new Exception(sprintf(Text::_('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS_ALREADY_USED'), $status_label));
							}
							$already_used_entry_status[] = $status['id'];

							if (empty($step['profile_id']))
							{
								throw new Exception(sprintf(Text::_('COM_EMUNDUS_WORKFLOW_STEP_APPLICANT_PROFILE_NOT_SET'), $step['label']));
							}
						}
						else
						{
							if ($this->isEvaluationStep($step['type']))
							{
								if (empty($step['form_id']))
								{
									throw new Exception(sprintf(Text::_('COM_EMUNDUS_WORKFLOW_STEP_EVALUATION_FORM_NOT_SET'), $step['label']));
								}
							}
						}
					}
				}

				foreach ($steps as $step)
				{
					$step['workflow_id'] = $workflow['id'];

					// if the step is new, we need to unset the id
					// if it is new, the id will be under 1
					if ($step['id'] < 1)
					{
						unset($step['id']);
					}

					if ($this->isPaymentStep($step['type']) || $this->isChoicesStep($step['type']))
					{
						$step['profile_id'] = null;
						$step['form_id']    = null;
					}
					else
					{
						if ($this->isEvaluationStep($step['type']))
						{
							$step['profile_id'] = null;
						}
						else
						{
							$step['form_id'] = null;
						}
					}
					$step_object = (object) $step;

					try
					{
						if (empty($step['id']))
						{
							if ($this->isEvaluationStep($step['type']))
							{
								$step['output_status'] = 0;
							}

							$inserted = $this->db->insertObject('#__emundus_setup_workflows_steps', $step_object);
							if ($inserted)
							{
								$step['id'] = $this->db->insertid();
							}
						}
						else
						{
							$fields = ['label', 'type', 'state', 'multiple', 'ordering', 'lock', 'output_status'];

							$fields_set = [];
							foreach ($fields as $field)
							{
								if (!isset($step[$field]))
								{
									continue;
								}

								if ($step[$field] == '')
								{
									$fields_set[] = $this->db->quoteName($field) . ' = NULL';
								}
								else
								{
									$fields_set[] = $this->db->quoteName($field) . ' = ' . $this->db->quote($step[$field]);
								}
							}
							// update existing step
							$query->clear()
								->update($this->db->quoteName('#__emundus_setup_workflows_steps'))
								->set($fields_set);

							if ($this->isPaymentStep($step['type']) || $this->isChoicesStep($step['type']))
							{
								$query->set($this->db->quoteName('profile_id') . ' = NULL');
								$query->set($this->db->quoteName('form_id') . ' = NULL');
							}
							else
							{
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
							}

							$query->where($this->db->quoteName('id') . ' = ' . $step['id']);

							$this->db->setQuery($query);
							$this->db->execute();
						}

						if (!empty($step['id']))
						{
							$step['entry_status'] = array_filter($step['entry_status']);

							$query->clear()
								->delete($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status'))
								->where($this->db->quoteName('step_id') . ' = ' . $step['id']);

							$this->db->setQuery($query);
							$this->db->execute();

							if (!empty($step['entry_status']))
							{
								foreach ($step['entry_status'] as $status)
								{
									$entry_status          = new stdClass();
									$entry_status->step_id = $step['id'];
									$entry_status->status  = $status['id'];

									$query->clear();
									$this->db->insertObject('#__emundus_setup_workflows_steps_entry_status', $entry_status);
								}
							}

							if ($this->isChoicesStep($step['type']))
							{
								// Save max_choices into workflow step choices rules table
								$this->saveChoicesStepRules($step);
							}
						}
					}
					catch (Exception $e)
					{
						Log::add('Error while adding workflow step: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
						$error_occurred = true;
					}
				}
			}

			$new_program_ids = array_map(function ($program) {
				return $program['id'];
			}, $programs);

			// select programs from the workflow
			$query->clear()
				->select('id, program_id')
				->from($this->db->quoteName('#__emundus_setup_workflows_programs'))
				->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id']);

			try
			{
				$this->db->setQuery($query);
				$workflow_programs = $this->db->loadAssocList('program_id');

				// delete the programs that are not in the workflow
				foreach ($workflow_programs as $program_id => $workflow_program)
				{
					if (!in_array($program_id, $new_program_ids))
					{
						$query->clear()
							->delete($this->db->quoteName('#__emundus_setup_workflows_programs'))
							->where($this->db->quoteName('workflow_id') . ' = ' . $workflow['id'])
							->andWhere($this->db->quoteName('program_id') . ' = ' . $program_id);

						try
						{
							$this->db->setQuery($query);
							$this->db->execute();
						}
						catch (Exception $e)
						{
							Log::add('Error while deleting workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
							$error_occurred = true;
						}
					}
				}

				if (!empty($new_program_ids))
				{
					// insert the programs that are not in the workflow
					foreach ($new_program_ids as $new_prog_id)
					{
						if (!array_key_exists($new_prog_id, $workflow_programs))
						{
							$programData              = new stdClass();
							$programData->workflow_id = $workflow['id'];
							$programData->program_id  = $new_prog_id;

							try
							{
								$this->db->insertObject('#__emundus_setup_workflows_programs', $programData);
							}
							catch (Exception $e)
							{
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
						->andWhere($this->db->quoteName('workflow_id') . ' != ' . $workflow['id']);

					try
					{
						$this->db->setQuery($query);
						$this->db->execute();
					}
					catch (Exception $e)
					{
						Log::add('Error while deleting workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
						$error_occurred = true;
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error while fetching workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				$error_occurred = true;
			}
		}

		if ($error_occurred)
		{
			$updated = false;
		} else {
			$this->h_cache->set('workflow_steps', null);
		}

		return $updated;
	}

	public function deleteWorkflowStep($stepId): bool
	{
		$deleted = false;

		if (!empty($stepId))
		{
			$query = $this->db->getQuery(true);

			$query->delete($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->where($this->db->quoteName('id') . ' = ' . $stepId);

			try
			{
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
			catch (Exception $e)
			{
				Log::add('Error while deleting workflow step: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}
		}

		return $deleted;
	}

	public function canDeleteWorkflowStep(int $stepId): bool
	{
		$can_delete = true;

		if (!empty($stepId))
		{
			// If step is a payment step, check if there are transactions linked to it
			$query = $this->db->getQuery(true);

			$query->select('type')
				->from($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->where($this->db->quoteName('id') . ' = ' . $stepId);
			try
			{
				$this->db->setQuery($query);
				$step_type = (int) $this->db->loadResult();

				if ($this->isPaymentStep($step_type))
				{
					$query->clear()
						->select('COUNT(id)')
						->from($this->db->quoteName('#__emundus_cart'))
						->where($this->db->quoteName('step_id') . ' = ' . $stepId);
					$this->db->setQuery($query);
					$nb_carts = (int) $this->db->loadResult();

					if ($nb_carts > 0)
					{
						$can_delete = false;
					}
				}
			}
			catch (Exception $e)
			{
				Log::add('Error while checking if workflow step can be deleted: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				$can_delete = false;
			}
		}

		return $can_delete;
	}

	public function countWorkflows($ids = []): int
	{
		$nb_workflows = 0;

		$query = $this->db->createQuery();

		$query->select('COUNT(esw.id)')
			->from($this->db->quoteName('#__emundus_setup_workflows', 'esw'))
			->where($this->db->quoteName('esw.published') . ' = 1');

		if (!empty($ids))
		{
			$query->where($this->db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
		}

		try
		{
			$this->db->setQuery($query);
			$nb_workflows = $this->db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Error counting published workflows : ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $nb_workflows;
	}

	/**
	 * @param $ids
	 * @param $limit    default is 0
	 * @param $page     default is 0
	 * @param $programs default is [], allowed values are an array of program ids
	 * @param $order_by default is 'esw.id', allowed values are 'esw.id' and 'esw.label'
	 * @param $order    default is 'DESC', allowed values are 'ASC' and 'DESC'
	 *
	 * @return array
	 */
	public function getWorkflows($ids = [], $limit = 0, $page = 0, $programs = [], $order_by = 'esw.id', $order = 'DESC', $search = ''): array
	{
		$workflows = [];

		$query = $this->db->getQuery(true);

		$query->select('esw.*, GROUP_CONCAT(eswp.program_id) as programme_ids')
			->from($this->db->quoteName('#__emundus_setup_workflows', 'esw'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp') . ' ON eswp.workflow_id = esw.id')
			->where($this->db->quoteName('esw.published') . ' = 1');

		if (!empty($ids))
		{
			$query->where($this->db->quoteName('esw.id') . ' IN (' . implode(',', $ids) . ')');
		}

		if (!empty($programs) && !in_array('all', $programs))
		{
			$query->where($this->db->quoteName('eswp.program_id') . ' IN (' . implode(',', $programs) . ')');
		}

		if (!empty($search))
		{
			$query->where($this->db->quoteName('esw.label') . ' LIKE ' . $this->db->quote('%' . $search . '%'));
		}

		$query->group('esw.id');

		if ($limit > 0)
		{
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

		$allowed_order_by = ['esw.id', 'esw.label'];
		$allowed_order    = ['ASC', 'DESC'];

		if (!in_array($order_by, $allowed_order_by))
		{
			$order_by = 'esw.id';
		}

		if (!in_array($order, $allowed_order))
		{
			$order = 'DESC';
		}

		$query->order($this->db->quoteName($order_by) . ' ' . $order);

		try
		{
			$this->db->setQuery($query);
			$workflows = $this->db->loadObjectList();

			foreach ($workflows as $key => $workflow)
			{
				$workflows[$key]->programme_ids = !empty($workflow->programme_ids) ? explode(',', $workflow->programme_ids) : [];
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while fetching workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $workflows;
	}

	/**
	 * @param   int  $id
	 *
	 * @return array with workflow, steps and programs
	 */
	public function getWorkflow($id = 0, array $program_ids = [], bool $displayed_archived = false): array
	{
		$workflowData = [];

		if (!empty($id) || !empty($program_ids))
		{
			$query = $this->db->getQuery(true);

			$query->select('esw.*')
				->from($this->db->quoteName('#__emundus_setup_workflows', 'esw'));

			if (!empty($program_ids))
			{
				$query->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp') . ' ON eswp.workflow_id = esw.id')
					->where($this->db->quoteName('eswp.program_id') . ' IN (' . implode(',', $program_ids) . ')');
			}

			if (!empty($id))
			{
				$query->where($this->db->quoteName('esw.id') . ' = ' . $id);
			}

			try
			{
				$this->db->setQuery($query);
				$workflow = $this->db->loadObject();
			}
			catch (Exception $e)
			{
				Log::add('Error while fetching workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}

			if (!empty($workflow->id))
			{
				$workflowData = [
					'workflow' => $workflow,
					'steps'    => [],
					'programs' => []
				];

				$query->clear()
					->select('esws.id')
					->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'));

				if (!empty($id))
				{
					$query->where($this->db->quoteName('esws.workflow_id') . ' = ' . $id);
				}
				else
				{
					if (!empty($program_ids))
					{
						$query->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp') . ' ON eswp.workflow_id = esws.workflow_id')
							->where($this->db->quoteName('eswp.program_id') . ' IN (' . implode(',', $program_ids) . ')');
					}
				}

				if (!$displayed_archived)
				{
					$query->andWhere($this->db->quoteName('esws.state') . ' = 1');
				}
				$query->order('esws.ordering ASC');

				try
				{
					$this->db->setQuery($query);
					$step_ids = $this->db->loadColumn();

					foreach ($step_ids as $step_id)
					{
						$workflowData['steps'][] = $this->getStepData($step_id);
					}
				}
				catch (Exception $e)
				{
					Log::add('Error while fetching workflow steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}

				if (!empty($id)) {
					$workflowData['programs'] = $this->getWorkflowPrograms($id);
				}
			}
		}

		return $workflowData;
	}

	/**
	 * @param   int  $workflowId
	 *
	 * @return array
	 */
	public function getWorkflowPrograms(int $workflowId): array
	{
		$workflowPrograms = $this->h_cache->get('workflow_programs');
		if (empty($workflowPrograms) || empty($workflowPrograms[$workflowId]))
		{
			$workflowPrograms = is_array($workflowPrograms) ? $workflowPrograms : [];

			$query = $this->db->getQuery(true);
			$query->clear()
				->select('program_id')
				->from($this->db->quoteName('#__emundus_setup_workflows_programs'))
				->where($this->db->quoteName('workflow_id') . ' = ' . $workflowId);

			try {
				$this->db->setQuery($query);
				$workflowPrograms[$workflowId] = $this->db->loadColumn();
				$this->h_cache->set('workflow_programs', $workflowPrograms);
			} catch (Exception $e) {
				Log::add('Error while fetching workflow programs: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}
		}

		return $workflowPrograms[$workflowId];
	}

	public function getWorkflowIdByFnum(string $fnum): int
	{
		$workflow_id = 0;

		if (!empty($fnum))
		{
			$query = $this->db->createQuery();
			$query->select('esw.id')
				->from($this->db->quoteName('#__emundus_setup_workflows', 'esw'))
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp') . ' ON eswp.workflow_id = esw.id')
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON esp.id = eswp.program_id')
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.training = esp.code')
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.campaign_id = esc.id')
				->where('ecc.fnum LIKE ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$workflow_id = (int)$this->db->loadResult();
			} catch (Exception $e) {
				Log::add('Failed to retrieve workflow id from fnum ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}
		}

		return $workflow_id;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array
	 */
	public function getWorkflowByFnum(string $fnum): array
	{
		$workflow = [];

		if (!empty($fnum))
		{
			$workflow_id = $this->getWorkflowIdByFnum($fnum);
			if (!empty($workflow_id)) {
				$workflow = $this->getWorkflow($workflow_id);
			}
		}

		return $workflow;
	}

	/**
	 * @param   int  $id
	 * @param   int  $cid  campaign id, if set, it will return the dates for the campaign and this step
	 *
	 * @return object with step data
	 */
	public function getStepData($id, $cid = null): object
	{
		$data = new stdClass();

		$stepsCache = $this->h_cache->get('workflow_steps');
		$stepsCache = is_array($stepsCache) ? $stepsCache : [];

		if (!empty($id) && isset($stepsCache[$id]) && $cid === null) {
			return $stepsCache[$id];
		}

		$query = $this->db->createQuery();
		$query->clear()
			->select('esws.*, GROUP_CONCAT(DISTINCT eswses.status) AS entry_status, payment_rules.adjust_balance_step_id, choices_rules.max, choices_rules.can_be_ordering, choices_rules.can_be_confirmed,choices_rules.can_be_sent, choices_rules.form_id as choices_form_id')
			->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'eswses') . ' ON ' . $this->db->quoteName('eswses.step_id') . ' = ' . $this->db->quoteName('esws.id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_payment_rules', 'payment_rules') . ' ON ' . $this->db->quoteName('payment_rules.step_id') . ' = ' . $this->db->quoteName('esws.id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_choices_rules', 'choices_rules') . ' ON ' . $this->db->quoteName('choices_rules.step_id') . ' = ' . $this->db->quoteName('esws.id'))
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
					$found_from_cache = false;
					$action_ids_by_types = $this->h_cache->get('action_ids_by_types');
					$action_ids_by_types = empty($action_ids_by_types) ? [] : $action_ids_by_types;
					if (!empty($action_ids_by_types[$data->type])) {
						$found_from_cache = true;
						$data->action_id = $action_ids_by_types[$data->type];
					}

					if (!$found_from_cache) {
						$query->clear()
							->select('action_id')
							->from($this->db->quoteName('#__emundus_setup_step_types'))
							->where('id = ' . $data->type);

						$this->db->setQuery($query);
						$data->action_id = $this->db->loadResult();

						if (!empty($data->action_id)) {
							$action_ids_by_types[$data->type] = $data->action_id;
							$this->h_cache->set('action_ids_by_types', $action_ids_by_types);
						}
					}
				}

				$data->programs = $this->getWorkflowPrograms($data->workflow_id);

				if (!empty($cid)) {
					$query->clear()
						->select('step_dates.start_date, step_dates.end_date, step_dates.infinite, step_dates.relative_date, step_dates.relative_to, step_dates.relative_start_date_value, step_dates.relative_start_date_unit,  esc.start_date as campaign_start_date, esc.end_date as campaign_end_date')
						->from($this->db->quoteName('#__emundus_setup_campaigns_step_dates', 'step_dates'))
						->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.id = step_dates.campaign_id')
						->where('step_dates.campaign_id = ' . $cid)
						->where('step_dates.step_id = ' . $id);

					$this->db->setQuery($query);
					$dates = $this->db->loadAssoc();

					$data->start_date = !empty($dates['start_date']) && $dates['start_date'] !== '0000-00-00 00:00:00' ? $dates['start_date'] : $dates['campaign_start_date'];
					$data->end_date = !empty($dates['end_date']) && $dates['end_date'] !== '0000-00-00 00:00:00' ? $dates['end_date'] : $dates['campaign_end_date'];
					$data->infinite = $dates['infinite'];
				}

				// Mise à jour du cache uniquement si pas de $cid
				if ($cid === null) {
					$stepsCache[$id] = $data;
					$this->h_cache->set('workflow_steps', $stepsCache);
				}
			} else {
				$data = new stdClass();
			}
		} catch (Exception $e) {
			Log::add('Error while fetching workflow steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $data;
	}

	/**
	 * @param $program_id
	 *
	 * @return array
	 */
	public function getEvaluatorStepsByProgram($program_id): array
	{
		$steps = [];

		if (!empty($program_id))
		{
			$workflows = $this->getWorkflows([], 0, 0, [$program_id]);

			foreach ($workflows as $workflow)
			{
				$workflow_data = $this->getWorkflow($workflow->id);

				foreach ($workflow_data['steps'] as $step)
				{
					if ($this->isEvaluationStep($step->type))
					{
						$steps[] = $step;
					}
				}
			}
		}

		return $steps;
	}

	/**
	 * @param         $user_id
	 * @param   bool  $read_only_steps
	 *
	 * @return array
	 */
	public function getEvaluatorSteps($user_id, bool $read_only_steps = true): array
	{
		$steps = [];

		if (!empty($user_id))
		{
			$workflows = $this->getWorkflows();

			if (!class_exists('EmundusHelperAccess'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
			}

			foreach ($workflows as $workflow)
			{
				$workflow_data = $this->getWorkflow($workflow->id);

				foreach ($workflow_data['steps'] as $step)
				{
					if ($this->isEvaluationStep($step->type) && (EmundusHelperAccess::asAccessAction($step->action_id, 'c', $user_id) || ($read_only_steps && EmundusHelperAccess::asAccessAction($step->action_id, 'r', $user_id))))
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

		if (!empty($step->id) && !empty($user_id))
		{
			if (!empty($step->table))
			{
				$has_edition_access = EmundusHelperAccess::asAccessAction($step->action_id, 'c', $user_id);

				$query = $this->db->createQuery();

				if ($step->multiple && $has_edition_access)
				{
					$query->select('ccid')
						->from($this->db->quoteName($step->table))
						->where('evaluator = ' . $user_id)
						->andWhere('step_id = ' . $step->id);
				}
				else
				{
					$query->select('ccid')
						->from($this->db->quoteName($step->table))
						->where('step_id = ' . $step->id);
				}

				try
				{
					$this->db->setQuery($query);
					$ids = $this->db->loadColumn();
				}
				catch (Exception $e)
				{
					Log::add('Error while fetching files evaluated by user: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}

			PluginHelper::importPlugin('emundus');
			$dispatcher                = Factory::getApplication()->getDispatcher();
			$onGetEvaluatedFilesByUser = new GenericEvent('onCallEventHandler', ['onGetEvaluatedFilesByUser', ['step' => $step, 'user_id' => (int) $user_id, 'evaluation_row_ids' => &$ids]]);
			$dispatcher->dispatch('onCallEventHandler', $onGetEvaluatedFilesByUser);
		}

		return $ids;
	}

	public function isEvaluated(object $step, int $user_id, int $ccid): bool
	{
		$evaluated = false;

		if (!empty($step->id) && !empty($user_id) && !empty($ccid))
		{
			if (!empty($step->table))
			{
				$has_edition_access = EmundusHelperAccess::asAccessAction($step->action_id, 'c', $user_id);

				$query = $this->db->createQuery();

				if ($step->multiple && $has_edition_access)
				{
					$query->select('ccid')
						->from($this->db->quoteName($step->table))
						->where('evaluator = ' . $user_id)
						->andWhere('step_id = ' . $step->id)
						->andWhere('ccid = ' . $ccid);
				}
				else
				{
					$query->select('ccid')
						->from($this->db->quoteName($step->table))
						->where('step_id = ' . $step->id)
						->andWhere('ccid = ' . $ccid);
				}

				try
				{
					$this->db->setQuery($query);
					$evaluated = $this->db->loadResult();
					$evaluated = !empty($evaluated);
				}
				catch (Exception $e)
				{
					Log::add('Error while checking if file is evaluated: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
			}

			PluginHelper::importPlugin('emundus');
			$dispatcher = Factory::getApplication()->getDispatcher();
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
			$fnum          = EmundusHelperFiles::getFnumFromId($ccid);
			$onIsEvaluated = new GenericEvent('onCallEventHandler', ['onIsEvaluated', ['step' => $step, 'user_id' => $user_id, 'fnum' => $fnum, 'evaluated' => &$evaluated]]);
			$dispatcher->dispatch('onCallEventHandler', $onIsEvaluated);
		}

		return $evaluated;
	}

	public function getStepEvaluationsForFile($step_id, $ccid)
	{
		$evaluations = [];

		if (!empty($step_id) && !empty($ccid))
		{
			$step = $this->getStepData($step_id);

			$query = $this->db->createQuery();
			$query->select('evaluation_table.*, CONCAT(jeu.firstname, " ", jeu.lastname) as evaluator_name')
				->from($this->db->quoteName($step->table, 'evaluation_table'))
				->leftJoin($this->db->quoteName('#__emundus_users', 'jeu') . ' ON jeu.user_id = evaluation_table.evaluator')
				->where('evaluation_table.ccid = ' . $ccid)
				->andWhere('evaluation_table.step_id = ' . $step_id);

			$this->db->setQuery($query);
			$evaluations = $this->db->loadAssocList();

			foreach ($evaluations as $key => $evaluation)
			{
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

		if (!empty($file_identifier) && in_array($column, ['fnum', 'id']))
		{
			$query = $this->db->createQuery();

			$file_infos = $this->getCampaignInfosFromFileIdentifier($file_identifier, $column);

			if (!empty($file_infos['program_id']))
			{
				$query->clear()
					->select('eswp.workflow_id')
					->from($this->db->quoteName('#__emundus_setup_workflows_programs', 'eswp'))
					->leftJoin($this->db->quoteName('#__emundus_setup_workflows', 'esw') . ' ON ' . $this->db->quoteName('esw.id') . ' = ' . $this->db->quoteName('eswp.workflow_id'))
					->where('eswp.program_id = ' . $this->db->quote($file_infos['program_id']))
					->andWhere('esw.published = 1');

				try
				{
					$this->db->setQuery($query);
					$workflow_ids = $this->db->loadColumn();
				}
				catch (Exception $e)
				{
				}

				if (!empty($workflow_ids))
				{
					$query->clear()
						->select('esws.*, choices_rules.max, choices_rules.can_be_ordering, choices_rules.can_be_confirmed, choices_rules.can_be_sent, choices_rules.form_id as choices_form_id')
						->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'esws'))
						->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'eswses') . ' ON ' . $this->db->quoteName('eswses.step_id') . ' = ' . $this->db->quoteName('esws.id'))
						->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_choices_rules', 'choices_rules') . ' ON ' . $this->db->quoteName('choices_rules.step_id') . ' = ' . $this->db->quoteName('esws.id'))
						->where('esws.workflow_id IN (' . implode(',', $workflow_ids) . ')')
						->andWhere('eswses.status = ' . $file_infos['status'])
						->andWhere('esws.type = ' . $this->db->quote($type))
						->andWhere('esws.state = 1');

					$this->db->setQuery($query);
					$step = $this->db->loadObject();

					if (empty($step->id) && !empty($file_infos['campaign_id']))
					{
						if (!class_exists('CampaignRepository'))
						{
							require_once(JPATH_ROOT . '/components/com_emundus/classes/Repositories/Campaigns/CampaignRepository.php');
						}
						$campaignRepository = new CampaignRepository();
						$campaign           = $campaignRepository->getById($file_infos['campaign_id']);
						$parent_campaigns   = !empty($campaign->getParent()) ? [$campaign->getParent()->getId()] : [];
						$linked_campaigns   = $campaignRepository->getAllCampaigns('ASC', '', 0, 0, 't.id', null, $file_infos['campaign_id'], null, $parent_campaigns);

						if ($linked_campaigns->getTotalItems() > 0)
						{
							foreach ($linked_campaigns->getItems() as $linked_campaign)
							{
								$linked_steps = $this->getCampaignSteps(($linked_campaign->getId()));
								foreach ($linked_steps as $linked_step)
								{
									if ($linked_step->type != $type || !in_array($file_infos['status'], $linked_step->entry_status))
									{
										continue;
									}

									$step = $linked_step;
									break 2;
								}
							}
						}
					}

					if (!empty($step->id))
					{
						$step->profile                       = $step->profile_id;
						$step->display_preliminary_documents = false;
						$step->specific_documents            = [];

						$query->clear()
							->select('status')
							->from('#__emundus_setup_workflows_steps_entry_status')
							->where('step_id = ' . $step->id);

						$this->db->setQuery($query);
						$step->entry_status = $this->db->loadColumn();

						$dates = $this->calculateStartAndEndDates($step, $file_infos['fnum'], (int) $file_infos['campaign_id']);
						if (!empty($dates))
						{
							$step->start_date = $dates['start_date'];
							$step->end_date   = $dates['end_date'];
							$step->infinite   = $dates['infinite'];
						}
					}
					else
					{
						$step = null;
					}
				}
			}
		}

		return $step;
	}

	public function getCampaignInfosFromFileIdentifier(string|int $identifier, string $column = 'fnum'): array
	{
		$file_infos = [];

		if (!empty($identifier)) {
			$query = $this->db->createQuery();

			$query->select('ecc.fnum, ecc.status, esp.id as program_id, ecc.published, ecc.campaign_id')
				->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
				->where('ecc.' . $column . ' LIKE ' . $this->db->quote($identifier));

			try {
				$this->db->setQuery($query);
				$file_infos = $this->db->loadAssoc();
			} catch (Exception $e) {
				Log::add('Error while fetching campaign infos from file identifier: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}
		}

		return $file_infos;
	}

	/**
	 * @param   object  $step
	 * @param   int     $campaign_id
	 *
	 * @return array
	 */
	public function calculateStartAndEndDates(object $step, string $fnum, int $campaign_id): array
	{
		$dates = [];

		$query = $this->db->createQuery();
		$stepId = $step instanceof StepEntity ? $step->getId() : $step->id;

		$query->clear()
			->select('start_date, end_date')
			->from('#__emundus_setup_campaigns')
			->where('id = ' . $campaign_id);

		$this->db->setQuery($query);
		$campaign_dates = $this->db->loadAssoc();

		$dates['start_date'] = $campaign_dates['start_date'];
		$dates['end_date']   = $campaign_dates['end_date'];

		$query->clear()
			->select('*')
			->from('#__emundus_setup_campaigns_step_dates')
			->where('campaign_id = ' . $campaign_id)
			->where('step_id = ' . $stepId);

		$this->db->setQuery($query);
		$step_date_config       = $this->db->loadObject();
		$dates['relative_date'] = !empty($step_date_config) && $step_date_config->relative_date == 1;
		$dates['infinite'] = !empty($step_date_config) && $step_date_config->infinite == 1;
		$dates['relative_start_date_unit'] = '';
		$dates['relative_start_date_value'] = 0;
		$dates['relative_end_date_unit'] = '';
		$dates['relative_end_date_value'] = 0;

		if (!empty($step_date_config))
		{
			$dates['relative_start_date_unit'] = $step_date_config->relative_start_date_unit;
			$dates['relative_start_date_value'] = $step_date_config->relative_start_date_value;
			$dates['relative_end_date_unit'] = $step_date_config->relative_end_date_unit;
			$dates['relative_end_date_value'] = $step_date_config->relative_end_date_value;

			if (!$dates['infinite'])
			{
				if ($step_date_config->relative_date == 1)
				{
					$relative_date_value = $this->getRelativeDate($fnum, $step, WorkflowStepDateRelativeToEnum::from($step_date_config->relative_to), $campaign_dates);

					if (!empty($relative_date_value) && $relative_date_value !== '0000-00-00 00:00:00')
					{
						$start_date = new DateTime($relative_date_value);
						if (!empty($step_date_config->relative_start_date_value))
						{
							$start_date->modify('+' . $step_date_config->relative_start_date_value . ' ' . $step_date_config->relative_start_date_unit);
						}

						$end_date = new DateTime($relative_date_value);
						if (!empty($step_date_config->relative_end_date_value))
						{
							$end_date->modify('+' . $step_date_config->relative_end_date_value . ' ' . $step_date_config->relative_end_date_unit);
						}

						$dates['relative_date_value'] = $relative_date_value;
						$dates['start_date'] = $start_date->format('Y-m-d H:i:s');
						$dates['end_date']   = $end_date->format('Y-m-d H:i:s');
					}
					else
					{
						// TODO: if we cannot calculate the relative date, we should not be able to start the step
					}
				}
				else
				{
					$dates['start_date'] = !empty($step_date_config->start_date) && $step_date_config->start_date !== '0000-00-00 00:00:00' ? $step_date_config->start_date : $campaign_dates['start_date'];
					$dates['end_date']   = !empty($step_date_config->end_date) && $step_date_config->end_date !== '0000-00-00 00:00:00' ? $step_date_config->end_date : $campaign_dates['end_date'];
				}
			}
		}

		return $dates;
	}

	public function getRelativeDate(string $fnum, object $step, WorkflowStepDateRelativeToEnum $relativeToEnum, array $campaign_dates = []): ?string
	{
		$relativeDate = null;

		$query = $this->db->createQuery();

		switch ($relativeToEnum)
		{
			case WorkflowStepDateRelativeToEnum::STATUS:
				$query->clear()
					->select('MAX(date_time)')
					->from('#__emundus_fnums_status_date')
					->where('fnum = ' . $this->db->quote($fnum))
					->andWhere('status IN (' . implode(',', $step->entry_status) . ')');

				try
				{
					$this->db->setQuery($query);
					$relativeDate = $this->db->loadResult();

					if (!empty($relativeDate))
					{
						$relativeDate = EmundusHelperDate::displayDate($relativeDate, 'Y-m-d H:i:s', 0);
					}
				}
				catch (\Exception $e)
				{
					Log::add('Error while fetching relative date for status: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
				}
				break;
			case WorkflowStepDateRelativeToEnum::CAMPAIGN_START_DATE:
				$relativeDate = !empty($campaign_dates['start_date']) ? $campaign_dates['start_date'] : null;
				break;
			case WorkflowStepDateRelativeToEnum::CAMPAIGN_END_DATE:
				$relativeDate = !empty($campaign_dates['end_date']) ? $campaign_dates['end_date'] : null;
				break;
			default:
				throw new \InvalidArgumentException('Invalid relative date type: ' . $relativeToEnum->value);

		}

		return $relativeDate;
	}


	/**
	 * @return array of step types
	 */
	public function getStepTypes(): array
	{
		$types = [];

		try
		{
			$query = $this->db->getQuery(true);
			$query->select('*')
				->from('#__emundus_setup_step_types')
				->where('published = 1 OR published IS NULL');

			$this->db->setQuery($query);
			$types = $this->db->loadObjectList();

			foreach ($types as $key => $type)
			{
				$query->clear()
					->select('DISTINCT(group_id)')
					->from('#__emundus_acl')
					->where('action_id = ' . $type->action_id)
					->andWhere('c = 1 OR r = 1');

				$this->db->setQuery($query);
				$types[$key]->group_ids = $this->db->loadColumn();
			}
		}
		catch (Exception $e)
		{
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
			$existing_type_ids = array_map(function ($type) {
				return $type->id;
			}, $existing_types);

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

			$updates   = [];
			$lang_code = Factory::getApplication()->getLanguage()->getTag();

			require_once(JPATH_ROOT . '/components/com_emundus/models/translations.php');
			$m_translations = new EmundusModelTranslations();

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

					if (!empty($action_id))
					{
						switch ($action_id)
						{
							case 1:
								$tag = 'COM_EMUNDUS_ACCESS_FILE';
								break;
							case 5:
								$tag = 'COM_EMUNDUS_ACCESS_EVALUATION';
								break;
							default:
								$tag = 'COM_EMUNDUS_ACCESS_' . $action_id;
						}

						$query->clear()
							->update('#__emundus_setup_actions')
							->set('label = ' . $this->db->quote($tag))
							->where('id = ' . $action_id);
						$this->db->setQuery($query);
						$updates[] = $this->db->execute();
						$updates[] = $m_translations->updateTranslation($tag, $type['label'], $lang_code);

						if (!in_array($action_id, [1, 5]))
						{
							$m_translations->updateTranslation('COM_EMUNDUS_ACCESS_' . $action_id . '_READ', $type['label'] . ' - ' . Text::_('COM_EMUNDUS_ACCESS_READ'), $lang_code);
							$m_translations->updateTranslation('COM_EMUNDUS_ACCESS_' . $action_id . '_CREATE', $type['label'] . ' - ' . Text::_('COM_EMUNDUS_ACCESS_CREATED'), $lang_code);
							$m_translations->updateTranslation('COM_EMUNDUS_ACCESS_' . $action_id . '_UPDATE', $type['label'] . ' - ' . Text::_('COM_EMUNDUS_ACCESS_UPDATED'), $lang_code);
							$m_translations->updateTranslation('COM_EMUNDUS_ACCESS_' . $action_id . '_DELETE', $type['label'] . ' - ' . Text::_('COM_EMUNDUS_ACCESS_DELETED'), $lang_code);
						}
					}

					$query->clear()
						->update('#__emundus_setup_step_types')
						->set('label = ' . $this->db->quote($type['label']))
						->set('class = ' . (!empty($type['class']) ? $this->db->quote($type['class']) : $this->db->quote('blue')))
						->set('published = 1')
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
				$action           = new stdClass();
				$action->name     = strtolower(str_replace(' ', '_', $type['label']));
				$action->name     = preg_replace('/[^A-Za-z0-9_]/', '', $action->name);
				$action->name     .= uniqid();
				$action->label    = $type['label'];
				$action->multi    = 0;
				$action->c        = 1;
				$action->r        = 1;
				$action->u        = 1;
				$action->d        = 1;
				$action->status   = 1;
				$action->ordering = 999;

				$query->clear()
					->insert('#__emundus_setup_actions')
					->columns('name, label, multi, c, r, u, d, status, ordering')
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

				if (!empty($action_id))
				{
					// re update label with the action id
					$tag = 'COM_EMUNDUS_ACCESS_' . $action_id;
					$query->clear()
						->update('#__emundus_setup_actions')
						->set('label = ' . $this->db->quote('COM_EMUNDUS_ACCESS_' . $action_id))
						->where('id = ' . $action_id);

					$m_translations->insertTranslation($tag, $type['label'], $lang_code);
					$m_translations->insertTranslation('COM_EMUNDUS_ACCESS_' . $action_id . '_READ', $type['label'] . ' - ' . Text::_('COM_EMUNDUS_ACCESS_READ'), $lang_code);
					$m_translations->insertTranslation('COM_EMUNDUS_ACCESS_' . $action_id . '_CREATE', $type['label'] . ' - ' . Text::_('COM_EMUNDUS_ACCESS_CREATED'), $lang_code);
					$m_translations->insertTranslation('COM_EMUNDUS_ACCESS_' . $action_id . '_UPDATE', $type['label'] . ' - ' . Text::_('COM_EMUNDUS_ACCESS_UPDATED'), $lang_code);
					$m_translations->insertTranslation('COM_EMUNDUS_ACCESS_' . $action_id . '_DELETE', $type['label'] . ' - ' . Text::_('COM_EMUNDUS_ACCESS_DELETED'), $lang_code);

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

					if (!empty($all_rights_grp))
					{
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
					->where($this->db->quoteName('id') . ' IN (' . implode(',', $removed_types_ids) . ')')
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

		if (!empty($step_id))
		{
			if ($this->h_cache->isEnabled())
			{
				$action_ids_by_steps = $this->h_cache->get('action_ids_by_steps');

				if (!empty($action_ids_by_steps))
				{
					if (isset($action_ids_by_steps[$step_id]))
					{
						return $action_ids_by_steps[$step_id];
					}
				}
				else
				{
					$action_ids_by_steps = [];
				}
			}

			$query = $this->db->createQuery();
			$query->select('type')
				->from($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->where('id = ' . $step_id);

			$this->db->setQuery($query);
			$step_types = $this->db->loadAssoc();

			if (!empty($step_types))
			{
				$query->clear()
					->select('action_id')
					->from($this->db->quoteName('#__emundus_setup_step_types'))
					->where('id = ' . $step_types['type']);

				$this->db->setQuery($query);
				$action_id = $this->db->loadResult();

				if ($this->h_cache->isEnabled())
				{
					$action_ids_by_type                      = $this->h_cache->get('action_ids_by_types');
					$action_ids_by_type                      = empty($action_ids_by_type) ? [] : $action_ids_by_type;
					$action_ids_by_type[$step_types['type']] = $action_id;
					$this->h_cache->set('action_ids_by_types', $action_ids_by_type);

					$action_ids_by_steps[$step_id] = $action_id;
					$this->h_cache->set('action_ids_by_steps', $action_ids_by_steps);
				}
			}
		}

		return $action_id;
	}

	/**
	 * @param   int  $campaign_id
	 *
	 * @return array
	 */
	public function getCampaignSteps(int $campaign_id): array
	{
		$steps = [];

		if (!empty($campaign_id))
		{
			$query = $this->db->createQuery();
			$query->select('esp.id')
				->from($this->db->quoteName('#__emundus_setup_programmes', 'esp'))
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esp.code = esc.training')
				->where('esc.id = ' . $campaign_id);

			$this->db->setQuery($query);
			$program_id = $this->db->loadResult();

			if (!empty($program_id))
			{
				$programs_ids = [$program_id];
				$campaignRepository = new CampaignRepository();
				$linked_programs_ids = $campaignRepository->getLinkedProgramsIds($campaign_id);
				if(!empty($linked_programs_ids))
				{
					$programs_ids = array_unique(array_merge($programs_ids, $linked_programs_ids));
				}

				$workflows = $this->getWorkflows([], 0, 0, $programs_ids);

				foreach ($workflows as $workflow)
				{
					$workflow_data = $this->getWorkflow($workflow->id);

					foreach ($workflow_data['steps'] as $step)
					{
						$query->clear()
							->select('*')
							->from('#__emundus_setup_campaigns_step_dates')
							->where('campaign_id = ' . $campaign_id)
							->where('step_id = ' . $step->id);

						$this->db->setQuery($query);
						$dates = $this->db->loadAssoc();

						if (empty($dates['id']))
						{
							$row                            = new stdClass();
							$row->campaign_id               = $campaign_id;
							$row->step_id                   = $step->id;
							$row->start_date                = null;
							$row->end_date                  = null;
							$row->infinite                  = 0;
							$row->relative_date             = 0;
							$row->relative_to               = WorkflowStepDateRelativeToEnum::STATUS->value;
							$row->relative_start_date_value = null;
							$row->relative_start_date_unit  = WorkflowStepDatesRelativeUnitsEnum::DAY->value;
							$row->relative_end_date_value   = null;
							$row->relative_end_date_unit    = WorkflowStepDatesRelativeUnitsEnum::DAY->value;

							$this->db->insertObject('#__emundus_setup_campaigns_step_dates', $row);

							$this->db->setQuery($query);
							$dates = $this->db->loadAssoc();
						}

						$step->start_date                = $dates['start_date'];
						$step->end_date                  = $dates['end_date'];
						$step->infinite                  = $dates['infinite'];
						$step->relative_date             = $dates['relative_date'];
						$step->relative_to               = $dates['relative_to'];
						$step->relative_start_date_value = $dates['relative_start_date_value'];
						$step->relative_start_date_unit  = $dates['relative_start_date_unit'];
						$step->relative_end_date_value   = $dates['relative_end_date_value'];
						$step->relative_end_date_unit    = $dates['relative_end_date_unit'];

						if ($this->isApplicantStep($step->type))
						{
							$step->readable_type = Text::_('COM_EMUNDUS_WORKFLOW_STEP_TYPE_APPLICANT');
						}
						else
						{
							if ($this->isEvaluationStep($step->type))
							{
								$step->readable_type = Text::_('COM_EMUNDUS_WORKFLOW_STEP_TYPE_EVALUATOR');
							}
							else
							{
								if ($this->isPaymentStep($step->type))
								{
									$step->readable_type = Text::_('COM_EMUNDUS_WORKFLOW_STEP_TYPE_PAYMENT');
								}
							}
						}

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

		if (!empty($campaign_id) && !empty($steps))
		{
			// todo: verify values are in enums

			$query = $this->db->createQuery();

			$saves = [];
			foreach ($steps as $step)
			{
				if (!empty($step['id']))
				{
					$query->clear()
						->update('#__emundus_setup_campaigns_step_dates')
						->set('start_date = ' . $this->db->quote($step['start_date']))
						->set('end_date = ' . $this->db->quote($step['end_date']))
						->set('infinite = ' . $this->db->quote($step['infinite']))
						->set('relative_date = ' . $this->db->quote($step['relative_date']))
						->set('relative_to = ' . (!empty($step['relative_to']) ? $this->db->quote($step['relative_to']) : WorkflowStepDateRelativeToEnum::STATUS->value))
						->set('relative_start_date_value = ' . (!empty($step['relative_start_date_value']) ? $this->db->quote($step['relative_start_date_value']) : 'NULL'))
						->set('relative_start_date_unit = ' . (!empty($step['relative_start_date_unit']) ? $this->db->quote($step['relative_start_date_unit']) : WorkflowStepDatesRelativeUnitsEnum::DAY->value))
						->set('relative_end_date_value = ' . (!empty($step['relative_end_date_value']) ? $this->db->quote($step['relative_end_date_value']) : 'NULL'))
						->set('relative_end_date_unit = ' . (!empty($step['relative_end_date_unit']) ? $this->db->quote($step['relative_end_date_unit']) : WorkflowStepDatesRelativeUnitsEnum::DAY->value))
						->where('step_id = ' . $step['id'])
						->andwhere('campaign_id = ' . $campaign_id);

					try
					{
						$this->db->setQuery($query);
						$saves[] = $this->db->execute();
					}
					catch (Exception $e)
					{
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

		if (!empty($program_id))
		{
			$deleted = false;
			$query   = $this->db->createQuery();

			$query->delete('#__emundus_setup_workflows_programs')
				->where('program_id = ' . $program_id);

			try
			{
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
			catch (Exception $e)
			{
				Log::add('Error while deleting program workflows: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}


			if (!empty($workflows))
			{
				$updates = [];
				foreach ($workflows as $workflow_id)
				{
					$workflowData              = new stdClass();
					$workflowData->workflow_id = $workflow_id;
					$workflowData->program_id  = $program_id;

					try
					{
						$updates[] = $this->db->insertObject('#__emundus_setup_workflows_programs', $workflowData);
					}
					catch (Exception $e)
					{
						Log::add('Error while adding program workflow: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
					}
				}

				$updated = !empty($updates) && !in_array(false, $updates);
			}
			else
			{
				$updated = $deleted;
			}
		}

		return $updated;
	}

	public function updateStepState($step_id, $state): bool
	{
		$archived = false;

		if (!empty($step_id))
		{
			// state must be 0 or 1
			if (!in_array($state, [0, 1]))
			{
				throw new Exception('Invalid state');
			}

			$query = $this->db->getQuery(true);

			$query->update($this->db->quoteName('#__emundus_setup_workflows_steps'))
				->set($this->db->quoteName('state') . ' = ' . $state)
				->where($this->db->quoteName('id') . ' = ' . $step_id);

			try
			{
				$this->db->setQuery($query);
				$archived = $this->db->execute();
			}
			catch (Exception $e)
			{
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

		if (!empty($type))
		{
			if ($this->h_cache->isEnabled())
			{
				$are_evaluation_steps = $this->h_cache->get('are_evaluation_steps');

				if (!empty($are_evaluation_steps) && isset($are_evaluation_steps[$type]))
				{
					return $are_evaluation_steps[$type];
				}

				if (empty($are_evaluation_steps))
				{
					$are_evaluation_steps = [];
				}
			}

			if ($type == 2)
			{
				$is_evaluation_step = true;
			}
			else
			{
				$parent_id          = $this->getParentStepType($type);
				$is_evaluation_step = $parent_id == 2;
			}

			if ($this->h_cache->isEnabled())
			{
				$are_evaluation_steps[$type] = $is_evaluation_step;
				$this->h_cache->set('are_evaluation_steps', $are_evaluation_steps);
			}
		}

		return $is_evaluation_step;
	}

	public function isPaymentStep($type): bool
	{
		$is_payment_step = false;

		if (!empty($type))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			if ($type == $this->payment_step_type)
			{
				$is_payment_step = true;
			}
			else
			{
				$query->clear()
					->select('parent_id')
					->from($db->quoteName('#__emundus_setup_step_types'))
					->where('id = ' . $type);

				$db->setQuery($query);
				$parent_id = $db->loadResult();

				$is_payment_step = $parent_id == $this->payment_step_type;
			}
		}

		return $is_payment_step;
	}

	public function isChoicesStep($type): bool
	{
		$is_choices_step = false;

		if (!empty($type))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->clear()
				->select('id')
				->from($db->quoteName('#__emundus_setup_step_types'))
				->where('code = ' . $db->quote('choices'));
			$db->setQuery($query);
			$choices_step_id = $db->loadResult();

			if ($type == $choices_step_id)
			{
				$is_choices_step = true;
			}
		}

		return $is_choices_step;
	}

	public function isApplicantStep($type): bool
	{
		$is_applicant_step = false;

		if (!empty($type))
		{
			if ($type == 1)
			{
				$is_applicant_step = true;
			}
			else
			{
				$query = $this->db->createQuery();
				$query->select('parent_id')
					->from($this->db->quoteName('#__emundus_setup_step_types'))
					->where('id = ' . $type);

				$this->db->setQuery($query);
				$parent_id = $this->db->loadResult();

				$is_applicant_step = $parent_id == 1;
			}
		}

		return $is_applicant_step;
	}

	public function getParentStepType($type)
	{
		$parent_id = 0;

		if (!empty($type))
		{
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

		try
		{
			$this->db->setQuery($query);
			$programs_workflows = $this->db->loadAssocList('program_id');

			foreach ($programs_workflows as $key => $program_workflow)
			{
				$programs_workflows[$key] = explode(',', $program_workflow['workflow_ids']);
			}

		}
		catch (Exception $e)
		{
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
	 * @deprecated Please use WorkflowRepository::duplicate instead
	 */
	public function duplicateWorkflow($workflowId): int
	{
		$newWorkflowId = 0;

		if (!empty($workflowId))
		{
			$repository = new WorkflowRepository();
			$workflow = $repository->getWorkflowById($workflowId);

			if (!empty($workflow))
			{
				$newWorkflow = $repository->duplicate($workflow);

				$newWorkflowId = $newWorkflow->getId();
			}
		}

		return $newWorkflowId;
	}

	/**
	 * @param   string          $fnum
	 * @param   int             $step_id
	 * @param   array           $elements
	 * @param   ExportModeEnum  $exportMode
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getEvaluationStepDataForFnum(string $fnum, int $step_id, array $elements, ExportModeEnum $exportMode = ExportModeEnum::GROUP_CONCAT): array
	{
		$data = [];

		if (!empty($fnum) && !empty($step_id) && !empty($elements))
		{
			$step_data = $this->getStepData($step_id);

			// get all evaluations on this step for this fnum
			if (isset($step_data->table))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
				$m_files = new EmundusModelFiles();

				$query = $this->db->getQuery(true);

				$query->clear()
					->select('evaluation.id, evaluation.evaluator, eu.firstname, eu.lastname, ecc.campaign_id')
					->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
					->leftJoin($this->db->quoteName($step_data->table, 'evaluation') . ' ON ' . $this->db->quoteName('evaluation.ccid') . ' = ' . $this->db->quoteName('ecc.id') . ' AND ' . $this->db->quoteName('evaluation.step_id') . ' = ' . $this->db->quote($step_id))
					->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.user_id') . ' = ' . $this->db->quoteName('evaluation.evaluator'))
					->where('ecc.fnum like ' . $this->db->quote($fnum));

				$this->db->setQuery($query);
				$evaluations = $this->db->loadAssocList();

				if (!empty($evaluations)) {
					$helper = new EmundusHelperFabrik();
					foreach ($evaluations as $eval_key => $evaluation)
					{
						$key        = $evaluation['id'] ?? $fnum . '-' . $eval_key;
						$data[$key] = [
							'campaign_id'    => $evaluation['campaign_id'],
							'step_id'        => $step_data->label,
							'evaluation_id'  => $evaluation['id'],
							'evaluator_name' => $evaluation['firstname'] . ' ' . $evaluation['lastname'],
						];

						$fabrik_elements = $m_files->getValueFabrikByIds($elements);
						foreach ($fabrik_elements as $fabrik_element)
						{
							$element_name              = !empty($fabrik_element['table_join']) ? $fabrik_element['table_join'] . '___' . $fabrik_element['name'] : $fabrik_element['db_table_name'] . '___' . $fabrik_element['name'];
							$data[$key][$element_name] = '';

							if (!empty($evaluation['id']))
							{
								$evaluation_row_id = $evaluation['id'];
								$value             = $helper->getFabrikElementValue($fabrik_element, $fnum, $evaluation_row_id, ValueFormatEnum::FORMATTED, 0, $exportMode);

								if (!empty($value) && isset($value[$fabrik_element['id']][$fnum]['val']))
								{
									$data[$key][$element_name] = $value[$fabrik_element['id']][$fnum]['val'];
								}
							}
						}
					}
				}
			}
		}

		return $data;
	}

	public function getPaymentStepFromFnum(string $fnum, bool $only_current = false): object|null
	{
		$step = null;

		if (!empty($fnum))
		{
			$current_step = $this->getCurrentWorkflowStepFromFile($fnum, $this->payment_step_type);

			if (empty($current_step) && !$only_current)
			{
				// TODO: get last applicant payment step
				// We should determine based on the current step of the applicant what is the last payment step is had accessed
				$workflow_data = $this->getWorkflowByFnum($fnum);

				if (!empty($workflow_data['steps']))
				{
					foreach ($workflow_data['steps'] as $workflow_step)
					{
						if ($workflow_step->type === $this->payment_step_type)
						{
							$step = $workflow_step;
							break;
						}
					}
				}
			}
			else
			{
				$step = $current_step;
			}
		}

		return $step;
	}

	/**
	 * TODO: move to PaymentRepository ?
	 * @param   string  $fnum
	 * @param   object  $step
	 *
	 * @return bool
	 */
	public function isPaymentStepCompleted(string $fnum, object $step): bool
	{
		$completed = false;

		$cart_repository = new CartRepository();
		$cart            = $cart_repository->getCartByFnum($fnum, $step->id);

		if (!empty($cart) && !empty($cart->getId()))
		{
			$transaction_repository = new TransactionRepository();
			$transaction            = $transaction_repository->getTransactionByCart($cart);

			if (!empty($transaction) && !empty($transaction->getId()))
			{
				if ($transaction->getStatus() === TransactionStatus::CONFIRMED)
				{
					$completed = true;
				}
			}
		}

		return $completed;
	}

	public function getPaymentStepUrl(): string
	{
		$url  = '';
		$app  = Factory::getApplication();
		$menu = $app->getMenu()->getItems('link', 'index.php?option=com_emundus&view=payment&layout=cart', true);

		if (!empty($menu))
		{
			$url = '/' . $menu->route . '?';
		}
		else
		{
			$url = '/index.php?option=com_emundus&view=payment&layout=cart';
		}

		return $url;
	}

	public function getChoicesStepFromFnum(string $fnum, bool $only_current = false): object|null
	{
		$step = null;

		if (!empty($fnum))
		{
			$query = $this->db->getQuery(true);
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_step_types'))
				->where('code = ' . $this->db->quote('choices'));
			$this->db->setQuery($query);
			$choices_step_type = $this->db->loadResult();

			$current_step = $this->getCurrentWorkflowStepFromFile($fnum, $choices_step_type);

			if (empty($current_step) && !$only_current)
			{
				// TODO: get last applicant payment step
				// We should determine based on the current step of the applicant what is the last payment step is had accessed
				$workflow_data = $this->getWorkflowByFnum($fnum);

				if (!empty($workflow_data['steps']))
				{
					foreach ($workflow_data['steps'] as $workflow_step)
					{
						if ($workflow_step->type === $choices_step_type)
						{
							$step = $workflow_step;
							break;
						}
					}
				}
			}
			else
			{
				$step = $current_step;
			}
		}

		return $step;
	}

	public function saveChoicesStepRules($step): bool
	{
		$query = $this->db->getQuery(true);

		try
		{
			if (!empty($step['max']))
			{
				$insert = (object) [
					'step_id'          => (int) $step['id'],
					'max'              => (int) $step['max'] ?? 1,
					'can_be_ordering'  => (int) $step['can_be_ordering'] ?? 0,
					'can_be_confirmed' => (int) $step['can_be_confirmed'] ?? 0,
					'can_be_sent' => (int) $step['can_be_sent'] ?? 0,
				];

				$query->select('id')
					->from($this->db->quoteName('#__emundus_setup_workflow_step_choices_rules'))
					->where('step_id = ' . (int) $step['id']);
				$this->db->setQuery($query);
				$rule_id = $this->db->loadResult();
				if (!empty($rule_id))
				{
					$insert->id = $rule_id;
					$this->db->updateObject('#__emundus_setup_workflow_step_choices_rules', $insert, 'id');
				}
				else
				{
					if (!$this->db->insertObject('#__emundus_setup_workflow_step_choices_rules', $insert))
					{
						throw new Exception('Could not insert choices step rules');
					}
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while saving choices step rules: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');

			return false;
		}

		return true;
	}

	public function getChoicesConfigurationFromFnum(string $fnum): array
	{
		$config = [
			'max'              => 1,
			'can_be_ordering'  => 1,
			'can_be_confirmed' => 0,
			'can_be_sent' => 0,
			'can_be_updated'   => 0,
			'form_id'          => 0,
		];

		try
		{
			$choices_step = $this->getChoicesStepFromFnum($fnum);

			$query = $this->db->getQuery(true);
			$query->select('status')
				->from($this->db->quoteName('#__emundus_campaign_candidature'))
				->where('fnum = ' . $fnum);
			$this->db->setQuery($query);
			$file_status = $this->db->loadResult();

			if (!empty($choices_step) && !empty($choices_step->max))
			{
				$config['max']              = $choices_step->max;
				$config['can_be_ordering']  = $choices_step->can_be_ordering ?? 0;
				$config['can_be_confirmed'] = $choices_step->can_be_confirmed ?? 0;
				$config['can_be_sent'] = $choices_step->can_be_sent ?? 0;
				$config['can_be_updated']   = in_array($file_status, $choices_step->entry_status) ? 1 : 0;
				$config['form_id']          = $choices_step->choices_form_id;
			}
		}
		catch (Exception $e)
		{
			Log::add('Error while fetching choices step configuration: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $config;
	}

	/**
	 * @return array
	 */
	public static function getFormsInSteps(): array
	{
		$forms = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select('DISTINCT(steps.form_id)')
			->from($db->quoteName('#__emundus_setup_workflows_steps', 'steps'))
			->leftJoin($db->quoteName('#__emundus_setup_workflows', 'workflows') . ' ON ' . $db->quoteName('workflows.id') . ' = ' . $db->quoteName('steps.workflow_id'))
			->where('steps.form_id IS NOT NULL')
			->where('steps.form_id <> 0')
			->where('steps.state = 1')
			->andWhere('workflows.published = 1');

		try {
			$db->setQuery($query);
			$forms = $db->loadColumn();
		} catch (Exception $e) {
			Log::add('Error while fetching forms in steps: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
		}

		return $forms;
	}

	/**
	 * @return array<StepEntity>
	 * @throws Exception
	 */
	public function getSteps(int $workflowId = 0, $types = []): array
	{
		$stepRepository = new StepRepository();

		return $stepRepository->getAll([
			's.workflow_id' => $workflowId,
			's.type'        => $types,
		]);
	}
}