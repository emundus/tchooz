<?php
/**
 * @package     Tchooz\Attributes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Attributes;

use Tchooz\Enums\AccessLevelEnum;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AccessAttribute
{
	public function __construct(
		public ?AccessLevelEnum $accessLevel = null,
		public array $actions = []
	) {}
}