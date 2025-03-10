<?php

namespace IP2LocationIO;

use Exception;

class IPGeolocation extends \Exception
{

	private $iplIOApiKey = '';

	public function __construct($config)
	{
		$this->iplIOApiKey = $config->apiKey;
	}

	public function lookup($ip, $language = '')
	{
		$queries = [
			'key'            => $this->iplIOApiKey,
			'format'         => 'json',
			'ip'             => (isset($ip)) ? $ip : '',
			'lang'           => (isset($language)) ? $language : '',
			'source'         => 'sdk-php-iplio',
			'source_version' => Configuration::VERSION,
		];

		$http = new Http();
		$response = $http->get('https://api.ip2location.io/?', $queries);

		if (($json = json_decode($response)) === null) {
			throw new Exception('IPGeolocation lookup error.', 10005);
		}

		if (isset($json->error)) {
			throw new Exception($json->error->error_message, $json->error->error_code);
		} else {
			return $json;
		}
	}
}

class_alias('IP2LocationIO\IPGeolocation', 'IP2LocationIO_IPGeolocation');
