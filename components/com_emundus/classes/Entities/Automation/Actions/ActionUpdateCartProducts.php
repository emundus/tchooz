<?php

namespace Tchooz\Entities\Automation\Actions;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationExecutionContext;
use Tchooz\Entities\Automation\Actions\Traits\WithProductChoice;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\YesnoField;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Automation\ActionCategoryEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\ProductRepository;

class ActionUpdateCartProducts extends ActionEntity
{
	use WithProductChoice;

	public const ADD_OR_REMOVE_PARAMETER = 'add_or_remove';
	public const ADD = 'add';
	public const REMOVE = 'remove';
	public const MANDATORY = 'mandatory';

	public static function getIcon(): ?string
	{
		return 'add_shopping_cart';
	}

	/**
	 * @inheritDoc
	 */
	public static function getType(): string
	{
		return 'update_cart_products';
	}

	/**
	 * @inheritDoc
	 */
	public static function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_PRODUCTS');
	}

	public static function getDescription(): string
	{
		return Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_PRODUCTS_DESCRIPTION');
	}

	/**
	 * @inheritDoc
	 */
	public function execute(ActionTargetEntity|array $context, ?AutomationExecutionContext $executionContext = null): ActionExecutionStatusEnum
	{
		if (!empty($context->getFile()))
		{
			$fnum = $context->getFile();

			$cartRepository = new CartRepository();
			$productRepository = new ProductRepository();
			if (!class_exists('EmundusModelWorkflow'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new \EmundusModelWorkflow();

			$allActionsSuccessful = true;
			$step = $m_workflow->getPaymentStepFromFnum($fnum);

			if (!empty($step->id))
			{
				$cart = $cartRepository->getCartByFnum($fnum, $step->id, $this->getAutomatedTaskUserId(), $executionContext);

				if (!empty($cart)) {
					$actionProducts = (array) $this->getParameterValue('products');
					$actionProducts = array_map('intval', $actionProducts);

					$mandatoryFlag = !empty($this->getParameterValue(self::MANDATORY)) ? 1 : 0;
					switch($this->getParameterValue(self::ADD_OR_REMOVE_PARAMETER))
					{
						case self::ADD:
							$saveCart = false;
							foreach ($actionProducts as $actionProductId)
							{
								$alreadyInCart = !empty($this->findMatchingProduct($cart->getProducts(), $actionProductId));
								if (!$alreadyInCart)
								{
									$product = $productRepository->getProductById($actionProductId);
									if (!empty($product->getId()))
									{
										$product->setMandatory($mandatoryFlag);
										$cart->addProduct($product);
										$saveCart = true;
									}
								}
							}

							if ($saveCart)
							{
								$saved = $cartRepository->saveCart($cart, $this->getAutomatedTaskUserId(), $executionContext);
								if (!$saved)
								{
									$allActionsSuccessful = false;
								}
							}
							break;
						case self::REMOVE:
							$needToSave = false;
							foreach ($actionProducts as $actionProductId)
							{
								$productToRemove = $this->findMatchingProduct($cart->getProducts(), $actionProductId);
								if (!empty($productToRemove))
								{
									$cart->removeProduct($productToRemove);
									$needToSave = true;
								}
							}

							if ($needToSave)
							{
								$saved = $cartRepository->saveCart($cart, $this->getAutomatedTaskUserId(), $executionContext);
								if (!$saved)
								{
									$allActionsSuccessful = false;
								}
							}
							break;
					}
				} else {
					// log info, maybe normal
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
		if (empty($this->parameters))
		{
			$this->parameters = [
				new ChoiceField(self::ADD_OR_REMOVE_PARAMETER, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_LABEL'), [
					new ChoiceFieldValue(self::ADD, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_ADD')),
					new ChoiceFieldValue(self::REMOVE, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_UPDATE_TAGS_PARAMETER_ADD_OR_REMOVE_REMOVE')),
				], true),
				$this->buildProductChoiceField(
					'products',
					Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_PRODUCTS_PARAMETER_PRODUCTS_LABEL'),
					true,
					true
				),
				new YesnoField(self::MANDATORY, Text::_('COM_EMUNDUS_ACTION_UPDATE_CART_PRODUCTS_PARAMETER_MANDATORY_LABEL'), 0),
			];
		}

		return $this->parameters;
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

	/**
	 * @param   array<ProductEntity>  $products
	 * @param   int    $productId
	 *
	 * @return ProductEntity|null
	 */
	private function findMatchingProduct(array $products, int $productId): ?ProductEntity
	{
		foreach ($products as $product)
		{
			if ($product->getId() === $productId)
			{
				return $product;
			}
		}

		return null;
	}
}