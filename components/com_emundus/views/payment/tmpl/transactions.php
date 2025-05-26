<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

Text::script('COM_EMUNDUS_ONBOARD_TRANSACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_TRANSACTIONS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_VISUALIZE');
Text::script('COM_EMUNDUS_ONBOARD_NO_TRANSACTIONS');
Text::script('COM_EMUNDUS_TRANSACTION_DETAILS');
Text::script('COM_EMUNDUS_TRANSACTION_PRODUCT_QUANTITY');
Text::script('COM_EMUNDUS_TRANSACTION_PRODUCT_LABEL');
Text::script('COM_EMUNDUS_TRANSACTION_PRODUCT_PRICE');
Text::script('COM_EMUNDUS_TRANSACTION_PRODUCT_DESCRIPTION');
Text::script('COM_EMUNDUS_TRANSACTION_ALTERATIONS');
Text::script('COM_EMUNDUS_TRANSACTION_ALTERATION_DESCRIPTION');
Text::script('COM_EMUNDUS_TRANSACTION_ALTERATION_AMOUNT');
Text::script('COM_EMUNDUS_LIST_CLOSE_PREVIEW');
Text::script('COM_EMUNDUS_ONBOARD_CONFIRM_TRANSACTION');
Text::script('COM_EMUNDUS_ONBOARD_CANCEL_TRANSACTION');

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$fnum = Factory::getApplication()->input->getString('fnum', '');

if (!empty($fnum)) {
	$datas = ['defaultFilter' => 'fnum=' . $fnum, 'readOnly' => !EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)];
} else {
    $datas = [ 'readOnly' => !EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)];
}
?>

<div id="em-component-vue"
     component="Payment/Transactions"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
