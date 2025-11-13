<template>
	<div :class="[bgColor, borderColor]" class="tw-rounded tw-border tw-px-5 tw-py-4">
		<div class="tw-flex tw-items-start tw-gap-2">
			<span v-if="displayIcon" :class="[iconType, iconColor]">{{ icon }}</span>
			<div class="tw-flex-1">
				<!-- Titre avec icône cliquable -->
				<div
					v-if="title && accordion"
					class="tw-flex tw-cursor-pointer tw-items-center tw-justify-between"
					@click="toggle"
				>
					<div class="tw-flex tw-w-full tw-items-center tw-justify-between tw-gap-1">
						<span class="tw-font-semibold" :class="[textColor]">{{ translate(title) }}</span>
						<span
							class="material-symbols-outlined tw-transition-transform tw-duration-200"
							:class="{ 'tw-rotate-90': isOpen }"
						>
							chevron_right
						</span>
					</div>
				</div>

				<!-- Titre simple sans accordéon -->
				<div v-else-if="title" class="tw-font-semibold" :class="[textColor]">
					{{ translate(title) }}
				</div>

				<!-- Contenu affiché ou non -->
				<slot name="content">
					<div
						v-if="!accordion || isOpen"
						v-html="textValueExtracted"
						:class="[textColor, title ? 'tw-mt-2' : '']"
					></div>
				</slot>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'Info',
	components: {},
	props: {
		text: {
			type: String,
		},
		title: {
			type: String,
			default: '',
		},
		accordion: {
			type: Boolean,
			default: false,
		},
		bgColor: {
			type: String,
			default: 'tw-bg-blue-50',
		},
		icon: {
			type: String,
			default: 'info',
		},
		iconColor: {
			type: String,
			default: 'tw-text-blue-500',
		},
		iconType: {
			type: String,
			default: 'material-symbols-outlined',
		},
		displayIcon: {
			type: Boolean,
			default: true,
		},
		textColor: {
			type: String,
			default: 'tw-text-neutral-900',
		},
	},

	mixins: [],

	data() {
		return {
			textValueExtracted: '',

			isOpen: false,
		};
	},
	created() {
		this.textValueExtracted = this.translate(this.text);
	},
	mounted() {},
	methods: {
		toggle() {
			this.isOpen = !this.isOpen;
		},
	},
	computed: {
		borderColor() {
			return this.iconColor.replace('text', 'border');
		},
	},
	watch: {
		text: function (val) {
			this.textValueExtracted = this.translate(val);
		},
	},
};
</script>

<style scoped></style>
