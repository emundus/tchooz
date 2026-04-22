<?php

namespace Unit\Component\Emundus\Class\Repositories\Reference;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingTransformEntity;
use Tchooz\Entities\Reference\InternalReferenceEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Enums\Reference\PositionEnum;
use Tchooz\Enums\Reference\ResetTypeEnum;
use Tchooz\Enums\Reference\SeparatorEnum;
use Tchooz\Factories\Reference\InternalReferenceFactory;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Repositories\Reference\InternalReferenceRepository;
use Tchooz\Services\Reference\InternalReferenceFormat;
use Tchooz\Services\Reference\InternalReferenceFormatBlock;
use Tchooz\Services\Reference\InternalReferenceFormatSequence;
use Tchooz\Services\Reference\InternalReferenceService;

class InternalReferenceRepositoryTest extends UnitTestCase
{
	private UserFactory $userFactory;

	private InternalReferenceRepository $repository;

	private InternalReferenceService $service;

	private InternalReferenceFormat $format;

	public function setUp(): void
	{
		parent::setUp();

		$this->userFactory = Factory::getContainer()->get(UserFactoryInterface::class);
		$this->repository  = new InternalReferenceRepository();
		$this->service     = new InternalReferenceService(
			new DateProvider(),
			new ApplicationFileRepository(),
		);

		$this->format = new InternalReferenceFormat([
			new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'AAP'),
			new InternalReferenceFormatBlock(
				ConditionTargetTypeEnum::FILEDATA,
				'date_time', [
					new MappingTransformEntity(
						0,
						0,
						0,
						MappingTransformersEnum::DATE_FORMAT,
						['format' => 'y'])
				]
			),
		],
			SeparatorEnum::DASH,
			null,
			false,
			false,
			new InternalReferenceFormatSequence(
				PositionEnum::END,
				ResetTypeEnum::YEARLY,
				3
			)
		);
	}

	public function tearDown(): void
	{
		parent::tearDown();

		// Clear all references after each test to ensure no side effects
		$this->repository->clearAll();
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::clearAll
	 * @return void
	 */
	public function testClearAll(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);
		
		$ref1   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref1);
		$this->assertTrue($result);

		$ref2   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref2);
		$this->assertTrue($result);
		
		$references = $this->repository->getList();
		$this->assertCount(2, $references->getItems(), 'There should be 2 references before clearAll');
		
		$this->repository->clearAll();

		$references = $this->repository->getList();
		$this->assertCount(0, $references->getItems(), 'There should be 1 references before clearAll');
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::delete
	 * @return void
	 */
	public function testDelete(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$ref1   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref1);
		$this->assertTrue($result);

		$retrievedRef1 = $this->repository->getById($ref1->getId());
		$this->assertNotEmpty($retrievedRef1);

		$result = $this->repository->delete($ref1->getId());
		$this->assertTrue($result);
		$retrievedRef2 = $this->repository->getById($ref1->getId());
		$this->assertEmpty($retrievedRef2);
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::flush
	 * @return void
	 */
	public function testFlush(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$ref1   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref1);
		$this->assertTrue($result);
		$this->assertNotEmpty($ref1->getSequenceInt(), 'beforeFlush sequence int is defined');

		// Verify that the reference was saved correctly
		$retrievedRef1 = $this->repository->getById($ref1->getId());
		$this->assertNotEmpty($retrievedRef1);
		$this->assertEquals($retrievedRef1->getReference(), $ref1->getReference());
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::flush
	 * @return void
	 */
	public function testSetInactive(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$ref1   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref1);

		$this->assertTrue($result);

		$ref1->setActive(false);
		$result = $this->repository->flush($ref1);
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::flush
	 * US#11
	 * @return void
	 */
	public function testSetInactiveWhenGenerateNew(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$ref1   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref1);

		$this->assertTrue($result);

		$ref2   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref2);
		$this->assertTrue($result);

		$oldRef1 = $this->repository->getById($ref1->getId());
		$this->assertNotEmpty($oldRef1);
		$this->assertFalse($oldRef1->isActive());
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::getActiveReference
	 * @return void
	 */
	public function testGetActiveReference(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$ref1   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref1);

		$this->assertTrue($result);

		$ref1->setActive(false);
		$result = $this->repository->flush($ref1);
		$this->assertTrue($result);

		$ref2   = $this->service->generateReference($this->format, $target);
		$result = $this->repository->flush($ref2);
		$this->assertTrue($result);

		$applicationFileRepository = new ApplicationFileRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);
		$activeRef                 = $this->repository->getActiveReference($applicationFile->getId());
		$this->assertNotEmpty($activeRef);
		$this->assertEquals($activeRef->getId(), $ref2->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::getById
	 * @return void
	 */
	public function testGetById(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$applicant   = $this->userFactory->loadUserById($this->dataset['applicant']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$ref1 = $this->service->generateReference($this->format, $target);
		$this->repository->flush($ref1);

		$retrievedRef1 = $this->repository->getById($ref1->getId());
		$this->assertNotEmpty($retrievedRef1);
		$this->assertNotEmpty($retrievedRef1->getReference());
		$this->assertEquals($retrievedRef1->getApplicantName(), $applicant->name);
		$this->assertEquals($retrievedRef1->getApplicationFile()->getFnum(), $this->dataset['fnum']);
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::getById
	 * US#12
	 * @return void
	 */
	public function testGetByIdAfterApplicationFileDelete(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$ref1 = $this->service->generateReference($this->format, $target);
		$this->repository->flush($ref1);

		$retrievedRef1 = $this->repository->getById($ref1->getId());
		$this->assertNotEmpty($retrievedRef1);
		$this->assertNotEmpty($retrievedRef1->getReference());

		// Delete the application file and verify that the reference can still be retrieved without errors
		$this->h_dataset->deleteSampleFile($this->dataset['fnum']);
		$retrievedRefAfterDelete = $this->repository->getById($ref1->getId());
		$this->assertNotEmpty($retrievedRefAfterDelete);
		$this->assertEquals($retrievedRefAfterDelete->getId(), $ref1->getId());
		$this->assertNull($retrievedRefAfterDelete->getApplicationFile());
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::getLastSequence
	 * @return void
	 */
	public function testGetLastSequence(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		// Create multiple references to test sequence increment
		for ($i = 0; $i < 5; $i++)
		{
			$ref = $this->service->generateReference($this->format, $target);
			$this->repository->flush($ref);
		}

		$lastSequence = $this->repository->getLastSequence();
		$this->assertEquals(5, $lastSequence);
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::getLastSequenceByYear
	 * @return void
	 */
	public function testGetLastSequenceByYear(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$currentYear = (int) date('Y');

		// Create multiple references for the current year
		for ($i = 0; $i < 3; $i++)
		{
			$ref = $this->service->generateReference($this->format, $target);
			$this->repository->flush($ref);
		}

		$lastSequenceThisYear = $this->repository->getLastSequenceByYear($currentYear);
		$this->assertEquals(3, $lastSequenceThisYear);

		$applicationFileRepository = new ApplicationFileRepository();
		$campaignRepository        = new CampaignRepository();
		$programRepository         = new ProgramRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);
		$campaign                  = $campaignRepository->getById($this->dataset['campaign']);
		$program                   = $programRepository->getById($this->dataset['program']['programme_id']);

		// Create a reference for a different year
		$refNextYear = new InternalReferenceEntity(
			id: 0,
			reference: 'AAP24-001',
			sequence: '001',
			campaign: $campaign,
			program: $program,
			year: (string) ($currentYear + 1),
			applicantName: 'Test Applicant',
			applicationFile: $applicationFile,
			active: true
		);
		$this->repository->flush($refNextYear);

		$lastSequenceNextYear = $this->repository->getLastSequenceByYear((string) ($currentYear + 1));
		$this->assertEquals(1, $lastSequenceNextYear);
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::getLastSequenceByCampaign
	 * @return void
	 */
	public function testGetLastSequenceByCampaign(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		// Create multiple references for the campaign
		for ($i = 0; $i < 4; $i++)
		{
			$ref = $this->service->generateReference($this->format, $target);
			$this->repository->flush($ref);
		}

		$lastSequenceByCampaign = $this->repository->getLastSequenceByCampaign($this->dataset['campaign']);
		$this->assertEquals(4, $lastSequenceByCampaign);

		$newCampaign               = $this->h_dataset->createSampleCampaign($this->dataset['program'], $this->dataset['coordinator']);
		$applicationFileRepository = new ApplicationFileRepository();
		$campaignRepository        = new CampaignRepository();
		$programRepository         = new ProgramRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);
		$campaign                  = $campaignRepository->getById($newCampaign);
		$program                   = $programRepository->getById($this->dataset['program']['programme_id']);

		// Create a reference for a different year
		$refOtherCampaign = new InternalReferenceEntity(
			id: 0,
			reference: 'AAP24-001',
			sequence: '001',
			campaign: $campaign,
			program: $program,
			year: date('Y'),
			applicantName: 'Test Applicant',
			applicationFile: $applicationFile,
			active: true
		);
		$this->repository->flush($refOtherCampaign);

		$lastSequenceOtherCampaign = $this->repository->getLastSequenceByCampaign($newCampaign);
		$this->assertEquals(1, $lastSequenceOtherCampaign);
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::getLastSequenceByProgram
	 * @return void
	 */
	public function testGetLastSequenceByProgram(): void
	{
		$coordinator = $this->userFactory->loadUserById($this->dataset['coordinator']);
		$target      = new ActionTargetEntity(
			$coordinator,
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		// Create multiple references for the campaign
		for ($i = 0; $i < 4; $i++)
		{
			$ref = $this->service->generateReference($this->format, $target);
			$this->repository->flush($ref);
		}

		$lastSequenceByProgram = $this->repository->getLastSequenceByProgram($this->dataset['program']['programme_id']);
		$this->assertEquals(4, $lastSequenceByProgram);

		$newProgram                = $this->h_dataset->createSampleProgram('Other Program', $this->dataset['coordinator']);
		$applicationFileRepository = new ApplicationFileRepository();
		$campaignRepository        = new CampaignRepository();
		$programRepository         = new ProgramRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);
		$campaign                  = $campaignRepository->getById($this->dataset['campaign']);
		$program                   = $programRepository->getById($newProgram['programme_id']);

		// Create a reference for a different year
		$refOtherProgram = new InternalReferenceEntity(
			id: 0,
			reference: 'AAP24-001',
			sequence: '001',
			campaign: $campaign,
			program: $program,
			year: date('Y'),
			applicantName: 'Test Applicant',
			applicationFile: $applicationFile,
			active: true
		);
		$this->repository->flush($refOtherProgram);

		$lastSequenceOtherProgram = $this->repository->getLastSequenceByProgram($newProgram['programme_id']);
		$this->assertEquals(1, $lastSequenceOtherProgram);
	}

	/**
	 * @covers \Tchooz\Repositories\Reference\InternalReferenceRepository::getFactory
	 * @return void
	 */
	public function testGetFactory(): void
	{
		$factory = $this->repository->getFactory();
		$this->assertInstanceOf(InternalReferenceFactory::class, $factory);
	}
}