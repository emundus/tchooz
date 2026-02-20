<script>
import { useMappingStore } from '@/stores/mapping.js';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';
import Parameter from '@/components/Utils/Parameter.vue';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';

export default {
	name: 'MappingTransformation',
	components: { ParameterForm, Parameter },
	props: {
		transformation: {
			type: Object,
			required: true,
		},
		row: {
			type: Object,
			required: true,
		},
	},
	mixins: [transformIntoParameterField],
	data() {
		return {
			groups: [],
		};
	},
	mounted() {
		let transformer = useMappingStore().getTransformerByType(this.transformation.type);

		if (Array.isArray(this.transformation.parameters)) {
			this.transformation.parameters = {};
		}

		if (transformer) {
			this.onSelectTransformerType(transformer.type);
		}
	},
	methods: {
		async mountGroups(fields, values) {
			this.groups = await this.fieldsToParameterFormGroups(fields, values, 'table');
		},
		async onSelectTransformerType(type) {
			const mappingStore = useMappingStore();
			const transformer = mappingStore.getTransformerByType(type);
			if (transformer) {
				let fields = transformer.parameters || [];

				let values = {
					...this.transformation.parameters,
					...this.row,
				};

				switch (transformer.type) {
					case 'map_values':
						let resolver = null;

						resolver = mappingStore.getDataResolverByType(this.row.source_type);

						if (resolver) {
							let formField = resolver.fields.find((field) => {
								return field.name === this.row.source_field.value;
							});

							if (formField && formField.type === 'choice' && formField.choices) {
								let mapFromField = fields.find((field) => field.name === 'map_from');

								if (mapFromField) {
									mapFromField.choices = formField.choices;
								}

								if (values.mapping === undefined || values.mapping.length === 0) {
									values.mapping = formField.choices.map((choice) => {
										return {
											map_from: choice.value,
											map_to: '',
										};
									});
								}
							} else {
								// Clear choices if no choices are available
								let mapFromField = fields.find((field) => field.name === 'map_from');

								if (mapFromField) {
									mapFromField.choices = [];
								}
							}
						}

						break;
					case 'map_databasejoin_element_values':
						const options = await this.provideParameterOptions(fields[0], values);

						if (Array.isArray(options)) {
							fields[0].choices = options;
						} else {
							fields[0].choices = [];
						}

						break;
				}

				this.mountGroups(fields, values);
			} else {
				this.groups = [];
			}
		},
		removeTransformation() {
			this.$emit('removeTransformation', this.transformation.id);
		},
		onParameterValueUpdated(parameter, group, rowIndex) {
			if (group.isRepeatable) {
				if (!this.transformation.parameters[group.id]) {
					this.transformation.parameters[group.id] = [];
				}

				if (!this.transformation.parameters[group.id][rowIndex]) {
					this.transformation.parameters[group.id][rowIndex] = {};
				}
				this.transformation.parameters[group.id][rowIndex][parameter.param] = parameter.value;
			} else {
				this.transformation.parameters[parameter.param] = parameter.value;
			}
			this.$emit('transformationUpdated', this.transformation.id, this.transformation);
		},
	},
	computed: {
		transformersSelectOptions() {
			// todo: refactor to make a dynamic check based on transformer requirements
			const mappingStore = useMappingStore();
			const resolver = mappingStore.getDataResolverByType(this.row.source_type);
			let displayMapValuesOption = false;
			let displayMapDatabaseJoinOption = false;

			if (resolver && resolver.fields) {
				const field = resolver.fields.find((field) => {
					return field.name === this.row.source_field.value;
				});

				if (field && field.type === 'choice') {
					displayMapValuesOption = true;
				}

				if (field && typeof field.originalType !== 'undefined' && field.originalType === 'databasejoin') {
					displayMapDatabaseJoinOption = true;
				}
			}

			return useMappingStore()
				.getTransformers()
				.filter((transformer) => {
					if (transformer.type === 'map_values' && !displayMapValuesOption) {
						return false;
					}
					if (transformer.type === 'map_databasejoin_element_values' && !displayMapDatabaseJoinOption) {
						return false;
					}
					return true;
				})
				.map((transformer) => {
					return {
						value: transformer.type,
						label: transformer.label,
					};
				})
				.sort((a, b) => a.label.localeCompare(b.label));
		},
	},
};
</script>

<template>
	<div id="mapping-transformation">
		<div class="tw-flex tw-w-full tw-justify-end">
			<span
				class="material-symbols-outlined not-to-close-modal tw-cursor-pointer tw-text-red-500"
				@click="removeTransformation"
			>
				close
			</span>
		</div>
		<div class="tw-flex tw-flex-col">
			<label for="transformation-type">{{ translate('COM_EMUNDUS_TRANSFORMATION_TYPE_SELECT_LABEL') }}</label>
			<select v-model="transformation.type" @change="onSelectTransformerType(transformation.type)">
				<option v-for="option in transformersSelectOptions" :key="option.value" :value="option.value">
					{{ option.label }}
				</option>
			</select>
		</div>

		<ParameterForm
			:id="0"
			:title="''"
			:description="''"
			:groups="groups"
			@parameterValueUpdated="onParameterValueUpdated"
		/>
	</div>
</template>

<style scoped></style>
