<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
$location = $this->options['location']; 
$config = hikashop_config();
$style = $config->get('accordion_style', 'default');
?>
<div class="hikashop_accordion_<?php echo $style.'_'.$location; ?>">
<?php
	$workflow = $this->checkoutHelper->checkout_workflow;
	foreach($workflow['steps'] as $k => $step) {
		if($step['content'][0]['task'] == 'end' && empty($this->options['display_end']))
			continue;

		if($location == 'before' && $k > $this->workflow_step) {
			continue;
		}elseif($location == 'after' && $k <= $this->workflow_step) {
			continue;
		}
		$badgeClass = ($k == $this->workflow_step) ? 'hkbadge-current' : ($k < $this->workflow_step ? 'hkbadge-past' : '');
		$stepClass = ($k == $this->workflow_step) ? 'hikashop_cart_step_current' : ($k < $this->workflow_step ? 'hikashop_cart_step_finished' : '');
		if(!empty($step['name'])){
			$key = strtoupper($step['name']);
			$trans = JText::_($key);
			if($trans == $key)
				$name = $step['name'];
			else
				$name = $trans;
		}else{
			$name = JText::_('HIKASHOP_CHECKOUT_'.strtoupper($step['content'][0]['task']));
		}

		if($k < $this->workflow_step) {
			$name = '<a href="'.$this->checkoutHelper->completeLink('&cid='.($k+1).$this->cartIdParam, false, false, false, $this->itemid).'">'.$name.'</a>';
		}
?>
		<div class="<?php echo $stepClass; ?>">
			<span class="hkbadge <?php echo $badgeClass; ?>"><?php echo ($k + 1); ?></span><span class="hikashop_checkout_step_name"><?php echo $name; ?></span>
	</div>
<?php
	}
?>
</div>
