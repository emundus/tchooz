<script>
/* Services */
import settingsService from '@/services/settings';

/* Components */
import TeamsSetup from '@/components/Settings/Integration/TeamsSetup.vue';
import DynamicsSetup from '@/components/Settings/Integration/DynamicsSetup.vue';
import AmmonSetup from '@/components/Settings/Integration/AmmonSetup.vue';
import OVHSetup from '@/components/Settings/Integration/OVHSetup.vue';
import YousignSetup from '@/components/Settings/Integration/YousignSetup.vue';
import SogecommerceSetup from '@/components/Settings/Integration/SogecommerceSetup.vue';

export default {
	name: 'Integration',
	components: { DynamicsSetup, TeamsSetup, AmmonSetup, OVHSetup, YousignSetup, SogecommerceSetup },
	data() {
		return {
			loading: true,

			apps: [],
			currentApp: null,
		};
	},
	created() {
		this.getApps();
	},
	methods: {
		getApps() {
			settingsService.getApps().then((response) => {
				this.apps = response.data;

				this.loading = false;
			});
		},
		toggleEnabled(app, event) {
			let value = event.target.checked ? 1 : 0;
			settingsService.toggleAppEnabled(app.id, value);
		},
	},
};
</script>

<template>
	<div>
		<div class="em-grid-3-2-1" v-if="!currentApp && apps.length > 0">
			<div
				v-for="app in apps"
				class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
			>
				<div class="tw-flex tw-items-center tw-justify-between">
					<img class="tw-w-[45px]" :src="'/images/emundus/icons/' + app.icon" :alt="app.type" />
					<div class="tw-flex tw-items-center" v-if="app.config !== '{}'">
						<div class="em-toggle">
							<input
								type="checkbox"
								true-value="1"
								false-value="0"
								class="em-toggle-check"
								:id="app.id + '_enabled_input'"
								v-model="app.enabled"
								@click="toggleEnabled(app, $event)"
							/>
							<strong class="b em-toggle-switch"></strong>
							<strong class="b em-toggle-track"></strong>
						</div>
					</div>
				</div>
				<h4 class="tw-mt-2">{{ app.name }}</h4>
				<p class="tw-text-medium tw-text-sm tw-text-neutral-800">
					{{ app.description }}
				</p>

				<div v-if="app.enabled === 0 && app.config === '{}'">
					<button class="tw-btn-secondary tw-w-full" @click="currentApp = app">
						<span>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_ADD') }}</span>
					</button>
				</div>

				<div v-else>
					<button class="tw-btn-secondary tw-w-full" @click="currentApp = app">
						<span>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE') }}</span>
					</button>
				</div>
			</div>
		</div>

		<div v-else-if="!currentApp">
			<h2>Aucune app dispo.</h2>
		</div>

		<div v-if="currentApp">
			<div class="tw-mb-2 tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-gap-1" @click="currentApp = null">
				<span class="material-symbols-outlined tw-text-neutral-900">arrow_back</span>
				<span>{{ translate('COM_EMUNDUS_ONBOARD_ADD_RETOUR') }}</span>
			</div>

			<TeamsSetup
				v-if="currentApp.type === 'teams'"
				:app="currentApp"
				@teamsInstalled="
					currentApp = null;
					getApps();
				"
			/>
			<DynamicsSetup
				v-else-if="currentApp.type === 'microsoft_dynamics'"
				:app="currentApp"
				@dynamicsInstalled="
					currentApp = null;
					getApps();
				"
			/>
			<AmmonSetup
				v-else-if="currentApp.type === 'ammon'"
				:app="currentApp"
				@ammonInstalled="
					currentApp = null;
					getApps();
				"
			/>
			<OVHSetup
				v-else-if="currentApp.type === 'ovh'"
				:app="currentApp"
				@ovhInstalled="
					currentApp = null;
					getApps();
				"
			/>
			<YousignSetup
				v-else-if="currentApp.type === 'yousign'"
				:app="currentApp"
				@yousignInstalled="
					currentApp = null;
					getApps();
				"
			/>
			<SogecommerceSetup
				v-else-if="currentApp.type === 'sogecommerce'"
				:app="currentApp"
				@sogecommerceInstalled="
					currentApp = null;
					getApps();
				"
			/>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<style scoped></style>
