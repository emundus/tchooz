<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Tchooz\Enums\Fabrik\ElementPluginEnum;

class EmundusFilters
{
	protected $user = null;
	protected $default_element = 0;
	protected $filters = [];
	protected $applied_filters = [];
	protected $quick_search_filters = [];

	protected $h_cache = null;

	public function __construct($config = array())
	{
		Log::addLogger(['text_file' => 'com_emundus.filters.php'], Log::ALL, 'com_emundus.filters');

		$app        = Factory::getApplication();
		$this->user = $app->getIdentity();

		if (!EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			throw new Exception('Access denied', 403);
		}

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
		$this->h_cache = new EmundusHelperCache();

		if (!empty($config['element_id'])) {
			$this->default_element = $config['element_id'];
			$this->setFilters();
		}

		$session_filters = $app->getSession()->get('em-applied-filters', null);
		if (!empty($session_filters)) {
			$this->setAppliedFilters($session_filters);
		}
		$quick_search_filters = $app->getSession()->get('em-quick-search-filters', null);
		if (!empty($quick_search_filters)) {
			$this->setQuickSearchFilters($quick_search_filters);
		}
	}

	protected function getDefaultElement()
	{
		return $this->default_element;
	}

	protected function setFilters(): void
	{
		$element = $this->getDefaultElement();

		if (!empty($element)) {
			$elements = $this->getAllAssociatedElements($element);
			$this->filters = $this->createFiltersFromFabrikElements($elements);
		}
	}


	protected function createFiltersFromFabrikElements($elements): array
	{
		$created_filters = [];

		if (!empty($elements)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$fieldCalcTypes = $this->resolveFieldCalcElementTypes($elements, $db);

			foreach($elements as $element) {
                $label = strip_tags(Text::_($element['label']));
				$label = !empty($label) ? $label : 'ELEMENT ' .  $element['id'];


				if (!empty($element['element_form_label']) || !empty($element['element_group_label'])) {
					$label .= ' (';
				}

				if (!empty($element['element_form_label'])) {
					$label .= strip_tags(Text::_($element['element_form_label']));
				}

				if (!empty($element['element_group_label'])) {
					if (!empty($element['element_form_label'])) {
						$label .= ' > ';
					}

					$label .= strip_tags(Text::_($element['element_group_label']));
				}

				if (!empty($element['element_form_label']) || !empty($element['element_group_label'])) {
					$label .= ')';
				}

				$filter = [
					'id' => $element['id'],
					'label' => $label,
					'type' => 'text',
					'values' => [],
					'form_label' => $element['element_form_label'],
					'form_id' => $element['element_form_id'],
					'group_id' => $element['element_group_id'] ?? 0,
					'group_label' => $element['element_group_label'] ?? '',
					'available' => true,
                    'plugin' => $element['plugin'],
					'operator' => '='
                ];

				switch ($element['plugin']) {
					case 'dropdown':
					case 'checkbox':
					case 'radiobutton':
					case 'databasejoin':
                    case 'cascadingdropdown':
						$filter['type'] = 'select';
						$filter['values'] = [];
						$filter['operator'] = 'IN';
						break;
					case ElementPluginEnum::ORDERLIST->value:
						$filter['type'] = ElementPluginEnum::ORDERLIST->value;
						$filter['values'] = [];
						$filter['value'] = [];
						break;
					case 'yesno':
						$filter['type'] = 'select';
						$filter['values'] = [
							['value' => 1, 'label' => Text::_('JYES')],
							['value' => 0, 'label' => Text::_('JNO')]
						];
						break;
					case 'date':
					case 'jdate':
					case 'birthday':
						$filter['type'] = 'date';
						$filter['value'] = ['', ''];
						break;
					case 'time':
						$filter['type'] = 'time';
						$filter['value'] = ['', ''];
						break;
					case 'field':
					case 'calc':
						// Type (number vs text) is resolved in one batch pass before the loop — see
						// resolveFieldCalcElementTypes() — instead of 2 queries per element. Elements whose
						// table/column cannot be resolved keep the defaults (text, '='), as before.
						if (array_key_exists($element['id'], $fieldCalcTypes)) {
							$filter['type'] = $fieldCalcTypes[$element['id']];
							if ($fieldCalcTypes[$element['id']] !== 'number') {
								$filter['operator'] = 'LIKE';
							}
						}

						break;
					case 'average':
					case 'numeric':
						$filter['type'] = 'number';
						break;
					default:
						$filter['operator'] = 'LIKE';
				}

				$created_filters[] = $filter;
			}
		}

		return $created_filters;
	}

	/**
	 * Resolves the filter type (number|text) for every field/calc element in TWO queries total,
	 * instead of two queries per element. Only elements whose table/column resolve are returned,
	 * so unresolved ones keep their caller defaults. Reuses the persistent cache when enabled.
	 *
	 * @param array $elements
	 * @param       $db
	 *
	 * @return array<int,string> element id => 'number' | 'text'
	 */
	protected function resolveFieldCalcElementTypes(array $elements, $db): array
	{
		$types = [];

		$ids = [];
		foreach ($elements as $element) {
			if (in_array($element['plugin'], ['field', 'calc'], true) && !empty($element['id'])) {
				$ids[] = (int) $element['id'];
			}
		}
		$ids = array_values(array_unique($ids));
		if (empty($ids)) {
			return $types;
		}

		$cached = $this->h_cache->isEnabled() ? $this->h_cache->get('em-filters-elements-type') : [];
		$cached = is_array($cached) ? $cached : [];

		$to_resolve = [];
		foreach ($ids as $id) {
			if (!empty($cached[$id])) {
				$types[$id] = $cached[$id];
			} else {
				$to_resolve[] = $id;
			}
		}
		if (empty($to_resolve)) {
			return $types;
		}

		// One query: element id => (table, column).
		$query = $db->getQuery(true);
		$query->select('jfe.id, jfl.db_table_name, jfe.name')
			->from('jos_fabrik_elements as jfe')
			->leftJoin('jos_fabrik_formgroup as jffg ON jffg.group_id = jfe.group_id')
			->leftJoin('jos_fabrik_lists as jfl ON jfl.form_id = jffg.form_id')
			->where('jfe.id IN (' . implode(',', $to_resolve) . ')');

		$element_columns = [];
		$tables          = [];
		try {
			$db->setQuery($query);
			foreach ($db->loadAssocList() ?: [] as $row) {
				if (!empty($row['db_table_name']) && !empty($row['name'])) {
					$element_columns[(int) $row['id']] = [$row['db_table_name'], $row['name']];
					$tables[$row['db_table_name']]     = true;
				}
			}
		} catch (Exception $e) {
			Log::add('Failed to batch resolve fabrik element tables: ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
		}

		// One query: column data types across all involved tables.
		$column_types = [];
		if (!empty($tables)) {
			$query->clear()
				->select('TABLE_NAME, COLUMN_NAME, DATA_TYPE')
				->from('INFORMATION_SCHEMA.COLUMNS')
				->where('TABLE_SCHEMA = DATABASE()')
				->where('TABLE_NAME IN (' . implode(',', array_map([$db, 'quote'], array_keys($tables))) . ')');

			try {
				$db->setQuery($query);
				foreach ($db->loadAssocList() ?: [] as $row) {
					$column_types[$row['TABLE_NAME'] . '.' . $row['COLUMN_NAME']] = $row['DATA_TYPE'];
				}
			} catch (Exception $e) {
				Log::add('Failed to batch resolve fabrik column types: ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
			}
		}

		$numeric_types = ['int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double'];
		foreach ($to_resolve as $id) {
			if (!isset($element_columns[$id])) {
				// Table/column unresolved: leave to the caller defaults, exactly as before.
				continue;
			}

			[$table, $column] = $element_columns[$id];
			$data_type        = $column_types[$table . '.' . $column] ?? null;
			$type             = in_array($data_type, $numeric_types, true) ? 'number' : 'text';

			$types[$id]  = $type;
			$cached[$id] = $type;
		}

		if ($this->h_cache->isEnabled()) {
			$this->h_cache->set('em-filters-elements-type', $cached);
		}

		return $types;
	}

	protected function getAllAssociatedElements($element_id): array
	{
		$elements = [];

		if ($this->h_cache->isEnabled()) {
			$elements = $this->h_cache->get('em-filters-associated-elements-' . $element_id);

			if (!empty($elements)) {
				return $elements;
			}
		}

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('jfl.form_id')
			->from('jos_fabrik_elements as jfe')
			->join('inner', 'jos_fabrik_groups as jfg ON jfg.id = jfe.group_id')
			->join('inner', 'jos_fabrik_formgroup as jffg ON jffg.group_id = jfg.id')
			->join('inner', 'jos_fabrik_lists as jfl ON jffg.form_id = jfl.form_id')
			->where('jfe.id = ' . $element_id);

		try {
			$db->setQuery($query);
			$data = $db->loadAssoc();
		} catch (Exception $e) {
			Log::add('Failed to get infos from fabrik element id ' . $element_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
		}

		if (!empty($data['form_id'])) {
			$query->clear()
				->select('jfe.id, jfe.plugin, jfe.label, jfe.params')
				->from('jos_fabrik_elements as jfe')
				->join('inner', 'jos_fabrik_formgroup as jffg ON jfe.group_id = jffg.group_id')
				->where('jffg.form_id = ' . $data['form_id'])
				->andWhere('published = 1')
				->andWhere('hidden = 0');

			try {
				$db->setQuery($query);
				$elements = $db->loadAssocList();

				if ($this->h_cache->isEnabled()) {
					$this->h_cache->set('em-filters-associated-elements-' . $element_id, $elements);
				}
			} catch (Exception $e) {
				Log::add('Failed to get elements associated element id ' . $element_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
			}
		}

		return $elements;
	}

	public function getFilters()
	{
		return $this->filters;
	}

	protected function setAppliedFilters($applied_filters)
	{
		$this->applied_filters = $applied_filters;
	}

	public function getAppliedFilters()
	{
		return $this->applied_filters;
	}

	protected function setQuickSearchFilters($quick_search_filters)
	{
		$this->quick_search_filters = $quick_search_filters;
	}

	public function getQuickSearchFilters()
	{
		return $this->quick_search_filters;
	}

	/**
	 * @param $element
	 * @return array
	 */
	protected function getFabrikElementValues($element, bool $applyWhere = true)
	{
		$values = [];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		switch ($element['plugin']) {
            case 'cascadingdropdown': // cascading dropdown is really similar to databasejoin. We just need to rewrite parameters entries to match those of databasejoin
                $params = json_decode($element['params'], true);

                list($table, $column) = explode('___', $params['cascadingdropdown_id']);
                $params['join_db_name'] = $table;
                $params['join_key_column'] = $column;
                list($table, $column) = explode('___', $params['cascadingdropdown_label']);
                $params['join_val_column'] = $column;
                $params['join_val_column_concat'] = $params['cascadingdropdown_label_concat'];
                $params['database_join_display_type'] = $params['cdd_display_type'];
                $element['params'] = json_encode($params);

            // we don't break here, we want to execute the code of databasejoin
            // ! DON'T ADD A BREAK HERE AND DON'T PUT ANYTHING BETWEEN THIS CASE AND DATABASEJOIN CASE ! //
			case 'databasejoin':
				if (!empty($element['params'])) {
					$params = json_decode($element['params'], true);

					if (!empty($params['join_db_name']) && !empty($params['join_key_column'])) {
						$select = $params['join_key_column'] . ' AS value';

						if (!empty($params['join_val_column_concat'])) {
							$lang = substr(Factory::getApplication()->getLanguage()->getTag(), 0, 2);
							$params['join_val_column_concat'] = str_replace('{thistable}', $params['join_db_name'], $params['join_val_column_concat']);
							$params['join_val_column_concat'] = str_replace('{shortlang}', $lang, $params['join_val_column_concat']);
							$params['join_val_column_concat'] = 'CONCAT(' . $params['join_val_column_concat'] . ') as label';

							if (preg_match_all('/[#_a-z]+\.[_a-z]+/', $params['join_val_column_concat'], $matches)) {
								foreach($matches[0] as $match) {
									$params['join_val_column_concat'] = str_replace($match, $db->quoteName($match), $params['join_val_column_concat']);
								}
							}
							$select .= ', ' . $params['join_val_column_concat'];
						} else {
							$select .= ', ' . $db->quoteName($params['join_val_column'], 'label');
						}

						$select .= ', 0 as count';

						$query->clear()
							->select($select)
							->from($params['join_db_name']);

						if($applyWhere && !empty($params['database_join_where_sql'])) {
							// TODO: I don't know yet how to handle complex database_join_where_sql using calculated fields
							if (strpos($params['database_join_where_sql'], '_raw}') === false) {
								$params['database_join_where_sql'] = str_replace('{thistable}', $params['join_db_name'], $params['database_join_where_sql']);
								$params['database_join_where_sql'] = str_replace('{shortlang}', $lang, $params['database_join_where_sql']);
								$first_where_pos = stripos($params['database_join_where_sql'], 'WHERE');

								if ($first_where_pos !== false) {
									$params['database_join_where_sql'] = substr($params['database_join_where_sql'], $first_where_pos + 5);
								}

								// this field can contains order by clause, so we need to split it
								$order_by_pos = stripos($params['database_join_where_sql'], 'ORDER BY');
								$where_clause = $order_by_pos !== false ? substr($params['database_join_where_sql'], 0, $order_by_pos) : $params['database_join_where_sql'];
								if (!empty($where_clause)) {
									$query->where($where_clause);
								}							}
						}

						try {
							$db->setQuery($query);
							$values = $db->loadAssocList();
						} catch (Exception $e) {
							Log::add('Failed to get filter values ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
						}
					}
				}
				break;
			case 'dropdown':
			case 'radiobutton':
			case 'checkbox':
			case ElementPluginEnum::ORDERLIST->value:
				if (!empty($element['params'])) {
					$params = json_decode($element['params'], true);
					if (!empty($params['sub_options'])) {
						foreach($params['sub_options']['sub_values'] as $sub_opt_key => $sub_opt) {
							$label = Text::_($params['sub_options']['sub_labels'][$sub_opt_key]);
							if (empty($label)) {
								$label = $sub_opt;
							}

							$values[] = [
								'count' => 0,
								'value' => $sub_opt,
								'label' => $label
							];
						}
					}
				}
				break;
		}

        foreach($values as $key => $value) {
           $values[$key]['label'] = Text::_($value['label']);
        }

		return $values;
	}

	public function getFabrikElementValuesFromElementId($element_id, bool $applyWhere = true) {
		$values = [];

		if (!empty($element_id)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('plugin, params')
				->from('jos_fabrik_elements')
				->where('id = ' . $element_id);

			try {
				$db->setQuery($query);
				$element = $db->loadAssoc();
			} catch (Exception $e) {
				Log::add('Failed to get element associated element id ' . $element_id . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.filters.error');
			}

			if (!empty($element)) {
				$values = $this->getFabrikElementValues($element, $applyWhere);

				if (!empty($values)) {
					$this->saveFiltersAllValues(['id' => $element_id, 'values' => $values]);
				}
			}
		}

		return $values;
	}

	protected function saveFiltersAllValues($element_values = null)
	{
		$session = Factory::getApplication()->getSession();

		if (!empty($element_values)) {
			$filters_all_values = $session->get('em-filters-all-values', []);
			$filters_all_values[$element_values['id']] = $element_values['values'];
		} else {
			$filters_all_values = [];

			foreach($this->filters as $filter) {
				if (!empty($filter['values'])) {
					$filters_all_values[$filter['id']] = $filter['values'];
				}
			}

			foreach($this->applied_filters as $filter) {
				if (!isset($filters_all_values[$filter['id']]) && !empty($filter['values'])) {
					$filters_all_values[$filter['id']] = $filter['values'];
				}
			}
		}

		$session->set('em-filters-all-values', $filters_all_values);
	}
}