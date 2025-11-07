<template>
	<div
		id="analytics-settings"
		class="tw-mb-6 tw-w-full tw-rounded-coordinator-cards tw-border tw-border-gray-200 tw-bg-neutral-0 tw-p-5 tw-shadow"
	>
		<h1>{{ translate('COM_EMUNDUS_GLOBAL_PARAMS_MENUS_ANALYTICS') }}</h1>

		<Info text="COM_EMUNDUS_SETTINGS_ANALYTICS_INFO" class="tw-mt-2" />

		<div class="tw-mt-4 tw-w-fit" v-if="!loading">
			<div v-for="field in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
				<Parameter
					:ref="'analytics_' + field.param"
					:parameter-object="field"
					:help-text-type="'above'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					@valueUpdated="toggleAnalytics"
				/>
			</div>

			<div
				v-if="fields[0].value == 1"
				class="tw-mt-6 tw-w-fit tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-p-4 tw-shadow-card"
			>
				<div class="tw-mb-2 tw-flex tw-items-center tw-gap-4">
					<h2>{{ translate('COM_EMUNDUS_SETTINGS_ANALYTICS_COUNT_PAGES_VISITED') }}</h2>
					<select
						v-model="period"
						@change="getPagesVisited"
						class="tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-px-2 tw-py-1"
					>
						<option v-for="option in periods" :key="option.value" :value="option.value">
							{{ option.text }}
						</option>
					</select>
				</div>

				<div class="tw-text-5xl tw-font-bold tw-text-profile-full">
					{{ countPagesVisited }}
				</div>
			</div>
		</div>

		<div class="em-page-loader" v-if="loading" />
	</div>
</template>

<script>
import Parameter from '@/components/Utils/Parameter.vue';
import Info from '@/components/Utils/Info.vue';
import settingsService from '@/services/settings';

export default {
	name: 'AnalyticsSettings',
	components: { Info, Parameter },
	data() {
		return {
			loading: true,

			fields: [
				{
					param: 'analytics_enabled',
					type: 'toggle',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_ANALYTCS_ENABLED',
					displayed: true,
					hideLabel: true,
					optional: true,
				},
			],

			period: 'all_time',
			periods: [
				{ value: 'all_time', text: this.translate('COM_EMUNDUS_SETTINGS_ANALYTICS_PERIOD_ALL_TIME') },
				{ value: 'yearly', text: this.translate('COM_EMUNDUS_SETTINGS_ANALYTICS_PERIOD_YEAR') },
				{ value: 'monthly', text: this.translate('COM_EMUNDUS_SETTINGS_ANALYTICS_PERIOD_LAST_30_DAYS') },
				{ value: 'weekly', text: this.translate('COM_EMUNDUS_SETTINGS_ANALYTICS_PERIOD_LAST_7_DAYS') },
				{ value: 'daily', text: this.translate('COM_EMUNDUS_SETTINGS_ANALYTICS_PERIOD_TODAY') },
			],
			countPagesVisited: 0,
		};
	},
	created() {
		this.getEnabled();
	},
	methods: {
		getEnabled() {
			settingsService
				.checkAnalyticsEnabled()
				.then((response) => {
					this.fields[0].value = response.data.enabled ? '1' : '0';
					if (response.data.enabled == 1) {
						this.getPagesVisited();

						this.loading = false;
					} else {
						this.loading = false;
					}
				})
				.catch((error) => {
					console.error('Error fetching analytics status:', error);
					this.loading = false;
				});
		},
		toggleAnalytics(parameter, oldValue, value) {
			if (value !== '' && oldValue !== value) {
				settingsService
					.toggleAnalytics(value)
					.then((response) => {
						if (value == 1) {
							this.getPagesVisited();
						}
					})
					.catch((error) => {
						console.error('Error toggling analytics:', error);
					});
			}
		},
		getPagesVisited() {
			settingsService
				.getPagesVisited(this.period)
				.then((response) => {
					this.countPagesVisited = response.data.count;
				})
				.catch((error) => {
					console.error('Error fetching pages visited:', error);
				});
		},
	},
};
</script>

<style scoped></style>
