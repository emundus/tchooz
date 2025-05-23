<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php echo JText::sprintf('HI_CUSTOMER',@$data->name);?>
<br/>
<?php echo JText::sprintf('THANK_YOU_FOR_REGISTERING',HIKASHOP_LIVE);?><br/>
<?php if($data->active){
	echo JText::sprintf('ACCOUNT_MUST_BE_ACTIVATED'); ?>
	<br/><br/>
	<a href="<?php echo $data->activation_url;?>"><?php echo $data->activation_url;?></a>
<?php } ?>
<br/><br/>
<?php echo JText::sprintf('YOU_CAN_LOG_IN_WITH');?><br/>
<?php echo JText::sprintf('HIKA_USERNAME').' : '.$data->username;?><br/>
<?php echo JText::sprintf('HIKA_PASSWORD').' : '.$data->password;?><br/>
<br/>
<?php if(!empty($data->user_partner_activated)){
	echo JText::sprintf('THANK_YOU_FOR_BECOMING_OUR_PARTNER',$data->id,$data->partner_url);
}?>
<br/>
<?php echo JText::sprintf('BEST_REGARDS_CUSTOMER',$mail->from_name);?>
