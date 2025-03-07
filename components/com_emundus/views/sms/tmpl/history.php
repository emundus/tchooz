<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

Text::_('COM_EMUNDUS_SMS_LABEL');
Text::_('COM_EMUNDUS_SMS_MESSAGE');
Text::_('COM_EMUNDUS_SMS_UPDATED_SUCCESSFULLY');

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
?>

<div id="em-component-vue"
     component="SMS/SMSGlobalHistory"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
