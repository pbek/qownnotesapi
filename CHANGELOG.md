# QOwnNotesAPI Change Log

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
