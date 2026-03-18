<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class;

use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_ROOT . '/components/com_emundus/mapper/ApiMapper.php';

class ApiMapperTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		$configuration = new \stdClass();
		$configuration->event = 'test_event';

		$phone_field = new \stdClass();
		$phone_field->type = 'default';
		$configuration->fields = [$phone_field];

		$construct_args = [$configuration, 'test_fnum'];
		parent::__construct('ApiMapper', $data, $dataName, 'ApiMapper', '', $construct_args);
	}

	/**
	 * @covers ApiMapper::sanitizeValue
	 *
	 * @since version 1.0.0
	 */
	public function testSanitizeValue()
	{
		$field = new \stdClass();
		$field->type = 'default';
		$value = 'FR+3399999999';

		$sanitized_value = self::callPrivateMethod($this->model, 'sanitizeValue', [$field, $value, 'emundus_phonenumber']);
		$this->assertSame('+3399999999', $sanitized_value);
	}
}