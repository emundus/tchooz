<script>
import applicationService from '@/services/application.js';
import Loader from '@/components/Atoms/Loader.vue';
import Button from '@/components/Atoms/Button.vue';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'UpdateApplicationChoiceState',
	components: { Button, Loader },
	mixins: [alerts],
	props: {
		item: Object,
		items: { type: Array, default: () => [] },
	},
	data() {
		return {
			loading: true,
			status: [],

			selectedState: null,
		};
	},
	async created() {
		await this.getStates();
		this.loading = false;
	},
	methods: {
		getStates() {
			return new Promise((resolve) => {
				applicationService.getChoicesStates().then((res) => {
					this.status = res.data;

					resolve();
				});
			});
		},

		updateState() {
			this.loading = true;

			let ids = [];
			if (this.items && this.items.length > 0) {
				ids = this.items;
			} else {
				ids.push(this.item.id);
			}
			ids = ids.join(',');

			applicationService.updateStatus(ids, this.selectedState).then(async (res) => {
				this.loading = false;

				this.$emit('close');

				if (res.status) {
					this.$emit('update-items');
					this.alertSuccess(
						'COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_SUCCESS_TITLE',
						'COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_SUCCESS_TEXT',
					);
				} else {
					this.alertError(
						'COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_ERROR_TITLE',
						res.error || 'COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_ERROR_TEXT',
					);
				}
			});
		},
	},
};
</script>

<template>
	<div>
		<h1>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_TITLE') }}</h1>

		<div v-if="!loading" class="tw-flex tw-flex-col tw-gap-4">
			<div class="tw-mt-7 tw-flex tw-w-full tw-flex-col">
				<label>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_SELECT_STATUS') }}</label>
				<select v-model="selectedState">
					<option :value="null" disabled>
						{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_PLEASE_SELECT_A_STATUS') }}
					</option>
					<option v-for="(state, index) in status" :key="index" :value="index">{{ state }}</option>
				</select>
			</div>

			<div class="tw-flex tw-w-full tw-items-center tw-justify-between">
				<Button @click="$emit('close')" variant="cancel"> {{ translate('COM_EMUNDUS_ACTIONS_CANCEL') }} </Button>
				<Button @click="updateState()" :disabled="selectedState === null">
					{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS') }}
				</Button>
			</div>
		</div>

		<Loader v-else />
	</div>
</template>

<style scoped></style>
