function cleanText(html) {

    return html.replace(/<br\s*\/?>/gi, '\n')
        .replace(/<p>/gi, '\r')
        .replace(/&nbsp;/g, ' ')
        .replace(/<[^>]+>/g, '').trim()
        .replace(/\n+/g, '\n');
}

if (rows) {
    const firstRow = Object.values(rows)[0];
    const fnum = firstRow.jos_emundus_files_request___fnum;
    const keyid = firstRow.jos_emundus_files_request___keyid;

    fetch('/index.php?option=com_emundus&controller=email&task=getemailcontent&tmp=8&fnum=' + fnum + '&keyid=' + keyid)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if(data.status) {
                const emailContent = data.data.message || "No email content found.";
                const link = data.data.link || "No link found.";
                const referentEmail = data.data.referent_email || "No referent email found.";
                const translations = data.data.translations || "No translations found";

                Swal.fire({
                    title: translations.title,
                    html: `
                    <div>
                        <label class="tw-text-neutral-900 !tw-font-semibold tw-flex tw-items-center">${translations.referentEmailLabel}</label>
                        <p><a href="mailto:${referentEmail}" target="_blank" id="referentEmail">${referentEmail}</a></p>
                        <hr/>
                        <label class="tw-text-neutral-900 !tw-font-semibold">${translations.linkLabel}</label>
                        <p><a href="${link}" target="_blank">${link}</a></p>
                        <hr/>
                        <label class="tw-text-neutral-900 !tw-font-semibold">${translations.emailContent}</label>
                        <p id="emailContentText">${emailContent}</p>
                    </div>
                `,
                    showConfirmButton: true,
                    showCancelButton: true,
                    confirmButtonText: translations.copy,
                    cancelButtonText: translations.close,
                    reverseButtons: true,
                    customClass: {
                        title: 'em-swal-title',
                        confirmButton: 'em-swal-confirm-button',
                        cancelButton: 'em-swal-cancel-button'
                    },
                    preConfirm: () => {
                        const cleanedContent = cleanText(emailContent);
                        if (cleanedContent) {
                            return navigator.clipboard.writeText(cleanedContent);
                        }
                    }
                }).then((value) => {
                    if (value.isConfirmed) {
                        Swal.fire({
                            title: translations.copied,
                            showConfirmButton: false,
                            icon: 'success',
                            customClass: {
                                title: 'em-swal-title',
                                confirmButton: 'em-swal-confirm-button'
                            },
                            timer: 1500
                        });
                    }
                })
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.msg,
                    icon: 'error',
                    customClass: {
                        title: 'em-swal-title',
                        confirmButton: 'em-swal-confirm-button'
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

