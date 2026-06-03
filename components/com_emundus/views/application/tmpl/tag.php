<?php

use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Label\LabelRepository;

defined('_JEXEC') or die('Restricted access');

if (empty($this->applicationFile) || !($this->applicationFile instanceof ApplicationFileEntity))
{
    return;
}

$labelRepository = new LabelRepository();
$applicationTags = $labelRepository->getLabelAssociationsByFnum($this->applicationFile->getFnum());

$data = LayoutFactory::prepareVueData();
$data['application'] = $this->applicationFile->__serialize();
$data['applicationTags'] = array_map(function ($tag) { return $tag->__serialize(); }, $applicationTags);
$data['tagOptions'] = array_map(function ($tag) { return $tag->__serialize(); }, $labelRepository->get());

?>
<div class="tags">
    <div class="row">
        <div class="panel panel-default widget em-container-tag">
            <div class="panel-heading em-container-tag-heading !tw-bg-profile-full">
                <h3 class="panel-title tw-flex tw-items-center tw-gap-1">
                    <span class="material-symbols-outlined">sell</span>
                    <span><?php echo Text::_('COM_EMUNDUS_TAGS'); ?></span>
                    <span class="tw-rounded tw-bg-white !tw-text-neutral-900 tw-px-2"><?php echo count($applicationTags); ?></span>
                </h3>

                <div class="btn-group pull-right">
                    <button id="em-prev-file" class="btn btn-info btn-xxl"><span
                            class="material-symbols-outlined">arrow_back</span></button>
                    <button id="em-next-file" class="btn btn-info btn-xxl"><span
                            class="material-symbols-outlined">arrow_forward</span></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tw-p-6">
    <div id="em-component-vue"
         component="Application/ApplicationTags"
         data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
    >
    </div>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] . uniqid() ?>"></script>
