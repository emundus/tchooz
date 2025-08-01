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

// TODO: Add parameters to manage decimals, thousands separator, etc.
class NumberModifier implements TagModifierInterface
{

	public function getName(): string
	{
		return 'NUMBER';
	}

	public function getLabel(): string
	{
		return 'Number';
	}

	public function transform(string $value): string
	{
		if (!is_numeric($value)) {
			$value = \EmundusHelperFabrik::extractNumericValue($value);
		}
		
		return $this->formatAmount($value);
	}

	private function formatAmount(float $value): string
	{
		if (fmod($value, 1) === 0.0) {
			return number_format($value, 0, ',', '.');
		}

		return number_format($value, 2, ',', '.');
	}
}