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

class DefaultTransformer implements FabrikTransformerInterface
{

	public function transform(mixed $value): mixed
	{
		return $value;
	}
}