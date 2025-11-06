<?php

require_once(__DIR__ . '/EmundusTableForeignKeyOnEnum.php');

class EmundusTableForeignKey
{
	public function __construct(
		private string $name,
		private string $fromColumn,
		private string $referencedTable,
		private string $referencedColumn,
		private EmundusTableForeignKeyOnEnum $onUpdate = EmundusTableForeignKeyOnEnum::NO_ACTION,
		private EmundusTableForeignKeyOnEnum $onDelete = EmundusTableForeignKeyOnEnum::NO_ACTION
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getFromColumn(): string
	{
		return $this->fromColumn;
	}

	public function getReferencedTable(): string
	{
		return $this->referencedTable;
	}

	public function getReferencedColumn(): string
	{
		return $this->referencedColumn;
	}

	public function getOnUpdate(): EmundusTableForeignKeyOnEnum
	{
		return $this->onUpdate;
	}

	public function getOnDelete(): EmundusTableForeignKeyOnEnum
	{
		return $this->onDelete;
	}
}