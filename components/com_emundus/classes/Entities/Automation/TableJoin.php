<?php

namespace Tchooz\Entities\Automation;

class TableJoin
{
	public function __construct(
		private string $tableName,
		private string $alias,
		private string $fromField,
		private string $referenceField,
		private string $referencedTableAlias,
		private ?string $joinType = 'INNER',
		private ?string $additionalConditions = null
	)
	{
	}

	public function getTableName(): string
	{
		return $this->tableName;
	}

	public function getAlias(): string
	{
		return $this->alias;
	}

	public function getFromField(): string
	{
		return $this->fromField;
	}

	public function getReferenceField(): string
	{
		return $this->referenceField;
	}

	public function getReferencedTableAlias(): string
	{
		return $this->referencedTableAlias;
	}

	public function getJoinType(): ?string
	{
		return $this->joinType;
	}

	public function getAdditionalConditions(): ?string
	{
		return $this->additionalConditions;
	}

	public function getOn(): string
	{
		return "{$this->alias}.{$this->fromField} = {$this->referencedTableAlias}.{$this->referenceField}" . ($this->additionalConditions ? " AND {$this->additionalConditions}" : '');
	}
}