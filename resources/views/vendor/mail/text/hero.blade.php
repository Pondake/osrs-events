@props(['tone' => 'account', 'label', 'title', 'eyebrow' => null])
{{ mb_strtoupper($label) }}@if (filled($eyebrow)) · {{ $eyebrow }}@endif

{{ $title }}
