export const uiConfig = {
    colors: {
        primary: 'amber',
        neutral: 'stone',
    },
    button: {
        slots: {
            // 44px minimum on phones, whatever size the button asks for.
            //
            // Measured on a real event page at 375px: the action row
            // ("Participants", "Leave event", "Event settings") renders at
            // `size="sm"` = 28px tall, and the header's icon buttons at 32px.
            // Apple's floor is 44 and Android's is 48; six 28px targets
            // wrapping over two lines is a mikado game with a thumb.
            //
            // Here rather than on each call site, because the sizes are
            // right on a desktop — this is about the input device, not about
            // the emphasis of any one button. min-w matters for the
            // icon-only ones, which would otherwise grow tall and stay
            // narrow; on a button with a label it is inert.
            base: ['cursor-pointer', 'max-sm:min-h-11', 'max-sm:min-w-11'],
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
