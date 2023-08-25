<?php
/*Frame for JS frontend add, like a radio bootstrap grid item 
*/
defined('JPATH_BASE') or die;

$d = $displayData;
if ($d->optsPerRow < 1)
{
	$d->optsPerRow = 1;
}
if ($d->optsPerRow > 12)
{
	$d->optsPerRow = 12;
}
$label = isset($d->option) ? $d->option->text : '';
$value = isset($d->option) ? $d->option->value : '';
$checked = isset($d->checked ) ? $d->checked : '';
$colSize    = floor(floatval(12) / $d->optsPerRow);
$colClass = (int) $colSize === 12 ? '' : 'col-sm-' . $colSize;
$id = isset($d->option->id) ? $d->option->id : '';
?>
<div class="form-check fabrikgrid_radio <?php echo $colClass;?> ">
	<input type="radio" value="" <?php echo $checked;?> data-role="suboption" name="" id="" class="fabrikinput form-check-input"/>
	<label for= "" class="form-check-label ">	
		<span></span>
	</label>
</div>
