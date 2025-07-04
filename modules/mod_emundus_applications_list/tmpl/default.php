<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

?>

<div class="tw-mb-6 tw-flex tw-flex-col tw-gap-8 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 !tw-bg-white tw-p-6 tw-shadow-standard">
    <h2 class="tw-mb-4"><?= Text::_('MOD_APPLICATIONS_LIST_HEADER') ?></h2>
    <?php
        $nb_column = 0;
        foreach ($params->get('content') as $column) {
            $nb_column++;
        }

        $grid_class = 'tw-grid tw-grid-cols-' . $nb_column . ' tw-gap-4';
    ?>

    <div>
        <div class="<?= $grid_class ?> tw-border-b tw-py-4">
            <?php
            foreach ($params->get('content') as $column) {
                ?>
                <div class="tw-font-bold tw-text-lg"><?= $column->label ?></div>
                <?php
            }
            ?>
        </div>
        <?php
        if (!empty($applications)) {
            foreach ($applications as $application) {
                ?>
                <div class="<?= $grid_class ?> tw-border-b">
	                <?php
	                foreach ($params->get('content') as $column) {
	                ?>
                    <div class="tw-text-base tw-text-neutral-900 tw-py-4">
		                <?= $application->{$column->column}; ?>
                    </div>
                    <?php
                } ?>
                </div>
                <?php
            }
        } else {
            echo Text::_('MOD_APPLICATIONS_LIST_NO_APPLICATIONS');
        }
        ?>
    </div>
</div>