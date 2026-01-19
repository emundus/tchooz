<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

defined('_JEXEC') or die('Restricted Access');

Text::script('COM_EMUNDUS_ONBOARD_ADD_RETOUR');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTINUER');
Text::script('COM_EMUNDUS_ONBOARD_OK');
Text::script('COM_EMUNDUS_ONBOARD_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_NEXT');
Text::script('COM_EMUNDUS_ONBOARD_LOAD_FILE');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM');
Text::script('COM_EMUNDUS_ONBOARD_SELECT_ALL');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_UPDATE_ICON');
Text::script('COM_EMUNDUS_SWAL_OK_BUTTON');
Text::script('COM_EMUNDUS_ONBOARD_SETTINGS_CONTENT_PUBLISH');

Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_GLOBAL');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_NO_LANGUAGES_AVAILABLE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHELINS_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHELINS');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_DEFAULT');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_DEFAULT_DESC');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SECONDARY');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SECONDARY_DESC');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_LANGUAGE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_TRANSLATION_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_TRANSLATION_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_OBJECT');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_PROGRESS');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_LAST');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_ORPHELINS_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_ORPHELINS_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHELIN_CONFIRM_TRANSLATION');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_OTHER_LANGUAGE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SETUP_PROGRESSING');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SETUP_SUCCESS');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_FIELD');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SEND');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SENDED');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SENDED_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_CONTENT');
Text::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_CONTENT_DESC');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHANS_CONGRATULATIONS');
Text::script('COM_EMUNDUS_ONBOARD_BANNER');
Text::script('COM_EMUNDUS_FORM_BUILDER_RECOMMENDED_SIZE');
Text::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_EXPORT');

$app          = Factory::getApplication();
$default_menu = $app->input->getInt('default_menu', 1);

$redirect_on_close = '';
$menus             = $app->getMenu();
$items             = $menus->getItems('component', 'com_emundus');
foreach ($items as $item)
{
    if ($item->query['view'] == 'settings')
    {
        $redirect_on_close = $item->alias;
        break;
    }
}

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     component="TranslationTool"
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
     defaultLang="<?= $data['default_lang'] ?>"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>"
     showModalOnLoad="1"
     defaultMenuIndex="<?= $default_menu ?>"
     redirectOnClose="<?= $redirect_on_close ?>"
></div>

<script src="media/com_emundus/js/settings.js?<?php echo $data['hash'] ?>"></script>
<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>