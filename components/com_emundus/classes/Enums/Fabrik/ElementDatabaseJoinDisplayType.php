<?php

namespace Tchooz\Enums\Fabrik;

enum ElementDatabaseJoinDisplayType: string
{
	case MULTILIST = 'multilist';
	case CHECKBOX = 'checkbox';
	case RADIO = 'radio';
	case DROPDOWN = 'dropdown';

	public static function isMultiSelect(string $type): bool
	{
		$type = ElementDatabaseJoinDisplayType::from($type);

		return in_array($type, self::multiselectTypes());
	}

	public static function multiselectTypes(): array
	{
		return [self::MULTILIST, self::CHECKBOX];
	}
}