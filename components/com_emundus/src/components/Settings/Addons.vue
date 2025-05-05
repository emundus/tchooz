<script>
/* Services */
import settingsService from '@/services/settings';
import Messenger from '@/components/Settings/Addons/Messenger.vue';
import SMSAddon from '@/components/Settings/Addons/SMSAddon.vue';
import RankingTool from '@/components/Settings/Addons/RankingTool.vue';

/* Components */

export default {
	name: 'Addons',
	components: { Messenger, SMSAddon, RankingTool },
	data() {
		return {
			loading: true,

			addons: [],
			currentAddon: null,
		};
	},
	created() {
		this.getAddons();
	},
	methods: {
		getAddons() {
			settingsService.getAddons().then((response) => {
				this.addons = response.data;

				this.loading = false;
			});
		},
		toggleEnabled(addon, event) {
			let value = event.target.checked ? 1 : 0;
			settingsService.toggleAddonEnabled(addon.type, value);
		},
	},
};
</script>

<template>
	<div>
		<div class="em-grid-3-2-1">
			<div
				v-if="!currentAddon"
				v-for="addon in addons"
				class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
			>
				<div class="tw-flex tw-items-center tw-justify-between">
					<span class="material-symbols-outlined" style="font-size: 32px">{{ addon.icon }}</span>
					<div class="tw-flex tw-items-center">
						<div class="em-toggle">
							<input
								type="checkbox"
								true-value="1"
								false-value="0"
								class="em-toggle-check"
								:id="addon.type + '_enabled_input'"
								v-model="addon.enabled"
								@click="toggleEnabled(addon, $event)"
							/>
							<strong class="b em-toggle-switch"></strong>
							<strong class="b em-toggle-track"></strong>
						</div>
					</div>
				</div>
				<h4 class="tw-mt-2">{{ translate(addon.name) }}</h4>
				<p class="tw-text-medium tw-text-sm tw-text-neutral-800">
					{{ translate(addon.description) }}
				</p>

				<div>
					<button class="tw-btn-tertiary tw-w-full" @click="currentAddon = addon">
						<span>{{ translate('COM_EMUNDUS_SETTINGS_ADDONS_UPDATE') }}</span>
					</button>
				</div>
			</div>
		</div>

		<div v-if="currentAddon">
			<div class="tw-mb-2 tw-flex tw-cursor-pointer tw-items-center tw-gap-1" @click="currentAddon = null">
				<span class="material-symbols-outlined tw-text-neutral-900">arrow_back</span>
				<span>{{ translate('COM_EMUNDUS_ONBOARD_ADD_RETOUR') }}</span>
			</div>

			<Messenger
				v-if="currentAddon.type === 'messenger'"
				:addon="currentAddon"
				@messengerSaved="
					currentAddon = null;
					getAddons();
				"
			/>
			<RankingTool
				v-else-if="currentAddon.type === 'ranking'"
				:addon="currentAddon"
				@rankingToolSaved="
					currentAddon = null;
					getAddons();
				"
			/>
			<SMSAddon
				v-if="currentAddon.type === 'sms'"
				:addon="currentAddon"
				@addonSaved="
					currentAddon = null;
					getAddons();
				"
			></SMSAddon>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<style scoped></style>
