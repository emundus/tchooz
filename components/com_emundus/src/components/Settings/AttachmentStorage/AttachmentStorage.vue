<template>
	<span :id="'attachmentStorage'">
		<modal
			:name="'attachmentStorage'"
			height="auto"
			transition="fade"
			:delay="100"
			:adaptive="true"
			:clickToClose="false"
			@closed="beforeClose"
		>
			<div class="em-modal-header">
				<div
					class="tw-flex tw-cursor-pointer tw-items-center tw-justify-between"
					@click.prevent="$modal.hide('attachmentStorage')"
				>
					<div class="tw-flex tw-w-max tw-items-center">
						<span class="material-symbols-outlined">arrow_back</span>
						<span class="tw-ml-2">{{ translate('COM_EMUNDUS_ONBOARD_ADD_RETOUR') }}</span>
					</div>
					<div v-if="saving" class="tw-flex tw-items-center tw-justify-start">
						<div class="em-loader tw-mr-2"></div>
						<p class="tw-flex tw-items-center tw-text-sm">
							{{ translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_PROGRESS') }}
						</p>
					</div>
					<p class="tw-text-sm" v-if="!saving && last_save != null">
						{{ translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_LAST') + last_save }}
					</p>
				</div>
			</div>

			<div class="em-modal-content">
				<div class="em-modal-menu__sidebar">
					<div
						v-for="menu in menus"
						:key="'menu_' + menu.index"
						@click="currentMenu = menu.index"
						class="translation-menu-item tw-flex tw-cursor-pointer tw-items-center tw-justify-between tw-p-4"
						:class="currentMenu === menu.index ? 'em-modal-menu__current' : ''"
					>
						<p class="tw-text-base">{{ translate(menu.title) }}</p>
					</div>
				</div>

				<transition name="fade" mode="out-in">
					<Configuration
						v-if="currentMenu === 1"
						class="em-modal-component"
						@updateSaving="updateSaving"
						@updateLastSaving="updateLastSaving"
					></Configuration>
					<Storage
						v-else-if="currentMenu === 2"
						class="em-modal-component"
						@updateSaving="updateSaving"
						@updateLastSaving="updateLastSaving"
					></Storage>
				</transition>
			</div>

			<div v-if="loading" class="em-page-loader"></div>
		</modal>
	</span>
</template>

<script>
import Configuration from './Configuration.vue';
import Storage from './Storage.vue';

export default {
	name: 'attachmentStorage',
	props: {},
	components: { Storage, Configuration },
	data() {
		return {
			currentMenu: 1,
			menus: [
				{
					title: 'COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_CONFIGURATION',
					index: 1,
				},
				{
					title: 'COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_STORAGE',
					index: 2,
				},
			],

			loading: false,
			saving: false,
			last_save: null,
		};
	},
	methods: {
		beforeClose() {
			this.$emit('resetMenuIndex');
		},

		updateSaving(saving) {
			this.saving = saving;
		},

		updateLastSaving(last_save) {
			this.last_save = last_save;
		},
	},
};
</script>
