/**
 * Action Element
 *
 * @copyright: Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license: GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
    window.FbAction = new Class({
        Extends   : FbElement,
        initialize: function (element, options) {
            this.setPlugin('fabrikAction');
            this.parent(element, options);

            this.addClickEvent('click', options);
        },

        addClickEvent: function (action, options) {
            jQuery(this.element).on(action, (e) => {
                // Unlike element addNewEventAux we need to stop the event otherwise the form is submitted
                if (e) {
                    e.stopPropagation();
                }

                var loader = document.querySelector('#action_loader_' + this.element.id);
                if(loader)
                {
                    loader.style.display = 'inline-block';
                }

                var data = {
                    type: options.type,
                    options: JSON.stringify(options.options),
                };
                var formData = new FormData();
                for (var key in data) {
                    formData.append(key, data[key]);
                }

                var parameters = {
                    method: 'POST',
                    body: formData,
                };

                fetch('/index.php?option=com_emundus&controller=automation&task=runaction', parameters).then((response) => {
                    if(loader)
                    {
                        loader.style.display = 'none';
                    }

                    if(response.ok)
                    {
                        return response.json();
                    }
                }).then((data) => {
                    this.manageActionResult(data, options.type);
                });
            });
        },

        manageActionResult: function(data, type) {
            if(type === 'generate_letter')
            {
                console.log(data.data[0]);

                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: Joomla.Text._('PLG_ELEMENT_ACTION_GENERATE_LETTER_SUCCESS_TITLE'),
                    text: Joomla.Text._('PLG_ELEMENT_ACTION_GENERATE_LETTER_SUCCESS_MSG'),
                    showConfirmButton: true,
                    confirmButtonText: Joomla.Text._('PLG_ELEMENT_ACTION_GENERATE_LETTER_DOWNLOAD'),
                    reverseButtons: true,
                    customClass: {
                        title: 'em-swal-title',
                        confirmButton: 'em-swal-confirm-button',
                        actions: 'em-swal-single-action'
                    },
                }).then((result_sw) => {
                    if (result_sw.isConfirmed) {
                        window.open(data.data[0].url + data.data[0].filename, '_blank');
                    }
                });
            }
        }
    });

    return window.FbAction;
});