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

enum ChoicesStateEnum: int
{
	case DRAFT = 0;
	case ACCEPTED = 1;
	case REJECTED = 2;
	case CONFIRMED = 3;
	case WAITING = 4;

	public function getLabel(): string
	{
		return match($this)
		{
			self::DRAFT => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_DRAFT'),
			self::WAITING => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_WAITING'),
			self::ACCEPTED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_ACCEPTED'),
			self::REJECTED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_REJECTED'),
			self::CONFIRMED => Text::_('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CONFIRMED'),
		};
	}

	public function getHtmlBadge(): string
	{
		[$bg, $text] = match ($this)
		{
			self::WAITING => ['tw-bg-yellow-500', 'tw-text-black'],
			self::DRAFT => ['tw-bg-blue-700', 'tw-text-white'],
			self::ACCEPTED, self::CONFIRMED => ['tw-bg-green-600', 'tw-text-white'],
			self::REJECTED => ['tw-bg-red-600', 'tw-text-white'],
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
			default => null,
		};
	}
}
