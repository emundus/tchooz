<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage  Factories\Contacts
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Factories\Contacts;

use Joomla\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Factories\Contacts\OrganizationFactory;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Contacts\OrganizationFactory
 */
class OrganizationFactoryTest extends UnitTestCase
{
	/** @var OrganizationFactory|MockObject */
	private OrganizationFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->factory = $this->getMockBuilder(OrganizationFactory::class)
			->onlyMethods(['loadRequestedRelations'])
			->getMock();
	}

	/**
	 * @covers \Tchooz\Factories\Contacts\OrganizationFactory::fromDbObject
	 */
	public function testFromDbObjectWithMinimalData(): void
	{
		$dbObject = [
			'id'          => 1,
			'name'        => 'Organization 1',
			'description' => null,
			'published'   => true,
			'status'      => 'verified',
		];

		$this->factory
			->method('loadRequestedRelations')
			->willReturn([
				'address'           => null,
				'referent_contacts' => [],
				'other_contacts'    => [],
			]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(OrganizationEntity::class, $entity);
		$this->assertEquals(1, $entity->getId());
		$this->assertEquals('Organization 1', $entity->getName());
		$this->assertTrue($entity->isPublished());
		$this->assertEquals(VerifiedStatusEnum::VERIFIED, $entity->getStatus());
		$this->assertNull($entity->getAddress());
		$this->assertEmpty($entity->getReferentContacts());
		$this->assertEmpty($entity->getOtherContacts());
	}

	/**
	 * @covers \Tchooz\Factories\Contacts\OrganizationFactory::fromDbObject
	 */
	public function testFromDbObjectWithFullData(): void
	{
		$dbObject = [
			'id'              => 10,
			'name'            => 'Emundus Org',
			'description'     => 'Main organization',
			'url_website'     => 'https://www.emundus.org',
			'identifier_code' => 'ORG123',
			'logo'            => 'images/logos/org.png',
			'published'       => 1,
			'status'          => 'verified',
		];

		$mockRelations = [
			'address'           => new AddressEntity(
				id: 99,
				locality: 'Paris'
			),
			'referent_contacts' => [
				new ContactEntity('testreferent@test.com', 'Referent', 'Contact')
			],
			'other_contacts'    => [
				new ContactEntity('testother@test.com', 'Other', 'Contact')

			],
		];

		$this->factory
			->method('loadRequestedRelations')
			->willReturn($mockRelations);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(OrganizationEntity::class, $entity);
		$this->assertEquals(10, $entity->getId());
		$this->assertEquals('Emundus Org', $entity->getName());
		$this->assertEquals('Main organization', $entity->getDescription());
		$this->assertEquals('https://www.emundus.org', $entity->getUrlWebsite());
		$this->assertEquals('ORG123', $entity->getIdentifierCode());
		$this->assertEquals('images/logos/org.png', $entity->getLogo());
		$this->assertTrue($entity->isPublished());
		$this->assertEquals(VerifiedStatusEnum::VERIFIED, $entity->getStatus());

		$this->assertEquals('Paris', $entity->getAddress()->getLocality());
		$this->assertNotEmpty($entity->getReferentContacts());
		$this->assertEquals('Referent', $entity->getReferentContacts()[0]->getLastname());
		$this->assertNotEmpty($entity->getOtherContacts());
		$this->assertEquals('Other', $entity->getOtherContacts()[0]->getLastname());
	}

	/**
	 * @covers \Tchooz\Factories\Contacts\OrganizationFactory::fromDbObject
	 */
	public function testFromDbObjectAcceptsObjectInput(): void
	{
		$dbObject = (object) [
			'id'          => 2,
			'name'        => 'Object Org',
			'description' => 'Created from object',
			'published'   => true,
			'status'      => 'verified',
		];

		$this->factory
			->method('loadRequestedRelations')
			->willReturn([
				'address'           => null,
				'referent_contacts' => [],
				'other_contacts'    => [],
			]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(OrganizationEntity::class, $entity);
		$this->assertEquals('Object Org', $entity->getName());
		$this->assertEquals('Created from object', $entity->getDescription());
		$this->assertEquals(VerifiedStatusEnum::VERIFIED, $entity->getStatus());
	}

	/**
	 * @covers \Tchooz\Factories\Contacts\OrganizationFactory::fromDbObject
	 */
	public function testFromDbObjectHandlesNullOrEmptyOptionalFields(): void
	{
		$dbObject = [
			'id'              => 5,
			'name'            => 'Null Org',
			'description'     => null,
			'url_website'     => null,
			'identifier_code' => '',
			'logo'            => null,
			'published'       => false,
			'status'          => null,
		];

		$this->factory
			->method('loadRequestedRelations')
			->willReturn([
				'address'           => null,
				'referent_contacts' => [],
				'other_contacts'    => [],
			]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(OrganizationEntity::class, $entity);
		$this->assertEquals('Null Org', $entity->getName());
		$this->assertNull($entity->getDescription());
		$this->assertNull($entity->getUrlWebsite());
		$this->assertNull($entity->getLogo());
		$this->assertEquals('', $entity->getIdentifierCode());
		$this->assertFalse($entity->isPublished());
		$this->assertEquals(VerifiedStatusEnum::VERIFIED, $entity->getStatus());
	}
}
