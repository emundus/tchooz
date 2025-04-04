<?php

defined('JPATH_BASE') or die;

$d = $displayData;
?>

<input type="number" class="fabrikinput inputbox tw-cursor-not-allowed" readonly="readonly" name="<?php echo $d->name;?>"
       id="<?php echo $d->id?>" value="<?php echo $d->value; ?>" step="0.01" />