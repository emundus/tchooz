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

Text::script('COM_EMUNDUS_ONBOARD_ADD_EMAIL');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_CHOOSETYPE');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_NAME');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_SENDER_EMAIL');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_RECEIVER');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESS');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESTIP');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PARAMETER');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_INFORMATION');
Text::script('COM_EMUNDUS_ONBOARD_CHOOSECATEGORY');
Text::script('COM_EMUNDUS_ONBOARD_ADD_RETOUR');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTINUER');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_RESUME');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_CATEGORY');
Text::script('COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE');
Text::script('COM_EMUNDUS_ONBOARD_EMAILTYPE');
Text::script('COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING');
Text::script('COM_EMUNDUS_ONBOARD_SUBJECT_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_BODY_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_BODY');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_BUTTON_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_BUTTON_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_BUTTON_TEXT_TIP');
Text::script('COM_EMUNDUS_ONBOARD_VARIABLESTIP');
Text::script('COM_EMUNDUS_ONBOARD_TIP');
Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TRIGGER');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERMODEL_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERSTATUS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERSTATUS_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERTARGET');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERTARGET_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_CANDIDATES');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_DEFINED_USERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_CHOOSE_USERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_USERS_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_SEARCH_USERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERMODEL');
Text::script('COM_EMUNDUS_ONBOARD_THE_CANDIDATE');
Text::script('COM_EMUNDUS_ONBOARD_MANUAL');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_ACTIONS');
Text::script('COM_EMUNDUS_EMAIL_SHOW_TAGS');

## TUTORIAL ##
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_CAMPAIGN');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORM');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORMBUILDER');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_DOCUMENTS');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_PROGRAM');
## END ##

Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TAGS');
Text::script('COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT');

# receiver
Text::script('COM_EMUNDUS_ONBOARD_RECEIVER_CC_TAGS');
Text::script('COM_EMUNDUS_ONBOARD_RECEIVER_BCC_TAGS');

Text::script('COM_EMUNDUS_ONBOARD_RECEIVER_CC_TAGS_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_RECEIVER_BCC_TAGS_PLACEHOLDER');

Text::script('COM_EMUNDUS_ONBOARD_PLACEHOLDER_EMAIL_DOCUMENT');

Text::script('COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT');

Text::script('COM_EMUNDUS_ONBOARD_CC_BCC_TOOLTIPS');

Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TAGS');
Text::script('COM_EMUNDUS_ONBOARD_PLACEHOLDER_EMAIL_TAGS');

Text::script('COM_EMUNDUS_ONBOARD_CANDIDAT_ATTACHMENTS');
Text::script('COM_EMUNDUS_ONBOARD_PLACEHOLDER_CANDIDAT_ATTACHMENTS');

Text::script('COM_EMUNDUS_ONBOARD_ERROR');
Text::script('COM_EMUNDUS_ONBOARD_ERROR_MESSAGE');
Text::script('COM_EMUNDUS_ONBOARD_OK');
Text::script('COM_EMUNDUS_FORM_BUILDER_NEW_VALUE');
Text::script('COM_EMUNDUS_FORM_BUILDER_EXISTING_VALUE');

Text::script('COM_EMUNDUS_ONBOARD_SHOW_ALIAS_LIST');

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     email="<?= Factory::getApplication()->input->get('eid'); ?>"
     component="addEmail"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
