<script>
import emailService from '@/services/email.js';
import settingsService from '@/services/settings.js';
import smsService from '@/services/sms.js';
import programService from '@/services/programme.js';
import groupsService from '@/services/groups.js';
import fileService from '@/services/file.js';

import Back from '@/components/Utils/Back.vue';
import Multiselect from 'vue-multiselect';
import ToggleInput from '@/components/Utils/ToggleInput.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import Info from '@/components/Utils/Info.vue';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'TriggersEdit',
	props: {
		triggerId: {
			type: Number,
			required: true,
		},
		smsActivated: {
			type: Boolean,
			default: false,
		},
		defaultProgramId: {
			type: Number,
			default: 0,
		},
	},
	components: {
		Parameter,
		ToggleInput,
		Back,
		Multiselect,
		Info,
	},
	data() {
		return {
			triggerData: {},
			loading: true,

			statusOptions: [],
			emailsOptions: [],
			smsOptions: [],
			programOptions: [],
			mandatoryFields: ['status', 'program_ids', ['email_id', 'sms_id']],
			fieldsToDisplayError: [],
			profilesOptions: [],
			groupsOptions: [],

			userField: {
				param: 'user_ids',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: true,
					taggable: false,
					searchable: true,
					internalSearch: false,
					asyncRoute: 'getavailablemanagers',
					optionsPlaceholder: '',
					selectLabel: '',
					selectGroupLabel: '',
					selectedLabel: '',
					deselectedLabel: '',
					deselectGroupLabel: '',
					noOptionsText: '',
					noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
					tagValidations: [],
					options: [],
					optionsLimit: 30,
					label: 'name',
					trackBy: 'value',
				},
				value: [],
				label: 'COM_EMUNDUS_ONBOARD_TRIGGER_EDIT_SEND_TO_USERS',
				placeholder: 'COM_EMUNDUS_ONBOARD_TRIGGER_EDIT_SEND_TO_USERS_PLACEHOLDER',
				displayed: true,
				optional: true,
			},
			backUrl: '',
		};
	},
	created() {
		this.getStatusOptions();
		this.getEmailsOptions();
		this.getSmsOptions();
		this.getGroupsOptions();
		this.getProfilesOptions();
		this.getProgramOptions().then(() => {
			this.loadTriggerData();
		});

		if (document.referrer) {
			const url = new URL(document.referrer);

			let tmpUrl = url.pathname + url.search + url.hash;
			tmpUrl = tmpUrl.substring(1); // remove first slash
			this.backUrl = tmpUrl;
		} else {
			this.backUrl = '/index.php?option=com_emundus&view=emails&layout=messagetriggers';
		}
	},
	methods: {
		loadTriggerData() {
			this.loading = true;
			if (this.triggerId > 0) {
				emailService.getEmailTriggerById(this.triggerId).then((response) => {
					if (response.status) {
						let tmpTrigger = response.data;
						tmpTrigger.program_ids = this.formattedProgramOptions.filter((program) => {
							return tmpTrigger.program_ids.includes(program.id);
						});

						tmpTrigger.profile_ids = this.profilesOptions.filter((profile) => {
							return tmpTrigger.profile_ids.includes(profile.id);
						});

						tmpTrigger.group_ids = this.groupsOptions.filter((group) => {
							return tmpTrigger.group_ids.includes(group.id);
						});

						this.userField.value = tmpTrigger.user_ids;

						this.triggerData = response.data;
						this.loading = false;
					} else {
						this.loading = false;
					}
				});
			} else {
				this.triggerData = {
					status: 0,
					program_ids: [],
					email_id: 0,
					sms_id: 0,
					to_current_user: 0,
					to_applicant: 0,
					group_ids: [],
					profile_ids: [],
					user_ids: [],
					all_program: 1,
				};

				if (this.defaultProgramId > 0) {
					this.triggerData.all_program = 0;
					this.triggerData.program_ids = this.formattedProgramOptions.filter((program) => {
						return program.id === this.defaultProgramId;
					});
				}

				this.loading = false;
			}
		},
		getStatusOptions() {
			settingsService.getStatus().then((response) => {
				if (response.status) {
					this.statusOptions = response.data.sort((a, b) => {
						return a.ordering - b.ordering;
					});
				}
			});
		},
		getEmailsOptions() {
			emailService.getEmails().then((response) => {
				if (response.status) {
					this.emailsOptions = response.data.datas;
				}
			});
		},
		getSmsOptions() {
			if (this.smsActivated) {
				smsService.getSmsTemplates().then((response) => {
					if (response.status) {
						this.smsOptions = response.data.datas.map((sms) => {
							return {
								id: sms.id,
								label: sms.label.fr,
							};
						});
					}
				});
			}
		},
		getProfilesOptions() {
			fileService.getProfiles().then((response) => {
				if (response.status) {
					this.profilesOptions = response.data
						.filter((profile) => {
							return profile.published != 1;
						})
						.map((profile) => {
							return {
								id: profile.id,
								label: profile.label,
							};
						});

					if (this.triggerData.profile_ids) {
						this.triggerData.profile_ids = this.profilesOptions.filter((profile) => {
							return this.triggerData.profile_ids.includes(profile);
						});
					}
				}
			});
		},
		getGroupsOptions() {
			groupsService.getGroups().then((response) => {
				if (response.status) {
					this.groupsOptions = response.data;

					if (this.triggerData.group_ids) {
						this.triggerData.group_ids = this.groupsOptions.filter((group) => {
							return this.triggerData.group_ids.includes(group);
						});
					}
				}
			});
		},
		async getProgramOptions() {
			return await programService.getAllPrograms().then((response) => {
				if (response.status) {
					this.programOptions = response.data.datas;
				}
			});
		},
		areMandatoryFieldsFilled() {
			let filled = true;
			this.fieldsToDisplayError = [];

			for (const field of this.mandatoryFields) {
				if (Array.isArray(field)) {
					const atLeastOneFilled = field.some((entry) => {
						if (
							this.triggerData[entry] === undefined ||
							this.triggerData[entry] === null ||
							this.triggerData[entry] < 1
						) {
							this.fieldsToDisplayError.push(entry);
							return false;
						}

						return true;
					});

					if (!atLeastOneFilled) {
						filled = false;
					}
				} else {
					switch (field) {
						case 'status':
							// sattus can be 0
							if (this.triggerData.status === undefined || this.triggerData.status === null) {
								this.fieldsToDisplayError.push(field);
								filled = false;
							}
							break;
						case 'program_ids':
							if (this.triggerData.program_ids.length === 0 && this.triggerData.all_program == 0) {
								this.fieldsToDisplayError.push(field);
								filled = false;
							}
							break;
						default:
							if (!this.triggerData[field]) {
								this.fieldsToDisplayError.push(field);
								filled = false;
							}
					}
				}
			}

			return filled;
		},
		saveTrigger() {
			if (this.loading) {
				return;
			}

			if (this.areMandatoryFieldsFilled()) {
				this.triggerData.user_ids = this.userField.value.map((user) => {
					return user.value;
				});

				this.fieldsToDisplayError = [];

				emailService.saveTrigger(this.triggerData).then((response) => {
					if (response.status) {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_TRIGGER_EDIT_SAVE_SUCCESS'),
							icon: 'success',
							showCancelButton: false,
							showConfirmButton: false,
							delay: 3000,
						});

						settingsService.redirectJRoute(this.backUrl, useGlobalStore().getCurrentLang);
					} else {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_TRIGGER_EDIT_SAVE_ERROR'),
							text: this.translate(response.msg),
							icon: 'error',
							showCancelButton: false,
							showConfirmButton: false,
							delay: 3000,
						});
					}
				});
			}
		},
	},
	computed: {
		formattedProgramOptions() {
			const options = this.programOptions.map((program) => {
				return {
					id: program.id,
					label: program.label.fr,
				};
			});

			// sort it by label
			return options.sort((a, b) => {
				return a.label.localeCompare(b.label);
			});
		},
	},
};
</script>

<template>
	<div id="message-triggers-edit" class="tw-mb-6 tw-flex tw-flex-col tw-rounded tw-bg-white tw-p-4 tw-shadow">
		<Back :link="backUrl" :class="'tw-mb-4'" />

		<h1>{{ this.triggerId > 0 ? translate('COM_EMUNDUS_TRIGGER_EDIT') : translate('COM_EMUNDUS_TRIGGER_ADD') }}</h1>

		<div id="trigger" class="tw-my-4 tw-flex tw-flex-col">
			<section id="when-to-send" class="tw-pt-4">
				<h2 class="tw-mb-4">{{ translate('COM_EMUNDUS_TRIGGER_EDIT_WHEN_TO_SEND') }}</h2>

				<div class="tw-flex tw-flex-col tw-gap-4">
					<div class="tw-flex tw-flex-col" :class="{ error: fieldsToDisplayError.includes('status') }">
						<label class="tw-flex tw-items-end tw-font-semibold">{{
							translate('COM_EMUNDUS_TRIGGER_EDIT_STATUS')
						}}</label>
						<select v-model="triggerData.status">
							<option v-for="status in statusOptions" :key="status.step" :value="status.step">
								{{ status.value }}
							</option>
						</select>

						<span class="error-message"> {{ translate('COM_EMUNDUS_TRIGGER_EDIT_STATUS_ERROR_MESSAGE') }} </span>
					</div>

					<div id="all_program" class="tw-flex tw-items-center tw-gap-2">
						<toggle-input
							id="all_program"
							:value="triggerData.all_program"
							@update:value="($event) => (triggerData.all_program = $event ? 1 : 0)"
						></toggle-input>
						<label class="tw-mb-0 tw-flex tw-items-end tw-font-semibold" for="all_program">{{
							translate('COM_EMUNDUS_TRIGGER_EDIT_ALL_PROGRAM')
						}}</label>
					</div>

					<transition v-show="triggerData.all_program == 0" name="fade">
						<div class="tw-flex tw-flex-col" :class="{ error: fieldsToDisplayError.includes('program_ids') }">
							<label class="tw-flex tw-items-end tw-font-semibold">{{
								translate('COM_EMUNDUS_TRIGGER_EDIT_PROGRAMMES')
							}}</label>
							<Multiselect
								v-model="triggerData.program_ids"
								label="label"
								track-by="id"
								:options="formattedProgramOptions"
								:multiple="true"
							/>

							<span class="error-message"> {{ translate('COM_EMUNDUS_TRIGGER_EDIT_PROGRAMMES_ERROR_MESSAGE') }} </span>
						</div>
					</transition>
				</div>
			</section>

			<section
				id="message-to-send"
				class="tw-mt-4 tw-flex tw-flex-col tw-pt-4"
				:class="{ error: fieldsToDisplayError.includes('email_id') || fieldsToDisplayError.includes('sms_id') }"
			>
				<h2 class="tw-mb-4">{{ translate('COM_EMUNDUS_TRIGGER_EDIT_MODELS_SELECTION') }}</h2>

				<div class="tw-flex tw-flex-col">
					<label class="tw-flex tw-items-end tw-font-semibold" for="model_id">{{
						translate('COM_EMUNDUS_TRIGGER_EDIT_MODELS_EMAIL_SELECTION')
					}}</label>
					<select id="email_id" v-model="triggerData.email_id">
						<option value="0">
							{{ translate('COM_EMUNDUS_TRIGGER_EDIT_MODELS_EMAIL_SELECTION_DEFAULT') }}
						</option>
						<option v-for="email in emailsOptions" :key="email.id" :value="email.id">
							{{ email.subject }}
						</option>
					</select>
				</div>

				<div
					class="tw-flex tw-flex-col"
					v-if="smsActivated"
					:class="{ error: fieldsToDisplayError.includes('sms_id') }"
				>
					<label class="tw-flex tw-items-end tw-font-semibold" for="sms_id">{{
						translate('COM_EMUNDUS_TRIGGER_EDIT_MODELS_SMS_SELECTION')
					}}</label>
					<select id="sms_id" v-model="triggerData.sms_id">
						<option value="0">
							{{ translate('COM_EMUNDUS_TRIGGER_EDIT_MODELS_SMS_SELECTION_DEFAULT') }}
						</option>
						<option v-for="sms in smsOptions" :key="sms.id" :value="sms.id">
							{{ sms.label }}
						</option>
					</select>
				</div>
				<span class="error-message"> {{ translate('COM_EMUNDUS_TRIGGER_EDIT_MODELS_SELECTION_ERROR_MESSAGE') }} </span>
			</section>

			<section id="send-to-applicant" class="tw-mt-4 tw-flex tw-flex-col tw-pt-4">
				<h2 class="tw-mb-4">{{ translate('COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_APPLICANT') }}</h2>

				<div class="tw-flex tw-flex-col tw-gap-4">
					<div id="on_applicant_action" class="tw-flex tw-items-center tw-gap-2">
						<toggle-input
							id="on_applicant_action"
							:value="triggerData.to_current_user"
							@update:value="($event) => (triggerData.to_current_user = $event ? 1 : 0)"
						></toggle-input>
						<label class="tw-mb-0 tw-flex tw-items-end tw-font-semibold" for="on_applicant_action">{{
							translate('COM_EMUNDUS_TRIGGER_EDIT_ON_APPLICANT_ACTION')
						}}</label>
					</div>

					<div id="on_manager_action" class="tw-flex tw-items-center tw-gap-2">
						<toggle-input
							id="on_manager_action"
							:value="triggerData.to_applicant"
							@update:value="($event) => (triggerData.to_applicant = $event ? 1 : 0)"
						></toggle-input>
						<label class="tw-mb-0 tw-flex tw-items-end tw-font-semibold" for="on_manager_action">{{
							translate('COM_EMUNDUS_TRIGGER_EDIT_ON_MANAGER_ACTION')
						}}</label>
					</div>
				</div>
			</section>

			<section id="send-to-others" class="tw-mt-4 tw-flex tw-flex-col tw-pt-4">
				<h2 class="tw-mb-4">{{ translate('COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_MANAGERS') }}</h2>
				<Info text="COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_MANAGERS_INTRO" />

				<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-4">
					<div class="tw-flex tw-flex-col" v-if="profilesOptions.length > 0">
						<label class="tw-flex tw-items-end tw-font-semibold">{{
							translate('COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_ROLE')
						}}</label>
						<Multiselect
							v-model="triggerData.profile_ids"
							label="label"
							track-by="id"
							:options="profilesOptions"
							:multiple="true"
							:placeholder="translate('COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_ROLE_PLACEHOLDER')"
							:select-label="translate('PRESS_ENTER_TO_SELECT')"
						/>
					</div>

					<div class="tw-flex tw-flex-col" v-if="groupsOptions.length > 0">
						<label class="tw-flex tw-items-end tw-font-semibold">{{
							translate('COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_GROUPS')
						}}</label>
						<Multiselect
							v-model="triggerData.group_ids"
							label="label"
							track-by="id"
							:options="groupsOptions"
							:multiple="true"
							:placeholder="translate('COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_GROUPS_PLACEHOLDER')"
							:select-label="translate('PRESS_ENTER_TO_SELECT')"
						/>
					</div>

					<Parameter
						v-if="!loading"
						:parameter-object="userField"
						:multiselect-options="userField.multiselectOptions ? userField.multiselectOptions : null"
					/>
				</div>
			</section>
		</div>
		<div id="actions" class="tw-flex tw-flex-row tw-justify-end">
			<button id="save" class="tw-btn-primary" @click="saveTrigger">
				{{ translate('COM_EMUNDUS_TRIGGER_EDIT_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style>
.error-message {
	color: var(--red-500);
	font-size: 0.875rem;
	margin-top: 0.25rem;
	display: none;
}

.error {
	select,
	input,
	.multiselect,
	.multiselect__tags {
		border-color: var(--red-500) !important;
	}

	.error-message {
		display: block;
	}
}
</style>
