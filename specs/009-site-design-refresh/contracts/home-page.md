# Contract: Home Page

**Route**: `GET /` (`home`), unchanged. **View**: `resources/views/welcome.blade.php`. No queries against user, couple, item or review data.

## Actions by visitor state

| Location | Guest, registration on | Guest, registration off | Signed in |
|----------|------------------------|-------------------------|-----------|
| Top bar | "Log in" (`login`), secondary | "Log in" (`login`), secondary | "Open Our Little List" (`partner.search`), secondary |
| Hero | "Create your account" (`register`), primary | "Log in" (`login`), primary | "Open Our Little List" (`partner.search`), primary |
| End of page | "Create your account" (`register`), primary | "Log in" (`login`), primary | "Open Our Little List" (`partner.search`), primary |

Registration availability is `Features::enabled(Features::registration())`. Hero and end-of-page actions are rendered by `<x-home-cta />`.

## Structure

Landmarks are `<header>` and `<main>`, with exactly one `<h1>` and an `<h2>` per section. Source order matches visual order on phones. From the `lg` breakpoint the hero may place the example beside the text.

| # | Section | Content |
|---|---------|---------|
| 1 | Top bar | Logo and app name, top-bar action |
| 2 | Hero | Headline, explanation, hero action. Must fit above the fold at 320×568 |
| 3 | Example | Fictional comparison, labelled "Example" |
| 4 | How it works | Three numbered steps |
| 5 | Categories | One item per `ReviewCategory` case, with `icon()` and `label()` |
| 6 | Privacy | Plain statement that reviews are for the two of you |
| 7 | Closing | Short invitation and end-of-page action |

Decorative icons use `aria-hidden`. The page has no animation, images or horizontal scrolling at 320px and above. Text uses the theme's tokens only.

## Copy

| Element | Text |
|---------|------|
| Headline | Everything you've loved, together. |
| Explanation | A private little place for the two of you to review the films, shows, books, food and restaurants you share. |
| Example title | Moonlight Over Marrow Lane |
| Example, "You", 4.5 stars | Cried twice. Would watch again tomorrow. |
| Example, "Your partner", 3 stars | Lovely, but far too long. Good snacks though. |
| How it works heading | How it works |
| Step 1 | **Create your account.** It only takes a minute. |
| Step 2 | **Connect with your partner.** Find them by username and send a request. They accept, and you're linked. |
| Step 3 | **Review things together.** Rate what you share, write what you thought, and see how your opinions compare. |
| Categories heading | For everything you do together |
| Privacy heading | Just for the two of you |
| Privacy text | Your reviews are visible only to you and your partner. Nobody else can read them. |
| Closing heading | Start your little list today |

Copy describes only what exists today and mentions no price, testimonials or usage figures (FR-016, FR-017). The example must carry its "Example" label wherever it appears.

## Test contract (`tests/Feature/HomePageTest.php`)

| Case | Expectation |
|------|-------------|
| Guest | Logo, headline, `login` link and at least two `register` links |
| Guest | How-it-works steps, every `ReviewCategory` label, the example label and the privacy statement |
| Signed in | `partner.search` link; no `register` link; no "Log in" |
| Registration disabled by config | No `register` link; `login` link present |
| Existing user and review in the database | Their name, username and review text are absent from the page |
| Existing | App name shown, no framework starter content |
