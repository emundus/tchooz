<?php
/**
 * @package     Tchooz\Repositories\Programs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Programs;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\Programs\ProgramFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Traits\TraitTable;

#[TableAttribute(
	table: '#__emundus_setup_programmes',
	alias: 'esp',
	columns: [
		'id',
		'code',
		'label',
		'notes',
		'published',
		'programmes',
		'synthesis',
		'apply_online',
		'ordering',
		'logo',
		'color'
	]
)
]
class ProgramRepository extends EmundusRepository
{
	private ProgramFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'programme', self::class);
		$this->factory = new ProgramFactory();
	}

	public function getById(int $id): ?ProgramEntity
	{
		$program_entity = null;

		$query = $this->db->getQuery(true);
		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$program = $this->db->loadAssoc();

		if (!empty($program)) {
			$program_entity = $this->factory::fromDbObject($program);
		}

		return $program_entity;
	}

	public function getByCode(string $code): ?ProgramEntity
	{
		$program_entity = null;

		$query = $this->db->getQuery(true);
		$query->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where('code = ' . $this->db->quote($code));
		$this->db->setQuery($query);
		$program = $this->db->loadAssoc();

		if (!empty($program)) {
			$program_entity = $this->factory::fromDbObject($program);
		}

		return $program_entity;
	}

	public function getCodesByIds(array $ids): array
	{
		$codes = [];

		if (!empty($ids)) {
			$query = $this->db->getQuery(true);
			$query->select('code')
				->from($this->db->quoteName($this->tableName, $this->alias))
				->where('id IN (' . implode(',', array_map([$this->db, 'quote'], $ids)) . ')');
			$this->db->setQuery($query);
			$codes = $this->db->loadColumn();
		}

		return $codes;
	}

	public function getCategories(): array
	{
		$cacheKey = 'program_categories';
		if ($this->cache && $this->cache->contains($cacheKey)) {
			return $this->cache->get($cacheKey);
		}

		$query = $this->db->getQuery(true);
		$query->select('programmes')
			->from($this->db->quoteName($this->tableName))
			->where('published = 1')
			->order('programmes ASC');
		$this->db->setQuery($query);
		$categories = $this->db->loadColumn();
		$categories = array_filter(array_unique($categories));

		if ($this->cache && !empty($categories)) {
			$this->cache->store($categories, $cacheKey);
		}

		return $categories;
	}

	public function getFactory(): ProgramFactory
	{
		return $this->factory;
	}
}