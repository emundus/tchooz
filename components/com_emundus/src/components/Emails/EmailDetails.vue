<script>
import Modal from '@/components/Modal.vue';
import Chip from '@/components/Atoms/Chip.vue';
import GridDetails from '@/components/Molecules/GridDetails.vue';
import CountryFlag from '@/components/Atoms/CountryFlag.vue';
import Avatar from '@/components/Atoms/Avatar.vue';
import contactsService from '@/services/contacts.js';
import userService from '@/services/user.js';
import { useGlobalStore } from '@/stores/global.js';
import Tag from '@/components/Atoms/Tag.vue';

export default {
	name: 'EmailDetails',
	components: { Tag, Avatar, CountryFlag, GridDetails, Chip, Modal },
	props: {
		item: Object,
	},
	emits: ['close', 'open'],
	data: function () {
		return {
			shortLang: 'fr',
		};
	},
	created() {
		if (!this.$props.item) {
			this.closeModal();
		}

		this.shortLang = useGlobalStore().getShortLang;
	},
	methods: {
		beforeClose() {
			this.$emit('close');
		},
		beforeOpen() {
			this.$emit('open');
		},
		closeModal() {
			this.$emit('close');
		},
	},
	computed: {
		publishedText() {
			return this.item.published == 1
				? this.translate('PUBLISHED')
				: this.translate('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH');
		},
		bgPublishedClass() {
			return this.item.published == 1 ? 'em-bg-main-500' : 'tw-bg-neutral-300';
		},
		textPublishedClass() {
			return this.item.published == 1 ? 'tw-text-white' : 'tw-text-neutral-700';
		},
		emailReceivers() {
			if (!this.item.receivers) return null;
			return this.item.receivers.split(',');
		},
		letterAttachments() {
			if (!this.item.letter_attachments) return null;
			return this.item.letter_attachments.split(',');
		},
		emailTags() {
			if (!this.item.tags) return null;
			return this.item.tags.split(',');
		},
		candidateAttachments() {
			if (!this.item.candidate_attachments) return null;
			return this.item.candidate_attachments.split(',');
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
			<Tag :text="publishedText" :bg-color-class="bgPublishedClass" :text-color-class="textPublishedClass" />
			<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="closeModal">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>

		<div class="tw-flex tw-flex-col tw-items-center tw-gap-2">
			<h2>{{ item.subject }}</h2>
			<p v-if="item.category" class="tw-text-neutral-600">{{ item.category }}</p>
		</div>

		<div class="tw-mt-6 tw-flex tw-flex-col tw-gap-4">
			<div v-if="item.message">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_BODY') }}</label>
				<div class="tw-rounded-coordinator tw-border tw-border-neutral-300 tw-p-2" v-html="item.message" />
			</div>
			<div v-if="item.name">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_RECEIVER') }}</label>
				<p>{{ item.name }}</p>
			</div>
			<div v-if="item.emailfrom">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESS') }}</label>
				<p>{{ item.emailfrom }}</p>
			</div>
			<div v-if="emailReceivers && emailReceivers.length > 0">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_RECEIVER_CC_TAGS') }}</label>
				<div class="tw-mt-2">
					<Chip v-for="(receiver, index) in emailReceivers" :key="index" :text="receiver" />
				</div>
			</div>
			<div v-if="letterAttachments && letterAttachments.length > 0">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT') }}</label>
				<div class="tw-mt-2">
					<Chip v-for="(letter, index) in letterAttachments" :key="index" :text="letter" />
				</div>
			</div>
			<div v-if="emailTags && emailTags.length > 0">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_EMAIL_TAGS') }}</label>
				<div class="tw-mt-2">
					<Chip v-for="(tag, index) in emailTags" :key="index" :text="tag" />
				</div>
			</div>
			<div v-if="candidateAttachments && candidateAttachments.length > 0">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_CANDIDAT_ATTACHMENTS') }}</label>
				<div class="tw-mt-2">
					<Chip v-for="(attachment, index) in candidateAttachments" :key="index" :text="attachment" />
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
