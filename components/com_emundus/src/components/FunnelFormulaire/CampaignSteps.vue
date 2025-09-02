<template>
	<div id="campaign-steps">
		<Info
			:title="'COM_EMUNDUS_CAMPAIGN_STEP_DATE_INFO_TITLE'"
			:text="'COM_EMUNDUS_CAMPAIGN_STEP_DATE_INFO'"
			:accordion="true"
		/>

		<div v-for="step in steps" :key="step.id" :id="'campaign-step-' + id + '-wrapper'" class="tw-my-4">
			<h3>{{ step.label }}</h3>

			<div id="step-informations" class="tw-mt-2">
				<p class="tw-text-base tw-text-neutral-600">
					{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE') }} : {{ step.readable_type }}
				</p>
				<p class="tw-text-base tw-text-neutral-600">
					{{ translate('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS') }} : {{ getStepEntryStatusLabels(step) }}
				</p>
			</div>

			<div class="tw-my-2 tw-flex tw-items-center">
				<div class="em-toggle">
					<input
						type="checkbox"
						true-value="1"
						false-value="0"
						class="em-toggle-check tw-mt-2"
						:id="'step_' + step.id + '_infinite'"
						:name="'step_' + step.id + '_infinite'"
						v-model="step.infinite"
					/>
					<strong class="b em-toggle-switch"></strong>
					<strong class="b em-toggle-track"></strong>
				</div>
				<span :for="'step_' + step.id + '_infinite'" class="tw-ml-2 tw-flex tw-items-center">
					{{ translate('COM_EMUNDUS_CAMPAIGNS_INFINITE_STEP') }}
				</span>
			</div>

			<div class="tw-mt-4 tw-flex tw-w-full tw-flex-col tw-gap-4" v-if="step.infinite == 0">
				<div class="tw-cursor-pointer">
					<input
						class="tw-cursor-pointer"
						type="radio"
						:id="'fixed_dates_' + step.id"
						:name="'step_' + step.id + 'date_type'"
						value="0"
						v-model="step.relative_date"
						:checked="step.relative_date == 0"
					/>
					<label class="tw-cursor-pointer" :for="'fixed_dates_' + step.id">{{
						translate('COM_EMUNDUS_CAMPAIGN_STEP_FIXED_DATES')
					}}</label>
				</div>
				<div
					id="fixed-dates"
					v-if="step.relative_date != 1"
					class="tw-flex tw-flex-row tw-gap-4 tw-border-l-4 tw-py-2 tw-pl-4"
				>
					<div class="tw-w-1/2">
						<label :for="'start_date_' + step.id">{{ translate('COM_EMUNDUS_CAMPAIGN_STEP_START_DATE') }}</label>
						<DatePicker
							:id="'campaign_step_' + step.id + '_start_date'"
							v-model="step.start_date"
							:keepVisibleOnInput="true"
							:time-accuracy="2"
							mode="dateTime"
							is24hr
							hide-time-header
							title-position="left"
							:input-debounce="500"
							:popover="{ visibility: 'focus' }"
							:locale="actualLanguage"
						>
							<template #default="{ inputValue, inputEvents }">
								<input
									:value="inputValue"
									v-on="inputEvents"
									class="form-control fabrikinput tw-mt-2 tw-w-full"
									:id="'start_date_' + step.id + '_input'"
									:name="'start_date_' + step.id"
								/>
							</template>
						</DatePicker>
					</div>
					<div class="tw-w-1/2">
						<label :for="'end_date_' + step.id">{{ translate('COM_EMUNDUS_CAMPAIGN_STEP_END_DATE') }}</label>
						<DatePicker
							:id="'campaign_step_' + step.id + '_end_date'"
							v-model="step.end_date"
							:keepVisibleOnInput="true"
							:time-accuracy="2"
							mode="dateTime"
							is24hr
							hide-time-header
							title-position="left"
							:input-debounce="500"
							:popover="{ visibility: 'focus' }"
							:locale="actualLanguage"
						>
							<template #default="{ inputValue, inputEvents }">
								<input
									:value="inputValue"
									v-on="inputEvents"
									class="form-control fabrikinput tw-mt-2 tw-w-full"
									:id="'end_date_' + step.id + '_input'"
									:name="'end_date_' + step.id"
								/>
							</template>
						</DatePicker>
					</div>
				</div>

				<div class="tw-cursor-pointer">
					<input
						class="tw-cursor-pointer"
						type="radio"
						:id="'relative_date_' + step.id"
						:name="'step_' + step.id + 'date_type'"
						value="1"
						v-model="step.relative_date"
						:checked="step.relative_date == 1"
					/>
					<label class="tw-cursor-pointer" :for="'relative_date_' + step.id">{{
						translate('COM_EMUNDUS_CAMPAIGN_STEP_RELATIVE_DATE')
					}}</label>
				</div>
				<div id="relative_end" v-if="step.relative_date == 1" class="tw-border-l-4 tw-py-2 tw-pl-4">
					<div class="tw-flex tw-flex-row tw-items-center tw-gap-2">
						<label :for="'relative_end_' + step.id">{{ translate('COM_EMUNDUS_CAMPAIGN_STEP_RELATIVE_END') }}</label>
						<input
							type="number"
							class="relative_end_date_value_input"
							:id="'relative_end_date_value_' + step.id"
							:name="'relative_end_date_value_' + step.id"
							v-model="step.relative_end_date_value"
							min="0"
						/>
						<select id="relative_end_date_unit" v-model="step.relative_end_date_unit">
							<option value="day">{{ translate('COM_EMUNDUS_DAY') }}</option>
							<option value="week">{{ translate('COM_EMUNDUS_WEEK') }}</option>
							<option value="month">{{ translate('COM_EMUNDUS_MONTH') }}</option>
							<option value="year">{{ translate('COM_EMUNDUS_YEAR') }}</option>
						</select>
						<p>{{ translate('COM_EMUNDUS_CAMPAIGN_STEP_RELATIVE_END_2') }}</p>
					</div>
				</div>
			</div>
		</div>

		<div v-if="steps.length < 1">
			<p>{{ translate('COM_EMUNDUS_CAMPAIGN_NO_STEPS_FOUND') }}</p>
		</div>

		<div class="tw-flex tw-flex-row tw-justify-end">
			<button v-if="steps.length > 0" class="tw-btn tw-btn-primary tw-mt-4" @click="saveCampaignSteps">
				{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTINUER') }}
			</button>
			<button v-else class="tw-btn tw-btn-primary tw-mt-4" @click="goNext">
				{{ translate('COM_EMUNDUS_ONBOARD_CONTINUE') }}
			</button>
		</div>
	</div>
</template>

<script>
import workflowService from '@/services/workflow.js';
import settingsService from '@/services/settings.js';
import Info from '@/components/Utils/Info.vue';
import { DatePicker } from 'v-calendar';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'CampaignSteps',
	components: {
		DatePicker,
		Info,
	},
	props: {
		campaignId: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			steps: [],
			actualLanguage: null,
			statuses: [],
		};
	},
	created() {
		this.getAllStatuses();
		this.actualLanguage = useGlobalStore().getShortLang;

		this.getCampaignSteps(this.campaignId);
	},
	methods: {
		getAllStatuses() {
			settingsService
				.getStatus()
				.then((response) => {
					if (response.status) {
						this.statuses = response.data;
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
		getCampaignSteps() {
			workflowService
				.getCampaignSteps(this.campaignId)
				.then((response) => {
					this.steps = response.data;
				})
				.catch((error) => {
					console.log(error);
				});
		},
		saveCampaignSteps() {
			this.steps.forEach((step) => {
				step.start_date =
					step.start_date === null || step.start_date === '' || step.start_date === '0000-00-00 00:00:00'
						? '0000-00-00 00:00:00'
						: this.formatDate(new Date(step.start_date), 'YYYY-MM-DD HH:mm:ss');
				step.end_date =
					step.end_date === null || step.end_date === '' || step.end_date === '0000-00-00 00:00:00'
						? '0000-00-00 00:00:00'
						: this.formatDate(new Date(step.end_date), 'YYYY-MM-DD HH:mm:ss');
			});

			workflowService
				.saveCampaignSteps(this.campaignId, this.steps)
				.then((response) => {
					if (response.status) {
						this.goNext();
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
		getStepType() {},
		getStepEntryStatusLabels(step) {
			if (!step.entry_status || step.entry_status.length === 0) {
				return this.translate('COM_EMUNDUS_CAMPAIGN_STEP_NO_STATUS');
			}

			let labels = step.entry_status.map((statusId) => {
				let status = this.statuses.find((s) => s.step == statusId);
				return status ? status.value : this.translate('COM_EMUNDUS_UNKNOWN_STATUS');
			});

			return labels.join(', ');
		},
		goNext() {
			this.$emit('nextSection');
		},
		formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
			if (date == '' || date == null || date == '0000-00-00 00:00:00') {
				return '0000-00-00 00:00:00';
			}
			let year = date.getFullYear();
			let month = (1 + date.getMonth()).toString().padStart(2, '0');
			let day = date.getDate().toString().padStart(2, '0');
			let hours = date.getHours().toString().padStart(2, '0');
			let minutes = date.getMinutes().toString().padStart(2, '0');
			let seconds = date.getSeconds().toString().padStart(2, '0');

			return format
				.replace('YYYY', year)
				.replace('MM', month)
				.replace('DD', day)
				.replace('HH', hours)
				.replace('mm', minutes)
				.replace('ss', seconds);
		},
	},
};
</script>

<style scoped>
.relative_end_date_value_input {
	width: fit-content !important;
}
</style>
