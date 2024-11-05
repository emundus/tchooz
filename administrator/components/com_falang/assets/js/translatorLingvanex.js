function translateService(fieldName,sourceText){
	var translatedText;

    var data = {
		    from: translator.from,
		    to:  translator.to,
            format: 'html',
            platform: 'api',
            text: sourceText
        };


	jQuery.ajax({
        url: "https://api-b2b.backenster.com/b1/api/v3/translate",
        dataType: 'json',
        headers:  {
            'authorization': 'Bearer '+LingvanexKey,
            'Content-Type':'application/x-www-form-urlencoded'
        },
        data: data,
        type: 'POST',
        beforeSend: function() {
            document.body.style.cursor = 'wait';
        },
		success: function (response) {
			translatedText = response.result;
            setTranslation(fieldName,translatedText);
		},
		error: function (xhr) {
			translatedText = "ERROR : "+xhr.responseJSON["err"];
		},
        complete: function() {
            document.body.style.cursor = 'default';
        }
	});
      
	return translatedText;
}