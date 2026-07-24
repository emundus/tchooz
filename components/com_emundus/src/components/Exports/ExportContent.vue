<script>
import Parameter from '@/components/Utils/Parameter.vue';
import Tabs from '@/components/Utils/Tabs.vue';
import { Button, Icon } from '@emundus/ui';
import exportService from '@/services/export.js';
import { VueDraggableNext as draggable } from 'vue-draggable-next';

const MAX_SELECTED_BY_CONTENT_TYPE = {
	content_header: 5,
	content_synthesis: 10,
};

export default {
	name: 'ExportContent',
	components: { Icon, Tabs, Parameter, Button, draggable },

	props: {
		modelValue: {
			type: Array,
			default: null,
		},
		selectedFormat: {
			type: String,
			default: 'xlsx',
		},
		contentType: {
			type: String,
			default: 'content_main',
		},
		elements: {
			type: Array,
			default: [],
		},
		loading: {
			type: Boolean,
			default: false,
		},
		elementsLoading: {
			type: Boolean,
			default: false,
		},
		// Whether default elements may be pre-selected on first setup (suppressed once the user has curated the list)
		applyDefaults: {
			type: Boolean,
			default: true,
		},
	},
	emits: ['update:modelValue', 'toggle-content-menu', 'initialized'],
	data() {
		return {
			localState: 'applicant',

			openedSections: [],
			subElementsLoading: 0,

			selectedElements: [],
		};
	},
	created() {
		// Defaults are a one-time convenience for a fresh export; once curated (even emptied), the
		// parent suppresses them via applyDefaults so the user's choice is not overwritten on remount.
		if (this.applyDefaults && this.modelValue.length <= 0) {
			if (this.selectedFormat === 'xlsx' || this.contentType === 'content_synthesis') {
				this.getDefaultSynthesis();
			}
			if (this.contentType === 'content_header') {
				this.getDefaultHeader();
			}
			this.$emit('initialized', this.contentType);
		}

		this.selectedElements = this.modelValue;
	},
	methods: {
		toggleState(value) {
			this.localState = this.localState === value ? null : value;

			this.$emit('toggle-content-menu', value);
		},
		isActive(value) {
			return this.localState === value;
		},

		updateOpenedSections(sectionId, type) {
			// check if subelements are loaded, if not, load them, then display
			if (type === 'step') {
				let foundElement = this.elements.find((element) => {
					return element.profile_id == sectionId;
				});

				if (foundElement && foundElement.forms.length < 1) {
					this.subElementsLoading = sectionId;

					exportService
						.getSubElements(sectionId, this.localState)
						.then((response) => {
							foundElement.forms = response.data;
							this.subElementsLoading = 0;
						})
						.catch((error) => {
							this.subElementsLoading = 0;
						});
				}
			}

			const idToUpdate = type + '-' + sectionId;
			if (this.openedSections.includes(idToUpdate)) {
				this.openedSections = this.openedSections.filter((id) => id !== idToUpdate);
			} else {
				this.openedSections.push(idToUpdate);
			}
		},
		checkOpenedSections(sectionId, type) {
			const idToCheck = type + '-' + sectionId;
			return this.openedSections.includes(idToCheck);
		},

		addSelectedStep(step) {
			let foundElement = this.elements.find((element) => {
				return element.profile_id == step.profile_id;
			});

			if (foundElement && foundElement.forms.length < 1) {
				this.subElementsLoading = step.profile_id;

				exportService
					.getSubElements(step.profile_id, this.localState)
					.then((response) => {
						foundElement.forms = response.data;
						this.subElementsLoading = 0;

						Object.values(step.forms).forEach((form) => {
							this.addSelectedForm(form);
						});
					})
					.catch((error) => {
						this.subElementsLoading = 0;
					});
			} else {
				Object.values(step.forms).forEach((form) => {
					this.addSelectedForm(form);
				});
			}
		},
		addSelectedForm(form) {
			if (form.groups) {
				Object.values(form.groups).forEach((group) => {
					this.addSelectedGroup(group);
				});
			}
		},
		addSelectedGroup(group) {
			if (group.elements) {
				Object.values(group.elements).forEach((element) => {
					this.addSelectedElement(element);
				});
			}
		},
		addSelectedElement(element) {
			this.addElement(element);
		},
		addElement(element) {
			if (this.isLimitReached) {
				return;
			}

			if (!this.selectedElements.some((el) => el.id === element.id)) {
				this.selectedElements.push(element);

				this.emitSelected();
			}
		},
		removeSelectedElement(element) {
			this.selectedElements = this.selectedElements.filter((el) => el.id !== element.id);
			this.emitSelected();
		},
		clearSelected() {
			this.selectedElements = [];
			this.emitSelected();
		},
		emitSelected() {
			this.$emit('update:modelValue', this.selectedElements);
		},

		disabled(element) {
			return this.isLimitReached || this.selectedElements.some((el) => el.id === element.id);
		},

		isGroupAllSelected(group) {
			const elements = group.elements ? Object.values(group.elements) : [];
			if (elements.length === 0) {
				return false;
			}

			return elements.every((element) => this.selectedElements.some((el) => el.id === element.id));
		},

		isFormAllSelected(form) {
			const groups = form.groups ? Object.values(form.groups) : [];
			if (groups.length === 0) {
				return false;
			}

			return groups.every((group) => this.isGroupAllSelected(group));
		},

		getDefaultSynthesis() {
			exportService.getDefaultSynthesisElements(this.selectedFormat).then((response) => {
				if (response.status) {
					this.selectedElements = [];

					if (response.data && response.data.length > 0) {
						response.data.forEach((element) => {
							this.addElement(element);
						});
					}
				}
			});
		},
		getDefaultHeader() {
			exportService.getDefaultHeaderElements().then((response) => {
				if (response.status) {
					this.selectedElements = [];

					if (response.data && response.data.length > 0) {
						response.data.forEach((element) => {
							this.addElement(element);
						});
					}
				}
			});
		},
	},
	computed: {
		maxSelectedElements() {
			return MAX_SELECTED_BY_CONTENT_TYPE[this.contentType] ?? Infinity;
		},
		isLimitReached() {
			return this.selectedElements.length >= this.maxSelectedElements;
		},
		tabsOptions() {
			let tabs = [];

			if (this.contentType !== 'content_attachment') {
				tabs.push(
					{
						id: 1,
						value: 'applicant',
						label: 'COM_EMUNDUS_EXPORTS_APPLICANTS_TAB',
					},
					{
						id: 2,
						value: 'management',
						label: 'COM_EMUNDUS_EXPORTS_MANAGEMENTS_TAB',
					},
				);
			}

			switch (this.selectedFormat) {
				case 'xlsx':
					tabs.push({
						id: 3,
						value: 'other',
						label: 'COM_EMUNDUS_EXPORTS_OTHER_TAB',
					});
					break;
				case 'pdf':
					if (this.contentType === 'content_header' || this.contentType === 'content_synthesis') {
						tabs.push({
							id: 3,
							value: 'other',
							label: 'COM_EMUNDUS_EXPORTS_OTHER_TAB',
						});
					}
					break;
			}

			return tabs;
		},
	},
};
</script>

<template>
	<div class="tw-grid tw-w-full tw-grid-cols-2">
		<div class="tw-z-10">
			<div
				class="tw-relative tw-flex tw-max-h-[60vh] tw-min-h-[60vh] tw-flex-col tw-gap-2 tw-overflow-scroll tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-5 tw-shadow-card"
			>
				<h2>{{ this.translate('COM_EMUNDUS_EXPORT_AVAILABLE_CONTENT') }}</h2>

				<div class="tw-flex tw-shrink-0 tw-flex-wrap tw-items-center tw-gap-2 tw-overflow-auto">
					<Button
						v-for="option in tabsOptions"
						:key="option.value"
						emphasis="activation"
						:active="isActive(option.value)"
						@click="toggleState(option.value)"
					>
						{{ translate(option.label) }}
					</Button>
				</div>
				<hr />

				<div v-if="loading" class="tw-flex tw-flex-col tw-items-center tw-justify-center">
					<div class="em-loader"></div>

					<span class="tw-mt-1" v-if="elementsLoading">{{ this.translate('COM_EMUNDUS_EXPORT_ELEMENTS_LOADER') }}</span>
				</div>

				<div v-if="!loading">
					<div class="tw-mt-2 tw-flex tw-flex-col tw-gap-3" v-if="elements.length > 0">
						<div
							v-for="step in elements"
							:key="step.profile_id"
							:id="'step-' + step.profile_id"
							class="tw-relative tw-rounded-coordinator-cards tw-border tw-border-profile-light tw-bg-white tw-p-0"
						>
							<div
								class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-bg-profile-light tw-p-2 tw-px-4 tw-py-2 tw-text-profile-full"
								:class="{
									'tw-rounded-t-coordinator-cards': checkOpenedSections(step.profile_id, 'step'),
									'tw-rounded-coordinator-cards': !checkOpenedSections(step.profile_id, 'step'),
								}"
							>
								<div
									class="tw-flex tw-w-full tw-flex-col tw-gap-1"
									@click="updateOpenedSections(step.profile_id, 'step')"
								>
									<div class="tw-flex tw-w-full tw-items-center tw-gap-2 tw-text-profile-full">
										<span>{{ step.label }}</span>
										<Icon v-if="!checkOpenedSections(step.profile_id, 'step')" name="chevron_right" />
										<Icon v-else name="keyboard_arrow_up" />
									</div>
									<span
										class="tw-w-fit tw-rounded-coordinator tw-bg-neutral-400 tw-px-2 tw-py-1 tw-text-xs tw-text-neutral-900"
										v-if="step.campaign_label && step.campaign_label !== ''"
										>{{ step.campaign_label }}</span
									>
								</div>
								<Button @click="addSelectedStep(step)" :disabled="isLimitReached">
									<template #leading>
										<Icon name="arrow_forward" />
									</template>
								</Button>
							</div>

							<div class="tw-flex tw-flex-col tw-gap-2 tw-p-3" v-if="checkOpenedSections(step.profile_id, 'step')">
								<div
									v-if="subElementsLoading > 0 && subElementsLoading === step.profile_id"
									class="tw-flex tw-flex-col tw-items-center tw-justify-center"
								>
									<div class="em-loader"></div>
									<span class="tw-mt-1">{{ this.translate('COM_EMUNDUS_EXPORT_ELEMENTS_LOADER') }}</span>
								</div>

								<div
									v-else
									v-for="form in step.forms"
									:key="form.id"
									class="tw-relative tw-rounded-coordinator-cards tw-border tw-border-profile-light tw-bg-white tw-p-0"
								>
									<div
										class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-bg-profile-light tw-px-4 tw-py-2 tw-text-profile-full"
										:class="{
											'tw-rounded-t-coordinator-cards': checkOpenedSections(form.id, 'form'),
											'tw-rounded-coordinator-cards': !checkOpenedSections(form.id, 'form'),
										}"
									>
										<div
											class="tw-flex tw-w-full tw-items-center tw-gap-2"
											@click="updateOpenedSections(form.id, 'form')"
										>
											<span>{{ form.label }}</span>
											<Icon v-if="!checkOpenedSections(form.id, 'form')" name="chevron_right" />
											<Icon v-else name="keyboard_arrow_up" />
										</div>
										<Button @click="addSelectedForm(form)" :disabled="isLimitReached || isFormAllSelected(form)">
											<template #leading>
												<Icon name="arrow_forward" />
											</template>
										</Button>
									</div>

									<div class="tw-flex tw-flex-col tw-gap-2 tw-p-3" v-if="checkOpenedSections(form.id, 'form')">
										<div
											v-for="group in form.groups"
											:key="group.id"
											class="tw-relative tw-rounded-coordinator-cards tw-border tw-border-profile-light tw-bg-white tw-p-0"
										>
											<div
												class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-rounded-t-coordinator-cards tw-bg-profile-light tw-px-4 tw-py-2 tw-text-profile-full"
												v-if="group.label !== ''"
											>
												<span class="tw-w-full">{{ group.label }}</span>
												<Button
													@click="addSelectedGroup(group)"
													:disabled="isLimitReached || isGroupAllSelected(group)"
												>
													<template #leading>
														<Icon name="arrow_forward" />
													</template>
												</Button>
											</div>

											<div class="tw-flex tw-flex-col tw-gap-2 tw-p-3">
												<div
													v-for="field in group.elements"
													:key="field.id"
													class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-px-4 tw-py-2"
													v-show="!['panel', 'display', 'emundus_fileupload'].includes(field.plugin)"
												>
													<span class="tw-w-full" v-if="field.label !== ''">{{ field.label }}</span>
													<span class="tw-w-full" v-else>{{ field.plugin_name }}</span>
													<Button @click="addSelectedElement(field)" :disabled="disabled(field)">
														<template #leading>
															<Icon name="arrow_forward" />
														</template>
													</Button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<p class="tw-text-center tw-text-neutral-600" v-else>
						{{ translate('COM_EMUNDUS_EXPORT_ELEMENTS_NO_ELEMENTS_FOUND') }}
					</p>
				</div>
			</div>
		</div>

		<div>
			<div
				class="tw-relative -tw-ml-8 tw-max-h-[60vh] tw-min-h-[60vh] tw-overflow-scroll tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-neutral-100 tw-py-6 tw-pl-12 tw-pr-6 tw-shadow-card"
			>
				<div>
					<div class="tw-mb-2 tw-flex tw-w-full tw-items-center tw-justify-between" v-if="selectedElements.length > 0">
						<h2>
							{{ this.translate('COM_EMUNDUS_EXPORT_EXPORTING_CONTENT') }}
							({{ selectedElements.length
							}}<span v-if="maxSelectedElements !== Infinity"> / {{ maxSelectedElements }}</span
							>)
						</h2>
						<Button @click="clearSelected">
							{{ this.translate('COM_EMUNDUS_EXPORT_CLEAR_ALL') }}
						</Button>
					</div>
					<div v-else>
						<h2>{{ this.translate('COM_EMUNDUS_EXPORT_EXPORTING_CONTENT') }}</h2>
					</div>

					<template v-if="selectedElements.length > 0">
						<draggable
							class="tw-flex tw-flex-col tw-gap-3"
							v-model="selectedElements"
							group="exports-elements"
							handle=".handle"
							:sort="selectedFormat === 'xlsx'"
							@change="emitSelected"
						>
							<transition-group>
								<div
									v-for="element in selectedElements"
									:key="element.id"
									class="tw-flex tw-items-center tw-justify-between tw-rounded-coordinator-cards tw-border tw-border-profile-light tw-bg-white tw-p-3"
								>
									<div class="tw-flex tw-items-center tw-gap-3">
										<span v-if="selectedFormat === 'xlsx'" class="material-symbols-outlined handle tw-cursor-grab"
											>drag_indicator</span
										>
										<div class="tw-flex tw-flex-col">
											<span>{{ element.label }}</span>
											<span class="tw-text-xs tw-text-neutral-500">{{ element.plugin_name }}</span>
										</div>
									</div>
									<Button @click="removeSelectedElement(element)" variant="danger" emphasis="lite">
										<template #leading>
											<Icon name="delete" />
										</template>
									</Button>
								</div>
							</transition-group>
						</draggable>
					</template>
					<div v-else v-html="this.translate('COM_EMUNDUS_EXPORT_NO_CONTENT_SELECTED')"></div>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
