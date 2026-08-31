<?php
/**
 * @package     Tchooz\Repositories\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Resource;

use Joomla\Database\ParameterType;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Resource\ResourceSeenEntity;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_resource_seen',
	alias: 'ers',
	columns: [
		'id',
		'user_id',
		'resource_id',
		'seen_at',
	]
)]
class ResourceSeenRepository extends EmundusRepository implements RepositoryInterface
{
	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'resource_seen', self::class);
	}

	public function getFactory(): ?object
	{
		return null;
	}

	public function getById(int $id): ?ResourceSeenEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? $this->hydrate($row) : null;
	}

	/**
	 * Mark a resource as seen by a user. Idempotent: the (user_id, resource_id) unique key
	 * lets a repeated call refresh seen_at instead of inserting a duplicate row.
	 */
	public function markSeen(int $userId, int $resourceId): bool
	{
		if ($userId <= 0 || $resourceId <= 0)
		{
			return false;
		}

		$sql = 'INSERT INTO ' . $this->db->quoteName($this->tableName)
			. ' (' . $this->db->quoteName('user_id') . ', ' . $this->db->quoteName('resource_id')
			. ', ' . $this->db->quoteName('seen_at') . ')'
			. ' VALUES (:userId, :resourceId, NOW())'
			. ' ON DUPLICATE KEY UPDATE ' . $this->db->quoteName('seen_at') . ' = NOW()';

		$query = $this->db->getQuery(true);
		$query->setQuery($sql)
			->bind(':userId', $userId, ParameterType::INTEGER)
			->bind(':resourceId', $resourceId, ParameterType::INTEGER);

		return (bool) $this->db->setQuery($query)->execute();
	}

	/**
	 * Resource ids already seen by a user.
	 *
	 * @return array<int>
	 */
	public function getSeenResourceIds(int $userId): array
	{
		if ($userId <= 0)
		{
			return [];
		}

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('resource_id'))
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $userId);

		return array_map('intval', $this->db->setQuery($query)->loadColumn() ?: []);
	}

	private function hydrate(object $row): ResourceSeenEntity
	{
		return new ResourceSeenEntity(
			id: (int) $row->id,
			userId: (int) $row->user_id,
			resourceId: (int) $row->resource_id,
			seenAt: new \DateTimeImmutable($row->seen_at)
		);
	}

	public function delete(int $id): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		if (!$this->db->setQuery($query)->execute())
		{
			throw new \RuntimeException('Failed to delete resource seen ' . $id);
		}

		return true;
	}
}
