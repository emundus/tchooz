<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
use Joomla\CMS\Language\Text;

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

$lang         = JFactory::getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$languages    = JLanguageHelper::getLanguages();
if (count($languages) > 1) {
	$many_languages = '1';
}
else {
	$many_languages = '0';
}

$user               = JFactory::getUser();
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($user->id);

require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div id="em-component-vue"
     locationId="<?= JFactory::getApplication()->input->get('location'); ?>"
     component="Events/LocationForm"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
     shortLang="<?= $short_lang ?>" currentLanguage="<?= $current_lang ?>"
     manyLanguages="<?= $many_languages ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
