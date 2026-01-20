<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Payment\ProductCategoryRepository;

Text::script('SAVE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_CONFIGURATION');
Text::script('COM_EMUNDUS_WORKFLOW');
Text::script('COM_EMUNDUS_WORKFLOW_STEP');
Text::script('COM_EMUNDUS_PAYMENT_STEP_MANDATORY_PRODUCTS');
Text::script('COM_EMUNDUS_PAYMENT_STEP_OPTIONAL_PRODUCTS');
Text::script('COM_EMUNDUS_WORKFLOW_CONFIGURE_PAYMENT_STEP');
Text::script('COM_EMUNDUS_SUCCESS');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_METHODS');
Text::script('COM_EMUNDUS_PAYMENT_STEP_NO_PAYMENT_SERVICE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_SELECT_PAYMENT_SERVICE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_SERVICE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE_0');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE_1');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE_2');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE_EDITABLE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT_TYPE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT_TYPE_FIXED');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT_TYPE_PERCENTAGE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_ADJUST_BALANCE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_ADJUST_BALANCE_STEP_ID');
Text::script('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULES');
Text::script('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULES_INFO');
Text::script('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULES_INFO_TITLE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULE_FROM_AMOUNT');
Text::script('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULE_TO_AMOUNT');
Text::script('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULE_MIN_INSTALLMENTS');
Text::script('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULE_MAX_INSTALLMENTS');
Text::script('COM_EMUNDUS_PAYMENT_STEP_ADD_INSTALLMENT_RULE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_INSTALLMENT_DAY');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_INSTALLMENT_DAY_HELPTEXT');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_INSTALLMENT_EFFECT_DATE');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_INSTALLMENT_EFFECT_DATE_HELPTEXT');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PRODUCT_CATEGORIES');
Text::script('COM_EMUNDUS_PAYMENT_STEP_PRODUCT_CATEGORIES_HELPTEXT');
Text::script('COM_EMUNDUS_PAYMENT_STEP_OPTIONAL_PRODUCT_CATEGORIES');
Text::script('COM_EMUNDUS_PAYMENT_STEP_OPTIONAL_PRODUCT_CATEGORIES_HELPTEXT');

$current_step = $this->step;

$product_category_repository = new ProductCategoryRepository();
$datas                       = [
    'workflow'               => $this->current_workflow['workflow'],
    'step'                   => $this->step->serialize(),
    'previous_payment_steps' => array_values(array_filter($this->current_workflow['steps'], function ($step) use ($current_step) {
        return $step->type == $current_step->getType() && $step->id != $current_step->getId();
    })),
    'product_categories'     => array_map(function ($category) {
        return $category->serialize();
    }, $product_category_repository->getProductCategories()),
];

?>

<div id="em-component-vue"
     component="Workflows/WorkflowPaymentStep"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
