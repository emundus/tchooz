<template>
	<div
		:class="[
			classes.length > 0 ? classes : '',
			{
				'tw-m-auto tw-mt-4 tw-flex tw-w-fit tw-flex-row tw-items-center tw-justify-center tw-gap-1 tw-rounded-full tw-bg-profile-full tw-p-1':
					template === 'toggle',
			},
		]"
	>
		<div v-for="tab in displayedTabs" :key="tab.id">
			<div
				@click="changeTab(tab.id)"
				class="tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-transition"
				:class="{
					'tw-rounded-t-lg tw-border-x tw-border-t tw-px-4 tw-py-2': template === 'default',
					'tw-bg-white': tab.active,
					'tw-border-profile-full': template === 'default' && tab.active,
					'tw-border-neutral-400 tw-bg-neutral-200': !tab.active && template === 'default',
					'tw-border-neutral-600 tw-bg-neutral-400': tab.disabled && template === 'default',

					'tw-rounded-full tw-px-2 tw-py-1': template === 'toggle',
					'tw-text-profile-full': template === 'toggle' && tab.active,
					'tw-text-white': template === 'toggle' && !tab.active,
				}"
			>
				<span
					v-if="tab.icon"
					class="material-symbols-outlined"
					:class="{
						'tw-text-profile-full': tab.active,
						'tw-text-neutral-700': !tab.active && template === 'default',
						'tw-text-neutral-400': tab.disabled && template === 'default',

						'tw-text-white': template === 'toggle' && !tab.active,
					}"
					>{{ tab.icon }}</span
				>
				<span
					:class="{
						'tw-text-profile-full': tab.active,
						'tw-text-neutral-700': !tab.active && template === 'default',
						'tw-text-neutral-400': tab.disabled && template === 'default',

						'tw-text-white': template === 'toggle' && !tab.active,
					}"
					class="em-profile-font tw-whitespace-nowrap"
					>{{ tabName(tab) }}</span
				>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	name: 'Tabs',
	emits: ['click-disabled-tab', 'changeTabActive'],
	props: {
		tabs: {
			type: Array,
			required: true,
		},
		classes: {
			type: String,
			default:
				'tw-overflow-x-auto tw-absolute tw-right-6 tw-flex tw-items-center tw-justify-end tw-gap-2 -tw-top-[42px]',
		},
		context: {
			type: String,
			default: '',
		},
		template: {
			type: String,
			default: 'default',
		},
	},

	data() {
		return {
			currentTabs: [],
		};
	},
	created() {
		this.currentTabs = this.tabs;

		// Get active tab from sessionStorage
		if (this.$props.context && this.$props.context !== '') {
			let selectedTab = sessionStorage.getItem(
				'tchooz_selected_tab/' + this.$props.context + '/' + document.location.hostname,
			);

			if (selectedTab) {
				this.changeTab(selectedTab);
			}
		}
	},
	methods: {
		changeTab(id) {
			let tab = this.currentTabs.find((tab) => tab.id == id);

			if (tab) {
				if (!tab.disabled) {
					for (const tab of this.currentTabs) {
						tab.active = tab.id == id;
					}

					if (this.$props.context && this.$props.context !== '') {
						sessionStorage.setItem('tchooz_selected_tab/' + this.$props.context + '/' + document.location.hostname, id);
					}

					this.$emit('changeTabActive', id);
				} else {
					this.$emit('click-disabled-tab', tab);
				}
			}
		},
		tabName(tab) {
			let tabName = this.translate(tab.name);
			if (tab.suffix) {
				tabName += ' ' + tab.suffix;
			}

			return tabName;
		},
	},
	computed: {
		displayedTabs() {
			return this.currentTabs.filter((tab) => tab.displayed);
		},
	},
};
</script>

<style scoped></style>
