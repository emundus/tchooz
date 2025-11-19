<?php

namespace Tchooz\Entities\Automation;

use Tchooz\Enums\Automation\ConditionsAndorEnum;

class ConditionGroupEntity
{
	private int $id;

	private int $parent_id = 0;

	private ConditionsAndorEnum $operator = ConditionsAndorEnum::AND;

	/**
	 * @var array <ConditionEntity>
	 */
	private array $conditions = [];

	/**
	 * @var array<ConditionGroupEntity>
	 */
	private array $subGroups = [];

	public function __construct(int $id, array $conditions = [], ?ConditionsAndorEnum $operator = null, int $parentId = 0, $subGroups = [])
	{
		$this->id = $id;
		$this->conditions = $conditions;
		if ($operator !== null) {
			$this->operator = $operator;
		}
		$this->parent_id = $parentId;
		$this->subGroups = $subGroups;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	/**
	 * @return array<ConditionEntity>
	 */
	public function getConditions(): array
	{
		return $this->conditions;
	}

	/**
	 * @param array<ConditionEntity> $conditions
	 */
	public function setConditions(array $conditions): void
	{
		foreach ($conditions as $condition) {
			assert($condition instanceof ConditionEntity);
		}

		$this->conditions = $conditions;
	}

	public function setOperator(ConditionsAndorEnum $operator): void
	{
		$this->operator = $operator;
	}

	public function getOperator(): ConditionsAndorEnum
	{
		return $this->operator;
	}

	public function setParentId(int $parentId): void
	{
		$this->parent_id = $parentId;
	}

	public function getParentId(): int
	{
		return $this->parent_id;
	}

	/**
	 * @return ConditionGroupEntity[]
	 */
	public function getSubGroups(): array
	{
		return $this->subGroups;
	}

	public function setSubGroups(array $subGroups): void
	{
		foreach ($subGroups as $subGroup) {
			assert($subGroup instanceof self);
		}

		$this->subGroups = $subGroups;
	}

	/**
	 * @param   ActionTargetEntity  $context*
	 * @return bool
	 */
	public function isSatisfied(ActionTargetEntity $context): bool
	{
		$results = [];

		// Évaluer les conditions du groupe courant
		foreach ($this->conditions as $condition)
		{
			assert($condition instanceof ConditionEntity);
			$satisfiedCondition = $condition->isSatisfied($context);
			$results[]          = $satisfiedCondition;

			if (!$satisfiedCondition && $this->operator === ConditionsAndorEnum::AND)
			{
				// Si l'opérateur est AND et qu'une condition n'est pas satisfaite, on peut retourner false immédiatement
				return false;
			}
			elseif ($satisfiedCondition && $this->operator === ConditionsAndorEnum::OR)
			{
				// Si l'opérateur est OR et qu'une condition est satisfaite, on peut retourner true immédiatement
				return true;
			}
		}


		// Évaluer les sous-groupes
		if (!empty($this->getSubGroups())) {
			foreach ($this->getSubGroups() as $subGroup) {
				assert($subGroup instanceof self);
				$results[] = $subGroup->isSatisfied($context);
			}
		}

		if ($this->operator === ConditionsAndorEnum::AND) {
			// Tous doivent être vrais
			return !in_array(false, $results, true);
		} else {
			// Au moins un doit être vrai
			return in_array(true, $results, true);
		}
	}

	public function serialize(): array
	{
		return [
			'id' => $this->id,
			'parent_id' => $this->parent_id,
			'operator' => $this->operator->value,
			'conditions' => array_map(function (ConditionEntity $condition) {
				return $condition->serialize();
			}, $this->conditions),
			'subGroups' => array_map(function (ConditionGroupEntity $subGroup) {
				return $subGroup->serialize();
			}, $this->subGroups),
		];
	}
}