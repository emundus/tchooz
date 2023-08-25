<?php
/**
 * Administrator QuickIcon
 *
 * @package		Joomla.Administrator
 * @subpackage	mod_quickicon
 * @copyright	Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license		GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
?>
<?php 
if (!empty($buttons) || !empty($lists)): 
?>
<nav class="quick-icons px-3 pb-3" aria-label="<?php echo Text::_('MOD_QUICKICON_NAV_LABEL') . ' ' . $module->title; ?>">
	<ul class="nav  flex-wrap">
		<li class="dropdown">
			<a class="dropdown-toggle" data-bs-toggle="dropdown" href="#">Fabrik <span class="caret"></span></a>
			<ul class="dropdown-menu p-2" style="z-index:2000">
				<?php 
				foreach ($buttons as $button) :
				?>
				<li>
					<a class="menu-lists" href="<?php echo $button['link']; ?>">
						<img style="width:16px" src="<?php echo JURI::base(true) . $button['image']; ?>" />
						<?php echo $button['text']?>
					</a>
				</li>
				<?php 
				endforeach;
				?>
				<?php
				if (!empty($lists)) {
					echo '<hr>';
				}
				
				foreach ($lists as $list) :
				?>
				<li>
					<a class="menu-lists" href="index.php?option=com_fabrik&task=list.view&listid=<?php echo $list->id; ?>">
						<span class="<?php echo $list->icon;?>"></span> <?php echo $list->label;?>
					</a>
				</li>
				<?php
				endforeach;?>
			</ul>
		</li>
	</ul>
</nav>
<?php 
endif;
?>

