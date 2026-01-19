<?php

namespace Tchooz\Enums\Mapping;

use Joomla\CMS\Language\Text;

enum MappingTransformersEnum: string
{
	case CAPITALIZE = 'capitalize';
	case LOWERCASE = 'lowercase';
	case UPPERCASE = 'uppercase';
	case DATE_FORMAT = 'date_format';
	case MAP_VALUES = 'map_values';
	case MAP_DATABASEJOIN_ELEMENT_VALUES = 'map_databasejoin_element_values';
	case USE_FORMATTED_VALUE = 'use_formatted_value';

	public function getLabel(): string
	{
		return match ($this) {
			self::CAPITALIZE => Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_CAPITALIZE'),
			self::LOWERCASE => Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_LOWERCASE'),
			self::UPPERCASE => Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_UPPERCASE'),
			self::DATE_FORMAT => Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_DATE_FORMAT'),
			self::MAP_VALUES => Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_VALUES'),
			self::MAP_DATABASEJOIN_ELEMENT_VALUES => Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_MAP_DATABASEJOIN_ELEMENT_VALUES'),
			self::USE_FORMATTED_VALUE => Text::_('COM_EMUNDUS_MAPPING_TRANSFORMER_USE_FORMATTED_VALUE'),
		};
	}
}