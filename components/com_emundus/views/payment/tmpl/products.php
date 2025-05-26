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

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
?>

<div id="em-component-vue"
     component="Payment/Products"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
