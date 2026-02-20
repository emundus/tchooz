<?php

namespace Unit\Component\Emundus\Class\Repositories\Mapping;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Entities\Mapping\MappingTransformEntity;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Mapping\MappingTransformersEnum;
use Tchooz\Repositories\Mapping\MappingRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class MappingRepositoryTest extends UnitTestCase
{
	private MappingRepository $repository;

	private SynchronizerEntity $synchronizerEntity;

	public function setUp(): void
	{
		parent::setUp();
		$this->repository = new MappingRepository();

		$synchronizerRepostory = new SynchronizerRepository();
		$this->synchronizerEntity = $synchronizerRepostory->getByType('hubspot');

	}

	/**
	 * @covers \Tchooz\Repositories\Mapping\MappingRepository::flush
	 * @return void
	 */
	public function testFlush(): void
	{
		$rows = [];
		$rows[] = new MappingRowEntity(0, 0, 0, ConditionTargetTypeEnum::CALCULATED, 'test', 'target_field_1');

		$mappingEntity = new MappingEntity(0, 'Test Mapping', $this->synchronizerEntity->getId(), 'test', [], $rows);
		$this->assertTrue($this->repository->flush($mappingEntity), 'The flush method should return true on success.');
		$this->assertGreaterThan(0, $mappingEntity->getId(), 'The mapping entity ID should be greater than 0 after flush.');
	}

	/**
	 * @covers \Tchooz\Repositories\Mapping\MappingRepository::getById
	 * @return void
	 */
	public function testGetById(): void
	{
		$transformations = [];
		$transformations[] = new MappingTransformEntity(0, 0, 0, MappingTransformersEnum::CAPITALIZE, []);

		$rows = [];
		$rows[] = new MappingRowEntity(0, 0, 0, ConditionTargetTypeEnum::CALCULATED, 'test', 'target_field_1', $transformations);

		$mappingEntity = new MappingEntity(0, 'Test Mapping', $this->synchronizerEntity->getId(), 'test', [], $rows);
		$this->assertTrue($this->repository->flush($mappingEntity), 'The flush method should return true on success.');
		$this->assertGreaterThan(0, $mappingEntity->getId(), 'The mapping entity ID should be greater than 0 after flush.');

		$fetchedMapping = $this->repository->getById($mappingEntity->getId());
		$this->assertInstanceOf(MappingEntity::class, $fetchedMapping, 'The fetched mapping should be an instance of MappingEntity.');
		$this->assertEquals($mappingEntity->getLabel(), $fetchedMapping->getLabel(), 'The labels of the mapping entities should match.');
		$this->assertCount(1, $fetchedMapping->getRows(), 'The fetched mapping should have one row.');
		$this->assertEquals(ConditionTargetTypeEnum::CALCULATED, $fetchedMapping->getRows()[0]->getSourceType(), 'The source type of the mapping row should match.');
		$this->assertCount(1, $fetchedMapping->getRows()[0]->getTransformations(), 'The mapping row should have one transformation.');
		$this->assertEquals(MappingTransformersEnum::CAPITALIZE, $fetchedMapping->getRows()[0]->getTransformations()[0]->getType(), 'The transformation type should match.');

	}
}