<?php
/**
 * ownCloud - qownnotesapi
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Patrizio Bekerle <patrizio@bekerle.com>
 * @copyright Patrizio Bekerle 2015
 */

namespace OCA\QOwnNotesAPI\AppInfo;

//use OCP\API;

// $application = new Application();

//API::register('get',
//    '/cloud/capabilities',
//    array('OCA\QOwnNotesAPI\Capabilities', 'getCapabilities'),
//    'qownnotesapi',
//    API::USER_AUTH);

// Register with the capabilities API
//\OCP\API::register('get', '/cloud/capabilities', array('OCA\QOwnNotesAPI\Capabilities', 'getCapabilities'), 'qownnotesapi', \OCP\API::USER_AUTH);


return ['routes' => [
    // note api
    ['name' => 'note_api#get_all_versions', 'url' => '/api/v1/note/versions', 'verb' => 'GET'],
    ['name' => 'note_api#get_app_info', 'url' => '/api/v1/note/app_info', 'verb' => 'GET'],
    ['name' => 'note_api#get_trashed_notes', 'url' => '/api/v1/note/trashed', 'verb' => 'GET'],
    ['name' => 'note_api#restore_trashed_note', 'url' => '/api/v1/note/restore_trashed', 'verb' => 'GET'],
]];

//API::register('get',
//	'/apps/qownnotesapi/api/v1/versions',
//	array('\OCA\QOwnNotesAPI\API\QOwnNotes', 'getAllVersions'),
//	'files_sharing');
//
// Register with the capabilities API
