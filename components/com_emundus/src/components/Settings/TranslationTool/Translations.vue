<template>
	<div>
		<h3 class="tw-mb-2">{{ this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS') }}</h3>
		<p class="em-h-25 tw-mb-6 tw-text-base tw-text-neutral-700">
			{{ this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE') }}
		</p>

		<p class="em-h-25 tw-mb-6 tw-text-base" v-if="availableLanguages.length === 0 && !loading">
			{{ this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_NO_LANGUAGES_AVAILABLE') }}
		</p>

		<div class="em-grid-4" v-else>
			<!-- Languages -->
			<div>
				<multiselect
					v-model="lang"
					label="title_native"
					track-by="lang_code"
					:options="availableLanguages"
					:multiple="false"
					:taggable="false"
					select-label=""
					selected-label=""
					deselect-label=""
					:placeholder="this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_LANGUAGE')"
					:close-on-select="true"
					:clear-on-select="false"
					:searchable="false"
					:allow-empty="true"
					@select="getObjects"
				></multiselect>
			</div>

			<!-- Objects availables -->
			<div v-if="lang" v-show="displayFilters">
				<multiselect
					v-model="object"
					label="name"
					track-by="name"
					:options="objects"
					:multiple="false"
					:taggable="false"
					select-label=""
					selected-label=""
					deselect-label=""
					:placeholder="this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_OBJECT')"
					:close-on-select="true"
					:clear-on-select="false"
					:searchable="false"
					:allow-empty="true"
				></multiselect>
			</div>

			<!-- Datas by reference id -->
			<div v-if="object" v-show="displayFilters">
				<multiselect
					v-model="data"
					label="label"
					track-by="id"
					:options="datas"
					:multiple="false"
					:taggable="false"
					select-label=""
					selected-label=""
					deselect-label=""
					:placeholder="this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT')"
					:close-on-select="true"
					:clear-on-select="false"
					:searchable="true"
					:allow-empty="true"
				></multiselect>
			</div>

			<!-- Childrens -->
			<div v-if="childrens.length > 0" v-show="displayFilters">
				<multiselect
					v-model="children"
					label="label"
					track-by="id"
					:options="childrens"
					:multiple="false"
					:taggable="false"
					select-label=""
					selected-label=""
					deselect-label=""
					:placeholder="this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT')"
					:close-on-select="true"
					:clear-on-select="false"
					:searchable="true"
					:allow-empty="true"
				></multiselect>
			</div>
		</div>

		<hr class="col-md-12" style="z-index: 0" />

		<div class="col-md-12">
			<div
				v-if="lang === '' || lang == null || object === '' || object == null || init_translations === false"
				class="text-center tw-mt-4"
			>
				<img
					src="@media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg"
					alt="empty-list"
					style="width: 10vw; height: 10vw; margin: 0 auto"
				/>
				<h5 class="tw-mb-2">{{ this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_TRANSLATION_TITLE') }}</h5>
				<p class="em-text-neutral-600 tw-text-base">
					{{ this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_TRANSLATION_TEXT') }}
				</p>
			</div>

			<div v-else>
				<button
					v-if="object.table.name === 'emundus_setup_profiles'"
					class="float-right em-profile-color em-text-underline"
					@click="exportToCsv"
				>
					{{ this.translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_EXPORT') }}
				</button>

				<div v-for="section in object.fields.Sections" :key="section.Table" class="tw-mb-8">
					<h4 class="mb-2">{{ section.Label }}</h4>

					<TranslationRow :section="section" :translations="translations" @saveTranslation="saveTranslation" />
				</div>
			</div>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import client from '@/services/axiosClient.js';
import translationsService from '@/services/translations.js';
import Multiselect from 'vue-multiselect';
import TranslationRow from './TranslationRow.vue';

import mixin from '@/mixins/mixin.js';
import errors from '@/mixins/errors.js';

export default {
	name: 'Translations',
	components: {
		TranslationRow,
		Multiselect,
	},
	props: {
		objectValue: {
			type: String,
			required: false,
		},
		dataValue: {
			type: String,
			required: false,
		},
		childrenValue: {
			type: String,
			required: false,
		},
		displayFilters: {
			type: Boolean,
			required: false,
			default: true,
		},
	},
	mixins: [mixin, errors],
	data() {
		return {
			defaultLang: null,
			availableLanguages: [],

			// Lists
			objects: [],
			datas: [],
			childrens: [],
			translations: {},

			// Values
			lang: null,
			object: null,
			data: null,
			children_type: null,
			children: null,

			loading: false,
			init_translations: false,
			firstLoadObjects: true,
			firstLoadDatas: true,
		};
	},

	created() {
		this.loading = true;
		translationsService.getDefaultLanguage().then((response) => {
			this.defaultLang = response;
			this.getAllLanguages().then(() => {
				this.loading = false;
			});
		});
	},

	methods: {
		async getAllLanguages() {
			try {
				const response = await client().get('index.php?option=com_emundus&controller=translations&task=getlanguages');
				this.allLanguages = response.data;
				for (const lang of this.allLanguages) {
					if (lang.lang_code !== this.defaultLang.lang_code) {
						if (lang.published == 1) {
							this.availableLanguages.push(lang);
						}
					}
				}

				if (this.availableLanguages.length === 1) {
					this.lang = this.availableLanguages[0];
					await this.getObjects();
				}
			} catch (e) {
				this.loading = false;
				return false;
			}
		},

		async getObjects() {
			this.loading = true;
			this.translations = [];
			this.childrens = [];
			this.datas = [];
			this.objects = [];
			this.object = null;
			this.data = null;
			this.children = null;

			translationsService.getObjects().then((response) => {
				this.objects = response.data;

				if (this.firstLoadObjects) {
					// get url parameter object
					const urlParams = new URLSearchParams(window.location.search);

					const object = urlParams.get('object');
					if (object) {
						this.object = this.objects.find((obj) => obj.table.name === object);
					}

					this.firstLoadObjects = false;
				}

				this.loading = false;
			});
		},

		async getDatas(value) {
			this.loading = true;

			translationsService
				.getDatas(value.table.name, value.table.reference, value.table.label, value.table.filters)
				.then(async (response) => {
					if (response.status) {
						if (response.data.length > 0) {
							this.datas = response.data;

							if (value.table.load_all === 'true') {
								let fields = [];
								await this.asyncForEach(this.object.fields.Fields, async (field) => {
									fields.push(field.Name);
								});
								fields = fields.join(',');
								const build = async () => {
									for (const data of this.datas) {
										await translationsService
											.getTranslations(
												this.object.table.type,
												this.defaultLang.lang_code,
												this.lang.lang_code,
												data.id,
												fields,
												this.object.table.name,
											)
											.then(async (rep) => {
												if (rep.status) {
													for (const translation of Object.values(rep.data)) {
														this.translations[data.id] = {};
														this.object.fields.Fields.forEach((field) => {
															this.translations[data.id][field.Name] = translation[field.Name];
														});
													}
												} else {
													this.displayError(rep.message, '');
												}
											});
									}
									this.init_translations = true;
									this.loading = false;
								};
								await build();
							} else if (value.table.load_first_data === 'true') {
								if (this.firstLoadDatas) {
									// get url parameter data
									const urlParams = new URLSearchParams(window.location.search);

									const dataParam = urlParams.get('data');
									if (dataParam) {
										this.data = this.datas.find((d) => parseInt(d.id) === parseInt(dataParam));
									} else {
										this.data = this.datas[0];
									}

									this.firstLoadDatas = false;
								} else {
									this.data = this.datas[0];
								}
							} else {
								this.loading = false;
							}
						} else {
							this.loading = false;
						}
					} else {
						this.loading = false;
					}
				});
		},

		async getTranslations(value) {
			let fields = [];
			this.object.fields.Fields.forEach((field) => {
				fields.push(field.Name);
			});
			fields = fields.join(',');

			translationsService
				.getTranslations(
					this.object.table.type,
					this.defaultLang.lang_code,
					this.lang.lang_code,
					value.id,
					fields,
					this.object.table.name,
				)
				.then((response) => {
					this.translations = response.data;
					this.init_translations = true;
					this.loading = false;
				});
		},

		async saveTranslation({ value, translation }) {
			this.$emit('updateSaving', true);
			translationsService
				.updateTranslations(
					value,
					this.object.table.type,
					this.lang.lang_code,
					translation.reference_id,
					translation.tag,
					translation.reference_table,
					translation.reference_field,
				)
				.then((response) => {
					if (response.status) {
						this.$emit('updateLastSaving', this.formattedDate('', 'LT'));
						this.$emit('updateSaving', false);
					} else {
						console.error(response.msg);
					}
				});
		},

		async exportToCsv() {
			window.open(
				'/index.php?option=com_emundus&controller=translations&task=export&profile=' + this.data.id,
				'_blank',
			);
		},
		translate(key) {
			if (typeof key != undefined && key != null && Joomla !== null && typeof Joomla !== 'undefined') {
				return Joomla.JText._(key) ? Joomla.JText._(key) : key;
			} else {
				return '';
			}
		},
	},

	watch: {
		objects: function (value) {
			if (value.length > 0) {
				if (this.objectValue) {
					this.object = this.objects.find((obj) => obj.table.name === this.objectValue);
				}
			}
		},
		object: function (value) {
			this.init_translations = false;
			this.translations = {};
			this.childrens = [];
			this.children = null;
			this.datas = [];
			this.data = null;

			if (value != null) {
				this.getDatas(value);
			}
		},
		datas: function (value) {
			if (value.length > 0) {
				if (this.dataValue) {
					this.data = this.datas.find((d) => d.id == this.dataValue);
				}
			}
		},
		data: function (value) {
			this.loading = true;
			this.init_translations = false;
			this.translations = {};
			this.childrens = [];
			this.children = null;
			this.children_type = null;

			var children_existing = false;

			if (value != null) {
				this.object.fields.Fields.forEach((field) => {
					if (field.Type === 'children') {
						this.children_type = field.Label;
						children_existing = true;
						translationsService.getChildrens(field.Label, this.data.id, field.Name).then((response) => {
							this.childrens = response.data;

							if (this.object.table.load_first_child === 'true') {
								this.children = this.childrens[0];
							}
							this.loading = false;
						});
					}
				});

				if (!children_existing) {
					this.getTranslations(value);
				}
			} else {
				this.getDatas(this.object);
			}
		},
		childrens: function (value) {
			if (value.length > 0) {
				if (this.childrenValue) {
					this.children = this.childrens.find((c) => c.id == this.childrenValue);
				}
			}
		},
		children: function (value) {
			this.loading = true;
			this.init_translations = false;
			this.translations = {};

			if (value != null) {
				let tables = [];
				this.object.fields.Sections.forEach((section) => {
					const table = {
						table: section.Table,
						join_table: section.TableJoin,
						join_column: section.TableJoinColumn,
						reference_column: section.ReferenceColumn,
						fields: Object.keys(section.indexedFields),
					};
					tables.push(table);
				});

				translationsService
					.getTranslations(
						this.object.table.type,
						this.defaultLang.lang_code,
						this.lang.lang_code,
						value.id,
						'',
						tables,
					)
					.then((response) => {
						this.translations = response.data;
						this.init_translations = true;
						this.loading = false;
					});
			} else {
				this.loading = false;
			}
		},
	},
};
</script>

<style scoped></style>
