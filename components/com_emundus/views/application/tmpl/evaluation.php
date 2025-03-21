<?php
/**
 * User: brivalland
 * Date: 17/06/16
 * Time: 11:39
 * @package       Joomla
 * @subpackage    eMundus
 * @link          http://www.emundus.fr
 * @copyright     Copyright (C) 2016 eMundus. All rights reserved.
 * @license       GNU/GPL
 * @author        eMundus
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

Factory::getApplication()->getSession()->set('application_layout', 'evaluation');


?>

<div class="row">
    <div class="panel panel-default widget em-container-evaluation">
        <div class="panel-heading em-container-evaluation-heading !tw-bg-profile-full">
            <h3 class="panel-title">
                <span class="glyphicon glyphicon-check"></span>
				<?= Text::_('COM_EMUNDUS_ASSESSMENT'); ?>
				<?php if (EmundusHelperAccess::asAccessAction(8, 'c', Factory::getApplication()->getIdentity()->id, $this->fnum) && !empty($this->url_form)) : ?>
                    <button id="download-evaluation-pdf"
                            class="em-mt-8 em-ml-8"
                            data-fnum="<?= $this->fnum ?>"
                            data-toggle="tooltip"
                            data-placement="right"
                            title="<?= Text::_('COM_EMUNDUS_APPLICATION_DOWNLOAD_EVALUATION_FORM'); ?>"
                    >
                        <span class="material-symbols-outlined" data-fnum="<?= $this->fnum ?>">file_download</span>
                    </button>
				<?php endif; ?>
					<?php if (!empty($this->url_form)) : ?>
                        <a href="<?= $this->url_form; ?>" target="_blank" class="em-flex-row"
                           title="<?= Text::_('COM_EMUNDUS_EVALUATIONS_OPEN_EVALUATION_FORM_IN_NEW_TAB_DESC'); ?>"><span
                                    class="material-symbols-outlined">open_in_new</span></a>
					<?php endif; ?>
					<?php
					if (EmundusHelperAccess::asAccessAction(5, 'd', $this->_user->id, $this->fnum)) :?>
                            <button class="btn btn-danger btn-xs btn-attach"
                                    title="<?= Text::_('COM_EMUNDUS_EVALUATIONS_DELETE_SELECTED_EVALUATIONS'); ?>"
                                    id="em_delete_evals" name="em_delete_evals"
                                    link="index.php?option=com_emundus&controller=evaluation&task=delevaluation&applicant=<?= $this->student->id; ?>&fnum=<?= $this->fnum; ?>">
                                <span class="material-symbols-outlined">delete_outline</span></button>
					<?php endif; ?>
            </h3>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span>
                </button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </div>
        <div class="panel-body em-container-evaluation-body">
            <div class="content" style="display: flex; flex-direction: column;">
				<?php if (!empty($this->evaluation_select)) : ?>
                    <label for="copy_evaltuations"
                           class="em-container-evaluation-body-label"><?= Text::_('COM_EMUNDUS_EVALUATION_PICK_EVAL_TO_COPY'); ?></label>
                    <select id="copy_evaluations">
                        <option value="0" selected><?= Text::_('COM_EMUNDUS_EVALUATION_PICK_EVAL_TO_COPY'); ?></option>
						<?php
						foreach ($this->evaluation_select as $eval) {
							foreach ($eval as $fnum => $evaluators) {
								foreach ($evaluators as $evaluator_id => $title) {
									echo "<option value='" . $fnum . "-" . $evaluator_id . "'>" . $title . "</option>";
								}
							}
						}
						?>
                    </select>
				<?php endif; ?>
                <a id="formCopyButton" href='#' style="display: none;">
                    <div class="btn button copyForm">Copy</div>
                </a>
                <div id="formCopy"></div>
                <div class="form" id="form">
					<?php if (!empty($this->url_form)) : ?>
                        <div class="em-w-100 em-flex-row" style="justify-content: center">
                            <div class="em-loader"></div>
                        </div>
                        <iframe id="iframe" src="<?= $this->url_form; ?>" height="600" width="100%" onload="onLoadIframe(this)">
                        </iframe>
					<?php else : ?>
                        <div class="em_no-form"><?= Text::_($this->message); ?></div>
					<?php endif; ?>
                </div>
                <div class="evaluations" id="evaluations"></div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var iframeEl =  document.getElementById('iframe');
    if (iframeEl) {
        iframeEl.addEventListener('mouseleave', function () {
            resizeIframe(document.getElementById('iframe'));
        });

        iframeEl.addEventListener('mouseover', function () {
            resizeIframe(document.getElementById('iframe'));
        });
    }

    function onLoadIframe(iframe) {
        document.querySelector('.em-loader').classList.add('hidden');
        resizeIframe(iframe);

        var iframe = $('#iframe').contents();

        iframe.find("body").click(function(){
            if (!$('ul.dropdown-menu.open').hasClass('just-open')) {
                $('ul.dropdown-menu.open').hide();
                $('ul.dropdown-menu.open').removeClass('open');
            }
        });
    }

    function resizeIframe(obj) {
        if (obj.contentWindow.document.body) {
            obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
        }
    }

    window.ScrollToTop = function () {
        $('html,body', window.document).animate({
            scrollTop: '0px'
        }, 'slow');
    };

    var url_evaluation = '<?php echo $this->url_evaluation; ?>';

    if (url_evaluation !== '') {
        $.ajax({
            type: "GET",
            url: url_evaluation,
            dataType: 'html',
            success: function (data) {
                $("#evaluations").empty();
                $("#evaluations").append(data);
            },
            error: function (jqXHR) {
                console.log(jqXHR.responseText);
            }
        });
    }

    $('#copy_evaluations').on('change', function () {
        if (this.value != 0) {

            var tmp = this.value.split('-');
            var fnum = tmp[0];
            var evaluator = tmp[1];

            $.ajax({
                type: 'GET',
                url: 'index.php?option=com_emundus&controller=evaluation&task=getevalcopy&format=raw&fnum=' + fnum + '&evaluator=' + evaluator,
                success: function (result) {
                    result = JSON.parse(result);

                    if (result.status) {

                        $('#formCopy').html(result.evaluation);
                        $('#formCopyButton').show();
                        $('div.copyForm').attr('id', result.formID);

                    }

                },
                error: function (jqXHR) {
                    console.log(jqXHR.responseText);
                }
            });
        } else {
            $('#formCopy').html(null);
            $('#formCopyButton').hide();
        }
    });

    $('#formCopyButton').on('click', function (e) {
        e.preventDefault();

        // ID of form we are copying from
        var fromID = $('div.copyForm').attr('id');
        // ID of form we are copying to
        var toID = $("#iframe").contents().find(".fabrikHiddenFields").find('[name="rowid"]').val(),
            fnum = $("#iframe").contents().find('#jos_emundus_evaluations___fnum').val(),
            student_id = parseInt(fnum.substr(-5), 10);

        $.ajax({
            type: 'POST',
            url: 'index.php?option=com_emundus&controller=evaluation&task=copyeval',
            data: {
                from: fromID,
                to: toID,
                fnum: fnum,
                student: student_id
            },
            success: function (result) {
                result = JSON.parse(result);

                if (result.status)
                    $('div#formCopy').before('<p style="color: green">Success</p>');
                else
                    $('div#formCopy').before('<p style="color: red">Failed</p>');
            },
            error: function (jqXHR) {
                console.log("error");
            }
        })
    });

    function getEvalChecked() {
        var checkedInput = new Array();
        $('#evaluations input:checked').each(function () {
            checkedInput.push($(this).data('evalid'));
        });
        return checkedInput
    }

    $(document).on('click', '#em_delete_evals', function (e) {
        var checked = getEvalChecked();

        if (checked.length > 0) {
            Swal.fire({
                title: "<?php echo Text::_('COM_EMUNDUS_EVALUATIONS_CONFIRM_DELETE_SELETED_EVALUATIONS')?>",
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: "<?php echo Text::_('JYES') ?>",
                cancelButtonText: "<?php echo Text::_('JNO') ?>",
                reverseButtons: true,
                customClass: {
                    title: 'em-swal-title',
                    confirmButton: 'em-swal-confirm-button',
                    cancelButton: 'em-swal-cancel-button'
                }
            }).then((result) => {
                if (result.value) {
                    var url = $(this).attr('link');

                    $('#em-modal-actions .modal-body').empty();
                    $('#em-modal-actions .modal-body').append('<div><img src="' + loadingLine + '" alt="' +
                        Joomla.Text._('COM_EMUNDUS_LOADING') + '"/></div>');
                    $('#em-modal-actions .modal-footer').hide();
                    $('#em-modal-actions .modal-dialog').addClass('modal-lg');
                    $('#em-modal-actions .modal').show();
                    $('#em-modal-actions').modal({backdrop: false, keyboard: true}, 'toggle');

                    $.ajax({
                        type: 'post',
                        url: url,
                        dataType: 'json',
                        data: {ids: JSON.stringify(checked)},
                        success: function (result) {
                            $('#em-modal-actions').modal('hide');
                            var url = "index.php?option=com_emundus&view=application&format=raw&layout=evaluation&fnum=<?php echo $this->fnum; ?>";
                            $.ajax({
                                type: 'get',
                                url: url,
                                dataType: 'html',
                                success: function (result) {
                                    $('#em-appli-block').empty();
                                    $('#em-appli-block').append(result);
                                },
                                error: function (jqXHR) {
                                    console.log(jqXHR.responseText);
                                }
                            });
                        },
                        error: function (jqXHR) {
                            console.log(jqXHR.responseText);
                        }
                    });
                }
            });
        } else {
            Swal.fire({
                title: "<?php echo Text::_('COM_EMUNDUS_EVALUATIONS_YOU_MUST_SELECT_EVALUATIONS')?>",
                type: 'warning',
                showCancelButton: false,
                confirmButtonText: "<?php echo Text::_('CONFIRM') ?>",
                reverseButtons: true,
                customClass: {
                    title: 'em-swal-title',
                    confirmButton: 'em-swal-confirm-button',
                    actions: 'em-swal-single-action'
                }
            });
        }
    });

    document.getElementById('download-evaluation-pdf').addEventListener('click', function (e) {
        if (typeof export_pdf === 'function') {
            export_pdf(JSON.stringify({0: <?= $this->fnum ?>}), null, 'evaluation');
        } else {
            console.error('Function export_pdf does not exist');
        }
    });
</script>
