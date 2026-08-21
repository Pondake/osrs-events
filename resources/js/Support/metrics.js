import { trans } from 'laravel-vue-i18n';

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
