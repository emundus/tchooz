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


namespace FedexRest\Authorization;


use FedexRest\Exceptions\MissingAuthCredentialsException;
use FedexRest\Traits\rawable;
use FedexRest\Traits\switchableEnv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Authorize
{
    use switchableEnv, rawable;

    private string $client_id;
    private string $client_secret;

    public function setClientId(string $client_id): Authorize
    {
        $this->client_id = $client_id;
        return $this;
    }

    public function setClientSecret(string $client_secret): Authorize
    {
        $this->client_secret = $client_secret;
        return $this;
    }


    public function authorize()
    {
        $httpClient = new Client(
            array( 
            'curl'   => array( CURLOPT_SSL_VERIFYPEER => false ),
            'verify' => false
          )
        );
        if (isset($this->client_id) && isset($this->client_secret)) {
            try {
                $query = $httpClient->request('POST', $this->getApiUri('/oauth/token'), [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->client_id,
                        'client_secret' => $this->client_secret,
                    ]
                ]);
                if ($query->getStatusCode() === 200) {
                    return ($this->raw === true) ? $query : json_decode($query->getBody()->getContents());
                }
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        } else {
            throw new MissingAuthCredentialsException('Please provide auth credentials');
        }
    }
}
