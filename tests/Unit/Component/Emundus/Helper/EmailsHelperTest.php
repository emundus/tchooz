<?php
/**
 * @package     Unit\Component\Emundus\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Helper;

use EmundusHelperEmails;
use EmundusHelperUpdate;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_BASE . '/components/com_emundus/helpers/emails.php';
require_once JPATH_BASE . '/administrator/components/com_emundus/helpers/update.php';

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      EmundusHelperEmails
 */
class EmailsHelperTest extends UnitTestCase
{
	/**
	 * @var    EmundusHelperEmails
	 * @since  4.2.0
	 */
	private $helper;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->helper = new EmundusHelperEmails();
	}

	/**
	 * @covers EmundusHelperEmails::getEmail
	 *
	 * @since version 2.0.0
	 */
	public function testGetEmail()
	{
		$this->assertEmpty($this->helper->getEmail(0), 'Get email with invalid user_id returns empty string');

		$email = $this->helper->getEmail('new_account');
		$this->assertNotEmpty($email, 'Get email with valid user_id returns not empty string');
		$this->assertSame($email->lbl,'new_account', 'Get email with valid label returns correct email object');
	}

	/**
	 * @covers EmundusHelperEmails::getAllEmail
	 *
	 * @since version 2.0.0
	 */
	public function testGetAllEmail()
	{
		$emails = $this->helper->getAllEmail();
		$this->assertIsArray($emails, 'Get all emails returns an array');

		$emails = $this->helper->getAllEmail(1);
		$this->assertNotEmpty($emails, 'Get all emails returns not empty array of emails of type 1');
	}

	/**
	 * @covers EmundusHelperEmails::assertCanSendMailToUser
	 *
	 * @since version 1.0.0
	 */
	public function testAssertCanSendEmail()
	{
		$this->assertSame(false, $this->helper->assertCanSendMailToUser(), 'can send mail returns false if nor user_id nor fnum given');

		// User with correct email
		if (!empty($this->dataset['applicant'])) {
			$this->assertSame(true, $this->helper->assertCanSendMailToUser($this->dataset['applicant']), 'A new created user with valid adress can receive emails');

			$query = $this->db->getQuery(true);

			$params = json_encode(array('send_mail' => false));
			$query->clear()
				->update('#__users')
				->set('params = ' . $this->db->quote($params))
				->where('id = ' . $this->dataset['applicant']);
			$this->db->setQuery($query);
			$this->db->execute();

			$this->assertSame(false, $this->helper->assertCanSendMailToUser($this->dataset['applicant']), 'A user with param send email to false does not pass assertCanSendMailToUser function');

			$params = json_encode(array('send_mail' => true));
			$query->clear()
				->update('#__users')
				->set('params = ' . $this->db->quote($params))
				->where('id = ' . $this->dataset['applicant']);
			$this->db->setQuery($query);
			$this->db->execute();

			$this->assertSame(true, $this->helper->assertCanSendMailToUser($this->dataset['applicant']), 'A user with param send email to true pass assertCanSendMailToUser function');

			$query->clear()
				->delete($this->db->quoteName('#__users'))
				->where('id = ' . $this->dataset['applicant']);
			$this->db->setQuery($query);
			$this->db->execute();
		}

		// User with incorrect email
		$invalid_email_user_id = $this->h_dataset->createSampleUser(1000, 'legendre.jeremy');
		if (!empty($invalid_email_user_id)) {
			$this->assertSame(false, $this->helper->assertCanSendMailToUser($invalid_email_user_id), 'A new created user with invalid address can not receive emails');

			$query->clear()
				->delete($this->db->quoteName('#__users'))
				->where('id = ' . $invalid_email_user_id);
			$this->db->setQuery($query);
			$this->db->execute();
		}

		// User with inexisting email dns
		$invalid_email_dns_user_id = $this->h_dataset->createSampleUser(1000, 'legendre.jeremy@wrong.dns.wrong');
		if (!empty($invalid_email_dns_user_id)) {
			$this->assertSame(false, $this->helper->assertCanSendMailToUser($invalid_email_dns_user_id), 'A new created user with invalid dns in address can not receive emails');

			$query->clear()
				->delete($this->db->quoteName('#__users'))
				->where('id = ' . $invalid_email_dns_user_id);
			$this->db->setQuery($query);
			$this->db->execute();
		}
	}

	/**
	 * @covers EmundusHelperEmails::correctEmail
	 *
	 * @since version 1.0.0
	 */
	public function testCorrectEmail()
	{
		$this->assertSame(false, $this->helper->correctEmail(''), 'Validate empty email returns false');

		$this->assertSame(false, $this->helper->correctEmail('@email.com'), 'Validate email with wrong format returns false');
		$this->assertSame(false, $this->helper->correctEmail('jeremy.legendreemundus.fr'), 'Validate email with wrong format returns false');
		$this->assertSame(false, $this->helper->correctEmail('jeremy.legendre@'), 'Validate email with wrong format returns false');

		$this->assertSame(false, $this->helper->correctEmail('jeremy.legendre@wrong.dns'), 'Validate email with wrong dns returns false');

		$this->assertSame(true, $this->helper->correctEmail('jeremy.legendre@emundus.fr'), 'Validate correct email format returns true');
		$this->assertSame(true, $this->helper->correctEmail('jeremy.legendre@etudiant.emundus.fr'), 'Validate multiple subdomins email format returns true');
	}

	public function testGetLogo()
	{
		$logo = $this->helper->getLogo();
		$this->assertNotEmpty($logo, 'Get logo returns not empty string');
		
		// Check if a logo is image
		$logo = $this->helper->getLogo();
		$ext = pathinfo($logo, PATHINFO_EXTENSION);
		$this->assertContains($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg'], 'Get logo returns a valid image');
	}

	/**
	 * @covers EmundusHelperEmails::getCustomHeader
	 *
	 * @since version 1.0.0
	 */
	public function testGetCustomHeader()
	{
		// By default we doesn't have custom header
		$this->assertSame('', EmundusHelperEmails::getCustomHeader());

		// Add a custom header to emundus component
		EmundusHelperUpdate::updateComponentParameter('com_emundus', 'email_custom_tag', 'X-Mailin-Tag,emundus');

		$this->assertSame('X-Mailin-Tag:emundus', EmundusHelperEmails::getCustomHeader());

		// Clear parameter
		EmundusHelperUpdate::updateComponentParameter('com_emundus', 'email_custom_tag', '');
	}
}