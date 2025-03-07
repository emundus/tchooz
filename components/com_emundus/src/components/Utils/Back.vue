<script>
import settingsService from '@/services/settings.js'
import { useGlobalStore } from '@/stores/global.js'

export default {
    name: 'Back',
    props: {
        link: {
            type: String,
            default: 'index.php?option=com_emundus&view=emails'
        }
    },
    methods: {
        redirectJRoute() {
            settingsService.redirectJRoute(this.link, useGlobalStore().getCurrentLang)
        }
    }
}
</script>

<template>
    <div class="tw-flex tw-items-center tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300"
        @click="redirectJRoute"
    >
        <span class="material-symbols-outlined tw-text-neutral-600">navigate_before</span>
        <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
    </div>
</template>