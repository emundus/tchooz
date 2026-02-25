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
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Repositories\Campaigns\CampaignRepository;

if (!class_exists('EmundusModelCampaign'))
{
	require_once JPATH_SITE . '/components/com_emundus/models/campaign.php';
}
if (!class_exists('EmundusControllerCampaign'))
{
	require_once JPATH_SITE . '/components/com_emundus/controllers/campaign.php';
}

class CampaignControllerTest extends TestCase
{
	private MockObject $controller;
	private MockObject $mockInput;
	private MockObject $mockCampaignModel;
	private MockObject $mockUser;

	protected function setUp(): void
	{
		// Mock dependencies
		$this->mockInput         = $this->getMockBuilder(Input::class)
			->disableOriginalConstructor()
			->addMethods(['getInt', 'getString'])  // declares methods that don't formally exist
			->getMock();
		$mockCampaignRepository  = $this->createMock(CampaignRepository::class);
		$this->mockCampaignModel = $this->createMock(\EmundusModelCampaign::class);
		$this->mockUser          = $this->createMock(User::class);
		$this->mockUser->id      = 42;

		$this->controller = $this->getMockBuilder(\EmundusControllerCampaign::class)
			->setConstructorArgs(['config' => ['base_path' => JPATH_SITE . '/components/com_emundus']])
			->onlyMethods(['getBaseUri', 'checkToken'])  // we want to test access separately
			->getMock();
		$this->controller->method('getBaseUri')->willReturn('https://example.com/');
		$this->controller->method('checkToken')->willReturn(true);

		$mockCampaign = new CampaignEntity(
			label: 'Test Campaign',
			start_date: new \DateTime('2024-01-01 00:00:00'),
			end_date: new \DateTime('2024-12-31 23:59:59'),
			program: null,
			year: '2024',
			id: 123,
			createdBy: 42
		);

		// Inject into controller
		$this->controller->setInput($this->mockInput);
		$this->controller->setMCampaign($this->mockCampaignModel);
		$this->controller->setCampaignRepository($mockCampaignRepository);
		$this->controller->setUser($this->mockUser);

		$mockCampaignRepository->method('getById')->willReturn($mockCampaign);
	}

	/**
	 * @covers \EmundusControllerCampaign::unpublishcampaign
	 */
	public function testUnpublishCampaignThrowsWhenNoIdProvided(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);
		$this->mockInput->method('getString')->with('ids')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->unpublishcampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::unpublishcampaign
	 */
	public function testUnpublishCampaignThrowsWhenModelFails(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('unpublishCampaign')->with([123], 42)->willReturn(false);   // simulate failure

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);

		$this->controller->unpublishcampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::unpublishcampaign
	 */
	public function testUnpublishCampaignSucceedsWithSingleId(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('unpublishCampaign')->with([123], 42)->willReturn(true);

		$response = $this->controller->unpublishcampaign();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertTrue($response->getData());
	}

	/**
	 * @covers \EmundusControllerCampaign::unpublishcampaign
	 */
	public function testUnpublishCampaignSucceedsWithMultipleIds(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);     // no single ID
		$this->mockInput->method('getString')->with('ids')->willReturn('1,2,3');
		$this->mockCampaignModel->method('unpublishCampaign')->with(['1', '2', '3'], 42)->willReturn(true);

		$response = $this->controller->unpublishcampaign();

		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::deletecampaign
	 */
	public function testDeleteCampaignThrowsWhenNoIdProvided(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);
		$this->mockInput->method('getString')->with('ids')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->deletecampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::deletecampaign
	 */
	public function testDeleteCampaignThrowsWhenModelFails(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('deleteCampaign')->with(123, true)->willReturn(false);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);

		$this->controller->deletecampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::deletecampaign
	 */
	public function testDeleteCampaignSucceeds(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('deleteCampaign')->with(123, true)->willReturn(true);

		$response = $this->controller->deletecampaign();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::publishcampaign
	 */
	public function testPublishCampaignThrowsWhenNoIdProvided(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);
		$this->mockInput->method('getString')->with('ids')->willReturn('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->publishcampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::publishcampaign
	 */
	public function testPublishCampaignReturnsFailOnModelFailure(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('publishCampaign')->with([123], 42)->willReturn(['success' => false, 'message' => 'ERROR']);

		$response = $this->controller->publishcampaign();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_INTERNAL_SERVER_ERROR, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::publishcampaign
	 */
	public function testPublishCampaignSucceeds(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('publishCampaign')->with([123], 42)->willReturn(['success' => true, 'message' => 'CAMPAIGN_PUBLISHED']);

		$response = $this->controller->publishcampaign();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::duplicatecampaign
	 */
	public function testDuplicateCampaignThrowsWhenNoIdProvided(): void
	{
		$this->mockInput->method('getInt')->with('id')->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->duplicatecampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::duplicatecampaign
	 */
	public function testDuplicateCampaignThrowsWhenModelFails(): void
	{
		$this->mockInput->method('getInt')->with('id')->willReturn(123);
		$this->mockCampaignModel->method('duplicateCampaign')->with(123)->willReturn(false);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);

		$this->controller->duplicatecampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::duplicatecampaign
	 */
	public function testDuplicateCampaignSucceeds(): void
	{
		$this->mockInput->method('getInt')->with('id')->willReturn(123);
		$this->mockCampaignModel->method('duplicateCampaign')->with(123)->willReturn(true);

		$response = $this->controller->duplicatecampaign();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::getyears
	 */
	public function testGetYearsReturnsOk(): void
	{
		$this->mockCampaignModel->method('getYears')->willReturn([2022, 2023]);

		$response = $this->controller->getyears();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertEquals([2022, 2023], $response->getData());
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignbyid
	 */
	public function testGetCampaignByIdThrowsWhenNoId(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->getcampaignbyid();
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignbyid
	 */
	public function testGetCampaignByIdThrowsWhenModelReturnsEmpty(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('getCampaignDetailsById')->with(123)->willReturn(null);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_NOT_FOUND);

		$this->controller->getcampaignbyid();
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignbyid
	 */
	public function testGetCampaignByIdReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('getCampaignDetailsById')->with(123)->willReturn(['id' => 123]);

		$response = $this->controller->getcampaignbyid();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertEquals(['id' => 123], $response->getData());
	}

	/**
	 * @covers \EmundusControllerCampaign::updateprofile
	 */
	public function testUpdateProfileThrowsWhenMissingParams(): void
	{
		$this->mockInput->method('getInt')->withConsecutive(['profile', 0], ['campaign', 0])->willReturnOnConsecutiveCalls(0, 0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->updateprofile();
	}

	/**
	 * @covers \EmundusControllerCampaign::updateprofile
	 */
	public function testUpdateProfileThrowsWhenModelFails(): void
	{
		$this->mockInput->method('getInt')->withConsecutive(['profile', 0], ['campaign', 0])->willReturnOnConsecutiveCalls(1, 2);
		$this->mockCampaignModel->method('updateProfile')->with(1, 2)->willReturn(false);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);

		$this->controller->updateprofile();
	}

	/**
	 * @covers \EmundusControllerCampaign::updateprofile
	 */
	public function testUpdateProfileReturnsOk(): void
	{
		$this->mockInput->method('getInt')->withConsecutive(['profile', 0], ['campaign', 0])->willReturnOnConsecutiveCalls(1, 2);
		$this->mockCampaignModel->method('updateProfile')->with(1, 2)->willReturn(true);

		$response = $this->controller->updateprofile();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::pincampaign
	 */
	public function testPinCampaignThrowsWhenNoId(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->pincampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::pincampaign
	 */
	public function testPinCampaignThrowsWhenModelFails(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('pinCampaign')->with(123)->willReturn(false);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);

		$this->controller->pincampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::pincampaign
	 */
	public function testPinCampaignReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('pinCampaign')->with(123)->willReturn(true);

		$response = $this->controller->pincampaign();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::unpincampaign
	 */
	public function testUnpinCampaignThrowsWhenNoId(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->unpincampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::unpincampaign
	 */
	public function testUnpinCampaignThrowsWhenModelFails(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('unpinCampaign')->with(123)->willReturn(false);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);

		$this->controller->unpincampaign();
	}

	/**
	 * @covers \EmundusControllerCampaign::unpincampaign
	 */
	public function testUnpinCampaignReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(123);
		$this->mockCampaignModel->method('unpinCampaign')->with(123)->willReturn(true);

		$response = $this->controller->unpincampaign();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::getallitemsalias
	 */
	public function testGetAllItemsAliasReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('campaign_id', 0)->willReturn(123);
		$this->mockCampaignModel->method('getAllItemsAlias')->with(123)->willReturn(['foo' => 'bar']);

		$response = $this->controller->getallitemsalias();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertEquals(['foo' => 'bar'], $response->getData());
	}

	/**
	 * @covers \EmundusControllerCampaign::getProgrammeByCampaignID
	 */
	public function testGetProgrammeByCampaignIDThrowsWhenNoId(): void
	{
		$this->mockInput->method('getInt')->with('campaign_id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->getProgrammeByCampaignID();
	}

	/**
	 * @covers \EmundusControllerCampaign::getProgrammeByCampaignID
	 */
	public function testGetProgrammeByCampaignIDReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('campaign_id', 0)->willReturn(123);
		$this->mockCampaignModel->method('getProgrammeByCampaignID')->with(123)->willReturn(['id' => 1]);

		$response = $this->controller->getProgrammeByCampaignID();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertEquals(['id' => 1], $response->getData());
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignmoreformurl
	 */
	public function testGetCampaignMoreFormUrlThrowsWhenNoCid(): void
	{
		$this->mockInput->method('getInt')->with('cid', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->getcampaignmoreformurl();
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignmoreformurl
	 */
	public function testGetCampaignMoreFormUrlThrowsWhenModelReturnsEmpty(): void
	{
		$this->mockInput->method('getInt')->with('cid', 0)->willReturn(123);
		$this->mockCampaignModel->method('getCampaignMoreFormUrl')->with(123)->willReturn('');

		$this->expectException(\Exception::class); // NotFoundException

		$this->controller->getcampaignmoreformurl();
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignmoreformurl
	 */
	public function testGetCampaignMoreFormUrlReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('cid', 0)->willReturn(123);
		$this->mockCampaignModel->method('getCampaignMoreFormUrl')->with(123)->willReturn('url');

		$response = $this->controller->getcampaignmoreformurl();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::getCampaignsByProgramId
	 */
	public function testGetCampaignsByProgramIdThrowsWhenNoProgramId(): void
	{
		$this->mockInput->method('getInt')->with('program_id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->getCampaignsByProgramId();
	}

	/**
	 * @covers \EmundusControllerCampaign::getCampaignsByProgramId
	 */
	public function testGetCampaignsByProgramIdReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('program_id', 0)->willReturn(123);
		$this->mockCampaignModel->method('getCampaignsByProgramId')->with(123)->willReturn(['foo']);

		$response = $this->controller->getCampaignsByProgramId();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertEquals(['foo'], $response->getData());
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignlanguages
	 */
	public function testGetCampaignLanguagesThrowsWhenNoCampaignId(): void
	{
		$this->mockInput->method('getInt')->with('campaign_id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->getcampaignlanguages();
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignlanguages
	 */
	public function testGetCampaignLanguagesReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('campaign_id', 0)->willReturn(123);
		$this->mockCampaignModel->method('getCampaignLanguagesValues')->with(123)->willReturn(['fr', 'en']);

		$response = $this->controller->getcampaignlanguages();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertEquals(['fr', 'en'], $response->getData());
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignusercategories
	 */
	public function testGetCampaignUserCategoriesThrowsWhenNoCampaignId(): void
	{
		$this->mockInput->method('getInt')->with('campaign_id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->getcampaignusercategories();
	}

	/**
	 * @covers \EmundusControllerCampaign::getcampaignusercategories
	 */
	public function testGetCampaignUserCategoriesReturnsOk(): void
	{
		$this->mockInput->method('getInt')->with('campaign_id', 0)->willReturn(123);
		$this->mockCampaignModel->method('getCampaignUserCategoriesValues')->with(123)->willReturn(['cat1', 'cat2']);

		$response = $this->controller->getcampaignusercategories();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
		$this->assertEquals(['cat1', 'cat2'], $response->getData());
	}

	/**
	 * @covers \EmundusControllerCampaign::getmediasize
	 */
	public function testGetMediaSizeThrowsWhenGuest(): void
	{
		$this->mockUser->guest = true;

		$this->expectException(\Exception::class);

		$this->controller->getmediasize();
	}

	/**
	 * @covers \EmundusControllerCampaign::getimportmodel
	 */
	public function testGetImportModelThrowsWhenNoId(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->getimportmodel();
	}

	/**
	 * @covers \EmundusControllerCampaign::isimportactivated
	 */
	public function testIsImportActivatedReturnsOk(): void
	{
		$response = $this->controller->isimportactivated();

		$this->assertInstanceOf(EmundusResponse::class, $response);
		$this->assertEquals(EmundusResponse::HTTP_OK, $response->getCode());
	}

	/**
	 * @covers \EmundusControllerCampaign::needmoreinfo
	 */
	public function testNeedMoreInfoThrowsWhenNoId(): void
	{
		$this->mockInput->method('getInt')->with('id', 0)->willReturn(0);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionCode(EmundusResponse::HTTP_BAD_REQUEST);

		$this->controller->needmoreinfo();
	}
}
