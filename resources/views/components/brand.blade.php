{{--
    The ParkOps lockup: mark, wordmark, and optionally the tagline.

    One component so the sidebar, the mobile drawer and the login screen cannot
    drift apart — "samakan" is a property of having a single definition, not of
    three places happening to agree today.

    The mark is public/img/parkops-icon.svg exactly as supplied: pure shapes, so
    it renders identically at any size. The wordmark is *not* the supplied
    parkops-logo.svg, and deliberately so — that file sets font-family="Poppins"
    on a <text> element, and an SVG used as an <img> is an isolated document
    that cannot pull in a webfont. It would silently fall back to whatever the
    viewer's system serves, which is the one thing a wordmark must never do.
    Real HTML text in the same Poppins the page loads renders as drawn, and
    stays selectable, scalable and translatable besides.

    @param string $size  'sm' (sidebar, drawer) or 'lg' (login)
    @param bool $tagline  show "Parking operations management"
--}}
@props([
    'size' => 'sm',
    'tagline' => false,
])

<span {{ $attributes->merge(['class' => 'brand brand-'.$size]) }}>
    <img src="{{ asset('img/parkops-icon.svg') }}" alt="" class="brand-mark" width="512" height="512">

    <span class="brand-text">
        {{-- Two-tone exactly as the artwork: "Park" in ink, "Ops" in brand blue. --}}
        <span class="brand-name">Park<span>Ops</span></span>

        @if ($tagline)
            <span class="brand-tagline">Parking operations management</span>
        @endif
    </span>
</span>
