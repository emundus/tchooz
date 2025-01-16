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
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.emundusbooking
 * @since       3.0
 */
class PlgFabrik_ListEmundusBooking extends plgFabrik_List
{
	public function onDeleteRows()
	{
		$listModel = $this->getModel();

		PluginHelper::importPlugin('emundus');
		$dispatcher = Factory::getApplication()->getDispatcher();

		require_once JPATH_BASE . '/components/com_emundus/models/events.php';
		$m_events = new EmundusModelEvents();

		if(!empty($listModel->data) && is_array($listModel->data)) {
			foreach ($listModel->data[0] as $row) {
				$fnum = $row->jos_emundus_registrants___fnum_raw;
				$ccid = $row->jos_emundus_registrants___ccid_raw;
				$registrant_id = $row->jos_emundus_registrants___id_raw;
				$availability_id = $row->jos_emundus_registrants___availability_raw;

				$availability = $m_events->getEventsAvailabilities('', '', '', $availability_id)[0];

				$onAfterUnsubscribeRegistrantEventHandler = new GenericEvent(
					'onCallEventHandler',
					['onAfterUnsubscribeRegistrant',
						// Datas to pass to the event
						['fnum' => $fnum, 'ccid' => (int) $ccid, 'availability' => $availability, 'registrant_id' => $registrant_id]
					]
				);
				$onAfterUnsubscribeRegistrant             = new GenericEvent(
					'onAfterUnsubscribeRegistrant',
					// Datas to pass to the event
					['fnum' => $fnum, 'ccid' => (int) $ccid, 'availability' => $availability, 'registrant_id' => $registrant_id]
				);

				// Dispatch the event
				$dispatcher->dispatch('onCallEventHandler', $onAfterUnsubscribeRegistrantEventHandler);
				$dispatcher->dispatch('onAfterBookingRegistrant', $onAfterUnsubscribeRegistrant);
			}
		}
	}
}
