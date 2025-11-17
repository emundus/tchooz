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

enum SignConnectorsEnum: string
{
	case YOUSIGN = 'yousign';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::YOUSIGN => 'Yousign'
		};
	}
}
