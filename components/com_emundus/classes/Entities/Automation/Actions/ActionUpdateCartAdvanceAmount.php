<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\PaymentStepEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Payment\CartRepository;

class ActionUpdateCartAdvanceAmount extends ActionEntity
{
	public const ADVANCE_AMOUNT_PARAMETER = 'advance_amount';
	public const RESET_ADVANCE_AMOUNT = 'reset_advance_amount';

	public static function getIcon(): ?string
	{
		return static::getCategory()?->getIcon();
	}

	public static function getType(): string
	{
		return 'update_cart_advance_amount';
	}

	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_ADVANCE_AMOUNT');
	}

	public static function getDescription(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_ADVANCE_AMOUNT_DESCRIPTION');
	}

	// todo: define a counter execute (if condition not met, reset what the current action is setting ?
	public function execute(ActionTargetEntity $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$status = ActionExecutionStatusEnum::FAILED;

		if (!empty($context->getFile()))
		{
			if(!class_exists('\EmundusModelWorkflow'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$modelWorkflow = new \EmundusModelWorkflow();
			$cartRepository = new CartRepository();

			$paymentStep = $modelWorkflow->getPaymentStepFromFnum($context->getFile(), true);

			if (!empty($paymentStep) && !empty($paymentStep->id)) {
				$cart = $cartRepository->getCartByFnum($context->getFile(), $paymentStep->id);
				if (!empty($cart))
				{
					try
					{
						if ((int)$this->getParameterValue(self::RESET_ADVANCE_AMOUNT) === 1)
						{
							$existingAlterations = array_filter(
								$cart->getPriceAlterations(),
								function (AlterationEntity $alteration) {
									return $alteration->getType() === AlterationType::ALTER_ADVANCE_AMOUNT;
								}
							);

							foreach ($existingAlterations as $alteration)
							{
								$cart->removeAlteration($alteration);
							}
						}
						else
						{
							$alteration = new AlterationEntity(
								0,
								$cart->getId(),
								null,
								null,
								self::getLabel(),
								$this->getParameterValue(self::ADVANCE_AMOUNT_PARAMETER),
								AlterationType::ALTER_ADVANCE_AMOUNT,
								$this->getAutomatedTaskUserId(),
							);

							$cart->addAlteration($alteration);
						}

						if ($cartRepository->saveCart($cart))
						{
							$status = ActionExecutionStatusEnum::COMPLETED;
						}
					}
					catch (\Exception $e)
					{
						$status = ActionExecutionStatusEnum::FAILED;
					}
				}
			}
		}

		return $status;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters))
		{
			$this->parameters = [
				// could be an option to get back to initial advance amount?
				new YesnoField(self::RESET_ADVANCE_AMOUNT, Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_ADVANCE_AMOUNT_PARAMETER_RESET_ADVANCE_AMOUNT_LABEL'), 0),
				new NumericField(self::ADVANCE_AMOUNT_PARAMETER, Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_ADVANCE_AMOUNT_PARAMETER_ADVANCE_AMOUNT_LABEL'), 0)
			];
		}

		return  $this->parameters;
	}

	public static function getCategory(): ?ActionCategoryEnum
	{
		return ActionCategoryEnum::CART;
	}

	public static function supportTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	public static function isAsynchronous(): bool
	{
		return false;
	}

	public function getLabelForLog(): string
	{
		return $this->getLabel();
	}
}