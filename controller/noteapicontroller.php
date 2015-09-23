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

use OC\Files\View;
use Exception;
use OCA\Files_Trashbin\Helper;
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
     * Gets all versions of a note
     *
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
        $versionsResults = array();

        if (is_array( $versions ) && (count($versions) > 0))
        {
            require_once __DIR__ . '/../3rdparty/finediff/finediff.php';

            $users_view = new View('/'.$uid);
            $currentData = $users_view->file_get_contents('files/' . $filename);

//            $previousData = $currentData;
//            $versions = array_reverse( $versions, true );

            foreach ($versions as $versionData)
            {
                // get timestamp of version
                $mtime = (int)$versionData["version"];

                // get filename of note version
                $versionFileName = 'files_versions/' . $filename . '.v' . $mtime;

                // load the data from the file
                $data = $users_view->file_get_contents($versionFileName);

                // calculate diff between versions
                $opcodes = \FineDiff::getDiffOpcodes($currentData, $data);
                $html = \FineDiff::renderDiffToHTMLFromOpcodes($currentData, $opcodes);

                $versionsResults[] = array(
                    "timestamp" => $mtime,
                    "humanReadableTimestamp" => $versionData["humanReadableTimestamp"],
                    "diffHtml" => $html,
                    "data" => $data,
                );

//                $previousData = $data;
            }

//            $versionsResults = array_reverse( $versionsResults );
        }

        return array(
            "file_name" => $source,
            "versions" => $versionsResults
        );
    }

    /**
     * Returns information about the ownCloud server
     *
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

    /**
     * Gets information about trashed notes
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return string
     */
    public function getTrashedNotes() {
        $dir = isset($_GET['dir']) ? (string)$_GET['dir'] : '';
        $sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
        $sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;
        $data = array();
        $filesInfo = array();

        // generate the file list
        try {
            $files = Helper::getTrashFiles("/", \OC::$server->getUserSession()->getUser()->getUID(), $sortAttribute, $sortDirection);
            $filesInfo = Helper::formatFileInfos($files);
        } catch (Exception $e) {

        }

        // only return notes in the $dir directory
        $resultFilesInfo = array();
        foreach($filesInfo as $fileInfo)
        {
            if (strpos($fileInfo["extraData"] . "/", $dir) === 0)
            {
                $resultFilesInfo[] = $fileInfo;
            }
        }

        $data['directory'] = $dir;
        $data['files'] = $resultFilesInfo;

        return $data;
    }
}
