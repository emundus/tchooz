<?php
/**
 * Bootstrap Form Template - Actions
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');

$form = $this->form;
$countActions = 0;
if ( $form->gobackButton || $form->resetButton || $form->deleteButton || $form->clearMultipageSessionButton) {
    $countActions++;
}
if ( $form->submitButton || $form->applyButton || $form->copyButton ) {
    $countActions++;
}
if ($form->customButtons) {
    $countActions++;
}
if ( $form->prevButton || $form->nextButton ) {
    $countActions++;
}

if ($this->hasActions) : ?>
<div class="fabrikActions form-actions tw-p-0 tw-m-0">
	<div
		<?php if (!($form->db_table_name == 'jos_emundus_users' && Factory::getApplication()->getIdentity()->guest == 1)) : ?>
            class="tw-w-full tw-flex <?php if($countActions > 1) : ?>tw-justify-between tw-items-center<?php else : ?>tw-justify-end<?php endif; ?>"
        <?php endif; ?>
    >
        <?php if ( $form->gobackButton || $form->resetButton || $form->deleteButton || $form->clearMultipageSessionButton): ?>
        <div>
            <div class="btn-group">
                <?php
                echo $form->resetButton;
                echo $form->deleteButton;
                echo $form->clearMultipageSessionButton;
                ?>
            </div>
        </div>
        <?php endif; ?>
		<?php if ( $form->submitButton || $form->applyButton || $form->copyButton ): ?>
			<div class="em-submit-form-button">
				<div class="btn-group">
					<?php
					echo $form->submitButton . ' ';
					echo $form->applyButton . ' ';
					echo $form->copyButton;
					?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ($form->customButtons): ?>
			<div class="fabrikCustomButtons <?php echo FabrikHelperHTML::getGridSpan(2); ?>">
				<div class="btn-group">
					<?php echo $form->customButtons; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( $form->prevButton || $form->nextButton ): ?>
			<div>
				<div class="btn-group">
					<?php echo $form->prevButton . ' ' . $form->nextButton; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php
endif;
