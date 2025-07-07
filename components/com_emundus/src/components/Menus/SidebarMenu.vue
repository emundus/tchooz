<template>
	<aside
		id="logo-sidebar"
		class="corner-bottom-left-background tw-sticky tw-left-0 tw-top-0 tw-h-screen tw-border-r tw-border-gray-200 tw-bg-white tw-transition-all"
		:class="minimized === true ? 'tw-w-[64px]' : 'tw-w-64'"
		aria-label="Sidebar"
	>
		<div
			class="tw-h-full tw-overflow-y-auto tw-bg-white tw-pb-4"
			@mouseover="showMinimized = true"
			@mouseleave="showMinimized = false"
		>
			<ul class="tw-items-left tw-font-large tw-flex tw-list-none tw-flex-col tw-gap-3 tw-space-y-2 tw-p-3">
				<li class="tw-flex tw-w-10 tw-items-center tw-justify-between">
					<span
						class="tw-group tw-flex tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular"
						@click="clickReturn()"
					>
						<!-- The back button icon -->
						<span class="material-symbols-outlined tw-user-select-none tw-mr-1 tw-text-link-regular"
							>navigate_before</span
						>
						<!-- The back button label -->
						<span class="!tw-text-link-regular group-hover:tw-underline" v-if="minimized === false">{{
							translate('BACK')
						}}</span>
					</span>
					<span
						class="material-symbols-outlined tw-absolute tw-right-[-12px] tw-cursor-pointer tw-rounded-full tw-bg-neutral-400 !tw-text-xl/5"
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
							class="tw-user-select-none tw-group tw-flex tw-w-full tw-cursor-pointer tw-items-start tw-rounded-lg tw-p-2"
							:class="
								activeMenu === indexMenu
									? 'tw-bg-profile-light tw-font-bold tw-text-profile-full'
									: 'hover:tw-bg-gray-200'
							"
						>
							<span
								class="material-symbols-outlined tw-mr-2.5 tw-font-bold"
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
