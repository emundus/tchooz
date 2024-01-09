function removeShared (request_id, ccid, fnum) {
    //TODO: Ask confirmation before delete file request
    let formData = new FormData();

    formData.append('request_id', request_id);
    formData.append('fnum', fnum);
    formData.append('ccid', ccid);

    fetch('index.php?option=com_emundus&controller=application&task=removeshareduser', {
        body: formData,
        method: 'post',
    }).then((response) => {
        if (response.ok) {
            return response.json();
        }
    }).then((res) => {
        if(res.status) {
            document.querySelector('#collaborator_block_'+request_id).remove();
        }
    });
}