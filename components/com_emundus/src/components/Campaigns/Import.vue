<script>
/* Services */
import campaignsService from '@/services/campaign.js';
import Parameter from '@/components/Utils/Parameter.vue';
import Swal from 'sweetalert2';

export default {
	name: 'Import',
	components: { Parameter },
	props: {
		item: Object,
	},
	emits: ['close'],
	data: () => ({
		modelPath: '',
		csvFile: null,

		displayOptions: false,
		submitted: false,

		fields: [
			{
				param: 'send_email',
				type: 'toggle',
				value: 0,
				label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_SEND_EMAIL',
				hideLabel: true,
				displayed: false,
				optional: true,
			},
			{
				param: 'create_new_fnum',
				type: 'radiobutton',
				value: 0,
				label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_EXISITING_FNUM',
				displayed: true,
				optional: true,
				options: [
					{
						value: 0,
						label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_EXISITING_FNUM_UPDATE',
					},
					{
						value: 1,
						label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_EXISITING_FNUM_CREATE',
					},
					{
						value: 2,
						label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_EXISITING_FNUM_DO_NOTHING',
					},
				],
			},
		],

		options: [
			{
				name: 'status',
				value: true,
				label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_OPTIONS_STATUS',
			},
			{
				name: 'forms',
				value: true,
				label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_OPTIONS_FORMS',
			},
			{
				name: 'evaluations',
				value: true,
				label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_OPTIONS_EVALUATION_FORMS',
			},
			{
				name: 'validators',
				value: true,
				label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_OPTIONS_VALIDATORS',
			},
		],
	}),
	created: function () {},
	methods: {
		onClosePopup() {
			this.$emit('close');
		},

		selectFile() {
			this.$refs.import_file.click();
		},

		displayHelp() {
			Swal.fire({
				title: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_ATTENDEE_MODEL_HELP_TITLE'),
				html: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_ATTENDEE_MODEL_HELP_TEXT'),
				icon: 'info',
				confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_RESULT_CLOSE'),
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					actions: 'em-swal-single-action',
				},
			});
		},

		downloadImportModel() {
			let options = {
				status: this.options[0].value,
				forms: this.options[1].value,
				evaluations: this.options[2].value,
				validators: this.options[3].value,
			};

			campaignsService
				.getImportModel(this.item.id, options)
				.then((response) => {
					this.modelPath = response.data;

					this.$nextTick(() => {
						document.querySelector('#import_model_download').click();
					});
				})
				.catch((error) => {
					console.error('Error fetching import model:', error);
				});
		},

		importFiles() {
			this.submitted = true;
			let csv_import = {};

			// Validate all fields
			const importValidationFailed = this.fields.some((field) => {
				if (field.displayed) {
					let ref_name = 'import_' + field.param;

					if (!this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					csv_import[field.param] = field.value;

					return false;
				}
			});

			if (importValidationFailed || !this.csvFile) return;

			csv_import.file = this.csvFile;
			csv_import.campaign_id = this.item.id;

			campaignsService.scanImportFile(csv_import).then((response) => {
				if (response.status) {
					// Display success message with files count that will be imported
					Swal.fire({
						icon: 'warning',
						title: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_CONFIRM_TITLE'),
						text:
							response.data > 1
								? this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_CONFIRM_ROWS').replace('%rows%', response.data)
								: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_CONFIRM_ROW').replace('%row%', response.data),
						showCancelButton: true,
						confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_RUN'),
						cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							cancelButton: 'em-swal-cancel-button',
							actions: 'em-swal-double-action',
						},
					}).then((result) => {
						if (result.value) {
							campaignsService.importFiles(csv_import).then((response) => {
								if (response.status) {
									Swal.fire({
										title: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_RESULT_TITLE'),
										text: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_RESULT_ROWS')
											.replace('%rows%', response.data.files_imported.length)
											.replace('%errors%', response.data.files_not_imported.length),
										confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_RESULT_CLOSE'),
										customClass: {
											title: 'em-swal-title',
											confirmButton: 'em-swal-confirm-button',
											actions: 'em-swal-single-action',
										},
									}).then(() => {
										this.onClosePopup();
									});
								}
							});
						} else {
							this.submitted = false;
						}
					});
				}
			});
		},

		onFileChange(event) {
			const file = event.target.files[0];
			if (file) {
				this.csvFile = file;
			}
		},
	},
	computed: {
		disabledSubmit: function () {
			return (
				this.fields.some((field) => {
					if (!field.optional && field.displayed) {
						return (
							field.value === '' ||
							field.value === 0 ||
							field.value === null ||
							(typeof field.value === 'object' && Object.keys(field.value).length === 0)
						);
					} else {
						return false;
					}
				}) || !this.csvFile
			);
		},
	},
};
</script>

<template>
	<div>
		<div>
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-center">
				<h2 class="tw-font-semibold tw-text-[var(--em-coordinator-h3)]">
					{{ translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_FILES') }}
				</h2>

				<button
					class="tw-absolute tw-right-[2rem] tw-top-[2rem] tw-cursor-pointer tw-bg-transparent"
					@click.prevent="onClosePopup"
				>
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>

			<div class="tw-mb-6 tw-mt-7 tw-flex tw-flex-col tw-gap-6">
				<div class="tw-flex tw-flex-col">
					<div class="tw-mb-2 tw-flex tw-flex-col">
						<div class="tw-flex tw-items-center tw-gap-1">
							<label class="tw-mb-0 tw-flex tw-items-center tw-font-medium">
								{{ translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_ATTENDEE_MODEL') }}
							</label>
							<span
								class="material-symbols-outlined tw-ml-1 tw-cursor-pointer tw-text-neutral-600"
								@click="displayHelp()"
								>help_outline</span
							>
						</div>

						<div class="tw-flex tw-cursor-pointer tw-items-center tw-gap-1" @click="displayOptions = !displayOptions">
							<span class="tw-text-xs">{{
								translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_ATTENDEE_MODEL_OPTIONS')
							}}</span>
							<span class="material-symbols-outlined tw-text-xs" :class="{ 'tw-rotate-90': displayOptions }"
								>chevron_right</span
							>
						</div>
						<div v-if="displayOptions" class="tw-mt-1 tw-grid tw-grid-cols-2">
							<div v-for="option in options" class="tw-flex tw-items-baseline tw-gap-2">
								<input class="!tw-h-auto" type="checkbox" :id="'import_' + option.name" v-model="option.value" />
								<label class="tw-mb-0" :for="'import_' + option.name">{{ translate(option.label) }}</label>
							</div>
						</div>
					</div>

					<button @click="downloadImportModel" class="tw-btn tw-btn-tertiary tw-w-fit">
						{{ translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_DOWNLOAD_XLSX') }}
					</button>
					<a :href="modelPath" id="import_model_download" download class="tw-hidden"></a>
				</div>

				<div class="tw-flex tw-flex-col">
					<label class="tw-flex tw-items-center tw-font-medium" for="import_file">
						{{ translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_FILE') }}
					</label>
					<input
						id="import_file"
						ref="import_file"
						type="file"
						class="tw-input !tw-hidden"
						accept=".csv, .xlsx, .xls"
						@change="onFileChange"
					/>
					<div
						v-if="!this.csvFile"
						@click="selectFile"
						class="tw-flex tw-h-[10rem] tw-w-full tw-cursor-pointer tw-flex-col tw-items-center tw-justify-center tw-gap-2 tw-rounded-coordinator tw-border-2 tw-border-dashed tw-border-neutral-500"
					>
						<span class="material-symbols-outlined">download</span>
						<p>{{ translate('COM_EMUNDUS_ONBOARD_ACTION_IMPORT_SELECT_FILE') }}</p>
					</div>
					<div v-else class="tw-flex tw-items-center tw-gap-2">
						<span>{{ this.csvFile.name }}</span>
						<span class="material-symbols-outlined tw-cursor-pointer" @click="this.csvFile = null">close</span>
					</div>
					<div></div>
				</div>

				<div
					v-for="field in fields"
					v-show="field.displayed"
					:key="field.param"
					:class="'tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-2'"
				>
					<Parameter
						v-if="field.displayed"
						:ref="'import_' + field.param"
						:parameter-object="field"
						:help-text-type="'above'"
					/>
				</div>
			</div>

			<div class="tw-mt-10 tw-flex tw-justify-between">
				<button class="tw-btn-cancel" @click="onClosePopup">
					{{ translate('COM_EMUNDUS_ONBOARD_IMPORT_CANCEL') }}
				</button>
				<button class="tw-btn-primary" :disabled="disabledSubmit || submitted" @click="importFiles()">
					{{ translate('COM_EMUNDUS_ONBOARD_IMPORT_RUN') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
