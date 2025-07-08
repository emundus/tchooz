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

class UppercaseModifier implements TagModifierInterface
{

	public function getName(): string
	{
		return 'UPPERCASE';
	}

	public function getLabel(): string
	{
		return 'Uppercase';
	}

	public function transform(string $value): string
	{
		return strtoupper($value);
	}
}