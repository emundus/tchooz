<?php

namespace Tchooz\Transformers\Mapping;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Fields\ChoiceField;
use Tchooz\Entities\Fields\FieldGroup;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Entities\Mapping\MappingRowEntity;
use Tchooz\Enums\Mapping\MappingTransformersEnum;

class MapValuesTransformer extends MappingTranformer
{
	public function __construct()
	{
		$group = new FieldGroup('mapping', Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_VALUES_PARAMETERS_GROUP_LABEL'), true);

		$parameters = [
			new ChoiceField('map_from', Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_VALUES_PARAMETER_MAP_FROM_LABEL'), [], true, false, $group),
			new StringField('map_to', Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_VALUES_PARAMETER_MAP_TO_LABEL'), true, $group),
		];

		parent::__construct(MappingTransformersEnum::MAP_VALUES, $parameters);
	}

	/**
	 * @inheritDoc
	 */
	public function transform(mixed $value): mixed
	{
		if (!empty($this->getParameterValues()))
		{
			$mapping = $this->getParameterValues()['mapping'] ?? [];

			foreach ($mapping as $row)
			{
				if (!isset($row['map_from']) || !isset($row['map_to']))
				{
					continue;
				}

				if ($value == $row['map_from'])
				{
					$value = $row['map_to'];
				}
			}
		}

		return $value;
	}
}