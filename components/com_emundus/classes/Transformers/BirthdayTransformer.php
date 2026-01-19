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

	public function transform(mixed $value, array $options = []): string
	{
		$transformedParts = [];

		$parts = explode(',', $value);
		foreach ($parts as $i => $v)
		{
			if (!empty($v) && $this->isValidDate($v))
			{
				$transformedParts[$i] = date($this->detailsDateFormat, strtotime($v));
			}
			else
			{
				$transformedParts[$i] = '';
			}
		}

		return implode(',', $transformedParts);
	}

	public function isValidDate(string $date): bool
	{
		$valid = true;

		if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00' || strtotime($date) === false)
		{
			$valid = false;
		}

		return $valid;
	}
}