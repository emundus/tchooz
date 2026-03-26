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
use Tchooz\EmundusResponse;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\List\ListResult;
use Tchooz\Repositories\Actions\GroupAccessRepository;
use Tchooz\Repositories\Groups\GroupRepository;

if (!class_exists('EmundusControllerGroups'))
{
	require_once JPATH_SITE . '/components/com_emundus/controllers/groups.php';
}

/**
 * @package     Unit\Component\Emundus\Controller
 *
 * @since       version 1.0.0
 * @covers      \EmundusControllerGroups
 */
class GroupsControllerTest extends TestCase
{
	private MockObject $controller;
	private MockObject $mockInput;
	private MockObject $mockGroupRepository;
	private MockObject $mockGroupAccessRepository;
	private MockObject $mockUser;

	private GroupEntity $mockGroup;

	protected function setUp(): void
	{
		$this->mockInput = $this->getMockBuilder(Input::class)
			->disableOriginalConstructor()
			->addMethods(['getInt', 'getString'])
			->getMock();

		$this->mockGroupRepository = $this->createMock(GroupRepository::class);
		$this->mockGroupAccessRepository = $this->createMock(GroupAccessRepository::class);
		$this->mockUser            = $this->createMock(User::class);
		$this->mockUser->id        = 42;

		$this->controller = $this->getMockBuilder(\EmundusControllerGroups::class)
			->setConstructorArgs(['config' => ['base_path' => JPATH_SITE . '/components/com_emundus']])
			->onlyMethods(['getBaseUri', 'checkToken'])
			->getMock();
		$this->controller->method('getBaseUri')->willReturn('https://example.com/');
		$this->controller->method('checkToken')->willReturn(true);

		$this->mockGroup = new GroupEntity(
			1,
			'Test Group',
			'A test group description',
			true,
			[],
			false,
			false,
			[],
			[],
			[],
			'label-blue-2'
		);

		$this->controller->setInput($this->mockInput);
		$this->controller->setGroupRepository($this->mockGroupRepository);
		$this->controller->setGroupAccessRepository($this->mockGroupAccessRepository);
		$this->controller->setUser($this->mockUser);
	}

	// =====================
	// getgroup tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::getgroup
	 */
	public function testGetGroupThrowsWhenNoIdProvided(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->getgroup();
	}

	/**
	 * @covers \EmundusControllerGroups::getgroup
	 */
	public function testGetGroupThrowsWhenGroupNotFound(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(999);
		$this->mockGroupRepository->method('getById')->with(999)->willReturn(null);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(404);

		$this->controller->getgroup();
	}

	/**
	 * @covers \EmundusControllerGroups::getgroup
	 */
	public function testGetGroupSucceeds(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(1);
		$this->mockGroupRepository->method('getById')->with(1)->willReturn($this->mockGroup);

		$response = $this->controller->getgroup();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	// =====================
	// savegroup tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::savegroup
	 */
	public function testSaveGroupThrowsWhenLabelEmpty(): void
	{
		$this->mockInput->method('getInt')->willReturnMap([
			['id', 0, 0],
			['published', 0, 1],
			['anonymize', 0, 0],
			['filter_status', 0, 0],
		]);
		$this->mockInput->method('getString')->willReturnMap([
			['label', '', ''],
			['description', '', 'desc'],
			['class', '', ''],
			['status', '', ''],
			['visible_groups', '', ''],
			['visible_attachments', '', ''],
		]);

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->savegroup();
	}

	/**
	 * @covers \EmundusControllerGroups::savegroup
	 */
	public function testSaveGroupCreatesNewGroup(): void
	{
		$this->mockInput->method('getInt')->willReturnMap([
			['id', 0, 0],
			['published', 0, 1],
			['anonymize', 0, 0],
			['filter_status', 0, 0],
		]);
		$this->mockInput->method('getString')->willReturnMap([
			['label', '', 'New Group'],
			['description', '', 'A new group'],
			['class', '', ''],
			['status', '', ''],
			['visible_groups', '', ''],
			['visible_attachments', '', ''],
		]);

		$this->mockGroupRepository->method('flush')->willReturn(true);

		$response = $this->controller->savegroup();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerGroups::savegroup
	 */
	public function testSaveGroupUpdatesExistingGroup(): void
	{
		$this->mockInput->method('getInt')->willReturnMap([
			['id', 0, 1],
			['published', 0, 0],
			['anonymize', 0, 1],
			['filter_status', 0, 0],
		]);
		$this->mockInput->method('getString')->willReturnMap([
			['label', '', 'Updated Group'],
			['description', '', 'Updated description'],
			['class', '', ''],
			['status', '', ''],
			['visible_groups', '', ''],
			['visible_attachments', '', ''],
		]);

		$this->mockGroupRepository->method('getById')->with(1)->willReturn($this->mockGroup);
		$this->mockGroupRepository->method('flush')->willReturn(true);

		$response = $this->controller->savegroup();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerGroups::savegroup
	 */
	public function testSaveGroupThrowsWhenGroupNotFoundOnUpdate(): void
	{
		$this->mockInput->method('getInt')->willReturnMap([
			['id', 0, 999],
			['published', 0, 1],
			['anonymize', 0, 0],
			['filter_status', 0, 0],
		]);
		$this->mockInput->method('getString')->willReturnMap([
			['label', '', 'Updated Group'],
			['description', '', 'desc'],
			['class', '', ''],
			['status', '', ''],
			['visible_groups', '', ''],
			['visible_attachments', '', ''],
		]);

		$this->mockGroupRepository->method('getById')->with(999)->willReturn(null);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(404);

		$this->controller->savegroup();
	}

	// =====================
	// deletegroup tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::deletegroup
	 */
	public function testDeleteGroupThrowsWhenNoIdProvided(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);
		$this->mockInput->method('getString')->with('ids')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->deletegroup();
	}

	/**
	 * @covers \EmundusControllerGroups::deletegroup
	 */
	public function testDeleteGroupSucceedsWithSingleId(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(1);
		$this->mockGroupRepository->method('delete')->with(1)->willReturn(true);

		$response = $this->controller->deletegroup();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerGroups::deletegroup
	 */
	public function testDeleteGroupSucceedsWithMultipleIds(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);
		$this->mockInput->method('getString')->with('ids')->willReturn('1,2,3');
		$this->mockGroupRepository->method('delete')->willReturn(true);

		$response = $this->controller->deletegroup();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerGroups::deletegroup
	 */
	public function testDeleteGroupThrowsWhenDeleteFails(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(1);
		$this->mockGroupRepository->method('delete')->with(1)->willReturn(false);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(500);

		$this->controller->deletegroup();
	}

	// =====================
	// duplicategroup tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::duplicategroup
	 */
	public function testDuplicateGroupThrowsWhenNoIdProvided(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);
		$this->mockInput->method('getString')->with('input', '')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->duplicategroup();
	}

	/**
	 * @covers \EmundusControllerGroups::duplicategroup
	 */
	public function testDuplicateGroupThrowsWhenGroupNotFound(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(999);
		$this->mockInput->method('getString')->with('input', '')->willReturn('');
		$this->mockGroupRepository->method('getById')->with(999)->willReturn(null);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(404);

		$this->controller->duplicategroup();
	}

	/**
	 * @covers \EmundusControllerGroups::duplicategroup
	 */
	public function testDuplicateGroupSucceedsWithDefaultName(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(1);
		$this->mockInput->method('getString')->with('input', '')->willReturn('');
		$this->mockGroupRepository->method('getById')->with(1)->willReturn($this->mockGroup);
		$this->mockGroupRepository->method('flush')->willReturn(true);

		$response = $this->controller->duplicategroup();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerGroups::duplicategroup
	 */
	public function testDuplicateGroupSucceedsWithCustomName(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(1);
		$this->mockInput->method('getString')->with('input', '')->willReturn('Custom Copy Name');
		$this->mockGroupRepository->method('getById')->with(1)->willReturn($this->mockGroup);
		$this->mockGroupRepository->method('flush')->willReturn(true);
		$this->mockGroupAccessRepository->method('flush')->willReturn(true);

		$response = $this->controller->duplicategroup();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerGroups::duplicategroup
	 */
	public function testDuplicateGroupThrowsWhenFlushFails(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(1);
		$this->mockInput->method('getString')->with('input', '')->willReturn('');
		$this->mockGroupRepository->method('getById')->with(1)->willReturn($this->mockGroup);
		$this->mockGroupRepository->method('flush')->willReturn(false);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(500);

		$this->controller->duplicategroup();
	}

	// =====================
	// getallgroups tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::getallgroups
	 */
	public function testGetAllGroupsReturnsResponse(): void
	{
		$this->mockInput->method('getString')->willReturnMap([
			['sort', 'ASC', 'ASC'],
			['recherche', '', ''],
			['program', '', ''],
			['order_by', 'id', 'id'],
		]);
		$this->mockInput->method('getInt')->willReturnMap([
			['lim', 0, 10],
			['page', 0, 1],
		]);

		$listResult = new ListResult([], 0);
		$this->mockGroupRepository->method('getList')->willReturn($listResult);
		$this->mockGroupRepository->method('buildOrderBy')->willReturn('esg.id ASC');

		$response = $this->controller->getallgroups();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertArrayHasKey('datas', $response->getData());
		$this->assertArrayHasKey('count', $response->getData());
	}

	/**
	 * @covers \EmundusControllerGroups::getallgroups
	 */
	public function testGetAllGroupsWithGroups(): void
	{
		$this->mockInput->method('getString')->willReturnMap([
			['sort', 'ASC', 'ASC'],
			['recherche', '', ''],
			['program', '', ''],
			['order_by', 'id', 'id'],
		]);
		$this->mockInput->method('getInt')->willReturnMap([
			['lim', 0, 10],
			['page', 0, 1],
		]);

		$listResult = new ListResult([$this->mockGroup], 1);
		$this->mockGroupRepository->method('getList')->willReturn($listResult);
		$this->mockGroupRepository->method('buildOrderBy')->willReturn('esg.id ASC');

		$response = $this->controller->getallgroups();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertEquals(1, $response->getData()['count']);
		$this->assertNotEmpty($response->getData()['datas']);
	}

	// =====================
	// associateprograms tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::associateprograms
	 */
	public function testAssociateProgramsThrowsWhenNoGroupId(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(0);
		$this->mockInput->method('getString')->with('program_codes', '')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->associateprograms();
	}

	/**
	 * @covers \EmundusControllerGroups::associateprograms
	 */
	public function testAssociateProgramsThrowsWhenGroupNotFound(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(999);
		$this->mockInput->method('getString')->with('program_codes', '')->willReturn('PROG1');
		$this->mockGroupRepository->method('getById')->with(999)->willReturn(null);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(404);

		$this->controller->associateprograms();
	}

	/**
	 * @covers \EmundusControllerGroups::associateprograms
	 */
	public function testAssociateProgramsThrowsWhenFlushFails(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(1);
		$this->mockInput->method('getString')->with('program_codes', '')->willReturn('PROG1');
		$this->mockGroupRepository->method('getById')->with(1)->willReturn($this->mockGroup);
		$this->mockGroupRepository->method('flush')->willReturn(false);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(500);

		$this->controller->associateprograms();
	}

	/**
	 * @covers \EmundusControllerGroups::associateprograms
	 */
	public function testAssociateProgramsSucceeds(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(1);
		$this->mockInput->method('getString')->with('program_codes', '')->willReturn('PROG1,PROG2');
		$this->mockGroupRepository->method('getById')->with(1)->willReturn($this->mockGroup);
		$this->mockGroupRepository->method('flush')->willReturn(true);

		$response = $this->controller->associateprograms();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	// =====================
	// getaccessrights tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::getaccessrights
	 */
	public function testGetAccessRightsThrowsWhenNoGroupId(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->getaccessrights();
	}

	/**
	 * @covers \EmundusControllerGroups::getaccessrights
	 */
	public function testGetAccessRightsThrowsWhenGroupNotFound(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(999);
		$this->mockGroupRepository->method('getById')->with(999)->willReturn(null);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(404);

		$this->controller->getaccessrights();
	}

	// =====================
	// updateaccessrights tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::updateaccessrights
	 */
	public function testUpdateAccessRightsThrowsWhenNoGroupId(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->updateaccessrights();
	}

	/**
	 * @covers \EmundusControllerGroups::updateaccessrights
	 */
	public function testUpdateAccessRightsThrowsWhenNoAccessRights(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(1);
		$this->mockInput->method('getString')->with('access_rights', '')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->updateaccessrights();
	}

	/**
	 * @covers \EmundusControllerGroups::updateaccessrights
	 */
	public function testUpdateAccessRightsThrowsWhenGroupNotFound(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(999);
		$this->mockInput->method('getString')->with('access_rights', '')->willReturn('[{"action_id":1,"id":0,"crud":{"create":1,"read":1,"update":0,"delete":0}}]');
		$this->mockGroupRepository->method('getById')->with(999)->willReturn(null);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(404);

		$this->controller->updateaccessrights();
	}

	// =====================
	// getusersgroup tests
	// =====================

	/**
	 * @covers \EmundusControllerGroups::getusersgroup
	 */
	public function testGetUsersGroupThrowsWhenNoGroupId(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);

		$this->controller->getusersgroup();
	}

	/**
	 * @covers \EmundusControllerGroups::getusersgroup
	 */
	public function testGetUsersGroupThrowsWhenGroupNotFound(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(999);
		$this->mockGroupRepository->method('getById')->with(999)->willReturn(null);

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(404);

		$this->controller->getusersgroup();
	}

	/**
	 * @covers \EmundusControllerGroups::getusersgroup
	 */
	public function testGetUsersGroupSucceeds(): void
	{
		$this->mockInput->method('getInt')->with('group_id', 0)->willReturn(1);
		$this->mockGroupRepository->method('getById')->with(1)->willReturn($this->mockGroup);

		$response = $this->controller->getusersgroup();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertIsArray($response->getData());
	}
}
