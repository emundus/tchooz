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
use Tchooz\Entities\Resource\ResourceShareEntity;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: 'jos_emundus_resource_shares',
	alias: 'ers',
	columns: [
		'id',
		'resource_id',
		'code',
		'password_hash',
		'expiration_date',
		'created_at',
	]
)]
class ResourceShareRepository extends EmundusRepository implements RepositoryInterface
{
	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'resource_share', self::class);
	}

	public function getFactory(): ?object
	{
		return null;
	}

	public function getById(int $id): ?ResourceShareEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('id') . ' = ' . (int) $id);

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? $this->hydrate($row) : null;
	}

	public function findByResource(int $resourceId): ?ResourceShareEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('resource_id') . ' = ' . (int) $resourceId);

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? $this->hydrate($row) : null;
	}

	public function findByCode(string $code): ?ResourceShareEntity
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('code') . ' = ' . $this->db->quote($code));

		$row = $this->db->setQuery($query)->loadObject();

		return $row ? $this->hydrate($row) : null;
	}

	public function flush(ResourceShareEntity $share): bool
	{
		if (empty($share->getResourceId()))
		{
			throw new \InvalidArgumentException('Share resource id cannot be empty');
		}

		if (empty($share->getCode()))
		{
			throw new \InvalidArgumentException('Share code cannot be empty');
		}

		$data = (object) [
			'resource_id'     => $share->getResourceId(),
			'code'            => $share->getCode(),
			'password_hash'   => $share->getPasswordHash(),
			'expiration_date' => $share->getExpirationDate()?->format('Y-m-d H:i:s'),
		];

		if (empty($share->getId()))
		{
			$data->created_at = Factory::getDate()->toSql();

			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Failed to insert resource share');
			}

			$share->setId($this->db->insertid());
		}
		else
		{
			$data->id = $share->getId();

			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \RuntimeException('Failed to update resource share');
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
			throw new \RuntimeException('Failed to delete resource share ' . $id);
		}

		return true;
	}

	public function deleteByResource(int $resourceId): bool
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->tableName))
			->where($this->db->quoteName('resource_id') . ' = ' . (int) $resourceId);

		if (!$this->db->setQuery($query)->execute())
		{
			throw new \RuntimeException('Failed to delete shares for resource ' . $resourceId);
		}

		return true;
	}

	private function hydrate(object $row): ResourceShareEntity
	{
		return new ResourceShareEntity(
			id: (int) $row->id,
			resourceId: (int) $row->resource_id,
			code: $row->code,
			passwordHash: $row->password_hash !== null && $row->password_hash !== '' ? $row->password_hash : null,
			expirationDate: !empty($row->expiration_date) ? new \DateTimeImmutable($row->expiration_date) : null,
			createdAt: new \DateTimeImmutable($row->created_at ?? 'now')
		);
	}
}
