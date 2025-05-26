<?php

namespace Tchooz\Entities\Payment;

use Joomla\CMS\Language\Text;

enum TransactionStatus: string {
	case INITIATED = 'initiated';
	case WAITING = 'waiting';
	case CANCELLED = 'cancelled';
	case CONFIRMED = 'confirmed';
	case FAILED = 'failed';

	public function getLabel(): string
	{
		return match ($this) {
			self::INITIATED => Text::_('COM_EMUNDUS_PAYMENT_TRANSACTION_STATUS_INITIATED'),
			self::WAITING   => Text::_('COM_EMUNDUS_PAYMENT_TRANSACTION_STATUS_WAITING'),
			self::CANCELLED => Text::_('COM_EMUNDUS_PAYMENT_TRANSACTION_STATUS_CANCELLED'),
			self::CONFIRMED => Text::_('COM_EMUNDUS_PAYMENT_TRANSACTION_STATUS_CONFIRMED'),
			self::FAILED    => Text::_('COM_EMUNDUS_PAYMENT_TRANSACTION_STATUS_FAILED'),
		};
	}

	public function getHtmlBadge(): string
	{
		[$bg, $text] = match ($this) {
			self::INITIATED => ['tw-bg-yellow-500', 'tw-text-black'],
			self::WAITING   => ['tw-bg-blue-700', 'tw-text-white'],
			self::CANCELLED, self::FAILED => ['tw-bg-red-600', 'tw-text-white'],
			self::CONFIRMED => ['tw-bg-green-600', 'tw-text-white'],
		};

		$label = $this->getLabel();

		return sprintf(
			'<div class="tw-rounded-status tw-px-3 tw-py-1 tw-font-semibold tw-text-sm tw-w-fit %s %s">%s</div>',
			$bg,
			$text,
			htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
		);
	}
}
