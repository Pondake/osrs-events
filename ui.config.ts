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
    // Same 44px floor for form triggers on phones.
    input: { slots: { base: 'max-sm:min-h-11' } },
    select: { slots: { base: 'max-sm:min-h-11' } },
    selectMenu: { slots: { base: 'max-sm:min-h-11' } },
    inputMenu: { slots: { base: 'max-sm:min-h-11', trailing: 'max-sm:min-w-11 justify-center' } },
    breadcrumb: {
        slots: {
            link: 'max-sm:min-h-11',
        },
    },
    // A modal is portalled to <body> with z-index:auto, and the header is a
    // sticky z-50 stacking context — so on a short viewport the header
    // painted over the top of the dialog and cut its title in half. The same
    // bug AppHeader's hover popover already carries a z-[60] for; fixed here
    // once instead of per modal, because every dialog in the app is under
    // that header. The overlay is lifted with it, or it would dim the page
    // and leave the header bright above its own dialog.
    modal: {
        slots: {
            overlay: 'z-[60]',
            content: 'z-[60]',
        },
    },
    // Above the z-[60] modal, or a popover opened from inside a dialog sits
    // under its overlay and cannot be clicked.
    popover: {
        slots: {
            content: 'z-[70]',
        },
    },
    // A sentence wraps at a readable width instead of running across the
    // page on one truncated line.
    tooltip: {
        slots: {
            content: 'h-auto max-w-64 py-1.5',
            text: 'whitespace-normal leading-snug',
        },
    },
    // Same stacking context, same fix: a slideover and a drawer are the same
    // kind of portalled panel.
    slideover: {
        slots: {
            overlay: 'z-[60]',
            content: 'z-[60]',
        },
    },
    drawer: {
        slots: {
            overlay: 'z-[60]',
            content: 'z-[60]',
        },
    },
    main: {
        base: 'min-h-[calc(100vh-var(--ui-header-height)-var(--ui-footer-height,0px))]',
    },
};
