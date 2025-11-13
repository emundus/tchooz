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
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_programmes')]
class ProgramRepository
{
	use TraitTable;

	private DatabaseInterface $db;

	private ProgramFactory $factory;

	private const COLUMNS = [
		't.id',
		't.code',
		't.label',
		't.notes',
		't.published',
		't.programmes',
		't.synthesis',
		't.apply_online',
		't.ordering',
		't.logo',
		't.color'
	];


	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.program.php'], Log::ALL, ['com_emundus.repository.program']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->factory = new ProgramFactory();
	}

	public function getById(int $id): ?ProgramEntity
	{
		$program_entity = null;

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where('t.id = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		$program = $this->db->loadAssoc();

		if (!empty($program)) {
			$program_entity = $this->factory->fromDbObject($program);
		}

		return $program_entity;
	}

	public function getByCode(string $code): ?ProgramEntity
	{
		$program_entity = null;

		$query = $this->db->getQuery(true);
		$query->select(self::COLUMNS)
			->from($this->db->quoteName($this->getTableName(self::class), 't'))
			->where('t.code = ' . $this->db->quote($code));
		$this->db->setQuery($query);
		$program = $this->db->loadAssoc();

		if (!empty($program)) {
			$program_entity = $this->factory->fromDbObject($program);
		}

		return $program_entity;
	}

	public function getCodesByIds(array $ids): array
	{
		$codes = [];

		if (!empty($ids)) {
			$query = $this->db->getQuery(true);
			$query->select('t.code')
				->from($this->db->quoteName($this->getTableName(self::class), 't'))
				->where('t.id IN (' . implode(',', array_map([$this->db, 'quote'], $ids)) . ')');
			$this->db->setQuery($query);
			$codes = $this->db->loadColumn();
		}

		return $codes;
	}
}