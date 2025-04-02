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
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

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

		$this->helper = HtmlSanitizerSingleton::getInstance($this->getCustomConfig());
	}

	private function getCustomConfig(): HtmlSanitizerConfig
	{
		return (new HtmlSanitizerConfig())
			->allowSafeElements()
			->allowRelativeLinks()
			->forceHttpsUrls()
			->allowRelativeMedias(true)
			->allowElement('span', ['class', 'id', 'style'])
			->allowAttribute('span', '*');
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

	public function testSpanAndAttributesArePreserved()
	{
		$sanitizer = HtmlSanitizerSingleton::getInstance($this->getCustomConfig());

		$input = '
            <span class="test-class" id="test-id" data-custom="value" style="color: red;" onclick="alert(\'hack\')">
                Test content
            </span>
            <script>alert("XSS");</script>
        ';

		$expected = '
            <span class="test-class" id="test-id" style="color: red;">
                Test content
            </span>
            
        ';

		$result = $sanitizer->sanitize($input);

		// Supprime les espaces et retours à la ligne pour faciliter la comparaison
		$normalizedResult = preg_replace('/\s+/', ' ', trim($result));
		$normalizedExpected = preg_replace('/\s+/', ' ', trim($expected));

		$this->assertEquals($normalizedExpected, $normalizedResult);

		// Vérifie que les attributs sont bien préservés
		$this->assertStringContainsString('class="test-class"', $result);
		$this->assertStringContainsString('id="test-id"', $result);
		$this->assertStringContainsString('style="color: red;"', $result);

		// Vérifie que le script et l'attribut onclick ont été supprimés
		$this->assertStringNotContainsString('<script>', $result);
		$this->assertStringNotContainsString('onclick=', $result);
	}
}