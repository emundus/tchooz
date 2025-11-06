import conditionInstance from '@/components/Automation/conditionInstance.js';

export function newConditionGroup(parentId = null) {
	const groupId = Math.floor(Math.random() * 1000000000);

	return {
		id: groupId,
		parent_id: parentId,
		conditions: [],
		operator: 'AND',
	};
}
