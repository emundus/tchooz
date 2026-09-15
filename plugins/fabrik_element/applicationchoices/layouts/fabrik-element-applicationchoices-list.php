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

<div class="tw-flex tw-flex-col tw-gap-2" :id="id_element+'_container'" :key="reload">
    <?php if (empty($d->choices)) : ?>
        <p class="tw-text-gray-500"><?php echo Text::_('PLG_ELEMENT_APPLICATION_CHOICES_LIST_EMPTY'); ?></p>
    <?php else : ?>
        <ul class="tw-flex tw-flex-col tw-gap-2 tw-list-none tw-m-0 tw-p-0">
            <?php foreach ($d->choices as $choice) : ?>
                <li class="tw-flex tw-items-center tw-justify-between tw-gap-4 tw-py-1">
                    <span><?php echo $choice['campaign']['label']; ?></span>
                    <?php echo $choice['state_html']; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <input type="hidden" class="fabrikinput" name="<?php echo $d->name; ?>" id="<?php echo $d->id; ?>" value="<?php echo $d->selected_choice.'|'.$d->selected_status; ?>" />
</div>
