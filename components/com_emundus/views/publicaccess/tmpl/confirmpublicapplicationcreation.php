<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die('Restricted access');

?>
<div class="tw-flex tw-flex-col tw-items-center tw-text-center tw-gap-4 tw-m-auto tw-w-50 tw-p-4 tw-min-h-[50vh] tw-max-w-[500px] tw-justify-evenly">
    <div class="tw-flex tw-flex-col tw-gap-2">
        <h2><?= Text::_('JLOGIN'); ?></h2>
        <p><?= Text::_('COM_EMUNDUS_ACCESS_PERSONAL_ACCOUNT'); ?></p>
        <a class="tw-btn-secondary" href="<?= Route::_('index.php?option=com_users&view=login&cid=' . $this->campaignId); ?>">
            <?= Text::_('COM_EMUNDUS_CONNEXION_BUTTON'); ?>
        </a>
    </div>

    <div class="tw-flex tw-flex-row tw-items-center tw-w-full tw-gap-2">
        <hr class="tw-w-full" />
        <span class="tw-w-fit tw-text-neutral-500"><?= strtoupper(Text::_('COM_EMUNDUS_OR')); ?></span>
        <hr class="tw-w-full" />
    </div>
    <div class="tw-flex tw-flex-col tw-gap-2">
        <h2><?= Text::_('COM_EMUNDUS_NOT_AUTHENTICATED'); ?></h2>
        <p><?= Text::_('COM_EMUNDUS_APPLY_TO_CAMPAIGN_PUBLICLY'); ?></p>

        <a href="<?= Route::_('/index.php?option=com_emundus&task=applyPubliclyToCampaign&campaign_id=' . $this->campaignId); ?>"
           class="tw-btn-primary">
            <?= Text::_('COM_EMUNDUS_APPLY_PUBLICALLY_BUTTON'); ?>
        </a>

        <div>
            <p class="tw-text-sm"><?= Text::_('COM_EMUNDUS_ALREADY_HAVE_PUBLIC_ACCESS_FILE'); ?></p>
            <a class="tw-text-sm" href="<?= Route::_('/index.php?option=com_emundus&view=publicaccess', false); ?>">
                <?= Text::_('COM_EMUNDUS_RETRIEVE_PUBLIC_ACCESS_FILE'); ?>
            </a>
        </div>
    </div>
</div>