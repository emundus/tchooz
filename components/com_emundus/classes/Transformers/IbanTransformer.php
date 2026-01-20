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

class IbanTransformer implements FabrikTransformerInterface
{
	public function transform(mixed $value, array $options = []): string
	{
		return \EmundusHelperFabrik::decryptDatas($value);
	}
}