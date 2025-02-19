<?php

/**
 * @package         Joomla.UnitTest
 * @subpackage      Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Unit\Component\Emundus\Model;

use EmundusModelApplication;
use Joomla\Tests\Unit\UnitTestCase;
use classes\api\Api;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelApplication
 */
class SyncModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('sync', $data, $dataName, 'EmundusModelSync');
	}


	public function testgetValueFromPath()
	{

		$data = ['results' => ['token' => 123]];
		$path = 'results.token';
		$token = self::callPrivateMethod($this->model, 'getValueFromPath', [$data, $path]);
		$this->assertEquals(123, $token, "Token value from path");
	}

	public function testgetApi()
	{
		$api = $this->model->getApi(0);
		$this->assertNull($api, "Api not found");
	}

	/**
	 * @covers EmundusModelSync::authenticateApi
	 */
	public function  testauthenticateApi()
	{
		// Test authenticateApi method

		$ammon_api = $this->model->getApi(0, 'ammon');
		if (!empty($ammon_api)) {
			$config = json_decode($ammon_api->config, true);

			$api_class = new Api();
			$api_class->setBaseUrl($config['base_url']);
			$api_class->setClient();

			$token = $this->model->authenticateApi($ammon_api, $api_class, $config['api_url'], true);
			$this->assertNotEmpty($token, "Ammon successful auth");
		}
	}

}