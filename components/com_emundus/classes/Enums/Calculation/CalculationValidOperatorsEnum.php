<?php

namespace Tchooz\Enums\Calculation;

use Joomla\CMS\Language\Text;

enum CalculationValidOperatorsEnum: string
{
	case ADDITION = '+';
	case SUBTRACTION = '-';
	case MULTIPLICATION = '*';
	case DIVISION = '/';
	case OPENING_BRACKET = '(';
	case CLOSING_BRACKET = ')';

	public function getLabel(): string
	{
		return match($this) {
			self::ADDITION => Text::_('COM_EMUNDUS_CALCULATION_OPERATOR_ADDITION'),
			self::SUBTRACTION => Text::_('COM_EMUNDUS_CALCULATION_OPERATOR_SUBTRACTION'),
			self::MULTIPLICATION => Text::_('COM_EMUNDUS_CALCULATION_OPERATOR_MULTIPLICATION'),
			self::DIVISION => Text::_('COM_EMUNDUS_CALCULATION_OPERATOR_DIVISION'),
			self::OPENING_BRACKET => Text::_('COM_EMUNDUS_CALCULATION_OPERATOR_OPENING_BRACKET'),
			self::CLOSING_BRACKET => Text::_('COM_EMUNDUS_CALCULATION_OPERATOR_CLOSING_BRACKET'),
		};
	}
}