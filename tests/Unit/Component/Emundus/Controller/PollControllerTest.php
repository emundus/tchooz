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
use Joomla\Input\Input;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tchooz\Controller\PollController;
use Tchooz\EmundusResponse;
use Tchooz\Repositories\Poll\PollRepository;

/**
 * @package     Unit\Component\Emundus\Controller
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Controller\PollController
 */
class PollControllerTest extends TestCase
{
	/** @var PollController&MockObject */
	private $controller;

	/** @var Input&MockObject */
	private $input;

	/** @var PollRepository&MockObject */
	private $pollRepository;

	protected function setUp(): void
	{
		parent::setUp();

		$this->input = $this->getMockBuilder(Input::class)
			->disableOriginalConstructor()
			->addMethods(['getInt', 'getString', 'getRaw'])
			->getMock();

		$this->pollRepository = $this->getMockBuilder(PollRepository::class)
			->disableOriginalConstructor()
			->getMock();

		$user = $this->createMock(User::class);
		$user->id    = 42;
		$user->email = 'coordinator@example.com';

		$this->controller = $this->getMockBuilder(PollController::class)
			->disableOriginalConstructor()
			->onlyMethods(['checkToken'])
			->getMock();
		$this->controller->method('checkToken')->willReturn(true);

		$this->controller->setInput($this->input);
		$this->controller->setUser($user);
		$this->setPrivateProperty($this->controller, 'pollRepository', $this->pollRepository);
	}

	private function setPrivateProperty(object $object, string $property, mixed $value): void
	{
		$reflection = new \ReflectionProperty(PollController::class, $property);
		$reflection->setAccessible(true);
		$reflection->setValue($object, $value);
	}

	// -------------------------------------------------------------------------
	// deletepoll
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Controller\PollController::delete
	 * @return void
	 */
	public function testDeletePollThrowsWhenNoIdProvided(): void
	{
		$this->input->method('getInt')->willReturn(0);
		$this->input->method('getString')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->delete();
	}

	/**
	 * @covers \Tchooz\Controller\PollController::delete
	 * @return void
	 */
	public function testDeletePollDeletesAndReturnsOk(): void
	{
		$this->input->method('getInt')->willReturn(5);
		$this->pollRepository->expects($this->once())->method('delete')->with(5)->willReturn(true);

		$response = $this->controller->delete();

		$this->assertInstanceOf(EmundusResponse::class, $response, 'delete should return an EmundusResponse');
		$this->assertSame(EmundusResponse::HTTP_OK, $response->getCode(), 'delete should return a 200 response on success');
	}

	// -------------------------------------------------------------------------
	// Other actions — missing id throws HTTP 400
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Controller\PollController::contactparticipants
	 * @return void
	 */
	public function testContactParticipantsThrowsWhenNoIdProvided(): void
	{
		$this->input->method('getInt')->willReturn(0);
		$this->input->method('getString')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->contactparticipants();
	}

	/**
	 * @covers \Tchooz\Controller\PollController::savepollslot
	 * @return void
	 */
	public function testSavePollSlotThrowsWhenParametersAreMissing(): void
	{
		$this->input->method('getInt')->willReturn(0);
		$this->input->method('getString')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->savepollslot();
	}

	/**
	 * @covers \Tchooz\Controller\PollController::deletepollslot
	 * @return void
	 */
	public function testDeletePollSlotThrowsWhenSlotIdIsMissing(): void
	{
		$this->input->method('getInt')->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->deletepollslot();
	}

	/**
	 * @covers \Tchooz\Controller\PollController::exportexcel
	 * @return void
	 */
	public function testExportExcelThrowsWhenNoIdProvided(): void
	{
		$this->input->method('getInt')->willReturn(0);
		$this->input->method('getString')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->exportexcel();
	}
}
