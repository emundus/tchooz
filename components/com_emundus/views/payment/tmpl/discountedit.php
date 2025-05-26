<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

Text::script('COM_EMUNDUS_ONBOARD_PRODUCTS_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_PRODUCTS');
Text::script('COM_EMUNDUS_PRODUCTS');
Text::script('COM_EMUNDUS_ONBOARD_PRODUCTS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NO_PRODUCTS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_PRODUCT');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_DISCOUNT_DESCRIPTION');
Text::script('COM_EMUNDUS_DISCOUNT_VALUE');
Text::script('COM_EMUNDUS_DISCOUNT_EDIT');
Text::script('COM_EMUNDUS_DISCOUNT_ADD');
Text::script('COM_EMUNDUS_DISCOUNT_SAVE');
Text::script('COM_EMUNDUS_DISCOUNT_LABEL');
Text::script('COM_EMUNDUS_DISCOUNT_CURRENCY');
Text::script('COM_EMUNDUS_DISCOUNT_QUANTITY');
Text::script('COM_EMUNDUS_DISCOUNT_AVAILABLE_FROM');
Text::script('COM_EMUNDUS_DISCOUNT_AVAILABLE_TO');
Text::script('COM_EMUNDUS_DISCOUNT_PUBLISHED');
Text::script('COM_EMUNDUS_DISCOUNT_SAVED');
Text::script('COM_EMUNDUS_DISCOUNT_TYPE');
Text::script('COM_EMUNDUS_DISCOUNT_TYPE_FIXED');
Text::script('COM_EMUNDUS_DISCOUNT_TYPE_PERCENTAGE');


$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();

$datas = [
	'discountId' => $this->item_id,
]
?>

<div id="em-component-vue"
     component="Payment/DiscountEdit"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
