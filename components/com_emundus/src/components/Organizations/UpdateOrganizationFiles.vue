<script>
import Parameter from '@/components/Utils/Parameter.vue';
import crcService from '@/services/crc.js';

export default {
	name: 'UpdateOrganizationFiles',
	components: { Parameter },
	props: {
		item: Object,
	},
	emits: ['close', 'update-items'],
	data: () => ({
		organization: Object,
		optionsReady: false,

		fields: [
			{
				param: 'files',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: true,
					taggable: false,
					searchable: true,
					internalSearch: false,
					asyncRoute: 'getapplicants',
					asyncController: 'sign',
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
				value: [],
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_CRC_UPDATE_ORGANIZATION_FILES_ASSOCIATED',
				placeholder: 'COM_EMUNDUS_ONBOARD_CRC_UPDATE_ORGANIZATION_FILES_ASSOCIATED_PLACEHOLDER',
				displayed: true,
			},
		],
	}),
	created() {
		this.organization = this.$props.item;
		this.fields.find((field) => field.param === 'files').value = this.organization.application_files;
	},
	methods: {
		onClosePopup() {
			this.$emit('close');
		},

		onOptionsLoaded(options, param) {
			if (param === 'files') {
				this.optionsReady = true;
			}
		},

		updateOrganizationFiles() {
			let fnums = [];

			const filesField = this.fields.find((field) => field.param === 'files');
			if (filesField && filesField.value) {
				filesField.value.forEach((element) => {
					if (element.value) {
						fnums.push(element.value);
					}
				});
			}

			crcService.updateOrganizationFiles(this.organization.id, fnums).then((response) => {
				if (response.status === true) {
					this.onClosePopup();

					Swal.fire({
						position: 'center',
						icon: 'success',
						title: Joomla.Text._('COM_EMUNDUS_ONBOARD_CRC_UPDATE_ORGANIZATION_FILES_SUCCESS'),
						showConfirmButton: true,
						allowOutsideClick: false,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
						timer: 1500,
					});
				} else {
					Swal.fire({
						icon: 'error',
						title: 'Oops...',
						text: response.message,
					});
				}
				this.$emit('update-items');
			});
		},
	},
	computed: {
		disabledSubmit() {
			return false;
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-pt-4">
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
				<h2>
					{{ translate('COM_EMUNDUS_ONBOARD_CRC_UPDATE_ORGANIZATION_FILES_ASSOCIATED_TITLE') }}
				</h2>

				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="onClosePopup">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
		</div>

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
			<div
				v-for="field in fields"
				v-show="field.displayed"
				:key="field.param"
				:class="'tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-2'"
			>
				<Parameter
					v-if="field.displayed"
					:ref="'assoc_organization_' + field.param"
					:key="field.reload ? field.reload + field.param : field.param"
					:parameter-object="field"
					:help-text-type="'below'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					@ajaxOptionsLoaded="onOptionsLoaded"
				/>
			</div>
		</div>

		<div class="tw-mb-8 tw-mt-5 tw-flex tw-justify-between">
			<button class="tw-btn-cancel" @click="onClosePopup">
				{{ translate('COM_EMUNDUS_ONBOARD_CRC_UPDATE_ORGANIZATION_FILES_ASSOCIATED_CANCEL') }}
			</button>
			<button class="tw-btn-primary" :disabled="disabledSubmit || !optionsReady" @click="updateOrganizationFiles()">
				{{ translate('COM_EMUNDUS_ONBOARD_CRC_UPDATE_ORGANIZATION_FILES_ASSOCIATED_CONFIRM') }}
			</button>
		</div>

		<div class="em-page-loader" v-if="!optionsReady"></div>
	</div>
</template>

<style scoped></style>
