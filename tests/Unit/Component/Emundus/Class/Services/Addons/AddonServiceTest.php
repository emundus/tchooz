<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Addons;

use PHPUnit\Framework\TestCase;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\AbstractAddonHandler;
use Tchooz\Services\Addons\AddonHandlerResolver;
use Tchooz\Services\Addons\AddonService;

/**
 * @covers \Tchooz\Services\Addons\AddonService
 */
class AddonServiceTest extends TestCase
{
	/** @var AddonRepository&\PHPUnit\Framework\MockObject\MockObject */
	private $repositoryMock;

	/** @var AddonHandlerResolver&\PHPUnit\Framework\MockObject\MockObject */
	private $resolverMock;

	/** @var AbstractAddonHandler&\PHPUnit\Framework\MockObject\MockObject */
	private $handlerMock;

	private AddonService $service;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		// Provide a no-op EmundusHelperCache so executeHandler does not fail outside Docker
		if (!class_exists('EmundusHelperCache'))
		{
			eval('class EmundusHelperCache { public function clean(bool $debug = false, array $groups = []): void {} }');
		}
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->repositoryMock = $this->getMockBuilder(AddonRepository::class)
			->disableOriginalConstructor()
			->getMock();

		$this->resolverMock = $this->getMockBuilder(AddonHandlerResolver::class)
			->disableOriginalConstructor()
			->getMock();

		$this->handlerMock = $this->getMockBuilder(AbstractAddonHandler::class)
			->disableOriginalConstructor()
			->getMock();

		$this->service = new AddonService($this->repositoryMock, $this->resolverMock);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function makeAddon(
		string $namekey = 'payment',
		bool $activated = false,
		bool $displayed = true,
		bool $suggested = false
	): AddonEntity
	{
		return new AddonEntity($namekey, $activated, $displayed, $suggested);
	}

	// -------------------------------------------------------------------------
	// activate()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::activate
	 */
	public function testActivateReturnsFalseWhenAddonNotFound(): void
	{
		$this->repositoryMock->method('getByName')->with('payment')->willReturn(null);

		$this->assertFalse($this->service->activate('payment'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::activate
	 */
	public function testActivateReturnsTrueImmediatelyWhenAlreadyActivated(): void
	{
		$addon = $this->makeAddon('payment', true);
		$this->repositoryMock->method('getByName')->with('payment')->willReturn($addon);
		$this->repositoryMock->expects($this->never())->method('flush');
		$this->resolverMock->expects($this->never())->method('resolve');

		$this->assertTrue($this->service->activate('payment'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::activate
	 */
	public function testActivateReturnsFalseWhenFlushFails(): void
	{
		$addon = $this->makeAddon('payment', false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(false);

		$this->assertFalse($this->service->activate('payment'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::activate
	 */
	public function testActivateSetsActivatedTrueBeforeFlush(): void
	{
		$addon = $this->makeAddon('payment', false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);
		$this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('No handler'));

		$this->service->activate('payment');

		$this->assertTrue($addon->isActivated());
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::activate
	 */
	public function testActivateSetsActivatedAtBeforeFlush(): void
	{
		$addon = $this->makeAddon('payment', false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);
		$this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('No handler'));

		$this->service->activate('payment');

		$this->assertNotNull($addon->getActivatedAt());
		$this->assertInstanceOf(\DateTimeImmutable::class, $addon->getActivatedAt());
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::activate
	 */
	public function testActivateReturnsTrueWhenNoHandlerExists(): void
	{
		$addon = $this->makeAddon('payment', false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);
		$this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('No handler'));

		$this->assertTrue($this->service->activate('payment'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::activate
	 */
	public function testActivateCallsOnActivateOnHandlerAndReturnsResult(): void
	{
		$addon = $this->makeAddon('payment', false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);

		$this->handlerMock->expects($this->once())->method('onActivate')->with($addon)->willReturn(true);
		$this->resolverMock->method('resolve')->with('payment', $addon)->willReturn($this->handlerMock);

		$this->assertTrue($this->service->activate('payment'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::activate
	 */
	public function testActivateReturnsHandlerOnActivateResult(): void
	{
		$addon = $this->makeAddon('payment', false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);

		$this->handlerMock->method('onActivate')->willReturn(false);
		$this->resolverMock->method('resolve')->willReturn($this->handlerMock);

		$this->assertFalse($this->service->activate('payment'));
	}

	// -------------------------------------------------------------------------
	// deactivate()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::deactivate
	 */
	public function testDeactivateReturnsFalseWhenAddonNotFound(): void
	{
		$this->repositoryMock->method('getByName')->with('sms')->willReturn(null);

		$this->assertFalse($this->service->deactivate('sms'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::deactivate
	 */
	public function testDeactivateReturnsTrueImmediatelyWhenAlreadyDeactivated(): void
	{
		$addon = $this->makeAddon('sms', false);
		$this->repositoryMock->method('getByName')->with('sms')->willReturn($addon);
		$this->repositoryMock->expects($this->never())->method('flush');
		$this->resolverMock->expects($this->never())->method('resolve');

		$this->assertTrue($this->service->deactivate('sms'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::deactivate
	 */
	public function testDeactivateReturnsFalseWhenFlushFails(): void
	{
		$addon = $this->makeAddon('sms', true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(false);

		$this->assertFalse($this->service->deactivate('sms'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::deactivate
	 */
	public function testDeactivateSetsActivatedFalseBeforeFlush(): void
	{
		$addon = $this->makeAddon('sms', true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);
		$this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('No handler'));

		$this->service->deactivate('sms');

		$this->assertFalse($addon->isActivated());
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::deactivate
	 */
	public function testDeactivateReturnsTrueWhenNoHandlerExists(): void
	{
		$addon = $this->makeAddon('sms', true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);
		$this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('No handler'));

		$this->assertTrue($this->service->deactivate('sms'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::deactivate
	 */
	public function testDeactivateCallsOnDeactivateOnHandlerAndReturnsResult(): void
	{
		$addon = $this->makeAddon('sms', true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);

		$this->handlerMock->expects($this->once())->method('onDeactivate')->with($addon)->willReturn(true);
		$this->resolverMock->method('resolve')->with('sms', $addon)->willReturn($this->handlerMock);

		$this->assertTrue($this->service->deactivate('sms'));
	}

	// -------------------------------------------------------------------------
	// hide()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::hide
	 */
	public function testHideReturnsFalseWhenAddonNotFound(): void
	{
		$this->repositoryMock->method('getByName')->willReturn(null);

		$this->assertFalse($this->service->hide('automation'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::hide
	 */
	public function testHideReturnsTrueImmediatelyWhenAlreadyHidden(): void
	{
		$addon = $this->makeAddon('automation', false, false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->expects($this->never())->method('flush');

		$this->assertTrue($this->service->hide('automation'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::hide
	 */
	public function testHideReturnsFalseWhenFlushFails(): void
	{
		$addon = $this->makeAddon('automation', false, true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(false);

		$this->assertFalse($this->service->hide('automation'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::hide
	 */
	public function testHideSetsDisplayedFalseAndReturnsTrue(): void
	{
		$addon = $this->makeAddon('automation', false, true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);

		$result = $this->service->hide('automation');

		$this->assertTrue($result);
		$this->assertFalse($addon->isDisplayed());
	}

	// -------------------------------------------------------------------------
	// show()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::show
	 */
	public function testShowReturnsFalseWhenAddonNotFound(): void
	{
		$this->repositoryMock->method('getByName')->willReturn(null);

		$this->assertFalse($this->service->show('choices'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::show
	 */
	public function testShowReturnsTrueImmediatelyWhenAlreadyDisplayedAndNotSuggested(): void
	{
		// displayed=true AND suggested=false → early return
		$addon = $this->makeAddon('choices', false, true, false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->expects($this->never())->method('flush');

		$this->assertTrue($this->service->show('choices'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::show
	 */
	public function testShowFlushesWhenDisplayedButAlsoSuggested(): void
	{
		// displayed=true AND suggested=true → should flush (no early return)
		$addon = $this->makeAddon('choices', false, true, true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->expects($this->once())->method('flush')->willReturn(true);

		$result = $this->service->show('choices');

		$this->assertTrue($result);
		$this->assertTrue($addon->isDisplayed());
		$this->assertFalse($addon->isSuggested());
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::show
	 */
	public function testShowFlushesWhenNotDisplayed(): void
	{
		$addon = $this->makeAddon('choices', false, false, false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->expects($this->once())->method('flush')->willReturn(true);

		$result = $this->service->show('choices');

		$this->assertTrue($result);
		$this->assertTrue($addon->isDisplayed());
		$this->assertFalse($addon->isSuggested());
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::show
	 */
	public function testShowReturnsFalseWhenFlushFails(): void
	{
		$addon = $this->makeAddon('choices', false, false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(false);

		$this->assertFalse($this->service->show('choices'));
	}

	// -------------------------------------------------------------------------
	// removeSuggest()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::removeSuggest
	 */
	public function testRemoveSuggestReturnsFalseWhenAddonNotFound(): void
	{
		$this->repositoryMock->method('getByName')->willReturn(null);

		$this->assertFalse($this->service->removeSuggest('messenger'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::removeSuggest
	 */
	public function testRemoveSuggestReturnsTrueImmediatelyWhenNotSuggested(): void
	{
		$addon = $this->makeAddon('messenger', false, true, false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->expects($this->never())->method('flush');

		$this->assertTrue($this->service->removeSuggest('messenger'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::removeSuggest
	 */
	public function testRemoveSuggestReturnsFalseWhenFlushFails(): void
	{
		$addon = $this->makeAddon('messenger', false, true, true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(false);

		$this->assertFalse($this->service->removeSuggest('messenger'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::removeSuggest
	 */
	public function testRemoveSuggestSetsSuggestedFalseAndReturnsTrue(): void
	{
		$addon = $this->makeAddon('messenger', false, true, true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);

		$result = $this->service->removeSuggest('messenger');

		$this->assertTrue($result);
		$this->assertFalse($addon->isSuggested());
	}

	// -------------------------------------------------------------------------
	// suggest()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::suggest
	 */
	public function testSuggestReturnsFalseWhenAddonNotFound(): void
	{
		$this->repositoryMock->method('getByName')->willReturn(null);

		$this->assertFalse($this->service->suggest('import'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::suggest
	 */
	public function testSuggestReturnsTrueImmediatelyWhenAlreadySuggested(): void
	{
		$addon = $this->makeAddon('import', false, true, true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->expects($this->never())->method('flush');

		$this->assertTrue($this->service->suggest('import'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::suggest
	 */
	public function testSuggestReturnsFalseWhenFlushFails(): void
	{
		$addon = $this->makeAddon('import', false, true, false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(false);

		$this->assertFalse($this->service->suggest('import'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::suggest
	 */
	public function testSuggestSetsSuggestedTrueAndReturnsTrue(): void
	{
		$addon = $this->makeAddon('import', false, true, false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);

		$result = $this->service->suggest('import');

		$this->assertTrue($result);
		$this->assertTrue($addon->isSuggested());
	}

	// -------------------------------------------------------------------------
	// toggle()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::toggle
	 */
	public function testToggleTrueDelegatesToActivate(): void
	{
		$addon = $this->makeAddon('payment', false);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);
		$this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('No handler'));

		$result = $this->service->toggle('payment', true);

		$this->assertTrue($result);
		$this->assertTrue($addon->isActivated());
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::toggle
	 */
	public function testToggleFalseDelegatesToDeactivate(): void
	{
		$addon = $this->makeAddon('payment', true);
		$this->repositoryMock->method('getByName')->willReturn($addon);
		$this->repositoryMock->method('flush')->willReturn(true);
		$this->resolverMock->method('resolve')->willThrowException(new \RuntimeException('No handler'));

		$result = $this->service->toggle('payment', false);

		$this->assertTrue($result);
		$this->assertFalse($addon->isActivated());
	}

	// -------------------------------------------------------------------------
	// getAddons()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::getAddons
	 */
	public function testGetAddonsReturnsRepositoryResult(): void
	{
		$addons = [
			$this->makeAddon('payment'),
			$this->makeAddon('sms'),
		];
		$this->repositoryMock->method('get')->willReturn($addons);

		$result = $this->service->getAddons();

		$this->assertSame($addons, $result);
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::getAddons
	 */
	public function testGetAddonsReturnsEmptyArrayWhenNoAddons(): void
	{
		$this->repositoryMock->method('get')->willReturn([]);

		$this->assertSame([], $this->service->getAddons());
	}

	// -------------------------------------------------------------------------
	// getVisibleAddons()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::getVisibleAddons
	 */
	public function testGetVisibleAddonsCallsRepositoryWithCorrectFilters(): void
	{
		$addons = [$this->makeAddon('payment', true, true)];
		$this->repositoryMock
			->expects($this->once())
			->method('getItemsByFields')
			->with(['displayed' => 1, 'suggested' => 1], true, 'OR')
			->willReturn($addons);

		$result = $this->service->getVisibleAddons();

		$this->assertSame($addons, $result);
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::getVisibleAddons
	 */
	public function testGetVisibleAddonsReturnsEmptyArrayWhenNoneVisible(): void
	{
		$this->repositoryMock->method('getItemsByFields')->willReturn([]);

		$this->assertSame([], $this->service->getVisibleAddons());
	}

	// -------------------------------------------------------------------------
	// getAddon()
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::getAddon
	 */
	public function testGetAddonReturnsEntityWhenFound(): void
	{
		$addon = $this->makeAddon('payment');
		$this->repositoryMock->method('getByName')->with('payment')->willReturn($addon);

		$this->assertSame($addon, $this->service->getAddon('payment'));
	}

	/**
	 * @covers \Tchooz\Services\Addons\AddonService::getAddon
	 */
	public function testGetAddonReturnsNullWhenNotFound(): void
	{
		$this->repositoryMock->method('getByName')->with('unknown')->willReturn(null);

		$this->assertNull($this->service->getAddon('unknown'));
	}
}
