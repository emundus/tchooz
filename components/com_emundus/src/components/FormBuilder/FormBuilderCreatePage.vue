<template>
	<div id="form-builder-create-page" class="em-p-32 tw-w-full tw-pt-4">
		<div>
			<h3 class="em-text-neutral-800 tw-mb-1">
				{{ translate('COM_EMUNDUS_FORM_BUILDER_CREATE_NEW_PAGE') }}
			</h3>
			<p>{{ translate('COM_EMUNDUS_FORM_BUILDER_CREATE_NEW_PAGE_INTRO') }}</p>
			<section id="new-page">
				<div class="card-wrapper tw-mb-4 tw-mt-4" :class="{ selected: -1 === selected }" @click="selected = -1">
					<div class="card em-shadow-cards tw-flex tw-cursor-pointer tw-items-center" @dblclick="createPage">
						<span class="add_circle material-symbols-outlined tw-text-profile-full">add_circle</span>
					</div>
					<input
						type="text"
						v-model="page.label[shortDefaultLang]"
						class="em-p-4"
						:class="{
							'tw-text-white': -1 === selected,
							'tw-bg-profile-full': -1 === selected,
						}"
					/>
				</div>
			</section>
			<div class="separator tw-mt-8">
				<p class="line-head em-mt-4 em-p-8 tw-bg-profile-full tw-text-white">
					{{ translate('COM_EMUNDUS_FORM_BUILDER_CREATE_NEW_PAGE_FROM_MODEL') }}
				</p>
				<div class="line tw-bg-profile-full"></div>
			</div>
			<section id="models" class="tw-flex tw-w-full tw-items-center">
				<div v-if="!loading" class="tw-w-full">
					<div id="search-model-wrapper">
						<input id="search-model" class="tw-mt-4" type="text" v-model="search" placeholder="Rechercher" />
						<span class="reset-search material-symbols-outlined tw-cursor-pointer" @click="search = ''">close</span>
					</div>
					<section id="structure-options">
						<div class="tw-flex tw-items-center">
							<input type="radio" id="new-structure" name="structure" value="new" v-model="structure" />
							<label for="new-structure">{{ translate('COM_EMUNDUS_FORM_BUILDER_NEW_STRUCTURE') }}</label>
						</div>
						<div class="tw-flex tw-items-center" :class="{ disabled: !canUseInitialStructure }">
							<input type="radio" id="initial-structure" name="structure" value="initial" v-model="structure" />
							<label for="initial-structure">{{ translate('COM_EMUNDUS_FORM_BUILDER_INITIAL_STRUCTURE') }}</label>
						</div>
					</section>
					<div class="models-card tw-flex tw-items-center">
						<div
							v-for="model in models"
							:key="model.id"
							class="card-wrapper em-mr-32"
							:class="{
								selected: model.id === selected,
								hidden: !model.displayed,
							}"
							:title="model.label[shortDefaultLang]"
							@click="selected = model.id"
							@dblclick="createPage"
						>
							<form-builder-preview-form
								:form_id="Number(model.form_id)"
								:form_label="model.label[shortDefaultLang]"
								class="card em-shadow-cards model-preview tw-cursor-pointer"
								:class="{
									'tw-text-white': model.id === selected,
									'tw-bg-profile-full': model.id === selected,
								}"
							>
							</form-builder-preview-form>
							<p
								class="em-p-4"
								:class="{
									'tw-text-white': model.id === selected,
									'tw-bg-profile-full': model.id === selected,
								}"
							>
								{{ model.label[shortDefaultLang] }}
							</p>
						</div>

						<div v-if="displayedModels.length < 1" class="empty-model-message tw-w-full tw-text-center">
							<span class="material-symbols-outlined">manage_search</span>
							<p class="tw-w-full">
								{{ translate('COM_EMUNDUS_FORM_BUILDER_EMPTY_PAGE_MODELS') }}
							</p>
						</div>
					</div>
				</div>
				<div v-else class="tw-w-full">
					<skeleton width="206px" height="41px" classes="tw-mt-4 tw-mb-4 tw-rounded-coordinator"></skeleton>
					<div class="models-card tw-grid">
						<div v-for="i in 16" :key="i" class="card-wrapper tw-mr-6 tw-flex tw-flex-col">
							<skeleton width="150px" height="200px" classes="card em-shadow-cards model-preview"></skeleton>
							<skeleton width="150px" height="20px" classes="em-p-4"></skeleton>
						</div>
					</div>
				</div>
			</section>
		</div>
		<div class="actions tw-flex tw-w-full tw-items-center tw-justify-between">
			<button class="tw-btn-cancel !tw-w-auto tw-bg-white" @click="close(false)">
				{{ translate('COM_EMUNDUS_FORM_BUILDER_CANCEL') }}
			</button>
			<button class="tw-btn-primary tw-ml-2 tw-w-auto" :disabled="loading" @click="createPage">
				{{ translate('COM_EMUNDUS_FORM_BUILDER_PAGE_CREATE_SAVE') }}
			</button>
		</div>
	</div>
</template>

<script>
import FormBuilderPreviewForm from '@/components/FormBuilder/FormBuilderPreviewForm.vue';
import formBuilderService from '@/services/formbuilder';
import Skeleton from '@/components/Skeleton.vue';

export default {
	name: 'FormBuilderCreatePage.vue',
	components: {
		Skeleton,
		FormBuilderPreviewForm,
	},
	props: {
		profile_id: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			selected: -1,
			models: [],
			page: {
				label: {
					fr: 'Nouvelle page',
					en: 'New page',
				},
				intro: {
					fr: '',
					en: '',
				},
				prid: this.profile_id,
				template: 0,
			},
			search: '',
			structure: 'new', // new | initial, structure means data structure, to know if we keep same database tables or not
			canUseInitialStructure: true,
		};
	},
	created() {
		this.getModels();
	},
	methods: {
		getModels() {
			formBuilderService.getModels().then((response) => {
				if (response.status) {
					this.models = response.data.map((model) => {
						model.displayed = true;

						return model;
					});
				} else {
					Swal.fire({
						type: 'warning',
						title: this.translate('COM_EMUNDUS_FORM_BUILDER_GET_PAGE_MODELS_ERROR'),
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
					});
				}

				this.loading = false;
			});
		},
		createPage() {
			this.loading = true;
			let model_form_id = -1;
			if (this.selected > 0) {
				const found_model = this.models.find((model) => {
					return model.id === this.selected;
				});

				if (found_model) {
					model_form_id = found_model.form_id;
					this.page.label = found_model.label;
					this.page.intro = found_model.intro;
				}

				if (this.structure !== 'new' && !this.canUseInitialStructure) {
					this.structure = 'new';
				}
			}

			const data = {
				...this.page,
				modelid: model_form_id,
				keep_structure: this.structure === 'initial',
			};
			formBuilderService.addPage(data).then((response) => {
				if (!response.status) {
					Swal.fire({
						type: 'error',
						title: this.translate('COM_EMUNDUS_FORM_BUILDER_CREATE_PAGE_ERROR'),
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
					});
					this.close(false);
				} else {
					this.close(true, response.id);
				}
			});
		},
		close(reload = true, newSelected = 0) {
			this.$emit('close', {
				reload: reload,
				newSelected: newSelected,
			});
		},
		isInitialStructureAlreadyUsed() {
			let used = false;

			if (this.selected !== -1) {
				const found_model = this.models.find((model) => {
					return model.id === this.selected;
				});

				if (found_model) {
					formBuilderService
						.checkIfModelTableIsUsedInForm(found_model.form_id, this.profile_id)
						.then((response) => {
							if (response.status) {
								used = response.data;
							}

							if (used) {
								this.structure = 'new';
								this.canUseInitialStructure = false;
							} else {
								this.canUseInitialStructure = true;
							}

							return used;
						})
						.catch(() => {
							this.canUseInitialStructure = false; // if error, we can't use initial structure, in doubt
							return true;
						});
				} else {
					return used;
				}
			} else {
				return used;
			}
		},
	},
	computed: {
		displayedModels() {
			return this.models.filter((model) => {
				return model.displayed;
			});
		},
	},
	watch: {
		search: function () {
			this.models.forEach((model) => {
				model.displayed = model.label[this.shortDefaultLang].toLowerCase().includes(this.search.toLowerCase().trim());
			});
		},
		selected: function () {
			if (this.selected !== -1) {
				this.isInitialStructureAlreadyUsed();
			}
		},
	},
};
</script>

<style lang="scss" scoped>
#form-builder-create-page {
	height: calc(100vh - 42px);
	overflow-y: auto;
	background-color: #f2f2f3;

	.line-head {
		border-top-left-radius: 4px;
		border-top-right-radius: 4px;
		width: fit-content;
		color: white !important;
	}

	.line {
		height: 4px;
	}

	.card-wrapper {
		width: 150px;

		.em-shadow-cards {
			background-color: white;
			width: 150px;
			border: 2px solid transparent;
		}

		.card {
			margin: 24px 0 12px 0;
		}

		p {
			text-align: center;
			border-radius: 4px;
			padding: 4px;
			transition: all 0.3s;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			font-size: 12px;
		}

		input {
			width: 150px;
			height: 20px;
			font-size: 12px;
			border: 0;
			text-align: center;
		}

		&.selected {
			.em-shadow-cards {
				border: 2px solid var(--em-profile-color);
			}

			p,
			input {
				color: white !important;
				background-color: var(--em-profile-color) !important;
			}
		}
	}

	#new-page {
		.material-symbols-outlined {
			margin: auto;
		}
	}

	.model-preview {
		overflow: hidden;
	}

	#models .models-card {
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
		margin-bottom: 64px;
		flex-wrap: wrap;
	}

	#search-model-wrapper {
		position: relative;

		.reset-search {
			position: absolute;
			top: 9px;
			right: 10px;
		}
	}

	.empty-model-message {
		margin: 120px;

		.material-symbols-outlined {
			font-size: 42px;
		}
	}

	.actions {
		position: fixed;
		bottom: 0;
		right: 0;
		padding: 16px 32px;
		background: linear-gradient(to top, white, transparent);
	}
}

#structure-options {
	transition: all 0.3s;

	input {
		margin: 0;
		height: auto;
	}

	label {
		margin: 0 0 0 8px;
	}

	.disabled {
		opacity: 0.5;
		cursor: not-allowed;

		input,
		label {
			pointer-events: none;
		}
	}
}
</style>
