<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

$d = $displayData;
?>

<a href="#" title="<?php echo Text::_('COM_FABRIK_ADD'); ?>" class="btn btn-info toggle-addoption tw-mt-3">
	<?php echo Text::_('COM_FABRIK_ADD'); ?>
</a>
<div style="clear:left">
	<div class="addoption tw-mt-2">
		<div><?php echo Text::_('COM_FABRIK_ADD_A_NEW_OPTION_TO_THOSE_ABOVE'); ?></div>

		<?php
		if (!$d->allowadd_onlylabel && $d->savenewadditions) : ?>
			<label for="<?php echo $d->id; ?>_ddVal">
				<?php echo Text::_('COM_FABRIK_VALUE'); ?>
			</label>
			<input class="inputbox text" id="<?php echo $d->id; ?>_ddVal" name="addPicklistValue" />

			<?php if (!$d->onlylabel) : ?>
				<label for="<?php echo $d->id; ?>_ddLabel">
					<?php echo Text::_('COM_FABRIK_LABEL'); ?>
				</label>
				<input class="inputbox text" id="<?php echo $d->id; ?>_ddLabel" name="addPicklistLabel" />
			<?php endif; ?>
		<?php else : ?>
			<input class="inputbox text" id="<?php echo $d->id; ?>_ddLabel" name="addPicklistLabel" />
		<?php endif; ?>

		<input class="button btn btn-success tw-mt-2"
			type="button" id="<?php echo $d->id; ?>_dd_add_entry" value="<?php echo Text::_('COM_FABRIK_SAVE'); ?>" />
		<?php echo $d->hidden_field; ?>
	</div>
</div>

