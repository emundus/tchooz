<script>
import PollSlotContact from '@/components/Polls/Popup/PollSlotContact.vue';
import { useGlobalStore } from '@/stores/global.js';
import alerts from '@/mixins/alerts.js';
import pollService from '@/services/poll.js';
import settingsService from '@/services/settings.js';
import Loader from '@/components/Atoms/Loader.vue';
import ModalHeader from '@/components/Utils/Modal/Header.vue';
import { Chip, Button, Icon } from '@emundus/ui';

export default {
	name: 'PollContact',
	components: { Icon, Chip, ModalHeader, Loader, Button, PollSlotContact },
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
			selected: [],
			shortLang: 'fr',
			isRunning: false,
			loading: true,

			pollAddonConfiguration: {
				configuration: {
					run_email_subject: '',
					run_email_body: '',
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

				this.loading = false;
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
		async contactParticipants() {
			if (this.isRunning) return;

			const ids = this.selected.map((p) => p.id);
			if (ids.length === 0) return;

			const payload = {
				ids: ids.join(','),
				recipients: this.recipients.map((r) => r.id).join(','),
			};
			if (this.$refs.pollSlotContact) {
				payload.subject = this.$refs.pollSlotContact.subject;
				payload.reply_to = this.$refs.pollSlotContact.replyTo;
				payload.body = this.$refs.pollSlotContact.body;
			}

			this.isRunning = true;
			try {
				const response = await pollService.contactParticipants(payload);
				if (response && response.status === true) {
					this.alertSuccess('COM_EMUNDUS_POLLS_RUN');
					this.$emit('update-items');
					this.$emit('close');
				} else {
					this.alertError(response?.message || response?.msg || 'COM_EMUNDUS_POLL_RUN_NO_IDS');
				}
			} finally {
				this.isRunning = false;
			}
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-6" v-if="!loading">
		<div class="tw-flex tw-flex-col tw-gap-6 tw-p-2">
			<ModalHeader :title="translate('COM_EMUNDUS_POLL_CONTACT_PARTICIPANTS_TITLE')" @close="$emit('close')" />

			<div class="tw-flex tw-flex-col tw-gap-2">
				<label class="tw-text-base tw-font-medium">
					{{ translate('COM_EMUNDUS_POLL_CONTACT_PARTICIPANTS_LABEL') }}
				</label>
				<div
					class="tw-flex tw-min-h-[40px] tw-flex-wrap tw-items-center tw-gap-2 tw-rounded-lg tw-border tw-border-neutral-300 tw-bg-white tw-px-3 tw-py-1"
				>
					<Chip v-for="chip in pollChips" :label="chip.label" closable variant="neutral" @close="removePoll(chip.id)" />
				</div>
			</div>
		</div>

		<PollSlotContact
			ref="pollSlotContact"
			:display-header="false"
			:display-submit="false"
			:display-reply-to="true"
			:recipients="recipients"
			:default-subject="''"
			:default-body="''"
		/>

		<div class="tw-flex tw-justify-center">
			<Button variant="primary" :disabled="isRunning" @click="contactParticipants" :loading="isRunning">
				<template #leading>
					<Icon v-if="!isRunning" name="send" />
				</template>
				{{
					translate(
						isRunning ? 'COM_EMUNDUS_POLL_CONTACT_PARTICIPANTS_SENDING' : 'COM_EMUNDUS_POLL_CONTACT_PARTICIPANTS_SEND',
					)
				}}
			</Button>
		</div>
	</div>
	<Loader v-else />
</template>

<style scoped></style>
