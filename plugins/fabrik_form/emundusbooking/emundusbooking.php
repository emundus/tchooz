<?php
/**
 * @version     2: emunduscollaborate
 * @package     Fabrik
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Accepter une invitation à collaborer sur un formulaire
 */

// No direct access
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */
class PlgFabrik_FormEmundusBooking extends plgFabrik_Form
{
	/**
	 * Status field
	 *
	 * @var  string
	 */
	protected $URLfield = '';

	/**
	 * Get an element name
	 *
	 * @param   string  $pname  Params property name to look up
	 * @param   bool    $short  Short (true) or full (false) element name, default false/full
	 *
	 * @return    string    element full name
	 */
	public function getFieldName($pname, $short = false)
	{
		$params = $this->getParams();

		if ($params->get($pname) == '') {
			return '';
		}

		$elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pname));

		return $short ? $elementModel->getElement()->name : $elementModel->getFullName();
	}

	/**
	 * Get the fields value regardless of whether its in joined data or no
	 *
	 * @param   string  $pname    Params property name to get the value for
	 * @param   mixed   $default  Default value
	 *
	 * @return  mixed  value
	 */
	public function getParam($pname, $default = '')
	{
		$params = $this->getParams();

		if ($params->get($pname) == '') {
			return $default;
		}

		return $params->get($pname);
	}

	public function onBeforeProcess()
	{
		$formModel = $this->getModel();
		$data = $formModel->formData;

		$ccid = is_array($data['jos_emundus_registrants___ccid_raw']) ? $data['jos_emundus_registrants___ccid_raw'][0] : $data['jos_emundus_registrants___ccid_raw'];
		$availability_id = is_array($data['jos_emundus_registrants___availability_raw']) ? $data['jos_emundus_registrants___availability_raw'][0] : $data['jos_emundus_registrants___availability_raw'];

		if(!empty($ccid)) {
			// Get applicant_id
			$db = Factory::getContainer()->get('DatabaseDriver');

			$query = $db->getQuery(true);

			$query->select('applicant_id,fnum')
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where($db->quoteName('id') . ' = ' . $db->quote($ccid));
			$db->setQuery($query);
			$applicant = $db->loadObject();

			// Update form data
			$formModel->updateFormData('jos_emundus_registrants___user', $applicant->applicant_id, true);
			$formModel->updateFormData('jos_emundus_registrants___fnum', $applicant->fnum, true);

			// Get slot from availability selected
			$query->clear()
				->select('slot')
				->from($db->quoteName('#__emundus_setup_availabilities'))
				->where($db->quoteName('id') . ' = ' . $db->quote($availability_id));
			$db->setQuery($query);
			$slot = $db->loadResult();

			$formModel->updateFormData('jos_emundus_registrants___slot', $slot, true);
		}

		return true;
	}

	public function onAfterProcess()
	{
		$datas = $this->getProcessData();

		$id = $datas['jos_emundus_registrants___id_raw'];
		$ccid = is_array($datas['jos_emundus_registrants___ccid_raw']) ? $datas['jos_emundus_registrants___ccid_raw'][0] : $datas['jos_emundus_registrants___ccid_raw'];
		$fnum = $datas['jos_emundus_registrants___fnum_raw'];
		$availability_id = is_array($datas['jos_emundus_registrants___availability_raw']) ? $datas['jos_emundus_registrants___availability_raw'][0] : $datas['jos_emundus_registrants___availability_raw'];

		require_once JPATH_BASE . '/components/com_emundus/models/events.php';
		$m_events = new EmundusModelEvents();
		$availability = $m_events->getEventsAvailabilities('', '', '', $availability_id)[0];

		// Declare the event
		$onAfterBookingRegistrantEventHandler = new GenericEvent(
			'onCallEventHandler',
			['onAfterBookingRegistrant',
				// Datas to pass to the event
				['fnum' => $fnum, 'ccid' => $ccid, 'availability' => $availability, 'registrant_id' => $id]
			]
		);
		$onAfterBookingRegistrant = new GenericEvent(
			'onAfterBookingRegistrant',
			// Datas to pass to the event
			['fnum' => $fnum, 'ccid' => $ccid, 'availability' => $availability, 'registrant_id' => $id]
		);

		// Dispatch the event
		PluginHelper::importPlugin('emundus');
		$dispatcher = Factory::getApplication()->getDispatcher();

		$dispatcher->dispatch('onCallEventHandler', $onAfterBookingRegistrantEventHandler);
		$dispatcher->dispatch('onAfterBookingRegistrant', $onAfterBookingRegistrant);
	}
}
