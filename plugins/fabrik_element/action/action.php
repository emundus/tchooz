<?php
/**
 * Plugin element to render button
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.button
 * @copyright   Copyright (C) 2005-2023  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

/**
 * Plugin element to perform an action
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.action
 * @since       3.0
 */
class PlgFabrik_ElementAction extends PlgFabrik_Element
{
	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string	elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id = $this->getHTMLId($repeatCounter);
		$element = $this->getElement();
		$params = $this->getParams();

		$layout = $this->getLayout('form');
		$displayData = new stdClass;
		$displayData->id = $id;
		$displayData->name = $name;
		$displayData->button_label = $params->get('button_label', Text::_($element->label));


		return $layout->render($displayData);
	}

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
		
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);
		
		$opts->type = $params->get('action_to_perform');
		$opts->options = $this->getOptionsViaAction($opts->type, $params);

		return array('FbAction', $id, $opts);
	}

	public function getOptionsViaAction($type, $params)
	{
		if($type === 'generate_letter')
		{
			return array(
				'letter' => $params->get('generate_letter_id')
			);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Get an array of element html ids and their corresponding
	 * js events which trigger a validation.
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 *
	 * @param   int  $repeatCounter  repeat group counter
	 *
	 * @return  array  html ids to watch for validation
	 */
	public function getValidationWatchElements($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$ar = array('id' => $id, 'triggerEvent' => 'click');

		return array($ar);
	}
}
