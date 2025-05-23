<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2022 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      LEGENDRE Jérémy
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class EmundusModelPayment extends JModelList
{
	public function __construct()
	{
		parent::__construct();

		// Attach logging system.
		jimport('joomla.log.log');
		JLog::addLogger(['text_file' => 'com_emundus.payment.php'], JLog::ALL, array('com_emundus.payment'));
	}

	public function getPrice($fnum)
	{
		$price   = 0;
		$payment = $this->getPaymentInfos($fnum);

		if (!empty($payment)) {
			$isScholarshipHolder = $this->isScholarshipStudent($fnum);
			if ($isScholarshipHolder) {
				$product = $this->getProduct($payment->scholarship_holder_product_id);
			}
			else {
				$product = $this->getProduct($payment->product_id);
			}

			if (!empty($product)) {
				$sort_price = str_replace(',', '', $product->product_sort_price);
				$price      = number_format((double) $sort_price, 2, ',', '');
			}
			else {
				JLog::add('Error getting product price : product is empty', JLog::WARNING, 'com_emundus.payment');
			}
		}
		else {
			JLog::add('Error getting product price : payment is empty', JLog::WARNING, 'com_emundus.payment');
		}

		return $price;
	}

	/**
	 * Detect if student is a scholarship student
	 *
	 * @param $fnum string
	 *
	 * @return bool
	 */
	public function isScholarshipStudent($fnum): bool
	{
		$amIScholarshipStudent = false;

		$params                  = JComponentHelper::getParams('com_emundus');
		$scholarship_document_id = $params->get('scholarship_document_id', null);

		if (!empty($scholarship_document_id)) {
			$db    = JFactory::getDBO();
			$query = $db->getQuery(true);

			$query->select('COUNT(id)')
				->from('#__emundus_uploads')
				->where('fnum = ' . $db->quote($fnum))
				->andWhere('attachment_id = ' . $db->quote($scholarship_document_id));

			$db->setQuery($query);

			try {
				$amIScholarshipStudent = $db->loadResult() > 0;
			}
			catch (Exception $e) {
				JLog::add('Error getting scholarship student infos from fnum (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $amIScholarshipStudent;
	}

	/**
	 * @return bool
	 */
	public function doesScholarshipHoldersNeedToPay(): bool
	{
		$params = JComponentHelper::getParams('com_emundus');

		return $params->get('pay_scholarship', 0) == 1;
	}

	public function setPaymentUniqid($fnum)
	{
		$uniqid = $fnum . '-' . uniqid();
		$db     = JFactory::getDbo();
		$query  = $db->getQuery(true);

		$query->select('id, order_id, params')
			->from($db->quoteName('#__emundus_hikashop'))
			->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

		$db->setQuery($query);
		$emundusHikashop = $db->loadObject();

		if (empty($emundusHikashop->id)) {
			$this->createPaymentOrder($fnum, 'flywire');
			$emundusHikashop = $db->loadObject();
		}
		else {
			$this->updateHikashopOrderType($emundusHikashop->order_id, 'flywire');
		}

		if (!empty($emundusHikashop->params)) {
			$params         = json_decode($emundusHikashop->params);
			$params->uniqId = $uniqid;
			$params->type   = 'flywire';
		}
		else {
			$params = array('uniqId' => $uniqid);
		}

		$query->clear();
		$query->update($db->quoteName('#__emundus_hikashop'))
			->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
			->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
		$db->setQuery($query);

		try {
			$db->execute();
		}
		catch (Exception $e) {
			$uniqid = false;
			JLog::add('Error setting payment uniqid (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
		}

		return $uniqid;
	}

	public function createPaymentOrder($fnum, $type, $order_number = null)
	{
		$order_id         = 0;
		$created          = false;
		$user_id          = $this->getUserIdFromFnum($fnum);
		$hikashop_user_id = $this->getHikashopUserId($user_id);

		if (!empty($hikashop_user_id)) {
			$price = $this->getPrice($fnum);
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$columns = array('order_user_id', 'order_status', 'order_created', 'order_modified', 'order_type', 'order_payment_method', 'order_full_price', 'order_number');
			$values  = $db->quote($hikashop_user_id) . ', ' . $db->quote('created') . ', ' . $db->quote(time()) . ', ' . $db->quote(time()) . ', ' . $db->quote('sale') . ', ' . $db->quote($type) . ', ' . $db->quote($price);

			if ($order_number !== null) {
				$values .= ', ' . $db->quote($order_number);
			}
			else {
				$values .= ', ' . rand(100000, 999999);
			}

			$query->clear()
				->insert($db->quoteName('#__hikashop_order'))
				->columns($db->quoteName($columns))
				->values($values);

			$db->setQuery($query);

			try {
				$created  = $db->execute();
				$order_id = $db->insertid();
			}
			catch (Exception $e) {
				JLog::add('Error creating payment order : ' . $e->getMessage(), JLog::WARNING, 'com_emundus.payment');
			}

			if ($created && !empty($order_id)) {
				$hikashop_product = $this->getProductByFnum($fnum);
				if (!empty($hikashop_product)) {
					$query->clear()
						->insert('#__hikashop_order_product')
						->columns(['order_id', 'product_id', 'order_product_name', 'order_product_code', 'order_product_price'])
						->values($order_id . ', ' . $hikashop_product->product_id . ', ' . $db->quote($hikashop_product->product_name) . ',' . $db->quote($hikashop_product->product_code) . ',' . $db->quote($hikashop_product->product_sort_price));

					try {
						$db->setQuery($query);
						$inserted = $db->execute();
					}
					catch (Exception $e) {
						JLog::add('Error inserting payment order product row : ' . $e->getMessage(), JLog::WARNING, 'com_emundus.payment');
					}
				}

				$this->updateEmundusHikashopOrderId($fnum, $order_id);
			}
		}
		else {
			JLog::add('Error creating payment order : user is empty', JLog::WARNING, 'com_emundus.payment');
		}

		return $order_id;
	}

	public function getUserIdFromFnum($fnum): int
	{
		$applicant_id = 0;

		if (!empty($fnum)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('applicant_id')
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

			try {
				$db->setQuery($query);
				$applicant_id = (int)$db->loadResult();
			} catch (Exception $e) {
				JLog::add('Error getting user id from fnum : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $applicant_id;
	}

	public function getHikashopUserId($user_id): int
	{
		$hikashop_user_id = 0;

		if (!empty($user_id))
		{
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('jhu.user_id')
				->from('#__hikashop_user AS jhu')
				->where('jhu.user_cms_id = ' . $user_id);

			try {
				$db->setQuery($query);
				$hikashop_user_id = $db->loadResult();

				if (empty($hikashop_user_id))
				{
					$hikashop_user_id = $this->createHikashopUser($user_id);
				}
			} catch (Exception $e) {
				JLog::add('Error getting hikashop user id from user id : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return (int)$hikashop_user_id;
	}

	private function createHikashopUser($user_id)
	{
		$hikashop_user_id = 0;

		if (!empty($user_id)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('email')
				->from('#__users')
				->where('id = ' . $user_id);

			try {
				$db->setQuery($query);
				$email = $db->loadResult();

				$query->clear();
				$query->insert('#__hikashop_user')
					->columns('user_cms_id, user_email')
					->values($user_id . ', ' . $db->quote($email));

				try {
					$db->setQuery($query);
					$inserted = $db->execute();

					if ($inserted) {
						$query->clear()
							->select('user_id')
							->from('#__hikashop_user')
							->where('user_cms_id = ' . $user_id);

						$db->setQuery($query);
						$hikashop_user_id = $db->loadResult();
					}
				} catch (Exception $e) {
					JLog::add('Error creating hikashop user : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
				}
			} catch (Exception $e) {
				JLog::add('Error getting email from user id : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $hikashop_user_id;
	}

	/**
	 * @param $fnum     string
	 * @param $order_id int
	 *
	 * @return bool
	 */
	private function updateEmundusHikashopOrderId($fnum, $order_id): bool
	{
		$updated = false;

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('id')
			->from('#__emundus_hikashop')
			->where('fnum = ' . $db->quote($fnum));

		$db->setQuery($query);

		try {
			$id = $db->loadResult();
		}
		catch (Exception $e) {
			JLog::add('Error getting id from fnum : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');

			return false;
		}

		if (empty($id)) {
			$updated = $this->insertEmundusHikashopOrderId($fnum, $order_id);
		}
		else {
			$query->clear();
			$query->update($db->quoteName('#__emundus_hikashop'))
				->set($db->quoteName('order_id') . ' = ' . $db->quote($order_id))
				->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

			$db->setQuery($query);

			try {
				$updated = $db->execute();
			}
			catch (Exception $e) {
				JLog::add('Error updating emundus hikashop order id : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $updated;
	}

	/**
	 * @param $fnum
	 * @param $order_id
	 *
	 * @return bool
	 */
	private function insertEmundusHikashopOrderId($fnum, $order_id): bool
	{
		$inserted = false;
		$db       = JFactory::getDbo();
		$query    = $db->getQuery(true);

		require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		$m_files   = new EmundusModelFiles;
		$fnumInfos = $m_files->getFnumInfos($fnum);

		if (!empty($fnumInfos)) {
			$query->clear()
				->insert($db->quoteName('#__emundus_hikashop'))
				->columns($db->quoteName(array('user', 'fnum', 'status', 'order_id', 'campaign_id')))
				->values($db->quote($fnumInfos['applicant_id']) . ', ' . $db->quote($fnum) . ', ' . $db->quote($fnumInfos['status']) . ', ' . $db->quote($order_id) . ', ' . $db->quote($fnumInfos['campaign_id']));

			$db->setQuery($query);

			try {
				$inserted = $db->execute();
			}
			catch (Exception $e) {
				JLog::add('Error inserting emundus hikashop order id : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}
		else {
			JLog::add('Error getting fnum infos : fnum is empty', JLog::WARNING, 'com_emundus.payment');
		}

		return $inserted;
	}

	public function getPaymentInfos($fnum)
	{
		$payment = false;

		if (!empty($fnum)) {
			$db    = JFactory::getDBO();
			$query = $db->getQuery(true);

			$query->select('jespbc.*, jec.campaign_id')
				->from('#__emundus_setup_payments_by_campaign AS jespbc')
				->leftJoin('#__emundus_setup_payments_by_campaign_repeat_campaign_id as jespbcr ON jespbcr.parent_id = jespbc.id')
				->leftJoin('#__emundus_campaign_candidature as jec ON jec.campaign_id = jespbcr.campaign_id')
				->where('jec.fnum = ' . $db->quote($fnum));

			$db->setQuery($query);

			try {
				$payment = $db->loadObject();
			}
			catch (Exception $e) {
				JLog::add('Error getting payment infos from fnum (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
			}
		}
		else {
			JLog::add('Error getting payment infos from fnum : fnum is empty', JLog::WARNING, 'com_emundus.payment');
		}

		return $payment;
	}

	public function getProduct($product_id)
	{
		$product = false;

		if (!empty($product_id)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('*')
				->from('#__hikashop_product')
				->where($db->quoteName('product_id') . ' = ' . $db->quote($product_id));

			try {
				$db->setQuery($query);
				$product = $db->loadObject();
			}
			catch (Exception $e) {
				JLog::add('Error getting product (' . $product_id . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
			}
		}
		else {
			JLog::add('Error getting product : product_id is empty', JLog::WARNING, 'com_emundus.payment');
		}

		return $product;
	}

	public function getProductByFnum($fnum)
	{
		$product = null;

		if (!empty($fnum)) {
			$payment = $this->getPaymentInfos($fnum);

			if (!empty($payment)) {
				$isScholarshipHolder = $this->isScholarshipStudent($fnum);
				if ($isScholarshipHolder) {
					$product = $this->getProduct($payment->scholarship_holder_product_id);
				}
				else {
					$product = $this->getProduct($payment->product_id);
				}

				if (!empty($product)) {
					$sort_price               = str_replace(',', '', $product->product_sort_price);
					$price                    = number_format((double) $sort_price, 2, ',', '');
					$product->displayed_price = $price;
				}
			}
			else {
				JLog::add('Error getting product : payment infos are empty', JLog::WARNING, 'com_emundus.payment');
			}
		}

		return $product;
	}

	/**
	 * @param   string  $callback_id
	 * @param   array   $data
	 *
	 * @return bool
	 */
	public function updateFlywirePaymentInfos($callback_id, $data)
	{
		$updated = false;
		$fnum    = $this->getFnumFromCallbackId($callback_id);

		if (!empty($fnum)) {
			require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
			require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
			$m_files      = new EmundusModelFiles;
			$fnum_infos   = $m_files->getFnumInfos($fnum);
			$check_amount = $this->checkAmountCoherence($fnum, $data['amount']);

			if ($check_amount === true) {
				$hikashop_status = '';
				switch ($data['status']) {
					case 'initiated': // Payment is being initiated
						$hikashop_status = 'created';
						break;
					case 'guaranteed': // funds are received by Flywire, so pending in hikashop
						$hikashop_status = 'pending';
						break;
					case 'delivered': // funds are delivered to the customer, so confirmed in hikashop
						$hikashop_status = 'confirmed';
						$this->updateFnumStateFromFlywire($fnum);
						break;
					case 'cancelled': // funds are cancelled
						$hikashop_status = 'cancelled';
						break;
					default:
						// do nothing, each case must be handled separately
						JLog::add('Error updating flywire payment infos : status ' . $data['status'] . ' is not handled', JLog::ERROR, 'com_emundus.payment');
						break;
				}

				if (!empty($hikashop_status)) {
					$updated         = $this->updateHikashopPayment($fnum, $hikashop_status, $data, 'flywire', $data['id']);
					$data['updated'] = $updated;
					EmundusModelLogs::log(95, $fnum_infos['applicant_id'], $fnum, 38, 'u', 'COM_EMUNDUS_PAYMENT_UPDATE_FLYWIRE_PAYMENT_INFOS', json_encode($data));
				}
				else {
					EmundusModelLogs::log(95, $fnum_infos['applicant_id'], $fnum, 38, 'u', 'COM_EMUNDUS_PAYMENT_UPDATE_FLYWIRE_PAYMENT_INFOS', 'Error updating flywire payment infos from given data ' . json_encode($data));
				}
			}
			else {
				EmundusModelLogs::log(95, $fnum_infos['applicant_id'], $fnum, 38, 'u', 'COM_EMUNDUS_PAYMENT_UPDATE_FLYWIRE_INCOHERENT_PAYMENT_INFOS', $data['amount'] . ' != ' . $this->getPrice($fnum));
				JLog::add('Error updating flywire payment infos : amount is not coherent for fnum ' . $fnum, JLog::ERROR, 'com_emundus.payment');
			}
		}
		else {
			JLog::add('Error updating flywire payment infos : callback_id is not correct, could be a malicious attempt', JLog::ERROR, 'com_emundus.payment');
		}

		return $updated;
	}

	/**
	 * Find callback_id in emundus_hikashop table and return fnum
	 *
	 * @param   string  $callback_id
	 *
	 * @return string|false
	 */
	private function getFnumFromCallbackId($callback_id)
	{
		$fnum = false;

		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('fnum, params')
			->from('#__emundus_hikashop')
			->where($db->quoteName('params') . ' LIKE "%' . $callback_id . '%"');

		$db->setQuery($query);

		try {
			$fnums = $db->loadObjectList();

			foreach ($fnums as $fnum_infos) {
				$params = json_decode($fnum_infos->params, true);
				if (isset($params['uniqId']) && $params['uniqId'] == $callback_id) {
					$fnum = $fnum_infos->fnum;
					break;
				}
			}
		}
		catch (Exception $e) {
			JLog::add('Error getting fnum from callback id : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
		}

		return $fnum;
	}

	/**
	 * Find callback_id in emundus_hikashop table and return fnum
	 *
	 * @param   string  $callback_id
	 *
	 * @return string|false
	 */
	private function getFnumFromOrderId($order)
	{
		$fnum = false;

		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('fnum')
			->from('#__emundus_hikashop')
			->where($db->quoteName('order_id') . ' = ' . $order);
		$db->setQuery($query);

		try {
			$fnum = $db->loadResult();
		}
		catch (Exception $e) {
			JLog::add('Error getting fnum from callback id : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
		}

		return $fnum;
	}

	private function checkAmountCoherence($fnum, $amount)
	{
		$correctAmount = false;

		$price = (float) $this->getPrice($fnum);

		if (!empty($price)) {
			$correctAmount = (float) $amount == (float) $price;
			if ($correctAmount === false) {
				JLog::add('Amount sent through callback does not correspond to payment on eMundus platform', JLog::WARNING, 'com_emundus.payment');
			}
		}
		else {
			JLog::add('Error checking amount coherence : price is empty', JLog::ERROR, 'com_emundus.payment');
		}


		return $correctAmount;
	}

	private function updateHikashopPayment($fnum, $hikashop_status, $data, $type = 'flywire', $order_number = null)
	{
		$updated = false;
		$db      = JFactory::getDBO();
		$query   = $db->getQuery(true);

		$query->select('*')
			->from('#__emundus_hikashop')
			->where('fnum = ' . $db->quote($fnum));
		$db->setQuery($query);

		try {
			$hikashop = $db->loadObject();
		}
		catch (Exception $e) {
			JLog::add('Error getting hikashop infos from fnum (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
		}

		if (!empty($hikashop)) {
			$query->clear();
			$query->update('#__hikashop_order')
				->set('order_status = ' . $db->quote($hikashop_status))
				->set('order_invoice_id = ' . $db->quote($data['id']));

			if (!empty($order_number)) {
				$query->set('order_number = ' . $db->quote($order_number));
			}

			$query->where('order_id = ' . $hikashop->order_id);
			$db->setQuery($query);

			try {
				$updated = $db->execute();
			}
			catch (Exception $e) {
				JLog::add('Error updating hikashop infos from fnum (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
			}

			if ($updated) {
				$params = json_decode($hikashop->params, true);
				if ($type == 'flywire') {
					$params['flywire_id']     = $data['id'];
					$params['flywire_status'] = $data['status'];
					$params['flywire_amount'] = $data['amount'];
					$params['flywire_at']     = $data['at'];
					$params['initiator']      = 'flywire';
				}

				$query->clear();
				$query->update('#__emundus_hikashop')
					->set('params = ' . $db->quote(json_encode($params)))
					->where('fnum = ' . $db->quote($fnum));
				$db->setQuery($query);

				try {
					$updated = $db->execute();
				}
				catch (Exception $e) {
					JLog::add('Error updating hikashop infos from fnum (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
				}
			}
		}
		else {
			JLog::add('Error updating hikashop infos from fnum (' . $fnum . ') : hikashop infos are empty', JLog::ERROR, 'com_emundus.payment');
		}

		return $updated;
	}

	private function updateFnumStateFromFlywire($fnum): bool
	{
		$updated = false;

		require_once JPATH_ROOT . '/components/com_emundus/models/profile.php';
		$m_profiles = new EmundusModelProfile();
		$profile    = $m_profiles->getProfileByStatus($fnum);

		if (!empty($profile['menutype'])) {
			$db    = JFactory::getDBO();
			$query = $db->getQuery(true);

			$query->select('modu.id, modu.params')
				->from('#__modules as modu')
				->leftJoin('#__modules_menu as modm ON modm.moduleid = modu.id')
				->leftJoin('#__menu as menu ON menu.id = modm.menuid')
				->where('menu.menutype = ' . $db->quote($profile['menutype']))
				->andWhere('modu.module = ' . $db->quote('mod_emundus_payment'))
				->andWhere('modu.published = 1');

			$db->setQuery($query);

			try {
				$module = $db->loadObject();
			}
			catch (Exception $e) {
				JLog::add('Error getting module infos from fnum (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
			}

			if (!empty($module)) {
				$params                    = json_decode($module->params, true);
				$params['payment_methods'] = json_decode($params['payment_methods'], true);
				$flywire_method_index      = array_search('flywire', $params['payment_methods']['payment_method']);
				if ($flywire_method_index !== false) {
					$status = $params['payment_methods']['payment_confirmed_state'][$flywire_method_index];

					if (isset($status)) {
						require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
						$m_files = new EmundusModelFiles();
						$updated = $m_files->updateState($fnum, $status);
					}
				}
			}
		}
		else {
			JLog::add('Error updating fnum state after flywire confirmed payment : profile is empty', JLog::ERROR, 'com_emundus.payment');
		}

		return $updated;
	}

	public function getFlywireExtendedConfig($config)
	{
		JPluginHelper::importPlugin('emundus');

		$extended_config = JFactory::getApplication()->triggerEvent('onCallEventHandler', ['extendFlywireConfig', ['config' => $config]]);

		if (!empty($extended_config)) {
			foreach ($extended_config[0] as $extend) {
				$config = array_merge($config, $extend);
			}
		}

		return $config;
	}

	/**
	 * @param $fnum
	 *
	 * @return array
	 */
	public function getConfig($fnum): array
	{
		$config = array();

		if (!empty($fnum)) {
			$db    = JFactory::getDBO();
			$query = $db->getQuery(true);

			$query->select('params')
				->from('#__emundus_hikashop')
				->where('fnum = ' . $db->quote($fnum));
			$db->setQuery($query);

			try {
				$params = $db->loadResult();
			}
			catch (Exception $e) {
				JLog::add('Error getting hikashop infos from fnum (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
			}

			$config = json_decode($params, true);
			if (empty($config) && empty($config['sender_first_name'])) {
				$config = array();

				$query->clear()
					->select('ju.email, jepd.first_name, jepd.last_name, dc.code_iso_2, jepd.city_1, jepd.telephone_1, jepd.street_1')
					->from('#__users as ju')
					->leftJoin('#__emundus_campaign_candidature as jecc ON jecc.user_id = ju.id')
					->leftJoin('#__emundus_personal_detail as jepd ON jepd.fnum LIKE jecc.fnum')
					->leftJoin('data_country AS dc ON dc.id = jepd.country_1')
					->where('jecc.fnum LIKE ' . $db->quote($fnum));

				try {
					$db->setQuery($query);
					$data = $db->loadObject();
				}
				catch (Exception $e) {
					JLog::add('Error trying to get peronal_details for payment infos', JLog::ERROR, 'com_emundus.payment');
				}

				if (!empty($data)) {
					$config = array(
						"sender_first_name" => $data->first_name,
						"sender_last_name"  => $data->last_name,
						"sender_email"      => $data->email,
						"sender_address1"   => $data->street_1,
						"sender_city"       => $data->city_1,
						"sender_country"    => $data->code_iso_2,
						"sender_phone"      => $data->telephone_1
					);
				}
			}
		}

		return $config;
	}

	/**
	 * @param $fnum       string
	 * @param $new_config array
	 *
	 * @return bool
	 */
	public function saveConfig($fnum, $new_config): bool
	{
		$saved = false;

		if (!empty($fnum)) {
			$config = $this->getConfig($fnum);

			if (!empty($config)) {
				$new_config = array_merge($config, $new_config);

				$db    = JFactory::getDBO();
				$query = $db->getQuery(true);

				$query->update('#__emundus_hikashop')
					->set('params = ' . $db->quote(json_encode($new_config)))
					->where('fnum = ' . $db->quote($fnum));
				$db->setQuery($query);

				try {
					$saved = $db->execute();
				}
				catch (Exception $e) {
					JLog::add('Error saving hikashop infos from fnum (' . $fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
				}
			}
		}
		else {
			JLog::add('Error saving config : fnum is empty', JLog::ERROR, 'com_emundus.payment');
		}

		return $saved;
	}

	public function updateFileTransferPayment($user)
	{
		$updated = false;

		require_once JPATH_ROOT . '/components/com_emundus/models/profile.php';
		$m_profiles = new EmundusModelProfile();
		$profile    = $m_profiles->getProfileByStatus($user->fnum);

		if (!empty($profile['menutype'])) {
			$db    = JFactory::getDBO();
			$query = $db->getQuery(true);

			$query->select('modu.id, modu.params')
				->from('#__modules as modu')
				->leftJoin('#__modules_menu as modm ON modm.moduleid = modu.id')
				->leftJoin('#__menu as menu ON menu.id = modm.menuid')
				->where('menu.menutype = ' . $db->quote($profile['menutype']))
				->andWhere('modu.module = ' . $db->quote('mod_emundus_payment'))
				->andWhere('modu.published = 1');

			$db->setQuery($query);

			try {
				$module = $db->loadObject();
			}
			catch (Exception $e) {
				JLog::add('Error getting module infos from fnum (' . $user->fnum . ') : ' . $e, JLog::ERROR, 'com_emundus.payment');
			}

			if (!empty($module)) {
				$params                    = json_decode($module->params, true);
				$params['payment_methods'] = json_decode($params['payment_methods'], true);
				$attachmentId              = $params['proof_attachment'];

				$query->clear()
					->select('jeu.id')
					->from('jos_emundus_uploads AS jeu')
					->where('jeu.fnum LIKE ' . $db->quote($user->fnum))
					->andWhere('jeu.attachment_id = ' . $attachmentId);

				$db->setQuery($query);

				try {
					$upload_id = $db->loadResult();
				}
				catch (Exception $e) {
					JLog::add('Error getting upload id from fnum (' . $user->fnum . ') : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
				}

				if (!empty($upload_id)) {
					// create hikashop order for file
					$order_number = $params['reference_prefix'] . substr($user->fnum, 8, 6) . $user->id;

					$query->clear()
						->select('id, order_id')
						->from($db->quoteName('#__emundus_hikashop'))
						->where($db->quoteName('fnum') . ' = ' . $db->quote($user->fnum));

					$db->setQuery($query);
					$emundusHikashop = $db->loadObject();

					if (empty($emundusHikashop->id)) {
						$created = $this->createPaymentOrder($user->fnum, 'transfer', $order_number);
					}
					else {
						$this->updateHikashopOrderType($emundusHikashop->order_id, 'transfer', $order_number);
					}

					$data = [
						'type'         => 'transfer',
						'date'         => time(),
						'status'       => 'sent',
						'order_number' => $order_number
					];

					$query->clear()
						->update($db->quoteName('#__emundus_hikashop'))
						->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($data)))
						->where($db->quoteName('fnum') . ' = ' . $db->quote($user->fnum));
					$db->setQuery($query);

					try {
						$updated = $db->execute();
					}
					catch (Exception $e) {
						JLog::add('Error updating emundus hikashop  (' . $user->fnum . ') with data ' . json_encode($data) . ' : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
					}

					if ($updated) {
						$transfer_method_index = array_search('transfer', $params['payment_methods']['payment_method']);
						$status                = $params['payment_methods']['payment_confirmed_state'][$transfer_method_index];

						if (isset($status)) {
							require_once JPATH_ROOT . '/components/com_emundus/models/files.php';
							$m_files = new EmundusModelFiles();
							$updated = $m_files->updateState(array($user->fnum), $status);
						}
					}
					else {
						JLog::add('Updating emundus hikashop  (' . $user->fnum . ') returned false with data ' . json_encode($data), JLog::WARNING, 'com_emundus.payment');
					}
				}
			}
		}

		return $updated;
	}

	/**
	 * @param $order_id
	 * @param $type
	 *
	 * @return $updated bool
	 */
	private function updateHikashopOrderType($order_id, $type, $order_number = null): bool
	{
		$updated = false;

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->clear()
			->update('#__hikashop_order')
			->set('order_payment_method = ' . $db->quote($type))
			->set('order_status = ' . $db->quote('created'));

		if (!empty($order_number)) {
			$query->set('order_number = ' . $db->quote($order_number));
		}

		$query->where('order_id = ' . $order_id);

		$db->setQuery($query);

		try {
			$updated = $db->execute();
		}
		catch (Exception $e) {
			JLog::add('Error trying to update hikashop order type ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
		}

		return $updated;
	}

	public function updateAxeptaPaymentInfos($order, $status, $id)
	{
		$updated = false;
		$fnum    = $this->getFnumFromOrderId($order);
		$price   = $this->getPrice($fnum);

		JLog::add('[updateAxeptaPaymentInfos] Update file ' . $fnum . ' in order : ' . $order . ' with status ' . $status, JLog::INFO, 'com_emundus.payment');

		if (!empty($fnum)) {
			require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'logs.php');
			require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
			require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'controllers' . DS . 'messages.php');
			$m_files    = new EmundusModelFiles();
			$c_messages = new EmundusControllerMessages();
			$fnum_infos = $m_files->getFnumInfos($fnum);

			$hikashop_status = '';
			$eMConfig        = JComponentHelper::getParams('com_emundus');
			$mail_template   = 0;

			switch ($status) {
				case 'OK':
				case 'AUTHORIZED':
					$hikashop_status = 'confirmed';

					$status_after_payment = $eMConfig->get('status_after_payment', '');

					if ($status_after_payment !== '') {
						JLog::add('[updateAxeptaPaymentInfos] Update file status to ' . $status_after_payment, JLog::INFO, 'com_emundus.payment');
						$m_files->updateState($fnum, $status_after_payment);
					}

					$mail_template = $eMConfig->get('axepta_success_mail', 0);
					break;
				case 'FAILED':
				case '':
					$mail_template = $eMConfig->get('axepta_failed_mail', 0);

					$hikashop_status = 'cancelled';
					break;
				default:
					// do nothing, each case must be handled separately
					JLog::add('Error updating axepta payment infos : status ' . $status . ' is not handled', JLog::ERROR, 'com_emundus.payment');
					break;
			}

			if (!empty($hikashop_status)) {
				$data['id'] = $id;
				$updated    = $this->updateHikashopPayment($fnum, $hikashop_status, $data, 'axepta');
				EmundusModelLogs::log(95, $fnum_infos['applicant_id'], $fnum, 38, 'u', 'COM_EMUNDUS_PAYMENT_UPDATE_AXEPTA_PAYMENT_INFOS', json_encode($data));
			}
			else {
				EmundusModelLogs::log(95, $fnum_infos['applicant_id'], $fnum, 38, 'u', 'COM_EMUNDUS_PAYMENT_UPDATE_AXEPTA_PAYMENT_INFOS', 'Error updating axepta payment infos from given data ' . $status . ',' . $order . ',' . $id);
			}

			if (!empty($mail_template)) {
				$post = [
					'ORDER_NUMBER' => $order,
					'ORDER_PRICE'  => $price . ' €'
				];
				$c_messages->sendEmail($fnum, $mail_template, $post);
			}
		}
		else {
			JLog::add('Error updating axepta payment infos : callback_id is not correct, could be a malicious attempt', JLog::ERROR, 'com_emundus.payment');
		}

		return $updated;
	}

	public function resetPaymentSession()
	{
		JPluginHelper::importPlugin('emundus', 'custom_event_handler');
		\Joomla\CMS\Factory::getApplication()->triggerEvent('onCallEventHandler', ['onHikashopResetSession', ['fnum' => JFactory::getSession()->get('emundusUser')->fnum]]);

		JFactory::getSession()->set('emundusPayment', null);
	}

	/**
	 * @return bool
	 */
	function checkPaymentSession($fnum = null, $caller = ''): bool
	{
		$valid_session = true;
		$app           = JFactory::getApplication();

		if (!$app->isClient('administrator')) {
			$emundus_payment = JFactory::getSession()->get('emundusPayment');
			$user            = JFactory::getSession()->get('emundusUser');

			$fnum_to_check = empty($fnum) ? $user->fnum : $fnum;

			if (empty($emundus_payment)) {
				$emundus_payment          = new StdClass();
				$emundus_payment->user_id = $user->id;
				$emundus_payment->fnum    = empty($fnum) ? $user->fnum : $fnum;

				JFactory::getSession()->set('emundusPayment', $emundus_payment);
			}
			else if ($emundus_payment->fnum != $fnum_to_check) {
				$user->fnum = $emundus_payment->fnum;
				JFactory::getSession()->set('emundusUser', $user);

				if ($caller == 'onAfterCheckoutStep') {
					$app->enqueueMessage(JText::_('CANT_GO_TO_TPE_WRONG_SESSION'), 'error');
				}
				else {
					$app->enqueueMessage(JText::_('ANOTHER_HIKASHOP_SESSION_OPENED'), 'error');
				}

				$this->resetPaymentSession();
				$app->redirect('/');
				$valid_session = false;
			}
		}

		return $valid_session;
	}


	private function getHikashopUser($fnum)
	{
		$hikashop_user = null;

		if (!empty($fnum)) {
			$db    = JFactory::getDBO();
			$query = $db->getQuery(true);

			$query->select('hu.user_id', 'hu.user_mail')
				->from($db->quoteName('#__hikashop_user', 'hu'))
				->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.applicant_id = hu.user_cms_id')
				->where('ecc.fnum LIKE ' . $db->quote($fnum));

			try {
				$db->setQuery($query);
				$hikashop_user = $db->loadObject();
			}
			catch (Exception  $e) {
				JLog::add('Failed to get hikashop user_id from fnum ' . $fnum . ' ' . $e->getMessage(), JLog::ERROR, 'com_emundus.emundus_hikashop_plugin');
			}
		}

		return $hikashop_user;
	}

	/**
	 * @param $fnum
	 * @param $hikashop_product_category
	 * @param $code_like
	 * @return int
	 */
    public function getGeneratedCoupon($fnum, $hikashop_product_category, $code_like = null)
	{
		$discount_id = 0;

		if (!empty($fnum)) {
			$hikashop_user = $this->getHikashopUser($fnum);

			if (!empty($hikashop_user->user_id)) {
				$db    = JFactory::getDBO();
				$query = $db->getQuery(true);

				$query->select('discount_code')
					->from($db->quoteName('#__hikashop_discount'))
					->where('discount_user_id = ' . $hikashop_user->user_id)
					->andWhere('discount_published = 1')
					->andWhere('discount_used_times < 1')
					->andWhere('discount_start <= ' . time())
                    ->andWhere('discount_end > '  . time())
                    ->andWhere('discount_category_id = ' . $db->quote($hikashop_product_category) . ' OR discount_category_id = ' . $db->quote($hikashop_product_category . ',%'));

				if (!empty($code_like)) {
					$query->andWhere('discount_code LIKE ' . $db->quote($code_like));
				}

				$query->order('discount_id DESC');

				try {
					$db->setQuery($query);
					$discount_id = $db->loadResult();
				}
				catch (Exception $e) {
					JLog::add('Failed to get discount coupon for fnum ' . $fnum . ' ' . $e->getMessage(), JLog::ERROR, 'com_emundus.emundus_hikashop_plugin');
				}
			}
		}

		return $discount_id;
	}

	/**
	 * Create a discount coupon for given user
	 * $fnum string
	 * $discount_amount price or percent of the discount
	 * $discount_amount_type flat (for price) OR percent
	 * $hikashop_product_category category on which discount can be applied
	 * $discount_duration
	 */
	public function generateCoupon($fnum, $discount_amount, $discount_amount_type = 'flat', $hikashop_product_category = 0, $discount_duration = 1800)
	{
		$discount_code = '';

		if (!empty($fnum)) {
			$discount_code = $fnum . '-REDUCTION-' . uniqid();
			$db            = JFactory::getDBO();
			$query         = $db->getQuery(true);

			$hikashop_user = $this->getHikashopUser($fnum);

			if (!empty($hikashop_user->user_id)) {
				$columns = ['discount_code', 'discount_type', 'discount_start', 'discount_end', 'discount_user_id', 'discount_quota', 'discount_published', 'discount_currency_id'];
				$values  = $db->quote($discount_code) . ',' . $db->quote('coupon') . ',' . $db->quote(time()) . ',' . $db->quote(time() + $discount_duration) . ',' . $hikashop_user->user_id . ', 1, 1, 1';

				if ($discount_amount_type == 'flat') {
					$columns[] = 'discount_flat_amount';
				}
				else {
					$columns[] = 'discount_percent_amount';
				}
				$values .= ', ' . $db->quote($discount_amount);

				if (!empty($hikashop_product_category)) {
					$columns[] = 'discount_category_id';
					$values    .= ', ' . $db->quote($hikashop_product_category);
				}

				$query->clear()
					->insert($db->quoteName('#__hikashop_discount'))
					->columns($columns)
					->values($values);
				try {
					$db->setQuery($query);
					$inserted = $db->execute();

					if (!$inserted) {
						$discount_code = '';
					}
				}
				catch (Exception $e) {
					JLog::add('Failed to generate discount coupon for fnum ' . $fnum . ' ' . $e->getMessage(), JLog::ERROR, 'com_emundus.emundus_hikashop_plugin');
				}
			}
		}
	}


	public function didUserPay($user, $fnum, $product_id) {
		$paid = false;
		$hika_user_id = $this->getHikashopUserId($user);

		if (!empty($hika_user_id)) {
			// Select all order confirmed or shipped for this user and product
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select($db->quoteName('jho.order_id'))
				->from($db->quoteName('#__hikashop_order', 'jho'))
				->join('INNER', $db->quoteName('#__hikashop_order_product', 'jhop') . ' ON (' . $db->quoteName('jho.order_id') . ' = ' . $db->quoteName('jhop.order_id') . ')')
				->where($db->quoteName('jho.order_user_id') . ' = ' . $hika_user_id)
				->andWhere($db->quoteName('jho.order_type') . ' = ' . $db->quote('sale'))
				->andWhere($db->quoteName('jho.order_status') . ' IN ("confirmed", "shipped")')
				->andWhere($db->quoteName('jhop.product_id') . ' = ' . $product_id);

			try {
				$db->setQuery($query);
				$order_ids = $db->loadColumn();

				if (!empty($order_ids)) {
					// check if order ids are all linked to user in emundus_hikashop table
					$query->clear()
						->select('order_id, fnum')
						->from($db->quoteName('#__emundus_hikashop'))
						->where($db->quoteName('order_id') . ' IN (' . implode(',', $order_ids) . ')')
						->andWhere($db->quoteName('user') . ' = ' . $user);

					$emundus_orders = $db->setQuery($query)->loadObjectList();

					$emundus_order_ids = array_column($emundus_orders, 'order_id');
					$emundus_fnums = array_column($emundus_orders, 'fnum');

					$not_linked_order_ids = array_diff($order_ids, $emundus_order_ids);
					if (!empty($not_linked_order_ids)) {
						$paid = true;
					} else if (in_array($fnum, $emundus_fnums)) {
						$paid = true;
					}
				}
			} catch (Exception $e) {
				JLog::add('Error getting hikashop orders for user ID: '.$hika_user_id . ' - ' . $e->getMessage(), JLog::ERROR, 'com_emundus.emundus_hikashop_plugin');
			}
		}

		return $paid;
	}

	/**
	 * Ajoute les données du virement aux différents versements sélectionnés (use case FHDP)
	 *
	 * @param $rows array
	 * @param $table_name string
	 * @param $date_virement string
	 * @param $numero_virement string
	 *
	 * @return bool
	 */
	public function insertVirementData($rows, $table_name, $date_virement, $numero_virement)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		try
		{
			$query->update($db->quoteName($table_name))
				->set('date_virement = '.$db->quote($date_virement))
				->set('numero_virement = '.$db->quote($numero_virement))
				->where('id IN ('.implode(',', $db->quote($rows)).')');
			$db->setQuery($query);
			$db->execute();

			return true;
		}
		catch (Exception $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return false;
		}
	}

	/*
	 * Create a hikashop product
	 * @param $label string
	 * @param $price float
	 * @param $code string
	 * @param $category int
	 * @param $type string allowed values : main, variant
	 * @param $parent_id int
	 */
	public function createHikashopProduct(string $label, float $price, string $code = '', int $category = 0, string $type = 'main', int $parent_id = 0, $variant_characteristic_id = 0, $to_user = 0, $vendor_id = 0): int
	{
		$product_id = 0;

		if (!empty($label) && !empty($price)) {
			if (empty($code)) {
				// sanitize label to create a code
				$code = preg_replace('/[^a-z0-9]/', '', strtolower($label)) . '-' . uniqid();
			}

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$columns = [
				'product_parent_id',
				'product_name',
				'product_description',
				'product_url',
				'product_keywords',
				'product_meta_description',
				'product_description_raw',
				'product_average_score',
				'product_quantity',
				'product_code',
				'product_published',
				'product_type',
				'product_sort_price'
			];

			$values = [
				$parent_id,
				$db->quote($label),
				$db->quote(''),
				$db->quote(''),
				$db->quote(''),
				$db->quote(''),
				$db->quote(''),
				0,
				-1,
				$db->quote($code),
				$db->quote('1'),
				$db->quote($type),
				$db->quote($price)
			];

			// Check if product_vendor_id and product_vendor_params exist
			$table_query = 'SHOW COLUMNS FROM #__hikashop_product';
			$db->setQuery($table_query);
			$columns_info = $db->loadColumn();

			if (in_array('product_vendor_id', $columns_info)) {
				$columns[] = 'product_vendor_id';
				$values[] = $db->quote($vendor_id);
			}
			if (in_array('product_vendor_params', $columns_info)) {
				$columns[] = 'product_vendor_params';
				$values[] = $db->quote('');
			}

			$query->insert('#__hikashop_product')
				->columns($columns)
				->values(implode(',', $values));

			try {
				$db->setQuery($query);
				$inserted = $db->execute();

				if ($inserted) {
					$product_id = $db->insertid();

					if (!empty($category)) {
						$query->clear()
							->insert('#__hikashop_product_category')
							->columns(['product_id', 'category_id'])
							->values($product_id . ', ' . $category);
						$db->setQuery($query);
						$db->execute();
					}

					Log::add('Product created with ID ' . $product_id, Log::INFO, 'com_emundus.payment');

					// need to insert into price table
					$columns = ['price_currency_id', 'price_product_id', 'price_value'];
					$values = [1, $product_id, $price];

					if (!empty($to_user)) {
						$columns[] = 'price_users';
						$values[] = $db->quote(','. $to_user . ',');
					}

					$query->clear()
						->insert('#__hikashop_price')
						->columns($columns)
						->values(implode(', ', $values));

					$db->setQuery($query);
					$inserted_price = $db->execute();

					if ($inserted_price) {
						Log::add('Price created for product ID ' . $product_id, Log::INFO, 'com_emundus.payment');
					} else {
						Log::add('Error creating price for product ID ' . $product_id, Log::ERROR, 'com_emundus.payment');
					}

					if ($type === 'variant') {
						$query->clear()
							->insert('#__hikashop_variant')
							->columns(['variant_product_id', 'variant_characteristic_id'])
							->values($product_id . ', ' . $variant_characteristic_id);
						$db->setQuery($query);
						$inserted_variant_characteristic = $db->execute();

						if ($inserted_variant_characteristic) {
							Log::add('Variant characteristic created for product ID ' . $product_id, Log::INFO, 'com_emundus.payment');
						} else {
							Log::add('Error creating variant characteristic for product ID ' . $product_id, Log::ERROR, 'com_emundus.payment');
						}
					}
				}
			} catch (Exception $e) {
				Log::add('Error creating hikashop product : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $product_id;
	}


	public function getProductType($product_id) {
		$type = '';

		if (!empty($product_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('product_type')
				->from('#__hikashop_product')
				->where('product_id = ' . $product_id);

			try {
				$db->setQuery($query);
				$type = $db->loadResult();
			} catch (Exception $e) {
				JLog::add('Error getting product type for product ID ' . $product_id . ' : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $type;
	}

	/**
	 * @param $product_id
	 * @param $new_price
	 *
	 * @return bool
	 */
	public function updateHikashopProductPrice($product_id, $new_price): bool
	{
		$updated = false;

		if (!empty($product_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$updates = [];

			$query->update('#__hikashop_price')
				->set('price_value = ' . $new_price)
				->where('price_product_id = ' . $product_id);

			try {
				$db->setQuery($query);
				$updates[] = $db->execute();
			} catch (Exception $e) {
				Log::add('Error updating hikashop product price : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}

			$query->clear()
				->update('#__hikashop_product')
				->set('product_sort_price = ' . $new_price)
				->where('product_id = ' . $product_id);

			try {
				$db->setQuery($query);
				$updates[] = $db->execute();
			} catch (Exception $e) {
				Log::add('Error updating hikashop product sort price : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}

			$updated = !in_array(false, $updates);

			if ($updated) {
				Log::add('Hikashop product ' . $product_id . ' price updated to ' . $new_price, JLog::INFO, 'com_emundus.payment');
			} else {
				Log::add('Error updating hikashop product id ' . $product_id . ' price', JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $updated;
	}

	/**
	 * get a hikashop product variant associated to a user
	 *
	 * @param $parent_product_id int
	 * @param $hikashop_user_id int
	 * @param $characteristic_id int
	 *
	 * @return int
	 */
	public function getHikashopProductVariantForUser(int $parent_product_id, int $hikashop_user_id, int $characteristic_id): int
	{
		$variant_product_id = $parent_product_id;

		if (!empty($parent_product_id) && !empty($hikashop_user_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('hp.product_id')
				->from($db->quoteName('#__hikashop_product', 'hp'))
				->leftJoin($db->quoteName('#__hikashop_price', 'hprice') . ' ON ' . $db->quoteName('hp.product_id') . ' = ' . $db->quoteName('hprice.price_product_id'))
				->leftJoin($db->quoteName('#__hikashop_variant', 'hv') . ' ON ' . $db->quoteName('hp.product_id') . ' = ' . $db->quoteName('hv.variant_product_id'))
				->where('hp.product_parent_id = ' . $parent_product_id)
				->andWhere('hp.product_type = ' . $db->quote('variant'))
				->andWhere('hp.product_published = 1')
				->andWhere('hp.product_quantity > 0 OR hp.product_quantity = -1')
				->andWhere('hprice.price_users LIKE ' . $db->quote('%,' . $hikashop_user_id . ',%'))
				->andWhere('hv.variant_characteristic_id = ' . $characteristic_id);

			try {
				$db->setQuery($query);
				$found_variant_id = $db->loadResult();

				if (!empty($found_variant_id)) {
					$variant_product_id = $found_variant_id;
				}
			} catch (Exception $e) {
				Log::add('Error getting hikashop product variant for user : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $variant_product_id;
	}

	public function getHikashopProductVariantsAndCharacteristics(int $parent_product_id, int $hikashop_user_id = 0): array
	{
		$characteristics = [];

		if (!empty($parent_product_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('hp.product_id, hp.product_name, hp.product_code, hv.variant_characteristic_id, hc.characteristic_value, hc.characteristic_parent_id, hprice.price_value, hcur.currency_symbol')
				->from($db->quoteName('#__hikashop_product', 'hp'))
				->leftJoin($db->quoteName('#__hikashop_variant', 'hv') . ' ON ' . $db->quoteName('hp.product_id') . ' = ' . $db->quoteName('hv.variant_product_id'))
				->leftJoin($db->quoteName('#__hikashop_characteristic', 'hc') . ' ON ' . $db->quoteName('hv.variant_characteristic_id') . ' = ' . $db->quoteName('hc.characteristic_id'))
				->leftJoin($db->quoteName('#__hikashop_price', 'hprice') . ' ON ' . $db->quoteName('hp.product_id') . ' = ' . $db->quoteName('hprice.price_product_id'))
				->leftJoin($db->quoteName('#__hikashop_currency', 'hcur') . ' ON ' . $db->quoteName('hprice.price_currency_id') . ' = ' . $db->quoteName('hcur.currency_id'))
				->where('hp.product_parent_id = ' . $parent_product_id)
				->andWhere('hp.product_type = ' . $db->quote('variant'))
				->andWhere('hp.product_published = 1')
				->andWhere('hp.product_quantity > 0 OR hp.product_quantity = -1');

			if (!empty($hikashop_user_id)) {
				$query->where('hprice.price_users LIKE ' . $db->quote('%,' . $hikashop_user_id . ',%'));
			}

			$query->order('hc.characteristic_value DESC');

			try {
				$db->setQuery($query);
				$characteristics = $db->loadObjectList();
			} catch (Exception $e) {
				Log::add('Error getting hikashop product variants and characteristics : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $characteristics;
	}

	/**
	 * @param $cart hikashop cart
	 * @param $product_ids array of hikashop product ids
	 * @param $fnum
	 *
	 * @return mixed
	 */
	public function updateHikashopCart($cart_id, $product_ids, $fnum = '')
	{
		$cart_updated = null;

		if (!empty($cart_id) && !empty($product_ids)) {
			$product_list = [];

			foreach ($product_ids as $product_id) {
				$product_list[$product_id] = ['qty' => 1, 'id' => $product_id];
			}

			// reset cart and add the needed product
			require_once(JPATH_ROOT . '/administrator/components/com_hikashop/helpers/helper.php');
			$cartClass = hikashop_get('class.cart');
			$cartClass->resetCart($cart_id);
			$cartClass->addProduct($cart_id, $product_list);
			$cart_updated = $cartClass->get($cart_id);
		}

		return $cart_updated;
	}

	public function getCartProducts($cart_id): array
	{
		$products = [];

		if (!empty($cart_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('hp.*, hcp.cart_product_id')
				->from($db->quoteName('#__hikashop_cart_product', 'hcp'))
				->leftJoin($db->quoteName('#__hikashop_product', 'hp') . ' ON ' . $db->quoteName('hcp.product_id') . ' = ' . $db->quoteName('hp.product_id'))
				->where('hcp.cart_id = ' . $cart_id);

			try {
				$db->setQuery($query);
				$products = $db->loadObjectList();
			} catch (Exception $e) {
				Log::add('Error getting hikashop cart products : ' . $e->getMessage(), JLog::ERROR, 'com_emundus.payment');
			}
		}

		return $products;
	}

	public function getFnumByOrder($order_id)
	{
		$fnum = '';

		if (!empty($order_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('fnum')
				->from('#__emundus_hikashop')
				->where('order_id = ' . $order_id);

			try {
				$db->setQuery($query);
				$fnum = $db->loadResult();
			} catch (Exception $e) {
				Log::add('Error getting fnum by order ID ' . $order_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.payment');
			}

			if (empty($fnum)) {
				$query->clear()
					->select('fnum')
					->from('#__emundus_hikashop')
					->where('order_id = (SELECT order_parent_id FROM #__hikashop_order WHERE order_id = ' . $order_id . ')');

				try {
					$db->setQuery($query);
					$fnum = $db->loadResult();
				} catch (Exception $e) {
					Log::add('Error getting fnum by order parent ID ' . $order_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.payment');
				}
			}
		}

		return $fnum;
	}
}
