<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\DiscountEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\DiscountRepository;

class ActionUpdateCartDiscounts extends ActionEntity
{
	public const ADD_OR_REMOVE_PARAMETER = 'add_or_remove';
	public const ADD = 'add';
	public const REMOVE = 'remove';

	public static function getIcon(): ?string
	{
		return 'mintmark';
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'update_cart_discounts';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_DISCOUNTS');
	}

	public static function getDescription(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_DISCOUNTS_DESCRIPTION');
	}

	public function execute(ActionTargetEntity $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$actionDiscountIds = $this->getParameterValue('discounts');
		$fnum = $context->getFile();

		if (!empty($fnum) && !empty($actionDiscountIds))
		{
			$allActionsSuccessful = true;
			$cartRepository = new CartRepository();
			if (!class_exists('EmundusModelWorkflow'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new \EmundusModelWorkflow();
			$discountRepository = new DiscountRepository();

			$step = $m_workflow->getPaymentStepFromFnum($fnum);
			if (!empty($step->id))
			{
				$cart = $cartRepository->getCartByFnum($fnum, $step->id);

				if (!empty($cart))
				{
					foreach ($actionDiscountIds as $actionDiscountId)
					{
						$allActionsSuccessful = true;

						switch ($this->getParameterValue(self::ADD_OR_REMOVE_PARAMETER))
						{
							case self::ADD:
								$alreadyInCart = false;
								foreach ($cart->getPriceAlterations() as $alteration)
								{
									if (!empty($alteration->getDiscount()) && $alteration->getDiscount()->getId() === (int) $actionDiscountId)
									{
										$alreadyInCart = true;
										break;
									}
								}

								if (!$alreadyInCart)
								{
									$discount   = $discountRepository->getDiscountById($actionDiscountId);
									$alteration = new AlterationEntity(0, $cart->getId(), null, $discount, $discount->getDescription(), -$discount->getValue(), AlterationType::from($discount->getType()->value), $this->getAutomatedTaskUserId());
									$cartRepository->addAlteration($cart, $alteration, $this->getAutomatedTaskUserId(), $executionContext);
								}
								break;
							case self::REMOVE:
								$stillInCart        = false;
								$alterationToRemove = null;

								foreach ($cart->getPriceAlterations() as $alteration)
								{
									if (!empty($alteration->getDiscount()) && $alteration->getDiscount()->getId() === (int) $actionDiscountId)
									{
										$stillInCart        = true;
										$alterationToRemove = $alteration;
										break;
									}
								}

								if ($stillInCart && !empty($alterationToRemove))
								{
									$removed = $cartRepository->removeAlteration($cart, $alterationToRemove, $this->getAutomatedTaskUserId(), $executionContext);
									if (!$removed)
									{
										$allActionsSuccessful = false;
									}
								}

								break;
						}
					}
				}
			}

			if (!$allActionsSuccessful)
			{
				return ActionExecutionStatusEnum::FAILED;
			}
		}

		return ActionExecutionStatusEnum::COMPLETED;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters)) {
			$this->parameters = [
				new ChoiceField(self::ADD_OR_REMOVE_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_LABEL'), [
					new ChoiceFieldValue(self::ADD, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_ADD')),
					new ChoiceFieldValue(self::REMOVE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_REMOVE')),
				], true),
				new ChoiceField('discounts', Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_DISCOUNTS_PARAMETER_DISCOUNTS_LABEL'), $this->getDiscountsList(), true, true)
			];
		}

		return $this->parameters;
	}

	/**
	 * @return array<ChoiceFieldValue>
	 */
	private function getDiscountsList(): array
	{
		$options = [];

		$repository = new DiscountRepository();
		foreach ($repository->getDiscounts(0) as $discount)
		{
			assert($discount instanceof DiscountEntity);
			$options[] = new ChoiceFieldValue($discount->getId(), $discount->getLabel());
		}

		return $options;
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