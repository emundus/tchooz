<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

require_once (JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();

$lang = Factory::getLanguage();
$short_lang = substr($lang->getTag(), 0 , 2);
$current_lang = $lang->getTag();
$languages = JLanguageHelper::getLanguages();
if (count($languages) > 1) {
	$many_languages = '1';
	require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
	$m_translations = new EmundusModelTranslations();
	$default_lang = $m_translations->getDefaultLanguage()->lang_code;
} else {
	$many_languages = '0';
	$default_lang = $current_lang;
}

$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id);
$sysadmin_access = EmundusHelperAccess::isAdministrator($this->user->id);

Text::script('COM_EMUNDUS_RANKING_NO_FILES');
Text::script('COM_EMUNDUS_CLASSEMENT_ASK_LOCK_RANKING');
Text::script('COM_EMUNDUS_CLASSEMENT_LOCK_RANKING');
Text::script('COM_EMUNDUS_NB_FILES');
Text::script('COM_EMUNDUS_CLASSEMENT_YOUR_RANKING');
Text::script('COM_EMUNDUS_CLASSEMENT_FILE');
Text::script('COM_EMUNDUS_CLASSEMENT_NOT_RANKED');
Text::script('COM_EMUNDUS_CLASSEMENT_RANKING_SELECT_LABEL');
Text::script('COM_EMUNDUS_CLASSEMENT_MODAL_COMPARISON_HEADER_TITLE');
Text::script('COM_EMUNDUS_MODAL_COMPARISON_SELECT_A_FILE_TO_COMPARE_TO');
Text::script('COM_EMUNDUS_MODAL_COMPARISON_BACK_BUTTON');
Text::script('COM_EMUNDUS_RANKING_LOCK_RANKING_CONFIRM_TITLE');
Text::script('COM_EMUNDUS_RANKING_LOCK_RANKING_CONFIRM_TEXT');
Text::script('COM_EMUNDUS_RANKING_LOCK_RANKING_CONFIRM_YES');
Text::script('COM_EMUNDUS_RANKING_LOCK_RANKING_CONFIRM_NO');
Text::script('COM_EMUNDUS_RANKING_RANKER');
Text::script('COM_EMUNDUS_CLASSEMENT_ASK_USERS_LOCK_RANKING');
Text::script('COM_EMUNDUS_CLASSEMENT_ASK_HIERARCHIES_LOCK_RANKING');
Text::script('COM_EMUNDUS_CLASSEMENT_CANCEL_ASK_LOCK_RANKING');
Text::script('COM_EMUNDUS_CLASSEMENT_CONFIRM_ASK_LOCK_RANKING');
Text::script('COM_EMUNDUS_RANKING_LOCK_RANKING_ASK_CONFIRM_SUCCESS_TITLE');
Text::script('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_TITLE');
Text::script('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_LOCKED');
Text::script('COM_EMUNDUS_RANKING_CANNOT_DRAG_AND_DROP');
Text::script('COM_EMUNDUS_DISPLAY');
Text::script('COM_EMUNDUS_RANKING_FILE_STATUS');
Text::script('COM_EMUNDUS_RANKING_EXPORT_PACKAGES');
Text::script('COM_EMUNDUS_RANKING_EXPORT_HIERARCHIES');
Text::script('COM_EMUNDUS_RANKING_EXPORT_COLUMNS');
Text::script('COM_EMUNDUS_RANKING_EXPORT_TITLE');
Text::script('COM_EMUNDUS_RANKING_EXPORT_DOWNLOAD_FILE');
Text::script('COM_EMUNDUS_RANKING_EXPORT_BUTTON');
Text::script('COM_EMUNDUS_RANKING_EXPORT_APPLICANT');
Text::script('COM_EMUNDUS_RANKING_EXPORT_STATUS');
Text::script('COM_EMUNDUS_RANKING_EXPORT_RANKER');
Text::script('COM_EMUNDUS_RANKING_EXPORT_RANKINGS_BTN');
Text::script('COM_EMUNDUS_SELECT_ALL');
Text::script('COM_EMUNDUS_RANKING_PACKAGE_START_DATE');
Text::script('COM_EMUNDUS_RANKING_PACKAGE_END_DATE');
Text::script('COM_EMUNDUS_RANKING_ADD_COLUMN');
Text::script('COM_EMUNDUS_RANKING_EXPORT_RANK');
Text::script('COM_EMUNDUS_RANKING_EXPORT_FILE_FNUM');
Text::script('COM_EMUNDUS_RANKING_EXPORT_FILE_ID');
Text::script('COM_EMUNDUS_RANKING_SELECT_COLUMN');
Text::script('COM_EMUNDUS_RANKING_EXPORT_RANKING');

// Translation for the file view
Text::script('COM_EMUNDUS_FILES_EVALUATION');
Text::script('COM_EMUNDUS_FILES_DECISION');
Text::script('COM_EMUNDUS_FILES_ADMISSION');
Text::script('COM_EMUNDUS_FILES_TO_EVALUATE');
Text::script('COM_EMUNDUS_FILES_EVALUATED');
Text::script('COM_EMUNDUS_ONBOARD_FILE');
Text::script('COM_EMUNDUS_ONBOARD_STATUS');
Text::script('COM_EMUNDUS_FILES_APPLICANT_FILE');
Text::script('COM_EMUNDUS_FILES_ATTACHMENTS');
Text::script('COM_EMUNDUS_FILES_COMMENTS');
Text::script('COM_EMUNDUS_ONBOARD_NOFILES');
Text::script('COM_EMUNDUS_FILES_ELEMENT_SELECTED');
Text::script('COM_EMUNDUS_FILES_ELEMENTS_SELECTED');
Text::script('COM_EMUNDUS_FILES_UNSELECT');
Text::script('COM_EMUNDUS_FILES_OPEN_IN_NEW_TAB');
Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS');
Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC');
Text::script('COM_EMUNDUS_FILES_DISPLAY_PAGE');
Text::script('COM_EMUNDUS_FILES_NEXT_PAGE');
Text::script('COM_EMUNDUS_FILES_PAGE');
Text::script('COM_EMUNDUS_FILES_TOTAL');
Text::script('COM_EMUNDUS_FILES_ALL');
Text::script('COM_EMUNDUS_FILES_ADD_COMMENT');
Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS_COMMENTS');
Text::script('COM_EMUNDUS_FILES_CANNOT_ACCESS_COMMENTS_DESC');
Text::script('COM_EMUNDUS_FILES_COMMENT_TITLE');
Text::script('COM_EMUNDUS_FILES_COMMENT_BODY');
Text::script('COM_EMUNDUS_FILES_VALIDATE_COMMENT');
Text::script('COM_EMUNDUS_FILES_COMMENT_DELETE');
Text::script('COM_EMUNDUS_FILES_ASSOCS');
Text::script('COM_EMUNDUS_FILES_TAGS');
Text::script('COM_EMUNDUS_FILES_PAGE_ON');
Text::script('COM_EMUNDUS_ERROR_OCCURED');
Text::script('COM_EMUNDUS_ACTIONS_CANCEL');
Text::script('COM_EMUNDUS_OK');
Text::script('COM_EMUNDUS_FILES_FILTER_NO_ELEMENTS_FOUND');
Text::script('COM_EMUNDUS_RANKING_FILE_ID');
Text::script('COM_EMUNDUS_RANKING_APPLICANT_NAME');
Text::script('COM_EMUNDUS_RANKING_FILE_PROGRAM');
Text::script('COM_EMUNDUS_RANKING_FILE_CAMPAIGN');
Text::script('COM_EMUNDUS_RANKING_RANKING_ROW_ID');
Text::script('COM_EMUNDUS_RANKING_RANK');
Text::script('COM_EMUNDUS_RANKING_RANKER_NAME');

?>
<div class="em-flex-row em-w-100 em-h-100 em-flex-align-start">

	<?php if ($this->display_filters): ?>
		<aside class="em-left-panel filters em-h-100">
			<div class="content">
				<h3>Filtres</h3>
				<?php echo JHtml::_('content.prepare', '{loadposition emundus_filters}'); ?>
			</div>
			<div class="em-left-panel-opener em-pointer em-flex-row">
				<span>Filtres</span>
				<span class="material-icons-outlined">arrow_drop_down</span>
			</div>
		</aside>
	<?php endif; ?>

	<div class="em-p-0-12 em-w-100 em-h-100">
		<h2><?= Text::_($this->title) ?></h2>
		<section id="ranking-introduction">
			<?= Text::_($this->introduction) ?>
		</section>

		<div id="em-component-vue"
			 component="Ranking/allRankings"
			 hash="<?= $hash ?>"
			 shortLang="<?= $short_lang ?>"
			 currentLanguage="<?= $current_lang ?>"
			 defaultLang="<?= $default_lang ?>"
			 manyLanguages="<?= $many_languages ?>"
			 coordinatorAccess="<?= $coordinator_access ?>"
			 sysadminAccess="<?= $sysadmin_access ?>"
		></div>
	</div>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
