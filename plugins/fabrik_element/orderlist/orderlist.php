<?php
/**
 * Plugin element to render fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.field
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

/**
 * Plugin element to render application choices fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.application_choices
 * @since       3.0
 */
class PlgFabrik_ElementOrderlist extends PlgFabrik_ElementList
{

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$params = $this->getParams();
		$id   = $this->getHTMLId($repeatCounter);
		$data = $this->getFormModel()->data;
		$arVals = $this->getSubOptionValues($data);
		$arTxt = $this->getSubOptionLabels($data);
		$opts = $this->getElementJSOptions($repeatCounter);
		$opts->value = $this->getValue($data, $repeatCounter);
		$opts->defaultVal = $this->getDefaultValue($data);
		$opts->data = empty($arVals) ? array() : array_combine($arVals, $arTxt);
		$opts->allowadd = $params->get('allow_frontend_addtoradio', false) ? true : false;
		$opts->changeEvent = $this->getChangeEvent();
		$opts->btnGroup = $this->buttonGroup();
		$opts->layout = $this->isEditable() ? 'form' : 'details';

		return array('FbOrderlist', $id, $opts);
	}

	/**
	 * @param $data
	 * @param $repeatCounter
	 *
	 * @return string
	 */
	public function render($data, $repeatCounter = 0)
	{
		$layout      = $this->getLayout('form');

		if (!$this->isEditable())
		{
			$layout = $this->getLayout('details');
		}

		$values = $this->getSubOptionValues($data);
		$labels = $this->getSubOptionLabels($data);

		$displayData = new stdClass;
		$displayData->values = $values;
		$displayData->labels = $labels;
		$displayData->options = [];
		$this->hasSubElements = false;
		$displayData->id = $this->getHTMLId($repeatCounter);
		$displayData->name = $this->getHTMLName($repeatCounter);
		$value = $this->getValue($data, $repeatCounter);
		$displayData->value = !empty($value) ? current($value) : '';
		$displayData->repeatCounter = $repeatCounter;

		// SANITIZE STORED VALUES
		if (!empty($displayData->value))
		{
			$storedValues = explode(',', $displayData->value);
			$storedValues = array_map(function ($item) {return trim($item, '"');}, $storedValues);

			// make sure stored values are inside the options values
			$storedValues = array_filter($storedValues, function ($storedValue) use ($values) {
				return in_array($storedValue, $values);
			});

			// if there are missing options, we add them to the options list with the stored value as label
			foreach ($values as $value) {
				if (!in_array($value, $storedValues))
				{
					$storedValues[] = $value;
				}
			}

			$displayData->value = implode(',', $storedValues);
		}

		foreach ($values as $key => $value)
		{
			$option = new \Tchooz\Entities\Fields\ChoiceFieldValue($value, $labels[$key]);
			$displayData->options[] = $option->toSchema();
		}

		return $layout->render($displayData);
	}
}