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
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted Access');

$app = Factory::getApplication();
$language = $app->getLanguage();
$current_lang = $language->getTag();

$language->load('com_emundus', JPATH_SITE.'/components/com_emundus', $current_lang, true);
$short_lang   = substr($current_lang, 0, 2);

$languages    = LanguageHelper::getLanguages();
if (count($languages) > 1) {
	$many_languages = '1';
}
else {
	$many_languages = '0';
}

$user                 = $app->getIdentity();
$coordinator_access   = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access      = EmundusHelperAccess::isAdministrator($user->id);

require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();

Text::script('COM_EMUNDUS_EVENT_NO_SLOT_AVAILABLE');
Text::script('COM_EMUNDUS_EVENT_MESSAGE_EDITING_SLOT_FROM_FILE');
Text::script('COM_EMUNDUS_EVENT_SLOT_RECAP');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOUR');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS');
Text::script('BACK');
Text::script('COM_EMUNDUS_EVENTS_CANCEL_RESERVATION');
Text::script('COM_EMUNDUS_EVENTS_ARE_YOU_SURE_CANCEL_RESERVATION');
Text::script('COM_EMUNDUS_EVENT_CANT_BOOK_UNTIL_DATE');
Text::script('COM_EMUNDUS_EVENT_CANT_BOOK_NOW');
Text::script('COM_EMUNDUS_EVENT_CANT_BOOK_FROM_DATE');
Text::script('COM_EMUNDUS_EVENT_CANT_BOOK_FROM_DATE');
Text::script('COM_EMUNDUS_EVENTS_RESERVATION_DELETED');

$d = $displayData;
?>

<style link="media/com_emundus_vue/app_emundus.css?<?php echo $hash ?>"></style>

<div id="em-component-vue"
     component="Events/EventBooking"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
     shortLang="<?= $short_lang ?>" currentLanguage="<?= $current_lang ?>"
     manyLanguages="<?= $many_languages ?>"
     name_element="<?= $d->name ?>"
     timezone="<?= $d->timezone; ?>"
     offset="<?= $d->offset; ?>"
     isApplicant="<?= $d->is_applicant ?>"
     source="fabrik"
     location_filter_elt="<?= $d->location_filter_elt ?>"
     application_choices_filter_elt="<?= $d->application_choices_filter_elt ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
