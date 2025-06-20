<template>
	<div id="sms-send" class="tw-flex tw-flex-col tw-gap-2">
		<h1>{{ translate('COM_EMUNDUS_SEND_SMS_TITLE') }}</h1>

		<div class="tw-flex tw-flex-col tw-gap-1" v-if="categories.length > 0">
			<label class="tw-font-medium">{{ translate('COM_EMUNDUS_SMS_TEMPLATE_CATEGORY') }}</label>
			<select v-model="selectedCategory">
				<option value="0">
					{{ translate('COM_EMUNDUS_SMS_TEMPLATE_CATEGORY_PLACEHOLDER') }}
				</option>
				<option v-for="category in categories" :key="category.id" :value="category.id">
					{{ category.label }}
				</option>
			</select>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-1" v-if="templates.length > 0">
			<label class="tw-font-medium">{{ translate('COM_EMUNDUS_SMS_TEMPLATE') }}</label>
			<select v-model="selectedTemplate">
				<option value="0">
					{{ translate('COM_EMUNDUS_SMS_TEMPLATE_PLACEHOLDER') }}
				</option>
				<option v-for="template in displayedTemplates" :key="template.id" :value="template.id">
					{{ template.label[useGlobalStore().getShortLang] }}
				</option>
			</select>
		</div>

		<div>
			<div
				class="tw-flex tw-w-full tw-cursor-pointer tw-flex-row tw-justify-between"
				@click="displayRecipients = !displayRecipients"
			>
				<label
					>{{ translate('COM_EMUNDUS_SMS_RECIPIENTS') }}
					<span v-if="fnums.length > 1">({{ fnums.length }})</span>
				</label>
				<span v-if="displayRecipients" class="material-symbols-outlined">keyboard_arrow_up</span>
				<span v-else-if="!displayRecipients" class="material-symbols-outlined">keyboard_arrow_down</span>
			</div>
			<transition name="fade" mode="in-out">
				<ul v-if="displayRecipients">
					<li v-for="fnum in fnums" :key="fnum">
						<span v-if="recipients[fnum]"
							>{{ recipients[fnum].username }} - {{ fnum }} -
							{{ recipients[fnum].tel ? recipients[fnum].tel : translate('COM_EMUNDUS_SMS_RECIPIENT_NO_TEL') }}</span
						>
						<span v-else>{{ fnum }}</span>
					</li>
				</ul>
			</transition>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-1">
			<label>{{ translate('COM_EMUNDUS_SMS_MESSAGE') }}</label>
			<textarea v-model="message" rows="3"></textarea>
		</div>

		<div class="tw-flex tw-justify-end">
			<button class="tw-btn-primary tw-w-fit" :disabled="message.length < 1 || fnums.length < 1" @click="sendSMS">
				{{ translate('COM_EMUNDUS_SEND_SMS_ACTION') }} <span> ({{ fnums.length }})</span>
			</button>
		</div>
	</div>
</template>

<script>
import smsService from '@/services/sms';
import { useGlobalStore } from '../../stores/global.js';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'SMSSend',
	props: {
		fnums: {
			type: Array,
			required: true,
		},
	},
	mixins: [alerts],
	data() {
		return {
			templates: [],
			selectedTemplate: 0,

			categories: [],
			selectedCategory: 0,

			recipients: {},

			message: '',

			displayRecipients: false,
		};
	},
	created() {
		this.getSMSTemplates();
		this.getSMSCategories();
		this.getRecipients();
	},
	methods: {
		useGlobalStore,
		getSMSTemplates() {
			smsService.getSmsTemplates().then((response) => {
				this.templates = response.data.datas;
			});
		},
		getSMSCategories() {
			smsService.getSMSCategories().then((response) => {
				this.categories = response.data;
			});
		},
		getRecipients() {
			smsService.getRecipientsData(this.fnums).then((response) => {
				this.recipients = response.data;
			});
		},
		sendSMS() {
			if (this.fnums.length < 1 || this.message.length < 1) {
				Swal.fire({
					icon: 'error',
					title:
						this.fnums.length < 1
							? this.translate('COM_EMUNDUS_SMS_NO_RECIPIENTS')
							: this.translate('COM_EMUNDUS_SMS_NO_MESSAGE'),
					showCancelButton: false,
					showConfirmButton: false,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						actions: 'em-swal-single-action',
						htmlContainer: '!tw-text-center',
					},
					timer: 3000,
				});
				return;
			}

			smsService.sendSMS(this.fnums, this.message, this.selectedTemplate).then((response) => {
				if (response.status) {
					Swal.fire({
						iconHtml:
							'<img class="tw-max-w-none" style="width: 180px;" src="/media/com_emundus/images/tchoozy/complex-illustrations/message-sent.svg"/>',
						title: this.translate('COM_EMUNDUS_SMS_SENT_SUCCESS'),
						text: this.translate('COM_EMUNDUS_SMS_SENT_SUCCESS_MESSAGE'),
						showCancelButton: false,
						showConfirmButton: false,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
							htmlContainer: '!tw-text-center',
						},
						timer: 3000,
					});

					this.reset();
				} else {
					this.alertError(this.translate('COM_EMUNDUS_SMS_NOT_SENT'), response.msg);
				}
			});
		},
		reset() {
			this.selectedTemplate = 0;
			this.selectedCategory = 0;
			this.message = '';
		},
	},
	computed: {
		displayedTemplates() {
			return this.selectedCategory > 0
				? this.templates.filter((template) => template.category_id === this.selectedCategory)
				: this.templates;
		},
	},
	watch: {
		selectedTemplate: function (val) {
			if (val) {
				this.message = this.templates.find((template) => template.id === val).message;
			}
		},
	},
};
</script>

<style scoped></style>
