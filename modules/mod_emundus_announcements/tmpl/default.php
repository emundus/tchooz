<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_custom
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>

<div class="alerte-message-container tw-text-center tw-w-full tw-bg-red-500" style="padding: 8px 24px;">
    <p style="font-weight: 500; color: #fff;">
        <span style="font-size: 16pt;"><?php echo $announcement_content ?></span>
    </p>
    <span id="close-preprod-alerte-container" aria-hidden="true" class="material-symbols-outlined em-pointer"
          style="color:white;position:absolute;top:10px;right:5px;">close</span>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        let sidebar_menu = document.querySelector('#g-navigation,#g-header #header-b');
        if(sidebar_menu) {
            sidebar_menu.style.top = document.getElementsByClassName('alerte-message-container')[0].offsetHeight + 'px';
        }

        let switch_menu_icon = document.querySelector('.switch-sidebar-icon');
        if(switch_menu_icon) {
            switch_menu_icon.style.top = (document.getElementsByClassName('alerte-message-container')[0].offsetHeight*1.6) + 'px';
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target.id === 'close-preprod-alerte-container') {
            document.querySelector('.alerte-message-container').classList.add('hidden');
            let navigation = document.querySelector('#g-navigation, #g-header');
            if(navigation) {
                navigation.style.top = '0';
            }
        }
    });
</script>
