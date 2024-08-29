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

$app = Factory::getApplication();
$app->getSession()->set('application_layout', 'form');

$defaultpid = $this->defaultpid;
$user = $this->userid;
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
            <div class="panel-heading em-container-form-heading">
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
        <div id="application-form-container" class="tw-relative tw-flex tw-flex-row">
            <div id="application-form-container-content" class="tw-w-full">
				<?php if (!EmundusHelperAccess::isDataAnonymized(JFactory::getUser()->id) && $this->header == 1) : ?>
                    <div class="em-flex-row em-mt-16">
                        <div class="em-flex-row em-small-flex-column em-small-align-items-start">
							<?php if(!empty($this->applicant->profile_picture)) :?>
                                <div class="em-profile-picture-big no-hover" style="background-image:url(<?php echo JURI::base() ?>/<?php echo $this->applicant->profile_picture ?>)">
                                </div>
							<?php else : ?>
                                <span class="em-no-profile-picture-big" data-initials="<?php echo substr($this->applicant->firstname,0, 1).substr($this->applicant->lastname,0,1);?>" alt="<?php echo Text::_('PROFILE_ICON_ALT')?>"></span>
							<?php endif; ?>
                        </div>
                        <div class="em-ml-24 ">
                            <p class="em-font-weight-500">
								<?php echo $this->applicant->lastname . ' ' . $this->applicant->firstname; ?>
                            </p>
                            <p><?php echo $this->fnum ?></p>
                        </div>
                    </div>
				<?php endif; ?>
                <div class="panel-body Marginpanel-body em-container-form-body">
                    <input type="hidden" id="dpid_hidden" value="<?php echo $defaultpid->pid ?>"/>

                    <div id="em-switch-profiles" <?php if(sizeof($this->pids) < 1): ?>style="display: none"<?php endif; ?>>

                        <div class="em_label">
                            <label class="control-label em-filter-label em-font-size-14" style="margin-left: 0 !important;"><?= Text::_('PROFILE_FORM'); ?></label>
                        </div>

                        <div class="em-flex-row em-border-bottom-neutral-300" style="overflow:hidden; overflow-x: auto;">

                            <div id="tab_link_<?php echo $defaultpid->pid; ?>" onclick="updateProfileForm(<?php echo $defaultpid->pid ?>)" class="em-mr-16 em-flex-row em-light-tabs profile_tab em-pointer em-light-selected-tab mb-2">
                                <p class="em-font-size-14 em-neutral-900-color" title="<?= $defaultpid->label; ?>" style="white-space: nowrap"> <?= $defaultpid->label; ?></p>
                            </div>

							<?php foreach($this->pids as $pid) : ?>
								<?php if(is_array($pid['data'])) : ?>
									<?php foreach($pid['data'] as $data) : ?>
										<?php if($data->pid != $defaultpid->pid): ?>
											<?php if($data->step !== null) : ?>
                                                <div id="tab_link_<?php echo $data->pid; ?>" onclick="updateProfileForm(<?php echo $data->pid ?>)" class="em-mr-16 em-flex-row profile_tab em-light-tabs em-pointer mb-2">
                                                    <p class="em-font-size-14 em-neutral-600-color" title="<?php echo $data->label; ?>" style="white-space: nowrap"><?php echo $data->label; ?></p>
                                                </div>
											<?php else: ?>
                                                <div id="tab_link_<?php echo $data->pid; ?>" onclick="updateProfileForm(<?php echo $data->pid ?>)" class="em-mr-16 profile_tab em-flex-row em-light-tabs em-pointer mb-2">
                                                    <p class="em-font-size-14 em-neutral-600-color" title="<?php echo $data->label; ?>" style="white-space: nowrap"><?php echo $data->label; ?></p>
                                                </div>
											<?php endif ?>
										<?php endif ?>
									<?php endforeach; ?>
								<?php else : ?>
                                    <div id="tab_link_<?php echo $pid['data']->pid; ?>" onclick="updateProfileForm(<?php echo $pid['data']->pid ?>)" class="em-mr-16 profile_tab em-flex-row em-light-tabs em-pointer mb-2">
                                        <p class="em-font-size-14 em-neutral-600-color" title="<?php echo $pid['data']->label; ?>" style="white-space: nowrap"> <?php echo $pid['data']->label; ?></p>
                                    </div>
								<?php endif;?>
							<?php endforeach; ?>
                        </div>

                    </div>

                    <div class="active content" id="show_profile">
						<?php echo $this->forms; ?>
                    </div>

                    <input type="hidden" id="user_hidden" value="<?php echo $user ?>">
                    <input type="hidden" id="fnum_hidden" value="<?php echo $this->fnum ?>">
                </div>
            </div>
			<?php
			if (EmundusHelperAccess::asAccessAction(10, 'c', $this->_user->id, $this->fnum) && $this->euser->applicant != 1): ?>
				<?php
				$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id);
				$sysadmin_access = EmundusHelperAccess::isAdministrator($this->_user->id);

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
                             component="comments"
                             user="<?= $this->_user->id ?>"
                             ccid="<?= $this->ccid ?>"
                             fnum="<?= $this->fnum ?>"
                             access='<?= json_encode($user_comment_access) ?>'
                             is_applicant="<?= 0 ?>"
                             current_form="<?= 0 ?>"
                             currentLanguage="<?= $current_lang->getTag() ?>"
                             shortLang="<?= $short_lang ?>"
                             coordinatorAccess="<?= $coordinator_access ?>"
                             sysadminAccess="<?= $sysadmin_access ?>"
                             manyLanguages="<?= $many_languages ?>"
                        >
                        </div>
                    </div>
                    <script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>
                    <script src="media/com_emundus/js/comment.js?<?php echo $hash ?>"></script>
                </aside>
			<?php endif; ?>
        </div>
    </div>
</div>
<script>
    $(".chzn-select").chosen();
    var dpid = $('#dpid_hidden').attr('value');

    if($('#select_profile option').length == 1) {
        $('#em-switch-profiles').remove();
    }

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });

    document.getElementById('download-all-phase-pdf').addEventListener('click', function (e) {
        if (typeof export_pdf === 'function') {
            export_pdf(JSON.stringify({0: <?= $this->fnum ?>}), null, 'forms');
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
        z-index: 10;

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