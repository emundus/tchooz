<script>
import Avatar from '@/components/Atoms/Avatar.vue';
import crcService from '@/services/crc.js';
import settingsService from '@/services/settings.js';
import ContactInformations from '@/components/Contacts/ContactInformations.vue';
import CrcComments from '@/components/Comments/CrcComments.vue';
import Tabs from '@/components/Utils/Tabs.vue';

export default {
	name: 'ContactDetails',
	components: { Tabs, CrcComments, Avatar, ContactInformations },
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
		normalizedLogo(logo) {
			if (!logo) return null;

			if (logo.startsWith('https')) {
				return logo;
			}
			const base = window.location.origin + '/';
			return base + logo.replace(/^\//, '');
		},
	},
	computed: {
		fullName() {
			if (this.$props.item) {
				return `${this.$props.item.firstname} ${this.$props.item.lastname}`;
			}
			return '';
		},
		emailLink() {
			if (this.$props.item && this.$props.item.email) {
				return `mailto:${this.$props.item.email}`;
			}
			return '';
		},
		normalizedProfilePicture() {
			if (!this.item || !this.item.profile_picture) return null;
			if (this.item.profile_picture.startsWith('https')) {
				return this.item.profile_picture;
			}
			const base = window.location.origin + '/';
			return base + this.item.profile_picture.replace(/^\//, '');
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
					<Avatar :fullname="fullName" :image="normalizedProfilePicture" :published-tag="item.published" />
				</Teleport>
				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="closeModal">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
		</div>

		<div class="tw-flex tw-flex-col tw-items-center tw-justify-center">
			<h2>{{ fullName }}</h2>
			<a v-if="emailLink" :href="emailLink" class="hover:tw-underline">
				{{ item.email }}
			</a>
			<p v-if="item.phone_1" class="tw-text-neutral-600">
				{{ item.phone_1 }}
			</p>
		</div>
		<Tabs :tabs="tabs" :classes="''" @changeTabActive="onChangeTabActive" :template="'toggle'"> </Tabs>

		<div class="tw-mt-6">
			<ContactInformations v-if="activeTab === 'informations'" :item="item" />
			<CrcComments
				v-else-if="activeTab === 'comments'"
				:item="item"
				@update-items="$emit('update-items')"
				target-type="contact"
			/>
		</div>
	</div>
</template>

<style scoped></style>
