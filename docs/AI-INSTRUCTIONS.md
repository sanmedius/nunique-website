# AI Instructions for NUNIQUE Website

This project is intentionally simple and AI-maintainable.

## Architecture

- The public website pages are static HTML files in the root folder.
- Do not convert the whole site to PHP.
- Do not introduce WordPress, CMS logic, React, Eleventy, Astro, Node build tools, or a database.
- The only backend code allowed is isolated form handling in `/backend/`.
- The original visual structure is preserved to avoid breaking the site.

## Current production files

Main pages:

- `index.html`
- `cakeshop.html`
- `flavours.html`
- `pastryshop.html`
- `week-special.html`
- `about.html`
- `order.html`
- `jobs.html`
- `impressum.html`
- `datenschutz.html`
- `agb.html`

Shared frontend files:

- `style.css`
- `atelier-iconic.css`
- `main.js`
- `images/`

Backend form files:

- `backend/config.php`
- `backend/send-order.php`

## Important rules for future AI edits

1. Preserve the current working file paths unless the user explicitly asks for a migration.
2. Do not aggressively move CSS, JS, or images without testing every page afterward.
3. Do not split `main.js` or CSS files unless the user specifically wants a full refactor and accepts the risk.
4. If changing the header, navigation, footer, cookie banner, or language toggle, update every HTML page consistently.
5. Keep the language system based on `data-de`, `data-en`, `data-de-html`, `data-en-html`, `data-de-ph`, and `data-en-ph`.
6. Keep the order form action as `backend/send-order.php`.
7. Keep PHP limited to the backend folder.
8. Do not store uploaded files publicly. The order backend should only validate temporary uploads and attach them to email.
9. Do not put mail credentials, API keys, or secrets in frontend JavaScript.
10. After changes, check browser console, page layout, image paths, CSS paths, JS paths, and order-form submission.

## Known deliberate compromise

The header/footer are repeated in static HTML pages. This is less elegant than templates, but it is easier for the user, their coder friend, and AI tools to understand and edit without a build step.

## Current strategic decisions

- Do not add a build system, framework or static-site generator unless explicitly requested later.
- Do not advertise NUNIQUE as a full-service caterer. It may support caterers and event partners with cakes and sweet pieces.
- Keep the public pages directly editable static HTML.
- PHP is allowed only inside `/backend/` for form submission.
- Prefer small, testable changes. Do not restructure CSS/JS aggressively.
- If shared header/footer changes are needed, use `shared-reference/` and update all marked shared blocks consistently.

