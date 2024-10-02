<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('ACCESS_DENIED');

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Emundus\Site\Exception\EmundusException;

$app = Factory::getApplication();

// Require the base controller
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'controller.php');

// Helpers
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'access.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'array.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'checklist.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'date.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'emails.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'events.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'fabrik.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'filters.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'javascript.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'list.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'menu.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'messages.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'module.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'tags.php');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'users.php');

// LOGGER
jimport('joomla.log.log');
Log::addLogger(
	array(
		'text_file' => 'com_emundus.error.php'
	),
	Log::ALL,
	array('com_emundus')
);
Log::addLogger(
	array(
		'text_file' => 'com_emundus.email.php'
	),
	Log::ALL,
	array('com_emundus.email')
);
Log::addLogger(
	array(
		'text_file' => 'com_emundus.webhook.php'
	),
	Log::ALL,
	array('com_emundus.webhook')
);

// Translations for Javascript
Text::script('PLEASE_SELECT');
Text::script('IN');
Text::script('ALL');
Text::script('ALL_FEMININE');
Text::script('USERNAME');
Text::script('EMAIL');
Text::script('APPLICATION_CREATION_DATE');
Text::script('CAMPAIGN_ID');
Text::script('SEND_ON');
Text::script('COM_EMUNDUS_ONBOARD_ERROR_MESSAGE');
Text::script('COM_EMUNDUS_ONBOARD_OK');
Text::script('COM_EMUNDUS_ONBOARD_CANCEL');
Text::script('COM_EMUNDUS_MAIL_SENDED');

Text::script('COM_EMUNDUS_EX');
Text::script('COM_EMUNDUS_ADD');
Text::script('COM_EMUNDUS_THESIS_DELETE');
Text::script('COM_EMUNDUS_APPLICATION_TAG');
Text::script('COM_EMUNDUS_APPLICATION_DELETE_TAG');
Text::script('COM_EMUNDUS_APPLICATION_DELETE_TAG_CONFIRM');
Text::script('COM_EMUNDUS_APPLICATION_DELETE_TAG_SUCCESS');
Text::script('COM_EMUNDUS_APPLICATION_DELETE_COMMENT');
Text::script('COM_EMUNDUS_APPLICATION_DELETE_COMMENT_CONFIRM');
Text::script('COM_EMUNDUS_APPLICATION_DELETE_COMMENT_SUCCESS');
Text::script('COM_EMUNDUS_ACCESS_FILE');
Text::script('COM_EMUNDUS_ACCESS_ATTACHMENT');
Text::script('COM_EMUNDUS_ACCESS_TAGS');
Text::script('COM_EMUNDUS_ACCESS_STATUS');
Text::script('COM_EMUNDUS_ACCESS_USER');
Text::script('COM_EMUNDUS_ACCESS_EVALUATION');
Text::script('COM_EMUNDUS_ACCESS_EXPORT_EXCEL');
Text::script('COM_EMUNDUS_ACCESS_EXPORT_ZIP');
Text::script('COM_EMUNDUS_ACCESS_EXPORT_PDF');
Text::script('COM_EMUNDUS_EXPORTS_EXPORT_AS_CSV_TEMPLATE');
Text::script('COM_EMUNDUS_ACCESS_MAIL_APPLICANT');
Text::script('COM_EMUNDUS_ACCESS_MAIL_EVALUATOR');
Text::script('COM_EMUNDUS_ACCESS_MAIL_GROUP');
Text::script('COM_EMUNDUS_ACCESS_MAIL_EXPERTS');
Text::script('COM_EMUNDUS_ACCESS_MAIL_ADDRESS');
Text::script('COM_EMUNDUS_ACCESS_COMMENT_FILE');
Text::script('COM_EMUNDUS_ACCESS_ACCESS_FILE');
Text::script('COM_EMUNDUS_CONFIRM_DELETE_FILE');
Text::script('COM_EMUNDUS_SHOW_ELEMENTS');
Text::script('COM_EMUNDUS_CHOOSE_PRG');
Text::script('COM_EMUNDUS_CHOOSE_CAMP');
Text::script('COM_EMUNDUS_CHOOSE_PRG_DEFAULT');
Text::script('COM_EMUNDUS_CHOOSE_FORM_ELEM');
Text::script('COM_EMUNDUS_CHOOSE_EVAL_FORM_ELEM');
Text::script('COM_EMUNDUS_CHOOSEN_FORM_ELEM');
Text::script('COM_EMUNDUS_CHOOSE_ADMISSION_FORM_ELEM');
Text::script('COM_EMUNDUS_CHOOSEN_ADMISSION_FORM_ELEM');
Text::script('COM_EMUNDUS_CHOOSE_OTHER_ADMISSION_ELTS');
Text::script('COM_EMUNDUS_CHOOSE_DECISION_FORM_ELEM');
Text::script('COM_EMUNDUS_CHOOSEN_DECISION_FORM_ELEM');
Text::script('COM_EMUNDUS_CHOOSE_OTHER_COL');
Text::script('COM_EMUNDUS_PHOTO');
Text::script('COM_EMUNDUS_FORMS');
Text::script('COM_EMUNDUS_ATTACHMENT');
Text::script('COM_EMUNDUS_ASSESSMENT');
Text::script('COM_EMUNDUS_COMMENT');
Text::script('COM_EMUNDUS_COMMENTS');
Text::script('COM_EMUNDUS_FILES_CANNOT_GET_COMMENTS');
Text::script('COM_EMUNDUS_FILES_CANNOT_SAVE_COMMENT');
Text::script('COM_EMUNDUS_FILES_COMMENT_EMPTY');
Text::script('COM_EMUNDUS_ACCESS_COMMENT_FILE_CREATE');
Text::script('COM_EMUNDUS_EXCEL_GENERATION');
Text::script('COM_EMUNDUS_CHOOSE_EXTRACTION_METHODE');
Text::script('COM_EMUNDUS_CHOOSE_EXTRACTION_METHODE_AGGREGATE_DISTINCT');
Text::script('COM_EMUNDUS_CHOOSE_EXTRACTION_METHODE_AGGREGATE');
Text::script('COM_EMUNDUS_CHOOSE_EXTRACTION_METHODE_LEFTJOIN');
Text::script('COM_EMUNDUS_DOWNLOAD_EXTRACTION');
Text::script('COM_EMUNDUS_EXPORTS_ZIP_GENERATION');
Text::script('COM_EMUNDUS_DOWNLOAD_ZIP');
Text::script('COM_EMUNDUS_PUBLISH');
Text::script('COM_EMUNDUS_COPY_FILE');
Text::script('COM_EMUNDUS_SHARE_FILE');
Text::script('COM_EMUNDUS_FILTERS_PLEASE_SELECT_FILTER');
Text::script('DELETE');
Text::script('COM_EMUNDUS_ACTIONS_DELETE');
Text::script('COM_EMUNDUS_FILTERS_FILTER_SAVED');
Text::script('COM_EMUNDUS_FILTERS_FILTER_DELETED');
Text::script('COM_EMUNDUS_ERROR_SQL_ERROR');
Text::script('COM_EMUNDUS_FORM_TITLE');
Text::script('COM_EMUNDUS_FORM_GROUP');
Text::script('COM_EMUNDUS_TO_UPPER_CASE');
Text::script('COM_EMUNDUS_ASSOCIATED_GROUPS');
Text::script('COM_EMUNDUS_ASSOCIATED_USERS');
Text::script('COM_EMUNDUS_EVALUATIONS_OVERALL');
Text::script('COM_EMUNDUS_CHOOSE_EXTRACTION_OPTION');
Text::script('COM_EMUNDUS_CHOOSE_OTHER_OPTION');
Text::script('COM_EMUNDUS_EXPORTS_GENERATE_ZIP');
Text::script('COM_EMUNDUS_ACTIONS_CANCEL');
Text::script('COM_EMUNDUS_OK');
Text::script('COM_EMUNDUS_ACTIONS_BACK');
Text::script('COM_EMUNDUS_USERNAME');
Text::script('COM_EMUNDUS_FORM_YEARS_OLD');
Text::script('ID');
Text::script('COM_EMUNDUS_ACTIONS_ALL');
Text::script('COM_EMUNDUS_IN');
Text::script('COM_EMUNDUS_SELECT_HERE');
Text::script('SELECT_HERE');
Text::script('COM_EMUNDUS_FILTERS_CHECK_ALL_ALL');
Text::script('COM_EMUNDUS_FILES_SAVE_FILTER');
Text::script('COM_EMUNDUS_FILES_ENTER_HERE');
Text::script('COM_EMUNDUS_ONBOARD_BUILDER_CURRENCY_OPTIONS');
Text::script('COM_EMUNDUS_ONBOARD_TYPE_CURRENCY');
Text::script('COM_EMUNDUS_ONBOARD_BUILDER_CURRENCY_ALL_OPTIONS');
Text::script('COM_EMUNDUS_ONBOARD_BUILDER_CURRENCY_CURRENCY');
Text::script('COM_EMUNDUS_ONBOARD_BUILDER_CURRENCY_THOUSAND_SEPARATOR');
Text::script('COM_EMUNDUS_ONBOARD_BUILDER_CURRENCY_DECIMAL_SEPARATOR');
Text::script('COM_EMUNDUS_ONBOARD_BUILDER_CURRENCY_DECIMAL_NUMBERS');
Text::script('COM_EMUNDUS_ONBOARD_BUILDER_CURRENCY_REGEX');
Text::script('COM_EMUNDUS_CAMPAIGN_MORE');
Text::script('COM_EMUNDUS_CAMPAIGN_MORE_DESC');
Text::script('COM_EMUNDUS_CAMPAIGN_STEPS');
Text::script('COM_EMUNDUS_CAMPAIGN_STEPS_DESC');
Text::script('COM_EMUNDUS_CAMPAIGN_STEP_START_DATE');
Text::script('COM_EMUNDUS_CAMPAIGN_STEP_END_DATE');
Text::script('COM_EMUNDUS_CAMPAIGNS_INFINITE_STEP');

Text::script('USERNAME_Q');
Text::script('ID_Q');
Text::script('ALL_Q');
Text::script('LAST_NAME_Q');
Text::script('FIRST_NAME_Q');
Text::script('FNUM_Q');
Text::script('EMAIL_Q');

Text::script('BACK');

Text::script('COM_EMUNDUS_LOADING');
Text::script('TITLE');
Text::script('COM_EMUNDUS_COMMENTS_ADD_COMMENT');
Text::script('COM_EMUNDUS_COMMENTS_ERROR_PLEASE_COMPLETE');
Text::script('COM_EMUNDUS_COMMENTS_ENTER_COMMENT');
Text::script('COM_EMUNDUS_COMMENTS_SENT');
Text::script('COM_EMUNDUS_FILES_ADD_COMMENT');
Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS_COMMENTS');
Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS_COMMENTS_DESC');
Text::script('COM_EMUNDUS_FILES_COMMENT_TITLE');
Text::script('COM_EMUNDUS_FILES_COMMENT_BODY');
Text::script('COM_EMUNDUS_FILES_VALIDATE_COMMENT');
Text::script('COM_EMUNDUS_FILES_COMMENT_DELETE');
Text::script('COM_EMUNDUS_COMMENTS_VISIBLE_PARTNERS');
Text::script('COM_EMUNDUS_COMMENTS_VISIBLE_ALL');
Text::script('COM_EMUNDUS_COMMENTS_ANSWERS');
Text::script('COM_EMUNDUS_COMMENTS_ANSWER');
Text::script('COM_EMUNDUS_COMMENTS_ADD_COMMENT_ON');
Text::script('COM_EMUNDUS_COMMENTS_CANCEL');
Text::script('COM_EMUNDUS_COMMENTS_UPDATE_COMMENT');
Text::script('COM_EMUNDUS_COMMENTS_ADD_COMMENT_PLACEHOLDER');
Text::script('COM_EMUNDUS_COMMENTS_CLOSE_COMMENT_THREAD');
Text::script('COM_EMUNDUS_COMMENTS_REOPEN_COMMENT_THREAD');
Text::script('COM_EMUNDUS_COMMENTS_SEARCH');
Text::script('COM_EMUNDUS_COMMENTS_ALL_THREAD');
Text::script('COM_EMUNDUS_COMMENTS_OPENED_THREAD');
Text::script('COM_EMUNDUS_COMMENTS_CLOSED_THREAD');
Text::script('COM_EMUNDUS_COMMENTS_EDITED');
Text::script('COM_EMUNDUS_COMMENTS_NO_COMMENTS');
Text::script('COM_EMUNDUS_COMMENTS_VISIBLE_ALL_OPT');
Text::script('COM_EMUNDUS_COMMENTS_CONFIRM_DELETE');
Text::script('COM_EMUNDUS_COMMENTS_CONFIRM_DELETE_TEXT');
Text::script('COM_EMUNDUS_COMMENTS_ADD_GLOBAL_COMMENT');

Text::script('COM_EMUNDUS_ACCESS_SHARE_PROGRESS');
Text::script('COM_EMUNDUS_ACCESS_SHARE_SUCCESS');
Text::script('COM_EMUNDUS_ACCESS_ERROR_REQUIRED');
Text::script('ERROR');
Text::script('COM_EMUNDUS_EXPORTS_DOWNLOAD_PDF');
Text::script('COM_EMUNDUS_EXPORTS_FORMS_PDF');
Text::script('COM_EMUNDUS_EXPORTS_ATTACHMENT_PDF');
Text::script('COM_EMUNDUS_EXPORTS_EVAL_STEPS_PDF');
Text::script('COM_EMUNDUS_EXPORTS_ASSESSMENT_PDF');
Text::script('JYES');
Text::script('JNO');
Text::script('COM_EMUNDUS_PLEASE_SELECT');
Text::script('COM_EMUNDUS_PLEASE_SELECT_MULTIPLE');
Text::script('COM_EMUNDUS_EXPORTS_CHANGE_STATUS');
Text::script('COM_EMUNDUS_EXPORTS_EXPORT_SET_TAG');
Text::script('COM_EMUNDUS_ATTACHMENTS_YOU_MUST_SELECT_ATTACHMENT');
Text::script('COM_EMUNDUS_ATTACHMENTS_AGGREGATIONS');
Text::script('COM_EMUNDUS_LETTERS_FILES_GENERATED');
Text::script('FILE_NAME');
Text::script('COM_EMUNDUS_ATTACHMENTS_LINK_TO_DOWNLOAD');
Text::script('LINK_TO_DOWNLOAD');
Text::script('COM_EMUNDUS_ATTACHMENTS_ALL_IN_ONE_DOC');
Text::script('COM_EMUNDUS_EXPORTS_PDF_TAGS');
Text::script('COM_EMUNDUS_EXPORTS_PDF_STATUS');
Text::script('COM_EMUNDUS_EXPORTS_ADD_HEADER');
Text::script('COM_EMUNDUS_TAGS_DELETE_TAGS');
Text::script('COM_EMUNDUS_TAGS_CATEGORIES');
Text::script('COM_EMUNDUS_TAGS_DELETE_TAGS_CONFIRM');
Text::script('COM_EMUNDUS_TAGS_DELETE_SUCCESS');
Text::script('COM_EMUNDUS_FILTERS_CONFIRM_DELETE_FILTER');
Text::script('COM_EMUNDUS_APPLICATION_ADD_TAGS');
Text::script('COM_EMUNDUS_FILES_PLEASE_SELECT_TAG');
Text::script('COM_EMUNDUS_SELECT_SOME_OPTIONS');
Text::script('COM_EMUNDUS_SELECT_AN_OPTION');
Text::script('COM_EMUNDUS_SELECT_NO_RESULT');
Text::script('VALID');
Text::script('INVALID');
Text::script('COM_EMUNDUS_ATTACHMENTS_UNCHECKED');
Text::script('COM_EMUNDUS_EXPORTS_SELECT_AT_LEAST_ONE_FILE');
Text::script('COM_EMUNDUS_EXPORTS_INFORMATION');
Text::script('COM_EMUNDUS_FILTERS_YOU_HAVE_SELECT');
Text::script('COM_EMUNDUS_FILTERS_SELECT_ALL');
Text::script('COM_EMUNDUS_FILES_FILE');
Text::script('COM_EMUNDUS_FILES_FILES');
Text::script('COM_EMUNDUS_FILES_SELECT_ALL_FILES');
Text::script('COM_EMUNDUS_FILES_OR_CONNECTOR');
Text::script('COM_EMUNDUS_FILES_UNSELECT_ALL_FILES');
Text::script('COM_EMUNDUS_FILES_UNSELECT_ALL_FILES_2');
Text::script('COM_EMUNDUS_USERS_SELECT_USER');
Text::script('COM_EMUNDUS_USERS_SELECT_USERS');
Text::script('COM_EMUNDUS_APPLICATION_WARNING_CHANGE_STATUS');
Text::script('COM_EMUNDUS_APPLICATION_WARNING_CHANGE_STATUS_OF_NB_FILES');
Text::script('COM_EMUNDUS_APPLICATION_WARNING_CHANGE_STATUS_OF_NB_FILES_2');
Text::script('COM_EMUNDUS_APPLICATION_WARNING_CHANGE_STATUS_OF_NB_FILES_3');
Text::script('COM_EMUNDUS_APPLICATION_MAIL_CHANGE_STATUT_INFO');
Text::script('COM_EMUNDUS_APPLICATION_VALIDATE_CHANGE_STATUT');
Text::script('COM_EMUNDUS_APPLICATION_CANCEL_CHANGE_STATUT');
Text::script('COM_EMUNDUS_APPLICATION_DOCUMENT_PRINTED_ON');
Text::script('COM_EMUNDUS_APPLICATION_APPLICANT');
Text::script('COM_EMUNDUS_CHECKLIST_PROFILE_FILES_FOUND');
Text::script('COM_EMUNDUS_CHECKLIST_PROFILE_FILES_FOUND_TEXT');
Text::script('COM_EMUNDUS_CHECKLIST_PROFILE_FILES_FOUND_TEXT_2');
Text::script('COM_EMUNDUS_CHECKLIST_PROFILE_FILES_UPLOAD');
Text::script('COM_EMUNDUS_CHECKLIST_PROFILE_ATTACHMENT_FOUND');
Text::script('COM_EMUNDUS_CHECKLIST_PROFILE_ATTACHMENT_FOUND_TEXT');
Text::script('COM_EMUNDUS_CHECKLIST_PROFILE_ATTACHMENT_FOUND_UPDATE');
Text::script('COM_EMUNDUS_CHECKLIST_PROFILE_ATTACHMENT_FOUND_CONTINUE_WITHOUT_UPDATE');
Text::script('COM_EMUNDUS_USERS_MY_DOCUMENTS_LOAD');
Text::script('COM_EMUNDUS_ACCOUNT_INFORMATIONS');
Text::script('COM_EMUNDUS_ACCOUNT_PERSONAL_DETAILS');
Text::script('COM_EMUNDUS_USERS_DEFAULT_LANGAGE');
Text::script('COM_EMUNDUS_USERS_NATIONALITY');
Text::script('COM_EMUNDUS_USERS_EDIT_PROFILE_PASSWORD');
Text::script('COM_EMUNDUS_PUBLISH_UPDATE');
Text::script('COM_EMUNDUS_FILES_FILTER');
Text::script('COM_EMUNDUS_FILES_APPLY_FILTER');
Text::script('COM_EMUNDUS_LETTERS_PROGRESSING');

// view user
Text::script('COM_EMUNDUS_USERS_ERROR_NOT_A_VALID_EMAIL');
Text::script('COM_EMUNDUS_USERS_ERROR_NOT_A_VALID_LOGIN_MUST_NOT_CONTAIN_SPECIAL_CHARACTER');
Text::script('REQUIRED');
Text::script('COM_EMUNDUS_SELECT_A_VALUE');
Text::script('GROUP_CREATED');
Text::script('COM_EMUNDUS_USERS_USER_CREATED');
Text::script('LOGIN_NOT_GOOD');
Text::script('MAIL_NOT_GOOD');
Text::script('COM_EMUNDUS_USERS_ARE_YOU_SURE_TO_DELETE_USERS');
Text::script('COM_EMUNDUS_USERS_DELETED');
Text::script('COM_EMUNDUS_ACCESS_SHARE_PROGRESS');
Text::script('COM_EMUNDUS_APPLICATION_SENT');
Text::script('COM_EMUNDUS_LETTERS_FILES_GENERATED');
Text::script('COM_EMUNDUS_STATE');
Text::script('COM_EMUNDUS_PROFILE_SWITCH_PROFILE');
Text::script('COM_EMUNDUS_PROFILE_PROFILE_CHOSEN');
Text::script('COM_EMUNDUS_USERS_ARE_YOU_SURE_TO_REGENERATE_PASSWORD');

//Export Excel
Text::script('COM_EMUNDUS_ADD_DATA_TO_CSV');
Text::script('COM_EMUNDUS_LIMIT_POST_SERVER');
Text::script('COM_EMUNDUS_ERROR_XLS');
Text::script('COM_EMUNDUS_ERROR_CSV_CAPACITY');
Text::script('COM_EMUNDUS_XLS_GENERATION');
Text::script('COM_EMUNDUS_EXPORT_FINISHED');
Text::script('COM_EMUNDUS_ERROR_CAPACITY_XLS');
Text::script('COM_EMUNDUS_EXPORT_EXCEL');
Text::script('COM_EMUNDUS_CREATE_CSV');
Text::script('COM_EMUNDUS_ATTACHMENTS_DOWNLOAD');
Text::script('COM_EMUNDUS_ATTACHMENTS_DOWNLOAD_READY');
Text::script('EXPECTED_GRADUATION_DATE');
Text::script('GRADE_POINT_AVERAGE');
Text::script('GRADUATION_DATE');
Text::script('TYPE_OF_DEGREE');
Text::script('INSTITUTION');
Text::script('FIELD');
Text::script('OTHER_INFORMATION');
Text::script('ADDRESS_FOR_CORRESPONDANCE');
Text::script('PERMANENT_ADDRESS_FOR_CORRESPONDANCE');
Text::script('CRIMINAL_DETAILS');
Text::script('CRIMINAL_CHARGES');
Text::script('PHYSICAL_DETAILS');
Text::script('PHYSICAL_DISABILITY');
Text::script('MOBILE');
Text::script('TELEPHONE');
Text::script('COUNTRY');
Text::script('COM_EMUNDUS_STATE');
Text::script('ZIPCODE');
Text::script('CITY');
Text::script('STREET');
Text::script('PERSONAL_DETAILS');
Text::script('NUMBER_OF_CHILDREN');
Text::script('ACCOMPANIED');
Text::script('DISABLED');
Text::script('COM_EMUNDUS_FORMS_NATIONALITY');
Text::script('BIRTH_PLACE');
Text::script('DATE_OF_BIRTH');
Text::script('MARITAL_STATUS');
Text::script('GENDER');
Text::script('MAIDEN_NAME');
Text::script('BACHELOR_DEGREE_ENGINEERING_DEGREE');
Text::script('OTHER_EDUCATION_OR_MASTER_DEGREE');
Text::script('LANGUAGE');
Text::script('MOTHER_TONGUE');
Text::script('DEGREE_LANGUAGE');
Text::script('ENGLISH');
Text::script('ENGLISH_READING');
Text::script('ENGLISH_SPEAKING');
Text::script('ENGLISH_WRITING');
Text::script('ENGLISH_TEST_SCORE');
Text::script('ENGLISH_TEST_NAME');
Text::script('OTHER_LANGUAGE');
Text::script('OTHER_LANGUAGE');
Text::script('OTHER_LANGUAGES');
Text::script('LANGUAGE_READING');
Text::script('LANGUAGE_WRITING');
Text::script('LANGUAGE_SPEAKING');
Text::script('OTHER_TEST_NAME');
Text::script('OTHER_TEST_SCORE');
Text::script('OTHER_TEST_DATE');
Text::script('INETRNSHIP');
Text::script('DURATION');
Text::script('COMPANY_OR_ACADEMIC_INSTITUTION');
Text::script('WORK_DESCRIPTION');
Text::script('FULL_TIME_OR_PART_TIME_ACTIVITY');
Text::script('PROFESSIONAL_EXPERIENCE');
Text::script('FIRST_REFEREE');
Text::script('SECOND_REFEREE');
Text::script('FIRST_NAME');
Text::script('LAST_NAME');
Text::script('UNIVERSITY_ORGANISATION');
Text::script('FAX_NUMBER');
Text::script('WEBSITE');
Text::script('COM_EMUNDUS_EMAIL');
Text::script('POSITION');
Text::script('APPLICATION_SCHOLARSHIP');
Text::script('ERASMUS_MUNDUS_SCHOLARSHIP');
Text::script('CATEGORY_B_DETAILS');
Text::script('FINANCIAL_INFORMATION');
Text::script('SOURCE_FUNDING');
Text::script('HOW_DID_YOU_LEARNED_ABOUT_THIS_MASTER');
Text::script('SELECT_ONE');
Text::script('FIRST_PREFERENCE');
Text::script('SECONDE_PREFERENCE');
Text::script('DID_YOU_APPLY_FOR_ANOTHER_PROGRAM');
Text::script('PROGRAM_NAME');
Text::script('CHOOSE_YOUR_OPTION');
Text::script('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS_OTHERS_EVAL');
Text::script('COM_EMUNDUS_EXPORTS_GENERATE_EXCEL');
Text::script('COM_EMUNDUS_USER_REGENERATE_PASSWORD_SUCCESS');
Text::script('COM_EMUNDUS_EXPORTS_SELECT_AT_LEAST_ONE_INFORMATION');

//Export PDF
Text::script('COM_EMUNDUS_FNUM');
Text::script('COM_EMUNDUS_ATTACHMENTS_FILES_UPLOADED');
Text::script('COM_EMUNDUS_EXPORTS_PDF_GENERATION');
Text::script('COM_EMUNDUS_EXPORTS_CREATE_PDF');
Text::script('COM_EMUNDUS_EXPORTS_ADD_FILES_TO_PDF');
Text::script('COM_EMUNDUS_EXPORT_FINISHED');
Text::script('COM_EMUNDUS_ERROR_EXPORTS_NBFILES_CAPACITY');
Text::script('COM_EMUNDUS_ERROR_CAPACITY_PDF');
Text::script('COM_EMUNDUS_EXPORTS_DECISION_PDF');
Text::script('COM_EMUNDUS_EXPORTS_ADMISSION_PDF');
Text::script('COM_EMUNDUS_EXPORTS_GENERATE_PDF');
Text::script('COM_EMUNDUS_EXPORTS_PDF_OPTIONS');
Text::script('FILES_UPLOADED');
Text::script('COM_EMUNDUS_TAGS');
Text::script('COM_EMUNDUS_EXPORTS_FILE_NOT_FOUND');
Text::script('COM_EMUNDUS_EXPORTS_FILE_NOT_DEFINED');
Text::script('ID_CANDIDAT');
Text::script('FNUM');
Text::script('COM_EMUNDUS_APPLICATION_SENT_ON');
Text::script('DOCUMENT_PRINTED_ON');
Text::script('COM_EMUNDUS_USERS_ARE_YOU_SURE_TO_DELETE_USERS');
Text::script('COM_EMUNDUS_USERS_EDIT_PROFILE_NO_FORM_FOUND');
Text::script('JCANCEL');
Text::script('JACTION_DELETE');
Text::script('COM_EMUNDUS_USERS_EDIT_PROFILE_NO_FORM_FOUND');
Text::script('COM_EMUNDUS_WANT_RESET_PASSWORD');

// Submit application
Text::script('COM_EMUNDUS_CONGRATULATIONS');
Text::script('COM_EMUNDUS_YOUR_FILE_HAS_BEEN_SENT');

//Export ZIP
Text::script('COM_EMUNDUS_EXPORTS_ZIP_GENERATION');
Text::script('COM_EMUNDUS_EXPORTS_CREATE_ZIP');
Text::script('COM_EMUNDUS_EXPORTS_CONCAT_ATTACHMENTS_WITH_FORMS');

//WHO'S WHO
Text::script('COM_EMUNDUS_TROMBI_GENERATE');
Text::script('COM_EMUNDUS_TROMBI_DOWNLOAD');
Text::script('COM_EMUNDUS_TROMBINOSCOPE_GENERATE_FAILED');

// Email to applicant
Text::script('COM_EMUNDUS_EMAILS_SEND_CUSTOM_EMAIL');
Text::script('COM_EMUNDUS_EMAILS_ERROR_GETTING_PREVIEW');
Text::script('COM_EMUNDUS_EMAILS_EMAIL_PREVIEW');
Text::script('COM_EMUNDUS_EMAILS_EMAIL_PREVIEW_BEFORE_SEND');
Text::script('COM_EMUNDUS_EMAILS_NO_EMAILS_SENT');
Text::script('COM_EMUNDUS_EMAILS_EMAILS_SENT');
Text::script('COM_EMUNDUS_EMAILS_FAILED');
Text::script('COM_EMUNDUS_EMAILS_SEND_FAILED');
Text::script('COM_EMUNDUS_MAILS_SEND_TO');
Text::script('COM_EMUNDUS_MAILS_EMAIL_SENDING');
Text::script('COM_EMUNDUS_EMAILS_CANCEL_EMAIL');

//view application layout share
Text::script('COM_EMUNDUS_ACCESS_ARE_YOU_SURE_YOU_WANT_TO_REMOVE_THIS_ACCESS');

//view ametys
Text::script('COM_EMUNDUS_CANNOT_RETRIEVE_EMUNDUS_PROGRAMME_LIST');
Text::script('COM_EMUNDUS_RETRIEVE_AMETYS_STORED_PROGRAMMES');
Text::script('COM_EMUNDUS_RETRIEVE_EMUNDUS_STORED_PROGRAMMES');
Text::script('COM_EMUNDUS_COMPARE_DATA');
Text::script('COM_EMUNDUS_ADD_DATA');
Text::script('COM_EMUNDUS_SYNC_DONE');
Text::script('COM_EMUNDUS_NO_SYNC_NEEDED');
Text::script('COM_EMUNDUS_CANNOT_RETRIEVE_EMUNDUS_PROGRAMME_LIST');
Text::script('COM_EMUNDUS_DATA_TO_ADD');
Text::script('COM_EMUNDUS_ERROR_MISSING_FORM_DATA');

Text::script('CONFIRM_PASSWORD');

Text::script('JGLOBAL_SELECT_AN_OPTION');

//Award list
Text::script('COM_EMUNDUS_VOTE_NON_ACCEPTED');
Text::script('COM_EMUNDUS_VOTE_ACCEPTED');


//Messenger
Text::script('COM_EMUNDUS_MESSENGER_TITLE');
Text::script('COM_EMUNDUS_MESSENGER_SEND_DOCUMENT');
Text::script('COM_EMUNDUS_MESSENGER_ASK_DOCUMENT');
Text::script('COM_EMUNDUS_MESSENGER_DROP_HERE');
Text::script('COM_EMUNDUS_PLEASE_SELECT');
Text::script('COM_EMUNDUS_MESSENGER_SEND');
Text::script('COM_EMUNDUS_MESSENGER_WRITE_MESSAGE');
Text::script('COM_EMUNDUS_MESSENGER_TYPE_ATTACHMENT');

// GENERATE LETTER
Text::script('COM_EMUNDUS_EXPORT_MODE');
Text::script('COM_EMUNDUS_EXPORT_BY_CANDIDAT');
Text::script('COM_EMUNDUS_EXPORT_BY_DOCUMENT');
Text::script('COM_EMUNDUS_EXPORT_BY_FILES');
Text::script('COM_EMUNDUS_PDF_MERGE');
Text::script('COM_EMUNDUS_CANDIDAT_EXPORT_TOOLTIP');
Text::script('COM_EMUNDUS_DOCUMENT_EXPORT_TOOLTIP');
Text::script('COM_EMUNDUS_CANDIDAT_MERGE_TOOLTIP');
Text::script('COM_EMUNDUS_DOCUMENT_MERGE_TOOLTIP');
Text::script('COM_EMUNDUS_SELECT_IMPOSSIBLE');
Text::script('COM_EMUNDUS_MESSENGER_ATTACHMENTS');
Text::script('GENERATE_DOCUMENT');
Text::script('DOWNLOAD_DOCUMENT');
Text::script('NO_LETTER_FOUND');
Text::script('AFFECTED_CANDIDATS');
Text::script('GENERATED_DOCUMENTS_LABEL');
Text::script('GENERATED_DOCUMENTS_COUNT');
Text::script('CANDIDAT_GENERATED');
Text::script('DOCUMENT_GENERATED');
Text::script('CANDIDATE');
Text::script('DOCUMENT_NAME');
Text::script('CANDIDAT_INFORMATION');
Text::script('CANDIDAT_STATUS');
Text::script('EMAIL_SUBJECT');
Text::script('EMAIL_BODY');
Text::script('ATTACHMENT_LETTER');
Text::script('MESSAGE_INFORMATION');
Text::script('EMAIL_FAILED');
Text::script('CAMPAIGN_YEAR');
Text::script('COM_EMUNDUS_CAMPAIGN_UNSAVED_CHANGES');
Text::script('CANDIDATE_EMAIL');
Text::script('EMAIL_TAGS');
Text::script('SEND_EMAIL_TOOLTIPS');
Text::script('COM_EMUNDUS_UNAVAILABLE_FEATURES');
Text::script('COM_EMUNDUS_EMAILS_SENDING_EMAILS');
Text::script('COM_EMUNDUS_AURION_EXPORT');
Text::script('EXPORT_CHANGE_STATUS');
Text::script('EXPORT_SET_TAG');
Text::script('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS');
Text::script('EVALUATION_PERIOD_NOT_STARTED');
Text::script('EVALUATION_PERIOD_PASSED');


// EXPORT EXCEL MODEL
Text::script('COM_EMUNDUS_CHOOSE_LETTER');
Text::script('COM_EMUNDUS_MODEL_ERR');

// UPLOADED IMAGE IS TOO SMALL
Text::script('COM_EMUNDUS_ERROR_IMAGE_TOO_SMALL');

Text::script('COM_EMUNDUS_EMAILS_CC_PLACEHOLDER');
Text::script('COM_EMUNDUS_EMAILS_BCC_PLACEHOLDER');

// VUE ATTACHMENT
Text::script('SEARCH');
Text::script('COM_EMUNDUS_ATTACHMENTS_FILE_NAME');
Text::script('COM_EMUNDUS_ATTACHMENTS_DESCRIPTION');
Text::script('STATUS');
Text::script('COM_EMUNDUS_ATTACHMENTS_REPLACE');
Text::script('EXPORT');
Text::script('DELETE_SELECTED_ATTACHMENTS');
Text::script('CONFIRM_DELETE_SELETED_ATTACHMENTS');
Text::script('SELECT_CATEGORY');
Text::script('APPLICATION_FORM');
Text::script('UPLOAD_BY_APPLICANT');
Text::script('COM_EMUNDUS_ATTACHMENTS_SEND_DATE');
Text::script('COM_EMUNDUS_ATTACHMENTS_MODIFICATION_DATE');
Text::script('COM_EMUNDUS_ATTACHMENTS_MODIFIED_BY');
Text::script('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_PREVIEW_INCOMPLETE_MSG');
Text::script('COM_EMUNDUS_ATTACHMENTS_DOCUMENT_TYPE');
Text::script('COM_EMUNDUS_ATTACHMENTS_MINI_DESCRIPTION');
Text::script('COM_EMUNDUS_ATTACHMENTS_CAMPAIGN_ID');
Text::script('COM_EMUNDUS_ATTACHMENTS_CATEGORY');
Text::script('COM_EMUNDUS_ATTACHMENTS_SAVE');
Text::script('COM_EMUNDUS_ATTACHMENTS_FILTER_ACTION');
Text::script('COM_EMUNDUS_ATTACHMENTS_REPLACE');
Text::script('COM_EMUNDUS_ATTACHMENTS_NO_ATTACHMENTS_FOUND');
Text::script('COM_EMUNDUS_ATTACHMENTS_WAITING');
Text::script('COM_EMUNDUS_ATTACHMENTS_REFRESH_TITLE');
Text::script('COM_EMUNDUS_ATTACHMENTS_DELETE_TITLE');
Text::script('COM_EMUNDUS_ATTACHMENTS_CLOSE');
Text::script('COM_EMUNDUS_ATTACHMENTS_USER_NOT_FOUND');
Text::script('COM_EMUNDUS_ATTACHMENTS_UPLOADED_BY');
Text::script('COM_EMUNDUS_ATTACHMENTS_CHECK');
Text::script('COM_EMUNDUS_ATTACHMENTS_WARNING');
Text::script('COM_EMUNDUS_ATTACHMENTS_PERMISSIONS');
Text::script('COM_EMUNDUS_ATTACHMENTS_CAN_BE_VIEWED');
Text::script('COM_EMUNDUS_ATTACHMENTS_CAN_BE_DELETED');
Text::script('COM_EMUNDUS_ATTACHMENTS_UNAUTHORIZED_ACTION');
Text::script('COM_EMUNDUS_ATTACHMENTS_PERMISSION_VIEW');
Text::script('COM_EMUNDUS_ATTACHMENTS_PERMISSION_DELETE');
Text::script('COM_EMUNDUS_ATTACHMENTS_COMPLETED');
Text::script('COM_EMUNDUS_ATTACHMENTS_SYNC');
Text::script('COM_EMUNDUS_ATTACHMENTS_SYNC_TITLE');
Text::script('COM_EMUNDUS_ATTACHMENTS_SYNC_WRITE');
Text::script('COM_EMUNDUS_ATTACHMENTS_SYNC_READ');
Text::script('COM_EMUNDUS_ONBOARD_DOCUMENTS');
Text::script('COM_EMUNDUS_ATTACHMENTS_NAME');
Text::script('COM_EMUNDUS_ATTACHMENTS_DESCRIPTION');
Text::script('COM_EMUNDUS_ATTACHMENTS_OPEN_IN_GED');
Text::script('COM_EMUNDUS_ATTACHMENTS_EXPORT_LINK');
Text::script('COM_EMUNDUS_ATTACHMENTS_SELECT_CATEGORY');
Text::script('COM_EMUNDUS_EMAILS_SELECT_CATEGORY');
Text::script('COM_EMUNDUS_EXPORTS_EXPORT');
Text::script('COM_EMUNDUS_EXPORTS_EXPORT_TO_ZIP');
Text::script('COM_EMUNDUS_ACTIONS_SEARCH');
Text::script('COM_EMUNDUS_TROMBINOSCOPE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_NEW_DOCUMENT');

Text::script('COM_EMUNDUS_VIEW_FORM_SELECT_PROFILE');
Text::script('COM_EMUNDUS_VIEW_FORM_OTHER_PROFILES');
Text::script('COM_EMUNDUS_FILES_ARE_EDITED_BY_OTHER_USERS');
Text::script('COM_EMUNDUS_FILES_IS_EDITED_BY_OTHER_USER');
Text::script('COM_EMUNDUS_FILE_EDITED_BY_ANOTHER_USER');
Text::script('COM_EMUNDUS_LIST_RETRIEVED');
Text::script('COM_EMUNDUS_ERROR_CANNOT_RETRIEVE_LIST');

// GOTENBERG EXPORT FAILED
Text::script('COM_EMUNDUS_EXPORT_FAILED');

// LOGS
Text::script('COM_EMUNDUS_LOGS_DOWNLOAD');
Text::script('COM_EMUNDUS_LOGS_DOWNLOAD_ERROR');
Text::script('COM_EMUNDUS_LOGS_EXPORT');

Text::script('COM_EMUNDUS_CRUD_FILTER_LABEL');
Text::script('COM_EMUNDUS_LOG_READ_TYPE');
Text::script('COM_EMUNDUS_LOG_CREATE_TYPE');
Text::script('COM_EMUNDUS_LOG_UPDATE_TYPE');
Text::script('COM_EMUNDUS_LOG_DELETE_TYPE');
Text::script('COM_EMUNDUS_NO_ACTION_FOUND');
Text::script('COM_EMUNDUS_NO_LOG_USERS_FOUND');
Text::script('COM_EMUNDUS_NO_LOGS_FILTER_FOUND');

Text::script('COM_EMUNDUS_CRUD_FILTER_PLACEHOLDER');
Text::script('COM_EMUNDUS_TYPE_FILTER_PLACEHOLDER');
Text::script('COM_EMUNDUS_ACTOR_FILTER_PLACEHOLDER');
Text::script('COM_EMUNDUS_ACCESS_FORM_READ');
Text::script('COM_EMUNDUS_LOGS_FILTERS_FOUND_RESULTS');

Text::script('COM_EMUNDUS_CRUD_LOG_FILTER_HINT');
Text::script('COM_EMUNDUS_TYPES_LOG_FILTER_HINT');
Text::script('COM_EMUNDUS_ACTOR_LOG_FILTER_HINT');

Text::script('COM_EMUNDUS_NO_LOGS_FILTERS_FOUND_RESULTS');

// ADD LABEL OF LOGS CATEGORY
Text::script('COM_EMUNDUS_ACCESS_FILE');                   # 1
Text::script('COM_EMUNDUS_ACCESS_ATTACHMENT');             # 4
Text::script('COM_EMUNDUS_ACCESS_EVALUATION');             # 5
Text::script('COM_EMUNDUS_ACCESS_EXPORT_EXCEL');           # 6
Text::script('COM_EMUNDUS_ACCESS_EXPORT_ZIP');             # 7
Text::script('COM_EMUNDUS_ACCESS_EXPORT_PDF');             # 8
Text::script('COM_EMUNDUS_ACCESS_MAIL_APPLICANT');         # 9
Text::script('COM_EMUNDUS_ACCESS_COMMENT_FILE');           # 10
Text::script('COM_EMUNDUS_ACCESS_ACCESS_FILE');            # 11
Text::script('COM_EMUNDUS_ACCESS_ACCESS_FILE_CREATE');     # 11
Text::script('COM_EMUNDUS_ACCESS_USER');                   # 12
Text::script('COM_EMUNDUS_ACCESS_STATUS');                 # 13
Text::script('COM_EMUNDUS_ACCESS_TAGS');                   # 14
Text::script('COM_EMUNDUS_ACCESS_MAIL_EVALUATOR');         # 15
Text::script('COM_EMUNDUS_ACCESS_MAIL_GROUP');             # 16
Text::script('COM_EMUNDUS_ACCESS_MAIL_EXPERT');            # 18
Text::script('COM_EMUNDUS_ACCESS_GROUPS');                 # 19
Text::script('COM_EMUNDUS_ADD_USER');                      # 20
Text::script('COM_EMUNDUS_ACTIVATE');                      # 21
Text::script('COM_EMUNDUS_DEACTIVATE');                    # 22
Text::script('COM_EMUNDUS_AFFECT');                        # 23
Text::script('COM_EMUNDUS_EDIT_USER');                     # 24
Text::script('COM_EMUNDUS_SHOW_RIGHT');                    # 25
Text::script('COM_EMUNDUS_DELETE_USER');                   # 26
Text::script('COM_EMUNDUS_ACCESS_LETTERS');                # 27
Text::script('COM_EMUNDUS_PUBLISH');                       # 28
Text::script('COM_EMUNDUS_DECISION');                      # 29
Text::script('COM_EMUNDUS_COPY_FILE');                     # 30
Text::script('COM_EMUNDUS_ACCESS_MULTI_LETTERS');          # 31
Text::script('COM_EMUNDUS_ADMISSION');                     # 32
Text::script('COM_EMUNDUS_EXTENAL_EXPORT');                # 33
Text::script('COM_EMUNDUS_INTERVIEW');                     # 34
Text::script('COM_EMUNDUS_FICHE_DE_SYNTHESE');             # 35
Text::script('COM_EMUNDUS_MESSENGER');                     # 36
Text::script('COM_EMUNDUS_ACCESS_LOGS');                   # 37

Text::script('COM_EMUNDUS_EDIT_COMMENT_BODY');
Text::script('COM_EMUNDUS_EDIT_COMMENT_TITLE');
Text::script('COM_EMUNDUS_FORM_BUILDER_DELETE_MODEL');
Text::script('COM_EMUNDUS_FORM_PAGE_MODELS');
Text::script('COM_EMUNDUS_FORM_MY_FORMS');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_ADDUSER');
Text::script('COM_EMUNDUS_ACTIONS_EDIT_USER');
Text::script('COM_EMUNDUS_USERS_ERROR_PLEASE_COMPLETE');
Text::script('COM_EMUNDUS_USERS_SHOW_USER_RIGHTS');
Text::script('COM_EMUNDUS_MAILS_SEND_EMAIL');
Text::script('COM_EMUNDUS_USERS_CREATE_GROUP');
Text::script('COM_EMUNDUS_USERS_AFFECT_USER');
Text::script('COM_EMUNDUS_USERS_AFFECT_GROUP_ERROR');
Text::script('COM_EMUNDUS_ERROR_OCCURED');
Text::script('COM_EMUNDUS_USERS_CREATE_USER_CONFIRM');
Text::script('COM_EMUNDUS_USERS_EDIT_USER_CONFIRM');
Text::script('COM_EMUNDUS_USERS_AFFECT_USER_CONFIRM');
Text::script('COM_EMUNDUS_MAIL_SEND_NEW');

// PASSWORD CHARACTER VALIDATION
Text::script('COM_EMUNDUS_PASSWORD_WRONG_FORMAT_TITLE');
Text::script('COM_EMUNDUS_PASSWORD_WRONG_FORMAT_DESCRIPTION');

// DELETE ADVANCED FILTERS
Text::script('COM_EMUNDUS_DELETE_ADVANCED_FILTERS');

Text::script('COM_EMUNDUS_MAIL_GB_BUTTON');

Text::script('COM_EMUNDUS_EMAIL_CURRENT_FILE');
Text::script('COM_EMUNDUS_EMAIL_ALL_FILES');
Text::script('COM_EMUNDUS_EMAIL_ON_FILE');

Text::script('COM_EMUNDUS_ERROR_INVALID_FILENAME');
Text::script('COM_EMUNDUS_ERROR_INVALID_FILETYPE');
Text::script('COM_EMUNDUS_ERROR_FILENAME_TOO_LONG');

Text::script('COM_EMUNDUS_GLOBAL_HISTORY');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_NO_HISTORY');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_DONE');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_PENDING');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_CANCELLED');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_TITLE');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_TEXT');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_YES');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_UPDATES');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_TYPE');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_LOG_DATE');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_BY');
Text::script('COM_EMUNDUS_GLOBAL_HISTORY_STATUS');

// Load translations for action log plugin
$actionlog_translation_tags = parse_ini_file(JPATH_ADMINISTRATOR.'/language/fr-FR/plg_actionlog_emundus.ini');
foreach ($actionlog_translation_tags as $tag => $translation) {
	Text::script($tag);
}

// Require specific controller if requested
if ($controller = $app->input->get('controller', '', 'WORD')) {
	$path = JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'controllers' . DS . $controller . '.php';
	if (file_exists($path)) {
		require_once $path;
	}
	else {
		$controller = '';
	}
}

// Create the controller
$classname  = 'EmundusController' . $controller;
$controller = new $classname();

$eMConfig = ComponentHelper::getParams('com_emundus');
$cdn      = $eMConfig->get('use_cdn', 1);

$name   = $app->input->get('view', '', 'CMD');
$task   = $app->input->get('task', '', 'CMD');
$format = $app->input->get('format', '', 'CMD');
$token  = $app->input->get('token', '', 'ALNUM');

require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();

$document = $app->getDocument();
$wa       = $document->getWebAssetManager();
$wa->useScript('jquery');

$user          = $app->getIdentity();
$secret        = $app->get('secret', '');
$webhook_token = $app->get('webhook_token') ?: '';

if (!in_array($name, ['settings', 'campaigns', 'emails', 'form'])) {
	$wa->registerAndUseScript('com_emundus_jquery_ui', 'media/com_emundus/lib/jquery-ui-1.12.1.min.js');
	$wa->registerAndUseScript('com_emundus_bootstrap', 'media/com_emundus/lib/bootstrap-emundus/js/bootstrap.min.js');
	$wa->registerAndUseScript('com_emundus_chosen', 'media/com_emundus/js/chosen.jquery.js');

	$wa->registerAndUseScript('com_emundus', 'media/com_emundus/js/em_files.js', ['version' => $hash]);
	$wa->registerAndUseScript('com_emundus_export', 'media/com_emundus/js/mixins/exports.js', ['version' => $hash]);
	$wa->registerAndUseScript('com_emundus_utilities', 'media/com_emundus/js/mixins/utilities.js', ['version' => $hash]);

	$wa->registerAndUseScript('com_emundus_selectize', 'media/com_emundus/lib/selectize/dist/js/standalone/selectize.js');
	$wa->registerAndUseScript('com_emundus_sumoselect', 'media/com_emundus/lib/sumoselect/jquery.sumoselect.min.js');

	$wa->registerAndUseStyle('com_emundus_reset', 'media/com_emundus/css/reset.css');
	$wa->registerAndUseStyle('com_emundus_chosen', 'media/com_emundus/css/chosen/chosen.css');
	$wa->registerAndUseStyle('com_emundus_bootstrap', 'media/com_emundus/lib/bootstrap-emundus/css/bootstrap.min.css');
	$wa->registerAndUseStyle('com_emundus_files', 'media/com_emundus/css/emundus_files.css');
	$wa->registerAndUseStyle('com_emundus_normalize', 'media/com_emundus/lib/selectize/dist/css/normalize.css');
	$wa->registerAndUseStyle('com_emundus_selectize', 'media/com_emundus/lib/selectize/dist/css/selectize.default.css');
	$wa->registerAndUseStyle('com_emundus_sumoselect', 'media/com_emundus/lib/sumoselect/sumoselect.css');
}

$wa->registerAndUseScript('com_emundus_chunk_vendors', 'media/com_emundus_vue/chunk-vendors_emundus.js', ['version' => $hash]);
$wa->registerAndUseStyle('com_emundus_app', 'media/com_emundus_vue/app_emundus.css', ['version' => $hash]);

$wa->registerAndUseScript('lottie', 'media/com_emundus/js/lib/@lottiefiles/lottie-player/dist/lottie-player.js');

// The task 'getproductpdf' can be executed as public (when not signed in and form any view).
if ($task == 'getproductpdf') {
	$controller->execute($task);
}
if ($user->authorise('core.viewjob', 'com_emundus') && ($name == 'jobs' || $name == 'job' || $name == 'thesiss' || $name == 'thesis')) {
	$controller->execute($task);
}
elseif ($user->guest && ((($name === 'webhook' || $app->input->get('controller', '', 'WORD') === 'webhook') && $format === 'raw') && ($secret === $token || $webhook_token == JApplicationHelper::getHash($token)) || $task == 'getfilereferent')) {
	$controller->execute($task);
}
elseif ($user->guest && $name != 'emailalert' && $name != 'programme' && $name != 'search_engine' && $name != 'ccirs' && ($name != 'campaign') && $task != 'passrequest' && $task != 'getusername' && $task != 'getpasswordsecurity') {
	PluginHelper::importPlugin('emundus', 'custom_event_handler');
	$app->triggerEvent('onCallEventHandler', ['onAccessDenied', []]);

	throw new EmundusException(Text::_('JERROR_ALERTNOAUTHOR'), 403, null, false, false);
}
else {
	if ($name != 'search_engine') {
		// Perform the Request task
		$controller->execute($task);
	}
}
// Redirect if set by the controller
$controller->redirect();
?>
