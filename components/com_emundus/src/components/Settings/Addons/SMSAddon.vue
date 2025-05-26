<script>
import settingsService from '@/services/settings';
import { useGlobalStore } from '@/stores/global.js';
import Info from '@/components/Utils/Info.vue';
export default {
	name: 'SMSAddon',
	components: { Info },
	emits: ['addonSaved'],
	props: {
		addon: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			smsLink: '',
			encodings: [
				{
					label: 'GSM-7',
					value: 'GSM-7',
				},
				{
					label: 'UCS-2',
					value: 'UCS-2',
				},
			],
			services: [
				{
					label: 'OVH',
					value: 'ovh',
				},
			],
		};
	},
	created() {
		this.addon.configuration = JSON.parse(this.addon.configuration);
		settingsService
			.redirectJRoute('index.php?option=com_emundus&view=emails', useGlobalStore().getCurrentLang, false)
			.then((response) => {
				this.smsLink = response;
			});
	},
	methods: {
		saveAddon() {
			settingsService.saveAddon(this.addon).then(() => {
				Swal.fire({
					icon: 'success',
					title: this.translate('COM_EMUNDUS_SUCCESS'),
					text: this.translate('COM_EMUNDUS_SMS_ADDON_CONFIGURATION_SAVED'),
					showConfirmButton: false,
					delay: 2000,
				});
				this.$emit('addonSaved');
			});
		},
	},
	computed: {
		smsShortcuts() {
			return this.translate('COM_EMUNDUS_SETTINGS_ADDONS_SMS_SHORTCUT').replace('{{smsLink}}', this.smsLink);
		},
	},
};
</script>

<template>
	<div
		id="sms_addon"
		class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<h3>{{ translate('COM_EMUNDUS_SMS_ADDON_CONFIGURATION') }}</h3>

		<Info class="tw-mt-2" :text="smsShortcuts" icon="warning" />
		<Info
			text="COM_EMUNDUS_SMS_SERVICE_INFO"
			:icon="'warning'"
			:bg-color="'tw-bg-orange-100'"
			:icon-type="'material-icons'"
			:icon-color="'tw-text-orange-600'"
		/>

		<div id="service" class="tw-flex tw-flex-col">
			<label for="service">{{ translate('COM_EMUNDUS_SMS_SERVICE') }}</label>
			<select v-model="addon.configuration.service">
				<option v-for="service in services" :key="service.value" :value="service.value" name="service">
					{{ service.label }}
				</option>
			</select>
		</div>

		<div id="encoding" class="tw-flex tw-flex-col">
			<label for="encoding">{{ translate('COM_EMUNDUS_SMS_ENCODING') }}</label>
			<select v-model="addon.configuration.encoding">
				<option value="">{{ translate('COM_EMUNDUS_SMS_SELECT_ENCODING') }}</option>
				<option v-for="encoding in encodings" :key="encoding.value" :value="encoding.value" name="encoding">
					{{ encoding.label }}
				</option>
			</select>
		</div>

		<div id="actions" class="tw-flex tw-justify-end">
			<button class="tw-btn-primary" @click="saveAddon()">
				{{ translate('SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
