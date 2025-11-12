<?php

namespace Tchooz\Repositories\Payment;

use EmundusHelperCache;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Payment\DiscountType;
use Tchooz\Entities\Payment\PaymentStepEntity;
use Tchooz\Entities\Payment\ProductCategoryEntity;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Settings\AddonEntity;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\Payment\PaymentStepFactory;
use Tchooz\Repositories\Actions\ActionRepository;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');

class PaymentRepository
{
	private DatabaseDriver $db;

	private int $action_id = 0;

	private AddonEntity|null $addon;

	public bool $activated = false;

	private int $payment_step_type = 0;

	private \EmundusHelperCache $h_cache;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.payment.php'], Log::ALL, ['com_emundus.repository.payment']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->h_cache = new \EmundusHelperCache();

		$actionRepository = new ActionRepository();
		$this->action_id = $actionRepository->getByName('payment')->getId();

		$this->loadAddon();
		$this->setPaymentStepTypeId();

		if (!empty($this->action_id) && $this->addon->enabled == 1) {
			$this->activated = true;
		}
	}

	public function isActivated(): bool
	{
		return $this->activated;
	}

	public function loadAddon(): void
	{
		$cache_addon = $this->h_cache->get('payment_addon');

		if (empty($cache_addon)) {
			$query = $this->db->createQuery();

			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('payment'));

			try {
				$this->db->setQuery($query);
				$addon = $this->db->loadObject();

				if ($addon)
				{
					$config = json_decode($addon->value, true);

					if(!class_exists('AddonEntity'))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/classes/Entities/Settings/AddonEntity.php');
					}

					$this->addon = new AddonEntity(
						Text::_('COM_EMUNDUS_ADDONS_PAYMENT'),
						'payment',
						'shopping_cart',
						Text::_('COM_EMUNDUS_ADDONS_PAYMENT_DESC'),
						json_encode($config['params']),
						$config['enabled'] ? 1 : 0,
						$config['displayed'] ? 1 : 0
					);
				} else {
					$this->addon = new AddonEntity(
						Text::_('COM_EMUNDUS_ADDONS_PAYMENT'),
						'payment',
						'shopping_cart',
						Text::_('COM_EMUNDUS_ADDONS_PAYMENT_DESC'),
						json_encode([]),
						0,
						0
					);
				}

				$this->h_cache->set('payment_addon', $this->addon);
			} catch (\Exception $e) {
				Log::add('Error loading addon: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.payment');
			}
		} else {
			$this->addon = $cache_addon;
		}
	}

	public function getActionId(): int
	{
		return $this->action_id;
	}

	public function getAddon(): AddonEntity
	{
		return $this->addon;
	}

	private function setPaymentStepTypeId(): void
	{
		$payment_action_id = $this->getActionId();

		$query = $this->db->createQuery();
		$query->select('est.id')
			->from($this->db->quoteName('#__emundus_setup_step_types', 'est'))
			->leftJoin($this->db->quoteName('#__emundus_setup_actions', 'esa') . ' ON esa.id = est.action_id')
			->where('esa.id = ' . $this->db->quote($payment_action_id));

		$this->db->setQuery($query);
		$this->payment_step_type = (int)$this->db->loadResult();
	}

	public function getPaymentStepTypeId(): int
	{
		return $this->payment_step_type;
	}

	/**
	 * @param   int          $step_id
	 * @param   string|null  $fnum
	 *
	 * @return PaymentStepEntity|null
	 * @throws \Exception
	 */
	public function getPaymentStepById(int $step_id, ?string $fnum = null): ?PaymentStepEntity
	{
		$payment_step = null;

		if (!empty($step_id)) {
			$query = $this->db->createQuery();
			$query->select('
				step.id as id,
				step.*, 
				GROUP_CONCAT(entry_status.status) AS entry_status, 
				rules.*, 
				GROUP_CONCAT(DISTINCT method.payment_method) AS payment_methods, 
				GROUP_CONCAT(DISTINCT mandatory_products.product_id) AS mandatory_product_ids,
				GROUP_CONCAT(DISTINCT mandatory_product_category.product_category) AS product_category_ids,
				GROUP_CONCAT(DISTINCT optional_products.product_id) AS optional_product_ids,
				GROUP_CONCAT(DISTINCT optional_product_category.product_category) AS optional_product_category_ids
			')
				->from($this->db->quoteName('#__emundus_setup_workflows_steps', 'step'))
				->leftJoin($this->db->quoteName('#__emundus_setup_workflows_steps_entry_status', 'entry_status') . ' ON entry_status.step_id = step.id')
				->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_payment_rules', 'rules') . ' ON rules.step_id = step.id')
				->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_payment_method', 'method') . ' ON method.step_id = step.id')
				->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_product', 'mandatory_products') . ' ON mandatory_products.step_id = step.id AND mandatory_products.mandatory = 1')
				->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_product', 'optional_products') . ' ON optional_products.step_id = step.id AND optional_products.mandatory = 0')
				->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_product_category', 'mandatory_product_category') . ' ON mandatory_product_category.step_id = step.id AND mandatory_product_category.mandatory = 1')
				->leftJoin($this->db->quoteName('#__emundus_setup_workflow_step_product_category', 'optional_product_category') . ' ON optional_product_category.step_id = step.id AND optional_product_category.mandatory = 0')
				->where('step.id = ' . $this->db->quote($step_id))
				->group('step.id');

			$this->db->setQuery($query);
			$step = $this->db->loadObject();

			if (!empty($step))
			{
				$step->id = $step_id;
				$step->installment_rules = $this->getInstallmentRulesByStepId($step_id);

				$payment_step = PaymentStepFactory::fromDbObjects([$step])[0];
			}
		}

		if (!empty($payment_step) && !empty($fnum)) {
			PluginHelper::importPlugin('emundus');
			$dispatcher = Factory::getApplication()->getDispatcher();
			$onAfterLoadEmundusPaymentStep = new GenericEvent(
				'onCallEventHandler',
				[
					'onAfterLoadEmundusPaymentStep',
					[
						'payment_step' => $payment_step,
						'rules' => $payment_step->getInstallmentRules(),
						'fnum' => $fnum,
						'context' => new EventContextEntity(
							null,
							[$fnum],
							[],
							[
								'payment_step' => $step_id,
								'rules' => $payment_step->getInstallmentRules(),
								'payment_step_entity' => $payment_step,
							]
						)
					]
				]);
			$dispatcher->dispatch('onCallEventHandler', $onAfterLoadEmundusPaymentStep);
		}

		return $payment_step;
	}

	/**
	 * @param   int  $stepId
	 *
	 * @return array
	 */
	public function getInstallmentRulesByStepId(int $stepId): array
	{
		$rules = [];

		if (!empty($stepId))
		{
			$query = $this->db->createQuery();
			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_workflow_step_installment_rule'))
				->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($stepId));

			try {
				$this->db->setQuery($query);
				$rules = $this->db->loadObjectList();
			} catch (\Exception $e) {
				Log::add('Error loading installment rules: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.payment');
			}
		}

		return $rules;
	}

	public function flushPaymentStep(PaymentStepEntity $payment_step): bool
	{
		$flushed = false;

		if (!empty($payment_step->getId())) {
			$query = $this->db->createQuery();

			$query->update('#__emundus_setup_workflows_steps')
				->set('description = ' . $this->db->quote($payment_step->getDescription()))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($payment_step->getId()));

			$this->db->setQuery($query);
			$this->db->execute();

			$query->clear()
				->delete($this->db->quoteName('jos_emundus_setup_workflow_step_product'))
				->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($payment_step->getId()));

			$this->db->setQuery($query);
			$cleared = $this->db->execute();

			$products = $payment_step->getProducts();
			$mandatory_products = array_filter($products, function ($product) {
				return $product->getMandatory() == 1;
			});
			foreach ($mandatory_products as $product) {
				$query->clear()
					->insert($this->db->quoteName('jos_emundus_setup_workflow_step_product'))
					->columns($this->db->quoteName(['step_id', 'product_id', 'mandatory']))
					->values(implode(',', [$this->db->quote($payment_step->getId()), $this->db->quote($product->getId()), 1]));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			$optional_products = array_filter($products, function ($product) {
				return $product->getMandatory() != 1;
			});
			foreach ($optional_products as $product) {
				$query->clear()
					->insert($this->db->quoteName('jos_emundus_setup_workflow_step_product'))
					->columns($this->db->quoteName(['step_id', 'product_id', 'mandatory']))
					->values(implode(',', [$this->db->quote($payment_step->getId()), $this->db->quote($product->getId()), 0]));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			$query->clear()
				->delete($this->db->quoteName('jos_emundus_setup_workflow_step_product_category'))
				->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($payment_step->getId()));

			$this->db->setQuery($query);
			$this->db->execute();

			$mandatory_categories = $payment_step->getMandatoryProductCategories();
			foreach ($mandatory_categories as $category) {
				assert($category instanceof ProductCategoryEntity);

				$query->clear()
					->insert($this->db->quoteName('jos_emundus_setup_workflow_step_product_category'))
					->columns($this->db->quoteName(['step_id', 'product_category', 'mandatory']))
					->values(implode(',', [$this->db->quote($payment_step->getId()), $this->db->quote($category->getId()), 1]));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			$query->clear()
				->delete($this->db->quoteName('jos_emundus_setup_workflow_step_payment_method'))
				->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($payment_step->getId()));

			$this->db->setQuery($query);
			$this->db->execute();

			foreach ($payment_step->getPaymentMethods() as $payment_method) {
				$query->clear()
					->insert($this->db->quoteName('jos_emundus_setup_workflow_step_payment_method'))
					->columns($this->db->quoteName(['step_id', 'payment_method']))
					->values(implode(',', [$this->db->quote($payment_step->getId()), $this->db->quote($payment_method->getId())]));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			// Update rules
			$query->clear()
				->select('id')
				->from($this->db->quoteName('jos_emundus_setup_workflow_step_payment_rules'))
				->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($payment_step->getId()));

			$this->db->setQuery($query);
			$row_id = $this->db->loadResult();

			if (empty($row_id)) {
				$values = [
					$this->db->quote($payment_step->getId()),
					$this->db->quote($payment_step->getSynchronizerId()),
					$this->db->quote($payment_step->getAdvanceType()),
					$this->db->quote($payment_step->isAdvanceAmountEditableByApplicant()),
					$this->db->quote($payment_step->getAdvanceAmount()),
					$this->db->quote($payment_step->getAdvanceAmountType()->value),
					$this->db->quote($payment_step->getAdjustBalance()),
					(!empty($payment_step->getAdjustBalanceStepId()) ? $this->db->quote($payment_step->getAdjustBalanceStepId()) : 'NULL'),
					$this->db->quote($payment_step->getInstallmentMonthday()),
					(!empty($payment_step->getInstallmentEffectDate()) ? $this->db->quote($payment_step->getInstallmentEffectDate()) : 'NULL')
				];

				$query->clear()
					->insert($this->db->quoteName('jos_emundus_setup_workflow_step_payment_rules'))
					->columns($this->db->quoteName(['step_id', 'synchronizer_id', 'advance_type', 'is_advance_amount_editable_by_applicant', 'advance_amount', 'advance_amount_type', 'adjust_balance', 'adjust_balance_step_id', 'installment_monthday', 'installment_effect_date']))
					->values(implode(',', $values));

				$this->db->setQuery($query);
				$this->db->execute();
			} else {
				$query->clear()
					->update($this->db->quoteName('jos_emundus_setup_workflow_step_payment_rules'))
					->set($this->db->quoteName('synchronizer_id') . ' = ' . $this->db->quote($payment_step->getSynchronizerId()))
					->set($this->db->quoteName('advance_type') . ' = ' . $this->db->quote($payment_step->getAdvanceType()))
					->set($this->db->quoteName('is_advance_amount_editable_by_applicant') . ' = ' . $this->db->quote($payment_step->isAdvanceAmountEditableByApplicant()))
					->set($this->db->quoteName('advance_amount') . ' = ' . $this->db->quote($payment_step->getAdvanceAmount()))
					->set($this->db->quoteName('advance_amount_type') . ' = ' . $this->db->quote($payment_step->getAdvanceAmountType()->value))
					->set($this->db->quoteName('adjust_balance') . ' = ' . $this->db->quote($payment_step->getAdjustBalance()))
					->set($this->db->quoteName('installment_monthday') . ' = ' . $this->db->quote($payment_step->getInstallmentMonthday()));

				if (!empty($payment_step->getInstallmentEffectDate()))
				{
					$query->set($this->db->quoteName('installment_effect_date') . ' = ' . $this->db->quote($payment_step->getInstallmentEffectDate()));
				}
				else
				{
					$query->set($this->db->quoteName('installment_effect_date') . ' = NULL');
				}

				if (!empty($payment_step->getAdjustBalanceStepId()))
				{
					$query->set($this->db->quoteName('adjust_balance_step_id') . ' = ' . $this->db->quote($payment_step->getAdjustBalanceStepId()));
				}
				else
				{
					$query->set($this->db->quoteName('adjust_balance_step_id') . ' = NULL');
				}

				$query->where($this->db->quoteName('id') . ' = ' . $this->db->quote($row_id));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			$query->clear()
				->delete($this->db->quoteName('jos_emundus_setup_workflow_step_installment_rule'))
				->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($payment_step->getId()));

			$this->db->setQuery($query);
			$this->db->execute();

			if (!empty($payment_step->getInstallmentRules()))
			{
				foreach ($payment_step->getInstallmentRules() as $installment_rule) {
					$query->clear()
						->insert($this->db->quoteName('jos_emundus_setup_workflow_step_installment_rule'))
						->columns($this->db->quoteName(['step_id', 'from_amount', 'to_amount', 'min_installments', 'max_installments']))
						->values(implode(',', [$this->db->quote($payment_step->getId()), $this->db->quote($installment_rule->from_amount), $this->db->quote($installment_rule->to_amount), $this->db->quote($installment_rule->min_installments), $this->db->quote($installment_rule->max_installments)]));

					$this->db->setQuery($query);
					$this->db->execute();
				}
			}

			$flushed = true;
		}


		return $flushed;
	}

	/**
	 * @return array<PaymentMethodEntity>
	 */
	public function getPaymentMethods(): array
	{
		$paymentMethodRepository = new PaymentMethodRepository();
		return $paymentMethodRepository->getAll();
	}

	public function getPaymentServices(): array
	{
		$payment_services = [];

		try {
			$types = ['sogecommerce', 'stripe'];

			$query = $this->db->createQuery();
			$query->select('id, name, description')
				->from('jos_emundus_setup_sync')
				->where('published = 1')
				->andWhere('enabled = 1')
				->andWhere('type IN (' . implode(',', array_map([$this->db, 'quote'], $types)) . ')');

			$this->db->setQuery($query);
			$payment_services = $this->db->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Error loading payment services: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.payment');
		}

		return $payment_services;
	}

	/**
	 * @param   int  $service_id
	 *
	 * @return \stdClass|null
	 */
	public function getPaymentServiceById(int $service_id): ?\stdClass
	{
		$payment_service = null;

		if (!empty($service_id)) {
			try {
				$query = $this->db->createQuery();
				$query->select('id, name, description')
					->from('jos_emundus_setup_sync')
					->where('id = ' . $this->db->quote($service_id))
					->andWhere('published = 1')
					->andWhere('enabled = 1');

				$this->db->setQuery($query);
				$payment_service = $this->db->loadObject();
			} catch (\Exception $e) {
				Log::add('Error loading payment service: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.payment');
			}
		}

		return $payment_service;
	}

	/**
	 * Used for the customer address form
	 *
	 * @param string $lang
	 * @return array
	 */


	public function getCountries(string $lang = 'fr'): array
	{
		$countries = [];

		$cache_countries = $this->h_cache->get('payment_countries');
		if (empty($cache_countries)) {
			try {
				$query = $this->db->createQuery();
				$query->select('id, label_' . $lang . ' AS label, iso2, flag, flag_img')
					->from($this->db->quoteName('data_country'))
					->order('label_' . $lang . ' ASC');

				$this->db->setQuery($query);
				$countries = $this->db->loadObjectList();

				$this->h_cache->set('payment_countries', $countries);
			} catch (\Exception $e) {
				Log::add('Error loading countries: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.payment');
			}
		} else {
			$countries = $cache_countries;
		}

		return $countries;
	}

	public function getManualPaymentMethods(): array
	{
		return ['cheque', 'transfer'];
	}

	public static function generateAmountsByIterations(float $total, int $nb_iterations): array
	{
		$iterations = [];

		if (!empty($total) && !empty($nb_iterations) && $nb_iterations > 0) {
			$first_iteration  = (int) ceil($total / $nb_iterations);
			$remainder        = $total - $first_iteration;
			$other_iterations = ($nb_iterations > 1) ? (int) floor($remainder / ($nb_iterations - 1)) : 0;

			$calculated_total = $first_iteration + ($other_iterations * ($nb_iterations - 1));
			$difference       = $total - $calculated_total;

			$iterations    = array_fill(0, $nb_iterations, $other_iterations);
			$iterations[0] = $first_iteration + $difference;
		}

		if ($total != array_sum($iterations)) {
			throw new \Exception(Text::_('COM_EMUNDUS_PAYMENT_GENERATE_ITERATIONS_ERROR'));
		}

		return $iterations;

	}
}
