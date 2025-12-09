export default function parameterGroupInstance(id, title, intro, parameters) {
	return {
		id: null,
		title: null,
		intro: null,
		parameters: parameters || [],
	};
}
