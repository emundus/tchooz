<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\NumericSign;

use Joomla\CMS\Language\Text;

enum SignStatus: string
{
	case AWAITING = 'awaiting';
	case TO_SIGN = 'to_sign';
	case REMINDER_SENT = 'reminder_sent';
	case SIGNED = 'signed';
	case DECLINED = 'declined';
	case CANCELLED = 'cancelled';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::AWAITING => Text::_('SIGN_STATUS_AWAITING'),
			self::TO_SIGN => Text::_('SIGN_STATUS_TO_SIGN'),
			self::REMINDER_SENT => Text::_('SIGN_STATUS_REMINDER_SENT'),
			self::SIGNED => Text::_('SIGN_STATUS_SIGNED'),
			self::DECLINED => Text::_('SIGN_STATUS_DECLINED'),
			self::CANCELLED => Text::_('SIGN_STATUS_CANCELLED'),
		};
	}

	public static function getChoices(): array
	{
		return [
			'Awaiting'      => self::AWAITING->value,
			'To sign'       => self::TO_SIGN->value,
			'Reminder sent' => self::REMINDER_SENT->value,
			'Signed'        => self::SIGNED->value,
			'Declined'      => self::DECLINED->value,
		];
	}

	public function getHtmlBadge(): string
	{
		[$bg, $text] = match ($this)
		{
			self::AWAITING => ['tw-bg-yellow-500', 'tw-text-black'],
			self::TO_SIGN, self::REMINDER_SENT => ['tw-bg-blue-700', 'tw-text-white'],
			self::SIGNED => ['tw-bg-green-600', 'tw-text-white'],
			self::DECLINED, self::CANCELLED => ['tw-bg-red-600', 'tw-text-white'],
		};

		$label = $this->getLabel();

		return sprintf(
			'<div class="tw-text-center tw-rounded-status tw-px-3 tw-py-1 tw-font-semibold %s %s">%s</div>',
			$bg,
			$text,
			htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
		);
	}

	public function getIconHtmlBadge(): string
	{
		[$color, $icon] = match ($this)
		{
			self::AWAITING => ['tw-text-yellow-500', 'schedule'],
			self::TO_SIGN, self::REMINDER_SENT => ['tw-text-blue-700', 'pending'],
			self::SIGNED => ['tw-text-green-600', 'check_circle'],
			self::DECLINED, self::CANCELLED => ['tw-text-red-600', 'cancel'],
		};

		return sprintf(
			'<span class="material-symbols-outlined %s">%s</span>',
			$color,
			$icon
		);
	}

	public function getClass(): string
	{
		return match ($this)
		{
			self::AWAITING => 'tw-bg-yellow-500',
			self::TO_SIGN, self::REMINDER_SENT => 'tw-bg-blue-700',
			self::SIGNED => 'tw-bg-green-600',
			self::DECLINED, self::CANCELLED => 'tw-bg-red-600',
		};
	}
}
