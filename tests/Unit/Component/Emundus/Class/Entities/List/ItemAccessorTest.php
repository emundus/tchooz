<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Entities\List;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Entities\List\ItemAccessor;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\NumericSign\Request
 */
class ItemAccessorTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->initDataSet();
	}

	public function testGetAccessorValue(): void
	{
		// Test with an array
		$array = [
			'first_key' => 'value1',
			'secondKey' => 'value2',
		];
		$this->assertEquals('value1', ItemAccessor::getAccessorValue($array, 'first_key'));

		// Test with an object with public properties
		$objectWithProps = new class {
			public string $first_key = 'value1';
			public string $secondKey = 'value2';
		};
		$this->assertEquals('value1', ItemAccessor::getAccessorValue($objectWithProps, 'first_key'));
		$this->assertEquals('value2', ItemAccessor::getAccessorValue($objectWithProps, 'second_key'));

		// Test with an object with public methods
		$objectWithMethods = new class {
			public function getFirstKey() {
				return 'value1';
			}
			public function secondKey() {
				return 'value2';
			}
		};
		$this->assertEquals('value1', ItemAccessor::getAccessorValue($objectWithMethods, 'first_key'));
		$this->assertEquals('value2', ItemAccessor::getAccessorValue($objectWithMethods, 'second_key'));

		// Test with non-existing key
		$this->assertNull(ItemAccessor::getAccessorValue($array, 'non_existing_key'));

		// Test with default value
		$this->assertEquals('default', ItemAccessor::getAccessorValue($array, 'non_existing_key', 'default'));

		// Test with a real entity
		$organizationEntity1 = new OrganizationEntity(
			id: 0,
			name: 'Organization 1',
			description: 'Description 1',
			url_website: 'https://www.organization1.com',
			address: null,
			identifier_code: 'ORG001',
			logo: null,
		);
		$this->assertEquals('Organization 1', ItemAccessor::getAccessorValue($organizationEntity1, 'name'));

		// Test with camelCase key
		$this->assertEquals('https://www.organization1.com', ItemAccessor::getAccessorValue($organizationEntity1, 'urlWebsite'));
	}
}