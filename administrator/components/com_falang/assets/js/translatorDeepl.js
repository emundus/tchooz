function translateService(sourceText){

    var translatedText;

    var data = {
        service: 'deepl',
        source: translator.from,
        target: translator.to,
        format: 'html',
        text: [sourceText]
    };

    var ajaxurl  = "index.php?option=com_falang&task=translate.service&translator=deepl&format=raw&tmpl=component";

    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        dataType: 'json',
        data : data,
        beforeSend: function() {
            document.body.style.cursor = 'wait';
        },
        success: function (result) {
            data = JSON.parse(result.data);
            if (result.success) {
                translatedText = data.translations[0].text;
            } else {
                console.log('Error : '+ data);
                translatedText = '--error--';
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            translatedText = "ERROR "+xhr.responseJSON["code"]+": "+xhr.responseJSON["message"];
        },
        complete: function() {
            document.body.style.cursor = 'default';
        },
        async:false
    });

    return translatedText;
}
