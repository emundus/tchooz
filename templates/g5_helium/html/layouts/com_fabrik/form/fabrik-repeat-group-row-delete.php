<?php
/**
 * Repeat group delete button for table format
 */

defined('JPATH_BASE') or die;

$d = $displayData;

$eMConfig = JComponentHelper::getParams('com_emundus');
$repeat_icon = $eMConfig->get('repeat_icon', 'clear');
?>
<a class="deleteGroup" href="#">
    <span class="material-symbols-outlined em-form-error-color em-pointer"><?php echo $repeat_icon ?></span>
</a>