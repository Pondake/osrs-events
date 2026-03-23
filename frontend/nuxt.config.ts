// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr: true,
  
  modules: ['@nuxt/eslint', '@nuxt/ui', '@vueuse/nuxt', '@pinia/nuxt', '@nuxtjs/i18n'],

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

  routeRules: {
    '/': { prerender: false },
    '/admin/**': { ssr: true },
    '/boards/**': { ssr: true },
    '/profile/**': { ssr: true },
  },

  app: {
    head: {
      link: [
        { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
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
