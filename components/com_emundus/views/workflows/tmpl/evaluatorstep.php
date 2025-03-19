<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
$m_workflow = new EmundusModelWorkflow();
?>

<div class="panel panel-default widget em-container-evaluator-step">
	<?php
	if (!empty($this->step) && $m_workflow->isEvaluationStep($this->step->type) && !empty($this->step->form_id) && $this->access['can_see'])
	{
		?>
        <div class="panel-heading em-container-form-heading  !tw-bg-profile-full">
            <div class="tw-flex tw-flex-row">
                <h3 class="panel-title tw-flex tw-flex-row tw-items-center tw-justify-start">
                    <span class="material-symbols-outlined em-color-white tw-mr-2">edit_note</span>
                    <span> <?= $this->step->label ?></span>
                    <?php
                        if (!empty($this->evaluation_row_id) && EmundusHelperAccess::asAccessAction($this->step->action_id, 'd', $this->user->id, $this->fnum))
                        {
                            ?>
                            <span id="delete_evaluation"
                                  data-fnum="<?= $this->fnum ?>"
                                  data-step_id="<?= $this->step->id ?>"
                                  data-row_id="<?= $this->evaluation_row_id ?>"
                                  class="material-symbols-outlined tw-text-white tw-mr-2 tw-cursor-pointer">delete</span>
                            <?php
                        }
                    ?>
                </h3>
	            <?php
	            if (EmundusHelperAccess::asAccessAction(8, 'c', $this->user->id, $this->fnum)) {
		            ?>
                    <button id="download-evaluation-step-pdf" class="em-mt-8 em-ml-8 tw-cursor-pointer" data-fnum="<?= $this->fnum ?>" data-toggle="tooltip" data-placement="right" title="Télécharger le formulaire d'évaluation">
                        <span class="material-symbols-outlined tw-cursor-pointer tw-text-white" data-fnum="<?= $this->fnum ?>">file_download</span>
                    </button>
		            <?php
	            }
	            ?>
            </div>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span>
                </button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </div>

        <div class="tw-px-4">
			<?php if (!EmundusHelperAccess::isDataAnonymized($this->user->id)) : ?>
                <div class="tw-flex tw-flex-row tw-items-center">
                    <div class="tw-flex tw-flex-row em-small-flex-column em-small-align-items-start">
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
            <iframe id="evaluation-step-iframe" class="tw-mt-4" height="600" width="100%" loading="lazy"
                    src="evaluation-step-form?view=form&formid=<?= $this->step->form_id ?>&<?= $this->step->table ?>___ccid=<?= $this->ccid ?>&<?= $this->step->table ?>___step_id=<?= $this->step->id ?>&tmpl=component&iframe=1"></iframe>
        </div>
		<?php

		/**
         * TODO: if really needed, display the list of other evaluations here :
		 * if (EmundusHelperAccess::asAccessAction($this->step->action_id, 'r', $this->user->id, $this->fnum) && $this->step->multiple == 1) {
            $step_evaluations = $m_workflow->getStepEvaluationsForFile($this->step->id, $this->ccid);
            ?>

            <div class="tw-px-4">
            </div>

            <?php

            foreach($step_evaluations as $evaluation) {

            }
        }*/

	}
    else if (!$this->access['can_see']) {
        ?>
        <p><?= Text::_('ACCESS_DENIED') ?></p>
        <?php
    }
	else
	{
		?>
        <p style="text-align: center" class="tw-p-4"><?= Text::_('COM_EMUNDU_WORKFLOW_NO_DATA') ?></p>
		<?php
	}
	?>
</div>

<script>
    document.getElementById('evaluation-step-iframe').onload = function() {
        let iframeElement = document.getElementById('evaluation-step-iframe');
        iframeElement.style.height = iframeElement.contentWindow.document.body.scrollHeight + 'px';
        iframeElement.contentWindow.document.body.style.background = 'white';
    }

    document.getElementById('download-evaluation-step-pdf').addEventListener('click', function (e) {
        if (typeof export_pdf === 'function') {
            let pdf_elements = {
                profiles: [
                ],
                tables: [
                    'evaluation_steps_<?= $this->step->table_id?>'
                ],
                groups: [],
                elements: []
            };


            export_pdf(JSON.stringify({0: <?= $this->fnum ?>}), null, 'evaluation_step', '<?= $this->step->table_id ?>,evaluation_steps_<?= $this->step->table_id ?>', pdf_elements);
        } else {
            console.error('Function export_pdf does not exist');
        }
    });
</script>