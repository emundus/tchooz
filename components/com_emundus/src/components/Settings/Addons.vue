<script>
/* Services */
import settingsService from '@/services/settings';
import Messenger from '@/components/Settings/Addons/Messenger.vue';
import SMSAddon from '@/components/Settings/Addons/SMSAddon.vue';
import RankingTool from '@/components/Settings/Addons/RankingTool.vue';
import PaymentAddon from '@/components/Settings/Addons/PaymentAddon.vue';
import Popover from '@/components/Popover.vue';
import alerts from '@/mixins/alerts.js';
import Addon from '@/components/Settings/Addons/Addon.vue';

import { useGlobalStore } from '@/stores/global.js';
import Tag from '@/components/Atoms/Tag.vue';
import Button from '@/components/Atoms/Button.vue';
import CustomReference from '@/components/Settings/Addons/CustomReference.vue';
import StripeSetup from '@/components/Settings/Integration/StripeSetup.vue';
import AmmonSetup from '@/components/Settings/Integration/AmmonSetup.vue';
import IntegrationSetup from '@/components/Settings/Integration/IntegrationSetup.vue';

export default {
	name: 'Addons',
	components: {
		IntegrationSetup,
		AmmonSetup,
		StripeSetup,
		CustomReference,
		Button,
		Tag,
		Messenger,
		SMSAddon,
		RankingTool,
		PaymentAddon,
		Popover,
		Addon,
	},
	mixins: [alerts],
	data() {
		return {
			loading: true,
			searchThroughAddons: '',

			addons: [],
			currentAddon: null,

			apps: [],
			currentApp: null,

			sysadminAccess: false,

			addonsWithParams: ['messenger', 'ranking', 'sms', 'payment', 'anonymous', 'custom_reference_format'],
		};
	},
	created() {
		this.getAddons();
		this.getApps();

		this.sysadminAccess = useGlobalStore().hasSysadminAccess;
	},
	methods: {
		getAddons() {
			settingsService.getAddons().then((response) => {
				this.addons = response.data;

				this.loading = false;
			});
		},
		getApps() {
			settingsService.getApps().then((response) => {
				this.apps = response.data;

				this.loading = false;
			});
		},
		toggleEnabled(addon, event) {
			this.toggleActivated(addon, event.target.checked);
		},
		toggleAppEnabled(addon, event) {
			this.toggleActivated(addon, event.target.checked);
		},
		toggleDisplay(addon, display) {
			let value = display ? 1 : 0;
			this.alertConfirm(
				display
					? this.translate('COM_EMUNDUS_ADDON_CONFIRM_DISPLAY')
					: this.translate('COM_EMUNDUS_ADDON_CONFIRM_HIDE'),
				display
					? this.translate('COM_EMUNDUS_ADDON_CONFIRM_DISPLAY_TEXT')
					: this.translate('COM_EMUNDUS_ADDON_CONFIRM_HIDE_TEXT'),
			).then((confirm) => {
				if (confirm.isConfirmed) {
					settingsService.toggleAddonDisplay(addon.namekey, value).then(() => {
						addon.displayed = display;
						addon.suggested = false; // Reset suggested status when display is toggled

						this.alertSuccess('COM_EMUNDUS_SETTINGS_ADDONS_DISPLAY_TOGGLE_SUCCESS');
					});
				}
			});
		},
		toggleActivated(addon, activated) {
			let value = activated ? 1 : 0;
			this.alertConfirm(
				activated
					? this.translate('COM_EMUNDUS_ADDON_CONFIRM_ACTIVATION')
					: this.translate('COM_EMUNDUS_ADDON_CONFIRM_DEACTIVATION'),
				activated
					? this.translate('COM_EMUNDUS_ADDON_CONFIRM_ACTIVATION_TEXT')
					: this.translate('COM_EMUNDUS_ADDON_CONFIRM_DEACTIVATION_TEXT'),
			).then((confirm) => {
				if (!confirm.isConfirmed && !this.sysadminAccess) {
					// Revert the change in the UI if the user cancels and doesn't have sysadmin access
					addon.activated = !activated;
					return;
				}

				if (confirm.isConfirmed) {
					settingsService.toggleAddonEnabled(addon.namekey, value).then(() => {
						addon.activated = activated;

						this.alertSuccess('COM_EMUNDUS_SETTINGS_ADDONS_ACTIVATION_TOGGLE_SUCCESS');
					});
				}
			});
		},
		toggleCommercial(addon, suggested) {
			let value = suggested ? 1 : 0;
			this.alertConfirm(
				suggested
					? this.translate('COM_EMUNDUS_ADDON_CONFIRM_SUGGEST')
					: this.translate('COM_EMUNDUS_ADDON_CONFIRM_REMOVE_SUGGEST'),
				suggested
					? this.translate('COM_EMUNDUS_ADDON_CONFIRM_SUGGEST_TEXT')
					: this.translate('COM_EMUNDUS_ADDON_CONFIRM_REMOVE_SUGGEST_TEXT'),
			).then((confirm) => {
				if (confirm.isConfirmed) {
					settingsService.toggleAddonSuggest(addon.namekey, value).then(() => {
						addon.suggested = suggested;

						this.alertSuccess('COM_EMUNDUS_SETTINGS_ADDONS_SUGGEST_TOGGLE_SUCCESS');
					});
				}
			});
		},
		addonHasConfiguration(addon) {
			let hasConfig = false;

			const configuration = addon.params;

			if (configuration) {
				if (typeof configuration === 'string') {
					try {
						const configObj = JSON.parse(configuration);
						hasConfig = Object.keys(configObj).length > 0;
					} catch (e) {
						hasConfig = false;
					}
				} else if (typeof configuration === 'object') {
					hasConfig = Object.keys(configuration).length > 0;
				}
			}

			return hasConfig || this.addonsWithParams.includes(addon.namekey);
		},
		toggleAppDisplay(app, publish) {
			let value = publish ? 1 : 0;
			this.alertConfirm(
				publish ? this.translate('COM_EMUNDUS_APP_CONFIRM_DISPLAY') : this.translate('COM_EMUNDUS_APP_CONFIRM_HIDE'),
				publish
					? this.translate('COM_EMUNDUS_APP_CONFIRM_DISPLAY_TEXT')
					: this.translate('COM_EMUNDUS_APP_CONFIRM_HIDE_TEXT'),
			).then((confirm) => {
				if (confirm.isConfirmed) {
					settingsService.toggleAppPublished(app.id, value).then(() => {
						app.published = publish;

						this.alertSuccess('COM_EMUNDUS_SETTINGS_APP_DISPLAY_TOGGLE_SUCCESS');
					});
				}
			});
		},
		toggleAppActivated(app, enabled) {
			let value = enabled ? 1 : 0;
			this.alertConfirm(
				enabled
					? this.translate('COM_EMUNDUS_APP_CONFIRM_ACTIVATION')
					: this.translate('COM_EMUNDUS_APP_CONFIRM_DEACTIVATION'),
				enabled
					? this.translate('COM_EMUNDUS_APP_CONFIRM_ACTIVATION_TEXT')
					: this.translate('COM_EMUNDUS_APP_CONFIRM_DEACTIVATION_TEXT'),
			).then((confirm) => {
				if (!confirm.isConfirmed && !this.sysadminAccess) {
					// Revert the change in the UI if the user cancels and doesn't have sysadmin access
					app.enabled = !enabled;
					return;
				}

				if (confirm.isConfirmed) {
					settingsService.toggleAppEnabled(app.id, value).then((response) => {
						if (response.status) {
							app.enabled = enabled;
							settingsService.getApp(app.id).then((response) => {
								const updatedApp = response.data;
								app.config = updatedApp.config; // Update the app config with the latest from the server
							});

							this.alertSuccess('COM_EMUNDUS_SETTINGS_APP_ACTIVATION_TOGGLE_SUCCESS');
						} else {
							this.alertError(response.msg);
						}
					});
				}
			});
		},
		appHasConfiguration(app) {
			return app.enabled && Object.keys(app.config).length > 0;
		},
		needMore(addon) {
			this.alertConfirm(
				'COM_EMUNDUS_SETTINGS_ADDONS_SUGGESTED_CONFIRM_TITLE',
				'COM_EMUNDUS_SETTINGS_ADDONS_SUGGESTED_CONFIRM_TEXT',
				false,
				'COM_EMUNDUS_SETTINGS_ADDONS_SUGGESTED_CONFIRM_BUTTON',
			).then((confirm) => {
				if (confirm.isConfirmed) {
					settingsService.sendCommercialInterest(addon.namekey).then(() => {
						this.alertSuccess('COM_EMUNDUS_SETTINGS_ADDONS_SUGGESTED_CONFIRM_SUCCESS');
					});
				}
			});
		},
		backToAddons() {
			this.currentAddon = null;
			this.currentApp = null;
		},
		isDisplayed(addon) {
			let isDisplayed = false;
			if (addon.hasOwnProperty('displayed') && addon.displayed) {
				isDisplayed = true;
			} else if (addon.hasOwnProperty('published') && addon.published) {
				isDisplayed = true;
			}

			return isDisplayed;
		},
		displayedText(addon) {
			return this.isDisplayed(addon) ? this.translate('COM_EMUNDUS_VISIBLE') : this.translate('COM_EMUNDUS_HIDDEN');
		},
		enabledText(addon) {
			return addon.activated || addon.enabled
				? this.translate('COM_EMUNDUS_ENABLED')
				: this.translate('COM_EMUNDUS_DISABLED');
		},
		bgDisplayedClass(addon) {
			return this.isDisplayed(addon) ? 'tw-bg-blue-600' : 'tw-bg-neutral-300';
		},
		textDisplayedClass(addon) {
			return this.isDisplayed(addon) ? 'tw-text-white' : 'tw-text-neutral-700';
		},
		bgEnabledClass(addon) {
			return addon.activated || addon.enabled ? 'tw-bg-main-500' : 'tw-bg-neutral-300';
		},
		textEnabledClass(addon) {
			return addon.activated || addon.enabled ? 'tw-text-white' : 'tw-text-neutral-700';
		},
	},
	computed: {
		displayedAddons() {
			if (!this.addons) return [];

			let addons = [];
			this.addons.forEach((addon) => {
				if (addon.label.toLowerCase().includes(this.searchThroughAddons.toLowerCase())) {
					addons.push(addon);
				}
			});

			return addons;
		},

		displayedApps() {
			if (!this.apps) return [];

			let apps = [];
			this.apps.forEach((app) => {
				if (app.name.toLowerCase().includes(this.searchThroughAddons.toLowerCase())) {
					apps.push(app);
				}
			});

			return apps;
		},
	},
};
</script>

<template>
	<div>
		<div v-if="!currentAddon && !currentApp">
			<input
				type="text"
				v-model="searchThroughAddons"
				:placeholder="translate('COM_EMUNDUS_ADDON_SEARCH_PLACEHOLDER')"
				class="tw-mb-4 tw-w-full tw-rounded tw-border tw-border-neutral-300 tw-p-2"
			/>

			<div class="tw-mt-4">
				<h2>{{ translate('COM_EMUNDUS_ADDON_MODULE_TITLE') }}</h2>
				<div class="em-grid-3-2-1 tw-mt-2">
					<div
						v-for="addon in displayedAddons"
						class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
					>
						<div class="tw-flex tw-w-full tw-flex-col tw-gap-3">
							<!-- sysadmin access can see tags visible, enabled -->
							<div v-if="sysadminAccess" class="tw-flex tw-items-start tw-justify-between">
								<div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
									<Tag
										:text="displayedText(addon)"
										:bg-color-class="bgDisplayedClass(addon)"
										:text-color-class="textDisplayedClass(addon)"
									/>
									<Tag
										:text="enabledText(addon)"
										:bg-color-class="bgEnabledClass(addon)"
										:text-color-class="textEnabledClass(addon)"
									/>
									<Tag
										v-if="addon.suggested"
										:text="translate('COM_EMUNDUS_SUGGESTED')"
										bg-color-class="tw-bg-yellow-500"
										text-color-class="tw-bg-neutral-900"
									/>
								</div>

								<div>
									<popover
										:position="'left'"
										:button="translate('COM_EMUNDUS_ONBOARD_ACTIONS')"
										:hide-button-label="true"
										class="custom-popover-arrow"
									>
										<ul style="list-style-type: none; margin: 0" class="em-flex-col-center tw-p-4">
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="!addon.displayed"
												@click="toggleDisplay(addon, true)"
											>
												{{ translate('COM_EMUNDUS_ADDON_DISPLAY') }}
											</li>
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="addon.displayed"
												@click="toggleDisplay(addon, false)"
											>
												{{ translate('COM_EMUNDUS_ADDON_HIDE') }}
											</li>
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="!addon.activated"
												@click="toggleActivated(addon, true)"
											>
												{{ translate('COM_EMUNDUS_ADDON_ENABLE') }}
											</li>
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="addon.activated"
												@click="toggleActivated(addon, false)"
											>
												{{ translate('COM_EMUNDUS_ADDON_DISABLE') }}
											</li>
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="!addon.suggested"
												@click="toggleCommercial(addon, true)"
											>
												{{ translate('COM_EMUNDUS_ADDON_SUGGEST') }}
											</li>
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="addon.suggested"
												@click="toggleCommercial(addon, false)"
											>
												{{ translate('COM_EMUNDUS_ADDON_REMOVE_COMMERCIAL') }}
											</li>
										</ul>
									</popover>
								</div>
							</div>

							<div class="tw-flex tw-items-center tw-justify-between">
								<span class="material-symbols-outlined" style="font-size: 32px">{{ addon.icon }}</span>
								<div class="tw-flex tw-items-center" v-if="!sysadminAccess && addon.displayed">
									<div class="em-toggle">
										<input
											type="checkbox"
											class="em-toggle-check"
											:id="addon.namekey + '_enabled_input'"
											v-model="addon.activated"
											@click="toggleEnabled(addon, $event)"
										/>
										<strong class="b em-toggle-switch"></strong>
										<strong class="b em-toggle-track"></strong>
									</div>
								</div>
							</div>
							<div class="tw-flex tw-flex-col tw-gap-2">
								<h4 class="tw-mt-2">{{ addon.label }}</h4>
								<p class="tw-text-medium tw-text-sm tw-text-neutral-800">
									{{ addon.description }}
								</p>
							</div>
						</div>

						<Button
							variant="cancel"
							width="full"
							class="tw-mt-4"
							v-if="addonHasConfiguration(addon) && (addon.displayed || sysadminAccess)"
							@click="currentAddon = addon"
						>
							{{ translate('COM_EMUNDUS_SETTINGS_ADDONS_UPDATE') }}
						</Button>
						<Button
							width="full"
							v-else-if="!sysadminAccess && addon.suggested"
							class="tw-mt-4"
							@click="needMore(addon)"
						>
							{{ translate('COM_EMUNDUS_SETTINGS_ADDONS_SUGGESTED') }}
						</Button>
					</div>
				</div>
			</div>

			<div class="tw-mt-4">
				<h2>{{ translate('COM_EMUNDUS_ADDON_INTEGRATION_TITLE') }}</h2>
				<div class="em-grid-3-2-1 tw-mt-2">
					<div
						v-for="app in displayedApps"
						class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
					>
						<div class="tw-flex tw-w-full tw-flex-col tw-gap-3">
							<!-- sysadmin access can see tags visible, enabled -->
							<div v-if="sysadminAccess" class="tw-flex tw-items-start tw-justify-between">
								<div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
									<Tag
										:text="displayedText(app)"
										:bg-color-class="bgDisplayedClass(app)"
										:text-color-class="textDisplayedClass(app)"
									/>
									<Tag
										:text="enabledText(app)"
										:bg-color-class="bgEnabledClass(app)"
										:text-color-class="textEnabledClass(app)"
									/>
								</div>

								<div>
									<popover
										:position="'left'"
										:button="translate('COM_EMUNDUS_ONBOARD_ACTIONS')"
										:hide-button-label="true"
										class="custom-popover-arrow"
									>
										<ul style="list-style-type: none; margin: 0" class="em-flex-col-center tw-p-4">
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="!app.published"
												@click="toggleAppDisplay(app, true)"
											>
												{{ translate('COM_EMUNDUS_ADDON_DISPLAY') }}
											</li>
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="app.published"
												@click="toggleAppDisplay(app, false)"
											>
												{{ translate('COM_EMUNDUS_ADDON_HIDE') }}
											</li>
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="!app.enabled"
												@click="toggleAppActivated(app, true)"
											>
												{{ translate('COM_EMUNDUS_ADDON_ENABLE') }}
											</li>
											<li
												class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
												v-if="app.enabled"
												@click="toggleAppActivated(app, false)"
											>
												{{ translate('COM_EMUNDUS_ADDON_DISABLE') }}
											</li>
										</ul>
									</popover>
								</div>
							</div>

							<div class="tw-flex tw-items-center tw-justify-between">
								<img class="tw-h-[45px]" :src="'/images/emundus/icons/' + app.icon" :alt="app.type" />
								<div class="tw-flex tw-items-center" v-if="!sysadminAccess && app.published">
									<div class="em-toggle">
										<input
											type="checkbox"
											class="em-toggle-check"
											:id="app.type + '_enabled_input'"
											v-model="app.enabled"
											@click="toggleAppEnabled(app, $event)"
										/>
										<strong class="b em-toggle-switch"></strong>
										<strong class="b em-toggle-track"></strong>
									</div>
								</div>
							</div>
							<div class="tw-flex tw-flex-col tw-gap-2">
								<h4 class="tw-mt-2">{{ app.name }}</h4>
								<p class="tw-text-medium tw-text-sm tw-text-neutral-800">
									{{ app.description }}
								</p>
							</div>
						</div>

						<Button
							variant="cancel"
							width="full"
							class="tw-mt-4"
							v-if="appHasConfiguration(app)"
							@click="currentApp = app"
						>
							{{ translate('COM_EMUNDUS_SETTINGS_ADDONS_UPDATE') }}
						</Button>
					</div>
				</div>
			</div>
		</div>

		<div v-if="currentAddon || currentApp">
			<div class="tw-mb-2 tw-flex tw-cursor-pointer tw-items-center tw-gap-1" @click="backToAddons">
				<span class="material-symbols-outlined tw-text-neutral-900">arrow_back</span>
				<span>{{ translate('COM_EMUNDUS_ONBOARD_ADD_RETOUR') }}</span>
			</div>

			<div v-if="currentAddon">
				<Messenger
					v-if="currentAddon.namekey === 'messenger'"
					:addon="currentAddon"
					@messengerSaved="
						currentAddon = null;
						getAddons();
					"
				/>
				<RankingTool
					v-else-if="currentAddon.namekey === 'ranking'"
					:addon="currentAddon"
					@rankingToolSaved="
						currentAddon = null;
						getAddons();
					"
				/>
				<SMSAddon
					v-else-if="currentAddon.namekey === 'sms'"
					:addon="currentAddon"
					@addonSaved="
						currentAddon = null;
						getAddons();
					"
				></SMSAddon>

				<PaymentAddon
					v-else-if="currentAddon.namekey === 'payment'"
					:addon="currentAddon"
					@addonSaved="
						currentAddon = null;
						getAddons();
					"
				></PaymentAddon>

				<CustomReference
					v-else-if="currentAddon.namekey === 'custom_reference_format'"
					@addonSaved="
						currentAddon = null;
						getAddons();
					"
				></CustomReference>

				<Addon
					v-else
					:addon="currentAddon"
					@addonSaved="
						currentAddon = null;
						getAddons();
					"
				></Addon>
			</div>

			<div v-if="currentApp">
				<AmmonSetup
					v-if="currentApp.type === 'ammon'"
					:app="currentApp"
					@ammonInstalled="
						currentApp = null;
						getApps();
					"
				/>

				<StripeSetup
					v-else-if="currentApp.type === 'stripe'"
					:app="currentApp"
					@stripeInstalled="
						currentApp = null;
						getApps();
					"
				/>

				<IntegrationSetup v-else-if="currentApp.type !== ''" :app="currentApp" :name="currentApp.name" />
			</div>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<style scoped></style>
