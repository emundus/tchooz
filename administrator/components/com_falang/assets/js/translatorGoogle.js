function translateService(fieldName,sourceText){

    var translatedText;

    //language iso-639-1
    //https://cloud.google.com/translate/docs/languages
    var data = {
        key: googleKey,
        source: translator.from,
        target: translator.to,
        format: 'html',
        q: sourceText
    };

    var endpoint = " https://translation.googleapis.com/language/translate/v2"

    jQuery.ajax({
        type: 'POST',
        url: endpoint,
        dataType: 'json',
        data : data,
        beforeSend: function() {
            document.body.style.cursor = 'wait';
        },
        success: function (result) {
            console.log(result);
            translatedText = result.data.translations[0].translatedText;
            setTranslation(fieldName,translatedText);
        },
        error: function (xhr, textStatus, errorThrown) {
            console.log(xhr);
            translatedText = "ERROR "+xhr.responseJSON["code"]+": "+xhr.responseJSON["message"];
        },
        complete: function() {
            document.body.style.cursor = 'default';
        }
    });

    return translatedText;
}
