<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php 
$pluginsClass = hikashop_get('class.plugins');
$google_products = $pluginsClass->getByName('hikashop','google_products');

$addCode = (empty($google_products->params['add_code'])) ? '0' : $google_products->params['add_code'];
$inStockOnly = (empty($google_products->params['in_stock_only'])) ? '0' : $google_products->params['in_stock_only'];
$taxedPrice = (empty($google_products->params['taxed_price'])) ? '0' : $google_products->params['taxed_price'];
$noDiscount = (empty($google_products->params['no_discount'])) ? '0' : $google_products->params['no_discount'];
$priceDisplayed = (empty($google_products->params['price_displayed'])) ? 'cheapest' : $google_products->params['price_displayed'];
$includeVariants = (empty($google_products->params['include_variants'])) ? '0' : $google_products->params['include_variants'];

$google_products_params = array();
$google_products_params['age_group'] = (empty($google_products->params['age_group'])) ? '' : $google_products->params['age_group'];
$google_products_params['gender'] = (empty($google_products->params['gender'])) ? '' : $google_products->params['gender'];
$google_products_params['size'] = (empty($google_products->params['size'])) ? '' : $google_products->params['size'];
$google_products_params['color'] = (empty($google_products->params['color'])) ? '' : $google_products->params['color'];
$google_products_params['mpn'] = (empty($google_products->params['mpn'])) ? '' : $google_products->params['mpn'];

global $Itemid;

$main =& $this->element;
if(!empty($this->element->main)) {
    $main =& $this->element->main;
}

$hasStock = $main->product_quantity != 0;

if (isset($this->element->variants) && $main->product_quantity == -1) {
    $hasStock = false;
    foreach ($this->element->variants as $key => $variant) {
        if ($variant->product_quantity != 0) {
            $hasStock = true;
            break;
        }
    }
}

if(($inStockOnly == '1') && !$hasStock)
    return;

$mpn = '';
if($addCode == '1')
    $mpn = $main->product_code;

$selected_price = $this->priceSelected($this->element, $priceDisplayed, $noDiscount, $taxedPrice);

$config = hikashop_config();
$uploadFolder = ltrim(JPath::clean(html_entity_decode($config->get('uploadfolder'))),DS);
$uploadFolder = rtrim($uploadFolder,DS).DS;
$main_uploadFolder_url = str_replace(DS,'/',$uploadFolder);

$img_tbl = array();
if (isset($main->images)) {

    foreach ($main->images as $key => $image) { 
        $img_tbl[] = JURI::base().$main_uploadFolder_url.$image->file_path;
    }
}

if ($main->product_quantity == 0)
    $stock = "OutOfStock";
else
    $stock = "InStock";

if (!isset($this->element->variants)) {
    $product_id = $this->element->product_id;
    $product_type = 'Product';
}
else {
    $product_id = $this->element->product_parent_id;
    $product_type = 'ProductGroup';
}

$db = JFactory::getDBO();
$query = 'SELECT * FROM '.hikashop_table('vote').' WHERE vote_type = \'product\' AND vote_published > 0 AND vote_ref_id = '.(int)$product_id;
$db->setQuery($query);
$voteComments = $db->loadObjectList();

if (!empty($voteComments)) {
    $config = hikashop_config();
    $hikashop_vote_nb_star = $config->get('vote_star_number');

    $bestRating = 0;
    $allRatingTot = 0;
    foreach ($voteComments as $k => $review) {
        if ($review->vote_rating > $bestRating)
            $bestRating = $review->vote_rating;
        $allRatingTot += $review->vote_rating;
    }
    $averageRating = 0;
    if (($allRatingTot != 0) && ((int)count($voteComments) != 0)) {
        $averageRating = round($allRatingTot / (int)count($voteComments), 2);
    }

    $type = '@type';
    $reviewObject  = array();
    foreach ($voteComments as $k => $review) {

        $author_obj = new stdClass();
        $author_obj->$type = "Person";
        $author_obj->name = $review->vote_pseudo;

        $reviewRating_obj = new stdClass();
        $reviewRating_obj->$type = "Rating";
        $reviewRating_obj->ratingValue = $review->vote_rating;
        $reviewRating_obj->bestRating = $bestRating;

        $review_obj = new stdClass();
        $review_obj->$type = "Review";
        $review_obj->reviewRating = $reviewRating_obj;
        $review_obj->author = $author_obj;
        $review_obj->datePublished = date('m/d/Y H:i:s', $review->vote_date);
        $review_obj->reviewBody = $review->vote_comment;

        $reviewObject[] = $review_obj;
    }

    $aggregateRating_obj = new stdClass();
    $aggregateRating_obj->$type = "AggregateRating";
    $aggregateRating_obj->ratingValue = $averageRating;
    $aggregateRating_obj->reviewCount = (int)count($voteComments);
}

$type = '@type';
if (isset($this->manufacturer->category_name)) {
    $brand_obj = new stdClass();
    $brand_obj->$type = "Brand";
    $brand_obj->name = $this->manufacturer->category_name;
}

$priceSpecification = new stdClass();
$priceSpecification->$type = "PriceSpecification";
$priceSpecification->price = $selected_price;
$priceSpecification->priceCurrency = $this->currency->currency_code;

$offer_obj = new stdClass();
$offer_obj->$type = "Offer";
$offer_obj->url = "https://www.example.com/trinket_offer";
$offer_obj->itemCondition = "https://schema.org/NewCondition";
$offer_obj->availability = "https://schema.org/".$stock;
$offer_obj->priceSpecification = $priceSpecification;

if ($product_type == 'Product' || $includeVariants == '0') {
    $obj = new stdClass();
    $obj->context = "https://schema.org/";
    $obj->$type = $product_type;
    $obj->name = strip_tags($main->product_name);
    $obj->image =  $img_tbl;
    $description = JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */>#i','',$main->product_description));
    $obj->description = strip_tags($description);
    $obj->url = hikashop_contentLink('index.php?option=com_hikashop&ctrl=product&task=show&cid='.$main->product_id.'&name='.$this->element->alias.'&Itemid='.$Itemid, $main);
    $obj->sku = $main->product_code;
    if ($mpn != '') 
        $obj->mpn = $mpn;
    if (isset($brand_obj))
        $obj->brand = $brand_obj;
    $obj->offers = $offer_obj;

    if (isset($aggregateRating_obj)) {
        $obj->review = $reviewObject;
        $obj->aggregateRating = $aggregateRating_obj;
    }

    $params_array = $this->_additionalParameter($main, $google_products_params);

    if(count($google_products_params) > 0) {
        foreach ($params_array as $key => $value) {
            $key = strval($key);
            if ($value != '') {
                if (($key != 'mpn')) {
                    $obj->$key = $value;
                }
                if (!isset($obj->mpn) && ($key == 'mpn')) {
                    $obj->$key = $value;
                }
            }
        }
    }
}
else {
    $characteristic_array = array();
    foreach ($this->characteristics as $k => $characteristics) {
        $characteristic_array[$characteristics->variant_characteristic_id] = array(
            "label" => $characteristics->characteristic_value
        );
    }

    $variant_products_all = array();
    $variesBy = array();
    foreach ($this->element->variants as $k => $variant) {
        if (isset($variant->product_published) && $variant->product_published == "-1")
		    continue;

        if ($variant->product_quantity == 0)
            $stock = "OutOfStock";
        else
            $stock = "InStock";

        $img_tbl = array();
        if (isset($variant->images)) {
            foreach ($variant->images as $k => $image) {
                $img_tbl[] = JURI::base().$main_uploadFolder_url.$image->file_path;
            }
        }

        $selected_price = $this->priceSelected($variant, $priceDisplayed, $noDiscount, $taxedPrice);

        $variant_offer = new stdClass();
        $variant_offer->$type = "Offer";
        $variant_offer->url = hikashop_contentLink('index.php?option=com_hikashop&ctrl=product&task=show&cid='.$variant->product_id.'&name='.$this->element->alias.'&Itemid='.$Itemid, $main);
        $variant_offer->priceCurrency = $this->currency->currency_code;
        $variant_offer->price = $selected_price;
        $variant_offer->availability = "https://schema.org/".$stock;

        $variant_products = new stdClass();
        $variant_products->$type = "Product";
        $variant_products->sku = $variant->product_code;
        $variant_products->image = $img_tbl;
        $variant_products->name =  strip_tags($variant->product_name);
        $description = JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */>#i','',$variant->product_description));
        $variant_products->description = strip_tags($description);
        $variant_products->offers = $variant_offer;

        $params_array = $this->_additionalParameter($variant, $google_products_params);
        if(count($google_products_params) > 0) {
            foreach ($params_array as $key => $value) {
                $key = strval($key);
                if ($value != '') {
                    if (($key != 'mpn'))
                        $variant_products->$key = $value;
                    if($addCode == '1')
                        $variant_products->mpn = $variant_products->product_code;
                    if (!isset($variant_products->mpn) && ($key == 'mpn'))
                        $variant_products->$key = $value;
                }
            }
        }

        foreach ($variant->characteristics as $k => $val) {
            if(!in_array($characteristic_array[$chara_key]["label"], array('size', 'color', 'suggestedAge', 'suggestedGender', 'material', 'pattern'))) {
                continue;
            }
            $variesByKey = 'https://schema.org/'.(string)$characteristic_array[$chara_key]["label"];
            if(array_search($variesByKey, $variesBy) === false)
                $variesBy[] = $variesByKey;
            $chara_key = $val->characteristic_parent_id;
            $label = (string)$characteristic_array[$chara_key]["label"];
            $value = (string)$val->characteristic_value;
            $variant_products->$label = $value;
        }
        $variant_products_all[] = $variant_products;
    }

    $context = '@context';
    $obj = new stdClass();
    $obj->$context = "https://schema.org/";
    $obj->$type = $product_type;
    $obj->name = strip_tags($main->product_name);
    $description = JHTML::_('content.prepare',preg_replace('#<hr *id="system-readmore" */>#i','',$main->product_description));
    $obj->description  = strip_tags($description);
    $obj->url = hikashop_contentLink('index.php?option=com_hikashop&ctrl=product&task=show&cid='.$main->product_id.'&name='.$this->element->alias.'&Itemid='.$Itemid, $main);
    if (isset($aggregateRating_obj)) {
        $obj->aggregateRating = $aggregateRating_obj;
    }
    if(isset($brand_obj))
        $obj->brand = $brand_obj;
    if(count($variesBy))
        $obj->variesBy = $variesBy;
    $obj->hasVariant = $variant_products_all;
}
$json = json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$doc = JFactory::getDocument();
$doc->addScriptDeclaration($json, 'application/ld+json');
