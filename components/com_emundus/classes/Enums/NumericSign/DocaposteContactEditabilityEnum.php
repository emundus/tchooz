<?php
/**
 * @package     Tchooz\Enums\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\NumericSign;

use Joomla\CMS\Language\Text;

/**
 * How much of their contact details a signatory may edit before the OTP code is sent.
 */
enum DocaposteContactEditabilityEnum: string
{
	case ALL = 'all';
	case NONE = 'none';
	case CUSTOM = 'custom';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::ALL => Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONTACT_EDITABILITY_ALL'),
			self::NONE => Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONTACT_EDITABILITY_NONE'),
			self::CUSTOM => Text::_('COM_EMUNDUS_INTEGRATIONS_DOCAPOSTE_CONTACT_EDITABILITY_CUSTOM'),
		};
	}

	/**
	 * Docaposte read only parameters to send, keyed by parameter name.
	 * The per detail parameters are only meaningful on CUSTOM, the other cases decide every parameter at once.
	 *
	 * @return array<string, bool>
	 */
	public function getReadOnlyParameters(bool $emailReadOnly, bool $phoneReadOnly): array
	{
		return match ($this)
		{
			self::ALL => [
				DocaposteContactReadOnlyParameterEnum::BOTH->value => false,
				DocaposteContactReadOnlyParameterEnum::EMAIL->value => false,
				DocaposteContactReadOnlyParameterEnum::PHONE->value => false,
			],
			self::NONE => [
				DocaposteContactReadOnlyParameterEnum::BOTH->value => true,
				DocaposteContactReadOnlyParameterEnum::EMAIL->value => true,
				DocaposteContactReadOnlyParameterEnum::PHONE->value => true,
			],
			self::CUSTOM => [
				DocaposteContactReadOnlyParameterEnum::BOTH->value => false,
				DocaposteContactReadOnlyParameterEnum::EMAIL->value => $emailReadOnly,
				DocaposteContactReadOnlyParameterEnum::PHONE->value => $phoneReadOnly,
			],
		};
	}
}
