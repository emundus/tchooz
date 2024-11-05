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


namespace FedexRest\Services;


use FedexRest\Exceptions\MissingAccessTokenException;
use FedexRest\Traits\rawable;
use FedexRest\Traits\switchableEnv;
use GuzzleHttp\Client;


abstract class AbstractRequest implements RequestInterface
{
    use switchableEnv, rawable;

    public string $api_endpoint = '';
    protected string $access_token;
    protected Client $http_client;

    public function __construct()
    {
        $this->api_endpoint = $this->setApiEndpoint();
    }

    public function setAccessToken(string $access_token)
    {
        $this->access_token = $access_token;
        return $this;
    }


    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function setClientSecret($client_secret)
    {
        $this->clientSecret = $client_secret;
        return $this;
    }

    public function request()
    {
        if (empty($this->access_token)) {
            throw new MissingAccessTokenException('Authorization token is missing. Make sure it is included');
        }
        $this->http_client = new Client([
			'headers' => [
				'Authorization' => "Bearer {$this->access_token}",
				'Content-Type' => 'application/json'
			],
			'curl' => [
				CURLOPT_SSL_VERIFYPEER => false
			],
			'verify' => false
		]);
    }
}
