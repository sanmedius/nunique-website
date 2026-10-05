# Secure STRATO deployment

## Private server configuration

The public test site is `/test-website`. Create a sibling directory named `/private`
through SFTP and copy `docs/nunique-config.example.php` to:

```text
/private/nunique-config.php
```

Fill in the STRATO mailbox password and both Cloudflare Turnstile keys. The completed
file must exist only on STRATO and in a protected local password store. Never commit
or upload it through GitHub Actions.

After the private file works, delete the former public copy if it exists:

```text
/test-website/backend/config.local.php
```

Production PHP never reads that public path. Local `php -S` development still uses
`backend/config.local.php`.

## Turnstile

Create a Cloudflare Turnstile widget in Managed mode and allow these hostnames:

```text
test.cakes-coffee.de
cakes-coffee.de
www.cakes-coffee.de
```

Put the public site key and private secret key in `/private/nunique-config.php`.
The secret key must never appear in HTML, JavaScript, Git, or GitHub Actions.

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

## Deployment boundaries

The workflow:

- uses SFTP over port 22;
- pins `actions/checkout` to an immutable commit;
- grants the GitHub token read-only repository access;
- verifies the STRATO SSH host key before login;
- excludes private configuration, documentation, archives, and development files;
- runs only when started manually.
