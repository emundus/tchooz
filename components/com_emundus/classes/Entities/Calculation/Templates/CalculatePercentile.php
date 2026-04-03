<?php

namespace Tchooz\Entities\Calculation\Templates;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Services\Field\FieldOptionProvider;

class CalculatePercentile extends CalculationTemplate
{
	public static function getCode(): string
	{
		return 'percentile';
	}

	public function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_CALCULATION_TPL_PERCENTILE');
	}

	public function getParameters(): array
	{
		$repeatableGroup = new FieldGroup('elements', Text::_('COM_EMUNDUS_CALCULATION_TPL_PERCENTILE_ELEMENTS'), true);
		$optionProvider = new FieldOptionProvider('automation', 'getConditionsFields', ['element_type']);

		return [
			(new NumericField('percentile', Text::_('COM_EMUNDUS_CALCULATION_TPL_PERCENTILE_PERCENTILE'), false))
				->setMin(0)
				->setMax(100),
			new ChoiceField('element_type', Text::_('COM_EMUNDUS_CALCULATION_TPL_PERCENTILE_ELEMENT_TYPE'), [
				new ChoiceFieldValue(ConditionTargetTypeEnum::FORMDATA->value, ConditionTargetTypeEnum::FORMDATA->getLabel()),
				new ChoiceFieldValue(ConditionTargetTypeEnum::ALIASDATA->value, ConditionTargetTypeEnum::ALIASDATA->getLabel()),
			], true, false, $repeatableGroup, false, false),
			(new ChoiceField('element_id', Text::_('COM_EMUNDUS_CALCULATION_TPL_PERCENTILE_ELEMENT'), [], true, false, $repeatableGroup, false, false))->setOptionsProvider($optionProvider),
		];
	}

	public function buildExpression(array $context): array
	{
		return [
			'expression' => 'percentile(elements, percentile)',
			'variables'  => [
				'elements'   => $context['elements'],
				'percentile' => $context['percentile'],
			],
		];
	}

	public function getExpressionFunction(): \Symfony\Component\ExpressionLanguage\ExpressionFunction
	{
		return new \Symfony\Component\ExpressionLanguage\ExpressionFunction(
			'percentile',
			function ($elements, $percentile) {
				return "percentile($elements, $percentile)";
			},
			function (array $variables, $elements, $percentile) {
				$values = [];
				foreach ($elements as $element)
				{
					if (!isset($element['element_value']))
					{
						throw new \InvalidArgumentException("Each element must have an 'element_value' key when calculating percentile()");
					}
					$values[]  = is_numeric($element['element_value']) ? $element['element_value'] : 0;
				}

				if (count($values) === 0)
				{
					return 0;
				}

				sort($values);
				$n     = count($values);
				$p     = max(0, min(100, (float) $percentile));
				$pos   = ($n - 1) * ($p / 100);
				$floor = (int) floor($pos);
				$ceil  = (int) ceil($pos);
				if ($floor === $ceil)
				{
					$percentileValue = $values[$floor];
				}
				else
				{
					$percentileValue = $values[$floor] + ($values[$ceil] - $values[$floor]) * ($pos - $floor);
				}

				return $percentileValue;
			}
		);
	}
}

