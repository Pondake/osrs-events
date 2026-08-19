<template>
    <!-- Logo 5a — source of truth is resources/images/logo/osrs-events-logo-color.svg,
         inlined here rather than <img>-ed so it renders server-side with no
         extra request. Kept byte-identical to that file's rects.

         No tile and no light/dark colour swap, unlike the mark this replaced:
         the darker handles and bases (#c15c1f / #9a721e / #b4501a) give the
         silhouette its own edge, so it holds up on white, on #1c1919, and on
         mid-grey alike — verified by rendering all three at 32px.

         size-8 on a 16-unit viewBox is exactly 2x, which keeps the pixels
         crisp; the mark's own spec forbids non-integer scaling, so don't
         swap this for an arbitrary width. -->
    <svg
        viewBox="0 0 16 16"
        shape-rendering="crispEdges"
        role="img"
        :aria-label="$t('common.app_name')"
        class="size-8 shrink-0"
    >
        <rect x="1" y="9" width="1" height="4" fill="#c15c1f" />
        <rect x="2" y="9" width="1" height="1" fill="#c15c1f" />
        <rect x="2" y="12" width="1" height="1" fill="#c15c1f" />
        <rect x="3" y="7" width="5" height="8" fill="#e0762f" />
        <rect x="3" y="7" width="1" height="8" fill="#f09a4e" />
        <rect x="3" y="14" width="5" height="1" fill="#b4501a" />
        <rect x="3" y="4" width="5" height="2" fill="#ffcf5c" />
        <rect x="15" y="9" width="1" height="4" fill="#9a721e" />
        <rect x="14" y="9" width="1" height="1" fill="#9a721e" />
        <rect x="14" y="12" width="1" height="1" fill="#9a721e" />
        <rect x="9" y="7" width="5" height="8" fill="#d4a33e" />
        <rect x="9" y="7" width="1" height="8" fill="#eec257" />
        <rect x="9" y="14" width="5" height="1" fill="#9a721e" />
        <rect x="9" y="4" width="5" height="2" fill="#f2d894" />
        <rect x="7" y="1" width="2" height="2" fill="#ffcf5c" />
        <rect x="4" y="2" width="1" height="1" fill="#e0762f" />
        <rect x="11" y="2" width="1" height="1" fill="#d4a33e" />
    </svg>
</template>
