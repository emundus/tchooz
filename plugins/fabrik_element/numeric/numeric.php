<?php

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

class PlgFabrik_ElementNumeric extends PlgFabrik_Element
{
	/**
	 * Get database field description
	 *
	 * @return  string  Db field type
	 */
	public function getFieldDescription(): string
	{
		return 'DECIMAL(10,3)';
	}

	/**
	 * @param $data
	 * @param $repeatCounter
	 *
	 * @return string
	 */
	public function render($data, $repeatCounter = 0): string
	{
		$layout      = $this->isEditable() ? $this->getLayout('form') : $this->getLayout('details');
		$displayData = new stdClass;
		$displayData->id = $this->getHTMLId($repeatCounter);
		$displayData->name = $this->getHTMLName($repeatCounter);
		$displayData->value = $this->getValue($data, $repeatCounter);

		if (!empty($displayData->value))
		{
			$displayData->value = $this->formatValue($displayData->value);
		}

		$displayData->repeatCounter = $repeatCounter;
		$displayData->editable = $this->isEditable();

		return $layout->render($displayData);
	}

	private function formatValue($value): string
	{
		$decimalSeparator = $this->getParams()->get('decimal_separator', '.');
		if ($decimalSeparator !== '.') {
			$value = str_replace($decimalSeparator, '.', $value);
		}
		$value = preg_replace('/[^\d.-]/', '', $value);

		$decimalNumber = $this->getParams()->get('decimal', 2);
		$thousandSeparator = $this->getParams()->get('thousand_separator', '');

		$value = number_format($value, $decimalNumber, $decimalSeparator, $thousandSeparator);

		return $value;
	}

	public function elementJavascript($repeatCounter): array
	{
		$id   = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		$opts->format = [
			'decimal_number' => $this->getParams()->get('decimal', ''),
			'decimal_separator' => $this->getParams()->get('decimal_separator', '.'),
			'thousand_separator' => $this->getParams()->get('thousand_separator', ''),
			'step' => $this->getParams()->get('step', ''),
			'min' => $this->getParams()->get('min', ''),
			'max' => $this->getParams()->get('max', ''),
		];

		return array('FbNumeric', $id, $opts);
	}

	/**
	 * @param $val
	 * @param $data
	 *
	 * @return float
	 */
	public function storeDatabaseFormat($val, $data): float
	{
		$decimalSeparator = $this->getParams()->get('decimal_separator', '.');
		if ($decimalSeparator !== '.') {
			$val = str_replace($decimalSeparator, '.', $val);
		}
		$val = preg_replace('/[^\d.-]/', '', $val);

		$decimalNumber = $this->getParams()->get('decimal', 2);
		$val = number_format((float)$val, $decimalNumber, '.', '');

		return (float)$val;
	}

	public function validate($data, $repeatCounter = 0): bool
	{
		$valid = true;

		$name           = $this->getHTMLId($repeatCounter);
		$hiddenElements = ArrayHelper::getValue($this->getFormModel()->formData, 'hiddenElements', '[]');
		$hiddenElements = json_decode($hiddenElements);

		if (!in_array($name, $hiddenElements))
		{
			$value = $data;

			$decimalSeparator = $this->getParams()->get('decimal_separator', '.');
			if ($decimalSeparator !== '.') {
				$value = str_replace($decimalSeparator, '.', $value);
			}

			$value = preg_replace('/[^\d.-]/', '', $value);

			if (!is_numeric($value))
			{
				$this->validationError = Text::_('PLG_FABRIK_ELEMENT_NUMERIC_NOT_NUMERIC');
				return false;
			}

			$max = $this->getParams()->get('max', '');
			if ($max !== '' && $value > $max)
			{
				$this->validationError = Text::sprintf('PLG_FABRIK_ELEMENT_NUMERIC_MAX_ERROR', $max);
				return false;
			}
			$min = $this->getParams()->get('min', '');
			if ($min !== '' && $value < $min)
			{
				$this->validationError = Text::sprintf('PLG_FABRIK_ELEMENT_NUMERIC_MIN_ERROR', $max);
				return false;
			}
		}

		return $valid;
	}
}