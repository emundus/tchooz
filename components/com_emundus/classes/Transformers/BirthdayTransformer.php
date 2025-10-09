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

class BirthdayTransformer implements FabrikTransformerInterface
{
	protected string $detailsDateFormat;

	public function __construct(string $detailsDateFormat)
	{
		$this->detailsDateFormat = $detailsDateFormat;
	}

	public function transform(mixed $value): string
	{
		$parts = $value === '' ? [] : explode(',', $value);
		foreach ($parts as $i => $v) {
			if (!empty($v)) {
				$parts[$i] = date($this->detailsDateFormat, strtotime($v));
			}
		}

		return implode(',', $parts);
	}
}