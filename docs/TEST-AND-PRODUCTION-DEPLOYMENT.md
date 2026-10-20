# NUNIQUE: test deployment and production deployment

Use this guide every time you change the website. A GitHub push saves the change in
the repository. It does **not** change either website until you manually run one of
the deployment workflows.

## The two website destinations

| Purpose | GitHub Actions workflow | Website to check | STRATO account |
| --- | --- | --- | --- |
| Test | **Deploy test website to STRATO** | `https://test.cakes-coffee.de` | Dedicated account rooted at `/test-website` |
| Live | **Deploy live website to STRATO** | `https://munichcakes.de` | Dedicated account rooted at the live website folder |

The test copy deliberately keeps production canonical URLs and is marked as
non-indexable. This prevents Google from treating it as a competing website.

## Normal safe process

1. Make the website change in VS Code.
2. Commit and push it to GitHub `main`.
3. Open the repository on GitHub and choose **Actions**.
4. Select **Deploy test website to STRATO** in the left column.
5. Click **Run workflow**.
6. Leave the branch as `main`, then click the green **Run workflow** button.
7. Wait until the workflow has a green tick. Do not treat the GitHub push itself as
   a deployment.
8. Open `https://test.cakes-coffee.de` in an incognito/private browser window and
   test the changed page, mobile layout, navigation, and order form.
9. If the order form is tested, use obviously fake test details and write `TEST` in
   the message. It can send a real email notification.
10. If everything is correct, do not make another commit before the live deployment.
    The live workflow deploys the latest version of `main`, so this ensures the
    tested version and live version are identical.
11. In GitHub **Actions**, select **Deploy live website to STRATO**.
12. Click **Run workflow**.
13. Tick **Confirm that this deployment should update munichcakes.de**.
14. Click the green **Run workflow** button and wait for the green tick.
15. Open `https://munichcakes.de` in an incognito/private browser window and check
    the changed page again.

## If a change is not ready

Do not run **Deploy live website to STRATO**. Make the correction, commit and push
it, then repeat the test workflow. The live website remains unchanged until the
live workflow is manually confirmed.

## Important safety rules

- Never put Brevo, Turnstile, STRATO passwords, or private configuration files in
  Git or in this guide.
- Do not edit server folders manually after a GitHub deployment unless there is an
  urgent recovery reason. A later deployment can overwrite manual server changes.
- The workflow named **Verify live STRATO SFTP folder** only lists the live folder.
  It does not upload anything.
- The workflow named **Show STRATO SSH host fingerprint** only displays the public
  SSH fingerprint. It does not upload anything.

## Quick decision

- Want to check a change safely? Run **Deploy test website to STRATO**.
- Want visitors on `munichcakes.de` to receive the already tested change? Run
  **Deploy live website to STRATO**, tick the production confirmation, and run it.
