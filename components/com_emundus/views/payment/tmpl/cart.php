<?php

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Payment\TransactionRepository;
use Tchooz\Entities\Payment\TransactionStatus;

$app = Factory::getApplication();

if (empty($this->cart)) {
	$app->enqueueMessage(Text::_('COM_EMUNDUS_CART_NOT_FOUND'), 'error');
	$app->redirect('/');
}

Text::script('COM_EMUNDUS_CART');
Text::script('COM_EMUNDUS_CART_RECAP');
Text::script('COM_EMUNDUS_PRICE');
Text::script('COM_EMUNDUS_TOTAL');
Text::script('COM_EMUNDUS_CHECKOUT');
Text::script('COM_EMUNDUS_CART_CUSTOMER_ADDRESS');
Text::script('COM_EMUNDUS_CART_PAYMENT_METHOD');
Text::script('COM_EMUNDUS_CART_NO_PAYMENT_METHODS');
Text::script('COM_EMUNDUS_CART_SELECTED_PRODUCTS');
Text::script('COM_EMUNDUS_CART_SELECT_PRODUCTS');
Text::script('COM_EMUNDUS_CUSTOMER_NAME');
Text::script('COM_EMUNDUS_CUSTOMER_FIRSTNAME');
Text::script('COM_EMUNDUS_CUSTOMER_LASTNAME');
Text::script('COM_EMUNDUS_CUSTOMER_EMAIL');
Text::script('COM_EMUNDUS_CUSTOMER_PHONE');
Text::script('COM_EMUNDUS_CUSTOMER_ADDRESS');
Text::script('COM_EMUNDUS_CUSTOMER_ADDRESS_1');
Text::script('COM_EMUNDUS_CUSTOMER_ADDRESS_2');
Text::script('COM_EMUNDUS_CUSTOMER_ZIPCODE');
Text::script('COM_EMUNDUS_CUSTOMER_CITY');
Text::script('COM_EMUNDUS_CUSTOMER_COUNTRY');
Text::script('COM_EMUNDUS_SAVE_CUSTOMER_ADDRESS');
Text::script('COM_EMUNDUS_EDIT_CUSTOMER_ADDRESS');
Text::script('COM_EMUNDUS_CANCEL_CUSTOMER_ADDRESS');
Text::script('COM_EMUNDUS_ADD_DISCOUNT');
Text::script('COM_EMUNDUS_ADD_PRODUCT');
Text::script('COM_EMUNDUS_MARKETPLACE_INTRO');
Text::script('COM_EMUNDUS_MARKETPLACE_SELECTED');
Text::script('COM_EMUNDUS_ADD_TO_CART');
Text::script('COM_EMUNDUS_CART_INSTALLMENT_NUMBER');
Text::script('COM_EMUNDUS_CART_INSTALLMENT_NUMBER_RECAP');
Text::script('COM_EMUNDUS_TOTAL_ADVANCE');
Text::script('COM_EMUNDUS_CART_PAYMENT_RULES');
Text::script('COM_EMUNDUS_CART_PAYMENT_PAY_ADVANCE_OR_TOTAL_LABEL');
Text::script('COM_EMUNDUS_CART_PAYMENT_PAY_ADVANCE');
Text::script('COM_EMUNDUS_CART_PAYMENT_PAY_TOTAL');

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();

$readonly = !$this->cart_repository->canUserUpdateCart($this->cart, $this->user->id);

assert($this->cart instanceof Tchooz\Entities\Payment\CartEntity);
$datas = [
    'cart' => $this->cart->serialize(),
    'step' => $this->cart->getPaymentStep()->serialize(),
    'readOnly' => $readonly,
];

if ($readonly) {
	$transaction_repository = new TransactionRepository();
	$transaction = $transaction_repository->getTransactionByCart($this->cart);

    if (!empty($transaction) && $transaction->getStatus() === TransactionStatus::WAITING) {
        $app->enqueueMessage(Text::_('COM_EMUNDUS_TRANSACTION_IS_WAITING_FOR_VALIDATION'));
    }
}
?>

<div id="em-component-vue"
     component="Payment/Cart"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
