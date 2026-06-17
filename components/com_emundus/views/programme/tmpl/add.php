<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\LayoutFactory;

Text::script('SAVE');
Text::script('BACK');
Text::script('CANCEL');
Text::script('COM_EMUNDUS_PROGRAM_FORM_CREATE_TITLE');
Text::script('COM_EMUNDUS_PROGRAM_LABEL_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_CODE_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_PROGRAMMES_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_PUBLISHED_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_DESCRIPTION_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_SYNTHESIS_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_SYNTHESIS_HELP');
Text::script('COM_EMUNDUS_PROGRAM_LOGO_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_FORM_CREATE');

$data = LayoutFactory::prepareVueData();
$data['program'] = (new ProgramEntity('', '', 0))->__serialize();
?>

<div id="em-component-vue" component="Program/ProgramForm"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
