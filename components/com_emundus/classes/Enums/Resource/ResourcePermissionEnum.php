<?php
/**
 * @package     Tchooz\Enums\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Resource;

use Joomla\CMS\Language\Text;

enum ResourcePermissionEnum: string
{
	case VIEW   = 'view';
	case EDIT   = 'edit';
	case MANAGE = 'manage';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::VIEW   => Text::_('COM_EMUNDUS_RESOURCE_PERMISSION_VIEW'),
			self::EDIT   => Text::_('COM_EMUNDUS_RESOURCE_PERMISSION_EDIT'),
			self::MANAGE => Text::_('COM_EMUNDUS_RESOURCE_PERMISSION_MANAGE'),
		};
	}
}
