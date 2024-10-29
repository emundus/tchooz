<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication();

if (empty($user) || empty($payment) || empty($product) || empty($countries) || empty($campaign)) {
    $app->enqueueMessage(Text::_('MOD_EMUNDUS_PAYMENT_ERROR_MISSING_DATA'), 'error');
    return false;
}

$document = $app->getDocument();
$document->addScript('https://payment.flywire.com/assets/js/checkout.js');
$document->addScript(Uri::base(). '/modules/mod_emundus_payment/assets/js/flywire-init.js');

$sort_price = str_replace(',', '', $product->product_sort_price);
$price = number_format((double)$sort_price, 2, '.', ' ');
$lang = $app->getLanguage()->getTag();
?>
<div id="flywire-payment" data-fnum="<?= $user->fnum; ?>">
    <?php if (strpos(Uri::getInstance(), 'payment_method') !== false) { ?>
        <a class="tw-flex tw-items-center tw-text-neutral-900 tw-w-fit-content tw-mb-4" href="<?= str_replace('&payment_method=flywire' , '', Uri::getInstance()) ?>">
            <span class="material-symbols-outlined tw-mr-1" aria-hidden="true">navigate_before</span>
            <?= Text::_('MOD_EMUNDUS_PAYMENT_GO_BACK_TO_METHOD_CHOICE') ?>
        </a>
    <?php } ?>
    <div class="view-form">
        <div class="span12 tw-m-0 tw-w-full">
            <form id="payer-infos" class="fabrikForm">
                <div class="fabrikGroup tw-p-4 tw-rounded tw-mb-4">
                    <h2><?= Text::_('MOD_EMUNDUS_PAYMENT_FLYWIRE_INFORMATIONS') ?></h2>
                    <br/>
                    <?php if (!empty($config) && $config['flywire_status'] == 'cancelled') { ?>
                        <section class="tw-mb-8">
                            <p><?= Text::_('MOD_EMUNDUS_PAYMENT_ALREADY_TRIED_PAYMENT_BUT_CANCELLED') ?></p>
                        </section>
                    <?php } ?>

                    <section id="recap_payment" class="tw-mb-6">
                        <p><?= Text::_('MOD_EMUNDUS_PAYMENT_RECAP_FOR') ?> <b> <?= " " . $campaign->label ?></b></p>
                        <p><?= Text::_('MOD_EMUNDUS_PAYMENT_PRICE') . " : " .  $price . "€" ?></p>
                    </section>
                    <div class="row-fluid tw-mb-3">
                        <label for="sender_first_name"><?= Text::_('FLYWIRE_SENDER_FIRST_NAME') ?><b class="asterisk">*</b></label>
                        <input id="sender_first_name" type="text" class="tw-w-full" placeholder=""
                               value="<?=  !empty($config['sender_first_name']) ? $config['sender_first_name'] : ''  ?>">
                    </div>

                    <div class="row-fluid tw-mb-3">
                        <label for="sender_last_name"><?= Text::_('FLYWIRE_SENDER_LAST_NAME') ?><b class="asterisk">*</b></label>
                        <input id="sender_last_name" type="text" class="tw-w-full" placeholder=""
                               value="<?=  !empty($config['sender_last_name']) ? $config['sender_last_name'] : ''  ?>">
                    </div>

                    <div class="row-fluid tw-mb-3">
                        <label for="sender_email"><?= Text::_('FLYWIRE_SENDER_EMAIL') ?><b class="asterisk">*</b></label>
                        <input id="sender_email" type="email" class="tw-w-full" placeholder="" value="<?= !empty($config['sender_email']) ? $config['sender_email'] : ''  ?>">
                    </div>

                    <div class="row-fluid tw-mb-3">
                        <label for="sender_phone"><?= Text::_('FLYWIRE_SENDER_PHONE') ?></label>
                        <input
                           id="sender_phone"
                           type="text"
                           class="tw-w-full"
                           placeholder=""
                           pattern="^\+?\d+(-\d+)*$"
                           value="<?=  !empty($config['sender_phone']) ? $config['sender_phone'] : ''  ?>"
                        >
                    </div>

                    <div class="row-fluid tw-mb-3">
                        <label for="sender_address1"><?= Text::_('FLYWIRE_SENDER_ADDRESS1') ?><b class="asterisk">*</b></label>
                        <input id="sender_address1" type="text" class="tw-w-full" placeholder=""
                               value="<?=  !empty($config['sender_address1']) ? $config['sender_address1'] : ''  ?>">
                    </div>

                    <div class="row-fluid tw-mb-3">
                        <label for="sender_address2"><?= Text::_('FLYWIRE_SENDER_ADDRESS2') ?></label>
                        <input id="sender_address2" type="text" class="tw-w-full" placeholder=""
                               value="<?=  !empty($config['sender_address2']) ? $config['sender_address2'] : ''  ?>">
                    </div>

                    <div class="row-fluid tw-mb-3">
                        <label for="sender_city"><?= Text::_('FLYWIRE_SENDER_CITY') ?><b class="asterisk">*</b></label>
                        <input id="sender_city" type="text" class="tw-w-full" placeholder=""
                               value="<?=  !empty($config['sender_city']) ? $config['sender_city'] : ''  ?>">
                    </div>

                    <div class="row-fluid tw-mb-3">
                        <label for="sender_state"><?= Text::_('FLYWIRE_SENDER_STATE') ?></label>
                        <input id="sender_state" type="text" class="tw-w-full" placeholder=""
                               value="<?=  !empty($config['sender_state']) ? $config['sender_state'] : ''  ?>">
                    </div>

                    <div class="row-fluid tw-mb-3">
                        <label for="sender_country"><?= Text::_('FLYWIRE_SENDER_COUNTRY') ?><b class="asterisk">*</b></label>
                        <select id="sender_country" class="tw-w-full">
                            <?php
                            foreach ($countries as $country) {
                                $label = $lang == 'fr-FR' ? $country->label_fr : $country->label_en;

                                if (!empty($config['sender_country']) && $config['sender_country'] == $country->code_iso_2) {
                                    echo '<option value="' . $country->code_iso_2 . '" selected>' . $label . '</option>';
                                } else {
                                    echo '<option value="' . $country->code_iso_2 . '">' . $label . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="tw-w-full tw-flex tw-items-end tw-justify-end tw-mt-4">
                        <button id="submit-payer-infos" class="em-front-btn em-front-primary-btn em-w-33">
                            <?= Text::_('MOD_EMUNDUS_PAYMENT_SEND_CONF') ?>
                        </button>
                        <button id="modify-payer-infos" class="hidden em-front-btn em-front-secondary-btn em-w-33 tw-ml-2">
                            <?= Text::_('MOD_EMUNDUS_PAYMENT_REEDIT_CONF') ?>
                        </button>
                    </div>

                    <div id="open-flywire-div" class="hidden tw-w-full tw-flex tw-items-end tw-justify-end tw-mt-4">
                        <button id="open-flywire" class="em-front-btn em-front-primary-btn em-w-33">
                            <?= Text::_('MOD_EMUNDUS_PAYMENT_OPEN_FLYWIRE') ?>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>