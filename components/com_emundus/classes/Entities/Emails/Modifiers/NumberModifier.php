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

class NumberModifier implements TagModifierInterface
{
	private array $params = [];

	public function getName(): string
	{
		return 'NUMBER';
	}

	public function getLabel(): string
	{
		return 'Number';
	}

	public function transform(string $value, array $params = []): string
	{
		if (!is_numeric($value)) {
			$value = \EmundusHelperFabrik::extractNumericValue($value);
		}

		$decimalCount = $params[0] ?? 2;
		$decimalSeparator = $params[1] ?? ',';
		$thousandsSeparator = $params[2] ?? '';
		
		return $this->formatAmount($value, (int) $decimalCount, $decimalSeparator, $thousandsSeparator);
	}

	private function formatAmount(float $value, int $decimalCount, string $decimalSeparator, string $thousandsSeparator): string
	{
		if (fmod($value, 1) === 0.0) {
			return number_format($value, 0, $decimalSeparator, $thousandsSeparator);
		}

		return number_format($value, $decimalCount, $decimalSeparator, $thousandsSeparator);
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	public function getParams(): array
	{
		return $this->params;
	}
}