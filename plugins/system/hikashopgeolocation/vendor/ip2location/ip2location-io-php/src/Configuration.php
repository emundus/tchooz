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

class Configuration
{
	const VERSION = '1.0.0';

	public $apiKey = '';

	public function __construct($key)
	{
		$this->apiKey = $key;
	}

	public function reset()
	{
		$apiKey = '';
	}

	public function apiKey($value = null)
	{
		if (empty($value)) {
			$this->getApiKey();
		}
		$this->setApiKey($value);
	}

	public function getApiKey()
	{
		return $this->apiKey;
	}

	private function setApiKey($value)
	{
		if (empty($value)) {
			throw new \RuntimeException('No API key is provided');
		}

		if (!is_string($value)) {
			throw new \RuntimeException('The API key must be a string');
		}

		if (!preg_match('/^[A-Z0-9]{32}$/', $value)) {
			throw new \RuntimeException('The API key is invalid');
		}

		$this->apiKey = $value;
	}
}

class_alias('IP2LocationIO\Configuration', 'IP2LocationIO_Configuration');
