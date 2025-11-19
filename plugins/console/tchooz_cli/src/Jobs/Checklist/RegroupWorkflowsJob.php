<?php

namespace Emundus\Plugin\Console\Tchooz\Jobs\Checklist;

use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use EmundusModelWorkflow;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Repositories\Workflow\WorkflowRepository;

require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');

class RegroupWorkflowsJob extends TchoozChecklistJob
{
	private OutputInterface $output;

	private EmundusModelWorkflow $m_workflow;

	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService,
	)
	{
		parent::__construct($logger);

		$this->m_workflow = new EmundusModelWorkflow();
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$workflows = $this->m_workflow->getWorkflows();

		if (!empty($workflows)) {
			$groupedWorkflows = self::getWorkflowsGroupedBySameSignature();

			// fusion grouped workflows, ask question before proceeding
			$foundSimilarWorkflows = false;
			foreach ($groupedWorkflows as $signature => $similarWorkflows) {
				if (count($similarWorkflows) > 1) {
					$foundSimilarWorkflows = true;
					$output->writeln(sprintf('Regrouping %d similar workflows (signature: %s)', count($similarWorkflows), $signature));

					// list the workflows to be regrouped
					foreach ($similarWorkflows as $wf) {
						assert($wf instanceof WorkflowEntity);
						$output->writeln(sprintf(' - Workflow ID: %d, Name: %s', $wf->getId(), $wf->getLabel()));
					}

					$helper = new QuestionHelper();
					$question = new ConfirmationQuestion('Do you want to proceed with regrouping these workflows? (y/n) ', false);
					$proceed = $helper->ask($input, $output, $question);

					if ($proceed) {
						$output->writeln('Regrouping process initiated...');
						$regrouped = $this->regroupWorkflows($similarWorkflows);

						if ($regrouped) {
							$output->writeln('Workflows regrouped successfully.');
						} else {
							$output->writeln('An error occurred during the regrouping process.');
						}
					} else {
						$output->writeln('Regrouping process skipped by user.');
					}
				}
			}

			if (!$foundSimilarWorkflows) {
				$output->writeln('No similar workflows found to regroup.');
			}
		}
	}

	/**
	 * @return array<string, array<WorkflowEntity>>
	 */
	public static function getWorkflowsGroupedBySameSignature(): array
	{
		$groupedWorkflows = [];

		$repository = new WorkflowRepository();
		$workflows = $repository->getWorkflows();

		foreach ($workflows as $workflow) {
			$signature = self::generateWorkflowSignature($workflow->getSteps());
			if (!isset($groupedWorkflows[$signature])) {
				$groupedWorkflows[$signature] = [];
			}
			$groupedWorkflows[$signature][] = $workflow;
		}

		return $groupedWorkflows;
	}

	/**
	 * @param   array<StepEntity>  $steps
	 *
	 * @return string
	 */
	public static function generateWorkflowSignature(array $steps): string
	{
		$signatureParts = [];

		foreach ($steps as $step) {
			$signatureParts[] = implode('|', [
				$step->getLabel(),
				$step->getType()->getId(),
				$step->getProfileId(),
				$step->getFormId(),
				implode(',', $step->getEntryStatus()),
				$step->getOutputStatus(),
				$step->getMultiple(),
				$step->getState(),
				$step->getOrdering()
			]);
		}

		return md5(implode(';', $signatureParts));
	}

	/**
	 * @param   array<WorkflowEntity>  $workflows
	 *
	 * @return bool
	 */
	public static function regroupWorkflows(array $workflows): bool
	{
		$repository = new WorkflowRepository();

		// Actual regrouping logic to be implemented here
		// keep only the first workflow, delete others, reassign programs, etc.
		$allProgramIds = [];

		$keptWorkflow = $workflows[0];
		foreach ($workflows as $index => $workflow) {
			if ($index === 0) {
				// Keep the first workflow
				continue;
			}

			$allProgramIds = array_merge($allProgramIds, $workflow->getProgramIds());

			// verify step dates before deleting, if steps have campaign dates, we should transfer them to the first workflow corresponding steps
			$steps = $workflow->getSteps();
			$firstWorkflowSteps = $keptWorkflow->getSteps();

			foreach ($steps as $step) {
				if (!empty($step->getCampaignsDates()))
				{
					// find corresponding step in first workflow
					foreach ($firstWorkflowSteps as $firstWorkflowStep) {
						if ($firstWorkflowStep->getLabel() === $step->getLabel() && $firstWorkflowStep->getType()->getId() === $step->getType()->getId()) {
							// transfer campaign dates
							$firstStepCampaignDates = $firstWorkflowStep->getCampaignsDates();
							foreach ($step->getCampaignsDates() as $campaignDate) {
								$campaignDate->setStepId($firstWorkflowStep->getId());
								$campaignDate->setId(0); // reset ID to avoid conflicts
								$firstStepCampaignDates[] = $campaignDate;
							}

							$firstWorkflowStep->setCampaignsDates($firstStepCampaignDates);
							break;
						}
					}

					$keptWorkflow->setSteps($firstWorkflowSteps);
				}
			}

			$repository->delete($workflow);
		}

		$firstWorkflowProgramIds = $keptWorkflow->getProgramIds();
		$uniqueProgramIds = array_unique(array_merge($firstWorkflowProgramIds, $allProgramIds));

		$keptWorkflow->setProgramIds($uniqueProgramIds);

		return $repository->save($keptWorkflow);
	}

	public static function getJobName(): string {
		return 'Workflows Assemble!';
	}

	public static function getJobDescription(): ?string {
		return 'Après la migration des anciens workflow et des évaluations, il peut être souhaitable de fusionner les workflows qui seraient similaires afin d\'optimiser leur gestion et leur exécution.';
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}