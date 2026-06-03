<?php

namespace Tchooz\Factories\Fabrik;

use Tchooz\Entities\Fields\ChoiceFieldValue;

class FabrikOptionsFactory
{
	public static function makeOptionsFromEnum(array $enumCases): array
	{
		$options = [
			'sub_options' => [
				'sub_values' => [],
				'sub_labels' => []
			]
		];

		foreach ($enumCases as $case)
		{
			if (method_exists($case, 'getLabel'))
			{
				$label = $case->getLabel();
			}
			else
			{
				$label = $case->value;
			}

			$options['sub_options']['sub_values'][] = $case->value;
			$options['sub_options']['sub_labels'][] = $label;
		}

		return $options;
	}
}