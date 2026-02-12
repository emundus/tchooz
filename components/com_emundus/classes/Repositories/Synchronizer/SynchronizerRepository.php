<?php

namespace Tchooz\Repositories\Synchronizer;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Factories\Synchronizer\SynchronizerFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute('#__emundus_setup_sync', 'sync')]
class SynchronizerRepository extends EmundusRepository implements RepositoryInterface
{
	private SynchronizerFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct(
			withRelations: $withRelations,
			exceptRelations: $exceptRelations,
			name: 'synchronizer',
			className: self::class
		);

		$this->factory = new SynchronizerFactory();
	}

	/**
	 * @param   SynchronizerEntity  $entity
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function flush(SynchronizerEntity $entity): bool
	{
		$flushed = false;

		$this->verifyRequirements($entity);

		if (!empty($entity->getId()))
		{
			$object = (object) [
				'id'          => $entity->getId(),
				'type'        => $entity->getType(),
				'name'        => $entity->getName(),
				'description' => $entity->getDescription(),
				'params'      => !empty($entity->getParams()) ? json_encode($entity->getParams()) : null,
				'config'      => !empty($entity->getConfig()) ? json_encode($entity->getConfig()) : null,
				'published'   => (int) $entity->isPublished(),
				'enabled'     => (int) $entity->isEnabled(),
				'icon'        => $entity->getIcon(),
				'consumptions'=> $entity->getConsumptions(),
				'context'     => !empty($entity->getContext()) ? $entity->getContext()->value : null,
			];
			$flushed = $this->db->updateObject($this->tableName, $object, 'id');
		}
		else
		{
			$synchronizerExists = $this->getByType($entity->getType());
			if ($synchronizerExists)
			{
				Log::add('Synchronizer type already exists: ' . $entity->getType(), Log::ERROR, 'com_emundus.repository.synchronizer');
				throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SYNCHRONIZER_TYPE_ALREADY_EXISTS'));
			}

			$object = (object) [
				'type'        => $entity->getType(),
				'name'        => $entity->getName(),
				'description' => $entity->getDescription(),
				'params'      => !empty($entity->getParams()) ? json_encode($entity->getParams()) : null,
				'config'      => !empty($entity->getConfig()) ? json_encode($entity->getConfig()) : null,
				'published'   => (int) $entity->isPublished(),
				'enabled'     => (int) $entity->isEnabled(),
				'icon'        => $entity->getIcon(),
				'consumptions'=> $entity->getConsumptions(),
				'context'     => !empty($entity->getContext()) ? $entity->getContext()->value : null,
			];

			try {
				$flushed = $this->db->insertObject($this->tableName, $object);
				if ($flushed)
				{
					$entityId = $this->db->insertid();
					$entity->setId($entityId);
				}
			} catch (\Exception $exception) {
			}
		}

		return $flushed;
	}

	/**
	 * @param   SynchronizerEntity  $entity
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function verifyRequirements(SynchronizerEntity $entity): void
	{
		if (empty($entity->getType()))
		{
			throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SYNCHRONIZER_TYPE_CANNOT_BE_EMPTY'));
		}

		if (empty($entity->getName()))
		{
			throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SYNCHRONIZER_NAME_CANNOT_BE_EMPTY'));
		}
	}

	/**
	 * @param   int  $id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('id') . ' = ' . $id);

			$this->db->setQuery($query);
			$deleted = (bool) $this->db->execute();
		}

		return $deleted;
	}

	/**
	 * @param   int  $id
	 *
	 * @return SynchronizerEntity|null
	 */
	public function getById(int $id): ?SynchronizerEntity
	{
		$entity = null;

		if (!empty($id))
		{
			$object = $this->getItemByField('id', $id);

			if (!empty($object)) {
				$entity = $this->factory->fromDbObjects([$object])[0] ?? null;
			}
		}

		return $entity;
	}

	public function applyFilters(QueryInterface $query, array $filters): void
	{
		if (empty($filters)) {
			$filters = ['published' => 1];
		}

		foreach ($filters as $field => $value)
		{
			if (str_contains($value, ','))
			{
				$values = explode(',', $value);
				$query->andWhere($this->db->quoteName($field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $values)) . ')');
			}
			else
			{
				$query->andWhere($this->db->quoteName($field) . ' = ' . $this->db->quote($value));
			}
		}
	}

	/**
	 * @param   array  $filters
	 * @param   int    $limit
	 * @param   int    $page
	 *
	 * @return array
	 */
	public function getAll(array $filters = [], int $limit = 10, int $page = 1): array
	{
		$entities = [];

		$query = $this->db->createQuery();
		$query->select($this->alias . '.*')
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('1 = 1');

		$this->applyFilters($query, $filters);

		$query->setLimit($limit, ($page - 1) * $limit);

		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();

		if (!empty($results))
		{
			$entities = $this->factory->fromDbObjects($results);
		}

		return $entities;
	}

	/**
	 * @param   string  $field
	 * @param   mixed   $value
	 *
	 * @return array
	 */
	public function getBy(string $field, mixed $value): array
	{
		$entities = [];

		$objects = $this->getItemsByField($field, $value);
		if (!empty($objects))
		{
			$entities = $this->factory->fromDbObjects($objects);
		}

		return $entities;
	}

	/**
	 * @param   string  $name
	 *
	 * @return SynchronizerEntity|null
	 */
	public function getByType(string $name): ?SynchronizerEntity
	{
		$entity = null;

		$object = $this->getItemByField('type', $name);

		if (!empty($object))
		{
			$entities = $this->factory->fromDbObjects([$object]);
			$entity = $entities[0] ?? null;
		}

		return $entity;
	}
}