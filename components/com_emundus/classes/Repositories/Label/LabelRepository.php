<?php
/**
 * @package     Tchooz\Repositories\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Label;

use http\Exception\InvalidArgumentException;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Entities\Label\LabelEntity;
use Tchooz\Entities\List\ListResult;
use Tchooz\Factories\Export\ExportFactory;
use Tchooz\Factories\Label\LabelFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(
	table: '#__emundus_setup_action_tag',
	alias: 'esat',
	columns: [
		'id'         => 'id',
		'label'         => 'label',
		'class'         => 'class',
		'ordering'         => 'ordering',
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
		if (empty($label->getLabel()))
		{
			throw new InvalidArgumentException('Label cannot be empty when flushing to database.');
		}

		$object = (object) [
			'label'   => $label->getLabel(),
			'class' => $label->getClass(),
			'ordering'    => $label->getOrdering()
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
		$labelEntity = null;

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->qn($this->tableName, $this->alias))
			->where('id = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($query);
		$dbObject = $this->db->loadObject();

		if ($dbObject)
		{
			$labelEntity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $labelEntity;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return array<LabelEntity>
	 */
	public function getByFnum(string $fnum): array
	{
		$results = [];

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->qn('#__emundus_tag_assoc','eta'))
			->leftJoin($this->db->qn($this->tableName, $this->alias) . ' ON ' . $this->db->qn('eta.id_tag') . ' = ' . $this->db->qn($this->alias . '.id'))
			->where($this->db->qn('eta.fnum') . ' = :fnum')
			->bind(':fnum', $fnum, ParameterType::STRING);
		$this->db->setQuery($query);
		$dbObjects = $this->db->loadObjectList();

		if ($dbObjects)
		{
			$results = $this->factory->fromDbObjects($dbObjects, $this->withRelations, $this->exceptRelations, $this->db);
		}

		return $results;
	}
}