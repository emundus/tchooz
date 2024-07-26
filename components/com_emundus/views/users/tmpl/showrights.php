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
    }

    #em-modal-action-table thead tr th:first-child {
        border-top-left-radius: 8px;
    }

    #em-modal-action-table thead tr th:last-child {
        border-top-right-radius: 8px;
    }

    #em-modal-action-table .em-actions-table-line td {
        padding: 12px;
    }

    #em-modal-action-table .em-actions-table-line td.action {
        text-align: center;
        width: 10vw;
    }
    tr.em-actions-table-line td:nth-child(2) {
        border-right: solid 2px var(--neutral-800);
    }
    thead tr th:nth-child(2) {
        border-right: solid 2px var(--neutral-800);
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
                    <table id="em-modal-action-table" class="tw-mt-2 table table-hover em-add-group-right-table"
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

