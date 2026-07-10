<?php
/**
 * @package     Unit\Component\Emundus\Class\Entities\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\Contacts;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Contacts\AddressEntity;

/**
 * @covers \Tchooz\Entities\Contacts\AddressEntity
 */
class AddressEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::__construct
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::getId
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::getLocality
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::getRegion
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::getStreetAddress
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::getExtendedAddress
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::getPostalCode
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::getDescription
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::getCountry
	 */
	public function testInstanciationWithRequiredParametersOnly(): void
	{
		$entity = new AddressEntity(1);

		$this->assertSame(1, $entity->getId());
		$this->assertNull($entity->getLocality());
		$this->assertNull($entity->getRegion());
		$this->assertNull($entity->getStreetAddress());
		$this->assertNull($entity->getExtendedAddress());
		$this->assertNull($entity->getPostalCode());
		$this->assertNull($entity->getDescription());
		$this->assertSame(0, $entity->getCountry());
	}

	/**
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::__construct
	 */
	public function testInstanciationWithAllParameters(): void
	{
		$entity = new AddressEntity(
			5, 'Paris', 'Île-de-France', '10 rue de la Paix',
			'Bâtiment A', '75002', 'Siège social', 250
		);

		$this->assertSame(5, $entity->getId());
		$this->assertSame('Paris', $entity->getLocality());
		$this->assertSame('Île-de-France', $entity->getRegion());
		$this->assertSame('10 rue de la Paix', $entity->getStreetAddress());
		$this->assertSame('Bâtiment A', $entity->getExtendedAddress());
		$this->assertSame('75002', $entity->getPostalCode());
		$this->assertSame('Siège social', $entity->getDescription());
		$this->assertSame(250, $entity->getCountry());
	}

	/**
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::__construct
	 */
	public function testIdZeroFallback(): void
	{
		$entity = new AddressEntity(0);

		$this->assertSame(0, $entity->getId());
	}

	/**
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setId
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setLocality
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setRegion
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setStreetAddress
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setExtendedAddress
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setPostalCode
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setDescription
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setCountry
	 */
	public function testSetters(): void
	{
		$entity = new AddressEntity(1);

		$entity->setId(99);
		$entity->setLocality('Lyon');
		$entity->setRegion('Auvergne-Rhône-Alpes');
		$entity->setStreetAddress('5 place Bellecour');
		$entity->setExtendedAddress('Étage 3');
		$entity->setPostalCode('69002');
		$entity->setDescription('Bureau régional');
		$entity->setCountry(100);

		$this->assertSame(99, $entity->getId());
		$this->assertSame('Lyon', $entity->getLocality());
		$this->assertSame('Auvergne-Rhône-Alpes', $entity->getRegion());
		$this->assertSame('5 place Bellecour', $entity->getStreetAddress());
		$this->assertSame('Étage 3', $entity->getExtendedAddress());
		$this->assertSame('69002', $entity->getPostalCode());
		$this->assertSame('Bureau régional', $entity->getDescription());
		$this->assertSame(100, $entity->getCountry());
	}

	/**
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setLocality
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setRegion
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setStreetAddress
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setExtendedAddress
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setPostalCode
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setDescription
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::setCountry
	 */
	public function testSettersAcceptNull(): void
	{
		$entity = new AddressEntity(1, 'Paris', 'IDF', 'Rue', 'Ext', '75000', 'Desc', 250);

		$entity->setLocality(null);
		$entity->setRegion(null);
		$entity->setStreetAddress(null);
		$entity->setExtendedAddress(null);
		$entity->setPostalCode(null);
		$entity->setDescription(null);
		$entity->setCountry(null);

		$this->assertNull($entity->getLocality());
		$this->assertNull($entity->getRegion());
		$this->assertNull($entity->getStreetAddress());
		$this->assertNull($entity->getExtendedAddress());
		$this->assertNull($entity->getPostalCode());
		$this->assertNull($entity->getDescription());
		$this->assertNull($entity->getCountry());
	}

	/**
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::__serialize
	 */
	public function testSerialize(): void
	{
		$entity = new AddressEntity(
			1, 'Paris', 'IDF', '10 rue Test',
			'Bât B', '75001', 'Description', 250
		);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertSame('Paris', $serialized['locality']);
		$this->assertSame('IDF', $serialized['region']);
		$this->assertSame('10 rue Test', $serialized['street_address']);
		$this->assertSame('Bât B', $serialized['extended_address']);
		$this->assertSame('75001', $serialized['postal_code']);
		$this->assertSame('Description', $serialized['description']);
		$this->assertSame(250, $serialized['country']);
	}

	/**
	 * @covers \Tchooz\Entities\Contacts\AddressEntity::__serialize
	 */
	public function testSerializeWithNullValues(): void
	{
		$entity = new AddressEntity(1);

		$serialized = $entity->__serialize();

		$this->assertSame(1, $serialized['id']);
		$this->assertNull($serialized['locality']);
		$this->assertNull($serialized['region']);
		$this->assertNull($serialized['street_address']);
		$this->assertNull($serialized['extended_address']);
		$this->assertNull($serialized['postal_code']);
		$this->assertNull($serialized['description']);
		$this->assertSame(0, $serialized['country']);
	}
}