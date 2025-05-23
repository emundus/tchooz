<template>
	<div
		class="form-builder-page-section-element"
		:id="'element_' + element.id"
		v-show="(!element.hidden && element.publish !== -2) || (element.hidden && sysadmin)"
		:class="{
			unpublished: !element.publish || element.hidden,
			'properties-active': propertiesOpened === element.id,
		}"
	>
		<div class="tw-mb-2 tw-flex tw-w-full tw-items-start tw-justify-between">
			<div class="tw-w-11/12">
				<label
					class="fabrikLabel control-label tw-mb-0 tw-flex tw-w-full tw-items-center"
					@click="triggerElementProperties"
				>
					<span
						v-if="element.FRequire"
						class="material-symbols-outlined tw-mr-0 !tw-text-xs tw-text-red-600"
						style="top: -5px; position: relative"
						>emergency</span
					>
					<input
						v-if="element.label_value && element.labelsAbove != 2"
						:ref="'element-label-' + element.id"
						:id="'element-label-' + element.id"
						class="element-title editable-data tw-ml-2"
						:name="'element-label-' + element.id"
						type="text"
						v-model="element.label[shortDefaultLang]"
						:placeholder="translate('COM_EMUNDUS_ONBOARD_TYPE_' + element.plugin.toUpperCase())"
						@focusout="updateLabel"
						@keyup.enter="updateLabelKeyup"
					/>
				</label>
				<span class="fabrikElementTip fabrikElementTipAbove">{{
					element.params.rollover.replace(/(<([^>]+)>)/gi, '')
				}}</span>
			</div>
			<div id="element-action-icons" class="tw-mt-2 tw-flex tw-items-end">
				<span class="material-symbols-outlined handle tw-cursor-grab">drag_indicator</span>
				<span
					id="delete-element"
					class="material-symbols-outlined tw-cursor-pointer tw-text-red-600"
					@click="deleteElement"
					>delete</span
				>
				<span v-if="sysadmin" class="material-symbols-outlined tw-ml-2 tw-cursor-pointer" @click="openAdmin"
					>content_copy</span
				>
			</div>
		</div>
		<div :class="'element-field fabrikElement' + element.plugin" @click="triggerElementProperties">
			<form-builder-element-options
				v-if="['radiobutton', 'checkbox'].includes(element.plugin) || (displayOptions && element.plugin === 'dropdown')"
				:element="element"
				:type="element.plugin == 'radiobutton' ? 'radio' : element.plugin"
				@update-element="$emit('update-element')"
			></form-builder-element-options>
			<form-builder-element-wysiwig
				v-else-if="element.plugin === 'display'"
				:element="element"
				type="display"
				@update-element="$emit('update-element')"
			></form-builder-element-wysiwig>
			<form-builder-element-phone-number
				v-else-if="element.plugin === 'emundus_phonenumber'"
				type="phonenumber"
				:element="element"
			></form-builder-element-phone-number>
			<form-builder-element-currency
				v-else-if="element.plugin === 'currency'"
				type="currency"
				:element="element"
			></form-builder-element-currency>
			<form-builder-element-geolocation
				v-else-if="element.plugin === 'emundus_geolocalisation'"
				type="geolocation"
				:element="element"
			></form-builder-element-geolocation>
			<form-builder-element-booking
				v-else-if="element.plugin === 'booking'"
				type="booking"
				:element="element"
			></form-builder-element-booking>
			<div v-else v-html="element.element" class="fabrikElement"></div>
		</div>
	</div>
</template>

<script>
import formBuilderService from '@/services/formbuilder.js';
import formBuilderMixin from '@/mixins/formbuilder.js';
import mixin from '@/mixins/mixin.js';
import FormBuilderElementOptions from './FormBuilderSectionSpecificElements/FormBuilderElementOptions.vue';
import FormBuilderElementWysiwig from './FormBuilderSectionSpecificElements/FormBuilderElementWysiwig.vue';
import FormBuilderElementPhoneNumber from '@/components/FormBuilder/FormBuilderSectionSpecificElements/FormBuilderElementPhoneNumber.vue';
import FormBuilderElementCurrency from '@/components/FormBuilder/FormBuilderSectionSpecificElements/FormBuilderElementCurrency.vue';
import FormBuilderElementGeolocation from '@/components/FormBuilder/FormBuilderSectionSpecificElements/FormBuilderElementGeolocation.vue';

import { useGlobalStore } from '@/stores/global.js';
import FormBuilderElementBooking from '@/components/FormBuilder/FormBuilderSectionSpecificElements/FormBuilderElementBooking.vue';

export default {
	components: {
		FormBuilderElementBooking,
		FormBuilderElementGeolocation,
		FormBuilderElementCurrency,
		FormBuilderElementPhoneNumber,
		FormBuilderElementWysiwig,
		FormBuilderElementOptions,
	},
	props: {
		element: {
			type: Object,
			default: {},
		},
	},
	mixins: [formBuilderMixin, mixin],
	data() {
		return {
			keysPressed: [],
			options_enabled: false,
		};
	},
	setup() {
		return {
			globalStore: useGlobalStore(),
		};
	},
	methods: {
		updateLabel() {
			this.element.label[this.shortDefaultLang] = this.$refs['element-label-' + this.element.id].value
				.trim()
				.replace(/[\r\n]/gm, '');

			formBuilderService
				.updateTranslation(
					{
						value: this.element.id,
						key: 'element',
					},
					this.element.label_tag,
					this.element.label,
				)
				.then((response) => {
					if (response.data.status) {
						this.element.label_tag = response.data.data;
						this.updateLastSave();
					} else {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'),
							text: this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR_SAVE_TRANSLATION'),
							icon: 'error',
							cancelButtonText: this.translate('OK'),
						});
					}
				});
		},
		updateLabelKeyup() {
			document.activeElement.blur();
		},
		updateElement() {
			formBuilderService.updateParams(this.element).then((response) => {
				if (response.data.status) {
					this.$emit('update-element');
					this.updateLastSave();
				} else {
					Swal.fire({
						title: this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'),
						text: this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR_UPDATE_PARAMS'),
						icon: 'error',
						cancelButtonText: this.translate('OK'),
					});
				}
			});
		},
		deleteElement() {
			this.swalConfirm(
				this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_ELEMENT'),
				this.element.label[this.shortDefaultLang],
				this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_ELEMENT_CONFIRM'),
				this.translate('JNO'),
				() => {
					formBuilderService.deleteElement(this.element.id);
					this.$emit('delete-element', this.element.id);
					this.updateLastSave();

					this.tipToast(this.translate('COM_EMUNDUS_FORM_BUILDER_DELETED_ELEMENT_TEXT'));
					window.addEventListener('keydown', this.cancelDelete);
				},
			);
		},
		openAdmin() {
			navigator.clipboard.writeText(this.element.id);
			Swal.fire({
				title: "Identifiant de l'élément copié",
				icon: 'success',
				showCancelButton: false,
				showConfirmButton: false,
				customClass: {
					title: 'em-swal-title',
				},
				timer: 1500,
			});
		},
		triggerElementProperties() {
			this.$emit('open-element-properties');
		},
		cancelDelete(event) {
			let elementsPending = this.$parent.$parent.$parent.$parent.$data.elementsDeletedPending;
			let index = elementsPending.indexOf(this.element.id);

			if (elementsPending.indexOf(this.element.id) === elementsPending.length - 1) {
				event.stopImmediatePropagation();
				this.keysPressed[event.key] = true;

				if ((this.keysPressed['Control'] || this.keysPressed['Meta']) && event.key === 'z') {
					formBuilderService.toggleElementPublishValue(this.element.id);
					this.$emit('cancel-delete-element', this.element.id);
					this.keysPressed = [];

					document.removeEventListener('keydown', this.cancelDelete);
					this.$parent.$parent.$parent.$parent.$data.elementsDeletedPending.splice(index, 1);
				}
			}
		},
	},
	computed: {
		sysadmin: function () {
			return parseInt(this.globalStore.hasSysadminAccess);
		},
		displayOptions: function () {
			return (
				this.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$data.selectedElement !== null &&
				this.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$data.selectedElement.id ==
					this.element.id
			);
		},
		propertiesOpened: function () {
			if (this.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$data.selectedElement !== null) {
				return this.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$data.selectedElement.id;
			} else {
				return 0;
			}
		},
	},
};
</script>

<style lang="scss">
.form-builder-page-section-element {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	justify-content: flex-start;
	margin: 12px 0;
	padding: 12px;
	border-radius: 4px;
	transition: 0.3s all;
	border: 2px solid transparent;

	.element-title {
		border: none !important;
		width: 100% !important;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;

		&:hover {
			border: none !important;
		}
	}

	.element-field.fabrikElementbirthday {
		table {
			border: none;
			width: auto;
			tr td {
				padding: 0;
				select {
					min-width: 90px;
				}
			}
		}
	}

	.element-field.fabrikElementyesno {
		.switcher {
			display: flex;
			align-items: center;
			gap: 8px;
			width: fit-content;
		}
		input {
			display: none;
		}
		input[value='0'] + label {
			align-items: center;
			-webkit-box-shadow: none;
			box-shadow: none;
			cursor: pointer;
			border: 1px solid var(--neutral-500);
			background: var(--red-700);
			border-radius: var(--em-coordinator-br) !important;
			padding: 10px 50px;
			display: -webkit-box;
			display: -ms-flexbox;
			display: flex;
			-webkit-box-pack: center;
			-ms-flex-pack: center;
			justify-content: center;
			color: var(--neutral-0);
			height: var(--em-form-height);
		}
		input[value='1'] + label {
			align-items: center;
			-webkit-box-shadow: none;
			box-shadow: none;
			cursor: pointer;
			border: 1px solid var(--neutral-500);
			color: var(--em-green-2);
			border-radius: var(--em-coordinator-br) !important;
			padding: 10px 50px;
			display: -webkit-box;
			display: -ms-flexbox;
			display: flex;
			-webkit-box-pack: center;
			-ms-flex-pack: center;
			justify-content: center;
			height: var(--em-form-height);
		}
	}

	.element-field:not(.fabrikElementdisplay) {
		.fabrikgrid_1.btn-default {
			padding: 12px;
			box-shadow: none;
			cursor: pointer;
			border: 1px solid var(--em-profile-color);
			border-radius: var(--em-coordinator-br) !important;
			width: 100% !important;
			max-width: 250px;
			display: flex;
			justify-content: center;

			span {
				margin-top: 0;
			}
		}

		.fabrikgrid_0.btn-default {
			padding: 12px;
			box-shadow: none;
			cursor: pointer;
			border: 1px solid var(--red-500);
			background: var(--red-500);
			border-radius: var(--em-coordinator-br) !important;
			width: 100% !important;
			max-width: 250px;
			display: flex;
			justify-content: center;

			span {
				margin-top: 0;
				color: var(--neutral-0) !important;
			}
		}
	}

	&.unpublished {
		opacity: 0.5;
	}

	&.properties-active {
		border: 2px solid #1c6ef2 !important;
	}

	&:hover {
		border: 2px solid var(--em-profile-color);

		#element-action-icons {
			opacity: 1;
			pointer-events: all;
		}
	}

	#element-action-icons {
		transition: 0.3s all;
		opacity: 0;
		pointer-events: none;

		.icon-handle {
			width: 18px;
			height: 18px;
		}
	}

	.element-field {
		width: 100%;

		&.element-preview-display .fabrikinput {
			height: auto;
			border: 0;
			padding: 4px 8px !important;

			&:hover {
				border: 0;
			}
		}
	}

	.element-required {
		width: 48px;
		height: 24px;
		margin-top: 15px;

		input:checked + .em-slider:before {
			transform: translateX(22px);
		}

		.em-slider {
			border-radius: 24px;

			&::before {
				height: 14px;
				width: 14px;
				bottom: 5px;
			}
		}
	}

	input:hover {
		border: 1px solid var(--neutral-600);
		box-shadow: none !important;
	}

	.fabrikElementTip {
		color: var(--em-form-tip-color);
		font-size: 12px;
		line-height: 1.5rem;
		font-weight: 400;
		font-family: var(--em-profile-font), Inter, sans-serif;
		font-style: normal;
		display: flex;
	}

	/* YES / NO */
	/* And radio buttons grouped together */

	.fabrikElementyesno .fabrikSubElementContainer .btn-group {
		width: 48.93617021276595%;
	}

	@media only all and (min-width: 48rem) and (max-width: 59.99rem) {
		.fabrikElementyesno .fabrikSubElementContainer .btn-group {
			width: 48.6187845304% !important;
		}
	}

	@media only all and (max-width: 48rem) {
		.fabrikElementyesno .fabrikSubElementContainer .btn-group {
			width: 100% !important;
		}
	}

	.fabrikElementyesno .fabrikSubElementContainer .btn-group {
		display: flex;
		gap: var(--em-form-yesno-gap);
	}

	label.btn-default.btn.btn-success.active {
		padding: var(--p-12);
		box-shadow: none;
		cursor: pointer;
		background-color: var(--em-form-yesno-bgc-yes);
		border: var(--em-form-yesno-bw) solid var(--em-form-yesno-bc-yes);
		color: var(--neutral-900);
		border-radius: var(--em-applicant-br) !important;
		width: var(--em-form-yesno-width) !important;
		display: flex;
		align-items: center;
		justify-content: center;
		height: var(--em-form-yesno-height);
		font-size: 16px;
		font-style: normal;
		line-height: 24px;
		letter-spacing: 0.0015em;
	}

	label.btn-default.btn.btn-success.active:hover {
		background-color: var(--em-form-yesno-bgc-yes-hover);
		border-color: var(--em-form-yesno-bc-yes-hover) !important;
	}

	label.btn-default.btn.btn-success.active:hover span {
		font-family: var(--em-profile-font), Inter, sans-serif;
		font-size: 16px;
		font-style: normal;
		line-height: 24px;
		letter-spacing: 0.0015em;
		color: var(--em-form-yesno-color-yes-hover);
		word-wrap: break-word;
	}

	label.btn-default.btn.btn-success.active span {
		font-family: var(--em-profile-font), Inter, sans-serif;
		font-size: 16px;
		font-style: normal;
		line-height: 24px;
		letter-spacing: 0.0015em;
		color: var(--em-form-yesno-color-yes);
		word-wrap: break-word;
	}

	label.btn-default.btn.btn-danger.active {
		height: var(--em-form-yesno-height);
		padding: var(--p-12);
		box-shadow: none;
		cursor: pointer;
		background-color: var(--em-form-yesno-bgc-no);
		border-color: var(--em-form-yesno-bc-no);
		color: var(--em-form-yesno-color-no);
		border-radius: var(--em-applicant-br) !important;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 16px;
		font-style: normal;
		line-height: 24px;
		letter-spacing: 0.0015em;
		width: var(--em-form-yesno-width) !important;
	}

	label.btn-default.btn.btn-danger.active:hover {
		background-color: var(--em-form-yesno-bgc-no-hover);
		border-color: var(--em-form-yesno-bc-no-hover) !important;
	}

	label.btn-default.btn.btn-danger.active:hover span {
		font-family: var(--em-profile-font), Inter, sans-serif;
		font-size: 16px;
		font-style: normal;
		line-height: 24px;
		letter-spacing: 0.0015em;
		color: var(--em-form-yesno-color-no-hover);
		word-wrap: break-word;
	}

	label.btn-default.btn.btn-danger.active span {
		font-family: var(--em-profile-font), Inter, sans-serif;
		font-size: 16px;
		font-style: normal;
		line-height: 24px;
		letter-spacing: 0.0015em;
		color: var(--em-form-yesno-color-no);
		word-wrap: break-word;
	}

	label.btn-default.btn:not(.active) {
		height: var(--em-form-yesno-height);
		padding: var(--p-12);
		box-shadow: none;
		cursor: pointer;
		border: var(--em-form-yesno-bw) solid var(--em-form-yesno-bc-not-active);
		background: var(--em-form-yesno-bgc-not-active);
		color: var(--em-form-yesno-color-not-active);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 16px;
		line-height: 24px;
		font-style: normal;
		letter-spacing: 0.0015em;
		width: var(--em-form-yesno-width) !important;
	}

	label.btn-default.btn:not(.active):hover {
		background-color: var(--em-form-yesno-bgc-not-active-hover);
		border-color: var(--em-form-yesno-bc-not-active-hover) !important;
	}

	label.btn-default.btn:not(.active):hover span {
		font-family: var(--em-profile-font), Inter, sans-serif;
		font-size: 16px;
		font-style: normal;
		line-height: 24px;
		letter-spacing: 0.0015em;
		color: var(--em-form-yesno-color-not-active-hover);
		word-wrap: break-word;
	}

	label.btn-default.btn:not(.active) span {
		font-family: var(--em-profile-font), Inter, sans-serif;
		font-size: 16px;
		font-style: normal;
		line-height: 24px;
		letter-spacing: 0.0015em;
		color: var(--em-form-yesno-color-not-active);
		word-wrap: break-word;
	}

	/** PANEL **/
	.fabrikElementpanel .fabrikElement .fabrikinput {
		display: flex;
		padding: var(--em-spacing-5);
		border-radius: 0.25rem;

		.fabrikElementContent {
			margin-left: var(--em-spacing-3);
			line-height: 24px;
			p:after {
				content: '';
				display: inline-block;
				width: 0px;
			}
		}
	}
}
</style>
