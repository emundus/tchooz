<?php
/**
 * @package    HikaMarket for Joomla!
 * @version    5.0.0
 * @author     Obsidev S.A.R.L.
 * @copyright  (C) 2011-2024 OBSIDEV. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php if( !isset($this->embbed) ) { ?>
<div class="iframedoc" id="iframedoc"></div>
<div>
<form action="<?php echo hikamarket::completeLink('vendor&task=orders'); ?>" method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

<div class="hk-row-fluid">
	<div class="hkc-md-12">
<?php
	echo $this->loadHkLayout('search', array(
		'id' => 'adminForm'
	));
?>
	</div>
	<div class="hkc-md-12">
		<!-- Filters -->
	</div>
</div>
<?php } else { ?>
<div style="float:right; margin:3px;">
	<a class="hikabtn hikabtn-info" href="<?php echo hikamarket::completeLink("shop.order&order_type=sale&filter_vendor=".$this->vendor_id.'&cancel_redirect='.$this->cancelUrl); ?>">
		<i class="fa fa-chevron-right"></i> <?php echo JText::_('SEE_ALL');?>
	</a>
</div>
<?php } ?>
	<table class="adminlist pad5 table table-striped table-hover" style="width:100%">
		<thead>
			<tr>
<?php if(!isset($this->embbed)) { ?>
				<th class="hikamarket_order_num_title title titlenum"><?php
					echo JHTML::_('grid.sort', JText::_('HIKA_NUM'), 'a.order_id', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value);
				?></th>
<?php } ?>
				<th class="hikamarket_order_id_title title"><?php
					if(isset($this->embbed))
						echo JText::_('ORDER_NUMBER');
					else
						echo JHTML::_('grid.sort', JText::_('ORDER_NUMBER'), 'a.order_number', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value);
				?></th>
				<th class="hikamarket_order_customer_title title"><?php
					if(isset($this->embbed))
						echo JText::_('CUSTOMER');
					else
						echo JHTML::_('grid.sort', JText::_('CUSTOMER'), 'c.name', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value);
				?></th>
				<th class="hikamarket_order_status_title title"><?php
					if(isset($this->embbed))
						echo JText::_('ORDER_STATUS');
					else
						echo JHTML::_('grid.sort', JText::_('ORDER_STATUS'), 'a.order_status', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value);
				?></th>
				<th class="hikamarket_order_date_title title"><?php
					if(isset($this->embbed))
						echo JText::_('DATE');
					else
						echo JHTML::_('grid.sort', JText::_('DATE'), 'a.order_modified', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value);
				?></th>
				<th class="hikamarket_order_vendor_total_title title"><?php
					if(isset($this->embbed))
						echo JText::_('VENDOR_TOTAL');
					else
						echo JHTML::_('grid.sort', JText::_('VENDOR_TOTAL'), 'a.order_vendor_price', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value);
				?></th>
				<th class="hikamarket_order_total_title title"><?php
					if(isset($this->embbed))
						echo JText::_('HIKASHOP_TOTAL');
					else
						echo JHTML::_('grid.sort', JText::_('HIKASHOP_TOTAL'), 'a.order_full_price', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value);
				?></th>
			</tr>
		</thead>
<?php if(!isset($this->embbed)) { ?>
		<tfoot>
			<tr>
				<td colspan="7">
					<?php echo $this->pagination->getListFooter(); ?>
					<?php echo $this->pagination->getResultsCounter(); ?>
				</td>
			</tr>
		</tfoot>
<?php } ?>
		<tbody>
<?php
$k = 0;
$i = 0;
foreach($this->orders as $order) {
?>
			<tr class="row<?php echo $k; ?>">
<?php if(!isset($this->embbed)) { ?>
				<td class="hikamarket_order_num_value"><?php
					echo $this->pagination->getRowOffset($i);
				?></td>
<?php } ?>
				<td class="hikamarket_order_id_value">
					<a href="<?php echo hikamarket::completeLink('shop.order&task=edit&cid[]='.$order->order_id.'&cancel_redirect='.$this->cancelUrl); ?>"><?php echo $order->order_number; ?></a>
				</td>
				<td class="hikamarket_order_customer_value"><?php
					echo $order->user_email;
				?></td>
				<td class="hikamarket_order_status_value"><span class="order-label order-label-<?php echo preg_replace('#[^a-z_0-9]#i', '_', str_replace(' ','_',$order->order_status)); ?>"><?php
					echo hikamarket::orderStatus($order->order_status);
				?></span></td>
				<td class="hikamarket_order_date_value"><?php
					echo hikamarket::getDate($order->order_created,'%Y-%m-%d %H:%M');
				?></td>
				<td class="hikamarket_order_vendor_total_value"><?php
					echo $this->currencyHelper->format($order->order_vendor_price, $order->order_currency_id);
				?></td>
				<td class="hikamarket_order_total_value"><?php
					echo $this->currencyHelper->format($order->order_full_price, $order->order_currency_id);
				?></td>
			</tr>
<?php
	$i++;
	$k = 1 - $k;
}
?>
		</tbody>
	</table>
<?php if( !isset($this->embbed) ) { ?>
	<input type="hidden" name="option" value="<?php echo HIKAMARKET_COMPONENT; ?>" />
	<input type="hidden" name="task" value="orders" />
	<input type="hidden" name="ctrl" value="<?php echo hikaInput::get()->getCmd('ctrl'); ?>" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->pageInfo->filter->order->value; ?>" />
	<input type="hidden" name="filter_order_dir" value="<?php echo $this->pageInfo->filter->order->dir; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
</div>
<?php } ?>
