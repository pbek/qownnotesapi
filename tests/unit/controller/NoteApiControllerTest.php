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

use OCP\IRequest;
use Test\TestCase;


class NoteApiControllerTest extends TestCase {

	/** @var NoteApiController $controller */
	private $controller;

	/** @var IRequest $request */
	private $request;

	public function setUp() {
		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();

		$user = "admin";
		$this->loginAsUser( $user );

		$this->controller = new NoteApiController( 'qownnotesapi', $user, $this->request );
	}

	public function testGetAllVersions() {
		$fileName = "/Notes/some-not-existing-test-file.txt";
		$this->request->expects( $this->any() )
			->method( "getParam" )
			->with( "file_name" )
			->willReturn( $fileName );

		$result = $this->controller->getAllVersions();

		$this->assertArrayHasKey( "file_name", $result );
		$this->assertArrayHasKey( "versions", $result );
//		$this->assertArraySubset( array( "file_name" => $fileName ), $result );
		$this->assertEquals( $fileName, $result["file_name"] );
	}

	public function testGetAppInfo() {
		$path = "/Notes";
		$this->request->expects( $this->any() )
			->method( "getParam" )
			->with( "notes_path" )
			->willReturn( $path );

		$result = $this->controller->getAppInfo();

		$this->assertArrayHasKey( "versions_app", $result );
		$this->assertArrayHasKey( "trash_app", $result );
		$this->assertArrayHasKey( "versioning", $result );
		$this->assertArrayHasKey( "app_version", $result );
		$this->assertArrayHasKey( "server_version", $result );
		$this->assertArrayHasKey( "notes_path_exists", $result );
		$this->assertTrue( $result["versions_app"] );
		$this->assertTrue( $result["trash_app"] );
		$this->assertEquals( $result["app_version"], \OC::$server->getConfig()->getAppValue('qownnotesapi', 'installed_version') );
	}

	public function testGetTrashedNotes() {
		$this->request->expects( $this->at(0) )
			->method( "getParam" )
			->with( "dir", "" )
			->willReturn( "/Notes" );
        $this->request->expects( $this->at(1) )
            ->method( "getParam" )
            ->with( "extensions", array() )
            ->willReturn( array() );
		$this->request->expects( $this->at(2) )
			->method( "getParam" )
			->with( "sort", "mtime" )
			->willReturn( "mtime" );

		$result = $this->controller->getTrashedNotes();

		$this->assertArrayHasKey( "directory", $result );
		$this->assertArrayHasKey( "notes", $result );
		$this->assertEquals( $result["directory"], "Notes" );
		$this->assertEquals( $result["notes"], array() );
	}

	public function testRestoreTrashedNote() {
		$file = "some-not-existing-test-file.txt";
		$timestamp = time();

		$this->request->expects( $this->at(0) )
			->method( "getParam" )
			->with( "file_name" )
			->willReturn( "/Notes/$file" );
		$this->request->expects( $this->at(1) )
			->method( "getParam" )
			->with( "timestamp" )
			->willReturn( $timestamp );

		$result = $this->controller->restoreTrashedNote();

		$this->assertArrayHasKey( "result", $result );
		$this->assertArrayHasKey( "path", $result );
		$this->assertArrayHasKey( "filename", $result );
		$this->assertFalse( $result["result"] );
		$this->assertEquals( $result["path"], "//$file.d$timestamp" );
		$this->assertEquals( $result["filename"], $file );
	}
}
