<script>
import Avatar from '@/components/Atoms/Avatar.vue';
import OrganizationInformations from '@/components/Organizations/OrganizationInformations.vue';
import CrcComments from '@/components/Comments/CrcComments.vue';
import Tabs from '@/components/Utils/Tabs.vue';

export default {
	name: 'OrganizationDetails',
	components: { Tabs, CrcComments, Avatar, OrganizationInformations },
	props: {
		item: Object,
		slot: Object,
	},
	emits: ['close', 'open', 'update-items'],
	data() {
		return {
			activeTab: 'informations',
			tabs: [
				{
					id: 'informations',
					name: this.translate('COM_EMUNDUS_ONBOARD_CRC_TAB_INFORMATIONS'),
					icon: 'info',
					active: true,
					displayed: true,
				},
				{
					id: 'comments',
					name: this.translate('COM_EMUNDUS_ONBOARD_CRC_TAB_COMMENTS'),
					icon: 'chat',
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
		onChangeTabActive(tabId) {
			this.activeTab = tabId;
		},
	},
	computed: {
		websiteLink() {
			if (this.$props.item && this.$props.item.url_website) {
				return this.$props.item.url_website;
			}
			return '';
		},
		normalizedImage() {
			if (!this.item || !this.item.image) return null;
			if (this.item.image.startsWith('https')) {
				return this.item.image;
			}
			const base = window.location.origin + '/';
			return base + this.item.image.replace(/^\//, '');
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-pt-4">
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
				<div></div>
				<Teleport defer to=".modal___wrapper">
					<Avatar :fullname="item.name" :image="normalizedImage" :published-tag="item.published" />
				</Teleport>
				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="closeModal">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
		</div>

		<div class="tw-flex tw-flex-col tw-items-center tw-justify-center">
			<h2>{{ item.name }}</h2>
			<p v-if="item.identifier_code" class="tw-text-neutral-600">
				{{ item.identifier_code }}
			</p>
			<a v-if="websiteLink" :href="websiteLink" target="_blank" class="hover:tw-underline">
				{{ item.url_website }}
			</a>
		</div>

		<Tabs :tabs="tabs" :classes="''" @changeTabActive="onChangeTabActive" :template="'toggle'"></Tabs>

		<div class="tw-mt-6">
			<OrganizationInformations v-if="activeTab === 'informations'" :item="item" />
			<CrcComments
				v-else-if="activeTab === 'comments'"
				:item="item"
				target-type="organization"
				@update-items="$emit('update-items')"
			/>
		</div>
	</div>
</template>

<style scoped></style>
