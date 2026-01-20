<template>
	<div id="form-builder-element-properties" class="tw-h-full">
		<div v-if="!loading">
			<div class="tw-flex tw-items-center tw-justify-between tw-p-4">
				<p>{{ translate('COM_EMUNDUS_FORM_BUILDER_SECTION_PROPERTIES') }}</p>
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
				<div v-if="tabs[0].active" id="section-parameters" class="tw-p-4">
					<div class="tw-mb-4">
						<label for="section-label">{{ translate('COM_EMUNDUS_FORM_BUILDER_SECTION_LABEL') }}</label>
						<input id="section-label" name="section-label" class="tw-w-full" type="text" v-model="section_tmp.label" />
					</div>
					<form-builder-section-params :params="params" :section="section_tmp"></form-builder-section-params>
				</div>
			</div>

			<div class="actions tw-m-4 tw-flex tw-items-center tw-justify-between">
				<button class="tw-btn-secondary tw-w-auto tw-rounded-coordinator" @click="$emit('close')">
					{{ translate('COM_EMUNDUS_CLOSE') }}
				</button>
				<button class="tw-btn-primary tw-w-auto tw-rounded-coordinator" @click="saveProperties()">
					{{ translate('COM_EMUNDUS_FORM_BUILDER_SECTION_PROPERTIES_SAVE') }}
				</button>
			</div>
		</div>

		<Loader v-else />
	</div>
</template>

<script>
import formBuilderService from '@/services/formbuilder.js';
import sectionParams from '../../../data/form-builder/form-builder-groups-params.json';
import FormBuilderSectionParams from '@/components/FormBuilder/FormBuilderSections/FormBuilderSectionParams.vue';
import { useGlobalStore } from '@/stores/global';
import Loader from '@/components/Atoms/Loader.vue';

export default {
	name: 'FormBuilderSectionProperties',
	components: { Loader, FormBuilderSectionParams },
	props: {
		section_id: {
			type: Number,
			required: true,
		},
		profile_id: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			section_tmp: {},
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
					published: false,
				},
			],

			loading: false,
		};
	},
	setup() {
		return {
			globalStore: useGlobalStore(),
		};
	},
	created() {
		this.paramsAvailable();
		this.getSection();
	},
	methods: {
		saveProperties() {
			formBuilderService
				.updateGroupParams(this.section_tmp.label, this.section_tmp.id, this.section_tmp.params, this.shortDefaultLang)
				.then(() => {
					this.$emit('close');
				});
		},
		toggleHidden() {
			this.section_tmp.params.hidden = !this.section_tmp.hidden;
		},
		selectTab(tab) {
			this.tabs.forEach((t) => {
				t.active = false;
			});
			tab.active = true;
		},
		paramsAvailable() {
			if (typeof sectionParams['parameters'] !== 'undefined') {
				//this.tabs[1].published = true;
				this.params = sectionParams['parameters'];
			} else {
				//this.tabs[1].active = false;
				this.tabs[0].active = true;
				//this.tabs[1].published = false;
			}
		},
		getSection() {
			this.loading = true;

			formBuilderService.getSection(this.$props.section_id).then((response) => {
				this.section_tmp = response.group;

				this.loading = false;

				this.focusLabel();
			});
		},
		focusLabel() {
			this.$nextTick(() => {
				const labelInput = document.getElementById('section-label');
				if (labelInput) {
					labelInput.focus();
				}
			});
		},
	},
	computed: {
		sysadmin: function () {
			return parseInt(this.globalStore.sysadminAccess);
		},
		publishedTabs() {
			return this.tabs.filter((tab) => {
				return tab.published;
			});
		},
	},
	watch: {
		section_id: function () {
			this.paramsAvailable();
			this.getSection();
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
</style>
