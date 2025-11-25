export default function conditionInstance(groupId) {
	return {
		id: Math.floor(Math.random() * 1000000000),
		group_id: groupId,
		target: null,
		type: null,
		operator: null,
		value: null,
	};
}
