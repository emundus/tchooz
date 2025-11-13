<?php

namespace Tchooz\Enums\Task;

use Joomla\CMS\Language\Text;

enum TaskStatusEnum: string
{
	case PENDING = 'pending';
	case IN_PROGRESS = 'in_progress';
	case COMPLETED = 'completed';
	case FAILED = 'failed';

	public function getIcon(): string
	{
		return match($this) {
			self::PENDING => 'schedule',
			self::IN_PROGRESS => 'clock_loader_40',
			self::COMPLETED => 'check_circle',
			self::FAILED => 'running_with_errors',
		};
	}

	public function getLabel(): string
	{
		return match($this) {
			self::PENDING => Text::_('COM_EMUNDUS_TASK_STATUS_PENDING'),
			self::IN_PROGRESS => Text::_('COM_EMUNDUS_TASK_STATUS_IN_PROGRESS'),
			self::COMPLETED => Text::_('COM_EMUNDUS_TASK_STATUS_COMPLETED'),
			self::FAILED => Text::_('COM_EMUNDUS_TASK_STATUS_FAILED'),
		};
	}

	public function getClasses(): string
	{
		return match($this) {
			self::FAILED => 'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-bg-red-700 tw-text-white material-symbols-outlined',
			self::COMPLETED => 'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-bg-green-800 tw-text-white material-symbols-outlined',
			self::IN_PROGRESS => 'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-bg-blue-700  tw-text-white material-symbols-outlined',
			self::PENDING => 'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-bg-yellow-700  tw-text-white material-symbols-outlined',
		};
	}
}
