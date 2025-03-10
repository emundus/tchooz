<template>
	<div id="sms-app-file">
		<Tabs
			:classes="'tw-overflow-x-scroll tw-right-6 tw-flex tw-items-center tw-gap-2 tw-ml-4'"
			:tabs="tabs"
			@changeTabActive="onChangeTabActive"
		></Tabs>

		<div class="tw-border tw-border-neutral-300 em-card-shadow tw-rounded-2xl tw-bg-white tw-p-6">
			<SMSHistory v-if="selectedTab === 'history'" :fnum="fnum"></SMSHistory>
			<SMSSend v-else :fnums="[fnum]"></SMSSend>
		</div>
	</div>
</template>

<script>
import SMSSend from '@/views/SMS/SMSSend.vue';
import SMSHistory from '@/views/SMS/SMSHistory.vue';
import Tabs from '@/components/Utils/Tabs.vue';

export default {
	name: 'SMSAppFile',
	components: { SMSHistory, SMSSend, Tabs },
	props: {
		fnum: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			tabs: [
				{ id: 'history', name: 'COM_EMUNDUS_SMS_HISTORY', active: true, displayed: true },
				{ id: 'send', name: 'COM_EMUNDUS_SEND_SMS', active: false, displayed: true },
			],
			selectedTab: 'history',
		};
	},
	methods: {
		onChangeTabActive(id) {
			this.selectedTab = id;
		},
	},
};
</script>

<style scoped></style>
