<script>
import Loader from '@/components/Atoms/Loader.vue';
import Button from '@/components/Atoms/Button.vue';
import Parameter from '@/components/Utils/Parameter.vue';

import alerts from '@/mixins/alerts.js';
import crcService from '@/services/crc.js';

export default {
	name: 'UpdateAssociatedContacts',
	components: { Parameter, Button, Loader },
	emits: ['close', 'update-items'],
	props: {
		fnum: {
			type: String,
			default: '',
		},
	},
	mixins: [alerts],
	data: () => ({
		loading: false,
		ready: false,
		field: {
			param: 'contacts',
			type: 'multiselect',
			multiselectOptions: {
				noOptions: false,
				multiple: true,
				taggable: false,
				searchable: true,
				internalSearch: false,
				asyncRoute: 'getcontacts',
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
			label: 'COM_EMUNDUS_UPDATE_ASSOCIATED_CONTACTS_CONTACTS',
			placeholder: 'COM_EMUNDUS_UPDATE_ASSOCIATED_CONTACTS_CONTACTS_PLACEHOLDER',
		},
	}),
	created() {
		this.loading = true;
		crcService.getFileContacts(this.$props.fnum).then((response) => {
			if (response.status === true) {
				this.field.value = response.data.contacts || [];
			} else {
				this.alertError(response.message);
			}
			// Mount the multiselect only once the current associations are loaded, so they appear preselected.
			this.field.reload++;
			this.ready = true;
			this.loading = false;
		});
	},
	methods: {
		onValueUpdated(parameter) {
			this.field.value = parameter.value || [];
		},
		onClosePopup() {
			this.$emit('close');
		},

		/* SERVICES */
		async saveAssociations() {
			this.loading = true;

			// Always send the full current selection: removed contacts are detached, an empty list detaches all.
			const payload = {
				fnum: this.$props.fnum,
				contacts: (this.field.value || []).map((c) => c.value).join(','),
			};

			crcService.updateFileContacts(payload).then((response) => {
				if (response.status === true) {
					this.onClosePopup();

					Swal.fire({
						position: 'center',
						icon: 'success',
						title: Joomla.Text._('COM_EMUNDUS_UPDATE_ASSOCIATED_CONTACTS_SUCCESS'),
						showConfirmButton: true,
						allowOutsideClick: false,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
						timer: 1500,
					}).then(() => {
						window.postMessage('reloadData');
					});
				} else {
					Swal.fire({
						icon: 'error',
						title: 'Oops...',
						text: response.message,
					});
				}
				this.$emit('update-items');
				this.loading = false;
			});
		},
	},
};
</script>

<template>
	<div>
		<div v-show="!loading">
			<p>
				{{ translate('COM_EMUNDUS_UPDATE_ASSOCIATED_CONTACTS_DESC') }}
			</p>

			<div class="tw-mb-6 tw-mt-3 tw-flex tw-w-full tw-flex-col tw-gap-2">
				<Parameter
					v-if="ready"
					:key="field.reload"
					:parameter-object="field"
					:help-text-type="'below'"
					:multiselect-options="field.multiselectOptions"
					@valueUpdated="onValueUpdated"
				/>
			</div>

			<div class="tw-flex tw-justify-end">
				<Button @click="saveAssociations">
					{{ translate('COM_EMUNDUS_CUSTOM_REFERENCE_CONFIRM_SAVE') }}
				</Button>
			</div>
		</div>

		<Loader v-if="loading" />
	</div>
</template>

<style scoped></style>
