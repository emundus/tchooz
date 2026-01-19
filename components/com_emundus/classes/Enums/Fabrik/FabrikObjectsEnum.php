<?php
/**
 * @package     Tchooz\Enums\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Fabrik;

enum FabrikObjectsEnum: string
{
	case FORM = 'form';
	case GROUP = 'group';
	case LIST = 'list';
	case ELEMENT = 'element';
	case CRON = 'cron';
	case JOIN = 'join';
	case JS = 'js';

	public function getTable(): string
	{
		return match ($this)
		{
			self::FORM => '#__fabrik_forms',
			self::GROUP => '#__fabrik_groups',
			self::LIST => '#__fabrik_lists',
			self::ELEMENT => '#__fabrik_elements',
			self::CRON => '#__fabrik_cron',
			self::JOIN => '#__fabrik_joins',
			self::JS => '#__fabrik_jsactions',
		};
	}
}
