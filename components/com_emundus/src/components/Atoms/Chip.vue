<script>
export default {
	name: 'Chip',
	props: {
		text: {
			type: String,
			default: '',
		},
		image: {
			type: String,
			default: '',
		},
		imageAltText: {
			type: String,
			default: 'Image',
		},
		initialsLength: {
			type: Number,
			default: 2,
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
		class="tw-mb-2 tw-mr-2 tw-flex tw-h-max tw-w-fit tw-items-center tw-rounded-full tw-border tw-border-neutral-500 tw-bg-neutral-400 tw-px-2 tw-py-1 tw-text-sm tw-font-semibold hover:tw-font-semibold"
	>
		<img
			v-if="image"
			:src="image"
			:alt="imageAltText"
			class="tw-mr-1 tw-h-7 tw-w-7 tw-rounded-full tw-object-contain"
		/>
		<div
			v-else
			class="tw-mr-2 tw-flex tw-h-7 tw-w-7 tw-shrink-0 tw-items-center tw-justify-center tw-rounded-full tw-bg-neutral-600 tw-text-sm tw-font-bold tw-text-white"
		>
			{{ initials }}
		</div>
		<span>{{ text }}</span>
	</div>
</template>

<style scoped></style>
