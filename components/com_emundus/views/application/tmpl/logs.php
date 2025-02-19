<?php
/**
 * Created by PhpStorm.
 * User: brivalland
 * Date: 13/11/14
 * Time: 11:24
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (version_compare(JVERSION, '4.0', '>')) {
	Factory::getApplication()->getSession()->set('application_layout', 'logs');
}
else {
	Factory::getSession()->set('application_layout', 'logs');
}

?>

<style>
    .widget .panel-body {
        padding: 0;
    }

    .widget .list-group {
        margin-bottom: 0;
    }

    .widget .panel-title {
        display: inline
    }

    .widget .log-info {
        margin: 1.5rem;
    }
</style>

<div class="logs">
    <input type="hidden" id="fnum_hidden" value="<?php echo $this->fnum ?>">

    <div class="row">
        <div class="panel panel-default widget em-container-logs <?php if ($this->euser->applicant == 1) : ?>tw-bg-transparent<?php else : ?>tw-bg-neutral-100<?php endif; ?>">

			<?php if ($this->euser->applicant == 0) : ?>
                <div class="panel-heading em-container-logs-heading !tw-bg-profile-full">

                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-list"></span>
						<?php echo Text::_('COM_EMUNDUS_ACCESS_LOGS'); ?>
                    </h3>

                    <div class="btn-group pull-right">
                        <button id="em-prev-file" class="btn btn-info btn-xxl"><span
                                    class="material-symbols-outlined">arrow_back</span></button>
                        <button id="em-next-file" class="btn btn-info btn-xxl"><span
                                    class="material-symbols-outlined">arrow_forward</span></button>
                    </div>

                </div>
			<?php endif; ?>

            <br class="panel-body em-container-logs-body">

	        <?php if ($this->euser->applicant == 0) : ?>
                <div class="view-type tw-flex tw-items-center tw-justify-end tw-mr-4 ">
                    <span style="padding: 4px;border-radius: calc(var(--em-default-br)/2);display: flex;height: 38px;width: 38px;align-items: center;justify-content: center; background: var(--neutral-0);"
                          id="table_view_button"
                          class="material-symbols-outlined tw-ml-2 tw-cursor-pointer active em-main-500-color em-border-main-500"
                    >dehaze</span>
                    <span style="padding: 4px;border-radius: calc(var(--em-default-br)/2);display: flex;height: 38px;width: 38px;align-items: center;justify-content: center; background: var(--neutral-0);"
                          id="grid_view_button"
                          class="material-symbols-outlined tw-ml-2 tw-cursor-pointer em-neutral-600-color em-border-neutral-600"
                    >grid_view</span>
                </div>
            <?php endif; ?>

			<?php if (!empty($this->fileLogs)) : ?>
				<?php if ($this->euser->applicant == 0) : ?>
                    <div id="filters-logs" class="em-flex-row">
                        <!-- add CRUD filters (multi-chosen) -->
                        <div id="actions" class="em-w-33 em-mr-16">
                            <label for="crud-logs-label"
                                   id="crud-logs-hint"><?= Text::_('COM_EMUNDUS_CRUD_FILTER_LABEL'); ?></label>
                            <select name="crud-logs-select" id="crud-logs" class="chzn-select em-w-100" multiple
                                    data-placeholder="<?= Text::_('COM_EMUNDUS_CRUD_FILTER_PLACEHOLDER'); ?>">
                                <option value="r"><?= Text::_('COM_EMUNDUS_LOG_READ_TYPE'); ?></option>
                                <option value="c"><?= Text::_('COM_EMUNDUS_LOG_CREATE_TYPE'); ?></option>
                                <option value="u"><?= Text::_('COM_EMUNDUS_LOG_UPDATE_TYPE'); ?></option>
                                <option value="d"><?= Text::_('COM_EMUNDUS_LOG_DELETE_TYPE'); ?></option>
                            </select>
                        </div>
                        <div id="types" class="em-w-33 em-mr-16">
                            <label for="actions-logs-label"
                                   id="actions-logs-hint"><?= Text::_('COM_EMUNDUS_TYPE_FILTER_LABEL'); ?></label>
                            <select name="type-logs-select" id="type-logs" class="chzn-select em-w-100" multiple
                                    data-placeholder="<?= Text::_('COM_EMUNDUS_TYPE_FILTER_PLACEHOLDER'); ?>"></select>
                        </div>
                        <div id="actors" class="em-w-33 em-mr-16">
                            <label for="actors-logs-label"
                                   id="actors-logs-hint"><?= Text::_('COM_EMUNDUS_ACTORS_FILTER_LABEL'); ?></label>
                            <select name="actor-logs-select" id="actors-logs" class="chzn-select em-w-100" multiple
                                    data-placeholder="<?= Text::_('COM_EMUNDUS_ACTOR_FILTER_PLACEHOLDER'); ?>"></select>
                        </div>
                    </div>

                    <div id="apply-filters" class="em-flex-row-justify-end">
                        <button id="log-reset-filter-btn"
                                class="em-w-auto tw-btn-cancel em-mt-8 em-mb-8 em-ml-8 em-mr-8">
							<?= Text::_('COM_EMUNDUS_LOGS_RESET_FILTER') ?>
                        </button>
                        <button id="log-filter-btn"
                                class="em-w-auto tw-btn-primary em-mt-8 em-mb-8 em-ml-8 em-mr-16">
							<?= Text::_('COM_EMUNDUS_LOGS_FILTER') ?>
                        </button>
                    </div>

                    <div id="export-logs" class="em-flex-row-justify-end">
                        <button id="log-export-btn"
                                class="em-w-auto tw-btn-cancel em-mt-8 em-mb-8 em-ml-8 em-mr-16"
                                onclick="exportLogs(<?= "'" . $this->fnum . "'" ?>)">
                            <span class="material-symbols-outlined em-mr-8">file_upload</span>
							<?= Text::_('COM_EMUNDUS_LOGS_EXPORT') ?>
                        </button>
                    </div>
				<?php endif; ?>

                <div class="<?php if ($this->euser->applicant == 1) : ?>!tw-pl-0<?php endif; ?> logs_grids <?php if ($this->euser->applicant == 0) : ?>tw-pr-1 tw-hidden<?php endif; ?>">
                    <div id="logs_list_grid" class="tw-flex tw-flex-col tw-gap-3">
                        <?php foreach ($this->fileLogs as $log) : ?>
                            <div class="tw-border-1 tw-border-neutral-300 tw-shadow-sm tw-py-4 tw-px-6 tw-bg-white tw-rounded-lg">
                                <div class="tw-flex tw-items-center">
                                    <span class="material-symbols-outlined"
                                          style="font-size: 48px"
                                          alt="<?php echo Text::_('PROFILE_ICON_ALT') ?>">
                                        account_circle
                                    </span>
                                    <div class="tw-ml-3">
                                        <span class="tw-text-sm tw-text-neutral-600"><?= $log->date; ?></span>
                                        <p><?= $log->firstname . ' ' . $log->lastname; ?></p>
                                    </div>
                                </div>
                                <div class="tw-mt-3">
                                    <p class="tw-font-bold"><?= $log->details['action_category']; ?></p>
                                    <p><?= $log->details['action_name']; ?></p>
                                    <p><?= $log->details['action_details']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>

                <table class="table table-hover logs_table <?php if ($this->euser->applicant == 1) : ?>tw-hidden<?php endif; ?>">
                    <caption class="hidden"><?= Text::_('COM_EMUNDUS_LOGS_CAPTION'); ?></caption>
                    <thead>
                    <tr>
                        <th id="date"><?= Text::_('DATE'); ?></th>
                        <th id="ip">IP</th>
                        <th id="user"><?= Text::_('USER'); ?></th>
                        <th id="action_category"><?= Text::_('COM_EMUNDUS_LOGS_VIEW_ACTION_CATEGORY'); ?></th>
                        <th id="action_name"><?= Text::_('COM_EMUNDUS_LOGS_VIEW_ACTION'); ?></th>
                        <th id="action_details"><?= Text::_('COM_EMUNDUS_LOGS_VIEW_ACTION_DETAILS'); ?></th>
                    </tr>
                    </thead>
                    <tbody id="logs_list">
					<?php
					foreach ($this->fileLogs as $log) { ?>
                        <tr>
                            <td><?= $log->date; ?></td>
                            <td><?= $log->ip_from; ?></td>
                            <td><?= $log->firstname . ' ' . $log->lastname; ?></td>
                            <td><?= $log->details['action_category']; ?></td>
                            <td><?= $log->details['action_name']; ?></td>
                            <td><?= $log->details['action_details']; ?></td>
                        </tr>
					<?php } ?>
                    </tbody>
                </table>
				<?php
				if (count($this->fileLogs) >= 100) : ?>
                    <div class="log-info show-more">
                        <button type="button" class="btn btn-info btn-xs" id="show-more">Afficher plus</button>
                    </div>
				<?php endif; ?>
			<?php endif; ?>

            <div <?php if (!empty($this->fileLogs)) : ?>class="tw-hidden"<?php endif; ?> id="no-results-block">
                <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
                <p class="tw-text-center"><?php echo Text::_("COM_EMUNDUS_NO_LOGS_FILTER_FOUND"); ?></p>
                <button id="log-reset-filter-link" onclick="resetFilters();" class="em-font-size-16 em-profile-color em-text-underline tw-w-full tw-block tw-text-center tw-pb-4">Réinitialiser les filtres</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var offset = 100;

    $('#crud-logs').chosen({width: '100%'});
    $('#type-logs').chosen({width: '100%'});
    $('#actors-logs').chosen({width: '100%'});

    $(document).ready(function () {
        $.ajax({
            method: "post",
            url: "index.php?option=com_emundus&controller=files&task=getalllogactions",
            dataType: 'json',
            success: function (results) {
                if (results.status) {
                    const typeLogs = $('#type-logs');

                    results.data.forEach(log => {
                        typeLogs.append('<option value="' + log.id + '">' + Joomla.Text._(log.label) + '</option>');           /// append data
                        typeLogs.trigger("liszt:updated");
                    })
                } else {
                    $('#filters-logs').remove();
                    $('#log-filter-btn').remove();
                    $('.em-container-logs-heading').after('<b style="color:red">' + Joomla.Text._("COM_EMUNDUS_NO_ACTION_FOUND") + '</b>');
                }
            }, error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR.responseText, textStatus, errorThrown);
            }
        });

        /* show hint */
        $('#crud-logs-hint').on('hover', function () {
            $(this).css('cursor', 'pointer').attr('title', Joomla.Text._("COM_EMUNDUS_CRUD_LOG_FILTER_HINT"));
        });

        $('#actions-logs-hint').on('hover', function () {
            $(this).css('cursor', 'pointer').attr('title', Joomla.Text._("COM_EMUNDUS_TYPES_LOG_FILTER_HINT"));
        });

        $('#actors-logs-hint').on('hover', function () {
            $(this).css('cursor', 'pointer').attr('title', Joomla.Text._("COM_EMUNDUS_ACTOR_LOG_FILTER_HINT"));
        });

        $.ajax({
            type: 'post',
            url: 'index.php?option=com_emundus&controller=files&task=getuserslogbyfnum',
            data: ({
                fnum: $('#fnum_hidden').attr('value'),
            }),
            dataType: 'json',
            success: function (results) {
                if (results.status) {
                    const actorsLog = $('#actors-logs');

                    results.data.forEach((user) => {
                        actorsLog.append('<option value="' + user.uid + '">' + user.name + '</option>');           /// append data
                        actorsLog.trigger("liszt:updated");
                    });
                } else {
                    $('#actors').remove();
                    $('#types').after('<br><p style="color:red">' + Joomla.Text._("COM_EMUNDUS_NO_LOG_USERS_FOUND") + '</p></br>');
                }

            }, error: function (xhr, status, error) {
                console.log(xhr.responseText, status, error);
            }
        });

        $('#log-filter-btn').on('click', function () {
            let crud = $('#crud-logs').val();

            if (!crud) {
                crud = ['c', 'r', 'u', 'd'];
            }

            const types = $('#type-logs').val();
            const persons = $('#actors-logs').val();

            $.ajax({
                type: 'post',
                url: 'index.php?option=com_emundus&controller=files&task=getactionsonfnum',
                data: ({
                    fnum: $('#fnum_hidden').attr('value'),
                    crud: crud,
                    types: types,
                    persons: persons,
                }),
                dataType: 'json',
                success: function (results) {
                    $('#log-count-results').remove();

                    // add loading icon
                    const logList = $('#logs_list');
                    const logListGrid = $('#logs_list_grid');
                    logList.empty();
                    logListGrid.empty();

                    // remove the error-message (if any)
                    if ($('#error-message').length > 0) {
                        $('#error-message').remove();
                    }

                    if (results.status) {
                        $('#no-results-block').add('tw-hidden');
                        $('#log-export-btn').show();
                        $('#loading').remove();

                        let tr = '';
                        let grid = '';
                        if (results.res.length < 100) {
                            $('.show-more').hide();
                        }
                        for (let i = 0; i < results.res.length; i++) {
                            tr = '<tr>' +
                                '<td>' + results.res[i].date + '</td>' +
                                '<td>' + results.res[i].ip_from + '</td>' +
                                '<td>' + results.res[i].firstname + ' ' + results.res[i].lastname + '</td>' +
                                '<td>' + results.details[i].action_category + '</td>' +
                                '<td>' + results.details[i].action_name + '</td>' +
                                '<td>' + results.details[i].action_details + '</td>' +
                                '</tr>'
                            logList.append(tr);

                            // Grid
                            grid = '<div class="tw-border-1 tw-border-neutral-300 tw-shadow-sm tw-py-4 tw-px-6 tw-bg-white tw-rounded-lg"> ' +
                                '<div class="tw-flex tw-items-center"> ' +
                                '<span class="material-symbols-outlined" style="font-size: 48px" alt="'+Joomla.Text._('PROFILE_ICON_ALT')+'">account_circle</span>' +
                                '<div class="tw-ml-3"> ' +
                                '<span class="tw-text-sm tw-text-neutral-600">'+results.res[i].date+'</span> ' +
                                '<p>'+ results.res[i].firstname + ' ' + results.res[i].lastname +'</p> ' +
                                '</div> ' +
                                '</div> ' +
                                '<div class="tw-mt-3"> ' +
                                '<p class="tw-font-bold">'+ results.details[i].action_category +'</p> ' +
                                '<p>'+ results.details[i].action_name +'</p> ' +
                                '<p>'+ results.details[i].action_details +'</p> ' +
                                '</div> ' +
                                '</div>';
                            logListGrid.append(grid);
                        }

                    } else {
                        $('.show-more').hide();
                        $('#log-export-btn').hide();
                        $('#logs_list').empty();
                        $('#no-results-block').removeClass('tw-hidden');
                    }
                }, error: function (xhr, status, error) {
                    console.log(xhr, status, error);
                }
            })
        })
    });

    $(document).on('click', '#show-more', function (e) {
        if (e.handle === true) {
            e.handle = false;
            const fnum = "<?php echo $this->fnum; ?>";
            const crud = $('#crud-logs').val();
            const types = $('#type-logs').val();
            const persons = $('#actors-logs').val();

            $.ajax({
                type: 'POST',
                url: 'index.php?option=com_emundus&controller=' + $('#view').val() + '&task=getactionsonfnum',
                dataType: 'json',
                data: ({
                    fnum: fnum,
                    offset: offset,
                    crud: crud,
                    types: types,
                    persons: persons
                }),
                success: function (result) {
                    if (result.status) {
                        let tr = ''
                        if (result.res.length < 100) {
                            $('.show-more').hide();
                        }
                        for (let i = 0; i < result.res.length; i++) {
                            tr = '<tr>' +
                                '<td>' + result.res[i].date + '</td>' +
                                '<td>' + result.res[i].ip_from + '</td>' +
                                '<td>' + result.res[i].firstname + ' ' + result.res[i].lastname + '</td>' +
                                '<td>' + result.details[i].action_category + '</td>' +
                                '<td>' + result.details[i].action_name + '</td>' +
                                '<td>' + result.details[i].action_details + '</td>' +
                                '</tr>'
                            $('#logs_list').append(tr);
                        }
                        offset += 100;
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR.responseText);
                }
            });
        }
    });

    function exportLogs(fnum) {
        xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?option=com_emundus&controller=files&task=exportLogs', true);

        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    const response = JSON.parse(xhr.response);

                    if (response) {
                        let file_link = document.createElement('a');
                        file_link.id = 'file-link';
                        file_link.href = response;
                        file_link.download = fnum + '_logs.csv';
                        file_link.innerText = Joomla.Text._('COM_EMUNDUS_LOGS_DOWNLOAD');
                        file_link.click();
                    } else {
                        $('#log-export-btn').hide();
                        Swal.fire({
                            title: Joomla.Text._('COM_EMUNDUS_LOGS_DOWNLOAD_ERROR'),
                            type: 'error',
                            confirmButtonText: Joomla.Text._('OK')
                        });
                    }
                } else {
                    alert('Error: ' + xhr.status);
                }
            }
        };

        let body = new FormData();

        const crud = $('#crud-logs').val();
        const types = $('#type-logs').val();
        const persons = $('#actors-logs').val();

        body.append('fnum', String(fnum));
        body.append('crud', JSON.stringify(crud));
        body.append('types', JSON.stringify(types));
        body.append('persons', JSON.stringify(persons));

        xhr.send(body);
    }

    document.querySelector('#log-reset-filter-btn').addEventListener('click', function () {
        resetFilters();
    });

    /*document.querySelector('#log-reset-filter-link').addEventListener('click', function () {
        resetFilters();
    });*/

    document.querySelector('#grid_view_button').addEventListener('click', function () {
        changeViewType('logs_grids');
    });

    document.querySelector('#table_view_button').addEventListener('click', function () {
        changeViewType('logs_table');
    });

    function resetFilters() {
        const log_link = document.querySelector('#em-appli-menu a[href*="layout=logs"]');
        if (log_link) {
            log_link.click();
        }
    }

    function changeViewType(view) {
        let grid_view = document.querySelector('.logs_grids');
        let table_view = document.querySelector('.logs_table');

        let grid_button = document.querySelector('#grid_view_button');
        let table_button = document.querySelector('#table_view_button');

        if(view === 'logs_grids') {
            grid_view.classList.remove('tw-hidden');
            table_view.classList.add('tw-hidden');

            grid_button.classList.add('em-main-500-color','active','em-border-main-500');
            grid_button.classList.remove('em-neutral-600-color','em-border-neutral-600');

            table_button.classList.add('em-neutral-600-color','em-border-neutral-600');
            table_button.classList.remove('em-main-500-color','active','em-border-main-500');
        } else {
            grid_view.classList.add('tw-hidden');
            table_view.classList.remove('tw-hidden');

            table_button.classList.add('em-main-500-color','active','em-border-main-500');
            table_button.classList.remove('em-neutral-600-color','em-border-neutral-600');

            grid_button.classList.add('em-neutral-600-color','em-border-neutral-600');
            grid_button.classList.remove('em-main-500-color','active','em-border-main-500');
        }
    }
</script>

<style>
    .search-field input {
        font-size: small !important;
        height: 20px !important;
    }

    #filters-logs, #export-logs {
        padding-bottom: 8px;
    }

    #apply-filters {
        padding-bottom: 0;
    }

    .chzn-container.chzn-container-multi {
        margin-top: 6px;
     }
</style>
