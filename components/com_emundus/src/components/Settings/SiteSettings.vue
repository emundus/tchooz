<template>
	<div class="em-settings-menu">
		<div class="tw-w-full">
			<div class="tw-w-4/5 tw-flex tw-flex-col" v-if="!loading">
				<div v-for="parameter in displayedParams" class="form-group tw-w-full tw-mb-7" :key="parameter.param">
					<Parameter
						v-if="
							(parameter.type === 'multiselect' && parameter.multiselectOptions) || parameter.type !== 'multiselect'
						"
						:parameter-object="parameter"
						:multiselect-options="parameter.multiselectOptions ? parameter.multiselectOptions : null"
						@needSaving="updateParameterToSaving"
					/>
				</div>

				<Global v-if="displayLanguage === true" />
			</div>

			<button class="btn btn-primary tw-float-right" v-if="parametersUpdated.length > 0" @click="saveSiteSettings">
				{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE') }}
			</button>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import Parameter from '@/components/Utils/Parameter.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import Global from '@/components/Settings/TranslationTool/Global.vue';
import settingsService from '../../services/settings';

const assetsPath = '/components/com_emundus/src/assets/data/';
const getPath = (path) => `${assetsPath}${path}`;

export default {
	name: 'SiteSettings',
	components: { Global, Parameter },
	props: {
		json_source: {
			type: String,
			required: true,
		},
		displayLanguage: {
			type: Boolean,
			default: false,
		},
	},

	mixins: [],

	data() {
		return {
			parameters: [],
			parametersUpdated: [],

			loading: true,
			config: {},
		};
	},
	created() {
		import(getPath(this.$props.json_source)).then((result) => {
			if (result) {
				this.parameters = result.default;

				for (let i = 0; i < this.parameters.length; i++) {
					if (this.parameters[i].param == 'offset') {
						settingsService.getTimezoneList().then((response) => {
							if (response.status) {
								this.parameters[i].multiselectOptions = {
									options: response.data,
									noOptions: false,
									multiple: false,
									taggable: false,
									searchable: true,
									label: 'label',
									trackBy: 'value',
									optionsPlaceholder: '',
									selectLabel: '',
									selectGroupLabel: '',
									selectedLabel: '',
									deselectedLabel: '',
									deselectGroupLabel: '',
									noOptionsText: '',
									tagValidations: [],
								};
							}
						});
					}
				}
			}
		});

		this.getEmundusParams();
	},
	mounted() {},
	methods: {
		getEmundusParams() {
			axios.get('index.php?option=com_emundus&controller=settings&task=getemundusparams').then((response) => {
				this.config = response.data;

				Object.values(this.parameters).forEach((parameter) => {
					if (parameter.type === 'keywords') {
						if (this.config[parameter.component][parameter.param]) {
							let keywords = this.config[parameter.component][parameter.param].split(',');
							parameter.value = keywords.map((keyword) => {
								return {
									name: keyword,
									code: keyword,
								};
							});
						}
					} else {
						parameter.value = this.config[parameter.component][parameter.param];
					}

					if (parameter.value === '1' || parameter.value === true || parameter.value === 'true') {
						parameter.value = 1;
					}

					if (parameter.value === '0' || parameter.value === false || parameter.value === 'false') {
						parameter.value = 0;
					}
				});

				this.loading = false;
			});
		},

		updateParameterToSaving(needSaving, parameter) {
			if (needSaving) {
				let checkExisting = this.parametersUpdated.find((param) => param.param === parameter.param);
				if (!checkExisting) {
					this.parametersUpdated.push(parameter);
				}
			} else {
				this.parametersUpdated = this.parametersUpdated.filter((param) => param.param !== parameter.param);
			}
		},

		displayHelp(message) {
			Swal.fire({
				title: this.translate('COM_EMUNDUS_SWAL_HELP_TITLE'),
				text: this.translate(message),
				showCancelButton: false,
				confirmButtonText: this.translate('COM_EMUNDUS_SWAL_OK_BUTTON'),
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					actions: 'em-swal-single-action',
				},
			});
		},

		async saveSiteSettings() {
			let params = [];
			this.parametersUpdated.forEach((param) => {
				params.push({
					component: param.component,
					param: param.param,
					value: param.value,
				});
			});

			settingsService
				.saveParams(params)
				.then(() => {
					this.parametersUpdated = [];
					Swal.fire({
						title: this.translate('COM_EMUNDUS_ONBOARD_SUCCESS'),
						text: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_SUCCESS'),
						showCancelButton: false,
						showConfirmButton: false,
						customClass: {
							title: 'em-swal-title',
						},
						timer: 2000,
					});
				})
				.catch(() => {
					Swal.fire({
						title: this.translate('COM_EMUNDUS_ERROR'),
						text: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_ERROR'),
						showCancelButton: false,
						confirmButtonText: this.translate('COM_EMUNDUS_SWAL_OK_BUTTON'),
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
					});
				});
		},

		async saveMethod() {
			await this.saveSiteSettings();
			return true;
		},
	},
	computed: {
		displayedParams() {
			return this.parameters.filter((param) => param.displayed === true);
		},
	},
	watch: {
		activeSection: function (val) {
			this.$emit('sectionSelected', this.sections[val]);
		},
		parametersUpdated: {
			handler: function (val) {
				this.$emit('needSaving', val.length > 0);
			},
			deep: true,
		},
	},
};
</script>

<style scoped></style>
