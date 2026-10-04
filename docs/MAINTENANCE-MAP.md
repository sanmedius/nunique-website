# Maintenance Map

## Rule

This remains a static website with one isolated PHP backend for the order form.

Do not introduce:

- WordPress
- CMS
- React
- Eleventy
- Astro
- PHP templates for every page
- build process

## Shared blocks

Header and footer are repeated manually in every HTML page. To make AI maintenance safer, these blocks are now marked:

```html
<!-- SHARED HEADER START -->
...
<!-- SHARED HEADER END -->

<!-- SHARED FOOTER START -->
...
<!-- SHARED FOOTER END -->
```

When changing navigation/footer, update the marked block across all pages.

## Main files

- `style.css` — base/original styling
- `atelier-iconic.css` — premium visual layer
- `order.css` — order page only
- `main.js` — shared behaviour
- `order.html` — order form markup and inline order-specific behaviour
- `backend/send-order.php` — form processing
- `backend/config.php` — backend configuration

## Safe change procedure

1. Change one area only.
2. Test page layout.
3. Test language switch.
4. Test mobile menu.
5. Test order form if order-related files changed.
6. Zip the result and document it in `docs/CHANGELOG.md`.
