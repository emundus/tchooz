<?php

namespace Unit\Component\Emundus\Class\Entities\Automation;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Automation\RedirectIntent;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Automation
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Automation\RedirectIntent
 */
class RedirectIntentTest extends TestCase
{
	// -------------------------------------------------------------------------
	// Constructor / getters
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Automation\RedirectIntent::__construct
	 * @covers \Tchooz\Entities\Automation\RedirectIntent::getUrl
	 * @return void
	 */
	public function testGetUrlReturnsConstructorValue(): void
	{
		$intent = new RedirectIntent('/index.php?option=com_emundus&task=openfile&fnum=42');

		$this->assertSame(
			'/index.php?option=com_emundus&task=openfile&fnum=42',
			$intent->getUrl(),
			'getUrl() must return the URL passed to the constructor.'
		);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\RedirectIntent::getSource
	 * @return void
	 */
	public function testGetSourceReturnsConstructorValue(): void
	{
		$intent = new RedirectIntent('/foo', 'redirect');

		$this->assertSame(
			'redirect',
			$intent->getSource(),
			'getSource() must return the source passed to the constructor.'
		);
	}

	// -------------------------------------------------------------------------
	// Default values
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Automation\RedirectIntent::getSource
	 * @return void
	 */
	public function testSourceDefaultsToNull(): void
	{
		$intent = new RedirectIntent('/foo');

		$this->assertNull(
			$intent->getSource(),
			'The default source must be null.'
		);
	}
}
