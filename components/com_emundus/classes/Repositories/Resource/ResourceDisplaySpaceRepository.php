<?php
/**
 * @package     Tchooz\Repositories\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Resource;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Resource\ResourceDisplaySpaceEntity;
use Tchooz\Enums\Resource\DisplaySpaceTypeEnum;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_resource_display_spaces',
	alias: 'erds',
	columns: [
		'id',
		'resource_id',
		'type',
		'target_id',
	]
)]
class ResourceDisplaySpaceRepository extends EmundusRepository implements RepositoryInterface
{
	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'resource_display_space', self::class);
	}

	public function getFactory(): ?object
	{
		return null;
	}

	public function getById(int $id): ?ResourceDisplaySpaceEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? $this->hydrate($row) : null;
	}

	/**
	 * @return array<ResourceDisplaySpaceEntity>
	 */
	public function findByResource(int $resourceId): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('resource_id') . ' = ' . (int) $resourceId);

		$rows = $this->db->setQuery($query)->loadObjectList();

		return array_map([$this, 'hydrate'], $rows ?: []);
	}

	/**
	 * Replace the whole display-space set of a resource atomically.
	 *
	 * @param   array<ResourceDisplaySpaceEntity>  $spaces
	 */
	public function replaceForResource(int $resourceId, array $spaces): bool
	{
		$this->db->transactionStart();

		try
		{
			$delete = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('resource_id') . ' = ' . (int) $resourceId);

			if (!$this->db->setQuery($delete)->execute())
			{
				throw new \RuntimeException('Failed to clear display spaces for resource ' . $resourceId);
			}

			foreach ($spaces as $space)
			{
				$space->setResourceId($resourceId);
				$this->insert($space);
			}

			$this->db->transactionCommit();
		}
		catch (\Throwable $e)
		{
			$this->db->transactionRollback();

			throw $e;
		}

		return true;
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		if (!$this->db->setQuery($query)->execute())
		{
			throw new \RuntimeException('Failed to delete resource display space ' . $id);
		}

		return true;
	}

	private function insert(ResourceDisplaySpaceEntity $space): void
	{
		$data = (object) [
			'resource_id' => $space->getResourceId(),
			'type'        => $space->getType()->value,
			'target_id'   => $space->getTargetId(),
		];

		if (!$this->db->insertObject($this->tableName, $data))
		{
			throw new \RuntimeException('Failed to insert resource display space');
		}

		$space->setId($this->db->insertid());
	}

	private function hydrate(object $row): ResourceDisplaySpaceEntity
	{
		return new ResourceDisplaySpaceEntity(
			id: (int) $row->id,
			resourceId: (int) $row->resource_id,
			type: DisplaySpaceTypeEnum::from($row->type),
			targetId: $row->target_id !== null ? (int) $row->target_id : null
		);
	}
}
