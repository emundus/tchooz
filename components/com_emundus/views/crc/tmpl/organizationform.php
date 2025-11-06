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
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');

Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');
Text::script('COM_EMUNDUS_MULTISELECT_NOKEYWORDS');
Text::script('COM_EMUNDUS_MULTISELECT_MAX_COUNTRIES_SELECTED');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NO');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORGANIZATION');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_ORG_MAIN');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_NAME');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_DESCRIPTION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_IDENTIFIER_CODE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_IDENTIFIER_CODE_HELP');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_URL_WEBSITE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_CONTACTS_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_OTHER_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_OTHER_CONTACTS_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_ORG_ADDRESS');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_ORG_SETTINGS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_STREET');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_EXTENDED');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_COUNTRY');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_POSTALCODE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_LOCALITY');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_REGION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_PUBLISHED');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_CREATE');
Text::script('COM_EMUNDUS_ONBOARD_SAVE');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_ORGANIZATION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_LOGO');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_URL_CHECK_INPUT_URL_NO');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_VALID_DOMAIN_NAME_CHECK_INPUT_URL_NO');
Text::script('COM_EMUNDUS_URL_UNVERIFIED_AND_UNSECURED');

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
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

$datas = [
	'id' => $this->id ?? 0,
	'shortLang' => $short_lang,
	'currentLanguage' => $current_lang,
	'defaultLang' => $default_lang,
	'manyLanguages' => $many_languages,
];
?>

<div id="em-component-vue"
     component="Organizations/OrganizationForm"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
