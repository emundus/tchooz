<?php
/**
 * @package       com_emundus
 * @subpackage    api
 * @author        eMundus.fr
 * @copyright (C) 2022 eMundus SOFTWARE. All rights reserved.
 * @license       GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Tchooz\api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');

class Api
{
	/**
	 * @var array $auth
	 */
	protected $auth = array();


	/**
	 * @var array $headers
	 */
	protected $headers = array();

	/**
	 * @var string $baseUrl
	 */
	protected $baseUrl = '';

	/**
	 * @param   GuzzleClient  $client
	 */
	protected $client = null;

	/**
	 * @var bool
	 */
	protected $retry = false;

	/**
	 * @return bool
	 */
	public function getRetry(): int
	{
		return $this->retry;
	}

	/**
	 * @param   bool  $retry
	 */
	public function setRetry($retry): void
	{
		$this->retry = $retry;
	}


	public function __construct($retry = false)
	{
		Log::addLogger(['text_file' => 'com_emundus.api.php'], Log::ALL, 'com_emundus.api');

		$this->setRetry($retry);
	}

	/**
	 * @return string
	 */
	public function getBaseUrl(): string
	{
		return $this->baseUrl;
	}

	public function setBaseUrl($baseUrl): void
	{
		$this->baseUrl = $baseUrl;
	}

	/**
	 * @return null
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * @param   null  $client
	 */
	public function setClient($client = null): void
	{
		if (empty($this->client))
		{
			$this->client = new GuzzleClient([
				'base_uri' => $this->baseUrl,
				'verify'   => false
			]);
		}
		else
		{
			$this->client = $client;
		}
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setHeaders($headers): void
	{
		$this->headers = $headers;
	}

	public function addHeader($key, $value): void
	{
		$this->headers[$key] = $value;
	}

	/**
	 * @return array
	 */
	public function getAuth(): array
	{
		return $this->auth;
	}

	public function setAuth($token): void
	{
		$this->auth['bearer_token'] = $token;
	}


	public function get(string $url, array $params = [], array $headers = [])
	{
		$response = ['status' => 200, 'message' => '', 'data' => ''];

		try
		{
			$url_params = http_build_query($params);
			$url        = !empty($url_params) ? $url . '?' . $url_params : $url;

			if (!empty($headers))
			{
				$headers = array_merge($this->getHeaders(), $headers);
			}
			else
			{
				$headers = $this->getHeaders();
			}

			$request = $this->client->get($this->baseUrl . '/' . $url, ['headers' => $headers]);

			$response['status'] = $request->getStatusCode();

			$contentType = $request->getHeaderLine('Content-Type');

			if (strpos($contentType, 'application/json') !== false)
			{
				$response['data'] = json_decode($request->getBody());
			}
			else
			{
				$response['data']         = $request->getBody()->getContents();
				$response['headers']      = $request->getHeaders();
				$response['content_type'] = $contentType;
				$response['is_file']      = true;
			}
		}
		catch (ClientException $e)
		{
			if ($this->getRetry())
			{
				$this->setRetry(false);
				$this->get($url, $params);
			}

			Log::add('[GET] ' . $e->getMessage(), Log::ERROR, 'com_emundus.api');

			$response['status']  = $e->getResponse()->getStatusCode();
			$response['message'] = $e->getResponse()->getReasonPhrase();
			$response['headers'] = $e->getResponse()->getHeaders();
			$response['error_details'] = $e->getResponse()->getBody()->getContents();
		}

		return $response;
	}

	public function post($url, $body = null, $headers = array(), $asMultipart = false)
	{
		$response = ['status' => 200, 'message' => '', 'data' => ''];

		try
		{
			$params            = array();
			$params['headers'] = $this->getHeaders();

			if (is_array($body) && !$asMultipart)
			{
				$params['form_params'] = $body;
			}
			elseif ($asMultipart)
			{
				$params['multipart'] = $body;
			}
			elseif (!empty($body))
			{
				$params['body'] = $body;
			}

			if (!empty($headers))
			{
				$params['headers'] = array_merge($params['headers'], $headers);
			}
			elseif (!$asMultipart)
			{
				$params['headers']['Content-Type'] = 'application/json';
				$params['headers']['Accept']       = 'application/json';

				if (is_array($body))
				{
					$params['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
				}
			}

			if (strpos($url, 'https') !== false)
			{
				$request = $this->client->post($url, $params);
			}
			else
			{
				if ($asMultipart)
				{
					unset($params['headers']['Content-Type']);
				}

				$request = $this->client->post($this->baseUrl . '/' . $url, $params);
			}

			$response['status'] = $request->getStatusCode();
			$response['data']   = json_decode($request->getBody());
		}
		catch (ClientException $e)
		{
			if ($this->getRetry())
			{
				$this->setRetry(false);
				$this->post($url, $body, $headers, $asMultipart);
			}

			Log::add('[POST] ' . $e->getMessage(), Log::ERROR, 'com_emundus.api');

			$response['status']  = $e->getResponse()->getStatusCode();
			$response['message'] = $e->getResponse()->getReasonPhrase();
			$response['headers'] = $e->getResponse()->getHeaders();
			$response['error']   = $e->getResponse()->getBody()->getContents();
		}

		return $response;
	}


	public function patch($url, $body = null)
	{
		$response = ['status' => 200, 'message' => '', 'data' => ''];

		try
		{
			$params            = array();
			$params['headers'] = $this->getHeaders();
			if (is_array($body))
			{
				$params['form_params'] = $body;
			}
			else
			{
				if (!empty($body))
				{
					$params['body']                    = $body;
					$params['headers']['Content-Type'] = 'application/json';
					$params['headers']['Accept']       = 'application/json';
				}
			}

			$request = $this->client->patch($this->baseUrl . '/' . $url, $params);

			$response['status'] = $request->getStatusCode();
			$response['data']   = json_decode($request->getBody());
		}
		catch (ClientException $e)
		{
			if ($this->getRetry())
			{
				$this->setRetry(false);
				$this->patch($url, $body);
			}

			Log::add('[PATCH] ' . $e->getMessage(), Log::ERROR, 'com_emundus.api');

			$response['status']  = $e->getResponse()->getStatusCode();
			$response['message'] = $e->getResponse()->getReasonPhrase();
			$response['headers'] = $e->getResponse()->getHeaders();
		}

		return $response;
	}

	public function delete($url, $params = array())
	{
		$response = ['status' => 200, 'message' => '', 'data' => ''];

		try
		{
			$url_params = http_build_query($params);
			$url        = !empty($url_params) ? $url . '?' . $url_params : $url;

			$request            = $this->client->delete($this->baseUrl . '/' . $url, ['headers' => $this->getHeaders()]);
			$response['status'] = $request->getStatusCode();
			$response['data']   = json_decode($request->getBody());
		}
		catch (ClientException $e)
		{

			if ($this->getRetry())
			{
				$this->setRetry(false);
				$this->delete($url);
			}

			Log::add('[DELETE] ' . $e->getMessage(), Log::ERROR, 'com_emundus.api');

			$response['status']  = $e->getResponse()->getStatusCode();
			$response['message'] = $e->getResponse()->getReasonPhrase();
			$response['headers'] = $e->getResponse()->getHeaders();
		}

		return $response;
	}
}