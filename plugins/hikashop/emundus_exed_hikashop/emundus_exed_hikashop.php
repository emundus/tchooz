<?php
/**
 * @version 1
 * @author  LEGENDRE Jérémy
 * @package Hikashop
 * @copyright Copyright (C) 2024 emundus.fr. All rigths reserved
 * @license GNU/GPL
 */
// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die('Restricted access');

class PlgHikashopEmundus_exed_hikashop extends CMSPlugin {

	public function __construct(&$subject, $config) {
		Log::addLogger(array('text_file' => 'com_emundus.emundus_exed_hikashop_plugin.php'), Log::ALL, array('com_emundus.emundus_exed_hikashop_plugin'));

		parent::__construct($subject, $config);
	}

	public function onAfterCartProductsLoad(&$cart)
	{
		$cart_id = $cart->cart_id;
		$app = Factory::getApplication();
		$session = $app->getSession()->get('emundusUser');
		$user_id = $app->getIdentity()->id;

		$fnum = $session->fnum;

		if (empty($fnum)) {
			return;
		}

		$discount_payment_status = $this->params->get('discount_payment_status', null);
		$totalite_characteristic_id = $this->params->get('hikashop_totalite_characteristic');
		$element_totalite_id = (int)$this->params->get('element_totalite_id', 0);
		$discounted_characteristic_id = $this->params->get('hikashop_discount_characteristic');
		$element_discount_id = (int)$this->params->get('element_discount_id', 0);
		$hikashop_vendor_id = $this->params->get('hikashop_vendor_id', 0);

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
		$h_files = new EmundusHelperFiles();
		$element_totalite = $h_files->getFabrikElementData($element_totalite_id);
		$element_discount = $h_files->getFabrikElementData($element_discount_id);

		if (!is_numeric($discount_payment_status) || empty($totalite_characteristic_id) || empty($discounted_characteristic_id)) {
			return;
		}

		if (!empty($cart_id) && !empty($fnum)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('status')
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where('fnum = ' . $db->quote($fnum));

			$db->setQuery($query);
			$status = $db->loadResult();

			if ($status == $discount_payment_status) {
				Log::add('User ' . $user_id . ' with fnum ' . $fnum . ' opened the cart with id ' . $cart_id . ' and has the status ' . $status, Log::INFO, 'com_emundus.emundus_exed_hikashop_plugin');

				require_once (JPATH_ROOT . '/components/com_emundus/models/payment.php');
				$m_payment = new EmundusModelPayment();
				$app = Factory::getApplication();
				$cart_products = $m_payment->getCartProducts($cart_id);

				if (!empty($cart_products)) {
					require_once (JPATH_ROOT . '/components/com_emundus/models/files.php');
					$m_files = new EmundusModelFiles();

					$parent_product_id = (int)current($cart_products)->product_id;
					$type = $m_payment->getProductType($parent_product_id);

					if ($type == 'variant') { // if the product is a variant, then cart is surely already updated
						return;
					}

					$total_value = $m_files->getFabrikValue([$fnum], $element_totalite['db_table_name'], $element_totalite['name']);
					$total_applicant_price = !empty($total_value) ? $total_value[$fnum]['val'] : null;

					$discount_value = $m_files->getFabrikValue([$fnum], $element_discount['db_table_name'], $element_discount['name']);
					$discounted_applicant_price = !empty($discount_value) ? $discount_value[$fnum]['val'] : null;

					if (empty($total_applicant_price) || empty($discounted_applicant_price)) {
						Log::add('User ' . $user_id . ' with fnum ' . $fnum . ' has no prices found', Log::ERROR, 'com_emundus.emundus_exed_hikashop_plugin');
						$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_NO_PRICES_FOUND'), 'error');
						$app->redirect('/');
					} else {
						$total_applicant_price = $this->rawValueFromCurrencyElement($total_applicant_price);
						$discounted_applicant_price = $this->rawValueFromCurrencyElement($discounted_applicant_price);

						$user_id          = $m_payment->getUserIdFromFnum($fnum);
						$hikashop_user_id = $m_payment->getHikashopUserId($user_id);

						$query->clear()
							->select('hp.*, hpc.category_id')
							->from($db->quoteName('#__hikashop_product', 'hp'))
							->leftJoin($db->quoteName('#__hikashop_product_category', 'hpc') . ' ON hp.product_id = hpc.product_id')
							->where('hp.product_id = ' . $db->quote($parent_product_id));

						try {
							$db->setQuery($query);
							$parent_product = $db->loadObject();

							$variant_totalite_product_id = $m_payment->getHikashopProductVariantForUser($parent_product_id, $hikashop_user_id, $totalite_characteristic_id);
							if ($variant_totalite_product_id == $parent_product_id) {
								$variant_totalite_product_id = $m_payment->createHikashopProduct('COM_EMUNDUS_TOTAL_PRODUCT_LABEL', $total_applicant_price, $parent_product->product_code . '-' . $hikashop_user_id, $parent_product->category_id, 'variant', $parent_product->product_id, $totalite_characteristic_id, $hikashop_user_id, $hikashop_vendor_id);
							} else {
								$updated_price = $m_payment->updateHikashopProductPrice($variant_totalite_product_id, $total_applicant_price);

								if (!$updated_price) {
									$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_PRODUCT_PRICE_NOT_UPDATED'), 'error');
									$app->redirect('/');
								}
							}

							$variant_discounted_product_id = $m_payment->getHikashopProductVariantForUser($parent_product_id, $hikashop_user_id, $discounted_characteristic_id);

							if ($variant_discounted_product_id == $parent_product_id || empty($variant_discounted_product_id)) {
								$product_code = $parent_product->product_code . '-' . $hikashop_user_id . '-' . $discounted_characteristic_id;

								$variant_discounted_product_id = $m_payment->createHikashopProduct('COM_EMUNDUS_DISCOUNTED_PRODUCT_LABEL', (float)$discounted_applicant_price, $product_code, (int)$parent_product->category_id, 'variant', $parent_product->product_id, $discounted_characteristic_id, $hikashop_user_id, $hikashop_vendor_id);
							} else {
								$updated_price = $m_payment->updateHikashopProductPrice($variant_discounted_product_id, (float)$discounted_applicant_price);

								if (!$updated_price) {
									$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_PRODUCT_PRICE_NOT_UPDATED'), 'error');
									$app->redirect('/');
								}
							}

							if ($variant_totalite_product_id != $parent_product_id) {
								$updated_cart = $m_payment->updateHikashopCart($cart_id, [$variant_totalite_product_id]);

								if (is_null($updated_cart)) {
									Log::add('User ' . $user_id . ' with fnum ' . $fnum . ' tried to update the cart with id ' . $cart_id . ' with the product ' . $variant_totalite_product_id, Log::ERROR, 'com_emundus.emundus_exed_hikashop_plugin');
									$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_PRODUCT_NOT_FOUND'), 'error');
									$app->redirect('/');
								} else {
									Log::add('User ' . $user_id . ' with fnum ' . $fnum . ' updated the cart with id ' . $cart_id . ' with the product ' . $variant_totalite_product_id, Log::INFO, 'com_emundus.emundus_exed_hikashop_plugin');
								}
							}
						} catch (Exception $e) {
							$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_PRODUCT_NOT_FOUND'), 'error');
							$app->redirect('/');
						}
					}
				} else {
					$app->enqueueMessage(Text::_('EMPTY_CART'), 'error');
					$app->redirect('/');
				}
			}
		}
	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 */
	private function rawValueFromCurrencyElement($value): float
	{
		$value = str_replace(' ', '', $value);
		$value = str_replace(',', '.', $value);
		$value = str_replace('€(EUR)', '', $value);

		return (float)$value;
	}
}