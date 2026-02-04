# AGENTS.md

This file contains information for automated tools and agents working with this codebase.

## Version Number Location

The version number is located in `appinfo/info.xml` on line 11 within the `<version>` tag.

## Signing Script Exclusions

When updating the signing script `docker/nextcloud/sign-app.sh`, ensure that new paths or files that should not be included in the app deployment are excluded in the rsync command.
