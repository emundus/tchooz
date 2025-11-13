<script>
import Modal from '@/components/Modal.vue';
import Chip from '@/components/Atoms/Chip.vue';
import GridDetails from '@/components/Molecules/GridDetails.vue';
import CountryFlag from '@/components/Atoms/CountryFlag.vue';
import Avatar from '@/components/Atoms/Avatar.vue';

export default {
	name: 'OrganizationDetails',
	components: { Avatar, Chip, Modal },
	props: {
		item: Object,
		slot: Object,
	},
	emits: ['close', 'open'],
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
		normalizedProfilePicture(profilePicture) {
			if (!profilePicture) return null;

			if (profilePicture.startsWith('https')) {
				return profilePicture;
			}
			const base = window.location.origin + '/';
			return base + profilePicture.replace(/^\//, '');
		},
	},
	computed: {
		websiteLink() {
			if (this.$props.item && this.$props.item.url_website) {
				return `mailto:${this.$props.item.url_website}`;
			}
			return '';
		},

		fullAddress() {
			if (this.$props.item && this.$props.item.address) {
				const parts = [
					this.$props.item.address.street_address ? this.$props.item.address.street_address : '',
					this.$props.item.address.extended_address ? this.$props.item.address.extended_address : '',
					this.$props.item.address.locality ? this.$props.item.address.locality : '',
					this.$props.item.address.region ? this.$props.item.address.region : '',
					this.$props.item.address.postal_code ? this.$props.item.address.postal_code : '',
					this.$props.item.address.country ? this.$props.item.address.country.label : '',
				];
				return parts.filter((part) => part).join(', ');
			}
			return '-';
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
			<a v-if="websiteLink" :href="websiteLink" class="hover:tw-underline">
				{{ item.url_website }}
			</a>
		</div>

		<!-- More informations -->
		<div class="tw-mt-6 tw-flex tw-flex-col tw-gap-4">
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADD_ORG_DESCRIPTION') }}</label>
				<p>{{ item.description || '-' }}</p>
			</div>
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADRESSE') }}</label>
				<p>{{ fullAddress }}</p>
			</div>
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADD_ORG_CONTACT') }}</label>
				<div v-if="item.referent_contacts && item.referent_contacts.some((c) => c.published)">
					<div class="tw-mt-1 tw-flex tw-flex-wrap tw-gap-2">
						<Chip
							v-for="contact in item.referent_contacts.filter((c) => c.published)"
							:key="contact.id"
							:text="contact.name"
							:image="normalizedProfilePicture(contact.profile_picture)"
							:image-alt-text="contact.name"
						/>
					</div>
				</div>
				<div v-else>
					<p>-</p>
				</div>
			</div>
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADD_ORG_OTHER_CONTACT') }}</label>
				<div v-if="item.other_contacts && item.other_contacts.some((c) => c.published)">
					<div class="tw-mt-1 tw-flex tw-flex-wrap tw-gap-2">
						<Chip
							v-for="contact in item.other_contacts.filter((c) => c.published)"
							:key="contact.id"
							:text="contact.name"
							:image="normalizedProfilePicture(contact.profile_picture)"
							:image-alt-text="contact.name"
						/>
					</div>
				</div>
				<div v-else>
					<p>-</p>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
