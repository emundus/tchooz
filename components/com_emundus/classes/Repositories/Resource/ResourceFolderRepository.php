<?php
/**
 * @package     Tchooz\Repositories\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Resource;

use Joomla\CMS\Factory;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Resource\ResourceFolderEntity;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_resource_folders',
	alias: 'erf',
	columns: [
		'id',
		'name',
		'parent_id',
		'created_by',
		'created_at',
	]
)]
class ResourceFolderRepository extends EmundusRepository implements RepositoryInterface
{
	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'resource_folder', self::class);
	}

	public function getFactory(): ?object
	{
		return null;
	}

	public function getById(int $id): ?ResourceFolderEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? $this->hydrate($row) : null;
	}

	/**
	 * @param   int|null  $parentId  null returns the folders at the root.
	 *
	 * @return array<ResourceFolderEntity>
	 */
	public function getAll(?int $parentId = null): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->order($this->db->quoteName('name') . ' ASC');

		if ($parentId === null)
		{
			$query->where($this->db->quoteName('parent_id') . ' IS NULL');
		}
		else
		{
			$query->where($this->db->quoteName('parent_id') . ' = ' . (int) $parentId);
		}

		$rows = $this->db->setQuery($query)->loadObjectList();

		return array_map([$this, 'hydrate'], $rows ?: []);
	}

	/**
	 * Every folder, regardless of parent, ordered by name.
	 *
	 * @return array<ResourceFolderEntity>
	 */
	public function getFlatList(): array
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->order($this->db->quoteName('name') . ' ASC');

		$rows = $this->db->setQuery($query)->loadObjectList();

		return array_map([$this, 'hydrate'], $rows ?: []);
	}

	/**
	 * Every folder as raw rows ({id, parent_id, name, created_at}), for callers that rebuild the
	 * folder tree in PHP (path prefixes, ancestor chains) rather than hydrating entities.
	 *
	 * @return array<\stdClass>
	 */
	public function getAllRows(): array
	{
		$query = $this->db->getQuery(true)
			->select([
				$this->db->quoteName('id'),
				$this->db->quoteName('parent_id'),
				$this->db->quoteName('name'),
				$this->db->quoteName('created_at'),
			])
			->from($this->db->quoteName($this->tableName));

		return $this->db->setQuery($query)->loadObjectList() ?: [];
	}

	public function flush(ResourceFolderEntity $folder): bool
	{
		if (empty($folder->getName()))
		{
			throw new \InvalidArgumentException('Folder name cannot be empty');
		}

		$data = (object) [
			'name'      => $folder->getName(),
			'parent_id' => $folder->getParentId(),
			'created_by' => $folder->getCreatedBy(),
		];

		if (empty($folder->getId()))
		{
			$data->created_at = Factory::getDate()->toSql();

			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Failed to insert resource folder');
			}

			$folder->setId($this->db->insertid());
		}
		else
		{
			$data->id = $folder->getId();

			// $updateNulls = true so moving a folder back to the root (parent_id = null) is persisted.
			if (!$this->db->updateObject($this->tableName, $data, 'id', true))
			{
				throw new \RuntimeException('Failed to update resource folder');
			}
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
			throw new \RuntimeException('Failed to delete resource folder ' . $id);
		}

		return true;
	}

	private function hydrate(object $row): ResourceFolderEntity
	{
		return new ResourceFolderEntity(
			id: (int) $row->id,
			name: $row->name,
			parentId: $row->parent_id !== null ? (int) $row->parent_id : null,
			createdBy: (int) $row->created_by,
			createdAt: new \DateTimeImmutable($row->created_at ?? 'now')
		);
	}
}
