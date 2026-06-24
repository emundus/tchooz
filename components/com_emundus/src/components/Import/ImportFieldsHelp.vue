<script>
import importService from '@/services/import.js';
import { StatusIcon } from '@emundus/ui';

export default {
	name: 'ImportFieldsHelp',
	components: { StatusIcon },
	emits: ['close', 'close-modal'],
	props: {
		fields: {
			type: Array,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			modelPath: '',
		};
	},
	methods: {
		downloadImportModel() {
			importService
				.getImportModel(this.type)
				.then((response) => {
					this.modelPath = response.data;

					this.$nextTick(() => {
						document.querySelector('#import_model_download').click();
					});
				})
				.catch((error) => {
					console.error('Error fetching import model:', error);
				});
		},
	},
	computed: {
		sortedFields() {
			return [...this.fields].sort((a, b) => {
				if (a.required !== b.required) return a.required ? -1 : 1;
				return a.canonical;
			});
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-4">
		<div class="tw-relative tw-flex tw-items-center tw-justify-between">
			<button
				type="button"
				class="tw-inline-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-bg-transparent tw-text-sm"
				@click="$emit('close')"
			>
				<span class="material-symbols-outlined">arrow_back</span>
				{{ translate('COM_EMUNDUS_IMPORT_BACK_TO_UPLOAD') }}
			</button>
			<h2 class="tw-text-center">
				{{ translate('COM_EMUNDUS_IMPORT_FIELDS_HELP_TITLE') }}
			</h2>
			<button class="tw-cursor-pointer tw-bg-transparent" @click="$emit('close-modal')">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>

		<div>
			<p class="tw-mt-2 tw-text-center">
				{{ translate('COM_EMUNDUS_IMPORT_FIELDS_HELP_INTRO') }}
			</p>
			<button @click="downloadImportModel" class="tw-btn tw-btn-tertiary tw-mx-auto tw-mt-2 tw-block tw-w-fit">
				{{ translate('COM_EMUNDUS_IMPORT_DOWNLOAD_XLSX') }}
			</button>
			<a :href="modelPath" id="import_model_download" download class="tw-hidden"></a>
		</div>

		<div class="tw-overflow-x-auto tw-rounded-coordinator-form tw-border tw-border-neutral-300">
			<table class="tw-border-collapse">
				<thead>
					<tr>
						<th>{{ translate('COM_EMUNDUS_IMPORT_MODEL_DOC_DATA_NAME') }}</th>
						<th>{{ translate('COM_EMUNDUS_IMPORT_ALLOWED_HEADER') }}</th>
						<th>{{ translate('COM_EMUNDUS_IMPORT_MODEL_DOC_FORMAT') }}</th>
						<th>{{ translate('COM_EMUNDUS_IMPORT_MODEL_DOC_EXAMPLES') }}</th>
						<th>{{ translate('COM_EMUNDUS_IMPORT_MODEL_DOC_REQUIRED_VALUE') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="field in sortedFields" :key="field.canonical">
						<td class="tw-border-t tw-border-neutral-200 tw-p-3 tw-text-sm tw-text-neutral-700">
							{{ field.label ?? field.aliases[0] }}
						</td>
						<td class="tw-border-t tw-border-neutral-200 tw-p-3 tw-text-sm tw-text-neutral-700">
							<div class="tw-flex tw-flex-col tw-gap-2">
								<div class="tw-flex tw-flex-wrap tw-items-center tw-gap-1">
									<template v-for="alias in field.aliases" :key="alias">
										<span
											class="tw-rounded tw-border tw-border-neutral-300 tw-bg-white tw-px-1.5 tw-py-0.5 tw-text-xs tw-font-normal tw-text-neutral-700"
										>
											<!-- todo: copy to clipboard -->
											{{ alias }}
										</span>
									</template>
								</div>
							</div>
						</td>
						<td class="tw-border-t tw-border-neutral-200 tw-p-3 tw-text-sm tw-text-neutral-700">
							{{ field.type_label }}
						</td>
						<td class="tw-border-t tw-border-neutral-200 tw-p-3 tw-text-sm tw-text-neutral-700">
							<div v-if="field.values && field.values.length > 0" class="tw-flex tw-flex-col tw-gap-2">
								<div v-for="value in field.values" :key="value.value" class="tw-flex tw-items-center">
									<div v-if="value.value !== value.label" class="tw-flex tw-flex-row tw-items-center tw-gap-1">
										<span
											class="tw-rounded tw-border tw-border-neutral-300 tw-bg-white tw-p-1 tw-text-xs tw-font-bold"
											>{{ value.value }}</span
										>
										<span class="tw-text-xs">{{ translate('COM_EMUNDUS_FOR') }}</span>
										<span class="tw-rounded tw-border tw-border-neutral-300 tw-bg-white tw-p-1 tw-text-xs">{{
											value.label
										}}</span>
									</div>
									<span v-else> {{ value.value }}</span>
								</div>
							</div>
							<div v-else-if="field.examples && field.examples.length > 0" class="tw-flex tw-flex-col tw-gap-2">
								<div v-for="example in field.examples" :key="example.value" class="tw-flex tw-items-center">
									<div v-if="example.value !== example.label" class="tw-flex tw-flex-row tw-items-center tw-gap-1">
										<span
											class="tw-rounded tw-border tw-border-neutral-300 tw-bg-white tw-p-1 tw-text-xs tw-font-bold"
											>{{ example.value }}</span
										>
										<span class="tw-text-xs">{{ translate('COM_EMUNDUS_FOR') }}</span>
										<span class="tw-rounded tw-border tw-border-neutral-300 tw-bg-white tw-p-1 tw-text-xs">{{
											example.label
										}}</span>
									</div>
									<span v-else> {{ example.value }}</span>
								</div>
							</div>
						</td>
						<td class="tw-border-t tw-border-neutral-200 tw-p-3 tw-text-sm tw-text-neutral-700">
							<template v-if="field.required">
								<StatusIcon state="correct" :label="translate('COM_EMUNDUS_IMPORT_REQUIRED_VALUE')" />
							</template>
							<span v-else>-</span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>

<style scoped></style>
