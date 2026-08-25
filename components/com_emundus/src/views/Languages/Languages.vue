<template>
	<div id="languages-list">
		<div v-if="languageOptions.length > 1" class="tw-mb-4 tw-flex tw-justify-end">
			<Slider v-model="selectedLang" :options="languageOptions" />
		</div>

		<list
			:default-lists="configString"
			:default-type="'languages'"
			:default-filter="'lang_code=' + selectedLang"
			:key="renderingKey"
			:crud="crud"
		></list>
	</div>
</template>

<script>
import list from '@/views/List.vue';
import { Slider } from '@emundus/ui';

export default {
	name: 'Languages',
	components: {
		list,
		Slider,
	},
	props: {
		crud: {
			type: Object,
			default: () => ({}),
		},
		languages: {
			type: Array,
			default: () => [],
		},
		defaultLang: {
			type: String,
			default: '',
		},
		currentLang: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			renderingKey: 1,
			selectedLang: '',

			config: {
				languages: {
					title: 'COM_EMUNDUS_LANGUAGES',
					tabs: [
						{
							title: 'COM_EMUNDUS_LANGUAGES',
							key: 'translations',
							controller: 'language',
							getter: 'gettranslations',
							noData: 'COM_EMUNDUS_LANGUAGES_NO_TRANSLATIONS',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							actions: [
								{
									action: 'add',
									label: 'COM_EMUNDUS_LANGUAGES_ADD',
									type: 'modal',
									component: 'TranslationAdd',
									name: 'add',
									iconLabel: 'control_point',
								},
								{
									action: 'edit',
									label: 'COM_EMUNDUS_LANGUAGES_EDIT',
									type: 'modal',
									component: 'TranslationEdit',
									name: 'edit',
								},
								{
									action: 'deletetranslation',
									label: 'COM_EMUNDUS_LANGUAGES_DELETE',
									controller: 'language',
									name: 'delete',
									method: 'delete',
									confirm: 'COM_EMUNDUS_LANGUAGES_DELETE_CONFIRM',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_LANGUAGES_FILTER_USED_IN',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									alwaysDisplay: true,
									getter: 'getformsforfilter',
									controller: 'language',
									key: 'form',
									values: null,
								},
							],
						},
					],
				},
			},
		};
	},
	created() {
		this.selectedLang = this.currentLang || this.defaultLang || this.languageOptions[0]?.value || '';
	},
	computed: {
		configString() {
			return btoa(JSON.stringify(this.config));
		},
		languageOptions() {
			return this.languages.map((language) => {
				const countryCode = language.lang_code.split('-')[1]?.toLowerCase();

				return {
					label: this.stripLanguageSuffix(language.title_native),
					value: language.lang_code,
					icon: `flag_round_${countryCode}`,
				};
			});
		},
	},
	watch: {
		selectedLang() {
			this.renderingKey++;
		},
	},
	methods: {
		stripLanguageSuffix(label) {
			return label ? label.replace(/\s*\([^)]*\)\s*$/, '') : label;
		},
	},
};
</script>

<style scoped></style>
