<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\NumericSign;

enum DocaposteEmailTypeEnum: string
{
	case INITIATE_TRANSACTION = 'initiate_transaction';
	case SEND_REMINDER = 'send_reminder';
	case CANCEL_TRANSACTION = 'cancel_transaction';
	case COMPLETE_TRANSACTION = 'complete_transaction';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::INITIATE_TRANSACTION => 'Initiate transaction',
			self::SEND_REMINDER => 'Send reminder',
			self::CANCEL_TRANSACTION => 'Cancel transaction',
			self::COMPLETE_TRANSACTION => 'Complete transaction',
		};
	}
}