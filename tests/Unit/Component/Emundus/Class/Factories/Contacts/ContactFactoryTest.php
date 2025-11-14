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
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Contacts\GenderEnum;
use Tchooz\Enums\Contacts\VerifiedStatusEnum;
use Tchooz\Factories\Contacts\ContactFactory;
use Tchooz\Repositories\CountryRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Contacts\ContactFactory
 */
class ContactFactoryTest extends UnitTestCase
{
	private ContactFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();

		$this->factory = $this->getMockBuilder(ContactFactory::class)
			->onlyMethods(['loadRequestedRelations'])
			->getMock();
	}

	/**
	 * @covers \Tchooz\Factories\Contacts\ContactFactory::fromDbObject
	 */
	public function testFromDbObjectWithMinimalData(): void
	{
		$dbObject = [
			'id'        => 1,
			'email'     => 'jane.doe@example.com',
			'lastname'  => 'Doe',
			'firstname' => 'Jane',
			'published' => false
		];

		$this->factory
			->method('loadRequestedRelations')
			->willReturn([
				'addresses'         => [],
				'countries'         => [],
				'organizations'     => [],
				'application_files' => [],
			]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(ContactEntity::class, $entity);
		$this->assertEquals('jane.doe@example.com', $entity->getEmail());
		$this->assertEquals('Doe', $entity->getLastname());
		$this->assertEquals('Jane', $entity->getFirstname());
		$this->assertEquals(1, $entity->getId());
		$this->assertEmpty($entity->getAddresses());
		$this->assertEmpty($entity->getCountries());
		$this->assertEmpty($entity->getOrganizations());
		$this->assertEmpty($entity->getApplicationFiles());
	}

	/**
	 * @covers \Tchooz\Factories\Contacts\ContactFactory::fromDbObject
	 */
	public function testFromDbObjectWithFullData(): void
	{
		$dbObject = [
			'id'        => 5,
			'user_id'   => 10,
			'email'     => 'john.doe@example.com',
			'lastname'  => 'Doe',
			'firstname' => 'John',
			'phone_1'   => '0123456789',
			'birthdate' => '1985-04-12',
			'gender'    => 'man',
			'fonction'  => 'Developer',
			'service'   => 'IT',
			'published' => false,
			'status'    => 'verified',
		];

		$countryRepository = new CountryRepository();
		$frCountry         = $countryRepository->getByIso2('FR');

		$mockRelations = [
			'addresses'     => [new AddressEntity(
				id: 0,
				locality: 'Paris',
			)],
			'countries'     => [$frCountry],
			'organizations' => [new OrganizationEntity(
				id: 0,
				name: 'Company',
			)],
		];

		$this->factory
			->method('loadRequestedRelations')
			->willReturn($mockRelations);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(ContactEntity::class, $entity);
		$this->assertEquals('john.doe@example.com', $entity->getEmail());
		$this->assertEquals('Doe', $entity->getLastname());
		$this->assertEquals('John', $entity->getFirstname());
		$this->assertEquals('0123456789', $entity->getPhone1());
		$this->assertEquals('1985-04-12', $entity->getBirthdate());
		$this->assertEquals(GenderEnum::MAN, $entity->getGender());
		$this->assertEquals('Developer', $entity->getFonction());
		$this->assertEquals('IT', $entity->getService());
		$this->assertEquals(false, $entity->isPublished());
		$this->assertEquals(VerifiedStatusEnum::VERIFIED, $entity->getStatus());

		$this->assertNotEmpty($entity->getAddresses());
		$this->assertEquals('Paris', $entity->getAddresses()[0]->getLocality());
		$this->assertEquals('FR', $entity->getCountries()[0]->getIso2());
		$this->assertEquals('Company', $entity->getOrganizations()[0]->getName());
	}

	/**
	 * @covers \Tchooz\Factories\Contacts\ContactFactory::fromDbObject
	 */
	public function testFromDbObjectAcceptsObjectInput(): void
	{
		$dbObject = (object) [
			'id'        => 2,
			'email'     => 'object@example.com',
			'lastname'  => 'Obj',
			'firstname' => 'Ect',
			'published' => false,
		];

		$this->factory
			->method('loadRequestedRelations')
			->willReturn([
				'addresses'         => [],
				'countries'         => [],
				'organizations'     => [],
				'application_files' => [],
			]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertInstanceOf(ContactEntity::class, $entity);
		$this->assertEquals('object@example.com', $entity->getEmail());
		$this->assertEquals('Obj', $entity->getLastname());
		$this->assertEquals('Ect', $entity->getFirstname());
	}

	/**
	 * @covers \Tchooz\Factories\Contacts\ContactFactory::fromDbObject
	 */
	public function testFromDbObjectHandlesEmptyOptionalFields(): void
	{
		$dbObject = [
			'id'        => 3,
			'email'     => 'emptyfields@example.com',
			'lastname'  => 'Empty',
			'firstname' => 'Fields',
			'gender'    => '',
			'status'    => '',
			'published' => false,
		];

		$this->factory
			->method('loadRequestedRelations')
			->willReturn([]);

		$entity = $this->factory->fromDbObject($dbObject);

		$this->assertNull($entity->getGender());
		$this->assertNull($entity->getStatus());
		$this->assertFalse($entity->isPublished());
	}
}
