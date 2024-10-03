<?php
defined('_JEXEC') || die;

$parameters = $this->form->getFieldset('importer');
if (!$this->form || empty($parameters)) {
    echo JText::sprintf('COM_DROPFILES_CONFIGURATION_NOT_FOUND');
    return;
}

?>
<div class="main-container">
    <div class="settings-title">
        <h1 class="settings-header"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORTER_TAB_LABEL') ?></h1>
    </div>
    <div class="container-settings">
        <ul class="field block-list">
            <?php foreach ($parameters as $k => $field) : ?>
                <?php if ($k === 'jform_serverfolder') : ?>
                    <li class="ju-settings-option block-item import-settings-option <?php echo $field->id;?>">
                        <?php
                        $width_100 = ($field->type !== 'Number' && $field->type !== 'Textarea' && $field->type !== 'Text' && $field->type !== 'List') ? ' ju-width-100' : '';
                        echo '<div class="ju-custom-block ' . $width_100 . '" >';
                        echo $field->input;
                        echo '</div>';
                        ?>
                    </li>
                <?php endif;?>
            <?php endforeach; ?>
        </ul>

        <div class="clearfix"></div>
    </div>
</div>