<?php

namespace Unit\Component\Emundus\Class\Repositories\Label;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Label\LabelEntity;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;
use Tchooz\Repositories\Label\LabelRepository;

class LabelRepositoryTest extends UnitTestCase
{
	private ?LabelRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new LabelRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::delete
	 */
	public function testDelete(): void
	{
		$labelEntity = new LabelEntity('Étiquette test unitaire', 'label-blue-2', 0);
		$result = $this->repository->flush($labelEntity);

		$this->assertTrue($result, 'Failed to flush new LabelEntity to the database.');
		$this->assertNotEmpty($labelEntity->getId());

		$deleteResult = $this->repository->delete($labelEntity->getId());
		$this->assertTrue($deleteResult, 'Failed to delete LabelEntity from the database.');

		$label = $this->repository->getById($labelEntity->getId());
		$this->assertNull($label, 'LabelEntity should not exist after deletion.');
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::flush
	 */
	public function testFlush(): void
	{
		$labelEntity = new LabelEntity('Étiquette test unitaire', 'label-blue-2', 0);
		$result = $this->repository->flush($labelEntity);

		$this->assertTrue($result, 'Failed to flush new LabelEntity to the database.');
		$this->assertNotEmpty($labelEntity->getId());

		$labelEntity->setLabel('Étiquette test unitaire modifiée');
		$result = $this->repository->flush($labelEntity);
		$this->assertTrue($result, 'Failed to update existing LabelEntity in the database.');

		$this->repository->delete($labelEntity->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::getById
	 */
	public function testGetById(): void
	{
		$labelEntity = new LabelEntity('Étiquette test unitaire', 'label-blue-2', 0);
		$result = $this->repository->flush($labelEntity);

		$this->assertTrue($result, 'Failed to flush new LabelEntity to the database.');
		$this->assertNotEmpty($labelEntity->getId());

		$fetchedLabel = $this->repository->getById($labelEntity->getId());
		$this->assertNotNull($fetchedLabel, 'Failed to retrieve LabelEntity by ID.');
		$this->assertEquals($labelEntity->getLabel(), $fetchedLabel->getLabel(), 'Retrieved LabelEntity label does not match.');

		$this->repository->delete($labelEntity->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Label\LabelRepository::getByFnum
	 */
	public function testGetByFnum(): void
	{
		$labelEntity = new LabelEntity('Étiquette test unitaire', 'label-blue-2', 0);
		$result = $this->repository->flush($labelEntity);

		$this->assertTrue($result, 'Failed to flush new LabelEntity to the database.');
		$this->assertNotEmpty($labelEntity->getId());

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

		$this->repository->delete($labelEntity->getId());

		// Clean up association
		$this->db->setQuery('DELETE FROM #__emundus_tag_assoc WHERE id_tag = ' . $labelEntity->getId());
	}
}