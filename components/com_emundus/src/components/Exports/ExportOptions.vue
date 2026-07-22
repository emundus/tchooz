<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';
import exportService from '@/services/export.js';

export default {
	name: 'ExportOptions',
	components: { ParameterForm },
	mixins: [transformIntoParameterField],

	props: {
		modelValue: {
			type: Object,
			default: () => ({}),
		},
		format: {
			type: String,
			required: true,
		},
	},

	emits: ['update:modelValue'],

	data() {
		return {
			loading: false,
			fields: [],
			groups: [],
		};
	},

	created() {
		this.loadSchema();
	},

	watch: {
		format() {
			this.loadSchema();
		},
	},

	methods: {
		async loadSchema() {
			this.loading = true;

			try {
				const response = await exportService.getOptionsSchema(this.format);

				if (!response.status || !Array.isArray(response.data)) {
					this.fields = [];
					this.groups = [];
					return;
				}

				this.fields = response.data;

				const initialValues = {};
				this.fields.forEach((field) => {
					initialValues[field.name] =
						this.modelValue && Object.prototype.hasOwnProperty.call(this.modelValue, field.name)
							? this.modelValue[field.name]
							: field.default;
				});

				this.groups = await this.fieldsToParameterFormGroups(this.fields, initialValues);
				this.applyFilenameSanitizer();

				this.$emit('update:modelValue', { ...initialValues });
			} finally {
				this.loading = false;
			}
		},

		onParameterValueUpdated(parameter) {
			const next = { ...(this.modelValue || {}) };
			next[parameter.param] = parameter.value;
			this.$emit('update:modelValue', next);
		},

		// The "filename" option feeds FilenameRenderer, which keeps only [A-Za-z0-9_.-] (plus the
		// %TAG% / [fabrik] template delimiters) and drops everything else — spaces, accents,
		// punctuation. Mirror that on the input so the user sees exactly what will be kept.
		applyFilenameSanitizer() {
			this.groups.forEach((group) => {
				const filenameParam = (group.parameters || []).find((param) => param.param === 'filename');
				if (filenameParam) {
					filenameParam.sanitizePattern = '[^A-Za-z0-9_.%\\[\\]-]';
				}
			});
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-4">
		<div v-if="loading" class="tw-flex tw-min-h-[10vh] tw-flex-col tw-items-center tw-justify-center">
			<div class="em-loader"></div>
		</div>

		<ParameterForm
			v-else-if="groups.length > 0"
			:groups="groups"
			:fields="fields"
			@parameter-value-updated="onParameterValueUpdated"
		/>

		<div v-else class="tw-text-neutral-600">
			{{ this.translate('COM_EMUNDUS_EXPORT_NO_OPTIONS_AVAILABLE') }}
		</div>
	</div>
</template>

<style scoped></style>
