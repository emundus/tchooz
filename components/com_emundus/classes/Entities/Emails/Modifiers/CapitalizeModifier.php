<?php
/**
 * @package     Tchooz\Entities\Emails\Modifiers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails\Modifiers;

use Tchooz\Interfaces\TagModifierInterface;

class CapitalizeModifier implements TagModifierInterface
{

	public function getName(): string
	{
		return 'CAPITALIZE';
	}

	public function getLabel(): string
	{
		return 'Capitalize';
	}

	public function transform(string $value): string
	{
		return ucwords(strtolower($value));
	}
}