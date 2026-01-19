<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

Text::script('COM_EMUNDUS_ONBOARD_SMS');
Text::script('COM_EMUNDUS_ONBOARD_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SEARCH');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_SMS_LABEL');
Text::script('COM_EMUNDUS_SMS_MESSAGE');
Text::script('COM_EMUNDUS_SMS_UPDATED_SUCCESSFULLY');
Text::script('COM_EMUNDUS_SMS_TEMPLATE_ADDED');

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     component="SMS/SMS"
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
