<script>
import { v4 as uuid } from 'uuid';

/* Components */
import Parameter from '@/components/Utils/Parameter.vue';

/* Services */
import crcService from '@/services/crc.js';
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

			personal_detail_fields: [
				{
					param: 'lastname',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_LASTNAME',
					helptext: '',
					displayed: true,
					width: 'tw-w-2/6',
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
					width: 'tw-w-2/6',
				},
				{
					param: 'fonction',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ROLE',
					helptext: '',
					displayed: true,
					optional: true,
					width: 'tw-w-2/6',
				},
				{
					param: 'service',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_DEPARTMENT',
					helptext: '',
					displayed: true,
					optional: true,
					width: 'tw-w-2/6',
				},
				{
					param: 'gender',
					type: 'radiobutton',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER',
					helptext: '',
					displayed: true,
					options: [
						{
							value: 'woman',
							label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER_WOMAN',
						},
						{
							value: 'man',
							label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER_MAN',
						},
						{
							value: 'other',
							label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER_OTHER',
						},
					],
					optional: true,
				},
				{
					param: 'birthdate',
					type: 'text',
					placeholder: '01/01/1970',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_BIRTHDATE',
					helptext: '',
					displayed: true,
					optional: true,
					width: 'tw-w-2/6',
					mask: '##/##/####',
				},
				{
					param: 'countries',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						maxElements: 'COM_EMUNDUS_MULTISELECT_MAX_COUNTRIES_SELECTED',
						tagValidations: [],
						options: [],
						optionsLimit: 30,
						max: 2,
						label: 'name',
						trackBy: 'value',
					},
					value: 0,
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_COUNTRIES',
					placeholder: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_COUNTRIES_PLACEHOLDER',
					displayed: true,
					optional: true,
					width: 'tw-w-1/2',
				},
				{
					param: 'profile_picture',
					type: 'file',
					fileUrl: window.location.origin + '/index.php?option=com_emundus&task=crc.uploadLogo',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_PICTURE',
					helptext: '',
					displayed: true,
					optional: true,
				},
			],

			contact_fields: [
				{
					param: 'email',
					type: 'email',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_EMAIL',
					helptext: '',
					displayed: true,
					width: 'tw-w-2/6',
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
					width: 'tw-w-1/2',
				},
			],

			setting_fields: [
				{
					param: 'organizations',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getorganizations',
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
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ORGANIZATIONS',
					placeholder: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ORGANIZATIONS_PLACEHOLDER',
					displayed: true,
					optional: true,
				},
				{
					param: 'published',
					type: 'toggle',
					value: '1',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_PUBLISHED',
					hideLabel: true,
					displayed: true,
					optional: true,
				},
			],

			address_fields: [
				{
					param: 'id',
					type: 'text',
					placeholder: '',
					maxlength: 255,
					value: '',
					label: '',
					helptext: '',
					displayed: false,
					optional: true,
				},
				{
					param: 'street_address',
					type: 'text',
					placeholder: '',
					maxlength: 255,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_STREET',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'extended_address',
					type: 'text',
					placeholder: '',
					maxlength: 255,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_EXTENDED',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'postal_code',
					type: 'text',
					placeholder: '',
					maxlength: 10,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_POSTALCODE',
					helptext: '',
					displayed: true,
					optional: true,
					width: 'tw-w-1/4',
				},
				{
					param: 'locality',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_LOCALITY',
					helptext: '',
					displayed: true,
					optional: true,
					width: 'tw-w-1/2',
				},
				{
					param: 'region',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_REGION',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'country',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: false,
						taggable: false,
						searchable: true,
						internalSearch: false,
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
					label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_COUNTRY',
					placeholder: '',
					displayed: true,
					optional: true,
					width: 'tw-w-1/2',
				},
			],

			addresses: [],
		};
	},
	async created() {
		await this.getCountries();

		if (useGlobalStore().datas.contactid) {
			this.contact_id = parseInt(useGlobalStore().datas.contactid.value);
		} else if (this.$props.id) {
			this.contact_id = this.$props.id;
		}

		if (this.contact_id) {
			this.getContact(this.contact_id);
		} else {
			this.addRepeatBlock();
			this.loading = false;
		}
	},
	methods: {
		async getCountries() {
			return new Promise((resolve, reject) => {
				settingsService.getCountries().then((response) => {
					if (response.status) {
						this.personal_detail_fields.find((field) => field.param === 'countries').multiselectOptions.options =
							response.data.map((country) => ({
								value: country.value,
								name: country.name,
							}));

						this.address_fields.find((field) => field.param === 'country').multiselectOptions.options =
							response.data.map((country) => ({
								value: country.value,
								name: country.name,
							}));

						resolve(true);
					}
				});
			});
		},
		redirectJRoute(link) {
			settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
		},

		addRepeatBlock(address = null) {
			let new_address = {};
			new_address.fields = this.address_fields.map((field) => ({ ...field, value: '' }));

			if (address) {
				for (const field of new_address.fields) {
					if (address[field.param]) {
						field.value = address[field.param];
					}
				}
			} else {
				new_address.id = uuid();
			}

			this.addresses.push(new_address);
		},

		removeRepeatBlock(address_id) {
			const key = this.addresses.findIndex((address) => address.id === address_id);
			this.addresses.splice(key, 1);

			this.$forceUpdate();
		},

		duplicateRepeatBlock(address_id) {
			const key = this.addresses.findIndex((address) => address.id === address_id);

			let new_address = {};
			new_address.id = uuid();

			// Manually deep clone the array without reactivity
			new_address.fields = this.addresses[key].fields.map((field) => {
				return {
					...field,
					// Deep copy the nested `multiselectOptions` object
					multiselectOptions: field.multiselectOptions ? { ...field.multiselectOptions } : null,
				};
			});

			// Push the deep-cloned array to the `rooms` array
			this.addresses.push(new_address);
		},

		getContact(contact_id) {
			crcService.getContact(contact_id).then((response) => {
				if (response.status) {
					this.contact = response.data;

					// Convert birthdate from YYYY-MM-DD to DD/MM/YYYY
					if (this.contact.birthdate) {
						const dateParts = this.contact.birthdate.split('-');
						if (dateParts.length === 3) {
							this.contact.birthdate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
						}
					}

					const fields = [...this.personal_detail_fields, ...this.contact_fields, ...this.setting_fields];

					for (const field of fields) {
						if (this.contact[field.param]) {
							field.value = this.contact[field.param];
						}
					}

					for (const address of this.contact.addresses) {
						this.addRepeatBlock(address);
					}

					if (this.contact.addresses.length === 0) {
						this.addRepeatBlock();
					}

					this.personal_detail_fields.find((field) => field.param === 'countries').value = [];
					for (const country of this.contact.countries) {
						this.personal_detail_fields.find((field) => field.param === 'countries').value.push(country.id);
					}

					this.setting_fields.find((field) => field.param === 'organizations').value = [];
					for (const organization of this.contact.organizations) {
						this.setting_fields.find((field) => field.param === 'organizations').value.push(organization.id);
					}
					if (this.contact.profile_picture) {
						const base = window.location.origin + '/';
						this.personal_detail_fields.find((f) => f.param === 'profile_picture').value =
							this.contact.profile_picture.startsWith('https')
								? this.contact.profile_picture
								: base + this.contact.profile_picture;
					}
				}

				this.loading = false;
			});
		},

		saveContact() {
			let contact = {};
			contact.addresses = [];

			// Merge all fields
			const fields = [...this.personal_detail_fields, ...this.contact_fields, ...this.setting_fields];

			// Validate all fields
			const contactValidationFailed = fields.some((field) => {
				let ref_name = 'contact_' + field.param;

				if (!this.$refs[ref_name][0].validate()) {
					// Return true to indicate validation failed
					return true;
				}

				if (field.type === 'multiselect') {
					if (field.multiselectOptions.multiple) {
						contact[field.param] = [];

						field.value.forEach((element) => {
							if (element.value) {
								contact[field.param].push(element.value);
							}
						});
					} else {
						if (field.value) {
							contact[field.param] = field.value[field.multiselectOptions.trackBy] ?? field.value;
						} else {
							contact[field.param] = null;
						}
					}
				} else {
					contact[field.param] = field.value;
				}

				return false;
			});

			if (contactValidationFailed) return;

			// Validate all addresses
			this.addresses.some((address) => {
				let addressObject = {};

				address.fields.forEach((field) => {
					let ref_name = 'address_' + address.id + '_' + field.param;

					if (!this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							addressObject[field.param] = [];

							field.value.forEach((element) => {
								if (element.value) {
									addressObject[field.param].push(element.value);
								}
							});
						} else {
							if (field.value) {
								addressObject[field.param] = field.value[field.multiselectOptions.trackBy] ?? field.value;
							} else {
								addressObject[field.param] = null;
							}
						}
					} else {
						addressObject[field.param] = field.value;
					}
				});

				contact.addresses.push(addressObject);
				return false;
			});

			if (this.contact_id) {
				contact.id = this.contact_id;
			}
			const profilePictureField = this.personal_detail_fields.find((f) => f.param === 'profile_picture');
			if (
				profilePictureField &&
				typeof profilePictureField.value === 'string' &&
				profilePictureField.value.startsWith('https')
			) {
				contact.logo = this.contact.logo;
			}

			crcService.saveContact(contact).then((response) => {
				if (response.status === true) {
					if (this.$props.isModal) {
						this.$emit('close', response.data);
					} else {
						this.redirectJRoute('index.php?option=com_emundus&view=crc');
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
			const fields = [...this.personal_detail_fields, ...this.contact_fields, ...this.setting_fields];

			if (
				fields.some((field) => {
					if (!field.optional) {
						return field.value === '' || field.value === 0;
					}
				})
			)
				return true;

			const emailRef = this.$refs['contact_email']?.[0];
			return !!(emailRef && !emailRef.validate(false));
		},
	},
};
</script>

<template>
	<div>
		<div
			v-if="!loading"
			:class="{
				'tw-mb-8 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-neutral-0 tw-p-6 tw-shadow-card':
					!isModal,
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
					@click="redirectJRoute('index.php?option=com_emundus&view=crc')"
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
				<h3>{{ translate('COM_EMUNDUS_ONBOARD_EDIT_CONTACT_PERSONAL_DETAILS') }}</h3>
				<div
					v-for="field in personal_detail_fields"
					:key="field.param"
					v-show="field.displayed"
					:class="field.width ? field.width : 'tw-w-full'"
				>
					<Parameter
						:ref="'contact_' + field.param"
						:parameter-object="field"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					/>
				</div>
			</div>

			<hr />

			<!-- REPEAT GROUP -->
			<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-3">
				<h3>{{ translate('COM_EMUNDUS_ONBOARD_EDIT_CONTACT_ADDRESSES') }}</h3>

				<div
					v-for="address in addresses"
					:key="address.id"
					class="tw-flex tw-flex-col tw-gap-2 tw-rounded-coordinator-form tw-border tw-border-neutral-400 tw-bg-white tw-px-3 tw-py-4"
				>
					<div class="tw-flex tw-items-center tw-justify-end tw-gap-2">
						<button type="button" @click="duplicateRepeatBlock(address.id)" class="w-auto">
							<span class="material-symbols-outlined !tw-text-neutral-900">content_copy</span>
						</button>
						<button v-if="addresses.length > 0" type="button" @click="removeRepeatBlock(address.id)" class="w-auto">
							<span class="material-symbols-outlined tw-text-red-600">close</span>
						</button>
					</div>

					<div class="tw-flex tw-flex-col tw-gap-6">
						<div
							v-for="field in address.fields"
							:key="field.param"
							:class="field.width ? field.width : 'tw-w-full'"
							v-show="field.displayed"
						>
							<Parameter
								:ref="'address_' + address.id + '_' + field.param"
								:parameter-object="field"
								:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
							/>
						</div>
					</div>
				</div>

				<div class="tw-flex tw-justify-end">
					<button type="button" @click="addRepeatBlock()" class="tw-mt-2 tw-flex tw-w-auto tw-items-center tw-gap-1">
						<span class="material-symbols-outlined !tw-text-neutral-900">add</span>
						<span
							>{{
								this.addresses.length > 0
									? translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_ADDRESS')
									: translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_ADDRESS')
							}}
						</span>
					</button>
				</div>
			</div>

			<hr />

			<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-6">
				<h3>{{ translate('COM_EMUNDUS_ONBOARD_EDIT_CONTACT_LINKS') }}</h3>
				<div
					v-for="field in contact_fields"
					:key="field.param"
					:class="field.width ? field.width : 'tw-w-full'"
					v-show="field.displayed"
				>
					<Parameter
						:ref="'contact_' + field.param"
						:parameter-object="field"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					/>
				</div>
			</div>

			<hr />

			<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-6">
				<h3>{{ translate('COM_EMUNDUS_ONBOARD_EDIT_CONTACT_SETTINGS') }}</h3>
				<div
					v-for="field in setting_fields"
					:key="field.param"
					:class="field.width ? field.width : 'tw-w-full'"
					v-show="field.displayed"
				>
					<Parameter
						:ref="'contact_' + field.param"
						:parameter-object="field"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					/>
				</div>
			</div>

			<div class="tw-mb-2 tw-mt-7 tw-flex tw-justify-end">
				<button
					type="button"
					class="tw-btn-primary !tw-w-auto"
					:disabled="disabledSubmit"
					@click.prevent="saveContact()"
				>
					<span v-if="contact_id">{{ translate('COM_EMUNDUS_ONBOARD_SAVE') }}</span>
					<span v-else>{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_CREATE') }}</span>
				</button>
			</div>
		</div>

		<div v-else class="em-page-loader"></div>
	</div>
</template>
