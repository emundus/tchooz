<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_menu
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Note. It is important to remove spaces between elements.
?>
<style type='text/css'>
    hr{
        z-index: 999;
    }
    .edge .g-active{
        position: absolute !important;
        left: -100% !important;
        z-index: 1 !important ;
        margin-left: -50px !important;
        top: -9px !important;
        /* right: 100%; */
        margin-top: 2px !important;
    }

    /*** Navbar ***/
    #header-a{
        position: relative;
        left: 5%;
        opacity: 0;
    }

    @media all and (max-width: 479px) {
        #header-a{
            opacity: 1;
        }
    }

    #header-b{
        width: auto;
        position: fixed;
        background: white;
        left: 0;
        top: 0;
        padding: 10px;
        height: 100%;
        align-items: baseline;
        overflow-y: auto;
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
    #header-b::-webkit-scrollbar {
        display: none;
    }
    #header-b #em_user_menu li.parent-active:hover a{
        color: black;
    }

    #header-b #em_user_menu li:hover a, #header-b #em_user_menu li:active a, #header-b #em_user_menu li:focus a{
        color: black;
        filter: brightness(90%);
    }
    .g-sublevel .g-menu-item-title span:hover,.g-sublevel .g-menu-item-title span:focus,.g-sublevel .g-menu-item-title span:active {
        color: black;
    }
    .g-sublevel-list{
        margin-top: 10px;
    }
    .g-sublevel-list::before{
        position: absolute;
        height: 50%;
        border: solid 3px #16AFE1;
        border-radius: 25px;
    }
    /*** END ***/

    /*** Sublevel parent ***/
    ul.tchooz-vertical-toplevel > li.active.tchooz-vertical-item > a.item::before{
        background: #12DB42;
        width: 5px;
        height: 100%;
        content: "";
        position: absolute;
        left: -20px;
    }
    .active .item .image-title{
        color: #12DB42;
    }
    .g-menu-parent-indicator{
        margin-left: 20px;
        align-self: center;
    }
    .parent-indicator-close::after{
        opacity: 1 !important;
        content: "\f054" !important;
    }
    /*** END ***/

    /*** List style ***/
    #g-navigation .g-main-nav .tchooz-vertical-toplevel > li{
        width: 50px;
        margin: 5px 10px !important;
        font-family: var(--font);
    }

    .g-menu-item.g-standard.tchooz-vertical-item.tchooz-vertical-logo.tchooz-vertical-item.tchooz-vertical-logo > a {
        margin-bottom: 16px;
    }
    .g-menu-item.g-standard.tchooz-vertical-item.tchooz-vertical-logo.tchooz-vertical-item.tchooz-vertical-logo img {
        width: 30px;
    }

    #g-navigation .g-main-nav .g-sublevel > li:not(:last-child) > .g-menu-item-container{
        border-bottom: unset !important;
    }

    .tchooz-vertical-item a{
        display: flex;
        align-items: center;
        white-space: nowrap;
        position: relative;
        z-index: 2;
        transition: all 0.3s ease-in-out;
    }
    .tchooz-vertical-item a img{
        width: 30px;
        height: 30px;
        padding: 5px;
    }

    .image-title{
        margin-left: 30px;
        transition: opacity 0.2s ease-in-out;
    }

    /*.g-sublevel-list{
        margin-left: 40px !important;
        margin-top: 10px !important;
        position: fixed;
        top: 90px;
        max-width: 200px;
    }*/
    /*** END ***/

    /*** Back button ***/
    .g-main-nav .g-standard .g-go-back{
        display: block;
        border-bottom: solid 1px #e0e0e5;
        margin-bottom: 15px !important;
    }
    .g-go-back a span{
        display: block;
        margin-left: 10px;
    }
    .burger-button{
        margin: 0 10px 10px 10px;
        background: transparent;
        padding: 0;width: 30px
    }
    /*** END ***/
    .message-tooltip{
        width: 20rem;
        height: auto;
        position: fixed;
        margin-left: 0;
        color: black;
        align-items: center;
        font-weight: 600;
        display: none;
        margin-top: -38px;
        z-index: 1;
    }
    .message-tooltip-block p {
        font-weight: 600;
        border-bottom: 1px solid #e0e0e5;
        padding-bottom: 10px;
        color: var(--neutral-900);
    }

    .message-tooltip-block{
        margin-left: 60px;
        box-shadow: 0 5px 10px rgb(0 0 0 / 10%);
        padding: 15px;
        border-radius: 5px;
        background: #fff;
    }
    .message-tooltip-block::after{
        content: "";
        position: absolute;
        height: 0;
        width: 0;
        right: 100%;
        top: 10px;
        border: 10px solid transparent;
        border-right-color: transparent;
        border-right-style: solid;
        border-right-width: 10px;
        border-right: 10px solid #fff;
    }

    .g-main-nav .g-standard .g-sublevel .g-menu-item a.g-menu-item-container:hover   {
        background: #eeeeee;
        border-radius: 5px;
    }

    .g-sublevel{
        margin-top: 10px !important;
    }
    .g-sublevel .tchooz-vertical-item::before {
        height: 110%;
        position: absolute;
        display: block;
       border: solid 2px #fff;
        border-radius: 5px;
        content: "";
    }
</style>
<nav class="g-main-nav <?php echo $class_sfx;?>" data-g-hover-expand="true"
    <?php
    $tag = '';
    if ($params->get('tag_id')!=NULL) {
        $tag = $params->get('tag_id').'';
        echo ' id="'.$tag.'"';
    }
    ?>>
    <div style="opacity: 0" class="grey-navbar-icons"></div>
    <ul class="g-toplevel tchooz-vertical-toplevel">
        <button class="g-menu-item g-standard burger-button" onclick="enableTitles()"><img src="<?php echo JURI::base()?>images/emundus/menus/menu.png" style="width: 30px"></button>
        <?php

        $target_dir = "images/custom/";
        $filename = 'favicon';
        $favicon = glob("{$target_dir}{$filename}.*");

        if(file_exists(JPATH_SITE . '/' . $favicon[0])){
            $favicon = JURI::base().'/' . $favicon[0];
        } else {
            $favicon = JURI::base().'/images/emundus/tchooz_favicon.png';
        }
        echo '<li class="g-menu-item g-standard tchooz-vertical-item tchooz-vertical-logo" style="height: auto"><a class="item" href="'.$favicon_link.'"><img src="'.$favicon.'" alt="Accueil"></a>
        </li>';

        if ($display_tchooz) :
            foreach ($tchooz_list as $i => &$item) :
                $item->anchor_css="item";
                $class = 'item-'.$item->id.' g-standard';
                if ($item->id == $active_id) {
                    $class .= ' current';
                }

                if (in_array($item->id, $path)) {
                    $class .= ' active';
                }
                elseif ($item->type == 'alias') {
                    $aliasToId = $item->getParams()->get('aliasoptions');
                    if (count($path) > 0 && $aliasToId == $path[count($path)-1]) {
                        $class .= ' active';
                    }
                    elseif (in_array($aliasToId, $path)) {
                        $class .= ' alias-parent-active';
                    }
                }

                if ($item->parent) {
                    $class .= ' g-parent';
                }

                if (!empty($class)) {
                    $class = ' class="tchooz-vertical-item g-menu-item g-menu-'.trim($class) .'"';
                }

                echo '<li' . $class . 'onmouseenter="enableTooltip(' . $item->id . ')" onmouseleave="disableTooltip(' . $item->id . ')">';

                // Render the menu item.
                switch ($item->type) :
                    case 'separator':
                    case 'url':
                    case 'component':
                        require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_'.$item->type);
                        break;

                    default:
                        require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_url');
                        break;
                endswitch;

                // The next item is deeper.
                if ($item->deeper) {
                    echo '<ul class="g-sublevel-list" id="sublevel_list_' . $item->id . '" style="display: none">';
                    echo '<li class="g-dropdown-column">';
                    echo '<div class="g-grid"><div class="g-block size-100"><ul class="g-sublevel"><li class="g-level-'.($item->level).' g-go-back"><a class="g-menu-item-container" href="#" onclick="backToParentMenu(' . $item->id . ')" data-g-menuparent=""><span class="g-menu-item-content"><span class="g-menu-item-title">' . JText::_("COM_EMUNDUS_BACK") . '</span></span></a></li>';
                }
                // The next item is shallower.
                elseif ($item->shallower) {
                    echo '<div class="message-tooltip" id="tooltip-'.$item->id.'"><div class="message-tooltip-block"> '.$item->title.'</div>';
                    echo '</li>';
                    echo str_repeat('</ul></div></div></li></ul>', $item->level_diff);
                }
                // The next item is on the same level.
                else {
                    echo '<div class="message-tooltip" id="tooltip-'.$item->id.'"><div class="message-tooltip-block"><a class class="'.$class.'" href="'. $item->flink . '"> '.$item->title.'</a></div></div>';
                    echo '</li>';
                }
            endforeach;

            if(sizeof($tchooz_list) > 0) :
                echo '<hr id="menu_separator">';
            endif;
        endif;

        foreach ($list as $i => &$item) :
            if($item->alias != 'homepage' && $item->getParams()->get('menu_show') != 0) :
                $item->anchor_css="item";
                $class = 'item-'.$item->id.' g-standard';
                if ($item->id == $active_id) {
                    $class .= ' current';
                }

                if (in_array($item->id, $path)) {
                    $class .= ' active';
                }
                elseif ($item->type == 'alias') {
                    $aliasToId = $item->getParams()->get('aliasoptions');
                    if (count($path) > 0 && $aliasToId == $path[count($path)-1]) {
                        $class .= ' active';
                    }
                    elseif (in_array($aliasToId, $path)) {
                        $class .= ' alias-parent-active';
                    }
                }

                if ($item->parent) {
                    $class .= ' g-parent';
                }

                if (!empty($class)) {
                    $class = ' class="tchooz-vertical-item g-menu-item g-menu-'.trim($class) .'"';
                }

                if($item->level == 1) {
                    echo '<li' . $class . 'onmouseenter="enableTooltip(' . $item->id . ')" onmouseleave="disableTooltip(' . $item->id . ')">';
                } else {
                    echo '<li' . $class.'>';
                }

                // Render the menu item.
                switch ($item->type) :
                    case 'separator':
                    case 'url':
                    case 'component':
                        require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_'.$item->type);
                        break;

                    default:
                        require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_url');
                        break;
                endswitch;

                // The next item is deeper.
                if ($item->deeper) {
                    echo '<div class="message-tooltip" id="tooltip-'.$item->id.'" style="height: auto"><div class="message-tooltip-block"><p>'.$item->title.'</p>';
                    echo '<ul class="g-sublevel-list" id="sublevel_list_' . $item->id . '">';
                    echo '<li class="g-dropdown-column">';
                    echo '<div class="g-grid"><div class="g-block size-100"><ul class="g-sublevel">';
                }
                // The next item is shallower.
                elseif ($item->shallower) {
                    //echo '<div class="message-tooltip" id="tooltip-'.$item->id.'">'.$item->title.'</div>';
                    echo '</li>';
                    echo str_repeat('</ul></div></div></li></ul>', $item->level_diff);
                }
                // The next item is on the same level.
                else {
                    echo '<div class="message-tooltip" id="tooltip-'.$item->id.'"><div class="message-tooltip-block"> <a class class="'.$class.'" href="'. $item->flink . '"> '.$item->title.'</a></div></div>';
                    echo '</li>';
                }
            endif;
        endforeach;



        foreach ($help_list as $i => &$item) :
            if($item->getParams()->get('menu_show') != 0) :
                $item->anchor_css="item";
                $class = 'item-'.$item->id.' g-standard';
                if ($item->id == $active_id) {
                    $class .= ' current';
                }

                if (in_array($item->id, $path)) {
                    $class .= ' active';
                }
                elseif ($item->type == 'alias') {
                    $aliasToId = $item->getParams()->get('aliasoptions');
                    if (count($path) > 0 && $aliasToId == $path[count($path)-1]) {
                        $class .= ' active';
                    }
                    elseif (in_array($aliasToId, $path)) {
                        $class .= ' alias-parent-active';
                    }
                }

                if ($item->parent) {
                    $class .= ' g-parent';
                }

                if (!empty($class)) {
                    $class = ' class="tchooz-vertical-item g-menu-item g-menu-'.trim($class) .'"';
                }

                echo '<li'.$class.'onmouseenter="enableTooltip('.$item->id.')" onmouseleave="disableTooltip('.$item->id.')">';

                // Render the menu item.
                switch ($item->type) :
                    case 'separator':
                    case 'url':
                    case 'component':
                        require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_'.$item->type);
                        break;

                    default:
                        require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_url');
                        break;
                endswitch;

                // The next item is deeper.
                if ($item->deeper) {
                    echo '<ul class="g-sublevel-list" id="sublevel_list_' . $item->id . '" style="display: none">';
                    echo '<li class="g-dropdown-column">';
                    echo '<div class="g-grid"><div class="g-block size-100"><ul class="g-sublevel"><li class="g-level-'.($item->level).' g-go-back"><a class="g-menu-item-container" href="#" onclick="backToParentMenu(' . $item->id . ')" data-g-menuparent=""><span class="g-menu-item-content"><span class="g-menu-item-title">' . JText::_("COM_EMUNDUS_BACK") . '</span></span></a></li>';
                }
                // The next item is shallower.
                elseif ($item->shallower) {
                    echo '</li>';
                    echo str_repeat('</ul></div></div></li></ul>', $item->level_diff);
                }
                // The next item is on the same level.
                else {
                    echo '<div class="message-tooltip" id="tooltip-'.$item->id.'"><div class="message-tooltip-block">'.$item->title.'</div></div>';

                    //'<a'.$class.' href="'. $item->flink . '"'. $item->title .'></a>

                    echo '</li>';
                }
            endif;
        endforeach;
        ?>
    </ul>
</nav>


<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".g-sublevel > li").forEach(function(el) {
            el.addEventListener("mouseenter", function(e) {
                if (this.querySelector("ul")) {
                    var elm = this.querySelector("ul:first-child");
                    var off = elm.getBoundingClientRect();
                    var l = off.left;
                    var w = elm.offsetWidth;
                    var docH = document.querySelector("#g-page-surround").offsetHeight;
                    var docW = document.querySelector("#g-page-surround").offsetWidth;
                    var isEntirelyVisible = (l + w <= docW);

                    if (!isEntirelyVisible) {
                        this.classList.add("edge");
                    } else {
                        this.classList.remove("edge");
                    }
                }
            });
        });
    });


    //Keep original tooltip margin to reposition after mouse out (usefull in case of window resizing)
    const originalMargin = parseInt(document.querySelector("[id^=tooltip-]:first-of-type").style.marginTop, 10);

    function enableTooltip(menu) {
        if (window.getComputedStyle(document.querySelector(".image-title")).getPropertyValue("display") !== "none") {
            if (document.querySelector("#sublevel_list_" + menu)) {
                document.querySelector("#tooltip-" + menu).style.marginLeft = "200px";
                document.querySelector("#tooltip-" + menu).style.display = "block";
            }
        } else {
            document.querySelector("#tooltip-" + menu).style.marginLeft = "0";
            document.querySelector("#tooltip-" + menu).style.display = "block";

            //Reposition tooltip if out of viewport or scroll happened
            var tooltipBox = document.querySelector("#tooltip-" + menu);
            var tooltipRect = tooltipBox.getBoundingClientRect();
            const menuBox = document.querySelector("li.g-menu-item-"+menu);
            const menuRect = menuBox.getBoundingClientRect();
            const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

            //reposition after scrolling
            if(tooltipRect.top - menuRect.top > 10){
                document.querySelector("#tooltip-" + menu).style.marginTop = -(tooltipRect.top - menuRect.top - originalMargin)+'px';
                //get new position of tooltip
                tooltipBox = document.querySelector("#tooltip-" + menu);
                tooltipRect = tooltipBox.getBoundingClientRect();
            }

            //reposition out of viewport
            if(tooltipRect.bottom > viewportHeight){
                document.querySelector("#tooltip-" + menu).style.marginTop = -(tooltipRect.bottom - viewportHeight - parseInt(document.querySelector("#tooltip-" + menu).style.marginTop, 10))+'px';
            }
        }
    }

    function disableTooltip(menu) {
        document.querySelector("#tooltip-" + menu).style.display = "none";
        document.querySelector("#tooltip-" + menu).style.marginTop = originalMargin + "px";
    }

    function enableTitles(state) {
        if (
            window.getComputedStyle(document.querySelector(".image-title")).display === "none" &&
            state === undefined
        ) {
            localStorage.setItem("menu", "true");
            document.querySelector(".tchooz-vertical-toplevel").style.width = "250px";
            document.querySelectorAll(".tchooz-vertical-item").forEach(function (elem) {
                elem.style.width = "auto";
            });
            document.querySelector(".grey-navbar-icons").style.opacity = "1";
            if(document.querySelector(".sidebar-formbuilder")) {
                document.querySelector(".sidebar-formbuilder").style.opacity = "0";
            }
            if (window.innerWidth >= 1280) {
                if(document.querySelector("#g-footer")) {
                    document.querySelector("#g-footer").style.paddingLeft = "300px";
                    if(document.querySelector("#footer-rgpd")) {
                        document.querySelector("#footer-rgpd").style.paddingLeft = "300px";
                    }
                }
                document.querySelector("#g-container-main").style.paddingLeft = "180px";
                document.querySelector("#header-a").style.opacity = "1";
            }
            setTimeout(() => {
                document.querySelectorAll(".image-title").forEach(function (elem) {
                    elem.style.display = "block";
                    elem.style.opacity = "1";
                });
                if(document.querySelector(".sidebar-formbuilder")) {
                    document.querySelector(".sidebar-formbuilder").style.display = "none";
                }
                setTimeout(() => {
                    if(document.querySelector(".g-menu-parent-indicator")) {
                        document.querySelector(".g-menu-parent-indicator").style.display = "block";
                    }
                }, 50);
            }, 250);
        } else if (state === "true") {
            document.querySelector(".tchooz-vertical-toplevel").style.width = "250px";
            document.querySelectorAll(".tchooz-vertical-item").forEach(function (elem) {
                elem.style.width = "auto";
            });
            document.querySelector(".grey-navbar-icons").style.opacity = "1";
            if(document.querySelector(".sidebar-formbuilder")) {
                document.querySelector(".sidebar-formbuilder").style.opacity = "0";
            }
            if (window.innerWidth >= 1280) {
                if(document.querySelector("#g-footer")) {
                    document.querySelector("#g-footer").style.paddingLeft = "300px";
                    if(document.querySelector("#footer-rgpd")) {
                        document.querySelector("#footer-rgpd").style.paddingLeft = "300px";
                    }
                }
                document.querySelector("#g-container-main").style.paddingLeft = "180px";
                document.querySelector("#header-a").style.opacity = "1";
            }
            setTimeout(() => {
                document.querySelectorAll(".image-title").forEach(function (elem) {
                    elem.style.display = "block";
                    elem.style.opacity = "1";
                });
                if(document.querySelector(".sidebar-formbuilder")) {
                    document.querySelector(".sidebar-formbuilder").style.display = "none";
                }
                setTimeout(() => {
                    if(document.querySelector(".g-menu-parent-indicator")) {
                        document.querySelector(".g-menu-parent-indicator").style.display = "block";
                    }
                }, 50);
            }, 250);
        } else {
            localStorage.setItem("menu", "false");
            document.querySelector(".tchooz-vertical-toplevel").style.width = "55px";
            document.querySelectorAll(".image-title").forEach(function (elem) {
                elem.style.opacity = "0";
            });
            if(document.querySelector(".g-menu-parent-indicator")) {
                document.querySelectorAll(".g-menu-parent-indicator").forEach(function (elem) {
                    elem.style.display = "none";
                });
            }

            if(document.querySelector(".sidebar-formbuilder")) {
                document.querySelector(".sidebar-formbuilder").style.display = "block";
                document.querySelector(".sidebar-formbuilder").style.opacity = "1";
            }

            if (window.innerWidth >= 1280) {
                document.querySelector("#g-container-main").style.paddingLeft = "0";
                if(document.querySelector("#g-footer")) {
                    document.querySelector("#g-footer").style.paddingLeft = "80px";
                    if(document.querySelector("#footer-rgpd")) {
                        document.querySelector("#footer-rgpd").style.paddingLeft = "80px";
                    }
                }
                document.querySelector("#header-a").style.opacity = "0";
            }

            setTimeout(function() {
                document.querySelectorAll(".image-title").forEach(function(elem) {
                    elem.style.display = "none";
                });
                document.querySelectorAll(".grey-navbar-icons").forEach(function(elem) {
                    elem.style.opacity = "0";
                });
            }, 50);
        }
    }

    function backToParentMenu(id){
        document.querySelector(".tchooz-vertical-toplevel").style.width = '250px';
        document.querySelectorAll(".tchooz-vertical-item").forEach(function (elem) {
            elem.style.width = "auto";
        });
        document.querySelector("#menu_separator").style.width = 'auto';
        document.querySelector(".g-menu-item-" + id).removeClass('parent-active');
        document.querySelector("#sublevel_list_" + id).style.display =  "none";
        document.querySelector(".g-menu-parent-indicator").style.display = "block";
        document.querySelectorAll(".image-title").forEach(function (elem) {
            elem.style.display = "block";
            elem.style.opacity = "1";
        });
    }

    document.addEventListener('click', function (e) {
        e.stopPropagation();
        if(document.querySelector(".image-title").style.display == 'block') {
            enableTitles();
        }
    });

    window.onload = function () {
        this.enableTitles(localStorage.getItem('menu'));
    }

</script>