<?php

/**
 * @package       com_emundus
 * @subpackage    api
 * @author        eMundus.fr
 * @copyright (C) 2022 eMundus SOFTWARE. All rights reserved.
 * @license       GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

use GuzzleHttp\Client as GuzzleClient;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;

class Zoom
{
	/**
	 * @var array $auth
	 */
	private $auth = array();

	/**
	 * @var array $headers
	 */
	private $headers = array();

	/**
	 * @var string $baseUrl
	 */
	private $baseUrl = '';

	/**
	 * @param   GuzzleClient  $client
	 */
	private $client = null;

	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.zoom.php'], Log::ALL, 'com_emundus.zoom');
		$this->setAuth();

		if (empty($this->auth['token'])) {
			throw new Exception('Missing zoom api token. Please check your configuration.');
		}
		else {
			$this->setHeaders();
			$this->setBaseUrl();
			$this->client = new GuzzleClient([
				'base_uri' => $this->getBaseUrl(),
				'headers'  => $this->getHeaders()
			]);
		}
	}

	private function setAuth()
	{
		$config              = ComponentHelper::getParams('com_emundus');
		$this->auth['token'] = $config->get('zoom_jwt', '');
	}

	private function getAuth(): array
	{
		return $this->auth;
	}

	private function setHeaders()
	{
		$auth = $this->getAuth();

		$this->headers = array(
			'Authorization' => 'Bearer ' . $auth['token'],
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json'
		);
	}

	private function getHeaders(): array
	{
		return $this->headers;
	}

	private function setBaseUrl()
	{
		$config        = ComponentHelper::getParams('com_emundus');
		$this->baseUrl = $config->get('zoom_base_url', 'https://api.zoom.us/v2/');
	}

	private function getBaseUrl(): string
	{
		return $this->baseUrl;
	}

	private function get($url, $params = array())
	{
		try {
			$url_params = http_build_query($params);
			$url        = !empty($url_params) ? $url . '?' . $url_params : $url;
			$response   = $this->client->get($url);

			return json_decode($response->getBody());
		}
		catch (\Exception $e) {
			Log::add('[GET] ' . $e->getMessage(), Log::ERROR, 'com_emundus.zoom');

			return $e->getMessage();
		}
	}

	private function post($url, $json = null)
	{
		$response = '';

		try {
			if ($json !== null) {
				$response = $this->client->post($url, ['body' => $json]);
			}
			else {
				$response = $this->client->post($url);
			}
			$response = json_decode($response->getBody());
		}
		catch (\Exception $e) {
			Log::add('[POST] ' . $e->getMessage(), Log::ERROR, 'com_emundus.zoom');
			$response = $e->getMessage();
		}

		return $response;
	}

	private function patch($url, $json)
	{
		$response = '';

		try {
			$response = $this->client->patch($url, ['body' => $json]);
			$response = json_decode($response->getBody());
		}
		catch (\Exception $e) {
			Log::add('[PATCH] ' . $e->getMessage(), Log::ERROR, 'com_emundus.zoom');
			$response = $e->getMessage();
		}

		return $response;
	}

	public function getMeeting($meeting_id)
	{
		$meeting = null;

		if (!empty($meeting_id)) {
			$meeting = $this->get('meetings/' . $meeting_id);
		}

		return $meeting;
	}

	public function createMeeting($host_id = 'me', $body)
	{
		$meeting = null;

		if (!empty($host_id) && !empty($body)) {
			if (is_array($body)) {
				$body = json_encode($body);
			}

			$host = $this->getUserById($host_id);

			if (!empty($host->id)) {
				$meeting = $this->post("users/$host_id/meetings", $body);
			}
			else {
				Log::add('[CREATE MEETING] Host not found', Log::ERROR, 'com_emundus.zoom');
			}
		}

		return $meeting;
	}

	public function getUserById($user_id)
	{
		$user = null;

		if (!empty($user_id)) {
			$user = $this->get('users/' . $user_id);

			if (is_string($user)) {
				$user = null;
			}
		}

		return $user;
	}

	public function createUser($user)
	{
		$user     = null;
		$response = null;

		if (!empty($user['email'])) {
			$existing_user = $this->getUserById($user['email']);
			if (empty($existing_user)) {
				$response = $this->post('users', json_encode([
					'action'    => 'create',
					'user_info' => [
						'email'      => $user['email'],
						'type'       => !empty($user['type']) ? $user['type'] : 2,
						'first_name' => $user['first_name'],
						'last_name'  => $user['last_name']
					]
				]));
			}
			else {
				$response = $existing_user;
			}
		}

		return $response;
	}

	public function updateMeeting($meeting_id, $body)
	{
		$response = null;

		if (!empty($meeting_id) && !empty($body)) {
			$response = $this->patch('meetings/' . $meeting_id, $body);
		}

		return $response;
	}

	public function updateMeetingDuration($meeting_id, $duration = 60)
	{
		$response = null;

		if (!empty($meeting_id) && $duration > 0) {
			$response = $this->patch('meetings/' . $meeting_id, [
				'duration' => $duration
			]);
		}

		return $response;
	}

	public function getUsers()
	{
		return $this->get('users?page_number=1&page_size=10');
	}

	public function getUserMeetings($zoom_user_id)
	{
		$meetings = null;

		if (!empty($zoom_user_id)) {
			$meetings = $this->get('users/' . $zoom_user_id . '/meetings');
		}

		return $meetings;
	}
}