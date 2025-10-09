<?php
/**
 * @package     Tchooz\Transformers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Transformers;

use Tchooz\Interfaces\FabrikTransformerInterface;

class PhoneTransformer implements FabrikTransformerInterface
{
	public function transform(mixed $value): string
	{
		$v = (string) $value;
		$v = trim($v);

		// keep same behaviour: remove first two chars
		return strlen($v) > 2 ? substr($v, 2) : $v;
	}
}