<?php

namespace Tchooz\Entities\Automation\Comparators;

use Tchooz\Entities\Automation\Comparators\ComparatorInterface;
use Tchooz\Enums\Automation\ConditionMatchModeEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;

class ScalarComparator implements ComparatorInterface
{
	public function supports(mixed $expected, mixed $found): bool
	{
		return is_scalar($expected) && is_scalar($found);
	}

	public function compare(
		mixed $found,
		mixed $expected,
		ConditionOperatorEnum $op,
		?ConditionMatchModeEnum $mode = null
	): bool {
		if (is_numeric($expected)) {
			$expected = (float) $expected;
		}
		if (is_numeric($found)) {
			$found = (float) $found;
		}

		return match ($op) {
			ConditionOperatorEnum::EQUALS => $found == $expected,
			ConditionOperatorEnum::NOT_EQUALS => $found != $expected,
			ConditionOperatorEnum::GREATER_THAN => $found > $expected,
			ConditionOperatorEnum::GREATER_THAN_OR_EQUAL => $found >= $expected,
			ConditionOperatorEnum::LESS_THAN => $found < $expected,
			ConditionOperatorEnum::LESS_THAN_OR_EQUAL => $found <= $expected,
			ConditionOperatorEnum::CONTAINS =>
				is_string($found) && is_string($expected) && str_contains($found, $expected),
			ConditionOperatorEnum::NOT_CONTAINS =>
				is_string($found) && is_string($expected) && !str_contains($found, $expected),
			ConditionOperatorEnum::IS_EMPTY => empty($found),
			ConditionOperatorEnum::IS_NOT_EMPTY => !empty($found),
			default => throw new \InvalidArgumentException("Unsupported operator for Scalar: " . $op->value),
		};
	}
}
