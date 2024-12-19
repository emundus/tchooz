<?php
/**
 * @version 1: ExceliaPrice 2019-10-30 James Dean
 * @author  James Dean
 * @package Hikashop
 * @copyright Copyright (C) 2018 emundus.fr. All r
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have
 * to the GNU General Public License, and as distr
 * is derivative of works licensed under the GNU G
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and det
 * @description Sets the price to 0 depending if the excelia user exists
 */
// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');


class PlgHikashopEmundus_exed_hikashop extends CMSPlugin {

	public function onCheckoutWorkflowLoad(&$checkout_workflow, &$shop_closed, $cart_id)
	{
		$app = Factory::getApplication();
		$session = $app->getSession()->get('emundusUser');

		$fnum = $session->fnum;

		if (empty($fnum)) {
			return;
		}

		$pluginsClass = hikashop_get('class.plugins');
		$plugin = $pluginsClass->getByName('hikashop', 'emundus_exed_history');

		$discount_payment_status = $plugin->params['discount_payment_status'];
		$totalite_characteristic_id = $plugin->params['hikashop_totalite_characteristic'];
		$element_totalite_id = $plugin->params['hikashop_totalite_element'];
		$discounted_characteristic_id = $plugin->params['hikashop_discount_characteristic'];
		$element_discount_id = $plugin->params['hikashop_discount_element'];

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
		$h_files = new EmundusHelperFiles();
		$element_totalite = $h_files->getFabrikElementData($element_totalite_id);
		$element_discount = $h_files->getFabrikElementData($element_discount_id);

		if (!is_numeric($discount_payment_status) || empty($totalite_characteristic_id) || empty($discounted_characteristic_id)) {
			return;
		}

		if (!empty($cart_id) && !empty($data['fnum'])) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('status')
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where('fnum = ' . $db->quote($data['fnum']));

			$db->setQuery($query);
			$status = $db->loadResult();

			if ($status == $discount_payment_status) {
				require_once (JPATH_ROOT . '/components/com_emundus/models/payment.php');
				$m_payment = new EmundusModelPayment();
				$app = Factory::getApplication();
				$cart_products = $m_payment->getCartProducts($cart_id);

				if (!empty($cart_products)) {
					require_once (JPATH_ROOT . '/components/com_emundus/models/files.php');
					$m_files = new EmundusModelFiles();

					$parent_product_id = (int)current($cart_products)->product_id;

					$total_applicant_price = $m_files->getFabrikValue([$fnum], $element_totalite->db_table_name, $element_totalite->name)[$fnum];
					$discounted_applicant_price = $m_files->getFabrikValue([$fnum], $element_discount->db_table_name, $element_discount->name)[$fnum];

					if (empty($prices)) {
						$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_NO_PRICES_FOUND'), 'error');
						$app->redirect('/');
					} else {

						if ($element_totalite->plugin == 'currency') {
							$total_applicant_price = $this->rawValueFromCurrencyElement($total_applicant_price);
						}

						if ($element_discount->plugin == 'currency') {
							$discounted_applicant_price = $this->rawValueFromCurrencyElement($discounted_applicant_price);
						}

						$user_id          = $m_payment->getUserIdFromFnum($data['fnum']);
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
								// create the product variation
								$variant_totalite_product_id = $m_payment->createHikashopProduct($parent_product->product_name . ' - ' . $hikashop_user_id, (float)$total_applicant_price, $parent_product->product_code . '-' . $hikashop_user_id, $parent_product->category_id, 'variant', $parent_product->product_id, $totalite_characteristic_id, $hikashop_user_id);
							} else {
								// update the product variation price
								$updated_price = $m_payment->updateHikashopProductPrice($variant_totalite_product_id, (float)$total_applicant_price);

								if (!$updated_price) {
									$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_PRODUCT_PRICE_NOT_UPDATED'), 'error');
									$app->redirect('/');
								}
							}

							$variant_discounted_product_id = $m_payment->getHikashopProductVariantForUser($parent_product_id, $hikashop_user_id, $discounted_characteristic_id);

							if ($variant_discounted_product_id == $parent_product_id || empty($variant_discounted_product_id)) {
								// create the product variation
								$product_label = $parent_product->product_name . ' - ' . $hikashop_user_id . '-' .$discounted_characteristic_id;
								$product_code = $parent_product->product_code . '-' . $hikashop_user_id . '-' . $discounted_characteristic_id;

								$variant_discounted_product_id = $m_payment->createHikashopProduct($product_label, (float)$discounted_applicant_price, $product_code, (int)$parent_product->category_id, 'variant', $parent_product->product_id, $discounted_characteristic_id, $hikashop_user_id);
							} else {
								// update the product variation price
								$updated_price = $m_payment->updateHikashopProductPrice($variant_discounted_product_id, (float)$discounted_applicant_price);

								if (!$updated_price) {
									$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_PRODUCT_PRICE_NOT_UPDATED'), 'error');
									$app->redirect('/');
								}
							}

							if ($variant_totalite_product_id != $parent_product_id) {
								$updated = $m_payment->updateHikashopCart($cart_id, [$variant_totalite_product_id]);

								if (!$updated) {
									$app->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_PRODUCT_NOT_FOUND'), 'error');
									$app->redirect('/');
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

	private function rawValueFromCurrencyElement($value)
	{
		$value = str_replace(' ', '', $value);
		$value = str_replace(',', '.', $value);
		$value = str_replace('€(EUR)', '', $value);

		return $value;
	}
}