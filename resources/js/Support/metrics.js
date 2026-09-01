import { trans } from 'laravel-vue-i18n';
import { BOSSES_WITH_ICONS } from '@/Support/bossIcons';

/**
 * Which Wise Old Man vocabulary an event type races on.
 *
 * Mirrors Event::EVENT_TYPES' `metricKind` on the server. Kept as a map
 * rather than derived from the type name so adding a type is one edit in each
 * language rather than a rule that quietly stops matching.
 */
const METRIC_KIND = {
    SKILL_RACE: 'skill',
    DROP_RACE: 'boss',
};

export function metricKindFor(type) {
    return METRIC_KIND[type] ?? null;
}

/**
 * The display name for a metric.
 *
 * The i18n namespace has to match the kind: a boss slug looked up under
 * `skills.` renders as the raw key, which is how "abyssal_sire" ends up on a
 * page. Falls back to the slug rather than an empty string so a metric added
 * upstream before its translation shows something readable.
 */
export function metricLabel(metric, kind) {
    if (!metric) return '—';

    const namespace = kind === 'boss' ? 'bosses' : 'skills';
    const label = trans(`${namespace}.${metric}`);

    return label === `${namespace}.${metric}` ? metric : label;
}

/**
 * "Ranked by X XP gained" or "Ranked by X kills" — the noun depends on what
 * is being counted, and calling boss kills XP would just be wrong.
 */
export function rankedByLabel(metric, kind) {
    const name = metricLabel(metric, kind);

    return kind === 'boss'
        ? trans('events.ranked_by_kills', { metric: name })
        : trans('events.ranked_by', { skill: name });
}

/**
 * Grouped thousands for a race's gained value — XP runs into the millions
 * (and boss KC into the hundreds) and an unbroken run of digits can't be
 * read at a glance. Shared by SkillRace.vue, Boards/Mine.vue and
 * RacePreview.vue, which each had their own copy of the same one-liner.
 */
export function formatMetricValue(value) {
    return new Intl.NumberFormat('en-GB').format(value ?? 0);
}

/**
 * The OSRS icon for a metric, or null when there isn't one.
 *
 * Files are committed under public/images/osrs/{skills,bosses}/, named by the
 * Wise Old Man metric so no second lookup table stands between the stored
 * value and the file — see scripts/extract-osrs-icons.mjs, which writes them.
 *
 * Bosses were the long-standing gap: the icon set is built from wiki item and
 * category images, and there is no "Zulrah icon" — only Zulrah's scales and
 * Zulrah's pet. **The pet is the answer.** One sprite per boss, unambiguous,
 * and already in the same package the skills come from.
 *
 * Not every boss has one, which is why the membership test is here rather
 * than a bare path: fifteen drop no pet at all, or are new enough that the
 * package has not caught up. Asking for a file that was never written is a
 * 404 and a broken image, so those answer null and render as no icon.
 */
export function metricIconUrl(metric, kind) {
    if (!metric) return null;

    if (kind === 'boss') {
        return BOSSES_WITH_ICONS.has(metric) ? `/images/osrs/bosses/${metric}.png` : null;
    }

    return `/images/osrs/skills/${metric}.png`;
}
