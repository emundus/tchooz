/* ======================================================
 # Web357 Framework for Joomla! - v2.0.0 (free version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Support: support@web357.com
 # Last modified: Monday 27 October 2025, 03:04:38 PM
 ========================================================= */

jQuery(document).ready(function ($) {
    var web357_apikey_activate = function () {
        var form = document.getElementById('style-form');

        $.ajax({
            type: 'POST',
            url: 'index.php?option=com_ajax&plugin=web357framework&format=raw&method=web357ApikeyValidation',
            data: $(form).serialize(),
            cache: false,
            success: function (response) {
                $('.web357_apikey_activation_html').html(response);
            },
            error: function (response) {
                $('.web357_apikey_activation_html').html(response);
            },
            beforeSend: function () {
                $('.web357-loading-gif').show();
                // $("#w357-not-yet-activated-msg").hide();

                $('#w357-activated-successfully-msg').hide();
                $('#w357-activated-successfully-msg-ajax').hide();
            },
            complete: function () {
                //setTimeout(function() {
                $('.web357-loading-gif').hide();
                $('#w357-activated-successfully-msg').css('display', 'none');
                $('#w357-activated-successfully-msg-ajax').css(
                    'display',
                    'block'
                );
                //}, 500);
            },
        });
    };

    // Do not show the button (activate api key) on typing
    $('#jform_params_apikey').on('input', function () {
        var translatedText = Joomla.JText._('W357FRM_SAVE_PLUGIN_SETTINGS');
        $('#apikey-container').html(
            '<p style="color: red; margin-top: 15px;">' +
                translatedText +
                '</p>'
        );

        //$("#apikey-container").hide();
    });

    // Restore to Defaults
    $(document).on('click', '.web357-activate-api-key-btn', function (e) {
        e.preventDefault();
        web357_apikey_activate();
    });
});
