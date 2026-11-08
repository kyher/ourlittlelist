# Contract: Design Tokens

All values live in `resources/css/app.css`. Views refer to them through Tailwind utilities and must not hard-code hex colours or use the `stone`, `neutral`, `gray` or `slate` ramps.

## Neutral ramp (`--color-zinc-*`)

| Step | Value | Typical use |
|------|-------|-------------|
| 50 | `#fdf8f3` | Cream page background and header, light |
| 100 | `#f8efe8` | Subtle fills, light |
| 200 | `#ecdfd6` | Dividers and card borders, light |
| 300 | `#dbcbc1` | Muted text and placeholders, dark |
| 400 | `#9c8b82` | Control boundaries and empty stars, both; muted text, dark |
| 500 | `#796860` | Muted text and placeholders, light |
| 600 | `#5b4c46` | Secondary text, light |
| 700 | `#463833` | Strong secondary text, light; borders, dark |
| 800 | `#2f2522` | Body text, light; app page background, dark |
| 900 | `#1f1816` | Headings, light; header and auth/home page background, dark |
| 950 | `#140f0d` | Deepest surface |

## Accent

| Token | Light | Dark |
|-------|-------|------|
| `--color-accent` | `#8a2252` | `#f2a7c3` |
| `--color-accent-content` | `#8a2252` | `#f2a7c3` |
| `--color-accent-foreground` | `#ffffff` | `#3a0f24` |

## Meaning-carrying colours

| Meaning | Light | Dark |
|---------|-------|------|
| Danger and error (theme shift) | `--color-red-500: #dc2626`, `--color-red-600: #b91c1c` | Same tokens; error text keeps `red-400` |
| Success | `text-green-700` | `text-green-400` |
| Filled star | `text-amber-600` | `text-amber-400` |
| Star input focus | `ring-accent` | `ring-accent` |

## Form control overrides

Unlayered rules in `app.css`, excluding disabled controls:

| Target | Property | Light | Dark |
|--------|----------|-------|------|
| `input`, `textarea`, `select` with `[data-flux-control]` | `::placeholder` colour | `zinc-500` | `zinc-300` |
| `select[data-flux-control]` showing its placeholder option | Text colour | `zinc-500` | `zinc-300` |
| Same controls when not `[data-invalid]`, `[data-flux-checkbox-indicator]`, `[data-flux-radio-indicator]` when unchecked | Border colour | `zinc-400` | `zinc-400` |
| Checked `ui-radio[data-flux-radio-segmented]` | Background / text and icon | accent / accent foreground | accent / accent foreground |

## Focus

`:focus-visible` in `@layer base` draws a 2px `--color-accent` outline with a 2px offset. It sits in the base layer so Flux components with their own focus styles keep them.

## Calculated contrast

| Pairing | Ratio | Required |
|---------|-------|----------|
| Light: body `800` on page `50` | 14.13 | 4.5 |
| Light: muted `500` on page `50` / white / `100` | 5.02 / 5.30 / 4.67 | 4.5 |
| Light: white on accent | 8.64 | 4.5 |
| Light: accent link on page `50` / white / `100` | 8.18 / 8.64 / 7.61 | 4.5 |
| Light: control boundary `400` on white / page `50` | 3.27 / 3.10 | 3 |
| Light: filled star `amber-600` on white / page `50` | 3.19 / 3.02 | 3 |
| Light: danger red text on white / page `50`, white on danger | 4.83 / 4.58 / 4.83 | 4.5 |
| Light: success `green-700` on white / page `50` | 5.02 / 4.75 | 4.5 |
| Dark: muted `400` on `900` / `800` | 5.36 / 4.56 | 4.5 |
| Dark: accent foreground on accent | 8.71 | 4.5 |
| Dark: accent link on `900` / `800` | 9.24 / 7.87 | 4.5 |
| Dark: placeholder `300` on input over `800` | 6.91 | 4.5 |
| Dark: control boundary `400` on input over `800` | 3.33 | 3 |
| Dark: filled star `amber-400` on `800` | 8.93 | 3 |
| Dark: white on danger `#b91c1c` | 6.47 | 4.5 |

Filled stars in light appearance fall to 2.81 on `zinc-100`, so stars must not be placed on `zinc-100` surfaces.

Flux cards are translucent in dark appearance (about `#443b38` over `zinc-800`), so muted text on them must use `zinc-300`, not `zinc-400`. Flux's `variant="subtle"` text and `opacity-*` on text do not meet AA and must not be used for content.

## Fixed surfaces

- App layout body: `bg-zinc-50 dark:bg-zinc-800`
- Auth layout and home page body: `bg-zinc-50 dark:bg-zinc-900`
- Logo on auth and home pages: `text-accent-content`
- Logo badge in the navigation bar: unchanged, as it already uses the accent tokens
- `favicon.svg`, `favicon.ico`, `apple-touch-icon.png`: white hearts on `#8a2252`
