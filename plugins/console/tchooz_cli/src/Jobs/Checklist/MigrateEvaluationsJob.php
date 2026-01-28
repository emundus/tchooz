<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Style\EmundusProgressBar;
use Gantry\Framework\Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tchooz\Entities\Indexer\IndexEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Workflow\StepRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

require_once(JPATH_ROOT . '/components/com_emundus/models/formbuilder.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/form.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/programme.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
require_once(JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php');

class MigrateEvaluationsJob extends TchoozChecklistJob
{
	private \EmundusModelWorkflow $m_workflow;
	private \EmundusModelProgramme $m_program;
	private \EmundusModelFormBuilder $m_form_builder;
	private \EmundusModelEvaluation $m_evaluation;
	private OutputInterface $output;
	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService,
		private readonly int $limit = 1000
	)
	{
		$this->allowFailure = false;
		$this->m_form_builder = new \EmundusModelFormBuilder();
		$this->m_program = new \EmundusModelProgramme();
		$this->m_workflow = new \EmundusModelWorkflow();
		$this->m_evaluation = new \EmundusModelEvaluation();


		if (!class_exists('IndexEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/Indexer/IndexEntity.php');
		}
		if (!class_exists('StepTypeEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/Workflow/StepTypeEntity.php');
		}
		if (!class_exists('StepEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/Workflow/StepEntity.php');
		}
		if (!class_exists('WorkflowEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/Workflow/WorkflowEntity.php');
		}

		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->output = $output;
		$this->databaseService->startTransaction();
		$this->installIndexes();
		$migrated = $this->migrateEvaluations();

		if ($migrated) {
			$this->databaseService->commitTransaction();
		} else {
			$this->databaseService->rollbackTransaction();
			throw new \Exception('Failed to migrate evaluations');
		}
	}

	private function installIndexes(): void
	{
		$columns = [
			['name' => 'label', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
			['name' => 'old_index', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
			['name' => 'new_index', 'type' => 'VARCHAR', 'length' => 255, 'null' => 0],
			['name' => 'params', 'type' => 'JSON', 'null' => 1],
		];
		\EmundusHelperUpdate::createTable('jos_emundus_indexes', $columns);
	}

	public function migrateEvaluations(): bool
	{
		$migrated = false;

		$programs = $this->getPrograms();
		$automated_task_user = ComponentHelper::getParams('com_emundus')->get('automated_task_user', 62);
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($automated_task_user);

		if (!empty($programs)) {
			Log::add('Found ' . count($programs) . ' programmes', Log::INFO, self::getJobName());
			$this->output->writeln('Found ' . count($programs) . ' programmes');

			$evaluations_old_new_mapping = [];

			$section = $this->output->section();
			$progressBar = new EmundusProgressBar($section, count($programs));
			$progressBar->setMessage('Migrating programmes evaluations');
			$progressBar->start();

			foreach ($programs as $program) {
				$evaluation_forms = [
					$this->m_program->getEvaluationGrid($program->id),
					$this->m_evaluation->getDecisionFormByProgramme($program->code),
					$this->m_evaluation->getAdmissionFormByProgramme($program->code)
				];
				$evaluation_forms = array_filter($evaluation_forms);
				$evaluation_forms = array_values($evaluation_forms);

				if (!empty($evaluation_forms)) {
					Log::add('Found ' . count($evaluation_forms) . ' evaluation forms for programme ' . $program->id . ' (' . $program->label . ')', Log::INFO, self::getJobName());

					foreach ($evaluation_forms as $evaluation_form_id) {
						if (!empty($evaluation_form_id)) {
							Log::add('Found evaluation form for programme ' . $program->id . ' (' . $program->label . ')  with id ' . $evaluation_form_id, Log::INFO, self::getJobName());

							if (!isset($evaluations_old_new_mapping[$evaluation_form_id])) {
								$found_index = $this->searchIndexNewValue($evaluation_form_id, 'fabrik_form_id');
								if (!empty($found_index)) {
									$evaluations_old_new_mapping[$evaluation_form_id] = $found_index;
								}
							}

							if (empty($evaluations_old_new_mapping[$evaluation_form_id])) {
								$new_evaluation_form_id = $this->m_form_builder->duplicateFabrikForm($evaluation_form_id, $user->id, ['keep_structure' => false, 'model_prefix' => '', 'profile_id' => null], '');

								if (!empty($new_evaluation_form_id))
								{
									// add columns to new fabrik table
									if (!$this->addMissingColumnsToNewEvaluationForm($new_evaluation_form_id, $user))
									{
										$this->output->writeln($this->colors['red'] . 'Failed to add missing columns to new evaluation form ' . $new_evaluation_form_id . ' for programme ' . $program->id . ' (' . $program->label . ')' . $this->colors['reset']);
										throw new \Exception('Failed to add missing columns to new evaluation form ' . $new_evaluation_form_id . ' for programme ' . $program->id . ' (' . $program->label . ')');
									}

									// add emundusstepevaluation as first plugin
									if (!$this->addEmundusStepEvaluationPluginToForm($new_evaluation_form_id, $user))
									{
										$this->output->writeln($this->colors['red'] . 'Failed to add emundusstepevaluation plugin to new evaluation form ' . $new_evaluation_form_id . ' for programme ' . $program->id . ' (' . $program->label . ')' . $this->colors['reset']);
										throw new \Exception('Failed to add emundusstepevaluation plugin to new evaluation form ' . $new_evaluation_form_id . ' for programme ' . $program->id . ' (' . $program->label . ')');
									}

									if (!$this->updateOverallElement($new_evaluation_form_id))
									{
										$this->output->writeln($this->colors['red'] . 'Failed to update overall element in new evaluation form ' . $new_evaluation_form_id . ' for programme ' . $program->id . ' (' . $program->label . ')' . $this->colors['reset']);
									}

									$form_index = new IndexEntity(0, 'fabrik_form_id', $evaluation_form_id, $new_evaluation_form_id);
									$form_index->save();
								}
								else
								{
									$this->output->writeln($this->colors['red'] . 'Failed to duplicate evaluation form ' . $evaluation_form_id . ' for programme ' . $program->id . ' (' . $program->label . ')' . $this->colors['reset']);
									throw new \Exception('Failed to duplicate evaluation form ' . $evaluation_form_id . ' for programme ' . $program->id . ' (' . $program->label . ')');
								}
							} else {
								$new_evaluation_form_id = $evaluations_old_new_mapping[$evaluation_form_id];
								Log::add('Evaluation form already created - ' . $new_evaluation_form_id, Log::INFO, self::getJobName());
							}

							if ($new_evaluation_form_id === $evaluation_form_id)
							{
								$this->output->writeln($this->colors['red'] . 'New evaluation form id is the same as old evaluation form id for programme ' . $program->id . ' (' . $program->label . ')' . $this->colors['reset']);
								throw new \Exception('New evaluation form id is the same as old evaluation form id for programme ' . $program->id . ' (' . $program->label . ')');
							}

							// if old evaluation form id is in table admission or final_grade, then the step type must be decision or admission and not evaluation
							// if those types does not exist, we create them
							$list = \EmundusHelperFabrik::getListByFormId($evaluation_form_id);

							$stepTypeRepository = new StepTypeRepository();
							switch ($list->db_table_name)
							{
								case 'jos_emundus_admission':
									$step_type = $stepTypeRepository->getStepTypeByCode('admission');

									if (empty($step_type))
									{
										$step_type = new StepTypeEntity(
											0,
											2,
											'Admission',
											'admission',
											32,
											false
										);

										$saved = $stepTypeRepository->flush($step_type);
										if (!$saved) {
											$this->output->writeln($this->colors['red'] . 'Failed to create admission step type' . $this->colors['reset']);
											throw new \Exception('Failed to create admission step type');
										}
									}

									$actionRepository = new ActionRepository();
									$crudAction = $actionRepository->getByName('admission');
									$crudAction->setStatus(true);
									$actionRepository->flush($crudAction);

									break;
								case 'jos_emundus_final_grade':
									$step_type = $stepTypeRepository->getStepTypeByCode('decision');

									if (empty($step_type))
									{
										$step_type = new StepTypeEntity(
											0,
											2,
											'Decision',
											'decision',
											29,
											false
										);

										$saved = $stepTypeRepository->flush($step_type);

										if (!$saved) {
											$this->output->writeln($this->colors['red'] . 'Failed to create decision step type' . $this->colors['reset']);
											throw new \Exception('Failed to create decision step type');
										}
									}

									$actionRepository = new ActionRepository();
									$crudAction = $actionRepository->getByName('decision');
									$crudAction->setStatus(true);
									$actionRepository->flush($crudAction);

									break;
								default:
									$step_type = $stepTypeRepository->getStepTypeById(2);
									break;
							}

							$step_id = $this->createWorkflowStepForEvaluation($program->id, $new_evaluation_form_id, $evaluation_form_id, $step_type);

							if (!empty($step_id)) {
								Log::add('Created evaluation step for programme ' . $program->id . ' (' . $program->label . ')  with id ' . $step_id, Log::INFO, self::getJobName());
								$migrated = $this->migrateDataFromOldToNewTables($evaluation_form_id, $step_id, $program->id);

								if ($migrated) {
									Log::add('Migrated data from old evaluation form to new evaluation form', Log::INFO, self::getJobName());

								} else {
									$this->output->writeln($this->colors['red'] . 'Failed to migrate data from old evaluation form to new evaluation form' . $this->colors['reset']);
								}
							} else {
								$this->output->writeln($this->colors['red'] .'Failed to create evaluation step for programme ' . $program->id . ' (' . $program->label . ')' . $this->colors['reset']);
							}
						}
					}
				} else {
					Log::add('No evaluation forms found for programme ' . $program->id . ' (' . $program->label . ')', Log::INFO, self::getJobName());
				}

				$progressBar->advance();
			}

			$progressBar->finish();
		} else {
			Log::add('No programmes found', Log::INFO, self::getJobName());
			$this->output->writeln('No programmes found');
		}

		return $migrated;
	}

	private function getPrograms(): array
	{
		$programs = [];

		try {
			$db = $this->databaseService->getDatabase();
			$query = $db->getQuery(true);

			$query->select('*')
				->from($db->quoteName('#__emundus_setup_programmes', 'esp'));

			$db->setQuery($query);
			$programs = $db->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Failed to get programmes: ' . $e->getMessage(), Log::ERROR, self::getJobName());
		}

		return $programs;
	}

	private function createWorkflowStepForEvaluation(int $program_id, int $evaluation_form_id, int $old_evaluation_form_id, StepTypeEntity $evaluationType): int
	{
		$step_id = 0;

		if (!empty($program_id) && !empty($evaluation_form_id)) {
			$stepRepository = new StepRepository();
			$workflowRepository = new WorkflowRepository();
			$workflow = $workflowRepository->getWorkflowByProgramId($program_id);

			if (empty($workflow))
			{
				$db = $this->databaseService->getDatabase();
				$query = $db->createQuery();
				$query->select('label')
					->from('#__emundus_setup_programmes')
					->where('id = ' . $program_id);

				$db->setQuery($query);
				$program_label = $db->loadResult();
				$workflow = new WorkflowEntity(0, 'Workflow - ' . $program_label, 1, [], [$program_id]);
				$workflowRepository->save($workflow);
			}

			if (!empty($workflow))
			{
				foreach ($workflow->steps as $step)
				{
					if ($step->getFormId() === $evaluation_form_id) {
						Log::add('Evaluation step already exists in workflow ' . $workflow->getId(), Log::INFO, self::getJobName());
						return $step->getId();
					}
				}

				$newStep = new StepEntity(
					0,
					$workflow->getId(),
					$this->getEvaluationLabel($evaluation_form_id)['fr'],
					$evaluationType,
					null,
					$evaluation_form_id,
					[1],
					null,
					$this->wasEvaluationMultiple($old_evaluation_form_id) ? 1 : 0,
					1,
					count($workflow->steps) + 1);

				if ($workflow->addStep($newStep))
				{
					Log::add('Added evaluation step to workflow ' . $workflow->getId(), Log::INFO, self::getJobName());

					$saved = $stepRepository->save($newStep);
					if ($saved) {
						$step_id = $newStep->getId();
					}
					else
					{
						$this->output->writeln($this->colors['red'] . 'Failed to save evaluation step to workflow ' . $workflow->getId() . $this->colors['reset']);
						Log::add('Failed to save evaluation step to workflow ' . $workflow->getId(), Log::ERROR, self::getJobName());
					}
				} else {
					$this->output->writeln($this->colors['red'] . 'Failed to add evaluation step to workflow ' . $workflow->getId() . $this->colors['reset']);
					Log::add('Failed to add evaluation step to workflow ' . $workflow->getId(), Log::ERROR, self::getJobName());
				}
			}
		}

		return $step_id;
	}

	/**
	 * @param   int  $form_id
	 * @param   int  $group_id
	 *
	 * @return array
	 */
	private function getElementsToCopy(int $form_id, int $group_id = 0): array
	{
		$elementsToCopy = [];

		if (!empty($form_id)) {
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();

			$elements_to_skip = ['id', 'parent_id', 'fnum', 'user', 'student_id', 'campaign_id'];
			$query->clear()
				->select('jfe.id, jfe.name, jfe.params, jfe.plugin, jfg.params as group_params, jfg.id as group_id')
				->from($db->quoteName('#__fabrik_elements', 'jfe'))
				->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
				->leftJoin($db->quoteName('#__fabrik_groups', 'jfg') . ' ON jfg.id = jfe.group_id')
				->where('jffg.form_id = ' . $form_id)
				->andWhere('jfe.published = 1')
				->andWhere('jfe.name NOT IN (' . implode(',', $db->quote($elements_to_skip)) .')');

			if (!empty($group_id)) {
				$query->andWhere('jfe.group_id = ' . $group_id);
			}

			$db->setQuery($query);
			$elementsToCopy = $db->loadAssocList();
		}

		return $elementsToCopy;
	}

	private function migrateDataFromOldToNewTables(int $old_evaluation_form_id, int $evaluation_step_id, int $program_id): bool
	{
		$migrated_task = [];

		$stepRepository = new StepRepository();
		$stepEntity = $stepRepository->getStepById($evaluation_step_id);
		if (!empty($stepEntity->getTable())) {
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();

			$query->select('jfl.db_table_name')
				->from($db->quoteName('#__fabrik_lists', 'jfl'))
				->where('jfl.form_id = ' . $old_evaluation_form_id);

			$db->setQuery($query);
			$old_fabrik_table = $db->loadResult();

			$elements = $this->getElementsToCopy($old_evaluation_form_id);

			if (!empty($elements)) {
				Log::add('Found ' . count($elements) . ' elements in old evaluation form ' . $old_evaluation_form_id, Log::INFO, self::getJobName());

				$elements_names = [];
				foreach ($elements as $element)
				{
					$groupParams = json_decode($element['group_params'], true);
					if ($groupParams['repeat_group_button'] == 1) {
						continue;
					}

					$elements_names[] = $element['name'];
				}

				$query->clear()
					->select('old_eval_table.id, old_eval_table.fnum, old_eval_table.user as evaluator,  ' . implode(',', array_map(fn($element_name) => 'old_eval_table.' . $element_name, $elements_names)))
					->from($db->quoteName($old_fabrik_table, 'old_eval_table'))
					->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.fnum = old_eval_table.fnum')
					->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.id = ecc.campaign_id')
					->leftJoin($db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON esp.code = esc.training')
					->where('esp.id = ' . $program_id);

				$db->setQuery($query);
				$old_evaluations = $db->loadObjectList();

				if (!empty($old_evaluations)) {
					Log::add('Found ' . count($old_evaluations) . ' evaluations for old evaluation form ' . $old_evaluation_form_id, Log::INFO, self::getJobName());
					$mapping_old_row_id_new_row_id = [];

					foreach ($old_evaluations as $old_evaluation) {
						$already_inserted = $this->searchIndexNewValue($old_evaluation->id, 'evaluation_row_id', [
							'old_table' => $old_fabrik_table,
						]);
						if (!empty($already_inserted)) {
							Log::add('Row id ' . $old_evaluation->id. ' already inserted in table ' . $old_fabrik_table, Log::INFO, self::getJobName());
							continue;
						}

						$evaluation_data = [];
						foreach ($elements_names as $element_name) {
							$evaluation_data[$element_name] = $old_evaluation->{$element_name};
						}

						$evaluation_data['fnum'] = $old_evaluation->fnum;
						$evaluation_data['ccid'] = \EmundusHelperFiles::getIdFromFnum($old_evaluation->fnum);
						$evaluation_data['evaluator'] = $old_evaluation->user ?? null;
						$evaluation_data['step_id'] = $evaluation_step_id;

						unset($evaluation_data['id']);
						$evaluation_data = (object) $evaluation_data;
						try {
							$inserted = $db->insertObject($stepEntity->getTable(), $evaluation_data);
							if ($inserted) {
								$migrated_task[] = true;
								$new_row_id = $db->insertid();
								$mapping_old_row_id_new_row_id[$old_evaluation->id] = $new_row_id;

								$evaluation_row_index = new IndexEntity(0, 'evaluation_row_id', $old_evaluation->id, $new_row_id, [
									'old_table' => $old_fabrik_table,
									'new_table' => $stepEntity->getTable()
								]);
								$evaluation_row_index->save();
							} else {
								$migrated_task[] = false;
								$this->output->writeln($this->colors['red'] . 'Failed to insert evaluation data of row id ' . $old_evaluation->id  . ' found in table ' . $old_fabrik_table . ' to new table ' . $stepEntity->getTable() . $this->colors['reset']);
								Log::add('Failed to insert evaluation data of row id ' . $old_evaluation->id  . ' in table ' . $old_fabrik_table, Log::ERROR, self::getJobName());
							}
						} catch (\Exception $e) {
							$this->output->writeln('Failed to insert evaluation data of row id ' . $old_evaluation->id  . ' found in table ' . $old_fabrik_table . ' to new table ' . $stepEntity->getTable(). ': ' . $e->getMessage());

							$migrated_task[] = false;
							Log::add('Failed to insert evaluation data: ' . $e->getMessage(), Log::ERROR, self::getJobName());
						}
					}

					$group_ids = array_unique(array_column($elements, 'group_id'));
					$migrated_task[] = $this->migrateDataFromRepeatableGroups($group_ids, $stepEntity, $mapping_old_row_id_new_row_id);

					// do the same with repeatable elements
					$migrated_task[] = $this->migrateDataFromRepeatableElements($stepEntity, $elements, $mapping_old_row_id_new_row_id);
				}

				if (!$this->removeUnnecessaryColumnsFromNewEvaluationForm($stepEntity))
				{
					$this->output->writeln($this->colors['warning'] . 'Failed to remove unnecessary columns from new evaluation form ' . $stepEntity->getFormId() . $this->colors['reset']);
				}
				else
				{
					Log::add('Removed unnecessary columns from new evaluation form ' . $stepEntity->getFormId(), Log::INFO, self::getJobName());
				}
			}
		}

		return !in_array(false, $migrated_task);
	}

	private function migrateDataFromRepeatableGroups(array $group_ids, StepEntity $step, array $evaluations_rows_mapping): bool
	{
		$migrated = false;

		if (!empty($group_ids)) {
			$migrated_task = [];
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();

			$query->clear()
				->select('*')
				->from($db->quoteName('#__fabrik_joins', 'jfj'))
				->where('jfj.group_id IN (' . implode(',', $group_ids) . ')')
				->andWhere('jfj.element_id = 0 OR jfj.element_id is null');

			try {
				$db->setQuery($query);
				$group_joins = $db->loadAssocList();
			} catch (\Exception $e) {
				Log::add('Failed to get join informations from group ids ' . $e->getMessage(), Log::ERROR, self::getJobName());
			}

			if (!empty($group_joins)) {
				Log::add('Found ' . sizeof($group_joins) . ' repeatable groups in ' . implode(',', $group_ids), Log::INFO, self::getJobName());

				foreach($group_joins as $group_join) {
					$original_table = $group_join['table_join'];
					$parent_column = $group_join['table_join_key'];
					$new_table = $this->findCorrespondingGroupTable($group_join['group_id'], $step->form_id);

					// copy data from old table to new table and update parent column with new row id
					if (!empty($new_table)) {
						foreach ($evaluations_rows_mapping as $old_row_id => $new_row_id)
						{
							$query->clear()
								->select('*')
								->from($db->quoteName($original_table, 'jft'))
								->where('jft.' . $parent_column . ' = ' . $old_row_id);

							$db->setQuery($query);
							$old_group_data = $db->loadAssocList();

							if (!empty($old_group_data)) {
								foreach ($old_group_data as $old_group_row) {
									$old_group_row_id = $old_group_row['id'];

									$old_group_row[$parent_column] = $new_row_id;
									unset($old_group_row['id']);
									$new_row_object = (object) $old_group_row;
									$inserted = $db->insertObject($new_table, $new_row_object);

									if (!$inserted) {
										$this->output->writeln('Failed to insert group data of row id ' . $old_group_row['id']  . ' in table ' . $new_table . ' from data of ' . $original_table);
										$migrated_task[] = false;
										Log::add('Failed to insert group data of row id ' . $old_group_row['id']  . ' in table ' . $new_table . ' from data of ' . $original_table, Log::ERROR, self::getJobName());
									} else {
										$migrated_task[] = true;
										$new_group_row_id = $db->insertid();
										$evaluation_group_row_index = new IndexEntity(0, 'evaluation_row_id', $old_group_row_id, $new_group_row_id, [
											'old_table' => $original_table,
											'new_table' => $new_table,
											'old_parent_id' => $old_row_id,
											'new_parent_id' => $new_row_id
										]);
										$evaluation_group_row_index->save();
									}
								}
							}
						}
					}
					else
					{
						$this->output->writeln('[' . $this->colors['red'] . 'Failed to find corresponding group table for old group id ' . $group_join['group_id'] . ' and new form id ' . $step->form_id . $this->colors['reset'] . ']');
						throw new \Exception('Failed to find corresponding group table for old group id ' . $group_join['group_id'] . ' and new form id ' . $step->form_id);
					}
				}
			}

			$migrated = !in_array(false, $migrated_task);
		} else {
			$migrated = true;
		}

		return $migrated;
	}

	private function findCorrespondingGroupTable(int $old_group_id, int $new_form_id): string
	{
		$table = '';

		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();

		try {
			// get old group elements names to find the new group
			$query->clear()
				->select('jfe.name')
				->from($db->quoteName('#__fabrik_elements', 'jfe'))
				->where('jfe.group_id = ' . $old_group_id)
				->where('jfe.published = 1');
			$db->setQuery($query);
			$old_group_elements_names = $db->loadColumn();

			// find new group id by searching a group having the same elements names
			$query->clear()
				->select('jfg.id')
				->from($db->quoteName('#__fabrik_groups', 'jfg'))
				->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfg.id')
				->leftJoin($db->quoteName('#__fabrik_elements', 'jfe') . ' ON jfe.group_id = jfg.id')
				->where('jffg.form_id = ' . $new_form_id)
				->where('jfe.name IN (' . implode(',', $db->quote($old_group_elements_names)) . ')')
				->where('jfe.published = 1')
				->group('jfg.id')
				->having('COUNT(DISTINCT jfe.name) = ' . count($old_group_elements_names));

			$db->setQuery($query);
			$new_group_id = $db->loadResult();

			if (!empty($new_group_id)) {
				$h_files = new \EmundusHelperFiles();
				$join_informations = $h_files->getJoinInformations(0, $new_group_id);

				if (!empty($join_informations)) {
					$table = $join_informations['table_join'];
				}
			}
		} catch (\Exception $e) {
			Log::add('Failed to find corresponding group table: ' . $e->getMessage() . ' ' . $query->__toString(), Log::ERROR, self::getJobName());
		}

		return $table;
	}

	private function migrateDataFromRepeatableElements(StepEntity $step, array $old_elements, array $evaluations_rows_mapping): bool
	{
		$migrated = false;

		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();

		if (!empty($old_elements)) {
			$h_files = new \EmundusHelperFiles();

			foreach ($old_elements as $element) {
				$migrated_elements = [];

				// get id of the new element id
				$query->clear()
					->select('ei.new_index')
					->from($db->quoteName('#__emundus_indexes', 'ei'))
					->where('ei.old_index = ' . $db->quote($element['id']))
					->andWhere('JSON_EXTRACT(ei.params, "$.new_form_id") = ' . $db->quote($step->form_id))
					->andWhere('ei.label = ' . $db->quote('fabrik_element_id'));

				$db->setQuery($query);
				$new_element_id = $db->loadResult();

				$new_join_informations = $h_files->getJoinInformations($new_element_id);
				if (!empty($new_join_informations)) {
					$old_join_informations = $h_files->getJoinInformations($element['id']);
					$in_repeat_group = false;

					if (!$in_repeat_group) {
						foreach ($evaluations_rows_mapping as $old_row_id => $new_row_id)
						{
							$query->clear()
								->select('*')
								->from($db->quoteName($old_join_informations['table_join'], 'old_table'))
								->where('old_table.' . $old_join_informations['table_join_key'] . ' = ' . $old_row_id);

							$db->setQuery($query);
							$old_data = $db->loadAssocList();

							if (!empty($old_data)) {
								// insert data in $new_join_informations['table_join'] with $new_join_informations['table_join_key'] = $new_row_id
								// only if not already done
								foreach ($old_data as $old_row) {
									$already_inserted = $this->searchIndexNewValue($old_row['id'], 'evaluation_row_id', [
										'old_table' => $old_join_informations['table_join'],
									]);
									if (!empty($already_inserted)) {
										// $this->output->writeln('Row id ' . $old_row['id'] . ' already inserted in table ' . $new_join_informations['table_join']);
										continue;
									}

									$old_row[$old_join_informations['table_join_key']] = $new_row_id;
									unset($old_row['id']);
									$new_row_object = (object) $old_row;
									$inserted = $db->insertObject($new_join_informations['table_join'], $new_row_object);

									if (!$inserted) {
										$migrated_elements[] = false;
										Log::add('Failed to insert repeatable element data of row id ' . $old_row['id']  . ' in table ' . $new_join_informations['table_join'] . ' from data of ' . $old_join_informations['table_join'], Log::ERROR, self::getJobName());
									} else {
										$migrated_elements[] = true;
										$new_group_row_id = $db->insertid();
										$evaluation_group_row_index = new IndexEntity(0, 'evaluation_row_id', $old_row['id'], $new_group_row_id, [
											'old_table' => $old_join_informations['table_join'],
											'new_table' => $new_join_informations['table_join'],
											'old_parent_id' => $old_row_id,
											'new_parent_id' => $new_row_id
										]);
										$evaluation_group_row_index->save();
									}
								}
							}
						}
					} else {
						// TODO:
						// the parent_id will not be the same, as it is in a child table level 2 (parent_table -> repeatable_group -> repeatable_element)
						// we need to find the parent_ids of the repeatable group using parent_table row id
						// thanks to indexes table
					}
				}

				$migrated = !in_array(false, $migrated_elements);
			}
		} else {
			$migrated = true;
		}


		return $migrated;
	}

	/**
	 * @param   string|int  $index
	 * @param   string      $label
	 * @param   array       $more_parameters
	 *
	 * @return string
	 */
	private function searchIndexNewValue(string|int $index, string $label, array $more_parameters = []): string
	{
		$new_index = '';

		try {
			$db = $this->databaseService->getDatabase();
			$query = $db->getQuery(true);

			$query->select('ei.new_index')
				->from($db->quoteName('#__emundus_indexes', 'ei'))
				->where('ei.old_index = ' . $db->quote($index))
				->andWhere('ei.label = ' . $db->quote($label));

			if (!empty($more_parameters)) {
				foreach ($more_parameters as $key => $value) {
					$query->andWhere('JSON_EXTRACT(' . $db->quoteName('ei.params') . ', "$.' . $key . '") = ' . $db->quote($value));
				}
			}

			$db->setQuery($query);
			$new_index = $db->loadResult();

			if (empty($new_index)) {
				$new_index = '';
			}
		} catch (\Exception $e) {
			Log::add('Failed to search index: ' . $e->getMessage(), Log::ERROR, self::getJobName());
		}

		return $new_index;
	}

	private function searchIndex(string|int $from_index, string $label, string $search = 'new'): string
	{
		$index = '';

		try {
			if (!in_array($search, ['new', 'old'])) {
				$search = 'new';
			}

			$search_from = $search === 'new' ? 'old' : 'new';

			$db = $this->databaseService->getDatabase();
			$query = $db->getQuery(true);

			$query->select('ei.' . $search . '_index')
				->from($db->quoteName('#__emundus_indexes', 'ei'))
				->where('ei.' . $search_from . '_index = ' . $db->quote($from_index))
				->andWhere('ei.label = ' . $db->quote($label));

			$db->setQuery($query);
			$index = $db->loadResult();

			if (empty($index)) {
				$index = '';
			}
		} catch (\Exception $e) {
			Log::add('Failed to search index: ' . $e->getMessage(), Log::ERROR, self::getJobName());
		}

		return $index;
	}

	private function wasEvaluationMultiple(int $old_form_id): bool
	{
		$multiple = false;

		if (!empty($old_form_id))
		{
			$list = \EmundusHelperFabrik::getListByFormId($old_form_id);

			if ($list->db_table_name === 'jos_emundus_evaluations')
			{
				$em_config            = ComponentHelper::getParams('com_emundus');
				$multi_eval_parameter = $em_config->get('multi_eval', 0);
				$multiple             = $multi_eval_parameter == 1;
			}
		}

		return $multiple;
	}

	private function getEvaluationLabel(int $formid): array
	{
		$label = [
			'fr' => 'Evaluation ' . $formid,
			'en' => 'Evaluation ' . $formid
		];

		if (!empty($formid)) {
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();
			$query->select('label')
				->from($db->quoteName('#__fabrik_forms'))
				->where('id = ' . $formid);

			$db->setQuery($query);
			$form_label = $db->loadResult();

			if (!empty($form_label)) {
				if (!class_exists('EmundusModelTranslations')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/translations.php');
				}

				$m_translations = new \EmundusModelTranslations();
				$translations = $m_translations->getTranslations('override', '*', '', '', '', 0,'', $form_label);

				foreach($translations as $translation) {
					if ($translation->lang_code == 'fr-FR') {
						$label['fr'] = $translation->override;
					} elseif ($translation->lang_code == 'en-GB') {
						$label['en'] = $translation->override;
					} else {
						$short_code = substr($translation->lang_code, 0, 2);
						$label[$short_code] = $translation->override;
					}
				}
			}
		}

		return $label;
	}

	/**
	 * Add missing columns to new evaluation table if any
	 *
	 * @param   int   $formId
	 * @param   User  $user
	 *
	 * @return  bool
	 */
	private function addMissingColumnsToNewEvaluationForm(int $formId, User $user): bool
	{
		$added = false;

		if (!empty($formId))
		{
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();

			$query->select('jfe.group_id')
				->from($db->quoteName('#__fabrik_elements', 'jfe'))
				->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
				->where('jffg.form_id = ' . $formId)
				->where('jfe.name = ' . $db->quote('id'));
			$db->setQuery($query);
			$group_id = $db->loadResult();

			if (!empty($group_id))
			{
				$added = $this->m_form_builder->createFormEvalDefaulltElements($group_id, $user);
			}
		}

		return $added;
	}

	/**
	 * @param   int   $formId
	 * @param   User  $user
	 *
	 * @return bool
	 */
	private function addEmundusStepEvaluationPluginToForm(int $formId, User $user): bool
	{
		$added = false;

		if (!empty($formId))
		{
			$query = $this->databaseService->getDatabase()->createQuery();
			$query->select('jff.params')
				->from($this->databaseService->getDatabase()->quoteName('#__fabrik_forms', 'jff'))
				->where('jff.id = ' . $formId);
			$this->databaseService->getDatabase()->setQuery($query);
			$json = $this->databaseService->getDatabase()->loadResult();
			$data = json_decode($json, true);

			// Valeurs à ajouter
			$new_plugin = 'emundusstepevaluation';
			$new_plugin_location = 'both';
			$new_plugin_event = 'both';
			$new_plugin_description = '';

			// Ajout en première position
			array_unshift($data['plugins'], $new_plugin);
			array_unshift($data['plugin_locations'], $new_plugin_location);
			array_unshift($data['plugin_events'], $new_plugin_event);
			array_unshift($data['plugin_description'], $new_plugin_description);
			array_unshift($data['plugin_state'], 1);

			// move indexes if any curl_code
			if (!empty($data['plugins_indexes'])) {
				$data['plugins_indexes'] = array_map(fn($index) => $index + 1, $data['plugins_indexes']);
			}

			if (!empty($data['only_process_curl']))
			{
				$new_only_process_curl = [];
				foreach ($data['only_process_curl'] as $index => $value) {
					$new_only_process_curl[$index + 1] = $value;
				}
				$data['only_process_curl'] = $new_only_process_curl;

			}

			if (!empty($data['form_php_file']))
			{
				$new_form_php_file = [];
				foreach ($data['form_php_file'] as $index => $value)
				{
					$new_form_php_file[$index + 1] = $value;
				}
				$data['form_php_file'] = $new_form_php_file;
			}

			if (!empty($data['form_php_require_once']))
			{
				$new_form_php_require_once = [];
				foreach ($data['form_php_require_once'] as $index => $value)
				{
					$new_form_php_require_once[$index + 1] = $value;
				}
				$data['form_php_require_once'] = $new_form_php_require_once;
			}

			if (!empty($data['curl_code']))
			{
				$new_curl_code = [];
				foreach ($data['curl_code'] as $index => $value)
				{
					$new_curl_code[$index + 1] = $value;
				}
				$data['curl_code'] = $new_curl_code;
			}

			// unpublish emundusisevaluatedbyme plugin if exists
			$indexEvaluatedByMe = array_search('emundusisevaluatedbyme', $data['plugins']);
			if ($indexEvaluatedByMe !== false)
			{
				$data['plugin_state'][$indexEvaluatedByMe] = 0;
			}

			// Mise à jour de la base de données
			$query->clear()
				->update($this->databaseService->getDatabase()->quoteName('#__fabrik_forms'))
				->set('params = ' . $this->databaseService->getDatabase()->quote(json_encode($data)))
				->where('id = ' . $formId);
			$this->databaseService->getDatabase()->setQuery($query);
			$added = $this->databaseService->getDatabase()->execute();
		}

		return $added;
	}

	/**
	 * Update overall element to be used as total to calculate averages of evaluations
	 * @param   int  $evaluationFormId
	 *
	 * @return bool
	 */
	private function updateOverallElement(int $evaluationFormId): bool
	{
		$updated = false;

		if (!empty($evaluationFormId)) {
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();

			$query->select('jfe.id, jfe.params')
				->from($db->quoteName('#__fabrik_elements', 'jfe'))
				->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
				->where('jffg.form_id = ' . $evaluationFormId)
				->where('jfe.name = ' . $db->quote('overall'));

			$db->setQuery($query);
			$overall_element = $db->loadObject();

			if (!empty($overall_element)) {
				$params = json_decode($overall_element->params, true);
				$params['used_as_total'] = 1;
				$overall_element->params = json_encode($params);

				try {
					$updated = $db->updateObject('#__fabrik_elements', $overall_element, 'id');
				} catch (\Exception $e) {
					$this->output->writeln('Failed to update overall element id ' . $overall_element->id . ': ' . $e->getMessage());
					Log::add('Failed to update overall element id ' . $overall_element->id . ': ' . $e->getMessage(), Log::ERROR, self::getJobName());
				}
			}
		}

		return $updated;
	}

	private function removeUnnecessaryColumnsFromNewEvaluationForm(StepEntity $step): bool
	{
		$removed = false;

		if (!empty($step->getFormId()))
		{
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();
			$query->select('jfe.id')
				->from($db->quoteName('#__fabrik_elements', 'jfe'))
				->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
				->where('jffg.form_id = ' . $step->getFormId())
				->where('jfe.name IN (' . $db->quote('user') . ', ' . $db->quote('student_id') . ')');
			$db->setQuery($query);
			$elementIds = $db->loadColumn();

			// unpublish user and student_id elements
			if (!empty($elementIds)) {
				$query->clear()
					->update($db->quoteName('#__fabrik_elements'))
					->set('published = 0')
					->where('id IN (' . implode(',', $elementIds) . ')');
				$db->setQuery($query);
				$removed = $db->execute();
			} else {
				$removed = true;
			}
		}

		return $removed;
	}

	public static function getJobName(): string {
		return 'Evaluations';
	}

	public static function getJobDescription(): ?string {
		return 'Migrate old evaluations, decision, admission and final grade data to new evaluation structure';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}
