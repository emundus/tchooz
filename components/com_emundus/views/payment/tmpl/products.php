<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Actions\ActionRepository;

Text::script('COM_EMUNDUS_ONBOARD_PRODUCTS_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_PRODUCTS');
Text::script('COM_EMUNDUS_PRODUCTS');
Text::script('COM_EMUNDUS_ONBOARD_PRODUCTS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NO_PRODUCTS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_PRODUCT');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_PRODUCT_DESCRIPTION');
Text::script('COM_EMUNDUS_PRODUCT_PRICE');
Text::script('COM_EMUNDUS_ONBOARD_PRODUCT_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_DISCOUNTS');
Text::script('COM_EMUNDUS_DISCOUNTS');
Text::script('COM_EMUNDUS_ONBOARD_DISCOUNTS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NO_DISCOUNTS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_DISCOUNT');
Text::script('COM_EMUNDUS_PRODUCT_CATEGORY_SAVE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_PRODUCT_FILTER_CATEGORY');
Text::script('COM_EMUNDUS_ONBOARD_PRODUCT_FILTER_CATEGORY_ALL');
Text::script('COM_EMUNDUS_ONBOARD_DISCOUNT_DELETE');
Text::script('COM_EMUNDUS_DISCOUNT_SAVED');

$data = LayoutFactory::prepareVueData();

$user = Factory::getApplication()->getIdentity();

$actionRepository = new ActionRepository();
$paymentAction   = $actionRepository->getByName('payment');

$data['crud'] = [
    'payment' => [
        'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($paymentAction->getId(), 'c', $user->id),
        'r' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($paymentAction->getId(), 'r', $user->id),
        'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($paymentAction->getId(), 'u', $user->id),
        'd' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($paymentAction->getId(), 'd', $user->id),
    ]
];
?>

<div id="em-component-vue"
     component="Payment/Products"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
