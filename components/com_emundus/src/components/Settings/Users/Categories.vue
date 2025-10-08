<script>
import Parameter from '@/components/Utils/Parameter.vue';

import settingsService from '@/services/settings';

import alerts from '@/mixins/alerts.js';

export default {
	name: 'Categories',
	components: { Parameter },
	mixins: [alerts],
	data() {
		return {
			loading: false,

			categories: [],
			show_archived: false,
			enable_user_category: 0,
			mandatory: 0,
		};
	},
	created() {
		this.loading = true;

		settingsService.getEmundusParams().then((response) => {
			this.enable_user_category = response.emundus.enable_user_categories;
			this.mandatory = response.emundus.user_category_mandatory;

			this.getCategories();
		});
	},
	methods: {
		getCategories() {
			settingsService.getUserCategories(!this.show_archived).then((response) => {
				this.categories = response.data;

				this.loading = false;
			});
		},

		addCategory() {
			this.categories.push({ id: null, label: '' });
		},

		removeCategory(index) {
			if (this.categories[index].id && this.categories[index].id > 0) {
				settingsService.unpublishUserCategory(this.categories[index].id).then(() => {
					if (!this.show_archived) {
						this.categories.splice(index, 1);
					} else {
						this.categories[index].published = false;
					}
				});
			} else {
				this.categories.splice(index, 1);
			}
		},

		publishCategory(index) {
			settingsService.publishUserCategory(this.categories[index].id).then(() => {
				this.categories[index].published = true;
			});
		},

		saveCategories() {
			this.loading = true;

			settingsService
				.saveUserCategories(this.categories)
				.then((response) => {
					Swal.fire({
						icon: 'success',
						title: this.translate('COM_EMUNDUS_ONBOARD_SUCCESS'),
						showConfirmButton: false,
						timer: 3000,
					}).then(() => {
						this.getCategories();
					});
				})
				.catch(() => {
					this.alertError('COM_EMUNDUS_SETTINGS_SAVE_CATEGORIES_ERROR');
					this.loading = false;
				});
		},

		switchUserCategory() {
			settingsService.switchUserCategory(this.enable_user_category);
		},

		switchUserCategoryMandatory() {
			settingsService.switchUserCategoryMandatory(this.mandatory);
		},
	},

	watch: {
		show_archived() {
			this.getCategories();
		},
	},
};
</script>

<template>
	<div class="tw-mt-2">
		<div class="tw-flex tw-flex-col tw-gap-6" v-if="!loading">
			<div class="tw-flex tw-items-center">
				<div class="em-toggle">
					<input
						true-value="1"
						false-value="0"
						type="checkbox"
						class="em-toggle-check tw-mt-2"
						id="enable_user_category"
						name="enable_user_category"
						v-model="enable_user_category"
						:checked="enable_user_category == 1"
						@change="switchUserCategory"
					/>
					<strong class="b em-toggle-switch"></strong>
					<strong class="b em-toggle-track"></strong>
				</div>
				<label for="enable_user_category" class="!tw-mb-0 tw-ml-2 tw-flex tw-cursor-pointer tw-items-center"
					>{{ translate('COM_EMUNDUS_ONBOARD_ENABLED_USER_CATEGORY') }}
				</label>
			</div>

			<Transition name="slide-fade">
				<div class="tw-flex tw-flex-col tw-gap-6" v-if="enable_user_category == 1">
					<div class="tw-flex tw-items-center">
						<div class="em-toggle">
							<input
								true-value="1"
								false-value="0"
								type="checkbox"
								class="em-toggle-check tw-mt-2"
								id="mandatory"
								name="mandatory"
								v-model="mandatory"
								:checked="mandatory == 1"
								@change="switchUserCategoryMandatory"
							/>
							<strong class="b em-toggle-switch"></strong>
							<strong class="b em-toggle-track"></strong>
						</div>
						<label for="mandatory" class="!tw-mb-0 tw-ml-2 tw-flex tw-cursor-pointer tw-items-center"
							>{{ translate('COM_EMUNDUS_ONBOARD_MANDATORY_USER_CATEGORY') }}
						</label>
					</div>

					<div class="tw-flex tw-items-center tw-gap-2">
						<input type="checkbox" id="show_archived" name="show_archived" v-model="show_archived" />
						<label class="!tw-mb-0 tw-cursor-pointer" for="show_archived">{{
							translate('COM_EMUNDUS_ONBOARD_SHOW_ARCHIVED_USER_CATEGORY')
						}}</label>
					</div>

					<button @click="addCategory" class="tw-btn-primary tw-mb-3 tw-flex tw-w-max tw-items-center tw-gap-1">
						<span class="material-symbols-outlined">add</span>
						{{ translate('COM_EMUNDUS_ONBOARD_ADD_USER_CATEGORY') }}
					</button>

					<div v-for="(category, index) in categories" class="tw-flex tw-flex-col tw-gap-2" :key="category.id">
						<div class="tw-flex tw-gap-2">
							<input type="text" v-model="category.label" class="form-control" />
							<a
								v-if="category.published || !category.id"
								type="button"
								:title="translate('COM_EMUNDUS_ONBOARD_DELETE_STATUS')"
								@click="removeCategory(index)"
								class="tw-flex tw-cursor-pointer tw-items-center"
							>
								<span class="material-symbols-outlined tw-text-red-600">delete_outline</span>
							</a>
							<a
								v-else
								type="button"
								:title="translate('COM_EMUNDUS_ONBOARD_DELETE_STATUS')"
								@click="publishCategory(index)"
								class="tw-flex tw-cursor-pointer tw-items-center"
							>
								<span class="material-symbols-outlined tw-text-green-600">restore_from_trash</span>
							</a>
						</div>
					</div>
				</div>
			</Transition>

			<div>
				<button class="tw-btn-primary tw-float-right tw-w-fit" @click="saveCategories()">
					<span>{{ translate('COM_EMUNDUS_ONBOARD_SAVE') }}</span>
				</button>
			</div>
		</div>
		<div v-else class="em-loader" />
	</div>
</template>
