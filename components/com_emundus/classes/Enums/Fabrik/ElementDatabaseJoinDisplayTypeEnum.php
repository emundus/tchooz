<?php

namespace Tchooz\Enums\Fabrik;

enum ElementDatabaseJoinDisplayTypeEnum: string
{
	case MULTILIST = 'multilist';
	case CHECKBOX = 'checkbox';
	case RADIO = 'radio';
	case DROPDOWN = 'dropdown';

	public static function isMultiSelect(string $type): bool
	{
		$type = ElementDatabaseJoinDisplayTypeEnum::from($type);

		return in_array($type, self::multiselectTypes());
	}

	public static function multiselectTypes(): array
	{
		return [self::MULTILIST, self::CHECKBOX];
	}
}