<template>
	<div class="tw-flex tw-w-full tw-gap-8">
		<SidebarMenu
			v-if="menusList.length > 0"
			:key="keyMenu"
			:menus-list="menusList"
			:id="'settings_menus'"
			@listMenus="GetList"
			@menuSelected="handleMenu"
		/>

		<div class="tw-w-full tw-overflow-hidden tw-pb-3 tw-pl-0 tw-pr-8 tw-pt-6" v-if="activeMenuItem">
			<h1 class="tw-mb-3 tw-pl-1 tw-text-2xl tw-font-semibold tw-text-profile-full">
				<span class="material-symbols-outlined tw-me-2 tw-scale-150 tw-text-profile-full">
					{{ activeMenuItem.icon }}
				</span>
				{{ translate(activeMenuItem.label) }}
			</h1>

			<div>
				<SettingsContent
					:ref="'content_' + activeMenuItem.name"
					:key="'json_' + activeMenuItem.name + clicker"
					v-if="activeMenuItem.type === 'JSON'"
					:json_source="'settings/sections/' + activeMenuItem.source"
					@needSaving="handleNeedSaving"
					@listSections="GetList"
					:class="activeMenuItem.format === 'Tile' ? 'tw-flex tw-flex-wrap tw-justify-between' : ''"
				/>

				<div id="accordion-collapse" v-else-if="activeMenuItem.type === 'sectionComponent'">
					<SectionComponent
						:activeMenuItem="activeMenuItem"
						:activeSectionComponent="activeSectionComponent"
						@handleSectionComponent="handleSectionComponent"
						@needSaving="handleNeedSaving"
					></SectionComponent>
				</div>
				<div v-else>
					<component
						:ref="'content_' + activeMenuItem.name"
						:is="activeMenuItem.component"
						:key="'component_' + activeMenuItem.name"
						v-bind="activeMenuItem.props"
						@needSaving="handleNeedSaving"
					/>
				</div>
			</div>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import EditEmailJoomla from '@/components/Settings/EditEmailJoomla.vue';
import WebSecurity from '@/components/Settings/WebSecurity/WebSecurity.vue';

import Multiselect from 'vue-multiselect';
import SidebarMenu from '@/components/Menus/SidebarMenu.vue';
import SettingsContent from '@/components/Settings/SettingsContent.vue';
import Addons from '@/components/Settings/Addons.vue';
import Integration from '@/components/Settings/Integration.vue';
import Info from '@/components/Utils/Info.vue';
import SectionComponent from '@/components/Settings/SectionComponent.vue';
import WorkflowSettings from '@/views/Workflows/WorkflowSettings.vue';

import Swal from 'sweetalert2';

import { useSettingsStore } from '@/stores/settings.js';
import menus from '@/assets/data/settings/menus.js';
import settingsService from '@/services/settings.js';

export default {
	name: 'globalSettings',
	components: {
		SectionComponent,
		SettingsContent,
		SidebarMenu,
		EditEmailJoomla,
		WebSecurity,
		Multiselect,
		Addons,
		Integration,
		Info,
		WorkflowSettings,
	},
	props: {
		actualLanguage: {
			type: String,
			default: 'fr',
		},
		coordinatorAccess: {
			type: Number,
			default: 1,
		},
		manyLanguages: {
			type: Number,
			default: 1,
		},
		helptext: {
			type: String,
			default: '',
		},
	},

	data: () => ({
		saving: false,
		endSaving: false,
		loading: null,
		needSaving: false,
		activeSectionComponent: 0,
		activeMenuItem: null,
		activeMenu: null,

		keyMenu: 0,
		clicker: 0,
		activeSection: null,
		urlRedirectMenu: false,
		urlRedirectSection: false,
		Menus: [],
		Sections: [],

		menusList: [],
	}),
	setup() {
		const settingsStore = useSettingsStore();

		return {
			settingsStore,
		};
	},
	created() {
		let menusToDisplay = menus;

		settingsService.getApps().then((response) => {
			if (response.data.length === 0) {
				//Remove the Integration menu if there are no apps
				for (const menu of menusToDisplay) {
					if (menu.name === 'integration') {
						menusToDisplay.splice(menusToDisplay.indexOf(menu), 1);
					}
				}
			}

			this.menusList = menusToDisplay;
		});
	},
	mounted() {
		if (sessionStorage.getItem('goToMenu')) {
			this.findMenu(sessionStorage.getItem('goToMenu'));
			this.urlRedirectMenu = true;
			sessionStorage.removeItem('goToMenu');
		}
		if (sessionStorage.getItem('goToSection')) {
			this.urlRedirectSection = true;
		}
	},

	methods: {
		handleNeedSaving(needSaving) {
			this.settingsStore.updateNeedSaving(needSaving);
		},
		GetList(list, type) {
			if (type === 'menus') {
				this.Menus = list;
			} else if (type === 'sections') {
				this.Sections = list;
			}
		},

		handleMenu(item) {
			if (this.settingsStore.needSaving) {
				Swal.fire({
					title: this.translate('COM_EMUNDUS_ONBOARD_WARNING'),
					text:
						this.activeMenuItem.component === 'EditEmailJoomla'
							? this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_UNSAVED_MUST_TEST_MAIL')
							: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_UNSAVED'),
					showCancelButton: true,
					confirmButtonText:
						this.activeMenuItem.component === 'EditEmailJoomla'
							? this.translate('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_BT')
							: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE'),
					cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL_UPDATES'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						cancelButton: 'em-swal-cancel-button',
						confirmButton: 'em-swal-confirm-button',
					},
				}).then((result) => {
					this.handleNeedSaving(false);

					if (result.value) {
						this.saveSection(this.activeMenuItem, item);
					} else {
						this.activeMenuItem = item;
					}
				});
			} else {
				this.activeMenuItem = item;
			}
		},
		saveSection(menu, item = null) {
			let vue_component = this.$refs['content_' + menu.name];
			if (Array.isArray(vue_component)) {
				vue_component = vue_component[0];
			}

			if (typeof vue_component.saveMethod !== 'function') {
				console.error('The component ' + menu.name + ' does not have a saveMethod function');
				return;
			}

			vue_component.saveMethod().then((response) => {
				if (response === true) {
					if (item !== null) {
						this.activeMenuItem = item;
					}
				}
			});
		},
		handleSectionComponent(element) {
			this.activeSectionComponent = this.activeSectionComponent === element.sectionTitle ? null : element.sectionTitle;
		},
		findMenu(menu) {
			for (let index in this.Menus) {
				if (this.Menus[index].name === menu) {
					this.activeMenu = index;
					break;
				}
			}
		},
		findSection(section) {
			for (let index in this.Sections) {
				if (this.Sections[index].name === section) {
					this.activeSection = index;
					break;
				}
			}
		},
	},
	watch: {
		activeMenuItem: function (val, oldVal) {
			if (oldVal !== null) {
				sessionStorage.setItem('tchooz_settings_selected_section/' + document.location.hostname, null);
			}
		},
		activeMenu: function (val) {
			sessionStorage.setItem('tchooz_selected_menu/' + 'settings_menus' + '/' + document.location.hostname, val);
			this.keyMenu++;
		},
		activeSection: function (val) {
			sessionStorage.setItem('tchooz_settings_selected_section/' + document.location.hostname, val);
			this.clicker++;
		},
		Sections: function () {
			if (this.urlRedirectSection) {
				this.findSection(sessionStorage.getItem('goToSection'));
				this.urlRedirectSection = false;
			}
		},
	},
};
</script>
