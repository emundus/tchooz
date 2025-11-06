<?php

namespace Tchooz\Factories\Automation;

use Joomla\CMS\Factory;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\TableJoin;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Services\Automation\Condition\ConditionTargetResolverInterface;
use Tchooz\Services\Automation\ConditionRegistry;
use Joomla\Database\DatabaseDriver;

class ConditionsQueryFactory
{

	private DatabaseDriver $db;
	private ConditionRegistry $registry;

	public function __construct(?DatabaseDriver $db = null, ?ConditionRegistry $registry = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
		$this->registry = $registry ?? new ConditionRegistry();
	}

	/**
	 * The goal is to transform a set of condition entities and construct a SQL query with it
	 *
	 * @param   array<ConditionEntity>  $conditions
	 *
	 * @throws \Exception
	 */
	public function buildConditionsQuery(array $conditions, TargetTypeEnum $type = TargetTypeEnum::FILE, ?ActionTargetEntity $context = null): ?object
	{
		$query = null;

		if (!empty($conditions))
		{
			$query = $this->db->createQuery();
			$query->select('DISTINCT ' . $type->getTableAlias() . '.' .$type->getPrimaryField())
				->from($type->getTable() . ' AS ' . $type->getTableAlias())
				->where("1=1");

			$tablesJoined = [];
			foreach ($conditions as $condition)
			{
				assert($condition instanceof ConditionEntity);
				$resolver = $this->registry->getResolver($condition->getTargetType()->value);
				assert($resolver instanceof ConditionTargetResolverInterface);

				if (!method_exists($resolver, 'getTableName'))
				{
					throw new \Exception('Unable to get condition table name for condition ' . $condition->getId());
				}

				$joins = $resolver->getJoinsToTable($type);
				if (!empty($joins))
				{
					foreach ($joins as $join)
					{
						assert($join instanceof TableJoin);
						if (!in_array($join->getTableName(), $tablesJoined))
						{
							$tablesJoined[] = $join->getTableName();
							$query->join($join->getJoinType(), $join->getTableName() . ' AS ' . $join->getAlias() . ' ON ' . $join->getOn());
						}
					}
				}

				$fieldJoins = $resolver->getJoins($condition->getField());
				if (!empty($fieldJoins))
				{
					foreach ($fieldJoins as $join)
					{
						assert($join instanceof TableJoin);
						if (!in_array($join->getTableName(), $tablesJoined))
						{
							$tablesJoined[] = $join->getTableName();
							$query->join($join->getJoinType(), $join->getTableName() . ' AS ' . $join->getAlias() . ' ON ' . $join->getOn());
						}
					}
				}

				$this->addWhere($query, $resolver, $condition, $context);
			}
		}

		return $query;
	}

	public function addWhere($query, ConditionTargetResolverInterface $resolver, ConditionEntity $condition, ?ActionTargetEntity $context = null): void
	{
		$columns = $resolver->getColumnsForField($condition->getField());
		$value = $condition->getValue();

		if (!empty($context)) {
			$value = $condition->getTransformedValue($context, $resolver);
		}

		if (sizeof($columns) > 1) {
			$orExpressions = [];
			foreach ($columns as $column) {
				$orExpressions[] = $this->translateOperator($column, $condition->getOperator(), $value);
			}

			$query->andWhere('(' . implode(' OR ', $orExpressions) . ')');

		} else {
			$query->andWhere($this->translateOperator($columns[0], $condition->getOperator(), $value));
		}
	}

	public function translateOperator(string $field, ConditionOperatorEnum $operator, mixed $value): string
	{
		switch($operator)
		{
			case ConditionOperatorEnum::EQUALS:
				if (is_array($value))
				{
					$expression = $this->db->quoteName($field) . ' IN (' . implode(',', $value) . ')';
				} else
				{
					$expression = $this->db->quoteName($field) . ' = ' . $this->db->quote($value);
				}
				break;
			case ConditionOperatorEnum::NOT_EQUALS:
				if (is_array($value))
				{
					$expression = $this->db->quoteName($field) . ' NOT IN (' . implode(',', $value) . ')';
				}
				else
				{
					$expression = $this->db->quoteName($field) . ' != ' . $this->db->quote($value);
				}
				break;
			case ConditionOperatorEnum::CONTAINS:
				if (is_array($value))
				{
					$likes = [];
					foreach ($value as $v) {
						$likes[] = $this->db->quoteName($field) . ' LIKE ' . $this->db->quote('%' . $v . '%');
					}
					$expression = '(' . implode(' OR ', $likes) . ')';
				} else
				{
					$expression = $this->db->quoteName($field) . ' LIKE ' . $this->db->quote('%' . $value . '%');
				}
				break;
			case ConditionOperatorEnum::NOT_CONTAINS:
				if (is_array($value))
				{
					$likes = [];
					foreach ($value as $v)
					{
						$likes[] = $this->db->quoteName($field) . ' NOT LIKE ' . $this->db->quote('%' . $v . '%');
					}
					$expression = '(' . implode(' AND ', $likes) . ')';
				} else {
					$expression = $this->db->quoteName($field) . ' NOT LIKE ' . $this->db->quote('%' . $value . '%');
				}
				break;
			case ConditionOperatorEnum::GREATER_THAN:
				$expression = $this->db->quoteName($field) . ' > ' . $this->db->quote($value);
				break;
			case ConditionOperatorEnum::GREATER_THAN_OR_EQUAL:
				$expression = $this->db->quoteName($field) . ' >= ' . $this->db->quote($value);
				break;
			case ConditionOperatorEnum::LESS_THAN:
				$expression = $this->db->quoteName($field) . ' < ' . $this->db->quote($value);
				break;
			case ConditionOperatorEnum::LESS_THAN_OR_EQUAL:
				$expression = $this->db->quoteName($field) . ' <= ' . $this->db->quote($value);
				break;
			case ConditionOperatorEnum::IS_EMPTY:
				$expression = '(' . $this->db->quoteName($field) . ' IS NULL OR ' . $this->db->quote($field) . ' = ' . $this->db->quote('') . ')';
				break;
			case ConditionOperatorEnum::IS_NOT_EMPTY:
				$expression = '(' . $this->db->quoteName($field) . ' IS NOT NULL AND ' . $this->db->quote($field) . ' != ' . $this->db->quote('') . ')';
				break;
			default:
				throw new \InvalidArgumentException("Unsupported operator: " . $operator->value);
		}

		return $expression;
	}
}