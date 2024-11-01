<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

if (!empty($params['proof_attachment'])) {
    $sort_price = str_replace(',', '', $product->product_sort_price);
    $price = number_format((double)$sort_price, 2, '.', ' ');
    $document->addScript(Uri::base(). '/modules/mod_emundus_payment/assets/js/transfer.js');
    $reference = $params['reference_prefix'] . substr($user->fnum, 8, 6) . $user->id;

    $label = $helper->getAttachmentLabelFromId($params['proof_attachment']);
    $allowed_extensions = $helper->getAttachmentAllowedExtTypes($params['proof_attachment']);
    $extensions = array_keys($allowed_extensions);
    $mime_types = array_values($allowed_extensions);
} else {
    $app = Factory::getApplication();
    $app->enqueueMessage(Text::_('MOD_EMUNDUS_PAYMENT_TRANSFER_ERROR_MISSING_PROOF_ATTACHMENT'), 'error');
    return false;
}
?>

<?php if (strpos(Uri::getInstance(), 'payment_method') !== false) { ?>
    <a href="<?= str_replace('&payment_method=transfer' , '', Uri::getInstance()) ?>" class="tw-flex tw-items-center tw-text-neutral-900 tw-w-fit-content">
        <span class="material-symbols-outlined tw-mr-1" aria-hidden="true">navigate_before</span>
		<?= Text::_('MOD_EMUNDUS_PAYMENT_GO_BACK_TO_METHOD_CHOICE') ?>
    </a>
<?php } ?>

<section class="tw-rounded tw-bg-white tw-p-4 tw-mt-4" style="min-height: 200px;">
    <div class="hidden em-loader" style="margin: auto;"></div>
    <div class="panier">
        <div class="tw-mt-4">
            <table>
                <tbody>
                <tr>
                    <th><?= Text::_('MOD_EMUNDUS_PAYMENT_PRICE') ?></th>
                    <td><?= $price ?>€</td>
                </tr>
                <tr>
                    <th><?= Text::_('MOD_EMUNDUS_PAYMENT_ACCOUNT_HOLDER') ?></th>
                    <td><?= $params['account_holder'] ?></td>
                </tr>
                <tr>
                    <th><?= Text::_('MOD_EMUNDUS_PAYMENT_BENIFICIARY_ADDRESS') ?></th>
                    <td><?= $params['beneficiary_bank_address'] ?></td>
                </tr>
                <tr>
                    <th><?= Text::_('MOD_EMUNDUS_PAYMENT_BENIFICIARY_IBAN') ?></th>
                    <td><?= $params['beneficiary_iban'] ?></td>
                </tr>
                <tr>
                    <th><?= Text::_('MOD_EMUNDUS_PAYMENT_BENIFICIARY') ?></th>
                    <td><?= $params['beneficiary_bank'] ?></td>
                </tr>
                <tr>
                    <th><?= Text::_('MOD_EMUNDUS_PAYMENT_PERSONAL_REFERENCE') ?><b class="em-red-500-color">*</b></th>
                    <td><?= $reference ?></td>
                </tr>
                </tbody>
            </table>
            <p class="tw-mt-4 em-red-500-color">* <?= Text::_('MOD_EMUNDUS_PAYMENT_PLEASE_REPORT_REFERENCE') ?></p>
        </div>
        <div id="upload-proof-file" class="tw-mt-4 tw-mb-4">
            <label class="tw-font-bold" for="proof-file"> <?= Text::_('MOD_EMUNDUS_PAYMENT_UPLOAD_PROOF_FILE') ?></label>
            <input
                data-attachment="<?= $params['proof_attachment']  ?>"
                data-attachment-labem="<?= $label ?>"
                style="height: auto;"
                id="proof-file"
                type="file"
                accept="<?= implode(',', $mime_types) ?>"
                max="1"
            >
        </div>
        <div class="tw-w-full tw-flex tw-items-center tw-justify-end">
            <button id="submit-transfer" class="em-front-btn em-front-primary-btn em-w-33"><?= Text::_('MOD_EMUNDUS_PAYMENT_SUBMIT_TRANSFER') ?></button>
        </div>
    </div>
</section>