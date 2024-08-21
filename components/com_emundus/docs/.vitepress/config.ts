import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Tchooz",
  description: "A website to learn how to develop on Tchooz project",
  head: [
      ['link', { rel: 'icon', href: 'favicon.ico' }]
  ],
  locales: {
    root: {
      label: 'French',
      lang: 'fr',
      themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        siteTitle: 'Tchooz - v2',
        search: {
          provider: 'local',
          options: {
            locales: {
              root: {
                translations: {
                  button: {
                    buttonText: 'Rechercher',
                    buttonAriaLabel: 'Cliquez pour rechercher',
                  },
                  modal: {
                    displayDetails: 'Afficher les détails',
                    resetButtonTitle: 'Réinitialiser la recherche',
                    backButtonTitle: 'Retour à la recherche',
                    noResultsText: 'Aucun résultat trouvé',
                    footer: {
                      selectText: 'Sélectionner',
                      selectKeyAriaLabel: 'espace',
                      navigateText: 'Naviguer',
                      navigateUpKeyAriaLabel: 'haut',
                      navigateDownKeyAriaLabel: 'bas',
                      closeText: 'Fermer',
                      closeKeyAriaLabel: 'esc',
                    }
                  }
                }
              }
            }
          }
        },
        nav: [
          { text: 'Accueil', link: '/' },
          { text: 'Back-end', link: '/back/Home' }
        ],

        sidebar: [
          {
            text: 'Concepts principaux',
            items: [
              {
                text: 'Créer une vue',
                link: 'general/create-view'
              },
              {
                text: 'Créer un module',
                link: 'general/create-module'
              },
              {
                text: 'Créer un plugin',
                link: 'general/create-plugin'
              },
              {
                text: 'Base de données',
                link: 'general/database'
              },
              {
                text: 'Mettre à jour Vanilla',
                link: 'general/update-vanilla'
              }
            ]
          },
          {
            text: 'Front',
            items: [
              {
                text: 'Tailwind',
                link: 'front/tailwind'
              },
              {
                text: 'Créer un composant VueJS',
                link: 'front/create-component'
              },
              {
                text: 'Wysiwyg',
                link: 'front/wysiwyg'
              }
            ]
          },
          {
            text: 'Back-end',
            items: [
              {
                text: 'Références',
                link: '/back/Home'
              }
            ]
          },
        ],

        socialLinks: []
      },
    }
  },
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    search: {
      provider: 'local'
    },
  },
  outDir: '../../../public',
  ignoreDeadLinks: true
})
