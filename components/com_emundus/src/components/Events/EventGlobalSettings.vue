<script>
/* Components */
import Parameter from '@/components/Utils/Parameter.vue';
import LocationPopup from '@/components/Events/Popup/LocationPopup.vue';
import ColorPicker from '@/components/ColorPicker.vue';
import Info from '@/components/Utils/Info.vue';

/* Services */
import settingsService from '@/services/settings';
import eventsService from '@/services/events';

/* Stores */
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'EventGlobalSettings',
	components: { ColorPicker, Info, LocationPopup, Parameter },
	emits: ['reload-event'],
	props: {
		event: Object,
	},
	data() {
		return {
			loading: true,
			openedLocationPopup: false,
			teamsEnabled: false,
			teamsPublished: false,

			settingsLink: '',
			eventsNames: [],

			eventColor: '',
			fields: [
				{
					param: 'name',
					type: 'text',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_NAME',
					helptext: '',
					displayed: true,
				},
				{
					param: 'location',
					type: 'select',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_LOCATION',
					helptext: '',
					displayed: true,
					options: [],
					reload: 0,
				},
				{
					param: 'is_conference_link',
					type: 'toggle',
					value: 0,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_IS_CONFERENCE_LINK',
					iconLabel: 'videocam',
					displayed: true,
					hideLabel: true,
					optional: true,
				},
				{
					param: 'conference_engine',
					type: 'radiobutton',
					value: null,
					default: 'link',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE',
					displayed: false,
					displayedOn: 'is_conference_link',
					displayedOnValue: 1,
					hideRadio: true,
					optional: true,
					options: [
						{
							value: 'link',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_LINK',
						},
						{
							value: 'teams',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_TEAMS',
							img: 'teams.svg',
							altImg: 'Microsoft Teams',
						},
					],
				},
				{
					param: 'link',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_LINK',
					helptext: '',
					displayed: false,
					displayedOn: 'conference_engine',
					displayedOnValue: 'link',
				},
				{
					param: 'generate_link_by',
					type: 'select',
					placeholder: '',
					default: 1,
					value: 1,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_GENERATE_LINK_BY',
					helptext: '',
					displayed: false,
					displayedOn: 'conference_engine',
					displayedOnValue: 'teams',
					options: [
						{
							value: 1,
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_GENERATE_LINK_BY_RESERVATION',
						},
						{
							value: 2,
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_GENERATE_LINK_BY_SLOT',
						},
					],
				},
				{
					param: 'teams_subject',
					type: 'text',
					placeholder: '',
					default: 'Entretien de [APPLICANT_NAME]',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_TEAMS_SUBJECT',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_TEAMS_SUBJECT_HELPTEXT',
					helpTextType: 'icon',
					displayed: false,
					displayedOn: 'conference_engine',
					displayedOnValue: 'teams',
				},
				{
					param: 'manager',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: false,
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
					value: 0,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_MANAGER',
					placeholder: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_MANAGER_PLACEHOLDER',
					displayed: true,
					optional: true,
				},
				{
					param: 'available_for',
					type: 'radiobutton',
					value: 1,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_AVAILABLE_FOR',
					displayed: true,
					options: [
						{
							value: 1,
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_AVAILABLE_FOR_CAMPAIGNS',
						},
						{
							value: 2,
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_AVAILABLE_FOR_PROGRAMS',
						},
					],
				},
				{
					param: 'campaigns',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getavailablecampaigns',
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
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CAMPAIGNS',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CAMPAIGNS_HELPTEXT',
					displayed: false,
					displayedOn: 'available_for',
					displayedOnValue: 1,
					optional: true,
				},
				{
					param: 'programs',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getavailableprograms',
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
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_PROGRAMS',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_PROGRAMS_HELPTEXT',
					displayed: false,
					displayedOn: 'available_for',
					displayedOnValue: 2,
					optional: true,
				},
			],
		};
	},
	created: function () {
		// fetch locations
		this.getLocations();

		// fetch events names to check for duplicates
		eventsService.getEventsNames().then((response) => {
			if (response.status) {
				this.eventsNames = response.data;
			}
		});

		// check every 8 seconds if teams integration is enabled
		this.checkTeamsIntegration();
		setInterval(() => {
			this.checkTeamsIntegration();
		}, 8000);
		this.getSettingsLink();

		if (this.event) {
			this.fields.forEach((field) => {
				if (this.event[field.param]) {
					field.value = this.event[field.param];
				}
			});

			if (this.event.color) {
				this.eventColor = this.event.color;
			}
		}
	},
	methods: {
		// Services
		getLocations(location_id = 0) {
			eventsService.getLocations().then((response) => {
				let options = [
					{
						value: 0,
						label: this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_LOCATION_SELECT'),
					},
				];

				if (response.status) {
					Array.prototype.push.apply(options, response.data);
				}

				this.fields.find((field) => field.param === 'location').options = options;
				if (location_id) {
					this.fields.find((field) => field.param === 'location').value = location_id;
					this.fields.find((field) => field.param === 'location').reload += 1;
				}

				this.loading = false;
			});
		},

		checkTeamsIntegration() {
			settingsService.getApp(0, 'teams').then((response) => {
				if (response.status) {
					this.teamsEnabled = response.data.enabled && response.data.config !== '{}';
					this.teamsPublished = response.data.published;

					this.updateConferenceEngineOptions();
				}
			});
		},

		updateConferenceEngineOptions() {
			const conferenceEngineField = this.fields.find((field) => field.param === 'conference_engine');

			if (this.teamsPublished) {
				conferenceEngineField.options = [
					{
						value: 'link',
						label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_LINK',
					},
					{
						value: 'teams',
						label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_TEAMS',
						img: 'teams.svg',
						altImg: 'Microsoft Teams',
					},
				];
			} else {
				conferenceEngineField.options = [
					{
						value: 'link',
						label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_LINK',
					},
				];
			}
		},

		getSettingsLink() {
			settingsService
				.getSEFLink('index.php?option=com_emundus&view=settings', useGlobalStore().getCurrentLang)
				.then((response) => {
					if (response.status) {
						this.settingsLink = '/' + response.data;
					}
				});
		},

		// Create
		createEvent() {
			let event = {};

			// Validate all fields
			const eventValidationFailed = this.fields.some((field) => {
				if (field.displayed) {
					let ref_name = 'event_' + field.param;

					// If name check if it is unique
					if (field.param === 'name') {
						if (this.eventsNames.includes(field.value)) {
							Swal.fire({
								icon: 'error',
								title: 'Oops...',
								text: Joomla.Text._('COM_EMUNDUS_ONBOARD_ADD_EVENT_NAME_EXISTS'),
								customClass: {
									title: 'em-swal-title',
									confirmButton: 'em-swal-confirm-button',
									actions: 'em-swal-single-action',
								},
							});
							return true;
						}
					}

					if (!this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							event[field.param] = [];
							field.value.forEach((element) => {
								event[field.param].push(element.value);
							});
						} else {
							event[field.param] = field.value.value;
						}
					} else {
						event[field.param] = field.value;
					}

					return false;
				}
			});

			if (eventValidationFailed) return;

			event['color'] = this.eventColor;

			eventsService.createEvent(event).then((response) => {
				if (response.status === true) {
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_CREATED'),
						showConfirmButton: false,
						allowOutsideClick: false,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
						timer: 1500,
					}).then(() => {
						this.$emit('reload-event', response.data, 2);

						// Update url to add ?event=ID
						const urlParams = new URLSearchParams(window.location.search);
						urlParams.set('event', response.data);
						window.history.replaceState({}, '', `${window.location.pathname}?${urlParams}`);
					});
				} else {
					// Handle error
					Swal.fire({
						icon: 'error',
						title: 'Oops...',
						text: response.message,
					});
				}
			});
		},

		// Edit
		editEvent(event_id) {
			let event_edited = {};

			// Validate all fields
			const eventValidationFailed = this.fields.some((field) => {
				if (field.displayed) {
					let ref_name = 'event_' + field.param;

					if (this.$refs[ref_name] && !this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							event_edited[field.param] = [];
							field.value.forEach((element) => {
								event_edited[field.param].push(element.value);
							});
						} else {
							event_edited[field.param] = field.value ? field.value.value : null;
						}
					} else {
						event_edited[field.param] = field.value;
					}

					return false;
				}
			});
			if (eventValidationFailed) return;

			event_edited['id'] = event_id;
			event_edited['color'] = this.eventColor;

			eventsService.editEvent(event_edited).then((response) => {
				if (response.status === true) {
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SAVED'),
						showConfirmButton: true,
						allowOutsideClick: false,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
						timer: 1500,
					}).then(() => {
						this.$emit('reload-event', response.data, 2);
					});
				} else {
					// Handle error
					Swal.fire({
						icon: 'error',
						title: 'Oops...',
						text: response.message,
					});
				}
			});
		},
		// Hooks
		checkConditional(parameter, oldValue, value) {
			// Find all fields that are displayed based on the current field
			let fields = this.fields.filter((field) => field.displayedOn === parameter.param);

			// Check if the current field is displayed based on the value
			for (let field of fields) {
				field.displayed = field.displayedOnValue == value;
				if (!field.displayed) {
					if (field.default) {
						field.value = field.default;
					} else {
						field.value = '';
					}
					this.checkConditional(field, field.value, '');
				}
			}
		},

		locationPopupClosed(location_id) {
			this.openedLocationPopup = false;
			this.getLocations(location_id);
		},
	},
	computed: {
		disabledSubmit: function () {
			return this.fields.some((field) => {
				if (!field.optional && field.displayed) {
					return field.value === '' || field.value === 0;
				} else {
					return false;
				}
			});
		},

		teamsDisabledText: function () {
			let text = this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_TEAMS_DISABLED');
			text = text.replace('{{settingsLink}}', this.settingsLink + '#integration');

			return text;
		},
	},
	watch: {},
};
</script>

<template>
	<div>
		<LocationPopup v-if="openedLocationPopup" :location_id="fields[1].value" @close="locationPopupClosed" />

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
			<div
				v-for="field in fields"
				v-show="field.displayed"
				:key="field.param"
				:class="{
					'tw-flex tw-w-1/2 tw-items-end tw-justify-between tw-gap-2': field.param === 'name',
					'tw-w-full': field.param !== 'name',
				}"
			>
				<Parameter
					v-if="field.displayed"
					:class="{ 'tw-w-full': field.param === 'name' }"
					:ref="'event_' + field.param"
					:key="field.reload ? field.reload : field.param"
					:parameter-object="field"
					:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					@valueUpdated="checkConditional"
				/>

				<color-picker
					v-if="field.param === 'name'"
					v-model="eventColor"
					:swatches="'dark'"
					:row-length="8"
					:id="'status_swatches'"
					:random="true"
					style="top: -8px"
				/>

				<Info
					v-if="field.param === 'conference_engine' && field.value === 'teams' && !teamsEnabled"
					:parameter-object="field"
					:text="teamsDisabledText"
					:icon="'warning'"
					:bg-color="'tw-bg-orange-100'"
					:icon-type="'material-symbols-outlined'"
					:icon-color="'tw-text-orange-600'"
					:class="'tw-mt-2'"
				/>

				<button
					v-if="field.param === 'location' && field.value === 0"
					type="button"
					class="tw-mt-2 tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-text-blue-500"
					@click="openedLocationPopup = true"
				>
					<span class="material-symbols-outlined !tw-text-blue-500">add</span>
					<span class="tw-underline">{{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_ADD_LOCATION') }}</span>
				</button>
				<button
					v-else-if="field.param === 'location'"
					type="button"
					class="tw-mt-2 tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-text-blue-500"
					@click="openedLocationPopup = true"
				>
					<span class="tw-underline">{{ translate('COM_EMUNDUS_ONBOARD_EDIT_LOCATION') }}</span>
				</button>
			</div>
		</div>

		<div class="tw-mt-7 tw-flex tw-justify-end">
			<button
				type="button"
				:disabled="disabledSubmit"
				class="tw-btn-primary tw-cursor-pointer"
				@click="
					this.$props.event && Object.keys(this.$props.event).length > 0
						? editEvent(this.$props.event['id'])
						: createEvent()
				"
			>
				{{
					this.$props.event && Object.keys(this.$props.event).length > 0
						? translate('COM_EMUNDUS_ONBOARD_EDIT_EVENT_GLOBAL_CREATE')
						: translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CREATE')
				}}
			</button>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<style scoped></style>
