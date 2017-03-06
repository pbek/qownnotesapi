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
use OCA\Files_Trashbin\Trashbin;

class NoteApiController extends ApiController {

    var $user;

    /**
     * @param string $AppName
     * @param string $userId
     * @param IRequest $request
     */
    public function __construct($AppName,
                                $userId,
                                IRequest $request) {
        $this->user = $userId;
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
        $source = $this->request->getParam( "file_name", "" );
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
        $appManager = \OC::$server->getAppManager();
        $versionsAppEnabled = $appManager->isEnabledForUser('files_versions');
        $trashAppEnabled = $appManager->isEnabledForUser('files_trashbin');
        $notesPathExists = false;
        $notesPath = $this->request->getParam( "notes_path", "" );

        // check if notes path exists
        if ($notesPath !== "")
        {
            $notesPath = "/files" . (string)$notesPath;
            $view = new \OC\Files\View('/' . $this->user);
            $notesPathExists = $view->is_dir($notesPath);
        }

        return [
            "versions_app" => $versionsAppEnabled,
            "trash_app" => $trashAppEnabled,
            "versioning" => true,
            "app_version" => \OC::$server->getConfig()->getAppValue('qownnotesapi', 'installed_version'),
            "server_version" => \OC::$server->getSystemConfig()->getValue('version'),
            "notes_path_exists" => $notesPathExists,
        ];
    }

    /**
     * Gets information about trashed notes
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @CORS
     *
     * @return string
     */
    public function getTrashedNotes() {
        $dir = $this->request->getParam( "dir", "" );
        $customFileExtensions = $this->request->getParam( "extensions", array() );

        if (!is_array($customFileExtensions)) {
            $customFileExtensions = array();
        }

        $noteFileExtensions = array_merge( array("md", "txt"), $customFileExtensions);

        // remove leading "/"
        if ( substr( $dir, 0, 1 ) === "/" )
        {
            $dir = substr( $dir, 1 );
        }

        // remove trailing "/"
        if ( substr( $dir, -1 ) === "/" )
        {
            $dir = substr( $dir, 0, -1 );
        }

        $sortAttribute = $this->request->getParam( "sort", "mtime" );
        $sortDirectionParam = $this->request->getParam( "sortdirection", "" );
        $sortDirection = ( $sortDirectionParam !== "" ) ? ( $sortDirectionParam === 'desc' ) : true;
        $filesInfo = array();

        // generate the file list
        try {
            $files = Helper::getTrashFiles("/", $this->user, $sortAttribute, $sortDirection);
            $filesInfo = Helper::formatFileInfos($files);
        } catch (Exception $e) {

        }

        // only return notes (with extension ".txt", ".md" and the custom extensions) in the $dir directory
        $resultFilesInfo = array();
        foreach($filesInfo as $fileInfo)
        {
            $pathParts = pathinfo($fileInfo["name"]);
            $extension = isset($pathParts["extension"]) ? $pathParts["extension"] : "";

            // if $fileInfo["extraData"] is not set we will have to show the note files from all folders in QOwnNotes
            $isInDir = isset($fileInfo["extraData"]) ?
                (strpos($fileInfo["extraData"], $dir . "/" . $fileInfo["name"]) === 0) : true;
            $isNoteFile = in_array($extension, $noteFileExtensions);

            if ($isInDir && $isNoteFile)
            {
                $timestamp = (int) ($fileInfo["mtime"] / 1000);
                $fileName = '/files_trashbin/files/' . $fileInfo["name"] . ".d$timestamp";

                $view = new \OC\Files\View('/' . $this->user);
                $data = "";

                // load the file data
                $handle = $view->fopen($fileName, 'rb');
                if ($handle) {
                    $chunkSize = 8192; // 8 kB chunks
                    while (!feof($handle)) {
                        $data .= fread($handle, $chunkSize);
                    }
                }

                $dateString = isset($fileInfo["date"]) ? $fileInfo["date"] : date("Y-m-d H:i:s", $timestamp);

                $resultFilesInfo[] = [
                    "noteName" => $pathParts["filename"],
                    "fileName" => $fileInfo["name"],
                    "timestamp" => $timestamp,
                    "dateString" => $dateString,
                    "data" => $data,
                ];
            }
        }

        $data = array();
        $data['directory'] = $dir;
        $data['notes'] = $resultFilesInfo;

        return $data;
    }

    /**
     * Restores a trashed note
     *
     * We try to mimic undelete.php to get all versions restored too.
     * @see owncloud/core/apps/files_trashbin/ajax/undelete.php
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @CORS
     *
     * @return string
     */
    public function restoreTrashedNote() {
        $filename = $this->request->getParam( "file_name" );
        $timestamp = (int) $this->request->getParam( "timestamp" );

        $path = $filename . ".d$timestamp";
        $pathParts = pathinfo( $path );
        $path = "//" . $pathParts['basename'];

        $pathParts = pathinfo( $filename );
        $filename = $pathParts['basename'];

        $restoreResult = Trashbin::restore($path, $filename, $timestamp);

        $data = array();
        $data['result'] = $restoreResult;
        $data['path'] = $path;
        $data['filename'] = $filename;

        return $data;
    }
}
