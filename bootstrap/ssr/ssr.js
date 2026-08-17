import { A as tryOnScopeDispose, C as isDef, D as toArray, E as reactivePick, M as useTimeoutFn, N as watchImmediate, O as toRef$1, P as watchPausable, S as isClient, T as pxValue, _ as omit, a as _sfc_main$6, b as createSharedComposable, c as useForwardProps, d as Slot, f as useForwardProps$1, j as useTimeout, k as tryOnMounted, l as useComponentProps, m as createContext, o as _sfc_main$7, p as renderSlotFragments, s as tv, t as _sfc_main$8, u as Primitive, w as isObject, x as injectLocal, y as useAppConfig } from "./assets/Button-BsqkFqP1.js";
import { n as useLocale, t as localeContextInjectionKey } from "./assets/useLocale-B1UhdR4w.js";
import { createInertiaApp } from "@inertiajs/vue3";
import createServer from "@inertiajs/vue3/server";
import { renderToString } from "@vue/server-renderer";
import { Fragment, Teleport, computed, createBlock, createCommentVNode, createElementBlock, createSSRApp, createTextVNode, createVNode, defineComponent, getCurrentInstance, getCurrentScope, guardReactiveProps, h, hasInjectionContext, inject, isRef, markRaw, mergeProps, nextTick, normalizeProps, normalizeStyle, onActivated, onBeforeUnmount, onDeactivated, onMounted, onScopeDispose, onUnmounted, onUpdated, openBlock, provide, reactive, ref, renderList, renderSlot, resolveDynamicComponent, shallowReactive, shallowReadonly, shallowRef, toDisplayString, toRef, toRefs, toValue, triggerRef, unref, useId, useSSRContext, useSlots, useTemplateRef, watch, watchEffect, withCtx, withModifiers } from "vue";
import colors from "tailwindcss/colors";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot, ssrRenderStyle, ssrRenderVNode } from "vue/server-renderer";
//#region node_modules/.pnpm/laravel-vite-plugin@3.2.0_f_6c4aec2d1b46d1e26ec8f67562bfffa2/node_modules/laravel-vite-plugin/inertia-helpers/index.js
async function resolvePageComponent(path, pages) {
	for (const p of Array.isArray(path) ? path : [path]) {
		const page = pages[p];
		if (typeof page === "undefined") continue;
		return typeof page === "function" ? page() : page;
	}
	throw new Error(`Page not found: ${path}`);
}
//#endregion
//#region vendor/tightenco/ziggy/dist/index.esm.js
function t(t, e) {
	for (var n = 0; n < e.length; n++) {
		var r = e[n];
		r.enumerable = r.enumerable || !1, r.configurable = !0, "value" in r && (r.writable = !0), Object.defineProperty(t, u(r.key), r);
	}
}
function e(e, n, r) {
	return n && t(e.prototype, n), r && t(e, r), Object.defineProperty(e, "prototype", { writable: !1 }), e;
}
function n() {
	return n = Object.assign ? Object.assign.bind() : function(t) {
		for (var e = 1; e < arguments.length; e++) {
			var n = arguments[e];
			for (var r in n) ({}).hasOwnProperty.call(n, r) && (t[r] = n[r]);
		}
		return t;
	}, n.apply(null, arguments);
}
function r(t) {
	return r = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function(t) {
		return t.__proto__ || Object.getPrototypeOf(t);
	}, r(t);
}
function o() {
	try {
		var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function() {}));
	} catch (t) {}
	return (o = function() {
		return !!t;
	})();
}
function i(t, e) {
	return i = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function(t, e) {
		return t.__proto__ = e, t;
	}, i(t, e);
}
function u(t) {
	var e = function(t) {
		if ("object" != typeof t || !t) return t;
		var e = t[Symbol.toPrimitive];
		if (void 0 !== e) {
			var n = e.call(t, "string");
			if ("object" != typeof n) return n;
			throw new TypeError("@@toPrimitive must return a primitive value.");
		}
		return String(t);
	}(t);
	return "symbol" == typeof e ? e : e + "";
}
function f(t) {
	var e = "function" == typeof Map ? /* @__PURE__ */ new Map() : void 0;
	return f = function(t) {
		if (null === t || !function(t) {
			try {
				return -1 !== Function.toString.call(t).indexOf("[native code]");
			} catch (e) {
				return "function" == typeof t;
			}
		}(t)) return t;
		if ("function" != typeof t) throw new TypeError("Super expression must either be null or a function");
		if (void 0 !== e) {
			if (e.has(t)) return e.get(t);
			e.set(t, n);
		}
		function n() {
			return function(t, e, n) {
				if (o()) return Reflect.construct.apply(null, arguments);
				var r = [null];
				r.push.apply(r, e);
				var u = new (t.bind.apply(t, r))();
				return n && i(u, n.prototype), u;
			}(t, arguments, r(this).constructor);
		}
		return n.prototype = Object.create(t.prototype, { constructor: {
			value: n,
			enumerable: !1,
			writable: !0,
			configurable: !0
		} }), i(n, t);
	}, f(t);
}
var c = String.prototype.replace;
var a = /%20/g;
var l = {
	RFC1738: function(t) {
		return c.call(t, a, "+");
	},
	RFC3986: function(t) {
		return String(t);
	}
};
var s = "RFC3986";
var p = Object.prototype.hasOwnProperty;
var y = Array.isArray;
var d = /* @__PURE__ */ new WeakMap();
var b = function(t, e) {
	return d.set(t, e), t;
};
function v(t) {
	return d.has(t);
}
var h$1 = function(t) {
	return d.get(t);
};
var m = function(t, e) {
	d.set(t, e);
};
var g = function() {
	const t = [];
	for (let e = 0; e < 256; ++e) t.push("%" + ((e < 16 ? "0" : "") + e.toString(16)).toUpperCase());
	return t;
}();
var w = function(t, e) {
	const n = e && e.plainObjects ? Object.create(null) : {};
	for (let e = 0; e < t.length; ++e) void 0 !== t[e] && (n[e] = t[e]);
	return n;
};
var j = function t(e, n, r) {
	if (!n) return e;
	if ("object" != typeof n) {
		if (y(e)) e.push(n);
		else {
			if (!e || "object" != typeof e) return [e, n];
			if (v(e)) {
				var o = h$1(e) + 1;
				e[o] = n, m(e, o);
			} else (r && (r.plainObjects || r.allowPrototypes) || !p.call(Object.prototype, n)) && (e[n] = !0);
		}
		return e;
	}
	if (!e || "object" != typeof e) {
		if (v(n)) {
			for (var i = Object.keys(n), u = r && r.plainObjects ? {
				__proto__: null,
				0: e
			} : { 0: e }, f = 0; f < i.length; f++) u[parseInt(i[f], 10) + 1] = n[i[f]];
			return b(u, h$1(n) + 1);
		}
		return [e].concat(n);
	}
	let c = e;
	return y(e) && !y(n) && (c = w(e, r)), y(e) && y(n) ? (n.forEach(function(n, o) {
		if (p.call(e, o)) {
			const i = e[o];
			i && "object" == typeof i && n && "object" == typeof n ? e[o] = t(i, n, r) : e.push(n);
		} else e[o] = n;
	}), e) : Object.keys(n).reduce(function(e, o) {
		const i = n[o];
		return e[o] = p.call(e, o) ? t(e[o], i, r) : i, e;
	}, c);
};
var O = 1024;
var E = function(t, e, n, r) {
	if (v(t)) {
		var o = h$1(t) + 1;
		return t[o] = e, m(t, o), t;
	}
	var i = [].concat(t, e);
	return i.length > n ? b(w(i, { plainObjects: r }), i.length - 1) : i;
};
var T = function(t, e) {
	if (y(t)) {
		const n = [];
		for (let r = 0; r < t.length; r += 1) n.push(e(t[r]));
		return n;
	}
	return e(t);
};
var R = Object.prototype.hasOwnProperty;
var k = {
	brackets: function(t) {
		return t + "[]";
	},
	comma: "comma",
	indices: function(t, e) {
		return t + "[" + e + "]";
	},
	repeat: function(t) {
		return t;
	}
};
var S = Array.isArray;
var I = Array.prototype.push;
var A = function(t, e) {
	I.apply(t, S(e) ? e : [e]);
};
var D = Date.prototype.toISOString;
var $ = {
	addQueryPrefix: !1,
	allowDots: !1,
	allowEmptyArrays: !1,
	arrayFormat: "indices",
	charset: "utf-8",
	charsetSentinel: !1,
	delimiter: "&",
	encode: !0,
	encodeDotInKeys: !1,
	encoder: function(t, e, n, r, o) {
		if (0 === t.length) return t;
		let i = t;
		if ("symbol" == typeof t ? i = Symbol.prototype.toString.call(t) : "string" != typeof t && (i = String(t)), "iso-8859-1" === n) return escape(i).replace(/%u[0-9a-f]{4}/gi, function(t) {
			return "%26%23" + parseInt(t.slice(2), 16) + "%3B";
		});
		let u = "";
		for (let t = 0; t < i.length; t += O) {
			const e = i.length >= O ? i.slice(t, t + O) : i, n = [];
			for (let t = 0; t < e.length; ++t) {
				let r = e.charCodeAt(t);
				45 === r || 46 === r || 95 === r || 126 === r || r >= 48 && r <= 57 || r >= 65 && r <= 90 || r >= 97 && r <= 122 || "RFC1738" === o && (40 === r || 41 === r) ? n[n.length] = e.charAt(t) : r < 128 ? n[n.length] = g[r] : r < 2048 ? n[n.length] = g[192 | r >> 6] + g[128 | 63 & r] : r < 55296 || r >= 57344 ? n[n.length] = g[224 | r >> 12] + g[128 | r >> 6 & 63] + g[128 | 63 & r] : (t += 1, r = 65536 + ((1023 & r) << 10 | 1023 & e.charCodeAt(t)), n[n.length] = g[240 | r >> 18] + g[128 | r >> 12 & 63] + g[128 | r >> 6 & 63] + g[128 | 63 & r]);
			}
			u += n.join("");
		}
		return u;
	},
	encodeValuesOnly: !1,
	format: s,
	formatter: l[s],
	indices: !1,
	serializeDate: function(t) {
		return D.call(t);
	},
	skipNulls: !1,
	strictNullHandling: !1
};
var N = {};
var _ = function(t, e, n, r, o, i, u, f, c, a, l, s, p, y, d, b, v, h) {
	let m = t, g = h, w = 0, j = !1;
	for (; void 0 !== (g = g.get(N)) && !j;) {
		const e = g.get(t);
		if (w += 1, void 0 !== e) {
			if (e === w) throw new RangeError("Cyclic object value");
			j = !0;
		}
		void 0 === g.get(N) && (w = 0);
	}
	if ("function" == typeof a ? m = a(e, m) : m instanceof Date ? m = p(m) : "comma" === n && S(m) && (m = T(m, function(t) {
		return t instanceof Date ? p(t) : t;
	})), null === m) {
		if (i) return c && !b ? c(e, $.encoder, v, "key", y) : e;
		m = "";
	}
	if ("string" == typeof (O = m) || "number" == typeof O || "boolean" == typeof O || "symbol" == typeof O || "bigint" == typeof O || function(t) {
		return !(!t || "object" != typeof t || !(t.constructor && t.constructor.isBuffer && t.constructor.isBuffer(t)));
	}(m)) return c ? [d(b ? e : c(e, $.encoder, v, "key", y)) + "=" + d(c(m, $.encoder, v, "value", y))] : [d(e) + "=" + d(String(m))];
	var O;
	const E = [];
	if (void 0 === m) return E;
	let R;
	if ("comma" === n && S(m)) b && c && (m = T(m, c)), R = [{ value: m.length > 0 ? m.join(",") || null : void 0 }];
	else if (S(a)) R = a;
	else {
		const t = Object.keys(m);
		R = l ? t.sort(l) : t;
	}
	const k = f ? e.replace(/\./g, "%2E") : e, I = r && S(m) && 1 === m.length ? k + "[]" : k;
	if (o && S(m) && 0 === m.length) return I + "[]";
	for (let e = 0; e < R.length; ++e) {
		const g = R[e], j = "object" == typeof g && void 0 !== g.value ? g.value : m[g];
		if (u && null === j) continue;
		const O = s && f ? g.replace(/\./g, "%2E") : g, T = S(m) ? "function" == typeof n ? n(I, O) : I : I + (s ? "." + O : "[" + O + "]");
		h.set(t, w);
		const k = /* @__PURE__ */ new WeakMap();
		k.set(N, h), A(E, _(j, T, n, r, o, i, u, f, "comma" === n && b && S(m) ? null : c, a, l, s, p, y, d, b, v, k));
	}
	return E;
};
var x = Object.prototype.hasOwnProperty;
var C = Array.isArray;
var P = {
	allowDots: !1,
	allowEmptyArrays: !1,
	allowPrototypes: !1,
	allowSparse: !1,
	arrayLimit: 20,
	charset: "utf-8",
	charsetSentinel: !1,
	comma: !1,
	decodeDotInKeys: !1,
	decoder: function(t, e, n) {
		const r = t.replace(/\+/g, " ");
		if ("iso-8859-1" === n) return r.replace(/%[0-9a-f]{2}/gi, unescape);
		try {
			return decodeURIComponent(r);
		} catch (t) {
			return r;
		}
	},
	delimiter: "&",
	depth: 5,
	duplicates: "combine",
	ignoreQueryPrefix: !1,
	interpretNumericEntities: !1,
	parameterLimit: 1e3,
	parseArrays: !0,
	plainObjects: !1,
	strictNullHandling: !1
};
var Z = function(t) {
	return t.replace(/&#(\d+);/g, function(t, e) {
		return String.fromCharCode(parseInt(e, 10));
	});
};
var F = function(t, e) {
	return t && "string" == typeof t && e.comma && t.indexOf(",") > -1 ? t.split(",") : t;
};
var U = function(t, e, n, r) {
	if (!t) return;
	const o = n.allowDots ? t.replace(/\.([^.[]+)/g, "[$1]") : t, i = /(\[[^[\]]*])/g;
	let u = n.depth > 0 && /(\[[^[\]]*])/.exec(o);
	const f = u ? o.slice(0, u.index) : o, c = [];
	if (f) {
		if (!n.plainObjects && x.call(Object.prototype, f) && !n.allowPrototypes) return;
		c.push(f);
	}
	let a = 0;
	for (; n.depth > 0 && null !== (u = i.exec(o)) && a < n.depth;) {
		if (a += 1, !n.plainObjects && x.call(Object.prototype, u[1].slice(1, -1)) && !n.allowPrototypes) return;
		c.push(u[1]);
	}
	return u && c.push("[" + o.slice(u.index) + "]"), function(t, e, n, r) {
		let o = r ? e : F(e, n);
		for (let e = t.length - 1; e >= 0; --e) {
			let r;
			const i = t[e];
			if ("[]" === i && n.parseArrays) r = v(o) ? o : n.allowEmptyArrays && ("" === o || n.strictNullHandling && null === o) ? [] : E([], o, n.arrayLimit, n.plainObjects);
			else {
				r = n.plainObjects ? Object.create(null) : {};
				const t = "[" === i.charAt(0) && "]" === i.charAt(i.length - 1) ? i.slice(1, -1) : i, e = n.decodeDotInKeys ? t.replace(/%2E/g, ".") : t, u = parseInt(e, 10);
				n.parseArrays || "" !== e ? !isNaN(u) && i !== e && String(u) === e && u >= 0 && n.parseArrays && u <= n.arrayLimit ? (r = [], r[u] = o) : "__proto__" !== e && (r[e] = o) : r = { 0: o };
			}
			o = r;
		}
		return o;
	}(c, e, n, r);
};
function q(t, e) {
	const n = function(t) {
		if (!t) return P;
		if (void 0 !== t.allowEmptyArrays && "boolean" != typeof t.allowEmptyArrays) throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");
		if (void 0 !== t.decodeDotInKeys && "boolean" != typeof t.decodeDotInKeys) throw new TypeError("`decodeDotInKeys` option can only be `true` or `false`, when provided");
		if (null != t.decoder && "function" != typeof t.decoder) throw new TypeError("Decoder has to be a function.");
		if (void 0 !== t.charset && "utf-8" !== t.charset && "iso-8859-1" !== t.charset) throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");
		const e = void 0 === t.charset ? P.charset : t.charset, n = void 0 === t.duplicates ? P.duplicates : t.duplicates;
		if ("combine" !== n && "first" !== n && "last" !== n) throw new TypeError("The duplicates option must be either combine, first, or last");
		return {
			allowDots: void 0 === t.allowDots ? !0 === t.decodeDotInKeys || P.allowDots : !!t.allowDots,
			allowEmptyArrays: "boolean" == typeof t.allowEmptyArrays ? !!t.allowEmptyArrays : P.allowEmptyArrays,
			allowPrototypes: "boolean" == typeof t.allowPrototypes ? t.allowPrototypes : P.allowPrototypes,
			allowSparse: "boolean" == typeof t.allowSparse ? t.allowSparse : P.allowSparse,
			arrayLimit: "number" == typeof t.arrayLimit ? t.arrayLimit : P.arrayLimit,
			charset: e,
			charsetSentinel: "boolean" == typeof t.charsetSentinel ? t.charsetSentinel : P.charsetSentinel,
			comma: "boolean" == typeof t.comma ? t.comma : P.comma,
			decodeDotInKeys: "boolean" == typeof t.decodeDotInKeys ? t.decodeDotInKeys : P.decodeDotInKeys,
			decoder: "function" == typeof t.decoder ? t.decoder : P.decoder,
			delimiter: "string" == typeof t.delimiter || (r = t.delimiter, "[object RegExp]" === Object.prototype.toString.call(r)) ? t.delimiter : P.delimiter,
			depth: "number" == typeof t.depth || !1 === t.depth ? +t.depth : P.depth,
			duplicates: n,
			ignoreQueryPrefix: !0 === t.ignoreQueryPrefix,
			interpretNumericEntities: "boolean" == typeof t.interpretNumericEntities ? t.interpretNumericEntities : P.interpretNumericEntities,
			parameterLimit: "number" == typeof t.parameterLimit ? t.parameterLimit : P.parameterLimit,
			parseArrays: !1 !== t.parseArrays,
			plainObjects: "boolean" == typeof t.plainObjects ? t.plainObjects : P.plainObjects,
			strictNullHandling: "boolean" == typeof t.strictNullHandling ? t.strictNullHandling : P.strictNullHandling
		};
		var r;
	}(e);
	if ("" === t || null == t) return n.plainObjects ? Object.create(null) : {};
	const r = "string" == typeof t ? function(t, e) {
		const n = { __proto__: null }, r = (e.ignoreQueryPrefix ? t.replace(/^\?/, "") : t).split(e.delimiter, Infinity === e.parameterLimit ? void 0 : e.parameterLimit);
		let o, i = -1, u = e.charset;
		if (e.charsetSentinel) for (o = 0; o < r.length; ++o) 0 === r[o].indexOf("utf8=") && ("utf8=%E2%9C%93" === r[o] ? u = "utf-8" : "utf8=%26%2310003%3B" === r[o] && (u = "iso-8859-1"), i = o, o = r.length);
		for (o = 0; o < r.length; ++o) {
			if (o === i) continue;
			const t = r[o], f = t.indexOf("]="), c = -1 === f ? t.indexOf("=") : f + 1;
			let a, l;
			-1 === c ? (a = e.decoder(t, P.decoder, u, "key"), l = e.strictNullHandling ? null : "") : (a = e.decoder(t.slice(0, c), P.decoder, u, "key"), l = T(F(t.slice(c + 1), e), function(t) {
				return e.decoder(t, P.decoder, u, "value");
			})), l && e.interpretNumericEntities && "iso-8859-1" === u && (l = Z(l)), t.indexOf("[]=") > -1 && (l = C(l) ? [l] : l);
			const s = x.call(n, a);
			s && "combine" === e.duplicates ? n[a] = E(n[a], l, e.arrayLimit, e.plainObjects) : s && "last" !== e.duplicates || (n[a] = l);
		}
		return n;
	}(t, n) : t;
	let o = n.plainObjects ? Object.create(null) : {};
	const i = Object.keys(r);
	for (let e = 0; e < i.length; ++e) {
		const u = i[e], f = U(u, r[u], n, "string" == typeof t);
		o = j(o, f, n);
	}
	return !0 === n.allowSparse ? o : function(t) {
		const e = [{
			obj: { o: t },
			prop: "o"
		}], n = [];
		for (let t = 0; t < e.length; ++t) {
			const r = e[t], o = r.obj[r.prop], i = Object.keys(o);
			for (let t = 0; t < i.length; ++t) {
				const r = i[t], u = o[r];
				"object" == typeof u && null !== u && -1 === n.indexOf(u) && (e.push({
					obj: o,
					prop: r
				}), n.push(u));
			}
		}
		return function(t) {
			for (; t.length > 1;) {
				const e = t.pop(), n = e.obj[e.prop];
				if (y(n)) {
					const t = [];
					for (let e = 0; e < n.length; ++e) void 0 !== n[e] && t.push(n[e]);
					e.obj[e.prop] = t;
				}
			}
		}(e), t;
	}(o);
}
var K = /*#__PURE__*/ function() {
	function t(t, e, n) {
		var r, o;
		this.name = t, this.definition = e, this.bindings = null != (r = e.bindings) ? r : {}, this.wheres = null != (o = e.wheres) ? o : {}, this.config = n;
	}
	var n = t.prototype;
	return n.matchesUrl = function(t) {
		var e, n = this;
		if (!this.definition.methods.includes("GET")) return !1;
		var r = this.template.replace(/[.*+$()[\]]/g, "\\$&").replace(/(\/?){([^}?]*)(\??)}/g, function(t, e, r, o) {
			var i, u = "(?<" + r + ">" + ((null == (i = n.wheres[r]) ? void 0 : i.replace(/(^\^)|(\$$)/g, "")) || "[^/?]+") + ")";
			return o ? "(" + e + u + ")?" : "" + e + u;
		}).replace(/^\w+:\/\//, ""), o = t.replace(/^\w+:\/\//, "").split("?"), i = o[0], u = o[1], f = null != (e = new RegExp("^" + r + "/?$").exec(i)) ? e : new RegExp("^" + r + "/?$").exec(decodeURI(i));
		if (f) {
			for (var c in f.groups) f.groups[c] = "string" == typeof f.groups[c] ? decodeURIComponent(f.groups[c]) : f.groups[c];
			return {
				params: f.groups,
				query: q(u)
			};
		}
		return !1;
	}, n.compile = function(t) {
		var e = this;
		return this.parameterSegments.length ? this.template.replace(/{([^}?]+)(\??)}/g, function(n, r, o) {
			var i, u;
			if (!o && [null, void 0].includes(t[r])) throw new Error("Ziggy error: '" + r + "' parameter is required for route '" + e.name + "'.");
			if (e.wheres[r] && !new RegExp("^" + (o ? "(" + e.wheres[r] + ")?" : e.wheres[r]) + "$").test(null != (u = t[r]) ? u : "")) throw new Error("Ziggy error: '" + r + "' parameter '" + t[r] + "' does not match required format '" + e.wheres[r] + "' for route '" + e.name + "'.");
			return encodeURI(null != (i = t[r]) ? i : "").replace(/%7C/g, "|").replace(/%25/g, "%").replace(/\$/g, "%24");
		}).replace(this.config.absolute ? /(\.[^/]+?)(\/\/)/ : /(^)(\/\/)/, "$1/").replace(/\/+$/, "") : this.template;
	}, e(t, [
		{
			key: "template",
			get: function() {
				var t = (this.origin + "/" + this.definition.uri).replace(/\/+$/, "");
				return "" === t ? "/" : t;
			}
		},
		{
			key: "origin",
			get: function() {
				return this.config.absolute ? this.definition.domain ? "" + this.config.url.match(/^\w+:\/\//)[0] + this.definition.domain + (this.config.port ? ":" + this.config.port : "") : this.config.url : "";
			}
		},
		{
			key: "parameterSegments",
			get: function() {
				var t, e;
				return null != (t = null == (e = this.template.match(/{[^}?]+\??}/g)) ? void 0 : e.map(function(t) {
					return {
						name: t.replace(/{|\??}/g, ""),
						required: !/\?}$/.test(t)
					};
				})) ? t : [];
			}
		}
	]);
}();
var z = /*#__PURE__*/ function(t) {
	function r(e, r, o, i) {
		var u;
		if (void 0 === o && (o = !0), (u = t.call(this) || this).t = null != i ? i : "undefined" != typeof Ziggy ? Ziggy : null == globalThis ? void 0 : globalThis.Ziggy, !u.t && "undefined" != typeof document && document.getElementById("ziggy-routes-json") && (globalThis.Ziggy = JSON.parse(document.getElementById("ziggy-routes-json").textContent), u.t = globalThis.Ziggy), u.t = n({}, u.t, { absolute: o }), e) {
			if (!u.t.routes[e]) throw new Error("Ziggy error: route '" + e + "' is not in the route list.");
			u.i = new K(e, u.t.routes[e], u.t), u.u = u.l(r);
		}
		return u;
	}
	var o, u = t;
	(o = r).prototype = Object.create(u.prototype), o.prototype.constructor = o, i(o, u);
	var f = r.prototype;
	return f.toString = function() {
		var t = this, e = Object.keys(this.u).filter(function(e) {
			return !t.i.parameterSegments.some(function(t) {
				return t.name === e;
			});
		}).filter(function(t) {
			return "_query" !== t;
		}).reduce(function(e, r) {
			var o;
			return n({}, e, ((o = {})[r] = t.u[r], o));
		}, {});
		return this.i.compile(this.u) + function(t, e) {
			let n = t;
			const r = function(t) {
				if (!t) return $;
				if (void 0 !== t.allowEmptyArrays && "boolean" != typeof t.allowEmptyArrays) throw new TypeError("`allowEmptyArrays` option can only be `true` or `false`, when provided");
				if (void 0 !== t.encodeDotInKeys && "boolean" != typeof t.encodeDotInKeys) throw new TypeError("`encodeDotInKeys` option can only be `true` or `false`, when provided");
				if (null != t.encoder && "function" != typeof t.encoder) throw new TypeError("Encoder has to be a function.");
				const e = t.charset || $.charset;
				if (void 0 !== t.charset && "utf-8" !== t.charset && "iso-8859-1" !== t.charset) throw new TypeError("The charset option must be either utf-8, iso-8859-1, or undefined");
				let n = s;
				if (void 0 !== t.format) {
					if (!R.call(l, t.format)) throw new TypeError("Unknown format option provided.");
					n = t.format;
				}
				const r = l[n];
				let o, i = $.filter;
				if (("function" == typeof t.filter || S(t.filter)) && (i = t.filter), o = t.arrayFormat in k ? t.arrayFormat : "indices" in t ? t.indices ? "indices" : "repeat" : $.arrayFormat, "commaRoundTrip" in t && "boolean" != typeof t.commaRoundTrip) throw new TypeError("`commaRoundTrip` must be a boolean, or absent");
				return {
					addQueryPrefix: "boolean" == typeof t.addQueryPrefix ? t.addQueryPrefix : $.addQueryPrefix,
					allowDots: void 0 === t.allowDots ? !0 === t.encodeDotInKeys || $.allowDots : !!t.allowDots,
					allowEmptyArrays: "boolean" == typeof t.allowEmptyArrays ? !!t.allowEmptyArrays : $.allowEmptyArrays,
					arrayFormat: o,
					charset: e,
					charsetSentinel: "boolean" == typeof t.charsetSentinel ? t.charsetSentinel : $.charsetSentinel,
					commaRoundTrip: t.commaRoundTrip,
					delimiter: void 0 === t.delimiter ? $.delimiter : t.delimiter,
					encode: "boolean" == typeof t.encode ? t.encode : $.encode,
					encodeDotInKeys: "boolean" == typeof t.encodeDotInKeys ? t.encodeDotInKeys : $.encodeDotInKeys,
					encoder: "function" == typeof t.encoder ? t.encoder : $.encoder,
					encodeValuesOnly: "boolean" == typeof t.encodeValuesOnly ? t.encodeValuesOnly : $.encodeValuesOnly,
					filter: i,
					format: n,
					formatter: r,
					serializeDate: "function" == typeof t.serializeDate ? t.serializeDate : $.serializeDate,
					skipNulls: "boolean" == typeof t.skipNulls ? t.skipNulls : $.skipNulls,
					sort: "function" == typeof t.sort ? t.sort : null,
					strictNullHandling: "boolean" == typeof t.strictNullHandling ? t.strictNullHandling : $.strictNullHandling
				};
			}(e);
			let o, i;
			"function" == typeof r.filter ? (i = r.filter, n = i("", n)) : S(r.filter) && (i = r.filter, o = i);
			const u = [];
			if ("object" != typeof n || null === n) return "";
			const f = k[r.arrayFormat], c = "comma" === f && r.commaRoundTrip;
			o || (o = Object.keys(n)), r.sort && o.sort(r.sort);
			const a = /* @__PURE__ */ new WeakMap();
			for (let t = 0; t < o.length; ++t) {
				const e = o[t];
				r.skipNulls && null === n[e] || A(u, _(n[e], e, f, c, r.allowEmptyArrays, r.strictNullHandling, r.skipNulls, r.encodeDotInKeys, r.encode ? r.encoder : null, r.filter, r.sort, r.allowDots, r.serializeDate, r.format, r.formatter, r.encodeValuesOnly, r.charset, a));
			}
			const p = u.join(r.delimiter);
			let y = !0 === r.addQueryPrefix ? "?" : "";
			return r.charsetSentinel && (y += "iso-8859-1" === r.charset ? "utf8=%26%2310003%3B&" : "utf8=%E2%9C%93&"), p.length > 0 ? y + p : "";
		}(n({}, e, this.u._query), {
			addQueryPrefix: !0,
			arrayFormat: "indices",
			encodeValuesOnly: !0,
			skipNulls: !0,
			encoder: function(t, e) {
				return "boolean" == typeof t ? Number(t) : e(t);
			}
		});
	}, f.p = function(t) {
		var e = this;
		t ? this.t.absolute && t.startsWith("/") && (t = this.v().host + t) : t = this.h();
		var r = {}, o = Object.entries(this.t.routes).find(function(n) {
			return r = new K(n[0], n[1], e.t).matchesUrl(t);
		}) || [void 0, void 0];
		return n({ name: o[0] }, r, { route: o[1] });
	}, f.h = function() {
		var t = this.v(), e = t.pathname, n = t.search;
		return (this.t.absolute ? t.host + e : e.replace(this.t.url.replace(/^\w*:\/\/[^/]+/, ""), "").replace(/^\/+/, "/")) + n;
	}, f.current = function(t, e) {
		var r = this.p(), o = r.name, i = r.params, u = r.query, f = r.route;
		if (!t) return o;
		var c = new RegExp("^" + t.replace(/\./g, "\\.").replace(/\*/g, ".*") + "$").test(o);
		if ([null, void 0].includes(e) || !c) return c;
		var a = new K(o, f, this.t);
		e = this.l(e, a);
		var l = n({}, i, u);
		if (Object.values(e).every(function(t) {
			return !t;
		}) && !Object.values(l).some(function(t) {
			return void 0 !== t;
		})) return !0;
		var s = function(t, e) {
			return Object.entries(t).every(function(t) {
				var n = t[0], r = t[1];
				return Array.isArray(r) && Array.isArray(e[n]) ? r.every(function(t) {
					return e[n].includes(t) || e[n].includes(decodeURIComponent(t));
				}) : "object" == typeof r && "object" == typeof e[n] && null !== r && null !== e[n] ? s(r, e[n]) : e[n] == r || e[n] == decodeURIComponent(r);
			});
		};
		return s(e, l);
	}, f.v = function() {
		var t, e, n, r, o, i, u = "undefined" != typeof window ? window.location : {}, f = u.host, c = u.pathname, a = u.search;
		return {
			host: null != (t = null == (e = this.t.location) ? void 0 : e.host) ? t : void 0 === f ? "" : f,
			pathname: null != (n = null == (r = this.t.location) ? void 0 : r.pathname) ? n : void 0 === c ? "" : c,
			search: null != (o = null == (i = this.t.location) ? void 0 : i.search) ? o : void 0 === a ? "" : a
		};
	}, f.has = function(t) {
		return this.t.routes.hasOwnProperty(t);
	}, f.l = function(t, e) {
		var r = this;
		void 0 === t && (t = {}), void 0 === e && (e = this.i), t ??= {}, t = ["string", "number"].includes(typeof t) ? [t] : t;
		var o = e.parameterSegments.filter(function(t) {
			return !r.t.defaults[t.name];
		});
		if (Array.isArray(t)) t = t.reduce(function(t, e, r) {
			var i, u;
			return n({}, t, o[r] ? ((i = {})[o[r].name] = e, i) : "object" == typeof e ? e : ((u = {})[e] = "", u));
		}, {});
		else if (1 === o.length && !t.hasOwnProperty(o[0].name) && (t.hasOwnProperty(Object.values(e.bindings)[0]) || t.hasOwnProperty("id"))) {
			var i;
			(i = {})[o[0].name] = t, t = i;
		}
		return n({}, this.m(e), this.j(t, e));
	}, f.m = function(t) {
		var e = this;
		return t.parameterSegments.filter(function(t) {
			return e.t.defaults[t.name];
		}).reduce(function(t, r, o) {
			var i, u = r.name;
			return n({}, t, ((i = {})[u] = e.t.defaults[u], i));
		}, {});
	}, f.j = function(t, e) {
		var r = e.bindings, o = e.parameterSegments;
		return Object.entries(t).reduce(function(t, e) {
			var i, u, f = e[0], c = e[1];
			if (!c || "object" != typeof c || Array.isArray(c) || !o.some(function(t) {
				return t.name === f;
			})) return n({}, t, ((u = {})[f] = c, u));
			var a = c.hasOwnProperty(r[f]) ? r[f] : c.hasOwnProperty("id") ? "id" : void 0;
			if (void 0 === a) throw new Error("Ziggy error: object passed as '" + f + "' parameter is missing route model binding key '" + r[f] + "'.");
			return n({}, t, ((i = {})[f] = c[a], i));
		}, {});
	}, f.valueOf = function() {
		return this.toString();
	}, e(r, [
		{
			key: "params",
			get: function() {
				var t = this.p();
				return n({}, t.params, t.query);
			}
		},
		{
			key: "routeParams",
			get: function() {
				return this.p().params;
			}
		},
		{
			key: "queryParams",
			get: function() {
				return this.p().query;
			}
		}
	]);
}(/*#__PURE__*/ f(String));
function B(t, e, n, r) {
	var o = new z(t, e, n, r);
	return t ? o.toString() : o;
}
var M = { install: function(t, e) {
	var n = function(t, n, r, o) {
		return void 0 === o && (o = e), B(t, n, r, o);
	};
	parseInt(t.version) > 2 ? (t.config.globalProperties.route = n, t.provide("route", n)) : t.mixin({ methods: { route: n } });
} };
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/vue/plugins/icons.js
var icons_default = { install() {} };
//#endregion
//#region node_modules/.pnpm/hookable@6.1.1/node_modules/hookable/dist/index.mjs
function flatHooks(configHooks, hooks = {}, parentName) {
	for (const key in configHooks) {
		const subHook = configHooks[key];
		const name = parentName ? `${parentName}:${key}` : key;
		if (typeof subHook === "object" && subHook !== null) flatHooks(subHook, hooks, name);
		else if (typeof subHook === "function") hooks[name] = subHook;
	}
	return hooks;
}
var createTask = /* @__PURE__ */ (() => {
	if (console.createTask) return console.createTask;
	const defaultTask = { run: (fn) => fn() };
	return () => defaultTask;
})();
function callHooks(hooks, args, startIndex, task) {
	for (let i = startIndex; i < hooks.length; i += 1) try {
		const result = task ? task.run(() => hooks[i](...args)) : hooks[i](...args);
		if (result && typeof result.then === "function") return Promise.resolve(result).then(() => callHooks(hooks, args, i + 1, task));
	} catch (error) {
		return Promise.reject(error);
	}
}
function serialTaskCaller(hooks, args, name) {
	if (hooks.length > 0) return callHooks(hooks, args, 0, createTask(name));
}
function parallelTaskCaller(hooks, args, name) {
	if (hooks.length > 0) {
		const task = createTask(name);
		return Promise.all(hooks.map((hook) => task.run(() => hook(...args))));
	}
}
function callEachWith(callbacks, arg0) {
	for (const callback of [...callbacks]) callback(arg0);
}
var Hookable = class {
	_hooks;
	_before;
	_after;
	_deprecatedHooks;
	_deprecatedMessages;
	constructor() {
		this._hooks = {};
		this._before = void 0;
		this._after = void 0;
		this._deprecatedMessages = void 0;
		this._deprecatedHooks = {};
		this.hook = this.hook.bind(this);
		this.callHook = this.callHook.bind(this);
		this.callHookWith = this.callHookWith.bind(this);
	}
	hook(name, function_, options = {}) {
		if (!name || typeof function_ !== "function") return () => {};
		const originalName = name;
		let dep;
		while (this._deprecatedHooks[name]) {
			dep = this._deprecatedHooks[name];
			name = dep.to;
		}
		if (dep && !options.allowDeprecated) {
			let message = dep.message;
			if (!message) message = `${originalName} hook has been deprecated` + (dep.to ? `, please use ${dep.to}` : "");
			if (!this._deprecatedMessages) this._deprecatedMessages = /* @__PURE__ */ new Set();
			if (!this._deprecatedMessages.has(message)) {
				console.warn(message);
				this._deprecatedMessages.add(message);
			}
		}
		if (!function_.name) try {
			Object.defineProperty(function_, "name", {
				get: () => "_" + name.replace(/\W+/g, "_") + "_hook_cb",
				configurable: true
			});
		} catch {}
		this._hooks[name] = this._hooks[name] || [];
		this._hooks[name].push(function_);
		return () => {
			if (function_) {
				this.removeHook(name, function_);
				function_ = void 0;
			}
		};
	}
	hookOnce(name, function_) {
		let _unreg;
		let _function = (...arguments_) => {
			if (typeof _unreg === "function") _unreg();
			_unreg = void 0;
			_function = void 0;
			return function_(...arguments_);
		};
		_unreg = this.hook(name, _function);
		return _unreg;
	}
	removeHook(name, function_) {
		const hooks = this._hooks[name];
		if (hooks) {
			const index = hooks.indexOf(function_);
			if (index !== -1) hooks.splice(index, 1);
			if (hooks.length === 0) this._hooks[name] = void 0;
		}
	}
	clearHook(name) {
		this._hooks[name] = void 0;
	}
	deprecateHook(name, deprecated) {
		this._deprecatedHooks[name] = typeof deprecated === "string" ? { to: deprecated } : deprecated;
		const _hooks = this._hooks[name] || [];
		this._hooks[name] = void 0;
		for (const hook of _hooks) this.hook(name, hook);
	}
	deprecateHooks(deprecatedHooks) {
		for (const name in deprecatedHooks) this.deprecateHook(name, deprecatedHooks[name]);
	}
	addHooks(configHooks) {
		const hooks = flatHooks(configHooks);
		const removeFns = Object.keys(hooks).map((key) => this.hook(key, hooks[key]));
		return () => {
			for (const unreg of removeFns) unreg();
			removeFns.length = 0;
		};
	}
	removeHooks(configHooks) {
		const hooks = flatHooks(configHooks);
		for (const key in hooks) this.removeHook(key, hooks[key]);
	}
	removeAllHooks() {
		this._hooks = {};
	}
	callHook(name, ...args) {
		return this.callHookWith(serialTaskCaller, name, args);
	}
	callHookParallel(name, ...args) {
		return this.callHookWith(parallelTaskCaller, name, args);
	}
	callHookWith(caller, name, args) {
		const event = this._before || this._after ? {
			name,
			args,
			context: {}
		} : void 0;
		if (this._before) callEachWith(this._before, event);
		const result = caller(this._hooks[name] ? [...this._hooks[name]] : [], args, name);
		if (result instanceof Promise) return result.finally(() => {
			if (this._after && event) callEachWith(this._after, event);
		});
		if (this._after && event) callEachWith(this._after, event);
		return result;
	}
	beforeEach(function_) {
		this._before = this._before || [];
		this._before.push(function_);
		return () => {
			if (this._before !== void 0) {
				const index = this._before.indexOf(function_);
				if (index !== -1) this._before.splice(index, 1);
			}
		};
	}
	afterEach(function_) {
		this._after = this._after || [];
		this._after.push(function_);
		return () => {
			if (this._after !== void 0) {
				const index = this._after.indexOf(function_);
				if (index !== -1) this._after.splice(index, 1);
			}
		};
	}
};
function createHooks() {
	return new Hookable();
}
//#endregion
//#region node_modules/.pnpm/unhead@2.1.17/node_modules/unhead/dist/shared/unhead.AvDFlk_u.mjs
var DupeableTags = /* @__PURE__ */ new Set([
	"link",
	"style",
	"script",
	"noscript"
]);
var TagsWithInnerContent = /* @__PURE__ */ new Set([
	"title",
	"titleTemplate",
	"script",
	"style",
	"noscript"
]);
var HasElementTags = /* @__PURE__ */ new Set([
	"base",
	"meta",
	"link",
	"style",
	"script",
	"noscript"
]);
var ValidHeadTags = /* @__PURE__ */ new Set([
	"title",
	"base",
	"htmlAttrs",
	"bodyAttrs",
	"meta",
	"link",
	"style",
	"script",
	"noscript"
]);
var UniqueTags = /* @__PURE__ */ new Set([
	"base",
	"title",
	"titleTemplate",
	"bodyAttrs",
	"htmlAttrs",
	"templateParams"
]);
var TagConfigKeys = /* @__PURE__ */ new Set([
	"key",
	"tagPosition",
	"tagPriority",
	"tagDuplicateStrategy",
	"innerHTML",
	"textContent",
	"processTemplateParams"
]);
var UsesMergeStrategy = /* @__PURE__ */ new Set([
	"templateParams",
	"htmlAttrs",
	"bodyAttrs"
]);
var MetaTagsArrayable = /* @__PURE__ */ new Set([
	"theme-color",
	"google-site-verification",
	"og",
	"article",
	"book",
	"profile",
	"twitter",
	"author"
]);
var hasContent = (value) => typeof value === "number" ? Number.isFinite(value) : value;
//#endregion
//#region node_modules/.pnpm/unhead@2.1.17/node_modules/unhead/dist/shared/unhead.ChXiQvI8.mjs
// @__NO_SIDE_EFFECTS__
function isUnsafeKey(key) {
	return key === "__proto__" || key === "constructor" || key === "prototype";
}
var sortTags = (a, b) => a._w === b._w ? a._p - b._p : a._w - b._w;
var TAG_WEIGHTS = {
	base: -10,
	title: 10
};
var TAG_ALIASES = {
	critical: -8,
	high: -1,
	low: 2
};
var WEIGHT_MAP = {
	meta: {
		"content-security-policy": -30,
		"charset": -20,
		"viewport": -15
	},
	link: {
		"preconnect": 20,
		"stylesheet": 60,
		"preload": 70,
		"modulepreload": 70,
		"prefetch": 90,
		"dns-prefetch": 90,
		"prerender": 90
	},
	script: {
		async: 30,
		defer: 80,
		sync: 50
	},
	style: {
		imported: 40,
		sync: 60
	}
};
var ImportStyleRe = /@import/;
var isTruthy = (val) => val === "" || val === true;
function tagWeight(head, tag) {
	if (typeof tag.tagPriority === "number") return tag.tagPriority;
	let weight = 100;
	const offset = TAG_ALIASES[tag.tagPriority] || 0;
	const weightMap = head.resolvedOptions.disableCapoSorting ? {
		link: {},
		script: {},
		style: {}
	} : WEIGHT_MAP;
	if (tag.tag in TAG_WEIGHTS) weight = TAG_WEIGHTS[tag.tag];
	else if (tag.tag === "meta") {
		const metaType = tag.props["http-equiv"] === "content-security-policy" ? "content-security-policy" : tag.props.charset ? "charset" : tag.props.name === "viewport" ? "viewport" : null;
		if (metaType) weight = WEIGHT_MAP.meta[metaType];
	} else if (tag.tag === "link" && tag.props.rel) weight = weightMap.link[tag.props.rel];
	else if (tag.tag === "script") {
		const type = String(tag.props.type);
		if (isTruthy(tag.props.async)) weight = weightMap.script.async;
		else if (tag.props.src && !isTruthy(tag.props.defer) && !isTruthy(tag.props.async) && type !== "module" && !type.endsWith("json") || tag.innerHTML && !type.endsWith("json")) weight = weightMap.script.sync;
		else if (isTruthy(tag.props.defer) && tag.props.src && !isTruthy(tag.props.async) || type === "module") weight = weightMap.script.defer;
	} else if (tag.tag === "style") weight = tag.innerHTML && ImportStyleRe.test(tag.innerHTML) ? weightMap.style.imported : weightMap.style.sync;
	return (weight || 100) + offset;
}
//#endregion
//#region node_modules/.pnpm/unhead@2.1.17/node_modules/unhead/dist/shared/unhead.J7R8psSN.mjs
var allowedMetaProperties = [
	"name",
	"property",
	"http-equiv"
];
var StandardSingleMetaTags = /* @__PURE__ */ new Set([
	"viewport",
	"description",
	"keywords",
	"robots"
]);
function isMetaArrayDupeKey(v) {
	const i = v.indexOf(":");
	if (i === -1) return false;
	const j = v.indexOf(":", i + 1);
	const namespace = v.slice(i + 1, j === -1 ? v.length : j);
	if (namespace === "twitter") return v === "meta:twitter:image" || v.startsWith("meta:twitter:image:");
	return MetaTagsArrayable.has(namespace);
}
function dedupeKey(tag) {
	const { props, tag: name } = tag;
	if (UniqueTags.has(name)) return name;
	if (name === "link" && props.rel === "canonical") return "canonical";
	if (name === "link" && props.rel === "alternate") {
		if (props.hreflang) return `alternate:${props.hreflang}`;
		if (props.type) return `alternate:${props.type}:${props.href || ""}`;
	}
	if (props.charset) return "charset";
	if (tag.tag === "meta") {
		for (const n of allowedMetaProperties) if (props[n] !== void 0) {
			const propValue = props[n];
			const isStructured = propValue && typeof propValue === "string" && propValue.includes(":");
			const isStandardSingle = propValue && StandardSingleMetaTags.has(propValue);
			return `${name}:${propValue}${!(isStructured || isStandardSingle) && tag.key ? `:key:${tag.key}` : ""}`;
		}
	}
	if (tag.key) return `${name}:key:${tag.key}`;
	if (props.id) return `${name}:id:${props.id}`;
	if (name === "link" && props.rel === "alternate") return `alternate:${props.href || ""}`;
	if (TagsWithInnerContent.has(name)) {
		const v = tag.textContent || tag.innerHTML;
		if (v) return `${name}:content:${v}`;
	}
}
function hashTag(tag) {
	const dedupe = tag._h || tag._d;
	if (dedupe) return dedupe;
	const inner = tag.textContent || tag.innerHTML;
	if (inner) return inner;
	const keys = Object.keys(tag.props).sort();
	return `${tag.tag}:${keys.map((k) => `${k}:${String(tag.props[k])}`).join(",")}`;
}
function walkResolver(val, resolve, key) {
	if (typeof val === "function") {
		if (!key || key !== "titleTemplate" && !(key[0] === "o" && key[1] === "n")) val = val();
	}
	const v = resolve ? resolve(key, val) : val;
	if (Array.isArray(v)) {
		let out;
		for (let i = 0; i < v.length; i++) {
			const resolved = walkResolver(v[i], resolve);
			if (out) out[i] = resolved;
			else if (resolved !== v[i]) {
				out = v.slice(0, i);
				out[i] = resolved;
			}
		}
		return out || v;
	}
	if (v?.constructor === Object) {
		let next;
		for (const k in v) {
			const unsafe = /* @__PURE__ */ isUnsafeKey(k);
			const resolved = unsafe ? void 0 : walkResolver(v[k], resolve, k);
			if (!next && (unsafe || k === "_resolver" || resolved !== v[k])) {
				next = {};
				for (const previousKey in v) {
					if (previousKey === k) break;
					next[previousKey] = v[previousKey];
				}
			}
			if (next && !unsafe) next[k] = resolved;
		}
		return next || v;
	}
	return v;
}
var INVALID_ATTR_NAME_RE = /[\s"'<>/=\x00-\x1F\x7F]/;
function normalizeStyleClassProps(key, value) {
	const store = key === "style" ? /* @__PURE__ */ new Map() : /* @__PURE__ */ new Set();
	function processValue(rawValue) {
		if (rawValue == null || rawValue === void 0) return;
		const value2 = String(rawValue).trim();
		if (!value2) return;
		if (key === "style") {
			const [k, ...v] = value2.split(":").map((s) => s ? s.trim() : "");
			if (k && v.length) store.set(k, v.join(":"));
		} else value2.split(" ").filter(Boolean).forEach((c) => store.add(c));
	}
	if (typeof value === "string") key === "style" ? value.split(";").forEach(processValue) : processValue(value);
	else if (Array.isArray(value)) value.forEach((item) => processValue(item));
	else if (value && typeof value === "object") Object.entries(value).forEach(([k, v]) => {
		if (v && v !== "false") key === "style" ? store.set(String(k).trim(), String(v)) : processValue(k);
	});
	return store;
}
function normalizeProps$1(tag, input) {
	tag.props = tag.props || {};
	if (!input) return tag;
	if (tag.tag === "templateParams") {
		tag.props = input;
		return tag;
	}
	const isHtmlTag = HasElementTags.has(tag.tag) || tag.tag === "htmlAttrs" || tag.tag === "bodyAttrs";
	for (const prop of Object.keys(input)) {
		if (/* @__PURE__ */ isUnsafeKey(prop)) continue;
		const isDataKey = prop.startsWith("data-");
		const isHtmlAttr = isHtmlTag && !TagConfigKeys.has(prop);
		const key = isHtmlAttr && !isDataKey ? prop.toLowerCase() : prop;
		if (isHtmlAttr && (!key || INVALID_ATTR_NAME_RE.test(key))) continue;
		const value = input[prop];
		if (value === null) {
			tag.props[key] = null;
			continue;
		}
		if (prop === "class" || prop === "style") {
			tag.props[prop] = normalizeStyleClassProps(prop, value);
			continue;
		}
		if (TagConfigKeys.has(prop)) {
			if ((prop === "textContent" || prop === "innerHTML") && typeof value === "object") {
				let type = input.type;
				if (!input.type) type = "application/json";
				if (!type?.endsWith("json") && type !== "speculationrules") continue;
				input.type = type;
				tag.props.type = type;
				tag[prop] = JSON.stringify(value);
			} else tag[prop] = value;
			continue;
		}
		const strValue = String(value);
		const isMetaContentKey = tag.tag === "meta" && key === "content";
		if (strValue === "true" || strValue === "") tag.props[key] = isDataKey || isMetaContentKey ? strValue : true;
		else if (!value && isDataKey && strValue === "false") tag.props[key] = "false";
		else if (value !== void 0) tag.props[key] = value;
	}
	return tag;
}
function normalizeTag(tagName, _input) {
	const tag = normalizeProps$1({
		tag: tagName,
		props: {}
	}, typeof _input === "object" && typeof _input !== "function" ? _input : { [tagName === "script" || tagName === "noscript" || tagName === "style" ? "innerHTML" : "textContent"]: _input });
	if (tag.key && DupeableTags.has(tag.tag)) tag.props["data-hid"] = tag._h = tag.key;
	if (tag.tag === "script" && typeof tag.innerHTML === "object") {
		tag.innerHTML = JSON.stringify(tag.innerHTML);
		tag.props.type = tag.props.type || "application/json";
	}
	return Array.isArray(tag.props.content) ? tag.props.content.map((v) => ({
		...tag,
		props: {
			...tag.props,
			content: v
		}
	})) : tag;
}
function normalizeEntryToTags(input, propResolvers) {
	if (!input) return [];
	if (typeof input === "function") input = input();
	const resolvers = (key, val) => {
		for (let i = 0; i < propResolvers.length; i++) val = propResolvers[i](key, val);
		return val;
	};
	input = resolvers(void 0, input);
	const tags = [];
	input = walkResolver(input, resolvers);
	Object.entries(input || {}).forEach(([key, value]) => {
		if (value === void 0) return;
		for (const v of Array.isArray(value) ? value : [value]) tags.push(normalizeTag(key, v));
	});
	return tags.flat();
}
//#endregion
//#region node_modules/.pnpm/unhead@2.1.17/node_modules/unhead/dist/shared/unhead.BrXkGDAU.mjs
function registerPlugin(head, p) {
	const plugin = typeof p === "function" ? p(head) : p;
	const key = plugin.key || String(head.plugins.size + 1);
	if (!head.plugins.get(key)) {
		head.plugins.set(key, plugin);
		head.hooks.addHooks(plugin.hooks || {});
	}
}
// @__NO_SIDE_EFFECTS__
function createUnhead(resolvedOptions = {}) {
	const hooks = createHooks();
	hooks.addHooks(resolvedOptions.hooks || {});
	const ssr = !resolvedOptions.document;
	const entries = /* @__PURE__ */ new Map();
	const plugins = /* @__PURE__ */ new Map();
	const normalizeQueue = /* @__PURE__ */ new Set();
	const head = {
		_entryCount: 1,
		plugins,
		dirty: false,
		resolvedOptions,
		hooks,
		ssr,
		entries,
		headEntries() {
			return [...entries.values()];
		},
		use: (p) => registerPlugin(head, p),
		push(input, _options) {
			const options = { ..._options || {} };
			delete options.head;
			const _i = options._index ?? head._entryCount++;
			const inst = {
				_i,
				input,
				options
			};
			const _ = {
				_poll(rm = false) {
					head.dirty = true;
					!rm && normalizeQueue.add(_i);
					hooks.callHook("entries:updated", head);
				},
				dispose() {
					if (entries.delete(_i)) head.invalidate();
				},
				patch(input2) {
					if (!options.mode || options.mode === "server" && ssr || options.mode === "client" && !ssr) {
						inst.input = input2;
						entries.set(_i, inst);
						_._poll();
					}
				}
			};
			_.patch(input);
			return _;
		},
		async resolveTags() {
			const ctx = {
				tagMap: /* @__PURE__ */ new Map(),
				tags: [],
				entries: [...head.entries.values()]
			};
			await hooks.callHook("entries:resolve", ctx);
			while (normalizeQueue.size) {
				const i = normalizeQueue.values().next().value;
				normalizeQueue.delete(i);
				const e = entries.get(i);
				if (e) {
					const normalizeCtx = {
						tags: normalizeEntryToTags(e.input, resolvedOptions.propResolvers || []).map((t) => Object.assign(t, e.options)),
						entry: e
					};
					await hooks.callHook("entries:normalize", normalizeCtx);
					e._tags = normalizeCtx.tags.map((t, i2) => {
						t._w = tagWeight(head, t);
						t._p = (e._i << 10) + i2;
						t._d = dedupeKey(t);
						if (!t._d) t._h = hashTag(t);
						return t;
					});
				}
			}
			let hasFlatMeta = false;
			ctx.entries.flatMap((e) => (e._tags || []).map((t) => ({
				...t,
				props: { ...t.props }
			}))).sort(sortTags).reduce((acc, next) => {
				const k = next._d || next._h;
				if (!acc.has(k)) return acc.set(k, next);
				const prev = acc.get(k);
				if ((next?.tagDuplicateStrategy || (UsesMergeStrategy.has(next.tag) ? "merge" : null) || (next.key && next.key === prev.key ? "merge" : null)) === "merge") {
					const newProps = { ...prev.props };
					Object.entries(next.props).forEach(([p, v]) => newProps[p] = p === "style" ? new Map([...prev.props.style || /* @__PURE__ */ new Map(), ...v]) : p === "class" ? /* @__PURE__ */ new Set([...prev.props.class || /* @__PURE__ */ new Set(), ...v]) : v);
					acc.set(k, {
						...next,
						props: newProps
					});
				} else if (next._p >> 10 === prev._p >> 10 && next.tag === "meta" && isMetaArrayDupeKey(k)) {
					acc.set(k, Object.assign([...Array.isArray(prev) ? prev : [prev], next], next));
					hasFlatMeta = true;
				} else if (next._w === prev._w ? next._p > prev._p : next?._w < prev?._w) acc.set(k, next);
				return acc;
			}, ctx.tagMap);
			const title = ctx.tagMap.get("title");
			const titleTemplate = ctx.tagMap.get("titleTemplate");
			head._title = title?.textContent;
			if (titleTemplate) {
				const titleTemplateFn = titleTemplate?.textContent;
				head._titleTemplate = titleTemplateFn;
				if (titleTemplateFn) {
					let newTitle = typeof titleTemplateFn === "function" ? titleTemplateFn(title?.textContent) : titleTemplateFn;
					if (typeof newTitle === "string" && !head.plugins.has("template-params")) newTitle = newTitle.replace("%s", title?.textContent || "");
					if (title) newTitle === null ? ctx.tagMap.delete("title") : ctx.tagMap.set("title", {
						...title,
						textContent: newTitle
					});
					else {
						titleTemplate.tag = "title";
						titleTemplate.textContent = newTitle;
					}
				}
			}
			ctx.tags = Array.from(ctx.tagMap.values());
			if (hasFlatMeta) ctx.tags = ctx.tags.flat().sort(sortTags);
			await hooks.callHook("tags:beforeResolve", ctx);
			await hooks.callHook("tags:resolve", ctx);
			await hooks.callHook("tags:afterResolve", ctx);
			const finalTags = [];
			for (const t of ctx.tags) {
				const { innerHTML, tag, props } = t;
				if (!ValidHeadTags.has(tag)) continue;
				if (Object.keys(props).length === 0 && !hasContent(t.innerHTML) && !hasContent(t.textContent)) continue;
				if (tag === "meta") {
					if (!hasContent(props.content) && !props["http-equiv"] && !props.charset) continue;
				}
				if (tag === "script" && innerHTML) {
					if (String(props.type).endsWith("json")) t.innerHTML = (typeof innerHTML === "string" ? innerHTML : JSON.stringify(innerHTML)).replace(/</g, "\\u003C");
					else if (typeof innerHTML === "string") t.innerHTML = innerHTML.replace(new RegExp(`</${tag}`, "g"), `<\\/${tag}`);
					t._d = dedupeKey(t);
				}
				finalTags.push(t);
			}
			return finalTags;
		},
		invalidate() {
			for (const entry of entries.values()) normalizeQueue.add(entry._i);
			head.dirty = true;
			hooks.callHook("entries:updated", head);
		}
	};
	(resolvedOptions?.plugins || []).forEach((p) => registerPlugin(head, p));
	head.hooks.callHook("init", head);
	resolvedOptions.init?.forEach((e) => e && head.push(e));
	return head;
}
//#endregion
//#region node_modules/.pnpm/unhead@2.1.17/node_modules/unhead/dist/client.mjs
async function renderDOMHead(head, options = {}) {
	const dom = options.document || head.resolvedOptions.document;
	if (!dom || !head.dirty) return;
	const beforeRenderCtx = {
		shouldRender: true,
		tags: []
	};
	await head.hooks.callHook("dom:beforeRender", beforeRenderCtx);
	if (!beforeRenderCtx.shouldRender) return;
	if (head._domUpdatePromise) return head._domUpdatePromise;
	head._domUpdatePromise = new Promise(async (resolve) => {
		const dupeKeyCounter = /* @__PURE__ */ new Map();
		const resolveTagPromise = new Promise((resolve2) => {
			head.resolveTags().then((tags2) => {
				resolve2(tags2.map((tag) => {
					const count = dupeKeyCounter.get(tag._d) || 0;
					const res = {
						tag,
						id: (count ? `${tag._d}:${count}` : tag._d) || tag._h,
						shouldRender: true
					};
					if (tag._d && isMetaArrayDupeKey(tag._d)) dupeKeyCounter.set(tag._d, count + 1);
					return res;
				}));
			});
		});
		let state = head._dom;
		if (!state) {
			state = {
				title: dom.title,
				elMap: (/* @__PURE__ */ new Map()).set("htmlAttrs", dom.documentElement).set("bodyAttrs", dom.body)
			};
			for (const key of ["body", "head"]) {
				const children = dom[key]?.children;
				for (const c of children) {
					const tag = c.tagName.toLowerCase();
					if (!HasElementTags.has(tag)) continue;
					const next = normalizeProps$1({
						tag,
						props: {}
					}, {
						innerHTML: c.innerHTML,
						...c.getAttributeNames().reduce((props, name) => {
							props[name] = c.getAttribute(name);
							return props;
						}, {}) || {}
					});
					next.key = c.getAttribute("data-hid") || void 0;
					next._d = dedupeKey(next) || hashTag(next);
					if (state.elMap.has(next._d)) {
						let count = 1;
						let k = next._d;
						while (state.elMap.has(k)) k = `${next._d}:${count++}`;
						state.elMap.set(k, c);
					} else state.elMap.set(next._d, c);
				}
			}
		}
		state.pendingSideEffects = { ...state.sideEffects };
		state.sideEffects = {};
		function track(id, scope, fn) {
			const k = `${id}:${scope}`;
			state.sideEffects[k] = fn;
			delete state.pendingSideEffects[k];
		}
		function trackCtx({ id, $el, tag }) {
			const isAttrTag = tag.tag.endsWith("Attrs");
			state.elMap.set(id, $el);
			if (!isAttrTag) {
				if (tag.textContent && tag.textContent !== $el.textContent) $el.textContent = tag.textContent;
				if (tag.innerHTML && tag.innerHTML !== $el.innerHTML) $el.innerHTML = tag.innerHTML;
				track(id, "el", () => {
					$el?.remove();
					state.elMap.delete(id);
				});
			}
			for (const k in tag.props) {
				if (!Object.prototype.hasOwnProperty.call(tag.props, k)) continue;
				const value = tag.props[k];
				if (k.startsWith("on") && typeof value === "function") {
					const dataset = $el?.dataset;
					if (dataset && dataset[`${k}fired`]) {
						const ek = k.slice(0, -5);
						value.call($el, new Event(ek.substring(2)));
					}
					if ($el.getAttribute(`data-${k}`) !== "") {
						(tag.tag === "bodyAttrs" ? dom.defaultView : $el).addEventListener(k.substring(2), value.bind($el));
						$el.setAttribute(`data-${k}`, "");
					}
					continue;
				}
				const ck = `attr:${k}`;
				if (k === "class") {
					if (!value) continue;
					for (const c of value) {
						isAttrTag && track(id, `${ck}:${c}`, () => $el.classList.remove(c));
						!$el.classList.contains(c) && $el.classList.add(c);
					}
				} else if (k === "style") {
					if (!value) continue;
					for (const [k2, v] of value) {
						track(id, `${ck}:${k2}`, () => {
							$el.style.removeProperty(k2);
						});
						$el.style.setProperty(k2, v);
					}
				} else if (value !== false && value !== null) {
					$el.getAttribute(k) !== value && $el.setAttribute(k, value === true ? "" : String(value));
					isAttrTag && track(id, ck, () => $el.removeAttribute(k));
				}
			}
		}
		const pending = [];
		const frag = {
			bodyClose: void 0,
			bodyOpen: void 0,
			head: void 0
		};
		const tags = await resolveTagPromise;
		for (const ctx of tags) {
			const { tag, shouldRender, id } = ctx;
			if (!shouldRender) continue;
			if (tag.tag === "title") {
				dom.title = tag.textContent;
				track("title", "", () => dom.title = state.title);
				continue;
			}
			ctx.$el = ctx.$el || state.elMap.get(id);
			if (ctx.$el) trackCtx(ctx);
			else if (HasElementTags.has(tag.tag)) pending.push(ctx);
		}
		for (const ctx of pending) {
			const pos = ctx.tag.tagPosition || "head";
			ctx.$el = dom.createElement(ctx.tag.tag);
			trackCtx(ctx);
			frag[pos] = frag[pos] || dom.createDocumentFragment();
			frag[pos].appendChild(ctx.$el);
		}
		for (const ctx of tags) await head.hooks.callHook("dom:renderTag", ctx, dom, track);
		frag.head && dom.head.appendChild(frag.head);
		frag.bodyOpen && dom.body.insertBefore(frag.bodyOpen, dom.body.firstChild);
		frag.bodyClose && dom.body.appendChild(frag.bodyClose);
		for (const k in state.pendingSideEffects) state.pendingSideEffects[k]();
		head._dom = state;
		await head.hooks.callHook("dom:rendered", { renders: tags });
		resolve();
	}).finally(() => {
		head._domUpdatePromise = void 0;
		head.dirty = false;
	});
	return head._domUpdatePromise;
}
function createHead$1(options = {}) {
	const render = options.domOptions?.render || renderDOMHead;
	options.document = options.document || (typeof window !== "undefined" ? document : void 0);
	const initialPayload = options.document?.head.querySelector("script[id=\"unhead:payload\"]")?.innerHTML || false;
	const head = /* @__PURE__ */ createUnhead({
		...options,
		plugins: [...options.plugins || [], {
			key: "client",
			hooks: { "entries:updated": render }
		}],
		init: [initialPayload ? JSON.parse(initialPayload) : false, ...options.init || []]
	});
	head.ssr = false;
	return head;
}
function createDebouncedFn(callee, delayer) {
	let ctxId = 0;
	return () => {
		const delayFnCtxId = ++ctxId;
		delayer(() => {
			if (ctxId === delayFnCtxId) callee();
		});
	};
}
//#endregion
//#region node_modules/.pnpm/@unhead+vue@2.1.17_vue@3.5.41_typescript@6.0.3_/node_modules/@unhead/vue/dist/shared/vue.N9zWjxoK.mjs
var VueResolver = (_, value) => {
	return isRef(value) ? toValue(value) : value;
};
//#endregion
//#region node_modules/.pnpm/@unhead+vue@2.1.17_vue@3.5.41_typescript@6.0.3_/node_modules/@unhead/vue/dist/shared/vue.Cd6dkybA.mjs
var headSymbol = "usehead";
// @__NO_SIDE_EFFECTS__
function vueInstall(head) {
	return { install(app) {
		app.config.globalProperties.$unhead = head;
		app.config.globalProperties.$head = head;
		app.provide(headSymbol, head);
	} }.install;
}
// @__NO_SIDE_EFFECTS__
function injectHead() {
	if (hasInjectionContext()) {
		const instance = inject(headSymbol);
		if (instance) return instance;
	}
	throw new Error("useHead() was called without provide context, ensure you call it through the setup() function.");
}
function useHead(input, options = {}) {
	const head = options.head || /* @__PURE__ */ injectHead();
	return head.ssr ? head.push(input || {}, options) : clientUseHead(head, input, options);
}
function clientUseHead(head, input, options = {}) {
	const scope = getCurrentScope();
	if (scope && !scope.active) return {
		patch() {},
		dispose() {},
		_poll() {}
	};
	const deactivated = ref(false);
	let entry;
	watchEffect(() => {
		const i = deactivated.value ? {} : walkResolver(input, VueResolver);
		if (entry) entry.patch(i);
		else entry = head.push(i, options);
	});
	if (getCurrentInstance()) {
		onBeforeUnmount(() => {
			entry.dispose();
		});
		onDeactivated(() => {
			deactivated.value = true;
		});
		onActivated(() => {
			deactivated.value = false;
		});
	}
	return entry;
}
//#endregion
//#region node_modules/.pnpm/@unhead+vue@2.1.17_vue@3.5.41_typescript@6.0.3_/node_modules/@unhead/vue/dist/client.mjs
// @__NO_SIDE_EFFECTS__
function createHead(options = {}) {
	const head = createHead$1({
		domOptions: { render: createDebouncedFn(() => renderDOMHead(head), (fn) => setTimeout(fn, 0)) },
		...options
	});
	head.install = /* @__PURE__ */ vueInstall(head);
	return head;
}
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/vue/plugins/head.js
var head_default = { install(app) {
	if (app._context.provides.usehead) return;
	const head = /* @__PURE__ */ createHead();
	app.use(head);
} };
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/vue/plugins/router.js
var router_default = { install(app, options) {
	if (options?.router && typeof options.router === "function") app.provide("nuxtui:router", options.router);
} };
//#endregion
//#region node_modules/.pnpm/@vueuse+core@14.4.0_vue@3.5.41_typescript@6.0.3_/node_modules/@vueuse/core/dist/index.js
var defaultWindow = isClient ? window : void 0;
isClient && window.document;
isClient && window.navigator;
isClient && window.location;
/**
* Get the dom element of a ref of element or Vue component instance
*
* @param elRef
*/
function unrefElement(elRef) {
	var _$el;
	const plain = toValue(elRef);
	return (_$el = plain === null || plain === void 0 ? void 0 : plain.$el) !== null && _$el !== void 0 ? _$el : plain;
}
function useEventListener(...args) {
	const register = (el, event, listener, options) => {
		el.addEventListener(event, listener, options);
		return () => el.removeEventListener(event, listener, options);
	};
	const firstParamTargets = computed(() => {
		const test = toArray(toValue(args[0])).filter((e) => e != null);
		return test.every((e) => typeof e !== "string") ? test : void 0;
	});
	return watchImmediate(() => {
		var _firstParamTargets$va, _firstParamTargets$va2;
		return [
			(_firstParamTargets$va = (_firstParamTargets$va2 = firstParamTargets.value) === null || _firstParamTargets$va2 === void 0 ? void 0 : _firstParamTargets$va2.map((e) => unrefElement(e))) !== null && _firstParamTargets$va !== void 0 ? _firstParamTargets$va : [defaultWindow].filter((e) => e != null),
			toArray(toValue(firstParamTargets.value ? args[1] : args[0])),
			toArray(unref(firstParamTargets.value ? args[2] : args[1])),
			toValue(firstParamTargets.value ? args[3] : args[2])
		];
	}, ([raw_targets, raw_events, raw_listeners, raw_options], _, onCleanup) => {
		if (!(raw_targets === null || raw_targets === void 0 ? void 0 : raw_targets.length) || !(raw_events === null || raw_events === void 0 ? void 0 : raw_events.length) || !(raw_listeners === null || raw_listeners === void 0 ? void 0 : raw_listeners.length)) return;
		const optionsClone = isObject(raw_options) ? { ...raw_options } : raw_options;
		const cleanups = raw_targets.flatMap((el) => raw_events.flatMap((event) => raw_listeners.map((listener) => register(el, event, listener, optionsClone))));
		onCleanup(() => {
			cleanups.forEach((fn) => fn());
		});
	}, { flush: "post" });
}
/**
* Mounted state in ref.
*
* @see https://vueuse.org/useMounted
*
* @__NO_SIDE_EFFECTS__
*/
function useMounted() {
	const isMounted = shallowRef(false);
	const instance = getCurrentInstance();
	if (instance) onMounted(() => {
		isMounted.value = true;
	}, instance);
	return isMounted;
}
/* @__NO_SIDE_EFFECTS__ */
function useSupported(callback) {
	const isMounted = useMounted();
	return computed(() => {
		isMounted.value;
		return Boolean(callback());
	});
}
function createKeyPredicate(keyFilter) {
	if (typeof keyFilter === "function") return keyFilter;
	else if (typeof keyFilter === "string") return (event) => event.key === keyFilter;
	else if (Array.isArray(keyFilter)) return (event) => keyFilter.includes(event.key);
	return () => true;
}
function onKeyStroke(...args) {
	let key;
	let handler;
	let options = {};
	if (args.length === 3) {
		key = args[0];
		handler = args[1];
		options = args[2];
	} else if (args.length === 2) if (typeof args[1] === "object") {
		key = true;
		handler = args[0];
		options = args[1];
	} else {
		key = args[0];
		handler = args[1];
	}
	else {
		key = true;
		handler = args[0];
	}
	const { target = defaultWindow, eventName = "keydown", passive = false, dedupe = false } = options;
	const predicate = createKeyPredicate(key);
	const listener = (e) => {
		if (e.repeat && toValue(dedupe)) return;
		if (predicate(e)) handler(e);
	};
	return useEventListener(target, eventName, listener, passive);
}
/**
* Call function on every `requestAnimationFrame`. With controls of pausing and resuming.
*
* @see https://vueuse.org/useRafFn
* @param fn
* @param options
*/
function useRafFn(fn, options = {}) {
	const { immediate = true, fpsLimit = null, window = defaultWindow, once = false } = options;
	const isActive = shallowRef(false);
	const intervalLimit = computed(() => {
		const limit = toValue(fpsLimit);
		return limit ? 1e3 / limit : null;
	});
	let previousFrameTimestamp = 0;
	let rafId = null;
	function loop(timestamp) {
		if (!isActive.value || !window) return;
		if (!previousFrameTimestamp) previousFrameTimestamp = timestamp;
		const delta = timestamp - previousFrameTimestamp;
		if (intervalLimit.value && delta < intervalLimit.value) {
			rafId = window.requestAnimationFrame(loop);
			return;
		}
		previousFrameTimestamp = timestamp;
		fn({
			delta,
			timestamp
		});
		if (once) {
			isActive.value = false;
			rafId = null;
			return;
		}
		rafId = window.requestAnimationFrame(loop);
	}
	function resume() {
		if (!isActive.value && window) {
			isActive.value = true;
			previousFrameTimestamp = 0;
			rafId = window.requestAnimationFrame(loop);
		}
	}
	function pause() {
		isActive.value = false;
		if (rafId != null && window) {
			window.cancelAnimationFrame(rafId);
			rafId = null;
		}
	}
	if (immediate) resume();
	tryOnScopeDispose(pause);
	return {
		isActive: shallowReadonly(isActive),
		pause,
		resume
	};
}
var ssrWidthSymbol = Symbol("vueuse-ssr-width");
/* @__NO_SIDE_EFFECTS__ */
function useSSRWidth() {
	const ssrWidth = hasInjectionContext() ? injectLocal(ssrWidthSymbol, null) : null;
	return typeof ssrWidth === "number" ? ssrWidth : void 0;
}
/**
* Reactive Media Query.
*
* @see https://vueuse.org/useMediaQuery
* @param query
* @param options
*/
function useMediaQuery(query, options = {}) {
	const { window = defaultWindow, ssrWidth = /* @__PURE__ */ useSSRWidth() } = options;
	const isSupported = /* @__PURE__ */ useSupported(() => window && "matchMedia" in window && typeof window.matchMedia === "function");
	const ssrSupport = shallowRef(typeof ssrWidth === "number");
	const mediaQuery = shallowRef();
	const matches = shallowRef(false);
	const handler = (event) => {
		matches.value = event.matches;
	};
	watchEffect(() => {
		if (ssrSupport.value) {
			ssrSupport.value = !isSupported.value;
			const queryStrings = toValue(query).split(",");
			matches.value = queryStrings.some((queryString) => {
				const not = queryString.includes("not all");
				const minWidth = queryString.match(/\(\s*min-width:\s*(-?\d+(?:\.\d*)?[a-z]+\s*)\)/);
				const maxWidth = queryString.match(/\(\s*max-width:\s*(-?\d+(?:\.\d*)?[a-z]+\s*)\)/);
				let res = Boolean(minWidth || maxWidth);
				if (minWidth && res) res = ssrWidth >= pxValue(minWidth[1]);
				if (maxWidth && res) res = ssrWidth <= pxValue(maxWidth[1]);
				return not ? !res : res;
			});
			return;
		}
		if (!isSupported.value) return;
		mediaQuery.value = window.matchMedia(toValue(query));
		matches.value = mediaQuery.value.matches;
	});
	useEventListener(mediaQuery, "change", handler, { passive: true });
	return computed(() => matches.value);
}
function cloneFnJSON(source) {
	return JSON.parse(JSON.stringify(source));
}
var _global = typeof globalThis !== "undefined" ? globalThis : typeof window !== "undefined" ? window : typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : {};
var globalKey = "__vueuse_ssr_handlers__";
var handlers = /* #__PURE__ */ getHandlers();
function getHandlers() {
	if (!(globalKey in _global)) _global[globalKey] = _global[globalKey] || {};
	return _global[globalKey];
}
function getSSRHandler(key, fallback) {
	return handlers[key] || fallback;
}
/**
* Reactive dark theme preference.
*
* @see https://vueuse.org/usePreferredDark
* @param [options]
*
* @__NO_SIDE_EFFECTS__
*/
function usePreferredDark(options) {
	return useMediaQuery("(prefers-color-scheme: dark)", options);
}
function guessSerializerType(rawInit) {
	return rawInit == null ? "any" : rawInit instanceof Set ? "set" : rawInit instanceof Map ? "map" : rawInit instanceof Date ? "date" : typeof rawInit === "boolean" ? "boolean" : typeof rawInit === "string" ? "string" : typeof rawInit === "object" ? "object" : !Number.isNaN(rawInit) ? "number" : "any";
}
var StorageSerializers = {
	boolean: {
		read: (v) => v === "true",
		write: (v) => String(v)
	},
	object: {
		read: (v) => JSON.parse(v),
		write: (v) => JSON.stringify(v)
	},
	number: {
		read: (v) => Number.parseFloat(v),
		write: (v) => String(v)
	},
	any: {
		read: (v) => v,
		write: (v) => String(v)
	},
	string: {
		read: (v) => v,
		write: (v) => String(v)
	},
	map: {
		read: (v) => new Map(JSON.parse(v)),
		write: (v) => JSON.stringify(Array.from(v.entries()))
	},
	set: {
		read: (v) => new Set(JSON.parse(v)),
		write: (v) => JSON.stringify(Array.from(v))
	},
	date: {
		read: (v) => new Date(v),
		write: (v) => v.toISOString()
	}
};
var customStorageEventName = "vueuse-storage";
/**
* Reactive LocalStorage/SessionStorage.
*
* @see https://vueuse.org/useStorage
*/
function useStorage(key, defaults, storage, options = {}) {
	var _options$serializer;
	const { flush = "pre", deep = true, listenToStorageChanges = true, writeDefaults = true, mergeDefaults = false, shallow, window = defaultWindow, eventFilter, onError = (e) => {
		console.error(e);
	}, initOnMounted } = options;
	const data = (shallow ? shallowRef : ref)(typeof defaults === "function" ? defaults() : defaults);
	const keyComputed = computed(() => toValue(key));
	if (!storage) try {
		storage = getSSRHandler("getDefaultStorage", () => defaultWindow === null || defaultWindow === void 0 ? void 0 : defaultWindow.localStorage)();
	} catch (e) {
		onError(e);
	}
	if (!storage) return data;
	const rawInit = toValue(defaults);
	const type = guessSerializerType(rawInit);
	const serializer = (_options$serializer = options.serializer) !== null && _options$serializer !== void 0 ? _options$serializer : StorageSerializers[type];
	const { pause: pauseWatch, resume: resumeWatch } = watchPausable(data, (newValue) => write(newValue), {
		flush,
		deep,
		eventFilter
	});
	watch(keyComputed, () => update(), { flush });
	let firstMounted = false;
	const onStorageEvent = (ev) => {
		if (initOnMounted && !firstMounted) return;
		update(ev);
	};
	const onStorageCustomEvent = (ev) => {
		if (initOnMounted && !firstMounted) return;
		updateFromCustomEvent(ev);
	};
	/**
	* The custom event is needed for same-document syncing when using custom
	* storage backends, but it doesn't work across different documents.
	*
	* TODO: Consider implementing a BroadcastChannel-based solution that fixes this.
	*/
	if (window && listenToStorageChanges) if (storage instanceof Storage) useEventListener(window, "storage", onStorageEvent, { passive: true });
	else useEventListener(window, customStorageEventName, onStorageCustomEvent);
	if (initOnMounted) tryOnMounted(() => {
		firstMounted = true;
		update();
	});
	else update();
	function dispatchWriteEvent(oldValue, newValue) {
		if (window) {
			const payload = {
				key: keyComputed.value,
				oldValue,
				newValue,
				storageArea: storage
			};
			window.dispatchEvent(storage instanceof Storage ? new StorageEvent("storage", payload) : new CustomEvent(customStorageEventName, { detail: payload }));
		}
	}
	function write(v) {
		try {
			const oldValue = storage.getItem(keyComputed.value);
			if (v == null) {
				dispatchWriteEvent(oldValue, null);
				storage.removeItem(keyComputed.value);
			} else {
				const serialized = serializer.write(v);
				if (oldValue !== serialized) {
					storage.setItem(keyComputed.value, serialized);
					dispatchWriteEvent(oldValue, serialized);
				}
			}
		} catch (e) {
			onError(e);
		}
	}
	function read(event) {
		const rawValue = event ? event.newValue : storage.getItem(keyComputed.value);
		if (rawValue == null) {
			if (writeDefaults && rawInit != null) storage.setItem(keyComputed.value, serializer.write(rawInit));
			return rawInit;
		} else if (!event && mergeDefaults) {
			const value = serializer.read(rawValue);
			if (typeof mergeDefaults === "function") return mergeDefaults(value, rawInit);
			else if (type === "object" && !Array.isArray(value)) return {
				...rawInit,
				...value
			};
			return value;
		} else if (typeof rawValue !== "string") return rawValue;
		else return serializer.read(rawValue);
	}
	function update(event) {
		if (event && event.storageArea !== storage) return;
		if (event && event.key == null) {
			data.value = rawInit;
			return;
		}
		if (event && event.key !== keyComputed.value) return;
		pauseWatch();
		try {
			const serializedData = serializer.write(data.value);
			if (event === void 0 || (event === null || event === void 0 ? void 0 : event.newValue) !== serializedData) data.value = read(event);
		} catch (e) {
			onError(e);
		} finally {
			if (event) nextTick(resumeWatch);
			else resumeWatch();
		}
	}
	function updateFromCustomEvent(event) {
		update(event.detail);
	}
	return data;
}
var CSS_DISABLE_TRANS = "*,*::before,*::after{-webkit-transition:none!important;-moz-transition:none!important;-o-transition:none!important;-ms-transition:none!important;transition:none!important}";
/**
* Reactive color mode with auto data persistence.
*
* @see https://vueuse.org/useColorMode
* @param options
*/
function useColorMode(options = {}) {
	const { selector = "html", attribute = "class", initialValue = "auto", window = defaultWindow, storage, storageKey = "vueuse-color-scheme", listenToStorageChanges = true, storageRef, emitAuto, disableTransition = true } = options;
	const modes = {
		auto: "",
		light: "light",
		dark: "dark",
		...options.modes || {}
	};
	const preferredDark = usePreferredDark({ window });
	const system = computed(() => preferredDark.value ? "dark" : "light");
	const store = storageRef || (storageKey == null ? toRef$1(initialValue) : useStorage(storageKey, initialValue, storage, {
		window,
		listenToStorageChanges
	}));
	const state = computed(() => store.value === "auto" ? system.value : store.value);
	const updateHTMLAttrs = getSSRHandler("updateHTMLAttrs", (selector, attribute, value) => {
		const el = typeof selector === "string" ? window === null || window === void 0 ? void 0 : window.document.querySelector(selector) : unrefElement(selector);
		if (!el) return;
		const classesToAdd = /* @__PURE__ */ new Set();
		const classesToRemove = /* @__PURE__ */ new Set();
		let attributeToChange = null;
		if (attribute === "class") {
			const current = value.split(/\s/g);
			Object.values(modes).flatMap((i) => (i || "").split(/\s/g)).filter(Boolean).forEach((v) => {
				if (current.includes(v)) classesToAdd.add(v);
				else classesToRemove.add(v);
			});
		} else attributeToChange = {
			key: attribute,
			value
		};
		if (classesToAdd.size === 0 && classesToRemove.size === 0 && attributeToChange === null) return;
		let style;
		if (disableTransition) {
			style = window.document.createElement("style");
			style.appendChild(document.createTextNode(CSS_DISABLE_TRANS));
			window.document.head.appendChild(style);
		}
		for (const c of classesToAdd) el.classList.add(c);
		for (const c of classesToRemove) el.classList.remove(c);
		if (attributeToChange) el.setAttribute(attributeToChange.key, attributeToChange.value);
		if (disableTransition) {
			window.getComputedStyle(style).opacity;
			document.head.removeChild(style);
		}
	});
	function defaultOnChanged(mode) {
		var _modes$mode;
		updateHTMLAttrs(selector, attribute, (_modes$mode = modes[mode]) !== null && _modes$mode !== void 0 ? _modes$mode : mode);
	}
	function onChanged(mode) {
		if (options.onChanged) options.onChanged(mode, defaultOnChanged);
		else defaultOnChanged(mode);
	}
	watch(state, onChanged, {
		flush: "post",
		immediate: true
	});
	tryOnMounted(() => onChanged(state.value));
	const auto = computed({
		get() {
			return emitAuto ? store.value : state.value;
		},
		set(v) {
			store.value = v;
		}
	});
	return Object.assign(auto, {
		store,
		system,
		state
	});
}
/**
* Reactive dark mode with auto data persistence.
*
* @see https://vueuse.org/useDark
* @param options
*/
function useDark(options = {}) {
	const { valueDark = "dark", valueLight = "" } = options;
	const mode = useColorMode({
		...options,
		onChanged: (mode, defaultHandler) => {
			var _options$onChanged;
			if (options.onChanged) (_options$onChanged = options.onChanged) === null || _options$onChanged === void 0 || _options$onChanged.call(options, mode === "dark", defaultHandler, mode);
			else defaultHandler(mode);
		},
		modes: {
			dark: valueDark,
			light: valueLight
		}
	});
	const system = computed(() => mode.system.value);
	return computed({
		get() {
			return mode.value === "dark";
		},
		set(v) {
			const modeVal = v ? "dark" : "light";
			if (system.value === modeVal) mode.value = "auto";
			else mode.value = modeVal;
		}
	});
}
Number.POSITIVE_INFINITY;
/**
* Shorthand for v-model binding, props + emit -> ref
*
* @see https://vueuse.org/useVModel
* @param props
* @param key (default 'modelValue')
* @param emit
* @param options
*
* @__NO_SIDE_EFFECTS__
*/
function useVModel(props, key, emit, options = {}) {
	var _vm$$emit, _vm$proxy;
	const { clone = false, passive = false, eventName, deep = false, defaultValue, shouldEmit } = options;
	const vm = getCurrentInstance();
	const _emit = emit || (vm === null || vm === void 0 ? void 0 : vm.emit) || (vm === null || vm === void 0 || (_vm$$emit = vm.$emit) === null || _vm$$emit === void 0 ? void 0 : _vm$$emit.bind(vm)) || (vm === null || vm === void 0 || (_vm$proxy = vm.proxy) === null || _vm$proxy === void 0 || (_vm$proxy = _vm$proxy.$emit) === null || _vm$proxy === void 0 ? void 0 : _vm$proxy.bind(vm === null || vm === void 0 ? void 0 : vm.proxy));
	let event = eventName;
	if (!key) key = "modelValue";
	event = event || `update:${key.toString()}`;
	const cloneFn = (val) => !clone ? val : typeof clone === "function" ? clone(val) : cloneFnJSON(val);
	const getValue = () => isDef(props[key]) ? cloneFn(props[key]) : defaultValue;
	const triggerEmit = (value) => {
		if (shouldEmit) {
			if (shouldEmit(value)) _emit(event, value);
		} else _emit(event, value);
	};
	if (passive) {
		const proxy = ref(getValue());
		let isUpdating = false;
		watch(() => props[key], (v) => {
			if (!isUpdating) {
				isUpdating = true;
				proxy.value = cloneFn(v);
				nextTick(() => isUpdating = false);
			}
		});
		watch(proxy, (v) => {
			if (!isUpdating && (v !== props[key] || deep)) triggerEmit(v);
		}, { deep });
		return proxy;
	} else return computed({
		get() {
			return getValue();
		},
		set(value) {
			triggerEmit(value);
		}
	});
}
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/vue/stubs/base.js
var state = {};
var useState = (key, init) => {
	if (state[key]) return state[key];
	const value = ref(init());
	state[key] = value;
	return value;
};
var hooks = createHooks();
function useNuxtApp() {
	return {
		isHydrating: true,
		payload: { serverRendered: true },
		hooks,
		hook: hooks.hook
	};
}
function defineNuxtPlugin(plugin) {
	return { install(app) {
		app.runWithContext(() => plugin({ vueApp: app }));
	} };
}
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/plugins/colors.js
var shades = [
	50,
	100,
	200,
	300,
	400,
	500,
	600,
	700,
	800,
	900,
	950
];
function getColor(color, shade) {
	if (color in colors && typeof colors[color] === "object" && shade in colors[color]) return colors[color][shade];
	return "";
}
function generateShades(key, value, prefix) {
	const prefixStr = prefix ? `${prefix}-` : "";
	return `${shades.map((shade) => `--ui-color-${key}-${shade}: var(--${prefixStr}color-${value === "neutral" ? "old-neutral" : value}-${shade}, ${getColor(value, shade)});`).join("\n  ")}`;
}
function generateColor(key, shade) {
	return `--ui-${key}: var(--ui-color-${key}-${shade});`;
}
function removeTemporaryColorsStyle() {
	document.querySelector("[data-nuxt-ui-colors]")?.remove();
}
var colors_default = defineNuxtPlugin(() => {
	const appConfig = useAppConfig();
	const nuxtApp = useNuxtApp();
	const root = computed(() => {
		const { neutral, ...colors2 } = appConfig.ui.colors;
		const prefix = appConfig.ui.prefix;
		return `@layer theme {
  :root, :host {
  ${Object.entries(appConfig.ui.colors).map(([key, value]) => generateShades(key, value, prefix)).join("\n  ")}
  }
  :root, :host, .light {
  ${Object.keys(colors2).map((key) => generateColor(key, 500)).join("\n  ")}
  }
  .dark {
  ${Object.keys(colors2).map((key) => generateColor(key, 400)).join("\n  ")}
  }
}`;
	});
	const headData = { style: [{
		innerHTML: root,
		tagPriority: "critical",
		id: "nuxt-ui-colors"
	}] };
	if (nuxtApp.isHydrating && !nuxtApp.payload.serverRendered) {
		const style = document.createElement("style");
		style.innerHTML = root.value;
		style.setAttribute("data-nuxt-ui-colors", "");
		document.head.appendChild(style);
		const unhook = (/* @__PURE__ */ injectHead()).hooks?.hook("dom:rendered", () => {
			removeTemporaryColorsStyle();
			unhook?.();
		});
	}
	useHead(headData);
});
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/vue/plugins/color-mode.js
var color_mode_default = { install() {
	useDark();
} };
//#endregion
//#region virtual:nuxt-ui-plugins
var virtual_nuxt_ui_plugins_default = { install(app, pluginOptions = {}) {
	app.use(icons_default, pluginOptions);
	app.use(head_default, pluginOptions);
	app.use(router_default, pluginOptions);
	app.use(colors_default, pluginOptions);
	app.use(color_mode_default, pluginOptions);
} };
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/shared/getActiveElement.js
function getActiveElement() {
	let activeElement = document.activeElement;
	if (activeElement == null) return null;
	while (activeElement != null && activeElement.shadowRoot != null && activeElement.shadowRoot.activeElement != null) activeElement = activeElement.shadowRoot.activeElement;
	return activeElement;
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/shared/nullish.js
function isNullish(value) {
	return value === null || value === void 0;
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/ConfigProvider/ConfigProvider.js
var [injectConfigProviderContext, provideConfigProviderContext] = /*#__PURE__*/ createContext("ConfigProvider");
var ConfigProvider_default = /* @__PURE__ */ defineComponent({
	inheritAttrs: false,
	__name: "ConfigProvider",
	props: {
		dir: {
			type: String,
			required: false,
			default: "ltr"
		},
		locale: {
			type: String,
			required: false,
			default: "en"
		},
		scrollBody: {
			type: [Boolean, Object],
			required: false,
			default: true
		},
		nonce: {
			type: String,
			required: false,
			default: void 0
		},
		teleportTo: {
			type: null,
			required: false,
			default: void 0
		},
		useId: {
			type: Function,
			required: false,
			default: void 0
		}
	},
	setup(__props) {
		const props = __props;
		const { dir, locale, scrollBody, nonce, teleportTo } = toRefs(props);
		provideConfigProviderContext({
			dir,
			locale,
			scrollBody,
			nonce,
			teleportTo,
			useId: props.useId
		});
		return (_ctx, _cache) => {
			return renderSlot(_ctx.$slots, "default");
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/shared/useForwardExpose.js
function useForwardExpose() {
	const instance = getCurrentInstance();
	const currentRef = ref();
	const currentElement = computed(() => resolveCurrentElement());
	onUpdated(() => {
		if (currentElement.value !== resolveCurrentElement()) triggerRef(currentRef);
	});
	function resolveCurrentElement() {
		return currentRef.value && "$el" in currentRef.value && ["#text", "#comment"].includes(currentRef.value.$el.nodeName) ? currentRef.value.$el.nextElementSibling : unrefElement(currentRef);
	}
	const localExpose = Object.assign({}, instance.exposed);
	const ret = {};
	for (const key in instance.props) Object.defineProperty(ret, key, {
		enumerable: true,
		configurable: true,
		get: () => instance.props[key]
	});
	if (Object.keys(localExpose).length > 0) for (const key in localExpose) Object.defineProperty(ret, key, {
		enumerable: true,
		configurable: true,
		get: () => localExpose[key]
	});
	Object.defineProperty(ret, "$el", {
		enumerable: true,
		configurable: true,
		get: () => instance.vnode.el
	});
	instance.exposed = ret;
	function forwardRef(ref$1) {
		currentRef.value = ref$1;
		if (!ref$1) return;
		Object.defineProperty(ret, "$el", {
			enumerable: true,
			configurable: true,
			get: () => ref$1 instanceof Element ? ref$1 : ref$1.$el
		});
		if (!(ref$1 instanceof Element) && !Object.hasOwn(ref$1, "$el")) {
			const childExposed = ref$1.$.exposed;
			const merged = Object.assign({}, ret);
			for (const key in childExposed) Object.defineProperty(merged, key, {
				enumerable: true,
				configurable: true,
				get: () => childExposed[key]
			});
			instance.exposed = merged;
		}
	}
	return {
		forwardRef,
		currentRef,
		currentElement
	};
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/shared/useStateMachine.js
/**
* The `useStateMachine` function is a TypeScript function that creates a state machine and returns the
* current state and a dispatch function to update the state based on events.
* @param initialState - The `initialState` parameter is the initial state of the state machine. It
* represents the starting point of the state machine's state.
* @param machine - The `machine` parameter is an object that represents a state machine. It should
* have keys that correspond to the possible states of the machine, and the values should be objects
* that represent the possible events and their corresponding next states.
* @returns The `useStateMachine` function returns an object with two properties: `state` and
* `dispatch`.
*/
function useStateMachine(initialState, machine) {
	const state = ref(initialState);
	function reducer(event) {
		return machine[state.value][event] ?? state.value;
	}
	const dispatch = (event) => {
		state.value = reducer(event);
	};
	return {
		state,
		dispatch
	};
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Presence/usePresence.js
function usePresence(present, node) {
	const stylesRef = ref({});
	const prevAnimationNameRef = ref("none");
	const prevPresentRef = ref(present);
	const initialState = present.value ? "mounted" : "unmounted";
	let timeoutId;
	const ownerWindow = node.value?.ownerDocument.defaultView ?? defaultWindow;
	const { state, dispatch } = useStateMachine(initialState, {
		mounted: {
			UNMOUNT: "unmounted",
			ANIMATION_OUT: "unmountSuspended"
		},
		unmountSuspended: {
			MOUNT: "mounted",
			ANIMATION_END: "unmounted"
		},
		unmounted: { MOUNT: "mounted" }
	});
	const dispatchCustomEvent = (name) => {
		if (isClient) {
			const customEvent = new CustomEvent(name, {
				bubbles: false,
				cancelable: false
			});
			node.value?.dispatchEvent(customEvent);
		}
	};
	watch(present, async (currentPresent, prevPresent) => {
		const hasPresentChanged = prevPresent !== currentPresent;
		await nextTick();
		if (hasPresentChanged) {
			const prevAnimationName = prevAnimationNameRef.value;
			const currentAnimationName = getAnimationName(node.value);
			if (currentPresent) {
				dispatch("MOUNT");
				dispatchCustomEvent("enter");
				if (currentAnimationName === "none") dispatchCustomEvent("after-enter");
			} else if (currentAnimationName === "none" || currentAnimationName === "undefined" || stylesRef.value?.display === "none") {
				dispatch("UNMOUNT");
				dispatchCustomEvent("leave");
				dispatchCustomEvent("after-leave");
			} else if (prevPresent && prevAnimationName !== currentAnimationName) {
				dispatch("ANIMATION_OUT");
				dispatchCustomEvent("leave");
			} else {
				dispatch("UNMOUNT");
				dispatchCustomEvent("after-leave");
			}
		}
	}, { immediate: true });
	/**
	* Triggering an ANIMATION_OUT during an ANIMATION_IN will fire an `animationcancel`
	* event for ANIMATION_IN after we have entered `unmountSuspended` state. So, we
	* make sure we only trigger ANIMATION_END for the currently active animation.
	*/
	const handleAnimationEnd = (event) => {
		const currentAnimationName = getAnimationName(node.value);
		const isCurrentAnimation = currentAnimationName.includes(CSS.escape(event.animationName));
		const directionName = state.value === "mounted" ? "enter" : "leave";
		if (event.target === node.value && isCurrentAnimation) {
			dispatchCustomEvent(`after-${directionName}`);
			dispatch("ANIMATION_END");
			if (!prevPresentRef.value) {
				const currentFillMode = node.value.style.animationFillMode;
				node.value.style.animationFillMode = "forwards";
				timeoutId = ownerWindow?.setTimeout(() => {
					if (node.value?.style.animationFillMode === "forwards") node.value.style.animationFillMode = currentFillMode;
				});
			}
		}
		if (event.target === node.value && currentAnimationName === "none") dispatch("ANIMATION_END");
	};
	const handleAnimationStart = (event) => {
		if (event.target === node.value) prevAnimationNameRef.value = getAnimationName(node.value);
	};
	const watcher = watch(node, (newNode, oldNode) => {
		if (newNode) {
			stylesRef.value = getComputedStyle(newNode);
			newNode.addEventListener("animationstart", handleAnimationStart);
			newNode.addEventListener("animationcancel", handleAnimationEnd);
			newNode.addEventListener("animationend", handleAnimationEnd);
		} else {
			dispatch("ANIMATION_END");
			if (timeoutId !== void 0) ownerWindow?.clearTimeout(timeoutId);
			oldNode?.removeEventListener("animationstart", handleAnimationStart);
			oldNode?.removeEventListener("animationcancel", handleAnimationEnd);
			oldNode?.removeEventListener("animationend", handleAnimationEnd);
		}
	}, { immediate: true });
	const stateWatcher = watch(state, () => {
		const currentAnimationName = getAnimationName(node.value);
		prevAnimationNameRef.value = state.value === "mounted" ? currentAnimationName : "none";
	});
	onUnmounted(() => {
		watcher();
		stateWatcher();
		if (node.value) {
			node.value.removeEventListener("animationstart", handleAnimationStart);
			node.value.removeEventListener("animationcancel", handleAnimationEnd);
			node.value.removeEventListener("animationend", handleAnimationEnd);
		}
		if (timeoutId !== void 0) ownerWindow?.clearTimeout(timeoutId);
	});
	return { isPresent: computed(() => ["mounted", "unmountSuspended"].includes(state.value)) };
}
function getAnimationName(node) {
	return node ? getComputedStyle(node).animationName || "none" : "none";
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Presence/Presence.js
var Presence_default = /*#__PURE__*/ defineComponent({
	name: "Presence",
	props: {
		present: {
			type: Boolean,
			required: true
		},
		forceMount: { type: Boolean }
	},
	slots: {},
	setup(props, { slots, expose }) {
		const { present, forceMount } = toRefs(props);
		const node = ref();
		const { isPresent } = usePresence(present, node);
		expose({ present: isPresent });
		let children = slots.default({ present: isPresent.value });
		children = renderSlotFragments(children || []);
		const instance = getCurrentInstance();
		if (children && children?.length > 1) {
			const componentName = instance?.parent?.type.name ? `<${instance.parent.type.name} />` : "component";
			throw new Error([
				`Detected an invalid children for \`${componentName}\` for  \`Presence\` component.`,
				"",
				"Note: Presence works similarly to `v-if` directly, but it waits for animation/transition to finished before unmounting. So it expect only one direct child of valid VNode type.",
				"You can apply a few solutions:",
				["Provide a single child element so that `presence` directive attach correctly.", "Ensure the first child is an actual element instead of a raw text node or comment node."].map((line) => `  - ${line}`).join("\n")
			].join("\n"));
		}
		return () => {
			if (forceMount.value || present.value || isPresent.value) return h(slots.default({ present: isPresent.value })[0], { ref: (v) => {
				const el = unrefElement(v);
				if (typeof el?.hasAttribute === "undefined") return el;
				if (el?.hasAttribute("data-reka-popper-content-wrapper")) node.value = el.firstElementChild;
				else node.value = el;
				return el;
			} });
			else return null;
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Primitive/usePrimitiveElement.js
function usePrimitiveElement() {
	const primitiveElement = ref();
	return {
		primitiveElement,
		currentElement: computed(() => ["#text", "#comment"].includes(primitiveElement.value?.$el.nodeName) ? primitiveElement.value?.$el.nextElementSibling : unrefElement(primitiveElement))
	};
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/DismissableLayer/DismissableLayer.js
var context = /*#__PURE__*/ reactive({
	layersRoot: /* @__PURE__ */ new Set(),
	layersWithOutsidePointerEventsDisabled: /* @__PURE__ */ new Set(),
	originalBodyPointerEvents: void 0,
	branches: /* @__PURE__ */ new Set()
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/DismissableLayer/DismissableLayerBranch.js
var DismissableLayerBranch_default = /* @__PURE__ */ defineComponent({
	__name: "DismissableLayerBranch",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const { forwardRef, currentElement } = useForwardExpose();
		onMounted(() => {
			context.branches.add(currentElement.value);
		});
		onUnmounted(() => {
			context.branches.delete(currentElement.value);
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps({ ref: unref(forwardRef) }, props), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/FocusScope/utils.js
/**
* Attempts focusing the first element in a list of candidates.
* Stops when focus has actually moved.
*/
function focusFirst(candidates, { select = false } = {}) {
	const previouslyFocusedElement = getActiveElement();
	for (const candidate of candidates) {
		focus(candidate, { select });
		if (getActiveElement() !== previouslyFocusedElement) return true;
	}
}
/**
* Returns a list of potential tabbable candidates.
*
* NOTE: This is only a close approximation. For example it doesn't take into account cases like when
* elements are not visible. This cannot be worked out easily by just reading a property, but rather
* necessitate runtime knowledge (computed styles, etc). We deal with these cases separately.
*
* See: https://developer.mozilla.org/en-US/docs/Web/API/TreeWalker
* Credit: https://github.com/discord/focus-layers/blob/master/src/util/wrapFocus.tsx#L1
*/
function getTabbableCandidates(container) {
	const nodes = [];
	const walker = document.createTreeWalker(container, NodeFilter.SHOW_ELEMENT, { acceptNode: (node) => {
		const isHiddenInput = node.tagName === "INPUT" && node.type === "hidden";
		if (node.disabled || node.hidden || isHiddenInput) return NodeFilter.FILTER_SKIP;
		return node.tabIndex >= 0 ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP;
	} });
	while (walker.nextNode()) nodes.push(walker.currentNode);
	return nodes;
}
function isSelectableInput(element) {
	return element instanceof HTMLInputElement && "select" in element;
}
function focus(element, { select = false } = {}) {
	if (element && element.focus) {
		const previouslyFocusedElement = getActiveElement();
		element.focus({ preventScroll: true });
		if (element !== previouslyFocusedElement && isSelectableInput(element) && select) element.select();
	}
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Teleport/Teleport.js
var Teleport_default = /* @__PURE__ */ defineComponent({
	__name: "Teleport",
	props: {
		to: {
			type: null,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		defer: {
			type: Boolean,
			required: false
		},
		forceMount: {
			type: Boolean,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const configContext = injectConfigProviderContext({});
		const target = computed(() => props.to ?? configContext.teleportTo?.value ?? "body");
		const isMounted = useMounted();
		return (_ctx, _cache) => {
			return unref(isMounted) || _ctx.forceMount ? (openBlock(), createBlock(Teleport, {
				key: 0,
				to: target.value,
				disabled: _ctx.disabled,
				defer: _ctx.defer
			}, [renderSlot(_ctx.$slots, "default")], 8, [
				"to",
				"disabled",
				"defer"
			])) : createCommentVNode("v-if", true);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Collection/Collection.js
var ITEM_DATA_ATTR = "data-reka-collection-item";
function useCollection(options = {}) {
	const { key = "", isProvider = false } = options;
	const injectionKey = `${key}CollectionProvider`;
	let context;
	if (isProvider) {
		const itemMap = ref(/* @__PURE__ */ new Map());
		context = {
			collectionRef: ref(),
			itemMap
		};
		provide(injectionKey, context);
	} else context = inject(injectionKey);
	const getItems = (includeDisabledItem = false) => {
		const collectionNode = context.collectionRef.value;
		if (!collectionNode) return [];
		const orderedNodes = Array.from(collectionNode.querySelectorAll(`[${ITEM_DATA_ATTR}]`));
		const orderMap = new Map(orderedNodes.map((node, index) => [node, index]));
		const orderedItems = Array.from(context.itemMap.value.values()).sort((a, b) => (orderMap.get(a.ref) ?? -1) - (orderMap.get(b.ref) ?? -1));
		if (includeDisabledItem) return orderedItems;
		else return orderedItems.filter((i) => i.ref.dataset.disabled !== "");
	};
	const CollectionSlot = /*#__PURE__*/ defineComponent({
		name: "CollectionSlot",
		inheritAttrs: false,
		setup(_, { slots, attrs }) {
			const { primitiveElement, currentElement } = usePrimitiveElement();
			watch(currentElement, () => {
				context.collectionRef.value = currentElement.value;
			});
			return () => h(Slot, {
				ref: primitiveElement,
				...attrs
			}, slots);
		}
	});
	const CollectionItem = /*#__PURE__*/ defineComponent({
		name: "CollectionItem",
		inheritAttrs: false,
		props: { value: { validator: () => true } },
		setup(props, { slots, attrs }) {
			const { primitiveElement, currentElement } = usePrimitiveElement();
			watchEffect((cleanupFn) => {
				if (currentElement.value) {
					const key$1 = markRaw(currentElement.value);
					context.itemMap.value.set(key$1, {
						ref: currentElement.value,
						value: props.value
					});
					cleanupFn(() => context.itemMap.value.delete(key$1));
				}
			});
			return () => h(Slot, {
				...attrs,
				[ITEM_DATA_ATTR]: "",
				ref: primitiveElement
			}, slots);
		}
	});
	return {
		getItems,
		reactiveItems: computed(() => Array.from(context.itemMap.value.values())),
		itemMapSize: computed(() => context.itemMap.value.size),
		CollectionSlot,
		CollectionItem
	};
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/VisuallyHidden/VisuallyHidden.js
var VisuallyHidden_default = /* @__PURE__ */ defineComponent({
	__name: "VisuallyHidden",
	props: {
		feature: {
			type: String,
			required: false,
			default: "focusable"
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "span"
		}
	},
	setup(__props) {
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), {
				as: _ctx.as,
				"as-child": _ctx.asChild,
				"aria-hidden": _ctx.feature === "focusable" || _ctx.feature === "fully-hidden" ? "true" : void 0,
				"data-hidden": _ctx.feature === "fully-hidden" ? "" : void 0,
				tabindex: _ctx.feature === "fully-hidden" ? "-1" : void 0,
				style: {
					position: "absolute",
					border: 0,
					width: "1px",
					height: "1px",
					padding: 0,
					margin: "-1px",
					overflow: "hidden",
					clip: "rect(0, 0, 0, 0)",
					clipPath: "inset(50%)",
					whiteSpace: "nowrap",
					wordWrap: "normal",
					top: "-1px",
					left: "-1px"
				}
			}, {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 8, [
				"as",
				"as-child",
				"aria-hidden",
				"data-hidden",
				"tabindex"
			]);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Progress/ProgressRoot.js
var DEFAULT_MAX = 100;
var [injectProgressRootContext, provideProgressRootContext] = /*#__PURE__*/ createContext("ProgressRoot");
var isNumber = (v) => typeof v === "number";
function validateValue(value, max) {
	if (isNullish(value) || isNumber(value) && !Number.isNaN(value) && value <= max && value >= 0) return value;
	console.error(`Invalid prop \`value\` of value \`${value}\` supplied to \`ProgressRoot\`. The \`value\` prop must be:
  - a positive number
  - less than the value passed to \`max\` (or ${DEFAULT_MAX} if no \`max\` prop is set)
  - \`null\`  or \`undefined\` if the progress is indeterminate.

Defaulting to \`null\`.`);
	return null;
}
function validateMax(max) {
	if (isNumber(max) && !Number.isNaN(max) && max > 0) return max;
	console.error(`Invalid prop \`max\` of value \`${max}\` supplied to \`ProgressRoot\`. Only numbers greater than 0 are valid max values. Defaulting to \`${DEFAULT_MAX}\`.`);
	return DEFAULT_MAX;
}
var ProgressRoot_default = /* @__PURE__ */ defineComponent({
	__name: "ProgressRoot",
	props: {
		modelValue: {
			type: [Number, null],
			required: false
		},
		max: {
			type: Number,
			required: false,
			default: DEFAULT_MAX
		},
		getValueLabel: {
			type: Function,
			required: false,
			default: (value, max) => isNumber(value) ? `${Math.round(value / max * DEFAULT_MAX)}%` : void 0
		},
		getValueText: {
			type: Function,
			required: false
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	emits: ["update:modelValue", "update:max"],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emit = __emit;
		useForwardExpose();
		const modelValue = useVModel(props, "modelValue", emit, { passive: props.modelValue === void 0 });
		const max = useVModel(props, "max", emit, { passive: props.max === void 0 });
		watch(() => modelValue.value, async (value) => {
			const correctedValue = validateValue(value, props.max);
			if (correctedValue !== value) {
				await nextTick();
				modelValue.value = correctedValue;
			}
		}, { immediate: true });
		watch(() => props.max, (newMax) => {
			const correctedMax = validateMax(props.max);
			if (correctedMax !== newMax) max.value = correctedMax;
		}, { immediate: true });
		const progressState = computed(() => {
			if (isNullish(modelValue.value)) return "indeterminate";
			if (modelValue.value === max.value) return "complete";
			return "loading";
		});
		provideProgressRootContext({
			modelValue,
			max,
			progressState
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), {
				"as-child": _ctx.asChild,
				as: _ctx.as,
				"aria-valuemax": unref(max),
				"aria-valuemin": 0,
				"aria-valuenow": isNumber(unref(modelValue)) ? unref(modelValue) : void 0,
				"aria-valuetext": _ctx.getValueText?.(unref(modelValue), unref(max)),
				"aria-label": _ctx.getValueLabel(unref(modelValue), unref(max)),
				role: "progressbar",
				"data-state": progressState.value,
				"data-value": unref(modelValue) ?? void 0,
				"data-max": unref(max)
			}, {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default", { modelValue: unref(modelValue) })]),
				_: 3
			}, 8, [
				"as-child",
				"as",
				"aria-valuemax",
				"aria-valuenow",
				"aria-valuetext",
				"aria-label",
				"data-state",
				"data-value",
				"data-max"
			]);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Progress/ProgressIndicator.js
var ProgressIndicator_default = /* @__PURE__ */ defineComponent({
	__name: "ProgressIndicator",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const rootContext = injectProgressRootContext();
		useForwardExpose();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), mergeProps(props, {
				"data-state": unref(rootContext).progressState.value,
				"data-value": unref(rootContext).modelValue?.value ?? void 0,
				"data-max": unref(rootContext).max.value
			}), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16, [
				"data-state",
				"data-value",
				"data-max"
			]);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastAnnounceExclude.js
var ToastAnnounceExclude_default = /* @__PURE__ */ defineComponent({
	__name: "ToastAnnounceExclude",
	props: {
		altText: {
			type: String,
			required: false
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	setup(__props) {
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), {
				as: _ctx.as,
				"as-child": _ctx.asChild,
				"data-reka-toast-announce-exclude": "",
				"data-reka-toast-announce-alt": _ctx.altText || void 0
			}, {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 8, [
				"as",
				"as-child",
				"data-reka-toast-announce-alt"
			]);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastProvider.js
var [injectToastProviderContext, provideToastProviderContext] = /*#__PURE__*/ createContext("ToastProvider");
var ToastProvider_default = /* @__PURE__ */ defineComponent({
	inheritAttrs: false,
	__name: "ToastProvider",
	props: {
		label: {
			type: String,
			required: false,
			default: "Notification"
		},
		duration: {
			type: Number,
			required: false,
			default: 5e3
		},
		disableSwipe: {
			type: Boolean,
			required: false
		},
		swipeDirection: {
			type: String,
			required: false,
			default: "right"
		},
		swipeThreshold: {
			type: Number,
			required: false,
			default: 50
		}
	},
	setup(__props) {
		const props = __props;
		const { label, duration, disableSwipe, swipeDirection, swipeThreshold } = toRefs(props);
		useCollection({ isProvider: true });
		const viewport = ref();
		const toastCount = ref(0);
		const isFocusedToastEscapeKeyDownRef = ref(false);
		const isClosePausedRef = ref(false);
		if (props.label && typeof props.label === "string" && !props.label.trim()) throw new Error("Invalid prop `label` supplied to `ToastProvider`. Expected non-empty `string`.");
		provideToastProviderContext({
			label,
			duration,
			disableSwipe,
			swipeDirection,
			swipeThreshold,
			toastCount,
			viewport,
			onViewportChange(el) {
				viewport.value = el;
			},
			onToastAdd() {
				toastCount.value++;
			},
			onToastRemove() {
				toastCount.value--;
			},
			isFocusedToastEscapeKeyDownRef,
			isClosePausedRef
		});
		return (_ctx, _cache) => {
			return renderSlot(_ctx.$slots, "default");
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastAnnounce.js
var ToastAnnounce_default = /* @__PURE__ */ defineComponent({
	__name: "ToastAnnounce",
	setup(__props) {
		const providerContext = injectToastProviderContext();
		const isAnnounced = useTimeout(1e3);
		const renderAnnounceText = ref(false);
		let raf1 = 0;
		let raf2 = 0;
		if (isClient) {
			raf1 = requestAnimationFrame(() => {
				raf2 = requestAnimationFrame(() => {
					renderAnnounceText.value = true;
				});
			});
			onScopeDispose(() => {
				cancelAnimationFrame(raf1);
				cancelAnimationFrame(raf2);
			});
		}
		return (_ctx, _cache) => {
			return unref(isAnnounced) || renderAnnounceText.value ? (openBlock(), createBlock(unref(VisuallyHidden_default), {
				key: 0,
				feature: "fully-hidden"
			}, {
				default: withCtx(() => [createTextVNode(toDisplayString(unref(providerContext).label.value) + " ", 1), renderSlot(_ctx.$slots, "default")]),
				_: 3
			})) : createCommentVNode("v-if", true);
		};
	}
});
var VIEWPORT_PAUSE = "toast.viewportPause";
var VIEWPORT_RESUME = "toast.viewportResume";
function handleAndDispatchCustomEvent(name, handler, detail) {
	const currentTarget = detail.originalEvent.currentTarget;
	const event = new CustomEvent(name, {
		bubbles: false,
		cancelable: true,
		detail
	});
	if (handler) currentTarget.addEventListener(name, handler, { once: true });
	currentTarget.dispatchEvent(event);
}
function isDeltaInDirection(delta, direction, threshold = 0) {
	const deltaX = Math.abs(delta.x);
	const deltaY = Math.abs(delta.y);
	const isDeltaX = deltaX > deltaY;
	if (direction === "left" || direction === "right") return isDeltaX && deltaX > threshold;
	else return !isDeltaX && deltaY > threshold;
}
function isHTMLElement(node) {
	return node.nodeType === node.ELEMENT_NODE;
}
function getAnnounceTextContent(container) {
	const textContent = [];
	Array.from(container.childNodes).forEach((node) => {
		if (node.nodeType === node.TEXT_NODE && node.textContent) textContent.push(node.textContent);
		if (isHTMLElement(node)) {
			const isHidden = node.ariaHidden || node.hidden || node.style.display === "none";
			const isExcluded = node.dataset.rekaToastAnnounceExclude === "";
			if (!isHidden) if (isExcluded) {
				const altText = node.dataset.rekaToastAnnounceAlt;
				if (altText) textContent.push(altText);
			} else textContent.push(...getAnnounceTextContent(node));
		}
	});
	return textContent;
}
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastRootImpl.js
var [injectToastRootContext, provideToastRootContext] = /*#__PURE__*/ createContext("ToastRoot");
var ToastRootImpl_default = /* @__PURE__ */ defineComponent({
	inheritAttrs: false,
	__name: "ToastRootImpl",
	props: {
		type: {
			type: String,
			required: false
		},
		open: {
			type: Boolean,
			required: false,
			default: false
		},
		duration: {
			type: Number,
			required: false
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "li"
		}
	},
	emits: [
		"close",
		"escapeKeyDown",
		"pause",
		"resume",
		"swipeStart",
		"swipeMove",
		"swipeCancel",
		"swipeEnd"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const { forwardRef, currentElement } = useForwardExpose();
		const { CollectionItem } = useCollection();
		const providerContext = injectToastProviderContext();
		const pointerStartRef = ref(null);
		const swipeDeltaRef = ref(null);
		const duration = computed(() => typeof props.duration === "number" ? props.duration : providerContext.duration.value);
		const closeTimerStartTimeRef = ref(0);
		const closeTimerRemainingTimeRef = ref(duration.value);
		const closeTimerRef = ref(0);
		const remainingTime = ref(duration.value);
		const remainingRaf = useRafFn(() => {
			const elapsedTime = Date.now() - closeTimerStartTimeRef.value;
			remainingTime.value = Math.max(closeTimerRemainingTimeRef.value - elapsedTime, 0);
		}, { fpsLimit: 60 });
		function startTimer(duration$1) {
			if (duration$1 <= 0 || duration$1 === Number.POSITIVE_INFINITY) return;
			if (!isClient) return;
			window.clearTimeout(closeTimerRef.value);
			closeTimerStartTimeRef.value = Date.now();
			closeTimerRef.value = window.setTimeout(handleClose, duration$1);
		}
		function handleClose(event) {
			const isNonPointerEvent = event?.pointerType === "";
			if (currentElement.value?.contains(getActiveElement()) && isNonPointerEvent) providerContext.viewport.value?.focus();
			if (isNonPointerEvent) providerContext.isClosePausedRef.value = false;
			emits("close");
		}
		const announceTextContent = computed(() => currentElement.value ? getAnnounceTextContent(currentElement.value) : null);
		if (props.type && !["foreground", "background"].includes(props.type)) throw new Error("Invalid prop `type` supplied to `Toast`. Expected `foreground | background`.");
		watchEffect((cleanupFn) => {
			const viewport = providerContext.viewport.value;
			if (viewport) {
				const handleResume = () => {
					startTimer(closeTimerRemainingTimeRef.value);
					remainingRaf.resume();
					emits("resume");
				};
				const handlePause = () => {
					const elapsedTime = Date.now() - closeTimerStartTimeRef.value;
					closeTimerRemainingTimeRef.value = closeTimerRemainingTimeRef.value - elapsedTime;
					window.clearTimeout(closeTimerRef.value);
					remainingRaf.pause();
					emits("pause");
				};
				viewport.addEventListener(VIEWPORT_PAUSE, handlePause);
				viewport.addEventListener(VIEWPORT_RESUME, handleResume);
				return () => {
					viewport.removeEventListener(VIEWPORT_PAUSE, handlePause);
					viewport.removeEventListener(VIEWPORT_RESUME, handleResume);
				};
			}
		});
		watch(() => [props.open, duration.value], () => {
			closeTimerRemainingTimeRef.value = duration.value;
			if (props.open && !providerContext.isClosePausedRef.value) startTimer(duration.value);
		}, { immediate: true });
		onKeyStroke("Escape", (event) => {
			emits("escapeKeyDown", event);
			if (!event.defaultPrevented) {
				providerContext.isFocusedToastEscapeKeyDownRef.value = true;
				handleClose();
			}
		});
		onMounted(() => {
			providerContext.onToastAdd();
		});
		onUnmounted(() => {
			providerContext.onToastRemove();
		});
		provideToastRootContext({ onClose: handleClose });
		return (_ctx, _cache) => {
			return openBlock(), createElementBlock(Fragment, null, [announceTextContent.value ? (openBlock(), createBlock(ToastAnnounce_default, {
				key: 0,
				role: "alert",
				"aria-live": _ctx.type === "foreground" ? "assertive" : "polite"
			}, {
				default: withCtx(() => [createCommentVNode("\n      Render each chunk as its own text node so screen readers get the\n      natural pause break between nodes (see comment in utils.ts).\n      Interpolating the array directly with `{{ announceTextContent }}`\n      would route through Vue's `toDisplayString`, which JSON-stringifies\n      arrays — the live region would then announce literal `[`, quotes\n      and commas instead of the toast title and description.\n    "), (openBlock(true), createElementBlock(Fragment, null, renderList(announceTextContent.value, (text, i) => {
					return openBlock(), createElementBlock(Fragment, { key: i }, [createTextVNode(toDisplayString(text), 1)], 64);
				}), 128))]),
				_: 1
			}, 8, ["aria-live"])) : createCommentVNode("v-if", true), unref(providerContext).viewport.value ? (openBlock(), createBlock(Teleport, {
				key: 1,
				to: unref(providerContext).viewport.value
			}, [createVNode(unref(CollectionItem), null, {
				default: withCtx(() => [createVNode(unref(Primitive), mergeProps({
					ref: unref(forwardRef),
					tabindex: "0"
				}, _ctx.$attrs, {
					as: _ctx.as,
					"as-child": _ctx.asChild,
					"data-state": _ctx.open ? "open" : "closed",
					"data-swipe-direction": unref(providerContext).swipeDirection.value,
					style: unref(providerContext).disableSwipe.value ? void 0 : {
						userSelect: "none",
						touchAction: "none"
					},
					onPointerdown: _cache[0] || (_cache[0] = withModifiers((event) => {
						if (unref(providerContext).disableSwipe.value) return;
						pointerStartRef.value = {
							x: event.clientX,
							y: event.clientY
						};
					}, ["left"])),
					onPointermove: _cache[1] || (_cache[1] = (event) => {
						if (unref(providerContext).disableSwipe.value || !pointerStartRef.value) return;
						const x = event.clientX - pointerStartRef.value.x;
						const y = event.clientY - pointerStartRef.value.y;
						const hasSwipeMoveStarted = Boolean(swipeDeltaRef.value);
						const isHorizontalSwipe = ["left", "right"].includes(unref(providerContext).swipeDirection.value);
						const clamp = ["left", "up"].includes(unref(providerContext).swipeDirection.value) ? Math.min : Math.max;
						const clampedX = isHorizontalSwipe ? clamp(0, x) : 0;
						const clampedY = !isHorizontalSwipe ? clamp(0, y) : 0;
						const moveStartBuffer = event.pointerType === "touch" ? 10 : 2;
						const delta = {
							x: clampedX,
							y: clampedY
						};
						const eventDetail = {
							originalEvent: event,
							delta
						};
						if (hasSwipeMoveStarted) {
							swipeDeltaRef.value = delta;
							unref(handleAndDispatchCustomEvent)(unref("toast.swipeMove"), (ev) => emits("swipeMove", ev), eventDetail);
						} else if (unref(isDeltaInDirection)(delta, unref(providerContext).swipeDirection.value, moveStartBuffer)) {
							swipeDeltaRef.value = delta;
							unref(handleAndDispatchCustomEvent)(unref("toast.swipeStart"), (ev) => emits("swipeStart", ev), eventDetail);
							event.target.setPointerCapture(event.pointerId);
						} else if (Math.abs(x) > moveStartBuffer || Math.abs(y) > moveStartBuffer) pointerStartRef.value = null;
					}),
					onPointerup: _cache[2] || (_cache[2] = (event) => {
						if (unref(providerContext).disableSwipe.value) return;
						const delta = swipeDeltaRef.value;
						const target = event.target;
						if (target.hasPointerCapture(event.pointerId)) target.releasePointerCapture(event.pointerId);
						swipeDeltaRef.value = null;
						pointerStartRef.value = null;
						if (delta) {
							const toast = event.currentTarget;
							const eventDetail = {
								originalEvent: event,
								delta
							};
							if (unref(isDeltaInDirection)(delta, unref(providerContext).swipeDirection.value, unref(providerContext).swipeThreshold.value)) unref(handleAndDispatchCustomEvent)(unref("toast.swipeEnd"), (ev) => emits("swipeEnd", ev), eventDetail);
							else unref(handleAndDispatchCustomEvent)(unref("toast.swipeCancel"), (ev) => emits("swipeCancel", ev), eventDetail);
							toast?.addEventListener("click", (event$1) => event$1.preventDefault(), { once: true });
						}
					})
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default", {
						remaining: remainingTime.value,
						duration: duration.value
					})]),
					_: 3
				}, 16, [
					"as",
					"as-child",
					"data-state",
					"data-swipe-direction",
					"style"
				])]),
				_: 3
			})], 8, ["to"])) : createCommentVNode("v-if", true)], 64);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastClose.js
var ToastClose_default = /* @__PURE__ */ defineComponent({
	__name: "ToastClose",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "button"
		}
	},
	setup(__props) {
		const props = __props;
		const rootContext = injectToastRootContext();
		const { forwardRef } = useForwardExpose();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(ToastAnnounceExclude_default, { "as-child": "" }, {
				default: withCtx(() => [createVNode(unref(Primitive), mergeProps(props, {
					ref: unref(forwardRef),
					type: _ctx.as === "button" ? "button" : void 0,
					onClick: unref(rootContext).onClose
				}), {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 3
				}, 16, ["type", "onClick"])]),
				_: 3
			});
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastAction.js
var ToastAction_default = /* @__PURE__ */ defineComponent({
	__name: "ToastAction",
	props: {
		altText: {
			type: String,
			required: true
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	setup(__props) {
		if (!__props.altText) throw new Error("Missing prop `altText` expected on `ToastAction`");
		const { forwardRef } = useForwardExpose();
		return (_ctx, _cache) => {
			return _ctx.altText ? (openBlock(), createBlock(ToastAnnounceExclude_default, {
				key: 0,
				"alt-text": _ctx.altText,
				"as-child": ""
			}, {
				default: withCtx(() => [createVNode(ToastClose_default, {
					ref: unref(forwardRef),
					as: _ctx.as,
					"as-child": _ctx.asChild
				}, {
					default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
					_: 3
				}, 8, ["as", "as-child"])]),
				_: 3
			}, 8, ["alt-text"])) : createCommentVNode("v-if", true);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastDescription.js
var ToastDescription_default = /* @__PURE__ */ defineComponent({
	__name: "ToastDescription",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		useForwardExpose();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), normalizeProps(guardReactiveProps(props)), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastPortal.js
var ToastPortal_default = /* @__PURE__ */ defineComponent({
	__name: "ToastPortal",
	props: {
		to: {
			type: null,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		defer: {
			type: Boolean,
			required: false
		},
		forceMount: {
			type: Boolean,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Teleport_default), normalizeProps(guardReactiveProps(props)), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastRoot.js
var ToastRoot_default = /* @__PURE__ */ defineComponent({
	__name: "ToastRoot",
	props: {
		defaultOpen: {
			type: Boolean,
			required: false,
			default: true
		},
		forceMount: {
			type: Boolean,
			required: false
		},
		type: {
			type: String,
			required: false,
			default: "foreground"
		},
		open: {
			type: Boolean,
			required: false,
			default: void 0
		},
		duration: {
			type: Number,
			required: false
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "li"
		}
	},
	emits: [
		"escapeKeyDown",
		"pause",
		"resume",
		"swipeStart",
		"swipeMove",
		"swipeCancel",
		"swipeEnd",
		"update:open"
	],
	setup(__props, { emit: __emit }) {
		const props = __props;
		const emits = __emit;
		const { forwardRef } = useForwardExpose();
		const open = useVModel(props, "open", emits, {
			defaultValue: props.defaultOpen,
			passive: props.open === void 0
		});
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Presence_default), { present: _ctx.forceMount || unref(open) }, {
				default: withCtx(() => [createVNode(ToastRootImpl_default, mergeProps({
					ref: unref(forwardRef),
					open: unref(open),
					type: _ctx.type,
					as: _ctx.as,
					"as-child": _ctx.asChild,
					duration: _ctx.duration
				}, _ctx.$attrs, {
					onClose: _cache[0] || (_cache[0] = ($event) => open.value = false),
					onPause: _cache[1] || (_cache[1] = ($event) => emits("pause")),
					onResume: _cache[2] || (_cache[2] = ($event) => emits("resume")),
					onEscapeKeyDown: _cache[3] || (_cache[3] = ($event) => emits("escapeKeyDown", $event)),
					onSwipeStart: _cache[4] || (_cache[4] = (event) => {
						emits("swipeStart", event);
						if (!event.defaultPrevented) event.currentTarget.setAttribute("data-swipe", "start");
					}),
					onSwipeMove: _cache[5] || (_cache[5] = (event) => {
						emits("swipeMove", event);
						if (!event.defaultPrevented) {
							const { x, y } = event.detail.delta;
							const target = event.currentTarget;
							target.setAttribute("data-swipe", "move");
							target.style.setProperty("--reka-toast-swipe-move-x", `${x}px`);
							target.style.setProperty("--reka-toast-swipe-move-y", `${y}px`);
						}
					}),
					onSwipeCancel: _cache[6] || (_cache[6] = (event) => {
						emits("swipeCancel", event);
						if (!event.defaultPrevented) {
							const target = event.currentTarget;
							target.setAttribute("data-swipe", "cancel");
							target.style.removeProperty("--reka-toast-swipe-move-x");
							target.style.removeProperty("--reka-toast-swipe-move-y");
							target.style.removeProperty("--reka-toast-swipe-end-x");
							target.style.removeProperty("--reka-toast-swipe-end-y");
						}
					}),
					onSwipeEnd: _cache[7] || (_cache[7] = (event) => {
						emits("swipeEnd", event);
						if (!event.defaultPrevented) {
							const { x, y } = event.detail.delta;
							const target = event.currentTarget;
							target.setAttribute("data-swipe", "end");
							target.style.removeProperty("--reka-toast-swipe-move-x");
							target.style.removeProperty("--reka-toast-swipe-move-y");
							target.style.setProperty("--reka-toast-swipe-end-x", `${x}px`);
							target.style.setProperty("--reka-toast-swipe-end-y", `${y}px`);
							open.value = false;
						}
					})
				}), {
					default: withCtx(({ remaining, duration: _duration }) => [renderSlot(_ctx.$slots, "default", {
						remaining,
						duration: _duration,
						open: unref(open)
					})]),
					_: 3
				}, 16, [
					"open",
					"type",
					"as",
					"as-child",
					"duration"
				])]),
				_: 3
			}, 8, ["present"]);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastTitle.js
var ToastTitle_default = /* @__PURE__ */ defineComponent({
	__name: "ToastTitle",
	props: {
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		useForwardExpose();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(Primitive), normalizeProps(guardReactiveProps(props)), {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			}, 16);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/FocusProxy.js
var FocusProxy_default = /* @__PURE__ */ defineComponent({
	__name: "FocusProxy",
	emits: ["focusFromOutsideViewport"],
	setup(__props, { emit: __emit }) {
		const emits = __emit;
		const providerContext = injectToastProviderContext();
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(VisuallyHidden_default), {
				tabindex: "0",
				style: { "position": "fixed" },
				onFocus: _cache[0] || (_cache[0] = (event) => {
					const prevFocusedElement = event.relatedTarget;
					if (!unref(providerContext).viewport.value?.contains(prevFocusedElement)) emits("focusFromOutsideViewport");
				})
			}, {
				default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
				_: 3
			});
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Toast/ToastViewport.js
var ToastViewport_default = /* @__PURE__ */ defineComponent({
	inheritAttrs: false,
	__name: "ToastViewport",
	props: {
		hotkey: {
			type: Array,
			required: false,
			default: () => ["F8"]
		},
		label: {
			type: [String, Function],
			required: false,
			default: "Notifications ({hotkey})"
		},
		asChild: {
			type: Boolean,
			required: false
		},
		as: {
			type: null,
			required: false,
			default: "ol"
		}
	},
	setup(__props) {
		const { hotkey, label } = toRefs(__props);
		const { forwardRef, currentElement } = useForwardExpose();
		const { CollectionSlot, getItems } = useCollection();
		const providerContext = injectToastProviderContext();
		const hasToasts = computed(() => providerContext.toastCount.value > 0);
		const headFocusProxyRef = ref();
		const tailFocusProxyRef = ref();
		const KEY_RE = /Key/g;
		const DIGIT_RE = /Digit/g;
		const hotkeyMessage = computed(() => hotkey.value.join("+").replace(KEY_RE, "").replace(DIGIT_RE, ""));
		onKeyStroke(hotkey.value, () => {
			currentElement.value.focus();
		});
		onMounted(() => {
			providerContext.onViewportChange(currentElement.value);
		});
		watchEffect((cleanupFn) => {
			const viewport = currentElement.value;
			if (hasToasts.value && viewport) {
				const handlePause = () => {
					if (!providerContext.isClosePausedRef.value) {
						const pauseEvent = new CustomEvent(VIEWPORT_PAUSE);
						viewport.dispatchEvent(pauseEvent);
						providerContext.isClosePausedRef.value = true;
					}
				};
				const handleResume = () => {
					if (providerContext.isClosePausedRef.value) {
						const resumeEvent = new CustomEvent(VIEWPORT_RESUME);
						viewport.dispatchEvent(resumeEvent);
						providerContext.isClosePausedRef.value = false;
					}
				};
				const handleFocusOutResume = (event) => {
					if (!viewport.contains(event.relatedTarget)) handleResume();
				};
				const handlePointerLeaveResume = () => {
					if (!viewport.contains(getActiveElement())) handleResume();
				};
				const handleKeyDown = (event) => {
					const isMetaKey = event.altKey || event.ctrlKey || event.metaKey;
					if (event.key === "Tab" && !isMetaKey) {
						const focusedElement = getActiveElement();
						const isTabbingBackwards = event.shiftKey;
						if (event.target === viewport && isTabbingBackwards) {
							headFocusProxyRef.value?.focus();
							return;
						}
						const sortedCandidates = getSortedTabbableCandidates({ tabbingDirection: isTabbingBackwards ? "backwards" : "forwards" });
						const index = sortedCandidates.findIndex((candidate) => candidate === focusedElement);
						if (focusFirst(sortedCandidates.slice(index + 1))) event.preventDefault();
						else isTabbingBackwards ? headFocusProxyRef.value?.focus() : tailFocusProxyRef.value?.focus();
					}
				};
				viewport.addEventListener("focusin", handlePause);
				viewport.addEventListener("focusout", handleFocusOutResume);
				viewport.addEventListener("pointermove", handlePause);
				viewport.addEventListener("pointerleave", handlePointerLeaveResume);
				viewport.addEventListener("keydown", handleKeyDown);
				window.addEventListener("blur", handlePause);
				window.addEventListener("focus", handleResume);
				cleanupFn(() => {
					viewport.removeEventListener("focusin", handlePause);
					viewport.removeEventListener("focusout", handleFocusOutResume);
					viewport.removeEventListener("pointermove", handlePause);
					viewport.removeEventListener("pointerleave", handlePointerLeaveResume);
					viewport.removeEventListener("keydown", handleKeyDown);
					window.removeEventListener("blur", handlePause);
					window.removeEventListener("focus", handleResume);
				});
			}
		});
		function getSortedTabbableCandidates({ tabbingDirection }) {
			const tabbableCandidates = getItems().map((i) => i.ref).map((toastNode) => {
				const toastTabbableCandidates = [toastNode, ...getTabbableCandidates(toastNode)];
				return tabbingDirection === "forwards" ? toastTabbableCandidates : toastTabbableCandidates.reverse();
			});
			return (tabbingDirection === "forwards" ? tabbableCandidates.reverse() : tabbableCandidates).flat();
		}
		return (_ctx, _cache) => {
			return openBlock(), createBlock(unref(DismissableLayerBranch_default), {
				role: "region",
				"aria-label": typeof unref(label) === "string" ? unref(label).replace("{hotkey}", hotkeyMessage.value) : unref(label)(hotkeyMessage.value),
				tabindex: "-1",
				style: normalizeStyle({ pointerEvents: hasToasts.value ? void 0 : "none" })
			}, {
				default: withCtx(() => [
					hasToasts.value ? (openBlock(), createBlock(FocusProxy_default, {
						key: 0,
						ref: (node) => {
							if (!node) return void 0;
							headFocusProxyRef.value = unref(unrefElement)(node);
						},
						onFocusFromOutsideViewport: _cache[0] || (_cache[0] = () => {
							const tabbableCandidates = getSortedTabbableCandidates({ tabbingDirection: "forwards" });
							unref(focusFirst)(tabbableCandidates);
						})
					}, null, 512)) : createCommentVNode("v-if", true),
					createVNode(unref(CollectionSlot), null, {
						default: withCtx(() => [createVNode(unref(Primitive), mergeProps({
							ref: unref(forwardRef),
							tabindex: "-1",
							as: _ctx.as,
							"as-child": _ctx.asChild
						}, _ctx.$attrs), {
							default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
							_: 3
						}, 16, ["as", "as-child"])]),
						_: 3
					}),
					hasToasts.value ? (openBlock(), createBlock(FocusProxy_default, {
						key: 1,
						ref: (node) => {
							if (!node) return void 0;
							tailFocusProxyRef.value = unref(unrefElement)(node);
						},
						onFocusFromOutsideViewport: _cache[1] || (_cache[1] = () => {
							const tabbableCandidates = getSortedTabbableCandidates({ tabbingDirection: "backwards" });
							unref(focusFirst)(tabbableCandidates);
						})
					}, null, 512)) : createCommentVNode("v-if", true)
				]),
				_: 3
			}, 8, ["aria-label", "style"]);
		};
	}
});
//#endregion
//#region node_modules/.pnpm/reka-ui@2.10.1_vue@3.5.41_typescript@6.0.3_/node_modules/reka-ui/dist/Tooltip/TooltipProvider.js
var [injectTooltipProviderContext, provideTooltipProviderContext] = /*#__PURE__*/ createContext("TooltipProvider");
var TooltipProvider_default = /* @__PURE__ */ defineComponent({
	inheritAttrs: false,
	__name: "TooltipProvider",
	props: {
		delayDuration: {
			type: Number,
			required: false,
			default: 700
		},
		skipDelayDuration: {
			type: Number,
			required: false,
			default: 300
		},
		disableHoverableContent: {
			type: Boolean,
			required: false,
			default: false
		},
		disableClosingTrigger: {
			type: Boolean,
			required: false
		},
		disabled: {
			type: Boolean,
			required: false
		},
		ignoreNonKeyboardFocus: {
			type: Boolean,
			required: false,
			default: false
		},
		content: {
			type: Object,
			required: false
		}
	},
	setup(__props) {
		const { delayDuration, skipDelayDuration, disableHoverableContent, disableClosingTrigger, ignoreNonKeyboardFocus, disabled, content } = toRefs(__props);
		useForwardExpose();
		const isOpenDelayed = ref(true);
		const isPointerInTransitRef = ref(false);
		const { start: startTimer, stop: clearTimer } = useTimeoutFn(() => {
			isOpenDelayed.value = true;
		}, skipDelayDuration, { immediate: false });
		provideTooltipProviderContext({
			isOpenDelayed,
			delayDuration,
			onOpen() {
				clearTimer();
				isOpenDelayed.value = false;
			},
			onClose() {
				startTimer();
			},
			isPointerInTransitRef,
			disableHoverableContent,
			disableClosingTrigger,
			disabled,
			ignoreNonKeyboardFocus,
			content
		});
		return (_ctx, _cache) => {
			return renderSlot(_ctx.$slots, "default");
		};
	}
});
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/composables/usePortal.js
var portalTargetInjectionKey = Symbol("nuxt-ui.portal-target");
function usePortal(portal) {
	const globalPortal = inject(portalTargetInjectionKey, void 0);
	const value = computed(() => portal.value === true ? globalPortal?.value : portal.value);
	const disabled = computed(() => typeof value.value === "boolean" ? !value.value : false);
	const to = computed(() => typeof value.value === "boolean" ? "body" : value.value);
	return computed(() => ({
		to: to.value,
		disabled: disabled.value
	}));
}
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/composables/useToast.js
var toastMaxInjectionKey = Symbol("nuxt-ui.toast-max");
function useToast() {
	const toasts = useState("toasts", () => []);
	const max = inject(toastMaxInjectionKey, void 0);
	const running = ref(false);
	const queue = [];
	const generateId = () => `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
	function mergeDuplicate(index, toast) {
		toasts.value[index] = {
			...toasts.value[index],
			...toast,
			_duplicate: (toasts.value[index]._duplicate || 0) + 1
		};
	}
	async function processQueue() {
		if (running.value || queue.length === 0) return;
		running.value = true;
		while (queue.length > 0) {
			await nextTick();
			const toast = queue.shift();
			const maxValue = max?.value ?? 5;
			if (maxValue <= 0) {
				if (toasts.value.length) toasts.value = [];
				continue;
			}
			const existingIndex = toasts.value.findIndex((t) => t.id === toast.id);
			if (existingIndex !== -1) {
				mergeDuplicate(existingIndex, toast);
				continue;
			}
			toasts.value = [...toasts.value, toast].slice(-maxValue);
		}
		running.value = false;
	}
	function add(toast) {
		const body = {
			id: generateId(),
			open: true,
			...toast
		};
		const existingIndex = toasts.value.findIndex((t) => t.id === body.id);
		if (existingIndex !== -1) {
			mergeDuplicate(existingIndex, body);
			return body;
		}
		queue.push(body);
		processQueue();
		return body;
	}
	function update(id, toast) {
		const index = toasts.value.findIndex((t) => t.id === id);
		if (index !== -1) {
			toasts.value[index] = {
				...toasts.value[index],
				...toast,
				duration: toast.duration,
				open: true,
				_updated: true
			};
			nextTick(() => {
				const i = toasts.value.findIndex((t) => t.id === id);
				if (i !== -1 && toasts.value[i]._updated) toasts.value[i] = {
					...toasts.value[i],
					_updated: void 0
				};
			});
		}
	}
	function remove(id) {
		const index = toasts.value.findIndex((t) => t.id === id);
		if (index !== -1 && toasts.value[index]._updated) return;
		if (index !== -1) toasts.value[index] = {
			...toasts.value[index],
			open: false
		};
		setTimeout(() => {
			toasts.value = toasts.value.filter((t) => t.id !== id);
		}, 200);
	}
	function clear() {
		toasts.value = [];
	}
	return {
		toasts,
		add,
		update,
		remove,
		clear
	};
}
//#endregion
//#region virtual:nuxt-ui-templates/ui/progress.ts
var progress_default = {
	"slots": {
		"root": "gap-2",
		"base": "relative overflow-hidden rounded-full bg-accented",
		"indicator": "rounded-full size-full transition-transform duration-200 ease-out motion-reduce:data-[state=indeterminate]:animate-pulse",
		"status": "flex text-dimmed transition-[width] duration-200",
		"steps": "grid items-end",
		"step": "truncate text-end row-start-1 col-start-1 transition-opacity"
	},
	"variants": {
		"animation": {
			"carousel": "",
			"carousel-inverse": "",
			"swing": "",
			"elastic": ""
		},
		"color": {
			"primary": {
				"indicator": "bg-primary",
				"steps": "text-primary"
			},
			"secondary": {
				"indicator": "bg-secondary",
				"steps": "text-secondary"
			},
			"success": {
				"indicator": "bg-success",
				"steps": "text-success"
			},
			"info": {
				"indicator": "bg-info",
				"steps": "text-info"
			},
			"warning": {
				"indicator": "bg-warning",
				"steps": "text-warning"
			},
			"error": {
				"indicator": "bg-error",
				"steps": "text-error"
			},
			"neutral": {
				"indicator": "bg-inverted",
				"steps": "text-inverted"
			}
		},
		"size": {
			"2xs": {
				"status": "text-xs",
				"steps": "text-xs"
			},
			"xs": {
				"status": "text-xs",
				"steps": "text-xs"
			},
			"sm": {
				"status": "text-sm",
				"steps": "text-sm"
			},
			"md": {
				"status": "text-sm",
				"steps": "text-sm"
			},
			"lg": {
				"status": "text-sm",
				"steps": "text-sm"
			},
			"xl": {
				"status": "text-base",
				"steps": "text-base"
			},
			"2xl": {
				"status": "text-base",
				"steps": "text-base"
			}
		},
		"step": {
			"active": { "step": "opacity-100" },
			"first": { "step": "opacity-100 text-muted" },
			"other": { "step": "opacity-0" },
			"last": { "step": "" }
		},
		"orientation": {
			"horizontal": {
				"root": "w-full flex flex-col",
				"base": "w-full",
				"status": "flex-row items-center justify-end min-w-fit"
			},
			"vertical": {
				"root": "h-full flex flex-row-reverse",
				"base": "h-full",
				"status": "flex-col justify-end min-h-fit"
			}
		},
		"inverted": { "true": { "status": "self-end" } }
	},
	"compoundVariants": [
		{
			"inverted": true,
			"orientation": "horizontal",
			"class": {
				"step": "text-start",
				"status": "flex-row-reverse"
			}
		},
		{
			"inverted": true,
			"orientation": "vertical",
			"class": {
				"steps": "items-start",
				"status": "flex-col-reverse"
			}
		},
		{
			"orientation": "horizontal",
			"size": "2xs",
			"class": "h-px"
		},
		{
			"orientation": "horizontal",
			"size": "xs",
			"class": "h-0.5"
		},
		{
			"orientation": "horizontal",
			"size": "sm",
			"class": "h-1"
		},
		{
			"orientation": "horizontal",
			"size": "md",
			"class": "h-2"
		},
		{
			"orientation": "horizontal",
			"size": "lg",
			"class": "h-3"
		},
		{
			"orientation": "horizontal",
			"size": "xl",
			"class": "h-4"
		},
		{
			"orientation": "horizontal",
			"size": "2xl",
			"class": "h-5"
		},
		{
			"orientation": "vertical",
			"size": "2xs",
			"class": "w-px"
		},
		{
			"orientation": "vertical",
			"size": "xs",
			"class": "w-0.5"
		},
		{
			"orientation": "vertical",
			"size": "sm",
			"class": "w-1"
		},
		{
			"orientation": "vertical",
			"size": "md",
			"class": "w-2"
		},
		{
			"orientation": "vertical",
			"size": "lg",
			"class": "w-3"
		},
		{
			"orientation": "vertical",
			"size": "xl",
			"class": "w-4"
		},
		{
			"orientation": "vertical",
			"size": "2xl",
			"class": "w-5"
		},
		{
			"orientation": "horizontal",
			"animation": "carousel",
			"class": { "indicator": "motion-safe:data-[state=indeterminate]:animate-[carousel_2s_ease-in-out_infinite] motion-safe:data-[state=indeterminate]:rtl:animate-[carousel-rtl_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "vertical",
			"animation": "carousel",
			"class": { "indicator": "motion-safe:data-[state=indeterminate]:animate-[carousel-vertical_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "horizontal",
			"animation": "carousel-inverse",
			"class": { "indicator": "motion-safe:data-[state=indeterminate]:animate-[carousel-inverse_2s_ease-in-out_infinite] motion-safe:data-[state=indeterminate]:rtl:animate-[carousel-inverse-rtl_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "vertical",
			"animation": "carousel-inverse",
			"class": { "indicator": "motion-safe:data-[state=indeterminate]:animate-[carousel-inverse-vertical_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "horizontal",
			"animation": "swing",
			"class": { "indicator": "motion-safe:data-[state=indeterminate]:animate-[swing_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "vertical",
			"animation": "swing",
			"class": { "indicator": "motion-safe:data-[state=indeterminate]:animate-[swing-vertical_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "horizontal",
			"animation": "elastic",
			"class": { "indicator": "motion-safe:data-[state=indeterminate]:animate-[elastic_2s_ease-in-out_infinite]" }
		},
		{
			"orientation": "vertical",
			"animation": "elastic",
			"class": { "indicator": "motion-safe:data-[state=indeterminate]:animate-[elastic-vertical_2s_ease-in-out_infinite]" }
		}
	],
	"defaultVariants": {
		"animation": "carousel",
		"color": "primary",
		"size": "md"
	}
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Progress.vue
var _sfc_main$5 = {
	__name: "Progress",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		max: {
			type: [Number, Array],
			required: false
		},
		status: {
			type: Boolean,
			required: false
		},
		inverted: {
			type: Boolean,
			required: false,
			default: false
		},
		size: {
			type: null,
			required: false
		},
		color: {
			type: null,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "horizontal"
		},
		animation: {
			type: null,
			required: false
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		getValueLabel: {
			type: Function,
			required: false
		},
		getValueText: {
			type: Function,
			required: false
		},
		modelValue: {
			type: [Number, null],
			required: false,
			default: null
		}
	},
	emits: ["update:modelValue", "update:max"],
	setup(__props, { emit: __emit }) {
		const _props = __props;
		const emits = __emit;
		const slots = useSlots();
		const props = useComponentProps("progress", _props);
		const { dir } = useLocale();
		const appConfig = useAppConfig();
		const rootProps = useForwardProps(reactivePick(props, "getValueLabel", "getValueText", "modelValue"), emits);
		const isIndeterminate = computed(() => rootProps.value.modelValue === null);
		const hasSteps = computed(() => Array.isArray(props.max));
		const realMax = computed(() => {
			if (isIndeterminate.value || !props.max) return;
			if (Array.isArray(props.max)) return props.max.length - 1;
			return Number(props.max);
		});
		const percent = computed(() => {
			if (isIndeterminate.value) return;
			switch (true) {
				case rootProps.value.modelValue < 0: return 0;
				case rootProps.value.modelValue > (realMax.value ?? 100): return 100;
				default: return Math.round(rootProps.value.modelValue / (realMax.value ?? 100) * 100);
			}
		});
		const indicatorStyle = computed(() => {
			if (percent.value === void 0) return;
			if (props.orientation === "vertical") return { transform: `translateY(${props.inverted ? "" : "-"}${100 - percent.value}%)` };
			else if (dir.value === "rtl") return { transform: `translateX(${props.inverted ? "-" : ""}${100 - percent.value}%)` };
			else return { transform: `translateX(${props.inverted ? "" : "-"}${100 - percent.value}%)` };
		});
		const statusStyle = computed(() => {
			const value = `${Math.max(percent.value ?? 0, 0)}%`;
			return props.orientation === "vertical" ? { height: value } : { width: value };
		});
		function isActive(index) {
			return index === Number(props.modelValue);
		}
		function isFirst(index) {
			return index === 0;
		}
		function isLast(index) {
			return index === realMax.value;
		}
		function stepVariant(index) {
			index = Number(index);
			if (isActive(index) && !isFirst(index)) return "active";
			if (isFirst(index) && isActive(index)) return "first";
			if (isLast(index) && isActive(index)) return "last";
			return "other";
		}
		const ui = computed(() => tv({
			extend: progress_default,
			...appConfig.ui?.progress || {}
		})({
			animation: props.animation,
			size: props.size,
			color: props.color,
			orientation: props.orientation,
			inverted: props.inverted
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: unref(props).as,
				"data-orientation": unref(props).orientation,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(props).ui?.root, unref(props).class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (!isIndeterminate.value && (unref(props).status || !!slots.status)) {
							_push(`<div data-slot="status" class="${ssrRenderClass(ui.value.status({ class: unref(props).ui?.status }))}" style="${ssrRenderStyle(statusStyle.value)}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "status", { percent: percent.value }, () => {
								_push(`${ssrInterpolate(percent.value)}% `);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						_push(ssrRenderComponent(unref(ProgressRoot_default), mergeProps(unref(rootProps), {
							max: realMax.value,
							"data-slot": "base",
							class: ui.value.base({ class: unref(props).ui?.base }),
							style: { "transform": "translateZ(0)" }
						}), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(ProgressIndicator_default), {
									"data-slot": "indicator",
									class: ui.value.indicator({ class: unref(props).ui?.indicator }),
									style: indicatorStyle.value
								}, null, _parent, _scopeId));
								else return [createVNode(unref(ProgressIndicator_default), {
									"data-slot": "indicator",
									class: ui.value.indicator({ class: unref(props).ui?.indicator }),
									style: indicatorStyle.value
								}, null, 8, ["class", "style"])];
							}),
							_: 1
						}, _parent, _scopeId));
						if (hasSteps.value) {
							_push(`<div data-slot="steps" class="${ssrRenderClass(ui.value.steps({ class: unref(props).ui?.steps }))}"${_scopeId}><!--[-->`);
							ssrRenderList(unref(props).max, (step, index) => {
								_push(`<div data-slot="step" class="${ssrRenderClass(ui.value.step({
									class: unref(props).ui?.step,
									step: stepVariant(index)
								}))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, `step-${index}`, { step }, () => {
									_push(`${ssrInterpolate(step)}`);
								}, _push, _parent, _scopeId);
								_push(`</div>`);
							});
							_push(`<!--]--></div>`);
						} else _push(`<!---->`);
					} else return [
						!isIndeterminate.value && (unref(props).status || !!slots.status) ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "status",
							class: ui.value.status({ class: unref(props).ui?.status }),
							style: statusStyle.value
						}, [renderSlot(_ctx.$slots, "status", { percent: percent.value }, () => [createTextVNode(toDisplayString(percent.value) + "% ", 1)])], 6)) : createCommentVNode("", true),
						createVNode(unref(ProgressRoot_default), mergeProps(unref(rootProps), {
							max: realMax.value,
							"data-slot": "base",
							class: ui.value.base({ class: unref(props).ui?.base }),
							style: { "transform": "translateZ(0)" }
						}), {
							default: withCtx(() => [createVNode(unref(ProgressIndicator_default), {
								"data-slot": "indicator",
								class: ui.value.indicator({ class: unref(props).ui?.indicator }),
								style: indicatorStyle.value
							}, null, 8, ["class", "style"])]),
							_: 1
						}, 16, ["max", "class"]),
						hasSteps.value ? (openBlock(), createBlock("div", {
							key: 1,
							"data-slot": "steps",
							class: ui.value.steps({ class: unref(props).ui?.steps })
						}, [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).max, (step, index) => {
							return openBlock(), createBlock("div", {
								key: index,
								"data-slot": "step",
								class: ui.value.step({
									class: unref(props).ui?.step,
									step: stepVariant(index)
								})
							}, [renderSlot(_ctx.$slots, `step-${index}`, { step }, () => [createTextVNode(toDisplayString(step), 1)])], 2);
						}), 128))], 2)) : createCommentVNode("", true)
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Progress.vue");
	return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/toast.ts
var toast_default = {
	"slots": {
		"root": "relative group overflow-hidden bg-default shadow-lg rounded-lg ring ring-default p-4 flex gap-2.5",
		"wrapper": "w-0 flex-1 flex flex-col",
		"title": "text-sm font-medium text-highlighted",
		"description": "text-sm text-muted",
		"icon": "shrink-0 size-5",
		"avatar": "shrink-0",
		"avatarSize": "2xl",
		"actions": "flex gap-1.5 shrink-0",
		"progress": "absolute inset-x-0 bottom-0",
		"close": "p-0"
	},
	"variants": {
		"color": {
			"primary": {
				"root": "outline-primary/25 focus-visible:outline-3 focus-visible:ring-primary",
				"icon": "text-primary"
			},
			"secondary": {
				"root": "outline-secondary/25 focus-visible:outline-3 focus-visible:ring-secondary",
				"icon": "text-secondary"
			},
			"success": {
				"root": "outline-success/25 focus-visible:outline-3 focus-visible:ring-success",
				"icon": "text-success"
			},
			"info": {
				"root": "outline-info/25 focus-visible:outline-3 focus-visible:ring-info",
				"icon": "text-info"
			},
			"warning": {
				"root": "outline-warning/25 focus-visible:outline-3 focus-visible:ring-warning",
				"icon": "text-warning"
			},
			"error": {
				"root": "outline-error/25 focus-visible:outline-3 focus-visible:ring-error",
				"icon": "text-error"
			},
			"neutral": {
				"root": "outline-inverted/25 focus-visible:outline-3 focus-visible:ring-inverted",
				"icon": "text-highlighted"
			}
		},
		"orientation": {
			"horizontal": {
				"root": "items-center",
				"actions": "items-center"
			},
			"vertical": {
				"root": "items-start",
				"actions": "items-start mt-2.5"
			}
		},
		"title": { "true": { "description": "mt-1" } }
	},
	"defaultVariants": { "color": "primary" }
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Toast.vue
var _sfc_main$4 = {
	__name: "Toast",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		title: {
			type: [
				String,
				Object,
				Function
			],
			required: false
		},
		description: {
			type: [
				String,
				Object,
				Function
			],
			required: false
		},
		icon: {
			type: null,
			required: false
		},
		avatar: {
			type: Object,
			required: false
		},
		color: {
			type: null,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "vertical"
		},
		close: {
			type: [Boolean, Object],
			required: false,
			default: true
		},
		closeIcon: {
			type: null,
			required: false
		},
		actions: {
			type: Array,
			required: false
		},
		duration: {
			type: Number,
			required: false
		},
		progress: {
			type: [Boolean, Object],
			required: false,
			default: true
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		defaultOpen: {
			type: Boolean,
			required: false
		},
		open: {
			type: Boolean,
			required: false
		},
		type: {
			type: String,
			required: false
		}
	},
	emits: [
		"escapeKeyDown",
		"pause",
		"resume",
		"swipeStart",
		"swipeMove",
		"swipeCancel",
		"swipeEnd",
		"update:open"
	],
	setup(__props, { expose: __expose, emit: __emit }) {
		const _props = __props;
		const emits = __emit;
		const slots = useSlots();
		const props = useComponentProps("toast", _props);
		const { t } = useLocale();
		const appConfig = useAppConfig();
		const rootProps = useForwardProps(reactivePick(props, "as", "defaultOpen", "open", "duration", "type"), emits);
		const ui = computed(() => tv({
			extend: toast_default,
			...appConfig.ui?.toast || {}
		})({
			color: props.color,
			orientation: props.orientation,
			title: !!props.title || !!slots.title
		}));
		const rootRef = useTemplateRef("rootRef");
		const height = ref(0);
		onMounted(() => {
			if (!rootRef.value?.$el?.getBoundingClientRect) return;
			height.value = rootRef.value.$el.getBoundingClientRect().height;
		});
		__expose({ height });
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(ToastRoot_default), mergeProps({
				ref_key: "rootRef",
				ref: rootRef
			}, unref(rootProps), {
				"data-orientation": unref(props).orientation,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(props).ui?.root, unref(props).class] }),
				style: { "--height": height.value }
			}, _attrs), {
				default: withCtx(({ remaining, duration: totalDuration, open }, _push, _parent, _scopeId) => {
					if (_push) {
						ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
							if (unref(props).avatar) _push(ssrRenderComponent(_sfc_main$6, mergeProps({ size: unref(props).ui?.avatarSize || ui.value.avatarSize() }, unref(props).avatar, {
								"data-slot": "avatar",
								class: ui.value.avatar({ class: unref(props).ui?.avatar })
							}), null, _parent, _scopeId));
							else if (unref(props).icon) _push(ssrRenderComponent(_sfc_main$7, {
								name: unref(props).icon,
								"data-slot": "icon",
								class: ui.value.icon({ class: unref(props).ui?.icon })
							}, null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(props).ui?.wrapper }))}"${_scopeId}>`);
						if (unref(props).title || !!slots.title) _push(ssrRenderComponent(unref(ToastTitle_default), {
							"data-slot": "title",
							class: ui.value.title({ class: unref(props).ui?.title })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "title", {}, () => {
									if (typeof unref(props).title === "function") ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(props).title()), null, null), _parent, _scopeId);
									else if (typeof unref(props).title === "object") ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(props).title), null, null), _parent, _scopeId);
									else _push(`<!--[-->${ssrInterpolate(unref(props).title)}<!--]-->`);
								}, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "title", {}, () => [typeof unref(props).title === "function" ? (openBlock(), createBlock(resolveDynamicComponent(unref(props).title()), { key: 0 })) : typeof unref(props).title === "object" ? (openBlock(), createBlock(resolveDynamicComponent(unref(props).title), { key: 1 })) : (openBlock(), createBlock(Fragment, { key: 2 }, [createTextVNode(toDisplayString(unref(props).title), 1)], 64))])];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						if (unref(props).description || !!slots.description) _push(ssrRenderComponent(unref(ToastDescription_default), {
							"data-slot": "description",
							class: ui.value.description({ class: unref(props).ui?.description })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "description", {}, () => {
									if (typeof unref(props).description === "function") ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(props).description()), null, null), _parent, _scopeId);
									else if (typeof unref(props).description === "object") ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(props).description), null, null), _parent, _scopeId);
									else _push(`<!--[-->${ssrInterpolate(unref(props).description)}<!--]-->`);
								}, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "description", {}, () => [typeof unref(props).description === "function" ? (openBlock(), createBlock(resolveDynamicComponent(unref(props).description()), { key: 0 })) : typeof unref(props).description === "object" ? (openBlock(), createBlock(resolveDynamicComponent(unref(props).description), { key: 1 })) : (openBlock(), createBlock(Fragment, { key: 2 }, [createTextVNode(toDisplayString(unref(props).description), 1)], 64))])];
							}),
							_: 2
						}, _parent, _scopeId));
						else _push(`<!---->`);
						if (unref(props).orientation === "vertical" && (unref(props).actions?.length || !!slots.actions)) {
							_push(`<div data-slot="actions" class="${ssrRenderClass(ui.value.actions({ class: unref(props).ui?.actions }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "actions", {}, () => {
								_push(`<!--[-->`);
								ssrRenderList(unref(props).actions, (action, index) => {
									_push(ssrRenderComponent(unref(ToastAction_default), {
										key: index,
										"alt-text": action.label || "Action",
										"as-child": "",
										onClick: () => {}
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_sfc_main$8, mergeProps({
												size: "xs",
												color: unref(props).color
											}, { ref_for: true }, action), null, _parent, _scopeId));
											else return [createVNode(_sfc_main$8, mergeProps({
												size: "xs",
												color: unref(props).color
											}, { ref_for: true }, action), null, 16, ["color"])];
										}),
										_: 2
									}, _parent, _scopeId));
								});
								_push(`<!--]-->`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						_push(`</div>`);
						if (unref(props).orientation === "horizontal" && (unref(props).actions?.length || !!slots.actions) || unref(props).close) {
							_push(`<div data-slot="actions" class="${ssrRenderClass(ui.value.actions({
								class: unref(props).ui?.actions,
								orientation: "horizontal"
							}))}"${_scopeId}>`);
							if (unref(props).orientation === "horizontal" && (unref(props).actions?.length || !!slots.actions)) ssrRenderSlot(_ctx.$slots, "actions", {}, () => {
								_push(`<!--[-->`);
								ssrRenderList(unref(props).actions, (action, index) => {
									_push(ssrRenderComponent(unref(ToastAction_default), {
										key: index,
										"alt-text": action.label || "Action",
										"as-child": "",
										onClick: () => {}
									}, {
										default: withCtx((_, _push, _parent, _scopeId) => {
											if (_push) _push(ssrRenderComponent(_sfc_main$8, mergeProps({
												size: "xs",
												color: unref(props).color
											}, { ref_for: true }, action), null, _parent, _scopeId));
											else return [createVNode(_sfc_main$8, mergeProps({
												size: "xs",
												color: unref(props).color
											}, { ref_for: true }, action), null, 16, ["color"])];
										}),
										_: 2
									}, _parent, _scopeId));
								});
								_push(`<!--]-->`);
							}, _push, _parent, _scopeId);
							else _push(`<!---->`);
							if (unref(props).close || !!slots.close) _push(ssrRenderComponent(unref(ToastClose_default), { "as-child": "" }, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) ssrRenderSlot(_ctx.$slots, "close", { ui: ui.value }, () => {
										if (unref(props).close) _push(ssrRenderComponent(_sfc_main$8, mergeProps({
											icon: unref(props).closeIcon || unref(appConfig).ui.icons.close,
											color: "neutral",
											variant: "link",
											"aria-label": unref(t)("toast.close")
										}, typeof unref(props).close === "object" ? unref(props).close : {}, {
											"data-slot": "close",
											class: ui.value.close({ class: unref(props).ui?.close }),
											onClick: () => {}
										}), null, _parent, _scopeId));
										else _push(`<!---->`);
									}, _push, _parent, _scopeId);
									else return [renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [unref(props).close ? (openBlock(), createBlock(_sfc_main$8, mergeProps({
										key: 0,
										icon: unref(props).closeIcon || unref(appConfig).ui.icons.close,
										color: "neutral",
										variant: "link",
										"aria-label": unref(t)("toast.close")
									}, typeof unref(props).close === "object" ? unref(props).close : {}, {
										"data-slot": "close",
										class: ui.value.close({ class: unref(props).ui?.close }),
										onClick: withModifiers(() => {}, ["stop"])
									}), null, 16, [
										"icon",
										"aria-label",
										"class",
										"onClick"
									])) : createCommentVNode("", true)])];
								}),
								_: 2
							}, _parent, _scopeId));
							else _push(`<!---->`);
							_push(`</div>`);
						} else _push(`<!---->`);
						if (unref(props).progress && open && remaining > 0 && totalDuration) _push(ssrRenderComponent(_sfc_main$5, mergeProps({
							"model-value": remaining / totalDuration * 100,
							color: unref(props).color
						}, typeof unref(props).progress === "object" ? unref(props).progress : {}, {
							size: "sm",
							"data-slot": "progress",
							class: ui.value.progress({ class: unref(props).ui?.progress })
						}), null, _parent, _scopeId));
						else _push(`<!---->`);
					} else return [
						renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(props).avatar ? (openBlock(), createBlock(_sfc_main$6, mergeProps({
							key: 0,
							size: unref(props).ui?.avatarSize || ui.value.avatarSize()
						}, unref(props).avatar, {
							"data-slot": "avatar",
							class: ui.value.avatar({ class: unref(props).ui?.avatar })
						}), null, 16, ["size", "class"])) : unref(props).icon ? (openBlock(), createBlock(_sfc_main$7, {
							key: 1,
							name: unref(props).icon,
							"data-slot": "icon",
							class: ui.value.icon({ class: unref(props).ui?.icon })
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)]),
						createVNode("div", {
							"data-slot": "wrapper",
							class: ui.value.wrapper({ class: unref(props).ui?.wrapper })
						}, [
							unref(props).title || !!slots.title ? (openBlock(), createBlock(unref(ToastTitle_default), {
								key: 0,
								"data-slot": "title",
								class: ui.value.title({ class: unref(props).ui?.title })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, "title", {}, () => [typeof unref(props).title === "function" ? (openBlock(), createBlock(resolveDynamicComponent(unref(props).title()), { key: 0 })) : typeof unref(props).title === "object" ? (openBlock(), createBlock(resolveDynamicComponent(unref(props).title), { key: 1 })) : (openBlock(), createBlock(Fragment, { key: 2 }, [createTextVNode(toDisplayString(unref(props).title), 1)], 64))])]),
								_: 3
							}, 8, ["class"])) : createCommentVNode("", true),
							unref(props).description || !!slots.description ? (openBlock(), createBlock(unref(ToastDescription_default), {
								key: 1,
								"data-slot": "description",
								class: ui.value.description({ class: unref(props).ui?.description })
							}, {
								default: withCtx(() => [renderSlot(_ctx.$slots, "description", {}, () => [typeof unref(props).description === "function" ? (openBlock(), createBlock(resolveDynamicComponent(unref(props).description()), { key: 0 })) : typeof unref(props).description === "object" ? (openBlock(), createBlock(resolveDynamicComponent(unref(props).description), { key: 1 })) : (openBlock(), createBlock(Fragment, { key: 2 }, [createTextVNode(toDisplayString(unref(props).description), 1)], 64))])]),
								_: 3
							}, 8, ["class"])) : createCommentVNode("", true),
							unref(props).orientation === "vertical" && (unref(props).actions?.length || !!slots.actions) ? (openBlock(), createBlock("div", {
								key: 2,
								"data-slot": "actions",
								class: ui.value.actions({ class: unref(props).ui?.actions })
							}, [renderSlot(_ctx.$slots, "actions", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).actions, (action, index) => {
								return openBlock(), createBlock(unref(ToastAction_default), {
									key: index,
									"alt-text": action.label || "Action",
									"as-child": "",
									onClick: withModifiers(() => {}, ["stop"])
								}, {
									default: withCtx(() => [createVNode(_sfc_main$8, mergeProps({
										size: "xs",
										color: unref(props).color
									}, { ref_for: true }, action), null, 16, ["color"])]),
									_: 2
								}, 1032, ["alt-text", "onClick"]);
							}), 128))])], 2)) : createCommentVNode("", true)
						], 2),
						unref(props).orientation === "horizontal" && (unref(props).actions?.length || !!slots.actions) || unref(props).close ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "actions",
							class: ui.value.actions({
								class: unref(props).ui?.actions,
								orientation: "horizontal"
							})
						}, [unref(props).orientation === "horizontal" && (unref(props).actions?.length || !!slots.actions) ? renderSlot(_ctx.$slots, "actions", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).actions, (action, index) => {
							return openBlock(), createBlock(unref(ToastAction_default), {
								key: index,
								"alt-text": action.label || "Action",
								"as-child": "",
								onClick: withModifiers(() => {}, ["stop"])
							}, {
								default: withCtx(() => [createVNode(_sfc_main$8, mergeProps({
									size: "xs",
									color: unref(props).color
								}, { ref_for: true }, action), null, 16, ["color"])]),
								_: 2
							}, 1032, ["alt-text", "onClick"]);
						}), 128))], void 0, 0) : createCommentVNode("", true), unref(props).close || !!slots.close ? (openBlock(), createBlock(unref(ToastClose_default), {
							key: 1,
							"as-child": ""
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [unref(props).close ? (openBlock(), createBlock(_sfc_main$8, mergeProps({
								key: 0,
								icon: unref(props).closeIcon || unref(appConfig).ui.icons.close,
								color: "neutral",
								variant: "link",
								"aria-label": unref(t)("toast.close")
							}, typeof unref(props).close === "object" ? unref(props).close : {}, {
								"data-slot": "close",
								class: ui.value.close({ class: unref(props).ui?.close }),
								onClick: withModifiers(() => {}, ["stop"])
							}), null, 16, [
								"icon",
								"aria-label",
								"class",
								"onClick"
							])) : createCommentVNode("", true)])]),
							_: 3
						})) : createCommentVNode("", true)], 2)) : createCommentVNode("", true),
						unref(props).progress && open && remaining > 0 && totalDuration ? (openBlock(), createBlock(_sfc_main$5, mergeProps({
							key: 1,
							"model-value": remaining / totalDuration * 100,
							color: unref(props).color
						}, typeof unref(props).progress === "object" ? unref(props).progress : {}, {
							size: "sm",
							"data-slot": "progress",
							class: ui.value.progress({ class: unref(props).ui?.progress })
						}), null, 16, [
							"model-value",
							"color",
							"class"
						])) : createCommentVNode("", true)
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Toast.vue");
	return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/toaster.ts
var toaster_default = {
	"slots": {
		"viewport": "fixed flex flex-col w-[calc(100%-2rem)] sm:w-96 z-[100] data-[expanded=true]:h-(--height) focus:outline-none",
		"base": "pointer-events-auto absolute inset-x-0 z-(--index) transform-(--transform) data-[expanded=false]:data-[front=false]:h-(--front-height) data-[expanded=false]:data-[front=false]:*:opacity-0 data-[front=false]:*:transition-opacity data-[front=false]:*:duration-100 data-[state=closed]:animate-[toast-closed_200ms_ease-in-out] data-[state=closed]:data-[expanded=false]:data-[front=false]:animate-[toast-collapsed-closed_200ms_ease-in-out] data-[state=open]:data-[pulsing=odd]:animate-[toast-pulse-a_300ms_ease-out] data-[state=open]:data-[pulsing=even]:animate-[toast-pulse-b_300ms_ease-out] data-[swipe=move]:transition-none transition-[transform,translate,height] duration-200 ease-out"
	},
	"variants": {
		"position": {
			"top-left": { "viewport": "left-4" },
			"top-center": { "viewport": "left-1/2 transform -translate-x-1/2" },
			"top-right": { "viewport": "right-4" },
			"bottom-left": { "viewport": "left-4" },
			"bottom-center": { "viewport": "left-1/2 transform -translate-x-1/2" },
			"bottom-right": { "viewport": "right-4" }
		},
		"swipeDirection": {
			"up": "data-[swipe=end]:animate-[toast-slide-up_200ms_ease-out]",
			"right": "data-[swipe=end]:animate-[toast-slide-right_200ms_ease-out]",
			"down": "data-[swipe=end]:animate-[toast-slide-down_200ms_ease-out]",
			"left": "data-[swipe=end]:animate-[toast-slide-left_200ms_ease-out]"
		}
	},
	"compoundVariants": [
		{
			"position": [
				"top-left",
				"top-center",
				"top-right"
			],
			"class": {
				"viewport": "top-4",
				"base": "top-0 data-[state=open]:animate-[toast-slide-in-from-top_200ms_ease-in-out]"
			}
		},
		{
			"position": [
				"bottom-left",
				"bottom-center",
				"bottom-right"
			],
			"class": {
				"viewport": "bottom-4",
				"base": "bottom-0 data-[state=open]:animate-[toast-slide-in-from-bottom_200ms_ease-in-out]"
			}
		},
		{
			"swipeDirection": ["left", "right"],
			"class": "data-[swipe=move]:translate-x-(--reka-toast-swipe-move-x) data-[swipe=end]:translate-x-(--reka-toast-swipe-end-x) data-[swipe=cancel]:translate-x-0"
		},
		{
			"swipeDirection": ["up", "down"],
			"class": "data-[swipe=move]:translate-y-(--reka-toast-swipe-move-y) data-[swipe=end]:translate-y-(--reka-toast-swipe-end-y) data-[swipe=cancel]:translate-y-0"
		}
	],
	"defaultVariants": { "position": "bottom-right" }
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Toaster.vue
var _sfc_main$3 = /*@__PURE__*/ Object.assign({ name: "Toaster" }, {
	__ssrInlineRender: true,
	props: {
		position: {
			type: null,
			required: false
		},
		expand: {
			type: Boolean,
			required: false,
			default: true
		},
		progress: {
			type: Boolean,
			required: false,
			default: true
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: true
		},
		max: {
			type: Number,
			required: false,
			default: 5
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		},
		label: {
			type: String,
			required: false
		},
		duration: {
			type: Number,
			required: false,
			default: 5e3
		},
		disableSwipe: {
			type: Boolean,
			required: false
		},
		swipeThreshold: {
			type: Number,
			required: false
		}
	},
	setup(__props) {
		const props = useComponentProps("toaster", __props);
		const { toasts, remove } = useToast();
		const appConfig = useAppConfig();
		provide(toastMaxInjectionKey, toRef(() => props.max));
		const providerProps = useForwardProps(reactivePick(props, "duration", "label", "swipeThreshold", "disableSwipe"));
		const portalProps = usePortal(toRef(() => props.portal));
		const swipeDirection = computed(() => {
			switch (props.position) {
				case "top-center": return "up";
				case "top-right":
				case "bottom-right": return "right";
				case "bottom-center": return "down";
				case "top-left":
				case "bottom-left": return "left";
			}
			return "right";
		});
		const ui = computed(() => tv({
			extend: toaster_default,
			...appConfig.ui?.toaster || {}
		})({
			position: props.position,
			swipeDirection: swipeDirection.value
		}));
		function onUpdateOpen(value, id) {
			if (value) return;
			remove(id);
		}
		const hovered = ref(false);
		const expanded = computed(() => props.expand || hovered.value);
		const refs = ref([]);
		const height = computed(() => refs.value.reduce((acc, { height: height2 }) => acc + height2 + 16, 0));
		const frontHeight = computed(() => refs.value[refs.value.length - 1]?.height || 0);
		function getOffset(index) {
			return refs.value.slice(index + 1).reduce((acc, { height: height2 }) => acc + height2 + 16, 0);
		}
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(ToastProvider_default), mergeProps({ "swipe-direction": swipeDirection.value }, unref(providerProps), _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
						_push(`<!--[-->`);
						ssrRenderList(unref(toasts), (toast, index) => {
							_push(ssrRenderComponent(_sfc_main$4, mergeProps({
								key: toast.id,
								ref_for: true,
								ref_key: "refs",
								ref: refs,
								progress: unref(props).progress
							}, { ref_for: true }, unref(omit)(toast, [
								"id",
								"close",
								"_duplicate",
								"_updated"
							]), {
								close: toast.close,
								"data-expanded": expanded.value,
								"data-front": !expanded.value && index === unref(toasts).length - 1,
								"data-pulsing": toast._duplicate ? toast._duplicate % 2 === 0 ? "even" : "odd" : void 0,
								style: {
									"--index": index - unref(toasts).length + unref(toasts).length,
									"--before": unref(toasts).length - 1 - index,
									"--offset": getOffset(index),
									"--scale": expanded.value ? "1" : "calc(1 - var(--before) * var(--scale-factor))",
									"--translate": expanded.value ? "calc(var(--offset) * var(--translate-factor))" : "calc(var(--before) * var(--gap))",
									"--transform": "translateY(var(--translate)) scale(var(--scale))"
								},
								"data-slot": "base",
								class: ui.value.base({ class: [unref(props).ui?.base, toast.onClick ? "cursor-pointer" : void 0] }),
								"onUpdate:open": ($event) => onUpdateOpen($event, toast.id),
								onClick: ($event) => toast.onClick && toast.onClick(toast)
							}), null, _parent, _scopeId));
						});
						_push(`<!--]-->`);
						_push(ssrRenderComponent(unref(ToastPortal_default), unref(portalProps), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(ssrRenderComponent(unref(ToastViewport_default), {
									"data-expanded": expanded.value,
									"data-slot": "viewport",
									class: ui.value.viewport({ class: [unref(props).ui?.viewport, unref(props).class] }),
									style: {
										"--scale-factor": "0.05",
										"--translate-factor": unref(props).position?.startsWith("top") ? "1px" : "-1px",
										"--gap": unref(props).position?.startsWith("top") ? "16px" : "-16px",
										"--front-height": `${frontHeight.value}px`,
										"--height": `${height.value}px`
									},
									onMouseenter: ($event) => hovered.value = true,
									onMouseleave: ($event) => hovered.value = false
								}, null, _parent, _scopeId));
								else return [createVNode(unref(ToastViewport_default), {
									"data-expanded": expanded.value,
									"data-slot": "viewport",
									class: ui.value.viewport({ class: [unref(props).ui?.viewport, unref(props).class] }),
									style: {
										"--scale-factor": "0.05",
										"--translate-factor": unref(props).position?.startsWith("top") ? "1px" : "-1px",
										"--gap": unref(props).position?.startsWith("top") ? "16px" : "-16px",
										"--front-height": `${frontHeight.value}px`,
										"--height": `${height.value}px`
									},
									onMouseenter: ($event) => hovered.value = true,
									onMouseleave: ($event) => hovered.value = false
								}, null, 8, [
									"data-expanded",
									"class",
									"style",
									"onMouseenter",
									"onMouseleave"
								])];
							}),
							_: 1
						}, _parent, _scopeId));
					} else return [
						renderSlot(_ctx.$slots, "default"),
						(openBlock(true), createBlock(Fragment, null, renderList(unref(toasts), (toast, index) => {
							return openBlock(), createBlock(_sfc_main$4, mergeProps({
								key: toast.id,
								ref_for: true,
								ref_key: "refs",
								ref: refs,
								progress: unref(props).progress
							}, { ref_for: true }, unref(omit)(toast, [
								"id",
								"close",
								"_duplicate",
								"_updated"
							]), {
								close: toast.close,
								"data-expanded": expanded.value,
								"data-front": !expanded.value && index === unref(toasts).length - 1,
								"data-pulsing": toast._duplicate ? toast._duplicate % 2 === 0 ? "even" : "odd" : void 0,
								style: {
									"--index": index - unref(toasts).length + unref(toasts).length,
									"--before": unref(toasts).length - 1 - index,
									"--offset": getOffset(index),
									"--scale": expanded.value ? "1" : "calc(1 - var(--before) * var(--scale-factor))",
									"--translate": expanded.value ? "calc(var(--offset) * var(--translate-factor))" : "calc(var(--before) * var(--gap))",
									"--transform": "translateY(var(--translate)) scale(var(--scale))"
								},
								"data-slot": "base",
								class: ui.value.base({ class: [unref(props).ui?.base, toast.onClick ? "cursor-pointer" : void 0] }),
								"onUpdate:open": ($event) => onUpdateOpen($event, toast.id),
								onClick: ($event) => toast.onClick && toast.onClick(toast)
							}), null, 16, [
								"progress",
								"close",
								"data-expanded",
								"data-front",
								"data-pulsing",
								"style",
								"class",
								"onUpdate:open",
								"onClick"
							]);
						}), 128)),
						createVNode(unref(ToastPortal_default), unref(portalProps), {
							default: withCtx(() => [createVNode(unref(ToastViewport_default), {
								"data-expanded": expanded.value,
								"data-slot": "viewport",
								class: ui.value.viewport({ class: [unref(props).ui?.viewport, unref(props).class] }),
								style: {
									"--scale-factor": "0.05",
									"--translate-factor": unref(props).position?.startsWith("top") ? "1px" : "-1px",
									"--gap": unref(props).position?.startsWith("top") ? "16px" : "-16px",
									"--front-height": `${frontHeight.value}px`,
									"--height": `${height.value}px`
								},
								onMouseenter: ($event) => hovered.value = true,
								onMouseleave: ($event) => hovered.value = false
							}, null, 8, [
								"data-expanded",
								"class",
								"style",
								"onMouseenter",
								"onMouseleave"
							])]),
							_: 1
						}, 16)
					];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Toaster.vue");
	return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/composables/useOverlay.js
function _useOverlay() {
	const overlays = shallowReactive([]);
	const create = (component, _options) => {
		const { props, defaultOpen, destroyOnClose } = _options || {};
		const options = reactive({
			id: Symbol(import.meta.dev ? "useOverlay" : ""),
			isOpen: !!defaultOpen,
			component: markRaw(component),
			isMounted: !!defaultOpen,
			destroyOnClose: !!destroyOnClose,
			originalProps: props || {},
			props: { ...props }
		});
		overlays.push(options);
		return {
			...options,
			open: (props2) => open(options.id, props2),
			close: (value) => close(options.id, value),
			patch: (props2) => patch(options.id, props2)
		};
	};
	const open = (id, props) => {
		const overlay = getOverlay(id);
		if (props) overlay.props = {
			...overlay.originalProps,
			...props
		};
		else overlay.props = { ...overlay.originalProps };
		overlay.isOpen = true;
		overlay.isMounted = true;
		const result = new Promise((resolve) => overlay.resolvePromise = resolve);
		return Object.assign(result, {
			id,
			isMounted: overlay.isMounted,
			isOpen: overlay.isOpen,
			result
		});
	};
	const close = (id, value) => {
		const overlay = getOverlay(id);
		overlay.isOpen = false;
		if (overlay.resolvePromise) {
			overlay.resolvePromise(value);
			overlay.resolvePromise = void 0;
		}
	};
	const closeAll = () => {
		overlays.forEach((overlay) => close(overlay.id));
	};
	const unmount = (id) => {
		const overlay = getOverlay(id);
		overlay.isMounted = false;
		if (overlay.destroyOnClose) {
			const index = overlays.findIndex((overlay2) => overlay2.id === id);
			overlays.splice(index, 1);
		}
	};
	const patch = (id, props) => {
		const overlay = getOverlay(id);
		overlay.props = {
			...overlay.props,
			...props
		};
	};
	const getOverlay = (id) => {
		const overlay = overlays.find((overlay2) => overlay2.id === id);
		if (!overlay) throw new Error("Overlay not found");
		return overlay;
	};
	const isOpen = (id) => {
		return getOverlay(id).isOpen;
	};
	return {
		overlays,
		open,
		close,
		closeAll,
		create,
		patch,
		unmount,
		isOpen
	};
}
var useOverlay = /* @__PURE__ */ createSharedComposable(_useOverlay);
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/OverlayProvider.vue
var _sfc_main$2 = {
	__name: "OverlayProvider",
	__ssrInlineRender: true,
	setup(__props) {
		const { overlays, unmount, close } = useOverlay();
		const mountedOverlays = computed(() => overlays.filter((overlay) => overlay.isMounted));
		const onAfterLeave = (id) => {
			close(id);
			unmount(id);
		};
		const onClose = (id, value) => {
			close(id, value);
		};
		return (_ctx, _push, _parent, _attrs) => {
			_push(`<!--[-->`);
			ssrRenderList(mountedOverlays.value, (overlay) => {
				ssrRenderVNode(_push, createVNode(resolveDynamicComponent(overlay.component), mergeProps({ key: overlay.id }, { ref_for: true }, overlay.props, {
					open: overlay.isOpen,
					"onUpdate:open": ($event) => overlay.isOpen = $event,
					onClose: (value) => onClose(overlay.id, value),
					"onAfter:leave": ($event) => onAfterLeave(overlay.id)
				}), null), _parent);
			});
			_push(`<!--]-->`);
		};
	}
};
var _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/OverlayProvider.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/App.vue
var _sfc_main$1 = /*@__PURE__*/ Object.assign({ name: "App" }, {
	__ssrInlineRender: true,
	props: {
		tooltip: {
			type: Object,
			required: false
		},
		toaster: {
			type: [Object, null],
			required: false
		},
		locale: {
			type: Object,
			required: false
		},
		portal: {
			type: [Boolean, String],
			required: false,
			skipCheck: true,
			default: "body"
		},
		dir: {
			type: String,
			required: false
		},
		scrollBody: {
			type: [Boolean, Object],
			required: false
		},
		nonce: {
			type: String,
			required: false
		}
	},
	setup(__props) {
		const props = __props;
		const configProviderProps = useForwardProps$1(reactivePick(props, "scrollBody"));
		const tooltipProps = toRef(() => props.tooltip);
		const toasterProps = toRef(() => props.toaster);
		const locale = toRef(() => props.locale);
		provide(localeContextInjectionKey, locale);
		const portal = toRef(() => props.portal);
		provide(portalTargetInjectionKey, portal);
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(ConfigProvider_default), mergeProps({
				"use-id": () => useId(),
				dir: props.dir || locale.value?.dir,
				locale: locale.value?.code
			}, unref(configProviderProps), _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(unref(TooltipProvider_default), tooltipProps.value, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) {
								if (__props.toaster !== null) _push(ssrRenderComponent(_sfc_main$3, toasterProps.value, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
										else return [renderSlot(_ctx.$slots, "default")];
									}),
									_: 3
								}, _parent, _scopeId));
								else ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
								_push(ssrRenderComponent(_sfc_main$2, null, null, _parent, _scopeId));
							} else return [__props.toaster !== null ? (openBlock(), createBlock(_sfc_main$3, mergeProps({ key: 0 }, toasterProps.value), {
								default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
								_: 3
							}, 16)) : renderSlot(_ctx.$slots, "default", {}, void 0, void 0, 1), createVNode(_sfc_main$2)];
						}),
						_: 3
					}, _parent, _scopeId));
					else return [createVNode(unref(TooltipProvider_default), tooltipProps.value, {
						default: withCtx(() => [__props.toaster !== null ? (openBlock(), createBlock(_sfc_main$3, mergeProps({ key: 0 }, toasterProps.value), {
							default: withCtx(() => [renderSlot(_ctx.$slots, "default")]),
							_: 3
						}, 16)) : renderSlot(_ctx.$slots, "default", {}, void 0, void 0, 1), createVNode(_sfc_main$2)]),
						_: 3
					}, 16)];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/App.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/AppRoot.vue
var _sfc_main = {
	__name: "AppRoot",
	__ssrInlineRender: true,
	props: {
		page: Object,
		pageProps: Object
	},
	setup(__props) {
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(_sfc_main$1, _attrs, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderVNode(_push, createVNode(resolveDynamicComponent(__props.page), __props.pageProps, null), _parent, _scopeId);
					else return [(openBlock(), createBlock(resolveDynamicComponent(__props.page), __props.pageProps, null, 16))];
				}),
				_: 1
			}, _parent));
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/AppRoot.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
//#region resources/js/ssr.js
createServer((page) => createInertiaApp({
	page,
	render: renderToString,
	resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, /* #__PURE__ */ Object.assign({
		"./Pages/BoardShow.vue": () => import("./assets/BoardShow-C9jVH_jI.js"),
		"./Pages/SnakesAndLadders.vue": () => import("./assets/SnakesAndLadders-BJYcscTz.js")
	})),
	setup({ App, props, plugin }) {
		return createSSRApp({ render: () => h(_sfc_main, {
			page: App,
			pageProps: props
		}) }).use(plugin).use(virtual_nuxt_ui_plugins_default).use(M, page.props.ziggy);
	}
}));
//#endregion
export {};
