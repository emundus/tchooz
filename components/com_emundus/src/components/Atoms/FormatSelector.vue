<script>
export default {
	name: 'FormatSelector',

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
	},

	emits: ['update:modelValue', 'select-template'],

	methods: {
		selectFormat(value) {
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
	<div class="tw-flex tw-flex-col tw-gap-4">
		<div class="tw-flex tw-items-center tw-justify-center tw-gap-8">
			<label
				v-for="format in formats"
				:key="format.value"
				class="tw-flex tw-cursor-pointer tw-flex-col tw-items-center tw-justify-center tw-gap-5 tw-rounded-coordinator tw-border tw-px-6 tw-py-3"
				:class="[
					modelValue === format.value ? 'tw-border-main-300 tw-bg-main-100' : 'tw-border-transparent tw-bg-neutral-200',
				]"
				@click="selectFormat(format.value)"
			>
				<img :src="format.image" alt="" class="tw-h-12 tw-w-12" />
				<span>{{ this.translate(format.label) }}</span>
			</label>
		</div>
		<div v-if="exportTemplates.length > 0" class="tw-flex tw-items-center tw-gap-3">
			<hr class="tw-w-full" />
			<span class="tw-whitespace-nowrap">{{ this.translate('COM_EMUNDUS_CONDITIONS_GROUP_OPERATOR_OR') }}</span>
			<hr class="tw-w-full" />
		</div>
		<div v-if="exportTemplates.length > 0" class="tw-flex tw-flex-col tw-gap-2">
			<label>{{ this.translate('COM_EMUNDUS_EXPORT_SELECT_TEMPLATE') }}</label>
			<select @change="selectTemplate($event)">
				<option selected value="">{{ this.translate('COM_EMUNDUS_EXPORT_SELECT_TEMPLATE_PLEASE_SELECT') }}</option>
				<option v-for="template in exportTemplates" :key="template.id" :value="template.id">
					{{ template.name }}
				</option>
			</select>
		</div>
	</div>
</template>
