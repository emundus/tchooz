<?php
/**
 * @package     Joomla.Site
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

$current_lang = empty($current_lang) ? 'fr' : $current_lang;
$file_version = empty($file_version) ? '1.0.0' : $file_version;
?>

<div id="mod_emundus_help">
    <p data-toggle="popover" class="mod_emundus_help__popover"><span class="material-icons">help</span></p>
    <div id="mod_emundus_help__popover_content" class="popover hidden" style="margin-top:-65px;">
        <div class="popover-inner">
            <div class="popover-content">
                <a href='https://emundus.atlassian.net/wiki/spaces/HD/overview' target='_blank'><span class='material-icons'>menu_book</span><p><?= JText::_('MOD_EMUNDUS_HELP_ARTICLES'); ?></p></a>
                <a href='https://emundus.atlassian.net/servicedesk/customer/portals' target='_blank'><span class='material-icons'>textsms</span><p><?= JText::_('MOD_EMUNDUS_HELP_HELP_CENTER'); ?></p></a>
                <hr/>
                <?php if($current_lang == 'fr') : ?>
                    <a href='https://emundus.atlassian.net/wiki/external/2456584208/YTIyZmRmNTFmODc1NDg5YWI1MDdkMmZhYjc0YjBmNjY?atlOrigin=eyJpIjoiZDgzYmE2NzVlZjI2NDQxMmEyNmJhNzgxNGE5N2M3YzYiLCJwIjoiYyJ9' target='_blank'>
                        <span class='material-icons'>new_releases</span>
                        <p><?= JText::_('MOD_EMUNDUS_HELP_LAST_RELEASE'); ?></p>
                    </a>
                <?php else : ?>
                    <a href='https://emundus.atlassian.net/wiki/external/2472378369/NTY1MDA4NWJlNTk5NGY2NWEzMjkwNTUwMzBkYWZkMWQ' target='_blank'>
                        <span class='material-icons'>new_releases</span>
                        <p><?= JText::_('MOD_EMUNDUS_HELP_LAST_RELEASE'); ?></p>
                    </a>
                <?php endif; ?>
                <hr/>
                <span>Version <?php echo trim($file_version) ?></span>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelector('p[data-toggle="popover"]').addEventListener('click', () => {
        document.getElementById('mod_emundus_help__popover_content').classList.toggle('hidden');
    });

    document.addEventListener('click', function(evt) {
        let popover = document.getElementById('mod_emundus_help'),
            targetEl = evt.target; // clicked element
        do {
            if(targetEl === popover) {
                return;
            }
            // Go up the DOM
            targetEl = targetEl.parentNode;
        } while (targetEl);

        const popoverContent = document.getElementById('mod_emundus_help__popover_content');
        if (popoverContent.classList.contains('hidden') === false) {
            popoverContent.classList.add('hidden');
        }
    });
</script>

<style>
    #mod_emundus_help__popover_content {
        bottom: 74px;
        top: unset;
        left: 55px;
    }
</style>
