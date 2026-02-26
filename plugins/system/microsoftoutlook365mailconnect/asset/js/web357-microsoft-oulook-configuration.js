document.addEventListener('DOMContentLoaded', function () {

    const microsoftOutlookOptions = Joomla.getOptions('web357.microsoft-outlook.js.configuration.script-options');

    /* Revoke Button */
    const revokeBtn = document.getElementById('web357-revoke-microsoft-token');
    if (revokeBtn) {
        revokeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            web357postForm(microsoftOutlookOptions.actionRevokeToken, {
                revoke: 1
            });
        });
    }

    /* Send test email Button */
    const emailTestBtn = document.getElementById('web357-microsoft-test-email');
    if (emailTestBtn) {
        emailTestBtn.addEventListener('click', function (e) {
            e.preventDefault();
            web357postForm(microsoftOutlookOptions.actionTestEmail, {
                email: document.getElementById('web357_microsoft_test_email').value
            });
        });
    }
});

/**
 * Submits a POST request to the specified URL with the provided data. The method dynamically
 * creates and submits a hidden form to send the data to the server.
 *
 * @param {string} url - The URL to which the form should be submitted.
 * @param {Object} [data={}] - An optional object of key-value pairs to include as form data.
 *                             Defaults to an empty object if not provided.
 * @return {void} This function does not return a value.
 */
function web357postForm(url, data = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';

    data[Joomla.getOptions('csrf.token')] = 1;
    // Add data as hidden inputs
    for (const key in data) {
        if (data.hasOwnProperty(key)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = data[key];
            form.appendChild(input);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

/**
 * Copies the text content of a specified HTML element to the clipboard
 * and temporarily updates the button content to indicate the successful
 * action, reverting back to the original content after 2 seconds.
 *
 * @param {string} elementId - The ID of the HTML element containing the text to copy.
 * @param {HTMLElement} button - The button element to update with a temporary feedback message.
 * @return {void} This function does not return a value.
 */
function web357MicrosoftOutlookCopyToClipboard(elementId, button) {
    var element = document.getElementById(elementId);
    element.select();
    document.execCommand('copy');

    // Store original content
    var originalContent = button.innerHTML;

    // Change to checkmark
    button.innerHTML = '<span class="icon-checkmark" aria-hidden="true"></span>';

    // Revert back after 2 seconds
    setTimeout(function () {
        button.innerHTML = originalContent;
    }, 2000);
}