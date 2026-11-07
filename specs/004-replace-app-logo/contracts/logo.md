# Logo Contract: Replace the Laravel Logo

## `<x-app-logo-icon>` component

| Aspect | Contract |
|--------|----------|
| Root element | `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" data-logo="our-little-list" {{ $attributes }}>` |
| Colour | All shapes use `fill="currentColor"`; no hard-coded colours. Callers set colour with `text-*` classes |
| Size | Set by callers (`size-5`, `size-9`, `size-12`); artwork must fill the square viewBox evenly so existing sizes look balanced |
| Accessibility | Decorative; no `<title>`. The accessible name stays on the surrounding link/brand (`config('app.name')`) |
| Mask ids | Must be unique per instance (e.g. suffixed with a per-render id) because the icon can appear twice on one page (top bar + phone slide-out) |

Existing callers, unchanged:

| Caller | Classes passed |
|--------|----------------|
| `components/app-logo.blade.php` (top bar, phone slide-out) | `size-5 fill-current text-white dark:text-black` on an accent square |
| `layouts/auth/simple.blade.php` (all auth pages) | `size-9 fill-current text-black dark:text-white` |
| `welcome.blade.php` (home page) | `size-12 fill-current text-black dark:text-white` |

## Public icon files (referenced by `partials/head.blade.php`, unchanged)

| Path | Required format |
|------|-----------------|
| `/favicon.svg` | SVG, solid `#262626` rounded-square background, white hearts, no Laravel artwork |
| `/favicon.ico` | ICO (`00 00 01 00` header), one 32×32 entry containing a PNG |
| `/apple-touch-icon.png` | PNG, 180×180 |
