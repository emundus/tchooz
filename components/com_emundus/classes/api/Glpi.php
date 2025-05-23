<?php
/**
 * @package       com_emundus
 * @subpackage    api
 * @author        eMundus.fr
 * @copyright (C) 2022 eMundus SOFTWARE. All rights reserved.
 * @license       GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Tchooz\api;

use JComponentHelper;
use JFactory;
use JLog;

use Tchooz\api\Api;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');

class Glpi extends Api
{
	public function __construct($entities = array())
	{
		parent::__construct();

		$config = ComponentHelper::getParams('com_emundus');
		$baseUrl = $config->get('glpi_api_base_url', '');
		$this->setBaseUrl($baseUrl);

		$this->setClient();
		$this->setAuth();

		$auth = $this->getAuth();
		$headers = array(
			'App-Token' => $auth['app_token'],
			'Session-token' => $auth['session_token'],
		);
		$this->setHeaders($headers);

		if (!empty($entities)) {
			$this->setEntities($entities);
		}
	}

	public function setAuth(): void
	{
		$config = ComponentHelper::getParams('com_emundus');

		$this->auth['app_token']     = $config->get('glpi_api_app_token', '');
		$this->auth['user_token']    = $config->get('glpi_api_user_token', '');
		$this->auth['session_token'] = $this->getSessionToken();
	}

	private function getSessionToken(): string
	{
		$session            = Factory::getApplication()->getSession();
		$glpi_session_token = $session->get('glpi_session_token', '');

		if (empty($glpi_session_token)) {
			$auth = $this->getAuth();

			$this->headers = array(
				'App-Token'     => $auth['app_token'],
				'Authorization' => 'user_token ' . $auth['user_token'],
			);

			$response = $this->get('initSession');

			if ($response['status'] == 200) {
				$session->set('glpi_session_token', $response['data']->session_token);

				$glpi_session_token = $response['data']->session_token;
			}
		}

		return $glpi_session_token;
	}

	private function setEntities($entities): void
	{
		if (!empty($entities)) {
			$body = array();
			array_map(function ($entity) use (&$body) {
				$body[] = array(
					'entities_id'  => $entity,
					'is_recursive' => true
				);
			}, $entities);

			$this->post('changeActiveEntities', json_encode($body));
		}
	}

	public function getEntities(): array
	{
		$response = $this->get('getMyEntities');

		$entities = array();
		if ($response['status'] == 200) {
			$entities = $response['data']->myentities;
		}

		return $entities;
	}

	public function get($url, $params = array(), $retry = true)
	{
		$response = ['status' => 200, 'message' => '', 'data' => ''];

		$session = Factory::getApplication()->getSession();

		try {
			$url_params   = http_build_query($params);
			$complete_url = !empty($url_params) ? $url . '?' . $url_params : $url;
			if (!empty($complete_url)) {
				$complete_url = $this->baseUrl . '/' . $complete_url;
			}
			else {
				$complete_url = $this->baseUrl;
			}

			$request            = $this->client->get($complete_url, ['headers' => $this->getHeaders()]);
			$response['status'] = $request->getStatusCode();
			$response['data']   = json_decode($request->getBody());

			if ($response['status'] == 401 && $retry) {
				$session->clear('glpi_session_token');
				$this->setAuth();
				$this->setHeaders();
				$this->get($url, $params, false);
			}
		}
		catch (\Exception $e) {
			JLog::add('[GET] ' . $e->getMessage(), JLog::ERROR, 'com_emundus.api');
			$response['status']  = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		return $response;
	}

	/**
	 * @param $table
	 *
	 *
	 * @desc List the searchoptions of provided itemtype. To use with Search items.
	 */
	public function listSearchOptions($table): array
	{
		return $this->get('listSearchOptions/' . $table);
	}

	/**
	 * @param $table
	 * @param $criterias
	 * @param $forcedisplay
	 * @param $range
	 *
	 * @return array
	 *
	 * @desc  Expose the GLPI searchEngine and combine criteria to retrieve a list of elements of specified itemtype. > Note: you can use 'AllAssets' itemtype to retrieve a combination of all asset's types.
	 */
	public function search($table, $criterias = [], $forcedisplay = [], $range = '0-100'): array
	{
		return $this->get('search/' . $table,
			[
				'criteria'     => $criterias,
				'forcedisplay' => $forcedisplay,
				'range'        => $range
			]
		);
	}

	/**
	 * @param $table
	 * @param $data
	 *
	 * @return array
	 *
	 * @desc Add an object (or multiple objects) into GLPI.
	 */
	public function addItem($table, $data): array
	{
		return $this->post($table, $data);
	}

	/**
	 * @param $table
	 * @param $id
	 * @param $force_purge
	 *
	 * @return array
	 *
	 * @desc Delete an object existing in GLPI.
	 */
	public function deleteItem($table, $id, $force_purge = false): array
	{
		return $this->delete($table . '/' . $id . '?force_purge=' . $force_purge);
	}
}