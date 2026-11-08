# Data Model: Site Colour Scheme & Welcoming Home Page

No database entities, migrations or configuration change. The home page reads no stored data.

The only code-level addition to the domain is a method on the existing `App\Enums\ReviewCategory` enum:

| Case | `label()` (existing) | `icon()` (new) |
|------|----------------------|----------------|
| `Film` | Film | `film` |
| `TvShow` | TV show | `tv` |
| `Book` | Book | `book-open` |
| `Food` | Food | `cake` |
| `Restaurant` | Restaurant | `building-storefront` |

`icon()` returns a Heroicons name that Flux can render, using an exhaustive `match` so that a new case must supply an icon.

The colour scheme is static configuration, defined in [contracts/design-tokens.md](contracts/design-tokens.md).
