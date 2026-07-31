<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Automation\Actions\Traits\WithProductChoice;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\DiscountEntity;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\DiscountRepository;
use Tchooz\Repositories\Payment\ProductRepository;

class ActionUpdateCartDiscounts extends ActionEntity
{
	use WithProductChoice;

	public const ADD_OR_REMOVE_PARAMETER = 'add_or_remove';
	public const ADD = 'add';
	public const REMOVE = 'remove';
	public const PRODUCT_PARAMETER = 'product';
	public const DISCOUNTS_PARAMETER = 'discounts';

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

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		$actionDiscountIds = $this->getParameterValue(self::DISCOUNTS_PARAMETER);
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

			$selectedProduct = $this->resolveSelectedProduct();
			$step = $m_workflow->getPaymentStepFromFnum($fnum);
			if (!empty($step->id))
			{
				$cart = $cartRepository->getCartByFnum($fnum, $step->id, $this->getAutomatedTaskUserId(), $executionContext);

				if (!empty($cart))
				{
					$actionDiscountIds = array_map('intval', (array) $actionDiscountIds);

					foreach ($actionDiscountIds as $actionDiscountId)
					{
						$allActionsSuccessful = true;

						switch ($this->getParameterValue(self::ADD_OR_REMOVE_PARAMETER))
						{
							case self::ADD:
								if (!$this->findMatchingAlteration($cart->getPriceAlterations(), $actionDiscountId, $selectedProduct))
								{
									$discount   = $discountRepository->getDiscountById($actionDiscountId);
									$alteration = new AlterationEntity(
										0,
										$cart->getId(),
										$selectedProduct,
										$discount,
										$discount->getDescription(),
										-$discount->getValue(),
										AlterationType::from($discount->getType()->value),
										$this->getAutomatedTaskUserId()
									);
									$cartRepository->addAlteration($cart, $alteration, $this->getAutomatedTaskUserId(), $executionContext);
								}
								break;
							case self::REMOVE:
								$alterationToRemove = $this->findMatchingAlteration($cart->getPriceAlterations(), $actionDiscountId, $selectedProduct);

								if (!empty($alterationToRemove))
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

	private function resolveSelectedProduct(): ?ProductEntity
	{
		$productId = (int) $this->getParameterValue(self::PRODUCT_PARAMETER);

		if (empty($productId))
		{
			return null;
		}

		$product = (new ProductRepository())->getProductById($productId);

		return !empty($product) && !empty($product->getId()) ? $product : null;
	}

	/**
	 * @param   array<AlterationEntity>  $alterations
	 */
	private function findMatchingAlteration(array $alterations, int $discountId, ?ProductEntity $product): ?AlterationEntity
	{
		$expectedProductId = !empty($product) ? $product->getId() : null;

		foreach ($alterations as $alteration)
		{
			$alterationDiscount = $alteration->getDiscount();
			if (empty($alterationDiscount) || $alterationDiscount->getId() !== $discountId)
			{
				continue;
			}

			$alterationProductId = !empty($alteration->getProduct()) ? $alteration->getProduct()->getId() : null;
			if ($alterationProductId === $expectedProductId)
			{
				return $alteration;
			}
		}

		return null;
	}

	public function getParameters(): array
	{
		if (empty($this->parameters)) {
			$this->parameters = [
				new ChoiceField(self::ADD_OR_REMOVE_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_LABEL'), [
					new ChoiceFieldValue(self::ADD, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_ADD')),
					new ChoiceFieldValue(self::REMOVE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_REMOVE')),
				], true),
				new ChoiceField(self::DISCOUNTS_PARAMETER, Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_DISCOUNTS_PARAMETER_DISCOUNTS_LABEL'), $this->getDiscountsList(), true, true),
				$this->buildProductChoiceField(
					self::PRODUCT_PARAMETER,
					Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_DISCOUNTS_PARAMETER_PRODUCT_LABEL')
				),
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