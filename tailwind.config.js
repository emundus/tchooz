/** @type {import('tailwindcss').Config} */
const plugin = require('tailwindcss/plugin');

module.exports = {
    prefix: 'tw-',
    content: [
        "./templates/g5_helium/html/**/*.{html,js,php}",
        "./modules/**/src/**/*.{html,js,php,vue}",
        "./modules/**/tmpl/*.{html,js,php}",
        "./plugins/fabrik_element/**/*.{html,js,php}",
        "./plugins/fabrik_list/js/scripts/*.{html,js}",
        "./components/com_emundus/helpers/**/*.{html,js,php,vue}",
        "./components/com_emundus/classes/**/*.php",
        "./components/com_emundus/controllers/**/*.{html,js,php,vue}",
        "./components/com_emundus/models/**/*.{html,js,php,vue}",
        "./components/com_emundus/src/**/*.{html,js,php,vue}",
        "./components/com_emundus/views/**/*.{html,js,php,vue}",
        "./components/com_fabrik/layouts/**/*.{html,js,php}",
        "./components/com_fabrik/views/**/*.{html,js,php}",
        "./media/com_emundus/js/em_files.js",
        "./media/com_emundus/js/em_user.js",
        "./media/com_emundus/js/collaborate.js",
        "./media/com_emundus/js/mixins/exports.js",
        "./media/com_emundus/js/mixins/utilities.js",
    ],
    safelist: [
        {
            pattern: /label-/
        },
        {
            pattern: /tw-m(l|r|t|b|x|y)-/
        },
        {
            pattern: /tw-w-/
        },
        {
            pattern: /tw-p(y|x|l|r|t|b)-/
        },
        {
            pattern: /tw-flex-/
        },
        {
            pattern: /tw-grid-cols-/,
            variants: ['sm', 'md', 'lg'],
        },
        {
            pattern: /tw-grid-rows-/,
            variants: ['sm', 'md', 'lg'],
        },
        {
            pattern: /tw-border-/
        },
        {
            pattern: /tw-bg-/
        },
        {
            pattern: /tw-text-orange-500/
        },
        {
            pattern: /tw-text-main-500/
        },
    ],
    theme: {
        extend: {
            scale: {
                '200': '2',
                '300': '3',
                '400': '4',
                '500': '5',
                '600': '6',
                '700': '7',
                '800': '8',
                '900': '9',
                '1000': '10',
            },
            colors: {
                profile: {
                    full: 'var(--em-profile-color)',
                    light: 'hsl(from var(--em-profile-color) h s l / 15%)',
                    medium: 'hsl(from var(--em-profile-color) h s l / 30%)',
                    dark: 'color-mix(in srgb,var(--em-profile-color),#000 15%)',
                },
                link: {
                    regular: 'var(--link-regular)',
                } ,

                coordinator: {
                    bg: 'var(--em-coordinator-bg)',
                },

                form: {
                    'border-hover': 'var(--em-form-bc-hover)',
                },

                red: {
                    50: 'var(--red-50)',
                    100: 'var(--red-100)',
                    200: 'var(--red-200)',
                    300: 'var(--red-300)',
                    400: 'var(--red-400)',
                    500: 'var(--red-500)',
                    600: 'var(--red-600)',
                    700: 'var(--red-700)',
                    800: 'var(--red-800)',
                    900: 'var(--red-900)',
                },
                blue: {
                    50: 'var(--blue-50)',
                    100: 'var(--blue-100)',
                    200: 'var(--blue-200)',
                    300: 'var(--blue-300)',
                    400: 'var(--blue-400)',
                    500: 'var(--blue-500)',
                    600: 'var(--blue-600)',
                    700: 'var(--blue-700)',
                    800: 'var(--blue-800)',
                    900: 'var(--blue-900)',
                },
                orange: {
                    50: 'var(--orange-50)',
                    100: 'var(--orange-100)',
                    200: 'var(--orange-200)',
                    300: 'var(--orange-300)',
                    400: 'var(--orange-400)',
                    500: 'var(--orange-500)',
                    600: 'var(--orange-600)',
                    700: 'var(--orange-700)',
                    800: 'var(--orange-800)',
                    900: 'var(--orange-900)',
                },
                neutral: {
                    0: 'var(--neutral-0)',
                    50: 'var(--neutral-50)',
                    100: 'var(--neutral-100)',
                    200: 'var(--neutral-200)',
                    300: 'var(--neutral-300)',
                    400: 'var(--neutral-400)',
                    500: 'var(--neutral-500)',
                    600: 'var(--neutral-600)',
                    700: 'var(--neutral-700)',
                    800: 'var(--neutral-800)',
                    900: 'var(--neutral-900)',
                },
                main: {
                    50: 'var(--main-50)',
                    100: 'var(--main-100)',
                    200: 'var(--main-200)',
                    300: 'var(--main-300)',
                    400: 'var(--main-400)',
                    500: 'var(--main-500)',
                    600: 'var(--main-600)',
                    700: 'var(--main-700)',
                    800: 'var(--main-800)',
                    900: 'var(--main-900)',
                },
                green: {
                    500: 'var(--green-500)',
                    700: 'var(--green-700)'
                }
            },
            spacing: {
                1: 'var(--em-spacing-1)',
                2: 'var(--em-spacing-2)',
                3: 'var(--em-spacing-3)',
                4: 'var(--em-spacing-4)',
                5: 'var(--em-spacing-5)',
                6: 'var(--em-spacing-6)',
                7: 'var(--em-spacing-7)',
                8: 'var(--em-spacing-8)',
                9: 'var(--em-spacing-9)',
                10: 'var(--em-spacing-10)',
                11: 'var(--em-spacing-11)',
                12: 'var(--em-spacing-12)',
                'py-4': 'calc(var(--em-spacing-4) * 3.7)',
            },
            borderRadius: {
                'coordinator': 'var(--em-coordinator-br)',
                'coordinator-cards': 'var(--em-coordinator-br-cards)',
                'applicant': 'var(--em-applicant-br)',
                'form': 'var(--em-form-br)',
                'status': 'var(--em-status-br)',
                'form-block': 'var(--em-form-br-block)',
            },
            boxShadow: {
                'standard': 'var(--em-box-shadow-x-1) var(--em-box-shadow-y-1) var(--em-box-shadow-blur-1) var(--em-box-shadow-color-1), var(--em-box-shadow-x-2) var(--em-box-shadow-y-2) var(--em-box-shadow-blur-2) var(--em-box-shadow-color-2), var(--em-box-shadow-x-3) var(--em-box-shadow-y-3) var(--em-box-shadow-blur-3) var(--em-box-shadow-color-3)',
                'modal': '0 0 0 50vmax rgba(0,0,0,.5)',
                'table-border-profile': '0 0px 0px 1px var(--em-profile-color)',
                'table-border-neutral': '0 0px 0px 1px var(--neutral-400)',
                'card': 'var(--em-box-shadow-x-1) var(--em-box-shadow-y-1) var(--em-box-shadow-blur-1) var(--em-box-shadow-color-1), var(--em-box-shadow-x-2) var(--em-box-shadow-y-2) var(--em-box-shadow-blur-2) var(--em-box-shadow-color-2), var(--em-box-shadow-x-3) var(--em-box-shadow-y-3) var(--em-box-shadow-blur-3) var(--em-box-shadow-color-3)'
            },
            fontSize: {
                'xxs': '8px'
            },
            height: {
                'form': 'var(--em-form-height)',
            },
            width: {
                'form': 'var(--em-form-height)',
            }
        },
    },
    plugins: [
        plugin(function ({addComponents, theme}) {
            addComponents({
                '.em-default-title-1': {
                    color: 'var(--em-default-title-color-1)',
                    fontFamily: 'var(--em-profile-font-title)',
                    fontSize: 'var(--em-coordinator-h1)',
                    fontStyle: 'normal',
                    lineHeight: '28.8px',
                    fontWeight: 500,
                },
                '.em-default-title-2': {
                    color: 'var(--em-default-title-color-1)',
                    fontFamily: 'var(--em-profile-font-title)',
                    fontSize: 'var(--em-coordinator-h2)',
                    fontStyle: 'normal',
                    lineHeight: '26.4px',
                    fontWeight: 500,
                },
                '.em-default-title-3': {
                    color: 'var(--em-default-title-color-1)',
                    fontFamily: 'var(--em-profile-font-title)',
                    fontSize: 'var(--em-coordinator-h3)',
                    fontStyle: 'normal',
                    lineHeight: '24.2px',
                    fontWeight: 500,
                },

                '.btn-primary': {
                    backgroundColor: 'var(--em-profile-color)',
                    color: 'var(--neutral-0) !important',
                    border: '1px solid var(--em-profile-color)',
                    textShadow: 'none',
                    textTransform: 'math-auto',
                    padding: 'var(--em-spacing-vertical) var(--em-spacing-horizontal)',
                    fontSize: '16px',
                    fontFamily: 'var(--em-profile-font)',
                    lineHeight: '1.25',
                    borderRadius: 'var(--em-applicant-br)',
                    transition: 'all 0.2s ease-out',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    cursor: 'pointer',

                    '&:hover': {
                        backgroundColor: 'var(--neutral-0)',
                        color: 'var(--em-profile-color) !important',
                        border: '1px solid var(--em-profile-color)',
                        textDecoration: 'none',
                    },

                    '&:disabled': {
                        opacity: '0.6',
                        cursor: 'not-allowed',
                    }
                },

                '.btn-secondary': {
                    backgroundColor: 'var(--neutral-0)',
                    color: 'var(--em-secondary-color) !important',
                    border: '1px solid var(--em-secondary-color)',
                    textShadow: 'none',
                    textTransform: 'math-auto',
                    padding: 'var(--em-spacing-vertical) var(--em-spacing-horizontal)',
                    fontSize: '16px',
                    fontFamily: 'var(--em-profile-font)',
                    lineHeight: '1.25',
                    borderRadius: 'var(--em-applicant-br)',
                    transition: 'all 0.2s ease-out',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    cursor: 'pointer',

                    '&:hover': {
                        backgroundColor: 'var(--em-secondary-color)',
                        color: 'var(--neutral-0) !important',
                        border: '1px solid var(--em-secondary-color)',
                        textDecoration: 'none',
                    },
                },


                '.btn-tertiary': {
                    backgroundColor: 'var(--neutral-0)',
                    color: 'var(--em-tertiary-color)',
                    border: '1px solid var(--em-tertiary-color)',
                    textShadow: 'none',
                    textTransform: 'math-auto',
                    padding: 'var(--em-spacing-vertical) var(--em-spacing-horizontal)',
                    fontSize: '16px',
                    fontFamily: 'var(--em-profile-font)',
                    lineHeight: '1.25',
                    borderRadius: 'var(--em-applicant-br)',
                    transition: 'all 0.3s ease-in-out',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',

                    '&:hover': {
                        backgroundColor: 'var(--em-tertiary-color)',
                        color: 'var(--neutral-0)',
                        border: '1px solid var(--em-tertiary-color)',
                        textDecoration: 'none',
                    },
                },

                '.btn-cancel': {
                    backgroundColor: 'var(--neutral-0)',
                    color: 'var(--em-coordinator-secondary-color) !important',
                    border: '1px solid var(--em-coordinator-secondary-color)',
                    textShadow: 'none',
                    textTransform: 'math-auto',
                    padding: 'var(--em-coordinator-vertical) var(--em-coordinator-horizontal)',
                    fontSize: 'var(--em-coordinator-font-size)',
                    fontFamily: 'var(--em-profile-font)',
                    lineHeight: '1.25',
                    borderRadius: 'var(--em-coordinator-br)',
                    transition: 'all 0.3s ease-in-out',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',

                    '&:hover': {
                        backgroundColor: 'var(--em-coordinator-secondary-color)',
                        color: 'var(--neutral-0) !important',
                        border: '1px solid var(--em-coordinator-secondary-color)',
                        textDecoration: 'none',
                    },
                },

                '.btn-disabled': {
                    opacity: '0.6',

                    '&:hover': {
                        cursor: 'not-allowed',
                    },
                },

                '.btn-red': {
                    backgroundColor: 'var(--red-500)',
                    color: 'var(--neutral-0) !important',
                    border: '1px solid var(--red-500)',
                    textShadow: 'none',
                    textTransform: 'math-auto',
                    padding: 'var(--em-spacing-vertical) var(--em-spacing-horizontal)',
                    fontSize: '16px',
                    fontFamily: 'var(--em-profile-font)',
                    lineHeight: '1.25',
                    borderRadius: 'var(--em-applicant-br)',
                    transition: 'all 0.2s ease-out',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    cursor: 'pointer',

                    '&:hover': {
                        backgroundColor: 'var(--neutral-0)',
                        color: 'var(--red-500) !important',
                        border: '1px solid var(--red-500)',
                        textDecoration: 'none',
                    },

                    '&:disabled': {
                        opacity: '0.6',
                        cursor: 'not-allowed',
                    }
                },
                '.target-blank-links': {
                    textDecoration: 'underline',

                    '&::after': {
                        content: '"open_in_new"',
                        display: 'inline-block',
                        fontFamily: '"Material Icons"',
                        marginLeft: '4px',
                        textDecoration: 'none',
                        position: 'relative',
                        top: '4px',
                    },
                },
            });
        })
    ],
};
