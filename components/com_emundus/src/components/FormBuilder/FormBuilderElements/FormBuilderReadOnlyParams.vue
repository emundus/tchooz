<script>
import Parameter from '@/components/Utils/Parameter.vue';

export default {
	name: 'FormBuilderReadOnlyParams',
	components: { Parameter },
	props: {
		element: {
			type: Object,
			required: true,
		},
	},
	emits: ['updateParams'],
	data() {
		return {
			sourceField: {
				param: 'source_element_id',
				type: 'multiselect',
				value: this.element.params?.source_element_id ? parseInt(this.element.params.source_element_id) : null,
				label: 'COM_EMUNDUS_ONBOARD_BUILDER_EMUNDUSREADONLY_SOURCE_ELEMENT',
				helptext: 'COM_EMUNDUS_ONBOARD_BUILDER_EMUNDUSREADONLY_SOURCE_ELEMENT_HELPTEXT',
				helpTextType: 'icon',
				placeholder: 'COM_EMUNDUS_ONBOARD_BUILDER_EMUNDUSREADONLY_SOURCE_ELEMENT_PLACEHOLDER',
				optional: true,
				displayed: true,
				multiselectOptions: {
					multiple: false,
					taggable: false,
					searchable: true,
					internalSearch: false,
					closeOnSelect: true,
					asyncRoute: 'getElementsListOptions',
					asyncController: 'formbuilder',
					options: [],
					optionsLimit: 20,
					label: 'label',
					trackBy: 'id',
					noOptionsText: 'COM_EMUNDUS_MULTISELECT_NOKEYWORDS',
					noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
					selectLabel: '',
					selectedLabel: '',
					deselectedLabel: '',
					tagValidations: [],
				},
			},
		};
	},
	computed: {
		asyncAttributes() {
			return {
				element_id: this.sourceField.value ?? 0,
			};
		},
	},
	methods: {
		onSourceUpdated(parameter) {
			const picked = parameter?.value;
			const id = picked && typeof picked === 'object' ? picked.id : picked;
			this.element.params.source_element_id = id ? parseInt(id) : '';
			this.$emit('updateParams', this.element.params);
		},
	},
};
</script>

<template>
	<div id="emundusreadonly_parameters" class="tw-flex tw-flex-col tw-gap-3">
		<Parameter
			:parameter-object="sourceField"
			:multiselect-options="sourceField.multiselectOptions"
			:async-attributes="asyncAttributes"
			:help-text-type="sourceField.helpTextType"
			@valueUpdated="onSourceUpdated"
		/>
	</div>
</template>
