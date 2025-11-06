<?php

namespace Tchooz\Entities\Automation\Comparators;

use Tchooz\Enums\Automation\ConditionMatchModeEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;

class DateComparator implements ComparatorInterface
{
	public function supports(mixed $expected, mixed $found): bool
	{
		return $this->isDateLike($expected) && $this->isDateLike($found);
	}

	private function isDateLike($value): bool
	{
		return is_string($value) && preg_match('/^\d{2}-\d{2}-\d{4}( \d{2}:\d{2}:\d{2})?$/', $value);
	}

	private function toDateTime(string $value): \DateTime
	{
		$format = str_contains($value, ' ') ? 'd-m-Y H:i:s' : 'd-m-Y';
		return \DateTime::createFromFormat($format, $value);
	}

	public function compare(
		mixed $found,
		mixed $expected,
		ConditionOperatorEnum $op,
		?ConditionMatchModeEnum $mode = null
	): bool {
		$foundDate    = $this->toDateTime($found);
		$expectedDate = $this->toDateTime($expected);

		return match ($op) {
			ConditionOperatorEnum::EQUALS => $foundDate == $expectedDate,
			ConditionOperatorEnum::NOT_EQUALS => $foundDate != $expectedDate,
			ConditionOperatorEnum::GREATER_THAN => $foundDate > $expectedDate,
			ConditionOperatorEnum::GREATER_THAN_OR_EQUAL => $foundDate >= $expectedDate,
			ConditionOperatorEnum::LESS_THAN => $foundDate < $expectedDate,
			ConditionOperatorEnum::LESS_THAN_OR_EQUAL => $foundDate <= $expectedDate,
			default => throw new \InvalidArgumentException("Unsupported operator for Date: " . $op->value),
		};
	}
}
