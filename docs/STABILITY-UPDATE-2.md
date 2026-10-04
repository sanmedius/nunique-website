# Stability Update 2

This build continues from Stability Update 1 and keeps the working website stable.

## Changed

1. Moved the large `order.html` inline `<style>` block into `order.css`.
2. Kept the stylesheet load order equivalent: `style.css`, then `order.css`, then `atelier-iconic.css`.
3. Fixed several missing or malformed bilingual `data-de` / `data-en` wrappers on marketing pages, cookie banners and review cards.
4. Added safe image performance attributes to local images.

## Not changed

- No JavaScript logic was rewritten.
- No PHP form validation or mail-sending behavior was changed.
- No global layout/header/footer structure was rebuilt.
- No legal text was translated; legal pages remain German-first for now.

## Test focus

- `order.html`: visual layout, date/time picker, captcha, confirmation modal and submit.
- Language switch on `about`, `flavours`, `pastryshop`, `week-special`, and `jobs`.
- Image display on desktop and mobile.
