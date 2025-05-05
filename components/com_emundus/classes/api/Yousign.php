<?php
/**
 * @package     com_emundus
 * @subpackage  api
 * @author    eMundus.fr
 * @copyright (C) 2022 eMundus SOFTWARE. All rights reserved.
 * @license    GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Tchooz\api;

use JComponentHelper;
use JFactory;
use JLog;

use Tchooz\api\Api;

defined('_JEXEC') or die('Restricted access');

class Yousign extends Api
{
	public function __construct()
	{
		parent::__construct();

		$this->setAuth();

		$em_config = JComponentHelper::getParams('com_emundus');
		$api_key = $em_config->get('yousign_api_key', '');

		$baseUrl = $em_config->get('yousign_prod', 'https://staging-api.yousign.com');
		$this->setBaseUrl($baseUrl);

		$auth = $this->getAuth();

		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $auth['bearer_token'],
			'accept' => 'application/json'
		);
		$this->setHeaders($headers);
		$this->setClient();
	}

	public function setAuth(): void
	{
		$config = JComponentHelper::getParams('com_emundus');
		$this->auth['bearer_token'] = $config->get('yousign_api_key', '');
	}

	public function getSignatureRequest($id) {
		$signature_request = [];
		
		$response = $this->get('/signature_requests/' . $id);
		
		if (!empty($response['data']->id)) {
			$signature_request = $response['data'];
		}

		return $signature_request;
	}

	public function activateSignatureRequest($id) {
		$response = $this->post('/signature_requests/' . $id . '/activate');

		return $response;
	}

	public function getDocument($sr_id, $document_id) {
		$response = $this->get('/signature_requests/' . $sr_id . '/documents/' . $document_id);

		return $response;
	}
}