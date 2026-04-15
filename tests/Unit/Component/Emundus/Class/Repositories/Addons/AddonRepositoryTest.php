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
use Tchooz\Entities\Addons\AddonStatus;
use Tchooz\Repositories\Addons\AddonRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Addons
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Addons\AddonRepository
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
}

