<?php

namespace Tchooz\Repositories\ApplicationFile;

use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\ApplicationFile\ApplicationTagEntity;
use Tchooz\Factories\ApplicationFile\ApplicationTagFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: '#__emundus_setup_action_tag', alias: 'esat', columns: [
	'id',
	'label',
	'class',
	'category',
	'ordering'
])]
class TagsRepository extends EmundusRepository implements RepositoryInterface
{
	private ApplicationTagFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'application_tag', self::class);

		$this->factory = new ApplicationTagFactory();
	}

	public function getById(int $id): ?ApplicationTagEntity
	{
		$entity = null;

		if (empty($id))
		{
			return $entity;
		}

		try
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName(['id', 'label', 'class', 'category', 'ordering']))
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$this->db->setQuery($query);
			$dbObject = $this->db->loadObject();

			if (!empty($dbObject))
			{
				$entity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error fetching application tag ' . $id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.application_tag');
		}

		return $entity;
	}

	public function getByLabel(string $label): ?ApplicationTagEntity
	{
		$entity = null;

		if ($label === '')
		{
			return $entity;
		}

		try
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName(['id', 'label', 'class', 'category', 'ordering']))
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where('LOWER(' . $this->db->quoteName('label') . ') = LOWER(:label)')
				->bind(':label', $label, ParameterType::STRING);

			$this->db->setQuery($query);
			$dbObject = $this->db->loadObject();

			if (!empty($dbObject))
			{
				$entity = $this->factory->fromDbObject($dbObject, $this->withRelations, $this->exceptRelations, $this->db);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error fetching application tag by label: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.application_tag');
		}

		return $entity;
	}

	public function existsByLabel(string $label): bool
	{
		return $this->getByLabel($label) !== null;
	}

	public function create(string $label, string $color = 'label-default', string $category = '', int $ordering = 0): ?ApplicationTagEntity
	{
		$entity = null;

		if (trim($label) === '')
		{
			throw new \InvalidArgumentException('Tag label cannot be empty.');
		}

		if ($this->existsByLabel($label))
		{
			throw new \RuntimeException('A tag with this label already exists.');
		}

		$object = (object) [
			'label'    => $label,
			'class'    => $color,
			'category' => $category,
			'ordering' => $ordering,
		];

		try
		{
			if ($this->db->insertObject($this->tableName, $object))
			{
				$entity = new ApplicationTagEntity(
					id: (int) $this->db->insertid(),
					label: $label,
					color: $color,
					ordering: $ordering,
					category: $category
				);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Error creating application tag: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.application_tag');
		}

		return $entity;
	}

	public function delete(int $id): bool
	{
		$deleted = false;

		if (empty($id))
		{
			return $deleted;
		}

		try
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->tableName))
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$this->db->setQuery($query);
			$deleted = (bool) $this->db->execute();
		}
		catch (\Exception $e)
		{
			Log::add('Error deleting application tag ' . $id . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.application_tag');
		}

		return $deleted;
	}

	public function getFactory(): ?object
	{
		return $this->factory;
	}
}
