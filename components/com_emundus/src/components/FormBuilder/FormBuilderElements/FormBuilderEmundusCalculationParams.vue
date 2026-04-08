<script>
import formBuilderService from '@/services/formbuilder.js';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';
import BuildCalculationOperation from '@/components/Calculation/BuildCalculationOperation.vue';
import { useFormBuilderStore } from '@/stores/formbuilder.js';

export default {
	name: 'FormBuilderEmundusCalculationParams',
	components: { BuildCalculationOperation, ParameterForm },
	props: {
		element: {
			type: Object,
			required: true,
		},
	},
	mixins: [transformIntoParameterField],
	data() {
		return {
			fields: [],
			types: [],
			fieldsByType: {},
			groupsByType: {},
		};
	},
	setup() {
		const formBuilderStore = useFormBuilderStore();
		return { formBuilderStore };
	},
	created() {
		formBuilderService.getEmundusCalculationParameters().then((response) => {
			this.fields = response.data;
			if (this.typeField) {
				this.types = this.typeField.choices || [];
			}
		});

		formBuilderService.getEmundusCalculationParametersByType(this.element.id).then((response) => {
			this.fieldsByType = response.data;

			Object.entries(this.fieldsByType).forEach(([type, fields]) => {
				this.fieldsToParameterFormGroups(fields, this.element.params[type + '_form']).then((groups) => {
					this.groupsByType[type] = groups;
				});
			});
		});
	},
	methods: {
		onUpdateOperation(operation, fields) {
			this.element.params.operation = JSON.stringify({ operation, fields });

			this.$emit('updateParams', this.element.params);
		},
		onParameterValueUpdated(parameter) {
			this.element.params[this.element.params.type + '_form'][parameter.param] = parameter.value;

			this.$emit('updateParams', this.element.params);
		},
	},
	computed: {
		typeField() {
			return this.fields.find((field) => field.name === 'type');
		},
	},
};
</script>

<template>
	<div id="emundus_calculation_parameters">
		<div v-if="typeField && types.length > 0" class="tw-flex tw-w-full tw-flex-col tw-gap-2">
			<label>{{ typeField.label }}</label>
			<select id="type" v-model="element.params.type">
				<option v-for="type in types" :key="type.value" :value="type.value">
					{{ type.label }}
				</option>
			</select>
		</div>

		<BuildCalculationOperation
			v-if="element.params.type === 'custom'"
			:operation="element.params.operation ? JSON.parse(element.params.operation).operation : ''"
			:fields="element.params.operation ? JSON.parse(element.params.operation).fields : {}"
			@updateOperation="onUpdateOperation"
			class="tw-mt-4"
		/>
		<div v-else class="tw-mt-4">
			<ParameterForm
				v-if="groupsByType && groupsByType[element.params.type]"
				:groups="groupsByType[element.params.type]"
				:key="element.params.type"
				:fields="fieldsByType[element.params.type]"
				@parameterValueUpdated="onParameterValueUpdated"
			/>
		</div>
	</div>
</template>

<style scoped></style>
