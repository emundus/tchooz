<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Addons;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Addons\AddonValue;
use Tchooz\Repositories\Addons\AddonRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\NumericSign\Request
 */
class AddonRepositoryTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();

		$this->model = new AddonRepository();
	}

	public function testGetByName()
	{
		$action = $this->model->getByName('choices');

		$this->assertNotNull($action);
		$this->assertEquals('choices', $action->getNamekey());

		$action = $this->model->getByName('addon_that_does_not_exist');
		$this->assertNull($action);
	}

	public function testFlush()
	{
		$addon = $this->model->getByName('choices');
		$this->assertNotNull($addon);

		$addon->getValue()->setEnabled(!$addon->getValue()->isEnabled());
		$flushed = $this->model->flush($addon);

		$this->assertTrue($flushed);

		$new_addon = new AddonEntity('new_addon', new AddonValue(false, false, ['param1' => 'value1']));
		$flushed = $this->model->flush($new_addon);
		$this->assertTrue($flushed);

		// Test exception for invalid entity
		$this->expectException(\InvalidArgumentException::class);
		$new_addon_invalid = new AddonEntity('', new AddonValue(false, false, []));
		$this->model->flush($new_addon_invalid);
		$this->model->flush(new CrudEntity(1, 1, 1, 1, 1));
	}

	public function testFlushWithInvalidAddon()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Addon namekey is required to flush AddonEntity');
		$invalidAddon = new AddonEntity('', new AddonValue(false, false, []));
		$this->model->flush($invalidAddon);
	}
}