<template>
    <!-- Heading and action bar stack until `lg`. With a `flex-1` heading the
         row never wraps on its own (a zero basis "fits" anywhere), so side by
         side the actions kept their natural width and squeezed the title to
         one word per line, ~190px at 690px wide. -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-6">
        <slot />

        <div class="flex flex-wrap items-center gap-2 lg:gap-3 lg:shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
