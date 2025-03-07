<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

Text::script('COM_EMUNDUS_ONBOARD_SMS');
Text::script('COM_EMUNDUS_ONBOARD_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SEARCH');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_SMS_LABEL');
Text::script('COM_EMUNDUS_SMS_MESSAGE');
Text::script('COM_EMUNDUS_SMS_UPDATED_SUCCESSFULLY');
Text::script('COM_EMUNDUS_SMS_TEMPLATE_ADDED');

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
?>

<div id="em-component-vue"
     component="SMS/SMS"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
