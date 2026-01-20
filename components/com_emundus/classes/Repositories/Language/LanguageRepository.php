<?php
/**
 * @package     Tchooz\Repositories\Language
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Language;

use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Language\LanguageEntity;
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;
use Tchooz\Services\Language\DbLanguage;
use Tchooz\Traits\TraitAutomatedTask;

#[TableAttribute(
	table: '#__emundus_setup_languages',
	alias: 'esl',
	columns: [
		'id',
		'tag',
		'lang_code',
		'override',
		'original_text',
		'type',
		'reference_id',
		'reference_table',
		'reference_field',
		'published',
		'created_by',
		'created_date',
		'modified_by',
		'modified_date',
	]
)]
class LanguageRepository extends EmundusRepository implements RepositoryInterface
{
	use TraitAutomatedTask;

	private LanguageFactory $factory;

	private string $langCode;

	private CacheController $cache;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'language', self::class);

		$this->factory = new LanguageFactory();

		// By default we get the site language
		$this->langCode = Factory::getApplication()->getLanguage()->getTag();

		$this->cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus.translations', 'lifetime' => 86400, 'caching' => true]);
	}

	public function flush(LanguageEntity $language): bool
	{
		try
		{
			if (empty($language->getTag()))
			{
				throw new \InvalidArgumentException('Language tag cannot be empty.');
			}

			if (empty($language->getLangCode()))
			{
				throw new \InvalidArgumentException('Language code cannot be empty.');
			}

			$data = (object) [
				'tag'             => $language->getTag(),
				'lang_code'       => $language->getLangCode(),
				'override'        => $language->getOverride(),
				'override_md5'    => md5($language->getOverride()),
				'type'            => $language->getType(),
				'reference_id'    => $language->getReferenceId(),
				'reference_table' => $language->getReferenceTable(),
				'reference_field' => $language->getReferenceField(),
				'published'       => $language->getPublished()->value,
				'location'        => '',
			];

			if (empty($language->getId()))
			{
				$data->created_by    = $language->getCreatedBy();
				$data->created_date  = $language->getCreatedDate()->format('Y-m-d H:i:s');
				$data->original_text = $language->getOverride();
				$data->original_md5  = md5($language->getOverride());

				if ($executed = $this->db->insertObject($this->tableName, $data))
				{
					$language->setId((int) $this->db->insertid());
				}
			}
			else
			{
				$data->modified_by   = $language->getModifiedBy();
				$data->modified_date = $language->getModifiedDate()?->format('Y-m-d H:i:s');
				$data->id            = $language->getId();


				$executed = $this->db->updateObject($this->tableName, $data, 'id');
			}

			if ($executed && Factory::getApplication()->isClient('site'))
			{
				/*$cache_key = 'emundus_translations_' . LanguageFactory::getShortLangCode($language->getLangCode());
				$results   = $this->getFromCache($cache_key);
				if(!empty($results))
				{
					if (isset($results[$language->getTag()]))
					{
						$results[$language->getTag()]->override = $language->getOverride();
					}
					else {
						$results[$language->getTag()] = $language->toObject();
					}

					$this->cache->unlock($cache_key);
					$this->cache->store($results, $cache_key);
				}*/

				// Clean cache (because update not working as expected)
				$this->cache->clean();

				$languageClass = Factory::getApplication()->getLanguage();
				if($languageClass instanceof DbLanguage)
				{
					$languageClass->addTranslation($language->getTag(), $language->getOverride());
				}
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to flush Language: ' . $e->getMessage(), 0, $e);
		}

		return $executed;
	}

	public function delete(int $id): bool
	{
		try
		{
			$query = $this->db->getQuery(true);

			$query->delete($this->tableName)
				->where($this->db->quoteName('id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to delete Language with ID ' . $id . ': ' . $e->getMessage(), 0, $e);
		}
	}

	public function buildQuery(?string $langCode = ''): QueryInterface
	{
		if(empty($langCode))
		{
			$langCode = $this->getLangCode();
		}

		$query = $this->db->getQuery(true);

		$query->select($this->columns)
			->from($this->db->qn($this->tableName, $this->alias));
		if($langCode !== 'all')
		{
			$query->where($this->db->qn('lang_code') . ' = ' . $this->db->q($langCode));
		}

		return $query;
	}

	public function applyFilters(QueryInterface $query, array $filters): void
	{
		if (in_array('type', array_keys($filters)))
		{
			$query->where($this->db->qn('type') . ' = ' . $this->db->q($filters['type']));
		}

		if (in_array('reference_table', array_keys($filters)))
		{
			$query->where($this->db->qn('reference_table') . ' = ' . $this->db->q($filters['reference_table']));
		}

		if (in_array('reference_id', array_keys($filters)))
		{
			if (is_array($filters['reference_id']))
			{
				$references = implode(',', $this->db->quote($filters['reference_id']));
				$query->where($this->db->qn('reference_id') . ' IN ('.$references.')');
			}
			else
			{
				$query->where($this->db->qn('reference_id') . ' = ' . $filters['reference_id']);
			}
		}

		if (in_array('reference_fields', array_keys($filters)))
		{
			if (is_array($filters['reference_fields']))
			{
				$fields = implode(',', $this->db->quote($filters['reference_fields']));
				$query->where($this->db->qn('reference_field') . ' IN ('.$fields.')');
			}
			else {
				$query->where($this->db->qn('reference_field') . ' LIKE ' . $this->db->q($filters['reference_fields']));
			}
		}
	}

	public function getCount(array $filters = []): int
	{
		$count = 0;

		try
		{
			if(!in_array('lang_code', array_keys($filters)))
			{
				$filters['lang_code'] = $this->getLangCode();
			}

			$query = $this->db->getQuery(true);

			$query->select('COUNT(*)')
				->from($this->db->qn($this->tableName, $this->alias))
				->where($this->db->qn('lang_code') . ' = ' . $this->db->q($filters['lang_code']));
			$this->db->setQuery($query);
			$count = (int) $this->db->loadResult();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to get Language count: ' . $e->getMessage(), 0, $e);
		}

		return $count;
	}

	/**
	 *
	 * @return array
	 *
	 */
	public function getAll(
		array $filters = [],
		bool $cache = true,
		int $limit = 0,
		int $offset = 0,
		bool $loadEntity = true
	): array
	{
		try
		{
			$results = [];

			if(!in_array('lang_code', array_keys($filters)))
			{
				$filters['lang_code'] = $this->getLangCode();
			}

			$cache_key = 'emundus_translations_' . LanguageFactory::getShortLangCode($filters['lang_code']);
			if($cache && Factory::getApplication()->isClient('site'))
			{
				$results   = $this->getFromCache($cache_key);
			}

			if (empty($results))
			{
				$query = $this->buildQuery($filters['lang_code']);
				$this->applyFilters($query, $filters);
				
				$this->db->setQuery($query, $offset, $limit > 0 ? $limit : null);
				$dbObjects = $this->db->loadObjectList();

				if (!empty($dbObjects))
				{
					foreach ($dbObjects as $dbObject)
					{
						if($filters['lang_code'] !== 'all') {
							$results[$dbObject->tag] = $dbObject;
						}
						else {
							$results[] = $dbObject;
						}
					}

					if (Factory::getApplication()->isClient('site') && $cache && $limit === 0 && $offset === 0)
					{
						$this->cache->store($results, $cache_key);
					}
				}
			}
			else
			{
				// Apply filters on cached results
				if (in_array('type', array_keys($filters)))
				{
					$results = array_filter($results, function ($language) use ($filters) {
						return $language->type === $filters['type'];
					});
				}

				if (in_array('reference_table', array_keys($filters)))
				{
					$results = array_filter($results, function ($language) use ($filters) {
						return $language->reference_table === $filters['reference_table'];
					});
				}

				if (in_array('reference_id', array_keys($filters)))
				{
					if (is_array($filters['reference_id']))
					{
						$results = array_filter($results, function ($language) use ($filters) {
							return in_array($language->reference_id, $filters['reference_id']);
						});
					}
					else
					{
						$results = array_filter($results, function ($language) use ($filters) {
							return $language->reference_id === $filters['reference_id'];
						});
					}
				}

				if (in_array('reference_fields', array_keys($filters)))
				{
					if (is_array($filters['reference_fields']))
					{
						$results = array_filter($results, function ($language) use ($filters) {
							return in_array($language->reference_field, $filters['reference_fields']);
						});
					}
					else {
						$results = array_filter($results, function ($language) use ($filters) {
							return stripos($language->reference_field, $filters['reference_fields']) !== false;
						});
					}
				}

				// Apply limit and offset
				if ($limit > 0 || $offset > 0)
				{
					$results = array_slice($results, $offset, $limit > 0 ? $limit : null, true);
				}
			}

			if($loadEntity)
			{
				$results = array_map(function ($dbObject) {
					return $this->factory->fromDbObject($dbObject);
				}, $results);
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to get all Languages: ' . $e->getMessage(), 0, $e);
		}

		return $results;
	}

	public function getById(int $id): ?LanguageEntity
	{
		try
		{
			$query = $this->buildQuery();

			$query->where($this->db->qn('id') . ' = :id')
				->bind(':id', $id, ParameterType::INTEGER);
			$this->db->setQuery($query);
			$dbObject = $this->db->loadObject();

			if (!empty($dbObject))
			{
				return $this->factory->fromDbObject($dbObject);
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to get Language with ID ' . $id . ': ' . $e->getMessage(), 0, $e);
		}

		return null;
	}

	public function getByTag(string $tag): ?LanguageEntity
	{
		try
		{
			$cache_key = 'emundus_translations_' . LanguageFactory::getShortLangCode($this->getLangCode());
			$cached   = $this->getFromCache($cache_key);
			if (!empty($cached) && !empty($cached[$tag]))
			{
				return $this->factory->fromDbObject($cached[$tag]);
			}

			$query = $this->buildQuery();

			$query->where($this->db->qn('tag') . ' = :tag')
				->bind(':tag', $tag);
			$this->db->setQuery($query);
			$dbObject = $this->db->loadObject();

			if (!empty($dbObject))
			{
				if (Factory::getApplication()->isClient('site'))
				{
					$this->cache->clean();
				}

				return $this->factory->fromDbObject($dbObject);
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to get Language with tag ' . $tag . ': ' . $e->getMessage(), 0, $e);
		}

		return null;
	}

	public function getPlatformLanguages(): array
	{
		try
		{
			$cache_key = 'platform_languages';
			$languages = $this->getFromCache($cache_key);
			if (empty($languages))
			{

				$query = $this->db->getQuery(true);

				$query->select($this->db->qn('lang_code'))
					->from($this->db->qn('#__languages'))
					->where($this->db->qn('published') . ' = 1');
				$this->db->setQuery($query);
				$languages = $this->db->loadColumn();

				if (!empty($languages) && Factory::getApplication()->isClient('site'))
				{
					$this->cache->store($languages, $cache_key);
				}
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to get platform languages: ' . $e->getMessage(), 0, $e);
		}

		return $languages;
	}

	public function getDefaultLanguage(): object
	{
		$defaultLangCode = LanguageFactory::getDefaultLanguageCode();

		return $this->getLanguage($defaultLangCode);
	}

	public function getLanguage(string $langCode): ?object
	{
		$query = $this->db->getQuery(true);

		$query->select('lang_code, title_native')
			->from($this->db->qn('#__languages'))
			->where($this->db->qn('lang_code') . ' = :lang_code')
			->bind(':lang_code', $langCode);
		$this->db->setQuery($query);

		return $this->db->loadObject();
	}

	public function getLanguages(): array
	{
		$query = $this->db->getQuery(true);

		$query->select('lang_code, title_native, published')
			->from($this->db->qn('#__languages'));
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}
	
	public function updateContentLanguage(string $lang_code, int $published, ?int $default = 0): bool
	{
		$updated = false;
		$query   = $this->db->getQuery(true);

		try
		{
			if (!empty($default))
			{
				$old_lang        = LanguageFactory::getDefaultLanguageCode();
				$language_params = ComponentHelper::getParams('com_languages');
				$language_params->set('site', $lang_code);

				$query->update($this->db->quoteName('#__extensions'))
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote($language_params->toString()))
					->where($this->db->quoteName('extension_id') . ' = ' . ComponentHelper::getComponent('com_languages')->id);
				$this->db->setQuery($query);

				if ($this->db->execute())
				{
					$query->clear()
						->update($this->db->quoteName('#__languages'))
						->set($this->db->quoteName('published') . ' = ' . $this->db->quote($published))
						->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($lang_code));
					$this->db->setQuery($query);

					if ($this->db->execute())
					{
						$query->clear()
							->update($this->db->quoteName('#__languages'))
							->set($this->db->quoteName('published') . ' = 0')
							->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($old_lang));
						$this->db->setQuery($query);

						$updated = $this->db->execute();
					}
				}
			}
			else
			{
				$query->update($this->db->quoteName('#__languages'))
					->set($this->db->quoteName('published') . ' = ' . $this->db->quote($published))
					->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($lang_code));
				$this->db->setQuery($query);

				$updated = $this->db->execute();
			}
		}
		catch (\Exception $e)
		{
			Log::add('Failed to update content language: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
		}

		return $updated;
	}

	public function updateFalangModule(int $published): bool
	{
		$query = $this->db->getQuery(true);

		try
		{
			$query->update('#__modules')
				->set($this->db->qn('published') . ' = ' . $this->db->q($published))
				->where($this->db->qn('module') . ' = ' . $this->db->q('mod_falang'));
			$this->db->setQuery($query);

			return $this->db->execute();
		}
		catch (\Exception $e)
		{
			Log::add('Failed to update Falang module: ' . $e->getMessage(), Log::ERROR, 'com_emundus');

			return false;
		}
	}

	public function findReferences(string $key): array
	{
		$result = [
			'reference_table' => null,
			'reference_id'    => null,
			'reference_field' => null,
		];

		$query = $this->db->getQuery(true);

		try
		{
			$referenceTables = [
				'fabrik_forms'    => ['label', 'intro'],
				'fabrik_groups'   => ['label', 'params'],
				'fabrik_elements' => ['label', 'params'],
				'fabrik_lists'    => ['label', 'introduction'],
			];

			foreach ($referenceTables as $table => $fields)
			{
				foreach ($fields as $field)
				{
					$find           = null;
					$referenceField = $field;

					if ($field === 'intro' || $field === 'introduction')
					{
						$columns = [$this->db->qn('id'), $this->db->qn($field)];
						$query->clear()
							->select($columns)
							->from($this->db->qn('#__' . $table));
						$this->db->setQuery($query);
						$rows = $this->db->loadObjectList();

						foreach ($rows as $row)
						{
							if (strip_tags($row->intro) == $key)
							{
								$find = $row->id;
								break;
							}
						}

					}
					elseif ($field === 'params')
					{
						$referenceField = 'sub_labels';

						$query->clear()
							->select('id, params')
							->from($this->db->qn('#__' . $table));
						$this->db->setQuery($query);
						$rows = $this->db->loadObjectList();

						foreach ($rows as $row)
						{
							$params = json_decode($row->params, true);
							if (!empty($params['intro']) && strip_tags($params['intro']) == $key)
							{
								$referenceField = 'intro';
								$find           = $row->id;
								break;
							}

							if (!empty($params['sub_options']))
							{
								if (in_array($key, array_values($params['sub_options']['sub_labels'])))
								{
									$find = $row->id;
									break;
								}
							}
						}
					}
					else
					{
						$escapedKey = '%' . $this->db->q($key) . '%';

						$query->clear()
							->select($this->db->qn('id'))
							->from($this->db->qn('#__' . $table))
							->where($this->db->qn($field) . ' LIKE :key_' . $field)
							->bind(':key_' . $field, $escapedKey);
						$this->db->setQuery($query);
						$find = $this->db->loadResult();
					}

					if (!empty($find))
					{
						return [
							'reference_table' => $table,
							'reference_id'    => $find,
							'reference_field' => $referenceField,
						];
					}
				}
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to find references for key ' . $key . ': ' . $e->getMessage(), 0, $e);
		}

		return $result;
	}
	
	public function getOrphans(string $defaultLang, string $langCode, ?string $type = 'override'): array
	{
		$query     = $this->db->getQuery(true);
		$sub_query = $this->db->getQuery(true);

		try
		{
			$sub_query->select('tag')
				->from($this->db->quoteName('#__emundus_setup_languages'))
				->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($langCode))
				->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote($type));

			$query->select('*')
				->from($this->db->quoteName('#__emundus_setup_languages'))
				->where($this->db->quoteName('lang_code') . ' = ' . $this->db->quote($defaultLang))
				->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote($type))
				->andWhere($this->db->quoteName('tag') . ' NOT IN (' . $sub_query->__toString() . ')');
			$this->db->setQuery($query);

			return $this->db->loadObjectList();
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException('Failed to get orphan translations: ' . $e->getMessage(), 0, $e);
		}
	}

	public function getLangCode(): string
	{
		return $this->langCode;
	}

	public function setLangCode(string $langCode): void
	{
		$this->langCode = $langCode;
	}

	private function getFromCache(string $key = ''): array|false
	{
		$caching = [];

		if (Factory::getApplication()->isClient('site'))
		{
			if (empty($key))
			{
				$key = 'emundus_translations_' . $this->getLangCode();
			}

			// First unlock the cache
			$this->cache->unlock($key);
			if ($this->cache->contains($key))
			{
				$caching = $this->cache->get($key);
			}
		}

		return $caching;
	}
}