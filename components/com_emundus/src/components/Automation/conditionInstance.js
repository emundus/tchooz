export default function conditionInstance(groupId) {
	return {
		id: groupId + 1,
		group_id: groupId,
		target: null,
		type: null,
		operator: null,
		value: null,
	};
}
