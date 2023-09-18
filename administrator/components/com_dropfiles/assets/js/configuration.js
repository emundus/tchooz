jQuery(document).ready(function ($) {
    // Configuration
    var dropfiles_configuration = {
        init: function () {
            $(window).on('resize scroll', function () {
                // Get position of subhead-fixed
                if ($(window).scrollTop() >= 53) {
                    $('.ju-left-panel').addClass('ju-tab-position-fixed');
                    $('.ju-left-panel').prop('style', false);
                } else {
                    if ($(window).scrollTop() > 0 && $(window).scrollTop() <= 53) {
                        $('.ju-left-panel').css('top', 140 - $(window).scrollTop());
                    } else {
                        $('.ju-left-panel').removeClass('ju-tab-position-fixed');
                        $('.ju-left-panel').prop('style', false);
                    }
                }
            });

            $('#ju-message-container .close').on('click', function () {
                $("#ju-message-container").css({paddingTop: 0})
            });

            $('.ju-left-panel .parent-tabs > .link-tab').on('click', function () {
                var open = false;
                if ($(this).parent('.parent-tabs').hasClass('expanded')) {
                    open = true;
                }
                $('.parent-tabs.expanded').removeClass('expanded');
                if (open === true) {
                    $(this).parent('.parent-tabs').addClass('expanded');
                }
                $(this).parent('.parent-tabs').toggleClass('expanded');
            });

            dropfiles_configuration.showContentFromMenuTabs();

            dropfiles_configuration.showContentFromContentTabs();

            $('input#jform_newtheme').attr('placeholder', Joomla.JText._('COM_DROPFILES_CONFIGURATION_JFORM_NEWTHEME_PLACEHOLDER', 'Type here'));

            $('div#notification .ju-settings-option').each(function () {
                if(!$(this).find('.ju-switch-button').length) {
                    $(this).addClass('notification-full-width');
                }
            });

            $('div#cloud_connection .ju-settings-option').each(function () {
                $(this).addClass('cloud-full-width');
            });

            $('div#cloud_onedrive .ju-settings-option').each(function () {
                $(this).addClass('cloud-full-width');
            });

            $('div#cloud_dropbox .ju-settings-option').each(function () {
                $(this).addClass('cloud-full-width');
            });

            $('input#jform_onedriveBusinessKey').parents('.ju-settings-option').addClass('cloud-full-width');
            $('input#jform_onedriveBusinessSecret').parents('.ju-settings-option').addClass('cloud-full-width');
            $('select#jform_onedriveBusinessSyncMethod').parents('.ju-settings-option').addClass('cloud-full-width');
            $('select#jform_onedriveBusinessSyncTime').parents('.ju-settings-option').addClass('cloud-full-width');
            $('.ju-settings-option.jform_onedriveBusinessConnectMethod').addClass('cloud-full-width');
            $('.ju-settings-option.jform_onedrive_business_document').addClass('cloud-full-width');
            $('.ju-settings-option.jform_onedrivebusinessbtn').addClass('cloud-full-width');
            $('.ju-settings-option.jform_onedrive_business_cron_task_url').addClass('cloud-full-width');

            dropfiles_configuration.setValueImportSettings();

            dropfiles_configuration.setCloudConnectBtnStatus();

            dropfiles_configuration.initConfigSet();

            dropfiles_configuration.showThelastConfigWorking();

            dropfiles_configuration.showHelperContent();

            $(document).on('change', '#jform_sync_method', dropfiles_configuration.displayTheCrontask);
            $(document).on('change', '#jform_dropbox_sync_method', dropfiles_configuration.displayTheCrontask);
            $(document).on('change', '#jform_onedriveSyncMethod', dropfiles_configuration.displayTheCrontask);
            $(document).on('change', '#jform_onedriveBusinessSyncMethod', dropfiles_configuration.displayTheCrontask);

            dropfiles_configuration.displayTheCrontask();

            $('#docman_import_button').on('click', function (e) {

                e.preventDefault();
                var cat = $('#jform_doccat').val();
                if (cat == 0 ) {
                    alert('please select a category to import');
                    return false;
                } else {
                    $('#docman_import_button').attr('disabled', true);
                    $('#docman_import_button').html('Importing');
                    $.ajax({
                        url: "index.php?option=com_dropfiles&task=config.docimport",
                        type: "POST",
                        dataType : 'json',
                        data : {doccat : cat}
                    }).done(function(res){
                        $('#docman_import_button').html('Done');
                        alert('Docman import run with success!');
                    });
                }
            });

            $('#jdownloads_import_button').on('click', function (e) {

                e.preventDefault();
                var cat = $('#jform_jdowncat').val();
                if (cat == 0 ) {
                    alert('please select a category to import');
                    return false;
                } else {
                    $('#jdownloads_import_button').attr('disabled', true);
                    $('#jdownloads_import_button').html('Importing');
                    $.ajax({
                        url: "index.php?option=com_dropfiles&task=config.downimport",
                        type: "POST",
                        dataType : 'json',
                        data : {doccat : cat}
                    }).done(function(res){
                        $('#jdownloads_import_button').html('Done');
                        alert('Jdownloads import run with success!');
                    });
                }
            });

            $('#edoc_import_button').on('click', function (e) {

                e.preventDefault();
                var cat = $('#jformedocmancategory').val();
                if (cat == 0 ) {
                    alert('please select a category to import');
                    return false;
                } else {
                    $('#edoc_import_button').attr('disabled', true);
                    $('#edoc_import_button').html('Importing');
                    $.ajax({
                        url: "index.php?option=com_dropfiles&task=config.eDocImport",
                        type: "POST",
                        dataType : 'json',
                        data : {doccat : cat}
                    }).done(function(res){
                        $('#edoc_import_button').html('Done');
                        alert('eDocman import run with success!');
                    });
                }
            });

            $('#phocadownload_import_button').on('click', function (e) {

                e.preventDefault();
                var cat = $('#jform_phocadownloadcat').val();
                if (cat == 0 ) {
                    alert('please select a category to import');
                    return false;
                } else {
                    $('#phocadownload_import_button').attr('disabled', true);
                    $('#phocadownload_import_button').html('Importing');
                    $.ajax({
                        url: "index.php?option=com_dropfiles&task=config.phocaDownloadImport",
                        type: "POST",
                        dataType : 'json',
                        data : {phocadownloadcat : cat}
                    }).done(function(res){
                        $('#phocadownload_import_button').html('Done');
                        alert('Phoca download import run with success!');
                    });
                }
            });

            $('#dropfiles_btn_google_changes').on('click', function (e) {
                e.preventDefault();
                var csrfToken = $(e.target).parent().data('csrf');
                Joomla.request({
                    url: "index.php?option=com_dropfiles&task=config.googleStopWatchChanges",
                    type: "POST",
                    headers: {
                        'X-Csrf-Token': csrfToken
                    },
                    onSuccess: function(res, xhr){
                        response = JSON.parse(res);
                        if (response.response === true) {
                            setTimeout(function() {
                                document.location.reload();
                            }, 500);
                        } else {
                            alert('Something wrong! Check Console Tab for details.');
                            console.log(xhr);
                        }
                    },
                    onError: function (xhr) {
                        alert('Something wrong! Check Console Tab for details.');
                        console.log(xhr);
                    }
                });

            });

            $(".exclude-category-select").chosen({
                allow_single_deselect: true,
                width: '100%',
                no_results: "No results"
            });
            // $(document).on('click', '#dropfiles-onedrive-business-connect', dropfiles_configuration.connectOneDriveBusiness);
            // Onedrive Push Notification
            $('#dropfiles-btnpush-onedrive-business').on('click', function(e) {
                e.preventDefault();
                var csrfToken = $(this).data('csrf');
                // Add loading
                $('#dropfiles-btnpush-onedrive-business span[class^="icon"]').addClass('icon-loading').addClass('dropfiles_loading');
                Joomla.request({
                    url: "index.php?option=com_dropfiles&task=config.onedriveBusinessStopWatchChanges",
                    type: "POST",
                    headers: {
                        'X-Csrf-Token': csrfToken
                    },
                    onSuccess: function(res, xhr){
                        response = JSON.parse(res);
                        if (response.response === true) {
                            setTimeout(function() {
                                document.location.reload();
                            }, 500);
                        } else {
                            alert('Something wrong! Check Console Tab for details.');
                            console.log(xhr);
                        }
                        // Remove loading
                        $('#dropfiles-btnpush-onedrive-business span[class^="icon"]').removeClass('icon-loading').removeClass('dropfiles_loading');
                    },
                    onError: function (xhr) {
                        // Remove loading
                        $('#dropfiles-btnpush-onedrive-business span[class^="icon"]').removeClass('icon-loading').removeClass('dropfiles_loading');
                        alert('Something wrong! Check Console Tab for details.');
                        console.log(xhr);
                    },
                });
            });
        },
        showContentFromMenuTabs: function () {
            $('.ju-menu-tabs .link-tab').on('click', function () {
                if($(this).parents('.parent-tabs').hasClass('main-settings-list-tab')) {
                    $('#main-settings-top-tabs').show();
                    $('#theme-settings-top-tabs').hide();
                    $('#cloud-settings-top-tabs').hide();
                } else if($(this).parents('.parent-tabs').hasClass('theme-list-tab')) {
                    $('#theme-settings-top-tabs').show();
                    $('#main-settings-top-tabs').hide();
                    $('#cloud-settings-top-tabs').hide();
                } else if($(this).parents('.parent-tabs').hasClass('cloud-connection-tab')) {
                    $('#cloud-settings-top-tabs').show();
                    $('#main-settings-top-tabs').hide();
                    $('#theme-settings-top-tabs').hide();
                } else {
                    $('#main-settings-top-tabs').hide();
                    $('#theme-settings-top-tabs').hide();
                    $('#cloud-settings-top-tabs').hide();
                }
                var menuContentName = $(this).attr('href').replace('#', '');
                $('.ju-right-panel .ju-content-wrapper').each(function () {
                    $(this).hide();
                });
                $('.ju-right-panel #' + menuContentName).show();
                $('.ju-top-tabs .link-tab').each(function () {
                    if($(this).attr('href').replace('#', '') == menuContentName) {
                        $(this).click();
                    }
                });
            });
        },
        showContentFromContentTabs: function () {
            $('.ju-top-tabs .link-tab').on('click', function () {
                var contentName = $(this).attr('href').replace('#', '');
                $('.ju-right-panel .ju-content-wrapper').each(function () {
                    $(this).hide();
                });
                $('.ju-right-panel #' + contentName).show();
                $('.ju-menu-tabs .link-tab').each(function () {
                    if($(this).hasClass('active')) {
                        $(this).removeClass('active');
                    }
                    if($(this).attr('href').replace('#', '') == contentName) {
                        $(this).addClass('active');
                    }
                });
            });
        },
        setValueImportSettings: function () {
            if($('.import-settings-option select').length) {
                $this = $('.import-settings-option select');
                $this.each(function () {
                    $(this).addClass('ju-input');
                });
            }

            if ($('.no-docman').length) {
                $this = $('.jform_doccat > .ju-setting-label');
                var contentlb = Joomla.JText._('COM_DROPFILES_CONFIG_IMPORT_AVAILABLE_DOCMAN_NAME', 'Docman');
                $this.empty();
                $this.append(contentlb);
            }

            if($('.import-settings-option .import-name').length) {
                var mainitem = $('.import-settings-option .import-name');
                mainitem.each(function () {
                    $(this).parents('.import-settings-option').addClass('isInstall');
                });
            }

            if ($('.no-jdownload').length) {
                $this = $('.jform_jdowncat > .ju-setting-label');
                var contentlb = Joomla.JText._('COM_DROPFILES_CONFIG_JDOWN_IMPORT_NAME', 'jDownload');
                $this.empty();
                $this.append(contentlb);
            }

            if ($('.no-edocman').length) {
                $this = $('.jform_edocmancategory > .ju-setting-label');
                var contentlb = Joomla.JText._('COM_DROPFILES_CONFIG_IMPORT_AVAILABLE_EDOCMAN_NAME', 'Edocman');
                $this.empty();
                $this.append(contentlb);
            }
            if ($('.no-phoca').length) {
                $this = $('.jform_phocadownloadcat > .ju-setting-label');
                var contentlb = Joomla.JText._('COM_DROPFILES_CONFIG_IMPORT_AVAILABLE_PHOCA_DOWNLOADS_NAME', 'Phoca Downloads');
                $this.empty();
                $this.append(contentlb);
            }
        },
        setCloudConnectBtnStatus: function () {
            if($('#jform_google_client_id').val() != '' && $('#jform_google_client_secret').val() != '') {
                $('a.btn-google').show();
            } else {
                $('a.btn-google').hide();
            }

            if($('#jform_onedriveKey').val() != '' && $('#jform_onedriveSecret').val() != '') {
                $('a.btn-onedrive').show();
            } else {
                $('a.btn-onedrive').hide();
            }

            if($('#jform_dropbox_key').val() != '' && $('#jform_dropbox_secret').val() != '') {
                $('a.btn-dropbox').show();
            } else {
                $('a.btn-dropbox').hide();
            }
        },
        // save configuration status
        initConfigSet: function (e) {
            $(document).on('click', '.link-tab', function(e) {
                var id = $(this).attr('id');
                localStorage.setItem('dropfilesConfigState', id);
            });
        },
        showThelastConfigWorking: function (e) {
            var configid =  localStorage.getItem('dropfilesConfigState');
            if(configid != '' && configid != 'undefined' && configid != null) {
                if(configid == 'mainlinktab' || configid == 'main_frontendlinktab' || configid == 'main_advancedlinktab'
                    || configid == 'mainjutoplink' || configid == 'main_advancedjutoplink' || configid == 'main_frontendjutoplink') {
                    $('.main-settings-list-tab').addClass('expanded');
                    if(configid == 'mainjutoplink' || configid == 'main_advancedjutoplink' || configid == 'main_frontendjutoplink') {
                        $('#main-settings-top-tabs').show();
                    }
                } else if(configid == 'default_themelinktab' || configid == 'ggd_themelinktab'
                    || configid == 'theme_tablelinktab' || configid == 'tree_themelinktab'
                    || configid == 'default_themejutoplink' || configid == 'ggd_themejutoplink'
                    || configid == 'theme_tablejutoplink' || configid == 'tree_themejutoplink') {
                    $('.theme-list-tab').addClass('expanded');
                    if (configid == 'default_themejutoplink' || configid == 'ggd_themejutoplink'
                        || configid == 'theme_tablejutoplink' || configid == 'tree_themejutoplink') {
                        $('#theme-settings-top-tabs').show();
                    }
                } else if(configid == 'cloud_connectionlinktab' || configid == 'cloud_onedrivelinktab'
                    || configid == 'cloud_dropboxlinktab' || configid == 'cloud_connectionjutoplink'
                    || configid == 'cloud_onedrivejutoplink' || configid == 'cloud_dropboxjutoplink'
                    || configid == 'cloud_onedrive_businessjutoplink') {
                    $('.cloud-connection-tab').addClass('expanded');
                    if(configid == 'cloud_connectionjutoplink' || configid == 'cloud_onedrivejutoplink'
                        || configid == 'cloud_dropboxjutoplink' || configid == 'cloud_onedrive_businessjutoplink') {
                        $('#cloud-settings-top-tabs').show();
                    }
                }
                $('#' + configid).click();
            } else if (configid == null) {
                $('.ju-right-panel .ju-content-wrapper').hide();
                $('.ju-right-panel #main').show();
                $('#theme-settings-top-tabs').hide();
                $('#cloud-settings-top-tabs').hide();
                $('#main-settings-top-tabs').show();
                $('.ju-menu-tabs .main-settings-list-tab > .link-tab').click();
                $('#mainjutoplink').click();
            }
        },
        showHelperContent: function (e) {
            var googleClientSecretHelp = '<div class="ju-settings-help">' + Joomla.JText._('COM_DROPFILES_CONFIG_SYNC_METHOD_HELP', 'The Google Drive synchronization method. Default is AJAX, advanced user only.') + '</div>';
            var googleSyncTimeHelp = '<div class="ju-settings-help">' + Joomla.JText._('COM_DROPFILES_CONFIG_SYNC_TIME_HELP', 'Automatic Google Drive content synchronization delay. Default is 5 minutes.') + '</div>';
            var onedriveSyncMethodHelp = '<div class="ju-settings-help">' + Joomla.JText._('COM_DROPFILES_CONFIG_ONEDRIVE_SYNC_METHOD_HELP', 'The OneDrive synchronization method. Default is AJAX, advanced user only.') + '</div>';
            var onedriveSyncTimeHelp = '<div class="ju-settings-help">' + Joomla.JText._('COM_DROPFILES_CONFIG_ONEDRIVE_SYNC_TIME_HELP', 'Automatic OneDrive content synchronization delay. Default is 5 minutes.') + '</div>';
            var dropboxSyncMethodHelp = '<div class="ju-settings-help">' + Joomla.JText._('COM_DROPFILES_CONFIG_DROPBOX_SYNC_METHOD_HELP', 'The Dropbox synchronization method. Default is AJAX, advanced user only.') + '</div>';
            var dropboxSyncTimeHelp = '<div class="ju-settings-help">' + Joomla.JText._('COM_DROPFILES_CONFIG_DROPBOX_SYNC_TIME_HELP', 'Automatic Dropbox content synchronization delay. Default is 5 minutes.') + '</div>';
            var onedriveBusinessSyncMethodHelp = '<div class="ju-settings-help">' + Joomla.JText._('COM_DROPFILES_CONFIG_ONEDRIVE_BUSINESS_SYNC_METHOD_HELP', 'The OneDrive synchronization method. Default is AJAX, advanced user only.') + '</div>';
            var onedriveBusinessSyncTimeHelp = '<div class="ju-settings-help">' + Joomla.JText._('COM_DROPFILES_CONFIG_CLOUD_ONEDRIVE_BUSINESS_SYNC_TIME_HELP', 'Automatic OneDrive content synchronization delay. Default is 5 minutes.') + '</div>';
            if($('#jform_google_client_secret').length) {
                $('li.jform_sync_method .ju-custom-block').append(googleClientSecretHelp);
            }
            if($('#jform_sync_time').length) {
                $('li.jform_sync_time .ju-custom-block').append(googleSyncTimeHelp);
            }
            if($('#jform_onedriveSyncMethod').length) {
                $('li.jform_onedriveSyncMethod .ju-custom-block').append(onedriveSyncMethodHelp);
            }
            if($('#jform_onedriveSyncTime').length) {
                $('li.jform_onedriveSyncTime .ju-custom-block').append(onedriveSyncTimeHelp);
            }
            if($('#jform_dropbox_sync_method').length) {
                $('li.jform_dropbox_sync_method .ju-custom-block').append(dropboxSyncMethodHelp);
            }
            if($('#jform_dropbox_sync_time').length) {
                $('li.jform_dropbox_sync_time .ju-custom-block').append(dropboxSyncTimeHelp);
            }
            if($('#jform_onedriveBusinessSyncMethod').length) {
                $('li.jform_onedriveBusinessSyncMethod .ju-custom-block').append(onedriveBusinessSyncMethodHelp);
            }
            if($('#jform_onedriveBusinessSyncTime').length) {
                $('li.jform_onedriveBusinessSyncTime .ju-custom-block').append(onedriveBusinessSyncTimeHelp);
            }
        },
        displayTheCrontask: function () {
            //google cron-task
            var googleSyncMethod = $('#jform_sync_method').val();
            if(googleSyncMethod == 'setup_on_server') {
                $('#cloud_connection li.jform_cron_task_url').show();
            } else {
                $('#cloud_connection li.jform_cron_task_url').hide();
            }
            //onedrive cron-task
            var onedriveSyncMethod = $('#jform_onedriveSyncMethod').val();
            if(onedriveSyncMethod == 'setup_on_server') {
                $('#cloud_onedrive li.jform_cron_task_url').show();
            } else {
                $('#cloud_onedrive li.jform_cron_task_url').hide();
            }
            //dropbox cron-task
            var dropboxSyncMethod = $('#jform_dropbox_sync_method').val();
            if(dropboxSyncMethod == 'dropbox_setup_on_server') {
                $('#cloud_dropbox li.jform_dropbox_cron_task_url').show();
            } else {
                $('#cloud_dropbox li.jform_dropbox_cron_task_url').hide();
            }
            //onedrive business cron-task
            var onedriveBusinessSyncMethod = $('#jform_onedriveBusinessSyncMethod').val();
            if(onedriveBusinessSyncMethod === 'setup_on_server') {
                $('#cloud_onedrive_business li.jform_onedrive_business_cron_task_url').show();
            } else {
                $('#cloud_onedrive_business li.jform_onedrive_business_cron_task_url').hide();
            }
        },
        connectOneDriveBusiness: function (e) {
            e.preventDefault();
            if (typeof (dropfilesOnedriveBusinessUrl) === undefined ||
                dropfilesOnedriveBusinessUrl === '') {
                return;
            }

            var connect_window = window.open(dropfilesOnedriveBusinessUrl,'foo','width=600,height=600');
            setTimeout(function () {
                connect_window.close();
                location.href = window.location.href;
            }, 4000);
        }
    };

    // Search indexer
    var dropfiles_indexer = {
        init: function () {
            $(document).on('change', '#search .switch #jform_plain_text_search', this.onChange);
            $(document).on('mouseover', '#search_indexer.worked', this.onMouseOver);
            $(document).on('mouseout', '#search_indexer.worked', this.onMouseOut);

            this.onReady();
        },
        onReady: function () {
            if($('#indexResult').length) {
                $('#search_indexer').empty();
                $('#search_indexer').append($('#indexResult'));
                $('#search_indexer').removeClass('default');
                $('#search_indexer').addClass('worked');
            }

            if($('#jform_plain_text_search').attr('checked') != 'checked') {
                $('.plain-text-search-settings .jform_searchindexer').hide();
                $('#search_indexer').html(Joomla.JText._('COM_DROPFILES_CONFIGURATION_INNER_SEARCH', 'Build Search Index'));
                $('#search_indexer').addClass('default');
            }

        },
        onChange: function (e) {
            var $this = $(e.target);
            var $indexerContainer = $('.plain-text-search-settings .jform_searchindexer');
            $indexerContainer.slideToggle();
        },
        onMouseOver: function (e) {
            e.preventDefault();
            var $this = $(e.target);
            this.status = $this.html();
            $this.html(Joomla.JText._('COM_DROPFILES_CONFIGURATION_INNER_SEARCH', 'Build Search Index'));
            return false;
        },
        onMouseOut: function (e) {
            e.preventDefault();
            var $this = $(e.target);
            $this.html(this.status);

            return false;
        },
    };

    // Generate preview
    var dropfiles_generate_preview = {
        disabled: false,
        running: false,
        pinger: false,
        init: function() {
            $(document).on('change', '.switch input[name="jform[auto_generate_preview]"]', this.onChange);
            $(document).on('click', '#dropfiles_generate_preview', {'wrapper': this}, this.run);
            $(document).on('mouseover', '#dropfiles_generate_preview', {'wrapper': this}, this.onMouseOver);
            $(document).on('mouseout', '#dropfiles_generate_preview', this.onMouseOut);
            // Generate preview show log
            $(document).on('click', '#dropfiles_show_log', this.showGeneratePreviewLog);
            this.onReady();
        },
        onReady: function() {
            var $elem = $('#jform_auto_generate_preview');
            if ($elem.length && parseInt($elem.data('val')) === 1) {
                dropfiles_generate_preview.send('generatepreview.status', {}, dropfiles_generate_preview.pingProcessor);
            }
        },
        showGeneratePreviewLog: function(e) {
            var $this = $(this);
            var showLabel = $(this).data('show-label');
            var hideLabel = $(this).data('hide-label');
            $this.attr('data-clicked', '1');
            $('#dropfiles_generate_preview-logs').slideToggle(400, function() {
                if ($('#dropfiles_generate_preview-logs').is(':visible')) {
                    $this.text(hideLabel);
                } else {
                    $this.text(showLabel);
                }
            });

        },
        ping: function(data) {
            dropfiles_generate_preview.pinger = setTimeout(function() {
                if (dropfiles_generate_preview.running) {
                    return;
                }
                dropfiles_generate_preview.running = true;
                dropfiles_generate_preview.send('generatepreview.status', {}, dropfiles_generate_preview.pingProcessor);
                dropfiles_generate_preview.running = false;
            }, 5000);
        },
        onMouseOver: function (e) {
            e.preventDefault();
            if (dropfiles_generate_preview.disabled) {
                return;
            }
            var $this = $(e.target);
            this.status = $this.html();
            $this.html('REGENERATE ALL FILES PREVIEW/THUMBNAIL');

            return false;
        },
        onMouseOut: function (e) {
            e.preventDefault();
            if (dropfiles_generate_preview.disabled) {
                return;
            }
            var $this = $(e.target);
            $this.html(this.status);

            return false;
        },
        onChange: function () {
            $('.generate_preview_wrapper').slideToggle();
        },
        run: function() {
            if (dropfiles_generate_preview.disabled) {
                return;
            }
            var $this = $(this);
            var confirm_text = $this.attr('data-confirm');
            var isallow = false;

            if ((confirm_text) && (confirm_text.length > 0)) {
                if (confirm(confirm_text)) {
                    isallow = true;
                }
            } else {
                isallow = true;
            }

            if (isallow) {
                dropfiles_generate_preview.send('generatepreview.restartqueue', {}, function(data) {
                    if (typeof (data.code) !== "undefined") {
                        $('#dropfiles_generate_error_message').html(data.message);
                        // Hide error message after 15sec
                        setTimeout(function() {
                            $('#dropfiles_generate_error_message').html('');
                        }, 15000);
                    } else {
                        clearTimeout(dropfiles_generate_preview.pinger);
                        // Update current status to waiting
                        $("#dropfiles_generate_preview").css('background', 'linear-gradient(90deg, #5dca70 100%, #2196f3 100%)');
                        $('#dropfiles_generate_preview').html('Waiting...');
                        dropfiles_generate_preview.ping();
                    }
                });
            }

            return false;
        },
        pingStatus: function() {
            dropfiles_generate_preview.send('generatepreview.status', {}, dropfiles_generate_preview.updateStatus);
        },
        pingProcessor: function(status) {
            if (typeof status === 'string') {
                status = JSON.parse(status);
            }
            dropfiles_generate_preview.updateStatus(status);

            if (!status.is_running && (status.p_generated + status.p_error) !== status.p_total) {
                dropfiles_generate_preview.send('generatepreview.runqueue', {}, function() {});
            }
            clearTimeout(dropfiles_generate_preview.pinger);
            if ((status.p_generated + status.p_error) !== status.p_total) {
                dropfiles_generate_preview.ping();
            }
        },
        updateStatus: function (status) {
            if (typeof status === 'string') {
                status = JSON.parse(status);
            }
            if (typeof status.message !== "undefined") {
                if (status.error && status.code === 'user_not_login') {
                    dropfiles_generate_preview.disabled = true;
                    $("#dropfiles_generate_preview").unbind('click').on('click', function() {
                        window.location.href = 'index.php?option=com_dropfiles&task=configuration.display';
                    });
                }
                $("#dropfiles_generate_preview").css('background', 'linear-gradient(90deg, #5dca70 100%, #2196f3 100%)');
                $('#dropfiles_generate_preview').html(status.message);
            } else {
                $("#dropfiles_generate_preview").prop('disabled', false);
                dropfiles_generate_preview.disabled = false;
                // Prepare
                var pending = status.p_pending;
                var processing = status.p_processing;
                var error = status.p_error;
                var generated = status.p_generated;
                var total = status.p_total;
                var logs = status.logs || '';
                var $showLogs = $('#dropfiles_show_log');
                var $logsWrapper = $('#dropfiles_generate_preview-logs');

                $logsWrapper.html('');
                if (logs) {
                    var logLabel = $logsWrapper.data('label');
                    if (!parseInt($showLogs.attr('data-clicked'))) {
                        $logsWrapper.show();
                    }
                    $logsWrapper.html('<h4 class="dropfiles_log_label">' + logLabel + '</h4><ul>' + logs.split("\n").map(function(line) { return '<li>' + line + '</li>';}).join('') + '</ul>');
                    $logsWrapper.animate({scrollTop: $logsWrapper.prop('scrollHeight')}, 500);
                }

                if (generated + error === total) {
                    $("#dropfiles_generate_preview").css('background', 'linear-gradient(90deg, #5dca70 0%, #2196f3 0%)');
                    if (total === 0 && generated === 0) {
                        $("#dropfiles_generate_preview").html('Generate Preview/Thumbnail');
                    } else {
                        $("#dropfiles_generate_preview").html(generated + ' files preview/thumbnail generated!');
                    }
                    $logsWrapper.hide();
                } else {
                    var processerStatus = '<i class="dropfiles-icon-indexing"></i> Generator is running: ' + generated + ' / ' + total + ' files';
                    var percent = generated * 100 / total;
                    $("#dropfiles_generate_preview").css('background', 'linear-gradient(90deg, #5dca70 ' + percent + '%, #2196f3 ' + percent + '%)');
                    $('#dropfiles_generate_preview').html(processerStatus);
                    $('#dropfiles_generate_error_message').html('');
                }

                if (error > 0 && typeof (status.error_message) !== "undefined") {
                    $('#dropfiles_generate_error_message').html(status.error_message);
                }
            }
        },
        send: function(action, data, callback) {
            var url = 'index.php?option=com_dropfiles&';

            $.ajax({
                url: url + "task=" + action,
                method: 'POST',
                data: {data: data},
                success: function(response) {
                    if (typeof response === "string") {
                        response = JSON.parse(response);
                    }
                    callback(response);
                }
            });
        }
    };

    // Init
    dropfiles_configuration.init();
    dropfiles_indexer.init();
    dropfiles_generate_preview.init();
});