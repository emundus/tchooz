<?php

namespace Tchooz\Enums\Task;

use Joomla\CMS\Language\Text;

enum TaskPriorityEnum: int
{
	case LOW = 1;
	case MEDIUM = 2;
	case HIGH = 3;

	public function getLabel(): string
	{
		return match ($this) {
			self::LOW => Text::_('COM_EMUNDUS_TASK_PRIORITY_LOW'),
			self::MEDIUM => Text::_('COM_EMUNDUS_TASK_PRIORITY_MEDIUM'),
			self::HIGH => Text::_('COM_EMUNDUS_TASK_PRIORITY_HIGH'),
		};
	}

	public function getHtmlBadge(): string
	{
		return match ($this) {
			self::LOW => '<span class="tw-bg-green-500 tw-text-green-800 tw-px-2 tw-py-1 tw-rounded tw-text-sm">' . $this->getLabel() . '</span>',
			self::MEDIUM => '<span class="tw-bg-yellow-500 tw-text-yellow-800 tw-px-2 tw-py-1 tw-rounded tw-text-sm">' . $this->getLabel() . '</span>',
			self::HIGH => '<span class="tw-bg-red-500 tw-text-white tw-px-2 tw-py-1 tw-rounded tw-text-sm">' . $this->getLabel() . '</span>',
		};
	}

	public function getIcon(): string
	{
		return match ($this) {
			self::LOW => 'arrow_downward',
			self::MEDIUM => 'drag_handle',
			self::HIGH => 'arrow_upward',
		};
	}
}
