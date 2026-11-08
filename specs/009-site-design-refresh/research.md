# Research: Site Colour Scheme & Welcoming Home Page

No open clarifications came out of Technical Context. Each section records a design decision made against the current code.

## R1. Where the scheme lives

- **Decision**: Keep the scheme in `resources/css/app.css`. Re-tint the existing `--color-zinc-*` ramp to warm values and set `--color-accent*` to berry.
- **Rationale**: Flux's vendor views use `zinc` throughout (75 view files) and take their accent from `--color-accent`, `--color-accent-content` and `--color-accent-foreground`. Most project views use `zinc` too, and `app.css` already overrides the ramp. One change therefore covers almost every page and meets FR-002 and FR-003.
- **Alternatives considered**: Changing only the accent (rejected: backgrounds and text stay cold grey, so the site would not feel re-themed). A semantic token layer such as `--color-surface` with every view rewritten (rejected: large diff, and Flux would still use `zinc`). Publishing Flux views to edit them (rejected: upgrade burden across 75 files).

## R2. Palette

- **Decision**: The values in [contracts/design-tokens.md](contracts/design-tokens.md): cream to warm near-black neutrals, `#8a2252` berry in light appearance and `#f2a7c3` soft berry in dark appearance.
- **Rationale**: Each ramp step sits close to the lightness it has today, so existing light/dark pairings keep working. Berry leans towards purple, so it stays clearly separate from error reds (FR-006). `zinc-400` was tuned to `#9c8b82` so one step works both as a 3:1 boundary in light appearance and as 4.5:1 muted text in dark appearance.
- **Verification**: Contrast ratios were calculated with the WCAG formula for the pairings in the design tokens contract, including translucent dark input backgrounds. All pass.
- **Alternatives considered**: Rose or coral accent (rejected: too close to error red). Terracotta (rejected: reads earthy instead of affectionate, and sits close to amber stars).

## R3. Stock colours that fail AA

The calculations showed these fail FR-005 regardless of the new palette:

| Element | Today | Ratio | Fix |
|---------|-------|-------|-----|
| Input placeholder, light | `zinc-400` on white | about 3.3 | Unlayered CSS rule: `zinc-500` |
| Input placeholder, dark | `zinc-400` at 75% on translucent input | 2.6 | Unlayered CSS rule: `zinc-300` |
| Input, checkbox, radio boundary, light | `zinc-200`/`zinc-300` on white | 1.3–1.6 | Unlayered CSS rule: `zinc-400` |
| Input, checkbox, radio boundary, dark | `white/10` | under 3 | Unlayered CSS rule: `zinc-400` |
| Danger button and error text, light | `red-500` | 3.76 | Theme: `red-500` → `#dc2626`, `red-600` → `#b91c1c` |
| Filled stars, light | `amber-400` | 1.67 | `amber-600`, keeping `amber-400` in dark |
| Empty star outlines | `zinc-300` / `zinc-600` | 1.6 / 1.8 | `zinc-400` in both appearances |
| Prompt icon, light | `amber-500` | 2.15 | `amber-600`, keeping `amber-500` in dark |
| Success text and icon, light | `green-600` / `green-500` | 3.3 / under 3 | `green-700`, with `green-400` in dark |
| Muted text, light (`forgot-password`, `navlist/group`) | `zinc-400` | about 3.1 | `zinc-500`, with `zinc-400` in dark |
| Muted text on dark Flux cards (found in browser audit) | `zinc-400` / `zinc-500` on translucent card | 3.33 / 2.05 | `zinc-300` in dark |
| Flux `subtle` text, light (found in browser audit) | `zinc-400` | 3.09 | Default text variant |
| `opacity-50` text on two-factor challenge (found in browser audit) | Faded text | 3.92 | `zinc-500`, with `zinc-400` in dark |
| Select placeholder (found in browser audit) | `zinc-400` | about 3.3 | Unlayered CSS rule: `zinc-500`, `zinc-300` in dark |

- **Why unlayered rules**: `app.css` already overrides Flux this way. Unlayered CSS beats Flux's utilities without publishing its views.
- **Why shift red in the theme**: two lines fix every Flux danger button and error message. The shift keeps hover states one step darker than the resting state. Dark danger buttons also pass, at 6.47.
- **Disabled controls** are exempt under WCAG 1.4.3 and 1.4.11, so overrides use `:not(:disabled)` where it matters.
- **Malformed classes**: `!dark:text-green-400` in `⚡profile` and `verify-email` has the important marker in the wrong place, so these are replaced outright.

## R4. Views that bypass the ramp

- **Decision**: Replace `stone-*` with the matching `zinc-*` step in `⚡two-factor-setup-modal`. Remove `neutral-*` from the layouts. Change the base border rule in `app.css` from `--color-gray-200` to `--color-zinc-200`.
- **Rationale**: Only these places use another ramp, so aligning them is smaller than aliasing three further ramps to `zinc`.

## R5. Page backgrounds and appearance

- **Decision**: Page backgrounds become `bg-zinc-50 dark:bg-zinc-800` in the app layout and `bg-zinc-50 dark:bg-zinc-900` in the auth layout and home page. Remove the no-op `bg-background` class and the `neutral` gradients. Keep the `class="dark"` starter-kit default on `<html>`.
- **Rationale**: Cream pages with white cards give the warmth the spec asks for. `@fluxAppearance` runs synchronously in `<head>` and sets or removes `dark` before first paint, so there is no flash and saved choices are honoured. `flux.min.js`, loaded by `@fluxScripts` on these pages, follows device changes in system mode. FR-008 therefore needs no code.
- **Alternatives considered**: A new page-background token (rejected: only three uses).

## R6. Home page form

- **Decision**: Keep `Route::view('/', 'welcome')` and rebuild `welcome.blade.php` as a static Blade page using Flux buttons, with an anonymous `x-home-cta` component for the hero and end-of-page action.
- **Rationale**: The page has no interactive state, so Livewire adds nothing. The component holds the only real logic: guest, signed-in or registration-off.

## R7. Review comparison illustration

- **Decision**: Build it from markup and the real `x-star-rating` component, labelled as an example, with the reviewers shown as "You" and "Your partner" and an invented title.
- **Rationale**: No image can fail to load, so that edge case disappears. It renders in both appearances automatically, stays accurate if the star design changes, and needs no invented people's names (FR-013, FR-017).
- **Alternatives considered**: Screenshot (rejected: goes stale and needs a version per appearance). Decorative illustration (rejected: shows nothing about how the app works).

## R8. Category list

- **Decision**: Loop over `ReviewCategory::cases()` using `label()`, and add an `icon()` method to the enum with an exhaustive `match`.
- **Rationale**: Principle V. A new category shows up on the home page, and Larastan flags the `match` if it lacks an icon. The labels are the app's own vocabulary ("TV show"), which is how the spec's "series" appears in the product.

## R9. Detecting whether registration is available

- **Decision**: Use `Features::enabled(Features::registration())` in place of the current `Route::has('register')`.
- **Rationale**: Both are equivalent in production, but routes are registered at boot, so `Route::has` cannot be switched off in a test. FR-019 and FR-023 require that case to be tested, and the Fortify config can be changed per test.

## R10. Motion

- **Decision**: The home page has no animations or scroll effects.
- **Rationale**: This satisfies FR-021 by construction and matches Principle VI.

## R11. Browser-tab and home-screen icons

- **Decision**: Change the `favicon.svg` background from `#262626` to `#8a2252`, then regenerate `favicon.ico` and `apple-touch-icon.png` using the one-off Chromium export from spec 004 R4. White hearts stay. `AppLogoTest` asserts the new colour.
- **Rationale**: Berry with white hearts is visible on light and dark tab bars (FR-007) and adds no build step.

## R12. Keeping contrast correct over time

- **Decision**: Verify contrast by calculation during planning and by a manual pass in the quickstart. Do not commit an automated contrast test.
- **Rationale**: FR-023 does not ask for one, and a test that parses the stylesheet could not see how Flux combines colours in practice (Principle VI).
