<script>
import History from '@/views/History.vue';
import Tabs from '@/components/Utils/Tabs.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import Info from '@/components/Utils/Info.vue';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';
import settingsService from '@/services/settings.js';
import alerts from '@/mixins/alerts.js';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';

export default {
	name: 'AddonSetup',
	components: { ParameterForm, History, Tabs, Parameter, Info },
	props: {
		name: {
			type: String,
			required: true,
		},
		addon: {
			type: Object,
			required: true,
		},
	},
	mixins: [transformIntoParameterField, alerts],
	data() {
		return {
			selectedTab: null,
			tabs: [],
			loading: true,
		};
	},
	created() {
		if (this.addon.parameters) {
			this.addon.parameters.forEach((parameter) => {
				const vueParameter = this.fromFieldEntityToParameter(parameter);

				vueParameter.value = this.addon.params[parameter.group.name][parameter.name] ?? '';

				const tab = this.tabs.find((tab) => tab.id === parameter.group.name);
				if (!tab) {
					this.tabs.push({
						id: parameter.group.name,
						name: parameter.group.label,
						parameters: [vueParameter],
						active: this.tabs.length === 0,
						disabled: false,
						displayed: true,
					});
				} else {
					tab.parameters.push(vueParameter);
				}
			});

			this.selectedTab = this.tabs.find((tab) => tab.active);
			this.loading = false;
		}
	},
	methods: {
		onSelectTab(tabId) {
			this.selectedTab = this.tabs.find((tab) => tab.id === tabId);
		},
		saveAddon() {
			let config = {};

			this.selectedTab.parameters.forEach((parameter) => {
				const addonParameter = this.addon.parameters.find((p) => p.name === parameter.param);

				if (!config[addonParameter.group.name]) {
					config[addonParameter.group.name] = {};
				}

				config[addonParameter.group.name][addonParameter.name] = parameter.value;
			});

			settingsService.setupAddon(this.addon.namekey, config).then((response) => {
				if (response.status) {
					this.alertSuccess(response.msg);
				} else {
					this.alertError(response.msg);
				}
			});
		},
	},
};
</script>

<template>
	<div :id="addon.namekey">
		<div v-if="!loading">
			<Tabs
				:tabs="tabs"
				@changeTabActive="onSelectTab"
				:classes="'tw-overflow-x-auto tw-absolute tw-right-6 tw-flex tw-items-center tw-justify-end tw-gap-2 tw-top-[59px] tw-right-[50px]'"
			/>
			<div
				v-if="selectedTab"
				class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-coordinator tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
			>
				<ParameterForm :groups="[selectedTab]" />
				<div class="tw-mt-4 tw-flex tw-w-full tw-justify-end">
					<button class="tw-btn-primary" @click="saveAddon">
						{{ translate('SAVE') }}
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
