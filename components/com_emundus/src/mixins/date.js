export default {
	methods: {
		formattedDate(stringDate, lang = 'fr-FR') {
			let formattedDate = '';
			const date = Date.parse(stringDate);

			if (date !== null) {
				formattedDate = Intl.DateTimeFormat(lang, {
					year: 'numeric',
					month: 'numeric',
					day: 'numeric',
				}).format(date);
			}

			return formattedDate;
		},

		formatDateForCampaign(dateString) {
			const date = new Date(dateString);
			const day = String(date.getDate()).padStart(2, '0');
			const month = String(date.getMonth() + 1).padStart(2, '0');
			const year = date.getFullYear();
			const hours = String(date.getHours()).padStart(2, '0');
			const minutes = String(date.getMinutes()).padStart(2, '0');
			return `${day}/${month}/${year} à ${hours}h${minutes}`;
		},

		formatSlotDay(dateString) {
			if (!dateString) {
				return '';
			}

			const [date] = dateString.split(' ');
			const [year, month, day] = date.split('-');
			return `${day}/${month}/${year}`;
		},

		formatSlotTime(dateString) {
			if (!dateString) {
				return '';
			}

			const [, time] = dateString.split(' ');
			if (!time) {
				return '';
			}

			const [hour, minute] = time.split(':');
			return `${parseInt(hour, 10)}h${minute}`;
		},

		formatSlotTimeRange(startString, endString) {
			if (!startString || !endString) {
				return '';
			}

			return `${this.formatSlotTime(startString)} - ${this.formatSlotTime(endString)}`;
		},

		convertOldDateTimeStringToZonedDateTime(dateString) {
			const [date, time] = dateString.split(' ');
			const [year, month, day] = date.split('-');
			const [hour, minute] = time.split(':');
			return Temporal.ZonedDateTime.from({
				year: parseInt(year),
				month: parseInt(month),
				day: parseInt(day),
				hour: parseInt(hour),
				minute: parseInt(minute),
				timeZone: 'UTC',
			});
		},
	},
};
