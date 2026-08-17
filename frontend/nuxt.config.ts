// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr: true,

  modules: [
    '@nuxt/eslint',
    '@nuxt/ui',
    '@vueuse/nuxt',
    '@pinia/nuxt',
    '@nuxtjs/i18n',
    '@nuxtjs/robots',
    '@nuxtjs/sitemap',
  ],

  devtools: {
    enabled: true,
  },

  css: ['~/assets/css/main.css'],

  runtimeConfig: {
    // Private (server-side only)
    discordClientSecret: process.env.DISCORD_CLIENT_SECRET,
    jwtSecret: process.env.JWT_SECRET,
    // Public (exposed to client)
    public: {
      graphqlUrl: process.env.NUXT_PUBLIC_GRAPHQL_URL || 'http://localhost:3001/graphql',
      discordClientId: process.env.DISCORD_CLIENT_ID,
      appUrl: process.env.NUXT_PUBLIC_APP_URL || 'http://localhost:3000',
    },
  },

  i18n: {
    locales: [{ code: 'en', language: 'en-US', name: 'English', file: 'en.json' }],
    defaultLocale: 'en',
    strategy: 'no_prefix',
    langDir: '../locales/',
  },

  // Canonical origin for canonical tags, og:url, robots.txt and the sitemap.
  // Overridable per-environment so preview deploys don't advertise production URLs.
  site: {
    url: process.env.NUXT_PUBLIC_APP_URL || 'https://osrs-events.com',
    name: 'OSRS Events',
  },

  robots: {
    // Everything not explicitly disallowed below is crawlable. Board detail
    // pages decide per-board (see useSeo) because indexability depends on the
    // board's accessMode, which only the server knows at request time.
    disallow: ['/admin', '/profile', '/auth'],
  },

  sitemap: {
    // Auth-gated or per-user routes never belong in a sitemap.
    // /teams renders an "unauthorized" state to logged-out visitors, so to a
    // crawler it is an empty page — indexing it would only add a thin result.
    exclude: ['/admin/**', '/profile/**', '/auth/**', '/boards/*/join/**', '/teams'],
    sources: ['/api/__sitemap__/boards'],
  },

  routeRules: {
    '/': { prerender: false },
    '/admin/**': { ssr: true, robots: false },
    '/boards/**': { ssr: true },
    '/boards/*/join/**': { robots: false },
    '/profile/**': { ssr: true, robots: false },
    '/teams': { robots: false },
    '/auth/**': { robots: false },
  },

  app: {
    head: {
      meta: [
        { name: 'theme-color', content: '#1C1919' },
        { name: 'apple-mobile-web-app-capable', content: 'yes' },
        { name: 'apple-mobile-web-app-status-bar-style', content: 'black-translucent' },
        { name: 'apple-mobile-web-app-title', content: 'OSRS Events' },
        { name: 'mobile-web-app-capable', content: 'yes' },
        { name: 'application-name', content: 'OSRS Events' },
        { name: 'msapplication-TileColor', content: '#1C1919' },
      ],
      link: [
        // Favicons — transparent, monochrome trophy. favicon.svg adapts to
        // light/dark tab chrome; the .ico/PNGs are the legacy fallback.
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
        { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon-16x16.png' },
        { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon-32x32.png' },
        { rel: 'icon', type: 'image/png', sizes: '48x48', href: '/favicon-48x48.png' },
        // Home-screen / PWA icons — full logo on its own dark ground
        { rel: 'apple-touch-icon', sizes: '180x180', href: '/apple-touch-icon.png' },
        { rel: 'manifest', href: '/manifest.webmanifest' },
        // Google Fonts — Cinzel and Cinzel Decorative for OSRS-style headings
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cinzel+Decorative:wght@400;700&display=swap',
        },
      ],
    },
  },

  compatibilityDate: '2025-01-15',

  eslint: {
    config: {
      stylistic: {
        commaDangle: 'never',
        braceStyle: '1tbs',
      },
    },
  },
});
