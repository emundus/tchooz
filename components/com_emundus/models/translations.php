<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2015 emundus.fr. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Component\Languages\Administrator\Model\OverrideModel;

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

class EmundusModelTranslations extends ListModel
{

	function __construct()
	{
		parent::__construct();

		jimport('joomla.log.log');
		Log::addLogger(['text_file' => 'com_emundus.translations.php'], Log::ALL, ['com_emundus.translations']);
	}

	/**
	 * @param   string|null   $type
	 * @param   string|null   $lang_code
	 * @param   string|null   $search
	 * @param   string|null   $location
	 * @param   string|null   $reference_table
	 * @param   int|array     $reference_id
	 * @param   string|array  $reference_fields
	 * @param   string|null   $tag
	 *
	 * @return array
	 *
	 * @depecated Use LanguageRepository->getAll() instead
	 */
	public function getTranslations(?string $type = 'override', ?string $lang_code = '*', ?string $search = '', ?string $location = '', ?string $reference_table = '', int|array $reference_id = 0, string|array $reference_fields = '', ?string $tag = ''): array
	{
		$translations = [];

		try
		{
			$query = $this->_db->getQuery(true);

			$query->select('*')
				->from($this->_db->quoteName('#__emundus_setup_languages'))
				->where($this->_db->quoteName('type') . ' LIKE ' . $this->_db->quote('%' . $type . '%'));

			if ($lang_code !== '*' && !empty($lang_code))
			{
				$query->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang_code));
			}
			if (!empty($search))
			{
				$query->where($this->_db->quoteName('override') . ' LIKE ' . $this->_db->quote('%' . $search . '%'));
			}
			if (!empty($location))
			{
				$query->where($this->_db->quoteName('location') . ' = ' . $this->_db->quote($location));
			}
			if (!empty($reference_table))
			{
				$query->where($this->_db->quoteName('reference_table') . ' LIKE ' . $this->_db->quote($reference_table));
			}
			if (!empty($reference_fields))
			{
				if (is_array($reference_fields))
				{
					$query->where($this->_db->quoteName('reference_field') . ' IN (' . implode(',', $this->_db->quote($reference_fields)) . ')');
				}
				else
				{
					$query->where($this->_db->quoteName('reference_field') . ' LIKE ' . $this->_db->quote($reference_fields));
				}
			}
			if (!empty($reference_id))
			{
				if (is_array($reference_id))
				{
					$query->where($this->_db->quoteName('reference_id') . ' IN (' . implode(',', $this->_db->quote($reference_id)) . ')');
				}
				else
				{
					$query->where($this->_db->quoteName('reference_id') . ' LIKE ' . $this->_db->quote($reference_id));
				}
			}
			if (!empty($tag))
			{
				$query->where($this->_db->quoteName('tag') . ' LIKE ' . $this->_db->quote($tag));
			}
			$this->_db->setQuery($query);
			$translations = $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to get translations with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');
		}

		return $translations;
	}

	public function insertTranslation(string $tag, string $override, string $lang_code, ?string $location = '', ?string $type = 'override', ?string $reference_table = '', ?int $reference_id = 0, ?string $reference_field = '', ?int $user_id = 0): bool
	{
		$inserted = false;

		$app = Factory::getApplication();

		if ($app->isClient('cli'))
		{
			$isCorrect = $this->checkTagIsCorrect($tag, $override, 'insert', $lang_code);

			if (!$isCorrect)
			{
				return false;
			}
		}
		else
		{
			$OverrideModel   = new OverrideModel();
			$LanguagesHelper = new \Joomla\Component\Languages\Administrator\Helper\LanguagesHelper();
			$tag             = $LanguagesHelper::filterKey($tag);
		}

		if (empty($this->getTranslations($type, $lang_code, '', $location, '', 0, '', $tag)))
		{
			$query = $this->_db->getQuery(true);

			if (empty($user_id))
			{
				$user_id = $app->getIdentity()->id;
			}

			try
			{
				if (empty($location))
				{
					$location = $lang_code . '.override.ini';
				}

				$columns = ['tag', 'lang_code', 'override', 'original_text', 'original_md5', 'override_md5', 'location', 'type', 'reference_id', 'reference_table', 'reference_field', 'published', 'created_by', 'created_date', 'modified_by', 'modified_date'];
				$values  = [
					$this->_db->quote($tag),
					$this->_db->quote($lang_code),
					$this->_db->quote($override),
					$this->_db->quote($override),
					$this->_db->quote(md5($override)),
					$this->_db->quote(md5($override)),
					$this->_db->quote($location),
					$this->_db->quote($type),
					$this->_db->quote($reference_id),
					$this->_db->quote($reference_table),
					$this->_db->quote($reference_field),
					1,
					$user_id ?? 0,
					$this->_db->quote(date('Y-m-d H:i:s')),
					$user_id ?? 0,
					$this->_db->quote(date('Y-m-d H:i:s'))
				];

				$query->insert($this->_db->quoteName('#__emundus_setup_languages'))
					->columns($this->_db->quoteName($columns))
					->values(implode(',', $values));
				$this->_db->setQuery($query);

				if ($this->_db->execute())
				{
					if ($app->isClient('cli'))
					{
						$override_file = JPATH_SITE . '/language/overrides/' . $location;
						if (file_exists($override_file))
						{
							$parsed_file       = LanguageHelper::parseIniFile($override_file, true);
							$parsed_file[$tag] = $override;
							$inserted          = LanguageHelper::saveToIniFile($override_file, $parsed_file);
						}
					}
					else
					{
						$app->setUserState('com_languages.overrides.filter.language', $lang_code);

						$data     = [
							'key'      => $tag,
							'override' => $override,
							'id'       => $tag
						];
						$inserted = $OverrideModel->save($data);
					}
				}
				else
				{
					Log::add('Failed to insert translation into database with tag ' . $tag . ' and value ' . $override, Log::ERROR, 'com_emundus.translations');
				}
			}
			catch (Exception $e)
			{
				Log::add('Problem when try to insert translation into file ' . $location . ' with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');
			}
		}

		return $inserted;
	}

	public function updateTranslation(string $tag, string $override, string $lang_code, ?string $type = 'override', ?string $reference_table = '', ?int $reference_id = 0, ?string $reference_field = '', ?int $user_id = 0): bool|string
	{
		$saved = false;

		$app = Factory::getApplication();

		if ($app->isClient('cli'))
		{
			if (!$this->checkTagIsCorrect($tag, $override, 'update', $lang_code))
			{
				return false;
			}

			$tag_already_exists = $this->checkTagExists($tag, $reference_table, $reference_id);
			if (!$tag_already_exists)
			{
				$tag = $this->generateNewTag($tag, $reference_table, $reference_id);
			}
		}
		else
		{
			$OverrideModel   = new OverrideModel();
			$LanguagesHelper = new \Joomla\Component\Languages\Administrator\Helper\LanguagesHelper();
			$tag             = $LanguagesHelper::filterKey($tag);
		}

		$query = $this->_db->getQuery(true);

		if (empty($user_id))
		{
			$user_id = $app->getIdentity()->id;
		}

		$location = $lang_code . '.override.ini';

		try
		{
			if ($type === 'override')
			{
				$query->select('id')
					->from($this->_db->qn('#__emundus_setup_languages'))
					->where($this->_db->quoteName('tag') . ' = ' . $this->_db->quote($tag))
					->andWhere($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang_code))
					->andWhere($this->_db->quoteName('type') . ' = ' . $this->_db->quote($type));
				$this->_db->setQuery($query);
				$id = $this->_db->loadResult();

				if (!empty($id))
				{
					$query->clear()
						->update($this->_db->qn('#__emundus_setup_languages'))
						->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($id));
				}
				else
				{
					$query->clear()
						->insert($this->_db->qn('#__emundus_setup_languages'))
						->set($this->_db->qn('tag') . ' = ' . $this->_db->q($tag))
						->set($this->_db->qn('lang_code') . ' = ' . $this->_db->q($lang_code))
						->set($this->_db->qn('type') . ' = ' . $this->_db->q($type))
						->set($this->_db->qn('original_text') . ' = ' . $this->_db->q($override))
						->set($this->_db->qn('original_md5') . ' = ' . $this->_db->q(md5($override)))
						->set($this->_db->qn('created_by') . ' = ' . $this->_db->q($user_id))
						->set($this->_db->qn('created_date') . ' = ' . $this->_db->q(date('Y-m-d H:i:s')));
				}

				$query->set($this->_db->quoteName('override') . ' = ' . $this->_db->quote($override))
					->set($this->_db->quoteName('override_md5') . ' = ' . $this->_db->quote(md5($override)))
					->set($this->_db->quoteName('modified_by') . ' = ' . $this->_db->quote($user_id))
					->set($this->_db->quoteName('modified_date') . ' = ' . $this->_db->quote(date('Y-m-d H:i:s')))
					->set($this->_db->quoteName('location') . ' = ' . $this->_db->quote($location));

				if (!empty($reference_table))
				{
					$query->set($this->_db->quoteName('reference_table') . ' = ' . $this->_db->quote($reference_table));
				}
				if (!empty($reference_id))
				{
					$query->set($this->_db->quoteName('reference_id') . ' = ' . $this->_db->quote($reference_id));
				}
				if (!empty($reference_field))
				{
					$query->set($this->_db->quoteName('reference_field') . ' = ' . $this->_db->quote($reference_field));
				}

				$this->_db->setQuery($query);

				if ($this->_db->execute())
				{
					if ($app->isClient('cli'))
					{
						$override_file = JPATH_BASE . '/language/overrides/' . $location;
						if (file_exists($override_file))
						{
							$parsed_file       = LanguageHelper::parseIniFile($override_file, true);
							$parsed_file[$tag] = $override;
							$saved          = LanguageHelper::saveToIniFile($override_file, $parsed_file);
						}
					}
					else
					{
						$app->setUserState('com_languages.overrides.filter.language', $lang_code);

						$data  = [
							'key'      => $tag,
							'override' => $override,
							'id'       => $tag
						];
						$saved = $OverrideModel->save($data);
					}

					if ($saved)
					{
						$saved = $tag;
					}
				}
			}
			else
			{
				$existing_translation = $this->getTranslations('override', $lang_code, '', '', '', '', $tag);
				if (empty($existing_translation))
				{
					$saved = $this->insertTranslation($tag, $override, $lang_code);
				}
				else
				{
					$saved = $this->updateTranslation($tag, $override, $lang_code);
				}
			}
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to update translation ' . $tag . ' into file ' . $location . ' with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');

			return false;
		}

		return $saved;
	}

	public function getPlatformLanguages(): array
	{
		$query = $this->_db->getQuery(true);

		$query
			->select($this->_db->quoteName('lang_code'))
			->from($this->_db->quoteName('#__languages'))
			->where($this->_db->quoteName('published') . ' = 1 ');
		$this->_db->setQuery($query);

		try
		{
			return $this->_db->loadColumn();
		}
		catch (Exception $e)
		{
			return [];
		}
	}

	public function getTranslationsFalang(string $default_lang, string $lang_to, int $reference_id, string $fields, string $reference_table): object|bool
	{
		$translations = new stdClass();
		$fields       = explode(',', $fields);

		$query = $this->_db->getQuery(true);

		try
		{
			$query->clear()
				->select('lang_id')
				->from($this->_db->quoteName('#__languages'))
				->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($default_lang));
			$this->_db->setQuery($query);
			$default_lang_id = $this->_db->loadResult();

			$query->clear()
				->select('lang_id')
				->from($this->_db->quoteName('#__languages'))
				->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang_to));
			$this->_db->setQuery($query);
			$lang_to_id = $this->_db->loadResult();

			$translations->{$reference_id} = new stdClass;

			foreach ($fields as $field)
			{
				$labels                  = new stdClass;
				$labels->reference_field = $field;
				$labels->reference_table = $reference_table;
				$labels->reference_id    = $reference_id;

				$query->clear()
					->select('value')
					->from($this->_db->quoteName('#__falang_content'))
					->where($this->_db->quoteName('reference_id') . ' = ' . $this->_db->quote($reference_id))
					->where($this->_db->quoteName('reference_table') . ' = ' . $this->_db->quote($reference_table))
					->where($this->_db->quoteName('language_id') . ' = ' . $this->_db->quote($default_lang_id))
					->where($this->_db->quoteName('reference_field') . ' = ' . $this->_db->quote($field));
				$this->_db->setQuery($query);
				$labels->default_lang = $this->_db->loadResult();

				if (empty($labels->default_lang))
				{
					$query->clear()
						->select('tablepkID')
						->from($this->_db->quoteName('#__falang_tableinfo'))
						->where($this->_db->quoteName('joomlatablename') . ' = ' . $this->_db->quote($reference_table));

					$this->_db->setQuery($query);
					$tablepkID = $this->_db->loadResult();

					$query->clear()
						->select($field)
						->from($this->_db->quoteName('#__' . $reference_table))
						->where($this->_db->quoteName($tablepkID) . ' = ' . $reference_id);
					$this->_db->setQuery($query);
					$labels->default_lang = $this->_db->loadResult();
				}

				$query->clear()
					->select($this->_db->quoteName('value'))
					->from($this->_db->quoteName('#__falang_content'))
					->where($this->_db->quoteName('reference_id') . ' = ' . $this->_db->quote($reference_id))
					->where($this->_db->quoteName('reference_table') . ' = ' . $this->_db->quote($reference_table))
					->where($this->_db->quoteName('language_id') . ' = ' . $this->_db->quote($lang_to_id))
					->where($this->_db->quoteName('reference_field') . ' = ' . $this->_db->quote($field));
				$this->_db->setQuery($query);
				$labels->lang_to                         = $this->_db->loadResult();
				$translations->{$reference_id}->{$field} = $labels;
			}

			return $translations;
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/translations | Error at getting the translations ' . $reference_id . ' references to table ' . $reference_table . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.translations');

			return false;
		}
	}

	public function updateFalangTranslation(string $value, string $lang_to, string $reference_table, int $reference_id, string $field, ?int $user_id = 0): bool
	{
		$updated = false;
		$query   = $this->_db->getQuery(true);

		if (empty($user_id))
		{
			$user_id = Factory::getApplication()->getIdentity()->id;
		}

		try
		{
			$query->select('lang_id')
				->from($this->_db->quoteName('#__languages'))
				->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang_to));
			$this->_db->setQuery($query);
			$lang_to_id = $this->_db->loadResult();

			if (!empty($lang_to_id))
			{
				$query->clear()
					->select('id')
					->from($this->_db->quoteName('#__falang_content'))
					->where($this->_db->quoteName('language_id') . ' = ' . $this->_db->quote($lang_to_id))
					->where($this->_db->quoteName('reference_id') . ' = ' . $this->_db->quote($reference_id))
					->where($this->_db->quoteName('reference_table') . ' = ' . $this->_db->quote($reference_table))
					->where($this->_db->quoteName('reference_field') . ' = ' . $this->_db->quote($field));
				$this->_db->setQuery($query);
				$falang_translation = $this->_db->loadResult();

				if (!empty($falang_translation))
				{
					$query->clear()
						->update($this->_db->quoteName('#__falang_content'))
						->set($this->_db->quoteName('value') . ' = ' . $this->_db->quote($value))
						->set($this->_db->quoteName('modified_by') . ' = ' . $this->_db->quote($user_id))
						->where($this->_db->quoteName('id') . ' = ' . $this->_db->quote($falang_translation));
				}
				else
				{
					$query->clear()
						->insert($this->_db->quoteName('#__falang_content'))
						->set($this->_db->quoteName('language_id') . ' = ' . $this->_db->quote($lang_to_id))
						->set($this->_db->quoteName('reference_id') . ' = ' . $this->_db->quote($reference_id))
						->set($this->_db->quoteName('reference_table') . ' = ' . $this->_db->quote($reference_table))
						->set($this->_db->quoteName('reference_field') . ' = ' . $this->_db->quote($field))
						->set($this->_db->quoteName('value') . ' = ' . $this->_db->quote($value))
						->set($this->_db->quoteName('original_text') . ' = ' . $this->_db->quote($value))
						->set($this->_db->quoteName('modified') . ' = ' . $this->_db->quote(date('Y-m-d H:i:s')))
						->set($this->_db->quoteName('modified_by') . ' = ' . $this->_db->quote($user_id))
						->set($this->_db->quoteName('published') . ' = ' . $this->_db->quote(1));
				}

				$this->_db->setQuery($query);
				$updated = $this->_db->execute();
			}
			else
			{
				Log::add('component/com_emundus/models/translations | Error at getting the language id for ' . $lang_to, Log::ERROR, 'com_emundus.translations');
			}
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/translations | Error at updating the translation ' . $reference_id . ' references to table ' . $reference_table . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.translations');
		}

		return $updated;
	}

	public function checkTagIsCorrect(string $tag, string $override, string $action, string $lang): bool
	{
		$isCorrect = false;

		if (!empty($tag))
		{
			if (!preg_match('/[$^*()=+\\\[<?;]/', $tag, $matches))
			{
				$isCorrect = true;
			}
			else
			{
				Log::add("Problem when try to $action translation into file, tag [$tag] for override [$override] contains forbidden characters ", Log::ERROR, 'com_emundus.translations');
			}
		}
		else
		{
			Log::add("Problem when try to $action translation into file, missing tag for this override $override, $lang", Log::ERROR, 'com_emundus.translations');
		}

		return $isCorrect;
	}

	public function checkTagExists(string $tag, string $reference_table, int $reference_id): bool
	{
		$tagExistsInBdd       = false;
		$tagExistsInOverrides = false;
		$translations         = $this->getTranslations('override', '*', '', '', $reference_table, $reference_id, $tag);

		if (!empty($translations))
		{
			$tagExistsInBdd = true;
		}
		else
		{
			$tagExistsInOverrides = $this->checkTagExistsInOverrideFiles($tag);
		}

		return ($tagExistsInBdd || $tagExistsInOverrides);
	}

	public function checkTagExistsInOverrideFiles(string $tag, ?array $languages = []): bool
	{
		$existsInOverrideFiles = false;
		$languages             = empty($languages) ? $this->getPlatformLanguages() : $languages;

		$files = [];
		foreach ($languages as $language)
		{
			$override_file = JPATH_SITE . '/language/overrides/' . $language . '.override.ini';
			if (file_exists($override_file))
			{
				$files[] = $override_file;
			}
		}

		foreach ($files as $file)
		{
			$parsed_file = JLanguageHelper::parseIniFile($file);

			if (!empty($parsed_file))
			{
				if (in_array($tag, array_keys($parsed_file)))
				{
					$existsInOverrideFiles = true;
					break;
				}
			}
		}

		return $existsInOverrideFiles;
	}

	// TODO: Move to Object to have a tag generation based on some paramters
	public function generateNewTag(string $tag, string $reference_table, int $reference_id): string
	{
		if (!empty($reference_table) && !empty($reference_id))
		{
			$query = $this->_db->getQuery(true);

			switch ($reference_table)
			{
				case 'fabrik_elements':
					$element_id = $reference_id;
					$group_id   = 0;

					$query->select('group_id')
						->from('#__fabrik_elements')
						->where('id = ' . $element_id);

					$this->_db->setQuery($query);

					try
					{
						$group_id = $this->_db->loadResult();
					}
					catch (Exception $e)
					{
						Log::add("Error trying to find group_id from element_id $element_id " . preg_replace("/[\r\n]/", " ", $e->getMessage()), Log::ERROR, 'com_emundus.translations');
					}

					$tag = 'ELEMENT_' . $group_id . '_' . $element_id;
					break;
				case 'fabrik_forms':
					$tag = "FORM_$reference_id";
					break;
				case 'fabrik_groups':
					$group_id = $reference_id;
					$form_id  = 0;

					$query->select('form_id')
						->from('#__fabrik_formgroup')
						->where('group_id = ' . $reference_id);

					$this->_db->setQuery($query);

					try
					{
						$form_id = $this->_db->loadResult();
					}
					catch (Exception $e)
					{
						Log::add("Error trying to find form_id from group_id $group_id " . preg_replace("/[\r\n]/", " ", $e->getMessage()), Log::ERROR, 'com_emundus.translations');
					}

					$tag = 'GROUP_' . $form_id . '_' . $group_id;
					break;
				default:
					Log::add("Impossible to generate a new tag. $tag has no TAG in setup_languages nor in override files, but reference_id is empty.", Log::INFO, 'com_emundus.translations');
					break;
			}

			$index   = 0;
			$tmp_tag = $tag;
			while ($this->checkTagExistsInOverrideFiles($tmp_tag))
			{
				$tmp_tag = $tag . '_' . $index;
			}
			$tag = $tmp_tag;
		}

		return $tag;
	}
}
