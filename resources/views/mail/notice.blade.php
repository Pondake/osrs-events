{{--
    The one template every mail uses: a hero (label + title), the greeting
    and lines from the MailMessage, an optional quote, the button, and the
    sign-off. Used through MailMessage::markdown('mail.notice', [...]) so a
    notification still builds its body with ->greeting() / ->line() /
    ->action(), and only adds what Laravel's stock template has no place for:

     - hero:      ['tone', 'label', 'title', 'eyebrow'?]
     - quote:     a person's own words, with quoteLabel above them
     - preheader: the line an inbox shows after the subject
     - reason:    the footer's "why you got this" line
--}}
<x-mail::message :preheader="$preheader ?? null" :reason="$reason ?? null">
@isset($hero)
<x-mail::hero :tone="$hero['tone']" :label="$hero['label']" :title="$hero['title']" :eyebrow="$hero['eyebrow'] ?? null" />

@endisset
@if (! empty($greeting))
{{ $greeting }}

@endif
@foreach ($introLines as $line)
{{ $line }}

@endforeach
@isset($quote)
<x-mail::quote :label="$quoteLabel ?? null">{{ $quote }}</x-mail::quote>

@endisset
@isset($actionText)
<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

@endisset
@foreach ($outroLines as $line)
{{ $line }}

@endforeach
<p class="signoff">{{ trans('mail.signoff') }}<br>
<strong>{{ config('app.name') }}</strong></p>

@isset($actionText)
<x-slot:subcopy>
{{ trans('mail.button_trouble') }} <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
