<?php

defined('JPATH_BASE') or die;

use Joomla\CMS\Language\Text;

$d = $displayData;
?>
<div class="<?php echo $d->name; ?>_container" id="<?php echo $d->id; ?>_container">
	<div class="row">
		<div class="col-sm-6 <?php echo $d->errorCSS; ?>">
			<div class="card" style="border:10px solid; border-color: grey;">
				<div class="card-body">
					<h5 class="card-title"><?php echo Text::_('PLG_FABRIK_PICKLIST_FROM'); ?></h5>
					<ul id="<?php echo $d->id; ?>_fromlist" class="picklist list-group fromList">
						<?php
foreach ($d->from as $value => $label):
?>
							<li id="<?php echo $d->id; ?>_value_<?php echo $value; ?>" class="picklist list-group-item">
								<?php echo $label; ?>
							</li>
						<?php
endforeach;
?>
						<li class="emptypicklist" style="display:none"><?php echo FabrikHelperHTML::icon('icon-move'); ?>
							<?php echo Text::_('PLG_ELEMENT_PICKLIST_DRAG_OPTIONS_HERE'); ?>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="card" style="border:10px solid; border-color: grey;">
				<div class="card-body">
					<h5 class="card-title"><?php echo Text::_('PLG_FABRIK_PICKLIST_TO'); ?></h5>
					<ul id="<?php echo $d->id; ?>_tolist" class="picklist list-group toList">

						<?php
foreach ($d->to as $value => $label):
?>
							<li id="<?php echo $d->id; ?>_value_<?php echo $value; ?>" class="list-group-item <?php echo $value; ?>">
								<?php echo $label; ?>
							</li>
						<?php
endforeach;
?>

						<li class="emptypicklist" style="display:none"><?php echo FabrikHelperHTML::icon('icon-move'); ?>
							<?php echo Text::_('PLG_ELEMENT_PICKLIST_DRAG_OPTIONS_HERE'); ?>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="<?php echo $d->name; ?>" value="<?php echo htmlspecialchars($d->value, ENT_QUOTES); ?>" id="<?php echo $d->id; ?>" />
	<?php echo $d->addOptionsUi; ?>
</div>