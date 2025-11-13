import { createApp } from 'vue';
import Vuex from 'vuex';
import App from './App.vue';
import translate from './mixins/translate';

const modFilters =  document.getElementById('em-filters-vue');
if (modFilters) {
    const appliedFilters = JSON.parse(atob(modFilters.getAttribute('data-applied-filters')));
    const filters = JSON.parse(atob(modFilters.getAttribute('data-filters')));

    const app = createApp(App, {
        moduleId: parseInt(modFilters.getAttribute('data-module-id')),
        defaultAppliedFilters: appliedFilters,
        defaultFilters: filters,
        defaultQuickSearchFilters: JSON.parse(atob(modFilters.getAttribute('data-quick-search-filters'))),
        countFilterValues: modFilters.getAttribute('data-count-filter-values') === '1',
        canShareFilters: modFilters.getAttribute('data-can-share-filters') === '1',
        userId: parseInt(modFilters.getAttribute('data-user-id')),
    }).use(Vuex).mixin(translate);

    app.config.productionTip = false;
    app.config.devtools = false;
    app.mount('#em-filters-vue');
}

/******************************************
/* Comportement de la barre de recherche  *
 ******************************************/

/* À la saisie dans le champ de recherche */
const globalSearchDiv = document.getElementById("global-search-values");
const inputElement = document.querySelector("#current-global-search");

inputElement.addEventListener("input", function () {
    globalSearchDiv.classList.add("global-search-values-wide");
});

/* Au clic en dehors de l'input */
document.addEventListener("click", function(event) {
    if (event.target !== inputElement && !inputElement.contains(event.target) && document.querySelectorAll("#global-search-values .global-search-tag").length === 0) {
        globalSearchDiv.classList.remove("global-search-values-wide");
    }
});