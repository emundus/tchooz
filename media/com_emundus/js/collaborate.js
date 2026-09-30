// Pages rendering the collaborators modal can live under nested aliases: relative URLs would break
function collaborateUrl(query) {
    const langPath = Joomla.getOptions('plg_system_emundus.language', {}).currentPath || '';
    return langPath + '/index.php?' + query;
}

function shareApplication(fnum, ccid) {
    const loader = document.querySelector('.em-page-loader');
    const userEmail = Joomla.getOptions('com_emundus.collaborate', {}).userEmail || '';

    if (loader) {
        loader.style.display = 'block';
    }

    fetch(collaborateUrl('option=com_emundus&view=application&layout=collaborate&format=raw&fnum=' + fnum + '&ccid=' + ccid), {
        method: 'get'
    }).then((response) => {
        if (response.ok) {
            return response.text();
        }
    }).then((res) => {
        if (loader) {
            loader.style.display = 'none';
        }

        const modalContent = document.createElement('div');
        modalContent.innerHTML = res;
        const canShare = modalContent.querySelector('#collaborate_modal_content')?.dataset.canShare === '1';

        Swal.fire({
            title: Joomla.Text._(canShare ? 'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_TITLE' : 'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_VIEW_TITLE'),
            html: res,
            showConfirmButton: canShare,
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_SEND'),
            cancelButtonText: Joomla.Text._(canShare ? 'MOD_EMUNDUS_APPLICATIONS_COLLABORATE_BACK' : 'JCLOSE'),
            customClass: {
                title: 'em-swal-title',
                cancelButton: 'em-swal-cancel-button',
                // em-swal-confirm-button forces display: flex !important, which defeats showConfirmButton: false
                confirmButton: canShare ? 'em-swal-confirm-button' : '',
                popup: '!w-3/6',
                validationMessage: 'em-swal-validation-message'
            },
            didOpen: () => {
                if (!canShare) {
                    return;
                }

                jQuery('#collab_emails').selectize({
                    plugins: ['remove_button'],
                    delimiter: ',',
                    persist: false,
                    createOnBlur: true,
                    create: true,
                    preload: true,
                    maxItems: null,
                    placeholder: Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ADD_EMAILPLACEHOLDER'),
                    render: {
                        create: function (input) {
                            return {
                                value: input,
                                text: input
                            };
                        },
                        item: function (data, escape) {
                            const val = data.value;
                            return '<div>' +
                                '<span class="title">' +
                                '<span class="name">' + escape(val.substring(val.indexOf(':') + 1)) + '</span>' +
                                '</span>' +
                                '</div>';
                        },
                        option_create: function (data, escape) {
                            return '<div class="create">' + Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ADD_EMAIL') + ' <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                        }
                    },
                    onItemAdd: function (value) {
                        if (document.querySelector('#collab_error')) {
                            document.querySelector('#collab_error').remove();
                        }

                        const email = value.substring(value.indexOf(':') + 1).trim();
                        const regex = /^\S{1,64}@\S{1,255}\.\S{1,255}$/;

                        if (!regex.test(email) || userEmail === email) {
                            this.removeItem(value);
                            let p = document.createElement('p');
                            p.classList.add('tw-text-red-500');
                            p.id = 'collab_error';
                            if (userEmail === email) {
                                p.innerText = Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_NOT_YOUR_OWN');
                            }
                            if (!regex.test(email)) {
                                p.innerText = Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_INVALID_EMAIL');
                            }
                            document.querySelector('#collab_emails_block').append(p);
                        }
                    }
                });
            },
            preConfirm: () => {
                if (document.querySelector('#collab_emails').value === '') {
                    Swal.showValidationMessage(Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_FILL_EMAILS'));
                }
            }
        }).then((result) => {
            if (result.value) {
                let formData = new FormData();

                formData.append('fnum', fnum);
                formData.append('ccid', ccid);
                formData.append('emails', document.querySelector('#collab_emails').value);

                Swal.fire({
                    title: Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_SUCCESS'),
                    iconHtml: '<img class="em-sending-email-img tw-w-1/3 tw-max-w-none" src="/media/com_emundus/images/tchoozy/complex-illustrations/sending-message.svg"/>',
                    showCancelButton: false,
                    showConfirmButton: false,
                    customClass: {
                        title: 'em-swal-title !tw-text-center',
                        cancelButton: 'em-swal-cancel-button',
                        confirmButton: 'em-swal-confirm-button',
                        icon: 'em-swal-icon'
                    },
                    timer: 3000
                });

                fetch(collaborateUrl('option=com_emundus&controller=application&task=sharefilewith'), {
                    body: formData,
                    method: 'post'
                }).then((response) => {
                    if (response.ok) {
                        return response.json();
                    } else {
                        return response.text().then((text) => {
                            throw new Error(text);
                        });
                    }
                }).then((res) => {
                    if (res.status != true) {
                        throw new Error(res.msg);
                    } else if (res.data.failed_emails.length > 0) {
                        throw new Error(Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_ERROR_EMAILS') + ' ' + res.data.failed_emails.join(', '));
                    } else {
                        Swal.fire({
                            title: Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_COLLABORATE_FINISH_SUCCESS'),
                            text: res.msg,
                            iconHtml: '<img class="em-sending-email-img tw-w-1/3 tw-max-w-none" src="/media/com_emundus/images/tchoozy/complex-illustrations/message-sent.svg"/>',
                            showCancelButton: false,
                            showConfirmButton: false,
                            customClass: {
                                title: 'em-swal-title !tw-text-center',
                                cancelButton: 'em-swal-cancel-button',
                                confirmButton: 'em-swal-confirm-button',
                                icon: 'em-swal-icon'
                            },
                            timer: 3000
                        });
                    }
                }).catch((error) => {
                    Swal.fire({
                        title: Joomla.Text._('MOD_EMUNDUS_APPLICATIONS_AN_ERROR_OCCURED'),
                        text: error,
                        type: 'error',
                        reverseButtons: true,
                        confirmButtonText: Joomla.Text._('JYES')
                    });
                });
            }
        });
    });
}

function removeShared (request_id, ccid, fnum) {
    //TODO: Ask confirmation before delete file request
    if (confirm(Joomla.Text._('COM_EMUNDUS_APPLICATION_SHARE_CONFIRM_DELETE')) == true) {
        let formData = new FormData();

        formData.append('request_id', request_id);
        formData.append('fnum', fnum);
        formData.append('ccid', ccid);

        fetch(collaborateUrl('option=com_emundus&controller=application&task=removeshareduser'), {
            body: formData,
            method: 'post',
        }).then((response) => {
            if (response.ok) {
                return response.json();
            }
        }).then((res) => {
            if (res.status) {
                document.querySelector('#collaborator_block_' + request_id).remove();
            }
        });
    }
}

function sendNewEmail(request_id, ccid, fnum) {
    let formData = new FormData();

    formData.append('request_id', request_id);
    formData.append('fnum', fnum);
    formData.append('ccid', ccid);

    document.getElementById('email_icon_'+request_id).innerHTML = 'sync';
    document.getElementById('email_icon_'+request_id).classList.add('tw-animate-spin');

    fetch(collaborateUrl('option=com_emundus&controller=application&task=sendnewcollaborationemail'), {
        body: formData,
        method: 'post',
    }).then((response) => {
        if (response.ok) {
            return response.json();
        }
    }).then((res) => {
        if(res.status) {
            Swal.showValidationMessage('<span class="material-symbols-outlined">mark_email_read </span>'+res.msg);

            document.getElementById('email_icon_'+request_id).classList.remove('tw-animate-spin');
            document.getElementById('email_icon_'+request_id).innerHTML = 'done';

            setTimeout(() => {
                Swal.resetValidationMessage();
                document.getElementById('email_icon_'+request_id).innerHTML = 'send';
            }, 4000);
        } else {
            Swal.showValidationMessage('<span class="material-symbols-outlined tw-text-red-500">error</span>'+res.msg);
            document.getElementById('email_icon_'+request_id).classList.remove('tw-animate-spin');
            document.getElementById('email_icon_'+request_id).innerHTML = 'send';

            setTimeout(() => {
                Swal.resetValidationMessage();
            }, 4000);
        }
    });
}

function toggleRequests() {
    let requests = document.querySelector('#collaborators_requests');
    let requestsIcon = document.querySelector('#requests_icon');
    if(requests.classList.contains('tw-hidden')) {
        requests.classList.remove('tw-hidden');
        requestsIcon.innerHTML = 'expand_more';
    } else {
        requests.classList.add('tw-hidden');
        requestsIcon.innerHTML = 'expand_less';
    }
}

function updateRight(request_id, ccid, fnum, right, value) {

    let formData = new FormData();
    formData.append('request_id', request_id);
    formData.append('fnum', fnum);
    formData.append('ccid', ccid);
    formData.append('right', right);
    formData.append('value', value);

    fetch(collaborateUrl('option=com_emundus&controller=application&task=updateright'), {
        body: formData,
        method: 'post',
    }).then((response) => {
        if (response.ok) {
            return response.json();
        }
    }).then((res) => {
        if(res.status) {
            Swal.showValidationMessage(res.msg);

            setTimeout(() => {
                Swal.resetValidationMessage();
            }, 2000);
        }
    });
}