<script>
import Chip from '@/components/Atoms/Chip.vue';
import { useGlobalStore } from '@/stores/global.js';
import Tag from '@/components/Atoms/Tag.vue';
import Tabs from '@/components/Utils/Tabs.vue';
import AccessRightsTable from '@/components/Groups/AccessRightsTable.vue';
import UsersList from '@/components/Groups/UsersList.vue';

export default {
	name: 'GroupDetails',
	components: { UsersList, AccessRightsTable, Tabs, Tag, Chip },
	props: {
		item: Object,
	},
	emits: ['close', 'open'],
	data: function () {
		return {
			shortLang: 'fr',

			tabs: [
				{
					id: 1,
					name: 'COM_EMUNDUS_GROUPS_ADD_GROUP_PROGRAMMES',
					description: '',
					icon: 'event_list',
					active: true,
					displayed: true,
				},
				{
					id: 2,
					name: 'COM_EMUNDUS_GROUPS_ADD_GROUP_RIGHTS',
					description: '',
					icon: 'shield_toggle',
					active: false,
					displayed: true,
				},
				{
					id: 3,
					name: 'COM_EMUNDUS_GROUPS_USERS_ASSOCIATE',
					description: '',
					icon: 'patient_list',
					active: false,
					displayed: true,
				},
			],
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
	<div class="tw-relative">
		<div class="tw-sticky tw-top-0 tw-z-20 tw-flex tw-items-center tw-justify-between tw-bg-white tw-py-8">
			<Tag :text="publishedText" :bg-color-class="bgPublishedClass" :text-color-class="textPublishedClass" />
			<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="closeModal">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-2">
			<h2 class="tw-text-center">{{ item.label[shortLang] }}</h2>
			<div v-if="item.description" v-html="item.description" />
		</div>

		<Tabs
			ref="tabsComponent"
			:classes="'tw-flex tw-items-center tw-gap-2 tw-mt-4 tw-border-b tw-border-neutral-300'"
			:tabs="tabs"
			:context="item.id ? 'group_details' + item.id : ''"
		/>

		<div v-if="tabs[0].active" class="tw-mt-6 tw-flex tw-flex-col tw-gap-4">
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_GROUPS_PROGRAMS') }}</label>
				<div v-if="item.programs" class="tw-mt-2 tw-grid tw-grid-cols-3 tw-gap-2">
					<Chip v-for="program in item.programs" :key="program.id" :text="program.label" />
				</div>
				<p v-else>-</p>
			</div>
		</div>

		<template v-if="tabs[1].active">
			<AccessRightsTable :group="this.item" />
		</template>

		<template v-if="tabs[2].active">
			<UsersList :group="this.item" />
		</template>
	</div>
</template>

<style scoped></style>
