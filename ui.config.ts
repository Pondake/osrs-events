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
