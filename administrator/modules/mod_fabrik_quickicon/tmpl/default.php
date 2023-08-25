<?php
/**
 * Administrator QuickIcon
 *
 * @package        Joomla.Administrator
 * @subpackage     mod_quickicon
 * @copyright      Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license        GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

?>
<?php if (!empty($buttons) && $menuLinks): ?>
<div class="cpanel px-3 pb-3">
	<div>
		<?php foreach ($buttons as $button) : ?>
			<div class="row-striped">
				<div class="row" <?php echo empty($button['id']) ? '' : ' id="' . $button['id'] . '"' ?>>
					<div class="col-md-12">
						<a href="<?php echo $button['link']; ?>"
							<?php
							echo empty($button['target']) ? '' : ' target="' . $button['target'] . '" ';
							echo empty($button['onclick']) ? '' : ' onclick="' . $button['onclick'] . '" ';
							echo empty($button['title']) ? '' : ' title="' . htmlspecialchars($button['title']) . '" ';
							?>
						>
							<img style="width:16px" src="<?php echo JURI::base(true) . $button['image']; ?>" />
							<?php echo empty($button['text']) ? '' : '<span>' . $button['text'] . '</span>'; ?>
						</a>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>


	<div>
		<?php
		$items = array();
		foreach ($lists as $list) :
			$items[] = '
				<a href="index.php?option=com_fabrik&task=list.view&listid=' . $list->id . '" >
					<span class="' . $list->icon . '"></span> <span style="margin-left:6px">' . $list->label . '</span>
				</a>
			';
		endforeach;
		if (!empty($items)) {
		echo '<hr>' . FabrikHelperHTML::bootstrapGrid($items, 1, '', true);
		}
		?>
	</div>
<?php endif;
?>
