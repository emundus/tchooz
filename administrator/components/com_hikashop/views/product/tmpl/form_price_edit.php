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
	$config = hikashop_config();
	$delay = (int)$config->get('price_cookie_retaining_period', 31557600);
	$selected = 'selected="selected"';
?>
<style>
dt.hidden_price, 
dd.hidden_price {
	display: none;
}
</style>
<dl class="hika_options">
<dt><label class="hk_tbl_key" ><?php echo JText::_('PRODUCT_PRICE_BE'); ?></dt>
	<dd>
		<div class="hikaradios">
			<input type="radio" id="input_price_displayed_0" value="0" class="btn-danger " style="display:none;" onchange="hikashopLocal.radioEvent(this);">
			<input type="radio" id="input_price_displayed_1" value="1" class="btn-success " style="display:none;" onchange="hikashopLocal.radioEvent(this);">
			<input type="radio" id="input_price_displayed_2" value="2" class="btn-primary " style="display:none;" onchange="hikashopLocal.radioEvent(this);">
			<div class="btn-group">
				<label id="label_price_displayed_0" href="#" onclick="window.localPage.priceDisplay(0); return false;" class="btn label_btn">
					<?php echo JText::_('PRODUCT_PRICE_WITHOUT_TAX'); ?>
				</label>
				<label id="label_price_displayed_1" href="#" onclick="window.localPage.priceDisplay(1); return false;" class="btn label_btn">
					<?php echo JText::_('PRODUCT_PRICE_WITH_TAX'); ?>
				</label>
				<label id="label_price_displayed_2" href="#" onclick="window.localPage.priceDisplay(2); return false;" class="btn label_btn">
					<?php echo JText::_('WIZARD_BOTH'); ?>
				</label>
			</div>
		</div>
	</dd>
	<dt class="price_display price_without_tax"><label><?php echo JText::_('PRICE'); ?></label></dt>
	<dd class="price_display price_without_tax">
		<input type="hidden" id="hikashop_<?php echo $this->form_key; ?>_id_edit" name="" value="<?php echo @$this->price->price_id; ?>>"/>
		<input type="text" size="5" style="width:70px;" onchange="window.productMgr.updatePrice(false, '<?php echo $this->form_key; ?>')" id="hikashop_<?php echo $this->form_key; ?>_edit" name="" value="<?php if($this->config->get('floating_tax_prices',0)){ echo @$this->price->price_value_with_tax; }else{ echo @$this->price->price_value; } ?>"/>
	</dd>
<?php
if(!$this->config->get('floating_tax_prices',0)) { ?>
	<dt class="price_display price_with_tax"><label><?php echo JText::_('PRICE_WITH_TAX'); ?></label></dt>
	<dd class="price_display price_with_tax">
		<input type="text" size="5" style="width:70px;" oninput="window.productMgr.updatePrice(true, '<?php echo $this->form_key; ?>')" id="hikashop_<?php echo $this->form_key; ?>_with_tax_edit" name="" value="<?php echo @$this->price->price_value_with_tax; ?>"/>
	</dd>
<?php } ?>
	<dt><label><?php echo JText::_('CURRENCY'); ?></label></dt>
<?php
$width = '80px';
if (HIKASHOP_J40) {
	$width = '110px';
} ?>
	<dd>
		<?php echo $this->currencyType->display('', @$this->price->price_currency_id, 'size="1" style="width:'.$width.'"','hikashop_' . $this->form_key . '_currency_edit'); ?>
	</dd>
	<dt><label><?php echo JText::_('MINIMUM_QUANTITY'); ?></label></dt>
	<dd>
		<input type="text" size="5" style="width:70px;" id="hikashop_<?php echo $this->form_key; ?>_qty_edit" name="" value="<?php echo $this->price->price_min_quantity; ?>"/>
	</dd>
<?php if(hikashop_level(2)) { ?>
	<dt><label><?php echo JText::_('START_DATE'); ?></label></dt>
	<dd>
		<?php echo JHTML::_('calendar', hikashop_getDate((@$this->price->price_start_date?@$this->price->price_start_date:''),'%Y-%m-%d %H:%M'), 'price_start_date', 'hikashop_' . $this->form_key . '_start_date_edit', hikashop_getDateFormat('%d %B %Y %H:%M'), array('size' => '20', 'showTime' => true)); ?>
	</dd>
	<dt><label><?php echo JText::_('END_DATE'); ?></label></dt>
	<dd>
		<?php echo JHTML::_('calendar', hikashop_getDate((@$this->price->price_end_date?@$this->price->price_end_date:''),'%Y-%m-%d %H:%M'), 'price_end_date', 'hikashop_' . $this->form_key . '_end_date_edit', hikashop_getDateFormat('%d %B %Y %H:%M'), array('size' => '20', 'showTime' => true)); ?>
	</dd>
	<dt><label><?php echo JText::_('ACCESS_LEVEL'); ?></label></dt>
	<dd>
		<?php echo $this->joomlaAcl->display('hikashop_' . $this->form_key . '_acl_edit'.$this->price->price_id, @$this->price->price_access, true, true, 'hikashop_' . $this->form_key . '_acl_edit'); ?>
	</dd>
	<dt><label><?php echo JText::_('USERS'); ?></label></dt>
	<dd>
<?php
echo $this->nameboxVariantType->display(
	'hikashop_' . $this->form_key . '_user_edit',
	explode(',',trim((string)@$this->price->price_users,',')),
	hikashopNameboxType::NAMEBOX_MULTIPLE,
	'user',
	array(
		'id' => 'hikashop_' . $this->form_key . '_user_edit',
		'add' => true,
		'default_text' => 'PLEASE_SELECT'
	)
);
?>
	</dd>
	<dt><label><?php echo JText::_('ZONE'); ?></label></dt>
	<dd>
<?php
echo $this->nameboxVariantType->display(
	'hikashop_' . $this->form_key . '_zone_edit',
	explode(',',trim((string)@$this->price->price_zone_id,',')),
	hikashopNameboxType::NAMEBOX_MULTIPLE,
	'zone',
	array(
		'delete' => true,
		'id' => 'hikashop_' . $this->form_key . '_zone_edit',
		'default_text' => '<em>'.JText::_('HIKA_NONE').'</em>',
	)
);
?>
	</dd>
<?php } ?>
<?php if($this->jms_integration){ ?>
	<dt><label><?php echo JText::_('SITE_ID'); ?></label></dt>
	<dd>
		<?php echo str_replace('class="custom-select"','class="custom-select no-chzn" style="width:90px;"', MultisitesHelperUtils::getComboSiteIDs( @$this->price->price_site_id, 'hikashop_' . $this->form_key . '_site_edit', JText::_( 'SELECT_A_SITE'))); ?>
	</dd>
<?php } ?>
</dl>
<div style="float:right">
	<button onclick="return window.productMgr.addPrice('<?php echo $this->form_key; ?>');" class="btn btn-success">
		<i class="fa fa-save"></i> <?php echo JText::_('HIKA_OK'); ;?>
	</button>
</div>
<button onclick="<?php if(!empty($this->price->price_id)) echo 'window.productMgr.restorePriceRow('.$this->price->price_id.');'; ?>return window.productMgr.cancelNewPrice('<?php echo $this->form_key ?>');" class="btn btn-danger">
	<i class="fa fa-times"></i> <?php echo JText::_('HIKA_CANCEL'); ;?>
</button>
<div style="clear:both"></div>

<script type="text/javascript">
if(!window.localPage) window.localPage = {};
function autoPriceDisplay(value) {
	window.localPage.priceDisplay(value);
}
window.localPage.priceDisplay = function (value) {
	var elems = document.getElementsByClassName('price_display');
	var btns = document.getElementsByClassName('label_btn');
	var btn_active = "btn-primary active";

    for (var i = 0; i < elems.length; i++) {
        if (value == 0) {
            if (elems[i].classList.contains('price_without_tax')) {
                elems[i].classList.remove('hidden_price');
            } else if (elems[i].classList.contains('price_with_tax')) {
                elems[i].classList.add('hidden_price');
            }
        } else if (value == 1) {
            if (elems[i].classList.contains('price_with_tax')) {
                elems[i].classList.remove('hidden_price');
            } else if (elems[i].classList.contains('price_without_tax')) {
                elems[i].classList.add('hidden_price');
            }
        } else {
            elems[i].classList.remove('hidden_price');
        }
    }
	for (i = 0; i < btns.length; i++) {
		btns[i].classList.remove('btn-primary', 'active');
	}
	var active_btn = document.getElementById('label_price_displayed_' + value.toString());
	if (active_btn) {
		active_btn.classList.add('btn-primary', 'active');
	}
	window.hikashop.setCookie("cookie_price_display",value.toString(),<?php echo $delay; ?>);
}
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}
window.hikashop.ready(function(){
	var cookieValue = getCookie("cookie_price_display");

	if (cookieValue === null || cookieValue === "") {
		var cookieValue = 2;
	}
	var value = cookieValue;
	setTimeout(function () {
        autoPriceDisplay(value);
    }, 100);
});
</script>
