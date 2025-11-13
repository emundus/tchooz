export default {
	methods: {
		truncateText(text, maxLength) {
			if (text) {
				if (text.length <= maxLength) {
					return text;
				}
				return text.substring(0, maxLength) + '...';
			}

			return '';
		},
	},
};
