<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\Utilities\ArrayHelper;

/**
 * Plugin element to store the user's IP address
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.ip
 * @since       3.0
 */
class PlgFabrik_ElementAverage extends PlgFabrik_Element
{
	// Bound by the DECIMAL(10,2) column
	public const MAX_DECIMALS = 2;

	/**
	 * Get database field description
	 *
	 * @return  string  Db field type
	 */
	public function getFieldDescription(): string
	{
		return "DECIMAL(10,2) DEFAULT '0.00'";
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0): string
	{
		$name              = $this->getHTMLName($repeatCounter);
		$layout            = $this->getLayout('form');
		$displayData       = new stdClass;
		$displayData->name = $name;
		$displayData->type = 'text';
		$displayData->id   = $this->getHTMLId($repeatCounter);

		$decimals           = $this->getDecimals();
		$value              = $this->getValue($data, $repeatCounter);
		$displayData->value = number_format((float) $value, $decimals, '.', '');
		$displayData->step  = 10 ** -$decimals;

		return $layout->render($displayData);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter): array
	{
		$params = $this->getParams();
		$id     = $this->getHTMLId($repeatCounter);
		$opts   = $this->getElementJSOptions($repeatCounter);

		$opts->average_over = (int) $params->get('average_over', 20);
		$opts->decimals     = $this->getDecimals();
		$opts->rounding     = $this->getRounding();
		$opts->elements_to_observe = $this->getObservedFields();

		return array('FbAverage', $id, $opts);
	}

	private function getDecimals(): int
	{
		return max(0, min(self::MAX_DECIMALS, (int) $this->getParams()->get('decimals', self::MAX_DECIMALS)));
	}

	private function getRounding(): string
	{
		return (string) $this->getParams()->get('rounding', 'round');
	}

	private function getObservedFields(): array
	{
		$fields = [];

		$params                    = $this->getParams();
		$average_multiple_elements = $params->get('average_multiple_elements');
		$average_multiple_elements = json_decode($average_multiple_elements);

		foreach ($average_multiple_elements->average_multiple_element as $key => $element)
		{
			$repeat = false;

			if (preg_match('/_repeat___(.*)_[0-9]+$/', $element))
			{
				// remove last digit
				$element = preg_replace('/_[0-9]+$/', '', $element);
				$repeat  = true;
			}

			$fields[] = [
				'element' => $element,
				'repeat'  => $repeat,
				'weight'  => $average_multiple_elements->average_multiple_weight[$key] ?? 1,
				'max'     => $average_multiple_elements->average_multiple_max[$key] ?? 20
			];
		}

		return $fields;
	}

	public function onStoreRow(&$data, $repeatCounter = 0): bool
	{
		$element = $this->getElement();

		if (!$element->published)
		{
			return false;
		}

		if ($this->encryptMe())
		{
			$shortName            = $element->name;
			$listModel            = $this->getListModel();
			$listModel->encrypt[] = $shortName;
		}

		$formModel            = $this->getFormModel();
		$data[$element->name] = $this->getAverage($formModel->formDataWithTableName, $repeatCounter);

		return true;
	}

	/**
	 * @param   array  $data
	 * @param   int    $repeatCounter
	 *
	 * @return float
	 */
	private function getAverage(array $data = array(), int $repeatCounter = 0): float
	{
		$average = 0.00;

		$params         = $this->getParams();
		$averageOver    = $params->get('average_over', 20);
		$observedFields = $this->getObservedFields();

		$fieldValues = [];
		if (!empty($observedFields))
		{
			foreach ($observedFields as $field)
			{
				$element      = $field['element'];
				$elementModel = $this->getFormModel()->getElement($element) ?: null;

				$value = ArrayHelper::getValue($data, $element . '_raw');
				if ($this->getGroup()->canRepeat())
				{
					$value = ArrayHelper::getValue($value, $repeatCounter);
				}

				if (is_array($value))
				{
					$value = array_map(fn($v) => $this->toNumber($v, $elementModel), $value);
					$value = !empty($value) ? array_sum($value) / count($value) : 0;
				}
				else
				{
					$value = $this->toNumber($value, $elementModel);
				}

				$fieldValues[] = [
					'value'  => $value,
					'max'    => (float) $field['max'],
					'weight' => (float) $field['weight']
				];
			}

			$average = self::roundAverage(self::calculateAverage($fieldValues, $averageOver), $this->getDecimals(), $this->getRounding());
		}

		return $average;
	}

	/**
	 * Submitted values keep the observed element's display format (e.g. "10,5" or "1 000,5" for numeric)
	 *
	 * @param   mixed                   $value
	 * @param   PlgFabrik_Element|null  $elementModel
	 *
	 * @return float
	 */
	private function toNumber(mixed $value, ?PlgFabrik_Element $elementModel = null): float
	{
		if (is_array($value) || $value === null || $value === '')
		{
			return 0.0;
		}

		if ($elementModel instanceof PlgFabrik_ElementNumeric)
		{
			return $elementModel->storeDatabaseFormat($value, []);
		}

		return self::parseNumber((string) $value);
	}

	public static function parseNumber(string $value): float
	{
		$value = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $value);
		$value = str_replace(',', '.', $value);

		return is_numeric($value) ? (float) $value : 0.0;
	}

	/**
	 * @param   array  $fields
	 * @param   int    $averageOver
	 *
	 * @return float
	 */
	public static function calculateAverage(array $fields, int $averageOver): float
	{
		$average = 0.00;

		$totalWeights = 0;
		foreach ($fields as $field)
		{
			if ($field['max'] > 0)
			{
				if ($field['value'] > $field['max'])
				{
					$field['value'] = $field['max'];
				}

				$fieldValueNormalized = ($field['value'] / $field['max']) * $averageOver;
			}
			else
			{
				$fieldValueNormalized = $field['value'];
			}

			$average      += $fieldValueNormalized * $field['weight'];
			$totalWeights += $field['weight'];
		}

		if ($totalWeights > 0)
		{
			$average = $average / $totalWeights;
		}

		return $average;
	}

	/**
	 * @param   float   $value
	 * @param   int     $decimals
	 * @param   string  $rounding  round, ceil or floor
	 *
	 * @return float
	 */
	public static function roundAverage(float $value, int $decimals = self::MAX_DECIMALS, string $rounding = 'round'): float
	{
		$decimals = max(0, min(self::MAX_DECIMALS, $decimals));
		$factor   = 10 ** $decimals;

		// Strip float noise before ceil/floor, otherwise 12.3 * 10 = 123.00000000000001 is ceiled to 124
		$scaled = round($value * $factor, 6);

		return match ($rounding)
		{
			'ceil' => ceil($scaled) / $factor,
			'floor' => floor($scaled) / $factor,
			default => round($value, $decimals),
		};
	}
}
