<script>
/* Components */
import Parameter from '@/components/Utils/Parameter.vue';

/* Services */
import crcService from '@/services/crc.js';
import settingsService from '@/services/settings.js';

/* Stores */
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'OrganizationForm',
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
			org_id: 0,
			organization: {},

			loading: true,

			main_fields: [
				{
					param: 'name',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_ORG_NAME',
					helptext: '',
					displayed: true,
					width: 'tw-w-2/6',
				},
				{
					param: 'description',
					type: 'textarea',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_ORG_DESCRIPTION',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'identifier_code',
					type: 'text',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_ORG_IDENTIFIER_CODE',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_ORG_IDENTIFIER_CODE_HELP',
					displayed: true,
					optional: true,
					width: 'tw-w-2/6',
				},
				{
					param: 'url_website',
					type: 'secure_url',
					placeholder: '',
					maxlength: 150,
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_ORG_URL_WEBSITE',
					helptext: '',
					displayed: true,
					optional: true,
					width: 'tw-w-2/6',
				},
				{
					param: 'logo',
					type: 'file',
					fileUrl: window.location.origin + '/index.php?option=com_emundus&task=crc.uploadLogo',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_ORG_LOGO',
					helptext: '',
					displayed: true,
					optional: true,
				},
			],

			setting_fields: [
				{
					param: 'referent_contacts',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getcontacts',
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
						optionsLimit: 15,
						label: 'name',
						trackBy: 'value',
					},
					value: 0,
					label: 'COM_EMUNDUS_ONBOARD_ADD_ORG_CONTACT',
					placeholder: 'COM_EMUNDUS_ONBOARD_ADD_ORG_CONTACTS_PLACEHOLDER',
					displayed: true,
					optional: true,
				},
				{
					param: 'other_contacts',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getcontacts',
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
						optionsLimit: 15,
						label: 'name',
						trackBy: 'value',
					},
					value: 0,
					label: 'COM_EMUNDUS_ONBOARD_ADD_ORG_OTHER_CONTACT',
					placeholder: 'COM_EMUNDUS_ONBOARD_ADD_ORG_OTHER_CONTACTS_PLACEHOLDER',
					displayed: true,
					optional: true,
				},
				{
					param: 'published',
					type: 'toggle',
					value: '1',
					label: 'COM_EMUNDUS_ONBOARD_ADD_ORG_PUBLISHED',
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
			],

			addresses: [],
		};
	},
	async created() {
		await this.getCountries();

		if (useGlobalStore().datas.orgid) {
			this.org_id = parseInt(useGlobalStore().datas.orgid.value);
		} else if (this.$props.id) {
			this.org_id = this.$props.id;
		}

		if (this.org_id) {
			this.getOrganization(this.org_id);
		} else {
			this.loading = false;
		}
	},
	methods: {
		async getCountries() {
			return new Promise((resolve, reject) => {
				settingsService.getCountries().then((response) => {
					if (response.status) {
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

		getOrganization(org_id) {
			crcService.getOrganization(org_id).then((response) => {
				if (response.status) {
					this.organization = response.data;

					const fields = [...this.main_fields, ...this.setting_fields];

					for (const field of fields) {
						if (this.organization[field.param]) {
							if (field.param === 'url_website') {
								field.value = this.organization[field.param].replace(/^https?:\/\//i, '').trim();
							} else {
								field.value = this.organization[field.param];
							}
						}
					}

					if (this.organization.address) {
						this.address_fields.forEach((field) => {
							if (this.organization.address[field.param]) {
								field.value = this.organization.address[field.param];
							}
						});
					}
					this.setting_fields.find((field) => field.param === 'referent_contacts').value = [];
					for (const contact of this.organization.referent_contacts) {
						this.setting_fields.find((field) => field.param === 'referent_contacts').value.push(contact.id);
					}

					this.setting_fields.find((field) => field.param === 'other_contacts').value = [];
					for (const contact of this.organization.other_contacts) {
						this.setting_fields.find((field) => field.param === 'other_contacts').value.push(contact.id);
					}

					if (this.organization.logo) {
						const base = window.location.origin + '/';
						this.main_fields.find((f) => f.param === 'logo').value = this.organization.logo.startsWith('https')
							? this.organization.logo
							: base + this.organization.logo;
					}
				}

				this.loading = false;
			});
		},

		saveOrganization() {
			let organization = {};

			// Merge all fields
			const fields = [...this.main_fields, ...this.address_fields, ...this.setting_fields];

			// Validate all fields
			const orgValidationFailed = fields.some((field) => {
				let ref_name = 'organization_' + field.param;

				if (!this.$refs[ref_name][0].validate()) {
					// Return true to indicate validation failed
					return true;
				}

				if (field.param === 'url_website' && field.value) {
					let url = field.value.trim();
					url = url.replace(/^https?:\/\//, '');
					organization[field.param] = 'https://' + url;
					return false;
				}

				if (field.type === 'multiselect') {
					if (field.multiselectOptions.multiple) {
						organization[field.param] = [];

						field.value.forEach((element) => {
							if (element.value) {
								organization[field.param].push(element.value);
							}
						});
					} else {
						if (field.value) {
							organization[field.param] = field.value[field.multiselectOptions.trackBy] ?? field.value;
						} else {
							organization[field.param] = null;
						}
					}
				} else {
					organization[field.param] = field.value;
				}

				return false;
			});

			if (orgValidationFailed) return;

			if (this.org_id) {
				organization.id = this.org_id;
			}
			const logoField = this.main_fields.find((f) => f.param === 'logo');
			if (logoField && typeof logoField.value === 'string' && logoField.value.startsWith('https')) {
				organization.logo = this.organization.logo;
			}

			crcService.saveOrganization(organization).then((response) => {
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
			const fields = [...this.main_fields, ...this.address_fields, ...this.setting_fields];

			if (
				fields.some((field) => {
					if (!field.optional) {
						return field.value === '' || field.value === 0;
					}
				})
			)
				return true;

			const websiteRef = this.$refs['organization_url_website']?.[0];
			return !!(websiteRef && !websiteRef.validate(false));
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
							this.organization && Object.keys(this.organization).length > 0
								? translate('COM_EMUNDUS_ONBOARD_EDIT_ORGANIZATION') + ' ' + this.organization['name']
								: translate('COM_EMUNDUS_ONBOARD_ADD_ORGANIZATION')
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
						this.organization && Object.keys(this.organization).length > 0
							? translate('COM_EMUNDUS_ONBOARD_EDIT_ORGANIZATION') + ' ' + this.organization['name']
							: translate('COM_EMUNDUS_ONBOARD_ADD_ORGANIZATION')
					}}
				</h1>

				<hr class="tw-mb-8 tw-mt-1.5" />
			</div>

			<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
				<h3>{{ translate('COM_EMUNDUS_ONBOARD_EDIT_ORG_MAIN') }}</h3>
				<div
					v-for="field in main_fields"
					:key="field.param"
					v-show="field.displayed"
					:class="field.width ? field.width : 'tw-w-full'"
				>
					<Parameter
						:ref="'organization_' + field.param"
						:parameter-object="field"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
						:help-text-type="'above'"
					/>
				</div>
			</div>

			<hr />

			<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
				<h3>{{ translate('COM_EMUNDUS_ONBOARD_EDIT_ORG_ADDRESS') }}</h3>
				<div
					v-for="field in address_fields"
					:key="field.param"
					v-show="field.displayed"
					:class="field.width ? field.width : 'tw-w-full'"
				>
					<Parameter
						:ref="'organization_' + field.param"
						:parameter-object="field"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					/>
				</div>
			</div>

			<hr />

			<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-6">
				<h3>{{ translate('COM_EMUNDUS_ONBOARD_EDIT_ORG_SETTINGS') }}</h3>
				<div
					v-for="field in setting_fields"
					:key="field.param"
					:class="field.width ? field.width : 'tw-w-full'"
					v-show="field.displayed"
				>
					<Parameter
						:ref="'organization_' + field.param"
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
					@click.prevent="saveOrganization()"
				>
					<span v-if="org_id">{{ translate('COM_EMUNDUS_ONBOARD_SAVE') }}</span>
					<span v-else>{{ translate('COM_EMUNDUS_ONBOARD_ADD_ORG_CREATE') }}</span>
				</button>
			</div>
		</div>

		<div v-else class="em-page-loader"></div>
	</div>
</template>
