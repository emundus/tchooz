<template>
	<div id="form-builder-page-properties">
		<div class="tw-flex tw-items-center tw-justify-between tw-p-4">
			<p>{{ translate('COM_EMUNDUS_FORM_BUILDER_PAGE_PROPERTIES') }}</p>
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
			<div v-if="tabs[0].active" id="page-parameters" class="tw-p-4">
				<div class="tw-mb-4">
					<label for="page-label">{{ translate('COM_EMUNDUS_FORM_BUILDER_PAGE_LABEL') }}</label>
					<input id="page-label" name="page-label" class="tw-w-full" type="text" v-model="page_tmp.label" />
				</div>

				<div class="tw-mb-4">
					<label for="page-intro">{{ translate('COM_EMUNDUS_FORM_BUILDER_PAGE_INTRO') }}</label>
					<textarea id="page-into" name="page-intro" class="tw-w-full" v-model="page_tmp.intro" />
				</div>
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
</template>

<script>
import formBuilderService from '@/services/formbuilder.js';
import sectionParams from '../../../data/form-builder/form-builder-groups-params.json';
import FormBuilderSectionParams from '@/components/FormBuilder/FormBuilderSections/FormBuilderSectionParams.vue';
import { useGlobalStore } from '@/stores/global';

export default {
	name: 'FormBuilderPageProperties',
	components: {},
	props: {
		page: {
			required: true,
		},
		profile_id: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			page_tmp: {},
			tabs: [
				{
					id: 0,
					label: 'COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL',
					active: true,
					published: true,
				},
			],
		};
	},
	setup() {
		return {
			globalStore: useGlobalStore(),
		};
	},
	created() {
		this.getPage();
	},
	methods: {
		saveProperties() {
			formBuilderService
				.updatePageParams(this.page_tmp.label, this.page_tmp.intro, this.page_tmp.id, this.shortDefaultLang)
				.then(() => {
					this.page.label = this.page_tmp.label;
					this.$emit('close');
				});
		},
		selectTab(tab) {
			this.tabs.forEach((t) => {
				t.active = false;
			});
			tab.active = true;
		},
		getPage() {
			this.page_tmp = this.$props.page;

			this.focusLabel();
		},
		focusLabel() {
			this.$nextTick(() => {
				const labelInput = document.getElementById('page-label');
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
		page: function () {
			this.getPage();
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
