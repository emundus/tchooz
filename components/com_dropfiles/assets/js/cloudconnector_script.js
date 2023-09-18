jQuery(document).ready(function ($) {
    $(".ggd-automatic-connect, .onedrive-automatic-connect, .onedrive-business-automatic-connect, .dropbox-automatic-connect").on('click', function (e) {
        e.preventDefault();
        var data_link = $(this).data('link');
        var data_network = $(this).data('network');
        var extension = 'dropfiles.zip';

        // Connect Cloud
        window.open(dropfiles_cloud_connector_var + data_network + '/login?sendback=' + data_link + '&extension=' + extension + '&ju_token=' + dropfiles_cloud_connector_ju_token_var,
            'cloudconnectwindow',
            'location=yes,height=620,width=560,scrollbars=yes,status=yes'
        );
    });

    $('.ggd-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();
        if (val === 'automatic') {
            $('#dropfiles-btn-automaticconnect-ggd').show();
            $('#dropfiles-btn-automaticdisconnect-ggd').show();
            $('.ggd-ju-connect-message').show();
            $('.ju-settings-option.jform_google_client_id').hide();
            $('.ju-settings-option.jform_google_client_secret').hide();
            $('.ju-settings-option.jform_googlebtn').css({'padding': '0'});
            $('.ju-settings-option.jform_googlebtn .ju-setting-label').hide();
            $('.ju-settings-option.jform_googlebtn .ju-custom-block').children().filter(':not(#dropfiles_btn_google_changes)').hide();
            $('#dropfiles_btn_google_changes').removeClass('hide');

            if ($('#dropfiles-btn-automaticdisconnect-ggd').hasClass("ju-visibled")) {
                $('#dropfiles-btn-automaticdisconnect-ggd').show();
            } else {
                $('#dropfiles-btn-automaticdisconnect-ggd').hide();
            }

            if ($('#dropfiles-btn-automaticconnect-ggd').hasClass("ju-visibled")) {
                $('#dropfiles-btn-automaticconnect-ggd').show();
            } else {
                $('#dropfiles-btn-automaticconnect-ggd').hide();
            }
        } else {
            var is_gg_watch_change = $('#dropfiles_btn_google_changes').is(':visible');
            $('.ju-settings-option.jform_google_client_id').show();
            $('.ju-settings-option.jform_google_client_secret').show();

            $('.ggd-ju-connect-message').hide();
            $('#dropfiles-btn-automaticconnect-ggd').hide();
            $('#dropfiles-btn-automaticdisconnect-ggd').hide();
            $('.ju-settings-option.jform_googlebtn').css({'padding': '10px 20px'});
            $('.ju-settings-option.jform_googlebtn .ju-setting-label').show();
            $('.ju-settings-option.jform_googlebtn .ju-custom-block').children().filter(':not(#dropfiles_btn_google_changes, style)').show();
            if (is_gg_watch_change) {
                $('#dropfiles_btn_google_changes').addClass('hide');
            }
        }
    });

    $('.dropbox-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();

        if (val === 'automatic') {
            $('.ju-settings-option.jform_dropbox_key').hide();
            $('.ju-settings-option.jform_dropbox_secret').hide();
            $('.ju-settings-option.jform_dropbox_authorization_code').hide();
            $('.ju-settings-option.jform_dropboxbtn').hide();
            $('.btn-dropbox').hide();
            $('.dropbox-ju-connect-message').show();

            if ($('#dropfiles-btn-automaticdisconnect-dropbox').hasClass("ju-visibled")) {
                $('#dropfiles-btn-automaticdisconnect-dropbox').show();
            } else {
                $('#dropfiles-btn-automaticdisconnect-dropbox').hide();
            }

            if ($('#dropfiles-btn-automaticconnect-dropbox').hasClass("ju-visibled")) {
                $('#dropfiles-btn-automaticconnect-dropbox').show();
            } else {
                $('#dropfiles-btn-automaticconnect-dropbox').hide();
            }
        } else {
            $('.ju-settings-option.jform_dropbox_key').show();
            $('.ju-settings-option.jform_dropbox_secret').show();
            $('.ju-settings-option.jform_dropbox_authorization_code').show();
            $('.btn-dropbox').show();
            $('#dropfiles-btn-automaticconnect-dropbox').hide();
            $('#dropfiles-btn-automaticdisconnect-dropbox').hide();
            $('.dropbox-ju-connect-message').hide();
            $('.ju-settings-option.jform_dropboxbtn').show();
        }
    });

    $('.od-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();
        if (val === 'automatic') {
            $('.ju-settings-option.jform_onedriveKey').hide();
            $('.ju-settings-option.jform_onedriveSecret').hide();
            $('.btn-onedrive').hide();
            $('.od-ju-connect-message').show();
            $('.ju-settings-option.jform_onedrivebtn').css({'padding': '0'});
            $('.ju-settings-option.jform_onedrivebtn .ju-setting-label').hide();
            $('.ju-settings-option.jform_onedrivebtn .ju-custom-block').children().filter(':not(.btn-onedrive)').hide();

            if ($('#dropfiles-btn-automaticdisconnect-onedrive').hasClass("ju-visibled")) {
                $('#dropfiles-btn-automaticdisconnect-onedrive').show();
            } else {
                $('#dropfiles-btn-automaticdisconnect-onedrive').hide();
            }

            if ($('#dropfiles-btn-automaticconnect-onedrive').hasClass("ju-visibled")) {
                $('#dropfiles-btn-automaticconnect-onedrive').show();
            } else {
                $('#dropfiles-btn-automaticconnect-onedrive').hide();
            }
        } else {
            $('.ju-settings-option.jform_onedriveKey').show();
            $('.ju-settings-option.jform_onedriveSecret').show();
            $('.ju-settings-option.jform_onedrivebtn .btn-onedrive').show();
            $('#dropfiles-btn-automaticconnect-onedrive').hide();
            $('#dropfiles-btn-automaticdisconnect-onedrive').hide();
            $('.od-ju-connect-message').hide();
            $('.ju-settings-option.jform_onedrivebtn').css({'padding': '10px 20px'});
            $('.ju-settings-option.jform_onedrivebtn .ju-setting-label').show();
            $('.ju-settings-option.jform_onedrivebtn .ju-custom-block').children().filter(':not(.btn-onedrive, style)').show();

            if ($('.btn-onedrive.ju-no-configs').length) {
                $('.btn-onedrive.ju-no-configs').hide();
            }
        }
    });

    $('.odb-mode-radio-field .ju-radiobox').on('click', function () {
        var val = $(this).val();

        if (val === 'automatic') {
            $('.ju-settings-option.jform_onedriveBusinessKey').hide();
            $('.ju-settings-option.jform_onedriveBusinessSecret').hide();
            $('.btn-onedrivebusiness').hide();
            $('.odb-ju-connect-message').show();
            $('.ju-settings-option.jform_onedrivebusinessbtn').css({'padding': '0'});
            $('.ju-settings-option.jform_onedrivebusinessbtn .ju-setting-label').hide();
            $('.ju-settings-option.jform_onedrivebusinessbtn .ju-custom-block').children().filter(':not(#dropfiles-btnpush-onedrive-business)').hide();

            if ($('#dropfiles-btn-automaticdisconnect-onedrive-business').hasClass("ju-visibled")) {
                $('#dropfiles-btn-automaticdisconnect-onedrive-business').show();
            } else {
                $('#dropfiles-btn-automaticdisconnect-onedrive-business').hide();
            }

            if ($('#dropfiles-btn-automaticconnect-onedrive-business').hasClass("ju-visibled")) {
                $('#dropfiles-btn-automaticconnect-onedrive-business').show();
            } else {
                $('#dropfiles-btn-automaticconnect-onedrive-business').hide();
            }
        } else {
            $('.ju-settings-option.jform_onedriveBusinessKey').show();
            $('.ju-settings-option.jform_onedriveBusinessSecret').show();
            $('#dropfiles-btn-automaticconnect-onedrive-business').hide();
            $('#dropfiles-btn-automaticdisconnect-onedrive-business').hide();
            $('.btn-onedrivebusiness').show();
            $('.odb-ju-connect-message').hide();
            $('.ju-settings-option.jform_onedrivebusinessbtn').css({'padding': '10px 20px'});
            $('.ju-settings-option.jform_onedrivebusinessbtn .ju-setting-label').show();
            $('.ju-settings-option.jform_onedrivebusinessbtn .ju-custom-block').children().filter(':not(#dropfiles-btnpush-onedrive-business, style)').show();
        }

    });
});