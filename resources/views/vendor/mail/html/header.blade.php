{{--
    The mail header: the mark, then the wordmark under it.

    Overridden rather than themed, because the default renders the app name as
    text and nothing else — there is no slot for an image. This is the only
    Blade partial taken from the framework's mail views; everything else is
    left to Laravel so it keeps getting framework updates, and the look comes
    from themes/osrs.css.

    A PNG, not the SVG the site uses. Gmail strips <svg> and several clients
    will not fetch an SVG at all, so the raster app icon is what actually
    arrives. Displayed at 64px from a 192px source: an exact 3:1 downscale,
    which matters because the mark is a pixel grid and a fractional scale
    turns it to mush (see resources/images/logo/README.md).

    Width and height are attributes as well as CSS. Outlook ignores the CSS,
    and without the attributes the image lands at its full 192px.
--}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('android-chrome-192x192.png') }}"
     width="64"
     height="64"
     alt="{{ config('app.name') }}"
     style="display: block; margin: 0 auto 12px; width: 64px; height: 64px; border: 0; border-radius: 8px;">
{{ $slot }}
</a>
</td>
</tr>
