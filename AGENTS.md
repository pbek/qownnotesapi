# AGENTS.md

This file contains information for automated tools and agents working with this codebase.

## Commit Messages

Use `<area>: <short imperative summary>`, with a lowercase area that matches the change. Check recent commits for the files you are changing to choose the area. For example, use `just: add recipe release-downloads` for a Just recipe and `ai: document commit message format` for an `AGENTS.md` update.

When referencing a GitHub issue, prefix the subject with `#<issue>`, as in `#58 release: update app for Nextcloud 34`.

## Version Number Location

The version number is located in `appinfo/info.xml` on line 11 within the `<version>` tag.

## Signing Script Exclusions

When updating the signing script `docker/nextcloud/sign-app.sh`, ensure that new paths or files that should not be included in the app deployment are excluded in the rsync command.
