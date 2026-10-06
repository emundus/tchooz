<?php

namespace Tchooz\Transformers;

use Tchooz\Interfaces\FabrikTransformerInterface;

class DateTransformer implements FabrikTransformerInterface
{
	// todo use an enum to validate date formats in the future
	protected string $detailsDateFormat;

	protected int $dateOffset;

	public function __construct(string $detailsDateFormat, int $dateOffset = 1)
	{
		$this->detailsDateFormat = $detailsDateFormat;
		$this->dateOffset        = $dateOffset;
	}

	public function transform(mixed $value, array $options = []): string
	{
		if (!class_exists('EmundusHelperDate'))
		{
			require_once JPATH_SITE . '/components/com_emundus/helpers/date.php';
		}

		$transformedValue = '';
		$dates            = is_string($value) ? explode(',', $value) : $value;

		if (!empty($dates))
		{
			$transformedValues = [];
			foreach ($dates as $index => $date)
			{
				if (!empty($date) && $this->isValidDate($date))
				{
					$transformedValues[$index] = \EmundusHelperDate::displayDate($date, $this->detailsDateFormat, $this->dateOffset);
				}
				else
				{
					$transformedValues[$index] = '';
				}
			}

			$transformedValue = implode(',', $transformedValues);
		}

		return $transformedValue;
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