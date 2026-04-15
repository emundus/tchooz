<template>
	<div class="tw-flex tw-flex-wrap tw-justify-start">
		<div v-if="!loading" class="tw-w-full">
			<div class="tw-w-full">
				<Info
					class="tw-mb-4 tw-w-full"
					:title="translate('COM_EMUNDUS_CUSTOM_REFERENCE_HELP_TITLE')"
					:accordion="true"
					:text="translate('COM_EMUNDUS_CUSTOM_REFERENCE_HELP_TEXT')"
				></Info>

				<ParameterForm v-if="!loadForm" id="reference-parameters-form" :groups="formGroups" />

				<div id="mapping-rows" class="tw-mt-5 tw-w-full">
					<h4>
						{{ translate('COM_EMUNDUS_CUSTOM_REFERENCE_FORMAT') }}
					</h4>
					<p>
						{{ translate('COM_EMUNDUS_CUSTOM_REFERENCE_FORMAT_INTRO') }}
					</p>

					<div class="tw-mt-3 tw-rounded-coordinator tw-border tw-border-neutral-300 tw-p-4">
						<table class="tw-border-none">
							<!-- Rows -->
							<tbody>
								<draggable handle=".handle" v-model="referenceFormat.blocks" :sort="true">
									<MappingRow
										v-for="(block, index) in referenceFormat.blocks"
										:key="block.id"
										:row="block"
										:data-resolvers="dataResolvers"
										:display-target="false"
										:can-reorder="true"
										@removeRow="removeBlock"
										@rowTransformations="onBlockTransformationsUpdate"
									>
									</MappingRow>
								</draggable>
							</tbody>
						</table>

						<div class="tw-mt-4 tw-flex tw-w-full tw-justify-end">
							<Button variant="dashed" width="full" @click="addMappingRow">
								{{ translate('COM_EMUNDUS_CUSTOM_REFERENCE_FORMAT_ADD_BLOCK') }}
							</Button>
						</div>
					</div>
				</div>

				<Info
					class="tw-mt-4 tw-w-full"
					:title="translate('COM_EMUNDUS_CUSTOM_REFERENCE_PREVIEW_HELP_TITLE')"
					:text="translate('COM_EMUNDUS_CUSTOM_REFERENCE_PREVIEW_HELP_TEXT')"
				></Info>
			</div>

			<div class="tw-mt-4 tw-flex tw-w-full tw-justify-end">
				<Button @click="save">
					{{ translate('COM_EMUNDUS_SAVE') }}
				</Button>
			</div>
		</div>

		<Loader v-else />
	</div>
</template>

<script>
/* COMPONENTS */
import { VueDraggableNext } from 'vue-draggable-next';

/* SERVICES */
import mixin from '@/mixins/mixin.js';
import errors from '@/mixins/errors.js';
import alerts from '@/mixins/alerts.js';

import { useGlobalStore } from '@/stores/global';
import Info from '@/components/Utils/Info.vue';
import MappingRow from '@/components/Mapping/MappingRow.vue';
import { useMappingStore } from '@/stores/mapping.js';
import mappingService from '@/services/mapping.js';
import settingsService from '@/services/settings.js';
import Button from '@/components/Atoms/Button.vue';
import Loader from '@/components/Atoms/Loader.vue';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import { useSettingsStore } from '@/stores/settings.js';

export default {
	name: 'CustomReference',

	components: {
		ParameterForm,
		Loader,
		Button,
		MappingRow,
		Info,
		draggable: VueDraggableNext,
	},

	props: {},

	mixins: [mixin, errors, alerts],

	data() {
		return {
			loading: true,
			loadForm: true,

			referenceFormat: {
				id: 'custom_reference',
				params: {},
				blocks: [],
			},
			dataResolvers: [],
			fields: [],
			formGroups: [
				{
					id: 'default-group',
					title: '',
					description: '',
					helpTextType: 'above',
					parameters: [
						{
							param: 'triggering_status',
							type: 'multiselect',
							reload: 0,
							multiselectOptions: {
								noOptions: false,
								multiple: false,
								taggable: false,
								searchable: true,
								internalSearch: true,
								asyncRoute: '',
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
								label: 'value',
								trackBy: 'step',
							},
							placeholder: '',
							value: '',
							label: 'COM_EMUNDUS_CUSTOM_REFERENCE_STATUS_TRIGGER_LABEL',
							helptext: '',
							displayed: true,
							optional: true,
						},
						{
							param: 'show_to_applicant',
							type: 'toggle',
							placeholder: '',
							value: 1,
							label: 'COM_EMUNDUS_CUSTOM_REFERENCE_SET_APPLICANT_VISIBLE_LABEL',
							helptext: '',
							displayed: true,
							hideLabel: true,
						},
						{
							param: 'show_in_files',
							type: 'toggle',
							placeholder: '',
							value: 1,
							label: 'COM_EMUNDUS_CUSTOM_REFERENCE_SET_FILES_VISIBLE_LABEL',
							helptext: '',
							displayed: true,
							hideLabel: true,
						},
						{
							param: 'separator',
							type: 'select',
							value: '-',
							label: 'COM_EMUNDUS_CUSTOM_REFERENCE_SEPARATOR_LABEL',
							helptext: '',
							options: [
								{ value: '', label: this.translate('COM_EMUNDUS_CUSTOM_REFERENCE_SEPARATOR_OPTION_NONE_LABEL') },
								{ value: '-', label: this.translate('COM_EMUNDUS_CUSTOM_REFERENCE_SEPARATOR_OPTION_DASH_LABEL') },
								{ value: '_', label: this.translate('COM_EMUNDUS_CUSTOM_REFERENCE_SEPARATOR_OPTION_UNDERSCORE_LABEL') },
							],
							displayed: true,
						},
						{
							param: 'sequence',
							type: 'toggle',
							placeholder: '',
							value: 0,
							label: 'COM_EMUNDUS_CUSTOM_REFERENCE_SEQUENCE_LABEL',
							helptext: '',
							displayed: true,
							hideLabel: true,
						},
						{
							param: 'sequence_position',
							type: 'select',
							value: 'end',
							label: 'COM_EMUNDUS_CUSTOM_REFERENCE_SEQUENCE_POSITION_LABEL',
							helptext: '',
							options: [
								{
									value: 'start',
									label: this.translate('COM_EMUNDUS_CUSTOM_REFERENCE_SEQUENCE_POSITION_OPTION_START_LABEL'),
								},
								{
									value: 'end',
									label: this.translate('COM_EMUNDUS_CUSTOM_REFERENCE_SEQUENCE_POSITION_OPTION_END_LABEL'),
								},
							],
							displayed: false,
							displayRules: [
								{
									field: 'sequence',
									value: 1,
								},
							],
						},
						{
							param: 'sequence_reset_type',
							type: 'select',
							value: 'yearly',
							label: 'COM_EMUNDUS_CUSTOM_REFERENCE_SEQUENCE_RESET_LABEL',
							helptext: '',
							options: [
								{ value: 'never', label: this.translate('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_NEVER') },
								{
									value: 'yearly',
									label: this.translate('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_YEARLY'),
								},
								{
									value: 'campaign',
									label: this.translate('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_CAMPAIGN'),
								},
								{
									value: 'program',
									label: this.translate('COM_TCHOOZ_MAPPING_TRANSFORMER_SEQUENTIAL_RESET_TYPE_PROGRAM'),
								},
							],
							displayed: false,
							displayRules: [
								{
									field: 'sequence',
									value: 1,
								},
							],
						},
						{
							param: 'sequence_length',
							type: 'number',
							value: 4,
							min: 1,
							max: 8,
							label: 'COM_EMUNDUS_CUSTOM_REFERENCE_SEQUENCE_LENGTH_LABEL',
							helptext: 'COM_EMUNDUS_CUSTOM_REFERENCE_SEQUENCE_LENGTH_LABEL_HELP',
							displayed: false,
							displayRules: [
								{
									field: 'sequence',
									value: 1,
								},
							],
						},
					],
					isRepeatable: false,
				},
			],

			actualLanguage: '',
		};
	},
	setup() {
		return {
			globalStore: useGlobalStore(),
		};
	},

	async created() {
		this.loading = true;

		this.referenceFormat.params = this.referenceFormat.params || {};
		// if it is an array, convert to object
		if (Array.isArray(this.referenceFormat.params)) {
			this.referenceFormat.params = {};
		}

		await Promise.all([this.fetchDataResolvers(), this.getStatuses()]);

		this.loading = false;
	},

	methods: {
		async getStatuses() {
			let statuses = useSettingsStore().getStatuses;

			if (!statuses || statuses.length === 0) {
				await this.fetchStatuses();
			} else {
				this.fillStatuses(statuses);
			}
		},
		async fetchStatuses() {
			const response = await settingsService.getStatus();
			if (response.status) {
				let statuses = response.data || [];
				useSettingsStore().setStatuses(response.data);

				this.fillStatuses(statuses);
			} else {
				this.alertError(this.translate('COM_EMUNDUS_ERROR_FETCHING_STATUSES'));
			}
		},
		async fetchDataResolvers() {
			this.loadForm = true;
			const response = await settingsService.getCustomReferenceFormat();
			if (response.status) {
				useMappingStore().setTransformers(response.data.transformers);
				useMappingStore().setDataResolvers(response.data.dataResolvers);

				this.dataResolvers = response.data.dataResolvers;
				this.referenceFormat = response.data.referenceFormat || this.referenceFormat;

				this.formGroups[0].parameters.forEach((field) => {
					if (field.param in this.referenceFormat.params) {
						field.value = this.referenceFormat.params[field.param];
					}
				});

				this.loadForm = false;
			} else {
				this.alertError(this.translate('COM_EMUNDUS_ERROR_FETCHING_DATA_RESOLVERS'));
			}
		},

		fillStatuses(statuses) {
			statuses = [
				{ step: null, value: this.translate('COM_EMUNDUS_CUSTOM_REFERENCE_STATUS_TRIGGER_OPTION_NONE_LABEL') },
				...statuses,
			];
			this.formGroups[0].parameters.find((field) => field.param === 'triggering_status').multiselectOptions.options =
				statuses;
		},

		addMappingRow() {
			this.referenceFormat.blocks.push({
				id: Math.floor(Math.random() * 1000000000),
				mapping_id: this.referenceFormat.id,
				source_type: '',
				source_field: '',
				target_field: '',
				transformations: [],
			});
		},
		removeBlock(blockId) {
			this.referenceFormat.blocks = this.referenceFormat.blocks.filter((block) => block.id !== blockId);
		},
		onBlockTransformationsUpdate(blockId, transformations) {
			const block = this.referenceFormat.blocks.find((b) => b.id === blockId);
			if (block) {
				block.transformations = transformations;
			}
		},
		save() {
			let reference_form = {};

			// Validate all fields
			const formValidationFailed = this.formGroups[0].parameters.some((field) => {
				if (field.displayed) {
					let ref_name = 'field_' + field.param;

					if (this.$refs[ref_name] && !this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							reference_form[field.param] = [];
							field.value.forEach((element) => {
								reference_form[field.param].push(element[field.multiselectOptions.trackBy]);
							});
						} else {
							reference_form[field.param] = field.value ? field.value[field.multiselectOptions.trackBy] : null;
						}
					} else {
						reference_form[field.param] = field.value;
					}

					return false;
				}
			});
			if (formValidationFailed) return;

			this.loading = true;
			settingsService.saveCustomReference(this.referenceFormat, reference_form).then((response) => {
				if (response.status) {
					this.alertSuccess(this.translate('COM_EMUNDUS_CUSTOM_REFERENCE_FORMAT_SAVED_SUCCESSFULLY'));
					this.loading = false;
					this.$emit('addon-saved');
				} else {
					this.alertError(this.translate('COM_EMUNDUS_MAPPING_SAVE_ERROR'));
				}
			});
		},
	},
};
</script>
<style lang="scss"></style>
