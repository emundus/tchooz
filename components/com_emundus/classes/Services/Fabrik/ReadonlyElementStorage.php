<?php
/**
 * @package     Tchooz\Services\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Fabrik;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Fabrik\ReadonlyStorageTypeEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;

/**
 * Storage column of a read-only element: TEXT by default, DATETIME / DATE when its source is a date.
 * The column type is never changed once a value has been stored in it.
 */
class ReadonlyElementStorage
{
	private DatabaseInterface $db;

	private FabrikRepository $fabrikRepository;

	public function __construct(?DatabaseInterface $db = null, ?FabrikRepository $fabrikRepository = null)
	{
		$this->db = $db ?? Factory::getContainer()->get(DatabaseInterface::class);

		if ($fabrikRepository === null)
		{
			$fabrikRepository = new FabrikRepository(true);
			$fabrikRepository->setFactory(new FabrikFactory($fabrikRepository));
		}
		$this->fabrikRepository = $fabrikRepository;

		Log::addLogger(['text_file' => 'com_emundus.fabrik.readonly.php'], Log::ALL, ['com_emundus.fabrik.readonly']);
	}

	public function getColumnType(FabrikElementEntity $element): ReadonlyStorageTypeEnum
	{
		$columns = $this->db->getTableColumns($this->getColumnTable($element));

		return ReadonlyStorageTypeEnum::fromColumnType($columns[$element->getName()] ?? '');
	}

	public function getColumnTypeById(int $elementId): ReadonlyStorageTypeEnum
	{
		$element = !empty($elementId) ? $this->fabrikRepository->getElementById($elementId) : null;

		return !empty($element) ? $this->getColumnType($element) : ReadonlyStorageTypeEnum::TEXT;
	}

	public function getTargetType(FabrikElementEntity $readonly): ReadonlyStorageTypeEnum
	{
		$source = $this->getSource($readonly);

		return $source !== null ? $this->getColumnType($source) : ReadonlyStorageTypeEnum::TEXT;
	}

	/**
	 * The source element when the read-only element is stored in a date column, so its stored value can be
	 * formatted the way the source formats it.
	 */
	public function getDateSource(int $readonlyElementId): ?FabrikElementEntity
	{
		$readonly = !empty($readonlyElementId) ? $this->fabrikRepository->getElementById($readonlyElementId) : null;

		return !empty($readonly) && $this->getColumnType($readonly)->isDate() ? $this->getSource($readonly) : null;
	}

	/**
	 * Legacy renderers branch on the element plugin: a read-only element stored in a date column takes the plugin
	 * and params of its source so its stored value is formatted the way the source does.
	 *
	 * @param   object[]  $elements  rows of #__fabrik_elements holding at least id, plugin and params
	 *
	 * @return object[]
	 */
	public function displayAsDateSource(array $elements): array
	{
		foreach ($elements as $element)
		{
			if (($element->plugin ?? '') !== ElementPluginEnum::EMUNDUSREADONLY->value)
			{
				continue;
			}

			$dateSource = $this->getDateSource((int) $element->id);
			if ($dateSource !== null)
			{
				$element->plugin = $dateSource->getPlugin()->value;
				$element->params = $dateSource->getParamsRaw();
			}
		}

		return $elements;
	}

	public function sync(int $readonlyElementId): bool
	{
		$readonly = $this->fabrikRepository->getElementById($readonlyElementId);
		if (empty($readonly))
		{
			return false;
		}

		$target = $this->getTargetType($readonly);

		if ($target === $this->getColumnType($readonly))
		{
			return true;
		}

		return $this->syncColumn($this->getColumnTable($readonly), $readonly->getName(), $target);
	}

	public function syncColumn(string $table, string $column, ReadonlyStorageTypeEnum $target): bool
	{
		try
		{
			if ($this->hasStoredData($table, $column))
			{
				Log::add('column ' . $table . '.' . $column . ' kept as is, it already holds data (target type ' . $target->value . ')', Log::INFO, 'com_emundus.fabrik.readonly');

				return false;
			}

			// Empty strings are not valid date values, the MODIFY would fail on them
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName($table))
				->set($this->db->quoteName($column) . ' = NULL')
				->where('CAST(' . $this->db->quoteName($column) . ' AS CHAR) = ' . $this->db->quote(''));
			$this->db->setQuery($query);
			$this->db->execute();

			if (!class_exists('EmundusHelperUpdate'))
			{
				require_once JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php';
			}
			$altered = \EmundusHelperUpdate::alterColumn($table, $column, $target->value);

			if (!$altered['status'])
			{
				throw new \RuntimeException($altered['message']);
			}

			return true;
		}
		catch (\Throwable $e)
		{
			Log::add('failed to change column ' . $table . '.' . $column . ' to ' . $target->value . ' (' . get_class($e) . ' at ' . $e->getFile() . ':' . $e->getLine() . '): ' . $e->getMessage(), Log::ERROR, 'com_emundus.fabrik.readonly');

			return false;
		}
	}

	private function hasStoredData(string $table, string $column): bool
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName($table))
			->where($this->db->quoteName($column) . ' IS NOT NULL')
			->where('CAST(' . $this->db->quoteName($column) . ' AS CHAR) <> ' . $this->db->quote(''));
		$this->db->setQuery($query);

		return (int) $this->db->loadResult() > 0;
	}

	private function getSource(FabrikElementEntity $readonly): ?FabrikElementEntity
	{
		$sourceId = (int) ($readonly->getParamsArray()['source_element_id'] ?? 0);

		return !empty($sourceId) ? $this->fabrikRepository->getElementById($sourceId) : null;
	}

	/**
	 * An element of a repeating group lives in the group's join table, any other in the list table.
	 * table_join is only read for repeating groups: on a database join element it is the lookup table.
	 */
	private function getColumnTable(FabrikElementEntity $element): string
	{
		$isRepeatGroup = (int) ($element->getGroupParamsArray()['repeat_group_button'] ?? 0) === 1;

		return $isRepeatGroup && !empty($element->getTableJoin()) ? $element->getTableJoin() : $element->getDbTableName();
	}
}
