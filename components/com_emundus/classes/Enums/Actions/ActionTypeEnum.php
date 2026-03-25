<?php
/**
 * @package     Tchooz\Enums\Actions
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Actions;

use Joomla\CMS\Language\Text;

enum ActionTypeEnum: string
{
	case FILE = 'file';
	case PLATFORM = 'platform';
	case USERS = 'users';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::FILE => Text::_('COM_EMUNDUS_ACTION_TYPE_FILE'),
			self::PLATFORM => Text::_('COM_EMUNDUS_ACTION_TYPE_PLATFORM'),
			self::USERS => Text::_('COM_EMUNDUS_ACTION_TYPE_USERS'),
		};
	}
}
