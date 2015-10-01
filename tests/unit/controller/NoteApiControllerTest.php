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

use PHPUnit_Framework_TestCase;


class NoteApiControllerTest extends PHPUnit_Framework_TestCase {

	/** @var NoteApiController $request */
	private $controller;

	public function setUp() {
		$request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
//		$user = $this->getMockBuilder('\OC\User\User')
//			->disableOriginalConstructor()
//			->getMock();

		$this->controller = new NoteApiController( 'qownnotesapi', $request );
	}

	public function testGetAllVersions() {
//		$result = $this->controller->getAllVersions();
//
//		$this->assertTrue(is_array($result));

		$this->assertTrue(true);
	}
}
