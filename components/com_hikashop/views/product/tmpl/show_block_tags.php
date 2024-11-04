<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.1
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
if(!$this->params->get('show_tags', 1))
	return;

$tagHelper = hikashop_get('helper.tags');
if(!$tagHelper->isCompatible())
	return;

$variant_name = '_main';
$attribs = '';
if(!empty($this->variant_name)) {
	$variant_name = $this->variant_name;
	$main_prod =& $this->row;
	$attribs = ' style="display:none;"';
} else {
	if(!empty($this->element->main)){
		$main_prod =& $this->element->main;
	}else{
		$main_prod =& $this->element;
	}
}
if(!empty($main_prod->product_id)) {
	if(HIKASHOP_J50 && !class_exists('JHelperTags'))
		class_alias('Joomla\CMS\Helper\TagsHelper', 'JHelperTags');
	$main_prod->tags = new JHelperTags;
	$main_prod->tags->getItemTags('com_hikashop.product', $main_prod->product_id);
}
if(!empty($main_prod->tags) || $variant_name == '_main') {
?>
<div id="hikashop_product_tags<?php echo $variant_name; ?>" class="hikashop_product_tags"<?php echo $attribs; ?>><?php
	if(!empty($main_prod->tags)) {
		$main_prod->tagLayout = new JLayoutFile('joomla.content.tags');
		echo $main_prod->tagLayout->render($main_prod->tags->itemTags);
	}
?></div>
<?php } ?>
