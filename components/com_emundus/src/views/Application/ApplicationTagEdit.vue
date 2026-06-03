<script>
import EditTag from '@/components/Settings/Files/EditTag.vue';

export default {
	name: 'ApplicationTagEdit',
	emits: ['saved', 'close'],
	components: { EditTag },
	data() {
		return {
			tag: {
				id: 0,
				label: '',
				color: 'label-default',
				category: '',
				ordering: 0,
			},
		};
	},
	methods: {
		close() {
			this.$emit('close');
		},
		onSaved(tagId) {
			this.tag = {
				id: 0,
				label: '',
				color: 'label-default',
				category: '',
				ordering: 0,
			};

			this.$emit('saved', tagId);
		},
	},
};
</script>

<template>
	<EditTag :tag="tag" @saved="onSaved" @close="close" />
</template>

<style scoped></style>
