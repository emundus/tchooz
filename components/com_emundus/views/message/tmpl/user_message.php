<?php
/**
 * @package    Joomla
 * @subpackage emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Hugo Moracchini
 */

// no direct access
use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

$app          = Factory::getApplication();
$current_user = $app->getIdentity();
$config       = $app->getConfig();
$editor       = Editor::getInstance('tiptap');

$m_messages = new EmundusModelMessages();

// load all of the available messages, categories (to sort messages),attachments, letters.
$message_categories = $m_messages->getAllCategories();
$message_templates  = $m_messages->getAllMessages();

$email_list = array();
$name_list  = array();
$uids       = array();
?>

<style>
    form {
        margin: 0;
    }

    #emailForm #mceu_15 {
        display: none;
    }

    #emailForm .selectize-input {
        overflow: auto;
    }

    .form-group {
        position: static;
    }

    .form-group .email-input-block {
        height: var(--em-coordinator-form-height);
        display: flex;
        align-items: center;
        padding: 0 var(--p-12);
        border: solid 1px var(--em-coordinator-bc);
        border-radius: var(--em-coordinator-form-br);
        background: var(--neutral-0);
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
        border-radius: var(--em-coordinator-br);
        border: solid 2px transparent;
    }

    div#mail_from_name:focus, div#mail_subject:focus {
        outline-color: #2E90FA;
    }

    div#mail_from_name:hover, div#mail_subject:hover {
        border-radius: var(--em-coordinator-br);
        border: solid 2px var(--em-coordinator-bc);
    }

    #cc-box-label, #bcc-box-label, #replyto-box-label {
        border-radius: var(--em-coordinator-br);
        width: fit-content;
        padding: var(--p-4) var(--p-8) 5px 0;
        margin-left: 0;
    }

    #cc-box-label:hover, #bcc-box-label:hover {
        background: var(--neutral-300);
    }

    #emailForm div#mail_subject {
        min-width: 100%;
    }

    #mail_from_block {
        width: 90%;
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
    <div class="em_email_block" id="em_email_block">
        <div class="form-inline row">

            <!-- Dropdown to select the email categories used. -->
            <div class="form-group col-md-6 col-sm-6 em-form-selectCategory">
                <label for="select_category"><?= Text::_('COM_EMUNDUS_EMAILS_SELECT_CATEGORY'); ?></label>
                <select name="select_category" id="select_category"
                        class="em-border-radius-8 em-mb-16 email-input-block em-w-100" onChange="setCategory(this);">
					<?php if (!$message_categories) : ?>
                        <option value="%"> <?= Text::_('COM_EMUNDUS_EMAILS_NO_CATEGORIES_FOUND'); ?> </option>
					<?php else: ?>
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
					<?php else: ?>
                        <option value="%"> <?= Text::_('COM_EMUNDUS_EMAILS_SELECT_TEMPLATE'); ?> </option>
						<?php foreach ($message_templates as $message_template) : ?>
                            <option value="<?= $message_template->id; ?>"> <?= $message_template->subject; ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
                </select>
                <a class="em-font-size-14 em-pointer em-text-underline" href="emails"
                   target="_blank"><?= Text::_('COM_EMUNDUS_EMAILS_ADD_TEMPLATE'); ?>
                </a>
            </div>
        </div>

        <!-- Add current user to Bcc -->
        <div class="em-form-checkbox-copyEmail tw-flex tw-items-center tw-gap-1">
            <input type="checkbox" id="sendUserACopy">
            <label for="sendUserACopy" style="margin: 0">
				<?= Text::_('COM_EMUNDUS_EMAILS_SEND_COPY_TO_CURRENT_USER'); ?>
            </label>
        </div>

        <div class="form-inline row">
            <div class="form-group em-form-sender em-mt-12 col-md-6 col-sm-6">
                <div class="tw-flex tw-items-center">
                    <label class='em-mr-8' for="mail_from"><?= Text::_('FROM'); ?> :</label>
                    <div id="mail_from_block" class="em-border-radius-8 em-mb-4 email-input-block">
                        <div id="mail_from_name" class="em-p-4-6"
                             contenteditable="true"><?= JFactory::getConfig()->get('fromname') ?></div>
                        <div id="mail_from" class="em-ml-4" contenteditable="false">
                            <em class="em-font-size-14"><?= JFactory::getConfig()->get('mailfrom') ?></em>
                        </div>
                    </div>
                </div>

                <span class="em-font-size-14"><?= Text::_('COM_EMUNDUS_FROM_HELP_TEXT') ?></span>

            </div>

            <div class="form-group em-form-recipients em-mt-12 col-md-6 col-sm-6" style="position: static">

                <div class="email-list-modal hidden" id="email-list-modal">
                    <div class="tw-flex tw-justify-between mb-3">
                        <h3><?= Text::_('COM_EMUNDUS_EMAILS_TO_LIST') ?></h3>
                        <span class="material-icons-outlined pointer" onclick="showEmailList()">close</span>
                    </div>

                    <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap" style="max-height: 150px; overflow-y: auto;">
						<?php foreach ($this->users as $user) : ?>

							<?php if (!empty($user->email)) : ?>
								<?php $email_list[] = $user->email; ?>
								<?php $name_list[] = $user->name; ?>
								<?php $uids[] = $user->id; ?>

                                <span class="label label-default em-mr-8 em-email-label">
                                    <?= $user->email . ' <em class="em-font-size-14">&lt;' . $user->name . '&gt;</em>'; ?>
						</span>
                                <input type="hidden" name="ud[]" id="ud" value="<?= $user->id; ?>"/>
							<?php endif; ?>

						<?php endforeach; ?>
                    </div>
                </div>

                <div class="tw-flex tw-justify-between tw-items-center">
                    <div class="tw-flex tw-items-center">
                        <label class='em-mr-8 em-cursor-text mb-0'><?= Text::_('COM_EMUNDUS_TO'); ?> :</label>

                        <div class="em-border-radius-8">
                            <span class="label label-default em-mr-8 em-email-label">
                                <?= $name_list[0] . ' <em class="em-font-size-14">&lt;' . $email_list[0] . '&gt;</em>'; ?>
                            </span>


							<?php if (count($email_list) > 1) : ?>
                                <span class="label label-default em-mr-8 em-email-label pointer"
                                      onclick="showEmailList()">
                                    +<?= count($email_list) - 1 ?>
                                </span>
							<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <input name="uids" type="hidden" class="inputbox" id="uids" value="<?= implode(',', $uids); ?>"/>
            <input name="mail_from_id" type="hidden" class="inputbox" id="mail_from_id"
                   value="<?= $current_user->id; ?>"/><br>
        </div>


        <div class="form-group em-form-subject em-mt-12">
            <label class='em-mr-8' for="mail_from"><?= Text::_('COM_EMUNDUS_EMAILS_SUBJECT'); ?> :</label>
            <div class="em-border-radius-8 email-input-block em-mb-12">
                <div id="mail_subject"
                     class="em-p-4-6"
                     contenteditable="true"><?= JFactory::getConfig()->get('sitename'); ?></div>
            </div>

            <!-- Email WYSIWYG -->
	        <?php echo $editor->display('mail_body', $this->body, '100%', 0, 0, 0, true, 'mail_body', 'com_emundus', null,
		        [
			        'enable_suggestions' => true,
			        'plugins' => ['bold','italic','underline','strike','link','h1','h2','h3','ul','ol','color','left','right','center']
		        ]);
	        ?>

            <!-- TIP -->
            <p class="em-text-neutral-600 em-mt-8 em-font-size-14t">
				<?= Text::_('COM_EMUNDUS_ONBOARD_VARIABLESTIP'); ?>
            </p>
        </div>

        <div class="form-group">
            <br>
            <hr>
        </div>

        <div class="form-inline row em-form-attachments">

            <div class="form-group col-sm-12 col-md-12">
                <!-- Upload a file from computer -->
                <div class="upload-file em-form-attachments-uploadFile" id="upload_file">

                    <div class="file-browse">
                        <span id="em-filename"><?= Text::_('COM_EMUNDUS_ATTACHMENTS_FILE_NAME'); ?></span>

                        <label for="em-file_to_upload"
                               type="button"><?= Text::_('COM_EMUNDUS_ATTACHMENTS_SELECT_FILE_TO_UPLOAD') ?>
                            <input type="file" accept=".xls,.xlsx,.doc,.docx,.pdf,.png,.jpg,.jpeg,.gif,.odf,.ppt,.pptx,.svg,.csv" id="em-file_to_upload" onChange="addFile();">
                        </label>
                        <p><?php echo Text::_('COM_EMUNDUS_ATTACHMENTS_PLEASE_ONLY'); ?>.xls,.docx,.pdf,.png,.jpg,.jpeg,.gif,.odf,.pptx,.svg,.csv</p>
                    </div>
                </div>
                <span id="error_message" class="tw-text-red-600 tw-font-semibold"></span>
            </div>
        </div>
    </div>
    <div class="em-form-attachments-location tw-w-max tw-mt-2">
        <ul class="list-group tw-flex tw-flex-col tw-gap-2" id="em-attachment-list">
            <!-- Files to be attached will be added here. -->
        </ul>
    </div>

    <input type="hidden" name="task" value=""/>
</form>
<script type="text/javascript">
    var editor = null;

    // Editor loads disabled by default, we apply must toggle it active on page load.
    $(document).ready(() => {
	    <?php if(!empty($this->data['mail_subject'])) : ?>
        document.getElementById('mail_subject').innerText = "<?= $this->data['mail_subject'] ?>";
	    <?php endif; ?>

	    <?php if(!empty($this->data['mail_from_name'])) : ?>
        document.getElementById('mail_from_name').innerText = "<?= $this->data['mail_from_name'] ?>";
	    <?php endif; ?>
    });

    function showEmailList() {
        document.querySelector('#email-list-modal').classList.toggle('hidden');
    }

    // Change file upload string to selected file and reset the progress bar.
    $('#em-file_to_upload').change(function () {
        $('#em-filename').html(this.value.match(/([^\/\\]+)$/)[1]);
        $("#em-progress-wrp .progress-bar").css("width", +0 + "%");
        $("#em-progress-wrp .status").text(0 + "%");
    });

    // Loads the template and updates the WYSIWYG editor
    function getTemplate(select) {

        if (select.value !== '%') {
            fetch('index.php?option=com_emundus&controller=email&task=getemailbyid&id=' + select.value)
                .then(response => response.json())
                .then(function (data) {
                    if (data.status) {
                        var email = data.data.email;

                        document.getElementById('mail_subject').innerText = email.subject;

                        if (email.name !== '') {
                            document.getElementById('mail_from_name').innerText = email.name;
                        } else {
                            document.getElementById('mail_from_name').innerText = "<?= $app->getConfig()->get('fromname'); ?>";
                        }

                        document.getElementById('mail_body').value = email.message;
                        const event = new Event("input");
                        document.getElementById('mail_body').dispatchEvent(event);

                        // Get the attached uploaded file if there is one.
                        if (typeof (email.tmpl.attachment) != 'undefined' && email.tmpl.attachment != null) {
                            $('#em-attachment-list').append('<li class="list-group-item upload"><div class="value hidden">' + email.tmpl.attachment + '</div>' + email.tmpl.attachment.split('\\').pop().split('/').pop() + '<span class="badge em-error-button" style="padding: 0;" onClick="removeAttachment(this);"><span class="glyphicon glyphicon-remove"></span></span><span class="badge"><span class="glyphicon glyphicon-saved"></span></span></li>');
                        }
                    }
                })
                .catch(function (error) {
                    // handle error
                    $("#message_template").append('<span class="alert"> <?= Text::_('ERROR'); ?> </span>')
                })
        } else {
            document.getElementById("mail_subject").innerText = "<?= $config->get('sitename'); ?>";
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

    // Add file to the list being attached.
    function addFile() {
        // We need to get the file uploaded by the user.
        let file = $("#em-file_to_upload")[0].files[0];
        let upload = new Upload(file);
        // Verification of style size and type can be done here.
        upload.doUpload();
    }

    function removeAttachment(element) {
        $(element).parent().remove();
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
                    $('#em-attachment-list').append('<li class="list-group-item upload tw-flex tw-items-center tw-justify-between tw-gap-2"><div class="value hidden">' + data.file_path + '</div>' + data.file_name + '<span class="material-icons-outlined tw-cursor-pointer tw-text-red-600" onClick="removeAttachment(this);">clear</span></li>');
                } else {
                    document.querySelector('#error_message').innerText = data.msg;
                }
            })
            .catch(function (error) {
                document.querySelector('#error_message').innerText = error.msg;
            });
    };

    Upload.prototype.progressHandling = event => {
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
