<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><div class="hikashop_cardsave_thankyou" id="hikashop_cardsave_thankyou">
	<span id="hikashop_cardsave_thankyou_message" class="hikashop_cardsave_thankyou_message">
		<?php echo JText::_('THANK_YOU_FOR_PURCHASE');
		if(!empty($this->payment_params->return_url)){
			echo '<br/><a href="'.$this->payment_params->return_url.'">'.JText::_('GO_BACK_TO_SHOP').'</a>';
		}?>
	</span>
</div>
<?php
if(!empty($this->payment_params->return_url)){
	$doc = JFactory::getDocument();
	$doc->addScriptDeclaration("window.hikashop.ready( function() {window.location='".$this->payment_params->return_url."'});");
}
