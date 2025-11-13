<?php
/**
 * @package     Tchooz\Entities\List
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\List;

class AdditionalColumnTag
{
	public function __construct(
		public string $key,
		public string $value,
		public string $title = '',
		public string $classes = '',
	) {
	}
}