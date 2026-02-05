<?php
/**
 * @package     Tchooz\Enums\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\ApplicationFile;

use Joomla\CMS\Language\Text;

// TODO: Move to database table to allow custom states
enum ChoicesStateEnum: int
{
	case DRAFT = 0;
	case ACCEPTED = 1;
	case REJECTED = 2;
	case CONFIRMED = 3;
	case WAITING = 4;
	case REJECTED_FOR_APPLICATION = 5;
	case WAITING_DECISION = 6;
	case WAITING_JURY = 7;
	case WAITING_LIST = 8;
	case CALLED = 9;
	case BOOKING_SELECTED = 10;
	case CONFIRMED_DEF = 11;
	case CONFIRMED_REJECTED = 12;
	case RESIGNATION = 13;
	case CHOICE_NOT_OPEN = 14;
	case CHOICE_COMPLETED = 15;
	case DISABLED = 99;

	public function getLabel(): string
	{
		return match ($this)
		{
			self::DRAFT => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_DRAFT'),
			self::WAITING => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_WAITING'),
			self::ACCEPTED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_ACCEPTED'),
			self::REJECTED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_REJECTED'),
			self::CONFIRMED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CONFIRMED'),
			self::REJECTED_FOR_APPLICATION => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_REJECTEDFORAPPLICATION'),
			self::WAITING_DECISION => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_WAITING_DECISION'),
			self::WAITING_JURY => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_WAITING_JURY'),
			self::WAITING_LIST => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_WAITING_LIST'),
			self::CALLED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CALLED'),
			self::BOOKING_SELECTED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_BOOKING_SELECTED'),
			self::CONFIRMED_DEF => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CONFIRMED_DEF'),
			self::CONFIRMED_REJECTED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CONFIRMED_REJECTED'),
			self::RESIGNATION => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_RESIGNATION'),
			self::CHOICE_NOT_OPEN => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CHOICE_NOT_OPEN'),
			self::CHOICE_COMPLETED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CHOICE_COMPLETED'),
			self::DISABLED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_DISABLED'),
		};
	}

	public function getHtmlBadge(): string
	{
		[$bg, $text] = match ($this)
		{
			self::WAITING, self::WAITING_DECISION, self::WAITING_JURY, self::WAITING_LIST => ['tw-bg-yellow-500', 'tw-text-black'],
			self::DRAFT, self::CALLED, self::BOOKING_SELECTED => ['tw-bg-blue-700', 'tw-text-white'],
			self::ACCEPTED, self::CONFIRMED, self::CONFIRMED_DEF => ['tw-bg-green-600', 'tw-text-white'],
			self::REJECTED, self::REJECTED_FOR_APPLICATION, self::RESIGNATION, self::CONFIRMED_REJECTED, self::CHOICE_NOT_OPEN, self::CHOICE_COMPLETED => ['tw-bg-red-600', 'tw-text-white'],
			self::DISABLED => ['tw-bg-neutral-500', 'tw-text-white'],
		};

		$label = $this->getLabel();

		return sprintf(
			'<div class="tw-text-center tw-rounded-status tw-px-3 tw-py-1 tw-font-semibold %s %s">%s</div>',
			$bg,
			$text,
			htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
		);
	}

	public static function isValidState(string $value): ChoicesStateEnum|null
	{
		return match (strtolower($value))
		{
			'draft' => self::DRAFT,
			'waiting' => self::WAITING,
			'accepted' => self::ACCEPTED,
			'rejected' => self::REJECTED,
			'confirmed' => self::CONFIRMED,
			'rejected_for_application' => self::REJECTED_FOR_APPLICATION,
			'waiting_decision' => self::WAITING_DECISION,
			'waiting_jury' => self::WAITING_JURY,
			'waiting_list' => self::WAITING_LIST,
			'called' => self::CALLED,
			'booking_selected' => self::BOOKING_SELECTED,
			'confirmed_def' => self::CONFIRMED_DEF,
			'confirmed_rejected' => self::CONFIRMED_REJECTED,
			'resignation' => self::RESIGNATION,
			'choice_not_open' => self::CHOICE_NOT_OPEN,
			'choice_completed' => self::CHOICE_COMPLETED,
			'disabled' => self::DISABLED,
			default => null,
		};
	}
}
