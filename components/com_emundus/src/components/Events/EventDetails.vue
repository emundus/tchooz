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
	name: 'EventDetails',
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

		console.log(this.$props.item);
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
		slotDuration() {
			if (!this.item.slot_duration) return null;
			if (this.item.slot_duration_type === 'minutes') {
				return `${this.item.slot_duration} ${this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES')}`;
			}
			if (this.item.slot_duration_type === 'hours') {
				return `${this.item.slot_duration} ${this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS')}`;
			}
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-mb-4 tw-flex tw-items-center tw-justify-end">
			<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="closeModal">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-2">
			<h2 class="tw-text-center">{{ item.label[shortLang] }}</h2>
		</div>

		<!-- Grid Component -->
		<GridDetails>
			<template #label_1>
				{{ translate('COM_EMUNDUS_ONBOARD_REGISTRANTS') }}
			</template>
			<template #value_1>
				<p class="tw-font-semibold">{{ item.booked_count }}</p>
			</template>
			<template #label_2>
				{{ translate('COM_EMUNDUS_ONBOARD_CAPACITY') }}
			</template>
			<template #value_2>
				<p class="tw-font-semibold">{{ item.availabilities_count }}</p>
			</template>
			<template #label_3>
				{{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION') }}
			</template>
			<template #value_3>
				<p class="tw-font-semibold">{{ slotDuration }}</p>
			</template>
		</GridDetails>

		<div class="tw-mt-6 tw-flex tw-flex-col tw-gap-4">
			<div v-if="item.location">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_LOCATION') }}</label>
				<p>{{ item.location }}</p>
			</div>
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_MANAGER') }}</label>
				<p>{{ item.manager_name }} ({{ item.manager_email }})</p>
			</div>
			<div v-if="item.campaigns && item.campaigns.length > 0">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_CAMPAIGN_ASSOCIATED_DETAILS') }}</label>
				<div class="tw-mt-2">
					<Chip v-for="campaign in item.campaigns" :key="campaign.id" :text="campaign.label" />
				</div>
			</div>
			<div v-if="item.programs && item.programs.length > 0">
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_WORKFLOW_ASSOCIATED_PROGRAMS_DETAILS') }}</label>
				<div class="tw-mt-2">
					<Chip v-for="program in item.programs" :key="program.id" :text="program.label" />
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
