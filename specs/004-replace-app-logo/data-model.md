# Data Model: Replace the Laravel Logo

No database entities, migrations or configuration change.

The only "entity" in the spec, the **App icon**, is static artwork held in four files:

| File | Form | Colour |
|------|------|--------|
| `resources/views/components/app-logo-icon.blade.php` | Inline SVG, `viewBox="0 0 24 24"`, `data-logo="our-little-list"` | `currentColor` (follows the page) |
| `public/favicon.svg` | Standalone SVG, 24×24 artwork on a rounded square | White hearts on `#262626` |
| `public/favicon.ico` | 32×32 PNG inside an ICO container | Same as `favicon.svg` |
| `public/apple-touch-icon.png` | 180×180 PNG, full-bleed square | Same as `favicon.svg` |

`app-logo-icon.blade.php` is the source of the artwork; the other three are exports of it.
