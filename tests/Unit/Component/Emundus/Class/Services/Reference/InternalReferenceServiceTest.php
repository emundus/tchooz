<?php

namespace Unit\Component\Emundus\Class\Services\Reference;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingTransformEntity;
use Tchooz\Entities\Reference\InternalReferenceEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Enums\Reference\PositionEnum;
use Tchooz\Enums\Reference\ResetTypeEnum;
use Tchooz\Enums\Reference\SeparatorEnum;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Repositories\Reference\InternalReferenceRepository;
use Tchooz\Services\Reference\InternalReferenceFormat;
use Tchooz\Services\Reference\InternalReferenceFormatBlock;
use Tchooz\Services\Reference\InternalReferenceFormatSequence;
use Tchooz\Services\Reference\InternalReferenceService;

/**
 * @package     Unit\Component\Emundus\Class\Services\Reference
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Reference\InternalReferenceService
 */
class InternalReferenceServiceTest extends UnitTestCase
{
	private UserFactory $userFactory;

	private InternalReferenceRepository $internalReferenceRepository;

	private InternalReferenceService $internalReferenceService;

	public function setUp(): void
	{
		parent::setUp();

		$this->userFactory                 = Factory::getContainer()->get(UserFactoryInterface::class);
		$this->internalReferenceRepository = new InternalReferenceRepository();
		$this->internalReferenceService    = new InternalReferenceService(
			new DateProvider(),
			new ApplicationFileRepository(),
		);
	}

	public function tearDown(): void
	{
		parent::tearDown();

		// Clear all references after each test to ensure no side effects
		$this->internalReferenceRepository->clearAll();
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#1 - Generate a simple reference with a static value, the current year and a sequential number of 4 numbers reset yearly
	 * Example: SIG-2024-0001
	 */
	public function testGenerateReferenceWithStaticFullYearYearlySequential(): void
	{
		$target = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);
		$format = new InternalReferenceFormat([]);
		$format->addBlock(
			new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'SIG')
		);
		$format->addBlock(
			new InternalReferenceFormatBlock(
				ConditionTargetTypeEnum::FILEDATA,
				'date_time', [
					new MappingTransformEntity(
						0,
						0,
						0,
						MappingTransformersEnum::DATE_FORMAT,
						['format' => 'Y'])
				]
			)
		);

		$formatSequence = new InternalReferenceFormatSequence(
			position: PositionEnum::END,
			resetType: ResetTypeEnum::YEARLY,
			length: 4
		);
		$format->setSequence($formatSequence);

		$reference = $this->internalReferenceService->generateReference($format, $target);
		$this->assertEquals('SIG-' . date('Y') . '-0001', $reference->getReference());

		// Check that the sequential part is 4 digits
		$this->assertEquals('0001', $reference->getSequence());
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#1 - Sequence can be included in Internal Reference Format instead of a transformation on a static value, the result should be the same as the previous test
	 * Example: SIG-2024-0001
	 */
	public function testGenerateReferenceWithStaticFullYearYearlySequentialIncludedInFormat(): void
	{
		$sequenceFormat = new InternalReferenceFormatSequence(
			position: PositionEnum::END,
			resetType: ResetTypeEnum::YEARLY,
			length: 4
		);
		$target = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);
		$format = new InternalReferenceFormat([], SeparatorEnum::DASH, null, false, false, $sequenceFormat);
		$format->addBlock(
			new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'SIG')
		);
		$format->addBlock(
			new InternalReferenceFormatBlock(
				ConditionTargetTypeEnum::FILEDATA,
				'date_time', [
					new MappingTransformEntity(
						0,
						0,
						0,
						MappingTransformersEnum::DATE_FORMAT,
						['format' => 'Y'])
				]
			)
		);

		$reference = $this->internalReferenceService->generateReference($format, $target);
		$this->assertEquals('SIG-' . date('Y') . '-0001', $reference->getReference());

		// Check that the sequential part is 4 digits
		$this->assertEquals('0001', $reference->getSequence());
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#1 - Sequence can be included in Internal Reference Format at start of the reference, the result should be the same as the previous test but with the sequence at the start of the reference
	 * Example: 0001-SIG-2024
	 */
	public function testGenerateReferenceWithStaticFullYearYearlySequentialIncludedInFormatAtStart(): void
	{
		$sequenceFormat = new InternalReferenceFormatSequence(
			position: PositionEnum::START,
			resetType: ResetTypeEnum::YEARLY,
			length: 4
		);
		$target = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);
		$format = new InternalReferenceFormat([], SeparatorEnum::DASH, null, false, false, $sequenceFormat);
		$format->addBlock(
			new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'SIG')
		);
		$format->addBlock(
			new InternalReferenceFormatBlock(
				ConditionTargetTypeEnum::FILEDATA,
				'date_time', [
					new MappingTransformEntity(
						0,
						0,
						0,
						MappingTransformersEnum::DATE_FORMAT,
						['format' => 'Y'])
				]
			)
		);

		$reference = $this->internalReferenceService->generateReference($format, $target);
		$this->assertEquals('0001-SIG-' . date('Y'), $reference->getReference());

		// Check that the sequential part is 4 digits
		$this->assertEquals('0001', $reference->getSequence());
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#1 - Generate a simple reference with a static value, the current year with 2 characters and a sequential number of 3 numbers reset yearly
	 * Example: AAP-24-001
	 */
	public function testGenerateReferenceWithStaticShortYearShortYearlySequential(): void
	{
		$target  = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);
		$fnum2   = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$target2 = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum2,
			$this->dataset['applicant'],
		);
		$fnum3   = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$target3 = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum3,
			$this->dataset['applicant'],
		);

		$format = new InternalReferenceFormat([]);
		$format->addBlock(
			new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'AAP')
		);
		$format->addBlock(
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
			)
		);
		$format->addBlock(
			new InternalReferenceFormatBlock(
				ConditionTargetTypeEnum::STATICVALUE,
				'', [
					new MappingTransformEntity(
						0,
						0,
						0,
						MappingTransformersEnum::SEQUENTIAL,
						['reset_type' => 'yearly', 'length' => 3]
					)
				]
			)
		);

		$ref1 = $this->internalReferenceService->generateReference($format, $target);
		$this->internalReferenceRepository->flush($ref1);
		$ref2 = $this->internalReferenceService->generateReference($format, $target2);
		$this->internalReferenceRepository->flush($ref2);

		$this->assertEquals('AAP-' . date('y') . '-001', $ref1->getReference());
		$this->assertEquals('AAP-' . date('y') . '-002', $ref2->getReference());

		$this->assertEquals('001', $ref1->getSequence());
		$this->assertEquals('002', $ref2->getSequence());

		// Update mock
		$nextYear         = (int) date('Y') + 1;
		$dateProviderMock = $this->getMockBuilder(DateProvider::class)->onlyMethods(['getCurrentYear'])->getMock();
		$dateProviderMock->method('getCurrentYear')->willReturn($nextYear);
		$this->internalReferenceService->setDateProvider($dateProviderMock);
		//

		$ref3 = $this->internalReferenceService->generateReference($format, $target3);
		$this->assertEquals('001', $ref3->getSequence(), 'The sequence should reset for the new year');
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#2 - Génère une référence séquentielle par campagne
	 * Exemple : SIG-2024-0001 (pour chaque campagne, la séquence recommence)
	 */
	public function testGenerateReferenceWithStaticCampaignSequential(): void
	{
		$target    = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);
		$fnum2     = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$target2   = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum2,
			$this->dataset['applicant'],
		);
		$campaign2 = $this->h_dataset->createSampleCampaign($this->dataset['program'], $this->dataset['coordinator']);
		$fnum3     = $this->h_dataset->createSampleFile($campaign2, $this->dataset['applicant']);
		$target3   = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum3,
			$this->dataset['applicant'],
		);

		$format = new InternalReferenceFormat([]);
		$format->addBlock(new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'SIG'));

		$formatSequence = new InternalReferenceFormatSequence(
			position: PositionEnum::END,
			resetType: ResetTypeEnum::CAMPAIGN,
			length: 4
		);
		$format->setSequence($formatSequence);

		$ref1 = $this->internalReferenceService->generateReference($format, $target);
		$this->internalReferenceRepository->flush($ref1);
		$ref2 = $this->internalReferenceService->generateReference($format, $target2);
		$this->internalReferenceRepository->flush($ref2);
		$ref3 = $this->internalReferenceService->generateReference($format, $target3);

		$this->assertEquals('SIG-0001', $ref1->getReference());
		$this->assertEquals('SIG-0002', $ref2->getReference());
		$this->assertEquals('SIG-0001', $ref3->getReference(), 'The sequence should reset for a new campaign');
		$this->assertEquals('0001', $ref1->getSequence());
		$this->assertEquals('0002', $ref2->getSequence());
		$this->assertEquals('0001', $ref3->getSequence(), 'The sequence should reset for a new campaign');
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#2 - Génère une référence séquentielle par programme
	 * Exemple : SIG-2024-0001 (pour chaque programme, la séquence recommence)
	 */
	public function testGenerateReferenceWithStaticProgramSequential(): void
	{
		$target     = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);
		$fnum2      = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$target2    = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum2,
			$this->dataset['applicant'],
		);
		$newProgram = $this->h_dataset->createSampleProgram('Test Program other', $this->dataset['coordinator']);
		$campaign2  = $this->h_dataset->createSampleCampaign($newProgram, $this->dataset['coordinator']);
		$fnum3      = $this->h_dataset->createSampleFile($campaign2, $this->dataset['applicant']);
		$target3    = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum3,
			$this->dataset['applicant'],
		);

		$format = new InternalReferenceFormat([]);
		$format->addBlock(new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'SIG'));

		$formatSequence = new InternalReferenceFormatSequence(
			position: PositionEnum::END,
			resetType: ResetTypeEnum::PROGRAM,
			length: 4
		);
		$format->setSequence($formatSequence);

		$ref1 = $this->internalReferenceService->generateReference($format, $target);
		$this->internalReferenceRepository->flush($ref1);
		$ref2 = $this->internalReferenceService->generateReference($format, $target2);
		$this->internalReferenceRepository->flush($ref2);
		$ref3 = $this->internalReferenceService->generateReference($format, $target3);

		$this->assertEquals('SIG-0001', $ref1->getReference());
		$this->assertEquals('SIG-0002', $ref2->getReference());
		$this->assertEquals('SIG-0001', $ref3->getReference(), 'The sequence should reset for a new program');
		$this->assertEquals('0001', $ref1->getSequence());
		$this->assertEquals('0002', $ref2->getSequence());
		$this->assertEquals('0001', $ref3->getSequence(), 'The sequence should reset for a new program');
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#4
	 * Example: SIG-2024-0001, SIG-2025-0002 (the sequence should never reset, even if the year/campaign/program changes)
	 */
	public function testGenerateReferenceWithNeverResetSequential(): void
	{
		$target     = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);
		$fnum2      = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$target2    = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum2,
			$this->dataset['applicant'],
		);
		$campaign2  = $this->h_dataset->createSampleCampaign($this->dataset['program'], $this->dataset['coordinator']);
		$fnum3      = $this->h_dataset->createSampleFile($campaign2, $this->dataset['applicant']);
		$target3    = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum3,
			$this->dataset['applicant'],
		);
		$newProgram = $this->h_dataset->createSampleProgram('Test Program other', $this->dataset['coordinator']);
		$campaign3  = $this->h_dataset->createSampleCampaign($newProgram, $this->dataset['coordinator']);
		$fnum4      = $this->h_dataset->createSampleFile($campaign2, $this->dataset['applicant']);
		$target4    = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$fnum4,
			$this->dataset['applicant'],
		);

		$format = new InternalReferenceFormat([]);
		$format->addBlock(new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'SIG'));

		$formatSequence = new InternalReferenceFormatSequence(
			position: PositionEnum::END,
			resetType: ResetTypeEnum::NEVER,
			length: 4
		);
		$format->setSequence($formatSequence);

		$ref1 = $this->internalReferenceService->generateReference($format, $target);
		$this->internalReferenceRepository->flush($ref1);
		$ref2 = $this->internalReferenceService->generateReference($format, $target2);
		$this->internalReferenceRepository->flush($ref2);
		$ref3 = $this->internalReferenceService->generateReference($format, $target3);
		$this->internalReferenceRepository->flush($ref3);
		$ref4 = $this->internalReferenceService->generateReference($format, $target4);

		$this->assertEquals('SIG-0001', $ref1->getReference());
		$this->assertEquals('SIG-0002', $ref2->getReference());
		$this->assertEquals('SIG-0003', $ref3->getReference(), 'The sequence should never reset, even if the campaign changes');
		$this->assertEquals('SIG-0004', $ref4->getReference(), 'The sequence should never reset, even if the program changes');
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#4
	 * Example: PROJ-999, PROJ-1000 (the sequence should reach the max length and continue by increment, for example after 999 it should be 1000, then 1000, etc.)
	 */
	public function testGenerateReferenceWithSequentialReachMaxLength(): void
	{
		$target = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$applicationFileRepository = new ApplicationFileRepository();
		$campaignRepository        = new CampaignRepository();
		$programRepository         = new ProgramRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);
		$campaign                  = $campaignRepository->getById($this->dataset['campaign']);
		$program                   = $programRepository->getById($this->dataset['program']['programme_id']);

		$referenceEntity = new InternalReferenceEntity(
			id: 0,
			reference: 'PROJ-999',
			sequence: '999',
			campaign: $campaign,
			program: $program,
			year: date('Y'),
			applicantName: $applicationFile->getUser()->name,
			applicationFile: $applicationFile
		);
		$this->internalReferenceRepository->flush($referenceEntity);

		$format = new InternalReferenceFormat([]);
		$format->addBlock(new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'PROJ'));
		$formatSequence = new InternalReferenceFormatSequence(
			position: PositionEnum::END,
			resetType: ResetTypeEnum::NEVER,
			length: 3
		);
		$format->setSequence($formatSequence);

		$ref1 = $this->internalReferenceService->generateReference($format, $target);
		$this->assertEquals('PROJ-1000', $ref1->getReference());
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateReference
	 * US#4
	 * Example: PROJ-001, PROJ-0002 (the sequence should continue to increment even if length is update in format)
	 */
	public function testGenerateReferenceWithSequentialLengthUpdate(): void
	{
		$target = new ActionTargetEntity(
			$this->userFactory->loadUserById($this->dataset['coordinator']),
			$this->dataset['fnum'],
			$this->dataset['applicant'],
		);

		$applicationFileRepository = new ApplicationFileRepository();
		$campaignRepository        = new CampaignRepository();
		$programRepository         = new ProgramRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);
		$campaign                  = $campaignRepository->getById($this->dataset['campaign']);
		$program                   = $programRepository->getById($this->dataset['program']['programme_id']);

		$format = new InternalReferenceFormat([]);
		$formatSequence = new InternalReferenceFormatSequence(
			position: PositionEnum::END,
			resetType: ResetTypeEnum::NEVER,
			length: 3
		);
		$format->addBlock(new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'PROJ'));
		$format->setSequence($formatSequence);

		$ref1 = $this->internalReferenceService->generateReference($format, $target);
		$this->assertEquals('PROJ-001', $ref1->getReference());

		$referenceEntity = new InternalReferenceEntity(
			id: 0,
			reference: $ref1->getReference(),
			sequence: $ref1->getSequence(),
			campaign: $campaign,
			program: $program,
			year: date('Y'),
			applicantName: $applicationFile->getUser()->name,
			applicationFile: $applicationFile
		);
		$this->internalReferenceRepository->flush($referenceEntity);

		$formatSequence->setLength(4); // Update length to 4, but the next reference should be PROJ-0002 and not PROJ-0001
		$format->setSequence($formatSequence);
		$ref2 = $this->internalReferenceService->generateReference($format, $target);
		$this->assertEquals('PROJ-0002', $ref2->getReference());
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateShortReference
	 * US#7
	 * Example: A891, E321, Z122 (4 caractères, sans O/0/I/L)
	 */
	public function testGenerateShortReference(): void
	{
		$applicationFileRepository = new ApplicationFileRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);
		
		$shortRef = $this->internalReferenceService->generateShortReference($applicationFile);
		$this->assertNotEmpty($shortRef);
		$this->assertEquals(5, strlen($shortRef), 'The short reference should be 4 characters long');
		$this->assertDoesNotMatchRegularExpression('/[O0IL]/', $shortRef, 'The short reference should not contain O, 0, I, 1 or L');

		$shortRef2 = $this->internalReferenceService->generateShortReference($applicationFile);
		$this->assertNotEmpty($shortRef2);
		$this->assertEquals(substr($shortRef, 1), substr($shortRef2, 1), 'Generating a short reference twice on the same file should return the same value if not flushed');
		$this->assertEquals(5, strlen($shortRef2), 'The short reference should be 4 characters long');
		$this->assertDoesNotMatchRegularExpression('/[O0IL]/', $shortRef2, 'The short reference should not contain O, 0, I, 1 or L');

		$applicationFile->setShortReference($shortRef);
		$applicationFileRepository->flush($applicationFile, $this->dataset['coordinator']); // Simulate saving the file, which should persist the short reference

		$shortRef3 = $this->internalReferenceService->generateShortReference($applicationFile);
		$this->assertNotEmpty($shortRef3);
		$this->assertNotEquals($shortRef2, $shortRef3, 'Generating a short reference twice on the same file should not return the same value if flushed');
		$this->assertEquals(5, strlen($shortRef3), 'The short reference should be 4 characters long');
		$this->assertDoesNotMatchRegularExpression('/[O0IL]/', $shortRef3, 'The short reference should not contain O, 0, I, 1 or L');
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateWeightedSuffix
	 */
	public function testGenerateWeightedSuffix(): void
	{
		$service = new InternalReferenceService(
			$this->createMock(DateProvider::class),
			$this->createMock(ApplicationFileRepository::class)
		);

		$alphabet = array_diff(range('A', 'Z'), ['O', 'I', 'L']);
		$alphabet = array_values($alphabet);
		$digits = range('1', '9');

		$nbIterations = 10000;
		$digitsCount = 0;
		$lettersCount = 0;
		$forbidden = ['O', 'I', 'L', '0'];

		for ($i = 0; $i < $nbIterations; $i++) {
			$suffix = $this->invokeMethod($service, 'generateWeightedSuffix', [$digits, $alphabet]);
			$this->assertEquals(4, strlen($suffix), 'Suffix should be 4 characters long');
			foreach (str_split($suffix) as $char) {
				$this->assertNotContains($char, $forbidden, 'No forbidden characters should be generated');
				if (in_array($char, $digits)) {
					$digitsCount++;
				} elseif (in_array($char, $alphabet)) {
					$lettersCount++;
				} else {
					$this->fail('Generated character should be either a digit or a letter : ' . $char);
				}
			}
		}
		$ratioDigits = $digitsCount / ($nbIterations * 4);
		$ratioLetters = $lettersCount / ($nbIterations * 4);
		$this->assertGreaterThan(0.55, $ratioDigits, 'It would be expected to have around 55% digits');
		$this->assertLessThan(0.45, $ratioLetters, 'It would be expected to have around 45% letters');
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generateDeterministicSuffix
	 */
	public function testGenerateDeterministicSuffix(): void
	{
		$service = new InternalReferenceService(
			$this->createMock(DateProvider::class),
			$this->createMock(ApplicationFileRepository::class)
		);

		$alphabet = array_diff(range('A', 'Z'), ['O', 'I', 'L']);
		$alphabet = array_values($alphabet);
		$digits = range('1', '9');

		$applicationFile = $this->getMockBuilder(ApplicationFileEntity::class)
			->disableOriginalConstructor()
			->getMock();
		$applicationFile->method('getCampaignId')->willReturn(123);
		$user = new User();
		$user->id = 456;
		$applicationFile->method('getUser')->willReturn($user);
		$applicationFile->method('getId')->willReturn(789);

		$suffix1 = $this->invokeMethod($service, 'generateDeterministicSuffix', [$applicationFile, $digits, $alphabet]);
		$suffix2 = $this->invokeMethod($service, 'generateDeterministicSuffix', [$applicationFile, $digits, $alphabet]);

		$this->assertEquals(4, strlen($suffix1), 'Suffix should be 4 characters long');
		$this->assertEquals($suffix1, $suffix2, 'Suffix need to be deterministic based on the same file properties');
		foreach (str_split($suffix1) as $char) {
			$this->assertNotContains($char, ['O', 'I', 'L', '0'], 'No forbidden characters should be generated');
			$this->assertTrue(in_array($char, $digits) || in_array($char, $alphabet), 'Generated character should be either a digit or a letter : ' . $char);
		}

		$applicationFile = $this->getMockBuilder(ApplicationFileEntity::class)
			->disableOriginalConstructor()
			->getMock();
		$applicationFile->method('getCampaignId')->willReturn(123);
		$user = new User();
		$user->id = 456;
		$applicationFile->method('getUser')->willReturn($user);
		$applicationFile->method('getId')->willReturn(790);
		$suffix3 = $this->invokeMethod($service, 'generateDeterministicSuffix', [$applicationFile, $digits, $alphabet]);
		$this->assertNotEquals($suffix1, $suffix3, 'Changing the file properties should change the generated suffix');
	}

	/**
	 * @covers \Tchooz\Services\Reference\InternalReferenceService::generatePreviewReferences
	 */
	public function testGeneratePreviewReferences(): void
	{
		$fnum = $this->dataset['fnum'];
		sleep(1);
		$fnum2 = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$fnums = [$fnum, $fnum2];

		// Set up a custom reference format in the database so generatePreviewReferences has a format to use
		$format = new InternalReferenceFormat([]);
		$format->addBlock(new InternalReferenceFormatBlock(ConditionTargetTypeEnum::STATICVALUE, 'REF'));
		$format->setSequence(new InternalReferenceFormatSequence(
			position: PositionEnum::END,
			resetType: ResetTypeEnum::YEARLY,
			length: 4
		));

		$configurationRepository = new \Tchooz\Repositories\Settings\ConfigurationRepository();
		$configurationEntity = new \Tchooz\Entities\Settings\ConfigurationEntity(
			'custom_reference_format',
			$format->__serialize()
		);
		$configurationRepository->flush($configurationEntity);

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		$result = $this->internalReferenceService->generatePreviewReferences($fnums, $coordinator);
		$this->assertCount(2, $result, 'Should generate a preview reference for each file');
		foreach ($result as $fnum => $preview) {
			$this->assertArrayHasKey('short_reference', $preview, 'Preview should contain a short reference');
			$this->assertArrayHasKey('new_reference', $preview, 'Preview should contain a new reference');
			$this->assertArrayHasKey('old_reference', $preview, 'Preview should contain an old reference key');
			$this->assertNotEmpty($preview['new_reference'], 'New reference should not be empty');
		}
	}

	protected function invokeMethod(&$object, $methodName, array $parameters = [])
	{
		$reflection = new \ReflectionClass(get_class($object));
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);
		return $method->invokeArgs($object, $parameters);
	}
}

