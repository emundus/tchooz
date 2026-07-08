<?php

namespace Unit\Component\Emundus\Class\Services\ApplicationFile;

use Joomla\CMS\Language\Text;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\ApplicationFile\TagsRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Tchooz\Services\ApplicationFile\ApplicationFileService;

class ApplicationFileServiceTest extends UnitTestCase
{
	private ApplicationFileService $service;

	/**
	 * @var int[]
	 */
	private array $createdTagIds = [];

	public function setUp(): void
	{
		parent::setUp();
		$this->service = new ApplicationFileService();
	}

	public function tearDown(): void
	{
		if (!empty($this->createdTagIds))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__emundus_tag_assoc'))
				->where($this->db->quoteName('id_tag') . ' IN (' . implode(',', array_map('intval', $this->createdTagIds)) . ')');
			$this->db->setQuery($query);
			$this->db->execute();

			$tagsRepository = new TagsRepository();
			foreach ($this->createdTagIds as $id)
			{
				try
				{
					$tagsRepository->delete($id);
				}
				catch (\Throwable $e)
				{
				}
			}

			$this->createdTagIds = [];
		}

		parent::tearDown();
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileService::updateOwner
	 * @return void
	 */
	public function testUpdateOwner(): void
	{
		$this->refreshDataset();
		$uploadRepository          = new UploadRepository();
		$applicationFileRepository = new ApplicationFileRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);

		// 1. Create application file with owner A
		$attachment_id = $this->h_dataset->createSampleAttachment();
		$this->h_dataset->createSampleUpload($this->dataset['fnum'], $this->dataset['campaign'], $this->dataset['applicant'], $attachment_id);
		$applicantDir = JPATH_ROOT . "/images/emundus/files/{$this->dataset['applicant']}";
		if (!is_dir($applicantDir))
		{
			mkdir($applicantDir, 0755, true);
		}
		file_put_contents("$applicantDir/sample_file.txt", "This is a sample file for applicant A.");
		$this->assertFileExists("$applicantDir/sample_file.txt");
		// Fill data
		$this->h_dataset->createSampleData($this->dataset['fnum'], $this->dataset['applicant']);

		// 2. Create user B
		$newOwnerEmail = 'applicant' . rand(0, 99999) . '@emundus.test.fr';
		$newOwnerId    = $this->h_dataset->createSampleUser(1000, $newOwnerEmail);
		$newOwnerDir   = JPATH_ROOT . "/images/emundus/files/$newOwnerId";

		// 3. Call updateOwner to change owner from A to B
		$updated = $this->service->updateOwner($applicationFile, $newOwnerId, $this->dataset['coordinator']);

		$this->assertTrue($updated);
		$this->assertEquals($applicationFile->getUser()->id, $newOwnerId, "Application file owner should be updated to new owner ID $newOwnerId.");
		$this->assertFileExists("$newOwnerDir/sample_file.txt", "This is a sample file for applicant A should be copied to new owner directory.");

		$newOwnerUploads = $uploadRepository->getByFnum($applicationFile->getFnum());
		foreach ($newOwnerUploads as $upload)
		{
			$this->assertEquals($upload->getUserId(), $newOwnerId, "Upload ID {$upload->getId()} should have its user_id updated to new owner ID $newOwnerId.");
		}

		$this->h_dataset->deleteSampleUser($this->dataset['applicant']);
		$fnumData = $this->h_dataset->getUnitTestData($this->dataset['fnum']);
		$this->assertNotEmpty($fnumData, "Application file data should still exist after original owner is deleted.");
		$this->assertSame($this->dataset['fnum'], $fnumData['fnum'], "Fnum should remain the same after owner update.");
		$this->assertSame($this->dataset['applicant'], $fnumData['user'], "Original applicant should still be associated with the application file data after owner update even if deleted.");

		// Tests exceptions
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_OWNER_DOES_NOT_EXIST'));
		$this->service->updateOwner($applicationFile, 0, $this->dataset['coordinator']);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_OWNER_SAME_AS_CURRENT'));
		$this->service->updateOwner($applicationFile, $applicationFile->getUser()->id, $this->dataset['coordinator']);
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileService::assignTags
	 * @return void
	 */
	public function testAssignTagsAssociatesKnownTagsAndIgnoresUnknownIds(): void
	{
		$tagA      = $this->createSampleTag('UT Tag A ' . rand(0, 999999));
		$tagB      = $this->createSampleTag('UT Tag B ' . rand(0, 999999));
		$unknownId = 99999999;
		$fnum      = $this->dataset['fnum'];

		$associated = $this->service->assignTags($fnum, [$tagA, $unknownId, $tagB], $this->dataset['coordinator']);

		sort($associated);
		$expected = [$tagA, $tagB];
		sort($expected);
		$this->assertSame($expected, $associated, 'Only existing tag ids must be associated and returned.');

		$persisted = $this->getAssociatedTagIds($fnum);
		$this->assertContains($tagA, $persisted, 'Known tag A must be persisted in tag_assoc.');
		$this->assertContains($tagB, $persisted, 'Known tag B must be persisted in tag_assoc.');
		$this->assertNotContains($unknownId, $persisted, 'Unknown tag id must never reach tag_assoc.');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileService::assignTags
	 * @return void
	 */
	public function testAssignTagsDeduplicatesAcrossRepeatsAndReassign(): void
	{
		$tagA  = $this->createSampleTag('UT Tag Dedup ' . rand(0, 999999));
		$fnum  = $this->dataset['fnum'];
		$coord = $this->dataset['coordinator'];

		// Repeated id in the same call, then a second call with the same tag.
		$this->service->assignTags($fnum, [$tagA, $tagA], $coord);
		$this->service->assignTags($fnum, [$tagA], $coord);

		$occurrences = count(array_filter($this->getAssociatedTagIds($fnum), static fn(int $id): bool => $id === $tagA));
		$this->assertSame(1, $occurrences, 'A tag must be associated at most once per file.');
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileService::assignTags
	 * @return void
	 */
	public function testAssignTagsReturnsEmptyForEmptyFnumOrTags(): void
	{
		$this->assertSame([], $this->service->assignTags('', [1], $this->dataset['coordinator']), 'An empty fnum must associate nothing.');
		$this->assertSame([], $this->service->assignTags($this->dataset['fnum'], [], $this->dataset['coordinator']), 'An empty tag list must associate nothing.');
	}

	private function createSampleTag(string $label): int
	{
		$tag = (new TagsRepository())->create($label);
		$this->assertNotNull($tag, 'Failed to create sample tag for the test.');
		$this->createdTagIds[] = $tag->getId();

		return $tag->getId();
	}

	/**
	 * @return int[]
	 */
	private function getAssociatedTagIds(string $fnum): array
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('id_tag'))
			->from($this->db->quoteName('#__emundus_tag_assoc'))
			->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));
		$this->db->setQuery($query);

		return array_map('intval', $this->db->loadColumn());
	}
}