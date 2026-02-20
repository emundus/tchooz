<?php

namespace Tchooz\Entities\Automation\EventsDefinitions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\EventsDefinitions\Defaults\EventDefinition;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Payment\PaymentRepository;

class onAfterEmundusTransactionUpdateDefinition extends EventDefinition
{

	public CONST TRANSACTION_STATUS_PARAMETER = 'transaction_status';
	public CONST OLD_TRANSACTION_STATUS_PARAMETER = 'old_transaction_status';
	public CONST TRANSACTION_STEP_ID_PARAMETER = 'transaction_step_id';

	public function __construct()
	{
		parent::__construct('onAfterEmundusTransactionUpdate',
			[
				new ChoiceField(self::TRANSACTION_STATUS_PARAMETER, Text::_('COM_EMUNDUS_TRANSACTION_STATUS'), $this->getTransactionsStatusList(), false, true),
				new ChoiceField(self::OLD_TRANSACTION_STATUS_PARAMETER, Text::_('COM_EMUNDUS_OLD_TRANSACTION_STATUS'), $this->getTransactionsStatusList(), false, true),
				new ChoiceField(self::TRANSACTION_STEP_ID_PARAMETER, Text::_('COM_EMUNDUS_CURRENT_CART_STEP'), $this->getPaymentStepsList(), false, true)
			]
		);
	}

	public function supportTargetPredefinitionsCategories(): array
	{
		return [TargetTypeEnum::FILE];
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getTransactionsStatusList(): array
	{
		$statuses = [];

		foreach (TransactionStatus::cases() as $status) {
			$statuses[] = new ChoiceFieldValue($status->value, $status->getLabel());
		}

		return $statuses;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
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
}