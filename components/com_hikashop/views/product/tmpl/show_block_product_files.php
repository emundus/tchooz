<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.4
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><div id="hikashop_product_files_main" class="hikashop_product_files_main">
<?php
if(!empty($this->element->files)) {
	$user = JFactory::getUser();
	$files = array();
	foreach($this->element->files as $file) {
		if(empty($file->download_link))
			continue;
		if(empty($file->file_name))
			$file->file_name = $file->file_path;
		$attributes = '';
		if(!empty($this->element->product_canonical) && strpos(hikashop_currentURL(),$this->element->product_canonical) && !empty($user->guest)) {
			$attributes .= ' rel="nofollow"';
		}
		$files[] = '<a'.$attributes.' class="hikashop_product_file_link" href="' .  $file->download_link . '">' . $file->file_name . '</a>';
	}

	if(count($files)) {
?>
	<fieldset class="hikashop_product_files_fieldset">
		<legend><?php echo JText::_('DOWNLOADS'); ?></legend>
		<?php echo implode('<br/>', $files); ?>
	</fieldset>
<?php
	}
}
?>
</div>
