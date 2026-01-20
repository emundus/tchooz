<?php

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

$datas = LayoutFactory::prepareVueData();

Text::script('COM_EMUNDUS_ONBOARD_MAPPINGS');
Text::script('COM_EMUNDUS_ONBOARD_MAPPINGS_INTRO');
Text::script('COM_EMUNDUS_MAPPINGS');
Text::script('COM_EMUNDUS_MAPPING');
Text::script('COM_EMUNDUS_MAPPING_ADD');
Text::script('COM_EMUNDUS_MAPPING_EDIT');
Text::script('COM_EMUNDUS_MAPPING_DELETE');
Text::script('COM_EMUNDUS_MAPPING_DELETE_CONFIRM');
Text::script('COM_EMUNDUS_MAPPING_SAVED');
Text::script('COM_EMUNDUS_MAPPING_NAME');
Text::script('COM_EMUNDUS_MAPPING_SYNCHRONIZER_SERVICE');
Text::script('COM_EMUNDUS_ONBOARD_NO_MAPPINGS');
?>

<div id="em-component-vue"
     component="Mapping/MappingList"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>


<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
