<?php

defined('_JEXEC') or die('Restricted Access');

use Tchooz\Factories\LayoutFactory;

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     component="Application/ApplicationChoices"
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
