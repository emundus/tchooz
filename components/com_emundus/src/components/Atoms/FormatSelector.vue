<script>
import { SelectionCard, Slider } from '@emundus/ui';

export default {
	name: 'FormatSelector',
	components: { Slider, SelectionCard },

	props: {
		modelValue: {
			type: String,
			default: null,
		},
		formats: {
			type: Array,
			required: true,
		},
		exportTemplates: {
			type: Array,
			default: [],
		},
		selectedTemplate: {
			type: Number,
			default: 0,
		},
	},

	emits: ['update:modelValue', 'select-template', 'update:view'],

	data() {
		return {
			selectedView: 'format',
			selectionCardKey: 0,
		};
	},

	computed: {
		hasTemplates() {
			return this.exportTemplates.length > 0;
		},
		templatesByFormat() {
			const groups = {};
			this.exportTemplates.forEach((template) => {
				const format = template.format || 'other';
				if (!groups[format]) {
					groups[format] = [];
				}
				groups[format].push(template);
			});

			// Follow the formats prop order, then append any leftover (unknown) formats
			const orderedFormats = [...this.formats.map((format) => format.value), ...Object.keys(groups)];

			return orderedFormats
				.filter((format, index) => groups[format] && orderedFormats.indexOf(format) === index)
				.map((format) => {
					const formatDef = this.formats.find((f) => f.value === format);
					return {
						format,
						label: formatDef ? this.translate(formatDef.label) : format,
						templates: groups[format],
					};
				});
		},
		viewOptions() {
			return [
				{
					value: 'format',
					label: this.translate('COM_EMUNDUS_EXPORT_SLIDER_FORMAT'),
				},
				{
					value: 'template',
					label: this.translate('COM_EMUNDUS_EXPORT_SLIDER_TEMPLATE'),
				},
			];
		},
	},

	mounted() {
		// Restore the template view when returning with a template already selected
		if (this.selectedTemplate > 0) {
			this.selectedView = 'template';
		}
		// Keep the parent in sync with the view this component (re)mounts on
		this.$emit('update:view', this.selectedView);
	},

	methods: {
		selectView(value) {
			this.selectedView = value;
			this.$emit('update:view', value);
		},
		selectFormat(value) {
			if (this.modelValue === value) {
				return;
			}

			this.$emit('update:modelValue', value);
		},
		selectTemplate(event) {
			const templateId = event.target.value;
			this.$emit('select-template', templateId);
		},
	},
};
</script>

<template>
	<div class="tw-w-100 tw-flex tw-flex-col tw-gap-4">
		<div v-if="hasTemplates" class="tw-flex tw-justify-center">
			<Slider :model-value="selectedView" :options="viewOptions" variant="primary" @update:model-value="selectView" />
		</div>

		<div v-if="selectedView === 'format'" class="tw-flex tw-items-center tw-justify-center tw-gap-8">
			<SelectionCard
				v-for="format in formats"
				:key="format.value"
				:label="translate(format.label)"
				:active="modelValue === format.value"
				:icon="format.image"
				:class="{
					'tw-pointer-events-none': modelValue === format.value,
				}"
				@click="selectFormat(format.value)"
			/>
		</div>

		<div v-else-if="selectedView === 'template'" class="tw-flex tw-flex-col tw-gap-2">
			<label>{{ this.translate('COM_EMUNDUS_EXPORT_SELECT_TEMPLATE') }}</label>
			<span>{{ this.translate('COM_EMUNDUS_EXPORT_SELECT_TEMPLATE_HELP') }}</span>
			<select :value="selectedTemplate || ''" @change="selectTemplate($event)">
				<option value="">{{ this.translate('COM_EMUNDUS_EXPORT_SELECT_TEMPLATE_PLEASE_SELECT') }}</option>
				<optgroup v-for="group in templatesByFormat" :key="group.format" :label="group.label">
					<option v-for="template in group.templates" :key="template.id" :value="template.id">
						{{ template.name }}
					</option>
				</optgroup>
			</select>
		</div>
	</div>
</template>
