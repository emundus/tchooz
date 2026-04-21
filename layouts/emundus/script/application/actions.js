window.addEventListener('DOMContentLoaded', (event) => {

    function moveContainerToBody(container) {
        if (container.dataset.teleported === '1') return;
        document.body.appendChild(container);
        container.dataset.teleported = '1';
    }

    function positionContainer(trigger, container) {
        const rect = trigger.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();

        // align right edge of container with right edge of trigger, just below
        let top = rect.bottom + 4;
        let left = rect.right - containerRect.width;

        // viewport guards
        if (left < 8) left = 8;
        if (top + containerRect.height > window.innerHeight) {
            top = rect.top - containerRect.height - 4;
        }

        container.style.top = `${top}px`;
        container.style.left = `${left}px`;
    }

    document.querySelectorAll('.emundus-application-file-actions').forEach((trigger) => {
        const wrapper = trigger.closest('.emundus-application-file-actions-wrapper');
        if (!wrapper) return;
        const container = wrapper.querySelector('.emundus-application-file-actions-container');
        if (!container) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            moveContainerToBody(container);

            // close all other open containers
            document.querySelectorAll('.emundus-application-file-actions-container').forEach((c) => {
                if (c !== container) c.classList.add('tw-hidden');
            });

            const willOpen = container.classList.contains('tw-hidden');
            if (willOpen)
            {
                container.classList.remove('tw-hidden');
            }
            else
            {
                container.classList.add('tw-hidden');
            }

            if (willOpen) positionContainer(trigger, container);
        });

        // reposition on scroll/resize when open
        const reposition = () => {
            if (!container.classList.contains('tw-hidden')) {
                positionContainer(trigger, container);
            }
        };
        window.addEventListener('scroll', reposition, true);
        window.addEventListener('resize', reposition);
    });

    document.querySelectorAll('.emundus-application-file-actions-container .file-action').forEach((action) => {
        const actionId = action.getAttribute('data-actionid');
        const fnum = action.getAttribute('data-fnum');

        if (fnum.length < 1)
        {
            return;
        }

        // make sure action exists in Joomla.getOptions('mod_emundusflow.actions');
        const registeredActions = Joomla.getOptions('layout.emundus.actions.' + fnum);
        let foundAction = registeredActions.find((registeredAction) => {
            return registeredAction.name === actionId;
        });

        if (foundAction)
        {
            action.addEventListener('click', (e) => {
                const actionId = action.getAttribute('data-actionid');

                if (foundAction.confirmBeforeExecute)
                {
                    Swal.fire({
                        title: foundAction.label,
                        text: Joomla.Text._('COM_EMUNDUS_APPLICATION_FILE_ACTIONS_' + foundAction.name.toUpperCase() + '_CONFIRM'),
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
                            executeAction(foundAction, fnum);
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
                            executeAction(foundAction, fnum, result.value);
                        }
                    });
                }
                else
                {
                    executeAction(foundAction, fnum);
                }
            });
        }
        else
        {
            action.remove();
        }
    });

    // close container on click outside
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.emundus-application-file-actions-container').forEach((container) => {
            if (container.classList.contains('tw-hidden')) return;
            const fnum = container.dataset.fnum;
            const trigger = document.querySelector(
                `.emundus-application-file-actions-wrapper[data-fnum="${fnum}"] .emundus-application-file-actions`
            );
            if (!container.contains(e.target) && trigger && !trigger.contains(e.target))
            {
                container.classList.add('tw-hidden');
            }
        });
    });

    function executeAction(action, fnum, params = {})
    {
        let formData = new FormData();
        formData.append('action', action.name);

        Object.keys(params).forEach((key) => {
           formData.append(key, params[key]);
        });

        formData.append('fnum', fnum);

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
