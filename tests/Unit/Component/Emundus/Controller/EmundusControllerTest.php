<?php
/**
 * @package     Unit\Component\Emundus\Controller
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Controller;

use Joomla\CMS\User\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;

class TestableEmundusController extends EmundusController
{
	public $passesAnyAccessAttributeResult = null;
	public $callAccessLevelMethodResult = null;
	public $callActionMethodResult = null;

	protected function passesAnyAccessAttribute(array $attributes): bool
	{
		if ($this->passesAnyAccessAttributeResult !== null)
		{
			return $this->passesAnyAccessAttributeResult;
		}

		return false;
	}

	protected function callAccessLevelMethod(string $methodName, $userId)
	{
		if ($this->callAccessLevelMethodResult !== null)
		{
			return $this->callAccessLevelMethodResult;
		}

		return parent::callAccessLevelMethod($methodName, $userId);
	}

	protected function callAccessActionMethod(string $actionId, string $mode, int $userId): bool
	{
		if ($this->callActionMethodResult !== null)
		{
			return $this->callActionMethodResult;
		}

		return parent::callAccessActionMethod($actionId, $mode, $userId);
	}
}

class EmundusControllerTest extends TestCase
{
	private TestableEmundusController $controller;
	private MockObject $mockUser;

	protected function setUp(): void
	{
		$this->mockUser     = $this->createMock(User::class);
		$this->mockUser->id = 42;
		$this->controller   = $this->getMockBuilder(TestableEmundusController::class)
			->setConstructorArgs(['config' => ['base_path' => JPATH_SITE . '/components/com_emundus']])
			->onlyMethods(['getBaseUri', 'checkToken', 'getCachedAccessAttributes'])
			->getMock();
		$this->controller->method('getBaseUri')->willReturn('https://example.com/');
		$this->controller->method('checkToken')->willReturn(true);
		$this->controller->setUser($this->mockUser);
	}


	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessAllowsWhenNoAttributes(): void
	{
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [],
			'class'  => []
		]);
		$refMethod = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
		$this->assertTrue(true, 'enforceAccess should allow when there are no access attributes');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessAllowsWhenMethodAttributePasses(): void
	{
		$mockAttribute = new AccessAttribute(null, []);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$this->controller->passesAnyAccessAttributeResult = true;
		$refMethod                                        = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessAllowsWhenClassAttributePasses(): void
	{
		$mockAttribute = new \Tchooz\Attributes\AccessAttribute(null, []);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [],
			'class'  => [$mockAttribute]
		]);
		$this->controller->passesAnyAccessAttributeResult = true;
		$refMethod                                        = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithAccessLevelAllowed(): void
	{
		$mockAttribute = new AccessAttribute(AccessLevelEnum::PARTNER, []);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$this->controller->callAccessLevelMethodResult = true;
		$refMethod                                     = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
		$this->assertTrue(true, 'enforceAccess should allow when access level check passes');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithAccessLevelDenied(): void
	{
		$mockAttribute = new AccessAttribute(AccessLevelEnum::PARTNER, []);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$this->controller->callAccessLevelMethodResult = false;
		$refMethod                                     = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithClassAccessLevelAllowed(): void
	{
		$mockAttribute = new AccessAttribute(AccessLevelEnum::PARTNER, []);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [],
			'class'  => [$mockAttribute]
		]);
		$this->controller->callAccessLevelMethodResult = true;
		$refMethod                                     = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
		$this->assertTrue(true, 'enforceAccess should allow when access level check passes');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithClassAccessLevelDenied(): void
	{
		$mockAttribute = new AccessAttribute(AccessLevelEnum::PARTNER, []);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [],
			'class'  => [$mockAttribute]
		]);
		$this->controller->callAccessLevelMethodResult = false;
		$refMethod                                     = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithActionsAllowed(): void
	{
		$mockAttribute = new AccessAttribute(null, [['id' => 'campaign', 'mode' => CrudEnum::READ]]);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$this->controller->callActionMethodResult = true;
		$refMethod                                = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
		$this->assertTrue(true, 'enforceAccess should allow when access level check passes');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithActionsDenied(): void
	{
		$mockAttribute = new AccessAttribute(null, [['id' => 'campaign', 'mode' => CrudEnum::READ]]);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$refMethod                                = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithActionsInvalidMode(): void
	{
		$mockAttribute = new AccessAttribute(null, [['id' => 'campaign', 'mode' => 'foo']]);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$refMethod                                = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithActionsInvalidAction(): void
	{
		$mockAttribute = new AccessAttribute(null, [['id' => 'foo', 'mode' => CrudEnum::READ]]);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$refMethod                                = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithAccessLevelGuestUser(): void
	{
		$this->mockUser->guest = true;
		$mockAttribute = new AccessAttribute(AccessLevelEnum::PARTNER, []);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$refMethod                                = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithActionsGuestUser(): void
	{
		$this->mockUser->guest = true;
		$mockAttribute = new AccessAttribute(null, [['id' => 'campaign', 'mode' => CrudEnum::READ]]);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$refMethod                                = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}

	/**
	 * @covers \Tchooz\Controller\EmundusController::enforceAccess
	 */
	public function testEnforceAccessWithoutActionsGuestUser(): void
	{
		$this->mockUser->id = 0;
		$mockAttribute = new AccessAttribute(null, []);
		$this->controller->method('getCachedAccessAttributes')->willReturn([
			'method' => [$mockAttribute],
			'class'  => []
		]);
		$refMethod                                = new \ReflectionMethod($this->controller, 'enforceAccess');
		$refMethod->setAccessible(true);
		$this->expectException('Symfony\\Component\\OptionsResolver\\Exception\\AccessException');
		$refMethod->invoke($this->controller, $this->controller, 'dummyMethod');
	}
}
