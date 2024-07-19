import { createApp } from 'vue'
import './style.css'
import App from './App.vue'

let editors = document.querySelectorAll('.tiptap-editor');
editors.forEach((editor) => {
    let app = null;
    app = createApp(App, {
        textareaId: editor.attributes.textareaId.value,
        enableSuggestions: editor.attributes.enableSuggestions.value,
        suggestions: JSON.parse(editor.attributes.suggestions.value),
        plugins: JSON.parse(editor.attributes.plugins.value),
    });

    const devmode = import.meta.env.MODE === 'development';
    if(devmode) {
        app.config.productionTip = false;
        app.config.devtools = true;
    }

    app.mount('#'+editor.id);

    if(devmode) {
        const version = app.version;
        const devtools = window.__VUE_DEVTOOLS_GLOBAL_HOOK__;
        devtools.enabled = true;
        devtools.emit('app:init', app, version, {});
    }
});
