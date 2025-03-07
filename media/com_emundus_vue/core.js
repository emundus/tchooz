import { _ as _export_sfc, u as useGlobalStore, o as openBlock, c as createElementBlock, a as createBaseVNode, t as toDisplayString, e as createCommentVNode, n as normalizeStyle, d as normalizeClass, a7 as defineComponent, a8 as isReactive, a9 as h$4, m as Teleport, F as Fragment } from "./app_emundus.js";
const _sfc_main = {
  name: "EventDay",
  props: {
    calendarEvent: {
      type: Object,
      required: true
    },
    view: {
      type: String,
      required: true
    },
    editAction: {
      type: String
    },
    preset: {
      type: String,
      default: "basic"
    }
  },
  data() {
    return {
      actualLanguage: "fr-FR",
      eventStartDate: null,
      eventEndDate: null,
      eventDay: ""
    };
  },
  created() {
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getCurrentLang;
    this.eventStartDate = new Date(this.calendarEvent.start);
    this.eventEndDate = new Date(this.calendarEvent.end);
  },
  methods: {},
  watch: {
    calendarEvent: {
      handler() {
        this.eventStartDate = new Date(this.calendarEvent.start);
        this.eventEndDate = new Date(this.calendarEvent.end);
      },
      deep: true
    }
  },
  computed: {
    eventPeople() {
      let people = this.calendarEvent.people;
      if (Array.isArray(this.calendarEvent.people)) {
        people = this.calendarEvent.people.join(", ");
      }
      return people;
    },
    eventHours() {
      return this.eventStartDate.toLocaleTimeString(this.actualLanguage, {
        hour: "2-digit",
        minute: "2-digit"
      }) + " - " + this.eventEndDate.toLocaleTimeString(this.actualLanguage, { hour: "2-digit", minute: "2-digit" });
    },
    textColor() {
      if (this.calendarEvent.color) {
        const color = this.calendarEvent.color;
        const r2 = parseInt(color.substr(1, 2), 16);
        const g2 = parseInt(color.substr(3, 2), 16);
        const b2 = parseInt(color.substr(5, 2), 16);
        const brightness = Math.round((r2 * 299 + g2 * 587 + b2 * 114) / 1e3);
        return brightness > 125 ? "#000" : "#fff";
      } else {
        return "#000";
      }
    }
  }
};
const _hoisted_1 = { key: 0 };
const _hoisted_2 = {
  key: 1,
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_3 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_4 = {
  key: 2,
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_5 = {
  key: 3,
  class: "tw-flex tw-items-center tw-gap-2"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(["tw-flex tw-h-full tw-gap-2", {
      "tw-flex-col tw-p-2": $props.view === "week" || $props.preset === "full",
      "tw-items-center tw-flex-row tw-px-2 tw-border-2 tw-border-neutral-300": $props.view === "day" && $props.preset !== "full",
      "tw-border-s-neutral-300 sx__stripped_event": $props.calendarEvent.booked_count >= $props.calendarEvent.availabilities_count
    }]),
    style: normalizeStyle({ backgroundColor: $props.calendarEvent.color, color: $options.textColor })
  }, [
    $props.calendarEvent.title ? (openBlock(), createElementBlock("div", _hoisted_1, [
      createBaseVNode("span", null, [
        createBaseVNode("strong", null, toDisplayString($props.calendarEvent.title), 1)
      ])
    ])) : createCommentVNode("", true),
    $props.preset === "full" && $props.calendarEvent.people ? (openBlock(), createElementBlock("div", _hoisted_2, [
      createBaseVNode("span", {
        class: "material-symbols-outlined tw-text-neutral-900",
        style: normalizeStyle({ color: $options.textColor })
      }, "group", 4),
      createBaseVNode("p", {
        class: "tw-text-sm",
        style: normalizeStyle({ color: $options.textColor })
      }, toDisplayString($options.eventPeople), 5)
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_3, [
      $props.preset === "full" ? (openBlock(), createElementBlock("span", {
        key: 0,
        class: "material-symbols-outlined tw-text-neutral-900",
        style: normalizeStyle({ color: $options.textColor })
      }, "schedule", 4)) : createCommentVNode("", true),
      createBaseVNode("p", {
        class: "tw-text-sm",
        style: normalizeStyle({ color: $options.textColor })
      }, toDisplayString($options.eventHours), 5)
    ]),
    $props.preset === "full" && $props.calendarEvent.room && $props.calendarEvent.location ? (openBlock(), createElementBlock("div", _hoisted_4, [
      createBaseVNode("span", {
        class: "material-symbols-outlined tw-text-neutral-900",
        style: normalizeStyle({ color: $options.textColor })
      }, "room", 4),
      createBaseVNode("p", {
        class: "tw-text-sm",
        style: normalizeStyle({ color: $options.textColor })
      }, toDisplayString($props.calendarEvent.location), 5)
    ])) : createCommentVNode("", true),
    $props.preset === "full" && $props.calendarEvent.availabilities_count ? (openBlock(), createElementBlock("div", _hoisted_5, [
      createBaseVNode("span", {
        class: "material-symbols-outlined tw-text-neutral-900",
        style: normalizeStyle({ color: $options.textColor })
      }, "groups", 4),
      createBaseVNode("p", {
        class: "tw-text-sm",
        style: normalizeStyle({ color: $options.textColor })
      }, toDisplayString($props.calendarEvent.booked_count) + " / " + toDisplayString($props.calendarEvent.availabilities_count), 5)
    ])) : createCommentVNode("", true)
  ], 6);
}
const EventDay = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
const Ht = (e2, t2) => (n2, o2) => {
  const i2 = {
    Component: h$4(t2, o2),
    wrapperElement: n2
  };
  e2(i2);
};
class zt extends Error {
  constructor(t2) {
    super(t2), this.name = "[Schedule-X reactivity error]";
  }
}
const Ji = defineComponent({
  name: "ScheduleXCalendar",
  props: {
    calendarApp: {
      type: Object,
      required: true
    },
    customComponents: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      elId: "sx" + Math.random().toString(36).substr(2, 9),
      customComponentsMeta: []
    };
  },
  mounted() {
    if (isReactive(this.calendarApp))
      throw new zt("calendarApp cannot be saved in a ref. Since this causes deep reactivity, it destroys the calendars internal reactivity. Save in a normal const or shallowRef");
    const e2 = {
      ...this.customComponents,
      ...this.$slots
    };
    for (const [t2, n2] of Object.entries(e2))
      this.calendarApp._setCustomComponentFn(t2, Ht(this.setCustomComponentMeta, n2));
    this.calendarApp.render(document.getElementById(this.elId));
  },
  methods: {
    setCustomComponentMeta(e2) {
      if (!(e2.wrapperElement instanceof HTMLElement))
        return;
      const n2 = ({ wrapperElement: a2 }) => a2 instanceof HTMLElement, o2 = [
        ...this.customComponentsMeta.filter(n2)
      ], i2 = e2.wrapperElement.dataset.ccid, r2 = o2.find(({ wrapperElement: a2 }) => a2.dataset.ccid === i2);
      r2 && o2.splice(o2.indexOf(r2), 1), this.customComponentsMeta = [...o2, e2];
    }
  },
  render() {
    const e2 = this.customComponentsMeta.map(({ Component: t2, wrapperElement: n2 }) => h$4(Teleport, { to: n2 }, t2));
    return h$4("div", {
      id: this.elId,
      class: "sx-vue-calendar-wrapper"
    }, h$4(Fragment, {}, e2));
  }
});
var pe, v$3, lt, ut, V$2, We, dt, Pe, ct, q$2 = {}, _t = [], Kt = /acit|ex(?:s|g|n|p|$)|rph|grid|ows|mnc|ntw|ine[ch]|zoo|^ord|itera/i, fe = Array.isArray;
function A$2(e2, t2) {
  for (var n2 in t2) e2[n2] = t2[n2];
  return e2;
}
function vt(e2) {
  var t2 = e2.parentNode;
  t2 && t2.removeChild(e2);
}
function j$3(e2, t2, n2) {
  var o2, i2, r2, a2 = {};
  for (r2 in t2) r2 == "key" ? o2 = t2[r2] : r2 == "ref" ? i2 = t2[r2] : a2[r2] = t2[r2];
  if (arguments.length > 2 && (a2.children = arguments.length > 3 ? pe.call(arguments, 2) : n2), typeof e2 == "function" && e2.defaultProps != null) for (r2 in e2.defaultProps) a2[r2] === void 0 && (a2[r2] = e2.defaultProps[r2]);
  return se(e2, a2, o2, i2, null);
}
function se(e2, t2, n2, o2, i2) {
  var r2 = { type: e2, props: t2, key: n2, ref: o2, __k: null, __: null, __b: 0, __e: null, __d: void 0, __c: null, constructor: void 0, __v: i2 ?? ++lt, __i: -1, __u: 0 };
  return i2 == null && v$3.vnode != null && v$3.vnode(r2), r2;
}
function w$3(e2) {
  return e2.children;
}
function T$3(e2, t2) {
  this.props = e2, this.context = t2;
}
function H$2(e2, t2) {
  if (t2 == null) return e2.__ ? H$2(e2.__, e2.__i + 1) : null;
  for (var n2; t2 < e2.__k.length; t2++) if ((n2 = e2.__k[t2]) != null && n2.__e != null) return n2.__e;
  return typeof e2.type == "function" ? H$2(e2) : null;
}
function ht(e2) {
  var t2, n2;
  if ((e2 = e2.__) != null && e2.__c != null) {
    for (e2.__e = e2.__c.base = null, t2 = 0; t2 < e2.__k.length; t2++) if ((n2 = e2.__k[t2]) != null && n2.__e != null) {
      e2.__e = e2.__c.base = n2.__e;
      break;
    }
    return ht(e2);
  }
}
function Se(e2) {
  (!e2.__d && (e2.__d = true) && V$2.push(e2) && !ce.__r++ || We !== v$3.debounceRendering) && ((We = v$3.debounceRendering) || dt)(ce);
}
function ce() {
  var e2, t2, n2, o2, i2, r2, a2, s2, u2;
  for (V$2.sort(Pe); e2 = V$2.shift(); ) e2.__d && (t2 = V$2.length, o2 = void 0, r2 = (i2 = (n2 = e2).__v).__e, s2 = [], u2 = [], (a2 = n2.__P) && ((o2 = A$2({}, i2)).__v = i2.__v + 1, v$3.vnode && v$3.vnode(o2), Ce(a2, o2, i2, n2.__n, a2.ownerSVGElement !== void 0, 32 & i2.__u ? [r2] : null, s2, r2 ?? H$2(i2), !!(32 & i2.__u), u2), o2.__.__k[o2.__i] = o2, mt(s2, o2, u2), o2.__e != r2 && ht(o2)), V$2.length > t2 && V$2.sort(Pe));
  ce.__r = 0;
}
function pt(e2, t2, n2, o2, i2, r2, a2, s2, u2, d2, c2) {
  var l2, _2, h2, m2, f2, g2 = o2 && o2.__k || _t, y2 = t2.length;
  for (n2.__d = u2, Bt(n2, t2, g2), u2 = n2.__d, l2 = 0; l2 < y2; l2++) (h2 = n2.__k[l2]) != null && typeof h2 != "boolean" && typeof h2 != "function" && (_2 = h2.__i === -1 ? q$2 : g2[h2.__i] || q$2, h2.__i = l2, Ce(e2, h2, _2, i2, r2, a2, s2, u2, d2, c2), m2 = h2.__e, h2.ref && _2.ref != h2.ref && (_2.ref && Oe(_2.ref, null, h2), c2.push(h2.ref, h2.__c || m2, h2)), f2 == null && m2 != null && (f2 = m2), 65536 & h2.__u || _2.__k === h2.__k ? u2 = ft(h2, u2, e2) : typeof h2.type == "function" && h2.__d !== void 0 ? u2 = h2.__d : m2 && (u2 = m2.nextSibling), h2.__d = void 0, h2.__u &= -196609);
  n2.__d = u2, n2.__e = f2;
}
function Bt(e2, t2, n2) {
  var o2, i2, r2, a2, s2, u2 = t2.length, d2 = n2.length, c2 = d2, l2 = 0;
  for (e2.__k = [], o2 = 0; o2 < u2; o2++) (i2 = e2.__k[o2] = (i2 = t2[o2]) == null || typeof i2 == "boolean" || typeof i2 == "function" ? null : typeof i2 == "string" || typeof i2 == "number" || typeof i2 == "bigint" || i2.constructor == String ? se(null, i2, null, null, i2) : fe(i2) ? se(w$3, { children: i2 }, null, null, null) : i2.constructor === void 0 && i2.__b > 0 ? se(i2.type, i2.props, i2.key, i2.ref ? i2.ref : null, i2.__v) : i2) != null ? (i2.__ = e2, i2.__b = e2.__b + 1, s2 = Gt(i2, n2, a2 = o2 + l2, c2), i2.__i = s2, r2 = null, s2 !== -1 && (c2--, (r2 = n2[s2]) && (r2.__u |= 131072)), r2 == null || r2.__v === null ? (s2 == -1 && l2--, typeof i2.type != "function" && (i2.__u |= 65536)) : s2 !== a2 && (s2 === a2 + 1 ? l2++ : s2 > a2 ? c2 > u2 - a2 ? l2 += s2 - a2 : l2-- : l2 = s2 < a2 && s2 == a2 - 1 ? s2 - a2 : 0, s2 !== o2 + l2 && (i2.__u |= 65536))) : (r2 = n2[o2]) && r2.key == null && r2.__e && (r2.__e == e2.__d && (e2.__d = H$2(r2)), we(r2, r2, false), n2[o2] = null, c2--);
  if (c2) for (o2 = 0; o2 < d2; o2++) (r2 = n2[o2]) != null && !(131072 & r2.__u) && (r2.__e == e2.__d && (e2.__d = H$2(r2)), we(r2, r2));
}
function ft(e2, t2, n2) {
  var o2, i2;
  if (typeof e2.type == "function") {
    for (o2 = e2.__k, i2 = 0; o2 && i2 < o2.length; i2++) o2[i2] && (o2[i2].__ = e2, t2 = ft(o2[i2], t2, n2));
    return t2;
  }
  return e2.__e != t2 && (n2.insertBefore(e2.__e, t2 || null), t2 = e2.__e), t2 && t2.nextSibling;
}
function _e(e2, t2) {
  return t2 = t2 || [], e2 == null || typeof e2 == "boolean" || (fe(e2) ? e2.some(function(n2) {
    _e(n2, t2);
  }) : t2.push(e2)), t2;
}
function Gt(e2, t2, n2, o2) {
  var i2 = e2.key, r2 = e2.type, a2 = n2 - 1, s2 = n2 + 1, u2 = t2[n2];
  if (u2 === null || u2 && i2 == u2.key && r2 === u2.type) return n2;
  if (o2 > (u2 != null && !(131072 & u2.__u) ? 1 : 0)) for (; a2 >= 0 || s2 < t2.length; ) {
    if (a2 >= 0) {
      if ((u2 = t2[a2]) && !(131072 & u2.__u) && i2 == u2.key && r2 === u2.type) return a2;
      a2--;
    }
    if (s2 < t2.length) {
      if ((u2 = t2[s2]) && !(131072 & u2.__u) && i2 == u2.key && r2 === u2.type) return s2;
      s2++;
    }
  }
  return -1;
}
function Ve(e2, t2, n2) {
  t2[0] === "-" ? e2.setProperty(t2, n2 ?? "") : e2[t2] = n2 == null ? "" : typeof n2 != "number" || Kt.test(t2) ? n2 : n2 + "px";
}
function ie(e2, t2, n2, o2, i2) {
  var r2;
  e: if (t2 === "style") if (typeof n2 == "string") e2.style.cssText = n2;
  else {
    if (typeof o2 == "string" && (e2.style.cssText = o2 = ""), o2) for (t2 in o2) n2 && t2 in n2 || Ve(e2.style, t2, "");
    if (n2) for (t2 in n2) o2 && n2[t2] === o2[t2] || Ve(e2.style, t2, n2[t2]);
  }
  else if (t2[0] === "o" && t2[1] === "n") r2 = t2 !== (t2 = t2.replace(/(PointerCapture)$|Capture$/, "$1")), t2 = t2.toLowerCase() in e2 ? t2.toLowerCase().slice(2) : t2.slice(2), e2.l || (e2.l = {}), e2.l[t2 + r2] = n2, n2 ? o2 ? n2.u = o2.u : (n2.u = Date.now(), e2.addEventListener(t2, r2 ? Ie : Ue, r2)) : e2.removeEventListener(t2, r2 ? Ie : Ue, r2);
  else {
    if (i2) t2 = t2.replace(/xlink(H|:h)/, "h").replace(/sName$/, "s");
    else if (t2 !== "width" && t2 !== "height" && t2 !== "href" && t2 !== "list" && t2 !== "form" && t2 !== "tabIndex" && t2 !== "download" && t2 !== "rowSpan" && t2 !== "colSpan" && t2 !== "role" && t2 in e2) try {
      e2[t2] = n2 ?? "";
      break e;
    } catch {
    }
    typeof n2 == "function" || (n2 == null || n2 === false && t2[4] !== "-" ? e2.removeAttribute(t2) : e2.setAttribute(t2, n2));
  }
}
function Ue(e2) {
  var t2 = this.l[e2.type + false];
  if (e2.t) {
    if (e2.t <= t2.u) return;
  } else e2.t = Date.now();
  return t2(v$3.event ? v$3.event(e2) : e2);
}
function Ie(e2) {
  return this.l[e2.type + true](v$3.event ? v$3.event(e2) : e2);
}
function Ce(e2, t2, n2, o2, i2, r2, a2, s2, u2, d2) {
  var c2, l2, _2, h2, m2, f2, g2, y2, b2, $2, te, B2, Fe, ne, De, Y2 = t2.type;
  if (t2.constructor !== void 0) return null;
  128 & n2.__u && (u2 = !!(32 & n2.__u), r2 = [s2 = t2.__e = n2.__e]), (c2 = v$3.__b) && c2(t2);
  e: if (typeof Y2 == "function") try {
    if (y2 = t2.props, b2 = (c2 = Y2.contextType) && o2[c2.__c], $2 = c2 ? b2 ? b2.props.value : c2.__ : o2, n2.__c ? g2 = (l2 = t2.__c = n2.__c).__ = l2.__E : ("prototype" in Y2 && Y2.prototype.render ? t2.__c = l2 = new Y2(y2, $2) : (t2.__c = l2 = new T$3(y2, $2), l2.constructor = Y2, l2.render = Zt), b2 && b2.sub(l2), l2.props = y2, l2.state || (l2.state = {}), l2.context = $2, l2.__n = o2, _2 = l2.__d = true, l2.__h = [], l2._sb = []), l2.__s == null && (l2.__s = l2.state), Y2.getDerivedStateFromProps != null && (l2.__s == l2.state && (l2.__s = A$2({}, l2.__s)), A$2(l2.__s, Y2.getDerivedStateFromProps(y2, l2.__s))), h2 = l2.props, m2 = l2.state, l2.__v = t2, _2) Y2.getDerivedStateFromProps == null && l2.componentWillMount != null && l2.componentWillMount(), l2.componentDidMount != null && l2.__h.push(l2.componentDidMount);
    else {
      if (Y2.getDerivedStateFromProps == null && y2 !== h2 && l2.componentWillReceiveProps != null && l2.componentWillReceiveProps(y2, $2), !l2.__e && (l2.shouldComponentUpdate != null && l2.shouldComponentUpdate(y2, l2.__s, $2) === false || t2.__v === n2.__v)) {
        for (t2.__v !== n2.__v && (l2.props = y2, l2.state = l2.__s, l2.__d = false), t2.__e = n2.__e, t2.__k = n2.__k, t2.__k.forEach(function(oe) {
          oe && (oe.__ = t2);
        }), te = 0; te < l2._sb.length; te++) l2.__h.push(l2._sb[te]);
        l2._sb = [], l2.__h.length && a2.push(l2);
        break e;
      }
      l2.componentWillUpdate != null && l2.componentWillUpdate(y2, l2.__s, $2), l2.componentDidUpdate != null && l2.__h.push(function() {
        l2.componentDidUpdate(h2, m2, f2);
      });
    }
    if (l2.context = $2, l2.props = y2, l2.__P = e2, l2.__e = false, B2 = v$3.__r, Fe = 0, "prototype" in Y2 && Y2.prototype.render) {
      for (l2.state = l2.__s, l2.__d = false, B2 && B2(t2), c2 = l2.render(l2.props, l2.state, l2.context), ne = 0; ne < l2._sb.length; ne++) l2.__h.push(l2._sb[ne]);
      l2._sb = [];
    } else do
      l2.__d = false, B2 && B2(t2), c2 = l2.render(l2.props, l2.state, l2.context), l2.state = l2.__s;
    while (l2.__d && ++Fe < 25);
    l2.state = l2.__s, l2.getChildContext != null && (o2 = A$2(A$2({}, o2), l2.getChildContext())), _2 || l2.getSnapshotBeforeUpdate == null || (f2 = l2.getSnapshotBeforeUpdate(h2, m2)), pt(e2, fe(De = c2 != null && c2.type === w$3 && c2.key == null ? c2.props.children : c2) ? De : [De], t2, n2, o2, i2, r2, a2, s2, u2, d2), l2.base = t2.__e, t2.__u &= -161, l2.__h.length && a2.push(l2), g2 && (l2.__E = l2.__ = null);
  } catch (oe) {
    t2.__v = null, u2 || r2 != null ? (t2.__e = s2, t2.__u |= u2 ? 160 : 32, r2[r2.indexOf(s2)] = null) : (t2.__e = n2.__e, t2.__k = n2.__k), v$3.__e(oe, t2, n2);
  }
  else r2 == null && t2.__v === n2.__v ? (t2.__k = n2.__k, t2.__e = n2.__e) : t2.__e = Jt(n2.__e, t2, n2, o2, i2, r2, a2, u2, d2);
  (c2 = v$3.diffed) && c2(t2);
}
function mt(e2, t2, n2) {
  t2.__d = void 0;
  for (var o2 = 0; o2 < n2.length; o2++) Oe(n2[o2], n2[++o2], n2[++o2]);
  v$3.__c && v$3.__c(t2, e2), e2.some(function(i2) {
    try {
      e2 = i2.__h, i2.__h = [], e2.some(function(r2) {
        r2.call(i2);
      });
    } catch (r2) {
      v$3.__e(r2, i2.__v);
    }
  });
}
function Jt(e2, t2, n2, o2, i2, r2, a2, s2, u2) {
  var d2, c2, l2, _2, h2, m2, f2, g2 = n2.props, y2 = t2.props, b2 = t2.type;
  if (b2 === "svg" && (i2 = true), r2 != null) {
    for (d2 = 0; d2 < r2.length; d2++) if ((h2 = r2[d2]) && "setAttribute" in h2 == !!b2 && (b2 ? h2.localName === b2 : h2.nodeType === 3)) {
      e2 = h2, r2[d2] = null;
      break;
    }
  }
  if (e2 == null) {
    if (b2 === null) return document.createTextNode(y2);
    e2 = i2 ? document.createElementNS("http://www.w3.org/2000/svg", b2) : document.createElement(b2, y2.is && y2), r2 = null, s2 = false;
  }
  if (b2 === null) g2 === y2 || s2 && e2.data === y2 || (e2.data = y2);
  else {
    if (r2 = r2 && pe.call(e2.childNodes), g2 = n2.props || q$2, !s2 && r2 != null) for (g2 = {}, d2 = 0; d2 < e2.attributes.length; d2++) g2[(h2 = e2.attributes[d2]).name] = h2.value;
    for (d2 in g2) h2 = g2[d2], d2 == "children" || (d2 == "dangerouslySetInnerHTML" ? l2 = h2 : d2 === "key" || d2 in y2 || ie(e2, d2, null, h2, i2));
    for (d2 in y2) h2 = y2[d2], d2 == "children" ? _2 = h2 : d2 == "dangerouslySetInnerHTML" ? c2 = h2 : d2 == "value" ? m2 = h2 : d2 == "checked" ? f2 = h2 : d2 === "key" || s2 && typeof h2 != "function" || g2[d2] === h2 || ie(e2, d2, h2, g2[d2], i2);
    if (c2) s2 || l2 && (c2.__html === l2.__html || c2.__html === e2.innerHTML) || (e2.innerHTML = c2.__html), t2.__k = [];
    else if (l2 && (e2.innerHTML = ""), pt(e2, fe(_2) ? _2 : [_2], t2, n2, o2, i2 && b2 !== "foreignObject", r2, a2, r2 ? r2[0] : n2.__k && H$2(n2, 0), s2, u2), r2 != null) for (d2 = r2.length; d2--; ) r2[d2] != null && vt(r2[d2]);
    s2 || (d2 = "value", m2 !== void 0 && (m2 !== e2[d2] || b2 === "progress" && !m2 || b2 === "option" && m2 !== g2[d2]) && ie(e2, d2, m2, g2[d2], false), d2 = "checked", f2 !== void 0 && f2 !== e2[d2] && ie(e2, d2, f2, g2[d2], false));
  }
  return e2;
}
function Oe(e2, t2, n2) {
  try {
    typeof e2 == "function" ? e2(t2) : e2.current = t2;
  } catch (o2) {
    v$3.__e(o2, n2);
  }
}
function we(e2, t2, n2) {
  var o2, i2;
  if (v$3.unmount && v$3.unmount(e2), (o2 = e2.ref) && (o2.current && o2.current !== e2.__e || Oe(o2, null, t2)), (o2 = e2.__c) != null) {
    if (o2.componentWillUnmount) try {
      o2.componentWillUnmount();
    } catch (r2) {
      v$3.__e(r2, t2);
    }
    o2.base = o2.__P = null, e2.__c = void 0;
  }
  if (o2 = e2.__k) for (i2 = 0; i2 < o2.length; i2++) o2[i2] && we(o2[i2], t2, n2 || typeof e2.type != "function");
  n2 || e2.__e == null || vt(e2.__e), e2.__ = e2.__e = e2.__d = void 0;
}
function Zt(e2, t2, n2) {
  return this.constructor(e2, n2);
}
function qt(e2, t2) {
  var n2 = { __c: t2 = "__cC" + ct++, __: e2, Consumer: function(o2, i2) {
    return o2.children(i2);
  }, Provider: function(o2) {
    var i2, r2;
    return this.getChildContext || (i2 = [], (r2 = {})[t2] = this, this.getChildContext = function() {
      return r2;
    }, this.shouldComponentUpdate = function(a2) {
      this.props.value !== a2.value && i2.some(function(s2) {
        s2.__e = true, Se(s2);
      });
    }, this.sub = function(a2) {
      i2.push(a2);
      var s2 = a2.componentWillUnmount;
      a2.componentWillUnmount = function() {
        i2.splice(i2.indexOf(a2), 1), s2 && s2.call(a2);
      };
    }), o2.children;
  } };
  return n2.Provider.__ = n2.Consumer.contextType = n2;
}
pe = _t.slice, v$3 = { __e: function(e2, t2, n2, o2) {
  for (var i2, r2, a2; t2 = t2.__; ) if ((i2 = t2.__c) && !i2.__) try {
    if ((r2 = i2.constructor) && r2.getDerivedStateFromError != null && (i2.setState(r2.getDerivedStateFromError(e2)), a2 = i2.__d), i2.componentDidCatch != null && (i2.componentDidCatch(e2, o2 || {}), a2 = i2.__d), a2) return i2.__E = i2;
  } catch (s2) {
    e2 = s2;
  }
  throw e2;
} }, lt = 0, ut = function(e2) {
  return e2 != null && e2.constructor == null;
}, T$3.prototype.setState = function(e2, t2) {
  var n2;
  n2 = this.__s != null && this.__s !== this.state ? this.__s : this.__s = A$2({}, this.state), typeof e2 == "function" && (e2 = e2(A$2({}, n2), this.props)), e2 && A$2(n2, e2), e2 != null && this.__v && (t2 && this._sb.push(t2), Se(this));
}, T$3.prototype.forceUpdate = function(e2) {
  this.__v && (this.__e = true, e2 && this.__h.push(e2), Se(this));
}, T$3.prototype.render = w$3, V$2 = [], dt = typeof Promise == "function" ? Promise.prototype.then.bind(Promise.resolve()) : setTimeout, Pe = function(e2, t2) {
  return e2.__v.__b - t2.__v.__b;
}, ce.__r = 0, ct = 0;
var z$2, D$2, ke, Re, Ye = 0, yt = [], le = [], je = v$3.__b, He = v$3.__r, ze = v$3.diffed, Ke = v$3.__c, Be = v$3.unmount;
function me(e2, t2) {
  v$3.__h && v$3.__h(D$2, e2, Ye || t2), Ye = 0;
  var n2 = D$2.__H || (D$2.__H = { __: [], __h: [] });
  return e2 >= n2.__.length && n2.__.push({ __V: le }), n2.__[e2];
}
function Dt(e2, t2) {
  var n2 = me(z$2++, 7);
  return kt(n2.__H, t2) ? (n2.__V = e2(), n2.i = t2, n2.__h = e2, n2.__V) : n2.__;
}
function en$1() {
  for (var e2; e2 = yt.shift(); ) if (e2.__P && e2.__H) try {
    e2.__H.__h.forEach(ue), e2.__H.__h.forEach(Ne), e2.__H.__h = [];
  } catch (t2) {
    e2.__H.__h = [], v$3.__e(t2, e2.__v);
  }
}
v$3.__b = function(e2) {
  D$2 = null, je && je(e2);
}, v$3.__r = function(e2) {
  He && He(e2), z$2 = 0;
  var t2 = (D$2 = e2.__c).__H;
  t2 && (ke === D$2 ? (t2.__h = [], D$2.__h = [], t2.__.forEach(function(n2) {
    n2.__N && (n2.__ = n2.__N), n2.__V = le, n2.__N = n2.i = void 0;
  })) : (t2.__h.forEach(ue), t2.__h.forEach(Ne), t2.__h = [], z$2 = 0)), ke = D$2;
}, v$3.diffed = function(e2) {
  ze && ze(e2);
  var t2 = e2.__c;
  t2 && t2.__H && (t2.__H.__h.length && (yt.push(t2) !== 1 && Re === v$3.requestAnimationFrame || ((Re = v$3.requestAnimationFrame) || tn)(en$1)), t2.__H.__.forEach(function(n2) {
    n2.i && (n2.__H = n2.i), n2.__V !== le && (n2.__ = n2.__V), n2.i = void 0, n2.__V = le;
  })), ke = D$2 = null;
}, v$3.__c = function(e2, t2) {
  t2.some(function(n2) {
    try {
      n2.__h.forEach(ue), n2.__h = n2.__h.filter(function(o2) {
        return !o2.__ || Ne(o2);
      });
    } catch (o2) {
      t2.some(function(i2) {
        i2.__h && (i2.__h = []);
      }), t2 = [], v$3.__e(o2, n2.__v);
    }
  }), Ke && Ke(e2, t2);
}, v$3.unmount = function(e2) {
  Be && Be(e2);
  var t2, n2 = e2.__c;
  n2 && n2.__H && (n2.__H.__.forEach(function(o2) {
    try {
      ue(o2);
    } catch (i2) {
      t2 = i2;
    }
  }), n2.__H = void 0, t2 && v$3.__e(t2, n2.__v));
};
var Ge = typeof requestAnimationFrame == "function";
function tn(e2) {
  var t2, n2 = function() {
    clearTimeout(o2), Ge && cancelAnimationFrame(t2), setTimeout(e2);
  }, o2 = setTimeout(n2, 100);
  Ge && (t2 = requestAnimationFrame(n2));
}
function ue(e2) {
  var t2 = D$2, n2 = e2.__c;
  typeof n2 == "function" && (e2.__c = void 0, n2()), D$2 = t2;
}
function Ne(e2) {
  var t2 = D$2;
  e2.__c = e2.__(), D$2 = t2;
}
function kt(e2, t2) {
  return !e2 || e2.length !== t2.length || t2.some(function(n2, o2) {
    return n2 !== e2[o2];
  });
}
function nn(e2, t2) {
  for (var n2 in t2) e2[n2] = t2[n2];
  return e2;
}
function Je(e2, t2) {
  for (var n2 in e2) if (n2 !== "__source" && !(n2 in t2)) return true;
  for (var o2 in t2) if (o2 !== "__source" && e2[o2] !== t2[o2]) return true;
  return false;
}
function Ze(e2) {
  this.props = e2;
}
(Ze.prototype = new T$3()).isPureReactComponent = true, Ze.prototype.shouldComponentUpdate = function(e2, t2) {
  return Je(this.props, e2) || Je(this.state, t2);
};
var qe = v$3.__b;
v$3.__b = function(e2) {
  e2.type && e2.type.__f && e2.ref && (e2.props.ref = e2.ref, e2.ref = null), qe && qe(e2);
};
var on$1 = v$3.__e;
v$3.__e = function(e2, t2, n2, o2) {
  if (e2.then) {
    for (var i2, r2 = t2; r2 = r2.__; ) if ((i2 = r2.__c) && i2.__c) return t2.__e == null && (t2.__e = n2.__e, t2.__k = n2.__k), i2.__c(e2, t2);
  }
  on$1(e2, t2, n2, o2);
};
var Xe = v$3.unmount;
function bt(e2, t2, n2) {
  return e2 && (e2.__c && e2.__c.__H && (e2.__c.__H.__.forEach(function(o2) {
    typeof o2.__c == "function" && o2.__c();
  }), e2.__c.__H = null), (e2 = nn({}, e2)).__c != null && (e2.__c.__P === n2 && (e2.__c.__P = t2), e2.__c = null), e2.__k = e2.__k && e2.__k.map(function(o2) {
    return bt(o2, t2, n2);
  })), e2;
}
function Pt(e2, t2, n2) {
  return e2 && n2 && (e2.__v = null, e2.__k = e2.__k && e2.__k.map(function(o2) {
    return Pt(o2, t2, n2);
  }), e2.__c && e2.__c.__P === t2 && (e2.__e && n2.appendChild(e2.__e), e2.__c.__e = true, e2.__c.__P = n2)), e2;
}
function ge() {
  this.__u = 0, this.t = null, this.__b = null;
}
function St(e2) {
  var t2 = e2.__.__c;
  return t2 && t2.__a && t2.__a(e2);
}
function re() {
  this.u = null, this.o = null;
}
v$3.unmount = function(e2) {
  var t2 = e2.__c;
  t2 && t2.__R && t2.__R(), t2 && 32 & e2.__u && (e2.type = null), Xe && Xe(e2);
}, (ge.prototype = new T$3()).__c = function(e2, t2) {
  var n2 = t2.__c, o2 = this;
  o2.t == null && (o2.t = []), o2.t.push(n2);
  var i2 = St(o2.__v), r2 = false, a2 = function() {
    r2 || (r2 = true, n2.__R = null, i2 ? i2(s2) : s2());
  };
  n2.__R = a2;
  var s2 = function() {
    if (!--o2.__u) {
      if (o2.state.__a) {
        var u2 = o2.state.__a;
        o2.__v.__k[0] = Pt(u2, u2.__c.__P, u2.__c.__O);
      }
      var d2;
      for (o2.setState({ __a: o2.__b = null }); d2 = o2.t.pop(); ) d2.forceUpdate();
    }
  };
  o2.__u++ || 32 & t2.__u || o2.setState({ __a: o2.__b = o2.__v.__k[0] }), e2.then(a2, a2);
}, ge.prototype.componentWillUnmount = function() {
  this.t = [];
}, ge.prototype.render = function(e2, t2) {
  if (this.__b) {
    if (this.__v.__k) {
      var n2 = document.createElement("div"), o2 = this.__v.__k[0].__c;
      this.__v.__k[0] = bt(this.__b, n2, o2.__O = o2.__P);
    }
    this.__b = null;
  }
  var i2 = t2.__a && j$3(w$3, null, e2.fallback);
  return i2 && (i2.__u &= -33), [j$3(w$3, null, t2.__a ? null : e2.children), i2];
};
var Qe = function(e2, t2, n2) {
  if (++n2[1] === n2[0] && e2.o.delete(t2), e2.props.revealOrder && (e2.props.revealOrder[0] !== "t" || !e2.o.size)) for (n2 = e2.u; n2; ) {
    for (; n2.length > 3; ) n2.pop()();
    if (n2[1] < n2[0]) break;
    e2.u = n2 = n2[2];
  }
};
(re.prototype = new T$3()).__a = function(e2) {
  var t2 = this, n2 = St(t2.__v), o2 = t2.o.get(e2);
  return o2[0]++, function(i2) {
    var r2 = function() {
      t2.props.revealOrder ? (o2.push(i2), Qe(t2, e2, o2)) : i2();
    };
    n2 ? n2(r2) : r2();
  };
}, re.prototype.render = function(e2) {
  this.u = null, this.o = /* @__PURE__ */ new Map();
  var t2 = _e(e2.children);
  e2.revealOrder && e2.revealOrder[0] === "b" && t2.reverse();
  for (var n2 = t2.length; n2--; ) this.o.set(t2[n2], this.u = [1, 0, this.u]);
  return e2.children;
}, re.prototype.componentDidUpdate = re.prototype.componentDidMount = function() {
  var e2 = this;
  this.o.forEach(function(t2, n2) {
    Qe(e2, n2, t2);
  });
};
var ln$1 = typeof Symbol < "u" && Symbol.for && Symbol.for("react.element") || 60103, un$1 = /^(?:accent|alignment|arabic|baseline|cap|clip(?!PathU)|color|dominant|fill|flood|font|glyph(?!R)|horiz|image(!S)|letter|lighting|marker(?!H|W|U)|overline|paint|pointer|shape|stop|strikethrough|stroke|text(?!L)|transform|underline|unicode|units|v|vector|vert|word|writing|x(?!C))[A-Z]/, dn = /^on(Ani|Tra|Tou|BeforeInp|Compo)/, cn = /[A-Z0-9]/g, _n = typeof document < "u", vn = function(e2) {
  return (typeof Symbol < "u" && typeof Symbol() == "symbol" ? /fil|che|rad/ : /fil|che|ra/).test(e2);
};
T$3.prototype.isReactComponent = {}, ["componentWillMount", "componentWillReceiveProps", "componentWillUpdate"].forEach(function(e2) {
  Object.defineProperty(T$3.prototype, e2, { configurable: true, get: function() {
    return this["UNSAFE_" + e2];
  }, set: function(t2) {
    Object.defineProperty(this, e2, { configurable: true, writable: true, value: t2 });
  } });
});
var et = v$3.event;
function hn() {
}
function pn() {
  return this.cancelBubble;
}
function fn$1() {
  return this.defaultPrevented;
}
v$3.event = function(e2) {
  return et && (e2 = et(e2)), e2.persist = hn, e2.isPropagationStopped = pn, e2.isDefaultPrevented = fn$1, e2.nativeEvent = e2;
};
var mn = { enumerable: false, configurable: true, get: function() {
  return this.class;
} }, tt = v$3.vnode;
v$3.vnode = function(e2) {
  typeof e2.type == "string" && function(t2) {
    var n2 = t2.props, o2 = t2.type, i2 = {};
    for (var r2 in n2) {
      var a2 = n2[r2];
      if (!(r2 === "value" && "defaultValue" in n2 && a2 == null || _n && r2 === "children" && o2 === "noscript" || r2 === "class" || r2 === "className")) {
        var s2 = r2.toLowerCase();
        r2 === "defaultValue" && "value" in n2 && n2.value == null ? r2 = "value" : r2 === "download" && a2 === true ? a2 = "" : s2 === "ondoubleclick" ? r2 = "ondblclick" : s2 !== "onchange" || o2 !== "input" && o2 !== "textarea" || vn(n2.type) ? s2 === "onfocus" ? r2 = "onfocusin" : s2 === "onblur" ? r2 = "onfocusout" : dn.test(r2) ? r2 = s2 : o2.indexOf("-") === -1 && un$1.test(r2) ? r2 = r2.replace(cn, "-$&").toLowerCase() : a2 === null && (a2 = void 0) : s2 = r2 = "oninput", s2 === "oninput" && i2[r2 = s2] && (r2 = "oninputCapture"), i2[r2] = a2;
      }
    }
    o2 == "select" && i2.multiple && Array.isArray(i2.value) && (i2.value = _e(n2.children).forEach(function(u2) {
      u2.props.selected = i2.value.indexOf(u2.props.value) != -1;
    })), o2 == "select" && i2.defaultValue != null && (i2.value = _e(n2.children).forEach(function(u2) {
      u2.props.selected = i2.multiple ? i2.defaultValue.indexOf(u2.props.value) != -1 : i2.defaultValue == u2.props.value;
    })), n2.class && !n2.className ? (i2.class = n2.class, Object.defineProperty(i2, "className", mn)) : (n2.className && !n2.class || n2.class && n2.className) && (i2.class = i2.className = n2.className), t2.props = i2;
  }(e2), e2.$$typeof = ln$1, tt && tt(e2);
};
var nt = v$3.__r;
v$3.__r = function(e2) {
  nt && nt(e2), e2.__c;
};
var ot = v$3.diffed;
v$3.diffed = function(e2) {
  ot && ot(e2);
  var t2 = e2.props, n2 = e2.__e;
  n2 != null && e2.type === "textarea" && "value" in t2 && t2.value !== n2.value && (n2.value = t2.value == null ? "" : t2.value);
};
function ye() {
  throw new Error("Cycle detected");
}
var yn = Symbol.for("preact-signals");
function $e() {
  if (R > 1)
    R--;
  else {
    for (var e2, t2 = false; Z$1 !== void 0; ) {
      var n2 = Z$1;
      for (Z$1 = void 0, xe++; n2 !== void 0; ) {
        var o2 = n2.o;
        if (n2.o = void 0, n2.f &= -3, !(8 & n2.f) && Mt(n2)) try {
          n2.c();
        } catch (i2) {
          t2 || (e2 = i2, t2 = true);
        }
        n2 = o2;
      }
    }
    if (xe = 0, R--, t2) throw e2;
  }
}
var k$2 = void 0, Z$1 = void 0, R = 0, xe = 0, ve = 0;
function wt(e2) {
  if (k$2 !== void 0) {
    var t2 = e2.n;
    if (t2 === void 0 || t2.t !== k$2)
      return t2 = { i: 0, S: e2, p: k$2.s, n: void 0, t: k$2, e: void 0, x: void 0, r: t2 }, k$2.s !== void 0 && (k$2.s.n = t2), k$2.s = t2, e2.n = t2, 32 & k$2.f && e2.S(t2), t2;
    if (t2.i === -1)
      return t2.i = 0, t2.n !== void 0 && (t2.n.p = t2.p, t2.p !== void 0 && (t2.p.n = t2.n), t2.p = k$2.s, t2.n = void 0, k$2.s.n = t2, k$2.s = t2), t2;
  }
}
function P$2(e2) {
  this.v = e2, this.i = 0, this.n = void 0, this.t = void 0;
}
P$2.prototype.brand = yn;
P$2.prototype.h = function() {
  return true;
};
P$2.prototype.S = function(e2) {
  this.t !== e2 && e2.e === void 0 && (e2.x = this.t, this.t !== void 0 && (this.t.e = e2), this.t = e2);
};
P$2.prototype.U = function(e2) {
  if (this.t !== void 0) {
    var t2 = e2.e, n2 = e2.x;
    t2 !== void 0 && (t2.x = n2, e2.e = void 0), n2 !== void 0 && (n2.e = t2, e2.x = void 0), e2 === this.t && (this.t = n2);
  }
};
P$2.prototype.subscribe = function(e2) {
  var t2 = this;
  return X$1(function() {
    var n2 = t2.value, o2 = 32 & this.f;
    this.f &= -33;
    try {
      e2(n2);
    } finally {
      this.f |= o2;
    }
  });
};
P$2.prototype.valueOf = function() {
  return this.value;
};
P$2.prototype.toString = function() {
  return this.value + "";
};
P$2.prototype.toJSON = function() {
  return this.value;
};
P$2.prototype.peek = function() {
  return this.v;
};
Object.defineProperty(P$2.prototype, "value", { get: function() {
  var e2 = wt(this);
  return e2 !== void 0 && (e2.i = this.i), this.v;
}, set: function(e2) {
  if (k$2 instanceof W$1 && function() {
    throw new Error("Computed cannot have side-effects");
  }(), e2 !== this.v) {
    xe > 100 && ye(), this.v = e2, this.i++, ve++, R++;
    try {
      for (var t2 = this.t; t2 !== void 0; t2 = t2.x) t2.t.N();
    } finally {
      $e();
    }
  }
} });
function S$1(e2) {
  return new P$2(e2);
}
function Mt(e2) {
  for (var t2 = e2.s; t2 !== void 0; t2 = t2.n) if (t2.S.i !== t2.i || !t2.S.h() || t2.S.i !== t2.i) return true;
  return false;
}
function Yt(e2) {
  for (var t2 = e2.s; t2 !== void 0; t2 = t2.n) {
    var n2 = t2.S.n;
    if (n2 !== void 0 && (t2.r = n2), t2.S.n = t2, t2.i = -1, t2.n === void 0) {
      e2.s = t2;
      break;
    }
  }
}
function Nt(e2) {
  for (var t2 = e2.s, n2 = void 0; t2 !== void 0; ) {
    var o2 = t2.p;
    t2.i === -1 ? (t2.S.U(t2), o2 !== void 0 && (o2.n = t2.n), t2.n !== void 0 && (t2.n.p = o2)) : n2 = t2, t2.S.n = t2.r, t2.r !== void 0 && (t2.r = void 0), t2 = o2;
  }
  e2.s = n2;
}
function W$1(e2) {
  P$2.call(this, void 0), this.x = e2, this.s = void 0, this.g = ve - 1, this.f = 4;
}
(W$1.prototype = new P$2()).h = function() {
  if (this.f &= -3, 1 & this.f) return false;
  if ((36 & this.f) == 32 || (this.f &= -5, this.g === ve)) return true;
  if (this.g = ve, this.f |= 1, this.i > 0 && !Mt(this))
    return this.f &= -2, true;
  var e2 = k$2;
  try {
    Yt(this), k$2 = this;
    var t2 = this.x();
    (16 & this.f || this.v !== t2 || this.i === 0) && (this.v = t2, this.f &= -17, this.i++);
  } catch (n2) {
    this.v = n2, this.f |= 16, this.i++;
  }
  return k$2 = e2, Nt(this), this.f &= -2, true;
};
W$1.prototype.S = function(e2) {
  if (this.t === void 0) {
    this.f |= 36;
    for (var t2 = this.s; t2 !== void 0; t2 = t2.n) t2.S.S(t2);
  }
  P$2.prototype.S.call(this, e2);
};
W$1.prototype.U = function(e2) {
  if (this.t !== void 0 && (P$2.prototype.U.call(this, e2), this.t === void 0)) {
    this.f &= -33;
    for (var t2 = this.s; t2 !== void 0; t2 = t2.n) t2.S.U(t2);
  }
};
W$1.prototype.N = function() {
  if (!(2 & this.f)) {
    this.f |= 6;
    for (var e2 = this.t; e2 !== void 0; e2 = e2.x) e2.t.N();
  }
};
W$1.prototype.peek = function() {
  if (this.h() || ye(), 16 & this.f) throw this.v;
  return this.v;
};
Object.defineProperty(W$1.prototype, "value", { get: function() {
  1 & this.f && ye();
  var e2 = wt(this);
  if (this.h(), e2 !== void 0 && (e2.i = this.i), 16 & this.f) throw this.v;
  return this.v;
} });
function Dn(e2) {
  return new W$1(e2);
}
function xt(e2) {
  var t2 = e2.u;
  if (e2.u = void 0, typeof t2 == "function") {
    R++;
    var n2 = k$2;
    k$2 = void 0;
    try {
      t2();
    } catch (o2) {
      throw e2.f &= -2, e2.f |= 8, Ae(e2), o2;
    } finally {
      k$2 = n2, $e();
    }
  }
}
function Ae(e2) {
  for (var t2 = e2.s; t2 !== void 0; t2 = t2.n) t2.S.U(t2);
  e2.x = void 0, e2.s = void 0, xt(e2);
}
function kn(e2) {
  if (k$2 !== this) throw new Error("Out-of-order effect");
  Nt(this), k$2 = e2, this.f &= -2, 8 & this.f && Ae(this), $e();
}
function Q$1(e2) {
  this.x = e2, this.u = void 0, this.s = void 0, this.o = void 0, this.f = 32;
}
Q$1.prototype.c = function() {
  var e2 = this.S();
  try {
    if (8 & this.f || this.x === void 0) return;
    var t2 = this.x();
    typeof t2 == "function" && (this.u = t2);
  } finally {
    e2();
  }
};
Q$1.prototype.S = function() {
  1 & this.f && ye(), this.f |= 1, this.f &= -9, xt(this), Yt(this), R++;
  var e2 = k$2;
  return k$2 = this, kn.bind(this, e2);
};
Q$1.prototype.N = function() {
  2 & this.f || (this.f |= 2, this.o = Z$1, Z$1 = this);
};
Q$1.prototype.d = function() {
  this.f |= 8, 1 & this.f || Ae(this);
};
function X$1(e2) {
  var t2 = new Q$1(e2);
  try {
    t2.c();
  } catch (n2) {
    throw t2.d(), n2;
  }
  return t2.d.bind(t2);
}
var be;
function K$1(e2, t2) {
  v$3[e2] = t2.bind(null, v$3[e2] || function() {
  });
}
function he(e2) {
  be && be(), be = e2 && e2.S();
}
function Et(e2) {
  var t2 = this, n2 = e2.data, o2 = bn(n2);
  o2.value = n2;
  var i2 = Dt(function() {
    for (var r2 = t2.__v; r2 = r2.__; ) if (r2.__c) {
      r2.__c.__$f |= 4;
      break;
    }
    return t2.__$u.c = function() {
      var a2;
      !ut(i2.peek()) && ((a2 = t2.base) == null ? void 0 : a2.nodeType) === 3 ? t2.base.data = i2.peek() : (t2.__$f |= 1, t2.setState({}));
    }, Dn(function() {
      var a2 = o2.value.value;
      return a2 === 0 ? 0 : a2 === true ? "" : a2 || "";
    });
  }, []);
  return i2.value;
}
Et.displayName = "_st";
Object.defineProperties(P$2.prototype, { constructor: { configurable: true, value: void 0 }, type: { configurable: true, value: Et }, props: { configurable: true, get: function() {
  return { data: this };
} }, __b: { configurable: true, value: 1 } });
K$1("__b", function(e2, t2) {
  if (typeof t2.type == "string") {
    var n2, o2 = t2.props;
    for (var i2 in o2) if (i2 !== "children") {
      var r2 = o2[i2];
      r2 instanceof P$2 && (n2 || (t2.__np = n2 = {}), n2[i2] = r2, o2[i2] = r2.peek());
    }
  }
  e2(t2);
});
K$1("__r", function(e2, t2) {
  he();
  var n2, o2 = t2.__c;
  o2 && (o2.__$f &= -2, (n2 = o2.__$u) === void 0 && (o2.__$u = n2 = function(i2) {
    var r2;
    return X$1(function() {
      r2 = this;
    }), r2.c = function() {
      o2.__$f |= 1, o2.setState({});
    }, r2;
  }())), he(n2), e2(t2);
});
K$1("__e", function(e2, t2, n2, o2) {
  he(), e2(t2, n2, o2);
});
K$1("diffed", function(e2, t2) {
  he();
  var n2;
  if (typeof t2.type == "string" && (n2 = t2.__e)) {
    var o2 = t2.__np, i2 = t2.props;
    if (o2) {
      var r2 = n2.U;
      if (r2) for (var a2 in r2) {
        var s2 = r2[a2];
        s2 !== void 0 && !(a2 in o2) && (s2.d(), r2[a2] = void 0);
      }
      else n2.U = r2 = {};
      for (var u2 in o2) {
        var d2 = r2[u2], c2 = o2[u2];
        d2 === void 0 ? (d2 = gn(n2, u2, c2, i2), r2[u2] = d2) : d2.o(c2, i2);
      }
    }
  }
  e2(t2);
});
function gn(e2, t2, n2, o2) {
  var i2 = t2 in e2 && e2.ownerSVGElement === void 0, r2 = S$1(n2);
  return { o: function(a2, s2) {
    r2.value = a2, o2 = s2;
  }, d: X$1(function() {
    var a2 = r2.value.value;
    o2[t2] !== a2 && (o2[t2] = a2, i2 ? e2[t2] = a2 : a2 ? e2.setAttribute(t2, a2) : e2.removeAttribute(t2));
  }) };
}
K$1("unmount", function(e2, t2) {
  if (typeof t2.type == "string") {
    var n2 = t2.__e;
    if (n2) {
      var o2 = n2.U;
      if (o2) {
        n2.U = void 0;
        for (var i2 in o2) {
          var r2 = o2[i2];
          r2 && r2.d();
        }
      }
    }
  } else {
    var a2 = t2.__c;
    if (a2) {
      var s2 = a2.__$u;
      s2 && (a2.__$u = void 0, s2.d());
    }
  }
  e2(t2);
});
K$1("__h", function(e2, t2, n2, o2) {
  (o2 < 3 || o2 === 9) && (t2.__$f |= 2), e2(t2, n2, o2);
});
T$3.prototype.shouldComponentUpdate = function(e2, t2) {
  var n2 = this.__$u;
  if (!(n2 && n2.s !== void 0 || 4 & this.__$f) || 3 & this.__$f) return true;
  for (var o2 in t2) return true;
  for (var i2 in e2) if (i2 !== "__source" && e2[i2] !== this.props[i2]) return true;
  for (var r2 in this.props) if (!(r2 in e2)) return true;
  return false;
};
function bn(e2) {
  return Dt(function() {
    return S$1(e2);
  }, []);
}
qt({});
var U$1;
(function(e2) {
  e2.MONTH_DAYS = "month-days", e2.YEARS = "years";
})(U$1 || (U$1 = {}));
var Ee;
(function(e2) {
  e2[e2.SUNDAY = 0] = "SUNDAY", e2[e2.MONDAY = 1] = "MONDAY", e2[e2.TUESDAY = 2] = "TUESDAY", e2[e2.WEDNESDAY = 3] = "WEDNESDAY", e2[e2.THURSDAY = 4] = "THURSDAY", e2[e2.FRIDAY = 5] = "FRIDAY", e2[e2.SATURDAY = 6] = "SATURDAY";
})(Ee || (Ee = {}));
Ee.MONDAY;
var Le;
(function(e2) {
  e2[e2.JANUARY = 0] = "JANUARY", e2[e2.FEBRUARY = 1] = "FEBRUARY", e2[e2.MARCH = 2] = "MARCH", e2[e2.APRIL = 3] = "APRIL", e2[e2.MAY = 4] = "MAY", e2[e2.JUNE = 5] = "JUNE", e2[e2.JULY = 6] = "JULY", e2[e2.AUGUST = 7] = "AUGUST", e2[e2.SEPTEMBER = 8] = "SEPTEMBER", e2[e2.OCTOBER = 9] = "OCTOBER", e2[e2.NOVEMBER = 10] = "NOVEMBER", e2[e2.DECEMBER = 11] = "DECEMBER";
})(Le || (Le = {}));
var N$2;
(function(e2) {
  e2.SLASH = "/", e2.DASH = "-", e2.PERIOD = ".";
})(N$2 || (N$2 = {}));
var x$2;
(function(e2) {
  e2.DMY = "DMY", e2.MDY = "MDY", e2.YMD = "YMD";
})(x$2 || (x$2 = {}));
({
  slashMDY: {
    delimiter: N$2.SLASH,
    order: x$2.MDY
  },
  slashDMY: {
    delimiter: N$2.SLASH,
    order: x$2.DMY
  },
  slashYMD: {
    delimiter: N$2.SLASH,
    order: x$2.YMD
  },
  periodDMY: {
    delimiter: N$2.PERIOD,
    order: x$2.DMY
  },
  dashYMD: {
    delimiter: N$2.DASH,
    order: x$2.YMD
  }
});
var Te;
(function(e2) {
  e2.TOP_START = "top-start", e2.TOP_END = "top-end", e2.BOTTOM_START = "bottom-start", e2.BOTTOM_END = "bottom-end";
})(Te || (Te = {}));
var n, l$3, u$3, t$2, i$2, r$2, o$2, e$2, f$3, c$2, s$3, a$2, h$3, p$3 = {}, v$2 = [], y$2 = /acit|ex(?:s|g|n|p|$)|rph|grid|ows|mnc|ntw|ine[ch]|zoo|^ord|itera/i, d$3 = Array.isArray;
function w$2(n2, l2) {
  for (var u2 in l2) n2[u2] = l2[u2];
  return n2;
}
function _$1(n2) {
  n2 && n2.parentNode && n2.parentNode.removeChild(n2);
}
function g$2(l2, u2, t2) {
  var i2, r2, o2, e2 = {};
  for (o2 in u2) "key" == o2 ? i2 = u2[o2] : "ref" == o2 ? r2 = u2[o2] : e2[o2] = u2[o2];
  if (arguments.length > 2 && (e2.children = arguments.length > 3 ? n.call(arguments, 2) : t2), "function" == typeof l2 && null != l2.defaultProps) for (o2 in l2.defaultProps) void 0 === e2[o2] && (e2[o2] = l2.defaultProps[o2]);
  return m$1(l2, e2, i2, r2, null);
}
function m$1(n2, t2, i2, r2, o2) {
  var e2 = { type: n2, props: t2, key: i2, ref: r2, __k: null, __: null, __b: 0, __e: null, __c: null, constructor: void 0, __v: null == o2 ? ++u$3 : o2, __i: -1, __u: 0 };
  return null == o2 && null != l$3.vnode && l$3.vnode(e2), e2;
}
function k$1(n2) {
  return n2.children;
}
function x$1(n2, l2) {
  this.props = n2, this.context = l2;
}
function C$1(n2, l2) {
  if (null == l2) return n2.__ ? C$1(n2.__, n2.__i + 1) : null;
  for (var u2; l2 < n2.__k.length; l2++) if (null != (u2 = n2.__k[l2]) && null != u2.__e) return u2.__e;
  return "function" == typeof n2.type ? C$1(n2) : null;
}
function S(n2) {
  var l2, u2;
  if (null != (n2 = n2.__) && null != n2.__c) {
    for (n2.__e = n2.__c.base = null, l2 = 0; l2 < n2.__k.length; l2++) if (null != (u2 = n2.__k[l2]) && null != u2.__e) {
      n2.__e = n2.__c.base = u2.__e;
      break;
    }
    return S(n2);
  }
}
function M(n2) {
  (!n2.__d && (n2.__d = true) && i$2.push(n2) && !P$1.__r++ || r$2 !== l$3.debounceRendering) && ((r$2 = l$3.debounceRendering) || o$2)(P$1);
}
function P$1() {
  var n2, u2, t2, r2, o2, f2, c2, s2;
  for (i$2.sort(e$2); n2 = i$2.shift(); ) n2.__d && (u2 = i$2.length, r2 = void 0, f2 = (o2 = (t2 = n2).__v).__e, c2 = [], s2 = [], t2.__P && ((r2 = w$2({}, o2)).__v = o2.__v + 1, l$3.vnode && l$3.vnode(r2), j$2(t2.__P, r2, o2, t2.__n, t2.__P.namespaceURI, 32 & o2.__u ? [f2] : null, c2, null == f2 ? C$1(o2) : f2, !!(32 & o2.__u), s2), r2.__v = o2.__v, r2.__.__k[r2.__i] = r2, z$1(c2, r2, s2), r2.__e != f2 && S(r2)), i$2.length > u2 && i$2.sort(e$2));
  P$1.__r = 0;
}
function $$1(n2, l2, u2, t2, i2, r2, o2, e2, f2, c2, s2) {
  var a2, h2, y2, d2, w2, _2, g2 = t2 && t2.__k || v$2, m2 = l2.length;
  for (f2 = I(u2, l2, g2, f2, m2), a2 = 0; a2 < m2; a2++) null != (y2 = u2.__k[a2]) && (h2 = -1 === y2.__i ? p$3 : g2[y2.__i] || p$3, y2.__i = a2, _2 = j$2(n2, y2, h2, i2, r2, o2, e2, f2, c2, s2), d2 = y2.__e, y2.ref && h2.ref != y2.ref && (h2.ref && V$1(h2.ref, null, y2), s2.push(y2.ref, y2.__c || d2, y2)), null == w2 && null != d2 && (w2 = d2), 4 & y2.__u || h2.__k === y2.__k ? f2 = A$1(y2, f2, n2) : "function" == typeof y2.type && void 0 !== _2 ? f2 = _2 : d2 && (f2 = d2.nextSibling), y2.__u &= -7);
  return u2.__e = w2, f2;
}
function I(n2, l2, u2, t2, i2) {
  var r2, o2, e2, f2, c2, s2 = u2.length, a2 = s2, h2 = 0;
  for (n2.__k = new Array(i2), r2 = 0; r2 < i2; r2++) null != (o2 = l2[r2]) && "boolean" != typeof o2 && "function" != typeof o2 ? (f2 = r2 + h2, (o2 = n2.__k[r2] = "string" == typeof o2 || "number" == typeof o2 || "bigint" == typeof o2 || o2.constructor == String ? m$1(null, o2, null, null, null) : d$3(o2) ? m$1(k$1, { children: o2 }, null, null, null) : void 0 === o2.constructor && o2.__b > 0 ? m$1(o2.type, o2.props, o2.key, o2.ref ? o2.ref : null, o2.__v) : o2).__ = n2, o2.__b = n2.__b + 1, e2 = null, -1 !== (c2 = o2.__i = L(o2, u2, f2, a2)) && (a2--, (e2 = u2[c2]) && (e2.__u |= 2)), null == e2 || null === e2.__v ? (-1 == c2 && h2--, "function" != typeof o2.type && (o2.__u |= 4)) : c2 != f2 && (c2 == f2 - 1 ? h2-- : c2 == f2 + 1 ? h2++ : (c2 > f2 ? h2-- : h2++, o2.__u |= 4))) : n2.__k[r2] = null;
  if (a2) for (r2 = 0; r2 < s2; r2++) null != (e2 = u2[r2]) && 0 == (2 & e2.__u) && (e2.__e == t2 && (t2 = C$1(e2)), q$1(e2, e2));
  return t2;
}
function A$1(n2, l2, u2) {
  var t2, i2;
  if ("function" == typeof n2.type) {
    for (t2 = n2.__k, i2 = 0; t2 && i2 < t2.length; i2++) t2[i2] && (t2[i2].__ = n2, l2 = A$1(t2[i2], l2, u2));
    return l2;
  }
  n2.__e != l2 && (l2 && n2.type && !u2.contains(l2) && (l2 = C$1(n2)), u2.insertBefore(n2.__e, l2 || null), l2 = n2.__e);
  do {
    l2 = l2 && l2.nextSibling;
  } while (null != l2 && 8 == l2.nodeType);
  return l2;
}
function H$1(n2, l2) {
  return l2 = l2 || [], null == n2 || "boolean" == typeof n2 || (d$3(n2) ? n2.some(function(n3) {
    H$1(n3, l2);
  }) : l2.push(n2)), l2;
}
function L(n2, l2, u2, t2) {
  var i2, r2, o2 = n2.key, e2 = n2.type, f2 = l2[u2];
  if (null === f2 || f2 && o2 == f2.key && e2 === f2.type && 0 == (2 & f2.__u)) return u2;
  if (t2 > (null != f2 && 0 == (2 & f2.__u) ? 1 : 0)) for (i2 = u2 - 1, r2 = u2 + 1; i2 >= 0 || r2 < l2.length; ) {
    if (i2 >= 0) {
      if ((f2 = l2[i2]) && 0 == (2 & f2.__u) && o2 == f2.key && e2 === f2.type) return i2;
      i2--;
    }
    if (r2 < l2.length) {
      if ((f2 = l2[r2]) && 0 == (2 & f2.__u) && o2 == f2.key && e2 === f2.type) return r2;
      r2++;
    }
  }
  return -1;
}
function T$2(n2, l2, u2) {
  "-" == l2[0] ? n2.setProperty(l2, null == u2 ? "" : u2) : n2[l2] = null == u2 ? "" : "number" != typeof u2 || y$2.test(l2) ? u2 : u2 + "px";
}
function F$1(n2, l2, u2, t2, i2) {
  var r2;
  n: if ("style" == l2) if ("string" == typeof u2) n2.style.cssText = u2;
  else {
    if ("string" == typeof t2 && (n2.style.cssText = t2 = ""), t2) for (l2 in t2) u2 && l2 in u2 || T$2(n2.style, l2, "");
    if (u2) for (l2 in u2) t2 && u2[l2] === t2[l2] || T$2(n2.style, l2, u2[l2]);
  }
  else if ("o" == l2[0] && "n" == l2[1]) r2 = l2 != (l2 = l2.replace(f$3, "$1")), l2 = l2.toLowerCase() in n2 || "onFocusOut" == l2 || "onFocusIn" == l2 ? l2.toLowerCase().slice(2) : l2.slice(2), n2.l || (n2.l = {}), n2.l[l2 + r2] = u2, u2 ? t2 ? u2.u = t2.u : (u2.u = c$2, n2.addEventListener(l2, r2 ? a$2 : s$3, r2)) : n2.removeEventListener(l2, r2 ? a$2 : s$3, r2);
  else {
    if ("http://www.w3.org/2000/svg" == i2) l2 = l2.replace(/xlink(H|:h)/, "h").replace(/sName$/, "s");
    else if ("width" != l2 && "height" != l2 && "href" != l2 && "list" != l2 && "form" != l2 && "tabIndex" != l2 && "download" != l2 && "rowSpan" != l2 && "colSpan" != l2 && "role" != l2 && "popover" != l2 && l2 in n2) try {
      n2[l2] = null == u2 ? "" : u2;
      break n;
    } catch (n3) {
    }
    "function" == typeof u2 || (null == u2 || false === u2 && "-" != l2[4] ? n2.removeAttribute(l2) : n2.setAttribute(l2, "popover" == l2 && 1 == u2 ? "" : u2));
  }
}
function O(n2) {
  return function(u2) {
    if (this.l) {
      var t2 = this.l[u2.type + n2];
      if (null == u2.t) u2.t = c$2++;
      else if (u2.t < t2.u) return;
      return t2(l$3.event ? l$3.event(u2) : u2);
    }
  };
}
function j$2(n2, u2, t2, i2, r2, o2, e2, f2, c2, s2) {
  var a2, h2, p2, v2, y2, g2, m2, b2, C2, S2, M2, P2, I2, A2, H2, L2, T2, F2 = u2.type;
  if (void 0 !== u2.constructor) return null;
  128 & t2.__u && (c2 = !!(32 & t2.__u), o2 = [f2 = u2.__e = t2.__e]), (a2 = l$3.__b) && a2(u2);
  n: if ("function" == typeof F2) try {
    if (b2 = u2.props, C2 = "prototype" in F2 && F2.prototype.render, S2 = (a2 = F2.contextType) && i2[a2.__c], M2 = a2 ? S2 ? S2.props.value : a2.__ : i2, t2.__c ? m2 = (h2 = u2.__c = t2.__c).__ = h2.__E : (C2 ? u2.__c = h2 = new F2(b2, M2) : (u2.__c = h2 = new x$1(b2, M2), h2.constructor = F2, h2.render = B$2), S2 && S2.sub(h2), h2.props = b2, h2.state || (h2.state = {}), h2.context = M2, h2.__n = i2, p2 = h2.__d = true, h2.__h = [], h2._sb = []), C2 && null == h2.__s && (h2.__s = h2.state), C2 && null != F2.getDerivedStateFromProps && (h2.__s == h2.state && (h2.__s = w$2({}, h2.__s)), w$2(h2.__s, F2.getDerivedStateFromProps(b2, h2.__s))), v2 = h2.props, y2 = h2.state, h2.__v = u2, p2) C2 && null == F2.getDerivedStateFromProps && null != h2.componentWillMount && h2.componentWillMount(), C2 && null != h2.componentDidMount && h2.__h.push(h2.componentDidMount);
    else {
      if (C2 && null == F2.getDerivedStateFromProps && b2 !== v2 && null != h2.componentWillReceiveProps && h2.componentWillReceiveProps(b2, M2), !h2.__e && (null != h2.shouldComponentUpdate && false === h2.shouldComponentUpdate(b2, h2.__s, M2) || u2.__v == t2.__v)) {
        for (u2.__v != t2.__v && (h2.props = b2, h2.state = h2.__s, h2.__d = false), u2.__e = t2.__e, u2.__k = t2.__k, u2.__k.some(function(n3) {
          n3 && (n3.__ = u2);
        }), P2 = 0; P2 < h2._sb.length; P2++) h2.__h.push(h2._sb[P2]);
        h2._sb = [], h2.__h.length && e2.push(h2);
        break n;
      }
      null != h2.componentWillUpdate && h2.componentWillUpdate(b2, h2.__s, M2), C2 && null != h2.componentDidUpdate && h2.__h.push(function() {
        h2.componentDidUpdate(v2, y2, g2);
      });
    }
    if (h2.context = M2, h2.props = b2, h2.__P = n2, h2.__e = false, I2 = l$3.__r, A2 = 0, C2) {
      for (h2.state = h2.__s, h2.__d = false, I2 && I2(u2), a2 = h2.render(h2.props, h2.state, h2.context), H2 = 0; H2 < h2._sb.length; H2++) h2.__h.push(h2._sb[H2]);
      h2._sb = [];
    } else do {
      h2.__d = false, I2 && I2(u2), a2 = h2.render(h2.props, h2.state, h2.context), h2.state = h2.__s;
    } while (h2.__d && ++A2 < 25);
    h2.state = h2.__s, null != h2.getChildContext && (i2 = w$2(w$2({}, i2), h2.getChildContext())), C2 && !p2 && null != h2.getSnapshotBeforeUpdate && (g2 = h2.getSnapshotBeforeUpdate(v2, y2)), f2 = $$1(n2, d$3(L2 = null != a2 && a2.type === k$1 && null == a2.key ? a2.props.children : a2) ? L2 : [L2], u2, t2, i2, r2, o2, e2, f2, c2, s2), h2.base = u2.__e, u2.__u &= -161, h2.__h.length && e2.push(h2), m2 && (h2.__E = h2.__ = null);
  } catch (n3) {
    if (u2.__v = null, c2 || null != o2) if (n3.then) {
      for (u2.__u |= c2 ? 160 : 128; f2 && 8 == f2.nodeType && f2.nextSibling; ) f2 = f2.nextSibling;
      o2[o2.indexOf(f2)] = null, u2.__e = f2;
    } else for (T2 = o2.length; T2--; ) _$1(o2[T2]);
    else u2.__e = t2.__e, u2.__k = t2.__k;
    l$3.__e(n3, u2, t2);
  }
  else null == o2 && u2.__v == t2.__v ? (u2.__k = t2.__k, u2.__e = t2.__e) : f2 = u2.__e = N$1(t2.__e, u2, t2, i2, r2, o2, e2, c2, s2);
  return (a2 = l$3.diffed) && a2(u2), 128 & u2.__u ? void 0 : f2;
}
function z$1(n2, u2, t2) {
  for (var i2 = 0; i2 < t2.length; i2++) V$1(t2[i2], t2[++i2], t2[++i2]);
  l$3.__c && l$3.__c(u2, n2), n2.some(function(u3) {
    try {
      n2 = u3.__h, u3.__h = [], n2.some(function(n3) {
        n3.call(u3);
      });
    } catch (n3) {
      l$3.__e(n3, u3.__v);
    }
  });
}
function N$1(u2, t2, i2, r2, o2, e2, f2, c2, s2) {
  var a2, h2, v2, y2, w2, g2, m2, b2 = i2.props, k2 = t2.props, x2 = t2.type;
  if ("svg" == x2 ? o2 = "http://www.w3.org/2000/svg" : "math" == x2 ? o2 = "http://www.w3.org/1998/Math/MathML" : o2 || (o2 = "http://www.w3.org/1999/xhtml"), null != e2) {
    for (a2 = 0; a2 < e2.length; a2++) if ((w2 = e2[a2]) && "setAttribute" in w2 == !!x2 && (x2 ? w2.localName == x2 : 3 == w2.nodeType)) {
      u2 = w2, e2[a2] = null;
      break;
    }
  }
  if (null == u2) {
    if (null == x2) return document.createTextNode(k2);
    u2 = document.createElementNS(o2, x2, k2.is && k2), c2 && (l$3.__m && l$3.__m(t2, e2), c2 = false), e2 = null;
  }
  if (null === x2) b2 === k2 || c2 && u2.data === k2 || (u2.data = k2);
  else {
    if (e2 = e2 && n.call(u2.childNodes), b2 = i2.props || p$3, !c2 && null != e2) for (b2 = {}, a2 = 0; a2 < u2.attributes.length; a2++) b2[(w2 = u2.attributes[a2]).name] = w2.value;
    for (a2 in b2) if (w2 = b2[a2], "children" == a2) ;
    else if ("dangerouslySetInnerHTML" == a2) v2 = w2;
    else if (!(a2 in k2)) {
      if ("value" == a2 && "defaultValue" in k2 || "checked" == a2 && "defaultChecked" in k2) continue;
      F$1(u2, a2, null, w2, o2);
    }
    for (a2 in k2) w2 = k2[a2], "children" == a2 ? y2 = w2 : "dangerouslySetInnerHTML" == a2 ? h2 = w2 : "value" == a2 ? g2 = w2 : "checked" == a2 ? m2 = w2 : c2 && "function" != typeof w2 || b2[a2] === w2 || F$1(u2, a2, w2, b2[a2], o2);
    if (h2) c2 || v2 && (h2.__html === v2.__html || h2.__html === u2.innerHTML) || (u2.innerHTML = h2.__html), t2.__k = [];
    else if (v2 && (u2.innerHTML = ""), $$1(u2, d$3(y2) ? y2 : [y2], t2, i2, r2, "foreignObject" == x2 ? "http://www.w3.org/1999/xhtml" : o2, e2, f2, e2 ? e2[0] : i2.__k && C$1(i2, 0), c2, s2), null != e2) for (a2 = e2.length; a2--; ) _$1(e2[a2]);
    c2 || (a2 = "value", "progress" == x2 && null == g2 ? u2.removeAttribute("value") : void 0 !== g2 && (g2 !== u2[a2] || "progress" == x2 && !g2 || "option" == x2 && g2 !== b2[a2]) && F$1(u2, a2, g2, b2[a2], o2), a2 = "checked", void 0 !== m2 && m2 !== u2[a2] && F$1(u2, a2, m2, b2[a2], o2));
  }
  return u2;
}
function V$1(n2, u2, t2) {
  try {
    if ("function" == typeof n2) {
      var i2 = "function" == typeof n2.__u;
      i2 && n2.__u(), i2 && null == u2 || (n2.__u = n2(u2));
    } else n2.current = u2;
  } catch (n3) {
    l$3.__e(n3, t2);
  }
}
function q$1(n2, u2, t2) {
  var i2, r2;
  if (l$3.unmount && l$3.unmount(n2), (i2 = n2.ref) && (i2.current && i2.current !== n2.__e || V$1(i2, null, u2)), null != (i2 = n2.__c)) {
    if (i2.componentWillUnmount) try {
      i2.componentWillUnmount();
    } catch (n3) {
      l$3.__e(n3, u2);
    }
    i2.base = i2.__P = null;
  }
  if (i2 = n2.__k) for (r2 = 0; r2 < i2.length; r2++) i2[r2] && q$1(i2[r2], u2, t2 || "function" != typeof n2.type);
  t2 || _$1(n2.__e), n2.__c = n2.__ = n2.__e = void 0;
}
function B$2(n2, l2, u2) {
  return this.constructor(n2, u2);
}
function D$1(u2, t2, i2) {
  var r2, o2, e2, f2;
  t2 == document && (t2 = document.documentElement), l$3.__ && l$3.__(u2, t2), o2 = (r2 = false) ? null : t2.__k, e2 = [], f2 = [], j$2(t2, u2 = t2.__k = g$2(k$1, null, [u2]), o2 || p$3, p$3, t2.namespaceURI, o2 ? null : t2.firstChild ? n.call(t2.childNodes) : null, e2, o2 ? o2.__e : t2.firstChild, r2, f2), z$1(e2, u2, f2);
}
function J$1(n2, l2) {
  var u2 = { __c: l2 = "__cC" + h$3++, __: n2, Consumer: function(n3, l3) {
    return n3.children(l3);
  }, Provider: function(n3) {
    var u3, t2;
    return this.getChildContext || (u3 = /* @__PURE__ */ new Set(), (t2 = {})[l2] = this, this.getChildContext = function() {
      return t2;
    }, this.componentWillUnmount = function() {
      u3 = null;
    }, this.shouldComponentUpdate = function(n4) {
      this.props.value !== n4.value && u3.forEach(function(n5) {
        n5.__e = true, M(n5);
      });
    }, this.sub = function(n4) {
      u3.add(n4);
      var l3 = n4.componentWillUnmount;
      n4.componentWillUnmount = function() {
        u3 && u3.delete(n4), l3 && l3.call(n4);
      };
    }), n3.children;
  } };
  return u2.Provider.__ = u2.Consumer.contextType = u2;
}
n = v$2.slice, l$3 = { __e: function(n2, l2, u2, t2) {
  for (var i2, r2, o2; l2 = l2.__; ) if ((i2 = l2.__c) && !i2.__) try {
    if ((r2 = i2.constructor) && null != r2.getDerivedStateFromError && (i2.setState(r2.getDerivedStateFromError(n2)), o2 = i2.__d), null != i2.componentDidCatch && (i2.componentDidCatch(n2, t2 || {}), o2 = i2.__d), o2) return i2.__E = i2;
  } catch (l3) {
    n2 = l3;
  }
  throw n2;
} }, u$3 = 0, t$2 = function(n2) {
  return null != n2 && null == n2.constructor;
}, x$1.prototype.setState = function(n2, l2) {
  var u2;
  u2 = null != this.__s && this.__s !== this.state ? this.__s : this.__s = w$2({}, this.state), "function" == typeof n2 && (n2 = n2(w$2({}, u2), this.props)), n2 && w$2(u2, n2), null != n2 && this.__v && (l2 && this._sb.push(l2), M(this));
}, x$1.prototype.forceUpdate = function(n2) {
  this.__v && (this.__e = true, n2 && this.__h.push(n2), M(this));
}, x$1.prototype.render = k$1, i$2 = [], o$2 = "function" == typeof Promise ? Promise.prototype.then.bind(Promise.resolve()) : setTimeout, e$2 = function(n2, l2) {
  return n2.__v.__b - l2.__v.__b;
}, P$1.__r = 0, f$3 = /(PointerCapture)$|Capture$/i, c$2 = 0, s$3 = O(false), a$2 = O(true), h$3 = 0;
var f$2 = 0;
function u$2(e2, t2, n2, o2, i2, u2) {
  t2 || (t2 = {});
  var a2, c2, p2 = t2;
  if ("ref" in p2) for (c2 in p2 = {}, t2) "ref" == c2 ? a2 = t2[c2] : p2[c2] = t2[c2];
  var l2 = { type: e2, props: p2, key: n2, ref: a2, __k: null, __: null, __b: 0, __e: null, __c: null, constructor: void 0, __v: --f$2, __i: -1, __u: 0, __source: i2, __self: u2 };
  if ("function" == typeof e2 && (a2 = e2.defaultProps)) for (c2 in a2) void 0 === p2[c2] && (p2[c2] = a2[c2]);
  return l$3.vnode && l$3.vnode(l2), l2;
}
var t$1, r$1, u$1, i$1, o$1 = 0, f$1 = [], c$1 = l$3, e$1 = c$1.__b, a$1 = c$1.__r, v$1 = c$1.diffed, l$2 = c$1.__c, m = c$1.unmount, s$2 = c$1.__;
function d$2(n2, t2) {
  c$1.__h && c$1.__h(r$1, n2, o$1 || t2), o$1 = 0;
  var u2 = r$1.__H || (r$1.__H = { __: [], __h: [] });
  return n2 >= u2.__.length && u2.__.push({}), u2.__[n2];
}
function h$2(n2) {
  return o$1 = 1, p$2(D, n2);
}
function p$2(n2, u2, i2) {
  var o2 = d$2(t$1++, 2);
  if (o2.t = n2, !o2.__c && (o2.__ = [i2 ? i2(u2) : D(void 0, u2), function(n3) {
    var t2 = o2.__N ? o2.__N[0] : o2.__[0], r2 = o2.t(t2, n3);
    t2 !== r2 && (o2.__N = [r2, o2.__[1]], o2.__c.setState({}));
  }], o2.__c = r$1, !r$1.u)) {
    var f2 = function(n3, t2, r2) {
      if (!o2.__c.__H) return true;
      var u3 = o2.__c.__H.__.filter(function(n4) {
        return !!n4.__c;
      });
      if (u3.every(function(n4) {
        return !n4.__N;
      })) return !c2 || c2.call(this, n3, t2, r2);
      var i3 = o2.__c.props !== n3;
      return u3.forEach(function(n4) {
        if (n4.__N) {
          var t3 = n4.__[0];
          n4.__ = n4.__N, n4.__N = void 0, t3 !== n4.__[0] && (i3 = true);
        }
      }), c2 && c2.call(this, n3, t2, r2) || i3;
    };
    r$1.u = true;
    var c2 = r$1.shouldComponentUpdate, e2 = r$1.componentWillUpdate;
    r$1.componentWillUpdate = function(n3, t2, r2) {
      if (this.__e) {
        var u3 = c2;
        c2 = void 0, f2(n3, t2, r2), c2 = u3;
      }
      e2 && e2.call(this, n3, t2, r2);
    }, r$1.shouldComponentUpdate = f2;
  }
  return o2.__N || o2.__;
}
function y$1(n2, u2) {
  var i2 = d$2(t$1++, 3);
  !c$1.__s && C(i2.__H, u2) && (i2.__ = n2, i2.i = u2, r$1.__H.__h.push(i2));
}
function A(n2) {
  return o$1 = 5, T$1(function() {
    return { current: n2 };
  }, []);
}
function T$1(n2, r2) {
  var u2 = d$2(t$1++, 7);
  return C(u2.__H, r2) && (u2.__ = n2(), u2.__H = r2, u2.__h = n2), u2.__;
}
function x(n2) {
  var u2 = r$1.context[n2.__c], i2 = d$2(t$1++, 9);
  return i2.c = n2, u2 ? (null == i2.__ && (i2.__ = true, u2.sub(r$1)), u2.props.value) : n2.__;
}
function j$1() {
  for (var n2; n2 = f$1.shift(); ) if (n2.__P && n2.__H) try {
    n2.__H.__h.forEach(z), n2.__H.__h.forEach(B$1), n2.__H.__h = [];
  } catch (t2) {
    n2.__H.__h = [], c$1.__e(t2, n2.__v);
  }
}
c$1.__b = function(n2) {
  r$1 = null, e$1 && e$1(n2);
}, c$1.__ = function(n2, t2) {
  n2 && t2.__k && t2.__k.__m && (n2.__m = t2.__k.__m), s$2 && s$2(n2, t2);
}, c$1.__r = function(n2) {
  a$1 && a$1(n2), t$1 = 0;
  var i2 = (r$1 = n2.__c).__H;
  i2 && (u$1 === r$1 ? (i2.__h = [], r$1.__h = [], i2.__.forEach(function(n3) {
    n3.__N && (n3.__ = n3.__N), n3.i = n3.__N = void 0;
  })) : (i2.__h.forEach(z), i2.__h.forEach(B$1), i2.__h = [], t$1 = 0)), u$1 = r$1;
}, c$1.diffed = function(n2) {
  v$1 && v$1(n2);
  var t2 = n2.__c;
  t2 && t2.__H && (t2.__H.__h.length && (1 !== f$1.push(t2) && i$1 === c$1.requestAnimationFrame || ((i$1 = c$1.requestAnimationFrame) || w$1)(j$1)), t2.__H.__.forEach(function(n3) {
    n3.i && (n3.__H = n3.i), n3.i = void 0;
  })), u$1 = r$1 = null;
}, c$1.__c = function(n2, t2) {
  t2.some(function(n3) {
    try {
      n3.__h.forEach(z), n3.__h = n3.__h.filter(function(n4) {
        return !n4.__ || B$1(n4);
      });
    } catch (r2) {
      t2.some(function(n4) {
        n4.__h && (n4.__h = []);
      }), t2 = [], c$1.__e(r2, n3.__v);
    }
  }), l$2 && l$2(n2, t2);
}, c$1.unmount = function(n2) {
  m && m(n2);
  var t2, r2 = n2.__c;
  r2 && r2.__H && (r2.__H.__.forEach(function(n3) {
    try {
      z(n3);
    } catch (n4) {
      t2 = n4;
    }
  }), r2.__H = void 0, t2 && c$1.__e(t2, r2.__v));
};
var k = "function" == typeof requestAnimationFrame;
function w$1(n2) {
  var t2, r2 = function() {
    clearTimeout(u2), k && cancelAnimationFrame(t2), setTimeout(n2);
  }, u2 = setTimeout(r2, 100);
  k && (t2 = requestAnimationFrame(r2));
}
function z(n2) {
  var t2 = r$1, u2 = n2.__c;
  "function" == typeof u2 && (n2.__c = void 0, u2()), r$1 = t2;
}
function B$1(n2) {
  var t2 = r$1;
  n2.__c = n2.__(), r$1 = t2;
}
function C(n2, t2) {
  return !n2 || n2.length !== t2.length || t2.some(function(t3, r2) {
    return t3 !== n2[r2];
  });
}
function D(n2, t2) {
  return "function" == typeof t2 ? t2(n2) : t2;
}
function g$1(n2, t2) {
  for (var e2 in t2) n2[e2] = t2[e2];
  return n2;
}
function E$1(n2, t2) {
  for (var e2 in n2) if ("__source" !== e2 && !(e2 in t2)) return true;
  for (var r2 in t2) if ("__source" !== r2 && n2[r2] !== t2[r2]) return true;
  return false;
}
function N(n2, t2) {
  this.props = n2, this.context = t2;
}
(N.prototype = new x$1()).isPureReactComponent = true, N.prototype.shouldComponentUpdate = function(n2, t2) {
  return E$1(this.props, n2) || E$1(this.state, t2);
};
var T = l$3.__b;
l$3.__b = function(n2) {
  n2.type && n2.type.__f && n2.ref && (n2.props.ref = n2.ref, n2.ref = null), T && T(n2);
};
var F = l$3.__e;
l$3.__e = function(n2, t2, e2, r2) {
  if (n2.then) {
    for (var u2, o2 = t2; o2 = o2.__; ) if ((u2 = o2.__c) && u2.__c) return null == t2.__e && (t2.__e = e2.__e, t2.__k = e2.__k), u2.__c(n2, t2);
  }
  F(n2, t2, e2, r2);
};
var U = l$3.unmount;
function V(n2, t2, e2) {
  return n2 && (n2.__c && n2.__c.__H && (n2.__c.__H.__.forEach(function(n3) {
    "function" == typeof n3.__c && n3.__c();
  }), n2.__c.__H = null), null != (n2 = g$1({}, n2)).__c && (n2.__c.__P === e2 && (n2.__c.__P = t2), n2.__c = null), n2.__k = n2.__k && n2.__k.map(function(n3) {
    return V(n3, t2, e2);
  })), n2;
}
function W(n2, t2, e2) {
  return n2 && e2 && (n2.__v = null, n2.__k = n2.__k && n2.__k.map(function(n3) {
    return W(n3, t2, e2);
  }), n2.__c && n2.__c.__P === t2 && (n2.__e && e2.appendChild(n2.__e), n2.__c.__e = true, n2.__c.__P = e2)), n2;
}
function P() {
  this.__u = 0, this.o = null, this.__b = null;
}
function j(n2) {
  var t2 = n2.__.__c;
  return t2 && t2.__a && t2.__a(n2);
}
function B() {
  this.i = null, this.l = null;
}
l$3.unmount = function(n2) {
  var t2 = n2.__c;
  t2 && t2.__R && t2.__R(), t2 && 32 & n2.__u && (n2.type = null), U && U(n2);
}, (P.prototype = new x$1()).__c = function(n2, t2) {
  var e2 = t2.__c, r2 = this;
  null == r2.o && (r2.o = []), r2.o.push(e2);
  var u2 = j(r2.__v), o2 = false, i2 = function() {
    o2 || (o2 = true, e2.__R = null, u2 ? u2(c2) : c2());
  };
  e2.__R = i2;
  var c2 = function() {
    if (!--r2.__u) {
      if (r2.state.__a) {
        var n3 = r2.state.__a;
        r2.__v.__k[0] = W(n3, n3.__c.__P, n3.__c.__O);
      }
      var t3;
      for (r2.setState({ __a: r2.__b = null }); t3 = r2.o.pop(); ) t3.forceUpdate();
    }
  };
  r2.__u++ || 32 & t2.__u || r2.setState({ __a: r2.__b = r2.__v.__k[0] }), n2.then(i2, i2);
}, P.prototype.componentWillUnmount = function() {
  this.o = [];
}, P.prototype.render = function(n2, e2) {
  if (this.__b) {
    if (this.__v.__k) {
      var r2 = document.createElement("div"), o2 = this.__v.__k[0].__c;
      this.__v.__k[0] = V(this.__b, r2, o2.__O = o2.__P);
    }
    this.__b = null;
  }
  var i2 = e2.__a && g$2(k$1, null, n2.fallback);
  return i2 && (i2.__u &= -33), [g$2(k$1, null, e2.__a ? null : n2.children), i2];
};
var H = function(n2, t2, e2) {
  if (++e2[1] === e2[0] && n2.l.delete(t2), n2.props.revealOrder && ("t" !== n2.props.revealOrder[0] || !n2.l.size)) for (e2 = n2.i; e2; ) {
    for (; e2.length > 3; ) e2.pop()();
    if (e2[1] < e2[0]) break;
    n2.i = e2 = e2[2];
  }
};
function Z(n2) {
  return this.getChildContext = function() {
    return n2.context;
  }, n2.children;
}
function Y(n2) {
  var e2 = this, r2 = n2.h;
  e2.componentWillUnmount = function() {
    D$1(null, e2.v), e2.v = null, e2.h = null;
  }, e2.h && e2.h !== r2 && e2.componentWillUnmount(), e2.v || (e2.h = r2, e2.v = { nodeType: 1, parentNode: r2, childNodes: [], contains: function() {
    return true;
  }, appendChild: function(n3) {
    this.childNodes.push(n3), e2.h.appendChild(n3);
  }, insertBefore: function(n3, t2) {
    this.childNodes.push(n3), e2.h.insertBefore(n3, t2);
  }, removeChild: function(n3) {
    this.childNodes.splice(this.childNodes.indexOf(n3) >>> 1, 1), e2.h.removeChild(n3);
  } }), D$1(g$2(Z, { context: e2.context }, n2.__v), e2.v);
}
function $(n2, e2) {
  var r2 = g$2(Y, { __v: n2, h: e2 });
  return r2.containerInfo = e2, r2;
}
(B.prototype = new x$1()).__a = function(n2) {
  var t2 = this, e2 = j(t2.__v), r2 = t2.l.get(n2);
  return r2[0]++, function(u2) {
    var o2 = function() {
      t2.props.revealOrder ? (r2.push(u2), H(t2, n2, r2)) : u2();
    };
    e2 ? e2(o2) : o2();
  };
}, B.prototype.render = function(n2) {
  this.i = null, this.l = /* @__PURE__ */ new Map();
  var t2 = H$1(n2.children);
  n2.revealOrder && "b" === n2.revealOrder[0] && t2.reverse();
  for (var e2 = t2.length; e2--; ) this.l.set(t2[e2], this.i = [1, 0, this.i]);
  return n2.children;
}, B.prototype.componentDidUpdate = B.prototype.componentDidMount = function() {
  var n2 = this;
  this.l.forEach(function(t2, e2) {
    H(n2, e2, t2);
  });
};
var q = "undefined" != typeof Symbol && Symbol.for && Symbol.for("react.element") || 60103, G = /^(?:accent|alignment|arabic|baseline|cap|clip(?!PathU)|color|dominant|fill|flood|font|glyph(?!R)|horiz|image(!S)|letter|lighting|marker(?!H|W|U)|overline|paint|pointer|shape|stop|strikethrough|stroke|text(?!L)|transform|underline|unicode|units|v|vector|vert|word|writing|x(?!C))[A-Z]/, J = /^on(Ani|Tra|Tou|BeforeInp|Compo)/, K = /[A-Z0-9]/g, Q = "undefined" != typeof document, X = function(n2) {
  return ("undefined" != typeof Symbol && "symbol" == typeof Symbol() ? /fil|che|rad/ : /fil|che|ra/).test(n2);
};
x$1.prototype.isReactComponent = {}, ["componentWillMount", "componentWillReceiveProps", "componentWillUpdate"].forEach(function(t2) {
  Object.defineProperty(x$1.prototype, t2, { configurable: true, get: function() {
    return this["UNSAFE_" + t2];
  }, set: function(n2) {
    Object.defineProperty(this, t2, { configurable: true, writable: true, value: n2 });
  } });
});
var en = l$3.event;
function rn() {
}
function un() {
  return this.cancelBubble;
}
function on() {
  return this.defaultPrevented;
}
l$3.event = function(n2) {
  return en && (n2 = en(n2)), n2.persist = rn, n2.isPropagationStopped = un, n2.isDefaultPrevented = on, n2.nativeEvent = n2;
};
var ln = { enumerable: false, configurable: true, get: function() {
  return this.class;
} }, fn = l$3.vnode;
l$3.vnode = function(n2) {
  "string" == typeof n2.type && function(n3) {
    var t2 = n3.props, e2 = n3.type, u2 = {}, o2 = -1 === e2.indexOf("-");
    for (var i2 in t2) {
      var c2 = t2[i2];
      if (!("value" === i2 && "defaultValue" in t2 && null == c2 || Q && "children" === i2 && "noscript" === e2 || "class" === i2 || "className" === i2)) {
        var l2 = i2.toLowerCase();
        "defaultValue" === i2 && "value" in t2 && null == t2.value ? i2 = "value" : "download" === i2 && true === c2 ? c2 = "" : "translate" === l2 && "no" === c2 ? c2 = false : "o" === l2[0] && "n" === l2[1] ? "ondoubleclick" === l2 ? i2 = "ondblclick" : "onchange" !== l2 || "input" !== e2 && "textarea" !== e2 || X(t2.type) ? "onfocus" === l2 ? i2 = "onfocusin" : "onblur" === l2 ? i2 = "onfocusout" : J.test(i2) && (i2 = l2) : l2 = i2 = "oninput" : o2 && G.test(i2) ? i2 = i2.replace(K, "-$&").toLowerCase() : null === c2 && (c2 = void 0), "oninput" === l2 && u2[i2 = l2] && (i2 = "oninputCapture"), u2[i2] = c2;
      }
    }
    "select" == e2 && u2.multiple && Array.isArray(u2.value) && (u2.value = H$1(t2.children).forEach(function(n4) {
      n4.props.selected = -1 != u2.value.indexOf(n4.props.value);
    })), "select" == e2 && null != u2.defaultValue && (u2.value = H$1(t2.children).forEach(function(n4) {
      n4.props.selected = u2.multiple ? -1 != u2.defaultValue.indexOf(n4.props.value) : u2.defaultValue == n4.props.value;
    })), t2.class && !t2.className ? (u2.class = t2.class, Object.defineProperty(u2, "className", ln)) : (t2.className && !t2.class || t2.class && t2.className) && (u2.class = u2.className = t2.className), n3.props = u2;
  }(n2), n2.$$typeof = q, fn && fn(n2);
};
var an = l$3.__r;
l$3.__r = function(n2) {
  an && an(n2), n2.__c;
};
var sn = l$3.diffed;
l$3.diffed = function(n2) {
  sn && sn(n2);
  var t2 = n2.props, e2 = n2.__e;
  null != e2 && "textarea" === n2.type && "value" in t2 && t2.value !== e2.value && (e2.value = null == t2.value ? "" : t2.value);
};
var i = Symbol.for("preact-signals");
function t() {
  if (!(s$1 > 1)) {
    var i2, t2 = false;
    while (void 0 !== h$1) {
      var r2 = h$1;
      h$1 = void 0;
      f++;
      while (void 0 !== r2) {
        var o2 = r2.o;
        r2.o = void 0;
        r2.f &= -3;
        if (!(8 & r2.f) && c(r2)) try {
          r2.c();
        } catch (r3) {
          if (!t2) {
            i2 = r3;
            t2 = true;
          }
        }
        r2 = o2;
      }
    }
    f = 0;
    s$1--;
    if (t2) throw i2;
  } else s$1--;
}
function r(i2) {
  if (s$1 > 0) return i2();
  s$1++;
  try {
    return i2();
  } finally {
    t();
  }
}
var o = void 0;
var h$1 = void 0, s$1 = 0, f = 0, v = 0;
function e(i2) {
  if (void 0 !== o) {
    var t2 = i2.n;
    if (void 0 === t2 || t2.t !== o) {
      t2 = { i: 0, S: i2, p: o.s, n: void 0, t: o, e: void 0, x: void 0, r: t2 };
      if (void 0 !== o.s) o.s.n = t2;
      o.s = t2;
      i2.n = t2;
      if (32 & o.f) i2.S(t2);
      return t2;
    } else if (-1 === t2.i) {
      t2.i = 0;
      if (void 0 !== t2.n) {
        t2.n.p = t2.p;
        if (void 0 !== t2.p) t2.p.n = t2.n;
        t2.p = o.s;
        t2.n = void 0;
        o.s.n = t2;
        o.s = t2;
      }
      return t2;
    }
  }
}
function u(i2) {
  this.v = i2;
  this.i = 0;
  this.n = void 0;
  this.t = void 0;
}
u.prototype.brand = i;
u.prototype.h = function() {
  return true;
};
u.prototype.S = function(i2) {
  if (this.t !== i2 && void 0 === i2.e) {
    i2.x = this.t;
    if (void 0 !== this.t) this.t.e = i2;
    this.t = i2;
  }
};
u.prototype.U = function(i2) {
  if (void 0 !== this.t) {
    var t2 = i2.e, r2 = i2.x;
    if (void 0 !== t2) {
      t2.x = r2;
      i2.e = void 0;
    }
    if (void 0 !== r2) {
      r2.e = t2;
      i2.x = void 0;
    }
    if (i2 === this.t) this.t = r2;
  }
};
u.prototype.subscribe = function(i2) {
  var t2 = this;
  return E(function() {
    var r2 = t2.value, n2 = o;
    o = void 0;
    try {
      i2(r2);
    } finally {
      o = n2;
    }
  });
};
u.prototype.valueOf = function() {
  return this.value;
};
u.prototype.toString = function() {
  return this.value + "";
};
u.prototype.toJSON = function() {
  return this.value;
};
u.prototype.peek = function() {
  var i2 = o;
  o = void 0;
  try {
    return this.value;
  } finally {
    o = i2;
  }
};
Object.defineProperty(u.prototype, "value", { get: function() {
  var i2 = e(this);
  if (void 0 !== i2) i2.i = this.i;
  return this.v;
}, set: function(i2) {
  if (i2 !== this.v) {
    if (f > 100) throw new Error("Cycle detected");
    this.v = i2;
    this.i++;
    v++;
    s$1++;
    try {
      for (var r2 = this.t; void 0 !== r2; r2 = r2.x) r2.t.N();
    } finally {
      t();
    }
  }
} });
function d$1(i2) {
  return new u(i2);
}
function c(i2) {
  for (var t2 = i2.s; void 0 !== t2; t2 = t2.n) if (t2.S.i !== t2.i || !t2.S.h() || t2.S.i !== t2.i) return true;
  return false;
}
function a(i2) {
  for (var t2 = i2.s; void 0 !== t2; t2 = t2.n) {
    var r2 = t2.S.n;
    if (void 0 !== r2) t2.r = r2;
    t2.S.n = t2;
    t2.i = -1;
    if (void 0 === t2.n) {
      i2.s = t2;
      break;
    }
  }
}
function l$1(i2) {
  var t2 = i2.s, r2 = void 0;
  while (void 0 !== t2) {
    var o2 = t2.p;
    if (-1 === t2.i) {
      t2.S.U(t2);
      if (void 0 !== o2) o2.n = t2.n;
      if (void 0 !== t2.n) t2.n.p = o2;
    } else r2 = t2;
    t2.S.n = t2.r;
    if (void 0 !== t2.r) t2.r = void 0;
    t2 = o2;
  }
  i2.s = r2;
}
function y(i2) {
  u.call(this, void 0);
  this.x = i2;
  this.s = void 0;
  this.g = v - 1;
  this.f = 4;
}
(y.prototype = new u()).h = function() {
  this.f &= -3;
  if (1 & this.f) return false;
  if (32 == (36 & this.f)) return true;
  this.f &= -5;
  if (this.g === v) return true;
  this.g = v;
  this.f |= 1;
  if (this.i > 0 && !c(this)) {
    this.f &= -2;
    return true;
  }
  var i2 = o;
  try {
    a(this);
    o = this;
    var t2 = this.x();
    if (16 & this.f || this.v !== t2 || 0 === this.i) {
      this.v = t2;
      this.f &= -17;
      this.i++;
    }
  } catch (i3) {
    this.v = i3;
    this.f |= 16;
    this.i++;
  }
  o = i2;
  l$1(this);
  this.f &= -2;
  return true;
};
y.prototype.S = function(i2) {
  if (void 0 === this.t) {
    this.f |= 36;
    for (var t2 = this.s; void 0 !== t2; t2 = t2.n) t2.S.S(t2);
  }
  u.prototype.S.call(this, i2);
};
y.prototype.U = function(i2) {
  if (void 0 !== this.t) {
    u.prototype.U.call(this, i2);
    if (void 0 === this.t) {
      this.f &= -33;
      for (var t2 = this.s; void 0 !== t2; t2 = t2.n) t2.S.U(t2);
    }
  }
};
y.prototype.N = function() {
  if (!(2 & this.f)) {
    this.f |= 6;
    for (var i2 = this.t; void 0 !== i2; i2 = i2.x) i2.t.N();
  }
};
Object.defineProperty(y.prototype, "value", { get: function() {
  if (1 & this.f) throw new Error("Cycle detected");
  var i2 = e(this);
  this.h();
  if (void 0 !== i2) i2.i = this.i;
  if (16 & this.f) throw this.v;
  return this.v;
} });
function w(i2) {
  return new y(i2);
}
function _(i2) {
  var r2 = i2.u;
  i2.u = void 0;
  if ("function" == typeof r2) {
    s$1++;
    var n2 = o;
    o = void 0;
    try {
      r2();
    } catch (t2) {
      i2.f &= -2;
      i2.f |= 8;
      g(i2);
      throw t2;
    } finally {
      o = n2;
      t();
    }
  }
}
function g(i2) {
  for (var t2 = i2.s; void 0 !== t2; t2 = t2.n) t2.S.U(t2);
  i2.x = void 0;
  i2.s = void 0;
  _(i2);
}
function p$1(i2) {
  if (o !== this) throw new Error("Out-of-order effect");
  l$1(this);
  o = i2;
  this.f &= -2;
  if (8 & this.f) g(this);
  t();
}
function b(i2) {
  this.x = i2;
  this.u = void 0;
  this.s = void 0;
  this.o = void 0;
  this.f = 32;
}
b.prototype.c = function() {
  var i2 = this.S();
  try {
    if (8 & this.f) return;
    if (void 0 === this.x) return;
    var t2 = this.x();
    if ("function" == typeof t2) this.u = t2;
  } finally {
    i2();
  }
};
b.prototype.S = function() {
  if (1 & this.f) throw new Error("Cycle detected");
  this.f |= 1;
  this.f &= -9;
  _(this);
  a(this);
  s$1++;
  var i2 = o;
  o = this;
  return p$1.bind(this, i2);
};
b.prototype.N = function() {
  if (!(2 & this.f)) {
    this.f |= 2;
    this.o = h$1;
    h$1 = this;
  }
};
b.prototype.d = function() {
  this.f |= 8;
  if (!(1 & this.f)) g(this);
};
function E(i2) {
  var t2 = new b(i2);
  try {
    t2.c();
  } catch (i3) {
    t2.d();
    throw i3;
  }
  return t2.d.bind(t2);
}
var s;
function l(i2, n2) {
  l$3[i2] = n2.bind(null, l$3[i2] || function() {
  });
}
function d(i2) {
  if (s) s();
  s = i2 && i2.S();
}
function h(i2) {
  var r2 = this, f2 = i2.data, o2 = useSignal(f2);
  o2.value = f2;
  var e2 = T$1(function() {
    var i3 = r2.__v;
    while (i3 = i3.__) if (i3.__c) {
      i3.__c.__$f |= 4;
      break;
    }
    r2.__$u.c = function() {
      var i4, t2 = r2.__$u.S(), f3 = e2.value;
      t2();
      if (t$2(f3) || 3 !== (null == (i4 = r2.base) ? void 0 : i4.nodeType)) {
        r2.__$f |= 1;
        r2.setState({});
      } else r2.base.data = f3;
    };
    return w(function() {
      var i4 = o2.value.value;
      return 0 === i4 ? 0 : true === i4 ? "" : i4 || "";
    });
  }, []);
  return e2.value;
}
h.displayName = "_st";
Object.defineProperties(u.prototype, { constructor: { configurable: true, value: void 0 }, type: { configurable: true, value: h }, props: { configurable: true, get: function() {
  return { data: this };
} }, __b: { configurable: true, value: 1 } });
l("__b", function(i2, r2) {
  if ("string" == typeof r2.type) {
    var n2, t2 = r2.props;
    for (var f2 in t2) if ("children" !== f2) {
      var o2 = t2[f2];
      if (o2 instanceof u) {
        if (!n2) r2.__np = n2 = {};
        n2[f2] = o2;
        t2[f2] = o2.peek();
      }
    }
  }
  i2(r2);
});
l("__r", function(i2, r2) {
  d();
  var n2, t2 = r2.__c;
  if (t2) {
    t2.__$f &= -2;
    if (void 0 === (n2 = t2.__$u)) t2.__$u = n2 = function(i3) {
      var r3;
      E(function() {
        r3 = this;
      });
      r3.c = function() {
        t2.__$f |= 1;
        t2.setState({});
      };
      return r3;
    }();
  }
  d(n2);
  i2(r2);
});
l("__e", function(i2, r2, n2, t2) {
  d();
  i2(r2, n2, t2);
});
l("diffed", function(i2, r2) {
  d();
  var n2;
  if ("string" == typeof r2.type && (n2 = r2.__e)) {
    var t2 = r2.__np, f2 = r2.props;
    if (t2) {
      var o2 = n2.U;
      if (o2) for (var e2 in o2) {
        var u2 = o2[e2];
        if (void 0 !== u2 && !(e2 in t2)) {
          u2.d();
          o2[e2] = void 0;
        }
      }
      else n2.U = o2 = {};
      for (var a2 in t2) {
        var c2 = o2[a2], s2 = t2[a2];
        if (void 0 === c2) {
          c2 = p(n2, a2, s2, f2);
          o2[a2] = c2;
        } else c2.o(s2, f2);
      }
    }
  }
  i2(r2);
});
function p(i2, r2, n2, t2) {
  var f2 = r2 in i2 && void 0 === i2.ownerSVGElement, o2 = d$1(n2);
  return { o: function(i3, r3) {
    o2.value = i3;
    t2 = r3;
  }, d: E(function() {
    var n3 = o2.value.value;
    if (t2[r2] !== n3) {
      t2[r2] = n3;
      if (f2) i2[r2] = n3;
      else if (n3) i2.setAttribute(r2, n3);
      else i2.removeAttribute(r2);
    }
  }) };
}
l("unmount", function(i2, r2) {
  if ("string" == typeof r2.type) {
    var n2 = r2.__e;
    if (n2) {
      var t2 = n2.U;
      if (t2) {
        n2.U = void 0;
        for (var f2 in t2) {
          var o2 = t2[f2];
          if (o2) o2.d();
        }
      }
    }
  } else {
    var e2 = r2.__c;
    if (e2) {
      var u2 = e2.__$u;
      if (u2) {
        e2.__$u = void 0;
        u2.d();
      }
    }
  }
  i2(r2);
});
l("__h", function(i2, r2, n2, t2) {
  if (t2 < 3 || 9 === t2) r2.__$f |= 2;
  i2(r2, n2, t2);
});
x$1.prototype.shouldComponentUpdate = function(i2, r2) {
  var n2 = this.__$u, t2 = n2 && void 0 !== n2.s;
  for (var f2 in r2) return true;
  if (this.__f || "boolean" == typeof this.u && true === this.u) {
    if (!(t2 || 2 & this.__$f || 4 & this.__$f)) return true;
    if (1 & this.__$f) return true;
  } else {
    if (!(t2 || 4 & this.__$f)) return true;
    if (3 & this.__$f) return true;
  }
  for (var o2 in i2) if ("__source" !== o2 && i2[o2] !== this.props[o2]) return true;
  for (var e2 in this.props) if (!(e2 in i2)) return true;
  return false;
};
function useSignal(i2) {
  return T$1(function() {
    return d$1(i2);
  }, []);
}
function useSignalEffect(i2) {
  var r2 = A(i2);
  r2.current = i2;
  y$1(function() {
    return E(function() {
      return r2.current();
    });
  }, []);
}
const AppContext$1 = J$1({});
const DateFormats$1 = {
  DATE_STRING: /^\d{4}-\d{2}-\d{2}$/,
  TIME_STRING: /^\d{2}:\d{2}$/,
  DATE_TIME_STRING: /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/
};
let InvalidDateTimeError$1 = class InvalidDateTimeError extends Error {
  constructor(dateTimeSpecification) {
    super(`Invalid date time specification: ${dateTimeSpecification}`);
  }
};
const toJSDate$1 = (dateTimeSpecification) => {
  if (!DateFormats$1.DATE_TIME_STRING.test(dateTimeSpecification) && !DateFormats$1.DATE_STRING.test(dateTimeSpecification))
    throw new InvalidDateTimeError$1(dateTimeSpecification);
  return new Date(
    Number(dateTimeSpecification.slice(0, 4)),
    Number(dateTimeSpecification.slice(5, 7)) - 1,
    Number(dateTimeSpecification.slice(8, 10)),
    Number(dateTimeSpecification.slice(11, 13)),
    // for date strings this will be 0
    Number(dateTimeSpecification.slice(14, 16))
    // for date strings this will be 0
  );
};
const toIntegers = (dateTimeSpecification) => {
  const hours = dateTimeSpecification.slice(11, 13), minutes = dateTimeSpecification.slice(14, 16);
  return {
    year: Number(dateTimeSpecification.slice(0, 4)),
    month: Number(dateTimeSpecification.slice(5, 7)) - 1,
    date: Number(dateTimeSpecification.slice(8, 10)),
    hours: hours !== "" ? Number(hours) : void 0,
    minutes: minutes !== "" ? Number(minutes) : void 0
  };
};
const toLocalizedMonth = (date, locale) => {
  return date.toLocaleString(locale, { month: "long" });
};
const toLocalizedDateString = (date, locale) => {
  return date.toLocaleString(locale, {
    month: "numeric",
    day: "numeric",
    year: "numeric"
  });
};
const getOneLetterDayNames = (week, locale) => {
  return week.map((date) => {
    return date.toLocaleString(locale, { weekday: "short" }).charAt(0);
  });
};
const getDayNameShort = (date, locale) => date.toLocaleString(locale, { weekday: "short" });
const getDayNamesShort = (week, locale) => {
  return week.map((date) => getDayNameShort(date, locale));
};
const getOneLetterOrShortDayNames = (week, locale) => {
  if (["zh-cn", "zh-tw", "ca-es"].includes(locale.toLowerCase())) {
    return getDayNamesShort(week, locale);
  }
  return getOneLetterDayNames(week, locale);
};
var img = "data:image/svg+xml,%3c%3fxml version='1.0' encoding='utf-8'%3f%3e%3c!-- Uploaded to: SVG Repo%2c www.svgrepo.com%2c Generator: SVG Repo Mixer Tools --%3e%3csvg width='800px' height='800px' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M6 9L12 15L18 9' stroke='%23DED8E1' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'/%3e%3c/svg%3e";
const randomStringId = () => "s" + Math.random().toString(36).substring(2, 11);
const isKeyEnterOrSpace = (keyboardEvent) => keyboardEvent.key === "Enter" || keyboardEvent.key === " ";
function AppInput() {
  const datePickerInputId = randomStringId();
  const datePickerLabelId = randomStringId();
  const inputWrapperId = randomStringId();
  const $app = x(AppContext$1);
  const getLocalizedDate2 = (dateString) => {
    if (dateString === "")
      return $app.translate("MM/DD/YYYY");
    return toLocalizedDateString(toJSDate$1(dateString), $app.config.locale.value);
  };
  y$1(() => {
    $app.datePickerState.inputDisplayedValue.value = getLocalizedDate2($app.datePickerState.selectedDate.value);
  }, [$app.datePickerState.selectedDate.value, $app.config.locale.value]);
  const [wrapperClasses, setWrapperClasses] = h$2([]);
  const setInputElement = () => {
    const inputWrapperEl = document.getElementById(inputWrapperId);
    $app.datePickerState.inputWrapperElement.value = inputWrapperEl instanceof HTMLDivElement ? inputWrapperEl : void 0;
  };
  y$1(() => {
    if ($app.config.teleportTo)
      setInputElement();
    const newClasses = ["sx__date-input-wrapper"];
    if ($app.datePickerState.isOpen.value)
      newClasses.push("sx__date-input--active");
    setWrapperClasses(newClasses);
  }, [$app.datePickerState.isOpen.value]);
  const handleKeyUp = (event) => {
    if (event.key === "Enter")
      handleInputValue(event);
  };
  const handleInputValue = (event) => {
    event.stopPropagation();
    try {
      $app.datePickerState.inputDisplayedValue.value = event.target.value;
      $app.datePickerState.close();
    } catch (e2) {
    }
  };
  y$1(() => {
    const inputElement = document.getElementById(datePickerInputId);
    if (inputElement === null)
      return;
    inputElement.addEventListener("change", handleInputValue);
    return () => inputElement.removeEventListener("change", handleInputValue);
  });
  const handleClick = (event) => {
    handleInputValue(event);
    $app.datePickerState.open();
  };
  const handleButtonKeyDown = (keyboardEvent) => {
    if (isKeyEnterOrSpace(keyboardEvent)) {
      keyboardEvent.preventDefault();
      $app.datePickerState.open();
      setTimeout(() => {
        const element = document.querySelector('[data-focus="true"]');
        if (element instanceof HTMLElement)
          element.focus();
      }, 50);
    }
  };
  return u$2(k$1, { children: u$2("div", { className: wrapperClasses.join(" "), id: inputWrapperId, children: [u$2("label", { for: datePickerInputId, id: datePickerLabelId, className: "sx__date-input-label", children: $app.config.label || $app.translate("Date") }), u$2("input", { id: datePickerInputId, tabIndex: $app.datePickerState.isDisabled.value ? -1 : 0, name: $app.config.name || "date", "aria-describedby": datePickerLabelId, value: $app.datePickerState.inputDisplayedValue.value, "data-testid": "date-picker-input", className: "sx__date-input", onClick: handleClick, onKeyUp: handleKeyUp, type: "text" }), u$2("button", { type: "button", tabIndex: $app.datePickerState.isDisabled.value ? -1 : 0, "aria-label": $app.translate("Choose Date"), onKeyDown: handleButtonKeyDown, onClick: () => $app.datePickerState.open(), className: "sx__date-input-chevron-wrapper", children: u$2("img", { className: "sx__date-input-chevron", src: img, alt: "" }) })] }) });
}
var DatePickerView;
(function(DatePickerView2) {
  DatePickerView2["MONTH_DAYS"] = "month-days";
  DatePickerView2["YEARS"] = "years";
})(DatePickerView || (DatePickerView = {}));
const YEARS_VIEW = "years-view";
const MONTH_VIEW = "months-view";
const DATE_PICKER_WEEK = "date-picker-week";
let NumberRangeError$2 = class NumberRangeError extends Error {
  constructor(min, max) {
    super(`Number must be between ${min} and ${max}.`);
    Object.defineProperty(this, "min", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: min
    });
    Object.defineProperty(this, "max", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: max
    });
  }
};
const doubleDigit$2 = (number) => {
  if (number < 0 || number > 99)
    throw new NumberRangeError$2(0, 99);
  return String(number).padStart(2, "0");
};
const toDateString$1 = (date) => {
  return `${date.getFullYear()}-${doubleDigit$2(date.getMonth() + 1)}-${doubleDigit$2(date.getDate())}`;
};
const toTimeString = (date) => {
  return `${doubleDigit$2(date.getHours())}:${doubleDigit$2(date.getMinutes())}`;
};
const toDateTimeString = (date) => {
  return `${toDateString$1(date)} ${toTimeString(date)}`;
};
const addMonths = (to, nMonths) => {
  const { year, month, date, hours, minutes } = toIntegers(to);
  const isDateTimeString = hours !== void 0 && minutes !== void 0;
  const jsDate = new Date(year, month, date, hours !== null && hours !== void 0 ? hours : 0, minutes !== null && minutes !== void 0 ? minutes : 0);
  let expectedMonth = (jsDate.getMonth() + nMonths) % 12;
  if (expectedMonth < 0)
    expectedMonth += 12;
  jsDate.setMonth(jsDate.getMonth() + nMonths);
  if (jsDate.getMonth() > expectedMonth) {
    jsDate.setDate(0);
  } else if (jsDate.getMonth() < expectedMonth) {
    jsDate.setMonth(jsDate.getMonth() + 1);
    jsDate.setDate(0);
  }
  if (isDateTimeString) {
    return toDateTimeString(jsDate);
  }
  return toDateString$1(jsDate);
};
const addDays = (to, nDays) => {
  const { year, month, date, hours, minutes } = toIntegers(to);
  const isDateTimeString = hours !== void 0 && minutes !== void 0;
  const jsDate = new Date(year, month, date, hours !== null && hours !== void 0 ? hours : 0, minutes !== null && minutes !== void 0 ? minutes : 0);
  jsDate.setDate(jsDate.getDate() + nDays);
  if (isDateTimeString) {
    return toDateTimeString(jsDate);
  }
  return toDateString$1(jsDate);
};
const dateFromDateTime$1 = (dateTime) => {
  return dateTime.slice(0, 10);
};
const timeFromDateTime$1 = (dateTime) => {
  return dateTime.slice(11);
};
const setDateOfMonth = (dateString, date) => {
  dateString = dateString.slice(0, 8) + doubleDigit$2(date) + dateString.slice(10);
  return dateString;
};
const getFirstDayOPreviousMonth = (dateString) => {
  dateString = addMonths(dateString, -1);
  return setDateOfMonth(dateString, 1);
};
const getFirstDayOfNextMonth = (dateString) => {
  dateString = addMonths(dateString, 1);
  return setDateOfMonth(dateString, 1);
};
const setTimeInDateTimeString = (dateTimeString, newTime) => {
  const dateCache = toDateString$1(toJSDate$1(dateTimeString));
  return `${dateCache} ${newTime}`;
};
function Chevron({ direction, onClick, buttonText, disabled = false }) {
  const handleKeyDown = (keyboardEvent) => {
    if (isKeyEnterOrSpace(keyboardEvent))
      onClick();
  };
  return u$2("button", { type: "button", disabled, className: "sx__chevron-wrapper sx__ripple", onMouseUp: onClick, onKeyDown: handleKeyDown, tabIndex: 0, children: u$2("i", { className: `sx__chevron sx__chevron--${direction}`, children: buttonText }) });
}
function MonthViewHeader({ setYearsView }) {
  const $app = x(AppContext$1);
  const dateStringToLocalizedMonthName = (selectedDate) => {
    const selectedDateJS = toJSDate$1(selectedDate);
    return toLocalizedMonth(selectedDateJS, $app.config.locale.value);
  };
  const getYearFrom = (datePickerDate) => {
    return toIntegers(datePickerDate).year;
  };
  const [selectedDateMonthName, setSelectedDateMonthName] = h$2(dateStringToLocalizedMonthName($app.datePickerState.datePickerDate.value));
  const [datePickerYear, setDatePickerYear] = h$2(getYearFrom($app.datePickerState.datePickerDate.value));
  const setPreviousMonth = () => {
    $app.datePickerState.datePickerDate.value = getFirstDayOPreviousMonth($app.datePickerState.datePickerDate.value);
  };
  const setNextMonth = () => {
    $app.datePickerState.datePickerDate.value = getFirstDayOfNextMonth($app.datePickerState.datePickerDate.value);
  };
  y$1(() => {
    setSelectedDateMonthName(dateStringToLocalizedMonthName($app.datePickerState.datePickerDate.value));
    setDatePickerYear(getYearFrom($app.datePickerState.datePickerDate.value));
  }, [$app.datePickerState.datePickerDate.value]);
  const handleOpenYearsView = (e2) => {
    e2.stopPropagation();
    setYearsView();
  };
  return u$2(k$1, { children: u$2("header", { className: "sx__date-picker__month-view-header", children: [u$2(Chevron, { direction: "previous", onClick: () => setPreviousMonth(), buttonText: $app.translate("Previous month") }), u$2("button", { type: "button", className: "sx__date-picker__month-view-header__month-year", onClick: (event) => handleOpenYearsView(event), children: selectedDateMonthName + " " + datePickerYear }), u$2(Chevron, { direction: "next", onClick: () => setNextMonth(), buttonText: $app.translate("Next month") })] }) });
}
function DayNames() {
  const $app = x(AppContext$1);
  const aWeek = $app.timeUnitsImpl.getWeekFor(toJSDate$1($app.datePickerState.datePickerDate.value));
  const dayNames = getOneLetterOrShortDayNames(aWeek, $app.config.locale.value);
  return u$2("div", { className: "sx__date-picker__day-names", children: dayNames.map((dayName) => u$2("span", { "data-testid": "day-name", className: "sx__date-picker__day-name", children: dayName })) });
}
const isToday = (date) => {
  const today = /* @__PURE__ */ new Date();
  return date.getDate() === today.getDate() && date.getMonth() === today.getMonth() && date.getFullYear() === today.getFullYear();
};
const isSameMonth = (date1, date2) => {
  return date1.getMonth() === date2.getMonth() && date1.getFullYear() === date2.getFullYear();
};
function TimeIcon({ strokeColor }) {
  return u$2(k$1, { children: u$2("svg", { className: "sx__event-icon", viewBox: "0 0 24 24", fill: "none", xmlns: "http://www.w3.org/2000/svg", children: [u$2("g", { id: "SVGRepo_bgCarrier", "stroke-width": "0" }), u$2("g", { id: "SVGRepo_tracerCarrier", "stroke-linecap": "round", "stroke-linejoin": "round" }), u$2("g", { id: "SVGRepo_iconCarrier", children: [u$2("path", { d: "M12 8V12L15 15", stroke: strokeColor, "stroke-width": "2", "stroke-linecap": "round" }), u$2("circle", { cx: "12", cy: "12", r: "9", stroke: strokeColor, "stroke-width": "2" })] })] }) });
}
function UserIcon({ strokeColor }) {
  return u$2(k$1, { children: u$2("svg", { className: "sx__event-icon", viewBox: "0 0 24 24", fill: "none", xmlns: "http://www.w3.org/2000/svg", children: [u$2("g", { id: "SVGRepo_bgCarrier", "stroke-width": "0" }), u$2("g", { id: "SVGRepo_tracerCarrier", "stroke-linecap": "round", "stroke-linejoin": "round" }), u$2("g", { id: "SVGRepo_iconCarrier", children: [u$2("path", { d: "M15 7C15 8.65685 13.6569 10 12 10C10.3431 10 9 8.65685 9 7C9 5.34315 10.3431 4 12 4C13.6569 4 15 5.34315 15 7Z", stroke: strokeColor, "stroke-width": "2" }), u$2("path", { d: "M5 19.5C5 15.9101 7.91015 13 11.5 13H12.5C16.0899 13 19 15.9101 19 19.5V20C19 20.5523 18.5523 21 18 21H6C5.44772 21 5 20.5523 5 20V19.5Z", stroke: strokeColor, "stroke-width": "2" })] })] }) });
}
function LocationPinIcon({ strokeColor }) {
  return u$2(k$1, { children: u$2("svg", { className: "sx__event-icon", viewBox: "0 0 24 24", fill: "none", xmlns: "http://www.w3.org/2000/svg", children: [u$2("g", { id: "SVGRepo_bgCarrier", "stroke-width": "0" }), u$2("g", { id: "SVGRepo_tracerCarrier", "stroke-linecap": "round", "stroke-linejoin": "round" }), u$2("g", { id: "SVGRepo_iconCarrier", children: [u$2("g", { "clip-path": "url(#clip0_429_11046)", children: [u$2("rect", { x: "12", y: "11", width: "0.01", height: "0.01", stroke: strokeColor, "stroke-width": "2", "stroke-linejoin": "round" }), u$2("path", { d: "M12 22L17.5 16.5C20.5376 13.4624 20.5376 8.53757 17.5 5.5C14.4624 2.46244 9.53757 2.46244 6.5 5.5C3.46244 8.53757 3.46244 13.4624 6.5 16.5L12 22Z", stroke: strokeColor, "stroke-width": "2", "stroke-linejoin": "round" })] }), u$2("defs", { children: u$2("clipPath", { id: "clip0_429_11046", children: u$2("rect", { width: "24", height: "24", fill: "white" }) }) })] })] }) });
}
const timeStringRegex$2 = /^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
const dateTimeStringRegex$1 = /^(\d{4})-(\d{2})-(\d{2}) (0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
const dateStringRegex$2 = /^(\d{4})-(\d{2})-(\d{2})$/;
let InvalidTimeStringError$2 = class InvalidTimeStringError extends Error {
  constructor(timeString) {
    super(`Invalid time string: ${timeString}`);
  }
};
const minuteTimePointMultiplier$2 = 1.6666666666666667;
const timePointsFromString$2 = (timeString) => {
  if (!timeStringRegex$2.test(timeString) && timeString !== "24:00")
    throw new InvalidTimeStringError$2(timeString);
  const [hoursInt, minutesInt] = timeString.split(":").map((time) => parseInt(time, 10));
  let minutePoints = (minutesInt * minuteTimePointMultiplier$2).toString();
  if (minutePoints.split(".")[0].length < 2)
    minutePoints = `0${minutePoints}`;
  return Number(hoursInt + minutePoints);
};
const timeStringFromTimePoints$1 = (timePoints) => {
  const hours = Math.floor(timePoints / 100);
  const minutes = Math.round(timePoints % 100 / minuteTimePointMultiplier$2);
  return `${doubleDigit$2(hours)}:${doubleDigit$2(minutes)}`;
};
const addTimePointsToDateTime = (dateTimeString, pointsToAdd) => {
  const minutesToAdd = pointsToAdd / minuteTimePointMultiplier$2;
  const jsDate = toJSDate$1(dateTimeString);
  jsDate.setMinutes(jsDate.getMinutes() + minutesToAdd);
  return toDateTimeString(jsDate);
};
var WeekDay$1;
(function(WeekDay2) {
  WeekDay2[WeekDay2["SUNDAY"] = 0] = "SUNDAY";
  WeekDay2[WeekDay2["MONDAY"] = 1] = "MONDAY";
  WeekDay2[WeekDay2["TUESDAY"] = 2] = "TUESDAY";
  WeekDay2[WeekDay2["WEDNESDAY"] = 3] = "WEDNESDAY";
  WeekDay2[WeekDay2["THURSDAY"] = 4] = "THURSDAY";
  WeekDay2[WeekDay2["FRIDAY"] = 5] = "FRIDAY";
  WeekDay2[WeekDay2["SATURDAY"] = 6] = "SATURDAY";
})(WeekDay$1 || (WeekDay$1 = {}));
const DEFAULT_LOCALE = "en-US";
const DEFAULT_FIRST_DAY_OF_WEEK = WeekDay$1.MONDAY;
const DEFAULT_EVENT_COLOR_NAME$1 = "primary";
let CalendarEventImpl$1 = class CalendarEventImpl {
  constructor(_config, id, start, end, title, people, location, description, calendarId, _options = void 0, _customContent = {}, _foreignProperties = {}) {
    Object.defineProperty(this, "_config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _config
    });
    Object.defineProperty(this, "id", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: id
    });
    Object.defineProperty(this, "start", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: start
    });
    Object.defineProperty(this, "end", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: end
    });
    Object.defineProperty(this, "title", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: title
    });
    Object.defineProperty(this, "people", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: people
    });
    Object.defineProperty(this, "location", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: location
    });
    Object.defineProperty(this, "description", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: description
    });
    Object.defineProperty(this, "calendarId", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: calendarId
    });
    Object.defineProperty(this, "_options", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _options
    });
    Object.defineProperty(this, "_customContent", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _customContent
    });
    Object.defineProperty(this, "_foreignProperties", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _foreignProperties
    });
    Object.defineProperty(this, "_previousConcurrentEvents", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_totalConcurrentEvents", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_nDaysInGrid", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_eventFragments", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: {}
    });
  }
  get _isSingleDayTimed() {
    return dateTimeStringRegex$1.test(this.start) && dateTimeStringRegex$1.test(this.end) && dateFromDateTime$1(this.start) === dateFromDateTime$1(this.end);
  }
  get _isSingleDayFullDay() {
    return dateStringRegex$2.test(this.start) && dateStringRegex$2.test(this.end) && this.start === this.end;
  }
  get _isMultiDayTimed() {
    return dateTimeStringRegex$1.test(this.start) && dateTimeStringRegex$1.test(this.end) && dateFromDateTime$1(this.start) !== dateFromDateTime$1(this.end);
  }
  get _isMultiDayFullDay() {
    return dateStringRegex$2.test(this.start) && dateStringRegex$2.test(this.end) && this.start !== this.end;
  }
  get _isSingleHybridDayTimed() {
    if (!this._config.isHybridDay)
      return false;
    if (!dateTimeStringRegex$1.test(this.start) || !dateTimeStringRegex$1.test(this.end))
      return false;
    const startDate = dateFromDateTime$1(this.start);
    const endDate = dateFromDateTime$1(this.end);
    const endDateMinusOneDay = toDateString$1(new Date(toJSDate$1(endDate).getTime() - 864e5));
    if (startDate !== endDate && startDate !== endDateMinusOneDay)
      return false;
    const dayBoundaries = this._config.dayBoundaries.value;
    const eventStartTimePoints = timePointsFromString$2(timeFromDateTime$1(this.start));
    const eventEndTimePoints = timePointsFromString$2(timeFromDateTime$1(this.end));
    return eventStartTimePoints >= dayBoundaries.start && (eventEndTimePoints <= dayBoundaries.end || eventEndTimePoints > eventStartTimePoints) || eventStartTimePoints < dayBoundaries.end && eventEndTimePoints <= dayBoundaries.end;
  }
  get _color() {
    if (this.calendarId && this._config.calendars.value && this.calendarId in this._config.calendars.value) {
      return this._config.calendars.value[this.calendarId].colorName;
    }
    return DEFAULT_EVENT_COLOR_NAME$1;
  }
  _getForeignProperties() {
    return this._foreignProperties;
  }
  _getExternalEvent() {
    return {
      id: this.id,
      start: this.start,
      end: this.end,
      title: this.title,
      people: this.people,
      location: this.location,
      description: this.description,
      calendarId: this.calendarId,
      _options: this._options,
      ...this._getForeignProperties()
    };
  }
};
let CalendarEventBuilder$1 = class CalendarEventBuilder {
  constructor(_config, id, start, end) {
    Object.defineProperty(this, "_config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _config
    });
    Object.defineProperty(this, "id", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: id
    });
    Object.defineProperty(this, "start", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: start
    });
    Object.defineProperty(this, "end", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: end
    });
    Object.defineProperty(this, "people", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "location", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "description", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "title", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "calendarId", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_foreignProperties", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: {}
    });
    Object.defineProperty(this, "_options", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_customContent", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: {}
    });
  }
  build() {
    return new CalendarEventImpl$1(this._config, this.id, this.start, this.end, this.title, this.people, this.location, this.description, this.calendarId, this._options, this._customContent, this._foreignProperties);
  }
  withTitle(title) {
    this.title = title;
    return this;
  }
  withPeople(people) {
    this.people = people;
    return this;
  }
  withLocation(location) {
    this.location = location;
    return this;
  }
  withDescription(description) {
    this.description = description;
    return this;
  }
  withForeignProperties(foreignProperties) {
    this._foreignProperties = foreignProperties;
    return this;
  }
  withCalendarId(calendarId) {
    this.calendarId = calendarId;
    return this;
  }
  withOptions(options) {
    this._options = options;
    return this;
  }
  withCustomContent(customContent) {
    this._customContent = customContent;
    return this;
  }
};
const deepCloneEvent = (calendarEvent, $app) => {
  const calendarEventInternal = new CalendarEventBuilder$1($app.config, calendarEvent.id, calendarEvent.start, calendarEvent.end).withTitle(calendarEvent.title).withPeople(calendarEvent.people).withCalendarId(calendarEvent.calendarId).withForeignProperties(JSON.parse(JSON.stringify(calendarEvent._getForeignProperties()))).withLocation(calendarEvent.location).withDescription(calendarEvent.description).withOptions(calendarEvent._options).withCustomContent(calendarEvent._customContent).build();
  calendarEventInternal._nDaysInGrid = calendarEvent._nDaysInGrid;
  return calendarEventInternal;
};
const concatenatePeople = (people) => {
  return people.reduce((acc, person, index) => {
    if (index === 0)
      return person;
    if (index === people.length - 1)
      return `${acc} & ${person}`;
    return `${acc}, ${person}`;
  }, "");
};
const dateFn = (dateTimeString, locale) => {
  const { year, month, date } = toIntegers(dateTimeString);
  return new Date(year, month, date).toLocaleDateString(locale, {
    day: "numeric",
    month: "long",
    year: "numeric"
  });
};
const getLocalizedDate = dateFn;
const timeFn = (dateTimeString, locale) => {
  const { year, month, date, hours, minutes } = toIntegers(dateTimeString);
  return new Date(year, month, date, hours, minutes).toLocaleTimeString(locale, {
    hour: "numeric",
    minute: "numeric"
  });
};
const getTimeStamp = (calendarEvent, locale, delimiter = "–") => {
  const eventTime = { start: calendarEvent.start, end: calendarEvent.end };
  if (calendarEvent._isSingleDayFullDay) {
    return dateFn(eventTime.start, locale);
  }
  if (calendarEvent._isMultiDayFullDay) {
    return `${dateFn(eventTime.start, locale)} ${delimiter} ${dateFn(eventTime.end, locale)}`;
  }
  if (calendarEvent._isSingleDayTimed && eventTime.start !== eventTime.end) {
    return `${dateFn(eventTime.start, locale)} <span aria-hidden="true">⋅</span> ${timeFn(eventTime.start, locale)} ${delimiter} ${timeFn(eventTime.end, locale)}`;
  }
  if (calendarEvent._isSingleDayTimed && calendarEvent.start === calendarEvent.end) {
    return `${dateFn(eventTime.start, locale)}, ${timeFn(eventTime.start, locale)}`;
  }
  return `${dateFn(eventTime.start, locale)}, ${timeFn(eventTime.start, locale)} ${delimiter} ${dateFn(eventTime.end, locale)}, ${timeFn(eventTime.end, locale)}`;
};
function MonthViewWeek({ week }) {
  const $app = x(AppContext$1);
  const weekDays = week.map((day) => {
    const classes = ["sx__date-picker__day"];
    if (isToday(day))
      classes.push("sx__date-picker__day--today");
    if (toDateString$1(day) === $app.datePickerState.selectedDate.value)
      classes.push("sx__date-picker__day--selected");
    if (!isSameMonth(day, toJSDate$1($app.datePickerState.datePickerDate.value)))
      classes.push("is-leading-or-trailing");
    return {
      day,
      classes
    };
  });
  const isDateSelectable = (date) => {
    const dateString = toDateString$1(date);
    return dateString >= $app.config.min && dateString <= $app.config.max;
  };
  const selectDate = (date) => {
    $app.datePickerState.selectedDate.value = toDateString$1(date);
    $app.datePickerState.close();
  };
  const hasFocus = (weekDay) => toDateString$1(weekDay.day) === $app.datePickerState.datePickerDate.value;
  const handleKeyDown = (event) => {
    if (event.key === "Enter") {
      $app.datePickerState.selectedDate.value = $app.datePickerState.datePickerDate.value;
      $app.datePickerState.close();
      return;
    }
    const keyMapDaysToAdd = /* @__PURE__ */ new Map([
      ["ArrowDown", 7],
      ["ArrowUp", -7],
      ["ArrowLeft", -1],
      ["ArrowRight", 1]
    ]);
    $app.datePickerState.datePickerDate.value = addDays($app.datePickerState.datePickerDate.value, keyMapDaysToAdd.get(event.key) || 0);
  };
  return u$2(k$1, { children: u$2("div", { "data-testid": DATE_PICKER_WEEK, className: "sx__date-picker__week", children: weekDays.map((weekDay) => u$2("button", { type: "button", tabIndex: hasFocus(weekDay) ? 0 : -1, disabled: !isDateSelectable(weekDay.day), "aria-label": getLocalizedDate($app.datePickerState.datePickerDate.value, $app.config.locale.value), className: weekDay.classes.join(" "), "data-focus": hasFocus(weekDay) ? "true" : void 0, onClick: () => selectDate(weekDay.day), onKeyDown: handleKeyDown, children: weekDay.day.getDate() })) }) });
}
function MonthView({ seatYearsView }) {
  const elementId = randomStringId();
  const $app = x(AppContext$1);
  const [month, setMonth] = h$2([]);
  const renderMonth = () => {
    const newDatePickerDate = toJSDate$1($app.datePickerState.datePickerDate.value);
    setMonth($app.timeUnitsImpl.getMonthWithTrailingAndLeadingDays(newDatePickerDate.getFullYear(), newDatePickerDate.getMonth()));
  };
  y$1(() => {
    renderMonth();
  }, [$app.datePickerState.datePickerDate.value]);
  y$1(() => {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        const mutatedElement = mutation.target;
        if (mutatedElement.dataset.focus === "true")
          mutatedElement.focus();
      });
    });
    const monthViewElement = document.getElementById(elementId);
    observer.observe(monthViewElement, {
      childList: true,
      subtree: true,
      attributes: true
    });
    return () => observer.disconnect();
  }, []);
  return u$2(k$1, { children: u$2("div", { id: elementId, "data-testid": MONTH_VIEW, className: "sx__date-picker__month-view", children: [u$2(MonthViewHeader, { setYearsView: seatYearsView }), u$2(DayNames, {}), month.map((week) => u$2(MonthViewWeek, { week }))] }) });
}
function YearsViewAccordion({ year, setYearAndMonth, isExpanded, expand }) {
  const $app = x(AppContext$1);
  const yearWithDates = $app.timeUnitsImpl.getMonthsFor(year);
  const handleClickOnMonth = (event, month) => {
    event.stopPropagation();
    setYearAndMonth(year, month.getMonth());
  };
  return u$2(k$1, { children: u$2("li", { className: isExpanded ? "sx__is-expanded" : "", children: [u$2("button", { type: "button", className: "sx__date-picker__years-accordion__expand-button sx__ripple--wide", onClick: () => expand(year), children: year }), isExpanded && u$2("div", { className: "sx__date-picker__years-view-accordion__panel", children: yearWithDates.map((month) => u$2("button", { type: "button", className: "sx__date-picker__years-view-accordion__month", onClick: (event) => handleClickOnMonth(event, month), children: toLocalizedMonth(month, $app.config.locale.value) })) })] }) });
}
function YearsView({ setMonthView }) {
  const $app = x(AppContext$1);
  const minYear = toJSDate$1($app.config.min).getFullYear();
  const maxYear = toJSDate$1($app.config.max).getFullYear();
  const years = Array.from({ length: maxYear - minYear + 1 }, (_2, i2) => minYear + i2);
  const { year: selectedYear } = toIntegers($app.datePickerState.selectedDate.value);
  const [expandedYear, setExpandedYear] = h$2(selectedYear);
  const setNewDatePickerDate = (year, month) => {
    $app.datePickerState.datePickerDate.value = toDateString$1(new Date(year, month, 1));
    setMonthView();
  };
  y$1(() => {
    var _a;
    const initiallyExpandedYear = (_a = document.querySelector(".sx__date-picker__years-view")) === null || _a === void 0 ? void 0 : _a.querySelector(".sx__is-expanded");
    if (!initiallyExpandedYear)
      return;
    initiallyExpandedYear.scrollIntoView({
      block: "center"
    });
  }, []);
  return u$2(k$1, { children: u$2("ul", { className: "sx__date-picker__years-view", "data-testid": YEARS_VIEW, children: years.map((year) => u$2(YearsViewAccordion, { year, setYearAndMonth: (year2, month) => setNewDatePickerDate(year2, month), isExpanded: expandedYear === year, expand: (year2) => setExpandedYear(year2) })) }) });
}
const isScrollable = (el) => {
  if (el) {
    const hasScrollableContent = el.scrollHeight > el.clientHeight;
    const overflowYStyle = window.getComputedStyle(el).overflowY;
    const isOverflowHidden = overflowYStyle.indexOf("hidden") !== -1;
    return hasScrollableContent && !isOverflowHidden;
  }
  return true;
};
const getScrollableParents = (el, acc = []) => {
  if (!el || el === document.body || el.nodeType === Node.DOCUMENT_FRAGMENT_NODE) {
    acc.push(window);
    return acc;
  }
  if (isScrollable(el)) {
    acc.push(el);
  }
  return getScrollableParents(el.assignedSlot ? el.assignedSlot.parentNode : el.parentNode, acc);
};
const POPUP_CLASS_NAME = "sx__date-picker-popup";
function AppPopup() {
  const $app = x(AppContext$1);
  const [datePickerView, setDatePickerView] = h$2(DatePickerView.MONTH_DAYS);
  const basePopupClasses = [POPUP_CLASS_NAME, $app.config.placement];
  const [classList, setClassList] = h$2(basePopupClasses);
  y$1(() => {
    setClassList([
      ...basePopupClasses,
      $app.datePickerState.isDark.value ? "is-dark" : "",
      $app.config.teleportTo ? "is-teleported" : ""
    ]);
  }, [$app.datePickerState.isDark.value]);
  const clickOutsideListener = (event) => {
    const target = event.target;
    if (!target.closest(`.${POPUP_CLASS_NAME}`))
      $app.datePickerState.close();
  };
  const escapeKeyListener = (e2) => {
    if (e2.key === "Escape") {
      if ($app.config.listeners.onEscapeKeyDown)
        $app.config.listeners.onEscapeKeyDown($app);
      else
        $app.datePickerState.close();
    }
  };
  y$1(() => {
    document.addEventListener("click", clickOutsideListener);
    document.addEventListener("keydown", escapeKeyListener);
    return () => {
      document.removeEventListener("click", clickOutsideListener);
      document.removeEventListener("keydown", escapeKeyListener);
    };
  }, []);
  const remSize = Number(getComputedStyle(document.documentElement).fontSize.split("px")[0]);
  const popupHeight = 362;
  const popupWidth = 332;
  const getFixedPositionStyles = () => {
    const inputWrapperEl = $app.datePickerState.inputWrapperElement.value;
    const inputRect = inputWrapperEl === null || inputWrapperEl === void 0 ? void 0 : inputWrapperEl.getBoundingClientRect();
    if (inputWrapperEl === void 0 || !(inputRect instanceof DOMRect))
      return void 0;
    return {
      top: $app.config.placement.includes("bottom") ? inputRect.height + inputRect.y + 1 : inputRect.y - remSize - popupHeight,
      // subtract remsize to leave room for label text
      left: $app.config.placement.includes("start") ? inputRect.x : inputRect.x + inputRect.width - popupWidth,
      width: popupWidth,
      position: "fixed"
    };
  };
  const [fixedPositionStyle, setFixedPositionStyle] = h$2(getFixedPositionStyles());
  y$1(() => {
    const inputWrapper = $app.datePickerState.inputWrapperElement.value;
    if (inputWrapper === void 0)
      return;
    const scrollableParents = getScrollableParents(inputWrapper);
    const scrollListener = () => setFixedPositionStyle(getFixedPositionStyles());
    scrollableParents.forEach((parent) => parent.addEventListener("scroll", scrollListener));
    return () => scrollableParents.forEach((parent) => parent.removeEventListener("scroll", scrollListener));
  }, []);
  return u$2(k$1, { children: u$2("div", { style: $app.config.teleportTo ? fixedPositionStyle : void 0, "data-testid": "date-picker-popup", className: classList.join(" "), children: datePickerView === DatePickerView.MONTH_DAYS ? u$2(MonthView, { seatYearsView: () => setDatePickerView(DatePickerView.YEARS) }) : u$2(YearsView, { setMonthView: () => setDatePickerView(DatePickerView.MONTH_DAYS) }) }) });
}
function AppWrapper({ $app }) {
  const initialClassList = ["sx__date-picker-wrapper"];
  const [classList, setClassList] = h$2(initialClassList);
  y$1(() => {
    var _a;
    const list = [...initialClassList];
    if ($app.datePickerState.isDark.value)
      list.push("is-dark");
    if ((_a = $app.config.style) === null || _a === void 0 ? void 0 : _a.fullWidth)
      list.push("has-full-width");
    if ($app.datePickerState.isDisabled.value)
      list.push("is-disabled");
    setClassList(list);
  }, [$app.datePickerState.isDark.value, $app.datePickerState.isDisabled.value]);
  let appPopupJSX = u$2(AppPopup, {});
  if ($app.config.teleportTo)
    appPopupJSX = $(appPopupJSX, $app.config.teleportTo);
  return u$2(k$1, { children: u$2("div", { className: classList.join(" "), children: u$2(AppContext$1.Provider, { value: $app, children: [u$2(AppInput, {}), $app.datePickerState.isOpen.value && appPopupJSX] }) }) });
}
const AppContext = J$1({});
class DatePickerAppSingletonImpl {
  constructor(datePickerState, config2, timeUnitsImpl, translate2) {
    Object.defineProperty(this, "datePickerState", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: datePickerState
    });
    Object.defineProperty(this, "config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: config2
    });
    Object.defineProperty(this, "timeUnitsImpl", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: timeUnitsImpl
    });
    Object.defineProperty(this, "translate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: translate2
    });
  }
}
class DatePickerAppSingletonBuilder {
  constructor() {
    Object.defineProperty(this, "datePickerState", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "timeUnitsImpl", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "translate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
  }
  build() {
    return new DatePickerAppSingletonImpl(this.datePickerState, this.config, this.timeUnitsImpl, this.translate);
  }
  withDatePickerState(datePickerState) {
    this.datePickerState = datePickerState;
    return this;
  }
  withConfig(config2) {
    this.config = config2;
    return this;
  }
  withTimeUnitsImpl(timeUnitsImpl) {
    this.timeUnitsImpl = timeUnitsImpl;
    return this;
  }
  withTranslate(translate2) {
    this.translate = translate2;
    return this;
  }
}
var InternalViewName;
(function(InternalViewName2) {
  InternalViewName2["Day"] = "day";
  InternalViewName2["Week"] = "week";
  InternalViewName2["MonthGrid"] = "month-grid";
  InternalViewName2["MonthAgenda"] = "month-agenda";
})(InternalViewName || (InternalViewName = {}));
const getLocaleStringMonthArgs = ($app) => {
  return [$app.config.locale.value, { month: "long" }];
};
const getLocaleStringYearArgs = ($app) => {
  return [$app.config.locale.value, { year: "numeric" }];
};
const getMonthAndYearForDateRange = ($app, rangeStart, rangeEnd) => {
  const startDateMonth = toJSDate$1(rangeStart).toLocaleString(...getLocaleStringMonthArgs($app));
  const startDateYear = toJSDate$1(rangeStart).toLocaleString(...getLocaleStringYearArgs($app));
  const endDateMonth = toJSDate$1(rangeEnd).toLocaleString(...getLocaleStringMonthArgs($app));
  const endDateYear = toJSDate$1(rangeEnd).toLocaleString(...getLocaleStringYearArgs($app));
  if (startDateMonth === endDateMonth && startDateYear === endDateYear) {
    return `${startDateMonth} ${startDateYear}`;
  } else if (startDateMonth !== endDateMonth && startDateYear === endDateYear) {
    return `${startDateMonth} – ${endDateMonth} ${startDateYear}`;
  }
  return `${startDateMonth} ${startDateYear} – ${endDateMonth} ${endDateYear}`;
};
const getMonthAndYearForSelectedDate = ($app) => {
  const dateMonth = toJSDate$1($app.datePickerState.selectedDate.value).toLocaleString(...getLocaleStringMonthArgs($app));
  const dateYear = toJSDate$1($app.datePickerState.selectedDate.value).toLocaleString(...getLocaleStringYearArgs($app));
  return `${dateMonth} ${dateYear}`;
};
function RangeHeading() {
  const $app = x(AppContext);
  const [currentHeading, setCurrentHeading] = h$2("");
  y$1(() => {
    if ($app.calendarState.view.value === InternalViewName.Week) {
      setCurrentHeading(getMonthAndYearForDateRange($app, $app.calendarState.range.value.start, $app.calendarState.range.value.end));
    }
    if ($app.calendarState.view.value === InternalViewName.MonthGrid || $app.calendarState.view.value === InternalViewName.Day || $app.calendarState.view.value === InternalViewName.MonthAgenda) {
      setCurrentHeading(getMonthAndYearForSelectedDate($app));
    }
  }, [$app.calendarState.range.value]);
  return u$2("span", { className: "sx__range-heading", children: currentHeading });
}
function TodayButton() {
  const $app = x(AppContext);
  const setToday = () => {
    $app.datePickerState.selectedDate.value = toDateString$1(/* @__PURE__ */ new Date());
  };
  return u$2("button", { type: "button", className: "sx__today-button sx__ripple", onClick: setToday, children: $app.translate("Today") });
}
function ViewSelection() {
  const $app = x(AppContext);
  const [availableViews, setAvailableViews] = h$2([]);
  useSignalEffect(() => {
    if ($app.calendarState.isCalendarSmall.value) {
      setAvailableViews($app.config.views.value.filter((view) => view.hasSmallScreenCompat));
    } else {
      setAvailableViews($app.config.views.value.filter((view) => view.hasWideScreenCompat));
    }
  });
  const [selectedViewLabel, setSelectedViewLabel] = h$2("");
  useSignalEffect(() => {
    const selectedView = $app.config.views.value.find((view) => view.name === $app.calendarState.view.value);
    if (!selectedView)
      return;
    setSelectedViewLabel($app.translate(selectedView.label));
  });
  const [isOpen, setIsOpen] = h$2(false);
  const clickOutsideListener = (event) => {
    const target = event.target;
    if (target instanceof HTMLElement && !target.closest(".sx__view-selection")) {
      setIsOpen(false);
    }
  };
  y$1(() => {
    document.addEventListener("click", clickOutsideListener);
    return () => document.removeEventListener("click", clickOutsideListener);
  }, []);
  const handleClickOnSelectionItem = (viewName) => {
    setIsOpen(false);
    $app.calendarState.setView(viewName, $app.datePickerState.selectedDate.value);
  };
  const [viewSelectionItems, setViewSelectionItems] = h$2();
  const [focusedViewIndex, setFocusedViewIndex] = h$2(0);
  const handleSelectedViewKeyDown = (keyboardEvent) => {
    if (isKeyEnterOrSpace(keyboardEvent)) {
      setIsOpen(!isOpen);
    }
    setTimeout(() => {
      var _a;
      const allOptions = (_a = $app.elements.calendarWrapper) === null || _a === void 0 ? void 0 : _a.querySelectorAll(".sx__view-selection-item");
      if (!allOptions)
        return;
      setViewSelectionItems(allOptions);
      const firstOption = allOptions[0];
      if (firstOption instanceof HTMLElement) {
        setFocusedViewIndex(0);
        firstOption.focus();
      }
    }, 50);
  };
  const navigateUpOrDown = (keyboardEvent, viewName) => {
    if (!viewSelectionItems)
      return;
    if (keyboardEvent.key === "ArrowDown") {
      const nextOption = viewSelectionItems[focusedViewIndex + 1];
      if (nextOption instanceof HTMLElement) {
        setFocusedViewIndex(focusedViewIndex + 1);
        nextOption.focus();
      }
    } else if (keyboardEvent.key === "ArrowUp") {
      const prevOption = viewSelectionItems[focusedViewIndex - 1];
      if (prevOption instanceof HTMLElement) {
        setFocusedViewIndex(focusedViewIndex - 1);
        prevOption.focus();
      }
    } else if (isKeyEnterOrSpace(keyboardEvent)) {
      handleClickOnSelectionItem(viewName);
    }
  };
  return u$2("div", { className: "sx__view-selection", children: [u$2("div", { tabIndex: 0, role: "button", "aria-label": $app.translate("Select View"), className: "sx__view-selection-selected-item sx__ripple", onClick: () => setIsOpen(!isOpen), onKeyDown: handleSelectedViewKeyDown, children: selectedViewLabel }), isOpen && u$2("ul", { "data-testid": "view-selection-items", className: "sx__view-selection-items", children: availableViews.map((view) => u$2("li", { "aria-label": $app.translate("Select View") + " " + $app.translate(view.label), tabIndex: -1, role: "button", onKeyDown: (keyboardEvent) => navigateUpOrDown(keyboardEvent, view.name), onClick: () => handleClickOnSelectionItem(view.name), className: "sx__view-selection-item" + (view.name === $app.calendarState.view.value ? " is-selected" : ""), children: $app.translate(view.label) })) })] });
}
function ForwardBackwardNavigation() {
  const $app = x(AppContext);
  const navigate = (direction) => {
    const currentView = $app.config.views.value.find((view) => view.name === $app.calendarState.view.value);
    if (!currentView)
      return;
    $app.datePickerState.selectedDate.value = currentView.backwardForwardFn($app.datePickerState.selectedDate.value, direction === "forwards" ? currentView.backwardForwardUnits : -currentView.backwardForwardUnits);
  };
  const [localizedRange, setLocalizedRange] = h$2("");
  useSignalEffect(() => {
    setLocalizedRange(`${getLocalizedDate($app.calendarState.range.value.start, $app.config.locale.value)} ${$app.translate("to")} ${getLocalizedDate($app.calendarState.range.value.end, $app.config.locale.value)}`);
  });
  const [rangeEndMinusOneRange, setRangeEndMinusOneRange] = h$2("");
  const [rangeStartPlusOneRange, setRangeStartPlusOneRange] = h$2("");
  y$1(() => {
    const selectedView = $app.config.views.value.find((view) => view.name === $app.calendarState.view.value);
    if (!selectedView)
      return;
    setRangeEndMinusOneRange(selectedView.setDateRange({
      range: $app.calendarState.range,
      calendarConfig: $app.config,
      timeUnitsImpl: $app.timeUnitsImpl,
      date: selectedView.backwardForwardFn($app.datePickerState.selectedDate.value, -selectedView.backwardForwardUnits)
    }).end);
    setRangeStartPlusOneRange(selectedView.setDateRange({
      range: $app.calendarState.range,
      calendarConfig: $app.config,
      timeUnitsImpl: $app.timeUnitsImpl,
      date: selectedView.backwardForwardFn($app.datePickerState.selectedDate.value, selectedView.backwardForwardUnits)
    }).start);
  }, [$app.datePickerState.selectedDate.value, $app.calendarState.view.value]);
  return u$2(k$1, { children: u$2("div", { className: "sx__forward-backward-navigation", "aria-label": localizedRange, "aria-live": "polite", children: [u$2(Chevron, { disabled: !!($app.config.minDate.value && dateFromDateTime$1(rangeEndMinusOneRange) < $app.config.minDate.value), onClick: () => navigate("backwards"), direction: "previous", buttonText: $app.translate("Previous period") }), u$2(Chevron, { disabled: !!($app.config.maxDate.value && dateFromDateTime$1(rangeStartPlusOneRange) > $app.config.maxDate.value), onClick: () => navigate("forwards"), direction: "next", buttonText: $app.translate("Next period") })] }) });
}
const getElementByCCID = (customComponentId) => document.querySelector(`[data-ccid="${customComponentId}"]`);
function CalendarHeader() {
  const $app = x(AppContext);
  const datePickerAppSingleton = new DatePickerAppSingletonBuilder().withDatePickerState($app.datePickerState).withConfig($app.datePickerConfig).withTranslate($app.translate).withTimeUnitsImpl($app.timeUnitsImpl).build();
  const headerContent = $app.config._customComponentFns.headerContent;
  const headerContentId = h$2(headerContent ? randomStringId() : void 0)[0];
  const headerContentLeftPrepend = $app.config._customComponentFns.headerContentLeftPrepend;
  const headerContentLeftPrependId = h$2(headerContentLeftPrepend ? randomStringId() : void 0)[0];
  const headerContentLeftAppend = $app.config._customComponentFns.headerContentLeftAppend;
  const headerContentLeftAppendId = h$2(headerContentLeftAppend ? randomStringId() : void 0)[0];
  const headerContentRightPrepend = $app.config._customComponentFns.headerContentRightPrepend;
  const headerContentRightPrependId = h$2(headerContentRightPrepend ? randomStringId() : void 0)[0];
  const headerContentRightAppend = $app.config._customComponentFns.headerContentRightAppend;
  const headerContentRightAppendId = h$2(headerContentRightAppend ? randomStringId() : void 0)[0];
  y$1(() => {
    if (headerContent) {
      headerContent(getElementByCCID(headerContentId), {});
    }
    if (headerContentLeftPrepend && headerContentLeftPrependId) {
      headerContentLeftPrepend(getElementByCCID(headerContentLeftPrependId), {});
    }
    if (headerContentLeftAppend) {
      headerContentLeftAppend(getElementByCCID(headerContentLeftAppendId), {});
    }
    if (headerContentRightPrepend) {
      headerContentRightPrepend(getElementByCCID(headerContentRightPrependId), {});
    }
    if (headerContentRightAppend) {
      headerContentRightAppend(getElementByCCID(headerContentRightAppendId), {});
    }
  }, []);
  const keyForRerenderingOnLocaleChange = $app.config.locale.value;
  return u$2("header", { className: "sx__calendar-header", "data-ccid": headerContentId, children: !headerContent && u$2(k$1, { children: [u$2("div", { className: "sx__calendar-header-content", children: [headerContentLeftPrependId && u$2("div", { "data-ccid": headerContentLeftPrependId }), u$2(TodayButton, {}), u$2(ForwardBackwardNavigation, {}), u$2(RangeHeading, {}, $app.config.locale.value), headerContentLeftAppendId && u$2("div", { "data-ccid": headerContentLeftAppendId })] }), u$2("div", { className: "sx__calendar-header-content", children: [headerContentRightPrependId && u$2("div", { "data-ccid": headerContentRightPrependId }), $app.config.views.value.length > 1 && u$2(ViewSelection, {}, keyForRerenderingOnLocaleChange + "-view-selection"), u$2(AppWrapper, { "$app": datePickerAppSingleton }), headerContentRightAppendId && u$2("div", { "data-ccid": headerContentRightAppendId })] })] }) });
}
const setWrapperElement = ($app, calendarId) => {
  $app.elements.calendarWrapper = document.getElementById(calendarId);
};
const setScreenSizeCompatibleView = ($app, isSmall) => {
  const currentView = $app.config.views.value.find((view) => view.name === $app.calendarState.view.value);
  if (isSmall) {
    if (currentView.hasSmallScreenCompat)
      return;
    const smallScreenCompatibleView = $app.config.views.value.find((view) => view.hasSmallScreenCompat);
    if (smallScreenCompatibleView) {
      $app.calendarState.setView(smallScreenCompatibleView.name, $app.datePickerState.selectedDate.value);
    }
  } else {
    if (currentView.hasWideScreenCompat)
      return;
    const wideScreenCompatibleView = $app.config.views.value.find((view) => view.hasWideScreenCompat);
    if (wideScreenCompatibleView) {
      $app.calendarState.setView(wideScreenCompatibleView.name, $app.datePickerState.selectedDate.value);
    }
  }
};
const handleWindowResize = ($app) => {
  const documentRoot = document.documentElement;
  const calendarRoot = $app.elements.calendarWrapper;
  const documentFontSize = +window.getComputedStyle(documentRoot).fontSize.split("p")[0];
  const breakPointFor1RemEquals16px = 700;
  const multiplier = 16 / documentFontSize;
  const smallCalendarBreakpoint = breakPointFor1RemEquals16px / multiplier;
  if (!calendarRoot)
    return;
  const isSmall = $app.config.callbacks.isCalendarSmall ? $app.config.callbacks.isCalendarSmall($app) : calendarRoot.clientWidth < smallCalendarBreakpoint;
  const didIsSmallScreenChange = isSmall !== $app.calendarState.isCalendarSmall.value;
  if (!didIsSmallScreenChange)
    return;
  $app.calendarState.isCalendarSmall.value = isSmall;
  setScreenSizeCompatibleView($app, isSmall);
};
function useWrapperClasses($app) {
  const calendarWrapperClass = "sx__calendar-wrapper";
  const [wrapperClasses, setWrapperClasses] = h$2([
    calendarWrapperClass
  ]);
  useSignalEffect(() => {
    const classes = [calendarWrapperClass];
    if ($app.calendarState.isCalendarSmall.value)
      classes.push("sx__is-calendar-small");
    if ($app.calendarState.isDark.value)
      classes.push("is-dark");
    if ($app.config.theme === "shadcn")
      classes.push("is-shadcn");
    setWrapperClasses(classes);
  });
  return wrapperClasses;
}
const initPlugins = ($app) => {
  Object.values($app.config.plugins).forEach((plugin) => {
    if (plugin === null || plugin === void 0 ? void 0 : plugin.onRender) {
      plugin.onRender($app);
    }
  });
};
const destroyPlugins = ($app) => {
  Object.values($app.config.plugins).forEach((plugin) => {
    if (plugin === null || plugin === void 0 ? void 0 : plugin.destroy)
      plugin.destroy();
  });
};
const invokePluginsBeforeRender = ($app) => {
  Object.values($app.config.plugins).forEach((plugin) => {
    if (plugin === null || plugin === void 0 ? void 0 : plugin.beforeRender)
      plugin.beforeRender($app);
  });
};
function CalendarWrapper({ $app }) {
  var _a;
  const calendarId = randomStringId();
  const viewContainerId = randomStringId();
  y$1(() => {
    var _a2;
    setWrapperElement($app, calendarId);
    initPlugins($app);
    if ((_a2 = $app.config.callbacks) === null || _a2 === void 0 ? void 0 : _a2.onRender) {
      $app.config.callbacks.onRender($app);
    }
    return () => destroyPlugins($app);
  }, []);
  const onResize = () => {
    handleWindowResize($app);
  };
  y$1(() => {
    if ($app.config.isResponsive) {
      onResize();
      window.addEventListener("resize", onResize);
      return () => window.removeEventListener("resize", onResize);
    }
  }, []);
  const wrapperClasses = useWrapperClasses($app);
  const [currentView, setCurrentView] = h$2();
  useSignalEffect(() => {
    const newView = $app.config.views.value.find((view) => view.name === $app.calendarState.view.value);
    const viewElement = document.getElementById(viewContainerId);
    if (!newView || !viewElement || newView.name === (currentView === null || currentView === void 0 ? void 0 : currentView.name))
      return;
    if (currentView)
      currentView.destroy();
    setCurrentView(newView);
    newView.render(viewElement, $app);
  });
  const [previousRangeStart, setPreviousRangeStart] = h$2("");
  const [transitionClass, setTransitionClass] = h$2("");
  useSignalEffect(() => {
    var _a2, _b;
    const newRangeStartIsLaterThanPrevious = (((_a2 = $app.calendarState.range.value) === null || _a2 === void 0 ? void 0 : _a2.start) || "") > previousRangeStart;
    setTransitionClass(newRangeStartIsLaterThanPrevious ? "sx__slide-left" : "sx__slide-right");
    setTimeout(() => {
      setTransitionClass("");
    }, 300);
    setPreviousRangeStart(((_b = $app.calendarState.range.value) === null || _b === void 0 ? void 0 : _b.start) || "");
  });
  useSignalEffect(() => {
    $app.datePickerConfig.locale.value = $app.config.locale.value;
  });
  return u$2(k$1, { children: u$2("div", { className: wrapperClasses.join(" "), id: calendarId, children: u$2("div", { className: "sx__calendar", children: u$2(AppContext.Provider, { value: $app, children: [u$2(CalendarHeader, {}), u$2("div", { className: ["sx__view-container", transitionClass].join(" "), id: viewContainerId }), $app.config.plugins.eventModal && $app.config.plugins.eventModal.calendarEvent.value && u$2($app.config.plugins.eventModal.ComponentFn, { "$app": $app }, (_a = $app.config.plugins.eventModal.calendarEvent.value) === null || _a === void 0 ? void 0 : _a.id)] }) }) }) });
}
const externalEventToInternal$1 = (event, config2) => {
  const { id, start, end, title, description, location, people, _options, ...foreignProperties } = event;
  return new CalendarEventBuilder$1(config2, id, start, end).withTitle(title).withDescription(description).withLocation(location).withPeople(people).withCalendarId(event.calendarId).withOptions(_options).withForeignProperties(foreignProperties).withCustomContent(event._customContent).build();
};
let EventsFacadeImpl$1 = class EventsFacadeImpl {
  constructor($app) {
    Object.defineProperty(this, "$app", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: $app
    });
  }
  set(events) {
    this.$app.calendarEvents.list.value = events.map((event) => externalEventToInternal$1(event, this.$app.config));
  }
  add(event) {
    const newEvent = externalEventToInternal$1(event, this.$app.config);
    const copiedEvents = [...this.$app.calendarEvents.list.value];
    copiedEvents.push(newEvent);
    this.$app.calendarEvents.list.value = copiedEvents;
  }
  get(id) {
    var _a;
    return (_a = this.$app.calendarEvents.list.value.find((event) => event.id === id)) === null || _a === void 0 ? void 0 : _a._getExternalEvent();
  }
  getAll() {
    return this.$app.calendarEvents.list.value.map((event) => event._getExternalEvent());
  }
  remove(id) {
    const index = this.$app.calendarEvents.list.value.findIndex((event) => event.id === id);
    const copiedEvents = [...this.$app.calendarEvents.list.value];
    copiedEvents.splice(index, 1);
    this.$app.calendarEvents.list.value = copiedEvents;
  }
  update(event) {
    const index = this.$app.calendarEvents.list.value.findIndex((e2) => e2.id === event.id);
    const copiedEvents = [...this.$app.calendarEvents.list.value];
    copiedEvents.splice(index, 1, externalEventToInternal$1(event, this.$app.config));
    this.$app.calendarEvents.list.value = copiedEvents;
  }
};
class CalendarApp {
  constructor($app) {
    var _a;
    Object.defineProperty(this, "$app", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: $app
    });
    Object.defineProperty(this, "events", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    this.events = new EventsFacadeImpl$1(this.$app);
    invokePluginsBeforeRender(this.$app);
    Object.values(this.$app.config.plugins).forEach((plugin) => {
      if (!(plugin === null || plugin === void 0 ? void 0 : plugin.name))
        return;
      this[plugin.name] = plugin;
    });
    if ((_a = $app.config.callbacks) === null || _a === void 0 ? void 0 : _a.beforeRender) {
      $app.config.callbacks.beforeRender($app);
    }
  }
  render(el) {
    D$1(g$2(CalendarWrapper, { $app: this.$app }), el);
  }
  setTheme(theme) {
    this.$app.calendarState.isDark.value = theme === "dark";
  }
  getTheme() {
    return this.$app.calendarState.isDark.value ? "dark" : "light";
  }
  /**
   * @internal
   * Purpose: To be consumed by framework adapters for custom component rendering.
   * */
  _setCustomComponentFn(fnId, fn2) {
    this.$app.config._customComponentFns[fnId] = fn2;
  }
}
class CalendarAppSingletonImpl {
  constructor(config2, timeUnitsImpl, calendarState, datePickerState, translate2, datePickerConfig, calendarEvents, elements = { calendarWrapper: void 0 }) {
    Object.defineProperty(this, "config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: config2
    });
    Object.defineProperty(this, "timeUnitsImpl", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: timeUnitsImpl
    });
    Object.defineProperty(this, "calendarState", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: calendarState
    });
    Object.defineProperty(this, "datePickerState", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: datePickerState
    });
    Object.defineProperty(this, "translate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: translate2
    });
    Object.defineProperty(this, "datePickerConfig", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: datePickerConfig
    });
    Object.defineProperty(this, "calendarEvents", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: calendarEvents
    });
    Object.defineProperty(this, "elements", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: elements
    });
  }
}
class CalendarAppSingletonBuilder {
  constructor() {
    Object.defineProperty(this, "config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "timeUnitsImpl", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "datePickerState", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "calendarState", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "translate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "datePickerConfig", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "calendarEvents", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
  }
  build() {
    return new CalendarAppSingletonImpl(this.config, this.timeUnitsImpl, this.calendarState, this.datePickerState, this.translate, this.datePickerConfig, this.calendarEvents);
  }
  withConfig(config2) {
    this.config = config2;
    return this;
  }
  withTimeUnitsImpl(timeUnitsImpl) {
    this.timeUnitsImpl = timeUnitsImpl;
    return this;
  }
  withDatePickerState(datePickerState) {
    this.datePickerState = datePickerState;
    return this;
  }
  withCalendarState(calendarState) {
    this.calendarState = calendarState;
    return this;
  }
  withTranslate(translate2) {
    this.translate = translate2;
    return this;
  }
  withDatePickerConfig(datePickerConfig) {
    this.datePickerConfig = datePickerConfig;
    return this;
  }
  withCalendarEvents(calendarEvents) {
    this.calendarEvents = calendarEvents;
    return this;
  }
}
var DateFormatDelimiter;
(function(DateFormatDelimiter2) {
  DateFormatDelimiter2["SLASH"] = "/";
  DateFormatDelimiter2["DASH"] = "-";
  DateFormatDelimiter2["PERIOD"] = ".";
})(DateFormatDelimiter || (DateFormatDelimiter = {}));
var DateFormatOrder;
(function(DateFormatOrder2) {
  DateFormatOrder2["DMY"] = "DMY";
  DateFormatOrder2["MDY"] = "MDY";
  DateFormatOrder2["YMD"] = "YMD";
})(DateFormatOrder || (DateFormatOrder = {}));
const formatRules = {
  slashMDY: {
    delimiter: DateFormatDelimiter.SLASH,
    order: DateFormatOrder.MDY
  },
  slashDMY: {
    delimiter: DateFormatDelimiter.SLASH,
    order: DateFormatOrder.DMY
  },
  slashYMD: {
    delimiter: DateFormatDelimiter.SLASH,
    order: DateFormatOrder.YMD
  },
  periodDMY: {
    delimiter: DateFormatDelimiter.PERIOD,
    order: DateFormatOrder.DMY
  },
  dashYMD: {
    delimiter: DateFormatDelimiter.DASH,
    order: DateFormatOrder.YMD
  }
};
const dateFormatLocalizedRules = /* @__PURE__ */ new Map([
  ["en-US", formatRules.slashMDY],
  ["en-GB", formatRules.slashDMY],
  ["zh-CN", formatRules.slashYMD],
  ["de-DE", formatRules.periodDMY],
  ["sv-SE", formatRules.dashYMD]
]);
class LocaleNotSupportedError extends Error {
  constructor(locale) {
    super(`Locale not supported: ${locale}`);
  }
}
class InvalidDateFormatError extends Error {
  constructor(dateFormat, locale) {
    super(`Invalid date format: ${dateFormat} for locale: ${locale}`);
  }
}
const _getMatchesOrThrow = (format, matcher, locale) => {
  const matches = format.match(matcher);
  if (!matches)
    throw new InvalidDateFormatError(format, locale);
  return matches;
};
const toDateString$2 = (format, locale) => {
  const internationalFormat = /^\d{4}-\d{2}-\d{2}$/;
  if (internationalFormat.test(format))
    return format;
  const localeDateFormatRule = dateFormatLocalizedRules.get(locale);
  if (!localeDateFormatRule)
    throw new LocaleNotSupportedError(locale);
  const { order, delimiter } = localeDateFormatRule;
  const pattern224Slashed = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
  const pattern224Dotted = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/;
  const pattern442Slashed = /^(\d{4})\/(\d{1,2})\/(\d{1,2})$/;
  if (order === DateFormatOrder.DMY && delimiter === DateFormatDelimiter.SLASH) {
    const matches = _getMatchesOrThrow(format, pattern224Slashed, locale);
    const [, day, month, year] = matches;
    return `${year}-${doubleDigit$2(+month)}-${doubleDigit$2(+day)}`;
  }
  if (order === DateFormatOrder.MDY && delimiter === DateFormatDelimiter.SLASH) {
    const matches = _getMatchesOrThrow(format, pattern224Slashed, locale);
    const [, month, day, year] = matches;
    return `${year}-${doubleDigit$2(+month)}-${doubleDigit$2(+day)}`;
  }
  if (order === DateFormatOrder.YMD && delimiter === DateFormatDelimiter.SLASH) {
    const matches = _getMatchesOrThrow(format, pattern442Slashed, locale);
    const [, year, month, day] = matches;
    return `${year}-${doubleDigit$2(+month)}-${doubleDigit$2(+day)}`;
  }
  if (order === DateFormatOrder.DMY && delimiter === DateFormatDelimiter.PERIOD) {
    const matches = _getMatchesOrThrow(format, pattern224Dotted, locale);
    const [, day, month, year] = matches;
    return `${year}-${doubleDigit$2(+month)}-${doubleDigit$2(+day)}`;
  }
  throw new InvalidDateFormatError(format, locale);
};
const createDatePickerState = (config2, selectedDateParam) => {
  var _a;
  const currentDayDateString = toDateString$1(/* @__PURE__ */ new Date());
  const initialSelectedDate = typeof selectedDateParam === "string" ? selectedDateParam : currentDayDateString;
  const isOpen = d$1(false);
  const isDisabled = d$1(config2.disabled || false);
  const datePickerView = d$1(DatePickerView.MONTH_DAYS);
  const selectedDate = d$1(initialSelectedDate);
  const datePickerDate = d$1(initialSelectedDate || currentDayDateString);
  const isDark = d$1(((_a = config2.style) === null || _a === void 0 ? void 0 : _a.dark) || false);
  const inputDisplayedValue = d$1(selectedDateParam || "");
  const lastValidDisplayedValue = d$1(selectedDateParam || "");
  E(() => {
    try {
      const newValue = toDateString$2(inputDisplayedValue.value, config2.locale.value);
      if (newValue < config2.min || newValue > config2.max) {
        inputDisplayedValue.value = lastValidDisplayedValue.value;
        return;
      }
      selectedDate.value = newValue;
      datePickerDate.value = newValue;
      lastValidDisplayedValue.value = inputDisplayedValue.value;
    } catch (e2) {
    }
  });
  let wasInitialized = false;
  const handleOnChange = (selectedDate2) => {
    if (!wasInitialized)
      return wasInitialized = true;
    config2.listeners.onChange(selectedDate2);
  };
  E(() => {
    var _a2;
    if ((_a2 = config2.listeners) === null || _a2 === void 0 ? void 0 : _a2.onChange)
      handleOnChange(selectedDate.value);
  });
  return {
    inputWrapperElement: d$1(void 0),
    isOpen,
    isDisabled,
    datePickerView,
    selectedDate,
    datePickerDate,
    inputDisplayedValue,
    isDark,
    open: () => isOpen.value = true,
    close: () => isOpen.value = false,
    toggle: () => isOpen.value = !isOpen.value,
    setView: (view) => datePickerView.value = view
  };
};
const datePickerDeDE = {
  Date: "Datum",
  "MM/DD/YYYY": "TT.MM.JJJJ",
  "Next month": "Nächster Monat",
  "Previous month": "Vorheriger Monat",
  "Choose Date": "Datum auswählen"
};
const calendarDeDE = {
  Today: "Heute",
  Month: "Monat",
  Week: "Woche",
  Day: "Tag",
  "Select View": "Ansicht auswählen",
  events: "Ereignisse",
  event: "Ereignis",
  "No events": "Keine Ereignisse",
  "Next period": "Nächster Zeitraum",
  "Previous period": "Vorheriger Zeitraum",
  to: "bis",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Ganztägige und mehrtägige Ereignisse",
  "Link to {{n}} more events on {{date}}": "Link zu {{n}} weiteren Ereignissen am {{date}}",
  "Link to 1 more event on {{date}}": "Link zu 1 weiteren Ereignis am {{date}}"
};
const deDE = {
  ...datePickerDeDE,
  ...calendarDeDE
};
const datePickerEnUS = {
  Date: "Date",
  "MM/DD/YYYY": "MM/DD/YYYY",
  "Next month": "Next month",
  "Previous month": "Previous month",
  "Choose Date": "Choose Date"
};
const calendarEnUS = {
  Today: "Today",
  Month: "Month",
  Week: "Week",
  Day: "Day",
  "Select View": "Select View",
  events: "events",
  event: "event",
  "No events": "No events",
  "Next period": "Next period",
  "Previous period": "Previous period",
  to: "to",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Full day- and multiple day events",
  "Link to {{n}} more events on {{date}}": "Link to {{n}} more events on {{date}}",
  "Link to 1 more event on {{date}}": "Link to 1 more event on {{date}}"
};
const enUS = {
  ...datePickerEnUS,
  ...calendarEnUS
};
const datePickerItIT = {
  Date: "Data",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Mese successivo",
  "Previous month": "Mese precedente",
  "Choose Date": "Scegli la data"
};
const calendarItIT = {
  Today: "Oggi",
  Month: "Mese",
  Week: "Settimana",
  Day: "Giorno",
  "Select View": "Seleziona la vista",
  events: "eventi",
  event: "evento",
  "No events": "Nessun evento",
  "Next period": "Periodo successivo",
  "Previous period": "Periodo precedente",
  to: "a",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Eventi della giornata e plurigiornalieri",
  "Link to {{n}} more events on {{date}}": "Link a {{n}} eventi in più il {{date}}",
  "Link to 1 more event on {{date}}": "Link a 1 evento in più il {{date}}"
};
const itIT = {
  ...datePickerItIT,
  ...calendarItIT
};
const datePickerEnGB = {
  Date: "Date",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Next month",
  "Previous month": "Previous month",
  "Choose Date": "Choose Date"
};
const calendarEnGB = {
  Today: "Today",
  Month: "Month",
  Week: "Week",
  Day: "Day",
  "Select View": "Select View",
  events: "events",
  event: "event",
  "No events": "No events",
  "Next period": "Next period",
  "Previous period": "Previous period",
  to: "to",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Full day- and multiple day events",
  "Link to {{n}} more events on {{date}}": "Link to {{n}} more events on {{date}}",
  "Link to 1 more event on {{date}}": "Link to 1 more event on {{date}}"
};
const enGB = {
  ...datePickerEnGB,
  ...calendarEnGB
};
const datePickerSvSE = {
  Date: "Datum",
  "MM/DD/YYYY": "ÅÅÅÅ-MM-DD",
  "Next month": "Nästa månad",
  "Previous month": "Föregående månad",
  "Choose Date": "Välj datum"
};
const calendarSvSE = {
  Today: "Idag",
  Month: "Månad",
  Week: "Vecka",
  Day: "Dag",
  "Select View": "Välj vy",
  events: "händelser",
  event: "händelse",
  "No events": "Inga händelser",
  "Next period": "Nästa period",
  "Previous period": "Föregående period",
  to: "till",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Heldags- och flerdagshändelser",
  "Link to {{n}} more events on {{date}}": "Länk till {{n}} fler händelser den {{date}}",
  "Link to 1 more event on {{date}}": "Länk till 1 händelse till den {{date}}"
};
const svSE = {
  ...datePickerSvSE,
  ...calendarSvSE
};
const datePickerZhCN = {
  Date: "日期",
  "MM/DD/YYYY": "年/月/日",
  "Next month": "下个月",
  "Previous month": "上个月",
  "Choose Date": "选择日期"
};
const calendarZhCN = {
  Today: "今天",
  Month: "月",
  Week: "周",
  Day: "日",
  "Select View": "选择视图",
  events: "场活动",
  event: "活动",
  "No events": "没有活动",
  "Next period": "下一段时间",
  "Previous period": "上一段时间",
  to: "至",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "全天和多天活动",
  "Link to {{n}} more events on {{date}}": "链接到{{date}}上的{{n}}个更多活动",
  "Link to 1 more event on {{date}}": "链接到{{date}}上的1个更多活动"
};
const zhCN = {
  ...datePickerZhCN,
  ...calendarZhCN
};
const datePickerZhTW = {
  Date: "日期",
  "MM/DD/YYYY": "年/月/日",
  "Next month": "下個月",
  "Previous month": "上個月",
  "Choose Date": "選擇日期"
};
const calendarZhTW = {
  Today: "今天",
  Month: "月",
  Week: "周",
  Day: "日",
  "Select View": "選擇檢視模式",
  events: "場活動",
  event: "活動",
  "No events": "沒有活動",
  "Next period": "下一段時間",
  "Previous period": "上一段時間",
  to: "到",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "全天和多天活動",
  "Link to {{n}} more events on {{date}}": "連接到{{date}}上的{{n}}個更多活動",
  "Link to 1 more event on {{date}}": "連接到{{date}}上的1個更多活動"
};
const zhTW = {
  ...datePickerZhTW,
  ...calendarZhTW
};
const datePickerJaJP = {
  Date: "日付",
  "MM/DD/YYYY": "年/月/日",
  "Next month": "次の月",
  "Previous month": "前の月",
  "Choose Date": "日付を選択"
};
const calendarJaJP = {
  Today: "今日",
  Month: "月",
  Week: "週",
  Day: "日",
  "Select View": "ビューを選択",
  events: "イベント",
  event: "イベント",
  "No events": "イベントなし",
  "Next period": "次の期間",
  "Previous period": "前の期間",
  to: "から",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "終日および複数日イベント",
  "Link to {{n}} more events on {{date}}": "{{date}} に{{n}}件のイベントへのリンク",
  "Link to 1 more event on {{date}}": "{{date}} に1件のイベントへのリンク"
};
const jaJP = {
  ...datePickerJaJP,
  ...calendarJaJP
};
const datePickerRuRU = {
  Date: "Дата",
  "MM/DD/YYYY": "ММ/ДД/ГГГГ",
  "Next month": "Следующий месяц",
  "Previous month": "Прошлый месяц",
  "Choose Date": "Выберите дату"
};
const calendarRuRU = {
  Today: "Сегодня",
  Month: "Месяц",
  Week: "Неделя",
  Day: "День",
  "Select View": "Выберите вид",
  events: "события",
  event: "событие",
  "No events": "Нет событий",
  "Next period": "Следующий период",
  "Previous period": "Прошлый период",
  to: "по",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "События на целый день и несколько дней подряд",
  "Link to {{n}} more events on {{date}}": "Ссылка на {{n}} дополнительных событий на {{date}}",
  "Link to 1 more event on {{date}}": "Ссылка на 1 дополнительное событие на {{date}}"
};
const ruRU = {
  ...datePickerRuRU,
  ...calendarRuRU
};
const datePickerKoKR = {
  Date: "일자",
  "MM/DD/YYYY": "년/월/일",
  "Next month": "다음 달",
  "Previous month": "이전 달",
  "Choose Date": "날짜 선택"
};
const calendarKoKR = {
  Today: "오늘",
  Month: "월",
  Week: "주",
  Day: "일",
  "Select View": "보기 선택",
  events: "일정들",
  event: "일정",
  "No events": "일정 없음",
  "Next period": "다음",
  "Previous period": "이전",
  to: "부터",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "종일 및 복수일 일정",
  "Link to {{n}} more events on {{date}}": "{{date}}에 {{n}}개 이상의 이벤트로 이동",
  "Link to 1 more event on {{date}}": "{{date}}에 1개 이상의 이벤트로 이동"
};
const koKR = {
  ...datePickerKoKR,
  ...calendarKoKR
};
const datePickerFrFR = {
  Date: "Date",
  "MM/DD/YYYY": "JJ/MM/AAAA",
  "Next month": "Mois suivant",
  "Previous month": "Mois précédent",
  "Choose Date": "Choisir une date"
};
const calendarFrFR = {
  Today: "Aujourd'hui",
  Month: "Mois",
  Week: "Semaine",
  Day: "Jour",
  "Select View": "Choisir la vue",
  events: "événements",
  event: "événement",
  "No events": "Aucun événement",
  "Next period": "Période suivante",
  "Previous period": "Période précédente",
  to: "à",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Événements d'une ou plusieurs journées",
  "Link to {{n}} more events on {{date}}": "Lien vers {{n}} autres événements le {{date}}",
  "Link to 1 more event on {{date}}": "Lien vers 1 autre événement le {{date}}"
};
const frFR = {
  ...datePickerFrFR,
  ...calendarFrFR
};
const datePickerDaDK = {
  Date: "Dato",
  "MM/DD/YYYY": "ÅÅÅÅ-MM-DD",
  "Next month": "Næste måned",
  "Previous month": "Foregående måned",
  "Choose Date": "Vælg dato"
};
const calendarDaDK = {
  Today: "I dag",
  Month: "Måned",
  Week: "Uge",
  Day: "Dag",
  "Select View": "Vælg visning",
  events: "begivenheder",
  event: "begivenhed",
  "No events": "Ingen begivenheder",
  "Next period": "Næste periode",
  "Previous period": "Forgående periode",
  to: "til",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Heldagsbegivenheder og flerdagsbegivenheder",
  "Link to {{n}} more events on {{date}}": "Link til {{n}} flere begivenheder den {{date}}",
  "Link to 1 more event on {{date}}": "Link til 1 mere begivenhed den {{date}}"
};
const daDK = {
  ...datePickerDaDK,
  ...calendarDaDK
};
const datePickerPlPL = {
  Date: "Data",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Następny miesiąc",
  "Previous month": "Poprzedni miesiąc",
  "Choose Date": "Wybiewrz datę"
};
const calendarPlPL = {
  Today: "Dzisiaj",
  Month: "Miesiąc",
  Week: "Tydzień",
  Day: "Dzień",
  "Select View": "Wybierz widok",
  events: "wydarzenia",
  event: "wydarzenie",
  "No events": "Brak wydarzeń",
  "Next period": "Następny okres",
  "Previous period": "Poprzedni okres",
  to: "do",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Wydarzenia całodniowe i wielodniowe",
  "Link to {{n}} more events on {{date}}": "Link do {{n}} kolejnych wydarzeń w dniu {{date}}",
  "Link to 1 more event on {{date}}": "Link do 1 kolejnego wydarzenia w dniu {{date}}"
};
const plPL = {
  ...datePickerPlPL,
  ...calendarPlPL
};
const datePickerEsES = {
  Date: "Fecha",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Siguiente mes",
  "Previous month": "Mes anterior",
  "Choose Date": "Seleccione una fecha"
};
const calendarEsES = {
  Today: "Hoy",
  Month: "Mes",
  Week: "Semana",
  Day: "Día",
  "Select View": "Seleccione una vista",
  events: "eventos",
  event: "evento",
  "No events": "Sin eventos",
  "Next period": "Siguiente período",
  "Previous period": "Período anterior",
  to: "a",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Día completo y eventos de múltiples días",
  "Link to {{n}} more events on {{date}}": "Enlace a {{n}} eventos más el {{date}}",
  "Link to 1 more event on {{date}}": "Enlace a 1 evento más el {{date}}"
};
const esES = {
  ...datePickerEsES,
  ...calendarEsES
};
const calendarNlNL = {
  Today: "Vandaag",
  Month: "Maand",
  Week: "Week",
  Day: "Dag",
  "Select View": "Kies weergave",
  events: "gebeurtenissen",
  event: "gebeurtenis",
  "No events": "Geen gebeurtenissen",
  "Next period": "Volgende periode",
  "Previous period": "Vorige periode",
  to: "tot",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Evenementen van een hele dag en meerdere dagen",
  "Link to {{n}} more events on {{date}}": "Link naar {{n}} meer evenementen op {{date}}",
  "Link to 1 more event on {{date}}": "Link naar 1 meer evenement op {{date}}"
};
const datePickerNlNL = {
  Date: "Datum",
  "MM/DD/YYYY": "DD-MM-JJJJ",
  "Next month": "Volgende maand",
  "Previous month": "Vorige maand",
  "Choose Date": "Kies datum"
};
const nlNL = {
  ...datePickerNlNL,
  ...calendarNlNL
};
const datePickerPtBR = {
  Date: "Data",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Mês seguinte",
  "Previous month": "Mês anterior",
  "Choose Date": "Escolha uma data"
};
const calendarPtBR = {
  Today: "Hoje",
  Month: "Mês",
  Week: "Semana",
  Day: "Dia",
  "Select View": "Selecione uma visualização",
  events: "eventos",
  event: "evento",
  "No events": "Sem eventos",
  "Next period": "Período seguinte",
  "Previous period": "Período anterior",
  to: "a",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Dia inteiro e eventos de vários dias",
  "Link to {{n}} more events on {{date}}": "Link para mais {{n}} eventos em {{date}}",
  "Link to 1 more event on {{date}}": "Link para mais 1 evento em {{date}}"
};
const ptBR = {
  ...datePickerPtBR,
  ...calendarPtBR
};
const datePickerSkSK = {
  Date: "Dátum",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Ďalší mesiac",
  "Previous month": "Predchádzajúci mesiac",
  "Choose Date": "Vyberte dátum"
};
const calendarSkSK = {
  Today: "Dnes",
  Month: "Mesiac",
  Week: "Týždeň",
  Day: "Deň",
  "Select View": "Vyberte zobrazenie",
  events: "udalosti",
  event: "udalosť",
  "No events": "Žiadne udalosti",
  "Next period": "Ďalšie obdobie",
  "Previous period": "Predchádzajúce obdobie",
  to: "do",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Celodenné a viacdňové udalosti",
  "Link to {{n}} more events on {{date}}": "Odkaz na {{n}} ďalších udalostí dňa {{date}}",
  "Link to 1 more event on {{date}}": "Odkaz na 1 ďalšiu udalosť dňa {{date}}"
};
const skSK = {
  ...datePickerSkSK,
  ...calendarSkSK
};
const datePickerMkMK = {
  Date: "Датум",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Следен месец",
  "Previous month": "Претходен месец",
  "Choose Date": "Избери Датум"
};
const calendarMkMK = {
  Today: "Денес",
  Month: "Месец",
  Week: "Недела",
  Day: "Ден",
  "Select View": "Избери Преглед",
  events: "настани",
  event: "настан",
  "No events": "Нема настани",
  "Next period": "Следен период",
  "Previous period": "Претходен период",
  to: "до",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Целодневни и повеќедневни настани",
  "Link to {{n}} more events on {{date}}": "Линк до {{n}} повеќе настани на {{date}}",
  "Link to 1 more event on {{date}}": "Линк до 1 повеќе настан на {{date}}"
};
const mkMK = {
  ...datePickerMkMK,
  ...calendarMkMK
};
const datePickerTrTR = {
  Date: "Tarih",
  "MM/DD/YYYY": "GG/AA/YYYY",
  "Next month": "Sonraki ay",
  "Previous month": "Önceki ay",
  "Choose Date": "Tarih Seç"
};
const calendarTrTR = {
  Today: "Bugün",
  Month: "Aylık",
  Week: "Haftalık",
  Day: "Günlük",
  "Select View": "Görünüm Seç",
  events: "etkinlikler",
  event: "etkinlik",
  "No events": "Etkinlik yok",
  "Next period": "Sonraki dönem",
  "Previous period": "Önceki dönem",
  to: "dan",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Tüm gün ve çoklu gün etkinlikleri",
  "Link to {{n}} more events on {{date}}": "{{date}} tarihinde {{n}} etkinliğe bağlantı",
  "Link to 1 more event on {{date}}": "{{date}} tarihinde 1 etkinliğe bağlantı"
};
const trTR = {
  ...datePickerTrTR,
  ...calendarTrTR
};
const datePickerKyKG = {
  Date: "Датасы",
  "MM/DD/YYYY": "АА/КК/ЖЖЖЖ",
  "Next month": "Кийинки ай",
  "Previous month": "Өткөн ай",
  "Choose Date": "Күндү тандаңыз"
};
const calendarKyKG = {
  Today: "Бүгүн",
  Month: "Ай",
  Week: "Апта",
  Day: "Күн",
  "Select View": "Көрүнүштү тандаңыз",
  events: "Окуялар",
  event: "Окуя",
  "No events": "Окуя жок",
  "Next period": "Кийинки мезгил",
  "Previous period": "Өткөн мезгил",
  to: "чейин",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Күн бою жана бир нече күн катары менен болгон окуялар",
  "Link to {{n}} more events on {{date}}": "{{date}} күнүндө {{n}} окуяга байланыш",
  "Link to 1 more event on {{date}}": "{{date}} күнүндө 1 окуяга байланыш"
};
const kyKG = {
  ...datePickerKyKG,
  ...calendarKyKG
};
const datePickerIdID = {
  Date: "Tanggal",
  "MM/DD/YYYY": "DD.MM.YYYY",
  "Next month": "Bulan depan",
  "Previous month": "Bulan sebelumnya",
  "Choose Date": "Pilih tanggal"
};
const calendarIdID = {
  Today: "Hari Ini",
  Month: "Bulan",
  Week: "Minggu",
  Day: "Hari",
  "Select View": "Pilih tampilan",
  events: "Acara",
  event: "Acara",
  "No events": "Tidak ada acara",
  "Next period": "Periode selanjutnya",
  "Previous period": "Periode sebelumnya",
  to: "sampai",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Sepanjang hari dan acara beberapa hari ",
  "Link to {{n}} more events on {{date}}": "Tautan ke {{n}} acara lainnya pada {{date}}",
  "Link to 1 more event on {{date}}": "Tautan ke 1 acara lainnya pada {{date}}"
};
const idID = {
  ...datePickerIdID,
  ...calendarIdID
};
const datePickerCsCZ = {
  Date: "Datum",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Další měsíc",
  "Previous month": "Předchozí měsíc",
  "Choose Date": "Vyberte datum"
};
const calendarCsCZ = {
  Today: "Dnes",
  Month: "Měsíc",
  Week: "Týden",
  Day: "Den",
  "Select View": "Vyberte zobrazení",
  events: "události",
  event: "událost",
  "No events": "Žádné události",
  "Next period": "Příští období",
  "Previous period": "Předchozí období",
  to: "do",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Celodenní a vícedenní události",
  "Link to {{n}} more events on {{date}}": "Odkaz na {{n}} dalších událostí dne {{date}}",
  "Link to 1 more event on {{date}}": "Odkaz na 1 další událost dne {{date}}"
};
const csCZ = {
  ...datePickerCsCZ,
  ...calendarCsCZ
};
const datePickerEtEE = {
  Date: "Kuupäev",
  "MM/DD/YYYY": "PP.KK.AAAA",
  "Next month": "Järgmine kuu",
  "Previous month": "Eelmine kuu",
  "Choose Date": "Vali kuupäev"
};
const calendarEtEE = {
  Today: "Täna",
  Month: "Kuu",
  Week: "Nädal",
  Day: "Päev",
  "Select View": "Vali vaade",
  events: "sündmused",
  event: "sündmus",
  "No events": "Pole sündmusi",
  "Next period": "Järgmine periood",
  "Previous period": "Eelmine periood",
  to: "kuni",
  "Full day- and multiple day events": "Täispäeva- ja mitmepäevasündmused",
  "Link to {{n}} more events on {{date}}": "Link {{n}} rohkematele sündmustele kuupäeval {{date}}",
  "Link to 1 more event on {{date}}": "Link ühele lisasündmusele kuupäeval {{date}}"
};
const etEE = {
  ...datePickerEtEE,
  ...calendarEtEE
};
const datePickerUkUA = {
  Date: "Дата",
  "MM/DD/YYYY": "ММ/ДД/РРРР",
  "Next month": "Наступний місяць",
  "Previous month": "Минулий місяць",
  "Choose Date": "Виберіть дату"
};
const calendarUkUA = {
  Today: "Сьогодні",
  Month: "Місяць",
  Week: "Тиждень",
  Day: "День",
  "Select View": "Виберіть вигляд",
  events: "події",
  event: "подія",
  "No events": "Немає подій",
  "Next period": "Наступний період",
  "Previous period": "Минулий період",
  to: "по",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Події на цілий день і кілька днів поспіль",
  "Link to {{n}} more events on {{date}}": "Посилання на {{n}} додаткові події на {{date}}",
  "Link to 1 more event on {{date}}": "Посилання на 1 додаткову подію на {{date}}"
};
const ukUA = {
  ...datePickerUkUA,
  ...calendarUkUA
};
const datePickerSrLatnRS = {
  Date: "Datum",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Sledeći mesec",
  "Previous month": "Prethodni mesec",
  "Choose Date": "Izaberite datum"
};
const calendarSrLatnRS = {
  Today: "Danas",
  Month: "Mesec",
  Week: "Nedelja",
  Day: "Dan",
  "Select View": "Odaberite pregled",
  events: "Događaji",
  event: "Događaj",
  "No events": "Nema događaja",
  "Next period": "Naredni period",
  "Previous period": "Prethodni period",
  to: "do",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Celodnevni i višednevni događaji",
  "Link to {{n}} more events on {{date}}": "Link do još {{n}} događaja na {{date}}",
  "Link to 1 more event on {{date}}": "Link do jednog događaja na {{date}}"
};
const srLatnRS = {
  ...datePickerSrLatnRS,
  ...calendarSrLatnRS
};
const datePickerCaES = {
  Date: "Data",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Següent mes",
  "Previous month": "Mes anterior",
  "Choose Date": "Selecciona una data"
};
const calendarCaES = {
  Today: "Avui",
  Month: "Mes",
  Week: "Setmana",
  Day: "Dia",
  "Select View": "Selecciona una vista",
  events: "Esdeveniments",
  event: "Esdeveniment",
  "No events": "Sense esdeveniments",
  "Next period": "Següent període",
  "Previous period": "Període anterior",
  to: "a",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Esdeveniments de dia complet i de múltiples dies",
  "Link to {{n}} more events on {{date}}": "Enllaç a {{n}} esdeveniments més el {{date}}",
  "Link to 1 more event on {{date}}": "Enllaç a 1 esdeveniment més el {{date}}"
};
const caES = {
  ...datePickerCaES,
  ...calendarCaES
};
const datePickerSrRS = {
  Date: "Датум",
  "MM/DD/YYYY": "DD/MM/YYYY",
  "Next month": "Следећи месец",
  "Previous month": "Претходни месец",
  "Choose Date": "Изаберите Датум"
};
const calendarSrRS = {
  Today: "Данас",
  Month: "Месец",
  Week: "Недеља",
  Day: "Дан",
  "Select View": "Изаберите преглед",
  events: "Догађаји",
  event: "Догађај",
  "No events": "Нема догађаја",
  "Next period": "Следећи период",
  "Previous period": "Претходни период",
  to: "да",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Целодневни и вишедневни догађаји",
  "Link to {{n}} more events on {{date}}": "Линк до још {{n}} догађаја на {{date}}",
  "Link to 1 more event on {{date}}": "Линк до још 1 догађаја {{date}}"
};
const srRS = {
  ...datePickerSrRS,
  ...calendarSrRS
};
const datePickerLtLT = {
  Date: "Data",
  "MM/DD/YYYY": "MMMM-MM-DD",
  "Next month": "Kitas mėnuo",
  "Previous month": "Ankstesnis mėnuo",
  "Choose Date": "Pasirinkite datą"
};
const calendarLtLT = {
  Today: "Šiandien",
  Month: "Mėnuo",
  Week: "Savaitė",
  Day: "Diena",
  "Select View": "Pasirinkite vaizdą",
  events: "įvykiai",
  event: "įvykis",
  "No events": "Įvykių nėra",
  "Next period": "Kitas laikotarpis",
  "Previous period": "Ankstesnis laikotarpis",
  to: "iki",
  // as in 2/1/2020 to 2/2/2020
  "Full day- and multiple day events": "Visos dienos ir kelių dienų įvykiai",
  "Link to {{n}} more events on {{date}}": "Nuoroda į dar {{n}} įvykius {{date}}",
  "Link to 1 more event on {{date}}": "Nuoroda į dar 1 vieną įvykį {{date}}"
};
const ltLT = {
  ...datePickerLtLT,
  ...calendarLtLT
};
class InvalidLocaleError extends Error {
  constructor(locale) {
    super(`Invalid locale: ${locale}`);
  }
}
const translate = (locale, languages) => (key, translationVariables) => {
  if (!/^[a-z]{2}-[A-Z]{2}$/.test(locale.value) && "sr-Latn-RS" !== locale.value) {
    throw new InvalidLocaleError(locale.value);
  }
  const deHyphenatedLocale = locale.value.replaceAll("-", "");
  const language = languages.value[deHyphenatedLocale];
  if (!language)
    return key;
  let translation = language[key] || key;
  Object.keys(translationVariables || {}).forEach((variable) => {
    const value = String(translationVariables === null || translationVariables === void 0 ? void 0 : translationVariables[variable]);
    if (!value)
      return;
    translation = translation.replace(`{{${variable}}}`, value);
  });
  return translation;
};
const translations = {
  deDE,
  enUS,
  itIT,
  enGB,
  svSE,
  zhCN,
  zhTW,
  jaJP,
  ruRU,
  koKR,
  frFR,
  daDK,
  mkMK,
  plPL,
  esES,
  nlNL,
  ptBR,
  skSK,
  trTR,
  kyKG,
  idID,
  csCZ,
  etEE,
  ukUA,
  caES,
  srLatnRS,
  srRS,
  ltLT
};
class EventColors {
  constructor(config2) {
    Object.defineProperty(this, "config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: config2
    });
  }
  setLight() {
    Object.entries(this.config.calendars.value || {}).forEach(([calendarName, calendar]) => {
      if (!calendar.lightColors) {
        console.warn(`No light colors defined for calendar ${calendarName}`);
        return;
      }
      this.setColors(calendar.colorName, calendar.lightColors);
    });
  }
  setDark() {
    Object.entries(this.config.calendars.value || {}).forEach(([calendarName, calendar]) => {
      if (!calendar.darkColors) {
        console.warn(`No dark colors defined for calendar ${calendarName}`);
        return;
      }
      this.setColors(calendar.colorName, calendar.darkColors);
    });
  }
  setColors(colorName, colorDefinition) {
    document.documentElement.style.setProperty(`--sx-color-${colorName}`, colorDefinition.main);
    document.documentElement.style.setProperty(`--sx-color-${colorName}-container`, colorDefinition.container);
    document.documentElement.style.setProperty(`--sx-color-on-${colorName}-container`, colorDefinition.onContainer);
  }
}
const createCalendarState = (calendarConfig, timeUnitsImpl, selectedDate) => {
  var _a;
  const _view = d$1(((_a = calendarConfig.views.value.find((view2) => view2.name === calendarConfig.defaultView)) === null || _a === void 0 ? void 0 : _a.name) || calendarConfig.views.value[0].name);
  const view = w(() => {
    return _view.value;
  });
  const range = d$1(null);
  let wasInitialized = false;
  const callOnRangeUpdate = (_range) => {
    if (!wasInitialized)
      return wasInitialized = true;
    if (calendarConfig.callbacks.onRangeUpdate && _range.value) {
      calendarConfig.callbacks.onRangeUpdate(_range.value);
    }
  };
  E(() => {
    if (calendarConfig.callbacks.onRangeUpdate && range.value) {
      callOnRangeUpdate(range);
    }
  });
  const setRange = (date) => {
    var _a2, _b;
    const selectedView = calendarConfig.views.value.find((availableView) => availableView.name === _view.value);
    const newRange = selectedView.setDateRange({
      calendarConfig,
      date,
      range,
      timeUnitsImpl
    });
    if (newRange.start === ((_a2 = range.value) === null || _a2 === void 0 ? void 0 : _a2.start) && newRange.end === ((_b = range.value) === null || _b === void 0 ? void 0 : _b.end))
      return;
    range.value = newRange;
  };
  setRange(selectedDate || toDateString$1(/* @__PURE__ */ new Date()));
  const isCalendarSmall = d$1(void 0);
  const isDark = d$1(calendarConfig.isDark.value || false);
  E(() => {
    const eventColors = new EventColors(calendarConfig);
    if (isDark.value) {
      eventColors.setDark();
    } else {
      eventColors.setLight();
    }
  });
  return {
    view,
    isDark,
    setRange,
    range,
    isCalendarSmall,
    setView: (newView, selectedDate2) => {
      r(() => {
        _view.value = newView;
        setRange(selectedDate2);
      });
    }
  };
};
const createCalendarEventsImpl = (events, backgroundEvents, config2) => {
  const list = d$1(events.map((event) => {
    return externalEventToInternal$1(event, config2);
  }));
  const filterPredicate = d$1(void 0);
  return {
    list,
    filterPredicate,
    backgroundEvents: d$1(backgroundEvents)
  };
};
InternalViewName.Week;
const DEFAULT_DAY_BOUNDARIES = {
  start: 0,
  end: 2400
};
const DEFAULT_WEEK_GRID_HEIGHT = 1600;
const DATE_GRID_BLOCKER = "blocker";
const timePointsPerDay = (dayStart, dayEnd, isHybridDay) => {
  if (dayStart === dayEnd)
    return 2400;
  if (isHybridDay)
    return 2400 - dayStart + dayEnd;
  return dayEnd - dayStart;
};
class CalendarConfigImpl {
  constructor(locale = DEFAULT_LOCALE, firstDayOfWeek = DEFAULT_FIRST_DAY_OF_WEEK, defaultView = InternalViewName.Week, views = [], dayBoundaries = DEFAULT_DAY_BOUNDARIES, weekOptions, calendars = {}, plugins = {}, isDark = false, isResponsive = true, callbacks = {}, _customComponentFns = {}, minDate = void 0, maxDate = void 0, monthGridOptions = {
    nEventsPerDay: 4
  }, theme = void 0, translations2 = {}) {
    Object.defineProperty(this, "defaultView", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: defaultView
    });
    Object.defineProperty(this, "plugins", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: plugins
    });
    Object.defineProperty(this, "isResponsive", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: isResponsive
    });
    Object.defineProperty(this, "callbacks", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: callbacks
    });
    Object.defineProperty(this, "_customComponentFns", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _customComponentFns
    });
    Object.defineProperty(this, "firstDayOfWeek", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "views", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "dayBoundaries", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "weekOptions", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "calendars", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "isDark", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "minDate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "maxDate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "monthGridOptions", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "locale", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: d$1(DEFAULT_LOCALE)
    });
    Object.defineProperty(this, "theme", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "translations", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    this.locale = d$1(locale);
    this.firstDayOfWeek = d$1(firstDayOfWeek);
    this.views = d$1(views);
    this.dayBoundaries = d$1(dayBoundaries);
    this.weekOptions = d$1(weekOptions);
    this.calendars = d$1(calendars);
    this.isDark = d$1(isDark);
    this.minDate = d$1(minDate);
    this.maxDate = d$1(maxDate);
    this.monthGridOptions = d$1(monthGridOptions);
    this.theme = theme;
    this.translations = d$1(translations2);
  }
  get isHybridDay() {
    return this.dayBoundaries.value.start > this.dayBoundaries.value.end || this.dayBoundaries.value.start !== 0 && this.dayBoundaries.value.start === this.dayBoundaries.value.end;
  }
  get timePointsPerDay() {
    return timePointsPerDay(this.dayBoundaries.value.start, this.dayBoundaries.value.end, this.isHybridDay);
  }
}
class CalendarConfigBuilder {
  constructor() {
    Object.defineProperty(this, "locale", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "firstDayOfWeek", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "defaultView", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "views", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "dayBoundaries", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "weekOptions", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: {
        gridHeight: DEFAULT_WEEK_GRID_HEIGHT,
        nDays: 7,
        eventWidth: 100,
        timeAxisFormatOptions: { hour: "numeric" }
      }
    });
    Object.defineProperty(this, "monthGridOptions", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "calendars", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "plugins", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: {}
    });
    Object.defineProperty(this, "isDark", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: false
    });
    Object.defineProperty(this, "isResponsive", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: true
    });
    Object.defineProperty(this, "callbacks", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "minDate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "maxDate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "backgroundEvents", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "theme", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "translations", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
  }
  build() {
    return new CalendarConfigImpl(this.locale || DEFAULT_LOCALE, typeof this.firstDayOfWeek === "number" ? this.firstDayOfWeek : DEFAULT_FIRST_DAY_OF_WEEK, this.defaultView || InternalViewName.Week, this.views || [], this.dayBoundaries || DEFAULT_DAY_BOUNDARIES, this.weekOptions, this.calendars, this.plugins, this.isDark, this.isResponsive, this.callbacks, {}, this.minDate, this.maxDate, this.monthGridOptions, this.theme, this.translations);
  }
  withLocale(locale) {
    this.locale = locale;
    return this;
  }
  withTranslations(translation) {
    this.translations = translation;
    return this;
  }
  withFirstDayOfWeek(firstDayOfWeek) {
    this.firstDayOfWeek = firstDayOfWeek;
    return this;
  }
  withDefaultView(defaultView) {
    this.defaultView = defaultView;
    return this;
  }
  withViews(views) {
    this.views = views;
    return this;
  }
  withDayBoundaries(dayBoundaries) {
    if (!dayBoundaries)
      return this;
    this.dayBoundaries = {
      start: timePointsFromString$2(dayBoundaries.start),
      end: timePointsFromString$2(dayBoundaries.end)
    };
    return this;
  }
  withWeekOptions(weekOptions) {
    this.weekOptions = {
      ...this.weekOptions,
      ...weekOptions
    };
    return this;
  }
  withCalendars(calendars) {
    this.calendars = calendars;
    return this;
  }
  withPlugins(plugins) {
    if (!plugins)
      return this;
    plugins.forEach((plugin) => {
      this.plugins[plugin.name] = plugin;
    });
    return this;
  }
  withIsDark(isDark) {
    this.isDark = isDark;
    return this;
  }
  withIsResponsive(isResponsive) {
    this.isResponsive = isResponsive;
    return this;
  }
  withCallbacks(listeners) {
    this.callbacks = listeners;
    return this;
  }
  withMinDate(minDate) {
    this.minDate = minDate;
    return this;
  }
  withMaxDate(maxDate) {
    this.maxDate = maxDate;
    return this;
  }
  withMonthGridOptions(monthOptions) {
    this.monthGridOptions = monthOptions;
    return this;
  }
  withBackgroundEvents(backgroundEvents) {
    this.backgroundEvents = backgroundEvents;
    return this;
  }
  withTheme(theme) {
    this.theme = theme;
    return this;
  }
}
const createInternalConfig = (config2, plugins) => {
  return new CalendarConfigBuilder().withLocale(config2.locale).withFirstDayOfWeek(config2.firstDayOfWeek).withDefaultView(config2.defaultView).withViews(config2.views).withDayBoundaries(config2.dayBoundaries).withWeekOptions(config2.weekOptions).withCalendars(config2.calendars).withPlugins(plugins).withIsDark(config2.isDark).withIsResponsive(config2.isResponsive).withCallbacks(config2.callbacks).withMinDate(config2.minDate).withMaxDate(config2.maxDate).withMonthGridOptions(config2.monthGridOptions).withBackgroundEvents(config2.backgroundEvents).withTheme(config2.theme).withTranslations(config2.translations || translations).build();
};
var Month;
(function(Month2) {
  Month2[Month2["JANUARY"] = 0] = "JANUARY";
  Month2[Month2["FEBRUARY"] = 1] = "FEBRUARY";
  Month2[Month2["MARCH"] = 2] = "MARCH";
  Month2[Month2["APRIL"] = 3] = "APRIL";
  Month2[Month2["MAY"] = 4] = "MAY";
  Month2[Month2["JUNE"] = 5] = "JUNE";
  Month2[Month2["JULY"] = 6] = "JULY";
  Month2[Month2["AUGUST"] = 7] = "AUGUST";
  Month2[Month2["SEPTEMBER"] = 8] = "SEPTEMBER";
  Month2[Month2["OCTOBER"] = 9] = "OCTOBER";
  Month2[Month2["NOVEMBER"] = 10] = "NOVEMBER";
  Month2[Month2["DECEMBER"] = 11] = "DECEMBER";
})(Month || (Month = {}));
class NoYearZeroError extends Error {
  constructor() {
    super("Year zero does not exist in the Gregorian calendar.");
  }
}
class ExtendedDateImpl extends Date {
  constructor(yearArg, monthArg, dateArg) {
    super(yearArg, monthArg, dateArg);
    if (yearArg === 0)
      throw new NoYearZeroError();
    this.setFullYear(yearArg);
  }
  get year() {
    return this.getFullYear();
  }
  get month() {
    return this.getMonth();
  }
  get date() {
    return this.getDate();
  }
}
class TimeUnitsImpl {
  constructor(config2) {
    Object.defineProperty(this, "config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: config2
    });
  }
  get firstDayOfWeek() {
    return this.config.firstDayOfWeek.value;
  }
  set firstDayOfWeek(firstDayOfWeek) {
    this.config.firstDayOfWeek.value = firstDayOfWeek;
  }
  getMonthWithTrailingAndLeadingDays(year, month) {
    if (year === 0)
      throw new NoYearZeroError();
    const firstDateOfMonth = new Date(year, month, 1);
    const monthWithDates = [this.getWeekFor(firstDateOfMonth)];
    let isInMonth = true;
    let first = monthWithDates[0][0];
    while (isInMonth) {
      const newFirstDayOfWeek = new Date(first.getFullYear(), first.getMonth(), first.getDate() + 7);
      if (newFirstDayOfWeek.getMonth() === month) {
        monthWithDates.push(this.getWeekFor(newFirstDayOfWeek));
        first = newFirstDayOfWeek;
      } else {
        isInMonth = false;
      }
    }
    return monthWithDates;
  }
  getWeekFor(date) {
    const week = [this.getFirstDateOfWeek(date)];
    while (week.length < 7) {
      const lastDateOfWeek = week[week.length - 1];
      const nextDateOfWeek = new Date(lastDateOfWeek);
      nextDateOfWeek.setDate(lastDateOfWeek.getDate() + 1);
      week.push(nextDateOfWeek);
    }
    return week;
  }
  getMonthsFor(year) {
    return Object.values(Month).filter((month) => !isNaN(Number(month))).map((month) => new ExtendedDateImpl(year, Number(month), 1));
  }
  getFirstDateOfWeek(date) {
    const dateIsNthDayOfWeek = date.getDay() - this.firstDayOfWeek;
    const firstDateOfWeek = date;
    if (dateIsNthDayOfWeek === 0) {
      return firstDateOfWeek;
    } else if (dateIsNthDayOfWeek > 0) {
      firstDateOfWeek.setDate(date.getDate() - dateIsNthDayOfWeek);
    } else {
      firstDateOfWeek.setDate(date.getDate() - (7 + dateIsNthDayOfWeek));
    }
    return firstDateOfWeek;
  }
}
class TimeUnitsBuilder {
  constructor() {
    Object.defineProperty(this, "config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
  }
  build() {
    return new TimeUnitsImpl(this.config);
  }
  withConfig(config2) {
    this.config = config2;
    return this;
  }
}
const createTimeUnitsImpl = (internalConfig) => {
  return new TimeUnitsBuilder().withConfig(internalConfig).build();
};
var Placement;
(function(Placement2) {
  Placement2["TOP_START"] = "top-start";
  Placement2["TOP_END"] = "top-end";
  Placement2["BOTTOM_START"] = "bottom-start";
  Placement2["BOTTOM_END"] = "bottom-end";
})(Placement || (Placement = {}));
class ConfigImpl {
  constructor(locale = DEFAULT_LOCALE, firstDayOfWeek = DEFAULT_FIRST_DAY_OF_WEEK, min = toDateString$1(new Date(1970, 0, 1)), max = toDateString$1(new Date((/* @__PURE__ */ new Date()).getFullYear() + 50, 11, 31)), placement = Placement.BOTTOM_START, listeners = {}, style = {}, teleportTo, label, name, disabled) {
    Object.defineProperty(this, "min", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: min
    });
    Object.defineProperty(this, "max", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: max
    });
    Object.defineProperty(this, "placement", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: placement
    });
    Object.defineProperty(this, "listeners", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: listeners
    });
    Object.defineProperty(this, "style", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: style
    });
    Object.defineProperty(this, "teleportTo", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: teleportTo
    });
    Object.defineProperty(this, "label", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: label
    });
    Object.defineProperty(this, "name", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: name
    });
    Object.defineProperty(this, "disabled", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: disabled
    });
    Object.defineProperty(this, "locale", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "firstDayOfWeek", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    this.locale = d$1(locale);
    this.firstDayOfWeek = d$1(firstDayOfWeek);
  }
}
class ConfigBuilder {
  constructor() {
    Object.defineProperty(this, "locale", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "firstDayOfWeek", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "min", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "max", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "placement", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "listeners", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "style", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "teleportTo", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "label", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "name", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "disabled", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
  }
  build() {
    return new ConfigImpl(this.locale, this.firstDayOfWeek, this.min, this.max, this.placement, this.listeners, this.style, this.teleportTo, this.label, this.name, this.disabled);
  }
  withLocale(locale) {
    this.locale = locale;
    return this;
  }
  withFirstDayOfWeek(firstDayOfWeek) {
    this.firstDayOfWeek = firstDayOfWeek;
    return this;
  }
  withMin(min) {
    this.min = min;
    return this;
  }
  withMax(max) {
    this.max = max;
    return this;
  }
  withPlacement(placement) {
    this.placement = placement;
    return this;
  }
  withListeners(listeners) {
    this.listeners = listeners;
    return this;
  }
  withStyle(style) {
    this.style = style;
    return this;
  }
  withTeleportTo(teleportTo) {
    this.teleportTo = teleportTo;
    return this;
  }
  withLabel(label) {
    this.label = label;
    return this;
  }
  withName(name) {
    this.name = name;
    return this;
  }
  withDisabled(disabled) {
    this.disabled = disabled;
    return this;
  }
}
const createDatePickerConfig = (config2, dateSelectionCallback) => {
  var _a, _b;
  return new ConfigBuilder().withLocale(config2.locale).withFirstDayOfWeek(config2.firstDayOfWeek).withMin(config2.minDate).withMax(config2.maxDate).withTeleportTo((_a = config2.datePicker) === null || _a === void 0 ? void 0 : _a.teleportTo).withStyle((_b = config2.datePicker) === null || _b === void 0 ? void 0 : _b.style).withPlacement(Placement.BOTTOM_END).withListeners({ onChange: dateSelectionCallback }).build();
};
const createDateSelectionCallback = (calendarState, config2) => {
  let lastEmittedDate = null;
  return (date) => {
    var _a;
    calendarState.setRange(date);
    if (((_a = config2.callbacks) === null || _a === void 0 ? void 0 : _a.onSelectedDateUpdate) && date !== lastEmittedDate) {
      lastEmittedDate = date;
      config2.callbacks.onSelectedDateUpdate(date);
    }
  };
};
const validatePlugins = (configPlugins, pluginArg) => {
  if (configPlugins && pluginArg) {
    throw new Error("You cannot provide plugins over the config object and as an argument to createCalendar.");
  }
};
const validateEvents = (events = []) => {
  events === null || events === void 0 ? void 0 : events.forEach((event) => {
    if (!dateTimeStringRegex$1.test(event.start) && !dateStringRegex$2.test(event.start)) {
      throw new Error(`[Schedule-X error]: Event start time ${event.start} is not a valid time format. Please refer to the docs for more information.`);
    }
    if (!dateTimeStringRegex$1.test(event.end) && !dateStringRegex$2.test(event.end)) {
      throw new Error(`[Schedule-X error]: Event end time ${event.end} is not a valid time format. Please refer to the docs for more information.`);
    }
    const isIdDecimalNumber = typeof event.id === "number" && event.id % 1 !== 0;
    if (isIdDecimalNumber) {
      throw new Error(`[Schedule-X error]: Event id ${event.id} is not a valid id. Only non-unicode characters that can be used by document.querySelector is allowed, see: https://developer.mozilla.org/en-US/docs/Web/CSS/ident. We recommend using uuids or integers.`);
    }
    if (typeof event.id === "string" && !/^[a-zA-Z0-9_-]*$/.test(event.id)) {
      throw new Error(`[Schedule-X error]: Event id ${event.id} is not a valid id. Only non-unicode characters that can be used by document.querySelector is allowed, see: https://developer.mozilla.org/en-US/docs/Web/CSS/ident. We recommend using uuids or integers.`);
    }
    if (typeof event.id !== "string" && typeof event.id !== "number") {
      throw new Error(`[Schedule-X error]: Event id ${event.id} is not a valid id. Only non-unicode characters that can be used by document.querySelector is allowed, see: https://developer.mozilla.org/en-US/docs/Web/CSS/ident. We recommend using uuids or integers.`);
    }
  });
};
const createCalendarAppSingleton = (config2, plugins) => {
  var _a;
  const internalConfig = createInternalConfig(config2, plugins);
  const timeUnitsImpl = createTimeUnitsImpl(internalConfig);
  const calendarState = createCalendarState(internalConfig, timeUnitsImpl, config2.selectedDate);
  const dateSelectionCallback = createDateSelectionCallback(calendarState, config2);
  const datePickerConfig = createDatePickerConfig(config2, dateSelectionCallback);
  const datePickerState = createDatePickerState(datePickerConfig, config2.selectedDate || ((_a = config2.datePicker) === null || _a === void 0 ? void 0 : _a.selectedDate));
  const calendarEvents = createCalendarEventsImpl(config2.events || [], config2.backgroundEvents || [], internalConfig);
  return new CalendarAppSingletonBuilder().withConfig(internalConfig).withTimeUnitsImpl(timeUnitsImpl).withDatePickerState(datePickerState).withCalendarEvents(calendarEvents).withDatePickerConfig(datePickerConfig).withCalendarState(calendarState).withTranslate(translate(internalConfig.locale, internalConfig.translations)).build();
};
const createCalendar = (config2, plugins) => {
  validatePlugins(config2.plugins, plugins);
  if (config2.skipValidation !== true) {
    validateEvents(config2.events);
  }
  return new CalendarApp(createCalendarAppSingleton(config2, config2.plugins || []));
};
class PreactView {
  constructor(config2) {
    Object.defineProperty(this, "randomId", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: randomStringId()
    });
    Object.defineProperty(this, "name", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "label", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "Component", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "setDateRange", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "hasSmallScreenCompat", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "hasWideScreenCompat", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "backwardForwardFn", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "backwardForwardUnits", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    this.name = config2.name;
    this.label = config2.label;
    this.Component = config2.Component;
    this.setDateRange = config2.setDateRange;
    this.hasSmallScreenCompat = config2.hasSmallScreenCompat;
    this.hasWideScreenCompat = config2.hasWideScreenCompat;
    this.backwardForwardFn = config2.backwardForwardFn;
    this.backwardForwardUnits = config2.backwardForwardUnits;
  }
  render(onElement, $app) {
    D$1(g$2(this.Component, { $app, id: this.randomId }), onElement);
  }
  destroy() {
    const el = document.getElementById(this.randomId);
    if (el) {
      el.remove();
    }
  }
}
const createPreactView = (config2) => {
  return new PreactView(config2);
};
const timePointToPercentage = (timePointsInDay, dayBoundaries, timePoint) => {
  if (timePoint < dayBoundaries.start) {
    const firstDayTimePoints = 2400 - dayBoundaries.start;
    return (timePoint + firstDayTimePoints) / timePointsInDay * 100;
  }
  return (timePoint - dayBoundaries.start) / timePointsInDay * 100;
};
const getEventHeight = (start, end, dayBoundaries, pointsPerDay) => {
  if (start === end) {
    return timePointToPercentage(pointsPerDay, dayBoundaries, timePointsFromString$2(timeFromDateTime$1(addTimePointsToDateTime(end, 50)))) - timePointToPercentage(pointsPerDay, dayBoundaries, timePointsFromString$2(timeFromDateTime$1(start)));
  }
  return timePointToPercentage(pointsPerDay, dayBoundaries, timePointsFromString$2(timeFromDateTime$1(end))) - timePointToPercentage(pointsPerDay, dayBoundaries, timePointsFromString$2(timeFromDateTime$1(start)));
};
const getLeftRule = (calendarEvent, eventWidth) => {
  if (!calendarEvent._totalConcurrentEvents || !calendarEvent._previousConcurrentEvents)
    return 0;
  return (calendarEvent._previousConcurrentEvents || 0) / (calendarEvent._totalConcurrentEvents || 0) * eventWidth;
};
const getWidthRule = (leftRule, eventWidth) => {
  return eventWidth - leftRule;
};
const getBorderRule = (calendarEvent) => {
  if (!calendarEvent._previousConcurrentEvents)
    return 0;
  return "1px solid #fff";
};
const getTimeGridEventCopyElementId = (id) => {
  return "time-grid-event-copy-" + id;
};
const isUIEventTouchEvent = (event) => {
  return "touches" in event && typeof event.touches === "object";
};
function useEventInteractions($app) {
  const [eventCopy, setEventCopy] = h$2();
  const updateCopy = (newCopy) => {
    if (!newCopy)
      return setEventCopy(void 0);
    setEventCopy(deepCloneEvent(newCopy, $app));
  };
  const [dragStartTimeout, setDragStartTimeout] = h$2();
  const createDragStartTimeout = (callback, uiEvent) => {
    setDragStartTimeout(setTimeout(() => callback(uiEvent), 150));
  };
  const setClickedEvent = (uiEvent, calendarEvent) => {
    if (isUIEventTouchEvent(uiEvent) && uiEvent.touches.length === 0)
      return;
    if (!$app.config.plugins.eventModal)
      return;
    const eventTarget = uiEvent.target;
    if (!(eventTarget instanceof HTMLElement))
      return;
    const calendarEventElement = eventTarget.classList.contains("sx__event") ? eventTarget : eventTarget.closest(".sx__event");
    if (calendarEventElement instanceof HTMLElement) {
      $app.config.plugins.eventModal.calendarEventElement.value = calendarEventElement;
      $app.config.plugins.eventModal.setCalendarEvent(calendarEvent, calendarEventElement.getBoundingClientRect());
    }
  };
  const setClickedEventIfNotDragging = (calendarEvent, uiEvent) => {
    if (dragStartTimeout) {
      clearTimeout(dragStartTimeout);
      setClickedEvent(uiEvent, calendarEvent);
    }
    setDragStartTimeout(void 0);
  };
  return {
    eventCopy,
    updateCopy,
    createDragStartTimeout,
    setClickedEventIfNotDragging,
    setClickedEvent
  };
}
const getCCID = (customComponent, isCopy) => {
  let customComponentId = customComponent ? "custom-time-grid-event-" + randomStringId() : void 0;
  if (customComponentId && isCopy)
    customComponentId += "-copy";
  return customComponentId;
};
const invokeOnEventClickCallback = ($app, calendarEvent) => {
  if ($app.config.callbacks.onEventClick) {
    $app.config.callbacks.onEventClick(calendarEvent._getExternalEvent());
  }
};
const invokeOnEventDoubleClickCallback = ($app, calendarEvent) => {
  if ($app.config.callbacks.onDoubleClickEvent) {
    $app.config.callbacks.onDoubleClickEvent(calendarEvent._getExternalEvent());
  }
};
const getEventCoordinates = (uiEvent) => {
  const actualEvent = isUIEventTouchEvent(uiEvent) ? uiEvent.touches[0] : uiEvent;
  return {
    clientX: actualEvent.clientX,
    clientY: actualEvent.clientY
  };
};
const getYCoordinateInTimeGrid = (dateTimeString, dayBoundaries, pointsPerDay) => {
  return timePointToPercentage(pointsPerDay, dayBoundaries, timePointsFromString$2(timeFromDateTime$1(dateTimeString)));
};
const nextTick = (cb) => {
  setTimeout(() => {
    cb();
  });
};
const focusModal = ($app) => {
  const calendarWrapper = $app.elements.calendarWrapper;
  if (!(calendarWrapper instanceof HTMLElement))
    return;
  const eventModal = calendarWrapper.querySelector(".sx__event-modal");
  if (!(eventModal instanceof HTMLElement))
    return;
  setTimeout(() => {
    eventModal.focus();
  }, 100);
};
function TimeGridEvent({ calendarEvent, dayBoundariesDateTime, isCopy, setMouseDown }) {
  var _a, _b, _c, _d;
  const $app = x(AppContext);
  const { eventCopy, updateCopy, createDragStartTimeout, setClickedEventIfNotDragging, setClickedEvent } = useEventInteractions($app);
  const localizeArgs = [
    $app.config.locale.value,
    { hour: "numeric", minute: "numeric" }
  ];
  const getEventTime = (start, end) => {
    const localizedStartTime = toJSDate$1(start).toLocaleTimeString(...localizeArgs);
    if (start === end) {
      return localizedStartTime;
    }
    const localizedEndTime = toJSDate$1(end).toLocaleTimeString(...localizeArgs);
    return `${localizedStartTime} – ${localizedEndTime}`;
  };
  const eventCSSVariables = {
    borderLeft: `4px solid var(--sx-color-${calendarEvent._color})`,
    textColor: `var(--sx-color-on-${calendarEvent._color}-container)`,
    backgroundColor: `var(--sx-color-${calendarEvent._color}-container)`,
    iconStroke: `var(--sx-color-on-${calendarEvent._color}-container)`
  };
  const leftRule = getLeftRule(calendarEvent, $app.config.weekOptions.value.eventWidth);
  const handleStartDrag = (uiEvent) => {
    var _a2;
    if (isUIEventTouchEvent(uiEvent))
      uiEvent.preventDefault();
    if (!dayBoundariesDateTime)
      return;
    if (!uiEvent.target)
      return;
    if (!$app.config.plugins.dragAndDrop)
      return;
    if ((_a2 = calendarEvent._options) === null || _a2 === void 0 ? void 0 : _a2.disableDND)
      return;
    const newEventCopy = deepCloneEvent(calendarEvent, $app);
    updateCopy(newEventCopy);
    $app.config.plugins.dragAndDrop.createTimeGridDragHandler({
      $app,
      eventCoordinates: getEventCoordinates(uiEvent),
      updateCopy,
      eventCopy: newEventCopy
    }, dayBoundariesDateTime);
  };
  const customComponent = $app.config._customComponentFns.timeGridEvent;
  const customComponentId = getCCID(customComponent, isCopy);
  y$1(() => {
    if (!customComponent)
      return;
    customComponent(getElementByCCID(customComponentId), {
      calendarEvent: calendarEvent._getExternalEvent()
    });
  }, [calendarEvent, eventCopy]);
  const handleOnClick = (e2) => {
    e2.stopPropagation();
    invokeOnEventClickCallback($app, calendarEvent);
  };
  const handleOnDoubleClick = (e2) => {
    e2.stopPropagation();
    invokeOnEventDoubleClickCallback($app, calendarEvent);
  };
  const handleKeyDown = (e2) => {
    if (e2.key === "Enter" || e2.key === " ") {
      e2.stopPropagation();
      setClickedEvent(e2, calendarEvent);
      invokeOnEventClickCallback($app, calendarEvent);
      nextTick(() => {
        focusModal($app);
      });
    }
  };
  const startResize = (e2) => {
    setMouseDown(true);
    e2.stopPropagation();
    if (!dayBoundariesDateTime)
      return;
    if ($app.config.plugins.resize) {
      const eventCopy2 = deepCloneEvent(calendarEvent, $app);
      updateCopy(eventCopy2);
      $app.config.plugins.resize.createTimeGridEventResizer(eventCopy2, updateCopy, e2, dayBoundariesDateTime);
    }
  };
  const borderRule = getBorderRule(calendarEvent);
  const classNames = ["sx__time-grid-event", "sx__event"];
  if (isCopy)
    classNames.push("is-event-copy");
  if ((_a = calendarEvent._options) === null || _a === void 0 ? void 0 : _a.additionalClasses)
    classNames.push(...calendarEvent._options.additionalClasses);
  const handlePointerDown = (e2) => {
    setMouseDown(true);
    createDragStartTimeout(handleStartDrag, e2);
  };
  const handlePointerUp = (e2) => {
    nextTick(() => setMouseDown(false));
    setClickedEventIfNotDragging(calendarEvent, e2);
  };
  const hasCustomContent = (_b = calendarEvent._customContent) === null || _b === void 0 ? void 0 : _b.timeGrid;
  return u$2(k$1, { children: [u$2("div", { id: isCopy ? getTimeGridEventCopyElementId(calendarEvent.id) : void 0, "data-event-id": calendarEvent.id, onClick: handleOnClick, onDblClick: handleOnDoubleClick, onKeyDown: handleKeyDown, onMouseDown: handlePointerDown, onMouseUp: handlePointerUp, onTouchStart: handlePointerDown, onTouchEnd: handlePointerUp, className: classNames.join(" "), tabIndex: 0, role: "button", style: {
    top: `${getYCoordinateInTimeGrid(calendarEvent.start, $app.config.dayBoundaries.value, $app.config.timePointsPerDay)}%`,
    height: `${getEventHeight(calendarEvent.start, calendarEvent.end, $app.config.dayBoundaries.value, $app.config.timePointsPerDay)}%`,
    left: `${leftRule}%`,
    width: `${getWidthRule(leftRule, isCopy ? 100 : $app.config.weekOptions.value.eventWidth)}%`,
    backgroundColor: customComponent ? void 0 : eventCSSVariables.backgroundColor,
    color: customComponent ? void 0 : eventCSSVariables.textColor,
    borderTop: borderRule,
    borderRight: borderRule,
    borderBottom: borderRule,
    borderLeft: customComponent ? void 0 : eventCSSVariables.borderLeft,
    padding: customComponent ? "0" : void 0
  }, children: u$2("div", { "data-ccid": customComponentId, className: "sx__time-grid-event-inner", children: [!customComponent && !hasCustomContent && u$2(k$1, { children: [calendarEvent.title && u$2("div", { className: "sx__time-grid-event-title", children: calendarEvent.title }), u$2("div", { className: "sx__time-grid-event-time", children: [u$2(TimeIcon, { strokeColor: eventCSSVariables.iconStroke }), getEventTime(calendarEvent.start, calendarEvent.end)] }), calendarEvent.people && calendarEvent.people.length > 0 && u$2("div", { className: "sx__time-grid-event-people", children: [u$2(UserIcon, { strokeColor: eventCSSVariables.iconStroke }), concatenatePeople(calendarEvent.people)] }), calendarEvent.location && u$2("div", { className: "sx__time-grid-event-location", children: [u$2(LocationPinIcon, { strokeColor: eventCSSVariables.iconStroke }), calendarEvent.location] })] }), hasCustomContent && u$2("div", { dangerouslySetInnerHTML: {
    __html: ((_c = calendarEvent._customContent) === null || _c === void 0 ? void 0 : _c.timeGrid) || ""
  } }), $app.config.plugins.resize && !((_d = calendarEvent._options) === null || _d === void 0 ? void 0 : _d.disableResize) && u$2("div", { className: "sx__time-grid-event-resize-handle", onMouseDown: startResize })] }) }), eventCopy && u$2(TimeGridEvent, { calendarEvent: eventCopy, isCopy: true, setMouseDown })] });
}
const sortEventsByStartAndEnd = (a2, b2) => {
  if (a2.start === b2.start) {
    if (a2.end < b2.end)
      return 1;
    if (a2.end > b2.end)
      return -1;
    return 0;
  }
  if (a2.start < b2.start)
    return -1;
  if (a2.start > b2.start)
    return 1;
  return 0;
};
const sortEventsByStartAndEndWithoutConsideringTime = (a2, b2) => {
  const aStart = dateFromDateTime$1(a2.start);
  const bStart = dateFromDateTime$1(b2.start);
  const aEnd = dateFromDateTime$1(a2.end);
  const bEnd = dateFromDateTime$1(b2.end);
  if (aStart === bStart) {
    if (aEnd < bEnd)
      return 1;
    if (aEnd > bEnd)
      return -1;
    return 0;
  }
  if (aStart < bStart)
    return -1;
  if (aStart > bStart)
    return 1;
  return 0;
};
const handleEventConcurrency = (sortedEvents, concurrentEventsCache = [], currentIndex = 0) => {
  for (let i2 = currentIndex; i2 < sortedEvents.length; i2++) {
    const event = sortedEvents[i2];
    const nextEvent = sortedEvents[i2 + 1];
    if (concurrentEventsCache.length && (!nextEvent || concurrentEventsCache.every((e2) => e2.end < nextEvent.start))) {
      concurrentEventsCache.push(event);
      for (let ii = 0; ii < concurrentEventsCache.length; ii++) {
        const currentEvent = concurrentEventsCache[ii];
        const NpreviousConcurrentEvents = concurrentEventsCache.filter((cachedEvent, index) => {
          if (cachedEvent === currentEvent || index > ii)
            return false;
          return cachedEvent.start <= currentEvent.start && cachedEvent.end > currentEvent.start;
        }).length;
        const NupcomingConcurrentEvents = concurrentEventsCache.filter((cachedEvent, index) => {
          if (cachedEvent === currentEvent || index < ii)
            return false;
          return cachedEvent.start < currentEvent.end && cachedEvent.end >= currentEvent.start;
        }).length;
        currentEvent._totalConcurrentEvents = NpreviousConcurrentEvents + NupcomingConcurrentEvents + 1;
        currentEvent._previousConcurrentEvents = NpreviousConcurrentEvents;
      }
      concurrentEventsCache = [];
      return handleEventConcurrency(sortedEvents, concurrentEventsCache, i2 + 1);
    }
    if (nextEvent && event.end > nextEvent.start || concurrentEventsCache.some((e2) => e2.end > event.start)) {
      concurrentEventsCache.push(event);
      return handleEventConcurrency(sortedEvents, concurrentEventsCache, i2 + 1);
    }
    event._totalConcurrentEvents = 1;
    event._previousConcurrentEvents = 0;
  }
  return sortedEvents;
};
const getClickDateTime = (e2, $app, dayStartDateTime) => {
  if (!(e2.target instanceof HTMLElement))
    return;
  const DAY_GRID_CLASS_NAME = "sx__time-grid-day";
  const dayGridElement = e2.target.classList.contains(DAY_GRID_CLASS_NAME) ? e2.target : e2.target.closest("." + DAY_GRID_CLASS_NAME);
  const clientY = e2.clientY - dayGridElement.getBoundingClientRect().top;
  const clickPercentageOfDay = clientY / dayGridElement.getBoundingClientRect().height * 100;
  const clickTimePointsIntoDay = Math.round($app.config.timePointsPerDay / 100 * clickPercentageOfDay);
  return addTimePointsToDateTime(dayStartDateTime, clickTimePointsIntoDay);
};
const getClassNameForWeekday = (weekday) => {
  switch (weekday) {
    case 0:
      return "sx__sunday";
    case 1:
      return "sx__monday";
    case 2:
      return "sx__tuesday";
    case 3:
      return "sx__wednesday";
    case 4:
      return "sx__thursday";
    case 5:
      return "sx__friday";
    case 6:
      return "sx__saturday";
    default:
      throw new Error("Invalid weekday");
  }
};
function TimeGridBackgroundEvent({ backgroundEvent, date }) {
  const $app = x(AppContext);
  let start = backgroundEvent.start;
  let end = backgroundEvent.end;
  if (dateStringRegex$2.test(start))
    start += " 00:00";
  if (dateStringRegex$2.test(end))
    end += " 23:59";
  if (dateFromDateTime$1(start) !== date)
    start = date + " " + start.split(" ")[1];
  if (dateFromDateTime$1(end) !== date)
    end = date + " " + end.split(" ")[1];
  const startTimePoints = timePointsFromString$2(start.split(" ")[1]);
  if (startTimePoints < $app.config.dayBoundaries.value.start) {
    start = date + " " + timeStringFromTimePoints$1($app.config.dayBoundaries.value.start);
  }
  if (start === end) {
    return null;
  }
  return u$2(k$1, { children: u$2("div", { class: "sx__time-grid-background-event", title: backgroundEvent.title, style: {
    ...backgroundEvent.style,
    position: "absolute",
    zIndex: 0,
    top: `${getYCoordinateInTimeGrid(start, $app.config.dayBoundaries.value, $app.config.timePointsPerDay)}%`,
    height: `${getEventHeight(start, end, $app.config.dayBoundaries.value, $app.config.timePointsPerDay)}%`,
    width: "100%"
  } }) });
}
function TimeGridDay({ calendarEvents, date, backgroundEvents }) {
  const [mouseDownOnChild, setMouseDownOnChild] = h$2(false);
  const $app = x(AppContext);
  const timeStringFromDayBoundary = timeStringFromTimePoints$1($app.config.dayBoundaries.value.start);
  const timeStringFromDayBoundaryEnd = timeStringFromTimePoints$1($app.config.dayBoundaries.value.end);
  const dayStartDateTime = setTimeInDateTimeString(date, timeStringFromDayBoundary);
  const dayEndDateTime = $app.config.isHybridDay ? addDays(setTimeInDateTimeString(date, timeStringFromDayBoundaryEnd), 1) : setTimeInDateTimeString(date, timeStringFromDayBoundaryEnd);
  const dayBoundariesDateTime = {
    start: dayStartDateTime,
    end: dayEndDateTime
  };
  const sortedEvents = calendarEvents.sort(sortEventsByStartAndEnd);
  const [eventsWithConcurrency, setEventsWithConcurrency] = h$2([]);
  y$1(() => {
    setEventsWithConcurrency(handleEventConcurrency(sortedEvents));
  }, [calendarEvents]);
  const handleOnClick = (e2, callback) => {
    if (!callback || mouseDownOnChild)
      return;
    const clickDateTime = getClickDateTime(e2, $app, dayStartDateTime);
    if (clickDateTime) {
      callback(clickDateTime);
    }
  };
  const handleMouseDown = (e2) => {
    const callback = $app.config.callbacks.onMouseDownDateTime;
    if (!callback || mouseDownOnChild)
      return;
    const clickDateTime = getClickDateTime(e2, $app, dayStartDateTime);
    if (clickDateTime) {
      callback(clickDateTime, e2);
    }
  };
  const handlePointerUp = () => {
    const msWaitToEnsureThatClickEventWasDispatched = 10;
    setTimeout(() => {
      setMouseDownOnChild(false);
    }, msWaitToEnsureThatClickEventWasDispatched);
  };
  const baseClasses = [
    "sx__time-grid-day",
    getClassNameForWeekday(toJSDate$1(date).getDay())
  ];
  const [classNames, setClassNames] = h$2(baseClasses);
  useSignalEffect(() => {
    const newClassNames = [...baseClasses];
    if ($app.datePickerState.selectedDate.value === date)
      newClassNames.push("is-selected");
    setClassNames(newClassNames);
  });
  return u$2("div", { className: classNames.join(" "), "data-time-grid-date": date, onClick: (e2) => handleOnClick(e2, $app.config.callbacks.onClickDateTime), onDblClick: (e2) => handleOnClick(e2, $app.config.callbacks.onDoubleClickDateTime), "aria-label": getLocalizedDate(date, $app.config.locale.value), onMouseLeave: () => setMouseDownOnChild(false), onMouseUp: handlePointerUp, onTouchEnd: handlePointerUp, onMouseDown: handleMouseDown, children: [backgroundEvents.map((event) => u$2(k$1, { children: u$2(TimeGridBackgroundEvent, { backgroundEvent: event, date }) })), eventsWithConcurrency.map((event) => u$2(TimeGridEvent, { calendarEvent: event, dayBoundariesDateTime, setMouseDown: setMouseDownOnChild }, event.id))] });
}
const getTimeAxisHours = ({ start, end }, isHybridDay) => {
  const hours = [];
  let hour = Math.floor(start / 100);
  if (isHybridDay) {
    while (hour < 24) {
      hours.push(hour);
      hour += 1;
    }
    hour = 0;
  }
  const lastHour = end === 0 ? 24 : Math.ceil(end / 100);
  while (hour < lastHour) {
    hours.push(hour);
    hour += 1;
  }
  return hours;
};
function TimeAxis() {
  const $app = x(AppContext);
  const [hours, setHours] = h$2([]);
  useSignalEffect(() => {
    setHours(getTimeAxisHours($app.config.dayBoundaries.value, $app.config.isHybridDay));
    const hoursPerDay = $app.config.timePointsPerDay / 100;
    const pixelsPerHour = $app.config.weekOptions.value.gridHeight / hoursPerDay;
    document.documentElement.style.setProperty("--sx-week-grid-hour-height", `${pixelsPerHour}px`);
  });
  const formatter = new Intl.DateTimeFormat($app.config.locale.value, $app.config.weekOptions.value.timeAxisFormatOptions);
  return u$2(k$1, { children: u$2("div", { className: "sx__week-grid__time-axis", children: hours.map((hour) => u$2("div", { className: "sx__week-grid__hour", children: u$2("span", { className: "sx__week-grid__hour-text", children: formatter.format(new Date(0, 0, 0, hour)) }) })) }) });
}
function DateAxis({ week }) {
  const $app = x(AppContext);
  const getClassNames = (date) => {
    const classNames = [
      "sx__week-grid__date",
      getClassNameForWeekday(date.getDay())
    ];
    if (isToday(date)) {
      classNames.push("sx__week-grid__date--is-today");
    }
    return classNames.join(" ");
  };
  return u$2(k$1, { children: u$2("div", { className: "sx__week-grid__date-axis", children: week.map((date) => u$2("div", { className: getClassNames(date), "data-date": toDateString$1(date), children: [u$2("div", { className: "sx__week-grid__day-name", children: getDayNameShort(date, $app.config.locale.value) }), u$2("div", { className: "sx__week-grid__date-number", children: date.getDate() })] })) }) });
}
const sortEventsForWeekView = (allCalendarEvents) => {
  const dateGridEvents = [];
  const timeGridEvents = [];
  for (const event of allCalendarEvents) {
    if (event._isSingleDayTimed || event._isSingleHybridDayTimed) {
      timeGridEvents.push(event);
      continue;
    }
    if (event._isSingleDayFullDay || event._isMultiDayFullDay || event._isMultiDayTimed) {
      dateGridEvents.push(event);
    }
  }
  return { timeGridEvents, dateGridEvents };
};
const createOneDay = (week, date) => {
  const dateString = toDateString$1(date);
  week[dateString] = {
    date: dateString,
    timeGridEvents: [],
    dateGridEvents: {},
    backgroundEvents: []
  };
  return week;
};
const createWeek = ($app) => {
  if ($app.calendarState.view.value === InternalViewName.Day)
    return createOneDay({}, toJSDate$1($app.calendarState.range.value.start));
  return $app.timeUnitsImpl.getWeekFor(toJSDate$1($app.datePickerState.selectedDate.value)).slice(0, $app.config.weekOptions.value.nDays).reduce(createOneDay, {});
};
const positionInTimeGrid = (timeGridEvents, week, $app) => {
  var _a;
  for (const event of timeGridEvents) {
    const range = $app.calendarState.range.value;
    if (event.start >= range.start && event.end <= range.end) {
      let date = dateFromDateTime$1(event.start);
      const timeFromStart = timeFromDateTime$1(event.start);
      if (timePointsFromString$2(timeFromStart) < $app.config.dayBoundaries.value.start) {
        date = addDays(date, -1);
      }
      (_a = week[date]) === null || _a === void 0 ? void 0 : _a.timeGridEvents.push(event);
    }
  }
  return week;
};
const positionInDateGrid = (sortedDateGridEvents, week) => {
  const weekDates = Object.keys(week).sort();
  const firstDateOfWeek = weekDates[0];
  const lastDateOfWeek = weekDates[weekDates.length - 1];
  const occupiedLevels = /* @__PURE__ */ new Set();
  for (const event of sortedDateGridEvents) {
    const eventOriginalStartDate = dateFromDateTime$1(event.start);
    const eventOriginalEndDate = dateFromDateTime$1(event.end);
    const isEventStartInWeek = !!week[eventOriginalStartDate];
    let isEventInWeek = isEventStartInWeek;
    if (!isEventStartInWeek && eventOriginalStartDate < firstDateOfWeek && eventOriginalEndDate >= firstDateOfWeek) {
      isEventInWeek = true;
    }
    if (!isEventInWeek)
      continue;
    const firstDateOfEvent = isEventStartInWeek ? eventOriginalStartDate : firstDateOfWeek;
    const lastDateOfEvent = eventOriginalEndDate <= lastDateOfWeek ? eventOriginalEndDate : lastDateOfWeek;
    const eventDays = Object.values(week).filter((day) => {
      return day.date >= firstDateOfEvent && day.date <= lastDateOfEvent;
    });
    let levelInWeekForEvent;
    let testLevel = 0;
    while (levelInWeekForEvent === void 0) {
      const isLevelFree = eventDays.every((day) => {
        return !day.dateGridEvents[testLevel];
      });
      if (isLevelFree) {
        levelInWeekForEvent = testLevel;
        occupiedLevels.add(testLevel);
      } else
        testLevel++;
    }
    for (const [eventDayIndex, eventDay] of eventDays.entries()) {
      if (eventDayIndex === 0) {
        event._nDaysInGrid = eventDays.length;
        eventDay.dateGridEvents[levelInWeekForEvent] = event;
      } else {
        eventDay.dateGridEvents[levelInWeekForEvent] = DATE_GRID_BLOCKER;
      }
    }
  }
  for (const level of Array.from(occupiedLevels)) {
    for (const [, day] of Object.entries(week)) {
      if (!day.dateGridEvents[level]) {
        day.dateGridEvents[level] = void 0;
      }
    }
  }
  return week;
};
const getWidthToSubtract = (hasOverflowLeft, hasOverflowRight, enableOverflowSubtraction) => {
  let widthToSubtract = 2;
  const eventOverflowMargin = 10;
  if (hasOverflowLeft && enableOverflowSubtraction)
    widthToSubtract += eventOverflowMargin;
  if (hasOverflowRight && enableOverflowSubtraction)
    widthToSubtract += eventOverflowMargin;
  return widthToSubtract;
};
const getBorderRadius = (hasOverflowLeft, hasOverflowRight, forceZeroRule) => {
  return {
    borderBottomLeftRadius: hasOverflowLeft || forceZeroRule ? 0 : void 0,
    borderTopLeftRadius: hasOverflowLeft || forceZeroRule ? 0 : void 0,
    borderBottomRightRadius: hasOverflowRight || forceZeroRule ? 0 : void 0,
    borderTopRightRadius: hasOverflowRight || forceZeroRule ? 0 : void 0
  };
};
function DateGridEvent({ calendarEvent, gridRow, isCopy }) {
  var _a, _b, _c, _d;
  const $app = x(AppContext);
  const { eventCopy, updateCopy, createDragStartTimeout, setClickedEventIfNotDragging, setClickedEvent } = useEventInteractions($app);
  const eventCSSVariables = {
    borderLeft: `4px solid var(--sx-color-${calendarEvent._color})`,
    color: `var(--sx-color-on-${calendarEvent._color}-container)`,
    backgroundColor: `var(--sx-color-${calendarEvent._color}-container)`
  };
  const handleStartDrag = (uiEvent) => {
    var _a2;
    if (!$app.config.plugins.dragAndDrop)
      return;
    if ((_a2 = calendarEvent._options) === null || _a2 === void 0 ? void 0 : _a2.disableDND)
      return;
    if (isUIEventTouchEvent(uiEvent))
      uiEvent.preventDefault();
    const newEventCopy = deepCloneEvent(calendarEvent, $app);
    updateCopy(newEventCopy);
    $app.config.plugins.dragAndDrop.createDateGridDragHandler({
      eventCoordinates: getEventCoordinates(uiEvent),
      eventCopy: newEventCopy,
      updateCopy,
      $app
    });
  };
  const hasOverflowLeft = dateFromDateTime$1(calendarEvent.start) < dateFromDateTime$1($app.calendarState.range.value.start);
  const hasOverflowRight = dateFromDateTime$1(calendarEvent.end) > dateFromDateTime$1($app.calendarState.range.value.end);
  const overflowStyles = { backgroundColor: eventCSSVariables.backgroundColor };
  const customComponent = $app.config._customComponentFns.dateGridEvent;
  let customComponentId = customComponent ? "custom-date-grid-event-" + randomStringId() : void 0;
  if (isCopy && customComponentId)
    customComponentId += "-copy";
  y$1(() => {
    if (!customComponent)
      return;
    customComponent(getElementByCCID(customComponentId), {
      calendarEvent: calendarEvent._getExternalEvent()
    });
  }, [calendarEvent, eventCopy]);
  const startResize = (mouseEvent) => {
    mouseEvent.stopPropagation();
    const eventCopy2 = deepCloneEvent(calendarEvent, $app);
    updateCopy(eventCopy2);
    $app.config.plugins.resize.createDateGridEventResizer(eventCopy2, updateCopy, mouseEvent);
  };
  const handleKeyDown = (e2) => {
    if (e2.key === "Enter" || e2.key === " ") {
      e2.stopPropagation();
      setClickedEvent(e2, calendarEvent);
      invokeOnEventClickCallback($app, calendarEvent);
      nextTick(() => {
        focusModal($app);
      });
    }
  };
  const eventClasses = [
    "sx__event",
    "sx__date-grid-event",
    "sx__date-grid-cell"
  ];
  if (isCopy)
    eventClasses.push("sx__date-grid-event--copy");
  if (hasOverflowLeft)
    eventClasses.push("sx__date-grid-event--overflow-left");
  if (hasOverflowRight)
    eventClasses.push("sx__date-grid-event--overflow-right");
  if ((_a = calendarEvent._options) === null || _a === void 0 ? void 0 : _a.additionalClasses)
    eventClasses.push(...calendarEvent._options.additionalClasses);
  const borderLeftNonCustom = hasOverflowLeft ? "none" : eventCSSVariables.borderLeft;
  const hasCustomContent = (_b = calendarEvent._customContent) === null || _b === void 0 ? void 0 : _b.dateGrid;
  return u$2(k$1, { children: [u$2("div", { id: isCopy ? getTimeGridEventCopyElementId(calendarEvent.id) : void 0, tabIndex: 0, "aria-label": calendarEvent.title + " " + getTimeStamp(calendarEvent, $app.config.locale.value, $app.translate("to")), role: "button", "data-ccid": customComponentId, "data-event-id": calendarEvent.id, onMouseDown: (e2) => createDragStartTimeout(handleStartDrag, e2), onMouseUp: (e2) => setClickedEventIfNotDragging(calendarEvent, e2), onTouchStart: (e2) => createDragStartTimeout(handleStartDrag, e2), onTouchEnd: (e2) => setClickedEventIfNotDragging(calendarEvent, e2), onClick: () => invokeOnEventClickCallback($app, calendarEvent), onDblClick: () => invokeOnEventDoubleClickCallback($app, calendarEvent), onKeyDown: handleKeyDown, className: eventClasses.join(" "), style: {
    width: `calc(${calendarEvent._nDaysInGrid * 100}% - ${getWidthToSubtract(hasOverflowLeft, hasOverflowRight, !customComponent)}px)`,
    gridRow,
    display: eventCopy ? "none" : "flex",
    padding: customComponent ? "0px" : void 0,
    borderLeft: customComponent ? void 0 : borderLeftNonCustom,
    color: customComponent ? void 0 : eventCSSVariables.color,
    backgroundColor: customComponent ? void 0 : eventCSSVariables.backgroundColor,
    ...getBorderRadius(hasOverflowLeft, hasOverflowRight, !!customComponent)
  }, children: [!customComponent && !hasCustomContent && u$2(k$1, { children: [hasOverflowLeft && u$2("div", { className: "sx__date-grid-event--left-overflow", style: overflowStyles }), u$2("span", { className: "sx__date-grid-event-text", children: [calendarEvent.title, "  ", dateTimeStringRegex$1.test(calendarEvent.start) && u$2("span", { className: "sx__date-grid-event-time", children: timeFn(calendarEvent.start, $app.config.locale.value) })] }), hasOverflowRight && u$2("div", { className: "sx__date-grid-event--right-overflow", style: overflowStyles })] }), hasCustomContent && u$2("div", { dangerouslySetInnerHTML: {
    __html: ((_c = calendarEvent._customContent) === null || _c === void 0 ? void 0 : _c.dateGrid) || ""
  } }), $app.config.plugins.resize && !((_d = calendarEvent._options) === null || _d === void 0 ? void 0 : _d.disableResize) && !hasOverflowRight && u$2("div", { className: "sx__date-grid-event-resize-handle", onMouseDown: startResize })] }), eventCopy && u$2(DateGridEvent, { calendarEvent: eventCopy, gridRow, isCopy: true })] });
}
function DateGridDay({ calendarEvents, date, backgroundEvents }) {
  const $app = x(AppContext);
  const dateStart = date + " 00:00";
  const dateEnd = date + " 23:59";
  const fullDayBackgroundEvent = backgroundEvents.find((event) => {
    const eventStartWithTime = dateStringRegex$2.test(event.start) ? event.start + " 00:00" : event.start;
    const eventEndWithTime = dateStringRegex$2.test(event.end) ? event.end + " 23:59" : event.end;
    return eventStartWithTime <= dateStart && eventEndWithTime >= dateEnd;
  });
  const handleMouseDown = (e2) => {
    const callback = $app.config.callbacks.onMouseDownDateGridDate;
    if (!callback)
      return;
    callback(date, e2);
  };
  return u$2("div", { className: "sx__date-grid-day", "data-date-grid-date": date, children: [fullDayBackgroundEvent && u$2("div", { className: "sx__date-grid-background-event", title: fullDayBackgroundEvent.title, style: {
    ...fullDayBackgroundEvent.style
  } }), Object.values(calendarEvents).map((event, index) => {
    if (event === DATE_GRID_BLOCKER || !event)
      return u$2("div", { className: "sx__date-grid-cell", style: { gridRow: index + 1 }, onMouseDown: handleMouseDown });
    return u$2(DateGridEvent, { calendarEvent: event, gridRow: index + 1 });
  }), u$2("div", { className: "sx__spacer", onMouseDown: handleMouseDown })] });
}
const filterByRange = (events, range) => {
  return events.filter((event) => {
    let rangeStart = range.start;
    let rangeEnd = range.end;
    if (dateStringRegex$2.test(rangeStart))
      rangeStart = rangeStart + " 00:00";
    if (dateStringRegex$2.test(rangeEnd))
      rangeEnd = rangeEnd + " 23:59";
    let eventStart = event.start;
    let eventEnd = event.end;
    if (dateStringRegex$2.test(eventStart))
      eventStart = eventStart + " 00:00";
    if (dateStringRegex$2.test(eventEnd))
      eventEnd = eventEnd + " 23:59";
    const eventStartsInRange = eventStart >= rangeStart && eventStart <= rangeEnd;
    const eventEndInRange = eventEnd >= rangeStart && eventEnd <= rangeEnd;
    const eventStartBeforeAndEventEndAfterRange = eventStart < rangeStart && eventEnd > rangeEnd;
    return eventStartsInRange || eventEndInRange || eventStartBeforeAndEventEndAfterRange;
  });
};
const WeekWrapper = ({ $app, id }) => {
  document.documentElement.style.setProperty("--sx-week-grid-height", `${$app.config.weekOptions.value.gridHeight}px`);
  const [week, setWeek] = h$2({});
  useSignalEffect(() => {
    var _a, _b;
    const rangeStart = (_a = $app.calendarState.range.value) === null || _a === void 0 ? void 0 : _a.start;
    const rangeEnd = (_b = $app.calendarState.range.value) === null || _b === void 0 ? void 0 : _b.end;
    if (!rangeStart || !rangeEnd)
      return;
    let newWeek = createWeek($app);
    const filteredEvents = $app.calendarEvents.filterPredicate.value ? $app.calendarEvents.list.value.filter($app.calendarEvents.filterPredicate.value) : $app.calendarEvents.list.value;
    const { dateGridEvents, timeGridEvents } = sortEventsForWeekView(filteredEvents);
    newWeek = positionInDateGrid(dateGridEvents.sort(sortEventsByStartAndEnd), newWeek);
    Object.entries(newWeek).forEach(([date, day]) => {
      day.backgroundEvents = filterByRange($app.calendarEvents.backgroundEvents.value, {
        start: date,
        end: date
      });
    });
    newWeek = positionInTimeGrid(timeGridEvents, newWeek, $app);
    setWeek(newWeek);
  });
  return u$2(k$1, { children: u$2(AppContext.Provider, { value: $app, children: u$2("div", { className: "sx__week-wrapper", id, children: [u$2("div", { className: "sx__week-header", children: u$2("div", { className: "sx__week-header-content", children: [u$2(DateAxis, { week: Object.values(week).map((day) => toJSDate$1(day.date)) }), u$2("div", { className: "sx__date-grid", "aria-label": $app.translate("Full day- and multiple day events"), children: Object.values(week).map((day) => u$2(DateGridDay, { date: day.date, calendarEvents: day.dateGridEvents, backgroundEvents: day.backgroundEvents }, day.date)) }), u$2("div", { className: "sx__week-header-border" })] }) }), u$2("div", { className: "sx__week-grid", children: [u$2(TimeAxis, {}), Object.values(week).map((day) => u$2(TimeGridDay, { calendarEvents: day.timeGridEvents, backgroundEvents: day.backgroundEvents, date: day.date }, day.date))] })] }) }) });
};
const getRangeStartGivenDayBoundaries = (calendarConfig, date) => {
  return `${toDateString$1(date)} ${timeStringFromTimePoints$1(calendarConfig.dayBoundaries.value.start)}`;
};
const getRangeEndGivenDayBoundaries = (calendarConfig, date) => {
  let dayEndTimeString = timeStringFromTimePoints$1(calendarConfig.dayBoundaries.value.end);
  let newRangeEndDate = toDateString$1(date);
  if (calendarConfig.isHybridDay) {
    newRangeEndDate = addDays(newRangeEndDate, 1);
  }
  if (calendarConfig.dayBoundaries.value.end === 2400) {
    dayEndTimeString = "23:59";
  }
  return `${newRangeEndDate} ${dayEndTimeString}`;
};
const setRangeForWeek = (config2) => {
  const weekForDate = config2.timeUnitsImpl.getWeekFor(toJSDate$1(config2.date)).slice(0, config2.calendarConfig.weekOptions.value.nDays);
  return {
    start: getRangeStartGivenDayBoundaries(config2.calendarConfig, weekForDate[0]),
    end: getRangeEndGivenDayBoundaries(config2.calendarConfig, weekForDate[weekForDate.length - 1])
  };
};
const setRangeForMonth = (config2) => {
  const { year, month } = toIntegers(config2.date);
  const monthForDate = config2.timeUnitsImpl.getMonthWithTrailingAndLeadingDays(year, month);
  const newRangeEndDate = toDateString$1(monthForDate[monthForDate.length - 1][monthForDate[monthForDate.length - 1].length - 1]);
  return {
    start: toDateTimeString(monthForDate[0][0]),
    end: `${newRangeEndDate} 23:59`
  };
};
const setRangeForDay = (config2) => {
  return {
    start: getRangeStartGivenDayBoundaries(config2.calendarConfig, toJSDate$1(config2.date)),
    end: getRangeEndGivenDayBoundaries(config2.calendarConfig, toJSDate$1(config2.date))
  };
};
const config$3 = {
  name: InternalViewName.Week,
  label: "Week",
  Component: WeekWrapper,
  setDateRange: setRangeForWeek,
  hasSmallScreenCompat: false,
  hasWideScreenCompat: true,
  backwardForwardFn: addDays,
  backwardForwardUnits: 7
};
const viewWeek = createPreactView(config$3);
const createViewWeek = () => createPreactView(config$3);
const createWeekForMonth = (week, day) => {
  week.push({
    date: toDateString$1(day),
    events: {},
    backgroundEvents: []
  });
  return week;
};
const createMonth = (date, timeUnitsImpl) => {
  const { year, month: monthFromDate } = toIntegers(date);
  const monthWithDates = timeUnitsImpl.getMonthWithTrailingAndLeadingDays(year, monthFromDate);
  const month = [];
  for (const week of monthWithDates) {
    month.push(week.reduce(createWeekForMonth, []));
  }
  return month;
};
function MonthGridEvent({ gridRow, calendarEvent, date, isFirstWeek, isLastWeek }) {
  var _a, _b, _c, _d, _e2;
  const $app = x(AppContext);
  const hasOverflowLeft = isFirstWeek && ((_a = $app.calendarState.range.value) === null || _a === void 0 ? void 0 : _a.start) && dateFromDateTime$1(calendarEvent.start) < dateFromDateTime$1($app.calendarState.range.value.start);
  const hasOverflowRight = isLastWeek && ((_b = $app.calendarState.range.value) === null || _b === void 0 ? void 0 : _b.end) && dateFromDateTime$1(calendarEvent.end) > dateFromDateTime$1($app.calendarState.range.value.end);
  const { createDragStartTimeout, setClickedEventIfNotDragging, setClickedEvent } = useEventInteractions($app);
  const hasStartDate = dateFromDateTime$1(calendarEvent.start) === date;
  const nDays = calendarEvent._eventFragments[date];
  const eventCSSVariables = {
    borderLeft: hasStartDate ? `4px solid var(--sx-color-${calendarEvent._color})` : void 0,
    color: `var(--sx-color-on-${calendarEvent._color}-container)`,
    backgroundColor: `var(--sx-color-${calendarEvent._color}-container)`,
    // CORRELATION ID: 2 (10px subtracted from width)
    // nDays * 100% for the width of each day + 1px for border - 10 px for horizontal gap between events
    width: `calc(${nDays * 100 + "%"} + ${nDays}px - 10px)`
  };
  const handleStartDrag = (uiEvent) => {
    var _a2;
    if (isUIEventTouchEvent(uiEvent))
      uiEvent.preventDefault();
    if (!uiEvent.target)
      return;
    if (!$app.config.plugins.dragAndDrop || ((_a2 = calendarEvent._options) === null || _a2 === void 0 ? void 0 : _a2.disableDND))
      return;
    $app.config.plugins.dragAndDrop.createMonthGridDragHandler(calendarEvent, $app);
  };
  const customComponent = $app.config._customComponentFns.monthGridEvent;
  const customComponentId = customComponent ? "custom-month-grid-event-" + randomStringId() : void 0;
  y$1(() => {
    if (!customComponent)
      return;
    customComponent(getElementByCCID(customComponentId), {
      calendarEvent: calendarEvent._getExternalEvent(),
      hasStartDate
    });
  }, [calendarEvent]);
  const handleOnClick = (e2) => {
    e2.stopPropagation();
    invokeOnEventClickCallback($app, calendarEvent);
  };
  const handleOnDoubleClick = (e2) => {
    e2.stopPropagation();
    invokeOnEventDoubleClickCallback($app, calendarEvent);
  };
  const handleKeyDown = (e2) => {
    if (e2.key === "Enter" || e2.key === " ") {
      e2.stopPropagation();
      setClickedEvent(e2, calendarEvent);
      invokeOnEventClickCallback($app, calendarEvent);
      nextTick(() => {
        focusModal($app);
      });
    }
  };
  const classNames = [
    "sx__event",
    "sx__month-grid-event",
    "sx__month-grid-cell"
  ];
  if ((_c = calendarEvent._options) === null || _c === void 0 ? void 0 : _c.additionalClasses) {
    classNames.push(...calendarEvent._options.additionalClasses);
  }
  if (hasOverflowLeft)
    classNames.push("sx__month-grid-event--overflow-left");
  if (hasOverflowRight)
    classNames.push("sx__month-grid-event--overflow-right");
  const hasCustomContent = (_d = calendarEvent._customContent) === null || _d === void 0 ? void 0 : _d.monthGrid;
  return u$2("div", { draggable: !!$app.config.plugins.dragAndDrop, "data-event-id": calendarEvent.id, "data-ccid": customComponentId, onMouseDown: (e2) => createDragStartTimeout(handleStartDrag, e2), onMouseUp: (e2) => setClickedEventIfNotDragging(calendarEvent, e2), onTouchStart: (e2) => createDragStartTimeout(handleStartDrag, e2), onTouchEnd: (e2) => setClickedEventIfNotDragging(calendarEvent, e2), onClick: handleOnClick, onDblClick: handleOnDoubleClick, onKeyDown: handleKeyDown, className: classNames.join(" "), style: {
    gridRow,
    width: eventCSSVariables.width,
    padding: customComponent ? "0px" : void 0,
    borderLeft: customComponent ? void 0 : eventCSSVariables.borderLeft,
    color: customComponent ? void 0 : eventCSSVariables.color,
    backgroundColor: customComponent ? void 0 : eventCSSVariables.backgroundColor
  }, tabIndex: 0, role: "button", children: [!customComponent && !hasCustomContent && u$2(k$1, { children: [dateTimeStringRegex$1.test(calendarEvent.start) && u$2("div", { className: "sx__month-grid-event-time", children: timeFn(calendarEvent.start, $app.config.locale.value) }), u$2("div", { className: "sx__month-grid-event-title", children: calendarEvent.title })] }), hasCustomContent && u$2("div", { dangerouslySetInnerHTML: {
    __html: ((_e2 = calendarEvent._customContent) === null || _e2 === void 0 ? void 0 : _e2.monthGrid) || ""
  } })] });
}
function MonthGridDay({ day, isFirstWeek, isLastWeek }) {
  const $app = x(AppContext);
  const nEventsInDay = Object.values(day.events).filter((event) => typeof event === "object" || event === DATE_GRID_BLOCKER).length;
  const getEventTranslationSingularOrPlural = (nOfAdditionalEvents) => {
    if (nOfAdditionalEvents === 1)
      return $app.translate("event");
    return $app.translate("events");
  };
  const getAriaLabelSingularOrPlural = (nOfAdditionalEvents) => {
    if (nOfAdditionalEvents === 1) {
      return $app.translate("Link to 1 more event on {{date}}", {
        date: getLocalizedDate(day.date, $app.config.locale.value)
      });
    }
    return $app.translate("Link to {{n}} more events on {{date}}", {
      date: getLocalizedDate(day.date, $app.config.locale.value),
      n: nEventsInDay - $app.config.monthGridOptions.value.nEventsPerDay
    });
  };
  const handleClickAdditionalEvents = (e2) => {
    e2.stopPropagation();
    if ($app.config.callbacks.onClickPlusEvents)
      $app.config.callbacks.onClickPlusEvents(day.date);
    if (!$app.config.views.value.find((view) => view.name === InternalViewName.Day))
      return;
    setTimeout(() => {
      $app.datePickerState.selectedDate.value = day.date;
      $app.calendarState.setView(InternalViewName.Day, day.date);
    }, 250);
  };
  const dateClassNames = ["sx__month-grid-day__header-date"];
  const jsDate = toJSDate$1(day.date);
  const dayDate = jsDate;
  if (isToday(dayDate))
    dateClassNames.push("sx__is-today");
  const { month: selectedDateMonth } = toIntegers($app.datePickerState.selectedDate.value);
  const { month: dayMonth } = toIntegers(day.date);
  const baseClasses = [
    "sx__month-grid-day",
    getClassNameForWeekday(jsDate.getDay())
  ];
  const [wrapperClasses, setWrapperClasses] = h$2(baseClasses);
  y$1(() => {
    const classes = [...baseClasses];
    if (dayMonth !== selectedDateMonth)
      classes.push("is-leading-or-trailing");
    if ($app.datePickerState.selectedDate.value === day.date)
      classes.push("is-selected");
    setWrapperClasses(classes);
  }, [$app.datePickerState.selectedDate.value]);
  const getNumberOfNonDisplayedEvents = () => {
    return Object.values(day.events).slice($app.config.monthGridOptions.value.nEventsPerDay).filter((event) => event === DATE_GRID_BLOCKER || typeof event === "object").length;
  };
  const numberOfNonDisplayedEvents = getNumberOfNonDisplayedEvents();
  const dayStartDateTime = day.date + " 00:00";
  const dayEndDateTime = day.date + " 23:59";
  const fullDayBackgroundEvent = day.backgroundEvents.find((event) => {
    const eventStartWithTime = dateStringRegex$2.test(event.start) ? event.start + " 00:00" : event.start;
    const eventEndWithTime = dateStringRegex$2.test(event.end) ? event.end + " 23:59" : event.end;
    return eventStartWithTime <= dayStartDateTime && eventEndWithTime >= dayEndDateTime;
  });
  const handleMouseDown = (e2) => {
    const target = e2.target;
    if (!target.classList.contains("sx__month-grid-day"))
      return;
    const callback = $app.config.callbacks.onMouseDownMonthGridDate;
    if (callback)
      callback(day.date, e2);
  };
  return u$2("div", { className: wrapperClasses.join(" "), "data-date": day.date, onClick: () => $app.config.callbacks.onClickDate && $app.config.callbacks.onClickDate(day.date), "aria-label": getLocalizedDate(day.date, $app.config.locale.value), onDblClick: () => {
    var _a, _b;
    return (_b = (_a = $app.config.callbacks).onDoubleClickDate) === null || _b === void 0 ? void 0 : _b.call(_a, day.date);
  }, onMouseDown: handleMouseDown, children: [fullDayBackgroundEvent && u$2(k$1, { children: u$2("div", { className: "sx__month-grid-background-event", title: fullDayBackgroundEvent.title, style: {
    ...fullDayBackgroundEvent.style
  } }) }), u$2("div", { className: "sx__month-grid-day__header", children: [isFirstWeek ? u$2("div", { className: "sx__month-grid-day__header-day-name", children: getDayNameShort(dayDate, $app.config.locale.value) }) : null, u$2("div", { className: dateClassNames.join(" "), children: dayDate.getDate() })] }), u$2("div", { className: "sx__month-grid-day__events", children: Object.values(day.events).slice(0, $app.config.monthGridOptions.value.nEventsPerDay).map((event, index) => {
    if (typeof event !== "object")
      return u$2("div", { className: "sx__month-grid-blocker sx__month-grid-cell", style: { gridRow: index + 1 } });
    return u$2(MonthGridEvent, { gridRow: index + 1, calendarEvent: event, date: day.date, isFirstWeek, isLastWeek });
  }) }), numberOfNonDisplayedEvents > 0 ? u$2("button", { type: "button", className: "sx__month-grid-day__events-more sx__ripple--wide", "aria-label": getAriaLabelSingularOrPlural(numberOfNonDisplayedEvents), onClick: handleClickAdditionalEvents, children: `+ ${numberOfNonDisplayedEvents} ${getEventTranslationSingularOrPlural(numberOfNonDisplayedEvents)}` }) : null] });
}
function MonthGridWeek({ week, isFirstWeek, isLastWeek }) {
  return u$2("div", { className: "sx__month-grid-week", children: week.map((day) => {
    const dateKey = day.date;
    return u$2(MonthGridDay, { day, isFirstWeek, isLastWeek }, dateKey);
  }) });
}
const positionInMonthWeek = (sortedEvents, week) => {
  const weekDates = Object.keys(week).sort();
  const firstDateOfWeek = weekDates[0];
  const lastDateOfWeek = weekDates[weekDates.length - 1];
  const occupiedLevels = /* @__PURE__ */ new Set();
  for (const event of sortedEvents) {
    const eventOriginalStartDate = dateFromDateTime$1(event.start);
    const eventOriginalEndDate = dateFromDateTime$1(event.end);
    const isEventStartInWeek = !!week[eventOriginalStartDate];
    let isEventInWeek = isEventStartInWeek;
    if (!isEventStartInWeek && eventOriginalStartDate < firstDateOfWeek && eventOriginalEndDate >= firstDateOfWeek) {
      isEventInWeek = true;
    }
    if (!isEventInWeek)
      continue;
    const firstDateOfEvent = isEventStartInWeek ? eventOriginalStartDate : firstDateOfWeek;
    const lastDateOfEvent = eventOriginalEndDate <= lastDateOfWeek ? eventOriginalEndDate : lastDateOfWeek;
    const eventDays = Object.values(week).filter((day) => {
      return day.date >= firstDateOfEvent && day.date <= lastDateOfEvent;
    });
    let levelInWeekForEvent;
    let testLevel = 0;
    while (levelInWeekForEvent === void 0) {
      const isLevelFree = eventDays.every((day) => {
        return !day.events[testLevel];
      });
      if (isLevelFree) {
        levelInWeekForEvent = testLevel;
        occupiedLevels.add(testLevel);
      } else
        testLevel++;
    }
    for (const [eventDayIndex, eventDay] of eventDays.entries()) {
      if (eventDayIndex === 0) {
        event._eventFragments[firstDateOfEvent] = eventDays.length;
        eventDay.events[levelInWeekForEvent] = event;
      } else {
        eventDay.events[levelInWeekForEvent] = DATE_GRID_BLOCKER;
      }
    }
  }
  for (const level of Array.from(occupiedLevels)) {
    for (const [, day] of Object.entries(week)) {
      if (!day.events[level]) {
        day.events[level] = void 0;
      }
    }
  }
  return week;
};
const positionInMonth = (month, sortedEvents) => {
  const weeks = [];
  month.forEach((week) => {
    const weekMap = {};
    week.forEach((day) => weekMap[day.date] = day);
    weeks.push(weekMap);
  });
  weeks.forEach((week) => positionInMonthWeek(sortedEvents, week));
  return month;
};
const MonthGridWrapper = ({ $app, id }) => {
  const [month, setMonth] = h$2([]);
  useSignalEffect(() => {
    $app.calendarEvents.list.value.forEach((event) => {
      event._eventFragments = {};
    });
    const newMonth = createMonth($app.datePickerState.selectedDate.value, $app.timeUnitsImpl);
    newMonth.forEach((week) => {
      week.forEach((day) => {
        day.backgroundEvents = filterByRange($app.calendarEvents.backgroundEvents.value, {
          start: day.date,
          end: day.date
        });
      });
    });
    const filteredEvents = $app.calendarEvents.filterPredicate.value ? $app.calendarEvents.list.value.filter($app.calendarEvents.filterPredicate.value) : $app.calendarEvents.list.value;
    setMonth(positionInMonth(newMonth, filteredEvents.sort(sortEventsByStartAndEndWithoutConsideringTime)));
  });
  return u$2(AppContext.Provider, { value: $app, children: u$2("div", { id, className: "sx__month-grid-wrapper", children: month.map((week, index) => u$2(MonthGridWeek, { week, isFirstWeek: index === 0, isLastWeek: index === month.length - 1 }, index)) }) });
};
const config$2 = {
  name: InternalViewName.MonthGrid,
  label: "Month",
  setDateRange: setRangeForMonth,
  Component: MonthGridWrapper,
  hasWideScreenCompat: true,
  hasSmallScreenCompat: false,
  backwardForwardFn: addMonths,
  backwardForwardUnits: 1
};
createPreactView(config$2);
const DayWrapper = ({ $app, id }) => {
  return u$2(WeekWrapper, { "$app": $app, id });
};
const config$1 = {
  name: InternalViewName.Day,
  label: "Day",
  setDateRange: setRangeForDay,
  hasWideScreenCompat: true,
  hasSmallScreenCompat: true,
  Component: DayWrapper,
  backwardForwardFn: addDays,
  backwardForwardUnits: 1
};
createPreactView(config$1);
const createViewDay = () => createPreactView(config$1);
const createAgendaMonth = (date, timeUnitsImpl) => {
  const { year, month } = toIntegers(date);
  const monthWithDates = timeUnitsImpl.getMonthWithTrailingAndLeadingDays(year, month);
  return {
    weeks: monthWithDates.map((week) => {
      return week.map((date2) => {
        return {
          date: toDateString$1(date2),
          events: []
        };
      });
    })
  };
};
function MonthAgendaDay({ day, isActive, setActiveDate }) {
  const $app = x(AppContext);
  const { month: monthSelected } = toIntegers($app.datePickerState.selectedDate.value);
  const { month: monthOfDay } = toIntegers(day.date);
  const jsDate = toJSDate$1(day.date);
  const dayClasses = [
    "sx__month-agenda-day",
    getClassNameForWeekday(jsDate.getDay())
  ];
  if (isActive)
    dayClasses.push("sx__month-agenda-day--active");
  if (monthOfDay !== monthSelected)
    dayClasses.push("is-leading-or-trailing");
  const handleClick = (e2, callback) => {
    setActiveDate(day.date);
    if (!callback)
      return;
    callback(day.date);
  };
  const hasFocus = (weekDay) => weekDay.date === $app.datePickerState.selectedDate.value;
  const handleKeyDown = (event) => {
    const keyMapDaysToAdd = /* @__PURE__ */ new Map([
      ["ArrowDown", 7],
      ["ArrowUp", -7],
      ["ArrowLeft", -1],
      ["ArrowRight", 1]
    ]);
    $app.datePickerState.selectedDate.value = addDays($app.datePickerState.selectedDate.value, keyMapDaysToAdd.get(event.key) || 0);
  };
  const isBeforeMinDate = !!($app.config.minDate.value && day.date < $app.config.minDate.value);
  const isPastMaxDate = !!($app.config.maxDate.value && day.date > $app.config.maxDate.value);
  return u$2("button", { type: "button", className: dayClasses.join(" "), onClick: (e2) => handleClick(e2, $app.config.callbacks.onClickAgendaDate), onDblClick: (e2) => handleClick(e2, $app.config.callbacks.onDoubleClickAgendaDate), disabled: isBeforeMinDate || isPastMaxDate, "aria-label": getLocalizedDate(day.date, $app.config.locale.value), tabIndex: hasFocus(day) ? 0 : -1, "data-agenda-focus": hasFocus(day) ? "true" : void 0, onKeyDown: handleKeyDown, children: [u$2("div", { children: jsDate.getDate() }), u$2("div", { className: "sx__month-agenda-day__event-icons", children: day.events.slice(0, 3).map((event) => u$2("div", { style: {
    backgroundColor: `var(--sx-color-${event._color})`,
    filter: `brightness(1.6)`
  }, className: "sx__month-agenda-day__event-icon" })) })] });
}
function MonthAgendaWeek({ week, setActiveDate, activeDate }) {
  return u$2("div", { className: "sx__month-agenda-week", children: week.map((day, index) => u$2(MonthAgendaDay, { setActiveDate, day, isActive: activeDate === day.date }, index + day.date)) });
}
function MonthAgendaDayNames({ week }) {
  const $app = x(AppContext);
  const localizedShortDayNames = getOneLetterOrShortDayNames(week.map((day) => toJSDate$1(day.date)), $app.config.locale.value);
  return u$2("div", { className: "sx__month-agenda-day-names", children: localizedShortDayNames.map((oneLetterDayName) => u$2("div", { className: "sx__month-agenda-day-name", children: oneLetterDayName })) });
}
const getAllEventDates = (startDate, endDate) => {
  let currentDate = startDate;
  const dates = [currentDate];
  while (currentDate < endDate) {
    currentDate = addDays(currentDate, 1);
    dates.push(currentDate);
  }
  return dates;
};
const placeEventInDay = (allDaysMap) => (event) => {
  getAllEventDates(dateFromDateTime$1(event.start), dateFromDateTime$1(event.end)).forEach((date) => {
    if (allDaysMap[date]) {
      allDaysMap[date].events.push(event);
    }
  });
};
const positionEventsInAgenda = (agendaMonth, eventsSortedByStart) => {
  const allDaysMap = agendaMonth.weeks.reduce((acc, week) => {
    week.forEach((day) => {
      acc[day.date] = day;
    });
    return acc;
  }, {});
  eventsSortedByStart.forEach(placeEventInDay(allDaysMap));
  return agendaMonth;
};
function MonthAgendaEvent({ calendarEvent }) {
  var _a, _b;
  const $app = x(AppContext);
  const { setClickedEvent } = useEventInteractions($app);
  const eventCSSVariables = {
    backgroundColor: `var(--sx-color-${calendarEvent._color}-container)`,
    color: `var(--sx-color-on-${calendarEvent._color}-container)`,
    borderLeft: `4px solid var(--sx-color-${calendarEvent._color})`
  };
  const customComponent = $app.config._customComponentFns.monthAgendaEvent;
  const customComponentId = customComponent ? "custom-month-agenda-event-" + calendarEvent.id : void 0;
  y$1(() => {
    if (!customComponent)
      return;
    customComponent(getElementByCCID(customComponentId), {
      calendarEvent: calendarEvent._getExternalEvent()
    });
  }, [calendarEvent]);
  const onClick = (e2) => {
    setClickedEvent(e2, calendarEvent);
    invokeOnEventClickCallback($app, calendarEvent);
  };
  const onDoubleClick = (e2) => {
    setClickedEvent(e2, calendarEvent);
    invokeOnEventDoubleClickCallback($app, calendarEvent);
  };
  const onKeyDown = (e2) => {
    if (e2.key === "Enter" || e2.key === " ") {
      e2.stopPropagation();
      setClickedEvent(e2, calendarEvent);
      invokeOnEventClickCallback($app, calendarEvent);
      nextTick(() => {
        focusModal($app);
      });
    }
  };
  const hasCustomContent = (_a = calendarEvent._customContent) === null || _a === void 0 ? void 0 : _a.monthAgenda;
  return u$2("div", { className: "sx__event sx__month-agenda-event", "data-ccid": customComponentId, "data-event-id": calendarEvent.id, style: {
    backgroundColor: customComponent ? void 0 : eventCSSVariables.backgroundColor,
    color: customComponent ? void 0 : eventCSSVariables.color,
    borderLeft: customComponent ? void 0 : eventCSSVariables.borderLeft,
    padding: customComponent ? "0px" : void 0
  }, onClick: (e2) => onClick(e2), onDblClick: (e2) => onDoubleClick(e2), onKeyDown, tabIndex: 0, role: "button", children: [!customComponent && !hasCustomContent && u$2(k$1, { children: [u$2("div", { className: "sx__month-agenda-event__title", children: calendarEvent.title }), u$2("div", { className: "sx__month-agenda-event__time sx__month-agenda-event__has-icon", children: [u$2(TimeIcon, { strokeColor: `var(--sx-color-on-${calendarEvent._color}-container)` }), u$2("div", { dangerouslySetInnerHTML: {
    __html: getTimeStamp(calendarEvent, $app.config.locale.value)
  } })] })] }), hasCustomContent && u$2("div", { dangerouslySetInnerHTML: {
    __html: ((_b = calendarEvent._customContent) === null || _b === void 0 ? void 0 : _b.monthAgenda) || ""
  } })] });
}
function MonthAgendaEvents({ events }) {
  const $app = x(AppContext);
  return u$2("div", { className: "sx__month-agenda-events", children: events.length ? events.map((event) => u$2(MonthAgendaEvent, { calendarEvent: event }, event.id)) : u$2("div", { className: "sx__month-agenda-events__empty", children: $app.translate("No events") }) });
}
const MonthAgendaWrapper = ({ $app, id }) => {
  var _a;
  const getMonth = () => {
    const filteredEvents = $app.calendarEvents.filterPredicate.value ? $app.calendarEvents.list.value.filter($app.calendarEvents.filterPredicate.value) : $app.calendarEvents.list.value;
    return positionEventsInAgenda(createAgendaMonth($app.datePickerState.selectedDate.value, $app.timeUnitsImpl), filteredEvents.sort(sortEventsByStartAndEnd));
  };
  const [agendaMonth, setAgendaMonth] = h$2(getMonth());
  y$1(() => {
    setAgendaMonth(getMonth());
  }, [
    $app.datePickerState.selectedDate.value,
    $app.calendarEvents.list.value,
    $app.calendarEvents.filterPredicate.value
  ]);
  y$1(() => {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        const mutatedElement = mutation.target;
        if (mutatedElement.dataset.agendaFocus === "true")
          mutatedElement.focus();
      });
    });
    const monthViewElement = document.getElementById(id);
    observer.observe(monthViewElement, {
      childList: true,
      subtree: true,
      attributes: true
    });
    return () => observer.disconnect();
  }, []);
  return u$2(AppContext.Provider, { value: $app, children: u$2("div", { id, className: "sx__month-agenda-wrapper", children: [u$2(MonthAgendaDayNames, { week: agendaMonth.weeks[0] }), u$2("div", { className: "sx__month-agenda-weeks", children: agendaMonth.weeks.map((week, index) => u$2(MonthAgendaWeek, { week, setActiveDate: (dateString) => $app.datePickerState.selectedDate.value = dateString, activeDate: $app.datePickerState.selectedDate.value }, index)) }), u$2(MonthAgendaEvents, { events: ((_a = agendaMonth.weeks.flat().find((day) => day.date === $app.datePickerState.selectedDate.value)) === null || _a === void 0 ? void 0 : _a.events) || [] }, $app.datePickerState.selectedDate.value)] }) });
};
const config = {
  name: InternalViewName.MonthAgenda,
  label: "Month",
  setDateRange: setRangeForMonth,
  Component: MonthAgendaWrapper,
  hasSmallScreenCompat: true,
  hasWideScreenCompat: false,
  backwardForwardFn: addMonths,
  backwardForwardUnits: 1
};
createPreactView(config);
const timeStringRegex$1 = /^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
const dateTimeStringRegex = /^(\d{4})-(\d{2})-(\d{2}) (0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
const dateStringRegex$1 = /^(\d{4})-(\d{2})-(\d{2})$/;
const DateFormats = {
  DATE_STRING: /^\d{4}-\d{2}-\d{2}$/,
  TIME_STRING: /^\d{2}:\d{2}$/,
  DATE_TIME_STRING: /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/
};
class InvalidDateTimeError2 extends Error {
  constructor(dateTimeSpecification) {
    super(`Invalid date time specification: ${dateTimeSpecification}`);
  }
}
const toJSDate = (dateTimeSpecification) => {
  if (!DateFormats.DATE_TIME_STRING.test(dateTimeSpecification) && !DateFormats.DATE_STRING.test(dateTimeSpecification))
    throw new InvalidDateTimeError2(dateTimeSpecification);
  return new Date(
    Number(dateTimeSpecification.slice(0, 4)),
    Number(dateTimeSpecification.slice(5, 7)) - 1,
    Number(dateTimeSpecification.slice(8, 10)),
    Number(dateTimeSpecification.slice(11, 13)),
    // for date strings this will be 0
    Number(dateTimeSpecification.slice(14, 16))
    // for date strings this will be 0
  );
};
let NumberRangeError$1 = class NumberRangeError2 extends Error {
  constructor(min, max) {
    super(`Number must be between ${min} and ${max}.`);
    Object.defineProperty(this, "min", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: min
    });
    Object.defineProperty(this, "max", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: max
    });
  }
};
const doubleDigit$1 = (number) => {
  if (number < 0 || number > 99)
    throw new NumberRangeError$1(0, 99);
  return String(number).padStart(2, "0");
};
const toDateString = (date) => {
  return `${date.getFullYear()}-${doubleDigit$1(date.getMonth() + 1)}-${doubleDigit$1(date.getDate())}`;
};
let InvalidTimeStringError$1 = class InvalidTimeStringError2 extends Error {
  constructor(timeString) {
    super(`Invalid time string: ${timeString}`);
  }
};
const minuteTimePointMultiplier$1 = 1.6666666666666667;
const timePointsFromString$1 = (timeString) => {
  if (!timeStringRegex$1.test(timeString) && timeString !== "24:00")
    throw new InvalidTimeStringError$1(timeString);
  const [hoursInt, minutesInt] = timeString.split(":").map((time) => parseInt(time, 10));
  let minutePoints = (minutesInt * minuteTimePointMultiplier$1).toString();
  if (minutePoints.split(".")[0].length < 2)
    minutePoints = `0${minutePoints}`;
  return Number(hoursInt + minutePoints);
};
const dateFromDateTime = (dateTime) => {
  return dateTime.slice(0, 10);
};
const timeFromDateTime = (dateTime) => {
  return dateTime.slice(11);
};
var WeekDay;
(function(WeekDay2) {
  WeekDay2[WeekDay2["SUNDAY"] = 0] = "SUNDAY";
  WeekDay2[WeekDay2["MONDAY"] = 1] = "MONDAY";
  WeekDay2[WeekDay2["TUESDAY"] = 2] = "TUESDAY";
  WeekDay2[WeekDay2["WEDNESDAY"] = 3] = "WEDNESDAY";
  WeekDay2[WeekDay2["THURSDAY"] = 4] = "THURSDAY";
  WeekDay2[WeekDay2["FRIDAY"] = 5] = "FRIDAY";
  WeekDay2[WeekDay2["SATURDAY"] = 6] = "SATURDAY";
})(WeekDay || (WeekDay = {}));
WeekDay.MONDAY;
const DEFAULT_EVENT_COLOR_NAME = "primary";
class CalendarEventImpl2 {
  constructor(_config, id, start, end, title, people, location, description, calendarId, _options = void 0, _customContent = {}, _foreignProperties = {}) {
    Object.defineProperty(this, "_config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _config
    });
    Object.defineProperty(this, "id", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: id
    });
    Object.defineProperty(this, "start", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: start
    });
    Object.defineProperty(this, "end", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: end
    });
    Object.defineProperty(this, "title", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: title
    });
    Object.defineProperty(this, "people", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: people
    });
    Object.defineProperty(this, "location", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: location
    });
    Object.defineProperty(this, "description", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: description
    });
    Object.defineProperty(this, "calendarId", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: calendarId
    });
    Object.defineProperty(this, "_options", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _options
    });
    Object.defineProperty(this, "_customContent", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _customContent
    });
    Object.defineProperty(this, "_foreignProperties", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _foreignProperties
    });
    Object.defineProperty(this, "_previousConcurrentEvents", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_totalConcurrentEvents", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_nDaysInGrid", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_eventFragments", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: {}
    });
  }
  get _isSingleDayTimed() {
    return dateTimeStringRegex.test(this.start) && dateTimeStringRegex.test(this.end) && dateFromDateTime(this.start) === dateFromDateTime(this.end);
  }
  get _isSingleDayFullDay() {
    return dateStringRegex$1.test(this.start) && dateStringRegex$1.test(this.end) && this.start === this.end;
  }
  get _isMultiDayTimed() {
    return dateTimeStringRegex.test(this.start) && dateTimeStringRegex.test(this.end) && dateFromDateTime(this.start) !== dateFromDateTime(this.end);
  }
  get _isMultiDayFullDay() {
    return dateStringRegex$1.test(this.start) && dateStringRegex$1.test(this.end) && this.start !== this.end;
  }
  get _isSingleHybridDayTimed() {
    if (!this._config.isHybridDay)
      return false;
    if (!dateTimeStringRegex.test(this.start) || !dateTimeStringRegex.test(this.end))
      return false;
    const startDate = dateFromDateTime(this.start);
    const endDate = dateFromDateTime(this.end);
    const endDateMinusOneDay = toDateString(new Date(toJSDate(endDate).getTime() - 864e5));
    if (startDate !== endDate && startDate !== endDateMinusOneDay)
      return false;
    const dayBoundaries = this._config.dayBoundaries.value;
    const eventStartTimePoints = timePointsFromString$1(timeFromDateTime(this.start));
    const eventEndTimePoints = timePointsFromString$1(timeFromDateTime(this.end));
    return eventStartTimePoints >= dayBoundaries.start && (eventEndTimePoints <= dayBoundaries.end || eventEndTimePoints > eventStartTimePoints) || eventStartTimePoints < dayBoundaries.end && eventEndTimePoints <= dayBoundaries.end;
  }
  get _color() {
    if (this.calendarId && this._config.calendars.value && this.calendarId in this._config.calendars.value) {
      return this._config.calendars.value[this.calendarId].colorName;
    }
    return DEFAULT_EVENT_COLOR_NAME;
  }
  _getForeignProperties() {
    return this._foreignProperties;
  }
  _getExternalEvent() {
    return {
      id: this.id,
      start: this.start,
      end: this.end,
      title: this.title,
      people: this.people,
      location: this.location,
      description: this.description,
      calendarId: this.calendarId,
      _options: this._options,
      ...this._getForeignProperties()
    };
  }
}
class CalendarEventBuilder2 {
  constructor(_config, id, start, end) {
    Object.defineProperty(this, "_config", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: _config
    });
    Object.defineProperty(this, "id", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: id
    });
    Object.defineProperty(this, "start", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: start
    });
    Object.defineProperty(this, "end", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: end
    });
    Object.defineProperty(this, "people", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "location", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "description", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "title", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "calendarId", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_foreignProperties", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: {}
    });
    Object.defineProperty(this, "_options", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "_customContent", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: {}
    });
  }
  build() {
    return new CalendarEventImpl2(this._config, this.id, this.start, this.end, this.title, this.people, this.location, this.description, this.calendarId, this._options, this._customContent, this._foreignProperties);
  }
  withTitle(title) {
    this.title = title;
    return this;
  }
  withPeople(people) {
    this.people = people;
    return this;
  }
  withLocation(location) {
    this.location = location;
    return this;
  }
  withDescription(description) {
    this.description = description;
    return this;
  }
  withForeignProperties(foreignProperties) {
    this._foreignProperties = foreignProperties;
    return this;
  }
  withCalendarId(calendarId) {
    this.calendarId = calendarId;
    return this;
  }
  withOptions(options) {
    this._options = options;
    return this;
  }
  withCustomContent(customContent) {
    this._customContent = customContent;
    return this;
  }
}
const externalEventToInternal = (event, config2) => {
  const { id, start, end, title, description, location, people, _options, ...foreignProperties } = event;
  return new CalendarEventBuilder2(config2, id, start, end).withTitle(title).withDescription(description).withLocation(location).withPeople(people).withCalendarId(event.calendarId).withOptions(_options).withForeignProperties(foreignProperties).withCustomContent(event._customContent).build();
};
class EventsFacadeImpl2 {
  constructor($app) {
    Object.defineProperty(this, "$app", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: $app
    });
  }
  set(events) {
    this.$app.calendarEvents.list.value = events.map((event) => externalEventToInternal(event, this.$app.config));
  }
  add(event) {
    const newEvent = externalEventToInternal(event, this.$app.config);
    const copiedEvents = [...this.$app.calendarEvents.list.value];
    copiedEvents.push(newEvent);
    this.$app.calendarEvents.list.value = copiedEvents;
  }
  get(id) {
    var _a;
    return (_a = this.$app.calendarEvents.list.value.find((event) => event.id === id)) === null || _a === void 0 ? void 0 : _a._getExternalEvent();
  }
  getAll() {
    return this.$app.calendarEvents.list.value.map((event) => event._getExternalEvent());
  }
  remove(id) {
    const index = this.$app.calendarEvents.list.value.findIndex((event) => event.id === id);
    const copiedEvents = [...this.$app.calendarEvents.list.value];
    copiedEvents.splice(index, 1);
    this.$app.calendarEvents.list.value = copiedEvents;
  }
  update(event) {
    const index = this.$app.calendarEvents.list.value.findIndex((e2) => e2.id === event.id);
    const copiedEvents = [...this.$app.calendarEvents.list.value];
    copiedEvents.splice(index, 1, externalEventToInternal(event, this.$app.config));
    this.$app.calendarEvents.list.value = copiedEvents;
  }
}
const definePlugin$1 = (name, definition) => {
  definition.name = name;
  return definition;
};
class EventsServicePluginImpl {
  constructor() {
    Object.defineProperty(this, "name", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: "EventsServicePlugin"
    });
    Object.defineProperty(this, "$app", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "eventsFacade", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
  }
  beforeRender($app) {
    this.$app = $app;
    this.eventsFacade = new EventsFacadeImpl2($app);
  }
  update(event) {
    this.eventsFacade.update(event);
  }
  add(event) {
    this.eventsFacade.add(event);
  }
  remove(id) {
    this.eventsFacade.remove(id);
  }
  get(id) {
    return this.eventsFacade.get(id);
  }
  getAll() {
    return this.eventsFacade.getAll();
  }
  set(events) {
    this.eventsFacade.set(events);
  }
  setBackgroundEvents(backgroundEvents) {
    this.$app.calendarEvents.backgroundEvents.value = backgroundEvents;
  }
}
const createEventsServicePlugin = () => {
  return definePlugin$1("eventsService", new EventsServicePluginImpl());
};
var PluginName;
(function(PluginName2) {
  PluginName2["DragAndDrop"] = "dragAndDrop";
  PluginName2["EventModal"] = "eventModal";
  PluginName2["ScrollController"] = "scrollController";
  PluginName2["EventRecurrence"] = "eventRecurrence";
  PluginName2["Resize"] = "resize";
  PluginName2["CalendarControls"] = "calendarControls";
  PluginName2["CurrentTime"] = "currentTime";
})(PluginName || (PluginName = {}));
const timeStringRegex = /^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
const dateStringRegex = /^(\d{4})-(\d{2})-(\d{2})$/;
class InvalidTimeStringError3 extends Error {
  constructor(timeString) {
    super(`Invalid time string: ${timeString}`);
  }
}
class NumberRangeError3 extends Error {
  constructor(min, max) {
    super(`Number must be between ${min} and ${max}.`);
    Object.defineProperty(this, "min", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: min
    });
    Object.defineProperty(this, "max", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: max
    });
  }
}
const doubleDigit = (number) => {
  if (number < 0 || number > 99)
    throw new NumberRangeError3(0, 99);
  return String(number).padStart(2, "0");
};
const minuteTimePointMultiplier = 1.6666666666666667;
const timePointsFromString = (timeString) => {
  if (!timeStringRegex.test(timeString) && timeString !== "24:00")
    throw new InvalidTimeStringError3(timeString);
  const [hoursInt, minutesInt] = timeString.split(":").map((time) => parseInt(time, 10));
  let minutePoints = (minutesInt * minuteTimePointMultiplier).toString();
  if (minutePoints.split(".")[0].length < 2)
    minutePoints = `0${minutePoints}`;
  return Number(hoursInt + minutePoints);
};
const timeStringFromTimePoints = (timePoints) => {
  const hours = Math.floor(timePoints / 100);
  const minutes = Math.round(timePoints % 100 / minuteTimePointMultiplier);
  return `${doubleDigit(hours)}:${doubleDigit(minutes)}`;
};
const definePlugin = (name, definition) => {
  definition.name = name;
  return definition;
};
class CalendarControlsPluginImpl {
  constructor() {
    Object.defineProperty(this, "name", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: PluginName.CalendarControls
    });
    Object.defineProperty(this, "$app", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: void 0
    });
    Object.defineProperty(this, "getDate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.datePickerState.selectedDate.value
    });
    Object.defineProperty(this, "getView", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.calendarState.view.value
    });
    Object.defineProperty(this, "getFirstDayOfWeek", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.config.firstDayOfWeek.value
    });
    Object.defineProperty(this, "getLocale", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.config.locale.value
    });
    Object.defineProperty(this, "getViews", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.config.views.value
    });
    Object.defineProperty(this, "getDayBoundaries", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => ({
        start: timeStringFromTimePoints(this.$app.config.dayBoundaries.value.start),
        end: timeStringFromTimePoints(this.$app.config.dayBoundaries.value.end)
      })
    });
    Object.defineProperty(this, "getWeekOptions", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.config.weekOptions.value
    });
    Object.defineProperty(this, "getCalendars", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.config.calendars.value
    });
    Object.defineProperty(this, "getMinDate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.config.minDate.value
    });
    Object.defineProperty(this, "getMaxDate", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.config.maxDate.value
    });
    Object.defineProperty(this, "getMonthGridOptions", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.config.monthGridOptions.value
    });
    Object.defineProperty(this, "getRange", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: () => this.$app.calendarState.range.value
    });
  }
  beforeRender($app) {
    this.$app = $app;
  }
  onRender($app) {
    this.$app = $app;
  }
  setDate(date) {
    if (!dateStringRegex.test(date))
      throw new Error("Invalid date. Expected format YYYY-MM-DD");
    this.$app.datePickerState.selectedDate.value = date;
  }
  setView(view) {
    const viewToSet = this.$app.config.views.value.find((v2) => v2.name === view);
    if (!viewToSet)
      throw new Error(`Invalid view name. Expected one of ${this.$app.config.views.value.map((v2) => v2.name).join(", ")}`);
    this.$app.calendarState.setView(view, this.$app.datePickerState.selectedDate.value);
  }
  setFirstDayOfWeek(firstDayOfWeek) {
    this.$app.config.firstDayOfWeek.value = firstDayOfWeek;
  }
  setLocale(locale) {
    this.$app.config.locale.value = locale;
  }
  setViews(views) {
    const currentViewName = this.$app.calendarState.view.value;
    const isCurrentViewInViews = views.some((view) => view.name === currentViewName);
    if (!isCurrentViewInViews)
      throw new Error(`Currently active view is not in given views. Expected to find ${currentViewName} in ${views.map((view) => view.name).join(",")}`);
    this.$app.config.views.value = views;
  }
  setDayBoundaries(dayBoundaries) {
    this.$app.config.dayBoundaries.value = {
      start: timePointsFromString(dayBoundaries.start),
      end: timePointsFromString(dayBoundaries.end)
    };
  }
  setWeekOptions(weekOptions) {
    this.$app.config.weekOptions.value = {
      ...this.$app.config.weekOptions.value,
      ...weekOptions
    };
  }
  setCalendars(calendars) {
    this.$app.config.calendars.value = calendars;
  }
  setMinDate(minDate) {
    this.$app.config.minDate.value = minDate;
  }
  setMaxDate(maxDate) {
    this.$app.config.maxDate.value = maxDate;
  }
  setMonthGridOptions(monthGridOptions) {
    this.$app.config.monthGridOptions.value = monthGridOptions;
  }
}
const createCalendarControlsPlugin = () => {
  return definePlugin("calendarControls", new CalendarControlsPluginImpl());
};
export {
  EventDay as E,
  Ji as J,
  createCalendarControlsPlugin as a,
  createCalendar as b,
  createEventsServicePlugin as c,
  createViewDay as d,
  createViewWeek as e,
  d$1 as f,
  E as g,
  h$2 as h,
  k$1 as k,
  r,
  u$2 as u,
  viewWeek as v,
  y$1 as y
};
