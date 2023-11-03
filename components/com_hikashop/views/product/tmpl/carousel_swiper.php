<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.0
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
$nb_products = count($this->rows);
$options["direction"] = '"' . $this->params->get("slide_direction") . '"';
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
$mainDivName = $this->params->get("main_div_name");
$spaceBetween = (int) $this->params->get("margin");
$options["spaceBetween"] = $spaceBetween;
$navigation = (bool) $this->params->get("display_button");
$pagination_type = $this->params->get("pagination_type");
$pagination = $pagination_type != "no_pagination";
$pagination_position = $this->params->get("pagination_position");
?>
<style>
    #<?php echo $mainDivName; ?> .hikashop_carousel {
        position: relative;
    }
<?php
if ($navigation) {
?>
    #hikashop_carousel_<?php echo $mainDivName; ?> {
        width: 95%;
    }
<?php
}
if ($pagination && $pagination_type == "rounds" && in_array($pagination_position, array('top', 'bottom'))) {
?>
    #hikashop_carousel_pagination_<?php echo $mainDivName; ?> {
        height: 40px;
    }
<?php
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
$carouselEffect = $this->params->get("carousel_effect");
if ($carouselEffect == "fade") {
    $products = 1;
    $options["loop"] = true;
    $options["autoHeight"] = true;
    $options["mousewheel"] = true;
    $options["spaceBetween"] = 0;
} else {
    $options["direction"] = '"horizontal"';
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
<div class="hikashop_carousel_parent_div <?php echo $pagination_type; ?>" id="hikashop_carousel_parent_div_<?php echo $mainDivName; ?>">
    <div class="<?php echo $mainDivClasses; ?>">
<?php
    if ($navigation) {
?>
        <div class="swiper-button-prev"></div>
<?php
}
?>
        <div class="swiper" id="hikashop_carousel_<?php echo $mainDivName; ?>" <?php if($rtl){ ?>dir="rtl"<?php } ?>>
            <div class="swiper-wrapper">
<?php 
foreach ($this->rows as $row) {
    $this->row = &$row;
?>
                <div class="swiper-slide hikashop_carousel_item hikashop_subcontainer" itemprop="itemList" itemscope="" itemtype="http://schema.org/ItemList">
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
        '<div thumbsSlider="" class="swiper" id="hikashop_carousel_thumbs_' .
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
                $thumbs_data = $this->escape($row->product_name);
                break;
            case "thumbnails":
                $thumbnailHeight = $this->params->get(
                    "pagination_image_height"
                );
                $thumbnailWidth = $this->params->get("pagination_image_width");
                if (empty($thumbnailWidth) && empty($thumbnailHeight)) {
                    $thumbnailHeight = $this->image->main_thumbnail_y / 4;
                    $thumbnailWidth = $this->image->main_thumbnail_x / 4;
                }
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
if ($pagination && $pagination_type != "rounds") {
?>
    var thumbs_<?php echo $mainDivName; ?> = new Swiper('#hikashop_carousel_thumbs_<?php echo $mainDivName; ?>', { spaceBetween: <?php echo $spaceBetween; ?>, slidesPerView: <?php echo $thumbs; ?>, freeMode: true, watchSlidesProgress: true });
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
    document.querySelector("#hikashop_carousel_<?php echo $mainDivName; ?>").addEventListener('mouseover',function() {
        carousel_<?php echo $mainDivName; ?>.autoplay.stop();
    });
    document.querySelector("#hikashop_carousel_<?php echo $mainDivName; ?>").addEventListener('mouseout',function() {
        carousel_<?php echo $mainDivName; ?>.autoplay.start();
    });
});
</script>
