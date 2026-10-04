# JavaScript cleanup plan

Current main file:

- `main.js` handles language switching, navigation, cookie banner, lightbox, carousel, FAQ and several historic patches.

Do not split JavaScript right now. First reduce internal complexity.

## Safe cleanup order

1. Add section comments inside `main.js` if needed.
2. Identify duplicate event listeners for gallery/lightbox/carousel.
3. Remove only duplicate blocks that are proven redundant.
4. Keep language switching centralized; it currently also updates alt/title/aria/meta/caption fields.
5. Keep order-form logic stable because the live submission test succeeded.

## Only consider a split if

- order-specific logic grows again, or
- main.js becomes risky to maintain, or
- a browser test confirms a clean separation is safe.

Possible future files:

- `main.js` for site-wide initialization
- `order.js` only for the order form

Do not introduce module imports or bundlers without explicit approval.
