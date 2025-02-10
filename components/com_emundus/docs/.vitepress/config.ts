import {defineConfigWithTheme} from 'vitepress'
import type {ThemeConfig} from 'vitepress-carbon'
import baseConfig from 'vitepress-carbon/config'
import {withMermaid} from "vitepress-plugin-mermaid";

// https://vitepress.dev/reference/site-config
export default withMermaid(
    defineConfigWithTheme<ThemeConfig>({
        extends: baseConfig,
        title: "Tchooz - Documentation",
        description: "A website to learn how to develop on Tchooz project",
        srcDir: 'src',
        base: '/',
        lang: 'en-GB',
        head: [
            ['link',
                {rel: 'icon', href: './favicon.ico'}
            ]
        ],

        themeConfig: {
            // https://vitepress.dev/reference/default-theme-config
            logo: '/logo_tchooz.svg',
            nav: [
                {
                    text: 'Docs',
                    items: [
                        {text: 'Getting started', link: '/docs/getting-started'},
                        {text: 'Glossary', link: '/docs/glossary'},
                    ]
                },
                {
                    text: 'Ecosystem',
                    items: [
                        {
                            text: 'Resources',
                            items: [
                                {text: 'Joomla!', link: 'https://manual.joomla.org/', target: '_blank'},
                                {text: 'Storybook', link: 'https://emundus.github.io/storybook', target: '_blank'},
                                {text: 'eMundus', link: 'https://emundus.fr', target: '_blank'}
                            ]
                        },
                        {
                            text: 'Dependencies',
                            items: [
                                {text: 'Fabrikar', link: 'https://fabrikar.com/', target: '_blank'},
                                {text: 'Gantry', link: 'https://gantry.org/', target: '_blank'},
                                {text: 'Hikashop', link: 'https://www.hikashop.com/', target: '_blank'},
                                {text: 'Dependencies status', link: '/ecosystem/dependencies-status'},
                            ]
                        },
                    ]
                }
            ],

            search: {
                provider: 'local'
            },

            sidebar: {
                '/docs/': [
                    {
                        text: 'Getting started',
                        link: 'docs/getting-started'
                    },
                    {
                        text: 'Frontend',
                        items: [
                            {
                                text: 'Tailwind',
                                link: 'docs/front/tailwind'
                            },
                            {
                                text: 'Wysiwyg',
                                link: 'docs/front/wysiwyg'
                            }
                        ]
                    },
                    {
                        text: 'Backend',
                        items: [
                          {
                            text: 'Event handling',
                            link: 'docs/backend/event-handling'
                          },
                          {
                            text: 'Features',
                            items: [
                              {
                                text: 'Booking',
                                link: 'docs/backend/features/booking'
                              },
                              {
                                text: 'Workflow Builder',
                                link: 'docs/backend/features/workflow-builder'
                              },
                              {
                                text: 'Messenger',
                                link: 'docs/backend/features/messenger'
                              }
                            ]
                          }
                        ]
                    },

                ],
                '/ecosystem/': [
                    {
                        text: 'Dependencies status',
                        link: 'ecosystem/dependencies-status'
                    }
                ]
            },

            socialLinks: [
                {icon: 'github', link: 'https://github.com/emundus/tchooz'}
            ],
        },
        mermaid: {
            //mermaidConfig !theme here works for ligth mode since dark theme is forced in dark mode
        },
    })
);
