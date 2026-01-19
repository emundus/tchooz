<?php

namespace Tchooz\Enums\Field;

use Joomla\CMS\Language\Text;

enum FieldWatcherActionEnum: string
{
	case RELOAD = 'reload';

	public function getLabel(): string
	{
		return match($this) {
			self::RELOAD => Text::_('COM_EMUNDUS_FIELD_WATCHER_ACTION_RELOAD'),
		};
	}
}
