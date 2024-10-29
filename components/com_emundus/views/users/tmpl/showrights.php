<?php
/**
 * Created by PhpStorm.
 * User: yoan
 * Date: 19/09/14
 * Time: 17:14
 */

use Joomla\CMS\Language\Text;

?>
<style>
    form {
        margin: 0;
    }

    #em-modal-action-table thead tr th {
        text-align: center;
        padding-top: 20px;
        padding-bottom: 20px;
    }

    #em-modal-action-table thead tr th:first-child {
        border-top-left-radius: 16px;
    }

    #em-modal-action-table thead tr th:last-child {
        border-top-right-radius: 16px;
    }

    #em-modal-action-table thead tr th label {
        font-weight: 600;
    }

    #em-modal-action-table .em-actions-table-line td {
        padding: 12px;
    }

    #em-modal-action-table .em-actions-table-line td.action {
        text-align: center;
        width: 10vw;
    }
    tr.em-actions-table-line td:nth-child(2) {
        border-right: 1px solid var(--neutral-400);
    }
    thead tr th:nth-child(2) {
        border-right: 1px solid var(--neutral-400);
    }
    .table-hover tbody tr:hover td,
    .table-hover tbody tr:nth-child(2n):hover > td{
        background: linear-gradient(0deg, hsl(from var(--em-profile-color) h s l / 15%) 0%, hsl(from var(--em-profile-color) h s l / 15%) 100%), #FFF !important;
    }
    .table-hover tbody tr:nth-child(2n) > td,
    .table-hover tbody tr:nth-child(2n) > th {
        background: #FFFFFF !important;
    }
    #em-div-modal-action-table {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #EDEDED;
        box-shadow: var(--em-box-shadow-x-1) var(--em-box-shadow-y-1) var(--em-box-shadow-blur-1) var(--em-box-shadow-color-1), var(--em-box-shadow-x-2) var(--em-box-shadow-y-2) var(--em-box-shadow-blur-2) var(--em-box-shadow-color-2), var(--em-box-shadow-x-3) var(--em-box-shadow-y-3) var(--em-box-shadow-blur-3) var(--em-box-shadow-color-3);
        margin-top: 16px;
    }
</style>
<form action="index.php?option=com_emundus&controller=users&task=addgroup" id="em-add-group" class="em-add-group"
      role="form" method="post">
	<?php
	if (empty($this->groups)) {
		echo Text::_('COM_EMUNDUS_GROUPS_NO_GROUP');
	}
	else {
		?>
		<?php foreach ($this->groups as $k => $g): ?>
            <fieldset id="<?php echo $k ?>" class="em-add-group-right">
                <h2>
	                <?php echo Text::_('COM_EMUNDUS_GROUPS_GROUP_NAME') . ' : ' . $g['label']; ?>
                </h2>

				<?php if (!empty($g['acl'])): ?>
                <div id="em-div-modal-action-table">
                    <table id="em-modal-action-table" class="tw-mb-0 table table-hover em-add-group-right-table"
                           style="color:black !important;">
                        <thead>
                        <tr>
                            <th></th>
                            <th>
                                <label for="c-check-all"><?php echo Text::_('COM_EMUNDUS_ACCESS_CREATE') ?></label>
                            </th>
                            <th>
                                <label for="r-check-all"><?php echo Text::_('COM_EMUNDUS_ACCESS_RETRIEVE') ?></label>
                            </th>
                            <th>
                                <label for="u-check-all"><?php echo Text::_('COM_EMUNDUS_ACCESS_UPDATE') ?></label>
                            </th>
                            <th>
                                <label for="d-check-all"><?php echo Text::_('COM_EMUNDUS_ACTIONS_DELETE') ?></label>
                            </th>
                        </tr>
                        </thead>
                        <tbody size="<?php echo count($g['acl']) ?>">
						<?php foreach ($g['acl'] as $l => $action): ?>

                            <tr class="em-actions-table-line">
                                <td id="<?php echo $action['id'] ?>">
                                    <?php echo Text::_(strtoupper($action['label'])) ?>
                                </td>
                                <td class="action">
									<?php if ($action['c'] == 1): ?>
                                        <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
									<?php else: ?>
                                        <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
									<?php endif ?>
                                </td>
                                <td class="action">
									<?php if ($action['r'] == 1): ?>
                                        <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
									<?php else: ?>
                                        <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
									<?php endif ?>
                                </td>
                                <td class="action">
									<?php if ($action['u'] == 1): ?>
                                        <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
									<?php else: ?>
                                        <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
									<?php endif ?>
                                </td>
                                <td class="action">
									<?php if ($action['d'] == 1): ?>
                                        <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
									<?php else: ?>
                                        <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
									<?php endif ?>
                                </td>

                            </tr>
						<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
				<?php endif; ?>

	            <?php if (!empty($g['progs'])) : ?>
                <hr>
                    <div class="tw-mt-2"">
                        <h3 class="tw-mb-2"><?php echo Text::_('COM_EMUNDUS_GROUPS_PROGRAM') ?></h3>
                <ul>
			            <?php foreach ($g['progs'] as $p): ?>
                            <li><?php echo $p['label'] ?></li>
			            <?php endforeach; ?>
                </ul>
                    </div>
	            <?php endif; ?>
            </fieldset>
		<?php endforeach; ?>
	<?php }; ?>

	<?php
	echo '<script type="text/javascript">
    var $ = jQuery.noConflict();
	$(document).ready(function() {
	    $("#can-val").hide();
	});
	
	$(document).on("click", ".close", function() {
	    $("#can-val").show();
	});
</script>'
	?>

</form>

