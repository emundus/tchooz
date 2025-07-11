<template>
	<div class="tw-flex tw-flex-col tw-gap-2">
		<!-- LABEL -->
		<label
			v-if="parameter.hideLabel !== true"
			:for="paramId"
			class="tw-mb-0 tw-flex tw-items-end tw-font-medium"
			:class="parameter.helptext && helpTextType === 'above' ? 'tw-mb-0' : ''"
		>
			{{ translate(parameter.label) }}
			<!--      <span v-if="parameter.optional === true"
                  :class="'tw-italic tw-text-[#727272] tw-text-xs tw-ml-1'">
              {{ translate('COM_EMUNDUS_OPTIONAL') }}
            </span>-->
			<span v-if="parameter.optional !== true" class="tw-ml-1 tw-text-red-600">*</span>

			<span
				v-if="parameter.helptext && helpTextType === 'icon'"
				class="material-symbols-outlined tw-ml-1 tw-cursor-pointer tw-text-neutral-600"
				@click="displayHelp(parameter.helptext)"
				>help_outline</span
			>
		</label>

		<span v-if="parameter.helptext && helpTextType === 'above'" class="tw-text-base tw-text-neutral-600">
			<span v-html="translate(parameter.helptext)"></span>
		</span>

		<div
			v-show="!parameter.hidden"
			name="input-field"
			class="tw-flex tw-items-center"
			:class="{
				'input-split-field': parameter.splitField,
				'input-split-field-select': parameter.splitField && parameter.secondParameterType === 'select',
				'tw-gap-2': parameter.splitField && parameter.secondParameterType !== 'select',
			}"
		>
			<!-- ICON -->
			<div v-if="parameter.icon">
				<span :title="translate(parameter.label)" class="material-symbols-outlined tw-mr-2 tw-text-neutral-900">{{
					parameter.icon
				}}</span>
			</div>

			<!-- SELECT -->
			<div
				v-if="parameter.type === 'select'"
				class="tw-flex tw-w-full tw-min-w-[30%] tw-items-center tw-gap-2"
				:class="parameter.classes ? parameter.classes : ''"
			>
				<select
					class="dropdown-toggle w-select !tw-mb-0 tw-w-full"
					:class="[
						errors[parameter.param] ? 'tw-rounded-lg !tw-border-red-500' : '',
						parameter.secondParameterType === 'select' ? 'tw-w-auto' : 'tw-w-full',
					]"
					:id="paramId"
					v-model="value"
					:disabled="parameter.editable === false"
				>
					<option v-for="option in parameter.options" :key="option.value" :value="option.value">
						{{ translate(option.label) }}
					</option>
				</select>

				<!-- if select has a add new parameter -->
				<div v-if="parameter.addNew && parameter.addNew.component">
					<span
						class="material-symbols-outlined not-to-close-modal tw-btn-primary tw-cursor-pointer tw-p-[9px]"
						@click="displayModal('addNew' + parameter.param)"
						>add_circle</span
					>

					<modal
						:ref="'addNew' + parameter.param"
						:name="'addNew' + parameter.param"
						:width="'50%'"
						:height="'auto'"
						:transition="'fade'"
						:click-to-close="true"
						:open-on-create="false"
						:center="true"
						:classes="'tw-rounded-2xl'"
					>
						<component
							:is="parameter.addNew.component"
							v-model="value"
							:componentsProps="this.$props.componentsProps"
							@saved="onAddNewValue(parameter)"
							@close="onCloseAddModal(parameter)"
						>
						</component>
					</modal>
				</div>
			</div>

			<!-- MULTISELECT -->
			<multiselect
				v-else-if="parameter.type === 'multiselect'"
				:id="paramId"
				v-model="value"
				:class="[multiselectOptions.noOptions ? 'no-options' : '', 'tw-cursor-pointer']"
				:label="multiselectOptions.label ? multiselectOptions.label : 'name'"
				:track-by="multiselectOptions.trackBy ? multiselectOptions.trackBy : 'code'"
				:options="multiOptions"
				:options-limit="multiselectOptions.optionsLimit ? multiselectOptions.optionsLimit : 100"
				:multiple="multiselectOptions.multiple ? multiselectOptions.multiple : false"
				:taggable="multiselectOptions.taggable ? multiselectOptions.taggable : false"
				:placeholder="translate(parameter.placeholder)"
				:searchable="multiselectOptions.searchable ? multiselectOptions.searchable : true"
				:tagPlaceholder="translate(multiselectOptions.optionsPlaceholder)"
				:key="paramId"
				:selectLabel="translate(multiselectOptions.selectLabel)"
				:selectGroupLabel="translate(multiselectOptions.selectGroupLabel)"
				:selectedLabel="translate(multiselectOptions.selectedLabel)"
				:deselect-label="translate(multiselectOptions.deselectedLabel)"
				:deselectGroupLabel="translate(multiselectOptions.deselectGroupLabel)"
				:preserve-search="true"
				:internal-search="multiselectOptions.internalSearch ? multiselectOptions.internalSearch : true"
				:loading="isLoading"
				@tag="addOption"
				@keyup="checkComma($event)"
				@focusout="checkAddOption($event)"
				@search-change="asyncFind"
			>
				<template #noOptions>{{ translate(multiselectOptions.noOptionsText) }}</template>
				<template #noResult>{{ translate(multiselectOptions.noResultsText) }}</template>
			</multiselect>

			<!-- TEXTAREA -->
			<textarea
				v-else-if="parameter.type === 'textarea'"
				:id="paramId"
				v-model="value"
				class="!mb-0"
				:style="{
					resize: parameter.resize ? 'vertical' : 'none',
				}"
				:rows="parameter.rows ? parameter.rows : 3"
				:placeholder="translate(parameter.placeholder)"
				:class="errors[parameter.param] ? 'tw-rounded-lg !tw-border-red-500' : ''"
				:maxlength="parameter.maxlength"
				:readonly="parameter.editable === false"
			>
			</textarea>

			<!-- EDITOR -->
			<tip-tap-editor
				v-else-if="parameter.type === 'wysiwig'"
				v-model="value"
				:editor-content-height="'20em'"
				:class="'tw-mt-1 tw-w-full'"
				:locale="actualLanguage"
				:preset="'basic'"
				:toolbar-classes="['tw-bg-white']"
				:editor-content-classes="['tw-bg-white']"
				@focusout="checkValue(parameter)"
			>
			</tip-tap-editor>

			<!-- YESNO -->
			<div v-else-if="parameter.type === 'yesno'">
				<fieldset data-toggle="buttons" class="tw-flex tw-items-center tw-gap-2">
					<label
						:for="paramId + '_input_0'"
						:class="[value == 0 ? 'tw-bg-red-700' : 'tw-border-neutral-500 tw-bg-white hover:tw-border-red-700']"
						class="tw-inline-flex tw-h-10 tw-w-60 tw-items-center tw-justify-center tw-gap-2.5 tw-rounded-lg tw-border tw-p-2.5"
					>
						<input
							v-model="value"
							type="radio"
							class="fabrikinput !tw-hidden"
							:name="paramName"
							:id="paramId + '_input_0'"
							value="0"
							:checked="value === 0"
						/>
						<span :class="[value == 0 ? 'tw-text-white' : 'tw-text-red-700']">{{ translate('JNO') }}</span>
					</label>

					<label
						:for="paramId + '_input_1'"
						:class="[value == 1 ? 'tw-bg-green-700' : 'tw-border-neutral-500 tw-bg-white hover:tw-border-green-700']"
						class="tw-inline-flex tw-h-10 tw-w-60 tw-items-center tw-justify-center tw-gap-2.5 tw-rounded-lg tw-border tw-p-2.5"
					>
						<input
							v-model="value"
							type="radio"
							class="fabrikinput !tw-hidden"
							:name="paramName"
							:id="paramId + '_input_1'"
							value="1"
							:checked="value === 1"
						/>
						<span :class="[value == 1 ? 'tw-text-white' : 'tw-text-green-700']">{{ translate('JYES') }}</span></label
					>
				</fieldset>
			</div>

			<!-- RADIOBUTTON -->
			<div v-else-if="parameter.type === 'radiobutton'">
				<fieldset data-toggle="radio_buttons" class="tw-flex tw-flex-col tw-gap-1">
					<div v-for="option in parameter.options" :key="option.value" class="tw-flex tw-items-center tw-gap-2">
						<input
							v-model="value"
							type="radio"
							class="fabrikinput !tw-mr-0 !tw-h-fit tw-cursor-pointer"
							:class="parameter.hideRadio ? '!tw-hidden' : ''"
							:name="paramName"
							:id="paramId + '_input_' + option.value"
							:value="option.value"
							:checked="value === option.value"
						/>
						<label :for="paramId + '_input_' + option.value" class="tw-mb-0 tw-cursor-pointer">
							<span class="tw-flex tw-items-center tw-gap-2">
								<img
									v-if="option.img"
									:src="'/images/emundus/icons/' + option.img"
									:alt="option.altImg"
									style="width: 16px"
								/>
								{{ translate(option.label) }}
							</span>
						</label>
					</div>
				</fieldset>
			</div>

			<!-- TOGGLE -->
			<div v-else-if="parameter.type === 'toggle'" class="tw-flex tw-items-center">
				<div class="em-toggle">
					<input
						type="checkbox"
						true-value="1"
						false-value="0"
						class="em-toggle-check"
						:id="paramId + '_input'"
						v-model="value"
					/>
					<strong class="b em-toggle-switch"></strong>
					<strong class="b em-toggle-track"></strong>
				</div>
				<label
					:for="paramId + '_input'"
					class="!tw-mb-0 tw-ml-2 tw-flex tw-cursor-pointer tw-items-center tw-font-medium"
				>
					<span v-if="parameter.iconLabel" class="material-symbols-outlined tw-mr-1 tw-text-neutral-900">{{
						parameter.iconLabel
					}}</span>
					<span>{{ translate(parameter.label) }}</span>
				</label>
			</div>

			<!-- INPUT -->
			<input
				v-else-if="isInput"
				:type="parameter.type"
				class="form-control !tw-mb-0 tw-min-w-[30%]"
				style="box-shadow: none"
				:class="errors[parameter.param] ? 'tw-rounded-lg !tw-border-red-500' : ''"
				:max="parameter.type === 'number' ? parameter.max : null"
				:min="undefined"
				:step="parameter.type === 'number' ? parameter.step : null"
				:pattern="parameter.pattern && parameter.pattern.length > 0 ? parameter.pattern : null"
				:placeholder="translate(parameter.placeholder)"
				:id="paramId"
				v-model="value"
				:maxlength="parameter.maxlength"
				:readonly="parameter.editable === false"
				@change.self="checkValue(parameter)"
				@focusin="clearPassword(parameter)"
			/>

			<!-- Upload : TODO: use dropzone-->
			<input v-else-if="parameter.type === 'file'" type="file" @change="onUploadFile" />

			<DatePicker
				v-else-if="parameter.type === 'datetime' || parameter.type === 'date' || parameter.type === 'time'"
				:id="paramId"
				v-model="formattedValue"
				:keepVisibleOnInput="true"
				:popover="{ visibility: 'focus', placement: parameter.placement ? parameter.placement : 'right' }"
				:rules="{ minutes: { interval: 5 } }"
				:mode="parameter.type ? parameter.type : 'dateTime'"
				is24hr
				hide-time-header
				title-position="left"
				:input-debounce="500"
				:locale="actualLanguage"
			>
				<template #default="{ inputValue, inputEvents }">
					<input
						:value="formatDateForDisplay(inputValue)"
						v-on="inputEvents"
						class="form-control fabrikinput !tw-w-auto"
						:class="parameter.classes ? parameter.classes : ''"
						style="box-shadow: none"
						:id="paramId + '_input'"
					/>
				</template>
			</DatePicker>

			<component
				v-else-if="parameter.type === 'component'"
				:is="EventBooking"
				v-model="value"
				:componentsProps="this.$props.componentsProps"
				@valueUpdated="bookingSlotIdUpdated"
			>
			</component>

			<!-- INPUT IN CASE OF SPLIT -->
			<span v-if="parameter.splitField">{{ parameter.splitChar }}</span>
			<span v-if="parameter.endText" class="tw-ml-2">{{ translate(parameter.endText) }}</span>

			<Parameter
				v-if="parameter.splitField && parameterSecondary"
				:parameter-object="parameterSecondary"
				:multiselect-options="multiselectOptions"
				@valueUpdated="regroupValue(parameterSecondary)"
			/>
		</div>

		<!-- ERRORS -->
		<div
			v-if="errors[parameter.param] && !['yesno', 'toggle'].includes(parameter.type) && parameter.displayed"
			class="tw-absolute tw-mt-1 tw-min-h-[24px] tw-text-red-600"
			:class="errors[parameter.param] ? 'tw-opacity-100' : 'tw-opacity-0'"
			:id="'error-message-' + parameter.param"
		>
			{{ translate(errors[parameter.param]) }}
		</div>
	</div>
</template>

<script>
import Multiselect from 'vue-multiselect';
import settingsService from '@/services/settings.js';
import Swal from 'sweetalert2';

import { reactive } from 'vue';
import { DatePicker } from 'v-calendar';
import { useGlobalStore } from '@/stores/global.js';
import dayjs from 'dayjs';
import EventBooking from '@/views/Events/EventBooking.vue';
import Modal from '@/components/Modal.vue';
import TipTapEditor from 'tip-tap-editor';

export default {
	name: 'Parameter',
	components: { DatePicker, Multiselect, Modal, TipTapEditor },
	props: {
		parameterObject: {
			type: Object,
			required: true,
		},
		multiselectOptions: {
			type: Object,
			required: false,
			default: () => {
				return {
					options: [],
					noOptions: false,
					multiple: true,
					taggable: false,
					searchable: true,
					internalSearch: true,
					asyncRoute: '',
					optionsLimit: 100,
					optionsPlaceholder: 'COM_EMUNDUS_MULTISELECT_ADDKEYWORDS',
					selectLabel: 'PRESS_ENTER_TO_SELECT',
					selectGroupLabel: 'PRESS_ENTER_TO_SELECT_GROUP',
					selectedLabel: 'SELECTED',
					deselectedLabel: 'PRESS_ENTER_TO_REMOVE',
					deselectGroupLabel: 'PRESS_ENTER_TO_DESELECT_GROUP',
					noOptionsText: 'COM_EMUNDUS_MULTISELECT_NOKEYWORDS',
					noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
					// Can add tag validations (ex. email, phone, regex)
					tagValidations: [],
					tagRegex: '',
				};
			},
		},
		helpTextType: {
			type: String,
			required: false,
			default: 'icon',
		},
		asyncAttributes: {
			type: Array,
			required: false,
		},
		componentsProps: {
			type: Object,
			required: false,
		},
	},
	emits: ['valueUpdated', 'needSaving', 'ajaxOptionsLoaded'],
	data() {
		return {
			initValue: null,
			value: null,
			valueSecondary: null,

			parameter: {},
			parameterSecondary: {},

			multiOptions: [],
			isLoading: false,

			errors: {},

			abortController: null,
			debounceTimeout: null,

			actualLanguage: 'fr-FR',
		};
	},
	async created() {
		const globalStore = useGlobalStore();
		this.actualLanguage = globalStore.getShortLang;

		this.parameter = this.parameterObject;

		if (this.parameter.type === 'multiselect') {
			if (this.$props.multiselectOptions.asyncRoute) {
				await this.asyncFind('');
			} else {
				this.multiOptions = this.$props.multiselectOptions.options;
			}
			if (!this.multiselectOptions.multiple) {
				this.value = this.multiOptions.find(
					(option) => option[this.$props.multiselectOptions.trackBy] == this.parameter.value,
				);
			} else {
				// Check if values are not already object
				if (this.parameter.value && this.parameter.value.length > 0 && typeof this.parameter.value[0] !== 'object') {
					this.value = this.multiOptions.filter((option) =>
						this.parameter.value.includes(option[this.$props.multiselectOptions.trackBy]),
					);
				} else {
					this.value = this.parameter.value;
				}
			}

			if (!this.value) {
				this.value = [];
			}
		} else if (this.parameter) {
			this.value = this.parameter.value;
		}

		// Check if splitField is set and duplicate the parameter
		if (this.parameter.splitField) {
			if (this.value) {
				let splitValue = this.value.split(this.parameter.splitChar);
				this.value = splitValue[0];
				this.valueSecondary = splitValue[1];
			}

			this.parameterSecondary = reactive({ ...this.parameter });
			if (this.parameter.secondParameterType) {
				this.parameterSecondary.type = this.parameter.secondParameterType;
			}
			if (this.parameter.secondParameterOptions) {
				this.parameterSecondary.options = this.parameter.secondParameterOptions;
			}
			// Pass splitField to false to avoid infinite loop
			this.parameterSecondary.splitField = false;
			//
			this.parameterSecondary.hideLabel = true;

			if (this.parameter.secondParameterDefault && (!this.valueSecondary || this.valueSecondary === '')) {
				this.parameterSecondary.value = this.parameter.secondParameterDefault;
			} else {
				this.parameterSecondary.value = this.valueSecondary;
			}
		}

		//

		this.initValue = this.value;
	},
	methods: {
		displayHelp(message) {
			Swal.fire({
				title: this.translate('COM_EMUNDUS_SWAL_HELP_TITLE'),
				html: this.translate(message),
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

		// MULTISELECT
		addOption(newOption) {
			if (this.multiselectOptions.taggable) {
				// Check if newOption is already in the list
				if (
					this.multiOptions &&
					this.multiOptions.length > 0 &&
					this.multiOptions.find((option) => option.name === newOption)
				) {
					return false;
				}

				if (this.$props.multiselectOptions.tagValidations.length > 0) {
					let valid = false;
					this.$props.multiselectOptions.tagValidations.forEach((validation) => {
						switch (validation) {
							case 'email':
								valid = this.validateEmail(newOption);
								break;
							case 'regex':
								valid = new RegExp(this.$props.multiselectOptions.tagRegex).test(newOption);
								break;
							default:
								break;
						}
					});
					if (!valid) {
						return false;
					}
				}

				const option = {
					name: newOption,
					code: newOption,
				};

				this.multiOptions.push(option);
				this.value.push(option);
			}
		},
		checkAddOption(event) {
			if (this.multiselectOptions.taggable) {
				event.preventDefault();
				let added = this.addOption(event.srcElement.value);
				if (!added) {
					event.srcElement.value = '';
				}
			}
		},
		checkComma(event) {
			if (
				this.$props.multiselectOptions.tagValidations.includes('email') &&
				event &&
				event.key === ',' &&
				this.multiselectOptions.taggable
			) {
				this.addOption(event.srcElement.value.replace(',', ''));
			}
		},
		async asyncFind(search_query) {
			if (this.$props.multiselectOptions.asyncRoute) {
				return new Promise((resolve, reject) => {
					if (this.abortController) {
						this.abortController.abort();
					}

					this.abortController = new AbortController();
					const signal = this.abortController.signal;

					clearTimeout(this.debounceTimeout);
					this.debounceTimeout = setTimeout(() => {
						this.isLoading = true;
						let data = {
							search_query: search_query,
							limit: this.$props.multiselectOptions.optionsLimit,
							properties: this.$props.asyncAttributes,
						};

						settingsService
							.getAsyncOptions(
								this.$props.multiselectOptions.asyncRoute,
								data,
								{ signal },
								this.$props.multiselectOptions.asyncController,
							)
							.then((response) => {
								this.multiOptions = response.data;
								this.isLoading = false;
								this.$emit('ajaxOptionsLoaded', this.multiOptions, this.parameter.param);
								resolve(true);
							});
					}, 500);
				});
			}
		},

		// VALIDATIONS
		validate() {
			if (this.parameter.value === '' && this.parameter.optional === true) {
				delete this.errors[this.parameter.param];
				return true;
			} else if (this.parameter.value === '') {
				this.errors[this.parameter.param] = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL';
				return false;
			} else {
				if (this.parameter.type === 'email') {
					if (!this.validateEmail(this.parameter.value)) {
						this.errors[this.parameter.param] = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NO';
						return false;
					}
				}

				delete this.errors[this.parameter.param];
				return true;
			}
		},
		checkValue(parameter) {
			if (parameter.type === 'number') {
				if (this.value > parameter.max) {
					this.value = parameter.max;
				}
			} else {
				this.validate(parameter);
			}
		},

		clearPassword(parameter) {
			if (parameter.type === 'password') {
				this.value = '';
			}
		},

		regroupValue(parameter) {
			this.valueSecondary = parameter.value;
		},

		validateEmail(email) {
			let res = /^[\w.-]+@([\w-]+\.)+[\w-]{2,4}$/;
			return res.test(email);
		},
		formatDateForDisplay(date) {
			if (!date && this.parameter.type === 'datetime') return '00:00';
			if (!date && this.parameter.type === 'date') return '';
			return date.split('-').reverse().join('/');
		},
		bookingSlotIdUpdated(value) {
			this.$emit('valueUpdated', value);
		},

		displayModal(modalName) {
			this.$refs[modalName].open();
		},

		onAddNewValue(parameter) {
			// Close the modal
			this.onCloseAddModal(parameter);
			this.$emit('newValueAdded', parameter);
		},
		onCloseAddModal() {
			// Close the modal
			this.$refs['addNew' + this.parameter.param].close();
		},
		//
	},
	watch: {
		value: {
			handler: function (val, oldVal) {
				this.parameter.value = val;

				if (this.parameter.splitField) {
					this.parameter.concatValue = val + this.parameter.splitChar + this.valueSecondary;
				}

				this.$emit('valueUpdated', this.parameter, oldVal, val);

				if (val !== oldVal && val !== this.initValue) {
					let valid = true;

					if (['text', 'email', 'number', 'password', 'textarea'].includes(this.parameter.type)) {
						valid = this.validate();
					}

					this.$emit('needSaving', true, this.parameter, valid);
				}

				if (val == this.initValue) {
					this.$emit('needSaving', false, this.parameter, true);
				}
			},
			deep: true,
		},
		valueSecondary: {
			handler: function (val, oldVal) {
				this.parameter.concatValue = this.value + this.parameter.splitChar + val;
				// Specific condition for event slot settings
				if (
					val !== oldVal &&
					((this.parameter.param === 'slot_can_book_until' &&
						this.parameter.label === 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL') ||
						(this.parameter.param === 'slot_can_cancel_until' &&
							this.parameter.label === 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_CANCEL_UNTIL'))
				) {
					if (val === 'days' && oldVal !== null) {
						this.value = '';
						this.parameter.concatValue = this.value + this.parameter.splitChar + val;
						this.parameter.type = 'text';
					} else if (val === 'date') {
						const dateRegex = /^\d{4}-\d{2}-\d{2}$/;

						this.value =
							typeof this.value === 'string' && dateRegex.test(this.value) && !isNaN(new Date(this.value).getTime())
								? this.value
								: new Date().toISOString().split('T')[0];
						this.parameter.concatValue = this.value + this.parameter.splitChar + val;
						this.parameter.type = 'date';
					}
				}
			},
			deep: true,
		},
	},
	computed: {
		EventBooking() {
			return EventBooking;
		},
		isInput() {
			return (
				['text', 'email', 'number', 'password'].includes(this.parameter.type) &&
				this.parameter.displayed &&
				this.parameter.editable !== 'semi'
			);
		},
		paramId() {
			return 'param_' + this.parameter.param + '_' + Math.floor(Math.random() * 100);
		},
		paramName() {
			return 'param_' + this.parameter.param + '[]';
		},
		formattedValue: {
			get() {
				if (this.parameter.type === 'date') {
					if (this.parameter.allownull && this.value === null) {
						return null;
					}

					let today = new Date().toISOString().split('T')[0];
					let dateValue = typeof this.value === 'string' ? this.value : today;
					return dateValue && dateValue < today ? today : dateValue;
				} else {
					return this.value;
				}
			},
			set(newValue) {
				if (this.parameter.type === 'date') {
					newValue = dayjs(newValue).format('YYYY-MM-DD');
					this.value = newValue.split('/').reverse().join('-');
				} else if (this.parameter.type === 'time') {
					if (newValue !== null) {
						const oldDate = new Date(this.value);
						const newDate = new Date(newValue);

						oldDate.setHours(newDate.getHours(), newDate.getMinutes());
						this.value = oldDate;
					} else {
						const oldDate = new Date(this.value);
						oldDate.setHours(0, 0, 0, 0);
						this.value = oldDate;
					}
				} else {
					this.value = newValue;
				}
			},
		},
	},
};
</script>

<style>
.no-options .multiselect__content-wrapper {
	display: none !important;
}

.no-options .multiselect__select {
	display: none !important;
}

div > fieldset[data-toggle='radio_buttons'] > div > input.fabrikinput {
	margin-right: 0 !important;
}
</style>
