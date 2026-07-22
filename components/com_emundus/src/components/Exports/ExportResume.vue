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
				label: field.label,
				value: this.formatValue(this.exportSettings[field.name], field.type),
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

		formatValue(value, type) {
			if (value === null || value === undefined || value === '') {
				return '—';
			}

			if (type === 'boolean') {
				return value
					? this.translate('COM_EMUNDUS_EXPORT_RESUME_VALUE_YES')
					: this.translate('COM_EMUNDUS_EXPORT_RESUME_VALUE_NO');
			}

			return value;
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
