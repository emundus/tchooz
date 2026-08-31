<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Resource;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Resource\ResourceAccessEntity;
use Tchooz\Entities\Resource\ResourceEntity;
use Tchooz\Enums\Resource\ResourceAccessTypeEnum;
use Tchooz\Enums\Resource\ResourcePermissionEnum;
use Tchooz\Repositories\Resource\ResourceAccessRepository;
use Tchooz\Repositories\Resource\ResourceRepository;

/**
 * @covers \Tchooz\Repositories\Resource\ResourceAccessRepository
 */
class ResourceAccessRepositoryTest extends UnitTestCase
{
	private ResourceAccessRepository $repository;

	private ResourceRepository $resourceRepository;

	private array $resourceFixtures = [];

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->initDataSet();

		$this->repository         = new ResourceAccessRepository();
		$this->resourceRepository = new ResourceRepository();
	}

	private function makeResource(string $name): ResourceEntity
	{
		$resource = new ResourceEntity(
			name: $name,
			format: 'pdf',
			filename: 'images/emundus/resources/' . $name . '.pdf',
			size: 512,
			createdBy: $this->dataset['coordinator']
		);
		$this->resourceRepository->flush($resource);
		$this->resourceFixtures[] = $resource;

		return $resource;
	}

	private function accessFor(int $resourceId, int $userId, ResourcePermissionEnum $permission): ResourceAccessEntity
	{
		return new ResourceAccessEntity(
			resourceId: $resourceId,
			type: ResourceAccessTypeEnum::USER,
			targetId: $userId,
			permission: $permission
		);
	}

	private function clearFixtures(): void
	{
		foreach ($this->resourceFixtures as $resource)
		{
			$this->repository->replaceForResource($resource->getId(), []);
			$this->resourceRepository->delete($resource->getId());
		}
		$this->resourceFixtures = [];
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceAccessRepository::replaceForResource
	 * @covers \Tchooz\Repositories\Resource\ResourceAccessRepository::findByResource
	 */
	public function testReplaceForResourceIsAtomicSet(): void
	{
		$resource = $this->makeResource('Access Doc');

		$this->repository->replaceForResource($resource->getId(), [
			$this->accessFor($resource->getId(), $this->dataset['applicant'], ResourcePermissionEnum::VIEW),
			$this->accessFor($resource->getId(), $this->dataset['coordinator'], ResourcePermissionEnum::MANAGE),
		]);
		$this->assertCount(2, $this->repository->findByResource($resource->getId()), 'Both accesses inserted');

		// Replacing with a single access clears the previous set.
		$this->repository->replaceForResource($resource->getId(), [
			$this->accessFor($resource->getId(), $this->dataset['applicant'], ResourcePermissionEnum::EDIT),
		]);
		$remaining = $this->repository->findByResource($resource->getId());
		$this->assertCount(1, $remaining, 'Old accesses cleared, only new one remains');
		$this->assertEquals(ResourcePermissionEnum::EDIT, $remaining[0]->getPermission());

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceAccessRepository::hasAccessibleForUser
	 */
	public function testHasAccessibleForUser(): void
	{
		$resource = $this->makeResource('Shared For Has');

		$this->repository->replaceForResource($resource->getId(), [
			$this->accessFor($resource->getId(), $this->dataset['applicant'], ResourcePermissionEnum::VIEW),
		]);

		$this->assertTrue($this->repository->hasAccessibleForUser($this->dataset['applicant']), 'Applicant now has an accessible resource');
		$this->assertFalse($this->repository->hasAccessibleForUser(0), 'Invalid user id returns false');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceAccessRepository::getUserPermissionRank
	 */
	public function testGetUserPermissionRank(): void
	{
		$resource = $this->makeResource('Rank Doc');

		$this->assertEquals(0, $this->repository->getUserPermissionRank($this->dataset['applicant'], $resource->getId()), 'No share means rank 0');

		$this->repository->replaceForResource($resource->getId(), [
			$this->accessFor($resource->getId(), $this->dataset['applicant'], ResourcePermissionEnum::MANAGE),
		]);
		$this->assertEquals(3, $this->repository->getUserPermissionRank($this->dataset['applicant'], $resource->getId()), 'MANAGE maps to rank 3');
		$this->assertEquals(0, $this->repository->getUserPermissionRank(0, $resource->getId()), 'Invalid user returns 0');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceAccessRepository::accessEligibilityCondition
	 */
	public function testAccessEligibilityConditionSql(): void
	{
		$db  = Factory::getContainer()->get('DatabaseDriver');
		$sql = ResourceAccessRepository::accessEligibilityCondition($db, 42);

		$this->assertStringContainsString('42', $sql, 'User id is embedded');
		$this->assertStringContainsString('profile_id', $sql, 'Role branch queries profiles');
		$this->assertStringContainsString('group_id', $sql, 'Group branch queries groups');
		$this->assertStringStartsWith('((', $sql, 'Outer group present for safe AND-ing');
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceAccessRepository::permissionRankExpression
	 */
	public function testPermissionRankExpressionSql(): void
	{
		$db  = Factory::getContainer()->get('DatabaseDriver');
		$sql = ResourceAccessRepository::permissionRankExpression($db);

		$this->assertStringContainsString('MAX(CASE', $sql);
		$this->assertStringContainsString('THEN 3', $sql);
		$this->assertStringContainsString('ELSE 0 END', $sql);
	}

	/**
	 * @covers \Tchooz\Repositories\Resource\ResourceAccessRepository::delete
	 */
	public function testDelete(): void
	{
		$resource = $this->makeResource('Delete Access Doc');
		$this->repository->replaceForResource($resource->getId(), [
			$this->accessFor($resource->getId(), $this->dataset['applicant'], ResourcePermissionEnum::VIEW),
		]);

		$access = $this->repository->findByResource($resource->getId())[0];
		$this->assertTrue($this->repository->delete($access->getId()));
		$this->assertEmpty($this->repository->findByResource($resource->getId()), 'Access removed');

		$this->clearFixtures();
	}
}
