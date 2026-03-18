<script>
export default {
	name: 'Tag',
	props: {
		text: {
			type: String,
			default: '',
		},
		bgColorClass: {
			type: String,
			default: 'em-bg-main-500',
		},
		textColorClass: {
			type: String,
			default: 'tw-text-white',
		},
	},
	computed: {
		initials() {
			if (this.text) {
				return this.text
					.split(' ')
					.map((word) => word.charAt(0).toUpperCase())
					.join('')
					.substring(0, this.initialsLength);
			}
		},
	},
};
</script>

<template>
	<div
		class="tw-mr-2 tw-flex tw-h-max tw-flex-row tw-items-center tw-gap-2 tw-rounded-coordinator tw-px-2 tw-py-1 tw-text-sm tw-font-medium"
		:class="[bgColorClass, textColorClass]"
	>
		<span>{{ text }}</span>
	</div>
</template>

<style scoped></style>
