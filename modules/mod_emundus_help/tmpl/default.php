<?php
/**
 * @package     Joomla.Site
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

$base_url = JUri::base(true);
?>

<div id="mod_emundus_help">
    <p id="trigger" data-toggle="popover" class="mod_emundus_help__popover"><span
            class="material-symbols-outlined tw-text-profile-full tw-text-[20px] hover:tw-text-profile-dark">help</span>
    </p>

    <template data-popover="popover">
        <div class="popover help-popover" id="help_popover" style="margin-top:-65px;">
            <div class="popover-inner">
                <h3 class="popover-title"></h3>
                <div class="popover-content">

					<?php if ($current_lang == 'fr') : ?>
                        <a href='https://emundus.atlassian.net/wiki/x/BoCjn' target='_blank'
                           class="tw-flex tw-items-center tw-gap-2 tw-py-2 tw-px-3 hover:tw-bg-neutral-300">
                            <img
                                class='icone-aide-tchoozy'
                                src='<?php echo $base_url; ?>/media/com_emundus/images/tchoozy/icons/Tchoozy-icone-articles-aide.svg'
                                alt='icone articles aide'>
                            <p><?= JText::_('MOD_EMUNDUS_HELP_ARTICLES'); ?></p>
                        </a>
					<?php else : ?>
                        <a href='https://emundus.atlassian.net/wiki/x/NQDLn' target='_blank'
                           class="tw-flex tw-items-center tw-gap-2 tw-py-2 tw-px-3 hover:tw-bg-neutral-300">
                            <img
                                class='icone-aide-tchoozy'
                                src='<?php echo $base_url; ?>/media/com_emundus/images/tchoozy/icons/Tchoozy-icone-articles-aide.svg'
                                alt='icone articles aide'>
                            <p><?= JText::_('MOD_EMUNDUS_HELP_ARTICLES'); ?></p>
                        </a>
					<?php endif; ?>
                    <a href='https://support.client.emundus.fr/' target='_blank'
                       class="tw-flex tw-items-center tw-gap-2 tw-py-2 tw-px-3 hover:tw-bg-neutral-300">
                        <img
                            class='icone-aide-tchoozy'
                            src='<?php echo $base_url; ?>/media/com_emundus/images/tchoozy/icons/Tchoozy-icone-centre-aide.svg'
                            alt='icone centre aide'>
                        <p><?= JText::_('MOD_EMUNDUS_HELP_HELP_CENTER'); ?></p>
                    </a>
                    <!--<?php if ($current_lang == 'fr') : ?>
                        <a href='https://emundus.atlassian.net/wiki/x/BADPn' target='_blank'
                           class="tw-flex tw-items-center tw-gap-2 tw-py-2 tw-px-3 hover:tw-bg-neutral-300">
                            <img
                                    class='icone-aide-tchoozy'
                                    src='../../../media/com_emundus/images/tchoozy/icons/Tchoozy-icone-videos.svg'
                                    alt='icone centre aide'>
                            <p><?= JText::_('MOD_EMUNDUS_HELP_VIDEOS'); ?></p>
                        </a>
					<?php else : ?>
                        <a href='https://emundus.atlassian.net/wiki/x/FoDMn' target='_blank'
                           class="tw-flex tw-items-center tw-gap-2 tw-py-2 tw-px-3 hover:tw-bg-neutral-300">
                            <span class='material-symbols-outlined tw-text-black'>smart_display</span>
                            <p><?= JText::_('MOD_EMUNDUS_HELP_VIDEOS'); ?></p>
                        </a>
					<?php endif; ?>-->
                    <hr class="tw-m-0" />
                    <a href='https://roadmap.tchooz.app/?t=changelog' target='_blank'
                       class="tw-flex tw-items-center tw-gap-2 tw-py-2 tw-px-3 hover:tw-bg-neutral-300">
                        <span class='material-symbols-outlined tw-text-black'>new_releases</span>
                        <p><?= JText::_('MOD_EMUNDUS_HELP_LAST_RELEASE'); ?></p>
                    </a>
                    <hr class="tw-m-0" />
                    <div class="tw-py-2 tw-px-3">Version <?php echo trim($file_version) ?></div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    //domready
    document.addEventListener('DOMContentLoaded', function() {
        jQuery('[data-toggle="popover"]').popover(
            {
                html: true,
                placement: 'top',
                template: document.querySelector('[data-popover="popover"]').innerHTML,
                content: 'content'
            }
        )

        document.addEventListener('click', function(e) {
            let clickInsideModule = false

            e.composedPath().forEach((pathElement) => {
                if (pathElement.id == 'help_popover') {
                    clickInsideModule = true
                }
            })

            if (clickInsideModule) {
                jQuery('p.mod_emundus_help__popover[data-toggle="popover"]').click()
            } else {
                if (document.querySelector('.help-popover') != null) {
                    jQuery('p.mod_emundus_help__popover[data-toggle="popover"]').click()
                }
            }
        })
    })
</script>
