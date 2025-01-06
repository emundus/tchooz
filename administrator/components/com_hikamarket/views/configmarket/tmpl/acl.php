<?php
/**
 * @package    HikaMarket for Joomla!
 * @version    5.0.0
 * @author     Obsidev S.A.R.L.
 * @copyright  (C) 2011-2024 OBSIDEV. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
if(!empty($this->acl_type)) {
	if($this->acl_type == 'vendor_options')
		echo $this->loadTemplate('options');
	else
		echo $this->loadTemplate('edit');
	return;
}
?>
<div class="iframedoc" id="iframedoc"></div>
<div class="adminform">
	<div id="cpanel">
<?php foreach($this->buttons as $btn) { ?>
		<div class="hkm-icon-wrapper">
			<div class="hkm-icon">
				<a href="<?php echo $btn['url'];?>">
					<span class="<?php echo $btn['icon'];?>" style="background-repeat:no-repeat;background-position:center;height:48px;padding:10px 0;"></span>
					<span><?php echo $btn['name'];?></span>
				</a>
			</div>
		</div>
<?php } ?>
	<div style="clear:both"></div>
	</div>
</div>
