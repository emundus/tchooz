<?php

namespace Tchooz\Services\Automation\Condition;

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\StringField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Providers\DateProvider;

/**
 * Exposes the system date as a data source, so a mapping can send the current date (and its usual
 * derivatives) without the target object hardcoding it.
 *
 * Values are computed on resolution, never cached: the registry serializes resolver instances, so a
 * date computed in the constructor would freeze on the day the cache was built.
 *
 * Local values follow the server timezone, like the rest of eMundus. The ISO variant is explicitly
 * UTC, as the format implies, and suits APIs expecting `2024-05-30T12:00:00Z` (OData, REST).
 * Reformat with the "date format" transformation when a connector expects something else.
 */
class SystemDateConditionResolver implements ConditionTargetResolverInterface
{
	private DateProvider $dateProvider;

	public function __construct(?DateProvider $dateProvider = null)
	{
		$this->dateProvider = $dateProvider ?? new DateProvider();
	}

	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::SYSTEMDATE->value;
	}

	public static function getAllowedActionTargetTypes(): array
	{
		return [];
	}

	public function getAvailableFields(array $contextFilters): array
	{
		return [
			new StringField('current_date', Text::_('COM_EMUNDUS_SYSTEM_DATE_CURRENT_DATE'), false),
			new StringField('current_datetime', Text::_('COM_EMUNDUS_SYSTEM_DATE_CURRENT_DATETIME'), false),
			new StringField('current_datetime_iso', Text::_('COM_EMUNDUS_SYSTEM_DATE_CURRENT_DATETIME_ISO'), false),
			new StringField('current_year', Text::_('COM_EMUNDUS_SYSTEM_DATE_CURRENT_YEAR'), false),
			new StringField('current_timestamp', Text::_('COM_EMUNDUS_SYSTEM_DATE_CURRENT_TIMESTAMP'), false),
		];
	}

	public function resolveValue(ActionTargetEntity $context, string $fieldName, ValueFormatEnum $format = ValueFormatEnum::RAW): mixed
	{
		return match ($fieldName)
		{
			'current_date'         => date('Y-m-d'),
			'current_datetime'     => $this->dateProvider->getCurrentDate(),
			'current_datetime_iso' => gmdate('Y-m-d\TH:i:s\Z'),
			'current_year'         => $this->dateProvider->getCurrentYear(),
			'current_timestamp'    => time(),
			default                => null,
		};
	}

	public function getColumnsForField(string $field): array
	{
		return [];
	}

	public function getJoins(string $field): array
	{
		return [];
	}

	public function getJoinsToTable(TargetTypeEnum $targetType): array
	{
		return [];
	}

	public function searchable(): bool
	{
		return false;
	}
}
