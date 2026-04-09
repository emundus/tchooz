<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
$m_workflow = new EmundusModelWorkflow();

$currentLanguage = Factory::getApplication()->getLanguage()->getTag();
$defaultLanguage = ComponentHelper::getParams('com_languages')->get('site', 'fr-FR');
if ($currentLanguage !== $defaultLanguage)
{
	$currentLangPath = '/' . substr($currentLanguage, 0, 2);
}
else
{
	$currentLangPath = '';
}

$data = LayoutFactory::prepareVueData();

$datas = [
    ...$data,
    'fnum' => $this->fnum,
    'defaultCcid' => EmundusHelperFiles::getIdFromFnum($this->fnum),
    'stepId' => !empty($this->step) ? $this->step->id : null,
]

?>

<div class="panel panel-default widget em-container-evaluator-step tw-h-full">
	<?php
	if (!empty($this->step) && $m_workflow->isEvaluationStep($this->step->type) && !empty($this->step->form_id) && $this->access['can_see'])
	{
		?>
        <div class="panel-heading em-container-form-heading !tw-bg-profile-full">
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
            </div>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span>
                </button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </div>

        <div class="tw-px-4" style="height: calc(100% - 52px);">
			<?php if (!EmundusHelperAccess::isDataAnonymized($this->user->id) && $this->applicant->is_anonym != 1 && !$this->applicationFile->isAnonymous()) : ?>
                <div class="tw-flex tw-flex-row tw-items-center em-mt-16">
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

            <!-- display evaluator step forms -->
            <div id="em-component-vue"
                 component="Evaluations"
                 class="tw-h-full"
                 data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
            >
            </div>

            <script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash']  . rand(0, 99999) ?>"></script>

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

<style>
    #evaluations-container {
        height: calc(100vh - 151px);
    }
</style>