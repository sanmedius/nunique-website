# Editing Guide

## To edit text

Most bilingual text uses attributes like:

```html
<span data-de="Deutscher Text" data-en="English text">Deutscher Text</span>
```

Change both `data-de` and `data-en`, and keep the visible German text consistent with `data-de`.

## To edit placeholders

```html
<input data-de-ph="Deutscher Platzhalter" data-en-ph="English placeholder">
```

## To edit order-form emails

Edit `backend/config.php` first.

## To edit form processing

Edit `backend/send-order.php` only. Do not move form logic into frontend JavaScript.

## Before upload

- Do not upload old ZIP files into the public web root.
- Do not upload editor files unless intentionally used and protected.
- Test `/order` after every backend change.
