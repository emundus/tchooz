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
		<?php if (1) : ?>
        <div class="panel-heading em-container-form-heading  !tw-bg-profile-full">
            <h3 class="panel-title tw-flex tw-flex-row tw-items-center tw-justify-start">
                <span class="material-symbols-outlined em-color-white tw-mr-2">edit_note</span>
                <span> <?= $this->step->label ?></span>
            </h3>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span>
                </button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </div>
	<?php endif; ?>

        <div class="tw-p-4">
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
                    src="evaluation-step-form?view=form&formid=<?= $this->step->form_id ?>&<?= $this->step->db_table_name ?>___ccid=<?= $this->ccid ?>&<?= $this->step->db_table_name ?>___step_id=<?= $this->step->id ?>&tmpl=component&iframe=1"></iframe>
        </div>
		<?php
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

        // set the height of the iframe to the height of the content
        iframeElement.style.height = iframeElement.contentWindow.document.body.scrollHeight + 'px';
    }
</script>