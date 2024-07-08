<template>
  <div>
    <div class="tw-flex tw-flex-wrap">
      <div v-for="addon in addons" class="tw-flex tw-flex-col tw-flex-wrap tw-mr-3"
           :key="addon.id">
        <div class="tw-cursor-pointer tw-flex tw-flex-col tw-bg-white tw-justify-center tw-items-center tw-rounded tw-border tw-border-neutral-300 tw-p-6" name="tilebutton">
            <div class="tw-mb-10 tw-rounded tw-flex tw-justify-center tw-items-center" :style="{ 'background-color': addon.color, 'width': '16em', 'height':'10em' }">
              <span class="material-icons-outlined !tw-text-white"
                 :style="{'transform': 'scale(7)'}">{{ addon.icon }}</span>
            </div>
          <span class="tw-font-medium">{{ translate(addon.label) }}</span>
        </div>
      </div>
    </div>
  </div>

</template>

<script>
export default {
  name: "Content",
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
      addons: [
        {
          "label": "COM_EMUNDUS_GLOBAL_PARMAS_SECTIONS_SHARE_FILE",
          "published": false,
          "type": "Tile",
          "color": "#F04437",
          "icon": "share",
          "link": "",
          "helptext": ""
        },
        {
          "label": "COM_EMUNDUS_GLOBAL_PARMAS_SECTIONS_MAIL_GENERATION/PUBLISHING",
          "published": false,
          "type": "Tile",
          "color": "#D292EF",
          "icon": "mail",
          "link": "",
          "helptext": ""
        }
      ],
    }
  },
  created() {},
  mounted() {},
  methods: {},
  watch: {},
}
</script>

<style scoped>

</style>