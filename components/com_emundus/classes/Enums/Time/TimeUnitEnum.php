<?php

namespace Tchooz\Enums\Time;

use Joomla\CMS\Language\Text;

enum TimeUnitEnum: string
{
	case YEARS = 'years';
	case MONTHS = 'months';
	case WEEKS = 'weeks';
	case DAYS = 'days';
	case HOURS = 'hours';
	case MINUTES = 'minutes';
	case SECONDS = 'seconds';

	public function getLabel(): string
	{
		return match($this) {
			self::YEARS => Text::_('COM_EMUNDUS_TIME_UNIT_YEARS'),
			self::MONTHS => Text::_('COM_EMUNDUS_TIME_UNIT_MONTHS'),
			self::WEEKS => Text::_('COM_EMUNDUS_TIME_UNIT_WEEKS'),
			self::DAYS => Text::_('COM_EMUNDUS_TIME_UNIT_DAYS'),
			self::HOURS => Text::_('COM_EMUNDUS_TIME_UNIT_HOURS'),
			self::MINUTES => Text::_('COM_EMUNDUS_TIME_UNIT_MINUTES'),
			self::SECONDS => Text::_('COM_EMUNDUS_TIME_UNIT_SECONDS'),
		};
	}

	public function getCalulationSyntax(): string
	{
		return match($this) {
			self::YEARS => 'Y',
			self::MONTHS => 'M',
			self::WEEKS => 'W',
			self::DAYS => 'D',
			self::HOURS => 'H',
			self::MINUTES => 'I',
			self::SECONDS => 'S',
		};
	}
}
