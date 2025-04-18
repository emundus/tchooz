<?php
defined('JPATH_BASE') or die;

use Joomla\CMS\HTML\HTMLHelper;

$d    = $displayData;
?>
<div class="fabrikSubElementContainer" id="<?= $d->id . '-container'; ?>">
	<div class="row">
		<div class="<?= $d->class; ?>">
			<div class="input-group" id="<?= $d->id . "_td"; ?>" data-td-target="nearest" data-td-toggle="nearest">
				<input id="<?= $d->id . '_input'; ?>" type="text" class="form-control" data-td-target="#<?= $d->id . '_td'; ?>" />
				<span class="input-group-text" data-td-target="#<?= $d->id . '_td'; ?>" data-td-toggle="nearest" >
					<i class="fas fa-calendar"></i>
				</span>
			</div>
		</div>
	</div>
	<input type="text" name="<?= $d->name?>" id="<?= $d->id?>" value="<?= $d->value?>" <?php foreach($d->calOpts as $key => $value) echo "$key = '$value'";?> />
</div>
