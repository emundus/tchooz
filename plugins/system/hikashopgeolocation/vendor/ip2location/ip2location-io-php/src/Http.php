<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php

namespace IP2LocationIO;

class Http
{
	public function __construct()
	{
	}

	public function get($url, $fields = [])
	{
		$query = '';
		foreach($fields as $key=>$value){
			$query .= $key . '=' . rawurlencode($value) . '&';
		}
		$query = rtrim($query, '&');
		$url = $url . $query;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_USERAGENT, 'IP2Location.io PHP SDK ' . Configuration::VERSION);

		$response = curl_exec($ch);

		if (empty($response) || curl_error($ch)) {
			curl_close($ch);

			return false;
		}

		curl_close($ch);

		return $response;
	}
}

class_alias('IP2LocationIO\Http', 'IP2LocationIO_Http');
