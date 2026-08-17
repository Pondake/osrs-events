import { a as _sfc_main$5, i as useComponentIcons, l as useComponentProps, o as _sfc_main$6, r as useFieldGroup, s as tv, t as _sfc_main$7, u as Primitive, y as useAppConfig } from "./Button-BsqkFqP1.js";
import { n as useLocale } from "./useLocale-B1UhdR4w.js";
import { n as _sfc_main$9, r as _sfc_main$10, t as _sfc_main$8 } from "./Main-BTQptCM4.js";
import { Head } from "@inertiajs/vue3";
import { Fragment, computed, createBlock, createCommentVNode, createTextVNode, createVNode, mergeProps, openBlock, renderList, renderSlot, toDisplayString, unref, useSSRContext, useSlots, withCtx } from "vue";
import { ssrInterpolate, ssrRenderClass, ssrRenderComponent, ssrRenderList, ssrRenderSlot } from "vue/server-renderer";
//#region virtual:nuxt-ui-templates/ui/alert.ts
var alert_default = {
	"slots": {
		"root": "relative overflow-hidden w-full rounded-lg p-4 flex gap-2.5",
		"wrapper": "min-w-0 flex-1 flex flex-col",
		"title": "text-sm font-medium",
		"description": "text-sm opacity-90",
		"icon": "shrink-0 size-5",
		"avatar": "shrink-0",
		"avatarSize": "2xl",
		"actions": "flex flex-wrap gap-1.5 shrink-0",
		"close": "p-0"
	},
	"variants": {
		"color": {
			"primary": "",
			"secondary": "",
			"success": "",
			"info": "",
			"warning": "",
			"error": "",
			"neutral": ""
		},
		"variant": {
			"solid": "",
			"outline": "",
			"soft": "",
			"subtle": ""
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
	"compoundVariants": [
		{
			"color": "primary",
			"variant": "solid",
			"class": { "root": "bg-primary text-inverted" }
		},
		{
			"color": "secondary",
			"variant": "solid",
			"class": { "root": "bg-secondary text-inverted" }
		},
		{
			"color": "success",
			"variant": "solid",
			"class": { "root": "bg-success text-inverted" }
		},
		{
			"color": "info",
			"variant": "solid",
			"class": { "root": "bg-info text-inverted" }
		},
		{
			"color": "warning",
			"variant": "solid",
			"class": { "root": "bg-warning text-inverted" }
		},
		{
			"color": "error",
			"variant": "solid",
			"class": { "root": "bg-error text-inverted" }
		},
		{
			"color": "primary",
			"variant": "outline",
			"class": { "root": "text-primary ring ring-inset ring-primary/25" }
		},
		{
			"color": "secondary",
			"variant": "outline",
			"class": { "root": "text-secondary ring ring-inset ring-secondary/25" }
		},
		{
			"color": "success",
			"variant": "outline",
			"class": { "root": "text-success ring ring-inset ring-success/25" }
		},
		{
			"color": "info",
			"variant": "outline",
			"class": { "root": "text-info ring ring-inset ring-info/25" }
		},
		{
			"color": "warning",
			"variant": "outline",
			"class": { "root": "text-warning ring ring-inset ring-warning/25" }
		},
		{
			"color": "error",
			"variant": "outline",
			"class": { "root": "text-error ring ring-inset ring-error/25" }
		},
		{
			"color": "primary",
			"variant": "soft",
			"class": { "root": "bg-primary/10 text-primary" }
		},
		{
			"color": "secondary",
			"variant": "soft",
			"class": { "root": "bg-secondary/10 text-secondary" }
		},
		{
			"color": "success",
			"variant": "soft",
			"class": { "root": "bg-success/10 text-success" }
		},
		{
			"color": "info",
			"variant": "soft",
			"class": { "root": "bg-info/10 text-info" }
		},
		{
			"color": "warning",
			"variant": "soft",
			"class": { "root": "bg-warning/10 text-warning" }
		},
		{
			"color": "error",
			"variant": "soft",
			"class": { "root": "bg-error/10 text-error" }
		},
		{
			"color": "primary",
			"variant": "subtle",
			"class": { "root": "bg-primary/10 text-primary ring ring-inset ring-primary/25" }
		},
		{
			"color": "secondary",
			"variant": "subtle",
			"class": { "root": "bg-secondary/10 text-secondary ring ring-inset ring-secondary/25" }
		},
		{
			"color": "success",
			"variant": "subtle",
			"class": { "root": "bg-success/10 text-success ring ring-inset ring-success/25" }
		},
		{
			"color": "info",
			"variant": "subtle",
			"class": { "root": "bg-info/10 text-info ring ring-inset ring-info/25" }
		},
		{
			"color": "warning",
			"variant": "subtle",
			"class": { "root": "bg-warning/10 text-warning ring ring-inset ring-warning/25" }
		},
		{
			"color": "error",
			"variant": "subtle",
			"class": { "root": "bg-error/10 text-error ring ring-inset ring-error/25" }
		},
		{
			"color": "neutral",
			"variant": "solid",
			"class": { "root": "text-inverted bg-inverted" }
		},
		{
			"color": "neutral",
			"variant": "outline",
			"class": { "root": "text-highlighted bg-default ring ring-inset ring-default" }
		},
		{
			"color": "neutral",
			"variant": "soft",
			"class": { "root": "text-highlighted bg-elevated/50" }
		},
		{
			"color": "neutral",
			"variant": "subtle",
			"class": { "root": "text-highlighted bg-elevated/50 ring ring-inset ring-accented" }
		}
	],
	"defaultVariants": {
		"color": "primary",
		"variant": "solid"
	}
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Alert.vue
var _sfc_main$4 = {
	__name: "Alert",
	__ssrInlineRender: true,
	props: {
		as: {
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
		variant: {
			type: null,
			required: false
		},
		orientation: {
			type: null,
			required: false,
			default: "vertical"
		},
		actions: {
			type: Array,
			required: false
		},
		close: {
			type: [Boolean, Object],
			required: false
		},
		closeIcon: {
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
	emits: ["update:open"],
	setup(__props, { emit: __emit }) {
		const _props = __props;
		const emits = __emit;
		const slots = useSlots();
		const props = useComponentProps("alert", _props);
		const { t } = useLocale();
		const appConfig = useAppConfig();
		const ui = computed(() => tv({
			extend: alert_default,
			...appConfig.ui?.alert || {}
		})({
			color: props.color,
			variant: props.variant,
			orientation: props.orientation,
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
						ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
							if (unref(props).avatar) _push(ssrRenderComponent(_sfc_main$5, mergeProps({ size: unref(props).ui?.avatarSize || ui.value.avatarSize() }, unref(props).avatar, {
								"data-slot": "avatar",
								class: ui.value.avatar({ class: unref(props).ui?.avatar })
							}), null, _parent, _scopeId));
							else if (unref(props).icon) _push(ssrRenderComponent(_sfc_main$6, {
								name: unref(props).icon,
								"data-slot": "icon",
								class: ui.value.icon({ class: unref(props).ui?.icon })
							}, null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						_push(`<div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(props).ui?.wrapper }))}"${_scopeId}>`);
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
						if (unref(props).orientation === "vertical" && (unref(props).actions?.length || !!slots.actions)) {
							_push(`<div data-slot="actions" class="${ssrRenderClass(ui.value.actions({ class: unref(props).ui?.actions }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "actions", {}, () => {
								_push(`<!--[-->`);
								ssrRenderList(unref(props).actions, (action, index) => {
									_push(ssrRenderComponent(_sfc_main$7, mergeProps({
										key: index,
										size: "xs"
									}, { ref_for: true }, action), null, _parent, _scopeId));
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
									_push(ssrRenderComponent(_sfc_main$7, mergeProps({
										key: index,
										size: "xs"
									}, { ref_for: true }, action), null, _parent, _scopeId));
								});
								_push(`<!--]-->`);
							}, _push, _parent, _scopeId);
							else _push(`<!---->`);
							ssrRenderSlot(_ctx.$slots, "close", { ui: ui.value }, () => {
								if (unref(props).close) _push(ssrRenderComponent(_sfc_main$7, mergeProps({
									icon: unref(props).closeIcon || unref(appConfig).ui.icons.close,
									color: "neutral",
									variant: "link",
									"aria-label": unref(t)("alert.close")
								}, typeof unref(props).close === "object" ? unref(props).close : {}, {
									"data-slot": "close",
									class: ui.value.close({ class: unref(props).ui?.close }),
									onClick: ($event) => emits("update:open", false)
								}), null, _parent, _scopeId));
								else _push(`<!---->`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
					} else return [
						renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(props).avatar ? (openBlock(), createBlock(_sfc_main$5, mergeProps({
							key: 0,
							size: unref(props).ui?.avatarSize || ui.value.avatarSize()
						}, unref(props).avatar, {
							"data-slot": "avatar",
							class: ui.value.avatar({ class: unref(props).ui?.avatar })
						}), null, 16, ["size", "class"])) : unref(props).icon ? (openBlock(), createBlock(_sfc_main$6, {
							key: 1,
							name: unref(props).icon,
							"data-slot": "icon",
							class: ui.value.icon({ class: unref(props).ui?.icon })
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)]),
						createVNode("div", {
							"data-slot": "wrapper",
							class: ui.value.wrapper({ class: unref(props).ui?.wrapper })
						}, [
							unref(props).title || !!slots.title ? (openBlock(), createBlock("div", {
								key: 0,
								"data-slot": "title",
								class: ui.value.title({ class: unref(props).ui?.title })
							}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(unref(props).title), 1)])], 2)) : createCommentVNode("", true),
							unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
								key: 1,
								"data-slot": "description",
								class: ui.value.description({ class: unref(props).ui?.description })
							}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(unref(props).description), 1)])], 2)) : createCommentVNode("", true),
							unref(props).orientation === "vertical" && (unref(props).actions?.length || !!slots.actions) ? (openBlock(), createBlock("div", {
								key: 2,
								"data-slot": "actions",
								class: ui.value.actions({ class: unref(props).ui?.actions })
							}, [renderSlot(_ctx.$slots, "actions", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).actions, (action, index) => {
								return openBlock(), createBlock(_sfc_main$7, mergeProps({
									key: index,
									size: "xs"
								}, { ref_for: true }, action), null, 16);
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
							return openBlock(), createBlock(_sfc_main$7, mergeProps({
								key: index,
								size: "xs"
							}, { ref_for: true }, action), null, 16);
						}), 128))], void 0, 0) : createCommentVNode("", true), renderSlot(_ctx.$slots, "close", { ui: ui.value }, () => [unref(props).close ? (openBlock(), createBlock(_sfc_main$7, mergeProps({
							key: 0,
							icon: unref(props).closeIcon || unref(appConfig).ui.icons.close,
							color: "neutral",
							variant: "link",
							"aria-label": unref(t)("alert.close")
						}, typeof unref(props).close === "object" ? unref(props).close : {}, {
							"data-slot": "close",
							class: ui.value.close({ class: unref(props).ui?.close }),
							onClick: ($event) => emits("update:open", false)
						}), null, 16, [
							"icon",
							"aria-label",
							"class",
							"onClick"
						])) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true)
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Alert.vue");
	return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/card.ts
var card_default = {
	"slots": {
		"root": "rounded-lg overflow-hidden",
		"header": "p-4 sm:px-6",
		"title": "text-highlighted font-semibold",
		"description": "mt-1 text-muted text-sm",
		"body": "p-4 sm:p-6",
		"footer": "p-4 sm:px-6"
	},
	"variants": { "variant": {
		"solid": {
			"root": "bg-inverted text-inverted",
			"title": "text-inverted",
			"description": "text-dimmed"
		},
		"outline": { "root": "bg-default ring ring-default divide-y divide-default" },
		"soft": { "root": "bg-elevated/50 divide-y divide-default" },
		"subtle": { "root": "bg-elevated/50 ring ring-default divide-y divide-default" }
	} },
	"defaultVariants": { "variant": "outline" }
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Card.vue
var _sfc_main$3 = {
	__name: "Card",
	__ssrInlineRender: true,
	props: {
		as: {
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
		variant: {
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
		const props = useComponentProps("card", _props);
		const appConfig = useAppConfig();
		const ui = computed(() => tv({
			extend: card_default,
			...appConfig.ui?.card || {}
		})({ variant: props.variant }));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: unref(props).as,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(props).ui?.root, unref(props).class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (!!slots.header || unref(props).title || !!slots.title || unref(props).description || !!slots.description) {
							_push(`<div data-slot="header" class="${ssrRenderClass(ui.value.header({ class: unref(props).ui?.header }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "header", {}, () => {
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
						} else _push(`<!---->`);
						if (!!slots.default) {
							_push(`<div data-slot="body" class="${ssrRenderClass(ui.value.body({ class: unref(props).ui?.body }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						if (!!slots.footer) {
							_push(`<div data-slot="footer" class="${ssrRenderClass(ui.value.footer({ class: unref(props).ui?.footer }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "footer", {}, null, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
					} else return [
						!!slots.header || unref(props).title || !!slots.title || unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "header",
							class: ui.value.header({ class: unref(props).ui?.header })
						}, [renderSlot(_ctx.$slots, "header", {}, () => [unref(props).title || !!slots.title ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "title",
							class: ui.value.title({ class: unref(props).ui?.title })
						}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(unref(props).title), 1)])], 2)) : createCommentVNode("", true), unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
							key: 1,
							"data-slot": "description",
							class: ui.value.description({ class: unref(props).ui?.description })
						}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(unref(props).description), 1)])], 2)) : createCommentVNode("", true)])], 2)) : createCommentVNode("", true),
						!!slots.default ? (openBlock(), createBlock("div", {
							key: 1,
							"data-slot": "body",
							class: ui.value.body({ class: unref(props).ui?.body })
						}, [renderSlot(_ctx.$slots, "default")], 2)) : createCommentVNode("", true),
						!!slots.footer ? (openBlock(), createBlock("div", {
							key: 2,
							"data-slot": "footer",
							class: ui.value.footer({ class: unref(props).ui?.footer })
						}, [renderSlot(_ctx.$slots, "footer")], 2)) : createCommentVNode("", true)
					];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Card.vue");
	return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/badge.ts
var badge_default = {
	"slots": {
		"base": "font-medium inline-flex items-center",
		"label": "truncate",
		"leadingIcon": "shrink-0",
		"leadingAvatar": "shrink-0",
		"leadingAvatarSize": "",
		"trailingIcon": "shrink-0"
	},
	"variants": {
		"fieldGroup": {
			"horizontal": "not-only:first:rounded-e-none not-only:last:rounded-s-none not-last:not-first:rounded-none focus-visible:z-[1]",
			"vertical": "not-only:first:rounded-b-none not-only:last:rounded-t-none not-last:not-first:rounded-none focus-visible:z-[1]"
		},
		"color": {
			"primary": "",
			"secondary": "",
			"success": "",
			"info": "",
			"warning": "",
			"error": "",
			"neutral": ""
		},
		"variant": {
			"solid": "",
			"outline": "",
			"soft": "",
			"subtle": ""
		},
		"size": {
			"xs": {
				"base": "text-[8px]/3 px-1 py-0.5 gap-1 rounded-sm",
				"leadingIcon": "size-3",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-3"
			},
			"sm": {
				"base": "text-[10px]/3 px-1.5 py-1 gap-1 rounded-sm",
				"leadingIcon": "size-3",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-3"
			},
			"md": {
				"base": "text-xs px-2 py-1 gap-1 rounded-md",
				"leadingIcon": "size-4",
				"leadingAvatarSize": "3xs",
				"trailingIcon": "size-4"
			},
			"lg": {
				"base": "text-sm px-2 py-1 gap-1.5 rounded-md",
				"leadingIcon": "size-5",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-5"
			},
			"xl": {
				"base": "text-base px-2.5 py-1 gap-1.5 rounded-md",
				"leadingIcon": "size-6",
				"leadingAvatarSize": "2xs",
				"trailingIcon": "size-6"
			}
		},
		"square": { "true": "" }
	},
	"compoundVariants": [
		{
			"color": "primary",
			"variant": "solid",
			"class": "bg-primary text-inverted"
		},
		{
			"color": "secondary",
			"variant": "solid",
			"class": "bg-secondary text-inverted"
		},
		{
			"color": "success",
			"variant": "solid",
			"class": "bg-success text-inverted"
		},
		{
			"color": "info",
			"variant": "solid",
			"class": "bg-info text-inverted"
		},
		{
			"color": "warning",
			"variant": "solid",
			"class": "bg-warning text-inverted"
		},
		{
			"color": "error",
			"variant": "solid",
			"class": "bg-error text-inverted"
		},
		{
			"color": "primary",
			"variant": "outline",
			"class": "text-primary ring ring-inset ring-primary/50"
		},
		{
			"color": "secondary",
			"variant": "outline",
			"class": "text-secondary ring ring-inset ring-secondary/50"
		},
		{
			"color": "success",
			"variant": "outline",
			"class": "text-success ring ring-inset ring-success/50"
		},
		{
			"color": "info",
			"variant": "outline",
			"class": "text-info ring ring-inset ring-info/50"
		},
		{
			"color": "warning",
			"variant": "outline",
			"class": "text-warning ring ring-inset ring-warning/50"
		},
		{
			"color": "error",
			"variant": "outline",
			"class": "text-error ring ring-inset ring-error/50"
		},
		{
			"color": "primary",
			"variant": "soft",
			"class": "bg-primary/10 text-primary"
		},
		{
			"color": "secondary",
			"variant": "soft",
			"class": "bg-secondary/10 text-secondary"
		},
		{
			"color": "success",
			"variant": "soft",
			"class": "bg-success/10 text-success"
		},
		{
			"color": "info",
			"variant": "soft",
			"class": "bg-info/10 text-info"
		},
		{
			"color": "warning",
			"variant": "soft",
			"class": "bg-warning/10 text-warning"
		},
		{
			"color": "error",
			"variant": "soft",
			"class": "bg-error/10 text-error"
		},
		{
			"color": "primary",
			"variant": "subtle",
			"class": "bg-primary/10 text-primary ring ring-inset ring-primary/25"
		},
		{
			"color": "secondary",
			"variant": "subtle",
			"class": "bg-secondary/10 text-secondary ring ring-inset ring-secondary/25"
		},
		{
			"color": "success",
			"variant": "subtle",
			"class": "bg-success/10 text-success ring ring-inset ring-success/25"
		},
		{
			"color": "info",
			"variant": "subtle",
			"class": "bg-info/10 text-info ring ring-inset ring-info/25"
		},
		{
			"color": "warning",
			"variant": "subtle",
			"class": "bg-warning/10 text-warning ring ring-inset ring-warning/25"
		},
		{
			"color": "error",
			"variant": "subtle",
			"class": "bg-error/10 text-error ring ring-inset ring-error/25"
		},
		{
			"color": "neutral",
			"variant": "solid",
			"class": "text-inverted bg-inverted"
		},
		{
			"color": "neutral",
			"variant": "outline",
			"class": "ring ring-inset ring-accented text-default bg-default"
		},
		{
			"color": "neutral",
			"variant": "soft",
			"class": "text-default bg-elevated"
		},
		{
			"color": "neutral",
			"variant": "subtle",
			"class": "ring ring-inset ring-accented text-default bg-elevated"
		},
		{
			"size": "xs",
			"square": true,
			"class": "p-0.5"
		},
		{
			"size": "sm",
			"square": true,
			"class": "p-1"
		},
		{
			"size": "md",
			"square": true,
			"class": "p-1"
		},
		{
			"size": "lg",
			"square": true,
			"class": "p-1"
		},
		{
			"size": "xl",
			"square": true,
			"class": "p-1"
		}
	],
	"defaultVariants": {
		"color": "primary",
		"variant": "solid",
		"size": "md"
	}
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Badge.vue
var _sfc_main$2 = {
	__name: "Badge",
	__ssrInlineRender: true,
	props: {
		as: {
			type: null,
			required: false,
			default: "span"
		},
		label: {
			type: [String, Number],
			required: false
		},
		color: {
			type: null,
			required: false
		},
		variant: {
			type: null,
			required: false
		},
		size: {
			type: null,
			required: false
		},
		square: {
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
		},
		icon: {
			type: null,
			required: false
		},
		avatar: {
			type: Object,
			required: false
		},
		leading: {
			type: Boolean,
			required: false
		},
		leadingIcon: {
			type: null,
			required: false
		},
		trailing: {
			type: Boolean,
			required: false
		},
		trailingIcon: {
			type: null,
			required: false
		}
	},
	setup(__props) {
		const _props = __props;
		const slots = useSlots();
		const props = useComponentProps("badge", _props);
		const appConfig = useAppConfig();
		const { orientation, size: fieldGroupSize } = useFieldGroup(_props);
		const { isLeading, isTrailing, leadingIconName, trailingIconName } = useComponentIcons(props);
		const ui = computed(() => tv({
			extend: badge_default,
			...appConfig.ui?.badge || {}
		})({
			color: props.color,
			variant: props.variant,
			size: fieldGroupSize.value ?? props.size,
			square: props.square || !slots.default && !props.label,
			fieldGroup: orientation.value
		}));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: unref(props).as,
				"data-slot": "base",
				class: ui.value.base({ class: [unref(props).ui?.base, unref(props).class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						ssrRenderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => {
							if (unref(isLeading) && unref(leadingIconName)) _push(ssrRenderComponent(_sfc_main$6, {
								name: unref(leadingIconName),
								"data-slot": "leadingIcon",
								class: ui.value.leadingIcon({ class: unref(props).ui?.leadingIcon })
							}, null, _parent, _scopeId));
							else if (!!unref(props).avatar) _push(ssrRenderComponent(_sfc_main$5, mergeProps({ size: unref(props).ui?.leadingAvatarSize || ui.value.leadingAvatarSize() }, unref(props).avatar, {
								"data-slot": "leadingAvatar",
								class: ui.value.leadingAvatar({ class: unref(props).ui?.leadingAvatar })
							}), null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						ssrRenderSlot(_ctx.$slots, "default", { ui: ui.value }, () => {
							if (unref(props).label !== void 0 && unref(props).label !== null) _push(`<span data-slot="label" class="${ssrRenderClass(ui.value.label({ class: unref(props).ui?.label }))}"${_scopeId}>${ssrInterpolate(unref(props).label)}</span>`);
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
						ssrRenderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => {
							if (unref(isTrailing) && unref(trailingIconName)) _push(ssrRenderComponent(_sfc_main$6, {
								name: unref(trailingIconName),
								"data-slot": "trailingIcon",
								class: ui.value.trailingIcon({ class: unref(props).ui?.trailingIcon })
							}, null, _parent, _scopeId));
							else _push(`<!---->`);
						}, _push, _parent, _scopeId);
					} else return [
						renderSlot(_ctx.$slots, "leading", { ui: ui.value }, () => [unref(isLeading) && unref(leadingIconName) ? (openBlock(), createBlock(_sfc_main$6, {
							key: 0,
							name: unref(leadingIconName),
							"data-slot": "leadingIcon",
							class: ui.value.leadingIcon({ class: unref(props).ui?.leadingIcon })
						}, null, 8, ["name", "class"])) : !!unref(props).avatar ? (openBlock(), createBlock(_sfc_main$5, mergeProps({
							key: 1,
							size: unref(props).ui?.leadingAvatarSize || ui.value.leadingAvatarSize()
						}, unref(props).avatar, {
							"data-slot": "leadingAvatar",
							class: ui.value.leadingAvatar({ class: unref(props).ui?.leadingAvatar })
						}), null, 16, ["size", "class"])) : createCommentVNode("", true)]),
						renderSlot(_ctx.$slots, "default", { ui: ui.value }, () => [unref(props).label !== void 0 && unref(props).label !== null ? (openBlock(), createBlock("span", {
							key: 0,
							"data-slot": "label",
							class: ui.value.label({ class: unref(props).ui?.label })
						}, toDisplayString(unref(props).label), 3)) : createCommentVNode("", true)]),
						renderSlot(_ctx.$slots, "trailing", { ui: ui.value }, () => [unref(isTrailing) && unref(trailingIconName) ? (openBlock(), createBlock(_sfc_main$6, {
							key: 0,
							name: unref(trailingIconName),
							"data-slot": "trailingIcon",
							class: ui.value.trailingIcon({ class: unref(props).ui?.trailingIcon })
						}, null, 8, ["name", "class"])) : createCommentVNode("", true)])
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/Badge.vue");
	return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
//#endregion
//#region virtual:nuxt-ui-templates/ui/page-header.ts
var page_header_default = {
	"slots": {
		"root": "relative border-b border-default py-8",
		"container": "",
		"wrapper": "flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4",
		"headline": "mb-2.5 text-sm font-semibold text-primary flex items-center gap-1.5",
		"title": "text-3xl sm:text-4xl text-pretty font-bold text-highlighted",
		"description": "text-lg text-pretty text-muted",
		"links": "flex flex-wrap items-center gap-1.5"
	},
	"variants": { "title": { "true": { "description": "mt-4" } } }
};
//#endregion
//#region node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/PageHeader.vue
var _sfc_main$1 = {
	__name: "PageHeader",
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
		const props = useComponentProps("pageHeader", _props);
		const appConfig = useAppConfig();
		const ui = computed(() => tv({
			extend: page_header_default,
			...appConfig.ui?.pageHeader || {}
		})({ title: !!props.title || !!slots.title }));
		return (_ctx, _push, _parent, _attrs) => {
			_push(ssrRenderComponent(unref(Primitive), mergeProps({
				as: unref(props).as,
				"data-slot": "root",
				class: ui.value.root({ class: [unref(props).ui?.root, unref(props).class] })
			}, _attrs), {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) {
						if (unref(props).headline || !!slots.headline) {
							_push(`<div data-slot="headline" class="${ssrRenderClass(ui.value.headline({ class: unref(props).ui?.headline }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "headline", {}, () => {
								_push(`${ssrInterpolate(unref(props).headline)}`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						_push(`<div data-slot="container" class="${ssrRenderClass(ui.value.container({ class: unref(props).ui?.container }))}"${_scopeId}><div data-slot="wrapper" class="${ssrRenderClass(ui.value.wrapper({ class: unref(props).ui?.wrapper }))}"${_scopeId}>`);
						if (unref(props).title || !!slots.title) {
							_push(`<h1 data-slot="title" class="${ssrRenderClass(ui.value.title({ class: unref(props).ui?.title }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "title", {}, () => {
								_push(`${ssrInterpolate(unref(props).title)}`);
							}, _push, _parent, _scopeId);
							_push(`</h1>`);
						} else _push(`<!---->`);
						if (unref(props).links?.length || !!slots.links) {
							_push(`<div data-slot="links" class="${ssrRenderClass(ui.value.links({ class: unref(props).ui?.links }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "links", {}, () => {
								_push(`<!--[-->`);
								ssrRenderList(unref(props).links, (link, index) => {
									_push(ssrRenderComponent(_sfc_main$7, mergeProps({
										key: index,
										color: "neutral",
										variant: "outline"
									}, { ref_for: true }, link), null, _parent, _scopeId));
								});
								_push(`<!--]-->`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						_push(`</div>`);
						if (unref(props).description || !!slots.description) {
							_push(`<div data-slot="description" class="${ssrRenderClass(ui.value.description({ class: unref(props).ui?.description }))}"${_scopeId}>`);
							ssrRenderSlot(_ctx.$slots, "description", {}, () => {
								_push(`${ssrInterpolate(unref(props).description)}`);
							}, _push, _parent, _scopeId);
							_push(`</div>`);
						} else _push(`<!---->`);
						ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent, _scopeId);
						_push(`</div>`);
					} else return [unref(props).headline || !!slots.headline ? (openBlock(), createBlock("div", {
						key: 0,
						"data-slot": "headline",
						class: ui.value.headline({ class: unref(props).ui?.headline })
					}, [renderSlot(_ctx.$slots, "headline", {}, () => [createTextVNode(toDisplayString(unref(props).headline), 1)])], 2)) : createCommentVNode("", true), createVNode("div", {
						"data-slot": "container",
						class: ui.value.container({ class: unref(props).ui?.container })
					}, [
						createVNode("div", {
							"data-slot": "wrapper",
							class: ui.value.wrapper({ class: unref(props).ui?.wrapper })
						}, [unref(props).title || !!slots.title ? (openBlock(), createBlock("h1", {
							key: 0,
							"data-slot": "title",
							class: ui.value.title({ class: unref(props).ui?.title })
						}, [renderSlot(_ctx.$slots, "title", {}, () => [createTextVNode(toDisplayString(unref(props).title), 1)])], 2)) : createCommentVNode("", true), unref(props).links?.length || !!slots.links ? (openBlock(), createBlock("div", {
							key: 1,
							"data-slot": "links",
							class: ui.value.links({ class: unref(props).ui?.links })
						}, [renderSlot(_ctx.$slots, "links", {}, () => [(openBlock(true), createBlock(Fragment, null, renderList(unref(props).links, (link, index) => {
							return openBlock(), createBlock(_sfc_main$7, mergeProps({
								key: index,
								color: "neutral",
								variant: "outline"
							}, { ref_for: true }, link), null, 16);
						}), 128))])], 2)) : createCommentVNode("", true)], 2),
						unref(props).description || !!slots.description ? (openBlock(), createBlock("div", {
							key: 0,
							"data-slot": "description",
							class: ui.value.description({ class: unref(props).ui?.description })
						}, [renderSlot(_ctx.$slots, "description", {}, () => [createTextVNode(toDisplayString(unref(props).description), 1)])], 2)) : createCommentVNode("", true),
						renderSlot(_ctx.$slots, "default")
					], 2)];
				}),
				_: 3
			}, _parent));
		};
	}
};
var _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
	const ssrContext = useSSRContext();
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/.pnpm/@nuxt+ui@4.10.0_7aaf5b96743bc488032fba6aed608feb/node_modules/@nuxt/ui/dist/runtime/components/PageHeader.vue");
	return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
//#endregion
//#region resources/js/Pages/BoardShow.vue
var _sfc_main = {
	__name: "BoardShow",
	__ssrInlineRender: true,
	props: {
		board: {
			type: Object,
			required: true
		},
		tiles: {
			type: Array,
			required: true
		},
		playerBoard: {
			type: Object,
			default: null
		}
	},
	setup(__props) {
		const props = __props;
		const GRID_CLASSES = {
			SIZE_5X5: "grid-cols-5",
			SIZE_7X7: "grid-cols-7",
			SIZE_9X9: "grid-cols-9"
		};
		const gridClass = computed(() => GRID_CLASSES[props.board.size] ?? GRID_CLASSES.SIZE_7X7);
		function tileClasses(tile) {
			if (props.playerBoard?.completedTileIds.includes(tile.id)) return "bg-primary/20 border-primary text-primary";
			if (tile.type === "SNAKE") return "bg-error/10 border-error/30";
			if (tile.type === "LADDER") return "bg-success/10 border-success/30";
			return "bg-elevated border-default";
		}
		return (_ctx, _push, _parent, _attrs) => {
			const _component_u_main = _sfc_main$8;
			const _component_u_page = _sfc_main$9;
			const _component_u_container = _sfc_main$10;
			const _component_u_page_header = _sfc_main$1;
			const _component_u_badge = _sfc_main$2;
			const _component_u_card = _sfc_main$3;
			const _component_u_alert = _sfc_main$4;
			_push(`<!--[-->`);
			_push(ssrRenderComponent(unref(Head), { title: __props.board.title }, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(`<meta name="robots" content="noindex, nofollow"${_scopeId}>`);
					else return [createVNode("meta", {
						name: "robots",
						content: "noindex, nofollow"
					})];
				}),
				_: 1
			}, _parent));
			_push(ssrRenderComponent(_component_u_main, null, {
				default: withCtx((_, _push, _parent, _scopeId) => {
					if (_push) _push(ssrRenderComponent(_component_u_page, null, {
						default: withCtx((_, _push, _parent, _scopeId) => {
							if (_push) _push(ssrRenderComponent(_component_u_container, { class: "py-12" }, {
								default: withCtx((_, _push, _parent, _scopeId) => {
									if (_push) {
										_push(ssrRenderComponent(_component_u_page_header, {
											title: __props.board.title,
											description: __props.board.description ?? ""
										}, {
											headline: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(ssrRenderComponent(_component_u_badge, {
													label: __props.board.mode,
													color: "neutral",
													variant: "subtle"
												}, null, _parent, _scopeId));
												else return [createVNode(_component_u_badge, {
													label: __props.board.mode,
													color: "neutral",
													variant: "subtle"
												}, null, 8, ["label"])];
											}),
											_: 1
										}, _parent, _scopeId));
										_push(`<div class="mt-8 flex flex-col lg:flex-row gap-8 items-start"${_scopeId}><div class="flex-1 w-full min-w-0 overflow-x-auto"${_scopeId}><div class="${ssrRenderClass([gridClass.value, "grid gap-1.5"])}"${_scopeId}><!--[-->`);
										ssrRenderList(__props.tiles, (tile) => {
											_push(`<div class="${ssrRenderClass([tileClasses(tile), "aspect-square rounded-md border flex items-center justify-center text-xs font-semibold"])}"${_scopeId}>${ssrInterpolate(tile.position + 1)}</div>`);
										});
										_push(`<!--]--></div></div><div class="w-full lg:w-64 shrink-0"${_scopeId}>`);
										if (__props.playerBoard) _push(ssrRenderComponent(_component_u_card, null, {
											header: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(`<span class="font-semibold"${_scopeId}>Your progress</span>`);
												else return [createVNode("span", { class: "font-semibold" }, "Your progress")];
											}),
											default: withCtx((_, _push, _parent, _scopeId) => {
												if (_push) _push(`<dl class="text-sm space-y-2"${_scopeId}><div class="flex justify-between"${_scopeId}><dt class="text-muted"${_scopeId}>Current tile</dt><dd${_scopeId}>${ssrInterpolate(__props.playerBoard.current_position + 1)} / ${ssrInterpolate(__props.tiles.length)}</dd></div><div class="flex justify-between"${_scopeId}><dt class="text-muted"${_scopeId}>Tiles completed</dt><dd${_scopeId}>${ssrInterpolate(__props.playerBoard.completedTileIds.length)}</dd></div></dl>`);
												else return [createVNode("dl", { class: "text-sm space-y-2" }, [createVNode("div", { class: "flex justify-between" }, [createVNode("dt", { class: "text-muted" }, "Current tile"), createVNode("dd", null, toDisplayString(__props.playerBoard.current_position + 1) + " / " + toDisplayString(__props.tiles.length), 1)]), createVNode("div", { class: "flex justify-between" }, [createVNode("dt", { class: "text-muted" }, "Tiles completed"), createVNode("dd", null, toDisplayString(__props.playerBoard.completedTileIds.length), 1)])])];
											}),
											_: 1
										}, _parent, _scopeId));
										else _push(ssrRenderComponent(_component_u_alert, {
											title: "Not joined yet",
											description: "You haven't joined this board — this session has an authenticated user but no PlayerBoard row for it.",
											color: "neutral",
											variant: "soft"
										}, null, _parent, _scopeId));
										_push(`</div></div>`);
									} else return [createVNode(_component_u_page_header, {
										title: __props.board.title,
										description: __props.board.description ?? ""
									}, {
										headline: withCtx(() => [createVNode(_component_u_badge, {
											label: __props.board.mode,
											color: "neutral",
											variant: "subtle"
										}, null, 8, ["label"])]),
										_: 1
									}, 8, ["title", "description"]), createVNode("div", { class: "mt-8 flex flex-col lg:flex-row gap-8 items-start" }, [createVNode("div", { class: "flex-1 w-full min-w-0 overflow-x-auto" }, [createVNode("div", { class: [gridClass.value, "grid gap-1.5"] }, [(openBlock(true), createBlock(Fragment, null, renderList(__props.tiles, (tile) => {
										return openBlock(), createBlock("div", {
											key: tile.id,
											class: ["aspect-square rounded-md border flex items-center justify-center text-xs font-semibold", tileClasses(tile)]
										}, toDisplayString(tile.position + 1), 3);
									}), 128))], 2)]), createVNode("div", { class: "w-full lg:w-64 shrink-0" }, [__props.playerBoard ? (openBlock(), createBlock(_component_u_card, { key: 0 }, {
										header: withCtx(() => [createVNode("span", { class: "font-semibold" }, "Your progress")]),
										default: withCtx(() => [createVNode("dl", { class: "text-sm space-y-2" }, [createVNode("div", { class: "flex justify-between" }, [createVNode("dt", { class: "text-muted" }, "Current tile"), createVNode("dd", null, toDisplayString(__props.playerBoard.current_position + 1) + " / " + toDisplayString(__props.tiles.length), 1)]), createVNode("div", { class: "flex justify-between" }, [createVNode("dt", { class: "text-muted" }, "Tiles completed"), createVNode("dd", null, toDisplayString(__props.playerBoard.completedTileIds.length), 1)])])]),
										_: 1
									})) : (openBlock(), createBlock(_component_u_alert, {
										key: 1,
										title: "Not joined yet",
										description: "You haven't joined this board — this session has an authenticated user but no PlayerBoard row for it.",
										color: "neutral",
										variant: "soft"
									}))])])];
								}),
								_: 1
							}, _parent, _scopeId));
							else return [createVNode(_component_u_container, { class: "py-12" }, {
								default: withCtx(() => [createVNode(_component_u_page_header, {
									title: __props.board.title,
									description: __props.board.description ?? ""
								}, {
									headline: withCtx(() => [createVNode(_component_u_badge, {
										label: __props.board.mode,
										color: "neutral",
										variant: "subtle"
									}, null, 8, ["label"])]),
									_: 1
								}, 8, ["title", "description"]), createVNode("div", { class: "mt-8 flex flex-col lg:flex-row gap-8 items-start" }, [createVNode("div", { class: "flex-1 w-full min-w-0 overflow-x-auto" }, [createVNode("div", { class: [gridClass.value, "grid gap-1.5"] }, [(openBlock(true), createBlock(Fragment, null, renderList(__props.tiles, (tile) => {
									return openBlock(), createBlock("div", {
										key: tile.id,
										class: ["aspect-square rounded-md border flex items-center justify-center text-xs font-semibold", tileClasses(tile)]
									}, toDisplayString(tile.position + 1), 3);
								}), 128))], 2)]), createVNode("div", { class: "w-full lg:w-64 shrink-0" }, [__props.playerBoard ? (openBlock(), createBlock(_component_u_card, { key: 0 }, {
									header: withCtx(() => [createVNode("span", { class: "font-semibold" }, "Your progress")]),
									default: withCtx(() => [createVNode("dl", { class: "text-sm space-y-2" }, [createVNode("div", { class: "flex justify-between" }, [createVNode("dt", { class: "text-muted" }, "Current tile"), createVNode("dd", null, toDisplayString(__props.playerBoard.current_position + 1) + " / " + toDisplayString(__props.tiles.length), 1)]), createVNode("div", { class: "flex justify-between" }, [createVNode("dt", { class: "text-muted" }, "Tiles completed"), createVNode("dd", null, toDisplayString(__props.playerBoard.completedTileIds.length), 1)])])]),
									_: 1
								})) : (openBlock(), createBlock(_component_u_alert, {
									key: 1,
									title: "Not joined yet",
									description: "You haven't joined this board — this session has an authenticated user but no PlayerBoard row for it.",
									color: "neutral",
									variant: "soft"
								}))])])]),
								_: 1
							})];
						}),
						_: 1
					}, _parent, _scopeId));
					else return [createVNode(_component_u_page, null, {
						default: withCtx(() => [createVNode(_component_u_container, { class: "py-12" }, {
							default: withCtx(() => [createVNode(_component_u_page_header, {
								title: __props.board.title,
								description: __props.board.description ?? ""
							}, {
								headline: withCtx(() => [createVNode(_component_u_badge, {
									label: __props.board.mode,
									color: "neutral",
									variant: "subtle"
								}, null, 8, ["label"])]),
								_: 1
							}, 8, ["title", "description"]), createVNode("div", { class: "mt-8 flex flex-col lg:flex-row gap-8 items-start" }, [createVNode("div", { class: "flex-1 w-full min-w-0 overflow-x-auto" }, [createVNode("div", { class: [gridClass.value, "grid gap-1.5"] }, [(openBlock(true), createBlock(Fragment, null, renderList(__props.tiles, (tile) => {
								return openBlock(), createBlock("div", {
									key: tile.id,
									class: ["aspect-square rounded-md border flex items-center justify-center text-xs font-semibold", tileClasses(tile)]
								}, toDisplayString(tile.position + 1), 3);
							}), 128))], 2)]), createVNode("div", { class: "w-full lg:w-64 shrink-0" }, [__props.playerBoard ? (openBlock(), createBlock(_component_u_card, { key: 0 }, {
								header: withCtx(() => [createVNode("span", { class: "font-semibold" }, "Your progress")]),
								default: withCtx(() => [createVNode("dl", { class: "text-sm space-y-2" }, [createVNode("div", { class: "flex justify-between" }, [createVNode("dt", { class: "text-muted" }, "Current tile"), createVNode("dd", null, toDisplayString(__props.playerBoard.current_position + 1) + " / " + toDisplayString(__props.tiles.length), 1)]), createVNode("div", { class: "flex justify-between" }, [createVNode("dt", { class: "text-muted" }, "Tiles completed"), createVNode("dd", null, toDisplayString(__props.playerBoard.completedTileIds.length), 1)])])]),
								_: 1
							})) : (openBlock(), createBlock(_component_u_alert, {
								key: 1,
								title: "Not joined yet",
								description: "You haven't joined this board — this session has an authenticated user but no PlayerBoard row for it.",
								color: "neutral",
								variant: "soft"
							}))])])]),
							_: 1
						})]),
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
	(ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("resources/js/Pages/BoardShow.vue");
	return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
//#endregion
export { _sfc_main as default };
