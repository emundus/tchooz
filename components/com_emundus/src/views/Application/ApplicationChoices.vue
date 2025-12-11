<script>
import campaignService from '@/services/campaign.js';
import applicationService from '@/services/application.js';

import Button from '@/components/Atoms/Button.vue';
import CampaignsList from '@/components/Organisms/Campaigns/CampaignsList.vue';
import Modal from '@/components/Modal.vue';
import Card from '@/components/Molecules/Card.vue';
import Back from '@/components/Utils/Back.vue';

import date from '@/mixins/date.js';
import string from '@/mixins/string.js';
import alerts from '@/mixins/alerts.js';
import campaign from '@/mixins/campaign.js';
import Info from '@/components/Utils/Info.vue';

export default {
	name: 'ApplicationChoices',
	components: { Info, Back, Card, Modal, CampaignsList, Button },
	mixins: [date, string, alerts, campaign],
	props: {
		fnum: {
			type: String,
		},
	},
	data() {
		return {
			campaigns: [],
			filters: [],
			parameters: {},
			search: '',

			choices: [],
			configuration: {},

			openCampaignModal: false,
			openFabrikFormModal: false,
			selectedChoice: 0,
			loading: false,

			status: {
				draft: this.translate('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_DRAFT'),
				waiting: this.translate('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_WAITING'),
				accepted: this.translate('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_ACCEPTED'),
				rejected: this.translate('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_REJECTED'),
				confirmed: this.translate('COM_TCHOOZ_ENUMS_APPLICATIONFILE_CHOICESSTATE_CONFIRMED'),
			},
		};
	},
	async mounted() {
		let fnum = this.fnum || '';

		this.loading = true;
		await this.getChoicesConfiguration(fnum);
		await this.getApplicationChoices(fnum);
		await this.getAvailableChoices(fnum);
		this.loading = false;

		window.addEventListener('message', async (event) => {
			if (event.data === 'CloseApplicationChoicesMoreModal') {
				this.openFabrikFormModal = false;
				this.selectedChoice = 0;
				this.loading = true;
				await this.getApplicationChoices(this.fnum || '');
				this.loading = false;
			}
		});
	},
	methods: {
		getAvailableChoices(fnum) {
			return new Promise((resolve) => {
				campaignService.getAvailableChoices(fnum, this.search, this.filters).then((res) => {
					if (res.code !== 200) {
						this.campaigns = [];
						this.configuration = null;
						resolve();
						return;
					}

					this.campaigns = res.data;
					this.filters = res.filters || [];
					this.parameters = res.parameters || {};

					if (this.campaigns && this.campaigns.length > 0) {
						this.choices.forEach((choice) => {
							this.markCampaignAsSelected(choice.campaign.id);
						});
					}

					resolve();
				});
			});
		},

		getApplicationChoices(fnum) {
			return new Promise((resolve) => {
				applicationService.getApplicationChoices(fnum).then((res) => {
					this.choices = res.data;

					resolve();
				});
			});
		},

		getChoicesConfiguration(fnum) {
			return new Promise((resolve) => {
				applicationService.getChoicesConfiguration(fnum).then((res) => {
					this.configuration = res.data;

					resolve();
				});
			});
		},

		applySearch(search) {
			this.search = search;
			this.getAvailableChoices(this.fnum || '');
		},

		applyFilters() {
			this.getAvailableChoices(this.fnum || '');
		},

		applyCampaign(campaign_id) {
			let fnum = this.fnum || '';

			this.loading = true;
			applicationService.addChoice(campaign_id, fnum).then((result) => {
				this.choices.push(result.data);
				this.markCampaignAsSelected(campaign_id);
				this.openCampaignModal = false;
				this.loading = false;
			});
		},

		markCampaignAsSelected(campaign_id, status = 'selected') {
			const campaign = this.campaigns.find((c) => c.id === campaign_id);
			if (campaign) {
				if (status === 'selected') {
					campaign.old_status = campaign.status;
					campaign.status = 'selected';
				} else if (campaign.old_status) {
					campaign.status = campaign.old_status;
				}
			}
		},

		openCampaignList() {
			if (this.maxChoicesReached) {
				return;
			}

			this.openCampaignModal = true;
		},

		removeChoice(id) {
			this.alertConfirm(
				'COM_EMUNDUS_APPLICATION_CHOICES_REMOVE_CHOICE_CONFIRM_TITLE',
				'COM_EMUNDUS_APPLICATION_CHOICES_REMOVE_CHOICE_CONFIRM_TEXT',
				false,
				'COM_EMUNDUS_APPLICATION_CHOICES_REMOVE_CHOICE_CONFIRM_BUTTON',
				'COM_EMUNDUS_ONBOARD_CANCEL',
			).then((result) => {
				if (result.isConfirmed) {
					let fnum = this.fnum || '';

					applicationService.removeChoice(id, fnum).then(() => {
						this.removeChoiceFromList(id);
					});
				}
			});
		},

		removeChoiceFromList(id) {
			const choice = this.choices.find((choice) => choice.id === id);
			const campaignId = choice ? choice.campaign.id : null;

			if (campaignId) {
				this.markCampaignAsSelected(campaignId, 'deselected');
			}

			this.choices = this.choices.filter((choice) => choice.id !== id);
		},

		reorderChoices(id, direction) {
			let fnum = this.fnum || '';

			const index = this.choices.findIndex((choice) => choice.id === id);
			if (index === -1) return;
			let newIndex = direction === 'up' ? index - 1 : index + 1;
			if (newIndex < 0 || newIndex >= this.choices.length) return;
			const choices = [...this.choices];
			const temp = choices[index];
			choices[index] = choices[newIndex];
			choices[newIndex] = temp;

			let formattedChoices = choices.map((choice, index) => {
				return {
					id: choice.id,
					ordering: index + 1,
				};
			});

			this.choices = choices;
			applicationService.reorderChoices(formattedChoices, fnum);
		},

		sendChoiceStep() {
			this.loading = true;

			// Display alert because applciation choices cannot be update after sending
			this.alertConfirm(
				'COM_EMUNDUS_APPLICATION_CHOICES_SEND_CONFIRM_TITLE',
				'COM_EMUNDUS_APPLICATION_CHOICES_SEND_CONFIRM_TEXT',
				false,
				'COM_EMUNDUS_APPLICATION_CHOICES_SEND_CONFIRM_BUTTON',
				'COM_EMUNDUS_ONBOARD_CANCEL',
			).then((result) => {
				if (result.isConfirmed) {
					applicationService.sendChoicesStep().then((response) => {
						this.alertSuccess(
							'COM_EMUNDUS_APPLICATION_CHOICES_SEND_SUCCESS_TITLE',
							'COM_EMUNDUS_APPLICATION_CHOICES_SEND_SUCCESS_TEXT',
						).then(() => {
							window.location.href = response.redirect;
						});
					});
				} else {
					this.loading = false;
				}
			});
		},

		confirmChoice(choice) {
			let fnum = this.fnum || '';

			this.alertConfirm(
				'COM_EMUNDUS_APPLICATION_CHOICES_CONFIRM_CHOICE_CONFIRM_TITLE',
				'COM_EMUNDUS_APPLICATION_CHOICES_CONFIRM_CHOICE_CONFIRM_TEXT',
				false,
				'COM_EMUNDUS_APPLICATION_CHOICES_CONFIRM_CHOICE_CONFIRM_BUTTON',
				'COM_EMUNDUS_ONBOARD_CANCEL',
			).then((result) => {
				if (result.isConfirmed) {
					this.loading = true;
					applicationService.confirmChoice(choice.id, fnum).then((response) => {
						this.alertSuccess(
							'COM_EMUNDUS_APPLICATION_CHOICES_CONFIRM_CHOICE_SUCCESS_TITLE',
							'COM_EMUNDUS_APPLICATION_CHOICES_CONFIRM_CHOICE_SUCCESS_TEXT',
						).then(() => {
							window.location.href = response.redirect;
						});
					});
				}
			});
		},

		refuseChoice(choice) {
			let fnum = this.fnum || '';

			this.alertConfirm(
				'COM_EMUNDUS_APPLICATION_CHOICES_REFUSE_CHOICE_CONFIRM_TITLE',
				'COM_EMUNDUS_APPLICATION_CHOICES_REFUSE_CHOICE_CONFIRM_TEXT',
				false,
				'COM_EMUNDUS_APPLICATION_CHOICES_REFUSE_CHOICE_CONFIRM_BUTTON',
				'COM_EMUNDUS_ONBOARD_CANCEL',
			).then((result) => {
				if (result.isConfirmed) {
					this.loading = true;
					applicationService.refuseChoice(choice.id, fnum).then((response) => {
						this.alertSuccess('COM_EMUNDUS_APPLICATION_CHOICES_REFUSE_CHOICE_SUCCESS_TITLE').then(() => {
							const choiceIndex = this.choices.findIndex((choiceOption) => choiceOption.id === choice.id);
							if (choiceIndex !== -1) {
								this.choices[choiceIndex].state = response.data.state;
								this.choices[choiceIndex].state_html = response.data.state_html;
							}

							this.loading = false;
						});
					});
				}
			});
		},

		async updateStatus(id, currentStatus) {
			let fnum = this.fnum || '';

			this.alertDropdown(
				'COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_TITLE',
				this.status,
				null,
				'COM_EMUNDUS_OK',
				'COM_EMUNDUS_ACTIONS_CANCEL',
				null,
				currentStatus.name.toLowerCase(),
			).then(async (result) => {
				if (result.isConfirmed) {
					applicationService.updateStatus(id, result.value).then(async (res) => {
						this.loading = true;
						await this.getChoicesConfiguration(fnum);
						await this.getApplicationChoices(fnum);
						await this.getAvailableChoices(fnum);
						this.loading = false;

						/*if(result.value === 'confirmed')
            {
              this.loading = true;
              await this.getChoicesConfiguration(fnum);
              await this.getApplicationChoices(fnum);
              await this.getAvailableChoices(fnum);
              this.loading = false;
            }
            else {
              const choiceIndex = this.choices.findIndex((choice) => choice.id === id);
              if (choiceIndex !== -1) {
                this.choices[choiceIndex].state = res.data.state;
                this.choices[choiceIndex].state_html = res.data.state_html;
              }
            }*/

						this.alertSuccess(
							'COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_SUCCESS_TITLE',
							'COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_SUCCESS_TEXT',
						);
					});
				}
			});
		},

		filterProperties(properties) {
			let filtered = {};
			for (const [key, property] of Object.entries(properties)) {
				if (!property.hidden) {
					filtered[key] = property;
				}
			}
			return filtered;
		},

		canMoreBeEdited(choice) {
			if (this.configuration.can_be_confirmed === 1) {
				return choice.state.value === 1;
			} else if (this.configuration.can_be_updated === 1) {
				return true;
			} else if (this.$props.fnum && this.configuration.crud) {
				return this.configuration.crud.u || this.configuration.crud.c;
			}

			return false;
		},

		canMoreBeViewed(choice) {
			if (this.configuration.form_id > 0) {
				if (this.configuration.can_be_confirmed === 1) {
					return choice.state.value === 1 || choice.state.value === 4;
				} else {
					return true;
				}
			}

			return false;
		},

		confirmDisabled(choice) {
			if (this.choices.length > 0 && this.configuration.form_id > 0) {
				// Each choices must have more informations filled
				if (!(choice.moreProperties && choice.moreProperties.id && choice.moreProperties.id.value > 0)) {
					return true;
				}
			}

			return this.loading || this.choices.length === 0;
		},
	},

	computed: {
		maxChoicesReached: function () {
			if (this.configuration.max) {
				return (
					this.choices.length >= this.configuration.max &&
					!(this.$props.fnum && this.configuration.crud && this.configuration.crud.c)
				);
			} else {
				return false;
			}
		},

		canBeCreate: function () {
			if (this.$props.fnum && this.configuration.crud && this.configuration.crud.c) {
				return true;
			} else {
				return (
					!this.$props.fnum && this.configuration.can_be_updated === 1 && this.configuration.can_be_confirmed !== 1
				);
			}
		},
		canBeUpdate: function () {
			if (this.$props.fnum && this.configuration.crud && this.configuration.crud.u) {
				return true;
			} else {
				return !this.$props.fnum && this.configuration.can_be_updated === 1;
			}
		},
		canBeDelete: function () {
			if (this.$props.fnum && this.configuration.crud && this.configuration.crud.d) {
				return true;
			} else {
				return (
					!this.$props.fnum && this.configuration.can_be_updated === 1 && this.configuration.can_be_confirmed !== 1
				);
			}
		},
		canBeConfirm: function () {
			if (this.$props.fnum && this.configuration.crud && this.configuration.crud.u) {
				return true;
			} else {
				return !this.$props.fnum && this.configuration.can_be_confirmed === 1;
			}
		},
		canBeSent: function () {
			return this.configuration.can_be_sent === 1;
		},
		fabrikFormUrl() {
			if (
				this.selectedChoice.moreProperties &&
				this.selectedChoice.moreProperties.id &&
				this.selectedChoice.moreProperties.id.value > 0
			) {
				return (
					'/index.php?option=com_fabrik&view=form&formid=' +
					this.configuration.form_id +
					'&tmpl=component&iframe=1&rowid=' +
					this.selectedChoice.moreProperties.id.value
				);
			} else {
				return (
					'/index.php?option=com_fabrik&view=form&formid=' +
					this.configuration.form_id +
					'&tmpl=component&iframe=1&jos_emundus_campaign_candidature_choices_more___parent_id=' +
					this.selectedChoice.id
				);
			}
		},
		submitDisabled() {
			if (this.choices.length > 0 && this.configuration.form_id > 0) {
				// Each choices must have more informations filled
				for (const choice of this.choices) {
					if (!(choice.moreProperties && choice.moreProperties.id && choice.moreProperties.id.value > 0)) {
						return true;
					}
				}
			}

			return this.loading || this.choices.length === 0;
		},
	},
};
</script>

<template>
	<div>
		<Info v-if="!loading && !configuration" text="COM_EMUNDUS_APPLICATION_CHOICES_NO_STEP_FOUND" class="tw-mb-4" />

		<modal
			v-if="openCampaignModal && canBeCreate"
			name="add-application-choice"
			:classes="'tw-max-h-[80vh] tw-overflow-y-auto tw-rounded-2xl tw-p-8 tw-shadow-modal'"
			transition="nice-modal-fade"
			width="60%"
			height="100%"
			:delay="100"
			:adaptive="true"
			:clickToClose="false"
		>
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
				<div>
					<h2>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_SELECT_CAMPAIGN') }}</h2>
					<p>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_SELECT_CAMPAIGN_DESC') }}</p>
				</div>
				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="openCampaignModal = false">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
			<CampaignsList
				:campaigns="campaigns"
				:parameters="parameters"
				:filters="filters"
				apply-text="COM_EMUNDUS_APPLICATION_CHOICES_SELECT_IT"
				@apply="applyCampaign"
				@search="applySearch"
				@update-filter="applyFilters"
			/>
		</modal>

		<modal
			v-if="configuration.form_id && openFabrikFormModal"
			name="add-application-choice-more"
			:classes="'tw-max-h-[80vh] tw-overflow-y-auto tw-rounded-2xl tw-p-8 tw-shadow-modal'"
			transition="nice-modal-fade"
			width="60%"
			height="100%"
			:delay="100"
			:adaptive="true"
			:clickToClose="false"
		>
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-end">
				<button
					class="tw-cursor-pointer tw-bg-transparent"
					@click.prevent="
						openFabrikFormModal = false;
						selectedChoice = 0;
					"
				>
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
			<iframe :src="fabrikFormUrl" class="tw-h-full tw-w-full"></iframe>
		</modal>

		<Back class="tw-mb-4" link="index.php" v-if="!$props.fnum" />
		<h1>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_TITLE') }}</h1>

		<div class="tw-mt-4" v-if="canBeCreate">
			<Button
				variant="primary"
				icon="add"
				id="add-application-choice"
				@click="openCampaignList"
				:disabled="maxChoicesReached"
			>
				{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_ADD') }}
			</Button>
			<p v-if="maxChoicesReached" class="tw-mt-2 tw-text-sm tw-text-red-600">
				{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_MAX_REACHED') }}
			</p>
		</div>

		<!-- Intro -->
		<div class="tw-mt-4">
			<p>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_DESCRIPTION').replace('%s', configuration.max) }}</p>
			<p v-if="configuration.can_be_ordering === 1">
				{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_DESCRIPTION_REORDER') }}
			</p>
		</div>

		<hr />

		<h2 v-if="configuration && configuration.max">
			{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_SUBTITLE') }} : {{ choices.length }}
			{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_SUBTITLE_SELECTED') }}
		</h2>

		<div class="tw-mt-4" v-if="choices.length > 0">
			<Card
				v-for="(choice, index) in choices"
				:key="choice.state.value"
				:legend-color="programColor(choice.campaign.program)"
				class="tw-mb-4"
				:class="{ '!tw-border-main-500 !tw-bg-main-50': choice.state.value === 3 }"
			>
				<template #legend>
					{{ choice.campaign.program.label }}
				</template>
				<template #legend_2>
					<div class="tw-flex tw-items-center tw-gap-4">
						<div v-html="choice.state_html" />
						<div class="tw-flex tw-items-center tw-gap-1" v-if="configuration.can_be_ordering === 1">
							<span
								v-if="canBeUpdate"
								class="material-symbols-outlined tw-cursor-pointer"
								@click="reorderChoices(choice.id, 'up')"
								:class="{ 'tw-text-neutral-400': index === 0 }"
							>
								arrow_upward
							</span>
							<span
								v-if="canBeUpdate"
								class="material-symbols-outlined tw-cursor-pointer"
								@click="reorderChoices(choice.id, 'down')"
								:class="{ 'tw-text-neutral-400': index === choices.length - 1 }"
							>
								arrow_downward
							</span>
							<span>{{ index + 1 }}</span>
						</div>
					</div>
				</template>
				<template #title class="tw-mt-4">
					{{ choice.campaign.label }}
				</template>
				<template #information_1>
					<div class="tw-flex tw-items-center tw-gap-1">
						<span class="material-symbols-outlined tw-text-neutral-600" aria-hidden="true">schedule</span>
						<p class="tw-text-neutral-600">
							{{ translate('COM_EMUNDUS_CAMPAIGNS_CLOSE_DATE') }} :
							{{ formatDateForCampaign(choice.campaign.end_date.date) }}
						</p>
					</div>
				</template>
				<template #description>
					<div
						v-html="
							choice.campaign.short_description
								? choice.campaign.short_description
								: truncateText(choice.campaign.description, 100)
						"
					></div>
				</template>
				<template #actions>
					<div class="tw-mb-2 tw-mt-2" v-if="canMoreBeViewed(choice)">
						<Info
							:bg-color="
								choice.moreProperties && choice.moreProperties.id && choice.moreProperties.id.value > 0
									? 'tw-bg-main-50'
									: 'tw-bg-blue-50'
							"
							:icon="
								choice.moreProperties && choice.moreProperties.id && choice.moreProperties.id.value > 0
									? 'check_circle'
									: 'info'
							"
							:icon-type="'material-symbols-outlined'"
							:icon-color="
								choice.moreProperties && choice.moreProperties.id && choice.moreProperties.id.value > 0
									? 'tw-text-green-500'
									: 'tw-text-blue-500'
							"
						>
							<template #content>
								<div v-if="choice.moreProperties && choice.moreProperties.id && choice.moreProperties.id.value > 0">
									<p>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_MORE_INFORMATIONS_COMPLETED') }}</p>
									<ul
										v-for="property in filterProperties(choice.moreProperties)"
										:key="property.id"
										class="tw-list-none tw-pl-0"
									>
										<li>
											<strong>{{ property.label }}</strong> : {{ property.formatted_value }}
										</li>
									</ul>
								</div>
								<p v-else>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_MORE_INFORMATIONS') }}</p>
								<Button
									v-if="canMoreBeEdited(choice)"
									variant="link"
									width="fit"
									class="tw-mt-1 tw-font-bold"
									@click="
										selectedChoice = choice;
										openFabrikFormModal = true;
									"
								>
									{{
										choice.moreProperties && choice.moreProperties.id && choice.moreProperties.id.value > 0
											? translate('COM_EMUNDUS_APPLICATION_CHOICES_MORE_INFORMATIONS_EDIT')
											: translate('COM_EMUNDUS_APPLICATION_CHOICES_MORE_INFORMATIONS_COMPLETE')
									}}
								</Button>
							</template>
						</Info>
					</div>
					<div class="tw-flex tw-justify-end tw-gap-2">
						<Button
							v-if="($props.fnum && canBeUpdate) || ($props.fnum && canBeCreate && choice.state.value === 3)"
							variant="primary"
							width="fit"
							@click="updateStatus(choice.id, choice.state)"
						>
							{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS') }}
						</Button>
						<Button variant="cancel" width="fit" v-if="canBeDelete" @click="removeChoice(choice.id)">
							{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_CANCEL') }}
						</Button>
						<Button
							variant="cancel"
							width="fit"
							v-if="canBeConfirm && choice.state.value === 1 && !$props.fnum"
							@click="refuseChoice(choice)"
						>
							{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_REFUSE') }}
						</Button>
						<Button
							variant="primary"
							width="fit"
							v-if="canBeConfirm && choice.state.value === 1 && !$props.fnum"
							@click="confirmChoice(choice)"
							:disabled="confirmDisabled(choice)"
						>
							{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_CONFIRM') }}
						</Button>
					</div>
				</template>
			</Card>
		</div>
		<div class="tw-mt-4" v-else>
			<h3>{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_EMPTY') }}</h3>
		</div>

		<hr />

		<div class="tw-mb-4 tw-flex tw-justify-end" v-if="!$props.fnum">
			<Button
				variant="primary"
				width="fit"
				v-if="canBeUpdate && !canBeConfirm && canBeSent"
				@click="sendChoiceStep"
				:disabled="submitDisabled"
			>
				{{ translate('COM_EMUNDUS_APPLICATION_CHOICES_SEND') }}
			</Button>
		</div>

		<div v-if="loading" class="em-page-loader"></div>
	</div>
</template>

<style scoped></style>
