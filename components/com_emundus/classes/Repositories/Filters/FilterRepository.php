<?php
/**
 * @package     Tchooz\Repositories\Filters
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Filters;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Filters\FilterEntity;
use Tchooz\Enums\Filters\FilterModeEnum;
use Tchooz\Factories\Filters\FilterFactory;
use Tchooz\Factories\User\EmundusUserFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: 'jos_emundus_filters', alias: 'ef', columns: [
	'id',
	'time_date',
	'user',
	'name',
	'constraints',
	'item_id',
	'mode'
])]
class FilterRepository extends EmundusRepository implements RepositoryInterface
{
	private FilterFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'filters', self::class);

		$this->factory = new FilterFactory();
	}

	public function flush(FilterEntity $entity): void
	{
		if (empty($entity->getName()))
		{
			throw new \InvalidArgumentException('Filter name cannot be empty');
		}

		$data = (object) [
			'time_date'   => $entity->getTimeDate()->format('Y-m-d H:i:s') ?? (new \DateTime())->format('Y-m-d H:i:s'),
			'user'        => $entity->getUser()->id,
			'name'        => $entity->getName(),
			'constraints' => json_encode($entity->getConstraints()),
			'item_id'     => $entity->getItemId(),
			'mode'        => $entity->getMode()->value
		];

		if (empty($entity->getId()))
		{
			if (!$this->db->insertObject($this->tableName, $data))
			{
				throw new \RuntimeException('Failed to insert filter into database');
			}

			$entity->setId($this->db->insertid());
		}
		else
		{
			$data->id = $entity->getId();
			if (!$this->db->updateObject($this->tableName, $data, 'id'))
			{
				throw new \RuntimeException('Failed to update filter in database');
			}
		}
	}

	public function applyFilters(QueryInterface $query, array $filters): void
	{
		$filterKeys = array_keys($filters);
		if(in_array('mode', $filterKeys))
		{
			assert($filters['mode'] instanceof FilterModeEnum);
			$query->where($this->alias . '.mode = ' . $this->db->quote($filters['mode']->value));
		}

		if(in_array('user', $filterKeys))
		{
			$query->where($this->alias . '.user = ' . $this->db->quote($filters['user']));
		}

		if(in_array('view', $filterKeys))
		{
			$query->where('JSON_EXTRACT(' . $this->db->quoteName($this->alias . '.constraints') . ', \'$.view\') = ' . $this->db->quote($filters['view']));
		}
	}

	public function getFactory(): ?object
	{
		return $this->factory;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if(!empty($id))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function getById(int $id): FilterEntity
	{
		$entity = null;

		$item = $this->getItemByField('id', $id);
		if(!empty($item))
		{
			$entity = $this->factory->fromDbObject($item);
		}

		return $entity;
	}
}