<?php
defined('_JEXEC') or die('Restricted access');
JFactory::getSession()->set('application_layout', 'mail');

require_once(JPATH_ROOT . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'date.php');

$fnum = JFactory::getApplication()->input->getString('fnum', 0);

?>

<style>
    .em-container-mail-content {
        border: solid 1px var(--neutral-400);
        border-radius: var(--em-coordinator-br);
    }

    div#em-appli-block div.em-container-mail-content div.em-container-mail-content-heading.panel-heading {
        border-top-left-radius: var(--em-coordinator-br);
        border-top-right-radius: var(--em-coordinator-br);
        margin-top: 0;
        align-items: start;
        background: var(--neutral-300);
    }

    .em-container-mail-content-body {
        border-radius: var(--em-coordinator-br);
    }

    .em-container-mail-content-body img {
        display: initial;
    }

    div.em-container-mail-content-heading.panel-heading small {
        font-size: 12px;
        color: var(--neutral-800);
    }

    div.em-container-mail-content-heading.panel-heading small a {
        font-size: 12px;
        color: var(--main-500);
        text-decoration: underline;
    }

    div#em-appli-block .em-container-mail-content-heading h3 {
        color: var(--neutral-900) !important;
    }
</style>

<div class='mail'>
    <div class="row">
        <div class="panel panel-default widget em-container-mail">
            <div class="panel-heading em-container-mail-heading !tw-bg-profile-full">

                <h3 class="panel-title">
                    <span class="material-symbols-outlined">mode_comment</span>
					<?= JText::_('COM_EMUNDUS_EMAILS_MESSAGES'); ?>
                    <span class="label label-info"><?= count($this->messages); ?></span>
                </h3>
                <div class="btn-group pull-right">
                    <button id="em-prev-file" class="btn btn-info btn-xxl"><span
                                class="material-symbols-outlined">arrow_back</span></button>
                    <button id="em-next-file" class="btn btn-info btn-xxl"><span
                                class="material-symbols-outlined">arrow_forward</span></button>
                </div>

            </div>
            <div class="panel-body em-container-mail-body">

				<?php if ($this->messages === false) : ?>
                    <h3> <?= JText::_('COM_EMUNDUS_EMAILS_ERROR_GETTING_MESSAGES'); ?> </h3>
				<?php elseif (count($this->messages) === 0) : ?>
                    <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
                    <p class="tw-text-center"><?php echo JText::_('COM_EMUNDUS_EMAILS_NO_MESSAGES_FOUND'); ?></p>
				<?php else : ?>

                    <div class="em-flex-row em-border-bottom-neutral-300 mb-3"
                         style="overflow:hidden; overflow-x: auto;">
                        <div id="tab_link_file" onclick="filterMessages('file')"
                             class="em-mr-16 em-flex-row em-light-tabs profile_tab em-pointer em-light-selected-tab mb-2">
                            <p class="em-font-size-14 em-neutral-900-color"
                               title="<?= JText::_('COM_EMUNDUS_EMAIL_CURRENT_FILE') ?>"
                               style="white-space: nowrap"><?= JText::_('COM_EMUNDUS_EMAIL_CURRENT_FILE') ?></p>
                        </div>
                        <div id="tab_link_all" onclick="filterMessages('all')"
                             class="em-mr-16 em-flex-row em-light-tabs profile_tab em-pointer mb-2">
                            <p class="em-font-size-14 em-neutral-900-color"
                               title="<?= JText::_('COM_EMUNDUS_EMAIL_ALL_FILES') ?>"
                               style="white-space: nowrap"><?= JText::_('COM_EMUNDUS_EMAIL_ALL_FILES') ?></p>
                        </div>
                    </div>

					<?php foreach ($this->messages as $message) : ?>
                        <div class='message_<?php echo $message->fnum_to ?> panel panel-default em-container-mail-content'
						     <?php if ($message->fnum_to != $fnum) : ?>style="display: none"<?php endif; ?>>
                            <div class="panel-heading em-container-mail-content-heading tw-flex tw-flex-col"><h3
                                        class="tw-w-full"><?= $message->subject; ?></h3>
                                <small class="tw-mb-1"> <?= JText::_('COM_EMUNDUS_EMAILS_MESSAGE_FROM') . ': ' . JFactory::getUser($message->user_id_from)->name . ' ' . EmundusHelperDate::displayDate($message->date_time, 'DATE_FORMAT_LC2', 0); ?> </small>
								<?php if (!empty($message->fnum_to) && $message->fnum_to != $fnum) : ?>
                                    <small><?= JText::_('COM_EMUNDUS_EMAIL_ON_FILE') ?> <a
                                                href="#<?php echo $message->fnum_to ?>"
                                                target="_blank"><?php echo $message->fnum_to ?></a> </small>
								<?php endif; ?>
                            </div>
							<?php if (!empty($message->email_to)): ?>
                                <div class="panel-body em-container-mail-content-body">
                                    <i><?= JText::_('COM_EMUNDUS_EMAILS_MESSAGE_SENT_TO') . ' ' . $message->email_to; ?></i>
                                </div>
							<?php endif; ?>
							<?php if (!empty($message->email_cc)): ?>
                                <div class="panel-body em-container-mail-content-body">
                                <i><?= JText::_('COM_EMUNDUS_EMAIL_PEOPLE_CC') . ' ' . $message->email_cc; ?></i>
                                </div><?php endif; ?>
                            <div class="panel-body em-container-mail-content-body">
								<?php
								$length = strlen($message->message);
								if ($length > 1000000) {
									echo '<strong>' . JText::sprintf('COM_EMUNDUS_ERROR_TOO_LARGE_EMAIL', $length) . '</strong>';
								}
								else {
									echo $message->message;
								}
								?>
                            </div>
                        </div>
					<?php endforeach; ?>
				<?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function filterMessages(type) {
        var tabs = document.querySelectorAll(".profile_tab");
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].classList.remove("em-light-selected-tab");
        }
        var selected_tab = document.getElementById("tab_link_" + type);
        selected_tab.classList.add("em-light-selected-tab");
        var fnum = "<?php echo $fnum; ?>";
        var messages_to_display = document.querySelectorAll(".em-container-mail-content");

        if (type === 'file') {
            var messages_to_hide = document.querySelectorAll(".em-container-mail-content");
            messages_to_display = document.querySelectorAll('.message_' + fnum)

            for (var i = 0; i < messages_to_hide.length; i++) {
                messages_to_hide[i].style.display = "none";
            }
        }

        for (var i = 0; i < messages_to_display.length; i++) {
            messages_to_display[i].style.display = "block";
        }

    }
</script>
