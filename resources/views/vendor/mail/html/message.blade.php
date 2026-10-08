{{--
    Laravel's message frame with our own footer. The stock one is a
    copyright line ("© 2026 OSRS Events. All rights reserved.") that tells
    the reader nothing; this one says why they got the mail, where it came
    from, and that the site is not Jagex's.

    `reason` is that first line, per mail. It names the event, whose title a
    host typed, so it goes in as HTML: the footer is Markdown, and a title
    with link syntax in it must not arrive as a link. `preheader` is handed
    on to the layout.
--}}
@props(['preheader' => null, 'reason' => null])
<x-mail::layout :preheader="$preheader">
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
@if (filled($reason))
<p>{{ $reason }}</p>

@endif
{{ trans('mail.footer_tagline') }} [{{ preg_replace('#^https?://#', '', config('app.url')) }}]({{ config('app.url') }})

{{ trans('mail.footer_jagex') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
