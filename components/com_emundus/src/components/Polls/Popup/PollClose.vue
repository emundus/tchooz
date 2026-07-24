<script>
import PollSlotContact from '@/components/Polls/Popup/PollSlotContact.vue';
import ModalHeader from '@/components/Utils/Modal/Header.vue';
import { useGlobalStore } from '@/stores/global.js';
import alerts from '@/mixins/alerts.js';
import pollService from '@/services/poll.js';
import settingsService from '@/services/settings.js';
import { Button, Chip } from '@emundus/ui';

export default {
	name: 'PollClose',
	components: { Button, ModalHeader, PollSlotContact, Chip },
	mixins: [alerts],
	props: {
		item: Object,
		items: {
			type: Array,
			default: () => [],
		},
		selectedItems: {
			type: Array,
			default: () => [],
		},
	},
	emits: ['close', 'update-items'],
	data() {
		return {
			notify: false,
			selected: [],
			contactOpen: false,
			shortLang: 'fr',
			isRunning: false,

			pollAddonConfiguration: {
				configuration: {
					close_email_subject: '',
					close_email_body: '',
				},
			},
		};
	},
	created() {
		if (!this.item && (!this.items || this.items.length === 0)) {
			this.$emit('close');
			return;
		}
		this.shortLang = useGlobalStore().getShortLang;
		if (this.item) {
			this.selected = [this.item];
		} else {
			this.selected = [...this.selectedItems];
		}

		settingsService.getAddon('poll').then((response) => {
			if (response && response.status === true && response.data) {
				this.pollAddonConfiguration = response.data.params;
			}
		});
	},
	computed: {
		pollChips() {
			return this.selected.map((poll) => ({
				id: poll.id,
				label: this.pollLabel(poll),
			}));
		},
		recipients() {
			const seen = new Set();
			const recipients = [];
			this.selected.forEach((poll) => {
				(poll.participants || []).forEach((p) => {
					if (!p.email || seen.has(p.email)) return;
					seen.add(p.email);
					recipients.push(p);
				});
			});
			return recipients;
		},
		firstPollName() {
			return this.selected.length > 0 ? this.pollLabel(this.selected[0]) : '';
		},
	},
	methods: {
		pollLabel(poll) {
			if (!poll) return '';
			if (poll.name) return poll.name;
			if (poll.label && this.shortLang && poll.label[this.shortLang]) return poll.label[this.shortLang];
			return '';
		},
		removePoll(id) {
			this.selected = this.selected.filter((p) => p.id !== id);
			if (this.selected.length === 0) this.$emit('close');
		},
		onNotifyChange() {
			this.contactOpen = this.notify;
		},
		closeContact() {
			this.contactOpen = false;
			this.notify = false;
		},
		async closePoll() {
			if (this.isRunning) return;

			const ids = this.selected.map((p) => p.id);
			if (ids.length === 0) return;

			const payload = { ids: ids.join(','), recipients: this.recipients.map((r) => r.id).join(',') };
			if (this.notify && this.$refs.pollSlotContact) {
				payload.notify = 1;
				payload.subject = this.$refs.pollSlotContact.subject;
				payload.body = this.$refs.pollSlotContact.body;
				payload.reply_to = this.$refs.pollSlotContact.replyTo;
			}

			this.isRunning = true;
			try {
				const response = await pollService.closePoll(payload);
				if (response && response.status === true) {
					this.alertSuccess('COM_EMUNDUS_POLLS_CLOSE');
					this.$emit('update-items');
					this.$emit('close');
				} else {
					this.alertError(response?.message || response?.msg || 'COM_EMUNDUS_POLL_RUN_NO_IDS');
				}
			} finally {
				this.isRunning = false;
			}
		},
		handleSend() {
			this.closePoll();
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-6">
		<div class="tw-flex tw-flex-col tw-gap-6 tw-p-2">
			<ModalHeader :title="translate('COM_EMUNDUS_POLL_CLOSE_TITLE')" @close="$emit('close')" />

			<div class="tw-flex tw-flex-col tw-gap-2">
				<label class="tw-text-base tw-font-medium">
					{{ translate('COM_EMUNDUS_POLL_CLOSE_FIELD_LABEL') }}
				</label>
				<div
					class="tw-flex tw-min-h-[40px] tw-flex-wrap tw-items-center tw-gap-2 tw-rounded-lg tw-border tw-border-neutral-300 tw-bg-white tw-px-3 tw-py-1"
				>
					<Chip v-for="chip in pollChips" :label="chip.label" closable variant="neutral" @close="removePoll(chip.id)" />
				</div>
			</div>

			<label class="tw-flex tw-cursor-pointer tw-items-center tw-gap-1">
				<input
					v-model="notify"
					type="checkbox"
					class="tw-h-5 tw-w-5 tw-shrink-0 tw-cursor-pointer tw-rounded tw-border tw-border-neutral-500 tw-bg-white tw-accent-main-500"
					@change="onNotifyChange"
				/>
				<span class="tw-text-base tw-font-medium">
					{{ translate('COM_EMUNDUS_POLL_CLOSE_NOTIFY') }}
				</span>
			</label>
		</div>

		<PollSlotContact
			v-if="contactOpen"
			ref="pollSlotContact"
			:display-header="false"
			:display-submit="false"
			:display-reply-to="true"
			:recipients="recipients"
			:default-subject="this.pollAddonConfiguration.configuration.close_email_subject"
			:default-body="this.pollAddonConfiguration.configuration.close_email_body"
			@back="closeContact"
			@send="handleSend"
		/>

		<div class="tw-flex tw-justify-center">
			<Button variant="primary" :disabled="isRunning" @click="closePoll" :loading="isRunning">
				{{ translate(isRunning ? 'COM_EMUNDUS_POLL_CLOSE_SENDING' : 'COM_EMUNDUS_POLL_CLOSE_BUTTON') }}
			</Button>
		</div>
	</div>
</template>

<style scoped></style>
