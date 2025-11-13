<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage  Repositories\Contacts
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Contacts;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Repositories\Contacts\AddressRepository;
use Tchooz\Repositories\CountryRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Contacts\AddressRepository
 */
class AddressRepositoryTest extends UnitTestCase
{
	private array $fixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->model = new AddressRepository();
	}

	public function createFixtures(): void
	{
		$countryRepository = new CountryRepository();
		$frCountry = $countryRepository->getByIso2('FR');

		$address1 = new AddressEntity(
			id: 0,
			locality: 'La Rochelle',
			region: 'Nouvelle-Aquitaine',
			street_address: '1 Rue de la Paix',
			extended_address: 'Bâtiment A',
			postal_code: '17000',
			description: 'Siège social',
			country: $frCountry->getId()
		);

		$address2 = new AddressEntity(
			id: 0,
			locality: 'Paris',
			region: 'Île-de-France',
			street_address: '10 Avenue des Champs-Élysées',
			extended_address: '',
			postal_code: '75008',
			description: 'Agence commerciale',
			country: $frCountry->getId()
		);

		$this->model->flush($address1);
		$this->model->flush($address2);
		$this->fixtures[] = $address1;
		$this->fixtures[] = $address2;
	}

	public function clearFixtures(): void
	{
		foreach ($this->fixtures as $address) {
			if (!empty($address->getId())) {
				$this->model->delete($address->getId());
			}
		}
		$this->fixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\AddressRepository::flush
	 * @return void
	 */
	public function testFlush()
	{
		// Insert new address
		$address = new AddressEntity(
			id: 0,
			locality: 'Toulouse',
			region: 'Occitanie',
			street_address: '2 Rue du Capitole',
			extended_address: '',
			postal_code: '31000',
			description: 'Bureau secondaire',
			country: 77
		);

		$result = $this->model->flush($address);
		$this->assertTrue($result, 'Result should be true');
		$this->assertGreaterThan(0, $address->getId(), 'Address should be created with an ID');

		// Update existing address
		$address->setLocality('Toulouse Centre');
		$this->model->flush($address);
		$this->assertEquals('Toulouse Centre', $address->getLocality(), 'Locality should be updated successfully');

		// Delete test address
		$this->assertTrue($this->model->delete($address->getId()), 'Address should be deleted successfully');

		// Test exception on insert failure (simulate empty entity)
		$invalid = new AddressEntity(
			id: 0,
			locality: '',
			region: '',
			street_address: '',
			extended_address: '',
			postal_code: '',
			description: '',
			country: 0
		);

		$this->expectException(\Exception::class);
		$this->model->flush($invalid);
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\AddressRepository::getById
	 * @return void
	 */
	public function testGetById()
	{
		$this->createFixtures();

		$firstFixture = $this->fixtures[0];
		$address = $this->model->getById($firstFixture->getId());

		$this->assertInstanceOf(AddressEntity::class, $address, 'Result should be an instance of AddressEntity');
		$this->assertEquals($firstFixture->getId(), $address->getId(), 'ID should match the fixture');
		$this->assertEquals($firstFixture->getLocality(), $address->getLocality());
		$this->assertEquals($firstFixture->getRegion(), $address->getRegion());
		$this->assertEquals($firstFixture->getPostalCode(), $address->getPostalCode());

		// Test with non-existing ID
		$notFound = $this->model->getById(999999);
		$this->assertNull($notFound, 'Result should be null when address does not exist');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Contacts\AddressRepository::delete
	 * @return void
	 */
	public function testDelete()
	{
		$this->createFixtures();

		$address = $this->fixtures[1];
		$result = $this->model->delete($address->getId());
		$this->assertTrue($result, 'Address should be deleted successfully');

		$this->clearFixtures();
	}
}
