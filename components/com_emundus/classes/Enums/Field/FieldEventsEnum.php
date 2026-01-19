<?php

namespace Tchooz\Enums\Field;

use Joomla\CMS\Language\Text;

enum FieldEventsEnum: string
{
	case ON_CHANGE = 'onChange';

	public function getLabel()
	{
		return match($this) {
			self::ON_CHANGE => Text::_('COM_EMUNDUS_FIELD_EVENT_ON_CHANGE'),
		};
	}
}
