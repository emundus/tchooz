<?php
use Joomla\CMS\Language\Text;defined('_JEXEC') or die;
?>
<style>
    .mod-emundus-btob th, .mod-emundus-btob td {
        text-align: center;
        border: 1px solid #DCE2E9;
    }
    .mod-emundus-btob td:first-child {
        background: #eceff3;
    }
</style>
<div class="mod-emundus-btob tw-bg-white tw-rounded-applicant tw-p-4 tw-border tw-border-neutral-300 tw-mb-4">
    <h1 class="tw-text-center">
		<?php echo Text::_('MOD_EMUNDUS_BTOB_TITLE'); ?>
    </h1>

    <table style="table-layout: fixed" class="tw-mt-6">
        <thead>
        <th></th>
		<?php foreach ($statuses as $key => $status) : ?>
            <th><?php echo $status['value']; ?></th>
		<?php endforeach; ?>
        <th><?php echo Text::_('MOD_EMUNDUS_BTOB_TOTAL_PRICE'); ?></th>
        </thead>
        <tbody>
		<?php foreach ($campaigns as $campaign) : ?>
            <tr>
                <td><?php echo $campaign['label']; ?></td>
				<?php foreach ($statuses as $key => $status) : ?>
                    <td>
						<?php if (isset($campaign['status'][$status['step']])) : ?>
							<?php echo $campaign['status'][$status['step']]; ?>
						<?php else : ?>
                            -
						<?php endif; ?>
                    </td>
				<?php endforeach; ?>
                <td><?php echo $campaign['price']; ?></td>
            </tr>
		<?php endforeach; ?>
        </tbody>
    </table>
</div>
