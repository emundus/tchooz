<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums;

use Joomla\CMS\Language\Text;

enum ApiStatusEnum: string
{
	case PENDING = 'pending';
	case PROCESSING = 'processing';
	case COMPLETED = 'completed';
	case FAILED = 'failed';
	case CANCELLED = 'cancelled';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::PENDING => Text::_('API_STATUS_PENDING'),
			self::PROCESSING => Text::_('API_STATUS_PROCESSING'),
			self::COMPLETED => Text::_('API_STATUS_COMPLETED'),
			self::FAILED => Text::_('API_STATUS_FAILED'),
			self::CANCELLED => Text::_('API_STATUS_CANCELLED'),
		};
	}
}
