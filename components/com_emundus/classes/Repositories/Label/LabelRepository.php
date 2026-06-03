<?php
/**
 * @package     Tchooz\Repositories\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Label;

use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Label\LabelAssociationEntity;
use Tchooz\Entities\Label\LabelEntity;
use Tchooz\Factories\Label\LabelAssociationFactory;
use Tchooz\Factories\Label\LabelFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: '#__emundus_setup_action_tag',
	alias: 'esat',
	columns: [
		'id',
		'label',
		'class',
		'category',
		'ordering',
	]
)]
class LabelRepository extends EmundusRepository implements RepositoryInterface
{
	private LabelFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'label', self::class);

		$this->factory = new LabelFactory();
	}

	public function flush(LabelEntity $label): bool
	{
		if (empty(trim($label->getLabel())))
		{
			throw new \InvalidArgumentException('Label cannot be empty when flushing to database.');
		}

		if ($this->existsByLabel($label->getLabel()))
		{
			throw new \RuntimeException('A tag with this label already exists.');
		}

		$object = (object) [
			'label'    => $label->getLabel(),
			'class'    => $label->getClass(),
			'ordering' => $label->getOrdering(),
			'category' => $label->getCategory(),
		];

		if (empty($label->getId()))
		{
			if ($flushed = $this->db->insertObject($this->tableName, $object))
			{
				$label->setId((int) $this->db->insertid());
			}
		}
		else
		{
			$object->id = $label->getId();

			$flushed = $this->db->updateObject($this->tableName, $object, 'id');
		}

		return $flushed;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (!empty($id))
		{
			try
			{
				$query = $this->db->getQuery(true)
					->delete($this->db->qn($this->tableName, $this->alias))
					->where('id = :id')
					->bind(':id', $id, ParameterType::INTEGER);
				$this->db->setQuery($query);
				$deleted = $this->db->execute();

				// TODO: Delete associated tag associations
			}
			catch (\Exception $e)
			{
				Log::add('Error deleting export with ID ' . $id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.label.repository');
			}
		}

		return $deleted;
	}

	public function getById(int $id): ?LabelEntity
	{
		return $this->getItemByField('id', $id, true);
	}

	public function existsByLabel(string $label): bool
	{
		return $this->getItemByField('label', $label, false, 'id') !== null;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array<LabelEntity>
	 */
	public function getByFnum(string $fnum): array
	{
		$results = [];

		$cacheKey = 'labels_fnum_' . $fnum;
		if ($this->cache->contains($cacheKey))
		{
			$dbObjects = $this->cache->get($cacheKey);
		}

		if (empty($dbObjects))
		{
			$query = $this->db->getQuery(true);

			$query->select($this->columns)
				->from($this->db->quoteName('#__emundus_tag_assoc', 'eta'))
				->leftJoin($this->db->quoteName($this->tableName, $this->alias) . ' ON ' . $this->db->quoteName('eta.id_tag') . ' = ' . $this->db->quoteName($this->alias . '.id'))
				->where($this->db->quoteName('esat.id') . ' IS NOT NULL')
				->where($this->db->quoteName('eta.fnum') . ' = :fnum')
				->bind(':fnum', $fnum, ParameterType::STRING);
			$this->db->setQuery($query);
			$dbObjects = $this->db->loadObjectList();

			if (!empty($dbObjects))
			{
				$this->cache->store($dbObjects, $cacheKey);
			}
		}

		if (!empty($dbObjects))
		{
			$results = $this->factory->fromDbObjects($dbObjects, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $results;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array<LabelAssociationEntity>
	 */
	public function getLabelAssociationsByFnum(string $fnum): array
	{
		$results = [];

		$query = $this->db->getQuery(true);

		$selection = [];
		foreach ($this->columns as $column)
		{
			$column_only = str_replace($this->alias . '.', '', $column);
			$selection[] = $this->db->quoteName($column) . ' AS ' . $this->db->quoteName($this->alias . '_' . $column_only);
		}

		$query->select('eta.*' . (!empty($selection) ? ', ' . implode(', ', $selection) : ''))
			->from($this->db->quoteName('#__emundus_tag_assoc', 'eta'))
			->innerJoin($this->db->quoteName($this->tableName, $this->alias) . ' ON ' . $this->db->quoteName('eta.id_tag') . ' = ' . $this->db->quoteName($this->alias . '.id'))
			->where($this->db->quoteName('eta.fnum') . ' = :fnum')
			->bind(':fnum', $fnum);

		$this->db->setQuery($query);
		$dbObjects = $this->db->loadObjectList();

		if (!empty($dbObjects))
		{
			$results = LabelAssociationFactory::fromDbObjects($dbObjects, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $results;
	}

	public function getFactory(): LabelFactory
	{
		return $this->factory;
	}
}