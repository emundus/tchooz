<template>
    <div v-if="primary && secondary">
        <div class="tw-flex tw-flex-row tw-gap-6">
            <div class="tw-flex tw-flex-col tw-gap-3">
                <div class="tw-flex tw-items-center tw-gap-3">
                    <div>
                        <input
                            type="color"
                            class="custom-color-picker tw-rounded-full"
                            v-model="primary"
                            id="primary_color"
                        />
                    </div>
                    <label class="tw-font-medium tw-mb-0" style="max-width: 100px">{{
                        translate('COM_EMUNDUS_ONBOARD_PRIMARY_COLOR')
                    }}</label>
                </div>

                <div class="tw-flex tw-items-center tw-gap-3">
                    <div>
                        <input
                            type="color"
                            v-model="secondary"
                            class="custom-color-picker tw-rounded-full"
                            id="secondary_color"
                        />
                    </div>
                    <label class="tw-font-medium tw-mb-0" style="max-width: 100px">{{
                        translate('COM_EMUNDUS_ONBOARD_SECONDARY_COLOR')
                    }}</label>
                </div>
            </div>
        </div>
        <div class="tw-mt-4 tw-w-full">
            <h3 class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_THEME_ACCESSIBILITY') }}</h3>
            <div class="tw-w-full" v-if="contrastPrimary && contrastSecondary">
                <Info
                    v-if="contrastPrimary.ratio > 4.5 && contrastSecondary.ratio > 4.5"
                    :text="'COM_EMUNDUS_ONBOARD_RGAA_OK'"
                    :bg-color="'tw-bg-main-50'"
                    :icon="'check_circle'"
                    :icon-type="'material-icons'"
                    :icon-color="'tw-text-green-500'"
                    :class="'tw-mt-2'"
                ></Info>
                <Info
                    v-if="contrastPrimary.ratio < 4.5"
                    s
                    :text="'COM_EMUNDUS_SETTINGS_CONTRAST_ERROR_PRIMARY'"
                    :icon="'warning'"
                    :bg-color="'tw-bg-orange-100'"
                    :icon-type="'material-icons'"
                    :icon-color="'tw-text-orange-600'"
                    :class="'tw-mt-2'"
                ></Info>
                <Info
                    v-if="contrastSecondary.ratio < 4.5"
                    :text="'COM_EMUNDUS_SETTINGS_CONTRAST_ERROR_SECONDARY'"
                    :icon="'warning'"
                    :bg-color="'tw-bg-orange-100'"
                    :icon-type="'material-icons'"
                    :icon-color="'tw-text-orange-600'"
                    :class="'tw-mt-2'"
                ></Info>
                <Info
                    v-if="rgaaState === 0"
                    :text="'COM_EMUNDUS_ONBOARD_ERROR_COLORS_SAME'"
                    :icon="'warning'"
                    :bg-color="'tw-bg-orange-100'"
                    :icon-type="'material-icons'"
                    :icon-color="'tw-text-orange-600'"
                    :class="'tw-mt-2'"
                ></Info>
                <div class="tw-mt-3">
                    <h4
                        @click="showDetails = !showDetails"
                        class="tw-flex tw-items-center tw-font-semibold tw-cursor-pointer"
                    >
                        {{ translate('COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS') }}
                        <span class="material-icons-outlined tw-font-sm" v-if="!showDetails">add</span>
                        <span class="material-icons-outlined tw-font-sm" v-if="showDetails">remove</span>
                    </h4>
                    <div v-if="showDetails" class="tw-mt-2">
                        <div>
                            <h5>{{ translate('COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_NORMAL_TEXT') }}</h5>
                            <div class="tw-flex tw-gap-2 tw-items-center tw-mt-1">
                                <span
                                    class="material-icons-outlined tw-text-green-500"
                                    v-if="contrastPrimary.AA === 'pass'"
                                    >check_circle</span
                                >
                                <span
                                    class="material-icons-outlined tw-text-red-500"
                                    v-if="contrastPrimary.AA === 'fail'"
                                    >highlight_off</span
                                >
                                <button
                                    class="tw-rounded-coordinator tw-px-3 tw-py-2 tw-text-white"
                                    type="button"
                                    :style="{ backgroundColor: primary, borderColor: primary }"
                                >
                                    {{ translate('COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LOGIN_TEXT') }}
                                </button>
                            </div>
                            <div class="tw-flex tw-gap-2 tw-items-center tw-mt-1">
                                <span
                                    class="material-icons-outlined tw-text-green-500"
                                    v-if="contrastSecondary.AA === 'pass'"
                                    >check_circle</span
                                >
                                <span
                                    class="material-icons-outlined tw-text-red-500"
                                    v-if="contrastSecondary.AA === 'fail'"
                                    >highlight_off</span
                                >
                                <button
                                    class="tw-btn-secondary tw-text-white"
                                    type="button"
                                    :style="{ backgroundColor: secondary, borderColor: secondary }"
                                >
                                    {{ translate('COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LOGIN_TEXT') }}
                                </button>
                            </div>
                        </div>
                        <div class="tw-mt-2">
                            <h5>{{ translate('COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LARGE_TEXT') }}</h5>
                            <div class="tw-flex tw-gap-2 tw-items-center tw-mt-1">
                                <span
                                    class="material-icons-outlined tw-text-green-500"
                                    v-if="contrastPrimary.AALarge === 'pass'"
                                    >check_circle</span
                                >
                                <span
                                    class="material-icons-outlined tw-text-red-500"
                                    v-if="contrastPrimary.AALarge === 'fail'"
                                    >highlight_off</span
                                >
                                <button
                                    class="tw-rounded-coordinator tw-px-3 tw-py-2 tw-text-white tw-font-bold"
                                    :style="{ backgroundColor: primary, borderColor: primary }"
                                    type="button"
                                >
                                    {{ translate('COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LOGIN_TEXT') }}
                                </button>
                            </div>
                            <div class="tw-flex tw-gap-2 tw-items-center tw-mt-1">
                                <span
                                    class="material-icons-outlined tw-text-green-500"
                                    v-if="contrastSecondary.AALarge === 'pass'"
                                    >check_circle</span
                                >
                                <span
                                    class="material-icons-outlined tw-text-red-500"
                                    v-if="contrastSecondary.AALarge === 'fail'"
                                    >highlight_off</span
                                >
                                <button
                                    class="tw-btn-secondary tw-font-bold tw-text-white"
                                    :style="{ backgroundColor: secondary, borderColor: secondary }"
                                    type="button"
                                >
                                    {{ translate('COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LOGIN_TEXT') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="tw-mt-3 btn btn-primary tw-float-right" v-if="changes" @click="saveColors">
            {{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE') }}
        </button>

        <div class="em-page-loader" v-if="loading"></div>
    </div>
</template>

<script>
import settingsService from '@/services/settings'
import axios from 'axios'
import qs from 'qs'
import Swal from 'sweetalert2'
import Info from '@/components/info.vue'

export default {
  name: 'global',
  props: {},
  components: { Info },
  data() {
    return {
      RED: 0.2126,
      GREEN: 0.7152,
      BLUE: 0.0722,
      GAMMA: 2.4,

      loading: false,
      showDetails: false,

      primary: null,
      secondary: null,
      changes: false,

      rgaaState: 0,
      contrastPrimary: null,
      contrastSecondary: null,
    }
  },

  async created() {
    this.loading = true
    this.changes = false

    await this.getAppColors()
    //await this.getVariable();

    this.loading = false
  },

  methods: {
    getVariable() {
      return new Promise((resolve) => {
        axios({
          method: 'get',
          url: 'index.php?option=com_emundus&controller=settings&task=getappVariablegantry',
        }).then(() => {
          resolve(true)
        })
      })
    },
    changeVariables(preset) {
      axios({
        method: 'post',
        url: 'index.php?option=com_emundus&controller=settings&task=updateVariablegantry',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        data: qs.stringify({
          preset: preset,
        }),
      }).then(() => {
        console.log('jojo')
      })
    },

    getAppColors() {
      return new Promise((resolve) => {
        axios({
          method: 'get',
          url: 'index.php?option=com_emundus&controller=settings&task=getappcolors',
        }).then((rep) => {
          this.primary = rep.data.primary
          this.secondary = rep.data.secondary

          this.rgaaState = this.checkSimilarity(this.primary, this.secondary)
          this.checkContrast('#FFFFFF', this.primary).then((response) => {
            this.contrastPrimary = response
          })
          this.checkContrast('#FFFFFF', this.secondary).then((response) => {
            this.contrastSecondary = response
          })

          resolve(true)
        })
      })
    },

    async saveColors() {
      let preset = { id: 7, primary: this.primary, secondary: this.secondary }
      settingsService.saveColors(preset).then((response) => {
        if (response.status == 1) {
          this.changes = false
          Swal.fire({
            title: this.translate('COM_EMUNDUS_ONBOARD_SUCCESS'),
            text: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_THEME_SAVE_SUCCESS'),
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
              title: 'em-swal-title',
            },
            timer: 2000,
          })
        }
      })
    },

    async saveMethod() {
      await this.saveColors()
      return true
    },

    checkSimilarity(hex1, hex2) {
      let rgb1 = this.hexToRgb(hex1)
      let rgb2 = this.hexToRgb(hex2)
      const deltaECalc = this.deltaE(rgb1, rgb2)

      if (deltaECalc < 11) {
        return 0
      } else {
        return 1
      }
    },

    checkContrast(hex1, hex2) {
      return new Promise((resolve) => {
        fetch(
          'https://webaim.org/resources/contrastchecker/?fcolor=' +
                        hex1.replace('#', '') +
                        '&bcolor=' +
                        hex2.replace('#', '') +
                        '&api',
        )
          .then((response) => {
            return response.json()
          })
          .then((data) => {
            resolve(data)
          })
      })
    },

    /* Utilities function */
    deltaE(rgbA, rgbB) {
      let labA = this.rgb2lab(rgbA)
      let labB = this.rgb2lab(rgbB)
      let deltaL = labA[0] - labB[0]
      let deltaA = labA[1] - labB[1]
      let deltaB = labA[2] - labB[2]
      let c1 = Math.sqrt(labA[1] * labA[1] + labA[2] * labA[2])
      let c2 = Math.sqrt(labB[1] * labB[1] + labB[2] * labB[2])
      let deltaC = c1 - c2
      let deltaH = deltaA * deltaA + deltaB * deltaB - deltaC * deltaC
      deltaH = deltaH < 0 ? 0 : Math.sqrt(deltaH)
      let sc = 1.0 + 0.045 * c1
      let sh = 1.0 + 0.015 * c1
      let deltaLKlsl = deltaL / 1.0
      let deltaCkcsc = deltaC / sc
      let deltaHkhsh = deltaH / sh
      let i = deltaLKlsl * deltaLKlsl + deltaCkcsc * deltaCkcsc + deltaHkhsh * deltaHkhsh
      return i < 0 ? 0 : Math.sqrt(i)
    },
    rgb2lab(rgb) {
      let r = rgb[0] / 255,
        g = rgb[1] / 255,
        b = rgb[2] / 255,
        x,
        y,
        z
      r = r > 0.04045 ? Math.pow((r + 0.055) / 1.055, 2.4) : r / 12.92
      g = g > 0.04045 ? Math.pow((g + 0.055) / 1.055, 2.4) : g / 12.92
      b = b > 0.04045 ? Math.pow((b + 0.055) / 1.055, 2.4) : b / 12.92
      x = (r * 0.4124 + g * 0.3576 + b * 0.1805) / 0.95047
      y = (r * 0.2126 + g * 0.7152 + b * 0.0722) / 1.0
      z = (r * 0.0193 + g * 0.1192 + b * 0.9505) / 1.08883
      x = x > 0.008856 ? Math.pow(x, 1 / 3) : 7.787 * x + 16 / 116
      y = y > 0.008856 ? Math.pow(y, 1 / 3) : 7.787 * y + 16 / 116
      z = z > 0.008856 ? Math.pow(z, 1 / 3) : 7.787 * z + 16 / 116
      return [116 * y - 16, 500 * (x - y), 200 * (y - z)]
    },
    hexToRgb(hex) {
      return hex
        .replace(/^#?([a-f\d])([a-f\d])([a-f\d])$/i, (m, r, g, b) => '#' + r + r + g + g + b + b)
        .substring(1)
        .match(/.{2}/g)
        .map((x) => parseInt(x, 16))
    },
  },
  watch: {
    primary: function (val, oldVal) {
      if (oldVal !== null) {
        this.$emit('needSaving', true)
        this.changes = true
        this.rgaaState = this.checkSimilarity(val, this.secondary)
        this.checkContrast('#FFFFFF', val).then((response) => {
          this.contrastPrimary = response
        })
      }
    },
    secondary: function (val, oldVal) {
      if (oldVal !== null) {
        this.$emit('needSaving', true)
        this.changes = true
        this.rgaaState = this.checkSimilarity(val, this.primary)
        this.checkContrast('#FFFFFF', val).then((response) => {
          this.contrastSecondary = response
        })
      }
    },
  },
}
</script>

<style scoped>
.custom-color-picker {
    width: 44px !important;
    height: 48px !important;
    border: none !important;
    padding: 0 !important;
    outline: none;
    cursor: pointer;
}

.custom-color-picker::-webkit-color-swatch {
    border-radius: 100%;
}
</style>
