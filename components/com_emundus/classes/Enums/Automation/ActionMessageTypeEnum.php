<?php

namespace Tchooz\Enums\Automation;

use Joomla\CMS\Language\Text;

enum ActionMessageTypeEnum: string
{
	case INFO = 'info';
	case WARNING = 'warning';
	case ERROR = 'error';

	public function getLabel(): string
	{
		return match ($this) {
			self::INFO => Text::_('COM_EMUNDUS_AUTOMATION_ACTION_MESSAGE_TYPE_INFO'),
			self::WARNING => Text::_('COM_EMUNDUS_AUTOMATION_ACTION_MESSAGE_TYPE_WARNING'),
			self::ERROR => Text::_('COM_EMUNDUS_AUTOMATION_ACTION_MESSAGE_TYPE_ERROR'),
		};
	}

	public function getHtmlBadge(): string
	{
		[$bg, $text] = match ($this) {
			self::INFO => ['tw-bg-blue-500', 'tw-text-white'],
			self::WARNING => ['tw-bg-yellow-500', 'tw-text-black'],
			self::ERROR => ['tw-bg-red-600', 'tw-text-white'],
		};

		$label = $this->getLabel();

		return sprintf(
			'<div class="tw-rounded-status tw-px-3 tw-py-1 tw-font-semibold tw-text-sm tw-w-fit tw-h-7 %s %s">%s</div>',
			$bg,
			$text,
			htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
		);
	}
}