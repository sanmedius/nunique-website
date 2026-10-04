# Shared blocks maintenance

The site is static. Header, navigation, footer and cookie markup are repeated in each HTML file.

The single source copies are stored in:

- `shared-reference/header-reference.html`
- `shared-reference/footer-reference.html`
- `shared-reference/cookie-banner-reference.html` if present

These files are not loaded by the browser. Before uploading the site, synchronize
their content into the static pages with:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\sync-shared-blocks.ps1
```

To check whether a synchronization is needed without changing files:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\sync-shared-blocks.ps1 -Check
```

## When changing the header or navigation

Edit `shared-reference/header-reference.html`, then run the synchronization
script. It updates the block between:

```html
<!-- SHARED HEADER START -->
...
<!-- SHARED HEADER END -->
```

in every HTML page.

## When changing the footer

Edit `shared-reference/footer-reference.html`, then run the synchronization
script. It updates the block between:

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
