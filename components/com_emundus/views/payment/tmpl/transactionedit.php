<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Payment\TransactionRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Entities\Payment\TransactionStatus;

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();

if (!empty($this->item_id))
{
	$payment_repository = new PaymentRepository();

	$transaction_repository = new TransactionRepository();
	$transaction = $transaction_repository->getById($this->item_id);

	if (EmundusHelperAccess::asAccessAction($payment_repository->getActionId(), 'u', $app->getIdentity()->id, $transaction->getFnum())) {
		$datas = [
			'transaction' => $transaction->serialize(),
            'statuses' => array_map(
	            function ($status) {
		            return [
			            'value'    => $status->value,
			            'label' => $status->getLabel(),
			            'badge' => $status->getHtmlBadge()
		            ];
	            },
	            TransactionStatus::cases()
            ),
            'services' => $payment_repository->getPaymentServices(),
		];
	} else {
		$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
		$app->redirect('/index.php?option=com_emundus&view=payment&layout=transactions');
	}
} else {
	$app->enqueueMessage(Text::_('COM_EMUNDUS_TRANSACTION_NOT_FOUND'), 'error');
	$app->redirect('/index.php?option=com_emundus&view=payment&layout=transactions');
}
?>

<div id="em-component-vue"
     component="Payment/TransactionEdit"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
