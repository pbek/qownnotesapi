# Deploying to the app stores

## Nextcloud

### Prerequisites

- Copy your app certificate files to `./docker/nextcloud/certificates`

### Signing and releasing

- Make sure the version in `appinfo/info.xml` and the `CHANGELOG.md` are updated
- Sign the app with `cd docker && just sign-app`
    - You should now have a `qownnotesapi-nc.tar.gz` in your git directory
    - Check the content of the archive for unwanted files (you can exclude more files in
      `docker/nextcloud/sign-app.sh`)
- Commit and push your changes to GitHub
- Create a new release on [QOwnNotesAPI releases](https://github.com/pbek/qownnotesapi/releases)
  with the version like `22.5.0` as *Tag name* and *Release title* and the changelog text of the current
  release as *Release notes*
    - Alternatively you can push to the `release` branch and the GitHub action will create
      a draft release for you
    - You also need to upload `qownnotesapi-nc.tar.gz` to the release and get its url
      like `https://github.com/pbek/qownnotesapi/releases/download/22.5.0/qownnotesapi-nc.tar.gz`
- Take the text from *Signature for your app archive*, which was printed by the sign-app command and
  release the app at [Upload app release](https://apps.nextcloud.com/developer/apps/releases/new)
    - You need the download link to `qownnotesapi-nc.tar.gz` from the GitHub release
- The new version should then appear on the [QOwnNotesAPI store page](https://apps.nextcloud.com/apps/qownnotesapi)

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
