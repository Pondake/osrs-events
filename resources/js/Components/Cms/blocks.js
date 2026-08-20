import { safeHref } from '@/Support/richtext';
import CalloutBlock from './Blocks/CalloutBlock.vue';
import CtaBlock from './Blocks/CtaBlock.vue';
import FeaturesBlock from './Blocks/FeaturesBlock.vue';
import HeroBlock from './Blocks/HeroBlock.vue';
import ImageBlock from './Blocks/ImageBlock.vue';
import LinksBlock from './Blocks/LinksBlock.vue';
import ListBlock from './Blocks/ListBlock.vue';
import ProseBlock from './Blocks/ProseBlock.vue';
import SectionBlock from './Blocks/SectionBlock.vue';
import SeparatorBlock from './Blocks/SeparatorBlock.vue';

/**
 * The block vocabulary the page renderer understands.
 *
 * This file is the security boundary, not a convenience layer. Block props
 * will come from the database once the CMS lands, which means they are
 * untrusted input rendered into a public page. Two rules follow:
 *
 *   1. `component` is resolved from THIS map only — never by looking up a
 *      stored type string against globally registered components, which
 *      would let a stored block render anything the app has registered.
 *   2. `sanitize()` builds a NEW props object from the schema. Raw input is
 *      never spread, so a key that isn't listed here cannot reach a
 *      component — including Vue's own (`is`, `ref`, event handlers) and
 *      Nuxt UI's `ui` slot overrides.
 *
 * Adding a block type means adding an entry here plus a component whose own
 * props declaration is the second line of defence.
 */

// --- field coercers --------------------------------------------------------
//
// Each returns a safe value or a null/empty fallback. None of them throw:
// stored content being malformed should cost that one field, not the page.

const text = (value) => (typeof value === 'string' ? value : null);

// Explicitly `!== false` rather than Boolean(): an absent value should
// take the block's own default, and for `rounded` that default is on.
const bool = (fallback) => (value) => (typeof value === 'boolean' ? value : fallback);

const oneOf = (allowed, fallback = null) => (value) => (allowed.includes(value) ? value : fallback);

/**
 * Icon names are passed to Nuxt UI's icon resolver, so they're constrained
 * to the iconify shape rather than accepted as free text.
 */
const icon = (value) => (typeof value === 'string' && /^i-[a-z0-9]+-[a-z0-9-]+$/i.test(value) ? value : null);

/** Reuses the announcement banner's URL rule — http(s) or site-relative. */
const url = (value) => safeHref(value);

export const BLOCK_COLORS = ['primary', 'secondary', 'success', 'info', 'warning', 'error', 'neutral'];

const COLORS = BLOCK_COLORS;

const color = oneOf(COLORS, 'primary');

/**
 * A bounded list of sub-objects. The cap is not tidiness: an editor (or a
 * bad import) putting ten thousand entries in one block would otherwise
 * render ten thousand nodes.
 */
const listOf = (schema, max) => (value) => (Array.isArray(value)
    ? value.slice(0, max).map((item) => sanitize(schema, item))
    : []);

/**
 * Field descriptors for the editor.
 *
 * The editor renders its forms from these rather than having a hand-written
 * form per block type, so adding a block means adding one entry to
 * BLOCK_TYPES — vocabulary, renderer and editor stay one definition instead
 * of three that drift.
 *
 * `type` here is an INPUT kind, not a data type; the schema above still
 * decides what actually survives into a component.
 */
const LINK_FIELDS = [
    { key: 'label', type: 'text', label: 'cms.field_label' },
    { key: 'to', type: 'text', label: 'cms.field_url' },
    { key: 'icon', type: 'icon', label: 'cms.field_icon' },
    { key: 'color', type: 'color', label: 'cms.field_color' },
    { key: 'variant', type: 'select', label: 'cms.field_variant', options: ['solid', 'outline', 'subtle', 'ghost', 'link'] },
];

const LIST_ITEM_FIELDS = [
    { key: 'text', type: 'text', label: 'cms.field_text', hint: 'cms.hint_inline_markdown' },
];

const LIST_ITEM_SCHEMA = { text };

const CARD_FIELDS = [
    { key: 'icon', type: 'icon', label: 'cms.field_icon' },
    { key: 'title', type: 'text', label: 'cms.field_title' },
    { key: 'description', type: 'textarea', label: 'cms.field_description' },
    { key: 'to', type: 'text', label: 'cms.field_url' },
];

const LINK_SCHEMA = {
    label: text,
    to: url,
    icon,
    color,
    variant: oneOf(['solid', 'outline', 'subtle', 'ghost', 'link'], 'solid'),
};

const CARD_SCHEMA = {
    title: text,
    description: text,
    icon,
    to: url,
};

// --- the vocabulary --------------------------------------------------------

export const BLOCK_TYPES = {
    hero: {
        label: 'cms.block_hero',
        icon: 'i-lucide-panel-top',
        fields: [{ key: 'title', type: 'text', label: 'cms.field_title' }, { key: 'description', type: 'textarea', label: 'cms.field_description' }, { key: 'links', type: 'repeater', label: 'cms.field_links', fields: LINK_FIELDS, max: 3 }],
        component: HeroBlock,
        schema: { title: text, description: text, links: listOf(LINK_SCHEMA, 3) },
    },
    section: {
        label: 'cms.block_section',
        icon: 'i-lucide-rows-3',
        fields: [{ key: 'title', type: 'text', label: 'cms.field_title' }, { key: 'description', type: 'textarea', label: 'cms.field_description' }, { key: 'align', type: 'select', label: 'cms.field_align', options: ['left', 'center'] }, { key: 'spacing', type: 'select', label: 'cms.field_spacing', options: ['compact', 'normal'] }],
        // The one container type: holds child blocks, which PageRenderer
        // renders into its default slot.
        component: SectionBlock,
        schema: {
            title: text,
            description: text,
            // Bounded options, not free classes — SectionBlock maps them to
            // fixed class strings. Defaults suit a text page; a marketing
            // page opts into centred and roomier.
            align: oneOf(['left', 'center'], 'left'),
            spacing: oneOf(['compact', 'normal'], 'compact'),
        },
        container: true,
    },
    features: {
        label: 'cms.block_features',
        icon: 'i-lucide-layout-grid',
        fields: [{ key: 'columns', type: 'number-select', label: 'cms.field_columns', options: [2, 3, 4] }, { key: 'items', type: 'repeater', label: 'cms.field_cards', fields: CARD_FIELDS, max: 24 }],
        component: FeaturesBlock,
        schema: { columns: oneOf([2, 3, 4], 3), items: listOf(CARD_SCHEMA, 24) },
    },
    image: {
        label: 'cms.block_image',
        icon: 'i-lucide-image',
        // src goes through the same URL rule as every link: an unvalidated
        // src is a request to any host the stored content names.
        component: ImageBlock,
        schema: { src: url, alt: text, caption: text, width: oneOf(['full', 'wide', 'narrow'], 'full'), rounded: bool(true) },
        fields: [
            { key: 'src', type: 'text', label: 'cms.field_image_url', hint: 'cms.hint_image_url' },
            { key: 'alt', type: 'text', label: 'cms.field_alt', hint: 'cms.hint_alt' },
            { key: 'caption', type: 'text', label: 'cms.field_caption' },
            { key: 'width', type: 'select', label: 'cms.field_width', options: ['full', 'wide', 'narrow'] },
            { key: 'rounded', type: 'toggle', label: 'cms.field_rounded' },
        ],
    },
    prose: {
        label: 'cms.block_prose',
        icon: 'i-lucide-text',
        fields: [{ key: 'text', type: 'textarea', label: 'cms.field_text', hint: 'cms.hint_inline_markdown', rows: 4 }],
        // Body copy. Goes through the inline markdown parser, never v-html —
        // see Support/richtext.js.
        component: ProseBlock,
        schema: { text },
    },
    list: {
        label: 'cms.block_list',
        icon: 'i-lucide-list',
        fields: [
            { key: 'items', type: 'repeater', label: 'cms.field_items', fields: LIST_ITEM_FIELDS, max: 24 },
            { key: 'ordered', type: 'toggle', label: 'cms.field_ordered' },
        ],
        // Bulleted or numbered copy. The prose block renders one paragraph,
        // and a policy or rules page is mostly lists — writing those as
        // dashed prose would look like a list without being one.
        component: ListBlock,
        schema: { items: listOf(LIST_ITEM_SCHEMA, 24), ordered: bool(false) },
    },
    callout: {
        label: 'cms.block_callout',
        icon: 'i-lucide-message-square-warning',
        fields: [{ key: 'title', type: 'text', label: 'cms.field_title' }, { key: 'description', type: 'textarea', label: 'cms.field_description' }, { key: 'icon', type: 'icon', label: 'cms.field_icon' }, { key: 'color', type: 'color', label: 'cms.field_color' }],
        component: CalloutBlock,
        schema: { title: text, description: text, icon, color },
    },
    links: {
        label: 'cms.block_links',
        icon: 'i-lucide-mouse-pointer-click',
        fields: [{ key: 'links', type: 'repeater', label: 'cms.field_links', fields: LINK_FIELDS, max: 4 }],
        // A row of buttons under a paragraph. Separate from `cta`, which
        // draws a whole panel — see LinksBlock for why.
        component: LinksBlock,
        schema: { links: listOf(LINK_SCHEMA, 4) },
    },
    cta: {
        label: 'cms.block_cta',
        icon: 'i-lucide-megaphone',
        fields: [{ key: 'title', type: 'text', label: 'cms.field_title' }, { key: 'description', type: 'textarea', label: 'cms.field_description' }, { key: 'links', type: 'repeater', label: 'cms.field_links', fields: LINK_FIELDS, max: 2 }],
        component: CtaBlock,
        schema: { title: text, description: text, links: listOf(LINK_SCHEMA, 2) },
    },
    separator: {
        label: 'cms.block_separator',
        icon: 'i-lucide-minus',
        fields: [],
        component: SeparatorBlock,
        schema: {},
    },
};

/** Builds a fresh props object containing only what the schema allows. */
export function sanitize(schema, raw) {
    const source = raw && typeof raw === 'object' ? raw : {};

    return Object.fromEntries(
        Object.entries(schema).map(([key, coerce]) => [key, coerce(source[key])]),
    );
}

/**
 * Resolves one stored block to {component, props, children} — or null if its
 * type isn't in the vocabulary.
 *
 * An unknown type returning null rather than throwing is deliberate: content
 * authored against a newer deploy shouldn't take down the whole page on an
 * older one, it should just render the blocks that deploy understands.
 */
export function resolveBlock(block) {
    const entry = BLOCK_TYPES[block?.type];

    if (!entry) return null;

    return {
        component: entry.component,
        props: sanitize(entry.schema, block.props),
        children: entry.container && Array.isArray(block.blocks) ? block.blocks : [],
    };
}

/** Options for the editor's "add block" menu, in vocabulary order. */
export function blockTypeOptions() {
    return Object.entries(BLOCK_TYPES).map(([value, entry]) => ({
        value,
        label: entry.label,
        icon: entry.icon,
    }));
}

/** Field descriptors for one type; empty for an unknown one. */
export function fieldsFor(type) {
    return BLOCK_TYPES[type]?.fields ?? [];
}

export function isContainer(type) {
    return Boolean(BLOCK_TYPES[type]?.container);
}

/** A new block of the given type, with every field present and empty. */
export function blankBlock(type) {
    const props = {};

    for (const field of fieldsFor(type)) {
        props[field.key] = field.type === 'repeater' ? [] : null;
    }

    return isContainer(type) ? { type, props, blocks: [] } : { type, props };
}
