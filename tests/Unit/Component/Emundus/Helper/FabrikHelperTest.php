<?php
/**
 * @package     Unit\Component\Emundus\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Helper;

use EmundusHelperFabrik;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';

class FabrikHelperTest extends UnitTestCase
{
	/**
	 * @var    EmundusHelperFabrik
	 * @since  4.2.0
	 */
	private $helper;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->helper = new EmundusHelperFabrik();
	}

	public function testgetFormattedPhoneNumberValue()
	{
		$unformatted_phone_number = '';
		$formatted_phone_number   = $this->helper->getFormattedPhoneNumberValue($unformatted_phone_number);
		$this->assertSame('', $formatted_phone_number, 'Empty phone number returns empty string');

		$unformatted_phone_number = 'zkljhdqopsjdpzhfklqsjnd';
		$formatted_phone_number   = $this->helper->getFormattedPhoneNumberValue($unformatted_phone_number);
		$this->assertSame('', $formatted_phone_number, 'Random string with incorrect characters returns empty string');

		$unformatted_phone_number = '+33 6 12 34 56 78';
		$formatted_phone_number   = $this->helper->getFormattedPhoneNumberValue($unformatted_phone_number);
		$this->assertNotEmpty($formatted_phone_number, 'Correct phone number returns not empty string and by default format is E164');
		$this->assertSame('FR+33612345678', $formatted_phone_number, 'Correct phone number returns correct formatted string');

		$unformatted_phone_number = 'FR+33 612 3456 7 8';
		$formatted_phone_number   = $this->helper->getFormattedPhoneNumberValue($unformatted_phone_number);
		$this->assertNotEmpty($formatted_phone_number, 'Correct phone number returns not empty string');
		$this->assertSame('FR+33612345678', $formatted_phone_number, 'Correct phone number with weird spacing returns correct formatted string');

		$unformatted_phone_number = 'FR+33 612 3456 7 8';
		$formatted_phone_number   = $this->helper->getFormattedPhoneNumberValue($unformatted_phone_number, 2);
		$this->assertNotEmpty($formatted_phone_number, 'Correct phone number returns not empty string');
		$this->assertSame('FR06 12 34 56 78', $formatted_phone_number, 'Setting format 2 (national) returns formatted number correctly');


		$unformatted_phone_number = 'FR+33 612 34za 7 8';
		$formatted_phone_number   = $this->helper->getFormattedPhoneNumberValue($unformatted_phone_number, 2);
		$this->assertEmpty($formatted_phone_number, 'Incorrect phone number returns empty string');
	}
}