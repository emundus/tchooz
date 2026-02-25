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

document.addEventListener('DOMContentLoaded', function () {
    /**
     * CUSTOM MODAL FUNCTIONALITY
     */
    function createCustomModal() {
        // Add CSS styles for custom modal
        var modalCSS = `
            <style>
                .web357-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 9999;
                    display: none;
                    justify-content: center;
                    align-items: center;
                }
                .web357-modal {
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                    max-width: 90vw;
                    max-height: 90vh;
                    overflow: auto;
                    position: relative;
                }
                .web357-modal-header {
                    padding: 15px 20px;
                    border-bottom: 1px solid #e5e5e5;
                    background: #f8f9fa;
                    border-radius: 8px 8px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .web357-modal-title {
                    margin: 0;
                    font-size: 18px;
                    font-weight: 500;
                }
                .web357-modal-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: background-color 0.2s;
                }
                .web357-modal-close:hover {
                    background-color: #e9ecef;
                }
                .web357-modal-body {
                    padding: 20px;
                    text-align: center;
                }
                .web357-modal-body img {
                    max-width: 100%;
                    height: auto;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                @media (max-width: 768px) {
                    .web357-modal {
                        margin: 20px;
                        max-width: calc(100vw - 40px);
                    }
                    .web357-modal-header {
                        padding: 10px 15px;
                    }
                    .web357-modal-body {
                        padding: 15px;
                    }
                }
            </style>
        `;

        // Add CSS to head if not already added
        if (!document.getElementById('web357-modal-styles')) {
            const head =
                document.head || document.getElementsByTagName('head')[0];
            head.insertAdjacentHTML('beforeend', modalCSS);
            head.insertAdjacentHTML(
                'beforeend',
                '<div id="web357-modal-styles"></div>'
            );
        }

        // Create modal overlay if not exists
        if (!document.getElementById('web357-modal-overlay')) {
            document.body.insertAdjacentHTML(
                'beforeend',
                `
                <div id="web357-modal-overlay" class="web357-modal-overlay">
                    <div class="web357-modal">
                        <div class="web357-modal-header">
                            <h3 class="web357-modal-title">Screenshot</h3>
                            <button type="button" class="web357-modal-close">&times;</button>
                        </div>
                        <div class="web357-modal-body">
                            <img src="" alt="Screenshot" />
                        </div>
                    </div>
                </div>
            `
            );

            // Add event listeners
            const modalOverlay = document.getElementById(
                'web357-modal-overlay'
            );
            modalOverlay.addEventListener('click', function (e) {
                if (e.target === this) {
                    closeModal();
                }
            });

            const closeButton = document.querySelector('.web357-modal-close');
            closeButton.addEventListener('click', function () {
                closeModal();
            });

            // Close modal on escape key
            document.addEventListener('keydown', function (e) {
                if (
                    e.key === 'Escape' &&
                    modalOverlay.style.display !== 'none'
                ) {
                    closeModal();
                }
            });
        }
    }

    function openModal(imageSrc, width, height) {
        const modal = document.getElementById('web357-modal-overlay');
        const modalContent = modal.querySelector('.web357-modal');
        const modalImg = modal.querySelector('.web357-modal-body img');

        // Set image source
        modalImg.src = imageSrc;

        // Set modal width based on image dimensions
        const modalWidth = Math.min(width + 40, window.innerWidth * 0.9);
        modalContent.style.width = modalWidth + 'px';

        // Show modal
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modal = document.getElementById('web357-modal-overlay');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }

    // Initialize custom modal
    createCustomModal();

    /**
     * SCREENSHOTS FOR PARAMETERS
     */
    // get paths and urls
    const baseUrlElement = document.getElementById('baseurl');
    const jversionElement = document.getElementById('jversion');
    const baseUrl = baseUrlElement ? baseUrlElement.dataset.baseurl : '';
    const jversion = jversionElement ? jversionElement.dataset.jversion : '';

    // Add the version class to the body
    if (jversion) {
        document.body.classList.add('web357-' + jversion);
    }

    // get screenshots array
    const screenshots = [];

    /**
     * SCREENSHOTS FOR LIMIT ACTIVE LOGINS
     */
    const lal_path =
        baseUrl +
        'media/com_limitactivelogins/images/screenshots-for-parameters/';
    screenshots.push({
        sclass: '.lal-showLoggedInDevices',
        src: lal_path + 'showLoggedInDevices.png',
        width: 1021,
        height: 699,
    });
    screenshots.push({
        sclass: '.lal-customErrorMessage',
        src: lal_path + 'customErrorMessage.png',
        width: 750,
        height: 250,
    });
    screenshots.push({
        sclass: '.lal-showGravatar',
        src: lal_path + 'showGravatar.png',
        width: 987,
        height: 216,
    });
    screenshots.push({
        sclass: '.lal-customLimits',
        src: lal_path + 'customLimits.png',
        width: 987,
        height: 216,
    });

    /**
     * SCREENSHOTS FOR COOKIES POLICY NOTIFICATION BAR
     */
    const cpnb_path =
        baseUrl +
        'plugins/system/cookiespolicynotificationbar/assets/images/screenshots-for-parameters/';
    screenshots.push({
        sclass: '.cpnb-position',
        src: cpnb_path + 'position.png',
        width: 1156,
        height: 669,
    });
    screenshots.push({
        sclass: '.cpnb-showCloseXIcon',
        src: cpnb_path + 'showCloseXIcon.png',
        width: 640,
        height: 208,
    });
    screenshots.push({
        sclass: '.cpnb-enableConfirmationAlerts',
        src: cpnb_path + 'enableConfirmationAlerts.png',
        width: 1021,
        height: 699,
    });
    screenshots.push({
        sclass: '.cpnb-notification-bar-message',
        src: cpnb_path + 'notification-bar.png',
        width: 1142,
        height: 691,
    });
    screenshots.push({
        sclass: '.cpnb-modal-info-window',
        src: cpnb_path + 'modal-info-window.png',
        width: 1145,
        height: 691,
    });
    screenshots.push({
        sclass: '.cpnb-modalState',
        src: cpnb_path + 'modalState.png',
        width: 1145,
        height: 786,
    });
    screenshots.push({
        sclass: '.cpnb-modalFloatButtonState',
        src: cpnb_path + 'modalFloatButtonState.png',
        width: 1145,
        height: 786,
    });
    screenshots.push({
        sclass: '.cpnb-modalHashLink',
        src: cpnb_path + 'modalHashLink.png',
        width: 1311,
        height: 795,
    });

    /**
     * SCREENSHOTS FOR SUPPORT HOURS
     */
    const sh_path = baseUrl + 'modules/mod_supporthours/screenshots/';
    screenshots.push({
        sclass: '.sh-display_copyright',
        src: sh_path + 'display_copyright.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-dateformat',
        src: sh_path + 'dateformat.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-timeformat',
        src: sh_path + 'timeformat.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-display_pm_am',
        src: sh_path + 'display_pm_am.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-open_hours_time_format',
        src: sh_path + 'open_hours_time_format.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-display_gmt',
        src: sh_path + 'display_gmt.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-display_open_hours_beside_maintext',
        src: sh_path + 'display_open_hours_beside_maintext.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-online_text',
        src: sh_path + 'online_text.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-front_text_available',
        src: sh_path + 'front_text_available.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-offline_text',
        src: sh_path + 'offline_text.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-front_text_offline',
        src: sh_path + 'front_text_offline.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-state_text',
        src: sh_path + 'state_text.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-show_available_left_link',
        src: sh_path + 'show_available_left_link.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-show_available_right_link',
        src: sh_path + 'show_available_right_link.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-show_offline_link',
        src: sh_path + 'show_offline_link.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-box_width',
        src: sh_path + 'box_width.png',
        width: 650,
        height: 535,
    });
    screenshots.push({
        sclass: '.sh-layout',
        src: sh_path + 'layout.png',
        width: 930,
        height: 550,
    });

    /**
     * SCREENSHOTS FOR THE FIX 404 ERROR LINKS
     */
    const f404_path =
        baseUrl +
        'administrator/components/com_fix404errorlinks/assets/images/screenshots-for-parameters/';
    screenshots.push({
        sclass: '.f404-copyright',
        src: f404_path + 'f404-copyright.png',
        width: 483,
        height: 297,
    });

    /**
     * SCREENSHOTS FOR LOGIN AS USER
     */
    const login_as_user_system_plugin_path =
        baseUrl +
        'plugins/system/loginasuser/assets/images/screenshots-for-parameters/';
    screenshots.push({
        sclass: '.lau-showSuccessMessage',
        src: login_as_user_system_plugin_path + 'showSuccessMessage.png',
        width: 976,
        height: 559,
    });

    /// add screenshots for parameters
    for (let i = 0, len = screenshots.length; i < len; i++) {
        const sclass = screenshots[i].sclass;
        const screenshot_src = screenshots[i].src;

        if (jversion === 'j4x') {
            if (document.querySelector(sclass)) {
                // check if the class exists.
                // j4
                const modal_width = screenshots[i].width + 2;
                const modal_id = sclass.replace('.', '');

                // styling
                let style;
                if (
                    sclass === '.cpnb-notification-bar-message' ||
                    sclass === '.sh-front_text_available' ||
                    sclass === '.sh-front_text_offline'
                ) {
                    // textarea
                    style =
                        'margin-left: 20px; cursor: pointer; vertical-align: top;';
                } else {
                    style = 'margin-left: 20px; cursor: pointer; ';
                }

                // Create button with custom modal functionality
                const screenshot_html =
                    `<button type="button" title="See a Screenshot" class="web357-screenshot-btn" 
                     data-image-src="` +
                    screenshot_src +
                    `" 
                     data-width="` +
                    screenshots[i].width +
                    `" 
                     data-height="` +
                    screenshots[i].height +
                    `" 
                     style="` +
                    style +
                    `">
                        <span class="icon-eye" aria-hidden="true"></span>
                    </button>`;

                // Insert after the element
                const targetElement = document.querySelector(sclass);
                if (targetElement) {
                    targetElement.insertAdjacentHTML(
                        'afterend',
                        screenshot_html
                    );
                }
            }
        } else {
            // j3x, j25x - also use custom modal
            const screenshot_html =
                `<div style="display: inline-block; margin-left: 20px; position: relative; top: 2px;">
                    <button type="button" title="Click to see an example." class="web357-screenshot-btn" 
                     data-image-src="` +
                screenshot_src +
                `" 
                     data-width="` +
                screenshots[i].width +
                `" 
                     data-height="` +
                screenshots[i].height +
                `" 
                     style="background: none; border: none; cursor: pointer; padding: 0;">
                        <i class="icon-eye-open"></i>
                    </button>
                </div>`;

            // Insert after the element
            const targetElement = document.querySelector(sclass);
            if (targetElement) {
                targetElement.insertAdjacentHTML('afterend', screenshot_html);
            }
        }
    }

    // Add event listener for custom screenshot buttons
    document.addEventListener('click', function (e) {
        if (e.target.closest('.web357-screenshot-btn')) {
            e.preventDefault();
            const button = e.target.closest('.web357-screenshot-btn');
            const imageSrc = button.dataset.imageSrc;
            const width = parseInt(button.dataset.width);
            const height = parseInt(button.dataset.height);
            openModal(imageSrc, width, height);
        }
    });

    // J4: Remove the label from subform fields in the component/plugin settings
    const cookieCategoriesLabel = document.querySelector(
        'body.web357-j4x label[for="jform_params_cookie_categories_group"]'
    );
    if (cookieCategoriesLabel && cookieCategoriesLabel.parentElement) {
        cookieCategoriesLabel.parentElement.remove();
    }

    const customLimitsLabel = document.querySelector(
        'body.web357-j4x label[for="jform_custom_limits_group"]'
    );
    if (customLimitsLabel && customLimitsLabel.parentElement) {
        customLimitsLabel.parentElement.remove();
    }
});
