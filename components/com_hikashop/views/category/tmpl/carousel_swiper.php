<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.2
 * @author	hikashop.com
 * @copyright	(C) 2010-2023 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
hikashop_loadJslib('swiper');
$options = [];
$rows = [];
$doc = JFactory::getDocument();
$lang = JFactory::getLanguage();
$rtl = (bool)$lang->isRTL();
$mainDivClasses = "hikashop_carousel";
if ($this->params->get("only_if_products", "-1") == "-1") {
    $config = hikashop_config();
    $defaultParams = $config->get("default_params");
    $this->params->set("only_if_products", @$defaultParams["only_if_products"]);
}
$only_if_products = $this->params->get("only_if_products", 0);
foreach ($this->rows as $row) {
    if ($only_if_products && $row->number_of_products < 1) {
        continue;
    }
    $rows[] = $row;
}
$this->rows = $rows;
$nb_products = count($this->rows);
$spaceBetween = (int) $this->params->get("margin");
$mainDivName = $this->params->get("main_div_name");
$thumbs = '';


$quantityDisplay = $this->params->get("show_quantity_field");
$layoutType = $this->params->get("div_item_layout_type");
$pagination_position = $this->params->get("pagination_position");
$main_image_width = $this->image->main_thumbnail_x;
$main_image_height = $this->image->main_thumbnail_y;
$thumbnailHeight=$this->params->get('pagination_image_height');
$thumbnailWidth=$this->params->get('pagination_image_width');
$navigation = (bool) $this->params->get("display_button");
$pagination_type = $this->params->get("pagination_type");
$carouselEffect = $this->params->get("carousel_effect");
$backgroundColor = $this->params->get("background_color");
$paginationClass = 'hika_carousel_'.$pagination_type;
$pagination = $pagination_type != "no_pagination";


$options["effect"] = '"' . $carouselEffect . '"';
$options["direction"] = '"' . $this->params->get("slide_direction") . '"';
$options["autoHeight"] = false;
$options["spaceBetween"] = $spaceBetween;
$options["crossFade"] = false;
$options["loop"] = true;

if ($nb_products < $this->params->get("columns")) {
    $options["loop"] = false;
    $columns = $this->params->get("columns");
    $products = $this->params->get("item_by_slide");
} else {
    $columns = min($nb_products, $this->params->get("columns"));
    $products = min($nb_products, $this->params->get("item_by_slide"));
}
if (empty($products)) {
    $products = $columns;
}

if ($main_image_width == 0)
    $main_image_width = 100;
if ($main_image_height == 0)
    $main_image_height = (int)$main_image_width;

$marge = 100;
if ($quantityDisplay)
    $marge = 140;

if ($pagination_position == 'bottom')
    $marge = $marge + 75;

if ($thumbnailWidth == 0)
    $thumbnailWidth = (int)$main_image_width/4;
if ($thumbnailHeight == 0)
    $thumbnailHeight = (int)$thumbnailWidth;

if($pagination_type == "numbers" || $pagination_type == "dots") {
    $swiperSlideWidth = 25;
    $swiperSlideHeight = 25;
}
if($pagination_type == "thumbnails") {
    $swiperSlideWidth = $thumbnailWidth;
    $swiperSlideHeight = $thumbnailHeight;
}

if ($carouselEffect == "fade") {
    $products = 1;
    $options["crossFade"] = true;
    $options["mousewheel"] = true;
    $options["spaceBetween"] = 0;
}
$direction = 'swiper-horizontal';
$thumbsDirection = 'swiper-horizontal';
if ($options["direction"] == '"vertical"')
    $direction = 'swiper-vertical';
if (in_array($pagination_position, array('left', 'right')))
    $thumbsDirection = 'swiper-vertical';
$extraCss  = 'horizontalThumbnails';
if (($pagination_position == 'left' || $pagination_position == 'right') && $pagination_type == "numbers")
    $extraCss = 'verticalNumber';
if (($pagination_position == 'left' || $pagination_position == 'right') && $pagination_type == "thumbnails")
    $extraCss = 'verticalThumbnails';
?>
<style>

.swiper-slide-thumb-active {
	border: 2px solid #d2d2d2;
}
#<?php echo $mainDivName; ?> .hikashop_carousel {
	position: relative;
	margin: 0;
}
#<?php echo $mainDivName; ?> .hikashop_subcontainer {
	padding: 0;
}
#hikashop_carousel_thumbs_<?php echo $mainDivName; ?> {
	background-color: #fff;
}
<?php
if($pagination_type != "no_pagination") {
?>
#hikashop_carousel_thumbs_<?php echo $mainDivName; ?> .swiper-wrapper .swiper-slide {
    width: <?php echo $swiperSlideWidth; ?>px !important;
    height: <?php echo $swiperSlideHeight; ?>px !important;
}
<?php
}

if ($options["direction"] == '"vertical"') {
?>
    #<?php echo $mainDivName; ?> .swiper-button-prev,
    #<?php echo $mainDivName; ?> .swiper-button-next {
        transform: rotate(90deg);
    }
<?php
    if ($pagination_position == 'left') {
?>  #<?php echo $mainDivName; ?> .swiper-button-prev {
        margin-left: 5%;
        left: var(--swiper-navigation-sides-offset,0px);
    }
<?php
    }
    else {
?>  #<?php echo $mainDivName; ?> .swiper-button-next {
        margin-right: 5%;
        right: var(--swiper-navigation-sides-offset,0px);
    }
<?php        
    }
}
else {

?>  #hikashop_module_<?php echo $mainDivName; ?> {
        height: <?php echo (int)$main_image_height + $marge; ?>px;
    }
<?php
}

if ($pagination_position == 'left' || $pagination_position == 'right') {
?>
    #hikashop_carousel_<?php echo $mainDivName; ?>.swiper-vertical {
        height: <?php echo (int)$products*((int)$main_image_height + $marge); ?>px;
    }
    #hikashop_carousel_<?php echo $mainDivName; ?>.swiper-vertical > div {
        height: <?php echo (int)$main_image_height + $marge; ?>px;
    }
    #hikashop_carousel_pagination_<?php echo $mainDivName; ?>,
    #hikashop_carousel_thumbs_<?php echo $mainDivName; ?> {
        float: <?php echo $pagination_position; ?>;
    }
    #hikashop_carousel_pagination_<?php echo $mainDivName; ?> {
        width: 15px;
    }
<?php
    if ($options["direction"] == '"horizontal"') {
?>  #hikashop_carousel_pagination_<?php echo $mainDivName; ?>,
    #<?php echo $mainDivName; ?> .swiper-thumbs {
        height: <?php echo (int)$main_image_height + $marge; ?>px;
    }
    #<?php echo $mainDivName; ?> {
        overflow: hidden;
    }
<?php    
    }
    else {
?>  #hikashop_carousel_pagination_<?php echo $mainDivName; ?>,
    #<?php echo $mainDivName; ?> .swiper-thumbs {
        height: <?php echo (int)$products*((int)$main_image_height + $marge); ?>px;
    }
<?php
    }
?>
    #hikashop_carousel_pagination_<?php echo $mainDivName; ?> .swiper-pagination {
        position: relative;
        right: 0px;
        left: 0px;
    }
    #<?php echo $mainDivName; ?> #hikashop_carousel_<?php echo $mainDivName; ?> {
        display: inline-block;
        width: 90%;
    }
<?php
if ($pagination_type == "thumbnails") {
?>  #<?php echo $mainDivName; ?> .swiper-thumbs {
        width: <?php echo $thumbnailWidth; ?>px;
    }
<?php
}
else {
?>  #<?php echo $mainDivName; ?> .swiper-thumbs {
        width: 25px;
    }
<?php
}
?>  #<?php echo $mainDivName; ?> .swiper-thumbs .swiper-wrapper div {
        margin-bottom: 2px !important;
    }
<?php
}

else {
?>  #hikashop_carousel_<?php echo $mainDivName; ?>.swiper-vertical {
        height: <?php echo (int)$products*((int)$main_image_height + $marge); ?>px;
    }
    #hikashop_carousel_<?php echo $mainDivName; ?>.swiper-vertical > div {
        height: <?php echo (int)$products*(int)$main_image_height; ?>px;
    }
    #<?php echo $mainDivName; ?> .swiper-thumbs {
        width: 100%;
    }
    #hikashop_carousel_thumbs_<?php echo $mainDivName; ?> .swiper-wrapper .swiper-slide {
        width: <?php echo $thumbnailWidth; ?>px !important;
        margin-right: 3px !important;
    }
    #hikashop_carousel_<?php echo $mainDivName; ?> .swiper-slide {
        margin-bottom: 0px !important;
    }
    #hikashop_carousel_pagination_<?php echo $mainDivName; ?> .swiper-pagination-bullet {
        display: inline-block;
        margin-left: 5px;
    }
    #hikashop_carousel_thumbs_<?php echo $mainDivName; ?> .swiper-wrapper {
        left: 25%;
        right: 25%;
    }
<?php
    if ($pagination_position == 'top') {
?>      #<?php echo $mainDivName; ?> #hikashop_carousel_<?php echo $mainDivName; ?> {
            padding-top: <?php echo (int)$thumbnailHeight + 5; ?>px;
        }
        #hikashop_carousel_pagination_<?php echo $mainDivName; ?> {
            position: absolute;
            top: 0;
            height: 25px;
        }
        #<?php echo $mainDivName; ?> .swiper-thumbs {
            position: absolute;
            top: 0;
            height: <?php echo (int)$thumbnailHeight + 15; ?>px;
        }
        #hikashop_carousel_pagination_<?php echo $mainDivName; ?> {
            width: 100%;
        }
        #hikashop_carousel_pagination_<?php echo $mainDivName; ?> .swiper-pagination {
            right: 0;
            left: 0;
        }
    <?php
    }
    if ($pagination_position == 'inside') {
?>      #<?php echo $mainDivName; ?> .swiper-thumbs {
            position: relative;
            top: -<?php echo $thumbnailHeight; ?>px;
        }
        #hikashop_carousel_pagination_<?php echo $mainDivName; ?> .swiper-pagination {
            position: relative;
            transform: unset;
            left: 0;
            top: -20px;
        }
<?php
    }
    if ($pagination_position == 'bottom') {
?>      #hikashop_carousel_pagination_<?php echo $mainDivName; ?> .swiper-pagination {
            position: relative;
            transform: unset;
            left: 0;
        }
<?php  
    }
}
?>
</style>
<?php


if ($navigation) {
    $options["navigation"] =
    '{nextEl:"#hikashop_carousel_parent_div_' .
    $mainDivName .
    ' .swiper-button-next", prevEl:"#hikashop_carousel_parent_div_' .
    $mainDivName .
    ' .swiper-button-prev"}';
}
$autoplay = (bool) $this->params->get("auto_slide");
if ($autoplay) {
    $delay = $this->params->get("auto_slide_duration");
    if (empty($delay)) {
        $delay = 3000;
    }
    $options["autoplay"] =
        "{pauseOnMouseEnter:true, disableOnInteraction:false, delay:" .
        $delay .
        "}";
}
$options["slidesPerView"] = $products;
$speed = $this->params->get("carousel_effect_duration");
if (empty($speed)) {
    $speed = 1500;
}
$options["speed"] = $speed;
$paginationDivClasses='';
if ($pagination) {
    switch ($pagination_position) {
        case "top":
            $paginationDivClasses .=
                " swiper-pagination-" . $pagination_position;
            break;
        case "bottom":
            $paginationDivClasses .=
                " swiper-pagination-" . $pagination_position;
            break;
        case "left":
            $paginationDivClasses .=
                " swiper-pagination-" . $pagination_position;
            break;
        case "right":
            $paginationDivClasses .=
                " swiper-pagination-" . $pagination_position;
            break;
        case "inside":
            $paginationDivClasses .=
                " swiper-pagination-" . $pagination_position;
            break;
    }
    $options["pagination"] = 
    '{el:"#hikashop_carousel_pagination_' .
    $mainDivName .
    '>.swiper-pagination", clickable:true}';
}
if ($products > 1) {
    $slidesPerView = $this->params->get("one_by_one") ? 1 : $products;
    $slideByFor2 = $this->params->get("one_by_one") ? 1 : 2;
    $options["breakpoints"] =
        "{0:{slidesPerView:1},768:{slidesPerView:" .
        $slideByFor2 .
        ", spaceBetween:" .
        $spaceBetween .
        "},992:{slidesPerView:" .
        $products .
        ", spaceBetween:" .
        $spaceBetween .
        "}}";
}
?>
<div class="hikashop_carousel_parent_div <?php echo $paginationClass; ?>" id="hikashop_carousel_parent_div_<?php echo $mainDivName; ?>">
    <div class="<?php echo $mainDivClasses; ?>">
<?php
    if ($navigation) {
?>
        <div class="swiper-button-prev"></div>
<?php
}
?>
        <div class="swiper <?php echo $direction; ?>" id="hikashop_carousel_<?php echo $mainDivName; ?>" <?php if($rtl){ ?>dir="rtl"<?php } ?>>
            <div class="swiper-wrapper">
<?php 
foreach ($this->rows as $row) {
    $this->row = &$row;
?>
                <div class="swiper-slide hikashop_carousel_item hikashop_subcontainer">
<?php
    $this->setLayout("listing_" . $this->params->get("div_item_layout_type"));
    echo $this->loadTemplate();
?>
                </div>
<?php
}
?>
            </div>
        </div>
<?php
if ($pagination && $pagination_type == "rounds") {
?>
        <div id="hikashop_carousel_pagination_<?php echo $mainDivName; ?>">
            <div class="swiper-pagination<?php echo $paginationDivClasses; ?>">
            </div>
        </div>
<?php
}
if ($pagination && $pagination_type != "rounds") {
   $i = 0;
    $pagination_html =
        '<div thumbsSlider="" class="swiper '.$thumbsDirection.'" id="hikashop_carousel_thumbs_' .
        $mainDivName .
        '"><div class="swiper-wrapper">';
    foreach ($this->rows as $row) {
        $i++;
        $this->row = &$row;
        $thumbs = $i;
        $thumbs_data = "";
        switch ($pagination_type) {
            case "numbers":
                $thumbs_data = $i;
                break;
            case "names":
                $thumbs_data = $this->escape($row->category_name);
                break;
            case "thumbnails":
                $img = $this->image->getThumbnail(
                    @$this->row->file_path,
                    ["width" => $thumbnailWidth, "height" => $thumbnailHeight],
                    [
                        "default" => true,
                        "forcesize" => $this->config->get(
                            "image_force_size",
                            true
                        ),
                        "scale" => $this->config->get(
                            "image_scale_mode",
                            "inside"
                        ),
                    ]
                );
                if ($img->success) {
                    $thumbs_data = '<img src="' . $img->url . '" />';
                }
                break;
        }
        $pagination_html .=
            '<div class="swiper-slide">' . $thumbs_data . "</div>";
    }
    $pagination_html .= "</div></div>";
    echo $pagination_html;
    $options["thumbs"] = "{swiper: thumbs_". $mainDivName."}";
}
if ($navigation) {
?>
        <div class="swiper-button-next"></div>
<?php
}
?>
    </div>
</div>
<script type="text/javascript">
window.hikashop.ready(function(){


<?php

$ThumbsOptions = [];
if ($pagination_position == 'left' || $pagination_position == 'right') 
    $ThumbsOptions['direction'] = '"vertical"';
$ThumbsOptions['spaceBetween'] = $spaceBetween;
$ThumbsOptions['slidesPerView'] = $thumbs;
$ThumbsOptions['freeMode'] = true;
$ThumbsOptions['watchSlidesProgress'] = true;
$ThumbsOptions['autoHeight'] = true;

if ($pagination && $pagination_type != "rounds") {
?>
    var thumbs_<?php echo $mainDivName; ?> = new Swiper('#hikashop_carousel_thumbs_<?php echo $mainDivName; ?>', {
<?php
foreach ($ThumbsOptions as $key => $val) {
    if (is_bool($val)) {
        $val = $val ? "true" : "false";
    }
    echo $key . ":" . $val . ",";
}
?>
    });
<?php
}
?>  
    var carousel_<?php echo $mainDivName; ?> = new Swiper('#hikashop_carousel_<?php echo $mainDivName; ?>', {
<?php
foreach ($options as $key => $val) {
    if (is_bool($val)) {
        $val = $val ? "true" : "false";
    }
    echo $key . ":" . $val . ",";
}
?>
    });
<?php
if($autoplay) {
?>
    document.querySelector("#hikashop_carousel_<?php echo $mainDivName; ?>").addEventListener('mouseover',function() {
        carousel_<?php echo $mainDivName; ?>.autoplay.stop();
    });
    document.querySelector("#hikashop_carousel_<?php echo $mainDivName; ?>").addEventListener('mouseout',function() {
        carousel_<?php echo $mainDivName; ?>.autoplay.start();
    });
<?php
}
?>
});
</script>
