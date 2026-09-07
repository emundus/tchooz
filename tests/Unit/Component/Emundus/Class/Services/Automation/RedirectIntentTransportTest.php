<?php

namespace Unit\Component\Emundus\Class\Services\Automation;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Automation\RedirectIntent;
use Tchooz\Services\Automation\RedirectIntentRegistry;
use Tchooz\Services\Automation\RedirectIntentTransport;

/**
 * @package     Unit\Component\Emundus\Class\Services\Automation
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Automation\RedirectIntentTransport
 */
class RedirectIntentTransportTest extends TestCase
{
	private $previousApplication;

	private array $previousServer;

	protected function setUp(): void
	{
		parent::setUp();

		RedirectIntentRegistry::reset();

		$this->previousApplication = Factory::$application;
		$this->previousServer      = $_SERVER;

		// Uri::getInstance() caches the current request statically.
		$_SERVER['HTTP_HOST']   = 'tchooz.test';
		$_SERVER['REQUEST_URI'] = '/fr/mes-candidatures';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		Uri::reset();
	}

	protected function tearDown(): void
	{
		RedirectIntentRegistry::reset();
		Factory::$application = $this->previousApplication;
		$_SERVER              = $this->previousServer;
		Uri::reset();

		parent::tearDown();
	}

	/**
	 * @param   array  $query  Request variables, e.g. ['format' => 'json'].
	 */
	private function application(array $query = [], string $client = 'site', bool $expectsRedirect = false, string $redirectUrl = ''): CMSApplication
	{
		$app = $this->createMock(CMSApplication::class);

		$app->method('isClient')->willReturnCallback(static fn ($name) => $name === $client);
		$app->method('getInput')->willReturn(new Input($query));
		$app->method('get')->willReturnCallback(
			static fn ($key, $default = null) => $key === 'log_path' ? sys_get_temp_dir() : $default
		);

		// No active menu item: the URL comparison then relies on the current request URI only.
		$menu = $this->createStub(AbstractMenu::class);
		$menu->method('getActive')->willReturn(null);
		$app->method('getMenu')->willReturn($menu);

		if ($expectsRedirect)
		{
			$app->expects($this->once())->method('redirect')->with($redirectUrl);
		}
		else
		{
			$app->expects($this->never())->method('redirect');
		}

		Factory::$application = $app;

		return $app;
	}

	// -------------------------------------------------------------------------
	// isInlineTransportAvailable()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::isInlineTransportAvailable
	 * @return void
	 */
	public function testInlineTransportIsAvailableOnAFullPageRequest(): void
	{
		$transport = new RedirectIntentTransport($this->application());

		$this->assertTrue(
			$transport->isInlineTransportAvailable(),
			'A site request with no format declared must be able to carry a redirect.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::isInlineTransportAvailable
	 * @return void
	 */
	public function testInlineTransportIsNotAvailableOutsideTheSiteClient(): void
	{
		$transport = new RedirectIntentTransport($this->application([], 'administrator'));

		$this->assertFalse(
			$transport->isInlineTransportAvailable(),
			'Only the site client redirects: the back-end has no redirect transport.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::isInlineTransportAvailable
	 * @return void
	 */
	public function testInlineTransportIsNotAvailableOnAJsonRequest(): void
	{
		$transport = new RedirectIntentTransport($this->application(['format' => 'json']));

		$this->assertFalse(
			$transport->isInlineTransportAvailable(),
			'A request declaring format=json expects the intent in its payload, not a 303.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::isInlineTransportAvailable
	 * @return void
	 */
	public function testInlineTransportIsNotAvailableOnARawRequest(): void
	{
		$transport = new RedirectIntentTransport($this->application(['format' => 'raw']));

		$this->assertFalse(
			$transport->isInlineTransportAvailable(),
			'A raw response must not be replaced by a redirect.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::isInlineTransportAvailable
	 * @return void
	 */
	public function testInlineTransportIsNotAvailableOnAnXmlHttpRequest(): void
	{
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$transport = new RedirectIntentTransport($this->application());

		$this->assertFalse(
			$transport->isInlineTransportAvailable(),
			'jQuery $.ajax calls declare themselves through X-Requested-With and answer JSON.'
		);
	}

	// -------------------------------------------------------------------------
	// transportPending()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::transportPending
	 * @return void
	 */
	public function testTransportPendingRedirectsToTheIntentUrl(): void
	{
		$app       = $this->application([], 'site', true, '/fr/catalogue');
		$transport = new RedirectIntentTransport($app);

		RedirectIntentRegistry::request(new RedirectIntent('/fr/catalogue', 'redirect'));

		$this->assertTrue($transport->transportPending(), 'A pending intent must be transported.');
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::transportPending
	 * @return void
	 */
	public function testTransportPendingWithoutIntentDoesNothing(): void
	{
		$transport = new RedirectIntentTransport($this->application());

		$this->assertFalse(
			$transport->transportPending(),
			'With no pending intent, the request must go on untouched.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::transportPending
	 * @return void
	 */
	public function testTransportPendingOnTheCurrentUrlDoesNotRedirect(): void
	{
		$transport = new RedirectIntentTransport($this->application());

		RedirectIntentRegistry::request(new RedirectIntent('/fr/mes-candidatures', 'redirect'));

		$this->assertFalse(
			$transport->transportPending(),
			'An intent pointing at the page being served must be dropped, otherwise it loops.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::transportPending
	 * @return void
	 */
	public function testTransportPendingLeavesTheIntentPendingOnAJsonRequest(): void
	{
		$transport = new RedirectIntentTransport($this->application(['format' => 'json']));
		$intent    = new RedirectIntent('/fr/catalogue', 'redirect');

		RedirectIntentRegistry::request($intent);

		$this->assertFalse($transport->transportPending(), 'A JSON request must not be redirected.');
		$this->assertSame(
			$intent,
			RedirectIntentRegistry::consume(),
			'The intent must stay pending so the endpoint can return it in its payload.'
		);
	}

	/**
	 * @covers \Tchooz\Services\Automation\RedirectIntentTransport::transportPending
	 * @return void
	 */
	public function testTransportPendingConsumesTheIntent(): void
	{
		$app       = $this->application([], 'site', true, '/fr/catalogue');
		$transport = new RedirectIntentTransport($app);

		RedirectIntentRegistry::request(new RedirectIntent('/fr/catalogue', 'redirect'));
		$transport->transportPending();

		$this->assertNull(
			RedirectIntentRegistry::consume(),
			'The transported intent must not stay pending.'
		);
	}
}
