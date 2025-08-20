<template>
	<div id="form-builder-rules" class="tw-w-full tw-self-start">
		<div class="tw-p-8">
			<h2 class="tw-mb-3" v-if="rules.length > 0">
				{{ translate('COM_EMUNDUS_FORMBUILDER_RULES') + this.$props.page.label }}
			</h2>

			<button id="add-section" class="tw-btn-primary tw-mb-4 tw-px-6 tw-py-3" @click="$emit('add-rule', 'js')">
				{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_ADD_CONDITION') }}
			</button>

			<div class="tw-relative tw-flex tw-items-center">
				<input
					v-model="keywords"
					type="text"
					class="formbuilder-searchbar tw-bg-transparent"
					:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_SEARCH_CONDITION')"
				/>
				<button
					v-if="keywords !== ''"
					type="button"
					@click="keywords = ''"
					class="tw-absolute tw-right-3 tw-h-[16px] tw-w-auto"
				>
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>

			<div class="tw-mt-3 tw-flex tw-flex-col tw-gap-3" v-if="!loading">
				<h5 v-if="searchedRules.length == 0">
					{{ translate('COM_EMUNDUS_FORM_BUILDER_RULES_NOT_FOUND') }}
				</h5>

				<div v-for="(rule, index) in searchedRules" :key="rule.id">
					<div
						class="tw-flex tw-flex-col tw-gap-6 tw-rounded-lg tw-border tw-border-neutral-600 tw-px-3 tw-py-4"
						:class="{
							'tw-bg-neutral-400': rule.published == 0,
							'tw-bg-white': rule.published == 1,
						}"
					>
						<div class="tw-flex tw-items-start tw-justify-between">
							<h3>{{ ruleLabel(rule, index) }}</h3>

							<div class="tw-cursor-pointer">
								<popover class="custom-popover-arrow" :position="'left'">
									<ul style="list-style-type: none; margin: 0" class="tw-items-center tw-p-4">
										<li
											@click="$emit('add-rule', 'js', rule)"
											class="tw-w-full tw-px-2 tw-py-1.5 hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
										>
											{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_EDIT') }}
										</li>
										<li
											@click="publishRule(rule, 1)"
											class="tw-w-full tw-px-2 tw-py-1.5 hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
											v-if="rule.published == 0"
										>
											{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_PUBLISH') }}
										</li>
										<li
											@click="publishRule(rule, 0)"
											class="tw-w-full tw-px-2 tw-py-1.5 hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
											v-if="rule.published == 1"
										>
											{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_UNPUBLISH') }}
										</li>
										<!--                          <li @click="cloneRule(rule)" class="py-3 px-4 w-full">
                                                {{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_DUPLICATE') }}
                                              </li>-->
										<li
											@click="deleteRule(rule)"
											class="tw-w-full tw-px-2 tw-py-1.5 tw-text-red-600 hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
										>
											{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_DELETE') }}
										</li>
									</ul>
								</popover>
							</div>
						</div>

						<div :id="'condition_' + rule.id" class="tw-flex tw-flex-col tw-gap-2">
							<div v-for="(grouped_condition, key) in Object.values(rule.conditions)">
								<p v-if="key != 0 && grouped_condition.length > 1" class="tw-mb-2 tw-ml-1 tw-mr-2 tw-font-medium">
									{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_' + rule.group) }}
								</p>

								<div
									class="tw-flex tw-flex-col tw-gap-4"
									:class="{
										'tw-rounded tw-bg-neutral-300 tw-p-2': grouped_condition.length > 1,
									}"
								>
									<div v-for="(condition, condition_index) in grouped_condition" class="tw-flex tw-items-center">
										<span
											v-if="
												(condition_index == 0 && grouped_condition.length > 1) ||
												(key == 0 && grouped_condition.length == 1)
											"
											class="material-symbols-outlined tw-mr-1 !tw-text-2xl !tw-font-medium tw-text-black"
											>alt_route</span
										>
										<span
											v-if="condition_index != 0 && grouped_condition.length > 1"
											class="tw-ml-1 tw-mr-2 tw-font-medium"
											>{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_' + condition.group_type) }}</span
										>
										<span v-if="key != 0 && grouped_condition.length == 1" class="tw-ml-1 tw-mr-2 tw-font-medium">{{
											translate('COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_' + rule.group)
										}}</span>

										<div class="tw-leading-8">
											<span
												class="tw-mr-1 tw-font-medium"
												v-if="
													(condition_index == 0 && grouped_condition.length > 1) ||
													(key == 0 && grouped_condition.length == 1)
												"
												>{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_IF') }}</span
											>

											<span class="conditions-label">{{ condition.elt_label }}</span>

											<span class="label-darkblue tw-ml-1 tw-mr-2 tw-rounded-md tw-p-1">{{
												operator(condition.state)
											}}</span>
											<span>{{ getvalues(condition) }}</span>
										</div>
									</div>
								</div>
							</div>
						</div>

						<hr class="m-0" />
						<div :id="'action_' + rule.id" class="tw-flex tw-flex-col tw-gap-2">
							<div v-for="action in rule.actions" :key="action.id" class="tw-flex tw-items-center">
								<span
									class="material-symbols-outlined tw-mr-3 !tw-text-2xl !tw-font-medium tw-text-black"
									v-if="['show', 'show_options'].includes(action.action)"
									>visibility</span
								>
								<span
									class="material-symbols-outlined tw-mr-3 !tw-text-2xl !tw-font-medium tw-text-black"
									v-if="['hide', 'hide_options'].includes(action.action)"
									>visibility_off</span
								>
								<span
									class="material-symbols-outlined tw-mr-3 !tw-text-2xl !tw-font-medium tw-text-black"
									v-if="['set_optional', 'set_mandatory'].includes(action.action)"
									>indeterminate_check_box</span
								>
								<span
									class="material-symbols-outlined tw-mr-3 !tw-text-2xl !tw-font-medium tw-text-black"
									v-if="['define_repeat_group'].includes(action.action)"
									>repeat</span
								>
								<div>
									<span class="tw-mr-1 tw-font-medium">{{
										translate('COM_EMUNDUS_FORMBUILDER_RULE_ACTION_' + action.action.toUpperCase())
									}}</span>

									<span v-if="['show_options', 'hide_options'].includes(action.action)">{{
										elementOptions(action)
									}}</span>
									<span v-if="['show_options', 'hide_options'].includes(action.action)" class="tw-mx-1 tw-font-medium">
										{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_OF_FIELD') }}</span
									>

									<span v-if="['define_repeat_group'].includes(action.action)">
										{{ repeatOptions(action) }}
									</span>

									<span class="actions-label">{{ action.labels.join(', ') }}</span>
								</div>
							</div>
						</div>

						<span class="material-symbols-outlined tw-self-end" v-if="rule.published == 0">visibility_off</span>
					</div>
				</div>
			</div>

			<button
				v-if="searchedRules.length > 5"
				id="add-section"
				class="tw-btn-primary tw-mt-4 tw-px-6 tw-py-3"
				@click="$emit('add-rule', 'js')"
			>
				{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_ADD_CONDITION') }}
			</button>
		</div>
		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import formService from '@/services/form';

import formBuilderMixin from '@/mixins/formbuilder';
import globalMixin from '@/mixins/mixin';
import errorMixin from '@/mixins/errors';
import Swal from 'sweetalert2';

import { useFormBuilderStore } from '@/stores/formbuilder.js';
import Popover from '@/components/Popover.vue';

import { useGlobalStore } from '@/stores/global.js';

export default {
	components: { Popover },
	props: {
		page: {
			type: Object,
			default: {},
		},
		mode: {
			type: String,
			default: 'forms',
		},
	},
	mixins: [formBuilderMixin, globalMixin, errorMixin],
	data() {
		return {
			rules: [],
			elements: [],
			keywords: '',

			loading: false,
		};
	},
	setup() {
		const formBuilderStore = useFormBuilderStore();

		return {
			formBuilderStore,
		};
	},
	created() {
		this.keywords = this.formBuilderStore.getRulesKeywords;
		if (this.keywords) {
			setTimeout(() => {
				this.highlight(this.keywords);
			}, 500);
		}

		if (this.page.id) {
			this.getConditions();

			formService.getPageObject(this.page.id).then((response) => {
				if (response.status && response.data != '') {
					this.fabrikPage = response.data;
				} else {
					this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
				}

				Object.entries(this.fabrikPage.Groups).forEach(([key, group]) => {
					Object.entries(group.elements).forEach(([key, element]) => {
						if (!element.hidden) {
							this.elements.push(element);
						}
					});
				});
			});
		}
	},
	methods: {
		getConditions() {
			this.loading = true;
			formService.getConditions(this.page.id).then((response) => {
				if (response.status && response.data != '') {
					this.rules = response.data.conditions;
				} else {
					this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
				}

				this.loading = false;
			});
		},

		operator(state) {
			switch (state) {
				case '=':
					return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_EQUALS');
				case '!=':
					return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_NOT_EQUALS');
				case '>':
					return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_GREATER_THAN');
				case '<':
					return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_LESS_THAN');
				case '>=':
					return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_GREATER_THAN_OR_EQUALS');
				case '<=':
					return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_LESS_THAN_OR_EQUALS');
			}
		},

		getvalues(condition) {
			if (condition.options) {
				let index = condition.options.sub_values.findIndex((option) => option == condition.values);
				return condition.options.sub_labels[index];
			} else {
				return condition.values;
			}
		},

		elementOptions(action) {
			let options = [];

			if (action.params) {
				try {
					let action_params = JSON.parse(action.params);

					action_params.forEach((param) => {
						options.push(param.value);
					});
				} catch (e) {
					return console.error(e); // error in the above string (in this case, yes)!
				}
			}

			if (options.length > 0) {
				options = options.join(', ');
			} else {
				options = '';
			}

			return options;
		},

		repeatOptions(action) {
			if (action.params) {
				try {
					let action_params = JSON.parse(action.params);

					if (action_params.length > 0) {
						let min = action_params[0].minRepeat;
						let max = action_params[0].maxRepeat;

						if (min == max) {
							return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_STRICT_REPEAT').replace(
								'%min',
								min,
							);
						} else if (max > 0) {
							return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_BETWEEN')
								.replace('%min', min)
								.replace('%max', max);
						} else {
							return this.translate('COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_MINIMUM').replace('%min', min);
						}
					}
				} catch (e) {
					return console.error(e); // error in the above string (in this case, yes)!
				}
			}
		},

		deleteRule(rule) {
			Swal.fire({
				title: this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_DELETE_TITLE'),
				text: this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_DELETE_CONFIRM'),
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_RULE'),
				cancelButtonText: this.translate('COM_EMUNDUS_FORM_BUILDER_CANCEL'),
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					cancelButton: 'em-swal-cancel-button',
					confirmButton: 'em-swal-confirm-button',
				},
			}).then((result) => {
				if (result.value) {
					this.loading = true;
					formService.deleteRule(rule.id).then((response) => {
						if (response.status) {
							this.getConditions();
						} else {
							this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
						}
					});
				}
			});
		},

		publishRule(rule, state) {
			this.loading = true;
			formService.publishRule(rule.id, state).then((response) => {
				if (response.status) {
					this.getConditions();
				} else {
					this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
				}
			});
		},

		ruleLabel(rule, index) {
			if (rule.label && rule.label.trim() != '') {
				return rule.label;
			} else {
				return this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION') + (index + 1);
			}
		},

		groupedType(conditions) {
			let type = 'AND';
			Object.values(conditions).forEach((condition) => {
				condition.forEach((cond) => {
					type = cond.group_type;
				});
			});

			return type;
		},

		highlight(searchTerm) {
			this.formBuilderStore.updateRulesKeywords(searchTerm);

			const conditions = document.querySelectorAll('.conditions-label');
			const actions = document.querySelectorAll('.actions-label');
			const elements = [...conditions, ...actions];

			elements.forEach((element) => {
				const text = element.innerText;
				let regex = new RegExp(`(${searchTerm})`, 'gi');
				// Check if the element's text contains the search term
				if (searchTerm && text.match(regex)) {
					// Split the text into parts (matched and unmatched)
					const parts = text.split(regex);
					// Create a new HTML structure with the matched term highlighted
					const highlightedText = parts
						.map((part) =>
							part.match(regex) ? `<span style="background-color: var(--em-yellow-1);">${part}</span>` : part,
						)
						.join('');
					// Replace the original text with the highlighted version
					element.innerHTML = highlightedText;
				} else {
					element.innerHTML = text;
				}
			});
		},

		removeHighlight(field) {
			let element = document.getElementById(field);

			if (element) {
				element.innerHTML = element.innerText;
			}
		},

		/*cloneRule(rule)
    {
      this.loading = true;
      formService.cloneRule(rule.id).then(response => {
        if (response.status) {
          this.getConditions();
        } else {
          this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
        }
      });
    }*/
	},
	computed: {
		searchedRules() {
			if (this.keywords) {
				let elements_found = this.elements.filter((element) =>
					element.label[useGlobalStore().getShortLang].toLowerCase().includes(this.keywords.toLowerCase()),
				);
				return this.rules.filter((rule) => {
					let found = false;
					if (rule.label) {
						found = rule.label.toLowerCase().includes(this.keywords.toLowerCase());
					}
					if (!found) {
						Object.values(rule.conditions).forEach((grouped_conditions, key) => {
							grouped_conditions.forEach((condition, index) => {
								if (elements_found.find((element) => element.name == condition.field)) {
									found = true;
								}
							});
						});
					}
					if (!found) {
						rule.actions.forEach((action, index) => {
							action.fields.forEach((field) => {
								if (elements_found.find((element) => element.name == field)) {
									found = true;
								}
							});
						});
					}

					return found;
				});
			} else {
				return this.rules;
			}
		},
	},
	watch: {
		keywords: {
			handler: function (val) {
				this.highlight(val);
			},
		},
	},
};
</script>

<style lang="scss">
#form-builder-rules,
#form-builder-rules-js-conditions {
	.label-darkblue {
		background-color: var(--em-profile-color) !important;
		color: white;
		text-shadow: none;
	}

	input.formbuilder-searchbar {
		border-width: 0 0 1px 0;
		border-radius: 0;
		border-color: var(--neutral-900);
		background-color: transparent;

		&:focus {
			outline: unset;
			border-bottom-color: var(--em-form-outline-color-focus);
		}
	}

	.highlight {
		background-color: var(--em-yellow-1);
	}
}
</style>
