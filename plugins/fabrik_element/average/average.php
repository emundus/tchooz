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
		$name = $this->getHTMLName($repeatCounter);
		$layout = $this->getLayout('form');
		$displayData = new stdClass;
		$displayData->name = $name;
		$displayData->type = 'text';
		$displayData->id = $this->getHTMLId($repeatCounter);

		$value = $this->getValue($data, $repeatCounter);
		$displayData->value = !empty($value) ? $value : "0.00";

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
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		$elements_to_observe = [];

		$opts->elements_to_observe = $this->getObservedFields();

		return array('FbAverage', $id, $opts);
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
			$shortName = $element->name;
			$listModel = $this->getListModel();
			$listModel->encrypt[] = $shortName;
		}

		$formModel = $this->getFormModel();
		$data[$element->name] = $this->getAverage($formModel->formDataWithTableName, $repeatCounter);

		return true;
	}

	/**
	 * @param $data
	 * @param $repeatCounter
	 *
	 * @return float
	 */
	private function getAverage(array $data = array(), int $repeatCounter = 0): float
	{
		$average = 0.00;

		$observedFields = $this->getObservedFields();

		if (!empty($observedFields))
		{
			$total_weight = 0;
			$sum = 0;

			foreach ($observedFields as $field)
			{
				$element = $field['element'];
				$weight = $field['weight'];
				$total_weight += $weight;

				$value   = ArrayHelper::getValue($data, $element . '_raw');
				if ($this->getGroup()->canRepeat())
				{
					$value = ArrayHelper::getValue($value, $repeatCounter);
				}

				if (is_array($value) && !empty($value))
				{
					$value = array_sum($value) / count($value);
				}

				$sum += $value * $weight;
			}

			$average = $sum / $total_weight;
			$average = round($average, 2);
		}

		return $average;
	}

	private function getObservedFields(): array
	{
		$fields = [];

		$params = $this->getParams();
		$average_multiple_elements = $params->get('average_multiple_elements');
		$average_multiple_elements = json_decode($average_multiple_elements);

		foreach ($average_multiple_elements->average_multiple_element as $key => $element)
		{
			$repeat = false;

			if (preg_match('/_repeat___(.*)_[0-9]+$/', $element))
			{
				// remove last digit
				$element = preg_replace('/_[0-9]+$/', '', $element);
				$repeat = true;
			}

			$fields[] = [
				'element' => $element,
				'repeat' => $repeat,
				'weight' => $average_multiple_elements->average_multiple_weight[$key]
			];
		}

		return $fields;
	}
}