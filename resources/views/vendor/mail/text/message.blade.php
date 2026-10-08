{{-- The plain-text half of html/message.blade.php: same footer, no markup. --}}
@props(['preheader' => null, 'reason' => null])
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
@if (filled($reason))
{{ $reason }}

@endif
{{ trans('mail.footer_tagline') }} {{ config('app.url') }}

{{ trans('mail.footer_jagex') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
