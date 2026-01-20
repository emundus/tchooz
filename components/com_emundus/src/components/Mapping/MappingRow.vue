<script>
import Parameter from '@/components/Utils/Parameter.vue';
import Modal from '@/components/Modal.vue';
import MappingTransformation from '@/components/Mapping/MappingTransformation.vue';

export default {
	name: 'MappingRow',
	components: { MappingTransformation, Parameter, Modal },
	props: {
		row: {
			type: Object,
			required: true,
		},
		dataResolvers: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			sourceTypeParameter: {
				param: 'source_type',
				type: 'select',
				placeholder: '',
				value: null,
				label: '',
				helptext: '',
				displayed: true,
				options: [],
				reload: 0,
				hideLabel: true,
			},
			sourceFieldParameter: {
				param: 'source_field', // to be populated dynamically based on the selected condition type
				type: 'multiselect',
				placeholder: '',
				value: null,
				label: '',
				helptext: '',
				displayed: false,
				options: [],
				reload: 0,
				hideLabel: true,
				multiselectOptions: {
					options: [],
					noOptions: false,
					multiple: false,
					taggable: false,
					searchable: true,
					internalSearch: true,
					asyncRoute: '',
					asyncController: '',
					asyncAttributes: [],
					optionsLimit: 100,
					optionsPlaceholder: 'COM_EMUNDUS_MULTISELECT_ADDKEYWORDS',
					selectLabel: 'PRESS_ENTER_TO_SELECT',
					selectGroupLabel: 'PRESS_ENTER_TO_SELECT_GROUP',
					selectedLabel: 'SELECTED',
					deselectedLabel: 'PRESS_ENTER_TO_REMOVE',
					deselectGroupLabel: 'PRESS_ENTER_TO_DESELECT_GROUP',
					noOptionsText: 'COM_EMUNDUS_MULTISELECT_NOKEYWORDS',
					noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
					// Can add tag validations (ex. email, phone, regex)
					tagValidations: [],
					tagRegex: '',
					trackBy: 'value',
					label: 'label',
				},
			},
			targetFieldParameter: {
				param: 'target_field', // to be populated dynamically based on the selected condition type
				type: 'select',
				placeholder: '',
				value: null,
				label: '',
				helptext: '',
				displayed: false,
				options: [],
				reload: 0,
				hideLabel: true,
			},
		};
	},
	created() {
		this.sourceTypeParameter.options = this.dataResolvers.map((resolver) => {
			return {
				value: resolver.targetType,
				label: resolver.label,
			};
		});

		this.sourceFieldParameter.value = this.row.source_field;
		this.sourceTypeParameter.value = this.row.source_type;

		this.sourceTypeParameter.reload += 1;
	},
	methods: {
		onParameterValueUpdated(param) {
			this.row[param.param] = param.value;

			if (param.param === 'source_type') {
				// Update sourceFieldParameter options based on selected source_type
				const selectedResolver = this.dataResolvers.find((resolver) => resolver.targetType === param.value);

				if (selectedResolver) {
					if (selectedResolver.targetType === 'static_value') {
						this.sourceFieldParameter.type = 'text';
					} else {
						this.sourceFieldParameter.type = 'multiselect';
					}

					this.sourceFieldParameter.options = selectedResolver.fields.map((field) => {
						return {
							value: field.name,
							label: field.label,
						};
					});
					this.sourceFieldParameter.displayed = true;
					this.sourceFieldParameter.reload += 1;

					if (!selectedResolver.searchable) {
						// searchable options
						this.sourceFieldParameter.multiselectOptions.asyncRoute = '';
						this.sourceFieldParameter.multiselectOptions.asyncController = '';
						this.sourceFieldParameter.multiselectOptions.asyncCallback = null;
						this.sourceFieldParameter.displayed = true;
						this.sourceFieldParameter.multiselectOptions.options = this.sourceFieldParameter.options;

						// remove grouping if any
						this.sourceFieldParameter.multiselectOptions.groupValues = null;
						this.sourceFieldParameter.multiselectOptions.groupLabel = null;
						this.sourceFieldParameter.multiselectOptions.groupSelect = false;
					} else {
						// group options

						let groups = {};
						let groupedOptions = [];

						selectedResolver.fields.forEach((field) => {
							// On suppose que chaque field a une propriété group: { name, label }
							const groupName = field.group?.name || 'default';
							const groupLabel = field.group?.label || 'Autres';

							if (!groups[groupName]) {
								groups[groupName] = {
									groupLabel: groupLabel + '(' + groupName + ')',
									options: [],
								};
							}

							groups[groupName].options.push({
								value: field.name,
								label: field.label,
							});
						});

						// Transformer l'objet groups en tableau
						groupedOptions = Object.values(groups);

						this.sourceFieldParameter.multiselectOptions.asyncRoute =
							'getConditionsFields&type=' + selectedResolver.targetType;
						this.sourceFieldParameter.multiselectOptions.asyncController = 'automation';
						this.sourceFieldParameter.multiselectOptions.asyncCallback = async (response, parameter) => {
							return await this.searchableCallback(response, parameter, selectedResolver);
						};
						this.sourceFieldParameter.multiselectOptions.groupValues = 'options';
						this.sourceFieldParameter.multiselectOptions.groupLabel = 'groupLabel';
						this.sourceFieldParameter.multiselectOptions.groupSelect = false;
						this.sourceFieldParameter.displayed = true;
						this.sourceFieldParameter.multiselectOptions.options = groupedOptions;
					}

					if (this.initialized) {
						this.sourceFieldParameter.value = null;
					}
				} else {
					this.sourceFieldParameter.options = [];
					this.sourceFieldParameter.displayed = false;
					this.sourceFieldParameter.value = null;
					this.sourceFieldParameter.reload += 1;
				}
			}
		},
		remove() {
			this.$emit('removeRow', this.row.id);
		},
		async searchableCallback(response, parameter, selectedResolver) {
			return new Promise((resolve, reject) => {
				if (response && response.status && response.data) {
					// Fusionner les nouveaux champs avec les existants
					selectedResolver.fields = [...selectedResolver.fields, ...response.data].filter(
						(field, index, self) => index === self.findIndex((f) => f.name === field.name),
					);

					// Regrouper les options par groupe
					let groups = {};
					selectedResolver.fields.forEach((field) => {
						const groupName = field.group?.name || 'default';
						const groupLabel = field.group?.label || 'Autres';
						if (!groups[groupName]) {
							groups[groupName] = {
								groupLabel: groupLabel + (groupName !== 'default' ? ' (' + groupName + ')' : ''),
								options: [],
							};
						}
						groups[groupName].options.push({
							value: field.name,
							label: field.label,
						});
					});

					// Mettre à jour les options groupées dans le paramètre
					parameter.multiselectOptions.options = Object.values(groups);

					resolve(parameter.multiselectOptions.options);
				} else {
					this.alertError('COM_EMUNDUS_AUTOMATION_CONDITIONS_FIELDS_ERROR', response?.msg);
					reject([]);
				}
			});
		},
		openRowTransformation() {
			this.$refs['mappingRowTransformationsModal' + this.row.id].open();
		},
		addTransformation(rowId) {
			let transformationInstance = {
				id: Math.floor(Math.random() * 1000000),
				mapping_row_id: rowId,
				type: '',
				parameters: {},
			};

			this.row.transformations.push(transformationInstance);
		},
		onRemoveTransformation(transformationId) {
			this.row.transformations = this.row.transformations.filter(
				(transformation) => transformation.id !== transformationId,
			);
		},
		onTransformationUpdated(transformationId, updatedTransformation) {
			let transformationToUpdate = this.row.transformations.find(
				(transformation) => transformation.id === transformationId,
			);
			if (transformationToUpdate) {
				// Update the transformation with the new values
				if (transformationToUpdate.type !== updatedTransformation.type) {
					transformationToUpdate.type = updatedTransformation.type;
					transformationToUpdate.parameters = {};
				}
				transformationToUpdate.parameters = updatedTransformation.parameters;
			}
		},
		onCloseTransformations() {
			this.$refs['mappingRowTransformationsModal' + this.row.id].close();
			this.$emit('rowTransformations', this.row.id, this.row.transformations);
		},
	},
};
</script>

<template>
	<tr :key="row.id">
		<td class="tw-flex tw-w-full tw-flex-row tw-gap-2">
			<Parameter
				class="tw-w-full"
				:parameter-object="sourceTypeParameter"
				:key="index + '|' + sourceTypeParameter.param + '-' + sourceTypeParameter.reload"
				@valueUpdated="onParameterValueUpdated"
			>
			</Parameter>

			<Parameter
				class="tw-w-full"
				:parameter-object="sourceFieldParameter"
				:key="index + '|' + sourceFieldParameter.param + '-' + sourceFieldParameter.reload"
				:multiselect-options="sourceFieldParameter.multiselectOptions"
				@valueUpdated="onParameterValueUpdated"
			>
			</Parameter>
		</td>
		<td>
			<input type="text" v-model="row.target_field" />
		</td>
		<td class="row-actions tw-flex tw-gap-4">
			<div class="tw-relative">
				<span
					class="material-symbols-outlined not-to-close-modal tw-cursor-pointer"
					@click="openRowTransformation"
					:title="translate('COM_EMUNDUS_MAPPING_ROW_EDIT_TRANSFORMATIONS_TOOLTIP')"
				>
					transform
				</span>
				<div
					v-if="row.transformations.length > 0"
					@click="openRowTransformation"
					class="tw-absolute tw-right-[-8px] tw-top-[-8px] tw-flex tw-h-[16px] tw-w-[16px] tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-full tw-bg-green-500 tw-text-xs tw-font-bold tw-text-white"
				>
					{{ row.transformations.length }}
				</div>
			</div>
			<span class="material-symbols-outlined tw-cursor-pointer tw-text-red-500" @click="remove"> delete </span>
		</td>

		<Modal
			name="mapping-row-transformations-modal-{{ row.id }}"
			:ref="'mappingRowTransformationsModal' + row.id"
			:open-on-create="false"
			:center="true"
			:width="'80%'"
			classes="tw-rounded-coordinator tw-shadow-card tw-p-4"
			:title="'COM_EMUNDUS_MAPPING_ROW_TRANSFORMATIONS_MODAL_TITLE'"
			:click-to-close="false"
		>
			<p class="!tw-mb-4">
				{{ translate('COM_EMUNDUS_MAPPING_ROW_TRANSFORMATIONS_MODAL_DESCRIPTION') }}
			</p>
			<MappingTransformation
				class="tw-shadow-cards tw-mb-4 tw-rounded-coordinator tw-border tw-border-neutral-300 tw-bg-white tw-p-6"
				v-for="transformation in row.transformations"
				:key="transformation.id"
				:transformation="transformation"
				:row="row"
				@removeTransformation="onRemoveTransformation"
				@transformationUpdated="onTransformationUpdated"
			/>

			<p v-if="row.transformations.length < 1" class="tw-mt-4 tw-text-center tw-text-neutral-500">
				{{ translate('COM_EMUNDUS_MAPPING_ROW_NO_TRANSFORMATIONS_DEFINED') }}
			</p>

			<div class="tw-mt-4 tw-flex tw-w-full tw-justify-end">
				<button class="tw-btn-primary" @click="addTransformation(row.id)">
					{{ translate('COM_EMUNDUS_BTN_ADD_TRANSFORMATION') }}
				</button>
				<button class="tw-btn-secondary tw-ml-2" @click="onCloseTransformations">
					{{ translate('COM_EMUNDUS_CLOSE') }}
				</button>
			</div>
		</Modal>
	</tr>
</template>

<style scoped></style>
