<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.1
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class plgHikashoppaymentPaypalcheckout extends hikashopPaymentPlugin
{

	var $bncode = 'HIKARISOFTWARE_Cart_PPCP';
	var $liveMerchantId = 'SXL9FKNKGAEM8';
	var $livePartnerClientId = 'ATMLcPXNkey-vCSk3rNDmQ84adkXQKf1bWNXK01UOT2gW8XbgMRMvLXnPjqoU6KjzDcuaGQ1QydJ6Yhn';
	var $sandboxMerchantId = 'NZFJZCZ2WRXPN';
	var $sandboxPartnerClientId = 'AcTqgpujxMEGpk8rKeEu9LOG6EgskJyK-AtCMdQBtZjN4zt51HLetLv3Y9plTUGrFCont2uzaZVi4aqe';

	var $accepted_currencies = array(
		'AUD','BRL','CAD','EUR','GBP','JPY','USD','NZD','CHF','HKD','SGD','SEK',
		'DKK','PLN','NOK','HUF','CZK','MXN','MYR','PHP','TWD','THB','ILS','TRY',
	);

	var $rounding = array('TWD' => 0, 'MYR' => 0, 'JPY' => 0, 'HUF' => 0);

	var $multiple = true;
	var $name = 'paypalcheckout';
	var $doc_form = 'paypalcheckout';
	var $extraParams = '';

	var $pluginConfig = array(
		'paypal_account_connection' => array('PAYPAL_ACCOUNT_CONNECTION', 'title'),
		'sandbox' => array('HIKA_SANDBOX', 'boolean', 0),
		'connect' => array('PAYPAL_CHECKOUT_CONNECT', 'html'),
		'client_id' => array('PAYPAL_CHECKOUT_CLIENT_ID', 'input'),
		'client_secret' => array('PAYPAL_CHECKOUT_CLIENT_SECRET', 'input'),
		'merchant_id' => array('PAYPAL_CHECKOUT_MERCHANT_ID', 'input'),

		'accept_credit_card_payments' => array('ACCEPT_CREDIT_CARD_PAYMENTS', 'title'),
		'enable_credit_card' => array('ENABLE_CREDIT_CARDS', 'boolean', 1),
		'credit_card_limits' => array('', 'content'),
		'enable_3dsecure' => array('ENABLE_3DSECURE', 'list', array(
				'SCA_WHEN_REQUIRED' =>'SCA_WHEN_REQUIRED',
				'SCA_ALWAYS' => 'SCA_ALWAYS',
				'NO' => 'HIKASHOP_NO',
			),
		),
		'info_3dsecure' => array('', 'content'),
		'payments_with_paypal_buttons_options' => array('PAYMENTS_WITH_PAYPAL_BUTTONS_OPTIONS', 'title'),
		'brand_name' => array('PAYPAL_CHECKOUT_MERCHANT_NAME', 'input'),
		'capture' => array('INSTANTCAPTURE', 'boolean','1'),
		'landing_page' => array('PAYPAL_CHECKOUT_LANDING_PAGE', 'list', array(
				'LOGIN' =>'PAYPAL_CHECKOUT_LOGIN_PAGE',
				'BILLING' => 'PAYPAL_CHECKOUT_CREDIT_CARD_PAGE',
				'NO_PREFERENCE' => 'PAYPAL_CHECKOUT_NO_PREFERENCE',
			),
		),
		'disable_funding' => array(
			'PAYPAL_CHECKOUT_DISABLE_FUNDING',
			'checkbox',
			array(
				'card' =>'Credit or debit cards',
				'credit' => 'PayPal Credit (US, UK)',
				'paylater' => 'Pay Later (US, UK), Pay in 4 (AU), 4X PayPal (France), Paga en 3 plazos (Spain), Paga in 3 rate (Italy), Später Bezahlen (Germany)',
				'venmo' => 'Venmo',
				'bancontact' => 'Bancontact',
				'blik' => 'BLIK',
				'eps' => 'eps',
				'giropay' => 'giropay',
				'ideal' => 'iDEAL',
				'mercadopago' => 'Mercado Pago',
				'mybank' => 'MyBank',
				'p24' => 'Przelewy24',
				'sepa' => 'SEPA-Lastschrift',
				'multibanco' => 'Multibanco',
				'trustly' => 'Trustly',
			),
			'tooltip' => 'PAYPAL_CHECKOUT_DISABLE_FUNDING_TOOLTIP',
		),
		'funding' => array(
			'PAYPAL_CHECKOUT_ENABLE_FUNDING',
			'checkbox',
			array(
				'card' =>'Credit or debit cards',
				'credit' => 'PayPal Credit (US, UK)',
				'paylater' => 'Pay Later (US, UK), Pay in 4 (AU), 4X PayPal (France), Paga en 3 plazos (Spain), Paga in 3 rate (Italy), Später Bezahlen (Germany)',
				'venmo' => 'Venmo',
				'bancontact' => 'Bancontact',
				'blik' => 'BLIK',
				'eps' => 'eps',
				'giropay' => 'giropay',
				'ideal' => 'iDEAL',
				'mercadopago' => 'Mercado Pago',
				'mybank' => 'MyBank',
				'p24' => 'Przelewy24',
				'sepa' => 'SEPA-Lastschrift',
				'multibanco' => 'Multibanco',
				'trustly' => 'Trustly',
			),
			'tooltip' => 'PAYPAL_CHECKOUT_ENABLE_FUNDING_TOOLTIP',
		),
		'layout' => array('PAYPAL_CHECKOUT_BUTTON_LAYOUT', 'list', array(
				'vertical' => 'VERTICAL',
				'horizontal' => 'HORIZONTAL',
			),
		),
		'color' => array('PAYPAL_CHECKOUT_BUTTON_COLOR', 'list', array(
				'gold' => 'PAYPAL_CHECKOUT_GOLD',
				'blue' => 'PAYPAL_CHECKOUT_BLUE',
				'silver' => 'PAYPAL_CHECKOUT_SILVER',
				'white' => 'PAYPAL_CHECKOUT_WHITE',
				'black' => 'PAYPAL_CHECKOUT_BLACK',
			),
		),
		'shape' => array('PAYPAL_CHECKOUT_BUTTON_SHAPE', 'list', array(
				'rect' => 'PAYPAL_CHECKOUT_RECTANGLE',
				'pill' => 'PAYPAL_CHECKOUT_PILL',
			),
		),
		'label' => array('PAYPAL_CHECKOUT_BUTTON_LABEL', 'list', array(
				'paypal' => 'PayPal',
				'checkout' => 'PayPal Checkout',
				'buynow' => 'PayPal Buy Now',
				'pay' => 'Pay with PayPal',
			),
		),
		'tagline' => array('PAYPAL_CHECKOUT_BUTTON_TAGLINE', 'boolean', 1),
		'listing_position' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_ON_PRODUCT_LISTINGS', 'list', array(
				'' => 'HIKASHOP_NO',
				'top' => 'HIKA_TOP',
				'middle' => 'HIKA_MIDDLE',
				'bottom' => 'HIKA_BOTTOM',
			),
		),
		'product_page_position' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_ON_PRODUCT_PAGE', 'list', array(
				'' => 'HIKASHOP_NO',
				'topBegin' => 'TOP_BEGIN',
				'topEnd' => 'TOP_END',
				'leftBegin' => 'LEFT_BEGIN',
				'leftEnd' => 'LEFT_END',
				'rightBegin' => 'RIGHT_BEGIN',
				'rightMiddle' => 'RIGHT_MIDDLE',
				'rightEnd' => 'RIGHT_END',
				'bottomBegin' => 'BOTTOM_BEGIN',
				'bottomMiddle' => 'BOTTOM_MIDDLE',
				'bottomEnd' => 'BOTTOM_END',
			),
		),
		'cart_page_position' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_ON_CART_PAGE', 'list', array(
				'' => 'HIKASHOP_NO',
				'bottom' => 'HIKA_BOTTOM',
			),
		),
		'checkout_page_position' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_ON_CHECKOUT_CART', 'list', array(
				'' => 'HIKASHOP_NO',
				'top' => 'HIKA_TOP',
				'bottom' => 'HIKA_BOTTOM',
			),
		),
		'paylater_messaging_color' => array('PAYPAL_CHECKOUT_PAY_LATER_MESSAGING_COLOR', 'list', array(
				'black' => 'PAYPAL_CHECKOUT_BLACK',
				'white' => 'PAYPAL_CHECKOUT_WHITE',
				'monochrome' => 'PAYPAL_CHECKOUT_MONOCHROME',
				'grayscale' => 'PAYPAL_CHECKOUT_GRAYSCALE',
			),
		),
		'standard_options' => array('STANDARD_OPTIONS', 'title'),
		'details' => array('SEND_DETAILS_OF_ORDER', 'boolean', 1),
		'debug' => array('DEBUG', 'boolean', 0),
		'cancel_url' => array('CANCEL_URL', 'input'),
		'return_url' => array('RETURN_URL', 'input'),
		'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
		'pending_status' => array('PENDING_STATUS', 'orderstatus'),
		'verified_status' => array('VERIFIED_STATUS', 'orderstatus'),
	);

	function __construct(&$subject, $config) {
		return parent::__construct($subject, $config);
	}

	public function onHikashopBeforeDisplayView(&$view) {
		$app = JFactory::getApplication();
		if(version_compare(JVERSION,'4.0','<'))
			$admin = $app->isAdmin();
		else
			$admin = $app->isClient('administrator');
		if($admin)
			return;
		$viewName = $view->getName();
		$layout = $view->getLayout();

		if($viewName == 'product' && $layout == 'listing' && hikaInput::get()->getVar('hikashop_front_end_main', 0) && hikaInput::get()->getVar('task') == 'listing') {
			$this->processListing($view);
		}
		if($viewName == 'product' && $layout == 'show') {
			$this->processDetailsPage($view);
		}
	}

	public function onHikashopAfterDisplayView(&$view) {
		$app = JFactory::getApplication();
		if(version_compare(JVERSION,'4.0','<'))
			$admin = $app->isAdmin();
		else
			$admin = $app->isClient('administrator');
		if($admin)
			return;
		$viewName = $view->getName();
		$layout = $view->getLayout();

		if($viewName == 'cart' && $layout == 'show') {
			$this->processCart($view);
		}
	}
	public function onBeforeCheckoutViewDisplay($layout, &$view) {
		if($layout != 'cart')
			return;

		$method = $this->getPaymentMethod();
		if(!$method) {
			return;
		}
		$position = $this->getPosition($method, 'listing');
		if(empty($position))
			return;

		$data = $this->getMessagingHTML(0, 'payment', $method);

		if(!isset($view->extraData))
			$view->extraData = array();

		if(!isset($view->extraData[$view->module_position]))
			$view->extraData[$view->module_position] = new stdClass();

		if(!isset($view->extraData[$view->module_position]->$position))
			$view->extraData[$view->module_position]->$position = array();
		array_push($view->extraData[$view->module_position]->$position, $data);
	}

	private function processListing(&$view) {

		$method = $this->getPaymentMethod();
		if(!$method) {
			return;
		}
		$position = $this->getPosition($method, 'listing');
		if(empty($position))
			return;

		$data = $this->getMessagingHTML(0, 'category', $method);

		if(!isset($view->element->extraData))
			$view->element->extraData = new stdClass();

		if(!isset($view->element->extraData->$position))
			$view->element->extraData->$position = array();
		array_push($view->element->extraData->$position, $data);
	}

	private function processCart(&$view) {
		if(empty($view->cart->total->prices)) {
			return;
		}

		$method = $this->getPaymentMethod();
		if(!$method) {
			return;
		}
		$position = $this->getPosition($method, 'cart');
		if(empty($position))
			return;

		$price_value = 'price_value';
		if($view->config->get('price_with_tax')) {
			$price_value = 'price_value_with_tax';
		}
		$mainPrice = reset($view->cart->total->prices);

		if(empty($mainPrice->$price_value) && $mainPrice->$price_value > 0) {
			return;
		}

		$data = $this->getMessagingHTML($amount, 'cart', $method);

		echo $data;
	}

	private function processDetailsPage(&$view) {
		if(empty($view->element->prices)) {
			return;
		}
		$method = $this->getPaymentMethod();
		if(!$method) {
			return;
		}
		$position = $this->getPosition($method, 'product_page');
		if(empty($position))
			return;

		$price_value = 'price_value';
		if($view->params->get('price_with_tax')) {
			$price_value = 'price_value_with_tax';
		}
		$mainPrice = reset($view->element->prices);

		if(empty($mainPrice->$price_value) && $mainPrice->$price_value > 0) {
			return;
		}

		$data = $this->getMessagingHTML($mainPrice->$price_value, 'product', $method);

		if(!isset($view->element->extraData))
			$view->element->extraData = new stdClass();

		if(!isset($view->element->extraData->$position))
			$view->element->extraData->$position = array();
		array_push($view->element->extraData->$position, $data);
	}

	private function getMessagingHTML($amount, $type, &$method) {
		static $currency = null;
		if($currency == null) {
			$currency_id = hikashop_getCurrency();
			$currencyClass = hikashop_get('class.currency');
			$currencyObj = $currencyClass->get($currency_id);
			$currency = $currencyObj->currency_code;
		}

		$attribs = '';
		if(!empty($method->payment_params->paylater_messaging_color)) {
			$attribs.=' data-pp-style-text-color="'.$method->payment_params->paylater_messaging_color.'"';
		}
		$url = 'https://www.paypal.com';
		$amount = number_format(round((float)$amount,2), 2, '.', '');
		return '<div data-pp-message
		data-pp-placement="'.$type.'" 
		data-pp-amount="'.$amount.'" 
		data-pp-currency="'.$currency.'" 
		'.$attribs.'
		></div>
		<script src="'.$url.'/sdk/js?client-id='.$method->payment_params->client_id.'&components=messages" data-partner-attribution-id="'.$this->bncode.'"></script>
';
	}

	private function getPosition(&$method, $type) {
		if(!empty($method->payment_params->funding)) {
			if(is_string($method->payment_params->funding)) {
				$fundings = explode(',', $method->payment_params->funding);
			} else {
				$fundings = $method->payment_params->funding;
			}
			if(!in_array('paylater', $fundings))
				return '';
		}
		$var = $type.'_position';
		if(!empty($method->payment_params->$var)) {
			return $method->payment_params->$var;
		}
		return '';
	}

	private function getPaymentMethod() {
		static $method = null;
		if(is_null($method)) {
			$class = hikashop_get('class.cart');
			$cart = $class->getFullCart();
			if(!empty($cart->usable_methods->payment)) {
				foreach($cart->usable_methods->payment as $payment) {
					if($payment->payment_type == $this->name) {
						$method = $payment;
						break;
					}
				}
			}
			if(is_null($method))
				$method = $this->loadFirstPaymentMethodFound();
		}

		if(empty($method->payment_params->client_id))
			$method = false;

		return $method;
	}

	private function loadFirstPaymentMethodFound() {
		static $result = null;
		if(!is_null($result))
			return $result;

		$db = JFactory::getDBO();
		$where = array('payment_type = '.$db->Quote($this->name),'payment_published=\'1\'');
		$currency = hikashop_getCurrency();
		if(!empty($currency)){
			$where[] = "(payment_currency IN ('','_','all') OR payment_currency LIKE '%,".intval($currency).",%')";
		}
		hikashop_addACLFilters($where,'payment_access');
		$db->setQuery('SELECT * FROM `#__hikashop_payment` WHERE '.implode(' AND ',$where).' ORDER BY payment_ordering ASC');
		$result = $db->loadObject();

		if(empty($result))
			$result = false;

		if(!empty($result->payment_params)) {
			$result->payment_params = hikashop_unserialize($result->payment_params);
		}
		if(!empty($result->payment_name))
			$result->payment_name = hikashop_translate($result->payment_name);
		if(!empty($result->payment_description))
			$result->payment_description = hikashop_translate($result->payment_description);
		return $result;
	}

	public function onBeforeOrderCreate(&$order,&$do){
		if(parent::onBeforeOrderCreate($order, $do) === true)
			return true;

		if((empty($this->payment_params->client_id) || empty($this->payment_params->client_secret)) && $this->plugin_data->payment_id == $order->order_payment_id) {
			$this->app->enqueueMessage('Please check your &quot;PayPal Checkout&quot; payment method configuration');
			$do = false;
		}
	}

	public function onAfterOrderUpdate(&$order,&$do){
		if(empty($order->order_status))
			return;

		$payment_method = $order->old->order_payment_method;
		if(!empty($order->order_payment_method)) {
			$payment_method = $order->order_payment_method;
		}
		if($payment_method != $this->name)
			return true;

		$order_type = $order->old->order_type;
		if(!empty($order->order_type)) {
			$order_type = $order->order_type;
		}

		if($order_type != 'sale')
			return true;

		$order_payment_id = $order->old->order_payment_id;
		if(!empty($order->order_payment_id)) {
			$order_payment_id = $order->order_payment_id;
		}

		$this->payment_params = null;
		if(!empty($order_payment_id) && $this->pluginParams($order_payment_id))
			$this->payment_params =& $this->plugin_params;

		if(empty($this->payment_params)) {
			return;
		}
		if(!empty($this->payment_params->capture)) {
			return;
		}

		if($order->order_status == $this->payment_params->verified_status && $order->old->order_status == $this->payment_params->pending_status) {
			$this->processCaptureorder($order);
		}
	}


	public function onAfterOrderConfirm(&$order, &$methods, $method_id) {
		parent::onAfterOrderConfirm($order, $methods, $method_id);

		$this->loadJS();

		$this->notify_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&order_id='.$order->order_id.'&tmpl=component&lang='.$this->locale . $this->url_itemid;
		$this->cancel_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id='.$order->order_id . $this->url_itemid;
		$this->orderData = $this->getOrderData($order);

		$this->currencyClass = hikashop_get('class.currency');
		$this->total_amount = $this->currencyClass->format($order->order_full_price, $order->order_currency_id);

		if(!empty($this->payment_params->debug)) {
			hikashop_writeToLog($this->orderData);
			hikashop_writeToLog($this->params);
		}

		return $this->showPage('end');

	}

	public function onPaymentConfiguration(&$element) {
		parent::onPaymentConfiguration($element);

		if(empty($element->payment_params->client_id) || empty($element->payment_params->client_secret)) {
			if(empty($element->payment_params->sandbox)) {
				$element->payment_params->sandbox = 0;
			}			
			$this->pluginConfig['connect'][2] = $this->getConnectButtonHTML($element->payment_params->sandbox);
		} else {
			$this->pluginConfig['connect'][2] = $this->checkMerchantStatus($element);
			$this->pluginConfig['connect'][2] .= $this->getChangeLinkedAccountHTML();
		}
		$config = hikashop_config();
		$round_calculations = $config->get('round_calculations');
		if(!isset($element->payment_params->details)) {
			$element->payment_params->details = 1;
		}
		if(empty($round_calculations) && !empty($element->payment_params->details)){
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('PAYPAL_CHECKOUT_ROUND_PRICES_WARNING'));
		}

		if(empty($element->payment_params->client_id)) {
			$app = JFactory::getApplication();
			$lang = JFactory::getLanguage();
			$locale = strtolower(substr($lang->get('tag'), 0, 2));
			$app->enqueueMessage(JText::sprintf('ENTER_INFO_REGISTER_IF_NEEDED', 'PayPal Checkout', JText::_('PAYPAL_CHECKOUT_CLIENT_ID'), 'PayPal', 'https://www.paypal.com/' . $locale . '/webapps/mpp/account-selection'));
		}
		if(empty($element->payment_params->client_secret)) {
			$app = JFactory::getApplication();
			$lang = JFactory::getLanguage();
			$locale = strtolower(substr($lang->get('tag'), 0, 2));
			$app->enqueueMessage(JText::sprintf('ENTER_INFO_REGISTER_IF_NEEDED', 'PayPal Checkout', JText::_('PAYPAL_CHECKOUT_CLIENT_SECRET'), 'PayPal', 'https://www.paypal.com/' . $locale . '/webapps/mpp/account-selection'));
		}

	}

	public function checkMerchantStatus(&$element) {
		if(empty($element->payment_params->merchant_id)) {
			return '';
		}
		$url = 'https://api-m.paypal.com';
		$merchantId = $this->liveMerchantId;

		if(empty($element->payment_params->sandbox)) {
			$element->payment_params->sandbox = 0;
		}
		if(!empty($element->payment_params->sandbox)) {
			$url = 'https://api-m.sandbox.paypal.com';
			$merchantId = $this->sandboxMerchantId;
		}

		$curl = curl_init();
		$post = "grant_type=client_credentials";
    	curl_setopt_array($curl, array(
			CURLOPT_URL => $url."/v1/oauth2/token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => $element->payment_params->client_id.":".$element->payment_params->client_secret,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $post,
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/x-www-form-urlencoded",
				"PayPal-Partner-Attribution-Id: ".$this->bncode,
    		),
			CURLOPT_CAINFO => __DIR__.'/cacert.pem',
			CURLOPT_CAPATH => __DIR__.'/cacert.pem',
			CURLINFO_HEADER_OUT => true,
		));
		$curl_result = curl_exec($curl);
		if(empty($curl_result)) {
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($post);
			hikashop_writeToLog($curl_result);
			return hikashop_display('Returned data from Access Token Request not valid','error',true);
		}
		$array = json_decode($curl_result, true);
		if(empty($array)) {
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($post);
			hikashop_writeToLog($curl_result);
			return hikashop_display('Returned data from Access Token Request not valid','error',true);
		}
		if(empty($array['access_token'])) {
			hikashop_writeToLog($array);
			return hikashop_display($array['error_description'],'error',true);
		}
		$token = $array['access_token'];

		$curl = curl_init();
    	curl_setopt_array($curl, array(
			CURLOPT_URL => $url."/v1/customer/partners/".$merchantId."/merchant-integrations/".$element->payment_params->merchant_id,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json",
				"Authorization: Bearer ".$token,
				"PayPal-Partner-Attribution-Id: ".$this->bncode,
    		),
			CURLOPT_CAINFO => __DIR__.'/cacert.pem',
			CURLOPT_CAPATH => __DIR__.'/cacert.pem',
			CURLINFO_HEADER_OUT => true,
		));
		$curl_result = curl_exec($curl);
		$array = json_decode($curl_result, true);

		$prod_ok_for_ACDC = false;
		$cap_ok_for_ACDC = false;
		$limit = false;
		$withdraw_ok = false;
		$send_ok = false;
		$in_review = false;
		$need_more_data = false;
		$denied = false;
		if(!empty($array['products'])) {
			foreach($array['products'] as $product) {
				if($product['name'] == 'PPCP_CUSTOM' && $product['vetting_status'] == 'SUBSCRIBED') {
					$prod_ok_for_ACDC = true;
				}
				if($product['name'] == 'PPCP_CUSTOM' && $product['vetting_status'] == 'NEED_MORE_DATA') {
					$need_more_data = true;
				}
				if($product['name'] == 'PPCP_CUSTOM' && $product['vetting_status'] == 'IN_REVIEW') {
					$in_review = true;
				}
				if($product['name'] == 'PPCP_CUSTOM' && $product['vetting_status'] == 'DENIED') {
					$denied = true;
				}
			}
		}
		if(!empty($array['capabilities'])) {
			foreach($array['capabilities'] as $capability) {
				if($capability['name'] == 'CUSTOM_CARD_PROCESSING' && $capability['status'] == 'ACTIVE') {
					$cap_ok_for_ACDC = true;
					if(!empty($capability['limits']) && !empty($capability['limits'][0]['type']) && $capability['limits'][0]['type'] == 'GENERAL') {
						$limit = true;
					}
				}
				if($capability['name'] == 'WITHDRAW_MONEY' && !empty($capability['limits'])) {
					$withdraw_ok = true;
				}
				if($capability['name'] == 'SEND_MONEY' && !empty($capability['limits'])) {
					$send_ok = true;
				}
			}
		}
		if(!$prod_ok_for_ACDC || !$cap_ok_for_ACDC) {
			$this->pluginConfig['enable_credit_card'] = array('', 'content');
			$this->pluginConfig['enable_3dsecure'] = array('', 'content');
		} else {
			if(empty($element->payment_params->enable_credit_card)) {
				$element->payment_params->enable_3dsecure = 'NO';
				$this->pluginConfig['enable_3dsecure'] = array('<input type="hidden" name="data[payment][payment_params][enable_3dsecure]" value="NO" />', 'content');
			}
			if(!empty($element->payment_params->enable_3dsecure)) {
				$this->pluginConfig['info_3dsecure'] = array(JText::_('INFO_3DSSECURE'), 'content');

			}
			if($limit && !$withdraw_ok && !$send_ok) {
				$this->pluginConfig['credit_card_limits'][0] .= JText::_('MORE_INFO_FOR_ACDC_WITH_LIMIT');
			}
			if($limit && $withdraw_ok && $send_ok) {
				$this->pluginConfig['credit_card_limits'][0] .= JText::_('MORE_INFO_FOR_ACDC_OVER_LIMIT');
			}
		}
		if($need_more_data) {
			$this->pluginConfig['credit_card_limits'][0] .= JText::_('MORE_INFO_FOR_ACDC_OVER_LIMIT');
		}
		if($in_review) {
			$this->pluginConfig['credit_card_limits'][0] .= JText::_('IN_REVIEW_FOR_ACDC');
		}
		if($denied) {
			$this->pluginConfig['credit_card_limits'][0] .= JText::_('DENIED_FOR_ACDC');
		}

		if(empty($array['primary_email_confirmed'])) {
			$msg = JText::_('PAYPAL_CHECKOUT_PRIMARY_EMAIL_NOT_CONFIRMED');
			if(!empty($array['message'])) {
				$msg = $array['message'] .'<br/>'. $msg;
			}
			return hikashop_display($msg,'error',true);
		}

		if(empty($array['payments_receivable'])) {
			$msg = JText::_('PAYPAL_CHECKOUT_PAYMENTS_NOT_RECEIVABLE');
			if(!empty($array['message'])) {
				$msg = $array['message'] .'<br/>'. $msg;
			}
			return hikashop_display($msg,'error',true);
		}
		return hikashop_display(JText::_('PAYPAL_CHECKOUT_SUCCESSFULLY_CONNECTED'),'success',true);
	}

	private function getChangeLinkedAccountHTML() {
		return '
		<script>
			window.hikashop.changeLinkedAccount = function() {
				var clientId = document.getElementById(\'data_payment_payment_params_client_id\');
				var clientSecret = document.getElementById(\'data_payment_payment_params_client_secret\');
				var merchantId = document.getElementById(\'data_payment_payment_params_merchant_id\');
				clientId.value = \'\';
				clientSecret.value = \'\';
				merchantId.value = \'\';

				Joomla.submitbutton(\'apply\');
			}
		</script>
		<a href="#" onclick="window.hikashop.changeLinkedAccount();return false;" class="hikabtn hikabtn-primary">'.JText::_('PAYPAL_CHECKOUT_CHANGE_LINKED_ACCOUNT').'</a>
		';
	}

	public function getConnectButtonHTML($sandbox = false, $callback = '') {
		hikashop_loadJslib('notify');
		$nonce = substr(hash('sha512',mt_rand()),17,70);
		$AJAX_URL = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&onboarding=1&nonce='.urlencode($nonce).'&tmpl=component';
		$partnerClientId = $this->livePartnerClientId;
		$merchantId = $this->liveMerchantId;
		$url = 'https://www.paypal.com';
		if(empty($sandbox)) {
			$sandbox = 0;
		}
		if(!empty($sandbox)) {
			$partnerClientId = $this->sandboxPartnerClientId;
			$merchantId = $this->sandboxMerchantId;
			$url = 'https://www.sandbox.paypal.com';
		}

		$closeURL = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&closepopup=1&tmpl=component';
		$params = array(
			'partnerId' => $merchantId,
			'product' => 'ppcp',
			'integrationType' => 'FO',
			'features' => 'PAYMENT',
			'partnerClientId' => $partnerClientId,
			'partnerLogoUrl' => 'https://www.hikashop.com/images/branding/hikashop_logo1.png',
			'displayMode' => 'minibrowser',
			'sellerNonce' => $nonce,
		);
		if(empty($callback)) {
			$js = 'Joomla.submitbutton(\'apply\');';
			$selector = '#data_payment_payment_params_sandbox input';
		} else {
			$js = $callback.'();';
			$selector = '#sanbox input';
		}

		$doc = JFactory::getDocument();
		$doc->addScript('https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js');
		$html = '
		<a
			id="paypal_connect"
			class="direct"
			target="_blank"
			data-paypal-onboard-complete="onboardedCallback"
			href="'.$url.'/bizsignup/partner/entry?'.http_build_query($params).'"
			data-paypal-button="PPLtBlue"
			>
			'.JText::_('PAYPAL_CHECKOUT_CONNECT_TO_PAYPAL_CHECKOUT').'
		</a>
		<script>
			function onboardedCallback(authCode, sharedId) {
				var callbackURL = \''.$AJAX_URL.'\';
				if(window.hikashop.isPayPalSandbox==1) {
					callbackURL += \'&sandbox=1\';
				}
				callbackURL += \'&authCode=\' + authCode + \'&sharedId=\' + sharedId;
				window.Oby.xRequest(callbackURL,  {mode:\'POST\'}, function(xhr){
					var resp = window.Oby.evalJSON(xhr.responseText);

					console.log(callbackURL);
					console.log(resp);

					if(resp && resp.error) {
						jQuery(document.getElementById(\'paypal_connect\')).notify({title:resp.errorTitle, text:resp.errorMessage, image:\'<i class=\"fas fa-3x fa-file-invoice\"></i>\', globalPosition:\'top right\'},{style:"metro",className:\'error\', autoHide: false, arrowShow:true});
					} else {
						var clientId = document.getElementById(\'data_payment_payment_params_client_id\');
						if(!clientId)
							clientId =  document.querySelector(\'input[name="data[payment][payment_params][client_id]"]\');
						var clientSecret = document.getElementById(\'data_payment_payment_params_client_secret\');
						if(!clientSecret)
							clientSecret =  document.querySelector(\'input[name="data[payment][payment_params][client_secret]"]\');
						var merchantId = document.getElementById(\'data_payment_payment_params_merchant_id\');
						if(!merchantId)
							merchantId =  document.querySelector(\'input[name="data[payment][payment_params][merchant_id]"]\');
						clientId.value = resp.clientId;
						clientSecret.value = resp.clientSecret;
						merchantId.value = resp.merchantId;

						'.$js.'
					}
				});
			}
			window.hikashop.changeSandbox = function(sandbox) {
				window.hikashop.isPayPalSandbox = sandbox;

				var src = \'https://www.paypal\';
				var dst = \'https://www.paypal\';
				if(sandbox==1) {
					dst = \'https://www.sandbox.paypal\';
				} else {
					src = \'https://www.sandbox.paypal\';
				}
				document.getElementById(\'paypal_connect\').href = document.getElementById(\'paypal_connect\').href.replace(src, dst);

				if(sandbox==1) {
					src = \''.$this->livePartnerClientId.'\';
					dst = \''.$this->sandboxPartnerClientId.'\';
				} else {
					src = \''.$this->sandboxPartnerClientId.'\';
					dst = \''.$this->livePartnerClientId.'\';
				}
				document.getElementById(\'paypal_connect\').href = document.getElementById(\'paypal_connect\').href.replace(src, dst);

				if(sandbox==1) {
					src = \''.$this->liveMerchantId.'\';
					dst = \''.$this->sandboxMerchantId.'\';
				} else {
					src = \''.$this->sandboxMerchantId.'\';
					dst = \''.$this->liveMerchantId.'\';
				}
				document.getElementById(\'paypal_connect\').href = document.getElementById(\'paypal_connect\').href.replace(src, dst);


			}
			window.hikashop.setSandboxFlag = function() {
				document.querySelectorAll(\''.$selector.'\').forEach((elem) => {
					if(elem.checked) {
						window.hikashop.changeSandbox(elem.value);
					}
				});
			}

			window.hikashop.ready( function() {
				window.hikashop.isPayPalSandbox = '.(int)empty($sandbox).';
				window.hikashop.setSandboxFlag();
				document.querySelectorAll(\''.$selector.'\').forEach((elem) => {
					elem.addEventListener("click", function(event) {
						window.hikashop.changeSandbox(event.target.value);
					});
				});
				(function(d, s, id) {
					var js, ref = d.getElementsByTagName(s)[0];
					if (!d.getElementById(id)) {
						js = d.createElement(s);
						js.id = id;
						js.async = true;
						js.src = "https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js";
						ref.parentNode.insertBefore(js, ref);
					}
				}(document, "script", "paypal-js"));
			});
		</script>
		';
		return $html;
	}

	private function processCreateorder() {
		$result = new stdClass();
		$result->errorTitle = JText::_('PAYPAL_CHECKOUT_ERROR_OCCURRED');
		$result->errorMessage = '';
		$result->error = false;

		$order_id = hikaInput::get()->getInt('order_id');
		if(empty($order_id)) {
			$result->error = true;
			$result->errorMessage = 'Order id is missing';
			return $result;
		}

		$dbOrder = $this->getOrder($order_id);
		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params)) {
			$result->error = true;
			$result->errorMessage = 'Params could not be loaded for payment method';
			return $result;
		}

		$orderClass = hikashop_get('class.order');
		$order = $orderClass->loadFullOrder($order_id);
		if(empty($order)) {
			$result->error = true;
			$result->errorMessage = 'Order could not be loaded';
			return $result;
		}
		$this->loadOrderData($order);

		$userClass = hikashop_get('class.user');
		$order->customer = $userClass->get($order->order_user_id);
		$order->cart =& $order;

		$price = new stdClass();
		$price->price_value_with_tax = $order->order_full_price;
		$price->price_value = $order->order_full_price-$order->order_tax_price;
		$order->cart->full_total = new stdClass();
		$order->cart->full_total->prices = array($price);
		$order->cart->total = new stdClass();

		$config = hikashop_config();
		if($config->get('group_options',0)){
			foreach($order->cart->products as $k => $product){
				if(!empty($product->order_product_option_parent_id)){
					foreach($order->cart->products as $k2 => $product2){
						if($product->order_product_option_parent_id == $product2->order_product_id){
							$product2->order_product_price += $product->order_product_price;
							$product2->order_product_tax += $product->order_product_tax;
							$product2->order_product_total_price_no_vat += $product->order_product_total_price_no_vat;
							$product2->order_product_total_price += $product->order_product_total_price;
						}
					}
				}
			}
		}

		$currencyClass = hikashop_get('class.currency');
		$currencyClass->calculateTotal($order->cart->products, $order->cart->total, $order->order_currency_id);
		if(bccomp(sprintf('%F',$order->order_discount_price), 0, 5) !== 0) {
			$order->cart->coupon = new stdClass();
			$order->cart->coupon->discount_value =& $order->order_discount_price;
		}

		try {
			require __DIR__ . '/vendor/autoload.php';
			if($this->payment_params->sandbox) {
				$env = new PayPalCheckoutSdk\Core\SandboxEnvironment($this->payment_params->client_id, $this->payment_params->client_secret);
			} else {
				$env = new PayPalCheckoutSdk\Core\ProductionEnvironment($this->payment_params->client_id, $this->payment_params->client_secret);
			}
			require_once __DIR__ . '/paypalcheckout_override.php';
			$client = new hkHttpClient($env);
			$request = new PayPalCheckoutSdk\Orders\OrdersCreateRequest();
			$request->payPalPartnerAttributionId($this->bncode);
			$request->prefer('return=representation');
			$request->body = json_encode($this->getOrderData($order), JSON_PRETTY_PRINT);
			if($this->payment_params->debug) {
				hikashop_writeToLog($request->body);
			}
			$response = $client->execute($request);
			if(empty($response->result->id)) {
				$result->error = true;
				hikashop_writeToLog('order creation failed.');
				if(!empty($response->result->status)) {
					$result->errorMessage = $response->result->status;
				} else {
					$result->errorMessage = 'Unkowned error when creating order';
				}
				hikashop_writeToLog($response);
				return $result;
			}
			$result->id = $response->result->id;

			if($this->payment_params->debug) {
				hikashop_writeToLog($response);
			}

			return $result;
		} catch(Exception $e) {
			$result->error = true;
			$result->errorMessage = $e->getMessage();
			hikashop_writeToLog('order creation failed. Catched');
			hikashop_writeToLog($result->errorMessage);
			return $result;
		}
	}
	private function processCaptureorder(&$order) {
		if(empty($order->old->order_payment_params->paypal_checkout_id)) {
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('PAYPAL_CHECKOUT_ERROR_ORDER_ID_MISSING'), 'error');

			if($this->payment_params->debug) {
				hikashop_writeToLog('PayPal checkout error order id missing for order '.$order->order_id);
			}
			return false;
		}

		if($this->payment_params->debug) {
			hikashop_writeToLog('capture order with paypal id  '.$order->old->order_payment_params->paypal_checkout_id);
		}

		try {
			require __DIR__ . '/vendor/autoload.php';
			if($this->payment_params->sandbox) {
				$env = new PayPalCheckoutSdk\Core\SandboxEnvironment($this->payment_params->client_id, $this->payment_params->client_secret);
			} else {
				$env = new PayPalCheckoutSdk\Core\ProductionEnvironment($this->payment_params->client_id, $this->payment_params->client_secret);
			}

			require_once __DIR__ . '/paypalcheckout_override.php';
			$client = new hkHttpClient($env);

			$request = new PayPalCheckoutSdk\Orders\OrdersGetRequest($order->old->order_payment_params->paypal_checkout_id);
			$request->headers['PayPal-Partner-Attribution-Id'] = $this->bncode;
			$response = $client->execute($request);
			if(empty($response->result->id)) {
				$app = JFactory::getApplication();
				if(!empty($response->result->status)) {
					$app->enqueueMessage($response->result->status, 'error');
				} else {
					$app->enqueueMessage('Unkowned error when capturing order', 'error');
				}
				hikashop_writeToLog($response);
				return false;
			}

			if($this->payment_params->debug) {
				hikashop_writeToLog($response);
			}
			$authorization_id = $response->result->purchase_units[0]->payments->authorizations[0]->id;
			if(!empty($response->result->purchase_units[0]->payments->authorizations[0]->expiration_time)) {
				$d = DateTime::createFromFormat('c', $response->result->purchase_units[0]->payments->authorizations[0]->expiration_time);
				if($d) {
					$date = $d->getTimestamp();
					if($date <= time()) {
						$request = new PayPalCheckoutSdk\Payments\AuthorizationsReauthorizeRequest($authorization_id);
						$request->headers['PayPal-Partner-Attribution-Id'] = $this->bncode;
						$response = $client->execute($request);
						if(empty($response->result->id)) {
							$app = JFactory::getApplication();
							if(!empty($response->result->status)) {
								$app->enqueueMessage($response->result->status, 'error');
							} else {
								$app->enqueueMessage('Unkowned error when capturing order', 'error');
							}
							hikashop_writeToLog($response);
							return false;
						} else {
							$authorization_id = $response->result->id;
						}
					} else {
					}
				} else {
					$app = JFactory::getApplication();
					$app->enqueueMessage('authorizaiont date format unknown', 'error');
					hikashop_writeToLog('authorizaiont date format unknown');
					hikashop_writeToLog($response);
				}
			} else {
			}

			$request = new PayPalCheckoutSdk\Payments\AuthorizationsCaptureRequest($authorization_id);
			$request->headers['PayPal-Partner-Attribution-Id'] = $this->bncode;

			$response = $client->execute($request);
			if(empty($response->result->id)) {
				$app = JFactory::getApplication();
				if(!empty($response->result->status)) {
					$app->enqueueMessage($response->result->status, 'error');
				} else {
					$app->enqueueMessage('Unkowned error when capturing order', 'error');
				}
				hikashop_writeToLog($response);
				return false;
			}
			if($this->payment_params->debug) {
				hikashop_writeToLog($response);
				hikashop_writeToLog('capture successful');
			}
			return true;
		} catch(Exception $e) {
			$errorMessage = $e->getMessage();
			$app = JFactory::getApplication();
			$app->enqueueMessage($errorMessage, 'error');
			hikashop_writeToLog($errorMessage);
			return false;
		}
	}

	private function processOnapprove() {
		$result = new stdClass();
		$result->errorTitle = JText::_('PAYPAL_CHECKOUT_ERROR_OCCURRED');
		$result->errorMessage = '';
		$result->errorCode = null;
		$result->error = false;

		$order_id = hikaInput::get()->getInt('order_id');
		if(empty($order_id)) {
			$result->error = true;
			$result->errorMessage = 'Order id is missing';
			return $result;
		}

		$paypal_id = hikaInput::get()->getString('orderID');
		if(empty($paypal_id)) {
			$result->error = true;
			$result->errorMessage = 'PayPal Order id is missing';
			return $result;
		}

		$dbOrder = $this->getOrder($order_id);
		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params)) {
			$result->error = true;
			$result->errorMessage = 'Params could not be loaded for payment method';
			return $result;
		}

		$orderClass = hikashop_get('class.order');
		$order = $orderClass->loadFullOrder($order_id);
		if(empty($order)) {
			$result->error = true;
			$result->errorMessage = 'Order could not be loaded';
			return $result;
		}

		$updateOrder = new stdClass();
		$updateOrder->order_id = $order_id;
		$updateOrder->order_payment_params = new stdClass();
		$updateOrder->order_payment_params->paypal_checkout_id = $paypal_id;
		$orderClass->save($updateOrder);

		if($this->payment_params->debug) {
			hikashop_writeToLog('on approve for '.$paypal_id);
		}

		try {
			require __DIR__ . '/vendor/autoload.php';
			if($this->payment_params->sandbox) {
				$env = new PayPalCheckoutSdk\Core\SandboxEnvironment($this->payment_params->client_id, $this->payment_params->client_secret);
			} else {
				$env = new PayPalCheckoutSdk\Core\ProductionEnvironment($this->payment_params->client_id, $this->payment_params->client_secret);
			}

			require_once __DIR__ . '/paypalcheckout_override.php';
			$client = new hkHttpClient($env);

			$action = 'proceed';
			if(!empty($this->payment_params->enable_3dsecure) && $this->payment_params->enable_3dsecure != 'NO') {
				$request = new PayPalCheckoutSdk\Orders\OrdersGetRequest($paypal_id);
				$request->headers['PayPal-Partner-Attribution-Id'] = $this->bncode;
				$response = $client->execute($request);
				if(empty($response->result->id)) {
					$result->error = true;
					if(!empty($response->result->status)) {
						$result->errorMessage = $response->result->status;
					} else {
						$result->errorMessage = 'Unkowned error when creating order';
					}
					hikashop_writeToLog($response);
					return $result;
				}

				if($this->payment_params->debug) {
					hikashop_writeToLog($response);
				}

				if(!empty($response->result->payment_source->card->authentication_result)) {
					$liability_shift = null;
					if(!empty($response->result->payment_source->card->authentication_result->liability_shift)) {
						$liability_shift = $response->result->payment_source->card->authentication_result->liability_shift;
					}
					$enrollment_status = null;
					if(!empty($response->result->payment_source->card->authentication_result->three_d_secure->enrollment_status)) {
						$enrollment_status = $response->result->payment_source->card->authentication_result->three_d_secure->enrollment_status;
					}
					$authentication_status = null;
					if(!empty($response->result->payment_source->card->authentication_result->three_d_secure->authentication_status)) {
						$authentication_status = $response->result->payment_source->card->authentication_result->three_d_secure->authentication_status;
					}

					$action = 'stop_and_retry';
					if($enrollment_status == 'Y') {
						if($authentication_status == 'Y' && in_array($liability_shift, array('POSSIBLE','YES'))) {
							$action = 'proceed';
						} elseif(in_array($authentication_status, array('N','R')) && $liability_shift == 'NO') {
							$action = 'stop_and_retry'; // maybe 'stop' instead ?
						} elseif($authentication_status == 'A' && $liability_shift == 'POSSIBLE') {
							$action = 'proceed';
						} elseif($authentication_status == 'N' && $liability_shift == 'NO') {
							$action = 'proceed';
						}
					} elseif(in_array($enrollment_status, array('N','U','B')) && $liability_shift == 'NO') {
						$action = 'proceed';
					}
					if(!empty($response->result->payment_source->card->authentication_result)) {
						$updateOrder->order_payment_params = new stdClass();
						$updateOrder->order_payment_params->paypal_checkout_id = $paypal_id;
						$updateOrder->order_payment_params->authentication_result = $response->result->payment_source->card->authentication_result;
						$updateOrder->order_payment_params->authentication_action = $action;
						$orderClass->save($updateOrder);
					}
				}
			}

			if($action != 'proceed') {
				$result->error = true;
				if($action == 'stop') {
					$result->errorMessage = JText::_('PAYPAL_CHECKOUT_3DSECURE_STOP');
				} elseif($action == 'stop_and_retry') {
					$result->errorMessage = JText::_('PAYPAL_CHECKOUT_3DSECURE_STOP_AND_RETRY');
				}
				$result->errorCode = $action;
				return $result;
			}


			if(!empty($this->payment_params->capture)) {
				$request = new PayPalCheckoutSdk\Orders\OrdersCaptureRequest($paypal_id);
			} else {
				$request = new PayPalCheckoutSdk\Orders\OrdersAuthorizeRequest($paypal_id);
			}
			$request->headers['PayPal-Partner-Attribution-Id'] = $this->bncode;

			$response = $client->execute($request);
			if(empty($response->result->id)) {
				$result->error = true;
				if(!empty($response->result->status)) {
					$result->errorMessage = $response->result->status;
				} else {
					$result->errorMessage = 'Unkowned error when creating order';
				}
				hikashop_writeToLog($response);
				return $result;
			}
			$result->id = $response->result->id;

			if($this->payment_params->debug) {
				hikashop_writeToLog($response);
			}

			return $result;
		} catch(Exception $e) {
			$result->error = true;
			$result->errorMessage = $e->getMessage();
			hikashop_writeToLog($result->errorMessage);
			return $result;
		}
	}

	private function processOnboarding() {
		$result = new stdClass();
		$result->errorTitle = JText::_('PAYPAL_CHECKOUT_ERROR_OCCURRED');
		$result->errorMessage = '';
		$result->error = false;

		$nonce = hikaInput::get()->getString('nonce');
		if(empty($nonce)) {
			$result->error = true;
			$result->errorMessage = 'Nonce is missing';
			return $result;
		}
		$authCode = hikaInput::get()->getString('authCode');
		if(empty($authCode)) {
			$result->error = true;
			$result->errorMessage = 'authCode is missing';
			return $result;
		}
		$sharedId = hikaInput::get()->getString('sharedId');
		if(empty($sharedId)) {
			$result->error = true;
			$result->errorMessage = 'sharedId is missing';
			return $result;
		}
		$sharedId = base64_encode($sharedId.':');

		$sandbox = hikaInput::get()->getInt('sandbox');
		$url = 'https://api-m.paypal.com';
		$merchantId = $this->liveMerchantId;
		if($sandbox) {
			$url = 'https://api-m.sandbox.paypal.com';
			$merchantId = $this->sandboxMerchantId;
		}

		$curl = curl_init();
		$post = "grant_type=authorization_code&code=".urlencode($authCode)."&code_verifier=".urlencode($nonce);
    	curl_setopt_array($curl, array(
			CURLOPT_URL => $url."/v1/oauth2/token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $post,
			CURLOPT_HTTPHEADER => array(
				"Content-Type: text/plain",
				"Authorization: Basic ".$sharedId,
				"PayPal-Partner-Attribution-Id: ".$this->bncode,
    		),
			CURLOPT_CAINFO => __DIR__.'/cacert.pem',
			CURLOPT_CAPATH => __DIR__.'/cacert.pem',
			CURLINFO_HEADER_OUT => true,
		));
		$curl_result = curl_exec($curl);
		$array = json_decode($curl_result, true);
		if(empty($array)) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Acquire Seller Access not valid';
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($post);
			hikashop_writeToLog($curl_result);
			return $result;
		}
		if(empty($array['access_token'])) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Acquire Seller Access does not contain access token';
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($post);
			hikashop_writeToLog($array);
			return $result;
		}
		$token = $array['access_token'];

		$curl = curl_init();
    	curl_setopt_array($curl, array(
			CURLOPT_URL => $url."/v1/customer/partners/".$merchantId."/merchant-integrations/credentials",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json",
				"Authorization: Bearer ".$token,
				"PayPal-Partner-Attribution-Id: ".$this->bncode,
    		),
			CURLOPT_CAINFO => __DIR__.'/cacert.pem',
			CURLOPT_CAPATH => __DIR__.'/cacert.pem',
			CURLINFO_HEADER_OUT => true,
		));
		$curl_result = curl_exec($curl);
		$array = json_decode($curl_result, true);
		if(empty($array)) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Get Seller Credentials not valid';
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($curl_result);
			return $result;
		}
		if(empty($array['client_id'])) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Get Seller Credentials does not contain client ID';
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($array);
			return $result;
		}
		if(empty($array['client_secret'])) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Get Seller Credentials does not contain client secret';
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($array);
			return $result;
		}
		if(empty($array['payer_id'])) {
			$result->error = true;
			$result->errorMessage = 'Returned data from Get Seller Credentials does not contain payer ID';
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($array);
			return $result;
		}

		$result->clientId = $array['client_id'];
		$result->clientSecret = $array['client_secret'];
		$result->merchantId = $array['payer_id'];

		return $result;
	}

	private function runProcess($name) {
		ob_start();
		$result = $this->$name();
		$output = ob_get_clean();
		if(!empty($ouput)) {
			hikashop_writeToLog($output);
		}
		echo json_encode($result);
	}

	public function onPaymentNotification(&$statuses) {
		$createorder = hikaInput::get()->getInt('createorder');
		if($createorder) {
			$this->runProcess('processCreateorder');
			exit;
		}
		$onapprove = hikaInput::get()->getInt('onapprove');
		if($onapprove) {
			$this->runProcess('processOnapprove');
			exit;
		}
		$onboarding = hikaInput::get()->getInt('onboarding');
		if($onboarding) {
			$this->runProcess('processOnboarding');
			exit;
		}
		$closepopup = hikaInput::get()->getInt('closepopup');
		if($closepopup) {
			echo "
			<script>
			window.close();
			</script>
			";
			exit;
		}

		$order_id = hikaInput::get()->getInt('order_id');
		$paypal_id = hikaInput::get()->getString('paypal_id');

		if(empty($paypal_id)) {
			hikashop_writeToLog('paypal_id missing !');
			hikashop_writeToLog($order_id);
			return false;
		}

		$dbOrder = $this->getOrder($order_id);
		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params))
			return false;

		$this->loadOrderData($dbOrder);

		if($this->payment_params->debug) {
			hikashop_writeToLog($dbOrder);
			hikashop_writeToLog($paypal_id);
		}

		try {

			require __DIR__ . '/vendor/autoload.php';

			if($this->payment_params->sandbox) {
				$env = new PayPalCheckoutSdk\Core\SandboxEnvironment($this->payment_params->client_id, $this->payment_params->client_secret);
			} else {
				$env = new PayPalCheckoutSdk\Core\ProductionEnvironment($this->payment_params->client_id, $this->payment_params->client_secret);
			}
			require_once __DIR__ . '/paypalcheckout_override.php';
			$client = new hkHttpClient($env);
			$request = new PayPalCheckoutSdk\Orders\OrdersGetRequest($paypal_id);
			$request->headers['PayPal-Partner-Attribution-Id'] = $this->bncode;
			$response = $client->execute($request);

			$ok = $this->checkResponse($response, $dbOrder);
		} catch(Exception $e) {
			hikashop_writeToLog($e->getMessage());
		}


		if(!empty($ok)) {
			$history = new stdClass();
			$history->notified = 1;
			$history->amount = @$ok->amount->value.@$ok->amount->currency_code;
			$history->data = 'PayPal transaction id:'.$paypal_id;
			$status = $this->payment_params->verified_status;
			if($response->result->intent == 'AUTHORIZE' && in_array($response->result->status, array('APPROVED', 'COMPLETED'))) {
				$status = $this->payment_params->pending_status;
			}

			if($this->payment_params->debug) {
				hikashop_writeToLog($response);
			}

			$this->modifyOrder($order_id, $status, $history, true);
		} else {
			hikashop_writeToLog($paypal_id);
			hikashop_writeToLog($response);
			$this->modifyOrder($order_id, $this->payment_params->invalid_status, false, true);
		}

		$return_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id='.$dbOrder->order_id . $this->url_itemid;
		$app = JFactory::getApplication();
		$app->redirect($return_url);
	}

	private function checkResponse(&$response, &$dbOrder) {
		$completed = $response->result->status == 'COMPLETED';
		if(!$completed && empty($this->payment_params->capture)) {
			$completed = $response->result->status == 'APPROVED';
		}
		if(!$completed) {
			return false;
		}

		if(!isset($response->result->purchase_units) || !count($response->result->purchase_units))
			return false;
		$purchaseUnit = reset($response->result->purchase_units);

		if($this->currency->currency_locale['int_frac_digits'] > 2)
			$this->currency->currency_locale['int_frac_digits'] = 2;
		$rounding = 2;
		if(isset($this->rounding[$this->currency->currency_code]))
			$rounding = $this->rounding[$this->currency->currency_code];
		if($purchaseUnit->amount->value < round($dbOrder->order_full_price, $rounding)) {
			return false;
		}

		if($purchaseUnit->amount->currency_code != $this->currency->currency_code) {
			return false;
		}

		return $purchaseUnit;
	}

	public function getPaymentDefaultValues(&$element) {
		$element->payment_name='PayPal Checkout';
		$element->payment_description='You can pay with PayPal Checkout using this payment method';
		$element->payment_images='PayPal';

		$element->payment_params->instant_capture = 1;
		$element->payment_params->landing_page='NO_PREFERENCE';
		$element->payment_params->invalid_status='cancelled';
		$element->payment_params->verified_status='confirmed';
		$element->payment_params->funding = array_keys($this->pluginConfig['funding'][2]);
		$element->payment_params->layout = 'vertical';
		$element->payment_params->color = 'gold';
		$element->payment_params->label = 'paypal';
		$element->payment_params->shape = 'rect';
		$element->payment_params->tagline = '1';
		$element->payment_params->listing_position = 'bottom';
		$element->payment_params->product_page_position = 'rightMiddle';
		$element->payment_params->cart_page_position = 'bottom';
		$element->payment_params->checkout_page_position = 'bottom';
		$element->payment_params->paylater_messaging_color = 'black';
	}

	private function getOrderData(&$order) {
		if($this->currency->currency_locale['int_frac_digits'] > 2)
			$this->currency->currency_locale['int_frac_digits'] = 2;

		$rounding = 2;
		if(isset($this->rounding[$this->currency->currency_code]))
			$rounding = $this->rounding[$this->currency->currency_code];

		$orderData = new stdClass();
		if(!empty($this->payment_params->capture)) {
			$orderData->intent = 'CAPTURE';
		} else {
			$orderData->intent = 'AUTHORIZE';
		}
		$orderData->application_context = new stdClass();
		if(empty($order->order_shipping_id)) {
			$orderData->application_context->shipping_preference = 'NO_SHIPPING';
		}elseif(!empty($order->cart->billing_address)) {
			$orderData->application_context->shipping_preference = 'SET_PROVIDED_ADDRESS';
		}
		if(!empty($this->payment_params->brand_name))
			$orderData->application_context->brand_name = mb_substr($this->payment_params->brand_name, 0, 127);
		$orderData->application_context->cancel_url = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id='.$order->order_id . $this->url_itemid;
		if(!empty($this->payment_params->landing_page)) {
			$orderData->application_context->landing_page = $this->payment_params->landing_page;
		}

		$orderData->payer = new stdClass();
		$orderData->payer->email_address = $this->user->user_email;

		$billing_country_code = null;
		if(empty($order->cart->billing_address->address_country_code_2)) {
			if(!empty($order->cart->billing_address->address_country->zone_code_2)) {
				$billing_country_code = $order->cart->billing_address->address_country->zone_code_2;
			}
		} else {
			$billing_country_code = $order->cart->billing_address->address_country_code_2;
		}

		if(
			!empty($order->cart->billing_address->address_firstname) && 
			!empty($order->cart->billing_address->address_lastname) && 
			!empty($order->cart->billing_address->address_street) && 
			!empty($order->cart->billing_address->address_city) && 
			!empty($order->cart->billing_address->address_post_code) && 
			!empty($billing_country_code)
		) {
			$orderData->payer->name = new stdClass();
			$orderData->payer->name->given_name = $order->cart->billing_address->address_firstname;
			$orderData->payer->name->surname = $order->cart->billing_address->address_lastname;
			$orderData->payer->address = new stdClass();
			$orderData->payer->address->address_line_1 = $order->cart->billing_address->address_street;
			if(!empty($order->cart->billing_address->address_street2)) {
				$orderData->payer->address->address_line_2 = $order->cart->billing_address->address_street2;
			}
			$orderData->payer->address->admin_area_2 = $order->cart->billing_address->address_city;
			$orderData->payer->address->postal_code = $order->cart->billing_address->address_post_code;
			if(!empty($order->cart->billing_address->address_state_name)) {
				$orderData->payer->address->admin_area_1 = $order->cart->billing_address->address_state_name;
			}elseif(!empty($order->cart->billing_address->address_state->zone_name)) {
				$orderData->payer->address->admin_area_1 = $order->cart->billing_address->address_state->zone_name;
			}
			$orderData->payer->address->country_code = $billing_country_code;
		}
		$purchaseUnit = new stdClass();
		$purchaseUnit->invoice_id = $order->order_id;
		$purchaseUnit->description = mb_substr(JText::_('ORDER_NUMBER').' '.$order->order_number,0,127);
		$purchaseUnit->items = [];

		$shipping_country_code = null;
		if(empty($order->cart->shipping_address->address_country_code_2)) {
			if(!empty($order->cart->shipping_address->address_country->zone_code_2)) {
				$shipping_country_code = $order->cart->shipping_address->address_country->zone_code_2;
			}
		} else {
			$shipping_country_code = $order->cart->shipping_address->address_country_code_2;
		}

		if(
			!empty($order->cart->shipping_address->address_firstname) && 
			!empty($order->cart->shipping_address->address_lastname) && 
			!empty($order->cart->shipping_address->address_street) && 
			!empty($order->cart->shipping_address->address_city) && 
			!empty($order->cart->shipping_address->address_post_code) && 
			!empty($shipping_country_code)
		) {
			$purchaseUnit->shipping = new stdClass();
			$purchaseUnit->shipping->name = new stdClass();
			$purchaseUnit->shipping->name->full_name = $order->cart->shipping_address->address_firstname. ' ' . $order->cart->shipping_address->address_lastname;
			$purchaseUnit->shipping->address = new stdClass();
			$purchaseUnit->shipping->address->address_line_1 = $order->cart->shipping_address->address_street;
			if(!empty($order->cart->shipping_address->address_street2)) {
				$purchaseUnit->shipping->address->address_line_2 = $order->cart->shipping_address->address_street2;
			}
			$purchaseUnit->shipping->address->admin_area_2 = $order->cart->shipping_address->address_city;
			$purchaseUnit->shipping->address->postal_code = $order->cart->shipping_address->address_post_code;
			if(!empty($order->cart->shipping_address->address_state_name)) {
				$purchaseUnit->shipping->address->admin_area_1 = $order->cart->shipping_address->address_state_name;
			}elseif(!empty($order->cart->shipping_address->address_state->zone_name)) {
				$purchaseUnit->shipping->address->admin_area_1 = $order->cart->shipping_address->address_state->zone_name;
			}
			$purchaseUnit->shipping->address->country_code = $shipping_country_code;
		} else {
			$orderData->application_context->shipping_preference = 'NO_SHIPPING';
		}

		$config = hikashop_config();
		$group = $config->get('group_options',0);
		$item_total = 0;
		$tax_total = 0;
		foreach($order->cart->products as $product) {
			if($group && $product->order_product_option_parent_id) continue;
			if(empty($product->order_product_quantity)) continue;

			$item = new stdClass();
			$item->name = mb_substr(strip_tags($product->order_product_name),0,127);
			$item->quantity = $product->order_product_quantity;
			$item->sku = mb_substr($product->order_product_code,0,127);
			$item->unit_amount = new stdClass();
			$item->unit_amount->value = number_format(round($product->order_product_price, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
			$item->unit_amount->currency_code = $this->currency->currency_code;
			$item->tax = new stdClass();
			$item->tax->value = number_format(round($product->order_product_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
			$item->tax->currency_code = $this->currency->currency_code;
			$purchaseUnit->items[] = $item;
			$item_total += round($product->order_product_price, $rounding) * $product->order_product_quantity;
			$tax_total += round($product->order_product_tax, $rounding) * $product->order_product_quantity;
		}
		$discount = new stdClass();
		$discount->currency_code = $this->currency->currency_code;
		$discount->value = 0;

		if(!empty($order->cart->additional)){
			foreach($order->cart->additional as $product) {
				if(empty($product->order_product_price) || $product->order_product_price == 0) continue;
				if($product->order_product_price < 0) {
					$discount->value += $product->order_product_price+$product->order_product_tax;
					continue;
				}
				$item = new stdClass();
				$item->name =  mb_substr(JText::_(strip_tags($product->order_product_name)),0,127);
				$item->quantity = 1;
				$item->sku = mb_substr($product->order_product_code,0,127);
				$item->unit_amount = new stdClass();
				$item->unit_amount->value = number_format(round($product->order_product_price, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
				$item->unit_amount->currency_code = $this->currency->currency_code;
				$item->tax = new stdClass();
				$item->tax->value = number_format(round($product->order_product_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
				$item->tax->currency_code = $this->currency->currency_code;
				$purchaseUnit->items[] = $item;
				$item_total += round($product->order_product_price, $rounding);
				$tax_total += round($product->order_product_tax, $rounding);
			}
		}
		if(!empty($order->order_payment_price) && bccomp(sprintf('%F',$order->order_payment_price), 0, 5)) {
			$item = new stdClass();
			$item->name = mb_substr(JText::_('HIKASHOP_PAYMENT'),0,127);
			$item->quantity = 1;
			$item->sku = 'payment_fee';
			$item->unit_amount = new stdClass();
			$item->unit_amount->value = number_format(round($order->order_payment_price-$order->order_payment_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
			$item->unit_amount->currency_code = $this->currency->currency_code;
			$item->tax = new stdClass();
			$item->tax->value = number_format(round($order->order_payment_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
			$item->tax->currency_code = $this->currency->currency_code;
			$purchaseUnit->items[] = $item;
			$item_total += round($order->order_payment_price-$order->order_payment_tax, $rounding);
			$tax_total += round($order->order_payment_tax, $rounding);
		}

		if(!isset($this->payment_params->details))
			$this->payment_params->details = 1;

		if(empty($this->payment_params->details)) {
			$item_total_with_taxes = round($order->cart->full_total->prices[0]->price_value_with_tax, (int)$this->currency->currency_locale['int_frac_digits']);
			$item_total = round($order->cart->full_total->prices[0]->price_value, (int)$this->currency->currency_locale['int_frac_digits']);
			$tax_total = $item_total_with_taxes - $item_total;
			$item = new stdClass();
			$item->name = $purchaseUnit->description;
			$item->quantity = 1;
			$item->sku = 'full_order';
			$item->unit_amount = new stdClass();
			$item->unit_amount->value = number_format($item_total, $rounding, '.', '');
			$item->unit_amount->currency_code = $this->currency->currency_code;
			$item->tax = new stdClass();
			$item->tax->value = number_format($tax_total, $rounding, '.', '');
			$item->tax->currency_code = $this->currency->currency_code; 
			$purchaseUnit->items = array($item);
		}

		$purchaseUnit->amount = new stdClass();
		$purchaseUnit->amount->value = number_format(round($order->cart->full_total->prices[0]->price_value_with_tax, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
		$purchaseUnit->amount->currency_code = $this->currency->currency_code;
		$purchaseUnit->amount->breakdown = new stdClass();
		$purchaseUnit->amount->breakdown->item_total = new stdClass();
		$purchaseUnit->amount->breakdown->item_total->value = number_format($item_total, $rounding, '.', '');
		$purchaseUnit->amount->breakdown->item_total->currency_code = $this->currency->currency_code;
		$purchaseUnit->amount->breakdown->tax_total = new stdClass();
		$purchaseUnit->amount->breakdown->tax_total->value = number_format($tax_total, $rounding, '.', '');
		$purchaseUnit->amount->breakdown->tax_total->currency_code = $this->currency->currency_code;

		if(!empty($this->payment_params->details)) {
			if(!empty($order->cart->coupon) && bccomp(sprintf('%F',$order->order_discount_price), 0, 5)){
				$discount->value -= $order->order_discount_price;
			}
			if($discount->value !== 0) {
				$discount->value = number_format(round(-1*$discount->value, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
				$purchaseUnit->amount->breakdown->discount = $discount;
			}

			if(!empty($order->order_shipping_price) && bccomp(sprintf('%F',$order->order_shipping_price), 0, 5)) {
				$purchaseUnit->amount->breakdown->shipping = new stdClass();
				$purchaseUnit->amount->breakdown->shipping->value = number_format(round($order->order_shipping_price, (int)$this->currency->currency_locale['int_frac_digits']), $rounding, '.', '');
				$purchaseUnit->amount->breakdown->shipping->currency_code = $this->currency->currency_code;
			}
		}
		$orderData->purchase_units = [$purchaseUnit];

		return $orderData;
	}

	private function _generateClientToken($id) {

		$url = 'https://api-m.paypal.com';
		$merchantId = $this->liveMerchantId;

		if(empty($this->payment_params->sandbox)) {
			$this->payment_params->sandbox = 0;
		}
		if(!empty($this->payment_params->sandbox)) {
			$url = 'https://api-m.sandbox.paypal.com';
			$merchantId = $this->sandboxMerchantId;
		}

		$curl = curl_init();
		$post = "grant_type=client_credentials";
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url."/v1/oauth2/token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => $this->payment_params->client_id.":".$this->payment_params->client_secret,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $post,
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/x-www-form-urlencoded",
				"PayPal-Partner-Attribution-Id: ".$this->bncode,
			),
			CURLOPT_CAINFO => __DIR__.'/cacert.pem',
			CURLOPT_CAPATH => __DIR__.'/cacert.pem',
			CURLINFO_HEADER_OUT => true,
		));
		$curl_result = curl_exec($curl);
		if(empty($curl_result)) {
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($post);
			hikashop_writeToLog($curl_result);
			hikashop_writeToLog('Returned data from Access Token Request not valid','error',true);
			return false;
		}
		$array = json_decode($curl_result, true);
		if(empty($array)) {
			hikashop_writeToLog(curl_getinfo($curl));
			hikashop_writeToLog($post);
			hikashop_writeToLog($curl_result);
			hikashop_writeToLog('Returned data from Access Token Request not valid','error',true);
			return false;
		}
		if(empty($array['access_token'])) {
			hikashop_writeToLog($array);
			hikashop_writeToLog($array['error_description'],'error',true);
			return false;
		}
		$token = $array['access_token'];

		$curl = curl_init();
		$post = '{"customer_id":"'.$id.'"}';
    	curl_setopt_array($curl, array(
			CURLOPT_URL => $url."/v1/identity/generate-token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_POSTFIELDS => $post,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json",
				"Authorization: Bearer ".$token,
				"PayPal-Partner-Attribution-Id: ".$this->bncode,
    		),
			CURLOPT_CAINFO => __DIR__.'/cacert.pem',
			CURLOPT_CAPATH => __DIR__.'/cacert.pem',
			CURLINFO_HEADER_OUT => true,
		));
		$curl_result = curl_exec($curl);
		$array = json_decode($curl_result, true);
		if(!empty($array['client_token'])) {
			return $array['client_token'];
		}
		hikashop_writeToLog($post);
		hikashop_writeToLog($array);
		hikashop_writeToLog('Client token could not be generated by PayPal Checkout for ACDC');
		return false;
	}

	private function loadJS() {
		$this->params = [
			'client-id' => $this->payment_params->client_id,
			'integration-date' => '2024-05-19',
			'currency' => $this->currency->currency_code,
			'components' => 'buttons,funding-eligibility,messages',
		];

		if(!empty($this->payment_params->enable_credit_card)) {

			$token = $this->_generateClientToken($this->user->user_id);
			if(empty($token)) {
				$this->payment_params->enable_credit_card = false;
			} else {
				$this->params['components'].=',hosted-fields';
				$this->extraParams = 'data-client-token="'.$token.'"';
				$_SESSION['hikashop_paypalcheckout_client_token'] = $token;
			}
			if(!empty($this->payment_params->enable_3dsecure) && $this->payment_params->enable_3dsecure != 'NO') {
				$this->enable_3dsecure = $this->payment_params->enable_3dsecure;
			}
		}

		if(!empty($this->payment_params->disable_funding)) {
			if(is_string($this->payment_params->disable_funding)) {
				$this->payment_params->disable_funding = explode(',', $this->payment_params->disable_funding);
			}
			$key = array_search('sofort', $this->payment_params->disable_funding);
			if ($key !== false) {
				unset($this->payment_params->disable_funding[$key]);
			}
			$this->payment_params->disable_funding = implode(',', $this->payment_params->disable_funding);

			$this->params['disable-funding'] = $this->payment_params->disable_funding;
		}
		if(!empty($this->payment_params->funding)) {
			if(is_string($this->payment_params->funding)) {
				$this->payment_params->funding = explode(',', $this->payment_params->funding);
			}
			$key = array_search('sofort', $this->payment_params->funding);
			if ($key !== false) {
				unset($this->payment_params->funding[$key]);
			}
			$this->payment_params->funding = implode(',', $this->payment_params->funding);
			$this->params['enable-funding'] = $this->payment_params->funding;
		}

		if(!empty($this->payment_params->capture)) {
			$this->params['intent'] = 'capture';
		} else {
			$this->params['intent'] = 'authorize';
		}
		if(!empty($this->payment_params->debug)) {
			$this->params['debug'] = 'true';
		}

		if(empty($this->payment_params->layout)) {
			$this->payment_params->layout = 'vertical';
		}
		if(empty($this->payment_params->color)) {
			$this->payment_params->color = 'gold';
		}
		if(empty($this->payment_params->label)) {
			$this->payment_params->label = 'paypal';
		}
		if(empty($this->payment_params->shape)) {
			$this->payment_params->shape = 'rect';
		}
		if($this->payment_params->layout == 'horizontal') {
			if(!isset($this->payment_params->tagline)) {
				$this->payment_params->tagline = '1';
			}
		} else {
			$this->payment_params->tagline = false;
		}
	}
}
