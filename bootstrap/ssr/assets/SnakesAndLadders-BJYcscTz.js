import { g as getSlotChildrenText, l as useComponentProps, n as _sfc_main$5, o as _sfc_main$4, s as tv, t as _sfc_main$6, u as Primitive, y as useAppConfig } from "./Button-BsqkFqP1.js";
import { n as _sfc_main$9, r as _sfc_main$7, t as _sfc_main$8 } from "./Main-BTQptCM4.js";
import { Head, usePage } from "@inertiajs/vue3";
import { Fragment, computed, createBlock, createCommentVNode, createTextVNode, createVNode, mergeProps, openBlock, renderList, renderSlot, toDisplayString, unref, useSSRContext, useSlots, withCtx } from "vue";
import { ssrInterpolate, ssrRenderAttr, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot } from "vue/server-renderer";
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/composables/usePrefix.js
function usePrefix() {
	const prefix = useAppConfig().ui?.prefix;
	return (classString) => {
		if (!prefix || !classString) return classString;
		return classString.split(/\s+/).filter(Boolean).map((cls) => `${prefix}:${cls}`).join(" ");
	};
}
//#endregion
//#region virtual:nuxt-ui-templates/ui/page-feature.ts
var page_feature_default = {
	"slots": {
		"root": "relative rounded-sm",
		"wrapper": "",
		"leading": "inline-flex items-center justify-center",
		"leadingIcon": "size-5 shrink-0 text-primary",
		"title": "text-base text-pretty font-semibold text-highlighted",
		"description": "text-[15px] text-pretty text-muted"
	},
	"variants": {
		"orientation": {
			"horizontal": {
				"root": "flex items-start gap-2.5",
				"leading": "p-0.5"
			},
			"vertical": { "leading": "mb-2.5" }
		},
		"to": { "true": { "root": ["outline-primary/25 has-focus-visible:outline-3", "transition"] } },
		"title": { "true": { "description": "mt-1" } }
	}
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/PageFeature.vue
var _sfc_main$3 = /*@__PURE__*/ Object.assign({ inheritAttrs: false }, {
	__name: "PageFeature",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		icon: {
			type: null,
			required: false
		},
		title: {
			type: String,
			required: false
		},
		description: {
			type: String,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "horizontal"
		},
		to: {
			type: null,
			required: false
		},
		target: {
			type: [
				String,
				Object,
				null
			],
			required: false
		},
		onClick: {
			type: Function,
			required: false
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		}
	},
	setup(__props) {
		const _props = __props;
		const slots = useSlots();
		const props = useComponentProps("pageFeature", _props);
		const appConfig = useAppConfig();
		const prefix = usePrefix();
		const ui = computed(() => tv({
			extend: page_feature_default,
			...appConfig.ui?.pageFeature || {}
		})({
			orientation: props.orientation,
			title: !!props.title || !!slots.title,
			to: !!props.to || !!props.onClick
		}));
		const ariaLabel = computed(() => {
			return (slots.title && getSlotChildrenText(slots.title()) || props.title || "Feature link").trim();
		});
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({ as: unref(props).as }, !unref(props).to ? _ctx.$attrs : {}, {
				"data-orientation": unref(props).orientation,
				"data-slot": _ctx.$attrs["data-slot"] ?? "root",
				class: ui.value.root({ class: [unref(props).ui?.root, unref(props).class] }),
				onClick: unref(props).onClick
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (unref(props).icon || !!slots.leading) {
							_push(`<div data-slot="leading" class="${ssrRenderClass(ui.value.leading({ class: unref(props).ui?.leading }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
								if (unref(props).icon) _push(ssrRenderComponent(_sfc_main$4, {
									name: unref(props).icon,
									"data-slot": "leadingIcon",
									class: ui.value.leadingIcon({ class: unref(props).ui?.leadingIcon })
								}, null, _parent, _scopeId));
								else _push(`<!---->`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(props).ui?.wrapper }))}"${_scopeId}>`);
						if (unref(props).to) _push(ssrRenderComponent(_sfc_main$5, mergeProps({ "aria-label": ariaLabel.value }, {
							"to": unref(props).to,
							"target": unref(props).target,
							..._ctx.$attrs,
							"data-slot": void 0
						}, {
							class: unref(prefix)("focus:outline-none peer"),
							raw: ""
						}), {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) _push(`<span class="${ssrRenderClass(unref(prefix)("absolute inset-0"))}" aria-hidden="true"${_scopeId}></span>`);
								else return [createVNode("span", {
									class: unref(prefix)("absolute inset-0"),
									"aria-hidden": "true"
								}, null, 2)];
							}),
							_: 1
						}, _parent, _scopeId));
						else _push(`<!---->`);
						ssrRenderSlot(_ctx.$slots, "default", {}, () => {
							if (unref(props).title || !!slots.title) {
								_push(`<div data-slot="title" class="${ssrRenderClass(ui.value.title({ class: unref(props).ui?.title }))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, "title", {}, () => {
									_push(`${ssrInterpolate(unref(props).title)}`);
								}, _push, _parent, _scopeId);
								_push(`</div>`);
							} else _push(`<!---->`);
							if (unref(props).description || !!slots.description) {
								_push(`<div data-slot="description" class="${ssrRenderClass(ui.value.description({ class: unref(props).ui?.description }))}"${_scopeId}>`);
								ssrRenderSlot(_ctx.$slots, "description", {}, () => {
									_push(`${ssrInterpolate(unref(props).description)}`);
								}, _push, _parent, _scopeId);
								_push(`</div>`);
							} else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						_push(`</div>`);
					} else return [unref(props).icon || !!slots.leading ? (openBlock(), createBlock("div", {
						key: 0,
						"data-slot": "leading",
						class: ui.value.leading({ class: unref(props).ui?.leading })
					}, [renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(props).icon ? (openBlock(), createBlock(_sfc_main$4, {
						key: 0,
						name: unref(props).icon,
						"data-slot": "leadingIcon",
						class: ui.value.leadingIcon({ class: unref(props).ui?.leadingIcon })
					}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true), createVNode("div", {
						"data-slot": "wrapper",
						class: ui.value.wrapper({ class: unref(props).ui?.wrapper })
					}, [unref(props).to ? (openBlock(), createBlock(_sfc_main$5, mergeProps({
						key: 0,
						"aria-label": ariaLabel.value
					}, {
						"to": unref(props).to,
						"target": unref(props).target,
						..._ctx.$attrs,
						"data-slot": void 0
					}, {
						class: unref(prefix)("focus:outline-none peer"),
						raw: ""
					}), {
						default: withCtx(() => [createVNode("span", {
							class: unref(prefix)("absolute inset-0"),
							"aria-hidden": "true"
						}, null, 2)]),
						_: 1
					}, 16, ["aria-label", "class"])) : createCommentVNode("", true), renderSlot(_ctx.$slots, "default", {}, () => [unref(props).title || !!slots.title ? (openBlock(), createBlock("div", {
						key: 0,
						"data-slot": "title",
						class: ui.value.title({ class: unref(props).ui?.title })
					}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(unref(props).title), 1)])], 2)) : createCommentVNode("", true), unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
						key: 1,
						"data-slot": "description",
						class: ui.value.description({ class: unref(props).ui?.description })
					}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(unref(props).description), 1)])], 2)) : createCommentVNode("", true)])], 2)];
				}),
				_: 3
			}, _parent));
		};
	}
});
var _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/PageFeature.vue");
	return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/page-section.ts
var page_section_default = {
	"slots": {
		"root": "relative isolate",
		"container": "flex flex-col lg:grid py-16 sm:py-24 lg:py-32 gap-8 sm:gap-16",
		"wrapper": "",
		"header": "",
		"leading": "flex items-center mb-6",
		"leadingIcon": "size-10 shrink-0 text-primary",
		"headline": "mb-3",
		"title": "text-3xl sm:text-4xl lg:text-5xl text-pretty tracking-tight font-bold text-highlighted",
		"description": "text-base sm:text-lg text-muted",
		"body": "mt-8",
		"features": "grid",
		"footer": "mt-8",
		"links": "flex flex-wrap gap-x-6 gap-y-3"
	},
	"variants": {
		"orientation": {
			"horizontal": {
				"container": "lg:grid-cols-2 lg:items-center",
				"description": "text-pretty",
				"features": "gap-4"
			},
			"vertical": {
				"container": "",
				"headline": "justify-center",
				"leading": "justify-center",
				"title": "text-center",
				"description": "text-center text-balance",
				"links": "justify-center",
				"features": "sm:grid-cols-2 lg:grid-cols-3 gap-8"
			}
		},
		"reverse": { "true": { "wrapper": "order-last" } },
		"headline": { "true": { "headline": "font-semibold text-primary flex items-center gap-1.5" } },
		"title": { "true": { "description": "mt-6" } },
		"description": { "true": "" },
		"body": { "true": "" }
	},
	"compoundVariants": [
		{
			"orientation": "vertical",
			"title": true,
			"class": { "body": "mt-16" }
		},
		{
			"orientation": "vertical",
			"description": true,
			"class": { "body": "mt-16" }
		},
		{
			"orientation": "vertical",
			"body": true,
			"class": { "footer": "mt-16" }
		}
	]
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/PageSection.vue
var _sfc_main$2 = {
	__name: "PageSection",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false,
			default: "section"
		},
		headline: {
			type: String,
			required: false
		},
		icon: {
			type: null,
			required: false
		},
		title: {
			type: String,
			required: false
		},
		description: {
			type: String,
			required: false
		},
		links: {
			type: Array,
			required: false
		},
		features: {
			type: Array,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "vertical"
		},
		reverse: {
			type: Boolean,
			required: false
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		}
	},
	setup(__props) {
		const _props = __props;
		const slots = useSlots();
		const props = useComponentProps("pageSection", _props);
		const appConfig = useAppConfig();
		const prefix = usePrefix();
		const ui = computed(() => tv({
			extend: page_section_default,
			...appConfig.ui?.pageSection || {}
		})({
			orientation: props.orientation,
			reverse: props.reverse,
			title: !!props.title || !!slots.title,
			description: !!props.description || !!slots.description,
			body: !!slots.body || !!props.features?.length || !!slots.features
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
						ssrRenderSlot(_ctx.$slots, "top", {}, null, _push, _parent, _scopeId);
						_push(ssrRenderComponent(_sfc_main$7, {
							"data-slot": "container",
							class: ui.value.container({ class: unref(props).ui?.container })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									if (!!slots.header || unref(props).icon || !!slots.leading || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description || !!slots.body || unref(props).features?.length || !!slots.features || !!slots.footer || unref(props).links?.length || !!slots.links) {
										_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(props).ui?.wrapper }))}"${_scopeId}>`);
										if (!!slots.header || unref(props).icon || !!slots.leading || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description) {
											_push(`<div data-slot="header" class="${ssrRenderClass(ui.value.header({ class: unref(props).ui?.header }))}"${_scopeId}>`);
											ssrRenderSlot(_ctx.$slots, "header", {}, () => {
												if (unref(props).icon || !!slots.leading) {
													_push(`<div data-slot="leading" class="${ssrRenderClass(ui.value.leading({ class: unref(props).ui?.leading }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
														if (unref(props).icon) _push(ssrRenderComponent(_sfc_main$4, {
															name: unref(props).icon,
															"data-slot": "leadingIcon",
															class: ui.value.leadingIcon({ class: unref(props).ui?.leadingIcon })
														}, null, _parent, _scopeId));
														else _push(`<!---->`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
												if (unref(props).headline || !!slots.headline) {
													_push(`<div data-slot="headline" class="${ssrRenderClass(ui.value.headline({
														class: unref(props).ui?.headline,
														headline: !slots.headline
													}))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "headline", {}, () => {
														_push(`${ssrInterpolate(unref(props).headline)}`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
												if (unref(props).title || !!slots.title) {
													_push(`<h2 data-slot="title" class="${ssrRenderClass(ui.value.title({ class: unref(props).ui?.title }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "title", {}, () => {
														_push(`${ssrInterpolate(unref(props).title)}`);
													}, _push, _parent, _scopeId);
													_push(`</h2>`);
												} else _push(`<!---->`);
												if (unref(props).description || !!slots.description) {
													_push(`<div data-slot="description" class="${ssrRenderClass(ui.value.description({ class: unref(props).ui?.description }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "description", {}, () => {
														_push(`${ssrInterpolate(unref(props).description)}`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
											}, _push, _parent, _scopeId);
											_push(`</div>`);
										} else _push(`<!---->`);
										if (!!slots.body || unref(props).features?.length || !!slots.features) {
											_push(`<div data-slot="body" class="${ssrRenderClass(ui.value.body({ class: unref(props).ui?.body }))}"${_scopeId}>`);
											ssrRenderSlot(_ctx.$slots, "body", {}, () => {
												if (unref(props).features?.length || !!slots.features) {
													_push(`<ul data-slot="features" class="${ssrRenderClass(ui.value.features({ class: unref(props).ui?.features }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "features", {}, () => {
														_push(`<!--[-->`);
														ssrRenderList(unref(props).features, (feature, index) => {
															_push(ssrRenderComponent(_sfc_main$3, mergeProps({
																key: index,
																as: "li"
															}, { ref_for: true }, feature), null, _parent, _scopeId));
														});
														_push(`<!--]-->`);
													}, _push, _parent, _scopeId);
													_push(`</ul>`);
												} else _push(`<!---->`);
											}, _push, _parent, _scopeId);
											_push(`</div>`);
										} else _push(`<!---->`);
										if (!!slots.footer || unref(props).links?.length || !!slots.links) {
											_push(`<div data-slot="footer" class="${ssrRenderClass(ui.value.footer({ class: unref(props).ui?.footer }))}"${_scopeId}>`);
											ssrRenderSlot(_ctx.$slots, "footer", {}, () => {
												if (unref(props).links?.length || !!slots.links) {
													_push(`<div data-slot="links" class="${ssrRenderClass(ui.value.links({ class: unref(props).ui?.links }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "links", {}, () => {
														_push(`<!--[-->`);
														ssrRenderList(unref(props).links, (link, index) => {
															_push(ssrRenderComponent(_sfc_main$6, mergeProps({
																key: index,
																size: "lg"
															}, { ref_for: true }, link), null, _parent, _scopeId));
														});
														_push(`<!--]-->`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
											}, _push, _parent, _scopeId);
											_push(`</div>`);
										} else _push(`<!---->`);
										_push(`</div>`);
									} else _push(`<!---->`);
									if (!!slots.default) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
									else if (unref(props).orientation === "horizontal") _push(`<div class="${ssrRenderClass(unref(prefix)("hidden lg:block"))}"${_scopeId}></div>`);
									else _push(`<!---->`);
								} else return [!!slots.header || unref(props).icon || !!slots.leading || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description || !!slots.body || unref(props).features?.length || !!slots.features || !!slots.footer || unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
									key: 0,
									"data-slot": "wrapper",
									class: ui.value.wrapper({ class: unref(props).ui?.wrapper })
								}, [
									!!slots.header || unref(props).icon || !!slots.leading || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
										key: 0,
										"data-slot": "header",
										class: ui.value.header({ class: unref(props).ui?.header })
									}, [renderSlot(_ctx.$slots, "header", {}, () => [
										unref(props).icon || !!slots.leading ? (openBlock(), createBlock("div", {
											key: 0,
											"data-slot": "leading",
											class: ui.value.leading({ class: unref(props).ui?.leading })
										}, [renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(props).icon ? (openBlock(), createBlock(_sfc_main$4, {
											key: 0,
											name: unref(props).icon,
											"data-slot": "leadingIcon",
											class: ui.value.leadingIcon({ class: unref(props).ui?.leadingIcon })
										}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
										unref(props).headline || !!slots.headline ? (openBlock(), createBlock("div", {
											key: 1,
											"data-slot": "headline",
											class: ui.value.headline({
												class: unref(props).ui?.headline,
												headline: !slots.headline
											})
										}, [renderSlot(_ctx.$slots, "headline", {}, () => [createTextVNode(toDisplayString(unref(props).headline), 1)])], 2)) : createCommentVNode("", true),
										unref(props).title || !!slots.title ? (openBlock(), createBlock("h2", {
											key: 2,
											"data-slot": "title",
											class: ui.value.title({ class: unref(props).ui?.title })
										}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(unref(props).title), 1)])], 2)) : createCommentVNode("", true),
										unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
											key: 3,
											"data-slot": "description",
											class: ui.value.description({ class: unref(props).ui?.description })
										}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(unref(props).description), 1)])], 2)) : createCommentVNode("", true)
									])], 2)) : createCommentVNode("", true),
									!!slots.body || unref(props).features?.length || !!slots.features ? (openBlock(), createBlock("div", {
										key: 1,
										"data-slot": "body",
										class: ui.value.body({ class: unref(props).ui?.body })
									}, [renderSlot(_ctx.$slots, "body", {}, () => [unref(props).features?.length || !!slots.features ? (openBlock(), createBlock("ul", {
										key: 0,
										"data-slot": "features",
										class: ui.value.features({ class: unref(props).ui?.features })
									}, [renderSlot(_ctx.$slots, "features", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).features, (feature, index) => {
										return openBlock(), createBlock(_sfc_main$3, mergeProps({
											key: index,
											as: "li"
										}, { ref_for: true }, feature), null, 16);
									}), 128))])], 2)) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
									!!slots.footer || unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
										key: 2,
										"data-slot": "footer",
										class: ui.value.footer({ class: unref(props).ui?.footer })
									}, [renderSlot(_ctx.$slots, "footer", {}, () => [unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
										key: 0,
										"data-slot": "links",
										class: ui.value.links({ class: unref(props).ui?.links })
									}, [renderSlot(_ctx.$slots, "links", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).links, (link, index) => {
										return openBlock(), createBlock(_sfc_main$6, mergeProps({
											key: index,
											size: "lg"
										}, { ref_for: true }, link), null, 16);
									}), 128))])], 2)) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
								], 2)) : createCommentVNode("", true), !!slots.default ? renderSlot(_ctx.$slots, "default", {}, void 0, void 0, 1) : unref(props).orientation === "horizontal" ? (openBlock(), createBlock("div", {
									key: 2,
									class: unref(prefix)("hidden lg:block")
								}, null, 2)) : createCommentVNode("", true)];
							}),
							_: 3
						}, _parent, _scopeId));
						ssrRenderSlot(_ctx.$slots, "bottom", {}, null, _push, _parent, _scopeId);
					} else return [
						renderSlot(_ctx.$slots, "top"),
						createVNode(_sfc_main$7, {
							"data-slot": "container",
							class: ui.value.container({ class: unref(props).ui?.container })
						}, {
							default: withCtx(() => [!!slots.header || unref(props).icon || !!slots.leading || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description || !!slots.body || unref(props).features?.length || !!slots.features || !!slots.footer || unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
								key: 0,
								"data-slot": "wrapper",
								class: ui.value.wrapper({ class: unref(props).ui?.wrapper })
							}, [
								!!slots.header || unref(props).icon || !!slots.leading || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
									key: 0,
									"data-slot": "header",
									class: ui.value.header({ class: unref(props).ui?.header })
								}, [renderSlot(_ctx.$slots, "header", {}, () => [
									unref(props).icon || !!slots.leading ? (openBlock(), createBlock("div", {
										key: 0,
										"data-slot": "leading",
										class: ui.value.leading({ class: unref(props).ui?.leading })
									}, [renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(props).icon ? (openBlock(), createBlock(_sfc_main$4, {
										key: 0,
										name: unref(props).icon,
										"data-slot": "leadingIcon",
										class: ui.value.leadingIcon({ class: unref(props).ui?.leadingIcon })
									}, null, 8, ["name", "class"])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
									unref(props).headline || !!slots.headline ? (openBlock(), createBlock("div", {
										key: 1,
										"data-slot": "headline",
										class: ui.value.headline({
											class: unref(props).ui?.headline,
											headline: !slots.headline
										})
									}, [renderSlot(_ctx.$slots, "headline", {}, () => [createTextVNode(toDisplayString(unref(props).headline), 1)])], 2)) : createCommentVNode("", true),
									unref(props).title || !!slots.title ? (openBlock(), createBlock("h2", {
										key: 2,
										"data-slot": "title",
										class: ui.value.title({ class: unref(props).ui?.title })
									}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(unref(props).title), 1)])], 2)) : createCommentVNode("", true),
									unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
										key: 3,
										"data-slot": "description",
										class: ui.value.description({ class: unref(props).ui?.description })
									}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(unref(props).description), 1)])], 2)) : createCommentVNode("", true)
								])], 2)) : createCommentVNode("", true),
								!!slots.body || unref(props).features?.length || !!slots.features ? (openBlock(), createBlock("div", {
									key: 1,
									"data-slot": "body",
									class: ui.value.body({ class: unref(props).ui?.body })
								}, [renderSlot(_ctx.$slots, "body", {}, () => [unref(props).features?.length || !!slots.features ? (openBlock(), createBlock("ul", {
									key: 0,
									"data-slot": "features",
									class: ui.value.features({ class: unref(props).ui?.features })
								}, [renderSlot(_ctx.$slots, "features", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).features, (feature, index) => {
									return openBlock(), createBlock(_sfc_main$3, mergeProps({
										key: index,
										as: "li"
									}, { ref_for: true }, feature), null, 16);
								}), 128))])], 2)) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
								!!slots.footer || unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
									key: 2,
									"data-slot": "footer",
									class: ui.value.footer({ class: unref(props).ui?.footer })
								}, [renderSlot(_ctx.$slots, "footer", {}, () => [unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
									key: 0,
									"data-slot": "links",
									class: ui.value.links({ class: unref(props).ui?.links })
								}, [renderSlot(_ctx.$slots, "links", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).links, (link, index) => {
									return openBlock(), createBlock(_sfc_main$6, mergeProps({
										key: index,
										size: "lg"
									}, { ref_for: true }, link), null, 16);
								}), 128))])], 2)) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
							], 2)) : createCommentVNode("", true), !!slots.default ? renderSlot(_ctx.$slots, "default", {}, void 0, void 0, 1) : unref(props).orientation === "horizontal" ? (openBlock(), createBlock("div", {
								key: 2,
								class: unref(prefix)("hidden lg:block")
							}, null, 2)) : createCommentVNode("", true)]),
							_: 3
						}, 8, ["class"]),
						renderSlot(_ctx.$slots, "bottom")
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/PageSection.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/page-hero.ts
var page_hero_default = {
	"slots": {
		"root": "relative isolate",
		"container": "flex flex-col lg:grid py-24 sm:py-32 lg:py-40 gap-16 sm:gap-y-24",
		"wrapper": "",
		"header": "",
		"headline": "mb-4",
		"title": "text-5xl sm:text-7xl text-pretty tracking-tight font-bold text-highlighted",
		"description": "text-lg sm:text-xl/8 text-muted",
		"body": "mt-10",
		"footer": "mt-10",
		"links": "flex flex-wrap gap-x-6 gap-y-3"
	},
	"variants": {
		"orientation": {
			"horizontal": {
				"container": "lg:grid-cols-2 lg:items-center",
				"description": "text-pretty"
			},
			"vertical": {
				"container": "",
				"headline": "justify-center",
				"wrapper": "text-center",
				"description": "text-balance",
				"links": "justify-center"
			}
		},
		"reverse": { "true": { "wrapper": "order-last" } },
		"headline": { "true": { "headline": "font-semibold text-primary flex items-center gap-1.5" } },
		"title": { "true": { "description": "mt-6" } }
	}
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/PageHero.vue
var _sfc_main$1 = {
	__name: "PageHero",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false
		},
		headline: {
			type: String,
			required: false
		},
		title: {
			type: String,
			required: false
		},
		description: {
			type: String,
			required: false
		},
		links: {
			type: Array,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "vertical"
		},
		reverse: {
			type: Boolean,
			required: false
		},
		class: {
			type: null,
			required: false
		},
		ui: {
			type: Object,
			required: false
		}
	},
	setup(__props) {
		const _props = __props;
		const slots = useSlots();
		const props = useComponentProps("pageHero", _props);
		const appConfig = useAppConfig();
		const prefix = usePrefix();
		const ui = computed(() => tv({
			extend: page_hero_default,
			...appConfig.ui?.pageHero || {}
		})({
			orientation: props.orientation,
			reverse: props.reverse,
			title: !!props.title || !!slots.title
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
						ssrRenderSlot(_ctx.$slots, "top", {}, null, _push, _parent, _scopeId);
						_push(ssrRenderComponent(_sfc_main$7, {
							"data-slot": "container",
							class: ui.value.container({ class: unref(props).ui?.container })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) {
									if (!!slots.header || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description || !!slots.body || !!slots.footer || unref(props).links?.length || !!slots.links) {
										_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(props).ui?.wrapper }))}"${_scopeId}>`);
										if (!!slots.header || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description) {
											_push(`<div data-slot="header" class="${ssrRenderClass(ui.value.header({ class: unref(props).ui?.header }))}"${_scopeId}>`);
											ssrRenderSlot(_ctx.$slots, "header", {}, () => {
												if (unref(props).headline || !!slots.headline) {
													_push(`<div data-slot="headline" class="${ssrRenderClass(ui.value.headline({
														class: unref(props).ui?.headline,
														headline: !slots.headline
													}))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "headline", {}, () => {
														_push(`${ssrInterpolate(unref(props).headline)}`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
												if (unref(props).title || !!slots.title) {
													_push(`<h1 data-slot="title" class="${ssrRenderClass(ui.value.title({ class: unref(props).ui?.title }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "title", {}, () => {
														_push(`${ssrInterpolate(unref(props).title)}`);
													}, _push, _parent, _scopeId);
													_push(`</h1>`);
												} else _push(`<!---->`);
												if (unref(props).description || !!slots.description) {
													_push(`<div data-slot="description" class="${ssrRenderClass(ui.value.description({ class: unref(props).ui?.description }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "description", {}, () => {
														_push(`${ssrInterpolate(unref(props).description)}`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
											}, _push, _parent, _scopeId);
											_push(`</div>`);
										} else _push(`<!---->`);
										if (!!slots.body) {
											_push(`<div data-slot="body" class="${ssrRenderClass(ui.value.body({ class: unref(props).ui?.body }))}"${_scopeId}>`);
											ssrRenderSlot(_ctx.$slots, "body", {}, null, _push, _parent, _scopeId);
											_push(`</div>`);
										} else _push(`<!---->`);
										if (!!slots.footer || unref(props).links?.length || !!slots.links) {
											_push(`<div data-slot="footer" class="${ssrRenderClass(ui.value.footer({ class: unref(props).ui?.footer }))}"${_scopeId}>`);
											ssrRenderSlot(_ctx.$slots, "footer", {}, () => {
												if (unref(props).links?.length || !!slots.links) {
													_push(`<div data-slot="links" class="${ssrRenderClass(ui.value.links({ class: unref(props).ui?.links }))}"${_scopeId}>`);
													ssrRenderSlot(_ctx.$slots, "links", {}, () => {
														_push(`<!--[-->`);
														ssrRenderList(unref(props).links, (link, index) => {
															_push(ssrRenderComponent(_sfc_main$6, mergeProps({
																key: index,
																size: "xl"
															}, { ref_for: true }, link), null, _parent, _scopeId));
														});
														_push(`<!--]-->`);
													}, _push, _parent, _scopeId);
													_push(`</div>`);
												} else _push(`<!---->`);
											}, _push, _parent, _scopeId);
											_push(`</div>`);
										} else _push(`<!---->`);
										_push(`</div>`);
									} else _push(`<!---->`);
									if (!!slots.default) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
									else if (unref(props).orientation === "horizontal") _push(`<div class="${ssrRenderClass(unref(prefix)("hidden lg:block"))}"${_scopeId}></div>`);
									else _push(`<!---->`);
								} else return [!!slots.header || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description || !!slots.body || !!slots.footer || unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
									key: 0,
									"data-slot": "wrapper",
									class: ui.value.wrapper({ class: unref(props).ui?.wrapper })
								}, [
									!!slots.header || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
										key: 0,
										"data-slot": "header",
										class: ui.value.header({ class: unref(props).ui?.header })
									}, [renderSlot(_ctx.$slots, "header", {}, () => [
										unref(props).headline || !!slots.headline ? (openBlock(), createBlock("div", {
											key: 0,
											"data-slot": "headline",
											class: ui.value.headline({
												class: unref(props).ui?.headline,
												headline: !slots.headline
											})
										}, [renderSlot(_ctx.$slots, "headline", {}, () => [createTextVNode(toDisplayString(unref(props).headline), 1)])], 2)) : createCommentVNode("", true),
										unref(props).title || !!slots.title ? (openBlock(), createBlock("h1", {
											key: 1,
											"data-slot": "title",
											class: ui.value.title({ class: unref(props).ui?.title })
										}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(unref(props).title), 1)])], 2)) : createCommentVNode("", true),
										unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
											key: 2,
											"data-slot": "description",
											class: ui.value.description({ class: unref(props).ui?.description })
										}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(unref(props).description), 1)])], 2)) : createCommentVNode("", true)
									])], 2)) : createCommentVNode("", true),
									!!slots.body ? (openBlock(), createBlock("div", {
										key: 1,
										"data-slot": "body",
										class: ui.value.body({ class: unref(props).ui?.body })
									}, [renderSlot(_ctx.$slots, "body")], 2)) : createCommentVNode("", true),
									!!slots.footer || unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
										key: 2,
										"data-slot": "footer",
										class: ui.value.footer({ class: unref(props).ui?.footer })
									}, [renderSlot(_ctx.$slots, "footer", {}, () => [unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
										key: 0,
										"data-slot": "links",
										class: ui.value.links({ class: unref(props).ui?.links })
									}, [renderSlot(_ctx.$slots, "links", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).links, (link, index) => {
										return openBlock(), createBlock(_sfc_main$6, mergeProps({
											key: index,
											size: "xl"
										}, { ref_for: true }, link), null, 16);
									}), 128))])], 2)) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
								], 2)) : createCommentVNode("", true), !!slots.default ? renderSlot(_ctx.$slots, "default", {}, void 0, void 0, 1) : unref(props).orientation === "horizontal" ? (openBlock(), createBlock("div", {
									key: 2,
									class: unref(prefix)("hidden lg:block")
								}, null, 2)) : createCommentVNode("", true)];
							}),
							_: 3
						}, _parent, _scopeId));
						ssrRenderSlot(_ctx.$slots, "bottom", {}, null, _push, _parent, _scopeId);
					} else return [
						renderSlot(_ctx.$slots, "top"),
						createVNode(_sfc_main$7, {
							"data-slot": "container",
							class: ui.value.container({ class: unref(props).ui?.container })
						}, {
							default: withCtx(() => [!!slots.header || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description || !!slots.body || !!slots.footer || unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
								key: 0,
								"data-slot": "wrapper",
								class: ui.value.wrapper({ class: unref(props).ui?.wrapper })
							}, [
								!!slots.header || unref(props).headline || !!slots.headline || unref(props).title || !!slots.title || unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
									key: 0,
									"data-slot": "header",
									class: ui.value.header({ class: unref(props).ui?.header })
								}, [renderSlot(_ctx.$slots, "header", {}, () => [
									unref(props).headline || !!slots.headline ? (openBlock(), createBlock("div", {
										key: 0,
										"data-slot": "headline",
										class: ui.value.headline({
											class: unref(props).ui?.headline,
											headline: !slots.headline
										})
									}, [renderSlot(_ctx.$slots, "headline", {}, () => [createTextVNode(toDisplayString(unref(props).headline), 1)])], 2)) : createCommentVNode("", true),
									unref(props).title || !!slots.title ? (openBlock(), createBlock("h1", {
										key: 1,
										"data-slot": "title",
										class: ui.value.title({ class: unref(props).ui?.title })
									}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(unref(props).title), 1)])], 2)) : createCommentVNode("", true),
									unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
										key: 2,
										"data-slot": "description",
										class: ui.value.description({ class: unref(props).ui?.description })
									}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(unref(props).description), 1)])], 2)) : createCommentVNode("", true)
								])], 2)) : createCommentVNode("", true),
								!!slots.body ? (openBlock(), createBlock("div", {
									key: 1,
									"data-slot": "body",
									class: ui.value.body({ class: unref(props).ui?.body })
								}, [renderSlot(_ctx.$slots, "body")], 2)) : createCommentVNode("", true),
								!!slots.footer || unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
									key: 2,
									"data-slot": "footer",
									class: ui.value.footer({ class: unref(props).ui?.footer })
								}, [renderSlot(_ctx.$slots, "footer", {}, () => [unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
									key: 0,
									"data-slot": "links",
									class: ui.value.links({ class: unref(props).ui?.links })
								}, [renderSlot(_ctx.$slots, "links", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).links, (link, index) => {
									return openBlock(), createBlock(_sfc_main$6, mergeProps({
										key: index,
										size: "xl"
									}, { ref_for: true }, link), null, 16);
								}), 128))])], 2)) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
							], 2)) : createCommentVNode("", true), !!slots.default ? renderSlot(_ctx.$slots, "default", {}, void 0, void 0, 1) : unref(props).orientation === "horizontal" ? (openBlock(), createBlock("div", {
								key: 2,
								class: unref(prefix)("hidden lg:block")
							}, null, 2)) : createCommentVNode("", true)]),
							_: 3
						}, 8, ["class"]),
						renderSlot(_ctx.$slots, "bottom")
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/PageHero.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Composables/useSeo.js
var SITE_URL = "https://osrs-events.com";
var SITE_NAME = "OSRS Events";
var DEFAULT_OG_IMAGE = "/og-image.png";
/**
* Inertia equivalent of frontend/app/composables/useSeo.ts. Same contract —
* title, description, image, noindex, ogType, jsonLd — but instead of Nuxt's
* useSeoMeta()/useHead() composables (which mutate a shared head singleton
* outside the render tree), this returns a render function that emits an
* Inertia <Head> block. Head's tag-diffing during renderToString is what
* actually gets these tags into the SSR HTML — the same job @inertiaHead
* does in resources/views/app.blade.php on the Laravel side.
*
* Usage in a page's <template>:
*   <SeoHead :options="{ title, description, jsonLd }" />
*
* The one behavioural difference from the Nuxt version worth flagging: Nuxt's
* useRoute().path drives the canonical URL automatically. Inertia has no
* router-owned "current path" composable in the same sense — usePage().url
* is the closest equivalent and is used here, but it includes the query
* string, unlike Nuxt's route.path. A real port would need to strip it the
* same way the Nuxt version deliberately does (see useSeo.ts's own comment
* on why it uses route.path over fullPath).
*/
function useSeoData(options) {
	const page = usePage();
	const rawOptions = computed(() => typeof options === "function" ? options() : options);
	const resolved = computed(() => ({
		...rawOptions.value,
		title: `${rawOptions.value.title} - ${SITE_NAME}`
	}));
	const path = computed(() => page.url.split("?")[0]);
	return {
		resolved,
		canonical: computed(() => new URL(path.value, SITE_URL).toString()),
		imageUrl: computed(() => new URL(resolved.value.image ?? DEFAULT_OG_IMAGE, SITE_URL).toString()),
		robots: computed(() => resolved.value.noindex ? "noindex, follow" : "index, follow"),
		jsonLdBlocks: computed(() => {
			const { jsonLd } = resolved.value;
			if (!jsonLd) return [];
			return (Array.isArray(jsonLd) ? jsonLd : [jsonLd]).map((block) => ({
				"@context": "https://schema.org",
				...block
			}));
		}),
		Head
	};
}
//#endregion
//#region resources/js/Pages/SnakesAndLadders.vue
var _sfc_main = {
	__name: "SnakesAndLadders",
	__ssrInlineRender: true,
	props: {
		steps: {
			type: Array,
			required: true
		},
		sizes: {
			type: Array,
			required: true
		},
		faqs: {
			type: Array,
			required: true
		}
	},
	setup(__props) {
		const props = __props;
		const { resolved, canonical, imageUrl, robots, Head } = useSeoData({
			title: "OSRS Snakes and Ladders — clan event boards",
			description: "Create a Snakes and Ladders board for your Old School RuneScape clan. Set custom tiles, invite your team via Discord, and race to the top.",
			jsonLd: [{
				"@type": "FAQPage",
				mainEntity: props.faqs.map((faq) => ({
					"@type": "Question",
					name: faq.question,
					acceptedAnswer: {
						"@type": "Answer",
						text: faq.answer
					}
				}))
			}, {
				"@type": "HowTo",
				name: "How it works",
				description: "Five steps from empty board to a running clan event.",
				step: props.steps.map((step, i) => ({
					"@type": "HowToStep",
					position: i + 1,
					name: step.title,
					text: step.description
				}))
			}]
		});
		return (_ctx, _push, _parent, _attrs) => {
			const _component_u_main = _sfc_main$8;
			const _component_u_page = _sfc_main$9;
			const _component_u_page_hero = _sfc_main$1;
			const _component_u_button = _sfc_main$6;
			const _component_u_page_section = _sfc_main$2;
			const _component_u_container = _sfc_main$7;
			_push(`<!--[-->`);
			_push(ssrRenderComponent(unref(Head), { title: unref(resolved).title }, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<meta name="description"${ssrRenderAttr("content", unref(resolved).description)}${_scopeId}><link rel="canonical"${ssrRenderAttr("href", unref(canonical))}${_scopeId}><meta name="robots"${ssrRenderAttr("content", unref(robots))}${_scopeId}><meta property="og:title"${ssrRenderAttr("content", unref(resolved).title)}${_scopeId}><meta property="og:description"${ssrRenderAttr("content", unref(resolved).description)}${_scopeId}><meta property="og:type" content="website"${_scopeId}><meta property="og:url"${ssrRenderAttr("content", unref(canonical))}${_scopeId}><meta property="og:site_name" content="OSRS Events"${_scopeId}><meta property="og:image"${ssrRenderAttr("content", unref(imageUrl))}${_scopeId}><meta property="og:image:width" content="1200"${_scopeId}><meta property="og:image:height" content="630"${_scopeId}><meta name="twitter:card" content="summary_large_image"${_scopeId}><meta name="twitter:title"${ssrRenderAttr("content", unref(resolved).title)}${_scopeId}><meta name="twitter:description"${ssrRenderAttr("content", unref(resolved).description)}${_scopeId}><meta name="twitter:image"${ssrRenderAttr("content", unref(imageUrl))}${_scopeId}>`);
					else return [
						createVNode("meta", {
							name: "description",
							content: unref(resolved).description
						}, null, 8, ["content"]),
						createVNode("link", {
							rel: "canonical",
							href: unref(canonical)
						}, null, 8, ["href"]),
						createVNode("meta", {
							name: "robots",
							content: unref(robots)
						}, null, 8, ["content"]),
						createVNode("meta", {
							property: "og:title",
							content: unref(resolved).title
						}, null, 8, ["content"]),
						createVNode("meta", {
							property: "og:description",
							content: unref(resolved).description
						}, null, 8, ["content"]),
						createVNode("meta", {
							property: "og:type",
							content: "website"
						}),
						createVNode("meta", {
							property: "og:url",
							content: unref(canonical)
						}, null, 8, ["content"]),
						createVNode("meta", {
							property: "og:site_name",
							content: "OSRS Events"
						}),
						createVNode("meta", {
							property: "og:image",
							content: unref(imageUrl)
						}, null, 8, ["content"]),
						createVNode("meta", {
							property: "og:image:width",
							content: "1200"
						}),
						createVNode("meta", {
							property: "og:image:height",
							content: "630"
						}),
						createVNode("meta", {
							name: "twitter:card",
							content: "summary_large_image"
						}),
						createVNode("meta", {
							name: "twitter:title",
							content: unref(resolved).title
						}, null, 8, ["content"]),
						createVNode("meta", {
							name: "twitter:description",
							content: unref(resolved).description
						}, null, 8, ["content"]),
						createVNode("meta", {
							name: "twitter:image",
							content: unref(imageUrl)
						}, null, 8, ["content"])
					];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(_component_u_main, null, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_u_page, null, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) {
								_push(ssrRenderComponent(_component_u_page_hero, {
									title: "OSRS Snakes and Ladders for your clan",
									description: "Turn any Old School RuneScape clan event into a Snakes and Ladders board — set the tiles, invite your team, and race to the top."
								}, {
									links: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) {
											_push(ssrRenderComponent(_component_u_button, {
												size: "xl",
												color: "primary",
												icon: "i-lucide-plus",
												label: "Start a board",
												href: "/boards"
											}, null, _parent, _scopeId));
											_push(ssrRenderComponent(_component_u_button, {
												to: "/boards",
												size: "xl",
												color: "neutral",
												variant: "outline",
												"trailing-icon": "i-lucide-arrow-right",
												label: "Browse boards"
											}, null, _parent, _scopeId));
										} else return [createVNode(_component_u_button, {
											size: "xl",
											color: "primary",
											icon: "i-lucide-plus",
											label: "Start a board",
											href: "/boards"
										}), createVNode(_component_u_button, {
											to: "/boards",
											size: "xl",
											color: "neutral",
											variant: "outline",
											"trailing-icon": "i-lucide-arrow-right",
											label: "Browse boards"
										})];
									}),
									_: 1
								}, _parent, _scopeId));
								_push(ssrRenderComponent(_component_u_page_section, {
									title: "How it works",
									description: "Five steps from empty board to a running clan event.",
									features: __props.steps
								}, null, _parent, _scopeId));
								_push(ssrRenderComponent(_component_u_page_section, { title: "Why Snakes and Ladders" }, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(_component_u_container, { class: "max-w-3xl" }, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(`<p class="text-lg text-muted leading-relaxed"${_scopeId}> Clan events are more fun with structure and a little bit of luck. Snakes and Ladders boards give every member a shared goal, a visible race, and the chance of a lucky climb — or an unlucky slide back down. </p>`);
												else return [createVNode("p", { class: "text-lg text-muted leading-relaxed" }, " Clan events are more fun with structure and a little bit of luck. Snakes and Ladders boards give every member a shared goal, a visible race, and the chance of a lucky climb — or an unlucky slide back down. ")];
											}),
											_: 1
										}, _parent, _scopeId));
										else return [createVNode(_component_u_container, { class: "max-w-3xl" }, {
											default: withCtx(() => [createVNode("p", { class: "text-lg text-muted leading-relaxed" }, " Clan events are more fun with structure and a little bit of luck. Snakes and Ladders boards give every member a shared goal, a visible race, and the chance of a lucky climb — or an unlucky slide back down. ")]),
											_: 1
										})];
									}),
									_: 1
								}, _parent, _scopeId));
								_push(ssrRenderComponent(_component_u_page_section, {
									title: "Board sizes",
									description: "Pick the size that fits your event.",
									features: __props.sizes
								}, null, _parent, _scopeId));
								_push(ssrRenderComponent(_component_u_page_section, { title: "Solo or team mode" }, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(_component_u_container, { class: "max-w-3xl" }, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(`<p class="text-lg text-muted leading-relaxed"${_scopeId}> Boards support both individual players racing independently and teams pooling progress together — pick whichever fits your clan&#39;s event. </p>`);
												else return [createVNode("p", { class: "text-lg text-muted leading-relaxed" }, " Boards support both individual players racing independently and teams pooling progress together — pick whichever fits your clan's event. ")];
											}),
											_: 1
										}, _parent, _scopeId));
										else return [createVNode(_component_u_container, { class: "max-w-3xl" }, {
											default: withCtx(() => [createVNode("p", { class: "text-lg text-muted leading-relaxed" }, " Boards support both individual players racing independently and teams pooling progress together — pick whichever fits your clan's event. ")]),
											_: 1
										})];
									}),
									_: 1
								}, _parent, _scopeId));
								_push(ssrRenderComponent(_component_u_page_section, { title: "Frequently asked questions" }, {
									default: withCtx((_, _push, _parent, _scopeId) => {
										if (_push) _push(ssrRenderComponent(_component_u_container, { class: "max-w-3xl" }, {
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) {
													_push(`<dl class="divide-y divide-default"${_scopeId}><!--[-->`);
													ssrRenderList(__props.faqs, (faq) => {
														_push(`<div class="py-6 first:pt-0 last:pb-0"${_scopeId}><dt class="text-lg font-semibold"${_scopeId}>${ssrInterpolate(faq.question)}</dt><dd class="mt-2 text-muted leading-relaxed"${_scopeId}>${ssrInterpolate(faq.answer)}</dd></div>`);
													});
													_push(`<!--]--></dl>`);
												} else return [createVNode("dl", { class: "divide-y divide-default" }, [(openBlock(true), createBlock(Fragment, null, renderList(__props.faqs, (faq) => {
													return openBlock(), createBlock("div", {
														key: faq.question,
														class: "py-6 first:pt-0 last:pb-0"
													}, [createVNode("dt", { class: "text-lg font-semibold" }, toDisplayString(faq.question), 1), createVNode("dd", { class: "mt-2 text-muted leading-relaxed" }, toDisplayString(faq.answer), 1)]);
												}), 128))])];
											}),
											_: 1
										}, _parent, _scopeId));
										else return [createVNode(_component_u_container, { class: "max-w-3xl" }, {
											default: withCtx(() => [createVNode("dl", { class: "divide-y divide-default" }, [(openBlock(true), createBlock(Fragment, null, renderList(__props.faqs, (faq) => {
												return openBlock(), createBlock("div", {
													key: faq.question,
													class: "py-6 first:pt-0 last:pb-0"
												}, [createVNode("dt", { class: "text-lg font-semibold" }, toDisplayString(faq.question), 1), createVNode("dd", { class: "mt-2 text-muted leading-relaxed" }, toDisplayString(faq.answer), 1)]);
											}), 128))])]),
											_: 1
										})];
									}),
									_: 1
								}, _parent, _scopeId));
							} else return [
								createVNode(_component_u_page_hero, {
									title: "OSRS Snakes and Ladders for your clan",
									description: "Turn any Old School RuneScape clan event into a Snakes and Ladders board — set the tiles, invite your team, and race to the top."
								}, {
									links: withCtx(() => [createVNode(_component_u_button, {
										size: "xl",
										color: "primary",
										icon: "i-lucide-plus",
										label: "Start a board",
										href: "/boards"
									}), createVNode(_component_u_button, {
										to: "/boards",
										size: "xl",
										color: "neutral",
										variant: "outline",
										"trailing-icon": "i-lucide-arrow-right",
										label: "Browse boards"
									})]),
									_: 1
								}),
								createVNode(_component_u_page_section, {
									title: "How it works",
									description: "Five steps from empty board to a running clan event.",
									features: __props.steps
								}, null, 8, ["features"]),
								createVNode(_component_u_page_section, { title: "Why Snakes and Ladders" }, {
									default: withCtx(() => [createVNode(_component_u_container, { class: "max-w-3xl" }, {
										default: withCtx(() => [createVNode("p", { class: "text-lg text-muted leading-relaxed" }, " Clan events are more fun with structure and a little bit of luck. Snakes and Ladders boards give every member a shared goal, a visible race, and the chance of a lucky climb — or an unlucky slide back down. ")]),
										_: 1
									})]),
									_: 1
								}),
								createVNode(_component_u_page_section, {
									title: "Board sizes",
									description: "Pick the size that fits your event.",
									features: __props.sizes
								}, null, 8, ["features"]),
								createVNode(_component_u_page_section, { title: "Solo or team mode" }, {
									default: withCtx(() => [createVNode(_component_u_container, { class: "max-w-3xl" }, {
										default: withCtx(() => [createVNode("p", { class: "text-lg text-muted leading-relaxed" }, " Boards support both individual players racing independently and teams pooling progress together — pick whichever fits your clan's event. ")]),
										_: 1
									})]),
									_: 1
								}),
								createVNode(_component_u_page_section, { title: "Frequently asked questions" }, {
									default: withCtx(() => [createVNode(_component_u_container, { class: "max-w-3xl" }, {
										default: withCtx(() => [createVNode("dl", { class: "divide-y divide-default" }, [(openBlock(true), createBlock(Fragment, null, renderList(__props.faqs, (faq) => {
											return openBlock(), createBlock("div", {
												key: faq.question,
												class: "py-6 first:pt-0 last:pb-0"
											}, [createVNode("dt", { class: "text-lg font-semibold" }, toDisplayString(faq.question), 1), createVNode("dd", { class: "mt-2 text-muted leading-relaxed" }, toDisplayString(faq.answer), 1)]);
										}), 128))])]),
										_: 1
									})]),
									_: 1
								})
							];
						}),
						_: 1
					}, _parent, _scopeId));
					else return [createVNode(_component_u_page, null, {
						default: withCtx(() => [
							createVNode(_component_u_page_hero, {
								title: "OSRS Snakes and Ladders for your clan",
								description: "Turn any Old School RuneScape clan event into a Snakes and Ladders board — set the tiles, invite your team, and race to the top."
							}, {
								links: withCtx(() => [createVNode(_component_u_button, {
									size: "xl",
									color: "primary",
									icon: "i-lucide-plus",
									label: "Start a board",
									href: "/boards"
								}), createVNode(_component_u_button, {
									to: "/boards",
									size: "xl",
									color: "neutral",
									variant: "outline",
									"trailing-icon": "i-lucide-arrow-right",
									label: "Browse boards"
								})]),
								_: 1
							}),
							createVNode(_component_u_page_section, {
								title: "How it works",
								description: "Five steps from empty board to a running clan event.",
								features: __props.steps
							}, null, 8, ["features"]),
							createVNode(_component_u_page_section, { title: "Why Snakes and Ladders" }, {
								default: withCtx(() => [createVNode(_component_u_container, { class: "max-w-3xl" }, {
									default: withCtx(() => [createVNode("p", { class: "text-lg text-muted leading-relaxed" }, " Clan events are more fun with structure and a little bit of luck. Snakes and Ladders boards give every member a shared goal, a visible race, and the chance of a lucky climb — or an unlucky slide back down. ")]),
									_: 1
								})]),
								_: 1
							}),
							createVNode(_component_u_page_section, {
								title: "Board sizes",
								description: "Pick the size that fits your event.",
								features: __props.sizes
							}, null, 8, ["features"]),
							createVNode(_component_u_page_section, { title: "Solo or team mode" }, {
								default: withCtx(() => [createVNode(_component_u_container, { class: "max-w-3xl" }, {
									default: withCtx(() => [createVNode("p", { class: "text-lg text-muted leading-relaxed" }, " Boards support both individual players racing independently and teams pooling progress together — pick whichever fits your clan's event. ")]),
									_: 1
								})]),
								_: 1
							}),
							createVNode(_component_u_page_section, { title: "Frequently asked questions" }, {
								default: withCtx(() => [createVNode(_component_u_container, { class: "max-w-3xl" }, {
									default: withCtx(() => [createVNode("dl", { class: "divide-y divide-default" }, [(openBlock(true), createBlock(Fragment, null, renderList(__props.faqs, (faq) => {
										return openBlock(), createBlock("div", {
											key: faq.question,
											class: "py-6 first:pt-0 last:pb-0"
										}, [createVNode("dt", { class: "text-lg font-semibold" }, toDisplayString(faq.question), 1), createVNode("dd", { class: "mt-2 text-muted leading-relaxed" }, toDisplayString(faq.answer), 1)]);
									}), 128))])]),
									_: 1
								})]),
								_: 1
							})
						]),
						_: 1
					})];
				}),
				_: 1
			}, _parent));
			_push(`<!--]-->`);
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/SnakesAndLadders.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };
