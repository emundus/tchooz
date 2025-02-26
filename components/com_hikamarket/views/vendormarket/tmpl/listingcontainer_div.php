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
if(empty($this->rows))
	return;

$this->align = 'left';
if($this->params->get('text_center', 1))
	$this->align = 'center';

$borderClass = '';
if($this->params->get('border_visible', 1) == 1)
	$borderClass = 'hikamarket_subcontainer_border';
if($this->params->get('border_visible', 1) == 2)
	$borderClass = 'thumbnail';

?><div class="hikamarket_vendors"><?php

	if($this->params->get('enable_carousel', 0)) {
		$this->setLayout('carousel');
		echo $this->loadTemplate();
	} else {
		$columns = (int)$this->params->get('columns');
		if(empty($columns) || $columns < 1)
			$columns = 1;
		$width = (int)(100 / $columns) - 1;
		$current_column = 1;
		$current_row = 1;
		if($this->params->get('only_if_products','-1') == '-1') {
			$defaultParams = $this->shopConfig->get('default_params');
			$this->params->set('only_if_products', @$defaultParams['only_if_products']);
		}
		$only_if_products = $this->params->get('only_if_products', 0);


		switch($columns) {
			case 12:
			case 6:
			case 4:
			case 3:
			case 2:
			case 1:
				$row_fluid = 12;
				$span = $row_fluid / $columns;
				break;
			case 10:
			case 8:
			case 7:
				$row_fluid = $columns;
				$span = 1;
				break;
			case 5:
				$row_fluid = 10;
				$span = 2;
				break;
			case 9: // special case
				$row_fluid = 10;
				$span = 1;
				break;
		}
		if($row_fluid == 12)
			echo '<div class="hk-row-fluid">';
		else
			echo '<div class="hk-row-fluid hk-row-'.$row_fluid.'">';

		foreach($this->rows as $row) {


?>
			<div class="hkc-md-<?php echo $span; ?> hikamarket_vendor hikamarket_listing_column_<?php echo $current_column; ?> hikamarket_listing_row_<?php echo $current_row; ?> hikamarket_vendor_column_<?php echo $current_column; ?> hikamarket_vendor_row_<?php echo $current_row; ?>">
				<div class="hikamarket_container">
					<div class="hikamarket_subcontainer <?php echo $borderClass; ?>">
<?php
			$this->row =& $row;
			$this->setLayout('listingcontent_' . $this->params->get('div_item_layout_type'));
			echo $this->loadTemplate();
?>
					</div>
				</div>
		</div>
<?php
			if($current_column >= $columns) {
				$current_row++;
				$current_column = 0;
			}
			$current_column++;
		}

		echo '</div>';
	}
?>
	<div style="clear:both"></div>
</div>
