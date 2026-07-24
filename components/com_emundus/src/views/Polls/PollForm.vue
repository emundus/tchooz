<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import transformMixin from '@/mixins/transformIntoParameterField.js';
import Parameter from '@/components/Utils/Parameter.vue';
import Back from '@/components/Utils/Back.vue';
import alerts from '@/mixins/alerts.js';
import parameterForm from '@/mixins/parameterForm.js';
import pollService from '@/services/poll.js';
import settingsService from '@/services/settings.js';
import { useGlobalStore } from '@/stores/global.js';
import { Button } from '@emundus/ui';
import PollCalendar from '@/components/Polls/PollCalendar.vue';

export default {
	name: 'PollForm',
	components: { PollCalendar, Button, Back, Parameter, ParameterForm },
	props: {
		poll: {
			type: Object,
			required: true,
		},
		fields: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			formGroups: [],
			defaultParameters: [],
			requiredFields: [],
			requiredFieldsKey: 0,
		};
	},
	mixins: [transformMixin, alerts, parameterForm],
	created() {
		this.getFormGroups();
	},
	mounted() {},
	methods: {
		async getFormGroups() {
			this.fieldsToParameterFormGroups(this.fields, this.poll).then((groups) => {
				this.formGroups = groups;

				this.loading = false;
			});
		},
		save() {
			const { isValid, form: poll_form } = this.validateParameterForm(this.$refs.parameterForm);
			if (!isValid) return;

			if (this.poll && this.poll.id > 0) {
				poll_form.id = this.poll.id;
			}

			if (this.poll && this.poll.slots) {
				const slotsToSend = this.poll.slots.map((slot) => ({
					...slot,
					id: slot.id > 0 ? slot.id : 0,
				}));
				poll_form.slots = JSON.stringify(slotsToSend);
			}

			this.loading = true;
			pollService.savePoll(poll_form).then((response) => {
				if (response.status) {
					this.alertSuccess(response.msg).then(() => {
						settingsService.redirectJRoute('index.php?option=com_emundus&view=polls', useGlobalStore().getCurrentLang);
					});
				} else {
					this.loading = false;
					this.alertError(response.message);
				}
			});
		},

		goBack() {
			window.history.back();
		},
	},
	computed: {
		title() {
			return this.poll && this.poll.id
				? this.translate('COM_EMUNDUS_POLLS_EDIT')
				: this.translate('COM_EMUNDUS_POLLS_ADD');
		},
	},
};
</script>

<template>
	<div
		id="poll-form"
		class="tw-mb-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<Back :link="'index.php?option=com_emundus&view=polls'" class="tw-mb-4"></Back>
		<h1>
			{{ title }}
		</h1>

		<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-4" v-if="!loading">
			<ParameterForm id="poll-parameters-form" ref="parameterForm" :groups="formGroups" :fields="fields" />

			<PollCalendar :poll="poll" />
		</div>

		<div class="tw-mt-4 tw-flex tw-w-full tw-justify-between">
			<Button @click="goBack" emphasis="lite">
				{{ translate('COM_EMUNDUS_ACTIONS_CANCEL') }}
			</Button>
			<Button @click="save">
				{{ translate('COM_EMUNDUS_SAVE') }}
			</Button>
		</div>
	</div>
</template>

<style scoped></style>
