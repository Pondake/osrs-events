/**
 * Nuxt UI v4 component configuration.
 * Imported by app.config.ts — keeps UI defaults separate from app config.
 *
 * In Nuxt UI v4, component overrides follow the TV (Tailwind Variants) slot structure.
 * Classes provided here are MERGED with the Nuxt UI defaults — not replaced.
 */
export const uiConfig = {
  colors: {
    primary: 'purple',
    neutral: 'stone',
  },
  button: {
    slots: {
      base: ['cursor-pointer'],
    },
  },
  main: {
    base: 'min-h-[calc(100vh-var(--ui-header-height)-var(--ui-footer-height,0px))]',
  },
};
