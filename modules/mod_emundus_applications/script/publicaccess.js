window.addEventListener('DOMContentLoaded', (event) => {
    const importFileCard = document.getElementById('import_file');

    if (importFileCard)
    {
        importFileCard.addEventListener('click', function(e) {
            Swal.fire({
                title: Joomla.Text._('IMPORT_FILE_FROM_PUBLIC_ACCESS_TITLE'),
                icon: '',
                showCancelButton: true,
                confirmButtonText: Joomla.Text._('COM_EMUNDUS_IMPORT_FILE_CONFIRM') || 'Importer',
                cancelButtonText: Joomla.Text._('CANCEL') || 'Annuler',
                html:
                    '<p>'+ Joomla.Text._('IMPORT_FILE_FROM_PUBLIC_ACCESS_DESC') + '</p>' +
                    '<div style="text-align:left;margin-top:12px;">' +
                        '<label for="swal-input-fnum" style="display:block;margin-bottom:4px;font-weight:600;">' +
                            (Joomla.Text._('COM_EMUNDUS_FNUM_LABEL') || 'Numéro de dossier (fnum)') +
                        '</label>' +
                        '<input id="swal-input-fnum" class="swal2-input" placeholder="Ex: 1234-56-0001" style="width:100%;margin:0 0 16px 0;">' +
                        '<label for="swal-input-token" style="display:block;margin-bottom:4px;font-weight:600;">' +
                            (Joomla.Text._('COM_EMUNDUS_ACCESS_TOKEN_LABEL') || 'Token d\'accès') +
                        '</label>' +
                        '<input id="swal-input-token" class="swal2-input" placeholder="Collez votre token ici" style="width:100%;margin:0;">' +
                    '</div>',
                focusConfirm: false,
                customClass: {
                    title: 'em-swal-title',
                    cancelButton: 'em-swal-cancel-button',
                    confirmButton: 'em-swal-confirm-button',
                },
                reverseButtons: true,
                preConfirm: () => {
                    const fnum = document.getElementById('swal-input-fnum').value.trim();
                    const token = document.getElementById('swal-input-token').value.trim();

                    if (!fnum || !token) {
                        Swal.showValidationMessage(
                            Joomla.Text._('COM_EMUNDUS_IMPORT_FILE_FIELDS_REQUIRED') || 'Veuillez remplir les deux champs'
                        );
                        return false;
                    }

                    return { fnum: fnum, access_token: token };
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    // Build and submit a POST form with CSRF token
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = ''; // TODO
                    form.style.display = 'none';

                    const fnumInput = document.createElement('input');
                    fnumInput.type = 'hidden';
                    fnumInput.name = 'fnum';
                    fnumInput.value = result.value.fnum;
                    form.appendChild(fnumInput);

                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'access_token';
                    tokenInput.value = result.value.access_token;
                    form.appendChild(tokenInput);

                    // Joomla CSRF token
                    const csrfToken = Joomla.getOptions('csrf.token');

                    document.body.appendChild(form);
                    form.submit();
                }
            });

            e.stopPropagation();
        });
    }
});