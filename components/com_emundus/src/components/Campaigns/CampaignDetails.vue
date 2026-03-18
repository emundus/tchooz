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
	name: 'CampaignDetails',
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
		aliasLink() {
			return this.item.alias ? window.location.origin + '/' + this.item.alias : null;
		},
		startDateFormatted() {
			if (!this.item.start_date) return null;
			const date = new Date(this.item.start_date.date);
			return date.toLocaleDateString(this.shortLang, {
				year: 'numeric',
				month: 'long',
				day: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
			});
		},
		endDateFormatted() {
			if (!this.item.end_date) return null;
			const date = new Date(this.item.end_date.date);
			return date.toLocaleDateString(this.shortLang, {
				year: 'numeric',
				month: 'long',
				day: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
			});
		},
		publishedText() {
			return this.item.published ? this.translate('PUBLISHED') : this.translate('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH');
		},
		bgPublishedClass() {
			return this.item.published ? 'em-bg-main-500' : 'tw-bg-neutral-300';
		},
		textPublishedClass() {
			return this.item.published ? 'tw-text-white' : 'tw-text-neutral-700';
		},
		morePropertiesFormatted() {
			const properties = [];
			if (this.item.moreProperties) {
				Object.values(this.item.moreProperties).forEach((property) => {
					if (property.hidden) return;
					properties.push({
						label: property.label || '',
						value: property.formatted_value || '-',
					});
				});
			}

			return properties;
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

		<div class="tw-flex tw-flex-col tw-gap-2">
			<h2 class="tw-text-center">{{ item.label[shortLang] }}</h2>
			<a v-if="aliasLink" :href="aliasLink" target="_blank" class="hover:tw-underline">
				{{ aliasLink }}
			</a>
			<div v-if="item.short_description" v-html="item.short_description" />
		</div>

		<!-- Grid Component -->
		<GridDetails>
			<template #label_1>
				{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_STARTDATE') }}
			</template>
			<template #value_1>
				<p class="tw-font-semibold">{{ startDateFormatted }}</p>
			</template>
			<template #label_2>
				{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_ENDDATE') }}
			</template>
			<template #value_2>
				<p class="tw-font-semibold">{{ endDateFormatted }}</p>
			</template>
			<template #label_3>
				{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_PICKYEAR') }}
			</template>
			<template #value_3>
				<p class="tw-font-semibold">{{ item.year }}</p>
			</template>
		</GridDetails>

		<div class="tw-mt-6 tw-flex tw-flex-col tw-gap-4">
			<div v-if="item.description">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION') }}</label>
				<div class="tw-rounded-coordinator tw-border tw-border-neutral-300 tw-p-2" v-html="item.description" />
			</div>
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM') }}</label>
				<p>{{ item.program.label }}</p>
			</div>
			<div v-for="(property, index) in morePropertiesFormatted" :key="index">
				<label class="tw-font-semibold">{{ property.label }}</label>
				<p>{{ property.value }}</p>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
