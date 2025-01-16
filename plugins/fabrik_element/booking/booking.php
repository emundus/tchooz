<?php
/**
 * Plugin element to book an availability event's slot
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.booking
 * @copyright   Copyright (C) 2005-2023  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Plugin element to render booking
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.booking
 * @since       3.0
 */
class PlgFabrik_ElementBooking extends PlgFabrik_Element
{
	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$format           = 'hours';
		$config           = $this->app->getConfig();
		$timezone         = [];
		$timezone['name'] = $config->get('offset', 'UTC');

		$dateTZ = new DateTimeZone($timezone['name']);
		$date   = new DateTime('now', $dateTZ);
		$offset = $dateTZ->getOffset($date);
		if (!empty($offset))
		{
			if ($format == 'hours')
			{
				$offset = $offset / 3600;
			}
			elseif ($format == 'minutes')
			{
				$offset = $offset / 60;
			}
		}
		$timezone['offset'] = $offset;

		$params                           = $this->getParams();
		$name                             = $this->getHTMLName($repeatCounter);
		$layout                           = $this->getLayout('form');
		$displayData                      = new stdClass;
		$displayData->name                = $name;
		$displayData->timezone            = $timezone['name'];
		$displayData->offset              = $timezone['offset'];
		$displayData->location_filter_elt = $params->get('location_filter_elt', '');

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
		$id   = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		return array('FbBooking', $id, $opts);
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   This elements posted form data
	 * @param   array  $data  Posted form data
	 *
	 * @return  mixed
	 */
	public function storeDatabaseFormat($val, $data)
	{
		require_once(JPATH_ROOT . '/components/com_emundus/models/events.php');
		$m_events = new EmundusModelEvents();

		$fnum = $data['fnum'];
		if (empty($fnum))
		{
			$app       = Factory::getApplication();
			$emSession = $app->getSession()->get('emundusUser');

			$fnum = $emSession->fnum;
		}

		$m_events->createAvailabilityRegistrant($val, $fnum);

		return $val;
	}

	/**
	 * Internal element validation
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  bool
	 */
	public function validate($data, $repeatCounter = 0)
	{
		$validate = false;

		$user = Factory::getApplication()->getIdentity();

		require_once(JPATH_ROOT . '/components/com_emundus/models/events.php');
		$m_events = new EmundusModelEvents();

		$availability   = $m_events->getEventsAvailabilities('', '', '', $data, false)[0];
		$availabilities = $m_events->getEventsAvailabilities($availability->start, $availability->end, $availability->event);

		$my_registrants = [];
		foreach ($availabilities as $availability)
		{
			$my_registrants = array_merge($my_registrants, $m_events->getAvailabilityRegistrants($availability->id, $user->id));
		}

		if (!empty($my_registrants))
		{
			$validate = true;
		}
		else
		{
			$registrants = [];
			foreach ($availabilities as $availability)
			{
				$registrants = array_merge($registrants, $m_events->getAvailabilityRegistrants($availability->id));
			}

			$totalCapacity = array_sum(array_map(function ($availability) {
				return $availability->capacity;
			}, $availabilities));


			$validate = $totalCapacity > count($registrants);
		}

		return $validate;
	}

	/**
	 * Get validation error - run through Text
	 *
	 * @return  string
	 */
	public function getValidationErr()
	{
		return Text::_('PLG_FABRIK_ELEMENT_BOOKING_SLOT_NO_LONGER_AVAILABLE');
	}
}
