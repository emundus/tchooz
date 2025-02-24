import { P as Parameter } from "./Parameter.js";
import { e as eventsService } from "./events2.js";
import { _ as _export_sfc, r as resolveComponent, c as createElementBlock, o as openBlock, d as createBaseVNode, t as toDisplayString, i as withModifiers, F as Fragment, e as renderList, h as withDirectives, v as vShow, g as createVNode, a as createCommentVNode, s as settingsService, u as useGlobalStore } from "./app_emundus.js";
import "./index.js";
const byteToHex = [];
for (let i = 0; i < 256; ++i) {
  byteToHex.push((i + 256).toString(16).slice(1));
}
function unsafeStringify(arr, offset = 0) {
  return (byteToHex[arr[offset + 0]] + byteToHex[arr[offset + 1]] + byteToHex[arr[offset + 2]] + byteToHex[arr[offset + 3]] + "-" + byteToHex[arr[offset + 4]] + byteToHex[arr[offset + 5]] + "-" + byteToHex[arr[offset + 6]] + byteToHex[arr[offset + 7]] + "-" + byteToHex[arr[offset + 8]] + byteToHex[arr[offset + 9]] + "-" + byteToHex[arr[offset + 10]] + byteToHex[arr[offset + 11]] + byteToHex[arr[offset + 12]] + byteToHex[arr[offset + 13]] + byteToHex[arr[offset + 14]] + byteToHex[arr[offset + 15]]).toLowerCase();
}
let getRandomValues;
const rnds8 = new Uint8Array(16);
function rng() {
  if (!getRandomValues) {
    if (typeof crypto === "undefined" || !crypto.getRandomValues) {
      throw new Error("crypto.getRandomValues() not supported. See https://github.com/uuidjs/uuid#getrandomvalues-not-supported");
    }
    getRandomValues = crypto.getRandomValues.bind(crypto);
  }
  return getRandomValues(rnds8);
}
const randomUUID = typeof crypto !== "undefined" && crypto.randomUUID && crypto.randomUUID.bind(crypto);
const native = { randomUUID };
function v4(options, buf, offset) {
  var _a;
  if (native.randomUUID && true && !options) {
    return native.randomUUID();
  }
  options = options || {};
  const rnds = options.random ?? ((_a = options.rng) == null ? void 0 : _a.call(options)) ?? rng();
  if (rnds.length < 16) {
    throw new Error("Random bytes length must be >= 16");
  }
  rnds[6] = rnds[6] & 15 | 64;
  rnds[8] = rnds[8] & 63 | 128;
  return unsafeStringify(rnds);
}
const _sfc_main = {
  name: "LocationForm",
  components: { Parameter },
  emits: ["close", "open"],
  props: {
    isModal: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      location_id: 0,
      location: {},
      loading: true,
      specifications: [],
      fields: [
        {
          param: "name",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_LOCATION_NAME",
          helptext: "",
          displayed: true
        },
        {
          param: "address",
          type: "textarea",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_LOCATION_ADDRESS",
          helptext: "",
          displayed: true,
          optional: true
        },
        {
          param: "description",
          type: "textarea",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_LOCATION_DESCRIPTION",
          helptext: "",
          displayed: true,
          optional: true
        }
      ],
      rooms: []
    };
  },
  created() {
    if (useGlobalStore().datas.locationid) {
      this.location_id = parseInt(useGlobalStore().datas.locationid.value);
    }
    this.getSpecifications().then((response) => {
      if (response) {
        if (this.location_id) {
          this.getLocation(this.location_id);
        } else {
          this.loading = false;
        }
      }
    });
  },
  methods: {
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },
    // Form
    addRepeatBlock(name = "", specifications = []) {
      let new_room = {};
      new_room.id = v4();
      new_room.fields = [
        {
          param: "name",
          type: "text",
          placeholder: "",
          value: name,
          label: "COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOM_NAME",
          helptext: "",
          displayed: true
        },
        {
          param: "specifications",
          type: "multiselect",
          multiselectOptions: {
            noOptions: false,
            multiple: true,
            taggable: false,
            searchable: true,
            optionsPlaceholder: "",
            selectLabel: "",
            selectGroupLabel: "",
            selectedLabel: "",
            deselectedLabel: "",
            deselectGroupLabel: "",
            noOptionsText: "",
            noResultsText: "COM_EMUNDUS_MULTISELECT_NORESULTS",
            tagValidations: [],
            options: this.specifications,
            label: "label",
            trackBy: "value"
          },
          value: specifications,
          label: "COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOM_SPECS",
          helptext: "",
          placeholder: "",
          displayed: true,
          optional: true
        }
      ];
      this.rooms.push(new_room);
    },
    removeRepeatBlock(room_id) {
      const key = this.rooms.findIndex((room) => room.id === room_id);
      this.rooms.splice(key, 1);
      this.$forceUpdate();
    },
    duplicateRepeatBlock(room_id) {
      const key = this.rooms.findIndex((room) => room.id === room_id);
      let new_room = {};
      new_room.id = v4();
      new_room.fields = this.rooms[key].fields.map((field) => {
        return {
          ...field,
          // Deep copy the nested `multiselectOptions` object
          multiselectOptions: field.multiselectOptions ? { ...field.multiselectOptions } : null
        };
      });
      this.rooms.push(new_room);
    },
    // Services
    getSpecifications() {
      return new Promise((resolve, reject) => {
        eventsService.getSpecifications().then((response) => {
          if (response.status) {
            this.specifications = response.data;
            resolve(true);
          } else {
            reject("Failed to get specifications");
          }
        });
      });
    },
    getLocation(location_id) {
      eventsService.getLocation(location_id).then((response) => {
        if (response.status) {
          this.location = response.data;
          for (const field of this.fields) {
            if (this.location[field.param]) {
              field.value = this.location[field.param];
            }
          }
          for (const room of this.location.rooms) {
            this.addRepeatBlock(room.label, room.specifications);
          }
        }
        this.loading = false;
      });
    },
    saveLocation() {
      let location = {};
      location.rooms = [];
      const locationValidationFailed = this.fields.some((field) => {
        let ref_name = "location_" + field.param;
        if (!this.$refs[ref_name][0].validate()) {
          return true;
        }
        location[field.param] = field.value;
        return false;
      });
      if (locationValidationFailed) return;
      this.rooms.some((room) => {
        let roomObject = {};
        room.fields.forEach((field) => {
          let ref_name = "room_" + room.id + "_" + field.param;
          if (!this.$refs[ref_name][0].validate()) {
            return true;
          }
          roomObject[field.param] = field.value;
        });
        location.rooms.push(roomObject);
        return false;
      });
      if (this.location_id) {
        location.id = this.location_id;
      }
      eventsService.saveLocation(location).then((response) => {
        if (response.status === true) {
          if (this.$props.isModal) {
            this.$emit("close", response.data);
          } else {
            this.redirectJRoute("index.php?option=com_emundus&view=events");
          }
        } else {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: response.message
          });
        }
      });
    }
  },
  computed: {
    disabledSubmit: function() {
      let field_bool = this.fields.some((field) => {
        if (!field.optional) {
          return field.value === "" || field.value === 0;
        }
      });
      if (!field_bool && this.rooms.length > 0) {
        return this.rooms.some((room) => {
          return room.fields.some((field) => {
            if (!field.optional) {
              return field.value === "" || field.value === 0 || field.value.length === 0;
            } else {
              return false;
            }
          });
        });
      }
      return field_bool;
    }
  }
};
const _hoisted_1 = { key: 0 };
const _hoisted_2 = {
  key: 0,
  class: "tw-pt-4 tw-sticky tw-top-0 tw-bg-white tw-border-b tw-border-neutral-300 tw-z-10"
};
const _hoisted_3 = { class: "tw-flex tw-items-center tw-justify-between tw-mb-4" };
const _hoisted_4 = { key: 1 };
const _hoisted_5 = { class: "tw-ml-2 tw-text-neutral-900" };
const _hoisted_6 = { class: "tw-mt-4" };
const _hoisted_7 = { class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6" };
const _hoisted_8 = { class: "tw-mt-4 tw-flex tw-flex-col tw-gap-3" };
const _hoisted_9 = { class: "tw-flex tw-justify-end tw-items-center tw-gap-2" };
const _hoisted_10 = ["onClick"];
const _hoisted_11 = ["onClick"];
const _hoisted_12 = { class: "tw-flex tw-flex-col tw-gap-6" };
const _hoisted_13 = { class: "tw-flex tw-justify-end" };
const _hoisted_14 = { class: "tw-flex tw-justify-end tw-mt-7 tw-mb-2" };
const _hoisted_15 = ["disabled"];
const _hoisted_16 = { key: 0 };
const _hoisted_17 = { key: 1 };
const _hoisted_18 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Parameter = resolveComponent("Parameter");
  return openBlock(), createElementBlock("div", null, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_1, [
      $props.isModal ? (openBlock(), createElementBlock("div", _hoisted_2, [
        createBaseVNode("div", _hoisted_3, [
          createBaseVNode("h2", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_LOCATION")), 1),
          createBaseVNode("button", {
            class: "tw-cursor-pointer tw-bg-transparent",
            onClick: _cache[0] || (_cache[0] = withModifiers(($event) => _ctx.$emit("close"), ["prevent"]))
          }, _cache[4] || (_cache[4] = [
            createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
          ]))
        ])
      ])) : (openBlock(), createElementBlock("div", _hoisted_4, [
        createBaseVNode("div", {
          class: "tw-flex tw-items-center tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300",
          onClick: _cache[1] || (_cache[1] = ($event) => $options.redirectJRoute("index.php?option=com_emundus&view=events"))
        }, [
          _cache[5] || (_cache[5] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-600" }, "navigate_before", -1)),
          createBaseVNode("span", _hoisted_5, toDisplayString(_ctx.translate("BACK")), 1)
        ]),
        createBaseVNode("h1", _hoisted_6, toDisplayString(this.location && Object.keys(this.location).length > 0 ? _ctx.translate("COM_EMUNDUS_ONBOARD_EDIT_LOCATION") + " " + this.location["name"] : _ctx.translate("COM_EMUNDUS_ONBOARD_ADD_LOCATION")), 1),
        _cache[6] || (_cache[6] = createBaseVNode("hr", { class: "tw-mt-1.5 tw-mb-8" }, null, -1))
      ])),
      createBaseVNode("div", _hoisted_7, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.fields, (field) => {
          return withDirectives((openBlock(), createElementBlock("div", {
            key: field.param,
            class: "tw-w-full"
          }, [
            createVNode(_component_Parameter, {
              ref_for: true,
              ref: "location_" + field.param,
              "parameter-object": field
            }, null, 8, ["parameter-object"])
          ])), [
            [vShow, field.displayed]
          ]);
        }), 128)),
        createBaseVNode("div", _hoisted_8, [
          createBaseVNode("h3", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOMS")), 1),
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.rooms, (room) => {
            return openBlock(), createElementBlock("div", {
              key: room.id,
              class: "tw-flex tw-flex-col tw-gap-2 tw-bg-white tw-rounded-coordinator tw-border tw-border-neutral-400 tw-px-3 tw-py-4"
            }, [
              createBaseVNode("div", _hoisted_9, [
                createBaseVNode("button", {
                  type: "button",
                  onClick: ($event) => $options.duplicateRepeatBlock(room.id),
                  class: "w-auto"
                }, _cache[7] || (_cache[7] = [
                  createBaseVNode("span", { class: "material-symbols-outlined !tw-text-neutral-900" }, "content_copy", -1)
                ]), 8, _hoisted_10),
                $data.rooms.length > 0 ? (openBlock(), createElementBlock("button", {
                  key: 0,
                  type: "button",
                  onClick: ($event) => $options.removeRepeatBlock(room.id),
                  class: "w-auto"
                }, _cache[8] || (_cache[8] = [
                  createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "close", -1)
                ]), 8, _hoisted_11)) : createCommentVNode("", true)
              ]),
              createBaseVNode("div", _hoisted_12, [
                (openBlock(true), createElementBlock(Fragment, null, renderList(room.fields, (field) => {
                  return withDirectives((openBlock(), createElementBlock("div", {
                    key: field.param,
                    class: "tw-w-full"
                  }, [
                    createVNode(_component_Parameter, {
                      ref_for: true,
                      ref: "room_" + room.id + "_" + field.param,
                      "parameter-object": field,
                      "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null
                    }, null, 8, ["parameter-object", "multiselect-options"])
                  ])), [
                    [vShow, field.displayed]
                  ]);
                }), 128))
              ])
            ]);
          }), 128)),
          createBaseVNode("div", _hoisted_13, [
            createBaseVNode("button", {
              type: "button",
              onClick: _cache[2] || (_cache[2] = ($event) => $options.addRepeatBlock()),
              class: "tw-mt-2 tw-w-auto tw-flex tw-items-center tw-gap-1"
            }, [
              _cache[9] || (_cache[9] = createBaseVNode("span", { class: "material-symbols-outlined !tw-text-neutral-900" }, "add", -1)),
              createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_ROOM")), 1)
            ])
          ])
        ])
      ]),
      createBaseVNode("div", _hoisted_14, [
        createBaseVNode("button", {
          type: "button",
          class: "tw-btn-primary !tw-w-auto",
          disabled: $options.disabledSubmit,
          onClick: _cache[3] || (_cache[3] = withModifiers(($event) => $options.saveLocation(), ["prevent"]))
        }, [
          $data.location_id ? (openBlock(), createElementBlock("span", _hoisted_16, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EDIT_LOCATION")), 1)) : (openBlock(), createElementBlock("span", _hoisted_17, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_LOCATION_CREATE")), 1))
        ], 8, _hoisted_15)
      ])
    ])) : (openBlock(), createElementBlock("div", _hoisted_18))
  ]);
}
const LocationForm = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  LocationForm as default
};
