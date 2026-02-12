<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Payment\PaymentRepository;

class onBeforeEmundusCartRenderDefinition extends EventDefinition
{
	public const PAYMENT_STEP_KEY = 'payment_step';

	public function __construct()
	{
		parent::__construct(
			'onBeforeEmundusCartRender',
			[
				new ChoiceField(self::PAYMENT_STEP_KEY, Text::_('COM_EMUNDUS_CURRENT_CART_STEP'), $this->getPaymentStepsList(), false, true),
			]
		);
	}

	private function getPaymentStepsList(): array
	{
		$options = [];

		if (!class_exists('EmundusModelWorkflow'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/workflow.php';
		}
		$m_workflow = new \EmundusModelWorkflow();
		$repository = new PaymentRepository();
		$steps = $m_workflow->getSteps(0, $repository->getPaymentStepTypeIds());

		foreach ($steps as $step)
		{
			assert($step instanceof StepEntity);
			$options[] = new ChoiceFieldValue($step->getId(), $step->getLabel());
		}


		return $options;
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE, TargetTypeEnum::USER];
	}
}