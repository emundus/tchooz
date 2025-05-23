<template>
	<div id="form-builder-rules-js-action" class="tw-w-full tw-self-start">
		<div class="tw-flex tw-items-center tw-justify-between">
			<h2>{{ actionLabel }}</h2>
			<button v-if="index !== 0" type="button" @click="$emit('remove-action', index)" class="tw-w-auto">
				<span class="material-symbols-outlined tw-text-red-600">close</span>
			</button>
		</div>

		<div class="tw-ml-4 tw-mt-4 tw-flex" v-if="!loading">
			<p class="tw-mr-4 tw-mt-3 tw-font-bold">
				{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_THEN') }}
			</p>

			<div class="tw-ml-2 tw-flex tw-w-full tw-flex-col">
				<div class="tw-flex tw-items-center">
					<div class="form-group tw-w-full">
						<select class="tw-w-full" v-model="action.action">
							<option v-for="actionOpt in actions" :value="actionOpt.value">
								{{ translate(actionOpt.label) }}
							</option>
						</select>
					</div>
				</div>

				<div class="mt-4">
					<div>
						<multiselect
							v-model="action.fields"
							label="label_tag"
							:custom-label="labelTranslate"
							:track-by="multiselectTrackBy"
							:options="availableElements"
							:multiple="actionMultiple"
							:taggable="false"
							select-label=""
							selected-label=""
							deselect-label=""
							:placeholder="
								actionMultiple
									? translate('COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELDS')
									: translate('COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELD')
							"
							:close-on-select="!actionMultiple"
							:clear-on-select="false"
							:searchable="true"
							:allow-empty="true"
							:key="action.action"
						></multiselect>
					</div>

					<div class="tw-mt-4" v-if="['show_options', 'hide_options'].includes(action.action) && options.length > 0">
						<multiselect
							v-model="action.params"
							label="value"
							track-by="primary_key"
							:options="options"
							:multiple="true"
							:taggable="false"
							select-label=""
							selected-label=""
							deselect-label=""
							:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_OPTIONS')"
							:close-on-select="false"
							:clear-on-select="false"
							:searchable="true"
							:allow-empty="true"
						></multiselect>
					</div>

					<div v-if="action.action == 'define_repeat_group'">
						<div class="tw-mt-4">
							<label>{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_GROUP_MIN') }}</label>
							<input type="text" v-model="minRepeat" />
						</div>

						<div class="tw-mt-4">
							<label>{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_GROUP_MAX') }}</label>
							<input type="text" v-model="maxRepeat" />
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import formBuilderMixin from '@/mixins/formbuilder';
import globalMixin from '@/mixins/mixin';
import fabrikMixin from '@/mixins/fabrik';
import errorMixin from '@/mixins/errors';
import Multiselect from 'vue-multiselect';
import formBuilderService from '@/services/formbuilder';

import { useGlobalStore } from '@/stores/global.js';

export default {
	components: {
		Multiselect,
	},
	props: {
		page: {
			type: Object,
			default: {},
		},
		action: {
			type: Object,
			default: {},
		},
		index: {
			type: Number,
			default: 0,
		},
		elements: {
			type: Array,
			default: [],
		},
	},
	mixins: [formBuilderMixin, globalMixin, errorMixin, fabrikMixin],
	data() {
		return {
			loading: false,

			actions: [
				{
					id: 1,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_ACTION_SHOW',
					value: 'show',
					multiple: true,
				},
				{
					id: 2,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_ACTION_HIDE',
					value: 'hide',
					multiple: true,
				},
				{
					id: 3,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_ACTION_SHOW_OPTIONS',
					value: 'show_options',
					multiple: false,
				},
				{
					id: 4,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_ACTION_HIDE_OPTIONS',
					value: 'hide_options',
					multiple: false,
				},
				{
					id: 5,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_ACTION_MANDATORY',
					value: 'set_mandatory',
					multiple: true,
				},
				{
					id: 6,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_ACTION_OPTIONAL',
					value: 'set_optional',
					multiple: true,
				},
				{
					id: 7,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_GROUP',
					value: 'define_repeat_group',
					multiple: false,
				},
			],

			options: [],
			options_plugins: ['dropdown', 'databasejoin', 'radiobutton', 'checkbox'],

			minRepeat: 1,
			maxRepeat: 0,
		};
	},
	created() {
		if (this.page.id) {
			if (this.$props.action.params) {
				this.$props.action.params.forEach((param) => {
					this.$props.action.params = JSON.parse(param);
				});
			}

			this.$props.action.fields.forEach((field, index) => {
				if (this.$props.action.action == 'define_repeat_group') {
					this.minRepeat = this.$props.action.params[0].minRepeat;
					this.maxRepeat = this.$props.action.params[0].maxRepeat;

					this.$props.action.fields[index] = Object.values(this.page.Groups).find((group) => group.group_id == field);
				} else {
					this.$props.action.fields[index] = this.elements.find((element) => element.name === field);
				}

				if (this.action.action == 'show_options' || this.action.action == 'hide_options') {
					this.defineOptions(this.$props.action.fields[index]);
				}
			});
		}
	},
	methods: {
		labelTranslate({ label, name, group_id, elements }) {
			let labelTranslated = label ? label[useGlobalStore().getShortLang] : '';

			// If labelTranslated is empty, we try to find an other language
			if (labelTranslated === '') {
				let labels = Object.values(label);
				labels.forEach((label) => {
					if (label !== '') {
						labelTranslated = label;
					}
				});
			}

			if (labelTranslated !== '') {
				return labelTranslated;
			} else if (group_id && elements) {
				let groupElements = Object.values(elements);
				let element = groupElements.find(
					(element) => !element.hidden && element.label && element.label[useGlobalStore().getShortLang] !== '',
				);
				return this.translate('COM_EMUNDUS_FORM_BUILDER_RULES_GROUP_WITH_ELEMENT').replace(
					'%s',
					element.label[useGlobalStore().getShortLang],
				);
			} else {
				return name;
			}
		},
		defineOptions(val) {
			if (['show_options', 'hide_options'].includes(this.action.action)) {
				if (this.options_plugins.includes(val.plugin)) {
					if (val.plugin == 'databasejoin') {
						this.getDatabasejoinOptions(
							val.params.join_db_name,
							val.params.join_key_column,
							val.params.join_val_column,
							val.params.join_val_column_concat,
						).then((response) => {
							if (response.status && response.data != '') {
								this.options = response.options;
							} else {
								this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
							}
						});
					} else {
						formBuilderService.getJTEXTA(val.params.sub_options.sub_labels).then((response) => {
							if (response) {
								val.params.sub_options.sub_labels.forEach((label, index) => {
									val.params.sub_options.sub_labels[index] = Object.values(response.data)[index];
								});
							}

							Object.values(val.params.sub_options.sub_values).forEach((option, key) => {
								let new_option = {
									primary_key: option,
									value: val.params.sub_options.sub_labels[key],
								};

								this.options.push(new_option);
							});
						});
					}
				}
			}
		},
	},
	computed: {
		actionLabel() {
			return `Action n°${this.index + 1}`;
		},
		actionMultiple() {
			return this.actions.find((action) => action.value === this.action.action).multiple;
		},
		availableElements() {
			if (!this.actionMultiple) {
				if (this.action.action == 'define_repeat_group') {
					return Object.values(this.page.Groups).filter((group) => group.repeat_group == true);
				} else {
					return this.elements.filter((element) =>
						['databasejoin', 'dropdown', 'radiobutton', 'checkbox'].includes(element.plugin),
					);
				}
			} else {
				return this.elements;
			}
		},
		multiselectTrackBy() {
			return this.action.action == 'define_repeat_group' ? 'group_id' : 'name';
		},
	},
	watch: {
		'action.action': {
			handler: function (val, oldVal) {
				if (
					['show', 'hide'].includes(oldVal) &&
					['show_options', 'hide_options'].includes(val) &&
					this.action.fields.length > 1
				) {
					this.action.fields = [];
				} else if (
					['show', 'hide'].includes(oldVal) &&
					['show_options', 'hide_options'].includes(val) &&
					this.action.fields.length == 1
				) {
					this.defineOptions(this.action.fields[0]);
				}

				if (val === 'define_repeat_group') {
					if (this.$props.action.params[0] == undefined) {
						this.$props.action.params[0] = {};
					}
					this.$props.action.params[0].minRepeat = this.minRepeat;
					this.$props.action.params[0].maxRepeat = this.maxRepeat;
				}
			},
			deep: true,
		},

		'action.fields': {
			handler: function (val, oldVal) {
				if (val) {
					this.defineOptions(val);
				}
			},
			deep: true,
		},

		minRepeat: function (val) {
			if (this.$props.action.params[0] == undefined) {
				this.$props.action.params[0] = {};
			}
			this.$props.action.params[0].minRepeat = val;
		},

		maxRepeat: function (val) {
			if (this.$props.action.params[0] == undefined) {
				this.$props.action.params[0] = {};
			}
			this.$props.action.params[0].maxRepeat = val;
		},
	},
};
</script>
