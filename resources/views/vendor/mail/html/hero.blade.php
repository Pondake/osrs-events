{{--
    The top of a mail: what happened, as a coloured label, then what it
    happened to, as the title. It is the part read in a notification preview
    and the part read first when the mail is opened, so the event's name is
    the biggest thing in it rather than one word in the middle of a sentence.

    Plain HTML on purpose. Everything around it is Markdown, and an event
    title is typed by a host: a title with a * or a [ in it should read as
    the title, not be turned into emphasis or a link.

    `tone` is one of: paused, live, finished, cancelled, account — see the
    .status-* rules in themes/osrs.css.
--}}
@props(['tone' => 'account', 'label', 'title', 'eyebrow' => null])
<table class="hero" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="hero-cell">
<span class="status status-{{ $tone }}">{{ $label }}</span>
@if (filled($eyebrow))
<p class="eyebrow">{{ $eyebrow }}</p>
@endif
<h1 class="hero-title">{{ $title }}</h1>
</td>
</tr>
</table>
