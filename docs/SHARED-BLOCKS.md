# Shared blocks maintenance

The site is static. Header, navigation, footer and cookie markup are repeated in each HTML file.

Reference copies are stored in:

- `shared-reference/header-reference.html`
- `shared-reference/footer-reference.html`
- `shared-reference/cookie-banner-reference.html` if present

These files are references only. They are not loaded by the browser.

## When changing the header or navigation

Update the block between:

```html
<!-- SHARED HEADER START -->
...
<!-- SHARED HEADER END -->
```

in every HTML page.

## When changing the footer

Update the block between:

```html
<!-- SHARED FOOTER START -->
...
<!-- SHARED FOOTER END -->
```

in every HTML page.

## Pages to check

- `index.html`
- `about.html`
- `cakeshop.html`
- `pastryshop.html`
- `flavours.html`
- `week-special.html`
- `jobs.html`
- `order.html`
- `agb.html`
- `datenschutz.html`
- `impressum.html`

After shared-block edits, test desktop nav, mobile nav, social links, order CTA and footer links.
