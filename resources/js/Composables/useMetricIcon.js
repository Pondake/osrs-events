import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { metricIconUrl } from '@/Support/metrics';

/**
 * The icon for a metric, with an admin's override taking precedence.
 *
 * Two sources and they live in different places, which is the whole reason
 * this exists. The committed pet sprites are known client-side — the same
 * script that writes the PNGs writes Support/bossIcons.js — so `metricIconUrl`
 * can answer for those on its own, with no round trip. An override is a
 * database row and has to be shared as a prop (`site.bossIconOverrides`),
 * which `metricIconUrl` cannot reach: it is a pure helper, imported by things
 * that are not components.
 *
 * So the composable owns the precedence and the helper stays pure. Reach for
 * this in a component; reach for `metricIconUrl` only where there is no page
 * to ask, and accept that overrides will not apply there.
 */
export function useMetricIcon() {
    const page = usePage();

    const overrides = computed(() => page.props?.site?.bossIconOverrides ?? {});

    /**
     * @param {string|null} metric  a Wise Old Man metric name
     * @param {string|null} kind    'skill' or 'boss'
     */
    return function iconFor(metric, kind) {
        if (!metric) return null;

        // Overrides are a boss-only concept: skills have all 24 sprites and
        // nothing to disagree with.
        if (kind === 'boss' && overrides.value[metric]) {
            return overrides.value[metric];
        }

        return metricIconUrl(metric, kind);
    };
}
