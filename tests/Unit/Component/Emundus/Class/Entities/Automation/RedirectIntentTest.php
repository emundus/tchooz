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
	// Constructeur / getters
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
			'getUrl() doit renvoyer l\'URL passée au constructeur.'
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
			'getSource() doit renvoyer la source passée au constructeur.'
		);
	}

	// -------------------------------------------------------------------------
	// Valeurs par défaut
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
			'La source par défaut doit être null.'
		);
	}
}
