import { safeHref } from '@/Support/richtext';
import CalloutBlock from './Blocks/CalloutBlock.vue';
import CtaBlock from './Blocks/CtaBlock.vue';
import FeaturesBlock from './Blocks/FeaturesBlock.vue';
import HeroBlock from './Blocks/HeroBlock.vue';
import LinksBlock from './Blocks/LinksBlock.vue';
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

const oneOf = (allowed, fallback = null) => (value) => (allowed.includes(value) ? value : fallback);

/**
 * Icon names are passed to Nuxt UI's icon resolver, so they're constrained
 * to the iconify shape rather than accepted as free text.
 */
const icon = (value) => (typeof value === 'string' && /^i-[a-z0-9]+-[a-z0-9-]+$/i.test(value) ? value : null);

/** Reuses the announcement banner's URL rule — http(s) or site-relative. */
const url = (value) => safeHref(value);

const COLORS = ['primary', 'secondary', 'success', 'info', 'warning', 'error', 'neutral'];

const color = oneOf(COLORS, 'primary');

/**
 * A bounded list of sub-objects. The cap is not tidiness: an editor (or a
 * bad import) putting ten thousand entries in one block would otherwise
 * render ten thousand nodes.
 */
const listOf = (schema, max) => (value) => (Array.isArray(value)
    ? value.slice(0, max).map((item) => sanitize(schema, item))
    : []);

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
        component: HeroBlock,
        schema: { title: text, description: text, links: listOf(LINK_SCHEMA, 3) },
    },
    section: {
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
        component: FeaturesBlock,
        schema: { columns: oneOf([2, 3, 4], 3), items: listOf(CARD_SCHEMA, 24) },
    },
    prose: {
        // Body copy. Goes through the inline markdown parser, never v-html —
        // see Support/richtext.js.
        component: ProseBlock,
        schema: { text },
    },
    callout: {
        component: CalloutBlock,
        schema: { title: text, description: text, icon, color },
    },
    links: {
        // A row of buttons under a paragraph. Separate from `cta`, which
        // draws a whole panel — see LinksBlock for why.
        component: LinksBlock,
        schema: { links: listOf(LINK_SCHEMA, 4) },
    },
    cta: {
        component: CtaBlock,
        schema: { title: text, description: text, links: listOf(LINK_SCHEMA, 2) },
    },
    separator: {
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
