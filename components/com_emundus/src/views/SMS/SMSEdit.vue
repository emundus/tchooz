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
				<textarea class="tw-mt-1" rows="3" id="message" v-model="smsTemplate.message" />
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

			<div id="actions" class="tw-flex tw-justify-end">
				<button class="tw-btn-primary" @click="save">
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
		};
	},
	created() {
		this.getSmsTemplate(this.id);
		this.getSMSCategories();
		this.getTags();
	},
	methods: {
		getSmsTemplate(id) {
			smsService.getSmsTemplate(id).then((response) => {
				this.smsTemplate = response.data;
			});
		},
		save() {
			smsService.updateTemplate(this.smsTemplate).then((response) => {
				if (response.status) {
					Swal.fire({
						title: this.translate('COM_EMUNDUS_SMS_UPDATED_SUCCESSFULLY'),
						timer: 2000,
						icon: 'success',
						showConfirmButton: false,
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
			});
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
	},
};
</script>

<style scoped></style>
