# CSS cleanup plan

Current CSS files:

- `style.css`: older global/base site styling
- `atelier-iconic.css`: newer premium visual layer
- `order.css`: order-form-specific styling

Do not split CSS further unless there is a clear maintenance benefit. The goal is to reduce complexity, not create more files.

## Safe cleanup rules

1. Keep the current three-file structure for now.
2. Remove only rules proven to be unused across all HTML pages.
3. Do not remove V4/V5/V6 patch sections blindly; many may stabilize the current design.
4. Prefer consolidating duplicate variables and comments before deleting visual rules.
5. After each cleanup, test desktop, mobile, DE/EN, gallery and order form.

## Possible future simplification

If enough duplicates are removed, consider merging `atelier-iconic.css` into `style.css` later. Do this only after visual regression testing.
