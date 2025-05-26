<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Payment\ProductRepository;

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
Text::script('COM_EMUNDUS_PRODUCT_EDIT');
Text::script('COM_EMUNDUS_PRODUCT_ADD');
Text::script('COM_EMUNDUS_PRODUCT_SAVE');
Text::script('COM_EMUNDUS_PRODUCT_LABEL');
Text::script('COM_EMUNDUS_PRODUCT_CURRENCY');
Text::script('COM_EMUNDUS_PRODUCT_CATEGORY');
Text::script('COM_EMUNDUS_PRODUCT_CATEGORY_ADD');
Text::script('COM_EMUNDUS_PRODUCT_ILLIMITED');
Text::script('COM_EMUNDUS_PRODUCT_QUANTITY');
Text::script('COM_EMUNDUS_PRODUCT_AVAILABLE_FROM');
Text::script('COM_EMUNDUS_PRODUCT_AVAILABLE_TO');
Text::script('COM_EMUNDUS_PRODUCT_PUBLISHED');
Text::script('COM_EMUNDUS_PRODUCT_SAVED');
Text::script('COM_EMUNDUS_PRODUCT_CATEGORY_SELECT');
Text::script('COM_EMUNDUS_PRODUCT_CAMPAIGNS');

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();

if (!empty($this->item_id)) {
    $product_repository = new ProductRepository();

    try {
	    $product_repository->getProductById($this->item_id);
    } catch (Exception $e) {
        $app->enqueueMessage(Text::_('COM_EMUNDUS_PRODUCT_NOT_FOUND'), 'error');
	    $app->redirect('/index.php?option=com_emundus&view=payment&layout=products');
    }
}

$datas = [
	'productId' => $this->item_id,
]
?>

<div id="em-component-vue"
     component="Payment/ProductEdit"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
