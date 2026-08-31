<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\EventCategoryEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Factories\Field\ChoiceFieldFactory;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\ColumnFilter;

class onAfterApplicationChoiceUpdateDefinition extends EventDefinition
{
	const NAME = 'onAfterApplicationChoiceUpdate';

	const IS_NEW_PARAMETER = 'is_new';
	const ORDER_PARAMETER = 'order';
	const STATE_PARAMETER = 'state';
	const CAMPAIGN_PARAMETER = 'campaign';

	// Carried by the event context but not declared as a field: there is nothing to filter on an id,
	// it is there for actions that will need to target this very choice
	const ID_PARAMETER = 'id';


	public function __construct()
	{
		$campaignOptions = $this->getCampaignOptions();

		parent::__construct(
			self::NAME,
			[
				new BooleanField(self::IS_NEW_PARAMETER, 'COM_EMUNDUS_AUTOMATION_EVENT_FIELD_CHOICE_IS_NEW', true),
				new NumericField(self::ORDER_PARAMETER, 'COM_EMUNDUS_AUTOMATION_EVENT_FIELD_CHOICE_ORDER', true),
				new ChoiceField(self::STATE_PARAMETER, 'COM_EMUNDUS_AUTOMATION_EVENT_FIELD_CHOICE_STATE', ChoiceFieldFactory::makeOptionsFromEnum(ChoicesStateEnum::cases()), false, true),
				(new ChoiceField(self::CAMPAIGN_PARAMETER, 'COM_EMUNDUS_AUTOMATION_EVENT_FIELD_CHOICE_CAMPAIGN', $campaignOptions, false, true))
			],
			EventCategoryEnum::CHOICES
		);
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE];
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getCampaignOptions(): array
	{
		$options = [];

		$campaignRepository = new CampaignRepository();
		$campaigns = $campaignRepository->get([
			new ColumnFilter('published', ConditionOperatorEnum::EQUALS, 1),
			new ColumnFilter('parent_id', ConditionOperatorEnum::IS_NOT_EMPTY)
		]);

		foreach ($campaigns as $campaign) {
			assert($campaign instanceof CampaignEntity);
			$options[] = new ChoiceFieldValue($campaign->getId(), $campaign->getLabel());
		}

		return $options;
	}
}