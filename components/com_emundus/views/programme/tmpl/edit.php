<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

Text::script('SAVE');
Text::script('BACK');
Text::script('CANCEL');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_TITLE');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_SUBTITLE');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_INTRO');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_TAB_GENERAL');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_TAB_CAMPAIGNS');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_TAB_WORKFLOWS');
Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_WORKFLOWS_ASSOCIATED_TITLE');
Text::script('COM_EMUNDUS_PROGRAMS_ACCESS_TO_WORKFLOWS');
Text::script('COM_EMUNDUS_PROGRAMS_ACCESS_TO_CAMPAIGNS');
Text::script('COM_EMUNDUS_PROGRAM_UPDATE_ASSOCIATED_WORKFLOW_SUCCESS');

$data = LayoutFactory::prepareVueData();

?>

<div id="em-component-vue" component="Program/ProgramEdit"
     program_id="<?= $this->program_id; ?>"
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
     defaultLang="<?= $data['default_lang'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
