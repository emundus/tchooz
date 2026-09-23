<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import transformMixin from '@/mixins/transformIntoParameterField.js';
import Parameter from '@/components/Utils/Parameter.vue';
import Back from '@/components/Utils/Back.vue';
import mappingService from '@/services/mapping.js';
import alerts from '@/mixins/alerts.js';
import MappingRow from '@/components/Mapping/MappingRow.vue';
import { useMappingStore } from '@/stores/mapping.js';
import MappingParams from '@/components/Mapping/MappingParams.vue';

export default {
	name: 'MappingEdit',
	components: { MappingParams, MappingRow, Back, Parameter, ParameterForm },
	props: {
		mapping: {
			type: Object,
			required: true,
		},
		fields: {
			type: Array,
			required: true,
		},
		dataResolvers: {
			type: Array,
			required: true,
		},
		synchronizers: {
			type: Array,
			required: true,
		},
		transformers: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			formGroups: [],
			defaultParameters: [],
			requiredFields: [],
			requiredFieldsKey: 0,
			availableFields: [],
		};
	},
	mixins: [transformMixin, alerts],
	created() {
		this.mapping.params = this.mapping.params || {};
		// if it is an array, convert to object
		if (Array.isArray(this.mapping.params)) {
			this.mapping.params = {};
		}
		this.getFormGroups();
	},
	mounted() {
		useMappingStore().setTransformers(this.transformers);
		useMappingStore().setDataResolvers(this.dataResolvers);
		this.loading = false;
	},
	methods: {
		async getFormGroups() {
			this.fieldsToParameterFormGroups(this.fields, this.mapping).then((groups) => {
				this.formGroups = groups;
				this.constructRequiredFields();
			});
		},
		addMappingRow() {
			this.mapping.rows.push({
				id: Math.floor(Math.random() * 1000000000),
				mapping_id: this.mapping.id,
				source_type: '',
				source_field: '',
				target_field: '',
				transformations: [],
			});
		},
		removeMappingRow(rowId) {
			this.mapping.rows = this.mapping.rows.filter((row) => row.id !== rowId);
		},
		onRowTransformationsUpdate(rowId, transformations) {
			const row = this.mapping.rows.find((r) => r.id === rowId);
			if (row) {
				row.transformations = transformations;
			}
		},
		onParameterValueUpdated(param) {
			let sameValue = false;

			if (param.value == this.mapping[param.param]) {
				sameValue = true;
			}
			const oldValue = this.mapping[param.param];

			this.mapping[param.param] = param.value;

			if (param.param === 'target_object' && oldValue != param.value && oldValue != param.value.value) {
				if (!sameValue) {
					this.mapping.rows = [];
				}
				this.constructRequiredFields();
			}
		},
		constructRequiredFields() {
			this.requiredFields = [];
			this.availableFields = [];

			const selectedValue =
				typeof this.mapping.target_object === 'object' && this.mapping.target_object !== null
					? this.mapping.target_object.value
					: this.mapping.target_object;

			// The selected target_object is the full option once picked (it carries requiredFields /
			// availableFields). Prefer it: at creation the field options are loaded asynchronously in
			// the dropdown and are not reflected in this.formGroups. Fall back to the field options
			// (edition, where they are server-built).
			let option =
				typeof this.mapping.target_object === 'object' &&
				this.mapping.target_object !== null &&
				(this.mapping.target_object.availableFields || this.mapping.target_object.requiredFields)
					? this.mapping.target_object
					: null;

			if (!option) {
				const targetObjectParam = this.formGroups[0]?.parameters.find((param) => param.param === 'target_object');
				option = targetObjectParam?.options.find((opt) => opt.value === selectedValue) || null;
			}

			if (option) {
				this.requiredFields = (option.requiredFields || []).map((field) =>
					this.fromFieldEntityToParameter(field, this.mapping.params[field.name] || null),
				);
				this.availableFields = option.availableFields || [];
				this.syncAvailableFieldRows();
			}

			this.requiredFieldsKey += 1;
		},
		// Pre-fill one mapping row per available target field (source left to the admin). This only
		// runs on a fresh mapping (no rows yet): on an already-saved mapping the persisted rows are
		// authoritative, so we never re-add fields the admin deliberately removed. No-op when the
		// object declares no available fields (free-form mapping).
		syncAvailableFieldRows() {
			if (!this.availableFields || this.availableFields.length === 0) {
				return;
			}

			this.mapping.rows = this.mapping.rows || [];

			if (this.mapping.rows.length > 0) {
				return;
			}

			const existingTargets = this.mapping.rows.map((row) => row.target_field);

			this.availableFields.forEach((field) => {
				if (!existingTargets.includes(field.name)) {
					const row = {
						id: Math.floor(Math.random() * 1000000000),
						mapping_id: this.mapping.id,
						source_type: '',
						source_field: '',
						target_field: field.name,
						transformations: [],
					};

					// Pre-fill the field default value as a static source (editable / overridable).
					if (field.defaultValue !== null && field.defaultValue !== undefined && field.defaultValue !== '') {
						row.source_type = 'static_value';
						row.source_field = field.defaultValue;
					}

					this.mapping.rows.push(row);
				}
			});
		},
		onMappingParamsUpdated(params) {
			this.mapping.params = params;
		},
		save() {
			// No hard block on required fields: "required" is a POST-time constraint enforced at
			// runtime (the object throws if a required field is missing when actually creating).
			// The UI only pre-fills defaults and marks required fields with a *.
			mappingService.save(this.mapping).then((response) => {
				if (response.status) {
					this.alertSuccess(this.translate('COM_EMUNDUS_MAPPING_SAVED_SUCCESSFULLY'));

					if (response.redirect) {
						window.location.href = response.redirect;
					}
				} else {
					this.alertError(this.translate('COM_EMUNDUS_MAPPING_SAVE_ERROR'));
				}
			});
		},
	},
};
</script>

<template>
	<div
		id="mapping-edit-form"
		class="tw-mb-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<Back :link="'index.php?option=com_emundus&view=mapping'" class="tw-mb-4"></Back>
		<h1>{{ translate('COM_EMUNDUS_MAPPING_EDIT') }}</h1>
		<p>{{ translate('COM_EMUNDUS_MAPPING_EDIT_INTRO') }}</p>

		<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-4" v-if="!loading">
			<ParameterForm
				id="mapping-parameters-form"
				:groups="formGroups"
				:fields="fields"
				@parameterValueUpdated="onParameterValueUpdated"
			/>

			<MappingParams
				:key="requiredFieldsKey"
				:fields="requiredFields"
				:params="mapping.params"
				@mappingParamsUpdated="onMappingParamsUpdated"
			/>

			<div id="mapping-rows">
				<table>
					<!-- Headers -->
					<thead>
						<tr>
							<td class="tw-w-1/2 tw-font-bold">{{ translate('COM_EMUNDUS_MAPPING_SOURCE_HEADER') }}</td>
							<td class="tw-w-1/2 tw-font-bold">{{ translate('COM_EMUNDUS_MAPPING_TARGET_HEADER') }}</td>
							<td></td>
						</tr>
					</thead>

					<!-- Rows -->
					<tbody>
						<MappingRow
							v-for="(row, index) in mapping.rows"
							:key="row.id"
							:row="row"
							:data-resolvers="dataResolvers"
							:available-fields="availableFields"
							@removeRow="removeMappingRow"
							@rowTransformations="onRowTransformationsUpdate"
						>
						</MappingRow>
					</tbody>
				</table>

				<div class="tw-mt-4 tw-flex tw-w-full tw-justify-end">
					<button class="tw-btn-secondary" @click="addMappingRow">
						{{ translate('COM_EMUNDUS_MAPPING_ADD_ROW_BUTTON') }}
					</button>
				</div>
			</div>
		</div>

		<div class="tw-mt-4 tw-flex tw-w-full tw-justify-end">
			<button class="tw-btn-primary" @click="save">
				{{ translate('COM_EMUNDUS_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
