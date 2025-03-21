<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
use WhatArmy\fedexRest as fedexRest;
use FedexRest\Services\Rates\CreateRatesRequest;
use FedexRest\Entity\Person;
use FedexRest\Entity\Address;
use FedexRest\Entity\Item;
use FedexRest\Entity\Weight;
use FedexRest\Entity\Dimensions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

use FedexRest\Services\Ship\Entity\Value;
use FedexRest\Services\Ship\Type\LinearUnits;
use FedexRest\Services\Ship\Type\WeightUnits;
use FedexRest\Services\Ship\Type\PickupType;

require __DIR__ . '/vendor/autoload.php';

class plgHikashopshippingFedEx2 extends hikashopShippingPlugin
{
	var $pluginConfig = array(
		'client_id' => array('PAYPAL_CHECKOUT_CLIENT_ID', 'input'),
		'client_secret' => array('PAYPAL_CHECKOUT_CLIENT_SECRET', 'input'),
		'account_number' => array('FEDEX_ACCOUNT_NUMBER', 'input'),
		'services' => array(
			'SHIPPING_SERVICES', 'checkbox', array(),
		),
		'packaging_type' => array('SHIPPING_PACKAGING_TYPE', 'list', array(
				"YOUR_PACKAGING"=> 'SHIPPING_YOUR_PACKAGING',
				"FEDEX_PAK"=>"FedEx pak",
				"FEDEX_TUBE"=>"FedEx tube",
				"FEDEX_BOX"=>"FedEx box",
				"FEDEX_SMALL_BOX"=>"FedEx small box",
				"FEDEX_MEDIUM_BOX"=>"FedEx medium box",
				"FEDEX_LARGE_BOX"=>"FedEx large box",
				"FEDEX_EXTRA_LARGE_BOX"=>"FedEx extra large box",
				"FEDEX_10KG_BOX"=>"FedEx 10KG box",
				"FEDEX_25KG_BOX"=>"FedEx 25 box",
				"FEDEX_ENVELOPE"=>"FedEx envelope",
			),
		),
		'pickup_type' => array('PICKUP_TYPE', 'list', array(
				"CONTACT_FEDEX_TO_SCHEDULE"=>"Contact Fedex to Schedule",
				"DROPOFF_AT_FEDEX_LOCATION"=> 'Drop off at Fedex Location',
				"USE_SCHEDULED_PICKUP"=>"Use scheduled pickup",
			),
		),
		'weight_unit' => array('WEIGHT_UNIT', 'list', array(
				'lb' => 'lb',
				'g' => 'G',
				'kg' => 'Kg',
				'mg' => 'Mg',
			),
		),
		'transit_time' => array('FEDEX_TRANSIT_TIME', 'boolean', 0),
		'add_insurance' => array('ADD_INSURRANCE', 'boolean', 0),
		'weight_approximation' => array('FEDEX_WEIGHT_APPROXIMATION', 'input'),
		'dimensions_approximation' => array('FEDEX_DIMENSION_APPROXIMATION', 'input'),
		'name' => array('HIKA_NAME', 'input'),
		'address_street' => array('STREET','input'),
		'address_street2' => array('STREET_LINE2','input'),
		'address_city' => array('CITY', 'input'),
		'postal_code' => array('POSTAL_CODE', 'input'),
		'state_code' => array('STATE_CODE', 'input'),
		'country_code' => array('COUNTRY_CODE', 'input'),
		'debug' => array('DEBUG', 'boolean', 0)
	);

	var $fedex_methods = array(
		array('key'=>1,'code' => 'FEDEX_GROUND', 'name' => 'FedEx Ground', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172') , 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key'=>2,'code' => 'FEDEX_2_DAY', 'name' => 'FedEx 2 Day', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key'=>3,'code' => 'FEDEX_EXPRESS_SAVER', 'name' => 'FedEx Express Saver', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key'=>4,'code' => 'FIRST_OVERNIGHT', 'name' => 'FedEx First Overnight', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key'=>5,'code' => 'GROUND_HOME_DELIVERY', 'name' => 'FedEx Ground (Home Delivery)', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key'=>6,'code' => 'PRIORITY_OVERNIGHT', 'name' => 'FedEx Priority Overnight', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key'=>7,'code' => 'SMART_POST', 'name' => 'FedEx Smart Post', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key'=>8,'code' => 'STANDARD_OVERNIGHT', 'name' => 'FedEx Standard Overnight', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key'=>9,'code' => 'FEDEX_INTERNATIONAL_GROUND', 'name' => 'FedEx International Ground'),
		array('key'=>10,'code' => 'INTERNATIONAL_ECONOMY', 'name' => 'FedEx International Economy'),
		array('key'=>11,'code' => 'INTERNATIONAL_ECONOMY_DISTRIBUTION', 'name' => 'FedEx International Economy Distribution'),
		array('key'=>12,'code' => 'INTERNATIONAL_FIRST', 'name' => 'FedEx International First'),
		array('key'=>13,'code' => 'FEDEX_INTERNATIONAL_PRIORITY', 'name' => 'FedEx International Priority'),
		array('key'=>14,'code' => 'INTERNATIONAL_PRIORITY_DISTRIBUTION', 'name' => 'FedEx International Priority Distribution'),
		array('key'=>15,'code' => 'EUROPE_FIRST_INTERNATIONAL_PRIORITY', 'name' => 'FedEx Europe First'),
		array('key'=>16,'code' => 'FEDEX_REGIONAL_ECONOMY', 'name' => 'FedEx Regional Economy', 'countries' => 'Austria, Belgium, Czech Republic, Denmark, Estonia, Finland, France, Germany, Hungary, Ireland, Italy, Latvia, Lithuania, Luxembourg, Netherlands, Norway, Poland, Slovenia, Spain, Sweden, Switzerland, United Kingdom'),
		array('key'=>17,'code' => 'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS', 'name' => 'FedEx International Priority Express'),
		array('key'=>18,'code' => 'FEDEX_INTERNATIONAL_CONNECT_PLUS', 'name' => 'FedEx International Connect Plus'),
		array('key'=>19,'code' => 'FEDEX_FIRST', 'name' => 'FedEx First'),
		array('key'=>20,'code' => 'FEDEX_PRIORITY_EXPRESS', 'name' => 'FedEx Priority Express'),
		array('key'=>21,'code' => 'FEDEX_PRIORITY', 'name' => 'FedEx Priority'),
	);

	var $multiple = true;
	var $name = 'fedex2';
	var $doc_form = 'fedex2';
	var $use_cache = false;

	function __construct(&$subject, $config) {
		$all_services = array();
		foreach($this->fedex_methods as $method) {
			$txt = '';
			if (isset($method['countries']))
			$txt = ' ('.$method['countries'].')';

			$varName =  $method['code'];
			$all_services[] = $varName;
			$this->pluginConfig['services'][2][$varName] = $method['name'].$txt;
		}
		$this->all_services = implode(',', $all_services);

		return parent::__construct($subject, $config);
	}

	public function onShippingDisplay(&$order, &$dbrates, &$usable_rates, &$messages) {
		if(empty($order->shipping_address))
			return true;

		if($this->loadShippingCache($order, $usable_rates, $messages))
			return true;

		$local_usable_rates = array();
		$local_messages = array();
		$ret = parent::onShippingDisplay($order, $dbrates, $local_usable_rates, $local_messages);
		if($ret === false)
			return false;

		if(!function_exists('curl_init')) {
			$app = JFactory::getApplication();
			$app->enqueueMessage('The FedEx 2 shipping plugin needs the CURL library installed but it seems that it is not available on your server. Please contact your web hosting to set it up.','error');
			return false;
		}

		$cache_usable_rates = array();
		$cache_messages = array();

		$currentShippingZone = null;
		$currentCurrencyId = null;

		$found = true;
		$usableWarehouses = array();
		$zoneClass = hikashop_get('class.zone');
		$zones = $zoneClass->getOrderZones($order);

		$this->error_messages = array();

		foreach($local_usable_rates as $k => $rate) {

			if(empty($rate->shipping_params->services)) {
				$cache_messages['no_shipping_methods_configured'] = 'No shipping methods configured in the Fedex shipping plugin options';
				continue;
			}
			$null = null;
			if(empty($this->shipping_currency_id)) {
				$this->shipping_currency_id = hikashop_getCurrency();
			}
			$currencyClass = hikashop_get('class.currency');
			$currencies = $currencyClass->getCurrencies(array($this->shipping_currency_id), $null);
			$this->shipping_currency_code = $currencies[$this->shipping_currency_id]->currency_code;

			$cart = hikashop_get('class.cart');
			$cart->loadAddress($null, $order->shipping_address->address_id, 'object', 'shipping');

			$receivedMethods = $this->_getBestMethods($rate, $order, $null);
			if(empty($receivedMethods) || ($receivedMethods == false)) {
				$this->error_messages['no_rates'] = JText::_('NO_SHIPPING_METHOD_FOUND');
				continue;
			}
			if (is_array($receivedMethods)) {
				$i = 0;
				$new_usable_rates = array();
				foreach($receivedMethods as $method) {
					$new_usable_rates[$i] = clone($rate);
					$new_usable_rates[$i]->shipping_price += round($method['value'], 2);
					$selected_method = '';
					$name = '';
					$description = '';

					foreach($this->fedex_methods as $fedex_method) {
						if($fedex_method['code'] == $method['code']) {
							$selected_method = $fedex_method['key'];

							$typeKey = str_replace(' ','_', strtoupper($fedex_method['name']));
							$shipping_name = JText::_($typeKey);

							if($shipping_name != $typeKey)
								$name = $shipping_name;
							else
								$name = $fedex_method['name'];

							$transit_time = 0;
							if (isset($rate->shipping_params->transit_time))
								$transit_time = $rate->shipping_params->transit_time;

							if ($transit_time && $method['delivery_date'] != '')
								$description .= '<div class="fedex_eta">' . JText::sprintf('SHIPPING_DELIVERY_DELAY',$method['delivery_date']).'</div>';

							$shipping_description = JText::_($typeKey.'_DESCRIPTION');
							if($shipping_description != $typeKey.'_DESCRIPTION')
								$description .= $shipping_description;

							break;
						}
					}
					$new_usable_rates[$i]->shipping_name = $name;

					if($description != '')
						$new_usable_rates[$i]->shipping_description .= $description;

					if(!empty($selected_method))
						$new_usable_rates[$i]->shipping_id .= '-' . $selected_method;
					$i++;
				}
			}

			foreach($new_usable_rates as $i => $usable_rate) {
				if(isset($usable_rate->shipping_price_orig) || isset($usable_rate->shipping_currency_id_orig)){
					if($usable_rate->shipping_currency_id_orig == $usable_rate->shipping_currency_id)
						$usable_rate->shipping_price_orig = $usable_rate->shipping_price;
					else
						$usable_rate->shipping_price_orig = $currencyClass->convertUniquePrice($usable_rate->shipping_price, $usable_rate->shipping_currency_id, $usable_rate->shipping_currency_id_orig);
				}
				$usable_rates[$usable_rate->shipping_id] = $usable_rate;
				$cache_usable_rates[$usable_rate->shipping_id] = $usable_rate;
			}
		}

		if(!empty($this->error_messages)) {
			foreach($this->error_messages as $key => $value) {
				$cache_messages[$key] = $value;
			}
		}

		$this->setShippingCache($order, $cache_usable_rates, $cache_messages); // return True or False...

		if(!empty($cache_messages)) {
			foreach($cache_messages as $k => $msg) {
				$messages[$k] = $msg;
			}
		}
	}

	public function shippingMethods(&$main) {
		$methods = array();
		if(!empty($main->shipping_params->services)) {
			foreach($main->shipping_params->services as $method) {
				$selected = null;
				foreach($this->fedex_methods as $fedex) {
					if($fedex['code'] == $method) {
						$selected = $fedex;
						break;
					}
				}
				if($selected)
					$methods[$main->shipping_id .'-'. $selected['key']] = $selected['name'];
			}
		}
		return $methods;
	}

	protected function extractDeliveryDate($shippingName) {
		if (preg_match('/\d{4}\/\d{2}\/\d{2}/', $shippingName, $matches)) {
			return $matches[0];
		}
		return null;
	}

	protected function _fedex_request_rates ($method_params, $address, $order) {
		$clientId = $method_params->shipping_params->client_id;
		$clientSecret = $method_params->shipping_params->client_secret;
		$accountNumber = $method_params->shipping_params->account_number;
		$pickup = array (
			'CONTACT_FEDEX_TO_SCHEDULE' => PickupType::_CONTACT_FEDEX_TO_SCHEDULE,
			'DROPOFF_AT_FEDEX_LOCATION' => PickupType::_DROPOFF_AT_FEDEX_LOCATION,
			'USE_SCHEDULED_PICKUP' => PickupType::_USE_SCHEDULED_PICKUP,
		);
		$pickupParams = 'CONTACT_FEDEX_TO_SCHEDULE';
		if (!empty($method_params->shipping_params->pickup_type) && in_array($method_params->shipping_params->pickup_type, $pickup))
			$pickupParams = $method_params->shipping_params->pickup_type;

		$addr_address_street2 = '';
		if (isset($address->shipping_address->address_street2))
			$addr_address_street2 = $address->shipping_address->address_street2;
		$meth_address_street2 = '';
		if (isset($method_params->shipping_params->address_street2))
			$meth_address_street2 = $method_params->shipping_params->address_street2;

		$addr_street_lines = array($address->shipping_address->address_street, $addr_address_street2);
		$meth_street_lines = array($method_params->shipping_params->address_street, $meth_address_street2);
		$array_address = array(
			'address' =>
				(new Address)
				->setStreetLines(...$addr_street_lines)
				->setCity($address->shipping_address->address_city)
				->setPostalCode($address->shipping_address->address_post_code)
				->setCountryCode($address->shipping_address->address_country->zone_code_2)
			,	
			'method' =>
				(new Address)
				->setStreetLines(...$meth_street_lines)
				->setCity($method_params->shipping_params->address_city)
				->setPostalCode($method_params->shipping_params->postal_code)
				->setCountryCode($method_params->shipping_params->country_code)
		);
		$addr_country_code = $address->shipping_address->address_country->zone_code_2;
		$addr_state_code =  $address->shipping_address->address_state->zone_code_3;
		if ($addr_country_code == 'US' || $addr_country_code == 'PR' || $addr_country_code == 'CA' && $addr_state_code != '')
			$array_address['address']->setStateOrProvince($addr_state_code);

		$meth_country_code = $method_params->shipping_params->country_code;
		$meth_state_code =  $method_params->shipping_params->state_code;
		if ($meth_country_code == 'US' || $meth_country_code == 'PR' || $meth_country_code == 'CA' && $meth_state_code != '')
			$array_address['method']->setStateOrProvince($meth_state_code);

		try {
			$auth_result = $this->authorize($clientId, $clientSecret);

			$requested_rates = false;
			if (is_string($auth_result)) {
				$responseArray = json_decode($auth_result, true);
				$app = JFactory::getApplication();
				if(isset($responseArray['errors']) && is_array($responseArray['errors'])) {
					foreach ($responseArray['errors'] as $error) {
						$app->enqueueMessage('An error occurred. '. $error['message'] .', Code error :'. $error['code']);
					}
				}
			}
			if (isset($auth_result->access_token)) {
				$createRatesRequest = (new CreateRatesRequest)
					->setAccessToken(((string)$auth_result->access_token))
					->setAccountNumber($accountNumber)
					->setRateRequestTypes('ACCOUNT', 'LIST')
					->setPackagingType($method_params->shipping_params->packaging_type)
					->setPickupType($pickup[$pickupParams])
					->useProduction()
					->setRecipient(
						(new Person)
							->withAddress(
								$array_address['address']
							)
					)
					->setShipper(
						(new Person)
							->withAddress(
								$array_address['method']
							)
					);
				$weight_unit = 'kg';
				if ($method_params->shipping_params->weight_unit != '') 
					$weight_unit = $method_params->shipping_params->weight_unit;

				$new_data = $this->DimWeightProcess ($order, $method_params->shipping_params);

				$items = array();
				foreach($new_data->products as $product) {
					$i = 1;
					if(isset($product->cart_product_quantity))
						$i = $product->cart_product_quantity;

					while($i > 0) {
						$i--;
						$item = (new Item)
						->setWeight(
							(new Weight)
							->setValue((float)$product->product_weight)
							->setUnit($new_data->fedex_weight_unit)
						)
						->setDimensions(
							(new Dimensions)
							->setLength((float)$product->product_length)
							->setWidth((float)$product->product_width)
							->setHeight((float)$product->product_height)
							->setUnits($new_data->fedex_dim_unit)
						);
						if ($method_params->shipping_params->add_insurance) {
							$item->setDeclaredValue((new Value)
							->setAmount((float)$product->prices[0]->price_value_with_tax)
							->setCurrency($this->shipping_currency_code));
						}
						$items[] = $item;
					}
				}
				$createRatesRequest->setLineItems(...$items);

				$transit_time = 0;
				if (isset($method_params->shipping_params->transit_time))
					$transit_time = $method_params->shipping_params->transit_time;

				if ($transit_time && $addr_country_code == $meth_country_code)
					$createRatesRequest->setReturnTransitTimes(true);

				if(!empty($method_params->shipping_params->debug)) {
					hikashop_writeToLog('FedEx 2 : Impossible to received rates from FedEx for request :');
					hikashop_writeToLog($createRatesRequest->prepare());
				}
				$requested_rates = $createRatesRequest->request();
			}
		} catch(Exception $e) {
			if(!empty($method_params->shipping_params->debug)) {
				hikashop_writeToLog($e->getMessage());
			}
			$this->error_messages['fedex2_error'] = $e->getMessage();

			$requested_rates = false;
		}
		return $requested_rates;
	}


	protected function authorize($client_id, $client_secret) {
		$config = ["verify" => __DIR__ . "/cacert.pem"];
		$httpClient = new Client($config);
		if (!empty($client_id) && !empty($client_secret)) {
			$query = $httpClient->request('POST', 'https://apis.fedex.com/oauth/token', [
				'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded',
				],
				'form_params' => [
					'grant_type' => 'client_credentials',
					'client_id' => $client_id,
					'client_secret' => $client_secret,
				]
			]);
			if ($query->getStatusCode() === 200) {
				return json_decode($query->getBody()->getContents());
			}
		} else {
			throw new MissingAuthCredentialsException('Please provide auth credentials');
		}
	}

	protected function _getBestMethods(&$rate, &$order, $null) {
		$usableMethods = array();
		$zone_code = '';
		$methods = $this->_getShippingMethods($rate, $order, $null);

		if(empty($methods))
			return false;

		if($methods !== true) {
			foreach($methods as $i => $method) {
				$found = false;
				foreach($this->fedex_methods as $availableMethod) {
					if($availableMethod['code'] == $method['code']) {
						$varCode = $method['code'];
						if(in_array($varCode, $rate->shipping_params->services))
							$found = true;
					}
				}

				if(!$found)
					unset($methods[$i]);
			}
		}
		return $methods;
	}

	function getShipAddress($usable_addresses) {
		foreach ($usable_addresses->shipping as $adr) {
			if ($adr->address_default) {
				$ship_address = $adr;
				break;
			}
		}
		return $ship_address;
	}

	protected function _getShippingMethods(&$rate, &$order, $null) {
		$config = hikashop_config();
		$currency = (int)$config->get('main_currency', 1);
		if(!empty($rate->shipping_params->shipping_currency_id)) {
			$currency = $rate->shipping_params->shipping_currency_id;
		}
		$currencyClass = hikashop_get('class.currency');
		$array = null;
		$currencies = $currencyClass->getCurrencies(array($currency), $array);
		$currency_code = $currencies[$currency]->currency_code;

		$data = array();
		$data['currency'] = $data['old_currency'] = $currency;
		$data['currency_code'] = $data['old_currency_code'] = $currency_code;

		$price = 0;
		if(isset($order->total->prices[0]->price_value))
			$price = $order->total->prices[0]->price_value;

		$fedex_rates = $this->_fedex_request_rates($rate, $null, $order);

		if (!$fedex_rates)
			return false;

		$usableMethods = $this->_FedExRequestMethods($rate, $null, $price, $data['currency_code'], $fedex_rates);

		if (!$usableMethods) 
			return false;

		$currencies = array();
		$db = JFactory::getDBO();

		foreach($usableMethods as $method){
			$currencies[$method['currency_code']] = $db->Quote( $currency_code );
		}

		$query = 'SELECT currency_code, currency_id FROM '. hikashop_table('currency') .' WHERE currency_code IN ('. implode(',',$currencies) .')';
		$db->setQuery($query);
		$currencyList = $db->loadObjectList();
		$currencyList = reset($currencyList);
		foreach($usableMethods as $i => $method) {
			$usableMethods[$i]['currency_id'] = $currencyList->currency_id;
		}
		$usableMethods = parent::_currencyConversion($usableMethods, $order);
		return $usableMethods;
	}

	protected function _FedExRequestMethods( &$rate, &$address, $price, $currency_code, $fedex_rates) {
		if ($fedex_rates == false || !isset($fedex_rates)) {
			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog('FedEx 2 : Impossible to received rates from FedEx for order ID :');
			}
			$app = JFactory::getApplication();
			$app->enqueueMessage('An error occurred. The connection to the FedEx server could not be established, no rates received from FedEx');
			return false;
		}
		if (isset($fedex_rates->errors)) {
			$code = $fedex_rates->errors[0]->code;
			$message = $fedex_rates->errors[0]->message;
			$this->error_messages['no_shipping_methods_available'] = $code .'</br>'. $message;

			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog($fedex_ratest);
				hikashop_writeToLog('FedEx 2 : no FedEx rates available = '. $code .' : '. $message);
			}

			return false;
		}
		else {
			$array_rates = $fedex_rates->output->rateReplyDetails;
			$method_services = $rate->shipping_params->services;
			$i = 0;
			$shipment = array();
			foreach($array_rates as $k => $fedex_rate) { 
				if (in_array($fedex_rate->serviceType, $method_services)) {
					$best_rate = '';
					foreach ($fedex_rate->ratedShipmentDetails as $k => $avalaible_rate) {
						if($best_rate == '' || $best_rate > $avalaible_rate->totalNetFedExCharge)
						$best_rate = $avalaible_rate->totalNetFedExCharge;
					}

					$delivery_date = '';
					$transit_time = 0;
					if (isset($rate->shipping_params->transit_time))
						$transit_time = $rate->shipping_params->transit_time;

					if ($transit_time && isset($fedex_rate->operationalDetail->deliveryDate)) {
						$date_str = $fedex_rate->operationalDetail->deliveryDate;
						$date = new DateTime($date_str);

						$day = $date->format('d');
						$month = $date->format('m');
						$year = $date->format('Y');
						$delivery_date = (string)$year.'/'.$month.'/'.$day;
					}

					$rate_curr = $fedex_rate->ratedShipmentDetails[0]->currency;
					$shipment[$i]['name'] = (string)$fedex_rate->serviceName;
					$shipment[$i]['value'] = (string)$best_rate;
					$shipment[$i]['currency_code'] = (string)$rate_curr;
					$shipment[$i]['old_currency_code'] = (string)$rate_curr;
					$shipment[$i]['code'] = (string)$fedex_rate->serviceType;
					$shipment[$i]['delivery_date'] = $delivery_date;

					$i++;
				}
			}
			if ($rate->shipping_params->debug) {
				hikashop_writeToLog($shipment);
			}
		}
		if (!count($shipment))
			return false;

		return $shipment;
	}

	protected function DimWeightProcess ($order, $shipping_params) {
		$weight_unit = 'g';
		if ($shipping_params->weight_unit != '')
			$weight_unit = $weight_unit = $shipping_params->weight_unit;
		if ($shipping_params->weight_unit == 'oz')
			$weight_unit = 'lb';

		$weightClass = hikashop_get('helper.weight');
		$volumeClass = hikashop_get('helper.volume');
		$Unit_links = array (
			'kg' => 'cm',
			'g' => 'cm',
			'mg' => 'cm',
			'lb' => 'in',
			'oz' => 'in',
		);

		$weightUnits = array (
			'lb' => WeightUnits::_POUND,
			'g' => WeightUnits::_GRAM,
			'kg' => WeightUnits::_KILOGRAM,
			'mg' => WeightUnits::_MILLIGRAM,
			'oz' => WeightUnits::_OUNCE,
		);

		$linearUnits = array (
			'cm' => LinearUnits::_CENTIMETER,
			'in' => LinearUnits::_INCH,
			'm' => LinearUnits::_METER ,
			'ft' => LinearUnits::_FOOT,
			'yd' => LinearUnits::_YARD,
		);

		$new_data = hikashop_copy($order);
		foreach ($new_data->products as $id => $product) {
			$width = $product->product_width;
			$length = $product->product_length;
			$height = $product->product_height;
			$weight = $product->product_weight;

			if ($product->product_weight_unit != $weight_unit)
				$weight = $weightClass->convert($weight, $product->product_weight_unit, $weight_unit);

			$old_unit = $product->product_dimension_unit;
			$dim_unit = $Unit_links[$weight_unit];

			if ($old_unit != $dim_unit) {
				$width = $volumeClass->convert($width, $old_unit, $dim_unit , 'dimension');
				$length = $volumeClass->convert($length, $old_unit, $dim_unit , 'dimension');
				$height = $volumeClass->convert($height, $old_unit, $dim_unit , 'dimension');
			}

			$fedex_weight_unit = $weightUnits[$weight_unit];
			$fedex_dim_unit = $linearUnits[$dim_unit];

			if ($product->product_width != $width)
				$new_data->products[$id]->product_width = $width;

			if ($product->product_height != $height)
				$new_data->products[$id]->product_height = $height;

			if ($product->product_length != $length)
				$new_data->products[$id]->product_length = $length;

			if ($product->product_weight != $weight)
				$new_data->products[$id]->product_weight = $weight;

			if ($product->product_weight_unit != $weight_unit)
				$new_data->products[$id]->product_weight_unit = $weight_unit;

			if ($product->product_dimension_unit != $dim_unit)
				$new_data->products[$id]->product_dimension_unit = $dim_unit;

			if(!empty($shipping_params->dimensions_approximation)) {
				$new_data->products[$id]->product_height = $height + $height * $shipping_params->dimensions_approximation / 100;
				$new_data->products[$id]->product_length = $length + $length * $shipping_params->dimensions_approximation / 100;
				$new_data->products[$id]->product_width = $width + $width * $shipping_params->dimensions_approximation / 100;
			}
			if(!empty($shipping_params->weight_approximation))
				$new_data->products[$id]->product_weight = $weight + $weight * $shipping_params->weight_approximation / 100;

			$new_data->fedex_weight_unit = $fedex_weight_unit;
			$new_data->fedex_dim_unit = $fedex_dim_unit;
		}
		return $new_data;
	}

	public function onShippingConfiguration(&$element) {
		$country_array = array('US', 'PR', 'CA');

		$app = JFactory::getApplication();
		if(empty($element->shipping_params->client_id)) {
			$app->enqueueMessage(JText::sprintf('ENTER_INFO', 'Fedex', 'Client Id'));
		}
		if(empty($element->shipping_params->client_secret)) {
			$app->enqueueMessage(JText::sprintf('ENTER_INFO', 'Fedex', 'Client Secret'));
		}
		if(empty($element->shipping_params->account_number)) {
			$app->enqueueMessage(JText::sprintf('ENTER_INFO', 'Fedex', 'Account Number'));
		}
		if(empty($element->shipping_params->services)) {
			$app->enqueueMessage(JText::sprintf('ENTER_INFO', 'Fedex', 'Service Type'));
		}
		if(empty($element->shipping_params->address_street)) {
			$app->enqueueMessage(JText::sprintf('ENTER_INFO', 'Fedex', 'Address Street'));
		}
		if(empty($element->shipping_params->address_city)) {
			$app->enqueueMessage(JText::sprintf('ENTER_INFO', 'Fedex', 'Address City'));
		}
		if(empty($element->shipping_params->postal_code)) {
			$app->enqueueMessage(JText::sprintf('ENTER_INFO', 'Fedex', 'Postal Code'));
		}
		if(!empty($element->shipping_params->state_code) && strlen($element->shipping_params->state_code) > 2) {
			$app->enqueueMessage(JText::sprintf('FEDEX_CODE_2_CHAR', 'Fedex', 'State Code'));
		}
		if(empty($element->shipping_params->country_code)) {
			$app->enqueueMessage(JText::sprintf('ENTER_INFO', 'Fedex', 'Country Code'));
		} else {
			if(strlen($element->shipping_params->country_code) > 2) {
				$app->enqueueMessage(JText::sprintf('FEDEX_CODE_2_CHAR', 'Fedex', 'Country Code'));
			}
			if(empty($element->shipping_params->state_code) && in_array($element->shipping_params->country_code, $country_array)) {
				$app->enqueueMessage(JText::sprintf('STATE_ENTER_INFO', 'Fedex', 'State Code'));
			}
		}

		if(!empty($element->shipping_params->dimensions_approximation) && !ctype_digit($element->shipping_params->dimensions_approximation)) {
			$app->enqueueMessage(JText::sprintf('DIGITS_ONLY', 'Fedex', 'Dimensions Approximation'));
		}
		if(!empty($element->shipping_params->weight_approximation) && !ctype_digit($element->shipping_params->weight_approximation)) {
			$app->enqueueMessage(JText::sprintf('DIGITS_ONLY', 'Fedex', 'Weight Approximation'));
		}
		parent::onShippingConfiguration($element);
	}

	public function getShippingDefaultValues(&$element){
		$element->shipping_name = 'FEDEX';
		$element->shipping_description = '';
		$element->sort_rate = 'default';
		$element->shipping_images = 'fedex';
		$element->shipping_type = 'fedex 2';
		$element->shipping_params = new stdClass();
		$element->shipping_params->transit_time = 0;
		$config = hikashop_config();
	}
}
