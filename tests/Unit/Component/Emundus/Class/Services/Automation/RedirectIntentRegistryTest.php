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

		// Request-scoped static state: start each test from a clean slate.
		RedirectIntentRegistry::reset();

		// The registry logs when a request is dropped. Provide an application with a writable
		// log_path so Joomla's file logger does not raise "Cannot write to log file." in a pure
		// unit test.
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
	// request() — registration and "first come, first served" policy
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::request
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::consume
	 * @return void
	 */
	public function testRequestWhenNoPendingStoresIntent(): void
	{
		$intent = new RedirectIntent('/foo', 'a');
		RedirectIntentRegistry::request($intent);

		$this->assertSame(
			$intent,
			RedirectIntentRegistry::consume(),
			'The first registered intent must become the current one.'
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
			RedirectIntentRegistry::consume(),
			'An intent with an empty URL must not be registered.'
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
			RedirectIntentRegistry::consume(),
			'First come, first served: a later request must be ignored.'
		);
	}

	// -------------------------------------------------------------------------
	// consume()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::consume
	 * @return void
	 */
	public function testConsumeClearsIntentSoSecondCallReturnsNull(): void
	{
		RedirectIntentRegistry::request(new RedirectIntent('/foo', 'a'));

		RedirectIntentRegistry::consume();

		$this->assertNull(
			RedirectIntentRegistry::consume(),
			'consume() must clear the intent after reading it.'
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
			'consume() with no pending intent must return null.'
		);
	}

	// -------------------------------------------------------------------------
	// reset()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentRegistry::reset
	 * @return void
	 */
	public function testResetRemovesPending(): void
	{
		RedirectIntentRegistry::request(new RedirectIntent('/foo', 'a'));

		RedirectIntentRegistry::reset();

		$this->assertNull(
			RedirectIntentRegistry::consume(),
			'reset() must clear the pending intent.'
		);
	}
}
