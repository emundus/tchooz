<?php

use Tchooz\Factories\LayoutFactory;

defined('_JEXEC') or die('Restricted Access');

$data = LayoutFactory::prepareVueData();

$datas = [
    ...$data,
    'options' => $displayData->options,
    'elementId' => $displayData->id,
    'elementName' => $displayData->name,
    'value' => $displayData->value,
];
?>

<div id="<?= $displayData->id; ?>-container"
     class="fabrik-vue-element"
     component="Fabrik/OrderList"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo uniqid(); ?>"></script>
