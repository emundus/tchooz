<?php
/**
 * @package     Tchooz\Fixtures\Evaluations\Seeders
 * @subpackage
 *
 * @copyright   (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Fixtures\Evaluations\Seeders;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Workflow\StepTypesEnum;
use Tchooz\Repositories\Workflow\StepRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

\defined('_JEXEC') or die;

/**
 * Makes sure every workflow covering the given programs holds a single-evaluator step and a
 * multiple-evaluator step, both sharing one sample evaluation form whose criteria are completed
 * on every run with the ones declared in CRITERIA but missing (e.g. added after a first run).
 *
 * Steps are appended to the workflow a program already belongs to rather than moving the program,
 * so the existing configuration (applicant, payment, other evaluation steps) is left untouched.
 * Programs that belong to no workflow get a dedicated one. Idempotent by step label.
 */
final class EvaluationStepSeeder
{
	public const SINGLE_STEP_LABEL   = '[TEST] Évaluation simple';
	public const MULTIPLE_STEP_LABEL = '[TEST] Évaluation multiple';
	public const WORKFLOW_LABEL      = '[TEST] Workflow évaluations';

	private const FORM_LABEL  = ['fr' => '[TEST] Évaluation', 'en' => '[TEST] Evaluation'];
	private const GROUP_LABEL = ['fr' => 'Évaluation', 'en' => 'Evaluation'];

	/**
	 * Criteria of the sample form, by Fabrik plugin. EvaluationSeeder fills them by plugin.
	 * 'params' are merged over the plugin defaults set by the form builder.
	 */
	private const CRITERIA = [
		['plugin' => ElementPluginEnum::NUMERIC, 'label' => ['fr' => 'Note (sur 20)', 'en' => 'Grade (out of 20)'], 'params' => []],
		['plugin' => ElementPluginEnum::TEXTAREA, 'label' => ['fr' => 'Commentaire', 'en' => 'Comment'], 'params' => []],
		[
			'plugin' => ElementPluginEnum::DATABASEJOIN,
			'label'  => ['fr' => 'Nationalité', 'en' => 'Nationality'],
			'params' => [
				'join_db_name'            => 'data_nationality',
				'join_key_column'         => 'id',
				'join_val_column'         => 'label_fr',
				'database_join_where_sql' => 'order by id',
			],
		],
	];

	private DatabaseInterface  $db;
	private StepRepository     $stepRepository;
	private WorkflowRepository $workflowRepository;

	public function __construct(DatabaseInterface $db)
	{
		$this->db                 = $db;
		$this->stepRepository     = new StepRepository();
		$this->workflowRepository = new WorkflowRepository();
	}

	/**
	 * @param   array<int, array{id: int, code: string}>  $programs
	 * @param   int                                       $ownerUserId  Author of the evaluation form.
	 *
	 * @return  array{steps: StepEntity[], created: int, criteria: int}  Every sample step covering the programs, steps and criteria created.
	 */
	public function seed(array $programs, int $ownerUserId): array
	{
		if (empty($programs))
		{
			throw new \RuntimeException('No seeded programs found - run the sampledata scenario first.');
		}

		$evaluationType = (new StepTypeRepository())->getStepTypeByCode(StepTypesEnum::EVALUATOR->value);
		if ($evaluationType === null)
		{
			throw new \RuntimeException('Evaluation step type not found.');
		}

		$owner = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($ownerUserId);

		$formId          = $this->findSampleFormId() ?? $this->createForm($owner);
		$createdCriteria = $this->ensureCriteria($formId, $owner);

		$workflowIds = $this->resolveWorkflowIds(array_column($programs, 'id'));

		$steps   = [];
		$created = 0;

		foreach ($workflowIds as $workflowId)
		{
			$existing = $this->stepRepository->getStepsByWorkflowId($workflowId);
			$ordering = empty($existing) ? 0 : max(array_map(fn(StepEntity $step) => $step->getOrdering(), $existing)) + 1;

			foreach ([self::SINGLE_STEP_LABEL => 0, self::MULTIPLE_STEP_LABEL => 1] as $label => $multiple)
			{
				$step = $this->findByLabel($existing, $label);

				if ($step === null)
				{
					$step = $this->createStep($workflowId, $label, $multiple, $formId, $evaluationType, $ordering++);
					$created++;
				}

				$steps[] = $step;
			}
		}

		return ['steps' => $steps, 'created' => $created, 'criteria' => $createdCriteria];
	}

	/**
	 * The workflows the programs belong to, plus a dedicated workflow for programs that have none.
	 *
	 * @param   int[]  $programIds
	 *
	 * @return  int[]
	 */
	private function resolveWorkflowIds(array $programIds): array
	{
		$workflowIds = [];
		$orphans     = [];

		foreach ($programIds as $programId)
		{
			$workflow = $this->workflowRepository->getWorkflowByProgramId($programId);

			if ($workflow === null)
			{
				$orphans[] = $programId;

				continue;
			}

			$workflowIds[$workflow->getId()] = $workflow->getId();
		}

		if (!empty($orphans))
		{
			$workflow = new WorkflowEntity(0, self::WORKFLOW_LABEL, 1, [], $orphans);

			if (!$this->workflowRepository->save($workflow))
			{
				throw new \RuntimeException('Failed to create workflow ' . self::WORKFLOW_LABEL);
			}

			$workflowIds[$workflow->getId()] = $workflow->getId();
		}

		return array_values($workflowIds);
	}

	/**
	 * @param   StepEntity[]  $steps
	 */
	private function findByLabel(array $steps, string $label): ?StepEntity
	{
		foreach ($steps as $step)
		{
			if ($step->getLabel() === $label)
			{
				return $step;
			}
		}

		return null;
	}

	/**
	 * Form already used by a sample step in any workflow, so every run shares the same table.
	 */
	private function findSampleFormId(): ?int
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('form_id'))
			->from($this->db->quoteName('#__emundus_setup_workflows_steps'))
			->where($this->db->quoteName('label') . ' IN (' . implode(',', $this->db->quote([self::SINGLE_STEP_LABEL, self::MULTIPLE_STEP_LABEL])) . ')')
			->where($this->db->quoteName('form_id') . ' IS NOT NULL');
		$this->db->setQuery($query, 0, 1);

		$formId = (int) $this->db->loadResult();

		return $formId > 0 ? $formId : null;
	}

	private function createForm(User $owner): int
	{
		if (!class_exists('EmundusModelForm'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/form.php';
		}

		$formId = (int) (new \EmundusModelForm())->createFormEval($owner, self::FORM_LABEL);

		if (empty($formId))
		{
			throw new \RuntimeException('Failed to create the sample evaluation form.');
		}

		return $formId;
	}

	/**
	 * Add to the form the declared criteria it lacks, matched by plugin.
	 *
	 * @return int  Criteria created.
	 */
	private function ensureCriteria(int $formId, User $owner): int
	{
		if (!class_exists('EmundusModelFormbuilder'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/formbuilder.php';
		}

		$query = $this->db->getQuery(true)
			->select([$this->db->quoteName('fe.group_id'), $this->db->quoteName('fe.plugin')])
			->from($this->db->quoteName('#__fabrik_elements', 'fe'))
			->join('INNER', $this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.group_id') . ' = ' . $this->db->quoteName('fe.group_id'))
			->where($this->db->quoteName('ffg.form_id') . ' = ' . $formId)
			->where($this->db->quoteName('fe.published') . ' = 1')
			->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('criteria\\_%'));
		$this->db->setQuery($query);
		$existing = $this->db->loadObjectList() ?: [];

		$missing = array_filter(self::CRITERIA, fn(array $criterion) => !in_array($criterion['plugin']->value, array_column($existing, 'plugin'), true));
		if (empty($missing))
		{
			return 0;
		}

		$formBuilder = new \EmundusModelFormbuilder();
		$groupId     = (int) ($existing[0]->group_id ?? 0);

		if (empty($groupId))
		{
			$group   = $formBuilder->createGroup(self::GROUP_LABEL, $formId, 1, 'eval', $owner);
			$groupId = (int) ($group['group_id'] ?? 0);

			if (empty($groupId))
			{
				throw new \RuntimeException('Failed to create the criteria group of form ' . $formId);
			}
		}

		foreach ($missing as $criterion)
		{
			$elementId = (int) $formBuilder->createSimpleElement($groupId, $criterion['plugin']->value, null, 1, $criterion['label'], $owner);

			if (empty($elementId))
			{
				throw new \RuntimeException('Failed to create the ' . $criterion['plugin']->value . ' criterion of form ' . $formId);
			}

			if (!empty($criterion['params']))
			{
				$this->overrideElementParams($elementId, $criterion['params']);
			}
		}

		return count($missing);
	}

	/**
	 * @param   array<string, string>  $params
	 */
	private function overrideElementParams(int $elementId, array $params): void
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('params'))
			->from($this->db->quoteName('#__fabrik_elements'))
			->where($this->db->quoteName('id') . ' = ' . $elementId);
		$this->db->setQuery($query);

		$merged = array_merge(json_decode((string) $this->db->loadResult(), true) ?: [], $params);

		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__fabrik_elements'))
			->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($merged)))
			->where($this->db->quoteName('id') . ' = ' . $elementId);
		$this->db->setQuery($query);

		if (!$this->db->execute())
		{
			throw new \RuntimeException('Failed to set the params of element ' . $elementId);
		}
	}

	private function createStep(int $workflowId, string $label, int $multiple, int $formId, StepTypeEntity $type, int $ordering): StepEntity
	{
		$step = new StepEntity(
			id: 0,
			workflow_id: $workflowId,
			label: $label,
			type: $type,
			profile_id: null,
			form_id: $formId,
			entry_status: $this->submittedStatuses(),
			output_status: 0,
			multiple: $multiple,
			ordering: $ordering
		);

		if (!$this->stepRepository->save($step))
		{
			throw new \RuntimeException('Failed to create step ' . $label . ' in workflow ' . $workflowId);
		}

		// Reload to get the evaluation table resolved from the form's Fabrik list.
		return $this->stepRepository->getStepById($step->getId());
	}

	/**
	 * Every status except the draft one, so evaluations spread across the whole pipeline.
	 *
	 * @return int[]
	 */
	private function submittedStatuses(): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('step'))
			->from($this->db->quoteName('#__emundus_setup_status'))
			->where($this->db->quoteName('step') . ' > 0');
		$this->db->setQuery($query);

		return array_map('intval', $this->db->loadColumn() ?: []);
	}
}
