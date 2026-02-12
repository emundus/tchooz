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
			if (param.value === this.mapping[param.param]) {
				sameValue = true;
			}

			this.mapping[param.param] = param.value;

			if (param.param === 'target_object') {
				// clean existing fields
				if (!sameValue) {
					this.mapping.rows = {};
				}
				this.constructRequiredFields();
			}
		},
		constructRequiredFields() {
			this.requiredFields = [];

			// find the target_object field in this.formGroups and get its choices
			const targetObjectParam = this.formGroups[0].parameters.find((param) => param.param === 'target_object');
			if (targetObjectParam) {
				const option = targetObjectParam.options.find((option) => option.value === this.mapping.target_object);

				if (option?.requiredFields) {
					this.requiredFields = option.requiredFields.map((field) =>
						this.fromFieldEntityToParameter(field, this.mapping.params[field.name] || null),
					);
				} else {
					this.requiredFields = [];
				}
				this.requiredFieldsKey += 1;
			}
		},
		onMappingParamsUpdated(params) {
			this.mapping.params = params;
		},
		save() {
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
