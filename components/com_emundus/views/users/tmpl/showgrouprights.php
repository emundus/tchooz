<?php
/**
 * User: brivalland
 * Date: 24/09/14
 * Time: 17:14
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

?>
<style>
    span:hover {
        cursor: pointer;
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

<h1><?= Text::_('COM_EMUNDUS_GROUPS_SHOW_RIGHTS'); ?></h1>
<div class="tw-mt-1"><?= Text::_('COM_EMUNDUS_GROUPS_SHOW_RIGHTS_INTRO'); ?></div>

<?php foreach ($this->groups as $k => $g) : ?>
    <fieldset id="<?= $k; ?>" class="em-showgroupright tw-mt-4">
        <h2>
			<?php echo Text::_('COM_EMUNDUS_GROUPS_GROUP_NAME') . ' : ' . $g['label']; ?>
        </h2>

		<?php if (!empty($g['acl'])) : ?>
        <div id="em-div-modal-action-table">
            <table id="em-modal-action-table" class="tw-mb-0 table table-hover em-showgroupright-table"
                   style="color:black !important;">
                <thead>
                <tr>
                    <th></th>
                    <th>
                        <label for="c-check-all"><?= Text::_('COM_EMUNDUS_ACCESS_CREATE'); ?></label>
                    </th>
                    <th>
                        <label for="r-check-all"><?= Text::_('COM_EMUNDUS_ACCESS_RETRIEVE'); ?></label>
                    </th>
                    <th>
                        <label for="u-check-all"><?= Text::_('COM_EMUNDUS_ACCESS_UPDATE'); ?></label>
                    </th>
                    <th>
                        <label for="d-check-all"><?= Text::_('COM_EMUNDUS_ACTIONS_DELETE'); ?></label>
                    </th>
                </tr>
                </thead>
                <tbody>
				<?php
				foreach ($g['acl'] as $l => $action) :?>

                    <tr class="em-actions-table-line" id="<?= $action['id']; ?>">
                        <td id="<?= $action['id']; ?>">
                            <span><?= Text::_(strtoupper($action['label'])); ?></span>
                            <?php if (!empty(Text::_($action['action_description']))) : ?>
                                <span class="material-icons-outlined !tw-text-lg" style="vertical-align: middle" onclick="displayHelpText('<?php echo Text::_($action['action_description']); ?>')">help_outline</span>
                            <?php endif; ?>
                        </td>
						<?php if ($action['is_c'] == 1) : ?>
                            <td action="c" class="action">
								<?php if ($action['c'] == 1) : ?>
                                    <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
								<?php else : ?>
                                    <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
								<?php endif; ?>
                            </td>
						<?php else : ?>
                            <td></td>
						<?php endif; ?>

						<?php if ($action['is_r'] == 1) : ?>
                            <td action="r" class="action">
								<?php if ($action['r'] == 1) : ?>
                                    <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
								<?php else : ?>
                                    <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
								<?php endif; ?>
                            </td>
						<?php else : ?>
                            <td></td>
						<?php endif; ?>

						<?php if ($action['is_u'] == 1) : ?>
                            <td action="u" class="action">
								<?php if ($action['u'] == 1) : ?>
                                    <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
								<?php else : ?>
                                    <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
								<?php endif; ?>
                            </td>
						<?php else: ?>
                            <td></td>
						<?php endif; ?>

						<?php if ($action['is_d'] == 1) : ?>
                            <td action="d" class="action">
								<?php if ($action['d'] == 1) : ?>
                                    <span class="glyphicon glyphicon-ok" style="color: #00c500"></span>
								<?php else : ?>
                                    <span class="glyphicon glyphicon-ban-circle" style="color: #ff0000"></span>
								<?php endif; ?>
                            </td>
						<?php else : ?>
                            <td></td>
						<?php endif; ?>

                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
		<?php endif; ?>
	    <?php if (!empty($g['progs'])) : ?>
        <hr>
            <div class="tw-mt-2">
                <h3 class="tw-mb-2"><?= Text::_('COM_EMUNDUS_GROUPS_PROGRAM'); ?></h3>
                <ul>
			    <?php foreach ($g['progs'] as $p) : ?>
                    <li><?= $p['label']; ?></li>
			    <?php endforeach; ?>
                </ul>
            </div>
	    <?php endif; ?>
		<?php if (!empty($this->users)) : ?>
            <hr>
            <div class="tw-mt-2">
                <h3 class="tw-mb-2"><?= Text::_('COM_EMUNDUS_USERS_GROUP'); ?></h3>
                <ul>
				<?php foreach ($this->users as $user) : ?>
                    <li><?= ucwords($user['firstname']) . ' ' . strtoupper($user['lastname']); ?></li>
				<?php endforeach; ?>
                </ul>
            </div>
		<?php endif; ?>
        <div class="modal-footer">
            <button type="button" class="btn tw-btn-primary em-w-auto"
                    onclick="history.go(-1)"><?php echo Text::_('COM_EMUNDUS_OK'); ?></button>
        </div>
    </fieldset>
<?php endforeach; ?>

<script type="text/javascript">
    var $ = jQuery.noConflict();
    var itemId = <?= $this->itemId; ?>;

    $(document).ready(function () {
        $('.action').click(function () {
            var id = $(this).parent('tr').attr('id');
            var action = $(this).attr('action');
            var mclass = 'glyphicon-ok';
            var value = 0;

            if ($('#' + id + ' td[action="' + action + '"] img').is(':visible')) {
                return false;
            }
            if ($('#' + id + ' td[action="' + action + '"] span').hasClass('glyphicon-ok')) {
                mclass = 'glyphicon-ban-circle';
                value = 0;
            } else value = 1;
            $('#' + id + ' td[action="' + action + '"]').html('<img alt="loading" src="media/com_emundus/images/icones/loading.gif"></img>');

            $.ajax({
                type: 'post',
                url: '<?php echo Route::_('index.php?option=com_emundus&controller=users&task=setgrouprights&format=raw', true); ?>',
                dataType: 'json',
                data: {
                    id: $(this).parent('tr').attr('id'),
                    action: $(this).attr('action'),
                    value: value
                },
                success: function (result) {
                    if (result.status)
                        $('#' + id + ' td[action="' + action + '"]').html('<span class="glyphicon ' + mclass + '" style="color: #01ADE3"></span>')
                },
                error: function () {
                    $('#' + id + ' td[action="' + action + '"]').html('')
                }
            })
        })
    });

    function displayHelpText(desc) {
        Swal.fire({
            title: 'Description',
            text: desc,
            confirmButtonText: "<?php echo Text::_('COM_EMUNDUS_OK'); ?>",
            customClass: {
                title: 'em-swal-title',
                confirmButton: 'em-swal-confirm-button',
                actions: 'em-swal-single-action'
            }
        })
    }
</script>
