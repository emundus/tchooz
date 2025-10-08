<template>
	<div>
		<div id="accordion-collapse" v-for="(section, indexSection) in sections" :key="indexSection">
			<!-- Flex container for tiles -->
			<div v-if="section.type === 'tile'">
				<Tile v-bind="section.props"></Tile>
			</div>

			<div
				v-else
				class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-2xl tw-border tw-border-gray-200 tw-bg-white tw-font-medium tw-text-black tw-shadow rtl:tw-text-right"
				data-accordion-target="#accordion-collapse-body-1"
				aria-expanded="true"
				aria-controls="accordion-collapse-body-1"
			>
				<div class="tw-flex tw-flex-col">
					<div class="tw-flex tw-items-center tw-justify-between tw-p-5">
						<h2 id="accordion-collapse-heading-1" class="tw-user-select-none tw-flex tw-justify-between">
							<span :id="'Subtile' + indexSection" class="tw-user-select-none tw-text-2xl">{{
								translate(section.label)
							}}</span>
							<div
								:key="countNotifUpdate"
								v-if="sectionsToNotif.includes(indexSection) && numberNotif > 0"
								class="tw-box-border-2 -top-2 -end-2 tw-inline-flex tw-h-6 tw-w-6 tw-items-center tw-justify-center tw-rounded-full tw-border-white tw-bg-red-500"
							>
								<span class="tw-text-xs tw-font-bold tw-text-white">{{ numberNotif }}</span>
							</div>
						</h2>
						<!-- The expand icon of the section which rotates -->
						<span
							class="material-symbols-outlined tw-user-select-none hidden tw-scale-150"
							:id="'SubtitleArrow' + indexSection"
							name="SubtitleArrows"
							:class="activeSection === indexSection ? 'tw-rotate-180' : ''"
							>expand_more</span
						>
					</div>
					<span v-if="section.intro" class="tw--mt-5 tw-px-5 tw-pb-5 tw-text-sm tw-text-neutral-800">{{
						translate(section.intro)
					}}</span>
				</div>

				<!-- The content of the section -->
				<div name="SubMenuContent" class="tw-flex tw-flex-col tw-px-5 tw-pb-5">
					<!--v-if="activeSection === indexSection"-->
					<Info v-if="section.helptext" :text="section.helptext" class="tw-mb-4"></Info>
					<div v-if="section.component !== 'SubSection'">
						<component
							:ref="'component_' + section.name"
							:is="section.component"
							:key="activeSection"
							v-bind="section.props"
							@needSaving="handleNeedSaving"
						>
						</component>
					</div>
					<div v-else v-for="(subSectionElement, indexSubSection) in section.props">
						<SubSection
							:key="countNotifUpdate"
							:name="subSectionElement.label"
							:ref="'component_Subsection-' + subSectionElement.name"
							:component="subSectionElement.component"
							:props="subSectionElement.props"
							:json_source="$props.json_source"
							:notify="needToNotify[indexSubSection]"
							:index="indexSubSection"
							@needSaving="handleNeedSaving"
							@updateNotif="setCountNotifUpdate"
						></SubSection>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import SiteSettings from '@/components/Settings/SiteSettings.vue';
import EditTheme from '@/components/Settings/Style/EditTheme.vue';
import EditStatus from '@/components/Settings/Files/EditStatus.vue';
import EditTags from '@/components/Settings/Files/EditTags.vue';
import MultifactorAuthentication from '@/components/Settings/Users/MultifactorAuthentication.vue';
import SecurityRules from '@/components/Settings/Users/SecurityRules.vue';
import Categories from '@/components/Settings/Users/Categories.vue';
import General from '@/components/Settings/Style/General.vue';
import Orphelins from '@/components/Settings/TranslationTool/Orphelins.vue';
import Translations from '@/components/Settings/TranslationTool/Translations.vue';
import EditArticle from '@/components/Settings/Content/EditArticle.vue';
import EditFooter from '@/components/Settings/Content/EditFooter.vue';
import Info from '@/components/Utils/Info.vue';
import SubSection from '@/components/Settings/SubSection.vue';
import Swal from 'sweetalert2';
import Tile from '@/components/Settings/Tile.vue';
import settingsService from '@/services/settings.js';

const assetsPath = '/components/com_emundus/src/assets/data/';
const getPath = (path) => `${assetsPath}${path}`;

import { useSettingsStore } from '@/stores/settings.js';

export default {
	name: 'SettingsContent',
	components: {
		SiteSettings,
		EditTheme,
		EditStatus,
		EditTags,
		General,
		Orphelins,
		Translations,
		EditArticle,
		EditFooter,
		Info,
		SubSection,
		Tile,
		MultifactorAuthentication,
		SecurityRules,
		Categories,
	},

	props: {
		json_source: {
			type: String,
			required: true,
		},
	},

	mixins: [],

	data() {
		return {
			sections: [],

			activeSection: null,
			needSaving: false,
			notificationElements: [],
			sectionsToNotif: [],
			countNotifUpdate: 0,
			needToNotify: [],
			numberNotif: 0,
		};
	},
	setup() {
		const settingsStore = useSettingsStore();

		return {
			settingsStore,
		};
	},
	async created() {
		import(getPath(this.$props.json_source)).then((result) => {
			if (result) {
				this.sections = result.default;
			}
		});
		const sessionSection = sessionStorage.getItem('tchooz_settings_selected_section/' + document.location.hostname);
		if (sessionSection) {
			this.activeSection = parseInt(sessionSection);
		}
		this.$emit('listSections', this.sections, 'sections');
	},

	mounted() {
		this.initsmallDotnotif();
	},
	methods: {
		async saveMethod() {
			await this.saveSection(this.sections[this.activeSection]);
			return true;
		},

		getSection() {
			return this.sections[this.activeSection];
		},

		async saveSection(section, index = null) {
			if (section.component === 'SubSection') {
				for (let i in section.props) {
					let url = 'component_Subsection-' + section.props[i].name;
					if (this.$refs[url]) {
						this.$refs[url][0].$children[0].saveContent();
					}
				}
				this.setCountNotifUpdate();

				if (index !== null) {
					this.handleActiveSection(index);
				}
			} else if (section.component !== 'SubSection') {
				let vue_component = this.$refs['component_' + section.name];
				if (Array.isArray(vue_component)) {
					vue_component = vue_component[0];
				}

				if (typeof vue_component.saveMethod !== 'function') {
					console.error('The component ' + section.name + ' does not have a saveMethod function');
					return;
				}

				vue_component.saveMethod().then((response) => {
					if (response === true) {
						if (index !== null) {
							this.handleActiveSection(index);
						}
					}
				});
			}
		},

		handleNeedSaving(needSaving) {
			this.settingsStore.updateNeedSaving(needSaving);
		},
		handleSection(index) {
			if (this.settingsStore.needSaving) {
				this.showConfirmationDialog(index).then((result) => {
					if (result.value) {
						this.handleNeedSaving(false);
						this.saveSection(this.getSection(), index);
						this.settingsStore.updateNeedSaving(false);
					} else {
						this.settingsStore.updateNeedSaving(false);
						this.handleActiveSection(index);
					}
				});
			} else {
				this.handleActiveSection(index);
			}
		},

		showConfirmationDialog: function () {
			return Swal.fire({
				title: this.translate('COM_EMUNDUS_ONBOARD_WARNING'),
				text: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_UNSAVED'),
				showCancelButton: true,
				confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE'),
				cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL_UPDATES'),
				reverseButtons: true,
				allowOutsideClick: false,
				customClass: {
					title: 'em-swal-title',
					cancelButton: 'em-swal-cancel-button',
					confirmButton: 'em-swal-confirm-button',
				},
			});
		},

		saveAllSections(index) {
			this.sections.forEach((section) => {
				if (section.component === 'SubSection') {
					this.saveSubSection(section);
				} else {
					this.saveSection(this.sections[this.activeSection], index);
				}
			});
		},

		saveSubSection(section) {
			for (let i = 0; i < section.props[i]; i++) {
				let vue_component = this.$refs['component_Subsection-' + section.props[i].name];
				if (vue_component && typeof vue_component.saveMethod === 'function') {
					vue_component.saveMethod();
				}
			}
			this.setCountNotifUpdate();
		},
		handleActiveSection(index) {
			if (index === this.activeSection) {
				this.activeSection = null;
			} else {
				this.activeSection = index;
			}
		},
		async getNeedToModify() {
			const response = await settingsService.getAllArticleNeedToModify();
			return response.data;
		},
		async setCountNotifUpdate(index, needNotify) {
			if (index !== undefined) {
				for (let i in this.sections) {
					if (this.sections[i].notify === 1) {
						if (this.sections[i].component === 'SubSection') {
							this.needToNotify[index] = needNotify ? true : false;
						}
					}
				}
				this.countNotifUpdate++;
			}
		},
		async initsmallDotnotif() {
			for (let i in this.sections) {
				if (this.sections[i].notify === 1) {
					this.sectionsToNotif.push(parseInt(i));
					const response = await this.getNeedToModify();
					this.notificationElements = Object.values(response);

					if (this.sections[i].component === 'SubSection') {
						for (let k in this.notificationElements) {
							let foundIndex = this.sections[i].props.findIndex(
								(subSection) => subSection.props.article_alias === this.notificationElements[k].alias,
							);
							if (foundIndex !== -1) {
								this.needToNotify[foundIndex] = true;
							}
						}
					}
					this.countNotifUpdate++;
				}
			}
		},
	},
	watch: {
		activeSection: function (val) {
			sessionStorage.setItem('tchooz_settings_selected_section/' + document.location.hostname, this.activeSection);
			this.$emit('sectionSelected', this.sections[val]);
		},
		countNotifUpdate: function () {
			this.numberNotif = 0;
			for (let i = 0; i < this.needToNotify.length; i++) {
				if (this.needToNotify[i]) {
					this.numberNotif++;
				}
			}
			this.$emit('updateNotif', false);
		},
	},
	computed: {},
};
</script>

<style scoped></style>
