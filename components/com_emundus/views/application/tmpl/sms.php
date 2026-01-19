<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

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

$data = LayoutFactory::prepareVueData();

?>

<div class="row">
    <div class="panel panel-default widget em-container-sms em-container-form">
        <div class="panel-heading em-container-form-heading !tw-bg-profile-full">
            <div class="tw-flex tw-items-center tw-gap-2">
                <span class="material-symbols-outlined !tw-text-neutral-50">sms</span>
                <span class="!tw-text-neutral-50"><?= Text::_('COM_EMUNDUS_ONBOARD_SMS') ?></span>
            </div>
            <div class="btn-group pull-right">
                <button id="em-prev-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_back</span>
                </button>
                <button id="em-next-file" class="btn btn-info btn-xxl"><span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="tw-p-6">
    <div id="em-component-vue"
         component="SMS/SMSAppFile"
         fnum="<?= $this->fnum ?>"
         shortLang="<?= $data['short_lang'] ?>"
         currentLanguage="<?= $data['current_lang'] ?>"
    >
    </div>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
