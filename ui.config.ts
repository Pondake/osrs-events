export const uiConfig = {
    colors: {
        primary: 'amber',
        neutral: 'stone',
    },
    button: {
        slots: {
            base: ['cursor-pointer'],
        },
        // Nuxt UI's solid variant is `text-inverted bg-{color}`, and
        // `--ui-text-inverted` is white in light mode. On amber that's white
        // on #fe9a00 = 2.15:1 — below even the 3:1 large-text floor. Dark mode
        // already resolves text-inverted to dark ink and scores 10.17:1, so
        // only light mode was broken. Pinning the brand ink for both modes
        // gives 8.14:1 light / 10.17:1 dark and keeps the gold fill vivid,
        // which darkening the background to amber-700 would have cost.
        // Scoped to primary on purpose: error/success are dark enough that
        // white text is correct there.
        compoundVariants: [
            {
                color: 'primary',
                variant: 'solid',
                class: 'text-[#1c1919]',
            },
        ],
    },
    main: {
        base: 'min-h-[calc(100vh-var(--ui-header-height)-var(--ui-footer-height,0px))]',
    },
};
