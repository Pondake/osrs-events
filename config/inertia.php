<?php

// Only the keys that differ from the package defaults. The package merges its
// own config underneath this one, one level deep, so `pages` is spelled out in full.

return [

    'pages' => [

        'ensure_pages_exist' => false,

        // The default is `js/pages`; this repo uses `js/Pages`, which only
        // matters on a case-sensitive filesystem like the CI runner.
        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],

    ],

];
