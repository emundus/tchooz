<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

PluginHelper::importPlugin('emundus');
$dispatcher = Factory::getApplication()->getDispatcher();
$onBeforeLoadChecklist  = new GenericEvent('onCallEventHandler',['onBeforeLoadChecklist', ['fnum' => $this->user->fnum]]);
$dispatcher->dispatch('onCallEventHandler', $onBeforeLoadChecklist);

$app     = Factory::getApplication();
$sesdion = $app->getSession();
$_db     = Factory::getContainer()->get('DatabaseDriver');
$user    = $app->getIdentity();
$chemin  = EMUNDUS_PATH_REL;

$itemid = $app->input->get('Itemid', null);

$eMConfig                = ComponentHelper::getParams('com_emundus');
$mediaConfig             = ComponentHelper::getParams('com_media');
$upload_maxsize          = $mediaConfig->get('upload_maxsize', ini_get("upload_max_filesize"));
$copy_application_form   = $eMConfig->get('copy_application_form', 0);
$can_edit_until_deadline = $eMConfig->get('can_edit_until_deadline', '0');
$can_edit_after_deadline = $eMConfig->get('can_edit_after_deadline', 0);
$status_for_send         = explode(',', $eMConfig->get('status_for_send', 0));
$id_applicants           = $eMConfig->get('id_applicants', '0');
$applicants              = explode(',', $id_applicants);

//ADDPIPE
$addpipe_activation   = $eMConfig->get('addpipe_activation', 0);
$addpipe_account_hash = $eMConfig->get('addpipe_account_hash', null);
$addpipe_eid          = $eMConfig->get('addpipe_eid', null);
$addpipe_showmenu     = $eMConfig->get('addpipe_showmenu', 1);
$addpipe_asv          = $eMConfig->get('addpipe_asv', 0);
$addpipe_dup          = $eMConfig->get('addpipe_dup', 1);
$addpipe_srec         = $eMConfig->get('addpipe_srec', 0);
$addpipe_mrt          = $eMConfig->get('addpipe_mrt', 60);
$addpipe_qualityurl   = $eMConfig->get('addpipe_qualityurl', 'avq/480p.xml');
$addpipe_size         = $eMConfig->get('addpipe_size', '{width:640,height:510}');

$offset = $app->get('offset', 'UTC');
try {
	$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
	$dateTime = $dateTime->setTimezone(new DateTimeZone($offset));
	$now      = $dateTime->format('Y-m-d H:i:s');
}
catch (Exception $e) {
	echo $e->getMessage() . '<br />';
}

if (!empty($this->current_phase) && !empty($this->current_phase->entry_status)) {
	foreach ($this->current_phase->entry_status as $status) {
		$status_for_send[] = $status;
	}
}
$is_app_sent = !in_array($this->_user->status, $status_for_send);

$block_upload = true;
if ($can_edit_after_deadline ||
	(!$is_app_sent && $this->is_campaign_started && !$this->is_dead_line_passed && $this->isLimitObtained !== true) ||
	in_array($this->_user->id, $applicants) ||
	($is_app_sent && $this->is_campaign_started && !$this->is_dead_line_passed && $can_edit_until_deadline && $this->isLimitObtained !== true)) {
	$block_upload = false;
}

function return_bytes($val)
{
	$val  = trim($val);
	$last = strtolower($val[strlen($val) - 1]);
	switch ($last) {
		// Le modifieur 'G' est disponible depuis PHP 5.1.0
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}


if (!empty($this->custom_title)) :?>
    <h1 class="em-checklist-title"><?= $this->custom_title; ?></h1>
<?php endif; ?>
<?php if ($this->show_info_panel) : ?>
    <fieldset>
        <legend><?= $this->need < 2 ? Text::_('COM_EMUNDUS_ATTACHMENTS_CHECKLIST') : Text::_('COM_EMUNDUS_ATTACHMENTS_RESULTS'); ?></legend>
        <div class="<?= $this->need ? 'checklist' . $this->need : 'checklist' . '0'; ?>" id="info_checklist">
            <h3><?= $this->title; ?></h3>
			<?php
			if ($this->sent && count($this->result) == 0) {
				echo '<h3>' . Text::_('COM_EMUNDUS_ATTACHMENTS_APPLICATION_SENT') . '</h3>';
			}
			else {
				echo $this->text;
			}

			if (!$this->need) { ?>
                <h3><a href="<?= $this->sent ? 'index.php?option=com_emundus&task=pdf' : $this->confirm_form_url; ?>"
                       class="<?= $this->sent ? 'appsent' : 'sent'; ?>"
                       target="<?= $this->sent ? '_blank' : ''; ?>"><?= $this->sent ? Text::_('COM_EMUNDUS_APPLICATION_PRINT_APPLICATION') : Text::_('COM_EMUNDUS_APPLICATION_SEND_APPLICATION'); ?></a>
                </h3>
			<?php } ?>
        </div>
    </fieldset>

	<?php
	if (!$this->sent) :?>
        <p class="em-instructions">
        <div id="instructions">
            <h3><?= $this->instructions->title; ?></h3>
			<?= $this->instructions->text; ?>
        </div>
        </p>
	<?php endif; ?>
<?php endif; ?>

<?php if (count($this->attachments) > 0) : ?>

    <div class="tw-text-link-regular tw-cursor-pointer tw-font-semibold tw-flex tw-items-center tw-group tw-mb-4 tw-mt-2" onclick="window.history.go(-1)">
        <span class="material-symbols-outlined tw-mr-1 tw-text-link-regular">navigate_before</span>
        <span class="group-hover:tw-underline" name="Goback"><?php echo Text::_('GO_BACK') ?></span>
    </div>
    <div id="attachment_list" class="em-attachmentList em-repeat-card tw-p-6">
        <iframe id="background-shapes" src="/modules/mod_emundus_campaign/assets/fond-clair.svg"
                alt="<?= Text::_('MOD_EM_FORM_IFRAME') ?>"></iframe>
        <h2 class="after-em-border after:tw-bg-red-800 tw-mb-4"><?php echo Text::_('COM_EMUNDUS_ATTACHMENTS_TITLE') ?></h2>
        <div class="alert alert-info tw-flex tw-items-center tw-gap-1 tw-mt-1">
            <span class="material-symbols-outlined">info</span>
            <div>
                <p><?= Text::_('COM_EMUNDUS_ATTACHMENTS_INFO_UPLOAD_MAX_FILESIZE') . ' ' . $upload_maxsize  . Text::_('COM_EMUNDUS_ATTACHMENTS_MEGABYTES_SHORT'); ?> </p>
            </div>
        </div>
		<?php if ($this->show_info_legend) : ?>
            <div id="legend" class="em-mt-4">
                <div class="em-flex-row em-mb-4">
                    <span class="material-symbols-outlined em-red-600-color em-mr-4">highlight_off</span>
                    <p><?= Text::_('COM_EMUNDUS_ATTACHMENTS_MISSING_DOC'); ?></p>
                </div>
                <div class="em-flex-row em-mb-4">
                    <span class="material-symbols-outlined em-green-500-color em-mr-4">check_circle</span>
                    <p><?= Text::_('COM_EMUNDUS_ATTACHMENTS_SENT_DOC'); ?></p>
                </div>
                <div class="em-flex-row em-mb-4">
                    <span class="material-symbols-outlined em-yellow-600-color em-mr-4">error_outline</span>
                    <p><?= Text::_('COM_EMUNDUS_ATTACHMENTS_MISSING_DOC_FAC'); ?></p>
                </div>
            </div>
		<?php endif; ?>
        <hr/>
		<?php
		$file_upload          = 1;
		$attachment_list_mand = "";
		$attachment_list_opt  = "";
		foreach ($this->attachments as $attachment) {
			if ($attachment->nb == 0) {
				$class = $attachment->mandatory ? 'need_missing' : 'need_missing_fac';
			}
			else {
				$class = 'need_ok';
			}
			$div = '<div id="a' . $attachment->id . '" style="position: relative;top: -65px;"></div>
                <fieldset id="a' . $attachment->id . '" class="em-fieldset-attachment mt-3">
                <div id="l' . $attachment->id . '" class="tw-flex tw-items-center em-ml-8 em-mt-8">';
			if ($attachment->nb == 0) {
				if ($this->show_info_legend) {
					$div .= $attachment->mandatory ? '<span class="material-symbols-outlined em-red-600-color em-mr-4">highlight_off</span>' : '<span class="material-symbols-outlined em-yellow-600-color em-mr-4">error_outline</span>';
				}
			}
			else {
				$div .= '<span class="material-symbols-outlined em-green-500-color em-mr-4">check_circle</span>';
			}
			$div .= '<h4 class="em-mt-0-important">' . $attachment->value . '</h4>';

			$div .= '</div>';

			if (!empty($attachment->description)) {
				$div .= '<p class="em-ml-8 em-mt-8" style="white-space: pre-line">' . $attachment->description . '</p>';
			}

			$div .= '<div>';

			if ($attachment->has_sample && !empty($attachment->sample_filepath)) {
				$div .= '<div class="tw-ml-2 tw-mb-2 tw-flex tw-items-center tw-gap-1 attachment_model">
                            <span>'.Text::_('COM_EMUNDUS_ATTACHMENTS_SAMPLE') . '</span><a class="tw-flex tw-items-center" href="'.Uri::root() . $attachment->sample_filepath.'" target="_blank"> <span class="em-text-underline"> ' . Text::_('COM_EMUNDUS_ATTACHMENTS_SAMPLE_FILE').'</span><span class="material-symbols-outlined tw-ml-2 tw-text-neutral-900">cloud_download</span></a>
                         </div>';
			}

			$div .= '<table id="' . $attachment->id . '" class="table em-fieldset-attachment-table">';
			if ($attachment->nb > 0) {
				foreach ($attachment->liste as $key => $item) {
					$nb  = $key + 1;
					$div .= '<tr><td>';
					if (!empty($item->local_filename)) {
						$div .= $item->local_filename;
					}
					else {
						$div .= Text::_('COM_EMUNDUS_ONBOARD_TYPE_FILE') . ' ' . $nb;
					}
					$div .= ' | <span style="font-size: 13px">' . ucfirst(EmundusHelperDate::displayDate($item->timedate, 'DATE_FORMAT_LC2', 0)) . '</span>';
					if ($this->show_shortdesc_input) {
						$div .= ' | ';
						$div .= empty($item->description) ? Text::_('COM_EMUNDUS_ATTACHMENTS_NO_DESC') : $item->description;
					}
					$div .= '</td></tr>';
					$div .= '<tr class="em-added-files">
                    <td class="em-flex-row">';
					if ($item->can_be_viewed == 1) {
						$div .= '<a class="em-flex-row em-mr-16 tw-btn-tertiary" href="' . $chemin . $this->_user->id . '/' . $item->filename . '" target="_blank"><span class="material-symbols-outlined em-mr-4">visibility</span>' . Text::_('COM_EMUNDUS_ATTACHMENTS_VIEW') . '</a>';
					}
					else {
						$div .= Text::_('COM_EMUNDUS_ATTACHMENTS_CANT_VIEW') . '</br>';
					}
					if (($item->can_be_deleted == 1 || $item->is_validated == "0") && !$block_upload) {
						$div .= '<a onclick="deletedoc(this)" class="em-flex-row em-error-button tw-cursor-pointer" data-url="' . JRoute::_('index.php?option=com_emundus&task=delete&uid=' . $item->id . '&aid=' . $item->attachment_id . '&duplicate=' . $attachment->duplicate . '&nb=' . $attachment->nb . '&Itemid=' . $itemid . '#a' . $attachment->id) . '">
						<span class="material-symbols-outlined em-mr-4">delete_outline</span> ' . Text::_('COM_EMUNDUS_ACTIONS_DELETE') . '</a>';
					}
					else {
						$div .= Text::_('COM_EMUNDUS_ATTACHMENTS_CANT_DELETE') . '</br>';
					}
					$div .= '</td></tr>';
					$div .= '<tr><td><hr class="em-mt-4 em-mb-4"></td></tr>';
				}
			}

			// Disable upload UI if
			if (!$block_upload) {

				if ($attachment->nb < $attachment->nbmax || $this->_user->profile <= 4) {
					$div .= '
                <tr>
                    <td>';
					///Video
					if ($attachment->allowed_types == 'video' && $addpipe_activation == 1) {
						if (version_compare(JVERSION, '4.0', '>')) {
							$wa->registerAndUseScript('com_emundus.checklist.addpipe', 'https://cdn.addpipe.com/2.0/pipe.js', [], ['version' => 'auto', 'relative' => true]);
							$wa->registerAndUseStyle('com_emundus.checklist.addpipe', 'https://cdn.addpipe.com/2.0/pipe.css', [], ['version' => 'auto', 'relative' => true]);
						}
						else {
							$document->addStyleSheet("//cdn.addpipe.com/2.0/pipe.css");
							$document->addScript("//cdn.addpipe.com/2.0/pipe.js");
						}

						$div .= '<div id="recorder-' . $attachment->id . '-' . $attachment->nb . '"></div>';
						$div .= '<pre id="log"></pre>';

						$div .= '<script type="text/javascript">
    
                    var pipeParams = {
                        size: ' . $addpipe_size . ',
                        qualityurl: "' . $addpipe_qualityurl . '", 
                        accountHash:"' . $addpipe_account_hash . '", 
                        payload:"{\"userId\":\"' . $this->_user->id . '\",\"fnum\":\"' . $this->_user->fnum . '\",\"aid\":\"' . $attachment->id . '\",\"lbl\":\"' . $attachment->lbl . '\",\"jobId\":\"' . $this->_user->fnum . '|' . $attachment->id . '|' . date("Y-m-d_H:i:s") . '\"}", 
                        eid:"' . $addpipe_eid . '", 
                        showMenu:' . $addpipe_showmenu . ', 
                        mrt:' . (!empty($attachment->video_max_length) ? $attachment->video_max_length : $addpipe_mrt) . ',
                        sis:0,
                        asv:' . $addpipe_asv . ', 
                        mv:0, 
                        st:1, 
                        ssb:1,
                        dup:' . $addpipe_dup . ',
                        srec:' . $addpipe_srec . '
                    };

                    PipeSDK.insert("recorder-' . $attachment->id . '-' . $attachment->nb . '", pipeParams, function(recorderInserted){
     
                        //DESKTOP EVENTS API
                        recorderInserted.userHasCamMic = function(id,camNr, micNr){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_CAM_ACCESS') . '");
                        }
            
                        recorderInserted.btRecordPressed = function(id){
                            //var args = Array.prototype.slice.call(arguments);
                            //__log("btRecordPressed("+args.join(\', \')+")");
                        }
            
                        recorderInserted.btStopRecordingPressed = function(id){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_STOP_RECORDING') . '");
                        }
            
                        recorderInserted.btPlayPressed = function(id){
                            //var args = Array.prototype.slice.call(arguments);
                            //__log("btPlayPressed("+args.join(\', \')+")");
                        }
            
                        recorderInserted.btPausePressed = function(id){
                            //var args = Array.prototype.slice.call(arguments);
                            //__log("btPausePressed("+args.join(\', \')+")");
                        }
            
                        recorderInserted.onUploadDone = function(recorderId, streamName, streamDuration, audioCodec, videoCodec, fileType, audioOnly, location){
                            document.querySelector(".em-page-loader").style.display = "block";    
                            __log("onUploadDone("+args.join(\', \')+")");
                            recorderInserted.save();
                        }
            
                        recorderInserted.onCamAccess = function(id, allowed){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_CAM_ACCESS_READY') . '");
                        }
            
                        recorderInserted.onPlaybackComplete = function(id){
                            //var args = Array.prototype.slice.call(arguments);
                            //__log("onPlaybackComplete("+args.join(\', \')+")");       
                        }
            
                        recorderInserted.onRecordingStarted = function(id){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_RECORDING') . '");
                        }
            
                        recorderInserted.onConnectionClosed = function(id){
                            //var args = Array.prototype.slice.call(arguments);
                            //__log("onConnectionClosed("+args.join(\', \')+")");
                        }
            
                        recorderInserted.onConnectionStatus = function(id, status){
                            //var args = Array.prototype.slice.call(arguments);
                            //__log("onConnectionStatus("+args.join(\', \')+")");
                        }
            
                        recorderInserted.onMicActivityLevel = function(id, level){
                            //var args = Array.prototype.slice.call(arguments);
                            //__log("onMicActivityLevel("+args.join(\', \')+")");
                        }
            
                        recorderInserted.onFPSChange = function(id, fps){
                            //var args = Array.prototype.slice.call(arguments);
                            //__log("onFPSChange("+args.join(\', \')+")");
                        }
            
                        recorderInserted.onSaveOk = function(recorderId, streamName, streamDuration, cameraName, micName, audioCodec, videoCodec, filetype, videoId, audioOnly, location){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_RECORD_SAVED') . '");
                
                            //reload page
                            recorderInserted.remove();
                            is_file_uploaded("' . $this->_user->fnum . '","' . $attachment->id . '","' . $this->_user->id . '");
                        }
            
                        //DESKTOP UPLOAD EVENTS API
                        recorderInserted.onFlashReady = function(id){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_CLICK_TO_RECORD') . '");
                        }
            
                        recorderInserted.onDesktopVideoUploadStarted = function(recorderId, filename, filetype, audioOnly){
                            //var args = Array.prototype.slice.call(arguments);
                            document.querySelector(".em-page-loader").style.display = "block";
                            __log("' . Text::_('VIDEO_INSTR_UPLOADING') . '");
                        }
            
                        recorderInserted.onDesktopVideoUploadSuccess = function(recorderId, filename, filetype, videoId, audioOnly, location){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_RECORD_SAVED') . '");
                
                            //reload page
                            recorderInserted.remove();
                            is_file_uploaded(' . $this->_user->fnum . ',' . $attachment->id . ',' . $this->_user->id . ');
                        }
            
                        recorderInserted.onDesktopVideoUploadFailed = function(id, error){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_RECORD_FAILED') . '");
                        }
            
                        //MOBILE EVENTS API
                        recorderInserted.onVideoUploadStarted = function(recorderId, filename, filetype, audioOnly){
                            //var args = Array.prototype.slice.call(arguments);
                            document.querySelector(".em-page-loader").style.display = "block";
                            __log("' . Text::_('VIDEO_INSTR_RECORD_SAVED') . '");
                        }
    
                        recorderInserted.onVideoUploadSuccess = function(recorderId, filename, filetype, videoId, audioOnly, location){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_RECORD_SAVED') . '");
                
                            //reload page
                            recorderInserted.remove();
                            is_file_uploaded("' . $this->_user->fnum . '","' . $attachment->id . '","' . $this->_user->id . '");
                        }
            
                        recorderInserted.onVideoUploadProgress = function(recorderId, percent){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_UPLOADING') . '");
                        }
            
                        recorderInserted.onVideoUploadFailed = function(id, error){
                            //var args = Array.prototype.slice.call(arguments);
                            __log("' . Text::_('VIDEO_INSTR_RECORD_FAILED') . '");
                        }
        
                    });
                    function __log(e, data) {
                        log.innerHTML += "\n" + e + " " + (data || "");
                    }
</script>';
					}
					else {
						$div .= '<form id="form-a' . $attachment->id . '" name="checklistForm" class="dropzone em-attachment-dropzone" action="' . JRoute::_('index.php?option=com_emundus&task=upload&duplicate=' . $attachment->duplicate . '&Itemid=' . $itemid) . '" method="post" enctype="multipart/form-data">';
						$div .= '<input type="hidden" name="attachment" value="' . $attachment->id . '"/>
                <input type="hidden" name="duplicate" value="' . $attachment->duplicate . '"/>
                <input type="hidden" name="label" value="' . $attachment->lbl . '"/>
                <input type="hidden" name="required_desc" value="' . $this->required_desc . '"/>
                <div>';
						if ($this->show_shortdesc_input) {
							$div .= '<div class="row"><div class="tw-mb-2"><label><span>' . Text::_('COM_EMUNDUS_ATTACHMENTS_SHORT_DESC') . '</span></label><input type="text" class="em-w-100" maxlength="80" name="description" placeholder="' . (($this->required_desc != 0) ? Text::_('EMUNDUS_REQUIRED_FIELD') : '') . '" /></div></div>';
						}
						if ($this->show_browse_button) {
							$div .= '<div class="row" id="upload-files-' . $file_upload . '"><div class="col-sm-12"><label for="file" class="custom-file-upload"><input class="em-send-attachment" id="em-send-attachment-' . $file_upload . '" type="file" name="file" multiple onchange="processSelectedFiles(this)"/><span style="display: none;" >' . Text::_("COM_EMUNDUS_SELECT_UPLOAD_FILE") . '</span></label>';
						}
						$div .= '<input type="hidden" class="form-control" readonly="">';
						if ($this->show_browse_button) {
							$div .= '<input class="btn btn-success em_send_uploaded_file" name="sendAttachment" type="submit" onclick="document.pressed=this.name" value="' . Text::_('COM_EMUNDUS_ATTACHMENTS_SEND_ATTACHMENT') . '"/></div></div>';
						}
						$div .= '</div>';

						$div .= '<script>
                    var maxFilesize = "' . $upload_maxsize . '";
                    Dropzone.options.formA' . $attachment->id . ' =  {
                        maxFiles: ' . $attachment->nbmax . ',
                        maxFilesize: maxFilesize,
                        dictDefaultMessage: "' . Text::_('COM_EMUNDUS_ATTACHMENTS_UPLOAD_DROP_FILE_OR_CLICK') . '",
                        dictInvalidFileType: "' . Text::_('COM_EMUNDUS_WRONG_FORMAT') . ' ' . $attachment->allowed_types . '",
                        dictFileTooBig:  "' . Text::_('COM_EMUNDUS_FILE_TOO_LARGE') . '",
                        dictMaxFilesExceeded: "' . Text::_('COM_EMUNDUS_MAX_FILES_EXCEEDED') . '",
                        url: "index.php?option=com_emundus&task=upload&duplicate=' . $attachment->duplicate . '&Itemid=' . $itemid . '&format=raw",
                
                        accept: function(file, done) {
                            var sFileName = file.name;
                            var sFileExtension = sFileName.split(".")[sFileName.split(".").length - 1].toLowerCase();
                
                            if (sFileExtension == "php") {
                              done("' . Text::_('COM_EMUNDUS_WRONG_FORMAT') . ' ' . $attachment->allowed_types . '");
                            } else {
                                var allowedExtension = "' . $attachment->allowed_types . '";
                                var n = allowedExtension.indexOf(sFileExtension);
                                
                                var required_desc =  document.querySelector("#form-a' . $attachment->id . ' input[name=\'required_desc\']").value;
                                if (document.querySelector("#form-a' . $attachment->id . ' input[name=\'description\']") && required_desc == 1) {
                                    var desc =  document.querySelector("#form-a' . $attachment->id . ' input[name=\'description\']").value;
                                }
                                
                                if (n >= 0) {
                                    if (required_desc == 1 && desc.trim() === "") {
                                        Swal.fire({
                                            icon: "warning",
                                            title: "' . Text::_("COM_EMUNDUS_ERROR_DESCRIPTION_REQUIRED") . '",
                                            confirmButtonText: "' . Text::_("COM_EMUNDUS_SWAL_OK_BUTTON") . '",
                                            showCancelButton: false,
                                            customClass: {
                                              title: "em-swal-title",
                                              confirmButton: "em-swal-confirm-button",
                                              actions: "em-flex-center",
                                            },
                                        });
                                        done("' . Text::_('COM_EMUNDUS_ERROR_DESCRIPTION_REQUIRED') . '");
                                        this.removeFile(file);
                                    } else {
                                        done();
                                    }
                                } else {           
                                    Swal.fire({
                                            icon: "warning",
                                            title: "' . Text::_("COM_EMUNDUS_WRONG_FORMAT") . ' ' . $attachment->allowed_types . '",
                                            confirmButtonText: "' . Text::_("COM_EMUNDUS_SWAL_OK_BUTTON") . '",
                                            showCancelButton: false,
                                            customClass: {
                                              title: "em-swal-title",
                                              confirmButton: "em-swal-confirm-button",
                                              actions: "em-flex-center",
                                            },
                                        });
                                    done("' . Text::_('COM_EMUNDUS_WRONG_FORMAT') . ' ' . $attachment->allowed_types . '");
                                    this.removeFile(file);
                                }
                            }
                        },
                
                        init: function() {
                
                          this.on("maxfilesexceeded", function(file) {
                            this.removeFile(file);
                            alert("' . Text::_('COM_EMUNDUS_ATTACHMENTS_NO_MORE') . ' : ' . $attachment->value . '. ' . Text::_('COM_EMUNDUS_ATTACHMENTS_MAX_ALLOWED') . ' ' . $attachment->nbmax . '");
                          });
                
                          this.on("success", function(file, responseText) {
                          var profile_attachments_not_uploaded = "' . $this->profile_attachments_not_uploaded_ids . '";
                          profile_attachments_not_uploaded = profile_attachments_not_uploaded.split(",");
                            // Handle the responseText here. For example, add the text to the preview element:
                            var response = JSON.parse(responseText);
                            var id = response["id"];
                            var attachment_id = "' . $attachment->id . '";
                                        
                            if (!response["status"]) {
                                // Remove the file preview.
                                this.removeFile(file);
                                Swal.fire({
                                    icon: "warning",
                                    title: response["message"],
                                    confirmButtonText: "' . Text::_("COM_EMUNDUS_SWAL_OK_BUTTON") . '",
                                    showCancelButton: false,
                                    customClass: {
                                       title: "em-swal-title",
                                       confirmButton: "em-swal-confirm-button",
                                       actions: "em-flex-center",
                                    },
                                });
                            } else {
                                if(profile_attachments_not_uploaded.includes(attachment_id)) {
                                    Swal.fire({
                                        icon: "info",
                                        title: "' . Text::_("COM_EMUNDUS_CHECKLIST_PROFILE_ATTACHMENT_FOUND") . '",
                                        text: "' . Text::_("COM_EMUNDUS_CHECKLIST_PROFILE_ATTACHMENT_FOUND_TEXT") . '",
                                        confirmButtonText: "' . Text::_("COM_EMUNDUS_CHECKLIST_PROFILE_ATTACHMENT_FOUND_UPDATE") . '",
                                        showCancelButton: true,
                                        cancelButtonText: "' . Text::_("COM_EMUNDUS_CHECKLIST_PROFILE_ATTACHMENT_FOUND_CONTINUE_WITHOUT_UPDATE") . '",
                                        reverseButtons: true,
                                        customClass: {
                                           title: "em-swal-title",
                                           confirmButton: "em-swal-confirm-button",
                                           cancelButton: "em-swal-cancel-button",
                                        },
                                    }).then(confirm => {
                                        if (confirm.value) {
                                            uploadintoprofile(attachment_id);
                                        } else{
                                            document.location.reload(true);
                                        }
                                    });
                                } else {
                                    document.location.reload(true);
                                }
                    
                                // Change icon on fieldset
                                document.getElementById("l' . $attachment->id . '").className = "need_ok";
                                document.getElementById("' . $attachment->id . '").className = "need_ok";
                    
                                // Create the remove button
                                var removeButton = Dropzone.createElement("<button>X</button>");
                    
                                // Capture the Dropzone instance as closure.
                                var _this = this;
                    
                                // Listen to the click event
                                removeButton.addEventListener("click", function(e) {
                                  // Make sure the button click does not submit the form:
                                  e.preventDefault();
                                  e.stopPropagation();
                    
                                  // Remove the file preview.
                                  _this.removeFile(file);
                                  // If you want to the delete the file on the server as well,
                                  // you can do the AJAX request here.
                                  $.ajax({
                                    type: "GET",
                                    dataType: "json",
                                    url: "index.php?option=com_emundus&task=delete&uid="+id+"&aid=' . $attachment->id . '&duplicate=' . $attachment->duplicate . '&nb=' . $attachment->nb . '&Itemid=' . $itemid . '&format=raw",
                                    data: ({
                                        format: "raw"
                                    }),
                                    success: function(result) {
                                        if (result.status) { 
                                            // Change icon on fieldset
                                            document.getElementById("l' . $attachment->id . '").className = "";
                                            document.getElementById("' . $attachment->id . '").className = "";
                                            alert("' . Text::_('COM_EMUNDUS_ATTACHMENTS_DELETED') . '");
                                        }
                    
                                    },
                                    error: function(jqXHR, textStatus, errorThrown) {
                                        console.log(jqXHR.responseText);
                                    }
                                  });
                                });
                                // Add the button to the file preview element.
                                file.previewElement.appendChild(removeButton);
                            }
                          });
                          this.on("error", function(file, responseText) {
                              this.removeFile(file);
                              Swal.fire({
                                    icon: "warning",
                                    text: responseText,
                                    confirmButtonText: "' . Text::_("COM_EMUNDUS_SWAL_OK_BUTTON") . '",
                                    showCancelButton: false,
                                    customClass: {
                                       title: "em-swal-title",
                                       confirmButton: "em-swal-confirm-button",
                                       actions: "em-flex-center",
                                    },
                                });
                          });
                        }
                    }
                    </script>';
						$div .= '</form>';
					}
					$div .= '</td>
                </tr>
                <tr class="em-allowed-files">
                    <td>
                    <div class="tw-ml-2">
                     <p style="word-break: break-all;" class="tw-text-neutral-600">'. Text::_('COM_EMUNDUS_ATTACHMENTS_PLEASE_ONLY').' '.$attachment->allowed_types.' | '.Text::sprintf('COM_EMUNDUS_ATTACHMENTS_MAXNB_TIP', $attachment->nbmax).'</p>
                    <div class="tw-flex tw-items-center tw-justify-between">';
					if (!empty($this->attachments_to_upload) && in_array($attachment->id, $this->attachments_to_upload)) {
						$div .= '<button class="btn btn-danger btn-xs em-pointer" onclick="uploadfromprofile(' . "$attachment->id" . ')">' . Text::_('COM_EMUNDUS_USERS_MY_DOCUMENTS_LOAD') . '</button>';
					}

					$div .= '</div></div></td>';

					$div .= '</tr>';
				}
				else {

					$div .= '</tbody>';
				}
			}
			else {
				if ($this->isLimitObtained === true) {
					$app->enqueueMessage(Text::_('LIMIT_OBTAINED'), 'notice');
				}
				else {
					$app->enqueueMessage(Text::_('COM_EMUNDUS_READONLY'), 'warning');
				}
			}
			$div .= '</table></div></fieldset>';
			if ($attachment->mandatory) {
				$attachment_list_mand .= $div;
			}
			else {
				$attachment_list_opt .= $div;
			}

			$file_upload++;
		}
		?>


        <div class="row">
            <div class="col-md-<?= (int) (12 / $this->show_nb_column); ?>">
				<?php
				if ($attachment_list_mand != '') {
					echo '<div id="attachment_list_mand" class="em-container-attachments em-w-100"><h3 class="after-em-border after:bg-neutral-500">' . Text::_('COM_EMUNDUS_ATTACHMENTS_MANDATORY_DOCUMENTS') . '</h3>' . $attachment_list_mand . '</div>';
				}
				?>
            </div>
			<?php
			if ($this->show_nb_column > 1) {
				echo '<div class="ui vertical divider"></div>';
			}
			?>
            <div class="col-md-<?= (int) (12 / $this->show_nb_column); ?>">
				<?php
				if ($attachment_list_opt != '') {
					echo '<div id="attachment_list_opt" class="em-container-attachmentsOpt em-mt-16 em-w-100"><h3 class="after-em-border after:bg-neutral-500">' . Text::_('COM_EMUNDUS_ATTACHMENTS_OPTIONAL_DOCUMENTS') . '</h3>' . $attachment_list_opt . '</div>';
				}
				?>
            </div>

            <div class="col-md-12">
                <div class="tw-flex tw-justify-between">
                    <div>
                    </div>
                    <div class="btn-group cursor-pointer"  <?php if ($block_upload || $this->attachments_prog < 100 || $this->forms_prog < 100) :?> style="opacity: 0.6; cursor: not-allowed !important;" <?php endif; ?>>
                        <div class="btn-group">

                            <button type="button"
								<?php if (!$block_upload && $this->attachments_prog >= 100 && $this->forms_prog >= 100) : ?>
                                    onclick="window.location.href='<?php echo $this->confirm_form_url; ?>'" style="opacity: 1"
								<?php else: ?>
                                    style="opacity: 0.6; cursor: not-allowed !important;" disabled
								<?php endif; ?>
                                    class="btn btn-primary save-btn sauvegarder button save_continue" name="Submit"
                                    id="fabrikSubmit_287">
								<?php echo Text::_('COM_EMUNDUS_ATTACHMENTS_SEND_FILE') ?>
                            </button>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var $ = jQuery.noConflict();
        $(document).on('change', '.btn-file :file', function () {
            var input = $(this),
                numFiles = input.get(0).files ? input.get(0).files.length : 1,
                label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
            input.trigger('fileselect', [numFiles, label]);
        });

        $(document).ready(function () {
            // Set sidebar sticky depends on height of header
            const headerNav = document.getElementById('g-navigation');
            const sidebar = document.querySelector('.view-checklist #g-sidebar');
            if (headerNav && sidebar) {
                sidebar.style.top = headerNav.offsetHeight + 8 + 'px';
                sidebar.style.cssText += 'margin-top: 52px !important;';
            }
            $('.em_send_uploaded_file').attr("disabled", "disabled");

            $('.btn-file :file').on('fileselect', function (event, numFiles, label) {

                var input = $(this).parents('.input-group').find(':text'),
                    log = numFiles > 1 ? numFiles + ' <?= Text::_("FILES_SELECTED"); ?>' : label;

                if (input.length) {
                    input.val(log);
                } else {
                    if (log) alert(log);
                }
            });
        });

        $(document).on('click', '.em_form .document', function (f) {
            var id = $(this).attr('id');
            $("fieldset").removeClass("hover");
            $("#a" + id).addClass("hover");
        });

        function toggleVisu(baliseId) {
            if (document.getElementById && document.getElementById(baliseId) != null) {
                if (document.getElementById(baliseId).style.visibility == 'visible') {
                    document.getElementById(baliseId).style.visibility = 'hidden';
                    document.getElementById(baliseId).style.display = 'none';
                } else {
                    document.getElementById(baliseId).style.visibility = 'visible';
                    document.getElementById(baliseId).style.display = 'block';
                }
            }
        }

        function OnSubmitForm() {
            var btn = document.getElementsByName(document.pressed);

            for (i = 0; i < btn.length; i++) {
                btn[i].disabled = "disabled";
                btn[i].value = "<?= Text::_('COM_EMUNDUS_ATTACHMENTS_SENDING_ATTACHMENT'); ?>";
            }

            switch (document.pressed) {
                case 'sendAttachment':
                    document.checklistForm.action = "<?= Uri::base();?>index.php?option=com_emundus&task=upload&Itemid=<?= $itemid; ?>";
                    break;
                default:
                    return false;
            }
            return true;
        }

        function processSelectedFiles(fileInput) {
            var files = fileInput.files;
            var max_post_size = <?= return_bytes($upload_maxsize);?>;

            var row = fileInput.parentNode.parentNode.parentNode.id;
            var rowId = document.getElementById(row);
            if (files[0].size < max_post_size) {
                if ($(rowId).find('.em-added-file').length > 0) {
                    if (files.length > 0) {
                        $(rowId).find('.em-added-file')[0].innerHTML = files[0].name;
                    } else {
                        $(rowId).find('.em-added-file')[0].innerHTML = "";
                    }
                } else {
                    var fileParagraphe = document.createElement("p");
                    fileParagraphe.className = "em-added-file";
                    if (files.length > 0) {
                        fileParagraphe.innerHTML = files[0].name;
                    } else {
                        fileParagraphe.innerHTML = "";
                    }
                    rowId.append(fileParagraphe);
                }
                $(rowId).find(".em_send_uploaded_file").removeAttr("disabled");
            } else {
                if ($(rowId).find('.em-added-file').length > 0) {
                    $(rowId).find('.em-added-file')[0].innerHTML = "<?= Text::_('COM_EMUNDUS_ATTACHMENTS_ERROR_FILE_TOO_BIG')?>";
                } else {
                    var fileParagraphe = document.createElement("p");
                    fileParagraphe.className = "em-added-file em-added-file-error";
                    fileParagraphe.innerHTML = "<?= Text::_('COM_EMUNDUS_ATTACHMENTS_ERROR_FILE_TOO_BIG')?>";
                    rowId.append(fileParagraphe);
                }
                $(rowId).find(".em_send_uploaded_file").attr("disabled", "disabled");
            }
        }

		<?php if ($this->notify_complete_file == 1 && !$block_upload && $this->attachments_prog >= 100 && $this->forms_prog >= 100) :?>
        $(document).ready(() => {
            Swal.fire({
                icon: 'success',
                title: '<?= Text::_('COM_EMUNDUS_CHECKLIST_FILE_COMPLETE'); ?>',
                confirmButtonText: '<?= Text::_('COM_EMUNDUS_CHECKLIST_SEND_FILE'); ?>',
                showCancelButton: true,
                cancelButtonText: '<?= Text::_('COM_EMUNDUS_ATTACHMENTS_EM_CONTINUE'); ?>',
                reverseButtons: true,
                customClass: {
                    title: 'em-swal-title',
                    cancelButton: 'em-swal-cancel-button',
                    confirmButton: 'em-swal-confirm-button',
                },
            })
                .then(confirm => {
                    if (confirm.value) {
                        window.location.href = '<?= $this->confirm_form_url; ?>';
                    }
                })
        });
		<?php else :?>
        $(document).ready(() => {
			<?php if(!empty($this->attachments_to_upload) && $this->attachments_prog == 0) :?>
			<?php $attachments_label = '';
			foreach ($this->attachments as $attachment) {
				if (in_array($attachment->id, $this->attachments_to_upload)) {
					$attachments_label .= '<p> - ' . $attachment->value . '</p>';
				}
			}
			?>
            var attachments = "<?php echo $attachments_label; ?>";
            Swal.fire({
                icon: 'info',
                title: '<?= Text::_('COM_EMUNDUS_CHECKLIST_PROFILE_FILES_FOUND'); ?>',
                html: '<p><?= Text::_('COM_EMUNDUS_CHECKLIST_PROFILE_FILES_FOUND_TEXT') . '</p><div class="em-mt-8">' . $attachments_label . '</div><p class="em-mt-8">' . Text::_('COM_EMUNDUS_CHECKLIST_PROFILE_FILES_FOUND_TEXT_2'); ?></p>',
                confirmButtonText: '<?= Text::_('COM_EMUNDUS_CHECKLIST_PROFILE_FILES_UPLOAD'); ?>',
                showCancelButton: true,
                cancelButtonText: '<?= Text::_('COM_EMUNDUS_ONBOARD_CANCEL'); ?>',
                reverseButtons: true,
                customClass: {
                    title: 'em-swal-title',
                    cancelButton: 'em-swal-cancel-button',
                    confirmButton: 'em-swal-confirm-button',
                },
            }).then(confirm => {
                if (confirm.value) {
                    uploadfromprofile("<?php echo implode(',', $this->attachments_to_upload); ?>");
                }
            });
			<?php endif ?>
        });
		<?php endif; ?>

        //ADDPIPE check if video is uploaded. If yes, reaload page
        function is_file_uploaded(fnum, aid, applicant_id) {
        let is_file_uploaded_timer = setInterval(function(){

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: 'index.php?option=com_emundus&view=webhook&controller=webhook&task=is_file_uploaded&format=raw',
                    data: ({
                        fnum: fnum,
                        aid: aid,
                        applicant_id: applicant_id
                    }),
                    success: function (result) {
                //console.log(result.status + " :: " + result.fnum + " :: " + result.aid + " :: " + result.applicant_id + " :: " + result.user_id + " :: " + result.user_fnum + " :: " + result.query)
                        if (result.status) {
                    document.querySelector(".em-page-loader").style.display = "none";

                    clearInterval(is_file_uploaded_timer);

                    Swal.fire({
                        icon: 'success',
                        title: "<?= Text::_('COM_EMUNDUS_UPLOAD_SUCCESS'); ?>",
                        showCancelButton: false,
                        showConfirmButton: false,
                        customClass: {
                            title: 'em-swal-title'
                        },
                        timer: 3000
                    }).then(() => {
                            window.location.reload(true);
                    });
                        }
                    },
                    error: function (jqXHR) {
                        console.log("ERROR: " + jqXHR.responseText);

                Swal.fire({
                    icon: 'error',
                    title: "<?= Text::_('COM_EMUNDUS_ERROR_OCCURED'); ?>",
                    showCancelButton: false,
                    showConfirmButton: false,
                    customClass: {
                        title: 'em-swal-title'
                    },
                    timer: 3000
                }).then(() => {
                    window.location.reload(true);
                });
                    }
                });
            }, 500);

        }

        function uploadfromprofile(attachments_to_upload) {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: 'index.php?option=com_emundus&controller=users&task=uploadprofileattachmenttofile',
                data: ({
                    aids: attachments_to_upload
                }),
                success: function (result) {
                    if (result.status) {
                        clearInterval();
                        window.location.reload(true);
                    }
                },
                error: function (jqXHR) {
                    console.log("ERROR: " + jqXHR.responseText);
                }
            });
        }

        function uploadintoprofile(aid) {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: 'index.php?option=com_emundus&controller=users&task=uploadfileattachmenttoprofile',
                data: ({
                    aid: aid
                }),
                success: function (result) {
                    if (result.status) {
                        document.location.reload(true);
                    }
                },
                error: function (jqXHR) {
                    console.log("ERROR: " + jqXHR.responseText);
                }
            });
        }

        function deletedoc(element) {
            Swal.fire({
                title: "<?= JText::_('COM_EMUNDUS_FORM_BUILDER_DELETE_DOCUMENT'); ?>",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#dc3545",
                reverseButtons: true,
                confirmButtonText: "<?php echo JText::_('JYES');?>",
                cancelButtonText: "<?php echo JText::_('JNO');?>"
            }).then((confirm) => {
                if (confirm.value) {
                    document.location.href = element.getAttribute('data-url');
                }
            });
        }

    </script>

<?php else: ?>
    <div id="attachment_list" class="em-attachmentList em-repeat-card em-w-100">
        <h3><?= Text::_('COM_EMUNDUS_CHECKLIST_NO_DOCUMENTS_ASSOCIATED_TO_FORM') ?></h3>
        <p class="em-mt-16"><?= Text::_('COM_EMUNDUS_CHECKLIST_NO_DOCUMENTS_ASSOCIATED_TO_FORM_DESC') ?></p>
    </div>
<?php endif; ?>

