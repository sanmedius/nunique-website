# Production domain configuration

The production website domain is stored in one place:

`scripts/site-config.json`

The GitHub deployment runs `scripts/apply-site-domain.ps1` before uploading the
website. The script synchronizes:

- canonical links on every root HTML page;
- `og:url` social sharing metadata on every root HTML page;
- `site_url` in `backend/config.php`.

The SMTP client derives its `EHLO` hostname from `site_url`, so it does not have
a separate hardcoded website domain.

## Changing the domain in the future

1. Change `productionDomain` in `scripts/site-config.json`. Use an HTTPS address
   without a trailing slash or page path.
2. Commit and push the setting. GitHub applies it automatically before the
   deployment upload.
3. To synchronize the files locally as well, open PowerShell in the website
   folder and run:

   ```powershell
   powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\apply-site-domain.ps1
   ```

Changing this setting does not configure DNS, HTTPS certificates, redirects,
or Search Console. Those services must also be updated during a real domain
move.

The contact address `info@designcakes.de` is intentionally independent of this
setting and remains unchanged.
