export default function targetInstance(type = 'file') {
	return {
		id: Date.now(),
		type: type,
		predefinition: null,
		conditions: [],
	};
}
