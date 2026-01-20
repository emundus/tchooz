<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

defined('_JEXEC') or die('Restricted Access');

Text::script('BACK');
Text::script('COM_EMUNDUS_OPTIONAL');
Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL');

Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_ADD_LOCATION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_LOCATION_SELECT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION_CREATE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION_NAME');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION_ADDRESS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOMS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOM_NAME');
Text::script('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_ROOM');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOM_SPECS');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_LOCATION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_LOCATION_DESCRIPTION');

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     locationId="<?= Factory::getApplication()->input->get('location'); ?>"
     component="Events/LocationForm"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
     shortLang="<?= $data['short_lang'] ?>" currentLanguage="<?= $data['current_lang'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
