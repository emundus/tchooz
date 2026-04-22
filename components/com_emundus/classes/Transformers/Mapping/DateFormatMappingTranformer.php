<?php

namespace Tchooz\Transformers\Mapping;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\ChoiceFieldValue;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;

class DateFormatMappingTranformer extends MappingTranformer
{
	public function __construct()
	{
		$parameters = [new ChoiceField('format', Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_DATE_FORMAT_PARAMETER_FORMAT'), $this->getAvailableFormats())];
		parent::__construct(MappingTransformersEnum::DATE_FORMAT, $parameters);
	}

	private function getAvailableFormats(): array
	{
		return [
			new ChoiceFieldValue('Y-m-d', 'Y-m-d (2024-12-31)'),
			new ChoiceFieldValue('d/m/Y', 'd/m/Y (31/12/2024)'),
			new ChoiceFieldValue('m-d-Y', 'm-d-Y (12-31-2024)'),
			new ChoiceFieldValue('Y', 'Y (2024)'),
			new ChoiceFieldValue('y', 'y (24)'),
		];
	}

	public function transform(mixed $value): mixed
	{
		// Check that the value is a valid date string
		if (is_string($value) && strtotime($value) !== false) {
			$date = new \DateTime($value);
			
			// Check if we have a format parameter
			$format = $this->getParameterValue('format');
			if(empty($format))
			{
				$format = 'Y-m-d'; // Default format if none provided
			}

			return $date->format($format);
		}

		return $value;
	}
}