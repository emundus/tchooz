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

		if (is_string($value))
		{
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
		}
		else if (is_array($value))
		{
			// value 1 is day, value 2 is month, value 3 is year
			$day = $value[0] ?? '';
			$month = $value[1] ?? '';
			$year = $value[2] ?? '';

			if (!empty($day) && !empty($month) && !empty($year))
			{
				$dateString = sprintf('%04d-%02d-%02d', $year, $month, $day);
				if ($this->isValidDate($dateString))
				{
					$transformedParts[0] = date($this->detailsDateFormat, strtotime($dateString));
				}
				else
				{
					$transformedParts[0] = '';
				}
			}
			else
			{
				$transformedParts[0] = '';
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

	public function setDetailsDateFormat(string $detailsDateFormat): self
	{
		$this->detailsDateFormat = $detailsDateFormat;

		return $this;
	}
}