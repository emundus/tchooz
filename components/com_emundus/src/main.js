import {createApp, defineAsyncComponent} from 'vue/dist/vue.esm-bundler.js';

/** STORE **/
import {createPinia} from 'pinia';
import {useGlobalStore} from "@/stores/global.js";

/** MIXINS **/
import translate from './mixins/translate.js';

/** SERVICES **/
import fileService from "@/services/file.js";
import settingsService from "@/services/settings.js";

/** LIBS **/
import 'vue2-dropzone-vue3';
import moment from "moment/moment.js";

/** DIRECTIVES **/
import clickOutside from './directives/clickOutside';

/** STYLE **/
import './assets/css/main.scss';
import Attachments from "@/views/Attachments.vue";
import Comments from "@/views/Comments.vue";
import WorkflowEdit from "@/views/Workflows/WorkflowEdit.vue";
import ProgramEdit from "@/views/Program/ProgramEdit.vue";

if (document) {
    let app = null;
    let elementId = '#em-component-vue';
    let datas = {};

    let el = document.getElementById('em-component-vue');
    const attachmentElement = document.getElementById('em-application-attachment');
    const filesElement = document.getElementById('em-files');

    if (attachmentElement) {
        elementId = '#em-application-attachment';
        el = attachmentElement;
    } else if (filesElement) {
        elementId = '#em-files';
        el = filesElement;
    }


    if (el) {
        const componentName = el.getAttribute('component');

        if (componentName) {
            const componentNames = ['Attachments', 'Comments', 'Workflows/WorkflowEdit', 'Program/ProgramEdit'];

            if (filesElement || componentNames.includes(componentName)) {
                Array.prototype.slice.call(el.attributes).forEach(function (attr) {
                    datas[attr.name] = attr.value;
                });

                if (datas.attachments) {
                    datas.attachments = JSON.parse(atob(datas.attachments));
                }

                if (datas.columns) {
                    datas.columns = JSON.parse(atob(datas.columns));
                }
            }

            switch (componentName) {
                case 'Attachments':
                    app = createApp(Attachments, {
                        fnum: datas.fnum,
                        user: datas.user,
                        defaultAttachments: datas.attachments ? datas.attachments : null,
                        columns: datas.columns,
                        is_applicant: datas.is_applicant
                    });
                    break;
                case 'Comments':
                    app = createApp(Comments, {
                        defaultCcid: datas.ccid,
                        fnum: datas.fnum ? datas.fnum : '',
                        user: datas.user,
                        'is-applicant': datas.is_applicant == 1,
                        'current-form': datas.current_form,
                        access: datas.access ? JSON.parse(datas.access) : {
                            'c': false,
                            'r': true,
                            'u': false,
                            'd': false
                        },
                        applicantsAllowedToComment: datas.applicants_allowed_to_comment == 1,
                        border: datas.border ? datas.border == 1 : true
                    });
                    break;
                case 'Workflows/WorkflowEdit':
                    app = createApp(WorkflowEdit, {
                        workflowId: Number(datas.workflowid),
                    });
                    break;
                case 'Program/ProgramEdit':
                    app = createApp(ProgramEdit, {
                        programId: Number(datas.program_id),
                    });
                    break;
                default:
                    app = createApp({
                        components: {
                            'lazy-component': defineAsyncComponent(() => {
                                let componentPath = componentName.split('/');
                                if(componentPath.length > 1) {
                                    let directory = componentPath[0];
                                    let name = componentPath[1];

                                    return import(`./views/${directory}/${name}.vue`)
                                } else {
                                    return import(`./views/${componentName}.vue`)
                                }
                            })
                        },
                        data: {
                            componentProps: {
                                ...datas
                            }
                        },
                        template: '<div class="com_emundus_vue"><transition name="slide-right"><lazy-component v-bind="componentProps" /></transition></div>',
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

            if (componentName !== 'Attachments' && !filesElement && componentName !== 'Comments') {
                datas = el.attributes;
            }
            //

            // Manage stores
            const globalStore = useGlobalStore();
            if (componentName === 'Attachments') {
                fileService.isDataAnonymized().then(response => {
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

            settingsService.getOffset().then(response => {
                if (response.status !== false) {
                    globalStore.initOffset(response.data.data);
                }
            });

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
            app.mount(elementId);
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
