# Project Structure

This is a static website with one isolated PHP backend for the order form.

```text
/
├── index.html
├── cakeshop.html
├── flavours.html
├── pastryshop.html
├── week-special.html
├── about.html
├── order.html
├── jobs.html
├── impressum.html
├── datenschutz.html
├── agb.html
├── style.css
├── atelier-iconic.css
├── main.js
├── images/
├── backend/
│   ├── config.php
│   ├── send-order.php
│   └── .htaccess
├── docs/
├── forms/
├── robots.txt
└── .htaccess
```

## Why this structure?

The previous attempted restructure moved too much at once and broke the site. This version preserves the working frontend paths and only adds a clearer backend and documentation.

## Backend

`order.html` submits to `backend/send-order.php`.

Edit `backend/config.php` for:

- bakery email
- sender email
- phone number
- site URL
- upload limits
- rate limit


## Archive folder

`archive-unused/` contains files that were not referenced by the public pages at the time of cleanup. They are kept there instead of being deleted immediately, so they can be restored if needed.
