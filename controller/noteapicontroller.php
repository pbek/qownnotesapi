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
     * @param string $fileName
     * @return array
     */
    public function getAllVersions($fileName) {
        return array("file_name" => $fileName);
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
