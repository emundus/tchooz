<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$current_url = Uri::getInstance()->toString();
$retry_url   = str_replace(['status=cancelled', 'status=error'], '', $current_url);
?>

<div class="tw-rounded tw-bg-white tw-p-4">
    <p><?= Text::_('MOD_EMUNDUS_PAYMENT_FLYWIRE_ERROR') ?></p>
    <p><?= Text::_('MOD_EMUNDUS_PAYMENT_FLYWIRE_TRY_AGAIN_LATER') ?></p>
    <div class="tw-flex tw-items-center tw-justify-between tw-mt-8">
        <a href="<?= $retry_url ?>" class="em-front-btn em-front-secondary-btn em-m-center tw-mr-2">
            <?= Text::_('MOD_EMUNDUS_PAYMENT_FLYWIRE_RETRY') ?>
        </a>
        <a href="<?= Uri::base() ?>" class="em-front-btn em-front-primary-btn em-m-center">
            <?= Text::_('MOD_EMUNDUS_PAYMENT_FLYWIRE_GO_TO_HOMEPAGE') ?>
        </a>
    </div>
</div>
