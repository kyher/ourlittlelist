# Research: Replace the Laravel Logo

## R1. Icon source: emoji, icon library, or hand-made SVG

- **Decision**: A small hand-made SVG of two overlapping hearts, single colour (`fill="currentColor"`), square `viewBox="0 0 24 24"`. The back heart is cut away where the front heart overlaps it (an SVG `<mask>` using a slightly enlarged copy of the front heart), leaving a thin gap so the two shapes read as "linked" in one colour.
- **Rationale**: Spec FR-002/FR-007 need theme-following colour and identical rendering on every device, which rules out emoji. Heroicons (bundled with Flux) only has a single `heart`; Lucide (which Flux can import via `flux:icon`) has no two-heart icon either. Importing an icon for one SVG would also go against Principle VI. The mask approach keeps it to one colour without needing a background-coloured stroke, so it works on any background.
- **Alternatives considered**: 💑 / 💕 emoji (rejected: per-device rendering, no theme colour, gendered couple emoji). Single Heroicons `heart` (rejected: does not say "couple"). Outline back heart + filled front heart (rejected: thin outline disappears at 16px).

## R2. Where the artwork lives

- **Decision**: Replace the body of `resources/views/components/app-logo-icon.blade.php` with the new SVG, keeping `{{ $attributes }}` on the `<svg>` element and adding `data-logo="our-little-list"` as a stable marker for tests. No call sites change (`app-logo`, `welcome`, `layouts/auth/simple`) because they already pass size and colour classes.
- **Rationale**: Issue #13 notes this one component feeds every in-page logo. FR-006 (same accessible name and link) is satisfied by not touching `app-logo.blade.php` or the call sites.

## R3. Browser-tab and home-screen icons

- **Decision**:
  - `public/favicon.svg`: the same hearts in white on a rounded square filled with `#262626` (Tailwind `neutral-800`, the app's light-mode `--color-accent`). This matches the navigation-bar logo badge (white icon on the accent square) and has a solid background, so it is visible on light and dark tab bars (FR-004).
  - `public/apple-touch-icon.png`: 180×180 PNG of the same artwork (180px is Apple's current recommended size; the old file was 166×166). Full-bleed square (no rounding) because iOS applies its own mask.
  - `public/favicon.ico`: 32×32 PNG wrapped in an ICO container.
- **Rationale**: Keeps one visual identity across the three files referenced in `resources/views/partials/head.blade.php`; no changes to that partial.

## R4. Generating the PNG and ICO without new dependencies

- **Decision**: One-off generation, not a committed build step. Render `favicon.svg` to PNG at 180px and 32px with headless Chromium (Playwright is already installed on the developer machine; `page.setContent` + `element.screenshot({ omitBackground: true })`). Build `favicon.ico` by writing the 6-byte ICONDIR header + 16-byte ICONDIRENTRY + the raw 32×32 PNG bytes (PNG-in-ICO, supported by every current browser). The generation script lives in the scratchpad and is not committed; the three output files are committed.
- **Rationale**: Neither the host nor the app container has ImageMagick/rsvg; adding a package or container for three static files would breach Principle VI. The files only change when the logo changes, and the eventual designed logo will come with its own exports.
- **Alternatives considered**: Adding ImageMagick to `docker/app/Dockerfile` (rejected: heavier image for a one-off). A committed npm script with `sharp` (rejected: new dependency). Leaving `favicon.ico` as is (rejected: FR-005 requires no Laravel artwork remain).

## R5. Tests

- **Decision**: Add `tests/Feature/AppLogoTest.php` (class-based PHPUnit):
  - home page (guest), login page, and `/partner/search` (signed in) each contain `data-logo="our-little-list"` and do not contain the Laravel logo path fragment `M17.2 5.633`;
  - `public/favicon.svg` does not contain the old Laravel path fragment `M162.041 38.7592` and does contain `<mask` (the two-hearts cut-out);
  - `public/favicon.ico` starts with the ICO header bytes `00 00 01 00` and its embedded image starts with the PNG signature;
  - `public/apple-touch-icon.png` is a 180×180 PNG (read width/height from the IHDR chunk via `getimagesize`).
- **Rationale**: FR-008 and Principle IV. Byte-level checks are cheap and catch a regenerated Laravel file. Visual quality (SC-003/SC-004) is covered by the quickstart screenshot check.
