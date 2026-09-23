<?php

namespace Unit\Component\Emundus\Class\Factories\Mapping;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Factories\Mapping\MappingObjectFactory;
use Tchooz\Synchronizers\Hubspot\Objects\ContactObject;
use Tchooz\Synchronizers\Hubspot\Objects\DealObject;
use Tchooz\Synchronizers\Mapping\MappingObjectInterface;

/**
 * @package     Unit\Component\Emundus\Class\Factories\Mapping
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Mapping\MappingObjectFactory
 */
class MappingObjectFactoryTest extends UnitTestCase
{
	private MappingObjectFactory $factory;

	protected function setUp(): void
	{
		$this->factory = new MappingObjectFactory();
	}

	protected function tearDown(): void
	{
		// No dataset created — nothing to clean up.
	}

	// -------------------------------------------------------------------------
	// make() — resolution
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Factories\Mapping\MappingObjectFactory::make
	 * @return void
	 */
	public function testMakeWhenHubspotContactReturnsContactObject(): void
	{
		$object = $this->factory->make('hubspot', 'contact');

		$this->assertInstanceOf(ContactObject::class, $object, 'make(hubspot, contact) must return a ContactObject.');
		$this->assertInstanceOf(MappingObjectInterface::class, $object, 'A resolved object must implement MappingObjectInterface.');
	}

	/**
	 * @covers \Tchooz\Factories\Mapping\MappingObjectFactory::make
	 * @return void
	 */
	public function testMakeWhenHubspotDealReturnsDealObject(): void
	{
		$object = $this->factory->make('hubspot', 'deal');

		$this->assertInstanceOf(DealObject::class, $object, 'make(hubspot, deal) must return a DealObject.');
	}

	/**
	 * @covers \Tchooz\Factories\Mapping\MappingObjectFactory::make
	 * @return void
	 */
	public function testMakeWhenUnknownConnectorThrowsDomainException(): void
	{
		$this->expectException(\DomainException::class);

		$this->factory->make('unknown_connector', 'contact');
	}

	/**
	 * @covers \Tchooz\Factories\Mapping\MappingObjectFactory::make
	 * @return void
	 */
	public function testMakeWhenUnknownObjectThrowsDomainException(): void
	{
		$this->expectException(\DomainException::class);

		$this->factory->make('hubspot', 'unknown_object');
	}

	// -------------------------------------------------------------------------
	// getAvailableObjects() — listing
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Factories\Mapping\MappingObjectFactory::getAvailableObjects
	 * @return void
	 */
	public function testGetAvailableObjectsWhenHubspotReturnsContactAndDeal(): void
	{
		$objects = $this->factory->getAvailableObjects('hubspot');

		$this->assertCount(2, $objects, 'HubSpot must expose exactly two mapping objects.');

		$names = array_map(static fn(MappingObjectInterface $object) => $object->getName(), $objects);
		$this->assertSame(['contact', 'deal'], $names, 'HubSpot objects must be contact then deal.');
	}

	/**
	 * @covers \Tchooz\Factories\Mapping\MappingObjectFactory::getAvailableObjects
	 * @return void
	 */
	public function testGetAvailableObjectsWhenUnknownConnectorReturnsEmptyArray(): void
	{
		$objects = $this->factory->getAvailableObjects('unknown_connector');

		$this->assertSame([], $objects, 'An unknown connector must expose no mapping objects.');
	}
}
