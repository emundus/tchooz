<script>
import { defineComponent } from 'vue';

import fileService from '@/services/file.js';
import alertMixin from '@/mixins/alerts.js';

import Parameter from '@/components/Utils/Parameter.vue';
import Button from '@/components/Atoms/Button.vue';
import Loader from '@/components/Atoms/Loader.vue';

export default defineComponent({
	name: 'UpdateOwner',
	components: { Loader, Button, Parameter },
	mixins: [alertMixin],
	data: function () {
		return {
			loading: false,

			selectedFiles: [],

			fields: [
				{
					param: 'owner',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: false,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getavailableapplicants',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [],
						optionsLimit: 30,
						label: 'name',
						trackBy: 'value',
					},
					value: 0,
					hideLabel: false,
					label: 'COM_EMUNDUS_UPDATE_OWNER_NEW_OWNER_LABEL',
					placeholder: '',
					displayed: true,
					optional: false,
					helptext: 'COM_EMUNDUS_UPDATE_OWNER_NEW_OWNER_HELPTEXT',
				},
			],
		};
	},
	created() {
		this.getSelectedFiles();
	},
	methods: {
		getSelectedFiles() {
			fileService
				.getUpdateOwnerFiles()
				.then((response) => {
					this.selectedFiles = response.data;
				})
				.catch((error) => {
					console.error('Error fetching files:', error);
				});
		},

		updateOwner() {
			this.loading = true;
			const newOwnerId = this.fields.find((field) => field.param === 'owner').value;
			if (!newOwnerId) {
				this.alertError('COM_EMUNDUS_UPDATE_OWNER_NO_OWNER_SELECTED');
				this.loading = false;
				return;
			}

			fileService
				.updateOwner(newOwnerId.value)
				.then((response) => {
					if (response.status) {
						this.alertSuccess('COM_EMUNDUS_UPDATE_OWNER_SUCCESS', null, false, null, 3500).then(() => {
							window.postMessage('reloadData');
						});
					} else {
						this.alertError('COM_EMUNDUS_UPDATE_OWNER_ERROR', response.msg);
					}

					this.loading = false;
				})
				.catch((error) => {
					// Handle error, e.g., show an error message
					console.error('Error updating owner:', error);
				});
		},
	},
	computed: {
		ownerSelected() {
			const ownerField = this.fields.find((field) => field.param === 'owner');
			return !ownerField.value || ownerField.value.length === 0;
		},
		firstFiveFiles() {
			return this.selectedFiles.slice(0, 5);
		},
	},
});
</script>

<template>
	<div class="tw-min-w-[20vw] tw-max-w-[35vw]">
		<div v-if="!loading" class="tw-flex tw-flex-col tw-gap-6">
			<div class="tw-flex tw-flex-col">
				<label>{{ translate('COM_EMUNDUS_UPDATE_OWNER_FILES_WILL_BE_TRANSFERED') }}</label>
				<span v-if="selectedFiles.length > 5"
					>{{ selectedFiles.length }} {{ translate('COM_EMUNDUS_UPDATE_OWNER_FILES_WILL_BE_TRANSFERED_SUBTEXT') }}</span
				>
				<ul class="tw-list-disc tw-pl-5 tw-text-neutral-900">
					<li v-for="file in firstFiveFiles" :key="file.id">
						{{ file.user }} - {{ file.campaign.label }} ({{ file.campaign.year }}) - {{ file.status.label }}
					</li>
				</ul>
			</div>

			<div v-for="field in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
				<Parameter
					:ref="'update_owner_' + field.param"
					:parameter-object="field"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					:help-text-type="'above'"
				/>
			</div>

			<div class="tw-flex tw-w-full tw-items-center tw-justify-between">
				<Button variant="secondary">
					{{ translate('COM_EMUNDUS_ONBOARD_CANCEL') }}
				</Button>
				<Button variant="primary" @click="updateOwner" :disabled="ownerSelected">
					{{ translate('COM_EMUNDUS_UPDATE_OWNER_RUN') }}
				</Button>
			</div>
		</div>

		<Loader v-else />
	</div>
</template>

<style scoped></style>
