<?php

namespace Unit\Component\Emundus\Class\Repositories\Fabrik;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;

class FabrikRepositoryTest extends UnitTestCase
{
	private ?FabrikRepository $repository;

	private ?FabrikFactory $factory;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new FabrikRepository();
		$this->factory = new FabrikFactory($this->repository);
		$this->repository->setFactory($this->factory);
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getFormById
	 */
	public function testGetFormById(): void
	{
		$formId = 102;
		$form = $this->repository->withRelations(false)->getFormById($formId);

		$this->assertNotNull($form);
		$this->assertEquals($formId, $form->getId());
		$this->assertNotEmpty($form->getLabel());
		$this->assertEmpty($form->getGroups(), 'Groups should not be loaded when withRelations is false');

		$form = $this->repository->withRelations()->getFormById($formId);
		$this->assertNotEmpty($form->getGroups());
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getGroupsByFormId
	 */
	public function testGetGroupsByFormId(): void
	{
		$formId = 102;
		$groups = $this->repository->getGroupsByFormId($formId);

		$this->assertNotEmpty($groups);

		$form = $this->repository->withRelations()->getFormById($formId);
		$this->assertEquals(count($form->getGroups()), count($groups));
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElementsByGroupId
	 */
	public function testGetElementsByGroupId(): void
	{
		$formId = 102;
		$form = $this->repository->withRelations()->getFormById($formId);
		$this->assertNotEmpty($form->getGroups());

		$group = $form->getGroups()[0];
		$elements = $this->repository->getElementsByGroupId($group->getId());

		$this->assertNotEmpty($elements);
		$this->assertEquals($group->getId(), $elements[0]->getGroupId());
	}
}