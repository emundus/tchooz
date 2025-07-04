<?php

namespace Tchooz\Repositories\Payment;

use EmundusHelperCache;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Tchooz\Entities\Payment\DiscountType;
use Tchooz\Entities\Payment\PaymentStepEntity;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Settings\AddonEntity;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;

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
		$this->action_id = \EmundusHelperAccess::getActionIdFromActionName('payment');
		$this->loadAddon();
		$this->setPaymentStepTypeId();

		if (!empty($this->action_id) && $this->addon->enabled == 1) {
			$this->activated = true;
		}
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
			if (!class_exists('EmundusModelWorkflow')) {
				require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			}
			$m_workflow = new \EmundusModelWorkflow();
			$step = $m_workflow->getStepData($step_id);

			if (!empty($step)) {
				$payment_step = new PaymentStepEntity($step->id);
				$payment_step->setWorkflowId($step->workflow_id);
				$payment_step->setLabel($step->label);
				if (!empty($step->description)) {
					$payment_step->setDescription($step->description);
				}
				$payment_step->setType($step->type);
				$payment_step->setState($step->state);
				$payment_step->setEntryStatus($step->entry_status);
				$payment_step->setOutputStatus($step->output_status);
				$query = $this->db->createQuery();

				$query->clear()
					->select('product_id, mandatory')
					->from($this->db->quoteName('jos_emundus_setup_workflow_step_product'))
					->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($step_id));

				$this->db->setQuery($query);
				$step_products = $this->db->loadObjectList();

				$products = [];
				if (!empty($step_products)) {
					foreach ($step_products as $step_product) {
						$product_entity = new ProductEntity($step_product->product_id);
						$step_product->mandatory = !empty($step_product->mandatory) ? 1 : 0;
						$product_entity->setMandatory($step_product->mandatory);
						$products[] = $product_entity;
					}
				}

				$payment_step->setProducts($products);

				$query->clear()
					->select('payment_method')
					->from($this->db->quoteName('jos_emundus_setup_workflow_step_payment_method'))
					->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($step_id));

				$this->db->setQuery($query);
				$step_payment_method_ids = $this->db->loadColumn();

				if (!empty($step_payment_method_ids)) {
					$payment_methods = [];
					foreach ($step_payment_method_ids as $payment_method_id) {
						$payment_method = new PaymentMethodEntity($payment_method_id);
						$payment_methods[] = $payment_method;
					}
					$payment_step->setPaymentMethods($payment_methods);
				}

				$query->clear()
					->select('*')
					->from($this->db->quoteName('jos_emundus_setup_workflow_step_payment_rules'))
					->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($step_id));
				$this->db->setQuery($query);
				$rules = $this->db->loadObject();

				if (!empty($rules)) {
					$payment_step->setAdjustBalance((int)$rules->adjust_balance);
					if (!empty($rules->adjust_balance_step_id)) {
						$payment_step->setAdjustBalanceStepId($rules->adjust_balance_step_id);
					}
					$payment_step->setSynchronizerId($rules->synchronizer_id);
					$payment_step->setAdvanceType($rules->advance_type);
					$payment_step->setIsAdvanceAmountEditableByApplicant($rules->is_advance_amount_editable_by_applicant);
					$payment_step->setAdvanceAmount($rules->advance_amount);
					$payment_step->setAdvanceAmountType(DiscountType::getInstance($rules->advance_amount_type));
					$payment_step->setInstallmentMonthday($rules->installment_monthday);
					$payment_step->setInstallmentEffectDate($rules->installment_effect_date);
				}

				$query->clear()
					->select('*')
					->from('#__emundus_setup_workflow_step_installment_rule')
					->where('step_id = ' . $this->db->quote($step_id));

				$this->db->setQuery($query);
				$installment_rules = $this->db->loadObjectList();

				if (!empty($installment_rules)) {
					$payment_step->setInstallmentRules($installment_rules);
				}
			}
		}

		if (!empty($payment_step) && !empty($fnum)) {
			PluginHelper::importPlugin('emundus');
			$dispatcher = Factory::getApplication()->getDispatcher();
			$onAfterLoadEmundusPaymentStep = new GenericEvent('onCallEventHandler', ['onAfterLoadEmundusPaymentStep', ['payment_step' => $payment_step, 'rules' => $rules, 'fnum' => $fnum]]);
			$dispatcher->dispatch('onCallEventHandler', $onAfterLoadEmundusPaymentStep);
		}

		return $payment_step;
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

			$query->clear()->delete($this->db->quoteName('jos_emundus_setup_workflow_step_product'))
				->where($this->db->quoteName('step_id') . ' = ' . $this->db->quote($payment_step->getId()));

			$this->db->setQuery($query);
			$this->db->execute();

			$products = $payment_step->getProducts();
			$mandatory_products = array_filter($products, function ($product) {
				return $product->getMandatory() == 1;
			});
			$optional_products = array_filter($products, function ($product) {
				return $product->getMandatory() == 0;
			});

			foreach ($mandatory_products as $product) {
				$query->clear()
					->insert($this->db->quoteName('jos_emundus_setup_workflow_step_product'))
					->columns($this->db->quoteName(['step_id', 'product_id', 'mandatory']))
					->values(implode(',', [$this->db->quote($payment_step->getId()), $this->db->quote($product->getId()), 1]));

				$this->db->setQuery($query);
				$this->db->execute();
			}

			foreach ($optional_products as $product) {
				$query->clear()
					->insert($this->db->quoteName('jos_emundus_setup_workflow_step_product'))
					->columns($this->db->quoteName(['step_id', 'product_id', 'mandatory']))
					->values(implode(',', [$this->db->quote($payment_step->getId()), $this->db->quote($product->getId()), 0]));

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

			// TODO: update installment rules
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

	public function getPaymentMethods(): array
	{
		$payment_methods = [];

		try {
			$query = $this->db->createQuery();
			$query->select($this->db->quoteName(['id']))
				->from($this->db->quoteName('jos_emundus_setup_payment_method'))
				->where($this->db->quoteName('published') . ' = 1');

			$this->db->setQuery($query);
			$payment_method_ids = $this->db->loadColumn();

			if (!empty($payment_method_ids)) {
				foreach ($payment_method_ids as $payment_method_id) {
					$payment_methods[] = new PaymentMethodEntity($payment_method_id);
				}
			}
		} catch (\Exception $e) {
			Log::add('Error loading payment methods: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.payment');
		}

		return $payment_methods;
	}

	public function getPaymentServices(): array
	{
		$payment_services = [];

		try {
			$types = ['sogecommerce'];

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
				$query->select('id, label_' . $lang . ' AS label')
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
