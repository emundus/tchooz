<?php

namespace Tchooz\Factories\Workflow;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Workflow\CampaignStepDateEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Enums\Workflow\WorkflowStepDateRelativeToEnum;
use Tchooz\Enums\Workflow\WorkflowStepDatesRelativeUnitsEnum;
use Tchooz\Repositories\Workflow\CampaignStepDateRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;

class StepFactory
{
	public static function fromDbObjects(array $dbObjects): array
	{
		$steps = [];

		if (!empty($dbObjects))
		{
			$stepTypeRepository = new StepTypeRepository();
			$campaignStepDateRepository = new CampaignStepDateRepository();

			foreach ($dbObjects as $dbObject)
			{
				if (!empty($dbObject->form_id) && (empty($dbObject->table) || empty($dbObject->table_id)))
				{
					$factory = new self();
					$table = $factory->getEvaluationTable($dbObject->form_id);

					if ($table) {
						$dbObject->table = $table->name;
						$dbObject->table_id = $table->id;
					} else {
						Log::add('No evaluation table found for form id: ' . $dbObject->form_id, Log::ERROR, 'com_emundus.workflow');
					}
				}

				$steps[] = new StepEntity(
					id: $dbObject->id,
					workflow_id: $dbObject->workflow_id,
					label: $dbObject->label,
					type: $stepTypeRepository->getStepTypeById($dbObject->type),
					profile_id: $dbObject->profile_id,
					form_id: $dbObject->form_id,
					entry_status: !is_null($dbObject->entry_status) ? explode(',', $dbObject->entry_status) : [],
					output_status: $dbObject->output_status,
					multiple: $dbObject->multiple ?? 0,
					state: $dbObject->state,
					ordering: $dbObject->ordering,
					table: $dbObject->table ?? '',
					table_id: $dbObject->table_id ?? 0,
					campaignsDates: $campaignStepDateRepository->getCampaignsDatesByStepId($dbObject->id),
				);
			}
		}

		return $steps;
	}


	/**
	 * @param   int  $formId
	 *
	 * @return object|null
	 */
	private function getEvaluationTable(int $formId): ?object
	{
		$table = null;

		if (!empty($formId))
		{
			$db = Factory::getContainer();
			$query = $db->createQuery();
			$query->select('db_table_name, id')
				->from('#__fabrik_lists')
				->where('form_id = ' . $formId);

			try {
				$db->setQuery($query);
				$table_data = $db->loadAssoc();

				if (!empty($table_data) && !empty($table_data['db_table_name'])) {
					$table = new \stdClass();
					$table->name = $table_data['db_table_name'];
					$table->id = (int)$table_data['id'];
				} else {
					Log::add('No table found for form id: ' . $formId, Log::ERROR, 'com_emundus.workflow');
					throw new \Exception('No table found for form id: ' . $formId);
				}
			} catch (\Exception $e) {
				Log::add('Error while fetching form table name: ' . $e->getMessage(), Log::ERROR, 'com_emundus.workflow');
			}
		}

		return $table;
	}

	/**
	 * An old step correspond to the structure used in Tchooz 1.x
	 * Usefull when migrating from Tchooz 1.x to Tchooz 2.x
	 * @param   array  $oldStep
	 *
	 * @return StepEntity|null
	 * @throws \Exception
	 */
	public static function fromV1Step(array $oldStep): ?StepEntity
	{
		$step = null;

		if (!empty($oldStep))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			// set a default label
			$oldStep['label'] = 'Workflow Step';
			if (!empty($oldStep['profile']))
			{
				$query->clear()
					->select('label')
					->from($db->quoteName('#__emundus_setup_profiles'))
					->where($db->quoteName('id') . ' = ' . (int)$oldStep['profile']);

				$db->setQuery($query);
				$profileLabel = $db->loadResult();
				$oldStep['label'] = !empty($profileLabel) ? $profileLabel : 'Profile Step';
			}

			$stepTypeRepository = new StepTypeRepository();
			$step = new StepEntity(
				id: 0,
				workflow_id: 0,
				label: $oldStep['label'],
				type: $stepTypeRepository->getStepTypeById(1),
				profile_id: $oldStep['profile'] ?? 0,
				form_id: null,
				entry_status: $oldStep['entry_status'] ?? [0],
				output_status: $oldStep['output_status'] ?? null,
				multiple: 0,
				state: 1,
				ordering: 0,
				table: '',
				table_id: 0
			);

			// if $oldStep has dates, we need to create CampaignStepDateEntity objects foreach campaign ids the step is linked to
			if (!empty($oldStep['start_date']) || !empty($oldStep['end_date']))
			{
				// campaignIds is a fusion of $oldStep['campaign_ids'] and also all campaigns attached to $oldStep['program_ids']
				$campaignIds = $oldStep['campaign_ids'] ?? [];
				if (!empty($oldStep['program_ids']))
				{
					$query->clear()
						->select('esc.id')
						->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
						->leftJoin($db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $db->quoteName('esc.training') . ' = ' . $db->quoteName('esp.code'))
						->where($db->quoteName('esp.id') . ' IN (' . implode(',', $oldStep['program_ids']) . ')');

					$db->setQuery($query);
					$programCampaigns = $db->loadColumn();
					$campaignIds = array_unique(array_merge($campaignIds, $programCampaigns));
				}

				if (!empty($campaignIds))
				{
					$campaignDates = [];
					foreach ($campaignIds as $campaignId)
					{
						$campaignDates[] = new CampaignStepDateEntity(
							id: 0,
							campaignId: $campaignId,
							stepId: 0,
							startDate: !empty($oldStep['start_date']) ? new \DateTimeImmutable($oldStep['start_date']) : null,
							endDate: !empty($oldStep['end_date']) ? new \DateTimeImmutable($oldStep['end_date']) : null,
							infinite: false,
							relativeDate: false,
							relativeTo: WorkflowStepDateRelativeToEnum::STATUS,
							relativeStartDateValue: 0,
							relativeStartDateUnit: WorkflowStepDatesRelativeUnitsEnum::DAY,
							relativeEndDateValue: 0,
							relativeEndDateUnit: WorkflowStepDatesRelativeUnitsEnum::DAY
						);
					}
					$step->setCampaignsDates($campaignDates);
				}
			}
		}

		return $step;
	}
}