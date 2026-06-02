<?php

namespace Unit\Component\Emundus\Class\Repositories\Label;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Label\LabelEntity;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Label\LabelRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Label
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Label\LabelRepository
 */
class LabelRepositoryTest extends UnitTestCase
{
	private ?LabelRepository $repository;

	private array $createdLabelIds = [];

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new LabelRepository();
	}

	protected function tearDown(): void
	{
		foreach ($this->createdLabelIds as $labelId)
		{
			$this->repository->delete($labelId);
		}
		$this->createdLabelIds = [];

		parent::tearDown();
	}

	private function uniqueLabel(string $prefix = 'TU Tag'): string
	{
		return $prefix . ' ' . uniqid('', true);
	}


	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::delete
	 */
	public function testDelete(): void
	{
		$label = $this->uniqueLabel();
		$labelEntity = new LabelEntity($label);
		$result = $this->repository->flush($labelEntity);

		$this->assertTrue($result, 'Failed to flush new LabelEntity to the database.');
		$this->assertNotEmpty($labelEntity->getId());

		$deleteResult = $this->repository->delete($labelEntity->getId());
		$this->assertTrue($deleteResult, 'Failed to delete LabelEntity from the database.');

		$label = $this->repository->getById($labelEntity->getId());
		$this->assertNull($label, 'LabelEntity should not exist after deletion.');
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::delete
	 */
	public function testDeleteReturnsFalseForZeroId(): void
	{
		$this->assertFalse($this->repository->delete(0));
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::delete
	 */
	public function testDeleteAllowsRecreatingSameLabel(): void
	{
		$label = $this->uniqueLabel();
		$labelEntity = new LabelEntity($label, 'label-blue-2', 0);

		$this->repository->flush($labelEntity);
		$this->assertNotEmpty($labelEntity->getId());
		$this->repository->delete($labelEntity->getId());

		$this->repository->flush($labelEntity);
		$this->assertNotEmpty($labelEntity, 'Should be able to create a tag again with the same label after deletion.');
		$this->createdLabelIds[] = $labelEntity->getId();
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::flush
	 */
	public function testCreateThrowsOnEmptyLabel(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$labelEntity = new LabelEntity('   ');
		$this->repository->flush($labelEntity);
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::flush
	 */
	public function testCreateThrowsWhenLabelAlreadyExists(): void
	{
		$label = $this->uniqueLabel();
		$labelEntity = new LabelEntity($label, 'label-blue-2', 0);

		$this->repository->flush($labelEntity);
		$this->assertNotEmpty($labelEntity->getId());
		$this->createdLabelIds[] = $labelEntity->getId();

		$this->expectException(\RuntimeException::class);
		$this->repository->flush($labelEntity);
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::flush
	 */
	public function testCreateIsCaseInsensitiveForDuplicateCheck(): void
	{
		$label = $this->uniqueLabel('Étiquette TU');
		$labelEntity = new LabelEntity($label, 'label-blue-2', 0);

		$this->repository->flush($labelEntity);
		$this->assertNotEmpty($labelEntity->getId());
		$this->createdLabelIds[] = $labelEntity->getId();

		$labelEntity->setLabel(strtoupper($label));
		$this->expectException(\RuntimeException::class);
		$this->repository->flush($labelEntity);
	}


	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::flush
	 */
	public function testFlush(): void
	{
		$label = $this->uniqueLabel();
		$labelEntity = new LabelEntity($label);
		$result = $this->repository->flush($labelEntity);

		$this->assertTrue($result, 'Failed to flush new LabelEntity to the database.');
		$this->assertNotEmpty($labelEntity->getId());
		$this->createdLabelIds[] = $labelEntity->getId();

		$labelEntity->setLabel('Étiquette test unitaire modifiée');
		$result = $this->repository->flush($labelEntity);
		$this->assertTrue($result, 'Failed to update existing LabelEntity in the database.');
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::existsByLabel
	 */
	public function testExistsByLabelReturnsTrueForExistingTag(): void
	{
		$label = $this->uniqueLabel();
		$labelEntity = new LabelEntity($label, 'label-blue-2', 0);
		$this->repository->flush($labelEntity);
		$this->createdLabelIds[] = $labelEntity->getId();

		$this->assertTrue($this->repository->existsByLabel($label));
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::existsByLabel
	 */
	public function testExistsByLabelIsCaseInsensitive(): void
	{
		$label = $this->uniqueLabel('Mixed Case');
		$labelEntity = new LabelEntity($label, 'label-blue-2', 0);
		$this->repository->flush($labelEntity);
		$this->createdLabelIds[] = $labelEntity->getId();

		$this->assertTrue($this->repository->existsByLabel(strtolower($label)));
		$this->assertTrue($this->repository->existsByLabel(strtoupper($label)));
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::existsByLabel
	 */
	public function testExistsByLabelReturnsFalseForUnknownLabel(): void
	{
		$this->assertFalse($this->repository->existsByLabel($this->uniqueLabel('Unknown')));
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::existsByLabel
	 */
	public function testExistsByLabelReturnsFalseForEmptyLabel(): void
	{
		$this->assertFalse($this->repository->existsByLabel(''));
	}


	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::getById
	 */
	public function testGetById(): void
	{
		$label = $this->uniqueLabel('Mixed Case');
		$labelEntity = new LabelEntity($label, 'label-blue-2', 0);
		$result = $this->repository->flush($labelEntity);

		$this->assertTrue($result, 'Failed to flush new LabelEntity to the database.');
		$this->assertNotEmpty($labelEntity->getId());
		$this->createdLabelIds[] = $labelEntity->getId();

		$fetchedLabel = $this->repository->getById($labelEntity->getId());
		$this->assertNotNull($fetchedLabel, 'Failed to retrieve LabelEntity by ID.');
		$this->assertEquals($labelEntity->getLabel(), $fetchedLabel->getLabel(), 'Retrieved LabelEntity label does not match.');
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::getById
	 */
	public function testGetByIdReturnsNullForNonExistentId(): void
	{
		$this->assertNull($this->repository->getById(999999));
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::getById
	 */
	public function testGetByIdReturnsNullForZeroId(): void
	{
		$this->assertNull($this->repository->getById(0));
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::getByFnum
	 */
	public function testGetByFnum(): void
	{
		$label = $this->uniqueLabel('Mixed Case');
		$labelEntity = new LabelEntity($label, 'label-blue-2', 0);
		$result = $this->repository->flush($labelEntity);

		$this->assertTrue($result, 'Failed to flush new LabelEntity to the database.');
		$this->assertNotEmpty($labelEntity->getId());
		$this->createdLabelIds[] = $labelEntity->getId();

		// Associate to fnum for testing
		// TODO: Replace with repository method when available
		$assocTag = (object)[
			'fnum' => $this->dataset['fnum'],
			'id_tag' => $labelEntity->getId(),
			'date_time' => (new \DateTime())->format('Y-m-d H:i:s'),
			'user_id' => $this->dataset['coordinator']
		];
		$this->db->insertObject('#__emundus_tag_assoc', $assocTag);

		$fetchedLabels = $this->repository->getByFnum($this->dataset['fnum']);
		$this->assertNotNull($fetchedLabels, 'Failed to retrieve LabelEntity by fnum.');
		$this->assertIsArray($fetchedLabels);
		$this->assertCount(1, $fetchedLabels, 'Expected exactly one LabelEntity associated with the fnum.');
		$fetchedLabel = $fetchedLabels[0];
		$this->assertEquals($labelEntity->getLabel(), $fetchedLabel->getLabel(), 'Retrieved LabelEntity label does not match.');

		// Clean up association
		$this->db->setQuery('DELETE FROM #__emundus_tag_assoc WHERE id_tag = ' . $labelEntity->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::getByFnum
	 */
	public function testGetByFnumWithTagNonExisting(): void
	{
		// Associate to fnum for testing
		$this->dataset['fnum'] = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$assocTag = (object)[
			'fnum' => $this->dataset['fnum'],
			'id_tag' => 9999,
			'date_time' => (new \DateTime())->format('Y-m-d H:i:s'),
			'user_id' => $this->dataset['coordinator']
		];
		$this->db->insertObject('#__emundus_tag_assoc', $assocTag);

		$fetchedLabels = $this->repository->getByFnum($this->dataset['fnum']);
		$this->assertEmpty($fetchedLabels, 'Failed to retrieve LabelEntity by fnum.');
		$this->assertIsArray($fetchedLabels);

		// Clean up association
		$this->db->setQuery('DELETE FROM #__emundus_tag_assoc WHERE id_tag = 9999');
	}
}