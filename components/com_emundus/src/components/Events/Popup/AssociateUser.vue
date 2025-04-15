<script>
import Parameter from '@/components/Utils/Parameter.vue';

import eventsService from '@/services/events.js';

export default {
	name: 'AssociateUser',
	components: { Parameter },
	props: {
		items: Array,
		slot: Object,
	},
	emits: ['close', 'valueUpdated'],
	data: () => ({
		actualLanguage: 'fr-FR',
		cancelPopupOpenForBookingId: null,
		initialEvent: null,
		registrants: [],

		fields: [
			{
				param: 'juror',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: true,
					taggable: false,
					searchable: true,
					internalSearch: false,
					asyncRoute: 'getavailablemanagers',
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
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS',
				placeholder: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_PLACEHOLDER',
				displayed: true,
			},
			{
				param: 'replace_jurors',
				type: 'toggle',
				value: 1,
				label: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_REPLACE',
				displayed: true,
				hideLabel: true,
				optional: true,
			},
		],
	}),
	created() {
		this.registrants = this.$props.slot ? [this.$props.slot.id] : this.$props.items;
	},
	methods: {
		onClosePopup() {
			this.$emit('close');
		},

		assocUsers() {
			let jurors = [];
			this.fields[0].value.forEach((juror) => {
				jurors.push(juror.value);
			});
			eventsService.assocUsers(this.registrants, jurors, this.fields[1].value).then((response) => {
				if (response.status === true) {
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: Joomla.Text._('COM_EMUNDUS_ONBOARD_REGISTRANT_ASSOC_SAVED'),
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
						this.onClosePopup();
						this.$emit('update-items');
					});
				} else {
					// Handle error
					Swal.fire({
						icon: 'error',
						title: 'Oops...',
						text: response.message,
					});
				}
			});
		},
	},
	computed: {
		disabledSubmit: function () {
			return this.fields.some((field) => {
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
			});
		},

		confirmButton: function () {
			return this.translate('COM_EMUNDUS_ONBOARD_REGISTRANT_CONFIRM_ASSOCIATE').replace(
				'{{registrantCount}}',
				this.registrants.length,
			);
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-pt-4">
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
				<h2>
					{{ translate('COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_ASSOCIATE') }}
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
					:ref="'assoc_user_' + field.param"
					:key="field.reload ? field.reload + field.param : field.param"
					:parameter-object="field"
					:help-text-type="'below'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
				/>
			</div>
		</div>

		<div class="tw-mb-8 tw-mt-5 tw-flex tw-justify-between">
			<button class="tw-btn-cancel" @click="onClosePopup">
				{{ translate('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_CANCEL') }}
			</button>
			<button class="tw-btn-primary" :disabled="disabledSubmit" @click="assocUsers()">
				{{ confirmButton }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
