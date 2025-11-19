<?php

namespace Tchooz\Enums\Export;

enum ExportModeEnum: string
{
	case GROUP_CONCAT_DISTINCT = 'GROUP_CONCAT_DISTINCT';
	case GROUP_CONCAT = 'GROUP_CONCAT';
	case LEFT_JOIN = 'LEFT_JOIN';

	public function getLabel(): string
	{
		return match($this) {
			ExportModeEnum::GROUP_CONCAT_DISTINCT => 'COM_EMUNDUS_EXPORT_MODE_GROUP_CONCAT_DISTINCT',
			ExportModeEnum::GROUP_CONCAT => 'COM_EMUNDUS_EXPORT_MODE_GROUP_CONCAT',
			ExportModeEnum::LEFT_JOIN => 'COM_EMUNDUS_EXPORT_MODE_LEFT_JOIN',
		};
	}

	public static function getFromId(int $id): ExportModeEnum
	{
		return match($id) {
			0 => ExportModeEnum::GROUP_CONCAT_DISTINCT,
			2 => ExportModeEnum::GROUP_CONCAT,
			1 => ExportModeEnum::LEFT_JOIN,
			default => throw new \InvalidArgumentException("Invalid ExportModeEnum id: $id"),
		};
	}
}
