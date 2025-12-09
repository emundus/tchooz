<?php
/**
 * @package     Tchooz\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Tchooz\Traits\TraitTable;

class EmundusRepository
{
	use TraitTable;

	protected bool $withRelations;
	protected array $exceptRelations = [];

	protected DatabaseInterface $db;

	protected string $tableName = '';
	protected string $primaryKey = 'id';
	protected string $alias = 't';
	protected array $columns = [];

	public function __construct(
		$withRelations = true,
		$exceptRelations = [],
		$name = '',
		$className = self::class
	)
	{
		$this->db              = Factory::getContainer()->get('DatabaseDriver');

		$this->tableName = $this->getTableName($className);
		$this->alias 	 = $this->getTableAlias($className);
		$this->columns   = $this->getTableColumns($className);

		$this->withRelations   = $withRelations;
		$this->exceptRelations = $exceptRelations;

		if (!empty($name))
		{
			Log::addLogger(['text_file' => "com_emundus.repository.{$name}.php"], Log::ALL, ["com_emundus.repository.{$name}"]);
		}
	}

	/**
	 * @param   string  $field
	 * @param   mixed   $value
	 *
	 * @return object|null
	 */
	public function getItemByField(string $field, mixed $value): ?object
	{
		$item = null;

		$query = $this->db->getQuery(true)
			->select($this->alias . '.*')
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName($this->alias . '.' . $field) . ' = ' . $this->db->quote($value));

		try {
			$this->db->setQuery($query);
			$item = $this->db->loadObject();
		} catch (\Exception $e) {
			Log::add('Error on fetching item by field ' . $field . ' for table ' . $this->tableName . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $item;
	}

	/**
	 * @param   string  $field
	 * @param   mixed   $value
	 *
	 * @return array
	 */
	public function getItemsByField(string $field, mixed $value): array
	{
		$items = [];

		$query = $this->db->getQuery(true)
			->select($this->alias . '.*')
			->from($this->db->quoteName($this->tableName, $this->alias));

		if (is_array($value)) {
			$query->where($this->db->quoteName($this->alias . '.' . $field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
		} else {
			$query->where($this->db->quoteName($this->alias . '.' . $field) . ' = ' . $this->db->quote($value));
		}

		try {
			$this->db->setQuery($query);
			$items = $this->db->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Error on fetching items by field ' . $field . ' for table ' . $this->tableName . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $items;
	}
}