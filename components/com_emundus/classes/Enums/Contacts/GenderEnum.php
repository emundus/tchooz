<?php
/**
 * @package     Tchooz\Enums\Contacts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Contacts;

use Joomla\CMS\Language\Text;

enum GenderEnum: string
{
	case MAN = 'man';
	case WOMAN = 'woman';
	case OTHER = 'other';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::MAN => Text::_('GENDER_MAN'),
			self::WOMAN => Text::_('GENDER_WOMAN'),
			self::OTHER => Text::_('GENDER_OTHER'),
		};
	}

	public function getIcon(): string
	{
		return match ($this)
		{
			self::MAN => 'male',
			self::WOMAN => 'female',
			self::OTHER => 'agender',
		};
	}
}
