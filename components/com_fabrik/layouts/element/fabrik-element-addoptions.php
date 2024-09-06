<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

$d = $displayData;
?>

<a href="#" title="<?php echo Text::_('COM_FABRIK_ADD'); ?>" class="btn btn-info toggle-addoption tw-w-10 tw-h-10 tw-flex tw-justify-center">
	<?php echo $d->add_image; ?>
</a>
<div class="tw-w-full" style="clear:left">
	<div class="addoption tw-flex tw-flex-wrap tw-items-center">
		<div class="tw-mb-1.5 tw-mt-6"><?php echo Text::_('COM_FABRIK_ADD_A_NEW_OPTION_TO_THOSE_ABOVE'); ?></div>

		<?php
		if (!$d->allowadd_onlylabel && $d->savenewadditions) : ?>
			<label for="<?php echo $d->id; ?>_ddVal">
				<?php echo Text::_('COM_FABRIK_VALUE'); ?>
			</label>
			<input class="inputbox text tw-w-full" id="<?php echo $d->id; ?>_ddVal" name="addPicklistValue" />

			<?php if (!$d->onlylabel) : ?>
				<label for="<?php echo $d->id; ?>_ddLabel">
					<?php echo Text::_('COM_FABRIK_LABEL'); ?>
				</label>
				<input class="inputbox text" id="<?php echo $d->id; ?>_ddLabel" name="addPicklistLabel" />
			<?php endif; ?>
		<?php else : ?>
			<input class="inputbox text tw-w-full tw-mr-2.5 mt-0 tw-h-10" id="<?php echo $d->id; ?>_ddLabel" name="addPicklistLabel" />
		<?php endif; ?>

		<input class="tw-btn-primary js-tiny-toggler-button em-flex-gap-8 em-flex-row"
			type="button" id="<?php echo $d->id; ?>_dd_add_entry" value="<?php echo Text::_('COM_FABRIK_ADD'); ?>" />
		<?php echo $d->hidden_field; ?>
	</div>
</div>

<script>
    let addButton = document.querySelector('.toggle-addoption')
    let iconButton = document.querySelector('.toggle-addoption .material-symbols-outlined');
    let addProgramButton = document.querySelector('#jos_emundus_setup_programmes___programmes_dd_add_entry');

    let inputAddProgram = document.querySelector('form.fabrikForm[name=form_108] .fabrikGroup .plg-dropdown .fabrikElement #jos_emundus_setup_programmes___programmes_ddLabel');

    addButton.addEventListener('click', function(event) {
        if (iconButton.textContent.trim() === 'add') {
            iconButton.textContent = 'close';
        } else {
            iconButton.textContent = 'add';
        }
    });

    addProgramButton.addEventListener('click', function(event) {
        if (inputAddProgram.value === '') {
            iconButton.textContent = 'close';
        } else if(inputAddProgram.value !== '') {
            iconButton.textContent = 'add';
        }
    });
</script>
