function translateService(fieldName,sourceText){
	var translatedText;

    var data = {
            key: YandexKey,
            lang: translator.from + '-' + translator.to,
            format: 'html',
            text: sourceText
        };

	jQuery.ajax({
        url: "https://translate.yandex.net/api/v1.5/tr.json/translate",
        dataType: 'json',
        data: data,
		beforeSend: function() {
			document.body.style.cursor = 'wait';
		},
		success: function (result) {
			translatedText = result.text[0];
			setTranslation(fieldName,translatedText);
		},
		error: function (xhr) {
			translatedText = "ERROR "+xhr.responseJSON["code"]+": "+xhr.responseJSON["message"];
		},
		complete: function() {
			document.body.style.cursor = 'default';
		}
	});
      
	return translatedText;
}