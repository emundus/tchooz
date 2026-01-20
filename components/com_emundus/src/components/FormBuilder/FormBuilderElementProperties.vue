<template>
	<div id="form-builder-element-properties" class="tw-h-full">
		<div v-if="!loading">
			<div class="tw-flex tw-items-center tw-justify-between tw-p-4">
				<div>
					<p>{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES') }}</p>
					<span class="tw-text-sm tw-text-neutral-700">
						{{ element.label !== '' ? element.label + ' - ' : '' }}
						{{ translate('COM_EMUNDUS_ONBOARD_TYPE_' + element.plugin.toUpperCase()) }}</span
					>
				</div>
				<span class="material-symbols-outlined tw-cursor-pointer" @click="$emit('close')">close</span>
			</div>

			<ul id="properties-tabs" class="tw-flex tw-w-full tw-items-center tw-justify-between tw-p-4">
				<li
					v-for="tab in publishedTabs"
					:key="tab.id"
					:class="{
						'em-light-tabs em-light-selected-tab': tab.active,
						'em-light-tabs': !tab.active,
						'tw-w-2/4': publishedTabs.length === 2,
						'tw-w-full': publishedTabs.length === 1,
					}"
					class="tw-cursor-pointer tw-p-4"
					@click="selectTab(tab)"
				>
					{{ translate(tab.label) }}
				</li>
			</ul>

			<div id="properties">
				<!-- General properties -->
				<div v-show="tabs[0].active" id="element-parameters" class="tw-p-4">
					<!-- Element label -->
					<label for="element-label">{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_LABEL') }}</label>
					<input id="element-label" name="element-label" class="tw-w-full" type="text" v-model="element.label" />

					<!-- Help text -->
					<div v-if="element.params" class="tw-mt-4">
						<label for="element-rollover">{{ translate('COM_EMUNDUS_ONBOARD_BUILDER_HELPTEXT') }}</label>
						<input id="element-rollover" name="element-alias" type="text" v-model="element.params.rollover" />
					</div>

					<!-- Publish/Unpublish -->
					<div class="tw-flex tw-w-full tw-items-center tw-justify-between tw-pb-4 tw-pt-4">
						<span>{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_UNPUBLISH') }}</span>
						<div class="em-toggle">
							<input type="checkbox" class="em-toggle-check" v-model="isPublished" @click="togglePublish" />
							<strong class="b em-toggle-switch"></strong>
							<strong class="b em-toggle-track"></strong>
						</div>
					</div>

					<!-- Mandatory/Optional -->
					<div
						class="tw-flex tw-w-full tw-items-center tw-justify-between tw-pb-4 tw-pt-4"
						v-show="!['display', 'panel'].includes(this.element.plugin)"
					>
						<span>{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_REQUIRED') }}</span>
						<div class="em-toggle">
							<input
								type="checkbox"
								class="em-toggle-check"
								v-model="element.FRequire"
								@click="element.FRequire = !element.FRequire"
							/>
							<strong class="b em-toggle-switch"></strong>
							<strong class="b em-toggle-track"></strong>
						</div>
					</div>

					<!-- OPTIONS -->
					<div v-if="['radiobutton', 'checkbox', 'dropdown'].includes(element.plugin)">
						<hr />
						<form-builder-element-options
							ref="form-builder-element-options"
							:key="'form_builder_element_options_' + reloadOptions"
							:element="element"
							:type="element.plugin === 'radiobutton' ? 'radio' : element.plugin"
							:display-values="true"
							:dynamically-save="false"
							@update-element="$emit('update-element')"
						></form-builder-element-options>
					</div>

					<!-- Advanced formatting for panel only -->
					<div
						class="tw-flex tw-w-full tw-items-center tw-justify-between tw-pb-4 tw-pt-4"
						v-show="this.element.plugin === 'panel'"
					>
						<span>{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_ADVANCED_FORMAT') }}</span>
						<div class="em-toggle">
							<input
								type="checkbox"
								true-value="1"
								false-value="0"
								class="em-toggle-check"
								v-model="element.eval"
								@click="element.eval == 1 ? (element.eval = 0) : (element.eval = 1)"
							/>
							<strong class="b em-toggle-switch"></strong>
							<strong class="b em-toggle-track"></strong>
						</div>
					</div>

					<!-- Content for panel only -->
					<div class="tw-w-full tw-pb-4 tw-pt-4" v-show="this.element.plugin === 'panel'">
						<label for="element-default">{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_CONTENT') }}</label>

						<textarea
							v-if="element.eval == 0"
							id="element-default"
							name="element-default"
							v-model="element.default"
							class="tw-w-full tw-resize-y"
						></textarea>
						<tip-tap-editor
							v-if="element.eval == 1"
							v-model="element.default"
							:id="'element-default'"
							:upload-url="'/index.php?option=com_emundus&controller=settings&task=uploadmedia'"
							:editor-content-height="'30em'"
							:class="'tw-mt-1'"
							:locale="'fr'"
							:preset="'custom'"
							:plugins="editorPlugins"
							:toolbar-classes="['tw-bg-white']"
							:editor-content-classes="['tw-bg-white']"
						/>
					</div>
				</div>

				<!-- Advanced settings -->
				<div v-show="tabs[1].active" class="tw-flex tw-flex-col tw-gap-3 tw-p-4">
					<div v-if="element.params">
						<label for="element-alias">{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_ALIAS') }}</label>
						<input
							id="element-alias"
							name="element-alias"
							type="text"
							v-model="element.params.alias"
							@keyup="formatAlias"
						/>
						<p class="tw-mt-1 tw-text-xs">{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_ALIAS_HELPTEXT') }}</p>
					</div>

					<div class="tw-flex tw-w-full tw-justify-between" v-if="sysadmin">
						<span>{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_HIDDEN') }}</span>
						<div class="em-toggle">
							<input type="checkbox" class="em-toggle-check" v-model="isHidden" @click="toggleHidden" />
							<strong class="b em-toggle-switch"></strong>
							<strong class="b em-toggle-track"></strong>
						</div>
					</div>

					<div class="tw-flex tw-w-full tw-justify-between">
						<span>{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_SHOW_IN_LIST_SUMMARY') }}</span>
						<div class="em-toggle">
							<input
								true-value="1"
								false-value="0"
								type="checkbox"
								class="em-toggle-check"
								id="show-in-list-summary"
								name="show-in-list-summary"
								v-model="element.show_in_list_summary"
								@click="toggleShowInList"
							/>
							<strong class="b em-toggle-switch"></strong>
							<strong class="b em-toggle-track"></strong>
						</div>
					</div>

					<FormBuilderElementParams :element="element" :params="params" :key="element.id" :databases="databases" />
				</div>
			</div>

			<div class="actions tw-m-4 tw-flex tw-items-center tw-justify-between">
				<button class="tw-btn-secondary tw-w-auto tw-rounded-coordinator" @click="$emit('close')">
					{{ translate('COM_EMUNDUS_CLOSE') }}
				</button>
				<button class="tw-btn-primary tw-w-auto tw-rounded-coordinator" @click="saveProperties()">
					{{ translate('COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_SAVE') }}
				</button>
			</div>
		</div>

		<Loader v-else />
	</div>
</template>

<script>
import elementParams from '../../../data/form-builder/form-builder-elements-params.json';

import formBuilderService from '@/services/formbuilder.js';
import FormBuilderElementParams from '@/components/FormBuilder/FormBuilderElements/FormBuilderElementParams.vue';
import TipTapEditor from 'tip-tap-editor';
import 'tip-tap-editor/tip-tap-editor.css';
import '../../../../../templates/g5_helium/css/editor.css';

import { useGlobalStore } from '@/stores/global.js';

import formBuilderMixin from '@/mixins/formbuilder.js';
import FormBuilderElementOptions from '@/components/FormBuilder/FormBuilderSectionSpecificElements/FormBuilderElementOptions.vue';
import Loader from '@/components/Atoms/Loader.vue';

export default {
	name: 'FormBuilderElementProperties',
	components: {
		Loader,
		FormBuilderElementOptions,
		FormBuilderElementParams,
		TipTapEditor,
	},
	props: {
		element: {
			type: Object,
			required: true,
		},
		profile_id: {
			type: Number,
			required: true,
		},
	},
	mixins: [formBuilderMixin],
	data() {
		return {
			databases: [],
			params: [],

			tabs: [
				{
					id: 0,
					label: 'COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL',
					active: true,
					published: true,
				},
				{
					id: 1,
					label: 'COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_PARAMETERS',
					active: false,
					published: true,
				},
			],

			editorPlugins: [
				'history',
				'link',
				'image',
				'bold',
				'italic',
				'underline',
				'left',
				'center',
				'right',
				'h1',
				'h2',
				'ul',
			],

			reloadOptions: 0,
			loading: false,
		};
	},
	setup() {
		return {
			globalStore: useGlobalStore(),
		};
	},
	mounted() {
		if (this.element.plugin === 'databasejoin') {
			this.getDatabases();
		}

		this.paramsAvailable();
		this.focusLabel();
	},
	methods: {
		getDatabases() {
			this.loading = true;

			formBuilderService.getDatabases().then((response) => {
				if (response.status) {
					this.databases = response.data;

					this.loading = false;
				}
			});
		},
		saveProperties() {
			this.loading = true;
			if (
				['radiobutton', 'checkbox', 'dropdown'].includes(this.element.plugin) &&
				this.$refs['form-builder-element-options']
			) {
				this.element.params.sub_options.sub_values = [];
				this.element.params.sub_options.sub_labels = [];

				for (const option of this.$refs['form-builder-element-options'].arraySubValues) {
					this.element.params.sub_options.sub_values.push(option.sub_value);
					this.element.params.sub_options.sub_labels.push(option.sub_label);
				}
			}

			formBuilderService.updateParams(this.element).then((response) => {
				if (response.status) {
					this.loading = false;
					this.updateLastSave();
					this.$emit('close');

					if (['radiobutton', 'checkbox', 'dropdown'].includes(this.element.plugin)) {
						formBuilderService.getJTEXTA(this.element.params.sub_options.sub_labels).then((response) => {
							if (response) {
								this.element.params.sub_options.sub_labels.forEach((label, index) => {
									this.element.params.sub_options.sub_labels[index] = Object.values(response.data)[index];
								});
							}

							this.loading = false;
							this.updateLastSave();
							this.$emit('close');
						});
					}
				}
			});
		},
		togglePublish() {
			this.element.publish = !this.element.publish;
		},
		toggleHidden() {
			this.element.hidden = !this.element.hidden;
		},
		toggleShowInList() {
			this.element.show_in_list_summary = this.element.show_in_list_summary == 1 ? 0 : 1;
		},
		selectTab(tab) {
			this.tabs.forEach((t) => {
				t.active = false;
			});
			tab.active = true;
		},
		paramsAvailable() {
			if (typeof elementParams[this.element.plugin] !== 'undefined') {
				this.tabs[1].published = true;
				this.params = elementParams[this.element.plugin];
			} else {
				this.tabs[1].active = false;
				this.tabs[0].active = true;
				this.tabs[1].published = false;
			}
		},
		formatAlias() {
			this.element.alias = this.element.alias.toLowerCase().replace(/ /g, '_');
			this.element.alias = this.element.alias.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
			this.element.alias = this.element.alias.replace(/[^a-z0-9_]/g, '');
		},
		focusLabel() {
			this.$nextTick(() => {
				const labelInput = document.getElementById('element-label');
				if (labelInput) {
					labelInput.focus();
				}
			});
		},
	},
	computed: {
		isPublished() {
			return !this.element.publish;
		},
		isHidden() {
			return this.element.hidden == 1;
		},
		sysadmin: function () {
			return parseInt(this.globalStore.hasSysadminAccess);
		},
		publishedTabs() {
			return this.tabs.filter((tab) => {
				return tab.published;
			});
		},
	},
	watch: {
		'element.eval': function (value) {
			if (value == 0 && this.element.default && this.element.default[this.shortDefaultLang]) {
				this.element.default[this.shortDefaultLang] = this.element.default[this.shortDefaultLang].replace(/<p>/g, '\n');
				this.element.default[this.shortDefaultLang] = this.element.default[this.shortDefaultLang].replace(
					/(<([^>]+)>)/gi,
					'',
				);
			}
		},

		'element.id': function (value) {
			this.paramsAvailable();
			this.reloadOptions++;

			this.focusLabel();
		},
	},
};
</script>

<style lang="scss">
#properties-tabs {
	list-style-type: none;
	margin: auto;
	align-items: center;

	li {
		text-align: center;
		transition: all 0.3s;
	}
}

.em-toggle {
	min-width: 45px !important;
}
</style>
