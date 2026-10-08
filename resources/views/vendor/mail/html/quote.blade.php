{{--
    Somebody's own words, set apart from the mail's — a host's reason for a
    pause. Plain HTML for the same reason as hero.blade.php: it is typed by a
    person and must arrive as they typed it.
--}}
@props(['label' => null])
<table class="quote" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="quote-cell">
@if (filled($label))
<p class="quote-label">{{ $label }}</p>
@endif
<p class="quote-text">{{ $slot }}</p>
</td>
</tr>
</table>
