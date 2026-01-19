<template>
	<div id="form-builder-radio-button">
		<div v-if="loading" class="em-loader"></div>
		<div v-else>
			<div v-if="displayValues" class="tw-grid tw-w-full tw-grid-cols-2">
				<label class="tw-pl-6 tw-font-medium">Valeurs</label>
				<label class="tw-pl-2 tw-font-medium">Étiquettes</label>
			</div>

			<draggable v-model="arraySubValues" sort="true" handle=".handle-options">
				<div
					class="element-option tw-mb-2 tw-mt-2 tw-flex tw-items-center tw-justify-between"
					v-for="(option, index) in arraySubValues"
					:key="option"
					@mouseover="optionHighlight = index"
					@mouseleave="optionHighlight = null"
				>
					<div class="tw-flex tw-w-full tw-items-center tw-gap-1">
						<div class="tw-flex tw-items-center">
							<span class="icon-handle" :style="optionHighlight === index ? 'opacity: 1' : 'opacity: 0'">
								<span class="material-symbols-outlined handle-options tw-cursor-grab" style="font-size: 18px"
									>drag_indicator</span
								>
							</span>
						</div>

						<input
							v-if="type !== 'dropdown' && !displayValues"
							:type="type"
							:name="'element-id-' + element.id"
							:value="option.sub_label"
						/>

						<div class="tw-flex tw-w-full tw-gap-2">
							<input v-if="displayValues" type="text" v-model="option.sub_value" />

							<input
								type="text"
								class="tw-w-full"
								:class="{ 'editable-data editable-data-input tw-ml-1': !displayValues }"
								:id="'option-' + element.id + '-' + index"
								v-model="option.sub_label"
								@focusout="updateOption(index, option.sub_label)"
								@keyup.enter="updateOption(index, option.sub_label, true)"
								@keyup.tab="document.getElementById('new-option-' + element.id).focus()"
								:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_ADD_OPTION')"
							/>
						</div>
					</div>
					<div class="tw-flex tw-items-center">
						<span
							class="material-symbols-outlined tw-cursor-pointer"
							@click="removeOption(index)"
							:style="optionHighlight === index ? 'opacity: 1' : 'opacity: 0'"
							>close</span
						>
					</div>
				</div>
			</draggable>
			<div id="add-option" class="tw-flex tw-items-center md:tw-justify-center lg:tw-justify-start">
				<span class="icon-handle" style="opacity: 0">
					<span class="material-symbols-outlined handle-options" style="font-size: 18px">drag_indicator</span>
				</span>
				<input
					type="text"
					class="editable-data editable-data-input tw-ml-1 tw-w-full"
					:id="'new-option-' + element.id"
					v-model="newOption"
					@focusout="!dynamicallySave ? addOption : null"
					@keyup.enter="addOption"
					:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_ADD_OPTION')"
				/>
			</div>
		</div>
	</div>
</template>

<script>
import formBuilderService from '../../../services/formbuilder';
import { VueDraggableNext } from 'vue-draggable-next';

export default {
	props: {
		element: {
			type: Object,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
		displayValues: {
			type: Boolean,
			default: false,
		},
		dynamicallySave: {
			type: Boolean,
			default: true,
		},
	},
	components: {
		draggable: VueDraggableNext,
	},
	data() {
		return {
			loading: false,
			newOption: '',
			arraySubValues: [],

			optionHighlight: null,
		};
	},
	created() {
		this.getSubOptionsTranslation();
	},
	methods: {
		async getSubOptionsTranslation(new_option = false) {
			this.arraySubValues = this.element.params.sub_options.sub_values.map((value, i) => {
				return {
					sub_value: value,
					sub_label: this.element.params.sub_options.sub_labels[i] || '',
				};
			});

			setTimeout(() => {
				if (new_option) {
					document.getElementById('new-option-' + this.element.id).focus();
				}
			}, 200);
		},
		addOption() {
			let new_value = this.newOption;
			let options_length = this.arraySubValues.length;
			// If previous value is integer, new_value must be integer too
			if (!isNaN(this.arraySubValues[options_length - 1].sub_value)) {
				new_value = parseInt(this.arraySubValues[options_length - 1].sub_value) + 1;
			}
			// Add the new option to the array
			this.arraySubValues.push({
				sub_value: new_value,
				sub_label: this.newOption,
			});

			this.newOption = '';
		},
		updateOption(index, option, next = false) {
			// Update the option in the array
			this.arraySubValues[index].sub_label = option;

			if (next) {
				setTimeout(() => {
					if (!document.getElementById('option-' + this.element.id + '-' + (index + 1))) {
						document.getElementById('new-option-' + this.element.id).focus();
					} else {
						document.getElementById('option-' + this.element.id + '-' + (index + 1)).focus();
					}
				}, 300);
			}
		},
		removeOption(index) {
			// Remove the option from the array
			this.arraySubValues.splice(index, 1);
		},
	},
};
</script>

<style lang="scss">
.editable-data-input {
	padding: 0 !important;
	height: auto !important;
	border: unset !important;
	border-bottom: solid 2px transparent !important;
	border-radius: 0 !important;

	&:focus {
		outline: none !important;
		box-shadow: unset !important;
		border-bottom: solid 2px #20835f !important;
		border-radius: 0 !important;
	}

	&:hover {
		box-shadow: unset !important;
		border-bottom: solid 2px rgba(32, 131, 95, 0.87) !important;
		border-radius: 0 !important;
	}
}

.element-option,
#add-option {
	.icon-handle {
		height: 18px;
		width: 18px;
		transition: all 0.3s;
	}
}
</style>
