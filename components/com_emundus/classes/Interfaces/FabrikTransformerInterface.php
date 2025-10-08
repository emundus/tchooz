<?php
/**
 * @package     Tchooz\Interfaces
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Interfaces;

interface FabrikTransformerInterface
{
	public function transform(mixed $value): mixed;
}