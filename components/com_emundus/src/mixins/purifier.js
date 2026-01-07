import DOMPurify from 'dompurify';

export default {
	methods: {
		cleanHTML(dirty) {
			return DOMPurify.sanitize(dirty);
		},
	},
};
