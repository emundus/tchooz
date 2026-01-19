<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

$data = LayoutFactory::prepareVueData();

$datas = [
	'fnum' => $this->fnum
];
?>
<div class="row">
    <div class="panel panel-default widget em-container-cart em-container-form">
        <div class="panel-heading em-container-form-heading !tw-bg-profile-full">
            <div class="tw-flex tw-items-center tw-gap-2">
                <span class="material-symbols-outlined !tw-text-neutral-50">fork_right</span>
                <span class="!tw-text-neutral-50"><?= Text::_('COM_EMUNDUS_APPLICATION_CHOICES') ?></span>
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
         component="Application/ApplicationChoices"
         shortLang="<?= $data['short_lang'] ?>"
         currentLanguage="<?= $data['current_lang'] ?>"
         data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
    >
    </div>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
