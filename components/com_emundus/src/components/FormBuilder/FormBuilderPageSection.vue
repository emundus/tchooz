<template>
	<div :id="'form-builder-page-section-' + section.group_id" class="form-builder-page-section tw-mb-8 tw-mt-8">
		<div class="section-card tw-flex tw-flex-col">
			<div
				class="section-identifier tw-flex tw-cursor-pointer tw-items-center tw-bg-profile-full"
				@click="closedSection = !closedSection"
			>
				<span class="material-symbols-outlined tw-mr-2 tw-text-white" v-show="section.repeat_group">library_add</span>
				{{ translate('COM_EMUNDUS_FORM_BUILDER_SECTION') }} {{ index }} /
				{{ totalSections }}
				<span class="material-symbols-outlined tw-ml-2 tw-text-white" v-show="!closedSection">unfold_less</span>
				<span class="material-symbols-outlined tw-ml-2 tw-text-white" v-show="closedSection">unfold_more</span>
			</div>
			<div class="section-content em-p-32 tw-w-full" :class="{ closed: closedSection }">
				<div class="tw-flex tw-w-full tw-items-center tw-justify-between">
					<input
						id="section-title"
						class="editable-data tw-w-full"
						:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_ADD_PAGE_TITLE_ADD')"
						v-model="section.label[shortDefaultLang]"
						@focusout="updateTitle"
						@keyup.enter="blurElement('#section-title')"
						maxlength="100"
					/>
					<div class="section-actions-wrapper">
						<span
							class="material-symbols-outlined hover-opacity tw-cursor-pointer"
							@click="moveSection('up')"
							title="Move section upwards"
							>keyboard_double_arrow_up</span
						>
						<span
							class="material-symbols-outlined hover-opacity tw-cursor-pointer"
							@click="moveSection('down')"
							title="Move section downwards"
							>keyboard_double_arrow_down</span
						>
						<span
							class="material-symbols-outlined delete hover-opacity tw-cursor-pointer tw-text-red-600"
							@click="deleteSection"
							>delete</span
						>
						<span
							class="material-symbols-outlined hover-opacity tw-cursor-pointer"
							@click="$emit('open-section-properties')"
							>settings</span
						>
					</div>
				</div>
				<transition name="slide-down">
					<div v-show="!closedSection">
						<span
							id="section-intro"
							class="editable-data description"
							ref="sectionIntro"
							contenteditable="true"
							@focusout="updateIntro"
							v-html="section.group_intro"
						>
						</span>
						<draggable
							v-model="elements"
							group="form-builder-section-elements"
							:sort="true"
							class="draggables-list"
							@end="onDragEnd"
							handle=".handle"
							:data-prid="profile_id"
							:data-page="page_id"
							:data-sid="section.group_id"
						>
							<transition-group>
								<form-builder-page-section-element
									v-for="element in elements"
									:key="element.id"
									:element="element"
									@open-element-properties="$emit('open-element-properties', element)"
									@delete-element="deleteElement"
									@cancel-delete-element="cancelDeleteElement"
									@update-element="$emit('update-element')"
								>
								</form-builder-page-section-element>
							</transition-group>
						</draggable>
						<div v-if="publishedElements.length < 1" class="empty-section-element">
							<draggable
								:list="emptySection"
								group="form-builder-section-elements"
								:sort="false"
								class="draggables-list"
								:data-prid="profile_id"
								:data-page="page_id"
								:data-sid="section.group_id"
							>
								<transition-group :data-prid="profile_id" :data-page="page_id" :data-sid="section.group_id">
									<p class="tw-w-full tw-text-center" v-for="(item, index) in emptySection" :key="index">
										{{ translate(item.text) }}
									</p>
								</transition-group>
							</draggable>
						</div>
					</div>
				</transition>
			</div>
		</div>
	</div>
</template>

<script>
import formBuilderService from '@/services/formbuilder.js';
import formBuilderMixin from '@/mixins/formbuilder.js';
import globalMixin from '@/mixins/mixin.js';
import FormBuilderPageSectionElement from './FormBuilderPageSectionElement.vue';
import { VueDraggableNext } from 'vue-draggable-next';
import { useGlobalStore } from '@/stores/global.js';

export default {
	components: {
		FormBuilderPageSectionElement,
		draggable: VueDraggableNext,
	},
	props: {
		profile_id: {
			type: Number,
			required: true,
		},
		page_id: {
			type: Number,
			required: true,
		},
		section: {
			type: Object,
			required: true,
		},
		index: {
			type: Number,
			default: 0,
		},
		totalSections: {
			type: Number,
			default: 0,
		},
	},
	mixins: [formBuilderMixin, globalMixin],
	data() {
		return {
			closedSection: false,
			elements: [],
			emptySection: [
				{
					text: 'COM_EMUNDUS_FORM_BUILDER_EMPTY_SECTION',
				},
			],
			elementsDeletedPending: [],
		};
	},
	setup() {
		return {
			globalStore: useGlobalStore(),
		};
	},
	created() {
		this.getElements();
	},
	methods: {
		getElements() {
			this.elements = Object.values(this.section.elements).length > 0 ? Object.values(this.section.elements) : [];
		},
		updateTitle() {
			this.section.label[this.shortDefaultLang] = this.section.label[this.shortDefaultLang].trim();
			formBuilderService
				.updateTranslation(
					{
						value: this.section.group_id,
						key: 'group',
					},
					this.section.group_tag,
					this.section.label,
				)
				.then((response) => {
					if (response.data.status) {
						this.section.group_tag = response.data.data;
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
		blurElement(selector) {
			document.querySelector(selector).blur();
		},
		updateIntro() {
			this.$refs.sectionIntro.innerHTML = this.$refs.sectionIntro.innerHTML.trim().replace(/[\r\n]/gm, '<br/>');
			this.section.group_intro = this.$refs.sectionIntro.innerHTML;
			formBuilderService
				.updateGroupParams(this.section.group_id, { intro: this.section.group_intro }, this.shortDefaultLang)
				.then((response) => {
					if (response.status) {
						this.updateLastSave();
					} else {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'),
							text: this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR_UPDATE_GROUP_PARAMS'),
							icon: 'error',
							cancelButtonText: this.translate('OK'),
						});
					}
				});
		},
		onDragEnd(e) {
			const toGroup = e.to.getAttribute('data-sid');

			if (toGroup == this.section.group_id) {
				const elements = this.elements.map((element, index) => {
					return { id: element.id, order: index + 1 };
				});
				const movedElement = this.elements[e.newIndex];
				formBuilderService.updateOrder(elements, this.section.group_id, movedElement).then((response) => {
					this.updateLastSave();
					let obj = {};
					this.elements.forEach((elem) => {
						obj['element' + elem.id] = elem;
					});
					this.section.elements = obj;
				});
			} else {
				this.$emit('move-element', e, this.section.group_id, toGroup);
			}
		},
		deleteElement(elementId) {
			this.section.elements['element' + elementId].publish = -2;
			this.elementsDeletedPending.push(elementId);
			this.getElements();
			this.updateLastSave();
		},
		cancelDeleteElement(elementId) {
			this.section.elements['element' + elementId].publish = true;
			this.getElements();
		},
		deleteSection() {
			this.swalConfirm(
				this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_SECTION'),
				this.section.label[this.shortDefaultLang],
				this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_SECTION_CONFIRM'),
				this.translate('JNO'),
				() => {
					formBuilderService.deleteGroup(this.section.group_id);
					this.$emit('delete-section', this.section.group_id);
					this.updateLastSave();
				},
			);
		},
		moveSection(direction = 'up') {
			this.$emit('move-section', this.section.group_id, direction);
		},
	},
	watch: {
		section: {
			handler() {
				this.getElements();
			},
			deep: true,
		},
	},
	computed: {
		publishedElements() {
			return this.elements && this.elements.length > 0
				? this.elements.filter((element) => {
						return element.publish === true && (element.hidden === false || this.sysadmin);
					})
				: [];
		},
		sysadmin: function () {
			return parseInt(this.globalStore.hasSysadminAccess);
		},
	},
};
</script>

<style lang="scss">
.form-builder-page-section {
	.section-actions-wrapper {
		min-width: fit-content;
	}

	.section-card {
		.section-identifier {
			color: white;
			padding: 8px 24px;
			border-radius: 4px 4px 0px 0px;
			display: flex;
			align-self: flex-end;
		}

		.section-content {
			border-top: 4px solid var(--em-profile-color);
			background-color: white;
			transition: all 0.3s ease-in-out;
			border-radius: calc(var(--em-default-br) / 2) 0 calc(var(--em-default-br) / 2) calc(var(--em-default-br) / 2);

			&:hover {
				.hover-opacity {
					opacity: 1;
					pointer-events: all;
				}
			}

			&.closed {
				//max-height: 93px;
			}

			.hover-opacity {
				opacity: 0;
				pointer-events: none;
				transition: all 0.3s;
			}

			#section-title {
				font-weight: 800;
				font-size: 20px;
				line-height: 25px;
			}

			.empty-section-element {
				border: 1px dashed;
				opacity: 0.2;
				padding: 11px;
				margin: 32px 0 0 0;
			}
		}
	}
}
</style>
