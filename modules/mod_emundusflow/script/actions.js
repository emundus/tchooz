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

    // if actions container is open and user clicks outside of it, close it
    document.addEventListener('click', (e) => {
        let actionsContainer = document.getElementById('emundus-application-file-actions-container');
        let actionsButton = document.getElementById('emundus-application-file-actions');

        if (actionsContainer && !actionsContainer.classList.contains('tw-hidden') && !actionsContainer.contains(e.target) && !actionsButton.contains(e.target))
        {
            actionsContainer.classList.add('tw-hidden');
        }
    });

    document.querySelectorAll('#emundus-application-file-actions-container .file-action').forEach((action) => {
        action.addEventListener('click', (e) => {
            const actionId = action.id;

            console.log('click', actionId);
        })
    });
});