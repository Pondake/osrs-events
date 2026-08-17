interface SeoOptions {
  /** Page title, without the site-name suffix — the title template appends that. */
  title: string;
  /** Meta description. Aim for 120–160 characters. */
  description: string;
  /** Site-root-relative path to the social preview image. */
  image?: string;
  /** Keep the page out of search results. Links are still followed. */
  noindex?: boolean;
  ogType?: 'website' | 'article';
  /** JSON-LD blocks to embed. `@context` is added automatically. */
  jsonLd?: Record<string, unknown> | Record<string, unknown>[];
}

const DEFAULT_OG_IMAGE = '/og-image.png';

/**
 * Sets every per-page SEO tag in one call: title, description, canonical URL,
 * Open Graph, Twitter card, robots directive and optional JSON-LD.
 *
 * Canonical and og:url are absolute and built from the site URL, since search
 * engines and social scrapers both reject relative values. The path comes from
 * `route.path` rather than `fullPath` so query strings (`?error=auth_failed`,
 * campaign tags) collapse onto a single canonical rather than splitting it.
 */
export function useSeo(options: SeoOptions | (() => SeoOptions)) {
  const route = useRoute();
  const siteConfig = useSiteConfig();

  const resolved = computed(() => (typeof options === 'function' ? options() : options));

  const canonical = computed(() => new URL(route.path, siteConfig.url).toString());
  const imageUrl = computed(() =>
    new URL(resolved.value.image ?? DEFAULT_OG_IMAGE, siteConfig.url).toString(),
  );

  useSeoMeta({
    title: () => resolved.value.title,
    description: () => resolved.value.description,

    ogTitle: () => resolved.value.title,
    ogDescription: () => resolved.value.description,
    ogType: () => resolved.value.ogType ?? 'website',
    ogUrl: () => canonical.value,
    ogSiteName: siteConfig.name,
    ogImage: () => imageUrl.value,
    ogImageWidth: 1200,
    ogImageHeight: 630,
    ogImageAlt: () => resolved.value.title,

    twitterCard: 'summary_large_image',
    twitterTitle: () => resolved.value.title,
    twitterDescription: () => resolved.value.description,
    twitterImage: () => imageUrl.value,
  });

  useHead({
    link: [{ rel: 'canonical', href: () => canonical.value }],
    script: () => {
      const { jsonLd } = resolved.value;
      if (!jsonLd) return [];

      return (Array.isArray(jsonLd) ? jsonLd : [jsonLd]).map(block => ({
        type: 'application/ld+json',
        innerHTML: JSON.stringify({ '@context': 'https://schema.org', ...block }),
      }));
    },
  });

  // @nuxtjs/robots owns the robots meta tag, so route the directive through the
  // module rather than emitting a second, competing tag. It takes a writable
  // ref rather than a computed, hence the watcher instead of a derived value.
  const robotsRule = ref<string>('index, follow');
  watchEffect(() => {
    robotsRule.value = resolved.value.noindex ? 'noindex, follow' : 'index, follow';
  });
  useRobotsRule(robotsRule);
}
