<?php
use Joomla\CMS\Language\Text;defined('_JEXEC') or die;
?>

<?php if(!empty($rows)) : ?>
    <style>
        .mod-emundus-count-applications th, .mod-emundus-count-applications td {
            text-align: center;
            border: 1px solid #DCE2E9;
        }
        .mod-emundus-count-applications td:first-child {
            background: #eceff3;
        }
    </style>
    <div class="mod-emundus-count-applications tw-bg-white tw-rounded-applicant tw-p-4 tw-border tw-border-neutral-300 tw-mb-4">
        <h1 class="tw-text-center">
            <?php echo $params->get('mod_emundus_count_applications_title', Text::_('MOD_EMUNDUS_COUNT_APPLICATIONS_TITLE')); ?>
        </h1>

        <table style="table-layout: fixed" class="tw-mt-6">
            <thead>
            <th></th>
            <?php foreach ($columns as $key => $column) : ?>
                <th><?php echo $column->mod_emundus_count_applications_columns_title; ?></th>
            <?php endforeach; ?>
            </thead>
            <tbody>
            <?php foreach ($rows as $key => $row) : ?>
                <tr>
                    <td><?php echo $row->mod_emundus_count_applications_rows_title; ?></td>
                    <?php foreach ($columns as $index => $column) : ?>
                        <td><?php echo $row->applications[$index] ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
