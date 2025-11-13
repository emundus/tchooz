<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterStatusChangeDefinition extends EventDefinition
{
	private array $statusChoices = [];

	public const OLD_STATUS_PARAMETER = 'old_status';
	public const STATUS_PARAMETER = 'status';


	public function __construct()
	{
		parent::__construct(
			'onAfterStatusChange',
			[
				new ChoiceField(self::OLD_STATUS_PARAMETER, 'COM_EMUNDUS_AUTOMATION_EVENT_FIELD_OLD_STATUS', $this->getStatusChoices(), false, true),
				new ChoiceField(self::STATUS_PARAMETER, 'COM_EMUNDUS_AUTOMATION_EVENT_FIELD_NEW_STATUS', $this->getStatusChoices(), false, true),
			]
		);
	}

	/**
	 *
	 * @return ChoiceFieldValue[]
	 */
	private function getStatusChoices(): array
	{
		if (!empty($this->statusChoices))
		{
			return $this->statusChoices;
		} else {
			if (!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files = new \EmundusModelFiles();
			$states  = $m_files->getAllStatus($this->getAutomatedTaskUserId());

			$choices = [];
			foreach ($states as $state)
			{
				$choices[] = new ChoiceFieldValue($state['step'], $state['value']);
			}

			$this->statusChoices = $choices;
		}

		return $this->statusChoices;
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE, TargetTypeEnum::USER];
	}
}