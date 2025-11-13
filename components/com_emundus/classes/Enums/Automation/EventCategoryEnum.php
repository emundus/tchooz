<?php

namespace Tchooz\Enums\Automation;

use Joomla\CMS\Language\Text;

enum EventCategoryEnum: string
{
	case USER = 'User';
	case FILE = 'File';
	case TAG = 'Tag';
	case STATUS = 'Status';
	case PROGRAM = 'Program';
	case CAMPAIGN = 'Campaign';
	case FORM = 'Form';
	case EVALUATION = 'Evaluation';
	case PAYMENT = 'Payment';
	case EMAIL = 'Email';
	case LETTERS = 'Files';
	case JOOMLA = 'Joomla';

	public function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_EVENT_CATEGORY_' . strtoupper($this->name));
	}

	public function getIcon(): string
	{
		// use material symbol icons
		return match ($this)
		{
			self::USER => 'group',
			self::FILE => 'inventory_2',
			self::TAG => 'sell',
			self::STATUS => 'label',
			self::PROGRAM => 'computer',
			self::CAMPAIGN => 'layers',
			self::FORM => 'content_paste',
			self::EVALUATION => 'grading',
			self::PAYMENT => 'attach_money',
			self::EMAIL => 'mail',
			self::LETTERS => 'drafts',
			self::JOOMLA => 'extension',
		};
	}
}
