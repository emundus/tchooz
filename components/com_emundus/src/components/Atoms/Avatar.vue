<script>
export default {
	name: 'Avatar',
	props: {
		fullname: String,
		image: {
			type: String,
			default: null,
		},
		publishedTag: {
			type: Boolean,
			default: null,
		},
	},
	computed: {
		initials() {
			if (this.$props.fullname) {
				const names = this.$props.fullname.split(' ');
				return (
					(names[0] ? names[0].charAt(0) : '') + (names.length > 1 && names[1] ? names[1].charAt(0) : '')
				).toUpperCase();
			}
			return '';
		},

		randomColor() {
			const colors = ['green-700', 'blue-600'];
			return colors[Math.floor(Math.random() * colors.length)];
		},
	},
};
</script>

<template>
	<div
		class="tw-absolute tw-flex tw-h-24 tw-w-24 tw-items-center tw-justify-center tw-rounded-full tw-border tw-border-white tw-shadow"
		style="transform: translateY(-30vh); z-index: 1000000"
		:class="image ? 'tw-bg-white' : `tw-bg-${randomColor}`"
	>
		<img v-if="image" :src="image" class="object-cover tw-h-24 tw-w-24 tw-rounded-full" />
		<h1 v-else class="tw-text-white">
			{{ initials }}
		</h1>
		<span
			v-if="publishedTag !== null"
			class="tw-absolute tw-flex tw-flex-row tw-items-center tw-gap-2 tw-rounded-coordinator tw-px-2 tw-py-1 tw-text-sm tw-font-medium"
			:class="publishedTag ? 'em-bg-main-500 tw-text-white' : 'tw-bg-neutral-300 tw-text-neutral-800'"
			style="transform: translate(80%, 80%)"
		>
			{{
				publishedTag
					? translate('COM_EMUNDUS_ONBOARD_FILTER_PUBLISH')
					: translate('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH')
			}}
		</span>
	</div>
</template>

<style scoped></style>
