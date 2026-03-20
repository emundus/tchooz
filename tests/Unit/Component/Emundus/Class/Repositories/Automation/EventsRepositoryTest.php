<?php

namespace Unit\Component\Emundus\Class\Repositories\Automation;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\Automation\EventsRepository;

class EventsRepositoryTest extends UnitTestCase
{
	private ?EventsRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new EventsRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\EventsRepository::getEventByName
	 * @return void
	 */
	public function testGetEventByName(): void
	{
		$event = $this->repository->getEventByName('onAfterStatusChange');
		$this->assertNotEmpty($event);
		$this->assertEquals('onAfterStatusChange', $event->getName());
		$this->assertNotEmpty($event->getLabel());
		$this->assertNotEmpty($event->getCategory());
	}


	/**
	 * @covers \Tchooz\Repositories\Automation\EventsRepository::getEventById
	 * @return void
	 */
	public function testGetEventById(): void
	{
		$event = $this->repository->getEventByName('onAfterStatusChange');
		$this->assertNotEmpty($event);

		$eventById = $this->repository->getEventById($event->getId());
		$this->assertNotEmpty($eventById);
		$this->assertEquals($event->getId(), $eventById->getId());
		$this->assertEquals($event->getName(), $eventById->getName());
		$this->assertEquals($event->getLabel(), $eventById->getLabel());
		$this->assertEquals($event->getDescription(), $eventById->getDescription());
	}

	/**
	 * @covers \Tchooz\Repositories\Automation\EventsRepository::getEventsList
	 * @return void
	 */
	public function testGetEventsList(): void
	{
		$eventsList = $this->repository->getEventsList();
		$this->assertNotEmpty($eventsList);
		$this->assertIsArray($eventsList);
		$this->assertGreaterThan(0, count($eventsList));
	}
}