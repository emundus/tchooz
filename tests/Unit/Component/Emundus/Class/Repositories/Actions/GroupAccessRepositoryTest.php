<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories\Actions
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
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Factories\Actions\GroupAccessFactory;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Actions\GroupAccessRepository;
use Tchooz\Repositories\Groups\GroupRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Actions
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Actions\GroupAccessRepository
 */
class GroupAccessRepositoryTest extends UnitTestCase
{
	private Registry $config;
	private ?ActionEntity $action = null;
	private array $createdAclIds = [];
	private array $createdGroupIds = [];
	
	private GroupAccessRepository $repository;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('cache_handler', 'file');
		$this->config->set('caching', 1);

		$this->repository = new GroupAccessRepository(false);

		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__emundus_setup_actions'))
			->setLimit(1);
		$db->setQuery($query);
		$actionId = (int) $db->loadResult();

		if (!empty($actionId)) {
			$actionRepository = new ActionRepository();
			$this->action = $actionRepository->getById($actionId);
		}
	}

	protected function tearDown(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		if (!empty($this->createdAclIds)) {
			foreach ($this->createdAclIds as $id) {
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__emundus_acl'))
					->where($db->quoteName('id') . ' = ' . (int) $id);
				$db->setQuery($query);
				$db->execute();
			}
		}

		if (!empty($this->createdGroupIds)) {
			foreach ($this->createdGroupIds as $id) {
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__emundus_setup_groups'))
					->where($db->quoteName('id') . ' = ' . (int) $id);
				$db->setQuery($query);
				$db->execute();
			}
		}

		parent::tearDown();
	}

	/**
	 * Crée un nouveau groupe unique dans #__emundus_setup_groups et le retourne via le GroupRepository.
	 */
	private function createUniqueGroup(): GroupEntity
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$data = (object) [
			'label'         => 'Test Group ' . uniqid(),
			'description'   => 'Group for unit test',
			'published'     => 1,
			'class'         => 'label-blue-2',
			'anonymize'     => 0,
			'filter_status' => 0,
		];

		$db->insertObject('#__emundus_setup_groups', $data);
		$groupId = (int) $db->insertid();
		$this->createdGroupIds[] = $groupId;

		$groupRepository = new GroupRepository(false);

		return $groupRepository->getById($groupId);
	}

	// =====================
	// flush — insert tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Actions\GroupAccessRepository::flush
	 */
	public function testFlushInsertCreatesNewGroupAccess(): void
	{
		$group = $this->createUniqueGroup();
		$this->assertNotNull($this->action, 'An action must exist in the database');

		$entity = new GroupAccessEntity(
			0,
			$group,
			$this->action,
			new CrudEntity(0, 1, 1, 0, 0)
		);

		$result = $this->repository->flush($entity);

		$this->assertTrue($result);
		$this->assertNotEmpty($entity->getId());
		$this->assertGreaterThan(0, $entity->getId());

		$this->createdAclIds[] = $entity->getId();
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\GroupAccessRepository::flush
	 */
	public function testFlushInsertPersistsCrudValues(): void
	{
		$group = $this->createUniqueGroup();
		$this->assertNotNull($this->action);

		$entity = new GroupAccessEntity(
			0,
			$group,
			$this->action,
			new CrudEntity(0, 1, 1, 1, 0)
		);

		$this->repository->flush($entity);
		$this->createdAclIds[] = $entity->getId();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__emundus_acl'))
			->where($db->quoteName('id') . ' = ' . $entity->getId());
		$db->setQuery($query);
		$row = $db->loadObject();

		$this->assertNotNull($row);
		$this->assertEquals($group->getId(), $row->group_id);
		$this->assertEquals($this->action->getId(), $row->action_id);
		$this->assertEquals(1, $row->c);
		$this->assertEquals(1, $row->r);
		$this->assertEquals(1, $row->u);
		$this->assertEquals(0, $row->d);
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\GroupAccessRepository::flush
	 */
	public function testFlushInsertWithoutGroupThrowsException(): void
	{
		$this->assertNotNull($this->action);

		$entity = new GroupAccessEntity(
			0,
			null,
			$this->action,
			new CrudEntity(0, 1, 0, 0, 0)
		);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Group access entity must have a group and action');

		$this->repository->flush($entity);
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\GroupAccessRepository::flush
	 */
	public function testFlushInsertWithoutActionThrowsException(): void
	{
		$group = $this->createUniqueGroup();

		$entity = new GroupAccessEntity(
			0,
			$group,
			null,
			new CrudEntity(0, 1, 0, 0, 0)
		);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Group access entity must have a group and action');

		$this->repository->flush($entity);
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\GroupAccessRepository::flush
	 */
	public function testFlushInsertWithoutGroupAndActionThrowsException(): void
	{
		$entity = new GroupAccessEntity(
			0,
			null,
			null,
			new CrudEntity(0, 0, 0, 0, 0)
		);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Group access entity must have a group and action');

		$this->repository->flush($entity);
	}

	// =====================
	// flush — update tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Actions\GroupAccessRepository::flush
	 */
	public function testFlushUpdateModifiesCrudValues(): void
	{
		$group = $this->createUniqueGroup();
		$this->assertNotNull($this->action);

		$entity = new GroupAccessEntity(
			0,
			$group,
			$this->action,
			new CrudEntity(0, 1, 0, 0, 0)
		);
		$this->repository->flush($entity);
		$this->createdAclIds[] = $entity->getId();

		$entity->setCrud(new CrudEntity(0, 0, 1, 1, 1));
		$result = $this->repository->flush($entity);

		$this->assertTrue($result);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__emundus_acl'))
			->where($db->quoteName('id') . ' = ' . $entity->getId());
		$db->setQuery($query);
		$row = $db->loadObject();

		$this->assertNotNull($row);
		$this->assertEquals(0, $row->c);
		$this->assertEquals(1, $row->r);
		$this->assertEquals(1, $row->u);
		$this->assertEquals(1, $row->d);
	}

	/**
	 * @covers \Tchooz\Repositories\Actions\GroupAccessRepository::flush
	 */
	public function testFlushUpdateDoesNotChangeGroupAndAction(): void
	{
		$group = $this->createUniqueGroup();
		$this->assertNotNull($this->action);

		$entity = new GroupAccessEntity(
			0,
			$group,
			$this->action,
			new CrudEntity(0, 1, 1, 1, 1)
		);
		$this->repository->flush($entity);
		$this->createdAclIds[] = $entity->getId();

		$originalGroupId = $group->getId();
		$originalActionId = $this->action->getId();

		$entity->setCrud(new CrudEntity(0, 0, 0, 0, 0));
		$this->repository->flush($entity);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('group_id, action_id')
			->from($db->quoteName('#__emundus_acl'))
			->where($db->quoteName('id') . ' = ' . $entity->getId());
		$db->setQuery($query);
		$row = $db->loadObject();

		$this->assertEquals($originalGroupId, $row->group_id);
		$this->assertEquals($originalActionId, $row->action_id);
	}

	// =====================
	// getFactory tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Actions\GroupAccessRepository::getFactory
	 */
	public function testGetFactoryReturnsGroupAccessFactory(): void
	{
		$factory = $this->repository->getFactory();

		$this->assertNotNull($factory);
		$this->assertInstanceOf(GroupAccessFactory::class, $factory);
	}
}