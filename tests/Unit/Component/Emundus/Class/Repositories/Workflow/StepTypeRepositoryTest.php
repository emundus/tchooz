<?php

namespace Unit\Component\Emundus\Class\Repositories\Workflow;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Repositories\Workflow\StepTypeRepository;

class StepTypeRepositoryTest extends UnitTestCase
{
	private StepTypeRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new StepTypeRepository();
	}


	/**
	 * @covers StepTypeRepository::getStepTypeById
	 * @return void
	 */
	public function testGetStepTypeById(): void
	{
		$stepType = $this->repository->getStepTypeById(0);
		$this->assertNull($stepType, 'Expected null when step type ID is 0');

		$stepType = $this->repository->getStepTypeById(1);
		$this->assertNotNull($stepType, 'Expected StepTypeEntity object when step type ID is valid');
		$this->assertEquals(1, $stepType->getId(), 'Step type ID should match the requested ID');
		$this->assertTrue($stepType->isSystem(), 'Step type should be system');
		$this->assertTrue($stepType->isPublished(), 'Step type should be published');
	}

	/**
	 * @covers StepTypeRepository::flush
	 * @return void
	 */
	public function testFlush(): void
	{
		$newStepType = new StepTypeEntity(0, 0, 'Test Step Type', 'code', 1, false);
		$flushed = $this->repository->flush($newStepType);
		$this->assertTrue($flushed, 'Expected flush to return true for new step type');
		$this->assertGreaterThan(0, $newStepType->getId(), 'Expected new step type to have an ID after flush');
	}

	/**
	 * @covers StepTypeRepository::flush
	 * @return void
	 */
	public function testUpdate(): void
	{
		$stepType = $this->repository->getStepTypeById(1);
		$stepType->setCode('applicant');
		$flushed = $this->repository->flush($stepType);
		$this->assertTrue($flushed, 'Expected flush to return true for updated step type');
	}

	/**
	 * @covers StepTypeRepository::getStepTypeByCode
	 * @return void
	 */
	public function testGetStepTypeByCode(): void
	{
		$stepType = $this->repository->getStepTypeByCode('');
		$this->assertNull($stepType, 'Expected null when step type code is empty');
		$stepType = $this->repository->getStepTypeById(1);
		$stepType->setCode('applicant');
		$this->repository->flush($stepType);

		$retrievedStepType = $this->repository->getStepTypeByCode('applicant');
		$this->assertNotNull($retrievedStepType, 'Expected StepTypeEntity object when step type code is valid');
		$this->assertEquals('applicant', $retrievedStepType->getCode(), 'Step type code should match the requested code');
		$this->assertEquals($stepType->getId(), $retrievedStepType->getId(), 'Step type ID should match the requested id');
		$this->assertEquals($stepType->getActionId(), $retrievedStepType->getActionId(), 'Step type action ID should match the requested id');
	}

	/**
	 * @covers StepTypeRepository::get
	 * @return void
	 */
	public function testGet(): void
	{
		$stepTypes = $this->repository->get();

		$this->assertIsArray($stepTypes, 'Expected get to return an array');
		$this->assertNotEmpty($stepTypes, 'Expected get to return a non-empty array');
		foreach ($stepTypes as $stepType) {
			$this->assertInstanceOf(StepTypeEntity::class, $stepType, 'Expected each item to be an instance of StepTypeEntity');
		}
	}
}