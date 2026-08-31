<?php
/**
 * @package     Tchooz\Enums\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\NumericSign;

/**
 * Docaposte parameters preventing a signatory from editing their contact details before the OTP code is sent.
 * The case value is both the configuration key and the API parameter name.
 *
 * A parameter set to true locks its scope. A parameter set to false defers to the other level, so a contact
 * detail is locked when its own parameter or the global one is true.
 */
enum DocaposteContactReadOnlyParameterEnum: string
{
	case EMAIL = 'emailReadOnly';
	case PHONE = 'phoneReadOnly';
	case BOTH = 'phoneAndEmailReadOnly';
}
