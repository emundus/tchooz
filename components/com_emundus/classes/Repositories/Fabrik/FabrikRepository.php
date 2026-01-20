<?php
/**
 * @package     Tchooz\Repositories\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Fabrik;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Entities\Fabrik\FabrikFormEntity;
use Tchooz\Entities\Fabrik\FabrikGroupEntity;
use Tchooz\Enums\Fabrik\FabrikObjectsEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;

// TODO: Add caching layer
class FabrikRepository
{
	private FabrikFactory $factory;

	private bool $withRelations;

	private DatabaseInterface $db;

	const FABRIK_FORM_COLUMNS = [
		'ff.*',
		'fl.id AS list_id',
		'fl.db_table_name',
	];

	const FABRIK_GROUP_COLUMNS = [
		'fg.*',
	];

	const FABRIK_ELEMENT_COLUMNS = [
		'fe.*'
	];

	private array $elementFilters = [];

	public function __construct($withRelations = true)
	{
		$this->withRelations = $withRelations;
		$this->db            = Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * @param   bool  $withRelations
	 *
	 * @return $this
	 */
	public function withRelations(bool $withRelations = true): self
	{
		$this->withRelations = $withRelations;

		return $this;
	}

	public function getEncryptedTables(): array
	{
		$table_names = [];

		$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus']);
		$cache_key = 'fabrik_encrypted_tables';
		if ($cache->contains($cache_key))
		{
			$table_names = $cache->get($cache_key);
		}

		if(empty($table_names))
		{
			$query = $this->db->getQuery(true);

			$query->select($this->db->quoteName('fl.db_table_name'))
				->from($this->db->quoteName('#__fabrik_lists', 'fl'))
				->leftJoin($this->db->quoteName('#__fabrik_forms', 'fm') . ' ON fl.form_id = fm.id')
				->where('JSON_EXTRACT(fm.params, "$.note") = "encrypted"');

			try
			{
				$this->db->setQuery($query);
				$table_names = $this->db->loadColumn();

				$cache->store($table_names, $cache_key);
			}
			catch (\Exception $e)
			{
				Log::add('Failed to get encrypted table names ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $table_names;
	}

	public function getFormById(int $id): ?FabrikFormEntity
	{
		$form = null;

		$query = $this->db->getQuery(true);

		try
		{
			$query->select(self::FABRIK_FORM_COLUMNS)
				->from($this->db->quoteName('#__fabrik_forms', 'ff'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
				->where($this->db->quoteName('ff.published') . ' = 1')
				->where($this->db->quoteName('ff.id') . ' = ' . $this->db->quote($id));

			$this->db->setQuery($query);
			$form = $this->db->loadObject();

			if (!empty($form))
			{
				$form = $this->factory->buildFormEntity($form, $this->withRelations);
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $form;
	}

	/**
	 * @param   int  $profileId
	 *
	 * @return array<FabrikFormEntity>
	 */
	public function getFormsByProfileId(int $profileId): array
	{
		$forms = [];

		$query = $this->db->getQuery(true);

		try
		{
			$query->select(self::FABRIK_FORM_COLUMNS)
				->from($this->db->quoteName('#__menu', 'm'))
				->innerJoin($this->db->quoteName('#__emundus_setup_profiles', 'esp') . ' ON ' . $this->db->quoteName('esp.menutype') . ' = ' . $this->db->quoteName('m.menutype') . ' AND ' . $this->db->quoteName('esp.id') . ' = ' . $this->db->quote($profileId))
				->innerJoin($this->db->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->db->quoteName('ff.id') . ' = SUBSTRING_INDEX(SUBSTRING(m.link, LOCATE("formid=",m.link)+7, 4), "&", 1)')
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
				->where($this->db->quoteName('m.published') . ' = 1')
				->where($this->db->quoteName('m.parent_id') . ' != 1')
				->order('m.lft');

			$this->db->setQuery($query);
			$lists = $this->db->loadObjectList();

			if (!empty($lists))
			{
				$forms = $this->factory->fromDbObjects($lists, $this->withRelations);
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $forms;
	}

	/**
	 * @param   int  $formId
	 *
	 * @return array<FabrikGroupEntity>
	 *
	 * @since version
	 */
	public function getGroupsByFormId(int $formId): array
	{
		$groups = [];

		$query = $this->db->getQuery(true);

		try
		{
			$query->select(self::FABRIK_GROUP_COLUMNS)
				->from($this->db->quoteName('#__fabrik_groups', 'fg'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ff') . ' ON ' . $this->db->quoteName('ff.group_id') . ' = ' . $this->db->quoteName('fg.id'))
				->where($this->db->quoteName('ff.form_id') . ' = ' . $this->db->quote($formId))
				->where($this->db->quoteName('fg.published') . ' = 1')
				->where('JSON_EXTRACT(fg.params,"$.repeat_group_show_first")' . ' = ' . $this->db->quote(1))
				->order('ff.ordering');

			$this->db->setQuery($query);
			$groupObjects = $this->db->loadObjectList();

			if (!empty($groupObjects))
			{
				$groups = $this->factory->fromDbObjects($groupObjects, $this->withRelations, FabrikObjectsEnum::GROUP);
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $groups;
	}

	public function getGroupsOrdering(int $formId): array
	{
		$groupOrder = [];

		$query = $this->db->getQuery(true);

		try
		{
			$query->select($this->db->quoteName('fg.id'))
				->from($this->db->quoteName('#__fabrik_groups', 'fg'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ff') . ' ON ' . $this->db->quoteName('ff.group_id') . ' = ' . $this->db->quoteName('fg.id'))
				->where($this->db->quoteName('ff.form_id') . ' = ' . $this->db->quote($formId))
				->where($this->db->quoteName('fg.published') . ' = 1')
				->order('ff.ordering');

			$this->db->setQuery($query);
			$groupOrder = $this->db->loadColumn();
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $groupOrder;
	}

	/**
	 * @param   int  $groupId
	 *
	 * @return array<FabrikElementEntity>
	 *
	 * @since version
	 */
	public function getElementsByGroupId(int $groupId): array
	{
		$elements = [];

		try
		{
			/*$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('output', ['defaultgroup' => 'com_emundus']);
			$cache_key = 'fabrik_elements_group_' . $groupId;
			if ($cache->contains($cache_key))
			{
				$elementsCached = $cache->get($cache_key);
				if (!empty($elementsCached))
				{
					foreach ($elementsCached as $elementCached)
					{
						// Build entity from cached data
						$elementEntity = $this->factory->buildElementEntity((object) $elementCached);
						if (!empty($elementEntity) && $elementEntity instanceof FabrikElementEntity)
						{
							$elements[] = $elementEntity;
						}
					}
				}
			}*/

			if (empty($elements))
			{
				$query   = $this->buildElementQuery();
				$this->elementFilters = array_merge($this->elementFilters, ['group_id' => $groupId]);
				$this->applyElementFilters($query);

				$this->db->setQuery($query);
				$elementObjects = $this->db->loadObjectList();

				if (!empty($elementObjects))
				{
					$elements = $this->factory->fromDbObjects($elementObjects, $this->withRelations, FabrikObjectsEnum::ELEMENT);

					// Store in cache
					/*$elementsToCache = [];
					foreach ($elements as $element)
					{
						$elementsToCache[] = $element->__serialize();
					}
					$cache->store($elementsToCache, $cache_key);*/
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $elements;
	}

	public function getElementById(int $id): ?FabrikElementEntity
	{
		$element = null;

		try
		{
			$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('output', ['defaultgroup' => 'com_emundus']);
			$cache_key = 'fabrik_element_' . $id;
			if ($cache->contains($cache_key))
			{
				$elementCached = $cache->get($cache_key);
				if (!empty($elementCached))
				{
					// Build entity from cached data
					$element = $this->factory->buildElementEntity((object) $elementCached);
				}
			}

			if (empty($element))
			{
				$this->elementFilters = array_merge($this->elementFilters, ['id' => $id]);
				$query   = $this->buildElementQuery(true);
				$this->applyElementFilters($query, true);

				$this->db->setQuery($query);
				$elementObject = $this->db->loadObject();

				if (!empty($elementObject))
				{
					$element = $this->factory->fromDbObject($elementObject, $this->withRelations, FabrikObjectsEnum::ELEMENT);

					if (!empty($element) && $element instanceof FabrikElementEntity)
					{
						$cache->store($element->toArray(false), $cache_key);
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $element;
	}

	public function buildElementQuery(bool $withJoins = false): QueryInterface
	{
		$query = $this->db->getQuery(true);

		$columns = self::FABRIK_ELEMENT_COLUMNS;
		if ($withJoins)
		{
			$columns = array_merge($columns, ['fg.params AS group_params', 'fl.db_table_name', 'fj.table_join']);
		}
		$query->select($columns)
			->from($this->db->quoteName('#__fabrik_elements', 'fe'));

		if ($withJoins)
		{
			$query->leftJoin($this->db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $this->db->quoteName('fe.group_id') . ' = ' . $this->db->quoteName('fg.id'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ff') . ' ON ' . $this->db->quoteName('ff.group_id') . ' = ' . $this->db->quoteName('fe.group_id'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.form_id'))
				->leftJoin(
					$this->db->quoteName('#__fabrik_joins', 'fj') . ' ON (' .
					$this->db->quoteName('fl.id') . ' = ' . $this->db->quoteName('fj.list_id') .
					' AND (' . $this->db->quoteName('fg.id') . '= ' . $this->db->quoteName('fj.group_id') . ' OR ' . $this->db->quoteName('fe.id') . '= ' . $this->db->quoteName('fj.element_id') . '))');
		}

		$query->order('fe.ordering');

		return $query;
	}

	public function applyElementFilters(QueryInterface $query, bool $withJoins = false): void
	{
		$filters = $this->elementFilters;
		if (in_array('id', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.id') . ' = ' . $this->db->quote($filters['id']));
		}

		if (in_array('group_id', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.group_id') . ' = ' . $this->db->quote($filters['group_id']));
		}

		if ($withJoins)
		{
			$query->where($this->db->quoteName('fg.published') . ' = 1')
				->where($this->db->quoteName('fl.published') . ' = 1');
		}

		if (in_array('published', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.published') . ' = ' . $this->db->quote($filters['published']));
		}
		else {
			$query->where($this->db->quoteName('fe.published') . ' <> -2');
		}

		if (in_array('hidden', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.hidden') . ' = ' . $this->db->quote($filters['hidden']));
		}

		if (in_array('exluded_elements', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.plugin') . ' NOT IN (' . implode(',', array_map([$this->db, 'quote'], $filters['exluded_elements'])) . ')');
		}
	}

	public function getElementAlias(int $id): string
	{
		$alias = '';

		try
		{
			$cache     = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('output', ['defaultgroup' => 'com_emundus']);
			$cache_key = 'fabrik_alias_' . $id;
			if ($cache->contains($cache_key))
			{
				return $cache->get($cache_key);
			}

			$query = $this->db->getQuery(true);

			$query->select($this->db->quoteName('alias'))
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

			$this->db->setQuery($query);
			$alias = (string) $this->db->loadResult();

			$cache->store($alias, $cache_key);
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $alias;
	}

	public function deleteAliases(array $aliases): bool
	{
		$deleted = false;

		if (!empty($aliases))
		{
			try
			{
				$query = $this->db->getQuery(true)
					->update($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('alias') . ' = null')
					->where($this->db->quoteName('alias') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $aliases)) . ')');
				$this->db->setQuery($query);
				$deleted = $this->db->execute();

				if(!class_exists('EmundusHelperFabrik'))
				{
					require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
				}
				\EmundusHelperFabrik::clearFabrikAliasesCache();
			}
			catch (\Exception $e)
			{
				Log::add('Error deleting fabrik element aliases: ' . $e->getMessage(), Log::ERROR, 'com_emundus.fabrik.repository');
			}
		}

		return $deleted;
	}

	public function getMenuItemIdByFormId(int $formId): ?int
	{
		$menuItemId = null;

		$query = $this->db->getQuery(true);

		try
		{
			$query->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' LIKE ' . $this->db->quote('index.php?option=com_fabrik&view=form&formid=' . $formId))
				->where($this->db->quoteName('published') . ' = 1');

			$this->db->setQuery($query);
			$menuItemId = (int) $this->db->loadResult();
		}
		catch (\Exception $e)
		{
			Log::add('Failed to get menu item id by fabrik form id ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return !empty($menuItemId) ? $menuItemId : null;
	}

	public function setFactory(FabrikFactory $factory = null): void
	{
		$this->factory = $factory;
	}

	public function getElementFilters(): array
	{
		return $this->elementFilters;
	}

	public function setElementFilters(array $elementFilters): void
	{
		$this->elementFilters = $elementFilters;
	}
}