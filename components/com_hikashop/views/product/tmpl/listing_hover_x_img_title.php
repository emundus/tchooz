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
$mainDivName = $this->params->get('main_div_name', '');
$height = (int)$this->image->main_thumbnail_y;
$width = (int)$this->image->main_thumbnail_x;
$hidden_html = '';
$part_html = '';

if ($height == '') {
	$height = '150';
}
$img_nb = 0;
if (isset($this->row->images)) 
	$img_nb = count($this->row->images);

if ($img_nb > 1) {
	$row = @$this->row->images;

	$img_array = array();
	foreach($row as $k => $img) {
		if($k > 0) {
			$hidden_img = $this->image->getThumbnail(
				$img->file_path,
				array('width' => $width, 'height' => $height),
				array('default' => true,'forcesize'=>$this->config->get('image_force_size',true),'scale'=>$this->config->get('image_scale_mode','inside'))
			);

			if($hidden_img->success) {
				$part_html = ''.
				'<img class="hikashop_product_listing_image hikashop_img_hidden_'.$k.'"'.
				'title="'.$this->escape($img->file_description).'" '.
				'alt="'.$this->escape($img->file_name).'" src="'.$hidden_img->url.'"/>';

				if($this->config->get('add_webp_images', 1) && function_exists('imagewebp') && !empty($hidden_img->webpurl)) {
					$part_html = ''.
					'<picture>'.
						'<source srcset="'.$hidden_img->webpurl.'" type="image/webp">'.
						'<source srcset="'.$hidden_img->url.'" type="image/'.$hidden_img->ext.'">'.
					$part_html.
					'</picture>';
				}
				$hidden_html .= '<div class="hikashop_div_hidden_'.$k.' hika_hover_x_img" style="top: 0;">'.
					$part_html.
				'</div>';
			}
		}
	}
}
$link = hikashop_contentLink('product&task=show&cid=' . (int)$this->row->product_id . '&name=' . $this->row->alias . $this->itemid . $this->category_pathway, $this->row);
$this->haveLink = (int)$this->params->get('link_to_product_page', 1);

$hk_main_classes = array('hikashop_listing_img_title');
if(!empty($this->row->categories)) {
	foreach($this->row->categories as $category) {
		$hk_main_classes[] = 'hikashop_product_of_category_'.$category->category_id;
	}
}

if(!empty($this->row->extraData->top)) { echo implode("\r\n",$this->row->extraData->top); }

?>
<div class="<?php echo implode(' ', $hk_main_classes); ?>" id="div_<?php echo $mainDivName.'_'.$this->row->product_id; ?>">
<?php
if($this->config->get('thumbnail', 1)) {
	$extra_class = '';
	$container_extra_class = '';
	if ($img_nb > 1) {
		$extra_class = 'hikashop_hover_img';
		$container_extra_class = 'hikashop_hover_x_container';
	}
?>
	<!-- PRODUCT IMG -->
	<div class="hikashop_product_image <?php echo $container_extra_class; ?>" data-ref="0">
<?php 
	if ($img_nb > 1) {
?>
		<a class="hika_hover_x_btn hika_hover_x_btn_previous" style="width:20px;margin-left:-5px;"  onclick="hover_x_slide('-', this); return false;" 
			title="<?php echo JText::_('HIKA_PREVIOUS'); ?>">
			<i class="fas fa-chevron-left"></i>
		</a>
<?php 
	}
?>
		<div id="hikashop_product_image_subdiv_<?php echo $mainDivName.'_'.$this->row->product_id; ?>" class="hikashop_product_image_subdiv <?php echo $extra_class; ?>">
<?php
	$img = $this->image->getThumbnail(
		@$this->row->file_path,
		array('width' => $width, 'height' => $height),
		array('default' => true,'forcesize'=>$this->config->get('image_force_size',true),'scale'=>$this->config->get('image_scale_mode','inside'))
	);
	if($img->success) {
		$html = '<img class="hikashop_product_listing_image hikashop_img_hidden_0"' .
		'title="'.$this->escape((string)@$this->row->file_description).'" '.
		'alt="'.$this->escape((string)@$this->row->file_name).'" src="'.$img->url.'"/>';
		if($this->config->get('add_webp_images', 1) && function_exists('imagewebp') && !empty($img->webpurl)) {
			$html = ''.
			'<picture>
				<source srcset="'.$img->webpurl.'" type="image/webp">
				<source srcset="'.$img->url.'" type="image/'.$img->ext.'">
				'.$html;
			'</picture>'.
			'';
		}
		if ($img_nb > 1) {
			if ($hidden_html != '') {
				$full_html = ''. $hidden_html;
			}
			$k = $k + 1;
			$full_html .= '<div class="hikashop_div_hidden_'.$k.' hika_hover_x_img hikashop_img_curr_final" style="top: 0; position:relative !important;">'.
				$html.
			'</div>';
		}
		else {
			$full_html = '<div class="hikashop_div_hidden_1 hika_hover_x_img hikashop_img_curr_final" style="position: relative !important;">'.
				$html.
			'</div>';
		}
		$this->css_button = 'hikashop_hover_parent';
		$this->link_content = $full_html;
		$this->setLayout('show_popup');
		echo $this->loadTemplate();
?>		<meta itemprop="image" content="<?php echo $img->url; ?>"/>
<?php
	}
	if($this->params->get('display_badges', 1)) {
		$this->classbadge->placeBadges($this->image, $this->row->badges, array('vertical' => -10, 'horizontal' => 0, 'thumbnail' => $img));
	}
?>
		</div>
<?php 
	if ($img_nb > 1) {
?>
		<a class="hika_hover_x_btn hika_hover_x_btn_next" style="width:20px;margin-right:-5px;" onclick="hover_x_slide('+', this); return false;" 
			title="<?php echo JText::_('HIKA_NEXT'); ?>">
			<i class="fas fa-chevron-right"></i>
		</a>
<?php 
	}
?>
	</div>
	<!-- EO PRODUCT IMG -->
<?php } ?>

	<!-- PRODUCT PRICE -->
<?php
	if($this->params->get('show_price','-1')=='-1'){
		$config =& hikashop_config();
		$this->params->set('show_price',$config->get('show_price'));
	}
	if($this->params->get('show_price')){
		$this->setLayout('listing_price');
		echo $this->loadTemplate();
	}
?>
	<!-- EO PRODUCT PRICE -->

	<!-- PRODUCT NAME -->
	<span class="hikashop_product_name">
<?php
		$this->link_content = $this->row->product_name;
		$this->setLayout('show_popup');
		echo $this->loadTemplate();
?>
	</span>
	<meta itemprop="name" content="<?php echo $this->escape(strip_tags($this->row->product_name)); ?>">
	<!-- EO PRODUCT NAME -->

	<!-- PRODUCT CODE -->
	<span class='hikashop_product_code_list'>
<?php 
	if ($this->config->get('show_code')) {
	$this->link_content = $this->row->product_code;
	$this->setLayout('show_popup');
	echo $this->loadTemplate();
	} 
?>	</span>
	<!-- EO PRODUCT CODE -->

	<!-- PRODUCT CUSTOM FIELDS -->
<?php
if(!empty($this->productFields)) {
	foreach($this->productFields as $fieldName => $oneExtraField) {
		if(empty($this->row->$fieldName) && (!isset($this->row->$fieldName) || $this->row->$fieldName !== '0'))
			continue;

		if(!empty($oneExtraField->field_products)) {
			$field_products = is_string($oneExtraField->field_products) ? explode(',', trim($oneExtraField->field_products, ',')) : $oneExtraField->field_products;
			if(!in_array($this->row->product_id, $field_products))
				continue;
		}
		$oneExtraField->currentElement = $this->row;
?>
	<dl class="hikashop_product_custom_<?php echo $oneExtraField->field_namekey;?>_line">
		<dt class="hikashop_product_custom_name">
			<?php echo $this->fieldsClass->getFieldName($oneExtraField);?>
		</dt>
		<dd class="hikashop_product_custom_value">
			<?php echo $this->fieldsClass->show($oneExtraField,$this->row->$fieldName); ?>
		</dd>
	</dl>
<?php
	}
}
?>
	<!-- EO PRODUCT CUSTOM FIELDS -->

<?php if(!empty($this->row->extraData->afterProductName)) { echo implode("\r\n",$this->row->extraData->afterProductName); } ?>

	<!-- PRODUCT VOTE -->
<?php

if($this->params->get('show_vote')) {
	$this->setLayout('listing_vote');
	echo $this->loadTemplate();
}
?>
	<!-- EO PRODUCT VOTE -->

	<!-- CHARACTERISTIC AVAILABLE VALUES -->
<?php
if(!empty($this->row->characteristics)) {
	foreach($this->row->characteristics as $characteristic) {
		if(!empty($characteristic->availableValues)) {
?>
	<div class="hikashop_product_characteristic_on_listing hikashop_product_characteristic_on_listing_<?php echo $characteristic->characteristic_id; ?>">
		<div class="hikashop_product_characteristic_name_on_listing"><?php echo $characteristic->characteristic_value; ?></div>
		<div class="hikashop_product_characteristic_values_on_listing">
<?php
			foreach($characteristic->availableValues as $value) {
?>
			<span class="hikashop_product_characteristic_value_on_listing hikashop_product_characteristic_value_on_listing_<?php echo $value->characteristic_id; ?>">
				<?php echo $value->characteristic_value; ?>
			</span>
<?php
			}
?>
		</div>
	</div>
<?php		
		}
	}
}
?>
	<!-- EO CHARACTERISTIC AVAILABLE VALUES -->

	<!-- ADD TO CART BUTTON AREA -->
<?php
if($this->params->get('add_to_cart') || $this->params->get('add_to_wishlist')) {
	$this->setLayout('add_to_cart_listing');
	echo $this->loadTemplate();
}
?>
	<!-- EO ADD TO CART BUTTON AREA -->

	<!-- COMPARISON AREA -->
<?php
if(hikaInput::get()->getVar('hikashop_front_end_main', 0) && hikaInput::get()->getVar('task') == 'listing' && $this->params->get('show_compare')) {
	$css_button = $this->config->get('css_button', 'hikabtn');
	$css_button_compare = $this->config->get('css_button_compare', 'hikabtn-compare');
?>
	<br/>
<?php
	if((int)$this->params->get('show_compare') == 1) {
		$onclick = ' onclick="if(window.hikashop.addToCompare) { return window.hikashop.addToCompare(this); }" '.
			'data-addToCompare="'.$this->row->product_id.'" '. 
			'data-product-name="'.$this->escape($this->row->product_name).'" '.
			'data-addTo-class="hika-compare"';
		$attributes = 'class="'.$css_button . ' ' . $css_button_compare.'" '.$onclick;
		$fallback_url = $link;
		$content = JText::_('ADD_TO_COMPARE_LIST');

		echo $this->loadHkLayout('button', array( 'attributes' => $attributes, 'content' => $content, 'fallback_url' => $fallback_url));

	} else {
?>
	<label><input type="checkbox" class="hikashop_compare_checkbox" onchange="if(window.hikashop.addToCompare) { return window.hikashop.addToCompare(this); }" data-addToCompare="<?php echo $this->row->product_id; ?>" data-product-name="<?php echo $this->escape($this->row->product_name); ?>" data-addTo-class="hika-compare"><?php echo JText::_('ADD_TO_COMPARE_LIST'); ?></label>
<?php
	}
}
?>
	<!-- EO COMPARISON AREA -->

	<!-- CONTACT US AREA -->
<?php
	$contact = (int)$this->config->get('product_contact', 0);
	if(hikashop_level(1) && $this->params->get('product_contact_button', 0) && ($contact == 2 || ($contact == 1 && !empty($this->row->product_contact)))) {
		$css_button = $this->config->get('css_button', 'hikabtn');
		$attributes = 'class="'.$css_button.' product_contact_button"';
		$fallback_url = hikashop_completeLink('product&task=contact&cid=' . (int)$this->row->product_id . $this->itemid);
		$content = JText::_('CONTACT_US_FOR_INFO');

		echo $this->loadHkLayout('button', array( 'attributes' => $attributes, 'content' => $content, 'fallback_url' => $fallback_url));
	}
?>
	<!-- EO CONTACT US AREA -->

	<!-- PRODUCT DETAILS BUTTON AREA -->
<?php
	$details_button = (int)$this->params->get('details_button', 0);
	if($details_button) {
		$this->link_content = JText::_('PRODUCT_DETAILS');
		$this->css_button = $this->config->get('css_button', 'hikabtn').' product_details_button';
		$this->type = 'detail';
		$this->setLayout('show_popup');
		echo $this->loadTemplate();
	}
?>
	<!-- EO PRODUCT DETAILS BUTTON AREA -->
	<meta itemprop="url" content="<?php echo $link; ?>">
</div>
<?php

if(!empty($this->row->extraData->bottom)) { echo implode("\r\n",$this->row->extraData->bottom); }

if(isset($this->rows[0]) && $this->rows[0]->product_id == $this->row->product_id) {
	$height_list_cont = $height + 40;

	$css = ''.
	'#'.$mainDivName.' .hikashop_product_image_subdiv.hikashop_hover_img div { position: absolute !important; width: 100%;}';

	if($height > 0){
		$css .= ''.
'#'.$mainDivName.' .hikashop_product_image_subdiv.hikashop_hover_img a { width: 100%; display:inline-block; }'.
'#'.$mainDivName.' .display_list .hikashop_container .hikashop_subcontainer {  height: '. $height_list_cont .'px; }';
	}

	$doc = JFactory::getDocument();
	$doc->addStyleDeclaration($css);
?>
<script type="text/javascript">
	window.hikashop.ready(function(){
		image_auto_height('<?php echo $mainDivName; ?>');
	});
	window.addEventListener('resize', function(event) {
		image_auto_height('<?php echo $mainDivName; ?>');
	}, true);

	function getElementHeight(mainDivs) {
		var first_img = mainDivs[0].querySelector('.hikashop_div_hidden_1');

		if(!first_img.complete)
			return 0;

		var ref_height = first_img.offsetHeight;
		return ref_height;
	};
	function hover_x_slide_auto(target) {
		if(!window.hikashop.hoverInProgress || target.id != window.hikashop.hoverInProgress)
			return;
		btn = target.parentNode.querySelector('.hika_hover_x_btn_next');
		hover_x_slide('+', btn);
		setTimeout(() => {
			hover_x_slide_auto(target);
		}, '1000');

	}
	function hover_x_slide(action, btnElement) {
		var container = btnElement.closest('.hikashop_product_image');
		if (!container) return;

		var allImg = container.querySelectorAll('.hika_hover_x_img');
		var ref = parseInt(container.dataset.ref, 10);

		allImg.forEach(img => img.style.opacity = "0");

		if (action === "+") {
			ref = (ref + 1) % allImg.length;
		} else {
			ref = (ref - 1 + allImg.length) % allImg.length;
		}
		allImg[ref].style.opacity = "1";

		container.dataset.ref = ref;
	}

	function image_auto_height (DivName) {
		var mainDivs = document.querySelectorAll('#' + DivName + ' .hikashop_hover_parent');
		var ref_height = getElementHeight(mainDivs);
		if (ref_height == 0) {
			setTimeout(function(){image_auto_height(DivName);}, 500);
			return;
		}
		var nb_images = mainDivs.length;

		for (i = 0; i < nb_images; i++) {

			for (j = 0; j < 8; j++) {
				var arrayDiv = mainDivs[i].querySelector('.hikashop_div_hidden_' + j);

				if (j > 0) {
					if (arrayDiv != null) {
						arrayDiv.style.top = "0";
					}
				}
			}
		}
	};
</script>
<?php
}
if ($img_nb > 1) {
?>
<style>
#<?php echo $mainDivName; ?> .hikashop_product_image_subdiv {
    width: 85%;
}
.hika_hover_x_btn, 
.hikashop_product_image_subdiv {
    vertical-align: middle;
    display: inline-block;
}
#<?php echo $mainDivName; ?> .hikashop_product_image.hikashop_hover_x_container .hika_hover_x_btn {
    cursor: pointer;
}
</style>

<script>
window.hikashop.ready(function(){
	var item = document.querySelector('#div_<?php echo $mainDivName.'_'.$this->row->product_id; ?> .hikashop_product_image_subdiv');
	item.addEventListener('mouseenter', function(event) {
		if(window.hikashop.hoverInProgress)
			return;
		window.hikashop.hoverInProgress = this.id;
		hover_x_slide_auto(this);
	}, true);

	item.addEventListener('mouseleave', function(event) {
		window.hikashop.hoverInProgress = false;
	}, true);

});
</script>
<?php
}
