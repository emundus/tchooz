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
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;

class MigrateWorkflowsJob extends TchoozChecklistJob
{

	private ?\EmundusModelWorkflow $m_workflow = null;
	private ?\EmundusModelProgramme $m_program = null;

	private ?\EmundusModelCampaign $m_campaign = null;

	private OutputInterface $output;

	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService,
	)
	{
		$this->allowFailure = false;

		require_once(JPATH_ROOT. '/components/com_emundus/models/workflow.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/programme.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/campaign.php');
		$this->m_workflow = new \EmundusModelWorkflow();
		$this->m_program = new \EmundusModelProgramme();
		$this->m_campaign = new \EmundusModelCampaign();

		parent::__construct($logger);
	}


	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$helper = new QuestionHelper();
		$question = new ConfirmationQuestion('Confirm migration of workflows from source to destination? [y/n]', false);
		if (!$helper->ask($input, $output, $question)) {
			return;
		}

		$old_workflows = $this->getOldWorkflows();

		$old_workflows_with_campaigns = array_filter($old_workflows, function($workflow) {
			return !empty($workflow['campaign_ids']);
		});

		if (!empty($old_workflows_with_campaigns)) {
			$output->writeln('====================================');
			$output->writeln('There are ' . count($old_workflows_with_campaigns) . ' workflows with campaign mapping. The migration will duplicate the campaign\'s programs and associate them to new workflows.');
			$question = new ConfirmationQuestion('Do you still want to proceed? [y/n]', false);

			if (!$helper->ask($input, $output, $question)) {
				$output->writeln('Workflows migration aborted');

				// stop the execution
				return;
			}
		}

		$this->output = $output;

		if (!empty($old_workflows)) {
			$section = $this->output->section();
			$progressBar = new EmundusProgressBar($section, count($old_workflows));
			$progressBar->setMessage('Migrating old workflows from source to destination');
			$progressBar->start();

			Log::add('Migrating workflows - Found ' . count($old_workflows) . ' old workflows to migrate.', Log::INFO, self::getJobName());

			$succeeded = $this->migrateWorkflows($old_workflows, $progressBar);
			if ($succeeded) {
				$progressBar->finish('Migrated workflows');

				Log::add('Workflows migrated successfully', Log::INFO, self::getJobName());
			} else {
				$progressBar->finish('Error while migrating workflows');
				Log::add('Error while migrating workflows', Log::ERROR, self::getJobName());
				throw new \RuntimeException('Error while migrating workflows');
			}
		} else {
			Log::add('No workflows to migrate', Log::INFO, self::getJobName());
		}
	}

	private function getOldWorkflows(): array
	{
		$workflows = [];

		$db = $this->databaseServiceSource->getDatabase();

		$query = $db->getQuery(true)
			->select('esw.*, GROUP_CONCAT(DISTINCT eswrp.programs) as program_codes, GROUP_CONCAT(DISTINCT eswrc.campaign) as campaign_ids, GROUP_CONCAT(DISTINCT eswres.entry_status) as entry_status')
			->from($db->quoteName('jos_emundus_campaign_workflow', 'esw'))
			->leftJoin($db->quoteName('jos_emundus_campaign_workflow_repeat_campaign', 'eswrc'), 'eswrc.parent_id = esw.id')
			->leftJoin($db->quoteName('jos_emundus_campaign_workflow_repeat_programs', 'eswrp'), 'eswrp.parent_id = esw.id')
			->leftJoin($db->quoteName('jos_emundus_campaign_workflow_repeat_entry_status', 'eswres'), 'eswres.parent_id = esw.id')
			->group('esw.id');

		try {
			$db->setQuery($query);
			$workflows = $db->loadAssocList();

			if (!empty($workflows)) {
				foreach ($workflows as $key => $workflow) {
					$workflows[$key]['program_codes'] = !empty($workflow['program_codes']) ? explode(',', $workflow['program_codes']) : [];
					$workflows[$key]['campaign_ids'] = !empty($workflow['campaign_ids']) ? explode(',', $workflow['campaign_ids']) : [];
					$workflows[$key]['entry_status'] = explode(',', $workflow['entry_status']);
				}
			}
		} catch (\Exception $e) {
			Log::add('Error while fetching old workflows: ' . $e->getMessage(), Log::ERROR, self::getJobName());
			throw new \Exception('Error while fetching old workflows');
		}

		return $workflows;
	}

	/**
	 * @param   array  $old_workflows
	 *
	 * @return bool
	 */
	public function migrateWorkflows(array $workflows, EmundusProgressBar $progressBar): bool
	{
		$succeeded = false;

		if (!empty($workflows)) {
			$global_steps = []; // an old workflow associated to no one thus global
			$steps_by_program = [];
			$steps_by_campaign = [];

			$this->databaseService->startTransaction();

			foreach ($workflows as $step) {
				$new_step_entity = $this->transformStep($step);

				if (empty($step['program_codes']) && empty($step['campaign_ids'])) {
					$global_steps[] = $new_step_entity;
				} else {
					foreach ($step['program_codes'] as $program_code) {
						$steps_by_program[$program_code][] = $new_step_entity;
					}

					foreach ($step['campaign_ids'] as $campaign_id) {
						$steps_by_campaign[$campaign_id][] = $new_step_entity;
					}
				}
			}

			Log::add('Migrating ' . count($global_steps) . ' global steps.', Log::INFO, self::getJobName());
			Log::add('Migrating ' . count($steps_by_program) . ' steps by program.', Log::INFO, self::getJobName());
			Log::add('Migrating ' . count($steps_by_campaign) . ' steps by campaign.', Log::INFO, self::getJobName());

			$tasks = [];
			if (!empty($global_steps)) {
				Log::add('Migrating ' . count($global_steps) . ' global steps. Starting to create a workflow for each program.', Log::INFO, self::getJobName());
				$programs = $this->m_program->getProgrammes();

				foreach ($programs as $program) {
					$added = $this->addStepsToProgram($program, $global_steps);

					if ($added) {
						Log::add('Global steps added to program ' . $program['code'], Log::INFO, self::getJobName());
					} else {
						Log::add('Error while associating global steps to program ' . $program['code'], Log::ERROR, self::getJobName());
					}

					$tasks[] = $added;
					$progressBar->advance();
				}
			}

			if (!empty($steps_by_program)) {
				Log::add('Migrating ' . count($steps_by_program) . ' steps by program. Starting to create a workflow for each program.', Log::INFO, self::getJobName());

				foreach ($steps_by_program as $program_code => $steps) {
					Log::add('Migrating ' . count($steps) . ' steps for program ' . $program_code, Log::INFO, self::getJobName());

					$programmes = $this->m_program->getProgrammes(null, ['IN' => [$program_code]]);
					$program = !empty($programmes) ? current($programmes) : null;

					if (!empty($program)) {
						$added = $this->addStepsToProgram($program, $steps);

						if ($added) {
							Log::add('Steps added to program ' . $program_code, Log::INFO, self::getJobName());
						} else {
							Log::add('Error while associating steps to program ' . $program_code, Log::ERROR, self::getJobName());
						}

						$tasks[] = $added;
						$progressBar->advance();
					} else {
						Log::add('Error while fetching program by code ' . $program_code, Log::ERROR, self::getJobName());
						$tasks[] = false;
					}
				}
			}

			if (!empty($steps_by_campaign)) {
				foreach ($steps_by_campaign as $campaign_id => $steps) {
					$program = $this->m_campaign->getProgrammeByCampaignID($campaign_id);

					if (!empty($program)) {
						if ($this->needToDuplicateProgram($program, $campaign_id)) {
							$program['label'] = $program['label'] . ' - ' . $campaign_id;
							$new_program = $this->duplicateProgram($program);
							Log::add('Program ' . $program['code'] . ' duplicated to ' . $new_program['code'], Log::INFO, self::getJobName());
						} else {
							$new_program = $program;
							Log::add('No need to duplicate program ' . $program['code'], Log::INFO, self::getJobName());
						}

						if (!empty($new_program)) {
							$campaign = $this->m_campaign->getCampaignByID($campaign_id);
							$updated = $this->m_campaign->updateCampaign(
								[
									'start_date' => $campaign['start_date'],
									'end_date' => $campaign['end_date'],
									'training' => $new_program['code']
								], $campaign_id
							);

							if ($updated) {
								Log::add('Campaign ' . $campaign_id . ' updated with new training code ' . $new_program['code'], Log::INFO, self::getJobName());
								$program_workflow_data = $this->m_workflow->getWorkflow(0, [$program['id']]);
								$program_workflow_id = !empty($program_workflow_data['workflow']->id) ? $program_workflow_data['workflow']->id : null;

								if (!empty($program_workflow_id)) {
									$new_workflow_id = $this->m_workflow->duplicateWorkflow($program_workflow_id);
									Log::add('Workflow ' . $program_workflow_id . ' duplicated to ' . $new_workflow_id, Log::INFO, self::getJobName());

									$new_workflow = $this->m_workflow->getWorkflow($new_workflow_id);
									Log::add('Workflow ' . $new_workflow_id . ' fetched', Log::INFO, self::getJobName());

									foreach ($new_workflow['steps'] as $key => $step) {
										$step->entry_status = array_map(function ($entry_status) {
											return ['id' => $entry_status, 'label' => ''];
										}, $step->entry_status);

										$new_workflow['steps'][$key] = (array)$step;
									}

									$updated = $this->m_workflow->updateWorkflow((array)$new_workflow['workflow'], $new_workflow['steps'], [['id' => $new_program['id']]]);

									if ($updated) {
										Log::add('Workflow ' . $new_workflow_id . ' updated with new program ' . $new_program['code'], Log::INFO, self::getJobName());
									} else {
										Log::add('Error while updating workflow ' . $new_workflow_id . ' with new program ' . $new_program['code'], Log::ERROR, self::getJobName());
									}
								}

								$added = $this->addStepsToProgram($new_program, $steps);

								if ($added) {
									Log::add('Steps added to program ' . $new_program['code'], Log::INFO, self::getJobName());
								} else {
									Log::add('Error while associating steps to program ' . $new_program['code'], Log::ERROR, self::getJobName());
								}

								$tasks[] = $added;
								$progressBar->advance();
							} else {
								Log::add('Error while updating campaign ' . $campaign_id . ' with new training code ' . $new_program['code'], Log::ERROR, self::getJobName());
								$tasks[] = false;
							}
						} else {
							Log::add('Error while duplicating program ' . $program['code'], Log::ERROR, self::getJobName());
							$tasks[] = false;
						}
					} else {
						Log::add('Error while fetching program by campaign id ' . $campaign_id, Log::ERROR, self::getJobName());
						$tasks[] = false;
					}
				}
			}

			$this->databaseService->commitTransaction();

			$succeeded = !in_array(false, $tasks) && !empty($tasks);
		}

		return $succeeded;
	}

	/**
	 * Transform an old J3 workflow step to a new J5 workflow step entity
	 * @param   array  $step
	 *
	 * @return array|int[]
	 */
	private function transformStep(array $step): array
	{
		$new_step = [];

		if (!empty($step)) {
			$new_entry_status = [];

			foreach ($step['entry_status'] as $step_entry_status)
			{
				$new_entry_status[] = [
					'id' => $step_entry_status,
					'label' => ''
				];
			}

			$new_step = [
				'id' => 0,
				'label' => $this->getProfileLabel($step['profile']) ?? 'Étape [Profile ' . $step['profile'] . ']',
				'workflow_id' => 0,
				'type' => 1, // means applicant
				'profile_id' => $step['profile'],
				'form_id' => null,
				'entry_status' => $new_entry_status,
				'output_status' => $step['output_status'],
				'multiple' => 0,
				'state' => 1
			];
		}

		return $new_step;
	}

	private function duplicateProgram($program): array
	{
		$new_program = [];

		if (!empty($program)) {
			$program['code'] = '';
			unset($program['id']);

			$response = $this->m_program->addProgram($program);

			if (!empty($response['programme_id'])) {
				$new_program = $program;
				$new_program['id'] = $response['programme_id'];
				$new_program['code'] = $response['programme_code'];
			}
		}

		return $new_program;
	}

	/**
	 * @param   array  $program
	 * @param   array  $steps
	 *
	 * @return bool
	 */
	private function addStepsToProgram(array $program, array $new_steps): bool
	{
		$added = false;

		if (!empty($program) && !empty($new_steps)) {
			$workflow_data = $this->m_workflow->getWorkflow(0, [$program['id']]);

			if (empty($workflow_data['workflow']->id)) {
				$workflow_id = $this->m_workflow->add('Workflow - ' . $program['label']);

				if (!empty($workflow_id)) {
					Log::add('Workflow created with id ' . $workflow_id . ' for program ' . $program['id'], Log::INFO, self::getJobName());
					$workflow_data = $this->m_workflow->getWorkflow($workflow_id);
				} else {
					Log::add('Failed to generate a new workflow for porgram ' . $program['id'], Log::ERROR, self::getJobName());
				}
			}

			if (!empty($workflow_data['workflow']->id)) {
				foreach ($new_steps as $key => $step) {
					$new_steps[$key]['workflow_id'] = $workflow_data['workflow']->id;
				}

				foreach ($workflow_data['steps'] as $key => $step) {
					$step->entry_status = array_map(function ($entry_status) {
						return ['id' => $entry_status, 'label' => ''];
					}, $step->entry_status);

					$workflow_data['steps'][$key] = (array)$step;
				}

				try {
					$steps = array_merge($workflow_data['steps'], $new_steps);
					$updated = $this->m_workflow->updateWorkflow((array)$workflow_data['workflow'], $steps, [['id' => $program['id']]]);
				} catch (\Exception $e) {
					Log::add('Error while adding steps to workflow ' . $workflow_data['workflow']->id . ' for program ' . $program['id'] . ': ' . $e->getMessage(), Log::ERROR, self::getJobName());

					$this->output->writeln('Error while adding steps to workflow ' . $workflow_data['workflow']->id . ' for program ' . $program['id'] . ': ' . $e->getMessage());
					$added = true;
				}

				if ($updated) {
					Log::add('Steps added to workflow ' . $workflow_data['workflow']->id, Log::INFO, self::getJobName());
					$added = true;
				} else {
					Log::add('Error while adding steps to workflow ' . $workflow_data['workflow']->id, Log::ERROR, self::getJobName());
				}
			}
		}

		return $added;
	}

	private function getProfileLabel($profile_id) {
		$profile_label = '';

		if (!empty($profile_id)) {
			$db = $this->databaseServiceSource->getDatabase();
			$query = $db->getQuery(true)
				->select('label')
				->from($db->quoteName('jos_emundus_setup_profiles'))
				->where($db->quoteName('id') . ' = ' . $db->quote($profile_id));

			try {
				$db->setQuery($query);
				$profile_label = $db->loadResult();
			} catch (\Exception $e) {
				Log::add('Error while fetching profile label: ' . $e->getMessage(), Log::ERROR, self::getJobName());
			}
		}

		return $profile_label;
	}

	private function needToDuplicateProgram($program, $campaign_id): bool
	{
		$duplicate = true;

		$db = $this->databaseServiceSource->getDatabase();
		$query = $db->createQuery();

		$query->select('id')
			->from($db->quoteName('jos_emundus_setup_campaigns'))
			->where($db->quoteName('training') . ' = ' . $db->quote($program['code']));
		$campaigns = $db->setQuery($query)->loadColumn();

		if (count($campaigns) === 1 && $campaigns[0] == $campaign_id) {
			$duplicate = false;
		}

		return $duplicate;
	}

	public static function getJobName(): string {
		return 'Workflows';
	}

	public static function getJobDescription(): ?string {
		return 'Migrate emundus_campaign_workflow to emundus_setup_workflows';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}
