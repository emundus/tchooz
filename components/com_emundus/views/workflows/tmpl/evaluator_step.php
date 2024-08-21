<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (!empty($this->step) && $this->step->type === 'evaluator' && !empty($this->step->form_id)) {
	?>
        <div class="tw-p-4">
            <h2><?= $this->step->label ?></h2>
            <iframe height="600" width="100%" src="index.php?option=com_fabrik&view=form&formid=<?= $this->step->form_id ?>&jos_emundus_evaluations___fnum[value]=<?= $this->fnum ?>&tmpl=component&iframe=1">
            </iframe>
        </div>
	<?php
} else {
    ?>
    <p><?= Text::_('COM_EMUNDU_WORKFLOW_NO_DATA') ?></p>
    <?php
}
?>
