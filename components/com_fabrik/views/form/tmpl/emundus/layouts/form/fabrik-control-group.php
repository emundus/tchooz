<?php
/**
 * Form element grid row
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.4
 */
defined('JPATH_BASE') or die;
$d = $displayData;

$class = explode(' ', $d->class);

if (in_array('error', $class))
{
	$class[] = 'has-error';

}
$class[] = 'form-group';
if ($d->column) {
	if ($d->startRow) {
		echo "<div class='row'>";
	}
	$class[] = $d->column;
}

if ($d->display_comments) {
  ?>

    <div class="fabrik-element-emundus-container tw-flex !tw-flex-row tw-justify-items-start tw-items-start <?php echo implode(' ', $class);?>">
        <span id="elements-<?= $d->element->element_fabrik_id ?>" style="margin-top: -180px"></span>
        <span class="tw-absolute tw--left-[24px] tw-top-1 material-symbols-outlined tw-cursor-pointer comment-icon tw-mr-5" data-target-type="elements" data-target-id="<?= $d->element->element_fabrik_id ?>">comment</span>
        <div class="tw-w-full">
            <?php echo $d->row;?>
        </div>
    </div>

    <?php
} else {
    ?>
    <div class="<?php echo implode(' ', $class);?>">
        <?php echo $d->row;?>
    </div>
    <?php
}
?>