<template>
	<aside
		id="logo-sidebar"
		class="corner-bottom-left-background tw-sticky tw-left-0 tw-top-0 tw-h-screen tw-bg-white tw-border-r tw-border-gray-200 tw-transition-all"
		:class="minimized === true ? 'tw-w-[64px]' : 'tw-w-64'"
		aria-label="Sidebar"
	>
		<div
			class="tw-h-full tw-pb-4 tw-overflow-y-auto tw-bg-white"
			@mouseover="showMinimized = true"
			@mouseleave="showMinimized = false"
		>
			<ul class="tw-flex tw-flex-col tw-items-left tw-gap-3 tw-p-3 tw-space-y-2 tw-font-large tw-list-none">
				<li class="tw-w-10 tw-flex tw-items-center tw-justify-between">
					<span
						class="tw-flex tw-items-center tw-group tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300"
						@click="clickReturn()"
					>
						<!-- The back button icon -->
						<span class="material-symbols-outlined tw-text-neutral-600 tw-user-select-none">navigate_before</span>
						<!-- The back button label -->
						<span class="tw-pl-1 tw-text-neutral-900" v-if="minimized === false">{{ translate('BACK') }}</span>
					</span>
					<span
						class="material-symbols-outlined tw-absolute tw-right-[-12px] !tw-text-xl/5 tw-bg-neutral-400 tw-rounded-full tw-cursor-pointer"
						:class="minimized ? 'tw-rotate-180' : ''"
						v-show="showMinimized === true"
						@click="handleSidebarSize"
						>chevron_left</span
					>
				</li>

				<template v-for="(menu, indexMenu) in menus" :key="$props.id + '_' + menu.name">
					<li v-if="menu.published" class="!tw-mt-0 tw-w-full">
						<div
							:id="'Menu-' + indexMenu"
							@click="activeMenu = indexMenu"
							class="tw-flex tw-items-start tw-w-full tw-p-2 tw-cursor-pointer tw-rounded-lg tw-group tw-user-select-none"
							:class="
								activeMenu === indexMenu
									? 'tw-font-bold tw-text-profile-full tw-bg-profile-light'
									: 'hover:tw-bg-gray-200'
							"
						>
							<span
								class="material-symbols-outlined tw-font-bold tw-mr-2.5"
								:class="activeMenu === indexMenu ? 'tw-text-profile-full' : ''"
								name="icon-Menu"
								:title="translate(menu.label)"
								:id="'icon-' + indexMenu"
								>{{ menu.icon }}</span
							>
							<p
								class="tw-font-bold tw-leading-6"
								v-if="minimized === false"
								:class="activeMenu === indexMenu ? 'tw-text-profile-full' : ''"
							>
								{{ translate(menu.label) }}
							</p>
						</div>
					</li>
				</template>
			</ul>
		</div>

		<div
			class="tchoozy-corner-bottom-left-bakground-mask-image tw-h-1/3 tw-w-full tw-absolute tw-bottom-0 tw-bg-profile-full"
		></div>
	</aside>
</template>

<script>
export default {
	name: 'SidebarMenu',
	components: {},
	props: {
		menusList: {
			type: Array,
			required: true,
		},
	},

	mixins: [],

	data() {
		return {
			menus: [],

			activeMenu: null,
			minimized: false,
			showMinimized: false,
		};
	},
	created() {
		this.menus = this.$props.menusList;
		this.activeMenu = 0;

		const sessionMenu = sessionStorage.getItem(
			'tchooz_selected_menu/' + this.$props.id + '/' + document.location.hostname,
		);
		const sessionSideBarMinimized = sessionStorage.getItem('tchooz_sidebar_minimized/' + document.location.hostname);
		if (sessionSideBarMinimized) {
			this.minimized = sessionSideBarMinimized === 'true';
		}
		if (sessionMenu) {
			this.activeMenu = parseInt(sessionMenu);
		}

		if (window.location.hash) {
			let hash = window.location.hash.substring(1);
			for (let index in this.menus) {
				if (this.menus[index].name === hash) {
					this.activeMenu = parseInt(index);
					break;
				}
			}
		}

		this.$emit('listMenus', this.menus, 'menus');
	},
	mounted() {},
	methods: {
		clickReturn() {
			if (window.history.length > 1) {
				window.history.back();
			} else {
				window.location.href = '/';
			}
		},
		handleSidebarSize() {
			this.minimized = !this.minimized;
		},
	},
	watch: {
		activeMenu: function (val) {
			sessionStorage.setItem('tchooz_selected_menu/' + this.$props.id + '/' + document.location.hostname, val);
			this.$emit('menuSelected', this.menus[val]);
		},
		minimized: function (val, oldVal) {
			if (oldVal !== null) {
				sessionStorage.setItem('tchooz_sidebar_minimized/' + document.location.hostname, val);
			}
		},
	},
};
</script>

<style scoped></style>
