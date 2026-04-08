<?php

defined('JPATH_BASE') or die;

$d = $displayData;
?>

<div class="tw-w-full tw-flex tw-flex-row tw-items-center tw-gap-2 tw-pointer-events-none">
    <input type="text" class="fabrikinput emundus-calculation !tw-border-none tw-pl-2 tw-pointer-events-none tw-w-fit" readonly="readonly" name="<?php echo $d->name;?>"
           id="<?php echo $d->id?>" value="<?php echo $d->value; ?>" />

    <?php
    if (!empty($d->suffix))
    {
        echo '<span>' . $d->suffix . '</span>';
    }
    ?>
</div>