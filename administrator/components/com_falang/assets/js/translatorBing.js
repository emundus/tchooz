function translateService(fieldName,sourceText){

	var translatedText;

    var data = [{
        text:sourceText
    }];

    var endpoint = "https://api.cognitive.microsofttranslator.com/translate?api-version=3.0";
    endpoint = endpoint + '&from='+translator.from;
    endpoint = endpoint + '&to='+translator.to;
    endpoint = endpoint + '&textType=html';

	jQuery.ajax({
        url: endpoint,
        dataType: 'json',
        headers:  {
            'Ocp-Apim-Subscription-Key': azureKey,
            'Content-Type':'application/json; charset=UTF-8'
        },
        data: JSON.stringify(data),
        type: 'POST',
        beforeSend: function() {
            document.body.style.cursor = 'wait';
        },
        success: function (result) {
            console.log(result);
            translatedText = result[0]['translations'][0].text;
            setTranslation(fieldName,translatedText);
		},
		error: function (xhr, textStatus, errorThrown) {
			translatedText = "ERROR : "+errorThrown;
		},
        complete: function() {
            document.body.style.cursor = 'default';
        }
	});
      
	return translatedText;
}



