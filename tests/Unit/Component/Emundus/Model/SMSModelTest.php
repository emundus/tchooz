<?php

/**
 * @package         Joomla.UnitTest
 * @subpackage      Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Unit\Component\Emundus\Model;

use classes\Entities\SMS\ReceiverEntity;
use classes\Synchronizers\SMS\OvhSMS;
use EmundusModelApplication;
use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelApplication
 */
class SMSModelTest extends UnitTestCase
{

	private string $applicant_phone_number = '+33999999999';

	private int $user_id = 1;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('sms', $data, $dataName, 'EmundusModelSMS');
	}

	private function addPhoneNumberToApplicant(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->update('#__emundus_users')
			->set('tel = ' . $db->quote($this->applicant_phone_number))
			->where('user_id = ' . $this->dataset['applicant']);

		$db->setQuery($query)->execute();
	}

	public function testGetSmsActionId()
	{
		$this->assertNotEmpty($this->model->getSmsActionId(), 'The SMS action ID should not be empty');
	}

	public function testAddTemplate()
	{
		$template_id = $this->model->addTemplate($this->user_id, 'This is a test template', 'This is a test message');
		$this->assertGreaterThan(0, $template_id, 'The template ID should be greater than 0');
	}

	public function testGetTemplate()
	{
		$template = 'This is a test template';
		$message = 'This is a test message';
		$template_id = $this->model->addTemplate($this->user_id, $template, $message);
		$retrieved_template = $this->model->getSMSTemplate($template_id);
		$this->assertNotEmpty($retrieved_template, 'The retrieved template should not be empty');
		$this->assertSame($template, $retrieved_template['label'], 'The retrieved template should be the same as the one added');
		$this->assertSame($message, $retrieved_template['message'], 'The retrieved message should be the same as the one added');
	}

	public function testUpdateTemplate()
	{
		$label = 'This is a test template';
		$template_id = $this->model->addTemplate($this->user_id, $label, 'This is a test message');

		$new_message = 'This is a new test message';
		$this->model->updateTemplate($template_id, $label, $new_message, $this->user_id);
		$retrieved_template = $this->model->getSMSTemplate($template_id);
		$this->assertSame($new_message, $retrieved_template['message'], 'The retrieved message should be udpated');
	}

	public function testDeleteTemplate()
	{
		$template_id = $this->model->addTemplate($this->user_id, 'This is a test template', 'This is a test message');
		$this->model->deleteTemplate($template_id, $this->user_id);
		$retrieved_template = $this->model->getSMSTemplate($template_id);
		$this->assertEmpty($retrieved_template, 'The template should be deleted');
	}
	public function testCreateReceiversFromFnums()
	{
		$this->addPhoneNumberToApplicant();
		$fnum = $this->dataset['fnum'];

		$receivers = $this->model->createReceiversFromFnums([$fnum]);
		$this->assertNotEmpty($receivers, 'Receivers should not be empty');
		$this->assertIsArray($receivers, 'Receivers should be an array');
		$this->assertInstanceOf(ReceiverEntity::class, $receivers[0], 'Receivers should be an array of ReceiverEntity');
		$this->assertSame($this->applicant_phone_number, $receivers[0]->phone_number, 'The phone number of the receiver should be the one of the applicant');
	}

	public function testStoreSmsToSend()
	{
		$this->addPhoneNumberToApplicant();
		$stored = $this->model->storeSmsToSend('', [], 0, $this->user_id);
		$this->assertFalse($stored, 'The SMS should not be stored because it is empty');

		$receivers = $this->model->createReceiversFromFnums([$this->dataset['fnum']]);
		$template_id = $this->model->addTemplate($this->user_id, 'This is a test template', 'This is a test message');

		$stored = $this->model->storeSmsToSend('Test message', $receivers, $template_id, $this->user_id);
		$this->assertTrue($stored, 'The SMS should be stored');
	}

	public function testGetStoredSMS()
	{
		$this->addPhoneNumberToApplicant();
		$receivers = $this->model->createReceiversFromFnums([$this->dataset['fnum']]);
		$template_id = $this->model->addTemplate($this->user_id, 'This is a test template', 'This is a test message');
		$this->model->storeSmsToSend('Test message', $receivers, $template_id, $this->user_id);

		$stored_sms = $this->model->getStoredSMS($this->dataset['fnum'], [], 0, 0, '');
		$this->assertNotEmpty($stored_sms, 'The stored SMS should not be empty');
	}

	public function testGetPendingSMS()
	{
		$this->addPhoneNumberToApplicant();
		$receivers = $this->model->createReceiversFromFnums([$this->dataset['fnum']]);
		$template_id = $this->model->addTemplate($this->user_id, 'This is a test template', 'This is a test message');
		$this->model->storeSmsToSend('Test message', $receivers, $template_id, $this->user_id);

		$pending_sms = $this->model->getPendingSMS();
		$this->assertNotEmpty($pending_sms, 'The pending SMS should not be empty');
	}

	public function testSendPendingSMS()
	{
		$this->addPhoneNumberToApplicant();
		$receivers = $this->model->createReceiversFromFnums([$this->dataset['fnum']]);
		$template_id = $this->model->addTemplate($this->user_id, 'This is a test template', 'This is a test message');
		$this->model->storeSmsToSend('Test message', $receivers, $template_id, $this->user_id);

		$pending_sms = $this->model->getPendingSMS();
		$this->assertNotEmpty($pending_sms, 'The pending SMS should not be empty.');


		if (!class_exists('OvhSMS')) {
			require_once(JPATH_ROOT . '/components/com_emundus/classes/Synchronizers/SMS/OvhSMS.php');
		}

		// fake OvhSMS
		$synchronizer = $this->createMock(OvhSMS::class);

		$sent = $this->model->sendPendingSMS($synchronizer, 3, 500, true);
		$this->assertTrue($sent, 'The pending SMS should be sent (DEBUG MODE)');

		$pending_sms = $this->model->getPendingSMS();
		$this->assertEmpty($pending_sms, 'The pending SMS should be empty.');
	}
}