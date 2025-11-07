<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Actions;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Repositories\Actions\ActionRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\NumericSign\Request
 */
class ActionRepositoryTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->initDataSet();

		$this->model = new ActionRepository();
	}

	public function testFlush()
	{
		$action = $this->model->getByName('file');
		$this->assertNotNull($action);

		$action->setLabel('Updated File Action');
		$flushed = $this->model->flush($action);
		$this->assertTrue($flushed);

		$this->assertEquals('Updated File Action', $action->getLabel());

		$new_action_name = 'new_action_'.$this->generateRandomString();
		$new_action = new ActionEntity(0, $new_action_name, 'New Action', new CrudEntity(1, 1, 0, 0, 0), 0, true, 'This is a new action');
		$flushed = $this->model->flush($new_action);
		$this->assertTrue($flushed);
		$this->assertNotEmpty($new_action->getId());
	}

	public function testFlushDuplicateName()
	{
		$new_action_name = 'new_action_'.$this->generateRandomString();
		$new_action = new ActionEntity(0, $new_action_name, 'New Action', new CrudEntity(1, 1, 0, 0, 0), 0, true, 'This is a new action');
		$flushed = $this->model->flush($new_action);
		$this->assertTrue($flushed);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('An action with the name "'.$new_action_name.'" already exists');
		$duplicate_action = new ActionEntity(0, $new_action_name, 'Duplicate Action', new CrudEntity(1, 1, 0, 0, 0), 0, true, 'This is a duplicate action');
		$this->model->flush($duplicate_action);
	}

	public function testGetByName()
	{
		$action = $this->model->getByName('file');

		$this->assertNotNull($action);
		$this->assertEquals('file', $action->getName());
		$this->assertInstanceOf(CrudEntity::class, $action->getCrud());

		$action = $this->model->getByName('action_that_does_not_exist');
		$this->assertNull($action);
	}

	private function generateRandomString(int $length = 10): string {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';

		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}

		return $randomString;
	}
}