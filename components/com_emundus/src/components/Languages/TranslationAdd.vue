<script>
import languageService from '@/services/language.js';
import Loader from '@/components/Atoms/Loader.vue';
import Button from '@/components/Atoms/Button.vue';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'TranslationAdd',
	components: { Button, Loader },
	mixins: [alerts],
	data() {
		return {
			loading: true,
			saving: false,

			tag: '',
			languages: [],
			overrides: {},

			searchString: '',
			searchType: 'constant',
			searchResults: [],
			searchMore: null,
			searching: false,
			searched: false,
		};
	},
	created() {
		languageService.getPlatformLanguages().then((response) => {
			if (response.status) {
				this.languages = response.data;
				this.languages.forEach((language) => {
					this.overrides[language.lang_code] = '';
				});
			}
			this.loading = false;
		});
	},
	computed: {
		filledOverrides() {
			return Object.fromEntries(Object.entries(this.overrides).filter(([, override]) => override.trim() !== ''));
		},
		canSubmit() {
			return this.tag.trim() !== '' && Object.keys(this.filledOverrides).length > 0;
		},
	},
	methods: {
		runSearch(more = 0) {
			const term = this.searchString.trim();
			if (term === '') {
				return;
			}

			const append = more > 0;
			this.searching = true;
			if (!append) {
				this.searched = false;
				this.searchResults = [];
				this.searchMore = null;
			}

			languageService
				.searchStrings(term, this.searchType, more)
				.then(({ results, more: nextMore }) => {
					this.searchResults = append ? this.searchResults.concat(results) : results;
					this.searchMore = nextMore;
				})
				.catch((error) => {
					if (!append) {
						this.searchResults = [];
					}
					this.searchMore = null;
					this.alertError('COM_EMUNDUS_LANGUAGES_SEARCH_FAILED', error.message || '');
				})
				.finally(() => {
					this.searching = false;
					this.searched = true;
				});
		},
		useResult(result) {
			this.tag = result.constant;
		},
		addTranslation() {
			this.saving = true;

			languageService
				.addTranslation(this.tag, this.filledOverrides)
				.then((response) => {
					if (response.status) {
						this.$emit('close');
						this.$emit('update-items');
						this.alertSuccess('COM_EMUNDUS_LANGUAGES_TRANSLATION_ADDED');
					} else {
						this.alertError('COM_EMUNDUS_LANGUAGES_TRANSLATION_ADD_FAILED', response.msg || '');
					}
				})
				.catch((error) => {
					this.alertError('COM_EMUNDUS_LANGUAGES_TRANSLATION_ADD_FAILED', error.message || '');
				})
				.finally(() => {
					this.saving = false;
				});
		},
	},
};
</script>

<template>
	<div>
		<h1>{{ translate('COM_EMUNDUS_LANGUAGES_ADD') }}</h1>

		<div v-if="!loading && !saving" class="tw-flex tw-flex-col tw-gap-4">
			<div class="tw-mt-7 tw-flex tw-w-full tw-flex-col tw-gap-2 tw-rounded-coordinator tw-bg-neutral-200 tw-p-4">
				<label for="translation-search">{{ translate('COM_EMUNDUS_LANGUAGES_SEARCH_EXISTING') }}</label>
				<div class="tw-flex tw-w-full tw-items-center tw-gap-2">
					<select v-model="searchType" class="tw-w-40">
						<option value="constant">{{ translate('COM_EMUNDUS_LANGUAGES_SEARCH_BY_KEY') }}</option>
						<option value="value">{{ translate('COM_EMUNDUS_LANGUAGES_SEARCH_BY_VALUE') }}</option>
					</select>
					<input
						id="translation-search"
						v-model="searchString"
						type="text"
						class="tw-flex-1"
						:placeholder="translate('COM_EMUNDUS_LANGUAGES_SEARCH_PLACEHOLDER')"
						@keyup.enter="runSearch()"
					/>
					<Button @click="runSearch()" :disabled="searchString.trim() === '' || searching">
						{{ translate('COM_EMUNDUS_LANGUAGES_SEARCH_BUTTON') }}
					</Button>
				</div>

				<Loader v-if="searching && searchResults.length === 0" />

				<template v-else-if="searchResults.length > 0">
					<ul
						class="tw-m-0 tw-max-h-60 tw-divide-y tw-divide-neutral-200 tw-overflow-y-auto tw-rounded-coordinator tw-border tw-border-neutral-200 tw-bg-white tw-p-0"
					>
						<li
							v-for="result in searchResults"
							:key="result.constant"
							class="tw-flex tw-cursor-pointer tw-items-center tw-justify-between tw-gap-2 tw-p-2 hover:tw-bg-neutral-300"
							@click="useResult(result)"
						>
							<div class="tw-min-w-0 tw-flex-1">
								<p class="tw-truncate tw-font-mono tw-text-sm">{{ result.constant }}</p>
								<p class="tw-truncate tw-text-sm tw-text-neutral-600">{{ result.string }}</p>
							</div>
							<span class="material-symbols-outlined tw-text-neutral-500">check</span>
						</li>
					</ul>

					<Button
						v-if="searchMore !== null"
						variant="link"
						width="fit"
						:disabled="searching"
						@click="runSearch(searchMore)"
					>
						{{ translate('COM_EMUNDUS_LANGUAGES_SEARCH_MORE') }}
					</Button>
				</template>

				<p v-else-if="searched" class="tw-mt-1 tw-text-sm tw-text-neutral-600">
					{{ translate('COM_EMUNDUS_LANGUAGES_SEARCH_NO_RESULTS') }}
				</p>
			</div>

			<div class="tw-flex tw-w-full tw-flex-col">
				<label for="translation-new-tag">
					{{ translate('COM_EMUNDUS_LANGUAGES_COLUMN_TAG') }} <span class="tw-text-red-600">*</span>
				</label>
				<input id="translation-new-tag" v-model="tag" type="text" />
				<p class="tw-mt-1 tw-text-sm tw-text-neutral-600">
					{{ translate('COM_EMUNDUS_LANGUAGES_ADD_TAG_HELP') }}
				</p>
			</div>

			<div v-for="language in languages" :key="language.lang_code" class="tw-flex tw-w-full tw-flex-col">
				<label :for="'translation-new-' + language.lang_code">{{ language.title_native }}</label>
				<textarea
					:id="'translation-new-' + language.lang_code"
					v-model="overrides[language.lang_code]"
					rows="3"
				></textarea>
			</div>

			<div class="tw-flex tw-w-full tw-items-center tw-justify-between">
				<Button @click="$emit('close')" variant="cancel">
					{{ translate('COM_EMUNDUS_ONBOARD_CANCEL') }}
				</Button>
				<Button @click="addTranslation()" :disabled="!canSubmit">
					{{ translate('COM_EMUNDUS_ONBOARD_OK') }}
				</Button>
			</div>
		</div>

		<Loader v-else />
	</div>
</template>

<style scoped></style>
