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

Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');

Text::script('COM_EMUNDUS_ONBOARD_SEARCH');
Text::script('COM_EMUNDUS_ONBOARD_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_LABEL');
Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');

defined('_JEXEC') or die('Restricted Access');

require_once(JPATH_BASE . '/components/com_emundus/helpers/access.php');

$lang         = Factory::getApplication()->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$languages    = LanguageHelper::getLanguages();
if (count($languages) > 1) {
	$many_languages = '1';
	require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
	$m_translations = new EmundusModelTranslations();
	$default_lang   = $m_translations->getDefaultLanguage()->lang_code;
}
else {
	$many_languages = '0';
	$default_lang   = $current_lang;
}

$user               = Factory::getApplication()->getIdentity();
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($user->id);

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();
?>

<style link="media/com_emundus_vue/app_emundus.css?<?php echo $hash ?>"></style>

<div id="em-component-vue"
     component="Events/Registrants"
     shortLang="<?= $short_lang ?>" currentLanguage="<?= $current_lang ?>"
     defaultLang="<?= $default_lang ?>"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
     manyLanguages="<?= $many_languages ?>"
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
