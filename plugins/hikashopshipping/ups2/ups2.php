<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.4
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class plgHikashopshippingUps2 extends hikashopShippingPlugin
{

	var $pluginConfig = array(
		'environment' => array('ENVIRONMENT', 'list', array(
				'production' =>'Production',
				'test' => 'Customer Integration Environment (tests)',
			),
		),
		'client_id' => array('PAYPAL_CHECKOUT_CLIENT_ID', 'input'),
		'client_secret' => array('PAYPAL_CHECKOUT_CLIENT_SECRET', 'input'),
		'shipper_number' => array('SHIPPER_NUMBER', 'input'),
		'name' => array('HIKA_NAME', 'input'),
		'address' => array('ADDRESS_LINE', 'input'),
		'city' => array('CITY', 'input'),
		'post_code' => array('POST_CODE', 'input'),
		'state_code' => array('STATE_CODE', 'input'),
		'country_code' => array('COUNTRY_CODE', 'input'),
		'pickup_type' => array('PICKUP_TYPE', 'list', array(
				'01' =>'Daily Pickup',
				'03' =>'Customer Counter',
				'06' =>'One Time Pickup',
				'07' =>'On Call Air',
				'19' =>'Letter Center',
				'20' =>'Air Service Center',
			),
		),
		'services' => array(
			'SHIPPING_SERVICES',
			'checkbox',
			array(
			),
		),
		'weight_unit' => array('WEIGHT_UNIT', 'list', array(
				'kg' =>'KG',
				'lb' =>'LB',
			),
		),
		'negotiated_rate' => array('NEGOTIATED_RATE', 'boolean', 0),
		'saturday_shipping' => array('SATURDAY_SHIPPING', 'boolean', 0),
		'group_package' => array('GROUP_PACKAGE', 'boolean', 1),
		'show_eta' => array('FEDEX_SHOW_ETA', 'boolean', 1),
		'include_price' => array('INCLUDE_PRICE', 'boolean', 0),
		'exclude_dimensions' => array('EXCLUDE_DIMENSIONS', 'boolean', 1),
		'weight_approximation' => array('UPS_WEIGHT_APPROXIMATION', 'input'),
		'dim_approximation' => array('DIMENSION_APPROXIMATION', 'input'),
		'debug' => array('DEBUG', 'boolean', 0),
	);

	var $ups_methods = array(
		array('key' => 1, 'code' => '01', 'name' => 'UPS Next Day Air', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172') , 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key' => 2, 'code' => '02', 'name' => 'UPS Second Day Air', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key' => 3, 'code' => '03', 'name' => 'UPS Ground', 'countries' => 'USA, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key' => 4, 'code' => '07', 'name' => 'UPS Worldwide Express', 'countries' => 'USA, PUERTO RICO, CANADA', 'zones' => array('country_United_States_of_America_223', 'country_Puerto_Rico_172', 'country_Canada_38'), 'destinations' => array('country_United_States_of_America_223', 'country_Puerto_Rico_172', 'country_Canada_38', 'international')),
		array('key' => 5, 'code' => '08', 'name' => 'UPS Worldwide Expedited', 'countries' => 'USA, PUERTO RICO, CANADA' , 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172', 'country_Canada_38'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172', 'country_Canada_38', 'international')),
		array('key' => 6, 'code' => '11', 'name' => 'UPS Standard', 'countries' => 'USA, CANADA, POLAND, EUROPEAN UNION, OTHER', 'zones' => array('country_United_States_of_America_223', 'country_Canada_38', 'country_Poland_170', 'tax_europe_9728', 'other'), 'destinations' => array('country_United_States_of_America_223', 'country_Canada_38', 'country_Poland_170', 'tax_europe_9728', 'other')),
		array('key' => 7, 'code' => '12', 'name' => 'UPS Three-Day Select', 'countries' => 'USA, CANADA', 'zones' => array('country_United_States_of_America_223', 'country_Canada_38'), 'destinations' => array('country_United_States_of_America_223', 'country_Canada_38')),
		array('key' => 8, 'code' => '13', 'name' => 'UPS Next Day Air Saver', 'countries' => 'USA', 'zones' => array('country_United_States_of_America_223'), 'destinations' => array('country_United_States_of_America_223')),
		array('key' => 9, 'code' => '14', 'name' => 'UPS Next Day Air Early A.M.', 'countries' => 'USA, PUERTO RICO' , 'zones' => array('country_United_States_of_America_223','country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Puerto_Rico_172')),
		array('key' => 10, 'code' => '54', 'name' => 'UPS Worldwide Express Plus', 'countries' => 'USA, CANADA, POLAND, EUROPEAN UNION, OTHER, PUERTO RICO', 'zones' => array('country_United_States_of_America_223','country_Canada_38', 'country_Poland_170', 'tax_europe_9728', 'other', 'country_Puerto_Rico_172'), 'destinations' => array('country_United_States_of_America_223','country_Canada_38', 'country_Poland_170', 'tax_europe_9728', 'other', 'country_Puerto_Rico_172', 'international')),
		array('key' => 11, 'code' => '59', 'name' => 'UPS Second Day Air A.M.', 'countries' => 'USA', 'zones' => array('country_United_States_of_America_223'), 'destinations' => array('country_United_States_of_America_223')),
		array('key' => 12, 'code' => '65', 'name' => 'UPS Saver', 'countries' => 'USA, PUERTO RICO, CANADA, MEXICO, POLAND, EUROPEAN UNION, OTHER', 'zones' => array('country_United_States_of_America_223', 'country_Puerto_Rico_172', 'country_Canada_38', 'country_Mexico_138', 'country_Poland_170', 'tax_europe_9728', 'other'), 'destinations' => array('country_United_States_of_America_223', 'country_Puerto_Rico_172', 'country_Canada_38', 'country_Mexico_138', 'country_Poland_170', 'tax_europe_9728', 'other')),

		array('key' => 13, 'code' => '01', 'double' => true, 'name' => 'UPS Express CA', 'countries' => 'CANADA', 'zones' => array('country_Canada_38'), 'destinations' => array('country_Canada_38')),
		array('key' => 14, 'code' => '02', 'double' => true, 'name' => 'UPS Expedited CA', 'countries' => 'CANADA', 'zones' => array('country_Canada_38'), 'destinations' => array('country_Canada_38')),
		array('key' => 15, 'code' => '13', 'double' => true, 'name' => 'UPS Saver CA', 'countries' => 'CANADA', 'zones' => array('country_Canada_38'), 'destinations' => array('country_Canada_38')),
		array('key' => 16, 'code' => '14', 'double' => true, 'name' => 'UPS Express Early A.M', 'countries' => 'CANADA', 'zones' => array('country_Canada_38'), 'destinations' => array('country_Canada_38')),

		array('key' => 17, 'code' => '07', 'name' => 'UPS Express', 'countries' => 'MEXICO, POLAND, EUROPEAN UNION, OTHER', 'zones' => array('country_Mexico_138', 'country_Poland_170','tax_europe_9728', 'other'), 'destinations' => array('country_Mexico_138', 'country_Poland_170','tax_europe_9728', 'other')),
		array('key' => 18, 'code' => '08', 'name' => 'UPS Expedited', 'countries' => 'MEXICO, POLAND, EUROPEAN UNION, OTHER', 'zones' => array('country_Mexico_138', 'country_Poland_170','tax_europe_9728', 'other'), 'destinations' => array('country_Mexico_138', 'country_Poland_170','tax_europe_9728', 'other')),
		array('key' => 19, 'code' => '54', 'name' => 'UPS Express Plus', 'countries' => 'MEXICO', 'zones' => array('country_Mexico_138'), 'destinations' => array('country_Mexico_138')),

		array('key' => 20, 'code' => '82', 'name' => 'UPS Today Standard', 'countries' => 'POLAND', 'zones' => array('country_Poland_170'), 'destinations' => array('country_Poland_170')),
		array('key' => 21, 'code' => '83', 'name' => 'UPS Today Dedicated Courrier', 'countries' => 'POLAND', 'zones' => array('country_Poland_170'), 'destinations' => array('country_Poland_170')),
		array('key' => 22, 'code' => '84', 'name' => 'UPS Today Intercity', 'countries' => 'POLAND', 'zones' => array('country_Poland_170'), 'destinations' => array('country_Poland_170')),
		array('key' => 23, 'code' => '85', 'name' => 'UPS Today Express', 'countries' => 'POLAND', 'zones' => array('country_Poland_170'), 'destinations' => array('country_Poland_170')),
		array('key' => 24, 'code' => '86', 'name' => 'UPS Today Express Saver', 'countries' => 'POLAND', 'zones' => array('country_Poland_170'), 'destinations' => array('country_Poland_170'))
	);

	var $multiple = true;
	var $name = 'ups2';
	var $doc_form = 'ups2';
	var $use_cache = true;
	var $version = 'v2205';

	public $nbpackage = 0;


	function __construct(&$subject, $config) {
		$all_services = array();
		foreach($this->ups_methods as $method) {
			$varName = strtolower($method['name']);
			$varName = str_replace(' ','_', $varName);
			$all_services[] = $varName;
			$this->pluginConfig['services'][2][$varName] = $method['name'].' ('.$method['countries'].')';
		}
		$this->all_services = implode(',', $all_services);

		return parent::__construct($subject, $config);
	}

	public function processPackageLimit($limit_key, $limit_value, $product, $qty, $package, $units) {
		switch ($limit_key) {
			case 'dimension':
				$divide = (float)(($product['x'] + $product['y']) * 2 + $product['z']);
				if(empty($divide) || $divide > $limit_value)
					return false;
				$current_limit_value = max(0.0, $limit_value - (float)(($package['x'] + $package['y']) * 2 + $package['z']));
				return (int)floor($current_limit_value / $divide);
				break;
		}
		return parent::processPackageLimit($limit_key, $limit_value , $product, $qty, $package, $units);
	}

	public function shippingMethods(&$main) {
		$methods = array();
		if(!empty($main->shipping_params->services)) {
			foreach($main->shipping_params->services as $method) {
				$selected = null;
				foreach($this->ups_methods as $ups) {

					$varName = strtolower($ups['name']);
					$varName = str_replace(' ','_', $varName);
					if($varName == $method) {
						$selected = $ups;
						break;
					}
				}
				if($selected)
					$methods[$main->shipping_id .'-'. $selected['key']] = $selected['name'];
			}
		}
		return $methods;
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
			$app->enqueueMessage('The UPS 2 shipping plugin needs the CURL library installed but it seems that it is not available on your server. Please contact your web hosting to set it up.','error');
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
				$cache_messages['no_shipping_methods_configured'] = 'No shipping methods configured in the UPS shipping plugin options';
				continue;
			}

			if($order->weight <= 0 || ($order->volume <= 0 && @$rate->shipping_params->exclude_dimensions != 1)) {
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

			if(empty($receivedMethods)) {
				$this->error_messages['no_rates'] = JText::_('NO_SHIPPING_METHOD_FOUND');
				continue;
			}

			$i = 0;
			$new_usable_rates = array();
			foreach($receivedMethods as $method) {
				$new_usable_rates[$i] = clone($rate);
				$new_usable_rates[$i]->shipping_price += round($method['value'], 2);
				$selected_method = '';
				$name = '';
				$description = '';

				foreach($this->ups_methods as $ups_method) {
					if($ups_method['code'] == $method['code'] && ($method['old_currency_code'] == 'CAD' || !isset($ups_method['double']))) {
						$selected_method = $ups_method['key'];

						$typeKey = str_replace(' ','_', strtoupper($ups_method['name']));
						$shipping_name = JText::_($typeKey);

						if($shipping_name != $typeKey)
							$name = $shipping_name;
						else
							$name = $ups_method['name'];

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

				if(isset($rate->shipping_params->show_eta) && $rate->shipping_params->show_eta) {
					if(!empty($method['delivery_day']) && $method['delivery_day'] != -1)
						$new_usable_rates[$i]->shipping_description .= ' '.JText::sprintf('SHIPPING_DELIVERY_DELAY', $method['delivery_day']);
					else
						$new_usable_rates[$i]->shipping_description .= ' '.JText::_('NO_ESTIMATED_TIME_AFTER_SEND');

					if(!empty($method['delivery_time']) && $method['delivery_time'] != -1)
						$new_usable_rates[$i]->shipping_description .= '<br/>'.JText::sprintf('DELIVERY_HOUR', $method['delivery_time']);
					else
						$new_usable_rates[$i]->shipping_description .= '<br/>'.JText::_('NO_DELIVERY_HOUR');
				}

				if($rate->shipping_params->group_package && $this->nbpackage > 1)
					$new_usable_rates[$i]->shipping_description .= '<br/>'.JText::sprintf('X_PACKAGES', $this->nbpackage);

				$i++;
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

		$this->setShippingCache($order, $cache_usable_rates, $cache_messages);

		if(!empty($cache_messages)) {
			foreach($cache_messages as $k => $msg) {
				$messages[$k] = $msg;
			}
		}
	}

	public function getShippingDefaultValues(&$element){
		$element->shipping_name = 'UPS';
		$element->shipping_description = '';
		$element->group_package = 1;
		$element->shipping_images = 'ups';
		$element->shipping_type = 'ups2';
		$element->shipping_params->post_code = '';
		$config = hikashop_config();
		$element->shipping_currency_id = (int)$config->get('main_currency');
		$element->shipping_params->pickup_type = '01';
		$element->shipping_params->services = $this->all_services;
	}

	protected function _getBestMethods(&$rate, &$order, $null) {
		$db = JFactory::getDBO();
		$usableMethods = array();
		$zone_code = '';


		$methods = $this->_getShippingMethods($rate, $order, $null);
		if(empty($methods))
			return false;

		if($methods !== true) {
			foreach($methods as $i => $method) {
				$found = false;
				foreach($this->ups_methods as $availableMethod) {
					if($availableMethod['code'] == $method['code']) {
						$varName = strtolower($availableMethod['name']);
						$varName = str_replace(' ','_', $varName);
						if(in_array($varName, $rate->shipping_params->services))
							$found = true;
					}
				}

				if(!$found)
					unset($methods[$i]);
			}
		}

		return $methods;
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

		$exclude_dimensions = false;
		if(@$rate->shipping_params->exclude_dimensions == 1)
			$exclude_dimensions = true;

		$data['pickup_type'] = $rate->shipping_params->pickup_type;

		$limitations = array();

		$limitations['dimension'] = 165;
		$limitations['w'] = 150;

		if(empty($rate->shipping_params->group_package))
			$limitations['unit'] = 1;

		$weight_unit = 'lb';
		$volume_unit = 'in';
		if($rate->shipping_params->weight_unit == 'kg') {
			$weight_unit = 'kg';
			if(isset($limitations['w']))
				$limitations['w'] = 68.04;

			$volume_unit = 'cm';
			if(isset($limitations['dimension']))
				$limitations['dimension'] = 419.1;
		}

		if($exclude_dimensions) {
			unset($limitations['dimension']);
			$packages = $this->getOrderPackage($order, array('weight_unit' => $weight_unit, 'volume_unit' => $volume_unit, 'limit' => $limitations, 'required_dimensions' => array('w')));
		} else {
			$packages = $this->getOrderPackage($order, array('weight_unit' => $weight_unit, 'volume_unit' => $volume_unit, 'limit' => $limitations, 'required_dimensions' => array('w','x','y','z')));
		}

		if(empty($packages))
			return true;

		$this->nbpackage = 0;
		$price = 0;
		if(isset($order->total->prices[0]->price_value))
			$price = $order->total->prices[0]->price_value;

		$packagesToSend = array();
		if(isset($packages['w']) && isset($packages['x']) && isset($packages['y']) && isset($packages['z'])) {
			$this->nbpackage++;
			$data['weight_unit'] = ($weight_unit == 'lb' ? 'LBS' : 'KGS');
			$data['dimension_unit'] = ($volume_unit == 'in' ? 'IN' : 'CM');
			$data['weight'] = $packages['w'];
			$data['height'] = $packages['z'];
			$data['length'] = $packages['y'];
			$data['width'] = $packages['x'];
			if(!empty($packages['price']))
				$data['price'] = $packages['price'];
			else
				$data['price'] = $price;
			$data['quantity'] = 1;

			$packagesToSend[] = $this->_createPackage($data, $rate, $order, true);
		} else {
			foreach($packages as $package) {
				if(!isset($package['w']) || $package['w'] == 0)
					continue;
				if(!$exclude_dimensions) {
					if(!isset($package['x']) || $package['x'] == 0 || !isset($package['y']) || $package['y'] == 0 || !isset($package['z']) || $package['z'] == 0)
						continue;
				}
				$this->nbpackage++;
				$data['weight_unit'] = ($weight_unit == 'lb' ? 'LBS' : 'KGS');
				$data['dimension_unit'] = ($volume_unit == 'in' ? 'IN' : 'CM');
				$data['weight'] = $package['w'];
				$data['height'] = $package['z'];
				$data['length'] = $package['y'];
				$data['width'] = $package['x'];
				if(!empty($package['price']))
					$data['price'] = $package['price'];
				else
					$data['price'] = $price;
				$data['quantity'] = 1;

				$packagesToSend[] = $this->_createPackage($data, $rate, $order, true);
			}
		}
		$usableMethods = $this->_UPSrequestMethods($packagesToSend, $rate, $null, $price, $data['currency_code']);

		if(empty($usableMethods))
			return false;

		$currencies = array();
		foreach($usableMethods as $method){
			$currencies[$method['currency_code']] = '"'. $method['currency_code'] .'"';
		}
		$db = JFactory::getDBO();
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

	protected function _createPackage(&$data, &$rate, &$order, $includeDimension=false) {
		if(@$rate->shipping_params->exclude_dimensions == 1){
			$includeDimension = false;
		}

		$currencyClass = hikashop_get('class.currency');
		$config =& hikashop_config();
		$this->main_currency = $config->get('main_currency',1);
		$currency = hikashop_getCurrency();

		if(isset($data['price']))
			$price = $data['price'];
		else
			$price = 0;

		if($this->shipping_currency_id != $data['currency'] && $price > 0)
			$price = $currencyClass->convertUniquePrice($price, $this->shipping_currency_id,$data['currency']);

		if(!empty($rate->shipping_params->weight_approximation))
			$data['weight'] = $data['weight'] + $data['weight'] * $rate->shipping_params->weight_approximation / 100;

		if($data['weight'] < 0.1)
			$data['weight'] = 0.1;

		if(!empty($rate->shipping_params->dim_approximation)) {
			$data['height'] = $data['height'] + $data['height'] * $rate->shipping_params->dim_approximation / 100;
			$data['length'] = $data['length'] + $data['length'] * $rate->shipping_params->dim_approximation / 100;
			$data['width'] = $data['width'] + $data['width'] * $rate->shipping_params->dim_approximation / 100;
		}

		$desc = 'Kilograms';
		if($data['weight_unit'] == 'LBS')
			$desc = 'Pounds';
		$package = array(
			"PackagingType" => array(
				"Code" => "02",
				"Description" => "Packaging"
			),
			"PackageWeight" => array(
				"UnitOfMeasurement" => array(
					"Code" => $data['weight_unit'],
					"Description" => $desc
				),
				"Weight" => (string)round($data['weight'],2)
			)
		);

		if($includeDimension) {
			$desc = 'Centimeters';
			if($data['dimension_unit'] == 'IN')
				$desc = 'Inches';
			$package['Dimensions'] = array(
				"UnitOfMeasurement" => array(
					"Code" => $data['dimension_unit'],
					"Description" => $desc
				),
				"Length" => (string)round($data['length'],2) ,
				"Width" => (string)round($data['width'], 2),
				"Height" => (string)round($data['height'], 2)
			);
		}
		if($rate->shipping_params->include_price) {
			$package['PackageServiceOptions'] = array(
				'DeclaredValue' => array(
					'CurrencyCode' => $data['currency_code'],
					'MonetaryValue' => (string)round($price, 2),
				)
			);
		}

		return $package;
	}

	protected function _UPSrequestMethods($packagesToSend, &$rate, &$address, $price, $currency_code) {
		$srcCountry = 'US';
		if(!empty($rate->shipping_params->country_code))
			$srcCountry = $rate->shipping_params->country_code;
		$dstCountry = $srcCountry;
		if(!empty($address->shipping_address->address_country->zone_code_2))
			$dstCountry = $address->shipping_address->address_country->zone_code_2;

		$dstName = array();
		if(!empty($address->shipping_address->address_firstname))
			$dstName[] = $address->shipping_address->address_firstname;
		if(!empty($address->shipping_address->address_middle_name))
			$dstName[] = $address->shipping_address->address_middle_name;
		if(!empty($address->shipping_address->address_lastname))
			$dstName[] = $address->shipping_address->address_lastname;
		$dstName = implode(' ', $dstName);
		$attentionName = false;
		if(!empty($address->shipping_address->address_company)) {
			$attentionName = $dstName;
			$dstName = $address->shipping_address->address_company;
		}

		$dstAddressLine = array((string)$address->shipping_address->address_street);
		if(!empty($address->shipping_address->address_street2)) {
			$dstAddressLine[] = $address->shipping_address->address_street2;
		}

		$ShipmentTotalWeight = array(
			'Weight' => 0
		);
		foreach($packagesToSend as $package) {
			$ShipmentTotalWeight['Weight'] += $package['PackageWeight']['Weight'];
			if(empty($ShipmentTotalWeight['UnitOfMeasurement'])) {
				$ShipmentTotalWeight['UnitOfMeasurement'] = $package['PackageWeight']['UnitOfMeasurement'];
			}
		}

		$ShipmentTotalWeight['Weight'] = "1.0";

		$transId = '';
		for ($i=0; $i<30; $i++)
			$transId.= rand(0, 9);

		$payload = array(
			"RateRequest" => array(
				"Request" => array(
					"TransactionReference" => array(
						"CustomerContext" => "Verify Success response",
						"TransactionIdentifier" => $transId
					)
				),
				"Shipment" => array(
					"Shipper" => array(
						"Name" => $rate->shipping_params->name,
						"ShipperNumber" => $rate->shipping_params->shipper_number,
						"Address" => array(
							"AddressLine" => array(
								(string)$rate->shipping_params->address
							),
							"City" => (string)$rate->shipping_params->city,
							"StateProvinceCode" => (string)$rate->shipping_params->state_code,
							"PostalCode" => (string)$rate->shipping_params->post_code,
							"CountryCode" => (string)$srcCountry
						)
					),
					"ShipTo" => array(
						"Name" => $dstName,
						"Address" => array(
							"AddressLine" => $dstAddressLine,
							"City" => (string)@$address->shipping_address->address_city,
							"StateProvinceCode" => (string)@$address->shipping_address->address_state->zone_code_3,
							"PostalCode" => (string)@$address->shipping_address->address_post_code,
							"CountryCode" => (string)$dstCountry
						)
					),
					"ShipFrom" => array(
						"Name" => $rate->shipping_params->name,
						"Address" => array(
							"AddressLine" => array(
								$rate->shipping_params->address
							),
							"City" => $rate->shipping_params->city,
							"StateProvinceCode" => $rate->shipping_params->state_code,
							"PostalCode" => $rate->shipping_params->post_code,
							"CountryCode" => $srcCountry
						)
					),
					"PaymentDetails" => array(
						"ShipmentCharge" => array(
							"Type" => "01",
							"BillShipper" => array(
								"AccountNumber" => $rate->shipping_params->shipper_number
							)
						)
					),
					"DeliveryTimeInformation" => array(
						"PackageBillType" => '03',
						"Pickup" => array(
							'Date' => date("Ymd", time()+3600), // add an hour just in case we're close to the end of the day
						)
					),
					"ShipmentTotalWeight" => $ShipmentTotalWeight,
					"Package" => $packagesToSend,
				)
			)
		);
		if(!empty($attentionName)) {
			$payload['RateRequest']['Shipment']['ShipTo']['attentionName'] = $attentionName;
		}

		if($dstCountry != $srcCountry) {
			$payload['RateRequest']['Shipment']['InvoiceLineTotal'] = array(
				'CurrencyCode' => $currency_code,
				'MonetaryValue' => (string)round($price, 2),
			);
		}

		if(!empty($rate->shipping_params->negotiated_rate)) {
			$payload['RateRequest']['Shipment']['ShipmentRatingOptions'] = array(
				'TPFCNegotiatedRatesIndicator' => 'Y',
				'NegotiatedRatesIndicator' => 'Y',
			);
		}

		$domain = 'onlinetools.ups.com';
		if(!empty($rate->shipping_params->environment) && $rate->shipping_params->environment == 'test')
			$domain = 'wwwcie.ups.com';

		$token = $this->_getAccessToken($rate);
		if(empty($token)) {
			return false;
		}

		$curl = curl_init();
		$query = array(
			"additionalinfo" => "timeintransit"
		);
		$options = [
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer ".$token,
				"Content-Type: application/json",
				"transId: ".$transId,
				"transactionSrc: HikaShop"
			],
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_URL => "https://".$domain."/api/rating/" . $this->version . "/Shop?" . http_build_query($query),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_CAINFO => __DIR__.'/cacert.pem',
			CURLOPT_CAPATH => __DIR__.'/cacert.pem',
		];
		curl_setopt_array($curl, $options);

		$result = curl_exec($curl);
		$error = curl_error($curl);
		$error_msg = curl_error($curl);

		curl_close($curl);

		if($error || empty($result)) {
			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog($options);
				hikashop_writeToLog($result);
				hikashop_writeToLog($error_msg);
			}
			$app = JFactory::getApplication();
			if(!empty($error_msg))
				$error_msg = ' : '. $error_msg;
			$app->enqueueMessage('An error occurred. The connection to the UPS server could not be established. '. $error_msg);
			return false;
		}

		$response = json_decode($result, true);
		if(!$response) {
			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog($options);
				hikashop_writeToLog($result);
			}
			$app = JFactory::getApplication();
			$app->enqueueMessage('An error occurred. The connection to the UPS server could not be established. Invalid json response');
			return false;
		}

		if(!isset($response['RateResponse']['Response']['ResponseStatus']['Code'])) {
			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog($options);
				hikashop_writeToLog($result);
			}
			$app = JFactory::getApplication();
			$app->enqueueMessage('An error occurred. The connection to the UPS server could not be established.');
			if(!empty($response['response']['errors'])) {
				foreach($response['response']['errors'] as $error) {
					$app->enqueueMessage($error['code'] . ' ' .$error['message']);
					$this->error_messages['ups_error'] = 'An error occurred. The connection to the UPS server could not be established. [' . $error['code'] . '] ' .$error['message'];
				}
			} else {
				$app->enqueueMessage('No response status');
			}
			return false;
		}

		if($response['RateResponse']['Response']['ResponseStatus']['Code'] != '1') {
			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog($options);
				hikashop_writeToLog($result);
			}
			$this->error_messages['ups_error'] = $response['RateResponse']['Response']['ResponseStatus']['Code'] . ' ' . $response['RateResponse']['Response']['ResponseStatus']['Description'];
			return false;
		}

		$shipment = array();
		$i = 1;
		foreach($response['RateResponse']['RatedShipment'] as $ups_rate) {
			if(@$rate->shipping_params->negotiated_rate && isset($ups_rate['NegotiatedRateCharges']['TotalCharge']['MonetaryValue'])) {
				$shipment[$i]['value'] = (string)$ups_rate['NegotiatedRateCharges']['TotalCharge']['MonetaryValue'];
				$shipment[$i]['currency_code'] = (string)$ups_rate['NegotiatedRateCharges']['TotalCharge']['CurrencyCode'];
				$shipment[$i]['old_currency_code'] = (string)$ups_rate['NegotiatedRateCharges']['TotalCharge']['CurrencyCode'];
			} else {
				$shipment[$i]['value'] = (string) $ups_rate['TotalCharges']['MonetaryValue'];
				$shipment[$i]['currency_code'] = (string)$ups_rate['TotalCharges']['CurrencyCode'];
				$shipment[$i]['old_currency_code'] = (string)$ups_rate['TotalCharges']['CurrencyCode'];
			}
			$shipment[$i]['code'] = (string)$ups_rate['Service']['Code'];
			if(!empty($ups_rate['TimeInTransit']['ServiceSummary']['EstimatedArrival']['Arrival']['Date'])) {
				$date = (string)$ups_rate['TimeInTransit']['ServiceSummary']['EstimatedArrival']['Arrival']['Date'];
				$shipment[$i]['delivery_day'] = substr($date, 0, 4).'/'.substr($date, 4, 2).'/'.substr($date, 6, 2);
				$time = (string)$ups_rate['TimeInTransit']['ServiceSummary']['EstimatedArrival']['Arrival']['Time'];
				$shipment[$i]['delivery_time'] = substr($time, 0, 2).':'.substr($time, 2, 2);
			}
			$i++;
		}
		if(!empty($rate->shipping_params->debug)) {
			hikashop_writeToLog($options);
			hikashop_writeToLog($shipment);
		}
		return $shipment;
	}

	function _getAccessToken(&$rate) {
		if(!empty($_SESSION['UPS_ACCESS_TOKEN'])) {
			if(!empty($_SESSION['UPS_ACCESS_TOKEN_EXPIRE'])) {
				if($_SESSION['UPS_ACCESS_TOKEN_EXPIRE'] > time())
					return $_SESSION['UPS_ACCESS_TOKEN'];
			} else {
				return $_SESSION['UPS_ACCESS_TOKEN'];
			}
		}
		$domain = 'onlinetools.ups.com';
		if(!empty($rate->shipping_params->environment) && $rate->shipping_params->environment == 'test')
			$domain = 'wwwcie.ups.com';
		$curl = curl_init();
		$credentials = base64_encode($rate->shipping_params->client_id.':'.$rate->shipping_params->client_secret);
		$options = [
			CURLOPT_HTTPHEADER => [
				"Authorization: Basic ".$credentials,
				'accept: application/json',
				"Content-Type: application/x-www-form-urlencoded",
				"x-merchant-id: ".$rate->shipping_params->client_id
			],
			CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
			CURLOPT_URL => "https://".$domain."/security/v1/oauth/token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_CAINFO => __DIR__.'/cacert.pem',
			CURLOPT_CAPATH => __DIR__.'/cacert.pem',
		];
		curl_setopt_array($curl, $options);

		$result = curl_exec($curl);
		$error = curl_error($curl);
		$error_msg = curl_error($curl);

		curl_close($curl);
		if($error || empty($result)) {
			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog($options);
				hikashop_writeToLog($result);
				hikashop_writeToLog($error_msg);
			}
			$app = JFactory::getApplication();
			if(!empty($error_msg))
				$error_msg = ' : '. $error_msg;
			$app->enqueueMessage('An error occurred. The connection to the UPS server could not be established. '. $error_msg);
			return false;
		}

		$response = json_decode($result, true);
		if(!$response) {
			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog($options);
				hikashop_writeToLog($result);
				hikashop_writeToLog($error_msg);
			}
			$app = JFactory::getApplication();
			$app->enqueueMessage('An error occurred. The connection to the UPS server could not be established. Invalid json in response');
			return false;
		}

		if(!isset($response['access_token'])) {
			if(!empty($rate->shipping_params->debug)) {
				hikashop_writeToLog($options);
				hikashop_writeToLog($result);
				hikashop_writeToLog($error_msg);
			}
			$app = JFactory::getApplication();
			$app->enqueueMessage('An error occurred. The connection to the UPS server could not be established.');
			if(!empty($response['response']['errors'])) {
				foreach($response['response']['errors'] as $error) {
					$app->enqueueMessage($error['code'].' '.$error['message']);
				}
			}

			return false;
		}

		$_SESSION['UPS_ACCESS_TOKEN'] = $response['access_token'];

		if(!empty($response['expires_in'])) {
			$_SESSION['UPS_ACCESS_TOKEN_EXPIRE'] = time() + $response['expires_in'];
		}

		return $_SESSION['UPS_ACCESS_TOKEN'];
	}
}
