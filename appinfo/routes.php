<?php
/**
 * Nextcloud / ownCloud - QOwnNotesAPI
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Patrizio Bekerle <patrizio@bekerle.com>
 * @copyright Patrizio Bekerle 2015-2020
 */

namespace OCA\QOwnNotesAPI\AppInfo;

return ['routes' => [
    // note api
    ['name' => 'note_api#get_all_versions', 'url' => '/api/v1/note/versions', 'verb' => 'GET'],
    ['name' => 'note_api#get_app_info', 'url' => '/api/v1/note/app_info', 'verb' => 'GET'],
    ['name' => 'note_api#get_trashed_notes', 'url' => '/api/v1/note/trashed', 'verb' => 'GET'],
    ['name' => 'note_api#restore_trashed_note', 'url' => '/api/v1/note/restore_trashed', 'verb' => 'GET'],
]];
