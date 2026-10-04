# Changelog
## Stability Update 1 — SEO, CSS variables, backend config

- Added absolute canonical URLs to all public HTML pages.
- Fixed the malformed meta description in `week-special.html`.
- Consolidated the global `:root` colour variables in `atelier-iconic.css` into one clear block.
- Replaced legacy short variable names such as `--pink` / `--teal` with the clearer `--brand-*` naming system.
- Kept responsive `:root` overrides for header height only where needed.
- Added `mail_subject_prefix` to `backend/config.php`.
- Moved currently unreferenced image files into `archive-unused/images/` instead of deleting them.
- No layout logic, order-form logic, main JavaScript, or PHP validation flow was intentionally changed.

## Stability Update 2

- Extracted the large inline CSS block from `order.html` into `order.css` without changing order form behavior.
- Added `order.css` as a separate stylesheet so the browser can cache it and AI/code review can find it easily.
- Fixed several malformed or missing `data-de` / `data-en` wrappers on marketing pages, cookie banners and review cards.
- Added safer image performance attributes (`width`, `height`, `decoding`, and lazy loading where appropriate) to local images.
- Left PHP validation, JavaScript logic, layout structure, and form behavior unchanged.
- Legal pages remain German-first; decide separately whether to translate them or hide the language toggle there.


## Stability Update 3

- Preserved German-only legal content as requested.
- Improved non-legal bilingual UI coverage.
- Added runtime switching for alt text, titles, aria labels, meta descriptions, Open Graph descriptions, and lightbox captions.
- Added bilingual SEO titles and meta descriptions.
- Added Open Graph metadata to pages.
- Added Bakery/LocalBusiness structured data to the homepage.
- Added shared header/footer markers for easier AI-assisted maintenance.
- Consolidated `style.css` root variables into one top-level root block.
- Added `docs/JS-AUDIT.md`, `docs/CSS-AUDIT.md`, `docs/SEO-ANALYSIS.md`, and `docs/MAINTENANCE-MAP.md`.
- Did not change PHP form submission logic.
- Did not restructure folders or split CSS/JS further.

## Stability Update 4 — Order form submission hardening

- Added client-side validation for order-specific hidden fields before showing the confirmation modal:
  - pickup date
  - pickup time
  - at least one flavour
  - size / custom size
  - captcha
  - terms/privacy confirmation
- Added a visible validation summary on the order page.
- Added backend error codes to the PHP result page so failed submissions can be diagnosed.
- Added server-side logging via PHP error_log for order-form failure codes.
- Improved PHP mail delivery compatibility by adding a valid envelope sender parameter (`-f`) when using PHP `mail()` on shared hosting.
- No layout redesign and no SEO/content changes were made in this update.

## Stability Update 5 — maintenance and SEO focus

- Kept the site as static HTML/CSS/JS; no Eleventy/11ty or build tools added.
- Added `shared-reference/` with header/footer reference copies for easier AI-assisted maintenance.
- Added documentation for no-build-system decision, shared blocks, CSS cleanup, JS cleanup and SEO focus.
- Removed full-service catering wording from home SEO metadata and structured data.
- Added a `For Caterers & Event Partners` section to `cakeshop.html` without claiming catering services.
- Added `Caterer/Eventpartner` as an order-form occasion option.
- Left backend, order submission logic, main navigation, and core layout behavior unchanged.

