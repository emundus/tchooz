export default function targetInstance(type = 'file') {
	return {
		id: Math.floor(Math.random() * 1000000000),
		type: type,
		predefinition: null,
		conditions: [],
	};
}