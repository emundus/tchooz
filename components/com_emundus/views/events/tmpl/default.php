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

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

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

$data = LayoutFactory::prepareVueData();
?>

<style link="media/com_emundus_vue/app_emundus.css?<?php echo $data['hash'] ?>"></style>

<div id="em-component-vue"
     component="Events/Events"
     shortLang="<?= $data['short_lang'] ?>" currentLanguage="<?= $data['current_lang'] ?>"
     defaultLang="<?= $data['default_lang'] ?>"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>"
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
