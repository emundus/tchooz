<?php

namespace Tchooz\Transformers;

use Tchooz\Interfaces\FabrikTransformerInterface;

class DateTransformer implements FabrikTransformerInterface
{
	// todo use an enum to validate date formats in the future
	protected string $detailsDateFormat;

	public function __construct(string $detailsDateFormat)
	{
		$this->detailsDateFormat = $detailsDateFormat;
	}

	public function transform(mixed $value, array $options = []): string
	{
		$transformedValue = '';
		$dates = explode(',', $value);

		if (!empty($dates))
		{
			$transformedValues = [];
			foreach ($dates as $index =>  $date)
			{
				if (!empty($date) && $this->isValidDate($date))
				{
					$transformedValues[$index] = date($this->detailsDateFormat, strtotime($date));
				} else {
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