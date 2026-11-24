# Deploying to the app stores

## Nextcloud

### One-time GitHub setup

Create these environments under **Settings → Environments**:

| Environment          | Secrets                             | Purpose                                                                                         |
| -------------------- | ----------------------------------- | ----------------------------------------------------------------------------------------------- |
| `release-signing`    | `APP_PRIVATE_KEY`, `APP_PUBLIC_CRT` | Full PEM contents of the existing `qownnotesapi.key` and Nextcloud-issued `qownnotesapi.crt`    |
| `release-publishing` | `APPSTORE_TOKEN`                    | API token from [Nextcloud App Store account settings](https://apps.nextcloud.com/account/token) |

Use the credentials for **`qownnotesapi`**, not the successor's `qownnotes` signing
certificate. Restrict both environments to the `release` branch, configure required
reviewers where your GitHub plan supports them, and protect that branch against
unreviewed changes and force pushes. Keep the private key and token out of the repository,
logs, and release artifacts. The workflow needs a public GitHub release because the
App Store fetches the archive without GitHub credentials.

### Automated release

1. Set a **new** version (`YY.M.patch`) in `appinfo/info.xml` and add its nonempty notes
   as the latest `## <version>` section in `CHANGELOG.md`. Run
   `python3 scripts/release_metadata.py` locally.
2. Push the reviewed commit to `release`. Only pushes to this branch trigger the
   [release workflow](../.github/workflows/create_release.yml). It validates metadata,
   refuses an existing tag, and runs the Nextcloud VM tests before accessing signing secrets.
3. Approve signing when prompted. CI packages only the files in
   [`release-files.txt`](../release-files.txt), signs them in a disposable Nextcloud
   container, verifies the archive signature and the extracted app's integrity, and
   tests the installed app's `app_info` endpoint.
4. Approve publishing when prompted. The workflow publishes GitHub release `v<version>`
   at the exact commit with changelog notes, `qownnotesapi-nc.tar.gz`, its `.sig`, and
   its `.sha256`. The store job checks that the publicly downloadable archive matches
   the signed bytes, then submits its URL and signature to the
   [Nextcloud App Store](https://apps.nextcloud.com/apps/qownnotesapi).

If signing fails, correct the environment configuration and rerun the failed jobs.
If GitHub publishing or store submission fails, choose **Re-run failed jobs** while
the signed Actions artifact is still retained (14 days). Do not rebuild or re-sign an
archive that has already been uploaded. Existing tags and published assets are not
overwritten. If store submission fails, the GitHub release stays published. After the
Actions artifact expires, download the archive and `.sig` from GitHub and submit them
with `python3 scripts/publish-release.py --repo pbek/qownnotesapi appstore --version <version> --archive <path-to-qownnotesapi-nc.tar.gz>`
using a securely supplied `APPSTORE_TOKEN`.

### Local fallback

Copy the app certificate files to `docker/nextcloud/certificates`, run
`cd docker && just sign-app`, and inspect the resulting `qownnotesapi-nc.tar.gz`.
For a fully manual release, don't also push that release to the `release` branch:
publish the archive on GitHub under `v<version>` with the matching changelog notes,
then submit its public download URL and the printed signature via
[Upload app release](https://apps.nextcloud.com/developer/apps/releases/new).

## ownCloud

### Prerequisites

- Copy your app certificate files to `./docker/owncloud/certificates`

### Signing and releasing

- Make sure the version in `appinfo/info.xml` and the `CHANGELOG.md` are updated
- Sign the app with `cd docker && just sign-app-owncloud`
  - You should now have a `qownnotesapi-oc.tar.gz` in your git directory
  - Check the content of the archive for unwanted files (you can exclude more files in
    `docker/owncloud/sign-app.sh`)
- Upload `qownnotesapi-oc.tar.gz` on [ownCloud producs](https://marketplace.owncloud.com/account/products)
- Publish app on [qownnotesapi](https://marketplace.owncloud.com/account/edit/qownnotesapi)!
