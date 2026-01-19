<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_EXPORTS_EMARGEMENT');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_EXPORTS_EXCEL');

Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NO_REGISTRANTS');
Text::script('COM_EMUNDUS_ONBOARD_LABEL_REGISTRANTS');
Text::script('COM_EMUNDUS_REGISTRANTS_DAY');
Text::script('COM_EMUNDUS_REGISTRANTS_HOUR');
Text::script('COM_EMUNDUS_REGISTRANTS_LOCATION');
Text::script('COM_EMUNDUS_REGISTRANTS_ROOM');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_ALL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_ALL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ROOM_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ROOM_ALL');
Text::script('COM_EMUNDUS_REGISTRANTS_USER');
Text::script('COM_EMUNDUS_REGISTRANTS_ASSOC_USER');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_DELETE_CONFIRM');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_APPLICANT_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_APPLICANT_ALL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ASSOC_USER_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ASSOC_USER_ALL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_ADD');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_ADD_SAVED');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_EVENT');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_EVENT_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_BOOKING');
Text::script('COM_EMUNDUS_EVENT_SLOT_RECAP');
Text::script('COM_EMUNDUS_EVENT_NO_SLOT_AVAILABLE');
Text::script('COM_EMUNDUS_EVENT_MESSAGE_EDITING_SLOT_FROM_FILE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_APPLICANT');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_APPLICANT_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_NO_SELECTED');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_CONFIRM');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_SAVED');
Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');

Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_DELETE');
Text::script('COM_EMUNDUS_EDIT_ITEM');
Text::script('COM_EMUNDUS_REGISTRANTS_BOOKED');
Text::script('COM_EMUNDUS_ONBOARD_EDITOR_UNDO');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_BOOKED_SLOT_NUMBER');

Text::script('COM_EMUNDUS_ONBOARD_SEARCH');
Text::script('COM_EMUNDUS_ONBOARD_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_LABEL');
Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');
Text::script('COM_EMUNDUS_REGISTRANTS_FILE_READY');
Text::script('COM_EMUNDUS_REGISTRANTS_LOCATION_NB_ROOMS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOUR');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_DAY_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_HOUR_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_RESEND');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_RESEND_CONFIRM');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_ASSOCIATE');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_CONFIRM_ASSOCIATE');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_ASSOC_SAVED');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_REPLACE');
Text::script('COM_EMUNDUS_EVENTS_EMARGEMENT');
Text::script('COM_EMUNDUS_EXPORTS_SELECT_INFORMATIONS');
Text::script('ALL_FEMININE');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_CONFIRM_EXPORT');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_CANCEL_EXPORT');
Text::script('COM_EMUNDUS_REGISTRANT_NO_PERMISSION');
Text::script('COM_EMUNDUS_ONBOARD_ERROR');

$data = LayoutFactory::prepareVueData();

$format       = 'hours';
$config       = Factory::getApplication()->getConfig();
$timezoneName = $config->get('offset', 'UTC');

$dateTZ = new DateTimeZone($timezoneName);
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
?>

<style link="media/com_emundus_vue/app_emundus.css?<?php echo $data['hash'] ?>"></style>

<div id="em-component-vue"
     component="Events/Registrants"
     shortLang="<?= $data['short_lang'] ?>" currentLanguage="<?= $data['current_lang'] ?>"
     defaultLang="<?= $data['default_lang'] ?>"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>"
     offset="<?= $offset; ?>"
     timezone="<?= $timezoneName; ?>"
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
