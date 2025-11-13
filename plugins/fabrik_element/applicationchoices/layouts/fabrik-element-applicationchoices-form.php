<?php

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted Access');

Text::script('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_DRAFT');
Text::script('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_WAITING');
Text::script('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_ACCEPTED');
Text::script('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_REJECTED');
Text::script('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CONFIRMED');

$d = $displayData;
?>

<div class="tw-flex tw-items-center tw-gap-4" :id="id_element+'_container'" :key="reload">
    <select name="<?php echo $d->name.'_choice'; ?>" id="<?php echo $d->id.'_choice'; ?>"
            <?php if (sizeof($d->choices) === 1) : ?>disabled="disabled"<?php endif; ?>
            class="fabrikinput <?php if (sizeof($d->choices) === 1) : ?>tw-border-0 !tw-cursor-not-allowed !tw-bg-none hover:!tw-bg-transparent<?php endif; ?>"
            value="<?php echo $d->selected_choice; ?>"
    >
        <?php foreach ($d->choices as $choice) : ?>
            <option value="<?php echo $choice['id']; ?>" <?php if ($choice['id'] == $d->selected_choice) : ?>selected<?php endif; ?>>
                <?php echo $choice['campaign']['label']; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Status dropdown if confirmation parameter is set -->
    <?php if ($d->confirmation) : ?>
        <select name="<?php echo $d->name.'_status'; ?>" id="<?php echo $d->id.'_status'; ?>" class="fabrikinput" value="<?php echo $d->selected_status; ?>">
	        <?php foreach ($d->available_statuses as $status) : ?>
                <option value="<?php echo $status['value']; ?>" <?php if ($status['value'] == $d->selected_status) : ?>selected<?php endif; ?>>
                    <?php echo Text::_($status['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <input type="hidden" class="fabrikinput" name="<?php echo $d->name; ?>" id="<?php echo $d->id; ?>" value="<?php echo $d->selected_choice.'|'.$d->selected_status; ?>" />
</div>
