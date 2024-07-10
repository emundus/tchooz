<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php echo JText::sprintf('HI_CUSTOMER',@$data->customer->name)."\n"."\n";?>
<?php
if(!empty($data->order->order_number))
	$url = $data->order->order_number;
elseif(!empty($data->cart->order_number))
	$url = $data->cart->order_number;
if(!empty($url)) {
	$url .= "\n".'( '.$data->order_url.' )'."\n";
	echo JText::sprintf('ORDER_STATUS_CHANGED_TO',$url,$data->mail_status)."\n"."\n";
}?>
<?php echo JText::sprintf('THANK_YOU_FOR_YOUR_ORDER',HIKASHOP_LIVE)."\n"."\n"."\n";?>
<?php echo str_replace('<br/>',"\n",JText::sprintf('BEST_REGARDS_CUSTOMER',$mail->from_name));?>
