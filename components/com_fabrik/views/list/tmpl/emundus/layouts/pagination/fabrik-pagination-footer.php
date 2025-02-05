<?php
/**
 * Layout: List Pagination Footer
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.3.3
 */

$d = $displayData;

if ($d->showNav) :
	?>
<div class="list-footer em-mt-24">
	<div class="limit">
		<div class="input-prepend input-append em-list-pagination">
            <?php if($d->showTotal == 1): ?>
                <div class="tw-flex tw-items-center tw-gap-2">
                    <span><?php echo $d->pagesCounter; ?></span>
                    <span>|</span>
                </div>
            <?php endif; ?>
			<span class="add-on">
				<label for="<?php echo $d->listName;?>">
					<?php echo $d->label; ?>
				</label>
			</span>
			<?php echo $d->list; ?>
		</div>
	</div>
	<?php echo $d->links; ?>
	<input type="hidden" name="limitstart<?php echo $d->id; ?>" id="limitstart<?php echo $d->id; ?>" value="<?php echo $d->value; ?>" />
</div>
<?php
else :
	if ($d->showTotal) : ?>
<div class="list-footer">
	<span class="add-on">
			<small>
				<?php echo $d->pagesCounter; ?>
			</small>
	</span>
</div>
<?php
	endif;
endif;