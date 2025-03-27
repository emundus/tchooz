<template>
	<div class="tw-w-full tw-p-6">
		<div v-if="loading" class="em-page-loader"></div>
		<div v-else>
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
				<h2 class="tw-mb-6 tw-text-center">
					{{ translate('COM_EMUNDUS_EVENTS_EMARGEMENT') }}
				</h2>

				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="onClosePopup">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>

			<span class="tw-mb-6 tw-block">
				{{ translate('COM_EMUNDUS_EXPORTS_SELECT_INFORMATIONS') }}
			</span>

			<div class="tw-flex tw-gap-6">
				<div class="tw-flex tw-flex-1 tw-items-center tw-gap-2">
					<input
						type="checkbox"
						v-model="selectAll"
						:id="'all'"
						@change="toggleAll"
						class="tw-h-4 tw-w-4 tw-cursor-pointer"
					/>
					<label :for="'all'" class="checkbox-label tw-mt-1.5 tw-cursor-pointer tw-align-middle">{{
						translate('ALL_FEMININE')
					}}</label>
				</div>
			</div>

			<hr class="tw-my-2 tw-w-full tw-border-t tw-border-gray-300" />

			<div class="tw-flex tw-gap-6">
				<div class="tw-flex-1">
					<div
						v-for="(item, index) in checkboxItemsFromBookingsView"
						:key="'booking-' + index"
						class="tw-mb-2 tw-flex tw-items-center tw-gap-2"
					>
						<input
							type="checkbox"
							v-model="selectedItemsFromView"
							:value="item.label"
							:id="'checkbox-booking-' + index + this.checkboxItemsFromProfile.length"
							class="tw-h-4 tw-w-4 tw-cursor-pointer"
						/>
						<label
							:for="'checkbox-booking-' + index + this.checkboxItemsFromProfile.length"
							class="checkbox-label tw-mt-1.5 tw-cursor-pointer tw-align-middle"
							>{{ translate(item.label) }}</label
						>
					</div>
				</div>

				<div class="tw-flex-1">
					<div
						v-for="(item, index) in checkboxItemsFromProfile"
						:key="'profile-' + index"
						class="tw-mb-2 tw-flex tw-items-center tw-gap-2"
					>
						<input
							type="checkbox"
							v-model="selectedItemsFromProfile"
							:value="item.id"
							:id="'checkbox-booking-' + index"
							class="tw-h-4 tw-w-4 tw-cursor-pointer"
						/>
						<label
							:for="'checkbox-booking-' + index"
							class="checkbox-label tw-mt-1.5 tw-cursor-pointer tw-align-middle"
							>{{ translate(item.label) }}</label
						>
					</div>
				</div>
			</div>

			<div class="tw-mt-6 tw-flex tw-justify-between">
				<button @click="onClosePopup" class="tw-btn-secondary">
					{{ translate('COM_EMUNDUS_ONBOARD_REGISTRANT_CANCEL_EXPORT') }}
				</button>
				<button
					@click="onConfirmSelection"
					:disabled="selectedItemsFromView.length === 0 && selectedItemsFromProfile.length === 0"
					class="tw-btn-primary"
				>
					{{ translate('COM_EMUNDUS_ONBOARD_REGISTRANT_CONFIRM_EXPORT') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>

<script>
import userService from '@/services/user.js';

export default {
	name: 'ExportsSlotsModal',
	emits: ['close', 'selectionConfirm'],
	data() {
		return {
			selectedItemsFromView: [],
			selectedItemsFromProfile: [],
			checkboxItemsFromProfile: [],
			checkboxItemsFromBookingsView: [
				{ label: 'COM_EMUNDUS_ONBOARD_LABEL_REGISTRANTS' },
				{ label: 'COM_EMUNDUS_REGISTRANTS_USER' },
				{ label: 'COM_EMUNDUS_REGISTRANTS_DAY' },
				{ label: 'COM_EMUNDUS_REGISTRANTS_HOUR' },
				{ label: 'COM_EMUNDUS_REGISTRANTS_LOCATION' },
				{ label: 'COM_EMUNDUS_REGISTRANTS_ROOM' },
			],
			selectAll: false,
			loading: false,
		};
	},
	created() {
		this.loading = true;
		this.getColumnsFromProfileForm().then((checkboxItems) => {
			this.checkboxItemsFromProfile = checkboxItems;
			this.loading = false;
			this.selectedItemsFromView = this.checkboxItemsFromBookingsView.map((item) => item.label);
		});
	},
	methods: {
		async getColumnsFromProfileForm() {
			return new Promise((resolve, reject) => {
				userService.getColumnsFromProfileForm().then((response) => {
					if (response.status) {
						resolve(response.data);
					} else {
						console.error('Error when trying to retrieve columns from profile form', response.error);
						reject([]);
					}
				});
			});
		},
		toggleAll() {
			if (this.selectAll) {
				this.selectedItemsFromView = this.checkboxItemsFromBookingsView.map((item) => item.label);
				this.selectedItemsFromProfile = this.checkboxItemsFromProfile.map((item) => item.id);
			} else {
				this.selectedItemsFromView = [];
				this.selectedItemsFromProfile = [];
			}
		},
		onClosePopup() {
			this.$emit('close');
		},
		onConfirmSelection() {
			const sortedViewSelection = this.checkboxItemsFromBookingsView
				.filter((item) => this.selectedItemsFromView.includes(item.label))
				.map((item) => item.label);

			const sortedProfileSelection = this.checkboxItemsFromProfile
				.filter((item) => this.selectedItemsFromProfile.includes(item.id))
				.map((item) => item.id);

			this.$emit('selectionConfirm', {
				viewSelection: sortedViewSelection,
				profileSelection: sortedProfileSelection,
			});

			this.onClosePopup();
		},
	},
	watch: {
		selectedItemsFromView() {
			this.selectAll =
				this.selectedItemsFromView.length === this.checkboxItemsFromBookingsView.length &&
				this.selectedItemsFromProfile.length === this.checkboxItemsFromProfile.length;
		},
		selectedItemsFromProfile() {
			this.selectAll =
				this.selectedItemsFromView.length === this.checkboxItemsFromBookingsView.length &&
				this.selectedItemsFromProfile.length === this.checkboxItemsFromProfile.length;
		},
	},
};
</script>
