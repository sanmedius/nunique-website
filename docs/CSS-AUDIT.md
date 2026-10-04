# CSS Audit — Stability Update 3

## Current CSS files

- `style.css` — base/original site styling
- `atelier-iconic.css` — premium atelier design layer and later visual patches
- `order.css` — extracted order page CSS

## Safe changes already applied

- `style.css`: consolidated the second top-level `:root` block into the main root block.
- `atelier-iconic.css`: already uses one consolidated design-variable root block from the previous update.
- `order.html`: no large inline style block remains; order styles live in `order.css`.

## Complexity still present

Approximate selector stats:

| File | Lines | Selectors | Duplicate selector names |
|---|---:|---:|---:|
| `style.css` | ~963 | ~237 | ~22 |
| `atelier-iconic.css` | ~296 | ~360 | ~79 |
| `order.css` | ~629 | ~209 | ~48 |

Important: many duplicate selectors in `atelier-iconic.css` and `order.css` are later override patches. They should not be deleted blindly, because they may be the reason the current visual layout works.

## Recommended next safe CSS work

1. Add section headings inside existing files.
2. Group related selectors, but do not split files further.
3. Identify exact duplicate declarations.
4. Remove only rules proven unused by browser testing.
5. Keep `style.css`, `atelier-iconic.css`, and `order.css` as the main CSS files.

## Do not do yet

- Do not merge all CSS into one file.
- Do not split into many small CSS files.
- Do not remove the V4/V5/V6 patch sections without visual comparison.
- Do not rename classes unless all HTML and JS references are updated together.
