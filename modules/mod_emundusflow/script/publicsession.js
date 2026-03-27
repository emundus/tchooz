window.addEventListener('DOMContentLoaded', (event) => {
    document.getElementById('renew-public-access-token').addEventListener('click', (e) => {
        console.log('click');
        fetch('/index.php?option=com_emundus&controller=application&task=renewApplicationAccessToken', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': Joomla.getOptions('csrf.token', '')
            },
        }).then(response => {
            // todo
            console.log(response);

        }).finally(() => {
            location.reload();
        });
    });
});