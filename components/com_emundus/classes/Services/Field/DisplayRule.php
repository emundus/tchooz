<?php

namespace Tchooz\Services\Field;

use Tchooz\Entities\Fields\Field;
use Tchooz\Enums\Automation\ConditionOperatorEnum;

class DisplayRule
{
	public function __construct(
		private Field $field,
		private ConditionOperatorEnum $conditionOperator = ConditionOperatorEnum::EQUALS,
		private mixed $value = null
	)
	{}

	public function getField(): Field
	{
		return $this->field;
	}

	public function getConditionOperator(): ConditionOperatorEnum
	{
		return $this->conditionOperator;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}

	public function toSchema(): array
	{
		return [
			'field' => $this->field->getName(),
			'conditionOperator' => $this->getConditionOperator()->value,
			'value' => $this->value,
		];
	}
}