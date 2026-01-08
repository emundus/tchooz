<?php

use Joomla\CMS\Language\Text;

defined('JPATH_BASE') or die;

// Add span with id so that element fxs work.
$d = $displayData;

Text::script('PLG_ELEMENT_ACTION_GENERATE_LETTER_SUCCESS_TITLE');
Text::script('PLG_ELEMENT_ACTION_GENERATE_LETTER_SUCCESS_MSG');
Text::script('PLG_ELEMENT_ACTION_GENERATE_LETTER_DOWNLOAD');

?>

<button class="btn tw-btn-primary tw-w-fit" id="<?php echo $d->id; ?>" name="<?php echo $d->name; ?>" type="button">
    <?php echo $d->button_label; ?>
</button>

<div class="em-page-loader tw-hidden" id="action_loader_<?php echo $d->id; ?>"></div>