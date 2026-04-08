window.addEventListener('DOMContentLoaded', (event) => {
    document.getElementById('emundus-application-file-actions').addEventListener('click', (e) => {
       let actionsContainer = document.getElementById('emundus-application-file-actions-container');

       if (actionsContainer)
       {
           if (actionsContainer.classList.contains('tw-hidden'))
           {
               actionsContainer.classList.remove('tw-hidden');
           }
           else
           {
               actionsContainer.classList.add('tw-hidden');
           }
       }
    });

    document.querySelectorAll('#emundus-application-file-actions-container .file-action').forEach((action) => {
        const actionId = action.getAttribute('id');

        // make sure action exists in Joomla.getOptions('mod_emundusflow.actions');
        const registeredActions = Joomla.getOptions('mod_emundusflow.actions');
        let foundAction = registeredActions.find((registeredAction) => {
            return registeredAction.name === actionId;
        });

        if (foundAction)
        {
            action.addEventListener('click', (e) => {
                const actionId = action.getAttribute('id');
                if (actionId === 'delete')
                {
                    Swal.fire({
                        title: foundAction.label,
                        text: Joomla.Text._('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_DELETE_CONFIRM'),
                        icon: 'warning',
                        showCancelButton: true,
                        reverseButtons: true,
                        confirmButtonText: Joomla.Text._('CONFIRM'),
                        cancelButtonText: Joomla.Text._('CANCEL'),
                        customClass: {
                            title: 'em-swal-title',
                            cancelButton: 'em-swal-cancel-button',
                            confirmButton: 'em-swal-confirm-button',
                        },
                    }).then((result) => {
                        if (result.isConfirmed)
                        {
                            executeAction(foundAction);
                        }
                    });
                }
                else if (foundAction.parameters && foundAction.parameters.length > 0)
                {
                    const formHtml = FieldToHtmlFactory.buildForm(foundAction.parameters);

                    Swal.fire({
                        title: foundAction.label,
                        html: formHtml,
                        showCancelButton: true,
                        reverseButtons: true,
                        confirmButtonText: Joomla.Text._('CONFIRM'),
                        cancelButtonText: Joomla.Text._('CANCEL'),
                        customClass: {
                            title: 'em-swal-title',
                            cancelButton: 'em-swal-cancel-button',
                            confirmButton: 'em-swal-confirm-button',
                        },
                        preConfirm: () => {
                            const popup = Swal.getPopup();
                            const error = FieldToHtmlFactory.validate(foundAction.parameters, popup);
                            if (error)
                            {
                                Swal.showValidationMessage(error);
                                return false;
                            }

                            return FieldToHtmlFactory.extractValues(foundAction.parameters, popup);
                        }
                    }).then((result) => {
                        if (result.isConfirmed)
                        {
                            executeAction(foundAction, result.value);
                        }
                    });
                }
                else
                {
                    executeAction(foundAction);
                }
            });
        }
        else
        {
            action.remove();
        }
    });

    // if actions container is open and user clicks outside of it, close it
    document.addEventListener('click', (e) => {
        let actionsContainer = document.getElementById('emundus-application-file-actions-container');
        let actionsButton = document.getElementById('emundus-application-file-actions');

        if (actionsContainer && !actionsContainer.classList.contains('tw-hidden') && !actionsContainer.contains(e.target) && !actionsButton.contains(e.target))
        {
            actionsContainer.classList.add('tw-hidden');
        }
    });

    function executeAction(action, params = {})
    {
        let formData = new FormData();
        formData.append('action', action.name);

        Object.keys(params).forEach((key) => {
           formData.append(key, params[key]);
        });

        fetch('/index.php?option=com_emundus&controller=application&task=executeApplicationAction', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': Joomla.getOptions('csrf.token', '')
            }
        }).then(response => response.json())
        .then(data => {
            if (data.status)
            {
                if (data.data.redirect)
                {
                    window.location.href = data.data.redirect;
                }
                else
                {
                    window.location.reload();
                }
            }
        }).catch(error => {

        });
    }
});