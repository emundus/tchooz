import { au as getDefaultExportFromCjs, _ as _export_sfc, C as script, s as settingsService, S as Swal, u as useGlobalStore, af as reactive, r as resolveComponent, c as createElementBlock, o as openBlock, b as createCommentVNode, d as createBaseVNode, n as normalizeClass, m as createTextVNode, t as toDisplayString, w as withDirectives, a as createBlock, y as vModelSelect, F as Fragment, e as renderList, f as withCtx, z as vModelText, j as normalizeStyle, a6 as vModelRadio, Y as vModelCheckbox, a4 as vModelDynamic, h as withModifiers, W as mergeProps, Z as toHandlers, aa as resolveDynamicComponent } from "./app_emundus.js";
import { D as DatePicker } from "./index.js";
import EventBooking from "./EventBooking.js";
var dayjs_min$1 = { exports: {} };
var dayjs_min = dayjs_min$1.exports;
var hasRequiredDayjs_min;
function requireDayjs_min() {
  if (hasRequiredDayjs_min) return dayjs_min$1.exports;
  hasRequiredDayjs_min = 1;
  (function(module, exports) {
    !function(t, e) {
      module.exports = e();
    }(dayjs_min, function() {
      var t = 1e3, e = 6e4, n = 36e5, r = "millisecond", i = "second", s = "minute", u = "hour", a = "day", o = "week", c = "month", f = "quarter", h = "year", d = "date", l = "Invalid Date", $ = /^(\d{4})[-/]?(\d{1,2})?[-/]?(\d{0,2})[Tt\s]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?[.:]?(\d+)?$/, y = /\[([^\]]+)]|Y{1,4}|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g, M = { name: "en", weekdays: "Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"), months: "January_February_March_April_May_June_July_August_September_October_November_December".split("_"), ordinal: function(t2) {
        var e2 = ["th", "st", "nd", "rd"], n2 = t2 % 100;
        return "[" + t2 + (e2[(n2 - 20) % 10] || e2[n2] || e2[0]) + "]";
      } }, m = function(t2, e2, n2) {
        var r2 = String(t2);
        return !r2 || r2.length >= e2 ? t2 : "" + Array(e2 + 1 - r2.length).join(n2) + t2;
      }, v = { s: m, z: function(t2) {
        var e2 = -t2.utcOffset(), n2 = Math.abs(e2), r2 = Math.floor(n2 / 60), i2 = n2 % 60;
        return (e2 <= 0 ? "+" : "-") + m(r2, 2, "0") + ":" + m(i2, 2, "0");
      }, m: function t2(e2, n2) {
        if (e2.date() < n2.date()) return -t2(n2, e2);
        var r2 = 12 * (n2.year() - e2.year()) + (n2.month() - e2.month()), i2 = e2.clone().add(r2, c), s2 = n2 - i2 < 0, u2 = e2.clone().add(r2 + (s2 ? -1 : 1), c);
        return +(-(r2 + (n2 - i2) / (s2 ? i2 - u2 : u2 - i2)) || 0);
      }, a: function(t2) {
        return t2 < 0 ? Math.ceil(t2) || 0 : Math.floor(t2);
      }, p: function(t2) {
        return { M: c, y: h, w: o, d: a, D: d, h: u, m: s, s: i, ms: r, Q: f }[t2] || String(t2 || "").toLowerCase().replace(/s$/, "");
      }, u: function(t2) {
        return void 0 === t2;
      } }, g = "en", D = {};
      D[g] = M;
      var p = "$isDayjsObject", S = function(t2) {
        return t2 instanceof _ || !(!t2 || !t2[p]);
      }, w = function t2(e2, n2, r2) {
        var i2;
        if (!e2) return g;
        if ("string" == typeof e2) {
          var s2 = e2.toLowerCase();
          D[s2] && (i2 = s2), n2 && (D[s2] = n2, i2 = s2);
          var u2 = e2.split("-");
          if (!i2 && u2.length > 1) return t2(u2[0]);
        } else {
          var a2 = e2.name;
          D[a2] = e2, i2 = a2;
        }
        return !r2 && i2 && (g = i2), i2 || !r2 && g;
      }, O = function(t2, e2) {
        if (S(t2)) return t2.clone();
        var n2 = "object" == typeof e2 ? e2 : {};
        return n2.date = t2, n2.args = arguments, new _(n2);
      }, b = v;
      b.l = w, b.i = S, b.w = function(t2, e2) {
        return O(t2, { locale: e2.$L, utc: e2.$u, x: e2.$x, $offset: e2.$offset });
      };
      var _ = function() {
        function M2(t2) {
          this.$L = w(t2.locale, null, true), this.parse(t2), this.$x = this.$x || t2.x || {}, this[p] = true;
        }
        var m2 = M2.prototype;
        return m2.parse = function(t2) {
          this.$d = function(t3) {
            var e2 = t3.date, n2 = t3.utc;
            if (null === e2) return /* @__PURE__ */ new Date(NaN);
            if (b.u(e2)) return /* @__PURE__ */ new Date();
            if (e2 instanceof Date) return new Date(e2);
            if ("string" == typeof e2 && !/Z$/i.test(e2)) {
              var r2 = e2.match($);
              if (r2) {
                var i2 = r2[2] - 1 || 0, s2 = (r2[7] || "0").substring(0, 3);
                return n2 ? new Date(Date.UTC(r2[1], i2, r2[3] || 1, r2[4] || 0, r2[5] || 0, r2[6] || 0, s2)) : new Date(r2[1], i2, r2[3] || 1, r2[4] || 0, r2[5] || 0, r2[6] || 0, s2);
              }
            }
            return new Date(e2);
          }(t2), this.init();
        }, m2.init = function() {
          var t2 = this.$d;
          this.$y = t2.getFullYear(), this.$M = t2.getMonth(), this.$D = t2.getDate(), this.$W = t2.getDay(), this.$H = t2.getHours(), this.$m = t2.getMinutes(), this.$s = t2.getSeconds(), this.$ms = t2.getMilliseconds();
        }, m2.$utils = function() {
          return b;
        }, m2.isValid = function() {
          return !(this.$d.toString() === l);
        }, m2.isSame = function(t2, e2) {
          var n2 = O(t2);
          return this.startOf(e2) <= n2 && n2 <= this.endOf(e2);
        }, m2.isAfter = function(t2, e2) {
          return O(t2) < this.startOf(e2);
        }, m2.isBefore = function(t2, e2) {
          return this.endOf(e2) < O(t2);
        }, m2.$g = function(t2, e2, n2) {
          return b.u(t2) ? this[e2] : this.set(n2, t2);
        }, m2.unix = function() {
          return Math.floor(this.valueOf() / 1e3);
        }, m2.valueOf = function() {
          return this.$d.getTime();
        }, m2.startOf = function(t2, e2) {
          var n2 = this, r2 = !!b.u(e2) || e2, f2 = b.p(t2), l2 = function(t3, e3) {
            var i2 = b.w(n2.$u ? Date.UTC(n2.$y, e3, t3) : new Date(n2.$y, e3, t3), n2);
            return r2 ? i2 : i2.endOf(a);
          }, $2 = function(t3, e3) {
            return b.w(n2.toDate()[t3].apply(n2.toDate("s"), (r2 ? [0, 0, 0, 0] : [23, 59, 59, 999]).slice(e3)), n2);
          }, y2 = this.$W, M3 = this.$M, m3 = this.$D, v2 = "set" + (this.$u ? "UTC" : "");
          switch (f2) {
            case h:
              return r2 ? l2(1, 0) : l2(31, 11);
            case c:
              return r2 ? l2(1, M3) : l2(0, M3 + 1);
            case o:
              var g2 = this.$locale().weekStart || 0, D2 = (y2 < g2 ? y2 + 7 : y2) - g2;
              return l2(r2 ? m3 - D2 : m3 + (6 - D2), M3);
            case a:
            case d:
              return $2(v2 + "Hours", 0);
            case u:
              return $2(v2 + "Minutes", 1);
            case s:
              return $2(v2 + "Seconds", 2);
            case i:
              return $2(v2 + "Milliseconds", 3);
            default:
              return this.clone();
          }
        }, m2.endOf = function(t2) {
          return this.startOf(t2, false);
        }, m2.$set = function(t2, e2) {
          var n2, o2 = b.p(t2), f2 = "set" + (this.$u ? "UTC" : ""), l2 = (n2 = {}, n2[a] = f2 + "Date", n2[d] = f2 + "Date", n2[c] = f2 + "Month", n2[h] = f2 + "FullYear", n2[u] = f2 + "Hours", n2[s] = f2 + "Minutes", n2[i] = f2 + "Seconds", n2[r] = f2 + "Milliseconds", n2)[o2], $2 = o2 === a ? this.$D + (e2 - this.$W) : e2;
          if (o2 === c || o2 === h) {
            var y2 = this.clone().set(d, 1);
            y2.$d[l2]($2), y2.init(), this.$d = y2.set(d, Math.min(this.$D, y2.daysInMonth())).$d;
          } else l2 && this.$d[l2]($2);
          return this.init(), this;
        }, m2.set = function(t2, e2) {
          return this.clone().$set(t2, e2);
        }, m2.get = function(t2) {
          return this[b.p(t2)]();
        }, m2.add = function(r2, f2) {
          var d2, l2 = this;
          r2 = Number(r2);
          var $2 = b.p(f2), y2 = function(t2) {
            var e2 = O(l2);
            return b.w(e2.date(e2.date() + Math.round(t2 * r2)), l2);
          };
          if ($2 === c) return this.set(c, this.$M + r2);
          if ($2 === h) return this.set(h, this.$y + r2);
          if ($2 === a) return y2(1);
          if ($2 === o) return y2(7);
          var M3 = (d2 = {}, d2[s] = e, d2[u] = n, d2[i] = t, d2)[$2] || 1, m3 = this.$d.getTime() + r2 * M3;
          return b.w(m3, this);
        }, m2.subtract = function(t2, e2) {
          return this.add(-1 * t2, e2);
        }, m2.format = function(t2) {
          var e2 = this, n2 = this.$locale();
          if (!this.isValid()) return n2.invalidDate || l;
          var r2 = t2 || "YYYY-MM-DDTHH:mm:ssZ", i2 = b.z(this), s2 = this.$H, u2 = this.$m, a2 = this.$M, o2 = n2.weekdays, c2 = n2.months, f2 = n2.meridiem, h2 = function(t3, n3, i3, s3) {
            return t3 && (t3[n3] || t3(e2, r2)) || i3[n3].slice(0, s3);
          }, d2 = function(t3) {
            return b.s(s2 % 12 || 12, t3, "0");
          }, $2 = f2 || function(t3, e3, n3) {
            var r3 = t3 < 12 ? "AM" : "PM";
            return n3 ? r3.toLowerCase() : r3;
          };
          return r2.replace(y, function(t3, r3) {
            return r3 || function(t4) {
              switch (t4) {
                case "YY":
                  return String(e2.$y).slice(-2);
                case "YYYY":
                  return b.s(e2.$y, 4, "0");
                case "M":
                  return a2 + 1;
                case "MM":
                  return b.s(a2 + 1, 2, "0");
                case "MMM":
                  return h2(n2.monthsShort, a2, c2, 3);
                case "MMMM":
                  return h2(c2, a2);
                case "D":
                  return e2.$D;
                case "DD":
                  return b.s(e2.$D, 2, "0");
                case "d":
                  return String(e2.$W);
                case "dd":
                  return h2(n2.weekdaysMin, e2.$W, o2, 2);
                case "ddd":
                  return h2(n2.weekdaysShort, e2.$W, o2, 3);
                case "dddd":
                  return o2[e2.$W];
                case "H":
                  return String(s2);
                case "HH":
                  return b.s(s2, 2, "0");
                case "h":
                  return d2(1);
                case "hh":
                  return d2(2);
                case "a":
                  return $2(s2, u2, true);
                case "A":
                  return $2(s2, u2, false);
                case "m":
                  return String(u2);
                case "mm":
                  return b.s(u2, 2, "0");
                case "s":
                  return String(e2.$s);
                case "ss":
                  return b.s(e2.$s, 2, "0");
                case "SSS":
                  return b.s(e2.$ms, 3, "0");
                case "Z":
                  return i2;
              }
              return null;
            }(t3) || i2.replace(":", "");
          });
        }, m2.utcOffset = function() {
          return 15 * -Math.round(this.$d.getTimezoneOffset() / 15);
        }, m2.diff = function(r2, d2, l2) {
          var $2, y2 = this, M3 = b.p(d2), m3 = O(r2), v2 = (m3.utcOffset() - this.utcOffset()) * e, g2 = this - m3, D2 = function() {
            return b.m(y2, m3);
          };
          switch (M3) {
            case h:
              $2 = D2() / 12;
              break;
            case c:
              $2 = D2();
              break;
            case f:
              $2 = D2() / 3;
              break;
            case o:
              $2 = (g2 - v2) / 6048e5;
              break;
            case a:
              $2 = (g2 - v2) / 864e5;
              break;
            case u:
              $2 = g2 / n;
              break;
            case s:
              $2 = g2 / e;
              break;
            case i:
              $2 = g2 / t;
              break;
            default:
              $2 = g2;
          }
          return l2 ? $2 : b.a($2);
        }, m2.daysInMonth = function() {
          return this.endOf(c).$D;
        }, m2.$locale = function() {
          return D[this.$L];
        }, m2.locale = function(t2, e2) {
          if (!t2) return this.$L;
          var n2 = this.clone(), r2 = w(t2, e2, true);
          return r2 && (n2.$L = r2), n2;
        }, m2.clone = function() {
          return b.w(this.$d, this);
        }, m2.toDate = function() {
          return new Date(this.valueOf());
        }, m2.toJSON = function() {
          return this.isValid() ? this.toISOString() : null;
        }, m2.toISOString = function() {
          return this.$d.toISOString();
        }, m2.toString = function() {
          return this.$d.toUTCString();
        }, M2;
      }(), k = _.prototype;
      return O.prototype = k, [["$ms", r], ["$s", i], ["$m", s], ["$H", u], ["$W", a], ["$M", c], ["$y", h], ["$D", d]].forEach(function(t2) {
        k[t2[1]] = function(e2) {
          return this.$g(e2, t2[0], t2[1]);
        };
      }), O.extend = function(t2, e2) {
        return t2.$i || (t2(e2, _, O), t2.$i = true), O;
      }, O.locale = w, O.isDayjs = S, O.unix = function(t2) {
        return O(1e3 * t2);
      }, O.en = D[g], O.Ls = D, O.p = {}, O;
    });
  })(dayjs_min$1);
  return dayjs_min$1.exports;
}
var dayjs_minExports = requireDayjs_min();
const dayjs = /* @__PURE__ */ getDefaultExportFromCjs(dayjs_minExports);
const _sfc_main = {
  name: "Parameter",
  components: { DatePicker, Multiselect: script },
  props: {
    parameterObject: {
      type: Object,
      required: true
    },
    multiselectOptions: {
      type: Object,
      required: false,
      default: () => {
        return {
          options: [],
          noOptions: false,
          multiple: true,
          taggable: false,
          searchable: true,
          internalSearch: true,
          asyncRoute: "",
          optionsLimit: 100,
          optionsPlaceholder: "COM_EMUNDUS_MULTISELECT_ADDKEYWORDS",
          selectLabel: "PRESS_ENTER_TO_SELECT",
          selectGroupLabel: "PRESS_ENTER_TO_SELECT_GROUP",
          selectedLabel: "SELECTED",
          deselectedLabel: "PRESS_ENTER_TO_REMOVE",
          deselectGroupLabel: "PRESS_ENTER_TO_DESELECT_GROUP",
          noOptionsText: "COM_EMUNDUS_MULTISELECT_NOKEYWORDS",
          noResultsText: "COM_EMUNDUS_MULTISELECT_NORESULTS",
          // Can add tag validations (ex. email, phone, regex)
          tagValidations: [],
          tagRegex: ""
        };
      }
    },
    helpTextType: {
      type: String,
      required: false,
      default: "icon"
    },
    asyncAttributes: {
      type: Array,
      required: false
    },
    componentsProps: {
      type: Object,
      required: false
    }
  },
  emits: ["valueUpdated", "needSaving"],
  data() {
    return {
      initValue: null,
      value: null,
      valueSecondary: null,
      parameter: {},
      parameterSecondary: {},
      multiOptions: [],
      isLoading: false,
      errors: {},
      abortController: null,
      debounceTimeout: null,
      actualLanguage: "fr-FR"
    };
  },
  async created() {
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getShortLang;
    this.parameter = this.parameterObject;
    if (this.parameter.type === "multiselect") {
      if (this.$props.multiselectOptions.asyncRoute) {
        await this.asyncFind("");
      } else {
        this.multiOptions = this.$props.multiselectOptions.options;
      }
      if (!this.multiselectOptions.multiple) {
        this.value = this.multiOptions.find(
          (option) => option[this.$props.multiselectOptions.trackBy] == this.parameter.value
        );
      } else {
        if (this.parameter.value && this.parameter.value.length > 0 && typeof this.parameter.value[0] !== "object") {
          this.value = this.multiOptions.filter(
            (option) => this.parameter.value.includes(option[this.$props.multiselectOptions.trackBy])
          );
        } else {
          this.value = this.parameter.value;
        }
      }
      if (!this.value) {
        this.value = [];
      }
    } else if (this.parameter) {
      this.value = this.parameter.value;
    }
    if (this.parameter.splitField) {
      if (this.value) {
        let splitValue = this.value.split(this.parameter.splitChar);
        this.value = splitValue[0];
        this.valueSecondary = splitValue[1];
      }
      this.parameterSecondary = reactive({ ...this.parameter });
      if (this.parameter.secondParameterType) {
        this.parameterSecondary.type = this.parameter.secondParameterType;
      }
      if (this.parameter.secondParameterOptions) {
        this.parameterSecondary.options = this.parameter.secondParameterOptions;
      }
      this.parameterSecondary.splitField = false;
      this.parameterSecondary.hideLabel = true;
      if (this.parameter.secondParameterDefault && (!this.valueSecondary || this.valueSecondary === "")) {
        this.parameterSecondary.value = this.parameter.secondParameterDefault;
      } else {
        this.parameterSecondary.value = this.valueSecondary;
      }
    }
    this.initValue = this.value;
  },
  methods: {
    displayHelp(message) {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_SWAL_HELP_TITLE"),
        html: this.translate(message),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action"
        }
      });
    },
    // MULTISELECT
    addOption(newOption) {
      if (this.multiselectOptions.taggable) {
        if (this.multiOptions.find((option2) => option2.name === newOption)) {
          return false;
        }
        if (this.$props.multiselectOptions.tagValidations.length > 0) {
          let valid = false;
          this.$props.multiselectOptions.tagValidations.forEach((validation) => {
            switch (validation) {
              case "email":
                valid = this.validateEmail(newOption);
                break;
              case "regex":
                valid = new RegExp(this.$props.multiselectOptions.tagRegex).test(newOption);
                break;
            }
          });
          if (!valid) {
            return false;
          }
        }
        const option = {
          name: newOption,
          code: newOption
        };
        this.multiOptions.push(option);
        this.value.push(option);
      }
    },
    checkAddOption(event) {
      if (this.multiselectOptions.taggable) {
        event.preventDefault();
        let added = this.addOption(event.srcElement.value);
        if (!added) {
          event.srcElement.value = "";
        }
      }
    },
    checkComma(event) {
      if (this.$props.multiselectOptions.tagValidations.includes("email") && event && event.key === "," && this.multiselectOptions.taggable) {
        this.addOption(event.srcElement.value.replace(",", ""));
      }
    },
    async asyncFind(search_query) {
      if (this.$props.multiselectOptions.asyncRoute) {
        return new Promise((resolve, reject) => {
          if (this.abortController) {
            this.abortController.abort();
          }
          this.abortController = new AbortController();
          const signal = this.abortController.signal;
          clearTimeout(this.debounceTimeout);
          this.debounceTimeout = setTimeout(() => {
            this.isLoading = true;
            let data = {
              search_query,
              limit: this.$props.multiselectOptions.optionsLimit,
              properties: this.$props.asyncAttributes
            };
            settingsService.getAsyncOptions(this.$props.multiselectOptions.asyncRoute, data, { signal }).then((response) => {
              this.multiOptions = response.data;
              this.isLoading = false;
              resolve(true);
            });
          }, 500);
        });
      }
    },
    // VALIDATIONS
    validate() {
      if (this.parameter.value === "" && this.parameter.optional === true) {
        delete this.errors[this.parameter.param];
        return true;
      } else if (this.parameter.value === "") {
        this.errors[this.parameter.param] = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL";
        return false;
      } else {
        if (this.parameter.type === "email") {
          if (!this.validateEmail(this.parameter.value)) {
            this.errors[this.parameter.param] = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NO";
            return false;
          }
        }
        delete this.errors[this.parameter.param];
        return true;
      }
    },
    checkValue(parameter) {
      if (parameter.type === "number") {
        if (this.value > parameter.max) {
          this.value = parameter.max;
        }
      } else {
        this.validate(parameter);
      }
    },
    clearPassword(parameter) {
      if (parameter.type === "password") {
        this.value = "";
      }
    },
    regroupValue(parameter) {
      this.valueSecondary = parameter.value;
    },
    validateEmail(email) {
      let res = /^[\w.-]+@([\w-]+\.)+[\w-]{2,4}$/;
      return res.test(email);
    },
    formatDateForDisplay(date) {
      if (!date) return "00:00";
      return date.split("-").reverse().join("/");
    },
    bookingSlotIdUpdated(value) {
      this.$emit("valueUpdated", value);
    }
    //
  },
  watch: {
    value: {
      handler: function(val, oldVal) {
        if (this.parameter.type !== "multiselect" || this.parameter.type === "multiselect" && !this.multiselectOptions.taggable) {
          this.parameter.value = val;
        }
        if (this.parameter.splitField) {
          this.parameter.concatValue = val + this.parameter.splitChar + this.valueSecondary;
        }
        this.$emit("valueUpdated", this.parameter, oldVal, val);
        if (val !== oldVal && val !== this.initValue) {
          let valid = true;
          if (["text", "email", "number", "password", "textarea"].includes(this.parameter.type)) {
            valid = this.validate();
          }
          this.$emit("needSaving", true, this.parameter, valid);
        }
        if (val == this.initValue) {
          this.$emit("needSaving", false, this.parameter, true);
        }
      },
      deep: true
    },
    valueSecondary: {
      handler: function(val, oldVal) {
        this.parameter.concatValue = this.value + this.parameter.splitChar + val;
        if (val !== oldVal && (this.parameter.param === "slot_can_book_until" && this.parameter.label === "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL" || this.parameter.param === "slot_can_cancel_until" && this.parameter.label === "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_CANCEL_UNTIL")) {
          if (val === "days" && oldVal !== null) {
            this.value = "";
            this.parameter.concatValue = this.value + this.parameter.splitChar + val;
            this.parameter.type = "text";
          } else if (val === "date") {
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
            this.value = typeof this.value === "string" && dateRegex.test(this.value) && !isNaN(new Date(this.value).getTime()) ? this.value : (/* @__PURE__ */ new Date()).toISOString().split("T")[0];
            this.parameter.concatValue = this.value + this.parameter.splitChar + val;
            this.parameter.type = "date";
          }
        }
      },
      deep: true
    }
  },
  computed: {
    EventBooking() {
      return EventBooking;
    },
    isInput() {
      return ["text", "email", "number", "password"].includes(this.parameter.type) && this.parameter.displayed && this.parameter.editable !== "semi";
    },
    paramId() {
      return "param_" + this.parameter.param + "_" + Math.floor(Math.random() * 100);
    },
    paramName() {
      return "param_" + this.parameter.param + "[]";
    },
    formattedValue: {
      get() {
        if (this.parameter.type === "date") {
          let today = (/* @__PURE__ */ new Date()).toISOString().split("T")[0];
          let dateValue = typeof this.value === "string" ? this.value : today;
          return dateValue && dateValue < today ? today : dateValue;
        } else {
          return this.value;
        }
      },
      set(newValue) {
        if (this.parameter.type === "date") {
          newValue = dayjs(newValue).format("YYYY-MM-DD");
          this.value = newValue.split("/").reverse().join("-");
        } else if (this.parameter.type === "time") {
          if (newValue !== null) {
            const oldDate = new Date(this.value);
            const newDate = new Date(newValue);
            oldDate.setHours(newDate.getHours(), newDate.getMinutes());
            this.value = oldDate;
          } else {
            const oldDate = new Date(this.value);
            oldDate.setHours(0, 0, 0, 0);
            this.value = oldDate;
          }
        } else {
          this.value = newValue;
        }
      }
    }
  }
};
const _hoisted_1 = ["for"];
const _hoisted_2 = {
  key: 0,
  class: "tw-ml-1 tw-text-red-600"
};
const _hoisted_3 = {
  key: 1,
  class: "tw-text-base tw-text-neutral-600"
};
const _hoisted_4 = ["innerHTML"];
const _hoisted_5 = { key: 0 };
const _hoisted_6 = ["title"];
const _hoisted_7 = ["id", "disabled"];
const _hoisted_8 = ["value"];
const _hoisted_9 = ["id", "rows", "placeholder", "maxlength", "readonly"];
const _hoisted_10 = { key: 4 };
const _hoisted_11 = {
  "data-toggle": "buttons",
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_12 = ["for"];
const _hoisted_13 = ["name", "id", "checked"];
const _hoisted_14 = ["for"];
const _hoisted_15 = ["name", "id", "checked"];
const _hoisted_16 = { key: 5 };
const _hoisted_17 = {
  "data-toggle": "radio_buttons",
  class: "tw-grid tw-grid-cols-1 tw-gap-4 md:tw-grid-cols-2 lg:tw-grid-cols-2"
};
const _hoisted_18 = ["name", "id", "value", "checked"];
const _hoisted_19 = ["for"];
const _hoisted_20 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_21 = ["src", "alt"];
const _hoisted_22 = {
  key: 6,
  class: "tw-flex tw-items-center"
};
const _hoisted_23 = { class: "em-toggle" };
const _hoisted_24 = ["id"];
const _hoisted_25 = ["for"];
const _hoisted_26 = {
  key: 0,
  class: "material-symbols-outlined tw-mr-1 tw-text-neutral-900"
};
const _hoisted_27 = ["type", "max", "placeholder", "id", "maxlength", "readonly"];
const _hoisted_28 = ["value", "id"];
const _hoisted_29 = { key: 10 };
const _hoisted_30 = {
  key: 11,
  class: "tw-ml-2"
};
const _hoisted_31 = ["id"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  const _component_DatePicker = resolveComponent("DatePicker");
  const _component_Parameter = resolveComponent("Parameter", true);
  return openBlock(), createElementBlock("div", null, [
    $data.parameter.hideLabel !== true ? (openBlock(), createElementBlock("label", {
      key: 0,
      for: $options.paramId,
      class: normalizeClass(["tw-flex tw-items-end tw-font-semibold", $data.parameter.helptext && $props.helpTextType === "above" ? "tw-mb-0" : ""])
    }, [
      createTextVNode(toDisplayString(_ctx.translate($data.parameter.label)) + " ", 1),
      $data.parameter.optional !== true ? (openBlock(), createElementBlock("span", _hoisted_2, "*")) : createCommentVNode("", true),
      $data.parameter.helptext && $props.helpTextType === "icon" ? (openBlock(), createElementBlock("span", {
        key: 1,
        class: "material-symbols-outlined tw-ml-1 tw-cursor-pointer tw-text-neutral-600",
        onClick: _cache[0] || (_cache[0] = ($event) => $options.displayHelp($data.parameter.helptext))
      }, "help_outline")) : createCommentVNode("", true)
    ], 10, _hoisted_1)) : createCommentVNode("", true),
    $data.parameter.helptext && $props.helpTextType === "above" ? (openBlock(), createElementBlock("span", _hoisted_3, [
      createBaseVNode("span", {
        innerHTML: _ctx.translate($data.parameter.helptext)
      }, null, 8, _hoisted_4)
    ])) : createCommentVNode("", true),
    createBaseVNode("div", {
      name: "input-field",
      class: normalizeClass(["tw-flex tw-items-center", {
        "input-split-field": $data.parameter.splitField,
        "input-split-field-select": $data.parameter.splitField && $data.parameter.secondParameterType === "select",
        "tw-gap-2": $data.parameter.splitField && $data.parameter.secondParameterType !== "select"
      }])
    }, [
      $data.parameter.icon ? (openBlock(), createElementBlock("div", _hoisted_5, [
        createBaseVNode("span", {
          title: _ctx.translate($data.parameter.label),
          class: "material-symbols-outlined tw-mr-2 tw-text-neutral-900"
        }, toDisplayString($data.parameter.icon), 9, _hoisted_6)
      ])) : createCommentVNode("", true),
      $data.parameter.type === "select" ? withDirectives((openBlock(), createElementBlock("select", {
        key: 1,
        class: normalizeClass(["dropdown-toggle w-select !tw-mb-0 tw-min-w-[30%]", [
          $data.errors[$data.parameter.param] ? "tw-rounded-lg !tw-border-red-500" : "",
          $data.parameter.secondParameterType === "select" ? "tw-w-auto" : "tw-w-full"
        ]]),
        id: $options.paramId,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.value = $event),
        disabled: $data.parameter.editable === false
      }, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.parameter.options, (option) => {
          return openBlock(), createElementBlock("option", {
            key: option.value,
            value: option.value
          }, toDisplayString(_ctx.translate(option.label)), 9, _hoisted_8);
        }), 128))
      ], 10, _hoisted_7)), [
        [vModelSelect, $data.value]
      ]) : $data.parameter.type === "multiselect" ? (openBlock(), createBlock(_component_multiselect, {
        id: $options.paramId,
        modelValue: $data.value,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.value = $event),
        class: normalizeClass([$props.multiselectOptions.noOptions ? "no-options" : "", "tw-cursor-pointer"]),
        label: $props.multiselectOptions.label ? $props.multiselectOptions.label : "name",
        "track-by": $props.multiselectOptions.trackBy ? $props.multiselectOptions.trackBy : "code",
        options: $data.multiOptions,
        "options-limit": $props.multiselectOptions.optionsLimit ? $props.multiselectOptions.optionsLimit : 100,
        multiple: $props.multiselectOptions.multiple ? $props.multiselectOptions.multiple : false,
        taggable: $props.multiselectOptions.taggable ? $props.multiselectOptions.taggable : false,
        placeholder: _ctx.translate($data.parameter.placeholder),
        searchable: $props.multiselectOptions.searchable ? $props.multiselectOptions.searchable : true,
        tagPlaceholder: _ctx.translate($props.multiselectOptions.optionsPlaceholder),
        key: $options.paramId,
        selectLabel: _ctx.translate($props.multiselectOptions.selectLabel),
        selectGroupLabel: _ctx.translate($props.multiselectOptions.selectGroupLabel),
        selectedLabel: _ctx.translate($props.multiselectOptions.selectedLabel),
        "deselect-label": _ctx.translate($props.multiselectOptions.deselectedLabel),
        deselectGroupLabel: _ctx.translate($props.multiselectOptions.deselectGroupLabel),
        "preserve-search": true,
        "internal-search": $props.multiselectOptions.internalSearch ? $props.multiselectOptions.internalSearch : true,
        loading: $data.isLoading,
        onTag: $options.addOption,
        onKeyup: _cache[3] || (_cache[3] = ($event) => $options.checkComma($event)),
        onFocusout: _cache[4] || (_cache[4] = ($event) => $options.checkAddOption($event)),
        onSearchChange: $options.asyncFind
      }, {
        noOptions: withCtx(() => [
          createTextVNode(toDisplayString(_ctx.translate($props.multiselectOptions.noOptionsText)), 1)
        ]),
        noResult: withCtx(() => [
          createTextVNode(toDisplayString(_ctx.translate($props.multiselectOptions.noResultsText)), 1)
        ]),
        _: 1
      }, 8, ["id", "modelValue", "class", "label", "track-by", "options", "options-limit", "multiple", "taggable", "placeholder", "searchable", "tagPlaceholder", "selectLabel", "selectGroupLabel", "selectedLabel", "deselect-label", "deselectGroupLabel", "internal-search", "loading", "onTag", "onSearchChange"])) : $data.parameter.type === "textarea" ? withDirectives((openBlock(), createElementBlock("textarea", {
        key: 3,
        id: $options.paramId,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.value = $event),
        class: normalizeClass(["!mb-0", $data.errors[$data.parameter.param] ? "tw-rounded-lg !tw-border-red-500" : ""]),
        style: normalizeStyle({
          resize: $data.parameter.resize ? "vertical" : "none"
        }),
        rows: $data.parameter.rows ? $data.parameter.rows : 3,
        placeholder: _ctx.translate($data.parameter.placeholder),
        maxlength: $data.parameter.maxlength,
        readonly: $data.parameter.editable === false
      }, "			", 14, _hoisted_9)), [
        [vModelText, $data.value]
      ]) : $data.parameter.type === "yesno" ? (openBlock(), createElementBlock("div", _hoisted_10, [
        createBaseVNode("fieldset", _hoisted_11, [
          createBaseVNode("label", {
            for: $options.paramId + "_input_0",
            class: normalizeClass([[$data.value == 0 ? "tw-bg-red-700" : "tw-border-neutral-500 tw-bg-white hover:tw-border-red-700"], "tw-inline-flex tw-h-10 tw-w-60 tw-items-center tw-justify-center tw-gap-2.5 tw-rounded-lg tw-border tw-p-2.5"])
          }, [
            withDirectives(createBaseVNode("input", {
              "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.value = $event),
              type: "radio",
              class: "fabrikinput !tw-hidden",
              name: $options.paramName,
              id: $options.paramId + "_input_0",
              value: "0",
              checked: $data.value === 0
            }, null, 8, _hoisted_13), [
              [vModelRadio, $data.value]
            ]),
            createBaseVNode("span", {
              class: normalizeClass([$data.value == 0 ? "tw-text-white" : "tw-text-red-700"])
            }, toDisplayString(_ctx.translate("JNO")), 3)
          ], 10, _hoisted_12),
          createBaseVNode("label", {
            for: $options.paramId + "_input_1",
            class: normalizeClass([[$data.value == 1 ? "tw-bg-green-700" : "tw-border-neutral-500 tw-bg-white hover:tw-border-green-700"], "tw-inline-flex tw-h-10 tw-w-60 tw-items-center tw-justify-center tw-gap-2.5 tw-rounded-lg tw-border tw-p-2.5"])
          }, [
            withDirectives(createBaseVNode("input", {
              "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.value = $event),
              type: "radio",
              class: "fabrikinput !tw-hidden",
              name: $options.paramName,
              id: $options.paramId + "_input_1",
              value: "1",
              checked: $data.value === 1
            }, null, 8, _hoisted_15), [
              [vModelRadio, $data.value]
            ]),
            createBaseVNode("span", {
              class: normalizeClass([$data.value == 1 ? "tw-text-white" : "tw-text-green-700"])
            }, toDisplayString(_ctx.translate("JYES")), 3)
          ], 10, _hoisted_14)
        ])
      ])) : $data.parameter.type === "radiobutton" ? (openBlock(), createElementBlock("div", _hoisted_16, [
        createBaseVNode("fieldset", _hoisted_17, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.parameter.options, (option) => {
            return openBlock(), createElementBlock("div", {
              key: option.value,
              class: "fabrikgrid_radio"
            }, [
              withDirectives(createBaseVNode("input", {
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.value = $event),
                type: "radio",
                class: normalizeClass(["fabrikinput", $data.parameter.hideRadio ? "!tw-hidden" : ""]),
                name: $options.paramName,
                id: $options.paramId + "_input_" + option.value,
                value: option.value,
                checked: $data.value === option.value
              }, null, 10, _hoisted_18), [
                [vModelRadio, $data.value]
              ]),
              createBaseVNode("label", {
                for: $options.paramId + "_input_" + option.value
              }, [
                createBaseVNode("span", _hoisted_20, [
                  option.img ? (openBlock(), createElementBlock("img", {
                    key: 0,
                    src: "/images/emundus/icons/" + option.img,
                    alt: option.altImg,
                    style: { "width": "16px" }
                  }, null, 8, _hoisted_21)) : createCommentVNode("", true),
                  createTextVNode(" " + toDisplayString(_ctx.translate(option.label)), 1)
                ])
              ], 8, _hoisted_19)
            ]);
          }), 128))
        ])
      ])) : $data.parameter.type === "toggle" ? (openBlock(), createElementBlock("div", _hoisted_22, [
        createBaseVNode("div", _hoisted_23, [
          withDirectives(createBaseVNode("input", {
            type: "checkbox",
            "true-value": "1",
            "false-value": "0",
            class: "em-toggle-check",
            id: $options.paramId + "_input",
            "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => $data.value = $event)
          }, null, 8, _hoisted_24), [
            [vModelCheckbox, $data.value]
          ]),
          _cache[16] || (_cache[16] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
          _cache[17] || (_cache[17] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
        ]),
        createBaseVNode("label", {
          for: $options.paramId + "_input",
          class: "!tw-mb-0 tw-ml-2 tw-flex tw-cursor-pointer tw-items-center tw-font-bold"
        }, [
          $data.parameter.iconLabel ? (openBlock(), createElementBlock("span", _hoisted_26, toDisplayString($data.parameter.iconLabel), 1)) : createCommentVNode("", true),
          createTextVNode(" " + toDisplayString(_ctx.translate($data.parameter.label)), 1)
        ], 8, _hoisted_25)
      ])) : $options.isInput ? withDirectives((openBlock(), createElementBlock("input", {
        key: 7,
        type: $data.parameter.type,
        class: normalizeClass(["form-control !tw-mb-0 tw-min-w-[30%]", $data.errors[$data.parameter.param] ? "tw-rounded-lg !tw-border-red-500" : ""]),
        style: { "box-shadow": "none" },
        max: $data.parameter.type === "number" ? $data.parameter.max : null,
        min: void 0,
        placeholder: _ctx.translate($data.parameter.placeholder),
        id: $options.paramId,
        "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => $data.value = $event),
        maxlength: $data.parameter.maxlength,
        readonly: $data.parameter.editable === false,
        onChange: _cache[11] || (_cache[11] = withModifiers(($event) => $options.checkValue($data.parameter), ["self"])),
        onFocusin: _cache[12] || (_cache[12] = ($event) => $options.clearPassword($data.parameter))
      }, null, 42, _hoisted_27)), [
        [vModelDynamic, $data.value]
      ]) : $data.parameter.type === "datetime" || $data.parameter.type === "date" || $data.parameter.type === "time" ? (openBlock(), createBlock(_component_DatePicker, {
        key: 8,
        id: $options.paramId,
        modelValue: $options.formattedValue,
        "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => $options.formattedValue = $event),
        keepVisibleOnInput: true,
        popover: { visibility: "focus", placement: "right" },
        rules: { minutes: { interval: 5 } },
        mode: $data.parameter.type ? $data.parameter.type : "dateTime",
        is24hr: "",
        "hide-time-header": "",
        "title-position": "left",
        "input-debounce": 500,
        locale: $data.actualLanguage
      }, {
        default: withCtx(({ inputValue, inputEvents }) => [
          createBaseVNode("input", mergeProps({
            value: $options.formatDateForDisplay(inputValue)
          }, toHandlers(inputEvents, true), {
            class: "form-control fabrikinput tw-w-full",
            style: { "box-shadow": "none" },
            id: $options.paramId + "_input"
          }), null, 16, _hoisted_28)
        ]),
        _: 1
      }, 8, ["id", "modelValue", "mode", "locale"])) : $data.parameter.type === "component" ? (openBlock(), createBlock(resolveDynamicComponent($options.EventBooking), {
        key: 9,
        modelValue: $data.value,
        "onUpdate:modelValue": _cache[14] || (_cache[14] = ($event) => $data.value = $event),
        componentsProps: this.$props.componentsProps,
        onValueUpdated: $options.bookingSlotIdUpdated
      }, null, 40, ["modelValue", "componentsProps", "onValueUpdated"])) : createCommentVNode("", true),
      $data.parameter.splitField ? (openBlock(), createElementBlock("span", _hoisted_29, toDisplayString($data.parameter.splitChar), 1)) : createCommentVNode("", true),
      $data.parameter.endText ? (openBlock(), createElementBlock("span", _hoisted_30, toDisplayString(_ctx.translate($data.parameter.endText)), 1)) : createCommentVNode("", true),
      $data.parameter.splitField && $data.parameterSecondary ? (openBlock(), createBlock(_component_Parameter, {
        key: 12,
        "parameter-object": $data.parameterSecondary,
        "multiselect-options": $props.multiselectOptions,
        onValueUpdated: _cache[15] || (_cache[15] = ($event) => $options.regroupValue($data.parameterSecondary))
      }, null, 8, ["parameter-object", "multiselect-options"])) : createCommentVNode("", true)
    ], 2),
    $data.errors[$data.parameter.param] && !["yesno", "toggle"].includes($data.parameter.type) && $data.parameter.displayed ? (openBlock(), createElementBlock("div", {
      key: 2,
      class: normalizeClass(["tw-absolute tw-mt-1 tw-min-h-[24px] tw-text-red-600", $data.errors[$data.parameter.param] ? "tw-opacity-100" : "tw-opacity-0"]),
      id: "error-message-" + $data.parameter.param
    }, toDisplayString(_ctx.translate($data.errors[$data.parameter.param])), 11, _hoisted_31)) : createCommentVNode("", true)
  ]);
}
const Parameter = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Parameter as P,
  dayjs as d
};
