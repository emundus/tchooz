<?php
/**
 * @package     Tchooz\Enums\Reference
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Reference;

use Joomla\CMS\Language\Text;

enum ResetTypeEnum: string
{
	case NEVER = 'never';
	case YEARLY = 'yearly';
	case CAMPAIGN = 'campaign';
	case PROGRAM = 'program';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::NEVER => Text::_('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_NEVER'),
			self::YEARLY => Text::_('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_YEARLY'),
			self::CAMPAIGN => Text::_('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_CAMPAIGN'),
			self::PROGRAM => Text::_('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_PROGRAM'),
		};
	}
}
