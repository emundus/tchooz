# TipTap Editor
L'éditeur TipTap est un éditeur de texte riche pour Vue.js. Il est facile à utiliser et à personnaliser. Il est basé sur ProseMirror.

Nous avons fait le choix de créer notre propre composant VueJS basé sur TipTap pour l'éditeur WYSIWYG de Emundus. Cela nous permet de personnaliser l'éditeur pour répondre à nos besoins spécifiques. Ce composant sera à terme partagé avec la solution Wiin.

## Installation
Pour installer TipTap au sein d'un projet Vue, vous pouvez utiliser npm ou yarn :

```bash
npm install git+https://github.com/emundus/tiptap
```

Pour utiliser une version spécifique de TipTap, vous pouvez spécifier la version dans la commande d'installation :

```bash
npm install git+https://github.com/emundus/tiptap#release/0.0.1
```

## Utilisation
Pour utiliser TipTap dans un composant Vue, vous pouvez importer le composant TipTapEditor et l'utiliser dans votre template :

```vue
<tip-tap-editor
    v-model="content"
/>
```

## Modification du composant
1. Pour modifier le composant TipTapEditor, vous devez clone le dépôt Git de TipTap : 
    ```bash
       git clone https://github.com/emundus/tiptap
    ```
2. Vous pouvez ensuite créer une nouvelle branche pour vos modifications :
    ```bash
       git checkout -b feature/my-feature
    ```
3. Vous pouvez maintenant modifier le composant TipTapEditor pour appliquer des corrections ou proposer de nouvelles fonctionnalités.

Si vous souhaitez tester vos modifications, vous pouvez installer le composant localement en utilisant npm link :

```bash
cd tiptap
npm link
cd ../tchooz
npm link @emundus/tiptap
```


