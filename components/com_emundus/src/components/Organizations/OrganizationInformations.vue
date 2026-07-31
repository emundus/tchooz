<script>
import Chip from '@/components/Atoms/Chip.vue';
import crcService from '@/services/crc.js';
import settingsService from '@/services/settings.js';
import Swal from 'sweetalert2';

export default {
	name: 'OrganizationInformations',
	components: { Chip },
	props: {
		item: { type: Object, required: true },
	},
	created() {
		if (Swal.isVisible()) {
			Swal.close();
		}
	},
	methods: {
		openApplicationFiles() {
			if (!this.$props.item) return;

			crcService.getOrganizationFiles(this.$props.item.id).then((response) => {
				if (response.status) {
					settingsService.saveFilterFiles(response.data).then((response) => {
						if (response.status) {
							const route = response.data;
							const baseUrl = window.location.origin;
							window.open(`${baseUrl}/${route}`, '_blank');
						}
					});
				}
			});
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
		fullAddress() {
			if (this.$props.item && this.$props.item.address) {
				const parts = [
					this.$props.item.address.street_address ?? '',
					this.$props.item.address.extended_address ?? '',
					this.$props.item.address.locality ?? '',
					this.$props.item.address.region ?? '',
					this.$props.item.address.postal_code ?? '',
					this.$props.item.address.country ? this.$props.item.address.country.label : '',
				];
				return parts.filter((part) => part).join(', ');
			}
			return '-';
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-4">
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
						:text="contact.fullname"
						:image="normalizedProfilePicture(contact.profile_picture)"
						:image-alt-text="contact.fullname"
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
						:text="contact.fullname"
						:image="normalizedProfilePicture(contact.profile_picture)"
						:image-alt-text="contact.fullname"
					/>
				</div>
			</div>
			<div v-else>
				<p>-</p>
			</div>
		</div>
		<div>
			<label class="tw-font-semibold">
				{{ translate('COM_EMUNDUS_ONBOARD_ADD_ORG_FILES') }}
			</label>
			<div v-if="item.application_files && item.application_files.length" class="em tw-mt-2 tw-cursor-pointer">
				<p class="items-center gap-1 hover:tw-underline" style="color: #6e7eff" @click="openApplicationFiles">
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_ORG_FILES_CONSULT') }}
					<span class="material-symbols-outlined text-sm align-middle" style="color: #6e7eff"> open_in_new </span>
				</p>
			</div>
			<p v-else>-</p>
		</div>
	</div>
</template>

<style scoped></style>
