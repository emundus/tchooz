/** COMPONENTS **/
import 'vue2-dropzone-vue3';
import {createApp} from 'vue';
import clickOutside from './directives/clickOutside';
import App from './App.vue';

/** STORE **/
import {createPinia} from 'pinia';

/** MIXINS **/
import translate from './mixins/translate.js';


let elementId = '';
let data = {};
let componentName = '';

const attachmentElement = document.getElementById('em-application-attachment');
const filesElement = document.getElementById('em-files');

let app = null;

if (attachmentElement || filesElement) {
  let element = null;

  if (attachmentElement) {
    element = attachmentElement;
    componentName = 'attachments';
    elementId = '#em-application-attachment';
  } else if (filesElement) {
    element = filesElement;
    componentName = 'files';
    elementId = '#em-files';
  }

  if (element !== null) {
    Array.prototype.slice.call(element.attributes).forEach(function (attr) {
      data[attr.name] = attr.value;
    });

    if (data.fnum !== '' && filesElement) {
      componentName = 'application';
    }

    app = createApp(App, {
      component: componentName,
      data: data
    });
  }
} else if (document.getElementById('em-component-vue')) {
  let $el = document.getElementById('em-component-vue')
  app = createApp(App, {
    component: $el.attributes.component.value,
    datas: $el.attributes,
    currentLanguage: $el.attributes.currentLanguage.value,
    shortLang: $el.attributes.shortLang.value,
    manyLanguages: $el.attributes.manyLanguages.value,
    defaultLang: $el.attributes.defaultLang ? $el.attributes.defaultLang.value : $el.attributes.currentLanguage.value,
    coordinatorAccess: $el.attributes.coordinatorAccess.value,
    sysadminAccess: $el.attributes.sysadminAccess.value,
  });

  elementId = '#em-component-vue';
}

if (app !== null) {
  /** DIRECTIVES **/
  app.directive('click-outside', clickOutside);

  app.use(createPinia());
  app.mixin(translate);

  const devmode = import.meta.env.MODE === 'development';
  if(devmode) {
    app.config.productionTip = false;
    app.config.devtools = true;
  }

  app.mount(elementId);

  if(devmode) {
    const version = app.version;
    const devtools = window.__VUE_DEVTOOLS_GLOBAL_HOOK__;
    devtools.enabled = true;
    devtools.emit('app:init', app, version, {});
  }
}