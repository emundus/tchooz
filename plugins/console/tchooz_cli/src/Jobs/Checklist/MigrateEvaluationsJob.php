<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Style\EmundusProgressBar;
use Emundus\Workflow\StepEntity;
use Emundus\Workflow\StepTypeEntity;
use Gantry\Framework\Exception;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Emundus\Workflow\WorkflowEntity;
use Emundus\Indexer\Entities\IndexEntity;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;

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
	private \EmundusModelForm $m_form;
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
		$this->m_form = new \EmundusModelForm();
		$this->m_form_builder = new \EmundusModelFormBuilder();
		$this->m_program = new \EmundusModelProgramme();
		$this->m_workflow = new \EmundusModelWorkflow();
		$this->m_evaluation = new \EmundusModelEvaluation();


		if (!class_exists('IndexEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/indexer/Entities/IndexEntity.php');
		}
		if (!class_exists('StepTypeEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/workflow/StepTypeEntity.php');
		}
		if (!class_exists('StepEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/workflow/StepEntity.php');
		}
		if (!class_exists('WorkflowEntity')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/workflow/WorkflowEntity.php');
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
								// select $evaluation_form_id label
								$label = $this->getEvaluationLabel($evaluation_form_id);
								$intro = $this->getEvaluationIntro($evaluation_form_id);
								$new_evaluation_form_id = $this->m_form->createFormEval($user, $label, $intro);

								if (!empty($new_evaluation_form_id)) {
									$form_index = new IndexEntity(0, 'fabrik_form_id', $evaluation_form_id, $new_evaluation_form_id);
									$form_index->save();

									$old_fabrik_list = $this->m_form_builder->getList($evaluation_form_id);
									$new_fabrik_list = $this->m_form_builder->getList($new_evaluation_form_id);
									$evaluations_old_new_mapping[$evaluation_form_id] = $new_evaluation_form_id;
									Log::add('Created new evaluation form with id ' . $new_evaluation_form_id, Log::INFO, self::getJobName());

									$elements_to_exclude = $this->getElementIdsToExclude($evaluation_form_id);
									$copied = $this->m_form_builder->copyGroups($evaluation_form_id, $new_evaluation_form_id, $new_fabrik_list->id, $old_fabrik_list->db_table_name, '', $user, $elements_to_exclude);
									if ($copied) {
										Log::add('Copied groups from old evaluation form to new evaluation form', Log::INFO, self::getJobName());

										$added = $this->addGroupElementsToBDD($new_fabrik_list, $evaluation_form_id, $old_fabrik_list->db_table_name);

										if ($added) {
											Log::add('Added group elements to new evaluation form ' . $new_evaluation_form_id, Log::ERROR, self::getJobName());
										} else {
											Log::add('Failed to add group elements to new evaluation form', Log::ERROR, self::getJobName());
											$this->output->writeln($this->colors['yellow'] . 'Failed to add group elements to new evaluation form' . $this->colors['reset']);
										}
									} else {
										Log::add('Failed to copy groups from old evaluation form to new evaluation form', Log::ERROR, self::getJobName());
										$this->output->writeln($this->colors['yellow'] . 'Failed to copy groups from old evaluation form to new evaluation form' . $this->colors['reset']);
										continue;
									}
								} else {
									Log::add('Failed to create new evaluation form', Log::ERROR, self::getJobName());
									$this->output->writeln($this->colors['red'] . 'Failed to create new evaluation form' . $this->colors['reset']);
									continue;
								}
							} else {
								$new_evaluation_form_id = $evaluations_old_new_mapping[$evaluation_form_id];
								Log::add('Evaluation form already created - ' . $new_evaluation_form_id, Log::INFO, self::getJobName());

							}

							$step_id = $this->createWorkflowStepForEvaluation($program->id, $new_evaluation_form_id);

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

	private function createWorkflowStepForEvaluation(int $program_id, int $evaluation_form_id): int
	{
		$step_id = 0;

		if (!empty($program_id) && !empty($evaluation_form_id)) {
			$workflow_data = $this->m_workflow->getWorkflow(0, [$program_id]);

			if (empty($workflow_data['workflow']))
			{
				Log::add('Creating new workflow for programme ' . $program_id, Log::INFO, self::getJobName());

				$db = $this->databaseService->getDatabase();
				$query = $db->createQuery();
				$query->select('label')
					->from('#__emundus_setup_programmes')
					->where('id = ' . $program_id);

				$db->setQuery($query);
				$program_label = $db->loadResult();

				$workflow_label = 'Workflow - ' . $program_label;
				$workflow_id = $this->m_workflow->add($workflow_label);

				if (!empty($workflow_id)) {
					$this->m_workflow->updateWorkflow(
						['id' => $workflow_id, 'label' => $workflow_label, 'published' => 1],
						[],
						[['id' => $program_id]]
					);
					$workflow_data = $this->m_workflow->getWorkflow($workflow_id);
				} else {
					$this->output->writeln($this->colors['red'] . 'Failed to create new workflow for programme ' . $program_id . $this->colors['reset']);
					Log::add('Failed to create new workflow for programme ' . $program_id, Log::ERROR, self::getJobName());
				}
			}

			if (!empty($workflow_data['workflow']))
			{
				if ($workflow_data['steps']) {
					foreach ($workflow_data['steps'] as $step) {
						if ($step->form_id == $evaluation_form_id) {
							Log::add('Evaluation step already exists in workflow ' . $workflow_data['workflow']->id, Log::INFO, self::getJobName());
							return $step->id;
						}
					}
				}

				$workflow_entity = new WorkflowEntity($workflow_data['workflow']->id);
				$step = new StepEntity(0);
				$step->label = $this->getEvaluationLabel($evaluation_form_id)['fr'];
				$step->type = new StepTypeEntity(2); // 2 is default evaluation step type id
				$step->profile_id = 0;
				$step->form_id = $evaluation_form_id;
				$step->multiple = $this->wasEvaluationMultiple($evaluation_form_id) ? 1 : 0;
				$step->state = 1;
				$step->workflow_id = $workflow_entity->getId();

				try {
					$added = $workflow_entity->addStep($step);

					if ($added) {
						Log::add('Added evaluation step to workflow ' . $workflow_data['workflow']->id, Log::INFO, self::getJobName());
						$step_id = $step->getId();
					} else {
						$this->output->writeln($this->colors['red'] . 'Failed to add evaluation step to workflow ' . $workflow_data['workflow']->id . $this->colors['reset']);
						Log::add('Failed to add evaluation step to workflow ' . $workflow_data['workflow']->id, Log::ERROR, self::getJobName());
					}
				} catch (\Exception $e) {
					$this->output->writeln($this->colors['red'] . 'Failed to add evaluation step to workflow ' . $workflow_data['workflow']->id . ': ' . $e->getMessage() . $this->colors['reset']);
					Log::add('Failed to add evaluation step to workflow: ' . $e->getMessage(), Log::ERROR, self::getJobName());
				}
			}
		}

		return $step_id;
	}

	/**
	 * @param   object  $fabrik_list
	 * @param   int     $old_form_id
	 * @param   string  $src_table
	 *
	 * @return bool
	 */
	private function addGroupElementsToBDD(object $fabrik_list, int $old_form_id, string $src_table): bool
	{
		$added = false;

		$groups = $this->m_form->getGroupsByForm($old_form_id);

		if (!empty($groups)) {
			$target_table = $fabrik_list->db_table_name;

			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();

			foreach ($groups as $group_index => $group) {
				if (empty($group->id)) {
					Log::add('Group at index ' . $group_index . ' for form id ' . $old_form_id . ' is empty', Log::WARNING, self::getJobName());
					continue;
				}

				$groups_added = [];
				$params = json_decode($group->params);

				if ($params->repeat_group_button == 1) {
					$new_group_id = $this->searchIndexNewValue($group->id, 'fabrik_group_id');

					$old_repeat_table_name = $src_table . '_' . $group->id . '_repeat';
					$repeat_table_name = $target_table . '_' . $new_group_id . '_repeat';
					$columns = [['name' => 'parent_id', 'type' => 'INT', 'null' => 0]];
					$repeat_table_created = \EmundusHelperUpdate::createTable($repeat_table_name, $columns);

					if ($repeat_table_created) {
						// update join in jos_fabrik_joins
						$query->clear()
							->update($db->quoteName('#__fabrik_joins'))
							->set('join_from_table = ' . $db->quote($target_table))
							->set('table_join = ' . $db->quote($repeat_table_name))
							->set('list_id = ' . $fabrik_list->id)
							->set('params = ' . $db->quote(json_encode(['type' => 'group', 'pk' => $repeat_table_name . '.id'])))
							->where('group_id = ' . $new_group_id)
							->andWhere('element_id = 0 OR element_id IS NULL');

						$db->setQuery($query);
						$db->execute();

						$groups_added[] = $this->addOldEvalColumnsToNewForm($repeat_table_name, $old_form_id, $old_repeat_table_name, $group->id);
					} else {
						$this->output->writeln($this->colors['red'] . 'Failed to create repeatable group table ' . $repeat_table_name . $this->colors['reset']);
						Log::add('Failed to create repeatable group table ' . $repeat_table_name, Log::ERROR, self::getJobName());
					}
				} else {
					// this is basic table, just add columns
					$groups_added[] = $this->addOldEvalColumnsToNewForm($target_table, $old_form_id, $src_table, $group->id);
				}

				$added = !in_array(false, $groups_added);
			}
		}

		return $added;
	}

	/**
	 * @param   string  $target_table
	 * @param   int     $old_form_id
	 * @param   string  $source_table
	 *
	 * @return bool
	 */
	private function addOldEvalColumnsToNewForm(string $target_table, int $old_form_id, string $source_table, int $group_id = 0): bool
	{
		$added = false;

		Log::add('Adding columns to table ' . $target_table . ' from table ' . $source_table . ', form ' . $old_form_id . ' and group ' . $group_id, Log::INFO, self::getJobName());
		if (!empty($old_form_id) && !empty($target_table) && !empty($source_table)) {
			$elements = $this->getElementsToCopy($old_form_id, $group_id);

			if (!empty($elements)) {
				$h_files = new \EmundusHelperFiles();
				$elements_added = [];

				$db = $this->databaseService->getDatabase();
				$query = $db->createQuery();

				$columns = $db->getTableColumns($target_table);
				$columns = array_map('strtolower', $columns);

				foreach ($elements as $element) {
					$is_repeat = $this->isElementRepeatable($element);
					$new_group_id = $this->searchIndexNewValue($group_id, 'fabrik_group_id');
					$new_element_id = $this->searchIndexNewValue($element['id'], 'fabrik_element_id');

					if ($is_repeat) {
						$element_target_table = $target_table . '_repeat_' . $element['name'];
						$element_source_table = $source_table . '_repeat_' . $element['name'];

						$query->clear()
							->select('COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH')
							->from('information_schema.COLUMNS')
							->where('TABLE_NAME = ' . $db->quote($element_source_table))
							->andWhere('COLUMN_NAME = ' . $db->quote($element['name']));

						$db->setQuery($query);
						$column_infos = $db->loadAssoc();
						$columns = [
							['name' => 'parent_id', 'type' => 'INT', 'null' => 0],
							['name' => $element['name'], 'type' => $column_infos['DATA_TYPE'], 'null' => $column_infos['IS_NULLABLE'] === 'YES' ? 1 : 0, 'default' => $column_infos['COLUMN_DEFAULT']]
						];
						$element_table_created = \EmundusHelperUpdate::createTable($element_target_table, $columns);

						if ($element_table_created) {

							$query->clear()
								->update($db->quoteName('#__fabrik_joins'))
								->set('join_from_table =' . $db->quote($target_table))
								->set('table_join =' . $db->quote($element_target_table))
								->set('params = ' . $db->quote(json_encode(['type' => 'repeatElement', 'pk' => $element_target_table . '.id'])))
								->where('element_id = ' . $new_element_id)
								->andWhere('group_id = ' . $new_group_id);

							try {
								$db->setQuery($query);
								$db->execute();
							} catch (\Exception $e) {
								$this->output->writeln($e->getMessage() .  ' '  . $query->__toString());
								Log::add($e->getMessage() .  ' '  . $query->__toString(), Log::ERROR, self::getJobName());
							}

							$elements_added[] = true;
						} else {
							Log::add('Failed to create repeatable element table ' . $element_target_table, Log::WARNING, self::getJobName());
						}
					} else {
						$column_name = strtolower($element['name']);
						if (!in_array($column_name, $columns)) {
							$query->clear()
								->select('COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH')
								->from('information_schema.COLUMNS')
								->where('TABLE_NAME = ' . $db->quote($source_table))
								->andWhere('COLUMN_NAME = ' . $db->quote($element['name']));

							$db->setQuery($query);
							$column_infos = $db->loadAssoc();
							if (!empty($column_infos)) {
								if ($column_infos['DATA_TYPE'] !== 'varchar') {
									$column_infos['CHARACTER_MAXIMUM_LENGTH'] = null;
								}

								$result = \EmundusHelperUpdate::addColumn($target_table, $column_name, $column_infos['DATA_TYPE'], $column_infos['CHARACTER_MAXIMUM_LENGTH'], $column_infos['IS_NULLABLE'] === 'YES' ? 1 : 0, $column_infos['COLUMN_DEFAULT']);
								$column_added = $result['status'];

								if ($column_added) {
									$columns[] = $column_name;
									Log::add('Added column ' . $column_name . ' to table ' . $target_table, Log::INFO, self::getJobName());
									$elements_added[] = true;
								} else {
									Log::add('Failed to add column ' . $column_name . ' to table ' . $target_table . ' : ' . $result['message'], Log::ERROR, self::getJobName());
									$elements_added[] = false;
								}
							} else {
								$this->output->writeln($this->colors['yellow'] . 'Failed to add column ' . $column_name . ' to table ' . $target_table . $this->colors['reset']);
								Log::add('Failed to add column ' . $column_name . ' to table ' . $target_table, Log::ERROR, self::getJobName());
							}
						}
					}

					$this->replaceOldTableOccurrenceInElement($element, $target_table, $source_table);
				}

				$added = !in_array(false, $elements_added);
			}
		}

		return $added;
	}

	/**
	 * @param   array   $element
	 * @param   string  $target_table
	 * @param   string  $source_table
	 *
	 * @return void
	 */
	private function replaceOldTableOccurrenceInElement(array $element, string $target_table, string $source_table): void
	{
		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();

		// TODO: maybe it is necessary to do things about default values and databasejoin elements too

		if ($element['plugin'] === 'calc') {
			// calcs are used to make a result upon other elements in form
			// it is written as "{table___element_name}"
			// problem is table has now changed, we must find those, and change it

			if(!empty($element['params']))
			{
				$decoded_params = json_decode($element['params'], true);
				$code = $decoded_params['calc_calculation'];

				// replace every occurrences in code
				if (str_contains($code, '{' . $source_table . '___')) {
					$new_code = str_replace('{' . $source_table . '___', '{' . $target_table . '___', $code);
					$decoded_params['calc_calculation'] = $code;

					$query->clear()
						->update('#__fabrik_elements')
						->set('params = ' . $db->quote(json_encode($decoded_params)))
						->set('parent_id = 0')
						->where('id = ' . $element['id']);

					try {
						$db->setQuery($query);
						$db->execute();
					} catch (Exception $e) {
						Log::add($e->getMessage() .  ' ' . $e->getTraceAsString(), Log::ERROR, self::getJobName());
					}
				}
			}
		}

		// check if there are js associated with this element
		$query->clear()
			->select('*')
			->from('#__fabrik_jsactions')
			->where('element_id = ' . $element['id']);

		$db->setQuery($query);
		$js_actions = $db->loadObjectList();

		if (!empty($js_actions)) {
			foreach ($js_actions as $js_action) {
				if (str_contains($js_action->code, '{' . $source_table . '___')) {
					$js_action->code = str_replace('{' . $source_table . '___', '{' . $target_table . '___', $js_action->code);

					$query->clear()
						->update('#__fabrik_jsactions')
						->set('code = ' . $db->quote($js_action->code))
						->where('id = ' . $js_action->id);

					try {
						$db->setQuery($query);
						$updated = $db->execute();

						if ($updated) {
							Log::add('Updated JS action code for element ' . $element['name'] . ' in table ' . $target_table, Log::INFO, self::getJobName());
						} else {
							Log::add('Failed to update JS action code for element ' . $element['name'] . ' in table ' . $target_table, Log::ERROR, self::getJobName());
						}

					} catch (Exception $e) {
						Log::add($e->getMessage() .  ' ' . $e->getTraceAsString(), Log::ERROR, self::getJobName());
					}
				}
			}
		}
	}

	private function getElementsToCopy(int $form_id, int $group_id = 0): array
	{
		$elements = [];

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
			$elements = $db->loadAssocList();
		}

		return $elements;
	}

	private function getElementIdsToExclude($form_id): array
	{
		$ids = [];

		// remove first element id, fnum, user, student_id, campaign_id
		$db = $this->databaseService->getDatabase();
		$query = $db->createQuery();

		$query->clear()
			->select('jfe.id, jfe.name')
			->from($db->quoteName('#__fabrik_elements', 'jfe'))
			->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
			->where('jffg.form_id = ' . $form_id)
			->andWhere('jfe.published = 1')
			->andWhere('jfe.name IN (' . $db->quote('id') . ', ' . $db->quote('fnum') . ', ' . $db->quote('user') . ', ' . $db->quote('student_id') . ', ' . $db->quote('campaign_id') . ')')
			->order('jfe.id ASC');

		$db->setQuery($query);
		$elements = $db->loadAssocList();

		if (!empty($elements)) {
			$already_found_id = false;
			foreach ($elements as $element) {
				if ($element['name'] == 'id') {
					if (!$already_found_id) {
						$ids[] = $element['id'];
						$already_found_id = true;
					}
				} else {
					$ids[] = $element['id'];
				}
			}
		}

		return $ids;
	}

	private function isElementRepeatable(array $element): bool
	{
		$is_repeat = false;

		if (!empty($element)) {
			switch($element['plugin']) {
				case 'databasejoin':
					$params = json_decode($element['params'], true);
					$is_repeat = $params['database_join_display_type'] === 'checkbox' || $params['database_join_display_type'] === 'multilist';
					break;
				default:
					break;
			}
		}

		return $is_repeat;
	}

	// TODO: migrate data from old tables to new tables
	private function migrateDataFromOldToNewTables(int $old_evaluation_form_id, int $evaluation_step_id, int $program_id): bool
	{
		$migrated_task = [];

		$step_data = new StepEntity($evaluation_step_id);
		if (!empty($step_data->table)) {
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

				$elements_names = array_column($elements, 'name');
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
							// $this->output->writeln('Row id ' . $old_evaluation->id. ' already inserted in table ' . $old_fabrik_table);
							continue;
						}

						$evaluation_data = [];
						foreach ($elements_names as $element_name) {
							$evaluation_data[$element_name] = $old_evaluation->{$element_name};
						}

						$evaluation_data['fnum'] = $old_evaluation->fnum;
						$evaluation_data['ccid'] = \EmundusHelperFiles::getIdFromFnum($old_evaluation->fnum);
						$evaluation_data['evaluator'] = $old_evaluation->evaluator;
						$evaluation_data['step_id'] = $evaluation_step_id;

						unset($evaluation_data['id']);
						$evaluation_data = (object) $evaluation_data;
						try {
							$inserted = $db->insertObject($step_data->table, $evaluation_data);
							if ($inserted) {
								$migrated_task[] = true;
								$new_row_id = $db->insertid();
								$mapping_old_row_id_new_row_id[$old_evaluation->id] = $new_row_id;

								$evaluation_row_index = new IndexEntity(0, 'evaluation_row_id', $old_evaluation->id, $new_row_id, [
									'old_table' => $old_fabrik_table,
									'new_table' => $step_data->table
								]);
								$evaluation_row_index->save();
							} else {
								$migrated_task[] = false;
								$this->output->writeln($this->colors['red'] . 'Failed to insert evaluation data of row id ' . $old_evaluation->id  . ' found in table ' . $old_fabrik_table . ' to new table ' . $step_data->table . $this->colors['reset']);
								Log::add('Failed to insert evaluation data of row id ' . $old_evaluation->id  . ' in table ' . $old_fabrik_table, Log::ERROR, self::getJobName());
							}
						} catch (\Exception $e) {
							$this->output->writeln('Failed to insert evaluation data of row id ' . $old_evaluation->id  . ' found in table ' . $old_fabrik_table . ' to new table ' . $step_data->table . ': ' . $e->getMessage());

							$migrated_task[] = false;
							Log::add('Failed to insert evaluation data: ' . $e->getMessage(), Log::ERROR, self::getJobName());
						}
					}

					$group_ids = array_unique(array_column($elements, 'group_id'));
					$migrated_task[] = $this->migrateDataFromRepeatableGroups($group_ids, $step_data, $mapping_old_row_id_new_row_id);

					// do the same with repeatable elements
					$migrated_task[] = $this->migrateDataFromRepeatableElements($step_data, $elements, $mapping_old_row_id_new_row_id);
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
			$query->select('ei.new_index')
				->from($db->quoteName('#__emundus_indexes', 'ei'))
				->where('ei.old_index = ' .  $db->quote($old_group_id))
				->andWhere('JSON_EXTRACT(ei.params, "$.new_form_id") = ' . $new_form_id)
				->andWhere('ei.label = ' . $db->quote('fabrik_group_id'));

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
	 *
	 * @return bool
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

	private function wasEvaluationMultiple(int $new_evaluation_id): bool
	{
		$multiple = false;

		if (!empty($new_evaluation_id)) {
			$old_form_id = $this->searchIndex($new_evaluation_id, 'fabrik_form_id', 'old');

			if (!empty($old_form_id)) {
				$db = $this->databaseService->getDatabase();
				$query = $db->createQuery();
				$query->select('db_table_name')
					->from($db->quoteName('#__fabrik_lists'))
					->where('form_id = ' . $old_form_id);

				$db->setQuery($query);
				$old_table = $db->loadResult();

				if ($old_table === 'jos_emundus_evaluations') {
					$em_config = ComponentHelper::getParams('com_emundus');
					$multi_eval_parameter = $em_config->get('multi_eval', 0);
					$multiple = $multi_eval_parameter == 1;
				}
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

	private function getEvaluationIntro(int $formid)
	{
		$intro = [
			'fr' => '',
			'en' => ''
		];

		if (!empty($formid)) {
			$db = $this->databaseService->getDatabase();
			$query = $db->createQuery();
			$query->select('intro')
				->from($db->quoteName('#__fabrik_forms'))
				->where('id = ' . $formid);

			$db->setQuery($query);
			$form_intro = $db->loadResult();

			if (!empty($form_intro)) {
				$form_intro = strip_tags($form_intro);
				if (!class_exists('EmundusModelTranslations')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/translations.php');
				}

				$m_translations = new \EmundusModelTranslations();
				$translations = $m_translations->getTranslations('override', '*', '', '', '', 0,'', $form_intro);

				foreach($translations as $translation) {
					if ($translation->lang_code == 'fr-FR') {
						$intro['fr'] =  $translation->override;
					} elseif ($translation->lang_code == 'en-GB') {
						$intro['en'] =  $translation->override;
					} else {
						$short_code = substr($translation->lang_code, 0, 2);
						$intro[$short_code] =  $translation->override;
					}
				}
			}
		}

		return $intro;
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
