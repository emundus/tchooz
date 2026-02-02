<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\PaymentRepository;

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

$data = LayoutFactory::prepareVueData();

$cart_repository = new CartRepository();
$payment_repository = new PaymentRepository();

if (!class_exists('EmundusModelWorkflow')) {
	require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
}
$m_workflow = new \EmundusModelWorkflow();
$step = $m_workflow->getPaymentStepFromFnum($this->fnum);
$cart = $cart_repository->getCartByFnum($this->fnum, $step->id);

$datas = [
    'fnum' => $this->fnum,
	'cart' => $cart->serialize(),
    'paymentMethods' => array_map(function ($method) { return $method->serialize(); }, $payment_repository->getPaymentMethods()),
    'readOnly' => !$cart_repository->canUserUpdateCart($cart, Factory::getApplication()->getIdentity()->id),
    ...$data
];
PluginHelper::importPlugin('emundus');
$dispatcher         = Factory::getApplication()->getDispatcher();
$onBeforeRenderCart = new GenericEvent('onCallEventHandler', ['onBeforeRenderCart', ['fnum' => $this->fnum]]);
$dispatcher->dispatch('onCallEventHandler', $onBeforeRenderCart);
?>

<div class="row">
    <div class="panel panel-default widget em-container-cart em-container-form">
        <div class="panel-heading em-container-form-heading !tw-bg-profile-full">
            <div class="tw-flex tw-items-center tw-gap-2">
                <span class="material-symbols-outlined !tw-text-neutral-50">shopping_cart</span>
                <span class="!tw-text-neutral-50"><?= Text::_('COM_EMUNDUS_CART') ?></span>
            </div>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span>
                </button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="tw-p-6">
    <div id="em-component-vue"
         component="Payment/CartAppFile"
         data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
    >
    </div>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] . uniqid() ?>"></script>