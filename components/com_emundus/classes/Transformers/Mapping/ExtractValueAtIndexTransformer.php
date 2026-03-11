<?php

namespace Tchooz\Transformers\Mapping;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\NumericField;
use Tchooz\Enums\Mapping\MappingTransformersEnum;

class ExtractValueAtIndexTransformer extends MappingTranformer
{
	const PARAMETER_INDEX = 'index';

	public function __construct()
	{
		$parameters = [
			new NumericField(self::PARAMETER_INDEX, Text::_('COM_EMUNDUS_TRANSFORMER_EXTRACT_AT_INDEX_LABEL'), true)
		];

		parent::__construct(MappingTransformersEnum::EXTRACT_VALUE_AT_INDEX, $parameters);
	}

	/**
	 * @inheritDoc
	 */
	public function transform(mixed $value): mixed
	{
		$toIndex = $this->getParameterValue(self::PARAMETER_INDEX);
		// the index is selected by the user, so it does not know about the 0-based index of arrays and strings in PHP, so we need to subtract 1 from the index
		$toIndex = $toIndex - 1;

		if (is_array($value))
		{
			$transformedValue = $value[$toIndex] ?? null;
		}
		else if (is_string($value))
		{
			$characters = str_split($value);
			$transformedValue = $characters[$toIndex] ?? null;
		}
		else
		{
			$transformedValue = null;
		}

		return $transformedValue;
	}
}