<?php

namespace Tchooz\Entities\Automation\Comparators;

use Tchooz\Enums\Automation\ConditionMatchModeEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;

interface ComparatorInterface
{
	public function supports(mixed $expected, mixed $found): bool;

	public function compare(
		mixed $found,
		mixed $expected,
		ConditionOperatorEnum $op,
		?ConditionMatchModeEnum $mode = null
	): bool;
}
