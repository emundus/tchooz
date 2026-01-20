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

enum StatusEnum: int
{
	case PUBLISHED = 1;
	case UNPUBLISHED = 0;
	case ARCHIVED = 2;
	case TRASHED = -2;

	public function getLabel(): string
	{
		return match ($this)
		{
			self::PUBLISHED => Text::_('COM_EMUNDUS_STATUS_PUBLISHED'),
			self::UNPUBLISHED => Text::_('COM_EMUNDUS_STATUS_UNPUBLISHED'),
			self::ARCHIVED => Text::_('COM_EMUNDUS_STATUS_ARCHIVED'),
			self::TRASHED => Text::_('COM_EMUNDUS_STATUS_TRASHED'),
		};
	}
}
