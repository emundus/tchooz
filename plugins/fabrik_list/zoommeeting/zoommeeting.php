<?php
/**
 * List Article update plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.article
 * @copyright   Copyright (C) 2005 Fabrik. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';
require_once JPATH_BASE.'/plugins/fabrik_form/emunduszoommeeting/ZoomAPIWrapper.php';


/**
 * Add an action button to the list to enable update of content articles
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.article
 * @since       3.0
 */
class PlgFabrik_ListZoommeeting extends PlgFabrik_List {
    public function onDeleteRows() {
        /* delete a zoom meeting by id */
        $ids = filter_input(INPUT_POST, 'ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $mId = !empty($ids) ? current($ids) : 0;

        if (!empty($mId)) {
            $db = JFactory::getDbo();

            $query = $db->getQuery(true);
            $query->select('*')
                ->from($db->quoteName('#__emundus_jury'))
                ->where('id = ' . $db->quote($mId));

            $db->setQuery($query);

            try {
                $room = $db->loadObject();
            } catch (Exception $e) {
                JLog::add('Error trying to get meeting session on delete rows', JLog::ERROR, 'com_emundus.zoom');
            }

            if (!empty($room)) {
                $eMConfig = JComponentHelper::getParams('com_emundus');
                $apiSecret = $eMConfig->get('zoom_jwt', '');
                $zoom = new ZoomAPIWrapper($apiSecret);

                /* remove room */
                $zoom->doRequest('DELETE', '/meetings/' . $room->meeting_session, array(), array(), '');

                if ($zoom->responseCode() != 204) {
                    $zoom->requestErrors();
                } else {
                    $user = JFactory::getSession()->get('emundusUser');

                    if (!empty($user)) {
                        JLog::add('Zoom session ' . $room->meeting_session . ' deleted by ' .  $user->id , JLog::INFO, 'com_emundus.zoom');
                    } else {
                        JLog::add('Zoom session ' . $room->meeting_session . ' deleted', JLog::INFO, 'com_emundus.zoom');
                    }
                }
            } else {
                JLog::add('No zoom room found corresponding to mid ' . $mId, JLog::INFO, 'com_emundus.zoom');
            }
        } else {
            JLog::add('Empty ids onDeleteRows zoommeeting.php', JLog::INFO, 'com_emundus.zoom');
        }
    }
}
