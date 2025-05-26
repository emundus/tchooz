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
use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use stdClass;
use Tchooz\Enums\NumericSign\SignStatus;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelSign
 */
class SignModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('sign', $data, $dataName, 'EmundusModelSign');
	}

	public function testGetRequests()
	{
		$requests = $this->model->getRequests();

		$this->assertIsArray($requests, 'getRequests should return an array');

		$this->assertArrayHasKey('datas', $requests, 'getRequests should return an array of datas');
		$this->assertArrayHasKey('count', $requests, 'getRequests should return the count of requests');
	}

	public function testSaveRequest()
	{
		$attachment_id       = $this->h_dataset->createSampleAttachment();
		$upload_id              = $this->h_dataset->createSampleUpload($this->dataset['fnum'], $this->dataset['campaign'], $this->dataset['applicant'], $attachment_id);

		// Base request
		$request = [
			'status' => SignStatus::TO_SIGN->value,
			'ccid' => $this->dataset['ccid'],
			'user_id' => 0,
			'fnum' => '',
			'attachment' => $attachment_id,
			'connector' => 'yousign',
		];

		// 1. Test without signers
		$request_id = $this->model->saveRequest(
			0,
			$request['status'],
			$request['ccid'],
			$request['user_id'],
			$request['fnum'],
			$request['attachment'],
			$request['connector'],
			[],
			0,
			$this->dataset['coordinator']
		);
		$this->assertIsInt($request_id, 'saveRequest without signers should return an integer');

		// 2. Test with a signer
		$signer_1 = $this->h_dataset->createSampleContact('signer1@emundus.fr', 'Signer 1', 'TEST');
		$request['signers'] = [
			[
				'signer' => $signer_1,
				'page' => 1,
				'position' => 'C1'
			]
		];
		$request_id = $this->model->saveRequest(
			0,
			$request['status'],
			$request['ccid'],
			$request['user_id'],
			$request['fnum'],
			$request['attachment'],
			$request['connector'],
			$request['signers'],
			0,
			$this->dataset['coordinator']
		);
		$this->assertIsInt($request_id, 'saveRequest with signers should return an integer');

		// 3. Test with multiple signers
		$signer_2 = $this->h_dataset->createSampleContact('signer2@emundus.fr', 'Signer 2', 'TEST');
		$request['signers'] = ['signer1@emundus.fr', 'signer2@emundus.fr'];
		$request_id = $this->model->saveRequest(
			0,
			$request['status'],
			$request['ccid'],
			$request['user_id'],
			$request['fnum'],
			$request['attachment'],
			$request['connector'],
			$request['signers'],
			0,
			$this->dataset['coordinator']
		);
		$this->assertIsInt($request_id, 'saveRequest with signer\'s email should return an integer');

		// Test exceptions
		try
		{
			// 1. We cannot create a request without specifying the connector
			$this->model->saveRequest(
				0,
				$request['status'],
				$request['ccid'],
				$request['user_id'],
				$request['fnum'],
				$request['attachment'],
				'',
				[],
				0,
				$this->dataset['coordinator']
			);
		}
		catch (\Exception $e)
		{
			$this->assertSame('Connector is required.', $e->getMessage());
		}

		try
		{
			// 2. We cannot create a request with a wrong connector
			$this->model->saveRequest(
				0,
				$request['status'],
				$request['ccid'],
				$request['user_id'],
				$request['fnum'],
				$request['attachment'],
				'no_exising_connector',
				[],
				0,
				$this->dataset['coordinator']
			);
		}
		catch (\Exception $e)
		{
			$this->assertSame('Invalid connector type', $e->getMessage());
		}

		try
		{
			// 3. We cannot create a request with a wrong status
			$this->model->saveRequest(
				0,
				'no_exising_status',
				$request['ccid'],
				$request['user_id'],
				$request['fnum'],
				$request['attachment'],
				$request['connector'],
				[],
				0,
				$this->dataset['coordinator']
			);
		}
		catch (\Exception $e)
		{
			$this->assertSame('Invalid status type', $e->getMessage());
		}

		$this->h_dataset->deleteSampleUpload($upload_id);
		try
		{
			// 4. We cannot create a request without upload linked to attachment
			$this->model->saveRequest(
				0,
				$request['status'],
				$request['ccid'],
				$request['user_id'],
				$request['fnum'],
				$request['attachment'],
				$request['connector'],
				[],
				0,
				$this->dataset['coordinator']
			);
		}
		catch (\Exception $e)
		{
			$this->assertSame('Upload not found.', $e->getMessage());
		}

		$this->h_dataset->deleteSampleAttachment($attachment_id);
		try
		{
			// 5. We cannot create a request without attachment id
			$this->model->saveRequest(
				0,
				$request['status'],
				$request['ccid'],
				$request['user_id'],
				$request['fnum'],
				0,
				$request['connector'],
				[],
				0,
				$this->dataset['coordinator']
			);
		}
		catch (\Exception $e)
		{
			$this->assertSame('Attachment ID is required.', $e->getMessage());
		}

		$this->h_dataset->deleteSampleContact($signer_1);
		$this->h_dataset->deleteSampleContact($signer_2);
	}
}
