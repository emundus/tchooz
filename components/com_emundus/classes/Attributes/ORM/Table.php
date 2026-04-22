<?php
/**
 * @package     Tchooz\Attributes\ORM
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Attributes\ORM;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Table
{
	public function __construct(
		public readonly string|null $name = null
	) {}
}