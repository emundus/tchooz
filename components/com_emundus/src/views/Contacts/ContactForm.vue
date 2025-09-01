<script>
import { v4 as uuid } from 'uuid';

/* Components */
import Parameter from '@/components/Utils/Parameter.vue';

/* Services */
import contactsService from '@/services/contacts.js';
import settingsService from '@/services/settings.js';

/* Stores */
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'ContactForm',
	components: { Parameter },
	emits: ['close', 'open'],
	props: {
		isModal: {
			type: Boolean,
			default: false,
		},
		id: {
			type: Number,
			default: 0,
		},
	},
	data() {
		return {
			contact_id: 0,
			contact: {},

			loading: true,

			fields: [
				{
					param: 'lastname',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_LASTNAME',
					helptext: '',
					displayed: true,
				},
				{
					param: 'firstname',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_FIRSTNAME',
					helptext: '',
					displayed: true,
				},
				{
					param: 'email',
					type: 'email',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_EMAIL',
					helptext: '',
					displayed: true,
				},
				{
					param: 'phone_1',
					type: 'phonenumber',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_PHONENUMBER',
					helptext: '',
					displayed: true,
					optional: true,
				},
			],
		};
	},
	created() {
		if (useGlobalStore().datas.contactid) {
			this.contact_id = parseInt(useGlobalStore().datas.contactid.value);
		} else if (this.$props.id) {
			this.contact_id = this.$props.id;
		}

		if (this.contact_id) {
			this.getContact(this.contact_id);
		} else {
			this.loading = false;
		}
	},
	methods: {
		redirectJRoute(link) {
			settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
		},

		getContact(contact_id) {
			contactsService.getContact(contact_id).then((response) => {
				if (response.status) {
					this.contact = response.data;

					for (const field of this.fields) {
						if (this.contact[field.param]) {
							field.value = this.contact[field.param];
						}
					}
				}

				this.loading = false;
			});
		},

		saveContact() {
			let contact = {};

			// Validate all fields
			const contactValidationFailed = this.fields.some((field) => {
				let ref_name = 'contact_' + field.param;

				if (!this.$refs[ref_name][0].validate()) {
					// Return true to indicate validation failed
					return true;
				}

				contact[field.param] = field.value;
				return false;
			});

			if (contactValidationFailed) return;

			if (this.contact_id) {
				contact.id = this.contact_id;
			}

			contactsService.saveContact(contact).then((response) => {
				if (response.status === true) {
					if (this.$props.isModal) {
						this.$emit('close', response.data);
					} else {
						this.redirectJRoute('index.php?option=com_emundus&view=contacts');
					}
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
			let field_bool = this.fields.some((field) => {
				if (!field.optional) {
					return field.value === '' || field.value === 0;
				}
			});

			return field_bool;
		},
	},
};
</script>

<template>
	<div>
		<div
			v-if="!loading"
			:class="{
				'tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-neutral-0 tw-p-6 tw-shadow-card': !isModal,
			}"
		>
			<div v-if="isModal" class="tw-sticky tw-top-0 tw-z-10 tw-bg-white">
				<div class="tw-mb-4 tw-flex tw-items-center tw-justify-center">
					<h2>
						{{
							this.contact && Object.keys(this.contact).length > 0
								? translate('COM_EMUNDUS_ONBOARD_EDIT_CONTACT') +
									' ' +
									this.contact['lastname'] +
									' ' +
									this.contact['firstname']
								: translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT')
						}}
					</h2>
					<button class="tw-absolute tw-right-0 tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
						<span class="material-symbols-outlined">close</span>
					</button>
				</div>
			</div>

			<div v-else>
				<div
					class="tw-group tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular"
					@click="redirectJRoute('index.php?option=com_emundus&view=contacts')"
				>
					<span class="material-symbols-outlined tw-mr-1 tw-text-link-regular">navigate_before</span>
					<span class="group-hover:tw-underline">{{ translate('BACK') }}</span>
				</div>

				<h1 class="tw-mt-4">
					{{
						this.contact && Object.keys(this.contact).length > 0
							? translate('COM_EMUNDUS_ONBOARD_EDIT_CONTACT') +
								' ' +
								this.contact['lastname'] +
								' ' +
								this.contact['firstname']
							: translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT')
					}}
				</h1>

				<hr class="tw-mb-8 tw-mt-1.5" />
			</div>

			<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
				<div v-for="field in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
					<Parameter :ref="'contact_' + field.param" :parameter-object="field" />
				</div>
			</div>

			<div class="tw-mb-2 tw-mt-7 tw-flex tw-justify-end">
				<button
					type="button"
					class="tw-btn-primary !tw-w-auto"
					:disabled="disabledSubmit"
					@click.prevent="saveContact()"
				>
					<span v-if="contact_id">{{ translate('COM_EMUNDUS_ONBOARD_EDIT_CONTACT') }}</span>
					<span v-else>{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_CREATE') }}</span>
				</button>
			</div>
		</div>

		<div v-else class="em-page-loader"></div>
	</div>
</template>
