# Secure STRATO deployment

## Private server configuration

The public test site is `/test-website`. Create a sibling directory named `/private`
through SFTP and copy `docs/nunique-config.example.php` to:

```text
/private/nunique-config.php
```

Fill in the Brevo API key and the Cloudflare Turnstile secret key. The completed file
must exist only on STRATO and in a protected local password store. Never commit or
upload it through GitHub Actions.

After the private file works, delete the former public copy if it exists:

```text
/test-website/backend/config.local.php
```

Production PHP never reads that public path. Local `php -S` development still uses
`backend/config.local.php`.

## GitHub environment secrets

The `test` GitHub environment needs:

- `STRATO_SFTP_PASSWORD`: password of the dedicated deployment-only SFTP account
- `STRATO_SSH_FINGERPRINT`: the `SHA256:...` host-key fingerprint shown by FileZilla
  on the first connection to `58146001.ssh.w1.strato.hosting`

Compare the displayed fingerprint with a second trusted connection before saving it.
The workflow scans the presented host key and refuses to send the SFTP password unless
the SHA-256 fingerprint matches.

Restrict the SFTP account to the website directory and give repository administration
access only to people who are allowed to deploy. Protect the GitHub `test` environment
so deployments can run only from `main`.

## Live deployment

The repository also contains a separate manual workflow named **Deploy live website to
STRATO**. It uses GitHub's `production` environment and never runs automatically.

Before using it, create a separate STRATO SFTP account that is restricted to the
actual `/munichcakes.de` website directory. Its SFTP root must show the website files
directly, not a parent folder containing `munichcakes.de`. Confirm that in FileZilla
before adding its credentials to GitHub.

Add these secrets to the GitHub `production` environment:

- `STRATO_LIVE_SFTP_USERNAME`: the restricted live-site SFTP username
- `STRATO_LIVE_SFTP_PASSWORD`: its password
- `STRATO_SSH_FINGERPRINT`: the verified `SHA256:...` fingerprint for the STRATO host

The live workflow applies `scripts/site-config.json`, which is already set to
`https://munichcakes.de`, before uploading. It has a confirmation checkbox and is
safe to prepare before it is ever run. Configure a required reviewer for the
`production` environment in GitHub before the first live deployment.

## Deployment boundaries

The workflow:

- uses SFTP over port 22;
- pins `actions/checkout` to an immutable commit;
- grants the GitHub token read-only repository access;
- verifies the STRATO SSH host key before login;
- excludes private configuration, documentation, archives, and development files;
- runs only when started manually.
