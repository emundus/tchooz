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
