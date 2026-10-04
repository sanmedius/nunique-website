# JS Audit — Stability Update 3

## Current files checked

- `main.js`
- inline script at the bottom of `order.html`

## Safe changes already applied

- Added language-switch support for:
  - `data-de-alt` / `data-en-alt`
  - `data-de-title` / `data-en-title`
  - `data-de-aria` / `data-en-aria`
  - `data-de-content` / `data-en-content`
  - `data-de-caption` / `data-en-caption`
- Contact form success/error texts now follow the active language.
- CAPTCHA validation message now follows the active language.

## What remains

`main.js` is still historically grown. It contains several responsibilities in one file:

- language switching
- navigation drawer
- active nav state
- animation observer
- smooth anchor scroll
- contact form submit
- cookie banner
- FAQ accordion
- lightbox/gallery behavior
- carousel behavior
- V4 cake-gallery patch
- V4 robot-check validation

This is functional, but not easy to maintain.

## Recommended next safe refactor

Do **not** split into many files yet. First create internal sections inside `main.js`:

1. `// LANGUAGE`
2. `// NAVIGATION`
3. `// CONTACT FORM`
4. `// COOKIE BANNER`
5. `// LIGHTBOX / GALLERY`
6. `// FAQ`
7. `// ORDER HELPERS`

Then remove only clearly duplicated handlers after testing.

## Do not do yet

- Do not rewrite the whole JS.
- Do not introduce a framework.
- Do not move order form logic unless the page is fully tested afterwards.
- Do not remove V4 patches until their visual effect has been compared in browser.
