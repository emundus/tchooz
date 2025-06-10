(function polyfill() {
  const relList = document.createElement("link").relList;
  if (relList && relList.supports && relList.supports("modulepreload")) {
    return;
  }
  for (const link of document.querySelectorAll('link[rel="modulepreload"]')) {
    processPreload(link);
  }
  new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type !== "childList") {
        continue;
      }
      for (const node of mutation.addedNodes) {
        if (node.tagName === "LINK" && node.rel === "modulepreload")
          processPreload(node);
      }
    }
  }).observe(document, { childList: true, subtree: true });
  function getFetchOpts(link) {
    const fetchOpts = {};
    if (link.integrity) fetchOpts.integrity = link.integrity;
    if (link.referrerPolicy) fetchOpts.referrerPolicy = link.referrerPolicy;
    if (link.crossOrigin === "use-credentials")
      fetchOpts.credentials = "include";
    else if (link.crossOrigin === "anonymous") fetchOpts.credentials = "omit";
    else fetchOpts.credentials = "same-origin";
    return fetchOpts;
  }
  function processPreload(link) {
    if (link.ep)
      return;
    link.ep = true;
    const fetchOpts = getFetchOpts(link);
    fetch(link.href, fetchOpts);
  }
})();
/**
* @vue/shared v3.5.16
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
/*! #__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function makeMap(str) {
  const map = /* @__PURE__ */ Object.create(null);
  for (const key of str.split(",")) map[key] = 1;
  return (val) => val in map;
}
const EMPTY_OBJ = {};
const EMPTY_ARR = [];
const NOOP = () => {
};
const NO = () => false;
const isOn = (key) => key.charCodeAt(0) === 111 && key.charCodeAt(1) === 110 && // uppercase letter
(key.charCodeAt(2) > 122 || key.charCodeAt(2) < 97);
const isModelListener = (key) => key.startsWith("onUpdate:");
const extend = Object.assign;
const remove = (arr, el2) => {
  const i = arr.indexOf(el2);
  if (i > -1) {
    arr.splice(i, 1);
  }
};
const hasOwnProperty$1 = Object.prototype.hasOwnProperty;
const hasOwn = (val, key) => hasOwnProperty$1.call(val, key);
const isArray = Array.isArray;
const isMap = (val) => toTypeString(val) === "[object Map]";
const isSet = (val) => toTypeString(val) === "[object Set]";
const isDate = (val) => toTypeString(val) === "[object Date]";
const isFunction = (val) => typeof val === "function";
const isString = (val) => typeof val === "string";
const isSymbol = (val) => typeof val === "symbol";
const isObject = (val) => val !== null && typeof val === "object";
const isPromise = (val) => {
  return (isObject(val) || isFunction(val)) && isFunction(val.then) && isFunction(val.catch);
};
const objectToString = Object.prototype.toString;
const toTypeString = (value) => objectToString.call(value);
const toRawType = (value) => {
  return toTypeString(value).slice(8, -1);
};
const isPlainObject = (val) => toTypeString(val) === "[object Object]";
const isIntegerKey = (key) => isString(key) && key !== "NaN" && key[0] !== "-" && "" + parseInt(key, 10) === key;
const isReservedProp = /* @__PURE__ */ makeMap(
  // the leading comma is intentional so empty string "" is also included
  ",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"
);
const cacheStringFunction = (fn2) => {
  const cache = /* @__PURE__ */ Object.create(null);
  return (str) => {
    const hit = cache[str];
    return hit || (cache[str] = fn2(str));
  };
};
const camelizeRE = /-(\w)/g;
const camelize = cacheStringFunction(
  (str) => {
    return str.replace(camelizeRE, (_, c) => c ? c.toUpperCase() : "");
  }
);
const hyphenateRE = /\B([A-Z])/g;
const hyphenate = cacheStringFunction(
  (str) => str.replace(hyphenateRE, "-$1").toLowerCase()
);
const capitalize = cacheStringFunction((str) => {
  return str.charAt(0).toUpperCase() + str.slice(1);
});
const toHandlerKey = cacheStringFunction(
  (str) => {
    const s = str ? `on${capitalize(str)}` : ``;
    return s;
  }
);
const hasChanged = (value, oldValue) => !Object.is(value, oldValue);
const invokeArrayFns = (fns, ...arg) => {
  for (let i = 0; i < fns.length; i++) {
    fns[i](...arg);
  }
};
const def = (obj, key, value, writable = false) => {
  Object.defineProperty(obj, key, {
    configurable: true,
    enumerable: false,
    writable,
    value
  });
};
const looseToNumber = (val) => {
  const n2 = parseFloat(val);
  return isNaN(n2) ? val : n2;
};
const toNumber = (val) => {
  const n2 = isString(val) ? Number(val) : NaN;
  return isNaN(n2) ? val : n2;
};
let _globalThis;
const getGlobalThis = () => {
  return _globalThis || (_globalThis = typeof globalThis !== "undefined" ? globalThis : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : typeof global !== "undefined" ? global : {});
};
function normalizeStyle(value) {
  if (isArray(value)) {
    const res = {};
    for (let i = 0; i < value.length; i++) {
      const item = value[i];
      const normalized = isString(item) ? parseStringStyle(item) : normalizeStyle(item);
      if (normalized) {
        for (const key in normalized) {
          res[key] = normalized[key];
        }
      }
    }
    return res;
  } else if (isString(value) || isObject(value)) {
    return value;
  }
}
const listDelimiterRE = /;(?![^(]*\))/g;
const propertyDelimiterRE = /:([^]+)/;
const styleCommentRE = /\/\*[^]*?\*\//g;
function parseStringStyle(cssText) {
  const ret = {};
  cssText.replace(styleCommentRE, "").split(listDelimiterRE).forEach((item) => {
    if (item) {
      const tmp = item.split(propertyDelimiterRE);
      tmp.length > 1 && (ret[tmp[0].trim()] = tmp[1].trim());
    }
  });
  return ret;
}
function normalizeClass(value) {
  let res = "";
  if (isString(value)) {
    res = value;
  } else if (isArray(value)) {
    for (let i = 0; i < value.length; i++) {
      const normalized = normalizeClass(value[i]);
      if (normalized) {
        res += normalized + " ";
      }
    }
  } else if (isObject(value)) {
    for (const name in value) {
      if (value[name]) {
        res += name + " ";
      }
    }
  }
  return res.trim();
}
const specialBooleanAttrs = `itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly`;
const isSpecialBooleanAttr = /* @__PURE__ */ makeMap(specialBooleanAttrs);
function includeBooleanAttr(value) {
  return !!value || value === "";
}
function looseCompareArrays(a, b) {
  if (a.length !== b.length) return false;
  let equal = true;
  for (let i = 0; equal && i < a.length; i++) {
    equal = looseEqual(a[i], b[i]);
  }
  return equal;
}
function looseEqual(a, b) {
  if (a === b) return true;
  let aValidType = isDate(a);
  let bValidType = isDate(b);
  if (aValidType || bValidType) {
    return aValidType && bValidType ? a.getTime() === b.getTime() : false;
  }
  aValidType = isSymbol(a);
  bValidType = isSymbol(b);
  if (aValidType || bValidType) {
    return a === b;
  }
  aValidType = isArray(a);
  bValidType = isArray(b);
  if (aValidType || bValidType) {
    return aValidType && bValidType ? looseCompareArrays(a, b) : false;
  }
  aValidType = isObject(a);
  bValidType = isObject(b);
  if (aValidType || bValidType) {
    if (!aValidType || !bValidType) {
      return false;
    }
    const aKeysCount = Object.keys(a).length;
    const bKeysCount = Object.keys(b).length;
    if (aKeysCount !== bKeysCount) {
      return false;
    }
    for (const key in a) {
      const aHasKey = a.hasOwnProperty(key);
      const bHasKey = b.hasOwnProperty(key);
      if (aHasKey && !bHasKey || !aHasKey && bHasKey || !looseEqual(a[key], b[key])) {
        return false;
      }
    }
  }
  return String(a) === String(b);
}
function looseIndexOf(arr, val) {
  return arr.findIndex((item) => looseEqual(item, val));
}
const isRef$1 = (val) => {
  return !!(val && val["__v_isRef"] === true);
};
const toDisplayString = (val) => {
  return isString(val) ? val : val == null ? "" : isArray(val) || isObject(val) && (val.toString === objectToString || !isFunction(val.toString)) ? isRef$1(val) ? toDisplayString(val.value) : JSON.stringify(val, replacer, 2) : String(val);
};
const replacer = (_key, val) => {
  if (isRef$1(val)) {
    return replacer(_key, val.value);
  } else if (isMap(val)) {
    return {
      [`Map(${val.size})`]: [...val.entries()].reduce(
        (entries, [key, val2], i) => {
          entries[stringifySymbol(key, i) + " =>"] = val2;
          return entries;
        },
        {}
      )
    };
  } else if (isSet(val)) {
    return {
      [`Set(${val.size})`]: [...val.values()].map((v) => stringifySymbol(v))
    };
  } else if (isSymbol(val)) {
    return stringifySymbol(val);
  } else if (isObject(val) && !isArray(val) && !isPlainObject(val)) {
    return String(val);
  }
  return val;
};
const stringifySymbol = (v, i = "") => {
  var _a2;
  return (
    // Symbol.description in es2019+ so we need to cast here to pass
    // the lib: es2016 check
    isSymbol(v) ? `Symbol(${(_a2 = v.description) != null ? _a2 : i})` : v
  );
};
/**
* @vue/reactivity v3.5.16
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
let activeEffectScope;
class EffectScope {
  constructor(detached = false) {
    this.detached = detached;
    this._active = true;
    this._on = 0;
    this.effects = [];
    this.cleanups = [];
    this._isPaused = false;
    this.parent = activeEffectScope;
    if (!detached && activeEffectScope) {
      this.index = (activeEffectScope.scopes || (activeEffectScope.scopes = [])).push(
        this
      ) - 1;
    }
  }
  get active() {
    return this._active;
  }
  pause() {
    if (this._active) {
      this._isPaused = true;
      let i, l;
      if (this.scopes) {
        for (i = 0, l = this.scopes.length; i < l; i++) {
          this.scopes[i].pause();
        }
      }
      for (i = 0, l = this.effects.length; i < l; i++) {
        this.effects[i].pause();
      }
    }
  }
  /**
   * Resumes the effect scope, including all child scopes and effects.
   */
  resume() {
    if (this._active) {
      if (this._isPaused) {
        this._isPaused = false;
        let i, l;
        if (this.scopes) {
          for (i = 0, l = this.scopes.length; i < l; i++) {
            this.scopes[i].resume();
          }
        }
        for (i = 0, l = this.effects.length; i < l; i++) {
          this.effects[i].resume();
        }
      }
    }
  }
  run(fn2) {
    if (this._active) {
      const currentEffectScope = activeEffectScope;
      try {
        activeEffectScope = this;
        return fn2();
      } finally {
        activeEffectScope = currentEffectScope;
      }
    }
  }
  /**
   * This should only be called on non-detached scopes
   * @internal
   */
  on() {
    if (++this._on === 1) {
      this.prevScope = activeEffectScope;
      activeEffectScope = this;
    }
  }
  /**
   * This should only be called on non-detached scopes
   * @internal
   */
  off() {
    if (this._on > 0 && --this._on === 0) {
      activeEffectScope = this.prevScope;
      this.prevScope = void 0;
    }
  }
  stop(fromParent) {
    if (this._active) {
      this._active = false;
      let i, l;
      for (i = 0, l = this.effects.length; i < l; i++) {
        this.effects[i].stop();
      }
      this.effects.length = 0;
      for (i = 0, l = this.cleanups.length; i < l; i++) {
        this.cleanups[i]();
      }
      this.cleanups.length = 0;
      if (this.scopes) {
        for (i = 0, l = this.scopes.length; i < l; i++) {
          this.scopes[i].stop(true);
        }
        this.scopes.length = 0;
      }
      if (!this.detached && this.parent && !fromParent) {
        const last = this.parent.scopes.pop();
        if (last && last !== this) {
          this.parent.scopes[this.index] = last;
          last.index = this.index;
        }
      }
      this.parent = void 0;
    }
  }
}
function getCurrentScope() {
  return activeEffectScope;
}
let activeSub;
const pausedQueueEffects = /* @__PURE__ */ new WeakSet();
class ReactiveEffect {
  constructor(fn2) {
    this.fn = fn2;
    this.deps = void 0;
    this.depsTail = void 0;
    this.flags = 1 | 4;
    this.next = void 0;
    this.cleanup = void 0;
    this.scheduler = void 0;
    if (activeEffectScope && activeEffectScope.active) {
      activeEffectScope.effects.push(this);
    }
  }
  pause() {
    this.flags |= 64;
  }
  resume() {
    if (this.flags & 64) {
      this.flags &= -65;
      if (pausedQueueEffects.has(this)) {
        pausedQueueEffects.delete(this);
        this.trigger();
      }
    }
  }
  /**
   * @internal
   */
  notify() {
    if (this.flags & 2 && !(this.flags & 32)) {
      return;
    }
    if (!(this.flags & 8)) {
      batch(this);
    }
  }
  run() {
    if (!(this.flags & 1)) {
      return this.fn();
    }
    this.flags |= 2;
    cleanupEffect(this);
    prepareDeps(this);
    const prevEffect = activeSub;
    const prevShouldTrack = shouldTrack;
    activeSub = this;
    shouldTrack = true;
    try {
      return this.fn();
    } finally {
      cleanupDeps(this);
      activeSub = prevEffect;
      shouldTrack = prevShouldTrack;
      this.flags &= -3;
    }
  }
  stop() {
    if (this.flags & 1) {
      for (let link = this.deps; link; link = link.nextDep) {
        removeSub(link);
      }
      this.deps = this.depsTail = void 0;
      cleanupEffect(this);
      this.onStop && this.onStop();
      this.flags &= -2;
    }
  }
  trigger() {
    if (this.flags & 64) {
      pausedQueueEffects.add(this);
    } else if (this.scheduler) {
      this.scheduler();
    } else {
      this.runIfDirty();
    }
  }
  /**
   * @internal
   */
  runIfDirty() {
    if (isDirty(this)) {
      this.run();
    }
  }
  get dirty() {
    return isDirty(this);
  }
}
let batchDepth = 0;
let batchedSub;
let batchedComputed;
function batch(sub, isComputed = false) {
  sub.flags |= 8;
  if (isComputed) {
    sub.next = batchedComputed;
    batchedComputed = sub;
    return;
  }
  sub.next = batchedSub;
  batchedSub = sub;
}
function startBatch() {
  batchDepth++;
}
function endBatch() {
  if (--batchDepth > 0) {
    return;
  }
  if (batchedComputed) {
    let e = batchedComputed;
    batchedComputed = void 0;
    while (e) {
      const next = e.next;
      e.next = void 0;
      e.flags &= -9;
      e = next;
    }
  }
  let error;
  while (batchedSub) {
    let e = batchedSub;
    batchedSub = void 0;
    while (e) {
      const next = e.next;
      e.next = void 0;
      e.flags &= -9;
      if (e.flags & 1) {
        try {
          ;
          e.trigger();
        } catch (err) {
          if (!error) error = err;
        }
      }
      e = next;
    }
  }
  if (error) throw error;
}
function prepareDeps(sub) {
  for (let link = sub.deps; link; link = link.nextDep) {
    link.version = -1;
    link.prevActiveLink = link.dep.activeLink;
    link.dep.activeLink = link;
  }
}
function cleanupDeps(sub) {
  let head;
  let tail = sub.depsTail;
  let link = tail;
  while (link) {
    const prev = link.prevDep;
    if (link.version === -1) {
      if (link === tail) tail = prev;
      removeSub(link);
      removeDep(link);
    } else {
      head = link;
    }
    link.dep.activeLink = link.prevActiveLink;
    link.prevActiveLink = void 0;
    link = prev;
  }
  sub.deps = head;
  sub.depsTail = tail;
}
function isDirty(sub) {
  for (let link = sub.deps; link; link = link.nextDep) {
    if (link.dep.version !== link.version || link.dep.computed && (refreshComputed(link.dep.computed) || link.dep.version !== link.version)) {
      return true;
    }
  }
  if (sub._dirty) {
    return true;
  }
  return false;
}
function refreshComputed(computed2) {
  if (computed2.flags & 4 && !(computed2.flags & 16)) {
    return;
  }
  computed2.flags &= -17;
  if (computed2.globalVersion === globalVersion) {
    return;
  }
  computed2.globalVersion = globalVersion;
  if (!computed2.isSSR && computed2.flags & 128 && (!computed2.deps && !computed2._dirty || !isDirty(computed2))) {
    return;
  }
  computed2.flags |= 2;
  const dep = computed2.dep;
  const prevSub = activeSub;
  const prevShouldTrack = shouldTrack;
  activeSub = computed2;
  shouldTrack = true;
  try {
    prepareDeps(computed2);
    const value = computed2.fn(computed2._value);
    if (dep.version === 0 || hasChanged(value, computed2._value)) {
      computed2.flags |= 128;
      computed2._value = value;
      dep.version++;
    }
  } catch (err) {
    dep.version++;
    throw err;
  } finally {
    activeSub = prevSub;
    shouldTrack = prevShouldTrack;
    cleanupDeps(computed2);
    computed2.flags &= -3;
  }
}
function removeSub(link, soft = false) {
  const { dep, prevSub, nextSub } = link;
  if (prevSub) {
    prevSub.nextSub = nextSub;
    link.prevSub = void 0;
  }
  if (nextSub) {
    nextSub.prevSub = prevSub;
    link.nextSub = void 0;
  }
  if (dep.subs === link) {
    dep.subs = prevSub;
    if (!prevSub && dep.computed) {
      dep.computed.flags &= -5;
      for (let l = dep.computed.deps; l; l = l.nextDep) {
        removeSub(l, true);
      }
    }
  }
  if (!soft && !--dep.sc && dep.map) {
    dep.map.delete(dep.key);
  }
}
function removeDep(link) {
  const { prevDep, nextDep } = link;
  if (prevDep) {
    prevDep.nextDep = nextDep;
    link.prevDep = void 0;
  }
  if (nextDep) {
    nextDep.prevDep = prevDep;
    link.nextDep = void 0;
  }
}
let shouldTrack = true;
const trackStack = [];
function pauseTracking() {
  trackStack.push(shouldTrack);
  shouldTrack = false;
}
function resetTracking() {
  const last = trackStack.pop();
  shouldTrack = last === void 0 ? true : last;
}
function cleanupEffect(e) {
  const { cleanup } = e;
  e.cleanup = void 0;
  if (cleanup) {
    const prevSub = activeSub;
    activeSub = void 0;
    try {
      cleanup();
    } finally {
      activeSub = prevSub;
    }
  }
}
let globalVersion = 0;
class Link {
  constructor(sub, dep) {
    this.sub = sub;
    this.dep = dep;
    this.version = dep.version;
    this.nextDep = this.prevDep = this.nextSub = this.prevSub = this.prevActiveLink = void 0;
  }
}
class Dep {
  constructor(computed2) {
    this.computed = computed2;
    this.version = 0;
    this.activeLink = void 0;
    this.subs = void 0;
    this.map = void 0;
    this.key = void 0;
    this.sc = 0;
  }
  track(debugInfo) {
    if (!activeSub || !shouldTrack || activeSub === this.computed) {
      return;
    }
    let link = this.activeLink;
    if (link === void 0 || link.sub !== activeSub) {
      link = this.activeLink = new Link(activeSub, this);
      if (!activeSub.deps) {
        activeSub.deps = activeSub.depsTail = link;
      } else {
        link.prevDep = activeSub.depsTail;
        activeSub.depsTail.nextDep = link;
        activeSub.depsTail = link;
      }
      addSub(link);
    } else if (link.version === -1) {
      link.version = this.version;
      if (link.nextDep) {
        const next = link.nextDep;
        next.prevDep = link.prevDep;
        if (link.prevDep) {
          link.prevDep.nextDep = next;
        }
        link.prevDep = activeSub.depsTail;
        link.nextDep = void 0;
        activeSub.depsTail.nextDep = link;
        activeSub.depsTail = link;
        if (activeSub.deps === link) {
          activeSub.deps = next;
        }
      }
    }
    return link;
  }
  trigger(debugInfo) {
    this.version++;
    globalVersion++;
    this.notify(debugInfo);
  }
  notify(debugInfo) {
    startBatch();
    try {
      if (false) ;
      for (let link = this.subs; link; link = link.prevSub) {
        if (link.sub.notify()) {
          ;
          link.sub.dep.notify();
        }
      }
    } finally {
      endBatch();
    }
  }
}
function addSub(link) {
  link.dep.sc++;
  if (link.sub.flags & 4) {
    const computed2 = link.dep.computed;
    if (computed2 && !link.dep.subs) {
      computed2.flags |= 4 | 16;
      for (let l = computed2.deps; l; l = l.nextDep) {
        addSub(l);
      }
    }
    const currentTail = link.dep.subs;
    if (currentTail !== link) {
      link.prevSub = currentTail;
      if (currentTail) currentTail.nextSub = link;
    }
    link.dep.subs = link;
  }
}
const targetMap = /* @__PURE__ */ new WeakMap();
const ITERATE_KEY = Symbol(
  ""
);
const MAP_KEY_ITERATE_KEY = Symbol(
  ""
);
const ARRAY_ITERATE_KEY = Symbol(
  ""
);
function track(target, type, key) {
  if (shouldTrack && activeSub) {
    let depsMap = targetMap.get(target);
    if (!depsMap) {
      targetMap.set(target, depsMap = /* @__PURE__ */ new Map());
    }
    let dep = depsMap.get(key);
    if (!dep) {
      depsMap.set(key, dep = new Dep());
      dep.map = depsMap;
      dep.key = key;
    }
    {
      dep.track();
    }
  }
}
function trigger(target, type, key, newValue, oldValue, oldTarget) {
  const depsMap = targetMap.get(target);
  if (!depsMap) {
    globalVersion++;
    return;
  }
  const run = (dep) => {
    if (dep) {
      {
        dep.trigger();
      }
    }
  };
  startBatch();
  if (type === "clear") {
    depsMap.forEach(run);
  } else {
    const targetIsArray = isArray(target);
    const isArrayIndex = targetIsArray && isIntegerKey(key);
    if (targetIsArray && key === "length") {
      const newLength = Number(newValue);
      depsMap.forEach((dep, key2) => {
        if (key2 === "length" || key2 === ARRAY_ITERATE_KEY || !isSymbol(key2) && key2 >= newLength) {
          run(dep);
        }
      });
    } else {
      if (key !== void 0 || depsMap.has(void 0)) {
        run(depsMap.get(key));
      }
      if (isArrayIndex) {
        run(depsMap.get(ARRAY_ITERATE_KEY));
      }
      switch (type) {
        case "add":
          if (!targetIsArray) {
            run(depsMap.get(ITERATE_KEY));
            if (isMap(target)) {
              run(depsMap.get(MAP_KEY_ITERATE_KEY));
            }
          } else if (isArrayIndex) {
            run(depsMap.get("length"));
          }
          break;
        case "delete":
          if (!targetIsArray) {
            run(depsMap.get(ITERATE_KEY));
            if (isMap(target)) {
              run(depsMap.get(MAP_KEY_ITERATE_KEY));
            }
          }
          break;
        case "set":
          if (isMap(target)) {
            run(depsMap.get(ITERATE_KEY));
          }
          break;
      }
    }
  }
  endBatch();
}
function reactiveReadArray(array) {
  const raw = toRaw(array);
  if (raw === array) return raw;
  track(raw, "iterate", ARRAY_ITERATE_KEY);
  return isShallow(array) ? raw : raw.map(toReactive);
}
function shallowReadArray(arr) {
  track(arr = toRaw(arr), "iterate", ARRAY_ITERATE_KEY);
  return arr;
}
const arrayInstrumentations = {
  __proto__: null,
  [Symbol.iterator]() {
    return iterator(this, Symbol.iterator, toReactive);
  },
  concat(...args) {
    return reactiveReadArray(this).concat(
      ...args.map((x) => isArray(x) ? reactiveReadArray(x) : x)
    );
  },
  entries() {
    return iterator(this, "entries", (value) => {
      value[1] = toReactive(value[1]);
      return value;
    });
  },
  every(fn2, thisArg) {
    return apply(this, "every", fn2, thisArg, void 0, arguments);
  },
  filter(fn2, thisArg) {
    return apply(this, "filter", fn2, thisArg, (v) => v.map(toReactive), arguments);
  },
  find(fn2, thisArg) {
    return apply(this, "find", fn2, thisArg, toReactive, arguments);
  },
  findIndex(fn2, thisArg) {
    return apply(this, "findIndex", fn2, thisArg, void 0, arguments);
  },
  findLast(fn2, thisArg) {
    return apply(this, "findLast", fn2, thisArg, toReactive, arguments);
  },
  findLastIndex(fn2, thisArg) {
    return apply(this, "findLastIndex", fn2, thisArg, void 0, arguments);
  },
  // flat, flatMap could benefit from ARRAY_ITERATE but are not straight-forward to implement
  forEach(fn2, thisArg) {
    return apply(this, "forEach", fn2, thisArg, void 0, arguments);
  },
  includes(...args) {
    return searchProxy(this, "includes", args);
  },
  indexOf(...args) {
    return searchProxy(this, "indexOf", args);
  },
  join(separator) {
    return reactiveReadArray(this).join(separator);
  },
  // keys() iterator only reads `length`, no optimisation required
  lastIndexOf(...args) {
    return searchProxy(this, "lastIndexOf", args);
  },
  map(fn2, thisArg) {
    return apply(this, "map", fn2, thisArg, void 0, arguments);
  },
  pop() {
    return noTracking(this, "pop");
  },
  push(...args) {
    return noTracking(this, "push", args);
  },
  reduce(fn2, ...args) {
    return reduce(this, "reduce", fn2, args);
  },
  reduceRight(fn2, ...args) {
    return reduce(this, "reduceRight", fn2, args);
  },
  shift() {
    return noTracking(this, "shift");
  },
  // slice could use ARRAY_ITERATE but also seems to beg for range tracking
  some(fn2, thisArg) {
    return apply(this, "some", fn2, thisArg, void 0, arguments);
  },
  splice(...args) {
    return noTracking(this, "splice", args);
  },
  toReversed() {
    return reactiveReadArray(this).toReversed();
  },
  toSorted(comparer) {
    return reactiveReadArray(this).toSorted(comparer);
  },
  toSpliced(...args) {
    return reactiveReadArray(this).toSpliced(...args);
  },
  unshift(...args) {
    return noTracking(this, "unshift", args);
  },
  values() {
    return iterator(this, "values", toReactive);
  }
};
function iterator(self2, method, wrapValue) {
  const arr = shallowReadArray(self2);
  const iter = arr[method]();
  if (arr !== self2 && !isShallow(self2)) {
    iter._next = iter.next;
    iter.next = () => {
      const result = iter._next();
      if (result.value) {
        result.value = wrapValue(result.value);
      }
      return result;
    };
  }
  return iter;
}
const arrayProto = Array.prototype;
function apply(self2, method, fn2, thisArg, wrappedRetFn, args) {
  const arr = shallowReadArray(self2);
  const needsWrap = arr !== self2 && !isShallow(self2);
  const methodFn = arr[method];
  if (methodFn !== arrayProto[method]) {
    const result2 = methodFn.apply(self2, args);
    return needsWrap ? toReactive(result2) : result2;
  }
  let wrappedFn = fn2;
  if (arr !== self2) {
    if (needsWrap) {
      wrappedFn = function(item, index) {
        return fn2.call(this, toReactive(item), index, self2);
      };
    } else if (fn2.length > 2) {
      wrappedFn = function(item, index) {
        return fn2.call(this, item, index, self2);
      };
    }
  }
  const result = methodFn.call(arr, wrappedFn, thisArg);
  return needsWrap && wrappedRetFn ? wrappedRetFn(result) : result;
}
function reduce(self2, method, fn2, args) {
  const arr = shallowReadArray(self2);
  let wrappedFn = fn2;
  if (arr !== self2) {
    if (!isShallow(self2)) {
      wrappedFn = function(acc, item, index) {
        return fn2.call(this, acc, toReactive(item), index, self2);
      };
    } else if (fn2.length > 3) {
      wrappedFn = function(acc, item, index) {
        return fn2.call(this, acc, item, index, self2);
      };
    }
  }
  return arr[method](wrappedFn, ...args);
}
function searchProxy(self2, method, args) {
  const arr = toRaw(self2);
  track(arr, "iterate", ARRAY_ITERATE_KEY);
  const res = arr[method](...args);
  if ((res === -1 || res === false) && isProxy(args[0])) {
    args[0] = toRaw(args[0]);
    return arr[method](...args);
  }
  return res;
}
function noTracking(self2, method, args = []) {
  pauseTracking();
  startBatch();
  const res = toRaw(self2)[method].apply(self2, args);
  endBatch();
  resetTracking();
  return res;
}
const isNonTrackableKeys = /* @__PURE__ */ makeMap(`__proto__,__v_isRef,__isVue`);
const builtInSymbols = new Set(
  /* @__PURE__ */ Object.getOwnPropertyNames(Symbol).filter((key) => key !== "arguments" && key !== "caller").map((key) => Symbol[key]).filter(isSymbol)
);
function hasOwnProperty(key) {
  if (!isSymbol(key)) key = String(key);
  const obj = toRaw(this);
  track(obj, "has", key);
  return obj.hasOwnProperty(key);
}
class BaseReactiveHandler {
  constructor(_isReadonly = false, _isShallow = false) {
    this._isReadonly = _isReadonly;
    this._isShallow = _isShallow;
  }
  get(target, key, receiver) {
    if (key === "__v_skip") return target["__v_skip"];
    const isReadonly2 = this._isReadonly, isShallow2 = this._isShallow;
    if (key === "__v_isReactive") {
      return !isReadonly2;
    } else if (key === "__v_isReadonly") {
      return isReadonly2;
    } else if (key === "__v_isShallow") {
      return isShallow2;
    } else if (key === "__v_raw") {
      if (receiver === (isReadonly2 ? isShallow2 ? shallowReadonlyMap : readonlyMap : isShallow2 ? shallowReactiveMap : reactiveMap).get(target) || // receiver is not the reactive proxy, but has the same prototype
      // this means the receiver is a user proxy of the reactive proxy
      Object.getPrototypeOf(target) === Object.getPrototypeOf(receiver)) {
        return target;
      }
      return;
    }
    const targetIsArray = isArray(target);
    if (!isReadonly2) {
      let fn2;
      if (targetIsArray && (fn2 = arrayInstrumentations[key])) {
        return fn2;
      }
      if (key === "hasOwnProperty") {
        return hasOwnProperty;
      }
    }
    const res = Reflect.get(
      target,
      key,
      // if this is a proxy wrapping a ref, return methods using the raw ref
      // as receiver so that we don't have to call `toRaw` on the ref in all
      // its class methods
      isRef(target) ? target : receiver
    );
    if (isSymbol(key) ? builtInSymbols.has(key) : isNonTrackableKeys(key)) {
      return res;
    }
    if (!isReadonly2) {
      track(target, "get", key);
    }
    if (isShallow2) {
      return res;
    }
    if (isRef(res)) {
      return targetIsArray && isIntegerKey(key) ? res : res.value;
    }
    if (isObject(res)) {
      return isReadonly2 ? readonly(res) : reactive(res);
    }
    return res;
  }
}
class MutableReactiveHandler extends BaseReactiveHandler {
  constructor(isShallow2 = false) {
    super(false, isShallow2);
  }
  set(target, key, value, receiver) {
    let oldValue = target[key];
    if (!this._isShallow) {
      const isOldValueReadonly = isReadonly(oldValue);
      if (!isShallow(value) && !isReadonly(value)) {
        oldValue = toRaw(oldValue);
        value = toRaw(value);
      }
      if (!isArray(target) && isRef(oldValue) && !isRef(value)) {
        if (isOldValueReadonly) {
          return false;
        } else {
          oldValue.value = value;
          return true;
        }
      }
    }
    const hadKey = isArray(target) && isIntegerKey(key) ? Number(key) < target.length : hasOwn(target, key);
    const result = Reflect.set(
      target,
      key,
      value,
      isRef(target) ? target : receiver
    );
    if (target === toRaw(receiver)) {
      if (!hadKey) {
        trigger(target, "add", key, value);
      } else if (hasChanged(value, oldValue)) {
        trigger(target, "set", key, value);
      }
    }
    return result;
  }
  deleteProperty(target, key) {
    const hadKey = hasOwn(target, key);
    target[key];
    const result = Reflect.deleteProperty(target, key);
    if (result && hadKey) {
      trigger(target, "delete", key, void 0);
    }
    return result;
  }
  has(target, key) {
    const result = Reflect.has(target, key);
    if (!isSymbol(key) || !builtInSymbols.has(key)) {
      track(target, "has", key);
    }
    return result;
  }
  ownKeys(target) {
    track(
      target,
      "iterate",
      isArray(target) ? "length" : ITERATE_KEY
    );
    return Reflect.ownKeys(target);
  }
}
class ReadonlyReactiveHandler extends BaseReactiveHandler {
  constructor(isShallow2 = false) {
    super(true, isShallow2);
  }
  set(target, key) {
    return true;
  }
  deleteProperty(target, key) {
    return true;
  }
}
const mutableHandlers = /* @__PURE__ */ new MutableReactiveHandler();
const readonlyHandlers = /* @__PURE__ */ new ReadonlyReactiveHandler();
const shallowReactiveHandlers = /* @__PURE__ */ new MutableReactiveHandler(true);
const shallowReadonlyHandlers = /* @__PURE__ */ new ReadonlyReactiveHandler(true);
const toShallow = (value) => value;
const getProto = (v) => Reflect.getPrototypeOf(v);
function createIterableMethod(method, isReadonly2, isShallow2) {
  return function(...args) {
    const target = this["__v_raw"];
    const rawTarget = toRaw(target);
    const targetIsMap = isMap(rawTarget);
    const isPair = method === "entries" || method === Symbol.iterator && targetIsMap;
    const isKeyOnly = method === "keys" && targetIsMap;
    const innerIterator = target[method](...args);
    const wrap = isShallow2 ? toShallow : isReadonly2 ? toReadonly : toReactive;
    !isReadonly2 && track(
      rawTarget,
      "iterate",
      isKeyOnly ? MAP_KEY_ITERATE_KEY : ITERATE_KEY
    );
    return {
      // iterator protocol
      next() {
        const { value, done } = innerIterator.next();
        return done ? { value, done } : {
          value: isPair ? [wrap(value[0]), wrap(value[1])] : wrap(value),
          done
        };
      },
      // iterable protocol
      [Symbol.iterator]() {
        return this;
      }
    };
  };
}
function createReadonlyMethod(type) {
  return function(...args) {
    return type === "delete" ? false : type === "clear" ? void 0 : this;
  };
}
function createInstrumentations(readonly2, shallow) {
  const instrumentations = {
    get(key) {
      const target = this["__v_raw"];
      const rawTarget = toRaw(target);
      const rawKey = toRaw(key);
      if (!readonly2) {
        if (hasChanged(key, rawKey)) {
          track(rawTarget, "get", key);
        }
        track(rawTarget, "get", rawKey);
      }
      const { has } = getProto(rawTarget);
      const wrap = shallow ? toShallow : readonly2 ? toReadonly : toReactive;
      if (has.call(rawTarget, key)) {
        return wrap(target.get(key));
      } else if (has.call(rawTarget, rawKey)) {
        return wrap(target.get(rawKey));
      } else if (target !== rawTarget) {
        target.get(key);
      }
    },
    get size() {
      const target = this["__v_raw"];
      !readonly2 && track(toRaw(target), "iterate", ITERATE_KEY);
      return Reflect.get(target, "size", target);
    },
    has(key) {
      const target = this["__v_raw"];
      const rawTarget = toRaw(target);
      const rawKey = toRaw(key);
      if (!readonly2) {
        if (hasChanged(key, rawKey)) {
          track(rawTarget, "has", key);
        }
        track(rawTarget, "has", rawKey);
      }
      return key === rawKey ? target.has(key) : target.has(key) || target.has(rawKey);
    },
    forEach(callback, thisArg) {
      const observed = this;
      const target = observed["__v_raw"];
      const rawTarget = toRaw(target);
      const wrap = shallow ? toShallow : readonly2 ? toReadonly : toReactive;
      !readonly2 && track(rawTarget, "iterate", ITERATE_KEY);
      return target.forEach((value, key) => {
        return callback.call(thisArg, wrap(value), wrap(key), observed);
      });
    }
  };
  extend(
    instrumentations,
    readonly2 ? {
      add: createReadonlyMethod("add"),
      set: createReadonlyMethod("set"),
      delete: createReadonlyMethod("delete"),
      clear: createReadonlyMethod("clear")
    } : {
      add(value) {
        if (!shallow && !isShallow(value) && !isReadonly(value)) {
          value = toRaw(value);
        }
        const target = toRaw(this);
        const proto = getProto(target);
        const hadKey = proto.has.call(target, value);
        if (!hadKey) {
          target.add(value);
          trigger(target, "add", value, value);
        }
        return this;
      },
      set(key, value) {
        if (!shallow && !isShallow(value) && !isReadonly(value)) {
          value = toRaw(value);
        }
        const target = toRaw(this);
        const { has, get } = getProto(target);
        let hadKey = has.call(target, key);
        if (!hadKey) {
          key = toRaw(key);
          hadKey = has.call(target, key);
        }
        const oldValue = get.call(target, key);
        target.set(key, value);
        if (!hadKey) {
          trigger(target, "add", key, value);
        } else if (hasChanged(value, oldValue)) {
          trigger(target, "set", key, value);
        }
        return this;
      },
      delete(key) {
        const target = toRaw(this);
        const { has, get } = getProto(target);
        let hadKey = has.call(target, key);
        if (!hadKey) {
          key = toRaw(key);
          hadKey = has.call(target, key);
        }
        get ? get.call(target, key) : void 0;
        const result = target.delete(key);
        if (hadKey) {
          trigger(target, "delete", key, void 0);
        }
        return result;
      },
      clear() {
        const target = toRaw(this);
        const hadItems = target.size !== 0;
        const result = target.clear();
        if (hadItems) {
          trigger(
            target,
            "clear",
            void 0,
            void 0
          );
        }
        return result;
      }
    }
  );
  const iteratorMethods = [
    "keys",
    "values",
    "entries",
    Symbol.iterator
  ];
  iteratorMethods.forEach((method) => {
    instrumentations[method] = createIterableMethod(method, readonly2, shallow);
  });
  return instrumentations;
}
function createInstrumentationGetter(isReadonly2, shallow) {
  const instrumentations = createInstrumentations(isReadonly2, shallow);
  return (target, key, receiver) => {
    if (key === "__v_isReactive") {
      return !isReadonly2;
    } else if (key === "__v_isReadonly") {
      return isReadonly2;
    } else if (key === "__v_raw") {
      return target;
    }
    return Reflect.get(
      hasOwn(instrumentations, key) && key in target ? instrumentations : target,
      key,
      receiver
    );
  };
}
const mutableCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(false, false)
};
const shallowCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(false, true)
};
const readonlyCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(true, false)
};
const shallowReadonlyCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(true, true)
};
const reactiveMap = /* @__PURE__ */ new WeakMap();
const shallowReactiveMap = /* @__PURE__ */ new WeakMap();
const readonlyMap = /* @__PURE__ */ new WeakMap();
const shallowReadonlyMap = /* @__PURE__ */ new WeakMap();
function targetTypeMap(rawType) {
  switch (rawType) {
    case "Object":
    case "Array":
      return 1;
    case "Map":
    case "Set":
    case "WeakMap":
    case "WeakSet":
      return 2;
    default:
      return 0;
  }
}
function getTargetType(value) {
  return value["__v_skip"] || !Object.isExtensible(value) ? 0 : targetTypeMap(toRawType(value));
}
function reactive(target) {
  if (isReadonly(target)) {
    return target;
  }
  return createReactiveObject(
    target,
    false,
    mutableHandlers,
    mutableCollectionHandlers,
    reactiveMap
  );
}
function shallowReactive(target) {
  return createReactiveObject(
    target,
    false,
    shallowReactiveHandlers,
    shallowCollectionHandlers,
    shallowReactiveMap
  );
}
function readonly(target) {
  return createReactiveObject(
    target,
    true,
    readonlyHandlers,
    readonlyCollectionHandlers,
    readonlyMap
  );
}
function shallowReadonly(target) {
  return createReactiveObject(
    target,
    true,
    shallowReadonlyHandlers,
    shallowReadonlyCollectionHandlers,
    shallowReadonlyMap
  );
}
function createReactiveObject(target, isReadonly2, baseHandlers, collectionHandlers, proxyMap) {
  if (!isObject(target)) {
    return target;
  }
  if (target["__v_raw"] && !(isReadonly2 && target["__v_isReactive"])) {
    return target;
  }
  const targetType = getTargetType(target);
  if (targetType === 0) {
    return target;
  }
  const existingProxy = proxyMap.get(target);
  if (existingProxy) {
    return existingProxy;
  }
  const proxy = new Proxy(
    target,
    targetType === 2 ? collectionHandlers : baseHandlers
  );
  proxyMap.set(target, proxy);
  return proxy;
}
function isReactive(value) {
  if (isReadonly(value)) {
    return isReactive(value["__v_raw"]);
  }
  return !!(value && value["__v_isReactive"]);
}
function isReadonly(value) {
  return !!(value && value["__v_isReadonly"]);
}
function isShallow(value) {
  return !!(value && value["__v_isShallow"]);
}
function isProxy(value) {
  return value ? !!value["__v_raw"] : false;
}
function toRaw(observed) {
  const raw = observed && observed["__v_raw"];
  return raw ? toRaw(raw) : observed;
}
function markRaw(value) {
  if (!hasOwn(value, "__v_skip") && Object.isExtensible(value)) {
    def(value, "__v_skip", true);
  }
  return value;
}
const toReactive = (value) => isObject(value) ? reactive(value) : value;
const toReadonly = (value) => isObject(value) ? readonly(value) : value;
function isRef(r) {
  return r ? r["__v_isRef"] === true : false;
}
function ref(value) {
  return createRef(value, false);
}
function createRef(rawValue, shallow) {
  if (isRef(rawValue)) {
    return rawValue;
  }
  return new RefImpl(rawValue, shallow);
}
class RefImpl {
  constructor(value, isShallow2) {
    this.dep = new Dep();
    this["__v_isRef"] = true;
    this["__v_isShallow"] = false;
    this._rawValue = isShallow2 ? value : toRaw(value);
    this._value = isShallow2 ? value : toReactive(value);
    this["__v_isShallow"] = isShallow2;
  }
  get value() {
    {
      this.dep.track();
    }
    return this._value;
  }
  set value(newValue) {
    const oldValue = this._rawValue;
    const useDirectValue = this["__v_isShallow"] || isShallow(newValue) || isReadonly(newValue);
    newValue = useDirectValue ? newValue : toRaw(newValue);
    if (hasChanged(newValue, oldValue)) {
      this._rawValue = newValue;
      this._value = useDirectValue ? newValue : toReactive(newValue);
      {
        this.dep.trigger();
      }
    }
  }
}
function unref(ref2) {
  return isRef(ref2) ? ref2.value : ref2;
}
const shallowUnwrapHandlers = {
  get: (target, key, receiver) => key === "__v_raw" ? target : unref(Reflect.get(target, key, receiver)),
  set: (target, key, value, receiver) => {
    const oldValue = target[key];
    if (isRef(oldValue) && !isRef(value)) {
      oldValue.value = value;
      return true;
    } else {
      return Reflect.set(target, key, value, receiver);
    }
  }
};
function proxyRefs(objectWithRefs) {
  return isReactive(objectWithRefs) ? objectWithRefs : new Proxy(objectWithRefs, shallowUnwrapHandlers);
}
class CustomRefImpl {
  constructor(factory) {
    this["__v_isRef"] = true;
    this._value = void 0;
    const dep = this.dep = new Dep();
    const { get, set } = factory(dep.track.bind(dep), dep.trigger.bind(dep));
    this._get = get;
    this._set = set;
  }
  get value() {
    return this._value = this._get();
  }
  set value(newVal) {
    this._set(newVal);
  }
}
function customRef(factory) {
  return new CustomRefImpl(factory);
}
class ComputedRefImpl {
  constructor(fn2, setter, isSSR) {
    this.fn = fn2;
    this.setter = setter;
    this._value = void 0;
    this.dep = new Dep(this);
    this.__v_isRef = true;
    this.deps = void 0;
    this.depsTail = void 0;
    this.flags = 16;
    this.globalVersion = globalVersion - 1;
    this.next = void 0;
    this.effect = this;
    this["__v_isReadonly"] = !setter;
    this.isSSR = isSSR;
  }
  /**
   * @internal
   */
  notify() {
    this.flags |= 16;
    if (!(this.flags & 8) && // avoid infinite self recursion
    activeSub !== this) {
      batch(this, true);
      return true;
    }
  }
  get value() {
    const link = this.dep.track();
    refreshComputed(this);
    if (link) {
      link.version = this.dep.version;
    }
    return this._value;
  }
  set value(newValue) {
    if (this.setter) {
      this.setter(newValue);
    }
  }
}
function computed$1(getterOrOptions, debugOptions, isSSR = false) {
  let getter;
  let setter;
  if (isFunction(getterOrOptions)) {
    getter = getterOrOptions;
  } else {
    getter = getterOrOptions.get;
    setter = getterOrOptions.set;
  }
  const cRef = new ComputedRefImpl(getter, setter, isSSR);
  return cRef;
}
const INITIAL_WATCHER_VALUE = {};
const cleanupMap = /* @__PURE__ */ new WeakMap();
let activeWatcher = void 0;
function onWatcherCleanup(cleanupFn, failSilently = false, owner = activeWatcher) {
  if (owner) {
    let cleanups = cleanupMap.get(owner);
    if (!cleanups) cleanupMap.set(owner, cleanups = []);
    cleanups.push(cleanupFn);
  }
}
function watch$1(source, cb2, options = EMPTY_OBJ) {
  const { immediate, deep, once, scheduler, augmentJob, call } = options;
  const reactiveGetter = (source2) => {
    if (deep) return source2;
    if (isShallow(source2) || deep === false || deep === 0)
      return traverse(source2, 1);
    return traverse(source2);
  };
  let effect2;
  let getter;
  let cleanup;
  let boundCleanup;
  let forceTrigger = false;
  let isMultiSource = false;
  if (isRef(source)) {
    getter = () => source.value;
    forceTrigger = isShallow(source);
  } else if (isReactive(source)) {
    getter = () => reactiveGetter(source);
    forceTrigger = true;
  } else if (isArray(source)) {
    isMultiSource = true;
    forceTrigger = source.some((s) => isReactive(s) || isShallow(s));
    getter = () => source.map((s) => {
      if (isRef(s)) {
        return s.value;
      } else if (isReactive(s)) {
        return reactiveGetter(s);
      } else if (isFunction(s)) {
        return call ? call(s, 2) : s();
      } else ;
    });
  } else if (isFunction(source)) {
    if (cb2) {
      getter = call ? () => call(source, 2) : source;
    } else {
      getter = () => {
        if (cleanup) {
          pauseTracking();
          try {
            cleanup();
          } finally {
            resetTracking();
          }
        }
        const currentEffect = activeWatcher;
        activeWatcher = effect2;
        try {
          return call ? call(source, 3, [boundCleanup]) : source(boundCleanup);
        } finally {
          activeWatcher = currentEffect;
        }
      };
    }
  } else {
    getter = NOOP;
  }
  if (cb2 && deep) {
    const baseGetter = getter;
    const depth = deep === true ? Infinity : deep;
    getter = () => traverse(baseGetter(), depth);
  }
  const scope = getCurrentScope();
  const watchHandle = () => {
    effect2.stop();
    if (scope && scope.active) {
      remove(scope.effects, effect2);
    }
  };
  if (once && cb2) {
    const _cb = cb2;
    cb2 = (...args) => {
      _cb(...args);
      watchHandle();
    };
  }
  let oldValue = isMultiSource ? new Array(source.length).fill(INITIAL_WATCHER_VALUE) : INITIAL_WATCHER_VALUE;
  const job = (immediateFirstRun) => {
    if (!(effect2.flags & 1) || !effect2.dirty && !immediateFirstRun) {
      return;
    }
    if (cb2) {
      const newValue = effect2.run();
      if (deep || forceTrigger || (isMultiSource ? newValue.some((v, i) => hasChanged(v, oldValue[i])) : hasChanged(newValue, oldValue))) {
        if (cleanup) {
          cleanup();
        }
        const currentWatcher = activeWatcher;
        activeWatcher = effect2;
        try {
          const args = [
            newValue,
            // pass undefined as the old value when it's changed for the first time
            oldValue === INITIAL_WATCHER_VALUE ? void 0 : isMultiSource && oldValue[0] === INITIAL_WATCHER_VALUE ? [] : oldValue,
            boundCleanup
          ];
          oldValue = newValue;
          call ? call(cb2, 3, args) : (
            // @ts-expect-error
            cb2(...args)
          );
        } finally {
          activeWatcher = currentWatcher;
        }
      }
    } else {
      effect2.run();
    }
  };
  if (augmentJob) {
    augmentJob(job);
  }
  effect2 = new ReactiveEffect(getter);
  effect2.scheduler = scheduler ? () => scheduler(job, false) : job;
  boundCleanup = (fn2) => onWatcherCleanup(fn2, false, effect2);
  cleanup = effect2.onStop = () => {
    const cleanups = cleanupMap.get(effect2);
    if (cleanups) {
      if (call) {
        call(cleanups, 4);
      } else {
        for (const cleanup2 of cleanups) cleanup2();
      }
      cleanupMap.delete(effect2);
    }
  };
  if (cb2) {
    if (immediate) {
      job(true);
    } else {
      oldValue = effect2.run();
    }
  } else if (scheduler) {
    scheduler(job.bind(null, true), true);
  } else {
    effect2.run();
  }
  watchHandle.pause = effect2.pause.bind(effect2);
  watchHandle.resume = effect2.resume.bind(effect2);
  watchHandle.stop = watchHandle;
  return watchHandle;
}
function traverse(value, depth = Infinity, seen) {
  if (depth <= 0 || !isObject(value) || value["__v_skip"]) {
    return value;
  }
  seen = seen || /* @__PURE__ */ new Set();
  if (seen.has(value)) {
    return value;
  }
  seen.add(value);
  depth--;
  if (isRef(value)) {
    traverse(value.value, depth, seen);
  } else if (isArray(value)) {
    for (let i = 0; i < value.length; i++) {
      traverse(value[i], depth, seen);
    }
  } else if (isSet(value) || isMap(value)) {
    value.forEach((v) => {
      traverse(v, depth, seen);
    });
  } else if (isPlainObject(value)) {
    for (const key in value) {
      traverse(value[key], depth, seen);
    }
    for (const key of Object.getOwnPropertySymbols(value)) {
      if (Object.prototype.propertyIsEnumerable.call(value, key)) {
        traverse(value[key], depth, seen);
      }
    }
  }
  return value;
}
/**
* @vue/runtime-core v3.5.16
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
const stack = [];
let isWarning = false;
function warn$1(msg, ...args) {
  if (isWarning) return;
  isWarning = true;
  pauseTracking();
  const instance = stack.length ? stack[stack.length - 1].component : null;
  const appWarnHandler = instance && instance.appContext.config.warnHandler;
  const trace = getComponentTrace();
  if (appWarnHandler) {
    callWithErrorHandling(
      appWarnHandler,
      instance,
      11,
      [
        // eslint-disable-next-line no-restricted-syntax
        msg + args.map((a) => {
          var _a2, _b2;
          return (_b2 = (_a2 = a.toString) == null ? void 0 : _a2.call(a)) != null ? _b2 : JSON.stringify(a);
        }).join(""),
        instance && instance.proxy,
        trace.map(
          ({ vnode }) => `at <${formatComponentName(instance, vnode.type)}>`
        ).join("\n"),
        trace
      ]
    );
  } else {
    const warnArgs = [`[Vue warn]: ${msg}`, ...args];
    if (trace.length && // avoid spamming console during tests
    true) {
      warnArgs.push(`
`, ...formatTrace(trace));
    }
    console.warn(...warnArgs);
  }
  resetTracking();
  isWarning = false;
}
function getComponentTrace() {
  let currentVNode = stack[stack.length - 1];
  if (!currentVNode) {
    return [];
  }
  const normalizedStack = [];
  while (currentVNode) {
    const last = normalizedStack[0];
    if (last && last.vnode === currentVNode) {
      last.recurseCount++;
    } else {
      normalizedStack.push({
        vnode: currentVNode,
        recurseCount: 0
      });
    }
    const parentInstance = currentVNode.component && currentVNode.component.parent;
    currentVNode = parentInstance && parentInstance.vnode;
  }
  return normalizedStack;
}
function formatTrace(trace) {
  const logs = [];
  trace.forEach((entry, i) => {
    logs.push(...i === 0 ? [] : [`
`], ...formatTraceEntry(entry));
  });
  return logs;
}
function formatTraceEntry({ vnode, recurseCount }) {
  const postfix = recurseCount > 0 ? `... (${recurseCount} recursive calls)` : ``;
  const isRoot = vnode.component ? vnode.component.parent == null : false;
  const open = ` at <${formatComponentName(
    vnode.component,
    vnode.type,
    isRoot
  )}`;
  const close = `>` + postfix;
  return vnode.props ? [open, ...formatProps(vnode.props), close] : [open + close];
}
function formatProps(props) {
  const res = [];
  const keys = Object.keys(props);
  keys.slice(0, 3).forEach((key) => {
    res.push(...formatProp(key, props[key]));
  });
  if (keys.length > 3) {
    res.push(` ...`);
  }
  return res;
}
function formatProp(key, value, raw) {
  if (isString(value)) {
    value = JSON.stringify(value);
    return raw ? value : [`${key}=${value}`];
  } else if (typeof value === "number" || typeof value === "boolean" || value == null) {
    return raw ? value : [`${key}=${value}`];
  } else if (isRef(value)) {
    value = formatProp(key, toRaw(value.value), true);
    return raw ? value : [`${key}=Ref<`, value, `>`];
  } else if (isFunction(value)) {
    return [`${key}=fn${value.name ? `<${value.name}>` : ``}`];
  } else {
    value = toRaw(value);
    return raw ? value : [`${key}=`, value];
  }
}
function callWithErrorHandling(fn2, instance, type, args) {
  try {
    return args ? fn2(...args) : fn2();
  } catch (err) {
    handleError(err, instance, type);
  }
}
function callWithAsyncErrorHandling(fn2, instance, type, args) {
  if (isFunction(fn2)) {
    const res = callWithErrorHandling(fn2, instance, type, args);
    if (res && isPromise(res)) {
      res.catch((err) => {
        handleError(err, instance, type);
      });
    }
    return res;
  }
  if (isArray(fn2)) {
    const values = [];
    for (let i = 0; i < fn2.length; i++) {
      values.push(callWithAsyncErrorHandling(fn2[i], instance, type, args));
    }
    return values;
  }
}
function handleError(err, instance, type, throwInDev = true) {
  const contextVNode = instance ? instance.vnode : null;
  const { errorHandler, throwUnhandledErrorInProduction } = instance && instance.appContext.config || EMPTY_OBJ;
  if (instance) {
    let cur = instance.parent;
    const exposedInstance = instance.proxy;
    const errorInfo = `https://vuejs.org/error-reference/#runtime-${type}`;
    while (cur) {
      const errorCapturedHooks = cur.ec;
      if (errorCapturedHooks) {
        for (let i = 0; i < errorCapturedHooks.length; i++) {
          if (errorCapturedHooks[i](err, exposedInstance, errorInfo) === false) {
            return;
          }
        }
      }
      cur = cur.parent;
    }
    if (errorHandler) {
      pauseTracking();
      callWithErrorHandling(errorHandler, null, 10, [
        err,
        exposedInstance,
        errorInfo
      ]);
      resetTracking();
      return;
    }
  }
  logError(err, type, contextVNode, throwInDev, throwUnhandledErrorInProduction);
}
function logError(err, type, contextVNode, throwInDev = true, throwInProd = false) {
  if (throwInProd) {
    throw err;
  } else {
    console.error(err);
  }
}
const queue = [];
let flushIndex = -1;
const pendingPostFlushCbs = [];
let activePostFlushCbs = null;
let postFlushIndex = 0;
const resolvedPromise = /* @__PURE__ */ Promise.resolve();
let currentFlushPromise = null;
function nextTick(fn2) {
  const p2 = currentFlushPromise || resolvedPromise;
  return fn2 ? p2.then(this ? fn2.bind(this) : fn2) : p2;
}
function findInsertionIndex(id2) {
  let start = flushIndex + 1;
  let end = queue.length;
  while (start < end) {
    const middle = start + end >>> 1;
    const middleJob = queue[middle];
    const middleJobId = getId(middleJob);
    if (middleJobId < id2 || middleJobId === id2 && middleJob.flags & 2) {
      start = middle + 1;
    } else {
      end = middle;
    }
  }
  return start;
}
function queueJob(job) {
  if (!(job.flags & 1)) {
    const jobId = getId(job);
    const lastJob = queue[queue.length - 1];
    if (!lastJob || // fast path when the job id is larger than the tail
    !(job.flags & 2) && jobId >= getId(lastJob)) {
      queue.push(job);
    } else {
      queue.splice(findInsertionIndex(jobId), 0, job);
    }
    job.flags |= 1;
    queueFlush();
  }
}
function queueFlush() {
  if (!currentFlushPromise) {
    currentFlushPromise = resolvedPromise.then(flushJobs);
  }
}
function queuePostFlushCb(cb2) {
  if (!isArray(cb2)) {
    if (activePostFlushCbs && cb2.id === -1) {
      activePostFlushCbs.splice(postFlushIndex + 1, 0, cb2);
    } else if (!(cb2.flags & 1)) {
      pendingPostFlushCbs.push(cb2);
      cb2.flags |= 1;
    }
  } else {
    pendingPostFlushCbs.push(...cb2);
  }
  queueFlush();
}
function flushPreFlushCbs(instance, seen, i = flushIndex + 1) {
  for (; i < queue.length; i++) {
    const cb2 = queue[i];
    if (cb2 && cb2.flags & 2) {
      if (instance && cb2.id !== instance.uid) {
        continue;
      }
      queue.splice(i, 1);
      i--;
      if (cb2.flags & 4) {
        cb2.flags &= -2;
      }
      cb2();
      if (!(cb2.flags & 4)) {
        cb2.flags &= -2;
      }
    }
  }
}
function flushPostFlushCbs(seen) {
  if (pendingPostFlushCbs.length) {
    const deduped = [...new Set(pendingPostFlushCbs)].sort(
      (a, b) => getId(a) - getId(b)
    );
    pendingPostFlushCbs.length = 0;
    if (activePostFlushCbs) {
      activePostFlushCbs.push(...deduped);
      return;
    }
    activePostFlushCbs = deduped;
    for (postFlushIndex = 0; postFlushIndex < activePostFlushCbs.length; postFlushIndex++) {
      const cb2 = activePostFlushCbs[postFlushIndex];
      if (cb2.flags & 4) {
        cb2.flags &= -2;
      }
      if (!(cb2.flags & 8)) cb2();
      cb2.flags &= -2;
    }
    activePostFlushCbs = null;
    postFlushIndex = 0;
  }
}
const getId = (job) => job.id == null ? job.flags & 2 ? -1 : Infinity : job.id;
function flushJobs(seen) {
  try {
    for (flushIndex = 0; flushIndex < queue.length; flushIndex++) {
      const job = queue[flushIndex];
      if (job && !(job.flags & 8)) {
        if (false) ;
        if (job.flags & 4) {
          job.flags &= ~1;
        }
        callWithErrorHandling(
          job,
          job.i,
          job.i ? 15 : 14
        );
        if (!(job.flags & 4)) {
          job.flags &= ~1;
        }
      }
    }
  } finally {
    for (; flushIndex < queue.length; flushIndex++) {
      const job = queue[flushIndex];
      if (job) {
        job.flags &= -2;
      }
    }
    flushIndex = -1;
    queue.length = 0;
    flushPostFlushCbs();
    currentFlushPromise = null;
    if (queue.length || pendingPostFlushCbs.length) {
      flushJobs();
    }
  }
}
let currentRenderingInstance = null;
let currentScopeId = null;
function setCurrentRenderingInstance(instance) {
  const prev = currentRenderingInstance;
  currentRenderingInstance = instance;
  currentScopeId = instance && instance.type.__scopeId || null;
  return prev;
}
function withCtx(fn2, ctx = currentRenderingInstance, isNonScopedSlot) {
  if (!ctx) return fn2;
  if (fn2._n) {
    return fn2;
  }
  const renderFnWithContext = (...args) => {
    if (renderFnWithContext._d) {
      setBlockTracking(-1);
    }
    const prevInstance = setCurrentRenderingInstance(ctx);
    let res;
    try {
      res = fn2(...args);
    } finally {
      setCurrentRenderingInstance(prevInstance);
      if (renderFnWithContext._d) {
        setBlockTracking(1);
      }
    }
    return res;
  };
  renderFnWithContext._n = true;
  renderFnWithContext._c = true;
  renderFnWithContext._d = true;
  return renderFnWithContext;
}
function withDirectives(vnode, directives) {
  if (currentRenderingInstance === null) {
    return vnode;
  }
  const instance = getComponentPublicInstance(currentRenderingInstance);
  const bindings = vnode.dirs || (vnode.dirs = []);
  for (let i = 0; i < directives.length; i++) {
    let [dir, value, arg, modifiers = EMPTY_OBJ] = directives[i];
    if (dir) {
      if (isFunction(dir)) {
        dir = {
          mounted: dir,
          updated: dir
        };
      }
      if (dir.deep) {
        traverse(value);
      }
      bindings.push({
        dir,
        instance,
        value,
        oldValue: void 0,
        arg,
        modifiers
      });
    }
  }
  return vnode;
}
function invokeDirectiveHook(vnode, prevVNode, instance, name) {
  const bindings = vnode.dirs;
  const oldBindings = prevVNode && prevVNode.dirs;
  for (let i = 0; i < bindings.length; i++) {
    const binding = bindings[i];
    if (oldBindings) {
      binding.oldValue = oldBindings[i].value;
    }
    let hook = binding.dir[name];
    if (hook) {
      pauseTracking();
      callWithAsyncErrorHandling(hook, instance, 8, [
        vnode.el,
        binding,
        vnode,
        prevVNode
      ]);
      resetTracking();
    }
  }
}
const TeleportEndKey = Symbol("_vte");
const isTeleport = (type) => type.__isTeleport;
const leaveCbKey = Symbol("_leaveCb");
const enterCbKey = Symbol("_enterCb");
function useTransitionState() {
  const state = {
    isMounted: false,
    isLeaving: false,
    isUnmounting: false,
    leavingVNodes: /* @__PURE__ */ new Map()
  };
  onMounted(() => {
    state.isMounted = true;
  });
  onBeforeUnmount(() => {
    state.isUnmounting = true;
  });
  return state;
}
const TransitionHookValidator = [Function, Array];
const BaseTransitionPropsValidators = {
  mode: String,
  appear: Boolean,
  persisted: Boolean,
  // enter
  onBeforeEnter: TransitionHookValidator,
  onEnter: TransitionHookValidator,
  onAfterEnter: TransitionHookValidator,
  onEnterCancelled: TransitionHookValidator,
  // leave
  onBeforeLeave: TransitionHookValidator,
  onLeave: TransitionHookValidator,
  onAfterLeave: TransitionHookValidator,
  onLeaveCancelled: TransitionHookValidator,
  // appear
  onBeforeAppear: TransitionHookValidator,
  onAppear: TransitionHookValidator,
  onAfterAppear: TransitionHookValidator,
  onAppearCancelled: TransitionHookValidator
};
const recursiveGetSubtree = (instance) => {
  const subTree = instance.subTree;
  return subTree.component ? recursiveGetSubtree(subTree.component) : subTree;
};
const BaseTransitionImpl = {
  name: `BaseTransition`,
  props: BaseTransitionPropsValidators,
  setup(props, { slots }) {
    const instance = getCurrentInstance();
    const state = useTransitionState();
    return () => {
      const children = slots.default && getTransitionRawChildren(slots.default(), true);
      if (!children || !children.length) {
        return;
      }
      const child = findNonCommentChild(children);
      const rawProps = toRaw(props);
      const { mode } = rawProps;
      if (state.isLeaving) {
        return emptyPlaceholder(child);
      }
      const innerChild = getInnerChild$1(child);
      if (!innerChild) {
        return emptyPlaceholder(child);
      }
      let enterHooks = resolveTransitionHooks(
        innerChild,
        rawProps,
        state,
        instance,
        // #11061, ensure enterHooks is fresh after clone
        (hooks) => enterHooks = hooks
      );
      if (innerChild.type !== Comment) {
        setTransitionHooks(innerChild, enterHooks);
      }
      let oldInnerChild = instance.subTree && getInnerChild$1(instance.subTree);
      if (oldInnerChild && oldInnerChild.type !== Comment && !isSameVNodeType(innerChild, oldInnerChild) && recursiveGetSubtree(instance).type !== Comment) {
        let leavingHooks = resolveTransitionHooks(
          oldInnerChild,
          rawProps,
          state,
          instance
        );
        setTransitionHooks(oldInnerChild, leavingHooks);
        if (mode === "out-in" && innerChild.type !== Comment) {
          state.isLeaving = true;
          leavingHooks.afterLeave = () => {
            state.isLeaving = false;
            if (!(instance.job.flags & 8)) {
              instance.update();
            }
            delete leavingHooks.afterLeave;
            oldInnerChild = void 0;
          };
          return emptyPlaceholder(child);
        } else if (mode === "in-out" && innerChild.type !== Comment) {
          leavingHooks.delayLeave = (el2, earlyRemove, delayedLeave) => {
            const leavingVNodesCache = getLeavingNodesForType(
              state,
              oldInnerChild
            );
            leavingVNodesCache[String(oldInnerChild.key)] = oldInnerChild;
            el2[leaveCbKey] = () => {
              earlyRemove();
              el2[leaveCbKey] = void 0;
              delete enterHooks.delayedLeave;
              oldInnerChild = void 0;
            };
            enterHooks.delayedLeave = () => {
              delayedLeave();
              delete enterHooks.delayedLeave;
              oldInnerChild = void 0;
            };
          };
        } else {
          oldInnerChild = void 0;
        }
      } else if (oldInnerChild) {
        oldInnerChild = void 0;
      }
      return child;
    };
  }
};
function findNonCommentChild(children) {
  let child = children[0];
  if (children.length > 1) {
    for (const c of children) {
      if (c.type !== Comment) {
        child = c;
        break;
      }
    }
  }
  return child;
}
const BaseTransition = BaseTransitionImpl;
function getLeavingNodesForType(state, vnode) {
  const { leavingVNodes } = state;
  let leavingVNodesCache = leavingVNodes.get(vnode.type);
  if (!leavingVNodesCache) {
    leavingVNodesCache = /* @__PURE__ */ Object.create(null);
    leavingVNodes.set(vnode.type, leavingVNodesCache);
  }
  return leavingVNodesCache;
}
function resolveTransitionHooks(vnode, props, state, instance, postClone) {
  const {
    appear,
    mode,
    persisted = false,
    onBeforeEnter,
    onEnter,
    onAfterEnter,
    onEnterCancelled,
    onBeforeLeave,
    onLeave,
    onAfterLeave,
    onLeaveCancelled,
    onBeforeAppear,
    onAppear,
    onAfterAppear,
    onAppearCancelled
  } = props;
  const key = String(vnode.key);
  const leavingVNodesCache = getLeavingNodesForType(state, vnode);
  const callHook2 = (hook, args) => {
    hook && callWithAsyncErrorHandling(
      hook,
      instance,
      9,
      args
    );
  };
  const callAsyncHook = (hook, args) => {
    const done = args[1];
    callHook2(hook, args);
    if (isArray(hook)) {
      if (hook.every((hook2) => hook2.length <= 1)) done();
    } else if (hook.length <= 1) {
      done();
    }
  };
  const hooks = {
    mode,
    persisted,
    beforeEnter(el2) {
      let hook = onBeforeEnter;
      if (!state.isMounted) {
        if (appear) {
          hook = onBeforeAppear || onBeforeEnter;
        } else {
          return;
        }
      }
      if (el2[leaveCbKey]) {
        el2[leaveCbKey](
          true
          /* cancelled */
        );
      }
      const leavingVNode = leavingVNodesCache[key];
      if (leavingVNode && isSameVNodeType(vnode, leavingVNode) && leavingVNode.el[leaveCbKey]) {
        leavingVNode.el[leaveCbKey]();
      }
      callHook2(hook, [el2]);
    },
    enter(el2) {
      let hook = onEnter;
      let afterHook = onAfterEnter;
      let cancelHook = onEnterCancelled;
      if (!state.isMounted) {
        if (appear) {
          hook = onAppear || onEnter;
          afterHook = onAfterAppear || onAfterEnter;
          cancelHook = onAppearCancelled || onEnterCancelled;
        } else {
          return;
        }
      }
      let called = false;
      const done = el2[enterCbKey] = (cancelled) => {
        if (called) return;
        called = true;
        if (cancelled) {
          callHook2(cancelHook, [el2]);
        } else {
          callHook2(afterHook, [el2]);
        }
        if (hooks.delayedLeave) {
          hooks.delayedLeave();
        }
        el2[enterCbKey] = void 0;
      };
      if (hook) {
        callAsyncHook(hook, [el2, done]);
      } else {
        done();
      }
    },
    leave(el2, remove2) {
      const key2 = String(vnode.key);
      if (el2[enterCbKey]) {
        el2[enterCbKey](
          true
          /* cancelled */
        );
      }
      if (state.isUnmounting) {
        return remove2();
      }
      callHook2(onBeforeLeave, [el2]);
      let called = false;
      const done = el2[leaveCbKey] = (cancelled) => {
        if (called) return;
        called = true;
        remove2();
        if (cancelled) {
          callHook2(onLeaveCancelled, [el2]);
        } else {
          callHook2(onAfterLeave, [el2]);
        }
        el2[leaveCbKey] = void 0;
        if (leavingVNodesCache[key2] === vnode) {
          delete leavingVNodesCache[key2];
        }
      };
      leavingVNodesCache[key2] = vnode;
      if (onLeave) {
        callAsyncHook(onLeave, [el2, done]);
      } else {
        done();
      }
    },
    clone(vnode2) {
      const hooks2 = resolveTransitionHooks(
        vnode2,
        props,
        state,
        instance,
        postClone
      );
      if (postClone) postClone(hooks2);
      return hooks2;
    }
  };
  return hooks;
}
function emptyPlaceholder(vnode) {
  if (isKeepAlive(vnode)) {
    vnode = cloneVNode(vnode);
    vnode.children = null;
    return vnode;
  }
}
function getInnerChild$1(vnode) {
  if (!isKeepAlive(vnode)) {
    if (isTeleport(vnode.type) && vnode.children) {
      return findNonCommentChild(vnode.children);
    }
    return vnode;
  }
  if (vnode.component) {
    return vnode.component.subTree;
  }
  const { shapeFlag, children } = vnode;
  if (children) {
    if (shapeFlag & 16) {
      return children[0];
    }
    if (shapeFlag & 32 && isFunction(children.default)) {
      return children.default();
    }
  }
}
function setTransitionHooks(vnode, hooks) {
  if (vnode.shapeFlag & 6 && vnode.component) {
    vnode.transition = hooks;
    setTransitionHooks(vnode.component.subTree, hooks);
  } else if (vnode.shapeFlag & 128) {
    vnode.ssContent.transition = hooks.clone(vnode.ssContent);
    vnode.ssFallback.transition = hooks.clone(vnode.ssFallback);
  } else {
    vnode.transition = hooks;
  }
}
function getTransitionRawChildren(children, keepComment = false, parentKey) {
  let ret = [];
  let keyedFragmentCount = 0;
  for (let i = 0; i < children.length; i++) {
    let child = children[i];
    const key = parentKey == null ? child.key : String(parentKey) + String(child.key != null ? child.key : i);
    if (child.type === Fragment) {
      if (child.patchFlag & 128) keyedFragmentCount++;
      ret = ret.concat(
        getTransitionRawChildren(child.children, keepComment, key)
      );
    } else if (keepComment || child.type !== Comment) {
      ret.push(key != null ? cloneVNode(child, { key }) : child);
    }
  }
  if (keyedFragmentCount > 1) {
    for (let i = 0; i < ret.length; i++) {
      ret[i].patchFlag = -2;
    }
  }
  return ret;
}
/*! #__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function defineComponent(options, extraOptions) {
  return isFunction(options) ? (
    // #8236: extend call and options.name access are considered side-effects
    // by Rollup, so we have to wrap it in a pure-annotated IIFE.
    /* @__PURE__ */ (() => extend({ name: options.name }, extraOptions, { setup: options }))()
  ) : options;
}
function markAsyncBoundary(instance) {
  instance.ids = [instance.ids[0] + instance.ids[2]++ + "-", 0, 0];
}
function setRef(rawRef, oldRawRef, parentSuspense, vnode, isUnmount = false) {
  if (isArray(rawRef)) {
    rawRef.forEach(
      (r, i) => setRef(
        r,
        oldRawRef && (isArray(oldRawRef) ? oldRawRef[i] : oldRawRef),
        parentSuspense,
        vnode,
        isUnmount
      )
    );
    return;
  }
  if (isAsyncWrapper(vnode) && !isUnmount) {
    if (vnode.shapeFlag & 512 && vnode.type.__asyncResolved && vnode.component.subTree.component) {
      setRef(rawRef, oldRawRef, parentSuspense, vnode.component.subTree);
    }
    return;
  }
  const refValue = vnode.shapeFlag & 4 ? getComponentPublicInstance(vnode.component) : vnode.el;
  const value = isUnmount ? null : refValue;
  const { i: owner, r: ref3 } = rawRef;
  const oldRef = oldRawRef && oldRawRef.r;
  const refs = owner.refs === EMPTY_OBJ ? owner.refs = {} : owner.refs;
  const setupState = owner.setupState;
  const rawSetupState = toRaw(setupState);
  const canSetSetupRef = setupState === EMPTY_OBJ ? () => false : (key) => {
    return hasOwn(rawSetupState, key);
  };
  if (oldRef != null && oldRef !== ref3) {
    if (isString(oldRef)) {
      refs[oldRef] = null;
      if (canSetSetupRef(oldRef)) {
        setupState[oldRef] = null;
      }
    } else if (isRef(oldRef)) {
      oldRef.value = null;
    }
  }
  if (isFunction(ref3)) {
    callWithErrorHandling(ref3, owner, 12, [value, refs]);
  } else {
    const _isString = isString(ref3);
    const _isRef = isRef(ref3);
    if (_isString || _isRef) {
      const doSet = () => {
        if (rawRef.f) {
          const existing = _isString ? canSetSetupRef(ref3) ? setupState[ref3] : refs[ref3] : ref3.value;
          if (isUnmount) {
            isArray(existing) && remove(existing, refValue);
          } else {
            if (!isArray(existing)) {
              if (_isString) {
                refs[ref3] = [refValue];
                if (canSetSetupRef(ref3)) {
                  setupState[ref3] = refs[ref3];
                }
              } else {
                ref3.value = [refValue];
                if (rawRef.k) refs[rawRef.k] = ref3.value;
              }
            } else if (!existing.includes(refValue)) {
              existing.push(refValue);
            }
          }
        } else if (_isString) {
          refs[ref3] = value;
          if (canSetSetupRef(ref3)) {
            setupState[ref3] = value;
          }
        } else if (_isRef) {
          ref3.value = value;
          if (rawRef.k) refs[rawRef.k] = value;
        } else ;
      };
      if (value) {
        doSet.id = -1;
        queuePostRenderEffect(doSet, parentSuspense);
      } else {
        doSet();
      }
    }
  }
}
getGlobalThis().requestIdleCallback || ((cb2) => setTimeout(cb2, 1));
getGlobalThis().cancelIdleCallback || ((id2) => clearTimeout(id2));
const isAsyncWrapper = (i) => !!i.type.__asyncLoader;
const isKeepAlive = (vnode) => vnode.type.__isKeepAlive;
function onActivated(hook, target) {
  registerKeepAliveHook(hook, "a", target);
}
function onDeactivated(hook, target) {
  registerKeepAliveHook(hook, "da", target);
}
function registerKeepAliveHook(hook, type, target = currentInstance) {
  const wrappedHook = hook.__wdc || (hook.__wdc = () => {
    let current = target;
    while (current) {
      if (current.isDeactivated) {
        return;
      }
      current = current.parent;
    }
    return hook();
  });
  injectHook(type, wrappedHook, target);
  if (target) {
    let current = target.parent;
    while (current && current.parent) {
      if (isKeepAlive(current.parent.vnode)) {
        injectToKeepAliveRoot(wrappedHook, type, target, current);
      }
      current = current.parent;
    }
  }
}
function injectToKeepAliveRoot(hook, type, target, keepAliveRoot) {
  const injected = injectHook(
    type,
    hook,
    keepAliveRoot,
    true
    /* prepend */
  );
  onUnmounted(() => {
    remove(keepAliveRoot[type], injected);
  }, target);
}
function injectHook(type, hook, target = currentInstance, prepend = false) {
  if (target) {
    const hooks = target[type] || (target[type] = []);
    const wrappedHook = hook.__weh || (hook.__weh = (...args) => {
      pauseTracking();
      const reset = setCurrentInstance(target);
      const res = callWithAsyncErrorHandling(hook, target, type, args);
      reset();
      resetTracking();
      return res;
    });
    if (prepend) {
      hooks.unshift(wrappedHook);
    } else {
      hooks.push(wrappedHook);
    }
    return wrappedHook;
  }
}
const createHook = (lifecycle) => (hook, target = currentInstance) => {
  if (!isInSSRComponentSetup || lifecycle === "sp") {
    injectHook(lifecycle, (...args) => hook(...args), target);
  }
};
const onBeforeMount = createHook("bm");
const onMounted = createHook("m");
const onBeforeUpdate = createHook(
  "bu"
);
const onUpdated = createHook("u");
const onBeforeUnmount = createHook(
  "bum"
);
const onUnmounted = createHook("um");
const onServerPrefetch = createHook(
  "sp"
);
const onRenderTriggered = createHook("rtg");
const onRenderTracked = createHook("rtc");
function onErrorCaptured(hook, target = currentInstance) {
  injectHook("ec", hook, target);
}
const COMPONENTS = "components";
function resolveComponent(name, maybeSelfReference) {
  return resolveAsset(COMPONENTS, name, true, maybeSelfReference) || name;
}
const NULL_DYNAMIC_COMPONENT = Symbol.for("v-ndc");
function resolveAsset(type, name, warnMissing = true, maybeSelfReference = false) {
  const instance = currentRenderingInstance || currentInstance;
  if (instance) {
    const Component = instance.type;
    {
      const selfName = getComponentName(
        Component,
        false
      );
      if (selfName && (selfName === name || selfName === camelize(name) || selfName === capitalize(camelize(name)))) {
        return Component;
      }
    }
    const res = (
      // local registration
      // check instance[type] first which is resolved for options API
      resolve(instance[type] || Component[type], name) || // global registration
      resolve(instance.appContext[type], name)
    );
    if (!res && maybeSelfReference) {
      return Component;
    }
    return res;
  }
}
function resolve(registry, name) {
  return registry && (registry[name] || registry[camelize(name)] || registry[capitalize(camelize(name))]);
}
function renderList(source, renderItem, cache, index) {
  let ret;
  const cached = cache;
  const sourceIsArray = isArray(source);
  if (sourceIsArray || isString(source)) {
    const sourceIsReactiveArray = sourceIsArray && isReactive(source);
    let needsWrap = false;
    let isReadonlySource = false;
    if (sourceIsReactiveArray) {
      needsWrap = !isShallow(source);
      isReadonlySource = isReadonly(source);
      source = shallowReadArray(source);
    }
    ret = new Array(source.length);
    for (let i = 0, l = source.length; i < l; i++) {
      ret[i] = renderItem(
        needsWrap ? isReadonlySource ? toReadonly(toReactive(source[i])) : toReactive(source[i]) : source[i],
        i,
        void 0,
        cached
      );
    }
  } else if (typeof source === "number") {
    ret = new Array(source);
    for (let i = 0; i < source; i++) {
      ret[i] = renderItem(i + 1, i, void 0, cached);
    }
  } else if (isObject(source)) {
    if (source[Symbol.iterator]) {
      ret = Array.from(
        source,
        (item, i) => renderItem(item, i, void 0, cached)
      );
    } else {
      const keys = Object.keys(source);
      ret = new Array(keys.length);
      for (let i = 0, l = keys.length; i < l; i++) {
        const key = keys[i];
        ret[i] = renderItem(source[key], key, i, cached);
      }
    }
  } else {
    ret = [];
  }
  return ret;
}
function renderSlot(slots, name, props = {}, fallback, noSlotted) {
  if (currentRenderingInstance.ce || currentRenderingInstance.parent && isAsyncWrapper(currentRenderingInstance.parent) && currentRenderingInstance.parent.ce) {
    return openBlock(), createBlock(
      Fragment,
      null,
      [createVNode("slot", props, fallback)],
      64
    );
  }
  let slot = slots[name];
  if (slot && slot._c) {
    slot._d = false;
  }
  openBlock();
  const validSlotContent = slot && ensureValidVNode(slot(props));
  const slotKey = props.key || // slot content array of a dynamic conditional slot may have a branch
  // key attached in the `createSlots` helper, respect that
  validSlotContent && validSlotContent.key;
  const rendered = createBlock(
    Fragment,
    {
      key: (slotKey && !isSymbol(slotKey) ? slotKey : `_${name}`) + // #7256 force differentiate fallback content from actual content
      ""
    },
    validSlotContent || [],
    validSlotContent && slots._ === 1 ? 64 : -2
  );
  if (slot && slot._c) {
    slot._d = true;
  }
  return rendered;
}
function ensureValidVNode(vnodes) {
  return vnodes.some((child) => {
    if (!isVNode(child)) return true;
    if (child.type === Comment) return false;
    if (child.type === Fragment && !ensureValidVNode(child.children))
      return false;
    return true;
  }) ? vnodes : null;
}
const getPublicInstance = (i) => {
  if (!i) return null;
  if (isStatefulComponent(i)) return getComponentPublicInstance(i);
  return getPublicInstance(i.parent);
};
const publicPropertiesMap = (
  // Move PURE marker to new line to workaround compiler discarding it
  // due to type annotation
  /* @__PURE__ */ extend(/* @__PURE__ */ Object.create(null), {
    $: (i) => i,
    $el: (i) => i.vnode.el,
    $data: (i) => i.data,
    $props: (i) => i.props,
    $attrs: (i) => i.attrs,
    $slots: (i) => i.slots,
    $refs: (i) => i.refs,
    $parent: (i) => getPublicInstance(i.parent),
    $root: (i) => getPublicInstance(i.root),
    $host: (i) => i.ce,
    $emit: (i) => i.emit,
    $options: (i) => resolveMergedOptions(i),
    $forceUpdate: (i) => i.f || (i.f = () => {
      queueJob(i.update);
    }),
    $nextTick: (i) => i.n || (i.n = nextTick.bind(i.proxy)),
    $watch: (i) => instanceWatch.bind(i)
  })
);
const hasSetupBinding = (state, key) => state !== EMPTY_OBJ && !state.__isScriptSetup && hasOwn(state, key);
const PublicInstanceProxyHandlers = {
  get({ _: instance }, key) {
    if (key === "__v_skip") {
      return true;
    }
    const { ctx, setupState, data, props, accessCache, type, appContext } = instance;
    let normalizedProps;
    if (key[0] !== "$") {
      const n2 = accessCache[key];
      if (n2 !== void 0) {
        switch (n2) {
          case 1:
            return setupState[key];
          case 2:
            return data[key];
          case 4:
            return ctx[key];
          case 3:
            return props[key];
        }
      } else if (hasSetupBinding(setupState, key)) {
        accessCache[key] = 1;
        return setupState[key];
      } else if (data !== EMPTY_OBJ && hasOwn(data, key)) {
        accessCache[key] = 2;
        return data[key];
      } else if (
        // only cache other properties when instance has declared (thus stable)
        // props
        (normalizedProps = instance.propsOptions[0]) && hasOwn(normalizedProps, key)
      ) {
        accessCache[key] = 3;
        return props[key];
      } else if (ctx !== EMPTY_OBJ && hasOwn(ctx, key)) {
        accessCache[key] = 4;
        return ctx[key];
      } else if (shouldCacheAccess) {
        accessCache[key] = 0;
      }
    }
    const publicGetter = publicPropertiesMap[key];
    let cssModule, globalProperties;
    if (publicGetter) {
      if (key === "$attrs") {
        track(instance.attrs, "get", "");
      }
      return publicGetter(instance);
    } else if (
      // css module (injected by vue-loader)
      (cssModule = type.__cssModules) && (cssModule = cssModule[key])
    ) {
      return cssModule;
    } else if (ctx !== EMPTY_OBJ && hasOwn(ctx, key)) {
      accessCache[key] = 4;
      return ctx[key];
    } else if (
      // global properties
      globalProperties = appContext.config.globalProperties, hasOwn(globalProperties, key)
    ) {
      {
        return globalProperties[key];
      }
    } else ;
  },
  set({ _: instance }, key, value) {
    const { data, setupState, ctx } = instance;
    if (hasSetupBinding(setupState, key)) {
      setupState[key] = value;
      return true;
    } else if (data !== EMPTY_OBJ && hasOwn(data, key)) {
      data[key] = value;
      return true;
    } else if (hasOwn(instance.props, key)) {
      return false;
    }
    if (key[0] === "$" && key.slice(1) in instance) {
      return false;
    } else {
      {
        ctx[key] = value;
      }
    }
    return true;
  },
  has({
    _: { data, setupState, accessCache, ctx, appContext, propsOptions }
  }, key) {
    let normalizedProps;
    return !!accessCache[key] || data !== EMPTY_OBJ && hasOwn(data, key) || hasSetupBinding(setupState, key) || (normalizedProps = propsOptions[0]) && hasOwn(normalizedProps, key) || hasOwn(ctx, key) || hasOwn(publicPropertiesMap, key) || hasOwn(appContext.config.globalProperties, key);
  },
  defineProperty(target, key, descriptor) {
    if (descriptor.get != null) {
      target._.accessCache[key] = 0;
    } else if (hasOwn(descriptor, "value")) {
      this.set(target, key, descriptor.value, null);
    }
    return Reflect.defineProperty(target, key, descriptor);
  }
};
function normalizePropsOrEmits(props) {
  return isArray(props) ? props.reduce(
    (normalized, p2) => (normalized[p2] = null, normalized),
    {}
  ) : props;
}
let shouldCacheAccess = true;
function applyOptions(instance) {
  const options = resolveMergedOptions(instance);
  const publicThis = instance.proxy;
  const ctx = instance.ctx;
  shouldCacheAccess = false;
  if (options.beforeCreate) {
    callHook$1(options.beforeCreate, instance, "bc");
  }
  const {
    // state
    data: dataOptions,
    computed: computedOptions,
    methods,
    watch: watchOptions,
    provide: provideOptions,
    inject: injectOptions,
    // lifecycle
    created,
    beforeMount,
    mounted,
    beforeUpdate,
    updated,
    activated,
    deactivated,
    beforeDestroy,
    beforeUnmount,
    destroyed,
    unmounted,
    render: render2,
    renderTracked,
    renderTriggered,
    errorCaptured,
    serverPrefetch,
    // public API
    expose,
    inheritAttrs,
    // assets
    components,
    directives,
    filters
  } = options;
  const checkDuplicateProperties = null;
  if (injectOptions) {
    resolveInjections(injectOptions, ctx, checkDuplicateProperties);
  }
  if (methods) {
    for (const key in methods) {
      const methodHandler = methods[key];
      if (isFunction(methodHandler)) {
        {
          ctx[key] = methodHandler.bind(publicThis);
        }
      }
    }
  }
  if (dataOptions) {
    const data = dataOptions.call(publicThis, publicThis);
    if (!isObject(data)) ;
    else {
      instance.data = reactive(data);
    }
  }
  shouldCacheAccess = true;
  if (computedOptions) {
    for (const key in computedOptions) {
      const opt = computedOptions[key];
      const get = isFunction(opt) ? opt.bind(publicThis, publicThis) : isFunction(opt.get) ? opt.get.bind(publicThis, publicThis) : NOOP;
      const set = !isFunction(opt) && isFunction(opt.set) ? opt.set.bind(publicThis) : NOOP;
      const c = computed({
        get,
        set
      });
      Object.defineProperty(ctx, key, {
        enumerable: true,
        configurable: true,
        get: () => c.value,
        set: (v) => c.value = v
      });
    }
  }
  if (watchOptions) {
    for (const key in watchOptions) {
      createWatcher(watchOptions[key], ctx, publicThis, key);
    }
  }
  if (provideOptions) {
    const provides = isFunction(provideOptions) ? provideOptions.call(publicThis) : provideOptions;
    Reflect.ownKeys(provides).forEach((key) => {
      provide(key, provides[key]);
    });
  }
  if (created) {
    callHook$1(created, instance, "c");
  }
  function registerLifecycleHook(register, hook) {
    if (isArray(hook)) {
      hook.forEach((_hook) => register(_hook.bind(publicThis)));
    } else if (hook) {
      register(hook.bind(publicThis));
    }
  }
  registerLifecycleHook(onBeforeMount, beforeMount);
  registerLifecycleHook(onMounted, mounted);
  registerLifecycleHook(onBeforeUpdate, beforeUpdate);
  registerLifecycleHook(onUpdated, updated);
  registerLifecycleHook(onActivated, activated);
  registerLifecycleHook(onDeactivated, deactivated);
  registerLifecycleHook(onErrorCaptured, errorCaptured);
  registerLifecycleHook(onRenderTracked, renderTracked);
  registerLifecycleHook(onRenderTriggered, renderTriggered);
  registerLifecycleHook(onBeforeUnmount, beforeUnmount);
  registerLifecycleHook(onUnmounted, unmounted);
  registerLifecycleHook(onServerPrefetch, serverPrefetch);
  if (isArray(expose)) {
    if (expose.length) {
      const exposed = instance.exposed || (instance.exposed = {});
      expose.forEach((key) => {
        Object.defineProperty(exposed, key, {
          get: () => publicThis[key],
          set: (val) => publicThis[key] = val
        });
      });
    } else if (!instance.exposed) {
      instance.exposed = {};
    }
  }
  if (render2 && instance.render === NOOP) {
    instance.render = render2;
  }
  if (inheritAttrs != null) {
    instance.inheritAttrs = inheritAttrs;
  }
  if (components) instance.components = components;
  if (directives) instance.directives = directives;
  if (serverPrefetch) {
    markAsyncBoundary(instance);
  }
}
function resolveInjections(injectOptions, ctx, checkDuplicateProperties = NOOP) {
  if (isArray(injectOptions)) {
    injectOptions = normalizeInject(injectOptions);
  }
  for (const key in injectOptions) {
    const opt = injectOptions[key];
    let injected;
    if (isObject(opt)) {
      if ("default" in opt) {
        injected = inject(
          opt.from || key,
          opt.default,
          true
        );
      } else {
        injected = inject(opt.from || key);
      }
    } else {
      injected = inject(opt);
    }
    if (isRef(injected)) {
      Object.defineProperty(ctx, key, {
        enumerable: true,
        configurable: true,
        get: () => injected.value,
        set: (v) => injected.value = v
      });
    } else {
      ctx[key] = injected;
    }
  }
}
function callHook$1(hook, instance, type) {
  callWithAsyncErrorHandling(
    isArray(hook) ? hook.map((h2) => h2.bind(instance.proxy)) : hook.bind(instance.proxy),
    instance,
    type
  );
}
function createWatcher(raw, ctx, publicThis, key) {
  let getter = key.includes(".") ? createPathGetter(publicThis, key) : () => publicThis[key];
  if (isString(raw)) {
    const handler = ctx[raw];
    if (isFunction(handler)) {
      {
        watch(getter, handler);
      }
    }
  } else if (isFunction(raw)) {
    {
      watch(getter, raw.bind(publicThis));
    }
  } else if (isObject(raw)) {
    if (isArray(raw)) {
      raw.forEach((r) => createWatcher(r, ctx, publicThis, key));
    } else {
      const handler = isFunction(raw.handler) ? raw.handler.bind(publicThis) : ctx[raw.handler];
      if (isFunction(handler)) {
        watch(getter, handler, raw);
      }
    }
  } else ;
}
function resolveMergedOptions(instance) {
  const base = instance.type;
  const { mixins, extends: extendsOptions } = base;
  const {
    mixins: globalMixins,
    optionsCache: cache,
    config: { optionMergeStrategies }
  } = instance.appContext;
  const cached = cache.get(base);
  let resolved;
  if (cached) {
    resolved = cached;
  } else if (!globalMixins.length && !mixins && !extendsOptions) {
    {
      resolved = base;
    }
  } else {
    resolved = {};
    if (globalMixins.length) {
      globalMixins.forEach(
        (m) => mergeOptions(resolved, m, optionMergeStrategies, true)
      );
    }
    mergeOptions(resolved, base, optionMergeStrategies);
  }
  if (isObject(base)) {
    cache.set(base, resolved);
  }
  return resolved;
}
function mergeOptions(to2, from, strats, asMixin = false) {
  const { mixins, extends: extendsOptions } = from;
  if (extendsOptions) {
    mergeOptions(to2, extendsOptions, strats, true);
  }
  if (mixins) {
    mixins.forEach(
      (m) => mergeOptions(to2, m, strats, true)
    );
  }
  for (const key in from) {
    if (asMixin && key === "expose") ;
    else {
      const strat = internalOptionMergeStrats[key] || strats && strats[key];
      to2[key] = strat ? strat(to2[key], from[key]) : from[key];
    }
  }
  return to2;
}
const internalOptionMergeStrats = {
  data: mergeDataFn,
  props: mergeEmitsOrPropsOptions,
  emits: mergeEmitsOrPropsOptions,
  // objects
  methods: mergeObjectOptions,
  computed: mergeObjectOptions,
  // lifecycle
  beforeCreate: mergeAsArray,
  created: mergeAsArray,
  beforeMount: mergeAsArray,
  mounted: mergeAsArray,
  beforeUpdate: mergeAsArray,
  updated: mergeAsArray,
  beforeDestroy: mergeAsArray,
  beforeUnmount: mergeAsArray,
  destroyed: mergeAsArray,
  unmounted: mergeAsArray,
  activated: mergeAsArray,
  deactivated: mergeAsArray,
  errorCaptured: mergeAsArray,
  serverPrefetch: mergeAsArray,
  // assets
  components: mergeObjectOptions,
  directives: mergeObjectOptions,
  // watch
  watch: mergeWatchOptions,
  // provide / inject
  provide: mergeDataFn,
  inject: mergeInject
};
function mergeDataFn(to2, from) {
  if (!from) {
    return to2;
  }
  if (!to2) {
    return from;
  }
  return function mergedDataFn() {
    return extend(
      isFunction(to2) ? to2.call(this, this) : to2,
      isFunction(from) ? from.call(this, this) : from
    );
  };
}
function mergeInject(to2, from) {
  return mergeObjectOptions(normalizeInject(to2), normalizeInject(from));
}
function normalizeInject(raw) {
  if (isArray(raw)) {
    const res = {};
    for (let i = 0; i < raw.length; i++) {
      res[raw[i]] = raw[i];
    }
    return res;
  }
  return raw;
}
function mergeAsArray(to2, from) {
  return to2 ? [...new Set([].concat(to2, from))] : from;
}
function mergeObjectOptions(to2, from) {
  return to2 ? extend(/* @__PURE__ */ Object.create(null), to2, from) : from;
}
function mergeEmitsOrPropsOptions(to2, from) {
  if (to2) {
    if (isArray(to2) && isArray(from)) {
      return [.../* @__PURE__ */ new Set([...to2, ...from])];
    }
    return extend(
      /* @__PURE__ */ Object.create(null),
      normalizePropsOrEmits(to2),
      normalizePropsOrEmits(from != null ? from : {})
    );
  } else {
    return from;
  }
}
function mergeWatchOptions(to2, from) {
  if (!to2) return from;
  if (!from) return to2;
  const merged = extend(/* @__PURE__ */ Object.create(null), to2);
  for (const key in from) {
    merged[key] = mergeAsArray(to2[key], from[key]);
  }
  return merged;
}
function createAppContext() {
  return {
    app: null,
    config: {
      isNativeTag: NO,
      performance: false,
      globalProperties: {},
      optionMergeStrategies: {},
      errorHandler: void 0,
      warnHandler: void 0,
      compilerOptions: {}
    },
    mixins: [],
    components: {},
    directives: {},
    provides: /* @__PURE__ */ Object.create(null),
    optionsCache: /* @__PURE__ */ new WeakMap(),
    propsCache: /* @__PURE__ */ new WeakMap(),
    emitsCache: /* @__PURE__ */ new WeakMap()
  };
}
let uid$1 = 0;
function createAppAPI(render2, hydrate) {
  return function createApp2(rootComponent, rootProps = null) {
    if (!isFunction(rootComponent)) {
      rootComponent = extend({}, rootComponent);
    }
    if (rootProps != null && !isObject(rootProps)) {
      rootProps = null;
    }
    const context = createAppContext();
    const installedPlugins = /* @__PURE__ */ new WeakSet();
    const pluginCleanupFns = [];
    let isMounted = false;
    const app = context.app = {
      _uid: uid$1++,
      _component: rootComponent,
      _props: rootProps,
      _container: null,
      _context: context,
      _instance: null,
      version,
      get config() {
        return context.config;
      },
      set config(v) {
      },
      use(plugin, ...options) {
        if (installedPlugins.has(plugin)) ;
        else if (plugin && isFunction(plugin.install)) {
          installedPlugins.add(plugin);
          plugin.install(app, ...options);
        } else if (isFunction(plugin)) {
          installedPlugins.add(plugin);
          plugin(app, ...options);
        } else ;
        return app;
      },
      mixin(mixin) {
        {
          if (!context.mixins.includes(mixin)) {
            context.mixins.push(mixin);
          }
        }
        return app;
      },
      component(name, component) {
        if (!component) {
          return context.components[name];
        }
        context.components[name] = component;
        return app;
      },
      directive(name, directive) {
        if (!directive) {
          return context.directives[name];
        }
        context.directives[name] = directive;
        return app;
      },
      mount(rootContainer, isHydrate, namespace) {
        if (!isMounted) {
          const vnode = app._ceVNode || createVNode(rootComponent, rootProps);
          vnode.appContext = context;
          if (namespace === true) {
            namespace = "svg";
          } else if (namespace === false) {
            namespace = void 0;
          }
          {
            render2(vnode, rootContainer, namespace);
          }
          isMounted = true;
          app._container = rootContainer;
          rootContainer.__vue_app__ = app;
          return getComponentPublicInstance(vnode.component);
        }
      },
      onUnmount(cleanupFn) {
        pluginCleanupFns.push(cleanupFn);
      },
      unmount() {
        if (isMounted) {
          callWithAsyncErrorHandling(
            pluginCleanupFns,
            app._instance,
            16
          );
          render2(null, app._container);
          delete app._container.__vue_app__;
        }
      },
      provide(key, value) {
        context.provides[key] = value;
        return app;
      },
      runWithContext(fn2) {
        const lastApp = currentApp;
        currentApp = app;
        try {
          return fn2();
        } finally {
          currentApp = lastApp;
        }
      }
    };
    return app;
  };
}
let currentApp = null;
function provide(key, value) {
  if (!currentInstance) ;
  else {
    let provides = currentInstance.provides;
    const parentProvides = currentInstance.parent && currentInstance.parent.provides;
    if (parentProvides === provides) {
      provides = currentInstance.provides = Object.create(parentProvides);
    }
    provides[key] = value;
  }
}
function inject(key, defaultValue, treatDefaultAsFactory = false) {
  const instance = currentInstance || currentRenderingInstance;
  if (instance || currentApp) {
    let provides = currentApp ? currentApp._context.provides : instance ? instance.parent == null || instance.ce ? instance.vnode.appContext && instance.vnode.appContext.provides : instance.parent.provides : void 0;
    if (provides && key in provides) {
      return provides[key];
    } else if (arguments.length > 1) {
      return treatDefaultAsFactory && isFunction(defaultValue) ? defaultValue.call(instance && instance.proxy) : defaultValue;
    } else ;
  }
}
const internalObjectProto = {};
const createInternalObject = () => Object.create(internalObjectProto);
const isInternalObject = (obj) => Object.getPrototypeOf(obj) === internalObjectProto;
function initProps(instance, rawProps, isStateful, isSSR = false) {
  const props = {};
  const attrs = createInternalObject();
  instance.propsDefaults = /* @__PURE__ */ Object.create(null);
  setFullProps(instance, rawProps, props, attrs);
  for (const key in instance.propsOptions[0]) {
    if (!(key in props)) {
      props[key] = void 0;
    }
  }
  if (isStateful) {
    instance.props = isSSR ? props : shallowReactive(props);
  } else {
    if (!instance.type.props) {
      instance.props = attrs;
    } else {
      instance.props = props;
    }
  }
  instance.attrs = attrs;
}
function updateProps(instance, rawProps, rawPrevProps, optimized) {
  const {
    props,
    attrs,
    vnode: { patchFlag }
  } = instance;
  const rawCurrentProps = toRaw(props);
  const [options] = instance.propsOptions;
  let hasAttrsChanged = false;
  if (
    // always force full diff in dev
    // - #1942 if hmr is enabled with sfc component
    // - vite#872 non-sfc component used by sfc component
    (optimized || patchFlag > 0) && !(patchFlag & 16)
  ) {
    if (patchFlag & 8) {
      const propsToUpdate = instance.vnode.dynamicProps;
      for (let i = 0; i < propsToUpdate.length; i++) {
        let key = propsToUpdate[i];
        if (isEmitListener(instance.emitsOptions, key)) {
          continue;
        }
        const value = rawProps[key];
        if (options) {
          if (hasOwn(attrs, key)) {
            if (value !== attrs[key]) {
              attrs[key] = value;
              hasAttrsChanged = true;
            }
          } else {
            const camelizedKey = camelize(key);
            props[camelizedKey] = resolvePropValue(
              options,
              rawCurrentProps,
              camelizedKey,
              value,
              instance,
              false
            );
          }
        } else {
          if (value !== attrs[key]) {
            attrs[key] = value;
            hasAttrsChanged = true;
          }
        }
      }
    }
  } else {
    if (setFullProps(instance, rawProps, props, attrs)) {
      hasAttrsChanged = true;
    }
    let kebabKey;
    for (const key in rawCurrentProps) {
      if (!rawProps || // for camelCase
      !hasOwn(rawProps, key) && // it's possible the original props was passed in as kebab-case
      // and converted to camelCase (#955)
      ((kebabKey = hyphenate(key)) === key || !hasOwn(rawProps, kebabKey))) {
        if (options) {
          if (rawPrevProps && // for camelCase
          (rawPrevProps[key] !== void 0 || // for kebab-case
          rawPrevProps[kebabKey] !== void 0)) {
            props[key] = resolvePropValue(
              options,
              rawCurrentProps,
              key,
              void 0,
              instance,
              true
            );
          }
        } else {
          delete props[key];
        }
      }
    }
    if (attrs !== rawCurrentProps) {
      for (const key in attrs) {
        if (!rawProps || !hasOwn(rawProps, key) && true) {
          delete attrs[key];
          hasAttrsChanged = true;
        }
      }
    }
  }
  if (hasAttrsChanged) {
    trigger(instance.attrs, "set", "");
  }
}
function setFullProps(instance, rawProps, props, attrs) {
  const [options, needCastKeys] = instance.propsOptions;
  let hasAttrsChanged = false;
  let rawCastValues;
  if (rawProps) {
    for (let key in rawProps) {
      if (isReservedProp(key)) {
        continue;
      }
      const value = rawProps[key];
      let camelKey;
      if (options && hasOwn(options, camelKey = camelize(key))) {
        if (!needCastKeys || !needCastKeys.includes(camelKey)) {
          props[camelKey] = value;
        } else {
          (rawCastValues || (rawCastValues = {}))[camelKey] = value;
        }
      } else if (!isEmitListener(instance.emitsOptions, key)) {
        if (!(key in attrs) || value !== attrs[key]) {
          attrs[key] = value;
          hasAttrsChanged = true;
        }
      }
    }
  }
  if (needCastKeys) {
    const rawCurrentProps = toRaw(props);
    const castValues = rawCastValues || EMPTY_OBJ;
    for (let i = 0; i < needCastKeys.length; i++) {
      const key = needCastKeys[i];
      props[key] = resolvePropValue(
        options,
        rawCurrentProps,
        key,
        castValues[key],
        instance,
        !hasOwn(castValues, key)
      );
    }
  }
  return hasAttrsChanged;
}
function resolvePropValue(options, props, key, value, instance, isAbsent) {
  const opt = options[key];
  if (opt != null) {
    const hasDefault = hasOwn(opt, "default");
    if (hasDefault && value === void 0) {
      const defaultValue = opt.default;
      if (opt.type !== Function && !opt.skipFactory && isFunction(defaultValue)) {
        const { propsDefaults } = instance;
        if (key in propsDefaults) {
          value = propsDefaults[key];
        } else {
          const reset = setCurrentInstance(instance);
          value = propsDefaults[key] = defaultValue.call(
            null,
            props
          );
          reset();
        }
      } else {
        value = defaultValue;
      }
      if (instance.ce) {
        instance.ce._setProp(key, value);
      }
    }
    if (opt[
      0
      /* shouldCast */
    ]) {
      if (isAbsent && !hasDefault) {
        value = false;
      } else if (opt[
        1
        /* shouldCastTrue */
      ] && (value === "" || value === hyphenate(key))) {
        value = true;
      }
    }
  }
  return value;
}
const mixinPropsCache = /* @__PURE__ */ new WeakMap();
function normalizePropsOptions(comp, appContext, asMixin = false) {
  const cache = asMixin ? mixinPropsCache : appContext.propsCache;
  const cached = cache.get(comp);
  if (cached) {
    return cached;
  }
  const raw = comp.props;
  const normalized = {};
  const needCastKeys = [];
  let hasExtends = false;
  if (!isFunction(comp)) {
    const extendProps = (raw2) => {
      hasExtends = true;
      const [props, keys] = normalizePropsOptions(raw2, appContext, true);
      extend(normalized, props);
      if (keys) needCastKeys.push(...keys);
    };
    if (!asMixin && appContext.mixins.length) {
      appContext.mixins.forEach(extendProps);
    }
    if (comp.extends) {
      extendProps(comp.extends);
    }
    if (comp.mixins) {
      comp.mixins.forEach(extendProps);
    }
  }
  if (!raw && !hasExtends) {
    if (isObject(comp)) {
      cache.set(comp, EMPTY_ARR);
    }
    return EMPTY_ARR;
  }
  if (isArray(raw)) {
    for (let i = 0; i < raw.length; i++) {
      const normalizedKey = camelize(raw[i]);
      if (validatePropName(normalizedKey)) {
        normalized[normalizedKey] = EMPTY_OBJ;
      }
    }
  } else if (raw) {
    for (const key in raw) {
      const normalizedKey = camelize(key);
      if (validatePropName(normalizedKey)) {
        const opt = raw[key];
        const prop = normalized[normalizedKey] = isArray(opt) || isFunction(opt) ? { type: opt } : extend({}, opt);
        const propType = prop.type;
        let shouldCast = false;
        let shouldCastTrue = true;
        if (isArray(propType)) {
          for (let index = 0; index < propType.length; ++index) {
            const type = propType[index];
            const typeName = isFunction(type) && type.name;
            if (typeName === "Boolean") {
              shouldCast = true;
              break;
            } else if (typeName === "String") {
              shouldCastTrue = false;
            }
          }
        } else {
          shouldCast = isFunction(propType) && propType.name === "Boolean";
        }
        prop[
          0
          /* shouldCast */
        ] = shouldCast;
        prop[
          1
          /* shouldCastTrue */
        ] = shouldCastTrue;
        if (shouldCast || hasOwn(prop, "default")) {
          needCastKeys.push(normalizedKey);
        }
      }
    }
  }
  const res = [normalized, needCastKeys];
  if (isObject(comp)) {
    cache.set(comp, res);
  }
  return res;
}
function validatePropName(key) {
  if (key[0] !== "$" && !isReservedProp(key)) {
    return true;
  }
  return false;
}
const isInternalKey = (key) => key[0] === "_" || key === "$stable";
const normalizeSlotValue = (value) => isArray(value) ? value.map(normalizeVNode) : [normalizeVNode(value)];
const normalizeSlot = (key, rawSlot, ctx) => {
  if (rawSlot._n) {
    return rawSlot;
  }
  const normalized = withCtx((...args) => {
    if (false) ;
    return normalizeSlotValue(rawSlot(...args));
  }, ctx);
  normalized._c = false;
  return normalized;
};
const normalizeObjectSlots = (rawSlots, slots, instance) => {
  const ctx = rawSlots._ctx;
  for (const key in rawSlots) {
    if (isInternalKey(key)) continue;
    const value = rawSlots[key];
    if (isFunction(value)) {
      slots[key] = normalizeSlot(key, value, ctx);
    } else if (value != null) {
      const normalized = normalizeSlotValue(value);
      slots[key] = () => normalized;
    }
  }
};
const normalizeVNodeSlots = (instance, children) => {
  const normalized = normalizeSlotValue(children);
  instance.slots.default = () => normalized;
};
const assignSlots = (slots, children, optimized) => {
  for (const key in children) {
    if (optimized || !isInternalKey(key)) {
      slots[key] = children[key];
    }
  }
};
const initSlots = (instance, children, optimized) => {
  const slots = instance.slots = createInternalObject();
  if (instance.vnode.shapeFlag & 32) {
    const type = children._;
    if (type) {
      assignSlots(slots, children, optimized);
      if (optimized) {
        def(slots, "_", type, true);
      }
    } else {
      normalizeObjectSlots(children, slots);
    }
  } else if (children) {
    normalizeVNodeSlots(instance, children);
  }
};
const updateSlots = (instance, children, optimized) => {
  const { vnode, slots } = instance;
  let needDeletionCheck = true;
  let deletionComparisonTarget = EMPTY_OBJ;
  if (vnode.shapeFlag & 32) {
    const type = children._;
    if (type) {
      if (optimized && type === 1) {
        needDeletionCheck = false;
      } else {
        assignSlots(slots, children, optimized);
      }
    } else {
      needDeletionCheck = !children.$stable;
      normalizeObjectSlots(children, slots);
    }
    deletionComparisonTarget = children;
  } else if (children) {
    normalizeVNodeSlots(instance, children);
    deletionComparisonTarget = { default: 1 };
  }
  if (needDeletionCheck) {
    for (const key in slots) {
      if (!isInternalKey(key) && deletionComparisonTarget[key] == null) {
        delete slots[key];
      }
    }
  }
};
const queuePostRenderEffect = queueEffectWithSuspense;
function createRenderer(options) {
  return baseCreateRenderer(options);
}
function baseCreateRenderer(options, createHydrationFns) {
  const target = getGlobalThis();
  target.__VUE__ = true;
  const {
    insert: hostInsert,
    remove: hostRemove,
    patchProp: hostPatchProp,
    createElement: hostCreateElement,
    createText: hostCreateText,
    createComment: hostCreateComment,
    setText: hostSetText,
    setElementText: hostSetElementText,
    parentNode: hostParentNode,
    nextSibling: hostNextSibling,
    setScopeId: hostSetScopeId = NOOP,
    insertStaticContent: hostInsertStaticContent
  } = options;
  const patch = (n12, n2, container, anchor = null, parentComponent = null, parentSuspense = null, namespace = void 0, slotScopeIds = null, optimized = !!n2.dynamicChildren) => {
    if (n12 === n2) {
      return;
    }
    if (n12 && !isSameVNodeType(n12, n2)) {
      anchor = getNextHostNode(n12);
      unmount(n12, parentComponent, parentSuspense, true);
      n12 = null;
    }
    if (n2.patchFlag === -2) {
      optimized = false;
      n2.dynamicChildren = null;
    }
    const { type, ref: ref3, shapeFlag } = n2;
    switch (type) {
      case Text:
        processText(n12, n2, container, anchor);
        break;
      case Comment:
        processCommentNode(n12, n2, container, anchor);
        break;
      case Static:
        if (n12 == null) {
          mountStaticNode(n2, container, anchor, namespace);
        }
        break;
      case Fragment:
        processFragment(
          n12,
          n2,
          container,
          anchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
        break;
      default:
        if (shapeFlag & 1) {
          processElement(
            n12,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        } else if (shapeFlag & 6) {
          processComponent(
            n12,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        } else if (shapeFlag & 64) {
          type.process(
            n12,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized,
            internals
          );
        } else if (shapeFlag & 128) {
          type.process(
            n12,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized,
            internals
          );
        } else ;
    }
    if (ref3 != null && parentComponent) {
      setRef(ref3, n12 && n12.ref, parentSuspense, n2 || n12, !n2);
    }
  };
  const processText = (n12, n2, container, anchor) => {
    if (n12 == null) {
      hostInsert(
        n2.el = hostCreateText(n2.children),
        container,
        anchor
      );
    } else {
      const el2 = n2.el = n12.el;
      if (n2.children !== n12.children) {
        hostSetText(el2, n2.children);
      }
    }
  };
  const processCommentNode = (n12, n2, container, anchor) => {
    if (n12 == null) {
      hostInsert(
        n2.el = hostCreateComment(n2.children || ""),
        container,
        anchor
      );
    } else {
      n2.el = n12.el;
    }
  };
  const mountStaticNode = (n2, container, anchor, namespace) => {
    [n2.el, n2.anchor] = hostInsertStaticContent(
      n2.children,
      container,
      anchor,
      namespace,
      n2.el,
      n2.anchor
    );
  };
  const moveStaticNode = ({ el: el2, anchor }, container, nextSibling) => {
    let next;
    while (el2 && el2 !== anchor) {
      next = hostNextSibling(el2);
      hostInsert(el2, container, nextSibling);
      el2 = next;
    }
    hostInsert(anchor, container, nextSibling);
  };
  const removeStaticNode = ({ el: el2, anchor }) => {
    let next;
    while (el2 && el2 !== anchor) {
      next = hostNextSibling(el2);
      hostRemove(el2);
      el2 = next;
    }
    hostRemove(anchor);
  };
  const processElement = (n12, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    if (n2.type === "svg") {
      namespace = "svg";
    } else if (n2.type === "math") {
      namespace = "mathml";
    }
    if (n12 == null) {
      mountElement(
        n2,
        container,
        anchor,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    } else {
      patchElement(
        n12,
        n2,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    }
  };
  const mountElement = (vnode, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    let el2;
    let vnodeHook;
    const { props, shapeFlag, transition, dirs } = vnode;
    el2 = vnode.el = hostCreateElement(
      vnode.type,
      namespace,
      props && props.is,
      props
    );
    if (shapeFlag & 8) {
      hostSetElementText(el2, vnode.children);
    } else if (shapeFlag & 16) {
      mountChildren(
        vnode.children,
        el2,
        null,
        parentComponent,
        parentSuspense,
        resolveChildrenNamespace(vnode, namespace),
        slotScopeIds,
        optimized
      );
    }
    if (dirs) {
      invokeDirectiveHook(vnode, null, parentComponent, "created");
    }
    setScopeId(el2, vnode, vnode.scopeId, slotScopeIds, parentComponent);
    if (props) {
      for (const key in props) {
        if (key !== "value" && !isReservedProp(key)) {
          hostPatchProp(el2, key, null, props[key], namespace, parentComponent);
        }
      }
      if ("value" in props) {
        hostPatchProp(el2, "value", null, props.value, namespace);
      }
      if (vnodeHook = props.onVnodeBeforeMount) {
        invokeVNodeHook(vnodeHook, parentComponent, vnode);
      }
    }
    if (dirs) {
      invokeDirectiveHook(vnode, null, parentComponent, "beforeMount");
    }
    const needCallTransitionHooks = needTransition(parentSuspense, transition);
    if (needCallTransitionHooks) {
      transition.beforeEnter(el2);
    }
    hostInsert(el2, container, anchor);
    if ((vnodeHook = props && props.onVnodeMounted) || needCallTransitionHooks || dirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, vnode);
        needCallTransitionHooks && transition.enter(el2);
        dirs && invokeDirectiveHook(vnode, null, parentComponent, "mounted");
      }, parentSuspense);
    }
  };
  const setScopeId = (el2, vnode, scopeId, slotScopeIds, parentComponent) => {
    if (scopeId) {
      hostSetScopeId(el2, scopeId);
    }
    if (slotScopeIds) {
      for (let i = 0; i < slotScopeIds.length; i++) {
        hostSetScopeId(el2, slotScopeIds[i]);
      }
    }
    if (parentComponent) {
      let subTree = parentComponent.subTree;
      if (vnode === subTree || isSuspense(subTree.type) && (subTree.ssContent === vnode || subTree.ssFallback === vnode)) {
        const parentVNode = parentComponent.vnode;
        setScopeId(
          el2,
          parentVNode,
          parentVNode.scopeId,
          parentVNode.slotScopeIds,
          parentComponent.parent
        );
      }
    }
  };
  const mountChildren = (children, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, start = 0) => {
    for (let i = start; i < children.length; i++) {
      const child = children[i] = optimized ? cloneIfMounted(children[i]) : normalizeVNode(children[i]);
      patch(
        null,
        child,
        container,
        anchor,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    }
  };
  const patchElement = (n12, n2, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    const el2 = n2.el = n12.el;
    let { patchFlag, dynamicChildren, dirs } = n2;
    patchFlag |= n12.patchFlag & 16;
    const oldProps = n12.props || EMPTY_OBJ;
    const newProps = n2.props || EMPTY_OBJ;
    let vnodeHook;
    parentComponent && toggleRecurse(parentComponent, false);
    if (vnodeHook = newProps.onVnodeBeforeUpdate) {
      invokeVNodeHook(vnodeHook, parentComponent, n2, n12);
    }
    if (dirs) {
      invokeDirectiveHook(n2, n12, parentComponent, "beforeUpdate");
    }
    parentComponent && toggleRecurse(parentComponent, true);
    if (oldProps.innerHTML && newProps.innerHTML == null || oldProps.textContent && newProps.textContent == null) {
      hostSetElementText(el2, "");
    }
    if (dynamicChildren) {
      patchBlockChildren(
        n12.dynamicChildren,
        dynamicChildren,
        el2,
        parentComponent,
        parentSuspense,
        resolveChildrenNamespace(n2, namespace),
        slotScopeIds
      );
    } else if (!optimized) {
      patchChildren(
        n12,
        n2,
        el2,
        null,
        parentComponent,
        parentSuspense,
        resolveChildrenNamespace(n2, namespace),
        slotScopeIds,
        false
      );
    }
    if (patchFlag > 0) {
      if (patchFlag & 16) {
        patchProps(el2, oldProps, newProps, parentComponent, namespace);
      } else {
        if (patchFlag & 2) {
          if (oldProps.class !== newProps.class) {
            hostPatchProp(el2, "class", null, newProps.class, namespace);
          }
        }
        if (patchFlag & 4) {
          hostPatchProp(el2, "style", oldProps.style, newProps.style, namespace);
        }
        if (patchFlag & 8) {
          const propsToUpdate = n2.dynamicProps;
          for (let i = 0; i < propsToUpdate.length; i++) {
            const key = propsToUpdate[i];
            const prev = oldProps[key];
            const next = newProps[key];
            if (next !== prev || key === "value") {
              hostPatchProp(el2, key, prev, next, namespace, parentComponent);
            }
          }
        }
      }
      if (patchFlag & 1) {
        if (n12.children !== n2.children) {
          hostSetElementText(el2, n2.children);
        }
      }
    } else if (!optimized && dynamicChildren == null) {
      patchProps(el2, oldProps, newProps, parentComponent, namespace);
    }
    if ((vnodeHook = newProps.onVnodeUpdated) || dirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, n2, n12);
        dirs && invokeDirectiveHook(n2, n12, parentComponent, "updated");
      }, parentSuspense);
    }
  };
  const patchBlockChildren = (oldChildren, newChildren, fallbackContainer, parentComponent, parentSuspense, namespace, slotScopeIds) => {
    for (let i = 0; i < newChildren.length; i++) {
      const oldVNode = oldChildren[i];
      const newVNode = newChildren[i];
      const container = (
        // oldVNode may be an errored async setup() component inside Suspense
        // which will not have a mounted element
        oldVNode.el && // - In the case of a Fragment, we need to provide the actual parent
        // of the Fragment itself so it can move its children.
        (oldVNode.type === Fragment || // - In the case of different nodes, there is going to be a replacement
        // which also requires the correct parent container
        !isSameVNodeType(oldVNode, newVNode) || // - In the case of a component, it could contain anything.
        oldVNode.shapeFlag & (6 | 64 | 128)) ? hostParentNode(oldVNode.el) : (
          // In other cases, the parent container is not actually used so we
          // just pass the block element here to avoid a DOM parentNode call.
          fallbackContainer
        )
      );
      patch(
        oldVNode,
        newVNode,
        container,
        null,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        true
      );
    }
  };
  const patchProps = (el2, oldProps, newProps, parentComponent, namespace) => {
    if (oldProps !== newProps) {
      if (oldProps !== EMPTY_OBJ) {
        for (const key in oldProps) {
          if (!isReservedProp(key) && !(key in newProps)) {
            hostPatchProp(
              el2,
              key,
              oldProps[key],
              null,
              namespace,
              parentComponent
            );
          }
        }
      }
      for (const key in newProps) {
        if (isReservedProp(key)) continue;
        const next = newProps[key];
        const prev = oldProps[key];
        if (next !== prev && key !== "value") {
          hostPatchProp(el2, key, prev, next, namespace, parentComponent);
        }
      }
      if ("value" in newProps) {
        hostPatchProp(el2, "value", oldProps.value, newProps.value, namespace);
      }
    }
  };
  const processFragment = (n12, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    const fragmentStartAnchor = n2.el = n12 ? n12.el : hostCreateText("");
    const fragmentEndAnchor = n2.anchor = n12 ? n12.anchor : hostCreateText("");
    let { patchFlag, dynamicChildren, slotScopeIds: fragmentSlotScopeIds } = n2;
    if (fragmentSlotScopeIds) {
      slotScopeIds = slotScopeIds ? slotScopeIds.concat(fragmentSlotScopeIds) : fragmentSlotScopeIds;
    }
    if (n12 == null) {
      hostInsert(fragmentStartAnchor, container, anchor);
      hostInsert(fragmentEndAnchor, container, anchor);
      mountChildren(
        // #10007
        // such fragment like `<></>` will be compiled into
        // a fragment which doesn't have a children.
        // In this case fallback to an empty array
        n2.children || [],
        container,
        fragmentEndAnchor,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    } else {
      if (patchFlag > 0 && patchFlag & 64 && dynamicChildren && // #2715 the previous fragment could've been a BAILed one as a result
      // of renderSlot() with no valid children
      n12.dynamicChildren) {
        patchBlockChildren(
          n12.dynamicChildren,
          dynamicChildren,
          container,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds
        );
        if (
          // #2080 if the stable fragment has a key, it's a <template v-for> that may
          //  get moved around. Make sure all root level vnodes inherit el.
          // #2134 or if it's a component root, it may also get moved around
          // as the component is being moved.
          n2.key != null || parentComponent && n2 === parentComponent.subTree
        ) {
          traverseStaticChildren(
            n12,
            n2,
            true
            /* shallow */
          );
        }
      } else {
        patchChildren(
          n12,
          n2,
          container,
          fragmentEndAnchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
      }
    }
  };
  const processComponent = (n12, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    n2.slotScopeIds = slotScopeIds;
    if (n12 == null) {
      if (n2.shapeFlag & 512) {
        parentComponent.ctx.activate(
          n2,
          container,
          anchor,
          namespace,
          optimized
        );
      } else {
        mountComponent(
          n2,
          container,
          anchor,
          parentComponent,
          parentSuspense,
          namespace,
          optimized
        );
      }
    } else {
      updateComponent(n12, n2, optimized);
    }
  };
  const mountComponent = (initialVNode, container, anchor, parentComponent, parentSuspense, namespace, optimized) => {
    const instance = initialVNode.component = createComponentInstance(
      initialVNode,
      parentComponent,
      parentSuspense
    );
    if (isKeepAlive(initialVNode)) {
      instance.ctx.renderer = internals;
    }
    {
      setupComponent(instance, false, optimized);
    }
    if (instance.asyncDep) {
      parentSuspense && parentSuspense.registerDep(instance, setupRenderEffect, optimized);
      if (!initialVNode.el) {
        const placeholder = instance.subTree = createVNode(Comment);
        processCommentNode(null, placeholder, container, anchor);
      }
    } else {
      setupRenderEffect(
        instance,
        initialVNode,
        container,
        anchor,
        parentSuspense,
        namespace,
        optimized
      );
    }
  };
  const updateComponent = (n12, n2, optimized) => {
    const instance = n2.component = n12.component;
    if (shouldUpdateComponent(n12, n2, optimized)) {
      if (instance.asyncDep && !instance.asyncResolved) {
        updateComponentPreRender(instance, n2, optimized);
        return;
      } else {
        instance.next = n2;
        instance.update();
      }
    } else {
      n2.el = n12.el;
      instance.vnode = n2;
    }
  };
  const setupRenderEffect = (instance, initialVNode, container, anchor, parentSuspense, namespace, optimized) => {
    const componentUpdateFn = () => {
      if (!instance.isMounted) {
        let vnodeHook;
        const { el: el2, props } = initialVNode;
        const { bm: bm2, m, parent, root, type } = instance;
        const isAsyncWrapperVNode = isAsyncWrapper(initialVNode);
        toggleRecurse(instance, false);
        if (bm2) {
          invokeArrayFns(bm2);
        }
        if (!isAsyncWrapperVNode && (vnodeHook = props && props.onVnodeBeforeMount)) {
          invokeVNodeHook(vnodeHook, parent, initialVNode);
        }
        toggleRecurse(instance, true);
        {
          if (root.ce) {
            root.ce._injectChildStyle(type);
          }
          const subTree = instance.subTree = renderComponentRoot(instance);
          patch(
            null,
            subTree,
            container,
            anchor,
            instance,
            parentSuspense,
            namespace
          );
          initialVNode.el = subTree.el;
        }
        if (m) {
          queuePostRenderEffect(m, parentSuspense);
        }
        if (!isAsyncWrapperVNode && (vnodeHook = props && props.onVnodeMounted)) {
          const scopedInitialVNode = initialVNode;
          queuePostRenderEffect(
            () => invokeVNodeHook(vnodeHook, parent, scopedInitialVNode),
            parentSuspense
          );
        }
        if (initialVNode.shapeFlag & 256 || parent && isAsyncWrapper(parent.vnode) && parent.vnode.shapeFlag & 256) {
          instance.a && queuePostRenderEffect(instance.a, parentSuspense);
        }
        instance.isMounted = true;
        initialVNode = container = anchor = null;
      } else {
        let { next, bu: bu2, u, parent, vnode } = instance;
        {
          const nonHydratedAsyncRoot = locateNonHydratedAsyncRoot(instance);
          if (nonHydratedAsyncRoot) {
            if (next) {
              next.el = vnode.el;
              updateComponentPreRender(instance, next, optimized);
            }
            nonHydratedAsyncRoot.asyncDep.then(() => {
              if (!instance.isUnmounted) {
                componentUpdateFn();
              }
            });
            return;
          }
        }
        let originNext = next;
        let vnodeHook;
        toggleRecurse(instance, false);
        if (next) {
          next.el = vnode.el;
          updateComponentPreRender(instance, next, optimized);
        } else {
          next = vnode;
        }
        if (bu2) {
          invokeArrayFns(bu2);
        }
        if (vnodeHook = next.props && next.props.onVnodeBeforeUpdate) {
          invokeVNodeHook(vnodeHook, parent, next, vnode);
        }
        toggleRecurse(instance, true);
        const nextTree = renderComponentRoot(instance);
        const prevTree = instance.subTree;
        instance.subTree = nextTree;
        patch(
          prevTree,
          nextTree,
          // parent may have changed if it's in a teleport
          hostParentNode(prevTree.el),
          // anchor may have changed if it's in a fragment
          getNextHostNode(prevTree),
          instance,
          parentSuspense,
          namespace
        );
        next.el = nextTree.el;
        if (originNext === null) {
          updateHOCHostEl(instance, nextTree.el);
        }
        if (u) {
          queuePostRenderEffect(u, parentSuspense);
        }
        if (vnodeHook = next.props && next.props.onVnodeUpdated) {
          queuePostRenderEffect(
            () => invokeVNodeHook(vnodeHook, parent, next, vnode),
            parentSuspense
          );
        }
      }
    };
    instance.scope.on();
    const effect2 = instance.effect = new ReactiveEffect(componentUpdateFn);
    instance.scope.off();
    const update = instance.update = effect2.run.bind(effect2);
    const job = instance.job = effect2.runIfDirty.bind(effect2);
    job.i = instance;
    job.id = instance.uid;
    effect2.scheduler = () => queueJob(job);
    toggleRecurse(instance, true);
    update();
  };
  const updateComponentPreRender = (instance, nextVNode, optimized) => {
    nextVNode.component = instance;
    const prevProps = instance.vnode.props;
    instance.vnode = nextVNode;
    instance.next = null;
    updateProps(instance, nextVNode.props, prevProps, optimized);
    updateSlots(instance, nextVNode.children, optimized);
    pauseTracking();
    flushPreFlushCbs(instance);
    resetTracking();
  };
  const patchChildren = (n12, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized = false) => {
    const c12 = n12 && n12.children;
    const prevShapeFlag = n12 ? n12.shapeFlag : 0;
    const c2 = n2.children;
    const { patchFlag, shapeFlag } = n2;
    if (patchFlag > 0) {
      if (patchFlag & 128) {
        patchKeyedChildren(
          c12,
          c2,
          container,
          anchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
        return;
      } else if (patchFlag & 256) {
        patchUnkeyedChildren(
          c12,
          c2,
          container,
          anchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
        return;
      }
    }
    if (shapeFlag & 8) {
      if (prevShapeFlag & 16) {
        unmountChildren(c12, parentComponent, parentSuspense);
      }
      if (c2 !== c12) {
        hostSetElementText(container, c2);
      }
    } else {
      if (prevShapeFlag & 16) {
        if (shapeFlag & 16) {
          patchKeyedChildren(
            c12,
            c2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        } else {
          unmountChildren(c12, parentComponent, parentSuspense, true);
        }
      } else {
        if (prevShapeFlag & 8) {
          hostSetElementText(container, "");
        }
        if (shapeFlag & 16) {
          mountChildren(
            c2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        }
      }
    }
  };
  const patchUnkeyedChildren = (c12, c2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    c12 = c12 || EMPTY_ARR;
    c2 = c2 || EMPTY_ARR;
    const oldLength = c12.length;
    const newLength = c2.length;
    const commonLength = Math.min(oldLength, newLength);
    let i;
    for (i = 0; i < commonLength; i++) {
      const nextChild = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
      patch(
        c12[i],
        nextChild,
        container,
        null,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    }
    if (oldLength > newLength) {
      unmountChildren(
        c12,
        parentComponent,
        parentSuspense,
        true,
        false,
        commonLength
      );
    } else {
      mountChildren(
        c2,
        container,
        anchor,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized,
        commonLength
      );
    }
  };
  const patchKeyedChildren = (c12, c2, container, parentAnchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    let i = 0;
    const l2 = c2.length;
    let e12 = c12.length - 1;
    let e2 = l2 - 1;
    while (i <= e12 && i <= e2) {
      const n12 = c12[i];
      const n2 = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
      if (isSameVNodeType(n12, n2)) {
        patch(
          n12,
          n2,
          container,
          null,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
      } else {
        break;
      }
      i++;
    }
    while (i <= e12 && i <= e2) {
      const n12 = c12[e12];
      const n2 = c2[e2] = optimized ? cloneIfMounted(c2[e2]) : normalizeVNode(c2[e2]);
      if (isSameVNodeType(n12, n2)) {
        patch(
          n12,
          n2,
          container,
          null,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
      } else {
        break;
      }
      e12--;
      e2--;
    }
    if (i > e12) {
      if (i <= e2) {
        const nextPos = e2 + 1;
        const anchor = nextPos < l2 ? c2[nextPos].el : parentAnchor;
        while (i <= e2) {
          patch(
            null,
            c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]),
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
          i++;
        }
      }
    } else if (i > e2) {
      while (i <= e12) {
        unmount(c12[i], parentComponent, parentSuspense, true);
        i++;
      }
    } else {
      const s12 = i;
      const s2 = i;
      const keyToNewIndexMap = /* @__PURE__ */ new Map();
      for (i = s2; i <= e2; i++) {
        const nextChild = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
        if (nextChild.key != null) {
          keyToNewIndexMap.set(nextChild.key, i);
        }
      }
      let j;
      let patched = 0;
      const toBePatched = e2 - s2 + 1;
      let moved = false;
      let maxNewIndexSoFar = 0;
      const newIndexToOldIndexMap = new Array(toBePatched);
      for (i = 0; i < toBePatched; i++) newIndexToOldIndexMap[i] = 0;
      for (i = s12; i <= e12; i++) {
        const prevChild = c12[i];
        if (patched >= toBePatched) {
          unmount(prevChild, parentComponent, parentSuspense, true);
          continue;
        }
        let newIndex;
        if (prevChild.key != null) {
          newIndex = keyToNewIndexMap.get(prevChild.key);
        } else {
          for (j = s2; j <= e2; j++) {
            if (newIndexToOldIndexMap[j - s2] === 0 && isSameVNodeType(prevChild, c2[j])) {
              newIndex = j;
              break;
            }
          }
        }
        if (newIndex === void 0) {
          unmount(prevChild, parentComponent, parentSuspense, true);
        } else {
          newIndexToOldIndexMap[newIndex - s2] = i + 1;
          if (newIndex >= maxNewIndexSoFar) {
            maxNewIndexSoFar = newIndex;
          } else {
            moved = true;
          }
          patch(
            prevChild,
            c2[newIndex],
            container,
            null,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
          patched++;
        }
      }
      const increasingNewIndexSequence = moved ? getSequence(newIndexToOldIndexMap) : EMPTY_ARR;
      j = increasingNewIndexSequence.length - 1;
      for (i = toBePatched - 1; i >= 0; i--) {
        const nextIndex = s2 + i;
        const nextChild = c2[nextIndex];
        const anchor = nextIndex + 1 < l2 ? c2[nextIndex + 1].el : parentAnchor;
        if (newIndexToOldIndexMap[i] === 0) {
          patch(
            null,
            nextChild,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        } else if (moved) {
          if (j < 0 || i !== increasingNewIndexSequence[j]) {
            move(nextChild, container, anchor, 2);
          } else {
            j--;
          }
        }
      }
    }
  };
  const move = (vnode, container, anchor, moveType, parentSuspense = null) => {
    const { el: el2, type, transition, children, shapeFlag } = vnode;
    if (shapeFlag & 6) {
      move(vnode.component.subTree, container, anchor, moveType);
      return;
    }
    if (shapeFlag & 128) {
      vnode.suspense.move(container, anchor, moveType);
      return;
    }
    if (shapeFlag & 64) {
      type.move(vnode, container, anchor, internals);
      return;
    }
    if (type === Fragment) {
      hostInsert(el2, container, anchor);
      for (let i = 0; i < children.length; i++) {
        move(children[i], container, anchor, moveType);
      }
      hostInsert(vnode.anchor, container, anchor);
      return;
    }
    if (type === Static) {
      moveStaticNode(vnode, container, anchor);
      return;
    }
    const needTransition2 = moveType !== 2 && shapeFlag & 1 && transition;
    if (needTransition2) {
      if (moveType === 0) {
        transition.beforeEnter(el2);
        hostInsert(el2, container, anchor);
        queuePostRenderEffect(() => transition.enter(el2), parentSuspense);
      } else {
        const { leave, delayLeave, afterLeave } = transition;
        const remove22 = () => {
          if (vnode.ctx.isUnmounted) {
            hostRemove(el2);
          } else {
            hostInsert(el2, container, anchor);
          }
        };
        const performLeave = () => {
          leave(el2, () => {
            remove22();
            afterLeave && afterLeave();
          });
        };
        if (delayLeave) {
          delayLeave(el2, remove22, performLeave);
        } else {
          performLeave();
        }
      }
    } else {
      hostInsert(el2, container, anchor);
    }
  };
  const unmount = (vnode, parentComponent, parentSuspense, doRemove = false, optimized = false) => {
    const {
      type,
      props,
      ref: ref3,
      children,
      dynamicChildren,
      shapeFlag,
      patchFlag,
      dirs,
      cacheIndex
    } = vnode;
    if (patchFlag === -2) {
      optimized = false;
    }
    if (ref3 != null) {
      pauseTracking();
      setRef(ref3, null, parentSuspense, vnode, true);
      resetTracking();
    }
    if (cacheIndex != null) {
      parentComponent.renderCache[cacheIndex] = void 0;
    }
    if (shapeFlag & 256) {
      parentComponent.ctx.deactivate(vnode);
      return;
    }
    const shouldInvokeDirs = shapeFlag & 1 && dirs;
    const shouldInvokeVnodeHook = !isAsyncWrapper(vnode);
    let vnodeHook;
    if (shouldInvokeVnodeHook && (vnodeHook = props && props.onVnodeBeforeUnmount)) {
      invokeVNodeHook(vnodeHook, parentComponent, vnode);
    }
    if (shapeFlag & 6) {
      unmountComponent(vnode.component, parentSuspense, doRemove);
    } else {
      if (shapeFlag & 128) {
        vnode.suspense.unmount(parentSuspense, doRemove);
        return;
      }
      if (shouldInvokeDirs) {
        invokeDirectiveHook(vnode, null, parentComponent, "beforeUnmount");
      }
      if (shapeFlag & 64) {
        vnode.type.remove(
          vnode,
          parentComponent,
          parentSuspense,
          internals,
          doRemove
        );
      } else if (dynamicChildren && // #5154
      // when v-once is used inside a block, setBlockTracking(-1) marks the
      // parent block with hasOnce: true
      // so that it doesn't take the fast path during unmount - otherwise
      // components nested in v-once are never unmounted.
      !dynamicChildren.hasOnce && // #1153: fast path should not be taken for non-stable (v-for) fragments
      (type !== Fragment || patchFlag > 0 && patchFlag & 64)) {
        unmountChildren(
          dynamicChildren,
          parentComponent,
          parentSuspense,
          false,
          true
        );
      } else if (type === Fragment && patchFlag & (128 | 256) || !optimized && shapeFlag & 16) {
        unmountChildren(children, parentComponent, parentSuspense);
      }
      if (doRemove) {
        remove2(vnode);
      }
    }
    if (shouldInvokeVnodeHook && (vnodeHook = props && props.onVnodeUnmounted) || shouldInvokeDirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, vnode);
        shouldInvokeDirs && invokeDirectiveHook(vnode, null, parentComponent, "unmounted");
      }, parentSuspense);
    }
  };
  const remove2 = (vnode) => {
    const { type, el: el2, anchor, transition } = vnode;
    if (type === Fragment) {
      {
        removeFragment(el2, anchor);
      }
      return;
    }
    if (type === Static) {
      removeStaticNode(vnode);
      return;
    }
    const performRemove = () => {
      hostRemove(el2);
      if (transition && !transition.persisted && transition.afterLeave) {
        transition.afterLeave();
      }
    };
    if (vnode.shapeFlag & 1 && transition && !transition.persisted) {
      const { leave, delayLeave } = transition;
      const performLeave = () => leave(el2, performRemove);
      if (delayLeave) {
        delayLeave(vnode.el, performRemove, performLeave);
      } else {
        performLeave();
      }
    } else {
      performRemove();
    }
  };
  const removeFragment = (cur, end) => {
    let next;
    while (cur !== end) {
      next = hostNextSibling(cur);
      hostRemove(cur);
      cur = next;
    }
    hostRemove(end);
  };
  const unmountComponent = (instance, parentSuspense, doRemove) => {
    const {
      bum,
      scope,
      job,
      subTree,
      um: um2,
      m,
      a,
      parent,
      slots: { __: slotCacheKeys }
    } = instance;
    invalidateMount(m);
    invalidateMount(a);
    if (bum) {
      invokeArrayFns(bum);
    }
    if (parent && isArray(slotCacheKeys)) {
      slotCacheKeys.forEach((v) => {
        parent.renderCache[v] = void 0;
      });
    }
    scope.stop();
    if (job) {
      job.flags |= 8;
      unmount(subTree, instance, parentSuspense, doRemove);
    }
    if (um2) {
      queuePostRenderEffect(um2, parentSuspense);
    }
    queuePostRenderEffect(() => {
      instance.isUnmounted = true;
    }, parentSuspense);
    if (parentSuspense && parentSuspense.pendingBranch && !parentSuspense.isUnmounted && instance.asyncDep && !instance.asyncResolved && instance.suspenseId === parentSuspense.pendingId) {
      parentSuspense.deps--;
      if (parentSuspense.deps === 0) {
        parentSuspense.resolve();
      }
    }
  };
  const unmountChildren = (children, parentComponent, parentSuspense, doRemove = false, optimized = false, start = 0) => {
    for (let i = start; i < children.length; i++) {
      unmount(children[i], parentComponent, parentSuspense, doRemove, optimized);
    }
  };
  const getNextHostNode = (vnode) => {
    if (vnode.shapeFlag & 6) {
      return getNextHostNode(vnode.component.subTree);
    }
    if (vnode.shapeFlag & 128) {
      return vnode.suspense.next();
    }
    const el2 = hostNextSibling(vnode.anchor || vnode.el);
    const teleportEnd = el2 && el2[TeleportEndKey];
    return teleportEnd ? hostNextSibling(teleportEnd) : el2;
  };
  let isFlushing = false;
  const render2 = (vnode, container, namespace) => {
    if (vnode == null) {
      if (container._vnode) {
        unmount(container._vnode, null, null, true);
      }
    } else {
      patch(
        container._vnode || null,
        vnode,
        container,
        null,
        null,
        null,
        namespace
      );
    }
    container._vnode = vnode;
    if (!isFlushing) {
      isFlushing = true;
      flushPreFlushCbs();
      flushPostFlushCbs();
      isFlushing = false;
    }
  };
  const internals = {
    p: patch,
    um: unmount,
    m: move,
    r: remove2,
    mt: mountComponent,
    mc: mountChildren,
    pc: patchChildren,
    pbc: patchBlockChildren,
    n: getNextHostNode,
    o: options
  };
  let hydrate;
  return {
    render: render2,
    hydrate,
    createApp: createAppAPI(render2)
  };
}
function resolveChildrenNamespace({ type, props }, currentNamespace) {
  return currentNamespace === "svg" && type === "foreignObject" || currentNamespace === "mathml" && type === "annotation-xml" && props && props.encoding && props.encoding.includes("html") ? void 0 : currentNamespace;
}
function toggleRecurse({ effect: effect2, job }, allowed) {
  if (allowed) {
    effect2.flags |= 32;
    job.flags |= 4;
  } else {
    effect2.flags &= -33;
    job.flags &= -5;
  }
}
function needTransition(parentSuspense, transition) {
  return (!parentSuspense || parentSuspense && !parentSuspense.pendingBranch) && transition && !transition.persisted;
}
function traverseStaticChildren(n12, n2, shallow = false) {
  const ch1 = n12.children;
  const ch2 = n2.children;
  if (isArray(ch1) && isArray(ch2)) {
    for (let i = 0; i < ch1.length; i++) {
      const c12 = ch1[i];
      let c2 = ch2[i];
      if (c2.shapeFlag & 1 && !c2.dynamicChildren) {
        if (c2.patchFlag <= 0 || c2.patchFlag === 32) {
          c2 = ch2[i] = cloneIfMounted(ch2[i]);
          c2.el = c12.el;
        }
        if (!shallow && c2.patchFlag !== -2)
          traverseStaticChildren(c12, c2);
      }
      if (c2.type === Text) {
        c2.el = c12.el;
      }
      if (c2.type === Comment && !c2.el) {
        c2.el = c12.el;
      }
    }
  }
}
function getSequence(arr) {
  const p2 = arr.slice();
  const result = [0];
  let i, j, u, v, c;
  const len = arr.length;
  for (i = 0; i < len; i++) {
    const arrI = arr[i];
    if (arrI !== 0) {
      j = result[result.length - 1];
      if (arr[j] < arrI) {
        p2[i] = j;
        result.push(i);
        continue;
      }
      u = 0;
      v = result.length - 1;
      while (u < v) {
        c = u + v >> 1;
        if (arr[result[c]] < arrI) {
          u = c + 1;
        } else {
          v = c;
        }
      }
      if (arrI < arr[result[u]]) {
        if (u > 0) {
          p2[i] = result[u - 1];
        }
        result[u] = i;
      }
    }
  }
  u = result.length;
  v = result[u - 1];
  while (u-- > 0) {
    result[u] = v;
    v = p2[v];
  }
  return result;
}
function locateNonHydratedAsyncRoot(instance) {
  const subComponent = instance.subTree.component;
  if (subComponent) {
    if (subComponent.asyncDep && !subComponent.asyncResolved) {
      return subComponent;
    } else {
      return locateNonHydratedAsyncRoot(subComponent);
    }
  }
}
function invalidateMount(hooks) {
  if (hooks) {
    for (let i = 0; i < hooks.length; i++)
      hooks[i].flags |= 8;
  }
}
const ssrContextKey = Symbol.for("v-scx");
const useSSRContext = () => {
  {
    const ctx = inject(ssrContextKey);
    return ctx;
  }
};
function watchEffect(effect2, options) {
  return doWatch(effect2, null, options);
}
function watch(source, cb2, options) {
  return doWatch(source, cb2, options);
}
function doWatch(source, cb2, options = EMPTY_OBJ) {
  const { immediate, deep, flush, once } = options;
  const baseWatchOptions = extend({}, options);
  const runsImmediately = cb2 && immediate || !cb2 && flush !== "post";
  let ssrCleanup;
  if (isInSSRComponentSetup) {
    if (flush === "sync") {
      const ctx = useSSRContext();
      ssrCleanup = ctx.__watcherHandles || (ctx.__watcherHandles = []);
    } else if (!runsImmediately) {
      const watchStopHandle = () => {
      };
      watchStopHandle.stop = NOOP;
      watchStopHandle.resume = NOOP;
      watchStopHandle.pause = NOOP;
      return watchStopHandle;
    }
  }
  const instance = currentInstance;
  baseWatchOptions.call = (fn2, type, args) => callWithAsyncErrorHandling(fn2, instance, type, args);
  let isPre = false;
  if (flush === "post") {
    baseWatchOptions.scheduler = (job) => {
      queuePostRenderEffect(job, instance && instance.suspense);
    };
  } else if (flush !== "sync") {
    isPre = true;
    baseWatchOptions.scheduler = (job, isFirstRun) => {
      if (isFirstRun) {
        job();
      } else {
        queueJob(job);
      }
    };
  }
  baseWatchOptions.augmentJob = (job) => {
    if (cb2) {
      job.flags |= 4;
    }
    if (isPre) {
      job.flags |= 2;
      if (instance) {
        job.id = instance.uid;
        job.i = instance;
      }
    }
  };
  const watchHandle = watch$1(source, cb2, baseWatchOptions);
  if (isInSSRComponentSetup) {
    if (ssrCleanup) {
      ssrCleanup.push(watchHandle);
    } else if (runsImmediately) {
      watchHandle();
    }
  }
  return watchHandle;
}
function instanceWatch(source, value, options) {
  const publicThis = this.proxy;
  const getter = isString(source) ? source.includes(".") ? createPathGetter(publicThis, source) : () => publicThis[source] : source.bind(publicThis, publicThis);
  let cb2;
  if (isFunction(value)) {
    cb2 = value;
  } else {
    cb2 = value.handler;
    options = value;
  }
  const reset = setCurrentInstance(this);
  const res = doWatch(getter, cb2.bind(publicThis), options);
  reset();
  return res;
}
function createPathGetter(ctx, path) {
  const segments = path.split(".");
  return () => {
    let cur = ctx;
    for (let i = 0; i < segments.length && cur; i++) {
      cur = cur[segments[i]];
    }
    return cur;
  };
}
const getModelModifiers = (props, modelName) => {
  return modelName === "modelValue" || modelName === "model-value" ? props.modelModifiers : props[`${modelName}Modifiers`] || props[`${camelize(modelName)}Modifiers`] || props[`${hyphenate(modelName)}Modifiers`];
};
function emit(instance, event, ...rawArgs) {
  if (instance.isUnmounted) return;
  const props = instance.vnode.props || EMPTY_OBJ;
  let args = rawArgs;
  const isModelListener2 = event.startsWith("update:");
  const modifiers = isModelListener2 && getModelModifiers(props, event.slice(7));
  if (modifiers) {
    if (modifiers.trim) {
      args = rawArgs.map((a) => isString(a) ? a.trim() : a);
    }
    if (modifiers.number) {
      args = rawArgs.map(looseToNumber);
    }
  }
  let handlerName;
  let handler = props[handlerName = toHandlerKey(event)] || // also try camelCase event handler (#2249)
  props[handlerName = toHandlerKey(camelize(event))];
  if (!handler && isModelListener2) {
    handler = props[handlerName = toHandlerKey(hyphenate(event))];
  }
  if (handler) {
    callWithAsyncErrorHandling(
      handler,
      instance,
      6,
      args
    );
  }
  const onceHandler = props[handlerName + `Once`];
  if (onceHandler) {
    if (!instance.emitted) {
      instance.emitted = {};
    } else if (instance.emitted[handlerName]) {
      return;
    }
    instance.emitted[handlerName] = true;
    callWithAsyncErrorHandling(
      onceHandler,
      instance,
      6,
      args
    );
  }
}
function normalizeEmitsOptions(comp, appContext, asMixin = false) {
  const cache = appContext.emitsCache;
  const cached = cache.get(comp);
  if (cached !== void 0) {
    return cached;
  }
  const raw = comp.emits;
  let normalized = {};
  let hasExtends = false;
  if (!isFunction(comp)) {
    const extendEmits = (raw2) => {
      const normalizedFromExtend = normalizeEmitsOptions(raw2, appContext, true);
      if (normalizedFromExtend) {
        hasExtends = true;
        extend(normalized, normalizedFromExtend);
      }
    };
    if (!asMixin && appContext.mixins.length) {
      appContext.mixins.forEach(extendEmits);
    }
    if (comp.extends) {
      extendEmits(comp.extends);
    }
    if (comp.mixins) {
      comp.mixins.forEach(extendEmits);
    }
  }
  if (!raw && !hasExtends) {
    if (isObject(comp)) {
      cache.set(comp, null);
    }
    return null;
  }
  if (isArray(raw)) {
    raw.forEach((key) => normalized[key] = null);
  } else {
    extend(normalized, raw);
  }
  if (isObject(comp)) {
    cache.set(comp, normalized);
  }
  return normalized;
}
function isEmitListener(options, key) {
  if (!options || !isOn(key)) {
    return false;
  }
  key = key.slice(2).replace(/Once$/, "");
  return hasOwn(options, key[0].toLowerCase() + key.slice(1)) || hasOwn(options, hyphenate(key)) || hasOwn(options, key);
}
function markAttrsAccessed() {
}
function renderComponentRoot(instance) {
  const {
    type: Component,
    vnode,
    proxy,
    withProxy,
    propsOptions: [propsOptions],
    slots,
    attrs,
    emit: emit2,
    render: render2,
    renderCache,
    props,
    data,
    setupState,
    ctx,
    inheritAttrs
  } = instance;
  const prev = setCurrentRenderingInstance(instance);
  let result;
  let fallthroughAttrs;
  try {
    if (vnode.shapeFlag & 4) {
      const proxyToUse = withProxy || proxy;
      const thisProxy = false ? new Proxy(proxyToUse, {
        get(target, key, receiver) {
          warn$1(
            `Property '${String(
              key
            )}' was accessed via 'this'. Avoid using 'this' in templates.`
          );
          return Reflect.get(target, key, receiver);
        }
      }) : proxyToUse;
      result = normalizeVNode(
        render2.call(
          thisProxy,
          proxyToUse,
          renderCache,
          false ? shallowReadonly(props) : props,
          setupState,
          data,
          ctx
        )
      );
      fallthroughAttrs = attrs;
    } else {
      const render22 = Component;
      if (false) ;
      result = normalizeVNode(
        render22.length > 1 ? render22(
          false ? shallowReadonly(props) : props,
          false ? {
            get attrs() {
              markAttrsAccessed();
              return shallowReadonly(attrs);
            },
            slots,
            emit: emit2
          } : { attrs, slots, emit: emit2 }
        ) : render22(
          false ? shallowReadonly(props) : props,
          null
        )
      );
      fallthroughAttrs = Component.props ? attrs : getFunctionalFallthrough(attrs);
    }
  } catch (err) {
    blockStack.length = 0;
    handleError(err, instance, 1);
    result = createVNode(Comment);
  }
  let root = result;
  if (fallthroughAttrs && inheritAttrs !== false) {
    const keys = Object.keys(fallthroughAttrs);
    const { shapeFlag } = root;
    if (keys.length) {
      if (shapeFlag & (1 | 6)) {
        if (propsOptions && keys.some(isModelListener)) {
          fallthroughAttrs = filterModelListeners(
            fallthroughAttrs,
            propsOptions
          );
        }
        root = cloneVNode(root, fallthroughAttrs, false, true);
      }
    }
  }
  if (vnode.dirs) {
    root = cloneVNode(root, null, false, true);
    root.dirs = root.dirs ? root.dirs.concat(vnode.dirs) : vnode.dirs;
  }
  if (vnode.transition) {
    setTransitionHooks(root, vnode.transition);
  }
  {
    result = root;
  }
  setCurrentRenderingInstance(prev);
  return result;
}
const getFunctionalFallthrough = (attrs) => {
  let res;
  for (const key in attrs) {
    if (key === "class" || key === "style" || isOn(key)) {
      (res || (res = {}))[key] = attrs[key];
    }
  }
  return res;
};
const filterModelListeners = (attrs, props) => {
  const res = {};
  for (const key in attrs) {
    if (!isModelListener(key) || !(key.slice(9) in props)) {
      res[key] = attrs[key];
    }
  }
  return res;
};
function shouldUpdateComponent(prevVNode, nextVNode, optimized) {
  const { props: prevProps, children: prevChildren, component } = prevVNode;
  const { props: nextProps, children: nextChildren, patchFlag } = nextVNode;
  const emits = component.emitsOptions;
  if (nextVNode.dirs || nextVNode.transition) {
    return true;
  }
  if (optimized && patchFlag >= 0) {
    if (patchFlag & 1024) {
      return true;
    }
    if (patchFlag & 16) {
      if (!prevProps) {
        return !!nextProps;
      }
      return hasPropsChanged(prevProps, nextProps, emits);
    } else if (patchFlag & 8) {
      const dynamicProps = nextVNode.dynamicProps;
      for (let i = 0; i < dynamicProps.length; i++) {
        const key = dynamicProps[i];
        if (nextProps[key] !== prevProps[key] && !isEmitListener(emits, key)) {
          return true;
        }
      }
    }
  } else {
    if (prevChildren || nextChildren) {
      if (!nextChildren || !nextChildren.$stable) {
        return true;
      }
    }
    if (prevProps === nextProps) {
      return false;
    }
    if (!prevProps) {
      return !!nextProps;
    }
    if (!nextProps) {
      return true;
    }
    return hasPropsChanged(prevProps, nextProps, emits);
  }
  return false;
}
function hasPropsChanged(prevProps, nextProps, emitsOptions) {
  const nextKeys = Object.keys(nextProps);
  if (nextKeys.length !== Object.keys(prevProps).length) {
    return true;
  }
  for (let i = 0; i < nextKeys.length; i++) {
    const key = nextKeys[i];
    if (nextProps[key] !== prevProps[key] && !isEmitListener(emitsOptions, key)) {
      return true;
    }
  }
  return false;
}
function updateHOCHostEl({ vnode, parent }, el2) {
  while (parent) {
    const root = parent.subTree;
    if (root.suspense && root.suspense.activeBranch === vnode) {
      root.el = vnode.el;
    }
    if (root === vnode) {
      (vnode = parent.vnode).el = el2;
      parent = parent.parent;
    } else {
      break;
    }
  }
}
const isSuspense = (type) => type.__isSuspense;
function queueEffectWithSuspense(fn2, suspense) {
  if (suspense && suspense.pendingBranch) {
    if (isArray(fn2)) {
      suspense.effects.push(...fn2);
    } else {
      suspense.effects.push(fn2);
    }
  } else {
    queuePostFlushCb(fn2);
  }
}
const Fragment = Symbol.for("v-fgt");
const Text = Symbol.for("v-txt");
const Comment = Symbol.for("v-cmt");
const Static = Symbol.for("v-stc");
const blockStack = [];
let currentBlock = null;
function openBlock(disableTracking = false) {
  blockStack.push(currentBlock = disableTracking ? null : []);
}
function closeBlock() {
  blockStack.pop();
  currentBlock = blockStack[blockStack.length - 1] || null;
}
let isBlockTreeEnabled = 1;
function setBlockTracking(value, inVOnce = false) {
  isBlockTreeEnabled += value;
  if (value < 0 && currentBlock && inVOnce) {
    currentBlock.hasOnce = true;
  }
}
function setupBlock(vnode) {
  vnode.dynamicChildren = isBlockTreeEnabled > 0 ? currentBlock || EMPTY_ARR : null;
  closeBlock();
  if (isBlockTreeEnabled > 0 && currentBlock) {
    currentBlock.push(vnode);
  }
  return vnode;
}
function createElementBlock(type, props, children, patchFlag, dynamicProps, shapeFlag) {
  return setupBlock(
    createBaseVNode(
      type,
      props,
      children,
      patchFlag,
      dynamicProps,
      shapeFlag,
      true
    )
  );
}
function createBlock(type, props, children, patchFlag, dynamicProps) {
  return setupBlock(
    createVNode(
      type,
      props,
      children,
      patchFlag,
      dynamicProps,
      true
    )
  );
}
function isVNode(value) {
  return value ? value.__v_isVNode === true : false;
}
function isSameVNodeType(n12, n2) {
  return n12.type === n2.type && n12.key === n2.key;
}
const normalizeKey = ({ key }) => key != null ? key : null;
const normalizeRef = ({
  ref: ref3,
  ref_key,
  ref_for
}) => {
  if (typeof ref3 === "number") {
    ref3 = "" + ref3;
  }
  return ref3 != null ? isString(ref3) || isRef(ref3) || isFunction(ref3) ? { i: currentRenderingInstance, r: ref3, k: ref_key, f: !!ref_for } : ref3 : null;
};
function createBaseVNode(type, props = null, children = null, patchFlag = 0, dynamicProps = null, shapeFlag = type === Fragment ? 0 : 1, isBlockNode = false, needFullChildrenNormalization = false) {
  const vnode = {
    __v_isVNode: true,
    __v_skip: true,
    type,
    props,
    key: props && normalizeKey(props),
    ref: props && normalizeRef(props),
    scopeId: currentScopeId,
    slotScopeIds: null,
    children,
    component: null,
    suspense: null,
    ssContent: null,
    ssFallback: null,
    dirs: null,
    transition: null,
    el: null,
    anchor: null,
    target: null,
    targetStart: null,
    targetAnchor: null,
    staticCount: 0,
    shapeFlag,
    patchFlag,
    dynamicProps,
    dynamicChildren: null,
    appContext: null,
    ctx: currentRenderingInstance
  };
  if (needFullChildrenNormalization) {
    normalizeChildren(vnode, children);
    if (shapeFlag & 128) {
      type.normalize(vnode);
    }
  } else if (children) {
    vnode.shapeFlag |= isString(children) ? 8 : 16;
  }
  if (isBlockTreeEnabled > 0 && // avoid a block node from tracking itself
  !isBlockNode && // has current parent block
  currentBlock && // presence of a patch flag indicates this node needs patching on updates.
  // component nodes also should always be patched, because even if the
  // component doesn't need to update, it needs to persist the instance on to
  // the next vnode so that it can be properly unmounted later.
  (vnode.patchFlag > 0 || shapeFlag & 6) && // the EVENTS flag is only for hydration and if it is the only flag, the
  // vnode should not be considered dynamic due to handler caching.
  vnode.patchFlag !== 32) {
    currentBlock.push(vnode);
  }
  return vnode;
}
const createVNode = _createVNode;
function _createVNode(type, props = null, children = null, patchFlag = 0, dynamicProps = null, isBlockNode = false) {
  if (!type || type === NULL_DYNAMIC_COMPONENT) {
    type = Comment;
  }
  if (isVNode(type)) {
    const cloned = cloneVNode(
      type,
      props,
      true
      /* mergeRef: true */
    );
    if (children) {
      normalizeChildren(cloned, children);
    }
    if (isBlockTreeEnabled > 0 && !isBlockNode && currentBlock) {
      if (cloned.shapeFlag & 6) {
        currentBlock[currentBlock.indexOf(type)] = cloned;
      } else {
        currentBlock.push(cloned);
      }
    }
    cloned.patchFlag = -2;
    return cloned;
  }
  if (isClassComponent(type)) {
    type = type.__vccOpts;
  }
  if (props) {
    props = guardReactiveProps(props);
    let { class: klass, style } = props;
    if (klass && !isString(klass)) {
      props.class = normalizeClass(klass);
    }
    if (isObject(style)) {
      if (isProxy(style) && !isArray(style)) {
        style = extend({}, style);
      }
      props.style = normalizeStyle(style);
    }
  }
  const shapeFlag = isString(type) ? 1 : isSuspense(type) ? 128 : isTeleport(type) ? 64 : isObject(type) ? 4 : isFunction(type) ? 2 : 0;
  return createBaseVNode(
    type,
    props,
    children,
    patchFlag,
    dynamicProps,
    shapeFlag,
    isBlockNode,
    true
  );
}
function guardReactiveProps(props) {
  if (!props) return null;
  return isProxy(props) || isInternalObject(props) ? extend({}, props) : props;
}
function cloneVNode(vnode, extraProps, mergeRef = false, cloneTransition = false) {
  const { props, ref: ref3, patchFlag, children, transition } = vnode;
  const mergedProps = extraProps ? mergeProps(props || {}, extraProps) : props;
  const cloned = {
    __v_isVNode: true,
    __v_skip: true,
    type: vnode.type,
    props: mergedProps,
    key: mergedProps && normalizeKey(mergedProps),
    ref: extraProps && extraProps.ref ? (
      // #2078 in the case of <component :is="vnode" ref="extra"/>
      // if the vnode itself already has a ref, cloneVNode will need to merge
      // the refs so the single vnode can be set on multiple refs
      mergeRef && ref3 ? isArray(ref3) ? ref3.concat(normalizeRef(extraProps)) : [ref3, normalizeRef(extraProps)] : normalizeRef(extraProps)
    ) : ref3,
    scopeId: vnode.scopeId,
    slotScopeIds: vnode.slotScopeIds,
    children,
    target: vnode.target,
    targetStart: vnode.targetStart,
    targetAnchor: vnode.targetAnchor,
    staticCount: vnode.staticCount,
    shapeFlag: vnode.shapeFlag,
    // if the vnode is cloned with extra props, we can no longer assume its
    // existing patch flag to be reliable and need to add the FULL_PROPS flag.
    // note: preserve flag for fragments since they use the flag for children
    // fast paths only.
    patchFlag: extraProps && vnode.type !== Fragment ? patchFlag === -1 ? 16 : patchFlag | 16 : patchFlag,
    dynamicProps: vnode.dynamicProps,
    dynamicChildren: vnode.dynamicChildren,
    appContext: vnode.appContext,
    dirs: vnode.dirs,
    transition,
    // These should technically only be non-null on mounted VNodes. However,
    // they *should* be copied for kept-alive vnodes. So we just always copy
    // them since them being non-null during a mount doesn't affect the logic as
    // they will simply be overwritten.
    component: vnode.component,
    suspense: vnode.suspense,
    ssContent: vnode.ssContent && cloneVNode(vnode.ssContent),
    ssFallback: vnode.ssFallback && cloneVNode(vnode.ssFallback),
    el: vnode.el,
    anchor: vnode.anchor,
    ctx: vnode.ctx,
    ce: vnode.ce
  };
  if (transition && cloneTransition) {
    setTransitionHooks(
      cloned,
      transition.clone(cloned)
    );
  }
  return cloned;
}
function createTextVNode(text = " ", flag = 0) {
  return createVNode(Text, null, text, flag);
}
function createCommentVNode(text = "", asBlock = false) {
  return asBlock ? (openBlock(), createBlock(Comment, null, text)) : createVNode(Comment, null, text);
}
function normalizeVNode(child) {
  if (child == null || typeof child === "boolean") {
    return createVNode(Comment);
  } else if (isArray(child)) {
    return createVNode(
      Fragment,
      null,
      // #3666, avoid reference pollution when reusing vnode
      child.slice()
    );
  } else if (isVNode(child)) {
    return cloneIfMounted(child);
  } else {
    return createVNode(Text, null, String(child));
  }
}
function cloneIfMounted(child) {
  return child.el === null && child.patchFlag !== -1 || child.memo ? child : cloneVNode(child);
}
function normalizeChildren(vnode, children) {
  let type = 0;
  const { shapeFlag } = vnode;
  if (children == null) {
    children = null;
  } else if (isArray(children)) {
    type = 16;
  } else if (typeof children === "object") {
    if (shapeFlag & (1 | 64)) {
      const slot = children.default;
      if (slot) {
        slot._c && (slot._d = false);
        normalizeChildren(vnode, slot());
        slot._c && (slot._d = true);
      }
      return;
    } else {
      type = 32;
      const slotFlag = children._;
      if (!slotFlag && !isInternalObject(children)) {
        children._ctx = currentRenderingInstance;
      } else if (slotFlag === 3 && currentRenderingInstance) {
        if (currentRenderingInstance.slots._ === 1) {
          children._ = 1;
        } else {
          children._ = 2;
          vnode.patchFlag |= 1024;
        }
      }
    }
  } else if (isFunction(children)) {
    children = { default: children, _ctx: currentRenderingInstance };
    type = 32;
  } else {
    children = String(children);
    if (shapeFlag & 64) {
      type = 16;
      children = [createTextVNode(children)];
    } else {
      type = 8;
    }
  }
  vnode.children = children;
  vnode.shapeFlag |= type;
}
function mergeProps(...args) {
  const ret = {};
  for (let i = 0; i < args.length; i++) {
    const toMerge = args[i];
    for (const key in toMerge) {
      if (key === "class") {
        if (ret.class !== toMerge.class) {
          ret.class = normalizeClass([ret.class, toMerge.class]);
        }
      } else if (key === "style") {
        ret.style = normalizeStyle([ret.style, toMerge.style]);
      } else if (isOn(key)) {
        const existing = ret[key];
        const incoming = toMerge[key];
        if (incoming && existing !== incoming && !(isArray(existing) && existing.includes(incoming))) {
          ret[key] = existing ? [].concat(existing, incoming) : incoming;
        }
      } else if (key !== "") {
        ret[key] = toMerge[key];
      }
    }
  }
  return ret;
}
function invokeVNodeHook(hook, instance, vnode, prevVNode = null) {
  callWithAsyncErrorHandling(hook, instance, 7, [
    vnode,
    prevVNode
  ]);
}
const emptyAppContext = createAppContext();
let uid = 0;
function createComponentInstance(vnode, parent, suspense) {
  const type = vnode.type;
  const appContext = (parent ? parent.appContext : vnode.appContext) || emptyAppContext;
  const instance = {
    uid: uid++,
    vnode,
    type,
    parent,
    appContext,
    root: null,
    // to be immediately set
    next: null,
    subTree: null,
    // will be set synchronously right after creation
    effect: null,
    update: null,
    // will be set synchronously right after creation
    job: null,
    scope: new EffectScope(
      true
      /* detached */
    ),
    render: null,
    proxy: null,
    exposed: null,
    exposeProxy: null,
    withProxy: null,
    provides: parent ? parent.provides : Object.create(appContext.provides),
    ids: parent ? parent.ids : ["", 0, 0],
    accessCache: null,
    renderCache: [],
    // local resolved assets
    components: null,
    directives: null,
    // resolved props and emits options
    propsOptions: normalizePropsOptions(type, appContext),
    emitsOptions: normalizeEmitsOptions(type, appContext),
    // emit
    emit: null,
    // to be set immediately
    emitted: null,
    // props default value
    propsDefaults: EMPTY_OBJ,
    // inheritAttrs
    inheritAttrs: type.inheritAttrs,
    // state
    ctx: EMPTY_OBJ,
    data: EMPTY_OBJ,
    props: EMPTY_OBJ,
    attrs: EMPTY_OBJ,
    slots: EMPTY_OBJ,
    refs: EMPTY_OBJ,
    setupState: EMPTY_OBJ,
    setupContext: null,
    // suspense related
    suspense,
    suspenseId: suspense ? suspense.pendingId : 0,
    asyncDep: null,
    asyncResolved: false,
    // lifecycle hooks
    // not using enums here because it results in computed properties
    isMounted: false,
    isUnmounted: false,
    isDeactivated: false,
    bc: null,
    c: null,
    bm: null,
    m: null,
    bu: null,
    u: null,
    um: null,
    bum: null,
    da: null,
    a: null,
    rtg: null,
    rtc: null,
    ec: null,
    sp: null
  };
  {
    instance.ctx = { _: instance };
  }
  instance.root = parent ? parent.root : instance;
  instance.emit = emit.bind(null, instance);
  if (vnode.ce) {
    vnode.ce(instance);
  }
  return instance;
}
let currentInstance = null;
const getCurrentInstance = () => currentInstance || currentRenderingInstance;
let internalSetCurrentInstance;
let setInSSRSetupState;
{
  const g = getGlobalThis();
  const registerGlobalSetter = (key, setter) => {
    let setters;
    if (!(setters = g[key])) setters = g[key] = [];
    setters.push(setter);
    return (v) => {
      if (setters.length > 1) setters.forEach((set) => set(v));
      else setters[0](v);
    };
  };
  internalSetCurrentInstance = registerGlobalSetter(
    `__VUE_INSTANCE_SETTERS__`,
    (v) => currentInstance = v
  );
  setInSSRSetupState = registerGlobalSetter(
    `__VUE_SSR_SETTERS__`,
    (v) => isInSSRComponentSetup = v
  );
}
const setCurrentInstance = (instance) => {
  const prev = currentInstance;
  internalSetCurrentInstance(instance);
  instance.scope.on();
  return () => {
    instance.scope.off();
    internalSetCurrentInstance(prev);
  };
};
const unsetCurrentInstance = () => {
  currentInstance && currentInstance.scope.off();
  internalSetCurrentInstance(null);
};
function isStatefulComponent(instance) {
  return instance.vnode.shapeFlag & 4;
}
let isInSSRComponentSetup = false;
function setupComponent(instance, isSSR = false, optimized = false) {
  isSSR && setInSSRSetupState(isSSR);
  const { props, children } = instance.vnode;
  const isStateful = isStatefulComponent(instance);
  initProps(instance, props, isStateful, isSSR);
  initSlots(instance, children, optimized || isSSR);
  const setupResult = isStateful ? setupStatefulComponent(instance, isSSR) : void 0;
  isSSR && setInSSRSetupState(false);
  return setupResult;
}
function setupStatefulComponent(instance, isSSR) {
  const Component = instance.type;
  instance.accessCache = /* @__PURE__ */ Object.create(null);
  instance.proxy = new Proxy(instance.ctx, PublicInstanceProxyHandlers);
  const { setup } = Component;
  if (setup) {
    pauseTracking();
    const setupContext = instance.setupContext = setup.length > 1 ? createSetupContext(instance) : null;
    const reset = setCurrentInstance(instance);
    const setupResult = callWithErrorHandling(
      setup,
      instance,
      0,
      [
        instance.props,
        setupContext
      ]
    );
    const isAsyncSetup = isPromise(setupResult);
    resetTracking();
    reset();
    if ((isAsyncSetup || instance.sp) && !isAsyncWrapper(instance)) {
      markAsyncBoundary(instance);
    }
    if (isAsyncSetup) {
      setupResult.then(unsetCurrentInstance, unsetCurrentInstance);
      if (isSSR) {
        return setupResult.then((resolvedResult) => {
          handleSetupResult(instance, resolvedResult);
        }).catch((e) => {
          handleError(e, instance, 0);
        });
      } else {
        instance.asyncDep = setupResult;
      }
    } else {
      handleSetupResult(instance, setupResult);
    }
  } else {
    finishComponentSetup(instance);
  }
}
function handleSetupResult(instance, setupResult, isSSR) {
  if (isFunction(setupResult)) {
    if (instance.type.__ssrInlineRender) {
      instance.ssrRender = setupResult;
    } else {
      instance.render = setupResult;
    }
  } else if (isObject(setupResult)) {
    instance.setupState = proxyRefs(setupResult);
  } else ;
  finishComponentSetup(instance);
}
function finishComponentSetup(instance, isSSR, skipOptions) {
  const Component = instance.type;
  if (!instance.render) {
    instance.render = Component.render || NOOP;
  }
  {
    const reset = setCurrentInstance(instance);
    pauseTracking();
    try {
      applyOptions(instance);
    } finally {
      resetTracking();
      reset();
    }
  }
}
const attrsProxyHandlers = {
  get(target, key) {
    track(target, "get", "");
    return target[key];
  }
};
function createSetupContext(instance) {
  const expose = (exposed) => {
    instance.exposed = exposed || {};
  };
  {
    return {
      attrs: new Proxy(instance.attrs, attrsProxyHandlers),
      slots: instance.slots,
      emit: instance.emit,
      expose
    };
  }
}
function getComponentPublicInstance(instance) {
  if (instance.exposed) {
    return instance.exposeProxy || (instance.exposeProxy = new Proxy(proxyRefs(markRaw(instance.exposed)), {
      get(target, key) {
        if (key in target) {
          return target[key];
        } else if (key in publicPropertiesMap) {
          return publicPropertiesMap[key](instance);
        }
      },
      has(target, key) {
        return key in target || key in publicPropertiesMap;
      }
    }));
  } else {
    return instance.proxy;
  }
}
const classifyRE = /(?:^|[-_])(\w)/g;
const classify = (str) => str.replace(classifyRE, (c) => c.toUpperCase()).replace(/[-_]/g, "");
function getComponentName(Component, includeInferred = true) {
  return isFunction(Component) ? Component.displayName || Component.name : Component.name || includeInferred && Component.__name;
}
function formatComponentName(instance, Component, isRoot = false) {
  let name = getComponentName(Component);
  if (!name && Component.__file) {
    const match = Component.__file.match(/([^/\\]+)\.\w+$/);
    if (match) {
      name = match[1];
    }
  }
  if (!name && instance && instance.parent) {
    const inferFromRegistry = (registry) => {
      for (const key in registry) {
        if (registry[key] === Component) {
          return key;
        }
      }
    };
    name = inferFromRegistry(
      instance.components || instance.parent.type.components
    ) || inferFromRegistry(instance.appContext.components);
  }
  return name ? classify(name) : isRoot ? `App` : `Anonymous`;
}
function isClassComponent(value) {
  return isFunction(value) && "__vccOpts" in value;
}
const computed = (getterOrOptions, debugOptions) => {
  const c = computed$1(getterOrOptions, debugOptions, isInSSRComponentSetup);
  return c;
};
function h(type, propsOrChildren, children) {
  const l = arguments.length;
  if (l === 2) {
    if (isObject(propsOrChildren) && !isArray(propsOrChildren)) {
      if (isVNode(propsOrChildren)) {
        return createVNode(type, null, [propsOrChildren]);
      }
      return createVNode(type, propsOrChildren);
    } else {
      return createVNode(type, null, propsOrChildren);
    }
  } else {
    if (l > 3) {
      children = Array.prototype.slice.call(arguments, 2);
    } else if (l === 3 && isVNode(children)) {
      children = [children];
    }
    return createVNode(type, propsOrChildren, children);
  }
}
const version = "3.5.16";
/**
* @vue/runtime-dom v3.5.16
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
let policy = void 0;
const tt = typeof window !== "undefined" && window.trustedTypes;
if (tt) {
  try {
    policy = /* @__PURE__ */ tt.createPolicy("vue", {
      createHTML: (val) => val
    });
  } catch (e) {
  }
}
const unsafeToTrustedHTML = policy ? (val) => policy.createHTML(val) : (val) => val;
const svgNS = "http://www.w3.org/2000/svg";
const mathmlNS = "http://www.w3.org/1998/Math/MathML";
const doc = typeof document !== "undefined" ? document : null;
const templateContainer = doc && /* @__PURE__ */ doc.createElement("template");
const nodeOps = {
  insert: (child, parent, anchor) => {
    parent.insertBefore(child, anchor || null);
  },
  remove: (child) => {
    const parent = child.parentNode;
    if (parent) {
      parent.removeChild(child);
    }
  },
  createElement: (tag, namespace, is2, props) => {
    const el2 = namespace === "svg" ? doc.createElementNS(svgNS, tag) : namespace === "mathml" ? doc.createElementNS(mathmlNS, tag) : is2 ? doc.createElement(tag, { is: is2 }) : doc.createElement(tag);
    if (tag === "select" && props && props.multiple != null) {
      el2.setAttribute("multiple", props.multiple);
    }
    return el2;
  },
  createText: (text) => doc.createTextNode(text),
  createComment: (text) => doc.createComment(text),
  setText: (node, text) => {
    node.nodeValue = text;
  },
  setElementText: (el2, text) => {
    el2.textContent = text;
  },
  parentNode: (node) => node.parentNode,
  nextSibling: (node) => node.nextSibling,
  querySelector: (selector) => doc.querySelector(selector),
  setScopeId(el2, id2) {
    el2.setAttribute(id2, "");
  },
  // __UNSAFE__
  // Reason: innerHTML.
  // Static content here can only come from compiled templates.
  // As long as the user only uses trusted templates, this is safe.
  insertStaticContent(content, parent, anchor, namespace, start, end) {
    const before = anchor ? anchor.previousSibling : parent.lastChild;
    if (start && (start === end || start.nextSibling)) {
      while (true) {
        parent.insertBefore(start.cloneNode(true), anchor);
        if (start === end || !(start = start.nextSibling)) break;
      }
    } else {
      templateContainer.innerHTML = unsafeToTrustedHTML(
        namespace === "svg" ? `<svg>${content}</svg>` : namespace === "mathml" ? `<math>${content}</math>` : content
      );
      const template = templateContainer.content;
      if (namespace === "svg" || namespace === "mathml") {
        const wrapper = template.firstChild;
        while (wrapper.firstChild) {
          template.appendChild(wrapper.firstChild);
        }
        template.removeChild(wrapper);
      }
      parent.insertBefore(template, anchor);
    }
    return [
      // first
      before ? before.nextSibling : parent.firstChild,
      // last
      anchor ? anchor.previousSibling : parent.lastChild
    ];
  }
};
const TRANSITION = "transition";
const ANIMATION = "animation";
const vtcKey = Symbol("_vtc");
const DOMTransitionPropsValidators = {
  name: String,
  type: String,
  css: {
    type: Boolean,
    default: true
  },
  duration: [String, Number, Object],
  enterFromClass: String,
  enterActiveClass: String,
  enterToClass: String,
  appearFromClass: String,
  appearActiveClass: String,
  appearToClass: String,
  leaveFromClass: String,
  leaveActiveClass: String,
  leaveToClass: String
};
const TransitionPropsValidators = /* @__PURE__ */ extend(
  {},
  BaseTransitionPropsValidators,
  DOMTransitionPropsValidators
);
const decorate$1 = (t) => {
  t.displayName = "Transition";
  t.props = TransitionPropsValidators;
  return t;
};
const Transition = /* @__PURE__ */ decorate$1(
  (props, { slots }) => h(BaseTransition, resolveTransitionProps(props), slots)
);
const callHook = (hook, args = []) => {
  if (isArray(hook)) {
    hook.forEach((h2) => h2(...args));
  } else if (hook) {
    hook(...args);
  }
};
const hasExplicitCallback = (hook) => {
  return hook ? isArray(hook) ? hook.some((h2) => h2.length > 1) : hook.length > 1 : false;
};
function resolveTransitionProps(rawProps) {
  const baseProps = {};
  for (const key in rawProps) {
    if (!(key in DOMTransitionPropsValidators)) {
      baseProps[key] = rawProps[key];
    }
  }
  if (rawProps.css === false) {
    return baseProps;
  }
  const {
    name = "v",
    type,
    duration,
    enterFromClass = `${name}-enter-from`,
    enterActiveClass = `${name}-enter-active`,
    enterToClass = `${name}-enter-to`,
    appearFromClass = enterFromClass,
    appearActiveClass = enterActiveClass,
    appearToClass = enterToClass,
    leaveFromClass = `${name}-leave-from`,
    leaveActiveClass = `${name}-leave-active`,
    leaveToClass = `${name}-leave-to`
  } = rawProps;
  const durations = normalizeDuration(duration);
  const enterDuration = durations && durations[0];
  const leaveDuration = durations && durations[1];
  const {
    onBeforeEnter,
    onEnter,
    onEnterCancelled,
    onLeave,
    onLeaveCancelled,
    onBeforeAppear = onBeforeEnter,
    onAppear = onEnter,
    onAppearCancelled = onEnterCancelled
  } = baseProps;
  const finishEnter = (el2, isAppear, done, isCancelled) => {
    el2._enterCancelled = isCancelled;
    removeTransitionClass(el2, isAppear ? appearToClass : enterToClass);
    removeTransitionClass(el2, isAppear ? appearActiveClass : enterActiveClass);
    done && done();
  };
  const finishLeave = (el2, done) => {
    el2._isLeaving = false;
    removeTransitionClass(el2, leaveFromClass);
    removeTransitionClass(el2, leaveToClass);
    removeTransitionClass(el2, leaveActiveClass);
    done && done();
  };
  const makeEnterHook = (isAppear) => {
    return (el2, done) => {
      const hook = isAppear ? onAppear : onEnter;
      const resolve2 = () => finishEnter(el2, isAppear, done);
      callHook(hook, [el2, resolve2]);
      nextFrame(() => {
        removeTransitionClass(el2, isAppear ? appearFromClass : enterFromClass);
        addTransitionClass(el2, isAppear ? appearToClass : enterToClass);
        if (!hasExplicitCallback(hook)) {
          whenTransitionEnds(el2, type, enterDuration, resolve2);
        }
      });
    };
  };
  return extend(baseProps, {
    onBeforeEnter(el2) {
      callHook(onBeforeEnter, [el2]);
      addTransitionClass(el2, enterFromClass);
      addTransitionClass(el2, enterActiveClass);
    },
    onBeforeAppear(el2) {
      callHook(onBeforeAppear, [el2]);
      addTransitionClass(el2, appearFromClass);
      addTransitionClass(el2, appearActiveClass);
    },
    onEnter: makeEnterHook(false),
    onAppear: makeEnterHook(true),
    onLeave(el2, done) {
      el2._isLeaving = true;
      const resolve2 = () => finishLeave(el2, done);
      addTransitionClass(el2, leaveFromClass);
      if (!el2._enterCancelled) {
        forceReflow();
        addTransitionClass(el2, leaveActiveClass);
      } else {
        addTransitionClass(el2, leaveActiveClass);
        forceReflow();
      }
      nextFrame(() => {
        if (!el2._isLeaving) {
          return;
        }
        removeTransitionClass(el2, leaveFromClass);
        addTransitionClass(el2, leaveToClass);
        if (!hasExplicitCallback(onLeave)) {
          whenTransitionEnds(el2, type, leaveDuration, resolve2);
        }
      });
      callHook(onLeave, [el2, resolve2]);
    },
    onEnterCancelled(el2) {
      finishEnter(el2, false, void 0, true);
      callHook(onEnterCancelled, [el2]);
    },
    onAppearCancelled(el2) {
      finishEnter(el2, true, void 0, true);
      callHook(onAppearCancelled, [el2]);
    },
    onLeaveCancelled(el2) {
      finishLeave(el2);
      callHook(onLeaveCancelled, [el2]);
    }
  });
}
function normalizeDuration(duration) {
  if (duration == null) {
    return null;
  } else if (isObject(duration)) {
    return [NumberOf(duration.enter), NumberOf(duration.leave)];
  } else {
    const n2 = NumberOf(duration);
    return [n2, n2];
  }
}
function NumberOf(val) {
  const res = toNumber(val);
  return res;
}
function addTransitionClass(el2, cls) {
  cls.split(/\s+/).forEach((c) => c && el2.classList.add(c));
  (el2[vtcKey] || (el2[vtcKey] = /* @__PURE__ */ new Set())).add(cls);
}
function removeTransitionClass(el2, cls) {
  cls.split(/\s+/).forEach((c) => c && el2.classList.remove(c));
  const _vtc = el2[vtcKey];
  if (_vtc) {
    _vtc.delete(cls);
    if (!_vtc.size) {
      el2[vtcKey] = void 0;
    }
  }
}
function nextFrame(cb2) {
  requestAnimationFrame(() => {
    requestAnimationFrame(cb2);
  });
}
let endId = 0;
function whenTransitionEnds(el2, expectedType, explicitTimeout, resolve2) {
  const id2 = el2._endId = ++endId;
  const resolveIfNotStale = () => {
    if (id2 === el2._endId) {
      resolve2();
    }
  };
  if (explicitTimeout != null) {
    return setTimeout(resolveIfNotStale, explicitTimeout);
  }
  const { type, timeout, propCount } = getTransitionInfo(el2, expectedType);
  if (!type) {
    return resolve2();
  }
  const endEvent = type + "end";
  let ended = 0;
  const end = () => {
    el2.removeEventListener(endEvent, onEnd);
    resolveIfNotStale();
  };
  const onEnd = (e) => {
    if (e.target === el2 && ++ended >= propCount) {
      end();
    }
  };
  setTimeout(() => {
    if (ended < propCount) {
      end();
    }
  }, timeout + 1);
  el2.addEventListener(endEvent, onEnd);
}
function getTransitionInfo(el2, expectedType) {
  const styles = window.getComputedStyle(el2);
  const getStyleProperties = (key) => (styles[key] || "").split(", ");
  const transitionDelays = getStyleProperties(`${TRANSITION}Delay`);
  const transitionDurations = getStyleProperties(`${TRANSITION}Duration`);
  const transitionTimeout = getTimeout(transitionDelays, transitionDurations);
  const animationDelays = getStyleProperties(`${ANIMATION}Delay`);
  const animationDurations = getStyleProperties(`${ANIMATION}Duration`);
  const animationTimeout = getTimeout(animationDelays, animationDurations);
  let type = null;
  let timeout = 0;
  let propCount = 0;
  if (expectedType === TRANSITION) {
    if (transitionTimeout > 0) {
      type = TRANSITION;
      timeout = transitionTimeout;
      propCount = transitionDurations.length;
    }
  } else if (expectedType === ANIMATION) {
    if (animationTimeout > 0) {
      type = ANIMATION;
      timeout = animationTimeout;
      propCount = animationDurations.length;
    }
  } else {
    timeout = Math.max(transitionTimeout, animationTimeout);
    type = timeout > 0 ? transitionTimeout > animationTimeout ? TRANSITION : ANIMATION : null;
    propCount = type ? type === TRANSITION ? transitionDurations.length : animationDurations.length : 0;
  }
  const hasTransform = type === TRANSITION && /\b(transform|all)(,|$)/.test(
    getStyleProperties(`${TRANSITION}Property`).toString()
  );
  return {
    type,
    timeout,
    propCount,
    hasTransform
  };
}
function getTimeout(delays, durations) {
  while (delays.length < durations.length) {
    delays = delays.concat(delays);
  }
  return Math.max(...durations.map((d, i) => toMs(d) + toMs(delays[i])));
}
function toMs(s) {
  if (s === "auto") return 0;
  return Number(s.slice(0, -1).replace(",", ".")) * 1e3;
}
function forceReflow() {
  return document.body.offsetHeight;
}
function patchClass(el2, value, isSVG) {
  const transitionClasses = el2[vtcKey];
  if (transitionClasses) {
    value = (value ? [value, ...transitionClasses] : [...transitionClasses]).join(" ");
  }
  if (value == null) {
    el2.removeAttribute("class");
  } else if (isSVG) {
    el2.setAttribute("class", value);
  } else {
    el2.className = value;
  }
}
const vShowOriginalDisplay = Symbol("_vod");
const vShowHidden = Symbol("_vsh");
const vShow = {
  beforeMount(el2, { value }, { transition }) {
    el2[vShowOriginalDisplay] = el2.style.display === "none" ? "" : el2.style.display;
    if (transition && value) {
      transition.beforeEnter(el2);
    } else {
      setDisplay(el2, value);
    }
  },
  mounted(el2, { value }, { transition }) {
    if (transition && value) {
      transition.enter(el2);
    }
  },
  updated(el2, { value, oldValue }, { transition }) {
    if (!value === !oldValue) return;
    if (transition) {
      if (value) {
        transition.beforeEnter(el2);
        setDisplay(el2, true);
        transition.enter(el2);
      } else {
        transition.leave(el2, () => {
          setDisplay(el2, false);
        });
      }
    } else {
      setDisplay(el2, value);
    }
  },
  beforeUnmount(el2, { value }) {
    setDisplay(el2, value);
  }
};
function setDisplay(el2, value) {
  el2.style.display = value ? el2[vShowOriginalDisplay] : "none";
  el2[vShowHidden] = !value;
}
const CSS_VAR_TEXT = Symbol("");
const displayRE = /(^|;)\s*display\s*:/;
function patchStyle(el2, prev, next) {
  const style = el2.style;
  const isCssString = isString(next);
  let hasControlledDisplay = false;
  if (next && !isCssString) {
    if (prev) {
      if (!isString(prev)) {
        for (const key in prev) {
          if (next[key] == null) {
            setStyle(style, key, "");
          }
        }
      } else {
        for (const prevStyle of prev.split(";")) {
          const key = prevStyle.slice(0, prevStyle.indexOf(":")).trim();
          if (next[key] == null) {
            setStyle(style, key, "");
          }
        }
      }
    }
    for (const key in next) {
      if (key === "display") {
        hasControlledDisplay = true;
      }
      setStyle(style, key, next[key]);
    }
  } else {
    if (isCssString) {
      if (prev !== next) {
        const cssVarText = style[CSS_VAR_TEXT];
        if (cssVarText) {
          next += ";" + cssVarText;
        }
        style.cssText = next;
        hasControlledDisplay = displayRE.test(next);
      }
    } else if (prev) {
      el2.removeAttribute("style");
    }
  }
  if (vShowOriginalDisplay in el2) {
    el2[vShowOriginalDisplay] = hasControlledDisplay ? style.display : "";
    if (el2[vShowHidden]) {
      style.display = "none";
    }
  }
}
const importantRE = /\s*!important$/;
function setStyle(style, name, val) {
  if (isArray(val)) {
    val.forEach((v) => setStyle(style, name, v));
  } else {
    if (val == null) val = "";
    if (name.startsWith("--")) {
      style.setProperty(name, val);
    } else {
      const prefixed = autoPrefix(style, name);
      if (importantRE.test(val)) {
        style.setProperty(
          hyphenate(prefixed),
          val.replace(importantRE, ""),
          "important"
        );
      } else {
        style[prefixed] = val;
      }
    }
  }
}
const prefixes = ["Webkit", "Moz", "ms"];
const prefixCache = {};
function autoPrefix(style, rawName) {
  const cached = prefixCache[rawName];
  if (cached) {
    return cached;
  }
  let name = camelize(rawName);
  if (name !== "filter" && name in style) {
    return prefixCache[rawName] = name;
  }
  name = capitalize(name);
  for (let i = 0; i < prefixes.length; i++) {
    const prefixed = prefixes[i] + name;
    if (prefixed in style) {
      return prefixCache[rawName] = prefixed;
    }
  }
  return rawName;
}
const xlinkNS = "http://www.w3.org/1999/xlink";
function patchAttr(el2, key, value, isSVG, instance, isBoolean = isSpecialBooleanAttr(key)) {
  if (isSVG && key.startsWith("xlink:")) {
    if (value == null) {
      el2.removeAttributeNS(xlinkNS, key.slice(6, key.length));
    } else {
      el2.setAttributeNS(xlinkNS, key, value);
    }
  } else {
    if (value == null || isBoolean && !includeBooleanAttr(value)) {
      el2.removeAttribute(key);
    } else {
      el2.setAttribute(
        key,
        isBoolean ? "" : isSymbol(value) ? String(value) : value
      );
    }
  }
}
function patchDOMProp(el2, key, value, parentComponent, attrName) {
  if (key === "innerHTML" || key === "textContent") {
    if (value != null) {
      el2[key] = key === "innerHTML" ? unsafeToTrustedHTML(value) : value;
    }
    return;
  }
  const tag = el2.tagName;
  if (key === "value" && tag !== "PROGRESS" && // custom elements may use _value internally
  !tag.includes("-")) {
    const oldValue = tag === "OPTION" ? el2.getAttribute("value") || "" : el2.value;
    const newValue = value == null ? (
      // #11647: value should be set as empty string for null and undefined,
      // but <input type="checkbox"> should be set as 'on'.
      el2.type === "checkbox" ? "on" : ""
    ) : String(value);
    if (oldValue !== newValue || !("_value" in el2)) {
      el2.value = newValue;
    }
    if (value == null) {
      el2.removeAttribute(key);
    }
    el2._value = value;
    return;
  }
  let needRemove = false;
  if (value === "" || value == null) {
    const type = typeof el2[key];
    if (type === "boolean") {
      value = includeBooleanAttr(value);
    } else if (value == null && type === "string") {
      value = "";
      needRemove = true;
    } else if (type === "number") {
      value = 0;
      needRemove = true;
    }
  }
  try {
    el2[key] = value;
  } catch (e) {
  }
  needRemove && el2.removeAttribute(attrName || key);
}
function addEventListener(el2, event, handler, options) {
  el2.addEventListener(event, handler, options);
}
function removeEventListener(el2, event, handler, options) {
  el2.removeEventListener(event, handler, options);
}
const veiKey = Symbol("_vei");
function patchEvent(el2, rawName, prevValue, nextValue, instance = null) {
  const invokers = el2[veiKey] || (el2[veiKey] = {});
  const existingInvoker = invokers[rawName];
  if (nextValue && existingInvoker) {
    existingInvoker.value = nextValue;
  } else {
    const [name, options] = parseName(rawName);
    if (nextValue) {
      const invoker = invokers[rawName] = createInvoker(
        nextValue,
        instance
      );
      addEventListener(el2, name, invoker, options);
    } else if (existingInvoker) {
      removeEventListener(el2, name, existingInvoker, options);
      invokers[rawName] = void 0;
    }
  }
}
const optionsModifierRE = /(?:Once|Passive|Capture)$/;
function parseName(name) {
  let options;
  if (optionsModifierRE.test(name)) {
    options = {};
    let m;
    while (m = name.match(optionsModifierRE)) {
      name = name.slice(0, name.length - m[0].length);
      options[m[0].toLowerCase()] = true;
    }
  }
  const event = name[2] === ":" ? name.slice(3) : hyphenate(name.slice(2));
  return [event, options];
}
let cachedNow = 0;
const p = /* @__PURE__ */ Promise.resolve();
const getNow = () => cachedNow || (p.then(() => cachedNow = 0), cachedNow = Date.now());
function createInvoker(initialValue, instance) {
  const invoker = (e) => {
    if (!e._vts) {
      e._vts = Date.now();
    } else if (e._vts <= invoker.attached) {
      return;
    }
    callWithAsyncErrorHandling(
      patchStopImmediatePropagation(e, invoker.value),
      instance,
      5,
      [e]
    );
  };
  invoker.value = initialValue;
  invoker.attached = getNow();
  return invoker;
}
function patchStopImmediatePropagation(e, value) {
  if (isArray(value)) {
    const originalStop = e.stopImmediatePropagation;
    e.stopImmediatePropagation = () => {
      originalStop.call(e);
      e._stopped = true;
    };
    return value.map(
      (fn2) => (e2) => !e2._stopped && fn2 && fn2(e2)
    );
  } else {
    return value;
  }
}
const isNativeOn = (key) => key.charCodeAt(0) === 111 && key.charCodeAt(1) === 110 && // lowercase letter
key.charCodeAt(2) > 96 && key.charCodeAt(2) < 123;
const patchProp = (el2, key, prevValue, nextValue, namespace, parentComponent) => {
  const isSVG = namespace === "svg";
  if (key === "class") {
    patchClass(el2, nextValue, isSVG);
  } else if (key === "style") {
    patchStyle(el2, prevValue, nextValue);
  } else if (isOn(key)) {
    if (!isModelListener(key)) {
      patchEvent(el2, key, prevValue, nextValue, parentComponent);
    }
  } else if (key[0] === "." ? (key = key.slice(1), true) : key[0] === "^" ? (key = key.slice(1), false) : shouldSetAsProp(el2, key, nextValue, isSVG)) {
    patchDOMProp(el2, key, nextValue);
    if (!el2.tagName.includes("-") && (key === "value" || key === "checked" || key === "selected")) {
      patchAttr(el2, key, nextValue, isSVG, parentComponent, key !== "value");
    }
  } else if (
    // #11081 force set props for possible async custom element
    el2._isVueCE && (/[A-Z]/.test(key) || !isString(nextValue))
  ) {
    patchDOMProp(el2, camelize(key), nextValue, parentComponent, key);
  } else {
    if (key === "true-value") {
      el2._trueValue = nextValue;
    } else if (key === "false-value") {
      el2._falseValue = nextValue;
    }
    patchAttr(el2, key, nextValue, isSVG);
  }
};
function shouldSetAsProp(el2, key, value, isSVG) {
  if (isSVG) {
    if (key === "innerHTML" || key === "textContent") {
      return true;
    }
    if (key in el2 && isNativeOn(key) && isFunction(value)) {
      return true;
    }
    return false;
  }
  if (key === "spellcheck" || key === "draggable" || key === "translate" || key === "autocorrect") {
    return false;
  }
  if (key === "form") {
    return false;
  }
  if (key === "list" && el2.tagName === "INPUT") {
    return false;
  }
  if (key === "type" && el2.tagName === "TEXTAREA") {
    return false;
  }
  if (key === "width" || key === "height") {
    const tag = el2.tagName;
    if (tag === "IMG" || tag === "VIDEO" || tag === "CANVAS" || tag === "SOURCE") {
      return false;
    }
  }
  if (isNativeOn(key) && isString(value)) {
    return false;
  }
  return key in el2;
}
const getModelAssigner = (vnode) => {
  const fn2 = vnode.props["onUpdate:modelValue"] || false;
  return isArray(fn2) ? (value) => invokeArrayFns(fn2, value) : fn2;
};
function onCompositionStart(e) {
  e.target.composing = true;
}
function onCompositionEnd(e) {
  const target = e.target;
  if (target.composing) {
    target.composing = false;
    target.dispatchEvent(new Event("input"));
  }
}
const assignKey = Symbol("_assign");
const vModelText = {
  created(el2, { modifiers: { lazy, trim, number } }, vnode) {
    el2[assignKey] = getModelAssigner(vnode);
    const castToNumber = number || vnode.props && vnode.props.type === "number";
    addEventListener(el2, lazy ? "change" : "input", (e) => {
      if (e.target.composing) return;
      let domValue = el2.value;
      if (trim) {
        domValue = domValue.trim();
      }
      if (castToNumber) {
        domValue = looseToNumber(domValue);
      }
      el2[assignKey](domValue);
    });
    if (trim) {
      addEventListener(el2, "change", () => {
        el2.value = el2.value.trim();
      });
    }
    if (!lazy) {
      addEventListener(el2, "compositionstart", onCompositionStart);
      addEventListener(el2, "compositionend", onCompositionEnd);
      addEventListener(el2, "change", onCompositionEnd);
    }
  },
  // set value on mounted so it's after min/max for type="range"
  mounted(el2, { value }) {
    el2.value = value == null ? "" : value;
  },
  beforeUpdate(el2, { value, oldValue, modifiers: { lazy, trim, number } }, vnode) {
    el2[assignKey] = getModelAssigner(vnode);
    if (el2.composing) return;
    const elValue = (number || el2.type === "number") && !/^0\d/.test(el2.value) ? looseToNumber(el2.value) : el2.value;
    const newValue = value == null ? "" : value;
    if (elValue === newValue) {
      return;
    }
    if (document.activeElement === el2 && el2.type !== "range") {
      if (lazy && value === oldValue) {
        return;
      }
      if (trim && el2.value.trim() === newValue) {
        return;
      }
    }
    el2.value = newValue;
  }
};
const vModelCheckbox = {
  // #4096 array checkboxes need to be deep traversed
  deep: true,
  created(el2, _, vnode) {
    el2[assignKey] = getModelAssigner(vnode);
    addEventListener(el2, "change", () => {
      const modelValue = el2._modelValue;
      const elementValue = getValue(el2);
      const checked = el2.checked;
      const assign = el2[assignKey];
      if (isArray(modelValue)) {
        const index = looseIndexOf(modelValue, elementValue);
        const found = index !== -1;
        if (checked && !found) {
          assign(modelValue.concat(elementValue));
        } else if (!checked && found) {
          const filtered = [...modelValue];
          filtered.splice(index, 1);
          assign(filtered);
        }
      } else if (isSet(modelValue)) {
        const cloned = new Set(modelValue);
        if (checked) {
          cloned.add(elementValue);
        } else {
          cloned.delete(elementValue);
        }
        assign(cloned);
      } else {
        assign(getCheckboxValue(el2, checked));
      }
    });
  },
  // set initial checked on mount to wait for true-value/false-value
  mounted: setChecked,
  beforeUpdate(el2, binding, vnode) {
    el2[assignKey] = getModelAssigner(vnode);
    setChecked(el2, binding, vnode);
  }
};
function setChecked(el2, { value, oldValue }, vnode) {
  el2._modelValue = value;
  let checked;
  if (isArray(value)) {
    checked = looseIndexOf(value, vnode.props.value) > -1;
  } else if (isSet(value)) {
    checked = value.has(vnode.props.value);
  } else {
    if (value === oldValue) return;
    checked = looseEqual(value, getCheckboxValue(el2, true));
  }
  if (el2.checked !== checked) {
    el2.checked = checked;
  }
}
const vModelSelect = {
  // <select multiple> value need to be deep traversed
  deep: true,
  created(el2, { value, modifiers: { number } }, vnode) {
    const isSetModel = isSet(value);
    addEventListener(el2, "change", () => {
      const selectedVal = Array.prototype.filter.call(el2.options, (o) => o.selected).map(
        (o) => number ? looseToNumber(getValue(o)) : getValue(o)
      );
      el2[assignKey](
        el2.multiple ? isSetModel ? new Set(selectedVal) : selectedVal : selectedVal[0]
      );
      el2._assigning = true;
      nextTick(() => {
        el2._assigning = false;
      });
    });
    el2[assignKey] = getModelAssigner(vnode);
  },
  // set value in mounted & updated because <select> relies on its children
  // <option>s.
  mounted(el2, { value }) {
    setSelected(el2, value);
  },
  beforeUpdate(el2, _binding, vnode) {
    el2[assignKey] = getModelAssigner(vnode);
  },
  updated(el2, { value }) {
    if (!el2._assigning) {
      setSelected(el2, value);
    }
  }
};
function setSelected(el2, value) {
  const isMultiple = el2.multiple;
  const isArrayValue = isArray(value);
  if (isMultiple && !isArrayValue && !isSet(value)) {
    return;
  }
  for (let i = 0, l = el2.options.length; i < l; i++) {
    const option = el2.options[i];
    const optionValue = getValue(option);
    if (isMultiple) {
      if (isArrayValue) {
        const optionType = typeof optionValue;
        if (optionType === "string" || optionType === "number") {
          option.selected = value.some((v) => String(v) === String(optionValue));
        } else {
          option.selected = looseIndexOf(value, optionValue) > -1;
        }
      } else {
        option.selected = value.has(optionValue);
      }
    } else if (looseEqual(getValue(option), value)) {
      if (el2.selectedIndex !== i) el2.selectedIndex = i;
      return;
    }
  }
  if (!isMultiple && el2.selectedIndex !== -1) {
    el2.selectedIndex = -1;
  }
}
function getValue(el2) {
  return "_value" in el2 ? el2._value : el2.value;
}
function getCheckboxValue(el2, checked) {
  const key = checked ? "_trueValue" : "_falseValue";
  return key in el2 ? el2[key] : checked;
}
const systemModifiers = ["ctrl", "shift", "alt", "meta"];
const modifierGuards = {
  stop: (e) => e.stopPropagation(),
  prevent: (e) => e.preventDefault(),
  self: (e) => e.target !== e.currentTarget,
  ctrl: (e) => !e.ctrlKey,
  shift: (e) => !e.shiftKey,
  alt: (e) => !e.altKey,
  meta: (e) => !e.metaKey,
  left: (e) => "button" in e && e.button !== 0,
  middle: (e) => "button" in e && e.button !== 1,
  right: (e) => "button" in e && e.button !== 2,
  exact: (e, modifiers) => systemModifiers.some((m) => e[`${m}Key`] && !modifiers.includes(m))
};
const withModifiers = (fn2, modifiers) => {
  const cache = fn2._withMods || (fn2._withMods = {});
  const cacheKey = modifiers.join(".");
  return cache[cacheKey] || (cache[cacheKey] = (event, ...args) => {
    for (let i = 0; i < modifiers.length; i++) {
      const guard = modifierGuards[modifiers[i]];
      if (guard && guard(event, modifiers)) return;
    }
    return fn2(event, ...args);
  });
};
const rendererOptions = /* @__PURE__ */ extend({ patchProp }, nodeOps);
let renderer;
function ensureRenderer() {
  return renderer || (renderer = createRenderer(rendererOptions));
}
const render = (...args) => {
  ensureRenderer().render(...args);
};
const createApp = (...args) => {
  const app = ensureRenderer().createApp(...args);
  const { mount } = app;
  app.mount = (containerOrSelector) => {
    const container = normalizeContainer(containerOrSelector);
    if (!container) return;
    const component = app._component;
    if (!isFunction(component) && !component.render && !component.template) {
      component.template = container.innerHTML;
    }
    if (container.nodeType === 1) {
      container.textContent = "";
    }
    const proxy = mount(container, false, resolveRootNamespace(container));
    if (container instanceof Element) {
      container.removeAttribute("v-cloak");
      container.setAttribute("data-v-app", "");
    }
    return proxy;
  };
  return app;
};
function resolveRootNamespace(container) {
  if (container instanceof SVGElement) {
    return "svg";
  }
  if (typeof MathMLElement === "function" && container instanceof MathMLElement) {
    return "mathml";
  }
}
function normalizeContainer(container) {
  if (isString(container)) {
    const res = document.querySelector(container);
    return res;
  }
  return container;
}
function be(n2) {
  this.content = n2;
}
be.prototype = {
  constructor: be,
  find: function(n2) {
    for (var e = 0; e < this.content.length; e += 2)
      if (this.content[e] === n2) return e;
    return -1;
  },
  // :: (string) → ?any
  // Retrieve the value stored under `key`, or return undefined when
  // no such key exists.
  get: function(n2) {
    var e = this.find(n2);
    return e == -1 ? void 0 : this.content[e + 1];
  },
  // :: (string, any, ?string) → OrderedMap
  // Create a new map by replacing the value of `key` with a new
  // value, or adding a binding to the end of the map. If `newKey` is
  // given, the key of the binding will be replaced with that key.
  update: function(n2, e, t) {
    var r = t && t != n2 ? this.remove(t) : this, i = r.find(n2), s = r.content.slice();
    return i == -1 ? s.push(t || n2, e) : (s[i + 1] = e, t && (s[i] = t)), new be(s);
  },
  // :: (string) → OrderedMap
  // Return a map with the given key removed, if it existed.
  remove: function(n2) {
    var e = this.find(n2);
    if (e == -1) return this;
    var t = this.content.slice();
    return t.splice(e, 2), new be(t);
  },
  // :: (string, any) → OrderedMap
  // Add a new key to the start of the map.
  addToStart: function(n2, e) {
    return new be([n2, e].concat(this.remove(n2).content));
  },
  // :: (string, any) → OrderedMap
  // Add a new key to the end of the map.
  addToEnd: function(n2, e) {
    var t = this.remove(n2).content.slice();
    return t.push(n2, e), new be(t);
  },
  // :: (string, string, any) → OrderedMap
  // Add a key after the given key. If `place` is not found, the new
  // key is added to the end.
  addBefore: function(n2, e, t) {
    var r = this.remove(e), i = r.content.slice(), s = r.find(n2);
    return i.splice(s == -1 ? i.length : s, 0, e, t), new be(i);
  },
  // :: ((key: string, value: any))
  // Call the given function for each key/value pair in the map, in
  // order.
  forEach: function(n2) {
    for (var e = 0; e < this.content.length; e += 2)
      n2(this.content[e], this.content[e + 1]);
  },
  // :: (union<Object, OrderedMap>) → OrderedMap
  // Create a new map by prepending the keys in this map that don't
  // appear in `map` before the keys in `map`.
  prepend: function(n2) {
    return n2 = be.from(n2), n2.size ? new be(n2.content.concat(this.subtract(n2).content)) : this;
  },
  // :: (union<Object, OrderedMap>) → OrderedMap
  // Create a new map by appending the keys in this map that don't
  // appear in `map` after the keys in `map`.
  append: function(n2) {
    return n2 = be.from(n2), n2.size ? new be(this.subtract(n2).content.concat(n2.content)) : this;
  },
  // :: (union<Object, OrderedMap>) → OrderedMap
  // Create a map containing all the keys in this map that don't
  // appear in `map`.
  subtract: function(n2) {
    var e = this;
    n2 = be.from(n2);
    for (var t = 0; t < n2.content.length; t += 2)
      e = e.remove(n2.content[t]);
    return e;
  },
  // :: () → Object
  // Turn ordered map into a plain object.
  toObject: function() {
    var n2 = {};
    return this.forEach(function(e, t) {
      n2[e] = t;
    }), n2;
  },
  // :: number
  // The amount of keys in this map.
  get size() {
    return this.content.length >> 1;
  }
};
be.from = function(n2) {
  if (n2 instanceof be) return n2;
  var e = [];
  if (n2) for (var t in n2) e.push(t, n2[t]);
  return new be(e);
};
function Pu(n2, e, t) {
  for (let r = 0; ; r++) {
    if (r == n2.childCount || r == e.childCount)
      return n2.childCount == e.childCount ? null : t;
    let i = n2.child(r), s = e.child(r);
    if (i == s) {
      t += i.nodeSize;
      continue;
    }
    if (!i.sameMarkup(s))
      return t;
    if (i.isText && i.text != s.text) {
      for (let o = 0; i.text[o] == s.text[o]; o++)
        t++;
      return t;
    }
    if (i.content.size || s.content.size) {
      let o = Pu(i.content, s.content, t + 1);
      if (o != null)
        return o;
    }
    t += i.nodeSize;
  }
}
function Bu(n2, e, t, r) {
  for (let i = n2.childCount, s = e.childCount; ; ) {
    if (i == 0 || s == 0)
      return i == s ? null : { a: t, b: r };
    let o = n2.child(--i), l = e.child(--s), a = o.nodeSize;
    if (o == l) {
      t -= a, r -= a;
      continue;
    }
    if (!o.sameMarkup(l))
      return { a: t, b: r };
    if (o.isText && o.text != l.text) {
      let c = 0, u = Math.min(o.text.length, l.text.length);
      for (; c < u && o.text[o.text.length - c - 1] == l.text[l.text.length - c - 1]; )
        c++, t--, r--;
      return { a: t, b: r };
    }
    if (o.content.size || l.content.size) {
      let c = Bu(o.content, l.content, t - 1, r - 1);
      if (c)
        return c;
    }
    t -= a, r -= a;
  }
}
class A {
  /**
  @internal
  */
  constructor(e, t) {
    if (this.content = e, this.size = t || 0, t == null)
      for (let r = 0; r < e.length; r++)
        this.size += e[r].nodeSize;
  }
  /**
  Invoke a callback for all descendant nodes between the given two
  positions (relative to start of this fragment). Doesn't descend
  into a node when the callback returns `false`.
  */
  nodesBetween(e, t, r, i = 0, s) {
    for (let o = 0, l = 0; l < t; o++) {
      let a = this.content[o], c = l + a.nodeSize;
      if (c > e && r(a, i + l, s || null, o) !== false && a.content.size) {
        let u = l + 1;
        a.nodesBetween(Math.max(0, e - u), Math.min(a.content.size, t - u), r, i + u);
      }
      l = c;
    }
  }
  /**
  Call the given callback for every descendant node. `pos` will be
  relative to the start of the fragment. The callback may return
  `false` to prevent traversal of a given node's children.
  */
  descendants(e) {
    this.nodesBetween(0, this.size, e);
  }
  /**
  Extract the text between `from` and `to`. See the same method on
  [`Node`](https://prosemirror.net/docs/ref/#model.Node.textBetween).
  */
  textBetween(e, t, r, i) {
    let s = "", o = true;
    return this.nodesBetween(e, t, (l, a) => {
      let c = l.isText ? l.text.slice(Math.max(e, a) - a, t - a) : l.isLeaf ? i ? typeof i == "function" ? i(l) : i : l.type.spec.leafText ? l.type.spec.leafText(l) : "" : "";
      l.isBlock && (l.isLeaf && c || l.isTextblock) && r && (o ? o = false : s += r), s += c;
    }, 0), s;
  }
  /**
  Create a new fragment containing the combined content of this
  fragment and the other.
  */
  append(e) {
    if (!e.size)
      return this;
    if (!this.size)
      return e;
    let t = this.lastChild, r = e.firstChild, i = this.content.slice(), s = 0;
    for (t.isText && t.sameMarkup(r) && (i[i.length - 1] = t.withText(t.text + r.text), s = 1); s < e.content.length; s++)
      i.push(e.content[s]);
    return new A(i, this.size + e.size);
  }
  /**
  Cut out the sub-fragment between the two given positions.
  */
  cut(e, t = this.size) {
    if (e == 0 && t == this.size)
      return this;
    let r = [], i = 0;
    if (t > e)
      for (let s = 0, o = 0; o < t; s++) {
        let l = this.content[s], a = o + l.nodeSize;
        a > e && ((o < e || a > t) && (l.isText ? l = l.cut(Math.max(0, e - o), Math.min(l.text.length, t - o)) : l = l.cut(Math.max(0, e - o - 1), Math.min(l.content.size, t - o - 1))), r.push(l), i += l.nodeSize), o = a;
      }
    return new A(r, i);
  }
  /**
  @internal
  */
  cutByIndex(e, t) {
    return e == t ? A.empty : e == 0 && t == this.content.length ? this : new A(this.content.slice(e, t));
  }
  /**
  Create a new fragment in which the node at the given index is
  replaced by the given node.
  */
  replaceChild(e, t) {
    let r = this.content[e];
    if (r == t)
      return this;
    let i = this.content.slice(), s = this.size + t.nodeSize - r.nodeSize;
    return i[e] = t, new A(i, s);
  }
  /**
  Create a new fragment by prepending the given node to this
  fragment.
  */
  addToStart(e) {
    return new A([e].concat(this.content), this.size + e.nodeSize);
  }
  /**
  Create a new fragment by appending the given node to this
  fragment.
  */
  addToEnd(e) {
    return new A(this.content.concat(e), this.size + e.nodeSize);
  }
  /**
  Compare this fragment to another one.
  */
  eq(e) {
    if (this.content.length != e.content.length)
      return false;
    for (let t = 0; t < this.content.length; t++)
      if (!this.content[t].eq(e.content[t]))
        return false;
    return true;
  }
  /**
  The first child of the fragment, or `null` if it is empty.
  */
  get firstChild() {
    return this.content.length ? this.content[0] : null;
  }
  /**
  The last child of the fragment, or `null` if it is empty.
  */
  get lastChild() {
    return this.content.length ? this.content[this.content.length - 1] : null;
  }
  /**
  The number of child nodes in this fragment.
  */
  get childCount() {
    return this.content.length;
  }
  /**
  Get the child node at the given index. Raise an error when the
  index is out of range.
  */
  child(e) {
    let t = this.content[e];
    if (!t)
      throw new RangeError("Index " + e + " out of range for " + this);
    return t;
  }
  /**
  Get the child node at the given index, if it exists.
  */
  maybeChild(e) {
    return this.content[e] || null;
  }
  /**
  Call `f` for every child node, passing the node, its offset
  into this parent node, and its index.
  */
  forEach(e) {
    for (let t = 0, r = 0; t < this.content.length; t++) {
      let i = this.content[t];
      e(i, r, t), r += i.nodeSize;
    }
  }
  /**
  Find the first position at which this fragment and another
  fragment differ, or `null` if they are the same.
  */
  findDiffStart(e, t = 0) {
    return Pu(this, e, t);
  }
  /**
  Find the first position, searching from the end, at which this
  fragment and the given fragment differ, or `null` if they are
  the same. Since this position will not be the same in both
  nodes, an object with two separate positions is returned.
  */
  findDiffEnd(e, t = this.size, r = e.size) {
    return Bu(this, e, t, r);
  }
  /**
  Find the index and inner offset corresponding to a given relative
  position in this fragment. The result object will be reused
  (overwritten) the next time the function is called. @internal
  */
  findIndex(e, t = -1) {
    if (e == 0)
      return wi(0, e);
    if (e == this.size)
      return wi(this.content.length, e);
    if (e > this.size || e < 0)
      throw new RangeError(`Position ${e} outside of fragment (${this})`);
    for (let r = 0, i = 0; ; r++) {
      let s = this.child(r), o = i + s.nodeSize;
      if (o >= e)
        return o == e || t > 0 ? wi(r + 1, o) : wi(r, i);
      i = o;
    }
  }
  /**
  Return a debugging string that describes this fragment.
  */
  toString() {
    return "<" + this.toStringInner() + ">";
  }
  /**
  @internal
  */
  toStringInner() {
    return this.content.join(", ");
  }
  /**
  Create a JSON-serializeable representation of this fragment.
  */
  toJSON() {
    return this.content.length ? this.content.map((e) => e.toJSON()) : null;
  }
  /**
  Deserialize a fragment from its JSON representation.
  */
  static fromJSON(e, t) {
    if (!t)
      return A.empty;
    if (!Array.isArray(t))
      throw new RangeError("Invalid input for Fragment.fromJSON");
    return new A(t.map(e.nodeFromJSON));
  }
  /**
  Build a fragment from an array of nodes. Ensures that adjacent
  text nodes with the same marks are joined together.
  */
  static fromArray(e) {
    if (!e.length)
      return A.empty;
    let t, r = 0;
    for (let i = 0; i < e.length; i++) {
      let s = e[i];
      r += s.nodeSize, i && s.isText && e[i - 1].sameMarkup(s) ? (t || (t = e.slice(0, i)), t[t.length - 1] = s.withText(t[t.length - 1].text + s.text)) : t && t.push(s);
    }
    return new A(t || e, r);
  }
  /**
  Create a fragment from something that can be interpreted as a
  set of nodes. For `null`, it returns the empty fragment. For a
  fragment, the fragment itself. For a node or array of nodes, a
  fragment containing those nodes.
  */
  static from(e) {
    if (!e)
      return A.empty;
    if (e instanceof A)
      return e;
    if (Array.isArray(e))
      return this.fromArray(e);
    if (e.attrs)
      return new A([e], e.nodeSize);
    throw new RangeError("Can not convert " + e + " to a Fragment" + (e.nodesBetween ? " (looks like multiple versions of prosemirror-model were loaded)" : ""));
  }
}
A.empty = new A([], 0);
const lo = { index: 0, offset: 0 };
function wi(n2, e) {
  return lo.index = n2, lo.offset = e, lo;
}
function qi(n2, e) {
  if (n2 === e)
    return true;
  if (!(n2 && typeof n2 == "object") || !(e && typeof e == "object"))
    return false;
  let t = Array.isArray(n2);
  if (Array.isArray(e) != t)
    return false;
  if (t) {
    if (n2.length != e.length)
      return false;
    for (let r = 0; r < n2.length; r++)
      if (!qi(n2[r], e[r]))
        return false;
  } else {
    for (let r in n2)
      if (!(r in e) || !qi(n2[r], e[r]))
        return false;
    for (let r in e)
      if (!(r in n2))
        return false;
  }
  return true;
}
let te = class Vo {
  /**
  @internal
  */
  constructor(e, t) {
    this.type = e, this.attrs = t;
  }
  /**
  Given a set of marks, create a new set which contains this one as
  well, in the right position. If this mark is already in the set,
  the set itself is returned. If any marks that are set to be
  [exclusive](https://prosemirror.net/docs/ref/#model.MarkSpec.excludes) with this mark are present,
  those are replaced by this one.
  */
  addToSet(e) {
    let t, r = false;
    for (let i = 0; i < e.length; i++) {
      let s = e[i];
      if (this.eq(s))
        return e;
      if (this.type.excludes(s.type))
        t || (t = e.slice(0, i));
      else {
        if (s.type.excludes(this.type))
          return e;
        !r && s.type.rank > this.type.rank && (t || (t = e.slice(0, i)), t.push(this), r = true), t && t.push(s);
      }
    }
    return t || (t = e.slice()), r || t.push(this), t;
  }
  /**
  Remove this mark from the given set, returning a new set. If this
  mark is not in the set, the set itself is returned.
  */
  removeFromSet(e) {
    for (let t = 0; t < e.length; t++)
      if (this.eq(e[t]))
        return e.slice(0, t).concat(e.slice(t + 1));
    return e;
  }
  /**
  Test whether this mark is in the given set of marks.
  */
  isInSet(e) {
    for (let t = 0; t < e.length; t++)
      if (this.eq(e[t]))
        return true;
    return false;
  }
  /**
  Test whether this mark has the same type and attributes as
  another mark.
  */
  eq(e) {
    return this == e || this.type == e.type && qi(this.attrs, e.attrs);
  }
  /**
  Convert this mark to a JSON-serializeable representation.
  */
  toJSON() {
    let e = { type: this.type.name };
    for (let t in this.attrs) {
      e.attrs = this.attrs;
      break;
    }
    return e;
  }
  /**
  Deserialize a mark from JSON.
  */
  static fromJSON(e, t) {
    if (!t)
      throw new RangeError("Invalid input for Mark.fromJSON");
    let r = e.marks[t.type];
    if (!r)
      throw new RangeError(`There is no mark type ${t.type} in this schema`);
    let i = r.create(t.attrs);
    return r.checkAttrs(i.attrs), i;
  }
  /**
  Test whether two sets of marks are identical.
  */
  static sameSet(e, t) {
    if (e == t)
      return true;
    if (e.length != t.length)
      return false;
    for (let r = 0; r < e.length; r++)
      if (!e[r].eq(t[r]))
        return false;
    return true;
  }
  /**
  Create a properly sorted mark set from null, a single mark, or an
  unsorted array of marks.
  */
  static setFrom(e) {
    if (!e || Array.isArray(e) && e.length == 0)
      return Vo.none;
    if (e instanceof Vo)
      return [e];
    let t = e.slice();
    return t.sort((r, i) => r.type.rank - i.type.rank), t;
  }
};
te.none = [];
class Ji extends Error {
}
class O {
  /**
  Create a slice. When specifying a non-zero open depth, you must
  make sure that there are nodes of at least that depth at the
  appropriate side of the fragment—i.e. if the fragment is an
  empty paragraph node, `openStart` and `openEnd` can't be greater
  than 1.
  
  It is not necessary for the content of open nodes to conform to
  the schema's content constraints, though it should be a valid
  start/end/middle for such a node, depending on which sides are
  open.
  */
  constructor(e, t, r) {
    this.content = e, this.openStart = t, this.openEnd = r;
  }
  /**
  The size this slice would add when inserted into a document.
  */
  get size() {
    return this.content.size - this.openStart - this.openEnd;
  }
  /**
  @internal
  */
  insertAt(e, t) {
    let r = Fu(this.content, e + this.openStart, t);
    return r && new O(r, this.openStart, this.openEnd);
  }
  /**
  @internal
  */
  removeBetween(e, t) {
    return new O(Hu(this.content, e + this.openStart, t + this.openStart), this.openStart, this.openEnd);
  }
  /**
  Tests whether this slice is equal to another slice.
  */
  eq(e) {
    return this.content.eq(e.content) && this.openStart == e.openStart && this.openEnd == e.openEnd;
  }
  /**
  @internal
  */
  toString() {
    return this.content + "(" + this.openStart + "," + this.openEnd + ")";
  }
  /**
  Convert a slice to a JSON-serializable representation.
  */
  toJSON() {
    if (!this.content.size)
      return null;
    let e = { content: this.content.toJSON() };
    return this.openStart > 0 && (e.openStart = this.openStart), this.openEnd > 0 && (e.openEnd = this.openEnd), e;
  }
  /**
  Deserialize a slice from its JSON representation.
  */
  static fromJSON(e, t) {
    if (!t)
      return O.empty;
    let r = t.openStart || 0, i = t.openEnd || 0;
    if (typeof r != "number" || typeof i != "number")
      throw new RangeError("Invalid input for Slice.fromJSON");
    return new O(A.fromJSON(e, t.content), r, i);
  }
  /**
  Create a slice from a fragment by taking the maximum possible
  open value on both side of the fragment.
  */
  static maxOpen(e, t = true) {
    let r = 0, i = 0;
    for (let s = e.firstChild; s && !s.isLeaf && (t || !s.type.spec.isolating); s = s.firstChild)
      r++;
    for (let s = e.lastChild; s && !s.isLeaf && (t || !s.type.spec.isolating); s = s.lastChild)
      i++;
    return new O(e, r, i);
  }
}
O.empty = new O(A.empty, 0, 0);
function Hu(n2, e, t) {
  let { index: r, offset: i } = n2.findIndex(e), s = n2.maybeChild(r), { index: o, offset: l } = n2.findIndex(t);
  if (i == e || s.isText) {
    if (l != t && !n2.child(o).isText)
      throw new RangeError("Removing non-flat range");
    return n2.cut(0, e).append(n2.cut(t));
  }
  if (r != o)
    throw new RangeError("Removing non-flat range");
  return n2.replaceChild(r, s.copy(Hu(s.content, e - i - 1, t - i - 1)));
}
function Fu(n2, e, t, r) {
  let { index: i, offset: s } = n2.findIndex(e), o = n2.maybeChild(i);
  if (s == e || o.isText)
    return n2.cut(0, e).append(t).append(n2.cut(e));
  let l = Fu(o.content, e - s - 1, t);
  return l && n2.replaceChild(i, o.copy(l));
}
function $h(n2, e, t) {
  if (t.openStart > n2.depth)
    throw new Ji("Inserted content deeper than insertion position");
  if (n2.depth - t.openStart != e.depth - t.openEnd)
    throw new Ji("Inconsistent open depths");
  return zu(n2, e, t, 0);
}
function zu(n2, e, t, r) {
  let i = n2.index(r), s = n2.node(r);
  if (i == e.index(r) && r < n2.depth - t.openStart) {
    let o = zu(n2, e, t, r + 1);
    return s.copy(s.content.replaceChild(i, o));
  } else if (t.content.size)
    if (!t.openStart && !t.openEnd && n2.depth == r && e.depth == r) {
      let o = n2.parent, l = o.content;
      return xn(o, l.cut(0, n2.parentOffset).append(t.content).append(l.cut(e.parentOffset)));
    } else {
      let { start: o, end: l } = _h(t, n2);
      return xn(s, $u(n2, o, l, e, r));
    }
  else return xn(s, Gi(n2, e, r));
}
function Vu(n2, e) {
  if (!e.type.compatibleContent(n2.type))
    throw new Ji("Cannot join " + e.type.name + " onto " + n2.type.name);
}
function $o(n2, e, t) {
  let r = n2.node(t);
  return Vu(r, e.node(t)), r;
}
function Cn(n2, e) {
  let t = e.length - 1;
  t >= 0 && n2.isText && n2.sameMarkup(e[t]) ? e[t] = n2.withText(e[t].text + n2.text) : e.push(n2);
}
function Tr(n2, e, t, r) {
  let i = (e || n2).node(t), s = 0, o = e ? e.index(t) : i.childCount;
  n2 && (s = n2.index(t), n2.depth > t ? s++ : n2.textOffset && (Cn(n2.nodeAfter, r), s++));
  for (let l = s; l < o; l++)
    Cn(i.child(l), r);
  e && e.depth == t && e.textOffset && Cn(e.nodeBefore, r);
}
function xn(n2, e) {
  return n2.type.checkContent(e), n2.copy(e);
}
function $u(n2, e, t, r, i) {
  let s = n2.depth > i && $o(n2, e, i + 1), o = r.depth > i && $o(t, r, i + 1), l = [];
  return Tr(null, n2, i, l), s && o && e.index(i) == t.index(i) ? (Vu(s, o), Cn(xn(s, $u(n2, e, t, r, i + 1)), l)) : (s && Cn(xn(s, Gi(n2, e, i + 1)), l), Tr(e, t, i, l), o && Cn(xn(o, Gi(t, r, i + 1)), l)), Tr(r, null, i, l), new A(l);
}
function Gi(n2, e, t) {
  let r = [];
  if (Tr(null, n2, t, r), n2.depth > t) {
    let i = $o(n2, e, t + 1);
    Cn(xn(i, Gi(n2, e, t + 1)), r);
  }
  return Tr(e, null, t, r), new A(r);
}
function _h(n2, e) {
  let t = e.depth - n2.openStart, i = e.node(t).copy(n2.content);
  for (let s = t - 1; s >= 0; s--)
    i = e.node(s).copy(A.from(i));
  return {
    start: i.resolveNoCache(n2.openStart + t),
    end: i.resolveNoCache(i.content.size - n2.openEnd - t)
  };
}
class $r {
  /**
  @internal
  */
  constructor(e, t, r) {
    this.pos = e, this.path = t, this.parentOffset = r, this.depth = t.length / 3 - 1;
  }
  /**
  @internal
  */
  resolveDepth(e) {
    return e == null ? this.depth : e < 0 ? this.depth + e : e;
  }
  /**
  The parent node that the position points into. Note that even if
  a position points into a text node, that node is not considered
  the parent—text nodes are ‘flat’ in this model, and have no content.
  */
  get parent() {
    return this.node(this.depth);
  }
  /**
  The root node in which the position was resolved.
  */
  get doc() {
    return this.node(0);
  }
  /**
  The ancestor node at the given level. `p.node(p.depth)` is the
  same as `p.parent`.
  */
  node(e) {
    return this.path[this.resolveDepth(e) * 3];
  }
  /**
  The index into the ancestor at the given level. If this points
  at the 3rd node in the 2nd paragraph on the top level, for
  example, `p.index(0)` is 1 and `p.index(1)` is 2.
  */
  index(e) {
    return this.path[this.resolveDepth(e) * 3 + 1];
  }
  /**
  The index pointing after this position into the ancestor at the
  given level.
  */
  indexAfter(e) {
    return e = this.resolveDepth(e), this.index(e) + (e == this.depth && !this.textOffset ? 0 : 1);
  }
  /**
  The (absolute) position at the start of the node at the given
  level.
  */
  start(e) {
    return e = this.resolveDepth(e), e == 0 ? 0 : this.path[e * 3 - 1] + 1;
  }
  /**
  The (absolute) position at the end of the node at the given
  level.
  */
  end(e) {
    return e = this.resolveDepth(e), this.start(e) + this.node(e).content.size;
  }
  /**
  The (absolute) position directly before the wrapping node at the
  given level, or, when `depth` is `this.depth + 1`, the original
  position.
  */
  before(e) {
    if (e = this.resolveDepth(e), !e)
      throw new RangeError("There is no position before the top-level node");
    return e == this.depth + 1 ? this.pos : this.path[e * 3 - 1];
  }
  /**
  The (absolute) position directly after the wrapping node at the
  given level, or the original position when `depth` is `this.depth + 1`.
  */
  after(e) {
    if (e = this.resolveDepth(e), !e)
      throw new RangeError("There is no position after the top-level node");
    return e == this.depth + 1 ? this.pos : this.path[e * 3 - 1] + this.path[e * 3].nodeSize;
  }
  /**
  When this position points into a text node, this returns the
  distance between the position and the start of the text node.
  Will be zero for positions that point between nodes.
  */
  get textOffset() {
    return this.pos - this.path[this.path.length - 1];
  }
  /**
  Get the node directly after the position, if any. If the position
  points into a text node, only the part of that node after the
  position is returned.
  */
  get nodeAfter() {
    let e = this.parent, t = this.index(this.depth);
    if (t == e.childCount)
      return null;
    let r = this.pos - this.path[this.path.length - 1], i = e.child(t);
    return r ? e.child(t).cut(r) : i;
  }
  /**
  Get the node directly before the position, if any. If the
  position points into a text node, only the part of that node
  before the position is returned.
  */
  get nodeBefore() {
    let e = this.index(this.depth), t = this.pos - this.path[this.path.length - 1];
    return t ? this.parent.child(e).cut(0, t) : e == 0 ? null : this.parent.child(e - 1);
  }
  /**
  Get the position at the given index in the parent node at the
  given depth (which defaults to `this.depth`).
  */
  posAtIndex(e, t) {
    t = this.resolveDepth(t);
    let r = this.path[t * 3], i = t == 0 ? 0 : this.path[t * 3 - 1] + 1;
    for (let s = 0; s < e; s++)
      i += r.child(s).nodeSize;
    return i;
  }
  /**
  Get the marks at this position, factoring in the surrounding
  marks' [`inclusive`](https://prosemirror.net/docs/ref/#model.MarkSpec.inclusive) property. If the
  position is at the start of a non-empty node, the marks of the
  node after it (if any) are returned.
  */
  marks() {
    let e = this.parent, t = this.index();
    if (e.content.size == 0)
      return te.none;
    if (this.textOffset)
      return e.child(t).marks;
    let r = e.maybeChild(t - 1), i = e.maybeChild(t);
    if (!r) {
      let l = r;
      r = i, i = l;
    }
    let s = r.marks;
    for (var o = 0; o < s.length; o++)
      s[o].type.spec.inclusive === false && (!i || !s[o].isInSet(i.marks)) && (s = s[o--].removeFromSet(s));
    return s;
  }
  /**
  Get the marks after the current position, if any, except those
  that are non-inclusive and not present at position `$end`. This
  is mostly useful for getting the set of marks to preserve after a
  deletion. Will return `null` if this position is at the end of
  its parent node or its parent node isn't a textblock (in which
  case no marks should be preserved).
  */
  marksAcross(e) {
    let t = this.parent.maybeChild(this.index());
    if (!t || !t.isInline)
      return null;
    let r = t.marks, i = e.parent.maybeChild(e.index());
    for (var s = 0; s < r.length; s++)
      r[s].type.spec.inclusive === false && (!i || !r[s].isInSet(i.marks)) && (r = r[s--].removeFromSet(r));
    return r;
  }
  /**
  The depth up to which this position and the given (non-resolved)
  position share the same parent nodes.
  */
  sharedDepth(e) {
    for (let t = this.depth; t > 0; t--)
      if (this.start(t) <= e && this.end(t) >= e)
        return t;
    return 0;
  }
  /**
  Returns a range based on the place where this position and the
  given position diverge around block content. If both point into
  the same textblock, for example, a range around that textblock
  will be returned. If they point into different blocks, the range
  around those blocks in their shared ancestor is returned. You can
  pass in an optional predicate that will be called with a parent
  node to see if a range into that parent is acceptable.
  */
  blockRange(e = this, t) {
    if (e.pos < this.pos)
      return e.blockRange(this);
    for (let r = this.depth - (this.parent.inlineContent || this.pos == e.pos ? 1 : 0); r >= 0; r--)
      if (e.pos <= this.end(r) && (!t || t(this.node(r))))
        return new Yi(this, e, r);
    return null;
  }
  /**
  Query whether the given position shares the same parent node.
  */
  sameParent(e) {
    return this.pos - this.parentOffset == e.pos - e.parentOffset;
  }
  /**
  Return the greater of this and the given position.
  */
  max(e) {
    return e.pos > this.pos ? e : this;
  }
  /**
  Return the smaller of this and the given position.
  */
  min(e) {
    return e.pos < this.pos ? e : this;
  }
  /**
  @internal
  */
  toString() {
    let e = "";
    for (let t = 1; t <= this.depth; t++)
      e += (e ? "/" : "") + this.node(t).type.name + "_" + this.index(t - 1);
    return e + ":" + this.parentOffset;
  }
  /**
  @internal
  */
  static resolve(e, t) {
    if (!(t >= 0 && t <= e.content.size))
      throw new RangeError("Position " + t + " out of range");
    let r = [], i = 0, s = t;
    for (let o = e; ; ) {
      let { index: l, offset: a } = o.content.findIndex(s), c = s - a;
      if (r.push(o, l, i + a), !c || (o = o.child(l), o.isText))
        break;
      s = c - 1, i += a + 1;
    }
    return new $r(t, r, s);
  }
  /**
  @internal
  */
  static resolveCached(e, t) {
    let r = Ma.get(e);
    if (r)
      for (let s = 0; s < r.elts.length; s++) {
        let o = r.elts[s];
        if (o.pos == t)
          return o;
      }
    else
      Ma.set(e, r = new jh());
    let i = r.elts[r.i] = $r.resolve(e, t);
    return r.i = (r.i + 1) % Wh, i;
  }
}
class jh {
  constructor() {
    this.elts = [], this.i = 0;
  }
}
const Wh = 12, Ma = /* @__PURE__ */ new WeakMap();
class Yi {
  /**
  Construct a node range. `$from` and `$to` should point into the
  same node until at least the given `depth`, since a node range
  denotes an adjacent set of nodes in a single parent node.
  */
  constructor(e, t, r) {
    this.$from = e, this.$to = t, this.depth = r;
  }
  /**
  The position at the start of the range.
  */
  get start() {
    return this.$from.before(this.depth + 1);
  }
  /**
  The position at the end of the range.
  */
  get end() {
    return this.$to.after(this.depth + 1);
  }
  /**
  The parent node that the range points into.
  */
  get parent() {
    return this.$from.node(this.depth);
  }
  /**
  The start index of the range in the parent node.
  */
  get startIndex() {
    return this.$from.index(this.depth);
  }
  /**
  The end index of the range in the parent node.
  */
  get endIndex() {
    return this.$to.indexAfter(this.depth);
  }
}
const Uh = /* @__PURE__ */ Object.create(null);
let Qt = class _o {
  /**
  @internal
  */
  constructor(e, t, r, i = te.none) {
    this.type = e, this.attrs = t, this.marks = i, this.content = r || A.empty;
  }
  /**
  The array of this node's child nodes.
  */
  get children() {
    return this.content.content;
  }
  /**
  The size of this node, as defined by the integer-based [indexing
  scheme](https://prosemirror.net/docs/guide/#doc.indexing). For text nodes, this is the
  amount of characters. For other leaf nodes, it is one. For
  non-leaf nodes, it is the size of the content plus two (the
  start and end token).
  */
  get nodeSize() {
    return this.isLeaf ? 1 : 2 + this.content.size;
  }
  /**
  The number of children that the node has.
  */
  get childCount() {
    return this.content.childCount;
  }
  /**
  Get the child node at the given index. Raises an error when the
  index is out of range.
  */
  child(e) {
    return this.content.child(e);
  }
  /**
  Get the child node at the given index, if it exists.
  */
  maybeChild(e) {
    return this.content.maybeChild(e);
  }
  /**
  Call `f` for every child node, passing the node, its offset
  into this parent node, and its index.
  */
  forEach(e) {
    this.content.forEach(e);
  }
  /**
  Invoke a callback for all descendant nodes recursively between
  the given two positions that are relative to start of this
  node's content. The callback is invoked with the node, its
  position relative to the original node (method receiver),
  its parent node, and its child index. When the callback returns
  false for a given node, that node's children will not be
  recursed over. The last parameter can be used to specify a
  starting position to count from.
  */
  nodesBetween(e, t, r, i = 0) {
    this.content.nodesBetween(e, t, r, i, this);
  }
  /**
  Call the given callback for every descendant node. Doesn't
  descend into a node when the callback returns `false`.
  */
  descendants(e) {
    this.nodesBetween(0, this.content.size, e);
  }
  /**
  Concatenates all the text nodes found in this fragment and its
  children.
  */
  get textContent() {
    return this.isLeaf && this.type.spec.leafText ? this.type.spec.leafText(this) : this.textBetween(0, this.content.size, "");
  }
  /**
  Get all text between positions `from` and `to`. When
  `blockSeparator` is given, it will be inserted to separate text
  from different block nodes. If `leafText` is given, it'll be
  inserted for every non-text leaf node encountered, otherwise
  [`leafText`](https://prosemirror.net/docs/ref/#model.NodeSpec^leafText) will be used.
  */
  textBetween(e, t, r, i) {
    return this.content.textBetween(e, t, r, i);
  }
  /**
  Returns this node's first child, or `null` if there are no
  children.
  */
  get firstChild() {
    return this.content.firstChild;
  }
  /**
  Returns this node's last child, or `null` if there are no
  children.
  */
  get lastChild() {
    return this.content.lastChild;
  }
  /**
  Test whether two nodes represent the same piece of document.
  */
  eq(e) {
    return this == e || this.sameMarkup(e) && this.content.eq(e.content);
  }
  /**
  Compare the markup (type, attributes, and marks) of this node to
  those of another. Returns `true` if both have the same markup.
  */
  sameMarkup(e) {
    return this.hasMarkup(e.type, e.attrs, e.marks);
  }
  /**
  Check whether this node's markup correspond to the given type,
  attributes, and marks.
  */
  hasMarkup(e, t, r) {
    return this.type == e && qi(this.attrs, t || e.defaultAttrs || Uh) && te.sameSet(this.marks, r || te.none);
  }
  /**
  Create a new node with the same markup as this node, containing
  the given content (or empty, if no content is given).
  */
  copy(e = null) {
    return e == this.content ? this : new _o(this.type, this.attrs, e, this.marks);
  }
  /**
  Create a copy of this node, with the given set of marks instead
  of the node's own marks.
  */
  mark(e) {
    return e == this.marks ? this : new _o(this.type, this.attrs, this.content, e);
  }
  /**
  Create a copy of this node with only the content between the
  given positions. If `to` is not given, it defaults to the end of
  the node.
  */
  cut(e, t = this.content.size) {
    return e == 0 && t == this.content.size ? this : this.copy(this.content.cut(e, t));
  }
  /**
  Cut out the part of the document between the given positions, and
  return it as a `Slice` object.
  */
  slice(e, t = this.content.size, r = false) {
    if (e == t)
      return O.empty;
    let i = this.resolve(e), s = this.resolve(t), o = r ? 0 : i.sharedDepth(t), l = i.start(o), c = i.node(o).content.cut(i.pos - l, s.pos - l);
    return new O(c, i.depth - o, s.depth - o);
  }
  /**
  Replace the part of the document between the given positions with
  the given slice. The slice must 'fit', meaning its open sides
  must be able to connect to the surrounding content, and its
  content nodes must be valid children for the node they are placed
  into. If any of this is violated, an error of type
  [`ReplaceError`](https://prosemirror.net/docs/ref/#model.ReplaceError) is thrown.
  */
  replace(e, t, r) {
    return $h(this.resolve(e), this.resolve(t), r);
  }
  /**
  Find the node directly after the given position.
  */
  nodeAt(e) {
    for (let t = this; ; ) {
      let { index: r, offset: i } = t.content.findIndex(e);
      if (t = t.maybeChild(r), !t)
        return null;
      if (i == e || t.isText)
        return t;
      e -= i + 1;
    }
  }
  /**
  Find the (direct) child node after the given offset, if any,
  and return it along with its index and offset relative to this
  node.
  */
  childAfter(e) {
    let { index: t, offset: r } = this.content.findIndex(e);
    return { node: this.content.maybeChild(t), index: t, offset: r };
  }
  /**
  Find the (direct) child node before the given offset, if any,
  and return it along with its index and offset relative to this
  node.
  */
  childBefore(e) {
    if (e == 0)
      return { node: null, index: 0, offset: 0 };
    let { index: t, offset: r } = this.content.findIndex(e);
    if (r < e)
      return { node: this.content.child(t), index: t, offset: r };
    let i = this.content.child(t - 1);
    return { node: i, index: t - 1, offset: r - i.nodeSize };
  }
  /**
  Resolve the given position in the document, returning an
  [object](https://prosemirror.net/docs/ref/#model.ResolvedPos) with information about its context.
  */
  resolve(e) {
    return $r.resolveCached(this, e);
  }
  /**
  @internal
  */
  resolveNoCache(e) {
    return $r.resolve(this, e);
  }
  /**
  Test whether a given mark or mark type occurs in this document
  between the two given positions.
  */
  rangeHasMark(e, t, r) {
    let i = false;
    return t > e && this.nodesBetween(e, t, (s) => (r.isInSet(s.marks) && (i = true), !i)), i;
  }
  /**
  True when this is a block (non-inline node)
  */
  get isBlock() {
    return this.type.isBlock;
  }
  /**
  True when this is a textblock node, a block node with inline
  content.
  */
  get isTextblock() {
    return this.type.isTextblock;
  }
  /**
  True when this node allows inline content.
  */
  get inlineContent() {
    return this.type.inlineContent;
  }
  /**
  True when this is an inline node (a text node or a node that can
  appear among text).
  */
  get isInline() {
    return this.type.isInline;
  }
  /**
  True when this is a text node.
  */
  get isText() {
    return this.type.isText;
  }
  /**
  True when this is a leaf node.
  */
  get isLeaf() {
    return this.type.isLeaf;
  }
  /**
  True when this is an atom, i.e. when it does not have directly
  editable content. This is usually the same as `isLeaf`, but can
  be configured with the [`atom` property](https://prosemirror.net/docs/ref/#model.NodeSpec.atom)
  on a node's spec (typically used when the node is displayed as
  an uneditable [node view](https://prosemirror.net/docs/ref/#view.NodeView)).
  */
  get isAtom() {
    return this.type.isAtom;
  }
  /**
  Return a string representation of this node for debugging
  purposes.
  */
  toString() {
    if (this.type.spec.toDebugString)
      return this.type.spec.toDebugString(this);
    let e = this.type.name;
    return this.content.size && (e += "(" + this.content.toStringInner() + ")"), _u(this.marks, e);
  }
  /**
  Get the content match in this node at the given index.
  */
  contentMatchAt(e) {
    let t = this.type.contentMatch.matchFragment(this.content, 0, e);
    if (!t)
      throw new Error("Called contentMatchAt on a node with invalid content");
    return t;
  }
  /**
  Test whether replacing the range between `from` and `to` (by
  child index) with the given replacement fragment (which defaults
  to the empty fragment) would leave the node's content valid. You
  can optionally pass `start` and `end` indices into the
  replacement fragment.
  */
  canReplace(e, t, r = A.empty, i = 0, s = r.childCount) {
    let o = this.contentMatchAt(e).matchFragment(r, i, s), l = o && o.matchFragment(this.content, t);
    if (!l || !l.validEnd)
      return false;
    for (let a = i; a < s; a++)
      if (!this.type.allowsMarks(r.child(a).marks))
        return false;
    return true;
  }
  /**
  Test whether replacing the range `from` to `to` (by index) with
  a node of the given type would leave the node's content valid.
  */
  canReplaceWith(e, t, r, i) {
    if (i && !this.type.allowsMarks(i))
      return false;
    let s = this.contentMatchAt(e).matchType(r), o = s && s.matchFragment(this.content, t);
    return o ? o.validEnd : false;
  }
  /**
  Test whether the given node's content could be appended to this
  node. If that node is empty, this will only return true if there
  is at least one node type that can appear in both nodes (to avoid
  merging completely incompatible nodes).
  */
  canAppend(e) {
    return e.content.size ? this.canReplace(this.childCount, this.childCount, e.content) : this.type.compatibleContent(e.type);
  }
  /**
  Check whether this node and its descendants conform to the
  schema, and raise an exception when they do not.
  */
  check() {
    this.type.checkContent(this.content), this.type.checkAttrs(this.attrs);
    let e = te.none;
    for (let t = 0; t < this.marks.length; t++) {
      let r = this.marks[t];
      r.type.checkAttrs(r.attrs), e = r.addToSet(e);
    }
    if (!te.sameSet(e, this.marks))
      throw new RangeError(`Invalid collection of marks for node ${this.type.name}: ${this.marks.map((t) => t.type.name)}`);
    this.content.forEach((t) => t.check());
  }
  /**
  Return a JSON-serializeable representation of this node.
  */
  toJSON() {
    let e = { type: this.type.name };
    for (let t in this.attrs) {
      e.attrs = this.attrs;
      break;
    }
    return this.content.size && (e.content = this.content.toJSON()), this.marks.length && (e.marks = this.marks.map((t) => t.toJSON())), e;
  }
  /**
  Deserialize a node from its JSON representation.
  */
  static fromJSON(e, t) {
    if (!t)
      throw new RangeError("Invalid input for Node.fromJSON");
    let r;
    if (t.marks) {
      if (!Array.isArray(t.marks))
        throw new RangeError("Invalid mark data for Node.fromJSON");
      r = t.marks.map(e.markFromJSON);
    }
    if (t.type == "text") {
      if (typeof t.text != "string")
        throw new RangeError("Invalid text node in JSON");
      return e.text(t.text, r);
    }
    let i = A.fromJSON(e, t.content), s = e.nodeType(t.type).create(t.attrs, i, r);
    return s.type.checkAttrs(s.attrs), s;
  }
};
Qt.prototype.text = void 0;
class Xi extends Qt {
  /**
  @internal
  */
  constructor(e, t, r, i) {
    if (super(e, t, null, i), !r)
      throw new RangeError("Empty text nodes are not allowed");
    this.text = r;
  }
  toString() {
    return this.type.spec.toDebugString ? this.type.spec.toDebugString(this) : _u(this.marks, JSON.stringify(this.text));
  }
  get textContent() {
    return this.text;
  }
  textBetween(e, t) {
    return this.text.slice(e, t);
  }
  get nodeSize() {
    return this.text.length;
  }
  mark(e) {
    return e == this.marks ? this : new Xi(this.type, this.attrs, this.text, e);
  }
  withText(e) {
    return e == this.text ? this : new Xi(this.type, this.attrs, e, this.marks);
  }
  cut(e = 0, t = this.text.length) {
    return e == 0 && t == this.text.length ? this : this.withText(this.text.slice(e, t));
  }
  eq(e) {
    return this.sameMarkup(e) && this.text == e.text;
  }
  toJSON() {
    let e = super.toJSON();
    return e.text = this.text, e;
  }
}
function _u(n2, e) {
  for (let t = n2.length - 1; t >= 0; t--)
    e = n2[t].type.name + "(" + e + ")";
  return e;
}
class Tn {
  /**
  @internal
  */
  constructor(e) {
    this.validEnd = e, this.next = [], this.wrapCache = [];
  }
  /**
  @internal
  */
  static parse(e, t) {
    let r = new Kh(e, t);
    if (r.next == null)
      return Tn.empty;
    let i = ju(r);
    r.next && r.err("Unexpected trailing text");
    let s = Zh(Qh(i));
    return ep(s, r), s;
  }
  /**
  Match a node type, returning a match after that node if
  successful.
  */
  matchType(e) {
    for (let t = 0; t < this.next.length; t++)
      if (this.next[t].type == e)
        return this.next[t].next;
    return null;
  }
  /**
  Try to match a fragment. Returns the resulting match when
  successful.
  */
  matchFragment(e, t = 0, r = e.childCount) {
    let i = this;
    for (let s = t; i && s < r; s++)
      i = i.matchType(e.child(s).type);
    return i;
  }
  /**
  @internal
  */
  get inlineContent() {
    return this.next.length != 0 && this.next[0].type.isInline;
  }
  /**
  Get the first matching node type at this match position that can
  be generated.
  */
  get defaultType() {
    for (let e = 0; e < this.next.length; e++) {
      let { type: t } = this.next[e];
      if (!(t.isText || t.hasRequiredAttrs()))
        return t;
    }
    return null;
  }
  /**
  @internal
  */
  compatible(e) {
    for (let t = 0; t < this.next.length; t++)
      for (let r = 0; r < e.next.length; r++)
        if (this.next[t].type == e.next[r].type)
          return true;
    return false;
  }
  /**
  Try to match the given fragment, and if that fails, see if it can
  be made to match by inserting nodes in front of it. When
  successful, return a fragment of inserted nodes (which may be
  empty if nothing had to be inserted). When `toEnd` is true, only
  return a fragment if the resulting match goes to the end of the
  content expression.
  */
  fillBefore(e, t = false, r = 0) {
    let i = [this];
    function s(o, l) {
      let a = o.matchFragment(e, r);
      if (a && (!t || a.validEnd))
        return A.from(l.map((c) => c.createAndFill()));
      for (let c = 0; c < o.next.length; c++) {
        let { type: u, next: d } = o.next[c];
        if (!(u.isText || u.hasRequiredAttrs()) && i.indexOf(d) == -1) {
          i.push(d);
          let f = s(d, l.concat(u));
          if (f)
            return f;
        }
      }
      return null;
    }
    return s(this, []);
  }
  /**
  Find a set of wrapping node types that would allow a node of the
  given type to appear at this position. The result may be empty
  (when it fits directly) and will be null when no such wrapping
  exists.
  */
  findWrapping(e) {
    for (let r = 0; r < this.wrapCache.length; r += 2)
      if (this.wrapCache[r] == e)
        return this.wrapCache[r + 1];
    let t = this.computeWrapping(e);
    return this.wrapCache.push(e, t), t;
  }
  /**
  @internal
  */
  computeWrapping(e) {
    let t = /* @__PURE__ */ Object.create(null), r = [{ match: this, type: null, via: null }];
    for (; r.length; ) {
      let i = r.shift(), s = i.match;
      if (s.matchType(e)) {
        let o = [];
        for (let l = i; l.type; l = l.via)
          o.push(l.type);
        return o.reverse();
      }
      for (let o = 0; o < s.next.length; o++) {
        let { type: l, next: a } = s.next[o];
        !l.isLeaf && !l.hasRequiredAttrs() && !(l.name in t) && (!i.type || a.validEnd) && (r.push({ match: l.contentMatch, type: l, via: i }), t[l.name] = true);
      }
    }
    return null;
  }
  /**
  The number of outgoing edges this node has in the finite
  automaton that describes the content expression.
  */
  get edgeCount() {
    return this.next.length;
  }
  /**
  Get the _n_​th outgoing edge from this node in the finite
  automaton that describes the content expression.
  */
  edge(e) {
    if (e >= this.next.length)
      throw new RangeError(`There's no ${e}th edge in this content match`);
    return this.next[e];
  }
  /**
  @internal
  */
  toString() {
    let e = [];
    function t(r) {
      e.push(r);
      for (let i = 0; i < r.next.length; i++)
        e.indexOf(r.next[i].next) == -1 && t(r.next[i].next);
    }
    return t(this), e.map((r, i) => {
      let s = i + (r.validEnd ? "*" : " ") + " ";
      for (let o = 0; o < r.next.length; o++)
        s += (o ? ", " : "") + r.next[o].type.name + "->" + e.indexOf(r.next[o].next);
      return s;
    }).join(`
`);
  }
}
Tn.empty = new Tn(true);
class Kh {
  constructor(e, t) {
    this.string = e, this.nodeTypes = t, this.inline = null, this.pos = 0, this.tokens = e.split(/\s*(?=\b|\W|$)/), this.tokens[this.tokens.length - 1] == "" && this.tokens.pop(), this.tokens[0] == "" && this.tokens.shift();
  }
  get next() {
    return this.tokens[this.pos];
  }
  eat(e) {
    return this.next == e && (this.pos++ || true);
  }
  err(e) {
    throw new SyntaxError(e + " (in content expression '" + this.string + "')");
  }
}
function ju(n2) {
  let e = [];
  do
    e.push(qh(n2));
  while (n2.eat("|"));
  return e.length == 1 ? e[0] : { type: "choice", exprs: e };
}
function qh(n2) {
  let e = [];
  do
    e.push(Jh(n2));
  while (n2.next && n2.next != ")" && n2.next != "|");
  return e.length == 1 ? e[0] : { type: "seq", exprs: e };
}
function Jh(n2) {
  let e = Xh(n2);
  for (; ; )
    if (n2.eat("+"))
      e = { type: "plus", expr: e };
    else if (n2.eat("*"))
      e = { type: "star", expr: e };
    else if (n2.eat("?"))
      e = { type: "opt", expr: e };
    else if (n2.eat("{"))
      e = Gh(n2, e);
    else
      break;
  return e;
}
function Aa(n2) {
  /\D/.test(n2.next) && n2.err("Expected number, got '" + n2.next + "'");
  let e = Number(n2.next);
  return n2.pos++, e;
}
function Gh(n2, e) {
  let t = Aa(n2), r = t;
  return n2.eat(",") && (n2.next != "}" ? r = Aa(n2) : r = -1), n2.eat("}") || n2.err("Unclosed braced range"), { type: "range", min: t, max: r, expr: e };
}
function Yh(n2, e) {
  let t = n2.nodeTypes, r = t[e];
  if (r)
    return [r];
  let i = [];
  for (let s in t) {
    let o = t[s];
    o.isInGroup(e) && i.push(o);
  }
  return i.length == 0 && n2.err("No node type or group '" + e + "' found"), i;
}
function Xh(n2) {
  if (n2.eat("(")) {
    let e = ju(n2);
    return n2.eat(")") || n2.err("Missing closing paren"), e;
  } else if (/\W/.test(n2.next))
    n2.err("Unexpected token '" + n2.next + "'");
  else {
    let e = Yh(n2, n2.next).map((t) => (n2.inline == null ? n2.inline = t.isInline : n2.inline != t.isInline && n2.err("Mixing inline and block content"), { type: "name", value: t }));
    return n2.pos++, e.length == 1 ? e[0] : { type: "choice", exprs: e };
  }
}
function Qh(n2) {
  let e = [[]];
  return i(s(n2, 0), t()), e;
  function t() {
    return e.push([]) - 1;
  }
  function r(o, l, a) {
    let c = { term: a, to: l };
    return e[o].push(c), c;
  }
  function i(o, l) {
    o.forEach((a) => a.to = l);
  }
  function s(o, l) {
    if (o.type == "choice")
      return o.exprs.reduce((a, c) => a.concat(s(c, l)), []);
    if (o.type == "seq")
      for (let a = 0; ; a++) {
        let c = s(o.exprs[a], l);
        if (a == o.exprs.length - 1)
          return c;
        i(c, l = t());
      }
    else if (o.type == "star") {
      let a = t();
      return r(l, a), i(s(o.expr, a), a), [r(a)];
    } else if (o.type == "plus") {
      let a = t();
      return i(s(o.expr, l), a), i(s(o.expr, a), a), [r(a)];
    } else {
      if (o.type == "opt")
        return [r(l)].concat(s(o.expr, l));
      if (o.type == "range") {
        let a = l;
        for (let c = 0; c < o.min; c++) {
          let u = t();
          i(s(o.expr, a), u), a = u;
        }
        if (o.max == -1)
          i(s(o.expr, a), a);
        else
          for (let c = o.min; c < o.max; c++) {
            let u = t();
            r(a, u), i(s(o.expr, a), u), a = u;
          }
        return [r(a)];
      } else {
        if (o.type == "name")
          return [r(l, void 0, o.value)];
        throw new Error("Unknown expr type");
      }
    }
  }
}
function Wu(n2, e) {
  return e - n2;
}
function Ea(n2, e) {
  let t = [];
  return r(e), t.sort(Wu);
  function r(i) {
    let s = n2[i];
    if (s.length == 1 && !s[0].term)
      return r(s[0].to);
    t.push(i);
    for (let o = 0; o < s.length; o++) {
      let { term: l, to: a } = s[o];
      !l && t.indexOf(a) == -1 && r(a);
    }
  }
}
function Zh(n2) {
  let e = /* @__PURE__ */ Object.create(null);
  return t(Ea(n2, 0));
  function t(r) {
    let i = [];
    r.forEach((o) => {
      n2[o].forEach(({ term: l, to: a }) => {
        if (!l)
          return;
        let c;
        for (let u = 0; u < i.length; u++)
          i[u][0] == l && (c = i[u][1]);
        Ea(n2, a).forEach((u) => {
          c || i.push([l, c = []]), c.indexOf(u) == -1 && c.push(u);
        });
      });
    });
    let s = e[r.join(",")] = new Tn(r.indexOf(n2.length - 1) > -1);
    for (let o = 0; o < i.length; o++) {
      let l = i[o][1].sort(Wu);
      s.next.push({ type: i[o][0], next: e[l.join(",")] || t(l) });
    }
    return s;
  }
}
function ep(n2, e) {
  for (let t = 0, r = [n2]; t < r.length; t++) {
    let i = r[t], s = !i.validEnd, o = [];
    for (let l = 0; l < i.next.length; l++) {
      let { type: a, next: c } = i.next[l];
      o.push(a.name), s && !(a.isText || a.hasRequiredAttrs()) && (s = false), r.indexOf(c) == -1 && r.push(c);
    }
    s && e.err("Only non-generatable nodes (" + o.join(", ") + ") in a required position (see https://prosemirror.net/docs/guide/#generatable)");
  }
}
function Uu(n2) {
  let e = /* @__PURE__ */ Object.create(null);
  for (let t in n2) {
    let r = n2[t];
    if (!r.hasDefault)
      return null;
    e[t] = r.default;
  }
  return e;
}
function Ku(n2, e) {
  let t = /* @__PURE__ */ Object.create(null);
  for (let r in n2) {
    let i = e && e[r];
    if (i === void 0) {
      let s = n2[r];
      if (s.hasDefault)
        i = s.default;
      else
        throw new RangeError("No value supplied for attribute " + r);
    }
    t[r] = i;
  }
  return t;
}
function qu(n2, e, t, r) {
  for (let i in e)
    if (!(i in n2))
      throw new RangeError(`Unsupported attribute ${i} for ${t} of type ${i}`);
  for (let i in n2) {
    let s = n2[i];
    s.validate && s.validate(e[i]);
  }
}
function Ju(n2, e) {
  let t = /* @__PURE__ */ Object.create(null);
  if (e)
    for (let r in e)
      t[r] = new np(n2, r, e[r]);
  return t;
}
let Ta = class Gu {
  /**
  @internal
  */
  constructor(e, t, r) {
    this.name = e, this.schema = t, this.spec = r, this.markSet = null, this.groups = r.group ? r.group.split(" ") : [], this.attrs = Ju(e, r.attrs), this.defaultAttrs = Uu(this.attrs), this.contentMatch = null, this.inlineContent = null, this.isBlock = !(r.inline || e == "text"), this.isText = e == "text";
  }
  /**
  True if this is an inline type.
  */
  get isInline() {
    return !this.isBlock;
  }
  /**
  True if this is a textblock type, a block that contains inline
  content.
  */
  get isTextblock() {
    return this.isBlock && this.inlineContent;
  }
  /**
  True for node types that allow no content.
  */
  get isLeaf() {
    return this.contentMatch == Tn.empty;
  }
  /**
  True when this node is an atom, i.e. when it does not have
  directly editable content.
  */
  get isAtom() {
    return this.isLeaf || !!this.spec.atom;
  }
  /**
  Return true when this node type is part of the given
  [group](https://prosemirror.net/docs/ref/#model.NodeSpec.group).
  */
  isInGroup(e) {
    return this.groups.indexOf(e) > -1;
  }
  /**
  The node type's [whitespace](https://prosemirror.net/docs/ref/#model.NodeSpec.whitespace) option.
  */
  get whitespace() {
    return this.spec.whitespace || (this.spec.code ? "pre" : "normal");
  }
  /**
  Tells you whether this node type has any required attributes.
  */
  hasRequiredAttrs() {
    for (let e in this.attrs)
      if (this.attrs[e].isRequired)
        return true;
    return false;
  }
  /**
  Indicates whether this node allows some of the same content as
  the given node type.
  */
  compatibleContent(e) {
    return this == e || this.contentMatch.compatible(e.contentMatch);
  }
  /**
  @internal
  */
  computeAttrs(e) {
    return !e && this.defaultAttrs ? this.defaultAttrs : Ku(this.attrs, e);
  }
  /**
  Create a `Node` of this type. The given attributes are
  checked and defaulted (you can pass `null` to use the type's
  defaults entirely, if no required attributes exist). `content`
  may be a `Fragment`, a node, an array of nodes, or
  `null`. Similarly `marks` may be `null` to default to the empty
  set of marks.
  */
  create(e = null, t, r) {
    if (this.isText)
      throw new Error("NodeType.create can't construct text nodes");
    return new Qt(this, this.computeAttrs(e), A.from(t), te.setFrom(r));
  }
  /**
  Like [`create`](https://prosemirror.net/docs/ref/#model.NodeType.create), but check the given content
  against the node type's content restrictions, and throw an error
  if it doesn't match.
  */
  createChecked(e = null, t, r) {
    return t = A.from(t), this.checkContent(t), new Qt(this, this.computeAttrs(e), t, te.setFrom(r));
  }
  /**
  Like [`create`](https://prosemirror.net/docs/ref/#model.NodeType.create), but see if it is
  necessary to add nodes to the start or end of the given fragment
  to make it fit the node. If no fitting wrapping can be found,
  return null. Note that, due to the fact that required nodes can
  always be created, this will always succeed if you pass null or
  `Fragment.empty` as content.
  */
  createAndFill(e = null, t, r) {
    if (e = this.computeAttrs(e), t = A.from(t), t.size) {
      let o = this.contentMatch.fillBefore(t);
      if (!o)
        return null;
      t = o.append(t);
    }
    let i = this.contentMatch.matchFragment(t), s = i && i.fillBefore(A.empty, true);
    return s ? new Qt(this, e, t.append(s), te.setFrom(r)) : null;
  }
  /**
  Returns true if the given fragment is valid content for this node
  type.
  */
  validContent(e) {
    let t = this.contentMatch.matchFragment(e);
    if (!t || !t.validEnd)
      return false;
    for (let r = 0; r < e.childCount; r++)
      if (!this.allowsMarks(e.child(r).marks))
        return false;
    return true;
  }
  /**
  Throws a RangeError if the given fragment is not valid content for this
  node type.
  @internal
  */
  checkContent(e) {
    if (!this.validContent(e))
      throw new RangeError(`Invalid content for node ${this.name}: ${e.toString().slice(0, 50)}`);
  }
  /**
  @internal
  */
  checkAttrs(e) {
    qu(this.attrs, e, "node", this.name);
  }
  /**
  Check whether the given mark type is allowed in this node.
  */
  allowsMarkType(e) {
    return this.markSet == null || this.markSet.indexOf(e) > -1;
  }
  /**
  Test whether the given set of marks are allowed in this node.
  */
  allowsMarks(e) {
    if (this.markSet == null)
      return true;
    for (let t = 0; t < e.length; t++)
      if (!this.allowsMarkType(e[t].type))
        return false;
    return true;
  }
  /**
  Removes the marks that are not allowed in this node from the given set.
  */
  allowedMarks(e) {
    if (this.markSet == null)
      return e;
    let t;
    for (let r = 0; r < e.length; r++)
      this.allowsMarkType(e[r].type) ? t && t.push(e[r]) : t || (t = e.slice(0, r));
    return t ? t.length ? t : te.none : e;
  }
  /**
  @internal
  */
  static compile(e, t) {
    let r = /* @__PURE__ */ Object.create(null);
    e.forEach((s, o) => r[s] = new Gu(s, t, o));
    let i = t.spec.topNode || "doc";
    if (!r[i])
      throw new RangeError("Schema is missing its top node type ('" + i + "')");
    if (!r.text)
      throw new RangeError("Every schema needs a 'text' type");
    for (let s in r.text.attrs)
      throw new RangeError("The text node type should not have attributes");
    return r;
  }
};
function tp(n2, e, t) {
  let r = t.split("|");
  return (i) => {
    let s = i === null ? "null" : typeof i;
    if (r.indexOf(s) < 0)
      throw new RangeError(`Expected value of type ${r} for attribute ${e} on type ${n2}, got ${s}`);
  };
}
class np {
  constructor(e, t, r) {
    this.hasDefault = Object.prototype.hasOwnProperty.call(r, "default"), this.default = r.default, this.validate = typeof r.validate == "string" ? tp(e, t, r.validate) : r.validate;
  }
  get isRequired() {
    return !this.hasDefault;
  }
}
class Vs {
  /**
  @internal
  */
  constructor(e, t, r, i) {
    this.name = e, this.rank = t, this.schema = r, this.spec = i, this.attrs = Ju(e, i.attrs), this.excluded = null;
    let s = Uu(this.attrs);
    this.instance = s ? new te(this, s) : null;
  }
  /**
  Create a mark of this type. `attrs` may be `null` or an object
  containing only some of the mark's attributes. The others, if
  they have defaults, will be added.
  */
  create(e = null) {
    return !e && this.instance ? this.instance : new te(this, Ku(this.attrs, e));
  }
  /**
  @internal
  */
  static compile(e, t) {
    let r = /* @__PURE__ */ Object.create(null), i = 0;
    return e.forEach((s, o) => r[s] = new Vs(s, i++, t, o)), r;
  }
  /**
  When there is a mark of this type in the given set, a new set
  without it is returned. Otherwise, the input set is returned.
  */
  removeFromSet(e) {
    for (var t = 0; t < e.length; t++)
      e[t].type == this && (e = e.slice(0, t).concat(e.slice(t + 1)), t--);
    return e;
  }
  /**
  Tests whether there is a mark of this type in the given set.
  */
  isInSet(e) {
    for (let t = 0; t < e.length; t++)
      if (e[t].type == this)
        return e[t];
  }
  /**
  @internal
  */
  checkAttrs(e) {
    qu(this.attrs, e, "mark", this.name);
  }
  /**
  Queries whether a given mark type is
  [excluded](https://prosemirror.net/docs/ref/#model.MarkSpec.excludes) by this one.
  */
  excludes(e) {
    return this.excluded.indexOf(e) > -1;
  }
}
class Yu {
  /**
  Construct a schema from a schema [specification](https://prosemirror.net/docs/ref/#model.SchemaSpec).
  */
  constructor(e) {
    this.linebreakReplacement = null, this.cached = /* @__PURE__ */ Object.create(null);
    let t = this.spec = {};
    for (let i in e)
      t[i] = e[i];
    t.nodes = be.from(e.nodes), t.marks = be.from(e.marks || {}), this.nodes = Ta.compile(this.spec.nodes, this), this.marks = Vs.compile(this.spec.marks, this);
    let r = /* @__PURE__ */ Object.create(null);
    for (let i in this.nodes) {
      if (i in this.marks)
        throw new RangeError(i + " can not be both a node and a mark");
      let s = this.nodes[i], o = s.spec.content || "", l = s.spec.marks;
      if (s.contentMatch = r[o] || (r[o] = Tn.parse(o, this.nodes)), s.inlineContent = s.contentMatch.inlineContent, s.spec.linebreakReplacement) {
        if (this.linebreakReplacement)
          throw new RangeError("Multiple linebreak nodes defined");
        if (!s.isInline || !s.isLeaf)
          throw new RangeError("Linebreak replacement nodes must be inline leaf nodes");
        this.linebreakReplacement = s;
      }
      s.markSet = l == "_" ? null : l ? Oa(this, l.split(" ")) : l == "" || !s.inlineContent ? [] : null;
    }
    for (let i in this.marks) {
      let s = this.marks[i], o = s.spec.excludes;
      s.excluded = o == null ? [s] : o == "" ? [] : Oa(this, o.split(" "));
    }
    this.nodeFromJSON = this.nodeFromJSON.bind(this), this.markFromJSON = this.markFromJSON.bind(this), this.topNodeType = this.nodes[this.spec.topNode || "doc"], this.cached.wrappings = /* @__PURE__ */ Object.create(null);
  }
  /**
  Create a node in this schema. The `type` may be a string or a
  `NodeType` instance. Attributes will be extended with defaults,
  `content` may be a `Fragment`, `null`, a `Node`, or an array of
  nodes.
  */
  node(e, t = null, r, i) {
    if (typeof e == "string")
      e = this.nodeType(e);
    else if (e instanceof Ta) {
      if (e.schema != this)
        throw new RangeError("Node type from different schema used (" + e.name + ")");
    } else throw new RangeError("Invalid node type: " + e);
    return e.createChecked(t, r, i);
  }
  /**
  Create a text node in the schema. Empty text nodes are not
  allowed.
  */
  text(e, t) {
    let r = this.nodes.text;
    return new Xi(r, r.defaultAttrs, e, te.setFrom(t));
  }
  /**
  Create a mark with the given type and attributes.
  */
  mark(e, t) {
    return typeof e == "string" && (e = this.marks[e]), e.create(t);
  }
  /**
  Deserialize a node from its JSON representation. This method is
  bound.
  */
  nodeFromJSON(e) {
    return Qt.fromJSON(this, e);
  }
  /**
  Deserialize a mark from its JSON representation. This method is
  bound.
  */
  markFromJSON(e) {
    return te.fromJSON(this, e);
  }
  /**
  @internal
  */
  nodeType(e) {
    let t = this.nodes[e];
    if (!t)
      throw new RangeError("Unknown node type: " + e);
    return t;
  }
}
function Oa(n2, e) {
  let t = [];
  for (let r = 0; r < e.length; r++) {
    let i = e[r], s = n2.marks[i], o = s;
    if (s)
      t.push(s);
    else
      for (let l in n2.marks) {
        let a = n2.marks[l];
        (i == "_" || a.spec.group && a.spec.group.split(" ").indexOf(i) > -1) && t.push(o = a);
      }
    if (!o)
      throw new SyntaxError("Unknown mark type: '" + e[r] + "'");
  }
  return t;
}
function rp(n2) {
  return n2.tag != null;
}
function ip(n2) {
  return n2.style != null;
}
class Zt {
  /**
  Create a parser that targets the given schema, using the given
  parsing rules.
  */
  constructor(e, t) {
    this.schema = e, this.rules = t, this.tags = [], this.styles = [];
    let r = this.matchedStyles = [];
    t.forEach((i) => {
      if (rp(i))
        this.tags.push(i);
      else if (ip(i)) {
        let s = /[^=]*/.exec(i.style)[0];
        r.indexOf(s) < 0 && r.push(s), this.styles.push(i);
      }
    }), this.normalizeLists = !this.tags.some((i) => {
      if (!/^(ul|ol)\b/.test(i.tag) || !i.node)
        return false;
      let s = e.nodes[i.node];
      return s.contentMatch.matchType(s);
    });
  }
  /**
  Parse a document from the content of a DOM node.
  */
  parse(e, t = {}) {
    let r = new Da(this, t, false);
    return r.addAll(e, te.none, t.from, t.to), r.finish();
  }
  /**
  Parses the content of the given DOM node, like
  [`parse`](https://prosemirror.net/docs/ref/#model.DOMParser.parse), and takes the same set of
  options. But unlike that method, which produces a whole node,
  this one returns a slice that is open at the sides, meaning that
  the schema constraints aren't applied to the start of nodes to
  the left of the input and the end of nodes at the end.
  */
  parseSlice(e, t = {}) {
    let r = new Da(this, t, true);
    return r.addAll(e, te.none, t.from, t.to), O.maxOpen(r.finish());
  }
  /**
  @internal
  */
  matchTag(e, t, r) {
    for (let i = r ? this.tags.indexOf(r) + 1 : 0; i < this.tags.length; i++) {
      let s = this.tags[i];
      if (lp(e, s.tag) && (s.namespace === void 0 || e.namespaceURI == s.namespace) && (!s.context || t.matchesContext(s.context))) {
        if (s.getAttrs) {
          let o = s.getAttrs(e);
          if (o === false)
            continue;
          s.attrs = o || void 0;
        }
        return s;
      }
    }
  }
  /**
  @internal
  */
  matchStyle(e, t, r, i) {
    for (let s = i ? this.styles.indexOf(i) + 1 : 0; s < this.styles.length; s++) {
      let o = this.styles[s], l = o.style;
      if (!(l.indexOf(e) != 0 || o.context && !r.matchesContext(o.context) || // Test that the style string either precisely matches the prop,
      // or has an '=' sign after the prop, followed by the given
      // value.
      l.length > e.length && (l.charCodeAt(e.length) != 61 || l.slice(e.length + 1) != t))) {
        if (o.getAttrs) {
          let a = o.getAttrs(t);
          if (a === false)
            continue;
          o.attrs = a || void 0;
        }
        return o;
      }
    }
  }
  /**
  @internal
  */
  static schemaRules(e) {
    let t = [];
    function r(i) {
      let s = i.priority == null ? 50 : i.priority, o = 0;
      for (; o < t.length; o++) {
        let l = t[o];
        if ((l.priority == null ? 50 : l.priority) < s)
          break;
      }
      t.splice(o, 0, i);
    }
    for (let i in e.marks) {
      let s = e.marks[i].spec.parseDOM;
      s && s.forEach((o) => {
        r(o = La(o)), o.mark || o.ignore || o.clearMark || (o.mark = i);
      });
    }
    for (let i in e.nodes) {
      let s = e.nodes[i].spec.parseDOM;
      s && s.forEach((o) => {
        r(o = La(o)), o.node || o.ignore || o.mark || (o.node = i);
      });
    }
    return t;
  }
  /**
  Construct a DOM parser using the parsing rules listed in a
  schema's [node specs](https://prosemirror.net/docs/ref/#model.NodeSpec.parseDOM), reordered by
  [priority](https://prosemirror.net/docs/ref/#model.ParseRule.priority).
  */
  static fromSchema(e) {
    return e.cached.domParser || (e.cached.domParser = new Zt(e, Zt.schemaRules(e)));
  }
}
const Xu = {
  address: true,
  article: true,
  aside: true,
  blockquote: true,
  canvas: true,
  dd: true,
  div: true,
  dl: true,
  fieldset: true,
  figcaption: true,
  figure: true,
  footer: true,
  form: true,
  h1: true,
  h2: true,
  h3: true,
  h4: true,
  h5: true,
  h6: true,
  header: true,
  hgroup: true,
  hr: true,
  li: true,
  noscript: true,
  ol: true,
  output: true,
  p: true,
  pre: true,
  section: true,
  table: true,
  tfoot: true,
  ul: true
}, sp = {
  head: true,
  noscript: true,
  object: true,
  script: true,
  style: true,
  title: true
}, Qu = { ol: true, ul: true }, _r = 1, jo = 2, Or = 4;
function Na(n2, e, t) {
  return e != null ? (e ? _r : 0) | (e === "full" ? jo : 0) : n2 && n2.whitespace == "pre" ? _r | jo : t & ~Or;
}
class ki {
  constructor(e, t, r, i, s, o) {
    this.type = e, this.attrs = t, this.marks = r, this.solid = i, this.options = o, this.content = [], this.activeMarks = te.none, this.match = s || (o & Or ? null : e.contentMatch);
  }
  findWrapping(e) {
    if (!this.match) {
      if (!this.type)
        return [];
      let t = this.type.contentMatch.fillBefore(A.from(e));
      if (t)
        this.match = this.type.contentMatch.matchFragment(t);
      else {
        let r = this.type.contentMatch, i;
        return (i = r.findWrapping(e.type)) ? (this.match = r, i) : null;
      }
    }
    return this.match.findWrapping(e.type);
  }
  finish(e) {
    if (!(this.options & _r)) {
      let r = this.content[this.content.length - 1], i;
      if (r && r.isText && (i = /[ \t\r\n\u000c]+$/.exec(r.text))) {
        let s = r;
        r.text.length == i[0].length ? this.content.pop() : this.content[this.content.length - 1] = s.withText(s.text.slice(0, s.text.length - i[0].length));
      }
    }
    let t = A.from(this.content);
    return !e && this.match && (t = t.append(this.match.fillBefore(A.empty, true))), this.type ? this.type.create(this.attrs, t, this.marks) : t;
  }
  inlineContext(e) {
    return this.type ? this.type.inlineContent : this.content.length ? this.content[0].isInline : e.parentNode && !Xu.hasOwnProperty(e.parentNode.nodeName.toLowerCase());
  }
}
class Da {
  constructor(e, t, r) {
    this.parser = e, this.options = t, this.isOpen = r, this.open = 0, this.localPreserveWS = false;
    let i = t.topNode, s, o = Na(null, t.preserveWhitespace, 0) | (r ? Or : 0);
    i ? s = new ki(i.type, i.attrs, te.none, true, t.topMatch || i.type.contentMatch, o) : r ? s = new ki(null, null, te.none, true, null, o) : s = new ki(e.schema.topNodeType, null, te.none, true, null, o), this.nodes = [s], this.find = t.findPositions, this.needsBlock = false;
  }
  get top() {
    return this.nodes[this.open];
  }
  // Add a DOM node to the content. Text is inserted as text node,
  // otherwise, the node is passed to `addElement` or, if it has a
  // `style` attribute, `addElementWithStyles`.
  addDOM(e, t) {
    e.nodeType == 3 ? this.addTextNode(e, t) : e.nodeType == 1 && this.addElement(e, t);
  }
  addTextNode(e, t) {
    let r = e.nodeValue, i = this.top, s = i.options & jo ? "full" : this.localPreserveWS || (i.options & _r) > 0;
    if (s === "full" || i.inlineContext(e) || /[^ \t\r\n\u000c]/.test(r)) {
      if (s)
        s !== "full" ? r = r.replace(/\r?\n|\r/g, " ") : r = r.replace(/\r\n?/g, `
`);
      else if (r = r.replace(/[ \t\r\n\u000c]+/g, " "), /^[ \t\r\n\u000c]/.test(r) && this.open == this.nodes.length - 1) {
        let o = i.content[i.content.length - 1], l = e.previousSibling;
        (!o || l && l.nodeName == "BR" || o.isText && /[ \t\r\n\u000c]$/.test(o.text)) && (r = r.slice(1));
      }
      r && this.insertNode(this.parser.schema.text(r), t, !/\S/.test(r)), this.findInText(e);
    } else
      this.findInside(e);
  }
  // Try to find a handler for the given tag and use that to parse. If
  // none is found, the element's content nodes are added directly.
  addElement(e, t, r) {
    let i = this.localPreserveWS, s = this.top;
    (e.tagName == "PRE" || /pre/.test(e.style && e.style.whiteSpace)) && (this.localPreserveWS = true);
    let o = e.nodeName.toLowerCase(), l;
    Qu.hasOwnProperty(o) && this.parser.normalizeLists && op(e);
    let a = this.options.ruleFromNode && this.options.ruleFromNode(e) || (l = this.parser.matchTag(e, this, r));
    e: if (a ? a.ignore : sp.hasOwnProperty(o))
      this.findInside(e), this.ignoreFallback(e, t);
    else if (!a || a.skip || a.closeParent) {
      a && a.closeParent ? this.open = Math.max(0, this.open - 1) : a && a.skip.nodeType && (e = a.skip);
      let c, u = this.needsBlock;
      if (Xu.hasOwnProperty(o))
        s.content.length && s.content[0].isInline && this.open && (this.open--, s = this.top), c = true, s.type || (this.needsBlock = true);
      else if (!e.firstChild) {
        this.leafFallback(e, t);
        break e;
      }
      let d = a && a.skip ? t : this.readStyles(e, t);
      d && this.addAll(e, d), c && this.sync(s), this.needsBlock = u;
    } else {
      let c = this.readStyles(e, t);
      c && this.addElementByRule(e, a, c, a.consuming === false ? l : void 0);
    }
    this.localPreserveWS = i;
  }
  // Called for leaf DOM nodes that would otherwise be ignored
  leafFallback(e, t) {
    e.nodeName == "BR" && this.top.type && this.top.type.inlineContent && this.addTextNode(e.ownerDocument.createTextNode(`
`), t);
  }
  // Called for ignored nodes
  ignoreFallback(e, t) {
    e.nodeName == "BR" && (!this.top.type || !this.top.type.inlineContent) && this.findPlace(this.parser.schema.text("-"), t, true);
  }
  // Run any style parser associated with the node's styles. Either
  // return an updated array of marks, or null to indicate some of the
  // styles had a rule with `ignore` set.
  readStyles(e, t) {
    let r = e.style;
    if (r && r.length)
      for (let i = 0; i < this.parser.matchedStyles.length; i++) {
        let s = this.parser.matchedStyles[i], o = r.getPropertyValue(s);
        if (o)
          for (let l = void 0; ; ) {
            let a = this.parser.matchStyle(s, o, this, l);
            if (!a)
              break;
            if (a.ignore)
              return null;
            if (a.clearMark ? t = t.filter((c) => !a.clearMark(c)) : t = t.concat(this.parser.schema.marks[a.mark].create(a.attrs)), a.consuming === false)
              l = a;
            else
              break;
          }
      }
    return t;
  }
  // Look up a handler for the given node. If none are found, return
  // false. Otherwise, apply it, use its return value to drive the way
  // the node's content is wrapped, and return true.
  addElementByRule(e, t, r, i) {
    let s, o;
    if (t.node)
      if (o = this.parser.schema.nodes[t.node], o.isLeaf)
        this.insertNode(o.create(t.attrs), r, e.nodeName == "BR") || this.leafFallback(e, r);
      else {
        let a = this.enter(o, t.attrs || null, r, t.preserveWhitespace);
        a && (s = true, r = a);
      }
    else {
      let a = this.parser.schema.marks[t.mark];
      r = r.concat(a.create(t.attrs));
    }
    let l = this.top;
    if (o && o.isLeaf)
      this.findInside(e);
    else if (i)
      this.addElement(e, r, i);
    else if (t.getContent)
      this.findInside(e), t.getContent(e, this.parser.schema).forEach((a) => this.insertNode(a, r, false));
    else {
      let a = e;
      typeof t.contentElement == "string" ? a = e.querySelector(t.contentElement) : typeof t.contentElement == "function" ? a = t.contentElement(e) : t.contentElement && (a = t.contentElement), this.findAround(e, a, true), this.addAll(a, r), this.findAround(e, a, false);
    }
    s && this.sync(l) && this.open--;
  }
  // Add all child nodes between `startIndex` and `endIndex` (or the
  // whole node, if not given). If `sync` is passed, use it to
  // synchronize after every block element.
  addAll(e, t, r, i) {
    let s = r || 0;
    for (let o = r ? e.childNodes[r] : e.firstChild, l = i == null ? null : e.childNodes[i]; o != l; o = o.nextSibling, ++s)
      this.findAtPoint(e, s), this.addDOM(o, t);
    this.findAtPoint(e, s);
  }
  // Try to find a way to fit the given node type into the current
  // context. May add intermediate wrappers and/or leave non-solid
  // nodes that we're in.
  findPlace(e, t, r) {
    let i, s;
    for (let o = this.open, l = 0; o >= 0; o--) {
      let a = this.nodes[o], c = a.findWrapping(e);
      if (c && (!i || i.length > c.length + l) && (i = c, s = a, !c.length))
        break;
      if (a.solid) {
        if (r)
          break;
        l += 2;
      }
    }
    if (!i)
      return null;
    this.sync(s);
    for (let o = 0; o < i.length; o++)
      t = this.enterInner(i[o], null, t, false);
    return t;
  }
  // Try to insert the given node, adjusting the context when needed.
  insertNode(e, t, r) {
    if (e.isInline && this.needsBlock && !this.top.type) {
      let s = this.textblockFromContext();
      s && (t = this.enterInner(s, null, t));
    }
    let i = this.findPlace(e, t, r);
    if (i) {
      this.closeExtra();
      let s = this.top;
      s.match && (s.match = s.match.matchType(e.type));
      let o = te.none;
      for (let l of i.concat(e.marks))
        (s.type ? s.type.allowsMarkType(l.type) : Ra(l.type, e.type)) && (o = l.addToSet(o));
      return s.content.push(e.mark(o)), true;
    }
    return false;
  }
  // Try to start a node of the given type, adjusting the context when
  // necessary.
  enter(e, t, r, i) {
    let s = this.findPlace(e.create(t), r, false);
    return s && (s = this.enterInner(e, t, r, true, i)), s;
  }
  // Open a node of the given type
  enterInner(e, t, r, i = false, s) {
    this.closeExtra();
    let o = this.top;
    o.match = o.match && o.match.matchType(e);
    let l = Na(e, s, o.options);
    o.options & Or && o.content.length == 0 && (l |= Or);
    let a = te.none;
    return r = r.filter((c) => (o.type ? o.type.allowsMarkType(c.type) : Ra(c.type, e)) ? (a = c.addToSet(a), false) : true), this.nodes.push(new ki(e, t, a, i, null, l)), this.open++, r;
  }
  // Make sure all nodes above this.open are finished and added to
  // their parents
  closeExtra(e = false) {
    let t = this.nodes.length - 1;
    if (t > this.open) {
      for (; t > this.open; t--)
        this.nodes[t - 1].content.push(this.nodes[t].finish(e));
      this.nodes.length = this.open + 1;
    }
  }
  finish() {
    return this.open = 0, this.closeExtra(this.isOpen), this.nodes[0].finish(!!(this.isOpen || this.options.topOpen));
  }
  sync(e) {
    for (let t = this.open; t >= 0; t--) {
      if (this.nodes[t] == e)
        return this.open = t, true;
      this.localPreserveWS && (this.nodes[t].options |= _r);
    }
    return false;
  }
  get currentPos() {
    this.closeExtra();
    let e = 0;
    for (let t = this.open; t >= 0; t--) {
      let r = this.nodes[t].content;
      for (let i = r.length - 1; i >= 0; i--)
        e += r[i].nodeSize;
      t && e++;
    }
    return e;
  }
  findAtPoint(e, t) {
    if (this.find)
      for (let r = 0; r < this.find.length; r++)
        this.find[r].node == e && this.find[r].offset == t && (this.find[r].pos = this.currentPos);
  }
  findInside(e) {
    if (this.find)
      for (let t = 0; t < this.find.length; t++)
        this.find[t].pos == null && e.nodeType == 1 && e.contains(this.find[t].node) && (this.find[t].pos = this.currentPos);
  }
  findAround(e, t, r) {
    if (e != t && this.find)
      for (let i = 0; i < this.find.length; i++)
        this.find[i].pos == null && e.nodeType == 1 && e.contains(this.find[i].node) && t.compareDocumentPosition(this.find[i].node) & (r ? 2 : 4) && (this.find[i].pos = this.currentPos);
  }
  findInText(e) {
    if (this.find)
      for (let t = 0; t < this.find.length; t++)
        this.find[t].node == e && (this.find[t].pos = this.currentPos - (e.nodeValue.length - this.find[t].offset));
  }
  // Determines whether the given context string matches this context.
  matchesContext(e) {
    if (e.indexOf("|") > -1)
      return e.split(/\s*\|\s*/).some(this.matchesContext, this);
    let t = e.split("/"), r = this.options.context, i = !this.isOpen && (!r || r.parent.type == this.nodes[0].type), s = -(r ? r.depth + 1 : 0) + (i ? 0 : 1), o = (l, a) => {
      for (; l >= 0; l--) {
        let c = t[l];
        if (c == "") {
          if (l == t.length - 1 || l == 0)
            continue;
          for (; a >= s; a--)
            if (o(l - 1, a))
              return true;
          return false;
        } else {
          let u = a > 0 || a == 0 && i ? this.nodes[a].type : r && a >= s ? r.node(a - s).type : null;
          if (!u || u.name != c && !u.isInGroup(c))
            return false;
          a--;
        }
      }
      return true;
    };
    return o(t.length - 1, this.open);
  }
  textblockFromContext() {
    let e = this.options.context;
    if (e)
      for (let t = e.depth; t >= 0; t--) {
        let r = e.node(t).contentMatchAt(e.indexAfter(t)).defaultType;
        if (r && r.isTextblock && r.defaultAttrs)
          return r;
      }
    for (let t in this.parser.schema.nodes) {
      let r = this.parser.schema.nodes[t];
      if (r.isTextblock && r.defaultAttrs)
        return r;
    }
  }
}
function op(n2) {
  for (let e = n2.firstChild, t = null; e; e = e.nextSibling) {
    let r = e.nodeType == 1 ? e.nodeName.toLowerCase() : null;
    r && Qu.hasOwnProperty(r) && t ? (t.appendChild(e), e = t) : r == "li" ? t = e : r && (t = null);
  }
}
function lp(n2, e) {
  return (n2.matches || n2.msMatchesSelector || n2.webkitMatchesSelector || n2.mozMatchesSelector).call(n2, e);
}
function La(n2) {
  let e = {};
  for (let t in n2)
    e[t] = n2[t];
  return e;
}
function Ra(n2, e) {
  let t = e.schema.nodes;
  for (let r in t) {
    let i = t[r];
    if (!i.allowsMarkType(n2))
      continue;
    let s = [], o = (l) => {
      s.push(l);
      for (let a = 0; a < l.edgeCount; a++) {
        let { type: c, next: u } = l.edge(a);
        if (c == e || s.indexOf(u) < 0 && o(u))
          return true;
      }
    };
    if (o(i.contentMatch))
      return true;
  }
}
class Pn {
  /**
  Create a serializer. `nodes` should map node names to functions
  that take a node and return a description of the corresponding
  DOM. `marks` does the same for mark names, but also gets an
  argument that tells it whether the mark's content is block or
  inline content (for typical use, it'll always be inline). A mark
  serializer may be `null` to indicate that marks of that type
  should not be serialized.
  */
  constructor(e, t) {
    this.nodes = e, this.marks = t;
  }
  /**
  Serialize the content of this fragment to a DOM fragment. When
  not in the browser, the `document` option, containing a DOM
  document, should be passed so that the serializer can create
  nodes.
  */
  serializeFragment(e, t = {}, r) {
    r || (r = ao(t).createDocumentFragment());
    let i = r, s = [];
    return e.forEach((o) => {
      if (s.length || o.marks.length) {
        let l = 0, a = 0;
        for (; l < s.length && a < o.marks.length; ) {
          let c = o.marks[a];
          if (!this.marks[c.type.name]) {
            a++;
            continue;
          }
          if (!c.eq(s[l][0]) || c.type.spec.spanning === false)
            break;
          l++, a++;
        }
        for (; l < s.length; )
          i = s.pop()[1];
        for (; a < o.marks.length; ) {
          let c = o.marks[a++], u = this.serializeMark(c, o.isInline, t);
          u && (s.push([c, i]), i.appendChild(u.dom), i = u.contentDOM || u.dom);
        }
      }
      i.appendChild(this.serializeNodeInner(o, t));
    }), r;
  }
  /**
  @internal
  */
  serializeNodeInner(e, t) {
    let { dom: r, contentDOM: i } = Fi(ao(t), this.nodes[e.type.name](e), null, e.attrs);
    if (i) {
      if (e.isLeaf)
        throw new RangeError("Content hole not allowed in a leaf node spec");
      this.serializeFragment(e.content, t, i);
    }
    return r;
  }
  /**
  Serialize this node to a DOM node. This can be useful when you
  need to serialize a part of a document, as opposed to the whole
  document. To serialize a whole document, use
  [`serializeFragment`](https://prosemirror.net/docs/ref/#model.DOMSerializer.serializeFragment) on
  its [content](https://prosemirror.net/docs/ref/#model.Node.content).
  */
  serializeNode(e, t = {}) {
    let r = this.serializeNodeInner(e, t);
    for (let i = e.marks.length - 1; i >= 0; i--) {
      let s = this.serializeMark(e.marks[i], e.isInline, t);
      s && ((s.contentDOM || s.dom).appendChild(r), r = s.dom);
    }
    return r;
  }
  /**
  @internal
  */
  serializeMark(e, t, r = {}) {
    let i = this.marks[e.type.name];
    return i && Fi(ao(r), i(e, t), null, e.attrs);
  }
  static renderSpec(e, t, r = null, i) {
    return Fi(e, t, r, i);
  }
  /**
  Build a serializer using the [`toDOM`](https://prosemirror.net/docs/ref/#model.NodeSpec.toDOM)
  properties in a schema's node and mark specs.
  */
  static fromSchema(e) {
    return e.cached.domSerializer || (e.cached.domSerializer = new Pn(this.nodesFromSchema(e), this.marksFromSchema(e)));
  }
  /**
  Gather the serializers in a schema's node specs into an object.
  This can be useful as a base to build a custom serializer from.
  */
  static nodesFromSchema(e) {
    let t = Ia(e.nodes);
    return t.text || (t.text = (r) => r.text), t;
  }
  /**
  Gather the serializers in a schema's mark specs into an object.
  */
  static marksFromSchema(e) {
    return Ia(e.marks);
  }
}
function Ia(n2) {
  let e = {};
  for (let t in n2) {
    let r = n2[t].spec.toDOM;
    r && (e[t] = r);
  }
  return e;
}
function ao(n2) {
  return n2.document || window.document;
}
const Pa = /* @__PURE__ */ new WeakMap();
function ap(n2) {
  let e = Pa.get(n2);
  return e === void 0 && Pa.set(n2, e = cp(n2)), e;
}
function cp(n2) {
  let e = null;
  function t(r) {
    if (r && typeof r == "object")
      if (Array.isArray(r))
        if (typeof r[0] == "string")
          e || (e = []), e.push(r);
        else
          for (let i = 0; i < r.length; i++)
            t(r[i]);
      else
        for (let i in r)
          t(r[i]);
  }
  return t(n2), e;
}
function Fi(n2, e, t, r) {
  if (typeof e == "string")
    return { dom: n2.createTextNode(e) };
  if (e.nodeType != null)
    return { dom: e };
  if (e.dom && e.dom.nodeType != null)
    return e;
  let i = e[0], s;
  if (typeof i != "string")
    throw new RangeError("Invalid array passed to renderSpec");
  if (r && (s = ap(r)) && s.indexOf(e) > -1)
    throw new RangeError("Using an array from an attribute object as a DOM spec. This may be an attempted cross site scripting attack.");
  let o = i.indexOf(" ");
  o > 0 && (t = i.slice(0, o), i = i.slice(o + 1));
  let l, a = t ? n2.createElementNS(t, i) : n2.createElement(i), c = e[1], u = 1;
  if (c && typeof c == "object" && c.nodeType == null && !Array.isArray(c)) {
    u = 2;
    for (let d in c)
      if (c[d] != null) {
        let f = d.indexOf(" ");
        f > 0 ? a.setAttributeNS(d.slice(0, f), d.slice(f + 1), c[d]) : a.setAttribute(d, c[d]);
      }
  }
  for (let d = u; d < e.length; d++) {
    let f = e[d];
    if (f === 0) {
      if (d < e.length - 1 || d > u)
        throw new RangeError("Content hole must be the only child of its parent node");
      return { dom: a, contentDOM: a };
    } else {
      let { dom: h2, contentDOM: p2 } = Fi(n2, f, t, r);
      if (a.appendChild(h2), p2) {
        if (l)
          throw new RangeError("Multiple content holes");
        l = p2;
      }
    }
  }
  return { dom: a, contentDOM: l };
}
const Zu = 65535, ed = Math.pow(2, 16);
function up(n2, e) {
  return n2 + e * ed;
}
function Ba(n2) {
  return n2 & Zu;
}
function dp(n2) {
  return (n2 - (n2 & Zu)) / ed;
}
const td = 1, nd = 2, zi = 4, rd = 8;
class Wo {
  /**
  @internal
  */
  constructor(e, t, r) {
    this.pos = e, this.delInfo = t, this.recover = r;
  }
  /**
  Tells you whether the position was deleted, that is, whether the
  step removed the token on the side queried (via the `assoc`)
  argument from the document.
  */
  get deleted() {
    return (this.delInfo & rd) > 0;
  }
  /**
  Tells you whether the token before the mapped position was deleted.
  */
  get deletedBefore() {
    return (this.delInfo & (td | zi)) > 0;
  }
  /**
  True when the token after the mapped position was deleted.
  */
  get deletedAfter() {
    return (this.delInfo & (nd | zi)) > 0;
  }
  /**
  Tells whether any of the steps mapped through deletes across the
  position (including both the token before and after the
  position).
  */
  get deletedAcross() {
    return (this.delInfo & zi) > 0;
  }
}
class Ve {
  /**
  Create a position map. The modifications to the document are
  represented as an array of numbers, in which each group of three
  represents a modified chunk as `[start, oldSize, newSize]`.
  */
  constructor(e, t = false) {
    if (this.ranges = e, this.inverted = t, !e.length && Ve.empty)
      return Ve.empty;
  }
  /**
  @internal
  */
  recover(e) {
    let t = 0, r = Ba(e);
    if (!this.inverted)
      for (let i = 0; i < r; i++)
        t += this.ranges[i * 3 + 2] - this.ranges[i * 3 + 1];
    return this.ranges[r * 3] + t + dp(e);
  }
  mapResult(e, t = 1) {
    return this._map(e, t, false);
  }
  map(e, t = 1) {
    return this._map(e, t, true);
  }
  /**
  @internal
  */
  _map(e, t, r) {
    let i = 0, s = this.inverted ? 2 : 1, o = this.inverted ? 1 : 2;
    for (let l = 0; l < this.ranges.length; l += 3) {
      let a = this.ranges[l] - (this.inverted ? i : 0);
      if (a > e)
        break;
      let c = this.ranges[l + s], u = this.ranges[l + o], d = a + c;
      if (e <= d) {
        let f = c ? e == a ? -1 : e == d ? 1 : t : t, h2 = a + i + (f < 0 ? 0 : u);
        if (r)
          return h2;
        let p2 = e == (t < 0 ? a : d) ? null : up(l / 3, e - a), m = e == a ? nd : e == d ? td : zi;
        return (t < 0 ? e != a : e != d) && (m |= rd), new Wo(h2, m, p2);
      }
      i += u - c;
    }
    return r ? e + i : new Wo(e + i, 0, null);
  }
  /**
  @internal
  */
  touches(e, t) {
    let r = 0, i = Ba(t), s = this.inverted ? 2 : 1, o = this.inverted ? 1 : 2;
    for (let l = 0; l < this.ranges.length; l += 3) {
      let a = this.ranges[l] - (this.inverted ? r : 0);
      if (a > e)
        break;
      let c = this.ranges[l + s], u = a + c;
      if (e <= u && l == i * 3)
        return true;
      r += this.ranges[l + o] - c;
    }
    return false;
  }
  /**
  Calls the given function on each of the changed ranges included in
  this map.
  */
  forEach(e) {
    let t = this.inverted ? 2 : 1, r = this.inverted ? 1 : 2;
    for (let i = 0, s = 0; i < this.ranges.length; i += 3) {
      let o = this.ranges[i], l = o - (this.inverted ? s : 0), a = o + (this.inverted ? 0 : s), c = this.ranges[i + t], u = this.ranges[i + r];
      e(l, l + c, a, a + u), s += u - c;
    }
  }
  /**
  Create an inverted version of this map. The result can be used to
  map positions in the post-step document to the pre-step document.
  */
  invert() {
    return new Ve(this.ranges, !this.inverted);
  }
  /**
  @internal
  */
  toString() {
    return (this.inverted ? "-" : "") + JSON.stringify(this.ranges);
  }
  /**
  Create a map that moves all positions by offset `n` (which may be
  negative). This can be useful when applying steps meant for a
  sub-document to a larger document, or vice-versa.
  */
  static offset(e) {
    return e == 0 ? Ve.empty : new Ve(e < 0 ? [0, -e, 0] : [0, 0, e]);
  }
}
Ve.empty = new Ve([]);
class jr {
  /**
  Create a new mapping with the given position maps.
  */
  constructor(e, t, r = 0, i = e ? e.length : 0) {
    this.mirror = t, this.from = r, this.to = i, this._maps = e || [], this.ownData = !(e || t);
  }
  /**
  The step maps in this mapping.
  */
  get maps() {
    return this._maps;
  }
  /**
  Create a mapping that maps only through a part of this one.
  */
  slice(e = 0, t = this.maps.length) {
    return new jr(this._maps, this.mirror, e, t);
  }
  /**
  Add a step map to the end of this mapping. If `mirrors` is
  given, it should be the index of the step map that is the mirror
  image of this one.
  */
  appendMap(e, t) {
    this.ownData || (this._maps = this._maps.slice(), this.mirror = this.mirror && this.mirror.slice(), this.ownData = true), this.to = this._maps.push(e), t != null && this.setMirror(this._maps.length - 1, t);
  }
  /**
  Add all the step maps in a given mapping to this one (preserving
  mirroring information).
  */
  appendMapping(e) {
    for (let t = 0, r = this._maps.length; t < e._maps.length; t++) {
      let i = e.getMirror(t);
      this.appendMap(e._maps[t], i != null && i < t ? r + i : void 0);
    }
  }
  /**
  Finds the offset of the step map that mirrors the map at the
  given offset, in this mapping (as per the second argument to
  `appendMap`).
  */
  getMirror(e) {
    if (this.mirror) {
      for (let t = 0; t < this.mirror.length; t++)
        if (this.mirror[t] == e)
          return this.mirror[t + (t % 2 ? -1 : 1)];
    }
  }
  /**
  @internal
  */
  setMirror(e, t) {
    this.mirror || (this.mirror = []), this.mirror.push(e, t);
  }
  /**
  Append the inverse of the given mapping to this one.
  */
  appendMappingInverted(e) {
    for (let t = e.maps.length - 1, r = this._maps.length + e._maps.length; t >= 0; t--) {
      let i = e.getMirror(t);
      this.appendMap(e._maps[t].invert(), i != null && i > t ? r - i - 1 : void 0);
    }
  }
  /**
  Create an inverted version of this mapping.
  */
  invert() {
    let e = new jr();
    return e.appendMappingInverted(this), e;
  }
  /**
  Map a position through this mapping.
  */
  map(e, t = 1) {
    if (this.mirror)
      return this._map(e, t, true);
    for (let r = this.from; r < this.to; r++)
      e = this._maps[r].map(e, t);
    return e;
  }
  /**
  Map a position through this mapping, returning a mapping
  result.
  */
  mapResult(e, t = 1) {
    return this._map(e, t, false);
  }
  /**
  @internal
  */
  _map(e, t, r) {
    let i = 0;
    for (let s = this.from; s < this.to; s++) {
      let o = this._maps[s], l = o.mapResult(e, t);
      if (l.recover != null) {
        let a = this.getMirror(s);
        if (a != null && a > s && a < this.to) {
          s = a, e = this._maps[a].recover(l.recover);
          continue;
        }
      }
      i |= l.delInfo, e = l.pos;
    }
    return r ? e : new Wo(e, i, null);
  }
}
const co = /* @__PURE__ */ Object.create(null);
class Me {
  /**
  Get the step map that represents the changes made by this step,
  and which can be used to transform between positions in the old
  and the new document.
  */
  getMap() {
    return Ve.empty;
  }
  /**
  Try to merge this step with another one, to be applied directly
  after it. Returns the merged step when possible, null if the
  steps can't be merged.
  */
  merge(e) {
    return null;
  }
  /**
  Deserialize a step from its JSON representation. Will call
  through to the step class' own implementation of this method.
  */
  static fromJSON(e, t) {
    if (!t || !t.stepType)
      throw new RangeError("Invalid input for Step.fromJSON");
    let r = co[t.stepType];
    if (!r)
      throw new RangeError(`No step type ${t.stepType} defined`);
    return r.fromJSON(e, t);
  }
  /**
  To be able to serialize steps to JSON, each step needs a string
  ID to attach to its JSON representation. Use this method to
  register an ID for your step classes. Try to pick something
  that's unlikely to clash with steps from other modules.
  */
  static jsonID(e, t) {
    if (e in co)
      throw new RangeError("Duplicate use of step JSON ID " + e);
    return co[e] = t, t.prototype.jsonID = e, t;
  }
}
class de {
  /**
  @internal
  */
  constructor(e, t) {
    this.doc = e, this.failed = t;
  }
  /**
  Create a successful step result.
  */
  static ok(e) {
    return new de(e, null);
  }
  /**
  Create a failed step result.
  */
  static fail(e) {
    return new de(null, e);
  }
  /**
  Call [`Node.replace`](https://prosemirror.net/docs/ref/#model.Node.replace) with the given
  arguments. Create a successful result if it succeeds, and a
  failed one if it throws a `ReplaceError`.
  */
  static fromReplace(e, t, r, i) {
    try {
      return de.ok(e.replace(t, r, i));
    } catch (s) {
      if (s instanceof Ji)
        return de.fail(s.message);
      throw s;
    }
  }
}
function Ll(n2, e, t) {
  let r = [];
  for (let i = 0; i < n2.childCount; i++) {
    let s = n2.child(i);
    s.content.size && (s = s.copy(Ll(s.content, e, s))), s.isInline && (s = e(s, t, i)), r.push(s);
  }
  return A.fromArray(r);
}
class Jt extends Me {
  /**
  Create a mark step.
  */
  constructor(e, t, r) {
    super(), this.from = e, this.to = t, this.mark = r;
  }
  apply(e) {
    let t = e.slice(this.from, this.to), r = e.resolve(this.from), i = r.node(r.sharedDepth(this.to)), s = new O(Ll(t.content, (o, l) => !o.isAtom || !l.type.allowsMarkType(this.mark.type) ? o : o.mark(this.mark.addToSet(o.marks)), i), t.openStart, t.openEnd);
    return de.fromReplace(e, this.from, this.to, s);
  }
  invert() {
    return new ht(this.from, this.to, this.mark);
  }
  map(e) {
    let t = e.mapResult(this.from, 1), r = e.mapResult(this.to, -1);
    return t.deleted && r.deleted || t.pos >= r.pos ? null : new Jt(t.pos, r.pos, this.mark);
  }
  merge(e) {
    return e instanceof Jt && e.mark.eq(this.mark) && this.from <= e.to && this.to >= e.from ? new Jt(Math.min(this.from, e.from), Math.max(this.to, e.to), this.mark) : null;
  }
  toJSON() {
    return {
      stepType: "addMark",
      mark: this.mark.toJSON(),
      from: this.from,
      to: this.to
    };
  }
  /**
  @internal
  */
  static fromJSON(e, t) {
    if (typeof t.from != "number" || typeof t.to != "number")
      throw new RangeError("Invalid input for AddMarkStep.fromJSON");
    return new Jt(t.from, t.to, e.markFromJSON(t.mark));
  }
}
Me.jsonID("addMark", Jt);
class ht extends Me {
  /**
  Create a mark-removing step.
  */
  constructor(e, t, r) {
    super(), this.from = e, this.to = t, this.mark = r;
  }
  apply(e) {
    let t = e.slice(this.from, this.to), r = new O(Ll(t.content, (i) => i.mark(this.mark.removeFromSet(i.marks)), e), t.openStart, t.openEnd);
    return de.fromReplace(e, this.from, this.to, r);
  }
  invert() {
    return new Jt(this.from, this.to, this.mark);
  }
  map(e) {
    let t = e.mapResult(this.from, 1), r = e.mapResult(this.to, -1);
    return t.deleted && r.deleted || t.pos >= r.pos ? null : new ht(t.pos, r.pos, this.mark);
  }
  merge(e) {
    return e instanceof ht && e.mark.eq(this.mark) && this.from <= e.to && this.to >= e.from ? new ht(Math.min(this.from, e.from), Math.max(this.to, e.to), this.mark) : null;
  }
  toJSON() {
    return {
      stepType: "removeMark",
      mark: this.mark.toJSON(),
      from: this.from,
      to: this.to
    };
  }
  /**
  @internal
  */
  static fromJSON(e, t) {
    if (typeof t.from != "number" || typeof t.to != "number")
      throw new RangeError("Invalid input for RemoveMarkStep.fromJSON");
    return new ht(t.from, t.to, e.markFromJSON(t.mark));
  }
}
Me.jsonID("removeMark", ht);
class Gt extends Me {
  /**
  Create a node mark step.
  */
  constructor(e, t) {
    super(), this.pos = e, this.mark = t;
  }
  apply(e) {
    let t = e.nodeAt(this.pos);
    if (!t)
      return de.fail("No node at mark step's position");
    let r = t.type.create(t.attrs, null, this.mark.addToSet(t.marks));
    return de.fromReplace(e, this.pos, this.pos + 1, new O(A.from(r), 0, t.isLeaf ? 0 : 1));
  }
  invert(e) {
    let t = e.nodeAt(this.pos);
    if (t) {
      let r = this.mark.addToSet(t.marks);
      if (r.length == t.marks.length) {
        for (let i = 0; i < t.marks.length; i++)
          if (!t.marks[i].isInSet(r))
            return new Gt(this.pos, t.marks[i]);
        return new Gt(this.pos, this.mark);
      }
    }
    return new On(this.pos, this.mark);
  }
  map(e) {
    let t = e.mapResult(this.pos, 1);
    return t.deletedAfter ? null : new Gt(t.pos, this.mark);
  }
  toJSON() {
    return { stepType: "addNodeMark", pos: this.pos, mark: this.mark.toJSON() };
  }
  /**
  @internal
  */
  static fromJSON(e, t) {
    if (typeof t.pos != "number")
      throw new RangeError("Invalid input for AddNodeMarkStep.fromJSON");
    return new Gt(t.pos, e.markFromJSON(t.mark));
  }
}
Me.jsonID("addNodeMark", Gt);
class On extends Me {
  /**
  Create a mark-removing step.
  */
  constructor(e, t) {
    super(), this.pos = e, this.mark = t;
  }
  apply(e) {
    let t = e.nodeAt(this.pos);
    if (!t)
      return de.fail("No node at mark step's position");
    let r = t.type.create(t.attrs, null, this.mark.removeFromSet(t.marks));
    return de.fromReplace(e, this.pos, this.pos + 1, new O(A.from(r), 0, t.isLeaf ? 0 : 1));
  }
  invert(e) {
    let t = e.nodeAt(this.pos);
    return !t || !this.mark.isInSet(t.marks) ? this : new Gt(this.pos, this.mark);
  }
  map(e) {
    let t = e.mapResult(this.pos, 1);
    return t.deletedAfter ? null : new On(t.pos, this.mark);
  }
  toJSON() {
    return { stepType: "removeNodeMark", pos: this.pos, mark: this.mark.toJSON() };
  }
  /**
  @internal
  */
  static fromJSON(e, t) {
    if (typeof t.pos != "number")
      throw new RangeError("Invalid input for RemoveNodeMarkStep.fromJSON");
    return new On(t.pos, e.markFromJSON(t.mark));
  }
}
Me.jsonID("removeNodeMark", On);
class he extends Me {
  /**
  The given `slice` should fit the 'gap' between `from` and
  `to`—the depths must line up, and the surrounding nodes must be
  able to be joined with the open sides of the slice. When
  `structure` is true, the step will fail if the content between
  from and to is not just a sequence of closing and then opening
  tokens (this is to guard against rebased replace steps
  overwriting something they weren't supposed to).
  */
  constructor(e, t, r, i = false) {
    super(), this.from = e, this.to = t, this.slice = r, this.structure = i;
  }
  apply(e) {
    return this.structure && Uo(e, this.from, this.to) ? de.fail("Structure replace would overwrite content") : de.fromReplace(e, this.from, this.to, this.slice);
  }
  getMap() {
    return new Ve([this.from, this.to - this.from, this.slice.size]);
  }
  invert(e) {
    return new he(this.from, this.from + this.slice.size, e.slice(this.from, this.to));
  }
  map(e) {
    let t = e.mapResult(this.from, 1), r = e.mapResult(this.to, -1);
    return t.deletedAcross && r.deletedAcross ? null : new he(t.pos, Math.max(t.pos, r.pos), this.slice, this.structure);
  }
  merge(e) {
    if (!(e instanceof he) || e.structure || this.structure)
      return null;
    if (this.from + this.slice.size == e.from && !this.slice.openEnd && !e.slice.openStart) {
      let t = this.slice.size + e.slice.size == 0 ? O.empty : new O(this.slice.content.append(e.slice.content), this.slice.openStart, e.slice.openEnd);
      return new he(this.from, this.to + (e.to - e.from), t, this.structure);
    } else if (e.to == this.from && !this.slice.openStart && !e.slice.openEnd) {
      let t = this.slice.size + e.slice.size == 0 ? O.empty : new O(e.slice.content.append(this.slice.content), e.slice.openStart, this.slice.openEnd);
      return new he(e.from, this.to, t, this.structure);
    } else
      return null;
  }
  toJSON() {
    let e = { stepType: "replace", from: this.from, to: this.to };
    return this.slice.size && (e.slice = this.slice.toJSON()), this.structure && (e.structure = true), e;
  }
  /**
  @internal
  */
  static fromJSON(e, t) {
    if (typeof t.from != "number" || typeof t.to != "number")
      throw new RangeError("Invalid input for ReplaceStep.fromJSON");
    return new he(t.from, t.to, O.fromJSON(e, t.slice), !!t.structure);
  }
}
Me.jsonID("replace", he);
class pe extends Me {
  /**
  Create a replace-around step with the given range and gap.
  `insert` should be the point in the slice into which the content
  of the gap should be moved. `structure` has the same meaning as
  it has in the [`ReplaceStep`](https://prosemirror.net/docs/ref/#transform.ReplaceStep) class.
  */
  constructor(e, t, r, i, s, o, l = false) {
    super(), this.from = e, this.to = t, this.gapFrom = r, this.gapTo = i, this.slice = s, this.insert = o, this.structure = l;
  }
  apply(e) {
    if (this.structure && (Uo(e, this.from, this.gapFrom) || Uo(e, this.gapTo, this.to)))
      return de.fail("Structure gap-replace would overwrite content");
    let t = e.slice(this.gapFrom, this.gapTo);
    if (t.openStart || t.openEnd)
      return de.fail("Gap is not a flat range");
    let r = this.slice.insertAt(this.insert, t.content);
    return r ? de.fromReplace(e, this.from, this.to, r) : de.fail("Content does not fit in gap");
  }
  getMap() {
    return new Ve([
      this.from,
      this.gapFrom - this.from,
      this.insert,
      this.gapTo,
      this.to - this.gapTo,
      this.slice.size - this.insert
    ]);
  }
  invert(e) {
    let t = this.gapTo - this.gapFrom;
    return new pe(this.from, this.from + this.slice.size + t, this.from + this.insert, this.from + this.insert + t, e.slice(this.from, this.to).removeBetween(this.gapFrom - this.from, this.gapTo - this.from), this.gapFrom - this.from, this.structure);
  }
  map(e) {
    let t = e.mapResult(this.from, 1), r = e.mapResult(this.to, -1), i = this.from == this.gapFrom ? t.pos : e.map(this.gapFrom, -1), s = this.to == this.gapTo ? r.pos : e.map(this.gapTo, 1);
    return t.deletedAcross && r.deletedAcross || i < t.pos || s > r.pos ? null : new pe(t.pos, r.pos, i, s, this.slice, this.insert, this.structure);
  }
  toJSON() {
    let e = {
      stepType: "replaceAround",
      from: this.from,
      to: this.to,
      gapFrom: this.gapFrom,
      gapTo: this.gapTo,
      insert: this.insert
    };
    return this.slice.size && (e.slice = this.slice.toJSON()), this.structure && (e.structure = true), e;
  }
  /**
  @internal
  */
  static fromJSON(e, t) {
    if (typeof t.from != "number" || typeof t.to != "number" || typeof t.gapFrom != "number" || typeof t.gapTo != "number" || typeof t.insert != "number")
      throw new RangeError("Invalid input for ReplaceAroundStep.fromJSON");
    return new pe(t.from, t.to, t.gapFrom, t.gapTo, O.fromJSON(e, t.slice), t.insert, !!t.structure);
  }
}
Me.jsonID("replaceAround", pe);
function Uo(n2, e, t) {
  let r = n2.resolve(e), i = t - e, s = r.depth;
  for (; i > 0 && s > 0 && r.indexAfter(s) == r.node(s).childCount; )
    s--, i--;
  if (i > 0) {
    let o = r.node(s).maybeChild(r.indexAfter(s));
    for (; i > 0; ) {
      if (!o || o.isLeaf)
        return true;
      o = o.firstChild, i--;
    }
  }
  return false;
}
function fp(n2, e, t, r) {
  let i = [], s = [], o, l;
  n2.doc.nodesBetween(e, t, (a, c, u) => {
    if (!a.isInline)
      return;
    let d = a.marks;
    if (!r.isInSet(d) && u.type.allowsMarkType(r.type)) {
      let f = Math.max(c, e), h2 = Math.min(c + a.nodeSize, t), p2 = r.addToSet(d);
      for (let m = 0; m < d.length; m++)
        d[m].isInSet(p2) || (o && o.to == f && o.mark.eq(d[m]) ? o.to = h2 : i.push(o = new ht(f, h2, d[m])));
      l && l.to == f ? l.to = h2 : s.push(l = new Jt(f, h2, r));
    }
  }), i.forEach((a) => n2.step(a)), s.forEach((a) => n2.step(a));
}
function hp(n2, e, t, r) {
  let i = [], s = 0;
  n2.doc.nodesBetween(e, t, (o, l) => {
    if (!o.isInline)
      return;
    s++;
    let a = null;
    if (r instanceof Vs) {
      let c = o.marks, u;
      for (; u = r.isInSet(c); )
        (a || (a = [])).push(u), c = u.removeFromSet(c);
    } else r ? r.isInSet(o.marks) && (a = [r]) : a = o.marks;
    if (a && a.length) {
      let c = Math.min(l + o.nodeSize, t);
      for (let u = 0; u < a.length; u++) {
        let d = a[u], f;
        for (let h2 = 0; h2 < i.length; h2++) {
          let p2 = i[h2];
          p2.step == s - 1 && d.eq(i[h2].style) && (f = p2);
        }
        f ? (f.to = c, f.step = s) : i.push({ style: d, from: Math.max(l, e), to: c, step: s });
      }
    }
  }), i.forEach((o) => n2.step(new ht(o.from, o.to, o.style)));
}
function Rl(n2, e, t, r = t.contentMatch, i = true) {
  let s = n2.doc.nodeAt(e), o = [], l = e + 1;
  for (let a = 0; a < s.childCount; a++) {
    let c = s.child(a), u = l + c.nodeSize, d = r.matchType(c.type);
    if (!d)
      o.push(new he(l, u, O.empty));
    else {
      r = d;
      for (let f = 0; f < c.marks.length; f++)
        t.allowsMarkType(c.marks[f].type) || n2.step(new ht(l, u, c.marks[f]));
      if (i && c.isText && t.whitespace != "pre") {
        let f, h2 = /\r?\n|\r/g, p2;
        for (; f = h2.exec(c.text); )
          p2 || (p2 = new O(A.from(t.schema.text(" ", t.allowedMarks(c.marks))), 0, 0)), o.push(new he(l + f.index, l + f.index + f[0].length, p2));
      }
    }
    l = u;
  }
  if (!r.validEnd) {
    let a = r.fillBefore(A.empty, true);
    n2.replace(l, l, new O(a, 0, 0));
  }
  for (let a = o.length - 1; a >= 0; a--)
    n2.step(o[a]);
}
function pp(n2, e, t) {
  return (e == 0 || n2.canReplace(e, n2.childCount)) && (t == n2.childCount || n2.canReplace(0, t));
}
function hr(n2) {
  let t = n2.parent.content.cutByIndex(n2.startIndex, n2.endIndex);
  for (let r = n2.depth; ; --r) {
    let i = n2.$from.node(r), s = n2.$from.index(r), o = n2.$to.indexAfter(r);
    if (r < n2.depth && i.canReplace(s, o, t))
      return r;
    if (r == 0 || i.type.spec.isolating || !pp(i, s, o))
      break;
  }
  return null;
}
function mp(n2, e, t) {
  let { $from: r, $to: i, depth: s } = e, o = r.before(s + 1), l = i.after(s + 1), a = o, c = l, u = A.empty, d = 0;
  for (let p2 = s, m = false; p2 > t; p2--)
    m || r.index(p2) > 0 ? (m = true, u = A.from(r.node(p2).copy(u)), d++) : a--;
  let f = A.empty, h2 = 0;
  for (let p2 = s, m = false; p2 > t; p2--)
    m || i.after(p2 + 1) < i.end(p2) ? (m = true, f = A.from(i.node(p2).copy(f)), h2++) : c++;
  n2.step(new pe(a, c, o, l, new O(u.append(f), d, h2), u.size - d, true));
}
function Il(n2, e, t = null, r = n2) {
  let i = gp(n2, e), s = i && yp(r, e);
  return s ? i.map(Ha).concat({ type: e, attrs: t }).concat(s.map(Ha)) : null;
}
function Ha(n2) {
  return { type: n2, attrs: null };
}
function gp(n2, e) {
  let { parent: t, startIndex: r, endIndex: i } = n2, s = t.contentMatchAt(r).findWrapping(e);
  if (!s)
    return null;
  let o = s.length ? s[0] : e;
  return t.canReplaceWith(r, i, o) ? s : null;
}
function yp(n2, e) {
  let { parent: t, startIndex: r, endIndex: i } = n2, s = t.child(r), o = e.contentMatch.findWrapping(s.type);
  if (!o)
    return null;
  let a = (o.length ? o[o.length - 1] : e).contentMatch;
  for (let c = r; a && c < i; c++)
    a = a.matchType(t.child(c).type);
  return !a || !a.validEnd ? null : o;
}
function bp(n2, e, t) {
  let r = A.empty;
  for (let o = t.length - 1; o >= 0; o--) {
    if (r.size) {
      let l = t[o].type.contentMatch.matchFragment(r);
      if (!l || !l.validEnd)
        throw new RangeError("Wrapper type given to Transform.wrap does not form valid content of its parent wrapper");
    }
    r = A.from(t[o].type.create(t[o].attrs, r));
  }
  let i = e.start, s = e.end;
  n2.step(new pe(i, s, i, s, new O(r, 0, 0), t.length, true));
}
function vp(n2, e, t, r, i) {
  if (!r.isTextblock)
    throw new RangeError("Type given to setBlockType should be a textblock");
  let s = n2.steps.length;
  n2.doc.nodesBetween(e, t, (o, l) => {
    let a = typeof i == "function" ? i(o) : i;
    if (o.isTextblock && !o.hasMarkup(r, a) && wp(n2.doc, n2.mapping.slice(s).map(l), r)) {
      let c = null;
      if (r.schema.linebreakReplacement) {
        let h2 = r.whitespace == "pre", p2 = !!r.contentMatch.matchType(r.schema.linebreakReplacement);
        h2 && !p2 ? c = false : !h2 && p2 && (c = true);
      }
      c === false && sd(n2, o, l, s), Rl(n2, n2.mapping.slice(s).map(l, 1), r, void 0, c === null);
      let u = n2.mapping.slice(s), d = u.map(l, 1), f = u.map(l + o.nodeSize, 1);
      return n2.step(new pe(d, f, d + 1, f - 1, new O(A.from(r.create(a, null, o.marks)), 0, 0), 1, true)), c === true && id(n2, o, l, s), false;
    }
  });
}
function id(n2, e, t, r) {
  e.forEach((i, s) => {
    if (i.isText) {
      let o, l = /\r?\n|\r/g;
      for (; o = l.exec(i.text); ) {
        let a = n2.mapping.slice(r).map(t + 1 + s + o.index);
        n2.replaceWith(a, a + 1, e.type.schema.linebreakReplacement.create());
      }
    }
  });
}
function sd(n2, e, t, r) {
  e.forEach((i, s) => {
    if (i.type == i.type.schema.linebreakReplacement) {
      let o = n2.mapping.slice(r).map(t + 1 + s);
      n2.replaceWith(o, o + 1, e.type.schema.text(`
`));
    }
  });
}
function wp(n2, e, t) {
  let r = n2.resolve(e), i = r.index();
  return r.parent.canReplaceWith(i, i + 1, t);
}
function kp(n2, e, t, r, i) {
  let s = n2.doc.nodeAt(e);
  if (!s)
    throw new RangeError("No node at given position");
  t || (t = s.type);
  let o = t.create(r, null, i || s.marks);
  if (s.isLeaf)
    return n2.replaceWith(e, e + s.nodeSize, o);
  if (!t.validContent(s.content))
    throw new RangeError("Invalid content for node type " + t.name);
  n2.step(new pe(e, e + s.nodeSize, e + 1, e + s.nodeSize - 1, new O(A.from(o), 0, 0), 1, true));
}
function Rt(n2, e, t = 1, r) {
  let i = n2.resolve(e), s = i.depth - t, o = r && r[r.length - 1] || i.parent;
  if (s < 0 || i.parent.type.spec.isolating || !i.parent.canReplace(i.index(), i.parent.childCount) || !o.type.validContent(i.parent.content.cutByIndex(i.index(), i.parent.childCount)))
    return false;
  for (let c = i.depth - 1, u = t - 2; c > s; c--, u--) {
    let d = i.node(c), f = i.index(c);
    if (d.type.spec.isolating)
      return false;
    let h2 = d.content.cutByIndex(f, d.childCount), p2 = r && r[u + 1];
    p2 && (h2 = h2.replaceChild(0, p2.type.create(p2.attrs)));
    let m = r && r[u] || d;
    if (!d.canReplace(f + 1, d.childCount) || !m.type.validContent(h2))
      return false;
  }
  let l = i.indexAfter(s), a = r && r[0];
  return i.node(s).canReplaceWith(l, l, a ? a.type : i.node(s + 1).type);
}
function Cp(n2, e, t = 1, r) {
  let i = n2.doc.resolve(e), s = A.empty, o = A.empty;
  for (let l = i.depth, a = i.depth - t, c = t - 1; l > a; l--, c--) {
    s = A.from(i.node(l).copy(s));
    let u = r && r[c];
    o = A.from(u ? u.type.create(u.attrs, o) : i.node(l).copy(o));
  }
  n2.step(new he(e, e, new O(s.append(o), t, t), true));
}
function sn(n2, e) {
  let t = n2.resolve(e), r = t.index();
  return od(t.nodeBefore, t.nodeAfter) && t.parent.canReplace(r, r + 1);
}
function xp(n2, e) {
  e.content.size || n2.type.compatibleContent(e.type);
  let t = n2.contentMatchAt(n2.childCount), { linebreakReplacement: r } = n2.type.schema;
  for (let i = 0; i < e.childCount; i++) {
    let s = e.child(i), o = s.type == r ? n2.type.schema.nodes.text : s.type;
    if (t = t.matchType(o), !t || !n2.type.allowsMarks(s.marks))
      return false;
  }
  return t.validEnd;
}
function od(n2, e) {
  return !!(n2 && e && !n2.isLeaf && xp(n2, e));
}
function $s(n2, e, t = -1) {
  let r = n2.resolve(e);
  for (let i = r.depth; ; i--) {
    let s, o, l = r.index(i);
    if (i == r.depth ? (s = r.nodeBefore, o = r.nodeAfter) : t > 0 ? (s = r.node(i + 1), l++, o = r.node(i).maybeChild(l)) : (s = r.node(i).maybeChild(l - 1), o = r.node(i + 1)), s && !s.isTextblock && od(s, o) && r.node(i).canReplace(l, l + 1))
      return e;
    if (i == 0)
      break;
    e = t < 0 ? r.before(i) : r.after(i);
  }
}
function Sp(n2, e, t) {
  let r = null, { linebreakReplacement: i } = n2.doc.type.schema, s = n2.doc.resolve(e - t), o = s.node().type;
  if (i && o.inlineContent) {
    let u = o.whitespace == "pre", d = !!o.contentMatch.matchType(i);
    u && !d ? r = false : !u && d && (r = true);
  }
  let l = n2.steps.length;
  if (r === false) {
    let u = n2.doc.resolve(e + t);
    sd(n2, u.node(), u.before(), l);
  }
  o.inlineContent && Rl(n2, e + t - 1, o, s.node().contentMatchAt(s.index()), r == null);
  let a = n2.mapping.slice(l), c = a.map(e - t);
  if (n2.step(new he(c, a.map(e + t, -1), O.empty, true)), r === true) {
    let u = n2.doc.resolve(c);
    id(n2, u.node(), u.before(), n2.steps.length);
  }
  return n2;
}
function Mp(n2, e, t) {
  let r = n2.resolve(e);
  if (r.parent.canReplaceWith(r.index(), r.index(), t))
    return e;
  if (r.parentOffset == 0)
    for (let i = r.depth - 1; i >= 0; i--) {
      let s = r.index(i);
      if (r.node(i).canReplaceWith(s, s, t))
        return r.before(i + 1);
      if (s > 0)
        return null;
    }
  if (r.parentOffset == r.parent.content.size)
    for (let i = r.depth - 1; i >= 0; i--) {
      let s = r.indexAfter(i);
      if (r.node(i).canReplaceWith(s, s, t))
        return r.after(i + 1);
      if (s < r.node(i).childCount)
        return null;
    }
  return null;
}
function ld(n2, e, t) {
  let r = n2.resolve(e);
  if (!t.content.size)
    return e;
  let i = t.content;
  for (let s = 0; s < t.openStart; s++)
    i = i.firstChild.content;
  for (let s = 1; s <= (t.openStart == 0 && t.size ? 2 : 1); s++)
    for (let o = r.depth; o >= 0; o--) {
      let l = o == r.depth ? 0 : r.pos <= (r.start(o + 1) + r.end(o + 1)) / 2 ? -1 : 1, a = r.index(o) + (l > 0 ? 1 : 0), c = r.node(o), u = false;
      if (s == 1)
        u = c.canReplace(a, a, i);
      else {
        let d = c.contentMatchAt(a).findWrapping(i.firstChild.type);
        u = d && c.canReplaceWith(a, a, d[0]);
      }
      if (u)
        return l == 0 ? r.pos : l < 0 ? r.before(o + 1) : r.after(o + 1);
    }
  return null;
}
function _s(n2, e, t = e, r = O.empty) {
  if (e == t && !r.size)
    return null;
  let i = n2.resolve(e), s = n2.resolve(t);
  return ad(i, s, r) ? new he(e, t, r) : new Ap(i, s, r).fit();
}
function ad(n2, e, t) {
  return !t.openStart && !t.openEnd && n2.start() == e.start() && n2.parent.canReplace(n2.index(), e.index(), t.content);
}
class Ap {
  constructor(e, t, r) {
    this.$from = e, this.$to = t, this.unplaced = r, this.frontier = [], this.placed = A.empty;
    for (let i = 0; i <= e.depth; i++) {
      let s = e.node(i);
      this.frontier.push({
        type: s.type,
        match: s.contentMatchAt(e.indexAfter(i))
      });
    }
    for (let i = e.depth; i > 0; i--)
      this.placed = A.from(e.node(i).copy(this.placed));
  }
  get depth() {
    return this.frontier.length - 1;
  }
  fit() {
    for (; this.unplaced.size; ) {
      let c = this.findFittable();
      c ? this.placeNodes(c) : this.openMore() || this.dropNode();
    }
    let e = this.mustMoveInline(), t = this.placed.size - this.depth - this.$from.depth, r = this.$from, i = this.close(e < 0 ? this.$to : r.doc.resolve(e));
    if (!i)
      return null;
    let s = this.placed, o = r.depth, l = i.depth;
    for (; o && l && s.childCount == 1; )
      s = s.firstChild.content, o--, l--;
    let a = new O(s, o, l);
    return e > -1 ? new pe(r.pos, e, this.$to.pos, this.$to.end(), a, t) : a.size || r.pos != this.$to.pos ? new he(r.pos, i.pos, a) : null;
  }
  // Find a position on the start spine of `this.unplaced` that has
  // content that can be moved somewhere on the frontier. Returns two
  // depths, one for the slice and one for the frontier.
  findFittable() {
    let e = this.unplaced.openStart;
    for (let t = this.unplaced.content, r = 0, i = this.unplaced.openEnd; r < e; r++) {
      let s = t.firstChild;
      if (t.childCount > 1 && (i = 0), s.type.spec.isolating && i <= r) {
        e = r;
        break;
      }
      t = s.content;
    }
    for (let t = 1; t <= 2; t++)
      for (let r = t == 1 ? e : this.unplaced.openStart; r >= 0; r--) {
        let i, s = null;
        r ? (s = uo(this.unplaced.content, r - 1).firstChild, i = s.content) : i = this.unplaced.content;
        let o = i.firstChild;
        for (let l = this.depth; l >= 0; l--) {
          let { type: a, match: c } = this.frontier[l], u, d = null;
          if (t == 1 && (o ? c.matchType(o.type) || (d = c.fillBefore(A.from(o), false)) : s && a.compatibleContent(s.type)))
            return { sliceDepth: r, frontierDepth: l, parent: s, inject: d };
          if (t == 2 && o && (u = c.findWrapping(o.type)))
            return { sliceDepth: r, frontierDepth: l, parent: s, wrap: u };
          if (s && c.matchType(s.type))
            break;
        }
      }
  }
  openMore() {
    let { content: e, openStart: t, openEnd: r } = this.unplaced, i = uo(e, t);
    return !i.childCount || i.firstChild.isLeaf ? false : (this.unplaced = new O(e, t + 1, Math.max(r, i.size + t >= e.size - r ? t + 1 : 0)), true);
  }
  dropNode() {
    let { content: e, openStart: t, openEnd: r } = this.unplaced, i = uo(e, t);
    if (i.childCount <= 1 && t > 0) {
      let s = e.size - t <= t + i.size;
      this.unplaced = new O(Sr(e, t - 1, 1), t - 1, s ? t - 1 : r);
    } else
      this.unplaced = new O(Sr(e, t, 1), t, r);
  }
  // Move content from the unplaced slice at `sliceDepth` to the
  // frontier node at `frontierDepth`. Close that frontier node when
  // applicable.
  placeNodes({ sliceDepth: e, frontierDepth: t, parent: r, inject: i, wrap: s }) {
    for (; this.depth > t; )
      this.closeFrontierNode();
    if (s)
      for (let m = 0; m < s.length; m++)
        this.openFrontierNode(s[m]);
    let o = this.unplaced, l = r ? r.content : o.content, a = o.openStart - e, c = 0, u = [], { match: d, type: f } = this.frontier[t];
    if (i) {
      for (let m = 0; m < i.childCount; m++)
        u.push(i.child(m));
      d = d.matchFragment(i);
    }
    let h2 = l.size + e - (o.content.size - o.openEnd);
    for (; c < l.childCount; ) {
      let m = l.child(c), g = d.matchType(m.type);
      if (!g)
        break;
      c++, (c > 1 || a == 0 || m.content.size) && (d = g, u.push(cd(m.mark(f.allowedMarks(m.marks)), c == 1 ? a : 0, c == l.childCount ? h2 : -1)));
    }
    let p2 = c == l.childCount;
    p2 || (h2 = -1), this.placed = Mr(this.placed, t, A.from(u)), this.frontier[t].match = d, p2 && h2 < 0 && r && r.type == this.frontier[this.depth].type && this.frontier.length > 1 && this.closeFrontierNode();
    for (let m = 0, g = l; m < h2; m++) {
      let y = g.lastChild;
      this.frontier.push({ type: y.type, match: y.contentMatchAt(y.childCount) }), g = y.content;
    }
    this.unplaced = p2 ? e == 0 ? O.empty : new O(Sr(o.content, e - 1, 1), e - 1, h2 < 0 ? o.openEnd : e - 1) : new O(Sr(o.content, e, c), o.openStart, o.openEnd);
  }
  mustMoveInline() {
    if (!this.$to.parent.isTextblock)
      return -1;
    let e = this.frontier[this.depth], t;
    if (!e.type.isTextblock || !fo(this.$to, this.$to.depth, e.type, e.match, false) || this.$to.depth == this.depth && (t = this.findCloseLevel(this.$to)) && t.depth == this.depth)
      return -1;
    let { depth: r } = this.$to, i = this.$to.after(r);
    for (; r > 1 && i == this.$to.end(--r); )
      ++i;
    return i;
  }
  findCloseLevel(e) {
    e: for (let t = Math.min(this.depth, e.depth); t >= 0; t--) {
      let { match: r, type: i } = this.frontier[t], s = t < e.depth && e.end(t + 1) == e.pos + (e.depth - (t + 1)), o = fo(e, t, i, r, s);
      if (o) {
        for (let l = t - 1; l >= 0; l--) {
          let { match: a, type: c } = this.frontier[l], u = fo(e, l, c, a, true);
          if (!u || u.childCount)
            continue e;
        }
        return { depth: t, fit: o, move: s ? e.doc.resolve(e.after(t + 1)) : e };
      }
    }
  }
  close(e) {
    let t = this.findCloseLevel(e);
    if (!t)
      return null;
    for (; this.depth > t.depth; )
      this.closeFrontierNode();
    t.fit.childCount && (this.placed = Mr(this.placed, t.depth, t.fit)), e = t.move;
    for (let r = t.depth + 1; r <= e.depth; r++) {
      let i = e.node(r), s = i.type.contentMatch.fillBefore(i.content, true, e.index(r));
      this.openFrontierNode(i.type, i.attrs, s);
    }
    return e;
  }
  openFrontierNode(e, t = null, r) {
    let i = this.frontier[this.depth];
    i.match = i.match.matchType(e), this.placed = Mr(this.placed, this.depth, A.from(e.create(t, r))), this.frontier.push({ type: e, match: e.contentMatch });
  }
  closeFrontierNode() {
    let t = this.frontier.pop().match.fillBefore(A.empty, true);
    t.childCount && (this.placed = Mr(this.placed, this.frontier.length, t));
  }
}
function Sr(n2, e, t) {
  return e == 0 ? n2.cutByIndex(t, n2.childCount) : n2.replaceChild(0, n2.firstChild.copy(Sr(n2.firstChild.content, e - 1, t)));
}
function Mr(n2, e, t) {
  return e == 0 ? n2.append(t) : n2.replaceChild(n2.childCount - 1, n2.lastChild.copy(Mr(n2.lastChild.content, e - 1, t)));
}
function uo(n2, e) {
  for (let t = 0; t < e; t++)
    n2 = n2.firstChild.content;
  return n2;
}
function cd(n2, e, t) {
  if (e <= 0)
    return n2;
  let r = n2.content;
  return e > 1 && (r = r.replaceChild(0, cd(r.firstChild, e - 1, r.childCount == 1 ? t - 1 : 0))), e > 0 && (r = n2.type.contentMatch.fillBefore(r).append(r), t <= 0 && (r = r.append(n2.type.contentMatch.matchFragment(r).fillBefore(A.empty, true)))), n2.copy(r);
}
function fo(n2, e, t, r, i) {
  let s = n2.node(e), o = i ? n2.indexAfter(e) : n2.index(e);
  if (o == s.childCount && !t.compatibleContent(s.type))
    return null;
  let l = r.fillBefore(s.content, true, o);
  return l && !Ep(t, s.content, o) ? l : null;
}
function Ep(n2, e, t) {
  for (let r = t; r < e.childCount; r++)
    if (!n2.allowsMarks(e.child(r).marks))
      return true;
  return false;
}
function Tp(n2) {
  return n2.spec.defining || n2.spec.definingForContent;
}
function Op(n2, e, t, r) {
  if (!r.size)
    return n2.deleteRange(e, t);
  let i = n2.doc.resolve(e), s = n2.doc.resolve(t);
  if (ad(i, s, r))
    return n2.step(new he(e, t, r));
  let o = dd(i, n2.doc.resolve(t));
  o[o.length - 1] == 0 && o.pop();
  let l = -(i.depth + 1);
  o.unshift(l);
  for (let f = i.depth, h2 = i.pos - 1; f > 0; f--, h2--) {
    let p2 = i.node(f).type.spec;
    if (p2.defining || p2.definingAsContext || p2.isolating)
      break;
    o.indexOf(f) > -1 ? l = f : i.before(f) == h2 && o.splice(1, 0, -f);
  }
  let a = o.indexOf(l), c = [], u = r.openStart;
  for (let f = r.content, h2 = 0; ; h2++) {
    let p2 = f.firstChild;
    if (c.push(p2), h2 == r.openStart)
      break;
    f = p2.content;
  }
  for (let f = u - 1; f >= 0; f--) {
    let h2 = c[f], p2 = Tp(h2.type);
    if (p2 && !h2.sameMarkup(i.node(Math.abs(l) - 1)))
      u = f;
    else if (p2 || !h2.type.isTextblock)
      break;
  }
  for (let f = r.openStart; f >= 0; f--) {
    let h2 = (f + u + 1) % (r.openStart + 1), p2 = c[h2];
    if (p2)
      for (let m = 0; m < o.length; m++) {
        let g = o[(m + a) % o.length], y = true;
        g < 0 && (y = false, g = -g);
        let w = i.node(g - 1), C = i.index(g - 1);
        if (w.canReplaceWith(C, C, p2.type, p2.marks))
          return n2.replace(i.before(g), y ? s.after(g) : t, new O(ud(r.content, 0, r.openStart, h2), h2, r.openEnd));
      }
  }
  let d = n2.steps.length;
  for (let f = o.length - 1; f >= 0 && (n2.replace(e, t, r), !(n2.steps.length > d)); f--) {
    let h2 = o[f];
    h2 < 0 || (e = i.before(h2), t = s.after(h2));
  }
}
function ud(n2, e, t, r, i) {
  if (e < t) {
    let s = n2.firstChild;
    n2 = n2.replaceChild(0, s.copy(ud(s.content, e + 1, t, r, s)));
  }
  if (e > r) {
    let s = i.contentMatchAt(0), o = s.fillBefore(n2).append(n2);
    n2 = o.append(s.matchFragment(o).fillBefore(A.empty, true));
  }
  return n2;
}
function Np(n2, e, t, r) {
  if (!r.isInline && e == t && n2.doc.resolve(e).parent.content.size) {
    let i = Mp(n2.doc, e, r.type);
    i != null && (e = t = i);
  }
  n2.replaceRange(e, t, new O(A.from(r), 0, 0));
}
function Dp(n2, e, t) {
  let r = n2.doc.resolve(e), i = n2.doc.resolve(t), s = dd(r, i);
  for (let o = 0; o < s.length; o++) {
    let l = s[o], a = o == s.length - 1;
    if (a && l == 0 || r.node(l).type.contentMatch.validEnd)
      return n2.delete(r.start(l), i.end(l));
    if (l > 0 && (a || r.node(l - 1).canReplace(r.index(l - 1), i.indexAfter(l - 1))))
      return n2.delete(r.before(l), i.after(l));
  }
  for (let o = 1; o <= r.depth && o <= i.depth; o++)
    if (e - r.start(o) == r.depth - o && t > r.end(o) && i.end(o) - t != i.depth - o && r.start(o - 1) == i.start(o - 1) && r.node(o - 1).canReplace(r.index(o - 1), i.index(o - 1)))
      return n2.delete(r.before(o), t);
  n2.delete(e, t);
}
function dd(n2, e) {
  let t = [], r = Math.min(n2.depth, e.depth);
  for (let i = r; i >= 0; i--) {
    let s = n2.start(i);
    if (s < n2.pos - (n2.depth - i) || e.end(i) > e.pos + (e.depth - i) || n2.node(i).type.spec.isolating || e.node(i).type.spec.isolating)
      break;
    (s == e.start(i) || i == n2.depth && i == e.depth && n2.parent.inlineContent && e.parent.inlineContent && i && e.start(i - 1) == s - 1) && t.push(i);
  }
  return t;
}
class Zn extends Me {
  /**
  Construct an attribute step.
  */
  constructor(e, t, r) {
    super(), this.pos = e, this.attr = t, this.value = r;
  }
  apply(e) {
    let t = e.nodeAt(this.pos);
    if (!t)
      return de.fail("No node at attribute step's position");
    let r = /* @__PURE__ */ Object.create(null);
    for (let s in t.attrs)
      r[s] = t.attrs[s];
    r[this.attr] = this.value;
    let i = t.type.create(r, null, t.marks);
    return de.fromReplace(e, this.pos, this.pos + 1, new O(A.from(i), 0, t.isLeaf ? 0 : 1));
  }
  getMap() {
    return Ve.empty;
  }
  invert(e) {
    return new Zn(this.pos, this.attr, e.nodeAt(this.pos).attrs[this.attr]);
  }
  map(e) {
    let t = e.mapResult(this.pos, 1);
    return t.deletedAfter ? null : new Zn(t.pos, this.attr, this.value);
  }
  toJSON() {
    return { stepType: "attr", pos: this.pos, attr: this.attr, value: this.value };
  }
  static fromJSON(e, t) {
    if (typeof t.pos != "number" || typeof t.attr != "string")
      throw new RangeError("Invalid input for AttrStep.fromJSON");
    return new Zn(t.pos, t.attr, t.value);
  }
}
Me.jsonID("attr", Zn);
class Wr extends Me {
  /**
  Construct an attribute step.
  */
  constructor(e, t) {
    super(), this.attr = e, this.value = t;
  }
  apply(e) {
    let t = /* @__PURE__ */ Object.create(null);
    for (let i in e.attrs)
      t[i] = e.attrs[i];
    t[this.attr] = this.value;
    let r = e.type.create(t, e.content, e.marks);
    return de.ok(r);
  }
  getMap() {
    return Ve.empty;
  }
  invert(e) {
    return new Wr(this.attr, e.attrs[this.attr]);
  }
  map(e) {
    return this;
  }
  toJSON() {
    return { stepType: "docAttr", attr: this.attr, value: this.value };
  }
  static fromJSON(e, t) {
    if (typeof t.attr != "string")
      throw new RangeError("Invalid input for DocAttrStep.fromJSON");
    return new Wr(t.attr, t.value);
  }
}
Me.jsonID("docAttr", Wr);
let tr = class extends Error {
};
tr = function n(e) {
  let t = Error.call(this, e);
  return t.__proto__ = n.prototype, t;
};
tr.prototype = Object.create(Error.prototype);
tr.prototype.constructor = tr;
tr.prototype.name = "TransformError";
class Pl {
  /**
  Create a transform that starts with the given document.
  */
  constructor(e) {
    this.doc = e, this.steps = [], this.docs = [], this.mapping = new jr();
  }
  /**
  The starting document.
  */
  get before() {
    return this.docs.length ? this.docs[0] : this.doc;
  }
  /**
  Apply a new step in this transform, saving the result. Throws an
  error when the step fails.
  */
  step(e) {
    let t = this.maybeStep(e);
    if (t.failed)
      throw new tr(t.failed);
    return this;
  }
  /**
  Try to apply a step in this transformation, ignoring it if it
  fails. Returns the step result.
  */
  maybeStep(e) {
    let t = e.apply(this.doc);
    return t.failed || this.addStep(e, t.doc), t;
  }
  /**
  True when the document has been changed (when there are any
  steps).
  */
  get docChanged() {
    return this.steps.length > 0;
  }
  /**
  @internal
  */
  addStep(e, t) {
    this.docs.push(this.doc), this.steps.push(e), this.mapping.appendMap(e.getMap()), this.doc = t;
  }
  /**
  Replace the part of the document between `from` and `to` with the
  given `slice`.
  */
  replace(e, t = e, r = O.empty) {
    let i = _s(this.doc, e, t, r);
    return i && this.step(i), this;
  }
  /**
  Replace the given range with the given content, which may be a
  fragment, node, or array of nodes.
  */
  replaceWith(e, t, r) {
    return this.replace(e, t, new O(A.from(r), 0, 0));
  }
  /**
  Delete the content between the given positions.
  */
  delete(e, t) {
    return this.replace(e, t, O.empty);
  }
  /**
  Insert the given content at the given position.
  */
  insert(e, t) {
    return this.replaceWith(e, e, t);
  }
  /**
  Replace a range of the document with a given slice, using
  `from`, `to`, and the slice's
  [`openStart`](https://prosemirror.net/docs/ref/#model.Slice.openStart) property as hints, rather
  than fixed start and end points. This method may grow the
  replaced area or close open nodes in the slice in order to get a
  fit that is more in line with WYSIWYG expectations, by dropping
  fully covered parent nodes of the replaced region when they are
  marked [non-defining as
  context](https://prosemirror.net/docs/ref/#model.NodeSpec.definingAsContext), or including an
  open parent node from the slice that _is_ marked as [defining
  its content](https://prosemirror.net/docs/ref/#model.NodeSpec.definingForContent).
  
  This is the method, for example, to handle paste. The similar
  [`replace`](https://prosemirror.net/docs/ref/#transform.Transform.replace) method is a more
  primitive tool which will _not_ move the start and end of its given
  range, and is useful in situations where you need more precise
  control over what happens.
  */
  replaceRange(e, t, r) {
    return Op(this, e, t, r), this;
  }
  /**
  Replace the given range with a node, but use `from` and `to` as
  hints, rather than precise positions. When from and to are the same
  and are at the start or end of a parent node in which the given
  node doesn't fit, this method may _move_ them out towards a parent
  that does allow the given node to be placed. When the given range
  completely covers a parent node, this method may completely replace
  that parent node.
  */
  replaceRangeWith(e, t, r) {
    return Np(this, e, t, r), this;
  }
  /**
  Delete the given range, expanding it to cover fully covered
  parent nodes until a valid replace is found.
  */
  deleteRange(e, t) {
    return Dp(this, e, t), this;
  }
  /**
  Split the content in the given range off from its parent, if there
  is sibling content before or after it, and move it up the tree to
  the depth specified by `target`. You'll probably want to use
  [`liftTarget`](https://prosemirror.net/docs/ref/#transform.liftTarget) to compute `target`, to make
  sure the lift is valid.
  */
  lift(e, t) {
    return mp(this, e, t), this;
  }
  /**
  Join the blocks around the given position. If depth is 2, their
  last and first siblings are also joined, and so on.
  */
  join(e, t = 1) {
    return Sp(this, e, t), this;
  }
  /**
  Wrap the given [range](https://prosemirror.net/docs/ref/#model.NodeRange) in the given set of wrappers.
  The wrappers are assumed to be valid in this position, and should
  probably be computed with [`findWrapping`](https://prosemirror.net/docs/ref/#transform.findWrapping).
  */
  wrap(e, t) {
    return bp(this, e, t), this;
  }
  /**
  Set the type of all textblocks (partly) between `from` and `to` to
  the given node type with the given attributes.
  */
  setBlockType(e, t = e, r, i = null) {
    return vp(this, e, t, r, i), this;
  }
  /**
  Change the type, attributes, and/or marks of the node at `pos`.
  When `type` isn't given, the existing node type is preserved,
  */
  setNodeMarkup(e, t, r = null, i) {
    return kp(this, e, t, r, i), this;
  }
  /**
  Set a single attribute on a given node to a new value.
  The `pos` addresses the document content. Use `setDocAttribute`
  to set attributes on the document itself.
  */
  setNodeAttribute(e, t, r) {
    return this.step(new Zn(e, t, r)), this;
  }
  /**
  Set a single attribute on the document to a new value.
  */
  setDocAttribute(e, t) {
    return this.step(new Wr(e, t)), this;
  }
  /**
  Add a mark to the node at position `pos`.
  */
  addNodeMark(e, t) {
    return this.step(new Gt(e, t)), this;
  }
  /**
  Remove a mark (or all marks of the given type) from the node at
  position `pos`.
  */
  removeNodeMark(e, t) {
    let r = this.doc.nodeAt(e);
    if (!r)
      throw new RangeError("No node at position " + e);
    if (t instanceof te)
      t.isInSet(r.marks) && this.step(new On(e, t));
    else {
      let i = r.marks, s, o = [];
      for (; s = t.isInSet(i); )
        o.push(new On(e, s)), i = s.removeFromSet(i);
      for (let l = o.length - 1; l >= 0; l--)
        this.step(o[l]);
    }
    return this;
  }
  /**
  Split the node at the given position, and optionally, if `depth` is
  greater than one, any number of nodes above that. By default, the
  parts split off will inherit the node type of the original node.
  This can be changed by passing an array of types and attributes to
  use after the split (with the outermost nodes coming first).
  */
  split(e, t = 1, r) {
    return Cp(this, e, t, r), this;
  }
  /**
  Add the given mark to the inline content between `from` and `to`.
  */
  addMark(e, t, r) {
    return fp(this, e, t, r), this;
  }
  /**
  Remove marks from inline nodes between `from` and `to`. When
  `mark` is a single mark, remove precisely that mark. When it is
  a mark type, remove all marks of that type. When it is null,
  remove all marks of any type.
  */
  removeMark(e, t, r) {
    return hp(this, e, t, r), this;
  }
  /**
  Removes all marks and nodes from the content of the node at
  `pos` that don't match the given new parent node type. Accepts
  an optional starting [content match](https://prosemirror.net/docs/ref/#model.ContentMatch) as
  third argument.
  */
  clearIncompatible(e, t, r) {
    return Rl(this, e, t, r), this;
  }
}
const ho = /* @__PURE__ */ Object.create(null);
class $ {
  /**
  Initialize a selection with the head and anchor and ranges. If no
  ranges are given, constructs a single range across `$anchor` and
  `$head`.
  */
  constructor(e, t, r) {
    this.$anchor = e, this.$head = t, this.ranges = r || [new fd(e.min(t), e.max(t))];
  }
  /**
  The selection's anchor, as an unresolved position.
  */
  get anchor() {
    return this.$anchor.pos;
  }
  /**
  The selection's head.
  */
  get head() {
    return this.$head.pos;
  }
  /**
  The lower bound of the selection's main range.
  */
  get from() {
    return this.$from.pos;
  }
  /**
  The upper bound of the selection's main range.
  */
  get to() {
    return this.$to.pos;
  }
  /**
  The resolved lower  bound of the selection's main range.
  */
  get $from() {
    return this.ranges[0].$from;
  }
  /**
  The resolved upper bound of the selection's main range.
  */
  get $to() {
    return this.ranges[0].$to;
  }
  /**
  Indicates whether the selection contains any content.
  */
  get empty() {
    let e = this.ranges;
    for (let t = 0; t < e.length; t++)
      if (e[t].$from.pos != e[t].$to.pos)
        return false;
    return true;
  }
  /**
  Get the content of this selection as a slice.
  */
  content() {
    return this.$from.doc.slice(this.from, this.to, true);
  }
  /**
  Replace the selection with a slice or, if no slice is given,
  delete the selection. Will append to the given transaction.
  */
  replace(e, t = O.empty) {
    let r = t.content.lastChild, i = null;
    for (let l = 0; l < t.openEnd; l++)
      i = r, r = r.lastChild;
    let s = e.steps.length, o = this.ranges;
    for (let l = 0; l < o.length; l++) {
      let { $from: a, $to: c } = o[l], u = e.mapping.slice(s);
      e.replaceRange(u.map(a.pos), u.map(c.pos), l ? O.empty : t), l == 0 && Va(e, s, (r ? r.isInline : i && i.isTextblock) ? -1 : 1);
    }
  }
  /**
  Replace the selection with the given node, appending the changes
  to the given transaction.
  */
  replaceWith(e, t) {
    let r = e.steps.length, i = this.ranges;
    for (let s = 0; s < i.length; s++) {
      let { $from: o, $to: l } = i[s], a = e.mapping.slice(r), c = a.map(o.pos), u = a.map(l.pos);
      s ? e.deleteRange(c, u) : (e.replaceRangeWith(c, u, t), Va(e, r, t.isInline ? -1 : 1));
    }
  }
  /**
  Find a valid cursor or leaf node selection starting at the given
  position and searching back if `dir` is negative, and forward if
  positive. When `textOnly` is true, only consider cursor
  selections. Will return null when no valid selection position is
  found.
  */
  static findFrom(e, t, r = false) {
    let i = e.parent.inlineContent ? new F(e) : qn(e.node(0), e.parent, e.pos, e.index(), t, r);
    if (i)
      return i;
    for (let s = e.depth - 1; s >= 0; s--) {
      let o = t < 0 ? qn(e.node(0), e.node(s), e.before(s + 1), e.index(s), t, r) : qn(e.node(0), e.node(s), e.after(s + 1), e.index(s) + 1, t, r);
      if (o)
        return o;
    }
    return null;
  }
  /**
  Find a valid cursor or leaf node selection near the given
  position. Searches forward first by default, but if `bias` is
  negative, it will search backwards first.
  */
  static near(e, t = 1) {
    return this.findFrom(e, t) || this.findFrom(e, -t) || new je(e.node(0));
  }
  /**
  Find the cursor or leaf node selection closest to the start of
  the given document. Will return an
  [`AllSelection`](https://prosemirror.net/docs/ref/#state.AllSelection) if no valid position
  exists.
  */
  static atStart(e) {
    return qn(e, e, 0, 0, 1) || new je(e);
  }
  /**
  Find the cursor or leaf node selection closest to the end of the
  given document.
  */
  static atEnd(e) {
    return qn(e, e, e.content.size, e.childCount, -1) || new je(e);
  }
  /**
  Deserialize the JSON representation of a selection. Must be
  implemented for custom classes (as a static class method).
  */
  static fromJSON(e, t) {
    if (!t || !t.type)
      throw new RangeError("Invalid input for Selection.fromJSON");
    let r = ho[t.type];
    if (!r)
      throw new RangeError(`No selection type ${t.type} defined`);
    return r.fromJSON(e, t);
  }
  /**
  To be able to deserialize selections from JSON, custom selection
  classes must register themselves with an ID string, so that they
  can be disambiguated. Try to pick something that's unlikely to
  clash with classes from other modules.
  */
  static jsonID(e, t) {
    if (e in ho)
      throw new RangeError("Duplicate use of selection JSON ID " + e);
    return ho[e] = t, t.prototype.jsonID = e, t;
  }
  /**
  Get a [bookmark](https://prosemirror.net/docs/ref/#state.SelectionBookmark) for this selection,
  which is a value that can be mapped without having access to a
  current document, and later resolved to a real selection for a
  given document again. (This is used mostly by the history to
  track and restore old selections.) The default implementation of
  this method just converts the selection to a text selection and
  returns the bookmark for that.
  */
  getBookmark() {
    return F.between(this.$anchor, this.$head).getBookmark();
  }
}
$.prototype.visible = true;
class fd {
  /**
  Create a range.
  */
  constructor(e, t) {
    this.$from = e, this.$to = t;
  }
}
let Fa = false;
function za(n2) {
  !Fa && !n2.parent.inlineContent && (Fa = true, console.warn("TextSelection endpoint not pointing into a node with inline content (" + n2.parent.type.name + ")"));
}
class F extends $ {
  /**
  Construct a text selection between the given points.
  */
  constructor(e, t = e) {
    za(e), za(t), super(e, t);
  }
  /**
  Returns a resolved position if this is a cursor selection (an
  empty text selection), and null otherwise.
  */
  get $cursor() {
    return this.$anchor.pos == this.$head.pos ? this.$head : null;
  }
  map(e, t) {
    let r = e.resolve(t.map(this.head));
    if (!r.parent.inlineContent)
      return $.near(r);
    let i = e.resolve(t.map(this.anchor));
    return new F(i.parent.inlineContent ? i : r, r);
  }
  replace(e, t = O.empty) {
    if (super.replace(e, t), t == O.empty) {
      let r = this.$from.marksAcross(this.$to);
      r && e.ensureMarks(r);
    }
  }
  eq(e) {
    return e instanceof F && e.anchor == this.anchor && e.head == this.head;
  }
  getBookmark() {
    return new js(this.anchor, this.head);
  }
  toJSON() {
    return { type: "text", anchor: this.anchor, head: this.head };
  }
  /**
  @internal
  */
  static fromJSON(e, t) {
    if (typeof t.anchor != "number" || typeof t.head != "number")
      throw new RangeError("Invalid input for TextSelection.fromJSON");
    return new F(e.resolve(t.anchor), e.resolve(t.head));
  }
  /**
  Create a text selection from non-resolved positions.
  */
  static create(e, t, r = t) {
    let i = e.resolve(t);
    return new this(i, r == t ? i : e.resolve(r));
  }
  /**
  Return a text selection that spans the given positions or, if
  they aren't text positions, find a text selection near them.
  `bias` determines whether the method searches forward (default)
  or backwards (negative number) first. Will fall back to calling
  [`Selection.near`](https://prosemirror.net/docs/ref/#state.Selection^near) when the document
  doesn't contain a valid text position.
  */
  static between(e, t, r) {
    let i = e.pos - t.pos;
    if ((!r || i) && (r = i >= 0 ? 1 : -1), !t.parent.inlineContent) {
      let s = $.findFrom(t, r, true) || $.findFrom(t, -r, true);
      if (s)
        t = s.$head;
      else
        return $.near(t, r);
    }
    return e.parent.inlineContent || (i == 0 ? e = t : (e = ($.findFrom(e, -r, true) || $.findFrom(e, r, true)).$anchor, e.pos < t.pos != i < 0 && (e = t))), new F(e, t);
  }
}
$.jsonID("text", F);
class js {
  constructor(e, t) {
    this.anchor = e, this.head = t;
  }
  map(e) {
    return new js(e.map(this.anchor), e.map(this.head));
  }
  resolve(e) {
    return F.between(e.resolve(this.anchor), e.resolve(this.head));
  }
}
class B extends $ {
  /**
  Create a node selection. Does not verify the validity of its
  argument.
  */
  constructor(e) {
    let t = e.nodeAfter, r = e.node(0).resolve(e.pos + t.nodeSize);
    super(e, r), this.node = t;
  }
  map(e, t) {
    let { deleted: r, pos: i } = t.mapResult(this.anchor), s = e.resolve(i);
    return r ? $.near(s) : new B(s);
  }
  content() {
    return new O(A.from(this.node), 0, 0);
  }
  eq(e) {
    return e instanceof B && e.anchor == this.anchor;
  }
  toJSON() {
    return { type: "node", anchor: this.anchor };
  }
  getBookmark() {
    return new Bl(this.anchor);
  }
  /**
  @internal
  */
  static fromJSON(e, t) {
    if (typeof t.anchor != "number")
      throw new RangeError("Invalid input for NodeSelection.fromJSON");
    return new B(e.resolve(t.anchor));
  }
  /**
  Create a node selection from non-resolved positions.
  */
  static create(e, t) {
    return new B(e.resolve(t));
  }
  /**
  Determines whether the given node may be selected as a node
  selection.
  */
  static isSelectable(e) {
    return !e.isText && e.type.spec.selectable !== false;
  }
}
B.prototype.visible = false;
$.jsonID("node", B);
class Bl {
  constructor(e) {
    this.anchor = e;
  }
  map(e) {
    let { deleted: t, pos: r } = e.mapResult(this.anchor);
    return t ? new js(r, r) : new Bl(r);
  }
  resolve(e) {
    let t = e.resolve(this.anchor), r = t.nodeAfter;
    return r && B.isSelectable(r) ? new B(t) : $.near(t);
  }
}
class je extends $ {
  /**
  Create an all-selection over the given document.
  */
  constructor(e) {
    super(e.resolve(0), e.resolve(e.content.size));
  }
  replace(e, t = O.empty) {
    if (t == O.empty) {
      e.delete(0, e.doc.content.size);
      let r = $.atStart(e.doc);
      r.eq(e.selection) || e.setSelection(r);
    } else
      super.replace(e, t);
  }
  toJSON() {
    return { type: "all" };
  }
  /**
  @internal
  */
  static fromJSON(e) {
    return new je(e);
  }
  map(e) {
    return new je(e);
  }
  eq(e) {
    return e instanceof je;
  }
  getBookmark() {
    return Lp;
  }
}
$.jsonID("all", je);
const Lp = {
  map() {
    return this;
  },
  resolve(n2) {
    return new je(n2);
  }
};
function qn(n2, e, t, r, i, s = false) {
  if (e.inlineContent)
    return F.create(n2, t);
  for (let o = r - (i > 0 ? 0 : 1); i > 0 ? o < e.childCount : o >= 0; o += i) {
    let l = e.child(o);
    if (l.isAtom) {
      if (!s && B.isSelectable(l))
        return B.create(n2, t - (i < 0 ? l.nodeSize : 0));
    } else {
      let a = qn(n2, l, t + i, i < 0 ? l.childCount : 0, i, s);
      if (a)
        return a;
    }
    t += l.nodeSize * i;
  }
  return null;
}
function Va(n2, e, t) {
  let r = n2.steps.length - 1;
  if (r < e)
    return;
  let i = n2.steps[r];
  if (!(i instanceof he || i instanceof pe))
    return;
  let s = n2.mapping.maps[r], o;
  s.forEach((l, a, c, u) => {
    o == null && (o = u);
  }), n2.setSelection($.near(n2.doc.resolve(o), t));
}
const $a = 1, Ci = 2, _a = 4;
class Rp extends Pl {
  /**
  @internal
  */
  constructor(e) {
    super(e.doc), this.curSelectionFor = 0, this.updated = 0, this.meta = /* @__PURE__ */ Object.create(null), this.time = Date.now(), this.curSelection = e.selection, this.storedMarks = e.storedMarks;
  }
  /**
  The transaction's current selection. This defaults to the editor
  selection [mapped](https://prosemirror.net/docs/ref/#state.Selection.map) through the steps in the
  transaction, but can be overwritten with
  [`setSelection`](https://prosemirror.net/docs/ref/#state.Transaction.setSelection).
  */
  get selection() {
    return this.curSelectionFor < this.steps.length && (this.curSelection = this.curSelection.map(this.doc, this.mapping.slice(this.curSelectionFor)), this.curSelectionFor = this.steps.length), this.curSelection;
  }
  /**
  Update the transaction's current selection. Will determine the
  selection that the editor gets when the transaction is applied.
  */
  setSelection(e) {
    if (e.$from.doc != this.doc)
      throw new RangeError("Selection passed to setSelection must point at the current document");
    return this.curSelection = e, this.curSelectionFor = this.steps.length, this.updated = (this.updated | $a) & ~Ci, this.storedMarks = null, this;
  }
  /**
  Whether the selection was explicitly updated by this transaction.
  */
  get selectionSet() {
    return (this.updated & $a) > 0;
  }
  /**
  Set the current stored marks.
  */
  setStoredMarks(e) {
    return this.storedMarks = e, this.updated |= Ci, this;
  }
  /**
  Make sure the current stored marks or, if that is null, the marks
  at the selection, match the given set of marks. Does nothing if
  this is already the case.
  */
  ensureMarks(e) {
    return te.sameSet(this.storedMarks || this.selection.$from.marks(), e) || this.setStoredMarks(e), this;
  }
  /**
  Add a mark to the set of stored marks.
  */
  addStoredMark(e) {
    return this.ensureMarks(e.addToSet(this.storedMarks || this.selection.$head.marks()));
  }
  /**
  Remove a mark or mark type from the set of stored marks.
  */
  removeStoredMark(e) {
    return this.ensureMarks(e.removeFromSet(this.storedMarks || this.selection.$head.marks()));
  }
  /**
  Whether the stored marks were explicitly set for this transaction.
  */
  get storedMarksSet() {
    return (this.updated & Ci) > 0;
  }
  /**
  @internal
  */
  addStep(e, t) {
    super.addStep(e, t), this.updated = this.updated & ~Ci, this.storedMarks = null;
  }
  /**
  Update the timestamp for the transaction.
  */
  setTime(e) {
    return this.time = e, this;
  }
  /**
  Replace the current selection with the given slice.
  */
  replaceSelection(e) {
    return this.selection.replace(this, e), this;
  }
  /**
  Replace the selection with the given node. When `inheritMarks` is
  true and the content is inline, it inherits the marks from the
  place where it is inserted.
  */
  replaceSelectionWith(e, t = true) {
    let r = this.selection;
    return t && (e = e.mark(this.storedMarks || (r.empty ? r.$from.marks() : r.$from.marksAcross(r.$to) || te.none))), r.replaceWith(this, e), this;
  }
  /**
  Delete the selection.
  */
  deleteSelection() {
    return this.selection.replace(this), this;
  }
  /**
  Replace the given range, or the selection if no range is given,
  with a text node containing the given string.
  */
  insertText(e, t, r) {
    let i = this.doc.type.schema;
    if (t == null)
      return e ? this.replaceSelectionWith(i.text(e), true) : this.deleteSelection();
    {
      if (r == null && (r = t), r = r ?? t, !e)
        return this.deleteRange(t, r);
      let s = this.storedMarks;
      if (!s) {
        let o = this.doc.resolve(t);
        s = r == t ? o.marks() : o.marksAcross(this.doc.resolve(r));
      }
      return this.replaceRangeWith(t, r, i.text(e, s)), this.selection.empty || this.setSelection($.near(this.selection.$to)), this;
    }
  }
  /**
  Store a metadata property in this transaction, keyed either by
  name or by plugin.
  */
  setMeta(e, t) {
    return this.meta[typeof e == "string" ? e : e.key] = t, this;
  }
  /**
  Retrieve a metadata property for a given name or plugin.
  */
  getMeta(e) {
    return this.meta[typeof e == "string" ? e : e.key];
  }
  /**
  Returns true if this transaction doesn't contain any metadata,
  and can thus safely be extended.
  */
  get isGeneric() {
    for (let e in this.meta)
      return false;
    return true;
  }
  /**
  Indicate that the editor should scroll the selection into view
  when updated to the state produced by this transaction.
  */
  scrollIntoView() {
    return this.updated |= _a, this;
  }
  /**
  True when this transaction has had `scrollIntoView` called on it.
  */
  get scrolledIntoView() {
    return (this.updated & _a) > 0;
  }
}
function ja(n2, e) {
  return !e || !n2 ? n2 : n2.bind(e);
}
class Ar {
  constructor(e, t, r) {
    this.name = e, this.init = ja(t.init, r), this.apply = ja(t.apply, r);
  }
}
const Ip = [
  new Ar("doc", {
    init(n2) {
      return n2.doc || n2.schema.topNodeType.createAndFill();
    },
    apply(n2) {
      return n2.doc;
    }
  }),
  new Ar("selection", {
    init(n2, e) {
      return n2.selection || $.atStart(e.doc);
    },
    apply(n2) {
      return n2.selection;
    }
  }),
  new Ar("storedMarks", {
    init(n2) {
      return n2.storedMarks || null;
    },
    apply(n2, e, t, r) {
      return r.selection.$cursor ? n2.storedMarks : null;
    }
  }),
  new Ar("scrollToSelection", {
    init() {
      return 0;
    },
    apply(n2, e) {
      return n2.scrolledIntoView ? e + 1 : e;
    }
  })
];
class po {
  constructor(e, t) {
    this.schema = e, this.plugins = [], this.pluginsByKey = /* @__PURE__ */ Object.create(null), this.fields = Ip.slice(), t && t.forEach((r) => {
      if (this.pluginsByKey[r.key])
        throw new RangeError("Adding different instances of a keyed plugin (" + r.key + ")");
      this.plugins.push(r), this.pluginsByKey[r.key] = r, r.spec.state && this.fields.push(new Ar(r.key, r.spec.state, r));
    });
  }
}
class Xn {
  /**
  @internal
  */
  constructor(e) {
    this.config = e;
  }
  /**
  The schema of the state's document.
  */
  get schema() {
    return this.config.schema;
  }
  /**
  The plugins that are active in this state.
  */
  get plugins() {
    return this.config.plugins;
  }
  /**
  Apply the given transaction to produce a new state.
  */
  apply(e) {
    return this.applyTransaction(e).state;
  }
  /**
  @internal
  */
  filterTransaction(e, t = -1) {
    for (let r = 0; r < this.config.plugins.length; r++)
      if (r != t) {
        let i = this.config.plugins[r];
        if (i.spec.filterTransaction && !i.spec.filterTransaction.call(i, e, this))
          return false;
      }
    return true;
  }
  /**
  Verbose variant of [`apply`](https://prosemirror.net/docs/ref/#state.EditorState.apply) that
  returns the precise transactions that were applied (which might
  be influenced by the [transaction
  hooks](https://prosemirror.net/docs/ref/#state.PluginSpec.filterTransaction) of
  plugins) along with the new state.
  */
  applyTransaction(e) {
    if (!this.filterTransaction(e))
      return { state: this, transactions: [] };
    let t = [e], r = this.applyInner(e), i = null;
    for (; ; ) {
      let s = false;
      for (let o = 0; o < this.config.plugins.length; o++) {
        let l = this.config.plugins[o];
        if (l.spec.appendTransaction) {
          let a = i ? i[o].n : 0, c = i ? i[o].state : this, u = a < t.length && l.spec.appendTransaction.call(l, a ? t.slice(a) : t, c, r);
          if (u && r.filterTransaction(u, o)) {
            if (u.setMeta("appendedTransaction", e), !i) {
              i = [];
              for (let d = 0; d < this.config.plugins.length; d++)
                i.push(d < o ? { state: r, n: t.length } : { state: this, n: 0 });
            }
            t.push(u), r = r.applyInner(u), s = true;
          }
          i && (i[o] = { state: r, n: t.length });
        }
      }
      if (!s)
        return { state: r, transactions: t };
    }
  }
  /**
  @internal
  */
  applyInner(e) {
    if (!e.before.eq(this.doc))
      throw new RangeError("Applying a mismatched transaction");
    let t = new Xn(this.config), r = this.config.fields;
    for (let i = 0; i < r.length; i++) {
      let s = r[i];
      t[s.name] = s.apply(e, this[s.name], this, t);
    }
    return t;
  }
  /**
  Start a [transaction](https://prosemirror.net/docs/ref/#state.Transaction) from this state.
  */
  get tr() {
    return new Rp(this);
  }
  /**
  Create a new state.
  */
  static create(e) {
    let t = new po(e.doc ? e.doc.type.schema : e.schema, e.plugins), r = new Xn(t);
    for (let i = 0; i < t.fields.length; i++)
      r[t.fields[i].name] = t.fields[i].init(e, r);
    return r;
  }
  /**
  Create a new state based on this one, but with an adjusted set
  of active plugins. State fields that exist in both sets of
  plugins are kept unchanged. Those that no longer exist are
  dropped, and those that are new are initialized using their
  [`init`](https://prosemirror.net/docs/ref/#state.StateField.init) method, passing in the new
  configuration object..
  */
  reconfigure(e) {
    let t = new po(this.schema, e.plugins), r = t.fields, i = new Xn(t);
    for (let s = 0; s < r.length; s++) {
      let o = r[s].name;
      i[o] = this.hasOwnProperty(o) ? this[o] : r[s].init(e, i);
    }
    return i;
  }
  /**
  Serialize this state to JSON. If you want to serialize the state
  of plugins, pass an object mapping property names to use in the
  resulting JSON object to plugin objects. The argument may also be
  a string or number, in which case it is ignored, to support the
  way `JSON.stringify` calls `toString` methods.
  */
  toJSON(e) {
    let t = { doc: this.doc.toJSON(), selection: this.selection.toJSON() };
    if (this.storedMarks && (t.storedMarks = this.storedMarks.map((r) => r.toJSON())), e && typeof e == "object")
      for (let r in e) {
        if (r == "doc" || r == "selection")
          throw new RangeError("The JSON fields `doc` and `selection` are reserved");
        let i = e[r], s = i.spec.state;
        s && s.toJSON && (t[r] = s.toJSON.call(i, this[i.key]));
      }
    return t;
  }
  /**
  Deserialize a JSON representation of a state. `config` should
  have at least a `schema` field, and should contain array of
  plugins to initialize the state with. `pluginFields` can be used
  to deserialize the state of plugins, by associating plugin
  instances with the property names they use in the JSON object.
  */
  static fromJSON(e, t, r) {
    if (!t)
      throw new RangeError("Invalid input for EditorState.fromJSON");
    if (!e.schema)
      throw new RangeError("Required config field 'schema' missing");
    let i = new po(e.schema, e.plugins), s = new Xn(i);
    return i.fields.forEach((o) => {
      if (o.name == "doc")
        s.doc = Qt.fromJSON(e.schema, t.doc);
      else if (o.name == "selection")
        s.selection = $.fromJSON(s.doc, t.selection);
      else if (o.name == "storedMarks")
        t.storedMarks && (s.storedMarks = t.storedMarks.map(e.schema.markFromJSON));
      else {
        if (r)
          for (let l in r) {
            let a = r[l], c = a.spec.state;
            if (a.key == o.name && c && c.fromJSON && Object.prototype.hasOwnProperty.call(t, l)) {
              s[o.name] = c.fromJSON.call(a, e, t[l], s);
              return;
            }
          }
        s[o.name] = o.init(e, s);
      }
    }), s;
  }
}
function hd(n2, e, t) {
  for (let r in n2) {
    let i = n2[r];
    i instanceof Function ? i = i.bind(e) : r == "handleDOMEvents" && (i = hd(i, e, {})), t[r] = i;
  }
  return t;
}
class le {
  /**
  Create a plugin.
  */
  constructor(e) {
    this.spec = e, this.props = {}, e.props && hd(e.props, this, this.props), this.key = e.key ? e.key.key : pd("plugin");
  }
  /**
  Extract the plugin's state field from an editor state.
  */
  getState(e) {
    return e[this.key];
  }
}
const mo = /* @__PURE__ */ Object.create(null);
function pd(n2) {
  return n2 in mo ? n2 + "$" + ++mo[n2] : (mo[n2] = 0, n2 + "$");
}
class ue {
  /**
  Create a plugin key.
  */
  constructor(e = "key") {
    this.key = pd(e);
  }
  /**
  Get the active plugin with this key, if any, from an editor
  state.
  */
  get(e) {
    return e.config.pluginsByKey[this.key];
  }
  /**
  Get the plugin's state from an editor state.
  */
  getState(e) {
    return e[this.key];
  }
}
const ve = function(n2) {
  for (var e = 0; ; e++)
    if (n2 = n2.previousSibling, !n2)
      return e;
}, nr = function(n2) {
  let e = n2.assignedSlot || n2.parentNode;
  return e && e.nodeType == 11 ? e.host : e;
};
let Ko = null;
const Ot = function(n2, e, t) {
  let r = Ko || (Ko = document.createRange());
  return r.setEnd(n2, t ?? n2.nodeValue.length), r.setStart(n2, e || 0), r;
}, Pp = function() {
  Ko = null;
}, Nn = function(n2, e, t, r) {
  return t && (Wa(n2, e, t, r, -1) || Wa(n2, e, t, r, 1));
}, Bp = /^(img|br|input|textarea|hr)$/i;
function Wa(n2, e, t, r, i) {
  for (var s; ; ) {
    if (n2 == t && e == r)
      return true;
    if (e == (i < 0 ? 0 : Ye(n2))) {
      let o = n2.parentNode;
      if (!o || o.nodeType != 1 || ii(n2) || Bp.test(n2.nodeName) || n2.contentEditable == "false")
        return false;
      e = ve(n2) + (i < 0 ? 0 : 1), n2 = o;
    } else if (n2.nodeType == 1) {
      let o = n2.childNodes[e + (i < 0 ? -1 : 0)];
      if (o.nodeType == 1 && o.contentEditable == "false")
        if (!((s = o.pmViewDesc) === null || s === void 0) && s.ignoreForSelection)
          e += i;
        else
          return false;
      else
        n2 = o, e = i < 0 ? Ye(n2) : 0;
    } else
      return false;
  }
}
function Ye(n2) {
  return n2.nodeType == 3 ? n2.nodeValue.length : n2.childNodes.length;
}
function Hp(n2, e) {
  for (; ; ) {
    if (n2.nodeType == 3 && e)
      return n2;
    if (n2.nodeType == 1 && e > 0) {
      if (n2.contentEditable == "false")
        return null;
      n2 = n2.childNodes[e - 1], e = Ye(n2);
    } else if (n2.parentNode && !ii(n2))
      e = ve(n2), n2 = n2.parentNode;
    else
      return null;
  }
}
function Fp(n2, e) {
  for (; ; ) {
    if (n2.nodeType == 3 && e < n2.nodeValue.length)
      return n2;
    if (n2.nodeType == 1 && e < n2.childNodes.length) {
      if (n2.contentEditable == "false")
        return null;
      n2 = n2.childNodes[e], e = 0;
    } else if (n2.parentNode && !ii(n2))
      e = ve(n2) + 1, n2 = n2.parentNode;
    else
      return null;
  }
}
function zp(n2, e, t) {
  for (let r = e == 0, i = e == Ye(n2); r || i; ) {
    if (n2 == t)
      return true;
    let s = ve(n2);
    if (n2 = n2.parentNode, !n2)
      return false;
    r = r && s == 0, i = i && s == Ye(n2);
  }
}
function ii(n2) {
  let e;
  for (let t = n2; t && !(e = t.pmViewDesc); t = t.parentNode)
    ;
  return e && e.node && e.node.isBlock && (e.dom == n2 || e.contentDOM == n2);
}
const Ws = function(n2) {
  return n2.focusNode && Nn(n2.focusNode, n2.focusOffset, n2.anchorNode, n2.anchorOffset);
};
function pn(n2, e) {
  let t = document.createEvent("Event");
  return t.initEvent("keydown", true, true), t.keyCode = n2, t.key = t.code = e, t;
}
function Vp(n2) {
  let e = n2.activeElement;
  for (; e && e.shadowRoot; )
    e = e.shadowRoot.activeElement;
  return e;
}
function $p(n2, e, t) {
  if (n2.caretPositionFromPoint)
    try {
      let r = n2.caretPositionFromPoint(e, t);
      if (r)
        return { node: r.offsetNode, offset: Math.min(Ye(r.offsetNode), r.offset) };
    } catch {
    }
  if (n2.caretRangeFromPoint) {
    let r = n2.caretRangeFromPoint(e, t);
    if (r)
      return { node: r.startContainer, offset: Math.min(Ye(r.startContainer), r.startOffset) };
  }
}
const gt = typeof navigator < "u" ? navigator : null, Ua = typeof document < "u" ? document : null, on = gt && gt.userAgent || "", qo = /Edge\/(\d+)/.exec(on), md = /MSIE \d/.exec(on), Jo = /Trident\/(?:[7-9]|\d{2,})\..*rv:(\d+)/.exec(on), Re = !!(md || Jo || qo), en = md ? document.documentMode : Jo ? +Jo[1] : qo ? +qo[1] : 0, ot = !Re && /gecko\/(\d+)/i.test(on);
ot && +(/Firefox\/(\d+)/.exec(on) || [0, 0])[1];
const Go = !Re && /Chrome\/(\d+)/.exec(on), Ce = !!Go, gd = Go ? +Go[1] : 0, Ee = !Re && !!gt && /Apple Computer/.test(gt.vendor), rr = Ee && (/Mobile\/\w+/.test(on) || !!gt && gt.maxTouchPoints > 2), Ge = rr || (gt ? /Mac/.test(gt.platform) : false), _p = gt ? /Win/.test(gt.platform) : false, Dt = /Android \d/.test(on), si = !!Ua && "webkitFontSmoothing" in Ua.documentElement.style, jp = si ? +(/\bAppleWebKit\/(\d+)/.exec(navigator.userAgent) || [0, 0])[1] : 0;
function Wp(n2) {
  let e = n2.defaultView && n2.defaultView.visualViewport;
  return e ? {
    left: 0,
    right: e.width,
    top: 0,
    bottom: e.height
  } : {
    left: 0,
    right: n2.documentElement.clientWidth,
    top: 0,
    bottom: n2.documentElement.clientHeight
  };
}
function St(n2, e) {
  return typeof n2 == "number" ? n2 : n2[e];
}
function Up(n2) {
  let e = n2.getBoundingClientRect(), t = e.width / n2.offsetWidth || 1, r = e.height / n2.offsetHeight || 1;
  return {
    left: e.left,
    right: e.left + n2.clientWidth * t,
    top: e.top,
    bottom: e.top + n2.clientHeight * r
  };
}
function Ka(n2, e, t) {
  let r = n2.someProp("scrollThreshold") || 0, i = n2.someProp("scrollMargin") || 5, s = n2.dom.ownerDocument;
  for (let o = t || n2.dom; o; ) {
    if (o.nodeType != 1) {
      o = nr(o);
      continue;
    }
    let l = o, a = l == s.body, c = a ? Wp(s) : Up(l), u = 0, d = 0;
    if (e.top < c.top + St(r, "top") ? d = -(c.top - e.top + St(i, "top")) : e.bottom > c.bottom - St(r, "bottom") && (d = e.bottom - e.top > c.bottom - c.top ? e.top + St(i, "top") - c.top : e.bottom - c.bottom + St(i, "bottom")), e.left < c.left + St(r, "left") ? u = -(c.left - e.left + St(i, "left")) : e.right > c.right - St(r, "right") && (u = e.right - c.right + St(i, "right")), u || d)
      if (a)
        s.defaultView.scrollBy(u, d);
      else {
        let h2 = l.scrollLeft, p2 = l.scrollTop;
        d && (l.scrollTop += d), u && (l.scrollLeft += u);
        let m = l.scrollLeft - h2, g = l.scrollTop - p2;
        e = { left: e.left - m, top: e.top - g, right: e.right - m, bottom: e.bottom - g };
      }
    let f = a ? "fixed" : getComputedStyle(o).position;
    if (/^(fixed|sticky)$/.test(f))
      break;
    o = f == "absolute" ? o.offsetParent : nr(o);
  }
}
function Kp(n2) {
  let e = n2.dom.getBoundingClientRect(), t = Math.max(0, e.top), r, i;
  for (let s = (e.left + e.right) / 2, o = t + 1; o < Math.min(innerHeight, e.bottom); o += 5) {
    let l = n2.root.elementFromPoint(s, o);
    if (!l || l == n2.dom || !n2.dom.contains(l))
      continue;
    let a = l.getBoundingClientRect();
    if (a.top >= t - 20) {
      r = l, i = a.top;
      break;
    }
  }
  return { refDOM: r, refTop: i, stack: yd(n2.dom) };
}
function yd(n2) {
  let e = [], t = n2.ownerDocument;
  for (let r = n2; r && (e.push({ dom: r, top: r.scrollTop, left: r.scrollLeft }), n2 != t); r = nr(r))
    ;
  return e;
}
function qp({ refDOM: n2, refTop: e, stack: t }) {
  let r = n2 ? n2.getBoundingClientRect().top : 0;
  bd(t, r == 0 ? 0 : r - e);
}
function bd(n2, e) {
  for (let t = 0; t < n2.length; t++) {
    let { dom: r, top: i, left: s } = n2[t];
    r.scrollTop != i + e && (r.scrollTop = i + e), r.scrollLeft != s && (r.scrollLeft = s);
  }
}
let jn = null;
function Jp(n2) {
  if (n2.setActive)
    return n2.setActive();
  if (jn)
    return n2.focus(jn);
  let e = yd(n2);
  n2.focus(jn == null ? {
    get preventScroll() {
      return jn = { preventScroll: true }, true;
    }
  } : void 0), jn || (jn = false, bd(e, 0));
}
function vd(n2, e) {
  let t, r = 2e8, i, s = 0, o = e.top, l = e.top, a, c;
  for (let u = n2.firstChild, d = 0; u; u = u.nextSibling, d++) {
    let f;
    if (u.nodeType == 1)
      f = u.getClientRects();
    else if (u.nodeType == 3)
      f = Ot(u).getClientRects();
    else
      continue;
    for (let h2 = 0; h2 < f.length; h2++) {
      let p2 = f[h2];
      if (p2.top <= o && p2.bottom >= l) {
        o = Math.max(p2.bottom, o), l = Math.min(p2.top, l);
        let m = p2.left > e.left ? p2.left - e.left : p2.right < e.left ? e.left - p2.right : 0;
        if (m < r) {
          t = u, r = m, i = m && t.nodeType == 3 ? {
            left: p2.right < e.left ? p2.right : p2.left,
            top: e.top
          } : e, u.nodeType == 1 && m && (s = d + (e.left >= (p2.left + p2.right) / 2 ? 1 : 0));
          continue;
        }
      } else p2.top > e.top && !a && p2.left <= e.left && p2.right >= e.left && (a = u, c = { left: Math.max(p2.left, Math.min(p2.right, e.left)), top: p2.top });
      !t && (e.left >= p2.right && e.top >= p2.top || e.left >= p2.left && e.top >= p2.bottom) && (s = d + 1);
    }
  }
  return !t && a && (t = a, i = c, r = 0), t && t.nodeType == 3 ? Gp(t, i) : !t || r && t.nodeType == 1 ? { node: n2, offset: s } : vd(t, i);
}
function Gp(n2, e) {
  let t = n2.nodeValue.length, r = document.createRange();
  for (let i = 0; i < t; i++) {
    r.setEnd(n2, i + 1), r.setStart(n2, i);
    let s = Vt(r, 1);
    if (s.top != s.bottom && Hl(e, s))
      return { node: n2, offset: i + (e.left >= (s.left + s.right) / 2 ? 1 : 0) };
  }
  return { node: n2, offset: 0 };
}
function Hl(n2, e) {
  return n2.left >= e.left - 1 && n2.left <= e.right + 1 && n2.top >= e.top - 1 && n2.top <= e.bottom + 1;
}
function Yp(n2, e) {
  let t = n2.parentNode;
  return t && /^li$/i.test(t.nodeName) && e.left < n2.getBoundingClientRect().left ? t : n2;
}
function Xp(n2, e, t) {
  let { node: r, offset: i } = vd(e, t), s = -1;
  if (r.nodeType == 1 && !r.firstChild) {
    let o = r.getBoundingClientRect();
    s = o.left != o.right && t.left > (o.left + o.right) / 2 ? 1 : -1;
  }
  return n2.docView.posFromDOM(r, i, s);
}
function Qp(n2, e, t, r) {
  let i = -1;
  for (let s = e, o = false; s != n2.dom; ) {
    let l = n2.docView.nearestDesc(s, true), a;
    if (!l)
      return null;
    if (l.dom.nodeType == 1 && (l.node.isBlock && l.parent || !l.contentDOM) && // Ignore elements with zero-size bounding rectangles
    ((a = l.dom.getBoundingClientRect()).width || a.height) && (l.node.isBlock && l.parent && (!o && a.left > r.left || a.top > r.top ? i = l.posBefore : (!o && a.right < r.left || a.bottom < r.top) && (i = l.posAfter), o = true), !l.contentDOM && i < 0 && !l.node.isText))
      return (l.node.isBlock ? r.top < (a.top + a.bottom) / 2 : r.left < (a.left + a.right) / 2) ? l.posBefore : l.posAfter;
    s = l.dom.parentNode;
  }
  return i > -1 ? i : n2.docView.posFromDOM(e, t, -1);
}
function wd(n2, e, t) {
  let r = n2.childNodes.length;
  if (r && t.top < t.bottom)
    for (let i = Math.max(0, Math.min(r - 1, Math.floor(r * (e.top - t.top) / (t.bottom - t.top)) - 2)), s = i; ; ) {
      let o = n2.childNodes[s];
      if (o.nodeType == 1) {
        let l = o.getClientRects();
        for (let a = 0; a < l.length; a++) {
          let c = l[a];
          if (Hl(e, c))
            return wd(o, e, c);
        }
      }
      if ((s = (s + 1) % r) == i)
        break;
    }
  return n2;
}
function Zp(n2, e) {
  let t = n2.dom.ownerDocument, r, i = 0, s = $p(t, e.left, e.top);
  s && ({ node: r, offset: i } = s);
  let o = (n2.root.elementFromPoint ? n2.root : t).elementFromPoint(e.left, e.top), l;
  if (!o || !n2.dom.contains(o.nodeType != 1 ? o.parentNode : o)) {
    let c = n2.dom.getBoundingClientRect();
    if (!Hl(e, c) || (o = wd(n2.dom, e, c), !o))
      return null;
  }
  if (Ee)
    for (let c = o; r && c; c = nr(c))
      c.draggable && (r = void 0);
  if (o = Yp(o, e), r) {
    if (ot && r.nodeType == 1 && (i = Math.min(i, r.childNodes.length), i < r.childNodes.length)) {
      let u = r.childNodes[i], d;
      u.nodeName == "IMG" && (d = u.getBoundingClientRect()).right <= e.left && d.bottom > e.top && i++;
    }
    let c;
    si && i && r.nodeType == 1 && (c = r.childNodes[i - 1]).nodeType == 1 && c.contentEditable == "false" && c.getBoundingClientRect().top >= e.top && i--, r == n2.dom && i == r.childNodes.length - 1 && r.lastChild.nodeType == 1 && e.top > r.lastChild.getBoundingClientRect().bottom ? l = n2.state.doc.content.size : (i == 0 || r.nodeType != 1 || r.childNodes[i - 1].nodeName != "BR") && (l = Qp(n2, r, i, e));
  }
  l == null && (l = Xp(n2, o, e));
  let a = n2.docView.nearestDesc(o, true);
  return { pos: l, inside: a ? a.posAtStart - a.border : -1 };
}
function qa(n2) {
  return n2.top < n2.bottom || n2.left < n2.right;
}
function Vt(n2, e) {
  let t = n2.getClientRects();
  if (t.length) {
    let r = t[e < 0 ? 0 : t.length - 1];
    if (qa(r))
      return r;
  }
  return Array.prototype.find.call(t, qa) || n2.getBoundingClientRect();
}
const em = /[\u0590-\u05f4\u0600-\u06ff\u0700-\u08ac]/;
function kd(n2, e, t) {
  let { node: r, offset: i, atom: s } = n2.docView.domFromPos(e, t < 0 ? -1 : 1), o = si || ot;
  if (r.nodeType == 3)
    if (o && (em.test(r.nodeValue) || (t < 0 ? !i : i == r.nodeValue.length))) {
      let a = Vt(Ot(r, i, i), t);
      if (ot && i && /\s/.test(r.nodeValue[i - 1]) && i < r.nodeValue.length) {
        let c = Vt(Ot(r, i - 1, i - 1), -1);
        if (c.top == a.top) {
          let u = Vt(Ot(r, i, i + 1), -1);
          if (u.top != a.top)
            return kr(u, u.left < c.left);
        }
      }
      return a;
    } else {
      let a = i, c = i, u = t < 0 ? 1 : -1;
      return t < 0 && !i ? (c++, u = -1) : t >= 0 && i == r.nodeValue.length ? (a--, u = 1) : t < 0 ? a-- : c++, kr(Vt(Ot(r, a, c), u), u < 0);
    }
  if (!n2.state.doc.resolve(e - (s || 0)).parent.inlineContent) {
    if (s == null && i && (t < 0 || i == Ye(r))) {
      let a = r.childNodes[i - 1];
      if (a.nodeType == 1)
        return go(a.getBoundingClientRect(), false);
    }
    if (s == null && i < Ye(r)) {
      let a = r.childNodes[i];
      if (a.nodeType == 1)
        return go(a.getBoundingClientRect(), true);
    }
    return go(r.getBoundingClientRect(), t >= 0);
  }
  if (s == null && i && (t < 0 || i == Ye(r))) {
    let a = r.childNodes[i - 1], c = a.nodeType == 3 ? Ot(a, Ye(a) - (o ? 0 : 1)) : a.nodeType == 1 && (a.nodeName != "BR" || !a.nextSibling) ? a : null;
    if (c)
      return kr(Vt(c, 1), false);
  }
  if (s == null && i < Ye(r)) {
    let a = r.childNodes[i];
    for (; a.pmViewDesc && a.pmViewDesc.ignoreForCoords; )
      a = a.nextSibling;
    let c = a ? a.nodeType == 3 ? Ot(a, 0, o ? 0 : 1) : a.nodeType == 1 ? a : null : null;
    if (c)
      return kr(Vt(c, -1), true);
  }
  return kr(Vt(r.nodeType == 3 ? Ot(r) : r, -t), t >= 0);
}
function kr(n2, e) {
  if (n2.width == 0)
    return n2;
  let t = e ? n2.left : n2.right;
  return { top: n2.top, bottom: n2.bottom, left: t, right: t };
}
function go(n2, e) {
  if (n2.height == 0)
    return n2;
  let t = e ? n2.top : n2.bottom;
  return { top: t, bottom: t, left: n2.left, right: n2.right };
}
function Cd(n2, e, t) {
  let r = n2.state, i = n2.root.activeElement;
  r != e && n2.updateState(e), i != n2.dom && n2.focus();
  try {
    return t();
  } finally {
    r != e && n2.updateState(r), i != n2.dom && i && i.focus();
  }
}
function tm(n2, e, t) {
  let r = e.selection, i = t == "up" ? r.$from : r.$to;
  return Cd(n2, e, () => {
    let { node: s } = n2.docView.domFromPos(i.pos, t == "up" ? -1 : 1);
    for (; ; ) {
      let l = n2.docView.nearestDesc(s, true);
      if (!l)
        break;
      if (l.node.isBlock) {
        s = l.contentDOM || l.dom;
        break;
      }
      s = l.dom.parentNode;
    }
    let o = kd(n2, i.pos, 1);
    for (let l = s.firstChild; l; l = l.nextSibling) {
      let a;
      if (l.nodeType == 1)
        a = l.getClientRects();
      else if (l.nodeType == 3)
        a = Ot(l, 0, l.nodeValue.length).getClientRects();
      else
        continue;
      for (let c = 0; c < a.length; c++) {
        let u = a[c];
        if (u.bottom > u.top + 1 && (t == "up" ? o.top - u.top > (u.bottom - o.top) * 2 : u.bottom - o.bottom > (o.bottom - u.top) * 2))
          return false;
      }
    }
    return true;
  });
}
const nm = /[\u0590-\u08ac]/;
function rm(n2, e, t) {
  let { $head: r } = e.selection;
  if (!r.parent.isTextblock)
    return false;
  let i = r.parentOffset, s = !i, o = i == r.parent.content.size, l = n2.domSelection();
  return l ? !nm.test(r.parent.textContent) || !l.modify ? t == "left" || t == "backward" ? s : o : Cd(n2, e, () => {
    let { focusNode: a, focusOffset: c, anchorNode: u, anchorOffset: d } = n2.domSelectionRange(), f = l.caretBidiLevel;
    l.modify("move", t, "character");
    let h2 = r.depth ? n2.docView.domAfterPos(r.before()) : n2.dom, { focusNode: p2, focusOffset: m } = n2.domSelectionRange(), g = p2 && !h2.contains(p2.nodeType == 1 ? p2 : p2.parentNode) || a == p2 && c == m;
    try {
      l.collapse(u, d), a && (a != u || c != d) && l.extend && l.extend(a, c);
    } catch {
    }
    return f != null && (l.caretBidiLevel = f), g;
  }) : r.pos == r.start() || r.pos == r.end();
}
let Ja = null, Ga = null, Ya = false;
function im(n2, e, t) {
  return Ja == e && Ga == t ? Ya : (Ja = e, Ga = t, Ya = t == "up" || t == "down" ? tm(n2, e, t) : rm(n2, e, t));
}
const Xe = 0, Xa = 1, bn = 2, yt = 3;
class oi {
  constructor(e, t, r, i) {
    this.parent = e, this.children = t, this.dom = r, this.contentDOM = i, this.dirty = Xe, r.pmViewDesc = this;
  }
  // Used to check whether a given description corresponds to a
  // widget/mark/node.
  matchesWidget(e) {
    return false;
  }
  matchesMark(e) {
    return false;
  }
  matchesNode(e, t, r) {
    return false;
  }
  matchesHack(e) {
    return false;
  }
  // When parsing in-editor content (in domchange.js), we allow
  // descriptions to determine the parse rules that should be used to
  // parse them.
  parseRule() {
    return null;
  }
  // Used by the editor's event handler to ignore events that come
  // from certain descs.
  stopEvent(e) {
    return false;
  }
  // The size of the content represented by this desc.
  get size() {
    let e = 0;
    for (let t = 0; t < this.children.length; t++)
      e += this.children[t].size;
    return e;
  }
  // For block nodes, this represents the space taken up by their
  // start/end tokens.
  get border() {
    return 0;
  }
  destroy() {
    this.parent = void 0, this.dom.pmViewDesc == this && (this.dom.pmViewDesc = void 0);
    for (let e = 0; e < this.children.length; e++)
      this.children[e].destroy();
  }
  posBeforeChild(e) {
    for (let t = 0, r = this.posAtStart; ; t++) {
      let i = this.children[t];
      if (i == e)
        return r;
      r += i.size;
    }
  }
  get posBefore() {
    return this.parent.posBeforeChild(this);
  }
  get posAtStart() {
    return this.parent ? this.parent.posBeforeChild(this) + this.border : 0;
  }
  get posAfter() {
    return this.posBefore + this.size;
  }
  get posAtEnd() {
    return this.posAtStart + this.size - 2 * this.border;
  }
  localPosFromDOM(e, t, r) {
    if (this.contentDOM && this.contentDOM.contains(e.nodeType == 1 ? e : e.parentNode))
      if (r < 0) {
        let s, o;
        if (e == this.contentDOM)
          s = e.childNodes[t - 1];
        else {
          for (; e.parentNode != this.contentDOM; )
            e = e.parentNode;
          s = e.previousSibling;
        }
        for (; s && !((o = s.pmViewDesc) && o.parent == this); )
          s = s.previousSibling;
        return s ? this.posBeforeChild(o) + o.size : this.posAtStart;
      } else {
        let s, o;
        if (e == this.contentDOM)
          s = e.childNodes[t];
        else {
          for (; e.parentNode != this.contentDOM; )
            e = e.parentNode;
          s = e.nextSibling;
        }
        for (; s && !((o = s.pmViewDesc) && o.parent == this); )
          s = s.nextSibling;
        return s ? this.posBeforeChild(o) : this.posAtEnd;
      }
    let i;
    if (e == this.dom && this.contentDOM)
      i = t > ve(this.contentDOM);
    else if (this.contentDOM && this.contentDOM != this.dom && this.dom.contains(this.contentDOM))
      i = e.compareDocumentPosition(this.contentDOM) & 2;
    else if (this.dom.firstChild) {
      if (t == 0)
        for (let s = e; ; s = s.parentNode) {
          if (s == this.dom) {
            i = false;
            break;
          }
          if (s.previousSibling)
            break;
        }
      if (i == null && t == e.childNodes.length)
        for (let s = e; ; s = s.parentNode) {
          if (s == this.dom) {
            i = true;
            break;
          }
          if (s.nextSibling)
            break;
        }
    }
    return i ?? r > 0 ? this.posAtEnd : this.posAtStart;
  }
  nearestDesc(e, t = false) {
    for (let r = true, i = e; i; i = i.parentNode) {
      let s = this.getDesc(i), o;
      if (s && (!t || s.node))
        if (r && (o = s.nodeDOM) && !(o.nodeType == 1 ? o.contains(e.nodeType == 1 ? e : e.parentNode) : o == e))
          r = false;
        else
          return s;
    }
  }
  getDesc(e) {
    let t = e.pmViewDesc;
    for (let r = t; r; r = r.parent)
      if (r == this)
        return t;
  }
  posFromDOM(e, t, r) {
    for (let i = e; i; i = i.parentNode) {
      let s = this.getDesc(i);
      if (s)
        return s.localPosFromDOM(e, t, r);
    }
    return -1;
  }
  // Find the desc for the node after the given pos, if any. (When a
  // parent node overrode rendering, there might not be one.)
  descAt(e) {
    for (let t = 0, r = 0; t < this.children.length; t++) {
      let i = this.children[t], s = r + i.size;
      if (r == e && s != r) {
        for (; !i.border && i.children.length; )
          for (let o = 0; o < i.children.length; o++) {
            let l = i.children[o];
            if (l.size) {
              i = l;
              break;
            }
          }
        return i;
      }
      if (e < s)
        return i.descAt(e - r - i.border);
      r = s;
    }
  }
  domFromPos(e, t) {
    if (!this.contentDOM)
      return { node: this.dom, offset: 0, atom: e + 1 };
    let r = 0, i = 0;
    for (let s = 0; r < this.children.length; r++) {
      let o = this.children[r], l = s + o.size;
      if (l > e || o instanceof Sd) {
        i = e - s;
        break;
      }
      s = l;
    }
    if (i)
      return this.children[r].domFromPos(i - this.children[r].border, t);
    for (let s; r && !(s = this.children[r - 1]).size && s instanceof xd && s.side >= 0; r--)
      ;
    if (t <= 0) {
      let s, o = true;
      for (; s = r ? this.children[r - 1] : null, !(!s || s.dom.parentNode == this.contentDOM); r--, o = false)
        ;
      return s && t && o && !s.border && !s.domAtom ? s.domFromPos(s.size, t) : { node: this.contentDOM, offset: s ? ve(s.dom) + 1 : 0 };
    } else {
      let s, o = true;
      for (; s = r < this.children.length ? this.children[r] : null, !(!s || s.dom.parentNode == this.contentDOM); r++, o = false)
        ;
      return s && o && !s.border && !s.domAtom ? s.domFromPos(0, t) : { node: this.contentDOM, offset: s ? ve(s.dom) : this.contentDOM.childNodes.length };
    }
  }
  // Used to find a DOM range in a single parent for a given changed
  // range.
  parseRange(e, t, r = 0) {
    if (this.children.length == 0)
      return { node: this.contentDOM, from: e, to: t, fromOffset: 0, toOffset: this.contentDOM.childNodes.length };
    let i = -1, s = -1;
    for (let o = r, l = 0; ; l++) {
      let a = this.children[l], c = o + a.size;
      if (i == -1 && e <= c) {
        let u = o + a.border;
        if (e >= u && t <= c - a.border && a.node && a.contentDOM && this.contentDOM.contains(a.contentDOM))
          return a.parseRange(e, t, u);
        e = o;
        for (let d = l; d > 0; d--) {
          let f = this.children[d - 1];
          if (f.size && f.dom.parentNode == this.contentDOM && !f.emptyChildAt(1)) {
            i = ve(f.dom) + 1;
            break;
          }
          e -= f.size;
        }
        i == -1 && (i = 0);
      }
      if (i > -1 && (c > t || l == this.children.length - 1)) {
        t = c;
        for (let u = l + 1; u < this.children.length; u++) {
          let d = this.children[u];
          if (d.size && d.dom.parentNode == this.contentDOM && !d.emptyChildAt(-1)) {
            s = ve(d.dom);
            break;
          }
          t += d.size;
        }
        s == -1 && (s = this.contentDOM.childNodes.length);
        break;
      }
      o = c;
    }
    return { node: this.contentDOM, from: e, to: t, fromOffset: i, toOffset: s };
  }
  emptyChildAt(e) {
    if (this.border || !this.contentDOM || !this.children.length)
      return false;
    let t = this.children[e < 0 ? 0 : this.children.length - 1];
    return t.size == 0 || t.emptyChildAt(e);
  }
  domAfterPos(e) {
    let { node: t, offset: r } = this.domFromPos(e, 0);
    if (t.nodeType != 1 || r == t.childNodes.length)
      throw new RangeError("No node after pos " + e);
    return t.childNodes[r];
  }
  // View descs are responsible for setting any selection that falls
  // entirely inside of them, so that custom implementations can do
  // custom things with the selection. Note that this falls apart when
  // a selection starts in such a node and ends in another, in which
  // case we just use whatever domFromPos produces as a best effort.
  setSelection(e, t, r, i = false) {
    let s = Math.min(e, t), o = Math.max(e, t);
    for (let h2 = 0, p2 = 0; h2 < this.children.length; h2++) {
      let m = this.children[h2], g = p2 + m.size;
      if (s > p2 && o < g)
        return m.setSelection(e - p2 - m.border, t - p2 - m.border, r, i);
      p2 = g;
    }
    let l = this.domFromPos(e, e ? -1 : 1), a = t == e ? l : this.domFromPos(t, t ? -1 : 1), c = r.root.getSelection(), u = r.domSelectionRange(), d = false;
    if ((ot || Ee) && e == t) {
      let { node: h2, offset: p2 } = l;
      if (h2.nodeType == 3) {
        if (d = !!(p2 && h2.nodeValue[p2 - 1] == `
`), d && p2 == h2.nodeValue.length)
          for (let m = h2, g; m; m = m.parentNode) {
            if (g = m.nextSibling) {
              g.nodeName == "BR" && (l = a = { node: g.parentNode, offset: ve(g) + 1 });
              break;
            }
            let y = m.pmViewDesc;
            if (y && y.node && y.node.isBlock)
              break;
          }
      } else {
        let m = h2.childNodes[p2 - 1];
        d = m && (m.nodeName == "BR" || m.contentEditable == "false");
      }
    }
    if (ot && u.focusNode && u.focusNode != a.node && u.focusNode.nodeType == 1) {
      let h2 = u.focusNode.childNodes[u.focusOffset];
      h2 && h2.contentEditable == "false" && (i = true);
    }
    if (!(i || d && Ee) && Nn(l.node, l.offset, u.anchorNode, u.anchorOffset) && Nn(a.node, a.offset, u.focusNode, u.focusOffset))
      return;
    let f = false;
    if ((c.extend || e == t) && !d) {
      c.collapse(l.node, l.offset);
      try {
        e != t && c.extend(a.node, a.offset), f = true;
      } catch {
      }
    }
    if (!f) {
      if (e > t) {
        let p2 = l;
        l = a, a = p2;
      }
      let h2 = document.createRange();
      h2.setEnd(a.node, a.offset), h2.setStart(l.node, l.offset), c.removeAllRanges(), c.addRange(h2);
    }
  }
  ignoreMutation(e) {
    return !this.contentDOM && e.type != "selection";
  }
  get contentLost() {
    return this.contentDOM && this.contentDOM != this.dom && !this.dom.contains(this.contentDOM);
  }
  // Remove a subtree of the element tree that has been touched
  // by a DOM change, so that the next update will redraw it.
  markDirty(e, t) {
    for (let r = 0, i = 0; i < this.children.length; i++) {
      let s = this.children[i], o = r + s.size;
      if (r == o ? e <= o && t >= r : e < o && t > r) {
        let l = r + s.border, a = o - s.border;
        if (e >= l && t <= a) {
          this.dirty = e == r || t == o ? bn : Xa, e == l && t == a && (s.contentLost || s.dom.parentNode != this.contentDOM) ? s.dirty = yt : s.markDirty(e - l, t - l);
          return;
        } else
          s.dirty = s.dom == s.contentDOM && s.dom.parentNode == this.contentDOM && !s.children.length ? bn : yt;
      }
      r = o;
    }
    this.dirty = bn;
  }
  markParentsDirty() {
    let e = 1;
    for (let t = this.parent; t; t = t.parent, e++) {
      let r = e == 1 ? bn : Xa;
      t.dirty < r && (t.dirty = r);
    }
  }
  get domAtom() {
    return false;
  }
  get ignoreForCoords() {
    return false;
  }
  get ignoreForSelection() {
    return false;
  }
  isText(e) {
    return false;
  }
}
class xd extends oi {
  constructor(e, t, r, i) {
    let s, o = t.type.toDOM;
    if (typeof o == "function" && (o = o(r, () => {
      if (!s)
        return i;
      if (s.parent)
        return s.parent.posBeforeChild(s);
    })), !t.type.spec.raw) {
      if (o.nodeType != 1) {
        let l = document.createElement("span");
        l.appendChild(o), o = l;
      }
      o.contentEditable = "false", o.classList.add("ProseMirror-widget");
    }
    super(e, [], o, null), this.widget = t, this.widget = t, s = this;
  }
  matchesWidget(e) {
    return this.dirty == Xe && e.type.eq(this.widget.type);
  }
  parseRule() {
    return { ignore: true };
  }
  stopEvent(e) {
    let t = this.widget.spec.stopEvent;
    return t ? t(e) : false;
  }
  ignoreMutation(e) {
    return e.type != "selection" || this.widget.spec.ignoreSelection;
  }
  destroy() {
    this.widget.type.destroy(this.dom), super.destroy();
  }
  get domAtom() {
    return true;
  }
  get ignoreForSelection() {
    return !!this.widget.type.spec.relaxedSide;
  }
  get side() {
    return this.widget.type.side;
  }
}
class sm extends oi {
  constructor(e, t, r, i) {
    super(e, [], t, null), this.textDOM = r, this.text = i;
  }
  get size() {
    return this.text.length;
  }
  localPosFromDOM(e, t) {
    return e != this.textDOM ? this.posAtStart + (t ? this.size : 0) : this.posAtStart + t;
  }
  domFromPos(e) {
    return { node: this.textDOM, offset: e };
  }
  ignoreMutation(e) {
    return e.type === "characterData" && e.target.nodeValue == e.oldValue;
  }
}
class Dn extends oi {
  constructor(e, t, r, i, s) {
    super(e, [], r, i), this.mark = t, this.spec = s;
  }
  static create(e, t, r, i) {
    let s = i.nodeViews[t.type.name], o = s && s(t, i, r);
    return (!o || !o.dom) && (o = Pn.renderSpec(document, t.type.spec.toDOM(t, r), null, t.attrs)), new Dn(e, t, o.dom, o.contentDOM || o.dom, o);
  }
  parseRule() {
    return this.dirty & yt || this.mark.type.spec.reparseInView ? null : { mark: this.mark.type.name, attrs: this.mark.attrs, contentElement: this.contentDOM };
  }
  matchesMark(e) {
    return this.dirty != yt && this.mark.eq(e);
  }
  markDirty(e, t) {
    if (super.markDirty(e, t), this.dirty != Xe) {
      let r = this.parent;
      for (; !r.node; )
        r = r.parent;
      r.dirty < this.dirty && (r.dirty = this.dirty), this.dirty = Xe;
    }
  }
  slice(e, t, r) {
    let i = Dn.create(this.parent, this.mark, true, r), s = this.children, o = this.size;
    t < o && (s = Xo(s, t, o, r)), e > 0 && (s = Xo(s, 0, e, r));
    for (let l = 0; l < s.length; l++)
      s[l].parent = i;
    return i.children = s, i;
  }
  ignoreMutation(e) {
    return this.spec.ignoreMutation ? this.spec.ignoreMutation(e) : super.ignoreMutation(e);
  }
  destroy() {
    this.spec.destroy && this.spec.destroy(), super.destroy();
  }
}
class tn extends oi {
  constructor(e, t, r, i, s, o, l, a, c) {
    super(e, [], s, o), this.node = t, this.outerDeco = r, this.innerDeco = i, this.nodeDOM = l;
  }
  // By default, a node is rendered using the `toDOM` method from the
  // node type spec. But client code can use the `nodeViews` spec to
  // supply a custom node view, which can influence various aspects of
  // the way the node works.
  //
  // (Using subclassing for this was intentionally decided against,
  // since it'd require exposing a whole slew of finicky
  // implementation details to the user code that they probably will
  // never need.)
  static create(e, t, r, i, s, o) {
    let l = s.nodeViews[t.type.name], a, c = l && l(t, s, () => {
      if (!a)
        return o;
      if (a.parent)
        return a.parent.posBeforeChild(a);
    }, r, i), u = c && c.dom, d = c && c.contentDOM;
    if (t.isText) {
      if (!u)
        u = document.createTextNode(t.text);
      else if (u.nodeType != 3)
        throw new RangeError("Text must be rendered as a DOM text node");
    } else u || ({ dom: u, contentDOM: d } = Pn.renderSpec(document, t.type.spec.toDOM(t), null, t.attrs));
    !d && !t.isText && u.nodeName != "BR" && (u.hasAttribute("contenteditable") || (u.contentEditable = "false"), t.type.spec.draggable && (u.draggable = true));
    let f = u;
    return u = Ed(u, r, t), c ? a = new om(e, t, r, i, u, d || null, f, c, s, o + 1) : t.isText ? new Us(e, t, r, i, u, f, s) : new tn(e, t, r, i, u, d || null, f, s, o + 1);
  }
  parseRule() {
    if (this.node.type.spec.reparseInView)
      return null;
    let e = { node: this.node.type.name, attrs: this.node.attrs };
    if (this.node.type.whitespace == "pre" && (e.preserveWhitespace = "full"), !this.contentDOM)
      e.getContent = () => this.node.content;
    else if (!this.contentLost)
      e.contentElement = this.contentDOM;
    else {
      for (let t = this.children.length - 1; t >= 0; t--) {
        let r = this.children[t];
        if (this.dom.contains(r.dom.parentNode)) {
          e.contentElement = r.dom.parentNode;
          break;
        }
      }
      e.contentElement || (e.getContent = () => A.empty);
    }
    return e;
  }
  matchesNode(e, t, r) {
    return this.dirty == Xe && e.eq(this.node) && Qi(t, this.outerDeco) && r.eq(this.innerDeco);
  }
  get size() {
    return this.node.nodeSize;
  }
  get border() {
    return this.node.isLeaf ? 0 : 1;
  }
  // Syncs `this.children` to match `this.node.content` and the local
  // decorations, possibly introducing nesting for marks. Then, in a
  // separate step, syncs the DOM inside `this.contentDOM` to
  // `this.children`.
  updateChildren(e, t) {
    let r = this.node.inlineContent, i = t, s = e.composing ? this.localCompositionInfo(e, t) : null, o = s && s.pos > -1 ? s : null, l = s && s.pos < 0, a = new am(this, o && o.node, e);
    dm(this.node, this.innerDeco, (c, u, d) => {
      c.spec.marks ? a.syncToMarks(c.spec.marks, r, e) : c.type.side >= 0 && !d && a.syncToMarks(u == this.node.childCount ? te.none : this.node.child(u).marks, r, e), a.placeWidget(c, e, i);
    }, (c, u, d, f) => {
      a.syncToMarks(c.marks, r, e);
      let h2;
      a.findNodeMatch(c, u, d, f) || l && e.state.selection.from > i && e.state.selection.to < i + c.nodeSize && (h2 = a.findIndexWithChild(s.node)) > -1 && a.updateNodeAt(c, u, d, h2, e) || a.updateNextNode(c, u, d, e, f, i) || a.addNode(c, u, d, e, i), i += c.nodeSize;
    }), a.syncToMarks([], r, e), this.node.isTextblock && a.addTextblockHacks(), a.destroyRest(), (a.changed || this.dirty == bn) && (o && this.protectLocalComposition(e, o), Md(this.contentDOM, this.children, e), rr && fm(this.dom));
  }
  localCompositionInfo(e, t) {
    let { from: r, to: i } = e.state.selection;
    if (!(e.state.selection instanceof F) || r < t || i > t + this.node.content.size)
      return null;
    let s = e.input.compositionNode;
    if (!s || !this.dom.contains(s.parentNode))
      return null;
    if (this.node.inlineContent) {
      let o = s.nodeValue, l = hm(this.node.content, o, r - t, i - t);
      return l < 0 ? null : { node: s, pos: l, text: o };
    } else
      return { node: s, pos: -1, text: "" };
  }
  protectLocalComposition(e, { node: t, pos: r, text: i }) {
    if (this.getDesc(t))
      return;
    let s = t;
    for (; s.parentNode != this.contentDOM; s = s.parentNode) {
      for (; s.previousSibling; )
        s.parentNode.removeChild(s.previousSibling);
      for (; s.nextSibling; )
        s.parentNode.removeChild(s.nextSibling);
      s.pmViewDesc && (s.pmViewDesc = void 0);
    }
    let o = new sm(this, s, t, i);
    e.input.compositionNodes.push(o), this.children = Xo(this.children, r, r + i.length, e, o);
  }
  // If this desc must be updated to match the given node decoration,
  // do so and return true.
  update(e, t, r, i) {
    return this.dirty == yt || !e.sameMarkup(this.node) ? false : (this.updateInner(e, t, r, i), true);
  }
  updateInner(e, t, r, i) {
    this.updateOuterDeco(t), this.node = e, this.innerDeco = r, this.contentDOM && this.updateChildren(i, this.posAtStart), this.dirty = Xe;
  }
  updateOuterDeco(e) {
    if (Qi(e, this.outerDeco))
      return;
    let t = this.nodeDOM.nodeType != 1, r = this.dom;
    this.dom = Ad(this.dom, this.nodeDOM, Yo(this.outerDeco, this.node, t), Yo(e, this.node, t)), this.dom != r && (r.pmViewDesc = void 0, this.dom.pmViewDesc = this), this.outerDeco = e;
  }
  // Mark this node as being the selected node.
  selectNode() {
    this.nodeDOM.nodeType == 1 && this.nodeDOM.classList.add("ProseMirror-selectednode"), (this.contentDOM || !this.node.type.spec.draggable) && (this.dom.draggable = true);
  }
  // Remove selected node marking from this node.
  deselectNode() {
    this.nodeDOM.nodeType == 1 && (this.nodeDOM.classList.remove("ProseMirror-selectednode"), (this.contentDOM || !this.node.type.spec.draggable) && this.dom.removeAttribute("draggable"));
  }
  get domAtom() {
    return this.node.isAtom;
  }
}
function Qa(n2, e, t, r, i) {
  Ed(r, e, n2);
  let s = new tn(void 0, n2, e, t, r, r, r, i, 0);
  return s.contentDOM && s.updateChildren(i, 0), s;
}
class Us extends tn {
  constructor(e, t, r, i, s, o, l) {
    super(e, t, r, i, s, null, o, l, 0);
  }
  parseRule() {
    let e = this.nodeDOM.parentNode;
    for (; e && e != this.dom && !e.pmIsDeco; )
      e = e.parentNode;
    return { skip: e || true };
  }
  update(e, t, r, i) {
    return this.dirty == yt || this.dirty != Xe && !this.inParent() || !e.sameMarkup(this.node) ? false : (this.updateOuterDeco(t), (this.dirty != Xe || e.text != this.node.text) && e.text != this.nodeDOM.nodeValue && (this.nodeDOM.nodeValue = e.text, i.trackWrites == this.nodeDOM && (i.trackWrites = null)), this.node = e, this.dirty = Xe, true);
  }
  inParent() {
    let e = this.parent.contentDOM;
    for (let t = this.nodeDOM; t; t = t.parentNode)
      if (t == e)
        return true;
    return false;
  }
  domFromPos(e) {
    return { node: this.nodeDOM, offset: e };
  }
  localPosFromDOM(e, t, r) {
    return e == this.nodeDOM ? this.posAtStart + Math.min(t, this.node.text.length) : super.localPosFromDOM(e, t, r);
  }
  ignoreMutation(e) {
    return e.type != "characterData" && e.type != "selection";
  }
  slice(e, t, r) {
    let i = this.node.cut(e, t), s = document.createTextNode(i.text);
    return new Us(this.parent, i, this.outerDeco, this.innerDeco, s, s, r);
  }
  markDirty(e, t) {
    super.markDirty(e, t), this.dom != this.nodeDOM && (e == 0 || t == this.nodeDOM.nodeValue.length) && (this.dirty = yt);
  }
  get domAtom() {
    return false;
  }
  isText(e) {
    return this.node.text == e;
  }
}
class Sd extends oi {
  parseRule() {
    return { ignore: true };
  }
  matchesHack(e) {
    return this.dirty == Xe && this.dom.nodeName == e;
  }
  get domAtom() {
    return true;
  }
  get ignoreForCoords() {
    return this.dom.nodeName == "IMG";
  }
}
class om extends tn {
  constructor(e, t, r, i, s, o, l, a, c, u) {
    super(e, t, r, i, s, o, l, c, u), this.spec = a;
  }
  // A custom `update` method gets to decide whether the update goes
  // through. If it does, and there's a `contentDOM` node, our logic
  // updates the children.
  update(e, t, r, i) {
    if (this.dirty == yt)
      return false;
    if (this.spec.update && (this.node.type == e.type || this.spec.multiType)) {
      let s = this.spec.update(e, t, r);
      return s && this.updateInner(e, t, r, i), s;
    } else return !this.contentDOM && !e.isLeaf ? false : super.update(e, t, r, i);
  }
  selectNode() {
    this.spec.selectNode ? this.spec.selectNode() : super.selectNode();
  }
  deselectNode() {
    this.spec.deselectNode ? this.spec.deselectNode() : super.deselectNode();
  }
  setSelection(e, t, r, i) {
    this.spec.setSelection ? this.spec.setSelection(e, t, r.root) : super.setSelection(e, t, r, i);
  }
  destroy() {
    this.spec.destroy && this.spec.destroy(), super.destroy();
  }
  stopEvent(e) {
    return this.spec.stopEvent ? this.spec.stopEvent(e) : false;
  }
  ignoreMutation(e) {
    return this.spec.ignoreMutation ? this.spec.ignoreMutation(e) : super.ignoreMutation(e);
  }
}
function Md(n2, e, t) {
  let r = n2.firstChild, i = false;
  for (let s = 0; s < e.length; s++) {
    let o = e[s], l = o.dom;
    if (l.parentNode == n2) {
      for (; l != r; )
        r = Za(r), i = true;
      r = r.nextSibling;
    } else
      i = true, n2.insertBefore(l, r);
    if (o instanceof Dn) {
      let a = r ? r.previousSibling : n2.lastChild;
      Md(o.contentDOM, o.children, t), r = a ? a.nextSibling : n2.firstChild;
    }
  }
  for (; r; )
    r = Za(r), i = true;
  i && t.trackWrites == n2 && (t.trackWrites = null);
}
const Nr = function(n2) {
  n2 && (this.nodeName = n2);
};
Nr.prototype = /* @__PURE__ */ Object.create(null);
const vn = [new Nr()];
function Yo(n2, e, t) {
  if (n2.length == 0)
    return vn;
  let r = t ? vn[0] : new Nr(), i = [r];
  for (let s = 0; s < n2.length; s++) {
    let o = n2[s].type.attrs;
    if (o) {
      o.nodeName && i.push(r = new Nr(o.nodeName));
      for (let l in o) {
        let a = o[l];
        a != null && (t && i.length == 1 && i.push(r = new Nr(e.isInline ? "span" : "div")), l == "class" ? r.class = (r.class ? r.class + " " : "") + a : l == "style" ? r.style = (r.style ? r.style + ";" : "") + a : l != "nodeName" && (r[l] = a));
      }
    }
  }
  return i;
}
function Ad(n2, e, t, r) {
  if (t == vn && r == vn)
    return e;
  let i = e;
  for (let s = 0; s < r.length; s++) {
    let o = r[s], l = t[s];
    if (s) {
      let a;
      l && l.nodeName == o.nodeName && i != n2 && (a = i.parentNode) && a.nodeName.toLowerCase() == o.nodeName || (a = document.createElement(o.nodeName), a.pmIsDeco = true, a.appendChild(i), l = vn[0]), i = a;
    }
    lm(i, l || vn[0], o);
  }
  return i;
}
function lm(n2, e, t) {
  for (let r in e)
    r != "class" && r != "style" && r != "nodeName" && !(r in t) && n2.removeAttribute(r);
  for (let r in t)
    r != "class" && r != "style" && r != "nodeName" && t[r] != e[r] && n2.setAttribute(r, t[r]);
  if (e.class != t.class) {
    let r = e.class ? e.class.split(" ").filter(Boolean) : [], i = t.class ? t.class.split(" ").filter(Boolean) : [];
    for (let s = 0; s < r.length; s++)
      i.indexOf(r[s]) == -1 && n2.classList.remove(r[s]);
    for (let s = 0; s < i.length; s++)
      r.indexOf(i[s]) == -1 && n2.classList.add(i[s]);
    n2.classList.length == 0 && n2.removeAttribute("class");
  }
  if (e.style != t.style) {
    if (e.style) {
      let r = /\s*([\w\-\xa1-\uffff]+)\s*:(?:"(?:\\.|[^"])*"|'(?:\\.|[^'])*'|\(.*?\)|[^;])*/g, i;
      for (; i = r.exec(e.style); )
        n2.style.removeProperty(i[1]);
    }
    t.style && (n2.style.cssText += t.style);
  }
}
function Ed(n2, e, t) {
  return Ad(n2, n2, vn, Yo(e, t, n2.nodeType != 1));
}
function Qi(n2, e) {
  if (n2.length != e.length)
    return false;
  for (let t = 0; t < n2.length; t++)
    if (!n2[t].type.eq(e[t].type))
      return false;
  return true;
}
function Za(n2) {
  let e = n2.nextSibling;
  return n2.parentNode.removeChild(n2), e;
}
class am {
  constructor(e, t, r) {
    this.lock = t, this.view = r, this.index = 0, this.stack = [], this.changed = false, this.top = e, this.preMatch = cm(e.node.content, e);
  }
  // Destroy and remove the children between the given indices in
  // `this.top`.
  destroyBetween(e, t) {
    if (e != t) {
      for (let r = e; r < t; r++)
        this.top.children[r].destroy();
      this.top.children.splice(e, t - e), this.changed = true;
    }
  }
  // Destroy all remaining children in `this.top`.
  destroyRest() {
    this.destroyBetween(this.index, this.top.children.length);
  }
  // Sync the current stack of mark descs with the given array of
  // marks, reusing existing mark descs when possible.
  syncToMarks(e, t, r) {
    let i = 0, s = this.stack.length >> 1, o = Math.min(s, e.length);
    for (; i < o && (i == s - 1 ? this.top : this.stack[i + 1 << 1]).matchesMark(e[i]) && e[i].type.spec.spanning !== false; )
      i++;
    for (; i < s; )
      this.destroyRest(), this.top.dirty = Xe, this.index = this.stack.pop(), this.top = this.stack.pop(), s--;
    for (; s < e.length; ) {
      this.stack.push(this.top, this.index + 1);
      let l = -1;
      for (let a = this.index; a < Math.min(this.index + 3, this.top.children.length); a++) {
        let c = this.top.children[a];
        if (c.matchesMark(e[s]) && !this.isLocked(c.dom)) {
          l = a;
          break;
        }
      }
      if (l > -1)
        l > this.index && (this.changed = true, this.destroyBetween(this.index, l)), this.top = this.top.children[this.index];
      else {
        let a = Dn.create(this.top, e[s], t, r);
        this.top.children.splice(this.index, 0, a), this.top = a, this.changed = true;
      }
      this.index = 0, s++;
    }
  }
  // Try to find a node desc matching the given data. Skip over it and
  // return true when successful.
  findNodeMatch(e, t, r, i) {
    let s = -1, o;
    if (i >= this.preMatch.index && (o = this.preMatch.matches[i - this.preMatch.index]).parent == this.top && o.matchesNode(e, t, r))
      s = this.top.children.indexOf(o, this.index);
    else
      for (let l = this.index, a = Math.min(this.top.children.length, l + 5); l < a; l++) {
        let c = this.top.children[l];
        if (c.matchesNode(e, t, r) && !this.preMatch.matched.has(c)) {
          s = l;
          break;
        }
      }
    return s < 0 ? false : (this.destroyBetween(this.index, s), this.index++, true);
  }
  updateNodeAt(e, t, r, i, s) {
    let o = this.top.children[i];
    return o.dirty == yt && o.dom == o.contentDOM && (o.dirty = bn), o.update(e, t, r, s) ? (this.destroyBetween(this.index, i), this.index++, true) : false;
  }
  findIndexWithChild(e) {
    for (; ; ) {
      let t = e.parentNode;
      if (!t)
        return -1;
      if (t == this.top.contentDOM) {
        let r = e.pmViewDesc;
        if (r) {
          for (let i = this.index; i < this.top.children.length; i++)
            if (this.top.children[i] == r)
              return i;
        }
        return -1;
      }
      e = t;
    }
  }
  // Try to update the next node, if any, to the given data. Checks
  // pre-matches to avoid overwriting nodes that could still be used.
  updateNextNode(e, t, r, i, s, o) {
    for (let l = this.index; l < this.top.children.length; l++) {
      let a = this.top.children[l];
      if (a instanceof tn) {
        let c = this.preMatch.matched.get(a);
        if (c != null && c != s)
          return false;
        let u = a.dom, d, f = this.isLocked(u) && !(e.isText && a.node && a.node.isText && a.nodeDOM.nodeValue == e.text && a.dirty != yt && Qi(t, a.outerDeco));
        if (!f && a.update(e, t, r, i))
          return this.destroyBetween(this.index, l), a.dom != u && (this.changed = true), this.index++, true;
        if (!f && (d = this.recreateWrapper(a, e, t, r, i, o)))
          return this.destroyBetween(this.index, l), this.top.children[this.index] = d, d.contentDOM && (d.dirty = bn, d.updateChildren(i, o + 1), d.dirty = Xe), this.changed = true, this.index++, true;
        break;
      }
    }
    return false;
  }
  // When a node with content is replaced by a different node with
  // identical content, move over its children.
  recreateWrapper(e, t, r, i, s, o) {
    if (e.dirty || t.isAtom || !e.children.length || !e.node.content.eq(t.content) || !Qi(r, e.outerDeco) || !i.eq(e.innerDeco))
      return null;
    let l = tn.create(this.top, t, r, i, s, o);
    if (l.contentDOM) {
      l.children = e.children, e.children = [];
      for (let a of l.children)
        a.parent = l;
    }
    return e.destroy(), l;
  }
  // Insert the node as a newly created node desc.
  addNode(e, t, r, i, s) {
    let o = tn.create(this.top, e, t, r, i, s);
    o.contentDOM && o.updateChildren(i, s + 1), this.top.children.splice(this.index++, 0, o), this.changed = true;
  }
  placeWidget(e, t, r) {
    let i = this.index < this.top.children.length ? this.top.children[this.index] : null;
    if (i && i.matchesWidget(e) && (e == i.widget || !i.widget.type.toDOM.parentNode))
      this.index++;
    else {
      let s = new xd(this.top, e, t, r);
      this.top.children.splice(this.index++, 0, s), this.changed = true;
    }
  }
  // Make sure a textblock looks and behaves correctly in
  // contentEditable.
  addTextblockHacks() {
    let e = this.top.children[this.index - 1], t = this.top;
    for (; e instanceof Dn; )
      t = e, e = t.children[t.children.length - 1];
    (!e || // Empty textblock
    !(e instanceof Us) || /\n$/.test(e.node.text) || this.view.requiresGeckoHackNode && /\s$/.test(e.node.text)) && ((Ee || Ce) && e && e.dom.contentEditable == "false" && this.addHackNode("IMG", t), this.addHackNode("BR", this.top));
  }
  addHackNode(e, t) {
    if (t == this.top && this.index < t.children.length && t.children[this.index].matchesHack(e))
      this.index++;
    else {
      let r = document.createElement(e);
      e == "IMG" && (r.className = "ProseMirror-separator", r.alt = ""), e == "BR" && (r.className = "ProseMirror-trailingBreak");
      let i = new Sd(this.top, [], r, null);
      t != this.top ? t.children.push(i) : t.children.splice(this.index++, 0, i), this.changed = true;
    }
  }
  isLocked(e) {
    return this.lock && (e == this.lock || e.nodeType == 1 && e.contains(this.lock.parentNode));
  }
}
function cm(n2, e) {
  let t = e, r = t.children.length, i = n2.childCount, s = /* @__PURE__ */ new Map(), o = [];
  e: for (; i > 0; ) {
    let l;
    for (; ; )
      if (r) {
        let c = t.children[r - 1];
        if (c instanceof Dn)
          t = c, r = c.children.length;
        else {
          l = c, r--;
          break;
        }
      } else {
        if (t == e)
          break e;
        r = t.parent.children.indexOf(t), t = t.parent;
      }
    let a = l.node;
    if (a) {
      if (a != n2.child(i - 1))
        break;
      --i, s.set(l, i), o.push(l);
    }
  }
  return { index: i, matched: s, matches: o.reverse() };
}
function um(n2, e) {
  return n2.type.side - e.type.side;
}
function dm(n2, e, t, r) {
  let i = e.locals(n2), s = 0;
  if (i.length == 0) {
    for (let c = 0; c < n2.childCount; c++) {
      let u = n2.child(c);
      r(u, i, e.forChild(s, u), c), s += u.nodeSize;
    }
    return;
  }
  let o = 0, l = [], a = null;
  for (let c = 0; ; ) {
    let u, d;
    for (; o < i.length && i[o].to == s; ) {
      let g = i[o++];
      g.widget && (u ? (d || (d = [u])).push(g) : u = g);
    }
    if (u)
      if (d) {
        d.sort(um);
        for (let g = 0; g < d.length; g++)
          t(d[g], c, !!a);
      } else
        t(u, c, !!a);
    let f, h2;
    if (a)
      h2 = -1, f = a, a = null;
    else if (c < n2.childCount)
      h2 = c, f = n2.child(c++);
    else
      break;
    for (let g = 0; g < l.length; g++)
      l[g].to <= s && l.splice(g--, 1);
    for (; o < i.length && i[o].from <= s && i[o].to > s; )
      l.push(i[o++]);
    let p2 = s + f.nodeSize;
    if (f.isText) {
      let g = p2;
      o < i.length && i[o].from < g && (g = i[o].from);
      for (let y = 0; y < l.length; y++)
        l[y].to < g && (g = l[y].to);
      g < p2 && (a = f.cut(g - s), f = f.cut(0, g - s), p2 = g, h2 = -1);
    } else
      for (; o < i.length && i[o].to < p2; )
        o++;
    let m = f.isInline && !f.isLeaf ? l.filter((g) => !g.inline) : l.slice();
    r(f, m, e.forChild(s, f), h2), s = p2;
  }
}
function fm(n2) {
  if (n2.nodeName == "UL" || n2.nodeName == "OL") {
    let e = n2.style.cssText;
    n2.style.cssText = e + "; list-style: square !important", window.getComputedStyle(n2).listStyle, n2.style.cssText = e;
  }
}
function hm(n2, e, t, r) {
  for (let i = 0, s = 0; i < n2.childCount && s <= r; ) {
    let o = n2.child(i++), l = s;
    if (s += o.nodeSize, !o.isText)
      continue;
    let a = o.text;
    for (; i < n2.childCount; ) {
      let c = n2.child(i++);
      if (s += c.nodeSize, !c.isText)
        break;
      a += c.text;
    }
    if (s >= t) {
      if (s >= r && a.slice(r - e.length - l, r - l) == e)
        return r - e.length;
      let c = l < r ? a.lastIndexOf(e, r - l - 1) : -1;
      if (c >= 0 && c + e.length + l >= t)
        return l + c;
      if (t == r && a.length >= r + e.length - l && a.slice(r - l, r - l + e.length) == e)
        return r;
    }
  }
  return -1;
}
function Xo(n2, e, t, r, i) {
  let s = [];
  for (let o = 0, l = 0; o < n2.length; o++) {
    let a = n2[o], c = l, u = l += a.size;
    c >= t || u <= e ? s.push(a) : (c < e && s.push(a.slice(0, e - c, r)), i && (s.push(i), i = void 0), u > t && s.push(a.slice(t - c, a.size, r)));
  }
  return s;
}
function Fl(n2, e = null) {
  let t = n2.domSelectionRange(), r = n2.state.doc;
  if (!t.focusNode)
    return null;
  let i = n2.docView.nearestDesc(t.focusNode), s = i && i.size == 0, o = n2.docView.posFromDOM(t.focusNode, t.focusOffset, 1);
  if (o < 0)
    return null;
  let l = r.resolve(o), a, c;
  if (Ws(t)) {
    for (a = o; i && !i.node; )
      i = i.parent;
    let d = i.node;
    if (i && d.isAtom && B.isSelectable(d) && i.parent && !(d.isInline && zp(t.focusNode, t.focusOffset, i.dom))) {
      let f = i.posBefore;
      c = new B(o == f ? l : r.resolve(f));
    }
  } else {
    if (t instanceof n2.dom.ownerDocument.defaultView.Selection && t.rangeCount > 1) {
      let d = o, f = o;
      for (let h2 = 0; h2 < t.rangeCount; h2++) {
        let p2 = t.getRangeAt(h2);
        d = Math.min(d, n2.docView.posFromDOM(p2.startContainer, p2.startOffset, 1)), f = Math.max(f, n2.docView.posFromDOM(p2.endContainer, p2.endOffset, -1));
      }
      if (d < 0)
        return null;
      [a, o] = f == n2.state.selection.anchor ? [f, d] : [d, f], l = r.resolve(o);
    } else
      a = n2.docView.posFromDOM(t.anchorNode, t.anchorOffset, 1);
    if (a < 0)
      return null;
  }
  let u = r.resolve(a);
  if (!c) {
    let d = e == "pointer" || n2.state.selection.head < l.pos && !s ? 1 : -1;
    c = zl(n2, u, l, d);
  }
  return c;
}
function Td(n2) {
  return n2.editable ? n2.hasFocus() : Nd(n2) && document.activeElement && document.activeElement.contains(n2.dom);
}
function It(n2, e = false) {
  let t = n2.state.selection;
  if (Od(n2, t), !!Td(n2)) {
    if (!e && n2.input.mouseDown && n2.input.mouseDown.allowDefault && Ce) {
      let r = n2.domSelectionRange(), i = n2.domObserver.currentSelection;
      if (r.anchorNode && i.anchorNode && Nn(r.anchorNode, r.anchorOffset, i.anchorNode, i.anchorOffset)) {
        n2.input.mouseDown.delayedSelectionSync = true, n2.domObserver.setCurSelection();
        return;
      }
    }
    if (n2.domObserver.disconnectSelection(), n2.cursorWrapper)
      mm(n2);
    else {
      let { anchor: r, head: i } = t, s, o;
      ec && !(t instanceof F) && (t.$from.parent.inlineContent || (s = tc(n2, t.from)), !t.empty && !t.$from.parent.inlineContent && (o = tc(n2, t.to))), n2.docView.setSelection(r, i, n2, e), ec && (s && nc(s), o && nc(o)), t.visible ? n2.dom.classList.remove("ProseMirror-hideselection") : (n2.dom.classList.add("ProseMirror-hideselection"), "onselectionchange" in document && pm(n2));
    }
    n2.domObserver.setCurSelection(), n2.domObserver.connectSelection();
  }
}
const ec = Ee || Ce && gd < 63;
function tc(n2, e) {
  let { node: t, offset: r } = n2.docView.domFromPos(e, 0), i = r < t.childNodes.length ? t.childNodes[r] : null, s = r ? t.childNodes[r - 1] : null;
  if (Ee && i && i.contentEditable == "false")
    return yo(i);
  if ((!i || i.contentEditable == "false") && (!s || s.contentEditable == "false")) {
    if (i)
      return yo(i);
    if (s)
      return yo(s);
  }
}
function yo(n2) {
  return n2.contentEditable = "true", Ee && n2.draggable && (n2.draggable = false, n2.wasDraggable = true), n2;
}
function nc(n2) {
  n2.contentEditable = "false", n2.wasDraggable && (n2.draggable = true, n2.wasDraggable = null);
}
function pm(n2) {
  let e = n2.dom.ownerDocument;
  e.removeEventListener("selectionchange", n2.input.hideSelectionGuard);
  let t = n2.domSelectionRange(), r = t.anchorNode, i = t.anchorOffset;
  e.addEventListener("selectionchange", n2.input.hideSelectionGuard = () => {
    (t.anchorNode != r || t.anchorOffset != i) && (e.removeEventListener("selectionchange", n2.input.hideSelectionGuard), setTimeout(() => {
      (!Td(n2) || n2.state.selection.visible) && n2.dom.classList.remove("ProseMirror-hideselection");
    }, 20));
  });
}
function mm(n2) {
  let e = n2.domSelection(), t = document.createRange();
  if (!e)
    return;
  let r = n2.cursorWrapper.dom, i = r.nodeName == "IMG";
  i ? t.setStart(r.parentNode, ve(r) + 1) : t.setStart(r, 0), t.collapse(true), e.removeAllRanges(), e.addRange(t), !i && !n2.state.selection.visible && Re && en <= 11 && (r.disabled = true, r.disabled = false);
}
function Od(n2, e) {
  if (e instanceof B) {
    let t = n2.docView.descAt(e.from);
    t != n2.lastSelectedViewDesc && (rc(n2), t && t.selectNode(), n2.lastSelectedViewDesc = t);
  } else
    rc(n2);
}
function rc(n2) {
  n2.lastSelectedViewDesc && (n2.lastSelectedViewDesc.parent && n2.lastSelectedViewDesc.deselectNode(), n2.lastSelectedViewDesc = void 0);
}
function zl(n2, e, t, r) {
  return n2.someProp("createSelectionBetween", (i) => i(n2, e, t)) || F.between(e, t, r);
}
function ic(n2) {
  return n2.editable && !n2.hasFocus() ? false : Nd(n2);
}
function Nd(n2) {
  let e = n2.domSelectionRange();
  if (!e.anchorNode)
    return false;
  try {
    return n2.dom.contains(e.anchorNode.nodeType == 3 ? e.anchorNode.parentNode : e.anchorNode) && (n2.editable || n2.dom.contains(e.focusNode.nodeType == 3 ? e.focusNode.parentNode : e.focusNode));
  } catch {
    return false;
  }
}
function gm(n2) {
  let e = n2.docView.domFromPos(n2.state.selection.anchor, 0), t = n2.domSelectionRange();
  return Nn(e.node, e.offset, t.anchorNode, t.anchorOffset);
}
function Qo(n2, e) {
  let { $anchor: t, $head: r } = n2.selection, i = e > 0 ? t.max(r) : t.min(r), s = i.parent.inlineContent ? i.depth ? n2.doc.resolve(e > 0 ? i.after() : i.before()) : null : i;
  return s && $.findFrom(s, e);
}
function $t(n2, e) {
  return n2.dispatch(n2.state.tr.setSelection(e).scrollIntoView()), true;
}
function sc(n2, e, t) {
  let r = n2.state.selection;
  if (r instanceof F)
    if (t.indexOf("s") > -1) {
      let { $head: i } = r, s = i.textOffset ? null : e < 0 ? i.nodeBefore : i.nodeAfter;
      if (!s || s.isText || !s.isLeaf)
        return false;
      let o = n2.state.doc.resolve(i.pos + s.nodeSize * (e < 0 ? -1 : 1));
      return $t(n2, new F(r.$anchor, o));
    } else if (r.empty) {
      if (n2.endOfTextblock(e > 0 ? "forward" : "backward")) {
        let i = Qo(n2.state, e);
        return i && i instanceof B ? $t(n2, i) : false;
      } else if (!(Ge && t.indexOf("m") > -1)) {
        let i = r.$head, s = i.textOffset ? null : e < 0 ? i.nodeBefore : i.nodeAfter, o;
        if (!s || s.isText)
          return false;
        let l = e < 0 ? i.pos - s.nodeSize : i.pos;
        return s.isAtom || (o = n2.docView.descAt(l)) && !o.contentDOM ? B.isSelectable(s) ? $t(n2, new B(e < 0 ? n2.state.doc.resolve(i.pos - s.nodeSize) : i)) : si ? $t(n2, new F(n2.state.doc.resolve(e < 0 ? l : l + s.nodeSize))) : false : false;
      }
    } else return false;
  else {
    if (r instanceof B && r.node.isInline)
      return $t(n2, new F(e > 0 ? r.$to : r.$from));
    {
      let i = Qo(n2.state, e);
      return i ? $t(n2, i) : false;
    }
  }
}
function Zi(n2) {
  return n2.nodeType == 3 ? n2.nodeValue.length : n2.childNodes.length;
}
function Dr(n2, e) {
  let t = n2.pmViewDesc;
  return t && t.size == 0 && (e < 0 || n2.nextSibling || n2.nodeName != "BR");
}
function Wn(n2, e) {
  return e < 0 ? ym(n2) : bm(n2);
}
function ym(n2) {
  let e = n2.domSelectionRange(), t = e.focusNode, r = e.focusOffset;
  if (!t)
    return;
  let i, s, o = false;
  for (ot && t.nodeType == 1 && r < Zi(t) && Dr(t.childNodes[r], -1) && (o = true); ; )
    if (r > 0) {
      if (t.nodeType != 1)
        break;
      {
        let l = t.childNodes[r - 1];
        if (Dr(l, -1))
          i = t, s = --r;
        else if (l.nodeType == 3)
          t = l, r = t.nodeValue.length;
        else
          break;
      }
    } else {
      if (Dd(t))
        break;
      {
        let l = t.previousSibling;
        for (; l && Dr(l, -1); )
          i = t.parentNode, s = ve(l), l = l.previousSibling;
        if (l)
          t = l, r = Zi(t);
        else {
          if (t = t.parentNode, t == n2.dom)
            break;
          r = 0;
        }
      }
    }
  o ? Zo(n2, t, r) : i && Zo(n2, i, s);
}
function bm(n2) {
  let e = n2.domSelectionRange(), t = e.focusNode, r = e.focusOffset;
  if (!t)
    return;
  let i = Zi(t), s, o;
  for (; ; )
    if (r < i) {
      if (t.nodeType != 1)
        break;
      let l = t.childNodes[r];
      if (Dr(l, 1))
        s = t, o = ++r;
      else
        break;
    } else {
      if (Dd(t))
        break;
      {
        let l = t.nextSibling;
        for (; l && Dr(l, 1); )
          s = l.parentNode, o = ve(l) + 1, l = l.nextSibling;
        if (l)
          t = l, r = 0, i = Zi(t);
        else {
          if (t = t.parentNode, t == n2.dom)
            break;
          r = i = 0;
        }
      }
    }
  s && Zo(n2, s, o);
}
function Dd(n2) {
  let e = n2.pmViewDesc;
  return e && e.node && e.node.isBlock;
}
function vm(n2, e) {
  for (; n2 && e == n2.childNodes.length && !ii(n2); )
    e = ve(n2) + 1, n2 = n2.parentNode;
  for (; n2 && e < n2.childNodes.length; ) {
    let t = n2.childNodes[e];
    if (t.nodeType == 3)
      return t;
    if (t.nodeType == 1 && t.contentEditable == "false")
      break;
    n2 = t, e = 0;
  }
}
function wm(n2, e) {
  for (; n2 && !e && !ii(n2); )
    e = ve(n2), n2 = n2.parentNode;
  for (; n2 && e; ) {
    let t = n2.childNodes[e - 1];
    if (t.nodeType == 3)
      return t;
    if (t.nodeType == 1 && t.contentEditable == "false")
      break;
    n2 = t, e = n2.childNodes.length;
  }
}
function Zo(n2, e, t) {
  if (e.nodeType != 3) {
    let s, o;
    (o = vm(e, t)) ? (e = o, t = 0) : (s = wm(e, t)) && (e = s, t = s.nodeValue.length);
  }
  let r = n2.domSelection();
  if (!r)
    return;
  if (Ws(r)) {
    let s = document.createRange();
    s.setEnd(e, t), s.setStart(e, t), r.removeAllRanges(), r.addRange(s);
  } else r.extend && r.extend(e, t);
  n2.domObserver.setCurSelection();
  let { state: i } = n2;
  setTimeout(() => {
    n2.state == i && It(n2);
  }, 50);
}
function oc(n2, e) {
  let t = n2.state.doc.resolve(e);
  if (!(Ce || _p) && t.parent.inlineContent) {
    let i = n2.coordsAtPos(e);
    if (e > t.start()) {
      let s = n2.coordsAtPos(e - 1), o = (s.top + s.bottom) / 2;
      if (o > i.top && o < i.bottom && Math.abs(s.left - i.left) > 1)
        return s.left < i.left ? "ltr" : "rtl";
    }
    if (e < t.end()) {
      let s = n2.coordsAtPos(e + 1), o = (s.top + s.bottom) / 2;
      if (o > i.top && o < i.bottom && Math.abs(s.left - i.left) > 1)
        return s.left > i.left ? "ltr" : "rtl";
    }
  }
  return getComputedStyle(n2.dom).direction == "rtl" ? "rtl" : "ltr";
}
function lc(n2, e, t) {
  let r = n2.state.selection;
  if (r instanceof F && !r.empty || t.indexOf("s") > -1 || Ge && t.indexOf("m") > -1)
    return false;
  let { $from: i, $to: s } = r;
  if (!i.parent.inlineContent || n2.endOfTextblock(e < 0 ? "up" : "down")) {
    let o = Qo(n2.state, e);
    if (o && o instanceof B)
      return $t(n2, o);
  }
  if (!i.parent.inlineContent) {
    let o = e < 0 ? i : s, l = r instanceof je ? $.near(o, e) : $.findFrom(o, e);
    return l ? $t(n2, l) : false;
  }
  return false;
}
function ac(n2, e) {
  if (!(n2.state.selection instanceof F))
    return true;
  let { $head: t, $anchor: r, empty: i } = n2.state.selection;
  if (!t.sameParent(r))
    return true;
  if (!i)
    return false;
  if (n2.endOfTextblock(e > 0 ? "forward" : "backward"))
    return true;
  let s = !t.textOffset && (e < 0 ? t.nodeBefore : t.nodeAfter);
  if (s && !s.isText) {
    let o = n2.state.tr;
    return e < 0 ? o.delete(t.pos - s.nodeSize, t.pos) : o.delete(t.pos, t.pos + s.nodeSize), n2.dispatch(o), true;
  }
  return false;
}
function cc(n2, e, t) {
  n2.domObserver.stop(), e.contentEditable = t, n2.domObserver.start();
}
function km(n2) {
  if (!Ee || n2.state.selection.$head.parentOffset > 0)
    return false;
  let { focusNode: e, focusOffset: t } = n2.domSelectionRange();
  if (e && e.nodeType == 1 && t == 0 && e.firstChild && e.firstChild.contentEditable == "false") {
    let r = e.firstChild;
    cc(n2, r, "true"), setTimeout(() => cc(n2, r, "false"), 20);
  }
  return false;
}
function Cm(n2) {
  let e = "";
  return n2.ctrlKey && (e += "c"), n2.metaKey && (e += "m"), n2.altKey && (e += "a"), n2.shiftKey && (e += "s"), e;
}
function xm(n2, e) {
  let t = e.keyCode, r = Cm(e);
  if (t == 8 || Ge && t == 72 && r == "c")
    return ac(n2, -1) || Wn(n2, -1);
  if (t == 46 && !e.shiftKey || Ge && t == 68 && r == "c")
    return ac(n2, 1) || Wn(n2, 1);
  if (t == 13 || t == 27)
    return true;
  if (t == 37 || Ge && t == 66 && r == "c") {
    let i = t == 37 ? oc(n2, n2.state.selection.from) == "ltr" ? -1 : 1 : -1;
    return sc(n2, i, r) || Wn(n2, i);
  } else if (t == 39 || Ge && t == 70 && r == "c") {
    let i = t == 39 ? oc(n2, n2.state.selection.from) == "ltr" ? 1 : -1 : 1;
    return sc(n2, i, r) || Wn(n2, i);
  } else {
    if (t == 38 || Ge && t == 80 && r == "c")
      return lc(n2, -1, r) || Wn(n2, -1);
    if (t == 40 || Ge && t == 78 && r == "c")
      return km(n2) || lc(n2, 1, r) || Wn(n2, 1);
    if (r == (Ge ? "m" : "c") && (t == 66 || t == 73 || t == 89 || t == 90))
      return true;
  }
  return false;
}
function Vl(n2, e) {
  n2.someProp("transformCopied", (h2) => {
    e = h2(e, n2);
  });
  let t = [], { content: r, openStart: i, openEnd: s } = e;
  for (; i > 1 && s > 1 && r.childCount == 1 && r.firstChild.childCount == 1; ) {
    i--, s--;
    let h2 = r.firstChild;
    t.push(h2.type.name, h2.attrs != h2.type.defaultAttrs ? h2.attrs : null), r = h2.content;
  }
  let o = n2.someProp("clipboardSerializer") || Pn.fromSchema(n2.state.schema), l = Hd(), a = l.createElement("div");
  a.appendChild(o.serializeFragment(r, { document: l }));
  let c = a.firstChild, u, d = 0;
  for (; c && c.nodeType == 1 && (u = Bd[c.nodeName.toLowerCase()]); ) {
    for (let h2 = u.length - 1; h2 >= 0; h2--) {
      let p2 = l.createElement(u[h2]);
      for (; a.firstChild; )
        p2.appendChild(a.firstChild);
      a.appendChild(p2), d++;
    }
    c = a.firstChild;
  }
  c && c.nodeType == 1 && c.setAttribute("data-pm-slice", `${i} ${s}${d ? ` -${d}` : ""} ${JSON.stringify(t)}`);
  let f = n2.someProp("clipboardTextSerializer", (h2) => h2(e, n2)) || e.content.textBetween(0, e.content.size, `

`);
  return { dom: a, text: f, slice: e };
}
function Ld(n2, e, t, r, i) {
  let s = i.parent.type.spec.code, o, l;
  if (!t && !e)
    return null;
  let a = e && (r || s || !t);
  if (a) {
    if (n2.someProp("transformPastedText", (f) => {
      e = f(e, s || r, n2);
    }), s)
      return e ? new O(A.from(n2.state.schema.text(e.replace(/\r\n?/g, `
`))), 0, 0) : O.empty;
    let d = n2.someProp("clipboardTextParser", (f) => f(e, i, r, n2));
    if (d)
      l = d;
    else {
      let f = i.marks(), { schema: h2 } = n2.state, p2 = Pn.fromSchema(h2);
      o = document.createElement("div"), e.split(/(?:\r\n?|\n)+/).forEach((m) => {
        let g = o.appendChild(document.createElement("p"));
        m && g.appendChild(p2.serializeNode(h2.text(m, f)));
      });
    }
  } else
    n2.someProp("transformPastedHTML", (d) => {
      t = d(t, n2);
    }), o = Em(t), si && Tm(o);
  let c = o && o.querySelector("[data-pm-slice]"), u = c && /^(\d+) (\d+)(?: -(\d+))? (.*)/.exec(c.getAttribute("data-pm-slice") || "");
  if (u && u[3])
    for (let d = +u[3]; d > 0; d--) {
      let f = o.firstChild;
      for (; f && f.nodeType != 1; )
        f = f.nextSibling;
      if (!f)
        break;
      o = f;
    }
  if (l || (l = (n2.someProp("clipboardParser") || n2.someProp("domParser") || Zt.fromSchema(n2.state.schema)).parseSlice(o, {
    preserveWhitespace: !!(a || u),
    context: i,
    ruleFromNode(f) {
      return f.nodeName == "BR" && !f.nextSibling && f.parentNode && !Sm.test(f.parentNode.nodeName) ? { ignore: true } : null;
    }
  })), u)
    l = Om(uc(l, +u[1], +u[2]), u[4]);
  else if (l = O.maxOpen(Mm(l.content, i), true), l.openStart || l.openEnd) {
    let d = 0, f = 0;
    for (let h2 = l.content.firstChild; d < l.openStart && !h2.type.spec.isolating; d++, h2 = h2.firstChild)
      ;
    for (let h2 = l.content.lastChild; f < l.openEnd && !h2.type.spec.isolating; f++, h2 = h2.lastChild)
      ;
    l = uc(l, d, f);
  }
  return n2.someProp("transformPasted", (d) => {
    l = d(l, n2);
  }), l;
}
const Sm = /^(a|abbr|acronym|b|cite|code|del|em|i|ins|kbd|label|output|q|ruby|s|samp|span|strong|sub|sup|time|u|tt|var)$/i;
function Mm(n2, e) {
  if (n2.childCount < 2)
    return n2;
  for (let t = e.depth; t >= 0; t--) {
    let i = e.node(t).contentMatchAt(e.index(t)), s, o = [];
    if (n2.forEach((l) => {
      if (!o)
        return;
      let a = i.findWrapping(l.type), c;
      if (!a)
        return o = null;
      if (c = o.length && s.length && Id(a, s, l, o[o.length - 1], 0))
        o[o.length - 1] = c;
      else {
        o.length && (o[o.length - 1] = Pd(o[o.length - 1], s.length));
        let u = Rd(l, a);
        o.push(u), i = i.matchType(u.type), s = a;
      }
    }), o)
      return A.from(o);
  }
  return n2;
}
function Rd(n2, e, t = 0) {
  for (let r = e.length - 1; r >= t; r--)
    n2 = e[r].create(null, A.from(n2));
  return n2;
}
function Id(n2, e, t, r, i) {
  if (i < n2.length && i < e.length && n2[i] == e[i]) {
    let s = Id(n2, e, t, r.lastChild, i + 1);
    if (s)
      return r.copy(r.content.replaceChild(r.childCount - 1, s));
    if (r.contentMatchAt(r.childCount).matchType(i == n2.length - 1 ? t.type : n2[i + 1]))
      return r.copy(r.content.append(A.from(Rd(t, n2, i + 1))));
  }
}
function Pd(n2, e) {
  if (e == 0)
    return n2;
  let t = n2.content.replaceChild(n2.childCount - 1, Pd(n2.lastChild, e - 1)), r = n2.contentMatchAt(n2.childCount).fillBefore(A.empty, true);
  return n2.copy(t.append(r));
}
function el(n2, e, t, r, i, s) {
  let o = e < 0 ? n2.firstChild : n2.lastChild, l = o.content;
  return n2.childCount > 1 && (s = 0), i < r - 1 && (l = el(l, e, t, r, i + 1, s)), i >= t && (l = e < 0 ? o.contentMatchAt(0).fillBefore(l, s <= i).append(l) : l.append(o.contentMatchAt(o.childCount).fillBefore(A.empty, true))), n2.replaceChild(e < 0 ? 0 : n2.childCount - 1, o.copy(l));
}
function uc(n2, e, t) {
  return e < n2.openStart && (n2 = new O(el(n2.content, -1, e, n2.openStart, 0, n2.openEnd), e, n2.openEnd)), t < n2.openEnd && (n2 = new O(el(n2.content, 1, t, n2.openEnd, 0, 0), n2.openStart, t)), n2;
}
const Bd = {
  thead: ["table"],
  tbody: ["table"],
  tfoot: ["table"],
  caption: ["table"],
  colgroup: ["table"],
  col: ["table", "colgroup"],
  tr: ["table", "tbody"],
  td: ["table", "tbody", "tr"],
  th: ["table", "tbody", "tr"]
};
let dc = null;
function Hd() {
  return dc || (dc = document.implementation.createHTMLDocument("title"));
}
let bo = null;
function Am(n2) {
  let e = window.trustedTypes;
  return e ? (bo || (bo = e.defaultPolicy || e.createPolicy("ProseMirrorClipboard", { createHTML: (t) => t })), bo.createHTML(n2)) : n2;
}
function Em(n2) {
  let e = /^(\s*<meta [^>]*>)*/.exec(n2);
  e && (n2 = n2.slice(e[0].length));
  let t = Hd().createElement("div"), r = /<([a-z][^>\s]+)/i.exec(n2), i;
  if ((i = r && Bd[r[1].toLowerCase()]) && (n2 = i.map((s) => "<" + s + ">").join("") + n2 + i.map((s) => "</" + s + ">").reverse().join("")), t.innerHTML = Am(n2), i)
    for (let s = 0; s < i.length; s++)
      t = t.querySelector(i[s]) || t;
  return t;
}
function Tm(n2) {
  let e = n2.querySelectorAll(Ce ? "span:not([class]):not([style])" : "span.Apple-converted-space");
  for (let t = 0; t < e.length; t++) {
    let r = e[t];
    r.childNodes.length == 1 && r.textContent == " " && r.parentNode && r.parentNode.replaceChild(n2.ownerDocument.createTextNode(" "), r);
  }
}
function Om(n2, e) {
  if (!n2.size)
    return n2;
  let t = n2.content.firstChild.type.schema, r;
  try {
    r = JSON.parse(e);
  } catch {
    return n2;
  }
  let { content: i, openStart: s, openEnd: o } = n2;
  for (let l = r.length - 2; l >= 0; l -= 2) {
    let a = t.nodes[r[l]];
    if (!a || a.hasRequiredAttrs())
      break;
    i = A.from(a.create(r[l + 1], i)), s++, o++;
  }
  return new O(i, s, o);
}
const Te = {}, Oe = {}, Nm = { touchstart: true, touchmove: true };
class Dm {
  constructor() {
    this.shiftKey = false, this.mouseDown = null, this.lastKeyCode = null, this.lastKeyCodeTime = 0, this.lastClick = { time: 0, x: 0, y: 0, type: "", button: 0 }, this.lastSelectionOrigin = null, this.lastSelectionTime = 0, this.lastIOSEnter = 0, this.lastIOSEnterFallbackTimeout = -1, this.lastFocus = 0, this.lastTouch = 0, this.lastChromeDelete = 0, this.composing = false, this.compositionNode = null, this.composingTimeout = -1, this.compositionNodes = [], this.compositionEndedAt = -2e8, this.compositionID = 1, this.compositionPendingChanges = 0, this.domChangeCount = 0, this.eventHandlers = /* @__PURE__ */ Object.create(null), this.hideSelectionGuard = null;
  }
}
function Lm(n2) {
  for (let e in Te) {
    let t = Te[e];
    n2.dom.addEventListener(e, n2.input.eventHandlers[e] = (r) => {
      Im(n2, r) && !$l(n2, r) && (n2.editable || !(r.type in Oe)) && t(n2, r);
    }, Nm[e] ? { passive: true } : void 0);
  }
  Ee && n2.dom.addEventListener("input", () => null), tl(n2);
}
function Yt(n2, e) {
  n2.input.lastSelectionOrigin = e, n2.input.lastSelectionTime = Date.now();
}
function Rm(n2) {
  n2.domObserver.stop();
  for (let e in n2.input.eventHandlers)
    n2.dom.removeEventListener(e, n2.input.eventHandlers[e]);
  clearTimeout(n2.input.composingTimeout), clearTimeout(n2.input.lastIOSEnterFallbackTimeout);
}
function tl(n2) {
  n2.someProp("handleDOMEvents", (e) => {
    for (let t in e)
      n2.input.eventHandlers[t] || n2.dom.addEventListener(t, n2.input.eventHandlers[t] = (r) => $l(n2, r));
  });
}
function $l(n2, e) {
  return n2.someProp("handleDOMEvents", (t) => {
    let r = t[e.type];
    return r ? r(n2, e) || e.defaultPrevented : false;
  });
}
function Im(n2, e) {
  if (!e.bubbles)
    return true;
  if (e.defaultPrevented)
    return false;
  for (let t = e.target; t != n2.dom; t = t.parentNode)
    if (!t || t.nodeType == 11 || t.pmViewDesc && t.pmViewDesc.stopEvent(e))
      return false;
  return true;
}
function Pm(n2, e) {
  !$l(n2, e) && Te[e.type] && (n2.editable || !(e.type in Oe)) && Te[e.type](n2, e);
}
Oe.keydown = (n2, e) => {
  let t = e;
  if (n2.input.shiftKey = t.keyCode == 16 || t.shiftKey, !zd(n2, t) && (n2.input.lastKeyCode = t.keyCode, n2.input.lastKeyCodeTime = Date.now(), !(Dt && Ce && t.keyCode == 13)))
    if (t.keyCode != 229 && n2.domObserver.forceFlush(), rr && t.keyCode == 13 && !t.ctrlKey && !t.altKey && !t.metaKey) {
      let r = Date.now();
      n2.input.lastIOSEnter = r, n2.input.lastIOSEnterFallbackTimeout = setTimeout(() => {
        n2.input.lastIOSEnter == r && (n2.someProp("handleKeyDown", (i) => i(n2, pn(13, "Enter"))), n2.input.lastIOSEnter = 0);
      }, 200);
    } else n2.someProp("handleKeyDown", (r) => r(n2, t)) || xm(n2, t) ? t.preventDefault() : Yt(n2, "key");
};
Oe.keyup = (n2, e) => {
  e.keyCode == 16 && (n2.input.shiftKey = false);
};
Oe.keypress = (n2, e) => {
  let t = e;
  if (zd(n2, t) || !t.charCode || t.ctrlKey && !t.altKey || Ge && t.metaKey)
    return;
  if (n2.someProp("handleKeyPress", (i) => i(n2, t))) {
    t.preventDefault();
    return;
  }
  let r = n2.state.selection;
  if (!(r instanceof F) || !r.$from.sameParent(r.$to)) {
    let i = String.fromCharCode(t.charCode), s = () => n2.state.tr.insertText(i).scrollIntoView();
    !/[\r\n]/.test(i) && !n2.someProp("handleTextInput", (o) => o(n2, r.$from.pos, r.$to.pos, i, s)) && n2.dispatch(s()), t.preventDefault();
  }
};
function Ks(n2) {
  return { left: n2.clientX, top: n2.clientY };
}
function Bm(n2, e) {
  let t = e.x - n2.clientX, r = e.y - n2.clientY;
  return t * t + r * r < 100;
}
function _l(n2, e, t, r, i) {
  if (r == -1)
    return false;
  let s = n2.state.doc.resolve(r);
  for (let o = s.depth + 1; o > 0; o--)
    if (n2.someProp(e, (l) => o > s.depth ? l(n2, t, s.nodeAfter, s.before(o), i, true) : l(n2, t, s.node(o), s.before(o), i, false)))
      return true;
  return false;
}
function er(n2, e, t) {
  if (n2.focused || n2.focus(), n2.state.selection.eq(e))
    return;
  let r = n2.state.tr.setSelection(e);
  r.setMeta("pointer", true), n2.dispatch(r);
}
function Hm(n2, e) {
  if (e == -1)
    return false;
  let t = n2.state.doc.resolve(e), r = t.nodeAfter;
  return r && r.isAtom && B.isSelectable(r) ? (er(n2, new B(t)), true) : false;
}
function Fm(n2, e) {
  if (e == -1)
    return false;
  let t = n2.state.selection, r, i;
  t instanceof B && (r = t.node);
  let s = n2.state.doc.resolve(e);
  for (let o = s.depth + 1; o > 0; o--) {
    let l = o > s.depth ? s.nodeAfter : s.node(o);
    if (B.isSelectable(l)) {
      r && t.$from.depth > 0 && o >= t.$from.depth && s.before(t.$from.depth + 1) == t.$from.pos ? i = s.before(t.$from.depth) : i = s.before(o);
      break;
    }
  }
  return i != null ? (er(n2, B.create(n2.state.doc, i)), true) : false;
}
function zm(n2, e, t, r, i) {
  return _l(n2, "handleClickOn", e, t, r) || n2.someProp("handleClick", (s) => s(n2, e, r)) || (i ? Fm(n2, t) : Hm(n2, t));
}
function Vm(n2, e, t, r) {
  return _l(n2, "handleDoubleClickOn", e, t, r) || n2.someProp("handleDoubleClick", (i) => i(n2, e, r));
}
function $m(n2, e, t, r) {
  return _l(n2, "handleTripleClickOn", e, t, r) || n2.someProp("handleTripleClick", (i) => i(n2, e, r)) || _m(n2, t, r);
}
function _m(n2, e, t) {
  if (t.button != 0)
    return false;
  let r = n2.state.doc;
  if (e == -1)
    return r.inlineContent ? (er(n2, F.create(r, 0, r.content.size)), true) : false;
  let i = r.resolve(e);
  for (let s = i.depth + 1; s > 0; s--) {
    let o = s > i.depth ? i.nodeAfter : i.node(s), l = i.before(s);
    if (o.inlineContent)
      er(n2, F.create(r, l + 1, l + 1 + o.content.size));
    else if (B.isSelectable(o))
      er(n2, B.create(r, l));
    else
      continue;
    return true;
  }
}
function jl(n2) {
  return es(n2);
}
const Fd = Ge ? "metaKey" : "ctrlKey";
Te.mousedown = (n2, e) => {
  let t = e;
  n2.input.shiftKey = t.shiftKey;
  let r = jl(n2), i = Date.now(), s = "singleClick";
  i - n2.input.lastClick.time < 500 && Bm(t, n2.input.lastClick) && !t[Fd] && n2.input.lastClick.button == t.button && (n2.input.lastClick.type == "singleClick" ? s = "doubleClick" : n2.input.lastClick.type == "doubleClick" && (s = "tripleClick")), n2.input.lastClick = { time: i, x: t.clientX, y: t.clientY, type: s, button: t.button };
  let o = n2.posAtCoords(Ks(t));
  o && (s == "singleClick" ? (n2.input.mouseDown && n2.input.mouseDown.done(), n2.input.mouseDown = new jm(n2, o, t, !!r)) : (s == "doubleClick" ? Vm : $m)(n2, o.pos, o.inside, t) ? t.preventDefault() : Yt(n2, "pointer"));
};
class jm {
  constructor(e, t, r, i) {
    this.view = e, this.pos = t, this.event = r, this.flushed = i, this.delayedSelectionSync = false, this.mightDrag = null, this.startDoc = e.state.doc, this.selectNode = !!r[Fd], this.allowDefault = r.shiftKey;
    let s, o;
    if (t.inside > -1)
      s = e.state.doc.nodeAt(t.inside), o = t.inside;
    else {
      let u = e.state.doc.resolve(t.pos);
      s = u.parent, o = u.depth ? u.before() : 0;
    }
    const l = i ? null : r.target, a = l ? e.docView.nearestDesc(l, true) : null;
    this.target = a && a.dom.nodeType == 1 ? a.dom : null;
    let { selection: c } = e.state;
    (r.button == 0 && s.type.spec.draggable && s.type.spec.selectable !== false || c instanceof B && c.from <= o && c.to > o) && (this.mightDrag = {
      node: s,
      pos: o,
      addAttr: !!(this.target && !this.target.draggable),
      setUneditable: !!(this.target && ot && !this.target.hasAttribute("contentEditable"))
    }), this.target && this.mightDrag && (this.mightDrag.addAttr || this.mightDrag.setUneditable) && (this.view.domObserver.stop(), this.mightDrag.addAttr && (this.target.draggable = true), this.mightDrag.setUneditable && setTimeout(() => {
      this.view.input.mouseDown == this && this.target.setAttribute("contentEditable", "false");
    }, 20), this.view.domObserver.start()), e.root.addEventListener("mouseup", this.up = this.up.bind(this)), e.root.addEventListener("mousemove", this.move = this.move.bind(this)), Yt(e, "pointer");
  }
  done() {
    this.view.root.removeEventListener("mouseup", this.up), this.view.root.removeEventListener("mousemove", this.move), this.mightDrag && this.target && (this.view.domObserver.stop(), this.mightDrag.addAttr && this.target.removeAttribute("draggable"), this.mightDrag.setUneditable && this.target.removeAttribute("contentEditable"), this.view.domObserver.start()), this.delayedSelectionSync && setTimeout(() => It(this.view)), this.view.input.mouseDown = null;
  }
  up(e) {
    if (this.done(), !this.view.dom.contains(e.target))
      return;
    let t = this.pos;
    this.view.state.doc != this.startDoc && (t = this.view.posAtCoords(Ks(e))), this.updateAllowDefault(e), this.allowDefault || !t ? Yt(this.view, "pointer") : zm(this.view, t.pos, t.inside, e, this.selectNode) ? e.preventDefault() : e.button == 0 && (this.flushed || // Safari ignores clicks on draggable elements
    Ee && this.mightDrag && !this.mightDrag.node.isAtom || // Chrome will sometimes treat a node selection as a
    // cursor, but still report that the node is selected
    // when asked through getSelection. You'll then get a
    // situation where clicking at the point where that
    // (hidden) cursor is doesn't change the selection, and
    // thus doesn't get a reaction from ProseMirror. This
    // works around that.
    Ce && !this.view.state.selection.visible && Math.min(Math.abs(t.pos - this.view.state.selection.from), Math.abs(t.pos - this.view.state.selection.to)) <= 2) ? (er(this.view, $.near(this.view.state.doc.resolve(t.pos))), e.preventDefault()) : Yt(this.view, "pointer");
  }
  move(e) {
    this.updateAllowDefault(e), Yt(this.view, "pointer"), e.buttons == 0 && this.done();
  }
  updateAllowDefault(e) {
    !this.allowDefault && (Math.abs(this.event.x - e.clientX) > 4 || Math.abs(this.event.y - e.clientY) > 4) && (this.allowDefault = true);
  }
}
Te.touchstart = (n2) => {
  n2.input.lastTouch = Date.now(), jl(n2), Yt(n2, "pointer");
};
Te.touchmove = (n2) => {
  n2.input.lastTouch = Date.now(), Yt(n2, "pointer");
};
Te.contextmenu = (n2) => jl(n2);
function zd(n2, e) {
  return n2.composing ? true : Ee && Math.abs(e.timeStamp - n2.input.compositionEndedAt) < 500 ? (n2.input.compositionEndedAt = -2e8, true) : false;
}
const Wm = Dt ? 5e3 : -1;
Oe.compositionstart = Oe.compositionupdate = (n2) => {
  if (!n2.composing) {
    n2.domObserver.flush();
    let { state: e } = n2, t = e.selection.$to;
    if (e.selection instanceof F && (e.storedMarks || !t.textOffset && t.parentOffset && t.nodeBefore.marks.some((r) => r.type.spec.inclusive === false)))
      n2.markCursor = n2.state.storedMarks || t.marks(), es(n2, true), n2.markCursor = null;
    else if (es(n2, !e.selection.empty), ot && e.selection.empty && t.parentOffset && !t.textOffset && t.nodeBefore.marks.length) {
      let r = n2.domSelectionRange();
      for (let i = r.focusNode, s = r.focusOffset; i && i.nodeType == 1 && s != 0; ) {
        let o = s < 0 ? i.lastChild : i.childNodes[s - 1];
        if (!o)
          break;
        if (o.nodeType == 3) {
          let l = n2.domSelection();
          l && l.collapse(o, o.nodeValue.length);
          break;
        } else
          i = o, s = -1;
      }
    }
    n2.input.composing = true;
  }
  Vd(n2, Wm);
};
Oe.compositionend = (n2, e) => {
  n2.composing && (n2.input.composing = false, n2.input.compositionEndedAt = e.timeStamp, n2.input.compositionPendingChanges = n2.domObserver.pendingRecords().length ? n2.input.compositionID : 0, n2.input.compositionNode = null, n2.input.compositionPendingChanges && Promise.resolve().then(() => n2.domObserver.flush()), n2.input.compositionID++, Vd(n2, 20));
};
function Vd(n2, e) {
  clearTimeout(n2.input.composingTimeout), e > -1 && (n2.input.composingTimeout = setTimeout(() => es(n2), e));
}
function $d(n2) {
  for (n2.composing && (n2.input.composing = false, n2.input.compositionEndedAt = Km()); n2.input.compositionNodes.length > 0; )
    n2.input.compositionNodes.pop().markParentsDirty();
}
function Um(n2) {
  let e = n2.domSelectionRange();
  if (!e.focusNode)
    return null;
  let t = Hp(e.focusNode, e.focusOffset), r = Fp(e.focusNode, e.focusOffset);
  if (t && r && t != r) {
    let i = r.pmViewDesc, s = n2.domObserver.lastChangedTextNode;
    if (t == s || r == s)
      return s;
    if (!i || !i.isText(r.nodeValue))
      return r;
    if (n2.input.compositionNode == r) {
      let o = t.pmViewDesc;
      if (!(!o || !o.isText(t.nodeValue)))
        return r;
    }
  }
  return t || r;
}
function Km() {
  let n2 = document.createEvent("Event");
  return n2.initEvent("event", true, true), n2.timeStamp;
}
function es(n2, e = false) {
  if (!(Dt && n2.domObserver.flushingSoon >= 0)) {
    if (n2.domObserver.forceFlush(), $d(n2), e || n2.docView && n2.docView.dirty) {
      let t = Fl(n2), r = n2.state.selection;
      return t && !t.eq(r) ? n2.dispatch(n2.state.tr.setSelection(t)) : (n2.markCursor || e) && !r.$from.node(r.$from.sharedDepth(r.to)).inlineContent ? n2.dispatch(n2.state.tr.deleteSelection()) : n2.updateState(n2.state), true;
    }
    return false;
  }
}
function qm(n2, e) {
  if (!n2.dom.parentNode)
    return;
  let t = n2.dom.parentNode.appendChild(document.createElement("div"));
  t.appendChild(e), t.style.cssText = "position: fixed; left: -10000px; top: 10px";
  let r = getSelection(), i = document.createRange();
  i.selectNodeContents(e), n2.dom.blur(), r.removeAllRanges(), r.addRange(i), setTimeout(() => {
    t.parentNode && t.parentNode.removeChild(t), n2.focus();
  }, 50);
}
const Ur = Re && en < 15 || rr && jp < 604;
Te.copy = Oe.cut = (n2, e) => {
  let t = e, r = n2.state.selection, i = t.type == "cut";
  if (r.empty)
    return;
  let s = Ur ? null : t.clipboardData, o = r.content(), { dom: l, text: a } = Vl(n2, o);
  s ? (t.preventDefault(), s.clearData(), s.setData("text/html", l.innerHTML), s.setData("text/plain", a)) : qm(n2, l), i && n2.dispatch(n2.state.tr.deleteSelection().scrollIntoView().setMeta("uiEvent", "cut"));
};
function Jm(n2) {
  return n2.openStart == 0 && n2.openEnd == 0 && n2.content.childCount == 1 ? n2.content.firstChild : null;
}
function Gm(n2, e) {
  if (!n2.dom.parentNode)
    return;
  let t = n2.input.shiftKey || n2.state.selection.$from.parent.type.spec.code, r = n2.dom.parentNode.appendChild(document.createElement(t ? "textarea" : "div"));
  t || (r.contentEditable = "true"), r.style.cssText = "position: fixed; left: -10000px; top: 10px", r.focus();
  let i = n2.input.shiftKey && n2.input.lastKeyCode != 45;
  setTimeout(() => {
    n2.focus(), r.parentNode && r.parentNode.removeChild(r), t ? Kr(n2, r.value, null, i, e) : Kr(n2, r.textContent, r.innerHTML, i, e);
  }, 50);
}
function Kr(n2, e, t, r, i) {
  let s = Ld(n2, e, t, r, n2.state.selection.$from);
  if (n2.someProp("handlePaste", (a) => a(n2, i, s || O.empty)))
    return true;
  if (!s)
    return false;
  let o = Jm(s), l = o ? n2.state.tr.replaceSelectionWith(o, r) : n2.state.tr.replaceSelection(s);
  return n2.dispatch(l.scrollIntoView().setMeta("paste", true).setMeta("uiEvent", "paste")), true;
}
function _d(n2) {
  let e = n2.getData("text/plain") || n2.getData("Text");
  if (e)
    return e;
  let t = n2.getData("text/uri-list");
  return t ? t.replace(/\r?\n/g, " ") : "";
}
Oe.paste = (n2, e) => {
  let t = e;
  if (n2.composing && !Dt)
    return;
  let r = Ur ? null : t.clipboardData, i = n2.input.shiftKey && n2.input.lastKeyCode != 45;
  r && Kr(n2, _d(r), r.getData("text/html"), i, t) ? t.preventDefault() : Gm(n2, t);
};
class jd {
  constructor(e, t, r) {
    this.slice = e, this.move = t, this.node = r;
  }
}
const Ym = Ge ? "altKey" : "ctrlKey";
function Wd(n2, e) {
  let t = n2.someProp("dragCopies", (r) => !r(e));
  return t ?? !e[Ym];
}
Te.dragstart = (n2, e) => {
  let t = e, r = n2.input.mouseDown;
  if (r && r.done(), !t.dataTransfer)
    return;
  let i = n2.state.selection, s = i.empty ? null : n2.posAtCoords(Ks(t)), o;
  if (!(s && s.pos >= i.from && s.pos <= (i instanceof B ? i.to - 1 : i.to))) {
    if (r && r.mightDrag)
      o = B.create(n2.state.doc, r.mightDrag.pos);
    else if (t.target && t.target.nodeType == 1) {
      let d = n2.docView.nearestDesc(t.target, true);
      d && d.node.type.spec.draggable && d != n2.docView && (o = B.create(n2.state.doc, d.posBefore));
    }
  }
  let l = (o || n2.state.selection).content(), { dom: a, text: c, slice: u } = Vl(n2, l);
  (!t.dataTransfer.files.length || !Ce || gd > 120) && t.dataTransfer.clearData(), t.dataTransfer.setData(Ur ? "Text" : "text/html", a.innerHTML), t.dataTransfer.effectAllowed = "copyMove", Ur || t.dataTransfer.setData("text/plain", c), n2.dragging = new jd(u, Wd(n2, t), o);
};
Te.dragend = (n2) => {
  let e = n2.dragging;
  window.setTimeout(() => {
    n2.dragging == e && (n2.dragging = null);
  }, 50);
};
Oe.dragover = Oe.dragenter = (n2, e) => e.preventDefault();
Oe.drop = (n2, e) => {
  let t = e, r = n2.dragging;
  if (n2.dragging = null, !t.dataTransfer)
    return;
  let i = n2.posAtCoords(Ks(t));
  if (!i)
    return;
  let s = n2.state.doc.resolve(i.pos), o = r && r.slice;
  o ? n2.someProp("transformPasted", (p2) => {
    o = p2(o, n2);
  }) : o = Ld(n2, _d(t.dataTransfer), Ur ? null : t.dataTransfer.getData("text/html"), false, s);
  let l = !!(r && Wd(n2, t));
  if (n2.someProp("handleDrop", (p2) => p2(n2, t, o || O.empty, l))) {
    t.preventDefault();
    return;
  }
  if (!o)
    return;
  t.preventDefault();
  let a = o ? ld(n2.state.doc, s.pos, o) : s.pos;
  a == null && (a = s.pos);
  let c = n2.state.tr;
  if (l) {
    let { node: p2 } = r;
    p2 ? p2.replace(c) : c.deleteSelection();
  }
  let u = c.mapping.map(a), d = o.openStart == 0 && o.openEnd == 0 && o.content.childCount == 1, f = c.doc;
  if (d ? c.replaceRangeWith(u, u, o.content.firstChild) : c.replaceRange(u, u, o), c.doc.eq(f))
    return;
  let h2 = c.doc.resolve(u);
  if (d && B.isSelectable(o.content.firstChild) && h2.nodeAfter && h2.nodeAfter.sameMarkup(o.content.firstChild))
    c.setSelection(new B(h2));
  else {
    let p2 = c.mapping.map(a);
    c.mapping.maps[c.mapping.maps.length - 1].forEach((m, g, y, w) => p2 = w), c.setSelection(zl(n2, h2, c.doc.resolve(p2)));
  }
  n2.focus(), n2.dispatch(c.setMeta("uiEvent", "drop"));
};
Te.focus = (n2) => {
  n2.input.lastFocus = Date.now(), n2.focused || (n2.domObserver.stop(), n2.dom.classList.add("ProseMirror-focused"), n2.domObserver.start(), n2.focused = true, setTimeout(() => {
    n2.docView && n2.hasFocus() && !n2.domObserver.currentSelection.eq(n2.domSelectionRange()) && It(n2);
  }, 20));
};
Te.blur = (n2, e) => {
  let t = e;
  n2.focused && (n2.domObserver.stop(), n2.dom.classList.remove("ProseMirror-focused"), n2.domObserver.start(), t.relatedTarget && n2.dom.contains(t.relatedTarget) && n2.domObserver.currentSelection.clear(), n2.focused = false);
};
Te.beforeinput = (n2, e) => {
  if (Ce && Dt && e.inputType == "deleteContentBackward") {
    n2.domObserver.flushSoon();
    let { domChangeCount: r } = n2.input;
    setTimeout(() => {
      if (n2.input.domChangeCount != r || (n2.dom.blur(), n2.focus(), n2.someProp("handleKeyDown", (s) => s(n2, pn(8, "Backspace")))))
        return;
      let { $cursor: i } = n2.state.selection;
      i && i.pos > 0 && n2.dispatch(n2.state.tr.delete(i.pos - 1, i.pos).scrollIntoView());
    }, 50);
  }
};
for (let n2 in Oe)
  Te[n2] = Oe[n2];
function qr(n2, e) {
  if (n2 == e)
    return true;
  for (let t in n2)
    if (n2[t] !== e[t])
      return false;
  for (let t in e)
    if (!(t in n2))
      return false;
  return true;
}
class ts {
  constructor(e, t) {
    this.toDOM = e, this.spec = t || Sn, this.side = this.spec.side || 0;
  }
  map(e, t, r, i) {
    let { pos: s, deleted: o } = e.mapResult(t.from + i, this.side < 0 ? -1 : 1);
    return o ? null : new xe(s - r, s - r, this);
  }
  valid() {
    return true;
  }
  eq(e) {
    return this == e || e instanceof ts && (this.spec.key && this.spec.key == e.spec.key || this.toDOM == e.toDOM && qr(this.spec, e.spec));
  }
  destroy(e) {
    this.spec.destroy && this.spec.destroy(e);
  }
}
class nn {
  constructor(e, t) {
    this.attrs = e, this.spec = t || Sn;
  }
  map(e, t, r, i) {
    let s = e.map(t.from + i, this.spec.inclusiveStart ? -1 : 1) - r, o = e.map(t.to + i, this.spec.inclusiveEnd ? 1 : -1) - r;
    return s >= o ? null : new xe(s, o, this);
  }
  valid(e, t) {
    return t.from < t.to;
  }
  eq(e) {
    return this == e || e instanceof nn && qr(this.attrs, e.attrs) && qr(this.spec, e.spec);
  }
  static is(e) {
    return e.type instanceof nn;
  }
  destroy() {
  }
}
class Wl {
  constructor(e, t) {
    this.attrs = e, this.spec = t || Sn;
  }
  map(e, t, r, i) {
    let s = e.mapResult(t.from + i, 1);
    if (s.deleted)
      return null;
    let o = e.mapResult(t.to + i, -1);
    return o.deleted || o.pos <= s.pos ? null : new xe(s.pos - r, o.pos - r, this);
  }
  valid(e, t) {
    let { index: r, offset: i } = e.content.findIndex(t.from), s;
    return i == t.from && !(s = e.child(r)).isText && i + s.nodeSize == t.to;
  }
  eq(e) {
    return this == e || e instanceof Wl && qr(this.attrs, e.attrs) && qr(this.spec, e.spec);
  }
  destroy() {
  }
}
class xe {
  /**
  @internal
  */
  constructor(e, t, r) {
    this.from = e, this.to = t, this.type = r;
  }
  /**
  @internal
  */
  copy(e, t) {
    return new xe(e, t, this.type);
  }
  /**
  @internal
  */
  eq(e, t = 0) {
    return this.type.eq(e.type) && this.from + t == e.from && this.to + t == e.to;
  }
  /**
  @internal
  */
  map(e, t, r) {
    return this.type.map(e, this, t, r);
  }
  /**
  Creates a widget decoration, which is a DOM node that's shown in
  the document at the given position. It is recommended that you
  delay rendering the widget by passing a function that will be
  called when the widget is actually drawn in a view, but you can
  also directly pass a DOM node. `getPos` can be used to find the
  widget's current document position.
  */
  static widget(e, t, r) {
    return new xe(e, e, new ts(t, r));
  }
  /**
  Creates an inline decoration, which adds the given attributes to
  each inline node between `from` and `to`.
  */
  static inline(e, t, r, i) {
    return new xe(e, t, new nn(r, i));
  }
  /**
  Creates a node decoration. `from` and `to` should point precisely
  before and after a node in the document. That node, and only that
  node, will receive the given attributes.
  */
  static node(e, t, r, i) {
    return new xe(e, t, new Wl(r, i));
  }
  /**
  The spec provided when creating this decoration. Can be useful
  if you've stored extra information in that object.
  */
  get spec() {
    return this.type.spec;
  }
  /**
  @internal
  */
  get inline() {
    return this.type instanceof nn;
  }
  /**
  @internal
  */
  get widget() {
    return this.type instanceof ts;
  }
}
const Jn = [], Sn = {};
class ie {
  /**
  @internal
  */
  constructor(e, t) {
    this.local = e.length ? e : Jn, this.children = t.length ? t : Jn;
  }
  /**
  Create a set of decorations, using the structure of the given
  document. This will consume (modify) the `decorations` array, so
  you must make a copy if you want need to preserve that.
  */
  static create(e, t) {
    return t.length ? ns(t, e, 0, Sn) : ke;
  }
  /**
  Find all decorations in this set which touch the given range
  (including decorations that start or end directly at the
  boundaries) and match the given predicate on their spec. When
  `start` and `end` are omitted, all decorations in the set are
  considered. When `predicate` isn't given, all decorations are
  assumed to match.
  */
  find(e, t, r) {
    let i = [];
    return this.findInner(e ?? 0, t ?? 1e9, i, 0, r), i;
  }
  findInner(e, t, r, i, s) {
    for (let o = 0; o < this.local.length; o++) {
      let l = this.local[o];
      l.from <= t && l.to >= e && (!s || s(l.spec)) && r.push(l.copy(l.from + i, l.to + i));
    }
    for (let o = 0; o < this.children.length; o += 3)
      if (this.children[o] < t && this.children[o + 1] > e) {
        let l = this.children[o] + 1;
        this.children[o + 2].findInner(e - l, t - l, r, i + l, s);
      }
  }
  /**
  Map the set of decorations in response to a change in the
  document.
  */
  map(e, t, r) {
    return this == ke || e.maps.length == 0 ? this : this.mapInner(e, t, 0, 0, r || Sn);
  }
  /**
  @internal
  */
  mapInner(e, t, r, i, s) {
    let o;
    for (let l = 0; l < this.local.length; l++) {
      let a = this.local[l].map(e, r, i);
      a && a.type.valid(t, a) ? (o || (o = [])).push(a) : s.onRemove && s.onRemove(this.local[l].spec);
    }
    return this.children.length ? Xm(this.children, o || [], e, t, r, i, s) : o ? new ie(o.sort(Mn), Jn) : ke;
  }
  /**
  Add the given array of decorations to the ones in the set,
  producing a new set. Consumes the `decorations` array. Needs
  access to the current document to create the appropriate tree
  structure.
  */
  add(e, t) {
    return t.length ? this == ke ? ie.create(e, t) : this.addInner(e, t, 0) : this;
  }
  addInner(e, t, r) {
    let i, s = 0;
    e.forEach((l, a) => {
      let c = a + r, u;
      if (u = Kd(t, l, c)) {
        for (i || (i = this.children.slice()); s < i.length && i[s] < a; )
          s += 3;
        i[s] == a ? i[s + 2] = i[s + 2].addInner(l, u, c + 1) : i.splice(s, 0, a, a + l.nodeSize, ns(u, l, c + 1, Sn)), s += 3;
      }
    });
    let o = Ud(s ? qd(t) : t, -r);
    for (let l = 0; l < o.length; l++)
      o[l].type.valid(e, o[l]) || o.splice(l--, 1);
    return new ie(o.length ? this.local.concat(o).sort(Mn) : this.local, i || this.children);
  }
  /**
  Create a new set that contains the decorations in this set, minus
  the ones in the given array.
  */
  remove(e) {
    return e.length == 0 || this == ke ? this : this.removeInner(e, 0);
  }
  removeInner(e, t) {
    let r = this.children, i = this.local;
    for (let s = 0; s < r.length; s += 3) {
      let o, l = r[s] + t, a = r[s + 1] + t;
      for (let u = 0, d; u < e.length; u++)
        (d = e[u]) && d.from > l && d.to < a && (e[u] = null, (o || (o = [])).push(d));
      if (!o)
        continue;
      r == this.children && (r = this.children.slice());
      let c = r[s + 2].removeInner(o, l + 1);
      c != ke ? r[s + 2] = c : (r.splice(s, 3), s -= 3);
    }
    if (i.length) {
      for (let s = 0, o; s < e.length; s++)
        if (o = e[s])
          for (let l = 0; l < i.length; l++)
            i[l].eq(o, t) && (i == this.local && (i = this.local.slice()), i.splice(l--, 1));
    }
    return r == this.children && i == this.local ? this : i.length || r.length ? new ie(i, r) : ke;
  }
  forChild(e, t) {
    if (this == ke)
      return this;
    if (t.isLeaf)
      return ie.empty;
    let r, i;
    for (let l = 0; l < this.children.length; l += 3)
      if (this.children[l] >= e) {
        this.children[l] == e && (r = this.children[l + 2]);
        break;
      }
    let s = e + 1, o = s + t.content.size;
    for (let l = 0; l < this.local.length; l++) {
      let a = this.local[l];
      if (a.from < o && a.to > s && a.type instanceof nn) {
        let c = Math.max(s, a.from) - s, u = Math.min(o, a.to) - s;
        c < u && (i || (i = [])).push(a.copy(c, u));
      }
    }
    if (i) {
      let l = new ie(i.sort(Mn), Jn);
      return r ? new Wt([l, r]) : l;
    }
    return r || ke;
  }
  /**
  @internal
  */
  eq(e) {
    if (this == e)
      return true;
    if (!(e instanceof ie) || this.local.length != e.local.length || this.children.length != e.children.length)
      return false;
    for (let t = 0; t < this.local.length; t++)
      if (!this.local[t].eq(e.local[t]))
        return false;
    for (let t = 0; t < this.children.length; t += 3)
      if (this.children[t] != e.children[t] || this.children[t + 1] != e.children[t + 1] || !this.children[t + 2].eq(e.children[t + 2]))
        return false;
    return true;
  }
  /**
  @internal
  */
  locals(e) {
    return Ul(this.localsInner(e));
  }
  /**
  @internal
  */
  localsInner(e) {
    if (this == ke)
      return Jn;
    if (e.inlineContent || !this.local.some(nn.is))
      return this.local;
    let t = [];
    for (let r = 0; r < this.local.length; r++)
      this.local[r].type instanceof nn || t.push(this.local[r]);
    return t;
  }
  forEachSet(e) {
    e(this);
  }
}
ie.empty = new ie([], []);
ie.removeOverlap = Ul;
const ke = ie.empty;
class Wt {
  constructor(e) {
    this.members = e;
  }
  map(e, t) {
    const r = this.members.map((i) => i.map(e, t, Sn));
    return Wt.from(r);
  }
  forChild(e, t) {
    if (t.isLeaf)
      return ie.empty;
    let r = [];
    for (let i = 0; i < this.members.length; i++) {
      let s = this.members[i].forChild(e, t);
      s != ke && (s instanceof Wt ? r = r.concat(s.members) : r.push(s));
    }
    return Wt.from(r);
  }
  eq(e) {
    if (!(e instanceof Wt) || e.members.length != this.members.length)
      return false;
    for (let t = 0; t < this.members.length; t++)
      if (!this.members[t].eq(e.members[t]))
        return false;
    return true;
  }
  locals(e) {
    let t, r = true;
    for (let i = 0; i < this.members.length; i++) {
      let s = this.members[i].localsInner(e);
      if (s.length)
        if (!t)
          t = s;
        else {
          r && (t = t.slice(), r = false);
          for (let o = 0; o < s.length; o++)
            t.push(s[o]);
        }
    }
    return t ? Ul(r ? t : t.sort(Mn)) : Jn;
  }
  // Create a group for the given array of decoration sets, or return
  // a single set when possible.
  static from(e) {
    switch (e.length) {
      case 0:
        return ke;
      case 1:
        return e[0];
      default:
        return new Wt(e.every((t) => t instanceof ie) ? e : e.reduce((t, r) => t.concat(r instanceof ie ? r : r.members), []));
    }
  }
  forEachSet(e) {
    for (let t = 0; t < this.members.length; t++)
      this.members[t].forEachSet(e);
  }
}
function Xm(n2, e, t, r, i, s, o) {
  let l = n2.slice();
  for (let c = 0, u = s; c < t.maps.length; c++) {
    let d = 0;
    t.maps[c].forEach((f, h2, p2, m) => {
      let g = m - p2 - (h2 - f);
      for (let y = 0; y < l.length; y += 3) {
        let w = l[y + 1];
        if (w < 0 || f > w + u - d)
          continue;
        let C = l[y] + u - d;
        h2 >= C ? l[y + 1] = f <= C ? -2 : -1 : f >= u && g && (l[y] += g, l[y + 1] += g);
      }
      d += g;
    }), u = t.maps[c].map(u, -1);
  }
  let a = false;
  for (let c = 0; c < l.length; c += 3)
    if (l[c + 1] < 0) {
      if (l[c + 1] == -2) {
        a = true, l[c + 1] = -1;
        continue;
      }
      let u = t.map(n2[c] + s), d = u - i;
      if (d < 0 || d >= r.content.size) {
        a = true;
        continue;
      }
      let f = t.map(n2[c + 1] + s, -1), h2 = f - i, { index: p2, offset: m } = r.content.findIndex(d), g = r.maybeChild(p2);
      if (g && m == d && m + g.nodeSize == h2) {
        let y = l[c + 2].mapInner(t, g, u + 1, n2[c] + s + 1, o);
        y != ke ? (l[c] = d, l[c + 1] = h2, l[c + 2] = y) : (l[c + 1] = -2, a = true);
      } else
        a = true;
    }
  if (a) {
    let c = Qm(l, n2, e, t, i, s, o), u = ns(c, r, 0, o);
    e = u.local;
    for (let d = 0; d < l.length; d += 3)
      l[d + 1] < 0 && (l.splice(d, 3), d -= 3);
    for (let d = 0, f = 0; d < u.children.length; d += 3) {
      let h2 = u.children[d];
      for (; f < l.length && l[f] < h2; )
        f += 3;
      l.splice(f, 0, u.children[d], u.children[d + 1], u.children[d + 2]);
    }
  }
  return new ie(e.sort(Mn), l);
}
function Ud(n2, e) {
  if (!e || !n2.length)
    return n2;
  let t = [];
  for (let r = 0; r < n2.length; r++) {
    let i = n2[r];
    t.push(new xe(i.from + e, i.to + e, i.type));
  }
  return t;
}
function Qm(n2, e, t, r, i, s, o) {
  function l(a, c) {
    for (let u = 0; u < a.local.length; u++) {
      let d = a.local[u].map(r, i, c);
      d ? t.push(d) : o.onRemove && o.onRemove(a.local[u].spec);
    }
    for (let u = 0; u < a.children.length; u += 3)
      l(a.children[u + 2], a.children[u] + c + 1);
  }
  for (let a = 0; a < n2.length; a += 3)
    n2[a + 1] == -1 && l(n2[a + 2], e[a] + s + 1);
  return t;
}
function Kd(n2, e, t) {
  if (e.isLeaf)
    return null;
  let r = t + e.nodeSize, i = null;
  for (let s = 0, o; s < n2.length; s++)
    (o = n2[s]) && o.from > t && o.to < r && ((i || (i = [])).push(o), n2[s] = null);
  return i;
}
function qd(n2) {
  let e = [];
  for (let t = 0; t < n2.length; t++)
    n2[t] != null && e.push(n2[t]);
  return e;
}
function ns(n2, e, t, r) {
  let i = [], s = false;
  e.forEach((l, a) => {
    let c = Kd(n2, l, a + t);
    if (c) {
      s = true;
      let u = ns(c, l, t + a + 1, r);
      u != ke && i.push(a, a + l.nodeSize, u);
    }
  });
  let o = Ud(s ? qd(n2) : n2, -t).sort(Mn);
  for (let l = 0; l < o.length; l++)
    o[l].type.valid(e, o[l]) || (r.onRemove && r.onRemove(o[l].spec), o.splice(l--, 1));
  return o.length || i.length ? new ie(o, i) : ke;
}
function Mn(n2, e) {
  return n2.from - e.from || n2.to - e.to;
}
function Ul(n2) {
  let e = n2;
  for (let t = 0; t < e.length - 1; t++) {
    let r = e[t];
    if (r.from != r.to)
      for (let i = t + 1; i < e.length; i++) {
        let s = e[i];
        if (s.from == r.from) {
          s.to != r.to && (e == n2 && (e = n2.slice()), e[i] = s.copy(s.from, r.to), fc(e, i + 1, s.copy(r.to, s.to)));
          continue;
        } else {
          s.from < r.to && (e == n2 && (e = n2.slice()), e[t] = r.copy(r.from, s.from), fc(e, i, r.copy(s.from, r.to)));
          break;
        }
      }
  }
  return e;
}
function fc(n2, e, t) {
  for (; e < n2.length && Mn(t, n2[e]) > 0; )
    e++;
  n2.splice(e, 0, t);
}
function vo(n2) {
  let e = [];
  return n2.someProp("decorations", (t) => {
    let r = t(n2.state);
    r && r != ke && e.push(r);
  }), n2.cursorWrapper && e.push(ie.create(n2.state.doc, [n2.cursorWrapper.deco])), Wt.from(e);
}
const Zm = {
  childList: true,
  characterData: true,
  characterDataOldValue: true,
  attributes: true,
  attributeOldValue: true,
  subtree: true
}, eg = Re && en <= 11;
class tg {
  constructor() {
    this.anchorNode = null, this.anchorOffset = 0, this.focusNode = null, this.focusOffset = 0;
  }
  set(e) {
    this.anchorNode = e.anchorNode, this.anchorOffset = e.anchorOffset, this.focusNode = e.focusNode, this.focusOffset = e.focusOffset;
  }
  clear() {
    this.anchorNode = this.focusNode = null;
  }
  eq(e) {
    return e.anchorNode == this.anchorNode && e.anchorOffset == this.anchorOffset && e.focusNode == this.focusNode && e.focusOffset == this.focusOffset;
  }
}
class ng {
  constructor(e, t) {
    this.view = e, this.handleDOMChange = t, this.queue = [], this.flushingSoon = -1, this.observer = null, this.currentSelection = new tg(), this.onCharData = null, this.suppressingSelectionUpdates = false, this.lastChangedTextNode = null, this.observer = window.MutationObserver && new window.MutationObserver((r) => {
      for (let i = 0; i < r.length; i++)
        this.queue.push(r[i]);
      Re && en <= 11 && r.some((i) => i.type == "childList" && i.removedNodes.length || i.type == "characterData" && i.oldValue.length > i.target.nodeValue.length) ? this.flushSoon() : this.flush();
    }), eg && (this.onCharData = (r) => {
      this.queue.push({ target: r.target, type: "characterData", oldValue: r.prevValue }), this.flushSoon();
    }), this.onSelectionChange = this.onSelectionChange.bind(this);
  }
  flushSoon() {
    this.flushingSoon < 0 && (this.flushingSoon = window.setTimeout(() => {
      this.flushingSoon = -1, this.flush();
    }, 20));
  }
  forceFlush() {
    this.flushingSoon > -1 && (window.clearTimeout(this.flushingSoon), this.flushingSoon = -1, this.flush());
  }
  start() {
    this.observer && (this.observer.takeRecords(), this.observer.observe(this.view.dom, Zm)), this.onCharData && this.view.dom.addEventListener("DOMCharacterDataModified", this.onCharData), this.connectSelection();
  }
  stop() {
    if (this.observer) {
      let e = this.observer.takeRecords();
      if (e.length) {
        for (let t = 0; t < e.length; t++)
          this.queue.push(e[t]);
        window.setTimeout(() => this.flush(), 20);
      }
      this.observer.disconnect();
    }
    this.onCharData && this.view.dom.removeEventListener("DOMCharacterDataModified", this.onCharData), this.disconnectSelection();
  }
  connectSelection() {
    this.view.dom.ownerDocument.addEventListener("selectionchange", this.onSelectionChange);
  }
  disconnectSelection() {
    this.view.dom.ownerDocument.removeEventListener("selectionchange", this.onSelectionChange);
  }
  suppressSelectionUpdates() {
    this.suppressingSelectionUpdates = true, setTimeout(() => this.suppressingSelectionUpdates = false, 50);
  }
  onSelectionChange() {
    if (ic(this.view)) {
      if (this.suppressingSelectionUpdates)
        return It(this.view);
      if (Re && en <= 11 && !this.view.state.selection.empty) {
        let e = this.view.domSelectionRange();
        if (e.focusNode && Nn(e.focusNode, e.focusOffset, e.anchorNode, e.anchorOffset))
          return this.flushSoon();
      }
      this.flush();
    }
  }
  setCurSelection() {
    this.currentSelection.set(this.view.domSelectionRange());
  }
  ignoreSelectionChange(e) {
    if (!e.focusNode)
      return true;
    let t = /* @__PURE__ */ new Set(), r;
    for (let s = e.focusNode; s; s = nr(s))
      t.add(s);
    for (let s = e.anchorNode; s; s = nr(s))
      if (t.has(s)) {
        r = s;
        break;
      }
    let i = r && this.view.docView.nearestDesc(r);
    if (i && i.ignoreMutation({
      type: "selection",
      target: r.nodeType == 3 ? r.parentNode : r
    }))
      return this.setCurSelection(), true;
  }
  pendingRecords() {
    if (this.observer)
      for (let e of this.observer.takeRecords())
        this.queue.push(e);
    return this.queue;
  }
  flush() {
    let { view: e } = this;
    if (!e.docView || this.flushingSoon > -1)
      return;
    let t = this.pendingRecords();
    t.length && (this.queue = []);
    let r = e.domSelectionRange(), i = !this.suppressingSelectionUpdates && !this.currentSelection.eq(r) && ic(e) && !this.ignoreSelectionChange(r), s = -1, o = -1, l = false, a = [];
    if (e.editable)
      for (let u = 0; u < t.length; u++) {
        let d = this.registerMutation(t[u], a);
        d && (s = s < 0 ? d.from : Math.min(d.from, s), o = o < 0 ? d.to : Math.max(d.to, o), d.typeOver && (l = true));
      }
    if (ot && a.length) {
      let u = a.filter((d) => d.nodeName == "BR");
      if (u.length == 2) {
        let [d, f] = u;
        d.parentNode && d.parentNode.parentNode == f.parentNode ? f.remove() : d.remove();
      } else {
        let { focusNode: d } = this.currentSelection;
        for (let f of u) {
          let h2 = f.parentNode;
          h2 && h2.nodeName == "LI" && (!d || sg(e, d) != h2) && f.remove();
        }
      }
    }
    let c = null;
    s < 0 && i && e.input.lastFocus > Date.now() - 200 && Math.max(e.input.lastTouch, e.input.lastClick.time) < Date.now() - 300 && Ws(r) && (c = Fl(e)) && c.eq($.near(e.state.doc.resolve(0), 1)) ? (e.input.lastFocus = 0, It(e), this.currentSelection.set(r), e.scrollToSelection()) : (s > -1 || i) && (s > -1 && (e.docView.markDirty(s, o), rg(e)), this.handleDOMChange(s, o, l, a), e.docView && e.docView.dirty ? e.updateState(e.state) : this.currentSelection.eq(r) || It(e), this.currentSelection.set(r));
  }
  registerMutation(e, t) {
    if (t.indexOf(e.target) > -1)
      return null;
    let r = this.view.docView.nearestDesc(e.target);
    if (e.type == "attributes" && (r == this.view.docView || e.attributeName == "contenteditable" || // Firefox sometimes fires spurious events for null/empty styles
    e.attributeName == "style" && !e.oldValue && !e.target.getAttribute("style")) || !r || r.ignoreMutation(e))
      return null;
    if (e.type == "childList") {
      for (let u = 0; u < e.addedNodes.length; u++) {
        let d = e.addedNodes[u];
        t.push(d), d.nodeType == 3 && (this.lastChangedTextNode = d);
      }
      if (r.contentDOM && r.contentDOM != r.dom && !r.contentDOM.contains(e.target))
        return { from: r.posBefore, to: r.posAfter };
      let i = e.previousSibling, s = e.nextSibling;
      if (Re && en <= 11 && e.addedNodes.length)
        for (let u = 0; u < e.addedNodes.length; u++) {
          let { previousSibling: d, nextSibling: f } = e.addedNodes[u];
          (!d || Array.prototype.indexOf.call(e.addedNodes, d) < 0) && (i = d), (!f || Array.prototype.indexOf.call(e.addedNodes, f) < 0) && (s = f);
        }
      let o = i && i.parentNode == e.target ? ve(i) + 1 : 0, l = r.localPosFromDOM(e.target, o, -1), a = s && s.parentNode == e.target ? ve(s) : e.target.childNodes.length, c = r.localPosFromDOM(e.target, a, 1);
      return { from: l, to: c };
    } else return e.type == "attributes" ? { from: r.posAtStart - r.border, to: r.posAtEnd + r.border } : (this.lastChangedTextNode = e.target, {
      from: r.posAtStart,
      to: r.posAtEnd,
      // An event was generated for a text change that didn't change
      // any text. Mark the dom change to fall back to assuming the
      // selection was typed over with an identical value if it can't
      // find another change.
      typeOver: e.target.nodeValue == e.oldValue
    });
  }
}
let hc = /* @__PURE__ */ new WeakMap(), pc = false;
function rg(n2) {
  if (!hc.has(n2) && (hc.set(n2, null), ["normal", "nowrap", "pre-line"].indexOf(getComputedStyle(n2.dom).whiteSpace) !== -1)) {
    if (n2.requiresGeckoHackNode = ot, pc)
      return;
    console.warn("ProseMirror expects the CSS white-space property to be set, preferably to 'pre-wrap'. It is recommended to load style/prosemirror.css from the prosemirror-view package."), pc = true;
  }
}
function mc(n2, e) {
  let t = e.startContainer, r = e.startOffset, i = e.endContainer, s = e.endOffset, o = n2.domAtPos(n2.state.selection.anchor);
  return Nn(o.node, o.offset, i, s) && ([t, r, i, s] = [i, s, t, r]), { anchorNode: t, anchorOffset: r, focusNode: i, focusOffset: s };
}
function ig(n2, e) {
  if (e.getComposedRanges) {
    let i = e.getComposedRanges(n2.root)[0];
    if (i)
      return mc(n2, i);
  }
  let t;
  function r(i) {
    i.preventDefault(), i.stopImmediatePropagation(), t = i.getTargetRanges()[0];
  }
  return n2.dom.addEventListener("beforeinput", r, true), document.execCommand("indent"), n2.dom.removeEventListener("beforeinput", r, true), t ? mc(n2, t) : null;
}
function sg(n2, e) {
  for (let t = e.parentNode; t && t != n2.dom; t = t.parentNode) {
    let r = n2.docView.nearestDesc(t, true);
    if (r && r.node.isBlock)
      return t;
  }
  return null;
}
function og(n2, e, t) {
  let { node: r, fromOffset: i, toOffset: s, from: o, to: l } = n2.docView.parseRange(e, t), a = n2.domSelectionRange(), c, u = a.anchorNode;
  if (u && n2.dom.contains(u.nodeType == 1 ? u : u.parentNode) && (c = [{ node: u, offset: a.anchorOffset }], Ws(a) || c.push({ node: a.focusNode, offset: a.focusOffset })), Ce && n2.input.lastKeyCode === 8)
    for (let g = s; g > i; g--) {
      let y = r.childNodes[g - 1], w = y.pmViewDesc;
      if (y.nodeName == "BR" && !w) {
        s = g;
        break;
      }
      if (!w || w.size)
        break;
    }
  let d = n2.state.doc, f = n2.someProp("domParser") || Zt.fromSchema(n2.state.schema), h2 = d.resolve(o), p2 = null, m = f.parse(r, {
    topNode: h2.parent,
    topMatch: h2.parent.contentMatchAt(h2.index()),
    topOpen: true,
    from: i,
    to: s,
    preserveWhitespace: h2.parent.type.whitespace == "pre" ? "full" : true,
    findPositions: c,
    ruleFromNode: lg,
    context: h2
  });
  if (c && c[0].pos != null) {
    let g = c[0].pos, y = c[1] && c[1].pos;
    y == null && (y = g), p2 = { anchor: g + o, head: y + o };
  }
  return { doc: m, sel: p2, from: o, to: l };
}
function lg(n2) {
  let e = n2.pmViewDesc;
  if (e)
    return e.parseRule();
  if (n2.nodeName == "BR" && n2.parentNode) {
    if (Ee && /^(ul|ol)$/i.test(n2.parentNode.nodeName)) {
      let t = document.createElement("div");
      return t.appendChild(document.createElement("li")), { skip: t };
    } else if (n2.parentNode.lastChild == n2 || Ee && /^(tr|table)$/i.test(n2.parentNode.nodeName))
      return { ignore: true };
  } else if (n2.nodeName == "IMG" && n2.getAttribute("mark-placeholder"))
    return { ignore: true };
  return null;
}
const ag = /^(a|abbr|acronym|b|bd[io]|big|br|button|cite|code|data(list)?|del|dfn|em|i|ins|kbd|label|map|mark|meter|output|q|ruby|s|samp|small|span|strong|su[bp]|time|u|tt|var)$/i;
function cg(n2, e, t, r, i) {
  let s = n2.input.compositionPendingChanges || (n2.composing ? n2.input.compositionID : 0);
  if (n2.input.compositionPendingChanges = 0, e < 0) {
    let M = n2.input.lastSelectionTime > Date.now() - 50 ? n2.input.lastSelectionOrigin : null, I = Fl(n2, M);
    if (I && !n2.state.selection.eq(I)) {
      if (Ce && Dt && n2.input.lastKeyCode === 13 && Date.now() - 100 < n2.input.lastKeyCodeTime && n2.someProp("handleKeyDown", (j) => j(n2, pn(13, "Enter"))))
        return;
      let N = n2.state.tr.setSelection(I);
      M == "pointer" ? N.setMeta("pointer", true) : M == "key" && N.scrollIntoView(), s && N.setMeta("composition", s), n2.dispatch(N);
    }
    return;
  }
  let o = n2.state.doc.resolve(e), l = o.sharedDepth(t);
  e = o.before(l + 1), t = n2.state.doc.resolve(t).after(l + 1);
  let a = n2.state.selection, c = og(n2, e, t), u = n2.state.doc, d = u.slice(c.from, c.to), f, h2;
  n2.input.lastKeyCode === 8 && Date.now() - 100 < n2.input.lastKeyCodeTime ? (f = n2.state.selection.to, h2 = "end") : (f = n2.state.selection.from, h2 = "start"), n2.input.lastKeyCode = null;
  let p2 = fg(d.content, c.doc.content, c.from, f, h2);
  if (p2 && n2.input.domChangeCount++, (rr && n2.input.lastIOSEnter > Date.now() - 225 || Dt) && i.some((M) => M.nodeType == 1 && !ag.test(M.nodeName)) && (!p2 || p2.endA >= p2.endB) && n2.someProp("handleKeyDown", (M) => M(n2, pn(13, "Enter")))) {
    n2.input.lastIOSEnter = 0;
    return;
  }
  if (!p2)
    if (r && a instanceof F && !a.empty && a.$head.sameParent(a.$anchor) && !n2.composing && !(c.sel && c.sel.anchor != c.sel.head))
      p2 = { start: a.from, endA: a.to, endB: a.to };
    else {
      if (c.sel) {
        let M = gc(n2, n2.state.doc, c.sel);
        if (M && !M.eq(n2.state.selection)) {
          let I = n2.state.tr.setSelection(M);
          s && I.setMeta("composition", s), n2.dispatch(I);
        }
      }
      return;
    }
  n2.state.selection.from < n2.state.selection.to && p2.start == p2.endB && n2.state.selection instanceof F && (p2.start > n2.state.selection.from && p2.start <= n2.state.selection.from + 2 && n2.state.selection.from >= c.from ? p2.start = n2.state.selection.from : p2.endA < n2.state.selection.to && p2.endA >= n2.state.selection.to - 2 && n2.state.selection.to <= c.to && (p2.endB += n2.state.selection.to - p2.endA, p2.endA = n2.state.selection.to)), Re && en <= 11 && p2.endB == p2.start + 1 && p2.endA == p2.start && p2.start > c.from && c.doc.textBetween(p2.start - c.from - 1, p2.start - c.from + 1) == "  " && (p2.start--, p2.endA--, p2.endB--);
  let m = c.doc.resolveNoCache(p2.start - c.from), g = c.doc.resolveNoCache(p2.endB - c.from), y = u.resolve(p2.start), w = m.sameParent(g) && m.parent.inlineContent && y.end() >= p2.endA, C;
  if ((rr && n2.input.lastIOSEnter > Date.now() - 225 && (!w || i.some((M) => M.nodeName == "DIV" || M.nodeName == "P")) || !w && m.pos < c.doc.content.size && (!m.sameParent(g) || !m.parent.inlineContent) && !/\S/.test(c.doc.textBetween(m.pos, g.pos, "", "")) && (C = $.findFrom(c.doc.resolve(m.pos + 1), 1, true)) && C.head > m.pos) && n2.someProp("handleKeyDown", (M) => M(n2, pn(13, "Enter")))) {
    n2.input.lastIOSEnter = 0;
    return;
  }
  if (n2.state.selection.anchor > p2.start && dg(u, p2.start, p2.endA, m, g) && n2.someProp("handleKeyDown", (M) => M(n2, pn(8, "Backspace")))) {
    Dt && Ce && n2.domObserver.suppressSelectionUpdates();
    return;
  }
  Ce && p2.endB == p2.start && (n2.input.lastChromeDelete = Date.now()), Dt && !w && m.start() != g.start() && g.parentOffset == 0 && m.depth == g.depth && c.sel && c.sel.anchor == c.sel.head && c.sel.head == p2.endA && (p2.endB -= 2, g = c.doc.resolveNoCache(p2.endB - c.from), setTimeout(() => {
    n2.someProp("handleKeyDown", function(M) {
      return M(n2, pn(13, "Enter"));
    });
  }, 20));
  let b = p2.start, S = p2.endA, k = (M) => {
    let I = M || n2.state.tr.replace(b, S, c.doc.slice(p2.start - c.from, p2.endB - c.from));
    if (c.sel) {
      let N = gc(n2, I.doc, c.sel);
      N && !(Ce && n2.composing && N.empty && (p2.start != p2.endB || n2.input.lastChromeDelete < Date.now() - 100) && (N.head == b || N.head == I.mapping.map(S) - 1) || Re && N.empty && N.head == b) && I.setSelection(N);
    }
    return s && I.setMeta("composition", s), I.scrollIntoView();
  }, T;
  if (w) {
    if (m.pos == g.pos) {
      Re && en <= 11 && m.parentOffset == 0 && (n2.domObserver.suppressSelectionUpdates(), setTimeout(() => It(n2), 20));
      let M = k(n2.state.tr.delete(b, S)), I = u.resolve(p2.start).marksAcross(u.resolve(p2.endA));
      I && M.ensureMarks(I), n2.dispatch(M);
    } else if (
      // Adding or removing a mark
      p2.endA == p2.endB && (T = ug(m.parent.content.cut(m.parentOffset, g.parentOffset), y.parent.content.cut(y.parentOffset, p2.endA - y.start())))
    ) {
      let M = k(n2.state.tr);
      T.type == "add" ? M.addMark(b, S, T.mark) : M.removeMark(b, S, T.mark), n2.dispatch(M);
    } else if (m.parent.child(m.index()).isText && m.index() == g.index() - (g.textOffset ? 0 : 1)) {
      let M = m.parent.textBetween(m.parentOffset, g.parentOffset), I = () => k(n2.state.tr.insertText(M, b, S));
      n2.someProp("handleTextInput", (N) => N(n2, b, S, M, I)) || n2.dispatch(I());
    }
  } else
    n2.dispatch(k());
}
function gc(n2, e, t) {
  return Math.max(t.anchor, t.head) > e.content.size ? null : zl(n2, e.resolve(t.anchor), e.resolve(t.head));
}
function ug(n2, e) {
  let t = n2.firstChild.marks, r = e.firstChild.marks, i = t, s = r, o, l, a;
  for (let u = 0; u < r.length; u++)
    i = r[u].removeFromSet(i);
  for (let u = 0; u < t.length; u++)
    s = t[u].removeFromSet(s);
  if (i.length == 1 && s.length == 0)
    l = i[0], o = "add", a = (u) => u.mark(l.addToSet(u.marks));
  else if (i.length == 0 && s.length == 1)
    l = s[0], o = "remove", a = (u) => u.mark(l.removeFromSet(u.marks));
  else
    return null;
  let c = [];
  for (let u = 0; u < e.childCount; u++)
    c.push(a(e.child(u)));
  if (A.from(c).eq(n2))
    return { mark: l, type: o };
}
function dg(n2, e, t, r, i) {
  if (
    // The content must have shrunk
    t - e <= i.pos - r.pos || // newEnd must point directly at or after the end of the block that newStart points into
    wo(r, true, false) < i.pos
  )
    return false;
  let s = n2.resolve(e);
  if (!r.parent.isTextblock) {
    let l = s.nodeAfter;
    return l != null && t == e + l.nodeSize;
  }
  if (s.parentOffset < s.parent.content.size || !s.parent.isTextblock)
    return false;
  let o = n2.resolve(wo(s, true, true));
  return !o.parent.isTextblock || o.pos > t || wo(o, true, false) < t ? false : r.parent.content.cut(r.parentOffset).eq(o.parent.content);
}
function wo(n2, e, t) {
  let r = n2.depth, i = e ? n2.end() : n2.pos;
  for (; r > 0 && (e || n2.indexAfter(r) == n2.node(r).childCount); )
    r--, i++, e = false;
  if (t) {
    let s = n2.node(r).maybeChild(n2.indexAfter(r));
    for (; s && !s.isLeaf; )
      s = s.firstChild, i++;
  }
  return i;
}
function fg(n2, e, t, r, i) {
  let s = n2.findDiffStart(e, t);
  if (s == null)
    return null;
  let { a: o, b: l } = n2.findDiffEnd(e, t + n2.size, t + e.size);
  if (i == "end") {
    let a = Math.max(0, s - Math.min(o, l));
    r -= o + a - s;
  }
  if (o < s && n2.size < e.size) {
    let a = r <= s && r >= o ? s - r : 0;
    s -= a, s && s < e.size && yc(e.textBetween(s - 1, s + 1)) && (s += a ? 1 : -1), l = s + (l - o), o = s;
  } else if (l < s) {
    let a = r <= s && r >= l ? s - r : 0;
    s -= a, s && s < n2.size && yc(n2.textBetween(s - 1, s + 1)) && (s += a ? 1 : -1), o = s + (o - l), l = s;
  }
  return { start: s, endA: o, endB: l };
}
function yc(n2) {
  if (n2.length != 2)
    return false;
  let e = n2.charCodeAt(0), t = n2.charCodeAt(1);
  return e >= 56320 && e <= 57343 && t >= 55296 && t <= 56319;
}
class Jd {
  /**
  Create a view. `place` may be a DOM node that the editor should
  be appended to, a function that will place it into the document,
  or an object whose `mount` property holds the node to use as the
  document container. If it is `null`, the editor will not be
  added to the document.
  */
  constructor(e, t) {
    this._root = null, this.focused = false, this.trackWrites = null, this.mounted = false, this.markCursor = null, this.cursorWrapper = null, this.lastSelectedViewDesc = void 0, this.input = new Dm(), this.prevDirectPlugins = [], this.pluginViews = [], this.requiresGeckoHackNode = false, this.dragging = null, this._props = t, this.state = t.state, this.directPlugins = t.plugins || [], this.directPlugins.forEach(Cc), this.dispatch = this.dispatch.bind(this), this.dom = e && e.mount || document.createElement("div"), e && (e.appendChild ? e.appendChild(this.dom) : typeof e == "function" ? e(this.dom) : e.mount && (this.mounted = true)), this.editable = wc(this), vc(this), this.nodeViews = kc(this), this.docView = Qa(this.state.doc, bc(this), vo(this), this.dom, this), this.domObserver = new ng(this, (r, i, s, o) => cg(this, r, i, s, o)), this.domObserver.start(), Lm(this), this.updatePluginViews();
  }
  /**
  Holds `true` when a
  [composition](https://w3c.github.io/uievents/#events-compositionevents)
  is active.
  */
  get composing() {
    return this.input.composing;
  }
  /**
  The view's current [props](https://prosemirror.net/docs/ref/#view.EditorProps).
  */
  get props() {
    if (this._props.state != this.state) {
      let e = this._props;
      this._props = {};
      for (let t in e)
        this._props[t] = e[t];
      this._props.state = this.state;
    }
    return this._props;
  }
  /**
  Update the view's props. Will immediately cause an update to
  the DOM.
  */
  update(e) {
    e.handleDOMEvents != this._props.handleDOMEvents && tl(this);
    let t = this._props;
    this._props = e, e.plugins && (e.plugins.forEach(Cc), this.directPlugins = e.plugins), this.updateStateInner(e.state, t);
  }
  /**
  Update the view by updating existing props object with the object
  given as argument. Equivalent to `view.update(Object.assign({},
  view.props, props))`.
  */
  setProps(e) {
    let t = {};
    for (let r in this._props)
      t[r] = this._props[r];
    t.state = this.state;
    for (let r in e)
      t[r] = e[r];
    this.update(t);
  }
  /**
  Update the editor's `state` prop, without touching any of the
  other props.
  */
  updateState(e) {
    this.updateStateInner(e, this._props);
  }
  updateStateInner(e, t) {
    var r;
    let i = this.state, s = false, o = false;
    e.storedMarks && this.composing && ($d(this), o = true), this.state = e;
    let l = i.plugins != e.plugins || this._props.plugins != t.plugins;
    if (l || this._props.plugins != t.plugins || this._props.nodeViews != t.nodeViews) {
      let h2 = kc(this);
      pg(h2, this.nodeViews) && (this.nodeViews = h2, s = true);
    }
    (l || t.handleDOMEvents != this._props.handleDOMEvents) && tl(this), this.editable = wc(this), vc(this);
    let a = vo(this), c = bc(this), u = i.plugins != e.plugins && !i.doc.eq(e.doc) ? "reset" : e.scrollToSelection > i.scrollToSelection ? "to selection" : "preserve", d = s || !this.docView.matchesNode(e.doc, c, a);
    (d || !e.selection.eq(i.selection)) && (o = true);
    let f = u == "preserve" && o && this.dom.style.overflowAnchor == null && Kp(this);
    if (o) {
      this.domObserver.stop();
      let h2 = d && (Re || Ce) && !this.composing && !i.selection.empty && !e.selection.empty && hg(i.selection, e.selection);
      if (d) {
        let p2 = Ce ? this.trackWrites = this.domSelectionRange().focusNode : null;
        this.composing && (this.input.compositionNode = Um(this)), (s || !this.docView.update(e.doc, c, a, this)) && (this.docView.updateOuterDeco(c), this.docView.destroy(), this.docView = Qa(e.doc, c, a, this.dom, this)), p2 && !this.trackWrites && (h2 = true);
      }
      h2 || !(this.input.mouseDown && this.domObserver.currentSelection.eq(this.domSelectionRange()) && gm(this)) ? It(this, h2) : (Od(this, e.selection), this.domObserver.setCurSelection()), this.domObserver.start();
    }
    this.updatePluginViews(i), !((r = this.dragging) === null || r === void 0) && r.node && !i.doc.eq(e.doc) && this.updateDraggedNode(this.dragging, i), u == "reset" ? this.dom.scrollTop = 0 : u == "to selection" ? this.scrollToSelection() : f && qp(f);
  }
  /**
  @internal
  */
  scrollToSelection() {
    let e = this.domSelectionRange().focusNode;
    if (!(!e || !this.dom.contains(e.nodeType == 1 ? e : e.parentNode))) {
      if (!this.someProp("handleScrollToSelection", (t) => t(this))) if (this.state.selection instanceof B) {
        let t = this.docView.domAfterPos(this.state.selection.from);
        t.nodeType == 1 && Ka(this, t.getBoundingClientRect(), e);
      } else
        Ka(this, this.coordsAtPos(this.state.selection.head, 1), e);
    }
  }
  destroyPluginViews() {
    let e;
    for (; e = this.pluginViews.pop(); )
      e.destroy && e.destroy();
  }
  updatePluginViews(e) {
    if (!e || e.plugins != this.state.plugins || this.directPlugins != this.prevDirectPlugins) {
      this.prevDirectPlugins = this.directPlugins, this.destroyPluginViews();
      for (let t = 0; t < this.directPlugins.length; t++) {
        let r = this.directPlugins[t];
        r.spec.view && this.pluginViews.push(r.spec.view(this));
      }
      for (let t = 0; t < this.state.plugins.length; t++) {
        let r = this.state.plugins[t];
        r.spec.view && this.pluginViews.push(r.spec.view(this));
      }
    } else
      for (let t = 0; t < this.pluginViews.length; t++) {
        let r = this.pluginViews[t];
        r.update && r.update(this, e);
      }
  }
  updateDraggedNode(e, t) {
    let r = e.node, i = -1;
    if (this.state.doc.nodeAt(r.from) == r.node)
      i = r.from;
    else {
      let s = r.from + (this.state.doc.content.size - t.doc.content.size);
      (s > 0 && this.state.doc.nodeAt(s)) == r.node && (i = s);
    }
    this.dragging = new jd(e.slice, e.move, i < 0 ? void 0 : B.create(this.state.doc, i));
  }
  someProp(e, t) {
    let r = this._props && this._props[e], i;
    if (r != null && (i = t ? t(r) : r))
      return i;
    for (let o = 0; o < this.directPlugins.length; o++) {
      let l = this.directPlugins[o].props[e];
      if (l != null && (i = t ? t(l) : l))
        return i;
    }
    let s = this.state.plugins;
    if (s)
      for (let o = 0; o < s.length; o++) {
        let l = s[o].props[e];
        if (l != null && (i = t ? t(l) : l))
          return i;
      }
  }
  /**
  Query whether the view has focus.
  */
  hasFocus() {
    if (Re) {
      let e = this.root.activeElement;
      if (e == this.dom)
        return true;
      if (!e || !this.dom.contains(e))
        return false;
      for (; e && this.dom != e && this.dom.contains(e); ) {
        if (e.contentEditable == "false")
          return false;
        e = e.parentElement;
      }
      return true;
    }
    return this.root.activeElement == this.dom;
  }
  /**
  Focus the editor.
  */
  focus() {
    this.domObserver.stop(), this.editable && Jp(this.dom), It(this), this.domObserver.start();
  }
  /**
  Get the document root in which the editor exists. This will
  usually be the top-level `document`, but might be a [shadow
  DOM](https://developer.mozilla.org/en-US/docs/Web/Web_Components/Shadow_DOM)
  root if the editor is inside one.
  */
  get root() {
    let e = this._root;
    if (e == null) {
      for (let t = this.dom.parentNode; t; t = t.parentNode)
        if (t.nodeType == 9 || t.nodeType == 11 && t.host)
          return t.getSelection || (Object.getPrototypeOf(t).getSelection = () => t.ownerDocument.getSelection()), this._root = t;
    }
    return e || document;
  }
  /**
  When an existing editor view is moved to a new document or
  shadow tree, call this to make it recompute its root.
  */
  updateRoot() {
    this._root = null;
  }
  /**
  Given a pair of viewport coordinates, return the document
  position that corresponds to them. May return null if the given
  coordinates aren't inside of the editor. When an object is
  returned, its `pos` property is the position nearest to the
  coordinates, and its `inside` property holds the position of the
  inner node that the position falls inside of, or -1 if it is at
  the top level, not in any node.
  */
  posAtCoords(e) {
    return Zp(this, e);
  }
  /**
  Returns the viewport rectangle at a given document position.
  `left` and `right` will be the same number, as this returns a
  flat cursor-ish rectangle. If the position is between two things
  that aren't directly adjacent, `side` determines which element
  is used. When < 0, the element before the position is used,
  otherwise the element after.
  */
  coordsAtPos(e, t = 1) {
    return kd(this, e, t);
  }
  /**
  Find the DOM position that corresponds to the given document
  position. When `side` is negative, find the position as close as
  possible to the content before the position. When positive,
  prefer positions close to the content after the position. When
  zero, prefer as shallow a position as possible.
  
  Note that you should **not** mutate the editor's internal DOM,
  only inspect it (and even that is usually not necessary).
  */
  domAtPos(e, t = 0) {
    return this.docView.domFromPos(e, t);
  }
  /**
  Find the DOM node that represents the document node after the
  given position. May return `null` when the position doesn't point
  in front of a node or if the node is inside an opaque node view.
  
  This is intended to be able to call things like
  `getBoundingClientRect` on that DOM node. Do **not** mutate the
  editor DOM directly, or add styling this way, since that will be
  immediately overriden by the editor as it redraws the node.
  */
  nodeDOM(e) {
    let t = this.docView.descAt(e);
    return t ? t.nodeDOM : null;
  }
  /**
  Find the document position that corresponds to a given DOM
  position. (Whenever possible, it is preferable to inspect the
  document structure directly, rather than poking around in the
  DOM, but sometimes—for example when interpreting an event
  target—you don't have a choice.)
  
  The `bias` parameter can be used to influence which side of a DOM
  node to use when the position is inside a leaf node.
  */
  posAtDOM(e, t, r = -1) {
    let i = this.docView.posFromDOM(e, t, r);
    if (i == null)
      throw new RangeError("DOM position not inside the editor");
    return i;
  }
  /**
  Find out whether the selection is at the end of a textblock when
  moving in a given direction. When, for example, given `"left"`,
  it will return true if moving left from the current cursor
  position would leave that position's parent textblock. Will apply
  to the view's current state by default, but it is possible to
  pass a different state.
  */
  endOfTextblock(e, t) {
    return im(this, t || this.state, e);
  }
  /**
  Run the editor's paste logic with the given HTML string. The
  `event`, if given, will be passed to the
  [`handlePaste`](https://prosemirror.net/docs/ref/#view.EditorProps.handlePaste) hook.
  */
  pasteHTML(e, t) {
    return Kr(this, "", e, false, t || new ClipboardEvent("paste"));
  }
  /**
  Run the editor's paste logic with the given plain-text input.
  */
  pasteText(e, t) {
    return Kr(this, e, null, true, t || new ClipboardEvent("paste"));
  }
  /**
  Serialize the given slice as it would be if it was copied from
  this editor. Returns a DOM element that contains a
  representation of the slice as its children, a textual
  representation, and the transformed slice (which can be
  different from the given input due to hooks like
  [`transformCopied`](https://prosemirror.net/docs/ref/#view.EditorProps.transformCopied)).
  */
  serializeForClipboard(e) {
    return Vl(this, e);
  }
  /**
  Removes the editor from the DOM and destroys all [node
  views](https://prosemirror.net/docs/ref/#view.NodeView).
  */
  destroy() {
    this.docView && (Rm(this), this.destroyPluginViews(), this.mounted ? (this.docView.update(this.state.doc, [], vo(this), this), this.dom.textContent = "") : this.dom.parentNode && this.dom.parentNode.removeChild(this.dom), this.docView.destroy(), this.docView = null, Pp());
  }
  /**
  This is true when the view has been
  [destroyed](https://prosemirror.net/docs/ref/#view.EditorView.destroy) (and thus should not be
  used anymore).
  */
  get isDestroyed() {
    return this.docView == null;
  }
  /**
  Used for testing.
  */
  dispatchEvent(e) {
    return Pm(this, e);
  }
  /**
  @internal
  */
  domSelectionRange() {
    let e = this.domSelection();
    return e ? Ee && this.root.nodeType === 11 && Vp(this.dom.ownerDocument) == this.dom && ig(this, e) || e : { focusNode: null, focusOffset: 0, anchorNode: null, anchorOffset: 0 };
  }
  /**
  @internal
  */
  domSelection() {
    return this.root.getSelection();
  }
}
Jd.prototype.dispatch = function(n2) {
  let e = this._props.dispatchTransaction;
  e ? e.call(this, n2) : this.updateState(this.state.apply(n2));
};
function bc(n2) {
  let e = /* @__PURE__ */ Object.create(null);
  return e.class = "ProseMirror", e.contenteditable = String(n2.editable), n2.someProp("attributes", (t) => {
    if (typeof t == "function" && (t = t(n2.state)), t)
      for (let r in t)
        r == "class" ? e.class += " " + t[r] : r == "style" ? e.style = (e.style ? e.style + ";" : "") + t[r] : !e[r] && r != "contenteditable" && r != "nodeName" && (e[r] = String(t[r]));
  }), e.translate || (e.translate = "no"), [xe.node(0, n2.state.doc.content.size, e)];
}
function vc(n2) {
  if (n2.markCursor) {
    let e = document.createElement("img");
    e.className = "ProseMirror-separator", e.setAttribute("mark-placeholder", "true"), e.setAttribute("alt", ""), n2.cursorWrapper = { dom: e, deco: xe.widget(n2.state.selection.from, e, { raw: true, marks: n2.markCursor }) };
  } else
    n2.cursorWrapper = null;
}
function wc(n2) {
  return !n2.someProp("editable", (e) => e(n2.state) === false);
}
function hg(n2, e) {
  let t = Math.min(n2.$anchor.sharedDepth(n2.head), e.$anchor.sharedDepth(e.head));
  return n2.$anchor.start(t) != e.$anchor.start(t);
}
function kc(n2) {
  let e = /* @__PURE__ */ Object.create(null);
  function t(r) {
    for (let i in r)
      Object.prototype.hasOwnProperty.call(e, i) || (e[i] = r[i]);
  }
  return n2.someProp("nodeViews", t), n2.someProp("markViews", t), e;
}
function pg(n2, e) {
  let t = 0, r = 0;
  for (let i in n2) {
    if (n2[i] != e[i])
      return true;
    t++;
  }
  for (let i in e)
    r++;
  return t != r;
}
function Cc(n2) {
  if (n2.spec.state || n2.spec.filterTransaction || n2.spec.appendTransaction)
    throw new RangeError("Plugins passed directly to the view must not have a state component");
}
var rn = {
  8: "Backspace",
  9: "Tab",
  10: "Enter",
  12: "NumLock",
  13: "Enter",
  16: "Shift",
  17: "Control",
  18: "Alt",
  20: "CapsLock",
  27: "Escape",
  32: " ",
  33: "PageUp",
  34: "PageDown",
  35: "End",
  36: "Home",
  37: "ArrowLeft",
  38: "ArrowUp",
  39: "ArrowRight",
  40: "ArrowDown",
  44: "PrintScreen",
  45: "Insert",
  46: "Delete",
  59: ";",
  61: "=",
  91: "Meta",
  92: "Meta",
  106: "*",
  107: "+",
  108: ",",
  109: "-",
  110: ".",
  111: "/",
  144: "NumLock",
  145: "ScrollLock",
  160: "Shift",
  161: "Shift",
  162: "Control",
  163: "Control",
  164: "Alt",
  165: "Alt",
  173: "-",
  186: ";",
  187: "=",
  188: ",",
  189: "-",
  190: ".",
  191: "/",
  192: "`",
  219: "[",
  220: "\\",
  221: "]",
  222: "'"
}, rs = {
  48: ")",
  49: "!",
  50: "@",
  51: "#",
  52: "$",
  53: "%",
  54: "^",
  55: "&",
  56: "*",
  57: "(",
  59: ":",
  61: "+",
  173: "_",
  186: ":",
  187: "+",
  188: "<",
  189: "_",
  190: ">",
  191: "?",
  192: "~",
  219: "{",
  220: "|",
  221: "}",
  222: '"'
}, mg = typeof navigator < "u" && /Mac/.test(navigator.platform), gg = typeof navigator < "u" && /MSIE \d|Trident\/(?:[7-9]|\d{2,})\..*rv:(\d+)/.exec(navigator.userAgent);
for (var we = 0; we < 10; we++) rn[48 + we] = rn[96 + we] = String(we);
for (var we = 1; we <= 24; we++) rn[we + 111] = "F" + we;
for (var we = 65; we <= 90; we++)
  rn[we] = String.fromCharCode(we + 32), rs[we] = String.fromCharCode(we);
for (var ko in rn) rs.hasOwnProperty(ko) || (rs[ko] = rn[ko]);
function yg(n2) {
  var e = mg && n2.metaKey && n2.shiftKey && !n2.ctrlKey && !n2.altKey || gg && n2.shiftKey && n2.key && n2.key.length == 1 || n2.key == "Unidentified", t = !e && n2.key || (n2.shiftKey ? rs : rn)[n2.keyCode] || n2.key || "Unidentified";
  return t == "Esc" && (t = "Escape"), t == "Del" && (t = "Delete"), t == "Left" && (t = "ArrowLeft"), t == "Up" && (t = "ArrowUp"), t == "Right" && (t = "ArrowRight"), t == "Down" && (t = "ArrowDown"), t;
}
const bg = typeof navigator < "u" && /Mac|iP(hone|[oa]d)/.test(navigator.platform), vg = typeof navigator < "u" && /Win/.test(navigator.platform);
function wg(n2) {
  let e = n2.split(/-(?!$)/), t = e[e.length - 1];
  t == "Space" && (t = " ");
  let r, i, s, o;
  for (let l = 0; l < e.length - 1; l++) {
    let a = e[l];
    if (/^(cmd|meta|m)$/i.test(a))
      o = true;
    else if (/^a(lt)?$/i.test(a))
      r = true;
    else if (/^(c|ctrl|control)$/i.test(a))
      i = true;
    else if (/^s(hift)?$/i.test(a))
      s = true;
    else if (/^mod$/i.test(a))
      bg ? o = true : i = true;
    else
      throw new Error("Unrecognized modifier name: " + a);
  }
  return r && (t = "Alt-" + t), i && (t = "Ctrl-" + t), o && (t = "Meta-" + t), s && (t = "Shift-" + t), t;
}
function kg(n2) {
  let e = /* @__PURE__ */ Object.create(null);
  for (let t in n2)
    e[wg(t)] = n2[t];
  return e;
}
function Co(n2, e, t = true) {
  return e.altKey && (n2 = "Alt-" + n2), e.ctrlKey && (n2 = "Ctrl-" + n2), e.metaKey && (n2 = "Meta-" + n2), t && e.shiftKey && (n2 = "Shift-" + n2), n2;
}
function Cg(n2) {
  return new le({ props: { handleKeyDown: Gd(n2) } });
}
function Gd(n2) {
  let e = kg(n2);
  return function(t, r) {
    let i = yg(r), s, o = e[Co(i, r)];
    if (o && o(t.state, t.dispatch, t))
      return true;
    if (i.length == 1 && i != " ") {
      if (r.shiftKey) {
        let l = e[Co(i, r, false)];
        if (l && l(t.state, t.dispatch, t))
          return true;
      }
      if ((r.altKey || r.metaKey || r.ctrlKey) && // Ctrl-Alt may be used for AltGr on Windows
      !(vg && r.ctrlKey && r.altKey) && (s = rn[r.keyCode]) && s != i) {
        let l = e[Co(s, r)];
        if (l && l(t.state, t.dispatch, t))
          return true;
      }
    }
    return false;
  };
}
const Kl = (n2, e) => n2.selection.empty ? false : (e && e(n2.tr.deleteSelection().scrollIntoView()), true);
function Yd(n2, e) {
  let { $cursor: t } = n2.selection;
  return !t || (e ? !e.endOfTextblock("backward", n2) : t.parentOffset > 0) ? null : t;
}
const Xd = (n2, e, t) => {
  let r = Yd(n2, t);
  if (!r)
    return false;
  let i = ql(r);
  if (!i) {
    let o = r.blockRange(), l = o && hr(o);
    return l == null ? false : (e && e(n2.tr.lift(o, l).scrollIntoView()), true);
  }
  let s = i.nodeBefore;
  if (lf(n2, i, e, -1))
    return true;
  if (r.parent.content.size == 0 && (ir(s, "end") || B.isSelectable(s)))
    for (let o = r.depth; ; o--) {
      let l = _s(n2.doc, r.before(o), r.after(o), O.empty);
      if (l && l.slice.size < l.to - l.from) {
        if (e) {
          let a = n2.tr.step(l);
          a.setSelection(ir(s, "end") ? $.findFrom(a.doc.resolve(a.mapping.map(i.pos, -1)), -1) : B.create(a.doc, i.pos - s.nodeSize)), e(a.scrollIntoView());
        }
        return true;
      }
      if (o == 1 || r.node(o - 1).childCount > 1)
        break;
    }
  return s.isAtom && i.depth == r.depth - 1 ? (e && e(n2.tr.delete(i.pos - s.nodeSize, i.pos).scrollIntoView()), true) : false;
}, xg = (n2, e, t) => {
  let r = Yd(n2, t);
  if (!r)
    return false;
  let i = ql(r);
  return i ? Qd(n2, i, e) : false;
}, Sg = (n2, e, t) => {
  let r = ef(n2, t);
  if (!r)
    return false;
  let i = Jl(r);
  return i ? Qd(n2, i, e) : false;
};
function Qd(n2, e, t) {
  let r = e.nodeBefore, i = r, s = e.pos - 1;
  for (; !i.isTextblock; s--) {
    if (i.type.spec.isolating)
      return false;
    let u = i.lastChild;
    if (!u)
      return false;
    i = u;
  }
  let o = e.nodeAfter, l = o, a = e.pos + 1;
  for (; !l.isTextblock; a++) {
    if (l.type.spec.isolating)
      return false;
    let u = l.firstChild;
    if (!u)
      return false;
    l = u;
  }
  let c = _s(n2.doc, s, a, O.empty);
  if (!c || c.from != s || c instanceof he && c.slice.size >= a - s)
    return false;
  if (t) {
    let u = n2.tr.step(c);
    u.setSelection(F.create(u.doc, s)), t(u.scrollIntoView());
  }
  return true;
}
function ir(n2, e, t = false) {
  for (let r = n2; r; r = e == "start" ? r.firstChild : r.lastChild) {
    if (r.isTextblock)
      return true;
    if (t && r.childCount != 1)
      return false;
  }
  return false;
}
const Zd = (n2, e, t) => {
  let { $head: r, empty: i } = n2.selection, s = r;
  if (!i)
    return false;
  if (r.parent.isTextblock) {
    if (t ? !t.endOfTextblock("backward", n2) : r.parentOffset > 0)
      return false;
    s = ql(r);
  }
  let o = s && s.nodeBefore;
  return !o || !B.isSelectable(o) ? false : (e && e(n2.tr.setSelection(B.create(n2.doc, s.pos - o.nodeSize)).scrollIntoView()), true);
};
function ql(n2) {
  if (!n2.parent.type.spec.isolating)
    for (let e = n2.depth - 1; e >= 0; e--) {
      if (n2.index(e) > 0)
        return n2.doc.resolve(n2.before(e + 1));
      if (n2.node(e).type.spec.isolating)
        break;
    }
  return null;
}
function ef(n2, e) {
  let { $cursor: t } = n2.selection;
  return !t || (e ? !e.endOfTextblock("forward", n2) : t.parentOffset < t.parent.content.size) ? null : t;
}
const tf = (n2, e, t) => {
  let r = ef(n2, t);
  if (!r)
    return false;
  let i = Jl(r);
  if (!i)
    return false;
  let s = i.nodeAfter;
  if (lf(n2, i, e, 1))
    return true;
  if (r.parent.content.size == 0 && (ir(s, "start") || B.isSelectable(s))) {
    let o = _s(n2.doc, r.before(), r.after(), O.empty);
    if (o && o.slice.size < o.to - o.from) {
      if (e) {
        let l = n2.tr.step(o);
        l.setSelection(ir(s, "start") ? $.findFrom(l.doc.resolve(l.mapping.map(i.pos)), 1) : B.create(l.doc, l.mapping.map(i.pos))), e(l.scrollIntoView());
      }
      return true;
    }
  }
  return s.isAtom && i.depth == r.depth - 1 ? (e && e(n2.tr.delete(i.pos, i.pos + s.nodeSize).scrollIntoView()), true) : false;
}, nf = (n2, e, t) => {
  let { $head: r, empty: i } = n2.selection, s = r;
  if (!i)
    return false;
  if (r.parent.isTextblock) {
    if (t ? !t.endOfTextblock("forward", n2) : r.parentOffset < r.parent.content.size)
      return false;
    s = Jl(r);
  }
  let o = s && s.nodeAfter;
  return !o || !B.isSelectable(o) ? false : (e && e(n2.tr.setSelection(B.create(n2.doc, s.pos)).scrollIntoView()), true);
};
function Jl(n2) {
  if (!n2.parent.type.spec.isolating)
    for (let e = n2.depth - 1; e >= 0; e--) {
      let t = n2.node(e);
      if (n2.index(e) + 1 < t.childCount)
        return n2.doc.resolve(n2.after(e + 1));
      if (t.type.spec.isolating)
        break;
    }
  return null;
}
const Mg = (n2, e) => {
  let t = n2.selection, r = t instanceof B, i;
  if (r) {
    if (t.node.isTextblock || !sn(n2.doc, t.from))
      return false;
    i = t.from;
  } else if (i = $s(n2.doc, t.from, -1), i == null)
    return false;
  if (e) {
    let s = n2.tr.join(i);
    r && s.setSelection(B.create(s.doc, i - n2.doc.resolve(i).nodeBefore.nodeSize)), e(s.scrollIntoView());
  }
  return true;
}, Ag = (n2, e) => {
  let t = n2.selection, r;
  if (t instanceof B) {
    if (t.node.isTextblock || !sn(n2.doc, t.to))
      return false;
    r = t.to;
  } else if (r = $s(n2.doc, t.to, 1), r == null)
    return false;
  return e && e(n2.tr.join(r).scrollIntoView()), true;
}, Eg = (n2, e) => {
  let { $from: t, $to: r } = n2.selection, i = t.blockRange(r), s = i && hr(i);
  return s == null ? false : (e && e(n2.tr.lift(i, s).scrollIntoView()), true);
}, rf = (n2, e) => {
  let { $head: t, $anchor: r } = n2.selection;
  return !t.parent.type.spec.code || !t.sameParent(r) ? false : (e && e(n2.tr.insertText(`
`).scrollIntoView()), true);
};
function Gl(n2) {
  for (let e = 0; e < n2.edgeCount; e++) {
    let { type: t } = n2.edge(e);
    if (t.isTextblock && !t.hasRequiredAttrs())
      return t;
  }
  return null;
}
const Tg = (n2, e) => {
  let { $head: t, $anchor: r } = n2.selection;
  if (!t.parent.type.spec.code || !t.sameParent(r))
    return false;
  let i = t.node(-1), s = t.indexAfter(-1), o = Gl(i.contentMatchAt(s));
  if (!o || !i.canReplaceWith(s, s, o))
    return false;
  if (e) {
    let l = t.after(), a = n2.tr.replaceWith(l, l, o.createAndFill());
    a.setSelection($.near(a.doc.resolve(l), 1)), e(a.scrollIntoView());
  }
  return true;
}, sf = (n2, e) => {
  let t = n2.selection, { $from: r, $to: i } = t;
  if (t instanceof je || r.parent.inlineContent || i.parent.inlineContent)
    return false;
  let s = Gl(i.parent.contentMatchAt(i.indexAfter()));
  if (!s || !s.isTextblock)
    return false;
  if (e) {
    let o = (!r.parentOffset && i.index() < i.parent.childCount ? r : i).pos, l = n2.tr.insert(o, s.createAndFill());
    l.setSelection(F.create(l.doc, o + 1)), e(l.scrollIntoView());
  }
  return true;
}, of = (n2, e) => {
  let { $cursor: t } = n2.selection;
  if (!t || t.parent.content.size)
    return false;
  if (t.depth > 1 && t.after() != t.end(-1)) {
    let s = t.before();
    if (Rt(n2.doc, s))
      return e && e(n2.tr.split(s).scrollIntoView()), true;
  }
  let r = t.blockRange(), i = r && hr(r);
  return i == null ? false : (e && e(n2.tr.lift(r, i).scrollIntoView()), true);
};
function Og(n2) {
  return (e, t) => {
    let { $from: r, $to: i } = e.selection;
    if (e.selection instanceof B && e.selection.node.isBlock)
      return !r.parentOffset || !Rt(e.doc, r.pos) ? false : (t && t(e.tr.split(r.pos).scrollIntoView()), true);
    if (!r.depth)
      return false;
    let s = [], o, l, a = false, c = false;
    for (let h2 = r.depth; ; h2--)
      if (r.node(h2).isBlock) {
        a = r.end(h2) == r.pos + (r.depth - h2), c = r.start(h2) == r.pos - (r.depth - h2), l = Gl(r.node(h2 - 1).contentMatchAt(r.indexAfter(h2 - 1))), s.unshift(a && l ? { type: l } : null), o = h2;
        break;
      } else {
        if (h2 == 1)
          return false;
        s.unshift(null);
      }
    let u = e.tr;
    (e.selection instanceof F || e.selection instanceof je) && u.deleteSelection();
    let d = u.mapping.map(r.pos), f = Rt(u.doc, d, s.length, s);
    if (f || (s[0] = l ? { type: l } : null, f = Rt(u.doc, d, s.length, s)), !f)
      return false;
    if (u.split(d, s.length, s), !a && c && r.node(o).type != l) {
      let h2 = u.mapping.map(r.before(o)), p2 = u.doc.resolve(h2);
      l && r.node(o - 1).canReplaceWith(p2.index(), p2.index() + 1, l) && u.setNodeMarkup(u.mapping.map(r.before(o)), l);
    }
    return t && t(u.scrollIntoView()), true;
  };
}
const Ng = Og(), Dg = (n2, e) => {
  let { $from: t, to: r } = n2.selection, i, s = t.sharedDepth(r);
  return s == 0 ? false : (i = t.before(s), e && e(n2.tr.setSelection(B.create(n2.doc, i))), true);
};
function Lg(n2, e, t) {
  let r = e.nodeBefore, i = e.nodeAfter, s = e.index();
  return !r || !i || !r.type.compatibleContent(i.type) ? false : !r.content.size && e.parent.canReplace(s - 1, s) ? (t && t(n2.tr.delete(e.pos - r.nodeSize, e.pos).scrollIntoView()), true) : !e.parent.canReplace(s, s + 1) || !(i.isTextblock || sn(n2.doc, e.pos)) ? false : (t && t(n2.tr.join(e.pos).scrollIntoView()), true);
}
function lf(n2, e, t, r) {
  let i = e.nodeBefore, s = e.nodeAfter, o, l, a = i.type.spec.isolating || s.type.spec.isolating;
  if (!a && Lg(n2, e, t))
    return true;
  let c = !a && e.parent.canReplace(e.index(), e.index() + 1);
  if (c && (o = (l = i.contentMatchAt(i.childCount)).findWrapping(s.type)) && l.matchType(o[0] || s.type).validEnd) {
    if (t) {
      let h2 = e.pos + s.nodeSize, p2 = A.empty;
      for (let y = o.length - 1; y >= 0; y--)
        p2 = A.from(o[y].create(null, p2));
      p2 = A.from(i.copy(p2));
      let m = n2.tr.step(new pe(e.pos - 1, h2, e.pos, h2, new O(p2, 1, 0), o.length, true)), g = m.doc.resolve(h2 + 2 * o.length);
      g.nodeAfter && g.nodeAfter.type == i.type && sn(m.doc, g.pos) && m.join(g.pos), t(m.scrollIntoView());
    }
    return true;
  }
  let u = s.type.spec.isolating || r > 0 && a ? null : $.findFrom(e, 1), d = u && u.$from.blockRange(u.$to), f = d && hr(d);
  if (f != null && f >= e.depth)
    return t && t(n2.tr.lift(d, f).scrollIntoView()), true;
  if (c && ir(s, "start", true) && ir(i, "end")) {
    let h2 = i, p2 = [];
    for (; p2.push(h2), !h2.isTextblock; )
      h2 = h2.lastChild;
    let m = s, g = 1;
    for (; !m.isTextblock; m = m.firstChild)
      g++;
    if (h2.canReplace(h2.childCount, h2.childCount, m.content)) {
      if (t) {
        let y = A.empty;
        for (let C = p2.length - 1; C >= 0; C--)
          y = A.from(p2[C].copy(y));
        let w = n2.tr.step(new pe(e.pos - p2.length, e.pos + s.nodeSize, e.pos + g, e.pos + s.nodeSize - g, new O(y, p2.length, 0), 0, true));
        t(w.scrollIntoView());
      }
      return true;
    }
  }
  return false;
}
function af(n2) {
  return function(e, t) {
    let r = e.selection, i = n2 < 0 ? r.$from : r.$to, s = i.depth;
    for (; i.node(s).isInline; ) {
      if (!s)
        return false;
      s--;
    }
    return i.node(s).isTextblock ? (t && t(e.tr.setSelection(F.create(e.doc, n2 < 0 ? i.start(s) : i.end(s)))), true) : false;
  };
}
const Rg = af(-1), Ig = af(1);
function Pg(n2, e = null) {
  return function(t, r) {
    let { $from: i, $to: s } = t.selection, o = i.blockRange(s), l = o && Il(o, n2, e);
    return l ? (r && r(t.tr.wrap(o, l).scrollIntoView()), true) : false;
  };
}
function xc(n2, e = null) {
  return function(t, r) {
    let i = false;
    for (let s = 0; s < t.selection.ranges.length && !i; s++) {
      let { $from: { pos: o }, $to: { pos: l } } = t.selection.ranges[s];
      t.doc.nodesBetween(o, l, (a, c) => {
        if (i)
          return false;
        if (!(!a.isTextblock || a.hasMarkup(n2, e)))
          if (a.type == n2)
            i = true;
          else {
            let u = t.doc.resolve(c), d = u.index();
            i = u.parent.canReplaceWith(d, d + 1, n2);
          }
      });
    }
    if (!i)
      return false;
    if (r) {
      let s = t.tr;
      for (let o = 0; o < t.selection.ranges.length; o++) {
        let { $from: { pos: l }, $to: { pos: a } } = t.selection.ranges[o];
        s.setBlockType(l, a, n2, e);
      }
      r(s.scrollIntoView());
    }
    return true;
  };
}
function Yl(...n2) {
  return function(e, t, r) {
    for (let i = 0; i < n2.length; i++)
      if (n2[i](e, t, r))
        return true;
    return false;
  };
}
Yl(Kl, Xd, Zd);
Yl(Kl, tf, nf);
Yl(rf, sf, of, Ng);
typeof navigator < "u" ? /Mac|iP(hone|[oa]d)/.test(navigator.platform) : typeof os < "u" && os.platform && os.platform() == "darwin";
function Bg(n2, e = null) {
  return function(t, r) {
    let { $from: i, $to: s } = t.selection, o = i.blockRange(s);
    if (!o)
      return false;
    let l = r ? t.tr : null;
    return Hg(l, o, n2, e) ? (r && r(l.scrollIntoView()), true) : false;
  };
}
function Hg(n2, e, t, r = null) {
  let i = false, s = e, o = e.$from.doc;
  if (e.depth >= 2 && e.$from.node(e.depth - 1).type.compatibleContent(t) && e.startIndex == 0) {
    if (e.$from.index(e.depth - 1) == 0)
      return false;
    let a = o.resolve(e.start - 2);
    s = new Yi(a, a, e.depth), e.endIndex < e.parent.childCount && (e = new Yi(e.$from, o.resolve(e.$to.end(e.depth)), e.depth)), i = true;
  }
  let l = Il(s, t, r, e);
  return l ? (n2 && Fg(n2, e, l, i, t), true) : false;
}
function Fg(n2, e, t, r, i) {
  let s = A.empty;
  for (let u = t.length - 1; u >= 0; u--)
    s = A.from(t[u].type.create(t[u].attrs, s));
  n2.step(new pe(e.start - (r ? 2 : 0), e.end, e.start, e.end, new O(s, 0, 0), t.length, true));
  let o = 0;
  for (let u = 0; u < t.length; u++)
    t[u].type == i && (o = u + 1);
  let l = t.length - o, a = e.start + t.length - (r ? 2 : 0), c = e.parent;
  for (let u = e.startIndex, d = e.endIndex, f = true; u < d; u++, f = false)
    !f && Rt(n2.doc, a, l) && (n2.split(a, l), a += 2 * l), a += c.child(u).nodeSize;
  return n2;
}
function zg(n2) {
  return function(e, t) {
    let { $from: r, $to: i } = e.selection, s = r.blockRange(i, (o) => o.childCount > 0 && o.firstChild.type == n2);
    return s ? t ? r.node(s.depth - 1).type == n2 ? Vg(e, t, n2, s) : $g(e, t, s) : true : false;
  };
}
function Vg(n2, e, t, r) {
  let i = n2.tr, s = r.end, o = r.$to.end(r.depth);
  s < o && (i.step(new pe(s - 1, o, s, o, new O(A.from(t.create(null, r.parent.copy())), 1, 0), 1, true)), r = new Yi(i.doc.resolve(r.$from.pos), i.doc.resolve(o), r.depth));
  const l = hr(r);
  if (l == null)
    return false;
  i.lift(r, l);
  let a = i.doc.resolve(i.mapping.map(s, -1) - 1);
  return sn(i.doc, a.pos) && a.nodeBefore.type == a.nodeAfter.type && i.join(a.pos), e(i.scrollIntoView()), true;
}
function $g(n2, e, t) {
  let r = n2.tr, i = t.parent;
  for (let h2 = t.end, p2 = t.endIndex - 1, m = t.startIndex; p2 > m; p2--)
    h2 -= i.child(p2).nodeSize, r.delete(h2 - 1, h2 + 1);
  let s = r.doc.resolve(t.start), o = s.nodeAfter;
  if (r.mapping.map(t.end) != t.start + s.nodeAfter.nodeSize)
    return false;
  let l = t.startIndex == 0, a = t.endIndex == i.childCount, c = s.node(-1), u = s.index(-1);
  if (!c.canReplace(u + (l ? 0 : 1), u + 1, o.content.append(a ? A.empty : A.from(i))))
    return false;
  let d = s.pos, f = d + o.nodeSize;
  return r.step(new pe(d - (l ? 1 : 0), f + (a ? 1 : 0), d + 1, f - 1, new O((l ? A.empty : A.from(i.copy(A.empty))).append(a ? A.empty : A.from(i.copy(A.empty))), l ? 0 : 1, a ? 0 : 1), l ? 0 : 1)), e(r.scrollIntoView()), true;
}
function _g(n2) {
  return function(e, t) {
    let { $from: r, $to: i } = e.selection, s = r.blockRange(i, (c) => c.childCount > 0 && c.firstChild.type == n2);
    if (!s)
      return false;
    let o = s.startIndex;
    if (o == 0)
      return false;
    let l = s.parent, a = l.child(o - 1);
    if (a.type != n2)
      return false;
    if (t) {
      let c = a.lastChild && a.lastChild.type == l.type, u = A.from(c ? n2.create() : null), d = new O(A.from(n2.create(null, A.from(l.type.create(null, u)))), c ? 3 : 1, 0), f = s.start, h2 = s.end;
      t(e.tr.step(new pe(f - (c ? 3 : 1), h2, f, h2, d, 1, true)).scrollIntoView());
    }
    return true;
  };
}
function qs(n2) {
  const { state: e, transaction: t } = n2;
  let { selection: r } = t, { doc: i } = t, { storedMarks: s } = t;
  return {
    ...e,
    apply: e.apply.bind(e),
    applyTransaction: e.applyTransaction.bind(e),
    plugins: e.plugins,
    schema: e.schema,
    reconfigure: e.reconfigure.bind(e),
    toJSON: e.toJSON.bind(e),
    get storedMarks() {
      return s;
    },
    get selection() {
      return r;
    },
    get doc() {
      return i;
    },
    get tr() {
      return r = t.selection, i = t.doc, s = t.storedMarks, t;
    }
  };
}
class Js {
  constructor(e) {
    this.editor = e.editor, this.rawCommands = this.editor.extensionManager.commands, this.customState = e.state;
  }
  get hasCustomState() {
    return !!this.customState;
  }
  get state() {
    return this.customState || this.editor.state;
  }
  get commands() {
    const { rawCommands: e, editor: t, state: r } = this, { view: i } = t, { tr: s } = r, o = this.buildProps(s);
    return Object.fromEntries(Object.entries(e).map(([l, a]) => [l, (...u) => {
      const d = a(...u)(o);
      return !s.getMeta("preventDispatch") && !this.hasCustomState && i.dispatch(s), d;
    }]));
  }
  get chain() {
    return () => this.createChain();
  }
  get can() {
    return () => this.createCan();
  }
  createChain(e, t = true) {
    const { rawCommands: r, editor: i, state: s } = this, { view: o } = i, l = [], a = !!e, c = e || s.tr, u = () => (!a && t && !c.getMeta("preventDispatch") && !this.hasCustomState && o.dispatch(c), l.every((f) => f === true)), d = {
      ...Object.fromEntries(Object.entries(r).map(([f, h2]) => [f, (...m) => {
        const g = this.buildProps(c, t), y = h2(...m)(g);
        return l.push(y), d;
      }])),
      run: u
    };
    return d;
  }
  createCan(e) {
    const { rawCommands: t, state: r } = this, i = false, s = e || r.tr, o = this.buildProps(s, i);
    return {
      ...Object.fromEntries(Object.entries(t).map(([a, c]) => [a, (...u) => c(...u)({ ...o, dispatch: void 0 })])),
      chain: () => this.createChain(s, i)
    };
  }
  buildProps(e, t = true) {
    const { rawCommands: r, editor: i, state: s } = this, { view: o } = i, l = {
      tr: e,
      editor: i,
      view: o,
      state: qs({
        state: s,
        transaction: e
      }),
      dispatch: t ? () => {
      } : void 0,
      chain: () => this.createChain(e, t),
      can: () => this.createCan(e),
      get commands() {
        return Object.fromEntries(Object.entries(r).map(([a, c]) => [a, (...u) => c(...u)(l)]));
      }
    };
    return l;
  }
}
class jg {
  constructor() {
    this.callbacks = {};
  }
  on(e, t) {
    return this.callbacks[e] || (this.callbacks[e] = []), this.callbacks[e].push(t), this;
  }
  emit(e, ...t) {
    const r = this.callbacks[e];
    return r && r.forEach((i) => i.apply(this, t)), this;
  }
  off(e, t) {
    const r = this.callbacks[e];
    return r && (t ? this.callbacks[e] = r.filter((i) => i !== t) : delete this.callbacks[e]), this;
  }
  once(e, t) {
    const r = (...i) => {
      this.off(e, r), t.apply(this, i);
    };
    return this.on(e, r);
  }
  removeAllListeners() {
    this.callbacks = {};
  }
}
function L(n2, e, t) {
  return n2.config[e] === void 0 && n2.parent ? L(n2.parent, e, t) : typeof n2.config[e] == "function" ? n2.config[e].bind({
    ...t,
    parent: n2.parent ? L(n2.parent, e, t) : null
  }) : n2.config[e];
}
function Gs(n2) {
  const e = n2.filter((i) => i.type === "extension"), t = n2.filter((i) => i.type === "node"), r = n2.filter((i) => i.type === "mark");
  return {
    baseExtensions: e,
    nodeExtensions: t,
    markExtensions: r
  };
}
function cf(n2) {
  const e = [], { nodeExtensions: t, markExtensions: r } = Gs(n2), i = [...t, ...r], s = {
    default: null,
    rendered: true,
    renderHTML: null,
    parseHTML: null,
    keepOnSplit: true,
    isRequired: false
  };
  return n2.forEach((o) => {
    const l = {
      name: o.name,
      options: o.options,
      storage: o.storage,
      extensions: i
    }, a = L(o, "addGlobalAttributes", l);
    if (!a)
      return;
    a().forEach((u) => {
      u.types.forEach((d) => {
        Object.entries(u.attributes).forEach(([f, h2]) => {
          e.push({
            type: d,
            name: f,
            attribute: {
              ...s,
              ...h2
            }
          });
        });
      });
    });
  }), i.forEach((o) => {
    const l = {
      name: o.name,
      options: o.options,
      storage: o.storage
    }, a = L(o, "addAttributes", l);
    if (!a)
      return;
    const c = a();
    Object.entries(c).forEach(([u, d]) => {
      const f = {
        ...s,
        ...d
      };
      typeof (f == null ? void 0 : f.default) == "function" && (f.default = f.default()), f != null && f.isRequired && (f == null ? void 0 : f.default) === void 0 && delete f.default, e.push({
        type: o.name,
        name: u,
        attribute: f
      });
    });
  }), e;
}
function ge(n2, e) {
  if (typeof n2 == "string") {
    if (!e.nodes[n2])
      throw Error(`There is no node type named '${n2}'. Maybe you forgot to add the extension?`);
    return e.nodes[n2];
  }
  return n2;
}
function Q(...n2) {
  return n2.filter((e) => !!e).reduce((e, t) => {
    const r = { ...e };
    return Object.entries(t).forEach(([i, s]) => {
      if (!r[i]) {
        r[i] = s;
        return;
      }
      if (i === "class") {
        const l = s ? String(s).split(" ") : [], a = r[i] ? r[i].split(" ") : [], c = l.filter((u) => !a.includes(u));
        r[i] = [...a, ...c].join(" ");
      } else if (i === "style") {
        const l = s ? s.split(";").map((u) => u.trim()).filter(Boolean) : [], a = r[i] ? r[i].split(";").map((u) => u.trim()).filter(Boolean) : [], c = /* @__PURE__ */ new Map();
        a.forEach((u) => {
          const [d, f] = u.split(":").map((h2) => h2.trim());
          c.set(d, f);
        }), l.forEach((u) => {
          const [d, f] = u.split(":").map((h2) => h2.trim());
          c.set(d, f);
        }), r[i] = Array.from(c.entries()).map(([u, d]) => `${u}: ${d}`).join("; ");
      } else
        r[i] = s;
    }), r;
  }, {});
}
function nl(n2, e) {
  return e.filter((t) => t.type === n2.type.name).filter((t) => t.attribute.rendered).map((t) => t.attribute.renderHTML ? t.attribute.renderHTML(n2.attrs) || {} : {
    [t.name]: n2.attrs[t.name]
  }).reduce((t, r) => Q(t, r), {});
}
function uf(n2) {
  return typeof n2 == "function";
}
function W(n2, e = void 0, ...t) {
  return uf(n2) ? e ? n2.bind(e)(...t) : n2(...t) : n2;
}
function Wg(n2 = {}) {
  return Object.keys(n2).length === 0 && n2.constructor === Object;
}
function Ug(n2) {
  return typeof n2 != "string" ? n2 : n2.match(/^[+-]?(?:\d*\.)?\d+$/) ? Number(n2) : n2 === "true" ? true : n2 === "false" ? false : n2;
}
function Sc(n2, e) {
  return "style" in n2 ? n2 : {
    ...n2,
    getAttrs: (t) => {
      const r = n2.getAttrs ? n2.getAttrs(t) : n2.attrs;
      if (r === false)
        return false;
      const i = e.reduce((s, o) => {
        const l = o.attribute.parseHTML ? o.attribute.parseHTML(t) : Ug(t.getAttribute(o.name));
        return l == null ? s : {
          ...s,
          [o.name]: l
        };
      }, {});
      return { ...r, ...i };
    }
  };
}
function Mc(n2) {
  return Object.fromEntries(
    // @ts-ignore
    Object.entries(n2).filter(([e, t]) => e === "attrs" && Wg(t) ? false : t != null)
  );
}
function Kg(n2, e) {
  var t;
  const r = cf(n2), { nodeExtensions: i, markExtensions: s } = Gs(n2), o = (t = i.find((c) => L(c, "topNode"))) === null || t === void 0 ? void 0 : t.name, l = Object.fromEntries(i.map((c) => {
    const u = r.filter((y) => y.type === c.name), d = {
      name: c.name,
      options: c.options,
      storage: c.storage,
      editor: e
    }, f = n2.reduce((y, w) => {
      const C = L(w, "extendNodeSchema", d);
      return {
        ...y,
        ...C ? C(c) : {}
      };
    }, {}), h2 = Mc({
      ...f,
      content: W(L(c, "content", d)),
      marks: W(L(c, "marks", d)),
      group: W(L(c, "group", d)),
      inline: W(L(c, "inline", d)),
      atom: W(L(c, "atom", d)),
      selectable: W(L(c, "selectable", d)),
      draggable: W(L(c, "draggable", d)),
      code: W(L(c, "code", d)),
      whitespace: W(L(c, "whitespace", d)),
      linebreakReplacement: W(L(c, "linebreakReplacement", d)),
      defining: W(L(c, "defining", d)),
      isolating: W(L(c, "isolating", d)),
      attrs: Object.fromEntries(u.map((y) => {
        var w;
        return [y.name, { default: (w = y == null ? void 0 : y.attribute) === null || w === void 0 ? void 0 : w.default }];
      }))
    }), p2 = W(L(c, "parseHTML", d));
    p2 && (h2.parseDOM = p2.map((y) => Sc(y, u)));
    const m = L(c, "renderHTML", d);
    m && (h2.toDOM = (y) => m({
      node: y,
      HTMLAttributes: nl(y, u)
    }));
    const g = L(c, "renderText", d);
    return g && (h2.toText = g), [c.name, h2];
  })), a = Object.fromEntries(s.map((c) => {
    const u = r.filter((g) => g.type === c.name), d = {
      name: c.name,
      options: c.options,
      storage: c.storage,
      editor: e
    }, f = n2.reduce((g, y) => {
      const w = L(y, "extendMarkSchema", d);
      return {
        ...g,
        ...w ? w(c) : {}
      };
    }, {}), h2 = Mc({
      ...f,
      inclusive: W(L(c, "inclusive", d)),
      excludes: W(L(c, "excludes", d)),
      group: W(L(c, "group", d)),
      spanning: W(L(c, "spanning", d)),
      code: W(L(c, "code", d)),
      attrs: Object.fromEntries(u.map((g) => {
        var y;
        return [g.name, { default: (y = g == null ? void 0 : g.attribute) === null || y === void 0 ? void 0 : y.default }];
      }))
    }), p2 = W(L(c, "parseHTML", d));
    p2 && (h2.parseDOM = p2.map((g) => Sc(g, u)));
    const m = L(c, "renderHTML", d);
    return m && (h2.toDOM = (g) => m({
      mark: g,
      HTMLAttributes: nl(g, u)
    })), [c.name, h2];
  }));
  return new Yu({
    topNode: o,
    nodes: l,
    marks: a
  });
}
function xo(n2, e) {
  return e.nodes[n2] || e.marks[n2] || null;
}
function Ac(n2, e) {
  return Array.isArray(e) ? e.some((t) => (typeof t == "string" ? t : t.name) === n2.name) : e;
}
function Xl(n2, e) {
  const t = Pn.fromSchema(e).serializeFragment(n2), i = document.implementation.createHTMLDocument().createElement("div");
  return i.appendChild(t), i.innerHTML;
}
const qg = (n2, e = 500) => {
  let t = "";
  const r = n2.parentOffset;
  return n2.parent.nodesBetween(Math.max(0, r - e), r, (i, s, o, l) => {
    var a, c;
    const u = ((c = (a = i.type.spec).toText) === null || c === void 0 ? void 0 : c.call(a, {
      node: i,
      pos: s,
      parent: o,
      index: l
    })) || i.textContent || "%leaf%";
    t += i.isAtom && !i.isText ? u : u.slice(0, Math.max(0, r - s));
  }), t;
};
function Ql(n2) {
  return Object.prototype.toString.call(n2) === "[object RegExp]";
}
class Ys {
  constructor(e) {
    this.find = e.find, this.handler = e.handler;
  }
}
const Jg = (n2, e) => {
  if (Ql(e))
    return e.exec(n2);
  const t = e(n2);
  if (!t)
    return null;
  const r = [t.text];
  return r.index = t.index, r.input = n2, r.data = t.data, t.replaceWith && (t.text.includes(t.replaceWith) || console.warn('[tiptap warn]: "inputRuleMatch.replaceWith" must be part of "inputRuleMatch.text".'), r.push(t.replaceWith)), r;
};
function xi(n2) {
  var e;
  const { editor: t, from: r, to: i, text: s, rules: o, plugin: l } = n2, { view: a } = t;
  if (a.composing)
    return false;
  const c = a.state.doc.resolve(r);
  if (
    // check for code node
    c.parent.type.spec.code || !((e = c.nodeBefore || c.nodeAfter) === null || e === void 0) && e.marks.find((f) => f.type.spec.code)
  )
    return false;
  let u = false;
  const d = qg(c) + s;
  return o.forEach((f) => {
    if (u)
      return;
    const h2 = Jg(d, f.find);
    if (!h2)
      return;
    const p2 = a.state.tr, m = qs({
      state: a.state,
      transaction: p2
    }), g = {
      from: r - (h2[0].length - s.length),
      to: i
    }, { commands: y, chain: w, can: C } = new Js({
      editor: t,
      state: m
    });
    f.handler({
      state: m,
      range: g,
      match: h2,
      commands: y,
      chain: w,
      can: C
    }) === null || !p2.steps.length || (p2.setMeta(l, {
      transform: p2,
      from: r,
      to: i,
      text: s
    }), a.dispatch(p2), u = true);
  }), u;
}
function Gg(n2) {
  const { editor: e, rules: t } = n2, r = new le({
    state: {
      init() {
        return null;
      },
      apply(i, s, o) {
        const l = i.getMeta(r);
        if (l)
          return l;
        const a = i.getMeta("applyInputRules");
        return !!a && setTimeout(() => {
          let { text: u } = a;
          typeof u == "string" ? u = u : u = Xl(A.from(u), o.schema);
          const { from: d } = a, f = d + u.length;
          xi({
            editor: e,
            from: d,
            to: f,
            text: u,
            rules: t,
            plugin: r
          });
        }), i.selectionSet || i.docChanged ? null : s;
      }
    },
    props: {
      handleTextInput(i, s, o, l) {
        return xi({
          editor: e,
          from: s,
          to: o,
          text: l,
          rules: t,
          plugin: r
        });
      },
      handleDOMEvents: {
        compositionend: (i) => (setTimeout(() => {
          const { $cursor: s } = i.state.selection;
          s && xi({
            editor: e,
            from: s.pos,
            to: s.pos,
            text: "",
            rules: t,
            plugin: r
          });
        }), false)
      },
      // add support for input rules to trigger on enter
      // this is useful for example for code blocks
      handleKeyDown(i, s) {
        if (s.key !== "Enter")
          return false;
        const { $cursor: o } = i.state.selection;
        return o ? xi({
          editor: e,
          from: o.pos,
          to: o.pos,
          text: `
`,
          rules: t,
          plugin: r
        }) : false;
      }
    },
    // @ts-ignore
    isInputRules: true
  });
  return r;
}
function Yg(n2) {
  return Object.prototype.toString.call(n2).slice(8, -1);
}
function Si(n2) {
  return Yg(n2) !== "Object" ? false : n2.constructor === Object && Object.getPrototypeOf(n2) === Object.prototype;
}
function Xs(n2, e) {
  const t = { ...n2 };
  return Si(n2) && Si(e) && Object.keys(e).forEach((r) => {
    Si(e[r]) && Si(n2[r]) ? t[r] = Xs(n2[r], e[r]) : t[r] = e[r];
  }), t;
}
class lt {
  constructor(e = {}) {
    this.type = "mark", this.name = "mark", this.parent = null, this.child = null, this.config = {
      name: this.name,
      defaultOptions: {}
    }, this.config = {
      ...this.config,
      ...e
    }, this.name = this.config.name, e.defaultOptions && Object.keys(e.defaultOptions).length > 0 && console.warn(`[tiptap warn]: BREAKING CHANGE: "defaultOptions" is deprecated. Please use "addOptions" instead. Found in extension: "${this.name}".`), this.options = this.config.defaultOptions, this.config.addOptions && (this.options = W(L(this, "addOptions", {
      name: this.name
    }))), this.storage = W(L(this, "addStorage", {
      name: this.name,
      options: this.options
    })) || {};
  }
  static create(e = {}) {
    return new lt(e);
  }
  configure(e = {}) {
    const t = this.extend({
      ...this.config,
      addOptions: () => Xs(this.options, e)
    });
    return t.name = this.name, t.parent = this.parent, t;
  }
  extend(e = {}) {
    const t = new lt(e);
    return t.parent = this, this.child = t, t.name = e.name ? e.name : t.parent.name, e.defaultOptions && Object.keys(e.defaultOptions).length > 0 && console.warn(`[tiptap warn]: BREAKING CHANGE: "defaultOptions" is deprecated. Please use "addOptions" instead. Found in extension: "${t.name}".`), t.options = W(L(t, "addOptions", {
      name: t.name
    })), t.storage = W(L(t, "addStorage", {
      name: t.name,
      options: t.options
    })), t;
  }
  static handleExit({ editor: e, mark: t }) {
    const { tr: r } = e.state, i = e.state.selection.$from;
    if (i.pos === i.end()) {
      const o = i.marks();
      if (!!!o.find((c) => (c == null ? void 0 : c.type.name) === t.name))
        return false;
      const a = o.find((c) => (c == null ? void 0 : c.type.name) === t.name);
      return a && r.removeStoredMark(a), r.insertText(" ", i.pos), e.view.dispatch(r), true;
    }
    return false;
  }
}
function Xg(n2) {
  return typeof n2 == "number";
}
class df {
  constructor(e) {
    this.find = e.find, this.handler = e.handler;
  }
}
const Qg = (n2, e, t) => {
  if (Ql(e))
    return [...n2.matchAll(e)];
  const r = e(n2, t);
  return r ? r.map((i) => {
    const s = [i.text];
    return s.index = i.index, s.input = n2, s.data = i.data, i.replaceWith && (i.text.includes(i.replaceWith) || console.warn('[tiptap warn]: "pasteRuleMatch.replaceWith" must be part of "pasteRuleMatch.text".'), s.push(i.replaceWith)), s;
  }) : [];
};
function Zg(n2) {
  const { editor: e, state: t, from: r, to: i, rule: s, pasteEvent: o, dropEvent: l } = n2, { commands: a, chain: c, can: u } = new Js({
    editor: e,
    state: t
  }), d = [];
  return t.doc.nodesBetween(r, i, (h2, p2) => {
    if (!h2.isTextblock || h2.type.spec.code)
      return;
    const m = Math.max(r, p2), g = Math.min(i, p2 + h2.content.size), y = h2.textBetween(m - p2, g - p2, void 0, "￼");
    Qg(y, s.find, o).forEach((C) => {
      if (C.index === void 0)
        return;
      const b = m + C.index + 1, S = b + C[0].length, k = {
        from: t.tr.mapping.map(b),
        to: t.tr.mapping.map(S)
      }, T = s.handler({
        state: t,
        range: k,
        match: C,
        commands: a,
        chain: c,
        can: u,
        pasteEvent: o,
        dropEvent: l
      });
      d.push(T);
    });
  }), d.every((h2) => h2 !== null);
}
let Mi = null;
const ey = (n2) => {
  var e;
  const t = new ClipboardEvent("paste", {
    clipboardData: new DataTransfer()
  });
  return (e = t.clipboardData) === null || e === void 0 || e.setData("text/html", n2), t;
};
function ty(n2) {
  const { editor: e, rules: t } = n2;
  let r = null, i = false, s = false, o = typeof ClipboardEvent < "u" ? new ClipboardEvent("paste") : null, l;
  try {
    l = typeof DragEvent < "u" ? new DragEvent("drop") : null;
  } catch {
    l = null;
  }
  const a = ({ state: u, from: d, to: f, rule: h2, pasteEvt: p2 }) => {
    const m = u.tr, g = qs({
      state: u,
      transaction: m
    });
    if (!(!Zg({
      editor: e,
      state: g,
      from: Math.max(d - 1, 0),
      to: f.b - 1,
      rule: h2,
      pasteEvent: p2,
      dropEvent: l
    }) || !m.steps.length)) {
      try {
        l = typeof DragEvent < "u" ? new DragEvent("drop") : null;
      } catch {
        l = null;
      }
      return o = typeof ClipboardEvent < "u" ? new ClipboardEvent("paste") : null, m;
    }
  };
  return t.map((u) => new le({
    // we register a global drag handler to track the current drag source element
    view(d) {
      const f = (p2) => {
        var m;
        r = !((m = d.dom.parentElement) === null || m === void 0) && m.contains(p2.target) ? d.dom.parentElement : null, r && (Mi = e);
      }, h2 = () => {
        Mi && (Mi = null);
      };
      return window.addEventListener("dragstart", f), window.addEventListener("dragend", h2), {
        destroy() {
          window.removeEventListener("dragstart", f), window.removeEventListener("dragend", h2);
        }
      };
    },
    props: {
      handleDOMEvents: {
        drop: (d, f) => {
          if (s = r === d.dom.parentElement, l = f, !s) {
            const h2 = Mi;
            h2 && setTimeout(() => {
              const p2 = h2.state.selection;
              p2 && h2.commands.deleteRange({ from: p2.from, to: p2.to });
            }, 10);
          }
          return false;
        },
        paste: (d, f) => {
          var h2;
          const p2 = (h2 = f.clipboardData) === null || h2 === void 0 ? void 0 : h2.getData("text/html");
          return o = f, i = !!(p2 != null && p2.includes("data-pm-slice")), false;
        }
      }
    },
    appendTransaction: (d, f, h2) => {
      const p2 = d[0], m = p2.getMeta("uiEvent") === "paste" && !i, g = p2.getMeta("uiEvent") === "drop" && !s, y = p2.getMeta("applyPasteRules"), w = !!y;
      if (!m && !g && !w)
        return;
      if (w) {
        let { text: S } = y;
        typeof S == "string" ? S = S : S = Xl(A.from(S), h2.schema);
        const { from: k } = y, T = k + S.length, M = ey(S);
        return a({
          rule: u,
          state: h2,
          from: k,
          to: { b: T },
          pasteEvt: M
        });
      }
      const C = f.doc.content.findDiffStart(h2.doc.content), b = f.doc.content.findDiffEnd(h2.doc.content);
      if (!(!Xg(C) || !b || C === b.b))
        return a({
          rule: u,
          state: h2,
          from: C,
          to: b,
          pasteEvt: o
        });
    }
  }));
}
function ny(n2) {
  const e = n2.filter((t, r) => n2.indexOf(t) !== r);
  return Array.from(new Set(e));
}
class Qn {
  constructor(e, t) {
    this.splittableMarks = [], this.editor = t, this.extensions = Qn.resolve(e), this.schema = Kg(this.extensions, t), this.setupExtensions();
  }
  /**
   * Returns a flattened and sorted extension list while
   * also checking for duplicated extensions and warns the user.
   * @param extensions An array of Tiptap extensions
   * @returns An flattened and sorted array of Tiptap extensions
   */
  static resolve(e) {
    const t = Qn.sort(Qn.flatten(e)), r = ny(t.map((i) => i.name));
    return r.length && console.warn(`[tiptap warn]: Duplicate extension names found: [${r.map((i) => `'${i}'`).join(", ")}]. This can lead to issues.`), t;
  }
  /**
   * Create a flattened array of extensions by traversing the `addExtensions` field.
   * @param extensions An array of Tiptap extensions
   * @returns A flattened array of Tiptap extensions
   */
  static flatten(e) {
    return e.map((t) => {
      const r = {
        name: t.name,
        options: t.options,
        storage: t.storage
      }, i = L(t, "addExtensions", r);
      return i ? [t, ...this.flatten(i())] : t;
    }).flat(10);
  }
  /**
   * Sort extensions by priority.
   * @param extensions An array of Tiptap extensions
   * @returns A sorted array of Tiptap extensions by priority
   */
  static sort(e) {
    return e.sort((r, i) => {
      const s = L(r, "priority") || 100, o = L(i, "priority") || 100;
      return s > o ? -1 : s < o ? 1 : 0;
    });
  }
  /**
   * Get all commands from the extensions.
   * @returns An object with all commands where the key is the command name and the value is the command function
   */
  get commands() {
    return this.extensions.reduce((e, t) => {
      const r = {
        name: t.name,
        options: t.options,
        storage: t.storage,
        editor: this.editor,
        type: xo(t.name, this.schema)
      }, i = L(t, "addCommands", r);
      return i ? {
        ...e,
        ...i()
      } : e;
    }, {});
  }
  /**
   * Get all registered Prosemirror plugins from the extensions.
   * @returns An array of Prosemirror plugins
   */
  get plugins() {
    const { editor: e } = this, t = Qn.sort([...this.extensions].reverse()), r = [], i = [], s = t.map((o) => {
      const l = {
        name: o.name,
        options: o.options,
        storage: o.storage,
        editor: e,
        type: xo(o.name, this.schema)
      }, a = [], c = L(o, "addKeyboardShortcuts", l);
      let u = {};
      if (o.type === "mark" && L(o, "exitable", l) && (u.ArrowRight = () => lt.handleExit({ editor: e, mark: o })), c) {
        const m = Object.fromEntries(Object.entries(c()).map(([g, y]) => [g, () => y({ editor: e })]));
        u = { ...u, ...m };
      }
      const d = Cg(u);
      a.push(d);
      const f = L(o, "addInputRules", l);
      Ac(o, e.options.enableInputRules) && f && r.push(...f());
      const h2 = L(o, "addPasteRules", l);
      Ac(o, e.options.enablePasteRules) && h2 && i.push(...h2());
      const p2 = L(o, "addProseMirrorPlugins", l);
      if (p2) {
        const m = p2();
        a.push(...m);
      }
      return a;
    }).flat();
    return [
      Gg({
        editor: e,
        rules: r
      }),
      ...ty({
        editor: e,
        rules: i
      }),
      ...s
    ];
  }
  /**
   * Get all attributes from the extensions.
   * @returns An array of attributes
   */
  get attributes() {
    return cf(this.extensions);
  }
  /**
   * Get all node views from the extensions.
   * @returns An object with all node views where the key is the node name and the value is the node view function
   */
  get nodeViews() {
    const { editor: e } = this, { nodeExtensions: t } = Gs(this.extensions);
    return Object.fromEntries(t.filter((r) => !!L(r, "addNodeView")).map((r) => {
      const i = this.attributes.filter((a) => a.type === r.name), s = {
        name: r.name,
        options: r.options,
        storage: r.storage,
        editor: e,
        type: ge(r.name, this.schema)
      }, o = L(r, "addNodeView", s);
      if (!o)
        return [];
      const l = (a, c, u, d, f) => {
        const h2 = nl(a, i);
        return o()({
          // pass-through
          node: a,
          view: c,
          getPos: u,
          decorations: d,
          innerDecorations: f,
          // tiptap-specific
          editor: e,
          extension: r,
          HTMLAttributes: h2
        });
      };
      return [r.name, l];
    }));
  }
  /**
   * Go through all extensions, create extension storages & setup marks
   * & bind editor event listener.
   */
  setupExtensions() {
    this.extensions.forEach((e) => {
      var t;
      this.editor.extensionStorage[e.name] = e.storage;
      const r = {
        name: e.name,
        options: e.options,
        storage: e.storage,
        editor: this.editor,
        type: xo(e.name, this.schema)
      };
      e.type === "mark" && (!((t = W(L(e, "keepOnSplit", r))) !== null && t !== void 0) || t) && this.splittableMarks.push(e.name);
      const i = L(e, "onBeforeCreate", r), s = L(e, "onCreate", r), o = L(e, "onUpdate", r), l = L(e, "onSelectionUpdate", r), a = L(e, "onTransaction", r), c = L(e, "onFocus", r), u = L(e, "onBlur", r), d = L(e, "onDestroy", r);
      i && this.editor.on("beforeCreate", i), s && this.editor.on("create", s), o && this.editor.on("update", o), l && this.editor.on("selectionUpdate", l), a && this.editor.on("transaction", a), c && this.editor.on("focus", c), u && this.editor.on("blur", u), d && this.editor.on("destroy", d);
    });
  }
}
class fe {
  constructor(e = {}) {
    this.type = "extension", this.name = "extension", this.parent = null, this.child = null, this.config = {
      name: this.name,
      defaultOptions: {}
    }, this.config = {
      ...this.config,
      ...e
    }, this.name = this.config.name, e.defaultOptions && Object.keys(e.defaultOptions).length > 0 && console.warn(`[tiptap warn]: BREAKING CHANGE: "defaultOptions" is deprecated. Please use "addOptions" instead. Found in extension: "${this.name}".`), this.options = this.config.defaultOptions, this.config.addOptions && (this.options = W(L(this, "addOptions", {
      name: this.name
    }))), this.storage = W(L(this, "addStorage", {
      name: this.name,
      options: this.options
    })) || {};
  }
  static create(e = {}) {
    return new fe(e);
  }
  configure(e = {}) {
    const t = this.extend({
      ...this.config,
      addOptions: () => Xs(this.options, e)
    });
    return t.name = this.name, t.parent = this.parent, t;
  }
  extend(e = {}) {
    const t = new fe({ ...this.config, ...e });
    return t.parent = this, this.child = t, t.name = e.name ? e.name : t.parent.name, e.defaultOptions && Object.keys(e.defaultOptions).length > 0 && console.warn(`[tiptap warn]: BREAKING CHANGE: "defaultOptions" is deprecated. Please use "addOptions" instead. Found in extension: "${t.name}".`), t.options = W(L(t, "addOptions", {
      name: t.name
    })), t.storage = W(L(t, "addStorage", {
      name: t.name,
      options: t.options
    })), t;
  }
}
function ff(n2, e, t) {
  const { from: r, to: i } = e, { blockSeparator: s = `

`, textSerializers: o = {} } = t || {};
  let l = "";
  return n2.nodesBetween(r, i, (a, c, u, d) => {
    var f;
    a.isBlock && c > r && (l += s);
    const h2 = o == null ? void 0 : o[a.type.name];
    if (h2)
      return u && (l += h2({
        node: a,
        pos: c,
        parent: u,
        index: d,
        range: e
      })), false;
    a.isText && (l += (f = a == null ? void 0 : a.text) === null || f === void 0 ? void 0 : f.slice(Math.max(r, c) - c, i - c));
  }), l;
}
function Zl(n2) {
  return Object.fromEntries(Object.entries(n2.nodes).filter(([, e]) => e.spec.toText).map(([e, t]) => [e, t.spec.toText]));
}
const ry = fe.create({
  name: "clipboardTextSerializer",
  addOptions() {
    return {
      blockSeparator: void 0
    };
  },
  addProseMirrorPlugins() {
    return [
      new le({
        key: new ue("clipboardTextSerializer"),
        props: {
          clipboardTextSerializer: () => {
            const { editor: n2 } = this, { state: e, schema: t } = n2, { doc: r, selection: i } = e, { ranges: s } = i, o = Math.min(...s.map((u) => u.$from.pos)), l = Math.max(...s.map((u) => u.$to.pos)), a = Zl(t);
            return ff(r, { from: o, to: l }, {
              ...this.options.blockSeparator !== void 0 ? { blockSeparator: this.options.blockSeparator } : {},
              textSerializers: a
            });
          }
        }
      })
    ];
  }
}), iy = () => ({ editor: n2, view: e }) => (requestAnimationFrame(() => {
  var t;
  n2.isDestroyed || (e.dom.blur(), (t = window == null ? void 0 : window.getSelection()) === null || t === void 0 || t.removeAllRanges());
}), true), sy = (n2 = false) => ({ commands: e }) => e.setContent("", n2), oy = () => ({ state: n2, tr: e, dispatch: t }) => {
  const { selection: r } = e, { ranges: i } = r;
  return t && i.forEach(({ $from: s, $to: o }) => {
    n2.doc.nodesBetween(s.pos, o.pos, (l, a) => {
      if (l.type.isText)
        return;
      const { doc: c, mapping: u } = e, d = c.resolve(u.map(a)), f = c.resolve(u.map(a + l.nodeSize)), h2 = d.blockRange(f);
      if (!h2)
        return;
      const p2 = hr(h2);
      if (l.type.isTextblock) {
        const { defaultType: m } = d.parent.contentMatchAt(d.index());
        e.setNodeMarkup(h2.start, m);
      }
      (p2 || p2 === 0) && e.lift(h2, p2);
    });
  }), true;
}, ly = (n2) => (e) => n2(e), ay = () => ({ state: n2, dispatch: e }) => sf(n2, e), cy = (n2, e) => ({ editor: t, tr: r }) => {
  const { state: i } = t, s = i.doc.slice(n2.from, n2.to);
  r.deleteRange(n2.from, n2.to);
  const o = r.mapping.map(e);
  return r.insert(o, s.content), r.setSelection(new F(r.doc.resolve(o - 1))), true;
}, uy = () => ({ tr: n2, dispatch: e }) => {
  const { selection: t } = n2, r = t.$anchor.node();
  if (r.content.size > 0)
    return false;
  const i = n2.selection.$anchor;
  for (let s = i.depth; s > 0; s -= 1)
    if (i.node(s).type === r.type) {
      if (e) {
        const l = i.before(s), a = i.after(s);
        n2.delete(l, a).scrollIntoView();
      }
      return true;
    }
  return false;
}, dy = (n2) => ({ tr: e, state: t, dispatch: r }) => {
  const i = ge(n2, t.schema), s = e.selection.$anchor;
  for (let o = s.depth; o > 0; o -= 1)
    if (s.node(o).type === i) {
      if (r) {
        const a = s.before(o), c = s.after(o);
        e.delete(a, c).scrollIntoView();
      }
      return true;
    }
  return false;
}, fy = (n2) => ({ tr: e, dispatch: t }) => {
  const { from: r, to: i } = n2;
  return t && e.delete(r, i), true;
}, hy = () => ({ state: n2, dispatch: e }) => Kl(n2, e), py = () => ({ commands: n2 }) => n2.keyboardShortcut("Enter"), my = () => ({ state: n2, dispatch: e }) => Tg(n2, e);
function is(n2, e, t = { strict: true }) {
  const r = Object.keys(e);
  return r.length ? r.every((i) => t.strict ? e[i] === n2[i] : Ql(e[i]) ? e[i].test(n2[i]) : e[i] === n2[i]) : true;
}
function hf(n2, e, t = {}) {
  return n2.find((r) => r.type === e && is(
    // Only check equality for the attributes that are provided
    Object.fromEntries(Object.keys(t).map((i) => [i, r.attrs[i]])),
    t
  ));
}
function Ec(n2, e, t = {}) {
  return !!hf(n2, e, t);
}
function ea(n2, e, t) {
  var r;
  if (!n2 || !e)
    return;
  let i = n2.parent.childAfter(n2.parentOffset);
  if ((!i.node || !i.node.marks.some((u) => u.type === e)) && (i = n2.parent.childBefore(n2.parentOffset)), !i.node || !i.node.marks.some((u) => u.type === e) || (t = t || ((r = i.node.marks[0]) === null || r === void 0 ? void 0 : r.attrs), !hf([...i.node.marks], e, t)))
    return;
  let o = i.index, l = n2.start() + i.offset, a = o + 1, c = l + i.node.nodeSize;
  for (; o > 0 && Ec([...n2.parent.child(o - 1).marks], e, t); )
    o -= 1, l -= n2.parent.child(o).nodeSize;
  for (; a < n2.parent.childCount && Ec([...n2.parent.child(a).marks], e, t); )
    c += n2.parent.child(a).nodeSize, a += 1;
  return {
    from: l,
    to: c
  };
}
function ln(n2, e) {
  if (typeof n2 == "string") {
    if (!e.marks[n2])
      throw Error(`There is no mark type named '${n2}'. Maybe you forgot to add the extension?`);
    return e.marks[n2];
  }
  return n2;
}
const gy = (n2, e = {}) => ({ tr: t, state: r, dispatch: i }) => {
  const s = ln(n2, r.schema), { doc: o, selection: l } = t, { $from: a, from: c, to: u } = l;
  if (i) {
    const d = ea(a, s, e);
    if (d && d.from <= c && d.to >= u) {
      const f = F.create(o, d.from, d.to);
      t.setSelection(f);
    }
  }
  return true;
}, yy = (n2) => (e) => {
  const t = typeof n2 == "function" ? n2(e) : n2;
  for (let r = 0; r < t.length; r += 1)
    if (t[r](e))
      return true;
  return false;
};
function ta(n2) {
  return n2 instanceof F;
}
function Lt(n2 = 0, e = 0, t = 0) {
  return Math.min(Math.max(n2, e), t);
}
function pf(n2, e = null) {
  if (!e)
    return null;
  const t = $.atStart(n2), r = $.atEnd(n2);
  if (e === "start" || e === true)
    return t;
  if (e === "end")
    return r;
  const i = t.from, s = r.to;
  return e === "all" ? F.create(n2, Lt(0, i, s), Lt(n2.content.size, i, s)) : F.create(n2, Lt(e, i, s), Lt(e, i, s));
}
function mf() {
  return navigator.platform === "Android" || /android/i.test(navigator.userAgent);
}
function Qs() {
  return [
    "iPad Simulator",
    "iPhone Simulator",
    "iPod Simulator",
    "iPad",
    "iPhone",
    "iPod"
  ].includes(navigator.platform) || navigator.userAgent.includes("Mac") && "ontouchend" in document;
}
const by = (n2 = null, e = {}) => ({ editor: t, view: r, tr: i, dispatch: s }) => {
  e = {
    scrollIntoView: true,
    ...e
  };
  const o = () => {
    (Qs() || mf()) && r.dom.focus(), requestAnimationFrame(() => {
      t.isDestroyed || (r.focus(), e != null && e.scrollIntoView && t.commands.scrollIntoView());
    });
  };
  if (r.hasFocus() && n2 === null || n2 === false)
    return true;
  if (s && n2 === null && !ta(t.state.selection))
    return o(), true;
  const l = pf(i.doc, n2) || t.state.selection, a = t.state.selection.eq(l);
  return s && (a || i.setSelection(l), a && i.storedMarks && i.setStoredMarks(i.storedMarks), o()), true;
}, vy = (n2, e) => (t) => n2.every((r, i) => e(r, { ...t, index: i })), wy = (n2, e) => ({ tr: t, commands: r }) => r.insertContentAt({ from: t.selection.from, to: t.selection.to }, n2, e), gf = (n2) => {
  const e = n2.childNodes;
  for (let t = e.length - 1; t >= 0; t -= 1) {
    const r = e[t];
    r.nodeType === 3 && r.nodeValue && /^(\n\s\s|\n)$/.test(r.nodeValue) ? n2.removeChild(r) : r.nodeType === 1 && gf(r);
  }
  return n2;
};
function Ai(n2) {
  const e = `<body>${n2}</body>`, t = new window.DOMParser().parseFromString(e, "text/html").body;
  return gf(t);
}
function Jr(n2, e, t) {
  if (n2 instanceof Qt || n2 instanceof A)
    return n2;
  t = {
    slice: true,
    parseOptions: {},
    ...t
  };
  const r = typeof n2 == "object" && n2 !== null, i = typeof n2 == "string";
  if (r)
    try {
      if (Array.isArray(n2) && n2.length > 0)
        return A.fromArray(n2.map((l) => e.nodeFromJSON(l)));
      const o = e.nodeFromJSON(n2);
      return t.errorOnInvalidContent && o.check(), o;
    } catch (s) {
      if (t.errorOnInvalidContent)
        throw new Error("[tiptap error]: Invalid JSON content", { cause: s });
      return console.warn("[tiptap warn]: Invalid content.", "Passed value:", n2, "Error:", s), Jr("", e, t);
    }
  if (i) {
    if (t.errorOnInvalidContent) {
      let o = false, l = "";
      const a = new Yu({
        topNode: e.spec.topNode,
        marks: e.spec.marks,
        // Prosemirror's schemas are executed such that: the last to execute, matches last
        // This means that we can add a catch-all node at the end of the schema to catch any content that we don't know how to handle
        nodes: e.spec.nodes.append({
          __tiptap__private__unknown__catch__all__node: {
            content: "inline*",
            group: "block",
            parseDOM: [
              {
                tag: "*",
                getAttrs: (c) => (o = true, l = typeof c == "string" ? c : c.outerHTML, null)
              }
            ]
          }
        })
      });
      if (t.slice ? Zt.fromSchema(a).parseSlice(Ai(n2), t.parseOptions) : Zt.fromSchema(a).parse(Ai(n2), t.parseOptions), t.errorOnInvalidContent && o)
        throw new Error("[tiptap error]: Invalid HTML content", { cause: new Error(`Invalid element found: ${l}`) });
    }
    const s = Zt.fromSchema(e);
    return t.slice ? s.parseSlice(Ai(n2), t.parseOptions).content : s.parse(Ai(n2), t.parseOptions);
  }
  return Jr("", e, t);
}
function ky(n2, e, t) {
  const r = n2.steps.length - 1;
  if (r < e)
    return;
  const i = n2.steps[r];
  if (!(i instanceof he || i instanceof pe))
    return;
  const s = n2.mapping.maps[r];
  let o = 0;
  s.forEach((l, a, c, u) => {
    o === 0 && (o = u);
  }), n2.setSelection($.near(n2.doc.resolve(o), t));
}
const Cy = (n2) => !("type" in n2), xy = (n2, e, t) => ({ tr: r, dispatch: i, editor: s }) => {
  var o;
  if (i) {
    t = {
      parseOptions: s.options.parseOptions,
      updateSelection: true,
      applyInputRules: false,
      applyPasteRules: false,
      ...t
    };
    let l;
    const a = (g) => {
      s.emit("contentError", {
        editor: s,
        error: g,
        disableCollaboration: () => {
          s.storage.collaboration && (s.storage.collaboration.isDisabled = true);
        }
      });
    }, c = {
      preserveWhitespace: "full",
      ...t.parseOptions
    };
    if (!t.errorOnInvalidContent && !s.options.enableContentCheck && s.options.emitContentError)
      try {
        Jr(e, s.schema, {
          parseOptions: c,
          errorOnInvalidContent: true
        });
      } catch (g) {
        a(g);
      }
    try {
      l = Jr(e, s.schema, {
        parseOptions: c,
        errorOnInvalidContent: (o = t.errorOnInvalidContent) !== null && o !== void 0 ? o : s.options.enableContentCheck
      });
    } catch (g) {
      return a(g), false;
    }
    let { from: u, to: d } = typeof n2 == "number" ? { from: n2, to: n2 } : { from: n2.from, to: n2.to }, f = true, h2 = true;
    if ((Cy(l) ? l : [l]).forEach((g) => {
      g.check(), f = f ? g.isText && g.marks.length === 0 : false, h2 = h2 ? g.isBlock : false;
    }), u === d && h2) {
      const { parent: g } = r.doc.resolve(u);
      g.isTextblock && !g.type.spec.code && !g.childCount && (u -= 1, d += 1);
    }
    let m;
    if (f) {
      if (Array.isArray(e))
        m = e.map((g) => g.text || "").join("");
      else if (e instanceof A) {
        let g = "";
        e.forEach((y) => {
          y.text && (g += y.text);
        }), m = g;
      } else typeof e == "object" && e && e.text ? m = e.text : m = e;
      r.insertText(m, u, d);
    } else
      m = l, r.replaceWith(u, d, m);
    t.updateSelection && ky(r, r.steps.length - 1, -1), t.applyInputRules && r.setMeta("applyInputRules", { from: u, text: m }), t.applyPasteRules && r.setMeta("applyPasteRules", { from: u, text: m });
  }
  return true;
}, Sy = () => ({ state: n2, dispatch: e }) => Mg(n2, e), My = () => ({ state: n2, dispatch: e }) => Ag(n2, e), Ay = () => ({ state: n2, dispatch: e }) => Xd(n2, e), Ey = () => ({ state: n2, dispatch: e }) => tf(n2, e), Ty = () => ({ state: n2, dispatch: e, tr: t }) => {
  try {
    const r = $s(n2.doc, n2.selection.$from.pos, -1);
    return r == null ? false : (t.join(r, 2), e && e(t), true);
  } catch {
    return false;
  }
}, Oy = () => ({ state: n2, dispatch: e, tr: t }) => {
  try {
    const r = $s(n2.doc, n2.selection.$from.pos, 1);
    return r == null ? false : (t.join(r, 2), e && e(t), true);
  } catch {
    return false;
  }
}, Ny = () => ({ state: n2, dispatch: e }) => xg(n2, e), Dy = () => ({ state: n2, dispatch: e }) => Sg(n2, e);
function yf() {
  return typeof navigator < "u" ? /Mac/.test(navigator.platform) : false;
}
function Ly(n2) {
  const e = n2.split(/-(?!$)/);
  let t = e[e.length - 1];
  t === "Space" && (t = " ");
  let r, i, s, o;
  for (let l = 0; l < e.length - 1; l += 1) {
    const a = e[l];
    if (/^(cmd|meta|m)$/i.test(a))
      o = true;
    else if (/^a(lt)?$/i.test(a))
      r = true;
    else if (/^(c|ctrl|control)$/i.test(a))
      i = true;
    else if (/^s(hift)?$/i.test(a))
      s = true;
    else if (/^mod$/i.test(a))
      Qs() || yf() ? o = true : i = true;
    else
      throw new Error(`Unrecognized modifier name: ${a}`);
  }
  return r && (t = `Alt-${t}`), i && (t = `Ctrl-${t}`), o && (t = `Meta-${t}`), s && (t = `Shift-${t}`), t;
}
const Ry = (n2) => ({ editor: e, view: t, tr: r, dispatch: i }) => {
  const s = Ly(n2).split(/-(?!$)/), o = s.find((c) => !["Alt", "Ctrl", "Meta", "Shift"].includes(c)), l = new KeyboardEvent("keydown", {
    key: o === "Space" ? " " : o,
    altKey: s.includes("Alt"),
    ctrlKey: s.includes("Ctrl"),
    metaKey: s.includes("Meta"),
    shiftKey: s.includes("Shift"),
    bubbles: true,
    cancelable: true
  }), a = e.captureTransaction(() => {
    t.someProp("handleKeyDown", (c) => c(t, l));
  });
  return a == null || a.steps.forEach((c) => {
    const u = c.map(r.mapping);
    u && i && r.maybeStep(u);
  }), true;
};
function Gr(n2, e, t = {}) {
  const { from: r, to: i, empty: s } = n2.selection, o = e ? ge(e, n2.schema) : null, l = [];
  n2.doc.nodesBetween(r, i, (d, f) => {
    if (d.isText)
      return;
    const h2 = Math.max(r, f), p2 = Math.min(i, f + d.nodeSize);
    l.push({
      node: d,
      from: h2,
      to: p2
    });
  });
  const a = i - r, c = l.filter((d) => o ? o.name === d.node.type.name : true).filter((d) => is(d.node.attrs, t, { strict: false }));
  return s ? !!c.length : c.reduce((d, f) => d + f.to - f.from, 0) >= a;
}
const Iy = (n2, e = {}) => ({ state: t, dispatch: r }) => {
  const i = ge(n2, t.schema);
  return Gr(t, i, e) ? Eg(t, r) : false;
}, Py = () => ({ state: n2, dispatch: e }) => of(n2, e), By = (n2) => ({ state: e, dispatch: t }) => {
  const r = ge(n2, e.schema);
  return zg(r)(e, t);
}, Hy = () => ({ state: n2, dispatch: e }) => rf(n2, e);
function Zs(n2, e) {
  return e.nodes[n2] ? "node" : e.marks[n2] ? "mark" : null;
}
function Tc(n2, e) {
  const t = typeof e == "string" ? [e] : e;
  return Object.keys(n2).reduce((r, i) => (t.includes(i) || (r[i] = n2[i]), r), {});
}
const Fy = (n2, e) => ({ tr: t, state: r, dispatch: i }) => {
  let s = null, o = null;
  const l = Zs(typeof n2 == "string" ? n2 : n2.name, r.schema);
  return l ? (l === "node" && (s = ge(n2, r.schema)), l === "mark" && (o = ln(n2, r.schema)), i && t.selection.ranges.forEach((a) => {
    r.doc.nodesBetween(a.$from.pos, a.$to.pos, (c, u) => {
      s && s === c.type && t.setNodeMarkup(u, void 0, Tc(c.attrs, e)), o && c.marks.length && c.marks.forEach((d) => {
        o === d.type && t.addMark(u, u + c.nodeSize, o.create(Tc(d.attrs, e)));
      });
    });
  }), true) : false;
}, zy = () => ({ tr: n2, dispatch: e }) => (e && n2.scrollIntoView(), true), Vy = () => ({ tr: n2, dispatch: e }) => {
  if (e) {
    const t = new je(n2.doc);
    n2.setSelection(t);
  }
  return true;
}, $y = () => ({ state: n2, dispatch: e }) => Zd(n2, e), _y = () => ({ state: n2, dispatch: e }) => nf(n2, e), jy = () => ({ state: n2, dispatch: e }) => Dg(n2, e), Wy = () => ({ state: n2, dispatch: e }) => Ig(n2, e), Uy = () => ({ state: n2, dispatch: e }) => Rg(n2, e);
function rl(n2, e, t = {}, r = {}) {
  return Jr(n2, e, {
    slice: false,
    parseOptions: t,
    errorOnInvalidContent: r.errorOnInvalidContent
  });
}
const Ky = (n2, e = false, t = {}, r = {}) => ({ editor: i, tr: s, dispatch: o, commands: l }) => {
  var a, c;
  const { doc: u } = s;
  if (t.preserveWhitespace !== "full") {
    const d = rl(n2, i.schema, t, {
      errorOnInvalidContent: (a = r.errorOnInvalidContent) !== null && a !== void 0 ? a : i.options.enableContentCheck
    });
    return o && s.replaceWith(0, u.content.size, d).setMeta("preventUpdate", !e), true;
  }
  return o && s.setMeta("preventUpdate", !e), l.insertContentAt({ from: 0, to: u.content.size }, n2, {
    parseOptions: t,
    errorOnInvalidContent: (c = r.errorOnInvalidContent) !== null && c !== void 0 ? c : i.options.enableContentCheck
  });
};
function bf(n2, e) {
  const t = ln(e, n2.schema), { from: r, to: i, empty: s } = n2.selection, o = [];
  s ? (n2.storedMarks && o.push(...n2.storedMarks), o.push(...n2.selection.$head.marks())) : n2.doc.nodesBetween(r, i, (a) => {
    o.push(...a.marks);
  });
  const l = o.find((a) => a.type.name === t.name);
  return l ? { ...l.attrs } : {};
}
function qy(n2, e) {
  const t = new Pl(n2);
  return e.forEach((r) => {
    r.steps.forEach((i) => {
      t.step(i);
    });
  }), t;
}
function Jy(n2) {
  for (let e = 0; e < n2.edgeCount; e += 1) {
    const { type: t } = n2.edge(e);
    if (t.isTextblock && !t.hasRequiredAttrs())
      return t;
  }
  return null;
}
function Gy(n2, e, t) {
  const r = [];
  return n2.nodesBetween(e.from, e.to, (i, s) => {
    t(i) && r.push({
      node: i,
      pos: s
    });
  }), r;
}
function vf(n2, e) {
  for (let t = n2.depth; t > 0; t -= 1) {
    const r = n2.node(t);
    if (e(r))
      return {
        pos: t > 0 ? n2.before(t) : 0,
        start: n2.start(t),
        depth: t,
        node: r
      };
  }
}
function na(n2) {
  return (e) => vf(e.$from, n2);
}
function wf(n2, e) {
  const t = {
    from: 0,
    to: n2.content.size
  };
  return ff(n2, t, e);
}
function Yy(n2, e) {
  const t = ge(e, n2.schema), { from: r, to: i } = n2.selection, s = [];
  n2.doc.nodesBetween(r, i, (l) => {
    s.push(l);
  });
  const o = s.reverse().find((l) => l.type.name === t.name);
  return o ? { ...o.attrs } : {};
}
function kf(n2, e) {
  const t = Zs(typeof e == "string" ? e : e.name, n2.schema);
  return t === "node" ? Yy(n2, e) : t === "mark" ? bf(n2, e) : {};
}
function Xy(n2, e = JSON.stringify) {
  const t = {};
  return n2.filter((r) => {
    const i = e(r);
    return Object.prototype.hasOwnProperty.call(t, i) ? false : t[i] = true;
  });
}
function Qy(n2) {
  const e = Xy(n2);
  return e.length === 1 ? e : e.filter((t, r) => !e.filter((s, o) => o !== r).some((s) => t.oldRange.from >= s.oldRange.from && t.oldRange.to <= s.oldRange.to && t.newRange.from >= s.newRange.from && t.newRange.to <= s.newRange.to));
}
function Zy(n2) {
  const { mapping: e, steps: t } = n2, r = [];
  return e.maps.forEach((i, s) => {
    const o = [];
    if (i.ranges.length)
      i.forEach((l, a) => {
        o.push({ from: l, to: a });
      });
    else {
      const { from: l, to: a } = t[s];
      if (l === void 0 || a === void 0)
        return;
      o.push({ from: l, to: a });
    }
    o.forEach(({ from: l, to: a }) => {
      const c = e.slice(s).map(l, -1), u = e.slice(s).map(a), d = e.invert().map(c, -1), f = e.invert().map(u);
      r.push({
        oldRange: {
          from: d,
          to: f
        },
        newRange: {
          from: c,
          to: u
        }
      });
    });
  }), Qy(r);
}
function ra(n2, e, t) {
  const r = [];
  return n2 === e ? t.resolve(n2).marks().forEach((i) => {
    const s = t.resolve(n2), o = ea(s, i.type);
    o && r.push({
      mark: i,
      ...o
    });
  }) : t.nodesBetween(n2, e, (i, s) => {
    !i || (i == null ? void 0 : i.nodeSize) === void 0 || r.push(...i.marks.map((o) => ({
      from: s,
      to: s + i.nodeSize,
      mark: o
    })));
  }), r;
}
function Vi(n2, e, t) {
  return Object.fromEntries(Object.entries(t).filter(([r]) => {
    const i = n2.find((s) => s.type === e && s.name === r);
    return i ? i.attribute.keepOnSplit : false;
  }));
}
function il(n2, e, t = {}) {
  const { empty: r, ranges: i } = n2.selection, s = e ? ln(e, n2.schema) : null;
  if (r)
    return !!(n2.storedMarks || n2.selection.$from.marks()).filter((d) => s ? s.name === d.type.name : true).find((d) => is(d.attrs, t, { strict: false }));
  let o = 0;
  const l = [];
  if (i.forEach(({ $from: d, $to: f }) => {
    const h2 = d.pos, p2 = f.pos;
    n2.doc.nodesBetween(h2, p2, (m, g) => {
      if (!m.isText && !m.marks.length)
        return;
      const y = Math.max(h2, g), w = Math.min(p2, g + m.nodeSize), C = w - y;
      o += C, l.push(...m.marks.map((b) => ({
        mark: b,
        from: y,
        to: w
      })));
    });
  }), o === 0)
    return false;
  const a = l.filter((d) => s ? s.name === d.mark.type.name : true).filter((d) => is(d.mark.attrs, t, { strict: false })).reduce((d, f) => d + f.to - f.from, 0), c = l.filter((d) => s ? d.mark.type !== s && d.mark.type.excludes(s) : true).reduce((d, f) => d + f.to - f.from, 0);
  return (a > 0 ? a + c : a) >= o;
}
function e0(n2, e, t = {}) {
  if (!e)
    return Gr(n2, null, t) || il(n2, null, t);
  const r = Zs(e, n2.schema);
  return r === "node" ? Gr(n2, e, t) : r === "mark" ? il(n2, e, t) : false;
}
function Oc(n2, e) {
  const { nodeExtensions: t } = Gs(e), r = t.find((o) => o.name === n2);
  if (!r)
    return false;
  const i = {
    name: r.name,
    options: r.options,
    storage: r.storage
  }, s = W(L(r, "group", i));
  return typeof s != "string" ? false : s.split(" ").includes("list");
}
function eo(n2, { checkChildren: e = true, ignoreWhitespace: t = false } = {}) {
  var r;
  if (t) {
    if (n2.type.name === "hardBreak")
      return true;
    if (n2.isText)
      return /^\s*$/m.test((r = n2.text) !== null && r !== void 0 ? r : "");
  }
  if (n2.isText)
    return !n2.text;
  if (n2.isAtom || n2.isLeaf)
    return false;
  if (n2.content.childCount === 0)
    return true;
  if (e) {
    let i = true;
    return n2.content.forEach((s) => {
      i !== false && (eo(s, { ignoreWhitespace: t, checkChildren: e }) || (i = false));
    }), i;
  }
  return false;
}
function t0(n2) {
  return n2 instanceof B;
}
function Cf(n2, e, t) {
  const i = n2.state.doc.content.size, s = Lt(e, 0, i), o = Lt(t, 0, i), l = n2.coordsAtPos(s), a = n2.coordsAtPos(o, -1), c = Math.min(l.top, a.top), u = Math.max(l.bottom, a.bottom), d = Math.min(l.left, a.left), f = Math.max(l.right, a.right), h2 = f - d, p2 = u - c, y = {
    top: c,
    bottom: u,
    left: d,
    right: f,
    width: h2,
    height: p2,
    x: d,
    y: c
  };
  return {
    ...y,
    toJSON: () => y
  };
}
function n0(n2, e, t) {
  var r;
  const { selection: i } = e;
  let s = null;
  if (ta(i) && (s = i.$cursor), s) {
    const l = (r = n2.storedMarks) !== null && r !== void 0 ? r : s.marks();
    return !!t.isInSet(l) || !l.some((a) => a.type.excludes(t));
  }
  const { ranges: o } = i;
  return o.some(({ $from: l, $to: a }) => {
    let c = l.depth === 0 ? n2.doc.inlineContent && n2.doc.type.allowsMarkType(t) : false;
    return n2.doc.nodesBetween(l.pos, a.pos, (u, d, f) => {
      if (c)
        return false;
      if (u.isInline) {
        const h2 = !f || f.type.allowsMarkType(t), p2 = !!t.isInSet(u.marks) || !u.marks.some((m) => m.type.excludes(t));
        c = h2 && p2;
      }
      return !c;
    }), c;
  });
}
const r0 = (n2, e = {}) => ({ tr: t, state: r, dispatch: i }) => {
  const { selection: s } = t, { empty: o, ranges: l } = s, a = ln(n2, r.schema);
  if (i)
    if (o) {
      const c = bf(r, a);
      t.addStoredMark(a.create({
        ...c,
        ...e
      }));
    } else
      l.forEach((c) => {
        const u = c.$from.pos, d = c.$to.pos;
        r.doc.nodesBetween(u, d, (f, h2) => {
          const p2 = Math.max(h2, u), m = Math.min(h2 + f.nodeSize, d);
          f.marks.find((y) => y.type === a) ? f.marks.forEach((y) => {
            a === y.type && t.addMark(p2, m, a.create({
              ...y.attrs,
              ...e
            }));
          }) : t.addMark(p2, m, a.create(e));
        });
      });
  return n0(r, t, a);
}, i0 = (n2, e) => ({ tr: t }) => (t.setMeta(n2, e), true), s0 = (n2, e = {}) => ({ state: t, dispatch: r, chain: i }) => {
  const s = ge(n2, t.schema);
  let o;
  return t.selection.$anchor.sameParent(t.selection.$head) && (o = t.selection.$anchor.parent.attrs), s.isTextblock ? i().command(({ commands: l }) => xc(s, { ...o, ...e })(t) ? true : l.clearNodes()).command(({ state: l }) => xc(s, { ...o, ...e })(l, r)).run() : (console.warn('[tiptap warn]: Currently "setNode()" only supports text block nodes.'), false);
}, o0 = (n2) => ({ tr: e, dispatch: t }) => {
  if (t) {
    const { doc: r } = e, i = Lt(n2, 0, r.content.size), s = B.create(r, i);
    e.setSelection(s);
  }
  return true;
}, l0 = (n2) => ({ tr: e, dispatch: t }) => {
  if (t) {
    const { doc: r } = e, { from: i, to: s } = typeof n2 == "number" ? { from: n2, to: n2 } : n2, o = F.atStart(r).from, l = F.atEnd(r).to, a = Lt(i, o, l), c = Lt(s, o, l), u = F.create(r, a, c);
    e.setSelection(u);
  }
  return true;
}, a0 = (n2) => ({ state: e, dispatch: t }) => {
  const r = ge(n2, e.schema);
  return _g(r)(e, t);
};
function Nc(n2, e) {
  const t = n2.storedMarks || n2.selection.$to.parentOffset && n2.selection.$from.marks();
  if (t) {
    const r = t.filter((i) => e == null ? void 0 : e.includes(i.type.name));
    n2.tr.ensureMarks(r);
  }
}
const c0 = ({ keepMarks: n2 = true } = {}) => ({ tr: e, state: t, dispatch: r, editor: i }) => {
  const { selection: s, doc: o } = e, { $from: l, $to: a } = s, c = i.extensionManager.attributes, u = Vi(c, l.node().type.name, l.node().attrs);
  if (s instanceof B && s.node.isBlock)
    return !l.parentOffset || !Rt(o, l.pos) ? false : (r && (n2 && Nc(t, i.extensionManager.splittableMarks), e.split(l.pos).scrollIntoView()), true);
  if (!l.parent.isBlock)
    return false;
  const d = a.parentOffset === a.parent.content.size, f = l.depth === 0 ? void 0 : Jy(l.node(-1).contentMatchAt(l.indexAfter(-1)));
  let h2 = d && f ? [
    {
      type: f,
      attrs: u
    }
  ] : void 0, p2 = Rt(e.doc, e.mapping.map(l.pos), 1, h2);
  if (!h2 && !p2 && Rt(e.doc, e.mapping.map(l.pos), 1, f ? [{ type: f }] : void 0) && (p2 = true, h2 = f ? [
    {
      type: f,
      attrs: u
    }
  ] : void 0), r) {
    if (p2 && (s instanceof F && e.deleteSelection(), e.split(e.mapping.map(l.pos), 1, h2), f && !d && !l.parentOffset && l.parent.type !== f)) {
      const m = e.mapping.map(l.before()), g = e.doc.resolve(m);
      l.node(-1).canReplaceWith(g.index(), g.index() + 1, f) && e.setNodeMarkup(e.mapping.map(l.before()), f);
    }
    n2 && Nc(t, i.extensionManager.splittableMarks), e.scrollIntoView();
  }
  return p2;
}, u0 = (n2, e = {}) => ({ tr: t, state: r, dispatch: i, editor: s }) => {
  var o;
  const l = ge(n2, r.schema), { $from: a, $to: c } = r.selection, u = r.selection.node;
  if (u && u.isBlock || a.depth < 2 || !a.sameParent(c))
    return false;
  const d = a.node(-1);
  if (d.type !== l)
    return false;
  const f = s.extensionManager.attributes;
  if (a.parent.content.size === 0 && a.node(-1).childCount === a.indexAfter(-1)) {
    if (a.depth === 2 || a.node(-3).type !== l || a.index(-2) !== a.node(-2).childCount - 1)
      return false;
    if (i) {
      let y = A.empty;
      const w = a.index(-1) ? 1 : a.index(-2) ? 2 : 3;
      for (let M = a.depth - w; M >= a.depth - 3; M -= 1)
        y = A.from(a.node(M).copy(y));
      const C = a.indexAfter(-1) < a.node(-2).childCount ? 1 : a.indexAfter(-2) < a.node(-3).childCount ? 2 : 3, b = {
        ...Vi(f, a.node().type.name, a.node().attrs),
        ...e
      }, S = ((o = l.contentMatch.defaultType) === null || o === void 0 ? void 0 : o.createAndFill(b)) || void 0;
      y = y.append(A.from(l.createAndFill(null, S) || void 0));
      const k = a.before(a.depth - (w - 1));
      t.replace(k, a.after(-C), new O(y, 4 - w, 0));
      let T = -1;
      t.doc.nodesBetween(k, t.doc.content.size, (M, I) => {
        if (T > -1)
          return false;
        M.isTextblock && M.content.size === 0 && (T = I + 1);
      }), T > -1 && t.setSelection(F.near(t.doc.resolve(T))), t.scrollIntoView();
    }
    return true;
  }
  const h2 = c.pos === a.end() ? d.contentMatchAt(0).defaultType : null, p2 = {
    ...Vi(f, d.type.name, d.attrs),
    ...e
  }, m = {
    ...Vi(f, a.node().type.name, a.node().attrs),
    ...e
  };
  t.delete(a.pos, c.pos);
  const g = h2 ? [
    { type: l, attrs: p2 },
    { type: h2, attrs: m }
  ] : [{ type: l, attrs: p2 }];
  if (!Rt(t.doc, a.pos, 2))
    return false;
  if (i) {
    const { selection: y, storedMarks: w } = r, { splittableMarks: C } = s.extensionManager, b = w || y.$to.parentOffset && y.$from.marks();
    if (t.split(a.pos, 2, g).scrollIntoView(), !b || !i)
      return true;
    const S = b.filter((k) => C.includes(k.type.name));
    t.ensureMarks(S);
  }
  return true;
}, So = (n2, e) => {
  const t = na((o) => o.type === e)(n2.selection);
  if (!t)
    return true;
  const r = n2.doc.resolve(Math.max(0, t.pos - 1)).before(t.depth);
  if (r === void 0)
    return true;
  const i = n2.doc.nodeAt(r);
  return t.node.type === (i == null ? void 0 : i.type) && sn(n2.doc, t.pos) && n2.join(t.pos), true;
}, Mo = (n2, e) => {
  const t = na((o) => o.type === e)(n2.selection);
  if (!t)
    return true;
  const r = n2.doc.resolve(t.start).after(t.depth);
  if (r === void 0)
    return true;
  const i = n2.doc.nodeAt(r);
  return t.node.type === (i == null ? void 0 : i.type) && sn(n2.doc, r) && n2.join(r), true;
}, d0 = (n2, e, t, r = {}) => ({ editor: i, tr: s, state: o, dispatch: l, chain: a, commands: c, can: u }) => {
  const { extensions: d, splittableMarks: f } = i.extensionManager, h2 = ge(n2, o.schema), p2 = ge(e, o.schema), { selection: m, storedMarks: g } = o, { $from: y, $to: w } = m, C = y.blockRange(w), b = g || m.$to.parentOffset && m.$from.marks();
  if (!C)
    return false;
  const S = na((k) => Oc(k.type.name, d))(m);
  if (C.depth >= 1 && S && C.depth - S.depth <= 1) {
    if (S.node.type === h2)
      return c.liftListItem(p2);
    if (Oc(S.node.type.name, d) && h2.validContent(S.node.content) && l)
      return a().command(() => (s.setNodeMarkup(S.pos, h2), true)).command(() => So(s, h2)).command(() => Mo(s, h2)).run();
  }
  return !t || !b || !l ? a().command(() => u().wrapInList(h2, r) ? true : c.clearNodes()).wrapInList(h2, r).command(() => So(s, h2)).command(() => Mo(s, h2)).run() : a().command(() => {
    const k = u().wrapInList(h2, r), T = b.filter((M) => f.includes(M.type.name));
    return s.ensureMarks(T), k ? true : c.clearNodes();
  }).wrapInList(h2, r).command(() => So(s, h2)).command(() => Mo(s, h2)).run();
}, f0 = (n2, e = {}, t = {}) => ({ state: r, commands: i }) => {
  const { extendEmptyMarkRange: s = false } = t, o = ln(n2, r.schema);
  return il(r, o, e) ? i.unsetMark(o, { extendEmptyMarkRange: s }) : i.setMark(o, e);
}, h0 = (n2, e, t = {}) => ({ state: r, commands: i }) => {
  const s = ge(n2, r.schema), o = ge(e, r.schema), l = Gr(r, s, t);
  let a;
  return r.selection.$anchor.sameParent(r.selection.$head) && (a = r.selection.$anchor.parent.attrs), l ? i.setNode(o, a) : i.setNode(s, { ...a, ...t });
}, p0 = (n2, e = {}) => ({ state: t, commands: r }) => {
  const i = ge(n2, t.schema);
  return Gr(t, i, e) ? r.lift(i) : r.wrapIn(i, e);
}, m0 = () => ({ state: n2, dispatch: e }) => {
  const t = n2.plugins;
  for (let r = 0; r < t.length; r += 1) {
    const i = t[r];
    let s;
    if (i.spec.isInputRules && (s = i.getState(n2))) {
      if (e) {
        const o = n2.tr, l = s.transform;
        for (let a = l.steps.length - 1; a >= 0; a -= 1)
          o.step(l.steps[a].invert(l.docs[a]));
        if (s.text) {
          const a = o.doc.resolve(s.from).marks();
          o.replaceWith(s.from, s.to, n2.schema.text(s.text, a));
        } else
          o.delete(s.from, s.to);
      }
      return true;
    }
  }
  return false;
}, g0 = () => ({ tr: n2, dispatch: e }) => {
  const { selection: t } = n2, { empty: r, ranges: i } = t;
  return r || e && i.forEach((s) => {
    n2.removeMark(s.$from.pos, s.$to.pos);
  }), true;
}, y0 = (n2, e = {}) => ({ tr: t, state: r, dispatch: i }) => {
  var s;
  const { extendEmptyMarkRange: o = false } = e, { selection: l } = t, a = ln(n2, r.schema), { $from: c, empty: u, ranges: d } = l;
  if (!i)
    return true;
  if (u && o) {
    let { from: f, to: h2 } = l;
    const p2 = (s = c.marks().find((g) => g.type === a)) === null || s === void 0 ? void 0 : s.attrs, m = ea(c, a, p2);
    m && (f = m.from, h2 = m.to), t.removeMark(f, h2, a);
  } else
    d.forEach((f) => {
      t.removeMark(f.$from.pos, f.$to.pos, a);
    });
  return t.removeStoredMark(a), true;
}, b0 = (n2, e = {}) => ({ tr: t, state: r, dispatch: i }) => {
  let s = null, o = null;
  const l = Zs(typeof n2 == "string" ? n2 : n2.name, r.schema);
  return l ? (l === "node" && (s = ge(n2, r.schema)), l === "mark" && (o = ln(n2, r.schema)), i && t.selection.ranges.forEach((a) => {
    const c = a.$from.pos, u = a.$to.pos;
    let d, f, h2, p2;
    t.selection.empty ? r.doc.nodesBetween(c, u, (m, g) => {
      s && s === m.type && (h2 = Math.max(g, c), p2 = Math.min(g + m.nodeSize, u), d = g, f = m);
    }) : r.doc.nodesBetween(c, u, (m, g) => {
      g < c && s && s === m.type && (h2 = Math.max(g, c), p2 = Math.min(g + m.nodeSize, u), d = g, f = m), g >= c && g <= u && (s && s === m.type && t.setNodeMarkup(g, void 0, {
        ...m.attrs,
        ...e
      }), o && m.marks.length && m.marks.forEach((y) => {
        if (o === y.type) {
          const w = Math.max(g, c), C = Math.min(g + m.nodeSize, u);
          t.addMark(w, C, o.create({
            ...y.attrs,
            ...e
          }));
        }
      }));
    }), f && (d !== void 0 && t.setNodeMarkup(d, void 0, {
      ...f.attrs,
      ...e
    }), o && f.marks.length && f.marks.forEach((m) => {
      o === m.type && t.addMark(h2, p2, o.create({
        ...m.attrs,
        ...e
      }));
    }));
  }), true) : false;
}, v0 = (n2, e = {}) => ({ state: t, dispatch: r }) => {
  const i = ge(n2, t.schema);
  return Pg(i, e)(t, r);
}, w0 = (n2, e = {}) => ({ state: t, dispatch: r }) => {
  const i = ge(n2, t.schema);
  return Bg(i, e)(t, r);
};
var k0 = /* @__PURE__ */ Object.freeze({
  __proto__: null,
  blur: iy,
  clearContent: sy,
  clearNodes: oy,
  command: ly,
  createParagraphNear: ay,
  cut: cy,
  deleteCurrentNode: uy,
  deleteNode: dy,
  deleteRange: fy,
  deleteSelection: hy,
  enter: py,
  exitCode: my,
  extendMarkRange: gy,
  first: yy,
  focus: by,
  forEach: vy,
  insertContent: wy,
  insertContentAt: xy,
  joinBackward: Ay,
  joinDown: My,
  joinForward: Ey,
  joinItemBackward: Ty,
  joinItemForward: Oy,
  joinTextblockBackward: Ny,
  joinTextblockForward: Dy,
  joinUp: Sy,
  keyboardShortcut: Ry,
  lift: Iy,
  liftEmptyBlock: Py,
  liftListItem: By,
  newlineInCode: Hy,
  resetAttributes: Fy,
  scrollIntoView: zy,
  selectAll: Vy,
  selectNodeBackward: $y,
  selectNodeForward: _y,
  selectParentNode: jy,
  selectTextblockEnd: Wy,
  selectTextblockStart: Uy,
  setContent: Ky,
  setMark: r0,
  setMeta: i0,
  setNode: s0,
  setNodeSelection: o0,
  setTextSelection: l0,
  sinkListItem: a0,
  splitBlock: c0,
  splitListItem: u0,
  toggleList: d0,
  toggleMark: f0,
  toggleNode: h0,
  toggleWrap: p0,
  undoInputRule: m0,
  unsetAllMarks: g0,
  unsetMark: y0,
  updateAttributes: b0,
  wrapIn: v0,
  wrapInList: w0
});
const C0 = fe.create({
  name: "commands",
  addCommands() {
    return {
      ...k0
    };
  }
}), x0 = fe.create({
  name: "drop",
  addProseMirrorPlugins() {
    return [
      new le({
        key: new ue("tiptapDrop"),
        props: {
          handleDrop: (n2, e, t, r) => {
            this.editor.emit("drop", {
              editor: this.editor,
              event: e,
              slice: t,
              moved: r
            });
          }
        }
      })
    ];
  }
}), S0 = fe.create({
  name: "editable",
  addProseMirrorPlugins() {
    return [
      new le({
        key: new ue("editable"),
        props: {
          editable: () => this.editor.options.editable
        }
      })
    ];
  }
}), M0 = new ue("focusEvents"), A0 = fe.create({
  name: "focusEvents",
  addProseMirrorPlugins() {
    const { editor: n2 } = this;
    return [
      new le({
        key: M0,
        props: {
          handleDOMEvents: {
            focus: (e, t) => {
              n2.isFocused = true;
              const r = n2.state.tr.setMeta("focus", { event: t }).setMeta("addToHistory", false);
              return e.dispatch(r), false;
            },
            blur: (e, t) => {
              n2.isFocused = false;
              const r = n2.state.tr.setMeta("blur", { event: t }).setMeta("addToHistory", false);
              return e.dispatch(r), false;
            }
          }
        }
      })
    ];
  }
}), E0 = fe.create({
  name: "keymap",
  addKeyboardShortcuts() {
    const n2 = () => this.editor.commands.first(({ commands: o }) => [
      () => o.undoInputRule(),
      // maybe convert first text block node to default node
      () => o.command(({ tr: l }) => {
        const { selection: a, doc: c } = l, { empty: u, $anchor: d } = a, { pos: f, parent: h2 } = d, p2 = d.parent.isTextblock && f > 0 ? l.doc.resolve(f - 1) : d, m = p2.parent.type.spec.isolating, g = d.pos - d.parentOffset, y = m && p2.parent.childCount === 1 ? g === d.pos : $.atStart(c).from === f;
        return !u || !h2.type.isTextblock || h2.textContent.length || !y || y && d.parent.type.name === "paragraph" ? false : o.clearNodes();
      }),
      () => o.deleteSelection(),
      () => o.joinBackward(),
      () => o.selectNodeBackward()
    ]), e = () => this.editor.commands.first(({ commands: o }) => [
      () => o.deleteSelection(),
      () => o.deleteCurrentNode(),
      () => o.joinForward(),
      () => o.selectNodeForward()
    ]), r = {
      Enter: () => this.editor.commands.first(({ commands: o }) => [
        () => o.newlineInCode(),
        () => o.createParagraphNear(),
        () => o.liftEmptyBlock(),
        () => o.splitBlock()
      ]),
      "Mod-Enter": () => this.editor.commands.exitCode(),
      Backspace: n2,
      "Mod-Backspace": n2,
      "Shift-Backspace": n2,
      Delete: e,
      "Mod-Delete": e,
      "Mod-a": () => this.editor.commands.selectAll()
    }, i = {
      ...r
    }, s = {
      ...r,
      "Ctrl-h": n2,
      "Alt-Backspace": n2,
      "Ctrl-d": e,
      "Ctrl-Alt-Backspace": e,
      "Alt-Delete": e,
      "Alt-d": e,
      "Ctrl-a": () => this.editor.commands.selectTextblockStart(),
      "Ctrl-e": () => this.editor.commands.selectTextblockEnd()
    };
    return Qs() || yf() ? s : i;
  },
  addProseMirrorPlugins() {
    return [
      // With this plugin we check if the whole document was selected and deleted.
      // In this case we will additionally call `clearNodes()` to convert e.g. a heading
      // to a paragraph if necessary.
      // This is an alternative to ProseMirror's `AllSelection`, which doesn’t work well
      // with many other commands.
      new le({
        key: new ue("clearDocument"),
        appendTransaction: (n2, e, t) => {
          if (n2.some((m) => m.getMeta("composition")))
            return;
          const r = n2.some((m) => m.docChanged) && !e.doc.eq(t.doc), i = n2.some((m) => m.getMeta("preventClearDocument"));
          if (!r || i)
            return;
          const { empty: s, from: o, to: l } = e.selection, a = $.atStart(e.doc).from, c = $.atEnd(e.doc).to;
          if (s || !(o === a && l === c) || !eo(t.doc))
            return;
          const f = t.tr, h2 = qs({
            state: t,
            transaction: f
          }), { commands: p2 } = new Js({
            editor: this.editor,
            state: h2
          });
          if (p2.clearNodes(), !!f.steps.length)
            return f;
        }
      })
    ];
  }
}), T0 = fe.create({
  name: "paste",
  addProseMirrorPlugins() {
    return [
      new le({
        key: new ue("tiptapPaste"),
        props: {
          handlePaste: (n2, e, t) => {
            this.editor.emit("paste", {
              editor: this.editor,
              event: e,
              slice: t
            });
          }
        }
      })
    ];
  }
}), O0 = fe.create({
  name: "tabindex",
  addProseMirrorPlugins() {
    return [
      new le({
        key: new ue("tabindex"),
        props: {
          attributes: () => this.editor.isEditable ? { tabindex: "0" } : {}
        }
      })
    ];
  }
});
class mn {
  get name() {
    return this.node.type.name;
  }
  constructor(e, t, r = false, i = null) {
    this.currentNode = null, this.actualDepth = null, this.isBlock = r, this.resolvedPos = e, this.editor = t, this.currentNode = i;
  }
  get node() {
    return this.currentNode || this.resolvedPos.node();
  }
  get element() {
    return this.editor.view.domAtPos(this.pos).node;
  }
  get depth() {
    var e;
    return (e = this.actualDepth) !== null && e !== void 0 ? e : this.resolvedPos.depth;
  }
  get pos() {
    return this.resolvedPos.pos;
  }
  get content() {
    return this.node.content;
  }
  set content(e) {
    let t = this.from, r = this.to;
    if (this.isBlock) {
      if (this.content.size === 0) {
        console.error(`You can’t set content on a block node. Tried to set content on ${this.name} at ${this.pos}`);
        return;
      }
      t = this.from + 1, r = this.to - 1;
    }
    this.editor.commands.insertContentAt({ from: t, to: r }, e);
  }
  get attributes() {
    return this.node.attrs;
  }
  get textContent() {
    return this.node.textContent;
  }
  get size() {
    return this.node.nodeSize;
  }
  get from() {
    return this.isBlock ? this.pos : this.resolvedPos.start(this.resolvedPos.depth);
  }
  get range() {
    return {
      from: this.from,
      to: this.to
    };
  }
  get to() {
    return this.isBlock ? this.pos + this.size : this.resolvedPos.end(this.resolvedPos.depth) + (this.node.isText ? 0 : 1);
  }
  get parent() {
    if (this.depth === 0)
      return null;
    const e = this.resolvedPos.start(this.resolvedPos.depth - 1), t = this.resolvedPos.doc.resolve(e);
    return new mn(t, this.editor);
  }
  get before() {
    let e = this.resolvedPos.doc.resolve(this.from - (this.isBlock ? 1 : 2));
    return e.depth !== this.depth && (e = this.resolvedPos.doc.resolve(this.from - 3)), new mn(e, this.editor);
  }
  get after() {
    let e = this.resolvedPos.doc.resolve(this.to + (this.isBlock ? 2 : 1));
    return e.depth !== this.depth && (e = this.resolvedPos.doc.resolve(this.to + 3)), new mn(e, this.editor);
  }
  get children() {
    const e = [];
    return this.node.content.forEach((t, r) => {
      const i = t.isBlock && !t.isTextblock, s = t.isAtom && !t.isText, o = this.pos + r + (s ? 0 : 1), l = this.resolvedPos.doc.resolve(o);
      if (!i && l.depth <= this.depth)
        return;
      const a = new mn(l, this.editor, i, i ? t : null);
      i && (a.actualDepth = this.depth + 1), e.push(new mn(l, this.editor, i, i ? t : null));
    }), e;
  }
  get firstChild() {
    return this.children[0] || null;
  }
  get lastChild() {
    const e = this.children;
    return e[e.length - 1] || null;
  }
  closest(e, t = {}) {
    let r = null, i = this.parent;
    for (; i && !r; ) {
      if (i.node.type.name === e)
        if (Object.keys(t).length > 0) {
          const s = i.node.attrs, o = Object.keys(t);
          for (let l = 0; l < o.length; l += 1) {
            const a = o[l];
            if (s[a] !== t[a])
              break;
          }
        } else
          r = i;
      i = i.parent;
    }
    return r;
  }
  querySelector(e, t = {}) {
    return this.querySelectorAll(e, t, true)[0] || null;
  }
  querySelectorAll(e, t = {}, r = false) {
    let i = [];
    if (!this.children || this.children.length === 0)
      return i;
    const s = Object.keys(t);
    return this.children.forEach((o) => {
      r && i.length > 0 || (o.node.type.name === e && s.every((a) => t[a] === o.node.attrs[a]) && i.push(o), !(r && i.length > 0) && (i = i.concat(o.querySelectorAll(e, t, r))));
    }), i;
  }
  setAttribute(e) {
    const { tr: t } = this.editor.state;
    t.setNodeMarkup(this.from, void 0, {
      ...this.node.attrs,
      ...e
    }), this.editor.view.dispatch(t);
  }
}
const N0 = `.ProseMirror {
  position: relative;
}

.ProseMirror {
  word-wrap: break-word;
  white-space: pre-wrap;
  white-space: break-spaces;
  -webkit-font-variant-ligatures: none;
  font-variant-ligatures: none;
  font-feature-settings: "liga" 0; /* the above doesn't seem to work in Edge */
}

.ProseMirror [contenteditable="false"] {
  white-space: normal;
}

.ProseMirror [contenteditable="false"] [contenteditable="true"] {
  white-space: pre-wrap;
}

.ProseMirror pre {
  white-space: pre-wrap;
}

img.ProseMirror-separator {
  display: inline !important;
  border: none !important;
  margin: 0 !important;
  width: 0 !important;
  height: 0 !important;
}

.ProseMirror-gapcursor {
  display: none;
  pointer-events: none;
  position: absolute;
  margin: 0;
}

.ProseMirror-gapcursor:after {
  content: "";
  display: block;
  position: absolute;
  top: -2px;
  width: 20px;
  border-top: 1px solid black;
  animation: ProseMirror-cursor-blink 1.1s steps(2, start) infinite;
}

@keyframes ProseMirror-cursor-blink {
  to {
    visibility: hidden;
  }
}

.ProseMirror-hideselection *::selection {
  background: transparent;
}

.ProseMirror-hideselection *::-moz-selection {
  background: transparent;
}

.ProseMirror-hideselection * {
  caret-color: transparent;
}

.ProseMirror-focused .ProseMirror-gapcursor {
  display: block;
}

.tippy-box[data-animation=fade][data-state=hidden] {
  opacity: 0
}`;
function D0(n2, e, t) {
  const r = document.querySelector("style[data-tiptap-style]");
  if (r !== null)
    return r;
  const i = document.createElement("style");
  return e && i.setAttribute("nonce", e), i.setAttribute("data-tiptap-style", ""), i.innerHTML = n2, document.getElementsByTagName("head")[0].appendChild(i), i;
}
let L0 = class extends jg {
  constructor(e = {}) {
    super(), this.isFocused = false, this.isInitialized = false, this.extensionStorage = {}, this.options = {
      element: document.createElement("div"),
      content: "",
      injectCSS: true,
      injectNonce: void 0,
      extensions: [],
      autofocus: false,
      editable: true,
      editorProps: {},
      parseOptions: {},
      coreExtensionOptions: {},
      enableInputRules: true,
      enablePasteRules: true,
      enableCoreExtensions: true,
      enableContentCheck: false,
      emitContentError: false,
      onBeforeCreate: () => null,
      onCreate: () => null,
      onUpdate: () => null,
      onSelectionUpdate: () => null,
      onTransaction: () => null,
      onFocus: () => null,
      onBlur: () => null,
      onDestroy: () => null,
      onContentError: ({ error: t }) => {
        throw t;
      },
      onPaste: () => null,
      onDrop: () => null
    }, this.isCapturingTransaction = false, this.capturedTransaction = null, this.setOptions(e), this.createExtensionManager(), this.createCommandManager(), this.createSchema(), this.on("beforeCreate", this.options.onBeforeCreate), this.emit("beforeCreate", { editor: this }), this.on("contentError", this.options.onContentError), this.createView(), this.injectCSS(), this.on("create", this.options.onCreate), this.on("update", this.options.onUpdate), this.on("selectionUpdate", this.options.onSelectionUpdate), this.on("transaction", this.options.onTransaction), this.on("focus", this.options.onFocus), this.on("blur", this.options.onBlur), this.on("destroy", this.options.onDestroy), this.on("drop", ({ event: t, slice: r, moved: i }) => this.options.onDrop(t, r, i)), this.on("paste", ({ event: t, slice: r }) => this.options.onPaste(t, r)), window.setTimeout(() => {
      this.isDestroyed || (this.commands.focus(this.options.autofocus), this.emit("create", { editor: this }), this.isInitialized = true);
    }, 0);
  }
  /**
   * Returns the editor storage.
   */
  get storage() {
    return this.extensionStorage;
  }
  /**
   * An object of all registered commands.
   */
  get commands() {
    return this.commandManager.commands;
  }
  /**
   * Create a command chain to call multiple commands at once.
   */
  chain() {
    return this.commandManager.chain();
  }
  /**
   * Check if a command or a command chain can be executed. Without executing it.
   */
  can() {
    return this.commandManager.can();
  }
  /**
   * Inject CSS styles.
   */
  injectCSS() {
    this.options.injectCSS && document && (this.css = D0(N0, this.options.injectNonce));
  }
  /**
   * Update editor options.
   *
   * @param options A list of options
   */
  setOptions(e = {}) {
    this.options = {
      ...this.options,
      ...e
    }, !(!this.view || !this.state || this.isDestroyed) && (this.options.editorProps && this.view.setProps(this.options.editorProps), this.view.updateState(this.state));
  }
  /**
   * Update editable state of the editor.
   */
  setEditable(e, t = true) {
    this.setOptions({ editable: e }), t && this.emit("update", { editor: this, transaction: this.state.tr });
  }
  /**
   * Returns whether the editor is editable.
   */
  get isEditable() {
    return this.options.editable && this.view && this.view.editable;
  }
  /**
   * Returns the editor state.
   */
  get state() {
    return this.view.state;
  }
  /**
   * Register a ProseMirror plugin.
   *
   * @param plugin A ProseMirror plugin
   * @param handlePlugins Control how to merge the plugin into the existing plugins.
   * @returns The new editor state
   */
  registerPlugin(e, t) {
    const r = uf(t) ? t(e, [...this.state.plugins]) : [...this.state.plugins, e], i = this.state.reconfigure({ plugins: r });
    return this.view.updateState(i), i;
  }
  /**
   * Unregister a ProseMirror plugin.
   *
   * @param nameOrPluginKeyToRemove The plugins name
   * @returns The new editor state or undefined if the editor is destroyed
   */
  unregisterPlugin(e) {
    if (this.isDestroyed)
      return;
    const t = this.state.plugins;
    let r = t;
    if ([].concat(e).forEach((s) => {
      const o = typeof s == "string" ? `${s}$` : s.key;
      r = r.filter((l) => !l.key.startsWith(o));
    }), t.length === r.length)
      return;
    const i = this.state.reconfigure({
      plugins: r
    });
    return this.view.updateState(i), i;
  }
  /**
   * Creates an extension manager.
   */
  createExtensionManager() {
    var e, t;
    const i = [...this.options.enableCoreExtensions ? [
      S0,
      ry.configure({
        blockSeparator: (t = (e = this.options.coreExtensionOptions) === null || e === void 0 ? void 0 : e.clipboardTextSerializer) === null || t === void 0 ? void 0 : t.blockSeparator
      }),
      C0,
      A0,
      E0,
      O0,
      x0,
      T0
    ].filter((s) => typeof this.options.enableCoreExtensions == "object" ? this.options.enableCoreExtensions[s.name] !== false : true) : [], ...this.options.extensions].filter((s) => ["extension", "node", "mark"].includes(s == null ? void 0 : s.type));
    this.extensionManager = new Qn(i, this);
  }
  /**
   * Creates an command manager.
   */
  createCommandManager() {
    this.commandManager = new Js({
      editor: this
    });
  }
  /**
   * Creates a ProseMirror schema.
   */
  createSchema() {
    this.schema = this.extensionManager.schema;
  }
  /**
   * Creates a ProseMirror view.
   */
  createView() {
    var e;
    let t;
    try {
      t = rl(this.options.content, this.schema, this.options.parseOptions, { errorOnInvalidContent: this.options.enableContentCheck });
    } catch (o) {
      if (!(o instanceof Error) || !["[tiptap error]: Invalid JSON content", "[tiptap error]: Invalid HTML content"].includes(o.message))
        throw o;
      this.emit("contentError", {
        editor: this,
        error: o,
        disableCollaboration: () => {
          this.storage.collaboration && (this.storage.collaboration.isDisabled = true), this.options.extensions = this.options.extensions.filter((l) => l.name !== "collaboration"), this.createExtensionManager();
        }
      }), t = rl(this.options.content, this.schema, this.options.parseOptions, { errorOnInvalidContent: false });
    }
    const r = pf(t, this.options.autofocus);
    this.view = new Jd(this.options.element, {
      ...this.options.editorProps,
      attributes: {
        // add `role="textbox"` to the editor element
        role: "textbox",
        ...(e = this.options.editorProps) === null || e === void 0 ? void 0 : e.attributes
      },
      dispatchTransaction: this.dispatchTransaction.bind(this),
      state: Xn.create({
        doc: t,
        selection: r || void 0
      })
    });
    const i = this.state.reconfigure({
      plugins: this.extensionManager.plugins
    });
    this.view.updateState(i), this.createNodeViews(), this.prependClass();
    const s = this.view.dom;
    s.editor = this;
  }
  /**
   * Creates all node views.
   */
  createNodeViews() {
    this.view.isDestroyed || this.view.setProps({
      nodeViews: this.extensionManager.nodeViews
    });
  }
  /**
   * Prepend class name to element.
   */
  prependClass() {
    this.view.dom.className = `tiptap ${this.view.dom.className}`;
  }
  captureTransaction(e) {
    this.isCapturingTransaction = true, e(), this.isCapturingTransaction = false;
    const t = this.capturedTransaction;
    return this.capturedTransaction = null, t;
  }
  /**
   * The callback over which to send transactions (state updates) produced by the view.
   *
   * @param transaction An editor state transaction
   */
  dispatchTransaction(e) {
    if (this.view.isDestroyed)
      return;
    if (this.isCapturingTransaction) {
      if (!this.capturedTransaction) {
        this.capturedTransaction = e;
        return;
      }
      e.steps.forEach((o) => {
        var l;
        return (l = this.capturedTransaction) === null || l === void 0 ? void 0 : l.step(o);
      });
      return;
    }
    const t = this.state.apply(e), r = !this.state.selection.eq(t.selection);
    this.emit("beforeTransaction", {
      editor: this,
      transaction: e,
      nextState: t
    }), this.view.updateState(t), this.emit("transaction", {
      editor: this,
      transaction: e
    }), r && this.emit("selectionUpdate", {
      editor: this,
      transaction: e
    });
    const i = e.getMeta("focus"), s = e.getMeta("blur");
    i && this.emit("focus", {
      editor: this,
      event: i.event,
      transaction: e
    }), s && this.emit("blur", {
      editor: this,
      event: s.event,
      transaction: e
    }), !(!e.docChanged || e.getMeta("preventUpdate")) && this.emit("update", {
      editor: this,
      transaction: e
    });
  }
  /**
   * Get attributes of the currently selected node or mark.
   */
  getAttributes(e) {
    return kf(this.state, e);
  }
  isActive(e, t) {
    const r = typeof e == "string" ? e : null, i = typeof e == "string" ? t : e;
    return e0(this.state, r, i);
  }
  /**
   * Get the document as JSON.
   */
  getJSON() {
    return this.state.doc.toJSON();
  }
  /**
   * Get the document as HTML.
   */
  getHTML() {
    return Xl(this.state.doc.content, this.schema);
  }
  /**
   * Get the document as text.
   */
  getText(e) {
    const { blockSeparator: t = `

`, textSerializers: r = {} } = e || {};
    return wf(this.state.doc, {
      blockSeparator: t,
      textSerializers: {
        ...Zl(this.schema),
        ...r
      }
    });
  }
  /**
   * Check if there is no content.
   */
  get isEmpty() {
    return eo(this.state.doc);
  }
  /**
   * Get the number of characters for the current document.
   *
   * @deprecated
   */
  getCharacterCount() {
    return console.warn('[tiptap warn]: "editor.getCharacterCount()" is deprecated. Please use "editor.storage.characterCount.characters()" instead.'), this.state.doc.content.size - 2;
  }
  /**
   * Destroy the editor.
   */
  destroy() {
    if (this.emit("destroy"), this.view) {
      const e = this.view.dom;
      e && e.editor && delete e.editor, this.view.destroy();
    }
    this.removeAllListeners();
  }
  /**
   * Check if the editor is already destroyed.
   */
  get isDestroyed() {
    var e;
    return !(!((e = this.view) === null || e === void 0) && e.docView);
  }
  $node(e, t) {
    var r;
    return ((r = this.$doc) === null || r === void 0 ? void 0 : r.querySelector(e, t)) || null;
  }
  $nodes(e, t) {
    var r;
    return ((r = this.$doc) === null || r === void 0 ? void 0 : r.querySelectorAll(e, t)) || null;
  }
  $pos(e) {
    const t = this.state.doc.resolve(e);
    return new mn(t, this);
  }
  get $doc() {
    return this.$pos(0);
  }
};
function sr(n2) {
  return new Ys({
    find: n2.find,
    handler: ({ state: e, range: t, match: r }) => {
      const i = W(n2.getAttributes, void 0, r);
      if (i === false || i === null)
        return null;
      const { tr: s } = e, o = r[r.length - 1], l = r[0];
      if (o) {
        const a = l.search(/\S/), c = t.from + l.indexOf(o), u = c + o.length;
        if (ra(t.from, t.to, e.doc).filter((h2) => h2.mark.type.excluded.find((m) => m === n2.type && m !== h2.mark.type)).filter((h2) => h2.to > c).length)
          return null;
        u < t.to && s.delete(u, t.to), c > t.from && s.delete(t.from + a, c);
        const f = t.from + a + o.length;
        s.addMark(t.from + a, f, n2.type.create(i || {})), s.removeStoredMark(n2.type);
      }
    }
  });
}
function R0(n2) {
  return new Ys({
    find: n2.find,
    handler: ({ state: e, range: t, match: r }) => {
      const i = W(n2.getAttributes, void 0, r) || {}, { tr: s } = e, o = t.from;
      let l = t.to;
      const a = n2.type.create(i);
      if (r[1]) {
        const c = r[0].lastIndexOf(r[1]);
        let u = o + c;
        u > l ? u = l : l = u + r[1].length;
        const d = r[0][r[0].length - 1];
        s.insertText(d, o + r[0].length - 1), s.replaceWith(u, l, a);
      } else if (r[0]) {
        const c = n2.type.isInline ? o : o - 1;
        s.insert(c, n2.type.create(i)).delete(s.mapping.map(o), s.mapping.map(l));
      }
      s.scrollIntoView();
    }
  });
}
function sl(n2) {
  return new Ys({
    find: n2.find,
    handler: ({ state: e, range: t, match: r }) => {
      const i = e.doc.resolve(t.from), s = W(n2.getAttributes, void 0, r) || {};
      if (!i.node(-1).canReplaceWith(i.index(-1), i.indexAfter(-1), n2.type))
        return null;
      e.tr.delete(t.from, t.to).setBlockType(t.from, t.from, n2.type, s);
    }
  });
}
function Yr(n2) {
  return new Ys({
    find: n2.find,
    handler: ({ state: e, range: t, match: r, chain: i }) => {
      const s = W(n2.getAttributes, void 0, r) || {}, o = e.tr.delete(t.from, t.to), a = o.doc.resolve(t.from).blockRange(), c = a && Il(a, n2.type, s);
      if (!c)
        return null;
      if (o.wrap(a, c), n2.keepMarks && n2.editor) {
        const { selection: d, storedMarks: f } = e, { splittableMarks: h2 } = n2.editor.extensionManager, p2 = f || d.$to.parentOffset && d.$from.marks();
        if (p2) {
          const m = p2.filter((g) => h2.includes(g.type.name));
          o.ensureMarks(m);
        }
      }
      if (n2.keepAttributes) {
        const d = n2.type.name === "bulletList" || n2.type.name === "orderedList" ? "listItem" : "taskList";
        i().updateAttributes(d, s).run();
      }
      const u = o.doc.resolve(t.from - 1).nodeBefore;
      u && u.type === n2.type && sn(o.doc, t.from - 1) && (!n2.joinPredicate || n2.joinPredicate(r, u)) && o.join(t.from - 1);
    }
  });
}
class ce {
  constructor(e = {}) {
    this.type = "node", this.name = "node", this.parent = null, this.child = null, this.config = {
      name: this.name,
      defaultOptions: {}
    }, this.config = {
      ...this.config,
      ...e
    }, this.name = this.config.name, e.defaultOptions && Object.keys(e.defaultOptions).length > 0 && console.warn(`[tiptap warn]: BREAKING CHANGE: "defaultOptions" is deprecated. Please use "addOptions" instead. Found in extension: "${this.name}".`), this.options = this.config.defaultOptions, this.config.addOptions && (this.options = W(L(this, "addOptions", {
      name: this.name
    }))), this.storage = W(L(this, "addStorage", {
      name: this.name,
      options: this.options
    })) || {};
  }
  static create(e = {}) {
    return new ce(e);
  }
  configure(e = {}) {
    const t = this.extend({
      ...this.config,
      addOptions: () => Xs(this.options, e)
    });
    return t.name = this.name, t.parent = this.parent, t;
  }
  extend(e = {}) {
    const t = new ce(e);
    return t.parent = this, this.child = t, t.name = e.name ? e.name : t.parent.name, e.defaultOptions && Object.keys(e.defaultOptions).length > 0 && console.warn(`[tiptap warn]: BREAKING CHANGE: "defaultOptions" is deprecated. Please use "addOptions" instead. Found in extension: "${t.name}".`), t.options = W(L(t, "addOptions", {
      name: t.name
    })), t.storage = W(L(t, "addStorage", {
      name: t.name,
      options: t.options
    })), t;
  }
}
class I0 {
  constructor(e, t, r) {
    this.isDragging = false, this.component = e, this.editor = t.editor, this.options = {
      stopEvent: null,
      ignoreMutation: null,
      ...r
    }, this.extension = t.extension, this.node = t.node, this.decorations = t.decorations, this.innerDecorations = t.innerDecorations, this.view = t.view, this.HTMLAttributes = t.HTMLAttributes, this.getPos = t.getPos, this.mount();
  }
  mount() {
  }
  get dom() {
    return this.editor.view.dom;
  }
  get contentDOM() {
    return null;
  }
  onDragStart(e) {
    var t, r, i, s, o, l, a;
    const { view: c } = this.editor, u = e.target, d = u.nodeType === 3 ? (t = u.parentElement) === null || t === void 0 ? void 0 : t.closest("[data-drag-handle]") : u.closest("[data-drag-handle]");
    if (!this.dom || !((r = this.contentDOM) === null || r === void 0) && r.contains(u) || !d)
      return;
    let f = 0, h2 = 0;
    if (this.dom !== d) {
      const w = this.dom.getBoundingClientRect(), C = d.getBoundingClientRect(), b = (i = e.offsetX) !== null && i !== void 0 ? i : (s = e.nativeEvent) === null || s === void 0 ? void 0 : s.offsetX, S = (o = e.offsetY) !== null && o !== void 0 ? o : (l = e.nativeEvent) === null || l === void 0 ? void 0 : l.offsetY;
      f = C.x - w.x + b, h2 = C.y - w.y + S;
    }
    const p2 = this.dom.cloneNode(true);
    (a = e.dataTransfer) === null || a === void 0 || a.setDragImage(p2, f, h2);
    const m = this.getPos();
    if (typeof m != "number")
      return;
    const g = B.create(c.state.doc, m), y = c.state.tr.setSelection(g);
    c.dispatch(y);
  }
  stopEvent(e) {
    var t;
    if (!this.dom)
      return false;
    if (typeof this.options.stopEvent == "function")
      return this.options.stopEvent({ event: e });
    const r = e.target;
    if (!(this.dom.contains(r) && !(!((t = this.contentDOM) === null || t === void 0) && t.contains(r))))
      return false;
    const s = e.type.startsWith("drag"), o = e.type === "drop";
    if ((["INPUT", "BUTTON", "SELECT", "TEXTAREA"].includes(r.tagName) || r.isContentEditable) && !o && !s)
      return true;
    const { isEditable: a } = this.editor, { isDragging: c } = this, u = !!this.node.type.spec.draggable, d = B.isSelectable(this.node), f = e.type === "copy", h2 = e.type === "paste", p2 = e.type === "cut", m = e.type === "mousedown";
    if (!u && d && s && e.target === this.dom && e.preventDefault(), u && s && !c && e.target === this.dom)
      return e.preventDefault(), false;
    if (u && a && !c && m) {
      const g = r.closest("[data-drag-handle]");
      g && (this.dom === g || this.dom.contains(g)) && (this.isDragging = true, document.addEventListener("dragend", () => {
        this.isDragging = false;
      }, { once: true }), document.addEventListener("drop", () => {
        this.isDragging = false;
      }, { once: true }), document.addEventListener("mouseup", () => {
        this.isDragging = false;
      }, { once: true }));
    }
    return !(c || o || f || h2 || p2 || m && d);
  }
  /**
   * Called when a DOM [mutation](https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver) or a selection change happens within the view.
   * @return `false` if the editor should re-read the selection or re-parse the range around the mutation
   * @return `true` if it can safely be ignored.
   */
  ignoreMutation(e) {
    return !this.dom || !this.contentDOM ? true : typeof this.options.ignoreMutation == "function" ? this.options.ignoreMutation({ mutation: e }) : this.node.isLeaf || this.node.isAtom ? true : e.type === "selection" || this.dom.contains(e.target) && e.type === "childList" && (Qs() || mf()) && this.editor.isFocused && [
      ...Array.from(e.addedNodes),
      ...Array.from(e.removedNodes)
    ].every((r) => r.isContentEditable) ? false : this.contentDOM === e.target && e.type === "attributes" ? true : !this.contentDOM.contains(e.target);
  }
  /**
   * Update the attributes of the prosemirror node.
   */
  updateAttributes(e) {
    this.editor.commands.command(({ tr: t }) => {
      const r = this.getPos();
      return typeof r != "number" ? false : (t.setNodeMarkup(r, void 0, {
        ...this.node.attrs,
        ...e
      }), true);
    });
  }
  /**
   * Delete the node.
   */
  deleteNode() {
    const e = this.getPos();
    if (typeof e != "number")
      return;
    const t = e + this.node.nodeSize;
    this.editor.commands.deleteRange({ from: e, to: t });
  }
}
function Ln(n2) {
  return new df({
    find: n2.find,
    handler: ({ state: e, range: t, match: r, pasteEvent: i }) => {
      const s = W(n2.getAttributes, void 0, r, i);
      if (s === false || s === null)
        return null;
      const { tr: o } = e, l = r[r.length - 1], a = r[0];
      let c = t.to;
      if (l) {
        const u = a.search(/\S/), d = t.from + a.indexOf(l), f = d + l.length;
        if (ra(t.from, t.to, e.doc).filter((p2) => p2.mark.type.excluded.find((g) => g === n2.type && g !== p2.mark.type)).filter((p2) => p2.to > d).length)
          return null;
        f < t.to && o.delete(f, t.to), d > t.from && o.delete(t.from + u, d), c = t.from + u + l.length, o.addMark(t.from + u, c, n2.type.create(s || {})), o.removeStoredMark(n2.type);
      }
    }
  });
}
function P0(n2) {
  return n2.replace(/[-/\\^$*+?.()|[\]{}]/g, "\\$&");
}
function B0(n2) {
  return new df({
    find: n2.find,
    handler({ match: e, chain: t, range: r, pasteEvent: i }) {
      const s = W(n2.getAttributes, void 0, e, i), o = W(n2.getContent, void 0, s);
      if (s === false || s === null)
        return null;
      const l = { type: n2.type.name, attrs: s };
      o && (l.content = o), e.input && t().deleteRange(r).insertContentAt(r.from, l);
    }
  });
}
var Ie = "top", Ze = "bottom", et = "right", Pe = "left", ia = "auto", li = [Ie, Ze, et, Pe], or = "start", Xr = "end", H0 = "clippingParents", xf = "viewport", Cr = "popper", F0 = "reference", Dc = /* @__PURE__ */ li.reduce(function(n2, e) {
  return n2.concat([e + "-" + or, e + "-" + Xr]);
}, []), Sf = /* @__PURE__ */ [].concat(li, [ia]).reduce(function(n2, e) {
  return n2.concat([e, e + "-" + or, e + "-" + Xr]);
}, []), z0 = "beforeRead", V0 = "read", $0 = "afterRead", _0 = "beforeMain", j0 = "main", W0 = "afterMain", U0 = "beforeWrite", K0 = "write", q0 = "afterWrite", J0 = [z0, V0, $0, _0, j0, W0, U0, K0, q0];
function bt(n2) {
  return n2 ? (n2.nodeName || "").toLowerCase() : null;
}
function We(n2) {
  if (n2 == null)
    return window;
  if (n2.toString() !== "[object Window]") {
    var e = n2.ownerDocument;
    return e && e.defaultView || window;
  }
  return n2;
}
function Rn(n2) {
  var e = We(n2).Element;
  return n2 instanceof e || n2 instanceof Element;
}
function Qe(n2) {
  var e = We(n2).HTMLElement;
  return n2 instanceof e || n2 instanceof HTMLElement;
}
function sa(n2) {
  if (typeof ShadowRoot > "u")
    return false;
  var e = We(n2).ShadowRoot;
  return n2 instanceof e || n2 instanceof ShadowRoot;
}
function G0(n2) {
  var e = n2.state;
  Object.keys(e.elements).forEach(function(t) {
    var r = e.styles[t] || {}, i = e.attributes[t] || {}, s = e.elements[t];
    !Qe(s) || !bt(s) || (Object.assign(s.style, r), Object.keys(i).forEach(function(o) {
      var l = i[o];
      l === false ? s.removeAttribute(o) : s.setAttribute(o, l === true ? "" : l);
    }));
  });
}
function Y0(n2) {
  var e = n2.state, t = {
    popper: {
      position: e.options.strategy,
      left: "0",
      top: "0",
      margin: "0"
    },
    arrow: {
      position: "absolute"
    },
    reference: {}
  };
  return Object.assign(e.elements.popper.style, t.popper), e.styles = t, e.elements.arrow && Object.assign(e.elements.arrow.style, t.arrow), function() {
    Object.keys(e.elements).forEach(function(r) {
      var i = e.elements[r], s = e.attributes[r] || {}, o = Object.keys(e.styles.hasOwnProperty(r) ? e.styles[r] : t[r]), l = o.reduce(function(a, c) {
        return a[c] = "", a;
      }, {});
      !Qe(i) || !bt(i) || (Object.assign(i.style, l), Object.keys(s).forEach(function(a) {
        i.removeAttribute(a);
      }));
    });
  };
}
const Mf = {
  name: "applyStyles",
  enabled: true,
  phase: "write",
  fn: G0,
  effect: Y0,
  requires: ["computeStyles"]
};
function mt(n2) {
  return n2.split("-")[0];
}
var An = Math.max, ss = Math.min, lr = Math.round;
function ol() {
  var n2 = navigator.userAgentData;
  return n2 != null && n2.brands && Array.isArray(n2.brands) ? n2.brands.map(function(e) {
    return e.brand + "/" + e.version;
  }).join(" ") : navigator.userAgent;
}
function Af() {
  return !/^((?!chrome|android).)*safari/i.test(ol());
}
function ar(n2, e, t) {
  e === void 0 && (e = false), t === void 0 && (t = false);
  var r = n2.getBoundingClientRect(), i = 1, s = 1;
  e && Qe(n2) && (i = n2.offsetWidth > 0 && lr(r.width) / n2.offsetWidth || 1, s = n2.offsetHeight > 0 && lr(r.height) / n2.offsetHeight || 1);
  var o = Rn(n2) ? We(n2) : window, l = o.visualViewport, a = !Af() && t, c = (r.left + (a && l ? l.offsetLeft : 0)) / i, u = (r.top + (a && l ? l.offsetTop : 0)) / s, d = r.width / i, f = r.height / s;
  return {
    width: d,
    height: f,
    top: u,
    right: c + d,
    bottom: u + f,
    left: c,
    x: c,
    y: u
  };
}
function oa(n2) {
  var e = ar(n2), t = n2.offsetWidth, r = n2.offsetHeight;
  return Math.abs(e.width - t) <= 1 && (t = e.width), Math.abs(e.height - r) <= 1 && (r = e.height), {
    x: n2.offsetLeft,
    y: n2.offsetTop,
    width: t,
    height: r
  };
}
function Ef(n2, e) {
  var t = e.getRootNode && e.getRootNode();
  if (n2.contains(e))
    return true;
  if (t && sa(t)) {
    var r = e;
    do {
      if (r && n2.isSameNode(r))
        return true;
      r = r.parentNode || r.host;
    } while (r);
  }
  return false;
}
function Pt(n2) {
  return We(n2).getComputedStyle(n2);
}
function X0(n2) {
  return ["table", "td", "th"].indexOf(bt(n2)) >= 0;
}
function an(n2) {
  return ((Rn(n2) ? n2.ownerDocument : (
    // $FlowFixMe[prop-missing]
    n2.document
  )) || window.document).documentElement;
}
function to(n2) {
  return bt(n2) === "html" ? n2 : (
    // this is a quicker (but less type safe) way to save quite some bytes from the bundle
    // $FlowFixMe[incompatible-return]
    // $FlowFixMe[prop-missing]
    n2.assignedSlot || // step into the shadow DOM of the parent of a slotted node
    n2.parentNode || // DOM Element detected
    (sa(n2) ? n2.host : null) || // ShadowRoot detected
    // $FlowFixMe[incompatible-call]: HTMLElement is a Node
    an(n2)
  );
}
function Lc(n2) {
  return !Qe(n2) || // https://github.com/popperjs/popper-core/issues/837
  Pt(n2).position === "fixed" ? null : n2.offsetParent;
}
function Q0(n2) {
  var e = /firefox/i.test(ol()), t = /Trident/i.test(ol());
  if (t && Qe(n2)) {
    var r = Pt(n2);
    if (r.position === "fixed")
      return null;
  }
  var i = to(n2);
  for (sa(i) && (i = i.host); Qe(i) && ["html", "body"].indexOf(bt(i)) < 0; ) {
    var s = Pt(i);
    if (s.transform !== "none" || s.perspective !== "none" || s.contain === "paint" || ["transform", "perspective"].indexOf(s.willChange) !== -1 || e && s.willChange === "filter" || e && s.filter && s.filter !== "none")
      return i;
    i = i.parentNode;
  }
  return null;
}
function ai(n2) {
  for (var e = We(n2), t = Lc(n2); t && X0(t) && Pt(t).position === "static"; )
    t = Lc(t);
  return t && (bt(t) === "html" || bt(t) === "body" && Pt(t).position === "static") ? e : t || Q0(n2) || e;
}
function la(n2) {
  return ["top", "bottom"].indexOf(n2) >= 0 ? "x" : "y";
}
function Lr(n2, e, t) {
  return An(n2, ss(e, t));
}
function Z0(n2, e, t) {
  var r = Lr(n2, e, t);
  return r > t ? t : r;
}
function Tf() {
  return {
    top: 0,
    right: 0,
    bottom: 0,
    left: 0
  };
}
function Of(n2) {
  return Object.assign({}, Tf(), n2);
}
function Nf(n2, e) {
  return e.reduce(function(t, r) {
    return t[r] = n2, t;
  }, {});
}
var eb = function(e, t) {
  return e = typeof e == "function" ? e(Object.assign({}, t.rects, {
    placement: t.placement
  })) : e, Of(typeof e != "number" ? e : Nf(e, li));
};
function tb(n2) {
  var e, t = n2.state, r = n2.name, i = n2.options, s = t.elements.arrow, o = t.modifiersData.popperOffsets, l = mt(t.placement), a = la(l), c = [Pe, et].indexOf(l) >= 0, u = c ? "height" : "width";
  if (!(!s || !o)) {
    var d = eb(i.padding, t), f = oa(s), h2 = a === "y" ? Ie : Pe, p2 = a === "y" ? Ze : et, m = t.rects.reference[u] + t.rects.reference[a] - o[a] - t.rects.popper[u], g = o[a] - t.rects.reference[a], y = ai(s), w = y ? a === "y" ? y.clientHeight || 0 : y.clientWidth || 0 : 0, C = m / 2 - g / 2, b = d[h2], S = w - f[u] - d[p2], k = w / 2 - f[u] / 2 + C, T = Lr(b, k, S), M = a;
    t.modifiersData[r] = (e = {}, e[M] = T, e.centerOffset = T - k, e);
  }
}
function nb(n2) {
  var e = n2.state, t = n2.options, r = t.element, i = r === void 0 ? "[data-popper-arrow]" : r;
  i != null && (typeof i == "string" && (i = e.elements.popper.querySelector(i), !i) || Ef(e.elements.popper, i) && (e.elements.arrow = i));
}
const rb = {
  name: "arrow",
  enabled: true,
  phase: "main",
  fn: tb,
  effect: nb,
  requires: ["popperOffsets"],
  requiresIfExists: ["preventOverflow"]
};
function cr(n2) {
  return n2.split("-")[1];
}
var ib = {
  top: "auto",
  right: "auto",
  bottom: "auto",
  left: "auto"
};
function sb(n2, e) {
  var t = n2.x, r = n2.y, i = e.devicePixelRatio || 1;
  return {
    x: lr(t * i) / i || 0,
    y: lr(r * i) / i || 0
  };
}
function Rc(n2) {
  var e, t = n2.popper, r = n2.popperRect, i = n2.placement, s = n2.variation, o = n2.offsets, l = n2.position, a = n2.gpuAcceleration, c = n2.adaptive, u = n2.roundOffsets, d = n2.isFixed, f = o.x, h2 = f === void 0 ? 0 : f, p2 = o.y, m = p2 === void 0 ? 0 : p2, g = typeof u == "function" ? u({
    x: h2,
    y: m
  }) : {
    x: h2,
    y: m
  };
  h2 = g.x, m = g.y;
  var y = o.hasOwnProperty("x"), w = o.hasOwnProperty("y"), C = Pe, b = Ie, S = window;
  if (c) {
    var k = ai(t), T = "clientHeight", M = "clientWidth";
    if (k === We(t) && (k = an(t), Pt(k).position !== "static" && l === "absolute" && (T = "scrollHeight", M = "scrollWidth")), k = k, i === Ie || (i === Pe || i === et) && s === Xr) {
      b = Ze;
      var I = d && k === S && S.visualViewport ? S.visualViewport.height : (
        // $FlowFixMe[prop-missing]
        k[T]
      );
      m -= I - r.height, m *= a ? 1 : -1;
    }
    if (i === Pe || (i === Ie || i === Ze) && s === Xr) {
      C = et;
      var N = d && k === S && S.visualViewport ? S.visualViewport.width : (
        // $FlowFixMe[prop-missing]
        k[M]
      );
      h2 -= N - r.width, h2 *= a ? 1 : -1;
    }
  }
  var j = Object.assign({
    position: l
  }, c && ib), K = u === true ? sb({
    x: h2,
    y: m
  }, We(t)) : {
    x: h2,
    y: m
  };
  if (h2 = K.x, m = K.y, a) {
    var Y;
    return Object.assign({}, j, (Y = {}, Y[b] = w ? "0" : "", Y[C] = y ? "0" : "", Y.transform = (S.devicePixelRatio || 1) <= 1 ? "translate(" + h2 + "px, " + m + "px)" : "translate3d(" + h2 + "px, " + m + "px, 0)", Y));
  }
  return Object.assign({}, j, (e = {}, e[b] = w ? m + "px" : "", e[C] = y ? h2 + "px" : "", e.transform = "", e));
}
function ob(n2) {
  var e = n2.state, t = n2.options, r = t.gpuAcceleration, i = r === void 0 ? true : r, s = t.adaptive, o = s === void 0 ? true : s, l = t.roundOffsets, a = l === void 0 ? true : l, c = {
    placement: mt(e.placement),
    variation: cr(e.placement),
    popper: e.elements.popper,
    popperRect: e.rects.popper,
    gpuAcceleration: i,
    isFixed: e.options.strategy === "fixed"
  };
  e.modifiersData.popperOffsets != null && (e.styles.popper = Object.assign({}, e.styles.popper, Rc(Object.assign({}, c, {
    offsets: e.modifiersData.popperOffsets,
    position: e.options.strategy,
    adaptive: o,
    roundOffsets: a
  })))), e.modifiersData.arrow != null && (e.styles.arrow = Object.assign({}, e.styles.arrow, Rc(Object.assign({}, c, {
    offsets: e.modifiersData.arrow,
    position: "absolute",
    adaptive: false,
    roundOffsets: a
  })))), e.attributes.popper = Object.assign({}, e.attributes.popper, {
    "data-popper-placement": e.placement
  });
}
const lb = {
  name: "computeStyles",
  enabled: true,
  phase: "beforeWrite",
  fn: ob,
  data: {}
};
var Ei = {
  passive: true
};
function ab(n2) {
  var e = n2.state, t = n2.instance, r = n2.options, i = r.scroll, s = i === void 0 ? true : i, o = r.resize, l = o === void 0 ? true : o, a = We(e.elements.popper), c = [].concat(e.scrollParents.reference, e.scrollParents.popper);
  return s && c.forEach(function(u) {
    u.addEventListener("scroll", t.update, Ei);
  }), l && a.addEventListener("resize", t.update, Ei), function() {
    s && c.forEach(function(u) {
      u.removeEventListener("scroll", t.update, Ei);
    }), l && a.removeEventListener("resize", t.update, Ei);
  };
}
const cb = {
  name: "eventListeners",
  enabled: true,
  phase: "write",
  fn: function() {
  },
  effect: ab,
  data: {}
};
var ub = {
  left: "right",
  right: "left",
  bottom: "top",
  top: "bottom"
};
function $i(n2) {
  return n2.replace(/left|right|bottom|top/g, function(e) {
    return ub[e];
  });
}
var db = {
  start: "end",
  end: "start"
};
function Ic(n2) {
  return n2.replace(/start|end/g, function(e) {
    return db[e];
  });
}
function aa(n2) {
  var e = We(n2), t = e.pageXOffset, r = e.pageYOffset;
  return {
    scrollLeft: t,
    scrollTop: r
  };
}
function ca(n2) {
  return ar(an(n2)).left + aa(n2).scrollLeft;
}
function fb(n2, e) {
  var t = We(n2), r = an(n2), i = t.visualViewport, s = r.clientWidth, o = r.clientHeight, l = 0, a = 0;
  if (i) {
    s = i.width, o = i.height;
    var c = Af();
    (c || !c && e === "fixed") && (l = i.offsetLeft, a = i.offsetTop);
  }
  return {
    width: s,
    height: o,
    x: l + ca(n2),
    y: a
  };
}
function hb(n2) {
  var e, t = an(n2), r = aa(n2), i = (e = n2.ownerDocument) == null ? void 0 : e.body, s = An(t.scrollWidth, t.clientWidth, i ? i.scrollWidth : 0, i ? i.clientWidth : 0), o = An(t.scrollHeight, t.clientHeight, i ? i.scrollHeight : 0, i ? i.clientHeight : 0), l = -r.scrollLeft + ca(n2), a = -r.scrollTop;
  return Pt(i || t).direction === "rtl" && (l += An(t.clientWidth, i ? i.clientWidth : 0) - s), {
    width: s,
    height: o,
    x: l,
    y: a
  };
}
function ua(n2) {
  var e = Pt(n2), t = e.overflow, r = e.overflowX, i = e.overflowY;
  return /auto|scroll|overlay|hidden/.test(t + i + r);
}
function Df(n2) {
  return ["html", "body", "#document"].indexOf(bt(n2)) >= 0 ? n2.ownerDocument.body : Qe(n2) && ua(n2) ? n2 : Df(to(n2));
}
function Rr(n2, e) {
  var t;
  e === void 0 && (e = []);
  var r = Df(n2), i = r === ((t = n2.ownerDocument) == null ? void 0 : t.body), s = We(r), o = i ? [s].concat(s.visualViewport || [], ua(r) ? r : []) : r, l = e.concat(o);
  return i ? l : (
    // $FlowFixMe[incompatible-call]: isBody tells us target will be an HTMLElement here
    l.concat(Rr(to(o)))
  );
}
function ll(n2) {
  return Object.assign({}, n2, {
    left: n2.x,
    top: n2.y,
    right: n2.x + n2.width,
    bottom: n2.y + n2.height
  });
}
function pb(n2, e) {
  var t = ar(n2, false, e === "fixed");
  return t.top = t.top + n2.clientTop, t.left = t.left + n2.clientLeft, t.bottom = t.top + n2.clientHeight, t.right = t.left + n2.clientWidth, t.width = n2.clientWidth, t.height = n2.clientHeight, t.x = t.left, t.y = t.top, t;
}
function Pc(n2, e, t) {
  return e === xf ? ll(fb(n2, t)) : Rn(e) ? pb(e, t) : ll(hb(an(n2)));
}
function mb(n2) {
  var e = Rr(to(n2)), t = ["absolute", "fixed"].indexOf(Pt(n2).position) >= 0, r = t && Qe(n2) ? ai(n2) : n2;
  return Rn(r) ? e.filter(function(i) {
    return Rn(i) && Ef(i, r) && bt(i) !== "body";
  }) : [];
}
function gb(n2, e, t, r) {
  var i = e === "clippingParents" ? mb(n2) : [].concat(e), s = [].concat(i, [t]), o = s[0], l = s.reduce(function(a, c) {
    var u = Pc(n2, c, r);
    return a.top = An(u.top, a.top), a.right = ss(u.right, a.right), a.bottom = ss(u.bottom, a.bottom), a.left = An(u.left, a.left), a;
  }, Pc(n2, o, r));
  return l.width = l.right - l.left, l.height = l.bottom - l.top, l.x = l.left, l.y = l.top, l;
}
function Lf(n2) {
  var e = n2.reference, t = n2.element, r = n2.placement, i = r ? mt(r) : null, s = r ? cr(r) : null, o = e.x + e.width / 2 - t.width / 2, l = e.y + e.height / 2 - t.height / 2, a;
  switch (i) {
    case Ie:
      a = {
        x: o,
        y: e.y - t.height
      };
      break;
    case Ze:
      a = {
        x: o,
        y: e.y + e.height
      };
      break;
    case et:
      a = {
        x: e.x + e.width,
        y: l
      };
      break;
    case Pe:
      a = {
        x: e.x - t.width,
        y: l
      };
      break;
    default:
      a = {
        x: e.x,
        y: e.y
      };
  }
  var c = i ? la(i) : null;
  if (c != null) {
    var u = c === "y" ? "height" : "width";
    switch (s) {
      case or:
        a[c] = a[c] - (e[u] / 2 - t[u] / 2);
        break;
      case Xr:
        a[c] = a[c] + (e[u] / 2 - t[u] / 2);
        break;
    }
  }
  return a;
}
function Qr(n2, e) {
  e === void 0 && (e = {});
  var t = e, r = t.placement, i = r === void 0 ? n2.placement : r, s = t.strategy, o = s === void 0 ? n2.strategy : s, l = t.boundary, a = l === void 0 ? H0 : l, c = t.rootBoundary, u = c === void 0 ? xf : c, d = t.elementContext, f = d === void 0 ? Cr : d, h2 = t.altBoundary, p2 = h2 === void 0 ? false : h2, m = t.padding, g = m === void 0 ? 0 : m, y = Of(typeof g != "number" ? g : Nf(g, li)), w = f === Cr ? F0 : Cr, C = n2.rects.popper, b = n2.elements[p2 ? w : f], S = gb(Rn(b) ? b : b.contextElement || an(n2.elements.popper), a, u, o), k = ar(n2.elements.reference), T = Lf({
    reference: k,
    element: C,
    placement: i
  }), M = ll(Object.assign({}, C, T)), I = f === Cr ? M : k, N = {
    top: S.top - I.top + y.top,
    bottom: I.bottom - S.bottom + y.bottom,
    left: S.left - I.left + y.left,
    right: I.right - S.right + y.right
  }, j = n2.modifiersData.offset;
  if (f === Cr && j) {
    var K = j[i];
    Object.keys(N).forEach(function(Y) {
      var J = [et, Ze].indexOf(Y) >= 0 ? 1 : -1, Z = [Ie, Ze].indexOf(Y) >= 0 ? "y" : "x";
      N[Y] += K[Z] * J;
    });
  }
  return N;
}
function yb(n2, e) {
  e === void 0 && (e = {});
  var t = e, r = t.placement, i = t.boundary, s = t.rootBoundary, o = t.padding, l = t.flipVariations, a = t.allowedAutoPlacements, c = a === void 0 ? Sf : a, u = cr(r), d = u ? l ? Dc : Dc.filter(function(p2) {
    return cr(p2) === u;
  }) : li, f = d.filter(function(p2) {
    return c.indexOf(p2) >= 0;
  });
  f.length === 0 && (f = d);
  var h2 = f.reduce(function(p2, m) {
    return p2[m] = Qr(n2, {
      placement: m,
      boundary: i,
      rootBoundary: s,
      padding: o
    })[mt(m)], p2;
  }, {});
  return Object.keys(h2).sort(function(p2, m) {
    return h2[p2] - h2[m];
  });
}
function bb(n2) {
  if (mt(n2) === ia)
    return [];
  var e = $i(n2);
  return [Ic(n2), e, Ic(e)];
}
function vb(n2) {
  var e = n2.state, t = n2.options, r = n2.name;
  if (!e.modifiersData[r]._skip) {
    for (var i = t.mainAxis, s = i === void 0 ? true : i, o = t.altAxis, l = o === void 0 ? true : o, a = t.fallbackPlacements, c = t.padding, u = t.boundary, d = t.rootBoundary, f = t.altBoundary, h2 = t.flipVariations, p2 = h2 === void 0 ? true : h2, m = t.allowedAutoPlacements, g = e.options.placement, y = mt(g), w = y === g, C = a || (w || !p2 ? [$i(g)] : bb(g)), b = [g].concat(C).reduce(function(wt, nt) {
      return wt.concat(mt(nt) === ia ? yb(e, {
        placement: nt,
        boundary: u,
        rootBoundary: d,
        padding: c,
        flipVariations: p2,
        allowedAutoPlacements: m
      }) : nt);
    }, []), S = e.rects.reference, k = e.rects.popper, T = /* @__PURE__ */ new Map(), M = true, I = b[0], N = 0; N < b.length; N++) {
      var j = b[N], K = mt(j), Y = cr(j) === or, J = [Ie, Ze].indexOf(K) >= 0, Z = J ? "width" : "height", G = Qr(e, {
        placement: j,
        boundary: u,
        rootBoundary: d,
        altBoundary: f,
        padding: c
      }), ee = J ? Y ? et : Pe : Y ? Ze : Ie;
      S[Z] > k[Z] && (ee = $i(ee));
      var ae = $i(ee), ye = [];
      if (s && ye.push(G[K] <= 0), l && ye.push(G[ee] <= 0, G[ae] <= 0), ye.every(function(wt) {
        return wt;
      })) {
        I = j, M = false;
        break;
      }
      T.set(j, ye);
    }
    if (M)
      for (var Be = p2 ? 3 : 1, He = function(nt) {
        var kt = b.find(function(Fn) {
          var Ct = T.get(Fn);
          if (Ct)
            return Ct.slice(0, nt).every(function(zn) {
              return zn;
            });
        });
        if (kt)
          return I = kt, "break";
      }, Ue = Be; Ue > 0; Ue--) {
        var tt2 = He(Ue);
        if (tt2 === "break") break;
      }
    e.placement !== I && (e.modifiersData[r]._skip = true, e.placement = I, e.reset = true);
  }
}
const wb = {
  name: "flip",
  enabled: true,
  phase: "main",
  fn: vb,
  requiresIfExists: ["offset"],
  data: {
    _skip: false
  }
};
function Bc(n2, e, t) {
  return t === void 0 && (t = {
    x: 0,
    y: 0
  }), {
    top: n2.top - e.height - t.y,
    right: n2.right - e.width + t.x,
    bottom: n2.bottom - e.height + t.y,
    left: n2.left - e.width - t.x
  };
}
function Hc(n2) {
  return [Ie, et, Ze, Pe].some(function(e) {
    return n2[e] >= 0;
  });
}
function kb(n2) {
  var e = n2.state, t = n2.name, r = e.rects.reference, i = e.rects.popper, s = e.modifiersData.preventOverflow, o = Qr(e, {
    elementContext: "reference"
  }), l = Qr(e, {
    altBoundary: true
  }), a = Bc(o, r), c = Bc(l, i, s), u = Hc(a), d = Hc(c);
  e.modifiersData[t] = {
    referenceClippingOffsets: a,
    popperEscapeOffsets: c,
    isReferenceHidden: u,
    hasPopperEscaped: d
  }, e.attributes.popper = Object.assign({}, e.attributes.popper, {
    "data-popper-reference-hidden": u,
    "data-popper-escaped": d
  });
}
const Cb = {
  name: "hide",
  enabled: true,
  phase: "main",
  requiresIfExists: ["preventOverflow"],
  fn: kb
};
function xb(n2, e, t) {
  var r = mt(n2), i = [Pe, Ie].indexOf(r) >= 0 ? -1 : 1, s = typeof t == "function" ? t(Object.assign({}, e, {
    placement: n2
  })) : t, o = s[0], l = s[1];
  return o = o || 0, l = (l || 0) * i, [Pe, et].indexOf(r) >= 0 ? {
    x: l,
    y: o
  } : {
    x: o,
    y: l
  };
}
function Sb(n2) {
  var e = n2.state, t = n2.options, r = n2.name, i = t.offset, s = i === void 0 ? [0, 0] : i, o = Sf.reduce(function(u, d) {
    return u[d] = xb(d, e.rects, s), u;
  }, {}), l = o[e.placement], a = l.x, c = l.y;
  e.modifiersData.popperOffsets != null && (e.modifiersData.popperOffsets.x += a, e.modifiersData.popperOffsets.y += c), e.modifiersData[r] = o;
}
const Mb = {
  name: "offset",
  enabled: true,
  phase: "main",
  requires: ["popperOffsets"],
  fn: Sb
};
function Ab(n2) {
  var e = n2.state, t = n2.name;
  e.modifiersData[t] = Lf({
    reference: e.rects.reference,
    element: e.rects.popper,
    placement: e.placement
  });
}
const Eb = {
  name: "popperOffsets",
  enabled: true,
  phase: "read",
  fn: Ab,
  data: {}
};
function Tb(n2) {
  return n2 === "x" ? "y" : "x";
}
function Ob(n2) {
  var e = n2.state, t = n2.options, r = n2.name, i = t.mainAxis, s = i === void 0 ? true : i, o = t.altAxis, l = o === void 0 ? false : o, a = t.boundary, c = t.rootBoundary, u = t.altBoundary, d = t.padding, f = t.tether, h2 = f === void 0 ? true : f, p2 = t.tetherOffset, m = p2 === void 0 ? 0 : p2, g = Qr(e, {
    boundary: a,
    rootBoundary: c,
    padding: d,
    altBoundary: u
  }), y = mt(e.placement), w = cr(e.placement), C = !w, b = la(y), S = Tb(b), k = e.modifiersData.popperOffsets, T = e.rects.reference, M = e.rects.popper, I = typeof m == "function" ? m(Object.assign({}, e.rects, {
    placement: e.placement
  })) : m, N = typeof I == "number" ? {
    mainAxis: I,
    altAxis: I
  } : Object.assign({
    mainAxis: 0,
    altAxis: 0
  }, I), j = e.modifiersData.offset ? e.modifiersData.offset[e.placement] : null, K = {
    x: 0,
    y: 0
  };
  if (k) {
    if (s) {
      var Y, J = b === "y" ? Ie : Pe, Z = b === "y" ? Ze : et, G = b === "y" ? "height" : "width", ee = k[b], ae = ee + g[J], ye = ee - g[Z], Be = h2 ? -M[G] / 2 : 0, He = w === or ? T[G] : M[G], Ue = w === or ? -M[G] : -T[G], tt2 = e.elements.arrow, wt = h2 && tt2 ? oa(tt2) : {
        width: 0,
        height: 0
      }, nt = e.modifiersData["arrow#persistent"] ? e.modifiersData["arrow#persistent"].padding : Tf(), kt = nt[J], Fn = nt[Z], Ct = Lr(0, T[G], wt[G]), zn = C ? T[G] / 2 - Be - Ct - kt - N.mainAxis : He - Ct - kt - N.mainAxis, Ht = C ? -T[G] / 2 + Be + Ct + Fn + N.mainAxis : Ue + Ct + Fn + N.mainAxis, Vn = e.elements.arrow && ai(e.elements.arrow), ci = Vn ? b === "y" ? Vn.clientTop || 0 : Vn.clientLeft || 0 : 0, gr = (Y = j == null ? void 0 : j[b]) != null ? Y : 0, ui = ee + zn - gr - ci, di = ee + Ht - gr, yr = Lr(h2 ? ss(ae, ui) : ae, ee, h2 ? An(ye, di) : ye);
      k[b] = yr, K[b] = yr - ee;
    }
    if (l) {
      var br, fi = b === "x" ? Ie : Pe, hi = b === "x" ? Ze : et, xt = k[S], Ft = S === "y" ? "height" : "width", vr = xt + g[fi], un = xt - g[hi], wr = [Ie, Pe].indexOf(y) !== -1, pi = (br = j == null ? void 0 : j[S]) != null ? br : 0, mi = wr ? vr : xt - T[Ft] - M[Ft] - pi + N.altAxis, gi = wr ? xt + T[Ft] + M[Ft] - pi - N.altAxis : un, yi = h2 && wr ? Z0(mi, xt, gi) : Lr(h2 ? mi : vr, xt, h2 ? gi : un);
      k[S] = yi, K[S] = yi - xt;
    }
    e.modifiersData[r] = K;
  }
}
const Nb = {
  name: "preventOverflow",
  enabled: true,
  phase: "main",
  fn: Ob,
  requiresIfExists: ["offset"]
};
function Db(n2) {
  return {
    scrollLeft: n2.scrollLeft,
    scrollTop: n2.scrollTop
  };
}
function Lb(n2) {
  return n2 === We(n2) || !Qe(n2) ? aa(n2) : Db(n2);
}
function Rb(n2) {
  var e = n2.getBoundingClientRect(), t = lr(e.width) / n2.offsetWidth || 1, r = lr(e.height) / n2.offsetHeight || 1;
  return t !== 1 || r !== 1;
}
function Ib(n2, e, t) {
  t === void 0 && (t = false);
  var r = Qe(e), i = Qe(e) && Rb(e), s = an(e), o = ar(n2, i, t), l = {
    scrollLeft: 0,
    scrollTop: 0
  }, a = {
    x: 0,
    y: 0
  };
  return (r || !r && !t) && ((bt(e) !== "body" || // https://github.com/popperjs/popper-core/issues/1078
  ua(s)) && (l = Lb(e)), Qe(e) ? (a = ar(e, true), a.x += e.clientLeft, a.y += e.clientTop) : s && (a.x = ca(s))), {
    x: o.left + l.scrollLeft - a.x,
    y: o.top + l.scrollTop - a.y,
    width: o.width,
    height: o.height
  };
}
function Pb(n2) {
  var e = /* @__PURE__ */ new Map(), t = /* @__PURE__ */ new Set(), r = [];
  n2.forEach(function(s) {
    e.set(s.name, s);
  });
  function i(s) {
    t.add(s.name);
    var o = [].concat(s.requires || [], s.requiresIfExists || []);
    o.forEach(function(l) {
      if (!t.has(l)) {
        var a = e.get(l);
        a && i(a);
      }
    }), r.push(s);
  }
  return n2.forEach(function(s) {
    t.has(s.name) || i(s);
  }), r;
}
function Bb(n2) {
  var e = Pb(n2);
  return J0.reduce(function(t, r) {
    return t.concat(e.filter(function(i) {
      return i.phase === r;
    }));
  }, []);
}
function Hb(n2) {
  var e;
  return function() {
    return e || (e = new Promise(function(t) {
      Promise.resolve().then(function() {
        e = void 0, t(n2());
      });
    })), e;
  };
}
function Fb(n2) {
  var e = n2.reduce(function(t, r) {
    var i = t[r.name];
    return t[r.name] = i ? Object.assign({}, i, r, {
      options: Object.assign({}, i.options, r.options),
      data: Object.assign({}, i.data, r.data)
    }) : r, t;
  }, {});
  return Object.keys(e).map(function(t) {
    return e[t];
  });
}
var Fc = {
  placement: "bottom",
  modifiers: [],
  strategy: "absolute"
};
function zc() {
  for (var n2 = arguments.length, e = new Array(n2), t = 0; t < n2; t++)
    e[t] = arguments[t];
  return !e.some(function(r) {
    return !(r && typeof r.getBoundingClientRect == "function");
  });
}
function zb(n2) {
  n2 === void 0 && (n2 = {});
  var e = n2, t = e.defaultModifiers, r = t === void 0 ? [] : t, i = e.defaultOptions, s = i === void 0 ? Fc : i;
  return function(l, a, c) {
    c === void 0 && (c = s);
    var u = {
      placement: "bottom",
      orderedModifiers: [],
      options: Object.assign({}, Fc, s),
      modifiersData: {},
      elements: {
        reference: l,
        popper: a
      },
      attributes: {},
      styles: {}
    }, d = [], f = false, h2 = {
      state: u,
      setOptions: function(y) {
        var w = typeof y == "function" ? y(u.options) : y;
        m(), u.options = Object.assign({}, s, u.options, w), u.scrollParents = {
          reference: Rn(l) ? Rr(l) : l.contextElement ? Rr(l.contextElement) : [],
          popper: Rr(a)
        };
        var C = Bb(Fb([].concat(r, u.options.modifiers)));
        return u.orderedModifiers = C.filter(function(b) {
          return b.enabled;
        }), p2(), h2.update();
      },
      // Sync update – it will always be executed, even if not necessary. This
      // is useful for low frequency updates where sync behavior simplifies the
      // logic.
      // For high frequency updates (e.g. `resize` and `scroll` events), always
      // prefer the async Popper#update method
      forceUpdate: function() {
        if (!f) {
          var y = u.elements, w = y.reference, C = y.popper;
          if (zc(w, C)) {
            u.rects = {
              reference: Ib(w, ai(C), u.options.strategy === "fixed"),
              popper: oa(C)
            }, u.reset = false, u.placement = u.options.placement, u.orderedModifiers.forEach(function(N) {
              return u.modifiersData[N.name] = Object.assign({}, N.data);
            });
            for (var b = 0; b < u.orderedModifiers.length; b++) {
              if (u.reset === true) {
                u.reset = false, b = -1;
                continue;
              }
              var S = u.orderedModifiers[b], k = S.fn, T = S.options, M = T === void 0 ? {} : T, I = S.name;
              typeof k == "function" && (u = k({
                state: u,
                options: M,
                name: I,
                instance: h2
              }) || u);
            }
          }
        }
      },
      // Async and optimistically optimized update – it will not be executed if
      // not necessary (debounced to run at most once-per-tick)
      update: Hb(function() {
        return new Promise(function(g) {
          h2.forceUpdate(), g(u);
        });
      }),
      destroy: function() {
        m(), f = true;
      }
    };
    if (!zc(l, a))
      return h2;
    h2.setOptions(c).then(function(g) {
      !f && c.onFirstUpdate && c.onFirstUpdate(g);
    });
    function p2() {
      u.orderedModifiers.forEach(function(g) {
        var y = g.name, w = g.options, C = w === void 0 ? {} : w, b = g.effect;
        if (typeof b == "function") {
          var S = b({
            state: u,
            name: y,
            instance: h2,
            options: C
          }), k = function() {
          };
          d.push(S || k);
        }
      });
    }
    function m() {
      d.forEach(function(g) {
        return g();
      }), d = [];
    }
    return h2;
  };
}
var Vb = [cb, Eb, lb, Mf, Mb, wb, Nb, rb, Cb], $b = /* @__PURE__ */ zb({
  defaultModifiers: Vb
}), _b = "tippy-box", Rf = "tippy-content", jb = "tippy-backdrop", If = "tippy-arrow", Pf = "tippy-svg-arrow", hn = {
  passive: true,
  capture: true
}, Bf = function() {
  return document.body;
};
function Ao(n2, e, t) {
  if (Array.isArray(n2)) {
    var r = n2[e];
    return r ?? (Array.isArray(t) ? t[e] : t);
  }
  return n2;
}
function da(n2, e) {
  var t = {}.toString.call(n2);
  return t.indexOf("[object") === 0 && t.indexOf(e + "]") > -1;
}
function Hf(n2, e) {
  return typeof n2 == "function" ? n2.apply(void 0, e) : n2;
}
function Vc(n2, e) {
  if (e === 0)
    return n2;
  var t;
  return function(r) {
    clearTimeout(t), t = setTimeout(function() {
      n2(r);
    }, e);
  };
}
function Kb(n2) {
  return n2.split(/\s+/).filter(Boolean);
}
function Gn(n2) {
  return [].concat(n2);
}
function $c(n2, e) {
  n2.indexOf(e) === -1 && n2.push(e);
}
function qb(n2) {
  return n2.filter(function(e, t) {
    return n2.indexOf(e) === t;
  });
}
function Jb(n2) {
  return n2.split("-")[0];
}
function ls(n2) {
  return [].slice.call(n2);
}
function _c(n2) {
  return Object.keys(n2).reduce(function(e, t) {
    return n2[t] !== void 0 && (e[t] = n2[t]), e;
  }, {});
}
function Ir() {
  return document.createElement("div");
}
function Zr(n2) {
  return ["Element", "Fragment"].some(function(e) {
    return da(n2, e);
  });
}
function Gb(n2) {
  return da(n2, "NodeList");
}
function Yb(n2) {
  return da(n2, "MouseEvent");
}
function Xb(n2) {
  return !!(n2 && n2._tippy && n2._tippy.reference === n2);
}
function Qb(n2) {
  return Zr(n2) ? [n2] : Gb(n2) ? ls(n2) : Array.isArray(n2) ? n2 : ls(document.querySelectorAll(n2));
}
function Eo(n2, e) {
  n2.forEach(function(t) {
    t && (t.style.transitionDuration = e + "ms");
  });
}
function jc(n2, e) {
  n2.forEach(function(t) {
    t && t.setAttribute("data-state", e);
  });
}
function Zb(n2) {
  var e, t = Gn(n2), r = t[0];
  return r != null && (e = r.ownerDocument) != null && e.body ? r.ownerDocument : document;
}
function e1(n2, e) {
  var t = e.clientX, r = e.clientY;
  return n2.every(function(i) {
    var s = i.popperRect, o = i.popperState, l = i.props, a = l.interactiveBorder, c = Jb(o.placement), u = o.modifiersData.offset;
    if (!u)
      return true;
    var d = c === "bottom" ? u.top.y : 0, f = c === "top" ? u.bottom.y : 0, h2 = c === "right" ? u.left.x : 0, p2 = c === "left" ? u.right.x : 0, m = s.top - r + d > a, g = r - s.bottom - f > a, y = s.left - t + h2 > a, w = t - s.right - p2 > a;
    return m || g || y || w;
  });
}
function To(n2, e, t) {
  var r = e + "EventListener";
  ["transitionend", "webkitTransitionEnd"].forEach(function(i) {
    n2[r](i, t);
  });
}
function Wc(n2, e) {
  for (var t = e; t; ) {
    var r;
    if (n2.contains(t))
      return true;
    t = t.getRootNode == null || (r = t.getRootNode()) == null ? void 0 : r.host;
  }
  return false;
}
var ft = {
  isTouch: false
}, Uc = 0;
function t1() {
  ft.isTouch || (ft.isTouch = true, window.performance && document.addEventListener("mousemove", Ff));
}
function Ff() {
  var n2 = performance.now();
  n2 - Uc < 20 && (ft.isTouch = false, document.removeEventListener("mousemove", Ff)), Uc = n2;
}
function n1() {
  var n2 = document.activeElement;
  if (Xb(n2)) {
    var e = n2._tippy;
    n2.blur && !e.state.isVisible && n2.blur();
  }
}
function r1() {
  document.addEventListener("touchstart", t1, hn), window.addEventListener("blur", n1);
}
var i1 = typeof window < "u" && typeof document < "u", s1 = i1 ? (
  // @ts-ignore
  !!window.msCrypto
) : false;
var Vf = {
  animateFill: false,
  followCursor: false,
  inlinePositioning: false,
  sticky: false
}, c1 = {
  allowHTML: false,
  animation: "fade",
  arrow: true,
  content: "",
  inertia: false,
  maxWidth: 350,
  role: "tooltip",
  theme: "",
  zIndex: 9999
}, $e = Object.assign({
  appendTo: Bf,
  aria: {
    content: "auto",
    expanded: "auto"
  },
  delay: 0,
  duration: [300, 250],
  getReferenceClientRect: null,
  hideOnClick: true,
  ignoreAttributes: false,
  interactive: false,
  interactiveBorder: 2,
  interactiveDebounce: 0,
  moveTransition: "",
  offset: [0, 10],
  onAfterUpdate: function() {
  },
  onBeforeUpdate: function() {
  },
  onCreate: function() {
  },
  onDestroy: function() {
  },
  onHidden: function() {
  },
  onHide: function() {
  },
  onMount: function() {
  },
  onShow: function() {
  },
  onShown: function() {
  },
  onTrigger: function() {
  },
  onUntrigger: function() {
  },
  onClickOutside: function() {
  },
  placement: "top",
  plugins: [],
  popperOptions: {},
  render: null,
  showOnCreate: false,
  touch: true,
  trigger: "mouseenter focus",
  triggerTarget: null
}, Vf, c1), u1 = Object.keys($e), d1 = function(e) {
  var t = Object.keys(e);
  t.forEach(function(r) {
    $e[r] = e[r];
  });
};
function $f(n2) {
  var e = n2.plugins || [], t = e.reduce(function(r, i) {
    var s = i.name, o = i.defaultValue;
    if (s) {
      var l;
      r[s] = n2[s] !== void 0 ? n2[s] : (l = $e[s]) != null ? l : o;
    }
    return r;
  }, {});
  return Object.assign({}, n2, t);
}
function f1(n2, e) {
  var t = e ? Object.keys($f(Object.assign({}, $e, {
    plugins: e
  }))) : u1, r = t.reduce(function(i, s) {
    var o = (n2.getAttribute("data-tippy-" + s) || "").trim();
    if (!o)
      return i;
    if (s === "content")
      i[s] = o;
    else
      try {
        i[s] = JSON.parse(o);
      } catch {
        i[s] = o;
      }
    return i;
  }, {});
  return r;
}
function qc(n2, e) {
  var t = Object.assign({}, e, {
    content: Hf(e.content, [n2])
  }, e.ignoreAttributes ? {} : f1(n2, e.plugins));
  return t.aria = Object.assign({}, $e.aria, t.aria), t.aria = {
    expanded: t.aria.expanded === "auto" ? e.interactive : t.aria.expanded,
    content: t.aria.content === "auto" ? e.interactive ? null : "describedby" : t.aria.content
  }, t;
}
var h1 = function() {
  return "innerHTML";
};
function cl(n2, e) {
  n2[h1()] = e;
}
function Jc(n2) {
  var e = Ir();
  return n2 === true ? e.className = If : (e.className = Pf, Zr(n2) ? e.appendChild(n2) : cl(e, n2)), e;
}
function Gc(n2, e) {
  Zr(e.content) ? (cl(n2, ""), n2.appendChild(e.content)) : typeof e.content != "function" && (e.allowHTML ? cl(n2, e.content) : n2.textContent = e.content);
}
function ul(n2) {
  var e = n2.firstElementChild, t = ls(e.children);
  return {
    box: e,
    content: t.find(function(r) {
      return r.classList.contains(Rf);
    }),
    arrow: t.find(function(r) {
      return r.classList.contains(If) || r.classList.contains(Pf);
    }),
    backdrop: t.find(function(r) {
      return r.classList.contains(jb);
    })
  };
}
function jf(n2) {
  var e = Ir(), t = Ir();
  t.className = _b, t.setAttribute("data-state", "hidden"), t.setAttribute("tabindex", "-1");
  var r = Ir();
  r.className = Rf, r.setAttribute("data-state", "hidden"), Gc(r, n2.props), e.appendChild(t), t.appendChild(r), i(n2.props, n2.props);
  function i(s, o) {
    var l = ul(e), a = l.box, c = l.content, u = l.arrow;
    o.theme ? a.setAttribute("data-theme", o.theme) : a.removeAttribute("data-theme"), typeof o.animation == "string" ? a.setAttribute("data-animation", o.animation) : a.removeAttribute("data-animation"), o.inertia ? a.setAttribute("data-inertia", "") : a.removeAttribute("data-inertia"), a.style.maxWidth = typeof o.maxWidth == "number" ? o.maxWidth + "px" : o.maxWidth, o.role ? a.setAttribute("role", o.role) : a.removeAttribute("role"), (s.content !== o.content || s.allowHTML !== o.allowHTML) && Gc(c, n2.props), o.arrow ? u ? s.arrow !== o.arrow && (a.removeChild(u), a.appendChild(Jc(o.arrow))) : a.appendChild(Jc(o.arrow)) : u && a.removeChild(u);
  }
  return {
    popper: e,
    onUpdate: i
  };
}
jf.$$tippy = true;
var p1 = 1, Ti = [], Oo = [];
function m1(n2, e) {
  var t = qc(n2, Object.assign({}, $e, $f(_c(e)))), r, i, s, o = false, l = false, a = false, c = false, u, d, f, h2 = [], p2 = Vc(ui, t.interactiveDebounce), m, g = p1++, y = null, w = qb(t.plugins), C = {
    // Is the instance currently enabled?
    isEnabled: true,
    // Is the tippy currently showing and not transitioning out?
    isVisible: false,
    // Has the instance been destroyed?
    isDestroyed: false,
    // Is the tippy currently mounted to the DOM?
    isMounted: false,
    // Has the tippy finished transitioning in?
    isShown: false
  }, b = {
    // properties
    id: g,
    reference: n2,
    popper: Ir(),
    popperInstance: y,
    props: t,
    state: C,
    plugins: w,
    // methods
    clearDelayTimeouts: mi,
    setProps: gi,
    setContent: yi,
    show: Th,
    hide: Oh,
    hideWithInteractivity: Nh,
    enable: wr,
    disable: pi,
    unmount: Dh,
    destroy: Lh
  };
  if (!t.render)
    return b;
  var S = t.render(b), k = S.popper, T = S.onUpdate;
  k.setAttribute("data-tippy-root", ""), k.id = "tippy-" + b.id, b.popper = k, n2._tippy = b, k._tippy = b;
  var M = w.map(function(x) {
    return x.fn(b);
  }), I = n2.hasAttribute("aria-expanded");
  return Vn(), Be(), ee(), ae("onCreate", [b]), t.showOnCreate && vr(), k.addEventListener("mouseenter", function() {
    b.props.interactive && b.state.isVisible && b.clearDelayTimeouts();
  }), k.addEventListener("mouseleave", function() {
    b.props.interactive && b.props.trigger.indexOf("mouseenter") >= 0 && J().addEventListener("mousemove", p2);
  }), b;
  function N() {
    var x = b.props.touch;
    return Array.isArray(x) ? x : [x, 0];
  }
  function j() {
    return N()[0] === "hold";
  }
  function K() {
    var x;
    return !!((x = b.props.render) != null && x.$$tippy);
  }
  function Y() {
    return m || n2;
  }
  function J() {
    var x = Y().parentNode;
    return x ? Zb(x) : document;
  }
  function Z() {
    return ul(k);
  }
  function G(x) {
    return b.state.isMounted && !b.state.isVisible || ft.isTouch || u && u.type === "focus" ? 0 : Ao(b.props.delay, x ? 0 : 1, $e.delay);
  }
  function ee(x) {
    x === void 0 && (x = false), k.style.pointerEvents = b.props.interactive && !x ? "" : "none", k.style.zIndex = "" + b.props.zIndex;
  }
  function ae(x, R, z) {
    if (z === void 0 && (z = true), M.forEach(function(q) {
      q[x] && q[x].apply(q, R);
    }), z) {
      var X;
      (X = b.props)[x].apply(X, R);
    }
  }
  function ye() {
    var x = b.props.aria;
    if (x.content) {
      var R = "aria-" + x.content, z = k.id, X = Gn(b.props.triggerTarget || n2);
      X.forEach(function(q) {
        var Ae = q.getAttribute(R);
        if (b.state.isVisible)
          q.setAttribute(R, Ae ? Ae + " " + z : z);
        else {
          var Ke = Ae && Ae.replace(z, "").trim();
          Ke ? q.setAttribute(R, Ke) : q.removeAttribute(R);
        }
      });
    }
  }
  function Be() {
    if (!(I || !b.props.aria.expanded)) {
      var x = Gn(b.props.triggerTarget || n2);
      x.forEach(function(R) {
        b.props.interactive ? R.setAttribute("aria-expanded", b.state.isVisible && R === Y() ? "true" : "false") : R.removeAttribute("aria-expanded");
      });
    }
  }
  function He() {
    J().removeEventListener("mousemove", p2), Ti = Ti.filter(function(x) {
      return x !== p2;
    });
  }
  function Ue(x) {
    if (!(ft.isTouch && (a || x.type === "mousedown"))) {
      var R = x.composedPath && x.composedPath()[0] || x.target;
      if (!(b.props.interactive && Wc(k, R))) {
        if (Gn(b.props.triggerTarget || n2).some(function(z) {
          return Wc(z, R);
        })) {
          if (ft.isTouch || b.state.isVisible && b.props.trigger.indexOf("click") >= 0)
            return;
        } else
          ae("onClickOutside", [b, x]);
        b.props.hideOnClick === true && (b.clearDelayTimeouts(), b.hide(), l = true, setTimeout(function() {
          l = false;
        }), b.state.isMounted || kt());
      }
    }
  }
  function tt2() {
    a = true;
  }
  function wt() {
    a = false;
  }
  function nt() {
    var x = J();
    x.addEventListener("mousedown", Ue, true), x.addEventListener("touchend", Ue, hn), x.addEventListener("touchstart", wt, hn), x.addEventListener("touchmove", tt2, hn);
  }
  function kt() {
    var x = J();
    x.removeEventListener("mousedown", Ue, true), x.removeEventListener("touchend", Ue, hn), x.removeEventListener("touchstart", wt, hn), x.removeEventListener("touchmove", tt2, hn);
  }
  function Fn(x, R) {
    zn(x, function() {
      !b.state.isVisible && k.parentNode && k.parentNode.contains(k) && R();
    });
  }
  function Ct(x, R) {
    zn(x, R);
  }
  function zn(x, R) {
    var z = Z().box;
    function X(q) {
      q.target === z && (To(z, "remove", X), R());
    }
    if (x === 0)
      return R();
    To(z, "remove", d), To(z, "add", X), d = X;
  }
  function Ht(x, R, z) {
    z === void 0 && (z = false);
    var X = Gn(b.props.triggerTarget || n2);
    X.forEach(function(q) {
      q.addEventListener(x, R, z), h2.push({
        node: q,
        eventType: x,
        handler: R,
        options: z
      });
    });
  }
  function Vn() {
    j() && (Ht("touchstart", gr, {
      passive: true
    }), Ht("touchend", di, {
      passive: true
    })), Kb(b.props.trigger).forEach(function(x) {
      if (x !== "manual")
        switch (Ht(x, gr), x) {
          case "mouseenter":
            Ht("mouseleave", di);
            break;
          case "focus":
            Ht(s1 ? "focusout" : "blur", yr);
            break;
          case "focusin":
            Ht("focusout", yr);
            break;
        }
    });
  }
  function ci() {
    h2.forEach(function(x) {
      var R = x.node, z = x.eventType, X = x.handler, q = x.options;
      R.removeEventListener(z, X, q);
    }), h2 = [];
  }
  function gr(x) {
    var R, z = false;
    if (!(!b.state.isEnabled || br(x) || l)) {
      var X = ((R = u) == null ? void 0 : R.type) === "focus";
      u = x, m = x.currentTarget, Be(), !b.state.isVisible && Yb(x) && Ti.forEach(function(q) {
        return q(x);
      }), x.type === "click" && (b.props.trigger.indexOf("mouseenter") < 0 || o) && b.props.hideOnClick !== false && b.state.isVisible ? z = true : vr(x), x.type === "click" && (o = !z), z && !X && un(x);
    }
  }
  function ui(x) {
    var R = x.target, z = Y().contains(R) || k.contains(R);
    if (!(x.type === "mousemove" && z)) {
      var X = Ft().concat(k).map(function(q) {
        var Ae, Ke = q._tippy, $n = (Ae = Ke.popperInstance) == null ? void 0 : Ae.state;
        return $n ? {
          popperRect: q.getBoundingClientRect(),
          popperState: $n,
          props: t
        } : null;
      }).filter(Boolean);
      e1(X, x) && (He(), un(x));
    }
  }
  function di(x) {
    var R = br(x) || b.props.trigger.indexOf("click") >= 0 && o;
    if (!R) {
      if (b.props.interactive) {
        b.hideWithInteractivity(x);
        return;
      }
      un(x);
    }
  }
  function yr(x) {
    b.props.trigger.indexOf("focusin") < 0 && x.target !== Y() || b.props.interactive && x.relatedTarget && k.contains(x.relatedTarget) || un(x);
  }
  function br(x) {
    return ft.isTouch ? j() !== x.type.indexOf("touch") >= 0 : false;
  }
  function fi() {
    hi();
    var x = b.props, R = x.popperOptions, z = x.placement, X = x.offset, q = x.getReferenceClientRect, Ae = x.moveTransition, Ke = K() ? ul(k).arrow : null, $n = q ? {
      getBoundingClientRect: q,
      contextElement: q.contextElement || Y()
    } : n2, Ca = {
      name: "$$tippy",
      enabled: true,
      phase: "beforeWrite",
      requires: ["computeStyles"],
      fn: function(bi) {
        var _n = bi.state;
        if (K()) {
          var Rh = Z(), oo = Rh.box;
          ["placement", "reference-hidden", "escaped"].forEach(function(vi) {
            vi === "placement" ? oo.setAttribute("data-placement", _n.placement) : _n.attributes.popper["data-popper-" + vi] ? oo.setAttribute("data-" + vi, "") : oo.removeAttribute("data-" + vi);
          }), _n.attributes.popper = {};
        }
      }
    }, dn = [{
      name: "offset",
      options: {
        offset: X
      }
    }, {
      name: "preventOverflow",
      options: {
        padding: {
          top: 2,
          bottom: 2,
          left: 5,
          right: 5
        }
      }
    }, {
      name: "flip",
      options: {
        padding: 5
      }
    }, {
      name: "computeStyles",
      options: {
        adaptive: !Ae
      }
    }, Ca];
    K() && Ke && dn.push({
      name: "arrow",
      options: {
        element: Ke,
        padding: 3
      }
    }), dn.push.apply(dn, (R == null ? void 0 : R.modifiers) || []), b.popperInstance = $b($n, k, Object.assign({}, R, {
      placement: z,
      onFirstUpdate: f,
      modifiers: dn
    }));
  }
  function hi() {
    b.popperInstance && (b.popperInstance.destroy(), b.popperInstance = null);
  }
  function xt() {
    var x = b.props.appendTo, R, z = Y();
    b.props.interactive && x === Bf || x === "parent" ? R = z.parentNode : R = Hf(x, [z]), R.contains(k) || R.appendChild(k), b.state.isMounted = true, fi();
  }
  function Ft() {
    return ls(k.querySelectorAll("[data-tippy-root]"));
  }
  function vr(x) {
    b.clearDelayTimeouts(), x && ae("onTrigger", [b, x]), nt();
    var R = G(true), z = N(), X = z[0], q = z[1];
    ft.isTouch && X === "hold" && q && (R = q), R ? r = setTimeout(function() {
      b.show();
    }, R) : b.show();
  }
  function un(x) {
    if (b.clearDelayTimeouts(), ae("onUntrigger", [b, x]), !b.state.isVisible) {
      kt();
      return;
    }
    if (!(b.props.trigger.indexOf("mouseenter") >= 0 && b.props.trigger.indexOf("click") >= 0 && ["mouseleave", "mousemove"].indexOf(x.type) >= 0 && o)) {
      var R = G(false);
      R ? i = setTimeout(function() {
        b.state.isVisible && b.hide();
      }, R) : s = requestAnimationFrame(function() {
        b.hide();
      });
    }
  }
  function wr() {
    b.state.isEnabled = true;
  }
  function pi() {
    b.hide(), b.state.isEnabled = false;
  }
  function mi() {
    clearTimeout(r), clearTimeout(i), cancelAnimationFrame(s);
  }
  function gi(x) {
    if (!b.state.isDestroyed) {
      ae("onBeforeUpdate", [b, x]), ci();
      var R = b.props, z = qc(n2, Object.assign({}, R, _c(x), {
        ignoreAttributes: true
      }));
      b.props = z, Vn(), R.interactiveDebounce !== z.interactiveDebounce && (He(), p2 = Vc(ui, z.interactiveDebounce)), R.triggerTarget && !z.triggerTarget ? Gn(R.triggerTarget).forEach(function(X) {
        X.removeAttribute("aria-expanded");
      }) : z.triggerTarget && n2.removeAttribute("aria-expanded"), Be(), ee(), T && T(R, z), b.popperInstance && (fi(), Ft().forEach(function(X) {
        requestAnimationFrame(X._tippy.popperInstance.forceUpdate);
      })), ae("onAfterUpdate", [b, x]);
    }
  }
  function yi(x) {
    b.setProps({
      content: x
    });
  }
  function Th() {
    var x = b.state.isVisible, R = b.state.isDestroyed, z = !b.state.isEnabled, X = ft.isTouch && !b.props.touch, q = Ao(b.props.duration, 0, $e.duration);
    if (!(x || R || z || X) && !Y().hasAttribute("disabled") && (ae("onShow", [b], false), b.props.onShow(b) !== false)) {
      if (b.state.isVisible = true, K() && (k.style.visibility = "visible"), ee(), nt(), b.state.isMounted || (k.style.transition = "none"), K()) {
        var Ae = Z(), Ke = Ae.box, $n = Ae.content;
        Eo([Ke, $n], 0);
      }
      f = function() {
        var dn;
        if (!(!b.state.isVisible || c)) {
          if (c = true, k.offsetHeight, k.style.transition = b.props.moveTransition, K() && b.props.animation) {
            var so = Z(), bi = so.box, _n = so.content;
            Eo([bi, _n], q), jc([bi, _n], "visible");
          }
          ye(), Be(), $c(Oo, b), (dn = b.popperInstance) == null || dn.forceUpdate(), ae("onMount", [b]), b.props.animation && K() && Ct(q, function() {
            b.state.isShown = true, ae("onShown", [b]);
          });
        }
      }, xt();
    }
  }
  function Oh() {
    var x = !b.state.isVisible, R = b.state.isDestroyed, z = !b.state.isEnabled, X = Ao(b.props.duration, 1, $e.duration);
    if (!(x || R || z) && (ae("onHide", [b], false), b.props.onHide(b) !== false)) {
      if (b.state.isVisible = false, b.state.isShown = false, c = false, o = false, K() && (k.style.visibility = "hidden"), He(), kt(), ee(true), K()) {
        var q = Z(), Ae = q.box, Ke = q.content;
        b.props.animation && (Eo([Ae, Ke], X), jc([Ae, Ke], "hidden"));
      }
      ye(), Be(), b.props.animation ? K() && Fn(X, b.unmount) : b.unmount();
    }
  }
  function Nh(x) {
    J().addEventListener("mousemove", p2), $c(Ti, p2), p2(x);
  }
  function Dh() {
    b.state.isVisible && b.hide(), b.state.isMounted && (hi(), Ft().forEach(function(x) {
      x._tippy.unmount();
    }), k.parentNode && k.parentNode.removeChild(k), Oo = Oo.filter(function(x) {
      return x !== b;
    }), b.state.isMounted = false, ae("onHidden", [b]));
  }
  function Lh() {
    !b.state.isDestroyed && (b.clearDelayTimeouts(), b.unmount(), ci(), delete n2._tippy, b.state.isDestroyed = true, ae("onDestroy", [b]));
  }
}
function Bn(n2, e) {
  e === void 0 && (e = {});
  var t = $e.plugins.concat(e.plugins || []);
  r1();
  var r = Object.assign({}, e, {
    plugins: t
  }), i = Qb(n2);
  var l = i.reduce(function(a, c) {
    var u = c && m1(c, r);
    return u && a.push(u), a;
  }, []);
  return Zr(n2) ? l[0] : l;
}
Bn.defaultProps = $e;
Bn.setDefaultProps = d1;
Bn.currentInput = ft;
Object.assign({}, Mf, {
  effect: function(e) {
    var t = e.state, r = {
      popper: {
        position: t.options.strategy,
        left: "0",
        top: "0",
        margin: "0"
      },
      arrow: {
        position: "absolute"
      },
      reference: {}
    };
    Object.assign(t.elements.popper.style, r.popper), t.styles = r, t.elements.arrow && Object.assign(t.elements.arrow.style, r.arrow);
  }
});
Bn.setDefaultProps({
  render: jf
});
class g1 {
  constructor({ editor: e, element: t, view: r, tippyOptions: i = {}, updateDelay: s = 250, shouldShow: o }) {
    this.preventHide = false, this.shouldShow = ({ view: l, state: a, from: c, to: u }) => {
      const { doc: d, selection: f } = a, { empty: h2 } = f, p2 = !d.textBetween(c, u).length && ta(a.selection), m = this.element.contains(document.activeElement);
      return !(!(l.hasFocus() || m) || h2 || p2 || !this.editor.isEditable);
    }, this.mousedownHandler = () => {
      this.preventHide = true;
    }, this.dragstartHandler = () => {
      this.hide();
    }, this.focusHandler = () => {
      setTimeout(() => this.update(this.editor.view));
    }, this.blurHandler = ({ event: l }) => {
      var a;
      if (this.preventHide) {
        this.preventHide = false;
        return;
      }
      l != null && l.relatedTarget && (!((a = this.element.parentNode) === null || a === void 0) && a.contains(l.relatedTarget)) || (l == null ? void 0 : l.relatedTarget) !== this.editor.view.dom && this.hide();
    }, this.tippyBlurHandler = (l) => {
      this.blurHandler({ event: l });
    }, this.handleDebouncedUpdate = (l, a) => {
      const c = !(a != null && a.selection.eq(l.state.selection)), u = !(a != null && a.doc.eq(l.state.doc));
      !c && !u || (this.updateDebounceTimer && clearTimeout(this.updateDebounceTimer), this.updateDebounceTimer = window.setTimeout(() => {
        this.updateHandler(l, c, u, a);
      }, this.updateDelay));
    }, this.updateHandler = (l, a, c, u) => {
      var d, f, h2;
      const { state: p2, composing: m } = l, { selection: g } = p2;
      if (m || !a && !c)
        return;
      this.createTooltip();
      const { ranges: w } = g, C = Math.min(...w.map((k) => k.$from.pos)), b = Math.max(...w.map((k) => k.$to.pos));
      if (!((d = this.shouldShow) === null || d === void 0 ? void 0 : d.call(this, {
        editor: this.editor,
        element: this.element,
        view: l,
        state: p2,
        oldState: u,
        from: C,
        to: b
      }))) {
        this.hide();
        return;
      }
      (f = this.tippy) === null || f === void 0 || f.setProps({
        getReferenceClientRect: ((h2 = this.tippyOptions) === null || h2 === void 0 ? void 0 : h2.getReferenceClientRect) || (() => {
          if (t0(p2.selection)) {
            let k = l.nodeDOM(C);
            if (k) {
              const T = k.dataset.nodeViewWrapper ? k : k.querySelector("[data-node-view-wrapper]");
              if (T && (k = T.firstChild), k)
                return k.getBoundingClientRect();
            }
          }
          return Cf(l, C, b);
        })
      }), this.show();
    }, this.editor = e, this.element = t, this.view = r, this.updateDelay = s, o && (this.shouldShow = o), this.element.addEventListener("mousedown", this.mousedownHandler, { capture: true }), this.view.dom.addEventListener("dragstart", this.dragstartHandler), this.editor.on("focus", this.focusHandler), this.editor.on("blur", this.blurHandler), this.tippyOptions = i, this.element.remove(), this.element.style.visibility = "visible";
  }
  createTooltip() {
    const { element: e } = this.editor.options, t = !!e.parentElement;
    this.tippy || !t || (this.tippy = Bn(e, {
      duration: 0,
      getReferenceClientRect: null,
      content: this.element,
      interactive: true,
      trigger: "manual",
      placement: "top",
      hideOnClick: "toggle",
      ...this.tippyOptions
    }), this.tippy.popper.firstChild && this.tippy.popper.firstChild.addEventListener("blur", this.tippyBlurHandler));
  }
  update(e, t) {
    const { state: r } = e, i = r.selection.from !== r.selection.to;
    if (this.updateDelay > 0 && i) {
      this.handleDebouncedUpdate(e, t);
      return;
    }
    const s = !(t != null && t.selection.eq(e.state.selection)), o = !(t != null && t.doc.eq(e.state.doc));
    this.updateHandler(e, s, o, t);
  }
  show() {
    var e;
    (e = this.tippy) === null || e === void 0 || e.show();
  }
  hide() {
    var e;
    (e = this.tippy) === null || e === void 0 || e.hide();
  }
  destroy() {
    var e, t;
    !((e = this.tippy) === null || e === void 0) && e.popper.firstChild && this.tippy.popper.firstChild.removeEventListener("blur", this.tippyBlurHandler), (t = this.tippy) === null || t === void 0 || t.destroy(), this.element.removeEventListener("mousedown", this.mousedownHandler, { capture: true }), this.view.dom.removeEventListener("dragstart", this.dragstartHandler), this.editor.off("focus", this.focusHandler), this.editor.off("blur", this.blurHandler);
  }
}
const Wf = (n2) => new le({
  key: typeof n2.pluginKey == "string" ? new ue(n2.pluginKey) : n2.pluginKey,
  view: (e) => new g1({ view: e, ...n2 })
});
fe.create({
  name: "bubbleMenu",
  addOptions() {
    return {
      element: null,
      tippyOptions: {},
      pluginKey: "bubbleMenu",
      updateDelay: void 0,
      shouldShow: null
    };
  },
  addProseMirrorPlugins() {
    return this.options.element ? [
      Wf({
        pluginKey: this.options.pluginKey,
        editor: this.editor,
        element: this.options.element,
        tippyOptions: this.options.tippyOptions,
        updateDelay: this.options.updateDelay,
        shouldShow: this.options.shouldShow
      })
    ] : [];
  }
});
class y1 {
  getTextContent(e) {
    return wf(e, { textSerializers: Zl(this.editor.schema) });
  }
  constructor({ editor: e, element: t, view: r, tippyOptions: i = {}, shouldShow: s }) {
    this.preventHide = false, this.shouldShow = ({ view: o, state: l }) => {
      const { selection: a } = l, { $anchor: c, empty: u } = a, d = c.depth === 1, f = c.parent.isTextblock && !c.parent.type.spec.code && !c.parent.textContent && c.parent.childCount === 0 && !this.getTextContent(c.parent);
      return !(!o.hasFocus() || !u || !d || !f || !this.editor.isEditable);
    }, this.mousedownHandler = () => {
      this.preventHide = true;
    }, this.focusHandler = () => {
      setTimeout(() => this.update(this.editor.view));
    }, this.blurHandler = ({ event: o }) => {
      var l;
      if (this.preventHide) {
        this.preventHide = false;
        return;
      }
      o != null && o.relatedTarget && (!((l = this.element.parentNode) === null || l === void 0) && l.contains(o.relatedTarget)) || (o == null ? void 0 : o.relatedTarget) !== this.editor.view.dom && this.hide();
    }, this.tippyBlurHandler = (o) => {
      this.blurHandler({ event: o });
    }, this.editor = e, this.element = t, this.view = r, s && (this.shouldShow = s), this.element.addEventListener("mousedown", this.mousedownHandler, { capture: true }), this.editor.on("focus", this.focusHandler), this.editor.on("blur", this.blurHandler), this.tippyOptions = i, this.element.remove(), this.element.style.visibility = "visible";
  }
  createTooltip() {
    const { element: e } = this.editor.options, t = !!e.parentElement;
    this.tippy || !t || (this.tippy = Bn(e, {
      duration: 0,
      getReferenceClientRect: null,
      content: this.element,
      interactive: true,
      trigger: "manual",
      placement: "right",
      hideOnClick: "toggle",
      ...this.tippyOptions
    }), this.tippy.popper.firstChild && this.tippy.popper.firstChild.addEventListener("blur", this.tippyBlurHandler));
  }
  update(e, t) {
    var r, i, s;
    const { state: o } = e, { doc: l, selection: a } = o, { from: c, to: u } = a;
    if (t && t.doc.eq(l) && t.selection.eq(a))
      return;
    if (this.createTooltip(), !((r = this.shouldShow) === null || r === void 0 ? void 0 : r.call(this, {
      editor: this.editor,
      view: e,
      state: o,
      oldState: t
    }))) {
      this.hide();
      return;
    }
    (i = this.tippy) === null || i === void 0 || i.setProps({
      getReferenceClientRect: ((s = this.tippyOptions) === null || s === void 0 ? void 0 : s.getReferenceClientRect) || (() => Cf(e, c, u))
    }), this.show();
  }
  show() {
    var e;
    (e = this.tippy) === null || e === void 0 || e.show();
  }
  hide() {
    var e;
    (e = this.tippy) === null || e === void 0 || e.hide();
  }
  destroy() {
    var e, t;
    !((e = this.tippy) === null || e === void 0) && e.popper.firstChild && this.tippy.popper.firstChild.removeEventListener("blur", this.tippyBlurHandler), (t = this.tippy) === null || t === void 0 || t.destroy(), this.element.removeEventListener("mousedown", this.mousedownHandler, { capture: true }), this.editor.off("focus", this.focusHandler), this.editor.off("blur", this.blurHandler);
  }
}
const Uf = (n2) => new le({
  key: typeof n2.pluginKey == "string" ? new ue(n2.pluginKey) : n2.pluginKey,
  view: (e) => new y1({ view: e, ...n2 })
});
fe.create({
  name: "floatingMenu",
  addOptions() {
    return {
      element: null,
      tippyOptions: {},
      pluginKey: "floatingMenu",
      shouldShow: null
    };
  },
  addProseMirrorPlugins() {
    return this.options.element ? [
      Uf({
        pluginKey: this.options.pluginKey,
        editor: this.editor,
        element: this.options.element,
        tippyOptions: this.options.tippyOptions,
        shouldShow: this.options.shouldShow
      })
    ] : [];
  }
});
const b1 = /* @__PURE__ */ defineComponent({
  name: "BubbleMenu",
  props: {
    pluginKey: {
      type: [String, Object],
      default: "bubbleMenu"
    },
    editor: {
      type: Object,
      required: true
    },
    updateDelay: {
      type: Number,
      default: void 0
    },
    tippyOptions: {
      type: Object,
      default: () => ({})
    },
    shouldShow: {
      type: Function,
      default: null
    }
  },
  setup(n2, { slots: e }) {
    const t = ref(null);
    return onMounted(() => {
      const { updateDelay: r, editor: i, pluginKey: s, shouldShow: o, tippyOptions: l } = n2;
      i.registerPlugin(Wf({
        updateDelay: r,
        editor: i,
        element: t.value,
        pluginKey: s,
        shouldShow: o,
        tippyOptions: l
      }));
    }), onBeforeUnmount(() => {
      const { pluginKey: r, editor: i } = n2;
      i.unregisterPlugin(r);
    }), () => {
      var r;
      return h("div", { ref: t }, (r = e.default) === null || r === void 0 ? void 0 : r.call(e));
    };
  }
});
function Yc(n2) {
  return customRef((e, t) => ({
    get() {
      return e(), n2;
    },
    set(r) {
      n2 = r, requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          t();
        });
      });
    }
  }));
}
class Kf extends L0 {
  constructor(e = {}) {
    return super(e), this.contentComponent = null, this.appContext = null, this.reactiveState = Yc(this.view.state), this.reactiveExtensionStorage = Yc(this.extensionStorage), this.on("beforeTransaction", ({ nextState: t }) => {
      this.reactiveState.value = t, this.reactiveExtensionStorage.value = this.extensionStorage;
    }), markRaw(this);
  }
  get state() {
    return this.reactiveState ? this.reactiveState.value : this.view.state;
  }
  get storage() {
    return this.reactiveExtensionStorage ? this.reactiveExtensionStorage.value : super.storage;
  }
  /**
   * Register a ProseMirror plugin.
   */
  registerPlugin(e, t) {
    const r = super.registerPlugin(e, t);
    return this.reactiveState && (this.reactiveState.value = r), r;
  }
  /**
   * Unregister a ProseMirror plugin.
   */
  unregisterPlugin(e) {
    const t = super.unregisterPlugin(e);
    return this.reactiveState && t && (this.reactiveState.value = t), t;
  }
}
const v1 = /* @__PURE__ */ defineComponent({
  name: "EditorContent",
  props: {
    editor: {
      default: null,
      type: Object
    }
  },
  setup(n2) {
    const e = ref(), t = getCurrentInstance();
    return watchEffect(() => {
      const r = n2.editor;
      r && r.options.element && e.value && nextTick(() => {
        if (!e.value || !r.options.element.firstChild)
          return;
        const i = unref(e.value);
        e.value.append(...r.options.element.childNodes), r.contentComponent = t.ctx._, t && (r.appContext = {
          ...t.appContext,
          // Vue internally uses prototype chain to forward/shadow injects across the entire component chain
          // so don't use object spread operator or 'Object.assign' and just set `provides` as is on editor's appContext
          // @ts-expect-error forward instance's 'provides' into appContext
          provides: t.provides
        }), r.setOptions({
          element: i
        }), r.createNodeViews();
      });
    }), onBeforeUnmount(() => {
      const r = n2.editor;
      r && (r.contentComponent = null, r.appContext = null);
    }), { rootEl: e };
  },
  render() {
    return h("div", {
      ref: (n2) => {
        this.rootEl = n2;
      }
    });
  }
});
const w1 = /* @__PURE__ */ defineComponent({
  name: "NodeViewContent",
  props: {
    as: {
      type: String,
      default: "div"
    }
  },
  render() {
    return h(this.as, {
      style: {
        whiteSpace: "pre-wrap"
      },
      "data-node-view-content": ""
    });
  }
}), k1 = /* @__PURE__ */ defineComponent({
  name: "NodeViewWrapper",
  props: {
    as: {
      type: String,
      default: "div"
    }
  },
  inject: ["onDragStart", "decorationClasses"],
  render() {
    var n2, e;
    return h(this.as, {
      // @ts-ignore
      class: this.decorationClasses,
      style: {
        whiteSpace: "normal"
      },
      "data-node-view-wrapper": "",
      // @ts-ignore (https://github.com/vuejs/vue-next/issues/3031)
      onDragstart: this.onDragStart
    }, (e = (n2 = this.$slots).default) === null || e === void 0 ? void 0 : e.call(n2));
  }
});
class qf {
  constructor(e, { props: t = {}, editor: r }) {
    this.editor = r, this.component = markRaw(e), this.el = document.createElement("div"), this.props = reactive(t), this.renderedComponent = this.renderComponent();
  }
  get element() {
    return this.renderedComponent.el;
  }
  get ref() {
    var e, t, r, i;
    return !((t = (e = this.renderedComponent.vNode) === null || e === void 0 ? void 0 : e.component) === null || t === void 0) && t.exposed ? this.renderedComponent.vNode.component.exposed : (i = (r = this.renderedComponent.vNode) === null || r === void 0 ? void 0 : r.component) === null || i === void 0 ? void 0 : i.proxy;
  }
  renderComponent() {
    let e = h(this.component, this.props);
    return this.editor.appContext && (e.appContext = this.editor.appContext), typeof document < "u" && this.el && render(e, this.el), { vNode: e, destroy: () => {
      this.el && render(null, this.el), this.el = null, e = null;
    }, el: this.el ? this.el.firstElementChild : null };
  }
  updateProps(e = {}) {
    Object.entries(e).forEach(([t, r]) => {
      this.props[t] = r;
    }), this.renderComponent();
  }
  destroy() {
    this.renderedComponent.destroy();
  }
}
class C1 extends I0 {
  mount() {
    const e = {
      editor: this.editor,
      node: this.node,
      decorations: this.decorations,
      innerDecorations: this.innerDecorations,
      view: this.view,
      selected: false,
      extension: this.extension,
      HTMLAttributes: this.HTMLAttributes,
      getPos: () => this.getPos(),
      updateAttributes: (i = {}) => this.updateAttributes(i),
      deleteNode: () => this.deleteNode()
    }, t = this.onDragStart.bind(this);
    this.decorationClasses = ref(this.getDecorationClasses());
    const r = /* @__PURE__ */ defineComponent({
      extends: { ...this.component },
      props: Object.keys(e),
      template: this.component.template,
      setup: (i) => {
        var s, o;
        return provide("onDragStart", t), provide("decorationClasses", this.decorationClasses), (o = (s = this.component).setup) === null || o === void 0 ? void 0 : o.call(s, i, {
          expose: () => {
          }
        });
      },
      // add support for scoped styles
      // @ts-ignore
      // eslint-disable-next-line
      __scopeId: this.component.__scopeId,
      // add support for CSS Modules
      // @ts-ignore
      // eslint-disable-next-line
      __cssModules: this.component.__cssModules,
      // add support for vue devtools
      // @ts-ignore
      // eslint-disable-next-line
      __name: this.component.__name,
      // @ts-ignore
      // eslint-disable-next-line
      __file: this.component.__file
    });
    this.handleSelectionUpdate = this.handleSelectionUpdate.bind(this), this.editor.on("selectionUpdate", this.handleSelectionUpdate), this.renderer = new qf(r, {
      editor: this.editor,
      props: e
    });
  }
  /**
   * Return the DOM element.
   * This is the element that will be used to display the node view.
   */
  get dom() {
    if (!this.renderer.element || !this.renderer.element.hasAttribute("data-node-view-wrapper"))
      throw Error("Please use the NodeViewWrapper component for your node view.");
    return this.renderer.element;
  }
  /**
   * Return the content DOM element.
   * This is the element that will be used to display the rich-text content of the node.
   */
  get contentDOM() {
    return this.node.isLeaf ? null : this.dom.querySelector("[data-node-view-content]");
  }
  /**
   * On editor selection update, check if the node is selected.
   * If it is, call `selectNode`, otherwise call `deselectNode`.
   */
  handleSelectionUpdate() {
    const { from: e, to: t } = this.editor.state.selection, r = this.getPos();
    if (typeof r == "number")
      if (e <= r && t >= r + this.node.nodeSize) {
        if (this.renderer.props.selected)
          return;
        this.selectNode();
      } else {
        if (!this.renderer.props.selected)
          return;
        this.deselectNode();
      }
  }
  /**
   * On update, update the React component.
   * To prevent unnecessary updates, the `update` option can be used.
   */
  update(e, t, r) {
    const i = (s) => {
      this.decorationClasses.value = this.getDecorationClasses(), this.renderer.updateProps(s);
    };
    if (typeof this.options.update == "function") {
      const s = this.node, o = this.decorations, l = this.innerDecorations;
      return this.node = e, this.decorations = t, this.innerDecorations = r, this.options.update({
        oldNode: s,
        oldDecorations: o,
        newNode: e,
        newDecorations: t,
        oldInnerDecorations: l,
        innerDecorations: r,
        updateProps: () => i({ node: e, decorations: t, innerDecorations: r })
      });
    }
    return e.type !== this.node.type ? false : (e === this.node && this.decorations === t && this.innerDecorations === r || (this.node = e, this.decorations = t, this.innerDecorations = r, i({ node: e, decorations: t, innerDecorations: r })), true);
  }
  /**
   * Select the node.
   * Add the `selected` prop and the `ProseMirror-selectednode` class.
   */
  selectNode() {
    this.renderer.updateProps({
      selected: true
    }), this.renderer.element && this.renderer.element.classList.add("ProseMirror-selectednode");
  }
  /**
   * Deselect the node.
   * Remove the `selected` prop and the `ProseMirror-selectednode` class.
   */
  deselectNode() {
    this.renderer.updateProps({
      selected: false
    }), this.renderer.element && this.renderer.element.classList.remove("ProseMirror-selectednode");
  }
  getDecorationClasses() {
    return this.decorations.map((e) => e.type.attrs.class).flat().join(" ");
  }
  destroy() {
    this.renderer.destroy(), this.editor.off("selectionUpdate", this.handleSelectionUpdate);
  }
}
function x1(n2, e) {
  return (t) => {
    if (!t.editor.contentComponent)
      return {};
    const r = typeof n2 == "function" && "__vccOpts" in n2 ? n2.__vccOpts : n2;
    return new C1(r, t, e);
  };
}
const S1 = {
  toolbar: {
    headings: {
      normal: "Normal",
      h1: "Titre 1",
      h2: "Titre 2",
      h3: "Titre 3"
    },
    undo: "Annuler",
    bold: "Gras",
    italic: "Italique",
    underline: "Souligné",
    strike: "Barré",
    highlight: "Surligner",
    textColor: "Couleur du texte",
    align: {
      left: "Aligner à gauche",
      center: "Centrer",
      right: "Aligner à droite",
      justify: "Justifier"
    },
    list: {
      bullet: "Liste à puces",
      ordered: "Liste ordonnée"
    },
    link: {
      title: "Lien",
      modal_title: "Insérer un lien",
      url: "Saisissez une URL"
    },
    image: {
      title: "Image",
      import: "Importer",
      url: "Via URL",
      url_title: "Lien",
      url_insert: "Insérer",
      media: "Média",
      modal_title: "Insérer une image",
      import_drag: "Glissez déposez ou",
      import_download: "cliquez pour télécharger",
      youtube: "Vidéo"
    },
    table: {
      modal_title: "Insérer un tableau",
      columns: "Colonnes",
      rows: "Lignes",
      header: "Inclure en-tête",
      delete: "Supprimer",
      add_column_before: "Ajouter une colonne avant"
    },
    video: {
      modal_title: "Insérer une vidéo",
      url: "Saisissez une URL de vidéo"
    },
    insert: "Insérer",
    format: "Format",
    panel: {
      title: "Volet d'informations",
      type: {
        info: "Informations",
        warning: "Avertissement",
        error: "Erreur"
      }
    }
  },
  placeholder: {
    default: "Commencez à écrire..."
  },
  modal: {
    close: "Fermer"
  },
  mediaLibrary: {
    title: "Bibliothèque de médias",
    upload: "Télécharger",
    url: "URL",
    files: "Fichiers",
    file: "Fichier",
    search: {
      placeholder: "Tapez pour rechercher...",
      button: "Rechercher"
    },
    attributes: {
      title: "Informations",
      dimensions: "Dimensions",
      uploaded_by: "Téléchargé par"
    },
    actions: {
      delete: {
        title: "Supprimer",
        confirm: "Confirmer la suppression"
      },
      insert: "Insérer"
    }
  }
}, M1 = {
  toolbar: {
    headings: {
      normal: "Normal",
      h1: "Heading 1",
      h2: "Heading 2",
      h3: "Heading 3"
    },
    undo: "Undo",
    bold: "Bold",
    italic: "Italic",
    underline: "Underline",
    strike: "Strikethrough",
    highlight: "Highlight",
    textColor: "Text Color",
    align: {
      left: "Align Left",
      center: "Center",
      right: "Align Right",
      justify: "Justify"
    },
    list: {
      bullet: "Bullet List",
      ordered: "Ordered List"
    },
    link: {
      title: "Link",
      modal_title: "Insert a Link",
      url: "Enter a URL"
    },
    image: {
      title: "Image",
      import: "Import",
      url: "Via URL",
      url_title: "Link",
      url_insert: "Insert",
      media: "Media",
      modal_title: "Insert an Image",
      import_drag: "Drag and drop or",
      import_download: "click to upload",
      youtube: "Video"
    },
    table: {
      modal_title: "Insert a Table",
      columns: "Columns",
      rows: "Rows",
      header: "Include Header",
      delete: "Delete",
      add_column_before: "Add Column Before"
    },
    video: {
      modal_title: "Insert a Video",
      url: "Enter a Video URL"
    },
    insert: "Insert",
    format: "Format",
    panel: {
      title: "Section",
      type: {
        info: "Informations",
        warning: "Warning",
        error: "Error"
      }
    }
  },
  placeholder: {
    default: "Start writing..."
  },
  modal: {
    close: "Close"
  },
  mediaLibrary: {
    title: "Media Library",
    upload: "Upload",
    url: "URL",
    files: "Files",
    file: "File",
    search: {
      placeholder: "Type to search...",
      button: "Search"
    },
    attributes: {
      title: "Information",
      dimensions: "Dimensions",
      uploaded_by: "Uploaded by"
    },
    actions: {
      delete: {
        title: "Delete",
        confirm: "Confirm Deletion"
      },
      insert: "Insert"
    }
  }
}, no = {
  methods: {
    translate: function(n2, e) {
      const t = {
        fr: S1,
        en: M1
      };
      try {
        var r = n2.split(".").reduce(function(i, s, o) {
          return typeof i == "object" ? i[s] : t[e][i][s];
        });
      } catch (i) {
        console.warn("No translation found for namespace %s using locale %s (%s)", n2, e, i);
      }
      return r;
    }
  }
}, Hn = (n2, e) => {
  const t = n2.__vccOpts || n2;
  for (const [r, i] of e)
    t[r] = i;
  return t;
}, A1 = {
  name: "Popover",
  components: {},
  props: {
    // Icon name from Font Awesome
    icon: {
      type: String,
      required: false
    },
    // Text to display for toggle button
    text: {
      type: String,
      required: false
    },
    // Position of the popover content
    position: {
      type: String,
      default: "bottom"
      // top, bottom, left, right
    },
    // Style object for the popover content
    popoverContentStyle: {
      type: Object,
      default: () => ({})
    }
  },
  data: () => ({
    id: "popover-" + Math.random().toString(36).substr(2, 9),
    isOpen: false
  }),
  created() {
    this.calculatePosition(), document.addEventListener("click", this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener("click", this.handleClickOutside);
  },
  methods: {
    calculatePosition() {
      const n2 = this.$refs.popoverContent;
      if (n2) {
        const e = n2.children[0].offsetWidth, t = n2.children[0].offsetHeight, r = n2.previousElementSibling.offsetWidth, i = n2.previousElementSibling.offsetHeight, s = 10;
        switch (this.position) {
          case "top":
            n2.style.left = `calc(50% - ${e / 2}px)`, n2.style.bottom = `${i + s}px`;
            break;
          case "left":
            n2.style.top = `calc(50% - ${t / 2}px)`, n2.style.right = `${r + s}px`;
            break;
          case "right":
            n2.style.top = `calc(50% - ${t / 2}px)`, n2.style.left = `${r + s}px`;
            break;
          case "bottom":
          default:
            n2.style.left = "-4px", n2.style.top = `${i + s}px`;
            break;
        }
      }
    },
    onClickToggle() {
      this.isOpen = !this.isOpen, this.isOpen && this.calculatePosition();
    },
    onFocusOut() {
      this.isOpen = false;
    },
    handleClickOutside(n2) {
      n2.target.closest("#" + this.id) || (this.isOpen = false);
    }
  }
}, E1 = ["id"], T1 = { key: 0 }, O1 = { class: "material-symbols-outlined" }, N1 = ["id"];
function D1(n2, e, t, r, i, s) {
  return openBlock(), createElementBlock("div", {
    id: n2.id,
    class: "popover-container",
    onFocusout: e[1] || (e[1] = (...o) => s.onFocusOut && s.onFocusOut(...o))
  }, [
    createBaseVNode("div", {
      onClick: e[0] || (e[0] = (...o) => s.onClickToggle && s.onClickToggle(...o))
    }, [
      t.text ? (openBlock(), createElementBlock("span", T1, [
        createTextVNode(toDisplayString(t.text) + " ", 1),
        e[2] || (e[2] = createBaseVNode("span", { class: "material-symbols-outlined" }, "keyboard_arrow_down", -1))
      ])) : createCommentVNode("", true),
      createBaseVNode("span", O1, toDisplayString(t.icon), 1)
    ]),
    createVNode(Transition, { name: "fade" }, {
      default: withCtx(() => [
        withDirectives(createBaseVNode("div", {
          class: "popover-content tw-shadow tw-rounded",
          ref: "popoverContent",
          id: "popover-content-" + n2.id,
          style: normalizeStyle(t.popoverContentStyle)
        }, [
          renderSlot(n2.$slots, "default", {}, void 0, true)
        ], 12, N1), [
          [vShow, n2.isOpen]
        ])
      ]),
      _: 3
    })
  ], 40, E1);
}
const L1 = /* @__PURE__ */ Hn(A1, [["render", D1], ["__scopeId", "data-v-d2fe65a0"]]), R1 = {
  props: {
    name: {
      type: String,
      required: true
    },
    width: {
      type: String,
      default: "100%"
    },
    height: {
      type: String,
      default: "auto"
    },
    transition: {
      type: String,
      default: "fade"
    },
    delay: {
      type: Number,
      default: 0
    },
    clickToClose: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      isOpened: false
    };
  },
  mounted() {
    this.open();
  },
  methods: {
    open() {
      this.$emit("beforeOpen"), this.isOpened = true, this.$refs.modal_container.style.width = this.width, this.$refs.modal_container.style.height = this.height, this.$refs.modal_container.style.zIndex = 999999, this.$refs.modal_container.style.opacity = 1;
    },
    close() {
      this.$refs.modal_container.style.zIndex = -999999, this.$refs.modal_container.style.opacity = 0, this.$emit("closed");
    },
    onFocusOut() {
      this.clickToClose && this.close();
    }
  }
}, I1 = ["id"];
function P1(n2, e, t, r, i, s) {
  return openBlock(), createBlock(Transition, {
    name: t.transition,
    duration: t.delay
  }, {
    default: withCtx(() => [
      withDirectives(createBaseVNode("div", {
        id: "modal___" + t.name,
        class: "modal___container",
        ref: "modal_container",
        onFocusout: e[0] || (e[0] = (...o) => s.onFocusOut && s.onFocusOut(...o))
      }, [
        renderSlot(n2.$slots, "default", {}, void 0, true)
      ], 40, I1), [
        [vShow, i.isOpened]
      ])
    ]),
    _: 3
  }, 8, ["name", "duration"]);
}
const Jf = /* @__PURE__ */ Hn(R1, [["render", P1], ["__scopeId", "data-v-eaff321e"]]), B1 = {
  name: "Toolbar",
  components: {
    Modal: Jf,
    Popover: L1,
    BubbleMenu: b1
  },
  mixins: [no],
  props: {
    editorProp: {
      type: Kf,
      required: true
    },
    // Extensions to display in the toolbar
    extensions: {
      type: Array,
      required: true
    },
    displayMediaLibrary: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  inject: ["locale"],
  emits: ["importImage", "showMediaLibrary"],
  data() {
    return {
      heading: 0,
      fontFamily: "Arial",
      fontSize: "16px",
      color: "#000",
      lineHeight: 1.15,
      // Extensions values
      fontSizes: [
        "8px",
        "10px",
        "12px",
        "14px",
        "16px",
        "18px",
        "20px",
        "24px"
      ],
      lineHeights: [
        {
          label: "1.15",
          value: 1.15
        },
        {
          label: "1.50",
          value: 1.5
        },
        {
          label: "Double",
          value: 3
        }
      ],
      editor: void 0,
      locale: this.locale,
      urlIconNotFound: false,
      // Image
      imageModal: false,
      imageMethod: "import",
      imageImported: null,
      // Table
      tableModal: false,
      tableColumns: 3,
      tableRows: 3,
      tableHeader: true,
      // Video
      videoModal: false,
      videoUrl: "",
      // Link
      linkModal: false,
      linkUrl: ""
    };
  },
  watch: {
    // Extensions
    heading: {
      handler(n2) {
        this.triggerHeading(n2);
      }
    },
    fontFamily: {
      handler(n2) {
        this.setFontFamily(n2);
      }
    },
    fontSize: {
      handler(n2) {
        this.setFontSize(n2);
      }
    },
    color: {
      handler(n2) {
        this.setColor(n2);
      }
    }
  },
  mounted() {
    this.editor = this.editorProp;
  },
  beforeUnmount() {
  },
  methods: {
    triggerHeading(n2) {
      const e = n2 >= 1 && n2 <= 3;
      this.editor.chain().focus().toggleHeading({ level: e ? n2 : 4 }).run();
    },
    setFontFamily(n2) {
      this.editor.chain().focus().setFontFamily(n2).run();
    },
    setFontSize(n2) {
      this.editor.chain().focus().setFontSize(n2).run();
    },
    setColor(n2) {
      this.editor.chain().focus().setColor(n2).run();
    },
    setTextAlign(n2) {
      this.editor.chain().focus().setTextAlign(n2).run(), this.editor.chain().focus().setImgPosition(n2).run(), this.editor.chain().focus().setFilePosition(n2).run(), this.editor.chain().focus().setVideoPosition(n2).run();
    },
    setLink() {
      const n2 = this.editor.getAttributes("link").href, e = window.prompt("URL", n2);
      if (e !== null) {
        if (e === "") {
          this.editor.chain().focus().extendMarkRange("link").unsetLink().run();
          return;
        }
        this.editor.chain().focus().extendMarkRange("link").setLink({ href: e }).run();
      }
    },
    openImageModal() {
      this.imageModal = true;
    },
    openYoutubeModal() {
      this.videoModal = true;
    },
    openTableModal() {
      this.tableModal = true;
    },
    openLinkModal() {
      this.linkModal = true;
    },
    importFromComputer() {
      document.getElementById("import_file").click();
    },
    copyLink(n2) {
      navigator.clipboard.writeText(n2);
    },
    addPanel() {
      this.editor.chain().focus().insertContent('<div data-plugin="panel" data-type="info"><div><p></p></div></div>').run(), this.$refs.insertPopover.onFocusOut();
    }
  },
  computed: {
    headingLevels() {
      var n2 = [];
      return this.extensions.includes("h1") && n2.push(1), this.extensions.includes("h2") && n2.push(2), this.extensions.includes("h3") && n2.push(3), n2;
    },
    displaySeparator() {
      return this.extensions.includes("left") || this.extensions.includes("center") || this.extensions.includes("right") || this.extensions.includes("justify") || this.extensions.includes("ul") || this.extensions.includes("ol") || this.extensions.includes("table");
    },
    toolbarClasses() {
      return this.$attrs.toolbar_classes === void 0 ? "" : this.$attrs.toolbar_classes;
    },
    colors() {
      return this.$attrs.palette === void 0 ? "" : this.$attrs.palette;
    },
    fontFamilies() {
      return this.$attrs.font_families === void 0 ? "" : this.$attrs.font_families;
    }
  }
}, H1 = { class: "editor-toolbar--list" }, F1 = ["value"], z1 = ["value"], V1 = {
  key: 0,
  value: 0
}, $1 = ["value"], _1 = ["title"], j1 = ["title"], W1 = ["title"], U1 = { class: "editor-image--popover" }, K1 = ["title"], q1 = ["title"], J1 = ["title"], G1 = ["title"], Y1 = ["title"], X1 = ["title"], Q1 = ["title"], Z1 = ["title"], ev = ["title"], tv = ["title"], nv = ["title"], rv = { class: "editor-color-picker--popover" }, iv = ["onClick"], sv = ["title"], ov = ["title"], lv = ["title"], av = ["title"], cv = ["title"], uv = ["title"], dv = ["title"], fv = ["title"], hv = ["title"], pv = ["title"], mv = ["title"], gv = { class: "editor-image--popover" }, yv = ["title"], bv = ["title"], vv = ["title"], wv = { class: "insert-image--modal-head" }, kv = { class: "insert-image--modal-head-title" }, Cv = { style: { "margin-top": "0" } }, xv = ["title"], Sv = { class: "insert-image--modal-content" }, Mv = {
  key: 0,
  class: "insert-image--import-file"
}, Av = {
  key: 1,
  class: "insert-image--from-url"
}, Ev = { for: "image-url" }, Tv = { class: "insert-image--from-url-button" }, Ov = { class: "insert-video--modal-head" }, Nv = { class: "insert-video--modal-head-title" }, Dv = { style: { "margin-top": "0" } }, Lv = ["title"], Rv = { class: "insert-video--modal-content" }, Iv = { class: "insert-video--input" }, Pv = { for: "video-url" }, Bv = { class: "insert-video--button" }, Hv = { class: "insert-link--modal-head" }, Fv = { class: "insert-link--modal-head-title" }, zv = { style: { "margin-top": "0" } }, Vv = ["title"], $v = { class: "insert-link--modal-content" }, _v = { class: "insert-link--input" }, jv = { for: "link-url" }, Wv = { class: "insert-link--button" }, Uv = { class: "insert-table--modal-head" }, Kv = { class: "insert-table--modal-head-title" }, qv = { style: { "margin-top": "0" } }, Jv = ["title"], Gv = { class: "insert-table--modal-content" }, Yv = { class: "insert-table--inputs" }, Xv = { class: "insert-table--input" }, Qv = { for: "table-columns" }, Zv = { class: "insert-table--input" }, ew = { for: "table-rows" }, tw = { class: "insert-table--input-header" }, nw = { for: "table-header" }, rw = { class: "insert-table--button" };
function iw(n2, e, t, r, i, s) {
  var a, c, u;
  const o = resolveComponent("popover"), l = resolveComponent("modal");
  return this.editor ? (openBlock(), createElementBlock("div", {
    key: 0,
    class: normalizeClass(["editor-toolbar", s.toolbarClasses])
  }, [
    createBaseVNode("ul", H1, [
      this.extensions.includes("history") ? (openBlock(), createElementBlock("li", {
        key: 0,
        onClick: e[0] || (e[0] = withModifiers((d) => this.editor.chain().focus().undo().run(), ["stop", "prevent"]))
      }, e[50] || (e[50] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "undo", -1)
      ]))) : createCommentVNode("", true),
      this.extensions.includes("history") ? (openBlock(), createElementBlock("li", {
        key: 1,
        onClick: e[1] || (e[1] = withModifiers((d) => i.editor.chain().focus().redo().run(), ["stop", "prevent"]))
      }, e[51] || (e[51] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "redo", -1)
      ]))) : createCommentVNode("", true),
      this.extensions.includes("fontFamily") ? withDirectives((openBlock(), createElementBlock("select", {
        key: 2,
        "onUpdate:modelValue": e[2] || (e[2] = (d) => i.fontFamily = d)
      }, [
        (openBlock(true), createElementBlock(Fragment, null, renderList(s.fontFamilies, (d) => (openBlock(), createElementBlock("option", {
          key: d,
          value: d
        }, toDisplayString(d), 9, F1))), 128))
      ], 512)), [
        [vModelSelect, i.fontFamily]
      ]) : createCommentVNode("", true),
      this.extensions.includes("fontSize") ? withDirectives((openBlock(), createElementBlock("select", {
        key: 3,
        "onUpdate:modelValue": e[3] || (e[3] = (d) => i.fontSize = d)
      }, [
        (openBlock(true), createElementBlock(Fragment, null, renderList(i.fontSizes, (d) => (openBlock(), createElementBlock("option", {
          key: d,
          value: d
        }, toDisplayString(d), 9, z1))), 128))
      ], 512)), [
        [vModelSelect, i.fontSize]
      ]) : createCommentVNode("", true),
      this.extensions.includes("h1") || this.extensions.includes("h2") || this.extensions.includes("h3") ? withDirectives((openBlock(), createElementBlock("select", {
        key: 4,
        "onUpdate:modelValue": e[4] || (e[4] = (d) => i.heading = d)
      }, [
        (openBlock(), createElementBlock("option", V1, toDisplayString(n2.translate("toolbar.headings.normal", this.locale)), 1)),
        (openBlock(true), createElementBlock(Fragment, null, renderList(s.headingLevels, (d) => (openBlock(), createElementBlock("option", {
          key: d,
          value: d
        }, toDisplayString(n2.translate("toolbar.headings.h" + d, this.locale)), 9, $1))), 128))
      ], 512)), [
        [vModelSelect, i.heading]
      ]) : createCommentVNode("", true),
      this.extensions.includes("bold") ? (openBlock(), createElementBlock("li", {
        key: 5,
        title: n2.translate("toolbar.bold", this.locale),
        onClick: e[5] || (e[5] = withModifiers((d) => i.editor.chain().focus().toggleBold().run(), ["stop", "prevent"]))
      }, e[52] || (e[52] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "format_bold", -1)
      ]), 8, _1)) : createCommentVNode("", true),
      this.extensions.includes("italic") ? (openBlock(), createElementBlock("li", {
        key: 6,
        title: n2.translate("toolbar.italic", this.locale),
        onClick: e[6] || (e[6] = withModifiers((d) => i.editor.chain().focus().toggleItalic().run(), ["stop", "prevent"]))
      }, e[53] || (e[53] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "format_italic", -1)
      ]), 8, j1)) : createCommentVNode("", true),
      this.extensions.includes("bold") || this.extensions.includes("italic") || this.extensions.includes("underline") || this.extensions.includes("strike") || this.extensions.includes("highlight") || this.extensions.includes("codeblock") ? (openBlock(), createElementBlock("li", {
        key: 7,
        title: n2.translate("toolbar.format", this.locale),
        class: "editor-image"
      }, [
        createVNode(o, { icon: "more_horiz" }, {
          default: withCtx(() => [
            createBaseVNode("ul", U1, [
              this.extensions.includes("underline") ? (openBlock(), createElementBlock("li", {
                key: 0,
                class: "image-item",
                title: n2.translate("toolbar.underline", this.locale),
                onClick: e[7] || (e[7] = withModifiers((d) => i.editor.chain().focus().toggleUnderline().run(), ["stop", "prevent"]))
              }, [
                e[54] || (e[54] = createBaseVNode("span", { class: "material-symbols-outlined" }, "format_underlined", -1)),
                createBaseVNode("span", null, toDisplayString(n2.translate("toolbar.underline", this.locale)), 1)
              ], 8, K1)) : createCommentVNode("", true),
              this.extensions.includes("strike") ? (openBlock(), createElementBlock("li", {
                key: 1,
                class: "image-item",
                title: n2.translate("toolbar.strike", this.locale),
                onClick: e[8] || (e[8] = withModifiers((d) => i.editor.chain().focus().toggleStrike().run(), ["stop", "prevent"]))
              }, [
                e[55] || (e[55] = createBaseVNode("span", { class: "material-symbols-outlined" }, "format_clear", -1)),
                createBaseVNode("span", null, toDisplayString(n2.translate("toolbar.strike", this.locale)), 1)
              ], 8, q1)) : createCommentVNode("", true),
              this.extensions.includes("highlight") ? (openBlock(), createElementBlock("li", {
                key: 2,
                class: "image-item",
                title: n2.translate("toolbar.highlight", this.locale),
                onClick: e[9] || (e[9] = withModifiers((d) => i.editor.chain().focus().toggleHighlight({ color: "#ffc078" }).run(), ["stop", "prevent"]))
              }, [
                e[56] || (e[56] = createBaseVNode("span", { class: "material-symbols-outlined" }, "format_ink_highlighter", -1)),
                createBaseVNode("span", null, toDisplayString(n2.translate("toolbar.highlight", this.locale)), 1)
              ], 8, J1)) : createCommentVNode("", true),
              this.extensions.includes("codeblock") ? (openBlock(), createElementBlock("li", {
                key: 3,
                class: "image-item",
                title: n2.translate("toolbar.codeblock", this.locale),
                onClick: e[10] || (e[10] = withModifiers((d) => i.editor.chain().focus().toggleCodeBlock().run(), ["stop", "prevent"]))
              }, e[57] || (e[57] = [
                createBaseVNode("span", { class: "material-symbols-outlined" }, "code_blocks", -1),
                createBaseVNode("span", null, "Code", -1)
              ]), 8, G1)) : createCommentVNode("", true)
            ])
          ]),
          _: 1
        })
      ], 8, W1)) : createCommentVNode("", true),
      this.extensions.includes("left") ? (openBlock(), createElementBlock("li", {
        key: 8,
        title: n2.translate("toolbar.align.left", this.locale),
        class: normalizeClass({ "is-active": i.editor.isActive({ textAlign: "left" }) }),
        onClick: e[11] || (e[11] = withModifiers((d) => s.setTextAlign("left"), ["stop", "prevent"]))
      }, e[58] || (e[58] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "format_align_left", -1)
      ]), 10, Y1)) : createCommentVNode("", true),
      this.extensions.includes("center") ? (openBlock(), createElementBlock("li", {
        key: 9,
        title: n2.translate("toolbar.align.center", this.locale),
        class: normalizeClass({ "is-active": i.editor.isActive({ textAlign: "center" }) }),
        onClick: e[12] || (e[12] = withModifiers((d) => s.setTextAlign("center"), ["stop", "prevent"]))
      }, e[59] || (e[59] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "format_align_center", -1)
      ]), 10, X1)) : createCommentVNode("", true),
      this.extensions.includes("right") ? (openBlock(), createElementBlock("li", {
        key: 10,
        title: n2.translate("toolbar.align.right", this.locale),
        class: normalizeClass({ "is-active": i.editor.isActive({ textAlign: "right" }) }),
        onClick: e[13] || (e[13] = withModifiers((d) => s.setTextAlign("right"), ["stop", "prevent"]))
      }, e[60] || (e[60] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "format_align_right", -1)
      ]), 10, Q1)) : createCommentVNode("", true),
      this.extensions.includes("justify") ? (openBlock(), createElementBlock("li", {
        key: 11,
        title: n2.translate("toolbar.align.justify", this.locale),
        class: normalizeClass([{ "is-active": i.editor.isActive({ textAlign: "justify" }) }, "menubar__button"]),
        onClick: e[14] || (e[14] = withModifiers((d) => s.setTextAlign("justify"), ["stop", "prevent"]))
      }, e[61] || (e[61] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "format_align_justify", -1)
      ]), 10, Z1)) : createCommentVNode("", true),
      this.extensions.includes("ul") ? (openBlock(), createElementBlock("li", {
        key: 12,
        class: normalizeClass({ "is-active": i.editor.isActive("bulletList") }),
        title: n2.translate("toolbar.list.bullet", this.locale),
        onClick: e[15] || (e[15] = withModifiers((d) => i.editor.chain().focus().toggleBulletList().run(), ["stop", "prevent"]))
      }, e[62] || (e[62] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "format_list_bulleted", -1)
      ]), 10, ev)) : createCommentVNode("", true),
      this.extensions.includes("ol") ? (openBlock(), createElementBlock("li", {
        key: 13,
        class: normalizeClass({ "is-active": i.editor.isActive("orderedList") }),
        title: n2.translate("toolbar.list.ordered", this.locale),
        onClick: e[16] || (e[16] = withModifiers((d) => i.editor.chain().focus().toggleOrderedList().run(), ["stop", "prevent"]))
      }, e[63] || (e[63] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "format_list_numbered", -1)
      ]), 10, tv)) : createCommentVNode("", true),
      this.extensions.includes("color") ? (openBlock(), createElementBlock("li", {
        key: 14,
        title: n2.translate("toolbar.textColor", this.locale),
        class: "editor-color-picker"
      }, [
        createVNode(o, { icon: "format_color_fill" }, {
          default: withCtx(() => [
            createBaseVNode("div", rv, [
              (openBlock(true), createElementBlock(Fragment, null, renderList(s.colors, (d) => (openBlock(), createElementBlock("span", {
                onClick: (f) => s.setColor(d.value),
                style: normalizeStyle({ backgroundColor: d.value, border: "1px solid grey", margin: "2px" })
              }, null, 12, iv))), 256))
            ])
          ]),
          _: 1
        })
      ], 8, nv)) : createCommentVNode("", true),
      this.extensions.includes("link") ? (openBlock(), createElementBlock("li", {
        key: 15,
        class: "image-item",
        title: n2.translate("toolbar.link.title", this.locale),
        onClick: e[17] || (e[17] = (...d) => s.openLinkModal && s.openLinkModal(...d))
      }, e[64] || (e[64] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "link", -1)
      ]), 8, sv)) : createCommentVNode("", true),
      this.extensions.includes("image") ? (openBlock(), createElementBlock("li", {
        key: 16,
        class: "image-item",
        title: n2.translate("toolbar.image.title", this.locale),
        onClick: e[18] || (e[18] = (...d) => s.openImageModal && s.openImageModal(...d))
      }, e[65] || (e[65] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "image", -1)
      ]), 8, ov)) : createCommentVNode("", true),
      this.extensions.includes("table") && !((a = i.editor) != null && a.isActive("table")) ? (openBlock(), createElementBlock("li", {
        key: 17,
        onClick: e[19] || (e[19] = (...d) => s.openTableModal && s.openTableModal(...d))
      }, e[66] || (e[66] = [
        createBaseVNode("span", { class: "material-symbols-outlined" }, "table", -1)
      ]))) : createCommentVNode("", true),
      this.extensions.includes("link") && ((c = i.editor) != null && c.isActive("link")) ? (openBlock(), createElementBlock(Fragment, { key: 18 }, [
        e[68] || (e[68] = createBaseVNode("li", { class: "editor-separator" }, null, -1)),
        createBaseVNode("li", {
          onClick: e[20] || (e[20] = (d) => i.editor.chain().focus().extendMarkRange("link").unsetLink().run())
        }, e[67] || (e[67] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "link_off", -1)
        ]))
      ], 64)) : createCommentVNode("", true),
      this.extensions.includes("table") && ((u = i.editor) != null && u.isActive("table")) ? (openBlock(), createElementBlock(Fragment, { key: 19 }, [
        e[77] || (e[77] = createBaseVNode("li", { class: "editor-separator" }, null, -1)),
        createBaseVNode("li", {
          onClick: e[21] || (e[21] = (d) => {
            var f;
            return (f = i.editor) == null ? void 0 : f.commands.deleteTable();
          }),
          title: n2.translate("toolbar.table.delete", this.locale)
        }, e[69] || (e[69] = [
          createBaseVNode("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
            class: "h-5 w-5",
            fill: "currentColor"
          }, [
            createBaseVNode("path", { d: "M15.46,15.88L16.88,14.46L19,16.59L21.12,14.46L22.54,15.88L20.41,18L22.54,20.12L21.12,21.54L19,19.41L16.88,21.54L15.46,20.12L17.59,18L15.46,15.88M4,3H18A2,2 0 0,1 20,5V12.08C18.45,11.82 16.92,12.18 15.68,13H12V17H13.08C12.97,17.68 12.97,18.35 13.08,19H4A2,2 0 0,1 2,17V5A2,2 0 0,1 4,3M4,7V11H10V7H4M12,7V11H18V7H12M4,13V17H10V13H4Z" })
          ], -1)
        ]), 8, lv),
        createBaseVNode("li", {
          onClick: e[22] || (e[22] = (d) => {
            var f;
            return (f = i.editor) == null ? void 0 : f.commands.addColumnBefore();
          }),
          title: n2.translate("toolbar.table.add_column_before", this.locale)
        }, e[70] || (e[70] = [
          createBaseVNode("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
            class: "h-5 w-5",
            fill: "currentColor"
          }, [
            createBaseVNode("path", { d: "M13,2A2,2 0 0,0 11,4V20A2,2 0 0,0 13,22H22V2H13M20,10V14H13V10H20M20,16V20H13V16H20M20,4V8H13V4H20M9,11H6V8H4V11H1V13H4V16H6V13H9V11Z" })
          ], -1)
        ]), 8, av),
        createBaseVNode("li", {
          onClick: e[23] || (e[23] = (d) => {
            var f;
            return (f = i.editor) == null ? void 0 : f.commands.addColumnAfter();
          }),
          title: n2.translate("toolbar.table.add_column_after", this.locale)
        }, e[71] || (e[71] = [
          createBaseVNode("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
            class: "h-5 w-5",
            fill: "currentColor"
          }, [
            createBaseVNode("path", { d: "M11,2A2,2 0 0,1 13,4V20A2,2 0 0,1 11,22H2V2H11M4,10V14H11V10H4M4,16V20H11V16H4M4,4V8H11V4H4M15,11H18V8H20V11H23V13H20V16H18V13H15V11Z" })
          ], -1)
        ]), 8, cv),
        createBaseVNode("li", {
          onClick: e[24] || (e[24] = (d) => {
            var f;
            return (f = i.editor) == null ? void 0 : f.commands.deleteColumn();
          }),
          title: n2.translate("toolbar.table.delete_column", this.locale)
        }, e[72] || (e[72] = [
          createBaseVNode("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
            class: "h-5 w-5",
            fill: "currentColor"
          }, [
            createBaseVNode("path", { d: "M4,2H11A2,2 0 0,1 13,4V20A2,2 0 0,1 11,22H4A2,2 0 0,1 2,20V4A2,2 0 0,1 4,2M4,10V14H11V10H4M4,16V20H11V16H4M4,4V8H11V4H4M17.59,12L15,9.41L16.41,8L19,10.59L21.59,8L23,9.41L20.41,12L23,14.59L21.59,16L19,13.41L16.41,16L15,14.59L17.59,12Z" })
          ], -1)
        ]), 8, uv),
        createBaseVNode("li", {
          onClick: e[25] || (e[25] = (d) => {
            var f;
            return (f = i.editor) == null ? void 0 : f.commands.addRowBefore();
          }),
          title: n2.translate("toolbar.table.add_row_before", this.locale)
        }, e[73] || (e[73] = [
          createBaseVNode("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
            class: "h-5 w-5",
            fill: "currentColor"
          }, [
            createBaseVNode("path", { d: "M22,14A2,2 0 0,0 20,12H4A2,2 0 0,0 2,14V21H4V19H8V21H10V19H14V21H16V19H20V21H22V14M4,14H8V17H4V14M10,14H14V17H10V14M20,14V17H16V14H20M11,10H13V7H16V5H13V2H11V5H8V7H11V10Z" })
          ], -1)
        ]), 8, dv),
        createBaseVNode("li", {
          onClick: e[26] || (e[26] = (d) => {
            var f;
            return (f = i.editor) == null ? void 0 : f.commands.addRowAfter();
          }),
          title: n2.translate("toolbar.table.add_row_after", this.locale)
        }, e[74] || (e[74] = [
          createBaseVNode("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
            class: "h-5 w-5",
            fill: "currentColor"
          }, [
            createBaseVNode("path", { d: "M22,10A2,2 0 0,1 20,12H4A2,2 0 0,1 2,10V3H4V5H8V3H10V5H14V3H16V5H20V3H22V10M4,10H8V7H4V10M10,10H14V7H10V10M20,10V7H16V10H20M11,14H13V17H16V19H13V22H11V19H8V17H11V14Z" })
          ], -1)
        ]), 8, fv),
        createBaseVNode("li", {
          onClick: e[27] || (e[27] = (d) => {
            var f;
            return (f = i.editor) == null ? void 0 : f.commands.deleteRow();
          }),
          title: n2.translate("toolbar.table.delete_row", this.locale)
        }, e[75] || (e[75] = [
          createBaseVNode("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
            class: "h-5 w-5",
            fill: "currentColor"
          }, [
            createBaseVNode("path", { d: "M9.41,13L12,15.59L14.59,13L16,14.41L13.41,17L16,19.59L14.59,21L12,18.41L9.41,21L8,19.59L10.59,17L8,14.41L9.41,13M22,9A2,2 0 0,1 20,11H4A2,2 0 0,1 2,9V6A2,2 0 0,1 4,4H20A2,2 0 0,1 22,6V9M4,9H8V6H4V9M10,9H14V6H10V9M16,9H20V6H16V9Z" })
          ], -1)
        ]), 8, hv),
        createBaseVNode("li", {
          onClick: e[28] || (e[28] = (d) => {
            var f;
            return (f = i.editor) == null ? void 0 : f.commands.mergeOrSplit();
          }),
          title: n2.translate("toolbar.table.merge_or_split", this.locale)
        }, e[76] || (e[76] = [
          createBaseVNode("svg", {
            xmlns: "http://www.w3.org/2000/svg",
            viewBox: "0 0 24 24",
            class: "h-5 w-5",
            fill: "currentColor"
          }, [
            createBaseVNode("path", { d: "M5,10H3V4H11V6H5V10M19,18H13V20H21V14H19V18M5,18V14H3V20H11V18H5M21,4H13V6H19V10H21V4M8,13V15L11,12L8,9V11H3V13H8M16,11V9L13,12L16,15V13H21V11H16Z" })
          ], -1)
        ]), 8, pv)
      ], 64)) : createCommentVNode("", true),
      t.displayMediaLibrary || this.extensions.includes("youtube") || this.extensions.includes("panel") ? (openBlock(), createElementBlock("li", {
        key: 20,
        title: n2.translate("toolbar.insert", this.locale),
        class: "editor-image"
      }, [
        createVNode(o, {
          icon: "add",
          ref: "insertPopover"
        }, {
          default: withCtx(() => [
            createBaseVNode("ul", gv, [
              t.displayMediaLibrary ? (openBlock(), createElementBlock("li", {
                key: 0,
                class: "image-item",
                onClick: e[29] || (e[29] = (d) => n2.$emit("showMediaLibrary")),
                title: n2.translate("toolbar.image.media", this.locale)
              }, [
                e[78] || (e[78] = createBaseVNode("span", { class: "material-symbols-outlined" }, "photo_library", -1)),
                createBaseVNode("span", null, toDisplayString(n2.translate("toolbar.image.media", this.locale)), 1)
              ], 8, yv)) : createCommentVNode("", true),
              this.extensions.includes("youtube") ? (openBlock(), createElementBlock("li", {
                key: 1,
                class: "image-item",
                onClick: e[30] || (e[30] = (...d) => s.openYoutubeModal && s.openYoutubeModal(...d)),
                title: n2.translate("toolbar.image.youtube", this.locale)
              }, [
                e[79] || (e[79] = createBaseVNode("span", { class: "material-symbols-outlined" }, "movie", -1)),
                createBaseVNode("span", null, toDisplayString(n2.translate("toolbar.image.youtube", this.locale)), 1)
              ], 8, bv)) : createCommentVNode("", true),
              this.extensions.includes("panel") ? (openBlock(), createElementBlock("li", {
                key: 2,
                class: "image-item",
                onClick: e[31] || (e[31] = (...d) => s.addPanel && s.addPanel(...d)),
                title: n2.translate("toolbar.panel.title", this.locale)
              }, [
                e[80] || (e[80] = createBaseVNode("span", { class: "material-symbols-outlined" }, "info", -1)),
                createBaseVNode("span", null, toDisplayString(n2.translate("toolbar.panel.title", this.locale)), 1)
              ], 8, vv)) : createCommentVNode("", true)
            ])
          ]),
          _: 1
        })
      ], 8, mv)) : createCommentVNode("", true),
      i.imageModal ? (openBlock(), createBlock(l, {
        key: 21,
        class: "insert-image",
        name: "insert-image",
        resizable: true,
        draggable: true,
        "click-to-close": false,
        width: "40%"
      }, {
        default: withCtx(() => [
          createBaseVNode("div", wv, [
            createBaseVNode("div", kv, [
              createBaseVNode("h2", Cv, toDisplayString(n2.translate("toolbar.image.modal_title", this.locale)), 1),
              createBaseVNode("span", {
                title: n2.translate("modal.close", this.locale),
                class: "material-symbols-outlined",
                onClick: e[32] || (e[32] = (d) => i.imageModal = false)
              }, "close", 8, xv)
            ])
          ]),
          createBaseVNode("div", Sv, [
            createBaseVNode("ul", null, [
              createBaseVNode("li", {
                class: normalizeClass(["image-item", i.imageMethod === "import" ? "active" : ""]),
                onClick: e[33] || (e[33] = (d) => i.imageMethod = "import")
              }, [
                createBaseVNode("span", null, toDisplayString(n2.translate("toolbar.image.import", this.locale)), 1)
              ], 2),
              createBaseVNode("li", {
                class: normalizeClass(["image-item", i.imageMethod === "url" ? "active" : ""]),
                onClick: e[34] || (e[34] = (d) => i.imageMethod = "url")
              }, [
                createBaseVNode("span", null, toDisplayString(n2.translate("toolbar.image.url", this.locale)), 1)
              ], 2)
            ]),
            i.imageMethod === "import" ? (openBlock(), createElementBlock("div", Mv, [
              createBaseVNode("input", {
                type: "file",
                id: "import_file",
                accept: "image/*",
                style: { display: "none" },
                onChange: e[35] || (e[35] = (d) => {
                  n2.$emit("importImage", d), i.imageModal = false;
                })
              }, null, 32),
              createBaseVNode("div", {
                class: "insert-image--import-file-dz",
                onClick: e[36] || (e[36] = (...d) => s.importFromComputer && s.importFromComputer(...d))
              }, [
                createBaseVNode("div", null, [
                  createBaseVNode("span", null, [
                    createTextVNode(toDisplayString(n2.translate("toolbar.image.import_drag", this.locale)) + " ", 1),
                    createBaseVNode("u", null, toDisplayString(n2.translate("toolbar.image.import_download", this.locale)), 1)
                  ]),
                  e[81] || (e[81] = createBaseVNode("span", { class: "material-symbols-outlined" }, "cloud_upload", -1))
                ])
              ])
            ])) : createCommentVNode("", true),
            i.imageMethod === "url" ? (openBlock(), createElementBlock("div", Av, [
              createBaseVNode("label", Ev, toDisplayString(n2.translate("toolbar.image.url_title", this.locale)), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                id: "image-url",
                "onUpdate:modelValue": e[37] || (e[37] = (d) => i.imageImported = d),
                placeholder: "https://example.com/image.jpg"
              }, null, 512), [
                [vModelText, i.imageImported]
              ]),
              createBaseVNode("div", Tv, [
                createBaseVNode("button", {
                  onClick: e[38] || (e[38] = (d) => {
                    i.editor.chain().focus().setImage({ src: i.imageImported }).run(), i.imageImported = null, i.imageModal = false;
                  })
                }, toDisplayString(n2.translate("toolbar.image.url_insert", this.locale)), 1)
              ])
            ])) : createCommentVNode("", true)
          ])
        ]),
        _: 1
      })) : createCommentVNode("", true),
      i.videoModal ? (openBlock(), createBlock(l, {
        key: 22,
        class: "insert-video",
        name: "insert-video",
        resizable: true,
        draggable: true,
        "click-to-close": false,
        width: "40%"
      }, {
        default: withCtx(() => [
          createBaseVNode("div", Ov, [
            createBaseVNode("div", Nv, [
              createBaseVNode("h2", Dv, toDisplayString(n2.translate("toolbar.video.modal_title", this.locale)), 1),
              createBaseVNode("span", {
                title: n2.translate("modal.close", this.locale),
                class: "material-symbols-outlined",
                onClick: e[39] || (e[39] = (d) => i.videoModal = false)
              }, "close", 8, Lv)
            ])
          ]),
          createBaseVNode("div", Rv, [
            createBaseVNode("div", Iv, [
              createBaseVNode("label", Pv, toDisplayString(n2.translate("toolbar.video.url", this.locale)), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                id: "video-url",
                "onUpdate:modelValue": e[40] || (e[40] = (d) => i.videoUrl = d),
                placeholder: "https://youtube.com"
              }, null, 512), [
                [vModelText, i.videoUrl]
              ])
            ]),
            createBaseVNode("div", Bv, [
              createBaseVNode("button", {
                onClick: e[41] || (e[41] = (d) => {
                  i.editor.commands.setYoutubeVideo({ src: i.videoUrl, width: 400, height: 300 }), i.videoModal = false;
                })
              }, toDisplayString(n2.translate("toolbar.image.url_insert", this.locale)), 1)
            ])
          ])
        ]),
        _: 1
      })) : createCommentVNode("", true),
      i.linkModal ? (openBlock(), createBlock(l, {
        key: 23,
        class: "insert-link",
        name: "insert-link",
        resizable: true,
        draggable: true,
        "click-to-close": false,
        width: "40%"
      }, {
        default: withCtx(() => [
          createBaseVNode("div", Hv, [
            createBaseVNode("div", Fv, [
              createBaseVNode("h2", zv, toDisplayString(n2.translate("toolbar.link.modal_title", this.locale)), 1),
              createBaseVNode("span", {
                title: n2.translate("modal.close", this.locale),
                class: "material-symbols-outlined",
                onClick: e[42] || (e[42] = (d) => i.linkModal = false)
              }, "close", 8, Vv)
            ])
          ]),
          createBaseVNode("div", $v, [
            createBaseVNode("div", _v, [
              createBaseVNode("label", jv, toDisplayString(n2.translate("toolbar.link.url", this.locale)), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                id: "link-url",
                "onUpdate:modelValue": e[43] || (e[43] = (d) => i.linkUrl = d),
                placeholder: "https://example.com"
              }, null, 512), [
                [vModelText, i.linkUrl]
              ])
            ]),
            createBaseVNode("div", Wv, [
              createBaseVNode("button", {
                onClick: e[44] || (e[44] = (d) => {
                  i.editor.chain().focus().extendMarkRange("link").setLink({ href: i.linkUrl }).run(), i.linkModal = false;
                })
              }, toDisplayString(n2.translate("toolbar.image.url_insert", this.locale)), 1)
            ])
          ])
        ]),
        _: 1
      })) : createCommentVNode("", true),
      i.tableModal ? (openBlock(), createBlock(l, {
        key: 24,
        class: "insert-table",
        name: "insert-table",
        resizable: true,
        draggable: true,
        "click-to-close": false,
        width: "40%"
      }, {
        default: withCtx(() => [
          createBaseVNode("div", Uv, [
            createBaseVNode("div", Kv, [
              createBaseVNode("h2", qv, toDisplayString(n2.translate("toolbar.table.modal_title", this.locale)), 1),
              createBaseVNode("span", {
                title: n2.translate("modal.close", this.locale),
                class: "material-symbols-outlined",
                onClick: e[45] || (e[45] = (d) => i.tableModal = false)
              }, "close", 8, Jv)
            ])
          ]),
          createBaseVNode("div", Gv, [
            createBaseVNode("div", Yv, [
              createBaseVNode("div", Xv, [
                createBaseVNode("label", Qv, toDisplayString(n2.translate("toolbar.table.columns", this.locale)), 1),
                withDirectives(createBaseVNode("input", {
                  type: "text",
                  id: "table-columns",
                  "onUpdate:modelValue": e[46] || (e[46] = (d) => i.tableColumns = d),
                  placeholder: "3"
                }, null, 512), [
                  [vModelText, i.tableColumns]
                ])
              ]),
              createBaseVNode("div", Zv, [
                createBaseVNode("label", ew, toDisplayString(n2.translate("toolbar.table.rows", this.locale)), 1),
                withDirectives(createBaseVNode("input", {
                  type: "text",
                  id: "table-rows",
                  "onUpdate:modelValue": e[47] || (e[47] = (d) => i.tableRows = d),
                  placeholder: "3"
                }, null, 512), [
                  [vModelText, i.tableRows]
                ])
              ])
            ]),
            createBaseVNode("div", tw, [
              withDirectives(createBaseVNode("input", {
                type: "checkbox",
                id: "table-header",
                "onUpdate:modelValue": e[48] || (e[48] = (d) => i.tableHeader = d)
              }, null, 512), [
                [vModelCheckbox, i.tableHeader]
              ]),
              createBaseVNode("label", nw, toDisplayString(n2.translate("toolbar.table.header", this.locale)), 1)
            ]),
            createBaseVNode("div", rw, [
              createBaseVNode("button", {
                onClick: e[49] || (e[49] = (d) => {
                  i.editor.chain().focus().insertTable({ rows: i.tableRows, cols: i.tableColumns, withHeaderRow: i.tableHeader }).run(), i.tableModal = false;
                })
              }, toDisplayString(n2.translate("toolbar.image.url_insert", this.locale)), 1)
            ])
          ])
        ]),
        _: 1
      })) : createCommentVNode("", true)
    ])
  ], 2)) : createCommentVNode("", true);
}
const sw = /* @__PURE__ */ Hn(B1, [["render", iw]]), ow = ce.create({
  name: "doc",
  topNode: true,
  content: "block+"
}), lw = ce.create({
  name: "paragraph",
  priority: 1e3,
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  group: "block",
  content: "inline*",
  parseHTML() {
    return [
      { tag: "p" }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["p", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      setParagraph: () => ({ commands: n2 }) => n2.setNode(this.name)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Alt-0": () => this.editor.commands.setParagraph()
    };
  }
}), aw = ce.create({
  name: "text",
  group: "inline"
}), cw = ce.create({
  name: "hardBreak",
  addOptions() {
    return {
      keepMarks: true,
      HTMLAttributes: {}
    };
  },
  inline: true,
  group: "inline",
  selectable: false,
  linebreakReplacement: true,
  parseHTML() {
    return [
      { tag: "br" }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["br", Q(this.options.HTMLAttributes, n2)];
  },
  renderText() {
    return `
`;
  },
  addCommands() {
    return {
      setHardBreak: () => ({ commands: n2, chain: e, state: t, editor: r }) => n2.first([
        () => n2.exitCode(),
        () => n2.command(() => {
          const { selection: i, storedMarks: s } = t;
          if (i.$from.parent.type.spec.isolating)
            return false;
          const { keepMarks: o } = this.options, { splittableMarks: l } = r.extensionManager, a = s || i.$to.parentOffset && i.$from.marks();
          return e().insertContent({ type: this.name }).command(({ tr: c, dispatch: u }) => {
            if (u && a && o) {
              const d = a.filter((f) => l.includes(f.type.name));
              c.ensureMarks(d);
            }
            return true;
          }).run();
        })
      ])
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Enter": () => this.editor.commands.setHardBreak(),
      "Shift-Enter": () => this.editor.commands.setHardBreak()
    };
  }
}), uw = /(?:^|\s)(\*\*(?!\s+\*\*)((?:[^*]+))\*\*(?!\s+\*\*))$/, dw = /(?:^|\s)(\*\*(?!\s+\*\*)((?:[^*]+))\*\*(?!\s+\*\*))/g, fw = /(?:^|\s)(__(?!\s+__)((?:[^_]+))__(?!\s+__))$/, hw = /(?:^|\s)(__(?!\s+__)((?:[^_]+))__(?!\s+__))/g, pw = lt.create({
  name: "bold",
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  parseHTML() {
    return [
      {
        tag: "strong"
      },
      {
        tag: "b",
        getAttrs: (n2) => n2.style.fontWeight !== "normal" && null
      },
      {
        style: "font-weight=400",
        clearMark: (n2) => n2.type.name === this.name
      },
      {
        style: "font-weight",
        getAttrs: (n2) => /^(bold(er)?|[5-9]\d{2,})$/.test(n2) && null
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["strong", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      setBold: () => ({ commands: n2 }) => n2.setMark(this.name),
      toggleBold: () => ({ commands: n2 }) => n2.toggleMark(this.name),
      unsetBold: () => ({ commands: n2 }) => n2.unsetMark(this.name)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-b": () => this.editor.commands.toggleBold(),
      "Mod-B": () => this.editor.commands.toggleBold()
    };
  },
  addInputRules() {
    return [
      sr({
        find: uw,
        type: this.type
      }),
      sr({
        find: fw,
        type: this.type
      })
    ];
  },
  addPasteRules() {
    return [
      Ln({
        find: dw,
        type: this.type
      }),
      Ln({
        find: hw,
        type: this.type
      })
    ];
  }
}), mw = /(?:^|\s)(\*(?!\s+\*)((?:[^*]+))\*(?!\s+\*))$/, gw = /(?:^|\s)(\*(?!\s+\*)((?:[^*]+))\*(?!\s+\*))/g, yw = /(?:^|\s)(_(?!\s+_)((?:[^_]+))_(?!\s+_))$/, bw = /(?:^|\s)(_(?!\s+_)((?:[^_]+))_(?!\s+_))/g, vw = lt.create({
  name: "italic",
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  parseHTML() {
    return [
      {
        tag: "em"
      },
      {
        tag: "i",
        getAttrs: (n2) => n2.style.fontStyle !== "normal" && null
      },
      {
        style: "font-style=normal",
        clearMark: (n2) => n2.type.name === this.name
      },
      {
        style: "font-style=italic"
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["em", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      setItalic: () => ({ commands: n2 }) => n2.setMark(this.name),
      toggleItalic: () => ({ commands: n2 }) => n2.toggleMark(this.name),
      unsetItalic: () => ({ commands: n2 }) => n2.unsetMark(this.name)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-i": () => this.editor.commands.toggleItalic(),
      "Mod-I": () => this.editor.commands.toggleItalic()
    };
  },
  addInputRules() {
    return [
      sr({
        find: mw,
        type: this.type
      }),
      sr({
        find: yw,
        type: this.type
      })
    ];
  },
  addPasteRules() {
    return [
      Ln({
        find: gw,
        type: this.type
      }),
      Ln({
        find: bw,
        type: this.type
      })
    ];
  }
}), ww = /(?:^|\s)(~~(?!\s+~~)((?:[^~]+))~~(?!\s+~~))$/, kw = /(?:^|\s)(~~(?!\s+~~)((?:[^~]+))~~(?!\s+~~))/g, Cw = lt.create({
  name: "strike",
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  parseHTML() {
    return [
      {
        tag: "s"
      },
      {
        tag: "del"
      },
      {
        tag: "strike"
      },
      {
        style: "text-decoration",
        consuming: false,
        getAttrs: (n2) => n2.includes("line-through") ? {} : false
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["s", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      setStrike: () => ({ commands: n2 }) => n2.setMark(this.name),
      toggleStrike: () => ({ commands: n2 }) => n2.toggleMark(this.name),
      unsetStrike: () => ({ commands: n2 }) => n2.unsetMark(this.name)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Shift-s": () => this.editor.commands.toggleStrike()
    };
  },
  addInputRules() {
    return [
      sr({
        find: ww,
        type: this.type
      })
    ];
  },
  addPasteRules() {
    return [
      Ln({
        find: kw,
        type: this.type
      })
    ];
  }
}), xw = lt.create({
  name: "underline",
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  parseHTML() {
    return [
      {
        tag: "u"
      },
      {
        style: "text-decoration",
        consuming: false,
        getAttrs: (n2) => n2.includes("underline") ? {} : false
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["u", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      setUnderline: () => ({ commands: n2 }) => n2.setMark(this.name),
      toggleUnderline: () => ({ commands: n2 }) => n2.toggleMark(this.name),
      unsetUnderline: () => ({ commands: n2 }) => n2.unsetMark(this.name)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-u": () => this.editor.commands.toggleUnderline(),
      "Mod-U": () => this.editor.commands.toggleUnderline()
    };
  }
}), Sw = /^```([a-z]+)?[\s\n]$/, Mw = /^~~~([a-z]+)?[\s\n]$/, Aw = ce.create({
  name: "codeBlock",
  addOptions() {
    return {
      languageClassPrefix: "language-",
      exitOnTripleEnter: true,
      exitOnArrowDown: true,
      defaultLanguage: null,
      HTMLAttributes: {}
    };
  },
  content: "text*",
  marks: "",
  group: "block",
  code: true,
  defining: true,
  addAttributes() {
    return {
      language: {
        default: this.options.defaultLanguage,
        parseHTML: (n2) => {
          var e;
          const { languageClassPrefix: t } = this.options, s = [...((e = n2.firstElementChild) === null || e === void 0 ? void 0 : e.classList) || []].filter((o) => o.startsWith(t)).map((o) => o.replace(t, ""))[0];
          return s || null;
        },
        rendered: false
      }
    };
  },
  parseHTML() {
    return [
      {
        tag: "pre",
        preserveWhitespace: "full"
      }
    ];
  },
  renderHTML({ node: n2, HTMLAttributes: e }) {
    return [
      "pre",
      Q(this.options.HTMLAttributes, e),
      [
        "code",
        {
          class: n2.attrs.language ? this.options.languageClassPrefix + n2.attrs.language : null
        },
        0
      ]
    ];
  },
  addCommands() {
    return {
      setCodeBlock: (n2) => ({ commands: e }) => e.setNode(this.name, n2),
      toggleCodeBlock: (n2) => ({ commands: e }) => e.toggleNode(this.name, "paragraph", n2)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Alt-c": () => this.editor.commands.toggleCodeBlock(),
      // remove code block when at start of document or code block is empty
      Backspace: () => {
        const { empty: n2, $anchor: e } = this.editor.state.selection, t = e.pos === 1;
        return !n2 || e.parent.type.name !== this.name ? false : t || !e.parent.textContent.length ? this.editor.commands.clearNodes() : false;
      },
      // exit node on triple enter
      Enter: ({ editor: n2 }) => {
        if (!this.options.exitOnTripleEnter)
          return false;
        const { state: e } = n2, { selection: t } = e, { $from: r, empty: i } = t;
        if (!i || r.parent.type !== this.type)
          return false;
        const s = r.parentOffset === r.parent.nodeSize - 2, o = r.parent.textContent.endsWith(`

`);
        return !s || !o ? false : n2.chain().command(({ tr: l }) => (l.delete(r.pos - 2, r.pos), true)).exitCode().run();
      },
      // exit node on arrow down
      ArrowDown: ({ editor: n2 }) => {
        if (!this.options.exitOnArrowDown)
          return false;
        const { state: e } = n2, { selection: t, doc: r } = e, { $from: i, empty: s } = t;
        if (!s || i.parent.type !== this.type || !(i.parentOffset === i.parent.nodeSize - 2))
          return false;
        const l = i.after();
        return l === void 0 ? false : r.nodeAt(l) ? n2.commands.command(({ tr: c }) => (c.setSelection($.near(r.resolve(l))), true)) : n2.commands.exitCode();
      }
    };
  },
  addInputRules() {
    return [
      sl({
        find: Sw,
        type: this.type,
        getAttributes: (n2) => ({
          language: n2[1]
        })
      }),
      sl({
        find: Mw,
        type: this.type,
        getAttributes: (n2) => ({
          language: n2[1]
        })
      })
    ];
  },
  addProseMirrorPlugins() {
    return [
      // this plugin creates a code block for pasted content from VS Code
      // we can also detect the copied code language
      new le({
        key: new ue("codeBlockVSCodeHandler"),
        props: {
          handlePaste: (n2, e) => {
            if (!e.clipboardData || this.editor.isActive(this.type.name))
              return false;
            const t = e.clipboardData.getData("text/plain"), r = e.clipboardData.getData("vscode-editor-data"), i = r ? JSON.parse(r) : void 0, s = i == null ? void 0 : i.mode;
            if (!t || !s)
              return false;
            const { tr: o, schema: l } = n2.state, a = l.text(t.replace(/\r\n?/g, `
`));
            return o.replaceSelectionWith(this.type.create({ language: s }, a)), o.selection.$from.parent.type !== this.type && o.setSelection(F.near(o.doc.resolve(Math.max(0, o.selection.from - 2)))), o.setMeta("paste", true), n2.dispatch(o), true;
          }
        }
      })
    ];
  }
}), Ew = ce.create({
  name: "heading",
  addOptions() {
    return {
      levels: [1, 2, 3, 4, 5, 6],
      HTMLAttributes: {}
    };
  },
  content: "inline*",
  group: "block",
  defining: true,
  addAttributes() {
    return {
      level: {
        default: 1,
        rendered: false
      }
    };
  },
  parseHTML() {
    return this.options.levels.map((n2) => ({
      tag: `h${n2}`,
      attrs: { level: n2 }
    }));
  },
  renderHTML({ node: n2, HTMLAttributes: e }) {
    return [`h${this.options.levels.includes(n2.attrs.level) ? n2.attrs.level : this.options.levels[0]}`, Q(this.options.HTMLAttributes, e), 0];
  },
  addCommands() {
    return {
      setHeading: (n2) => ({ commands: e }) => this.options.levels.includes(n2.level) ? e.setNode(this.name, n2) : false,
      toggleHeading: (n2) => ({ commands: e }) => this.options.levels.includes(n2.level) ? e.toggleNode(this.name, "paragraph", n2) : false
    };
  },
  addKeyboardShortcuts() {
    return this.options.levels.reduce((n2, e) => ({
      ...n2,
      [`Mod-Alt-${e}`]: () => this.editor.commands.toggleHeading({ level: e })
    }), {});
  },
  addInputRules() {
    return this.options.levels.map((n2) => sl({
      find: new RegExp(`^(#{${Math.min(...this.options.levels)},${n2}})\\s$`),
      type: this.type,
      getAttributes: {
        level: n2
      }
    }));
  }
}), Tw = (n2) => {
  if (!n2.children.length)
    return;
  const e = n2.querySelectorAll("span");
  e && e.forEach((t) => {
    var r, i;
    const s = t.getAttribute("style"), o = (i = (r = t.parentElement) === null || r === void 0 ? void 0 : r.closest("span")) === null || i === void 0 ? void 0 : i.getAttribute("style");
    t.setAttribute("style", `${o};${s}`);
  });
}, Ow = lt.create({
  name: "textStyle",
  priority: 101,
  addOptions() {
    return {
      HTMLAttributes: {},
      mergeNestedSpanStyles: false
    };
  },
  parseHTML() {
    return [
      {
        tag: "span",
        getAttrs: (n2) => n2.hasAttribute("style") ? (this.options.mergeNestedSpanStyles && Tw(n2), {}) : false
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["span", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      removeEmptyTextStyle: () => ({ tr: n2 }) => {
        const { selection: e } = n2;
        return n2.doc.nodesBetween(e.from, e.to, (t, r) => {
          if (t.isTextblock)
            return true;
          t.marks.filter((i) => i.type === this.type).some((i) => Object.values(i.attrs).some((s) => !!s)) || n2.removeMark(r, r + t.nodeSize, this.type);
        }), true;
      }
    };
  }
}), Nw = fe.create({
  name: "textAlign",
  addOptions() {
    return {
      types: [],
      alignments: ["left", "center", "right", "justify"],
      defaultAlignment: null
    };
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          textAlign: {
            default: this.options.defaultAlignment,
            parseHTML: (n2) => {
              const e = n2.style.textAlign;
              return this.options.alignments.includes(e) ? e : this.options.defaultAlignment;
            },
            renderHTML: (n2) => n2.textAlign ? { style: `text-align: ${n2.textAlign}` } : {}
          }
        }
      }
    ];
  },
  addCommands() {
    return {
      setTextAlign: (n2) => ({ commands: e }) => this.options.alignments.includes(n2) ? this.options.types.map((t) => e.updateAttributes(t, { textAlign: n2 })).every((t) => t) : false,
      unsetTextAlign: () => ({ commands: n2 }) => this.options.types.map((e) => n2.resetAttributes(e, "textAlign")).every((e) => e),
      toggleTextAlign: (n2) => ({ editor: e, commands: t }) => this.options.alignments.includes(n2) ? e.isActive({ textAlign: n2 }) ? t.unsetTextAlign() : t.setTextAlign(n2) : false
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Shift-l": () => this.editor.commands.setTextAlign("left"),
      "Mod-Shift-e": () => this.editor.commands.setTextAlign("center"),
      "Mod-Shift-r": () => this.editor.commands.setTextAlign("right"),
      "Mod-Shift-j": () => this.editor.commands.setTextAlign("justify")
    };
  }
}), Dw = fe.create({
  name: "fontFamily",
  addOptions() {
    return {
      types: ["textStyle"]
    };
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          fontFamily: {
            default: null,
            parseHTML: (n2) => n2.style.fontFamily,
            renderHTML: (n2) => n2.fontFamily ? {
              style: `font-family: ${n2.fontFamily}`
            } : {}
          }
        }
      }
    ];
  },
  addCommands() {
    return {
      setFontFamily: (n2) => ({ chain: e }) => e().setMark("textStyle", { fontFamily: n2 }).run(),
      unsetFontFamily: () => ({ chain: n2 }) => n2().setMark("textStyle", { fontFamily: null }).removeEmptyTextStyle().run()
    };
  }
});
var Lw = /* @__PURE__ */ fe.create({
  name: "fontSize",
  addOptions: function() {
    return {
      types: ["textStyle"]
    };
  },
  addGlobalAttributes: function() {
    return [{
      types: this.options.types,
      attributes: {
        fontSize: {
          default: null,
          parseHTML: function(t) {
            return t.style.fontSize.replace(/['"]+/g, "");
          },
          renderHTML: function(t) {
            return t.fontSize ? {
              style: "font-size: " + t.fontSize
            } : {};
          }
        }
      }
    }];
  },
  addCommands: function() {
    return {
      setFontSize: function(t) {
        return function(r) {
          var i = r.chain;
          return i().setMark("textStyle", {
            fontSize: t
          }).run();
        };
      },
      unsetFontSize: function() {
        return function(t) {
          var r = t.chain;
          return r().setMark("textStyle", {
            fontSize: null
          }).removeEmptyTextStyle().run();
        };
      }
    };
  }
});
const Rw = /(?:^|\s)(==(?!\s+==)((?:[^=]+))==(?!\s+==))$/, Iw = /(?:^|\s)(==(?!\s+==)((?:[^=]+))==(?!\s+==))/g, Pw = lt.create({
  name: "highlight",
  addOptions() {
    return {
      multicolor: false,
      HTMLAttributes: {}
    };
  },
  addAttributes() {
    return this.options.multicolor ? {
      color: {
        default: null,
        parseHTML: (n2) => n2.getAttribute("data-color") || n2.style.backgroundColor,
        renderHTML: (n2) => n2.color ? {
          "data-color": n2.color,
          style: `background-color: ${n2.color}; color: inherit`
        } : {}
      }
    } : {};
  },
  parseHTML() {
    return [
      {
        tag: "mark"
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["mark", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      setHighlight: (n2) => ({ commands: e }) => e.setMark(this.name, n2),
      toggleHighlight: (n2) => ({ commands: e }) => e.toggleMark(this.name, n2),
      unsetHighlight: () => ({ commands: n2 }) => n2.unsetMark(this.name)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Shift-h": () => this.editor.commands.toggleHighlight()
    };
  },
  addInputRules() {
    return [
      sr({
        find: Rw,
        type: this.type
      })
    ];
  },
  addPasteRules() {
    return [
      Ln({
        find: Iw,
        type: this.type
      })
    ];
  }
}), Bw = fe.create({
  name: "color",
  addOptions() {
    return {
      types: ["textStyle"]
    };
  },
  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          color: {
            default: null,
            parseHTML: (n2) => {
              var e;
              return (e = n2.style.color) === null || e === void 0 ? void 0 : e.replace(/['"]+/g, "");
            },
            renderHTML: (n2) => n2.color ? {
              style: `color: ${n2.color}`
            } : {}
          }
        }
      }
    ];
  },
  addCommands() {
    return {
      setColor: (n2) => ({ chain: e }) => e().setMark("textStyle", { color: n2 }).run(),
      unsetColor: () => ({ chain: n2 }) => n2().setMark("textStyle", { color: null }).removeEmptyTextStyle().run()
    };
  }
});
var dl, fl;
if (typeof WeakMap < "u") {
  let n2 = /* @__PURE__ */ new WeakMap();
  dl = (e) => n2.get(e), fl = (e, t) => (n2.set(e, t), t);
} else {
  const n2 = [];
  let t = 0;
  dl = (r) => {
    for (let i = 0; i < n2.length; i += 2)
      if (n2[i] == r) return n2[i + 1];
  }, fl = (r, i) => (t == 10 && (t = 0), n2[t++] = r, n2[t++] = i);
}
var oe = class {
  constructor(n2, e, t, r) {
    this.width = n2, this.height = e, this.map = t, this.problems = r;
  }
  // Find the dimensions of the cell at the given position.
  findCell(n2) {
    for (let e = 0; e < this.map.length; e++) {
      const t = this.map[e];
      if (t != n2) continue;
      const r = e % this.width, i = e / this.width | 0;
      let s = r + 1, o = i + 1;
      for (let l = 1; s < this.width && this.map[e + l] == t; l++)
        s++;
      for (let l = 1; o < this.height && this.map[e + this.width * l] == t; l++)
        o++;
      return { left: r, top: i, right: s, bottom: o };
    }
    throw new RangeError(`No cell with offset ${n2} found`);
  }
  // Find the left side of the cell at the given position.
  colCount(n2) {
    for (let e = 0; e < this.map.length; e++)
      if (this.map[e] == n2)
        return e % this.width;
    throw new RangeError(`No cell with offset ${n2} found`);
  }
  // Find the next cell in the given direction, starting from the cell
  // at `pos`, if any.
  nextCell(n2, e, t) {
    const { left: r, right: i, top: s, bottom: o } = this.findCell(n2);
    return e == "horiz" ? (t < 0 ? r == 0 : i == this.width) ? null : this.map[s * this.width + (t < 0 ? r - 1 : i)] : (t < 0 ? s == 0 : o == this.height) ? null : this.map[r + this.width * (t < 0 ? s - 1 : o)];
  }
  // Get the rectangle spanning the two given cells.
  rectBetween(n2, e) {
    const {
      left: t,
      right: r,
      top: i,
      bottom: s
    } = this.findCell(n2), {
      left: o,
      right: l,
      top: a,
      bottom: c
    } = this.findCell(e);
    return {
      left: Math.min(t, o),
      top: Math.min(i, a),
      right: Math.max(r, l),
      bottom: Math.max(s, c)
    };
  }
  // Return the position of all cells that have the top left corner in
  // the given rectangle.
  cellsInRect(n2) {
    const e = [], t = {};
    for (let r = n2.top; r < n2.bottom; r++)
      for (let i = n2.left; i < n2.right; i++) {
        const s = r * this.width + i, o = this.map[s];
        t[o] || (t[o] = true, !(i == n2.left && i && this.map[s - 1] == o || r == n2.top && r && this.map[s - this.width] == o) && e.push(o));
      }
    return e;
  }
  // Return the position at which the cell at the given row and column
  // starts, or would start, if a cell started there.
  positionAt(n2, e, t) {
    for (let r = 0, i = 0; ; r++) {
      const s = i + t.child(r).nodeSize;
      if (r == n2) {
        let o = e + n2 * this.width;
        const l = (n2 + 1) * this.width;
        for (; o < l && this.map[o] < i; ) o++;
        return o == l ? s - 1 : this.map[o];
      }
      i = s;
    }
  }
  // Find the table map for the given table node.
  static get(n2) {
    return dl(n2) || fl(n2, Hw(n2));
  }
};
function Hw(n2) {
  if (n2.type.spec.tableRole != "table")
    throw new RangeError("Not a table node: " + n2.type.name);
  const e = Fw(n2), t = n2.childCount, r = [];
  let i = 0, s = null;
  const o = [];
  for (let c = 0, u = e * t; c < u; c++) r[c] = 0;
  for (let c = 0, u = 0; c < t; c++) {
    const d = n2.child(c);
    u++;
    for (let p2 = 0; ; p2++) {
      for (; i < r.length && r[i] != 0; ) i++;
      if (p2 == d.childCount) break;
      const m = d.child(p2), { colspan: g, rowspan: y, colwidth: w } = m.attrs;
      for (let C = 0; C < y; C++) {
        if (C + c >= t) {
          (s || (s = [])).push({
            type: "overlong_rowspan",
            pos: u,
            n: y - C
          });
          break;
        }
        const b = i + C * e;
        for (let S = 0; S < g; S++) {
          r[b + S] == 0 ? r[b + S] = u : (s || (s = [])).push({
            type: "collision",
            row: c,
            pos: u,
            n: g - S
          });
          const k = w && w[S];
          if (k) {
            const T = (b + S) % e * 2, M = o[T];
            M == null || M != k && o[T + 1] == 1 ? (o[T] = k, o[T + 1] = 1) : M == k && o[T + 1]++;
          }
        }
      }
      i += g, u += m.nodeSize;
    }
    const f = (c + 1) * e;
    let h2 = 0;
    for (; i < f; ) r[i++] == 0 && h2++;
    h2 && (s || (s = [])).push({ type: "missing", row: c, n: h2 }), u++;
  }
  (e === 0 || t === 0) && (s || (s = [])).push({ type: "zero_sized" });
  const l = new oe(e, t, r, s);
  let a = false;
  for (let c = 0; !a && c < o.length; c += 2)
    o[c] != null && o[c + 1] < t && (a = true);
  return a && zw(l, o, n2), l;
}
function Fw(n2) {
  let e = -1, t = false;
  for (let r = 0; r < n2.childCount; r++) {
    const i = n2.child(r);
    let s = 0;
    if (t)
      for (let o = 0; o < r; o++) {
        const l = n2.child(o);
        for (let a = 0; a < l.childCount; a++) {
          const c = l.child(a);
          o + c.attrs.rowspan > r && (s += c.attrs.colspan);
        }
      }
    for (let o = 0; o < i.childCount; o++) {
      const l = i.child(o);
      s += l.attrs.colspan, l.attrs.rowspan > 1 && (t = true);
    }
    e == -1 ? e = s : e != s && (e = Math.max(e, s));
  }
  return e;
}
function zw(n2, e, t) {
  n2.problems || (n2.problems = []);
  const r = {};
  for (let i = 0; i < n2.map.length; i++) {
    const s = n2.map[i];
    if (r[s]) continue;
    r[s] = true;
    const o = t.nodeAt(s);
    if (!o)
      throw new RangeError(`No cell with offset ${s} found`);
    let l = null;
    const a = o.attrs;
    for (let c = 0; c < a.colspan; c++) {
      const u = (i + c) % n2.width, d = e[u * 2];
      d != null && (!a.colwidth || a.colwidth[c] != d) && ((l || (l = Vw(a)))[c] = d);
    }
    l && n2.problems.unshift({
      type: "colwidth mismatch",
      pos: s,
      colwidth: l
    });
  }
}
function Vw(n2) {
  if (n2.colwidth) return n2.colwidth.slice();
  const e = [];
  for (let t = 0; t < n2.colspan; t++) e.push(0);
  return e;
}
function Se(n2) {
  let e = n2.cached.tableNodeTypes;
  if (!e) {
    e = n2.cached.tableNodeTypes = {};
    for (const t in n2.nodes) {
      const r = n2.nodes[t], i = r.spec.tableRole;
      i && (e[i] = r);
    }
  }
  return e;
}
var Ut = new ue("selectingCells");
function pr(n2) {
  for (let e = n2.depth - 1; e > 0; e--)
    if (n2.node(e).type.spec.tableRole == "row")
      return n2.node(0).resolve(n2.before(e + 1));
  return null;
}
function $w(n2) {
  for (let e = n2.depth; e > 0; e--) {
    const t = n2.node(e).type.spec.tableRole;
    if (t === "cell" || t === "header_cell") return n2.node(e);
  }
  return null;
}
function at(n2) {
  const e = n2.selection.$head;
  for (let t = e.depth; t > 0; t--)
    if (e.node(t).type.spec.tableRole == "row") return true;
  return false;
}
function ro(n2) {
  const e = n2.selection;
  if ("$anchorCell" in e && e.$anchorCell)
    return e.$anchorCell.pos > e.$headCell.pos ? e.$anchorCell : e.$headCell;
  if ("node" in e && e.node && e.node.type.spec.tableRole == "cell")
    return e.$anchor;
  const t = pr(e.$head) || _w(e.$head);
  if (t)
    return t;
  throw new RangeError(`No cell found around position ${e.head}`);
}
function _w(n2) {
  for (let e = n2.nodeAfter, t = n2.pos; e; e = e.firstChild, t++) {
    const r = e.type.spec.tableRole;
    if (r == "cell" || r == "header_cell") return n2.doc.resolve(t);
  }
  for (let e = n2.nodeBefore, t = n2.pos; e; e = e.lastChild, t--) {
    const r = e.type.spec.tableRole;
    if (r == "cell" || r == "header_cell")
      return n2.doc.resolve(t - e.nodeSize);
  }
}
function hl(n2) {
  return n2.parent.type.spec.tableRole == "row" && !!n2.nodeAfter;
}
function jw(n2) {
  return n2.node(0).resolve(n2.pos + n2.nodeAfter.nodeSize);
}
function fa(n2, e) {
  return n2.depth == e.depth && n2.pos >= e.start(-1) && n2.pos <= e.end(-1);
}
function Gf(n2, e, t) {
  const r = n2.node(-1), i = oe.get(r), s = n2.start(-1), o = i.nextCell(n2.pos - s, e, t);
  return o == null ? null : n2.node(0).resolve(s + o);
}
function In(n2, e, t = 1) {
  const r = { ...n2, colspan: n2.colspan - t };
  return r.colwidth && (r.colwidth = r.colwidth.slice(), r.colwidth.splice(e, t), r.colwidth.some((i) => i > 0) || (r.colwidth = null)), r;
}
function Yf(n2, e, t = 1) {
  const r = { ...n2, colspan: n2.colspan + t };
  if (r.colwidth) {
    r.colwidth = r.colwidth.slice();
    for (let i = 0; i < t; i++) r.colwidth.splice(e, 0, 0);
  }
  return r;
}
function Ww(n2, e, t) {
  const r = Se(e.type.schema).header_cell;
  for (let i = 0; i < n2.height; i++)
    if (e.nodeAt(n2.map[t + i * n2.width]).type != r)
      return false;
  return true;
}
var ne = class Et extends $ {
  // A table selection is identified by its anchor and head cells. The
  // positions given to this constructor should point _before_ two
  // cells in the same table. They may be the same, to select a single
  // cell.
  constructor(e, t = e) {
    const r = e.node(-1), i = oe.get(r), s = e.start(-1), o = i.rectBetween(
      e.pos - s,
      t.pos - s
    ), l = e.node(0), a = i.cellsInRect(o).filter((u) => u != t.pos - s);
    a.unshift(t.pos - s);
    const c = a.map((u) => {
      const d = r.nodeAt(u);
      if (!d)
        throw RangeError(`No cell with offset ${u} found`);
      const f = s + u + 1;
      return new fd(
        l.resolve(f),
        l.resolve(f + d.content.size)
      );
    });
    super(c[0].$from, c[0].$to, c), this.$anchorCell = e, this.$headCell = t;
  }
  map(e, t) {
    const r = e.resolve(t.map(this.$anchorCell.pos)), i = e.resolve(t.map(this.$headCell.pos));
    if (hl(r) && hl(i) && fa(r, i)) {
      const s = this.$anchorCell.node(-1) != r.node(-1);
      return s && this.isRowSelection() ? Et.rowSelection(r, i) : s && this.isColSelection() ? Et.colSelection(r, i) : new Et(r, i);
    }
    return F.between(r, i);
  }
  // Returns a rectangular slice of table rows containing the selected
  // cells.
  content() {
    const e = this.$anchorCell.node(-1), t = oe.get(e), r = this.$anchorCell.start(-1), i = t.rectBetween(
      this.$anchorCell.pos - r,
      this.$headCell.pos - r
    ), s = {}, o = [];
    for (let a = i.top; a < i.bottom; a++) {
      const c = [];
      for (let u = a * t.width + i.left, d = i.left; d < i.right; d++, u++) {
        const f = t.map[u];
        if (s[f]) continue;
        s[f] = true;
        const h2 = t.findCell(f);
        let p2 = e.nodeAt(f);
        if (!p2)
          throw RangeError(`No cell with offset ${f} found`);
        const m = i.left - h2.left, g = h2.right - i.right;
        if (m > 0 || g > 0) {
          let y = p2.attrs;
          if (m > 0 && (y = In(y, 0, m)), g > 0 && (y = In(
            y,
            y.colspan - g,
            g
          )), h2.left < i.left) {
            if (p2 = p2.type.createAndFill(y), !p2)
              throw RangeError(
                `Could not create cell with attrs ${JSON.stringify(y)}`
              );
          } else
            p2 = p2.type.create(y, p2.content);
        }
        if (h2.top < i.top || h2.bottom > i.bottom) {
          const y = {
            ...p2.attrs,
            rowspan: Math.min(h2.bottom, i.bottom) - Math.max(h2.top, i.top)
          };
          h2.top < i.top ? p2 = p2.type.createAndFill(y) : p2 = p2.type.create(y, p2.content);
        }
        c.push(p2);
      }
      o.push(e.child(a).copy(A.from(c)));
    }
    const l = this.isColSelection() && this.isRowSelection() ? e : o;
    return new O(A.from(l), 1, 1);
  }
  replace(e, t = O.empty) {
    const r = e.steps.length, i = this.ranges;
    for (let o = 0; o < i.length; o++) {
      const { $from: l, $to: a } = i[o], c = e.mapping.slice(r);
      e.replace(
        c.map(l.pos),
        c.map(a.pos),
        o ? O.empty : t
      );
    }
    const s = $.findFrom(
      e.doc.resolve(e.mapping.slice(r).map(this.to)),
      -1
    );
    s && e.setSelection(s);
  }
  replaceWith(e, t) {
    this.replace(e, new O(A.from(t), 0, 0));
  }
  forEachCell(e) {
    const t = this.$anchorCell.node(-1), r = oe.get(t), i = this.$anchorCell.start(-1), s = r.cellsInRect(
      r.rectBetween(
        this.$anchorCell.pos - i,
        this.$headCell.pos - i
      )
    );
    for (let o = 0; o < s.length; o++)
      e(t.nodeAt(s[o]), i + s[o]);
  }
  // True if this selection goes all the way from the top to the
  // bottom of the table.
  isColSelection() {
    const e = this.$anchorCell.index(-1), t = this.$headCell.index(-1);
    if (Math.min(e, t) > 0) return false;
    const r = e + this.$anchorCell.nodeAfter.attrs.rowspan, i = t + this.$headCell.nodeAfter.attrs.rowspan;
    return Math.max(r, i) == this.$headCell.node(-1).childCount;
  }
  // Returns the smallest column selection that covers the given anchor
  // and head cell.
  static colSelection(e, t = e) {
    const r = e.node(-1), i = oe.get(r), s = e.start(-1), o = i.findCell(e.pos - s), l = i.findCell(t.pos - s), a = e.node(0);
    return o.top <= l.top ? (o.top > 0 && (e = a.resolve(s + i.map[o.left])), l.bottom < i.height && (t = a.resolve(
      s + i.map[i.width * (i.height - 1) + l.right - 1]
    ))) : (l.top > 0 && (t = a.resolve(s + i.map[l.left])), o.bottom < i.height && (e = a.resolve(
      s + i.map[i.width * (i.height - 1) + o.right - 1]
    ))), new Et(e, t);
  }
  // True if this selection goes all the way from the left to the
  // right of the table.
  isRowSelection() {
    const e = this.$anchorCell.node(-1), t = oe.get(e), r = this.$anchorCell.start(-1), i = t.colCount(this.$anchorCell.pos - r), s = t.colCount(this.$headCell.pos - r);
    if (Math.min(i, s) > 0) return false;
    const o = i + this.$anchorCell.nodeAfter.attrs.colspan, l = s + this.$headCell.nodeAfter.attrs.colspan;
    return Math.max(o, l) == t.width;
  }
  eq(e) {
    return e instanceof Et && e.$anchorCell.pos == this.$anchorCell.pos && e.$headCell.pos == this.$headCell.pos;
  }
  // Returns the smallest row selection that covers the given anchor
  // and head cell.
  static rowSelection(e, t = e) {
    const r = e.node(-1), i = oe.get(r), s = e.start(-1), o = i.findCell(e.pos - s), l = i.findCell(t.pos - s), a = e.node(0);
    return o.left <= l.left ? (o.left > 0 && (e = a.resolve(
      s + i.map[o.top * i.width]
    )), l.right < i.width && (t = a.resolve(
      s + i.map[i.width * (l.top + 1) - 1]
    ))) : (l.left > 0 && (t = a.resolve(s + i.map[l.top * i.width])), o.right < i.width && (e = a.resolve(
      s + i.map[i.width * (o.top + 1) - 1]
    ))), new Et(e, t);
  }
  toJSON() {
    return {
      type: "cell",
      anchor: this.$anchorCell.pos,
      head: this.$headCell.pos
    };
  }
  static fromJSON(e, t) {
    return new Et(e.resolve(t.anchor), e.resolve(t.head));
  }
  static create(e, t, r = t) {
    return new Et(e.resolve(t), e.resolve(r));
  }
  getBookmark() {
    return new Uw(this.$anchorCell.pos, this.$headCell.pos);
  }
};
ne.prototype.visible = false;
$.jsonID("cell", ne);
var Uw = class Xf {
  constructor(e, t) {
    this.anchor = e, this.head = t;
  }
  map(e) {
    return new Xf(e.map(this.anchor), e.map(this.head));
  }
  resolve(e) {
    const t = e.resolve(this.anchor), r = e.resolve(this.head);
    return t.parent.type.spec.tableRole == "row" && r.parent.type.spec.tableRole == "row" && t.index() < t.parent.childCount && r.index() < r.parent.childCount && fa(t, r) ? new ne(t, r) : $.near(r, 1);
  }
};
function Kw(n2) {
  if (!(n2.selection instanceof ne)) return null;
  const e = [];
  return n2.selection.forEachCell((t, r) => {
    e.push(
      xe.node(r, r + t.nodeSize, { class: "selectedCell" })
    );
  }), ie.create(n2.doc, e);
}
function qw({ $from: n2, $to: e }) {
  if (n2.pos == e.pos || n2.pos < e.pos - 6) return false;
  let t = n2.pos, r = e.pos, i = n2.depth;
  for (; i >= 0 && !(n2.after(i + 1) < n2.end(i)); i--, t++)
    ;
  for (let s = e.depth; s >= 0 && !(e.before(s + 1) > e.start(s)); s--, r--)
    ;
  return t == r && /row|table/.test(n2.node(i).type.spec.tableRole);
}
function Jw({ $from: n2, $to: e }) {
  let t, r;
  for (let i = n2.depth; i > 0; i--) {
    const s = n2.node(i);
    if (s.type.spec.tableRole === "cell" || s.type.spec.tableRole === "header_cell") {
      t = s;
      break;
    }
  }
  for (let i = e.depth; i > 0; i--) {
    const s = e.node(i);
    if (s.type.spec.tableRole === "cell" || s.type.spec.tableRole === "header_cell") {
      r = s;
      break;
    }
  }
  return t !== r && e.parentOffset === 0;
}
function Gw(n2, e, t) {
  const r = (e || n2).selection, i = (e || n2).doc;
  let s, o;
  if (r instanceof B && (o = r.node.type.spec.tableRole)) {
    if (o == "cell" || o == "header_cell")
      s = ne.create(i, r.from);
    else if (o == "row") {
      const l = i.resolve(r.from + 1);
      s = ne.rowSelection(l, l);
    } else if (!t) {
      const l = oe.get(r.node), a = r.from + 1, c = a + l.map[l.width * l.height - 1];
      s = ne.create(i, a + 1, c);
    }
  } else r instanceof F && qw(r) ? s = F.create(i, r.from) : r instanceof F && Jw(r) && (s = F.create(i, r.$from.start(), r.$from.end()));
  return s && (e || (e = n2.tr)).setSelection(s), e;
}
var Yw = new ue("fix-tables");
function Qf(n2, e, t, r) {
  const i = n2.childCount, s = e.childCount;
  e: for (let o = 0, l = 0; o < s; o++) {
    const a = e.child(o);
    for (let c = l, u = Math.min(i, o + 3); c < u; c++)
      if (n2.child(c) == a) {
        l = c + 1, t += a.nodeSize;
        continue e;
      }
    r(a, t), l < i && n2.child(l).sameMarkup(a) ? Qf(n2.child(l), a, t + 1, r) : a.nodesBetween(0, a.content.size, r, t + 1), t += a.nodeSize;
  }
}
function Zf(n2, e) {
  let t;
  const r = (i, s) => {
    i.type.spec.tableRole == "table" && (t = Xw(n2, i, s, t));
  };
  return e ? e.doc != n2.doc && Qf(e.doc, n2.doc, 0, r) : n2.doc.descendants(r), t;
}
function Xw(n2, e, t, r) {
  const i = oe.get(e);
  if (!i.problems) return r;
  r || (r = n2.tr);
  const s = [];
  for (let a = 0; a < i.height; a++) s.push(0);
  for (let a = 0; a < i.problems.length; a++) {
    const c = i.problems[a];
    if (c.type == "collision") {
      const u = e.nodeAt(c.pos);
      if (!u) continue;
      const d = u.attrs;
      for (let f = 0; f < d.rowspan; f++) s[c.row + f] += c.n;
      r.setNodeMarkup(
        r.mapping.map(t + 1 + c.pos),
        null,
        In(d, d.colspan - c.n, c.n)
      );
    } else if (c.type == "missing")
      s[c.row] += c.n;
    else if (c.type == "overlong_rowspan") {
      const u = e.nodeAt(c.pos);
      if (!u) continue;
      r.setNodeMarkup(r.mapping.map(t + 1 + c.pos), null, {
        ...u.attrs,
        rowspan: u.attrs.rowspan - c.n
      });
    } else if (c.type == "colwidth mismatch") {
      const u = e.nodeAt(c.pos);
      if (!u) continue;
      r.setNodeMarkup(r.mapping.map(t + 1 + c.pos), null, {
        ...u.attrs,
        colwidth: c.colwidth
      });
    } else if (c.type == "zero_sized") {
      const u = r.mapping.map(t);
      r.delete(u, u + e.nodeSize);
    }
  }
  let o, l;
  for (let a = 0; a < s.length; a++)
    s[a] && (o == null && (o = a), l = a);
  for (let a = 0, c = t + 1; a < i.height; a++) {
    const u = e.child(a), d = c + u.nodeSize, f = s[a];
    if (f > 0) {
      let h2 = "cell";
      u.firstChild && (h2 = u.firstChild.type.spec.tableRole);
      const p2 = [];
      for (let g = 0; g < f; g++) {
        const y = Se(n2.schema)[h2].createAndFill();
        y && p2.push(y);
      }
      const m = (a == 0 || o == a - 1) && l == a ? c + 1 : d - 1;
      r.insert(r.mapping.map(m), p2);
    }
    c = d;
  }
  return r.setMeta(Yw, { fixTables: true });
}
function vt(n2) {
  const e = n2.selection, t = ro(n2), r = t.node(-1), i = t.start(-1), s = oe.get(r);
  return { ...e instanceof ne ? s.rectBetween(
    e.$anchorCell.pos - i,
    e.$headCell.pos - i
  ) : s.findCell(t.pos - i), tableStart: i, map: s, table: r };
}
function eh(n2, { map: e, tableStart: t, table: r }, i) {
  let s = i > 0 ? -1 : 0;
  Ww(e, r, i + s) && (s = i == 0 || i == e.width ? null : 0);
  for (let o = 0; o < e.height; o++) {
    const l = o * e.width + i;
    if (i > 0 && i < e.width && e.map[l - 1] == e.map[l]) {
      const a = e.map[l], c = r.nodeAt(a);
      n2.setNodeMarkup(
        n2.mapping.map(t + a),
        null,
        Yf(c.attrs, i - e.colCount(a))
      ), o += c.attrs.rowspan - 1;
    } else {
      const a = s == null ? Se(r.type.schema).cell : r.nodeAt(e.map[l + s]).type, c = e.positionAt(o, i, r);
      n2.insert(n2.mapping.map(t + c), a.createAndFill());
    }
  }
  return n2;
}
function Qw(n2, e) {
  if (!at(n2)) return false;
  if (e) {
    const t = vt(n2);
    e(eh(n2.tr, t, t.left));
  }
  return true;
}
function Zw(n2, e) {
  if (!at(n2)) return false;
  if (e) {
    const t = vt(n2);
    e(eh(n2.tr, t, t.right));
  }
  return true;
}
function ek(n2, { map: e, table: t, tableStart: r }, i) {
  const s = n2.mapping.maps.length;
  for (let o = 0; o < e.height; ) {
    const l = o * e.width + i, a = e.map[l], c = t.nodeAt(a), u = c.attrs;
    if (i > 0 && e.map[l - 1] == a || i < e.width - 1 && e.map[l + 1] == a)
      n2.setNodeMarkup(
        n2.mapping.slice(s).map(r + a),
        null,
        In(u, i - e.colCount(a))
      );
    else {
      const d = n2.mapping.slice(s).map(r + a);
      n2.delete(d, d + c.nodeSize);
    }
    o += u.rowspan;
  }
}
function tk(n2, e) {
  if (!at(n2)) return false;
  if (e) {
    const t = vt(n2), r = n2.tr;
    if (t.left == 0 && t.right == t.map.width) return false;
    for (let i = t.right - 1; ek(r, t, i), i != t.left; i--) {
      const s = t.tableStart ? r.doc.nodeAt(t.tableStart - 1) : r.doc;
      if (!s)
        throw RangeError("No table found");
      t.table = s, t.map = oe.get(s);
    }
    e(r);
  }
  return true;
}
function nk(n2, e, t) {
  var r;
  const i = Se(e.type.schema).header_cell;
  for (let s = 0; s < n2.width; s++)
    if (((r = e.nodeAt(n2.map[s + t * n2.width])) == null ? void 0 : r.type) != i)
      return false;
  return true;
}
function th(n2, { map: e, tableStart: t, table: r }, i) {
  var s;
  let o = t;
  for (let c = 0; c < i; c++) o += r.child(c).nodeSize;
  const l = [];
  let a = i > 0 ? -1 : 0;
  nk(e, r, i + a) && (a = i == 0 || i == e.height ? null : 0);
  for (let c = 0, u = e.width * i; c < e.width; c++, u++)
    if (i > 0 && i < e.height && e.map[u] == e.map[u - e.width]) {
      const d = e.map[u], f = r.nodeAt(d).attrs;
      n2.setNodeMarkup(t + d, null, {
        ...f,
        rowspan: f.rowspan + 1
      }), c += f.colspan - 1;
    } else {
      const d = a == null ? Se(r.type.schema).cell : (s = r.nodeAt(e.map[u + a * e.width])) == null ? void 0 : s.type, f = d == null ? void 0 : d.createAndFill();
      f && l.push(f);
    }
  return n2.insert(o, Se(r.type.schema).row.create(null, l)), n2;
}
function rk(n2, e) {
  if (!at(n2)) return false;
  if (e) {
    const t = vt(n2);
    e(th(n2.tr, t, t.top));
  }
  return true;
}
function ik(n2, e) {
  if (!at(n2)) return false;
  if (e) {
    const t = vt(n2);
    e(th(n2.tr, t, t.bottom));
  }
  return true;
}
function sk(n2, { map: e, table: t, tableStart: r }, i) {
  let s = 0;
  for (let c = 0; c < i; c++) s += t.child(c).nodeSize;
  const o = s + t.child(i).nodeSize, l = n2.mapping.maps.length;
  n2.delete(s + r, o + r);
  const a = /* @__PURE__ */ new Set();
  for (let c = 0, u = i * e.width; c < e.width; c++, u++) {
    const d = e.map[u];
    if (!a.has(d)) {
      if (a.add(d), i > 0 && d == e.map[u - e.width]) {
        const f = t.nodeAt(d).attrs;
        n2.setNodeMarkup(n2.mapping.slice(l).map(d + r), null, {
          ...f,
          rowspan: f.rowspan - 1
        }), c += f.colspan - 1;
      } else if (i < e.height && d == e.map[u + e.width]) {
        const f = t.nodeAt(d), h2 = f.attrs, p2 = f.type.create(
          { ...h2, rowspan: f.attrs.rowspan - 1 },
          f.content
        ), m = e.positionAt(i + 1, c, t);
        n2.insert(n2.mapping.slice(l).map(r + m), p2), c += h2.colspan - 1;
      }
    }
  }
}
function ok(n2, e) {
  if (!at(n2)) return false;
  if (e) {
    const t = vt(n2), r = n2.tr;
    if (t.top == 0 && t.bottom == t.map.height) return false;
    for (let i = t.bottom - 1; sk(r, t, i), i != t.top; i--) {
      const s = t.tableStart ? r.doc.nodeAt(t.tableStart - 1) : r.doc;
      if (!s)
        throw RangeError("No table found");
      t.table = s, t.map = oe.get(t.table);
    }
    e(r);
  }
  return true;
}
function Xc(n2) {
  const e = n2.content;
  return e.childCount == 1 && e.child(0).isTextblock && e.child(0).childCount == 0;
}
function lk({ width: n2, height: e, map: t }, r) {
  let i = r.top * n2 + r.left, s = i, o = (r.bottom - 1) * n2 + r.left, l = i + (r.right - r.left - 1);
  for (let a = r.top; a < r.bottom; a++) {
    if (r.left > 0 && t[s] == t[s - 1] || r.right < n2 && t[l] == t[l + 1])
      return true;
    s += n2, l += n2;
  }
  for (let a = r.left; a < r.right; a++) {
    if (r.top > 0 && t[i] == t[i - n2] || r.bottom < e && t[o] == t[o + n2])
      return true;
    i++, o++;
  }
  return false;
}
function Qc(n2, e) {
  const t = n2.selection;
  if (!(t instanceof ne) || t.$anchorCell.pos == t.$headCell.pos)
    return false;
  const r = vt(n2), { map: i } = r;
  if (lk(i, r)) return false;
  if (e) {
    const s = n2.tr, o = {};
    let l = A.empty, a, c;
    for (let u = r.top; u < r.bottom; u++)
      for (let d = r.left; d < r.right; d++) {
        const f = i.map[u * i.width + d], h2 = r.table.nodeAt(f);
        if (!(o[f] || !h2))
          if (o[f] = true, a == null)
            a = f, c = h2;
          else {
            Xc(h2) || (l = l.append(h2.content));
            const p2 = s.mapping.map(f + r.tableStart);
            s.delete(p2, p2 + h2.nodeSize);
          }
      }
    if (a == null || c == null)
      return true;
    if (s.setNodeMarkup(a + r.tableStart, null, {
      ...Yf(
        c.attrs,
        c.attrs.colspan,
        r.right - r.left - c.attrs.colspan
      ),
      rowspan: r.bottom - r.top
    }), l.size) {
      const u = a + 1 + c.content.size, d = Xc(c) ? a + 1 : u;
      s.replaceWith(d + r.tableStart, u + r.tableStart, l);
    }
    s.setSelection(
      new ne(s.doc.resolve(a + r.tableStart))
    ), e(s);
  }
  return true;
}
function Zc(n2, e) {
  const t = Se(n2.schema);
  return ak(({ node: r }) => t[r.type.spec.tableRole])(n2, e);
}
function ak(n2) {
  return (e, t) => {
    var r;
    const i = e.selection;
    let s, o;
    if (i instanceof ne) {
      if (i.$anchorCell.pos != i.$headCell.pos) return false;
      s = i.$anchorCell.nodeAfter, o = i.$anchorCell.pos;
    } else {
      if (s = $w(i.$from), !s) return false;
      o = (r = pr(i.$from)) == null ? void 0 : r.pos;
    }
    if (s == null || o == null || s.attrs.colspan == 1 && s.attrs.rowspan == 1)
      return false;
    if (t) {
      let l = s.attrs;
      const a = [], c = l.colwidth;
      l.rowspan > 1 && (l = { ...l, rowspan: 1 }), l.colspan > 1 && (l = { ...l, colspan: 1 });
      const u = vt(e), d = e.tr;
      for (let h2 = 0; h2 < u.right - u.left; h2++)
        a.push(
          c ? {
            ...l,
            colwidth: c && c[h2] ? [c[h2]] : null
          } : l
        );
      let f;
      for (let h2 = u.top; h2 < u.bottom; h2++) {
        let p2 = u.map.positionAt(h2, u.left, u.table);
        h2 == u.top && (p2 += s.nodeSize);
        for (let m = u.left, g = 0; m < u.right; m++, g++)
          m == u.left && h2 == u.top || d.insert(
            f = d.mapping.map(p2 + u.tableStart, 1),
            n2({ node: s, row: h2, col: m }).createAndFill(a[g])
          );
      }
      d.setNodeMarkup(
        o,
        n2({ node: s, row: u.top, col: u.left }),
        a[0]
      ), i instanceof ne && d.setSelection(
        new ne(
          d.doc.resolve(i.$anchorCell.pos),
          f ? d.doc.resolve(f) : void 0
        )
      ), t(d);
    }
    return true;
  };
}
function ck(n2, e) {
  return function(t, r) {
    if (!at(t)) return false;
    const i = ro(t);
    if (i.nodeAfter.attrs[n2] === e) return false;
    if (r) {
      const s = t.tr;
      t.selection instanceof ne ? t.selection.forEachCell((o, l) => {
        o.attrs[n2] !== e && s.setNodeMarkup(l, null, {
          ...o.attrs,
          [n2]: e
        });
      }) : s.setNodeMarkup(i.pos, null, {
        ...i.nodeAfter.attrs,
        [n2]: e
      }), r(s);
    }
    return true;
  };
}
function uk(n2) {
  return function(e, t) {
    if (!at(e)) return false;
    if (t) {
      const r = Se(e.schema), i = vt(e), s = e.tr, o = i.map.cellsInRect(
        n2 == "column" ? {
          left: i.left,
          top: 0,
          right: i.right,
          bottom: i.map.height
        } : n2 == "row" ? {
          left: 0,
          top: i.top,
          right: i.map.width,
          bottom: i.bottom
        } : i
      ), l = o.map((a) => i.table.nodeAt(a));
      for (let a = 0; a < o.length; a++)
        l[a].type == r.header_cell && s.setNodeMarkup(
          i.tableStart + o[a],
          r.cell,
          l[a].attrs
        );
      if (s.steps.length == 0)
        for (let a = 0; a < o.length; a++)
          s.setNodeMarkup(
            i.tableStart + o[a],
            r.header_cell,
            l[a].attrs
          );
      t(s);
    }
    return true;
  };
}
function eu(n2, e, t) {
  const r = e.map.cellsInRect({
    left: 0,
    top: 0,
    right: n2 == "row" ? e.map.width : 1,
    bottom: n2 == "column" ? e.map.height : 1
  });
  for (let i = 0; i < r.length; i++) {
    const s = e.table.nodeAt(r[i]);
    if (s && s.type !== t.header_cell)
      return false;
  }
  return true;
}
function ti(n2, e) {
  return e = e || { useDeprecatedLogic: false }, e.useDeprecatedLogic ? uk(n2) : function(t, r) {
    if (!at(t)) return false;
    if (r) {
      const i = Se(t.schema), s = vt(t), o = t.tr, l = eu("row", s, i), a = eu(
        "column",
        s,
        i
      ), u = (n2 === "column" ? l : n2 === "row" ? a : false) ? 1 : 0, d = n2 == "column" ? {
        left: 0,
        top: u,
        right: 1,
        bottom: s.map.height
      } : n2 == "row" ? {
        left: u,
        top: 0,
        right: s.map.width,
        bottom: 1
      } : s, f = n2 == "column" ? a ? i.cell : i.header_cell : n2 == "row" ? l ? i.cell : i.header_cell : i.cell;
      s.map.cellsInRect(d).forEach((h2) => {
        const p2 = h2 + s.tableStart, m = o.doc.nodeAt(p2);
        m && o.setNodeMarkup(p2, f, m.attrs);
      }), r(o);
    }
    return true;
  };
}
ti("row", {
  useDeprecatedLogic: true
});
ti("column", {
  useDeprecatedLogic: true
});
var dk = ti("cell", {
  useDeprecatedLogic: true
});
function fk(n2, e) {
  if (e < 0) {
    const t = n2.nodeBefore;
    if (t) return n2.pos - t.nodeSize;
    for (let r = n2.index(-1) - 1, i = n2.before(); r >= 0; r--) {
      const s = n2.node(-1).child(r), o = s.lastChild;
      if (o)
        return i - 1 - o.nodeSize;
      i -= s.nodeSize;
    }
  } else {
    if (n2.index() < n2.parent.childCount - 1)
      return n2.pos + n2.nodeAfter.nodeSize;
    const t = n2.node(-1);
    for (let r = n2.indexAfter(-1), i = n2.after(); r < t.childCount; r++) {
      const s = t.child(r);
      if (s.childCount) return i + 1;
      i += s.nodeSize;
    }
  }
  return null;
}
function tu(n2) {
  return function(e, t) {
    if (!at(e)) return false;
    const r = fk(ro(e), n2);
    if (r == null) return false;
    if (t) {
      const i = e.doc.resolve(r);
      t(
        e.tr.setSelection(F.between(i, jw(i))).scrollIntoView()
      );
    }
    return true;
  };
}
function hk(n2, e) {
  const t = n2.selection.$anchor;
  for (let r = t.depth; r > 0; r--)
    if (t.node(r).type.spec.tableRole == "table")
      return e && e(
        n2.tr.delete(t.before(r), t.after(r)).scrollIntoView()
      ), true;
  return false;
}
function Oi(n2, e) {
  const t = n2.selection;
  if (!(t instanceof ne)) return false;
  if (e) {
    const r = n2.tr, i = Se(n2.schema).cell.createAndFill().content;
    t.forEachCell((s, o) => {
      s.content.eq(i) || r.replace(
        r.mapping.map(o + 1),
        r.mapping.map(o + s.nodeSize - 1),
        new O(i, 0, 0)
      );
    }), r.docChanged && e(r);
  }
  return true;
}
function pk(n2) {
  if (!n2.size) return null;
  let { content: e, openStart: t, openEnd: r } = n2;
  for (; e.childCount == 1 && (t > 0 && r > 0 || e.child(0).type.spec.tableRole == "table"); )
    t--, r--, e = e.child(0).content;
  const i = e.child(0), s = i.type.spec.tableRole, o = i.type.schema, l = [];
  if (s == "row")
    for (let a = 0; a < e.childCount; a++) {
      let c = e.child(a).content;
      const u = a ? 0 : Math.max(0, t - 1), d = a < e.childCount - 1 ? 0 : Math.max(0, r - 1);
      (u || d) && (c = pl(
        Se(o).row,
        new O(c, u, d)
      ).content), l.push(c);
    }
  else if (s == "cell" || s == "header_cell")
    l.push(
      t || r ? pl(
        Se(o).row,
        new O(e, t, r)
      ).content : e
    );
  else
    return null;
  return mk(o, l);
}
function mk(n2, e) {
  const t = [];
  for (let i = 0; i < e.length; i++) {
    const s = e[i];
    for (let o = s.childCount - 1; o >= 0; o--) {
      const { rowspan: l, colspan: a } = s.child(o).attrs;
      for (let c = i; c < i + l; c++)
        t[c] = (t[c] || 0) + a;
    }
  }
  let r = 0;
  for (let i = 0; i < t.length; i++) r = Math.max(r, t[i]);
  for (let i = 0; i < t.length; i++)
    if (i >= e.length && e.push(A.empty), t[i] < r) {
      const s = Se(n2).cell.createAndFill(), o = [];
      for (let l = t[i]; l < r; l++)
        o.push(s);
      e[i] = e[i].append(A.from(o));
    }
  return { height: e.length, width: r, rows: e };
}
function pl(n2, e) {
  const t = n2.createAndFill();
  return new Pl(t).replace(0, t.content.size, e).doc;
}
function gk({ width: n2, height: e, rows: t }, r, i) {
  if (n2 != r) {
    const s = [], o = [];
    for (let l = 0; l < t.length; l++) {
      const a = t[l], c = [];
      for (let u = s[l] || 0, d = 0; u < r; d++) {
        let f = a.child(d % a.childCount);
        u + f.attrs.colspan > r && (f = f.type.createChecked(
          In(
            f.attrs,
            f.attrs.colspan,
            u + f.attrs.colspan - r
          ),
          f.content
        )), c.push(f), u += f.attrs.colspan;
        for (let h2 = 1; h2 < f.attrs.rowspan; h2++)
          s[l + h2] = (s[l + h2] || 0) + f.attrs.colspan;
      }
      o.push(A.from(c));
    }
    t = o, n2 = r;
  }
  if (e != i) {
    const s = [];
    for (let o = 0, l = 0; o < i; o++, l++) {
      const a = [], c = t[l % e];
      for (let u = 0; u < c.childCount; u++) {
        let d = c.child(u);
        o + d.attrs.rowspan > i && (d = d.type.create(
          {
            ...d.attrs,
            rowspan: Math.max(1, i - d.attrs.rowspan)
          },
          d.content
        )), a.push(d);
      }
      s.push(A.from(a));
    }
    t = s, e = i;
  }
  return { width: n2, height: e, rows: t };
}
function yk(n2, e, t, r, i, s, o) {
  const l = n2.doc.type.schema, a = Se(l);
  let c, u;
  if (i > e.width)
    for (let d = 0, f = 0; d < e.height; d++) {
      const h2 = t.child(d);
      f += h2.nodeSize;
      const p2 = [];
      let m;
      h2.lastChild == null || h2.lastChild.type == a.cell ? m = c || (c = a.cell.createAndFill()) : m = u || (u = a.header_cell.createAndFill());
      for (let g = e.width; g < i; g++) p2.push(m);
      n2.insert(n2.mapping.slice(o).map(f - 1 + r), p2);
    }
  if (s > e.height) {
    const d = [];
    for (let p2 = 0, m = (e.height - 1) * e.width; p2 < Math.max(e.width, i); p2++) {
      const g = p2 >= e.width ? false : t.nodeAt(e.map[m + p2]).type == a.header_cell;
      d.push(
        g ? u || (u = a.header_cell.createAndFill()) : c || (c = a.cell.createAndFill())
      );
    }
    const f = a.row.create(null, A.from(d)), h2 = [];
    for (let p2 = e.height; p2 < s; p2++) h2.push(f);
    n2.insert(n2.mapping.slice(o).map(r + t.nodeSize - 2), h2);
  }
  return !!(c || u);
}
function nu(n2, e, t, r, i, s, o, l) {
  if (o == 0 || o == e.height) return false;
  let a = false;
  for (let c = i; c < s; c++) {
    const u = o * e.width + c, d = e.map[u];
    if (e.map[u - e.width] == d) {
      a = true;
      const f = t.nodeAt(d), { top: h2, left: p2 } = e.findCell(d);
      n2.setNodeMarkup(n2.mapping.slice(l).map(d + r), null, {
        ...f.attrs,
        rowspan: o - h2
      }), n2.insert(
        n2.mapping.slice(l).map(e.positionAt(o, p2, t)),
        f.type.createAndFill({
          ...f.attrs,
          rowspan: h2 + f.attrs.rowspan - o
        })
      ), c += f.attrs.colspan - 1;
    }
  }
  return a;
}
function ru(n2, e, t, r, i, s, o, l) {
  if (o == 0 || o == e.width) return false;
  let a = false;
  for (let c = i; c < s; c++) {
    const u = c * e.width + o, d = e.map[u];
    if (e.map[u - 1] == d) {
      a = true;
      const f = t.nodeAt(d), h2 = e.colCount(d), p2 = n2.mapping.slice(l).map(d + r);
      n2.setNodeMarkup(
        p2,
        null,
        In(
          f.attrs,
          o - h2,
          f.attrs.colspan - (o - h2)
        )
      ), n2.insert(
        p2 + f.nodeSize,
        f.type.createAndFill(
          In(f.attrs, 0, o - h2)
        )
      ), c += f.attrs.rowspan - 1;
    }
  }
  return a;
}
function iu(n2, e, t, r, i) {
  let s = t ? n2.doc.nodeAt(t - 1) : n2.doc;
  if (!s)
    throw new Error("No table found");
  let o = oe.get(s);
  const { top: l, left: a } = r, c = a + i.width, u = l + i.height, d = n2.tr;
  let f = 0;
  function h2() {
    if (s = t ? d.doc.nodeAt(t - 1) : d.doc, !s)
      throw new Error("No table found");
    o = oe.get(s), f = d.mapping.maps.length;
  }
  yk(d, o, s, t, c, u, f) && h2(), nu(d, o, s, t, a, c, l, f) && h2(), nu(d, o, s, t, a, c, u, f) && h2(), ru(d, o, s, t, l, u, a, f) && h2(), ru(d, o, s, t, l, u, c, f) && h2();
  for (let p2 = l; p2 < u; p2++) {
    const m = o.positionAt(p2, a, s), g = o.positionAt(p2, c, s);
    d.replace(
      d.mapping.slice(f).map(m + t),
      d.mapping.slice(f).map(g + t),
      new O(i.rows[p2 - l], 0, 0)
    );
  }
  h2(), d.setSelection(
    new ne(
      d.doc.resolve(t + o.positionAt(l, a, s)),
      d.doc.resolve(t + o.positionAt(u - 1, c - 1, s))
    )
  ), e(d);
}
var bk = Gd({
  ArrowLeft: Ni("horiz", -1),
  ArrowRight: Ni("horiz", 1),
  ArrowUp: Ni("vert", -1),
  ArrowDown: Ni("vert", 1),
  "Shift-ArrowLeft": Di("horiz", -1),
  "Shift-ArrowRight": Di("horiz", 1),
  "Shift-ArrowUp": Di("vert", -1),
  "Shift-ArrowDown": Di("vert", 1),
  Backspace: Oi,
  "Mod-Backspace": Oi,
  Delete: Oi,
  "Mod-Delete": Oi
});
function _i(n2, e, t) {
  return t.eq(n2.selection) ? false : (e && e(n2.tr.setSelection(t).scrollIntoView()), true);
}
function Ni(n2, e) {
  return (t, r, i) => {
    if (!i) return false;
    const s = t.selection;
    if (s instanceof ne)
      return _i(
        t,
        r,
        $.near(s.$headCell, e)
      );
    if (n2 != "horiz" && !s.empty) return false;
    const o = nh(i, n2, e);
    if (o == null) return false;
    if (n2 == "horiz")
      return _i(
        t,
        r,
        $.near(t.doc.resolve(s.head + e), e)
      );
    {
      const l = t.doc.resolve(o), a = Gf(l, n2, e);
      let c;
      return a ? c = $.near(a, 1) : e < 0 ? c = $.near(t.doc.resolve(l.before(-1)), -1) : c = $.near(t.doc.resolve(l.after(-1)), 1), _i(t, r, c);
    }
  };
}
function Di(n2, e) {
  return (t, r, i) => {
    if (!i) return false;
    const s = t.selection;
    let o;
    if (s instanceof ne)
      o = s;
    else {
      const a = nh(i, n2, e);
      if (a == null) return false;
      o = new ne(t.doc.resolve(a));
    }
    const l = Gf(o.$headCell, n2, e);
    return l ? _i(
      t,
      r,
      new ne(o.$anchorCell, l)
    ) : false;
  };
}
function vk(n2, e) {
  const t = n2.state.doc, r = pr(t.resolve(e));
  return r ? (n2.dispatch(n2.state.tr.setSelection(new ne(r))), true) : false;
}
function wk(n2, e, t) {
  if (!at(n2.state)) return false;
  let r = pk(t);
  const i = n2.state.selection;
  if (i instanceof ne) {
    r || (r = {
      width: 1,
      height: 1,
      rows: [
        A.from(
          pl(Se(n2.state.schema).cell, t)
        )
      ]
    });
    const s = i.$anchorCell.node(-1), o = i.$anchorCell.start(-1), l = oe.get(s).rectBetween(
      i.$anchorCell.pos - o,
      i.$headCell.pos - o
    );
    return r = gk(r, l.right - l.left, l.bottom - l.top), iu(n2.state, n2.dispatch, o, l, r), true;
  } else if (r) {
    const s = ro(n2.state), o = s.start(-1);
    return iu(
      n2.state,
      n2.dispatch,
      o,
      oe.get(s.node(-1)).findCell(s.pos - o),
      r
    ), true;
  } else
    return false;
}
function kk(n2, e) {
  var t;
  if (e.ctrlKey || e.metaKey) return;
  const r = su(n2, e.target);
  let i;
  if (e.shiftKey && n2.state.selection instanceof ne)
    s(n2.state.selection.$anchorCell, e), e.preventDefault();
  else if (e.shiftKey && r && (i = pr(n2.state.selection.$anchor)) != null && ((t = No(n2, e)) == null ? void 0 : t.pos) != i.pos)
    s(i, e), e.preventDefault();
  else if (!r)
    return;
  function s(a, c) {
    let u = No(n2, c);
    const d = Ut.getState(n2.state) == null;
    if (!u || !fa(a, u))
      if (d) u = a;
      else return;
    const f = new ne(a, u);
    if (d || !n2.state.selection.eq(f)) {
      const h2 = n2.state.tr.setSelection(f);
      d && h2.setMeta(Ut, a.pos), n2.dispatch(h2);
    }
  }
  function o() {
    n2.root.removeEventListener("mouseup", o), n2.root.removeEventListener("dragstart", o), n2.root.removeEventListener("mousemove", l), Ut.getState(n2.state) != null && n2.dispatch(n2.state.tr.setMeta(Ut, -1));
  }
  function l(a) {
    const c = a, u = Ut.getState(n2.state);
    let d;
    if (u != null)
      d = n2.state.doc.resolve(u);
    else if (su(n2, c.target) != r && (d = No(n2, e), !d))
      return o();
    d && s(d, c);
  }
  n2.root.addEventListener("mouseup", o), n2.root.addEventListener("dragstart", o), n2.root.addEventListener("mousemove", l);
}
function nh(n2, e, t) {
  if (!(n2.state.selection instanceof F)) return null;
  const { $head: r } = n2.state.selection;
  for (let i = r.depth - 1; i >= 0; i--) {
    const s = r.node(i);
    if ((t < 0 ? r.index(i) : r.indexAfter(i)) != (t < 0 ? 0 : s.childCount)) return null;
    if (s.type.spec.tableRole == "cell" || s.type.spec.tableRole == "header_cell") {
      const l = r.before(i), a = e == "vert" ? t > 0 ? "down" : "up" : t > 0 ? "right" : "left";
      return n2.endOfTextblock(a) ? l : null;
    }
  }
  return null;
}
function su(n2, e) {
  for (; e && e != n2.dom; e = e.parentNode)
    if (e.nodeName == "TD" || e.nodeName == "TH")
      return e;
  return null;
}
function No(n2, e) {
  const t = n2.posAtCoords({
    left: e.clientX,
    top: e.clientY
  });
  return t && t ? pr(n2.state.doc.resolve(t.pos)) : null;
}
var Ck = class {
  constructor(e, t) {
    this.node = e, this.defaultCellMinWidth = t, this.dom = document.createElement("div"), this.dom.className = "tableWrapper", this.table = this.dom.appendChild(document.createElement("table")), this.table.style.setProperty(
      "--default-cell-min-width",
      `${t}px`
    ), this.colgroup = this.table.appendChild(document.createElement("colgroup")), ml(e, this.colgroup, this.table, t), this.contentDOM = this.table.appendChild(document.createElement("tbody"));
  }
  update(e) {
    return e.type != this.node.type ? false : (this.node = e, ml(
      e,
      this.colgroup,
      this.table,
      this.defaultCellMinWidth
    ), true);
  }
  ignoreMutation(e) {
    return e.type == "attributes" && (e.target == this.table || this.colgroup.contains(e.target));
  }
};
function ml(n2, e, t, r, i, s) {
  var o;
  let l = 0, a = true, c = e.firstChild;
  const u = n2.firstChild;
  if (u) {
    for (let d = 0, f = 0; d < u.childCount; d++) {
      const { colspan: h2, colwidth: p2 } = u.child(d).attrs;
      for (let m = 0; m < h2; m++, f++) {
        const g = i == f ? s : p2 && p2[m], y = g ? g + "px" : "";
        if (l += g || r, g || (a = false), c)
          c.style.width != y && (c.style.width = y), c = c.nextSibling;
        else {
          const w = document.createElement("col");
          w.style.width = y, e.appendChild(w);
        }
      }
    }
    for (; c; ) {
      const d = c.nextSibling;
      (o = c.parentNode) == null || o.removeChild(c), c = d;
    }
    a ? (t.style.width = l + "px", t.style.minWidth = "") : (t.style.width = "", t.style.minWidth = l + "px");
  }
}
var _e = new ue(
  "tableColumnResizing"
);
function xk({
  handleWidth: n2 = 5,
  cellMinWidth: e = 25,
  defaultCellMinWidth: t = 100,
  View: r = Ck,
  lastColumnResizable: i = true
} = {}) {
  const s = new le({
    key: _e,
    state: {
      init(o, l) {
        var a, c;
        const u = (c = (a = s.spec) == null ? void 0 : a.props) == null ? void 0 : c.nodeViews, d = Se(l.schema).table.name;
        return r && u && (u[d] = (f, h2) => new r(f, t, h2)), new Sk(-1, false);
      },
      apply(o, l) {
        return l.apply(o);
      }
    },
    props: {
      attributes: (o) => {
        const l = _e.getState(o);
        return l && l.activeHandle > -1 ? { class: "resize-cursor" } : {};
      },
      handleDOMEvents: {
        mousemove: (o, l) => {
          Mk(o, l, n2, i);
        },
        mouseleave: (o) => {
          Ak(o);
        },
        mousedown: (o, l) => {
          Ek(o, l, e, t);
        }
      },
      decorations: (o) => {
        const l = _e.getState(o);
        if (l && l.activeHandle > -1)
          return Lk(o, l.activeHandle);
      },
      nodeViews: {}
    }
  });
  return s;
}
var Sk = class ji {
  constructor(e, t) {
    this.activeHandle = e, this.dragging = t;
  }
  apply(e) {
    const t = this, r = e.getMeta(_e);
    if (r && r.setHandle != null)
      return new ji(r.setHandle, false);
    if (r && r.setDragging !== void 0)
      return new ji(t.activeHandle, r.setDragging);
    if (t.activeHandle > -1 && e.docChanged) {
      let i = e.mapping.map(t.activeHandle, -1);
      return hl(e.doc.resolve(i)) || (i = -1), new ji(i, t.dragging);
    }
    return t;
  }
};
function Mk(n2, e, t, r) {
  if (!n2.editable) return;
  const i = _e.getState(n2.state);
  if (i && !i.dragging) {
    const s = Ok(e.target);
    let o = -1;
    if (s) {
      const { left: l, right: a } = s.getBoundingClientRect();
      e.clientX - l <= t ? o = ou(n2, e, "left", t) : a - e.clientX <= t && (o = ou(n2, e, "right", t));
    }
    if (o != i.activeHandle) {
      if (!r && o !== -1) {
        const l = n2.state.doc.resolve(o), a = l.node(-1), c = oe.get(a), u = l.start(-1);
        if (c.colCount(l.pos - u) + l.nodeAfter.attrs.colspan - 1 == c.width - 1)
          return;
      }
      rh(n2, o);
    }
  }
}
function Ak(n2) {
  if (!n2.editable) return;
  const e = _e.getState(n2.state);
  e && e.activeHandle > -1 && !e.dragging && rh(n2, -1);
}
function Ek(n2, e, t, r) {
  var i;
  if (!n2.editable) return false;
  const s = (i = n2.dom.ownerDocument.defaultView) != null ? i : window, o = _e.getState(n2.state);
  if (!o || o.activeHandle == -1 || o.dragging)
    return false;
  const l = n2.state.doc.nodeAt(o.activeHandle), a = Tk(n2, o.activeHandle, l.attrs);
  n2.dispatch(
    n2.state.tr.setMeta(_e, {
      setDragging: { startX: e.clientX, startWidth: a }
    })
  );
  function c(d) {
    s.removeEventListener("mouseup", c), s.removeEventListener("mousemove", u);
    const f = _e.getState(n2.state);
    f != null && f.dragging && (Nk(
      n2,
      f.activeHandle,
      lu(f.dragging, d, t)
    ), n2.dispatch(
      n2.state.tr.setMeta(_e, { setDragging: null })
    ));
  }
  function u(d) {
    if (!d.which) return c(d);
    const f = _e.getState(n2.state);
    if (f && f.dragging) {
      const h2 = lu(f.dragging, d, t);
      au(
        n2,
        f.activeHandle,
        h2,
        r
      );
    }
  }
  return au(
    n2,
    o.activeHandle,
    a,
    r
  ), s.addEventListener("mouseup", c), s.addEventListener("mousemove", u), e.preventDefault(), true;
}
function Tk(n2, e, { colspan: t, colwidth: r }) {
  const i = r && r[r.length - 1];
  if (i) return i;
  const s = n2.domAtPos(e);
  let l = s.node.childNodes[s.offset].offsetWidth, a = t;
  if (r)
    for (let c = 0; c < t; c++)
      r[c] && (l -= r[c], a--);
  return l / a;
}
function Ok(n2) {
  for (; n2 && n2.nodeName != "TD" && n2.nodeName != "TH"; )
    n2 = n2.classList && n2.classList.contains("ProseMirror") ? null : n2.parentNode;
  return n2;
}
function ou(n2, e, t, r) {
  const i = t == "right" ? -r : r, s = n2.posAtCoords({
    left: e.clientX + i,
    top: e.clientY
  });
  if (!s) return -1;
  const { pos: o } = s, l = pr(n2.state.doc.resolve(o));
  if (!l) return -1;
  if (t == "right") return l.pos;
  const a = oe.get(l.node(-1)), c = l.start(-1), u = a.map.indexOf(l.pos - c);
  return u % a.width == 0 ? -1 : c + a.map[u - 1];
}
function lu(n2, e, t) {
  const r = e.clientX - n2.startX;
  return Math.max(t, n2.startWidth + r);
}
function rh(n2, e) {
  n2.dispatch(
    n2.state.tr.setMeta(_e, { setHandle: e })
  );
}
function Nk(n2, e, t) {
  const r = n2.state.doc.resolve(e), i = r.node(-1), s = oe.get(i), o = r.start(-1), l = s.colCount(r.pos - o) + r.nodeAfter.attrs.colspan - 1, a = n2.state.tr;
  for (let c = 0; c < s.height; c++) {
    const u = c * s.width + l;
    if (c && s.map[u] == s.map[u - s.width]) continue;
    const d = s.map[u], f = i.nodeAt(d).attrs, h2 = f.colspan == 1 ? 0 : l - s.colCount(d);
    if (f.colwidth && f.colwidth[h2] == t) continue;
    const p2 = f.colwidth ? f.colwidth.slice() : Dk(f.colspan);
    p2[h2] = t, a.setNodeMarkup(o + d, null, { ...f, colwidth: p2 });
  }
  a.docChanged && n2.dispatch(a);
}
function au(n2, e, t, r) {
  const i = n2.state.doc.resolve(e), s = i.node(-1), o = i.start(-1), l = oe.get(s).colCount(i.pos - o) + i.nodeAfter.attrs.colspan - 1;
  let a = n2.domAtPos(i.start(-1)).node;
  for (; a && a.nodeName != "TABLE"; )
    a = a.parentNode;
  a && ml(
    s,
    a.firstChild,
    a,
    r,
    l,
    t
  );
}
function Dk(n2) {
  return Array(n2).fill(0);
}
function Lk(n2, e) {
  var t;
  const r = [], i = n2.doc.resolve(e), s = i.node(-1);
  if (!s)
    return ie.empty;
  const o = oe.get(s), l = i.start(-1), a = o.colCount(i.pos - l) + i.nodeAfter.attrs.colspan - 1;
  for (let c = 0; c < o.height; c++) {
    const u = a + c * o.width;
    if ((a == o.width - 1 || o.map[u] != o.map[u + 1]) && (c == 0 || o.map[u] != o.map[u - o.width])) {
      const d = o.map[u], f = l + d + s.nodeAt(d).nodeSize - 1, h2 = document.createElement("div");
      h2.className = "column-resize-handle", (t = _e.getState(n2)) != null && t.dragging && r.push(
        xe.node(
          l + d,
          l + d + s.nodeAt(d).nodeSize,
          {
            class: "column-resize-dragging"
          }
        )
      ), r.push(xe.widget(f, h2));
    }
  }
  return ie.create(n2.doc, r);
}
function Rk({
  allowTableNodeSelection: n2 = false
} = {}) {
  return new le({
    key: Ut,
    // This piece of state is used to remember when a mouse-drag
    // cell-selection is happening, so that it can continue even as
    // transactions (which might move its anchor cell) come in.
    state: {
      init() {
        return null;
      },
      apply(e, t) {
        const r = e.getMeta(Ut);
        if (r != null) return r == -1 ? null : r;
        if (t == null || !e.docChanged) return t;
        const { deleted: i, pos: s } = e.mapping.mapResult(t);
        return i ? null : s;
      }
    },
    props: {
      decorations: Kw,
      handleDOMEvents: {
        mousedown: kk
      },
      createSelectionBetween(e) {
        return Ut.getState(e.state) != null ? e.state.selection : null;
      },
      handleTripleClick: vk,
      handleKeyDown: bk,
      handlePaste: wk
    },
    appendTransaction(e, t, r) {
      return Gw(
        r,
        Zf(r, t),
        n2
      );
    }
  });
}
function gl(n2, e) {
  return e ? ["width", `${Math.max(e, n2)}px`] : ["min-width", `${n2}px`];
}
function cu(n2, e, t, r, i, s) {
  var o;
  let l = 0, a = true, c = e.firstChild;
  const u = n2.firstChild;
  if (u !== null)
    for (let d = 0, f = 0; d < u.childCount; d += 1) {
      const { colspan: h2, colwidth: p2 } = u.child(d).attrs;
      for (let m = 0; m < h2; m += 1, f += 1) {
        const g = i === f ? s : p2 && p2[m], y = g ? `${g}px` : "";
        if (l += g || r, g || (a = false), c) {
          if (c.style.width !== y) {
            const [w, C] = gl(r, g);
            c.style.setProperty(w, C);
          }
          c = c.nextSibling;
        } else {
          const w = document.createElement("col"), [C, b] = gl(r, g);
          w.style.setProperty(C, b), e.appendChild(w);
        }
      }
    }
  for (; c; ) {
    const d = c.nextSibling;
    (o = c.parentNode) === null || o === void 0 || o.removeChild(c), c = d;
  }
  a ? (t.style.width = `${l}px`, t.style.minWidth = "") : (t.style.width = "", t.style.minWidth = `${l}px`);
}
class Ik {
  constructor(e, t) {
    this.node = e, this.cellMinWidth = t, this.dom = document.createElement("div"), this.dom.className = "tableWrapper", this.table = this.dom.appendChild(document.createElement("table")), this.colgroup = this.table.appendChild(document.createElement("colgroup")), cu(e, this.colgroup, this.table, t), this.contentDOM = this.table.appendChild(document.createElement("tbody"));
  }
  update(e) {
    return e.type !== this.node.type ? false : (this.node = e, cu(e, this.colgroup, this.table, this.cellMinWidth), true);
  }
  ignoreMutation(e) {
    return e.type === "attributes" && (e.target === this.table || this.colgroup.contains(e.target));
  }
}
function Pk(n2, e, t, r) {
  let i = 0, s = true;
  const o = [], l = n2.firstChild;
  if (!l)
    return {};
  for (let d = 0, f = 0; d < l.childCount; d += 1) {
    const { colspan: h2, colwidth: p2 } = l.child(d).attrs;
    for (let m = 0; m < h2; m += 1, f += 1) {
      const g = t === f ? r : p2 && p2[m];
      i += g || e, g || (s = false);
      const [y, w] = gl(e, g);
      o.push([
        "col",
        { style: `${y}: ${w}` }
      ]);
    }
  }
  const a = s ? `${i}px` : "", c = s ? "" : `${i}px`;
  return { colgroup: ["colgroup", {}, ...o], tableWidth: a, tableMinWidth: c };
}
function uu(n2, e) {
  return n2.createAndFill();
}
function Bk(n2) {
  if (n2.cached.tableNodeTypes)
    return n2.cached.tableNodeTypes;
  const e = {};
  return Object.keys(n2.nodes).forEach((t) => {
    const r = n2.nodes[t];
    r.spec.tableRole && (e[r.spec.tableRole] = r);
  }), n2.cached.tableNodeTypes = e, e;
}
function Hk(n2, e, t, r, i) {
  const s = Bk(n2), o = [], l = [];
  for (let c = 0; c < t; c += 1) {
    const u = uu(s.cell);
    if (u && l.push(u), r) {
      const d = uu(s.header_cell);
      d && o.push(d);
    }
  }
  const a = [];
  for (let c = 0; c < e; c += 1)
    a.push(s.row.createChecked(null, r && c === 0 ? o : l));
  return s.table.createChecked(null, a);
}
function Fk(n2) {
  return n2 instanceof ne;
}
const Li = ({ editor: n2 }) => {
  const { selection: e } = n2.state;
  if (!Fk(e))
    return false;
  let t = 0;
  const r = vf(e.ranges[0].$from, (s) => s.type.name === "table");
  return r == null || r.node.descendants((s) => {
    if (s.type.name === "table")
      return false;
    ["tableCell", "tableHeader"].includes(s.type.name) && (t += 1);
  }), t === e.ranges.length ? (n2.commands.deleteTable(), true) : false;
}, zk = ce.create({
  name: "table",
  // @ts-ignore
  addOptions() {
    return {
      HTMLAttributes: {},
      resizable: false,
      handleWidth: 5,
      cellMinWidth: 25,
      // TODO: fix
      View: Ik,
      lastColumnResizable: true,
      allowTableNodeSelection: false
    };
  },
  content: "tableRow+",
  tableRole: "table",
  isolating: true,
  group: "block",
  parseHTML() {
    return [{ tag: "table" }];
  },
  renderHTML({ node: n2, HTMLAttributes: e }) {
    const { colgroup: t, tableWidth: r, tableMinWidth: i } = Pk(n2, this.options.cellMinWidth);
    return [
      "table",
      Q(this.options.HTMLAttributes, e, {
        style: r ? `width: ${r}` : `min-width: ${i}`
      }),
      t,
      ["tbody", 0]
    ];
  },
  addCommands() {
    return {
      insertTable: ({ rows: n2 = 3, cols: e = 3, withHeaderRow: t = true } = {}) => ({ tr: r, dispatch: i, editor: s }) => {
        const o = Hk(s.schema, n2, e, t);
        if (i) {
          const l = r.selection.from + 1;
          r.replaceSelectionWith(o).scrollIntoView().setSelection(F.near(r.doc.resolve(l)));
        }
        return true;
      },
      addColumnBefore: () => ({ state: n2, dispatch: e }) => Qw(n2, e),
      addColumnAfter: () => ({ state: n2, dispatch: e }) => Zw(n2, e),
      deleteColumn: () => ({ state: n2, dispatch: e }) => tk(n2, e),
      addRowBefore: () => ({ state: n2, dispatch: e }) => rk(n2, e),
      addRowAfter: () => ({ state: n2, dispatch: e }) => ik(n2, e),
      deleteRow: () => ({ state: n2, dispatch: e }) => ok(n2, e),
      deleteTable: () => ({ state: n2, dispatch: e }) => hk(n2, e),
      mergeCells: () => ({ state: n2, dispatch: e }) => Qc(n2, e),
      splitCell: () => ({ state: n2, dispatch: e }) => Zc(n2, e),
      toggleHeaderColumn: () => ({ state: n2, dispatch: e }) => ti("column")(n2, e),
      toggleHeaderRow: () => ({ state: n2, dispatch: e }) => ti("row")(n2, e),
      toggleHeaderCell: () => ({ state: n2, dispatch: e }) => dk(n2, e),
      mergeOrSplit: () => ({ state: n2, dispatch: e }) => Qc(n2, e) ? true : Zc(n2, e),
      setCellAttribute: (n2, e) => ({ state: t, dispatch: r }) => ck(n2, e)(t, r),
      goToNextCell: () => ({ state: n2, dispatch: e }) => tu(1)(n2, e),
      goToPreviousCell: () => ({ state: n2, dispatch: e }) => tu(-1)(n2, e),
      fixTables: () => ({ state: n2, dispatch: e }) => (e && Zf(n2), true),
      setCellSelection: (n2) => ({ tr: e, dispatch: t }) => {
        if (t) {
          const r = ne.create(e.doc, n2.anchorCell, n2.headCell);
          e.setSelection(r);
        }
        return true;
      }
    };
  },
  addKeyboardShortcuts() {
    return {
      Tab: () => this.editor.commands.goToNextCell() ? true : this.editor.can().addRowAfter() ? this.editor.chain().addRowAfter().goToNextCell().run() : false,
      "Shift-Tab": () => this.editor.commands.goToPreviousCell(),
      Backspace: Li,
      "Mod-Backspace": Li,
      Delete: Li,
      "Mod-Delete": Li
    };
  },
  addProseMirrorPlugins() {
    return [
      ...this.options.resizable && this.editor.isEditable ? [
        xk({
          handleWidth: this.options.handleWidth,
          cellMinWidth: this.options.cellMinWidth,
          defaultCellMinWidth: this.options.cellMinWidth,
          View: this.options.View,
          lastColumnResizable: this.options.lastColumnResizable
        })
      ] : [],
      Rk({
        allowTableNodeSelection: this.options.allowTableNodeSelection
      })
    ];
  },
  extendNodeSchema(n2) {
    const e = {
      name: n2.name,
      options: n2.options,
      storage: n2.storage
    };
    return {
      tableRole: W(L(n2, "tableRole", e))
    };
  }
}), Vk = ce.create({
  name: "tableCell",
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  content: "block+",
  addAttributes() {
    return {
      colspan: {
        default: 1
      },
      rowspan: {
        default: 1
      },
      colwidth: {
        default: null,
        parseHTML: (n2) => {
          const e = n2.getAttribute("colwidth");
          return e ? e.split(",").map((r) => parseInt(r, 10)) : null;
        }
      }
    };
  },
  tableRole: "cell",
  isolating: true,
  parseHTML() {
    return [
      { tag: "td" }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["td", Q(this.options.HTMLAttributes, n2), 0];
  }
}), $k = ce.create({
  name: "tableHeader",
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  content: "block+",
  addAttributes() {
    return {
      colspan: {
        default: 1
      },
      rowspan: {
        default: 1
      },
      colwidth: {
        default: null,
        parseHTML: (n2) => {
          const e = n2.getAttribute("colwidth");
          return e ? e.split(",").map((r) => parseInt(r, 10)) : null;
        }
      }
    };
  },
  tableRole: "header_cell",
  isolating: true,
  parseHTML() {
    return [
      { tag: "th" }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["th", Q(this.options.HTMLAttributes, n2), 0];
  }
}), _k = ce.create({
  name: "tableRow",
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  content: "(tableCell | tableHeader)*",
  tableRole: "row",
  parseHTML() {
    return [
      { tag: "tr" }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["tr", Q(this.options.HTMLAttributes, n2), 0];
  }
}), jk = "aaa1rp3bb0ott3vie4c1le2ogado5udhabi7c0ademy5centure6ountant0s9o1tor4d0s1ult4e0g1ro2tna4f0l1rica5g0akhan5ency5i0g1rbus3force5tel5kdn3l0ibaba4pay4lfinanz6state5y2sace3tom5m0azon4ericanexpress7family11x2fam3ica3sterdam8nalytics7droid5quan4z2o0l2partments8p0le4q0uarelle8r0ab1mco4chi3my2pa2t0e3s0da2ia2sociates9t0hleta5torney7u0ction5di0ble3o3spost5thor3o0s4w0s2x0a2z0ure5ba0by2idu3namex4d1k2r0celona5laycard4s5efoot5gains6seball5ketball8uhaus5yern5b0c1t1va3cg1n2d1e0ats2uty4er2rlin4st0buy5t2f1g1h0arti5i0ble3d1ke2ng0o3o1z2j1lack0friday9ockbuster8g1omberg7ue3m0s1w2n0pparibas9o0ats3ehringer8fa2m1nd2o0k0ing5sch2tik2on4t1utique6x2r0adesco6idgestone9oadway5ker3ther5ussels7s1t1uild0ers6siness6y1zz3v1w1y1z0h3ca0b1fe2l0l1vinklein9m0era3p2non3petown5ital0one8r0avan4ds2e0er0s4s2sa1e1h1ino4t0ering5holic7ba1n1re3c1d1enter4o1rn3f0a1d2g1h0anel2nel4rity4se2t2eap3intai5ristmas6ome4urch5i0priani6rcle4sco3tadel4i0c2y3k1l0aims4eaning6ick2nic1que6othing5ud3ub0med6m1n1o0ach3des3ffee4llege4ogne5m0mbank4unity6pany2re3uter5sec4ndos3struction8ulting7tact3ractors9oking4l1p2rsica5untry4pon0s4rses6pa2r0edit0card4union9icket5own3s1uise0s6u0isinella9v1w1x1y0mru3ou3z2dad1nce3ta1e1ing3sun4y2clk3ds2e0al0er2s3gree4livery5l1oitte5ta3mocrat6ntal2ist5si0gn4v2hl2iamonds6et2gital5rect0ory7scount3ver5h2y2j1k1m1np2o0cs1tor4g1mains5t1wnload7rive4tv2ubai3nlop4pont4rban5vag2r2z2earth3t2c0o2deka3u0cation8e1g1mail3erck5nergy4gineer0ing9terprises10pson4quipment8r0icsson6ni3s0q1tate5t1u0rovision8s2vents5xchange6pert3osed4ress5traspace10fage2il1rwinds6th3mily4n0s2rm0ers5shion4t3edex3edback6rrari3ero6i0delity5o2lm2nal1nce1ial7re0stone6mdale6sh0ing5t0ness6j1k1lickr3ghts4r2orist4wers5y2m1o0o0d1tball6rd1ex2sale4um3undation8x2r0ee1senius7l1ogans4ntier7tr2ujitsu5n0d2rniture7tbol5yi3ga0l0lery3o1up4me0s3p1rden4y2b0iz3d0n2e0a1nt0ing5orge5f1g0ee3h1i0ft0s3ves2ing5l0ass3e1obal2o4m0ail3bh2o1x2n1odaddy5ld0point6f2o0dyear5g0le4p1t1v2p1q1r0ainger5phics5tis4een3ipe3ocery4up4s1t1u0cci3ge2ide2tars5ru3w1y2hair2mburg5ngout5us3bo2dfc0bank7ealth0care8lp1sinki6re1mes5iphop4samitsu7tachi5v2k0t2m1n1ockey4ldings5iday5medepot5goods5s0ense7nda3rse3spital5t0ing5t0els3mail5use3w2r1sbc3t1u0ghes5yatt3undai7ibm2cbc2e1u2d1e0ee3fm2kano4l1m0amat4db2mo0bilien9n0c1dustries8finiti5o2g1k1stitute6urance4e4t0ernational10uit4vestments10o1piranga7q1r0ish4s0maili5t0anbul7t0au2v3jaguar4va3cb2e0ep2tzt3welry6io2ll2m0p2nj2o0bs1urg4t1y2p0morgan6rs3uegos4niper7kaufen5ddi3e0rryhotels6properties14fh2g1h1i0a1ds2m1ndle4tchen5wi3m1n1oeln3matsu5sher5p0mg2n2r0d1ed3uokgroup8w1y0oto4z2la0caixa5mborghini8er3nd0rover6xess5salle5t0ino3robe5w0yer5b1c1ds2ease3clerc5frak4gal2o2xus4gbt3i0dl2fe0insurance9style7ghting6ke2lly3mited4o2ncoln4k2ve1ing5k1lc1p2oan0s3cker3us3l1ndon4tte1o3ve3pl0financial11r1s1t0d0a3u0ndbeck6xe1ury5v1y2ma0drid4if1son4keup4n0agement7go3p1rket0ing3s4riott5shalls7ttel5ba2c0kinsey7d1e0d0ia3et2lbourne7me1orial6n0u2rckmsd7g1h1iami3crosoft7l1ni1t2t0subishi9k1l0b1s2m0a2n1o0bi0le4da2e1i1m1nash3ey2ster5rmon3tgage6scow4to0rcycles9v0ie4p1q1r1s0d2t0n1r2u0seum3ic4v1w1x1y1z2na0b1goya4me2vy3ba2c1e0c1t0bank4flix4work5ustar5w0s2xt0direct7us4f0l2g0o2hk2i0co2ke1on3nja3ssan1y5l1o0kia3rton4w0ruz3tv4p1r0a1w2tt2u1yc2z2obi1server7ffice5kinawa6layan0group9lo3m0ega4ne1g1l0ine5oo2pen3racle3nge4g0anic5igins6saka4tsuka4t2vh3pa0ge2nasonic7ris2s1tners4s1y3y2ccw3e0t2f0izer5g1h0armacy6d1ilips5one2to0graphy6s4ysio5ics1tet2ures6d1n0g1k2oneer5zza4k1l0ace2y0station9umbing5s3m1n0c2ohl2ker3litie5rn2st3r0america6xi3ess3ime3o0d0uctions8f1gressive8mo2perties3y5tection8u0dential9s1t1ub2w0c2y2qa1pon3uebec3st5racing4dio4e0ad1lestate6tor2y4cipes5d0stone5umbrella9hab3ise0n3t2liance6n0t0als5pair3ort3ublican8st0aurant8view0s5xroth6ich0ardli6oh3l1o1p2o0cks3deo3gers4om3s0vp3u0gby3hr2n2w0e2yukyu6sa0arland6fe0ty4kura4le1on3msclub4ung5ndvik0coromant12ofi4p1rl2s1ve2xo3b0i1s2c0b1haeffler7midt4olarships8ol3ule3warz5ience5ot3d1e0arch3t2cure1ity6ek2lect4ner3rvices6ven3w1x0y3fr2g1h0angrila6rp3ell3ia1ksha5oes2p0ping5uji3w3i0lk2na1gles5te3j1k0i0n2y0pe4l0ing4m0art3ile4n0cf3o0ccer3ial4ftbank4ware6hu2lar2utions7ng1y2y2pa0ce3ort2t3r0l2s1t0ada2ples4r1tebank4farm7c0group6ockholm6rage3e3ream4udio2y3yle4u0cks3pplies3y2ort5rf1gery5zuki5v1watch4iss4x1y0dney4stems6z2tab1ipei4lk2obao4rget4tamotors6r2too4x0i3c0i2d0k2eam2ch0nology8l1masek5nnis4va3f1g1h0d1eater2re6iaa2ckets5enda4ps2res2ol4j0maxx4x2k0maxx5l1m0all4n1o0day3kyo3ols3p1ray3shiba5tal3urs3wn2yota3s3r0ade1ing4ining5vel0ers0insurance16ust3v2t1ube2i1nes3shu4v0s2w1z2ua1bank3s2g1k1nicom3versity8o2ol2ps2s1y1z2va0cations7na1guard7c1e0gas3ntures6risign5mögensberater2ung14sicherung10t2g1i0ajes4deo3g1king4llas4n1p1rgin4sa1ion4va1o3laanderen9n1odka3lvo3te1ing3o2yage5u2wales2mart4ter4ng0gou5tch0es6eather0channel12bcam3er2site5d0ding5ibo2r3f1hoswho6ien2ki2lliamhill9n0dows4e1ners6me2olterskluwer11odside6rk0s2ld3w2s1tc1f3xbox3erox4ihuan4n2xx2yz3yachts4hoo3maxun5ndex5e1odobashi7ga2kohama6u0tube6t1un3za0ppos4ra3ero3ip2m1one3uerich6w2", Wk = "ελ1υ2бг1ел3дети4ею2католик6ом3мкд2он1сква6онлайн5рг3рус2ф2сайт3рб3укр3қаз3հայ3ישראל5קום3ابوظبي5رامكو5لاردن4بحرين5جزائر5سعودية6عليان5مغرب5مارات5یران5بارت2زار4يتك3ھارت5تونس4سودان3رية5شبكة4عراق2ب2مان4فلسطين6قطر3كاثوليك6وم3مصر2ليسيا5وريتانيا7قع4همراه5پاکستان7ڀارت4कॉम3नेट3भारत0म्3ोत5संगठन5বাংলা5ভারত2ৰত4ਭਾਰਤ4ભારત4ଭାରତ4இந்தியா6லங்கை6சிங்கப்பூர்11భారత్5ಭಾರತ4ഭാരതം5ලංකා4คอม3ไทย3ລາວ3გე2みんな3アマゾン4クラウド4グーグル4コム2ストア3セール3ファッション6ポイント4世界2中信1国1國1文网3亚马逊3企业2佛山2信息2健康2八卦2公司1益2台湾1灣2商城1店1标2嘉里0大酒店5在线2大拿2天主教3娱乐2家電2广东2微博2慈善2我爱你3手机2招聘2政务1府2新加坡2闻2时尚2書籍2机构2淡马锡3游戏2澳門2点看2移动2组织机构4网址1店1站1络2联通2谷歌2购物2通販2集团2電訊盈科4飞利浦3食品2餐厅2香格里拉3港2닷넷1컴2삼성2한국2", ur = (n2, e) => {
  for (const t in e)
    n2[t] = e[t];
  return n2;
}, yl = "numeric", bl = "ascii", vl = "alpha", Pr = "asciinumeric", Er = "alphanumeric", wl = "domain", ih = "emoji", Uk = "scheme", Kk = "slashscheme", Do = "whitespace";
function qk(n2, e) {
  return n2 in e || (e[n2] = []), e[n2];
}
function wn(n2, e, t) {
  e[yl] && (e[Pr] = true, e[Er] = true), e[bl] && (e[Pr] = true, e[vl] = true), e[Pr] && (e[Er] = true), e[vl] && (e[Er] = true), e[Er] && (e[wl] = true), e[ih] && (e[wl] = true);
  for (const r in e) {
    const i = qk(r, t);
    i.indexOf(n2) < 0 && i.push(n2);
  }
}
function Jk(n2, e) {
  const t = {};
  for (const r in e)
    e[r].indexOf(n2) >= 0 && (t[r] = true);
  return t;
}
function Le(n2 = null) {
  this.j = {}, this.jr = [], this.jd = null, this.t = n2;
}
Le.groups = {};
Le.prototype = {
  accepts() {
    return !!this.t;
  },
  /**
   * Follow an existing transition from the given input to the next state.
   * Does not mutate.
   * @param {string} input character or token type to transition on
   * @returns {?State<T>} the next state, if any
   */
  go(n2) {
    const e = this, t = e.j[n2];
    if (t)
      return t;
    for (let r = 0; r < e.jr.length; r++) {
      const i = e.jr[r][0], s = e.jr[r][1];
      if (s && i.test(n2))
        return s;
    }
    return e.jd;
  },
  /**
   * Whether the state has a transition for the given input. Set the second
   * argument to true to only look for an exact match (and not a default or
   * regular-expression-based transition)
   * @param {string} input
   * @param {boolean} exactOnly
   */
  has(n2, e = false) {
    return e ? n2 in this.j : !!this.go(n2);
  },
  /**
   * Short for "transition all"; create a transition from the array of items
   * in the given list to the same final resulting state.
   * @param {string | string[]} inputs Group of inputs to transition on
   * @param {Transition<T> | State<T>} [next] Transition options
   * @param {Flags} [flags] Collections flags to add token to
   * @param {Collections<T>} [groups] Master list of token groups
   */
  ta(n2, e, t, r) {
    for (let i = 0; i < n2.length; i++)
      this.tt(n2[i], e, t, r);
  },
  /**
   * Short for "take regexp transition"; defines a transition for this state
   * when it encounters a token which matches the given regular expression
   * @param {RegExp} regexp Regular expression transition (populate first)
   * @param {T | State<T>} [next] Transition options
   * @param {Flags} [flags] Collections flags to add token to
   * @param {Collections<T>} [groups] Master list of token groups
   * @returns {State<T>} taken after the given input
   */
  tr(n2, e, t, r) {
    r = r || Le.groups;
    let i;
    return e && e.j ? i = e : (i = new Le(e), t && r && wn(e, t, r)), this.jr.push([n2, i]), i;
  },
  /**
   * Short for "take transitions", will take as many sequential transitions as
   * the length of the given input and returns the
   * resulting final state.
   * @param {string | string[]} input
   * @param {T | State<T>} [next] Transition options
   * @param {Flags} [flags] Collections flags to add token to
   * @param {Collections<T>} [groups] Master list of token groups
   * @returns {State<T>} taken after the given input
   */
  ts(n2, e, t, r) {
    let i = this;
    const s = n2.length;
    if (!s)
      return i;
    for (let o = 0; o < s - 1; o++)
      i = i.tt(n2[o]);
    return i.tt(n2[s - 1], e, t, r);
  },
  /**
   * Short for "take transition", this is a method for building/working with
   * state machines.
   *
   * If a state already exists for the given input, returns it.
   *
   * If a token is specified, that state will emit that token when reached by
   * the linkify engine.
   *
   * If no state exists, it will be initialized with some default transitions
   * that resemble existing default transitions.
   *
   * If a state is given for the second argument, that state will be
   * transitioned to on the given input regardless of what that input
   * previously did.
   *
   * Specify a token group flags to define groups that this token belongs to.
   * The token will be added to corresponding entires in the given groups
   * object.
   *
   * @param {string} input character, token type to transition on
   * @param {T | State<T>} [next] Transition options
   * @param {Flags} [flags] Collections flags to add token to
   * @param {Collections<T>} [groups] Master list of groups
   * @returns {State<T>} taken after the given input
   */
  tt(n2, e, t, r) {
    r = r || Le.groups;
    const i = this;
    if (e && e.j)
      return i.j[n2] = e, e;
    const s = e;
    let o, l = i.go(n2);
    if (l ? (o = new Le(), ur(o.j, l.j), o.jr.push.apply(o.jr, l.jr), o.jd = l.jd, o.t = l.t) : o = new Le(), s) {
      if (r)
        if (o.t && typeof o.t == "string") {
          const a = ur(Jk(o.t, r), t);
          wn(s, a, r);
        } else t && wn(s, t, r);
      o.t = s;
    }
    return i.j[n2] = o, o;
  }
};
const U = (n2, e, t, r, i) => n2.ta(e, t, r, i), se = (n2, e, t, r, i) => n2.tr(e, t, r, i), du = (n2, e, t, r, i) => n2.ts(e, t, r, i), E = (n2, e, t, r, i) => n2.tt(e, t, r, i), Tt = "WORD", kl = "UWORD", sh = "ASCIINUMERICAL", oh = "ALPHANUMERICAL", ni = "LOCALHOST", Cl = "TLD", xl = "UTLD", Wi = "SCHEME", Yn = "SLASH_SCHEME", ha = "NUM", Sl = "WS", pa = "NL", Br = "OPENBRACE", Hr = "CLOSEBRACE", as = "OPENBRACKET", cs = "CLOSEBRACKET", us = "OPENPAREN", ds = "CLOSEPAREN", fs = "OPENANGLEBRACKET", hs = "CLOSEANGLEBRACKET", ps = "FULLWIDTHLEFTPAREN", ms = "FULLWIDTHRIGHTPAREN", gs = "LEFTCORNERBRACKET", ys = "RIGHTCORNERBRACKET", bs = "LEFTWHITECORNERBRACKET", vs = "RIGHTWHITECORNERBRACKET", ws = "FULLWIDTHLESSTHAN", ks = "FULLWIDTHGREATERTHAN", Cs = "AMPERSAND", xs = "APOSTROPHE", Ss = "ASTERISK", _t = "AT", Ms = "BACKSLASH", As = "BACKTICK", Es = "CARET", Kt = "COLON", ma = "COMMA", Ts = "DOLLAR", ct = "DOT", Os = "EQUALS", ga = "EXCLAMATION", Je = "HYPHEN", Fr = "PERCENT", Ns = "PIPE", Ds = "PLUS", Ls = "POUND", zr = "QUERY", ya = "QUOTE", lh = "FULLWIDTHMIDDLEDOT", ba = "SEMI", ut = "SLASH", Vr = "TILDE", Rs = "UNDERSCORE", ah = "EMOJI", Is = "SYM";
var ch = /* @__PURE__ */ Object.freeze({
  __proto__: null,
  ALPHANUMERICAL: oh,
  AMPERSAND: Cs,
  APOSTROPHE: xs,
  ASCIINUMERICAL: sh,
  ASTERISK: Ss,
  AT: _t,
  BACKSLASH: Ms,
  BACKTICK: As,
  CARET: Es,
  CLOSEANGLEBRACKET: hs,
  CLOSEBRACE: Hr,
  CLOSEBRACKET: cs,
  CLOSEPAREN: ds,
  COLON: Kt,
  COMMA: ma,
  DOLLAR: Ts,
  DOT: ct,
  EMOJI: ah,
  EQUALS: Os,
  EXCLAMATION: ga,
  FULLWIDTHGREATERTHAN: ks,
  FULLWIDTHLEFTPAREN: ps,
  FULLWIDTHLESSTHAN: ws,
  FULLWIDTHMIDDLEDOT: lh,
  FULLWIDTHRIGHTPAREN: ms,
  HYPHEN: Je,
  LEFTCORNERBRACKET: gs,
  LEFTWHITECORNERBRACKET: bs,
  LOCALHOST: ni,
  NL: pa,
  NUM: ha,
  OPENANGLEBRACKET: fs,
  OPENBRACE: Br,
  OPENBRACKET: as,
  OPENPAREN: us,
  PERCENT: Fr,
  PIPE: Ns,
  PLUS: Ds,
  POUND: Ls,
  QUERY: zr,
  QUOTE: ya,
  RIGHTCORNERBRACKET: ys,
  RIGHTWHITECORNERBRACKET: vs,
  SCHEME: Wi,
  SEMI: ba,
  SLASH: ut,
  SLASH_SCHEME: Yn,
  SYM: Is,
  TILDE: Vr,
  TLD: Cl,
  UNDERSCORE: Rs,
  UTLD: xl,
  UWORD: kl,
  WORD: Tt,
  WS: Sl
});
const Mt = /[a-z]/, xr = new RegExp("\\p{L}", "u"), Lo = new RegExp("\\p{Emoji}", "u"), At = /\d/, Ro = /\s/, fu = "\r", Io = `
`, Gk = "️", Yk = "‍", Po = "￼";
let Ri = null, Ii = null;
function Xk(n2 = []) {
  const e = {};
  Le.groups = e;
  const t = new Le();
  Ri == null && (Ri = hu(jk)), Ii == null && (Ii = hu(Wk)), E(t, "'", xs), E(t, "{", Br), E(t, "}", Hr), E(t, "[", as), E(t, "]", cs), E(t, "(", us), E(t, ")", ds), E(t, "<", fs), E(t, ">", hs), E(t, "（", ps), E(t, "）", ms), E(t, "「", gs), E(t, "」", ys), E(t, "『", bs), E(t, "』", vs), E(t, "＜", ws), E(t, "＞", ks), E(t, "&", Cs), E(t, "*", Ss), E(t, "@", _t), E(t, "`", As), E(t, "^", Es), E(t, ":", Kt), E(t, ",", ma), E(t, "$", Ts), E(t, ".", ct), E(t, "=", Os), E(t, "!", ga), E(t, "-", Je), E(t, "%", Fr), E(t, "|", Ns), E(t, "+", Ds), E(t, "#", Ls), E(t, "?", zr), E(t, '"', ya), E(t, "/", ut), E(t, ";", ba), E(t, "~", Vr), E(t, "_", Rs), E(t, "\\", Ms), E(t, "・", lh);
  const r = se(t, At, ha, {
    [yl]: true
  });
  se(r, At, r);
  const i = se(r, Mt, sh, {
    [Pr]: true
  }), s = se(r, xr, oh, {
    [Er]: true
  }), o = se(t, Mt, Tt, {
    [bl]: true
  });
  se(o, At, i), se(o, Mt, o), se(i, At, i), se(i, Mt, i);
  const l = se(t, xr, kl, {
    [vl]: true
  });
  se(l, Mt), se(l, At, s), se(l, xr, l), se(s, At, s), se(s, Mt), se(s, xr, s);
  const a = E(t, Io, pa, {
    [Do]: true
  }), c = E(t, fu, Sl, {
    [Do]: true
  }), u = se(t, Ro, Sl, {
    [Do]: true
  });
  E(t, Po, u), E(c, Io, a), E(c, Po, u), se(c, Ro, u), E(u, fu), E(u, Io), se(u, Ro, u), E(u, Po, u);
  const d = se(t, Lo, ah, {
    [ih]: true
  });
  E(d, "#"), se(d, Lo, d), E(d, Gk, d);
  const f = E(d, Yk);
  E(f, "#"), se(f, Lo, d);
  const h2 = [[Mt, o], [At, i]], p2 = [[Mt, null], [xr, l], [At, s]];
  for (let m = 0; m < Ri.length; m++)
    zt(t, Ri[m], Cl, Tt, h2);
  for (let m = 0; m < Ii.length; m++)
    zt(t, Ii[m], xl, kl, p2);
  wn(Cl, {
    tld: true,
    ascii: true
  }, e), wn(xl, {
    utld: true,
    alpha: true
  }, e), zt(t, "file", Wi, Tt, h2), zt(t, "mailto", Wi, Tt, h2), zt(t, "http", Yn, Tt, h2), zt(t, "https", Yn, Tt, h2), zt(t, "ftp", Yn, Tt, h2), zt(t, "ftps", Yn, Tt, h2), wn(Wi, {
    scheme: true,
    ascii: true
  }, e), wn(Yn, {
    slashscheme: true,
    ascii: true
  }, e), n2 = n2.sort((m, g) => m[0] > g[0] ? 1 : -1);
  for (let m = 0; m < n2.length; m++) {
    const g = n2[m][0], w = n2[m][1] ? {
      [Uk]: true
    } : {
      [Kk]: true
    };
    g.indexOf("-") >= 0 ? w[wl] = true : Mt.test(g) ? At.test(g) ? w[Pr] = true : w[bl] = true : w[yl] = true, du(t, g, g, w);
  }
  return du(t, "localhost", ni, {
    ascii: true
  }), t.jd = new Le(Is), {
    start: t,
    tokens: ur({
      groups: e
    }, ch)
  };
}
function uh(n2, e) {
  const t = Qk(e.replace(/[A-Z]/g, (l) => l.toLowerCase())), r = t.length, i = [];
  let s = 0, o = 0;
  for (; o < r; ) {
    let l = n2, a = null, c = 0, u = null, d = -1, f = -1;
    for (; o < r && (a = l.go(t[o])); )
      l = a, l.accepts() ? (d = 0, f = 0, u = l) : d >= 0 && (d += t[o].length, f++), c += t[o].length, s += t[o].length, o++;
    s -= d, o -= f, c -= d, i.push({
      t: u.t,
      // token type/name
      v: e.slice(s - c, s),
      // string value
      s: s - c,
      // start index
      e: s
      // end index (excluding)
    });
  }
  return i;
}
function Qk(n2) {
  const e = [], t = n2.length;
  let r = 0;
  for (; r < t; ) {
    let i = n2.charCodeAt(r), s, o = i < 55296 || i > 56319 || r + 1 === t || (s = n2.charCodeAt(r + 1)) < 56320 || s > 57343 ? n2[r] : n2.slice(r, r + 2);
    e.push(o), r += o.length;
  }
  return e;
}
function zt(n2, e, t, r, i) {
  let s;
  const o = e.length;
  for (let l = 0; l < o - 1; l++) {
    const a = e[l];
    n2.j[a] ? s = n2.j[a] : (s = new Le(r), s.jr = i.slice(), n2.j[a] = s), n2 = s;
  }
  return s = new Le(t), s.jr = i.slice(), n2.j[e[o - 1]] = s, s;
}
function hu(n2) {
  const e = [], t = [];
  let r = 0, i = "0123456789";
  for (; r < n2.length; ) {
    let s = 0;
    for (; i.indexOf(n2[r + s]) >= 0; )
      s++;
    if (s > 0) {
      e.push(t.join(""));
      for (let o = parseInt(n2.substring(r, r + s), 10); o > 0; o--)
        t.pop();
      r += s;
    } else
      t.push(n2[r]), r++;
  }
  return e;
}
const ri = {
  defaultProtocol: "http",
  events: null,
  format: pu,
  formatHref: pu,
  nl2br: false,
  tagName: "a",
  target: null,
  rel: null,
  validate: true,
  truncate: 1 / 0,
  className: null,
  attributes: null,
  ignoreTags: [],
  render: null
};
function va(n2, e = null) {
  let t = ur({}, ri);
  n2 && (t = ur(t, n2 instanceof va ? n2.o : n2));
  const r = t.ignoreTags, i = [];
  for (let s = 0; s < r.length; s++)
    i.push(r[s].toUpperCase());
  this.o = t, e && (this.defaultRender = e), this.ignoreTags = i;
}
va.prototype = {
  o: ri,
  /**
   * @type string[]
   */
  ignoreTags: [],
  /**
   * @param {IntermediateRepresentation} ir
   * @returns {any}
   */
  defaultRender(n2) {
    return n2;
  },
  /**
   * Returns true or false based on whether a token should be displayed as a
   * link based on the user options.
   * @param {MultiToken} token
   * @returns {boolean}
   */
  check(n2) {
    return this.get("validate", n2.toString(), n2);
  },
  // Private methods
  /**
   * Resolve an option's value based on the value of the option and the given
   * params. If operator and token are specified and the target option is
   * callable, automatically calls the function with the given argument.
   * @template {keyof Opts} K
   * @param {K} key Name of option to use
   * @param {string} [operator] will be passed to the target option if it's a
   * function. If not specified, RAW function value gets returned
   * @param {MultiToken} [token] The token from linkify.tokenize
   * @returns {Opts[K] | any}
   */
  get(n2, e, t) {
    const r = e != null;
    let i = this.o[n2];
    return i && (typeof i == "object" ? (i = t.t in i ? i[t.t] : ri[n2], typeof i == "function" && r && (i = i(e, t))) : typeof i == "function" && r && (i = i(e, t.t, t)), i);
  },
  /**
   * @template {keyof Opts} L
   * @param {L} key Name of options object to use
   * @param {string} [operator]
   * @param {MultiToken} [token]
   * @returns {Opts[L] | any}
   */
  getObj(n2, e, t) {
    let r = this.o[n2];
    return typeof r == "function" && e != null && (r = r(e, t.t, t)), r;
  },
  /**
   * Convert the given token to a rendered element that may be added to the
   * calling-interface's DOM
   * @param {MultiToken} token Token to render to an HTML element
   * @returns {any} Render result; e.g., HTML string, DOM element, React
   *   Component, etc.
   */
  render(n2) {
    const e = n2.render(this);
    return (this.get("render", null, n2) || this.defaultRender)(e, n2.t, n2);
  }
};
function pu(n2) {
  return n2;
}
function dh(n2, e) {
  this.t = "token", this.v = n2, this.tk = e;
}
dh.prototype = {
  isLink: false,
  /**
   * Return the string this token represents.
   * @return {string}
   */
  toString() {
    return this.v;
  },
  /**
   * What should the value for this token be in the `href` HTML attribute?
   * Returns the `.toString` value by default.
   * @param {string} [scheme]
   * @return {string}
   */
  toHref(n2) {
    return this.toString();
  },
  /**
   * @param {Options} options Formatting options
   * @returns {string}
   */
  toFormattedString(n2) {
    const e = this.toString(), t = n2.get("truncate", e, this), r = n2.get("format", e, this);
    return t && r.length > t ? r.substring(0, t) + "…" : r;
  },
  /**
   *
   * @param {Options} options
   * @returns {string}
   */
  toFormattedHref(n2) {
    return n2.get("formatHref", this.toHref(n2.get("defaultProtocol")), this);
  },
  /**
   * The start index of this token in the original input string
   * @returns {number}
   */
  startIndex() {
    return this.tk[0].s;
  },
  /**
   * The end index of this token in the original input string (up to this
   * index but not including it)
   * @returns {number}
   */
  endIndex() {
    return this.tk[this.tk.length - 1].e;
  },
  /**
  	Returns an object  of relevant values for this token, which includes keys
  	* type - Kind of token ('url', 'email', etc.)
  	* value - Original text
  	* href - The value that should be added to the anchor tag's href
  		attribute
  		@method toObject
  	@param {string} [protocol] `'http'` by default
  */
  toObject(n2 = ri.defaultProtocol) {
    return {
      type: this.t,
      value: this.toString(),
      isLink: this.isLink,
      href: this.toHref(n2),
      start: this.startIndex(),
      end: this.endIndex()
    };
  },
  /**
   *
   * @param {Options} options Formatting option
   */
  toFormattedObject(n2) {
    return {
      type: this.t,
      value: this.toFormattedString(n2),
      isLink: this.isLink,
      href: this.toFormattedHref(n2),
      start: this.startIndex(),
      end: this.endIndex()
    };
  },
  /**
   * Whether this token should be rendered as a link according to the given options
   * @param {Options} options
   * @returns {boolean}
   */
  validate(n2) {
    return n2.get("validate", this.toString(), this);
  },
  /**
   * Return an object that represents how this link should be rendered.
   * @param {Options} options Formattinng options
   */
  render(n2) {
    const e = this, t = this.toHref(n2.get("defaultProtocol")), r = n2.get("formatHref", t, this), i = n2.get("tagName", t, e), s = this.toFormattedString(n2), o = {}, l = n2.get("className", t, e), a = n2.get("target", t, e), c = n2.get("rel", t, e), u = n2.getObj("attributes", t, e), d = n2.getObj("events", t, e);
    return o.href = r, l && (o.class = l), a && (o.target = a), c && (o.rel = c), u && ur(o, u), {
      tagName: i,
      attributes: o,
      content: s,
      eventListeners: d
    };
  }
};
function io(n2, e) {
  class t extends dh {
    constructor(i, s) {
      super(i, s), this.t = n2;
    }
  }
  for (const r in e)
    t.prototype[r] = e[r];
  return t.t = n2, t;
}
const mu = io("email", {
  isLink: true,
  toHref() {
    return "mailto:" + this.toString();
  }
}), gu = io("text"), Zk = io("nl"), Pi = io("url", {
  isLink: true,
  /**
  	Lowercases relevant parts of the domain and adds the protocol if
  	required. Note that this will not escape unsafe HTML characters in the
  	URL.
  		@param {string} [scheme] default scheme (e.g., 'https')
  	@return {string} the full href
  */
  toHref(n2 = ri.defaultProtocol) {
    return this.hasProtocol() ? this.v : `${n2}://${this.v}`;
  },
  /**
   * Check whether this URL token has a protocol
   * @return {boolean}
   */
  hasProtocol() {
    const n2 = this.tk;
    return n2.length >= 2 && n2[0].t !== ni && n2[1].t === Kt;
  }
}), qe = (n2) => new Le(n2);
function eC({
  groups: n2
}) {
  const e = n2.domain.concat([Cs, Ss, _t, Ms, As, Es, Ts, Os, Je, ha, Fr, Ns, Ds, Ls, ut, Is, Vr, Rs]), t = [xs, Kt, ma, ct, ga, Fr, zr, ya, ba, fs, hs, Br, Hr, cs, as, us, ds, ps, ms, gs, ys, bs, vs, ws, ks], r = [Cs, xs, Ss, Ms, As, Es, Ts, Os, Je, Br, Hr, Fr, Ns, Ds, Ls, zr, ut, Is, Vr, Rs], i = qe(), s = E(i, Vr);
  U(s, r, s), U(s, n2.domain, s);
  const o = qe(), l = qe(), a = qe();
  U(i, n2.domain, o), U(i, n2.scheme, l), U(i, n2.slashscheme, a), U(o, r, s), U(o, n2.domain, o);
  const c = E(o, _t);
  E(s, _t, c), E(l, _t, c), E(a, _t, c);
  const u = E(s, ct);
  U(u, r, s), U(u, n2.domain, s);
  const d = qe();
  U(c, n2.domain, d), U(d, n2.domain, d);
  const f = E(d, ct);
  U(f, n2.domain, d);
  const h2 = qe(mu);
  U(f, n2.tld, h2), U(f, n2.utld, h2), E(c, ni, h2);
  const p2 = E(d, Je);
  E(p2, Je, p2), U(p2, n2.domain, d), U(h2, n2.domain, d), E(h2, ct, f), E(h2, Je, p2);
  const m = E(h2, Kt);
  U(m, n2.numeric, mu);
  const g = E(o, Je), y = E(o, ct);
  E(g, Je, g), U(g, n2.domain, o), U(y, r, s), U(y, n2.domain, o);
  const w = qe(Pi);
  U(y, n2.tld, w), U(y, n2.utld, w), U(w, n2.domain, o), U(w, r, s), E(w, ct, y), E(w, Je, g), E(w, _t, c);
  const C = E(w, Kt), b = qe(Pi);
  U(C, n2.numeric, b);
  const S = qe(Pi), k = qe();
  U(S, e, S), U(S, t, k), U(k, e, S), U(k, t, k), E(w, ut, S), E(b, ut, S);
  const T = E(l, Kt), M = E(a, Kt), I = E(M, ut), N = E(I, ut);
  U(l, n2.domain, o), E(l, ct, y), E(l, Je, g), U(a, n2.domain, o), E(a, ct, y), E(a, Je, g), U(T, n2.domain, S), E(T, ut, S), E(T, zr, S), U(N, n2.domain, S), U(N, e, S), E(N, ut, S);
  const j = [
    [Br, Hr],
    // {}
    [as, cs],
    // []
    [us, ds],
    // ()
    [fs, hs],
    // <>
    [ps, ms],
    // （）
    [gs, ys],
    // 「」
    [bs, vs],
    // 『』
    [ws, ks]
    // ＜＞
  ];
  for (let K = 0; K < j.length; K++) {
    const [Y, J] = j[K], Z = E(S, Y);
    E(k, Y, Z), E(Z, J, S);
    const G = qe(Pi);
    U(Z, e, G);
    const ee = qe();
    U(Z, t), U(G, e, G), U(G, t, ee), U(ee, e, G), U(ee, t, ee), E(G, J, S), E(ee, J, S);
  }
  return E(i, ni, w), E(i, pa, Zk), {
    start: i,
    tokens: ch
  };
}
function tC(n2, e, t) {
  let r = t.length, i = 0, s = [], o = [];
  for (; i < r; ) {
    let l = n2, a = null, c = null, u = 0, d = null, f = -1;
    for (; i < r && !(a = l.go(t[i].t)); )
      o.push(t[i++]);
    for (; i < r && (c = a || l.go(t[i].t)); )
      a = null, l = c, l.accepts() ? (f = 0, d = l) : f >= 0 && f++, i++, u++;
    if (f < 0)
      i -= u, i < r && (o.push(t[i]), i++);
    else {
      o.length > 0 && (s.push(Bo(gu, e, o)), o = []), i -= f, u -= f;
      const h2 = d.t, p2 = t.slice(i - u, i);
      s.push(Bo(h2, e, p2));
    }
  }
  return o.length > 0 && s.push(Bo(gu, e, o)), s;
}
function Bo(n2, e, t) {
  const r = t[0].s, i = t[t.length - 1].e, s = e.slice(r, i);
  return new n2(s, t);
}
const nC = typeof console < "u" && console && console.warn || (() => {
}), rC = "until manual call of linkify.init(). Register all schemes and plugins before invoking linkify the first time.", re = {
  scanner: null,
  parser: null,
  tokenQueue: [],
  pluginQueue: [],
  customSchemes: [],
  initialized: false
};
function iC() {
  return Le.groups = {}, re.scanner = null, re.parser = null, re.tokenQueue = [], re.pluginQueue = [], re.customSchemes = [], re.initialized = false, re;
}
function yu(n2, e = false) {
  if (re.initialized && nC(`linkifyjs: already initialized - will not register custom scheme "${n2}" ${rC}`), !/^[0-9a-z]+(-[0-9a-z]+)*$/.test(n2))
    throw new Error(`linkifyjs: incorrect scheme format.
1. Must only contain digits, lowercase ASCII letters or "-"
2. Cannot start or end with "-"
3. "-" cannot repeat`);
  re.customSchemes.push([n2, e]);
}
function sC() {
  re.scanner = Xk(re.customSchemes);
  for (let n2 = 0; n2 < re.tokenQueue.length; n2++)
    re.tokenQueue[n2][1]({
      scanner: re.scanner
    });
  re.parser = eC(re.scanner.tokens);
  for (let n2 = 0; n2 < re.pluginQueue.length; n2++)
    re.pluginQueue[n2][1]({
      scanner: re.scanner,
      parser: re.parser
    });
  return re.initialized = true, re;
}
function wa(n2) {
  return re.initialized || sC(), tC(re.parser.start, n2, uh(re.scanner.start, n2));
}
wa.scan = uh;
function fh(n2, e = null, t = null) {
  if (e && typeof e == "object") {
    if (t)
      throw Error(`linkifyjs: Invalid link type ${e}; must be a string`);
    t = e, e = null;
  }
  const r = new va(t), i = wa(n2), s = [];
  for (let o = 0; o < i.length; o++) {
    const l = i[o];
    l.isLink && (!e || l.t === e) && r.check(l) && s.push(l.toFormattedObject(r));
  }
  return s;
}
function oC(n2) {
  return n2.length === 1 ? n2[0].isLink : n2.length === 3 && n2[1].isLink ? ["()", "[]"].includes(n2[0].value + n2[2].value) : false;
}
function lC(n2) {
  return new le({
    key: new ue("autolink"),
    appendTransaction: (e, t, r) => {
      const i = e.some((c) => c.docChanged) && !t.doc.eq(r.doc), s = e.some((c) => c.getMeta("preventAutolink"));
      if (!i || s)
        return;
      const { tr: o } = r, l = qy(t.doc, [...e]);
      if (Zy(l).forEach(({ newRange: c }) => {
        const u = Gy(r.doc, c, (h2) => h2.isTextblock);
        let d, f;
        if (u.length > 1 ? (d = u[0], f = r.doc.textBetween(d.pos, d.pos + d.node.nodeSize, void 0, " ")) : u.length && r.doc.textBetween(c.from, c.to, " ", " ").endsWith(" ") && (d = u[0], f = r.doc.textBetween(d.pos, c.to, void 0, " ")), d && f) {
          const h2 = f.split(" ").filter((y) => y !== "");
          if (h2.length <= 0)
            return false;
          const p2 = h2[h2.length - 1], m = d.pos + f.lastIndexOf(p2);
          if (!p2)
            return false;
          const g = wa(p2).map((y) => y.toObject(n2.defaultProtocol));
          if (!oC(g))
            return false;
          g.filter((y) => y.isLink).map((y) => ({
            ...y,
            from: m + y.start + 1,
            to: m + y.end + 1
          })).filter((y) => r.schema.marks.code ? !r.doc.rangeHasMark(y.from, y.to, r.schema.marks.code) : true).filter((y) => n2.validate(y.value)).filter((y) => n2.shouldAutoLink(y.value)).forEach((y) => {
            ra(y.from, y.to, r.doc).some((w) => w.mark.type === n2.type) || o.addMark(y.from, y.to, n2.type.create({
              href: y.href
            }));
          });
        }
      }), !!o.steps.length)
        return o;
    }
  });
}
function aC(n2) {
  return new le({
    key: new ue("handleClickLink"),
    props: {
      handleClick: (e, t, r) => {
        var i, s;
        if (r.button !== 0 || !e.editable)
          return false;
        let o = r.target;
        const l = [];
        for (; o.nodeName !== "DIV"; )
          l.push(o), o = o.parentNode;
        if (!l.find((f) => f.nodeName === "A"))
          return false;
        const a = kf(e.state, n2.type.name), c = r.target, u = (i = c == null ? void 0 : c.href) !== null && i !== void 0 ? i : a.href, d = (s = c == null ? void 0 : c.target) !== null && s !== void 0 ? s : a.target;
        return c && u ? (window.open(u, d), true) : false;
      }
    }
  });
}
function cC(n2) {
  return new le({
    key: new ue("handlePasteLink"),
    props: {
      handlePaste: (e, t, r) => {
        const { state: i } = e, { selection: s } = i, { empty: o } = s;
        if (o)
          return false;
        let l = "";
        r.content.forEach((c) => {
          l += c.textContent;
        });
        const a = fh(l, { defaultProtocol: n2.defaultProtocol }).find((c) => c.isLink && c.value === l);
        return !l || !a ? false : n2.editor.commands.setMark(n2.type, {
          href: a.href
        });
      }
    }
  });
}
const uC = /[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205F\u3000]/g;
function fn(n2, e) {
  const t = [
    "http",
    "https",
    "ftp",
    "ftps",
    "mailto",
    "tel",
    "callto",
    "sms",
    "cid",
    "xmpp"
  ];
  return e && e.forEach((r) => {
    const i = typeof r == "string" ? r : r.scheme;
    i && t.push(i);
  }), !n2 || n2.replace(uC, "").match(new RegExp(
    // eslint-disable-next-line no-useless-escape
    `^(?:(?:${t.join("|")}):|[^a-z]|[a-z0-9+.-]+(?:[^a-z+.-:]|$))`,
    "i"
  ));
}
const dC = lt.create({
  name: "link",
  priority: 1e3,
  keepOnSplit: false,
  exitable: true,
  onCreate() {
    this.options.validate && !this.options.shouldAutoLink && (this.options.shouldAutoLink = this.options.validate, console.warn("The `validate` option is deprecated. Rename to the `shouldAutoLink` option instead.")), this.options.protocols.forEach((n2) => {
      if (typeof n2 == "string") {
        yu(n2);
        return;
      }
      yu(n2.scheme, n2.optionalSlashes);
    });
  },
  onDestroy() {
    iC();
  },
  inclusive() {
    return this.options.autolink;
  },
  addOptions() {
    return {
      openOnClick: true,
      linkOnPaste: true,
      autolink: true,
      protocols: [],
      defaultProtocol: "http",
      HTMLAttributes: {
        target: "_blank",
        rel: "noopener noreferrer nofollow",
        class: null
      },
      isAllowedUri: (n2, e) => !!fn(n2, e.protocols),
      validate: (n2) => !!n2,
      shouldAutoLink: (n2) => !!n2
    };
  },
  addAttributes() {
    return {
      href: {
        default: null,
        parseHTML(n2) {
          return n2.getAttribute("href");
        }
      },
      target: {
        default: this.options.HTMLAttributes.target
      },
      rel: {
        default: this.options.HTMLAttributes.rel
      },
      class: {
        default: this.options.HTMLAttributes.class
      }
    };
  },
  parseHTML() {
    return [
      {
        tag: "a[href]",
        getAttrs: (n2) => {
          const e = n2.getAttribute("href");
          return !e || !this.options.isAllowedUri(e, {
            defaultValidate: (t) => !!fn(t, this.options.protocols),
            protocols: this.options.protocols,
            defaultProtocol: this.options.defaultProtocol
          }) ? false : null;
        }
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return this.options.isAllowedUri(n2.href, {
      defaultValidate: (e) => !!fn(e, this.options.protocols),
      protocols: this.options.protocols,
      defaultProtocol: this.options.defaultProtocol
    }) ? ["a", Q(this.options.HTMLAttributes, n2), 0] : [
      "a",
      Q(this.options.HTMLAttributes, { ...n2, href: "" }),
      0
    ];
  },
  addCommands() {
    return {
      setLink: (n2) => ({ chain: e }) => {
        const { href: t } = n2;
        return this.options.isAllowedUri(t, {
          defaultValidate: (r) => !!fn(r, this.options.protocols),
          protocols: this.options.protocols,
          defaultProtocol: this.options.defaultProtocol
        }) ? e().setMark(this.name, n2).setMeta("preventAutolink", true).run() : false;
      },
      toggleLink: (n2) => ({ chain: e }) => {
        const { href: t } = n2;
        return this.options.isAllowedUri(t, {
          defaultValidate: (r) => !!fn(r, this.options.protocols),
          protocols: this.options.protocols,
          defaultProtocol: this.options.defaultProtocol
        }) ? e().toggleMark(this.name, n2, { extendEmptyMarkRange: true }).setMeta("preventAutolink", true).run() : false;
      },
      unsetLink: () => ({ chain: n2 }) => n2().unsetMark(this.name, { extendEmptyMarkRange: true }).setMeta("preventAutolink", true).run()
    };
  },
  addPasteRules() {
    return [
      Ln({
        find: (n2) => {
          const e = [];
          if (n2) {
            const { protocols: t, defaultProtocol: r } = this.options, i = fh(n2).filter((s) => s.isLink && this.options.isAllowedUri(s.value, {
              defaultValidate: (o) => !!fn(o, t),
              protocols: t,
              defaultProtocol: r
            }));
            i.length && i.forEach((s) => e.push({
              text: s.value,
              data: {
                href: s.href
              },
              index: s.start
            }));
          }
          return e;
        },
        type: this.type,
        getAttributes: (n2) => {
          var e;
          return {
            href: (e = n2.data) === null || e === void 0 ? void 0 : e.href
          };
        }
      })
    ];
  },
  addProseMirrorPlugins() {
    const n2 = [], { protocols: e, defaultProtocol: t } = this.options;
    return this.options.autolink && n2.push(lC({
      type: this.type,
      defaultProtocol: this.options.defaultProtocol,
      validate: (r) => this.options.isAllowedUri(r, {
        defaultValidate: (i) => !!fn(i, e),
        protocols: e,
        defaultProtocol: t
      }),
      shouldAutoLink: this.options.shouldAutoLink
    })), this.options.openOnClick === true && n2.push(aC({
      type: this.type
    })), this.options.linkOnPaste && n2.push(cC({
      editor: this.editor,
      defaultProtocol: this.options.defaultProtocol,
      type: this.type
    })), n2;
  }
}), fC = /(?:^|\s)(!\[(.+|:?)]\((\S+)(?:(?:\s+)["'](\S+)["'])?\))$/, hh = ce.create({
  name: "image",
  addOptions() {
    return {
      inline: false,
      allowBase64: false,
      HTMLAttributes: {}
    };
  },
  inline() {
    return this.options.inline;
  },
  group() {
    return this.options.inline ? "inline" : "block";
  },
  draggable: true,
  addAttributes() {
    return {
      src: {
        default: null
      },
      alt: {
        default: null
      },
      title: {
        default: null
      }
    };
  },
  parseHTML() {
    return [
      {
        tag: this.options.allowBase64 ? "img[src]" : 'img[src]:not([src^="data:"])'
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["img", Q(this.options.HTMLAttributes, n2)];
  },
  addCommands() {
    return {
      setImage: (n2) => ({ commands: e }) => e.insertContent({
        type: this.name,
        attrs: n2
      })
    };
  },
  addInputRules() {
    return [
      R0({
        find: fC,
        type: this.type,
        getAttributes: (n2) => {
          const [, , e, t, r] = n2;
          return { src: t, alt: e, title: r };
        }
      })
    ];
  }
}), hC = hh.extend({
  addAttributes() {
    return {
      src: {
        default: null
      },
      alt: {
        default: null
      },
      style: {
        default: "width: 100%; height: auto; cursor: pointer;",
        parseHTML: (n2) => {
          const e = n2.getAttribute("width");
          return e ? `width: ${e}px; height: auto; cursor: pointer;` : `${n2.style.cssText}`;
        }
      },
      title: {
        default: null
      },
      loading: {
        default: null
      },
      srcset: {
        default: null
      },
      sizes: {
        default: null
      },
      crossorigin: {
        default: null
      },
      usemap: {
        default: null
      },
      ismap: {
        default: null
      },
      width: {
        default: null
      },
      height: {
        default: null
      },
      referrerpolicy: {
        default: null
      },
      longdesc: {
        default: null
      },
      decoding: {
        default: null
      },
      class: {
        default: null
      },
      id: {
        default: null
      },
      name: {
        default: null
      },
      draggable: {
        default: true
      },
      tabindex: {
        default: null
      },
      "aria-label": {
        default: null
      },
      "aria-labelledby": {
        default: null
      },
      "aria-describedby": {
        default: null
      }
    };
  },
  addNodeView() {
    return ({ node: n2, editor: e, getPos: t }) => {
      const { view: r, options: { editable: i } } = e, { style: s } = n2.attrs, o = document.createElement("div"), l = document.createElement("div"), a = document.createElement("img"), c = "width: 24px; height: 24px; cursor: pointer;", u = () => {
        if (typeof t == "function") {
          const g = Object.assign(Object.assign({}, n2.attrs), { style: `${a.style.cssText}` });
          r.dispatch(r.state.tr.setNodeMarkup(t(), null, g));
        }
      }, d = () => {
        const g = document.createElement("div"), y = document.createElement("img"), w = document.createElement("img"), C = document.createElement("img"), b = (k) => {
          k.target.style.opacity = 0.3;
        }, S = (k) => {
          k.target.style.opacity = 1;
        };
        g.setAttribute("style", "position: absolute; top: 0%; left: 50%; width: 100px; height: 25px; z-index: 999; background-color: rgba(255, 255, 255, 0.7); border-radius: 4px; border: 2px solid #6C6C6C; cursor: pointer; transform: translate(-50%, -50%); display: flex; justify-content: space-between; align-items: center; padding: 0 10px;"), y.setAttribute("src", "https://fonts.gstatic.com/s/i/short-term/release/materialsymbolsoutlined/format_align_left/default/20px.svg"), y.setAttribute("style", c), y.addEventListener("mouseover", b), y.addEventListener("mouseout", S), w.setAttribute("src", "https://fonts.gstatic.com/s/i/short-term/release/materialsymbolsoutlined/format_align_center/default/20px.svg"), w.setAttribute("style", c), w.addEventListener("mouseover", b), w.addEventListener("mouseout", S), C.setAttribute("src", "https://fonts.gstatic.com/s/i/short-term/release/materialsymbolsoutlined/format_align_right/default/20px.svg"), C.setAttribute("style", c), C.addEventListener("mouseover", b), C.addEventListener("mouseout", S), y.addEventListener("click", () => {
          a.setAttribute("style", `${a.style.cssText} margin: 0 auto 0 0;`), u();
        }), w.addEventListener("click", () => {
          a.setAttribute("style", `${a.style.cssText} margin: 0 auto;`), u();
        }), C.addEventListener("click", () => {
          a.setAttribute("style", `${a.style.cssText} margin: 0 0 0 auto;`), u();
        }), g.appendChild(y), g.appendChild(w), g.appendChild(C), l.appendChild(g);
      };
      if (o.setAttribute("style", "display: flex;"), o.appendChild(l), l.setAttribute("style", `${s}`), l.appendChild(a), Object.entries(n2.attrs).forEach(([g, y]) => {
        y != null && a.setAttribute(g, y);
      }), !i)
        return { dom: a };
      const f = [
        "top: -4px; left: -4px; cursor: nwse-resize;",
        "top: -4px; right: -4px; cursor: nesw-resize;",
        "bottom: -4px; left: -4px; cursor: nesw-resize;",
        "bottom: -4px; right: -4px; cursor: nwse-resize;"
      ];
      let h2 = false, p2, m;
      return l.addEventListener("click", () => {
        if (l.childElementCount > 3)
          for (let g = 0; g < 5; g++)
            l.removeChild(l.lastChild);
        d(), l.setAttribute("style", `position: relative; border: 1px dashed #6C6C6C; ${s} cursor: pointer;`), Array.from({ length: 4 }, (g, y) => {
          const w = document.createElement("div");
          w.setAttribute("style", `position: absolute; width: 9px; height: 9px; border: 1.5px solid #6C6C6C; border-radius: 50%; ${f[y]}`), w.addEventListener("mousedown", (C) => {
            C.preventDefault(), h2 = true, p2 = C.clientX, m = l.offsetWidth;
            const b = (k) => {
              if (!h2)
                return;
              const T = y % 2 === 0 ? -(k.clientX - p2) : k.clientX - p2, M = m + T;
              l.style.width = M + "px", a.style.width = M + "px";
            }, S = () => {
              h2 && (h2 = false), u(), document.removeEventListener("mousemove", b), document.removeEventListener("mouseup", S);
            };
            document.addEventListener("mousemove", b), document.addEventListener("mouseup", S);
          }), l.appendChild(w);
        });
      }), document.addEventListener("click", (g) => {
        const y = g.target;
        if (!(l.contains(y) || y.style.cssText === c)) {
          const C = l.getAttribute("style"), b = C == null ? void 0 : C.replace("border: 1px dashed #6C6C6C;", "");
          if (l.setAttribute("style", b), l.childElementCount > 3)
            for (let S = 0; S < 5; S++)
              l.removeChild(l.lastChild);
        }
      }), {
        dom: o
      };
    };
  }
});
function pC(n2) {
  var e;
  const { char: t, allowSpaces: r, allowToIncludeChar: i, allowedPrefixes: s, startOfLine: o, $position: l } = n2, a = r && !i, c = P0(t), u = new RegExp(`\\s${c}$`), d = o ? "^" : "", f = i ? "" : c, h2 = a ? new RegExp(`${d}${c}.*?(?=\\s${f}|$)`, "gm") : new RegExp(`${d}(?:^)?${c}[^\\s${f}]*`, "gm"), p2 = ((e = l.nodeBefore) === null || e === void 0 ? void 0 : e.isText) && l.nodeBefore.text;
  if (!p2)
    return null;
  const m = l.pos - p2.length, g = Array.from(p2.matchAll(h2)).pop();
  if (!g || g.input === void 0 || g.index === void 0)
    return null;
  const y = g.input.slice(Math.max(0, g.index - 1), g.index), w = new RegExp(`^[${s == null ? void 0 : s.join("")}\0]?$`).test(y);
  if (s !== null && !w)
    return null;
  const C = m + g.index;
  let b = C + g[0].length;
  return a && u.test(p2.slice(b - 1, b + 1)) && (g[0] += " ", b += 1), C < l.pos && b >= l.pos ? {
    range: {
      from: C,
      to: b
    },
    query: g[0].slice(t.length),
    text: g[0]
  } : null;
}
const mC = new ue("suggestion");
function gC({ pluginKey: n2 = mC, editor: e, char: t = "@", allowSpaces: r = false, allowToIncludeChar: i = false, allowedPrefixes: s = [" "], startOfLine: o = false, decorationTag: l = "span", decorationClass: a = "suggestion", command: c = () => null, items: u = () => [], render: d = () => ({}), allow: f = () => true, findSuggestionMatch: h2 = pC }) {
  let p2;
  const m = d == null ? void 0 : d(), g = new le({
    key: n2,
    view() {
      return {
        update: async (y, w) => {
          var C, b, S, k, T, M, I;
          const N = (C = this.key) === null || C === void 0 ? void 0 : C.getState(w), j = (b = this.key) === null || b === void 0 ? void 0 : b.getState(y.state), K = N.active && j.active && N.range.from !== j.range.from, Y = !N.active && j.active, J = N.active && !j.active, Z = !Y && !J && N.query !== j.query, G = Y || K && Z, ee = Z || K, ae = J || K && Z;
          if (!G && !ee && !ae)
            return;
          const ye = ae && !G ? N : j, Be = y.dom.querySelector(`[data-decoration-id="${ye.decorationId}"]`);
          p2 = {
            editor: e,
            range: ye.range,
            query: ye.query,
            text: ye.text,
            items: [],
            command: (He) => c({
              editor: e,
              range: ye.range,
              props: He
            }),
            decorationNode: Be,
            // virtual node for popper.js or tippy.js
            // this can be used for building popups without a DOM node
            clientRect: Be ? () => {
              var He;
              const { decorationId: Ue } = (He = this.key) === null || He === void 0 ? void 0 : He.getState(e.state), tt2 = y.dom.querySelector(`[data-decoration-id="${Ue}"]`);
              return (tt2 == null ? void 0 : tt2.getBoundingClientRect()) || null;
            } : null
          }, G && ((S = m == null ? void 0 : m.onBeforeStart) === null || S === void 0 || S.call(m, p2)), ee && ((k = m == null ? void 0 : m.onBeforeUpdate) === null || k === void 0 || k.call(m, p2)), (ee || G) && (p2.items = await u({
            editor: e,
            query: ye.query
          })), ae && ((T = m == null ? void 0 : m.onExit) === null || T === void 0 || T.call(m, p2)), ee && ((M = m == null ? void 0 : m.onUpdate) === null || M === void 0 || M.call(m, p2)), G && ((I = m == null ? void 0 : m.onStart) === null || I === void 0 || I.call(m, p2));
        },
        destroy: () => {
          var y;
          p2 && ((y = m == null ? void 0 : m.onExit) === null || y === void 0 || y.call(m, p2));
        }
      };
    },
    state: {
      // Initialize the plugin's internal state.
      init() {
        return {
          active: false,
          range: {
            from: 0,
            to: 0
          },
          query: null,
          text: null,
          composing: false
        };
      },
      // Apply changes to the plugin state from a view transaction.
      apply(y, w, C, b) {
        const { isEditable: S } = e, { composing: k } = e.view, { selection: T } = y, { empty: M, from: I } = T, N = { ...w };
        if (N.composing = k, S && (M || e.view.composing)) {
          (I < w.range.from || I > w.range.to) && !k && !w.composing && (N.active = false);
          const j = h2({
            char: t,
            allowSpaces: r,
            allowToIncludeChar: i,
            allowedPrefixes: s,
            startOfLine: o,
            $position: T.$from
          }), K = `id_${Math.floor(Math.random() * 4294967295)}`;
          j && f({
            editor: e,
            state: b,
            range: j.range,
            isActive: w.active
          }) ? (N.active = true, N.decorationId = w.decorationId ? w.decorationId : K, N.range = j.range, N.query = j.query, N.text = j.text) : N.active = false;
        } else
          N.active = false;
        return N.active || (N.decorationId = null, N.range = { from: 0, to: 0 }, N.query = null, N.text = null), N;
      }
    },
    props: {
      // Call the keydown hook if suggestion is active.
      handleKeyDown(y, w) {
        var C;
        const { active: b, range: S } = g.getState(y.state);
        return b && ((C = m == null ? void 0 : m.onKeyDown) === null || C === void 0 ? void 0 : C.call(m, { view: y, event: w, range: S })) || false;
      },
      // Setup decorator on the currently active suggestion.
      decorations(y) {
        const { active: w, range: C, decorationId: b } = g.getState(y);
        return w ? ie.create(y.doc, [
          xe.inline(C.from, C.to, {
            nodeName: l,
            class: a,
            "data-decoration-id": b
          })
        ]) : null;
      }
    }
  });
  return g;
}
const yC = new ue("mention"), bC = ce.create({
  name: "mention",
  priority: 101,
  addOptions() {
    return {
      HTMLAttributes: {},
      renderText({ options: n2, node: e }) {
        var t;
        return `${n2.suggestion.char}${(t = e.attrs.label) !== null && t !== void 0 ? t : e.attrs.id}`;
      },
      deleteTriggerWithBackspace: false,
      renderHTML({ options: n2, node: e }) {
        var t;
        return [
          "span",
          Q(this.HTMLAttributes, n2.HTMLAttributes),
          `${n2.suggestion.char}${(t = e.attrs.label) !== null && t !== void 0 ? t : e.attrs.id}`
        ];
      },
      suggestion: {
        char: "@",
        pluginKey: yC,
        command: ({ editor: n2, range: e, props: t }) => {
          var r, i, s;
          const o = n2.view.state.selection.$to.nodeAfter;
          ((r = o == null ? void 0 : o.text) === null || r === void 0 ? void 0 : r.startsWith(" ")) && (e.to += 1), n2.chain().focus().insertContentAt(e, [
            {
              type: this.name,
              attrs: t
            },
            {
              type: "text",
              text: " "
            }
          ]).run(), (s = (i = n2.view.dom.ownerDocument.defaultView) === null || i === void 0 ? void 0 : i.getSelection()) === null || s === void 0 || s.collapseToEnd();
        },
        allow: ({ state: n2, range: e }) => {
          const t = n2.doc.resolve(e.from), r = n2.schema.nodes[this.name];
          return !!t.parent.type.contentMatch.matchType(r);
        }
      }
    };
  },
  group: "inline",
  inline: true,
  selectable: false,
  atom: true,
  addAttributes() {
    return {
      id: {
        default: null,
        parseHTML: (n2) => n2.getAttribute("data-id"),
        renderHTML: (n2) => n2.id ? {
          "data-id": n2.id
        } : {}
      },
      label: {
        default: null,
        parseHTML: (n2) => n2.getAttribute("data-label"),
        renderHTML: (n2) => n2.label ? {
          "data-label": n2.label
        } : {}
      }
    };
  },
  parseHTML() {
    return [
      {
        tag: `span[data-type="${this.name}"]`
      }
    ];
  },
  renderHTML({ node: n2, HTMLAttributes: e }) {
    if (this.options.renderLabel !== void 0)
      return console.warn("renderLabel is deprecated use renderText and renderHTML instead"), [
        "span",
        Q({ "data-type": this.name }, this.options.HTMLAttributes, e),
        this.options.renderLabel({
          options: this.options,
          node: n2
        })
      ];
    const t = { ...this.options };
    t.HTMLAttributes = Q({ "data-type": this.name }, this.options.HTMLAttributes, e);
    const r = this.options.renderHTML({
      options: t,
      node: n2
    });
    return typeof r == "string" ? [
      "span",
      Q({ "data-type": this.name }, this.options.HTMLAttributes, e),
      r
    ] : r;
  },
  renderText({ node: n2 }) {
    return this.options.renderLabel !== void 0 ? (console.warn("renderLabel is deprecated use renderText and renderHTML instead"), this.options.renderLabel({
      options: this.options,
      node: n2
    })) : this.options.renderText({
      options: this.options,
      node: n2
    });
  },
  addKeyboardShortcuts() {
    return {
      Backspace: () => this.editor.commands.command(({ tr: n2, state: e }) => {
        let t = false;
        const { selection: r } = e, { empty: i, anchor: s } = r;
        return i ? (e.doc.nodesBetween(s - 1, s, (o, l) => {
          if (o.type.name === this.name)
            return t = true, n2.insertText(this.options.deleteTriggerWithBackspace ? "" : this.options.suggestion.char || "", l, l + o.nodeSize), false;
        }), t) : false;
      })
    };
  },
  addProseMirrorPlugins() {
    return [
      gC({
        editor: this.editor,
        ...this.options.suggestion
      })
    ];
  }
}), vC = fe.create({
  name: "placeholder",
  addOptions() {
    return {
      emptyEditorClass: "is-editor-empty",
      emptyNodeClass: "is-empty",
      placeholder: "Write something …",
      showOnlyWhenEditable: true,
      showOnlyCurrent: true,
      includeChildren: false
    };
  },
  addProseMirrorPlugins() {
    return [
      new le({
        key: new ue("placeholder"),
        props: {
          decorations: ({ doc: n2, selection: e }) => {
            const t = this.editor.isEditable || !this.options.showOnlyWhenEditable, { anchor: r } = e, i = [];
            if (!t)
              return null;
            const s = this.editor.isEmpty;
            return n2.descendants((o, l) => {
              const a = r >= l && r <= l + o.nodeSize, c = !o.isLeaf && eo(o);
              if ((a || !this.options.showOnlyCurrent) && c) {
                const u = [this.options.emptyNodeClass];
                s && u.push(this.options.emptyEditorClass);
                const d = xe.node(l, l + o.nodeSize, {
                  class: u.join(" "),
                  "data-placeholder": typeof this.options.placeholder == "function" ? this.options.placeholder({
                    editor: this.editor,
                    node: o,
                    pos: l,
                    hasAnchor: a
                  }) : this.options.placeholder
                });
                i.push(d);
              }
              return this.options.includeChildren;
            }), ie.create(n2, i);
          }
        }
      })
    ];
  }
}), wC = ce.create({
  name: "listItem",
  addOptions() {
    return {
      HTMLAttributes: {},
      bulletListTypeName: "bulletList",
      orderedListTypeName: "orderedList"
    };
  },
  content: "paragraph block*",
  defining: true,
  parseHTML() {
    return [
      {
        tag: "li"
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["li", Q(this.options.HTMLAttributes, n2), 0];
  },
  addKeyboardShortcuts() {
    return {
      Enter: () => this.editor.commands.splitListItem(this.name),
      Tab: () => this.editor.commands.sinkListItem(this.name),
      "Shift-Tab": () => this.editor.commands.liftListItem(this.name)
    };
  }
}), kC = "listItem", bu = "textStyle", vu = /^(\d+)\.\s$/, CC = ce.create({
  name: "orderedList",
  addOptions() {
    return {
      itemTypeName: "listItem",
      HTMLAttributes: {},
      keepMarks: false,
      keepAttributes: false
    };
  },
  group: "block list",
  content() {
    return `${this.options.itemTypeName}+`;
  },
  addAttributes() {
    return {
      start: {
        default: 1,
        parseHTML: (n2) => n2.hasAttribute("start") ? parseInt(n2.getAttribute("start") || "", 10) : 1
      },
      type: {
        default: null,
        parseHTML: (n2) => n2.getAttribute("type")
      }
    };
  },
  parseHTML() {
    return [
      {
        tag: "ol"
      }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    const { start: e, ...t } = n2;
    return e === 1 ? ["ol", Q(this.options.HTMLAttributes, t), 0] : ["ol", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      toggleOrderedList: () => ({ commands: n2, chain: e }) => this.options.keepAttributes ? e().toggleList(this.name, this.options.itemTypeName, this.options.keepMarks).updateAttributes(kC, this.editor.getAttributes(bu)).run() : n2.toggleList(this.name, this.options.itemTypeName, this.options.keepMarks)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Shift-7": () => this.editor.commands.toggleOrderedList()
    };
  },
  addInputRules() {
    let n2 = Yr({
      find: vu,
      type: this.type,
      getAttributes: (e) => ({ start: +e[1] }),
      joinPredicate: (e, t) => t.childCount + t.attrs.start === +e[1]
    });
    return (this.options.keepMarks || this.options.keepAttributes) && (n2 = Yr({
      find: vu,
      type: this.type,
      keepMarks: this.options.keepMarks,
      keepAttributes: this.options.keepAttributes,
      getAttributes: (e) => ({ start: +e[1], ...this.editor.getAttributes(bu) }),
      joinPredicate: (e, t) => t.childCount + t.attrs.start === +e[1],
      editor: this.editor
    })), [
      n2
    ];
  }
}), xC = "listItem", wu = "textStyle", ku = /^\s*([-+*])\s$/, SC = ce.create({
  name: "bulletList",
  addOptions() {
    return {
      itemTypeName: "listItem",
      HTMLAttributes: {},
      keepMarks: false,
      keepAttributes: false
    };
  },
  group: "block list",
  content() {
    return `${this.options.itemTypeName}+`;
  },
  parseHTML() {
    return [
      { tag: "ul" }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["ul", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      toggleBulletList: () => ({ commands: n2, chain: e }) => this.options.keepAttributes ? e().toggleList(this.name, this.options.itemTypeName, this.options.keepMarks).updateAttributes(xC, this.editor.getAttributes(wu)).run() : n2.toggleList(this.name, this.options.itemTypeName, this.options.keepMarks)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Shift-8": () => this.editor.commands.toggleBulletList()
    };
  },
  addInputRules() {
    let n2 = Yr({
      find: ku,
      type: this.type
    });
    return (this.options.keepMarks || this.options.keepAttributes) && (n2 = Yr({
      find: ku,
      type: this.type,
      keepMarks: this.options.keepMarks,
      keepAttributes: this.options.keepAttributes,
      getAttributes: () => this.editor.getAttributes(wu),
      editor: this.editor
    })), [
      n2
    ];
  }
}), MC = /^\s*>\s$/, AC = ce.create({
  name: "blockquote",
  addOptions() {
    return {
      HTMLAttributes: {}
    };
  },
  content: "block+",
  group: "block",
  defining: true,
  parseHTML() {
    return [
      { tag: "blockquote" }
    ];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    return ["blockquote", Q(this.options.HTMLAttributes, n2), 0];
  },
  addCommands() {
    return {
      setBlockquote: () => ({ commands: n2 }) => n2.wrapIn(this.name),
      toggleBlockquote: () => ({ commands: n2 }) => n2.toggleWrap(this.name),
      unsetBlockquote: () => ({ commands: n2 }) => n2.lift(this.name)
    };
  },
  addKeyboardShortcuts() {
    return {
      "Mod-Shift-b": () => this.editor.commands.toggleBlockquote()
    };
  },
  addInputRules() {
    return [
      Yr({
        find: MC,
        type: this.type
      })
    ];
  }
});
var Ps = 200, me = function() {
};
me.prototype.append = function(e) {
  return e.length ? (e = me.from(e), !this.length && e || e.length < Ps && this.leafAppend(e) || this.length < Ps && e.leafPrepend(this) || this.appendInner(e)) : this;
};
me.prototype.prepend = function(e) {
  return e.length ? me.from(e).append(this) : this;
};
me.prototype.appendInner = function(e) {
  return new EC(this, e);
};
me.prototype.slice = function(e, t) {
  return e === void 0 && (e = 0), t === void 0 && (t = this.length), e >= t ? me.empty : this.sliceInner(Math.max(0, e), Math.min(this.length, t));
};
me.prototype.get = function(e) {
  if (!(e < 0 || e >= this.length))
    return this.getInner(e);
};
me.prototype.forEach = function(e, t, r) {
  t === void 0 && (t = 0), r === void 0 && (r = this.length), t <= r ? this.forEachInner(e, t, r, 0) : this.forEachInvertedInner(e, t, r, 0);
};
me.prototype.map = function(e, t, r) {
  t === void 0 && (t = 0), r === void 0 && (r = this.length);
  var i = [];
  return this.forEach(function(s, o) {
    return i.push(e(s, o));
  }, t, r), i;
};
me.from = function(e) {
  return e instanceof me ? e : e && e.length ? new ph(e) : me.empty;
};
var ph = /* @__PURE__ */ function(n2) {
  function e(r) {
    n2.call(this), this.values = r;
  }
  n2 && (e.__proto__ = n2), e.prototype = Object.create(n2 && n2.prototype), e.prototype.constructor = e;
  var t = { length: { configurable: true }, depth: { configurable: true } };
  return e.prototype.flatten = function() {
    return this.values;
  }, e.prototype.sliceInner = function(i, s) {
    return i == 0 && s == this.length ? this : new e(this.values.slice(i, s));
  }, e.prototype.getInner = function(i) {
    return this.values[i];
  }, e.prototype.forEachInner = function(i, s, o, l) {
    for (var a = s; a < o; a++)
      if (i(this.values[a], l + a) === false)
        return false;
  }, e.prototype.forEachInvertedInner = function(i, s, o, l) {
    for (var a = s - 1; a >= o; a--)
      if (i(this.values[a], l + a) === false)
        return false;
  }, e.prototype.leafAppend = function(i) {
    if (this.length + i.length <= Ps)
      return new e(this.values.concat(i.flatten()));
  }, e.prototype.leafPrepend = function(i) {
    if (this.length + i.length <= Ps)
      return new e(i.flatten().concat(this.values));
  }, t.length.get = function() {
    return this.values.length;
  }, t.depth.get = function() {
    return 0;
  }, Object.defineProperties(e.prototype, t), e;
}(me);
me.empty = new ph([]);
var EC = /* @__PURE__ */ function(n2) {
  function e(t, r) {
    n2.call(this), this.left = t, this.right = r, this.length = t.length + r.length, this.depth = Math.max(t.depth, r.depth) + 1;
  }
  return n2 && (e.__proto__ = n2), e.prototype = Object.create(n2 && n2.prototype), e.prototype.constructor = e, e.prototype.flatten = function() {
    return this.left.flatten().concat(this.right.flatten());
  }, e.prototype.getInner = function(r) {
    return r < this.left.length ? this.left.get(r) : this.right.get(r - this.left.length);
  }, e.prototype.forEachInner = function(r, i, s, o) {
    var l = this.left.length;
    if (i < l && this.left.forEachInner(r, i, Math.min(s, l), o) === false || s > l && this.right.forEachInner(r, Math.max(i - l, 0), Math.min(this.length, s) - l, o + l) === false)
      return false;
  }, e.prototype.forEachInvertedInner = function(r, i, s, o) {
    var l = this.left.length;
    if (i > l && this.right.forEachInvertedInner(r, i - l, Math.max(s, l) - l, o + l) === false || s < l && this.left.forEachInvertedInner(r, Math.min(i, l), s, o) === false)
      return false;
  }, e.prototype.sliceInner = function(r, i) {
    if (r == 0 && i == this.length)
      return this;
    var s = this.left.length;
    return i <= s ? this.left.slice(r, i) : r >= s ? this.right.slice(r - s, i - s) : this.left.slice(r, s).append(this.right.slice(0, i - s));
  }, e.prototype.leafAppend = function(r) {
    var i = this.right.leafAppend(r);
    if (i)
      return new e(this.left, i);
  }, e.prototype.leafPrepend = function(r) {
    var i = this.left.leafPrepend(r);
    if (i)
      return new e(i, this.right);
  }, e.prototype.appendInner = function(r) {
    return this.left.depth >= Math.max(this.right.depth, r.depth) + 1 ? new e(this.left, new e(this.right, r)) : new e(this, r);
  }, e;
}(me);
const TC = 500;
class st {
  constructor(e, t) {
    this.items = e, this.eventCount = t;
  }
  // Pop the latest event off the branch's history and apply it
  // to a document transform.
  popEvent(e, t) {
    if (this.eventCount == 0)
      return null;
    let r = this.items.length;
    for (; ; r--)
      if (this.items.get(r - 1).selection) {
        --r;
        break;
      }
    let i, s;
    t && (i = this.remapping(r, this.items.length), s = i.maps.length);
    let o = e.tr, l, a, c = [], u = [];
    return this.items.forEach((d, f) => {
      if (!d.step) {
        i || (i = this.remapping(r, f + 1), s = i.maps.length), s--, u.push(d);
        return;
      }
      if (i) {
        u.push(new dt(d.map));
        let h2 = d.step.map(i.slice(s)), p2;
        h2 && o.maybeStep(h2).doc && (p2 = o.mapping.maps[o.mapping.maps.length - 1], c.push(new dt(p2, void 0, void 0, c.length + u.length))), s--, p2 && i.appendMap(p2, s);
      } else
        o.maybeStep(d.step);
      if (d.selection)
        return l = i ? d.selection.map(i.slice(s)) : d.selection, a = new st(this.items.slice(0, r).append(u.reverse().concat(c)), this.eventCount - 1), false;
    }, this.items.length, 0), { remaining: a, transform: o, selection: l };
  }
  // Create a new branch with the given transform added.
  addTransform(e, t, r, i) {
    let s = [], o = this.eventCount, l = this.items, a = !i && l.length ? l.get(l.length - 1) : null;
    for (let u = 0; u < e.steps.length; u++) {
      let d = e.steps[u].invert(e.docs[u]), f = new dt(e.mapping.maps[u], d, t), h2;
      (h2 = a && a.merge(f)) && (f = h2, u ? s.pop() : l = l.slice(0, l.length - 1)), s.push(f), t && (o++, t = void 0), i || (a = f);
    }
    let c = o - r.depth;
    return c > NC && (l = OC(l, c), o -= c), new st(l.append(s), o);
  }
  remapping(e, t) {
    let r = new jr();
    return this.items.forEach((i, s) => {
      let o = i.mirrorOffset != null && s - i.mirrorOffset >= e ? r.maps.length - i.mirrorOffset : void 0;
      r.appendMap(i.map, o);
    }, e, t), r;
  }
  addMaps(e) {
    return this.eventCount == 0 ? this : new st(this.items.append(e.map((t) => new dt(t))), this.eventCount);
  }
  // When the collab module receives remote changes, the history has
  // to know about those, so that it can adjust the steps that were
  // rebased on top of the remote changes, and include the position
  // maps for the remote changes in its array of items.
  rebased(e, t) {
    if (!this.eventCount)
      return this;
    let r = [], i = Math.max(0, this.items.length - t), s = e.mapping, o = e.steps.length, l = this.eventCount;
    this.items.forEach((f) => {
      f.selection && l--;
    }, i);
    let a = t;
    this.items.forEach((f) => {
      let h2 = s.getMirror(--a);
      if (h2 == null)
        return;
      o = Math.min(o, h2);
      let p2 = s.maps[h2];
      if (f.step) {
        let m = e.steps[h2].invert(e.docs[h2]), g = f.selection && f.selection.map(s.slice(a + 1, h2));
        g && l++, r.push(new dt(p2, m, g));
      } else
        r.push(new dt(p2));
    }, i);
    let c = [];
    for (let f = t; f < o; f++)
      c.push(new dt(s.maps[f]));
    let u = this.items.slice(0, i).append(c).append(r), d = new st(u, l);
    return d.emptyItemCount() > TC && (d = d.compress(this.items.length - r.length)), d;
  }
  emptyItemCount() {
    let e = 0;
    return this.items.forEach((t) => {
      t.step || e++;
    }), e;
  }
  // Compressing a branch means rewriting it to push the air (map-only
  // items) out. During collaboration, these naturally accumulate
  // because each remote change adds one. The `upto` argument is used
  // to ensure that only the items below a given level are compressed,
  // because `rebased` relies on a clean, untouched set of items in
  // order to associate old items with rebased steps.
  compress(e = this.items.length) {
    let t = this.remapping(0, e), r = t.maps.length, i = [], s = 0;
    return this.items.forEach((o, l) => {
      if (l >= e)
        i.push(o), o.selection && s++;
      else if (o.step) {
        let a = o.step.map(t.slice(r)), c = a && a.getMap();
        if (r--, c && t.appendMap(c, r), a) {
          let u = o.selection && o.selection.map(t.slice(r));
          u && s++;
          let d = new dt(c.invert(), a, u), f, h2 = i.length - 1;
          (f = i.length && i[h2].merge(d)) ? i[h2] = f : i.push(d);
        }
      } else o.map && r--;
    }, this.items.length, 0), new st(me.from(i.reverse()), s);
  }
}
st.empty = new st(me.empty, 0);
function OC(n2, e) {
  let t;
  return n2.forEach((r, i) => {
    if (r.selection && e-- == 0)
      return t = i, false;
  }), n2.slice(t);
}
class dt {
  constructor(e, t, r, i) {
    this.map = e, this.step = t, this.selection = r, this.mirrorOffset = i;
  }
  merge(e) {
    if (this.step && e.step && !e.selection) {
      let t = e.step.merge(this.step);
      if (t)
        return new dt(t.getMap().invert(), t, this.selection);
    }
  }
}
class jt {
  constructor(e, t, r, i, s) {
    this.done = e, this.undone = t, this.prevRanges = r, this.prevTime = i, this.prevComposition = s;
  }
}
const NC = 20;
function DC(n2, e, t, r) {
  let i = t.getMeta(En), s;
  if (i)
    return i.historyState;
  t.getMeta(IC) && (n2 = new jt(n2.done, n2.undone, null, 0, -1));
  let o = t.getMeta("appendedTransaction");
  if (t.steps.length == 0)
    return n2;
  if (o && o.getMeta(En))
    return o.getMeta(En).redo ? new jt(n2.done.addTransform(t, void 0, r, Ui(e)), n2.undone, Cu(t.mapping.maps), n2.prevTime, n2.prevComposition) : new jt(n2.done, n2.undone.addTransform(t, void 0, r, Ui(e)), null, n2.prevTime, n2.prevComposition);
  if (t.getMeta("addToHistory") !== false && !(o && o.getMeta("addToHistory") === false)) {
    let l = t.getMeta("composition"), a = n2.prevTime == 0 || !o && n2.prevComposition != l && (n2.prevTime < (t.time || 0) - r.newGroupDelay || !LC(t, n2.prevRanges)), c = o ? Ho(n2.prevRanges, t.mapping) : Cu(t.mapping.maps);
    return new jt(n2.done.addTransform(t, a ? e.selection.getBookmark() : void 0, r, Ui(e)), st.empty, c, t.time, l ?? n2.prevComposition);
  } else return (s = t.getMeta("rebased")) ? new jt(n2.done.rebased(t, s), n2.undone.rebased(t, s), Ho(n2.prevRanges, t.mapping), n2.prevTime, n2.prevComposition) : new jt(n2.done.addMaps(t.mapping.maps), n2.undone.addMaps(t.mapping.maps), Ho(n2.prevRanges, t.mapping), n2.prevTime, n2.prevComposition);
}
function LC(n2, e) {
  if (!e)
    return false;
  if (!n2.docChanged)
    return true;
  let t = false;
  return n2.mapping.maps[0].forEach((r, i) => {
    for (let s = 0; s < e.length; s += 2)
      r <= e[s + 1] && i >= e[s] && (t = true);
  }), t;
}
function Cu(n2) {
  let e = [];
  for (let t = n2.length - 1; t >= 0 && e.length == 0; t--)
    n2[t].forEach((r, i, s, o) => e.push(s, o));
  return e;
}
function Ho(n2, e) {
  if (!n2)
    return null;
  let t = [];
  for (let r = 0; r < n2.length; r += 2) {
    let i = e.map(n2[r], 1), s = e.map(n2[r + 1], -1);
    i <= s && t.push(i, s);
  }
  return t;
}
function RC(n2, e, t) {
  let r = Ui(e), i = En.get(e).spec.config, s = (t ? n2.undone : n2.done).popEvent(e, r);
  if (!s)
    return null;
  let o = s.selection.resolve(s.transform.doc), l = (t ? n2.done : n2.undone).addTransform(s.transform, e.selection.getBookmark(), i, r), a = new jt(t ? l : s.remaining, t ? s.remaining : l, null, 0, -1);
  return s.transform.setSelection(o).setMeta(En, { redo: t, historyState: a });
}
let Fo = false, xu = null;
function Ui(n2) {
  let e = n2.plugins;
  if (xu != e) {
    Fo = false, xu = e;
    for (let t = 0; t < e.length; t++)
      if (e[t].spec.historyPreserveItems) {
        Fo = true;
        break;
      }
  }
  return Fo;
}
const En = new ue("history"), IC = new ue("closeHistory");
function PC(n2 = {}) {
  return n2 = {
    depth: n2.depth || 100,
    newGroupDelay: n2.newGroupDelay || 500
  }, new le({
    key: En,
    state: {
      init() {
        return new jt(st.empty, st.empty, null, 0, -1);
      },
      apply(e, t, r) {
        return DC(t, r, e, n2);
      }
    },
    config: n2,
    props: {
      handleDOMEvents: {
        beforeinput(e, t) {
          let r = t.inputType, i = r == "historyUndo" ? gh : r == "historyRedo" ? yh : null;
          return i ? (t.preventDefault(), i(e.state, e.dispatch)) : false;
        }
      }
    }
  });
}
function mh(n2, e) {
  return (t, r) => {
    let i = En.getState(t);
    if (!i || (n2 ? i.undone : i.done).eventCount == 0)
      return false;
    if (r) {
      let s = RC(i, t, n2);
      s && r(s.scrollIntoView());
    }
    return true;
  };
}
const gh = mh(false), yh = mh(true), BC = fe.create({
  name: "history",
  addOptions() {
    return {
      depth: 100,
      newGroupDelay: 500
    };
  },
  addCommands() {
    return {
      undo: () => ({ state: n2, dispatch: e }) => gh(n2, e),
      redo: () => ({ state: n2, dispatch: e }) => yh(n2, e)
    };
  },
  addProseMirrorPlugins() {
    return [
      PC(this.options)
    ];
  },
  addKeyboardShortcuts() {
    return {
      "Mod-z": () => this.editor.commands.undo(),
      "Shift-Mod-z": () => this.editor.commands.redo(),
      "Mod-y": () => this.editor.commands.redo(),
      // Russian keyboard layouts
      "Mod-я": () => this.editor.commands.undo(),
      "Shift-Mod-я": () => this.editor.commands.redo()
    };
  }
});
function HC(n2 = {}) {
  return new le({
    view(e) {
      return new FC(e, n2);
    }
  });
}
class FC {
  constructor(e, t) {
    var r;
    this.editorView = e, this.cursorPos = null, this.element = null, this.timeout = -1, this.width = (r = t.width) !== null && r !== void 0 ? r : 1, this.color = t.color === false ? void 0 : t.color || "black", this.class = t.class, this.handlers = ["dragover", "dragend", "drop", "dragleave"].map((i) => {
      let s = (o) => {
        this[i](o);
      };
      return e.dom.addEventListener(i, s), { name: i, handler: s };
    });
  }
  destroy() {
    this.handlers.forEach(({ name: e, handler: t }) => this.editorView.dom.removeEventListener(e, t));
  }
  update(e, t) {
    this.cursorPos != null && t.doc != e.state.doc && (this.cursorPos > e.state.doc.content.size ? this.setCursor(null) : this.updateOverlay());
  }
  setCursor(e) {
    e != this.cursorPos && (this.cursorPos = e, e == null ? (this.element.parentNode.removeChild(this.element), this.element = null) : this.updateOverlay());
  }
  updateOverlay() {
    let e = this.editorView.state.doc.resolve(this.cursorPos), t = !e.parent.inlineContent, r, i = this.editorView.dom, s = i.getBoundingClientRect(), o = s.width / i.offsetWidth, l = s.height / i.offsetHeight;
    if (t) {
      let d = e.nodeBefore, f = e.nodeAfter;
      if (d || f) {
        let h2 = this.editorView.nodeDOM(this.cursorPos - (d ? d.nodeSize : 0));
        if (h2) {
          let p2 = h2.getBoundingClientRect(), m = d ? p2.bottom : p2.top;
          d && f && (m = (m + this.editorView.nodeDOM(this.cursorPos).getBoundingClientRect().top) / 2);
          let g = this.width / 2 * l;
          r = { left: p2.left, right: p2.right, top: m - g, bottom: m + g };
        }
      }
    }
    if (!r) {
      let d = this.editorView.coordsAtPos(this.cursorPos), f = this.width / 2 * o;
      r = { left: d.left - f, right: d.left + f, top: d.top, bottom: d.bottom };
    }
    let a = this.editorView.dom.offsetParent;
    this.element || (this.element = a.appendChild(document.createElement("div")), this.class && (this.element.className = this.class), this.element.style.cssText = "position: absolute; z-index: 50; pointer-events: none;", this.color && (this.element.style.backgroundColor = this.color)), this.element.classList.toggle("prosemirror-dropcursor-block", t), this.element.classList.toggle("prosemirror-dropcursor-inline", !t);
    let c, u;
    if (!a || a == document.body && getComputedStyle(a).position == "static")
      c = -pageXOffset, u = -pageYOffset;
    else {
      let d = a.getBoundingClientRect(), f = d.width / a.offsetWidth, h2 = d.height / a.offsetHeight;
      c = d.left - a.scrollLeft * f, u = d.top - a.scrollTop * h2;
    }
    this.element.style.left = (r.left - c) / o + "px", this.element.style.top = (r.top - u) / l + "px", this.element.style.width = (r.right - r.left) / o + "px", this.element.style.height = (r.bottom - r.top) / l + "px";
  }
  scheduleRemoval(e) {
    clearTimeout(this.timeout), this.timeout = setTimeout(() => this.setCursor(null), e);
  }
  dragover(e) {
    if (!this.editorView.editable)
      return;
    let t = this.editorView.posAtCoords({ left: e.clientX, top: e.clientY }), r = t && t.inside >= 0 && this.editorView.state.doc.nodeAt(t.inside), i = r && r.type.spec.disableDropCursor, s = typeof i == "function" ? i(this.editorView, t, e) : i;
    if (t && !s) {
      let o = t.pos;
      if (this.editorView.dragging && this.editorView.dragging.slice) {
        let l = ld(this.editorView.state.doc, o, this.editorView.dragging.slice);
        l != null && (o = l);
      }
      this.setCursor(o), this.scheduleRemoval(5e3);
    }
  }
  dragend() {
    this.scheduleRemoval(20);
  }
  drop() {
    this.scheduleRemoval(20);
  }
  dragleave(e) {
    this.editorView.dom.contains(e.relatedTarget) || this.setCursor(null);
  }
}
const zC = fe.create({
  name: "dropCursor",
  addOptions() {
    return {
      color: "currentColor",
      width: 1,
      class: void 0
    };
  },
  addProseMirrorPlugins() {
    return [
      HC(this.options)
    ];
  }
}), VC = /^((?:https?:)?\/\/)?((?:www|m|music)\.)?((?:youtube\.com|youtu.be|youtube-nocookie\.com))(\/(?:[\w-]+\?v=|embed\/|v\/)?)([\w-]+)(\S+)?$/, $C = /^((?:https?:)?\/\/)?((?:www|m|music)\.)?((?:youtube\.com|youtu.be|youtube-nocookie\.com))(\/(?:[\w-]+\?v=|embed\/|v\/)?)([\w-]+)(\S+)?$/g, bh = (n2) => n2.match(VC), _C = (n2, e) => e ? "https://www.youtube-nocookie.com/embed/videoseries?list=" : n2 ? "https://www.youtube-nocookie.com/embed/" : "https://www.youtube.com/embed/", jC = (n2) => n2.searchParams.has("v") ? { id: n2.searchParams.get("v") } : n2.hostname === "youtu.be" || n2.pathname.includes("shorts") || n2.pathname.includes("live") ? { id: n2.pathname.split("/").pop() } : n2.searchParams.has("list") ? { id: n2.searchParams.get("list"), isPlaylist: true } : null, WC = (n2) => {
  var e;
  const { url: t, allowFullscreen: r, autoplay: i, ccLanguage: s, ccLoadPolicy: o, controls: l, disableKBcontrols: a, enableIFrameApi: c, endTime: u, interfaceLanguage: d, ivLoadPolicy: f, loop: h2, modestBranding: p2, nocookie: m, origin: g, playlist: y, progressBarColor: w, startAt: C, rel: b } = n2;
  if (!bh(t))
    return null;
  if (t.includes("/embed/"))
    return t;
  const S = new URL(t), { id: k, isPlaylist: T } = (e = jC(S)) !== null && e !== void 0 ? e : {};
  if (!k)
    return null;
  const M = new URL(`${_C(m, T)}${k}`);
  return S.searchParams.has("t") && M.searchParams.set("start", S.searchParams.get("t").replaceAll("s", "")), r === false && M.searchParams.set("fs", "0"), i && M.searchParams.set("autoplay", "1"), s && M.searchParams.set("cc_lang_pref", s), o && M.searchParams.set("cc_load_policy", "1"), l || M.searchParams.set("controls", "0"), a && M.searchParams.set("disablekb", "1"), c && M.searchParams.set("enablejsapi", "1"), u && M.searchParams.set("end", u.toString()), d && M.searchParams.set("hl", d), f && M.searchParams.set("iv_load_policy", f.toString()), h2 && M.searchParams.set("loop", "1"), p2 && M.searchParams.set("modestbranding", "1"), g && M.searchParams.set("origin", g), y && M.searchParams.set("playlist", y), C && M.searchParams.set("start", C.toString()), w && M.searchParams.set("color", w), b !== void 0 && M.searchParams.set("rel", b.toString()), M.toString();
}, UC = ce.create({
  name: "youtube",
  addOptions() {
    return {
      addPasteHandler: true,
      allowFullscreen: true,
      autoplay: false,
      ccLanguage: void 0,
      ccLoadPolicy: void 0,
      controls: true,
      disableKBcontrols: false,
      enableIFrameApi: false,
      endTime: 0,
      height: 480,
      interfaceLanguage: void 0,
      ivLoadPolicy: 0,
      loop: false,
      modestBranding: false,
      HTMLAttributes: {},
      inline: false,
      nocookie: false,
      origin: "",
      playlist: "",
      progressBarColor: void 0,
      width: 640,
      rel: 1
    };
  },
  inline() {
    return this.options.inline;
  },
  group() {
    return this.options.inline ? "inline" : "block";
  },
  draggable: true,
  addAttributes() {
    return {
      src: {
        default: null
      },
      start: {
        default: 0
      },
      width: {
        default: this.options.width
      },
      height: {
        default: this.options.height
      }
    };
  },
  parseHTML() {
    return [
      {
        tag: "div[data-youtube-video] iframe"
      }
    ];
  },
  addCommands() {
    return {
      setYoutubeVideo: (n2) => ({ commands: e }) => bh(n2.src) ? e.insertContent({
        type: this.name,
        attrs: n2
      }) : false
    };
  },
  addPasteRules() {
    return this.options.addPasteHandler ? [
      B0({
        find: $C,
        type: this.type,
        getAttributes: (n2) => ({ src: n2.input })
      })
    ] : [];
  },
  renderHTML({ HTMLAttributes: n2 }) {
    const e = WC({
      url: n2.src,
      allowFullscreen: this.options.allowFullscreen,
      autoplay: this.options.autoplay,
      ccLanguage: this.options.ccLanguage,
      ccLoadPolicy: this.options.ccLoadPolicy,
      controls: this.options.controls,
      disableKBcontrols: this.options.disableKBcontrols,
      enableIFrameApi: this.options.enableIFrameApi,
      endTime: this.options.endTime,
      interfaceLanguage: this.options.interfaceLanguage,
      ivLoadPolicy: this.options.ivLoadPolicy,
      loop: this.options.loop,
      modestBranding: this.options.modestBranding,
      nocookie: this.options.nocookie,
      origin: this.options.origin,
      playlist: this.options.playlist,
      progressBarColor: this.options.progressBarColor,
      startAt: n2.start || 0,
      rel: this.options.rel
    });
    return n2.src = e, [
      "div",
      { "data-youtube-video": "" },
      [
        "iframe",
        Q(this.options.HTMLAttributes, {
          width: this.options.width,
          height: this.options.height,
          allowfullscreen: this.options.allowFullscreen,
          autoplay: this.options.autoplay,
          ccLanguage: this.options.ccLanguage,
          ccLoadPolicy: this.options.ccLoadPolicy,
          disableKBcontrols: this.options.disableKBcontrols,
          enableIFrameApi: this.options.enableIFrameApi,
          endTime: this.options.endTime,
          interfaceLanguage: this.options.interfaceLanguage,
          ivLoadPolicy: this.options.ivLoadPolicy,
          loop: this.options.loop,
          modestBranding: this.options.modestBranding,
          origin: this.options.origin,
          playlist: this.options.playlist,
          progressBarColor: this.options.progressBarColor,
          rel: this.options.rel
        }, n2)
      ]
    ];
  }
});
function Bt(n2) {
  return Array.isArray ? Array.isArray(n2) : kh(n2) === "[object Array]";
}
function KC(n2) {
  if (typeof n2 == "string")
    return n2;
  let e = n2 + "";
  return e == "0" && 1 / n2 == -1 / 0 ? "-0" : e;
}
function qC(n2) {
  return n2 == null ? "" : KC(n2);
}
function pt(n2) {
  return typeof n2 == "string";
}
function vh(n2) {
  return typeof n2 == "number";
}
function JC(n2) {
  return n2 === true || n2 === false || GC(n2) && kh(n2) == "[object Boolean]";
}
function wh(n2) {
  return typeof n2 == "object";
}
function GC(n2) {
  return wh(n2) && n2 !== null;
}
function ze(n2) {
  return n2 != null;
}
function zo(n2) {
  return !n2.trim().length;
}
function kh(n2) {
  return n2 == null ? n2 === void 0 ? "[object Undefined]" : "[object Null]" : Object.prototype.toString.call(n2);
}
const YC = "Incorrect 'index' type", XC = (n2) => `Invalid value for key ${n2}`, QC = (n2) => `Pattern length exceeds max of ${n2}.`, ZC = (n2) => `Missing ${n2} property in key`, ex = (n2) => `Property 'weight' in key '${n2}' must be a positive integer`, Su = Object.prototype.hasOwnProperty;
class tx {
  constructor(e) {
    this._keys = [], this._keyMap = {};
    let t = 0;
    e.forEach((r) => {
      let i = Ch(r);
      this._keys.push(i), this._keyMap[i.id] = i, t += i.weight;
    }), this._keys.forEach((r) => {
      r.weight /= t;
    });
  }
  get(e) {
    return this._keyMap[e];
  }
  keys() {
    return this._keys;
  }
  toJSON() {
    return JSON.stringify(this._keys);
  }
}
function Ch(n2) {
  let e = null, t = null, r = null, i = 1, s = null;
  if (pt(n2) || Bt(n2))
    r = n2, e = Mu(n2), t = Ml(n2);
  else {
    if (!Su.call(n2, "name"))
      throw new Error(ZC("name"));
    const o = n2.name;
    if (r = o, Su.call(n2, "weight") && (i = n2.weight, i <= 0))
      throw new Error(ex(o));
    e = Mu(o), t = Ml(o), s = n2.getFn;
  }
  return { path: e, id: t, weight: i, src: r, getFn: s };
}
function Mu(n2) {
  return Bt(n2) ? n2 : n2.split(".");
}
function Ml(n2) {
  return Bt(n2) ? n2.join(".") : n2;
}
function nx(n2, e) {
  let t = [], r = false;
  const i = (s, o, l) => {
    if (ze(s))
      if (!o[l])
        t.push(s);
      else {
        let a = o[l];
        const c = s[a];
        if (!ze(c))
          return;
        if (l === o.length - 1 && (pt(c) || vh(c) || JC(c)))
          t.push(qC(c));
        else if (Bt(c)) {
          r = true;
          for (let u = 0, d = c.length; u < d; u += 1)
            i(c[u], o, l + 1);
        } else o.length && i(c, o, l + 1);
      }
  };
  return i(n2, pt(e) ? e.split(".") : e, 0), r ? t : t[0];
}
const rx = {
  // Whether the matches should be included in the result set. When `true`, each record in the result
  // set will include the indices of the matched characters.
  // These can consequently be used for highlighting purposes.
  includeMatches: false,
  // When `true`, the matching function will continue to the end of a search pattern even if
  // a perfect match has already been located in the string.
  findAllMatches: false,
  // Minimum number of characters that must be matched before a result is considered a match
  minMatchCharLength: 1
}, ix = {
  // When `true`, the algorithm continues searching to the end of the input even if a perfect
  // match is found before the end of the same input.
  isCaseSensitive: false,
  // When `true`, the algorithm will ignore diacritics (accents) in comparisons
  ignoreDiacritics: false,
  // When true, the matching function will continue to the end of a search pattern even if
  includeScore: false,
  // List of properties that will be searched. This also supports nested properties.
  keys: [],
  // Whether to sort the result list, by score
  shouldSort: true,
  // Default sort function: sort by ascending score, ascending index
  sortFn: (n2, e) => n2.score === e.score ? n2.idx < e.idx ? -1 : 1 : n2.score < e.score ? -1 : 1
}, sx = {
  // Approximately where in the text is the pattern expected to be found?
  location: 0,
  // At what point does the match algorithm give up. A threshold of '0.0' requires a perfect match
  // (of both letters and location), a threshold of '1.0' would match anything.
  threshold: 0.6,
  // Determines how close the match must be to the fuzzy location (specified above).
  // An exact letter match which is 'distance' characters away from the fuzzy location
  // would score as a complete mismatch. A distance of '0' requires the match be at
  // the exact location specified, a threshold of '1000' would require a perfect match
  // to be within 800 characters of the fuzzy location to be found using a 0.8 threshold.
  distance: 100
}, ox = {
  // When `true`, it enables the use of unix-like search commands
  useExtendedSearch: false,
  // The get function to use when fetching an object's properties.
  // The default will search nested paths *ie foo.bar.baz*
  getFn: nx,
  // When `true`, search will ignore `location` and `distance`, so it won't matter
  // where in the string the pattern appears.
  // More info: https://fusejs.io/concepts/scoring-theory.html#fuzziness-score
  ignoreLocation: false,
  // When `true`, the calculation for the relevance score (used for sorting) will
  // ignore the field-length norm.
  // More info: https://fusejs.io/concepts/scoring-theory.html#field-length-norm
  ignoreFieldNorm: false,
  // The weight to determine how much field length norm effects scoring.
  fieldNormWeight: 1
};
var H = {
  ...ix,
  ...rx,
  ...sx,
  ...ox
};
const lx = /[^ ]+/g;
function ax(n2 = 1, e = 3) {
  const t = /* @__PURE__ */ new Map(), r = Math.pow(10, e);
  return {
    get(i) {
      const s = i.match(lx).length;
      if (t.has(s))
        return t.get(s);
      const o = 1 / Math.pow(s, 0.5 * n2), l = parseFloat(Math.round(o * r) / r);
      return t.set(s, l), l;
    },
    clear() {
      t.clear();
    }
  };
}
class ka {
  constructor({
    getFn: e = H.getFn,
    fieldNormWeight: t = H.fieldNormWeight
  } = {}) {
    this.norm = ax(t, 3), this.getFn = e, this.isCreated = false, this.setIndexRecords();
  }
  setSources(e = []) {
    this.docs = e;
  }
  setIndexRecords(e = []) {
    this.records = e;
  }
  setKeys(e = []) {
    this.keys = e, this._keysMap = {}, e.forEach((t, r) => {
      this._keysMap[t.id] = r;
    });
  }
  create() {
    this.isCreated || !this.docs.length || (this.isCreated = true, pt(this.docs[0]) ? this.docs.forEach((e, t) => {
      this._addString(e, t);
    }) : this.docs.forEach((e, t) => {
      this._addObject(e, t);
    }), this.norm.clear());
  }
  // Adds a doc to the end of the index
  add(e) {
    const t = this.size();
    pt(e) ? this._addString(e, t) : this._addObject(e, t);
  }
  // Removes the doc at the specified index of the index
  removeAt(e) {
    this.records.splice(e, 1);
    for (let t = e, r = this.size(); t < r; t += 1)
      this.records[t].i -= 1;
  }
  getValueForItemAtKeyId(e, t) {
    return e[this._keysMap[t]];
  }
  size() {
    return this.records.length;
  }
  _addString(e, t) {
    if (!ze(e) || zo(e))
      return;
    let r = {
      v: e,
      i: t,
      n: this.norm.get(e)
    };
    this.records.push(r);
  }
  _addObject(e, t) {
    let r = { i: t, $: {} };
    this.keys.forEach((i, s) => {
      let o = i.getFn ? i.getFn(e) : this.getFn(e, i.path);
      if (ze(o)) {
        if (Bt(o)) {
          let l = [];
          const a = [{ nestedArrIndex: -1, value: o }];
          for (; a.length; ) {
            const { nestedArrIndex: c, value: u } = a.pop();
            if (ze(u))
              if (pt(u) && !zo(u)) {
                let d = {
                  v: u,
                  i: c,
                  n: this.norm.get(u)
                };
                l.push(d);
              } else Bt(u) && u.forEach((d, f) => {
                a.push({
                  nestedArrIndex: f,
                  value: d
                });
              });
          }
          r.$[s] = l;
        } else if (pt(o) && !zo(o)) {
          let l = {
            v: o,
            n: this.norm.get(o)
          };
          r.$[s] = l;
        }
      }
    }), this.records.push(r);
  }
  toJSON() {
    return {
      keys: this.keys,
      records: this.records
    };
  }
}
function xh(n2, e, { getFn: t = H.getFn, fieldNormWeight: r = H.fieldNormWeight } = {}) {
  const i = new ka({ getFn: t, fieldNormWeight: r });
  return i.setKeys(n2.map(Ch)), i.setSources(e), i.create(), i;
}
function cx(n2, { getFn: e = H.getFn, fieldNormWeight: t = H.fieldNormWeight } = {}) {
  const { keys: r, records: i } = n2, s = new ka({ getFn: e, fieldNormWeight: t });
  return s.setKeys(r), s.setIndexRecords(i), s;
}
function Bi(n2, {
  errors: e = 0,
  currentLocation: t = 0,
  expectedLocation: r = 0,
  distance: i = H.distance,
  ignoreLocation: s = H.ignoreLocation
} = {}) {
  const o = e / n2.length;
  if (s)
    return o;
  const l = Math.abs(r - t);
  return i ? o + l / i : l ? 1 : o;
}
function ux(n2 = [], e = H.minMatchCharLength) {
  let t = [], r = -1, i = -1, s = 0;
  for (let o = n2.length; s < o; s += 1) {
    let l = n2[s];
    l && r === -1 ? r = s : !l && r !== -1 && (i = s - 1, i - r + 1 >= e && t.push([r, i]), r = -1);
  }
  return n2[s - 1] && s - r >= e && t.push([r, s - 1]), t;
}
const gn = 32;
function dx(n2, e, t, {
  location: r = H.location,
  distance: i = H.distance,
  threshold: s = H.threshold,
  findAllMatches: o = H.findAllMatches,
  minMatchCharLength: l = H.minMatchCharLength,
  includeMatches: a = H.includeMatches,
  ignoreLocation: c = H.ignoreLocation
} = {}) {
  if (e.length > gn)
    throw new Error(QC(gn));
  const u = e.length, d = n2.length, f = Math.max(0, Math.min(r, d));
  let h2 = s, p2 = f;
  const m = l > 1 || a, g = m ? Array(d) : [];
  let y;
  for (; (y = n2.indexOf(e, p2)) > -1; ) {
    let T = Bi(e, {
      currentLocation: y,
      expectedLocation: f,
      distance: i,
      ignoreLocation: c
    });
    if (h2 = Math.min(T, h2), p2 = y + u, m) {
      let M = 0;
      for (; M < u; )
        g[y + M] = 1, M += 1;
    }
  }
  p2 = -1;
  let w = [], C = 1, b = u + d;
  const S = 1 << u - 1;
  for (let T = 0; T < u; T += 1) {
    let M = 0, I = b;
    for (; M < I; )
      Bi(e, {
        errors: T,
        currentLocation: f + I,
        expectedLocation: f,
        distance: i,
        ignoreLocation: c
      }) <= h2 ? M = I : b = I, I = Math.floor((b - M) / 2 + M);
    b = I;
    let N = Math.max(1, f - I + 1), j = o ? d : Math.min(f + I, d) + u, K = Array(j + 2);
    K[j + 1] = (1 << T) - 1;
    for (let J = j; J >= N; J -= 1) {
      let Z = J - 1, G = t[n2.charAt(Z)];
      if (m && (g[Z] = +!!G), K[J] = (K[J + 1] << 1 | 1) & G, T && (K[J] |= (w[J + 1] | w[J]) << 1 | 1 | w[J + 1]), K[J] & S && (C = Bi(e, {
        errors: T,
        currentLocation: Z,
        expectedLocation: f,
        distance: i,
        ignoreLocation: c
      }), C <= h2)) {
        if (h2 = C, p2 = Z, p2 <= f)
          break;
        N = Math.max(1, 2 * f - p2);
      }
    }
    if (Bi(e, {
      errors: T + 1,
      currentLocation: f,
      expectedLocation: f,
      distance: i,
      ignoreLocation: c
    }) > h2)
      break;
    w = K;
  }
  const k = {
    isMatch: p2 >= 0,
    // Count exact matches (those with a score of 0) to be "almost" exact
    score: Math.max(1e-3, C)
  };
  if (m) {
    const T = ux(g, l);
    T.length ? a && (k.indices = T) : k.isMatch = false;
  }
  return k;
}
function fx(n2) {
  let e = {};
  for (let t = 0, r = n2.length; t < r; t += 1) {
    const i = n2.charAt(t);
    e[i] = (e[i] || 0) | 1 << r - t - 1;
  }
  return e;
}
const Bs = String.prototype.normalize ? (n2) => n2.normalize("NFD").replace(/[\u0300-\u036F\u0483-\u0489\u0591-\u05BD\u05BF\u05C1\u05C2\u05C4\u05C5\u05C7\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06DC\u06DF-\u06E4\u06E7\u06E8\u06EA-\u06ED\u0711\u0730-\u074A\u07A6-\u07B0\u07EB-\u07F3\u07FD\u0816-\u0819\u081B-\u0823\u0825-\u0827\u0829-\u082D\u0859-\u085B\u08D3-\u08E1\u08E3-\u0903\u093A-\u093C\u093E-\u094F\u0951-\u0957\u0962\u0963\u0981-\u0983\u09BC\u09BE-\u09C4\u09C7\u09C8\u09CB-\u09CD\u09D7\u09E2\u09E3\u09FE\u0A01-\u0A03\u0A3C\u0A3E-\u0A42\u0A47\u0A48\u0A4B-\u0A4D\u0A51\u0A70\u0A71\u0A75\u0A81-\u0A83\u0ABC\u0ABE-\u0AC5\u0AC7-\u0AC9\u0ACB-\u0ACD\u0AE2\u0AE3\u0AFA-\u0AFF\u0B01-\u0B03\u0B3C\u0B3E-\u0B44\u0B47\u0B48\u0B4B-\u0B4D\u0B56\u0B57\u0B62\u0B63\u0B82\u0BBE-\u0BC2\u0BC6-\u0BC8\u0BCA-\u0BCD\u0BD7\u0C00-\u0C04\u0C3E-\u0C44\u0C46-\u0C48\u0C4A-\u0C4D\u0C55\u0C56\u0C62\u0C63\u0C81-\u0C83\u0CBC\u0CBE-\u0CC4\u0CC6-\u0CC8\u0CCA-\u0CCD\u0CD5\u0CD6\u0CE2\u0CE3\u0D00-\u0D03\u0D3B\u0D3C\u0D3E-\u0D44\u0D46-\u0D48\u0D4A-\u0D4D\u0D57\u0D62\u0D63\u0D82\u0D83\u0DCA\u0DCF-\u0DD4\u0DD6\u0DD8-\u0DDF\u0DF2\u0DF3\u0E31\u0E34-\u0E3A\u0E47-\u0E4E\u0EB1\u0EB4-\u0EB9\u0EBB\u0EBC\u0EC8-\u0ECD\u0F18\u0F19\u0F35\u0F37\u0F39\u0F3E\u0F3F\u0F71-\u0F84\u0F86\u0F87\u0F8D-\u0F97\u0F99-\u0FBC\u0FC6\u102B-\u103E\u1056-\u1059\u105E-\u1060\u1062-\u1064\u1067-\u106D\u1071-\u1074\u1082-\u108D\u108F\u109A-\u109D\u135D-\u135F\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17B4-\u17D3\u17DD\u180B-\u180D\u1885\u1886\u18A9\u1920-\u192B\u1930-\u193B\u1A17-\u1A1B\u1A55-\u1A5E\u1A60-\u1A7C\u1A7F\u1AB0-\u1ABE\u1B00-\u1B04\u1B34-\u1B44\u1B6B-\u1B73\u1B80-\u1B82\u1BA1-\u1BAD\u1BE6-\u1BF3\u1C24-\u1C37\u1CD0-\u1CD2\u1CD4-\u1CE8\u1CED\u1CF2-\u1CF4\u1CF7-\u1CF9\u1DC0-\u1DF9\u1DFB-\u1DFF\u20D0-\u20F0\u2CEF-\u2CF1\u2D7F\u2DE0-\u2DFF\u302A-\u302F\u3099\u309A\uA66F-\uA672\uA674-\uA67D\uA69E\uA69F\uA6F0\uA6F1\uA802\uA806\uA80B\uA823-\uA827\uA880\uA881\uA8B4-\uA8C5\uA8E0-\uA8F1\uA8FF\uA926-\uA92D\uA947-\uA953\uA980-\uA983\uA9B3-\uA9C0\uA9E5\uAA29-\uAA36\uAA43\uAA4C\uAA4D\uAA7B-\uAA7D\uAAB0\uAAB2-\uAAB4\uAAB7\uAAB8\uAABE\uAABF\uAAC1\uAAEB-\uAAEF\uAAF5\uAAF6\uABE3-\uABEA\uABEC\uABED\uFB1E\uFE00-\uFE0F\uFE20-\uFE2F]/g, "") : (n2) => n2;
class Sh {
  constructor(e, {
    location: t = H.location,
    threshold: r = H.threshold,
    distance: i = H.distance,
    includeMatches: s = H.includeMatches,
    findAllMatches: o = H.findAllMatches,
    minMatchCharLength: l = H.minMatchCharLength,
    isCaseSensitive: a = H.isCaseSensitive,
    ignoreDiacritics: c = H.ignoreDiacritics,
    ignoreLocation: u = H.ignoreLocation
  } = {}) {
    if (this.options = {
      location: t,
      threshold: r,
      distance: i,
      includeMatches: s,
      findAllMatches: o,
      minMatchCharLength: l,
      isCaseSensitive: a,
      ignoreDiacritics: c,
      ignoreLocation: u
    }, e = a ? e : e.toLowerCase(), e = c ? Bs(e) : e, this.pattern = e, this.chunks = [], !this.pattern.length)
      return;
    const d = (h2, p2) => {
      this.chunks.push({
        pattern: h2,
        alphabet: fx(h2),
        startIndex: p2
      });
    }, f = this.pattern.length;
    if (f > gn) {
      let h2 = 0;
      const p2 = f % gn, m = f - p2;
      for (; h2 < m; )
        d(this.pattern.substr(h2, gn), h2), h2 += gn;
      if (p2) {
        const g = f - gn;
        d(this.pattern.substr(g), g);
      }
    } else
      d(this.pattern, 0);
  }
  searchIn(e) {
    const { isCaseSensitive: t, ignoreDiacritics: r, includeMatches: i } = this.options;
    if (e = t ? e : e.toLowerCase(), e = r ? Bs(e) : e, this.pattern === e) {
      let m = {
        isMatch: true,
        score: 0
      };
      return i && (m.indices = [[0, e.length - 1]]), m;
    }
    const {
      location: s,
      distance: o,
      threshold: l,
      findAllMatches: a,
      minMatchCharLength: c,
      ignoreLocation: u
    } = this.options;
    let d = [], f = 0, h2 = false;
    this.chunks.forEach(({ pattern: m, alphabet: g, startIndex: y }) => {
      const { isMatch: w, score: C, indices: b } = dx(e, m, g, {
        location: s + y,
        distance: o,
        threshold: l,
        findAllMatches: a,
        minMatchCharLength: c,
        includeMatches: i,
        ignoreLocation: u
      });
      w && (h2 = true), f += C, w && b && (d = [...d, ...b]);
    });
    let p2 = {
      isMatch: h2,
      score: h2 ? f / this.chunks.length : 1
    };
    return h2 && i && (p2.indices = d), p2;
  }
}
class cn {
  constructor(e) {
    this.pattern = e;
  }
  static isMultiMatch(e) {
    return Au(e, this.multiRegex);
  }
  static isSingleMatch(e) {
    return Au(e, this.singleRegex);
  }
  search() {
  }
}
function Au(n2, e) {
  const t = n2.match(e);
  return t ? t[1] : null;
}
class hx extends cn {
  constructor(e) {
    super(e);
  }
  static get type() {
    return "exact";
  }
  static get multiRegex() {
    return /^="(.*)"$/;
  }
  static get singleRegex() {
    return /^=(.*)$/;
  }
  search(e) {
    const t = e === this.pattern;
    return {
      isMatch: t,
      score: t ? 0 : 1,
      indices: [0, this.pattern.length - 1]
    };
  }
}
class px extends cn {
  constructor(e) {
    super(e);
  }
  static get type() {
    return "inverse-exact";
  }
  static get multiRegex() {
    return /^!"(.*)"$/;
  }
  static get singleRegex() {
    return /^!(.*)$/;
  }
  search(e) {
    const r = e.indexOf(this.pattern) === -1;
    return {
      isMatch: r,
      score: r ? 0 : 1,
      indices: [0, e.length - 1]
    };
  }
}
class mx extends cn {
  constructor(e) {
    super(e);
  }
  static get type() {
    return "prefix-exact";
  }
  static get multiRegex() {
    return /^\^"(.*)"$/;
  }
  static get singleRegex() {
    return /^\^(.*)$/;
  }
  search(e) {
    const t = e.startsWith(this.pattern);
    return {
      isMatch: t,
      score: t ? 0 : 1,
      indices: [0, this.pattern.length - 1]
    };
  }
}
class gx extends cn {
  constructor(e) {
    super(e);
  }
  static get type() {
    return "inverse-prefix-exact";
  }
  static get multiRegex() {
    return /^!\^"(.*)"$/;
  }
  static get singleRegex() {
    return /^!\^(.*)$/;
  }
  search(e) {
    const t = !e.startsWith(this.pattern);
    return {
      isMatch: t,
      score: t ? 0 : 1,
      indices: [0, e.length - 1]
    };
  }
}
class yx extends cn {
  constructor(e) {
    super(e);
  }
  static get type() {
    return "suffix-exact";
  }
  static get multiRegex() {
    return /^"(.*)"\$$/;
  }
  static get singleRegex() {
    return /^(.*)\$$/;
  }
  search(e) {
    const t = e.endsWith(this.pattern);
    return {
      isMatch: t,
      score: t ? 0 : 1,
      indices: [e.length - this.pattern.length, e.length - 1]
    };
  }
}
class bx extends cn {
  constructor(e) {
    super(e);
  }
  static get type() {
    return "inverse-suffix-exact";
  }
  static get multiRegex() {
    return /^!"(.*)"\$$/;
  }
  static get singleRegex() {
    return /^!(.*)\$$/;
  }
  search(e) {
    const t = !e.endsWith(this.pattern);
    return {
      isMatch: t,
      score: t ? 0 : 1,
      indices: [0, e.length - 1]
    };
  }
}
class Mh extends cn {
  constructor(e, {
    location: t = H.location,
    threshold: r = H.threshold,
    distance: i = H.distance,
    includeMatches: s = H.includeMatches,
    findAllMatches: o = H.findAllMatches,
    minMatchCharLength: l = H.minMatchCharLength,
    isCaseSensitive: a = H.isCaseSensitive,
    ignoreDiacritics: c = H.ignoreDiacritics,
    ignoreLocation: u = H.ignoreLocation
  } = {}) {
    super(e), this._bitapSearch = new Sh(e, {
      location: t,
      threshold: r,
      distance: i,
      includeMatches: s,
      findAllMatches: o,
      minMatchCharLength: l,
      isCaseSensitive: a,
      ignoreDiacritics: c,
      ignoreLocation: u
    });
  }
  static get type() {
    return "fuzzy";
  }
  static get multiRegex() {
    return /^"(.*)"$/;
  }
  static get singleRegex() {
    return /^(.*)$/;
  }
  search(e) {
    return this._bitapSearch.searchIn(e);
  }
}
class Ah extends cn {
  constructor(e) {
    super(e);
  }
  static get type() {
    return "include";
  }
  static get multiRegex() {
    return /^'"(.*)"$/;
  }
  static get singleRegex() {
    return /^'(.*)$/;
  }
  search(e) {
    let t = 0, r;
    const i = [], s = this.pattern.length;
    for (; (r = e.indexOf(this.pattern, t)) > -1; )
      t = r + s, i.push([r, t - 1]);
    const o = !!i.length;
    return {
      isMatch: o,
      score: o ? 0 : 1,
      indices: i
    };
  }
}
const Al = [
  hx,
  Ah,
  mx,
  gx,
  bx,
  yx,
  px,
  Mh
], Eu = Al.length, vx = / +(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/, wx = "|";
function kx(n2, e = {}) {
  return n2.split(wx).map((t) => {
    let r = t.trim().split(vx).filter((s) => s && !!s.trim()), i = [];
    for (let s = 0, o = r.length; s < o; s += 1) {
      const l = r[s];
      let a = false, c = -1;
      for (; !a && ++c < Eu; ) {
        const u = Al[c];
        let d = u.isMultiMatch(l);
        d && (i.push(new u(d, e)), a = true);
      }
      if (!a)
        for (c = -1; ++c < Eu; ) {
          const u = Al[c];
          let d = u.isSingleMatch(l);
          if (d) {
            i.push(new u(d, e));
            break;
          }
        }
    }
    return i;
  });
}
const Cx = /* @__PURE__ */ new Set([Mh.type, Ah.type]);
class xx {
  constructor(e, {
    isCaseSensitive: t = H.isCaseSensitive,
    ignoreDiacritics: r = H.ignoreDiacritics,
    includeMatches: i = H.includeMatches,
    minMatchCharLength: s = H.minMatchCharLength,
    ignoreLocation: o = H.ignoreLocation,
    findAllMatches: l = H.findAllMatches,
    location: a = H.location,
    threshold: c = H.threshold,
    distance: u = H.distance
  } = {}) {
    this.query = null, this.options = {
      isCaseSensitive: t,
      ignoreDiacritics: r,
      includeMatches: i,
      minMatchCharLength: s,
      findAllMatches: l,
      ignoreLocation: o,
      location: a,
      threshold: c,
      distance: u
    }, e = t ? e : e.toLowerCase(), e = r ? Bs(e) : e, this.pattern = e, this.query = kx(this.pattern, this.options);
  }
  static condition(e, t) {
    return t.useExtendedSearch;
  }
  searchIn(e) {
    const t = this.query;
    if (!t)
      return {
        isMatch: false,
        score: 1
      };
    const { includeMatches: r, isCaseSensitive: i, ignoreDiacritics: s } = this.options;
    e = i ? e : e.toLowerCase(), e = s ? Bs(e) : e;
    let o = 0, l = [], a = 0;
    for (let c = 0, u = t.length; c < u; c += 1) {
      const d = t[c];
      l.length = 0, o = 0;
      for (let f = 0, h2 = d.length; f < h2; f += 1) {
        const p2 = d[f], { isMatch: m, indices: g, score: y } = p2.search(e);
        if (m) {
          if (o += 1, a += y, r) {
            const w = p2.constructor.type;
            Cx.has(w) ? l = [...l, ...g] : l.push(g);
          }
        } else {
          a = 0, o = 0, l.length = 0;
          break;
        }
      }
      if (o) {
        let f = {
          isMatch: true,
          score: a / o
        };
        return r && (f.indices = l), f;
      }
    }
    return {
      isMatch: false,
      score: 1
    };
  }
}
const El = [];
function Sx(...n2) {
  El.push(...n2);
}
function Tl(n2, e) {
  for (let t = 0, r = El.length; t < r; t += 1) {
    let i = El[t];
    if (i.condition(n2, e))
      return new i(n2, e);
  }
  return new Sh(n2, e);
}
const Hs = {
  AND: "$and",
  OR: "$or"
}, Ol = {
  PATH: "$path",
  PATTERN: "$val"
}, Nl = (n2) => !!(n2[Hs.AND] || n2[Hs.OR]), Mx = (n2) => !!n2[Ol.PATH], Ax = (n2) => !Bt(n2) && wh(n2) && !Nl(n2), Tu = (n2) => ({
  [Hs.AND]: Object.keys(n2).map((e) => ({
    [e]: n2[e]
  }))
});
function Eh(n2, e, { auto: t = true } = {}) {
  const r = (i) => {
    let s = Object.keys(i);
    const o = Mx(i);
    if (!o && s.length > 1 && !Nl(i))
      return r(Tu(i));
    if (Ax(i)) {
      const a = o ? i[Ol.PATH] : s[0], c = o ? i[Ol.PATTERN] : i[a];
      if (!pt(c))
        throw new Error(XC(a));
      const u = {
        keyId: Ml(a),
        pattern: c
      };
      return t && (u.searcher = Tl(c, e)), u;
    }
    let l = {
      children: [],
      operator: s[0]
    };
    return s.forEach((a) => {
      const c = i[a];
      Bt(c) && c.forEach((u) => {
        l.children.push(r(u));
      });
    }), l;
  };
  return Nl(n2) || (n2 = Tu(n2)), r(n2);
}
function Ex(n2, { ignoreFieldNorm: e = H.ignoreFieldNorm }) {
  n2.forEach((t) => {
    let r = 1;
    t.matches.forEach(({ key: i, norm: s, score: o }) => {
      const l = i ? i.weight : null;
      r *= Math.pow(
        o === 0 && l ? Number.EPSILON : o,
        (l || 1) * (e ? 1 : s)
      );
    }), t.score = r;
  });
}
function Tx(n2, e) {
  const t = n2.matches;
  e.matches = [], ze(t) && t.forEach((r) => {
    if (!ze(r.indices) || !r.indices.length)
      return;
    const { indices: i, value: s } = r;
    let o = {
      indices: i,
      value: s
    };
    r.key && (o.key = r.key.src), r.idx > -1 && (o.refIndex = r.idx), e.matches.push(o);
  });
}
function Ox(n2, e) {
  e.score = n2.score;
}
function Nx(n2, e, {
  includeMatches: t = H.includeMatches,
  includeScore: r = H.includeScore
} = {}) {
  const i = [];
  return t && i.push(Tx), r && i.push(Ox), n2.map((s) => {
    const { idx: o } = s, l = {
      item: e[o],
      refIndex: o
    };
    return i.length && i.forEach((a) => {
      a(s, l);
    }), l;
  });
}
class mr {
  constructor(e, t = {}, r) {
    this.options = { ...H, ...t }, this.options.useExtendedSearch, this._keyStore = new tx(this.options.keys), this.setCollection(e, r);
  }
  setCollection(e, t) {
    if (this._docs = e, t && !(t instanceof ka))
      throw new Error(YC);
    this._myIndex = t || xh(this.options.keys, this._docs, {
      getFn: this.options.getFn,
      fieldNormWeight: this.options.fieldNormWeight
    });
  }
  add(e) {
    ze(e) && (this._docs.push(e), this._myIndex.add(e));
  }
  remove(e = () => false) {
    const t = [];
    for (let r = 0, i = this._docs.length; r < i; r += 1) {
      const s = this._docs[r];
      e(s, r) && (this.removeAt(r), r -= 1, i -= 1, t.push(s));
    }
    return t;
  }
  removeAt(e) {
    this._docs.splice(e, 1), this._myIndex.removeAt(e);
  }
  getIndex() {
    return this._myIndex;
  }
  search(e, { limit: t = -1 } = {}) {
    const {
      includeMatches: r,
      includeScore: i,
      shouldSort: s,
      sortFn: o,
      ignoreFieldNorm: l
    } = this.options;
    let a = pt(e) ? pt(this._docs[0]) ? this._searchStringList(e) : this._searchObjectList(e) : this._searchLogical(e);
    return Ex(a, { ignoreFieldNorm: l }), s && a.sort(o), vh(t) && t > -1 && (a = a.slice(0, t)), Nx(a, this._docs, {
      includeMatches: r,
      includeScore: i
    });
  }
  _searchStringList(e) {
    const t = Tl(e, this.options), { records: r } = this._myIndex, i = [];
    return r.forEach(({ v: s, i: o, n: l }) => {
      if (!ze(s))
        return;
      const { isMatch: a, score: c, indices: u } = t.searchIn(s);
      a && i.push({
        item: s,
        idx: o,
        matches: [{ score: c, value: s, norm: l, indices: u }]
      });
    }), i;
  }
  _searchLogical(e) {
    const t = Eh(e, this.options), r = (l, a, c) => {
      if (!l.children) {
        const { keyId: d, searcher: f } = l, h2 = this._findMatches({
          key: this._keyStore.get(d),
          value: this._myIndex.getValueForItemAtKeyId(a, d),
          searcher: f
        });
        return h2 && h2.length ? [
          {
            idx: c,
            item: a,
            matches: h2
          }
        ] : [];
      }
      const u = [];
      for (let d = 0, f = l.children.length; d < f; d += 1) {
        const h2 = l.children[d], p2 = r(h2, a, c);
        if (p2.length)
          u.push(...p2);
        else if (l.operator === Hs.AND)
          return [];
      }
      return u;
    }, i = this._myIndex.records, s = {}, o = [];
    return i.forEach(({ $: l, i: a }) => {
      if (ze(l)) {
        let c = r(t, l, a);
        c.length && (s[a] || (s[a] = { idx: a, item: l, matches: [] }, o.push(s[a])), c.forEach(({ matches: u }) => {
          s[a].matches.push(...u);
        }));
      }
    }), o;
  }
  _searchObjectList(e) {
    const t = Tl(e, this.options), { keys: r, records: i } = this._myIndex, s = [];
    return i.forEach(({ $: o, i: l }) => {
      if (!ze(o))
        return;
      let a = [];
      r.forEach((c, u) => {
        a.push(
          ...this._findMatches({
            key: c,
            value: o[u],
            searcher: t
          })
        );
      }), a.length && s.push({
        idx: l,
        item: o,
        matches: a
      });
    }), s;
  }
  _findMatches({ key: e, value: t, searcher: r }) {
    if (!ze(t))
      return [];
    let i = [];
    if (Bt(t))
      t.forEach(({ v: s, i: o, n: l }) => {
        if (!ze(s))
          return;
        const { isMatch: a, score: c, indices: u } = r.searchIn(s);
        a && i.push({
          score: c,
          key: e,
          value: s,
          idx: o,
          norm: l,
          indices: u
        });
      });
    else {
      const { v: s, n: o } = t, { isMatch: l, score: a, indices: c } = r.searchIn(s);
      l && i.push({ score: a, key: e, value: s, norm: o, indices: c });
    }
    return i;
  }
}
mr.version = "7.1.0";
mr.createIndex = xh;
mr.parseIndex = cx;
mr.config = H;
mr.parseQuery = Eh;
Sx(xx);
const Dx = {
  props: {
    // List of items to display
    items: {
      type: Array,
      required: true
    },
    // Function to execute when an item is selected
    command: {
      type: Function,
      required: true
    }
  },
  data() {
    return {
      selectedIndex: 0
    };
  },
  watch: {
    items() {
      this.selectedIndex = 0;
    }
  },
  methods: {
    onKeyDown({ event: n2 }) {
      return n2.key === "ArrowUp" ? (this.upHandler(), true) : n2.key === "ArrowDown" ? (this.downHandler(), true) : n2.key === "Enter" ? (this.enterHandler(), true) : false;
    },
    upHandler() {
      this.selectedIndex = (this.selectedIndex + this.items.length - 1) % this.items.length;
    },
    downHandler() {
      this.selectedIndex = (this.selectedIndex + 1) % this.items.length;
    },
    enterHandler() {
      this.selectItem(this.selectedIndex);
    },
    selectItem(n2) {
      const e = this.items[n2];
      e && this.command({ id: e.value });
    }
  }
}, Lx = { class: "editor-suggestions--dropdown-menu" }, Rx = ["onClick"], Ix = {
  key: 1,
  class: "item"
};
function Px(n2, e, t, r, i, s) {
  return openBlock(), createElementBlock("div", Lx, [
    t.items.length ? (openBlock(true), createElementBlock(Fragment, { key: 0 }, renderList(t.items, (o, l) => (openBlock(), createElementBlock("button", {
      class: normalizeClass({ "is-selected": l === i.selectedIndex }),
      key: l,
      onClick: (a) => s.selectItem(l)
    }, toDisplayString(o.label), 11, Rx))), 128)) : (openBlock(), createElementBlock("div", Ix, " No result "))
  ]);
}
const Bx = /* @__PURE__ */ Hn(Dx, [["render", Px]]), Hx = function() {
  return {
    items({ query: n2, editor: e }) {
      const r = new mr([...e.options.suggestions], {
        keys: ["label"]
      }).search(n2);
      return n2 === "" ? e.options.suggestions : r.map((i) => i.item);
    },
    char: "/",
    allowSpaces: true,
    render: () => {
      let n2, e;
      return {
        onStart: (t) => {
          n2 = new qf(Bx, {
            // using vue 2:
            // parent: this,
            // propsData: props,
            // using vue 3:
            props: t,
            editor: t.editor
          }), t.clientRect && (e = Bn("body", {
            getReferenceClientRect: t.clientRect,
            appendTo: () => document.body,
            content: n2.element,
            showOnCreate: true,
            interactive: true,
            trigger: "manual",
            placement: "bottom-start",
            theme: "editor"
          }));
        },
        onUpdate(t) {
          n2.updateProps(t), t.clientRect && e[0].setProps({
            getReferenceClientRect: t.clientRect
          });
        },
        onKeyDown(t) {
          var r;
          return t.event.key === "Escape" ? (e[0].hide(), true) : (r = n2.ref) == null ? void 0 : r.onKeyDown(t);
        },
        onExit() {
          e[0].destroy(), n2.destroy();
        }
      };
    }
  };
}, Fx = [
  {
    label: "Noir",
    value: "#1e1e1e"
  },
  {
    label: "Grey 1",
    value: "#b4b4b4"
  },
  {
    label: "Grey 2",
    value: "#757575"
  },
  {
    label: "Brown",
    value: "#8b511f"
  },
  {
    label: "Orange 1",
    value: "#ff6900"
  },
  {
    label: "Orange 2",
    value: "#cc4b00"
  },
  {
    label: "Green 1",
    value: "#98d432"
  },
  {
    label: "Green 2",
    value: "#008a35"
  },
  {
    label: "Blue 1",
    value: "#0073e5"
  },
  {
    label: "Blue 2",
    value: "#0644ae"
  },
  {
    label: "Red 1",
    value: "#eb0000"
  },
  {
    label: "Red 2",
    value: "#c00016"
  },
  {
    label: "Purple 1",
    value: "#d292ef"
  },
  {
    label: "Purple 2",
    value: "#9600c7"
  }
], zx = {
  name: "MediaLibrary",
  props: {
    files: {
      type: Array
    },
    deleteUrl: {
      type: String
    }
  },
  components: {
    Modal: Jf
  },
  mixins: [no],
  inject: ["locale"],
  emits: ["insertImage", "closeMediaLibrary"],
  data() {
    return {
      medias: [],
      current_media: {},
      search: "",
      locale: this.locale
    };
  },
  created() {
    this.medias = this.files, this.current_media = this.medias[0];
  },
  methods: {
    closeModal() {
    },
    deleteFile() {
      confirm(this.translate("mediaLibrary.actions.delete.confirm", this.locale)) && fetch(this.deleteUrl + "&file=" + this.current_media.name, {
        method: "DELETE"
      }).then((n2) => n2.json()).then((n2) => {
        n2.success && (this.medias = this.medias.filter((e) => e.name !== this.current_media.name), this.current_media = this.medias[0]);
      }).catch((n2) => console.error(n2));
    },
    readableFileSize(n2) {
      const t = n2 ?? 0;
      if (!t)
        return "0 kb";
      const r = t / 1024;
      return r > 1024 ? `${(r / 1024).toFixed(2)} mb` : `${r.toFixed(2)} kb`;
    }
  },
  computed: {
    computedMedias: function(n2) {
      return this.search ? this.medias.filter((e) => e.name.toLowerCase().includes(this.search.toLowerCase())) : this.medias;
    }
  }
}, Vx = { class: "media-library--modal-head" }, $x = { class: "media-library--modal-head-title" }, _x = { style: { "margin-top": "0" } }, jx = ["title"], Wx = { class: "media-library--modal-content" }, Ux = { class: "media-library--file-explorer" }, Kx = { class: "media-library--file-explorer-filters" }, qx = { style: { "margin-bottom": "0", "margin-top": "0" } }, Jx = ["placeholder"], Gx = { class: "media-library--file-explorer-files" }, Yx = ["onClick"], Xx = ["src", "alt"], Qx = { class: "media-library--file-name" }, Zx = {
  key: 0,
  class: "media-library--file-size"
}, eS = { class: "media-library--file-preview" }, tS = { class: "media-library--file-preview-image" }, nS = ["src", "alt"], rS = { class: "media-library--informations" }, iS = { class: "media-library--file-name" }, sS = {
  key: 0,
  class: "media-library--file-size"
}, oS = { key: 1 }, lS = { style: { "margin-bottom": "0" } }, aS = { class: "media-library--attributes" }, cS = { class: "media-library--attribute" }, uS = { class: "media-library--attribute-name" }, dS = { class: "media-library--actions" };
function fS(n2, e, t, r, i, s) {
  const o = resolveComponent("modal");
  return openBlock(), createBlock(o, {
    class: "media-library",
    name: "edit",
    resizable: true,
    draggable: true,
    "click-to-close": false,
    onClosed: s.closeModal,
    width: "70em",
    height: "90vh"
  }, {
    default: withCtx(() => [
      createBaseVNode("div", Vx, [
        createBaseVNode("div", $x, [
          createBaseVNode("h1", _x, toDisplayString(n2.translate("mediaLibrary.title", this.locale)), 1),
          createBaseVNode("span", {
            title: n2.translate("modal.close", this.locale),
            class: "material-symbols-outlined",
            onClick: e[0] || (e[0] = (l) => n2.$emit("closeMediaLibrary"))
          }, "close", 8, jx)
        ])
      ]),
      createBaseVNode("div", Wx, [
        createBaseVNode("div", Ux, [
          createBaseVNode("div", Kx, [
            createBaseVNode("h3", qx, [
              s.computedMedias.length > 1 ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
                createTextVNode(toDisplayString(s.computedMedias.length + " " + n2.translate("mediaLibrary.files", this.locale)), 1)
              ], 64)) : (openBlock(), createElementBlock(Fragment, { key: 1 }, [
                createTextVNode(toDisplayString(s.computedMedias.length + " " + n2.translate("mediaLibrary.file", this.locale)), 1)
              ], 64))
            ]),
            withDirectives(createBaseVNode("input", {
              type: "text",
              class: "media-library--searchbar",
              "onUpdate:modelValue": e[1] || (e[1] = (l) => i.search = l),
              placeholder: n2.translate("mediaLibrary.search.placeholder", this.locale)
            }, null, 8, Jx), [
              [vModelText, i.search]
            ])
          ]),
          createBaseVNode("div", Gx, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(s.computedMedias, (l) => (openBlock(), createElementBlock("div", {
              class: normalizeClass(["media-library--file", i.current_media.name === l.name ? "media-library--selected" : ""]),
              key: l.name,
              onClick: (a) => i.current_media = l
            }, [
              createBaseVNode("div", null, [
                createBaseVNode("img", {
                  src: l.url,
                  alt: l.name
                }, null, 8, Xx),
                createBaseVNode("span", Qx, toDisplayString(l.name), 1)
              ]),
              l.size ? (openBlock(), createElementBlock("span", Zx, toDisplayString(s.readableFileSize(l.size)), 1)) : createCommentVNode("", true)
            ], 10, Yx))), 128))
          ])
        ]),
        createBaseVNode("div", eS, [
          createBaseVNode("div", tS, [
            createBaseVNode("img", {
              src: i.current_media.url,
              alt: i.current_media.name
            }, null, 8, nS)
          ]),
          createBaseVNode("div", rS, [
            createBaseVNode("h2", iS, toDisplayString(i.current_media.name), 1),
            i.current_media.size ? (openBlock(), createElementBlock("span", sS, toDisplayString(s.readableFileSize(i.current_media.size)), 1)) : createCommentVNode("", true),
            i.current_media.attributes ? (openBlock(), createElementBlock("div", oS, [
              createBaseVNode("h3", lS, toDisplayString(n2.translate("mediaLibrary.attributes.title", this.locale)), 1),
              e[5] || (e[5] = createBaseVNode("hr", null, null, -1)),
              createBaseVNode("div", aS, [
                (openBlock(true), createElementBlock(Fragment, null, renderList(i.current_media.attributes, (l) => (openBlock(), createElementBlock("div", null, [
                  createBaseVNode("div", cS, [
                    createBaseVNode("span", uS, toDisplayString(n2.translate("mediaLibrary.attributes." + l.name, this.locale)), 1),
                    createBaseVNode("span", null, toDisplayString(l.value), 1)
                  ]),
                  e[4] || (e[4] = createBaseVNode("hr", null, null, -1))
                ]))), 256))
              ])
            ])) : createCommentVNode("", true),
            createBaseVNode("div", dS, [
              t.deleteUrl ? (openBlock(), createElementBlock("button", {
                key: 0,
                type: "button",
                onClick: e[2] || (e[2] = (...l) => s.deleteFile && s.deleteFile(...l))
              }, toDisplayString(n2.translate("mediaLibrary.actions.delete.title", this.locale)), 1)) : createCommentVNode("", true),
              createBaseVNode("button", {
                type: "button",
                class: "media-library--actions-insert",
                onClick: e[3] || (e[3] = (l) => {
                  n2.$emit("insertImage", i.current_media.url), n2.$emit("closeMediaLibrary");
                })
              }, toDisplayString(n2.translate("mediaLibrary.actions.insert", this.locale)), 1)
            ])
          ])
        ])
      ])
    ]),
    _: 1
  }, 8, ["onClosed"]);
}
const hS = /* @__PURE__ */ Hn(zx, [["render", fS]]), pS = {
  components: {
    NodeViewWrapper: k1,
    NodeViewContent: w1
  },
  props: {
    editor: Object,
    node: Object,
    updateAttributes: Function
  },
  mixins: [no],
  inject: ["locale"],
  data() {
    var n2;
    return {
      selectedType: ((n2 = this.node) == null ? void 0 : n2.attrs.type) || "info",
      icons: [
        { value: "info", label: this.translate("toolbar.panel.type.info") },
        { value: "warning", label: this.translate("toolbar.panel.type.warning") },
        { value: "error", label: this.translate("toolbar.panel.type.error") }
      ]
    };
  },
  computed: {
    icon() {
      switch (this.selectedType) {
        case "warning":
          return "warning";
        case "error":
          return "error";
        default:
          return "info";
      }
    },
    iconColor() {
      switch (this.selectedType) {
        case "warning":
          return "#b38405";
        case "error":
          return "#a60e15";
        default:
          return "#525b85";
      }
    },
    isActive() {
      var i;
      const { state: n2 } = this.editor, { from: e, to: t } = n2.selection, r = (i = this.getPos) == null ? void 0 : i.call(this);
      return typeof r != "number" ? false : e >= r && t <= r + this.node.nodeSize;
    }
  },
  watch: {
    selectedType(n2) {
      this.node && n2 !== this.node.attrs.type && this.updateAttributes({ type: n2 });
    }
  },
  methods: {
    /*updateType(event) {
      if (!this.node) return;
      this.selectedType = value;
    }*/
  }
}, mS = {
  key: 0,
  class: "info-panel__actions"
}, gS = { value: "info" }, yS = { value: "warning" }, bS = { value: "error" }, vS = { class: "info-panel--block" };
function wS(n2, e, t, r, i, s) {
  const o = resolveComponent("NodeViewContent"), l = resolveComponent("NodeViewWrapper");
  return openBlock(), createBlock(l, {
    class: normalizeClass(`info-panel info-panel--${i.selectedType}`)
  }, {
    default: withCtx(() => [
      s.isActive ? (openBlock(), createElementBlock("div", mS, [
        withDirectives(createBaseVNode("select", {
          "onUpdate:modelValue": e[0] || (e[0] = (a) => i.selectedType = a),
          class: "info-panel__select"
        }, [
          createBaseVNode("option", gS, toDisplayString(n2.translate("toolbar.panel.type.info", this.locale)), 1),
          createBaseVNode("option", yS, toDisplayString(n2.translate("toolbar.panel.type.warning", this.locale)), 1),
          createBaseVNode("option", bS, toDisplayString(n2.translate("toolbar.panel.type.error", this.locale)), 1)
        ], 512), [
          [vModelSelect, i.selectedType]
        ])
      ])) : createCommentVNode("", true),
      createBaseVNode("div", vS, [
        createBaseVNode("span", {
          class: "material-symbols-outlined",
          style: normalizeStyle({ color: s.iconColor })
        }, toDisplayString(s.icon), 5),
        createVNode(o, { class: "info-panel__content" })
      ])
    ]),
    _: 1
  }, 8, ["class"]);
}
const kS = /* @__PURE__ */ Hn(pS, [["render", wS]]), CS = ce.create({
  name: "panel",
  group: "block",
  content: "block+",
  selectable: true,
  defining: true,
  isolating: true,
  addAttributes() {
    return {
      type: {
        default: "info",
        parseHTML: (n2) => n2.getAttribute("data-type") || "info",
        renderHTML: (n2) => ({ "data-type": n2.type })
      },
      draggable: {
        default: false
      }
    };
  },
  parseHTML() {
    return [
      {
        tag: "div[data-plugin='panel']",
        contentElement: "div"
      }
    ];
  },
  renderHTML({ node: n2, HTMLAttributes: e }) {
    return [
      "div",
      Q({ "data-plugin": "panel", "data-type": n2.attrs.type }),
      ["span", { class: "material-symbols-outlined" }, n2.attrs.type],
      ["div", 0]
    ];
  },
  addNodeView() {
    return x1(kS);
  },
  addKeyboardShortcuts() {
    return {
      Enter: ({ editor: n2 }) => {
        const { state: e, dispatch: t } = n2, { selection: r } = e, { $from: i, $to: s } = r, o = i.node(-1), l = (o == null ? void 0 : o.type.name) === "panel", a = s.parentOffset === s.parent.content.size;
        if (l && a) {
          const c = i.after(i.depth - 1);
          return t(
            e.tr.insert(c, e.schema.nodes.paragraph.create()).scrollIntoView()
          ), true;
        }
        return false;
      }
    };
  }
}), Ou = [
  "bold",
  "italic",
  "strike",
  "underline",
  "h1",
  "h2",
  "h3",
  "link",
  //"hr",
  "codeblock",
  "image",
  "ul",
  "ol",
  "left",
  "center",
  "right",
  "justify",
  "blockquote",
  "history",
  "table",
  "color",
  "fontFamily",
  "fontSize",
  "highlight",
  "youtube",
  "panel"
], xS = {
  name: "TipTapEditor",
  components: {
    MediaLibrary: hS,
    Toolbar: sw,
    EditorContent: v1
  },
  mixins: [no],
  props: {
    modelValue: {
      type: String,
      default: ""
    },
    // Locale language for the editor (en, fr)
    locale: {
      type: String,
      default: "en",
      required: true,
      validator: (n2) => [
        "en",
        "fr"
      ].includes(n2)
    },
    // Output format for the editor (html, json)
    outputFormat: {
      type: String,
      default: "html",
      validator: (n2) => [
        "html",
        "json"
      ].includes(n2)
    },
    // Upload URL for images
    uploadUrl: {
      type: String,
      default: ""
    },
    deleteMediaUrl: {
      type: String,
      default: ""
    },
    // Suggestions for the mention plugin
    suggestions: {
      type: Array,
      required: false,
      default: () => []
    },
    // Class for the mention suggestions
    suggestionsClass: {
      type: String,
      default: "mention"
    },
    // Preset for the toolbar (basic, full or custom). If custom, you need to provide the plugins
    preset: {
      type: String,
      default: "basic",
      required: true,
      validator: (n2) => [
        "basic",
        "full",
        "custom"
      ].includes(n2)
    },
    // Plugins for the toolbar
    plugins: {
      type: Array,
      required: false,
      default: () => [],
      validator: (n2) => n2.every(
        (e) => typeof e == "string" && Ou.includes(e)
      )
    },
    // Placeholder for the editor
    placeholder: {
      type: String,
      required: false,
      default() {
        return "placeholder.default";
      }
    },
    // Palette colors for the editor
    palette: {
      type: Array,
      required: false,
      default: () => Fx,
      validator: (n2) => n2.every(
        (e) => typeof e == "object"
      )
    },
    // Font families for the editor
    fontFamilies: {
      type: Array,
      required: false,
      default: () => [
        "Arial",
        "Calibri",
        "Helvetica",
        "Times New Roman",
        "Comic Sans MS",
        "Caveat"
      ]
    },
    mediaFiles: {
      type: Array,
      required: false,
      default: () => []
    },
    wrapperClasses: {
      type: Array,
      default: () => ["editor-wrapper"]
    },
    // Class for the toolbar
    toolbarClasses: {
      type: Array
    },
    // Class for the editor content
    editorContentClasses: {
      type: Array
    },
    editorContentHeight: {
      type: String,
      default: "auto"
    }
  },
  emits: ["update:modelValue", "uploadedImage"],
  data() {
    return {
      editor: void 0,
      extensions: [],
      pluginsDisplayed: [],
      displayMediaLibrary: false,
      showMediaLibrary: false
    };
  },
  provide() {
    return {
      locale: this.$props.locale
    };
  },
  watch: {
    modelValue(n2) {
      var e = this.editor.getHTML() === n2;
      this.$props.outputFormat === "json" && (e = JSON.stringify(this.editor.getJSON()) === JSON.stringify(n2)), !e && this.editor.commands.setContent(n2, false);
    },
    // Extensions
    suggestions(n2) {
      this.editor && this.editor.setOptions({ suggestions: n2 });
    }
  },
  mounted() {
    this.$props.mediaFiles.length > 0 && (this.displayMediaLibrary = true), this.getPluginsDisplayed(), this.getEditorExtensions(), this.editor = new Kf({
      extensions: this.extensions,
      content: this.modelValue,
      suggestions: this.suggestions,
      onUpdate: () => {
        this.$props.outputFormat === "html" ? this.$emit("update:modelValue", this.editor.getHTML()) : this.$emit("update:modelValue", this.editor.getJSON());
      },
      editorProps: {
        handleDrop: (n2, e, t, r) => this.dropEventHandler(n2, e, t, r)
      }
    });
  },
  beforeUnmount() {
    this.editor.destroy();
  },
  methods: {
    // Get the plugins displayed in the toolbar
    getPluginsDisplayed() {
      this.preset === "full" ? this.pluginsDisplayed = Ou : this.preset === "custom" ? this.pluginsDisplayed = this.plugins : this.pluginsDisplayed = [
        "bold",
        "italic",
        "underline",
        "link",
        "history"
      ];
    },
    // Get the extensions for the editor
    getEditorExtensions() {
      this.extensions = [
        ow,
        cw,
        lw,
        aw,
        Ow,
        zC,
        vC.configure({
          placeholder: this.translate(this.placeholder, this.locale)
        }),
        Aw,
        dC.configure({
          openOnClick: false,
          defaultProtocol: "https"
        }),
        pw,
        vw,
        Cw,
        xw,
        Ew.configure({
          levels: [1, 2, 3]
        }),
        SC,
        wC,
        CC,
        AC,
        BC,
        Dw.configure({
          types: ["textStyle"]
        }),
        Lw.configure({
          types: ["textStyle"]
        }),
        Pw.configure({
          multicolor: true
        }),
        Bw.configure({
          types: ["textStyle"]
        }),
        Nw.configure({
          types: ["heading", "paragraph"]
        }),
        zk.configure({
          resizable: false,
          allowTableNodeSelection: true
        }),
        _k,
        $k,
        Vk,
        hh.configure({
          allowBase64: true
        }),
        hC,
        UC.configure({
          controls: true,
          nocookie: true
        }),
        CS
      ], this.suggestions.length > 0 && (this.extensions = this.extensions.concat([
        bC.configure({
          HTMLAttributes: {
            class: this.suggestions_class
          },
          renderText({ options: n2, node: e }) {
            return "test";
          },
          renderHTML({ options: n2, node: e }) {
            return [
              "span",
              n2.HTMLAttributes,
              `${e.attrs.label ?? e.attrs.id}`
            ];
          },
          suggestion: Hx(this.suggestions)
        })
      ]));
    },
    // Upload the image to the server
    async uploadImage(n2) {
      return new Promise((e, t) => {
        const r = new FormData();
        r.append("file", n2), fetch(this.$props.uploadUrl, {
          method: "POST",
          body: r
        }).then((i) => i.json()).then((i) => {
          this.$emit("uploadedImage", i), e(i);
        }).catch((i) => {
          console.error("There was an error uploading the image", i), t(i);
        });
      });
    },
    // Handle the drop event
    dropEventHandler(n2, e, t, r) {
      if (!r && e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
        let i = e.dataTransfer.files[0], s = (i.size / 1024 / 1024).toFixed(4);
        return (i.type === "image/jpeg" || i.type === "image/png") && s < 10 ? this.uploadImage(i).then((o) => {
          const { schema: l } = n2.state, a = n2.posAtCoords({ left: e.clientX, top: e.clientY }), c = l.nodes.image.create({ src: o.url }), u = n2.state.tr.insert(a.pos, c);
          return n2.dispatch(u);
        }) : window.alert("Images need to be in jpg or png format and less than 10mb in size."), true;
      }
      return false;
    },
    // Handle the paste event
    pasteEventHandler(n2) {
      if (n2.clipboardData.files.length > 0)
        for (var e = 0; e < n2.clipboardData.files.length; e++)
          n2.clipboardData.files[e].type.includes("image") && this.uploadImage(n2.clipboardData.files[e]).then((t) => {
            this.editor.chain().focus().setImage({ src: t.url }).run();
          });
    },
    importImage(n2) {
      if (n2.target.files.length > 0)
        for (var e = 0; e < n2.target.files.length; e++)
          n2.target.files[e].type.includes("image") && this.uploadImage(n2.target.files[e]).then((t) => {
            this.insertImage(t.url);
          });
    },
    insertImage(n2) {
      this.editor.chain().focus().setImage({ src: n2 }).run();
    }
  }
};
function SS(n2, e, t, r, i, s) {
  const o = resolveComponent("toolbar"), l = resolveComponent("editor-content"), a = resolveComponent("media-library");
  return this.editor ? (openBlock(), createElementBlock("div", {
    key: 0,
    class: normalizeClass(t.wrapperClasses)
  }, [
    createVNode(o, {
      onImportImage: s.importImage,
      onShowMediaLibrary: e[0] || (e[0] = (c) => i.showMediaLibrary = true),
      "editor-prop": this.editor,
      extensions: i.pluginsDisplayed,
      "display-media-library": i.displayMediaLibrary,
      toolbar_classes: t.toolbarClasses,
      palette: t.palette,
      font_families: t.fontFamilies
    }, null, 8, ["onImportImage", "editor-prop", "extensions", "display-media-library", "toolbar_classes", "palette", "font_families"]),
    createBaseVNode("div", {
      class: normalizeClass(["editor-content", t.editorContentClasses]),
      style: normalizeStyle({ height: this.editorContentHeight })
    }, [
      createVNode(l, {
        onPaste: s.pasteEventHandler,
        editor: i.editor,
        style: { height: "100%" }
      }, null, 8, ["onPaste", "editor"])
    ], 6),
    i.showMediaLibrary ? (openBlock(), createBlock(a, {
      key: 0,
      files: t.mediaFiles,
      "delete-url": t.deleteMediaUrl,
      onCloseMediaLibrary: e[1] || (e[1] = (c) => i.showMediaLibrary = false),
      onInsertImage: s.insertImage
    }, null, 8, ["files", "delete-url", "onInsertImage"])) : createCommentVNode("", true)
  ], 2)) : createCommentVNode("", true);
}
const TS = /* @__PURE__ */ Hn(xS, [["render", SS]]);
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main = {
  components: {
    TipTapEditor: TS
  },
  props: {
    // Textarea element linked to the editor
    textareaId: {
      type: Number,
      required: true
    },
    // Enable suggestions
    enableSuggestions: {
      type: Boolean,
      default: false
    },
    // List of suggestions
    suggestions: {
      type: Array,
      default: () => []
    },
    // List of plugins to use
    plugins: {
      type: Array,
      default: () => []
    }
  },
  data: () => ({
    ready: false,
    content: "",
    editorSuggestions: [],
    preset: "basic",
    textareaElement: null
  }),
  mounted() {
    this.textareaElement = document.getElementById(this.$props.textareaId);
    this.content = this.textareaElement.value;
    this.textareaElement.addEventListener("input", () => {
      this.content = this.textareaElement.value;
    });
    if (this.$props.plugins.length > 0) {
      this.preset = "custom";
    }
    if (this.$props.enableSuggestions) {
      if (this.$props.suggestions.length === 0) {
        this.getSuggestions();
      } else {
        this.editorSuggestions = this.$props.suggestions;
      }
    } else {
      this.ready = true;
    }
  },
  methods: {
    async getSuggestions() {
      fetch("/index.php?option=com_emundus&controller=settings&task=geteditorvariables").then((response) => response.json()).then((data) => {
        if (data.status) {
          this.editorSuggestions = data.data;
        }
        this.ready = true;
      });
    }
  },
  watch: {
    content: {
      handler: function(value) {
        this.textareaElement.value = value;
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_tip_tap_editor = resolveComponent("tip-tap-editor");
  return _ctx.ready ? (openBlock(), createBlock(_component_tip_tap_editor, {
    key: 0,
    modelValue: _ctx.content,
    "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => _ctx.content = $event),
    "upload-url": "/index.php?option=com_emundus&controller=settings&task=uploadmedia",
    "editor-content-height": "30em",
    class: normalizeClass("tw-mt-1"),
    locale: "fr",
    preset: _ctx.preset,
    plugins: $props.plugins,
    suggestions: _ctx.editorSuggestions
  }, null, 8, ["modelValue", "preset", "plugins", "suggestions"])) : createCommentVNode("", true);
}
const App = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
let editors = document.querySelectorAll(".tiptap-editor");
editors.forEach((editor) => {
  let app = null;
  app = createApp(App, {
    textareaId: editor.attributes.textareaId.value,
    enableSuggestions: editor.attributes.enableSuggestions.value,
    suggestions: JSON.parse(editor.attributes.suggestions.value),
    plugins: JSON.parse(editor.attributes.plugins.value)
  });
  app.mount("#" + editor.id);
});
