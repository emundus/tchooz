<?php
/**
 * @package     Tchooz\Transformers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Transformers;

use Joomla\CMS\Language\Text;
use Tchooz\Interfaces\FabrikTransformerInterface;

class YesNoTransformer implements FabrikTransformerInterface
{
	public function transform(mixed $value): string
	{
		return $value == '1' ? Text::_('JYES') : Text::_('JNO');
	}
}