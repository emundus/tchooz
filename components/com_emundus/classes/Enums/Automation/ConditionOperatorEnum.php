<?php

namespace Tchooz\Enums\Automation;

use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\DateField;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Entities\Fields\StringField;

enum ConditionOperatorEnum: string
{
	case EQUALS = '=';
	case NOT_EQUALS = '!=';
	case GREATER_THAN = '>';
	case GREATER_THAN_OR_EQUAL = '>=';
	case LESS_THAN = '<';
	case LESS_THAN_OR_EQUAL = '<=';
	case CONTAINS = 'contains';
	case NOT_CONTAINS = 'not_contains';
	case IS_EMPTY = 'empty';
	case IS_NOT_EMPTY = 'not_empty';

	public function getLabel(): string
	{
		return match($this) {
			self::EQUALS => 'COM_EMUNDUS_CONDITION_OPERATOR_EQUALS',
			self::NOT_EQUALS => 'COM_EMUNDUS_CONDITION_OPERATOR_NOT_EQUALS',
			self::GREATER_THAN => 'COM_EMUNDUS_CONDITION_OPERATOR_GREATER_THAN',
			self::GREATER_THAN_OR_EQUAL => 'COM_EMUNDUS_CONDITION_OPERATOR_GREATER_THAN_OR_EQUAL',
			self::LESS_THAN => 'COM_EMUNDUS_CONDITION_OPERATOR_LESS_THAN',
			self::LESS_THAN_OR_EQUAL => 'COM_EMUNDUS_CONDITION_OPERATOR_LESS_THAN_OR_EQUAL',
			self::CONTAINS => 'COM_EMUNDUS_CONDITION_OPERATOR_CONTAINS',
			self::NOT_CONTAINS => 'COM_EMUNDUS_CONDITION_OPERATOR_NOT_CONTAINS',
			self::IS_EMPTY => 'COM_EMUNDUS_CONDITION_OPERATOR_IS_EMPTY',
			self::IS_NOT_EMPTY => 'COM_EMUNDUS_CONDITION_OPERATOR_IS_NOT_EMPTY',
		};
	}

	public static function getAvailableOperatorsForFieldType(): array
	{
		return [
			ChoiceField::getType() => [
				self::EQUALS,
				self::NOT_EQUALS,
				self::IS_EMPTY,
				self::IS_NOT_EMPTY
			],
			BooleanField::getType() => [
				self::EQUALS,
				self::NOT_EQUALS,
				self::IS_EMPTY,
				self::IS_NOT_EMPTY
			],
			StringField::getType() => [
				self::EQUALS,
				self::NOT_EQUALS,
				self::CONTAINS,
				self::NOT_CONTAINS,
				self::IS_EMPTY,
				self::IS_NOT_EMPTY
			],
			NumericField::getType() => [
				self::EQUALS,
				self::NOT_EQUALS,
				self::GREATER_THAN,
				self::GREATER_THAN_OR_EQUAL,
				self::LESS_THAN,
				self::LESS_THAN_OR_EQUAL,
				self::IS_EMPTY,
				self::IS_NOT_EMPTY
			],
			DateField::getType() => [
				self::EQUALS,
				self::NOT_EQUALS,
				self::GREATER_THAN,
				self::GREATER_THAN_OR_EQUAL,
				self::LESS_THAN,
				self::LESS_THAN_OR_EQUAL,
				self::IS_EMPTY,
				self::IS_NOT_EMPTY
			],
			'default' => [
				self::EQUALS,
				self::NOT_EQUALS,
				self::CONTAINS,
				self::NOT_CONTAINS,
				self::IS_EMPTY,
				self::IS_NOT_EMPTY
			]
		];
	}
}
