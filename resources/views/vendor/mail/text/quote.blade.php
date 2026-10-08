@props(['label' => null])
@if (filled($label)){{ $label }}: @endif"{{ $slot }}"
