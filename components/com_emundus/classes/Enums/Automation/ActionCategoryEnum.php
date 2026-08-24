<?php

namespace Tchooz\Enums\Automation;

use Joomla\CMS\Language\Text;

enum ActionCategoryEnum: string
{
	case CART = 'cart';
	case USER = 'user';
	case FILE = 'file';
	case SIGN = 'sign';
	case CONTACT = 'contact';

	public function getLabel(): string
	{
		return match($this) {
			self::CART => Text::_('COM_EMUNDUS_CONDITION_ACTION_CATEGORY_CART'),
			self::USER => Text::_('COM_EMUNDUS_CONDITION_ACTION_CATEGORY_USER'),
			self::FILE => Text::_('COM_EMUNDUS_CONDITION_ACTION_CATEGORY_FILE'),
			self::SIGN => Text::_('COM_EMUNDUS_CONDITION_ACTION_CATEGORY_SIGN'),
			self::CONTACT => Text::_('COM_EMUNDUS_CONDITION_ACTION_CATEGORY_CONTACT'),
		};
	}

	public function getIcon(): string
	{
		return match($this) {
			self::CART => 'attach_money',
			self::USER => 'person',
			self::FILE => 'inventory_2',
			self::SIGN => 'signature',
			self::CONTACT => 'sensor_occupied',
		};
	}
}
