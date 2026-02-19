<?php

namespace Tchooz\Factories\Workflow;

use Joomla\CMS\Factory;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Joomla\Database\DatabaseDriver;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Workflow\StepRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;

class WorkflowFactory
{
	/**
	 * @param   array                       $dbObjects
	 * @param   bool                        $loadChilds
	 * @param   ApplicationFileEntity|null  $applicationFile
	 *
	 * @return array<WorkflowEntity>
	 * @throws \Exception
	 */
	public static function fromDbObjects(array $dbObjects, bool $loadChilds = false, ?ApplicationFileEntity $applicationFile = null): array
	{
		$workflows = [];

		if (!empty($dbObjects))
		{
			$stepRepository = new StepRepository();
			$campaignRepository = new CampaignRepository();
			$workflowRepository = new WorkflowRepository();

			foreach ($dbObjects as $dbObject)
			{
				// todo: check if steps are in dbObject, if so, use StepFactory to create them instead of querying again
				$steps = $stepRepository->getStepsByWorkflowId($dbObject->id);

				$programIds = !empty($dbObject->program_ids) ? explode(',', $dbObject->program_ids) : [];
				$programIds = array_map('intval', $programIds);

				$childWorkflows = [];
				if ($loadChilds)
				{
					if (!empty($applicationFile))
					{
						$campaignsIds = [$applicationFile->getCampaign()->getId()];
					}
					else
					{
						$campaignsIds = $campaignRepository->getCampaignIdsByPrograms($programIds);
					}

					foreach ($campaignsIds as $campaignId)
					{
						$programsLinked = $campaignRepository->getLinkedProgramsIds($campaignId);
						$programsLinked = array_filter(array_unique($programsLinked));
						foreach ($programsLinked as $programId)
						{
							$workflow = $workflowRepository->getWorkflowByProgramId($programId);
							if(!empty($workflow) && $workflow->getId() !== $dbObject->id && !in_array($workflow->getId(), array_keys($childWorkflows)))
							{
								$childWorkflows[$workflow->getId()] = $workflow;
							}
						}
					}
				}

				// Check if the campaign have a parent
				$parentWorkflow = null;
				if(!empty($applicationFile) && !empty($applicationFile->getCampaign()->getParent()))
				{
					// Get workflow linked to parent campaign
					$workflow = $workflowRepository->getWorkflowByProgramId($applicationFile->getCampaign()->getParent()->getProgram()->getId());
					if(!empty($workflow) && $workflow->getId() !== $dbObject->id && !in_array($workflow->getId(), array_keys($childWorkflows)))
					{
						$parentWorkflow = $workflow;
					}
				}
				
				$workflows[] = new WorkflowEntity(
					id: $dbObject->id,
					label: $dbObject->label,
					published: $dbObject->published,
					steps: $steps,
					program_ids: $programIds,
					childWorkflows: $childWorkflows,
					parentWorkflow: $parentWorkflow
				);
			}
		}

		return $workflows;
	}
}