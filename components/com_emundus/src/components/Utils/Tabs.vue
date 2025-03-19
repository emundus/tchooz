<template>
	<div :class="classes">
		<div v-for="tab in currentTabs" :key="tab.id">
			<div
				v-show="tab.displayed"
				@click="changeTab(tab.id)"
				class="tw-flex tw-cursor-pointer tw-items-center tw-rounded-t-lg tw-border-x tw-border-t tw-px-4 tw-py-2 tw-transition-colors tw-duration-300"
				:class="{
					'tw-border-profile-full tw-bg-white': tab.active,
					'tw-border-neutral-400 tw-bg-neutral-200': !tab.active,
					'tw-border-neutral-600 tw-bg-neutral-400': tab.disabled,
				}"
			>
				<span
					class="material-symbols-outlined tw-mr-2"
					:class="tab.active ? 'tw-text-profile-full' : 'tw-text-neutral-700'"
					>{{ tab.icon }}</span
				>
				<span
					:class="tab.active ? 'tw-text-profile-full' : 'tw-text-neutral-700'"
					class="em-profile-font tw-whitespace-nowrap"
					>{{ translate(tab.name) }}</span
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
	},
};
</script>

<style scoped></style>
