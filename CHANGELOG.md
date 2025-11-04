# QOwnNotesAPI Change Log

## Next

- Migrated to from PHP7 to PHP8 annotations (for [#53](https://github.com/pbek/qownnotesapi/issues/53))
- Migrated the use of the Nextcloud API to Nextcloud 29+ standards
  - Increased min-version to Nextcloud 29

## 25.8.0

- Enabled and tested app for Nextcloud 32 (for [#52](https://github.com/pbek/qownnotesapi/issues/52))

## 25.2.0

- Enabled and tested app for Nextcloud 31 (for [#51](https://github.com/pbek/qownnotesapi/issues/51))

## 24.11.0

- The versioning API was fixed for Nextcloud 30 (for [#50](https://github.com/pbek/qownnotesapi/issues/50))

## 24.9.0

- Enabled and tested app for Nextcloud 30 (for [#49](https://github.com/pbek/qownnotesapi/issues/49))
  - There were troubles with the version API that didn't always happen when trying to access the versions of a file:
    `Call to a member function getOwner() on null in file '/var/www/html/lib/private/Files/Filesystem.php' line 728`
    - It's unclear why this happens, but it seems to be a problem with the Nextcloud server and not the app

## 24.4.0

- enabled and tested app for Nextcloud 29 (for [#48](https://github.com/pbek/qownnotesapi/issues/48))

## 23.12.0

- enabled and tested app for Nextcloud 28 (for [#46](https://github.com/pbek/qownnotesapi/issues/46))
- updated the deprecated variable `$AppName` to `$appName`

## 23.6.0

- re-did the application structure for Nextcloud 27 (for [#43](https://github.com/pbek/qownnotesapi/issues/43))
- updated and tested app for Nextcloud 27 (for [#44](https://github.com/pbek/qownnotesapi/issues/44))
  - the min-version was raised to 22

## 23.3.0

- enabled and tested app for Nextcloud 26 (for [#42](https://github.com/pbek/qownnotesapi/issues/42))

## 22.10.0

- the code got a cleanup
- enabled and tested app for Nextcloud 25 (for [#41](https://github.com/pbek/qownnotesapi/issues/41))

## 22.5.0

- errors for not found files in the version API are now caught
- enabled and tested app for Nextcloud 24 (for [#38](https://github.com/pbek/qownnotesapi/issues/38))

## 21.12.0

- enabled and tested app for Nextcloud 23 (for [#36](https://github.com/pbek/qownnotesapi/issues/36))

## 21.7.0

- enabled and tested app for Nextcloud 22

## 21.3.0

- added a workaround for the failing username detection in ownCloud 10.3+
  (for [#1725](https://github.com/pbek/QOwnNotes/issues/1725))

## 20.9.0

- enabled app for Nextcloud 20/21

## 20.1.0

- enabled and tested app for Nextcloud 18

## 19.9.0

- enabled and tested app for Nextcloud 17

## 19.4.0

- enabled and tested app for Nextcloud 16

## 19.1.0

- enabled app for all minor versions of ownCloud 10

## 18.11.0

- enabled and tested app for Nextcloud 15

## 18.8.0

- enabled and tested app for Nextcloud 14

## 17.7.0

- changes to get the app into the ownCloud Marketplace

## 17.5.0

- enabled and tested app for ownCloud 10 and Nextcloud 12

## 17.3.0

- fixed the time-output of the trashed notes api
- fixed some PHP warnings in the log

## 16.12.0

- changes to make it ready for the Nextcloud app store

## 16.09.0

- fixed a problem when the ownCloud or Nextcloud server didn't provide the path
  of a trashed note and thus no note was shown in the trash dialog of QOwnNotes
- increased `max-version` to `9.2`
- switched to rolling release version numbers

## 0.4.4

- added support for custom note file extensions

## 0.4.3

- fixed a warning on the app page in ownCloud 9.0

## 0.4.2

- added markdown (`.md`) file support to the trash restoration

## 0.4.1

- added check if notes path exists to app info api

## 0.4

- added api to restore trashed notes on the server
- fixed a bug in the trashed notes api were also notes from children folders of the notes folder would be viewed

## 0.3.2

- fixed a bug in the trashed notes api when the storage path is not in the ownCloud server path

## 0.3.1

- added checks if core trash bin and versions apps are enabled for the user
- app compliance changes

## 0.3

- added trashed notes api

## 0.2

- added app info api
- added versioning api

## 0.1

- first release
