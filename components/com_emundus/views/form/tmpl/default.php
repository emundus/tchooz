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
require_once(JPATH_BASE . '/components/com_emundus/helpers/access.php');
require_once(JPATH_BASE . '/components/com_emundus/helpers/cache.php');


## GLOBAL ##
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_VISUALIZE');
Text::script('COM_EMUNDUS_ONBOARD_OK');
Text::script('COM_EMUNDUS_ONBOARD_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_ALL');
Text::script('COM_EMUNDUS_ONBOARD_SYSTEM');
Text::script('COM_EMUNDUS_ONBOARD_FORMS');
Text::script('COM_EMUNDUS_ONBOARD_FORMS_DESC');
Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGN_ASSOCIATED');
Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED');
Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_NOT');
Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_CANT_REVERT');
Text::script('COM_EMUNDUS_ONBOARD_EMPTY_LIST');
Text::script('COM_EMUNDUS_FORM_MY_EVAL_FORMS');
## END ##

## ACTIONS ##
Text::script('COM_EMUNDUS_ONBOARD_ACTION');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_CONFIRM_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_ARCHIVE');
Text::script('COM_EMUNDUS_ONBOARD_ARCHIVED');
Text::script('COM_EMUNDUS_ONBOARD_RESTORE');
## END ##

## FILTERS ##
Text::script('COM_EMUNDUS_ONBOARD_FILTER');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_ALL');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_OPEN');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_CLOSE');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_PUBLISH_FORM');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH_FORM');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_SELECT');
Text::script('COM_EMUNDUS_ONBOARD_DESELECT');
Text::script('COM_EMUNDUS_ONBOARD_TOTAL');
Text::script('COM_EMUNDUS_ONBOARD_SORT');
Text::script('COM_EMUNDUS_ONBOARD_SORT_CREASING');
Text::script('COM_EMUNDUS_ONBOARD_SORT_DECREASING');
Text::script('COM_EMUNDUS_ONBOARD_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_ALL_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_SEARCH');
## END ##

## FORM ##
Text::script('COM_EMUNDUS_ONBOARD_NOFORM');
Text::script('COM_EMUNDUS_ONBOARD_ADD_FORM');
Text::script('COM_EMUNDUS_ONBOARD_FORMDELETE');
Text::script('COM_EMUNDUS_ONBOARD_FORMDELETED');
Text::script('COM_EMUNDUS_ONBOARD_FORMUNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_FORMUNPUBLISHED');
Text::script('COM_EMUNDUS_ONBOARD_FORMPUBLISHED');
Text::script('COM_EMUNDUS_ONBOARD_FORMDUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_FORMDUPLICATED');
Text::script('COM_EMUNDUS_ONBOARD_FORMDUPLICATE_FAILED');
Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGN');
Text::script('COM_EMUNDUS_ONBOARD_EVALUATION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVAL_FORM');
Text::script('COM_EMUNDUS_ONBOARD_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_LABEL_FORM');
## END ##

## TUTORIAL ##
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_CAMPAIGN');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORM');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORMBUILDER');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_DOCUMENTS');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_PROGRAM');
## END ##

Text::script('COM_EMUNDUS_ONBOARD_NAME');
Text::script('COM_EMUNDUS_ONBOARD_START_DATE');
Text::script('COM_EMUNDUS_ONBOARD_END_DATE');
Text::script('COM_EMUNDUS_ONBOARD_STATE');
Text::script('COM_EMUNDUS_ONBOARD_NB_FILES');
Text::script('COM_EMUNDUS_ONBOARD_SUBJECT');
Text::script('COM_EMUNDUS_ONBOARD_TYPE');
Text::script('COM_EMUNDUS_ONBOARD_STATUS');
Text::script('COM_EMUNDUS_FORM_DELETE_MODEL_SUCCESS');
Text::script('COM_EMUNDUS_FORM_DELETE_MODEL_FAILURE');

Text::script('COM_EMUNDUS_ONBOARD_NO_PAGE_MODELS');
Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');
Text::script('COM_EMUNDUS_ONBOARD_FORMS_FILTER_PUBLISH');

$app = Factory::getApplication();
$lang         = $app->getLanguage();
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

$user               = $app->getIdentity();
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($user->id);

$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div id="em-component-vue"
      component="Forms"
      coordinatorAccess="<?= $coordinator_access ?>"
      sysadminAccess="<?= $sysadmin_access ?>"
      shortLang="<?= $short_lang ?>"
      currentLanguage="<?= $current_lang ?>"
      manyLanguages="<?= $many_languages ?>"
      defaultLang="<?= $default_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
