<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage  Repositories\Campaigns
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Campaigns;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Campaigns\CampaignEntity;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Campaigns\CampaignRepository
 */
class CampaignRepositoryTest extends UnitTestCase
{
	private CampaignRepository $repository;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->repository = new CampaignRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getAllCampaigns
	 * @return void
	 */
	public function testGetAllCampaigns()
	{
		$campaign1 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaign2 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaign3 = $this->repository->getById($this->dataset['campaign']);

		$campaign3->setPublished(false);
		$this->repository->flush($campaign3);

		$campaigns = $this->repository->getAllCampaigns();
		$this->assertGreaterThanOrEqual(3, $campaigns->getTotalItems(), 'The getAllCampaigns method should return an 3 CampaignEntity');
		$campaignsIds = array_map(fn($c) => $c->getId(), $campaigns->getItems());
		$this->assertContains($campaign1->getId(), $campaignsIds, 'The campaign1 id found should be in the array');
		$this->assertContains($campaign2->getId(), $campaignsIds, 'The campaign2 id found should be in the array');
		$this->assertContains($campaign3->getId(), $campaignsIds, 'The campaign3 id found should be in the array');

		$campaignsPublished = $this->repository->getAllCampaigns('DESC', '', 25, 0, 'esc.id', true);
		$campaignsIds       = array_map(fn($c) => $c->getId(), $campaignsPublished->getItems());
		$this->assertContains($campaign1->getId(), $campaignsIds, 'The campaign1 id found should be in the array');
		$this->assertContains($campaign2->getId(), $campaignsIds, 'The campaign2 id found should be in the array');
		$this->assertNotContains($campaign3->getId(), $campaignsIds, 'The campaign3 id found should not be in the array');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getAllCampaigns
	 * @return void
	 */
	public function testGetAllCampaignsWithSearch(): void
	{
		$campaign =  $this->repository->getById($this->dataset['campaign']);
		$campaign->setLabel('Unique label for search test');
		$this->repository->flush($campaign);

		$campaigns = $this->repository->getAllCampaigns('DESC', 'Unique label for search test', 25, 0, 'esc.id', false);
		$this->assertCount(1, $campaigns->getItems(), 'The getAllCampaigns method should return an array with one element');
		$this->assertEquals($campaign->getId(), $campaigns->getItems()[0]->getId(), 'The campaign id found should be the same as the original');

		$campaigns = $this->repository->getAllCampaigns('DESC', 'Label that is nowhere to be found', 25, 0, 'esc.id', false);
		$this->assertCount(0, $campaigns->getItems(), 'The getAllCampaigns method should return an empty array');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getAllCampaigns
	 * @return void
	 */
	public function testGetAllCampaignsDateFilter(): void
	{
		$ongoingCampaign = $this->repository->getById($this->dataset['campaign']);
		$ongoingCampaign->setStartDate((new \DateTime())->modify('-1 day'));
		$ongoingCampaign->setEndDate((new \DateTime())->modify('+1 day'));
		$this->repository->flush($ongoingCampaign);

		$yetToComeCampaign = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program'], 1));
		$yetToComeCampaign->setStartDate((new \DateTime())->modify('+10 day'));
		$yetToComeCampaign->setEndDate((new \DateTime())->modify('+20 day'));
		$this->repository->flush($yetToComeCampaign);

		$pastCampaign = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program'], 1));
		$pastCampaign->setStartDate((new \DateTime())->modify('-20 day'));
		$pastCampaign->setEndDate((new \DateTime())->modify('-10 day'));
		$this->repository->flush($pastCampaign);

		$listResult = $this->repository->getAllCampaigns('DESC', '', 25, 0, 'esc.id', null, null,null, 'ongoing');
		$this->assertGreaterThan(0, $listResult->getTotalItems(), 'The getAllCampaigns method should return an array with at least 1 elements for ongoing filter');
		$campaignsIds = array_map(fn($c) => $c->getId(), $listResult->getItems());
		$this->assertContains($ongoingCampaign->getId(), $campaignsIds, 'The ongoing campaign id found should be in the array');
		$this->assertNotContains($yetToComeCampaign->getId(), $campaignsIds, 'The yet to come campaign id found should not be in the array');
		$this->assertNotContains($pastCampaign->getId(), $campaignsIds, 'The past campaign id found should not be in the array');

		$listResult = $this->repository->getAllCampaigns('DESC', '', 25, 0, 'esc.id', null, null,null, 'yettocome');
		$this->assertGreaterThan(0, $listResult->getTotalItems(), 'The getAllCampaigns method should return an array with at least 1 elements for yet to come filter');
		$campaignsIds = array_map(fn($c) => $c->getId(), $listResult->getItems());
		$this->assertNotContains($ongoingCampaign->getId(), $campaignsIds, 'The ongoing campaign id found should not be in the array');
		$this->assertContains($yetToComeCampaign->getId(), $campaignsIds, 'The yet to come campaign id found should be in the array');
		$this->assertNotContains($pastCampaign->getId(), $campaignsIds, 'The past campaign id found should not be in the array');

		$listResult = $this->repository->getAllCampaigns('DESC', '', 25, 0, 'esc.id', null, null,null, 'Terminated');
		$this->assertGreaterThan(0, $listResult->getTotalItems(), 'The getAllCampaigns method should return an array with at least 1 elements for terminated filter');
		$campaignsIds = array_map(fn($c) => $c->getId(), $listResult->getItems());
		$this->assertNotContains($ongoingCampaign->getId(), $campaignsIds, 'The ongoing campaign id found should not be in the array');
		$this->assertNotContains($yetToComeCampaign->getId(), $campaignsIds, 'The yet to come campaign id found should not be in the array');
		$this->assertContains($pastCampaign->getId(), $campaignsIds, 'The past campaign id found should be in the array');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getParentCampaigns
	 * @return void
	 */
	public function testGetParentCampaigns()
	{
		$campaign1 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaign2 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaign3 = $this->repository->getById($this->dataset['campaign']);

		$campaigns = $this->repository->getParentCampaigns([$this->dataset['program']['programme_code']]);
		$this->assertIsArray($campaigns, 'The getParentCampaigns method should return an array');
		$this->assertNotEmpty($campaigns, 'The getParentCampaigns method should return an array with at least one element');
		$campaignsIds = array_map(fn($c) => $c->getId(), $campaigns);
		$this->assertContains($campaign1->getId(), $campaignsIds, 'The campaign1 id found should be in the array');
		$this->assertContains($campaign2->getId(), $campaignsIds, 'The campaign2 id found should be in the array');
		$this->assertContains($campaign3->getId(), $campaignsIds, 'The campaign3 id found should be in the array');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getChildrenCampaigns
	 * @return void
	 */
	public function testGetChildrenCampaigns()
	{
		$programRepository = new ProgramRepository();
		$program           = $programRepository->getById($this->dataset['program']['programme_id']);
		$parentCampaign    = new CampaignEntity('New parent campaign test', new \DateTime(), new \DateTime(), $program, '2050');

		if (!$this->repository->flush($parentCampaign))
		{
			$this->fail('Failed to flush parent campaign');
		}

		$childrenCampaign  = new CampaignEntity('New children campaign test', new \DateTime(), new \DateTime(), $program, '2050', 'Campaign test description', 'Short campaign test description', 1000, true, false, '', true, $parentCampaign);
		$this->repository->flush($childrenCampaign);

		$this->assertNotEmpty($parentCampaign->getId(), 'The parent campaign id should not be empty after flush');
		$this->assertNotEmpty($childrenCampaign->getId(), 'The children campaign id should not be empty after flush');
		$this->assertEquals($parentCampaign->getId(), $childrenCampaign->getParent()->getId(), 'The parent campaign id should be the parent');

		$campaigns = $this->repository->getChildrenCampaigns($parentCampaign->getId());
		$this->assertIsArray($campaigns, 'The getChildrenCampaigns method should return an array');
		$this->assertNotEmpty($campaigns, 'The getChildrenCampaigns method should return an array with at least one element');
		$campaignsIds = array_map(fn($c) => $c->getId(), $campaigns);
		$this->assertContains($childrenCampaign->getId(), $campaignsIds, 'The childrenCampaign id found should be in the array');
		$this->assertNotContains($this->dataset['campaign'], $campaignsIds, 'The original campaign id found should not be in the array, as it is not a child of the parent campaign');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getById
	 * @return void
	 */
	public function testGetById()
	{
		$campaign = $this->repository->getById($this->dataset['campaign']);
		$this->assertNotNull($campaign, 'The getById method should return a campaign entity');
		$this->assertEquals($this->dataset['campaign'], $campaign->getId(), 'The campaign entity found should have the same ID as the original');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getByLabel
	 * @return void
	 */
	public function testGetByLabel()
	{
		$campaign        = $this->repository->getById($this->dataset['campaign']);
		$campaignByLabel = $this->repository->getByLabel($campaign->getLabel());
		$this->assertNotNull($campaignByLabel, 'The getByLabel method should return a campaign entity');
		$this->assertEquals($campaign->getId(), $campaignByLabel->getId(), 'The campaign entity found should have the same ID as the original');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getCampaignIdsByPrograms
	 * @return void
	 */
	public function testGetCampaignIdsByPrograms()
	{
		$campaign1 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaign2 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaign3 = $this->repository->getById($this->dataset['campaign']);

		$campaignsIds = $this->repository->getCampaignIdsByPrograms([$this->dataset['program']['programme_id']]);
		$this->assertIsArray($campaignsIds, 'The getCampaignIdsByPrograms method should return an array');
		$this->assertNotEmpty($campaignsIds, 'The getCampaignIdsByPrograms method should return an array with at least one element');
		$this->assertContains($campaign1->getId(), $campaignsIds, 'The campaign1 id found should be in the array');
		$this->assertContains($campaign2->getId(), $campaignsIds, 'The campaign2 id found should be in the array');
		$this->assertContains($campaign3->getId(), $campaignsIds, 'The campaign3 id found should be in the array');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getCampaignsByFnums
	 * @return void
	 */
	public function testGetCampaignsByFnums()
	{
		$campaign1 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaign2 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaign3 = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));

		$fnum1 = $this->h_dataset->createSampleFile($campaign1->getId(), $this->dataset['applicant']);
		$fnum2 = $this->h_dataset->createSampleFile($campaign2->getId(), $this->dataset['applicant']);
		$fnum3 = $this->h_dataset->createSampleFile($campaign3->getId(), $this->dataset['applicant']);

		$campaigns = $this->repository->getCampaignsByFnums([$fnum1, $fnum2, $fnum3]);
		$this->assertIsArray($campaigns, 'The getCampaignsByFnums method should return an array');
		$this->assertNotEmpty($campaigns, 'The getCampaignsByFnums method should return an array with at least one element');
		$campaignsIds = array_map(fn($c) => $c->getId(), $campaigns);
		$this->assertContains($campaign1->getId(), $campaignsIds, 'The campaign1 id found should be in the array');
		$this->assertContains($campaign2->getId(), $campaignsIds, 'The campaign2 id found should be in the array');
		$this->assertContains($campaign3->getId(), $campaignsIds, 'The campaign3 id found should be in the array');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getLinkedProgramsIds
	 * @return void
	 */
	public function testGetLinkedProgramsIds()
	{
		$programRepository = new ProgramRepository();
		$program           = $programRepository->getById($this->dataset['program']['programme_id']);
		$parentCampaign    = new CampaignEntity('New children campaign test', new \DateTime(), new \DateTime(), $program, '2050');
		$this->repository->flush($parentCampaign);
		$this->assertNotEmpty($parentCampaign->getId());
		$fnum = $this->h_dataset->createSampleFile($parentCampaign->getId(), $this->dataset['applicant']);

		$childrenProgramArray = $this->h_dataset->createSampleProgram('Children Program', 1);
		$childrenProgram = $programRepository->getById($childrenProgramArray['programme_id']);
		$childrenCampaign  = new CampaignEntity('New children campaign test', new \DateTime(), new \DateTime(), $childrenProgram, '2050', 'Campaign test description', 'Short campaign test description', 1000, true, false, '', true, $parentCampaign);
		$this->repository->flush($childrenCampaign);

		$programsIds = $this->repository->getLinkedProgramsIds($parentCampaign->getId());
		$this->assertIsArray($programsIds, 'The getLinkedProgramsIds method should return an array');
		$this->assertNotEmpty($programsIds, 'The getLinkedProgramsIds method should return an array with at least one element');
		$this->assertContains($childrenProgram->getId(), $programsIds, 'The child program id should be in the array');

		$programsIds = $this->repository->getLinkedProgramsIds($parentCampaign->getId(), $fnum);
		$this->assertIsArray($programsIds, 'The getLinkedProgramsIds method should return an array');
		$this->assertEmpty($programsIds, 'The getLinkedProgramsIds method should return an empty array because fnum has not set a choice for the parent campaign');

		$programsIds = $this->repository->getLinkedProgramsIds($childrenCampaign->getId());
		$this->assertIsArray($programsIds, 'The getLinkedProgramsIds method should return an array');
		$this->assertNotEmpty($programsIds, 'The getLinkedProgramsIds method should return an array with at least one element');
		$this->assertContains($program->getId(), $programsIds, 'The parent program id should be in the array');
	}


	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getCampaignDefaultStep
	 * @return void
	 */
	public function testGetCampaignDefaultStep(): void
	{
		$defaultStep = $this->repository->getCampaignDefaultStep($this->dataset['campaign']);
		$campaign = $this->repository->getById($this->dataset['campaign']);

		if ($campaign->getProfileId() === 1000)
		{
			$this->assertEmpty($defaultStep, 'The default step should not be empty, because used profile is "noprofile"');

			$campaign->setProfileId(1001);
			$this->repository->flush($campaign);
			$defaultStep = $this->repository->getCampaignDefaultStep($this->dataset['campaign']);
			$this->assertNotEmpty($defaultStep, 'The default step should not be empty, because used profile is not "noprofile"');
			$this->assertInstanceOf(StepEntity::class, $defaultStep);
			$this->assertEmpty($defaultStep->getId(), 'The step id should be empty, because default step is not saved in database');
			$this->assertNotEmpty($defaultStep->getLabel(), 'The step label is based on the profile label, so it should not be empty');
		}
		else
		{
			$this->assertNotEmpty($defaultStep, 'The default step should not be empty');
			$this->assertInstanceOf(StepEntity::class, $defaultStep);
			$this->assertNotEmpty($defaultStep->getId(), 'The step id should not be empty, because default step is saved in database');
			$this->assertNotEmpty($defaultStep->getLabel(), 'The step label should not be empty');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::flush
	 * @return void
	 */
	public function testFlush()
	{
		$programRepository = new ProgramRepository();
		$program           = $programRepository->getById($this->dataset['program']['programme_id']);
		$campaign          = new CampaignEntity('New campaign test', new \DateTime(), new \DateTime(), $program, '2050', 'Campaign test description', 'Short campaign test description', 1000);

		$saved = $this->repository->flush($campaign);
		$this->assertTrue($saved, 'Campaign should be saved successfully.');
		$this->assertGreaterThan(0, $campaign->getId(), 'Campaign ID should be set after saving.');
		$this->assertEquals('New campaign test', $campaign->getLabel(), 'Campaign label should be set correctly.');

		$campaign->setLabel('Updated campaign test');
		$updated = $this->repository->flush($campaign);
		$this->assertTrue($updated, 'Campaign should be updated successfully.');
		$this->assertEquals('Updated campaign test', $campaign->getLabel(), 'Campaign label should be updated correctly.');

		$campaign->setLabel('Way too long label that exceeds the maximum length allowed for a campaign label in the database, which should cause the flush method to fail and return false. This is to test the validation of the label length in the flush method and ensure that it does not allow saving a campaign with an invalid label that could cause database errors or data integrity issues. We must try a text that is long enough to exceed the limit, which is usually around 255 characters for a VARCHAR field in the database. This will help us verify that the flush method correctly handles validation errors and does not save invalid data.');
		$failed = $this->repository->flush($campaign);
		$this->assertFalse($failed, 'Campaign with too long label should not be saved successfully.');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getParameters
	 * @return void
	 */
	public function testGetParameters()
	{
		$parameters = $this->repository->getParameters();
		$this->assertIsArray($parameters, 'The getParameters method should return an array');
		$this->assertArrayHasKey('campaign_date_format', $parameters, 'The getParameters method should return an array with campaign_date_format parameter key');
		$this->assertArrayHasKey('campaign_show_start_date', $parameters, 'The getParameters method should return an array with campaign_show_start_date parameter key');
		$this->assertArrayHasKey('campaign_show_end_date', $parameters, 'The getParameters method should return an array with campaign_show_end_date parameter key');
		$this->assertArrayHasKey('campaign_show_timezone', $parameters, 'The getParameters method should return an array with campaign_show_timezone parameter key');
		$this->assertArrayHasKey('campaign_show_programme', $parameters, 'The getParameters method should return an array with campaign_show_programme parameter key');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getOngoingCampaigns
	 * @return void
	 */
	public function testGetOngoingCampaignsReturnsOnlyOpenAndPublishedCampaigns(): void
	{
		// 1. Ongoing campaign: published, start_date in the past, end_date in the future
		$ongoingCampaign = $this->repository->getById($this->dataset['campaign']);
		$ongoingCampaign->setStartDate((new \DateTime())->modify('-5 days'));
		$ongoingCampaign->setEndDate((new \DateTime())->modify('+5 days'));
		$ongoingCampaign->setPublished(true);
		$this->repository->flush($ongoingCampaign);

		// 2. Future campaign: published, start_date in the future
		$futureCampaign = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$futureCampaign->setStartDate((new \DateTime())->modify('+10 days'));
		$futureCampaign->setEndDate((new \DateTime())->modify('+20 days'));
		$futureCampaign->setPublished(true);
		$this->repository->flush($futureCampaign);

		// 3. Past campaign: published, end_date in the past
		$pastCampaign = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$pastCampaign->setStartDate((new \DateTime())->modify('-20 days'));
		$pastCampaign->setEndDate((new \DateTime())->modify('-10 days'));
		$pastCampaign->setPublished(true);
		$this->repository->flush($pastCampaign);

		// 4. Unpublished ongoing campaign
		$unpublishedCampaign = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$unpublishedCampaign->setStartDate((new \DateTime())->modify('-5 days'));
		$unpublishedCampaign->setEndDate((new \DateTime())->modify('+5 days'));
		$unpublishedCampaign->setPublished(false);
		$this->repository->flush($unpublishedCampaign);

		$this->repository->cleanCache();
		$campaigns    = $this->repository->getOngoingCampaigns();
		$campaignIds  = array_map(fn($c) => $c->getId(), $campaigns);

		$this->assertContains($ongoingCampaign->getId(), $campaignIds, 'Ongoing published campaign should be returned');
		$this->assertNotContains($futureCampaign->getId(), $campaignIds, 'Future campaign should not be returned');
		$this->assertNotContains($pastCampaign->getId(), $campaignIds, 'Past campaign should not be returned');
		$this->assertNotContains($unpublishedCampaign->getId(), $campaignIds, 'Unpublished campaign should not be returned');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getOngoingCampaigns
	 * @return void
	 */
	public function testGetOngoingCampaignsIncludesCampaignsWithNoEndDate(): void
	{
		$db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');

		// Create a campaign with end_date = '0000-00-00 00:00:00' (no end date)
		$noEndDateCampaign = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$noEndDateCampaign->setStartDate((new \DateTime())->modify('-5 days'));
		$noEndDateCampaign->setPublished(true);
		$this->repository->flush($noEndDateCampaign);

		// Force end_date to '0000-00-00 00:00:00' directly in DB since entity won't allow it
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__emundus_setup_campaigns'))
			->set($db->quoteName('end_date') . ' = ' . $db->quote('0000-00-00 00:00:00'))
			->where($db->quoteName('id') . ' = ' . $noEndDateCampaign->getId());
		$db->setQuery($query);
		$db->execute();

		$this->repository->cleanCache();
		$campaigns   = $this->repository->getOngoingCampaigns();
		$campaignIds = array_map(fn($c) => $c->getId(), $campaigns);

		$this->assertContains($noEndDateCampaign->getId(), $campaignIds, 'Campaign with no end date (0000-00-00 00:00:00) should be returned as ongoing');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getOngoingCampaigns
	 * @return void
	 */
	public function testGetOngoingCampaignsOrderedByEndDateAsc(): void
	{
		// Create two ongoing campaigns with different end dates
		$campaignEndingSoon = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaignEndingSoon->setStartDate((new \DateTime())->modify('-5 days'));
		$campaignEndingSoon->setEndDate((new \DateTime())->modify('+2 days'));
		$campaignEndingSoon->setPublished(true);
		$this->repository->flush($campaignEndingSoon);

		$campaignEndingLater = $this->repository->getById($this->h_dataset->createSampleCampaign($this->dataset['program']));
		$campaignEndingLater->setStartDate((new \DateTime())->modify('-5 days'));
		$campaignEndingLater->setEndDate((new \DateTime())->modify('+30 days'));
		$campaignEndingLater->setPublished(true);
		$this->repository->flush($campaignEndingLater);

		$this->repository->cleanCache();
		$campaigns   = $this->repository->getOngoingCampaigns();
		$campaignIds = array_map(fn($c) => $c->getId(), $campaigns);

		$this->assertContains($campaignEndingSoon->getId(), $campaignIds, 'Campaign ending soon should be in results');
		$this->assertContains($campaignEndingLater->getId(), $campaignIds, 'Campaign ending later should be in results');

		// Verify ordering: campaign ending sooner should appear before campaign ending later
		$indexSoon  = array_search($campaignEndingSoon->getId(), $campaignIds);
		$indexLater = array_search($campaignEndingLater->getId(), $campaignIds);
		$this->assertLessThan($indexLater, $indexSoon, 'Campaign ending sooner should appear before campaign ending later (ordered by end_date ASC)');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getOngoingCampaigns
	 * @return void
	 */
	public function testGetOngoingCampaignsReturnsEmptyWhenNoneMatch(): void
	{
		// Set the dataset campaign to the past so nothing matches
		$campaign = $this->repository->getById($this->dataset['campaign']);
		$campaign->setStartDate((new \DateTime())->modify('-20 days'));
		$campaign->setEndDate((new \DateTime())->modify('-10 days'));
		$this->repository->flush($campaign);

		$this->repository->cleanCache();
		$campaigns = $this->repository->getOngoingCampaigns();

		// Filter only our test campaigns to avoid false positives from other data
		$ourCampaignIds = array_map(fn($c) => $c->getId(), $campaigns);
		$this->assertNotContains($campaign->getId(), $ourCampaignIds, 'Past campaign should not appear in ongoing campaigns');
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getOngoingCampaigns
	 * @return void
	 */
	public function testGetOngoingCampaignsReturnsCampaignEntities(): void
	{
		$this->repository->cleanCache();
		$campaigns = $this->repository->getOngoingCampaigns();

		$this->assertIsArray($campaigns, 'getOngoingCampaigns should return an array');
		foreach ($campaigns as $campaign)
		{
			$this->assertInstanceOf(CampaignEntity::class, $campaign, 'Each item returned should be a CampaignEntity');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getDbTablesByCampaignId
	 * @covers \Tchooz\Repositories\Campaigns\CampaignRepository::getTablesByProfileId
	 * @return void
	 */
	public function testGetDbTablesByCampaignId(): void
	{
		$campaign = $this->repository->getById($this->dataset['campaign']);
		$campaign->setProfileId(1001);
		$this->repository->flush($campaign);

		$tables = $this->repository->getDbTablesByCampaignId($campaign->getId());
		$this->assertIsArray($tables, 'The getDbTablesByCampaignId method should return an array');
		$this->assertNotEmpty($tables, 'The getDbTablesByCampaignId method should return an array with at least one element');
	}
}
