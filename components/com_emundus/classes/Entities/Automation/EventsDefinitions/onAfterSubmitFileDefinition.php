<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;

class onAfterSubmitFileDefinition extends Defaults\EventDefinition
{
	public CONST STEP_KEY = 'step';
	public CONST OLD_STATUS_KEY = 'old_status';
	public CONST STATUS_KEY = 'status';

	/** @var ChoiceFieldValue[] */
	private array $statusChoices = [];


	public function __construct()
	{
		parent::__construct('onAfterSubmitFile',
			[
				new ChoiceField(self::STEP_KEY, Text::_('COM_EMUNDUS_WORKFLOW_STEPS'), $this->getStepsChoices(), false, true, null, true),
				new ChoiceField(self::OLD_STATUS_KEY, Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_OLD_STATUS'), $this->getStatusChoices(), false, true),
				new ChoiceField(self::STATUS_KEY, Text::_('COM_EMUNDUS_AUTOMATION_EVENT_FIELD_NEW_STATUS'), $this->getStatusChoices(), false, true),
			]
		);
	}


	/**
	 * @inheritDoc
	 */
	public function supportTargetPredefinitionsCategories(): array
	{
		return [
			TargetTypeEnum::FILE
		];
	}

	/**
	 * @return ChoiceFieldValue[]
	 */
	private function getStepsChoices(): array
	{
		$options = [];

		if (!class_exists('EmundusModelWorkflow'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/workflow.php';
		}
		$m_workflow = new \EmundusModelWorkflow();
		$steps = $m_workflow->getSteps(0, [1]); // 1 is the step type id for applicant steps

		foreach ($steps as $step)
		{
			assert($step instanceof StepEntity);

			$workflowData = $m_workflow->getWorkflow($step->getWorkflowId());
			$options[] = new ChoiceFieldValue($step->getId(), Text::_($step->getLabel()), new FieldGroup($step->getWorkflowId(), $workflowData['workflow']->label));
		}

		return $options;
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
}