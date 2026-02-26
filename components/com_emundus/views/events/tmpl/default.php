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
use Tchooz\Repositories\Actions\ActionRepository;

Text::script('COM_EMUNDUS_ONBOARD_EVENTS');
Text::script('COM_EMUNDUS_ONBOARD_EVENTS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT');
Text::script('COM_EMUNDUS_ONBOARD_NOEVENTS');
Text::script('COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS');
Text::script('COM_EMUNDUS_ONBOARD_EVENT_DELETE_CONFIRM');
Text::script('COM_EMUNDUS_EDIT_ITEM');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION');
Text::script('COM_EMUNDUS_ONBOARD_LOCATION_DELETE_CONFIRM');

Text::script('COM_EMUNDUS_ONBOARD_SEARCH');
Text::script('COM_EMUNDUS_ONBOARD_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_LABEL');
Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');
Text::script('COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS_ALL');
Text::script('COM_EMUNDUS_ONBOARD_NO_EVENTS');

Text::script('COM_EMUNDUS_ONBOARD_ACTION_SHOW_DETAILS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANTS');
Text::script('COM_EMUNDUS_ONBOARD_CAPACITY');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_LOCATION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_MANAGER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION');
Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGN_ASSOCIATED_DETAILS');
Text::script('COM_EMUNDUS_WORKFLOW_ASSOCIATED_PROGRAMS_DETAILS');

$data = LayoutFactory::prepareVueData();

$user = Factory::getApplication()->getIdentity();

$actionRepository = new ActionRepository();
$eventAction   = $actionRepository->getByName('event');

$data['crud'] = [
    'event' => [
        'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($eventAction->getId(), 'c', $user->id),
        'r' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($eventAction->getId(), 'r', $user->id),
        'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($eventAction->getId(), 'u', $user->id),
        'd' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($eventAction->getId(), 'd', $user->id),
    ]
];
?>

<style link="media/com_emundus_vue/app_emundus.css?<?php echo $data['hash'] ?>"></style>

<div id="em-component-vue"
     component="Events/Events"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
