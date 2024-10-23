<?php
/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

use Joomla\CMS\Language\Text;

?>
<div class="view-form">
    <div class="span12 tw-m-0 tw-w-full">
        <form id="payer-infos" class="fabrikForm">
            <fieldset class="fabrikGroup">
                <h3>Régler les frais d'inscriptions</h3>
                <section id="recap_payment" class="tw-mt-4">
                    <p class="em-font-weight-600"><?= Text::_('MOD_EMUNDUS_PAYMENT_RECAP_FOR') ?>
                        <b> <?= " " . $campaign->label ?></b></p>
                    <p class="tw-mt-2"><?= Text::_('MOD_EMUNDUS_PAYMENT_PRICE') . " : " . $price . "€" ?></p>
                </section>

                <div class="tw-w-full tw-flex tw-items-center tw-justify-end tw-mt-4">
                    <a id="submit-payer-infos" class="em-front-btn em-front-primary-btn em-w-33"
                       href="<?php echo $payment_url ?>" target="_blank">
						<?= Text::_('MOD_EMUNDUS_PAYMENT_OPEN_FLYWIRE') ?>
                    </a>
                </div>
            </fieldset>
        </form>
    </div>
</div>
