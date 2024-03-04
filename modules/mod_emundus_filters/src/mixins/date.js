export default {
    methods: {
        formattedDate(stringDate, lang =  'fr-FR') {
            let formattedDate = '';
            const date = Date.parse(stringDate);

            if (date !== null) {
                formattedDate = Intl.DateTimeFormat(lang, {   
                    year: 'numeric',
                    month: 'numeric',
                    day: 'numeric'
                }).format(date);
            }

            return formattedDate;
        },
    }
};