<?php

namespace Tchooz\Entities\Calculation\Templates;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Services\Field\FieldOptionProvider;
use Tchooz\Services\Field\FieldWatcher;

class CalculateAverage extends CalculationTemplate
{

	public static function getCode(): string
	{
		return 'average';
	}

	public function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_CALCULATION_TPL_AVERAGE');
	}

	public function getParameters(): array
	{
		$repeatableGroup = new FieldGroup('elements', Text::_('COM_EMUNDUS_CALCULATION_TPL_AVERAGE_ELEMENTS'), true);
		$optionProvider = new FieldOptionProvider('automation', 'getConditionsFields', ['element_type']);

		return [
			new NumericField('result_out_of', Text::_('COM_EMUNDUS_CALCULATION_TPL_AVERAGE_RESULT_UPON')),
			new ChoiceField('element_type', Text::_('COM_EMUNDUS_CALCULATION_TPL_AVERAGE_ELEMENT_TYPE'), [
				new ChoiceFieldValue(ConditionTargetTypeEnum::FORMDATA->value, ConditionTargetTypeEnum::FORMDATA->getLabel()),
				new ChoiceFieldValue(ConditionTargetTypeEnum::ALIASDATA->value, ConditionTargetTypeEnum::ALIASDATA->getLabel()),
			], true, false, $repeatableGroup, false, false),
			(new ChoiceField('element_id', Text::_('COM_EMUNDUS_CALCULATION_TPL_AVERAGE_ELEMENT'), [], true, false, $repeatableGroup, false, false))
				->setOptionsProvider($optionProvider)
				->addWatcher(new FieldWatcher('element_type')),
			new NumericField('element_ponderation', Text::_('COM_EMUNDUS_CALCULATION_TPL_AVERAGE_ELEMENT_PONDERATION'), true, $repeatableGroup),
			new NumericField('element_out_of', Text::_('COM_EMUNDUS_CALCULATION_TPL_AVERAGE_ELEMENT_UPON'), false, $repeatableGroup),
		];
	}

	public function buildExpression(array $context): array
	{
		return [
			'expression' => 'average(elements, result_out_of)',
			'variables' => [
				'elements' => $context['elements'],
				'result_out_of' => $context['result_out_of'],
			],
		];
	}

	public function getExpressionFunction(): \Symfony\Component\ExpressionLanguage\ExpressionFunction
	{
		return new \Symfony\Component\ExpressionLanguage\ExpressionFunction(
			'average',
			function ($elements, $resultOutOf) {
				return "average($elements, $resultOutOf)";
			},
			function (array $variables, $elements, $resultOutOf) {
				$total = 0;
				$count = 0;

				foreach ($elements as $element) {
					if (!isset($element['element_value']))
					{
						throw new \InvalidArgumentException("Each element must have an 'element_value' key when calculating average()");
					}

					$value = $element['element_value'] ?? 0;
					$ponderation = $element['element_ponderation'] ?? 1;
					$outOf = $element['element_out_of'] ?? 100;

					if ($outOf > 0) {
						$total += ($value / $outOf) * $ponderation;
						$count += $ponderation;
					}
				}

				if ($count === 0) {
					return 0;
				}

				return ($total / $count) * $resultOutOf;
			}
		);
	}
}