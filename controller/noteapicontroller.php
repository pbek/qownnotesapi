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

namespace OCA\QOwnNotesAPI\Controller;

use \OCP\IRequest;
use \OCP\AppFramework\ApiController;
use \OCP\AppFramework\Http;
use OCA\Files_Versions\Storage;

class NoteApiController extends ApiController {

    //use JSONHttpError;

    public function __construct($AppName,
                                IRequest $request) {
        parent::__construct($AppName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @CORS
     *
     * @return array
     */
    public function getAllVersions() {
        $source = (string) $_GET["file_name"];
        list ($uid, $filename) = Storage::getUidAndFilename($source);
        $versions = Storage::getVersions($uid, $filename, $source);

        return array(
            "file_name" => $source,
            "versions" => $versions
        );
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @CORS
     *
     * @return string
     */
    public function getAppInfo() {
        return [
            "versioning" => true,
            "app_version" => \OC::$server->getConfig()->getAppValue('qownnotesapi', 'installed_version'),
            "server_version" => \OC::$server->getSystemConfig()->getValue('version'),
        ];
    }
}
