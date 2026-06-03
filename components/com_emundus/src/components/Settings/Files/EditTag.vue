<script>
import { FetchClient } from '@/services/fetchClient.js';
import Parameter from '@/components/Utils/Parameter.vue';

const client = new FetchClient('labels');

const TAG_CLASSES = [
	'label-default',
	'label-red-1',
	'label-red-2',
	'label-pink-1',
	'label-pink-2',
	'label-purple-1',
	'label-purple-2',
	'label-blue-1',
	'label-blue-2',
	'label-blue-3',
	'label-light-blue-1',
	'label-light-blue-2',
	'label-green-1',
	'label-green-2',
	'label-yellow-1',
	'label-yellow-2',
	'label-orange-1',
	'label-orange-2',
	'label-grey-1',
	'label-grey-2',
	'label-beige',
	'label-brown',
	'label-black',
];

export default {
	name: 'EditTag',
	components: { Parameter },
	emits: ['saved', 'close'],
	props: {
		tag: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			form: {
				id: 0,
				label: '',
				color: 'label-default',
				category: '',
				ordering: 0,
			},
			labelField: {
				param: 'label',
				type: 'text',
				value: '',
				label: 'COM_EMUNDUS_APPLICATION_TAGS_LABEL',
				placeholder: 'COM_EMUNDUS_APPLICATION_TAGS_LABEL_PLACEHOLDER',
				optional: false,
				displayed: true,
				maxlength: 100,
			},
			categoryField: {
				param: 'category',
				type: 'text',
				value: '',
				label: 'COM_EMUNDUS_APPLICATION_TAGS_CATEGORY',
				placeholder: '',
				optional: true,
				displayed: true,
				maxlength: 100,
			},
			availableClasses: TAG_CLASSES,
			saving: false,
			errorMessage: '',
		};
	},
	computed: {
		canSave() {
			return this.form.label.trim().length > 0 && !this.saving;
		},
	},
	created() {
		this.form.id = this.tag.id || 0;
		this.form.label = this.tag.label || '';
		this.form.color = this.tag.color || 'label-default';
		this.form.category = this.tag.category || '';
		this.form.ordering = this.tag.ordering || 0;

		this.labelField.value = this.form.label;
		this.categoryField.value = this.form.category;
	},
	methods: {
		onParameterUpdated(_parameter, _oldValue, newValue) {
			this.form[_parameter.param] = newValue;
		},
		selectColor(cls) {
			this.form.color = cls;
		},
		close() {
			this.$emit('close');
		},
		async save() {
			if (!this.canSave) {
				return;
			}

			this.saving = true;
			this.errorMessage = '';

			client
				.post('save', {
					id: this.form.id,
					label: this.form.label,
					color: this.form.color,
					category: this.form.category,
					ordering: this.form.ordering,
				})
				.then((response) => {
					this.saving = false;
					if (response.status) {
						this.$emit('saved', response.data.id);
					} else {
						this.errorMessage = response.msg || this.translate('COM_EMUNDUS_APPLICATION_TAGS_CREATE_FAILED');
					}
				})
				.catch((error) => {
					this.saving = false;
					this.errorMessage = error?.message || this.translate('COM_EMUNDUS_APPLICATION_TAGS_CREATE_FAILED');
				});
		},
	},
};
</script>

<template>
	<div
		id="application-tag-edit"
		class="tw-flex tw-flex-col tw-gap-6 tw-rounded-2xl tw-bg-white tw-p-8 tw-shadow-standard"
	>
		<h1 class="tw-mb-0 tw-text-center">{{ translate('COM_EMUNDUS_APPLICATION_ADD_NEW_TAG') }}</h1>

		<Parameter :parameter-object="labelField" help-text-type="above" @valueUpdated="onParameterUpdated" />
		<Parameter :parameter-object="categoryField" help-text-type="above" @valueUpdated="onParameterUpdated" />

		<div class="tw-flex tw-flex-col tw-gap-2">
			<label class="tw-font-medium">{{ translate('COM_EMUNDUS_APPLICATION_TAGS_COLOR') }}</label>
			<div class="tw-flex tw-flex-wrap tw-gap-2">
				<button
					v-for="cls in availableClasses"
					:key="cls"
					type="button"
					class="sticker tw-flex tw-cursor-pointer tw-items-center tw-gap-2 tw-rounded-full tw-border-2 tw-px-3 tw-py-1 tw-text-white"
					:class="[cls, form.color === cls ? 'tw-border-neutral-900' : 'tw-border-transparent']"
					@click="selectColor(cls)"
				>
					<span class="circle tw-bg-white"></span>
					<span class="tw-mr-2 tw-text-sm tw-font-semibold">{{
						form.label || translate('COM_EMUNDUS_APPLICATION_TAGS_PREVIEW')
					}}</span>
				</button>
			</div>
		</div>

		<div v-if="errorMessage" class="tw-text-sm tw-text-red-600">
			{{ errorMessage }}
		</div>

		<div class="tw-flex tw-justify-between">
			<button type="button" class="tw-btn-secondary" @click="close" :disabled="saving">
				{{ translate('COM_EMUNDUS_ACTIONS_CANCEL') }}
			</button>
			<button type="button" class="tw-btn-primary" :disabled="!canSave" @click="save">
				{{ translate('COM_EMUNDUS_APPLICATION_TAGS_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
