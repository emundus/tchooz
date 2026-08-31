<script>
import languageService from '@/services/language.js';
import Loader from '@/components/Atoms/Loader.vue';
import Button from '@/components/Atoms/Button.vue';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'TranslationEdit',
	components: { Button, Loader },
	mixins: [alerts],
	props: {
		item: Object,
	},
	data() {
		return {
			saving: false,
			override: '',
		};
	},
	created() {
		this.override = this.item?.override ?? '';
	},
	computed: {
		hasChanged() {
			return this.override !== (this.item?.override ?? '');
		},
	},
	methods: {
		saveTranslation() {
			this.saving = true;

			languageService.saveTranslation(this.item.id, this.item.lang_code, this.override).then((response) => {
				this.saving = false;
				this.$emit('close');

				if (response.status) {
					this.$emit('update-items');
					this.alertSuccess('COM_EMUNDUS_LANGUAGES_TRANSLATION_SAVED');
				} else {
					this.alertError('COM_EMUNDUS_LANGUAGES_TRANSLATION_SAVE_FAILED', response.msg || '');
				}
			});
		},
	},
};
</script>

<template>
	<div>
		<h1>{{ translate('COM_EMUNDUS_LANGUAGES_EDIT') }}</h1>

		<div v-if="!saving" class="tw-flex tw-flex-col tw-gap-4">
			<div class="tw-mt-7 tw-flex tw-w-full tw-flex-col">
				<label for="translation-tag">{{ translate('COM_EMUNDUS_LANGUAGES_COLUMN_TAG') }}</label>
				<input id="translation-tag" type="text" :value="item.tag" disabled />
			</div>

			<div class="tw-flex tw-w-full tw-flex-col">
				<label for="translation-override">{{ translate('COM_EMUNDUS_LANGUAGES_COLUMN_TRANSLATION') }}</label>
				<textarea id="translation-override" v-model="override" rows="4"></textarea>
			</div>

			<div class="tw-flex tw-w-full tw-items-center tw-justify-between">
				<Button @click="$emit('close')" variant="cancel">
					{{ translate('COM_EMUNDUS_ONBOARD_CANCEL') }}
				</Button>
				<Button @click="saveTranslation()" :disabled="!hasChanged">
					{{ translate('COM_EMUNDUS_ONBOARD_OK') }}
				</Button>
			</div>
		</div>

		<Loader v-else />
	</div>
</template>

<style scoped></style>
