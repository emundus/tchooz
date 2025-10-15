<template>
	<div id="sms-history">
		<h1 class="tw-mb-4">{{ translate('COM_EMUNDUS_SMS_HISTORY') }}</h1>
		<div v-if="smsHistory.length > 0">
			<div
				v-for="sms in smsHistory"
				:key="sms.id"
				class="em-card-shadow tw-mb-4 tw-rounded-lg tw-border tw-border-neutral-300 tw-bg-white tw-p-6"
			>
				<div class="from tw-mb-2 tw-flex tw-justify-between">
					<div class="tw-flex tw-flex-col">
						<span class="tw-text-xs tw-text-neutral-500">{{ sms.params.date }}</span>
						<span class="tw-text-xs">{{ translate('COM_EMUNDUS_EMAILS_MESSAGE_FROM') }} {{ sms.user_name_from }}</span>
					</div>
					<div>
						<span
							v-if="sms.status === 'sent'"
							class="material-symbols-outlined tw-text-main-400"
							:title="translate('COM_EMUNDUS_SMS_SENT')"
							>done_all</span
						>
						<span
							v-else-if="sms.status === 'pending'"
							class="material-symbols-outlined tw-text-yellow-600"
							:title="translate('COM_EMUNDUS_SMS_PENDING')"
							>schedule_send</span
						>
						<span
							v-else-if="sms.status === 'failed'"
							class="material-symbols-outlined tw-text-red-400"
							:title="translate('COM_EMUNDUS_SMS_FAILED')"
							>cancel_schedule_send</span
						>
					</div>
				</div>
				<p v-html="replaceWithBr(sms.params.message)" class="tw-whitespace-pre-line"></p>
			</div>
		</div>
		<div v-else id="empty-list" class="tw-text-center">
			<img
				src="@media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg"
				alt="empty-list"
				class="no-result-display tw-mx-auto tw-mt-8 tw-w-1/2"
				style="width: 10vw; height: 10vw; margin: 0 auto"
			/>
			<p>{{ translate('COM_EMUNDUS_SMS_EMPTY_HISTORY') }}</p>
		</div>
	</div>
</template>

<script>
import smsService from '@/services/sms';

export default {
	name: 'SMSHistory',
	props: {
		fnum: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			smsHistory: [],
		};
	},
	created() {
		this.getSMSHistory();
	},
	methods: {
		getSMSHistory() {
			smsService.getHistory(this.fnum).then((response) => {
				this.smsHistory = response.data.datas.map((message) => {
					return {
						id: message.id,
						message: message.message,
						user_id_from: message.user_id_from,
						user_name_from: message.lastname + ' ' + message.firstname,
						params: message.params,
						status: message.status,
					};
				});
			});
		},
		replaceWithBr(text) {
			return text.replace(/\n/g, '<br>').replace(/\r/g, '<br>');
		},
	},
};
</script>

<style scoped></style>
