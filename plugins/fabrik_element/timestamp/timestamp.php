<?php
/**
 * Plugin element to render a timestamp
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Profiler\Profiler;
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;
use Joomla\CMS\HTML\HTMLHelper;

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to render a timestamp
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.timestamp
 * @since       3.0
 */
class PlgFabrik_ElementTimestamp extends PlgFabrik_Element
{
	/**
	 * If the element 'Include in search all' option is set to 'default' then this states if the
	 * element should be ignored from search all.
	 *
	 * @var bool  True, ignore in extended search all.
	 */
	protected $ignoreSearchAllDefault = true;

	/**
	 * Does the element's data get recorded in the db
	 *
	 * @var bool
	 */
	protected $recordInDatabase = true;

	/**
	 * Set/get if element should record its data in the database
	 *
	 * @deprecated - not used
	 *
	 * @return bool
	 */
	public function setIsRecordedInDatabase()
	{
		$this->recordInDatabase = true;
	}

	/**
	 * States if the element contains data which is recorded in the database
	 * some elements (e.g. buttons) don't
	 *
	 * @param   array $data posted data
	 *
	 * @return  bool
	 */
	public function recordInDatabase($data = null)
	{
		return true;
	}

	/**
	 * Get the GMT Date time - tz offset applied in render() if needed
	 *
	 * @param   array $data          Form data timestamp will be GMT if store as local OFF, otherwise as local time
	 * @param   int   $repeatCounter When repeating joined groups we need to know what part of the array to access
	 * @param   array $opts          Options
	 *
	 * @return  string    value  timestamp
	 */
	public function getValue($data, $repeatCounter = 0, $opts = [])
	{
		if (is_array($data) === false) {
			$data = [$repeatCounter => $data];
		}
		$params       = $this->getParams();
		$storeAsLocal = $params->get('gmt_or_local', 0);
		$storeAsLocal += 0;
		$formModel    = $this->getFormModel();
		$value        = parent::getValue($data, $repeatCounter, $opts);

		if (FabrikWorker::inFormProcess())
		{
			// Don't mess with posted value - can cause double offsets - instead do in _indStoareDBFormat();
			return $value;
		}

		// Don't offset if null timestamp.
		if ($value === null)
		{
			return $value;
		}

		$timeZone = new \DateTimeZone($this->config->get('offset'));
		$date     = Factory::getDate($value);

		$value = $date->toSQL($storeAsLocal);

		return $value;
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();

		$layout = $this->getLayout('form');
		$layoutData = new stdClass;
		$layoutData->id =  $this->getHTMLId($repeatCounter);
		$layoutData->name = $this->getHTMLName($repeatCounter);

		if ($params->get('timestamp_update_on_edit') || $this->getFormModel()->isNewRecord()) {
			$date = Factory::getDate();
			$tz = new \DateTimeZone($this->config->get('offset'));
			$date->setTimezone($tz);
			$params = $this->getParams();
			$gmtOrLocal = $params->get('gmt_or_local');
			$gmtOrLocal += 0;
			$layoutData->value = $date->toSql($gmtOrLocal);
		} else {
			$layoutData->value = $this->getValue($data, $repeatCounter);
		}

		return $layout->render($layoutData);
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
        $profiler = Profiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

        $params = $this->getParams();
		$gmtOrLocal = $params->get('gmt_or_local');
		$gmtOrLocal += 0;

		if ($gmtOrLocal == '0') {
			/* Adjust the date to local time for display */
			$date = Factory::getDate($data);
			$tz = new \DateTimeZone($this->config->get('offset'));
			$date->setTimezone($tz);
			$data = $date->__toString();
		}

		return parent::renderListData($data, $thisRow, $opts);
	}

	/**
	 * Get database field description
	 *
	 * @return  string  db field type
	 */
	public function getFieldDescription()
	{
		$params = $this->getParams();

		if ($params->get('encrypt', false))
		{
			return 'BLOB';
		}

		if ($params->get('timestamp_update_on_edit'))
		{
			return "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
		}
		else
		{
			return "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP";
		}
	}

	/**
	 * Is the element hidden or not - if not set then return false
	 *
	 * @return  bool
	 */
	public function isHidden()
	{
		return true;
	}
}
