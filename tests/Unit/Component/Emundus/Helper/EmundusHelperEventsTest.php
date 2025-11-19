<?php

namespace Unit\Component\Emundus\Helper;

use Component\Emundus\Helpers\HtmlSanitizerSingleton;
use Joomla\Tests\Unit\UnitTestCase;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/events.php');

class EmundusHelperEventsTest extends UnitTestCase
{
	/**
	 * @var    \EmundusHelperEvents
	 * @since  4.2.0
	 */
	private \EmundusHelperEvents $helper;

	private HtmlSanitizerSingleton $sanitizer;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->helper = new \EmundusHelperEvents();
		if (!class_exists('HtmlSanitizerSingleton')) {
			require_once(JPATH_SITE . '/components/com_emundus/helpers/html.php');
		}
		$this->sanitizer = HtmlSanitizerSingleton::getInstance();
	}


	/**
	 * @covers \EmundusHelperEvents::sanitizeValue
	 * @return void
	 */
	public function testSanitizeValue()
	{
		$valueWithInjection = '<script>alert("XSS")</script><b>Bold Text</b>';
		$sanitizedValueNoHTML = $this->helper->sanitizeValue($valueWithInjection, true, $this->sanitizer);
		$this->assertEquals('Bold Text', $sanitizedValueNoHTML, 'Sanitize value should remove script tags and all html whan strong sanitize.');
		$sanitizedValueWithHTML = $this->helper->sanitizeValue($valueWithInjection, false, $this->sanitizer);
		$this->assertEquals('<b>Bold Text</b>', $sanitizedValueWithHTML, 'Sanitize value should remove script tags but keep allowed html tags when not strong sanitize.');
	}

	/**
	 * @covers \EmundusHelperEvents::sanitizeValue
	 * @return void
	 */
	public function testSanitizeValueUnallowXlsInjection()
	{
		$valueWithInjection = '=SUM(A1:A2)';
		$sanitizedValueNoHTML = $this->helper->sanitizeValue($valueWithInjection, true, $this->sanitizer);
		$this->assertEquals('SUM(A1:A2)', $sanitizedValueNoHTML, 'Sanitize value should remove = sign at start to prevent excel injection.');
	}
}