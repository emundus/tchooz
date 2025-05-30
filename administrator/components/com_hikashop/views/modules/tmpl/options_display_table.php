<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><div class="hk-row-fluid hikashop_module_edit_display_settings_table" data-type="product_layout" data-layout="product_table">
	<div class="hkc-xl-4 hkc-md-6 hikashop_module_subblock hikashop_module_edit_display_settings_subdiv">
		<div class="hikashop_module_subblock_content">
			<div class="hikashop_module_subblock_title hikashop_module_edit_display_settings_div_title"><?php echo JText::_('HIKA_ITEMS'); ?></div>
			<dl class="hika_options">
				<dt class="hikashop_option_name">
					<label class="field_rows" for="data_module__<?php echo $this->type; ?>_limit"><?php echo JText::_( 'FIELD_ROWS' ); ?></label>
				</dt>
				<dd class="hikashop_option_value">
					<div class="listing_item_quantity_selector" data-name="<?php echo $this->name; ?>">
	<?php
					$colsNb = @$this->element['columns'];
					$rowsNb = 0;
					if(@$this->element['columns'] != 0)
						$rowsNb = round((int)$this->element['limit'] / (int)$this->element['columns']);
					$i = 0;
					for($j = 0; $j < 12; $j++){
						$class = ' listing_table ';
						if($i < $colsNb && $j < $rowsNb)
							$class .= ' selected';
						echo '<div class="col'.$i.' row'.$j.$class.'"></div>';
						echo '<br/>';
					}
	?>
					</div>
					<div class="listing_item_quantity_fields" data-list-type="table">
						<div class="input-append">
							<input type="text" class="hikashop_product_listing_input" name="<?php echo $this->name; ?>[rows]" value="<?php echo $rowsNb; ?>">
							<div class="add-on hikashop_product_listing_input_buttons">
								<div class="hikashop_product_listing_input_button hikashop_product_listing_input_plus" data-ref="<?php echo $this->name; ?>[rows]" data-inc="plus">+</div>
								<div class="hikashop_product_listing_input_button hikashop_product_listing_input_minus" data-ref="<?php echo $this->name; ?>[rows]" data-inc="minus">&ndash;</div>
							</div>
						</div>
					</div>
				</dd>
			</dl>
			<dl class="hika_options" style="display: none;">
				<dt class="hikashop_option_name">
					<label for="data_module__<?php echo $this->type; ?>_limit"><?php echo JText::_( 'NUMBER_OF_ITEMS' ); ?></label>
				</dt>
				<dd class="hikashop_option_value">
					<?php if(!isset($this->element['limit'])) $this->element['limit'] = '20'; ?>
					<input id="data_module__<?php echo $this->type; ?>_limit" type="text" name="<?php echo $this->name; ?>[limit]" value="<?php echo $this->element['limit']; ?>">
				</dd>
			</dl>
		</div>
	</div>
</div>
