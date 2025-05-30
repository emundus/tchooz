<?php
/**
 * @package        Joomla.Site
 * @subpackage     mod_menu
 * @copyright      Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
// Note. It is important to remove spaces between elements.
?>

<style type='text/css'>
    hr {
        z-index: 999;
    }

    .edge .g-active {
        position: absolute !important;
        left: -100% !important;
        z-index: 1 !important;
        margin-left: -50px !important;
        top: -9px !important;
        /* right: 100%; */
        margin-top: 2px !important;
    }

    /*** Navbar ***/
    #header-a {
        position: relative;
        left: 5%;
    }

    #header-a .logo {
        position: relative;
        left: 5%;
    }

    @media all and (max-width: 479px) {
        #header-a {
            opacity: 1;
        }
    }

    #header-b {
        z-index: 21;
        width: auto;
        position: fixed;
        background: var(--neutral-0);
        left: 0;
        top: 0;
        padding: 10px;
        height: 100%;
        align-items: baseline;
        overflow-y: auto;
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
        box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.25);
    }

    #header-b::-webkit-scrollbar {
        display: none;
    }

    #header-b #em_user_menu li.parent-active:hover a {
        color: var(--neutral-900);
    }

    #header-b #em_user_menu li:hover a, #header-b #em_user_menu li:active a, #header-b #em_user_menu li:focus a {
        color: var(--neutral-900);
    }

    .g-sublevel .g-menu-item-title span:hover, .g-sublevel .g-menu-item-title span:focus, .g-sublevel .g-menu-item-title span:active {
        color: var(--neutral-900);
    }

    .g-sublevel-list {
        margin-top: 10px;
    }

    .g-sublevel-list::before {
        position: absolute;
        height: 50%;
        border: solid 3px #16AFE1;
        border-radius: 25px;
    }

    /*** END ***/

    /*** Sublevel parent ***/
    ul.tchooz-vertical-toplevel > li.tchooz-vertical-item > a.item {
        border-radius: var(--em-default-br);
    }

    ul.tchooz-vertical-toplevel > li.active.tchooz-vertical-item > a.item {
        background: linear-gradient(0deg, rgba(0, 138, 53, 0.10) 0%, rgba(0, 138, 53, 0.10) 100%), #FFF;
    }

    ul.tchooz-vertical-toplevel > li.active.tchooz-vertical-item > a.item span[class*="material-symbols"] {
        color: var(--em-profile-color) !important;
        background-color: hsl(from var(--em-profile-color) h s l / 15%);
        border-radius: var(--em-default-br);
    }

    .active .item .image-title {
        color: var(--em-profile-color);
    }

    li:not(.active) .item:hover {
        background-color: var(--neutral-300);
        border-radius: var(--em-default-br);
    }

    .g-menu-parent-indicator {
        margin-left: 20px;
        align-self: center;
    }

    .parent-indicator-close::after {
        opacity: 1 !important;
        content: "\f054" !important;
    }

    /*** END ***/

    /*** List style ***/
    #g-navigation .g-main-nav .tchooz-vertical-toplevel > li,
    #g-header .g-main-nav .tchooz-vertical-toplevel > li {
        margin-inline: 10px;
        font-family: var(--em-profile-font), Inter, sans-serif;
        margin: 5px 0.5rem;
    }

    .g-menu-item.g-standard.tchooz-vertical-item.tchooz-vertical-logo.tchooz-vertical-item.tchooz-vertical-logo {
        order: -1;
        margin-top: 10px !important;
        display: flex;
        justify-content: center;
        align-items: center;
        padding-bottom: 10px;
    }

    .g-menu-item.g-standard.tchooz-vertical-item.tchooz-vertical-logo.tchooz-vertical-item.tchooz-vertical-logo img {
        width: 30px;
        height: 30px;
        padding: 3px
    }

    #g-navigation .g-main-nav .g-sublevel > li:not(:last-child) > .g-menu-item-container,
    #g-header .g-main-nav .g-sublevel > li:not(:last-child) > .g-menu-item-container  {
        border-bottom: unset !important;
    }

    #g-navigation .g-main-nav .g-sublevel > li:hover:not(.active) > .g-menu-item-container,
    #g-header .g-main-nav .g-sublevel > li:hover:not(.active) > .g-menu-item-container {
        color: var(--neutral-900);
    }

    .tchooz-vertical-item a {
        display: flex;
        align-items: center;
        white-space: nowrap;
        position: relative;
        z-index: 2;
        transition: all 0.3s ease-in-out;
        color: var(--neutral-900);
        text-decoration: none;
    }

    .tchooz-vertical-item a:hover {
        text-decoration: none;
    }

    .tchooz-vertical-item a img {
        width: 30px;
        height: 30px;
        padding-left: 5px;
    }

    .image-title {
        margin-left: 18px;
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
    .g-main-nav .g-standard .g-go-back {
        display: block;
        border-bottom: solid 1px #e0e0e5;
        margin-bottom: 15px !important;
    }

    .g-go-back a span {
        display: block;
        margin-left: 10px;
    }

    .burger-button {
        margin: 0 10px 10px 12px;
        background: transparent;
        padding: 0;
        width: 30px;
        order: 0;
    }

    /*** END ***/
    .message-tooltip {
        width: auto;
        height: auto;
        position: fixed;
        margin-left: 0;
        color: var(--neutral-900);
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
        line-height: normal;
    }

    .message-tooltip-block a {
        font-weight: 400;
        font-style: normal;
        line-height: normal;
    }

    .message-tooltip-block {
        margin-left: 60px;
        box-shadow: 0 5px 10px rgb(0 0 0 / 10%);
        padding: 15px;
        border-radius: 5px;
        background: var(--neutral-0);
    }

    .message-tooltip-block::after {
        content: "";
        position: absolute;
        height: 0;
        width: 0;
        right: 100%;
        top: 12px;
        border: 10px solid transparent;
        border-right-color: transparent;
        border-right-style: solid;
        border-right-width: 10px;
        border-right: 10px solid var(--neutral-0);
    }

    .g-main-nav .g-standard .g-sublevel .g-menu-item a.g-menu-item-container:hover {
        background: #EBECF0 !important;
        border-radius: var(--em-default-br) !important;
    }

    .g-sublevel {
        margin-top: 10px !important;
    }

    .g-sublevel .tchooz-vertical-item::before {
        height: 110%;
        position: absolute;
        display: block;
        border: solid 2px var(--neutral-0);
        border-radius: 5px;
        content: "";
    }

    #g-container-main, #g-footer, #footer-rgpd {
        padding-left: 76px;
    }

    .switch-sidebar-icon {
        position: fixed;
        top: 25px;
        left: 65px;
        background: white;
        border: 1px solid var(--neutral-400);
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        width: 25px;
        height: 25px;
        transition: left 0.3s ease-in-out, background 0.2s ease-in-out, opacity 0.1s ease-in-out;
    }
    .switch-sidebar-icon:hover {
        background: var(--neutral-200);
    }
</style>
<nav class="g-main-nav <?php echo $class_sfx; ?>" data-g-hover-expand="true"
	<?php
	if (!empty($tag)) {
		echo ' id="' . $tag . '"';
	}
	?>>
    <div style="opacity: 0" class="navbar-icons"></div>
    <ul class="g-toplevel tchooz-vertical-toplevel">

<!--        <li class="g-menu-item g-standard tchooz-vertical-item tw-mb-6">
            <a class="item" onclick="enableTitles()">
                <img src="./images/emundus/menus/menu.png" style="width: 30px;padding-left: 5px" alt="<?php echo JText::_('MOD_EMUNDUSMENU_ITEM_MENU') ?>">
                <span class="image-title"
                      style="display: block; opacity: 1;"><?php echo JText::_('MOD_EMUNDUSMENU_ITEM_MENU') ?></span>
            </a>
        </li>-->

		<?php

        echo '<div class="switch-sidebar-icon" onclick="enableTitles()"><span class="material-symbols-outlined">chevron_right</span></div>';

        echo '<li class="g-menu-item g-standard tchooz-vertical-item tchooz-vertical-logo"><a class="item" title="'.JText::_('MOD_EMUNDUSMENU_HOME').'" href="'.$favicon_link.'"><img src="'.JURI::base().'/'.$favicon.'" alt="'.JText::_('MOD_EMUNDUSMENU_HOME').'"></a>
        </li>';

		if ($display_tchooz) :
			foreach ($tchooz_list as $i => &$item) :
                $item->anchor_css = "item";
				$class            = 'item-' . $item->id . ' g-standard';
				if ($item->id == $active_id) {
					$class .= ' current';
				}

				if (in_array($item->id, $path)) {
					$class .= ' active';
				}
                elseif ($item->type == 'alias') {
					$aliasToId = $item->getParams()->get('aliasoptions');
					if (count($path) > 0 && $aliasToId == $path[count($path) - 1]) {
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
					$class = ' class="tchooz-vertical-item g-menu-item g-menu-' . trim($class) . '"';
				}

				echo '<li' . $class . 'onmouseenter="enableTooltip(' . $item->id . ')" onmouseleave="disableTooltip(' . $item->id . ')">';

				// Render the menu item.
				switch ($item->type) :
					case 'separator':
					case 'url':
					case 'component':
						require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_' . $item->type);
						break;

					default:
						require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_url');
						break;
				endswitch;

				// The next item is deeper.
				if ($item->deeper) {
					echo '<ul class="g-sublevel-list" id="sublevel_list_' . $item->id . '" style="display: none">';
					echo '<li class="g-dropdown-column">';
					echo '<div class="g-grid"><div class="g-block size-100"><ul class="g-sublevel"><li class="g-level-' . ($item->level) . ' g-go-back"><a class="g-menu-item-container" href="#" onclick="backToParentMenu(' . $item->id . ')" data-g-menuparent=""><span class="g-menu-item-content"><span class="g-menu-item-title">' . JText::_("COM_EMUNDUS_BACK") . '</span></span></a></li>';
				}
				// The next item is shallower.
                elseif ($item->shallower) {
					echo '<div class="message-tooltip" id="tooltip-' . $item->id . '"><div class="message-tooltip-block"> ' . $item->title . '</div>';
					echo '</li>';
					echo str_repeat('</ul></div></div></li></ul>', $item->level_diff);
				}
				// The next item is on the same level.
				else {
					echo '<div class="message-tooltip" id="tooltip-' . $item->id . '"><div class="message-tooltip-block"><a class class="' . $class . '" href="' . $item->flink . '"> ' . $item->title . '</a></div></div>';
					echo '</li>';
				}
			endforeach;

			if (sizeof($tchooz_list) > 0) :
				echo '<hr id="menu_separator">';
			endif;
		endif;

		foreach ($list as $i => &$item) :
			if ($item->alias != 'homepage' && $item->getParams()->get('menu_show') != 0) :
				$item->anchor_css = "item";
				$class            = 'item-' . $item->id . ' g-standard';
				if ($item->id == $active_id) {
					$class .= ' current';
				}

				if (in_array($item->id, $path)) {
					$class .= ' active';
				}
                elseif ($item->type == 'alias') {
					$aliasToId = $item->getParams()->get('aliasoptions');
					if (count($path) > 0 && $aliasToId == $path[count($path) - 1]) {
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
					$class = ' class="tchooz-vertical-item g-menu-item g-menu-' . trim($class) . '"';
				}

				if ($item->level == 1) {
					echo '<li' . $class . 'onmouseenter="enableTooltip(' . $item->id . ')" onmouseleave="disableTooltip(' . $item->id . ')">';
				}
				else {
					echo '<li' . $class . '>';
				}

				// Render the menu item.
				switch ($item->type) :
					case 'separator':
					case 'url':
					case 'component':
						require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_' . $item->type);
						break;

					default:
						require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_url');
						break;
				endswitch;

				// The next item is deeper.
				if ($item->deeper) {
					echo '<div class="message-tooltip" id="tooltip-' . $item->id . '" style="height: auto"><div class="message-tooltip-block"><p>' . $item->title . '</p>';
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
					echo '<div class="message-tooltip" id="tooltip-' . $item->id . '"><div class="message-tooltip-block"> <a class class="' . $class . '" href="' . $item->flink . '"> ' . $item->title . '</a></div></div>';
					echo '</li>';
				}
			endif;
		endforeach;


		foreach ($help_list as $i => &$item) :
			if ($item->getParams()->get('menu_show') != 0) :
				$item->anchor_css = "item";
				$class            = 'item-' . $item->id . ' g-standard';
				if ($item->id == $active_id) {
					$class .= ' current';
				}

				if (in_array($item->id, $path)) {
					$class .= ' active';
				}
                elseif ($item->type == 'alias') {
					$aliasToId = $item->getParams()->get('aliasoptions');
					if (count($path) > 0 && $aliasToId == $path[count($path) - 1]) {
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
					$class = ' class="tchooz-vertical-item g-menu-item g-menu-' . trim($class) . '"';
				}

				echo '<li' . $class . 'onmouseenter="enableTooltip(' . $item->id . ')" onmouseleave="disableTooltip(' . $item->id . ')">';

				// Render the menu item.
				switch ($item->type) :
					case 'separator':
					case 'url':
					case 'component':
						require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_' . $item->type);
						break;

					default:
						require JModuleHelper::getLayoutPath('mod_emundusmenu', 'tchooz_url');
						break;
				endswitch;

				// The next item is deeper.
				if ($item->deeper) {
					echo '<ul class="g-sublevel-list" id="sublevel_list_' . $item->id . '" style="display: none">';
					echo '<li class="g-dropdown-column">';
					echo '<div class="g-grid"><div class="g-block size-100"><ul class="g-sublevel"><li class="g-level-' . ($item->level) . ' g-go-back"><a class="g-menu-item-container" href="#" onclick="backToParentMenu(' . $item->id . ')" data-g-menuparent=""><span class="g-menu-item-content"><span class="g-menu-item-title">' . JText::_("COM_EMUNDUS_BACK") . '</span></span></a></li>';
				}
				// The next item is shallower.
                elseif ($item->shallower) {
					echo '</li>';
					echo str_repeat('</ul></div></div></li></ul>', $item->level_diff);
				}
				// The next item is on the same level.
				else {
					echo '<div class="message-tooltip" id="tooltip-' . $item->id . '"><div class="message-tooltip-block">' . $item->title . '</div></div>';

					//'<a'.$class.' href="'. $item->flink . '"'. $item->title .'></a>

					echo '</li>';
				}
			endif;
		endforeach;
		?>
    </ul>
</nav>


<script type="text/javascript">
    //Keep original tooltip margin to reposition after mouse out (usefull in case of window resizing)
    const originalMargin = parseInt(document.querySelector("[id^=tooltip-]:first-of-type").style.marginTop, 10);

    function enableTooltip(menu) {
        let enabled = false;
        let tooltipMenu = document.querySelector("#tooltip-" + menu);

        if (tooltipMenu) {
            if (window.getComputedStyle(document.querySelector('.image-title')).getPropertyValue('display') !== 'none') {
                if (document.querySelector('#sublevel_list_' + menu)) {
                    if(document.querySelector(".image-title").style.display == 'block') {
                        tooltipMenu.style.marginLeft = '200px';
                    } else {
                        tooltipMenu.style.marginLeft = '0';
                    }
                    tooltipMenu.style.display = 'block';
                }
            } else {
                tooltipMenu.style.display = 'block';
                tooltipMenu.style.marginLeft = '0';

                //Reposition tooltip if out of viewport or scroll happened
                let tooltipRect = tooltipMenu.getBoundingClientRect();
                const menuBox = document.querySelector('li.g-menu-item-' + menu);
                const menuRect = menuBox.getBoundingClientRect();
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

                //reposition after scrolling
                if (tooltipRect.top - menuRect.top > 10) {
                    tooltipMenu.style.marginTop = -(tooltipRect.top - menuRect.top - originalMargin) + 'px';
                    // get new position of tooltip
                    tooltipRect = tooltipMenu.getBoundingClientRect();
                }

                // reposition out of viewport
                if (tooltipRect.bottom > viewportHeight) {
                    let tooltipMarginTop = tooltipMenu.style.marginTop ? parseInt(tooltipMenu.style.marginTop, 10) : -10;
                    tooltipMenu.style.marginTop = -(tooltipRect.bottom - viewportHeight - tooltipMarginTop) + 'px';
                }
            }

            enabled = true;
        }

        return enabled;
    }

    function disableTooltip(menu) {
        let disabled = false;

        let tooltipMenu = document.querySelector('#tooltip-' + menu);
        if (tooltipMenu) {
            tooltipMenu.style.display = 'none';
            tooltipMenu.style.marginTop = originalMargin + 'px';
            disabled = true;
        }

        return disabled;
    }

    function enableTitles(state) {
        if (
            window.getComputedStyle(document.querySelector(".image-title")).display === "none" &&
            state === undefined
        ) {
            localStorage.setItem("menu", "true");
            document.querySelector(".switch-sidebar-icon").style.left = "260px";
            document.querySelector(".switch-sidebar-icon").style.transform = "rotate(180deg)";
            document.querySelector(".tchooz-vertical-toplevel").style.width = "250px";
            document.querySelectorAll(".tchooz-vertical-item").forEach(function (elem) {
                elem.style.width = "auto";
            });
            document.querySelector(".navbar-icons").style.opacity = "1";
            if (document.querySelector(".sidebar-formbuilder")) {
                document.querySelector(".sidebar-formbuilder").style.opacity = "0";
            }
            if (window.innerWidth >= 0) {
                jQuery("#g-footer").css("padding-left", "280px");
                jQuery("#footer-rgpd").css("padding-left", "280px");
                jQuery("#g-container-main").css("padding-left", "270px");
                //jQuery("#header-a .logo").css("display", "block");
                //jQuery(".tchooz-vertical-logo").css("opacity", "0");
            }
            setTimeout(() => {
                document.querySelectorAll(".image-title").forEach(function (elem) {
                    elem.style.display = "block";
                    elem.style.opacity = "1";
                });
                if (document.querySelector(".sidebar-formbuilder")) {
                    document.querySelector(".sidebar-formbuilder").style.display = "none";
                }
                setTimeout(() => {
                    if (document.querySelector(".g-menu-parent-indicator")) {
                        document.querySelectorAll(".g-menu-parent-indicator").forEach(function (elem) {
                            elem.style.display = "block";
                        });
                    }
                }, 50);
            }, 250);
        } else if (state === "true") {
            document.querySelector(".switch-sidebar-icon").style.left = "260px";
            document.querySelector(".switch-sidebar-icon").style.transform = "rotate(180deg)";
            document.querySelector(".tchooz-vertical-toplevel").style.width = "250px";
            document.querySelectorAll(".tchooz-vertical-item").forEach(function (elem) {
                elem.style.width = "auto";
            });
            document.querySelector(".navbar-icons").style.opacity = "1";
            if (document.querySelector(".sidebar-formbuilder")) {
                document.querySelector(".sidebar-formbuilder").style.opacity = "0";
            }
            if (window.innerWidth >= 0) {
                if (document.querySelector("#g-footer")) {
                    document.querySelector("#g-footer").style.paddingLeft = "280px";
                    if (document.querySelector("#footer-rgpd")) {
                        document.querySelector("#footer-rgpd").style.paddingLeft = "280px";
                    }
                }
                document.querySelector("#g-container-main").style.paddingLeft = "280px";
                //document.querySelector("#header-a .logo").style.display = "block";
            }
            setTimeout(() => {
                document.querySelectorAll(".image-title").forEach(function (elem) {
                    elem.style.display = "block";
                    elem.style.opacity = "1";
                });
                if (document.querySelector(".sidebar-formbuilder")) {
                    document.querySelector(".sidebar-formbuilder").style.display = "none";
                }
                setTimeout(() => {
                    if (document.querySelector(".g-menu-parent-indicator")) {
                        document.querySelectorAll(".g-menu-parent-indicator").forEach(function (elem) {
                            elem.style.display = "block";
                        });
                    }
                }, 50);
            }, 250);
        } else {
            localStorage.setItem("menu", "false");
            document.querySelector(".switch-sidebar-icon").style.left = "65px";
            document.querySelector(".switch-sidebar-icon").style.transform = "rotate(0deg)";
            document.querySelector(".switch-sidebar-icon").style.opacity = "1";
            document.querySelector(".tchooz-vertical-toplevel").style.width = "55px";
            document.querySelectorAll(".image-title").forEach(function (elem) {
                elem.style.opacity = "0";
            });
            if (document.querySelector(".g-menu-parent-indicator")) {
                document.querySelectorAll(".g-menu-parent-indicator").forEach(function (elem) {
                    elem.style.display = "none";
                });
            }

            if (document.querySelector(".sidebar-formbuilder")) {
                document.querySelector(".sidebar-formbuilder").style.display = "block";
                document.querySelector(".sidebar-formbuilder").style.opacity = "1";
            }

            if (window.innerWidth >= 0) {
                document.querySelector("#g-container-main").style.paddingLeft = "76px";
                if (document.querySelector("#g-footer")) {
                    document.querySelector("#g-footer").style.paddingLeft = "76px";
                    if (document.querySelector("#footer-rgpd")) {
                        document.querySelector("#footer-rgpd").style.paddingLeft = "76px";
                    }
                }
                //document.querySelector("#header-a .logo").style.display = "none";
            }

            setTimeout(function () {
                //document.querySelector(".tchooz-vertical-logo").style.opacity = "1";
                document.querySelectorAll(".image-title").forEach(function (elem) {
                    elem.style.display = "none";
                });
                document.querySelectorAll(".navbar-icons").forEach(function (elem) {
                    elem.style.opacity = "0";
                });
            }, 50);
        }
    }

    function backToParentMenu(id) {
        document.querySelector(".tchooz-vertical-toplevel").style.width = '250px';
        document.querySelectorAll(".tchooz-vertical-item").forEach(function (elem) {
            elem.style.width = "auto";
        });
        document.querySelector("#menu_separator").style.width = 'auto';
        document.querySelector(".g-menu-item-" + id).removeClass('parent-active');
        document.querySelector("#sublevel_list_" + id).style.display = "none";
        document.querySelector(".g-menu-parent-indicator").style.display = "block";
        document.querySelectorAll(".image-title").forEach(function (elem) {
            elem.style.display = "block";
            elem.style.opacity = "1";
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".g-sublevel > li").forEach(function (el) {
            el.addEventListener("mouseenter", function (e) {
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

    document.addEventListener('click', function (e) {
        e.stopPropagation();
        const imageTitle = document.querySelector(".image-title");

        if (imageTitle && imageTitle.style.display == 'block') {
            enableTitles();
        }
    });

    document.querySelector('#header-b').addEventListener('mouseover', function (e) {
        document.querySelector('.switch-sidebar-icon').style.opacity = '1';
    });

    document.querySelector('#header-b').addEventListener('mouseleave', function (e) {
        if(document.querySelector(".image-title").style.display == 'block') {
            document.querySelector('.switch-sidebar-icon').style.opacity = '0';
        } else {
            document.querySelector('.switch-sidebar-icon').style.opacity = '1';
        }
    });

    window.onload = function () {
        this.enableTitles(localStorage.getItem('menu'));
    }

</script>
