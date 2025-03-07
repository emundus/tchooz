<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

Text::script('COM_EMUNDUS_SMS_LABEL');
Text::script('COM_EMUNDUS_SMS_MESSAGE');
Text::script('COM_EMUNDUS_SMS_UPDATED_SUCCESSFULLY');
Text::script('COM_EMUNDUS_SMS_HISTORY');
Text::script('COM_EMUNDUS_SMS_HISTORY_TITLE');
Text::script('COM_EMUNDUS_SEND_SMS');
Text::script('COM_EMUNDUS_SEND_SMS_TITLE');
Text::script('COM_EMUNDUS_SEND_SMS_ACTION');
Text::script('COM_EMUNDUS_SMS_TEMPLATE');
Text::script('COM_EMUNDUS_SMS_TEMPLATE_PLACEHOLDER');
Text::script('COM_EMUNDUS_SMS_RECIPIENTS');
Text::script('COM_EMUNDUS_EMAILS_MESSAGE_FROM');

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();

?>
<div class="tw-p-6">
    <div id="em-sms-send"
         component="SMS/SMSSend"
         fnums="<?= base64_encode(json_encode($this->fnums)) ?>"
         shortLang="<?= $short_lang ?>"
         currentLanguage="<?= $current_lang ?>"
    >
    </div>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>