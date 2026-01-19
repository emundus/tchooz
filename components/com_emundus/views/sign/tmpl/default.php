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
use Tchooz\Factories\Language\LanguageFactory;
use Tchooz\Factories\LayoutFactory;

## GLOBAL ##
Text::script('COM_EMUNDUS_ERROR');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_VISUALIZE');
Text::script('COM_EMUNDUS_ONBOARD_OK');
Text::script('COM_EMUNDUS_ONBOARD_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_ALL');
Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');
Text::script('COM_EMUNDUS_REGISTRANTS_FILE_READY');
Text::script('COM_EMUNDUS_ONBOARD_EDITOR_UNDO');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_NO_CONNECTORS');
## END ##

## ACTIONS ##
Text::script('COM_EMUNDUS_ONBOARD_ACTION');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');
## END ##

## FILTERS ##
Text::script('COM_EMUNDUS_ONBOARD_FILTER');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_ALL');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_OPEN');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_CLOSE');
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

## REQUESTS ##
Text::script('COM_EMUNDUS_ONBOARD_LABEL_REQUESTS');
Text::script('COM_EMUNDUS_ONBOARD_REQUESTS');
Text::script('COM_EMUNDUS_ONBOARD_REQUESTS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NO_REQUESTS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_REQUEST');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONFIRM');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_APPLICANT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_APPLICANT_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_ATTACHMENT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_ATTACHMENT_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONNECTOR');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONNECTOR_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_SIGNERS');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_SIGNERS_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_ADD_SAVED');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_CANCEL_REQUEST');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_CANCEL_CONFIRM');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_UPLOAD');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_UPLOAD_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_CANCEL_REASON');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_APPLICANTS_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_APPLICANTS_ALL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_ATTACHMENTS_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_ATTACHMENTS_ALL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_SIGNED_DATE_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_STATUS_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_STATUS_ALL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_EXPORTS_EXCEL');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_SEND_REMINDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SEND_REMINDER_CONFIRM');

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     component="Sign/Requests"
     shortLang="<?= $data['short_lang'] ?>" currentLanguage="<?= $data['current_lang'] ?>"
     defaultLang="<?= $data['default_lang'] ?>"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
