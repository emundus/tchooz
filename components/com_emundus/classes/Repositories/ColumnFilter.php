<?php

namespace Tchooz\Repositories;

use Tchooz\Enums\Automation\ConditionOperatorEnum;

class ColumnFilter
{
	public function __construct(
		private string $column,
		private ConditionOperatorEnum $operator,
		private mixed $value = null
	)
	{
	}

	public function getColumn(): string
	{
		return $this->column;
	}

	public function getOperator(): ConditionOperatorEnum
	{
		return $this->operator;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}
}