<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';

export default {
	name: 'MappingParams',
	components: { ParameterForm },
	props: {
		params: {
			type: Object,
			required: true,
		},
		fields: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			groups: [],
		};
	},
	mounted() {
		this.groups = [
			{
				id: 'params',
				title: this.translate('COM_EMUNDUS_MAPPING_OTHER_PARAMETERS'),
				parameters: this.fields,
			},
		];
	},
	methods: {
		onParameterValueUpdated(param) {
			this.params[param.param] = param.value;

			this.$emit('mappingParamsUpdated', this.params);
		},
	},
};
</script>

<template>
	<div id="mapping-parameters">
		<div v-if="fields.length > 0">
			<ParameterForm :groups="groups" @parameter-value-updated="onParameterValueUpdated"> </ParameterForm>
		</div>
	</div>
</template>

<style scoped></style>
