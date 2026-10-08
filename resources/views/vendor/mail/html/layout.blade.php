{{--
    The page every mail sits on. Laravel's own layout plus two things it has
    no slot for:

     - A preheader: the grey line an inbox shows after the subject. Without
       one, clients fill it with whatever text comes first, which for every
       mail here was the wordmark ("OSRS Events Hi Zezima, The host…").
     - Tighter padding and a smaller title on phones, where most of these get
       read.
     - A dark version. The theme is inlined into every element, so the only
       way to restyle a mail once it is in an inbox is a media query in this
       <style> block, which the inliner leaves alone. Every rule needs
       !important to beat the inline style it replaces; the button keeps its
       gold because its colours are inline !important, which nothing here
       outranks. Gmail ignores all of it and darkens the light version its
       own way, which the light palette survives.

    The rest of the look is themes/osrs.css.
--}}
@props(['preheader' => null])
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}

.content-cell {
padding: 24px !important;
}

.hero-title {
font-size: 23px !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
text-align: center !important;
}
}
@media (prefers-color-scheme: dark) {
body,
.wrapper,
.body {
background-color: #1c1917 !important;
}

.inner-body {
background-color: #292524 !important;
border-bottom-color: #44403c !important;
border-left-color: #44403c !important;
border-right-color: #44403c !important;
}

p,
td,
li,
.signoff {
color: #e7e5e4 !important;
}

h1,
h2,
h3,
.header a,
.signoff strong,
.quote-text {
color: #fafaf9 !important;
}

a {
color: #e8bd62 !important;
}

.hero-cell,
.subcopy {
border-color: #44403c !important;
}

.eyebrow,
.quote-label,
.footer p,
.footer a {
color: #a8a29e !important;
}

.quote-cell {
background-color: #332c26 !important;
}

.status-paused {
background-color: #4a3512 !important;
color: #fcd98a !important;
}

.status-live {
background-color: #1f3d24 !important;
color: #a9dcaf !important;
}

.status-cancelled {
background-color: #4d221b !important;
color: #f4b6a8 !important;
}

.status-account {
background-color: #3a332d !important;
color: #e7e5e4 !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>
@if (filled($preheader))
<div style="display: none; max-height: 0; max-width: 0; overflow: hidden; opacity: 0; mso-hide: all; font-size: 1px; line-height: 1px; color: #efe9df;">{{ $preheader }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
@endif

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
