<script>
import signService from '@/services/sign.js';
import Modal from '@/components/Modal.vue';
import Info from '@/components/Utils/Info.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import settingsService from '@/services/settings.js';
import Back from '@/components/Utils/Back.vue';
import { v4 as uuid } from 'uuid';
import LocationPopup from '@/components/Events/Popup/LocationPopup.vue';
import ContactPopup from '@/components/Contacts/ContactPopup.vue';
import { useGlobalStore } from '@/stores/global.js';
import GridPreview from '@/components/Utils/GridPreview.vue';

import alerts from '@/mixins/alerts.js';

export default {
	name: 'SaveRequest',
	components: {
		GridPreview,
		ContactPopup,
		LocationPopup,
		Back,
		Parameter,
		Info,
		Modal,
	},
	props: {
		item: Object,
	},
	mixins: [alerts],
	emits: ['close', 'valueUpdated'],
	data: () => ({
		actualLanguage: 'fr-FR',
		loading: false,

		submitted: false,
		displayedInfo: false,
		tagsFound: 0,
		openedContactPopup: false,
		displaySigners: false,

		thumbnail: null,
		noPages: 0,
		currentContact: 0,
		currentSigner: 0,
		reloadContacts: 0,

		fields: [
			{
				param: 'ccid',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: false,
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
				value: 0,
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REQUEST_EDIT_APPLICANT',
				placeholder: 'COM_EMUNDUS_ONBOARD_REQUEST_EDIT_APPLICANT_PLACEHOLDER',
				displayed: true,
			},
			{
				param: 'attachment',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: false,
					taggable: false,
					searchable: true,
					internalSearch: false,
					asyncRoute: 'getattachmentstypes',
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
				value: 0,
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REQUEST_EDIT_ATTACHMENT',
				placeholder: 'COM_EMUNDUS_ONBOARD_REQUEST_EDIT_ATTACHMENT_PLACEHOLDER',
				displayed: true,
			},
			{
				param: 'upload',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: false,
					taggable: false,
					searchable: true,
					internalSearch: false,
					asyncRoute: 'getuploads',
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
				value: 0,
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REQUEST_EDIT_UPLOAD',
				placeholder: 'COM_EMUNDUS_ONBOARD_REQUEST_EDIT_UPLOAD_PLACEHOLDER',
				displayed: false,
				displayedOn: 'attachment',
			},
			{
				param: 'connector',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: false,
					taggable: false,
					searchable: true,
					internalSearch: false,
					asyncRoute: 'getconnectors',
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
				value: 0,
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONNECTOR',
				placeholder: 'COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONNECTOR_PLACEHOLDER',
				displayed: true,
				hidden: true,
			},
		],

		signers: [],
	}),
	created: function () {
		const urlParams = new URLSearchParams(window.location.search);

		if (this.item) {
			this.fields = this.item.fields.map((field) => {
				field.displayed = true;
				field.value = field.value ?? null;
				field.reload = 0;

				return field;
			});
		} else {
			this.fields.forEach((field) => {
				if (urlParams.has(field.param)) {
					field.value = urlParams.get(field.param);
				} else {
					field.value = null;
				}
			});
		}
	},
	methods: {
		redirectJRoute(link) {
			settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
		},

		// Form
		saveRequest() {
			this.submitted = true;
			let request_created = {};

			// Validate all fields
			const requestValidationFailed = this.fields.some((field) => {
				if (field.displayed) {
					let ref_name = 'request_' + field.param;

					if (!this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							request_created[field.param] = [];

							field.value.forEach((element) => {
								if (element.value) {
									request_created[field.param].push(element.value);
								}
							});
						} else {
							if (field.value) {
								request_created[field.param] = field.value[field.multiselectOptions.trackBy] ?? field.value;
							} else {
								request_created[field.param] = null;
							}
						}
					} else {
						request_created[field.param] = field.value;
					}

					if (
						!field.optional &&
						(!request_created[field.param] ||
							request_created[field.param] === 0 ||
							request_created[field.param] === '' ||
							request_created[field.param].length === 0)
					) {
						// Return true to indicate validation failed
						return true;
					}

					return false;
				}
			});

			if (requestValidationFailed) {
				this.alertError(
					'COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_INCOMPLETE_ERROR_TITLE',
					'COM_EMUNDUS_ONBOARD_REQUEST_SIGNATURE_INCOMPLETE_ERROR_TEXT',
				);

				this.submitted = false;
				return;
			}

			// Validate all signers
			let signersValidationFailed = this.signers.some((signer) => {
				let signerObject = {};
				signer.fields.forEach((field) => {
					let ref_name = 'signer_' + signer.id + '_' + field.param;

					if (!this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							signerObject[field.param] = [];

							field.value.forEach((element) => {
								if (element.value) {
									signerObject[field.param].push(element.value);
								}
							});
						} else {
							signerObject[field.param] = field.value[field.multiselectOptions.trackBy] ?? field.value;
						}
					} else {
						signerObject[field.param] = field.value;
					}
				});

				request_created['signers'] = request_created['signers'] || [];
				request_created['signers'].push(signerObject);

				return false;
			});

			request_created['signers'].forEach((signer) => {
				if (signer.signer === 0 || signer.signer === '' || signer.signer.length === 0) {
					signersValidationFailed = true;
				}
			});

			if (signersValidationFailed) {
				this.alertError(
					'COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_INCOMPLETE_ERROR_TITLE',
					'COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_INCOMPLETE_ERROR_TEXT',
				);
				this.submitted = false;
				return;
			}

			// Check if 2 signers are not in the same page and position
			let signersPositionFailed = false;
			if (request_created['signers'].length > 0) {
				let positions = [];
				request_created['signers'].forEach((signer) => {
					if (signer.position) {
						let position = signer.page + '-' + signer.position;
						if (positions.includes(position)) {
							signersPositionFailed = true;
						} else {
							positions.push(position);
						}
					}
				});
			}

			if (signersPositionFailed) {
				this.alertError(
					'COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_POSITION_ERROR_TITLE',
					'COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_POSITION_ERROR_TEXT',
				);
				this.submitted = false;
				return;
			}

			if (this.item) {
				request_created['id'] = this.item.id;
			} else {
				request_created['id'] = null;
			}

			request_created['signers'] = JSON.stringify(request_created['signers']);

			this.loading = true;
			signService.saveRequest(request_created).then((response) => {
				if (response.status === true) {
					this.loading = false;
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: this.item
							? this.translate('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_SAVED')
							: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_ADD_SAVED'),
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
						this.redirectJRoute('index.php?option=com_emundus&view=sign');
					});
				} else {
					this.loading = false;
					this.alertError('COM_EMUNDUS_ONBOARD_REQUEST_ERROR_TITLE', response.message);
				}
			});
		},

		addRepeatBlock(signer = 0) {
			let new_signer = {};
			new_signer.id = uuid();
			new_signer.fields = [
				{
					param: 'signer',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: false,
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
					placeholder: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_SIGNERS_PLACEHOLDER',
					maxlength: 150,
					value: signer,
					label: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER') + ' ' + (this.signers.length + 1),
					displayed: true,
					reload: 0,
				},
				{
					param: 'authentication_level',
					type: 'select',
					label: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_LABEL'),
					value: 'electronic_signature',
					options: [
						{
							value: 'electronic_signature',
							label: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_STANDARD'),
						},
						{
							value: 'advanced_electronic_signature',
							label: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_AES'),
						},
						{
							value: 'qualified_electronic_signature',
							label: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_QES'),
						},
					],
					displayed: true,
				},
			];

			if (this.tagsFound === 0 && this.noPages !== 0) {
				// Add page choice
				let page_choice_field = {
					param: 'page',
					type: 'select',
					value: 1,
					label: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_PAGE_LABEL'),
					displayed: true,
					reload: 0,
					options: [],
				};
				for (let i = 1; i <= this.noPages; i++) {
					page_choice_field.options.push({
						value: i,
						label: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_PAGE') + ' ' + i,
					});
				}
				new_signer.fields.push(page_choice_field);

				// Add position choice
				new_signer.fields.push({
					param: 'position',
					type: 'select',
					value: 'A1',
					label: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_POSITION_LABEL'),
					helptext: this.translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_POSITION_HELP'),
					displayed: true,
					hidden: true,
					reload: 0,
					options: [
						{
							value: 'A1',
							label: 'A1',
						},
						{
							value: 'A2',
							label: 'A2',
						},
						{
							value: 'A3',
							label: 'A3',
						},
						{
							value: 'B1',
							label: 'B1',
						},
						{
							value: 'B2',
							label: 'B2',
						},
						{
							value: 'B3',
							label: 'B3',
						},
						{
							value: 'C1',
							label: 'C1',
						},
						{
							value: 'C2',
							label: 'C2',
						},
						{
							value: 'C3',
							label: 'C3',
						},
					],
				});
			}
			this.signers.push(new_signer);
		},

		removeRepeatBlock(signer_id) {
			const key = this.signers.findIndex((signer) => signer.id === signer_id);
			this.signers.splice(key, 1);

			this.$forceUpdate();
		},

		// Hooks
		asyncAttributes(parameter) {
			if (parameter.param === 'upload') {
				let ccid = this.fields.find((f) => f.param === 'ccid')?.value;
				ccid = this.getMultiselectValue(ccid);

				let attachment = this.fields.find((f) => f.param === 'attachment')?.value;
				attachment = this.getMultiselectValue(attachment);

				return [ccid, attachment, parameter.param];
			} else {
				return [];
			}
		},
		onClosePopup() {
			this.$emit('close');
		},
		onAjaxOptionsLoaded(options, parameter_name) {
			if (parameter_name === 'connector') {
				if (options.length === 0) {
					this.displayedInfo = true;
				} else if (options.length === 1) {
					this.fields.find((f) => f.param === 'connector').value = options[0].value;
				} else {
					this.fields.find((f) => f.param === 'connector').hidden = false;
				}
			}

			if (parameter_name === 'signer' && this.currentContact !== 0) {
				this.signers.find((s) => s.id === this.currentSigner).fields.find((f) => f.param === 'signer').value =
					this.currentContact;
				this.currentContact = 0;
				this.currentSigner = 0;
			}
		},
		checkConditional(parameter, oldValue, value) {
			if (parameter.param === 'attachment' || parameter.param === 'ccid') {
				this.displaySigners = false;
				this.thumbnail = null;
				this.tagsFound = 0;
				this.signers = [];

				let displayed = value && typeof value === 'object' && value.value && value.value !== 0;

				if (displayed) {
					if (parameter.param === 'attachment') {
						// Check if ccid is empty
						let ccid = this.fields.find((f) => f.param === 'ccid')?.value;
						ccid = this.getMultiselectValue(ccid);

						this.fields.find((f) => f.param === 'upload').displayed = !(!ccid || ccid === 0 || ccid === '');
					}

					if (parameter.param === 'ccid') {
						// Check if attachment is empty
						let attachment = this.fields.find((f) => f.param === 'attachment')?.value;
						attachment = this.getMultiselectValue(attachment);

						this.fields.find((f) => f.param === 'upload').displayed = !(
							!attachment ||
							attachment === 0 ||
							attachment === ''
						);
					}

					// Reload the upload field
					this.fields.find((f) => f.param === 'upload').reload =
						this.fields.find((f) => f.param === 'upload').reload + 1;
				} else {
					this.fields.find((f) => f.param === 'upload').displayed = false;
				}
			}

			if (parameter.param === 'upload' && typeof value !== 'undefined' && value && value !== 0 && value !== '') {
				this.signers = [];

				if (typeof value !== 'undefined' && value && value !== 0 && value !== '') {
					settingsService.getFileInfosFromUploadId(this.getMultiselectValue(value)).then((response) => {
						if (response.status === true) {
							this.displaySigners = true;
							this.thumbnail = response.data.thumbnail;

							if (response.data.pages_length) {
								this.noPages = response.data.pages_length;
							} else {
								this.noPages = 0;
							}

							// If response.data.text is not empty, search for tags like {{s*|signature|**}}
							if (response.data.text) {
								const regex = /{{s\d+\|signature(?:\|[^}|]+)*}}/g;
								const matches = response.data.text.match(regex);

								if (matches) {
									this.tagsFound = matches.length;
									matches.forEach((match) => {
										this.addRepeatBlock();
									});
								} else {
									this.tagsFound = 0;
									this.addRepeatBlock();
								}
							} else {
								this.tagsFound = 0;
								this.addRepeatBlock();
							}
						} else {
							this.thumbnail = null;
							this.noPages = 0;
							this.tagsFound = 0;
							this.displaySigners = false;
						}
					});
				} else {
					this.displaySigners = false;
				}
			}
		},
		getMultiselectValue(value) {
			if (value && typeof value === 'object') {
				value = value.value;
			} else {
				value = '';
			}

			return value;
		},
		contactPopupClosed(contact_id) {
			this.openedContactPopup = false;
			this.currentContact = contact_id;

			// Reload all signer fields
			this.reloadContacts++;
		},
		updatePosition(position, signer_id) {
			this.signers.find((s) => s.id === signer_id).fields.find((f) => f.param === 'position').value = position;
			this.signers.find((s) => s.id === signer_id).fields.find((f) => f.param === 'position').reload++;
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

		textTagsFound: function () {
			if (this.tagsFound > 1) {
				return this.translate('COM_EMUNDUS_ONBOARD_REQUEST_TAGS_FOUND').replace('{{signers_count}}', this.tagsFound);
			} else {
				return this.translate('COM_EMUNDUS_ONBOARD_REQUEST_TAG_FOUND').replace('{{signers_count}}', this.tagsFound);
			}
		},
	},
};
</script>

<template>
	<div class="tw-relative tw-mb-6 tw-w-full tw-rounded-2xl tw-border tw-border-neutral-300 tw-bg-white tw-p-6">
		<ContactPopup v-if="openedContactPopup" :contact_id="currentContact" @close="contactPopupClosed" />

		<div>
			<Back link="index.php?option=com_emundus&view=sign" ref="back_button" />

			<div class="tw-mt-4">
				<h1 v-if="item">
					{{ translate('COM_EMUNDUS_ONBOARD_REQUEST_EDIT') }}
				</h1>
				<h1 v-else>
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_REQUEST') }}
				</h1>
			</div>
		</div>

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
			<Info v-if="displayedInfo" text="COM_EMUNDUS_ONBOARD_REQUEST_NO_CONNECTORS" />

			<div
				v-for="field in fields"
				v-show="field.displayed && !field.hidden"
				:key="field.param"
				:class="'tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-2'"
			>
				<Parameter
					v-if="field.displayed"
					v-show="!field.hidden"
					:ref="'request_' + field.param"
					:key="field.reload ? field.reload + field.param : field.param"
					:parameter-object="field"
					:help-text-type="'below'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					:asyncAttributes="asyncAttributes(field)"
					@ajax-options-loaded="onAjaxOptionsLoaded"
					@value-updated="checkConditional"
				/>

				<div v-if="field.param === 'upload' && thumbnail && thumbnail !== ''" class="tw-mt-4">
					<img
						:src="'data:image/png;base64,' + thumbnail"
						alt="Thumbnail"
						class="tw-w-32 tw-rounded-coordinator tw-border tw-border-solid tw-border-neutral-300 tw-object-cover"
					/>
				</div>
			</div>

			<Info v-if="tagsFound" :text="textTagsFound" />

			<!-- REPEAT GROUP -->
			<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-3" v-show="displaySigners">
				<h3>{{ translate('COM_EMUNDUS_ONBOARD_REQUEST_SIGNERS_TITLE') }}</h3>

				<div
					v-for="signer in signers"
					:key="signer.id"
					class="tw-flex tw-flex-col tw-gap-2 tw-rounded-2xl tw-border tw-border-neutral-400 tw-bg-white tw-p-6"
				>
					<div class="tw-flex tw-items-center tw-justify-end tw-gap-2">
						<button
							v-if="signers.length > 0 && tagsFound === 0"
							type="button"
							@click="removeRepeatBlock(signer.id)"
							class="w-auto"
						>
							<span class="material-symbols-outlined tw-text-red-600">close</span>
						</button>
					</div>

					<div class="tw-flex tw-flex-col tw-gap-6">
						<div
							v-for="field in signer.fields"
							:key="field.param"
							class="tw-flex tw-w-full tw-gap-2"
							:class="[field.param === 'signer' ? 'tw-items-end' : '', field.param === 'position' ? 'tw-flex-col' : '']"
							v-show="field.displayed"
						>
							<Parameter
								class="tw-w-full"
								:ref="'signer_' + signer.id + '_' + field.param"
								:key="field.param === 'signer' ? reloadContacts : field.reload"
								:help-text-type="'above'"
								:parameter-object="field"
								:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
								@ajax-options-loaded="onAjaxOptionsLoaded"
							/>

							<button
								v-if="field.param === 'signer' && (field.value === 0 || !field.value || field.value.length === 0)"
								type="button"
								class="tw-btn-primary tw-h-form tw-w-form tw-p-3"
								:title="translate('COM_EMUNDUS_ONBOARD_ADD_REQUEST_CREATE_CONTACT')"
								@click="
									currentSigner = signer.id;
									openedContactPopup = true;
								"
							>
								<span class="material-symbols-outlined">add_circle</span>
							</button>
							<button
								v-else-if="field.param === 'signer'"
								type="button"
								class="tw-btn-primary tw-h-form tw-w-form tw-p-3"
								:title="translate('COM_EMUNDUS_ONBOARD_ADD_REQUEST_EDIT_CONTACT')"
								@click="
									currentSigner = signer.id;
									currentContact = field.value.value;
									openedContactPopup = true;
								"
							>
								<span class="material-symbols-outlined">edit</span>
							</button>

							<!-- Position grid -->
							<GridPreview
								v-if="field.param === 'position'"
								:columns="['A', 'B', 'C']"
								:rows="[1, 2, 3]"
								:current-position="field.value"
								:image="'data:image/png;base64,' + thumbnail"
								@update-position="(position) => updatePosition(position, signer.id)"
								class="tw-w-fit"
							/>
						</div>
					</div>
				</div>

				<div class="tw-flex tw-justify-end" v-if="tagsFound === 0">
					<button
						type="button"
						@click="addRepeatBlock()"
						class="tw-btn-secondary tw-mt-2 tw-flex tw-h-form tw-w-auto tw-items-center tw-gap-1 tw-rounded-coordinator"
					>
						<span class="material-symbols-outlined">add</span>
						<span>{{ translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_SIGNER') }}</span>
					</button>
				</div>
			</div>
		</div>

		<div class="tw-mt-5 tw-flex tw-justify-end">
			<button class="tw-btn-primary" :disabled="submitted" @click="saveRequest()">
				{{ translate('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONFIRM') }}
			</button>
		</div>

		<div class="em-page-loader" v-if="!fields[1].displayed || loading"></div>
	</div>
</template>

<style scoped></style>
