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
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Tchooz\Entities\Workflow\CampaignStepDateEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Factories\Workflow\StepFactory;
use Tchooz\Repositories\Workflow\WorkflowRepository;

class MigrateWorkflowsJob extends TchoozChecklistJob
{
	private ?\EmundusModelWorkflow $m_workflow = null;
	private ?\EmundusModelProgramme $m_program = null;
	private ?\EmundusModelCampaign $m_campaign = null;

	private WorkflowRepository $workflowRepository;

	private OutputInterface $output;

	private ?InputInterface $input = null;

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
		$this->workflowRepository = new WorkflowRepository();

		parent::__construct($logger);
	}


	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->input = $input;
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

			$this->databaseService->startTransaction();
			$succeeded = $this->migrateWorkflows($old_workflows, $progressBar);
			if ($succeeded) {
				$this->databaseService->commitTransaction();
				$progressBar->finish('Migrated workflows');

				Log::add('Workflows migrated successfully', Log::INFO, self::getJobName());
			} else {
				$this->databaseService->rollbackTransaction();
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
	 * @param   array               $workflows
	 * @param   EmundusProgressBar  $progressBar
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function migrateWorkflows(array $workflows, EmundusProgressBar $progressBar): bool
	{
		$succeeded = false;

		if (!empty($workflows)) {
			$tasks = [];
			$global_steps = []; // an old workflow associated to no one thus global
			$steps_by_program = [];
			$steps_by_campaign = [];

			foreach ($workflows as $step) {
				$newStepEntity = StepFactory::fromV1Step($step);
				if (empty($step['program_codes']) && empty($step['campaign_ids'])) {
					$global_steps[] = $newStepEntity;
				} else {
					foreach ($step['program_codes'] as $program_code) {
						if (!isset($steps_by_program[$program_code]))
						{
							$steps_by_program[$program_code] = [];
						}

						$steps_by_program[$program_code][] = $newStepEntity;
					}

					foreach ($step['campaign_ids'] as $campaign_id) {
						if (!isset($steps_by_campaign[$campaign_id])) {
							$steps_by_campaign[$campaign_id] = [];
						}

						$steps_by_campaign[$campaign_id][] = $newStepEntity;
					}
				}
			}

			Log::add('Migrating ' . count($global_steps) . ' global steps.', Log::INFO, self::getJobName());
			Log::add('Migrating ' . count($steps_by_program) . ' steps by program.', Log::INFO, self::getJobName());
			Log::add('Migrating ' . count($steps_by_campaign) . ' steps by campaign.', Log::INFO, self::getJobName());

			if (!empty($global_steps)) {
				Log::add('Migrating ' . count($global_steps) . ' global steps. Starting to create a workflow for each program.', Log::INFO, self::getJobName());
				$programs = $this->m_program->getProgrammes();

				foreach ($programs as $program) {
					$added = $this->addStepsToProgram($program, $global_steps);

					if ($added) {
						Log::add('Global steps added to program ' . $program['code'], Log::INFO, self::getJobName());
					} else {
						Log::add('Error while associating global steps to program ' . $program['code'], Log::ERROR, self::getJobName());
						$this->output->writeln($this->colors['red'] . 'Error while associating global steps to program ' . $program['code'] . $this->colors['reset']);
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
							$this->output->writeln($this->colors['red'] . 'Error while associating steps to program ' . $program_code . $this->colors['reset']);
						}

						$tasks[] = $added;
						$progressBar->advance();
					} else {
						Log::add('Error while fetching program by code ' . $program_code, Log::ERROR, self::getJobName());
						$this->output->writeln($this->colors['red'] . 'Error while fetching program by code ' . $program_code . $this->colors['reset']);
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

								$workflowEntity      = $this->workflowRepository->getWorkflowByProgramId($program['id']);
								if (!empty($workflowEntity) && !empty($workflowEntity->getId()))
								{
									$newWorkflowEntity = $this->workflowRepository->duplicate($workflowEntity, 'Workflow - ' . $new_program['label'], [$new_program['id']]);

									if (!empty($newWorkflowEntity->getId()))
									{
										// attach the new program to the new workflow
										Log::add('Workflow ' . $workflowEntity->getId() . ' duplicated to ' . $newWorkflowEntity->getId(), Log::INFO, self::getJobName());
									} else
									{
										Log::add('Error while duplicating workflow ' . $workflowEntity->getId() . ' for program ' . $new_program['code'], Log::ERROR, self::getJobName());
										$this->output->writeln($this->colors['red'] . 'Error while duplicating workflow ' . $workflowEntity->getId() . ' for program ' . $new_program['code'] . $this->colors['reset']);
										$tasks[] = false;
									}
								}
							}
							else
							{
								Log::add('Error while updating campaign ' . $campaign_id . ' with new training code ' . $new_program['code'], Log::ERROR, self::getJobName());
								$this->output->writeln($this->colors['red'] . 'Error while updating campaign ' . $campaign_id . ' with new training code ' . $new_program['code'] . $this->colors['reset']);
								$tasks[] = false;
							}
						} else {
							Log::add('No need to duplicate program ' . $program['code'], Log::INFO, self::getJobName());
							$new_program = $program;
						}

						if (!empty($new_program)) {
							$campaignSteps = [];
							foreach ($steps as $step)
							{
								// clone the step to avoid modifying the original step
								$clonedStep = clone $step;
								assert($clonedStep instanceof StepEntity);

								if (!empty($clonedStep->getCampaignsDates())) {
									// keep only step dates related to this campaign
									$campaignDates = array_filter($clonedStep->getCampaignsDates(), function ($campaignDate) use ($campaign_id) {
										assert($campaignDate instanceof CampaignStepDateEntity);
										return $campaignDate->getCampaignId() == $campaign_id;
									});

									$clonedStep->setCampaignsDates($campaignDates);
								}
								$campaignSteps[] = $clonedStep;
							}

							$added = $this->addStepsToProgram($new_program, $campaignSteps);

							if ($added)
							{
								Log::add('Steps added to program ' . $new_program['code'], Log::INFO, self::getJobName());
							}
							else
							{
								Log::add('Error while associating steps to program ' . $new_program['code'], Log::ERROR, self::getJobName());
								$this->output->writeln($this->colors['red'] . 'Error while associating steps to program ' . $new_program['code'] . $this->colors['reset']);
								return false;
							}

							$tasks[] = $added;
							$progressBar->advance();
						} else {
							Log::add('Error while duplicating program ' . $program['code'], Log::ERROR, self::getJobName());
							$this->output->writeln($this->colors['red'] . 'Error while duplicating program ' . $program['code'] . $this->colors['reset']);
							$tasks[] = false;
						}
					} else {
						Log::add('Error while fetching program by campaign id ' . $campaign_id, Log::ERROR, self::getJobName());
						$this->output->writeln($this->colors['red'] . 'Error while fetching program by campaign id ' . $campaign_id . $this->colors['reset']);
						$tasks[] = false;
					}
				}
			}

			$succeeded = !in_array(false, $tasks) && !empty($tasks);
		}

		return $succeeded;
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
	 * Add steps to a program's workflow, creating the workflow if it does not exist
	 *
	 * @param   array              $program
	 * @param   array<StepEntity>  $newSteps
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function addStepsToProgram(array $program, array $newSteps): bool
	{
		$added = false;

		if (!empty($program['id']) && !empty($newSteps)) {
			$workflow = $this->workflowRepository->getWorkflowByProgramId($program['id']);

			if (empty($workflow) || empty($workflow->getId())) {
				$workflow = new WorkflowEntity(0, 'Workflow - ' . $program['label'], true, $newSteps, [$program['id']]);
				if ($this->workflowRepository->save($workflow))
				{
					$added = true;
				}
			} else {
				$allAdded = true;
				foreach ($newSteps as $newStep)
				{
					if (!$workflow->addStep($newStep))
					{
						// show the user why the step was not added, it could be because another step is on the same entry status, or an error
						// if it is because of entry status, show the conflicting steps
						// then ask the user to verify if acceptable

						$currentWorkflowSteps = $workflow->getApplicantSteps();
						$foundConflict        = false;
						foreach ($currentWorkflowSteps as $currentStep)
						{
							assert($currentStep instanceof StepEntity);

							if (!empty(array_intersect($currentStep->getEntryStatus(), $newStep->getEntryStatus())))
							{
								$foundConflict = true;

								$question = 'Conflict detected while adding step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] to existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'] . ".\n";
								$this->output->writeln($this->colors['yellow'] . ' Conflicting step : ' . $currentStep->getId() . ' [' . $currentStep->getLabel() . '] uses same entry status : ' . implode(',', $currentStep->getEntryStatus()) . $this->colors['reset']);
								// tell current conflicting step profile
								$this->output->writeln($this->colors['yellow'] . ' Conflicting step profile : ' . $currentStep->getLabel() . ' [' . $currentStep->getProfileId() . ']' . $this->colors['reset']);
								// tell new step profile
								$this->output->writeln($this->colors['yellow'] . ' New step profile: ' . $newStep->getLabel() . ' [' . $newStep->getProfileId() . ']' . $this->colors['reset']);

								// ask user to verify if acceptable
								$helper   = new QuestionHelper();
								// Instead, we should ask the user if he wants to keep the old step, replace it with the new one, or abort the migration
								// so it is not a yes or no question, but a choice
								$question .= 'Choose an option:' . "\n";
								$question .= '  [k]eep existing step ' . $currentStep->getId() . ' [' . $currentStep->getLabel() . ']' . "\n";
								$question .= '  [r]eplace with new step ' . $newStep->getId() . ' [' . $newStep->getLabel() . ']' . "\n";
								$question .= '  [a]bort migration' . "\n";
								$question .= 'Your choice (k/r/a): ';
								$choice = null;
								while (!in_array($choice, ['k', 'r', 'a'])) {
									$choice = trim($helper->ask($this->input, $this->output, new \Symfony\Component\Console\Question\Question($question)));
								}

								switch($choice)
								{
									case 'k':
										$this->output->writeln($this->colors['green'] . ' Step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] skipped for workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'] . $this->colors['reset']);
										Log::add('Step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] skipped for existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'], Log::INFO, self::getJobName());
										break;
									case 'r':
										$workflow->removeStep($currentStep->getId());

										if ($this->workflowRepository->save($workflow))
										{
											if (!$this->addStepsToProgram($program, [$newStep]))
											{
												$allAdded = false;
												$this->output->writeln($this->colors['red'] . ' Error while adding step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] to existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'] . $this->colors['reset']);
											} else {
												$this->output->writeln($this->colors['green'] . ' Step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] replaced existing step ' . $currentStep->getId() . ' [' . $currentStep->getLabel() . '] in workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'] . $this->colors['reset']);
											}
										} else {
											Log::add('Error while removing step ' . $currentStep->getId() . ' [' . $currentStep->getLabel() . '] from existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'], Log::ERROR, self::getJobName());
											$this->output->writeln($this->colors['red'] . ' Error while removing step ' . $currentStep->getId() . ' [' . $currentStep->getLabel() . '] from existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'] . $this->colors['reset']);
											$allAdded = false;
										}

										break;
									default:
										$this->output->writeln($this->colors['red'] . ' Step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] not added to existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'] . ', migration aborted.' . $this->colors['reset']);
										Log::add('Error while adding step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] to existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'] . ', migration aborted.', Log::ERROR, self::getJobName());

										return false;
										break;
								}
							}
						}

						if (!$foundConflict)
						{
							$this->output->writeln($this->colors['red'] . ' Step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] not added to existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'] . $this->colors['reset']);
							Log::add('Error while adding step ' . $newStep->getId() . ' [' . $newStep->getLabel() . '] to existing workflow ' . $workflow->getId() . ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' ' . $program['label'], Log::ERROR, self::getJobName());

							return false;
						}
					}
				}

				if ($allAdded && $this->workflowRepository->save($workflow))
				{
					$added = true;
				} else {
					$this->output->writeln('Error while adding steps to workflow ' . $workflow->getId(). ' [' . $workflow->getLabel() . '] for program ' . $program['id'] . ' [' . $program['label'] . ']');
					Log::add('Error while adding steps to existing workflow ' . $workflow->getId() . ' for program ' . $program['id'], Log::ERROR, self::getJobName());
				}
			}
		}

		return $added;
	}

	private function needToDuplicateProgram($program, $campaign_id): bool
	{
		$duplicate = true;

		$db = $this->databaseService->getDatabase();
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
