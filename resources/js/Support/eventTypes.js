import { trans } from 'laravel-vue-i18n';

/**
 * Icon and label for each event type.
 *
 * Mirrors `Event::EVENT_TYPES` on the server, which is where the icons are
 * also declared for the create form. Duplicated here rather than read from the
 * shared `site.eventTypes` prop because a card renders in lists that have no
 * business reaching into page state for a label — and the icon strings must be
 * literal in source anyway, or Tailwind's icon scan never bundles them.
 */
const EVENT_TYPE_META = {
    SNAKES_LADDERS: { icon: 'i-lucide-dice-6', label: 'events.type_snakes_ladders' },
    SKILL_RACE: { icon: 'i-lucide-trophy', label: 'events.type_skill_race' },
    DROP_RACE: { icon: 'i-lucide-swords', label: 'events.type_drop_race' },
    BINGO: { icon: 'i-lucide-grid-3x3', label: 'events.type_bingo' },
};

/**
 * @returns {{icon: string, label: string}|null} null for an unknown type, so
 * a caller renders nothing rather than an empty badge.
 */
export function eventTypeMeta(type) {
    const meta = EVENT_TYPE_META[type];

    return meta ? { icon: meta.icon, label: trans(meta.label) } : null;
}
