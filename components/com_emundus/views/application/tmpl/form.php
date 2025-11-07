<?php
/**
 * Created by PhpStorm.
 * User: yoan
 * Date: 19/06/14
 * Time: 11:23
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

$app = Factory::getApplication();
$app->getSession()->set('application_layout', 'form');

$defaultpid = $this->defaultpid;
$user = $this->userid;
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id);
$sysadmin_access = EmundusHelperAccess::isAdministrator($this->_user->id);
$emundus_config = ComponentHelper::getParams('com_emundus');
require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();

$current_lang = $app->getLanguage();
$short_lang = substr($current_lang->getTag(), 0 , 2);
$languages = LanguageHelper::getLanguages();
if (count($languages) > 1) {
	$many_languages = '1';
	require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
	$m_translations = new EmundusModelTranslations();
	$default_lang = $m_translations->getDefaultLanguage()->lang_code;
} else {
	$many_languages = '0';
	$default_lang = $current_lang;
}
?>

<style type="text/css">
    .group-result { color: #16afe1 !important; }

    .profile_tab p {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        max-width: 200px;
    }

    .modal___container {
        position: fixed;
        z-index: -999999;
        width: 0;
        height: 0;
        background-color: white;
        opacity: 0;
        display: block !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%);
        box-shadow: 0 0 9999px 9999px rgb(0 0 0 / 43%);
        border-radius: var(--em-applicant-br);
    }

</style>

<div class="row">
    <div class="panel panel-default widget em-container-form">
		<?php if ($this->header == 1) : ?>
            <div class="panel-heading em-container-form-heading !tw-bg-profile-full">
                <h3 class="panel-title">
                    <span class="material-symbols-outlined">list_alt</span>
					<?php echo Text::_('COM_EMUNDUS_APPLICATION_APPLICATION_FORM').' - '.$this->formsProgress." % ".Text::_("COM_EMUNDUS_APPLICATION_COMPLETED"); ?>
					<?php if (EmundusHelperAccess::asAccessAction(8, 'c', $app->getIdentity()->id, $this->fnum)):?>
                        <button id="download-all-phase-pdf"
                                class="em-mt-8 em-ml-8"
                                data-fnum="<?= $this->fnum ?>"
                                data-toggle="tooltip"
                                data-placement="right"
                                title="<?= Text::_('COM_EMUNDUS_APPLICATION_DOWNLOAD_APPLICATION_FORM'); ?>"
                        >
                            <span class="material-symbols-outlined" data-fnum="<?= $this->fnum ?>">file_download</span>
                        </button>
					<?php endif;?>
                </h3>
                <div class="btn-group pull-right">
                    <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span></button>
                    <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span></button>
                </div>
            </div>
		<?php endif; ?>
        <div id="application-form-container" class="tw-relative tw-flex tw-flex-row tw-bg-neutral-100">
            <div id="application-form-container-content" class="tw-w-full">

                <?php if (!EmundusHelperAccess::isDataAnonymized($this->_user->id) && $this->header == 1 && !$this->applicant->is_anonym) : ?>
                    <div class="em-flex-row em-mt-16">
                        <div class="em-flex-row em-small-flex-column em-small-align-items-start">
                            <div class="em-profile-picture-big no-hover"
                                <?php if (empty($this->applicant->profile_picture)) : ?>
                                    style="background-image:url(<?php echo JURI::base() ?>/media/com_emundus/images/profile/default-profile.jpg)"
                                <?php else : ?>
                                    style="background-image:url(<?php echo JURI::base() ?>/<?php echo $this->applicant->profile_picture ?>)"
                                <?php endif; ?>
                            >
                            </div>
                        </div>
                        <div class="tw-ml-4">
                            <p class="em-font-weight-500">
                                <?php echo $this->applicant->lastname . ' ' . $this->applicant->firstname; ?>
                            </p>
                            <p><?php echo $this->fnum ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="panel-body Marginpanel-body em-container-form-body !tw-bg-neutral-100">
                    <input type="hidden" id="dpid_hidden" value="<?php echo $defaultpid->pid ?>"/>

                    <div id="steps-timeline"
                         component="Workflows/WorkflowStepsTimeline"
                         class="com_emundus_vue"
                         data="<?= htmlspecialchars(json_encode([
                             'user' => $this->_user->id,
                                'fnum' => $this->fnum,
                                'currentLanguage' => $current_lang->getTag(),
                                'shortLang' => $short_lang,
                                'coordinatorAccess' => $coordinator_access,
                                'sysadminAccess' => $sysadmin_access,
                                'manyLanguages' => $many_languages,
                         ]), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    </div>

                    <div class="active content" id="show_profile">
						<?php echo $this->forms; ?>
                    </div>

                    <input type="hidden" id="user_hidden" value="<?php echo $user ?>">
                    <input type="hidden" id="fnum_hidden" value="<?php echo $this->fnum ?>">
                </div>
            </div>
			<?php
			if (EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id, $this->fnum) && $this->euser->applicant != 1 && $this->context === 'default'): ?>
				<?php
				$user_comment_access = [
					'c' => EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id, $this->fnum),
					'r' => EmundusHelperAccess::asAccessAction(10, 'r', $this->_user->id, $this->fnum),
					'u' => EmundusHelperAccess::asAccessAction(10, 'u', $this->_user->id, $this->fnum),
					'd' => EmundusHelperAccess::asAccessAction(10, 'd', $this->_user->id, $this->fnum),
				];
				?>
                <aside id="aside-comment-section" class="tw-fixed tw-right-0 tw-bg-[#f8f8f8] tw-shadow tw-ease-out closed">
                    <!-- Comments -->
                    <div class="flex flex-row relative">
                        <span class="open-comment material-symbols-outlined tw-cursor-pointer tw-absolute tw-top-28 tw-bg-profile-full tw-rounded-l-lg tw-text-neutral-300" onclick="openCommentAside()">
                            comment
                        </span>
                        <span class="close-comment material-symbols-outlined tw-cursor-pointer tw-absolute tw-top-28 tw-bg-profile-full tw-rounded-l-lg tw-text-neutral-300" onclick="openCommentAside()">
                            close
                        </span>
                        <div id="em-component-vue"
                             component="Comments"
                             class="com_emundus_vue"
                             user="<?= $this->_user->id ?>"
                             ccid="<?= $this->ccid ?>"
                             fnum="<?= $this->fnum ?>"
                             access='<?= json_encode($user_comment_access) ?>'
                             is_applicant="<?= 0 ?>"
                             current_form="<?= 0 ?>"
                             applicants_allowed_to_comment="<?= $emundus_config->get('allow_applicant_to_comment', false) ? 1 : 0 ?>"
                             currentLanguage="<?= $current_lang->getTag() ?>"
                             shortLang="<?= $short_lang ?>"
                             coordinatorAccess="<?= $coordinator_access ?>"
                             sysadminAccess="<?= $sysadmin_access ?>"
                             manyLanguages="<?= $many_languages ?>"
                        >
                        </div>
                    </div>
                    <script src="media/com_emundus/js/comment.js?<?php echo $hash ?>"></script>
                </aside>
			<?php endif; ?>
        </div>
    </div>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>

<script>
    $(".chzn-select").chosen();
    var dpid = $('#dpid_hidden').attr('value');

    if($('#select_profile option').length == 1) {
        $('#em-switch-profiles').remove();
    }

    document.getElementById('download-all-phase-pdf').addEventListener('click', function (e) {
        if (typeof export_pdf === 'function') {
            export_pdf(JSON.stringify({0: "<?= $this->fnum ?>"}), null, 'forms');
        } else {
            console.error('Function export_pdf does not exist');
        }
    });
</script>

<style>
    #aside-comment-section #comments {
        height: calc(100vh - 120px);
        overflow: scroll;
    }

    #aside-comment-section #comments-list-container {
        max-height: 80%;
        overflow-y: auto;
    }

    #aside-comment-section {
        top: 112px;
        height: calc(100vh - 112px);
        transition: all .3s;
        width: 425px;
        min-width: 425px;
        z-index: 9;

        #filter-comments {
            flex-direction: column;

            select {
                margin-top: var(--em-spacing-2);
            }
        }

        .close-comment, .open-comment {
            left: -52px;
            padding: 8px;
            font-size: 36px;
        }

        .open-comment {
            display: none;
        }
        .close-comment {
            display: block;
        }

        &.closed {
            right: -425px;

            .open-comment {
                display: block;
            }
            .close-comment {
                display: none;
            }
        }
    }

    .comment-icon {
        transition: all .3s;
        opacity: 1 !important;
    }

    .comment-icon:not(.has-comments) {
        opacity: 0 !important;
    }

    table:not(.em-personalDetail-table-multiplleLine) tr:hover .comment-icon,
    .em-personalDetail-table-multiplleLine th:hover .comment-icon,
    .title-applicant-form:hover .comment-icon,
    .form-group-title:hover .comment-icon {
        opacity: 1 !important;
    }
</style>