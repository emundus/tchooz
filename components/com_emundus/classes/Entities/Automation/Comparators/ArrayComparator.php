<?php

namespace Tchooz\Entities\Automation\Comparators;

use Tchooz\Enums\Automation\ConditionMatchModeEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;

class ArrayComparator implements ComparatorInterface
{
	public function supports(mixed $expected, mixed $found): bool
	{
		return is_array($expected) || is_array($found);
	}

	public function compare(
		mixed $found,
		mixed $expected,
		ConditionOperatorEnum $op,
		?ConditionMatchModeEnum $mode = null
	): bool {
		$expectedArr = (array) $expected;
		$foundArr    = (array) $found;

		return match ($op) {
			ConditionOperatorEnum::EQUALS => $this->equals($foundArr, $expectedArr, $mode),
			ConditionOperatorEnum::NOT_EQUALS => !$this->equals($foundArr, $expectedArr, $mode),
			ConditionOperatorEnum::IS_EMPTY => empty($foundArr),
			ConditionOperatorEnum::IS_NOT_EMPTY => !empty($foundArr),
			default => throw new \InvalidArgumentException("Unsupported operator for Array: " . $op->value),
		};
	}

	private function equals(array $found, array $expected, ConditionMatchModeEnum $mode): bool
	{
		return match ($mode) {
			ConditionMatchModeEnum::ANY   => !empty(array_intersect($expected, $found)),
			ConditionMatchModeEnum::ALL   => empty(array_diff($expected, $found)),
			ConditionMatchModeEnum::EXACT => (count($expected) === count($found))
				&& empty(array_diff($expected, $found))
				&& empty(array_diff($found, $expected)),
		};
	}
}
