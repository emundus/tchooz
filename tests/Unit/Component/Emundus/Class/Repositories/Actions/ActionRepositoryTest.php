<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Actions;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Enums\Actions\ActionTypeEnum;
use Tchooz\Factories\Actions\ActionFactory;
use Tchooz\Repositories\Actions\ActionRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Actions
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Actions\ActionRepository
 */
class ActionRepositoryTest extends UnitTestCase
{
	private Registry $config;
	
	private ActionRepository $repository;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('cache_handler', 'file');
		$this->config->set('caching', 1);

		$this->repository = new ActionRepository();
	}

	// =====================
	// flush tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::flush
	 */
	public function testFlush()
	{
		$action = $this->repository->getByName('file');
		$this->assertNotNull($action);

		$action->setLabel('Updated File Action');
		$flushed = $this->repository->flush($action);
		$this->assertTrue($flushed);

		$this->assertEquals('Updated File Action', $action->getLabel());
		// Rollback to original value to avoid affecting other tests
		$action->setLabel('COM_EMUNDUS_ACCESS_FILE_RIGHT');
		$flushed = $this->repository->flush($action);

		$new_action_name = 'new_action_'.$this->generateRandomString();
		$new_action = new ActionEntity(0, $new_action_name, 'New Action', new CrudEntity(1, 1, 0, 0, 0), 0, true, 'This is a new action');
		$flushed = $this->repository->flush($new_action);
		$this->assertTrue($flushed);
		$this->assertNotEmpty($new_action->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::flush
	 */
	public function testFlushDuplicateName()
	{
		$new_action_name = 'new_action_'.$this->generateRandomString();
		$new_action = new ActionEntity(0, $new_action_name, 'New Action', new CrudEntity(1, 1, 0, 0, 0), 0, true, 'This is a new action');
		$flushed = $this->repository->flush($new_action);
		$this->assertTrue($flushed);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('An action with the name "'.$new_action_name.'" already exists');
		$duplicate_action = new ActionEntity(0, $new_action_name, 'Duplicate Action', new CrudEntity(1, 1, 0, 0, 0), 0, true, 'This is a duplicate action');
		$this->repository->flush($duplicate_action);
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::flush
	 */
	public function testFlushInsertAndUpdatePersistsInDatabase(): void
	{
		$name = 'persist_test_' . $this->generateRandomString();
		$action = new ActionEntity(0, $name, 'Persist Label', new CrudEntity(0, 1, 1, 0, 0), 5, true, 'Persist description');
		$this->repository->flush($action);

		$fetched = $this->repository->getByName($name);
		$this->assertNotNull($fetched);
		$this->assertEquals('Persist Label', $fetched->getLabel());
		$this->assertEquals(5, $fetched->getOrdering());
		$this->assertEquals('Persist description', $fetched->getDescription());
		$this->assertEquals(1, $fetched->getCrud()->getRead());
		$this->assertEquals(0, $fetched->getCrud()->getUpdate());
		$this->assertEquals(0, $fetched->getCrud()->getDelete());

		$fetched->setLabel('Updated Persist Label');
		$fetched->setDescription('Updated description');
		$this->repository->flush($fetched);

		$updated = $this->repository->getById($fetched->getId());
		$this->assertNotNull($updated);
		$this->assertEquals('Updated Persist Label', $updated->getLabel());
		$this->assertEquals('Updated description', $updated->getDescription());

		$this->repository->delete($fetched->getId());
	}

	// =====================
	// getByName tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::getByName
	 */
	public function testGetByName()
	{
		$action = $this->repository->getByName('file');

		$this->assertNotNull($action);
		$this->assertEquals('file', $action->getName());
		$this->assertInstanceOf(CrudEntity::class, $action->getCrud());

		$action = $this->repository->getByName('action_that_does_not_exist');
		$this->assertNull($action);
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::getByName
	 */
	public function testGetByNameReturnsCompleteEntity(): void
	{
		$action = $this->repository->getByName('file');

		$this->assertNotNull($action);
		$this->assertInstanceOf(ActionEntity::class, $action);
		$this->assertNotEmpty($action->getId());
		$this->assertNotEmpty($action->getName());
		$this->assertNotEmpty($action->getLabel());
		$this->assertInstanceOf(ActionTypeEnum::class, $action->getType());
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::getByName
	 */
	public function testGetByNameCacheHit(): void
	{
		$first = $this->repository->getByName('file');
		$this->assertNotNull($first);

		$second = $this->repository->getByName('file');
		$this->assertNotNull($second);

		$this->assertEquals($first->getId(), $second->getId());
		$this->assertEquals($first->getName(), $second->getName());
		$this->assertEquals($first->getLabel(), $second->getLabel());
	}

	// =====================
	// getById tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::getById
	 */
	public function testGetByIdReturnsActionEntity(): void
	{
		$action = $this->repository->getByName('file');
		$this->assertNotNull($action);

		$fetched = $this->repository->getById($action->getId());

		$this->assertNotNull($fetched);
		$this->assertInstanceOf(ActionEntity::class, $fetched);
		$this->assertEquals($action->getId(), $fetched->getId());
		$this->assertEquals($action->getName(), $fetched->getName());
		$this->assertEquals($action->getLabel(), $fetched->getLabel());
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::getById
	 */
	public function testGetByIdReturnsNullForNonExistentId(): void
	{
		$action = $this->repository->getById(999999);

		$this->assertNull($action);
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::getById
	 */
	public function testGetByIdCacheHit(): void
	{
		$action = $this->repository->getByName('file');
		$this->assertNotNull($action);

		$first = $this->repository->getById($action->getId());
		$second = $this->repository->getById($action->getId());

		$this->assertNotNull($first);
		$this->assertNotNull($second);
		$this->assertEquals($first->getId(), $second->getId());
		$this->assertEquals($first->getName(), $second->getName());
	}

	// =====================
	// delete tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::delete
	 */
	public function testDeleteRemovesAction(): void
	{
		$name = 'delete_test_' . $this->generateRandomString();
		$action = new ActionEntity(0, $name, 'To Delete', new CrudEntity(0, 1, 0, 0, 0), 0, true, 'Will be deleted');
		$this->repository->flush($action);
		$this->assertNotEmpty($action->getId());

		$deleted = $this->repository->delete($action->getId());
		$this->assertTrue($deleted);

		$fetched = $this->repository->getByName($name);
		$this->assertNull($fetched);
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::delete
	 */
	public function testDeleteNonExistentIdDoesNotThrow(): void
	{
		$result = $this->repository->delete(999999);

		$this->assertTrue($result);
	}

	// =====================
	// getFactory tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Actions\ActionRepository::getFactory
	 */
	public function testGetFactoryReturnsActionFactory(): void
	{
		$factory = $this->repository->getFactory();

		$this->assertNotNull($factory);
		$this->assertInstanceOf(ActionFactory::class, $factory);
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