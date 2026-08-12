<script>
import { Button, Icon, Chip, Avatar } from '@emundus/ui';
import TipTapEditor from 'tip-tap-editor';
import 'tip-tap-editor/tip-tap-editor.css';
import ModalHeader from '@/components/Utils/Modal/Header.vue';

export default {
	name: 'PollSlotContact',
	components: { ModalHeader, Avatar, Chip, Icon, Button, TipTapEditor },
	emits: ['back', 'send'],
	props: {
		recipients: {
			type: Array,
			default: () => [],
		},
		defaultSubject: {
			type: String,
			default: '',
		},
		defaultReplyTo: {
			type: String,
			default: '',
		},
		defaultBody: {
			type: String,
			default: '',
		},
		emailTemplates: {
			type: Array,
			default: () => [],
		},
		sender: {
			type: Object,
			default: () => ({}),
		},
		displayHeader: {
			type: Boolean,
			default: true,
		},
		displayReplyTo: {
			type: Boolean,
			default: false,
		},
		displaySubmit: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			selectedTemplateId: null,
			subject: this.defaultSubject,
			replyTo: this.defaultReplyTo,
			body: this.defaultBody,
			optionsOpen: false,
			attachmentType: null,
			editorPlugins: [
				'history',
				'link',
				'image',
				'bold',
				'italic',
				'underline',
				'left',
				'center',
				'right',
				'h1',
				'h2',
				'ul',
				'ol',
			],

			isRunning: false,
		};
	},
	computed: {
		recipientChips() {
			return (this.recipients || []).map((p) => ({
				id: p.id,
				label: this.fullName(p),
				initials: this.initials(p),
			}));
		},
		senderLabel() {
			return this.sender?.label || this.sender?.fullname || this.fullName(this.sender);
		},
		senderInitials() {
			return this.initials(this.sender);
		},
	},
	methods: {
		fullName(person) {
			if (!person) return '';
			if (person.fullname) return person.fullname;
			const parts = [person.firstname, person.lastname].filter(Boolean);
			return parts.join(' ') || person.email || '';
		},
		initials(person) {
			const name = this.fullName(person);
			if (!name) return '';
			return name
				.split(' ')
				.filter(Boolean)
				.map((w) => w.charAt(0).toUpperCase())
				.slice(0, 2)
				.join('');
		},
		toggleOptions() {
			this.optionsOpen = !this.optionsOpen;
		},
		send() {
			this.isRunning = true;

			this.$emit('send', {
				template_id: this.selectedTemplateId,
				sender: this.sender,
				recipients: this.recipientChips.map((c) => c.id),
				subject: this.subject,
				reply_to: this.replyTo,
				body: this.body,
				attachment_type: this.attachmentType,
			});
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-6 tw-p-2">
		<ModalHeader
			v-if="displayHeader"
			:title="translate('COM_EMUNDUS_POLL_CONTACT_PARTICIPANTS_TITLE')"
			:show-back="true"
			:show-close="true"
			@back="$emit('back')"
			@close="$emit('close')"
		/>

		<div class="tw-flex tw-gap-6">
			<div class="tw-flex tw-flex-1 tw-flex-col tw-gap-2">
				<label class="tw-text-base tw-font-medium">
					{{ translate('COM_EMUNDUS_POLL_CONTACT_TO') }}
				</label>
				<div
					class="tw-flex tw-min-h-[40px] tw-flex-wrap tw-items-center tw-gap-2 tw-rounded-lg tw-border tw-border-neutral-300 tw-bg-white tw-px-3 tw-py-1"
				>
					<Chip v-for="chip in recipientChips" :label="chip.label" variant="neutral">
						<template #before>
							<Avatar :initials="chip.initials" size="sm" />
						</template>
					</Chip>
				</div>
			</div>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-3">
			<div class="tw-flex tw-flex-col tw-gap-2">
				<label class="tw-text-base tw-font-medium">
					{{ translate('COM_EMUNDUS_POLL_CONTACT_SUBJECT') }}
				</label>
				<input
					v-model="subject"
					type="text"
					class="tw-h-10 tw-w-full tw-rounded-lg tw-border tw-border-neutral-300 tw-bg-white tw-px-3 tw-py-2 tw-text-base tw-font-medium focus:tw-border-profile-full focus:tw-outline-none"
				/>
			</div>

			<div class="tw-flex tw-flex-col tw-gap-2" v-if="displayReplyTo">
				<label class="tw-text-base tw-font-medium">
					{{ translate('COM_EMUNDUS_POLL_CONTACT_REPLY_TO') }}
				</label>
				<input
					v-model="replyTo"
					type="email"
					:placeholder="translate('COM_EMUNDUS_POLL_CONTACT_REPLY_TO_PLACEHOLDER')"
					class="tw-h-10 tw-w-full tw-rounded-lg tw-border tw-border-neutral-300 tw-bg-white tw-px-3 tw-py-2 tw-text-base tw-font-medium placeholder:tw-text-neutral-500 focus:tw-border-profile-full focus:tw-outline-none"
				/>
			</div>

			<div class="tw-flex tw-flex-col tw-gap-2">
				<tip-tap-editor
					v-model="body"
					:upload-url="'/index.php?option=com_emundus&controller=settings&task=uploadmedia'"
					:editor-content-height="'15em'"
					:class="'tw-mt-1'"
					:locale="'fr'"
					:preset="'custom'"
					:plugins="editorPlugins"
					:toolbar-classes="['tw-bg-white']"
					:editor-content-classes="['tw-bg-white']"
				/>
			</div>
		</div>

		<div class="tw-flex tw-justify-center" v-if="displaySubmit">
			<Button variant="primary" @click="send" :disabled="isRunning" :loading="isRunning">
				<template #leading>
					<Icon name="send" />
				</template>
				{{ translate(isRunning ? 'COM_EMUNDUS_POLL_CONTACT_PARTICIPANTS_SENDING' : 'COM_EMUNDUS_POLL_CONTACT_SEND') }}
			</Button>
		</div>
	</div>
</template>
