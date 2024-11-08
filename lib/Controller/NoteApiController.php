<?php

declare(strict_types=1);
/**
 * Nextcloud / ownCloud - QOwnNotesAPI.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Patrizio Bekerle <patrizio@bekerle.com>
 * @copyright Patrizio Bekerle 2015-2020
 */

namespace OCA\QOwnNotesAPI\Controller;

use Exception;
use OC\Files\Filesystem;
use OC\Files\View;
use OC\User\NoUserException;
use OC_User;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Trashbin;
use OCA\Files_Versions\Storage;
use OCP\AppFramework\ApiController;
use OCP\Files\NotFoundException;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Util;

class NoteApiController extends ApiController
{
    protected $user;

    /**
     * @param string $appName
     * @param string $userId
     */
    public function __construct($appName,
                                $userId,
                                IRequest $request)
    {
        // For some reason $userId is null on ownCloud 10.3+ anymore
        // https://github.com/pbek/QOwnNotes/issues/1725
        $this->user = $userId ? $userId : $_SERVER['PHP_AUTH_USER'];
        parent::__construct($appName, $request);
    }

    /**
     * Gets all versions of a note.
     *
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     *
     * @CORS
     *
     * @return array
     *
     * @throws \OCP\Lock\LockedException
     * @throws \OC\User\NoUserException
     */
    public function getAllVersions()
    {
        $source = $this->request->getParam('file_name', '');
        $errorMessages = [];
        $versionsResults = [];

        try {
            // Make sure the view is initialized and use our own implementation of getUidAndFilename to prevent issues in Nextcloud 30
            // See https://github.com/pbek/qownnotesapi/issues/50
            Filesystem::getView();
            [$uid, $filename] = self::getUidAndFilename($source);

            $versions = Storage::getVersions($uid, $filename, $source);

            if (is_array($versions) && (count($versions) > 0)) {
                require_once __DIR__.'/../../3rdparty/finediff/finediff.php';

                $users_view = new View('/'.$uid);
                $currentData = $users_view->file_get_contents('files/'.$filename);

                foreach ($versions as $versionData) {
                    // get timestamp of version
                    $mtime = (int) $versionData['version'];

                    // get filename of note version
                    $versionFileName = 'files_versions/'.$filename.'.v'.$mtime;

                    // load the data from the file
                    $data = $users_view->file_get_contents($versionFileName);

                    // calculate diff between versions
                    $opcodes = \FineDiff::getDiffOpcodes($currentData, $data);
                    $html = \FineDiff::renderDiffToHTMLFromOpcodes($currentData, $opcodes);

                    $versionsResults[] = [
                        'timestamp' => $mtime,
                        'humanReadableTimestamp' => $versionData['humanReadableTimestamp'],
                        'diffHtml' => $html,
                        'data' => $data,
                    ];
                }
            }
        } catch (\OCP\Files\NotFoundException $exception) {
            // Requested file was not found, silently fail (for now)
            $errorMessages[] = 'Requested file was not found!';
        } catch (Exception $exception) {
            $errorMessages[] = 'An error happened: ' . $exception->getMessage();
        }

        return [
            'file_name' => $source,
            'versions' => $versionsResults,
            'error_messages' => $errorMessages,
        ];
    }

    /**
     * Get the UID of the owner of the file and the path to the file relative to
     * owners files folder
     * This is a copy of \OCA\Files_Versions\Storage::getUidAndFilename
     *
     * @param string $filename
     * @return array
     * @throws NoUserException
     */
    protected static function getUidAndFilename($filename) {
        $uid = Filesystem::getOwner($filename);
        $userManager = \OC::$server->get(IUserManager::class);
        // if the user with the UID doesn't exists, e.g. because the UID points
        // to a remote user with a federated cloud ID we use the current logged-in
        // user. We need a valid local user to create the versions
        if (!$userManager->userExists($uid)) {
            $uid = OC_User::getUser();
        }
        Filesystem::initMountPoints($uid);
        if ($uid !== OC_User::getUser()) {
            $info = Filesystem::getFileInfo($filename);
            $ownerView = new View('/' . $uid . '/files');
            try {
                $filename = $ownerView->getPath($info['fileid']);
                // make sure that the file name doesn't end with a trailing slash
                // can for example happen single files shared across servers
                $filename = rtrim($filename, '/');
            } catch (NotFoundException $e) {
                $filename = null;
            }
        }
        return [$uid, $filename];
    }

    /**
     * Returns information about the ownCloud server.
     *
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     *
     * @CORS
     *
     * @return string|array
     *
     * @throws Exception
     */
    public function getAppInfo()
    {
        $appManager = \OC::$server->getAppManager();
        $versionsAppEnabled = $appManager->isEnabledForUser('files_versions');
        $trashAppEnabled = $appManager->isEnabledForUser('files_trashbin');
        $notesPathExists = false;
        $notesPath = $this->request->getParam('notes_path', '');

        // check if notes path exists
        if ($notesPath !== '') {
            $notesPath = '/files'.(string) $notesPath;
            $view = new \OC\Files\View('/'.$this->user);
            $notesPathExists = $view->is_dir($notesPath);
        }

        return [
            'user' => $this->user,
            'versions_app' => $versionsAppEnabled,
            'trash_app' => $trashAppEnabled,
            'versioning' => true,
            'app_version' => \OC::$server->getConfig()->getAppValue('qownnotesapi', 'installed_version'),
            'server_version' => \OC::$server->getSystemConfig()->getValue('version'),
            'notes_path_exists' => $notesPathExists,
        ];
    }

    /**
     * Gets information about trashed notes.
     *
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     *
     * @CORS
     *
     * @return string|array
     *
     * @throws \OCP\Lock\LockedException
     */
    public function getTrashedNotes()
    {
        $dir = $this->request->getParam('dir', '');
        $customFileExtensions = $this->request->getParam('extensions', []);

        if (!is_array($customFileExtensions)) {
            $customFileExtensions = [];
        }

        $noteFileExtensions = array_merge(['md', 'txt'], $customFileExtensions);

        // remove leading "/"
        if (substr($dir, 0, 1) === '/') {
            $dir = substr($dir, 1);
        }

        // remove trailing "/"
        if (substr($dir, -1) === '/') {
            $dir = substr($dir, 0, -1);
        }

        $sortAttribute = $this->request->getParam('sort', 'mtime');
        $sortDirectionParam = $this->request->getParam('sortdirection', '');
        $sortDirection = ($sortDirectionParam !== '') ? ($sortDirectionParam === 'desc') : true;
        $filesInfo = [];

        // generate the file list
        try {
            $files = Helper::getTrashFiles('/', $this->user, $sortAttribute, $sortDirection);
            $filesInfo = Helper::formatFileInfos($files);
        } catch (Exception $e) {
        }

        // only return notes (with extension ".txt", ".md" and the custom extensions) in the $dir directory
        $resultFilesInfo = [];
        foreach ($filesInfo as $fileInfo) {
            $pathParts = pathinfo($fileInfo['name']);
            $extension = $pathParts['extension'] ?? '';

            // if $fileInfo["extraData"] is not set we will have to show the note files from all folders in QOwnNotes
            $isInDir = isset($fileInfo['extraData']) ?
                (strpos($fileInfo['extraData'], $dir.'/'.$fileInfo['name']) === 0) : true;
            $isNoteFile = in_array($extension, $noteFileExtensions, true);

            if ($isInDir && $isNoteFile) {
                $timestamp = (int) ($fileInfo['mtime'] / 1000);
                $fileName = '/files_trashbin/files/'.$fileInfo['name'].".d$timestamp";

                $view = new \OC\Files\View('/'.$this->user);
                $data = '';

                // load the file data
                $handle = $view->fopen($fileName, 'rb');
                if ($handle) {
                    $chunkSize = 8192; // 8 kB chunks
                    while (!feof($handle)) {
                        $data .= fread($handle, $chunkSize);
                    }
                }

                $dateString = $fileInfo['date'] ?? date('Y-m-d H:i:s', $timestamp);

                $resultFilesInfo[] = [
                    'noteName' => $pathParts['filename'],
                    'fileName' => $fileInfo['name'],
                    'timestamp' => $timestamp,
                    'dateString' => $dateString,
                    'data' => $data,
                ];
            }
        }

        $data = [];
        $data['directory'] = $dir;
        $data['notes'] = $resultFilesInfo;

        return $data;
    }

    /**
     * Restores a trashed note.
     *
     * We try to mimic undelete.php to get all versions restored too.
     *
     * @return string|array
     *
     * @throws \OCP\Files\NotPermittedException
     *
     * @see owncloud/core/apps/files_trashbin/ajax/undelete.php
     *
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     *
     * @CORS
     */
    public function restoreTrashedNote()
    {
        $filename = $this->request->getParam('file_name');
        $timestamp = (int) $this->request->getParam('timestamp');

        $path = $filename.".d$timestamp";
        $pathParts = pathinfo($path);
        $path = '//'.$pathParts['basename'];

        $pathParts = pathinfo($filename);
        $filename = $pathParts['basename'];

        $restoreResult = Trashbin::restore($path, $filename, $timestamp);

        $data = [];
        $data['result'] = $restoreResult;
        $data['path'] = $path;
        $data['filename'] = $filename;

        return $data;
    }
}
