<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$m_users = new EmundusModelUsers();
$jinput = Factory::getApplication()->input;

$euser_columns = $m_users->getColumnsFromProfileForm();
$user_columns = $m_users->getJoomlaUserColumns();

$user_column_labels = array_map(function($field) {
	return Text::_($field->label);
}, $user_columns);

$euser_columns = array_filter($euser_columns, function($column) use ($user_column_labels) {
	return !in_array(Text::_($column->label), $user_column_labels);
});

?>

<div>
    <div>
        <span class="tw-mt-2 tw-mb-4 tw-block"><?= JText::_('COM_EMUNDUS_EXPORTS_SELECT_INFORMATIONS'); ?></span>
    </div>
    <div class="form-group tw-flex tw-items-center">
        <div class="all-boxes tw-flex tw-items-center">
            <input type="checkbox" id="checkbox-all" name="checkbox-all" value="all"
                   onchange="checkAllUserElement(this)" class="tw-mr-2">
            <label for="checkbox-all" class="checkbox-label tw-font-bold tw-mt-1"><?= JText::_('ALL_FEMININE'); ?></label>
        </div>
    </div>
</div>
<hr class="tw-w-full tw-border-t tw-border-gray-300 tw-my-2">
<div class="tw-flex tw-justify-between tw-items-start">
    <div class="tw-w-1/2">
		<?php
		foreach ($user_columns as $field) {
			?>
            <div class="form-group tw-flex tw-items-center tw-mb-1">
                <input type="checkbox" id="checkbox-<?= $field->name ?>" name="checkbox-csv" value="<?= $field->label ?>" onchange="uncheckCheckboxAllElement(this)" class="tw-mr-1 tw-mt-2">
                <label for="checkbox-<?= $field->name ?>" class="checkbox-label tw-align-middle tw-mt-1.5"><?= Text::_($field->label) ?></label>
            </div>
			<?php
		}
		?>
    </div>
    <div class="w-1/2">
		<?php
		foreach ($euser_columns as $column) {
			?>
            <div class="form-group tw-flex tw-items-center tw-mb-1">
                <input type="checkbox" id="checkbox-<?= $column->name ?>" name="checkbox-csv" value="<?= $column->label ?>" onchange="uncheckCheckboxAllElement(this)" class="tw-mr-1 tw-mt-2">
                <label for="checkbox-<?= $column->name ?>" class="checkbox-label tw-align-middle tw-mt-1.5"><?= Text::_($column->label) ?></label>
            </div>
			<?php
		}
		?>
    </div>
</div>
