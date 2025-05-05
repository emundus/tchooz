import { createApp, defineAsyncComponent } from 'vue/dist/vue.esm-bundler.js';

/** STORE **/
import { createPinia } from 'pinia';
import { useGlobalStore } from '@/stores/global.js';

/** MIXINS **/
import translate from './mixins/translate.js';

/** SERVICES **/
import fileService from '@/services/file.js';
import settingsService from '@/services/settings.js';

/** LIBS **/
import 'vue2-dropzone-vue3';
import moment from 'moment/moment.js';
import VueFusionCharts from 'vue-fusioncharts';
import FusionCharts from 'fusioncharts';
import Charts from 'fusioncharts/fusioncharts.charts';
import FusionTheme from 'fusioncharts/themes/fusioncharts.theme.fusion';

/** DIRECTIVES **/
import clickOutside from './directives/clickOutside';

/** STYLE **/
import './assets/css/main.scss';

/** COMPONENTS **/
import Attachments from '@/views/Attachments.vue';
import Comments from '@/views/Comments.vue';
import WorkflowEdit from '@/views/Workflows/WorkflowEdit.vue';
import ProgramEdit from '@/views/Program/ProgramEdit.vue';
import History from '@/views/History.vue';
import Expert from '@/views/Expert/Expert.vue';
import Filters from '@/views/Filters.vue';
import Dashboard from '@/views/Dashboard/Dashboard.vue';
import SMSEdit from '@/views/SMS/SMSEdit.vue';
import SMSAppFile from '@/views/SMS/SMSAppFile.vue';
import SMSSend from '@/views/SMS/SMSSend.vue';
import Rankings from '@/views/Ranking/rankings.vue';

if (document) {
	let app = null;
	let datas = {};

	let elements = [];

	let el = document.getElementById('em-component-vue');
	if (el) {
		elements.push(el);
	}
	const attachmentElement = document.getElementById('em-application-attachment');
	if (attachmentElement) {
		elements.push(attachmentElement);
	}
	const filesElement = document.getElementById('em-files');
	if (filesElement) {
		elements.push(filesElement);
	}
	const expertElement = document.getElementById('em-expert');
	if (expertElement) {
		elements.push(expertElement);
	}
	const smsElement = document.getElementById('em-sms-send');
	if (smsElement) {
		elements.push(smsElement);
	}
	const dashboardElement = document.getElementById('em-dashboard');
	if (dashboardElement) {
		elements.push(dashboardElement);
	}
	const messengerElement = document.getElementById('em-messenger');
	if (messengerElement) {
		elements.push(messengerElement);
	}
	const filterElement = document.getElementById('em_filters');
	if (filterElement) {
		elements.push(filterElement);
	}

	for (const el of elements) {
		if (el) {
			const componentName = el.getAttribute('component');

			if (elements.length > 1 && el.getAttribute('data-v-app') !== null) {
				continue;
			}

			if (componentName) {
				const componentNames = [
					'Attachments',
					'Comments',
					'Workflows/WorkflowEdit',
					'Program/ProgramEdit',
					'History',
					'Expert/Expert',
					'SMS/SMSEdit',
					'SMS/SMSAppFile',
					'SMS/SMSSend',
					'Filters',
					'Ranking/rankings',
				];

				if (filesElement || componentNames.includes(componentName)) {
					Array.prototype.slice.call(el.attributes).forEach(function (attr) {
						datas[attr.name] = attr.value;
					});

					if (datas.attachments) {
						datas.attachments = JSON.parse(atob(datas.attachments));
					}

					if (datas.columns && componentName !== 'History') {
						datas.columns = JSON.parse(atob(datas.columns));
					}
				} else {
					if (el.getAttribute('data')) {
						datas = JSON.parse(el.getAttribute('data'));
					}
				}

				switch (componentName) {
					case 'Attachments':
						app = createApp(Attachments, {
							fnum: datas.fnum,
							user: datas.user,
							defaultAttachments: datas.attachments ? datas.attachments : null,
							columns: datas.columns,
							is_applicant: datas.is_applicant,
						});
						break;
					case 'Comments':
						app = createApp(Comments, {
							defaultCcid: datas.ccid,
							fnum: datas.fnum ? datas.fnum : '',
							user: datas.user,
							'is-applicant': datas.is_applicant == 1,
							'current-form': datas.current_form,
							access: datas.access
								? JSON.parse(datas.access)
								: {
										c: false,
										r: true,
										u: false,
										d: false,
									},
							applicantsAllowedToComment: datas.applicants_allowed_to_comment == 1,
							border: datas.border ? datas.border == 1 : true,
						});
						break;
					case 'Workflows/WorkflowEdit':
						app = createApp(WorkflowEdit, {
							workflowId: Number(datas.workflowid),
						});
						break;
					case 'SMS/SMSEdit':
						app = createApp(SMSEdit, {
							id: Number(datas.smsid),
						});
						break;
					case 'SMS/SMSSend':
						datas.fnums = JSON.parse(atob(datas.fnums));

						app = createApp(SMSSend, {
							fnums: datas.fnums,
						});
						break;
					case 'SMS/SMSAppFile':
						app = createApp(SMSAppFile, {
							fnum: datas.fnum,
						});
						break;
					case 'Program/ProgramEdit':
						app = createApp(ProgramEdit, {
							programId: Number(datas.program_id),
						});
						break;
					case 'History':
						app = createApp(History, {
							extension: datas.extension,
							itemid: Number(datas.itemid),
							columns: datas.columns ? datas.columns.split(',') : null,
							moreData: datas.moredata ? datas.moredata.split(',') : null,
						});
						break;
					case 'Expert/Expert':
						app = createApp(Expert, {});
						break;
					case 'Dashboard/Dashboard':
						if (el.getAttribute('data')) {
							datas = JSON.parse(el.getAttribute('data'));
						}

						app = createApp(Dashboard, {
							...datas,
						});

						app.use(VueFusionCharts, FusionCharts, Charts, FusionTheme);
						break;
					case 'Filters':
						const appliedFilters = JSON.parse(atob(el.getAttribute('data-applied-filters')));
						const filters = JSON.parse(atob(el.getAttribute('data-filters')));

						app = createApp(Filters, {
							menuId: parseInt(el.getAttribute('data-menu-id')),
							defaultAppliedFilters: appliedFilters,
							defaultFilters: filters,
							defaultQuickSearchFilters: JSON.parse(atob(el.getAttribute('data-quick-search-filters'))),
							countFilterValues: el.getAttribute('data-count-filter-values') === '1',
							allowAddFilter: el.getAttribute('data-allow-add-filter') === '1',
						});
						break;
					case 'Ranking/rankings':
						app = createApp(Rankings, {
							hierarchy_id: datas.hierarchy_id,
							user: datas.user,
							fileTabsStr: datas.filetabsstr,
							specificTabs: datas.specifictabs,
							readonly: datas.readonly == 1,
						});
						break;
					default:
						if (el.getAttribute('data')) {
							datas = JSON.parse(el.getAttribute('data'));
						}

						app = createApp({
							components: {
								'lazy-component': defineAsyncComponent(() => {
									let componentPath = componentName.split('/');
									if (componentPath.length > 1) {
										let directory = componentPath[0];
										let name = componentPath[1];

										return import(`./views/${directory}/${name}.vue`);
									} else {
										return import(`./views/${componentName}.vue`);
									}
								}),
							},
							data: {
								componentProps: {
									...datas,
								},
							},
							template:
								'<div class="com_emundus_vue"><transition name="slide-right"><lazy-component v-bind="componentProps" /></transition></div>',
						});
				}

				// Setup Store, Mixins, Directives
				app.directive('click-outside', clickOutside);
				app.use(createPinia());
				app.mixin(translate);

				const coordinatorAccess = el.getAttribute('coordinatorAccess') || 0;
				const sysadminAccess = el.getAttribute('sysadminAccess') || 0;
				const currentLanguage = el.getAttribute('currentLanguage') || 'fr-FR';
				const shortLang = el.getAttribute('shortLang') || 'fr';
				const manyLanguages = el.getAttribute('manyLanguages') || 0;
				const defaultLang = el.getAttribute('defaultLang') || currentLanguage;
				const timezone = el.getAttribute('timezone') || 'Europe/Paris';
				const offset = el.getAttribute('offset') || 1;

				if (componentName !== 'Attachments' && !filesElement && componentName !== 'Comments') {
					datas = el.attributes;
				}
				//

				// Manage stores
				const globalStore = useGlobalStore();
				if (componentName === 'Attachments') {
					fileService.isDataAnonymized().then((response) => {
						if (response.status !== false) {
							globalStore.setAnonyme(response.anonyme);
						}
					});
				}

				if (typeof datas !== 'undefined') {
					globalStore.initDatas(datas);
				}
				if (typeof currentLanguage !== 'undefined') {
					globalStore.initCurrentLanguage(currentLanguage);

					moment.locale(globalStore.currentLanguage);
				} else {
					globalStore.initCurrentLanguage('fr');
					moment.locale('fr');
				}
				if (typeof shortLang !== 'undefined') {
					globalStore.initShortLang(shortLang);
				}
				if (typeof manyLanguages !== 'undefined') {
					globalStore.initManyLanguages(manyLanguages);
				}
				if (typeof defaultLang !== 'undefined') {
					globalStore.initDefaultLang(defaultLang);
				}
				if (typeof coordinatorAccess !== 'undefined') {
					globalStore.initCoordinatorAccess(coordinatorAccess);
				}
				if (typeof coordinatorAccess !== 'undefined') {
					globalStore.initSysadminAccess(sysadminAccess);
				}

				if (typeof offset !== 'undefined') {
					globalStore.initOffset(offset);
				} else {
					settingsService.getOffset().then((response) => {
						if (response.status !== false) {
							globalStore.initOffset(response.data.data);
						}
					});
				}

				if (typeof timezone !== 'undefined') {
					globalStore.initTimezone(timezone);
				}

				if (datas.base) {
					const globalStore = useGlobalStore();

					globalStore.initAttachmentPath(datas.base + '/images/emundus/files/');
				}
				//

				// Dev mode settings
				const devmode = import.meta.env.MODE === 'development';
				if (devmode) {
					app.config.productionTip = false;
					app.config.devtools = true;
					app.config.performance = true;
				}
				//

				// Finally, mount the app
				app.mount('#' + el.id);
				//

				// Vue DevTools setup after mounting the app
				if (devmode) {
					const version = app.version;
					const devtools = window.__VUE_DEVTOOLS_GLOBAL_HOOK__;
					if (devtools) {
						devtools.enabled = true;
						devtools.emit('app:init', app, version, {});
					}
				}
				//
			}
		}
	}
}
