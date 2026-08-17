import { d as Slot, l as useComponentProps, s as tv, u as Primitive, y as useAppConfig } from "./Button-BsqkFqP1.js";
import { computed, createBlock, createCommentVNode, createVNode, mergeProps, onBeforeUpdate, openBlock, renderSlot, shallowRef, unref, useSSRContext, useSlots, withCtx } from "vue";
import { ssrRenderClass, ssrRenderComponent, ssrRenderSlot } from "vue/server-renderer";
//#region virtual:nuxt-ui-templates/ui/container.ts
var container_default = { "base": "w-full max-w-(--ui-container) mx-auto px-4 sm:px-6 lg:px-8" };
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Container.vue
var _sfc_main$2 = {
	__name: "Container",
	__ssrInlineRender: true,
	props: {
		as: {
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
		}
	},
	setup(__props) {
		const props = useComponentProps("container", __props);
		const appConfig = useAppConfig();
		const ui = computed(() => tv({
			extend: container_default,
			...appConfig.ui?.container || {}
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: unref(props).as,
				class: ui.value({ class: [unref(props).ui?.base, unref(props).class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default")];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Container.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/page.ts
var page_default = {
	"slots": {
		"root": "flex flex-col lg:grid lg:grid-cols-10 lg:gap-10",
		"left": "lg:col-span-2",
		"center": "lg:col-span-8",
		"right": "lg:col-span-2 order-first lg:order-last"
	},
	"variants": {
		"left": { "true": "" },
		"right": { "true": "" }
	},
	"compoundVariants": [{
		"left": true,
		"right": true,
		"class": { "center": "lg:col-span-6" }
	}, {
		"left": false,
		"right": false,
		"class": { "center": "lg:col-span-10" }
	}]
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Page.vue
var _sfc_main$1 = {
	__name: "Page",
	__ssrInlineRender: true,
	props: {
		as: {
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
		}
	},
	setup(__props) {
		const _props = __props;
		const slots = useSlots();
		const props = useComponentProps("page", _props);
		const appConfig = useAppConfig();
		const hasLeft = shallowRef(!!slots.left);
		const hasRight = shallowRef(!!slots.right);
		onBeforeUpdate(() => {
			hasLeft.value = !!slots.left;
			hasRight.value = !!slots.right;
		});
		const ui = computed(() => tv({
			extend: page_default,
			...appConfig.ui?.page || {}
		})({
			left: hasLeft.value,
			right: hasRight.value
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: unref(props).as,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(props).ui?.root, unref(props).class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (!!slots.left) _push(ssrRenderComponent(unref(Slot), {
							"data-slot": "left",
							class: ui.value.left({ class: unref(props).ui?.left })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "left", {}, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "left")];
							}),
							_: 3
						}, _parent, _scopeId));
						else _push(`<!---->`);
						_push(`<div data-slot="center" class="${ssrRenderClass(ui.value.center({ class: unref(props).ui?.center }))}"${_scopeId}>`);
						ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
						_push(`</div>`);
						if (!!slots.right) _push(ssrRenderComponent(unref(Slot), {
							"data-slot": "right",
							class: ui.value.right({ class: unref(props).ui?.right })
						}, {
							default: withCtx((_, _push, _parent, _scopeId) => {
								if (_push) ssrRenderSlot(_ctx.$slots, "right", {}, null, _push, _parent, _scopeId);
								else return [renderSlot(_ctx.$slots, "right")];
							}),
							_: 3
						}, _parent, _scopeId));
						else _push(`<!---->`);
					} else return [
						!!slots.left ? (openBlock(), createBlock(unref(Slot), {
							key: 0,
							"data-slot": "left",
							class: ui.value.left({ class: unref(props).ui?.left })
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "left")]),
							_: 3
						}, 8, ["class"])) : createCommentVNode("", true),
						createVNode("div", {
							"data-slot": "center",
							class: ui.value.center({ class: unref(props).ui?.center })
						}, [renderSlot(_ctx.$slots, "default")], 2),
						!!slots.right ? (openBlock(), createBlock(unref(Slot), {
							key: 1,
							"data-slot": "right",
							class: ui.value.right({ class: unref(props).ui?.right })
						}, {
							default: withCtx(() => [renderSlot(_ctx.$slots, "right")]),
							_: 3
						}, 8, ["class"])) : createCommentVNode("", true)
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Page.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/main.ts
var main_default = { "base": "min-h-[calc(100vh-var(--ui-header-height))]" };
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Main.vue
var _sfc_main = {
	__name: "Main",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false,
			default: "main"
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
		const props = useComponentProps("main", __props);
		const appConfig = useAppConfig();
		const ui = computed(() => tv({
			extend: main_default,
			...appConfig.ui?.main || {}
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: unref(props).as,
				class: ui.value({ class: [unref(props).ui?.base, unref(props).class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
					else return [renderSlot(_ctx.$slots, "default")];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Main.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main$1 as n, _sfc_main$2 as r, _sfc_main as t };
