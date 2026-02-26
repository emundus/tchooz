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
	name: 'ProgramDetails',
	components: { Tag, GridDetails, Chip, Modal },
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
			return this.item.published ? this.translate('PUBLISHED') : this.translate('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH');
		},
		bgPublishedClass() {
			return this.item.published ? 'em-bg-main-500' : 'tw-bg-neutral-300';
		},
		textPublishedClass() {
			return this.item.published ? 'tw-text-white' : 'tw-text-neutral-700';
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
			<h2>{{ item.label[shortLang] }}</h2>
			<span class="tw-text-neutral-700">{{ item.code }}</span>
		</div>

		<div class="tw-mt-6 tw-flex tw-flex-col tw-gap-4">
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_CAMPAIGN_ASSOCIATED_DETAILS') }}</label>
				<div v-if="item.campaigns" class="tw-mt-2">
					<Chip v-for="campaign in item.campaigns" :key="campaign.id" :text="campaign.label" />
				</div>
				<p v-else>-</p>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
