<template>
	<div id="edit-campaign">
		<div class="em-w-custom"></div>
		<div class="em-card-shadow tw-rounded-2xl tw-border tw-border-neutral-300 tw-bg-white tw-p-6">
			<div>
				<div
					class="tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-rounded-md tw-px-2 tw-py-1 hover:tw-bg-neutral-300"
					@click="redirectJRoute('index.php?option=com_emundus&view=campaigns')"
				>
					<span class="material-symbols-outlined tw-text-neutral-600">navigate_before</span>
					<span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
				</div>
				<div class="tw-mt-4 tw-flex tw-items-center">
					<h1>{{ translate(selectedMenuItem.name) }}</h1>
				</div>
				<p v-html="translate(selectedMenuItem.description)"></p>
				<hr />

				<div id="campaign-info-line" class="tw-mb-8 tw-flex tw-items-center">
					<p>
						<b style="color: var(--em-profile-color); font-weight: 700 !important"> {{ form.label }}</b>
						{{ translate('COM_EMUNDUS_ONBOARD_FROM') }}
						<strong>{{ form.start_date }}</strong>
						{{ translate('COM_EMUNDUS_ONBOARD_TO') }}
						<strong>{{ form.end_date }}</strong>
					</p>
				</div>

				<Tabs
					v-show="profileId"
					:tabs="tabs"
					:classes="'tw-overflow-x-scroll tw-flex tw-items-center tw-gap-2 tw-ml-7'"
				></Tabs>

				<div class="tw-relative tw-w-full tw-rounded-2xl tw-border tw-border-neutral-300 tw-bg-white tw-p-6">
					<div v-if="selectedMenuItem.id === 5" class="warning-message-program mb-1">
						<p class="flex flex-row tw-text-red-600">
							<span class="material-symbols-outlined tw-mr-2 tw-text-red-600">warning_amber</span
							>{{ translate('COM_EMUNDUS_ONBOARD_PROGRAM_WARNING') }}
						</p>
						<ul v-if="campaignsByProgram.length > 0" class="em-pl-16 tw-mb-8 tw-mt-2">
							<li v-for="campaign in campaignsByProgram" :key="'camp_progs_' + campaign.id">{{ campaign.label }}</li>
						</ul>
					</div>

					<transition name="fade">
						<add-campaign
							v-if="selectedMenuItem.id === 1 && campaignId !== ''"
							:campaign="campaignId"
							:coordinatorAccess="true"
							:actualLanguage="actualLanguage"
							:manyLanguages="manyLanguages"
							@nextSection="next"
							@getInformations="initInformations"
							@updateHeader="updateHeader"
						></add-campaign>
						<campaign-more
							v-else-if="selectedMenuItem.id === 2 && campaignId !== ''"
							:campaignId="campaignId"
							:defaultFormUrl="campaignMoreFormUrl"
						>
						</campaign-more>
						<campaign-steps
							v-else-if="selectedMenuItem.name === 'COM_EMUNDUS_CAMPAIGN_STEPS' && campaignId !== ''"
							:campaignId="campaignId"
							@nextSection="next"
						>
						</campaign-steps>
						<add-documents-dropfiles
							v-else-if="selectedMenuItem.id === 3"
							:funnelCategorie="selectedMenuItem.label"
							:profileId="getProfileId"
							:campaignId="campaignId"
							:langue="actualLanguage"
							:manyLanguages="manyLanguages"
						/>
						<add-email v-else-if="selectedMenuItem.id === 5 && program.id != 0" :prog="Number(program.id)"></add-email>

						<History v-else-if="selectedMenuItem.id === 6" extension="com_emundus.campaign" :itemId="campaignId" />
					</transition>
				</div>
			</div>

			<div
				class="tw-mt-4 tw-flex tw-items-center tw-justify-end"
				v-if="['addDocumentsDropfiles'].includes(selectedMenu)"
			>
				<button type="button" class="mb-4 tw-btn-primary tw-w-auto" @click="next">
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTINUER') }}
				</button>
			</div>

			<div class="em-page-loader" v-if="loading"></div>
		</div>
	</div>
</template>

<script>
import mixin from '@/mixins/mixin.js';
import campaignService from '@/services/campaign.js';
import formService from '@/services/form.js';
import programmeService from '@/services/programme.js';
import settingsService from '@/services/settings.js';

import addCampaign from '@/views/addCampaign.vue';
import AddDocumentsDropfiles from '@/components/FunnelFormulaire/addDocumentsDropfiles.vue';
import addEmail from '@/components/FunnelFormulaire/addEmail.vue';
import campaignMore from '@/components/FunnelFormulaire/CampaignMore.vue';
import campaignSteps from '@/components/FunnelFormulaire/CampaignSteps.vue';

import { useGlobalStore } from '@/stores/global.js';
import History from '@/views/History.vue';
import Tabs from '@/components/Utils/Tabs.vue';

export default {
	name: 'CampaignEdition',

	components: {
		Tabs,
		History,
		AddDocumentsDropfiles,
		addCampaign,
		addEmail,
		campaignMore,
		campaignSteps,
	},

	props: {
		index: Number,
	},
	mixins: [mixin],

	data: () => ({
		campaignId: 0,
		actualLanguage: '',
		manyLanguages: 0,
		prid: '',
		tabs: [
			{
				id: 1,
				code: 'global',
				name: 'COM_EMUNDUS_GLOBAL_INFORMATIONS',
				description: 'COM_EMUNDUS_GLOBAL_INFORMATIONS_DESC',
				icon: 'info',
				active: true,
				displayed: true,
			},
			{
				id: 2,
				code: 'more',
				name: 'COM_EMUNDUS_CAMPAIGN_MORE',
				description: 'COM_EMUNDUS_CAMPAIGN_MORE_DESC',
				icon: 'note_stack',
				active: false,
				displayed: false,
			},
			{
				id: 7,
				code: 'steps',
				name: 'COM_EMUNDUS_CAMPAIGN_STEPS',
				description: '',
				icon: 'description',
				active: false,
				displayed: true,
			},
			{
				id: 3,
				code: 'attachments',
				name: 'COM_EMUNDUS_DOCUMENTS_CAMPAIGNS',
				description: 'COM_EMUNDUS_DOCUMENTS_CAMPAIGNS_DESC',
				icon: 'description',
				active: false,
				displayed: true,
			},
			{
				id: 5,
				code: 'emails',
				name: 'COM_EMUNDUS_EMAILS',
				description: 'COM_EMUNDUS_EMAILS_DESC',
				icon: 'mail',
				active: false,
				displayed: true,
			},
			{
				id: 6,
				code: 'history',
				name: 'COM_EMUNDUS_GLOBAL_HISTORY',
				description: '',
				icon: 'history',
				active: false,
				displayed: true,
			},
		],

		selectedMenu: 'addCampaign',
		formReload: 0,
		prog: 0,
		loading: true,
		closeSubmenu: true,
		profileId: null,
		profiles: [],
		campaignsByProgram: [],
		form: {},
		campaignMoreFormUrl: '',
		program: {
			id: 0,
			code: '',
			label: '',
			notes: '',
			programmes: [],
			tmpl_badge: '',
			published: 0,
			apply_online: 0,
			synthesis: '',
			tmpl_trombinoscope: '',
		},
	}),

	created() {
		const globalStore = useGlobalStore();

		// Get datas that we need with store
		this.campaignId = parseInt(globalStore.datas.campaignId.value);
		this.actualLanguage = globalStore.getCurrentLang;
		this.manyLanguages = globalStore.hasManyLanguages;
		//

		this.getProgram();
		this.getCampaignMoreForm().then((response) => {
			if (globalStore.datas.tabs) {
				let tabsToDisplay = globalStore.datas.tabs.value.split(',');
				this.tabs.forEach((tab) => {
					if (tab.code !== 'more') {
						tab.displayed = tabsToDisplay.includes(tab.code);
						if (!tab.displayed) {
							tab.active = false;
						}
					}
				});
			}

			// First found tab displayed and set active
			let firstTabDisplayed = this.tabs.find((tab) => tab.displayed);
			if (firstTabDisplayed) {
				firstTabDisplayed.active = true;
			}

			if (!this.tabs[0].displayed) {
				campaignService.getCampaignById(this.campaignId).then((response) => {
					this.form = response.data.campaign;
					this.initInformations(this.form);
				});
			}

			this.loading = false;
		});

		if (this.actualLanguage === 'en') {
			this.langue = 1;
		}
	},

	methods: {
		getCampaignMoreForm() {
			return new Promise((resolve, reject) => {
				campaignService
					.getCampaignMoreFormUrl(this.campaignId)
					.then((response) => {
						if (response.status && response.data.length > 0) {
							const globalStore = useGlobalStore();
							if (globalStore.datas.tabs) {
								let tabsToDisplay = globalStore.datas.tabs.value.split(',');
								if (tabsToDisplay.includes('more')) {
									this.tabs[1].displayed = true;
								}
							} else {
								this.tabs[1].displayed = true;
							}

							this.campaignMoreFormUrl = response.data;
						}

						resolve();
					})
					.catch((error) => {
						reject(error);
						console.error(error);
					});
			});
		},

		initInformations(campaign) {
			this.form.label = campaign.label;
			this.form.profile_id = campaign.profile_id;
			this.form.program_id = campaign.progid;

			this.initDates(campaign);

			formService.getPublishedForms().then((response) => {
				this.profiles = response.data.data;
				if (this.form.profile_id == null) {
					this.profiles.length != 0 ? (this.profileId = this.profiles[0].id) : (this.profileId = null);
					if (this.profileId != null) {
						this.formReload += 1;
					}
				} else {
					this.formReload += 1;
					this.profileId = this.form.profile_id;
				}
				this.loading = false;

				let cookie = this.getCookie('campaign_' + this.campaignId + '_menu');
				if (cookie) {
					this.menuHighlight = cookie;
					document.cookie = 'campaign_' + this.campaignId + '_menu =; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
				}
			});
		},

		updateHeader(value) {
			this.form.label = value.label[this.actualLanguage];
			this.initDates(value);
		},

		initDates(campaign) {
			this.form.start_date = campaign.start_date;
			this.form.end_date = campaign.end_date;

			let currentLanguage = useGlobalStore().getCurrentLang;
			if (currentLanguage === '' || currentLanguage === undefined) {
				currentLanguage = 'fr-FR';
			}

			const dateOptions = { dateStyle: 'long', timeStyle: 'short' };
			const startDate = new Date(campaign.start_date);
			this.form.start_date = new Intl.DateTimeFormat(currentLanguage, dateOptions).format(startDate);

			if (this.form.end_date === '0000-00-00 00:00:00') {
				this.form.end_date = null;
			} else {
				const endDate = new Date(campaign.end_date);
				this.form.end_date = new Intl.DateTimeFormat(currentLanguage, dateOptions).format(endDate);
			}
		},

		getProgram() {
			campaignService
				.getProgrammeByCampaignID(this.campaignId)
				.then((response) => {
					this.program = response.data;

					if (this.program.id) {
						programmeService.getCampaignsByProgram(this.program.id).then((resp) => {
							this.campaignsByProgram = resp.campaigns;
						});
					}
				})
				.catch((e) => {
					console.error(e);
				});
		},

		setProfileId(prid) {
			this.profileId = prid;
		},
		next() {
			let index = this.tabs.findIndex((tab) => tab.active);
			if (index < this.tabs.length - 1) {
				this.tabs[index].active = false;

				if (this.tabs[index + 1].displayed) {
					this.tabs[index + 1].active = true;
				} else {
					this.tabs[index + 2].active = true;
				}
			}
		},

		redirectJRoute(link) {
			settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
		},

		getCookie(cname) {
			var name = cname + '=';
			var decodedCookie = decodeURIComponent(document.cookie);
			var ca = decodedCookie.split(';');

			for (let c of ca) {
				while (c.charAt(0) == ' ') {
					c = c.substring(1);
				}
				if (c.indexOf(name) == 0) {
					return c.substring(name.length, c.length);
				}
			}
			return '';
		},
	},
	computed: {
		getProfileId() {
			return Number(this.profileId);
		},
		selectedMenuItem() {
			return this.tabs.find((tab) => tab.active);
		},
	},
};
</script>

<style scoped>
@import '../assets/css/formbuilder.scss';

.w--current {
	border: solid 1px #eeeeee;
	background: #eeeeee;
}

.w--current:hover {
	color: var(--em-profile-color);
}

.tw-cursor-pointer:hover {
	color: var(--em-profile-color);
}

.em-w-custom {
	width: calc(100% - 75px) !important;
	margin-left: auto;
}

#add-form-next-campaign {
	width: 100%;
}
</style>
