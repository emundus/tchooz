<?php

namespace Tchooz\Entities\Calculation\Templates;

use Joomla\CMS\Language\Text;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Time\TimeUnitEnum;
use Tchooz\Factories\Field\ChoiceFieldFactory;
use Tchooz\Repositories\Automation\ConditionRepository;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Services\Field\FieldResearch;

class CalculateDatesDiff extends CalculationTemplate
{

	public static function getCode(): string
	{
		return 'dates_diff';
	}

	public function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_CALCULATION_TPL_DATES_DIFF');
	}

	public function getParameters(): array
	{
		$research = new FieldResearch('automation', 'getConditionsFields', 'search_query', ['type' => ConditionTargetTypeEnum::FORMDATA->value]);

		$currentDateValue = new ChoiceFieldValue(null, Text::_('COM_EMUNDUS_CALCULATION_TPL_DATES_CURRENT_DATE'));

		// not TimeUnitEnum::cases cause hours, minutes and seconds are not relevant in this context yet
		$units = [
			new ChoiceFieldValue(TimeUnitEnum::YEARS->value, TimeUnitEnum::YEARS->getLabel()),
			new ChoiceFieldValue(TimeUnitEnum::MONTHS->value, TimeUnitEnum::MONTHS->getLabel()),
			new ChoiceFieldValue(TimeUnitEnum::WEEKS->value, TimeUnitEnum::WEEKS->getLabel()),
			new ChoiceFieldValue(TimeUnitEnum::DAYS->value, TimeUnitEnum::DAYS->getLabel()),
		];
		return [
			new ChoiceField('unit', Text::_('COM_EMUNDUS_CALCULATION_TPL_DATES_DIFF_UNIT'), $units, true),
			(new ChoiceField('start_date_element', Text::_('COM_EMUNDUS_CALCULATION_TPL_DATES_DIFF_START'), [$currentDateValue], true, false, null, true, false))->setResearch($research),
			(new ChoiceField('end_date_element', Text::_('COM_EMUNDUS_CALCULATION_TPL_DATES_DIFF_END'), [$currentDateValue], false, false, null, true, false))->setResearch($research),
		];
	}

	public function buildExpression(array $context): array
	{
		return [
			'expression' => 'diff(end_date_element, start_date_element, unit)',
			'variables' => [
				'unit' => $context['unit'],
				'start_date_element' => $context['start_date_element'],
				'end_date_element' => $context['end_date_element'],
			],
		];
	}

	public function getExpressionFunction(): ExpressionFunction
	{
		return new ExpressionFunction(
			'diff',
			function ($end, $start, $unit) {
				return "diff($end, $start, $unit)";
			},
			function (array $variables, $end, $start, $unit) {
				$endDate = new \DateTime($end);
				$startDate = new \DateTime($start);
				$interval = $startDate->diff($endDate);

				$sign = $interval->invert ? -1 : 1;
				$totalDays = $interval->days * $sign;

				return match ($unit) {
					TimeUnitEnum::YEARS, TimeUnitEnum::YEARS->value => $interval->y * $sign,
					TimeUnitEnum::MONTHS, TimeUnitEnum::MONTHS->value => (($interval->y * 12) + $interval->m) * $sign,
					TimeUnitEnum::WEEKS, TimeUnitEnum::WEEKS->value => intdiv($totalDays, 7),
					TimeUnitEnum::DAYS, TimeUnitEnum::DAYS->value => $totalDays,
					TimeUnitEnum::HOURS, TimeUnitEnum::HOURS->value => (($interval->days * 24) + $interval->h) * $sign,
					TimeUnitEnum::MINUTES, TimeUnitEnum::MINUTES->value => ((($interval->days * 24) + $interval->h) * 60 + $interval->i) * $sign,
					TimeUnitEnum::SECONDS, TimeUnitEnum::SECONDS->value => (((($interval->days * 24) + $interval->h) * 60 + $interval->i) * 60 + $interval->s) * $sign,
					default => throw new \InvalidArgumentException("Invalid unit: $unit"),
				};
			}
		);
	}

	public function isAvailable(): bool
	{
		return true;
	}
}