<?php

namespace Tchooz\Factories\Workflow;

use Tchooz\Entities\Workflow\CampaignStepDateEntity;
use Tchooz\Enums\Workflow\WorkflowStepDateRelativeToEnum;
use Tchooz\Enums\Workflow\WorkflowStepDatesRelativeUnitsEnum;

class CampaignStepDateFactory
{
	/**
	 * @param   array  $dbObjects
	 *
	 * @return array<CampaignStepDateEntity>
	 */
	public static function fromDbObjects(array $dbObjects): array
	{
		$dates = [];

		if (!empty($dbObjects))
		{
			foreach ($dbObjects as $dbObject)
			{
				$dates[] = new CampaignStepDateEntity(
					id: $dbObject->id,
					campaignId: $dbObject->campaign_id,
					stepId: $dbObject->step_id,
					startDate: !is_null($dbObject->start_date) ? new \DateTimeImmutable($dbObject->start_date) : null,
					endDate: !is_null($dbObject->end_date) ? new \DateTimeImmutable($dbObject->end_date) : null,
					infinite: (bool) $dbObject->infinite,
					relativeDate: (bool) $dbObject->relative_date,
					relativeTo: isset($dbObject->relative_to) ? WorkflowStepDateRelativeToEnum::from($dbObject->relative_to) : WorkflowStepDateRelativeToEnum::STATUS,
					relativeStartDateValue: (int) $dbObject->relative_start_date_value,
					relativeStartDateUnit: isset($dbObject->relative_start_date_unit) ? WorkflowStepDatesRelativeUnitsEnum::from($dbObject->relative_start_date_unit) : WorkflowStepDatesRelativeUnitsEnum::DAY,
					relativeEndDateValue: (int) $dbObject->relative_end_date_value,
					relativeEndDateUnit: isset($dbObject->relative_end_date_unit) ? WorkflowStepDatesRelativeUnitsEnum::from($dbObject->relative_end_date_unit) : WorkflowStepDatesRelativeUnitsEnum::DAY
				);
			}
		}

		return $dates;
	}
}