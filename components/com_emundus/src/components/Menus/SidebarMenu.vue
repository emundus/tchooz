<template>
  <aside id="logo-sidebar"
         class="corner-bottom-left-background tw-sticky tw-left-0 tw-top-0 tw-h-screen tw-bg-white tw-border-r tw-border-gray-200 tw-transition-all"
         :class="minimized === true ? 'tw-w-[64px]' : 'tw-w-64'"
         aria-label="Sidebar">
    <div class="tw-h-full tw-pb-4 tw-overflow-y-auto tw-bg-white"
         @mouseover="showMinimized = true"
         @mouseleave="showMinimized = false"
    >
      <ul class="tw-flex tw-flex-col tw-items-left tw-gap-3 tw-p-3 tw-space-y-2 tw-font-large tw-list-none">
        <li class="tw-w-10 tw-flex tw-items-center tw-justify-between">
              <span class="tw-flex tw-items-center tw-px-2 tw-rounded-lg tw-group tw-cursor-pointer" @click="clickReturn()">
                <!-- The back button icon -->
                <span class="material-icons-outlined tw-user-select-none tw-text-profile-full">arrow_back</span>
                <!-- The back button label -->
                <span class="tw-pl-2.5 tw-text-profile-full" v-if="minimized === false">{{ translate('BACK') }}</span>
              </span>
          <span class="material-icons-outlined tw-absolute tw-right-[-12px] !tw-text-xl/5 tw-bg-neutral-400 tw-rounded-full tw-cursor-pointer"
                :class="minimized ? 'tw-rotate-180' : ''"
                v-show="showMinimized === true"
                @click="handleSidebarSize">chevron_left</span>
        </li>

        <li v-for="(menu, indexMenu) in menus" class="!tw-mt-0 tw-w-full" v-if="menu.published === true">
              <div :id="'Menu-'+indexMenu" @click="activeMenu = indexMenu;"
                    class="tw-flex tw-items-start tw-w-full tw-p-2 tw-cursor-pointer tw-rounded-lg tw-group tw-user-select-none"
              :class="activeMenu === indexMenu ? 'tw-font-bold tw-text-profile-full tw-bg-profile-light'  : 'hover:tw-bg-gray-200'">
                <span class="material-icons-outlined tw-font-bold tw-mr-2.5" :class="activeMenu === indexMenu ? 'tw-text-profile-full' : ''"
                   name="icon-Menu"
                   :title="translate(menu.label)"
                   :id="'icon-'+indexMenu">{{ menu.icon }}</span>
                <p class="tw-font-bold tw-leading-6"
                      v-if="minimized === false"
                      :class="activeMenu === indexMenu ? 'tw-text-profile-full' : ''">{{ translate(menu.label) }}</p>
              </div>
        </li>
      </ul>
    </div>

    <div class="tchoozy-corner-bottom-left-bakground-mask-image tw-h-1/3	tw-w-full tw-absolute tw-bottom-0 tw-bg-main-500"></div>

  </aside>
</template>

<script>

export default {
  name: "SidebarMenu",
  components: {},
  props: {
    json_source: {
      type: String,
      required: true,
    },
  },

  mixins: [],

  data() {
    return {
      menus: [],

      activeMenu: null,
      minimized: false,
      showMinimized: false
    }
  },
  created() {
    this.menus = require('../../../data/' + this.$props.json_source);
    this.activeMenu = 0;

    const sessionMenu = sessionStorage.getItem('tchooz_selected_menu/'+this.$props.json_source.replace('.json','')+ '/' + document.location.hostname);
    const sessionSideBarMinimized = sessionStorage.getItem('tchooz_sidebar_minimized/'+ document.location.hostname);
    if (sessionSideBarMinimized) {
      this.minimized = sessionSideBarMinimized === 'true';
    }
    if (sessionMenu) {
      this.activeMenu = parseInt(sessionMenu);
    }
    this.$emit('listMenus', this.menus , 'menus');
  },
  mounted() {
  },
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
      sessionStorage.setItem('tchooz_selected_menu/'+this.$props.json_source.replace('.json','')+ '/' + document.location.hostname, val);
      this.$emit('menuSelected', this.menus[val])
    },
    minimized: function (val, oldVal) {
      console.log('minimized', val);
      console.log('oldVal', oldVal);
      if (oldVal !== null) {
        sessionStorage.setItem('tchooz_sidebar_minimized/' + document.location.hostname, val);
      }
    }
  },
}
</script>

<style scoped>
</style>