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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Tchooz\Entities\Calculation\Templates\CalculateDatesDiff;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Entities\Fabrik\FabrikFormEntity;
use Tchooz\Entities\Fabrik\FabrikGroupEntity;
use Tchooz\Entities\Profile\ProfileEntity;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Fabrik\FabrikObjectsEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Factories\Language\LanguageFactory;

// TODO: Add caching layer
class FabrikRepository
{
	private FabrikFactory $factory;

	private bool $withRelations;

	private DatabaseInterface $db;

	private ?User $user;

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

	public function __construct($withRelations = true, ?User $user = null)
	{
		$this->withRelations = $withRelations;
		$this->db            = Factory::getContainer()->get('DatabaseDriver');
		$this->user          = empty($user) ? Factory::getApplication()->getIdentity() : $user;
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

		if (empty($table_names))
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
	 * @param   int  $elementId
	 *
	 * @return FabrikFormEntity|null
	 *
	 * @since version
	 */
	public function getFormFromElementId(int $elementId): ?FabrikFormEntity
	{
		$form = null;

		if (!empty($elementId))
		{
			try
			{
				$query = $this->db->createQuery();

				$query->select(self::FABRIK_FORM_COLUMNS)
					->from($this->db->quoteName('#__fabrik_forms', 'ff'))
					->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
					->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $this->db->quoteName('ffg.form_id') . ' = ' . $this->db->quoteName('ff.id'))
					->leftJoin($this->db->quoteName('#__fabrik_elements', 'fe') . ' ON ' . $this->db->quoteName('fe.group_id') . ' = ' . $this->db->quoteName('ffg.group_id'))
					->where($this->db->quoteName('ff.published') . ' = 1')
					->where($this->db->quoteName('fe.id') . ' = ' . $this->db->quote($elementId));

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
				$query                = $this->buildElementQuery($this->withRelations);
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
				$query                = $this->buildElementQuery(true);
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

	public function getElementsByIds(array $ids): array
	{
		$elements = [];

		if (!empty($ids))
		{
			try
			{
				$this->elementFilters = array_merge($this->elementFilters, ['id' => $ids]);
				$query                = $this->buildElementQuery(true);
				$this->applyElementFilters($query, true);

				$this->db->setQuery($query);
				$elementObjects = $this->db->loadObjectList();

				if (!empty($elementObjects))
				{
					$elements = $this->factory->fromDbObjects($elementObjects, $this->withRelations, FabrikObjectsEnum::ELEMENT);
				}
			}
			catch (\Exception $e)
			{
				Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $elements;
	}

	/**
	 * @param   array  $filters
	 * @param   int    $limit
	 * @param   int    $page
	 *
	 * @return array<FabrikElementEntity>
	 */
	public function getElements(array $filters, int $limit = 100, int $page = 1): array
	{
		$elements = [];

		try
		{
			$query                = $this->buildElementQuery($this->withRelations);
			$this->elementFilters = array_merge($this->elementFilters, $filters);
			$this->applyElementFilters($query, $this->withRelations);

			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);

			$this->db->setQuery($query);
			$elementObjects = $this->db->loadObjectList();

			if (!empty($elementObjects))
			{
				// todo: nonsense, factory should not be instantiated this way, nor having as a parameter of the repository, need to rethink this
				if (empty($this->factory))
				{
					$this->factory = new FabrikFactory($this);
				}

				$elements = $this->factory->fromDbObjects($elementObjects, $this->withRelations, FabrikObjectsEnum::ELEMENT);
			}
		}
		catch (\Exception $e)
		{
			Log::add($e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $elements;
	}

	public function buildElementQuery(bool $withJoins = false): QueryInterface
	{
		$query = $this->db->getQuery(true);


		// todo: group concat on fabrik joins, and create a specific FabrikJoinEntity if needed in future
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
					' AND ((' . $this->db->quoteName('fg.id') . '= ' . $this->db->quoteName('fj.group_id') . '  AND JSON_EXTRACT(fj.params,"$.type") = "group")  OR ' . $this->db->quoteName('fe.id') . '= ' . $this->db->quoteName('fj.element_id') . '))');
		}

		$query->group('fe.id')
			->order('fe.ordering');

		return $query;
	}

	public function applyElementFilters(QueryInterface $query, bool $withJoins = false): void
	{
		$filters = $this->elementFilters;
		if (in_array('id', array_keys($filters)))
		{
			if (is_array($filters['id']))
			{
				$query->where($this->db->quoteName('fe.id') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $filters['id'])) . ')');
			}
			else
			{
				$query->where($this->db->quoteName('fe.id') . ' = ' . $this->db->quote($filters['id']));
			}
		}

		if (in_array('group_id', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.group_id') . ' = ' . $this->db->quote($filters['group_id']));
		}

		if ($withJoins)
		{
			$query->where($this->db->quoteName('fg.published') . ' = 1')
				->where($this->db->quoteName('fl.published') . ' = 1');

			if (in_array('form_id', array_keys($filters)))
			{
				$query->where($this->db->quoteName('ff.form_id') . ' = ' . $this->db->quote($filters['form_id']));
			}
		}

		if (in_array('published', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.published') . ' = ' . $this->db->quote($filters['published']));
		}
		else
		{
			$query->where($this->db->quoteName('fe.published') . ' <> -2');
		}

		if (in_array('hidden', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.hidden') . ' = ' . $this->db->quote($filters['hidden']));
		}

		if (in_array('excluded_elements', array_keys($filters)))
		{
			$query->where($this->db->quoteName('fe.name') . ' NOT IN (' . implode(',', array_map([$this->db, 'quote'], $filters['excluded_elements'])) . ')');
		}

		if (in_array('plugin', array_keys($filters)))
		{
			if (is_array($filters['plugin']))
			{
				$query->where($this->db->quoteName('fe.plugin') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $filters['plugin'])) . ')');
			}
			else
			{
				$query->where($this->db->quoteName('fe.plugin') . ' = ' . $this->db->quote($filters['plugin']));
			}
		}

		if (in_array('name', array_keys($filters)))
		{
			if (is_array($filters['name']))
			{
				$query->where($this->db->quoteName('fe.name') . ' IN (' . implode(',', array_map([$this->db, 'quote'], $filters['name'])) . ')');
			}
			else
			{
				$query->where($this->db->quoteName('fe.name') . ' = ' . $this->db->quote($filters['name']));
			}
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

				if (!class_exists('EmundusHelperFabrik'))
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

	public function duplicateForms(array $ids, ?ProfileEntity $profile = null, array $languages = []): array
	{
		$duplicatedForms = [];

		$query = $this->db->getQuery(true);

		if (!class_exists('EmundusHelperFabrik'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		}
		if (!class_exists('EmundusHelperMenu'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/menu.php';
		}
		if (!class_exists('EmundusHelperUpdate'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		}
		if (!class_exists('EmundusModelFalang'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/models/falang.php');
		}
		$falang = new \EmundusModelFalang();

		// Get the header menu
		$menu_parent = \EmundusHelperMenu::getHeaderMenu($profile->getMenutype());
		$eMConfig    = ComponentHelper::getParams('com_emundus');
		$modules     = $eMConfig->get('form_builder_page_creation_modules', [93, 102, 103, 104, 168, 170]);

		foreach ($ids as $formid)
		{
			$query->clear()
				->select('ff.*, fl.id as list_id, fl.db_table_name')
				->from($this->db->quoteName('#__fabrik_forms', 'ff'))
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
				->where($this->db->quoteName('ff.id') . ' = ' . $this->db->quote($formid));
			$this->db->setQuery($query);
			$oldForm = $this->db->loadObject();

			$label = array();
			$intro = array();

			if (empty($languages))
			{
				$languages = LanguageHelper::getLanguages();
			}

			foreach ($languages as $language)
			{
				# Fabrik has a functionnality that adds <p> tags around the intro text, we need to remove them
				$stripped_intro = strip_tags($oldForm->intro);
				if ($oldForm->intro == '<p>' . $stripped_intro . '</p>')
				{
					$oldForm->intro = $stripped_intro;
				}

				$label[$language->sef] = LanguageFactory::getTranslation($oldForm->label, $language->lang_code);
				$intro[$language->sef] = LanguageFactory::getTranslation($oldForm->intro, $language->lang_code);

				if (empty($label[$language->sef]))
				{
					$label[$language->sef] = '';
				}
				if (empty($intro[$language->sef]))
				{
					$intro[$language->sef] = '';
				}
			}

			$duplicatedForm = $this->duplicateForm($oldForm, $profile, $languages);

			// Duplicate the form-menu
			$query
				->clear()
				->select('rgt')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($profile->getMenutype()))
				->andWhere($this->db->quoteName('path') . ' LIKE ' . $this->db->quote($profile->getMenutype() . '%'))
				->andWhere($this->db->quoteName('published') . ' = 1')
				->order('rgt');
			$this->db->setQuery($query);
			$menus = $this->db->loadObjectList();
			$rgts  = [];
			foreach ($menus as $menu)
			{
				if (!in_array($menu->rgt, $rgts))
				{
					$rgts[] = intval($menu->rgt);
				}
			}

			$params    = \EmundusHelperFabrik::prepareFabrikMenuParams();
			$datas     = [
				'menutype'     => $profile->getMenutype(),
				'title'        => 'FORM_' . $profile->getId() . '_' . $duplicatedForm->form->id,
				'link'         => 'index.php?option=com_fabrik&view=form&formid=' . $duplicatedForm->form->id,
				'path'         => $menu_parent->path . '/' . str_replace(\EmundusHelperMenu::getSpecialCharacters(), '-', strtolower($label['fr'])) . '-' . $duplicatedForm->form->id,
				'alias'        => 'form-' . $duplicatedForm->form->id . '-' . str_replace(\EmundusHelperMenu::getSpecialCharacters(), '-', strtolower($label['fr'])),
				'type'         => 'component',
				'component_id' => ComponentHelper::getComponent('com_fabrik')->id,
				'params'       => $params,
				'note'         => '',
			];
			$parent_id = 1;
			if ($duplicatedForm->list->db_table_name != 'jos_emundus_declaration' && $menu_parent->id != 0)
			{
				$parent_id = $menu_parent->id;
			}
			$result    = \EmundusHelperUpdate::addJoomlaMenu($datas, $parent_id, 1, 'last-child', $modules);
			$newmenuid = $result['id'];

			if (!empty($newmenuid))
			{
				$update = [
					'id'        => $newmenuid,
					'alias'     => 'menu-profile' . $profile->getId() . '-form-' . $newmenuid,
					'published' => 1
				];
				$update = (object) $update;
				$this->db->updateObject('#__menu', $update, 'id');

				$falang->insertFalang($label, $newmenuid, 'menu', 'title');
			}

			$duplicatedForms[] = $duplicatedForm;
		}

		return $duplicatedForms;
	}

	public function duplicateForm(object $oldForm, ?ProfileEntity $profile = null, array $languages = []): object
	{
		$form     = clone $oldForm;
		$form->id = 0;

		$oldListId = $form->list_id;
		unset($form->list_id);
		$dbTableName = $form->db_table_name;
		unset($form->db_table_name);

		$this->flushForm($form);

		if (
			str_starts_with('jos_emundus_evaluations', $dbTableName)
			|| str_starts_with('jos_emundus_final_grade', $dbTableName)
			|| str_starts_with('jos_emundus_admission', $dbTableName)
		)
		{
			$keyPrefix = 'FORM_EVALUATION_';
		}
		else
		{
			if (!empty($profile))
			{
				$keyPrefix = 'FORM_' . $profile->getId() . '_';
			}
			else
			{
				$keyPrefix = 'FORM_';
			}
		}

		$labelPrefix = !empty($labelPrefix) ? Text::_($labelPrefix) : '';
		$this->updateFabrikLabel($form->id, $keyPrefix, '', $labelPrefix);
		$this->updateFabrikLabel($form->id, $keyPrefix, '_INTRO', '', 'fabrik_forms', 'intro');

		$list = $this->duplicateList($oldListId, $form->id, $profile);

		if (!empty($profile))
		{
			$data = (object) [
				'form_id'    => $form->id,
				'profile_id' => $profile->getId(),
				'created'    => date('Y-m-d H:i:s'),
			];
			$this->db->insertObject('#__emundus_setup_formlist', $data);
		}

		$groups = $this->duplicateGroups($oldForm->id, $form, $list, $languages);

		$this->duplicateConditions($oldForm, $form);

		return (object) [
			'form'   => $form,
			'list'   => $list,
			'groups' => $groups,
		];
	}

	public function flushForm(object $form): bool
	{
		if (empty($form->id))
		{
			$form->created_by       = $this->user->id;
			$form->created          = date('Y-m-d H:i:s');
			$form->modified         = null;
			$form->publish_up       = date('Y-m-d H:i:s');
			$form->checked_out_time = date('Y-m-d H:i:s');
			$form->publish_down     = null;

			$flushed = $this->db->insertObject('#__fabrik_forms', $form);
			if ($flushed)
			{
				$form->id = $this->db->insertid();
			}
		}
		else
		{
			$form->modified_by = $this->user->id;
			$form->modified    = date('Y-m-d H:i:s');

			return $this->db->updateObject('#__fabrik_forms', $form, 'id');
		}

		return $flushed;
	}

	public function duplicateList(int $id, int $formId, ?ProfileEntity $profile = null): object
	{
		$query = $this->db->getQuery(true);

		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__fabrik_lists'))
			->where('id = ' . $id);
		$this->db->setQuery($query);
		$list = $this->db->loadObject();

		$list->id      = 0;
		$list->form_id = $formId;
		$list->access  = !empty($profile) ? $profile->getAclAroGroups() : 1;

		if ($this->flushList($list))
		{
			$keyPrefix = str_starts_with('jos_emundus_evaluations', $list->db_table_name) ? 'FORM_EVALUATION_' : 'FORM_' . $profile?->getId() ?? 0 . '_';
			$this->updateFabrikLabel($list->id, $keyPrefix, '', Text::_('COPY_OF') . ' ', 'fabrik_lists');
			$this->updateFabrikLabel($list->id, $keyPrefix, '_INTRO', '', 'fabrik_lists', 'introduction');
		}

		return $list;
	}

	public function flushList(object $list): bool
	{
		if (empty($list->id))
		{
			$list->created_by       = $this->user->id;
			$list->created          = date('Y-m-d H:i:s');
			$list->modified         = null;
			$list->publish_up       = date('Y-m-d H:i:s');
			$list->checked_out_time = date('Y-m-d H:i:s');
			$list->publish_down     = null;

			$flushed = $this->db->insertObject('#__fabrik_lists', $list);
			if ($flushed)
			{
				$list->id = $this->db->insertid();
			}
		}
		else
		{
			$list->modified_by = $this->user->id;
			$list->modified    = date('Y-m-d H:i:s');

			return $this->db->updateObject('#__fabrik_lists', $list, 'id');
		}

		return $flushed;
	}

	public function duplicateGroups(int $oldFormId, object $form, object $list, array $languages = []): array
	{
		$duplicatedGroups = [];

		$query = $this->db->getQuery(true);

		$query->clear()
			->select('fg.*')
			->from($this->db->quoteName('#__fabrik_formgroup', 'ffg'))
			->leftJoin($this->db->quoteName('#__fabrik_groups', 'fg') . ' ON ' . $this->db->quoteName('fg.id') . ' = ' . $this->db->quoteName('ffg.group_id'))
			->where('ffg.form_id = ' . $oldFormId);
		$this->db->setQuery($query);
		$groups = $this->db->loadObjectList();

		$ordering = 0;
		foreach ($groups as $group)
		{
			$ordering++;
			$newGroup = $this->duplicateGroup($group, $list, $form, $oldFormId, true, $languages);

			$insert = [
				'form_id'  => $form->id,
				'group_id' => $newGroup->id,
				'ordering' => $ordering
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__fabrik_formgroup', $insert);

			$duplicatedGroups[] = $newGroup;
		}

		return $duplicatedGroups;
	}

	public function duplicateGroup(object $oldGroup, object $list, object $form, int $oldFormId, bool $duplicateElements = true, array $languages = []): object
	{
		$query = $this->db->getQuery(true);

		$group      = clone $oldGroup;
		$group->id  = 0;
		$duplicated = $this->flushGroup($group);
		if (!$duplicated)
		{
			throw new \RuntimeException('Cannot duplicate group');
		}

		if ($group->is_join)
		{
			$query->clear()
				->select('table_join')
				->from($this->db->quoteName('#__fabrik_joins'))
				->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($oldGroup->id))
				->andWhere($this->db->quoteName('table_join_key') . ' = ' . $this->db->quote('parent_id'));
			$this->db->setQuery($query);
			$repeat_table_to_copy = $this->db->loadResult();

			$joins_params = '{"type":"group","pk":"`' . $repeat_table_to_copy . '`.`id`"}';

			$insert = [
				'list_id'         => $list->id,
				'element_id'      => 0,
				'join_from_table' => $list->db_table_name,
				'table_join'      => $repeat_table_to_copy,
				'table_key'       => 'id',
				'table_join_key'  => 'parent_id',
				'join_type'       => 'left',
				'group_id'        => $group->id,
				'params'          => $joins_params
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__fabrik_joins', $insert);
		}

		$groupParams = json_decode($group->params);

		$labelKey            = 'GROUP_' . $form->id . '_' . $group->id;
		$introKey            = 'FORM_' . $form->id . '_GROUP_' . $group->id . '_INTRO';
		$oldIntroKey         = $groupParams->intro ?? '';
		$labels_to_duplicate = [];
		$intro_to_duplicate  = [];
		foreach ($languages as $language)
		{
			$labels_to_duplicate[$language->sef] = LanguageFactory::getTranslation($oldGroup->label, $language->lang_code);
			if (is_null($labels_to_duplicate[$language->sef]))
			{
				$labels_to_duplicate[$language->sef] = '';
			}

			if (!empty($oldIntroKey))
			{
				$intro_to_duplicate[$language->sef] = LanguageFactory::getTranslation($group->label, $language->lang_code);
				if (is_null($intro_to_duplicate[$language->sef]))
				{
					$intro_to_duplicate[$language->sef] = '';
				}
			}
			else
			{
				$intro_to_duplicate[$language->sef] = '';
			}
		}
		LanguageFactory::translate($labelKey, $labels_to_duplicate, 'fabrik_groups', $group->id, 'label', $this->user->id);
		LanguageFactory::translate($introKey, $intro_to_duplicate, 'fabrik_groups', $group->id, 'intro', $this->user->id);

		$groupParams->intro = $introKey;
		$group->label       = $labelKey;
		$group->name        = $labelKey;
		$group->params      = json_encode($groupParams);
		$this->flushGroup($group);

		if ($duplicateElements)
		{
			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__fabrik_elements'))
				->where($this->db->quoteName('group_id') . ' = ' . $this->db->quote($oldGroup->id))
				->where($this->db->quoteName('published') . ' <> -2');
			$this->db->setQuery($query);
			$elements = $this->db->loadObjectList();

			foreach ($elements as $element)
			{
				$this->duplicateElement($element, $group, $languages);
			}
		}

		// once all elements have been duplicated, check emundus_calculation parameters and change it
		$this->updateCalculationParametersAfterDuplicate($oldFormId, $form);

		return $group;
	}

	public function flushGroup(object $group): bool
	{
		if (empty($group->id))
		{
			$group->created_by       = $this->user->id;
			$group->created          = date('Y-m-d H:i:s');
			$group->modified         = null;
			$group->checked_out_time = date('Y-m-d H:i:s');

			$flushed = $this->db->insertObject('#__fabrik_groups', $group);
			if ($flushed)
			{
				$group->id = $this->db->insertid();
			}
		}
		else
		{
			$group->modified_by = $this->user->id;
			$group->modified    = date('Y-m-d H:i:s');

			return $this->db->updateObject('#__fabrik_groups', $group, 'id');
		}

		return $flushed;
	}

	public function duplicateElement(object $oldElement, object $group, array $languages): object
	{
		$query = $this->db->getQuery(true);

		$element            = clone $oldElement;
		$element->id        = 0;
		$element->group_id  = $group->id;
		$element->parent_id = $oldElement->id;
		$this->flushElement($element);

		// Joins
		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__fabrik_joins'))
			->where($this->db->quoteName('element_id') . ' = ' . $this->db->quote($oldElement->id));
		$this->db->setQuery($query);
		$join = $this->db->loadObject();
		if (!empty($join))
		{
			$join->id         = 0;
			$join->element_id = $element->id;
			$join->group_id   = $element->group_id;

			$this->db->insertObject('#__fabrik_joins', $join);
		}
		//

		// JSActions
		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__fabrik_jsactions'))
			->where($this->db->quoteName('element_id') . ' = ' . $this->db->quote($oldElement->id));
		$this->db->setQuery($query);
		$jsactions = $this->db->loadObjectList();
		foreach ($jsactions as $jsaction)
		{
			$jsaction->id         = 0;
			$jsaction->element_id = $element->id;

			$this->db->insertObject('#__fabrik_jsactions', $jsaction);
		}
		//

		$params = json_decode($element->params);

		// Update translation files
		$labelKey = 'ELEMENT_' . $element->group_id . '_' . $element->id;
		$plugin   = ElementPluginEnum::tryFrom($element->plugin);
		if ($plugin->isChoicesField() && $params->sub_options)
		{
			$sub_labels = [];
			foreach ($params->sub_options->sub_labels as $index => $sub_label)
			{
				$labels_to_duplicate = array();

				foreach ($languages as $language)
				{
					$labels_to_duplicate[$language->sef] = LanguageFactory::getTranslation($sub_label, $language->lang_code);
				}
				LanguageFactory::translate('SUBLABEL_' . $element->group_id . '_' . $element->id . '_' . $index, $labels_to_duplicate, 'fabrik_elements', $element->id, 'sub_labels', $this->user->id);
				$sub_labels[] = 'SUBLABEL_' . $element->group_id . '_' . $element->id . '_' . $index;
			}
			$params->sub_options->sub_labels = $sub_labels;
		}

		$labels_to_duplicate = array();
		foreach ($languages as $language)
		{
			$labels_to_duplicate[$language->sef] = LanguageFactory::getTranslation($element->label, $language->lang_code);
			if (is_null($labels_to_duplicate[$language->sef]))
			{
				$labels_to_duplicate[$language->sef] = '';
			}
		}
		LanguageFactory::translate($labelKey, $labels_to_duplicate, 'fabrik_elements', $element->id, 'label', $this->user->id);
		//

		$element->label  = $labelKey;
		$element->params = json_encode($params);
		$this->flushElement($element);

		return $element;
	}

	public function flushElement(object $element): bool
	{
		if (empty($element->id))
		{
			$element->created_by       = $this->user->id;
			$element->created          = date('Y-m-d H:i:s');
			$element->modified         = null;
			$element->checked_out_time = date('Y-m-d H:i:s');

			$flushed = $this->db->insertObject('#__fabrik_elements', $element);
			if ($flushed)
			{
				$element->id = $this->db->insertid();
			}
		}
		else
		{
			$element->modified_by = $this->user->id;
			$element->modified    = date('Y-m-d H:i:s');

			return $this->db->updateObject('#__fabrik_elements', $element, 'id');
		}

		return $flushed;
	}

	public function updateFabrikLabel(int $identifier, string $keyPrefix, string $keySuffix = '', string $labelPrefix = '', string $referenceTable = 'fabrik_forms', string $referenceField = 'label'): bool
	{
		$updated = false;

		if (!in_array($referenceTable, ['fabrik_forms', 'fabrik_groups', 'fabrik_elements', 'fabrik_lists']))
		{
			throw new \InvalidArgumentException('Reference table ' . $referenceTable . ' not allowed in updateFabrikLabel');
		}

		if (!empty($identifier))
		{
			$keyPrefix = empty($keyPrefix) ? 'FORM_' : $keyPrefix;
			$newKey    = $keyPrefix . $identifier . $keySuffix;

			$query = $this->db->getQuery(true);

			$query->clear()
				->select($referenceField)
				->from('#__' . $referenceTable)
				->where('id = ' . $this->db->quote($identifier));

			$this->db->setQuery($query);
			$oldLabel = $this->db->loadResult();
			$oldLabel = strip_tags($oldLabel);

			$query->clear()
				->update('#__' . $referenceTable)
				->set($referenceField . ' = ' . $this->db->quote($newKey))
				->where('id = ' . $identifier);

			$this->db->setQuery($query);
			$this->db->execute();

			$languages = LanguageHelper::getLanguages();
			$labels    = [];
			foreach ($languages as $language)
			{
				$labels[$language->sef] = $labelPrefix . LanguageFactory::getTranslation($oldLabel, $language->lang_code);
			}
			$key     = LanguageFactory::translate($newKey, $labels, $referenceTable, $identifier, $referenceField, $this->user->id);
			$updated = !empty($key);
		}

		return $updated;
	}

	public function updateCalculationParametersAfterDuplicate(int $oldFormId, object $form): bool
	{
		$updated = false;

		$newFormId = $form->id;
		if (!empty($oldFormId) && !empty($newFormId))
		{
			$calcElements = $this->getElements([
				'plugin'  => ElementPluginEnum::EMUNDUS_CALCULATION->value,
				'form_id' => $newFormId
			]);

			foreach ($calcElements as $element)
			{
				switch ($element->getParamsArray()['type'])
				{
					case 'custom':
						$this->setElementFilters([]);
						$operation = $element->getParamsArray()['operation'];
						if (is_string($operation))
						{
							$operation = json_decode($operation, true);
						}

						foreach ($operation['fields'] as $key => $field)
						{
							if ($field['type'] === ConditionTargetTypeEnum::FORMDATA->value)
							{
								list($fieldFormId, $fieldElementId) = explode('.', $field['field']);

								if (!empty($fieldElementId) && $fieldFormId == $oldFormId)
								{
									$fieldCurrentElement = $this->getElementById($fieldElementId);

									if (!empty($fieldCurrentElement))
									{
										$duplicatedElements = $this->getElements([
											'form_id' => $newFormId,
											'name'    => $fieldCurrentElement->getName()
										], 1);

										if (!empty($duplicatedElements))
										{
											$duplicatedElement                  = $duplicatedElements[0];
											$operation['fields'][$key]['field'] = $newFormId . '.' . $duplicatedElement->getId();

											$params              = $element->getParamsArray();
											$params['operation'] = $operation;
											$element->setParamsRaw(json_encode($params));

											$query = $this->db->createQuery();
											$query->update('#__fabrik_elements')
												->set('params = ' . $this->db->quote(json_encode($params)))
												->where('id = ' . $duplicatedElement->getId());

											try
											{
												$this->db->setQuery($query);
												$updated = $this->db->execute();
											}
											catch (\Exception $e)
											{
												Log::add('Failed to update element ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
											}
										}
									}
								}
							}
						}

						break;
					case CalculateDatesDiff::getCode():
						$this->setElementFilters([]);

						$params = $element->getParamsArray();
						if (is_string($params['dates_diff_form']))
						{
							$params['dates_diff_form'] = json_decode($params['dates_diff_form'], true);
						}

						if (!empty($params['dates_diff_form']['start_date_element']))
						{
							list($fieldFormId, $fieldElementId) = explode('.', $params['dates_diff_form']['start_date_element']);

							$fieldCurrentElement = $this->getElementById($fieldElementId);

							if (!empty($fieldCurrentElement))
							{
								$this->setElementFilters([]);
								$duplicatedElements = $this->getElements([
									'form_id' => $newFormId,
									'name'    => $fieldCurrentElement->getName()
								], 1);

								if (!empty($duplicatedElements))
								{
									$params['dates_diff_form']['start_date_element'] = $newFormId . '.' . $duplicatedElements[0]->getId();
								}
							}
						}

						if (!empty($params['dates_diff_form']['end_date_element']))
						{
							list($fieldFormId, $fieldElementId) = explode('.', $params['dates_diff_form']['end_date_element']);

							$this->setElementFilters([]);
							$fieldCurrentElement = $this->getElementById($fieldElementId);

							if (!empty($fieldCurrentElement))
							{
								$this->setElementFilters([]);
								$duplicatedElements = $this->getElements([
									'form_id' => $newFormId,
									'name'    => $fieldCurrentElement->getName()
								], 1);

								if (!empty($duplicatedElements))
								{
									$params['dates_diff_form']['end_date_element'] = $newFormId . '.' . $duplicatedElements[0]->getId();
								}
							}
						}

						try
						{
							$query = $this->db->createQuery();
							$query->update('#__fabrik_elements')
								->set('params = ' . $this->db->quote(json_encode($params)))
								->where('id = ' . $element->getId());

							$this->db->setQuery($query);
							$updated = $this->db->execute();
						}
						catch (\Exception $e)
						{
							Log::add('Failed to update element ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
						}

						break;
				}
			}
		}

		return $updated;
	}

	public function duplicateConditions(object $oldForm, object $form): bool
	{
		$duplicated = false;

		$query = $this->db->createQuery();

		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_setup_form_rules'))
			->where($this->db->quoteName('form_id') . ' = ' . $this->db->quote($oldForm->id));
		$this->db->setQuery($query);
		$rules = $this->db->loadObjectList();

		foreach ($rules as $rule)
		{
			$insert = [
				'date_time'  => date('Y-m-d H:i:s'),
				'type'       => $rule->type,
				'group'      => $rule->group,
				'published'  => $rule->published,
				'form_id'    => $form->id,
				'created_by' => $rule->created_by,
			];
			$insert = (object) $insert;
			$this->db->insertObject('#__emundus_setup_form_rules', $insert);
			$new_rule_id = $this->db->insertid();

			if (!empty($new_rule_id))
			{
				$duplicated = true;

				$query->clear()
					->select('*')
					->from($this->db->quoteName('#__emundus_setup_form_rules_js_actions'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($rule->id));
				$this->db->setQuery($query);
				$actions = $this->db->loadObjectList();

				foreach ($actions as $action)
				{
					$insert = [
						'parent_id' => $new_rule_id,
						'action'    => $action->action,
					];
					$insert = (object) $insert;
					$this->db->insertObject('#__emundus_setup_form_rules_js_actions', $insert);
					$new_action_id = $this->db->insertid();

					if (!empty($new_action_id))
					{
						$query->clear()
							->select('*')
							->from($this->db->quoteName('#__emundus_setup_form_rules_js_actions_fields'))
							->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($action->id));
						$this->db->setQuery($query);
						$fields = $this->db->loadObjectList();

						foreach ($fields as $field)
						{
							$insert = [
								'parent_id' => $new_action_id,
								'fields'    => $field->fields,
								'params'    => $field->params
							];
							$insert = (object) $insert;
							$this->db->insertObject('#__emundus_setup_form_rules_js_actions_fields', $insert);
						}
					}
				}

				$query->clear()
					->select('*')
					->from($this->db->quoteName('#__emundus_setup_form_rules_js_conditions'))
					->where($this->db->quoteName('parent_id') . ' = ' . $this->db->quote($rule->id));
				$this->db->setQuery($query);
				$conditions = $this->db->loadObjectList();

				$group_conditions = [];
				foreach ($conditions as $condition)
				{
					// Manage jos_emundus_setup_form_rules_js_conditions_group
					$insert = [
						'parent_id' => $new_rule_id,
						'field'     => $condition->field,
						'state'     => $condition->state,
						'values'    => $condition->values
					];
					$insert = (object) $insert;

					if ($this->db->insertObject('#__emundus_setup_form_rules_js_conditions', $insert))
					{
						// Store group condition to duplicate after
						$new_condition_id                      = $this->db->insertid();
						$group_conditions[$condition->group][] = $new_condition_id;
					}
				}

				foreach ($group_conditions as $group => $grouped_conditions)
				{
					$query->clear()
						->select('*')
						->from($this->db->quoteName('#__emundus_setup_form_rules_js_conditions_group'))
						->where($this->db->quoteName('id') . ' = ' . $this->db->quote($group));
					$this->db->setQuery($query);
					$group_info = $this->db->loadObject();

					if (empty($group_info))
					{
						continue;
					}

					$insert = (object) [
						'group_type' => $group_info->group_type,
					];

					if ($this->db->insertObject('#__emundus_setup_form_rules_js_conditions_group', $insert))
					{
						$new_group_id = $this->db->insertid();

						foreach ($grouped_conditions as $grouped_condition)
						{
							$update = (object) [
								'id'    => $grouped_condition,
								'group' => $new_group_id
							];
							$this->db->updateObject('#__emundus_setup_form_rules_js_conditions', $update, 'id');
						}
					}
				}
			}
		}

		return $duplicated;
	}
}