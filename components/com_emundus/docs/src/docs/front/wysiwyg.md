# TipTap Editor
The TipTap editor is a rich text editor for Vue.js. It is easy to use and customise. It is based on ProseMirror.

We chose to create our own VueJS component based on TipTap for the Emundus WYSIWYG editor. This allows us to customise the editor to meet our specific needs. This component will eventually be shared with the Wiin solution.

## Installation
To install TipTap in a Vue project, you can use npm or yarn :

```bash
npm install git+https://github.com/emundus/tiptap
```

To use a specific version of TipTap, you can specify the version in the installation command :

```bash
npm install git+https://github.com/emundus/tiptap#release/0.0.1
```

## Utilisation
To use TipTap in a View component, you can import the TipTapEditor component and use it in your template:

```vue
<tip-tap-editor
    v-model="content"
/>
```

## Modification du composant
1. To modify the TipTapEditor component, you need to clone the TipTap Git repository:
    ```bash
       git clone https://github.com/emundus/tiptap
    ```
2. You can then create a new branch for your changes:
    ```bash
       git checkout -b feature/my-feature
    ```
3. You can now modify the TipTapEditor component to apply corrections or add new features.

If you want to test your changes, you can install the component locally using npm link :

```bash
cd tiptap
npm link
cd ../tchooz
npm link @emundus/tiptap
```


