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
if(!hikamarket::acl('product/edit/variants') || empty($this->product->product_id))
	return;
?>
<div>
	<div style="float:right">
		<button class="hikabtn hikabtn-success" onclick="return window.productMgr.addVariants(this, <?php echo (int)$this->product->product_id; ?>);"><i class="fas fa-plus"></i> <?php echo JText::_('HIKAM_ADD_VARIANTS'); ?></button>
	</div>
	<div id="hikamarket_variant_bundle_toolbar" style="display:none;">
		<button class="hikabtn hikabtn-danger" onclick="return window.productMgr.deleteVariants(this, <?php echo (int)$this->product->product_id; ?>);"><i class="far fa-times-circle"></i> <?php echo JText::_('HIKA_DELETE'); ?></button>
		<button class="hikabtn hikabtn-info" onclick="return window.productMgr.duplicateVariants(this, <?php echo (int)$this->product->product_id; ?>);"><i class="fas fa-copy"></i> <?php echo JText::_('HIKAM_DUPLICATE'); ?></button>
	</div>
	<div style="clear:both"></div>
</div>
<div id="hikamarket_product_variant_creation_container"></div>
<table class="table table-striped table-hover" style="width:100%">
	<thead>
		<tr>
			<th style="width:25px; text-align:center">
				<input onchange="window.hikamarket.checkAll(this, 'hikamarket_product_variant_checkbox_');" type="checkbox" id="hikamarket_product_variant_checkbox_general" value=""/>
			</th>
			<th style="width:25px"></th>
<?php
	$default_variants = array();
	$characteristics = array();
	foreach($this->product->characteristics as $characteristic) {
		if((int)$characteristic->characteristic_parent_id > 0) {
			$default_variants[(int)$characteristic->characteristic_id] = (int)$characteristic->characteristic_id;
			continue;
		}
		$characteristics[(int)$characteristic->characteristic_id] = (int)$characteristic->characteristic_id;

?>			<th><?php echo $characteristic->characteristic_value; ?></th>
<?php
	}
?>
			<th><?php echo JText::_('PRICE'); ?></th>
			<th><?php echo JText::_('PRODUCT_QUANTITY'); ?></th>
			<th style="width:1%"><?php echo JText::_('HIKA_PUBLISHED'); ?></th>
			<th style="width:1%"><?php echo JText::_('HIKA_DEFAULT'); ?></th>
		</tr>
	</thead>
	<tbody>
<?php
	$tab_notice_msg = '';
	$tab_variant_counter_color = (count($this->product->variants) > 1) ? 'green' : (count($this->product->variants) == 0 ? 'red' : 'orange');
	$default_found = false;
	$k = 0;
	foreach($this->product->variants as $variant) {
	?>	<tr class="row<?php echo $k; ?>" id="hikamarket_product_variant_line_<?php echo $variant->product_id; ?>"> <!--style="cursor:pointer" onclick="return window.productMgr.editVariant(<?php echo $variant->product_id; ?>);">-->
			<td style="text-align:center">
				<input onchange="window.productMgr.checkVariant(this, <?php echo $variant->product_id; ?>);" type="checkbox" id="hikamarket_product_variant_checkbox_<?php echo $variant->product_id; ?>" value="<?php echo $variant->product_id; ?>"/>
			</td>
			<td style="text-align:center"><a href="#edit:<?php echo $variant->product_id; ?>" onclick="return window.productMgr.editVariant(<?php echo $variant->product_id; ?>);"><i class="fas fa-pencil-alt"></i></a></td>
<?php
		$cpt = 0;
		foreach($this->product->characteristics as $characteristic) {
			if((int)$characteristic->characteristic_parent_id > 0)
				continue;

			$o = @$variant->characteristics[$characteristic->characteristic_id];
			if(!empty($o) && isset($default_variants[ (int)$o->id ]))
				$cpt++;
?>			<td style="cursor:pointer" onclick="return window.productMgr.editVariant(<?php echo $variant->product_id; ?>);"><?php echo @$o->value; ?></td>
<?php
		}
		$variant_default = ($cpt == count($default_variants)) ? 'publish' : 'unpublish';
		if($variant_default == 'publish')
			$default_found = true;

?>			<td style="cursor:pointer" onclick="return window.productMgr.editVariant(<?php echo $variant->product_id; ?>);"><?php echo $this->currencyClass->displayPrices(@$variant->prices);?></td>
			<td style="cursor:pointer" onclick="return window.productMgr.editVariant(<?php echo $variant->product_id; ?>);"><?php echo (($variant->product_quantity == -1) ? JText::_('UNLIMITED') : $variant->product_quantity); ?></td>
			<td style="text-align:center"><?php echo $this->toggleClass->display('product_published', $variant->product_published); ?></td>
			<td style="text-align:center">
				<div class="toggle_loading"><a class="<?php echo $variant_default; ?>" href="#" onclick="return window.productMgr.setDefaultVariant(event, <?php echo $variant->product_id; ?>);"></a></div>
			</td>
		</tr>
<?php
		$k = 1 - $k;
	}

	if(count($this->product->variants) > 0 && !$default_found) {
		$tab_variant_counter_color = 'red';
		$tab_notice_msg = ' - ' . JText::_('HIKAM_NOT_DEFAULT_VARIANT');
	}
?>
	</tbody>
</table>
<?php if(hikaInput::get()->getCmd('tmpl', '') != 'component') { ?>
<script type="text/javascript">
window.hikashop.ready(function(){
	var el = document.getElementById('hikamarket_product_variant_label');
	if(el)
		el.innerHTML = '<span class="hk-label hk-label-<?php echo $tab_variant_counter_color; ?>"><?php echo count($this->product->variants) . $tab_notice_msg; ?></span>';
});
window.productMgr.variantEdition = {
	current: null,
	loading: false,
	checked: null
};
window.productMgr.refreshVariantList = function() {
	var w = window, o = w.Oby, t = this,
		url_list = '<?php echo hikamarket::completeLink('product&task=variants&product_id='.$this->product->product_id.'&'.hikamarket::getFormToken().'=1',true,false,true); ?>';
	o.xRequest(url_list, {update:'hikamarket_product_variant_list'}, function(x,p) {
		if(!t.variantEdition.current)
			return;
		setTimeout(function(){
			var l = document.getElementById('hikamarket_product_variant_line_' + t.variantEdition.current);
			if(l) window.Oby.addClass(l, 'selectedVariant');
		},10);
	});
};
window.productMgr.editVariant = function(id) {
	var w = window, o = w.Oby, d = document, t = this, l = null,
		el = d.getElementById('hikamarket_product_variant_edition'),
		url = '<?php echo hikamarket::completeLink('product&task=variant&product_id='.$this->product->product_id.'&cid={CID}',true,false,true); ?>';

	id = parseInt(id);
	if(isNaN(id) || id === 0)
		return false;
	if(w.productMgr.variantEdition.loading == true)
		return false;

	if(t.variantEdition.current) {
		l = d.getElementById('hikamarket_product_variant_line_' + t.variantEdition.current);
		if(l) o.removeClass(l, 'selectedVariant');
	}

	if(t.variantEdition.current && window.productMgr.closeVariantEditor) {
		try { window.productMgr.closeVariantEditor(); } catch(err){}
	}

	l = d.getElementById('hikamarket_product_variant_line_' + id);
	if(l) o.addClass(l, 'selectedVariant');

	w.productMgr.variantEdition.current = id;
	var url = url.replace('{CID}',id);
	o.addClass(el, 'ajax_loading');
	o.xRequest(url,{update:el},function(x,p){
		o.removeClass(el, 'ajax_loading');
		w.productMgr.variantEdition.loading = false;
		setTimeout(function(){window.hikamarket.dlTitle(el);},50);
	});
	return false;
};
window.productMgr.closeVariant = function() {
	var t = this, d = document,
		el = d.getElementById('hikamarket_product_variant_edition');
	if(el) {
		setTimeout(function() {
			el.innerHTML = '';
		}, 10);
	}
	if(t.variantEdition.current) {
		l = d.getElementById('hikamarket_product_variant_line_' + t.variantEdition.current);
		if(l) window.Oby.removeClass(l, 'selectedVariant');
	}
	t.variantEdition.current = null;
	t.variantEdition.loading = false;
	return false;
};
window.productMgr.cancelVariantEdition = function() {
	var t = this;
	if(t.variantEdition.current === null)
		return true;
	if(t.variantEdition.loading)
		return false;
	if(confirm('<?php echo str_replace('\'', '\\\'', JText::_('CONFIRM_CLOSiNG_VARIANT_IN_EDITION')); ?>')) {
		t.closeVariant();
		return true;
	}
	return false;
};
window.productMgr.saveVariant = function(id) {
	var w = window, o = w.Oby, d = document,
		el = d.getElementById('hikamarket_product_variant_edition'),
		form = d.getElementById('hikamarket_products_form');
		url = '<?php echo hikamarket::completeLink('product&task=save&subtask=variant&product_id='.$this->product->product_id.'&variant_id={CID}&'.hikamarket::getFormToken().'=1',true,false,true); ?>';
	if(!el)
		return false;
	url = url.replace('{CID}', id);

	o.addClass(el, 'ajax_loading');
	w.productMgr.variantEdition.loading = true;

	if(window.productMgr.saveVariantEditor) {
		try { window.productMgr.saveVariantEditor(); } catch(err){}
	}
	o.fireAjax("syncWysiwygEditors", null);

	var formData = o.getFormData(el);
	o.xRequest(url, {update:el, mode: 'POST', data:formData}, function(x,p) {
		o.removeClass(el, 'ajax_loading');
		w.productMgr.variantEdition.loading = false;
		w.productMgr.refreshVariantList();
	});
	return false;
};
window.productMgr.setDefaultVariant = function(ev, id) {
	var event = ev || window.event;
	event.stopPropagation();
	event.preventDefault();

	var w = window, o = w.Oby, d = document,
		url = '<?php echo hikamarket::completeLink('product&task=variants&subtask=setdefault&product_id='.$this->product->product_id.'&variant_id={CID}&'.hikamarket::getFormToken().'=1',true,false,true); ?>';
	url = url.replace('{CID}', id);
	o.xRequest(url, {update:'hikamarket_product_variant_list'});
	return false;
};
window.productMgr.checkVariant = function(el, id) {
	var ve = window.productMgr.variantEdition, d = document,
		tool = d.getElementById('hikamarket_variant_bundle_toolbar');
	if(!tool)
		return;
	if(el.checked) {
		if(ve.checked === null)
			ve.checked = [];
		if(ve.checked.indexOf(id) < 0)
			ve.checked.push(id);
	} else {
		if(ve.checked === null)
			ve.checked = [];
		var p = ve.checked.indexOf(id);
		if(p >= 0)
			ve.checked.splice(p, 1);
		if(ve.checked.length == 0) {
			ve.checked = null;
			var e = d.getElementById('hikamarket_product_variant_checkbox_general');
			if(e)
				e.checked = false;
		}
	}
	tool.style.display = (ve.checked && ve.checked.length > 0) ? '' : 'none';
};
window.productMgr.addVariants = function(el, id) {
	if(this.cancelVariantEdition && !this.cancelVariantEdition())
		return false;
	window.Oby.xRequest('<?php echo hikamarket::completeLink('product&task=variants&subtask=add&product_id='.$this->product->product_id.'&'.hikamarket::getFormToken().'=1',true,false,true); ?>', {update:'hikamarket_product_variant_creation_container'});
	return false;
};
window.productMgr.populateVariants = function(mode) {
	var d = document, w = window, o = w.Oby, data = null,
		ve = window.productMgr.variantEdition,
		el = d.getElementById('hikamarket_product_variant_creation_container');
	if(!el)
		return false;

	data = o.getFormData(el);
	if(mode && mode == 'duplicate') {
		if(ve.length == 0) {
			alert('<?php echo str_replace("'", "\\'", JText::_('PLEASE_SELECT_SOMETHING')); ?>');
			return false;
		}
		for(var i = ve.checked.length - 1; i >= 0; i--) {
			data += '&cid[]=' + ve.checked[i];
		}
	} else if(mode && mode == 'add') {
		var characteristics = [<?php echo implode(',', $characteristics); ?>],
			rawData = data;
		if(rawData.indexOf('data[variant_add]') < 0)
			rawData = decodeURI(rawData);
		if(rawData.indexOf('data[variant_add]') >= 0) {
			for(var i = characteristics.length - 1; i >= 0; i--) {
				if(rawData.indexOf('data[variant_add][' + characteristics[i] + '][]') >= 0)
					continue;
				alert('<?php echo str_replace("'", "\\'", JText::_('PLEASE_SELECT_A_VALUE_FOR_EACH_CHARACTERISTIC')); ?>');
				return false;
			}
		}
	}

	o.xRequest('<?php echo hikamarket::completeLink('product&task=variants&subtask=populate&product_id='.$this->product->product_id.'&'.hikamarket::getFormToken().'=1',true,false,true); ?>',
		{mode: 'POST', data: data},
		function(x,p) {
			o.updateElem(el, x.responseText);
			window.productMgr.refreshVariantList();
		}
	);
	return false;
};
window.productMgr.cancelPopulateVariants = function() {
	var d = document, el = d.getElementById('hikamarket_product_variant_creation_container');
	if(el)
		setTimeout(function() { el.innerHTML = ''; }, 10);
	return false;
};
window.productMgr.duplicateVariants = function(el, id) {
	var ve = window.productMgr.variantEdition, d = document;
	if(ve.checked.length > 0) {
		window.Oby.xRequest('<?php echo hikamarket::completeLink('product&task=variants&subtask=duplicate&product_id='.$this->product->product_id.'&'.hikamarket::getFormToken().'=1',true,false,true); ?>', {update:'hikamarket_product_variant_creation_container'});
	} else {
		var el = d.getElementById('hikamarket_product_variant_creation_container');
		if(el) el.innerHTML = '';
	}
	return false;
};
window.productMgr.deleteVariants = function(el, id) {
	var w = window, d = document, o = w.Oby, ve = window.productMgr.variantEdition, data = '';
	if(ve.checked.length == 0)
		return false;
	var msg = '<?php echo str_replace('\'', '\\\'', JText::_('PLEASE_CONFIRM_DELETION_X_VARIANTS')); ?>';
	if(!confirm(msg.replace('{NUM}', ve.checked.length)))
		return false;
	for(var i = ve.checked.length - 1; i >= 0; i--) {
		if(data.length > 0) data += '&';
		data += 'cid[]=' + ve.checked[i];
	}
	o.xRequest('<?php echo hikamarket::completeLink('product&task=variants&subtask=delete&product_id='.$this->product->product_id.'&'.hikamarket::getFormToken().'=1',true,false,true); ?>',
		{mode: 'POST', data: data},
		function(x,p) {
			window.productMgr.refreshVariantList();
		}
	);
	return false;
};
</script>
<?php } else { ?>
<script type="text/javascript">
if(window.productMgr.variantEdition)
	window.productMgr.variantEdition.checked = null;
var el = document.getElementById('hikamarket_product_variant_label');
if(el)
	el.innerHTML = '<span class="hk-label hk-label-<?php echo $tab_variant_counter_color; ?>"><?php echo count($this->product->variants) . $tab_notice_msg; ?></span>';
</script>
<?php }
