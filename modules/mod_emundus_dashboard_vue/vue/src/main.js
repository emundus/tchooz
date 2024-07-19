import { createApp } from 'vue';
import VueFusionCharts from 'vue-fusioncharts';
import FusionCharts from 'fusioncharts';
import Dashboard from './Dashboard.vue';
import Charts from 'fusioncharts/fusioncharts.charts';
import FusionTheme from 'fusioncharts/themes/fusioncharts.theme.fusion';

import translate from './mixins/translate';

const dashboardElement = document.getElementById('em-dashboard-vue');

let app = null;

if (dashboardElement) {
    app = createApp(Dashboard, {
        programmeFilter: parseInt(dashboardElement.attributes.programmeFilter.value),
        displayDescription: parseInt(dashboardElement.attributes.displayDescription.value),
        displayShapes: parseInt(dashboardElement.attributes.displayShapes.value),
        displayTchoozy: parseInt(dashboardElement.attributes.displayTchoozy.value),
        displayName: parseInt(dashboardElement.attributes.displayName.value),
        name: dashboardElement.attributes.name.value,
        language: parseInt(dashboardElement.attributes.language.value),
        profile_name: dashboardElement.attributes.profile_name.value,
        profile_description: dashboardElement.attributes.profile_description.value,
    });
}

if (app !== null) {
    app.use(VueFusionCharts, FusionCharts, Charts, FusionTheme);
    app.mixin(translate);

    const devmode = import.meta.env.MODE === 'development';
    if(devmode) {
        app.config.productionTip = false;
        app.config.devtools = true;
    }

    app.mount('#em-dashboard-vue');

    if(devmode) {
        const version = app.version;
        const devtools = window.__VUE_DEVTOOLS_GLOBAL_HOOK__;
        devtools.enabled = true;
        devtools.emit('app:init', app, version, {});
    }
}
