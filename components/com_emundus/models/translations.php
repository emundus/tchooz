<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2015 emundus.fr. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Uri\Uri;
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

	public function checkSetup(): bool|int
	{
		try
		{
			$query = $this->_db->getQuery(true);

			$query->select('count(id)')
				->from($this->_db->quoteName('#__emundus_setup_languages'));
			$this->_db->setQuery($query);

			return $this->_db->loadResult();
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to get setup translation tool with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');

			return false;
		}
	}

	public function configureSetup(): bool
	{
		$query = $this->_db->getQuery(true);

		try
		{
			$query
				->select('DISTINCT(element), CONCAT(type, "s") AS type')
				->from($this->_db->quoteName('#__extensions'))
				->where($this->_db->quoteName('element') . ' LIKE ' . $this->_db->quote('%emundus%'));

			$this->_db->setQuery($query);

			$extensions = $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Error getting extensions with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');

			return false;
		}

		// Components, modules, extensions files
		$files = [];
		foreach ($this->getPlatformLanguages() as $language)
		{
			// Overrides
			$override_file = JPATH_SITE . '/language/overrides/' . $language . '.override.ini';
			if (file_exists($override_file))
			{
				$files[] = $override_file;
			}
			//
		}
		//

		$db_columns = [
			$this->_db->quoteName('tag'),
			$this->_db->quoteName('lang_code'),
			$this->_db->quoteName('override'),
			$this->_db->quoteName('original_text'),
			$this->_db->quoteName('original_md5'),
			$this->_db->quoteName('override_md5'),
			$this->_db->quoteName('location'),
			$this->_db->quoteName('type'),
			$this->_db->quoteName('created_by'),
			$this->_db->quoteName('reference_id'),
			$this->_db->quoteName('reference_table'),
			$this->_db->quoteName('reference_field'),
		];

		foreach ($files as $file)
		{
			$parsed_file = LanguageHelper::parseIniFile($file);

			$file      = explode('/', $file);
			$file_name = end($file);
			$language  = strtok($file_name, '.');

			$key_added = [];

			foreach ($parsed_file as $key => $val)
			{
				if (!in_array(strtoupper($key), $key_added))
				{
					$query->clear()
						->select('count(id)')
						->from($this->_db->quoteName('jos_emundus_setup_languages'))
						->where($this->_db->quoteName('tag') . ' = ' . $this->_db->quote($key))
						->andWhere($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($language))
						->andWhere($this->_db->quoteName('location') . ' = ' . $this->_db->quote($file_name));
					$this->_db->setQuery($query);

					if ($this->_db->loadResult() == 0)
					{
						if (strpos($file_name, 'override') !== false)
						{
							// Search if value is use in fabrik
							$reference_table = null;
							$reference_id    = null;
							$reference_field = null;

							$query->clear()
								->select('id')
								->from($this->_db->quoteName('#__fabrik_forms'))
								->where($this->_db->quoteName('label') . ' LIKE ' . $this->_db->quote($key));
							$this->_db->setQuery($query);
							$find = $this->_db->loadResult();

							if (!empty($find))
							{
								$reference_table = 'fabrik_forms';
								$reference_id    = $find;
								$reference_field = 'label';
							}
							else
							{
								$query->clear()
									->select('id,intro')
									->from($this->_db->quoteName('#__fabrik_forms'));
								$this->_db->setQuery($query);
								$forms_intro = $this->_db->loadObjectList();

								foreach ($forms_intro as $intro)
								{
									if (strip_tags($intro->intro) == $key)
									{
										$find = $intro->id;
										break;
									}
								}

								if (!empty($find))
								{
									$reference_table = 'fabrik_forms';
									$reference_id    = $find;
									$reference_field = 'intro';
								}
								else
								{
									$query->clear()
										->select('id')
										->from($this->_db->quoteName('#__fabrik_groups'))
										->where($this->_db->quoteName('label') . ' LIKE ' . $this->_db->quote($key));
									$this->_db->setQuery($query);
									$find = $this->_db->loadResult();

									if (!empty($find))
									{
										$reference_table = 'fabrik_groups';
										$reference_id    = $find;
										$reference_field = 'label';
									}
									else
									{
										$query->clear()
											->select('id,params')
											->from($this->_db->quoteName('#__fabrik_groups'));
										$this->_db->setQuery($query);
										$groups_params = $this->_db->loadObjectList();

										foreach ($groups_params as $group_params)
										{
											$params = json_decode($group_params->params);
											if (strip_tags($params->intro) == $key)
											{
												$find = $group_params->id;
												break;
											}
										}

										if (!empty($find))
										{
											$reference_table = 'fabrik_groups';
											$reference_id    = $find;
											$reference_field = 'intro';
										}
										else
										{
											$query->clear()
												->select('id')
												->from($this->_db->quoteName('#__fabrik_elements'))
												->where($this->_db->quoteName('label') . ' LIKE ' . $this->_db->quote($key));
											$this->_db->setQuery($query);
											$find = $this->_db->loadResult();

											if (!empty($find))
											{
												$reference_table = 'fabrik_elements';
												$reference_id    = $find;
												$reference_field = 'label';
											}
											else
											{
												$query->clear()
													->select('id,params')
													->from($this->_db->quoteName('#__fabrik_elements'))
													->where($this->_db->quoteName('plugin') . ' = ' . $this->_db->quote('dropdown'));
												$this->_db->setQuery($query);
												$elements_params = $this->_db->loadObjectList();

												foreach ($elements_params as $element_params)
												{
													$params      = json_decode($element_params->params);
													$sub_options = $params->sub_options;
													if (in_array($key, array_values($sub_options->sub_labels)))
													{
														$find = $element_params->id;
														break;
													}
												}

												if (!empty($find))
												{
													$reference_table = 'fabrik_elements';
													$reference_id    = $find;
													$reference_field = 'sub_labels';
												}
											}
										}
									}
								}
							}
							//
							$row = [$this->_db->quote($key), $this->_db->quote($language), $this->_db->quote($val), $this->_db->quote($val), $this->_db->quote(md5($val)), $this->_db->quote(md5($val)), $this->_db->quote($file_name), $this->_db->quote('override'), 62, $this->_db->quote($reference_id), $this->_db->quote($reference_table), $this->_db->quote($reference_field)];
						}
						else
						{
							$row = [$this->_db->quote($key), $this->_db->quote($language), $this->_db->quote($val), $this->_db->quote($val), $this->_db->quote(md5($val)), $this->_db->quote(md5($val)), $this->_db->quote($file_name), $this->_db->quote(null), 62, $this->_db->quote(null), $this->_db->quote(null), $this->_db->quote(null)];
						}
						try
						{
							$query
								->clear()
								->insert($this->_db->quoteName('jos_emundus_setup_languages'))
								->columns($db_columns)
								->values(implode(',', $row));

							$this->_db->setQuery($query);
							$this->_db->execute();
						}
						catch (Exception $e)
						{
							Log::add('Problem when insert translations at first launch with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');

							return false;
						}
						$key_added[] = strtoupper($key);
					}
				}
			}
		}

		return true;
	}

	public function getTranslationsObject(): array
	{
		$objects = array();


		// todo: put the result in cache
		include_once(JPATH_ROOT . '/administrator/components/com_falang/models/ContentElement.php');

		jimport('joomla.filesystem.folder');
		$dir        = JPATH_ROOT . '/components/com_emundus/contentelements/';
		$filesindir = Joomla\Filesystem\Folder::files($dir, '.xml');
		if (count($filesindir) > 0)
		{
			foreach ($filesindir as $file)
			{
				$object = new stdClass;
				unset($xmlDoc);
				$xmlDoc = new DOMDocument();
				if ($xmlDoc->load($dir . $file))
				{
					$xpath        = new DOMXPath($xmlDoc);
					$tableElement = $xpath->query('//reference/table')->item(0);

					$object->name                    = JText::_($xmlDoc->getElementsByTagName('name')->item(0)->textContent);
					$object->description             = JText::_($xmlDoc->getElementsByTagName('description')->item(0)->textContent);
					$object->table                   = new stdClass;
					$object->table->name             = trim($tableElement->getAttribute('name'));
					$object->table->reference        = trim($tableElement->getAttribute('reference'));
					$object->table->label            = trim($tableElement->getAttribute('label'));
					$object->table->filters          = trim($tableElement->getAttribute('filters'));
					$object->table->load_all         = trim($tableElement->getAttribute('load_all'));
					$object->table->type             = trim($tableElement->getAttribute('type'));
					$object->table->load_first_data  = trim($tableElement->getAttribute('load_first_data'));
					$object->table->load_first_child = trim($tableElement->getAttribute('load_first_child'));

					$tableFields   = $tableElement->getElementsByTagName('field');
					$tableSections = $tableElement->getElementsByTagName('section');

					$fields          = array();
					$indexedSections = array();

					foreach ($tableFields as $tableField)
					{
						if (trim($tableField->getAttribute('type')) == 'children')
						{
							$field          = new stdClass;
							$field->Type    = trim($tableField->getAttribute('type'));
							$field->Name    = trim($tableField->getAttribute('name'));
							$field->Label   = trim($tableField->textContent);
							$field->Table   = trim($tableField->getAttribute('table'));
							$field->Options = trim($tableField->getAttribute('options'));

							$fields[] = $field;
						}
					}

					foreach ($tableSections as $tableSection)
					{
						$section                  = new stdClass;
						$section->Label           = trim($tableSection->getAttribute('label'));
						$section->Name            = trim($tableSection->getAttribute('name'));
						$section->Table           = trim($tableSection->getAttribute('table'));
						$section->TableJoin       = trim($tableSection->getAttribute('join_table'));
						$section->TableJoinColumn = trim($tableSection->getAttribute('join_column'));
						$section->ReferenceColumn = trim($tableSection->getAttribute('reference_column'));
						$section->indexedFields   = array();

						foreach ($tableFields as $tableField)
						{
							if (trim($tableField->getAttribute('section')) == $section->Name)
							{
								$field          = new stdClass;
								$field->Type    = trim($tableField->getAttribute('type'));
								$field->Name    = trim($tableField->getAttribute('name'));
								$field->Label   = trim($tableField->textContent);
								$field->Table   = trim($tableField->getAttribute('table'));
								$field->Options = trim($tableField->getAttribute('options'));

								$fields[]                             = $field;
								$section->indexedFields[$field->Name] = $field;
							}
						}
						$indexedSections[] = $section;

					}
					$object->fields           = new stdClass;
					$object->fields->Fields   = $fields;
					$object->fields->Sections = $indexedSections;

					$objects[] = $object;
				}
			}
		}

		return $objects;
	}

	public function getDatas(string $table, string $reference_id, string $label, string $filters): array
	{
		$datas = array();

		$query = $this->_db->getQuery(true);

		// todo: maybe use translations objects
		$allowed_tables = [
			'emundus_setup_attachments',
			'emundus_setup_profiles',
			'emundus_setup_campaigns',
			'emundus_setup_status',
			'emundus_setup_action_tag',
			'fabrik_lists'
		];

		if (!in_array($table, $allowed_tables))
		{
			throw new Exception(Text::_('ACCESS_DENIED'));
		}

		try
		{
			$query->select($this->_db->quoteName($reference_id) . 'as id,' . $this->_db->quoteName($label) . 'as label')
				->from($this->_db->quoteName('#__' . $table));
			if (!empty($filters))
			{
				$filters = explode(',', $filters);
				foreach ($filters as $filter)
				{
					if ($filter === 'evaluations') { // todo: this is not generic
						$query->where($this->_db->quoteName('db_table_name') . ' LIKE ' . $this->_db->quote('jos_emundus_evaluations_%'));
					} else
					{
						$query->where($this->_db->quoteName($filter) . ' = 1');
					}
				}
			}
			$this->_db->setQuery($query);

			$datas = $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to get datas from table ' . $table . ' with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');
		}

		return $datas;
	}

	public function getChildrens(string $table, int $reference_id, string $label, string $parent_table = '')
	{
		$childrens = array();

		$query = $this->_db->getQuery(true);

		if ($table == 'fabrik_forms')
		{
			if ($parent_table !== 'fabrik_lists') { // todo: this is not generic
				$forms = array();
				require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'menu.php');
				$h_menu = new EmundusHelperMenu;

				$tableuser = $h_menu->buildMenuQuery($reference_id);
				foreach ($tableuser as $menu)
				{
					$forms[] = $menu->form_id;
				}
			} else {
				$forms = array($reference_id);
			}
		}

		try
		{
			$query->select('id,' . $this->_db->quoteName($label) . ' as label')
				->from($this->_db->quoteName('#__' . $table));

			if (isset($forms))
			{
				$query->where($this->_db->quoteName('id') . ' IN (' . implode(',', $forms) . ')');
				$query->order('field(id,' . implode(',', $forms) . ') ASC');
			}
			$this->_db->setQuery($query);
			$childrens = $this->_db->loadObjectList();

			foreach ($childrens as $children)
			{
				$children->label = Text::_($children->label);
			}
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to get childrens from table ' . $table . ' with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');
		}

		return $childrens;
	}

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

	public function deleteTranslation(?string $tag = '', ?string $lang_code = '*', ?string $reference_table = '', ?int $reference_id = 0): bool
	{
		$query = $this->_db->getQuery(true);

		try
		{
			$query->delete($this->_db->quoteName('#__emundus_setup_languages'))
				->where($this->_db->quoteName('type') . ' = ' . $this->_db->quote('override'));

			if (!empty($tag))
			{
				$query->where($this->_db->quoteName('tag') . ' = ' . $this->_db->quote($tag));
			}
			if ($lang_code !== '*' && !empty($lang_code))
			{
				$query->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang_code));
			}
			if (!empty($reference_table))
			{
				$query->where($this->_db->quoteName('reference_table') . ' LIKE ' . $this->_db->quote($reference_table));
			}
			if (!empty($reference_id))
			{
				$query->where($this->_db->quoteName('reference_id') . ' LIKE ' . $this->_db->quote($reference_id));
			}
			$this->_db->setQuery($query);
			$this->_db->execute();

			if ($lang_code == '*')
			{
				$languages = LanguageHelper::getLanguages();

				foreach ($languages as $language)
				{
					$location      = $language->lang_code . '.override.ini';
					$override_file = JPATH_SITE . '/language/overrides/' . $location;
					if (file_exists($override_file))
					{
						$parsed_file = LanguageHelper::parseIniFile($override_file, true);
						unset($parsed_file[$tag]);
						LanguageHelper::saveToIniFile($override_file, $parsed_file);
					}
				}
			}
			else
			{
				$location      = $lang_code . '.override.ini';
				$override_file = JPATH_SITE . '/language/overrides/' . $location;
				if (file_exists($override_file))
				{
					$parsed_file = LanguageHelper::parseIniFile($override_file, true);
					unset($parsed_file[$tag]);
					LanguageHelper::saveToIniFile($override_file, $parsed_file);
				}
			}

			return true;
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to delete translation ' . $tag . ' with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');

			return false;
		}
	}

	public function getDefaultLanguage(): object|bool
	{
		$lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');

		$query = $this->_db->getQuery(true);

		try
		{
			$query->select('lang_code,title_native')
				->from($this->_db->quoteName('#__languages'))
				->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang));
			$this->_db->setQuery($query);

			return $this->_db->loadObject();
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to fet default language with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');

			return false;
		}
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

	public function getAllLanguages(): array
	{
		$languages = array();

		$query = $this->_db->getQuery(true);

		try
		{
			$query->select('lang_code,title_native,published')
				->from($this->_db->quoteName('#__languages'));
			$this->_db->setQuery($query);

			$languages = $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to fet default language with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');
		}

		return $languages;
	}

	public function updateLanguage(string $lang_code, int $published, $default): bool
	{
		$updated = false;
		$query   = $this->_db->getQuery(true);

		try
		{
			if (!empty($default))
			{
				$old_lang        = $this->getDefaultLanguage();
				$language_params = ComponentHelper::getParams('com_languages');
				$language_params->set('site', $lang_code);

				// Update the default language
				$query->update($this->_db->quoteName('#__extensions'))
					->set($this->_db->quoteName('params') . ' = ' . $this->_db->quote($language_params->toString()))
					->where($this->_db->quoteName('extension_id') . ' = ' . ComponentHelper::getComponent('com_languages')->id);
				$this->_db->setQuery($query);

				if ($this->_db->execute())
				{
					$query->clear()
						->update($this->_db->quoteName('#__languages'))
						->set($this->_db->quoteName('published') . ' = ' . $this->_db->quote($published))
						->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang_code));
					$this->_db->setQuery($query);

					if ($this->_db->execute())
					{
						$query->clear()
							->update($this->_db->quoteName('#__languages'))
							->set($this->_db->quoteName('published') . ' = 0')
							->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($old_lang->lang_code));
						$this->_db->setQuery($query);

						$updated = $this->_db->execute();
					}
				}
			}
			else
			{
				$query->update($this->_db->quoteName('#__languages'))
					->set($this->_db->quoteName('published') . ' = ' . $this->_db->quote($published))
					->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang_code));
				$this->_db->setQuery($query);

				$updated = $this->_db->execute();
			}
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to update language ' . $lang_code . ' with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');
		}

		return $updated;
	}

	public function updateFalangModule(int $published): bool
	{
		$query = $this->_db->getQuery(true);

		try
		{
			$query->update('#__modules')
				->set($this->_db->quoteName('published') . ' = ' . $this->_db->quote($published))
				->where($this->_db->quoteName('module') . ' = ' . $this->_db->quote('mod_falang'));
			$this->_db->setQuery($query);

			return $this->_db->execute();
		}
		catch (Exception $e)
		{
			Log::add('Problem when try to unpublish falang module with error : ' . $e->getMessage(), Log::ERROR, 'com_emundus.translations');

			return false;
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

	public function getJoinReferenceId(string $reference_table, string $reference_column, string $join_table, string $join_column, int|array $reference_id): array|bool
	{
		$query = $this->_db->getQuery(true);

		try
		{
			$query->select('rt.id')
				->from($this->_db->quoteName('#__' . $reference_table, 'rt'))
				->leftJoin($this->_db->quoteName('#__' . $join_table, 'jt') . ' ON ' . $this->_db->quoteName('rt.id') . ' = ' . $this->_db->quoteName('jt.' . $reference_column));
			if (is_array($reference_id))
			{
				$query->where($this->_db->quoteName('jt.' . $join_column) . ' IN (' . implode(',', $this->_db->quote($reference_id)) . ')');
			}
			else
			{
				$query->where($this->_db->quoteName('jt.' . $join_column) . ' = ' . $this->_db->quote($reference_id));
			}

			if ($reference_table == 'fabrik_groups')
			{
				$query->where('JSON_EXTRACT(rt.params,"$.repeat_group_show_first")' . ' = ' . $this->_db->quote(1))
					->where($this->_db->quoteName('rt.published') . ' = 1');
			}

			if ($reference_table == 'fabrik_elements')
			{
				$query->where($this->_db->quoteName('rt.hidden') . ' <> 1')
					->where($this->_db->quoteName('rt.published') . ' = 1');
			}

			$this->_db->setQuery($query);

			return $this->_db->loadColumn();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/translations | Error at getting the reference id by join with id ' . $reference_id . ' references to table ' . $join_table . ' : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.translations');

			return false;
		}
	}

	public function getOrphelins(string $default_lang, string $lang_code, ?string $type = 'override'): array|bool
	{
		$query     = $this->_db->getQuery(true);
		$sub_query = $this->_db->getQuery(true);

		try
		{
			$sub_query->select('tag')
				->from($this->_db->quoteName('#__emundus_setup_languages'))
				->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($lang_code))
				->andWhere($this->_db->quoteName('type') . ' = ' . $this->_db->quote($type));

			$query->select('*')
				->from($this->_db->quoteName('#__emundus_setup_languages'))
				->where($this->_db->quoteName('lang_code') . ' = ' . $this->_db->quote($default_lang))
				->andWhere($this->_db->quoteName('type') . ' = ' . $this->_db->quote($type))
				->andWhere($this->_db->quoteName('tag') . ' NOT IN (' . $sub_query->__toString() . ')');
			$this->_db->setQuery($query);

			return $this->_db->loadObjectList();
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/translations | Error at getting orphelins : ' . preg_replace("/[\r\n]/", " ", $query->__toString() . ' -> ' . $e->getMessage()), Log::ERROR, 'com_emundus.translations');

			return false;
		}
	}

	public function sendPurposeNewLanguage($language, $comment): bool
	{
		try
		{
			include_once(JPATH_SITE . '/components/com_emundus/helpers/emails.php');
			include_once(JPATH_SITE . '/components/com_emundus/controllers/messages.php');
			$c_messages = new EmundusControllerMessages();

			$template = Factory::getApplication()->getTemplate(true);
			$config   = Factory::getApplication()->getConfig();

			// Get LOGO
			$logo = EmundusHelperEmails::getLogo();

			$post = [
				'SITE_NAME'      => $config->get('sitename'),
				'SITE_URL'       => Uri::base(),
				'LANGUAGE_FIELD' => $language,
				'LOGO'           => $logo
			];

			return $c_messages->sendEmailNoFnum('support@emundus.fr', 'installation_new_language', $post);
		}
		catch (Exception $e)
		{
			Log::add('component/com_emundus/models/translations | Error at sending email to purpose a new language : ' . preg_replace("/[\r\n]/", " ", $e->getMessage()), Log::ERROR, 'com_emundus.translations');

			return false;
		}
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

	public function updateElementLabel(string $tag, string $reference_table, int $reference_id): bool
	{
		$updated = false;

		if (!empty($tag) && !empty($reference_table) && !empty($reference_id))
		{
			$query = $this->_db->getQuery(true);

			switch ($reference_table)
			{
				case 'fabrik_elements':
					$query->update('#__fabrik_elements')
						->set('label = ' . $this->_db->quote($tag))
						->where('id = ' . $reference_id);
					break;
				case 'fabrik_forms':
					$query->update('#__fabrik_forms')
						->set('label = ' . $this->_db->quote($tag))
						->where('id = ' . $reference_id);
					break;
				case 'fabrik_groups':
					$query->update('#__fabrik_groups')
						->set('label = ' . $this->_db->quote($tag))
						->where('id = ' . $reference_id);
					break;
			}

			$this->_db->setQuery($query);
			try
			{
				$updated = $this->_db->execute();
			}
			catch (Exception $e)
			{
				Log::add("Error trying to update label for $reference_table, $reference_id, $tag " . $e->getMessage(), Log::ERROR, 'com_emundus.translations');
			}
		}

		return $updated;
	}
}
