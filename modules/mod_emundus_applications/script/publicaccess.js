window.addEventListener('DOMContentLoaded', (event) => {
    const importFileCards = document.querySelectorAll('#import_file');

    importFileCards.forEach(importFileCard => {
        if (importFileCard)
        {
            importFileCard.addEventListener('click', function(e) {
                Swal.fire({
                    title: Joomla.Text._('IMPORT_FILE_FROM_PUBLIC_ACCESS_TITLE'),
                    icon: '',
                    showCancelButton: true,
                    confirmButtonText: Joomla.Text._('IMPORT_FILE_FROM_PUBLIC_ACCESS_BUTTON') || 'Importer',
                    cancelButtonText: Joomla.Text._('CANCEL') || 'Annuler',
                    html:
                        '<p>'+ Joomla.Text._('IMPORT_FILE_FROM_PUBLIC_ACCESS_DESC') + '</p>' +
                        '<div class="tw-flex tw-flex-col tw-gap-4">' +
                        '<div><label for="swal-input-token">' +
                        (Joomla.Text._('COM_EMUNDUS_ACCESS_KEY_LABEL')) +
                        '</label>' +
                        '<input id="swal-input-token" type="password" class="swal2-input" placeholder="' + Joomla.Text._('COM_EMUNDUS_ACCESS_KEY_LABEL_PLACEHOLDER') + '"></div>' +
                        '</div>',
                    focusConfirm: false,
                    customClass: {
                        title: 'em-swal-title',
                        cancelButton: 'em-swal-cancel-button',
                        confirmButton: 'em-swal-confirm-button',
                    },
                    reverseButtons: true,
                    preConfirm: () => {
                        const token = document.getElementById('swal-input-token').value.trim();

                        if (!token) {
                            Swal.showValidationMessage(
                                Joomla.Text._('COM_EMUNDUS_IMPORT_FILE_FIELDS_REQUIRED') || 'Veuillez remplir les champs'
                            );
                            return false;
                        }

                        return { access_token: token };
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const body = new FormData();
                        body.append('token', result.value.access_token);

                        fetch('/index.php?option=com_emundus&controller=application&task=updateOwnerPublicAccessFile', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-Token': Joomla.getOptions('csrf.token', '')
                            },
                            body: body
                        }).then(response => {
                            // todo

                        }).finally(() => {
                            // reload current page
                            location.reload();
                        });
                    }
                });

                e.stopPropagation();
            });
        }
    });
});