<?php

namespace Unit\Component\Emundus\Class\Services\Automation;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Automation\RedirectIntent;
use Tchooz\Services\Automation\RedirectIntentRegistry;

/**
 * @package     Unit\Component\Emundus\Class\Services\Automation
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Automation\RedirectIntentRegistry
 */
class RedirectIntentRegistryTest extends TestCase
{
	private $previousApplication;

	protected function setUp(): void
	{
		parent::setUp();

		// État statique request-scoped : on repart d'une ardoise vierge à chaque test.
		RedirectIntentRegistry::reset();

		// Le registre journalise quand une demande est ignorée. On fournit une application dont le
		// log_path est inscriptible pour que le logger fichier de Joomla ne lève pas
		// "Cannot write to log file." dans un test unitaire pur.
		$this->previousApplication = Factory::$application;

		$logPath = sys_get_temp_dir();
		$app     = $this->createStub(CMSApplication::class);
		$app->method('get')->willReturnCallback(
			static fn ($key, $default = null) => $key === 'log_path' ? $logPath : $default
		);
		Factory::$application = $app;
	}

	protected function tearDown(): void
	{
		RedirectIntentRegistry::reset();
		Factory::$application = $this->previousApplication;

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// request() — enregistrement et politique "premier arrivé gagne"
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::request
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::peek
	 * @return void
	 */
	public function testRequestWhenNoPendingStoresIntent(): void
	{
		$intent = new RedirectIntent('/foo', 'a');
		RedirectIntentRegistry::request($intent);

		$this->assertSame(
			$intent,
			RedirectIntentRegistry::peek(),
			'Le premier intent enregistré doit devenir l\'intent courant.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::request
	 * @return void
	 */
	public function testRequestWithEmptyUrlIsIgnored(): void
	{
		RedirectIntentRegistry::request(new RedirectIntent('', 'a'));

		$this->assertNull(
			RedirectIntentRegistry::peek(),
			'Un intent avec une URL vide ne doit pas être enregistré.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::request
	 * @return void
	 */
	public function testRequestKeepsFirstWhenAlreadyPending(): void
	{
		$first  = new RedirectIntent('/first', 'first');
		$second = new RedirectIntent('/second', 'second');

		RedirectIntentRegistry::request($first);
		RedirectIntentRegistry::request($second);

		$this->assertSame(
			$first,
			RedirectIntentRegistry::peek(),
			'Premier arrivé gagne : une demande ultérieure doit être ignorée.'
		);
	}

	// -------------------------------------------------------------------------
	// consume() / peek()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::consume
	 * @return void
	 */
	public function testConsumeReturnsIntentAndClearsIt(): void
	{
		$intent = new RedirectIntent('/foo', 'a');
		RedirectIntentRegistry::request($intent);

		$this->assertSame(
			$intent,
			RedirectIntentRegistry::consume(),
			'consume() doit renvoyer l\'intent courant.'
		);
		$this->assertNull(
			RedirectIntentRegistry::peek(),
			'consume() doit vider l\'intent après lecture.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::consume
	 * @return void
	 */
	public function testConsumeWhenEmptyReturnsNull(): void
	{
		$this->assertNull(
			RedirectIntentRegistry::consume(),
			'consume() sans intent en attente doit renvoyer null.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::peek
	 * @return void
	 */
	public function testPeekDoesNotClear(): void
	{
		$intent = new RedirectIntent('/foo', 'a');
		RedirectIntentRegistry::request($intent);

		RedirectIntentRegistry::peek();

		$this->assertSame(
			$intent,
			RedirectIntentRegistry::peek(),
			'peek() ne doit pas vider l\'intent (lecture non destructive).'
		);
	}

	// -------------------------------------------------------------------------
	// clear() / reset()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::clear
	 * @return void
	 */
	public function testClearRemovesPending(): void
	{
		RedirectIntentRegistry::request(new RedirectIntent('/foo', 'a'));

		RedirectIntentRegistry::clear();

		$this->assertNull(
			RedirectIntentRegistry::peek(),
			'clear() doit vider l\'intent en attente.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::reset
	 * @return void
	 */
	public function testResetRemovesPending(): void
	{
		RedirectIntentRegistry::request(new RedirectIntent('/foo', 'a'));

		RedirectIntentRegistry::reset();

		$this->assertNull(
			RedirectIntentRegistry::peek(),
			'reset() doit vider l\'intent en attente.'
		);
	}
}
