<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

Text::_('COM_EMUNDUS_ONBOARD_ADD_SMS');
Text::_('COM_EMUNDUS_SMS_LABEL');
Text::_('COM_EMUNDUS_SMS_NEW');
Text::_('COM_EMUNDUS_SMS_MESSAGE');
Text::_('COM_EMUNDUS_SMS_UPDATED_SUCCESSFULLY');

Text::_('COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE');

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     component="SMS/SMSEdit"
     smsid="<?= $this->current_sms_template_id; ?>"
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
