<?php
/**
 * @package     Unit\Component\Emundus\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Helper;

use Joomla\Tests\Unit\UnitTestCase;
use Component\Emundus\Helpers\HtmlSanitizerSingleton;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      EmundusHelperFiles
 */
class HtmlSanitizerSingletonTest extends UnitTestCase
{
	/**
	 * @var    HtmlSanitizerSingleton
	 * @since  4.2.0
	 */
	private HtmlSanitizerSingleton $helper;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		if (!class_exists('HtmlSanitizerSingleton'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/helpers/html.php');
		}

		$this->helper = HtmlSanitizerSingleton::getInstance();
	}

	public function testSanitize() {
		$input = '<script>alert("Hello World!")</script>';
		$result = $this->helper->sanitize($input);
		$this->assertSame('', $result, 'Remove script tags');

		$input = '<a href="http://www.example.com">Click here</a>';
		$result = $this->helper->sanitize($input);
		$this->assertSame('<a href="https://www.example.com">Click here</a>', $result, 'Force HTTPS URLs');

		$input = '<img src="http://www.example.com/image.jpg" />';
		$result = $this->helper->sanitize($input);
		$this->assertSame('<img src="https://www.example.com/image.jpg" />', $result, 'Force HTTPS URLs');
	}
}