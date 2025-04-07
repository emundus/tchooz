<template>
	<div
		id="email-edition"
		class="tw-flex-col tw-rounded-coordinator tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<Back class="tw-mb-4" :link="'index.php?option=com_emundus&view=' + parentView" />

		<FormHead :title="'COM_EMUNDUS_ONBOARD_ADD_SMS'" />

		<div class="tw-flex tw-flex-col tw-gap-4">
			<div>
				<label class="tw-font-medium">
					{{ translate('COM_EMUNDUS_SMS_LABEL') }}
					<span class="tw-text-red-600">*</span>
				</label>
				<input class="tw-mt-1" id="label" type="text" v-model="smsTemplate.label" />
			</div>

			<div>
				<label class="tw-font-medium">
					{{ translate('COM_EMUNDUS_SMS_MESSAGE') }}
					<span class="tw-text-red-600">*</span>
				</label>
				<textarea
					class="tw-mt-1"
					rows="3"
					id="message"
					v-model="smsTemplate.message"
					:placeholder="translate('COM_EMUNDUS_SMS_PLACEHOLDER')"
				/>
				<div v-if="maxLength > 0" class="tw-mt-1 tw-flex tw-justify-between tw-text-sm tw-text-gray-500">
					<span class="tw-text-sm tw-text-gray-500">{{
						translate('COM_EMUNDUS_SMS_MESSAGE_MAX_LENGTH') + ' ' + maxLength
					}}</span>
					<span class="tw-text-sm tw-text-gray-500">{{
						translate('COM_EMUNDUS_SMS_MESSAGE_CURRENT_LENGTH') + ' ' + smsTemplate.message.length
					}}</span>
				</div>
			</div>

			<div v-if="categoriesLoaded">
				<label class="tw-font-medium">
					{{ translate('COM_EMUNDUS_SMS_CATEGORY') }}
				</label>
				<IncrementalSelect
					:options="categories"
					:defaultValue="smsTemplate.category_id"
					@update-value="updateCategory"
				/>
			</div>

			<hr />

			<h2>{{ translate('COM_EMUNDUS_SMS_ADVANCED_PARAMETERS') }}</h2>
			<div id="advanced-parameters" class="tw-flex tw-flex-col tw-gap-4">
				<div class="tw-flex tw-flex-col tw-gap-1">
					<label class="tw-font-medium">
						{{ translate('COM_EMUNDUS_SMS_ASSOC_TAG_ON_SUCCESS') }}
					</label>
					<select id="success_tag" v-model="smsTemplate.success_tag">
						<option value="">
							{{ translate('COM_EMUNDUS_SMS_NO_ASSOC_TAG') }}
						</option>
						<option v-for="tag in tags" :key="tag.id" :value="tag.id">
							{{ tag.label }}
						</option>
					</select>
				</div>

				<div class="tw-flex tw-flex-col tw-gap-1">
					<label class="tw-font-medium">
						{{ translate('COM_EMUNDUS_SMS_ASSOC_TAG_ON_FAILURE') }}
					</label>
					<select id="failure_tag" v-model="smsTemplate.failure_tag">
						<option value="">
							{{ translate('COM_EMUNDUS_SMS_NO_ASSOC_TAG') }}
						</option>
						<option v-for="tag in tags" :key="tag.id" :value="tag.id">
							{{ tag.label }}
						</option>
					</select>
				</div>
			</div>

			<hr class="tw-mb-4 tw-mt-1.5" />

			<!-- HELP SECTION, closed by default -->
			<div
				class="tw-flex tw-flex-col tw-gap-4 tw-rounded tw-border tw-border-neutral-300 tw-bg-neutral-100 tw-p-4 tw-shadow"
			>
				<div
					class="tw-flex tw-cursor-pointer tw-items-center tw-justify-between"
					@click="displayHelpSection = !displayHelpSection"
				>
					<h2>{{ translate('COM_EMUNDUS_SMS_HELP_SECTION') }}</h2>
					<span class="material-symbols-outlined">add_circle_outline</span>
				</div>

				<div v-if="displayHelpSection">
					<div v-html="translate('COM_EMUNDUS_SMS_HELP_SECTION_CONTENT')"></div>
				</div>
			</div>

			<div id="actions" class="tw-flex tw-justify-end">
				<button class="tw-btn-primary" @click="save(false, false)">
					{{ translate('SAVE') }}
				</button>
			</div>
		</div>
	</div>
</template>

<script>
import smsService from '@/services/sms';
import translate from '@/mixins/translate.js';
import settingsService from '@/services/settings.js';
import { useGlobalStore } from '@/stores/global.js';

import IncrementalSelect from '@/components/IncrementalSelect.vue';
import Back from '@/components/Utils/Back.vue';
import FormHead from '@/components/Utils/Form/FormHead.vue';

export default {
	name: 'SMSEdit',
	props: {
		id: {
			type: Number,
			required: true,
		},
		parentView: {
			type: String,
			default: 'emails',
		},
	},
	components: {
		FormHead,
		Back,
		IncrementalSelect,
	},
	mixins: [translate],
	data() {
		return {
			smsTemplate: {},
			categories: [],
			categoriesLoaded: false,
			tags: [],
			encoding: 'GSM-7',
			maxLength: 0,

			displayHelpSection: false,
		};
	},
	created() {
		this.getSmsTemplate(this.id);
		this.getSMSCategories();
		this.getTags();
		this.setEncoding();
	},
	methods: {
		setEncoding() {
			smsService.getSMSConfiguration().then((response) => {
				const configuration = response.data;
				this.encoding = configuration.encoding;

				if (this.encoding === 'GSM-7') {
					this.maxLength = 160;
				}
			});
		},
		getSmsTemplate(id) {
			smsService.getSmsTemplate(id).then((response) => {
				this.smsTemplate = response.data;
			});
		},
		save(allow_unicode = false, allow_override_length = false) {
			if (!allow_override_length && this.maxLength > 0 && this.smsTemplate.message.length > this.maxLength) {
				// ask if users still want to save the message
				Swal.fire({
					title: this.translate('COM_EMUNDUS_SMS_MESSAGE_TOO_LONG', { maxLength: this.maxLength }),
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: this.translate('COM_EMUNDUS_ACTIONS_SAVE_ANYWAY'),
					cancelButtonText: this.translate('COM_EMUNDUS_ACTIONS_CANCEL'),
				}).then((result) => {
					if (result.isConfirmed) {
						this.save(allow_unicode, true);
					}
				});
			} else {
				smsService.updateTemplate(this.smsTemplate, allow_unicode).then((response) => {
					if (response.status) {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_SMS_UPDATED_SUCCESSFULLY'),
							timer: 2000,
							icon: 'success',
							showConfirmButton: false,
						});
					} else {
						if (response.data && response.data.invalid_characters) {
							// we will propose a valid message, or to save the message anyway but make him that encoding will be changed, and how much sms will be used
							Swal.fire({
								title: this.translate('COM_EMUNDUS_SMS_INCOMPATIBLE_ENCODING'),
								html: this.encodingErrorToHtml(response.data),
								icon: 'warning',
								showCancelButton: true,
								confirmButtonText: this.translate('COM_EMUNDUS_ACTIONS_SAVE_ANYWAY'),
								cancelButtonText: this.translate('COM_EMUNDUS_ACTIONS_CANCEL'),
							}).then((result) => {
								if (result.isConfirmed) {
									this.save(true, allow_override_length);
								} else {
									this.getSmsTemplate(this.id);
								}
							});
						} else {
							Swal.fire({
								title: this.translate('SMS_TEMPLATE_NOT_UPDATED'),
								icon: 'error',
								showCancelButton: false,
								showConfirmButton: false,
								timer: 5000,
							});
						}
					}
				});
			}
		},
		redirectJRoute(link) {
			settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
		},
		updateCategory(value) {
			if (value.id == 0 && value.label == '') {
				this.smsTemplate.category_id = 0;
				return;
			}

			if (value.id > 0) {
				this.smsTemplate.category_id = value.id;

				smsService.updateSMSCategory(value.id, value.label);
			} else {
				smsService.createSMSCategory(value.label).then((response) => {
					this.smsTemplate.category_id = response.data;

					this.getSMSCategories();
				});
			}
		},
		getSMSCategories() {
			this.categoriesLoaded = false;
			smsService.getSMSCategories().then((response) => {
				this.categories = response.data;
				this.categoriesLoaded = true;
			});
		},
		getTags() {
			settingsService.getTags().then((response) => {
				this.tags = response.data;
			});
		},

		encodingErrorToHtml(data) {
			let html = '<p>' + this.translate('COM_EMUNDUS_SMS_ENCODING_ERROR_DESCRIPTION') + '</p>';

			if (data.invalid_characters) {
				html += '<p>' + this.translate('COM_EMUNDUS_SMS_INVALID_CHARACTERS') + '</p>';
				html += '<p>' + data.invalid_characters + '</p>';
			}

			if (data.sanitized_message) {
				html += '<p>' + this.translate('COM_EMUNDUS_SMS_SANITIZED_MESSAGE') + '</p>';
				html += '<textarea>' + data.sanitized_message + '</textarea>';
			}

			if (data.compatibility) {
				html += '<p>' + this.translate('COM_EMUNDUS_SMS_COMPATIBILITY') + '</p>';
				html += '<p>' + data.compatibility.encoding + '</p>';

				html += '<p>' + this.translate('COM_EMUNDUS_SMS_COMPATIBILITY_PARTS') + '</p>';
				html += '<p>' + data.compatibility.parts + '</p>';
			}

			return html;
		},
	},
};
</script>

<style scoped></style>
