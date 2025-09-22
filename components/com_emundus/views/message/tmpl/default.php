<?php
/**
 * @package    Joomla
 * @subpackage emundus
 * @link       http://www.emundus.fr
 * @copyright  eMundus
 * @license    GNU/GPL
 * @author     Hugo Moracchini
 */

// no direct access
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

$app          = Factory::getApplication();
$current_user = $app->getIdentity();
$config       = $app->getConfig();
$editor       = Editor::getInstance('tiptap');

$input  = $app->getInput();
$itemid = $input->getInt('Itemid', null);
$view   = $input->getString('view', null);
$task   = $input->getString('task', null);
$tmpl   = $input->getString('tmpl', null);

$m_messages         = new EmundusModelMessages();
$message_categories = $m_messages->getAllCategories();
$message_templates  = $m_messages->getAllMessages();
$setup_attachments  = $m_messages->getAttachmentsByProfiles($this->fnums);
$setup_letters      = $m_messages->getAllDocumentsLetters();

require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
$m_evaluation       = new EmundusModelEvaluation;
$_applicant_letters = $m_evaluation->getLettersByFnums(implode(',', $this->fnums), false);

$email_list = array();
$name_list  = array();

$allowed_attachments = EmundusHelperAccess::getUserAllowedAttachmentIDs($current_user->id);
if ($allowed_attachments !== true) {
	foreach ($setup_attachments as $key => $att) {
		if (!in_array($att->id, $allowed_attachments)) {
			unset($setup_attachments[$key]);
		}
	}
}
?>
<!-- WYSIWYG Editor -->
<style>
    #emailForm #mceu_15 {
        display: none;
    }

    #emailForm .selectize-input {
        overflow: auto;
    }

    .form-group .email-input-block {
        height: var(--em-coordinator-form-height);
        display: flex;
        align-items: center;
        padding: 0 var(--p-12);
        border: solid 1px var(--em-coordinator-bc);
        border-radius: var(--em-coordinator-form-br);
        margin-top: 6px;
    }

    .cc-bcc-mails .items {
        border: 1px solid var(--em-coordinator-bc);
        border-radius: var(--em-coordinator-form-br);
    }

    .cc-bcc-mails .items div[data-value] {
        background: #EBECF0;
        border: unset;
        border-radius: var(--em-coordinator-form-br);
        box-shadow: unset !important;
        padding: var(--p-4) var(--p-8);
    }

    .cc-bcc-mails .items div[data-value] .remove {
        font-size: 16px;
        border: unset;
        padding-right: var(--p-12);
    }

    .email-input-block::-webkit-scrollbar {
        height: 6px;
    }

    div#mail_from_name, div#mail_subject {
        border-radius: var(--em-coordinator-form-br);
        border: solid 2px transparent;
    }

    div#mail_from_name:focus, div#mail_subject:focus, div#reply_to_from:focus {
        outline-color: #2E90FA;
    }

    div#mail_from_name:hover, div#mail_subject:hover {
        border-radius: var(--em-coordinator-form-br);
        border: solid 2px var(--em-coordinator-bc);
    }

    #cc-box-label, #bcc-box-label, #replyto-box-label {
        border-radius: var(--em-coordinator-form-br);
        width: fit-content;
        padding: var(--p-4) var(--p-4) var(--p-4) 0;
        margin-left: 0;
    }

    #cc-box-label:hover, #bcc-box-label:hover {
        background: var(--neutral-300);
    }

    #reply_to_from, #emailForm div#mail_subject {
        min-width: 100%;
    }

    #mail_from_block {
        width: 94%;
    }

    .em-form-recipients {
        height: 44px;
        display: flex !important;
        flex-direction: column;
        justify-content: center;
    }

    .email-list-modal {
        width: 50%;
        position: absolute;
        left: 0;
        right: 0;
        margin-left: auto;
        margin-right: auto;
        background: white;
        padding: 24px;
        box-shadow: 0 0 0 50vmax rgba(0, 0, 0, .5);
        border-radius: 8px;
    }

    .em-email-label {
        color: var(--neutral-800);
        background-color: var(--neutral-300) !important;
    }

</style>
<div id="em-email-messages"></div>

<div class="em-modal-sending-emails" id="em-modal-sending-emails">
    <div id="em-sending-email-caption"
         class="em-sending-email-caption"><?= Text::_('COM_EMUNDUS_EMAILS_SENDING_EMAILS'); ?></div>
    <img class="em-sending-email-img" id="em-sending-email-img" src="media/com_emundus/images/sending-email.gif">
</div>

<form id="emailForm" class="em-form-message" name="emailForm">
    <div class="em_email_block tw-mb-8" id="em_email_block">

        <div class="form-inline row">

            <!-- Dropdown to select the email categories used. -->
            <div class="form-group col-md-6 col-sm-6 em-form-selectCategory">
                <label for="select_category"><?= Text::_('COM_EMUNDUS_EMAILS_SELECT_CATEGORY'); ?></label>
                <select name="select_category" class="em-border-radius-8 em-mb-16 email-input-block em-w-100"
                        onChange="setCategory(this);">
					<?php if (!$message_categories) : ?>
                        <option value="%"> <?= Text::_('COM_EMUNDUS_EMAILS_NO_CATEGORIES_FOUND'); ?> </option>
					<?php else : ?>
                        <option value="%"> <?= Text::_('COM_EMUNDUS_EMAILS_SELECT_CATEGORY'); ?> </option>
						<?php foreach ($message_categories as $message_category) : ?>
							<?php if (!empty($message_category)) : ?>
                                <option value="<?= $message_category; ?>"> <?= $message_category; ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>
            </div>

            <!-- Dropdown to select the email template used. -->
            <div class="form-group col-md-6 col-sm-6 em-form-selectTypeEmail">
                <label for="select_template"><?= Text::_('COM_EMUNDUS_EMAILS_SELECT_TEMPLATE'); ?></label>
                <select name="select_template" id="message_template"
                        class="em-border-radius-8 em-mb-16 email-input-block em-w-100" onChange="getTemplate(this);">
					<?php if (!$message_templates) : ?>
                        <option value="%"> <?= Text::_('COM_EMUNDUS_EMAILS_NO_TEMPLATES_FOUND'); ?> </option>
					<?php else : ?>
                        <option value="%"> <?= Text::_('COM_EMUNDUS_EMAILS_SELECT_TEMPLATE'); ?> </option>
						<?php foreach ($message_templates as $message_template) : ?>
                            <option value="<?= $message_template->id; ?>"
							        <?php if ($this->data['template'] == $message_template->id) : ?>selected<?php endif; ?>> <?= $message_template->subject; ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>
                <a class="em-font-size-14 em-pointer em-text-underline" href="emails"
                   target="_blank"><?= Text::_('COM_EMUNDUS_EMAILS_ADD_TEMPLATE'); ?>
                </a>
            </div>
        </div>

        <input name="mail_from_id" type="hidden" class="inputbox" id="mail_from_id"
               value="<?= $current_user->id; ?>"/>
        <input name="fnums" type="hidden" class="inputbox" id="fnums" value="<?= implode(',', $this->fnums); ?>"/>
        <input name="tags" type="hidden" class="inputbox" id="tags" value=""/>

        <!-- FROM -->
        <div class="form-inline row">
            <div class="form-group em-form-sender tw-mt-3 col-md-6 col-sm-6">
                <div class="tw-flex tw-items-center tw-justify-between">
                    <label class='em-mr-8' for="mail_from"><?= Text::_('FROM'); ?> :</label>
                    <div id="mail_from_block" class="em-border-radius-8 em-mb-4 email-input-block">
                        <div id="mail_from_name" class="em-p-4-6"
                             contenteditable="true"><?= $config->get('fromname') ?></div>
                        <div id="mail_from" class="em-ml-4" contenteditable="false">
                            <em class="em-font-size-14">&lt;<?= $config->get('mailfrom') ?>&gt;</em>
                        </div>
                    </div>
                </div>

                <span class="em-font-size-14"><?= Text::_('COM_EMUNDUS_FROM_HELP_TEXT') ?></span>

            </div>

            <div class="form-group em-form-recipients tw-mt-4 col-md-6 col-sm-6" style="position:static;">

                <div class="email-list-modal hidden" id="email-list-modal">
                    <div class="tw-flex tw-justify-between mb-3">
                        <h3><?= Text::_('COM_EMUNDUS_EMAILS_TO_LIST') ?></h3>
                        <span class="material-symbols-outlined pointer" onclick="showEmailList()">close</span>
                    </div>

                    <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap"
                         style="max-height: 150px; overflow-y: auto;">
						<?php foreach ($this->users as $user) : ?>

							<?php if (!empty($user['email']) && !in_array($user['email'], $email_list)) : ?>
								<?php $email_list[] = $user['email']; ?>
								<?php $name_list[] = $user['name']; ?>

                                <span class="label label-default em-mr-8 em-email-label">
                                    <?= $user['email'] . ' <em class="em-font-size-14">&lt;' . $user['name'] . '&gt;</em>'; ?>
                                </span>

                                <input type="hidden" name="ud[]" id="ud" value="<?= $user['id']; ?>"/>
							<?php endif; ?>

						<?php endforeach; ?>
                    </div>
                </div>

                <!-- List of users / their emails, gotten from the fnums selected. -->
                <div class="tw-flex tw-justify-between tw-items-center">
                    <div class="tw-flex tw-items-center">
                        <label class='em-mr-8 em-cursor-text mb-0'><?= Text::_('COM_EMUNDUS_TO'); ?> :</label>

                        <div class="em-border-radius-8">
                        <span class="label label-default em-mr-8 em-email-label">
                                <?= $email_list[0] . ' <em class="em-font-size-14">&lt;' . $name_list[0] . '&gt;</em>'; ?>
                        </span>


							<?php if (count($email_list) > 1) : ?>
                                <span class="label label-default em-mr-8 em-email-label pointer"
                                      onclick="showEmailList()">
                                    +<?= count($email_list) - 1 ?>
                            </span>
							<?php endif; ?>
                        </div>
                    </div>

                    <div class="tw-flex tw-items-center">
                        <div id="cc-box-label" class="em-flex-row em-pointer" onclick="openCC()">
                            <label class="em-mb-0-important"><?= Text::_('COM_EMUNDUS_EMAILS_CC_LABEL'); ?></label>
                            <span id="cc-icon" class="material-symbols-outlined">chevron_right</span>
                        </div>

                        <div id="bcc-box-label" class="em-flex-row em-pointer" onclick="openBCC()">
                            <label class="em-mb-0-important"><?= Text::_('COM_EMUNDUS_EMAILS_BCC_LABEL'); ?></label>
                            <span id="bcc-icon" class="material-symbols-outlined">chevron_right</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Add current user to Cc -->
        <div id="cc-box" class="input-group form-inline col-md-12 em-mt-12">
            <label for="select_action_tags" class="cc-box-label"><?= Text::_('COM_EMUNDUS_EMAILS_CC_LABEL'); ?></label>
            <input type="text" id="cc-mails" class="cc-bcc-mails">
        </div>

        <!-- Add current user to Bcc -->
        <div id="bcc-box" class="input-group form-inline col-md-12 em-mt-12">
            <label for="select_action_tags"
                   class="bcc-box-label"><?= Text::_('COM_EMUNDUS_EMAILS_BCC_LABEL'); ?></label>
            <input type="text" id="bcc-mails" class="cc-bcc-mails">
        </div>

        <!-- REPLY TO -->
        <div id="replyto-box" class="form-group em-form-sender em-mt-12">
            <div id="replyto-box-label" class="em-flex-row em-pointer" onclick="openReplyTo()">
                <label class="em-mb-0-important"
                       for="reply_to_from"><?= Text::_('COM_EMUNDUS_EMAILS_FROM_REPLY_TO'); ?></label>
                <span id="replyto-icon" class="material-symbols-outlined">chevron_right</span>
            </div>
            <div id="reply_to_div" style="display: none">
                <div id="reply_to_block" class="em-border-radius-8 em-mb-4 email-input-block em-cursor-text">
                    <div id="reply_to_from" class="em-p-4-6 em-cursor-text" contenteditable="true"></div>
                </div>
                <span class="em-font-size-14"><?= Text::_('COM_EMUNDUS_EMAILS_REPLY_TO_HELP_TEXT') ?></span>
            </div>
        </div>


        <div class="form-group em-form-subject em-mt-12">
            <label class='em-mr-8' for="mail_from"><?= Text::_('COM_EMUNDUS_EMAILS_SUBJECT'); ?> :</label>
            <div class="em-border-radius-8 email-input-block em-mb-12">
                <div id="mail_subject"
                     class="em-p-4-6"
                     contenteditable="true"><?= $config->get('sitename'); ?></div>
            </div>


            <!-- Email WYSIWYG -->
            <?php echo $editor->display('mail_body', $this->body, '100%', 0, 0, 0, true, 'mail_body', 'com_emundus', null,
                [
                    'enable_suggestions' => true,
                    'plugins' => ['bold','italic','underline','strike','link','h1','h2','h3','ul','ol','color','left','right','center']
                ]);
            ?>

            <!-- TIP -->
            <p class="em-text-neutral-600 em-mt-8 em-font-size-14">
				<?= Text::_('COM_EMUNDUS_ONBOARD_VARIABLESTIP'); ?>
            </p>
        </div>

        <div class="form-group">
            <br>
            <hr>
        </div>

        <div class="form-inline row em-form-attachments">
            <div class="form-group col-sm-12 col-md-5">
                <label for="em-select_attachment_type"><?= Text::_('COM_EMUNDUS_EMAILS_SELECT_ATTACHMENT_TYPE'); ?></label>
                <select name="em-select_attachment_type" id="em-select_attachment_type"
                        class="em-border-radius-8 em-mb-16 email-input-block em-w-100 download"
                        onChange="toggleAttachmentType(this);">
                    <option value=""> <?= Text::_('COM_EMUNDUS_PLEASE_SELECT'); ?> </option>
                    <option value="upload"> <?= Text::_('COM_EMUNDUS_UPLOAD'); ?> </option>
					<?php if (EmundusHelperAccess::asAccessAction(4, 'r')) : ?>
                        <option value="candidate_file"> <?= Text::_('COM_EMUNDUS_EMAILS_CANDIDATE_FILE'); ?> </option>
					<?php endif; ?>
					<?php if (!empty($_applicant_letters)) { ?>
						<?php if (EmundusHelperAccess::asAccessAction(4, 'c') && EmundusHelperAccess::asAccessAction(27, 'c')) : ?>
                            <option value="setup_letters"> <?= Text::_('COM_EMUNDUS_EMAILS_SETUP_LETTERS_ATTACH'); ?> </option>
						<?php endif; ?>
					<?php } ?>
                </select>
            </div>

            <div class="form-group col-sm-12 col-md-7">
                <!-- Upload a file from computer -->
                <div class="hidden upload-file em-form-attachments-uploadFile" id="upload_file">

                    <div class="file-browse">
                        <span id="em-filename"><?= Text::_('COM_EMUNDUS_ATTACHMENTS_FILE_NAME'); ?></span>

                        <label for="em-file_to_upload"
                               type="button"><?= Text::_('COM_EMUNDUS_ATTACHMENTS_SELECT_FILE_TO_UPLOAD') ?>
                            <input type="file"
                                   accept=".xls,.xlsx,.doc,.docx,.pdf,.png,.jpg,.jpeg,.gif,.odf,.ppt,.pptx,.svg,.csv"
                                   id="em-file_to_upload" onChange="addFile();">
                        </label>
                        <p><?php echo Text::_('COM_EMUNDUS_ATTACHMENTS_PLEASE_ONLY'); ?>.xls,.docx,.pdf,.png,.jpg,.jpeg,.gif,.odf,.pptx,.svg,.csv</p>
                        <p id="error_message" class="tw-text-red-600 tw-font-semibold"></p>
                    </div>
                </div>

                <!-- Get a file from setup_attachments -->
				<?php if (EmundusHelperAccess::asAccessAction(4, 'r')) : ?>
                    <div class="hidden em-form-attachments-candidateFile" id="candidate_file">
                        <label for="em-select_candidate_file"><?= Text::_('COM_EMUNDUS_UPLOAD'); ?></label>
                        <select id="em-select_candidate_file" name="candidate_file" class="form-control download"
                                onchange="addFile();">
							<?php if (!$setup_attachments) : ?>
                                <option value="%"> <?= Text::_('COM_EMUNDUS_EMAILS_NO_FILES_FOUND'); ?> </option>
							<?php else : ?>
                                <option value="%"> <?= Text::_('JGLOBAL_SELECT_AN_OPTION'); ?> </option>
							<?php endif; ?>
                        </select>
                    </div>
				<?php endif; ?>

                <!-- Get a file from setup_letters -->
				<?php if (!empty($_applicant_letters)) { ?>
					<?php if (EmundusHelperAccess::asAccessAction(4, 'c') && EmundusHelperAccess::asAccessAction(27, 'c')) : ?>
                        <div class="hidden em-form-attachments-setupLetters" id="setup_letters">
                            <label for="em-select_setup_letters"><?= Text::_('COM_EMUNDUS_UPLOAD'); ?></label>
                            <select id="em-select_setup_letters" name="setup_letters" class="form-control"
                                    onchange="addFile();">
								<?php if (!$setup_letters) : ?>
                                    <option value="%"> <?= Text::_('COM_EMUNDUS_EMAILS_NO_FILES_FOUND'); ?> </option>
								<?php else : ?>
                                    <option value="%"> <?= Text::_('COM_EMUNDUS_PLEASE_SELECT'); ?> </option>
									<?php foreach ($setup_letters as $letter) : ?>
                                        <option value="<?= $letter->id; ?>"> <?= $letter->value; ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
                            </select>
                        </div>
					<?php endif; ?>
				<?php } ?>
            </div>
        </div>
        <div class="em-form-attachments-location tw-w-max tw-mt-2">
            <ul class="list-group tw-flex tw-flex-col tw-gap-2" id="em-attachment-list">
                <!-- Files to be attached will be added here. -->
            </ul>
        </div>
    </div>

    <input type="hidden" name="task" value=""/>
</form>

<script type="text/javascript" async>
    var editor = null;

    // add cc
    $("#cc-mails").selectize({
        plugins: ["remove_button"],
        create: true,
        preload: true,
        placeholder: '',
        render: {
            item: function (data, escape) {
                var val = data.value;
                return '<div>' +
                    '<span class="title">' +
                    '<span class="name">' + escape(val.substring(val.indexOf(":") + 1)) + '</span>' +
                    '</span>' +
                    '</div>';
            }
        },
        onItemAdd: function (value, $item) {
            var email = value.substring(value.indexOf(":") + 1);
            email = email.trim();

            const regex = /^\S{1,64}@\S{1,255}\.\S{1,255}$/;
            if (!regex.test(email)) {
                this.removeItem(value);
            }
        }
    });

    // add bcc
    $("#bcc-mails").selectize({
        plugins: ["remove_button"],
        create: true,
        preload: true,
        placeholder: '',
        render: {
            item: function (data, escape) {
                var val = data.value;
                return '<div>' +
                    '<span class="title">' +
                    '<span class="name">' + escape(val.substring(val.indexOf(":") + 1)) + '</span>' +
                    '</span>' +
                    '</div>';
            }
        },
        onItemAdd: function (value, $item) {
            var email = value.substring(value.indexOf(":") + 1);
            email = email.trim();

            const regex = /^\S{1,64}@\S{1,255}\.\S{1,255}$/;
            if (!regex.test(email)) {
                this.removeItem(value);
            }
        }
    });

    // update css
    document.getElementById('cc-mails-selectized').style.verticalAlign = '-10px';
    document.getElementById('bcc-mails-selectized').style.verticalAlign = '-10px';

    // get attachments by profiles (fnums)
    var fnums = document.getElementById('fnums').value;
    var formData = new FormData();
    formData.append('fnums', fnums);

    fetch('/index.php?option=com_emundus&controller=messages&task=getattachmentsbyprofiles', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
        .then(data => {
            var profiles_ids = Object.keys(data.attachments);
            document.getElementById('em-select_candidate_file').innerHTML = '<option value="0" selected>' + Joomla.Text._('JGLOBAL_SELECT_AN_OPTION') + '</option>';

            profiles_ids.forEach(profile_id => {
                var profile_label = data.attachments[profile_id].label;

                document.getElementById('em-select_candidate_file').append(new Option('_______' + profile_label + '_______', '', true, true))

                var letters = data.attachments[profile_id].letters;
                letters.forEach(letter => {
                    document.getElementById('em-select_candidate_file').append(new Option(letter.letter_label, letter.letter_id));
                })
            })
        })
        .catch((error) => {
            console.error('Error:', error);
        });

    document.getElementById('cc-box').style.display = 'none';
    document.getElementById('bcc-box').style.display = 'none';

    // Editor loads disabled by default, we apply must toggle it active on page load.
    $( document ).ready(function() {
        <?php if(!empty($this->data['mail_subject'])) : ?>
        document.getElementById('mail_subject').innerText = "<?= $this->data['mail_subject'] ?>";
		<?php endif; ?>

		<?php if(!empty($this->data['mail_from_name'])) : ?>
        document.getElementById('mail_from_name').innerText = "<?= $this->data['mail_from_name'] ?>";
		<?php endif; ?>

		<?php if(!empty($this->data['reply_to_from'])) : ?>
        document.getElementById('reply_to_from').innerText = "<?= $this->data['reply_to_from'] ?>";
		<?php endif; ?>
    });

    // Change file upload string to selected file and reset the progress bar.
    //TODO: Convert to Vanilla JS
    $('#em-file_to_upload').change(function () {
        $('#em-filename').html(this.value.match(/([^\/\\]+)$/)[1]);
        $("#em-progress-wrp .progress-bar").css("width", +0 + "%");
        $("#em-progress-wrp .status").text(0 + "%");
    });

    function showEmailList() {
        document.querySelector('#email-list-modal').classList.toggle('hidden');

        if (document.querySelector('#email-list-modal').classList.contains('hidden')) {
            document.querySelector('.em-form-attachments .form-group').style.position = 'relative';
        } else {
            document.querySelector('.em-form-attachments .form-group').style.position = 'static';
        }

    }

    function openCC() {
        var cc = document.getElementById('cc-box');
        var cc_input = document.querySelector('#cc-box .selectize-control');
        if (cc.style.display === 'block') {
            cc.style.display = 'none';
            cc_input.style.display = 'none';
            document.getElementById('cc-icon').style.transform = 'rotate(0deg)';
        } else {
            cc.style.display = 'block';
            cc_input.style.display = 'block';
            document.getElementById('cc-icon').style.transform = 'rotate(90deg)';
        }
    }

    function openBCC() {
        var bcc = document.getElementById('bcc-box');
        var bcc_input = document.querySelector('#bcc-box .selectize-control');
        if (bcc.style.display === 'block') {
            bcc.style.display = 'none';
            bcc_input.style.display = 'none';
            document.getElementById('bcc-icon').style.transform = 'rotate(0deg)';
        } else {
            bcc.style.display = 'block';
            bcc_input.style.display = 'block';
            document.getElementById('bcc-icon').style.transform = 'rotate(90deg)';
        }
    }

    function openReplyTo() {
        var replyto = document.getElementById('reply_to_div');
        if (replyto.style.display === 'block') {
            replyto.style.display = 'none';
            document.getElementById('replyto-icon').style.transform = 'rotate(0deg)';
        } else {
            replyto.style.display = 'block';
            document.getElementById('replyto-icon').style.transform = 'rotate(90deg)';
        }
    }

    // Loads the template and updates the WYSIWYG editor
    function getTemplate(select) {
        // clear CC and BCC
        var $select_cc = $(document.getElementById('cc-mails'));
        var selectize_cc = $select_cc[0].selectize;
        selectize_cc.clear();

        var $select_bcc = $(document.getElementById('bcc-mails'));
        var selectize_bcc = $select_bcc[0].selectize;
        selectize_bcc.clear();

        document.querySelector('.cc-bcc-mails .plugin-remove_button').innerHTML = '';

        // clear em-attachment-list
        document.getElementById('em-attachment-list').innerText = '';

        // call ajax to getemailbyid
        document.getElementById('can-val').style.cursor = '';
        document.querySelector('#can-val .btn-success').setAttribute('disabled', false);

        /// reset #em-select_candidate_file
        $('#em-select_candidate_file option').each(function () {
            if ($(this).is(":disabled")) {
                $(this).prop('disabled', false);
            }
            $(this).attr('style', '');
            $('#em-select_candidate_file option:selected').removeAttr('selected');
        })

        /// reset #em-select_setup_letters
        $('#em-select_setup_letters option').each(function () {
            if ($(this).is(":disabled")) {
                $(this).prop('disabled', false);
            }
            $(this).attr('style', ''); /// reset style
            $('#em-select_setup_letters option:selected').removeAttr('selected');
        })

        /// reset #em-select_attachment_type
        $('#em-select_attachment_type option:selected').removeAttr('selected');

        if (select.value !== '%') {
            fetch('/index.php?option=com_emundus&controller=email&task=getemailbyid&id=' + select.value)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        var email = data.data.email;

                        document.getElementById('mail_subject').innerText = email.subject;
                        document.getElementById('reply_to_from').innerText = email.emailfrom;

                        if (email.name !== '') {
                            document.getElementById('mail_from_name').innerText = email.name;
                        } else {
                            document.getElementById('mail_from_name').innerText = "<?= $app->getConfig()->get('fromname'); ?>";
                        }

                        document.getElementById('mail_body').value = email.message;
                        const event = new Event("input");
                        document.getElementById('mail_body').dispatchEvent(event);

                        if (data.data.receivers) {
                            var receivers = data.data.receivers;

                            var receiver_cc = [];
                            var receiver_bcc = [];
                            var fabrik_cc = [];
                            var fabrik_bcc = [];

                            for (var index = 0; index < receivers.length; index++) {
                                switch (receivers[index].type) {
                                    case 'receiver_cc_email':
                                        receiver_cc.push(receivers[index].receivers);
                                        break;

                                    case 'receiver_bcc_email':
                                        receiver_bcc.push(receivers[index].receivers);
                                        break;

                                    case 'receiver_cc_fabrik':
                                        fabrik_cc.push(receivers[index].receivers);
                                        break;

                                    case 'receiver_bcc_fabrik':
                                        fabrik_bcc.push(receivers[index].receivers);
                                        break;

                                    default:
                                        break;
                                }
                            }

                            // cc
                            receiver_cc.forEach(cc => {
                                selectize_cc.addOption({
                                    value: "CC: " + cc,
                                    text: cc
                                });
                                selectize_cc.addItem("CC: " + cc);
                            })

                            // bcc
                            receiver_bcc.forEach(bcc => {
                                selectize_bcc.addOption({
                                    value: "BCC: " + bcc,
                                    text: bcc
                                });
                                selectize_bcc.addItem("BCC: " + bcc);
                            })

                            if (fabrik_cc.length > 0 && fabrik_cc != "" && fabrik_cc != null && fabrik_cc != undefined) {
                                var regex_email = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                                // call to controller --> get fabrik value
                                $.ajax({
                                    type: 'post',
                                    url: 'index.php?option=com_emundus&controller=files&task=getfabrikvaluebyid',
                                    dataType: 'json',
                                    data: {
                                        elements: fabrik_cc
                                    },
                                    success: function (data) {
                                        var emails = [];

                                        for (email in data.data) {
                                            if (regex_email.test(data.data[email])) {
                                                emails.push(data.data[email]);
                                                selectize_cc.addOption({
                                                    value: "CC: " + data.data[email],
                                                    text: data.data[email]
                                                });
                                                selectize_cc.addItem("CC: " + data.data[email]);
                                            }
                                        }

                                    },
                                    error: function (jqXHR) {
                                        console.log(jqXHR.responseText);
                                    }
                                })
                            }

                            // do the same thing with bcc receivers
                            if (fabrik_bcc.length > 0 && fabrik_bcc != "" && fabrik_bcc != null && fabrik_bcc != undefined) {
                                var regex_email = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                                // call to controller --> get fabrik value
                                $.ajax({
                                    type: 'post',
                                    url: 'index.php?option=com_emundus&controller=files&task=getfabrikvaluebyid',
                                    dataType: 'json',
                                    data: {
                                        elements: fabrik_bcc
                                    },
                                    success: function (data) {
                                        var emails = [];

                                        for (email in data.data) {
                                            if (regex_email.test(data.data[email])) {
                                                emails.push(data.data[email]);
                                                selectize_bcc.addOption({
                                                    value: "BCC: " + data.data[email],
                                                    text: data.data[email]
                                                });
                                                selectize_bcc.addItem("BCC: " + data.data[email]);
                                            }
                                        }
                                    },
                                    error: function (jqXHR) {
                                        console.log(jqXHR.responseText);
                                    }
                                })
                            }
                        }

                        // get letter attachments block
                        if (data.data.letter_attachment !== null) {
                            var letters = data.data.letter_attachment;
                            letters.forEach(letter => {
                                $('#em-attachment-list').append('' +
                                    '<li class="list-group-item setup_letters" style="padding: 6px 12px; display: flex; align-content: center; justify-content: space-between">' +
                                    '<div class="value hidden">' + letter.id + '</div>' + letter.value +
                                    '<div>' +
                                    '<span class="badge">' + '<span class="glyphicon glyphicon-envelope">' + '</span>' + '</span>' +
                                    '<span class="badge btn-danger" onClick="removeAttachment(this);">' + '<span class="glyphicon glyphicon-remove"></span>' + '</span>' +
                                    '</div>' +
                                    '</li>');
                                /// set selected letter
                                $('#em-select_setup_letters option[value="' + letter.id + '"]').prop('disabled', true);
                                $('#em-select_setup_letters option[value="' + letter.id + '"]').css('font-style', 'italic');
                            })
                        }

                        /// get candidat attachments block * check in the user permission *
						<?php if (EmundusHelperAccess::asAccessAction(4, 'r')) : ?>
                        if (data.data.candidate_attachment !== null) {
                            var attachments = data.data.candidate_attachment;
                            attachments.forEach(attachment => {
                                $('#em-attachment-list').append('' +
                                    '<li class="list-group-item candidate_file" style="padding: 6px 12px; display: flex; align-content: center; justify-content: space-between">' +
                                    '<div class="value hidden">' + attachment.id + '</div>' + attachment.value +
                                    '<div>' +
                                    '<span class="badge">' + '<span class="glyphicon glyphicon-paperclip">' + '</span>' + '</span>' +
                                    '<span class="badge btn-danger" onClick="removeAttachment(this);">' + '<span class="glyphicon glyphicon-remove"></span>' + '</span>' +
                                    '</div>' +
                                    '</li>');
                                /// set selected letter
                                $('#em-select_candidate_file option[value="' + attachment.id + '"]').prop('disabled', true);
                                $('#em-select_candidate_file option[value="' + attachment.id + '"]').css('font-style', 'italic');
                            })
                        }
						<?php endif; ?>
                    } else {
                        /// lock send button
                        document.getElementById('can-val').style.cursor = 'not-allowed';
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        } else {
            document.getElementById("mail_subject").innerText = "<?= $config->get('sitename'); ?>";
            document.getElementById("reply_to_from").innerText = '';
            document.getElementById("mail_from_name").innerText = "<?= $config->get('fromname'); ?>";
            document.getElementById('mail_body').value = '<p>Bonjour [NAME],</p>';
            const event = new Event("input");
            document.getElementById('mail_body').dispatchEvent(event);
        }
    }

    // Used for toggling the options dipslayed in the message templates dropdown.
    function setCategory(element) {
        var category = element.value;
        if (element.value == "%") {
            category = 'all';
        }

        fetch('index.php?option=com_emundus&controller=messages&task=setcategory&category=' + category)
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    var $el = $("#message_template");
                    $('#message_template option:gt(0)').remove();

                    data.templates.forEach(function (value) {
                        $el.append($("<option></option>")
                            .attr("value", value.id).text(value.subject));
                    });
                } else {
                    $("#message_template").append('<span class="alert"> <?= Text::_('ERROR'); ?> </span>')
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                $("#message_template").append('<span class="alert"> <?= Text::_('ERROR'); ?> </span>')
            });
    }


    // Used for reseting a File upload input.
    function resetFileInput(e) {
        e.wrap('<form>').closest('form').get(0).reset();
        e.unwrap();
    }


    // Change the attachment type being uploaded.
    function toggleAttachmentType(toggle) {

        switch (toggle.value) {

            case 'upload':
                $('#upload_file').removeClass('hidden');
                $('#candidate_file').addClass('hidden');
                $('#setup_letters').addClass('hidden');
                $('#uploadButton').removeClass('hidden');
                break;

            case 'candidate_file':
                resetFileInput($('#upload_file'));
                $('#upload_file').addClass('hidden');
                $('#candidate_file').removeClass('hidden');
                $('#setup_letters').addClass('hidden');
                $('#uploadButton').removeClass('hidden');
                break;

            case 'setup_letters':
                resetFileInput($('#upload_file'));
                $('#upload_file').addClass('hidden');
                $('#candidate_file').addClass('hidden');
                $('#setup_letters').removeClass('hidden');
                $('#uploadButton').removeClass('hidden');
                break;

            default:
                resetFileInput($('#upload_file'));
                $('#upload_file').addClass('hidden');
                $('#candidate_file').addClass('hidden');
                $('#setup_letters').addClass('hidden');
                $('#uploadButton').addClass('hidden');
                break;

        }

    }


    // Add file to the list being attached.
    function addFile() {

        switch ($('#em-select_attachment_type :selected').val()) {

            case 'upload':

                // We need to get the file uploaded by the user.
                var file = $("#em-file_to_upload")[0].files[0];
                var upload = new Upload(file);
                // Verification of style size and type can be done here.
                upload.doUpload();

                break;


            case 'candidate_file':

                // we just need to note the reference to the setup_attachment file.
                var file = $('#em-select_candidate_file :selected');

                var alreadyPicked = $('#em-attachment-list li.candidate_file').find('.value:contains("' + file.val() + '")');

                if (alreadyPicked.length == 1) {

                    // Flash the line a certain color to show it's already picked.
                    alreadyPicked.parent().css("background-color", "#C5EFF7");
                    alreadyPicked.parent().css("display", "flex");
                    alreadyPicked.parent().css("align-items", "center");
                    alreadyPicked.parent().css("justify-content", "space-between");
                    alreadyPicked.parent().css("padding", "6px 12px");

                    setTimeout(function () {
                        alreadyPicked.parent().css("background-color", "");
                    }, 500);

                    // $('#em-select_candidate_file option[value="' + file.val() + '"]').css('font-style', 'italic');
                    // $('#em-select_candidate_file option[value="' + file.val() + '"]').prop('disabled', true);
                } else {

                    // Disable the file from the dropdown.
                    file.prop('disabled', true);
                    file.css('font-style', 'italic');
                    // Add the file to the list.
                    $('#em-attachment-list').append(
                        '<li class="list-group-item candidate_file" style="padding: 6px 12px; display: flex; align-content: center; justify-content: space-between">' +
                        '<div class="value hidden">' + file.val() + '</div>' + file.text() +
                        '<div>' +
                        '<span class="badge"><span class="glyphicon glyphicon-paperclip"></span></span>' +
                        '<span class="badge btn-danger" onclick="removeAttachment(this);"><span class="glyphicon glyphicon-remove"></span></span>' +
                        '</div>' +
                        '</li>');

                    // $('#em-select_candidate_file [value="' + file.val() + '"]').prop('disabled', true);
                    // $('#em-select_candidate_file option[value="' + file.val() + '"]').css('font-style', 'italic');
                }

                $('#em-select_candidate_file [value="' + file.val() + '"]').prop('disabled', true);
                $('#em-select_candidate_file option[value="' + file.val() + '"]').css('font-style', 'italic');

                break;

            case 'setup_letters':

                // We need to note the reference to the setup_letters file.
                var file = $('#em-select_setup_letters :selected');
                // var alreadyPicked = $('#em-attachment-list li.setup_letters').find('.value:contains("'+file.val()+'")');

                var alreadyPicked = $('#em-attachment-list li.setup_letters').find('.value:contains("' + file.val() + '")');

                if (alreadyPicked.length == 1) {

                    // Flash the line a certain color to show it's already picked.
                    alreadyPicked.parent().css("background-color", "#C5EFF7");
                    alreadyPicked.parent().css("display", "flex");
                    alreadyPicked.parent().css("align-items", "center");
                    alreadyPicked.parent().css("justify-content", "space-between");
                    alreadyPicked.parent().css("padding", "6px 12px");

                    setTimeout(function () {
                        alreadyPicked.parent().css("background-color", "");
                    }, 500);

                    $('#em-select_setup_letters option[value="' + file.val() + '"]').prop('disabled', true);
                    $('#em-select_setup_letters option[value="' + file.val() + '"]').css('font-style', 'italic');

                } else {

                    // Disable the file from the dropdown.
                    file.prop('disabled', true);
                    file.css('font-style', 'italic');
                    // Add the file to the list.
                    $('#em-attachment-list').append(
                        '<li class="list-group-item setup_letters" style="padding: 6px 12px; display: flex; align-content: center; justify-content: space-between">' +
                        '<div class="value hidden">' + file.val() + '</div>' + file.text() +
                        '<div>' +
                        '<span class="badge"><span class="glyphicon glyphicon-envelope"></span></span>' +
                        '<span class="badge btn-danger" onclick="removeAttachment(this);"><span class="glyphicon glyphicon-remove"></span></span>' +
                        '</div>' +
                        '</li>');

                }

                break;

            default:

                // Nothing selected, this case should not happen.
                $("#em-attachment-list").append('<span class="alert alert-danger"> <?= Text::_('ERROR'); ?> </span>')

                break;

        }
    }


    function removeAttachment(element) {

        element = $(element);

        if (element.parent().parent().hasClass('candidate_file')) {
            // Remove 'disabled' attr from select options.
            $('#em-select_candidate_file option[value="' + element.parent().parent().find('.value').text() + '"]').prop('disabled', false);

            // reset css style
            $('#em-select_candidate_file option[value="' + element.parent().parent().find('.value').text() + '"]').removeAttr('style');
        } else if (element.parent().parent().hasClass('setup_letters')) {
            // Remove 'disabled' attr from select options.
            $('#em-select_setup_letters option[value="' + element.parent().parent().find('.value').text() + '"]').prop('disabled', false);

            // reset css style
            $('#em-select_setup_letters option[value="' + element.parent().parent().find('.value').text() + '"]').removeAttr('style');
        }

        $(element).parent().parent().remove();
    }


    // Helper function for uploading a file via AJAX.
    var Upload = function (file) {
        this.file = file;
    };

    Upload.prototype.getType = function () {
        return this.file.type;
    };
    Upload.prototype.getSize = function () {
        return this.file.size;
    };
    Upload.prototype.getName = function () {
        return this.file.name;
    };
    Upload.prototype.doUpload = function () {
        var that = this;
        var formData = new FormData();

        // add assoc key values, this will be posts values
        formData.append("file", this.file, this.getName().replace(/\s/g, '-').normalize("NFD").replace(/[\u0300-\u036f]/g, ""));
        formData.append("upload_file", true);

        fetch('/index.php?option=com_emundus&controller=messages&task=uploadfiletosend', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    document.querySelector('#error_message').innerText = '';
                    $('#em-attachment-list').append('<li class="list-group-item upload tw-flex tw-items-center tw-justify-between tw-gap-2"><div class="value hidden">' + data.file_path + '</div>' + data.file_name + '<span class="material-symbols-outlined tw-cursor-pointer tw-text-red-600" onClick="removeAttachment(this);">clear</span></li>');
                } else {
                    document.querySelector('#error_message').innerText = data.msg;
                }
            })
            .catch(function (error) {
                document.querySelector('#error_message').innerText = error.msg;
            });
    };

    Upload.prototype.progressHandling = function (event) {
        var percent = 0;
        var position = event.loaded || event.position;
        var total = event.total;
        var progress_bar_id = "";
        if (event.lengthComputable) {
            percent = Math.ceil(position / total * 100);
        }
        // update progressbars classes so it fits your code
        $("#em-progress-wrp .progress-bar").css("width", +percent + "%");
        $("#em-progress-wrp .status").text(percent + "%");
    };
</script>
