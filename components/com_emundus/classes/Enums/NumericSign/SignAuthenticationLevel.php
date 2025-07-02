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

enum SignAuthenticationLevel: string
{
	case STANDARD = 'electronic_signature';
	case AES = 'advanced_electronic_signature';
	CASE QES = 'qualified_electronic_signature';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::STANDARD => Text::_('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_STANDARD'),
			self::AES => Text::_('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_AES'),
			self::QES => Text::_('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_QES'),
		};
	}
}
