<script>
import exportService from '@/services/export.js';
import { Tab, TabList } from '@emundus/ui';

export default {
	name: 'ExportResume',
	components: { Tab, TabList },

	props: {
		selectedFormat: {
			type: String,
			required: true,
		},
		exportSettings: {
			type: Object,
			default: () => ({}),
		},
		selectedHeaders: {
			type: Array,
			default: () => [],
		},
		selectedSynthesis: {
			type: Array,
			default: () => [],
		},
		selectedElements: {
			type: Array,
			default: () => [],
		},
		selectedAttachments: {
			type: Array,
			default: () => [],
		},
		formats: {
			type: Array,
			default: () => [],
		},
	},

	data() {
		return {
			optionsFields: [],
			activeContentTab: null,
		};
	},

	created() {
		this.loadOptionsSchema();
		this.activeContentTab = this.contentTabs[0]?.code ?? null;
	},

	watch: {
		selectedFormat() {
			this.loadOptionsSchema();
			if (!this.contentTabs.some((t) => t.code === this.activeContentTab)) {
				this.activeContentTab = this.contentTabs[0]?.code ?? null;
			}
		},
	},

	computed: {
		formatLabel() {
			const found = this.formats.find((format) => format.value === this.selectedFormat);
			return found && found.label ? this.translate(found.label) : (this.selectedFormat || '').toUpperCase();
		},

		optionRows() {
			return this.optionsFields.map((field) => ({
				name: field.name,
				label: this.formatFieldLabel(field),
				value: this.formatValue(this.exportSettings[field.name], field),
			}));
		},

		contentTabs() {
			let tabs = [];

			if (this.selectedFormat === 'pdf' || this.selectedFormat === 'zip') {
				tabs.push(
					{
						code: 'header',
						label: 'COM_EMUNDUS_EXPORT_RESUME_HEADER_CONTENT',
						items: this.selectedHeaders,
					},
					{
						code: 'synthesis',
						label: 'COM_EMUNDUS_EXPORT_RESUME_SYNTHESIS_CONTENT',
						items: this.selectedSynthesis,
					},
				);
			}

			tabs.push({
				code: 'body',
				label: 'COM_EMUNDUS_EXPORT_RESUME_BODY_CONTENT',
				items: this.selectedElements,
			});

			if (this.selectedFormat === 'pdf' || this.selectedFormat === 'zip') {
				tabs.push({
					code: 'attachments',
					label: 'COM_EMUNDUS_EXPORT_RESUME_ATTACHMENTS_CONTENT',
					items: this.selectedAttachments,
				});
			}

			return tabs;
		},

		totalContentCount() {
			return this.contentTabs.reduce((sum, tab) => sum + tab.items.length, 0);
		},

		activeContentItems() {
			const tab = this.contentTabs.find((t) => t.code === this.activeContentTab);
			return tab ? tab.items : [];
		},
	},

	methods: {
		loadOptionsSchema() {
			exportService.getOptionsSchema(this.selectedFormat).then((response) => {
				if (response.status && Array.isArray(response.data)) {
					this.optionsFields = response.data;
				} else {
					this.optionsFields = [];
				}
			});
		},

		// Pre-substitute `%s` in the pivot-target label with the current scope's own
		// translation (backend uses one label template with a `%s` placeholder).
		// Named `formatFieldLabel` to avoid clashing with the `formatLabel` computed
		// above, which returns the export-format label (xlsx/pdf/zip).
		formatFieldLabel(field) {
			if (field.name !== 'pivot_target') {
				return field.label;
			}

			const scopeLabelKeys = {
				group: 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_GROUP',
				element: 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_ELEMENT',
				evaluation: 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_EVALUATION',
			};

			const template = this.translate(field.label);
			const scopeKey = scopeLabelKeys[this.exportSettings?.pivot_scope];
			const scopeLabel = scopeKey ? this.translate(scopeKey) : '';
			return template.replace('%s', scopeLabel);
		},

		formatValue(value, field) {
			if (value === null || value === undefined || value === '') {
				return '—';
			}

			if (field.type === 'boolean') {
				return value
					? this.translate('COM_EMUNDUS_EXPORT_RESUME_VALUE_YES')
					: this.translate('COM_EMUNDUS_EXPORT_RESUME_VALUE_NO');
			}

			// Pivot target holds a raw id — resolve to a human label using the current
			// scope and the elements the user picked in the Content step.
			if (field.name === 'pivot_target') {
				return this.resolvePivotTargetLabel(value);
			}

			// Regular ChoiceField (e.g. pivot_scope, language) — look up the picked choice's label.
			if (field.type === 'choice' && Array.isArray(field.choices)) {
				const choice = field.choices.find((c) => String(c.value) === String(value));
				if (choice) return this.translate(choice.label);
			}

			return value;
		},

		resolvePivotTargetLabel(targetId) {
			const scope = this.exportSettings?.pivot_scope;
			if (!scope) return targetId;

			const eq = (a, b) => String(a) === String(b);
			const unnamed = () => this.translate('COM_EMUNDUS_FORM_BUILDER_UNNAMED_SECTION');

			switch (scope) {
				case 'element': {
					const el = this.selectedElements.find((e) => eq(e.id, targetId));
					return el ? el.label : targetId;
				}
				case 'group': {
					const el = this.selectedElements.find((e) => eq(e.group_id, targetId));
					return el ? el.group_label || unnamed() : targetId;
				}
				case 'evaluation': {
					const el = this.selectedElements.find((e) => eq(e.form_id, targetId));
					return el ? el.form_label || unnamed() : targetId;
				}
				default:
					return targetId;
			}
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-w-full tw-flex-col tw-gap-8 tw-overflow-y-auto">
		<div class="tw-flex tw-flex-col tw-gap-3">
			<h3 class="tw-font-light">
				{{ translate('COM_EMUNDUS_EXPORT_RESUME_FORMAT_AND_OPTIONS') }}
			</h3>

			<div class="tw-flex tw-w-full tw-flex-col">
				<div class="tw-flex tw-w-full tw-items-start tw-gap-3 tw-text-base">
					<p class="tw-m-0 tw-flex-1 tw-font-bold">
						{{ translate('COM_EMUNDUS_EXPORT_RESUME_SELECTED_FORMAT') }}
					</p>
					<p class="tw-m-0 tw-flex-1 tw-font-medium">{{ formatLabel }}</p>
				</div>

				<template v-for="row in optionRows" :key="row.name">
					<hr class="tw-my-1" />
					<div class="tw-flex tw-w-full tw-items-start tw-gap-3 tw-text-base">
						<p class="!tw-m-0 tw-flex-1 tw-font-bold">{{ translate(row.label) }}</p>
						<p class="!tw-m-0 tw-flex-1 tw-truncate tw-font-medium">{{ row.value }}</p>
					</div>
				</template>
			</div>
		</div>

		<div class="tw-flex tw-w-full tw-flex-col tw-gap-6">
			<h3 class="tw-font-light">{{ translate('COM_EMUNDUS_EXPORT_RESUME_CONTENT') }} ({{ totalContentCount }})</h3>

			<div class="tw-flex tw-w-full tw-flex-col">
				<TabList
					v-model="activeContentTab"
					:aria-label="translate('COM_EMUNDUS_EXPORT_RESUME_CONTENTS_ARIA_LABEL')"
					class="tw-ml-4"
				>
					<Tab
						v-for="tab in contentTabs"
						:value="tab.code"
						:tab-id="'tab-' + tab.code"
						:controls="'panel-' + tab.code"
						:label="translate(tab.label) + ' (' + tab.items.length + ')'"
					/>
				</TabList>

				<div
					class="tw-flex tw-w-full tw-flex-col tw-gap-3 tw-rounded-2xl tw-border tw-border-neutral-400 tw-bg-white tw-p-6"
				>
					<template
						v-if="activeContentItems.length > 0"
						:id="'panel-' + activeContentTab"
						role="tabpanel"
						:aria-labelledby="'tab-' + activeContentTab"
					>
						<div
							v-for="item in activeContentItems"
							:key="item.id"
							class="tw-flex tw-w-full tw-items-center tw-gap-3 tw-rounded-lg tw-border tw-border-profile-light tw-bg-white tw-p-2"
						>
							<div class="tw-flex tw-min-w-px tw-flex-1 tw-flex-col">
								<span>{{ item.label || item.plugin_name }}</span>
								<span class="tw-text-xs tw-text-neutral-500">{{ item.plugin_name }}</span>
							</div>
							<span
								v-if="item.tab_label || item.category_label || item.menu_label"
								class="tw-rounded-full tw-bg-neutral-400 tw-px-2 tw-py-1 tw-text-xs tw-text-white"
							>
								{{ item.tab_label || item.category_label || item.menu_label }}
							</span>
						</div>
					</template>

					<div v-else v-html="translate('COM_EMUNDUS_EXPORT_NO_CONTENT_SELECTED')" />
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
