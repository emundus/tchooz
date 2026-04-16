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
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

// TODO: Maybe move condition builder to the system built for automation and mapping
class ConditionService
{
	private DatabaseInterface $db;

	public function __construct()
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function checkNotEmptyRules(string $field, array $formData, int $formId, int $repeatCounter = 0): bool
	{
		$filters = [
			'fields' => $field,
			'action' => ['set_optional','set_mandatory']
		];
		$rules = $this->getRules($filters);

		foreach ($rules as $rule)
		{
			if (!empty($rule->parent_id))
			{
				$conditions = $this->getConditions($rule->parent_id, $formId);

				if(!empty($conditions))
				{
					$conditions = $this->groupConditions($conditions);

					$result = $this->checkCondition($conditions, $formData, $rule->group, $repeatCounter);

					if (!$result)
					{
						if ($rule->action == 'set_optional')
						{
							return true;
						}
						else
						{
							return false;
						}
					}
					else
					{
						if ($rule->action == 'set_optional')
						{
							return false;
						}
						else
						{
							return true;
						}
					}
				}
			}
		}

		return true;
	}

	private function groupConditions(array $conditions)
	{
		return array_reduce($conditions, function ($carry, $item) {
			if ($item->group === null) {
				$carry[] = $item;
			} else {
				$carry[$item->group][] = $item;
			}
			return $carry;
		});
	}

	private function checkCondition(array $conditions, array $formData, string $ruleGroup, int $repeatCounter = 0): bool
	{
		$condition_state = [];

		foreach ($conditions as $group => $condition)
		{
			$group_type = 'AND';
			if(is_array($condition))
			{
				$group_type = $this->getGroupType($group);
			}
			else {
				$condition = [$condition];
			}

			$subcondition_state = [];
			foreach ($condition as $subCondition)
			{
				foreach ($formData as $key => $data)
				{
					if (strpos($key, $subCondition->field . '_raw'))
					{
						$value = $data;
						if (strpos($key, 'repeat'))
						{
							$value = $data[$repeatCounter];
						}

						switch ($subCondition->state)
						{
							case '=': // Equal
								if (is_array($value))
								{
									$subcondition_state[] = in_array($subCondition->values, $value);
								}
								else
								{
									$subcondition_state[] = $value == $subCondition->values;
								}
								break;
							case '!=': // Not equal
								if (is_array($value))
								{
									$subcondition_state[] = !in_array($subCondition->values, $value);
								}
								else
								{
									$subcondition_state[] = $value != $subCondition->values;
								}
								break;
						}
						break;
					}
				}
			}

			if($group_type === 'OR')
			{
				// We need only once true to be true in condition_state
				$condition_state[] = in_array(true, $subcondition_state, true);
			}
			else {
				// We need all true to be true in condition_state
				$condition_state[] = !in_array(false, $subcondition_state, true);
			}
		}

		if($ruleGroup === 'OR')
		{
			return in_array(true, $condition_state);
		}
		else {
			return in_array(false, $condition_state);
		}
	}

	public function getRules(array $filters = [])
	{
		$query = $this->buildRulesQuery();
		$this->applyFilters($query, $filters);

		$this->db->setQuery($query);
		return $this->db->loadObjectList();
	}

	public function getConditions(int $ruleId, int $formId = 0)
	{
		$query = $this->buildConditionsQuery();
		$query->where($this->db->quoteName('esfrjc.parent_id') . ' = ' . $this->db->quote($ruleId));
		if($formId > 0)
		{
			$query->where($this->db->quoteName('esfr.form_id') . ' = ' . $this->db->quote($formId));
		}

		$this->db->setQuery($query);
		return $this->db->loadObjectList();
	}

	public function getGroupType(int $id): ?string
	{
		$query = $this->db->getQuery(true);

		$query->select('group_type')
			->from($this->db->quoteName('#__emundus_setup_form_rules_js_conditions_group'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));
		$this->db->setQuery($query);
		return $this->db->loadResult();
	}

	private function buildRulesQuery(): QueryInterface
	{
		$query = $this->db->getQuery(true);

		$query
			->select([
				$this->db->quoteName('esfrja.parent_id'),
				$this->db->quoteName('esfrja.action'),
				$this->db->quoteName('esfr.group')
			])
			->from($this->db->quoteName('#__emundus_setup_form_rules_js_actions', 'esfrja'))
			->leftJoin($this->db->quoteName('#__emundus_setup_form_rules_js_actions_fields', 'esfrjaf') . ' ON ' . $this->db->quoteName('esfrjaf.parent_id') . ' = ' . $this->db->quoteName('esfrja.id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_form_rules', 'esfr') . ' ON ' . $this->db->quoteName('esfr.id') . ' = ' . $this->db->quoteName('esfrja.parent_id'));

		return $query;
	}

	private function buildConditionsQuery(): QueryInterface
	{
		$query = $this->db->getQuery(true);

		$query->select('esfrjc.field, esfrjc.state, esfrjc.values, esfrjc.group')
			->from($this->db->quoteName('#__emundus_setup_form_rules_js_conditions', 'esfrjc'))
			->leftJoin($this->db->quoteName('#__emundus_setup_form_rules', 'esfr') . ' ON ' . $this->db->quoteName('esfr.id') . ' = ' . $this->db->quoteName('esfrjc.parent_id'));

		return $query;
	}

	private function applyFilters(QueryInterface $query, array $filters = []): void
	{
		$filterKeys = array_keys($filters);
		if(in_array('fields', $filterKeys))
		{
			$this->applyFilter($query, 'esfrjaf.fields', $filters['fields']);
		}
		if(in_array('action', $filterKeys))
		{
			$this->applyFilter($query, 'esfrja.action', $filters['action']);
		}
	}
	
	private function applyFilter(QueryInterface $query, string $column, mixed $value): void
	{
		if(is_array($value))
		{
			$values = implode(',', array_map([$this->db, 'quote'], $value));
			$query->where($this->db->quoteName($column) . ' IN (' . $values . ')');
		}
		else
		{
			$query->where($this->db->quoteName($column) . ' = ' . $this->db->quote($value));
		}
	}
}