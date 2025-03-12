<template>
	<div class="em-settings-menu">
		<div class="tw-w-full">
			<div class="tw-w-5/6">
				<div class="tw-mb-4 tw-grid tw-grid-cols-3 tw-gap-6">
					<multiselect
						v-model="selectedColumn"
						label="label"
						track-by="index"
						:options="columns"
						:multiple="false"
						:taggable="false"
						select-label=""
						selected-label=""
						deselect-label=""
						:placeholder="translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_COLUMN')"
						:close-on-select="true"
						:clear-on-select="false"
						:searchable="false"
						:allow-empty="true"
					></multiselect>
				</div>

				<div class="form-group controls" v-if="selectedColumn.index === 0 && this.form.content.col1 != null">
					<tip-tap-editor
						v-model="form.content.col1"
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
				<div class="form-group controls" v-if="selectedColumn.index === 1 && this.form.content.col2 != null">
					<tip-tap-editor
						v-model="form.content.col2"
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
				<button class="btn btn-primary tw-float-right tw-mt-3" v-if="updated" @click="saveMethod">
					{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE') }}
				</button>
			</div>
			<div class="em-page-loader" v-if="loading"></div>
		</div>
	</div>
</template>

<script>
/* COMPONENTS */
import Multiselect from 'vue-multiselect';
import TipTapEditor from 'tip-tap-editor';
import 'tip-tap-editor/style.css';
import '../../../../../../templates/g5_helium/css/editor.css';

/* SERVICES */
import client from '@/services/axiosClient.js';
import mixin from '@/mixins/mixin.js';

export default {
	name: 'EditFooter',

	components: {
		Multiselect,
		TipTapEditor,
	},

	props: {
		actualLanguage: String,
	},

	mixins: [mixin],

	data() {
		return {
			loading: false,
			dynamicComponent: 0,
			selectedColumn: 0,
			updated: false,
			initcol1: '',
			initcol2: '',

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

			form: {
				content: {
					col1: null,
					col2: null,
				},
			},
			columns: [
				{
					index: 0,
					label: this.translate('COM_EMUNDUS_ONBOARD_COLUMN') + ' 1',
				},
				{
					index: 1,
					label: this.translate('COM_EMUNDUS_ONBOARD_COLUMN') + ' 2',
				},
			],
		};
	},

	created() {
		this.loading = true;
		this.getArticles();
		this.selectedColumn = this.columns[0];
	},

	methods: {
		async getArticles() {
			await client()
				.get('index.php?option=com_emundus&controller=settings&task=getfooterarticles')
				.then((response) => {
					this.initcol1 = response.data.data.column1;
					this.initcol2 = response.data.data.column2;

					this.form.content.col1 = this.initcol1;
					this.form.content.col2 = this.initcol2;
					this.loading = false;
				});
		},

		async saveMethod() {
			this.$emit('updateSaving', true);

			const formData = new FormData();
			formData.append('col1', this.form.content.col1);
			formData.append('col2', this.form.content.col2);

			await client()
				.post(`index.php?option=com_emundus&controller=settings&task=updatefooter`, formData, {
					headers: {
						'Content-Type': 'multipart/form-data',
					},
				})
				.then(() => {
					this.$emit('updateSaving', false);
					this.$emit('updateLastSaving', this.formattedDate('', 'LT'));
					this.$emit('updatePublished', this.form.published);
					this.updated = false;
					Swal.fire({
						title: this.translate('COM_EMUNDUS_ONBOARD_SUCCESS'),
						text: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_SUCCESS'),
						showCancelButton: false,
						showConfirmButton: false,
						customClass: {
							title: 'em-swal-title',
						},
						timer: 1500,
					});
					this.initcol1 = this.form.content.col1;
					this.initcol2 = this.form.content.col2;
				});
		},
	},

	watch: {
		selectedColumn: function () {
			this.dynamicComponent++;
		},
		'form.content.col1': function (val, oldVal) {
			if (oldVal !== null) {
				if (val !== oldVal) {
					this.form.content.col1 = val;
					this.updated = true;
				}
			}
		},
		'form.content.col2': function (val, oldVal) {
			if (oldVal !== null) {
				if (val !== oldVal) {
					this.form.content.col1 = val;
					this.updated = true;
				}
			}
		},
		updated: function (val) {
			this.$emit('needSaving', val);
		},
	},
};
</script>
<style scoped></style>
