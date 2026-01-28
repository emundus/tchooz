<template>
	<div id="form-builder-page">
		<div
			class="tw-flex tw-flex-col tw-rounded-coordinator tw-border-2 tw-border-transparent tw-p-2 hover:tw-border-profile-full hover:tw-bg-neutral-300"
		>
			<div class="tw-flex tw-cursor-pointer tw-items-center tw-justify-between">
				<span
					@click="$emit('open-page-properties', page)"
					class="tw-w-full tw-text-2xl tw-font-semibold"
					id="page-title"
					ref="pageTitle"
					v-html="page.label"
				></span>
				<button id="add-page-modele" class="tw-btn-cancel !tw-w-auto" @click="$emit('open-create-model', page.id)">
					<span
						class="material-symbols-outlined tw-cursor-pointer"
						v-if="mode === 'forms'"
						:title="translate('COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_TITLE')"
						>post_add</span
					>
					{{ translate('COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_TITLE') }}
				</button>
			</div>
			<div
				id="pageDescription"
				ref="pageDescription"
				class="tw-cursor-pointer"
				v-html="page.intro"
				@click="$emit('open-page-properties', page)"
			></div>
		</div>

		<div class="form-builder-page-sections tw-mt-2">
			<button
				v-if="sections.length > 0"
				id="add-section"
				class="tw-btn-primary tw-rounded-coordinator tw-px-6 tw-py-3"
				@click="addSection()"
			>
				{{ translate('COM_EMUNDUS_FORM_BUILDER_ADD_SECTION') }}
			</button>
			<form-builder-page-section
				v-for="(section, index) in sections"
				:key="section.group_id"
				:profile_id="parseInt(profile_id)"
				:page_id="parseInt(page.id)"
				:section="section"
				:index="index + 1"
				:totalSections="sections.length"
				:ref="'section-' + section.group_id"
				@open-element-properties="$emit('open-element-properties', $event)"
				@move-element="updateElementsOrder"
				@delete-section="deleteSection"
				@update-element="getSections"
				@move-section="moveSection"
				@open-section-properties="$emit('open-section-properties', section)"
			>
			</form-builder-page-section>
		</div>
		<button id="add-section" class="tw-btn-primary tw-rounded-coordinator tw-px-6 tw-py-3" @click="addSection()">
			{{ translate('COM_EMUNDUS_FORM_BUILDER_ADD_SECTION') }}
		</button>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import formService from '@/services/form.js';
import formBuilderService from '@/services/formbuilder.js';
import translationService from '@/services/translations.js';

import FormBuilderPageSection from '@/components/FormBuilder/FormBuilderPageSection.vue';
import formBuilderMixin from '@/mixins/formbuilder.js';
import globalMixin from '@/mixins/mixin.js';
import errorMixin from '@/mixins/errors.js';

import { useFormBuilderStore } from '@/stores/formbuilder.js';
import { useGlobalStore } from '@/stores/global.js';

export default {
	components: {
		FormBuilderPageSection,
	},
	props: {
		profile_id: {
			type: Number,
			default: 0,
		},
		page: {
			type: Object,
			default: () => {},
		},
		mode: {
			type: String,
			default: 'forms',
		},
	},
	mixins: [formBuilderMixin, globalMixin, errorMixin],
	data() {
		return {
			fabrikPage: {},
			sections: [],

			loading: false,
		};
	},
	mounted() {
		if (this.page.id) {
			this.getSections();
		}
	},
	methods: {
		getSections(eltid = null, scrollTo = false) {
			this.loading = true;

			formService.getPageObject(this.page.id).then((response) => {
				if (response.status && response.data !== '') {
					this.fabrikPage = response.data;
					this.title = this.fabrikPage.show_title.label;

					const groups = Object.values(response.data.Groups);
					this.sections = groups.filter((group) => group.hidden_group != -1);
					if (eltid) {
						setTimeout(() => {
							if (scrollTo) {
								document
									.getElementById('center_content')
									.scrollTo(0, document.getElementById('center_content').scrollHeight);
							}
							document.getElementById('element_' + eltid).style.backgroundColor = 'var(--main-50)';
							document.getElementById('element_' + eltid).style.borderColor = 'var(--main-400)';
							document.getElementById('element-label-' + eltid).focus();
							setTimeout(() => {
								document.getElementById('element_' + eltid).style.backgroundColor = 'inherit';
								document.getElementById('element_' + eltid).style.borderColor = '';
							}, 1500);
						}, 300);
					}

					const allSectionsElements = [];
					this.sections.forEach((section) => {
						if (section.elements) {
							Object.values(section.elements).forEach((element) => {
								if (eltid && element.id === eltid && scrollTo) {
									this.$emit('open-element-properties', element);
								}

								allSectionsElements.push({
									label: section.label + ' - ' + element.label,
									value: element.db_table_name + '___' + element.name,
									publish: element.publish,
									plugin: element.plugin,
									element_id: element.id,
									name: element.name,
								});
							});
						}
					});

					useFormBuilderStore().updatePageElements(allSectionsElements);
				} else {
					this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
				}

				this.loading = false;
			});
		},
		addSection() {
			if (this.sections.length < 10) {
				formBuilderService
					.createSimpleGroup(
						this.page.id,
						{
							fr: 'Nouvelle section',
							en: 'New section',
						},
						this.mode,
					)
					.then((response) => {
						if (response.status) {
							this.getSections();
							this.updateLastSave();
						} else {
							this.displayError(
								this.translate('COM_EMUNDUS_FORM_BUILDER_CREATE_SECTION_ERROR'),
								this.translate(response.msg),
							);
						}
					})
					.catch((error) => {
						this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_CREATE_SECTION_ERROR'), error);
					});
			} else {
				this.displayError(
					this.translate('COM_EMUNDUS_FORM_BUILDER_MAX_SECTION_TITLE'),
					this.translate('COM_EMUNDUS_FORM_BUILDER_MAX_SECTION_TEXT'),
				);
			}
		},
		moveSection(sectionId, direction) {
			let sectionsInOrder = this.sections.map((section, index) => {
				return {
					id: section.group_id,
					order: index,
				};
			});

			const index = sectionsInOrder.findIndex((section) => sectionId === section.id);
			const sectionToMove = sectionsInOrder[index].id;
			if (direction === 'up') {
				if (index > 0) {
					sectionsInOrder[index].id = sectionsInOrder[index - 1].id;
					sectionsInOrder[index - 1].id = sectionToMove;
				}
			} else {
				if (index < sectionsInOrder.length - 1) {
					sectionsInOrder[index].id = sectionsInOrder[index + 1].id;
					sectionsInOrder[index + 1].id = sectionToMove;
				}
			}

			formBuilderService.reorderSections(this.page.id, sectionsInOrder);

			const oldOrderSections = this.sections;
			let newOrderSections = [];
			sectionsInOrder.forEach((section) => {
				newOrderSections.push(oldOrderSections.find((oldSection) => oldSection.group_id === section.id));
			});
			this.sections = newOrderSections;
		},
		updateElementsOrder(event, fromGroup, toGroup) {
			let updated = false;

			if (fromGroup > 0 && toGroup > 0 && fromGroup != toGroup) {
				const sectionFrom = this.sections.find((section) => section.group_id === fromGroup);
				const fromElements = Object.values(sectionFrom.elements);
				const movedElement = fromElements[event.oldIndex];

				if (movedElement !== undefined && movedElement !== null && movedElement.id) {
					const foundElement = this.$refs['section-' + toGroup][0].elements.find(
						(element) => element.id === movedElement.id,
					);

					if (foundElement === undefined || foundElement === null) {
						this.$refs['section-' + toGroup][0].elements.splice(event.newIndex, 0, movedElement);
					}

					const toElements = this.$refs['section-' + toGroup][0].elements.map((element, index) => {
						return { id: element.id, order: index + 1 };
					});
					formBuilderService.updateOrder(toElements, toGroup, movedElement).then((response) => {
						updated = response.data.status;

						if (!updated) {
							this.displayError('COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED', '');
						}
					});
					this.updateLastSave();
				} else {
					this.displayError('COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED', '');
				}
			} else {
				this.displayError('COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED', '');
			}
		},
		deleteSection(sectionId) {
			this.sections = this.sections.filter((section) => section.group_id !== sectionId);
			this.updateLastSave();
		},
	},
};
</script>

<style lang="scss">
#form-builder-page {
	width: calc(100% - 80px);
	margin: 40px 40px;

	#add-section {
		width: fit-content;
		margin: auto;
	}
}
</style>
