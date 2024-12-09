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
use Joomla\CMS\Language\LanguageHelper;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');

Text::script('COM_EMUNDUS_ONBOARD_ADD_CAMPAIGN');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PARAMETER');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_CAMPNAME');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_STARTDATE');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_ENDDATE');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_INFORMATION');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME_TIP');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME_TIP_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION_TIP');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION_TIP_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_LANGUAGES');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_CHOOSEPROG');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PICKYEAR');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_FORM');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_FORM_DESC');
Text::script('COM_EMUNDUS_ONBOARD_ACCESS_TO_FORMS_LIST');
Text::script('COM_EMUNDUS_ONBOARD_ADDPROGRAM');
Text::script('COM_EMUNDUS_ONBOARD_ADD_RETOUR');
Text::script('COM_EMUNDUS_ONBOARD_ADD_QUITTER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTINUER');
Text::script('COM_EMUNDUS_ONBOARD_CONTINUE');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_CLOSE');
Text::script('COM_EMUNDUS_ONBOARD_DEPOTDEDOSSIER');
Text::script('COM_EMUNDUS_ONBOARD_PROGNAME');
Text::script('COM_EMUNDUS_ONBOARD_PROGCODE');
Text::script('COM_EMUNDUS_ONBOARD_CHOOSECATEGORY');
Text::script('COM_EMUNDUS_ONBOARD_NAMECATEGORY');
Text::script('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_NAME');
Text::script('COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_RESUME');
Text::script('COM_EMUNDUS_ONBOARD_PROG_REQUIRED_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_CAMP_REQUIRED_RESUME');
Text::script('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_LINK');
Text::script('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_START_DATE');
Text::script('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_END_DATE');
Text::script('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_YEAR');
Text::script('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_PROGRAM');
Text::script('COM_EMUNDUS_ONBOARD_OK');
Text::script('COM_EMUNDUS_ONBOARD_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATE_ENGLISH');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATETIP');
Text::script('COM_EMUNDUS_ONBOARD_TIP');
Text::script('COM_EMUNDUS_ONBOARD_PINNED_CAMPAIGN_TIP');
Text::script('COM_EMUNDUS_ONBOARD_PINNED_CAMPAIGN_TIP_TEXT');
Text::script('COM_EMUNDUS_SWAL_OK_BUTTON');
Text::script('COM_EMUNDUS_ONBOARD_FILES_LIMIT');
Text::script('COM_EMUNDUS_ONBOARD_FILES_LIMIT_NUMBER');
Text::script('COM_EMUNDUS_ONBOARD_FILES_LIMIT_STATUS');
Text::script('COM_EMUNDUS_ONBOARD_FILES_LIMIT_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERSTATUS_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATE_IN');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_INTRO_DESC');
Text::script('COM_EMUNDUS_GLOBAL_INFORMATIONS');
Text::script('COM_EMUNDUS_GLOBAL_INFORMATIONS_DESC');
Text::script('COM_EMUNDUS_ONBOARD_PROGCOLOR');
Text::script('COM_EMUNDUS_ADD_CAMPAIGN_ERROR');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_ALIAS');
Text::script('COM_EMUNDUS_ONBOARD_ALIAS_COPIED');
Text::script('COM_EMUNDUS_OPTIONAL');
Text::script('COM_EMUNDUS_CAMPAIGN_NO_STEPS_FOUND');

## TUTORIAL ##
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_CAMPAIGN');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORM');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORMBUILDER');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_DOCUMENTS');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_PROGRAM');
Text::script('BACK');
Text::script('COM_EMUNDUS_CAMPAIGNS_PIN');
Text::script('COM_EMUNDUS_CAMPAIGNS_VISIBLE');
## END ##

$app = Factory::getApplication();
if (version_compare(JVERSION, '4.0', '>'))
{
	$lang = $app->getLanguage();
	$user = $app->getIdentity();
}
else
{
	$lang = Factory::getLanguage();
	$user = Factory::getUser();
}

$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$languages    = LanguageHelper::getLanguages();
if (count($languages) > 1)
{
	$many_languages = '1';
	require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
	$m_translations = new EmundusModelTranslations();
	$default_lang   = $m_translations->getDefaultLanguage()->lang_code;
}
else
{
	$many_languages = '0';
	$default_lang   = $current_lang;
}

$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($user->id);

require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();
?>


<div id="em-component-vue"
     component="addCampaign"
     campaign="<?= $this->id; ?>"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
     defaultLang="<?= $default_lang ?>"
     manyLanguages="<?= $many_languages ?>"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
></div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
