<?php

namespace Unit\Component\Emundus\Class\Repositories\Automation;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Automation\TargetRepository;

class TargetRepositoryTest extends UnitTestCase
{

	private TargetRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new TargetRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\TargetRepository::saveTarget
	 * @return void
	 */
	public function testSaveTarget(): void
	{
		$actionId = 0;
		$targetEntity = new TargetEntity(0, TargetTypeEnum::FILE, null, []);

		$saved = $this->repository->saveTarget($targetEntity, $actionId);
		$this->assertFalse($saved, 'Saving target with actionId 0 should fail');

		$automation = $this->h_dataset->createSampleAutomation();
		$action = $automation->getActions()[0];
		$saved = $this->repository->saveTarget($targetEntity, $action->getId());
		$this->assertTrue($saved, 'Saving target with valid actionId should succeed');
		$this->assertGreaterThan(0, $targetEntity->getId(), 'Target ID should be greater than 0 after saving');
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\TargetRepository::getTargetById
	 * @return void
	 */
	public function testGetTargetById(): void
	{
		$targetId = 99999;
		$target = $this->repository->getTargetById($targetId);
		$this->assertNull($target, 'Target not found');

		// create a new target to ensure there is one to retrieve
		$automation = $this->h_dataset->createSampleAutomation();
		$action = $automation->getActions()[0];
		$targetEntity = new TargetEntity(0, TargetTypeEnum::FILE, null, []);
		$saved = $this->repository->saveTarget($targetEntity, $action->getId());

		$this->assertTrue($saved, 'Saving target should succeed');
		$this->assertGreaterThan(0, $targetEntity->getId(), 'Target ID should be greater than 0 after saving');

		$foundTargetEntity = $this->repository->getTargetById($targetEntity->getId());
		$this->assertNotNull($foundTargetEntity, 'Target should be found');
		$this->assertEquals($targetEntity->getId(), $foundTargetEntity->getId(), 'Retrieved target ID should match saved target ID');
		$this->assertEquals($targetEntity->getType(), $foundTargetEntity->getType(), 'Retrieved target type should match saved target type');
		$this->assertEquals($targetEntity->getPredefinition(), $foundTargetEntity->getPredefinition(), 'Retrieved target predefinition should match saved target predefinition');
		$this->assertEquals(sizeof($targetEntity->getConditions()), sizeof($foundTargetEntity->getConditions()), 'Retrieved target conditions count should match saved target conditions count');
	}
}