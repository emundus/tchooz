<script>
import Modal from '@/components/Modal.vue';
import Chip from '@/components/Atoms/Chip.vue';
import GridDetails from '@/components/Molecules/GridDetails.vue';
import CountryFlag from '@/components/Atoms/CountryFlag.vue';
import Avatar from '@/components/Atoms/Avatar.vue';
import contactsService from '@/services/contacts.js';
import userService from '@/services/user.js';

export default {
	name: 'ContactDetails',
	components: { Avatar, CountryFlag, GridDetails, Chip, Modal },
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
		openApplicationFiles() {
			let user_email = null;

			userService.getUserById(this.$props.item.user_id).then((response) => {
				if (response.status) {
					user_email = response.user[0].email;
					contactsService.saveFilterEmail(user_email).then((response) => {
						if (response.status) {
							const route = response.data;
							const baseUrl = window.location.origin;
							window.open(`${baseUrl}/${route}`, '_blank');
						}
					});
				}
			});
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

		fullAddress() {
			if (this.$props.item && this.$props.item.addresses.length > 0) {
				const parts = [
					this.$props.item.addresses[0].street_address ? this.$props.item.addresses[0].street_address : '',
					this.$props.item.addresses[0].extended_address ? this.$props.item.addresses[0].extended_address : '',
					this.$props.item.addresses[0].locality ? this.$props.item.addresses[0].locality : '',
					this.$props.item.addresses[0].region ? this.$props.item.addresses[0].region : '',
					this.$props.item.addresses[0].postal_code ? this.$props.item.addresses[0].postal_code : '',
					this.$props.item.addresses[0].country ? this.$props.item.addresses[0].country.label : '',
				];
				return parts.filter((part) => part).join(', ');
			}
			return '-';
		},

		birthdateFormatted() {
			if (this.$props.item.birthdate) {
				// Format date as D M Y
				const date = new Date(this.$props.item.birthdate);
				return date.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
			} else {
				return '-';
			}
		},

		age() {
			if (this.$props.item.birthdate) {
				const birthDate = new Date(this.$props.item.birthdate);
				const ageDifMs = Date.now() - birthDate.getTime();
				const ageDate = new Date(ageDifMs); // miliseconds from epoch
				return Math.abs(ageDate.getUTCFullYear() - 1970) + ' ' + this.translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_AGE');
			} else {
				return '';
			}
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

		<!-- Grid Component -->
		<GridDetails>
			<template #label_1>
				{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER') }}
			</template>
			<template #value_1>
				<div v-if="item.gender_icon">
					<span class="material-symbols-outlined tw-text-neutral-900" style="font-size: 3rem">{{
						item.gender_icon
					}}</span>
				</div>
				<p v-else>-</p>
			</template>
			<template #label_2>
				{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_BIRTH') }}
			</template>
			<template #value_2>
				<p class="tw-font-semibold">{{ birthdateFormatted }}</p>
				<p v-if="item.birthdate">( {{ age }} )</p>
			</template>
			<template #label_3>
				{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_COUNTRIES') }}
			</template>
			<template #value_3>
				<div class="tw-flex tw-items-center tw-justify-center tw-gap-2">
					<div
						v-for="(country, index) of item.countries"
						:key="country.id"
						:class="index > 0 ? 'tw-absolute tw-translate-x-3/4' : 'tw-z-10'"
					>
						<CountryFlag :flagImage="country.flag_img" :flagAltText="country.flag" />
					</div>
				</div>
			</template>
		</GridDetails>

		<!-- More informations -->
		<div class="tw-mt-6 tw-flex tw-flex-col tw-gap-4">
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADRESSE') }}</label>
				<p>{{ fullAddress }}</p>
			</div>
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ROLE') }}</label>
				<p>{{ item.fonction ?? '-' }}</p>
			</div>
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_DEPARTMENT') }}</label>
				<p>{{ item.service ?? '-' }}</p>
			</div>
			<div>
				<label class="tw-font-semibold">{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ORGANIZATIONS') }}</label>
				<div v-if="item.organizations && item.organizations.some((o) => o.published)" class="tw-mt-2">
					<Chip
						v-for="org in item.organizations.filter((o) => o.published)"
						:key="org.id"
						:text="org.name"
						:image="normalizedLogo(org.logo)"
						:image-alt-text="org.name"
					/>
				</div>
				<p v-else>-</p>
			</div>
			<div>
				<label class="tw-font-semibold">
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_FILES') }}
				</label>

				<div v-if="item.application_files.length > 0" class="em tw-mt-2 tw-cursor-pointer">
					<p class="items-center gap-1 hover:tw-underline" style="color: #6e7eff" @click="openApplicationFiles">
						{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTACT_FILES_CONSULT') }}
						<span class="material-symbols-outlined text-sm align-middle" style="color: #6e7eff"> open_in_new </span>
					</p>
				</div>

				<p v-else>-</p>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
